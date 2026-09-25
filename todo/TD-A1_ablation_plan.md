# TD-A1 — Ablation Study: Energy Forecasting Research

**Status:** In research  
**Next:** [TD-B1_ablation_implementation.md](TD-B1_ablation_implementation.md)

---

## Objective

Conduct in-depth research in the field of electrical energy consumption forecasting, starting from the algorithms and data structures already present in the application. The goal is to identify alternative or complementary forecasting algorithms that can be compared against the current approach through a controlled ablation experiment.

The research process has two parallel tracks:

1. **Literature & SOTA review** — search academic papers, public benchmarks, and industry reports to understand the state of the art in short-term energy load forecasting, filtered by what is feasible given the data we actually capture and its frequency.

2. **Statistical DB profiling** — before selecting or implementing any algorithm, perform a data quality and distribution study on the existing database: data arrival frequency, coverage per customer, seasonality patterns, missing data gaps, and outliers. Algorithm selection must be grounded in the real characteristics of the available data, not just theoretical suitability.

---

## Starting Point — What the Current System Does

### Implemented Algorithms

The source code (`ForecastModel.php`) implements three estimation types, differentiated by the `estimation_type` column in `forecast_pods_estimates`:

| Type | DB Value | Description |
|------|----------|-------------|
| Simple | `simpla` | Historical average per hourly interval, day type, and month — pattern matching on customer consumption history |
| Temperature | `temperatura` | Consumption–temperature correlation using weather data (`weather_data`, `forecast_temperatures`) — adds an external regressor |
| Prosumer | `prosumator` | Estimation for customers with own generation (solar panels) — separate logic for net metering |

### Data Granularity

- **Base interval:** 15 minutes (96 intervals/day)
- **Optional aggregation:** 60 minutes (24 intervals/day)
- **Forecast horizon:** 1 day (D+1, standard in the energy market)
- **History used:** variable per customer, minimum 1 year, up to whatever exists in `customer_far_{id}`

### Available Input Variables in DB

| Source | Table | Available Data |
|--------|-------|---------------|
| Historical actual consumption | `customer_far_{id}` | EA per POD per 15 min, from 2023+ |
| Observed temperature | `weather_data` | `temp`, `feelslike` per county, at 15 min intervals |
| Forecast temperature | `forecast_temperatures` | Weather forecast for the estimated day |
| Public holidays / working days | `working_free_days` | National calendar |
| Day type profiles | `forecast_intervals` | Hourly profile per month, day type (Mon–Fri, Sat, Sun) |
| Synthetic data | `forecast_synthetics` | Artificially generated data for new customers |
| Consumption type | `forecast_customers_consumption_types` | Customer category (residential, industrial, etc.) |

---

## SOTA Review — State of the Art in Short-Term Load Forecasting

The literature review must be scoped to what is realistic given our data: 15-minute interval metering, D+1 horizon, per-customer granularity, with temperature as the main external variable.

### Search strategy

- **Primary sources:** Google Scholar, IEEE Xplore, ScienceDirect, arXiv (cs.LG, eess.SP)
- **Key search terms:** `short-term load forecasting`, `STLF`, `electricity consumption forecasting 15-minute`, `per-customer energy forecasting`, `smart meter forecasting`, `D+1 energy forecast Romania`
- **Benchmark datasets to reference:** GEFCOM (Global Energy Forecasting Competition), Open Power System Data, ENTSO-E transparency data
- **Target:** 10–15 relevant papers from the last 5 years; note method, accuracy reported (MAPE/MAE), data requirements, and applicability to our constraints

### SOTA categories to cover

| Category | Representative methods | Data requirements | Applicability filter |
|----------|----------------------|-------------------|---------------------|
| Statistical baselines | ARIMA, SARIMA, Exponential Smoothing | ≥1 year, no gaps | check gap tolerance |
| Classical ML | Random Forest, Gradient Boosting, SVR | features engineering needed | feasible with our DB |
| Deep Learning | LSTM, Transformer, N-BEATS | large data, GPU | check per-customer data volume |
| Hybrid models | Statistical + ML, physics-informed | depends on base | evaluate case by case |
| Probabilistic forecasting | Quantile regression, conformal prediction | same as base model | useful for risk assessment |

### Feasibility filter — our constraints

