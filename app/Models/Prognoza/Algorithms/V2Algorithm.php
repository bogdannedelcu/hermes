<?php
namespace App\Models\Prognoza\Algorithms;

/**
 * V2 estimation algorithm — EWMA + temperature kernel + cross-month + trend multiplier.
 *
 * Improvements over V1:
 *   1. Exact temporal cutoff: far_datetime < eStart (not year boundary).
 *   2. EWMA time-decay: λ=0.02 per week — recent data weighted more heavily.
 *   3. Temperature similarity: Gaussian kernel σ=0.05 on (T_hist − T_forecast)²,
 *      pulling estimates toward days with similar temperature.
 *   4. Zero-day filter: excludes from training any day where a POD's total consumption is 0
 *      (guards against missing-import artefacts corrupting the EWMA).
 *   5. Cross-month training: in addition to same-month-from-prior-years, include the most
 *      recent N weeks regardless of month — captures regime changes (new equipment, expanded usage)
 *      that aren't visible in same-month-only history. Day-of-week + hour matching still applies,
 *      and temperature kernel down-weights days at very different temperature.
 *   6. Trend multiplier: per-POD ratio of consumption in last N days vs same window one year prior.
 *      Multiplies the final estimate. Clamped to [0.3, 3.0]. Detects PODs whose consumption shifted
 *      across the year (e.g. POD with 3.4× growth between Apr 2025 and Apr 2026).
 *
 * Temperature data sources (in order of preference):
 *   - forecast_temperatures (historical, up to ~Jan 2026)
 *   - weather_data layer='temp' (covers forecast period and near history)
 *   When both are NULL the temperature weight defaults to 1.0 (neutral).
 */
trait V2Algorithm
{
    // V2 algorithm parameters — shared between simple and temperature paths
    private $v2_lambda          = 0.02;  // EWMA weekly decay
    private $v2_sigma           = 0.05;  // Gaussian temperature sensitivity (simple path)
    private $v2_crossMonthWeeks = 12;    // include last N weeks regardless of month
    private $v2_trendWindowDays = 60;    // window for trend multiplier
    private $v2_trendMinDays    = 5;     // min sample size per window to compute the trend
    private $v2_trendMin        = 0.3;   // safety clamp (extreme outliers)
    private $v2_trendMax        = 3.0;   // safety clamp (extreme outliers)

