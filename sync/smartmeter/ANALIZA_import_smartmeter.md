# Analiză: import date SmartMeter (Rețele Electrice) în `actual_readings`

Document de proiectare (READ-ONLY). NU s-a făcut conexiune la broker, NU s-au instalat pachete, NU s-a scris în DB.
Sursă: platformă Kafka de streaming AMR / smart-meter a DSO „Rețele Electrice”.
Scop: import curbe de sarcină măsurate la 15 min (LPO) în `actual_readings_<lună>`, oglindind modelul din `sync_distributie.php`.

Data analizei: 2026-09-25.

---

## 1. Schema mesajului LPO (din AMR_SM_LPO_Documentation.pdf)

**Format:** mesaj **Avro** pe topicul Kafka `<cui>_lpo` (ex. `ro1234567_lpo`). Fiecare înregistrare Kafka are două chei:
- `KEY` = schema Avro (stringified JSON) — poate fi folosită ca schemă dinamică.
- `VALUE` = valorile Avro binare.

Namespace/record: `rer_lpo`. În exemplul de output, câmpurile apar ca union `["string","null"]` (deci nullable). Schema:

| # | Câmp | Tip | Semnificație | Exemplu |
|---|------|-----|--------------|---------|
| 1 | `readDate` | string | Data citirii, **rotunjită la nivel de zi**, TZ Europe/Bucharest | `2025-10-28 00:00:00.000` |
| 2 | `timestamp` | string | Timestamp execuție/procesare, TZ Europe/Bucharest | `2025-10-28 13:30:42.000` |
| 3 | `meterSerialNumber` | string | Serie contor | `ABC123456789` |
| 4 | **`supplyPointId`** | string | **Cod POD (Point Of Delivery)** — cheia de mapare | `RO123456789` |
| 5 | **`energyType`** | string | Tipul de energie măsurat (vezi mai jos) | `A1` |
| 6 | `sampleFrequency` | string | Granularitatea în minute (`15` → 96 puncte/zi) | `15` |
| 7 | `complete` | string | `true`/`false` — dacă ziua e completă (finalizată) | `true` |
| 8 | `sampleDate` | string | **Data+ora eșantioanelor (ziua măsurată), TZ Europe/Bucharest** | `2025-10-27 00:00:00.000` |
| 9 | **`sampleValues`** | string | Șir de eșantioane separate prin `$`, **fiecare = kWh pe interval** | `0.359960$0.239960$...` (96 valori) |
| 10 | `cui_furnizor` | string | CUI furnizor | `RO11111111` |

**`energyType` — valori posibile:**
- `A1`: Active Energy Import (A+) → **echivalentul OBIS 1.8.0 / `wi_1_8_0`** din importul distribuitorului. **ACESTA e câmpul de interes pentru `actual_ea`.**
- `A2`: Active Energy Export (A−) → export (echiv. OBIS 2.8.0).
- `R1` R+L, `R2` R−L, `R3` R+C, `R4` R−C, `R1_R2` (R1+R2), `R3_R4` (R3+R4) → energii reactive, **irelevante** pentru `actual_ea`.

