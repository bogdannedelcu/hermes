/**
 * Estimat vs Realizat — multi-algorithm comparison screen
 * Layout: rows = (hour-header + algo sub-rows), cols = days
 * Algorithms are defined in evrAlgorithms. Each has a description shown via info popup.
 */

// ─── Algorithm registry ──────────────────────────────────────────────────────

var evrAlgorithms = {
    'realizat': {
        label: 'Realizat',
        color: '#388E3C',
        description: `
            <h6>Consum Realizat</h6>
            <p>Citirile reale de consum la 15 minute, importate zilnic de la distribuitori
            prin fisiere de tip SLC/REG. Stocate in <code>customer_far_{id}</code>.</p>
            <p>Aceste valori reprezinta consumul efectiv masurat la contor — sursa de adevar
            fata de care se evalueaza calitatea estimarilor.</p>
            <h6>Disponibilitate</h6>
            <ul>
                <li>Disponibil cu o intarziere de 1-2 zile (livrare distribuitor)</li>
                <li>Poate contine lacune sau erori de citire (zerouri, spike-uri)</li>
                <li>Nu este disponibil pentru ziua de maine (D+1)</li>
            </ul>
        `
    },
    'v1': {
        label: 'v1 — Medie Istorica',
        color: '#1565C0',
        description: `
            <h6>Medie Istorica Simpla (v1)</h6>
            <p>Algoritmul de baza al sistemului. Calculeaza media aritmetica a consumului
            inregistrat in intervale orare similare din istoricul clientului, grupate dupa:</p>
            <ul>
                <li><strong>Luna</strong> — sezonalitate anuala</li>
                <li><strong>Tipul zilei</strong> — Luni-Vineri / Sambata / Duminica / Zi libera</li>
                <li><strong>Intervalul orar</strong> — cele 96 de sferturi de ora ale zilei</li>
            </ul>
            <p>Datele istorice provin din <code>customer_far_{id}</code>, populat zilnic
            cu citiri reale de la distribuitori.</p>
            <h6>Limitari</h6>
            <ul>
                <li>Nu detecteaza trenduri — un client cu consum in crestere va fi subestimat</li>
                <li>Saptamanile recente si cele vechi au acelasi impact (nu exista decay)</li>
                <li>Nu incorporeaza prognoza meteo (temperatura) decat daca clientul are
                    <em>estimation_type = temperatura</em></li>
                <li>Necesita minim 2-4 saptamani de istoric pentru rezultate acceptabile</li>
            </ul>
            <h6>Cand e mai bun</h6>
            <p>Clienti stabili, fara trend, cu profil sezonier clar si istoric bogat (&gt; 6 luni).</p>
        `
    },
    'v2': {
        label: 'v2 — EWMA + Temp + Trend',
        color: '#E65100',
        description: `
            <h6>v2 — EWMA + Temperatura + Trend</h6>
            <p>Foloseste aceeasi structura de baza ca v1 (grupare pe tip zi / interval orar), dar
            combina cinci imbunatatiri pentru estimari mai precise:</p>

            <h6>1. EWMA — decay exponential in timp</h6>
            <p>Datele recente au impact mai mare decat cele vechi:</p>
            <p style="text-align:center; font-family:monospace;">
                w<sub>timp</sub> = e<sup>-λ × saptamani_in_urma</sup> &nbsp; (λ = 0.02)
            </p>
            <table class="table table-sm table-borderless mb-2" style="font-size:0.85em">
                <tr><th>Varsta date</th><th>Pondere</th></tr>
                <tr><td>1 saptamana</td><td>0.98</td></tr>
                <tr><td>1 luna</td><td>0.92</td></tr>
                <tr><td>6 luni</td><td>0.59</td></tr>
                <tr><td>1 an</td><td>0.35</td></tr>
                <tr><td>2 ani</td><td>0.12</td></tr>
            </table>

            <h6>2. Kernel temperatura (Gaussian)</h6>
            <p>Zilele istorice cu temperatura similara celei prognozate primesc pondere mai mare:</p>
            <p style="text-align:center; font-family:monospace;">
                w<sub>temp</sub> = e<sup>-σ × (T<sub>istoric</sub> - T<sub>prognoza</sub>)²</sup> &nbsp; (σ = 0.05)
            </p>
            <table class="table table-sm table-borderless mb-2" style="font-size:0.85em">
                <tr><th>Diferenta °C</th><th>Pondere</th></tr>
                <tr><td>0 °C</td><td>1.00</td></tr>
                <tr><td>2 °C</td><td>0.82</td></tr>
                <tr><td>5 °C</td><td>0.29</td></tr>
                <tr><td>10 °C</td><td>0.007</td></tr>
            </table>
            <p>Surse: <code>forecast_temperatures</code> (istoric) si <code>weather_data</code> (prognoza).
            Daca temperatura lipseste, w<sub>temp</sub> = 1 (neutru).</p>

            <h6>Agregare combinata</h6>
            <p style="text-align:center; font-family:monospace;">
                estimare = Σ(consum × w<sub>timp</sub> × w<sub>temp</sub>) / Σ(w<sub>timp</sub> × w<sub>temp</sub>)
            </p>

            <h6>3. Filtru zile-zero</h6>
            <p>Exclude din training orice zi in care consumul total al unui POD = 0
            (protejeaza EWMA de artefactele de import lipsa — ex. Martie 2026 cu citiri zero
            pentru anumite POD-uri).</p>

            <h6>4. Date cross-luna</h6>
            <p>Pe langa datele din aceeasi luna din anii anteriori, include si ultimele
            <strong>12 saptamani</strong> indiferent de luna. Combinat cu kernel-ul de temperatura,
            captureaza schimbari recente de regim (utilaj nou, expansiune) chiar daca s-au intamplat
            in luna precedenta.</p>

            <h6>5. Multiplier de trend (per POD)</h6>
            <p>Detecteaza POD-urile al caror consum a crescut/scazut intre ani:</p>
            <p style="text-align:center; font-family:monospace;">
                trend = AVG(ultimele 60 zile) / AVG(aceeasi fereastra cu 1 an in urma)
            </p>
            <p>Multiplier-ul este aplicat pe estimarea finala si limitat la intervalul <strong>[0.3, 3.0]</strong>
            pentru a evita extreme. Default 1.0 daca POD-ul nu are date in ambele ferestre.</p>
            <p>Exemplu: POD cu consum 3× mai mare in Ian-Mar 2026 fata de Ian-Mar 2025 va avea
            multiplier ≈ 3, corectand estimarile pentru Apr 2026 in sus.</p>

            <h6>Diferente fata de v1</h6>
            <ul>
                <li><strong>Temporal cutoff exact:</strong> <code>far_datetime &lt; eStart</code> (nu limita de an)</li>
                <li><strong>Ponderare temporala</strong> (EWMA) in loc de medie simpla</li>
                <li><strong>Ponderare prin temperatura</strong> (Gaussian)</li>
                <li><strong>Date cross-luna</strong> incluse in training</li>
                <li><strong>Multiplier de trend</strong> aplicat post-agregare</li>
                <li><strong>Filtru zile-zero</strong> in atat calea simpla cat si cea de temperatura</li>
            </ul>

            <h6>Cand e mai bun decat v1</h6>
            <p>POD-uri cu trend de crestere/scadere, schimbari de regim, sau profil sensibil
            la temperatura (incalzire/aer conditionat).</p>

            <h6>Limite</h6>
            <p>Schimbari complet fara precedent in istoric (utilaj absolut nou, fara nicio
            baza de comparatie) raman dificile. Pentru aceste cazuri se recomanda introducere
            manuala a estimarii.</p>
        `
    },
    'v3': {
        label: 'v3 — Lag + HDD/CDD',
        color: '#7B1FA2',
        description: `
            <h6>v3 — Lag features + HDD/CDD piecewise temperature</h6>
            <p>Mosteneste toate componentele lui v2 (EWMA, cross-month, filtru zile-zero, trend
            multiplier cu shrinkage Bayesian) si adauga doua imbunatatiri din state-of-the-art
            short-term load forecasting:</p>

            <h6>1. Lag features (persistence baseline)</h6>
            <p>Pentru fiecare interval estimat, blendeaza estimarea cu citirile reale de la
            <strong>24h</strong> si <strong>168h (1 saptamana)</strong> in urma — doar din date
            existente in <code>customer_far_{id}</code> (strict inainte de <em>eStart</em>):</p>
            <p style="text-align:center; font-family:monospace;">
                final = (w<sub>m</sub>·model + w<sub>24</sub>·lag<sub>24h</sub> + w<sub>168</sub>·lag<sub>168h</sub>) / (w<sub>m</sub> + w<sub>24</sub>·has<sub>24</sub> + w<sub>168</sub>·has<sub>168</sub>)
            </p>
            <p>Ponderi: w<sub>m</sub>=2.0, w<sub>24</sub>=1.0, w<sub>168</sub>=1.0 (conservatoare:
            model domina ~50%, fiecare lag prezent contribuie cu ~25%). Daca lag-ul lipseste
            (orizont &gt; 7 zile, lipsa istoric, sau zi-zero — artefact import), greutatea lui
            devine 0 si modelul ramane neschimbat. Zilele cu consum total = 0 sunt excluse
            automat (acelasi filtru ca in v2).</p>
            <p><b>Cand ajuta:</b> pentru orizont scurt (1-7 zile) si POD-uri cu consum stabil,
            "ieri la aceeasi ora" e cel mai puternic predictor. Explica tipic &gt; 60% din varianta
            pe consumatori stabili.</p>

            <h6>2. HDD / CDD piecewise temperature</h6>
            <p>Inlocuieste kernel-ul Gaussian al lui v2 pe temperatura bruta cu o reprezentare
            piecewise care reflecta corect raspunsul V-shape:</p>
            <p style="text-align:center; font-family:monospace;">
                HDD = max(0, 18 - T) &nbsp;&nbsp; CDD = max(0, T - 22)
            </p>
            <p>Praguri 18°C / 22°C sunt standard <strong>ASHRAE</strong> — nu parametri liberi.
            In zona neutra (18-22°C) ambele sunt zero si temperatura nu mai influenteaza ponderea.
            Sub 18°C ponderea creste cu cererea de incalzire, peste 22°C cu cererea de racire.</p>
            <p style="text-align:center; font-family:monospace;">
                w<sub>temp</sub> = exp(-σ × ((HDD<sub>hist</sub> - HDD<sub>tgt</sub>)² + (CDD<sub>hist</sub> - CDD<sub>tgt</sub>)²))
            </p>
            <p>Aplicabil doar pe <em>calea simpla</em>. Pe calea de temperatura, v3 = v2 + lag.</p>

            <h6>Diferente fata de v2</h6>
            <ul>
                <li><strong>Lag-blend</strong> universal (ambele cai), post-process pe estimare</li>
                <li><strong>HDD/CDD kernel</strong> (calea simpla) in loc de Gaussian pe temperatura bruta</li>
                <li>Toate celelalte mecanisme v2 sunt pastrate identic</li>
            </ul>

            <h6>Cand e mai bun decat v2</h6>
            <ul>
                <li>Orizont scurt (1-7 zile) — lag features domina</li>
                <li>Clienti HVAC / rezidentiali — HDD/CDD captureaza mai bine non-liniaritatea</li>
                <li>POD-uri stabile / zgomotoase — lag-ul "salveaza" estimarea cand modelul EWMA pierde semnal</li>
            </ul>

            <h6>Limite</h6>
            <ul>
                <li>Pentru orizont &gt; 7 zile, lag-ul devine indisponibil → v3 = v2 + HDD/CDD doar</li>
                <li>Daca istoric foarte recent lipseste (citiri nedescarcate inca), lag-ul nu poate fi folosit</li>
            </ul>
        `
    },
    'v4': {
        label: 'v4 — Chronos-2 (Amazon)',
        color: '#00838F',
        description: `
            <h6>v4 — Chronos-2 (Amazon, self-hosted)</h6>
            <p>Apel local la <strong>Chronos-2</strong>, modelul de tip foundation pentru serii
            temporale al Amazon Science (octombrie 2025). Encoder-only Transformer pre-antrenat
            pe miliarde de puncte din date diverse (consum electricitate, trafic, vreme, finante).</p>
            <p>Spre deosebire de v1/v2/v3 care fiteaza statistici per-client/POD, v4 trateaza
            modelul ca o cutie neagra pre-antrenata si ii ofera doar contextul recent al fiecarui POD.
            Inferenta ruleaza <strong>local pe serverul nostru, CPU</strong> — fara cost recurent
            si fara dependenta de cloud.</p>

            <h6>De ce Chronos-2 (vs TimesFM)</h6>
            <ul>
                <li><strong>Encoder-only forecast head</strong> — produce 1024 pasi/forward pass
                    fara autoregressive, deci eroarea nu se acumuleaza pe orizont lung</li>
                <li><strong>Bate TimesFM-2.5 in benchmarks 2026</strong> (TSFM.ai, Decathlon,
                    arXiv:2602.10848). MASE ~0.31 la context lung, -47% fata de Seasonal Naive</li>
                <li><strong>Self-hostable</strong> — model deschis pe HuggingFace
                    (<code>amazon/chronos-2</code>), ~200MB; CPU consumer hardware e suficient</li>
                <li>Suporta nativ <strong>covariate multivariate</strong> (temperatura ca regresor)
                    — pregatit pentru extensii viitoare</li>
            </ul>

            <h6>Input per POD</h6>
            <ul>
                <li><strong>Context:</strong> ultimele ~21 zile de consum la granularitate
                    15-minute (max. 2016 puncte; modelul accepta pana la 8192)</li>
                <li><strong>Frecventa:</strong> 15min sub-orar nativ — Chronos e
                    frequency-agnostic, nu cere encoding special</li>
                <li><strong>Orizont:</strong> tot intervalul cerut, in pasi de 15 minute
                    (pana la 1024 fara warning, pana la 2880 cu degradare usoara)</li>
                <li><strong>Gap-fill:</strong> grila contigua construita in PHP — gap-urile
                    minore sunt interpolate liniar; POD-urile cu &gt; 50% lipsa sunt sarite</li>
            </ul>

            <h6>Output</h6>
            <p>Chronos-2 returneaza distributie probabilistica: P10, P50 (mediana), P90 pe fiecare
            interval. v4 stocheaza P50 ca <code>forecast_ea</code>, similar cu celelalte algoritme.
            Quantilele P10/P90 raman disponibile in serviciul Python pentru viitoare extensii
            (risk management, prag de siguranta in trading energie).</p>

            <h6>Avantaje</h6>
            <ul>
                <li>Nu necesita feature engineering — modelul a invatat sezonalitati zilnice,
                    saptamanale, anuale + raspuns la regime change din antrenamentul global</li>
                <li>Util pentru POD-uri cu istoric scurt (3-6 luni) — beneficiaza de transfer
                    learning din miliardele de serii vazute</li>
                <li>Probabilistic out-of-the-box (P10/P90) si fara cost de inferenta</li>
            </ul>

            <h6>Limite curente</h6>
            <ul>
                <li>Versiunea actuala foloseste doar istoricul de consum — temperatura va fi
                    adaugata ca covariate intr-o iteratie viitoare</li>
                <li>Nu modeleaza prosumatori (P50 e clipat la 0)</li>
                <li>Latenta: ~10s per POD la orizont lunar (2880 pasi), ~0.4s per POD in batch</li>
                <li>Necesita disponibilitatea serviciului <code>127.0.0.1:8081</code>;
                    daca pica, faza v4 este sarita curat (UI arata "0 valori estimate")</li>
            </ul>

            <h6>Surse</h6>
            <p>Ansari et al., 2024 — <em>Chronos: Learning the Language of Time Series</em>
            (arXiv:2403.07815). Chronos-2 — <em>From Univariate to Universal Forecasting</em>
            (arXiv:2510.15821). Model: <code>amazon/chronos-2</code> pe HuggingFace.</p>
        `
    }
};

