#!/usr/bin/env python3
"""Scaneaza READ-ONLY (assign manual, fara commit) intervalul de sampleDate disponibil
in topicul _lpo si distributia mesajelor pe zi + energyType. Nu atinge offset productie."""
import os, sys, io, json, time
from collections import Counter
import fastavro
from confluent_kafka import Consumer, KafkaError, TopicPartition

ENV = (sys.argv[1] if len(sys.argv) > 1 else 'PRE-PROD').upper()
MAXSCAN = int(sys.argv[2]) if len(sys.argv) > 2 else 45000

creds = os.path.expanduser('~/.smartmeter/kafka_credentiale.txt')
sec, cur = {}, None
for raw in open(creds, encoding='utf-8'):
    s = raw.rstrip('\n')
    if s.strip() in ('PRE-PROD', 'PROD'): cur = s.strip(); sec[cur] = {}; continue
    if cur and ':' in s:
        k, v = s.split(':', 1); sec[cur][k.strip()] = v.strip()
c = sec[ENV]
topic = [t.strip() for t in c['Topics'].split(';') if 'lpo' in t][0]
conf = {'bootstrap.servers': c['Host'], 'security.protocol': 'SASL_SSL',
        'sasl.mechanisms': 'SCRAM-SHA-512', 'sasl.username': c['User'], 'sasl.password': c['Password'],
        'group.id': c['Consumer Group'].strip(), 'enable.auto.commit': False, 'socket.timeout.ms': 15000}
consumer = Consumer(conf)
print(f'== SCAN {ENV} topic={topic} (max {MAXSCAN} mesaje, fara commit) ==')

md = consumer.list_topics(topic, timeout=15)
parts = list(md.topics[topic].partitions.keys())
assign, total = [], 0
for p in parts:
    lo, hi = consumer.get_watermark_offsets(TopicPartition(topic, p), timeout=10)
    total += max(0, hi - lo)
    assign.append(TopicPartition(topic, p, lo))   # de la CEL MAI VECHI offset retinut
consumer.assign(assign)
print(f'   partitii={len(parts)} | mesaje in topic ~{total}')

by_date = Counter(); by_type = Counter(); a1_by_date = Counter()
freq_by_date = {}
seen = 0; t0 = time.time()
while seen < MAXSCAN and time.time() - t0 < 120:
    m = consumer.poll(4.0)
    if m is None: break
    if m.error():
        if m.error().code() == KafkaError._PARTITION_EOF: continue
        break
    seen += 1
    try:
        schema = json.loads(m.key().decode('utf-8').rsplit('||', 1)[0])
        rec = fastavro.schemaless_reader(io.BytesIO(m.value()), schema)
        d = (rec.get('sampleDate') or '')[:10]
        et = rec.get('energyType')
        by_date[d] += 1; by_type[et] += 1
        if et == 'A1':
            a1_by_date[d] += 1
            freq_by_date.setdefault(d, Counter())[rec.get('sampleFrequency')] += 1
    except Exception:
        pass
consumer.close()

print(f'\n   scanate: {seen}')
print('\n== mesaje pe zi (toate tipurile) ==')
for d in sorted(by_date): print(f'   {d}: {by_date[d]}')
print('\n== mesaje A1 (consum) pe zi + frecvente ==')
for d in sorted(a1_by_date):
    fr = dict(freq_by_date.get(d, {}))
    print(f'   {d}: {a1_by_date[d]} PODuri A1 | freq={fr}')
print('\n== energyType global ==', dict(by_type))