**Puncte-cheie confirmate din documentație:**
- **Identificarea POD/contor:** POD = `supplyPointId`; contorul = `meterSerialNumber`. Maparea în EBS se face pe **POD** (`supplyPointId` → `actual_curves.curve_name` / `pods.pod_no`).
- **Timestamp + fus orar:** toate datele sunt în **ora locală România (Europe/Bucharest)**, deja convertite din sursă. `sampleDate` este ziua/începutul zilei măsurate. Documentul „What has changed” precizează explicit: formatul s-a schimbat din `2025-10-17T00:00:00.000+03:00` în `2025-10-17 00:00:00` (**ora locală România**). Aceasta se aliniază perfect cu `actual_readings`, care stochează **ora locală RO** (ca și `sync_distributie.php`, fără conversie de fus).
- **Interval start vs end:** eșantioanele sunt indexate secvențial de la începutul zilei (`sampleDate` 00:00). Eșantionul *k* (k=0..95) → interval care **începe** la `sampleDate + k*15min`. `actual_readings.reading_datetime` = **START interval**, deci mapare directă: `reading_datetime = sampleDate 00:00 + k*15min`.
- **Câmpul de energie activă import + UNITATE:** valoarea = `sampleValues[k]` pentru `energyType='A1'`, în **kWh** (doc: „the sample values are now in kWh for SmartMeters”, până la 6 zecimale). → conversie **/1000 pentru MWh**, exact ca la distribuitor.
- **Export:** disponibil separat ca mesaj cu `energyType='A2'` (kWh). Pentru curbele „masurata” actuale nu se importă (consum activ ≥ 0), dar există dacă va fi nevoie.
- **Rezoluție interval:** 15 min (`sampleFrequency=15`). **Întotdeauna 96 de intervale, indiferent de DST** (doc: „there will always be 96 intervals no matter DST (so no 92 or 100 intervals)”).
- **Cumulativ vs delta:** valorile sunt **delta pe interval** (energia consumată în acel interval de 15 min, în kWh), **NU index cumulativ**. Deci NU se face diferență între citiri consecutive — se folosesc direct.

**Diferențe față de implementarea anterioară (relevante):** s-au eliminat `dataValidation`, `rv`, `meterLastSampleDate`; `sampleValues` a devenit numeric (max 6 zecimale) în **kWh**; timestamp în ora locală RO; `readDate` rotunjit la zi; mereu 96 intervale.

---

## 2. Modelul de consum (streaming Kafka) și „peek” sigur

**Este un stream Kafka continuu**, nu request/response. Furnizorul consumă „consume-as-you-go”:
- Fiecare furnizor are **2 topicuri dedicate**: `<cui>_lpo` (curbe de sarcină) și `<cui>_dcs` (citiri zilnice). Pentru `actual_readings` relevant e **`_lpo`**.
- Se consumă cu un **consumer group dedicat** (`group_<cui>`). Offset-urile sunt commit-uite per consumer group; astfel producția reia de unde a rămas.
- Cluster: 4 brokeri; se folosește **bootstrap URL** unic:
  - **PROD:** `smdata.reteleelectrice.ro:9000` (brokeri `b1..b4-smdata...:9001-9004`)
  - **PRE-PROD:** `r-smdata.reteleelectrice.ro:9000` (brokeri `b1..b4-r-smdata...:9001-9004`)
- Certificat semnat de **Digicert Public CA** (trusted de librăriile standard — NU e nevoie de `SSL_VERIFYPEER=false` ca la distribuitor).

**⚠️ CAVEAT CRITIC — offset-urile de producție.** Există un **consumer group de producție dedicat** (`group_ro24760429`, conform credențialelor reale). Kafka avansează offset-urile **per consumer group**. Dacă un „peek” read-only folosește **același** group id și **commit-uiește** offset-urile, va avansa poziția producției → **producția pierde acele mesaje** (nu le mai vede). 

**Cum se face un peek SIGUR (fără a afecta producția):**
1. **Group id DIFERIT** — de ex. `peek_ro24760429_tmp` (nu `group_ro24760429`). Un group id nou are propriul offset izolat.
   **ȘI/SAU**
2. **Fără commit de offset** — `enable.auto.commit=false` și nu se apelează niciun commit manual.
   **ȘI**
3. Consum limitat: citește doar câteva mesaje și ieși; `auto.offset.reset=earliest` doar pentru grupul temporar (nu afectează grupul de producție).

Astfel, grupul de producție `group_ro24760429` rămâne la offset-ul lui, iar peek-ul citește o copie fără să commit-uiască nimic. **Comanda exactă e propusă la secțiunea 5 (NU a fost rulată).**

---

## 3. Mapare câmpuri LPO → coloane `actual_readings` + transformări

