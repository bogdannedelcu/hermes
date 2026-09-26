#!/usr/bin/env python3
"""
SmartMeter (Retele Electrice, Kafka _lpo) -> ebs.

DOUA lucruri, in fiecare rulare:
 1) ARHIVA DURABILA: toate mesajele (orice energyType) din fereastra -> tabela `smartmeter_lpo`
    (raw sampleValues). Asa nu pierdem datele curbelor AGREGATE chiar daca decizia de import
    intarzie peste retentia Kafka (~6 sapt). Cand clientul decide, importam din arhiva.
 2) IMPORT 1:1: doar A1 pentru PODuri cu curba MASURATA 1:1 -> actual_readings_<luna>
    (kWh->MWh, freq 15/60, upsert idempotent).

Kafka: assign manual de la cel mai vechi offset, ZERO commit (offset productie group_ro24760429
NEATINS -> nu deranjam nimic, putem reciti oricand).

Uz: python3 sync_smartmeter.py [--env=PROD] [--from=2026-09-01] [--to=YYYY-MM-DD] [--dry] [--no-import]
"""
import os, sys, io, json, time, argparse, subprocess
from collections import defaultdict
from datetime import datetime, timedelta
import fastavro, pymysql
from confluent_kafka import Consumer, KafkaError, TopicPartition

BASE = '/var/www/ebsv2/sync'
ap = argparse.ArgumentParser()
ap.add_argument('--env', default='PROD')
ap.add_argument('--from', dest='dfrom', default='2026-09-01')
ap.add_argument('--to', dest='dto', default='2999-12-31')
ap.add_argument('--dry', action='store_true')
ap.add_argument('--no-import', action='store_true', help='doar arhiva, fara actual_readings')
a = ap.parse_args()

import atexit
LOGBUF = []
def log(m):
    line = time.strftime('%Y-%m-%d %H:%M:%S') + ' | ' + str(m)
    LOGBUF.append(line + '\n'); print(line, flush=True)

db = json.loads(subprocess.check_output(
    ['php8.2', '-r', f"echo json_encode((require '{BASE}/config.php')['db']);"]))
conn = pymysql.connect(host=db['host'], user=db['user'], password=db['pass'], database=db['name'],
                       charset='utf8mb4', autocommit=False)

# --- tabela arhiva durabila ---
with conn.cursor() as cur:
    cur.execute("""CREATE TABLE IF NOT EXISTS smartmeter_lpo (
        pod VARCHAR(40) NOT NULL,
        sample_date DATE NOT NULL,
        energy_type VARCHAR(12) NOT NULL,
        meter_serial VARCHAR(40) NOT NULL,
        sample_frequency VARCHAR(5) DEFAULT NULL,
        read_date DATETIME DEFAULT NULL,
        sample_values MEDIUMTEXT,
        captured_at DATETIME NOT NULL,
        PRIMARY KEY (pod, sample_date, energy_type, meter_serial),
        KEY k_date (sample_date), KEY k_type (energy_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4""")
conn.commit()

# --- jurnal job (sync_job_runs) — best-effort, nu strica jobul ---
_JOB = {'id': None}
def _job_begin():
    try:
        with conn.cursor() as c:
            c.execute("""CREATE TABLE IF NOT EXISTS sync_job_runs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, job VARCHAR(40) NOT NULL,
                started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL,
                status ENUM('running','ok','fail') NOT NULL DEFAULT 'running',
                summary VARCHAR(255) DEFAULT NULL, details MEDIUMTEXT,
                PRIMARY KEY (id), KEY k_job (job), KEY k_started (started_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4""")
            c.execute("INSERT INTO sync_job_runs (job, started_at, status) VALUES ('smartmeter', NOW(), 'running')")
            _JOB['id'] = c.lastrowid
        conn.commit()
    except Exception: pass
