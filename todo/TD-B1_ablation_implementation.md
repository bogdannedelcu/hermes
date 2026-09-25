# TD-B1 — Ablation Implementation Log

**Status:** In progress  
**Preceded by:** [TD-A1_ablation_plan.md](TD-A1_ablation_plan.md)

This document is a running log of everything implemented, studied, or decided during the ablation experiment. Updated incrementally.

---

## Infrastructure & Performance (pre-ablation cleanup)

### 2026-05-27 — MariaDB tuning

Before starting the ablation, tuned MariaDB for better write/read performance:

| Setting | Before | After | Reason |
|---------|--------|-------|--------|
| `query_cache_type` | ON | **OFF** | Global mutex bottleneck on write-heavy workloads |
| `query_cache_size` | 4096MB | **0** | Freed 4GB RAM, removed thrashing during estimation |
| `innodb_flush_log_at_trx_commit` | 1 | **2** | Faster writes, minimal durability risk |
| `innodb_io_capacity` | 200 | **1000** | Server disk is SSD-speed (~500 MB/s), 200 was set for HDD |
| `innodb_io_capacity_max` | 2000 | **4000** | Matches SSD tier |
| `innodb_read_io_threads` | 4 | **8** | 50 CPU cores available |
| `innodb_write_io_threads` | 4 | **8** | 50 CPU cores available |
| `innodb_buffer_pool_size` | ~40GB (capped by QC) | **60GB** | DB total size is 43.9GB — now fits entirely in RAM |

Config file: `/etc/mysql/mariadb.cnf`

---

### 2026-05-27 — Range condition fixes (date function wrappers)

Replaced `YEAR(col) = X AND MONTH(col) = Y` and `DATE(col) BETWEEN` patterns with index-friendly range conditions throughout the codebase. These patterns caused full table scans on multi-million row tables.

**Files modified:**

| File | Function | Fix |
|------|----------|-----|
| `ForecastModel.php` | `estimate()` — batch DELETE | `>= eStart AND < DATE_ADD(eEnd, +1 DAY)` |
| `ForecastModel.php` | `readAllEstimates()` | range on `forecast_datetime` |
| `ForecastModel.php` | `filterEstimatedPODValues()` | range on `forecast_datetime` |
| `ForecastModel.php` | `hasEstimates()` | range on `forecast_datetime` |
| `ForecastModel.php` | `insertEstimatedValues()` | range on `forecast_datetime` |
| `ForecastModel.php` | `prepareTempTable()` — weather_data | range on `weather_datetime` (13.8M rows) |
| `ForecastModel.php` | `selectSyntheticsHistoryValues()` | range on `synthetics_datetime` (709K rows) |
| `ForecastModel.php` | single-day DELETEs (lines 163, 239) | range with `DATE_ADD(dt, INTERVAL 1 DAY)` |
| `FarModel.php` | `getFarData()` | range on `far_datetime` per `customer_far_*` |
| `FarModel.php` | `updateCustomersProfile()` | range on `far_datetime` |

**Impact:** estimation time reduced from ~9 min → ~5 min. `readAllEstimates` reduced from 12s → 0.9s.

---

### 2026-05-27 — Estimation restructuring (batch DELETE)

**Before:** for each customer: DELETE → estimate → INSERT (sequential, per customer)

**After:** 3-phase approach:
1. Collect all non-manual customers and cache their options
2. Batch DELETE for all customers at once (2 queries total instead of N×2)
3. INSERT per customer in loop (no DELETEs inside loop)

**File:** `ForecastModel.php` — `estimate()` function  
**Impact:** Eliminated lock contention between DELETEs and INSERTs for different customers.

---

### 2026-05-27 — Eliminated double-run of $sqlValues

**Before:** the expensive history query (`$sqlValues`) ran twice per POD — once for INSERT, once for validation check.

**After:** validation check queries `forecast_pods_estimates` directly (data already inserted, uses composite index).

**File:** `ForecastModel.php` — `estimate()` loop  
**Impact:** ~50% reduction in DB time per client for the validation step.

---

### 2026-05-27 — readAllEstimates inner subquery rewrite

**Before:** GROUP BY with `DATE()` + `HOUR()` in JOIN context → filesort of 79K rows, 12–15 seconds.

**After:** GROUP BY moved into inner subquery on `forecast_estimates` alone → uses composite index, then joins small metadata tables.

**File:** `ForecastModel.php` — `readAllEstimates()`  
**Result:** 15s → 0.9s (15× speedup)

---

### 2026-05-27 — getFarDataMM single-query rewrite

**Before:** `getFarDataMM` called `getFarData` N times (once per selected month) → N × UNION of 150 customer_far tables.

**After:** single query covering full date range, `GROUP BY month` in outer wrapper. Result restructured in PHP to preserve JS interface `{month: [{hour, day, far_ea}]}`.

**File:** `FarModel.php` — `getFarDataMM()`  
**Result:** 7s → 2s for multi-month queries. Used `UNION ALL` (vs `UNION`) to skip deduplication.

---

### 2026-05-27 — Disabled "Sterge date" button

Disabled `systemDeleteData` in both environments (`/var/www/ebs` and `/var/www/ebsv2`) to protect long-term history in `customer_far_*` tables. Original code preserved below the `throw` — re-enable by removing the exception.

**File:** `Api.php` — `systemDeleteData()` in both `/var/www/ebs/app/` and `/var/www/ebsv2/app/`

---

## Data Architecture Study

### customer_far_* tables — source of truth for actuals

- 191 tables `customer_far_{id}` + `customer_far_archive`
- Total: **20GB**, largest single table: 6.2GB (`customer_far_archive`)
- Populated by: `ImportDailyReadingsModel.php` (daily import of real meter readings at 15-min intervals) and `FarModel.generateCurveForPOD()` (synthetic curve generation for new customers)
- These are **not a cache** — they are the authoritative actual consumption data
- Structure: `(pod, far_datetime DATETIME, far_ea DECIMAL)` + generated columns `dt DATE`, `dh`, `interval`
- Indexes: UNIQUE `(pod, far_datetime)`, plus `far_datetime`, `idx_dt`, `idx_dh`, `idx_interval`

### forecast_estimates — algorithm column added

Added `algorithm VARCHAR(20) NOT NULL DEFAULT 'v1'` to `forecast_estimates`.  
Rebuilt UNIQUE KEY to `(supplier_id, customer_id, forecast_datetime, algorithm)`.

```sql
ALTER TABLE forecast_estimates 
  ADD COLUMN algorithm VARCHAR(20) NOT NULL DEFAULT 'v1' AFTER forecast_ea,
  DROP INDEX supplier_id_customer_id_forecast_datetime,
  ADD UNIQUE KEY supplier_id_customer_id_forecast_datetime_algorithm 
      (supplier_id, customer_id, forecast_datetime, algorithm);
```