// ─── State ───────────────────────────────────────────────────────────────────

var evrDateRange  = null;
var evrStart      = null;
var evrEnd        = null;
var evrIntervalType  = '60min';
// Production default: only Realizat + v1 are checked. v2/v3/v4 stay registered in
// evrAlgorithms (so the checkboxes still render and can be turned on for benchmarks)
// but are unticked by default — keeps the screen fast and matches the legacy semantics.
var evrSelectedAlgos = ['realizat', 'v1'];
var evrSpreadsheet   = null;
var evrSheet         = null;
var evrCurrRow       = 1;
var evrNumDays       = 31;
var evrShowDiff      = false;
var evrLastResults   = null;
var evrViewMode      = 'pod'; // POD-only view; client toggle removed

const evrRowH  = 11;
const evrFontS = 9;
const evrHdrH  = 14;

// ─── Init ────────────────────────────────────────────────────────────────────

$(document).ready(function () {
    $("#evr_start_date").kendoDatePicker({ format: 'yyyy-MM-dd', change: evrDateChanged });
    $("#evr_end_date").kendoDatePicker({ format: 'yyyy-MM-dd', change: evrDateChanged });

    evrBuildAlgoControls();

    $("#evr_spreadsheet").kendoSpreadsheet({
        rows: 500,
        columns: 200,
        toolbar: false,
        sheetsbar: false
    });
    evrSpreadsheet = $("#evr_spreadsheet").data("kendoSpreadsheet");
    evrSheet = evrSpreadsheet.activeSheet();
});

// ─── Period / date wiring ─────────────────────────────────────────────────────

function evrPeriodChanged() {
    var year     = parseInt(selYear_tp_evr);
    var monthIdx = selMonth_tp_evr; // 0-based index, -1 = none selected
    if (isNaN(year) || year < 0) return;

    var start, end;
    if (monthIdx < 0) {
        start = new Date(year, 0, 1);
        end   = new Date(year, 11, 31);
    } else {
        start = new Date(year, monthIdx, 1);
        end   = new Date(year, monthIdx + 1, 0); // last day of month
    }

    evrDateRange = { start: start, end: end };
    $("#evr_start_date").data("kendoDatePicker").value(start);
    $("#evr_end_date").data("kendoDatePicker").value(end);
    evrStart = kendo.toString(start, 'yyyy-MM-dd');
    evrEnd   = kendo.toString(end,   'yyyy-MM-dd');

    var ms = $("#evrCustomers").data("kendoMultiSelect");
    if (ms) ms.dataSource.read();
}

function evrDateChanged() {
    var s = $("#evr_start_date").data("kendoDatePicker").value();
    var e = $("#evr_end_date").data("kendoDatePicker").value();
    if (s) evrStart = kendo.toString(s, 'yyyy-MM-dd');
    if (e) evrEnd   = kendo.toString(e, 'yyyy-MM-dd');
}

function evrCustomersChanged() {}

// ─── Algorithm controls ───────────────────────────────────────────────────────

function evrBuildAlgoControls() {
    var container = $('#evr_algorithms_container');
    container.empty();

    Object.keys(evrAlgorithms).forEach(function (key) {
        var algo    = evrAlgorithms[key];
        var checked = evrSelectedAlgos.indexOf(key) >= 0;
        var badge   = $('<span>').addClass('badge rounded-pill me-1')
                        .css('background-color', algo.color).text(' ');

        var cb = $('<div class="form-check form-check-inline me-0">').append(
            $('<input class="form-check-input" type="checkbox">')
                .attr('id', 'evr_cb_' + key)
                .prop('checked', checked)
                .val(key)
                .on('change', function () {
                    if (this.checked) {
                        if (evrSelectedAlgos.indexOf(key) < 0) evrSelectedAlgos.push(key);
                    } else {
                        evrSelectedAlgos = evrSelectedAlgos.filter(function (k) { return k !== key; });
                    }
                }),
            $('<label class="form-check-label d-flex align-items-center gap-1">')
                .attr('for', 'evr_cb_' + key)
                .append(badge)
                .append(document.createTextNode(algo.label))
        );

        var infoBtn = $('<button class="btn btn-link btn-sm p-0 ms-1" title="Descriere algoritm">')
            .html('<i class="fa fa-info-circle text-secondary"></i>')
            .on('click', function (e) { e.preventDefault(); evrShowAlgoInfo(key); });

        container.append(cb).append(infoBtn).append('  ');
    });
}