Each SOTA method found must be evaluated against:
- Requires data at 15-min intervals? (we have it)
- Minimum history length needed? (some customers have < 1 year)
- Handles missing intervals? (gaps are common in metering data)
- Works per individual customer, not just aggregated load? (we forecast per customer)
- Can run within the 60-second per-customer time budget?
- Requires GPU / external infrastructure? (we run on MariaDB + PHP)

---

## Statistical DB Profiling — Data Quality Study

Before selecting any algorithm, a quantitative study of the existing data must be completed. This study will directly constrain which algorithms are viable.

### A — Data frequency and coverage

For each active customer in `customer_far_{id}`:

- **Total intervals available** vs expected (days × 96 intervals/day)
- **Coverage ratio** = actual intervals / expected intervals (%)
- **Date range** — first and last recorded interval
- **Distribution of customers by history length** (< 6 months, 6–12 months, 1–2 years, > 2 years)

> Methods like ARIMA or LSTM require high coverage ratios (>90%) and long history. This analysis will immediately filter out which algorithms are viable for which customers.

### B — Missing data characterization

- **Gap frequency** — how often are intervals missing per customer?
- **Gap duration distribution** — single missing intervals vs hours vs full days
- **Gap patterns** — random (meter failure) vs systematic (weekends, holidays)?
- **Seasonal gap bias** — are gaps more frequent in certain months?

> This determines whether imputation is needed and which imputation strategy is appropriate before any model can be trained.

### C — Consumption distribution and seasonality

- **Annual seasonality** — average consumption by month across all customers
- **Weekly seasonality** — average consumption by day of week (Mon–Sun)
- **Daily seasonality** — average load curve shape by hour (96 intervals averaged)
- **Customer-level variance** — coefficient of variation per customer to identify stable vs volatile consumers
- **Outlier days** — days where consumption deviates > 3σ from the customer's mean (meter errors, holidays, special events)

### D — Temperature correlation study

Using `weather_data` (temp, feelslike) joined with `customer_far_{id}`:

- **Pearson correlation** between temperature and consumption per customer per season
- **Heating/cooling split** — does the customer show a V-shaped or monotonic relationship with temperature?
- **Lag analysis** — does consumption respond to temperature with a delay (thermal inertia)?

> This directly determines which customers benefit from DIR-2 (temperature regression) and informs the HDD/CDD approach.

### E — Cross-customer patterns

- **Cluster preview** — plot daily load curves for all customers to visually identify groups before formal clustering (DIR-3)
- **Correlation matrix** — are some customers highly correlated? (shared building, same industrial zone)
- **New customers** — identify customers with < 3 months of history who currently rely on synthetic data

### Output of the DB profiling study

| Deliverable | Format |
|-------------|--------|
| Coverage report per customer | SQL query + table |
| Gap characterization summary | statistics table |
| Seasonal load curves | charts (export from DB query) |
| Temperature correlation per customer | ranked table |
| Customer segmentation preview | grouping table |
| List of pilot-eligible customers | filtered list (stable, >1 year, no prosumer, >90% coverage) |

---

## Research Directions — A1

Each direction will be individually assessed and marked `✅ applicable` / `❌ not relevant` / `⚠️ partial` after research.

---

### DIR-1 — Improving the Historical Average Baseline

**Current approach:** Simple arithmetic mean over similar historical intervals.

**Directions to explore:**
- Time-weighted average — more recent data carries higher weight (exponential decay)
- Automatic outlier removal from history before averaging
- Adaptive selection of the number of historical weeks based on customer consumption volatility
- Finer differentiation between public holidays and regular weekends (existing `working_free_days` is used as binary)

**Data required:** already in DB  
**Implementation complexity:** low  
**Risk:** low

---

### DIR-2 — Regression Models with External Variables (Temperature)

**Current approach:** `estimation_type = 'temperatura'` exists but correlation is simple (lookup by interval/month).

**Directions to explore:**
- Linear regression: consumption ~ temperature per customer, calibrated on the last N months
- Separating heating effect (winter) from cooling effect (summer) — Heating Degree Days / Cooling Degree Days
- Using `feelslike` instead of raw temperature (available in `weather_data`)
- Multi-regressor models: temperature + day type + month

**Data required:** already in DB (`weather_data` has both `temp` and `feelslike`)  
**Implementation complexity:** medium  
**Risk:** low–medium (per-customer calibration required)

---

### DIR-3 — Customer Clustering and Group Profile Forecasting

**Current approach:** Each customer is estimated independently.