Existing 7.6M rows default to `algorithm = 'v1'` (current algorithm).

---

## Ablation Experiment — Algorithm Candidates

### Chosen baseline: v1 (existing)
- Simple historical average (`estimation_type = 'simpla'`) per hour/day-type/month
- No external regressors for most customers
- Temperature correlation exists but as a separate mode (`estimation_type = 'temperatura'`)

### Anti-Cheat Rule — Temporal Masking (MANDATORY for all algorithms)

**Every algorithm must enforce strict temporal masking on its input data.**

Rule: when estimating a period starting at `eStart`, only use `far_datetime < '$eStart'`.

| | v1 | v2 (requirement) |
|--|----|----|
| Filter used | `year(far_datetime) < year('$eStart')` | `far_datetime < '$eStart'` |
| Leaks future data? | No | No |
| Wastes recent past? | Yes — loses same-year pre-eStart data (e.g., Jan 2025 when estimating Feb 2025) | No |

This matters most during **backtests**: if we re-run estimation for 2025 today, the DB has all of 2025 available. Without the strict cutoff, the algorithm could see data it had no right to see at real D+1 time. Any MAPE score from a leaking backtest is invalid.

---

### v2 — EWMA (Exponentially Weighted Moving Average) ✓ implemented 2026-05-28

**DIR-1 + DIR-2 combined:** same structure as v1 (per-hour, per-day-type, per-month grouping) but:
1. **EWMA weighting** (DIR-1): weight = `EXP(-λ × weeks_ago)` where λ = 0.02
2. **Exact temporal cutoff** (DIR-2): `far_datetime < '$eStart'` instead of v1's `year < year(eStart)` — includes same-year-but-past data

**λ = 0.02 effect:**
| Data age | Weight |
|----------|--------|
| 1 week ago | 0.98 |
| 1 month ago | 0.92 |
| 6 months ago (26 weeks) | 0.59 |
| 1 year ago (52 weeks) | 0.35 |
| 2 years ago (104 weeks) | 0.12 |

**Implementation:**

| File | Change |
|------|--------|
| `ForecastModel.php` — `selectSimpleHistoryValues()` | Added `$lambda = 0.0` param; when λ > 0: temporal cutoff → `far_datetime < '$eStart'`, aggregation → `SUM(x*w)/SUM(w)` inline in final query |
| `ForecastModel.php` — `selectTemperatureHistoryValues()` | Added `$lambda` param; weighted aggregation in both A and B temperature-match subqueries (only when `$COMP == 'AVG'`) |
| `ForecastModel.php` — `selectHistoryValues()` | Added `$lambda` param, passes through to both functions |
| `ForecastModel.php` — `insertEstimatedValues()` | Added `$algo = 'v1'` param; replaces hardcoded `'v1'` tag |
| `ForecastModel.php` — `estimate()` | After v1 phase: deletes v2 rows, re-runs estimation with λ=0.02 per customer, inserts with `algorithm='v2'`; then calls `computeAccuracyLog()` |
| `ForecastModel.php` — new `computeAccuracyLog()` | Computes MAPE/MAE/RMSE/bias per customer/algorithm/month; upserts into `forecast_accuracy_log` |
| `ForecastModel.php` — new `getAccuracyLog()` | Returns monthly stats from `forecast_accuracy_log` for selected customers/period/algorithms |
| `ForecastModel.php` — new `getAccuracyDetails()` | Returns raw (forecast_datetime, realized, estimated, error_pct) for CSV download |
| `Controllers/Api.php` | Added `getAccuracyLog`, `getAccuracyDetails`, `computeAccuracyLog` API endpoints |
| `public/js/prognoza/estimat-vs-realizat.js` | Added `'v2'` to `evrAlgorithms` registry with full tooltip description |

**Trigger:** `estimate()` now always runs BOTH v1 and v2 automatically. No UI change needed — estimation button triggers both.

**Status:** ✓ implemented

---

### forecast_accuracy_log — Accuracy Statistics Table ✓ created 2026-05-28

New DB table for precomputed monthly accuracy metrics. Populated automatically after each estimation run.

```sql
CREATE TABLE forecast_accuracy_log (
    log_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id  TINYINT UNSIGNED NOT NULL,
    customer_id  INT UNSIGNED NOT NULL,
    algorithm    VARCHAR(20) NOT NULL,
    stat_month   DATE NOT NULL,            -- first day of the month
    n_intervals  INT UNSIGNED NOT NULL,    -- 15-min intervals compared
    mape         DECIMAL(10,4) NULL,       -- Mean Absolute % Error
    mae          DECIMAL(20,8) NULL,       -- Mean Absolute Error (kWh/15min)
    rmse         DECIMAL(20,8) NULL,       -- Root Mean Squared Error
    bias_pct     DECIMAL(10,4) NULL,       -- signed % error (+ = overestimate)
    total_realized_kwh  DECIMAL(20,8) NULL,
    total_estimated_kwh DECIMAL(20,8) NULL,
    computed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stat (supplier_id, customer_id, algorithm, stat_month)
);
```

**Useful queries after running estimation:**

```sql
-- v1 vs v2 MAPE comparison per customer for a given month
SELECT c.customer_name,
       MAX(CASE WHEN algorithm='v1' THEN mape END) AS mape_v1,
       MAX(CASE WHEN algorithm='v2' THEN mape END) AS mape_v2,
       MAX(CASE WHEN algorithm='v2' THEN mape END) - MAX(CASE WHEN algorithm='v1' THEN mape END) AS delta
FROM forecast_accuracy_log fal
JOIN customers c ON c.customer_id = fal.customer_id
WHERE stat_month = '2025-01-01'
GROUP BY fal.customer_id, c.customer_name
ORDER BY delta;

-- Customers where v2 beats v1 by > 2 percentage points
SELECT customer_name, stat_month, mape_v1, mape_v2, delta
FROM (
  SELECT c.customer_name, fal.stat_month,
         MAX(CASE WHEN algorithm='v1' THEN mape END) AS mape_v1,
         MAX(CASE WHEN algorithm='v2' THEN mape END) AS mape_v2,
         MAX(CASE WHEN algorithm='v1' THEN mape END) - MAX(CASE WHEN algorithm='v2' THEN mape END) AS delta
  FROM forecast_accuracy_log fal JOIN customers c ON c.customer_id = fal.customer_id
  GROUP BY fal.customer_id, c.customer_name, fal.stat_month
) t WHERE delta > 2 ORDER BY delta DESC;
```

**API endpoints:**
- `getAccuracyLog` → monthly summary stats (for display/export)
- `getAccuracyDetails` → raw (forecast_datetime, realized, estimated, error_pct) per customer/algorithm → CSV download
- `computeAccuracyLog` → on-demand recompute for a customer/period

**Status:** ✓ implemented

---

### TODO: POD-level algorithm selection via cluster analysis