function evrShowAlgoInfo(key) {
    var algo = evrAlgorithms[key];
    $('#algoInfoTitle').text(algo.label);
    $('#algoInfoBody').html(algo.description);
    $('#algoInfoModal').modal('show');
}

// ─── Load data ────────────────────────────────────────────────────────────────

// Wipes every visible result surface before a new fetch so nothing from the previous run leaks through.
function evrClearAll() {
    evrLastResults = null;

    // Spreadsheet (grid tab)
    try {
        if (evrSpreadsheet) {
            var s = evrSpreadsheet.activeSheet();
            s.batch(function () {
                s.range('R1C1:R2000C200').clear();
                for (var ci = 0; ci < 200; ci++) s.columnWidth(ci, 64);
            });
        }
    } catch (e) { /* ignore */ }

    // Daily totals chart
    var ch = $("#evr_chart").data("kendoChart");
    if (ch) ch.destroy();
    $("#evr_chart").empty();

    // Stats tab — all containers and any Kendo charts inside
    ['#evr_stats_hourly', '#evr_stats_daily', '#evr_stats_scatter'].forEach(function (sel) {
        var c = $(sel).data("kendoChart"); if (c) c.destroy();
        $(sel).empty();
    });
    $('#evr_stats_context, #evr_stats_summary, #evr_stats_perpod, #evr_stats_glossary').empty();
}

function evrLoad() {
    if (!evrStart || !evrEnd) { alert('Selectati o perioada.'); return; }
    if (evrSelectedAlgos.length === 0) { alert('Selectati cel putin un algoritm.'); return; }

    var bg = $("#evr_view_interval").data("kendoButtonGroup");
    if (bg && bg.current().length) evrIntervalType = bg.current().text().trim();

    evrClearAll();

    if (evrViewMode === 'pod') { evrLoadPod(); return; }

    kendo.ui.progress($(document.body), true);
    var customers = $("#evrCustomers").data("kendoMultiSelect").value();

    var algoKeys = evrSelectedAlgos.filter(function (k) { return k !== 'realizat'; });
    var promises = [];

    algoKeys.forEach(function (algo) { promises.push(evrFetchEstimates(algo, customers)); });
    if (evrSelectedAlgos.indexOf('realizat') >= 0) { promises.push(evrFetchActuals(customers)); }

    Promise.all(promises).then(function (results) {
        kendo.ui.progress($(document.body), false);
        evrLastResults = results;
        evrFillGrid(results);
        evrFillChart();
        if ($('#evr-stats-pane').hasClass('active')) evrFillStats();
    }).catch(function (err) {
        kendo.ui.progress($(document.body), false);
        console.error(err);
        alert('Eroare la incarcare date.');
    });
}

function evrFetchEstimates(algo, customers) {
    return new Promise(function (resolve, reject) {
        var data = {
            models: [{
                eStart: evrStart, eEnd: evrEnd,
                customers: customers,
                intervalType: evrIntervalType,
                consumptionType: -1,
                algorithm: algo
            }]
        };
        customCall(data, 'readEstimatVsRealizat', function (response) {
            resolve({ type: 'estimate', algo: algo, data: response });
        }, false, false);
    });
}

function evrFetchActuals(customers) {
    return new Promise(function (resolve, reject) {
        var start = new Date(evrStart);
        var year  = start.getFullYear();
        var months = [];
        var cur = new Date(evrStart);
        var endD = new Date(evrEnd);
        while (cur <= endD) {
            var m = cur.getMonth() + 1;
            if (months.indexOf(m) < 0) months.push(m);
            cur.setMonth(cur.getMonth() + 1);
        }
        var data = {
            models: [{
                consumption_types: -1,
                customers: customers,
                pod: null,
                month: months,
                year: year
            }]
        };
        customCall(data, 'getFarData', function (response) {
            resolve({ type: 'realizat', algo: 'realizat', data: response });
        }, false, false);
    });
}

// ─── Grid rendering ───────────────────────────────────────────────────────────

function evrFillGrid(results) {
    // Realizat always first, then estimate algos in registry order
    results.sort(function (a, b) {
        if (a.algo === 'realizat') return -1;
        if (b.algo === 'realizat') return 1;
        var keys = Object.keys(evrAlgorithms);
        return keys.indexOf(a.algo) - keys.indexOf(b.algo);
    });

    var norm = evrNormalizeResults(results);
    var dates = norm.dates;
    var algos = norm.algos;
    var intervalCount = evrIntervalType === '15min' ? 96 : 24;

    evrNumDays = dates.length;

    // Always grab a fresh sheet reference and do a hard clear before rendering
    evrSheet = evrSpreadsheet.activeSheet();
    evrSheet.batch(function () {
        evrSheet.range('R1C1:R500C50').clear();
        // Reset all column widths to Kendo default before setting our own
        for (var ci = 0; ci < 50; ci++) evrSheet.columnWidth(ci, 64);
        evrCurrRow = 1;

        // Column widths
        evrSheet.columnWidth(0, 80);
        for (var i = 0; i < evrNumDays; i++) evrSheet.columnWidth(i + 1, 42);

        // ── Title ──
        var startD = new Date(evrStart);
        var endD   = new Date(evrEnd);
        var title  = 'Estimat vs Realizat  ▸  ' +
            startD.toLocaleDateString('ro-ro', { day: '2-digit', month: 'short', year: 'numeric' });
        if (evrStart !== evrEnd)
            title += ' — ' + endD.toLocaleDateString('ro-ro', { day: '2-digit', month: 'short', year: 'numeric' });

        evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + (evrNumDays + 1))
            .merge().value(title)
            .background('#1565C0').color('#FFFFFF').bold(true).textAlign('center').fontSize(11);
        evrSheet.rowHeight(evrCurrRow - 1, 20);
        evrCurrRow++;

        // ── Day headers: weekday row ──
        var wdRow  = [''];
        var dayRow = ['Interval'];
        for (var di = 0; di < evrNumDays; di++) {
            var dt = new Date(dates[di]);
            wdRow.push(dt.toLocaleDateString('ro-ro', { weekday: 'narrow' }));
            dayRow.push(dt.getDate());
        }
        evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + (evrNumDays + 1))
            .values([wdRow]).background('rgb(167,214,255)').color('black').bold(true).textAlign('center').fontSize(evrFontS);
        evrSheet.rowHeight(evrCurrRow - 1, 13);
        evrCurrRow++;
        evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + (evrNumDays + 1))
            .values([dayRow]).background('rgb(167,214,255)').color('black').bold(true).textAlign('center').fontSize(evrFontS);
        evrSheet.rowHeight(evrCurrRow - 1, 13);
        evrCurrRow++;

        // ── Hour groups with algorithm sub-rows ──
        // Realizat is rendered on the hour-header row itself (saves one row per hour).
        // Remaining algorithms get sub-rows. In diff mode, sub-rows show (algo - realizat).
        var realizatAlgo = algos.find(function (a) { return a.algo === 'realizat'; });
        var otherAlgos   = algos.filter(function (a) { return a.algo !== 'realizat'; });
        var showDiff     = evrShowDiff && realizatAlgo !== undefined;

        for (var h = 0; h < intervalCount; h++) {
            var hLabel;
            if (evrIntervalType === '15min') {
                var hh = Math.floor(h / 4), mm = (h % 4) * 15;
                hLabel = hh.toString().padStart(2, '0') + ':' + mm.toString().padStart(2, '0');
            } else {
                hLabel = h.toString().padStart(2, '0') + ':00';
            }

            // Hour header row — doubles as Realizat row when Realizat is selected
            if (realizatAlgo) {
                var rowVals = [hLabel + '  ' + realizatAlgo.label];
                for (var di2 = 0; di2 < evrNumDays; di2++) {
                    var v = (realizatAlgo.values[h] !== undefined) ? realizatAlgo.values[h][di2] : null;
                    rowVals.push(v !== null ? v : '');
                }
                evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + (evrNumDays + 1))
                    .values([rowVals]).background(realizatAlgo.bgColor)
                    .textAlign('center').fontSize(evrFontS).format('0.000');
                evrSheet.range('R' + evrCurrRow + 'C1')
                    .color(realizatAlgo.color).bold(true).textAlign('left');
            } else {
                evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + (evrNumDays + 1))
                    .background('#BBDEFB');
                evrSheet.range('R' + evrCurrRow + 'C1')
                    .value(hLabel).color('#0D47A1').bold(true).fontSize(evrFontS).textAlign('left');
            }
            evrSheet.rowHeight(evrCurrRow - 1, evrRowH);
            evrCurrRow++;

            // Sub-rows for estimate algorithms
            otherAlgos.forEach(function (ad) {
                var rowVals = [ad.label];
                var pctVals = []; // parallel array of % values for coloring
                for (var di3 = 0; di3 < evrNumDays; di3++) {
                    var algoV = (ad.values[h] !== undefined) ? ad.values[h][di3] : null;
                    if (showDiff) {
                        var realV = (realizatAlgo.values[h] !== undefined) ? realizatAlgo.values[h][di3] : null;
                        var pct   = (algoV !== null && realV !== null && realV !== 0)
                                    ? (algoV - realV) / realV * 100
                                    : null;
                        rowVals.push(pct !== null ? pct : '');
                        pctVals.push(pct);
                    } else {
                        rowVals.push(algoV !== null ? algoV : '');
                        pctVals.push(null);
                    }
                }
                var fmt = showDiff ? '0.00"%"' : '0.000';
                evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + (evrNumDays + 1))
                    .values([rowVals]).textAlign('center').fontSize(evrFontS).format(fmt);
                evrSheet.range('R' + evrCurrRow + 'C1')
                    .background(ad.bgColor).color(ad.color).bold(true).textAlign('left');

                // Intensity-coded coloring: red = overestimate, green = underestimate
                if (showDiff) {
                    for (var dc = 0; dc < evrNumDays; dc++) {
                        var pv = pctVals[dc];
                        if (pv === null) continue;
                        evrSheet.range('R' + evrCurrRow + 'C' + (dc + 2))
                            .background(evrDiffColor(pv));
                    }
                }

                evrSheet.rowHeight(evrCurrRow - 1, evrRowH);
                evrCurrRow++;
            });
        }

        // ── Bottom section: always TOTAL (absolute sums) ──
        evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + (evrNumDays + 1))
            .background('rgb(167,214,255)');
        evrSheet.range('R' + evrCurrRow + 'C1')
            .value('TOTAL').color('#000000').bold(true).fontSize(10).textAlign('left');
        evrSheet.rowHeight(evrCurrRow - 1, evrHdrH);
        evrCurrRow++;

        algos.forEach(function (ad) {
            var row = [ad.label];
            for (var di5 = 0; di5 < evrNumDays; di5++) {
                var sum = 0, has = false;
                for (var ht = 0; ht < intervalCount; ht++) {
                    var vt = (ad.values[ht] !== undefined) ? ad.values[ht][di5] : null;
                    if (vt !== null) { sum += vt; has = true; }
                }
                row.push(has ? sum : '');
            }
            evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + (evrNumDays + 1))
                .values([row]).background('rgb(220,237,200)')
                .textAlign('center').fontSize(10).bold(true).format('0.000');
            evrSheet.range('R' + evrCurrRow + 'C1')
                .background(ad.bgColor).color(ad.color).bold(true).textAlign('left');
            evrSheet.rowHeight(evrCurrRow - 1, evrHdrH);
            evrCurrRow++;
        });

        // Scroll to top-left
        evrSheet.select('A1');
    });

    try { $(".k-spreadsheet-scroller").scrollTop(0).scrollLeft(0); } catch(e) {}
}

