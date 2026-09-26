# Curbe agregate vs. individuale — clarificare pentru echipă

**Subiect:** Am obținut acces la platforma SmartMeter (Rețele Electrice, stream Kafka) care ne
livrează **curbe de sarcină REALE la 15 minute, per POD**. O parte din PODuri erau reprezentate în
ebsv2 doar printr-o **curbă agregată împărțită pe cotă** (estimare), nu prin măsurătoare individuală.
Acest document lămurește situația și **decizia de luat**.

---

## 1. Concept: POD ≠ curbă

Un „POD" (punct de consum / contor) nu ține date direct — datele orare stau pe o **curbă**
(`curve_id`). O curbă măsurată poate fi:

- **1:1** — o curbă = un singur POD (măsurătoare individuală reală). → **754 curbe**
- **Agregată** — o curbă = **mai multe PODuri** la un loc (contor/grup de echilibrare comun),
  legate prin tabela `actual_curves_variance`. → **132 curbe măsurate agregate**

La o curbă agregată, consumul fiecărui POD se obține prin **împărțire pe cotă lunară**:

> consum POD (la 15 min) = forma curbei agregate × (consum_lunar_POD ÷ consum_lunar_total_grup)

Adică per POD e o **estimare**, nu o măsurătoare individuală.

---

## 2. Cifre

| | număr |
|---|---|
| Curbe măsurate **1:1** | 754 |
| Curbe măsurate **agregate** (>1 POD) | **132** |
| dintre ele, cu PODuri din feed-ul SmartMeter (`RO...`) | **105** |
| PODuri acoperite de curbele agregate | **~733** |
| PODuri max pe o singură curbă agregată | 61 |

---

## 2b. Câte PODuri au MAI MULTE curbe asociate

Un POD poate fi legat de mai multe curbe (în tabela `actual_curves_variance`):

| curbe distincte / POD | nr. PODuri |
|---|---|
| 1 curbă | 1247 |
| 2 curbe | 105 |
| 3 curbe | 22 |
| 4 curbe | 3 |
| 16 curbe | 1 |
| **Total cu >1 curbă** | **131** |

**De ce au mai multe curbe** (din cele 131):
- **94 PODuri — reasignare în timp:** în fiecare lună au **o singură** curbă, dar aceasta se
  schimbă de la o perioadă la alta (POD-ul a fost mutat de la un grup/curbă la altul). Pe orice lună
  dată, maparea e **neambiguă** — nu ridică probleme.
- **37 PODuri — 2 curbe în ACEEAȘI lună:** consumul lor e împărțit simultan pe 2 curbe. Acestea
  sunt cazurile delicate (un POD contribuie la 2 curbe agregate în același timp).

**Exemplu (reasignare în timp)** — `RO001E109186274`:
- curba `3062` din 2024-08 → 2025-05, apoi
- curba `3282` din 2025-07 → prezent.

> Notă tehnică: pentru cele **37 PODuri cu 2 curbe/lună**, trimiterea per-POD în VoltApp trebuie să
> **însumeze** contribuțiile ambelor curbe pe acea lună (altfel una o suprascrie pe cealaltă). E un
> caz de tratat separat la individualizare.

---

## 3. Exemplu concret — curba `1030`

`30ZFHRME-RELMS-QB20705985JT` agregă **8 PODuri, toate ale aceluiași client**
(GKS SPECIAL ADVERTISING S.R.L.):

```
RO001E109128809, RO001E109128810, RO001E109128821, RO001E109128832,
RO001E109128843, RO001E109128854, RO001E109128865, RO001E109131577
```

**Azi:** o curbă măsurată = suma celor 8; fiecare POD primește o felie estimată din ea.
**Cu feed-ul SmartMeter:** avem curba reală, separată, pentru fiecare din cele 8 puncte.

---

## 4. Ce am făcut deja (stare curentă)

1. **Feed SmartMeter conectat** (Kafka, read-only) și **arhivat durabil** zilnic în tabela
   `smartmeter_lpo` — TOATE PODurile (inclusiv cele agregate). Astfel **nu pierdem datele**
   nici dacă decizia întârzie peste retenția Kafka (~6 săptămâni).
2. **Import 1:1 real** din feed → `actual_readings` pentru curbele individuale (cron zilnic).
3. **Trimitere în VoltApp** (`sync_citiri`) actualizată: acoperă acum **522 PODuri** (față de ~424):
   - curbe **1:1** → trimise sub POD-ul real;
   - curbe **agregate** → trimise **împărțite pe cotă** (estimare), pentru lunile care au consum
     lunar încărcat (până în august; septembrie intră după încărcarea consumului lunar).

Deci în acest moment agregatele **ajung** în VoltApp, dar ca **estimare pe cotă**, nu ca măsurătoare
individuală reală.

---

## 5. Decizia — două variante

### Varianta A — Păstrăm curbele agregate (estimare pe cotă)
Rămâne modelul actual: o curbă agregată, împărțită pe PODuri după consumul lunar.

- ✅ Zero schimbări de model; rapoartele/prognoza/facturarea merg identic.
- ✅ Deja funcțional în VoltApp.
- ❌ Per POD rămâne **estimare**, deși avem datele reale în feed.
- ❌ Necesită ca toate PODurile grupului să fie în feed pentru o sumă corectă.

### Varianta B — Individualizăm (curbe 1:1 reale din feed)
Pentru fiecare POD din grup creăm o curbă `masurata` proprie și scriem datele reale ale lui.

- ✅ Precizie maximă — fiecare POD cu curba lui reală, la 15 min.
- ✅ Facturare / alocare / prognoză mult mai exacte per client.
- ❌ Schimbare de model: ~733 curbe noi + rescrierea alocării pentru grupurile afectate.
- ❌ De decis ce facem cu istoricul (păstrăm agregatul, migrăm, rulăm în paralel?).

---

## 6. Recomandare & întrebări pentru echipă

- Cele **754 curbe deja 1:1** → import real din feed, **fără decizie** (doar date mai bune).
- Cele **132 agregate** → **decizie de business/tehnică**:

**Întrebări:**
1. Individualizăm PODurile agregate acum că avem date reale, sau păstrăm agregarea pe cotă?
2. Dacă individualizăm — toate cele ~733 sau doar un subset (clienți mari / prosumatori)?
3. Ce facem cu istoricul curbelor agregate (păstrare vs. migrare vs. paralel)?
4. Impactul asupra **facturării** și **prognozei** — cine validează?
5. Până la decizie, e OK ca în VoltApp agregatele să apară ca **estimare pe cotă** (așa e acum)?

---

*Notă tehnică: feed-ul confirmă ~150–270 PODuri raportează consum activ (A1) pe zi, la freq 15 sau
60 min; date disponibile ~5–6 săptămâni în topic, dar arhivate durabil la noi. Fără presiune de timp
pe decizie — nu pierdem date.*