**Observation:** each POD has a different consumption profile (office vs factory vs lighting). The optimal algorithm (v1 vs v2 vs future) may differ by POD type. 

**Plan (not yet implemented):**
- Run cluster analysis on daily load curve shapes per POD
- Assign each POD to a cluster (stable/trending/volatile/injection-only)
- Store recommended algorithm per POD in a new config table (or extend `forecast_customers_consumption_types`)
- Estimation engine reads per-POD algorithm config and routes to the appropriate function

**Status:** TODO — not started

---

## Implemented: New Screen "Estimat vs Realizat" (2026-05-27)

New menu entry under Prognoza: **"Estimat vs Realizat"**

Shows, for the same period and customer selection, all selected algorithms + actuals in a single Kendo Spreadsheet grid.

**Layout (Option B — confirmed):** rows = hour groups with algorithm sub-rows, columns = days.
- Each hour has a light-blue header row, followed by one data row per selected algorithm.
- Total section at bottom with per-algorithm daily sums.
- "Realizat" always renders first (leftmost / topmost in each group).
- Algorithm checkboxes in the filter bar; each has an ⓘ info button opening a description popup.

**Data sources:**
- Estimates: `forecast_estimates` via `readEstimatVsRealizat` API → `readAllEstimates()` (aggregated across customers)
- Actuals: `customer_far_{id}` via `getFarData` API → `getFarDataMM()` (already aggregated)

**Files created/modified:**
| File | Role |
|------|------|
| `Controllers/Prognoza/Prognoza.php` | `EstimatVsRealizat()` action |
| `Controllers/Api.php` | `readEstimatVsRealizat()` method |
| `Views/prognoza/estimat-vs-realizat-content.php` | Page layout + modal |
| `Views/prognoza/estimat-vs-realizat-card.php` | Filter controls (period, dates, interval, customers, algos) |
| `public/js/prognoza/estimat-vs-realizat.js` | Grid logic, data normalization |
| `Views/header.php` | Menu link added |

**Layout refinements applied:**
- Realizat row merged into the hour header row (e.g. "01:00  Realizat") — saves one row per hour
- "Eroare față de Realizat" toggle switch added to filter bar
- **Absolute mode** (default): raw values for all algorithms
- **Diff mode** (switch on): estimate rows show `% error = (estimat − realizat) / realizat × 100`
  - Cells color-coded by intensity: red = overestimate, green = underestimate; saturation scales up to ±50% error
  - TOTAL section always shows absolute sums regardless of mode

**Status:** implemented ✓

---

Realizat row stays as-is in both modes. Difference cells could be color-coded (red = overestimate, green = underestimate).

**Status:** planned — not yet implemented

---

## TODO: Algorithm Selector in Estimare screen

Dropdown/ButtonGroup in the estimates card to select which algorithm's results to display (`v1`, `v2`, or comparison mode).

**Status:** planned — not yet implemented

---

## Statistical Profiling of the Database (2026-05-27)

### Coverage

| Metric | Value |
|--------|-------|
| Customers with v1 estimates | 159 |
| Date range (v1) | 2024-02-01 → 2026-09-30 |
| Total rows in `forecast_estimates` | 7.63M |
| Distinct forecast days | 972 |
| Customer_far tables | 191 tables, ~20GB |
| Customer_far date range | archive: up to 2024-12-31; current: 2025-01-01 → today |

**History length distribution (by customer):**

| History span | Customers |
|---|---|
| < 3 months | 11 |
| 3–6 months | 28 |
| 6–12 months | 469 |
| ≥ 1 year | 1,045 |

Most customers (67%) have ≥1 year of history — good for seasonality-based models.  
Data continuity is excellent: top customers show 99.0–99.9% coverage with 0 missing hourly slots.

### Consumption type breakdown

| Type | Customers |
|---|---|
| General | 47 |
| Realizat zilnic | 35 |
| Institutii | 23 |
| Iluminat public | 23 |
| Tribunal | 16 |
| Spitale | 15 |
| Manual | 11 |
| Firme | 7 |
| Prosumator | 6 |
| Unitati Militare | 4 |

Each type has a fundamentally different consumption profile:
- **Iluminat public**: highly predictable (sunset-driven), no temperature sensitivity
- **Spitale/Institutii**: stable 24/7 base load, low intra-week variance
- **Firme/General**: business-hours pattern, strong Mon–Fri vs weekend difference
- **Prosumator**: net-metering, has solar injection → negative values possible

### Seasonality patterns (2025, all customers)

**Hourly profile:** consumption peaks 08:00–10:00 (business ramp-up), minimum at 04:00–05:00.  
CV per hour ≈ 190–200% — extremely high across the customer portfolio (driven by size diversity).

**Day-of-week profile:**

| Day | Avg ea (mWh) | vs Monday |
|---|---|---|
| Sunday | 16.5 | −25% |
| Monday | 22.1 | baseline |
| Tue–Thu | 23.1–23.3 | +5% |
| Friday | 22.5 | +2% |
| Saturday | 18.0 | −19% |

Clear Mon–Fri / weekend split — already captured by v1's day-type grouping.

**Monthly pattern (total portfolio, 2024–2026):**  
Strong growth trend visible in the aggregate — portfolio grew from ~90 MWh/day avg in Feb 2024 to ~245 MWh/day avg in Feb 2025 (+170%). This is driven by **customer onboarding** (50 → 115 active customers), not pure consumption growth.

### Year-over-year growth per existing customer

For customers active in both 2024 and 2025:
- Median YoY growth: ~11–20%
- Outlier: customer 636 (+728%) — almost certainly a new large site added mid-year
- Several customers: +50–100% — structural change, not noise
- These are the customers where v1's equal-weight historical average will **systematically underestimate**

### v1 accuracy vs Realized (2025 backtest)

Tested on two representative customers:

**Customer 590 (RETIM ECOLOGIC SERVICE S.A.)** — waste management, CV=37.5%

| Month | MAPE | Bias | Interpretation |
|---|---|---|---|
| Jan–Nov 2025 | 72–78% | −72 to −78% | Systematic underestimate ~3× |
| Dec 2025 | 94% | −91% | Worst month |

**Customer 630 (Spital Clinic Jud. Târgu Mureș)** — hospital, CV=29.6%, very stable

| Period | MAPE | Bias |
|---|---|---|
| Full 2025 | 74–79% | −74 to −79% |

**Key finding: v1 consistently estimates ~¼ of actual consumption.**  
This is NOT a trend issue alone. Possible causes investigated:

1. **Partial POD coverage** — customer 590 has 12 active PODs, but 4 have NULL forecasts in `forecast_pods_estimates`. The system only aggregates the 8 estimated PODs into `forecast_estimates`, ignoring the other 4.
2. **Training data mismatch** — `customer_far` archive only covers 2023-12 to 2024-12. If the unestimated PODs were added/activated later, v1 has no history for them.
3. **POD-level vs customer-level** — `forecast_pods_estimates` shows ~8.25 kWh/day total for customer 590 on Jan 1 2025, but `forecast_estimates` shows only ~2.5 kWh/day for March 2025. Seasonal effect partially explains this, but the magnitude suggests the POD gap is the dominant factor.