// ─── Data normalization ───────────────────────────────────────────────────────

function evrNormalizeResults(results) {
    var intervalCount = evrIntervalType === '15min' ? 96 : 24;

    // Build ordered date list for the selected range
    var dates = [];
    var cur  = new Date(evrStart);
    var endD = new Date(evrEnd);
    while (cur <= endD) {
        dates.push(kendo.toString(cur, 'yyyy-MM-dd'));
        cur.setDate(cur.getDate() + 1);
    }

    var algos = [];

    results.forEach(function (result) {
        var algoDef = evrAlgorithms[result.algo];
        if (!algoDef) return;

        // values[h][dateIndex] = number | null
        var values = {};
        for (var h = 0; h < intervalCount; h++)
            values[h] = new Array(dates.length).fill(null);

        if (result.type === 'realizat' && result.data) {
            Object.keys(result.data).forEach(function (mKey) {
                var month = parseInt(mKey);
                (result.data[mKey] || []).forEach(function (row) {
                    var h = parseInt(row.hour);
                    if (h >= intervalCount) return;
                    var d = parseInt(row.day);
                    var fea = parseFloat(row.far_ea || 0);
                    for (var di = 0; di < dates.length; di++) {
                        var dt = new Date(dates[di]);
                        if (dt.getDate() === d && dt.getMonth() + 1 === month) {
                            values[h][di] = (values[h][di] === null ? 0 : values[h][di]) + fea;
                            break;
                        }
                    }
                });
            });
        } else if (result.type === 'estimate' && result.data && result.data.data) {
            result.data.data.forEach(function (row) {
                if (!row.forecast_datetime) return;
                var dtStr = row.forecast_datetime.substring(0, 10);
                var di = dates.indexOf(dtStr);
                if (di < 0) return;
                var h;
                if (evrIntervalType === '15min') {
                    var hh = parseInt(row.forecast_datetime.substring(11, 13));
                    var mm = parseInt(row.forecast_datetime.substring(14, 16));
                    h = hh * 4 + Math.floor(mm / 15);
                } else {
                    h = parseInt(row.forecast_datetime.substring(11, 13));
                }
                if (h >= intervalCount) return;
                var fea = parseFloat(row.forecast_ea || 0);
                values[h][di] = (values[h][di] === null ? 0 : values[h][di]) + fea;
            });
        }

        // Light background tint from the algo color
        var hex = algoDef.color.replace('#', '');
        var r = parseInt(hex.substring(0, 2), 16);
        var g = parseInt(hex.substring(2, 4), 16);
        var b = parseInt(hex.substring(4, 6), 16);
        var bgColor = 'rgb(' +
            Math.round(r + (255 - r) * 0.82) + ',' +
            Math.round(g + (255 - g) * 0.82) + ',' +
            Math.round(b + (255 - b) * 0.82) + ')';

        algos.push({
            algo:    result.algo,
            label:   algoDef.label,
            color:   algoDef.color,
            bgColor: bgColor,
            values:  values
        });
    });

    return { dates: dates, algos: algos };
}

// ─── Diff color helper ────────────────────────────────────────────────────────
// Returns a red/green shade with intensity proportional to |pct|.
// Cap at ±50% for full saturation. Positive = overestimate (red), negative = underestimate (green).
function evrDiffColor(pct) {
    var intensity = Math.min(Math.abs(pct) / 50, 1); // 0 → white, 1 → full color
    var light = Math.round(255 - intensity * 160);
    if (pct > 0) return 'rgb(255,' + light + ',' + light + ')';
    return 'rgb(' + light + ',255,' + light + ')';
}

// ─── Estimation trigger ───────────────────────────────────────────────────────

function evrEstimate() {
    if (!evrStart || !evrEnd) { kendo.alert('Selectati o perioada.'); return; }

    var customers = [];
    var ms = $('#evrCustomers').data('kendoMultiSelect');
    if (ms) customers = ms.value().map(Number).filter(Boolean);

    var btn = $('#evr_btn_estimate');
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Estimeaza...');

    var data = { models: [{
        customers:   customers,
        start:       evrStart,
        end:         evrEnd,
        customerType: ''
    }]};

    customCall(data, 'estimate', function(response) {
        btn.prop('disabled', false).html('<i class="fa fa-play"></i> Estimeaza');
        if (response) kendo.alert('<pre style="font-size:0.85em">' + response + '</pre>');
        evrLoad();
    }, true, false);
}

// ─── View mode toggle ─────────────────────────────────────────────────────────

function evrSetViewMode(mode) {
    evrViewMode = mode;
    $('#evr_view_mode button').each(function () {
        var m = $(this).data('mode');
        $(this).toggleClass('btn-primary', m === mode)
               .toggleClass('btn-outline-primary', m !== mode);
    });
}

// ─── POD view ─────────────────────────────────────────────────────────────────

function evrLoadPod() {
    kendo.ui.progress($(document.body), true);
    var customers = $("#evrCustomers").data("kendoMultiSelect").value();

    var algoKeys = evrSelectedAlgos.filter(function (k) { return k !== 'realizat'; });
    var promises = [];

    algoKeys.forEach(function (algo) { promises.push(evrFetchPodEstimates(algo, customers)); });
    if (evrSelectedAlgos.indexOf('realizat') >= 0) { promises.push(evrFetchPodActuals(customers)); }

    Promise.all(promises).then(function (results) {
        kendo.ui.progress($(document.body), false);
        evrLastResults = results;
        evrFillGridPod(results);
        evrFillChart();
        if ($('#evr-stats-pane').hasClass('active')) evrFillStats();
    }).catch(function (err) {
        kendo.ui.progress($(document.body), false);
        console.error(err);
        alert('Eroare la incarcare date POD.');
    });
}

function evrFetchPodEstimates(algo, customers) {
    return new Promise(function (resolve, reject) {
        var data = { models: [{ eStart: evrStart, eEnd: evrEnd, customers: customers, intervalType: evrIntervalType, algorithm: algo }] };
        customCall(data, 'readPodEstimatVsRealizat', function (response) {
            resolve({ type: 'estimate', algo: algo, data: response });
        }, false, false);
    });
}

function evrFetchPodActuals(customers) {
    return new Promise(function (resolve, reject) {
        var start = new Date(evrStart);
        var year  = start.getFullYear();
        var months = [];
        var cur = new Date(evrStart), endD = new Date(evrEnd);
        while (cur <= endD) {
            var m = cur.getMonth() + 1;
            if (months.indexOf(m) < 0) months.push(m);
            cur.setMonth(cur.getMonth() + 1);
        }
        var data = { models: [{ customers: customers, month: months, year: year }] };
        customCall(data, 'getFarDataByPod', function (response) {
            resolve({ type: 'realizat', algo: 'realizat', data: response });
        }, false, false);
    });
}