**Directions to explore:**
- Grouping customers with similar consumption profiles (clustering on daily curve shape)
- Estimating a group profile and applying it to customers with insufficient history
- Automatic detection of behavioral changes (concept drift) — customers who have changed activity patterns

**Data required:** `customer_far_{id}`, `forecast_customers_consumption_types`  
**Implementation complexity:** high  
**Risk:** medium — requires validation that groups remain stable over time

---

### DIR-4 — Foundation Models and Modern Neural Networks for Time Series

**Context:** Since 2023, a new class of pre-trained **foundation models for time series** has emerged, analogous to LLMs for text. These models are trained on massive collections of diverse time series and can perform **zero-shot or few-shot forecasting** — meaning they can forecast a new series without being retrained on it. This is particularly relevant for our use case since some customers have limited history.

#### Google — TimesFM (2024)

Google Research released **TimesFM** (Time Series Foundation Model) in 2024, a decoder-only transformer pre-trained on 100 billion real-world time points from Google Trends and Wikipedia pageviews.

- **Paper:** *A decoder-only foundation model for time-series forecasting* (Das et al., 2024, arXiv:2310.10688)
- **Key capability:** Zero-shot forecasting — no fine-tuning needed on new data
- **Input:** any univariate time series at any frequency
- **Forecast horizon:** flexible (up to 512 steps)
- **Available as:** Python package (`timesfm` on PyPI), model weights on HuggingFace
- **Relevance for us:** could forecast customers with < 3 months of history currently handled by synthetic data

#### Amazon — Chronos (2024)

AWS/Amazon released **Chronos**, a language model adapted for time series by tokenizing values into discrete bins.

- **Paper:** *Chronos: Learning the Language of Time Series* (Ansari et al., 2024, arXiv:2403.07815)
- **Available as:** HuggingFace model (`amazon/chronos-t5-*` family, from tiny to large)
- **Key capability:** probabilistic zero-shot forecasting (returns full forecast distribution, not just point estimate)
- **Relevance for us:** probabilistic output gives uncertainty intervals — useful for risk management in energy trading

#### Salesforce — Moirai (2024)

**Moirai** (Unified Training of Universal Time Series Forecasting Transformers) by Salesforce Research.

- **Paper:** *Unified Training of Universal Time Series Forecasting Transformers* (Woo et al., 2024, arXiv:2402.02592)
- **Key capability:** handles multiple frequencies natively (15 min, hourly, daily) — directly aligned with our data
- **Available as:** HuggingFace model (`Salesforce/moirai-*`)

#### Other modern neural approaches (non-foundation)

| Model | Year | Key idea | Relevance |
|-------|------|----------|-----------|
| **TFT** (Temporal Fusion Transformer) | 2021 | Multi-horizon forecasting with attention + static covariates | can incorporate customer type, temperature as covariates |
| **N-BEATS** | 2020 | Pure neural basis expansion, no feature engineering needed | strong on univariate series |
| **N-HiTS** | 2022 | Hierarchical interpolation, better long-horizon | handles daily + weekly + annual seasonality |
| **PatchTST** | 2023 | Transformer with patch-based input (efficient on long series) | good for 15-min series with long history |
| **TimesNet** | 2023 | Transforms 1D series into 2D for 2D convolutions | captures both intra-day and inter-day patterns |

#### Key questions for our evaluation

- **Zero-shot feasibility:** can TimesFM / Chronos forecast our 15-min consumption series out-of-the-box without training?
- **Inference speed:** foundation models are large — can one model be run per customer within the 60-second budget, or do we need batch inference?
- **Infrastructure:** these models require Python (PyTorch/JAX). Can we call a Python microservice from PHP, or run offline and write results to DB?
- **Accuracy vs complexity tradeoff:** do foundation models outperform a well-tuned weighted historical average for our specific use case?

**Suggested experiment:** run TimesFM and Chronos zero-shot on 3–5 pilot customers and compare MAE/MAPE against the current `simpla` baseline before committing to any infrastructure investment.

**Data required:** `customer_far_{id}` exported as time series (CSV or direct DB query from Python)  
**Implementation complexity:** high — requires Python environment and a bridge to PHP  
**Risk:** medium — zero-shot performance on energy consumption data needs empirical validation

---

**Current approach:** No explicit time series modeling — fixed time-window averages are used.

**Directions to explore:**
- Exponential Smoothing (Holt-Winters) — captures trend + seasonality
- Simple ARIMA per customer — suitable for customers with stable, predictable consumption
- Detection of multiple seasonality patterns (daily + weekly + annual)