def _job_end():
    try:
        if not _JOB['id']: return
        parts = []
        for kw in ('ARHIVA', 'IMPORT 1:1', 'result'):
            for ln in reversed(LOGBUF):
                if kw in ln: parts.append(ln.split('| ', 1)[-1].strip()); break
        summary = ' | '.join(parts)[:255]
        status = 'fail' if any(('err' in l.lower() or 'traceback' in l.lower()) for l in LOGBUF) else 'ok'
        with conn.cursor() as c:
            c.execute("UPDATE sync_job_runs SET finished_at=NOW(), status=%s, summary=%s, details=%s WHERE id=%s",
                      (status, summary, ''.join(LOGBUF[-60:]), _JOB['id']))
        conn.commit()
    except Exception: pass
_job_begin(); atexit.register(_job_end)

# --- mapare POD -> (curve_id, customer_id) DOAR curbe masurate 1:1 ---
POD2CURVE = {}
with conn.cursor() as cur:
    cur.execute("""
      SELECT pod, curve_id, customer_id FROM (
        SELECT ac.curve_name AS pod, ac.curve_id, p.customer_id, 1 pref
          FROM actual_curves ac JOIN pods p ON p.pod_no=ac.curve_name WHERE ac.curve_type='masurata'
        UNION ALL
        SELECT acv.pod, acv.curve_id, p.customer_id, 2 pref
          FROM actual_curves_variance acv
          JOIN actual_curves ac ON ac.curve_id=acv.curve_id AND ac.curve_type='masurata'
          JOIN pods p ON p.pod_no=acv.pod
         WHERE acv.curve_id IN (SELECT curve_id FROM actual_curves_variance
                                GROUP BY curve_id HAVING COUNT(DISTINCT pod)=1)
      ) t ORDER BY pref DESC""")
    for pod, cid, custid in cur.fetchall():
        POD2CURVE[pod] = (int(cid), int(custid) if custid is not None else None)
log(f'mapare 1:1: {len(POD2CURVE)} PODuri | interval [{a.dfrom}..{a.dto}] env={a.env} {"(DRY)" if a.dry else ""}')

# --- Kafka ---
sec, ck = {}, None
for raw in open(os.path.expanduser('~/.smartmeter/kafka_credentiale.txt'), encoding='utf-8'):
    s = raw.rstrip('\n')
    if s.strip() in ('PRE-PROD', 'PROD'): ck = s.strip(); sec[ck] = {}; continue
    if ck and ':' in s:
        k, v = s.split(':', 1); sec[ck][k.strip()] = v.strip()
c = sec[a.env]
topic = [t.strip() for t in c['Topics'].split(';') if 'lpo' in t][0]
consumer = Consumer({'bootstrap.servers': c['Host'], 'security.protocol': 'SASL_SSL',
    'sasl.mechanisms': 'SCRAM-SHA-512', 'sasl.username': c['User'], 'sasl.password': c['Password'],
    'group.id': c['Consumer Group'].strip(), 'enable.auto.commit': False, 'socket.timeout.ms': 15000})
md = consumer.list_topics(topic, timeout=15)
assign = []
for p in md.topics[topic].partitions.keys():
    lo, hi = consumer.get_watermark_offsets(TopicPartition(topic, p), timeout=10)
    if hi > lo: assign.append(TopicPartition(topic, p, lo))
consumer.assign(assign)

# --- consuma tot: per (pod, zi, energyType, contor) pastreaza mesajul cu offset maxim ---
arch = {}   # key -> (offset, freq, read_date, sample_values)
n_read = n_win = 0; empty = 0
while empty < 3:
    m = consumer.poll(4.0)
    if m is None: empty += 1; continue
    if m.error():
        if m.error().code() == KafkaError._PARTITION_EOF: continue
        log(f'  kafka err: {m.error()}'); continue
    empty = 0; n_read += 1
    try:
        schema = json.loads(m.key().decode('utf-8').rsplit('||', 1)[0])
        r = fastavro.schemaless_reader(io.BytesIO(m.value()), schema)
    except Exception:
        continue
    day = (r.get('sampleDate') or '')[:10]
    if not (a.dfrom <= day <= a.dto): continue
    n_win += 1
    key = (r.get('supplyPointId'), day, r.get('energyType'), r.get('meterSerialNumber') or '')
    if key not in arch or m.offset() > arch[key][0]:
        arch[key] = (m.offset(), str(r.get('sampleFrequency')), r.get('readDate'), r.get('sampleValues') or '')