// Build normalized structure:
// pods: sorted list of pod names
// byPod[pod][algo] = { values[h][di] = number|null }
function evrNormalizePodResults(results) {
    var intervalCount = evrIntervalType === '15min' ? 96 : 24;
    var dates = [];
    var cur = new Date(evrStart), endD = new Date(evrEnd);
    while (cur <= endD) {
        dates.push(kendo.toString(cur, 'yyyy-MM-dd'));
        cur.setDate(cur.getDate() + 1);
    }

    // Collect all pods across all results
    var podSet = {};

    results.forEach(function (result) {
        if (result.type === 'estimate' && result.data && result.data.pods) {
            result.data.pods.forEach(function (p) { podSet[p] = true; });
        } else if (result.type === 'realizat' && result.data) {
            Object.keys(result.data).forEach(function (p) { podSet[p] = true; });
        }
    });

    var pods = Object.keys(podSet).sort();
    var byPod = {};

    pods.forEach(function (pod) {
        byPod[pod] = {};
        evrSelectedAlgos.forEach(function (algo) {
            var vals = {};
            for (var h = 0; h < intervalCount; h++) vals[h] = new Array(dates.length).fill(null);
            byPod[pod][algo] = vals;
        });
    });

    results.forEach(function (result) {
        var algo = result.algo;

        if (result.type === 'estimate' && result.data && result.data.data) {
            result.data.data.forEach(function (row) {
                var pod = row.pod !== undefined ? row.pod : '';
                if (!byPod[pod] || !byPod[pod][algo]) return;
                if (!row.forecast_datetime) return;
                var dtStr = row.forecast_datetime.substring(0, 10);
                var di = dates.indexOf(dtStr);
                if (di < 0) return;
                var h;
                if (evrIntervalType === '15min') {
                    var hh = parseInt(row.forecast_datetime.substring(11, 13));
                    var mm = parseInt(row.forecast_datetime.substring(14, 16));
                    h = hh * 4 + Math.floor(mm / 15);
                } else {
                    h = parseInt(row.forecast_datetime.substring(11, 13));
                }
                if (h >= intervalCount) return;
                var fea = parseFloat(row.forecast_ea || 0);
                byPod[pod][algo][h][di] = (byPod[pod][algo][h][di] === null ? 0 : byPod[pod][algo][h][di]) + fea;
            });
        } else if (result.type === 'realizat' && result.data) {
            Object.keys(result.data).forEach(function (pod) {
                if (!byPod[pod] || !byPod[pod]['realizat']) return;
                var monthData = result.data[pod];
                Object.keys(monthData).forEach(function (mKey) {
                    var month = parseInt(mKey);
                    (monthData[mKey] || []).forEach(function (row) {
                        var h = parseInt(row.hour);
                        if (h >= intervalCount) return;
                        var d = parseInt(row.day);
                        var fea = parseFloat(row.far_ea || 0);
                        for (var di = 0; di < dates.length; di++) {
                            var dt = new Date(dates[di]);
                            if (dt.getDate() === d && dt.getMonth() + 1 === month) {
                                byPod[pod]['realizat'][h][di] = (byPod[pod]['realizat'][h][di] === null ? 0 : byPod[pod]['realizat'][h][di]) + fea;
                                break;
                            }
                        }
                    });
                });
            });
        }
    });

    return { pods: pods, byPod: byPod, dates: dates };
}

function evrFillGridPod(results) {
    var norm = evrNormalizePodResults(results);
    var pods  = norm.pods;
    var byPod = norm.byPod;
    var dates = norm.dates;
    var intervalCount = evrIntervalType === '15min' ? 96 : 24;
    var nDays = dates.length;
    var nPods = pods.length;
    // Layout: col 0 = label, then for each day: nPods sub-columns (one per POD)
    // colIndex(day, pod) = 1 + di * nPods + pi
    var totalCols = 1 + nDays * nPods;
    var algoOrder = evrSelectedAlgos.filter(function (k) { return evrAlgorithms[k]; });

    evrSheet = evrSpreadsheet.activeSheet();
    evrSheet.batch(function () {
        evrSheet.range('R1C1:R2000C200').clear();
        for (var ci = 0; ci < 200; ci++) evrSheet.columnWidth(ci, 48);
        evrCurrRow = 1;
        evrSheet.columnWidth(0, 110);

        // ── Title ──
        var title = 'Estimat vs Realizat ▸ POD  ▸  ' + evrStart;
        if (evrStart !== evrEnd) title += ' — ' + evrEnd;
        evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + totalCols)
            .merge().value(title).background('#1565C0').color('#FFFFFF').bold(true).textAlign('center').fontSize(11);
        evrSheet.rowHeight(evrCurrRow - 1, 20); evrCurrRow++;

        // ── Day header row (each day spans nPods columns) ──
        var dayHdrRow = [''];
        for (var di = 0; di < nDays; di++) {
            var dt = new Date(dates[di]);
            var dayLabel = dt.toLocaleDateString('ro-ro', { weekday: 'short', day: '2-digit', month: 'short' });
            dayHdrRow.push(dayLabel);
            for (var pi = 1; pi < nPods; pi++) dayHdrRow.push('');
        }
        evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + totalCols)
            .values([dayHdrRow]).background('rgb(167,214,255)').color('black').bold(true).textAlign('center').fontSize(evrFontS);
        // Merge each day group
        for (var di2 = 0; di2 < nDays; di2++) {
            var c1 = 1 + di2 * nPods, c2 = c1 + nPods - 1;
            if (c2 > c1) evrSheet.range('R' + evrCurrRow + 'C' + (c1+1) + ':R' + evrCurrRow + 'C' + (c2+1)).merge();
        }
        evrSheet.rowHeight(evrCurrRow - 1, 14); evrCurrRow++;

        // ── POD sub-header row ──
        var podHdrRow = ['Interval'];
        for (var di3 = 0; di3 < nDays; di3++) {
            pods.forEach(function (pod) {
                podHdrRow.push(pod ? pod.slice(-8) : '(fara)');
            });
        }
        evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + totalCols)
            .values([podHdrRow]).background('rgb(200,230,255)').color('#37474F').bold(true).textAlign('center').fontSize(7);
        evrSheet.rowHeight(evrCurrRow - 1, 12); evrCurrRow++;

        // ── Interval rows: algorithms as sub-rows ──
        for (var h = 0; h < intervalCount; h++) {
            var hLabel;
            if (evrIntervalType === '15min') {
                var hh = Math.floor(h / 4), mm = (h % 4) * 15;
                hLabel = hh.toString().padStart(2,'0') + ':' + mm.toString().padStart(2,'0');
            } else {
                hLabel = h.toString().padStart(2,'0') + ':00';
            }

            algoOrder.forEach(function (algo) {
                var algoDef  = evrAlgorithms[algo];
                var isReal   = algo === 'realizat';
                var hex = algoDef.color.replace('#','');
                var r=parseInt(hex.substring(0,2),16), g=parseInt(hex.substring(2,4),16), b=parseInt(hex.substring(4,6),16);
                var bgColor  = 'rgb('+Math.round(r+(255-r)*0.82)+','+Math.round(g+(255-g)*0.82)+','+Math.round(b+(255-b)*0.82)+')';

                var rowVals = [(isReal ? hLabel + '  ' : '  ') + algoDef.label];
                var diffCols = [];

                for (var di4 = 0; di4 < nDays; di4++) {
                    pods.forEach(function (pod) {
                        var vals = (byPod[pod] && byPod[pod][algo]) ? byPod[pod][algo] : {};
                        var v = (vals[h] !== undefined) ? vals[h][di4] : null;

                        if (evrShowDiff && !isReal) {
                            var rvVals = (byPod[pod] && byPod[pod]['realizat']) ? byPod[pod]['realizat'] : {};
                            var rv = (rvVals[h] !== undefined) ? rvVals[h][di4] : null;
                            var pct = (v !== null && rv !== null && rv !== 0) ? (v - rv) / rv * 100 : null;
                            rowVals.push(pct !== null ? pct : '');
                            diffCols.push(pct);
                        } else {
                            rowVals.push(v !== null ? v : '');
                            diffCols.push(null);
                        }
                    });
                }

                var fmt = (evrShowDiff && !isReal) ? '0.00"%"' : '0.000';
                evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + totalCols)
                    .values([rowVals]).textAlign('center').fontSize(evrFontS).format(fmt);
                evrSheet.range('R' + evrCurrRow + 'C1')
                    .background(bgColor).color(algoDef.color).bold(isReal).textAlign('left');

                if (evrShowDiff && !isReal) {
                    for (var dc = 0; dc < diffCols.length; dc++) {
                        if (diffCols[dc] !== null)
                            evrSheet.range('R' + evrCurrRow + 'C' + (dc + 2)).background(evrDiffColor(diffCols[dc]));
                    }
                }
                evrSheet.rowHeight(evrCurrRow - 1, evrRowH);
                evrCurrRow++;
            });
        }

        // ── TOTAL section ──
        evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + totalCols).background('rgb(167,214,255)');
        evrSheet.range('R' + evrCurrRow + 'C1').value('TOTAL').color('black').bold(true).fontSize(10).textAlign('left');
        evrSheet.rowHeight(evrCurrRow - 1, evrHdrH); evrCurrRow++;

        algoOrder.forEach(function (algo) {
            var algoDef = evrAlgorithms[algo];
            var hex = algoDef.color.replace('#','');
            var r=parseInt(hex.substring(0,2),16), g=parseInt(hex.substring(2,4),16), b=parseInt(hex.substring(4,6),16);
            var bgColor = 'rgb('+Math.round(r+(255-r)*0.82)+','+Math.round(g+(255-g)*0.82)+','+Math.round(b+(255-b)*0.82)+')';
            var row = [algoDef.label];
            for (var di5 = 0; di5 < nDays; di5++) {
                pods.forEach(function (pod) {
                    var vals = (byPod[pod] && byPod[pod][algo]) ? byPod[pod][algo] : {};
                    var sum = 0, has = false;
                    for (var ht = 0; ht < intervalCount; ht++) {
                        var vt = (vals[ht] !== undefined) ? vals[ht][di5] : null;
                        if (vt !== null) { sum += vt; has = true; }
                    }
                    row.push(has ? sum : '');
                });
            }
            evrSheet.range('R' + evrCurrRow + 'C1:R' + evrCurrRow + 'C' + totalCols)
                .values([row]).background('rgb(220,237,200)').textAlign('center').fontSize(10).bold(true).format('0.000');
            evrSheet.range('R' + evrCurrRow + 'C1').background(bgColor).color(algoDef.color).bold(true).textAlign('left');
            evrSheet.rowHeight(evrCurrRow - 1, evrHdrH); evrCurrRow++;
        });

        evrSheet.select('A1');
    });

    try { $(".k-spreadsheet-scroller").scrollTop(0).scrollLeft(0); } catch(e) {}
}