Ținta: `actual_readings_<lună>` cu coloanele `supplier_id, reading_datetime, customer_id, curve_id, actual_ea` (+ `year` GENERATED — **niciodată inserat**). Cheie unică `(supplier_id, reading_datetime, customer_id, curve_id)`.

| Coloană țintă | Sursă LPO | Transformare |
|---------------|-----------|--------------|
| `supplier_id` | (constantă) | `= 1` (Hermes Energy), la fel ca în `sync_distributie.php` |
| `reading_datetime` | `sampleDate` + index eșantion `k` | `DateTime(sampleDate 00:00 locală) + k*15min`, format `Y-m-d H:i:s`. **Fără conversie de fus** — sursa e deja ora locală RO, iar tabela stochează local. Interval **START**. |
| `curve_id` | `supplyPointId` (POD) | din `actual_curves` unde `curve_type='masurata'` și `curve_name = supplyPointId` |
| `customer_id` | `supplyPointId` (POD) | din `pods.customer_id` join pe `pod_no = supplyPointId` |
| `actual_ea` (MWh) | `sampleValues[k]` unde `energyType='A1'` | `max(0.0, kWh) / 1000.0`, formatat `%.8f`. Se sumează pe eventuale sub-contoare/mesaje multiple ale aceluiași POD (vezi mai jos). |

**Detalii de transformare:**
- **Unitate:** `sampleValues` e în **kWh** → `/1000` = MWh (decimal 20,8). Identic cu `wi_1_8_0/1000` din distribuitor.
- **Fus orar:** direct, fără conversie (sursa e Europe/Bucharest local, tabela stochează local). 96 intervale/zi garantat inclusiv în zilele de schimbare a orei (DST) — deci indexarea `k*15min` de la miezul nopții local e sigură.
- **Sumarizare per POD (echiv. „devloc” din distribuitor):** un POD poate avea mai multe contoare/mesaje. Se agregă prin **SUM pe (POD, reading_datetime)** doar pentru `energyType='A1'`, exact ca `SUM_devloc(wi_1_8_0)` din `sync_distributie.php`. (La distribuitor cheia era POD+timeStamp; aici e POD+sampleDate+k.)
- **Negative/export:** se filtrează pe `energyType='A1'` (import). `A2` (export) se ignoră pentru curbele „masurata” de consum. `max(0.0, kWh)` păstrează consumul activ ≥ 0 (defensiv, ca la distribuitor).
- **`complete`:** se recomandă import doar pentru `complete='true'` (ziua finalizată). Mesajele cu `complete='false'` sunt parțiale; dacă se importă, se vor rescrie ulterior (idempotent) la sosirea versiunii `true` — vezi secțiunea 6.
- **Parsare `sampleValues`:** `explode('$', sampleValues)` → array; se verifică `count == 96` (sau `== 1440/sampleFrequency`); se asociază fiecare index `k` cu timestamp-ul intervalului.

---

## 4. Verificare DB: potrivirea POD-urilor SmartMeter cu `pods` / `actual_curves`

Interogări read-only rulate pe `ebs`:

- **Curbe totale:** 886 `masurata`, 164 `sintetica`.
- **Curbe măsurate mapabile azi** (join `pods` pe `pod_no = curve_name`): **424**.
- **Formatul POD în exemplul SmartMeter:** `supplyPointId = RO123456789` (prefix `RO`, ~15 caractere) — ex. „RO123456789” din documentație.
- **Formate POD existente în `actual_curves` (masurata):**
  - `59...` 18 cifre — **349** (format distribuitor)
  - `VI...` 17 car. — 162
  - `30...` 26-32 car. — ~250
  - **`RO...` 15 car. — 70** ← formatul care se potrivește cu SmartMeter (`RO001E100093193` etc.)
  - `50.../51...` 8 car. — 52
- În `pods`, POD-urile `RO...` au forma `RO001E100093193` (15 car.), `RO001E109204499X` (16 car., cu sufix `X`).

