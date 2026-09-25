# API Distribuție Energie — curbe de sarcină (load profile)

**Bază:** `https://api-webfrz.distributie-energie.ro/`
**Tip:** exclusiv **GET** (read-only, nicio scriere).
**Auth:** header `X-Api-Key: <cheie>`. Identitatea furnizorului e determinată **exclusiv de cheie** — orice parametru de identificare trimis e ignorat; primești doar contoarele alocate societății tale.
**Expirare cheie:** **90 de zile**.
**⚠️ Nume parametri case-sensitive:** `afterMeter` (NU `aftermeter` — ignorat în tăcere).

---

## Constrângeri critice (a se respecta strict)

| Regulă | Detaliu |
|---|---|
| **Fereastră orară** | Serviciul răspunde doar **10:15 – 23:00**. În afara ei → **503** (corp gol) pe TOATE endpoint-urile, mai puțin `/health`. |
| **Rate limit** | **120 cereri/minut** per furnizor (fereastră fixă de 1 min). Cererea 121 → **429** imediat, fără coadă. Nu se aplică pe `/health`. |
| **Blocare auth** | După **10 cereri respinse (401)** de la același IP → **blocat 15 min**. Orice cerere nouă în interval **reînnoiește** blocarea. **La primul 401, OPREȘTE procesul** — nu reîncerca. |
| **Deblocare** | Cât timp blocarea e activă, cheia nu mai e verificată deloc. Aștepți expirarea celor 15 min de la ultima încercare. |
| **Paginare secvențială** | Nu prelucra pagini în paralel — cursorul paginii următoare vine doar din răspunsul curent. |
| **Max pe pagină** | `take` max **2000** (valori mai mari reduse silențios). |

---

## Endpoint-uri

### 1. `GET /health`
Verifică disponibilitatea serviciului. **Fără auth.** Disponibil permanent. Confirmă doar că serviciul rulează.
> Recomandat: apelează înainte de preluarea zilnică. Dacă nu răspunde → problemă de infrastructură (serviciu/rețea/firewall), nu de configurație.

### 2. `GET /api/meters`
Lista contoarelor alocate societății, cu datele de identificare. Necesită `X-Api-Key`.

### 3. `GET /api/loadprofile` — endpoint-ul principal
Curbele de sarcină pentru contoarele alocate. Necesită `X-Api-Key`.

**Parametri:**
| Param | Tip | Descriere |
|---|---|---|
| `take` | întreg | Nr. maxim de contoare pe pagină. Recomandat **2000**. Interval valid 1–2000. |
| `afterMeter` | întreg | Cursor de paginare. Prima cerere: **0**. Următoarele: valoarea din `nextCursor`. |

**Exemplu:** `GET /api/loadprofile?take=2000&afterMeter=0` + header `X-Api-Key`.

**Structura răspunsului (paginat):**
```json
{
  "codFurnizor": "COD_FU",
  "date": "yyyy-mm-dd",
  "take": 2000,
  "afterMeter": 0,
  "meters": [
    {
      "idMeter": 1,
      "pod": "100000000000000001",
      "devloc": "1000000001",
      "resolutionMinutes": 15,
      "readings": [
        { "timeStamp": "yyyy-mm-ddT00:15:00", "wi_1_8_0": 12.345, "we_2_8_0": 0.0 }
      ]
    }
  ],
  "meterCount": "nr_meter",
  "lastMeter": "nr_ultimul_meter",
  "nextCursor": "nt_urmatorul_cursor",
  "hasMore": true
}
```

**Câmpuri citire:**
- `wi_1_8_0` = energie activă **importată** (consum, OBIS 1.8.0)
- `we_2_8_0` = energie activă **exportată** (injecție/producție, OBIS 2.8.0)
- `resolutionMinutes` = 15 (curbă la sfert de oră)

**Algoritm de preluare (paginare cu cursor):**
1. Prima cerere cu `afterMeter=0`.
2. Procesează + salvează contoarele din `meters`.
3. Citește `nextCursor` din răspuns.
4. Cerere următoare cu `afterMeter = nextCursor`.
5. Repetă până când `hasMore` devine `false`.

> **`nextCursor` NU e număr de ordine** — e identificatorul ultimului contor din pagină, nu poziția lui. E normal să nu corespundă cu numărul de contoare preluate (identificatorii au discontinuități).

**⚠️ Răspuns în flux (streaming):** dacă apare o eroare după începerea transmisiei, statusul rămâne **200** și conexiunea se închide → **JSON incomplet**. Validează prezența câmpurilor finale `meterCount, lastMeter, nextCursor, hasMore`; în lipsa lor, tratează răspunsul ca eșuat și reia **aceeași** pagină (timeout de citire ≥ 5 min).

---

## Erori

**Structură:** `{ "error": "<mesaj>" }` (un singur câmp text; fără code/traceId/timestamp). `Content-Type: application/json; charset=utf-8`. **Nu se returnează 400.**

| HTTP | Situație | Mesaj | Reluare |
|---|---|---|---|
| **401** | Cheie lipsă/invalidă/dezactivată/expirată | `Unauthorized` | **NU reluați** (duce la blocare) |
| **429** | Blocare după 10 auth eșuate | `Too many failed attempts. Try again later.` | opriți cererile 15 min |
| **429** | Peste 120 cereri/min | `Rate limit exceeded. Try again later.` | așteptați ~60 s |
| **503** | În afara intervalului 10:15–23:00 | (corp gol, fără Retry-After) | reprogramați în interval |
| **500** | Eroare internă | `Internal error.` | backoff exponențial, 3–5 încercări |
| **404** | Cale inexistentă (cerere autentificată; cu cheie invalidă → 401, nu 404) | (corp gol) | eroare de integrare, nu reluați |
| **405** | Altă metodă decât GET (include `Allow: GET`) | (corp gol) | eroare integrare |
| **413/414/431** | Corp > 16KB / linie cerere > 4KB / antete > 16KB | — | eroare integrare |

**Reluări (rezumat):** 401 → nu relua (blocare); 429-blocare → 15 min; 429-limită → ~60s; 503 → reprogramează în interval; 500 → backoff 3–5; 404/405/413/414/431 → nu relua.

---

## Relevanță pentru noi
Acest API oferă **curbe de sarcină 15-min per POD** (import `wi_1_8_0` + export `we_2_8_0`) direct de la distribuitor — o **sursă alternativă/complementară** la importurile din fișiere (ENEL/DEER/Transilvania) și potențial la sync-ul de citiri către VoltApp. Read-only, paginat pe contor, cu fereastră orară 10:15–23:00 și limită 120 req/min.