    /**
     * Populate the per-POD trend multiplier table using Bayesian shrinkage toward 1.0.
     *
     * For each POD, we treat daily log-consumption as draws from two distributions:
     *  - Recent: last N days before eStart (excluding zero-consumption days)
     *  - Prior:  same N-day window one year earlier
     *
     * The signed log-ratio  d = mean(log(recent)) - mean(log(prior))  estimates the trend in log space.
     * Its standard error squared:  se² = Var(log(recent))/n_r + Var(log(prior))/n_p (Welch t-test setup).
     * The squared t-statistic:     t² = d² / se².
     *
     * Empirical-Bayes shrinkage weight:  w = t² / (t² + 1)  ∈ [0, 1]
     *   - Strong evidence (|t| ≫ 1):  w → 1, multiplier ≈ ratio (full effect)
     *   - Weak evidence  (|t| ≪ 1):  w → 0, multiplier → 1   (no adjustment, just noise)
     *
     * Final multiplier:  exp(d * w)  clamped to [v2_trendMin, v2_trendMax] for safety against degenerate inputs.
     *
     * PODs with fewer than v2_trendMinDays observations in either window are skipped
     * (downstream LEFT JOIN defaults them to multiplier = 1.0).
     */
    private function v2PopulateTrendMultipliers($eStart, $customerID, $podWhereTrend = '', $exclPODsTrend = '')
    {
        $this->writeData('DROP TEMPORARY TABLE tTrendMultipliers');
        $this->writeData("CREATE TEMPORARY TABLE tTrendMultipliers (
            pod VARCHAR(40) NOT NULL PRIMARY KEY,
            multiplier DECIMAL(10,4) NOT NULL DEFAULT 1.0
        )");

        $this->writeData("INSERT INTO tTrendMultipliers (pod, multiplier)
            SELECT
                sd.pod,
                LEAST({$this->v2_trendMax}, GREATEST({$this->v2_trendMin},
                    EXP(
                        sd.log_diff *
                        (sd.log_diff * sd.log_diff) / (sd.log_diff * sd.log_diff + sd.se_sq)
                    )
                )) AS multiplier
            FROM (
                SELECT
                    pod,
                    AVG(CASE WHEN win = 'r' THEN log_ea END) - AVG(CASE WHEN win = 'p' THEN log_ea END) AS log_diff,
                    COALESCE(VAR_SAMP(CASE WHEN win = 'r' THEN log_ea END), 0) / NULLIF(SUM(CASE WHEN win = 'r' THEN 1 ELSE 0 END), 0)
                  + COALESCE(VAR_SAMP(CASE WHEN win = 'p' THEN log_ea END), 0) / NULLIF(SUM(CASE WHEN win = 'p' THEN 1 ELSE 0 END), 0) AS se_sq,
                    SUM(CASE WHEN win = 'r' THEN 1 ELSE 0 END) AS n_r,
                    SUM(CASE WHEN win = 'p' THEN 1 ELSE 0 END) AS n_p
                FROM (
                    SELECT pod, 'r' AS win, LN(SUM(far_ea)) AS log_ea
                    FROM customer_far_$customerID
                    WHERE far_datetime >= DATE_SUB('$eStart', INTERVAL {$this->v2_trendWindowDays} DAY)
                      AND far_datetime < '$eStart'
                      $podWhereTrend $exclPODsTrend
                    GROUP BY pod, DATE(far_datetime)
                    HAVING SUM(far_ea) > 0
                    UNION ALL
                    SELECT pod, 'p' AS win, LN(SUM(far_ea)) AS log_ea
                    FROM customer_far_$customerID
                    WHERE far_datetime >= DATE_SUB(DATE_SUB('$eStart', INTERVAL 1 YEAR), INTERVAL {$this->v2_trendWindowDays} DAY)
                      AND far_datetime < DATE_SUB('$eStart', INTERVAL 1 YEAR)
                      $podWhereTrend $exclPODsTrend
                    GROUP BY pod, DATE(far_datetime)
                    HAVING SUM(far_ea) > 0
                ) daily_log
                GROUP BY pod
                HAVING n_r >= {$this->v2_trendMinDays} AND n_p >= {$this->v2_trendMinDays}
            ) sd
            WHERE sd.se_sq > 0 AND sd.log_diff IS NOT NULL");
    }

    /**
     * Returns a WHERE clause that broadens history to include the last N weeks regardless of month,
     * while keeping the existing same-month-from-prior-years range.
     * Use with FAR alias `far` (refers to `far.far_datetime`).
     */
    private function v2CrossMonthClause($eStart, $rangeDays)
    {
        return "(($rangeDays) OR far.far_datetime >= DATE_SUB('$eStart', INTERVAL {$this->v2_crossMonthWeeks} WEEK))";
    }

    private function selectSimpleHistoryValuesV2($eStart, $eEnd, $estimationOptions, $excludePODs, $history = false)
    {
        $lambda          = $this->v2_lambda;
        $sigma           = $this->v2_sigma;
        $crossMonthWeeks = $this->v2_crossMonthWeeks;
        $trendWindowDays = $this->v2_trendWindowDays;
        $trendMin        = $this->v2_trendMin;
        $trendMax        = $this->v2_trendMax;

        $customerID = $estimationOptions->customer;
        $marginDays = $estimationOptions->outliers ? $estimationOptions->outliers_radius : 0;

        $rawStart = \DateTime::createFromFormat('Y-m-d', $eStart)->format('Y-m-01');
        $rawEnd   = \DateTime::createFromFormat('Y-m-d', $eEnd)->format('Y-m-t');

        if ($estimationOptions->intervalZ != corelare_data || $estimationOptions->intervalN != corelare_data) {
            if ($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut'))
                $synthIntervalWhr = $this->getDateRangeWhere($customerID, $this->supplierID, $rawStart, $rawEnd, $marginDays, true);
            else
                $rangeDays = $this->getDateRangeWhere($customerID, $this->supplierID, $rawStart, $rawEnd, $marginDays);
        } else {
            if ($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut'))
                $synthIntervalWhr = $this->getDateRangeWhere($customerID, $this->supplierID, $rawStart, $rawEnd, $marginDays, true);
            else
                $rangeDays = $this->getDateRangeWhere($customerID, $this->supplierID, $eStart, $eEnd, $marginDays);
        }

        $podWhere      = isset($estimationOptions->pod) ? "AND far.pod = '{$estimationOptions->pod}'" : '';
        $podWhereTrend = isset($estimationOptions->pod) ? "AND pod = '{$estimationOptions->pod}'"     : '';
        $exclPODs      = !empty($excludePODs)           ? "AND far.pod NOT IN ($excludePODs)"          : '';
        $exclPODsTrend = !empty($excludePODs)           ? "AND pod NOT IN ($excludePODs)"              : '';

        $maxMonths    = '2000-01-01';
        $maxMonthsWhr = '';
        if ($estimationOptions->max_months_before) {
            $mxd          = \DateTime::createFromFormat('Y-m-d', substr($eStart, 0, -2) . '01');
            $maxMonths    = $mxd->modify("-{$estimationOptions->max_months_before} month")->format('Y-m-d');
            $maxMonthsWhr = " AND far.dt >= '$maxMonths' ";
        }

        $this->writeData('DROP TEMPORARY TABLE tSimpleValues');

        // Combined weight: EWMA (time-decay) × Gaussian (temperature similarity)
        // Falls back to weight=1 when A.temperature or tdates.temp is NULL.
        $timeW = "EXP(-$lambda * GREATEST(0, FLOOR(DATEDIFF('$eStart', A.far_datetime)/7)))";
        $tempW = "IF(A.temperature IS NULL OR tdates.temp IS NULL, 1.0, EXP(-$sigma * POW(A.temperature - tdates.temp, 2)))";
        $aggFn = "SUM(A.far_ea * $timeW * $tempW) / NULLIF(SUM($timeW * $tempW), 0)";

        $crossMonthWhr = $this->v2CrossMonthClause($eStart, $rangeDays);

        if ($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut')) {
            // Synthetics don't have temperature data — temperature weight is neutral (1.0)
            $sql = "CREATE TEMPORARY TABLE tSimpleValues SELECT 1 AS supplier_id,
                    $customerID AS customer_id,
                    far.pod,
                    far.far_datetime,
                    CASE WHEN wfd.mapping IS NOT NULL THEN wfd.mapping
                         WHEN DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3
                         ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex,
                    SUM(far.far_ea) AS far_ea,
                    NULL AS temperature
                    FROM (SELECT ROW_NUMBER() OVER (ORDER BY synthetics_datetime) AS far_id,
                          'sintetic' AS pod,
                          DATE_ADD(fs.synthetics_datetime, INTERVAL I.minute MINUTE) AS far_datetime,
                          fs.synthetic_ea / 4 AS far_ea,
                          concat(fs.dt,' ',hour(`synthetics_datetime`)) AS dh,
                          fs.dt AS dt,
                          floor((hour(fs.synthetics_datetime) * 60 + I.minute) / 15) AS `interval`
                          FROM forecast_synthetics fs
                          JOIN (SELECT 0 AS minute UNION SELECT 15 UNION SELECT 30 UNION SELECT 45) I
                          ON fs.customer_id = $customerID
                          WHERE fs.dt > '$maxMonths' AND $synthIntervalWhr AND fs.dt < '$eStart') far
                    LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND date(far.far_datetime) = wfd.free_date
                    GROUP BY far.pod, far.far_datetime";
            $this->writeData($sql);
        } else {
            // Cross-month: include same-month-from-prior-years AND recent N weeks regardless of month
            $sql = "CREATE TEMPORARY TABLE tSimpleValues SELECT 1 AS supplier_id,
                    $customerID AS customer_id,
                    far.pod,
                    far.far_datetime,
                    CASE WHEN wfd.mapping IS NOT NULL THEN wfd.mapping
                         WHEN DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3
                         ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex,
                    SUM(far.far_ea) AS far_ea,
                    MAX(COALESCE(ft_hist.temperature, wd_hist.value)) AS temperature
                    FROM customer_far_$customerID far
                    LEFT JOIN working_free_days wfd  ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND date(far.far_datetime) = wfd.free_date
                    LEFT JOIN forecast_temperatures ft_hist ON far.dh = ft_hist.dh
                    LEFT JOIN weather_data wd_hist ON wd_hist.layer = 'temp' AND wd_hist.county_code = 'B' AND far.dh = wd_hist.dh
                    WHERE far.far_datetime < '$eStart' $podWhere $exclPODs $maxMonthsWhr AND $crossMonthWhr
                    GROUP BY far.pod, far.far_datetime";
            $this->writeData($sql);
        }

        // Zero-day filter: remove any day where a POD's total consumption is 0
        // (catches missing-import artefacts, e.g. March 2026 all-zero readings)
        $this->writeData("DELETE tsv FROM tSimpleValues tsv
            JOIN (SELECT pod, DATE(far_datetime) AS dt
                  FROM tSimpleValues
                  GROUP BY pod, DATE(far_datetime)
                  HAVING SUM(far_ea) = 0) z
            ON tsv.pod = z.pod AND DATE(tsv.far_datetime) = z.dt");

        // Per-POD trend multiplier
        $this->v2PopulateTrendMultipliers($eStart, $customerID, $podWhereTrend, $exclPODsTrend);


        $exclDays  = $this->getExcludedDays($estimationOptions);
        $exclHours = $this->getExcludedHours($estimationOptions);

        $joinZ = $estimationOptions->intervalZ == corelare_data
            ? "month(A.far_datetime) = month(tdates.datetime) AND day(A.far_datetime) = day(tdates.datetime) AND hour(A.far_datetime) = hour(tdates.datetime) AND fi.interval_type = 'Z'"
            : "A.dayIndex = tdates.dayIndex AND hour(A.far_datetime) = hour(tdates.datetime) AND fi.interval_type = 'Z'";

        $joinN = $estimationOptions->intervalN == corelare_data
            ? "month(A.far_datetime) = month(tdates.datetime) AND day(A.far_datetime) = day(tdates.datetime) AND hour(A.far_datetime) = hour(tdates.datetime) AND fi.interval_type = 'N'"
            : "A.dayIndex = tdates.dayIndex AND hour(A.far_datetime) = hour(tdates.datetime) AND fi.interval_type = 'N'";

        $fi = "JOIN forecast_intervals fi ON tdates.county_code='B' AND fi.hour = hour(tdates.datetime) AND fi.month = month(tdates.datetime) AND fi.customer_id = COALESCE((SELECT customer_id FROM forecast_intervals WHERE customer_id = $customerID LIMIT 1), 0)";

        // Final aggregation multiplied by per-POD trend multiplier (default 1.0 if no comparison data)
        $sql = "SELECT A.supplier_id, A.customer_id, A.pod, tdates.datetime, 'simpla',
                    CAST(($aggFn) * COALESCE(MAX(tm.multiplier), 1.0) AS DECIMAL(20,8)) AS far_ea
                FROM tdates_{$estimationOptions->wfdProfile} AS tdates $fi
                JOIN tSimpleValues A ON $joinZ $exclDays $exclHours
                LEFT JOIN tTrendMultipliers tm ON tm.pod = A.pod
                GROUP BY A.customer_id, A.pod, tdates.datetime
                UNION
                SELECT A.supplier_id, A.customer_id, A.pod, tdates.datetime, 'simpla',
                    CAST(($aggFn) * COALESCE(MAX(tm.multiplier), 1.0) AS DECIMAL(20,8)) AS far_ea
                FROM tdates_{$estimationOptions->wfdProfile} AS tdates $fi
                JOIN tSimpleValues A ON $joinN $exclDays $exclHours
                LEFT JOIN tTrendMultipliers tm ON tm.pod = A.pod
                GROUP BY A.customer_id, A.pod, tdates.datetime";

        if ($history) {
            $ret['estimatedSQLValues'] = $sql;
            $ret['historySQLValues']   = "SELECT A.far_datetime, CAST(SUM(A.far_ea) AS DECIMAL(20,8)) AS far_ea
                FROM tdates_{$estimationOptions->wfdProfile} AS tdates $fi
                JOIN tSimpleValues A ON $joinZ $exclDays $exclHours
                GROUP BY hour(A.far_datetime), date(A.far_datetime)
                UNION
                SELECT A.far_datetime, CAST(SUM(A.far_ea) AS DECIMAL(20,8)) AS far_ea
                FROM tdates_{$estimationOptions->wfdProfile} AS tdates $fi
                JOIN tSimpleValues A ON $joinN $exclDays $exclHours
                GROUP BY hour(A.far_datetime), date(A.far_datetime)";
            $ret['historyInterval']    = "SELECT date(A.far_datetime) AS value
                FROM tdates_{$estimationOptions->wfdProfile} AS tdates
                JOIN tSimpleValues A ON tdates.county_code='B' AND month(A.far_datetime) = month(tdates.datetime)
                    AND day(A.far_datetime) = day(tdates.datetime) AND hour(A.far_datetime) = hour(tdates.datetime)
                    $exclDays $exclHours
                GROUP BY date(A.far_datetime)";
            $ret['historyType'] = 'istorice';
            return $ret;
        }

        return $sql;
    }
}