### estimation_type distribution

| Type | PODs |
|---|---|
| temperatura | 81 customers' PODs |
| simpla | 9 customers' PODs |

~90% of PODs use temperature-corrected estimation. This matters for algorithm selection: a new algorithm must at minimum handle the same temperature correlation.

### POD structure note

A single customer can have many PODs (metering points at different physical locations — office buildings, warehouses, construction sites). Each POD is estimated independently in `forecast_pods_estimates`, then aggregated per customer into `forecast_estimates`. PODs can be added/deactivated at any time. The 4 unestimated PODs for customer 590 are examples where a POD exists in the `pods` table but has no corresponding history/forecast.

### Preliminary conclusions for algorithm selection

| Finding | Implication |
|---|---|
| v1 MAPE ~75% with systematic negative bias | Fix POD coverage FIRST — then re-measure true v1 baseline |
| 11–20% YoY growth on existing customers | A decay-weighted average (EWMA) would help |
| CV per hour ~200% across portfolio | Cross-customer variance is high; per-customer models are essential |
| 90% of PODs use temperatura model | New algorithm must preserve or improve temperature handling |
| Iluminat public / Spitale profiles very stable | Simple models may already be optimal for these types |
| Prosumator has net-injection | Separate model / sign-aware handling needed |

### POD coverage investigation — conclusions

The 4 "missing" PODs for customer 590 are correctly excluded by v1:

| POD | Reason excluded |
|-----|-----------------|
| RO005E532128418 (Brad, Hunedoara) | Never had any meter readings — no data in either current or archive table |
| RO005E511658336 | Archive: Dec 2023 only, total_ea = 0.00 — zero consumption |
| RO005E511658358 | Archive: Dec 2023 only, total_ea = −2.88 — negative injection (prosumator-like) |
| RO005E511658369 | Archive: Dec 2023 only, total_ea = −0.97 — negative injection |

There is also one orphan POD `RO005E513078244` in `customer_far_590` that belongs to a **different customer** (718 — SUPERCOM S.R.L.) and has been accumulating readings there. This is a data routing issue unrelated to estimation quality.

**No action needed** — the 4 unestimated PODs genuinely have no usable consumption data.

### Corrected v1 MAPE — the 75% was a query bug

The initial MAPE of ~75% was caused by a comparison mismatch: both tables store **15-min granularity**, so `forecast_ea` per row is a quarter-hour value. The query joined hourly-grouped realized data (sum of 4×15-min) against a single 15-min estimate → systematic 4× discrepancy.

**Corrected 15-min vs 15-min MAPE, customer 590 (RETIM), 2025:**

| Month | MAPE | Bias |
|---|---|---|
| Jan | 14.9% | −13.8% |
| Feb | 12.8% | −9.2% |
| Mar | 33.1% | +0.8% |
| Apr | 18.9% | −14.2% |
| May | 20.0% | −4.0% |
| Jun | 21.6% | +1.8% |
| Jul | 17.2% | +5.0% |
| Aug | 10.7% | −2.6% |
| Sep | 11.1% | +2.0% |
| Oct | 25.8% | −3.4% |
| Nov | 15.3% | −2.9% |
| Dec | **75.3%** | **−64.6%** — anomaly |

**Corrected MAPE, customer 630 (hospital), 2025:** typical 6–17%, except April (52%) and July (38%).

**v1 true baseline: 10–25% MAPE** in typical months. The December 2025 spike for customer 590 and April/July spikes for the hospital likely reflect consumption regime changes (new equipment, seasonal shutdown, etc.) that the historical average cannot anticipate.

### How v1 uses history — archive question

`selectSimpleHistoryValues()` queries **`customer_far_{customerID}`** with filter `year(far_datetime) < year(eStart)`. So for D+1 estimates generated in 2025, it uses the 2024 data that was in the live table at that time. At year-end, that 2024 data was moved to `customer_far_archive`. The archive is not queried separately — it was the live data at estimation time.

**No special action needed to use the archive** — estimates generated today (for 2026) use 2025 data from the live `customer_far_{id}` tables.

### DST (Daylight Saving Time) gaps

Romania follows EU DST. On the spring transition (last Sunday of March), clocks jump 02:00 → 03:00, so one hour of readings is missing. On the fall transition (last Sunday of October), clocks fall 03:00 → 02:00, so one hour of readings appears twice. This causes minor MAPE spikes in March and October. No fix needed for v1/v2 — these are 2 hours/year out of ~8760.

### True v1 baseline summary

