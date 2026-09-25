#!/usr/bin/env python3
"""
Peek READ-ONLY pe topicul _lpo (SmartMeter / Retele Electrice), FARA sa atinga
offset-ul de productie:
  - group id nou, temporar (NU group_ro24760429)
  - enable.auto.commit=false  + niciun commit()
  - citeste cateva mesaje si iese
Credentialele se citesc din ~/.smartmeter/kafka_credentiale.txt (nu se afiseaza).
Uz: python3 peek_lpo.py [PRE-PROD|PROD] [n_mesaje]
"""
import os, sys, io, json, time, binascii

ENV = (sys.argv[1] if len(sys.argv) > 1 else 'PRE-PROD').upper()
NMSG = int(sys.argv[2]) if len(sys.argv) > 2 else 5

# --- parseaza sectiunea de credentiale ceruta ---
creds_path = os.path.expanduser('~/.smartmeter/kafka_credentiale.txt')
sec, cur = {}, None
for raw in open(creds_path, encoding='utf-8'):
    line = raw.rstrip('\n')
    if line.strip() in ('PRE-PROD', 'PROD'):
        cur = line.strip(); sec[cur] = {}; continue
    if cur and ':' in line:
        k, v = line.split(':', 1)
        sec[cur][k.strip()] = v.strip()
if ENV not in sec:
    print('Mediu necunoscut:', ENV, '| disponibile:', list(sec)); sys.exit(1)
c = sec[ENV]
host = c['Host']
user = c['User']
pwd  = c['Password']
topic_lpo = [t.strip() for t in c['Topics'].split(';') if 'lpo' in t][0]
group = c.get('Consumer Group', '').strip()   # ACL permite DOAR grupul lor

print(f'== PEEK {ENV} == broker={host} topic={topic_lpo}')
print(f'   metoda=assign() manual (NU subscribe -> fara rebalance, nu deranjez productia)')
print(f'   group={group} | auto.commit=OFF, ZERO commit -> offset productie NEATINS')

from confluent_kafka import Consumer, KafkaError, TopicPartition

conf = {
    'bootstrap.servers': host,
    'security.protocol': 'SASL_SSL',
    'sasl.mechanisms': 'SCRAM-SHA-512',
    'sasl.username': user,
    'sasl.password': pwd,
    'group.id': group,
    'enable.auto.commit': False,
    'socket.timeout.ms': 15000,
}
consumer = Consumer(conf)

import fastavro

def decode(key, val):
    """Valoarea e Avro schemaless SIMPLU. Schema (writer) vine in CHEIE ca JSON,
    urmata de '||<nr>'. Decodam intreaga valoare cu ea."""
    if val is None:
        return ('null', None)
    try:
        schema = json.loads(key.rsplit('||', 1)[0])
        rec = fastavro.schemaless_reader(io.BytesIO(val), schema)
        return ('avro', rec)
    except Exception as e:
        return (f'avro decode err: {e}', None)

try:
    # metadata: partitiile topicului
    md = consumer.list_topics(topic_lpo, timeout=15)
    if topic_lpo not in md.topics or md.topics[topic_lpo].error is not None:
        print('   Topic inexistent/inaccesibil:', md.topics.get(topic_lpo)); sys.exit(1)
    parts = list(md.topics[topic_lpo].partitions.keys())
    print(f'   partitii={len(parts)}')

    # pentru fiecare partitie: seek la (high - NMSG) ca sa citim ULTIMELE mesaje; assign manual
    assign = []
    total_msgs = 0
    for p in parts:
        lo, hi = consumer.get_watermark_offsets(TopicPartition(topic_lpo, p), timeout=10)
        total_msgs += max(0, hi - lo)
        start = max(lo, hi - NMSG)
        if hi > lo:
            assign.append(TopicPartition(topic_lpo, p, start))
    print(f'   mesaje totale in topic (toate partitiile): ~{total_msgs}')
    if not assign:
        print('\n   (topic gol pe acest mediu — niciun mesaj)'); consumer.close(); sys.exit(0)
    consumer.assign(assign)

    seen = 0; t0 = time.time(); pods_seen = set(); etypes_seen = set()
    while seen < NMSG and time.time() - t0 < 30:
        m = consumer.poll(5.0)
        if m is None:
            print('   ... poll timeout'); continue
        if m.error():
            if m.error().code() == KafkaError._PARTITION_EOF:
                continue
            print('   EROARE:', m.error()); continue
        seen += 1
        key = m.key().decode('utf-8', 'replace') if m.key() else ''
        kind, obj = decode(key, m.value())
        print(f'\n--- mesaj #{seen} p{m.partition()} off={m.offset()} len={len(m.value() or b"")} | {kind}')
        if isinstance(obj, dict):
            for fld in ('readDate','timestamp','meterSerialNumber','supplyPointId','energyType',
                        'sampleFrequency','complete','sampleDate','cui_furnizor'):
                if fld in obj: print(f'      {fld} = {obj[fld]}')
            sv = obj.get('sampleValues')
            if isinstance(sv, str):
                parts = sv.split('$')
                print(f'      sampleValues: {len(parts)} valori | primele 6={parts[:6]} | ultimele 2={parts[-2:]}')
            pods_seen.add(obj.get('supplyPointId')); etypes_seen.add(obj.get('energyType'))
    if seen == 0:
        print('\n   (niciun mesaj primit in fereastra — topic gol pe PRE-PROD sau fara date recente)')
    else:
        print(f'\n   Total citite: {seen}. POD distincte={sorted(x for x in pods_seen if x)} | energyType={sorted(x for x in etypes_seen if x)}')
        print('   NU s-a facut commit -> offset productie neatins.')
finally:
    consumer.close()   # fara commit
