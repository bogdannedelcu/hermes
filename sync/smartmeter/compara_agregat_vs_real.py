#!/usr/bin/env python3
"""
Material de suport (Varianta B): pentru curbe MASURATE AGREGATE, compara per POD membru:
  - ESTIMAT (modelul actual) = curba_agregata(interval) x cota_lunara_POD
  - REAL (SmartMeter)        = valoarea A1 proprie a POD-ului din arhiva smartmeter_lpo
Pe cateva zile. Arata totaluri zilnice + potrivirea de forma intraday (corelatie).
Uz: python3 compara_agregat_vs_real.py <curve_id> <YYYY-MM-01_luna_coef> <zi1> <zi2> ...
"""
import os, sys, io, json, subprocess
from datetime import datetime
import pymysql

BASE = '/var/www/ebsv2/sync'
cid   = int(sys.argv[1])
coefMonth = sys.argv[2]                 # ex 2026-08-31 (consumption_date pt coeficienti)
days  = sys.argv[3:]                    # ex 2026-08-20 2026-08-21 ...
mo    = int(days[0][5:7])

db = json.loads(subprocess.check_output(['php8.2','-r',f"echo json_encode((require '{BASE}/config.php')['db']);"]))
conn = pymysql.connect(host=db['host'],user=db['user'],password=db['pass'],database=db['name'],charset='utf8mb4')

def q(sql, args=()):
    with conn.cursor() as c: c.execute(sql, args); return c.fetchall()

cname = q("SELECT curve_name FROM actual_curves WHERE curve_id=%s",(cid,))[0][0]
# membrii + coeficient pe luna
rows = q("""SELECT acv.pod, acv.consumption_ea/tot.total AS coef, cu.customer_name
            FROM actual_curves_variance acv
            JOIN (SELECT curve_id,consumption_date,SUM(consumption_ea) total FROM actual_curves_variance
                  WHERE curve_id=%s AND consumption_date=%s GROUP BY curve_id,consumption_date) tot
              ON tot.curve_id=acv.curve_id AND tot.consumption_date=acv.consumption_date
            JOIN pods p ON p.pod_no=acv.pod JOIN customers cu ON cu.customer_id=p.customer_id
            WHERE acv.curve_id=%s AND acv.consumption_date=%s""",(cid,coefMonth,cid,coefMonth))
members = [(r[0], float(r[1]), r[2]) for r in rows]
print(f"\n==== CURBA {cid}  {cname} ====")
print(f"membri ({len(members)}), cote pe {coefMonth}:")
for pod,coef,cn in members: print(f"   {pod}  cota={coef:.3f}  ({cn})")

def parse_real(sv, freq):
    parts = (sv or '').split('$')[1:1+96]
    parts += ['']*(96-len(parts))
    out=[0.0]*96
    def f(x):
        try: return float(x)
        except: return 0.0
    if str(freq)=='60':
        for h in range(24):
            v=f(parts[4*h])/4.0
            for j in range(4): out[4*h+j]=v/1000.0
    else:
        for k in range(96): out[k]=f(parts[k])/1000.0
    return out   # MWh/interval

def corr(a,b):
    n=len(a); ma=sum(a)/n; mb=sum(b)/n
    num=sum((a[i]-ma)*(b[i]-mb) for i in range(n))
    da=sum((x-ma)**2 for x in a)**.5; dbb=sum((x-mb)**2 for x in b)**.5
    return num/(da*dbb) if da>0 and dbb>0 else float('nan')

for day in days:
    # REAL per POD din SmartMeter; AGREGATUL = suma reala a membrilor
    reals={}
    for pod,coef,cn in members:
        sm=q("SELECT sample_frequency, sample_values FROM smartmeter_lpo WHERE pod=%s AND sample_date=%s AND energy_type='A1'",(pod,day))
        reals[pod]=parse_real(sm[0][1], sm[0][0]) if sm else None
    if any(reals[p] is None for p,_,_ in members):
        print(f"\n-- {day}: lipsesc date SmartMeter pt un membru --"); continue
    N=96
    agg=[sum(reals[p][i] for p,_,_ in members) for i in range(N)]   # agregat real
    print(f"\n-- {day} --  (agregat real total zi = {sum(agg):.3f} MWh)")
    for pod,coef,cn in members:
        est=[agg[i]*coef for i in range(N)]          # modelul actual: agregat x cota lunara
        real=reals[pod]
        te,tr=sum(est),sum(real)
        diff=(tr-te)/te*100 if te else 0
        # eroare medie absoluta pe interval, ca % din media reala
        mae=sum(abs(est[i]-real[i]) for i in range(N))/N
        mr=tr/N
        print(f"   {pod}: cota={coef:.3f} | ESTIMAT_zi={te:.3f} MWh | REAL_zi={tr:.3f} MWh | dif_zi={diff:+.1f}% | corelatie_forma={corr(est,real):.2f} | eroare_interval~{ (mae/mr*100 if mr else 0):.0f}%")
conn.close()