consumer.close()
log(f'citite={n_read} | in fereastra={n_win} | (pod,zi,tip,contor) unice={len(arch)}')

if a.dry:
    et = defaultdict(int)
    for (pod, day, etype, meter) in arch: et[etype] += 1
    log(f'  DRY arhiva: {len(arch)} inregistrari | pe tip={dict(et)}')
    a1_1to1 = sum(1 for (pod, day, etype, meter) in arch if etype == 'A1' and pod in POD2CURVE)
    log(f'  DRY import 1:1: {a1_1to1} (pod,zi,contor) A1 mapabile')
    log('=== dry done ==='); sys.exit(0)

# --- 1) ARHIVA: upsert tot in smartmeter_lpo ---
now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
arows = [(pod, day, etype, meter, freq, rd, sv, now)
         for (pod, day, etype, meter), (off, freq, rd, sv) in arch.items()]
with conn.cursor() as cur:
    sql = ("INSERT INTO smartmeter_lpo (pod,sample_date,energy_type,meter_serial,sample_frequency,read_date,sample_values,captured_at) "
           "VALUES (%s,%s,%s,%s,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE "
           "sample_frequency=VALUES(sample_frequency), read_date=VALUES(read_date), "
           "sample_values=VALUES(sample_values), captured_at=VALUES(captured_at)")
    for i in range(0, len(arows), 1000):
        cur.executemany(sql, arows[i:i+1000])
conn.commit()
log(f'ARHIVA smartmeter_lpo: {len(arows)} inregistrari upsertate')

if a.no_import:
    log('=== done (doar arhiva) ==='); sys.exit(0)

# --- 2) IMPORT 1:1: A1 mapate -> actual_readings ---
def fnum(x):
    try: return float(x)
    except: return 0.0

series = defaultdict(lambda: [0.0]*96)   # (pod, day) -> 96 MWh
for (pod, day, etype, meter), (off, freq, rd, sv) in arch.items():
    if etype != 'A1' or pod not in POD2CURVE: continue
    vals = sv.split('$')[1:1+96]
    if len(vals) < 96: vals += [''] * (96 - len(vals))
    acc = series[(pod, day)]
    if freq == '60':
        for h in range(24):
            v = fnum(vals[4*h]) / 4.0
            for j in range(4): acc[4*h+j] += v / 1000.0
    else:
        for k in range(96): acc[k] += fnum(vals[k]) / 1000.0

by_month = defaultdict(list)
for (pod, day), acc in series.items():
    cid, custid = POD2CURVE[pod]
    base = datetime.strptime(day, '%Y-%m-%d')
    for k in range(96):
        ts = (base + timedelta(minutes=15*k)).strftime('%Y-%m-%d %H:%M:%S')
        by_month[int(day[5:7])].append((ts, custid, cid, round(acc[k], 8)))

written = 0
with conn.cursor() as cur:
    for mo, rows in by_month.items():
        sql = (f"INSERT INTO actual_readings_{mo} (supplier_id,reading_datetime,customer_id,curve_id,actual_ea) "
               f"VALUES (1,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE actual_ea=VALUES(actual_ea)")
        for i in range(0, len(rows), 2000):
            cur.executemany(sql, rows[i:i+2000]); written += len(rows[i:i+2000])
conn.commit()
log(f'IMPORT 1:1 actual_readings: serii={len(series)} randuri={written}')
log('=== done ===')