**Concluzie mapare (RISC IMPORTANT):** formatul `RO...` din SmartMeter (`supplyPointId`) **se potrivește** cu POD-urile `RO...` din `pods`/`actual_curves` (există **70** curbe măsurate `RO`-prefixate). MAPAREA VA FUNCȚIONA pentru aceste POD-uri prin **exact același join** ca `sync_distributie.php`.
**ATENȚIE:** majoritatea curbelor măsurate actuale (349 de `59...` 18-cifre + altele) provin de la **importul distribuitorului cu alt format de POD**. Nu știm încă dacă SmartMeter livrează `supplyPointId` sub formă `RO...` pentru toate POD-urile noastre sau doar pentru un subset. Fără un mesaj live nu putem confirma acoperirea completă. → **De validat pe PRE-PROD**: câte `supplyPointId` distincte apar și câte se mapează în `actual_curves(masurata)`. Mecanismul de skip (POD nealocat → skip + log) din `sync_distributie.php` acoperă în siguranță POD-urile nemapate.

---

## 5. Client Kafka + parametri SASL/SCRAM (fără a afișa parola)

**Mecanism (din PDF-uri):** `security.protocol = SASL_SSL`, `sasl.mechanism = SCRAM-SHA-512` (NU 256). JAAS: `org.apache.kafka.common.security.scram.ScramLoginModule`. Certificat Digicert (trusted — fără workaround TLS).

**Niciun client Kafka nu este instalat** (verificat): `confluent_kafka` (Python) absent, `kafka-python` absent, `kcat`/`kafkacat` absent. PHP e **8.3** (nu 8.2), fără extensia `rdkafka`.

**Opțiuni de client (recomandare):**
1. **Python `confluent-kafka`** — RECOMANDAT. E și exemplul oficial din documentație (`pip install confluent-kafka`), suportă nativ SASL_SSL + SCRAM-SHA-512, decodare Avro (`fastavro`/`confluent-kafka[avro]`). Instalare: `pip install confluent-kafka fastavro` (într-un venv).
2. **`kcat` (kafkacat)** — bun pentru peek/diagnostic din shell. Instalare: `apt-get install kafkacat` (sau `kcat`). Suportă `-X security.protocol=SASL_SSL -X sasl.mechanism=SCRAM-SHA-512`.
3. **PHP `rdkafka`** — ar permite un `sync_smartmeter.php` „nativ” în stilul repo-ului, dar necesită `librdkafka` + `pecl install rdkafka` + decodare Avro manuală în PHP (mai fragil). Mai puțin recomandat pentru MVP.

**Forma parametrilor de conexiune** (valorile reale din `~/.smartmeter/kafka_credentiale.txt` — **NU se afișează aici**):
```
bootstrap.servers = <PROD: smdata.reteleelectrice.ro:9000 | PRE-PROD: r-smdata.reteleelectrice.ro:9000>
security.protocol = SASL_SSL
sasl.mechanism    = SCRAM-SHA-512
sasl.username     = user_<cui>            # din fișierul de credențiale
sasl.password     = <SECRET>              # din fișierul de credențiale; NU se loghează
group.id          = <group temporar pentru peek | group_<cui> pentru producție>
auto.offset.reset = earliest
enable.auto.commit= false                 # pentru peek
```
Topic relevant: `<cui>_lpo`. (Ex. generic din doc: `ro1234567_lpo`.)

