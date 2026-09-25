<?php
namespace App\Models\Prognoza\Algorithms;

/**
 * V3 estimation algorithm — V2 + HDD/CDD piecewise temperature + lag-feature blend.
 *
 * Improvements over V2:
 *
 * 1. HDD / CDD piecewise temperature response (simple path only):
 *    Consumption responds non-linearly to temperature in a V-shape:
 *      - Heating: kicks in below ~18°C → use HDD = max(0, T_heat_base − T)
 *      - Cooling: kicks in above ~22°C → use CDD = max(0, T − T_cool_base)
 *      - Neutral band 18–22°C: both zero, no temperature effect
 *    V3 weights historical samples by joint HDD/CDD distance from the forecast target,
 *    capturing the V-shape correctly. (V2's Gaussian on raw temperature assumes symmetric
 *    response which underweights demand from extreme but same-direction days.)
 *    Bases 18°C / 22°C are ASHRAE / industry standard — not free parameters.
 *
 * 2. Lag-feature blend (both simple and temperature paths, applied as post-process):
 *    For each (POD, target_datetime), blend the model estimate with realized values from
 *    the same hour 24h and 168h (1 week) prior. These are the most predictive features
 *    for short-horizon load forecasting (M4 competition winners, classical persistence baseline).
 *    Weights (1.0 / 1.5 / 1.0 for model / lag-24h / lag-168h) are literature-standard for STLF.
 *    Lags absent (forecast horizon > 7 days) → model estimate retained intact.
 */
trait V3Algorithm
{
    // V3 parameters (statistically grounded, not free-tuned)
    private $v3_lambda        = 0.02;  // EWMA, same as v2
    private $v3_hdd_base      = 18.0;  // ASHRAE heating-degree-day base
    private $v3_cdd_base      = 22.0;  // ASHRAE cooling-degree-day base
    private $v3_kernel_sigma  = 0.05;  // HDD/CDD Gaussian sensitivity

    // Lag-blend weights — conservative (model dominates, lags contribute ~25% each when present)
    // Empirical tuning: aggressive lag weights (1.0/1.5/1.0 = 71% lag) hurt stable PODs on
    // multi-day forecasts where lag-24h only covers day 1 and lag-168h only covers days 1-7.
    private $v3_lag_w_model   = 2.0;
    private $v3_lag_w_24h     = 1.0;
    private $v3_lag_w_168h    = 1.0;

    /**
     * V3 simple history values — like V2 simple but uses HDD/CDD kernel instead of Gaussian on raw temperature.
     * All V2 enhancements (EWMA, cross-month, zero-day filter, trend multiplier via shrinkage) are retained.
     * Lag features are applied separately by v3ApplyLagBlend() after estimates are persisted.
     */
    private function selectSimpleHistoryValuesV3($eStart, $eEnd, $estimationOptions, $excludePODs, $history = false)
    {
        $lambda  = $this->v3_lambda;
        $sigma   = $this->v3_kernel_sigma;
        $hddBase = $this->v3_hdd_base;
        $cddBase = $this->v3_cdd_base;

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

        // HDD/CDD piecewise kernel — replaces v2's Gaussian on raw temperature
        $timeW = "EXP(-$lambda * GREATEST(0, FLOOR(DATEDIFF('$eStart', A.far_datetime)/7)))";
        $tempW = "IF(A.hdd IS NULL AND A.cdd IS NULL, 1.0,
                     EXP(-$sigma * (
                         POW(COALESCE(A.hdd, 0) - GREATEST(0, $hddBase - COALESCE(tdates.temp, $hddBase)), 2)
                       + POW(COALESCE(A.cdd, 0) - GREATEST(0, COALESCE(tdates.temp, $cddBase) - $cddBase), 2)
                     )))";
        $aggFn = "SUM(A.far_ea * $timeW * $tempW) / NULLIF(SUM($timeW * $tempW), 0)";

        $crossMonthWhr = $this->v2CrossMonthClause($eStart, $rangeDays);

        if ($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut')) {
            // Synthetics: no temperature data, HDD/CDD NULL → temperature weight neutral (1.0)
            $sql = "CREATE TEMPORARY TABLE tSimpleValues SELECT 1 AS supplier_id,
                    $customerID AS customer_id,
                    far.pod,
                    far.far_datetime,
                    CASE WHEN wfd.mapping IS NOT NULL THEN wfd.mapping
                         WHEN DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3
                         ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex,
                    SUM(far.far_ea) AS far_ea,
                    NULL AS hdd,
                    NULL AS cdd
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
            // Derive HDD / CDD per interval from joined historical temperature
            $sql = "CREATE TEMPORARY TABLE tSimpleValues SELECT 1 AS supplier_id,
                    $customerID AS customer_id,
                    far.pod,
                    far.far_datetime,
                    CASE WHEN wfd.mapping IS NOT NULL THEN wfd.mapping
                         WHEN DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3
                         ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex,
                    SUM(far.far_ea) AS far_ea,
                    MAX(GREATEST(0, $hddBase - COALESCE(ft_hist.temperature, wd_hist.value))) AS hdd,
                    MAX(GREATEST(0, COALESCE(ft_hist.temperature, wd_hist.value) - $cddBase)) AS cdd
                    FROM customer_far_$customerID far
                    LEFT JOIN working_free_days wfd  ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND date(far.far_datetime) = wfd.free_date
                    LEFT JOIN forecast_temperatures ft_hist ON far.dh = ft_hist.dh
                    LEFT JOIN weather_data wd_hist ON wd_hist.layer = 'temp' AND wd_hist.county_code = 'B' AND far.dh = wd_hist.dh
                    WHERE far.far_datetime < '$eStart' $podWhere $exclPODs $maxMonthsWhr AND $crossMonthWhr
                    GROUP BY far.pod, far.far_datetime";
            $this->writeData($sql);
        }