// ─── Chart rendering (daily totals) ──────────────────────────────────────────

function evrFillChart() {
    if (!evrLastResults) return;

    var algoOrder = evrSelectedAlgos.filter(function (k) { return evrAlgorithms[k]; });

    var categories  = [];
    var seriesData  = [];
    var chartTitle  = '';

    if (evrViewMode === 'pod') {
        var norm = evrNormalizePodResults(evrLastResults);
        categories = norm.dates.map(function (d) {
            return new Date(d).toLocaleDateString('ro-ro', { weekday: 'short', day: '2-digit', month: 'short' });
        });

        // One series per (POD × algorithm), aggregated to daily totals
        norm.pods.forEach(function (pod) {
            algoOrder.forEach(function (algo) {
                var podLabel = pod ? pod.slice(-8) : '(fara pod)';
                var dailyTotals = [];
                for (var di = 0; di < norm.dates.length; di++) {
                    var total = 0, hasData = false;
                    var vals = (norm.byPod[pod] && norm.byPod[pod][algo]) ? norm.byPod[pod][algo] : {};
                    Object.keys(vals).forEach(function (h) {
                        if (vals[h][di] !== null) { total += vals[h][di]; hasData = true; }
                    });
                    dailyTotals.push(hasData ? Math.round(total * 1000) / 1000 : null);
                }
                seriesData.push({
                    name:  podLabel + ' / ' + evrAlgorithms[algo].label,
                    data:  dailyTotals,
                    color: evrAlgorithms[algo].color,
                    type:  'column'
                });
            });
        });

        chartTitle = 'Totaluri zilnice — comparatie per POD (' + norm.pods.length + ' POD-uri)';
    } else {
        // Client view — daily totals aggregated across all PODs of selected customers
        var norm2 = evrNormalizeResults(evrLastResults);
        categories = norm2.dates.map(function (d) {
            return new Date(d).toLocaleDateString('ro-ro', { weekday: 'short', day: '2-digit', month: 'short' });
        });

        norm2.algos.forEach(function (ad) {
            var dailyTotals = [];
            for (var di = 0; di < norm2.dates.length; di++) {
                var total = 0, hasData = false;
                Object.keys(ad.values).forEach(function (h) {
                    if (ad.values[h][di] !== null) { total += ad.values[h][di]; hasData = true; }
                });
                dailyTotals.push(hasData ? Math.round(total * 1000) / 1000 : null);
            }
            seriesData.push({
                name:  ad.label,
                data:  dailyTotals,
                color: ad.color,
                type:  'column'
            });
        });

        chartTitle = 'Totaluri zilnice — comparatie pe client';
    }

    var existing = $("#evr_chart").data("kendoChart");
    if (existing) existing.destroy();

    $("#evr_chart").kendoChart({
        title:    { text: chartTitle, font: 'bold 13px sans-serif' },
        legend:   { position: 'bottom', labels: { font: '11px sans-serif' } },
        series:   seriesData,
        categoryAxis: { categories: categories, labels: { font: '11px sans-serif' } },
        valueAxis: {
            labels: { format: '{0:n3}', font: '11px sans-serif' },
            title:  { text: 'kWh / zi' }
        },
        tooltip: {
            visible: true,
            template: '#= series.name #<br/>#= category #: <b>#= kendo.toString(value, "n3") #</b> kWh'
        },
        seriesDefaults: { gap: 0.3, spacing: 0.1 }
    });
}

// Re-render chart when tab is shown (Kendo needs container visible to size correctly)
$(document).on('shown.bs.tab', '#evr-chart-tab', function () {
    if (evrLastResults) evrFillChart();
});


// ─── Statistics tab ──────────────────────────────────────────────────────────

// Builds flat per-interval pairs {y_true, y_pred} per (pod, algo, hour, day)
// from already-normalized POD results.
function evrBuildPairs(norm) {
    var algoOrder = evrSelectedAlgos.filter(function (k) { return evrAlgorithms[k] && k !== 'realizat'; });
    var pairs = { byAlgo: {}, byAlgoPod: {}, byAlgoHour: {}, byAlgoDay: {} };

    algoOrder.forEach(function (algo) {
        pairs.byAlgo[algo]     = [];
        pairs.byAlgoPod[algo]  = {};
        pairs.byAlgoHour[algo] = {};
        pairs.byAlgoDay[algo]  = {};
    });

    var intervalCount = evrIntervalType === '15min' ? 96 : 24;

    norm.pods.forEach(function (pod) {
        var realVals = (norm.byPod[pod] && norm.byPod[pod]['realizat']) ? norm.byPod[pod]['realizat'] : null;
        if (!realVals) return;

        algoOrder.forEach(function (algo) {
            var estVals = (norm.byPod[pod] && norm.byPod[pod][algo]) ? norm.byPod[pod][algo] : null;
            if (!estVals) return;

            if (!pairs.byAlgoPod[algo][pod]) pairs.byAlgoPod[algo][pod] = [];

            for (var h = 0; h < intervalCount; h++) {
                var hourBucket = evrIntervalType === '15min' ? Math.floor(h / 4) : h;
                if (!pairs.byAlgoHour[algo][hourBucket]) pairs.byAlgoHour[algo][hourBucket] = [];

                for (var di = 0; di < norm.dates.length; di++) {
                    var yt = realVals[h] ? realVals[h][di] : null;
                    var yp = estVals[h]  ? estVals[h][di]  : null;
                    if (yt === null || yp === null) continue;

                    var pair = { yt: yt, yp: yp };
                    pairs.byAlgo[algo].push(pair);
                    pairs.byAlgoPod[algo][pod].push(pair);
                    pairs.byAlgoHour[algo][hourBucket].push(pair);

                    if (!pairs.byAlgoDay[algo][di]) pairs.byAlgoDay[algo][di] = [];
                    pairs.byAlgoDay[algo][di].push(pair);
                }
            }
        });
    });

    return pairs;
}

// Standard regression error metrics. Returns {n, mae, rmse, wape, bias_pct, r2, sum_true, sum_pred}.
function evrMetrics(pairs) {
    var n = pairs.length;
    if (n === 0) return null;

    var sumAbsErr = 0, sumSqErr = 0, sumAbsTrue = 0, sumTrue = 0, sumPred = 0;
    var sumSqDevTrue = 0, meanTrue;

    for (var i = 0; i < n; i++) { sumTrue += pairs[i].yt; sumPred += pairs[i].yp; }
    meanTrue = sumTrue / n;

    for (var j = 0; j < n; j++) {
        var err = pairs[j].yp - pairs[j].yt;
        sumAbsErr   += Math.abs(err);
        sumSqErr    += err * err;
        sumAbsTrue  += Math.abs(pairs[j].yt);
        sumSqDevTrue += (pairs[j].yt - meanTrue) * (pairs[j].yt - meanTrue);
    }

    var ssRes = sumSqErr;
    var ssTot = sumSqDevTrue;
    var r2    = ssTot > 0 ? (1 - ssRes / ssTot) : null;

    return {
        n:        n,
        mae:      sumAbsErr / n,
        rmse:     Math.sqrt(sumSqErr / n),
        wape:     sumAbsTrue > 0 ? (sumAbsErr / sumAbsTrue * 100) : null,
        bias_pct: sumTrue > 0 ? ((sumPred - sumTrue) / sumTrue * 100) : null,
        r2:       r2,
        sum_true: sumTrue,
        sum_pred: sumPred
    };
}

function evrFmt(v, decimals, suffix) {
    if (v === null || v === undefined || isNaN(v)) return '<span class="text-muted">—</span>';
    var s = kendo.toString(v, 'n' + (decimals === undefined ? 3 : decimals));
    return s + (suffix || '');
}

// Returns HTML for a "winner" badge across any number of algos.
// `values` and `algoKeys` are parallel arrays. `lowerIsBetter` controls direction;
// `useAbs` => compare absolute values (used for bias_pct).
function evrWinnerBadge(values, algoKeys, lowerIsBetter, useAbs) {
    var bestIdx = -1, bestVal = null;
    for (var i = 0; i < values.length; i++) {
        if (values[i] === null || values[i] === undefined || isNaN(values[i])) continue;
        var v = useAbs ? Math.abs(values[i]) : values[i];
        if (bestIdx < 0) { bestIdx = i; bestVal = v; continue; }
        if (lowerIsBetter ? (v < bestVal) : (v > bestVal)) { bestIdx = i; bestVal = v; }
    }
    if (bestIdx < 0) return '';

    // Detect ties (within epsilon)
    var tied = [];
    for (var j = 0; j < values.length; j++) {
        if (values[j] === null || values[j] === undefined || isNaN(values[j])) continue;
        var vj = useAbs ? Math.abs(values[j]) : values[j];
        if (Math.abs(vj - bestVal) < 1e-9) tied.push(algoKeys[j]);
    }
    if (tied.length > 1) return '<span class="badge bg-secondary">egal: ' + tied.join('/') + '</span>';
    return '<span class="badge bg-success">' + algoKeys[bestIdx] + '</span>';
}

