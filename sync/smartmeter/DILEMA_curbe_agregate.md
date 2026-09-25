# Curbe agregate vs. curbe individuale — decizie necesară

**Context:** Am obținut acces la platforma SmartMeter (Rețele Electrice, stream Kafka) care ne
livrează **curbe de sarcină REALE la 15 minute, per POD** (`supplyPointId`). Până acum, pentru o
parte din PODuri, ebsv2 **nu avea** măsurătoare individuală — folosea o **curbă agregată împărțită
pe cotă**. Acest document descrie situația și decizia de luat.

---

## 1. Cum stau datele azi în ebsv2

Un „POD" (punct de consum / contor) nu ține date direct — datele orare stau pe o **curbă**
(`curve_id`). O curbă măsurată poate fi:

- **1:1** — o curbă = un singur POD (măsurătoare individuală reală). → **754 curbe**
- **Agregată** — o curbă = **mai multe PODuri** la un loc, legate prin tabela de alocare
  `actual_curves_variance`. → **132 curbe măsurate agregate**

Pentru o curbă agregată, consumul fiecărui POD se obține azi prin **împărțire pe cotă**:

> consum POD (la fiecare 15 min) = forma curbei agregate × (consum_lunar_POD ÷ consum_lunar_total_grup)

Adică per POD e o **estimare** derivată din agregat — **nu** o măsurătoare individuală.

---

## 2. Cât ne afectează

| | număr |
|---|---|
| Curbe măsurate **1:1** (deja individuale) | 754 |
| Curbe măsurate **agregate** (>1 POD) | **132** |
| dintre ele, cu PODuri din feed-ul SmartMeter (`RO...`) | **105** |
| PODuri acoperite de aceste curbe agregate | **~733** |
| PODuri max pe o singură curbă agregată | 61 |

Feed-ul SmartMeter ne dă acum, pentru aceste ~733 PODuri, **valoarea reală a fiecăruia**, nu doar
suma grupului.

---

## 3. Exemplu concret — curba `1030`

Curba `30ZFHRME-RELMS-QB20705985JT` agregă **8 PODuri, toate ale aceluiași client**
(GKS SPECIAL ADVERTISING S.R.L., id 424):

```
RO001E109128809, RO001E109128810, RO001E109128821, RO001E109128832,
RO001E109128843, RO001E109128854, RO001E109128865, RO001E109131577
```

**Azi:** o singură curbă măsurată = suma celor 8; fiecare POD primește o felie estimată din ea.
**Cu feed-ul:** avem curba reală, separată, pentru fiecare din cele 8 puncte.

---

## 4. Dilema — două variante

### Varianta A — Păstrăm curbele agregate (fără schimbări)
Alimentăm curba agregată din feed: însumăm cei N PODuri din feed → scriem în curba agregată.
Structura rămâne neschimbată; per POD se face în continuare împărțirea pe cotă.

- ✅ Zero schimbări de model; rapoartele/prognoza merg identic.
- ❌ Reagreghezi apoi reîmparți pe cotă → **pierzi datele reale per POD** (deși le avem).
- ❌ Necesită ca **toate** PODurile grupului să fie în feed; dacă unele lipsesc, suma e incompletă.

### Varianta B — Creăm curbe individuale 1:1 (valorificăm datele reale)
Pentru fiecare POD din grup creăm o curbă `masurata` proprie (curve_name = POD) și scriem datele
reale ale lui. „Promovăm" PODurile din estimat-agregat în măsurat-individual.

- ✅ Precizie maximă — fiecare POD cu curba lui reală, la 15 min.
- ✅ Facturare / alocare / prognoză mult mai exacte per client.
- ❌ Schimbare de model: ~733 curbe noi + rescrierea modului de alocare pentru grupurile afectate.
- ❌ Trebuie decis ce facem cu istoricul (păstrăm agregatul vechi, migrăm, rulăm în paralel?).

---

## 5. Recomandare

- Pentru cele **754 curbe deja 1:1** → pornim importul real din feed **acum** (fără decizie
  necesară; doar umplem cu date mai bune).
- Pentru cele **132 agregate** → **decizie de business/tehnică cu clientul**:
  - dacă precizia per-POD contează pentru facturare/raportare → **Varianta B** (individualizare),
    eventual etapizat (întâi clienții mari / prosumatorii);
  - dacă se preferă stabilitatea modelului actual → **Varianta A** (alimentăm agregatele).

**Întrebări pentru client:**
1. Individualizăm PODurile agregate acum că avem date reale, sau păstrăm agregarea?
2. Dacă individualizăm — pentru toate cele 733 sau doar un subset (mari/prosumatori)?
3. Ce facem cu istoricul curbelor agregate (păstrare vs. migrare)?
4. Impactul asupra facturării și prognozei — cine validează?

---

*Notă tehnică: feed-ul confirmă ~150–270 PODuri raportează A1 (consum activ) pe zi, la freq 15 sau
60 min; datele merg înapoi ~5–6 săptămâni în topic. Import 1:1 deja în lucru.*