        // Zero-day filter (same as v2)
        $this->writeData("DELETE tsv FROM tSimpleValues tsv
            JOIN (SELECT pod, DATE(far_datetime) AS dt
                  FROM tSimpleValues
                  GROUP BY pod, DATE(far_datetime)
                  HAVING SUM(far_ea) = 0) z
            ON tsv.pod = z.pod AND DATE(tsv.far_datetime) = z.dt");

        // Per-POD trend multiplier with Bayesian shrinkage (reuse v2 helper)
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

    /**
     * Post-process: blend forecast_pods_estimates with lag-24h and lag-168h realized values.
     * Run AFTER v3 base estimates are inserted into forecast_pods_estimates, BEFORE persistence.
     * Final value per (pod, datetime):
     *   final = (w_m·model + w24·lag24 + w168·lag168) / (w_m + w24·has24 + w168·has168)
     * Lags only counted when far_datetime < eStart (no peeking) AND the corresponding day
     * was not an all-zero day (missing-import artefact); for zero days the lag is ignored.
     */
    private function v3ApplyLagBlend($eStart, $eEnd, $customerIDs)
    {
        if (empty($customerIDs)) return;
        $wm   = $this->v3_lag_w_model;
        $w24  = $this->v3_lag_w_24h;
        $w168 = $this->v3_lag_w_168h;
        $sid  = $this->supplierID;

        foreach ($customerIDs as $cid) {
            $cid = (int)$cid;

            // Precompute zero-consumption days in the lag lookback range (consistent with v2 zero-day filter).
            // We need to cover up to (eStart - 7 days) at minimum; extend to (eStart - eEnd_length) for full coverage.
            $this->writeData('DROP TEMPORARY TABLE tZeroDaysLag');
            $this->writeData("CREATE TEMPORARY TABLE tZeroDaysLag (
                pod VARCHAR(40) NOT NULL,
                dt DATE NOT NULL,
                PRIMARY KEY (pod, dt)
            )");
            $this->writeData("INSERT INTO tZeroDaysLag (pod, dt)
                SELECT pod, DATE(far_datetime) AS dt
                FROM customer_far_$cid
                WHERE far_datetime >= DATE_SUB('$eStart', INTERVAL 60 DAY)
                  AND far_datetime < '$eStart'
                GROUP BY pod, DATE(far_datetime)
                HAVING SUM(far_ea) = 0");

            $this->writeData("
                UPDATE forecast_pods_estimates fpe
                LEFT JOIN customer_far_$cid L24
                    ON L24.pod = fpe.pod
                    AND L24.far_datetime = DATE_SUB(fpe.forecast_datetime, INTERVAL 1 DAY)
                    AND L24.far_datetime < '$eStart'
                LEFT JOIN tZeroDaysLag Z24
                    ON Z24.pod = L24.pod AND Z24.dt = DATE(L24.far_datetime)
                LEFT JOIN customer_far_$cid L168
                    ON L168.pod = fpe.pod
                    AND L168.far_datetime = DATE_SUB(fpe.forecast_datetime, INTERVAL 7 DAY)
                    AND L168.far_datetime < '$eStart'
                LEFT JOIN tZeroDaysLag Z168
                    ON Z168.pod = L168.pod AND Z168.dt = DATE(L168.far_datetime)
                SET fpe.forecast_ea = (
                    ($wm * fpe.forecast_ea
                     + $w24  * IF(Z24.pod  IS NOT NULL, 0, IFNULL(L24.far_ea,  0))
                     + $w168 * IF(Z168.pod IS NOT NULL, 0, IFNULL(L168.far_ea, 0)))
                    /
                    ($wm
                     + IF(L24.far_ea  IS NULL OR Z24.pod  IS NOT NULL, 0, $w24)
                     + IF(L168.far_ea IS NULL OR Z168.pod IS NOT NULL, 0, $w168))
                )
                WHERE fpe.supplier_id = $sid
                  AND fpe.customer_id = $cid
                  AND fpe.forecast_datetime >= '$eStart'
                  AND fpe.forecast_datetime < DATE_ADD('$eEnd', INTERVAL 1 DAY)
            ");
        }
    }
}