function evrFillStats() {
    if (!evrLastResults) {
        $('#evr_stats_summary').html('<div class="alert alert-info">Apasa "Incarca" pentru a vedea statisticile.</div>');
        return;
    }

    // Stats need realizat to compare against — verify it was selected
    if (evrSelectedAlgos.indexOf('realizat') < 0) {
        $('#evr_stats_summary').html('<div class="alert alert-warning">Selecteaza si "Realizat" pentru a vedea statisticile.</div>');
        $('#evr_stats_perpod').empty();
        $('#evr_stats_hourly, #evr_stats_daily, #evr_stats_scatter').each(function () {
            var c = $(this).data('kendoChart'); if (c) c.destroy();
            $(this).empty();
        });
        return;
    }

    var norm  = evrNormalizePodResults(evrLastResults);
    var pairs = evrBuildPairs(norm);
    var algoOrder = evrSelectedAlgos.filter(function (k) { return evrAlgorithms[k] && k !== 'realizat'; });

    // ── Context block (copy-pastable) ──
    var ms = $('#evrCustomers').data('kendoMultiSelect');
    var custIds = ms ? ms.value() : [];
    var custLabels = [];
    if (ms && ms.dataSource) {
        var data = ms.dataSource.data();
        custIds.forEach(function (cid) {
            var match = data.find ? data.find(function (d) { return String(d.customer_id) === String(cid); }) : null;
            custLabels.push(match ? (match.customer_name + ' (#' + cid + ')') : ('#' + cid));
        });
    }
    var ctxLines = [];
    ctxLines.push('Perioada: ' + evrStart + ' — ' + evrEnd);
    ctxLines.push('Interval: ' + evrIntervalType + '  ▸  Vizualizare: ' + evrViewMode);
    ctxLines.push('Clienti (' + custIds.length + '): ' + (custLabels.length ? custLabels.join(', ') : '—'));
    ctxLines.push('POD-uri (' + norm.pods.length + '): ' + (norm.pods.length ? norm.pods.join(', ') : '—'));
    ctxLines.push('Algoritmi: ' + evrSelectedAlgos.join(', '));

    var ctxText = ctxLines.join('\n');
    var ctxHtml = '<div class="card"><div class="card-header py-1 d-flex justify-content-between align-items-center bg-light">' +
        '<span class="small fw-bold">Context (pentru copy-paste)</span>' +
        '<button type="button" class="btn btn-sm btn-outline-secondary" id="evr_stats_ctx_copy"><i class="fa fa-copy"></i> Copiaza</button>' +
        '</div><pre id="evr_stats_ctx_text" class="card-body p-2 mb-0 small" style="white-space:pre-wrap;font-family:monospace;font-size:11px">' +
        $('<div/>').text(ctxText).html() + '</pre></div>';
    $('#evr_stats_context').html(ctxHtml);
    $('#evr_stats_ctx_copy').off('click').on('click', function () {
        navigator.clipboard.writeText(ctxText).then(function () {
            var b = $('#evr_stats_ctx_copy');
            var orig = b.html();
            b.html('<i class="fa fa-check"></i> Copiat').addClass('btn-success').removeClass('btn-outline-secondary');
            setTimeout(function () { b.html(orig).removeClass('btn-success').addClass('btn-outline-secondary'); }, 1500);
        });
    });

    if (algoOrder.length === 0) {
        $('#evr_stats_summary').html('<div class="alert alert-warning">Selecteaza cel putin un algoritm (v1 / v2) pe langa Realizat.</div>');
        return;
    }

    // ── 1. "Castig potential — per-POD assignment vs algoritm unic" ──
    // For each POD, compute |bias| (absolute total error) per algorithm.
    // Then aggregate: sum(|gap|) using each algo as baseline vs best-per-POD selection.

    var algoTotals = {}; // sum |estimat - realizat| across all PODs if always this algo
    var bestTotal  = 0;  // sum |estimat - realizat| if best-per-POD selected
    var realTotal  = 0;
    var algoEstSum = {}; // sum estimat (signed) for bias
    var bestEstSum = 0;

    algoOrder.forEach(function (algo) { algoTotals[algo] = 0; algoEstSum[algo] = 0; });

    var bestCounts = {}; algoOrder.forEach(function (a) { bestCounts[a] = 0; });

    norm.pods.forEach(function (pod) {
        var podReal = 0, podEst = {};
        var realVals = (norm.byPod[pod] && norm.byPod[pod]['realizat']) ? norm.byPod[pod]['realizat'] : null;
        if (!realVals) return;
        for (var h in realVals) for (var di = 0; di < realVals[h].length; di++) {
            if (realVals[h][di] !== null) podReal += realVals[h][di];
        }
        if (podReal <= 0) return;

        var bestGap = Infinity, bestAlgoForPod = null, bestSignedEst = 0;
        algoOrder.forEach(function (algo) {
            var est = 0;
            var v = (norm.byPod[pod] && norm.byPod[pod][algo]) ? norm.byPod[pod][algo] : {};
            for (var h in v) for (var di = 0; di < v[h].length; di++) if (v[h][di] !== null) est += v[h][di];
            podEst[algo] = est;
            algoTotals[algo] += Math.abs(est - podReal);
            algoEstSum[algo] += est;
            var gap = Math.abs(est - podReal);
            if (gap < bestGap) { bestGap = gap; bestAlgoForPod = algo; bestSignedEst = est; }
        });
        bestTotal  += bestGap;
        bestEstSum += bestSignedEst;
        realTotal  += podReal;
        if (bestAlgoForPod) bestCounts[bestAlgoForPod] += 1;
    });

    var sumHtml = '<h5 class="mb-2">Castig potential — assignment per POD vs algoritm unic</h5>';
    sumHtml += '<div class="alert alert-info py-2 small mb-2" style="max-width:1100px">';
    sumHtml += '<b>Cum se citeste:</b> pentru fiecare POD, alegem algoritmul cu cel mai mic |gap| total (|estimat − realizat|) si insumam la nivel agregat. ';
    sumHtml += 'Comparam acest "best-per-POD" cu varianta in care folosim un singur algoritm pentru toate POD-urile. ';
    sumHtml += 'Reducere |gap| = cat de mult ne apropiem de totalul realizat pe portofoliul selectat.';
    sumHtml += '</div>';

    sumHtml += '<table class="table table-sm table-bordered table-striped" style="max-width:1100px"><thead class="table-light"><tr>';
    sumHtml += '<th>Strategie</th>';
    sumHtml += '<th class="text-end">Suma |gap| (kWh)</th>';
    sumHtml += '<th class="text-end">Bias agregat</th>';
    sumHtml += '<th class="text-end">Total estimat (kWh)</th>';
    sumHtml += '<th class="text-end">Reducere |gap| vs aceasta</th>';
    sumHtml += '</tr></thead><tbody>';

    algoOrder.forEach(function (algo) {
        if (algo === 'realizat') return;
        var totalGap = algoTotals[algo];
        var bias = realTotal > 0 ? (algoEstSum[algo] - realTotal) / realTotal * 100 : null;
        var reduction = totalGap > 0 ? (totalGap - bestTotal) / totalGap * 100 : 0;
        sumHtml += '<tr>';
        sumHtml += '<td>Doar <b style="color:' + evrAlgorithms[algo].color + '">' + evrAlgorithms[algo].label + '</b> pentru toate POD-urile</td>';
        sumHtml += '<td class="text-end">' + evrFmt(totalGap, 3) + '</td>';
        sumHtml += '<td class="text-end">' + evrFmt(bias, 1, '%') + '</td>';
        sumHtml += '<td class="text-end">' + evrFmt(algoEstSum[algo], 3) + '</td>';
        sumHtml += '<td class="text-end"><b class="' + (reduction > 0 ? 'text-success' : 'text-muted') + '">' + evrFmt(reduction, 1, '%') + '</b></td>';
        sumHtml += '</tr>';
    });

    var bestBias = realTotal > 0 ? (bestEstSum - realTotal) / realTotal * 100 : null;
    sumHtml += '<tr class="table-success"><td><b>Best-per-POD (assignment optim)</b></td>';
    sumHtml += '<td class="text-end"><b>' + evrFmt(bestTotal, 3) + '</b></td>';
    sumHtml += '<td class="text-end"><b>' + evrFmt(bestBias, 1, '%') + '</b></td>';
    sumHtml += '<td class="text-end"><b>' + evrFmt(bestEstSum, 3) + '</b></td>';
    sumHtml += '<td class="text-end">—</td></tr>';

    sumHtml += '<tr class="table-light"><td colspan="5" class="small text-muted">';
    sumHtml += 'Total realizat: <b>' + evrFmt(realTotal, 3) + ' kWh</b> &nbsp;|&nbsp; ';
    sumHtml += 'POD-uri analizate: <b>' + norm.pods.filter(function (p) {
        var rv = (norm.byPod[p] && norm.byPod[p]['realizat']) ? norm.byPod[p]['realizat'] : null;
        if (!rv) return false;
        var t = 0; for (var h in rv) for (var di=0;di<rv[h].length;di++) if (rv[h][di]!==null) t+=rv[h][di];
        return t > 0;
    }).length + '</b> &nbsp;|&nbsp; ';
    sumHtml += 'Distributie best-per-POD: ';
    algoOrder.forEach(function (a) {
        if (a === 'realizat') return;
        sumHtml += '<span style="color:' + evrAlgorithms[a].color + '"><b>' + a + '</b>=' + bestCounts[a] + '</span> &nbsp;';
    });
    sumHtml += '</td></tr>';

    sumHtml += '</tbody></table>';
    $('#evr_stats_summary').html(sumHtml);

    // ── 2. Per-POD breakdown ──
    var podHtml = '<h5 class="mb-2">Pe POD</h5>';
    podHtml +=
        '<div class="alert alert-info py-2 small mb-2" style="max-width:1100px">' +
        '<b>Cum se alege castigatorul:</b> compar <b>WAPE</b> (eroare absoluta totala / consum realizat) intre algoritmi; ' +
        'badge-ul verde indica algoritmul cu <b>WAPE mai mic</b>.<br>' +
        '<b>De ce WAPE</b>: penalizeaza orice abatere pe orice interval, indiferent daca supraestimeaza dimineata si subestimeaza seara. ' +
        'E robust la valori mici (spre deosebire de MAPE care exploda la consum aproape de zero) si reflecta fidel calitatea curbei pe parcursul zilei.<br>' +
        '<b>Atentie</b>: WAPE != Bias. Un algoritm cu <i>Bias mic</i> nimereste bine <i>totalul</i> (zilnic / lunar), dar poate avea zig-zag mare in interiorul zilei. ' +
        'Daca scopul tau primar e doar acuratetea totalului (ex: pentru facturare / contracte), ' +
        '<i>|Bias|</i> mai mic e mai relevant. Pentru calitate generala a curbei, WAPE.' +
        '</div>';
    podHtml += '<table class="table table-sm table-bordered table-striped" style="max-width:1100px"><thead class="table-light"><tr>';
    podHtml += '<th>POD</th><th>n</th><th>Total realizat</th>';
    algoOrder.forEach(function (algo) {
        podHtml += '<th class="text-center" colspan="3" style="background:' + evrAlgorithms[algo].color + '22">' + evrAlgorithms[algo].label + '</th>';
    });
    if (algoOrder.length >= 2) podHtml += '<th class="text-center">Castigator (WAPE)</th>';
    podHtml += '</tr><tr><th></th><th></th><th></th>';
    algoOrder.forEach(function () { podHtml += '<th class="small">WAPE</th><th class="small">Bias</th><th class="small">RMSE</th>'; });
    if (algoOrder.length >= 2) podHtml += '<th></th>';
    podHtml += '</tr></thead><tbody>';

    norm.pods.forEach(function (pod) {
        var label = pod ? pod : '(fara pod)';
        var metricsByAlgoForPod = {};
        algoOrder.forEach(function (algo) { metricsByAlgoForPod[algo] = evrMetrics(pairs.byAlgoPod[algo][pod] || []); });

        var firstMetrics = metricsByAlgoForPod[algoOrder[0]];
        if (!firstMetrics) return;

        podHtml += '<tr><td><code>' + label + '</code></td>';
        podHtml += '<td class="text-center">' + firstMetrics.n.toLocaleString() + '</td>';
        podHtml += '<td class="text-center">' + evrFmt(firstMetrics.sum_true, 3, ' kWh') + '</td>';

        algoOrder.forEach(function (algo) {
            var m = metricsByAlgoForPod[algo];
            podHtml += '<td class="text-center">' + evrFmt(m ? m.wape : null, 1, '%') + '</td>';
            podHtml += '<td class="text-center">' + evrFmt(m ? m.bias_pct : null, 1, '%') + '</td>';
            podHtml += '<td class="text-center">' + evrFmt(m ? m.rmse : null, 4, '') + '</td>';
        });

        if (algoOrder.length >= 2) {
            var wapes = algoOrder.map(function (a) { return metricsByAlgoForPod[a] ? metricsByAlgoForPod[a].wape : null; });
            podHtml += '<td class="text-center">' + evrWinnerBadge(wapes, algoOrder, true, false) + '</td>';
        }
        podHtml += '</tr>';
    });
    podHtml += '</tbody></table>';
    $('#evr_stats_perpod').html(podHtml);

    // ── 3. WAPE per ora (hour-of-day) ──
    var hourCategories = [];
    var hourSeries = algoOrder.map(function (algo) {
        return { name: evrAlgorithms[algo].label, color: evrAlgorithms[algo].color, data: [] };
    });
    for (var hh = 0; hh < 24; hh++) {
        hourCategories.push(hh.toString().padStart(2,'0') + ':00');
        algoOrder.forEach(function (algo, i) {
            var m = evrMetrics(pairs.byAlgoHour[algo][hh] || []);
            hourSeries[i].data.push(m ? m.wape : null);
        });
    }

    var oldHourly = $('#evr_stats_hourly').data('kendoChart'); if (oldHourly) oldHourly.destroy();
    $('#evr_stats_hourly').kendoChart({
        title:    { text: 'WAPE pe ora-din-zi (%)' , font: 'bold 12px sans-serif' },
        legend:   { position: 'bottom' },
        series:   hourSeries.map(function (s) { return { type: 'line', name: s.name, color: s.color, data: s.data, markers: { visible: true, size: 4 } }; }),
        categoryAxis: { categories: hourCategories, labels: { rotation: -45, font: '9px sans-serif' } },
        valueAxis: { labels: { format: '{0}%' }, title: { text: 'WAPE (%)' } },
        tooltip:  { visible: true, template: '#= series.name #<br/>#= category #: <b>#= kendo.toString(value, "n1") #%</b>' }
    });

    // ── 4. Total error per day (signed: pred - actual) ──
    var dayCategories = norm.dates.map(function (d) {
        return new Date(d).toLocaleDateString('ro-ro', { weekday: 'short', day: '2-digit', month: 'short' });
    });
    var daySeries = algoOrder.map(function (algo) {
        var data = [];
        for (var di = 0; di < norm.dates.length; di++) {
            var dp = pairs.byAlgoDay[algo][di] || [];
            var diff = 0;
            for (var k = 0; k < dp.length; k++) diff += (dp[k].yp - dp[k].yt);
            data.push(dp.length ? Math.round(diff * 1000) / 1000 : null);
        }
        return { type: 'column', name: evrAlgorithms[algo].label, color: evrAlgorithms[algo].color, data: data };
    });

    var oldDaily = $('#evr_stats_daily').data('kendoChart'); if (oldDaily) oldDaily.destroy();
    $('#evr_stats_daily').kendoChart({
        title:    { text: 'Eroare totala zilnica: Estimat − Realizat (kWh)', font: 'bold 12px sans-serif' },
        legend:   { position: 'bottom' },
        series:   daySeries,
        categoryAxis: { categories: dayCategories, labels: { rotation: -45, font: '9px sans-serif' } },
        valueAxis: { labels: { format: '{0:n3}' }, title: { text: 'kWh' }, plotBands: [{ from: -0.0001, to: 0.0001, color: '#000', opacity: 0.4 }] },
        tooltip:  { visible: true, template: '#= series.name #<br/>#= category #: <b>#= kendo.toString(value, "n3") # kWh</b>' }
    });

    // ── 5. Scatter: predicted vs actual (per-day totals per POD) ──
    var scatterSeries = algoOrder.map(function (algo) {
        var pts = [];
        norm.pods.forEach(function (pod) {
            for (var di = 0; di < norm.dates.length; di++) {
                var rt = 0, ep = 0, hasR = false, hasE = false;
                var rv = (norm.byPod[pod] && norm.byPod[pod]['realizat']) ? norm.byPod[pod]['realizat'] : {};
                var ev = (norm.byPod[pod] && norm.byPod[pod][algo])       ? norm.byPod[pod][algo]       : {};
                Object.keys(rv).forEach(function (h) { if (rv[h][di] !== null) { rt += rv[h][di]; hasR = true; } });
                Object.keys(ev).forEach(function (h) { if (ev[h][di] !== null) { ep += ev[h][di]; hasE = true; } });
                if (hasR && hasE) pts.push([rt, ep]);
            }
        });
        return { type: 'scatter', name: evrAlgorithms[algo].label, color: evrAlgorithms[algo].color, data: pts, markers: { size: 6 } };
    });

    // Determine axis bounds for the y=x reference line
    var maxV = 0;
    scatterSeries.forEach(function (s) { s.data.forEach(function (p) { if (p[0] > maxV) maxV = p[0]; if (p[1] > maxV) maxV = p[1]; }); });
    if (maxV > 0) {
        scatterSeries.push({
            type: 'scatterLine', name: 'y = x (estimat = realizat)', color: '#888',
            data: [[0, 0], [maxV, maxV]], markers: { visible: false }, dashType: 'dash'
        });
    }

    var oldScatter = $('#evr_stats_scatter').data('kendoChart'); if (oldScatter) oldScatter.destroy();
    $('#evr_stats_scatter').kendoChart({
        title:    { text: 'Estimat vs Realizat — totaluri zilnice per POD', font: 'bold 12px sans-serif' },
        legend:   { position: 'bottom' },
        series:   scatterSeries,
        xAxis:    { title: { text: 'Realizat (kWh / zi)' }, labels: { format: '{0:n3}' }, min: 0 },
        yAxis:    { title: { text: 'Estimat (kWh / zi)' },  labels: { format: '{0:n3}' }, min: 0 },
        tooltip:  { visible: true, template: '#= series.name #<br/>Realizat: <b>#= kendo.toString(value.x, "n3") #</b><br/>Estimat: <b>#= kendo.toString(value.y, "n3") #</b>' }
    });

    // ── 6. Glossary ──
    $('#evr_stats_glossary').html(
        '<h6 class="mt-3">Note metodologice</h6>' +
        '<ul>' +
        '<li><b>WAPE</b> (Weighted Absolute Percentage Error) — masura primara, robusta la intervale cu consum mic. Mai mic = mai bine.</li>' +
        '<li><b>MAE / RMSE</b> sunt exprimate in <i>kWh per interval</i> (15min sau 60min, in functie de selectie). RMSE penalizeaza puternic erorile mari individuale.</li>' +
        '<li><b>Bias</b> = (suma estimari − suma realizat) / suma realizat. Pozitiv = supraestimare sistemica. Castigatorul e cel cu |Bias| mai mic.</li>' +
        '<li><b>R²</b> — cat de bine urmareste estimarea variatia reala. 1.0 = corelatie perfecta, 0 = aleatoriu, negativ = mai prost decat o medie constanta.</li>' +
        '<li>Scatter-ul arata totaluri zilnice per POD. Cu cat punctele sunt mai aproape de linia y=x, cu atat estimarea e mai precisa.</li>' +
        '<li>Statisticile sunt calculate doar pe perechile <i>(realizat, estimat)</i> in care ambele valori sunt prezente.</li>' +
        '</ul>'
    );
}

$(document).on('shown.bs.tab', '#evr-stats-tab', function () {
    if (evrLastResults) evrFillStats();
});