**Data required:** `customer_far_{id}` with at least 2 years of data  
**Implementation complexity:** high — requires external library or PHP/SQL implementation  
**Risk:** high — ARIMA is sensitive to missing data and outliers, common in metering data

---

### DIR-5 — Analog Day Method (Similar Day Forecasting)

**Current approach:** Implicitly similar but without explicit day selection.

**Directions to explore:**
- Selection of the K most similar historical days based on: temperature + day type + month
- Weighting analog days by distance to the target forecast day
- More robust to outliers than simple averaging

**Data required:** `customer_far_{id}`, `weather_data`  
**Implementation complexity:** medium  
**Risk:** low — conceptually simple, easy to debug and explain to end users

---

### DIR-6 — Post-estimation Adjustment (Post-processing)

**Current approach:** No post-processing after estimation.

**Directions to explore:**
- Adjustment to a known monthly total (if a guaranteed consumption contract exists)
- Clipping to zero for prosumers (net consumption cannot be negative)
- Smoothing on night/morning transition intervals

**Data required:** `contracts`, `service_rates`, `forecast_pods_estimates`  
**Implementation complexity:** low  
**Risk:** low

---

## Evaluation Metrics

Metrics must be agreed upon before implementation to enable meaningful comparison between algorithms. To be assessed for relevance in the Romanian energy market context (ANRE, BRM):

| Metric | Formula | Relevance |
|--------|---------|-----------|
| MAE | Mean Absolute Error | easy to interpret in MWh |
| MAPE | Mean Absolute Percentage Error | comparable across customers of different sizes |
| RMSE | Root Mean Square Error | penalizes large errors more heavily |
| Bias | Signed mean error | detects systematic under- or over-estimation |

**Open question:** Are there asymmetric penalties in the Romanian market (under- vs over-estimation treated differently by ANRE/BRM)?

---

## Technical Constraints

1. The new algorithm must produce data at **15-minute granularity** — the energy market requires D+1 at 15 min intervals
2. Must work for **new customers** with limited history (existing `forecast_synthetics` mechanism in place)
3. Estimation time per customer must remain **under 60 seconds** — full estimation for 150+ customers currently runs in ~5 minutes
4. Results will be stored in the existing DB structure — implementation details in **TD-B1**

---

## Anti-Cheat Rule — Temporal Masking (MANDATORY)

**Every algorithm, including backtests on historical data, must be strictly temporally masked.**

When estimating for a target period starting at `eStart`:
- **Allowed input:** `far_datetime < eStart` (strictly before the first day being estimated)
- **Forbidden input:** any data from `eStart` onwards, even if it exists in the DB today

This rule applies when:
- Running estimation normally (D+1 forecasting) → naturally satisfied if eStart is tomorrow
- Running a **backtest** on a past period (e.g., re-estimating Jan 2025 today in May 2026) → the DB now contains all of 2025; the SQL filter MUST cut strictly at eStart to avoid the algorithm seeing data it couldn't have seen at the real estimation time

**Current v1 status:** uses `year(far_datetime) < year('$eStart')` — this is overcautious (excludes Jan 2025 when estimating Feb 2025, losing 1 month of recent data) but does NOT leak future data. Safe, but wastes recent history.

**v2 requirement:** use `far_datetime < '$eStart'` as the exact temporal cutoff. More accurate and still anti-cheat.

**Consequence if violated:** MAPE scores look artificially good; algorithm comparison is meaningless; any production deployment based on those scores would fail in real D+1 usage.

---

## Expected Output from A1

**SOTA review:**
- [ ] 10–15 relevant papers reviewed and summarized
- [ ] Each SOTA method evaluated against our feasibility filter
- [ ] Top 2–3 candidate methods selected for further consideration

**DB profiling study:**
- [ ] Coverage report completed for all active customers
- [ ] Gap characterization summary produced
- [ ] Seasonal and daily load curves analyzed
- [ ] Temperature correlation assessed per customer
- [ ] List of pilot-eligible customers produced

**Research directions:**
- [ ] Each direction DIR-1..DIR-6 marked as applicable or not, with justification grounded in both SOTA and DB profiling findings
- [ ] At least 1–2 directions selected for implementation in TD-B1

**Metrics & pilot:**
- [ ] Agreed evaluation metrics defined (with ANRE/BRM asymmetry question answered)
- [ ] Pilot customer confirmed for the first experiment
