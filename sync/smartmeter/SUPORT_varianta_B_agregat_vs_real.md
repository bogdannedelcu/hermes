# Suport decizie — Varianta B (individualizare) pe PODurile din SmartMeter

**Scop:** să evaluăm, pe date reale, cât de exact e modelul actual (curbă agregată **împărțită pe
cotă lunară**) față de **datele reale per POD** din feed-ul SmartMeter — pentru a decide dacă
individualizăm PODurile agregate acoperite de SmartMeter (**105 curbe / 658 PODuri**).

## Metoda (comparație curată, doar din SmartMeter)
Pentru fiecare grup agregat cu date reale la toți membrii:
- **AGREGAT real** = suma citirilor reale ale membrilor (ce ar „vedea" un contor de grup).
- **ESTIMAT** (modelul actual) = AGREGAT × **cota lunară** a POD-ului (coeficient din `actual_curves_variance`, aug 2026).
- **REAL** = citirea proprie a POD-ului din SmartMeter.
- Comparăm pe zi: totalul zilnic, diferența %, și **corelația de formă** intraday (1.00 = formă identică; ~0 = fără legătură).

---

## Exemplul 1 — grup ~50/50 · A2Z EFFECTIVE MANAGEMENT (curbă 1022, 2 PODuri)
Cote lunare: `0.508` / `0.492` (aproape egale).

| Zi | POD | Estimat (MWh) | Real (MWh) | Dif. zi | Corelație formă |
|---|---|---|---|---|---|
| 10 sep | …771048 | 0.502 | 0.416 | **−17%** | 0.95 |
| 10 sep | …346042 | 0.487 | 0.573 | **+18%** | 0.97 |
| 15 sep | …771048 | 0.371 | 0.291 | **−21%** | 0.94 |
| 15 sep | …346042 | 0.360 | 0.439 | **+22%** | 0.98 |
| 20 sep | …771048 | 0.098 | 0.047 | **−52%** | **0.26** |
| 20 sep | …346042 | 0.095 | 0.145 | **+53%** | 0.91 |

→ Deși cota e ~50/50, **repartiția reală zilnică variază enorm** (−52% … +53%). Un POD domină într-o
zi, celălalt în alta — cota fixă nu poate prinde asta.

---

## Exemplul 2 — grup cu un dominant · TRIBUNALUL TULCEA (curbă 3208, 3 PODuri)
Cote: `0.842` (dominant) / `0.090` / `0.068`.

| Zi | POD | cotă | Estimat | Real | Dif. zi |
|---|---|---|---|---|---|
| 15 sep | …097175 | 0.842 | 1.083 | 1.067 | −1.5% (ok) |
| 15 sep | …674434 | 0.090 | 0.115 | 0.139 | **+21%** |
| 20 sep | …654735 | 0.068 | 0.083 | 0.065 | **−22%** |

→ **PODul dominant e estimat bine** (dif <2%, formă ~1.00), dar **PODurile minoritare sunt greșite ±20%+**.

---

## Exemplul 3 — grup foarte dezechilibrat · FILARMONICA "GEORGE ENESCU" (curbă 3282, 2 PODuri)
Cote: `0.992` / `0.008` (unul minuscul).

| Zi | POD | cotă | Estimat | Real | Dif. zi | Corelație formă |
|---|---|---|---|---|---|---|
| 10 sep | …186285 | 0.992 | 0.109 | 0.109 | +0.6% | 1.00 |
| 10 sep | …186274 | 0.008 | 0.001 | 0.000 | −71% | **0.28** |
| 15 sep | …186274 | 0.008 | 0.000 | 0.007 | **+4815%** | **−0.17** |
| 20 sep | …186274 | 0.008 | 0.000 | 0.007 | **+2795%** | **−0.04** |

→ Pentru POD-ul mic, estimatul e **complet greșit** (erori de mii de %, corelație **negativă** = formă opusă).
Dominantul e perfect.

---

## Concluzii

1. **Estimatul pe cotă e bun DOAR pentru POD-ul dominant** al grupului.
2. **PODurile minoritare sunt grav mis-estimate** — volum ±20–50%+, iar forma intraday poate fi
   necorelată sau chiar opusă (corelație negativă).
3. **Cu cât grupul e mai dezechilibrat, cu atât PODurile mici sunt mai greșite** (până la erori de mii de %).
4. Eroarea contează la **facturare per client** și la **dezechilibru/decontare** (forma orară greșită).

## Recomandare
**Varianta B (individualizare) merită, limitată la PODurile din SmartMeter** (105 curbe / 658 PODuri):
avem deja datele reale per POD, arhivate zilnic. Beneficiul e mare mai ales pentru grupurile
dezechilibrate și PODurile minoritare.

**Propunere de etapizare (opțional):** începem cu grupurile cu cel mai mare impact (dezechilibrate /
clienți mari / prosumatori), unde estimatul e cel mai greșit, apoi restul.

*Sursă: `compara_agregat_vs_real.py` (agregat reconstruit din real; cote aug 2026; zile 10/15/20 sep 2026).*
