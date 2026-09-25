<?php
namespace App\Models\Prognoza\Algorithms;

/**
 * V4 estimation algorithm — Chronos-2 (Amazon foundation model) via local FastAPI microservice.
 *
 * V4 calls a pre-trained time-series foundation model (Chronos-2, October 2025) instead of
 * fitting per-customer statistics. The model takes a recent history window per POD and returns
 * a probabilistic forecast (p10 / p50 / p90) for the next N steps.
 *
 * Wire path:  V4Algorithm → HTTP → 127.0.0.1:8081 (FastAPI) → local Chronos-2 pipeline (CPU)
 *
 * V4 differs from v1/v2/v3 structurally:
 *   - No SQL aggregation over history: history is read out, sent to Chronos, results written back.
 *   - No per-customer EWMA / HDD-CDD / trend multiplier — Chronos learned those patterns
 *     globally during pre-training on billions of time-series points.
 *   - Operates per (customer, POD) — one request item per POD, batched up to MAX_BATCH_SIZE.
 *
 * Why Chronos-2 over TimesFM (pivot 2026-05-29):
 *   - Encoder-only forecast head emits 1024+ steps in a single forward pass (no autoregression);
 *     TimesFM-2.0 caps at 128 steps on the Vertex Model Garden default container.
 *   - Outperforms TimesFM-2.5 on recent 2026 benchmarks (TSFM.ai, Decathlon, arXiv:2602.10848).
 *   - Self-hostable from HuggingFace, runs on CPU. Zero recurring cost vs ~$1.50/hour on Vertex.
 *
 * Fallback: if the Chronos service is unreachable / errors out, the customer is skipped
 * (no v4 row inserted), leaving v3 as the most recent algorithm visible in the UI.
 */
trait V4Algorithm
{
    private $v4_service_url   = 'http://127.0.0.1:8081/forecast';
    private $v4_max_context   = 2016;   // 15-min intervals — ~21 days of recent history
    private $v4_min_context   = 96;     // refuse PODs with < 1 day of usable history
    private $v4_batch_size    = 8;      // PODs per HTTP request (well under MAX_BATCH_SIZE=32)
    private $v4_timeout       = 180;    // seconds per HTTP call
    private $v4_frequency     = '15min';
    private $v4_step_minutes  = 15;
    private $v4_insert_chunk  = 500;    // rows per INSERT statement
    private $v4_steps_per_day = 96;     // 24h * 4 slots/h