**PEEK SIGUR — comandă propusă (NU a fost rulată):**
```bash
# citeste creds din fisierul securizat FARA a le afisa; group id TEMPORAR; fara commit; 3 mesaje
kcat -b r-smdata.reteleelectrice.ro:9000 \
     -X security.protocol=SASL_SSL \
     -X sasl.mechanism=SCRAM-SHA-512 \
     -X sasl.username="$(sed -n 's/^user=//p' ~/.smartmeter/kafka_credentiale.txt)" \
     -X sasl.password="$(sed -n 's/^pass=//p' ~/.smartmeter/kafka_credentiale.txt)" \
     -G peek_tmp_$(date +%s) \        # <-- group DIFERIT de group_ro24760429
     <cui>_lpo -c 3 -e -o beginning
# NOTA: -G in kcat commit-uieste offset pt grupul dat; folosind un grup NOU si UNIC (peek_tmp_...),
# grupul de productie group_ro24760429 NU e afectat. Alternativ, in Python: enable.auto.commit=false
# + assign() manual, fara commit, pentru zero risc de avans offset.
```
Recomandare mai sigură (zero commit): consumer Python cu `group.id` nou, `enable.auto.commit=false`, citește N mesaje, `close()` fără commit. Pentru diagnostic pur, pe **PRE-PROD** întâi.

---

## 6. Arhitectura propusă pentru `sync_smartmeter.php` (sau consumer mic)

Oglindește `sync_distributie.php`: **consumă LPO → agregă per POD → upsert în `actual_readings_<lună>`**. Diferența majoră: **streaming**, nu polling zilnic.

**Fluxul (recomandat: consumer Python + upsert, sau PHP+rdkafka):**
1. **Încarcă maparea POD→(curve_id, customer_id)** o singură dată la start (aceeași interogare ca la distribuitor):
   ```sql
   SELECT ac.curve_id, ac.curve_name AS pod, p.customer_id
   FROM actual_curves ac JOIN pods p ON p.pod_no = ac.curve_name
   WHERE ac.curve_type='masurata';
   ```
2. **Consumă** de pe `<cui>_lpo` cu `group_<cui>` (producție), `enable.auto.commit=false` (commit manual **doar după** scrierea reușită în DB — vezi idempotență).
3. **Decodează Avro** (schema din `KEY`, sau schema statică `rer_lpo`) → câmpuri.
4. **Filtrează** `energyType='A1'`; ideal doar `complete='true'` (sau importă și parțiale, se corectează la re-sosire).
5. **Parsează `sampleValues`** (`$`-split), verifică 96 intervale, construiește `reading_datetime = sampleDate + k*15min`.
6. **Agregă** `actual_ea += kWh/1000` pe cheia `(POD, reading_datetime)` (SUM pe sub-contoare). POD nemapate → skip + log (ca la distribuitor).
7. **Upsert pe partiția lunară** derivată din luna lui `reading_datetime`:
   ```sql
   INSERT INTO actual_readings_<luna> (supplier_id,reading_datetime,customer_id,curve_id,actual_ea)
   VALUES (...) ON DUPLICATE KEY UPDATE actual_ea=VALUES(actual_ea);
   ```
   (chunk 2000, exact ca `sync_distributie.php`; `year` NU se inserează — e GENERATED).
8. **Commit offset Kafka DOAR după** ce toate rândurile mesajului au fost scrise cu succes → livrare „at-least-once”; re-scrierile sunt sigure (idempotent).
9. **Log zilnic**: `sync/logs/smartmeter-YYYY-MM-DD.log` prin `logline()` din `lib.php`.

**Diferențe față de modelul polling al distribuitorului:**
- **Streaming vs pull zilnic:** distribuitorul se trage o dată/zi într-o fereastră (10:15-23:00) prin HTTP paginat; SmartMeter e un consumer Kafka **long-running** (sau rulat periodic care consumă tot backlog-ul disponibil de la ultimul offset). Nu există fereastră orară, nici limită de rată HTTP.
- **Managementul offset-ului:** la distribuitor nu există offset (se cere mereu „ziua precedentă”); aici **offset-ul Kafka per consumer group** este starea de progres. Commit doar după scriere reușită. NU refolosi grupul de producție pentru teste.
- **Idempotență:** identică — aceeași **cheie unică** `(supplier_id, reading_datetime, customer_id, curve_id)` + `ON DUPLICATE KEY UPDATE actual_ea` face ca re-rulările, replay-urile și corecțiile (mesaj `complete='false'` urmat de `true`) să convergă la valoarea finală.
- **`supplier_id=1`, kWh→MWh /1000, ora locală directă** — identice cu distribuitorul.
- **Model de execuție:** fie daemon (`systemd`/supervisor) care consumă continuu, fie cron scurt care golește backlog-ul și iese. Pentru MVP: cron la fiecare X minute cu `consume` până la EOF (`_PARTITION_EOF`) apoi commit + exit.