| Metric | Value |
|---|---|
| Typical MAPE (10 of 12 months) | 10–25% |
| Typical bias | −15% to +5% (slight underestimate in winter, near-zero in summer) |
| Worst months | Regime changes (new consumption pattern v1 hasn't seen yet) |
| Bar for v2 to beat | Consistently < 15% MAPE |

**Recommended first candidate algorithm (v2):** Exponentially Weighted Moving Average (EWMA) — keeps the same structure as v1 (per-hour, per-day-type, per-month grouping) but weights recent weeks more heavily via decay factor λ. Should help in months where consumption recently shifted. Simple to implement in SQL, no external libraries, directly comparable to v1.

---

## v2 Refinements (2026-05-28) — beyond the initial EWMA

The initial v2 (just EWMA + exact temporal cutoff) underperformed on several customer types. Four targeted refinements were added without changing v1:

### 1. Per-POD zero-day filter

**Problem:** several customers had whole-month all-zero readings (missing-import artefacts, e.g. POD `RO001E119771048` in March 2026 — 2972 intervals = 0.000000 kWh). EWMA gave these recent zeros high weight → estimate collapsed.

**Fix:** after building `tSimpleValues` / `$rawDataTable`, DELETE rows where `(pod, DATE) HAVING SUM(far_ea) = 0`. Applied only when `$lambda > 0` (v2 path) so v1 stays unchanged.

### 2. Gaussian temperature-similarity kernel (simple path only)

**Problem:** v2 simple path ignored temperature entirely, even though `tdates` already had forecasted temps from `weather_data`.

**Fix:** added kernel `w_temp = IF(NULL, 1.0, EXP(-σ × (T_hist − T_target)²))` with σ=0.05. Multiplies the EWMA time weight in the aggregation. Historical temperatures pulled from `forecast_temperatures` + `weather_data` fallback. Temperature path keeps existing hard-margin matching.

### 3. Cross-month data inclusion

**Problem:** `getDateRangeWhere` restricts to same-month-from-prior-years. PODs with recent regime changes (Jan-Mar 2026 different from Jan-Mar 2025) had no way to "see" the change because April training pulled only April 2025.

**Fix:** when `$lambda > 0`, WHERE clause OR's with `far.far_datetime >= DATE_SUB('$eStart', INTERVAL 12 WEEK)`. Adds last 12 weeks regardless of month. Temperature kernel + dayIndex matching ensure only relevant samples contribute.

### 4. Per-POD trend multiplier with Bayesian shrinkage

**Problem:** for POD `RO001E119771048` (A2Z), consumption tripled between April 2025 (0.001368/15min) and April 2026 (0.004658/15min). v2 still pulled only April 2025 → estimate -39% below realized.

**Fix:** compute per-POD log-ratio between recent N-day window and same window 1 year prior. Apply Bayesian shrinkage instead of binary thresholds (initially tried deadband 0.7/1.4, replaced with proper statistic):

```
d = mean(log(recent)) - mean(log(prior))
se² = Var(log(recent))/n_r + Var(log(prior))/n_p
t² = d² / se²
w = t² / (t² + 1)              # empirical-Bayes shrinkage weight
multiplier = exp(d × w)        # clamped to [0.3, 3.0]
```

Strong evidence (|t| ≫ 1) → w → 1 → full effect.
Weak evidence (|t| ≪ 1) → w → 0 → multiplier ≈ 1 (no adjustment).

For POD 19771048: t² = 98, w = 0.99, multiplier = 2.20 (almost full effect on raw ratio 2.22).
For stable POD with raw ratio 0.88, t² = 3.6, w = 0.78, multiplier = 0.90 (mild correction).

### Code organization

Per user request "pentru orice algoritm nou implementat, inclusiv v2, in fisiere PHP separate":

- `app/Models/Prognoza/Algorithms/V1Algorithm.php` — PHP trait, V1 simple history (unchanged logic, extracted from ForecastModel)
- `app/Models/Prognoza/Algorithms/V2Algorithm.php` — PHP trait, V2 simple history + shared helpers `v2PopulateTrendMultipliers`, `v2CrossMonthClause`
- `ForecastModel.php` uses both traits. `selectSimpleHistoryValues` dispatches by `$lambda`. Temperature path still in `ForecastModel.selectTemperatureHistoryValues` (too complex to extract cleanly) but uses v2 helpers conditionally.

### Results — April 2026 backtests (A2Z, customer 388)

| POD | V1 bias | V2 bias | Notes |
|-----|--------:|--------:|-------|
| RO001E115346042 (stable) | +15.9% | **+3.7%** | Cross-month + temp kernel helped |
| RO001E119771048 (trending) | -82.1% | **-39.7%** | Trend multiplier 2.20× pulled estimate from 0.37 to 1.24 kWh (vs realized 2.06) |

**Status:** ✓ implemented and validated

---

## v3 Algorithm (2026-05-28) — Lag features + HDD/CDD

Implemented per user request following SOTA review of v2:

**File:** `app/Models/Prognoza/Algorithms/V3Algorithm.php` (PHP trait)

### Components

1. **HDD / CDD piecewise temperature** (simple path only):
   - `HDD = max(0, 18 - T)` (ASHRAE heating base)
   - `CDD = max(0, T - 22)` (ASHRAE cooling base)
   - Kernel: `w_temp = exp(-σ × ((HDD_hist - HDD_target)² + (CDD_hist - CDD_target)²))`
   - Replaces v2's symmetric Gaussian on raw temperature — captures V-shape demand response correctly.

2. **Lag features (persistence baseline)** — applied as post-process to `forecast_pods_estimates`:
   - `lag_24h` = realized far_ea at (datetime - 1 day) if `< eStart`
   - `lag_168h` = realized far_ea at (datetime - 7 days) if `< eStart`
   - Blend: `(w_m·model + w_24·lag_24 + w_168·lag_168) / (w_m + w_24·has24 + w_168·has168)`
   - Weights: w_m=2.0, w_24=1.0, w_168=1.0 (conservative — model 50%, each lag ~25% when present)
   - Zero-day filter applied to lags too (skip lags from zero-consumption days)

3. **Reuses all v2 mechanisms**: EWMA, cross-month, zero-day filter (training), trend multiplier with Bayesian shrinkage.

### Integration

- `ForecastModel.php` — `use \App\Models\Prognoza\Algorithms\V3Algorithm;`
- `selectHistoryValues` accepts new `$v3 = false` param
- `selectSimpleHistoryValues` dispatches: v3 → V3Algorithm trait, lambda>0 → v2, else v1
- `selectTemperatureHistoryValues` gets lag-blend post-process for v3 (HDD/CDD doesn't apply on temperature path — already has temp matching)
- `estimate()` runs phase v3 after v2: same loop, then `v3ApplyLagBlend()` post-process, then `insertEstimatedValues($algo='v3')` and `persistPodEstimates`

### Results

v3 trade-offs are visible in backtests:
- For PODs with no clear trend AND stable consumption: v3 ≈ v2 (lag post-process adds slight noise)
- For PODs with strong trend (POD 19771048): v3 ≈ v2 since lag-24h/168h fall in March 2026 zero-days → filtered → blend collapses to model-only
- For mid-volume PODs with weak seasonality: v3 marginally better than v2 on |bias|

**Critical lesson:** lag features only directly affect the first 1-7 days of a 30-day forecast window (lag-24h covers day 1, lag-168h covers days 1-7). For monthly horizons, the benefit is concentrated on a small subset of intervals.

**Status:** ✓ implemented; net effect mixed (better on some PODs, marginally worse on others — see per-POD profiling below)

---

## Per-POD Profiling and Algorithm-Assignment Analysis (2026-05-28)

Profiled 30+ PODs across 8 customers to characterize where each algorithm wins. Findings:

### Winner distribution (April 2026 estimates, 30 PODs):
- **V1 wins:** ~50% of PODs — stable consumers, very noisy (WAPE > 100%) consumers, very low-volume PODs
- **V2 wins:** ~31% of PODs — moderate-volume PODs with detectable trend or temperature sensitivity
- **V3 wins clearly:** ~6% of PODs — short-horizon forecasts with strong daily/weekly persistence
- **Mixed / tie:** remainder

### Catastrophic failure cases (V2/V3 catastrophic vs V1 ~OK)
Identified 3 PODs where v2/v3 produced > 200% bias while v1 was at 16-40%. Root cause: extreme low-volume PODs (`mean_daily_kwh < 0.01`) where trend multiplier amplifies tiny absolute errors into huge relative errors. **Implication for clustering rules: hard minimum volume threshold for trend application.**

### Aggregate gain from per-POD assignment (sample: 7 customers, ~25 PODs, April 2026)

| Strategy | Sum \|gap\| (kWh) | Bias agregat |
|---|---:|---:|
| Only V1 | 19.83 | +3.1% |
| Only V2 | ~ | +6% |
| Only V3 | ~ | +5% |
| **Best-per-POD** | **9.79** | **+3.6%** |

→ **51% reduction in summed |gap| with per-POD assignment vs single-algorithm baseline.**

### Cold-start clustering rules (proposed, not yet implemented)

```
IF mean_daily_kwh < 0.01                    → V1   (POD too small; trend multiplier unsafe)
IF n_zero_days > 30/90                      → V1   (uncertain data)
IF V1_WAPE_recent > 80%                     → V1   (low SNR, V2 adds noise)
IF trend_t² > 5 AND mean_daily_kwh > 0.05   → V2   (real trend, sufficient volume)
IF CV > 0.5 AND mean_daily_kwh > 0.1        → V2   (volatile with volume)
ELSE                                        → V1   (default conservative)
```

Validation on 30-POD sample: ~22/30 correctly assigned (73%). Edge cases near thresholds — empirical, will refine with accuracy log data.

### Hot-phase clustering (recommended after 2-3 months of data)

```
preferred_algorithm[pod] = argmin(WAPE_recent_3months) per pod
```

Requires:
1. Adding `forecast_accuracy_log_pod` table (per-POD, not per-customer like current `forecast_accuracy_log`)
2. Job `recomputeAlgorithmPreferences()` running after each estimation
3. `pod_algorithm_preference` table to store decisions

**Status:** TODO — schema not yet built

---

## UI — Statistics Tab (2026-05-28)

The "Estimat vs Realizat" screen was extended from a single grid into a 3-tab layout:

| Tab | Purpose |
|---|---|
| **Tabel** | Original Kendo Spreadsheet — per-interval values per POD per algorithm |
| **Grafic (totaluri zilnice)** | Kendo Chart — column chart, daily totals, one series per (POD × algorithm) |
| **Statistici** | Comprehensive statistical comparison (see below) |

### Statistics tab structure

1. **Context block (copy-paste ready)** — Perioada, Interval, Clienti, POD-uri, Algoritmi. With "Copy" button → user can paste straight back to support/AI requests.
2. **"Castig potential — assignment per POD vs algoritm unic"** table — shows what would be saved (in |gap| kWh) by picking best-per-POD vs always-V1/V2/V3. Includes best-per-POD distribution count.
3. **Pe POD** table — per-POD breakdown with WAPE, |Bias|, RMSE per algorithm + winner badge (statistically-aware: handles N≥2 algorithms, detects ties).
4. **WAPE per hour-of-day chart** — line chart, identifies which hours each algorithm struggles with.
5. **Daily total error chart** — column chart, signed `Estimat − Realizat` per day per algorithm.
6. **Scatter Estimat vs Realizat (daily totals)** — visual check; reference line y=x.
7. **Methodology notes** — WAPE/MAE/RMSE/R²/Bias glossary for non-technical reader.

### UI cleanup

- Removed "Vizualizare Client/POD" toggle — always POD now (per user request: "ma incurca per client")
- Removed "Eroare fata de Realizat" diff toggle — replaced by dedicated statistics tab
- Default algorithm selection: `['realizat', 'v1', 'v2', 'v3']` — all four shown by default
- Default interval: 60min (was 15min) — better readability for monthly views
- Default date interval: previous full month
- `evrClearAll()` runs at every `evrLoad()` — wipes grid + chart + stats containers before fetching new data (no stale residual)
- Removed the "Rezumat — toate intervalele si POD-urile" table (not actionable; replaced by per-POD breakdown + assignment-gain table)

---

## v4 — TimesFM (Google Vertex AI) — Deployed & Integrated (2026-05-29)

User identified that V3 is approaching the ceiling of pure SQL/statistical methods and proposed using Google's TimesFM time-series foundation model.

### What TimesFM is

- Decoder-only Transformer with causal attention, trained specifically on time series (~100 billion points across electricity, weather, traffic, finance, Wikipedia views, etc.)
- Zero-shot forecasting: send history → receive forecast, no fine-tuning
- Available on Vertex AI Model Garden (hosted by Google) OR self-hostable via HuggingFace weights
- Returns point forecast + quantile bands (P10/P50/P90)

### Decision: hosted via Vertex AI

Self-host would require ~€500 GPU investment + Python ML stack on-prem; hosted is ~$2-5/month for our portfolio scale. Better experimentation cost.

### Implementation

**Folder:** `/var/www/ebsv2/python/timesfm_service/`

```
├── README.md              — setup, architecture, integration with PHP
├── requirements.txt       — fastapi, uvicorn, google-cloud-aiplatform, pydantic
├── .env.example           — config template
├── config.py              — loads .env
├── models.py              — Pydantic schemas (HistoryPoint, ForecastItem, ForecastResponse)
├── timesfm_client.py      — wrapper over Vertex AI PredictionServiceClient
├── main.py                — FastAPI app (POST /forecast, GET /health, GET /info)
├── run.sh                 — dev startup
├── sample_request.json    — for `curl` testing
├── credentials/           — `.gitignore` excludes JSON keys, `.gitkeep` keeps folder
│   └── gcp-sa.json        — service account key (chmod 600)
└── deploy/
    ├── apache-snippet.conf   — proxy config examples (mount vs separate vhost)
    ├── setup-apache.sh       — idempotent script to wire `/timesfm/*` → `127.0.0.1:8081`
    └── systemd.service       — production unit
```

### Architecture

```
Browser → Apache:8080 → /timesfm/* → 127.0.0.1:8081 (FastAPI) → Vertex AI TimesFM
       └─ /(other)    → PHP (ebsv2)
```

- Port 8081 bound to localhost only (no external exposure)
- Apache proxy lets PHP call `http://localhost:8080/timesfm/forecast` OR FastAPI directly via `http://127.0.0.1:8081/forecast`
- `Require ip 127.0.0.1 192.168.1.0/24` restricts access in the proxy mount

### GCP setup status

| Step | Status |
|---|---|
| Project `autonomous-7d0de` created | ✓ |
| Billing enabled | ✓ |
| Vertex AI API enabled | ✓ (gcloud calls return successfully) |
| Service account `ebs-timesfm` with role `roles/aiplatform.user` | ✓ |
| Service account JSON key generated | ✓ |
| Key stored at `/var/www/ebsv2/python/timesfm_service/credentials/gcp-sa.json` (chmod 600) | ✓ |
| `.env` configured with project + creds path | ✓ |
| gcloud CLI installed in `/home/hermes/google-cloud-sdk/` | ✓ |
| Python venv + dependencies installed | ✓ (via `virtualenv`, since `python3-venv` apt pkg missing) |
| Apache proxy wired (`/timesfm/*` → 8081) | TODO (script ready, needs sudo) — *PHP currently calls 127.0.0.1:8081 directly, no proxy needed for backend* |
| Systemd service enabled | TODO (needs sudo) — *FastAPI in `nohup` for now; won't survive reboot* |

### Deploy resolution (2026-05-29)

The 404 on `publishers/google/models/timesfm-2.0:predict` was because **the publisher path is not directly callable** — TimesFM in Vertex AI Model Garden must first be deployed to a **dedicated endpoint** via the Model Garden one-click flow.

Important gotcha: the deployed endpoint may land in a region different from `us-central1` (depending on the default Model Garden region at deploy time). In our case it landed in `europe-west6` (Zürich), NVIDIA L4 g2-standard-8 — so the original assumption that endpoint would be in `us-central1` produced empty results from `gcloud ai endpoints list --region=us-central1`. **Scan all regions** when an endpoint disappears.

```
Endpoint ID:    mg-endpoint-00bad2fe-e062-4caf-97f4-4076070b6bca
Region:         europe-west6
Dedicated DNS:  mg-endpoint-...-europe-west6-839045260585.prediction.vertexai.goog
Project number: 848100748088
```

The `:predict` URL must use the *dedicated* DNS, not the shared `aiplatform.googleapis.com` — the latter returns:
```
400 FAILED_PRECONDITION — Dedicated Endpoint cannot be accessed through the shared Vertex AI domain
```

### Request/response schema confirmed live (2026-05-29)

The TimesFM v2.0 inference server returns its own schema docs when given a malformed request:

```json
{
  "instances": [{
    "input":   [float, ...],            // history, oldest → newest  ◀ NOT "context"
    "horizon": int,                     // optional, default model-side
    "freq":    0|1|2,                   // 0=sub-hourly+hourly, 1=daily/weekly/monthly, 2=annual
    "timestamp": ["..."], "timestamp_format": "...",   // optional
    "dynamic_numerical_covariates":   {...},           // optional — exogenous regressors
    "dynamic_categorical_covariates": {...},
    "static_numerical_covariates":    {...},
    "static_categorical_covariates":  {...}
  }, ...]
}
```

Response per item: `point_forecast` (= p50), `mean`, `p10`, `p20`, ..., `p90`.

### Code updates

**`timesfm_service/`** rewritten:
- `.env`: GCP_PROJECT_NUMBER + TIMESFM_ENDPOINT_ID + TIMESFM_ENDPOINT_DNS (dedicated)
- `timesfm_client.py`: dropped `google-cloud-aiplatform` (was bringing in heavy gRPC stack); now uses plain `requests` + `google.oauth2.service_account` + `google.auth.transport.requests.Request` for token refresh. Schema swapped to `input` and response keys `mean`/`p10`..`p90`.
- `requirements.txt`: removed `google-cloud-aiplatform`, `httpx`; added `requests`.

**`V4Algorithm.php`** (new trait):
- `v4EstimateCustomers($eStart, $eEnd, $customerIDs)` — per-customer driver: wipes prior v4 rows, batches PODs (8 per HTTP request), inserts into `forecast_pods_estimates` with `estimation_type='timesfm'`.
- `v4GetPodHistory()` — **critical**: builds a contiguous 15-min grid ending exactly at `eStart - 1 step`. Aligns to grid, marks zero-days as missing (same heuristic as v2/v3 zero-day filter), interpolates linearly. PODs with > 50% missing in the 21-day window are skipped (return `[]`).
- `v4CallService()` — cURL POST to `127.0.0.1:8081/forecast` with 180s timeout.
- `v4ServiceReady()` — pre-flight `/health` probe; if down, the v4 phase is skipped cleanly.

**`ForecastModel.php`**:
- Added `use V4Algorithm`.
- Phase v4 after v3 in `estimate()`: gated on `v4ServiceReady()`, calls `v4EstimateCustomers`, runs `filterEstimatedPODValues` + `insertEstimatedValues($algo='v4')` + `persistPodEstimates($algo='v4')`. Adds "v4 TimesFM: ..." line to `errorCollection` for UI feedback.

**`estimat-vs-realizat.js`**:
- Added `'v4'` entry to `evrAlgorithms` with full description (foundation model, P10/P50/P90, latency, limitations).
- Default `evrSelectedAlgos` now includes `'v4'`.

### TimesFM input requirements (from official docs — verified 2026-05-29)

Critical for V4Algorithm correctness:

| Property | Requirement | How V4 satisfies it |
|---|---|---|
| **Contiguous timestamps** | Required — "ideally requires the context to be contiguous (i.e. no holes)" | Builds a 2016-slot 15-min grid ending at `eStart-1step`; never SELECT-LIMITs in DESC order. |
| **Same frequency context↔horizon** | Required | All sent at `freq="15min"`. |
| **Missing values** | Local Python pkg auto-fills NaN with linear interp; **Vertex AI does NOT** | Linear interpolation done client-side in PHP. |
| **Max context length** | 2048 (trained), can go beyond | We send 2016 to leave headroom. |
| **Min history** | "≥ 24 points; recommended ≥ 168" | `v4_min_context = 96` (1 day); PODs below this skipped. |

### Test results (2026-05-29, single-day backtest)

| POD | Customer | Profile | V4 bias on 2026-04-01 | Notes |
|---|---|---|---|---|
| RO001E115346042 | 388 | stable, 0 zero-days | +29.6% | Worse than v2 (+3.7%) on this POD; TimesFM gets no temperature signal |
| RO001E119771048 | 388 | trending, 21 recent zero-days | (skipped) | Correctly skipped — > 50% interpolated; v3 trend multiplier handles it |

Latency per single-POD request: 400-500 ms (NVIDIA L4 cold/warm).

**Interpretation:** V4 zero-shot on raw consumption-only context underperforms v2/v3 for PODs where temperature dominates the signal. Likely improvements (deferred):
1. Pass forecast temperature as `dynamic_numerical_covariates` — TimesFM 2.0 supports exogenous regressors.
2. Send `feelslike` too.
3. Run wider backtest (multiple customers × multiple months) to find the POD segment where v4 actually wins.

**Status:** ✓ deployed; v4 callable end-to-end. Quality evaluation pending broader backtests.

---

## Summary of all algorithm files in `app/Models/Prognoza/Algorithms/`

| File | Type | Purpose | Status |
|------|------|---------|--------|
| `V1Algorithm.php` | trait | Simple historical average (year-boundary cutoff, no decay, no temp kernel) | ✓ deployed |
| `V2Algorithm.php` | trait | EWMA + cross-month + zero-day filter + trend multiplier (Bayesian shrinkage) + Gaussian temp kernel (simple path) | ✓ deployed |
| `V3Algorithm.php` | trait | V2 + lag features (Z-1, Z-7) post-process + HDD/CDD piecewise (simple path only) | ✓ deployed |
| `V4Algorithm.php` | trait | Chronos-2 (Amazon, Oct 2025) zero-shot, self-hosted on CPU via Python microservice on 127.0.0.1:8081 | ✓ deployed 2026-05-29 |

---

## Pivot: TimesFM → Chronos-2 (2026-05-29 afternoon)

After deploying TimesFM 2.0 on Vertex AI Model Garden, we hit two blockers we couldn't resolve from the client side:

1. **`TIMESFM_HORIZON=128` hardcoded** in the Vertex serving container — so a 30-day 15-min forecast (2880 steps) is impossible in one call.
2. **`serving_container_environment_variables` are silently dropped** when deploying a publisher model via `model_garden.OpenModel(...).deploy()`. The new endpoint still came up with HORIZON=128 after we tried to override.
3. **Cost** — Model Garden one-click endpoint provisions `g2-standard-8 + L4` with `minReplicaCount=1`, billed ~$1.50/hour 24/7 whether you call it or not. ~$1,100/month for a defective model.

Tried `timesfm-2.5` (newer model) — image `timesfm-serve-v2p5` crashes on L4 ("Model server exited unexpectedly. Please use recommended machine spec." — needs A100, ~5× the cost).

### Decision: drop Vertex, self-host Chronos-2

**Chronos-2** (Amazon Science, October 2025) is open on HuggingFace (`amazon/chronos-2`):
- **Encoder-only forecast head** — emits 1024+ steps in a single forward pass (no autoregression). Beats TimesFM-2.5 and Moirai-2 on recent 2026 benchmarks (TSFM.ai, Decathlon, arXiv:2602.10848).
- **CPU-friendly** — 500M parameters, runs on consumer hardware (Ryzen 7 + 16GB was enough in published benchmarks). Our server has 50 cores + 60GB RAM.
- **Multivariate native** — accepts exogenous covariates (e.g. temperature) directly.
- **Cost: $0** recurring. Privacy: data stays on-server.

### Service stack changes

- `chronos_client.py` (new) — replaces `timesfm_client.py`. Loads `BaseChronosPipeline.from_pretrained("amazon/chronos-2", device_map="cpu")` once at first request; thread-safe lazy init. Uses `predict_df` so multivariate input + future covariates are first-class.
- `main.py` — swapped `TimesFMClient` import for `ChronosClient`. FastAPI route shape (`/forecast`, request/response Pydantic schemas) preserved so `V4Algorithm.php` keeps working through the same HTTP API.
- `requirements.txt` — added `torch` (CPU build), `chronos-forecasting`, `pandas`, `pymysql`; dropped `google-auth`, `requests` (no longer talking to Vertex).
- `.env` — removed all Vertex-related vars.
- Vertex endpoint **undeployed + endpoint deleted** to stop the $1.50/h burn.

### Critical lesson: rolling D+1 vs monthly single-shot

Production at EBS estimates **1 day ahead, daily** (cron at evening, estimate tomorrow). Backtesting must replicate this exactly. The right protocol for evaluating any algorithm on a past month is:

```
for D in [day 1, day 2, …, day 30]:
    history := real consumption from D-21 days up to D-1 23:45    # ACTUALS, not estimates
    forecast := algorithm(history, horizon=96)
    record forecast for day D
```

Our first ablation rolled the full month as a single call (eStart=2026-04-01, eEnd=2026-04-30 → predict 2880 steps in one shot). Foundation models compound their own error over a long horizon — for Chronos that meant WAPE 23-28% vs WAPE 12-15% under correct D+1 rolling. Same goes for any AR model. v1/v2/v3 are not autoregressive so they are mostly insensitive to this (no compounded error inside the algorithm; they always look at history-up-to-eStart), but for v4 the gap is huge.

### Tribunal Tulcea (cust 592) — rolling D+1 backtest, April 2026

3 PODs (RO002E240654735 / RO002E240674434 / RO002E241097175), county Tulcea (`TL`). All PODs have ~99.5% coverage on the test window. Covariates pulled from `weather_data` (`temp` + `feelslike`) at hourly grain, ffilled to 15-min.

**Setup**: 30 separate Chronos calls, each with 21-day rolling history of REAL actuals from `customer_far_592`, horizon = 96 (one day at 15-min). Batched 3 PODs per call. Total wall time 195s.

```
POD                              v1          v2          v3       v4 only    v4 +temp
----------------------------------------------------------------------------------
RO002E240654735         17.9%/-0.9%  29.1%/+25.3% 29.0%/+23.8% 14.2%/-0.1%  12.0%/-0.5%
RO002E240674434         19.3%/+3.6%  19.4%/+4.5%  19.8%/+4.4%  14.6%/-0.6%  12.4%/-1.1%
RO002E241097175         29.5%/+27.1% 34.6%/+33.4% 36.0%/+34.5% 14.8%/+2.0%  11.7%/+2.1%

AGGREGATE (mean across the 3 PODs):
  v1       WAPE 22.22%   bias  +9.95%
  v2       WAPE 27.72%   bias +21.08%
  v3       WAPE 28.26%   bias +20.90%
  v4 only  WAPE 14.54%   bias  +0.43%
  v4 +temp WAPE 12.06%   bias  +0.17%
```

**Findings:**

1. **Chronos-2 with `temp` + `feelslike` covariates wins decisively**: WAPE 12.06% vs the best baseline (v1) at 22.22%. That's a **46% relative error reduction**.
2. **Bias ≈ 0%** for Chronos — forecasts are calibrated, no systematic over- or under-shoot. v1 underestimates by 10%, v2/v3 by 21%. For energy balance / settlement this matters more than WAPE.
3. **Temperature helps clearly for Tribunal**: v4_uni → 14.54%, v4_cov → 12.06%. Reasonable: public buildings with HVAC respond to outdoor temperature.
4. **v2/v3 collapse on the high-consumption POD** (`RO002E241097175`): WAPE 34-36%, bias +33%. v2/v3's EWMA + trend multiplier overweights recent weeks where this POD spiked — Chronos didn't fall into that trap.
5. **v1 surprisingly stable** — it underweights recency too much by year-boundary cutoff, but for Tribunal April 2026 that happened to land closer to actual than v2/v3's misfired trend.

### What this validates

- Chronos-2 zero-shot, **self-hosted on CPU**, beats every hand-tuned baseline by a wide margin on this customer.
- The **rolling D+1 protocol** is what gives it the win — running it as a monthly single-shot drops WAPE to ~23%, which would have made it look only marginal. **Always evaluate algorithms in the same regime they'll run in production.**
- Covariates measurably help; we should keep the temp+feelslike path enabled for clients where it improves (start with `temperature=1` config flag).
- 3.3s/day for 3 PODs ≈ ~15 min/day for the full ~150-customer portfolio cron — comfortable.

### Open questions

- Does this hold on customers where v2/v3 were already competitive (e.g. Barbu Vacarescu cust 397 at 13% WAPE)?
- For prosumers (negative readings), the current zero-clip in V4Algorithm wipes the injection. Need a separate path.
- Does v4 still win when the test period has structural breaks (regime change)? Need a backtest on a customer with known change.