    /**
     * Drive V4 across one or more days. Production estimates D+1 (eStart == eEnd == tomorrow),
     * but backtests can ask for a longer window. To match production behaviour we
     * iterate day-by-day: each day's forecast is anchored on REAL prior-day actuals,
     * not on v4's own previous predictions. This matches the rolling D+1 protocol
     * documented in TD-B1 and gives the same numbers we measure in the validation script.
     */
    private function v4EstimateCustomers($eStart, $eEnd, $customerIDs)
    {
        if (empty($customerIDs)) return 0;
        $supplierID = $this->supplierID;

        $insertedAcross = 0;

        // Generate the list of forecast days (inclusive)
        $startDT = (new \DateTime($eStart))->setTime(0, 0, 0);
        $endDT   = (new \DateTime($eEnd))->setTime(0, 0, 0);
        $days = [];
        for ($d = clone $startDT; $d <= $endDT; $d->modify('+1 day')) {
            $days[] = $d->format('Y-m-d');
        }
        log_message('info', "V4: rolling D+1 — " . count($days) . " day(s) × " . count($customerIDs) . " customer(s)");

        foreach ($customerIDs as $customerID)
        {
            $customerID = (int)$customerID;
            $tableExists = (int)$this->getValue("SELECT COUNT(*) AS value
                FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = 'customer_far_$customerID'");
            if (!$tableExists) continue;

            // Wipe any prior v4 rows for the full window so re-runs are idempotent
            $this->writeData("DELETE FROM forecast_pods_estimates
                WHERE supplier_id = $supplierID AND customer_id = $customerID
                  AND estimation_type = 'timesfm'
                  AND forecast_datetime >= '$eStart'
                  AND forecast_datetime <  DATE_ADD('$eEnd', INTERVAL 1 DAY)");

            // Resolve county once per customer (used to fetch weather covariates).
            // Customers can have PODs in different counties, but we look up the weather
            // per-POD anyway; here we just cache it for active-POD discovery.
            $podCounty = $this->v4GetPodCountyMap($customerID);

            // PODs active at the START of the window (extends across all days)
            $pods = $this->v4GetActivePods($customerID, $eStart);
            if (empty($pods)) continue;

            foreach ($days as $day)
            {
                $dayStart = $day . ' 00:00:00';
                foreach (array_chunk($pods, $this->v4_batch_size) as $batch)
                {
                    $items = [];
                    foreach ($batch as $pod)
                    {
                        // History ends one step before this day starts. For day == eStart that's
                        // the same cutoff as before; for subsequent days, the prior days' real
                        // actuals from customer_far_X enter the context — true rolling D+1.
                        $county = $podCounty[$pod] ?? 'B';
                        $history = $this->v4GetPodHistoryWithCovariates(
                            $customerID, $pod, $county, $dayStart, $this->v4_max_context);
                        if (count($history) < $this->v4_min_context) {
                            log_message('info', "V4: POD $pod skipped on $day — "
                                . count($history) . " usable history points");
                            continue;
                        }
                        $future = $this->v4GetFutureCovariates($county, $dayStart, $this->v4_steps_per_day);
                        $items[] = [
                            'pod_id'            => $pod,
                            'history'           => $history,
                            'horizon_steps'     => $this->v4_steps_per_day,
                            'future_covariates' => $future,
                        ];
                    }
                    if (empty($items)) continue;

                    $resp = $this->v4CallService([
                        'frequency' => $this->v4_frequency,
                        'items'     => $items,
                    ]);
                    if ($resp === null) {
                        log_message('error', "V4: service call failed for customer $customerID day $day");
                        continue;
                    }

                    $dayEnd = (new \DateTime($day))->modify('+1 day')->format('Y-m-d');
                    $insertedAcross += $this->v4InsertForecasts(
                        $customerID, $resp['results'] ?? [], $day, $dayEnd);
                }
            }
        }
        return $insertedAcross;
    }

    private function v4GetPodCountyMap($customerID)
    {
        $sql = "SELECT p.pod_no, COALESCE(c.county_code,'B') AS county_code
                FROM pods p LEFT JOIN counties c ON c.county = p.county
                WHERE p.customer_id = $customerID";
        $rows = $this->getArray($sql);
        $map = [];
        foreach ($rows as $r) $map[$r['pod_no']] = $r['county_code'];
        return $map;
    }

    /**
     * Future covariates (temp + feelslike) for the next $steps 15-min intervals starting
     * at $dayStart. Sourced from weather_data at hourly grain, forward-filled to 15-min.
     * In production for D+1 estimation, weather_data must contain at least the forecast
     * hours; for backtests on past months it always does (observed data lives there).
     */
    private function v4GetFutureCovariates($county, $dayStart, $steps)
    {
        $county = addslashes($county);
        $endStr = (new \DateTime($dayStart))->modify('+' . ($steps * $this->v4_step_minutes) . ' minutes')
                                            ->format('Y-m-d H:i:s');
        $sql = "SELECT weather_datetime, layer, value FROM weather_data
                WHERE county_code = '$county' AND layer IN ('temp','feelslike')
                  AND weather_datetime >= '$dayStart' AND weather_datetime < '$endStr'";
        $rows = $this->getArray($sql);

        // Index by hour for temp/feelslike
        $byHour = [];
        foreach ($rows as $r) {
            $h = substr($r['weather_datetime'], 0, 13);    // YYYY-MM-DD HH
            $byHour[$h][$r['layer']] = (float)$r['value'];
        }

        $step = $this->v4_step_minutes * 60;
        $startTs = (new \DateTime($dayStart))->getTimestamp();
        $out = [];
        for ($i = 0; $i < $steps; $i++) {
            $ts = $startTs + $i * $step;
            $dt = date('Y-m-d H:i:s', $ts);
            $hKey = substr($dt, 0, 13);
            $entry = ['datetime' => $dt];
            if (isset($byHour[$hKey]['temp']))      $entry['temp']      = $byHour[$hKey]['temp'];
            if (isset($byHour[$hKey]['feelslike'])) $entry['feelslike'] = $byHour[$hKey]['feelslike'];
            $out[] = $entry;
        }
        return $out;
    }

    /**
     * Same contiguous-grid history as v4GetPodHistory, but also attaches temp+feelslike
     * covariates per interval. Falls back to no covariate fields if weather missing.
     */
    private function v4GetPodHistoryWithCovariates($customerID, $pod, $county, $eStart, $maxContext)
    {
        $base = $this->v4GetPodHistory($customerID, $pod, $eStart, $maxContext);
        if (empty($base)) return $base;

        // Fetch weather (hourly) over the same window
        $county = addslashes($county);
        $firstDT = $base[0]['datetime'];
        $lastDT  = $base[count($base) - 1]['datetime'];
        $sql = "SELECT weather_datetime, layer, value FROM weather_data
                WHERE county_code='$county' AND layer IN ('temp','feelslike')
                  AND weather_datetime >= '$firstDT' AND weather_datetime <= '$lastDT'";
        $rows = $this->getArray($sql);
        $byHour = [];
        foreach ($rows as $r) {
            $h = substr($r['weather_datetime'], 0, 13);
            $byHour[$h][$r['layer']] = (float)$r['value'];
        }
        if (empty($byHour)) return $base;   // no weather data → univariate path

        // Attach covariates
        foreach ($base as $i => $hp) {
            $hKey = substr($hp['datetime'], 0, 13);
            if (isset($byHour[$hKey]['temp']))      $base[$i]['temp']      = $byHour[$hKey]['temp'];
            if (isset($byHour[$hKey]['feelslike'])) $base[$i]['feelslike'] = $byHour[$hKey]['feelslike'];
        }
        return $base;
    }

    private function v4GetActivePods($customerID, $eStart)
    {
        // PODs that have ANY non-zero readings in the last 90 days — same spirit as v2/v3 zero-day filter.
        $sql = "SELECT pod AS value FROM customer_far_$customerID
                WHERE far_datetime >= DATE_SUB('$eStart', INTERVAL 90 DAY)
                  AND far_datetime <  '$eStart'
                GROUP BY pod
                HAVING SUM(far_ea) > 0
                ORDER BY pod";
        return $this->getValueArray($sql);
    }

    /**
     * Build a contiguous 15-minute grid ending exactly one step before eStart, length up to
     * $maxContext. Missing intervals are linearly interpolated; leading missing intervals
     * (before the first real reading) are forward-filled from the first known value.
     *
     * TimesFM REQUIRES contiguous input — gaps in timestamps break the implicit
     * "next timestamp = last + step" assumption used when mapping forecast outputs to
     * datetimes. On Vertex AI specifically, missing rows are NOT auto-interpolated by
     * the inference server; the caller must materialize the full grid (see Google docs:
     * "If there are missing rows in the time series, you must manually insert them").
     *
     * Returns [] if the POD has too many gaps (>50% of the window) — caller will skip it.
     */
    private function v4GetPodHistory($customerID, $pod, $eStart, $maxContext)
    {
        $pod = addslashes($pod);
        $step = $this->v4_step_minutes * 60;   // seconds

        $eStartTs = (new \DateTime($eStart))->getTimestamp();
        $windowSeconds = $maxContext * $step;
        $windowStartTs = $eStartTs - $windowSeconds;
        $windowStartStr = date('Y-m-d H:i:s', $windowStartTs);

        $sql = "SELECT far_datetime, far_ea FROM customer_far_$customerID
                WHERE pod = '$pod'
                  AND far_datetime >= '$windowStartStr'
                  AND far_datetime <  '$eStart'
                ORDER BY far_datetime ASC";
        $rows = $this->getArray($sql);

        // Need at least SOME data to be usable
        if (count($rows) < $this->v4_min_context) return [];

        // Index by aligned 15-min slot timestamp (snap to 15-min boundary).
        // ALSO: identify all-zero days (missing-import artefacts — same heuristic as v2/v3
        // zero-day filter). Intervals belonging to those days are treated as missing so
        // they get interpolated from neighbouring real days, instead of feeding TimesFM
        // a fake zero signal.
        $byTs = [];
        $sumPerDay = [];
        foreach ($rows as $r) {
            $ts = strtotime($r['far_datetime']);
            $alignedTs = $ts - ($ts % $step);
            $byTs[$alignedTs] = (float)$r['far_ea'];
            $dayKey = date('Y-m-d', $alignedTs);
            $sumPerDay[$dayKey] = ($sumPerDay[$dayKey] ?? 0) + (float)$r['far_ea'];
        }
        $zeroDays = [];
        foreach ($sumPerDay as $d => $s) {
            if ($s == 0.0) $zeroDays[$d] = true;
        }

        // Build the full contiguous grid ending at (eStart - 1 step)
        $lastSlotTs = $eStartTs - $step;
        $firstSlotTs = $lastSlotTs - ($maxContext - 1) * $step;

        $hist = [];
        $present = 0;
        $missingIdxs = [];

        // First pass: pick up known values, mark missing slots.
        // Intervals on a zero-day are treated as missing.
        $values = [];
        $tsList  = [];
        for ($ts = $firstSlotTs; $ts <= $lastSlotTs; $ts += $step) {
            $tsList[] = $ts;
            $dayKey = date('Y-m-d', $ts);
            if (isset($zeroDays[$dayKey])) {
                $values[] = null;
                $missingIdxs[] = count($values) - 1;
            } elseif (isset($byTs[$ts])) {
                $values[] = $byTs[$ts];
                $present++;
            } else {
                $values[] = null;
                $missingIdxs[] = count($values) - 1;
            }
        }

        $total = count($values);
        if ($total === 0) return [];

        // Refuse PODs where less than half the grid is real data — TimesFM accuracy
        // degrades sharply on heavily-interpolated context.
        if ($present < intdiv($total, 2)) {
            log_message('info', "V4: POD $pod skipped — only $present/$total real points in 15-min grid");
            return [];
        }

        // Linear interpolation for interior gaps; edge fills with nearest known value.
        // Find first/last non-null indices.
        $firstNonNull = null; $lastNonNull = null;
        foreach ($values as $i => $v) {
            if ($v !== null) { $firstNonNull = $i; break; }
        }
        for ($i = $total - 1; $i >= 0; $i--) {
            if ($values[$i] !== null) { $lastNonNull = $i; break; }
        }
        if ($firstNonNull === null || $lastNonNull === null) return [];

        // Forward-fill leading nulls with first known value
        for ($i = 0; $i < $firstNonNull; $i++) $values[$i] = $values[$firstNonNull];
        // Back-fill trailing nulls (rare — caller's window ends at eStart-step, so the
        // last slot is almost always present if any recent data exists)
        for ($i = $lastNonNull + 1; $i < $total; $i++) $values[$i] = $values[$lastNonNull];

        // Linear interpolation over interior gaps
        $i = $firstNonNull + 1;
        while ($i <= $lastNonNull) {
            if ($values[$i] === null) {
                // Find next non-null
                $j = $i + 1;
                while ($j <= $lastNonNull && $values[$j] === null) $j++;
                // values[i-1] and values[j] are both known
                $a = $values[$i - 1];
                $b = $values[$j];
                $span = $j - ($i - 1);
                for ($k = $i; $k < $j; $k++) {
                    $values[$k] = $a + ($b - $a) * (($k - ($i - 1)) / $span);
                }
                $i = $j + 1;
            } else {
                $i++;
            }
        }

        // Emit
        for ($k = 0; $k < $total; $k++) {
            $hist[] = [
                'datetime' => date('Y-m-d H:i:s', $tsList[$k]),
                'value'    => (float)$values[$k],
            ];
        }
        return $hist;
    }

    private function v4CallService($payload)
    {
        $ch = curl_init($this->v4_service_url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->v4_timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $code !== 200) {
            log_message('error', "V4 service HTTP $code — curl: '$err' — body: " . substr((string)$body, 0, 500));
            return null;
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            log_message('error', "V4 service returned non-JSON: " . substr((string)$body, 0, 500));
            return null;
        }
        return $decoded;
    }

    private function v4InsertForecasts($customerID, $results, $eStart, $eEnd)
    {
        if (empty($results)) return 0;
        $supplierID = $this->supplierID;
        $values = [];

        $endExclusive = (new \DateTime($eEnd))->modify('+1 day')->format('Y-m-d H:i:s');

        foreach ($results as $r) {
            if (!isset($r['pod_id'], $r['forecast'])) continue;
            $pod = addslashes($r['pod_id']);
            foreach ($r['forecast'] as $f) {
                if (!isset($f['datetime'], $f['p50'])) continue;
                $dt = str_replace('T', ' ', substr($f['datetime'], 0, 19));

                // Filter to the requested window only — the model returns extra steps
                // when (eStart - last_history) is itself > 1 step.
                if ($dt < $eStart || $dt >= $endExclusive) continue;

                // Clamp p50 to non-negative (electricity consumption can't go below zero
                // outside the prosumer/injection case, which v4 doesn't model yet)
                $ea = max(0.0, (float)$f['p50']);
                $values[] = "($supplierID, $customerID, '$pod', '$dt', 'timesfm', $ea)";
            }
        }

        if (empty($values)) return 0;
        $inserted = 0;
        foreach (array_chunk($values, $this->v4_insert_chunk) as $chunk) {
            $sql = "INSERT INTO forecast_pods_estimates
                        (supplier_id, customer_id, pod, forecast_datetime, estimation_type, forecast_ea)
                    VALUES " . implode(',', $chunk) . "
                    ON DUPLICATE KEY UPDATE forecast_ea = VALUES(forecast_ea)";
            $this->writeData($sql);
            $inserted += count($chunk);
        }
        return $inserted;
    }

    /**
     * Quick health probe — returns true if the FastAPI service answers /health with vertex_ai_reachable.
     * Called before kicking off the v4 phase; if false, the phase is skipped cleanly.
     */
    private function v4ServiceReady()
    {
        $ch = curl_init(str_replace('/forecast', '/health', $this->v4_service_url));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || $body === false) return false;
        $j = json_decode($body, true);
        return !empty($j['vertex_ai_reachable']);
    }
}