**De reținut privind `year` GENERATED STORED:** dacă un mesaj conține intervale din 2 ani (nu se întâmplă la nivel de zi), oricum partiționarea e pe **lună** din `reading_datetime`, deci un mesaj poate genera rânduri în luni diferite doar la granițe (nu e cazul, o zi = o lună).

---

## 7. Întrebări deschise / riscuri

1. **PROD vs PRE-PROD:** validarea și primul peek se fac pe **PRE-PROD** (`r-smdata...:9000`). Trebuie confirmat că datele de test există acolo și că credențialele funcționează pe ambele.
2. **Offset / replay:** cât backlog e reținut în topic (retention)? Dacă e finit (ex. 7 zile), un consumer nou pornit cu `earliest` prinde doar fereastra reținută — istoric mai vechi trebuie obținut altfel. De confirmat retention-ul topicului `_lpo`.
3. **Riscul offset producție:** orice test care folosește din greșeală `group_ro24760429` cu commit va face producția să piardă mesaje. **Politică fermă:** teste doar cu group id temporar + `enable.auto.commit=false`.
4. **Acoperirea POD-urilor:** cât din portofoliul nostru livrează SmartMeter și în ce format (`RO...` vs alt format)? Azi doar 70 curbe măsurate `RO`-prefixate se potrivesc evident cu exemplul `supplyPointId`. Restul (349 × `59...` etc.) pot proveni exclusiv de la distribuitor. Risc de dublă-sursă pentru același consum → de decis **sursa autoritativă per POD** (SmartMeter vs distribuitor) ca să nu se suprascrie reciproc pe aceeași cheie unică.
5. **Volum de mesaje:** un mesaj = 1 POD × 1 energyType × 1 zi (96 intervale). Cu ~mii de POD-uri × ~6 tipuri de energie × zilnic → volum notabil; consumer-ul trebuie să facă commit incremental și upsert în batch. De estimat throughput real pe PRE-PROD.
6. **Mesaje `complete='false'`:** politica de import al zilelor parțiale (import + corecție ulterioară idempotentă vs. așteptare până la `complete='true'`). Recomandat: **doar `true`** pentru simplitate, sau import + rescriere.
7. **Avro în PHP:** dacă se merge pe `sync_smartmeter.php` nativ, decodarea Avro în PHP e mai fragilă (necesită librărie Avro PHP sau parsare manuală). Un mic consumer Python (`confluent-kafka`+`fastavro`) care scrie direct în MariaDB e probabil calea cea mai robustă.
8. **Dependențe de instalat** (niciuna prezentă): Python `confluent-kafka` + `fastavro` (recomandat), SAU `kcat` pentru diagnostic, SAU `librdkafka`+`php-rdkafka` dacă se insistă pe PHP. PHP CLI e 8.3 (spec cerea php8.2 — de aliniat).
9. **Consumer group unic pe instanță:** dacă rulează în paralel 2 procese cu același `group_<cui>`, Kafka împarte partițiile — OK pentru scalare, dar atenție să nu ruleze simultan cu peek-uri pe același grup.

---

## Rezumat mapare (o linie)

`A1` din `<cui>_lpo`: `reading_datetime = sampleDate + k*15min` (local RO, start interval), `actual_ea = SUM_contoare(sampleValues[k]) / 1000` MWh, `curve_id/customer_id` din POD=`supplyPointId` (masurata), `supplier_id=1`, upsert idempotent pe cheia unică în `actual_readings_<lună>`.
