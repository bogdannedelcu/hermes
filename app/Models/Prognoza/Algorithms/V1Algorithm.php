<?php
namespace App\Models\Prognoza\Algorithms;

/**
 * V1 simple average estimation algorithm.
 * Groups historical data by day-type + hour, computes unweighted average.
 * Temporal cutoff: year(far_datetime) < year(eStart) (year-boundary).
 */
trait V1Algorithm
{
    private function selectSimpleHistoryValuesV1($eStart, $eEnd, $estimationOptions, $excludePODs, $history = false)
    {
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

        $podWhere = isset($estimationOptions->pod) ? "AND far.pod = '{$estimationOptions->pod}'" : '';
        $exclPODs = !empty($excludePODs)           ? "AND far.pod NOT IN ($excludePODs)"          : '';

        $maxMonths    = '2000-01-01';
        $maxMonthsWhr = '';
        if ($estimationOptions->max_months_before) {
            $mxd          = \DateTime::createFromFormat('Y-m-d', substr($eStart, 0, -2) . '01');
            $maxMonths    = $mxd->modify("-{$estimationOptions->max_months_before} month")->format('Y-m-d');
            $maxMonthsWhr = " AND far.dt >= '$maxMonths' ";
        }

        $this->writeData('DROP TEMPORARY TABLE tSimpleValues');

        $temporalCutoff = "year(far.far_datetime) < year('$eStart')";
        $aggFn          = "AVG(A.far_ea)";

        if ($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut')) {
            $sql = "CREATE TEMPORARY TABLE tSimpleValues SELECT 1 AS supplier_id,
                    $customerID AS customer_id,
                    far.pod,
                    far.far_datetime,
                    CASE WHEN wfd.mapping IS NOT NULL THEN wfd.mapping
                         WHEN DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3
                         ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex,
                    SUM(far.far_ea) AS far_ea
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
            $sql = "CREATE TEMPORARY TABLE tSimpleValues SELECT 1 AS supplier_id,
                    $customerID AS customer_id,
                    far.pod,
                    far.far_datetime,
                    CASE WHEN wfd.mapping IS NOT NULL THEN wfd.mapping
                         WHEN DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3
                         ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex,
                    SUM(far.far_ea) AS far_ea
                    FROM customer_far_$customerID far
                    LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND date(far.far_datetime) = wfd.free_date
                    WHERE $temporalCutoff $podWhere $exclPODs $maxMonthsWhr AND ($rangeDays)
                    GROUP BY far.pod, far.far_datetime";
            $this->writeData($sql);
        }

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
                    CAST($aggFn AS DECIMAL(20,8)) AS far_ea
                FROM tdates_{$estimationOptions->wfdProfile} AS tdates $fi
                JOIN tSimpleValues A ON $joinZ $exclDays $exclHours
                GROUP BY A.customer_id, A.pod, tdates.datetime
                UNION
                SELECT A.supplier_id, A.customer_id, A.pod, tdates.datetime, 'simpla',
                    CAST($aggFn AS DECIMAL(20,8)) AS far_ea
                FROM tdates_{$estimationOptions->wfdProfile} AS tdates $fi
                JOIN tSimpleValues A ON $joinN $exclDays $exclHours
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
