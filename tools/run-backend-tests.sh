#!/bin/bash
# Runs the project's Pest suite through php-wasm.
#
# Two constraints of this sandbox shape the script:
#   1. Pest/PHPUnit resolve their autoloader from the entry script's directory,
#      so tools/pest-entry.php drops a copy of the real bin inside
#      vendor/pestphp/pest/bin/ before requiring it.
#   2. The asyncify wasm build overflows its stack if the migrations run inside
#      the test process, so the schema is migrated once up front and the tests
#      run against it inside a rolled-back transaction.
set -u
cd /home/user/TOEFL-House
export NODE_EXTRA_CA_CERTS=${NODE_EXTRA_CA_CERTS:-/usr/local/share/ca-certificates/e2b-ca.crt}
export TEST_DB=${TEST_DB:-/tmp/testdb.sqlite}
SUITE=${1:-Unit}

node tools/migrate-testdb.mjs >/dev/null 2>&1 || { echo "test db build failed"; exit 1; }

rm -f /tmp/junit-${SUITE}_*.xml
for f in toefl-house-v3/server/tests/$SUITE/*.php; do
  n="${SUITE}_$(basename "$f" .php)"
  timeout 900 node tools/artisan.mjs /home/user/TOEFL-House/tools/pest-entry.php \
    --colors=never "tests/$SUITE/$(basename "$f")" --log-junit "/tmp/junit-$n.xml" >/dev/null 2>&1
  [ -s "/tmp/junit-$n.xml" ] && echo "ran   $SUITE/$(basename "$f")" || echo "CRASH $SUITE/$(basename "$f")"
done

python3 - "$SUITE" <<'PY'
import xml.etree.ElementTree as ET, glob, re, sys
suite=sys.argv[1]; tot=fail=err=0; rows=[]; fails=[]
for p in sorted(glob.glob(f'/tmp/junit-{suite}_*.xml')):
    try: r=ET.parse(p).getroot()
    except Exception: continue
    for ts in r.iter('testsuite'):
        if ts.find('testcase') is None: continue
        t=int(ts.get('tests') or 0); f=int(ts.get('failures') or 0); e=int(ts.get('errors') or 0)
        tot+=t; fail+=f; err+=e; rows.append((ts.get('name').split('\\')[-1],t,f,e))
        for tc in ts.iter('testcase'):
            for c in tc:
                if c.tag in ('failure','error'):
                    m=re.search(r'(Failed asserting[^\n]*|Exception:[^\n]*)', c.text or '')
                    fails.append((ts.get('name').split('\\')[-1], tc.get('name').split('→')[-1].strip()[:46], (m.group(1) if m else '')[:130]))
print(f"\n{'suite':<34}{'tests':>6}{'fail':>6}{'err':>5}{'pass':>6}")
for n,t,f,e in rows: print(f"{n:<34}{t:>6}{f:>6}{e:>5}{t-f-e:>6}")
print("-"*57); print(f"{'TOTAL':<34}{tot:>6}{fail:>6}{err:>5}{tot-fail-err:>6}")
if fails:
    print("\n--- failures ---")
    for s,n,m in fails: print(f"  [{s}] {n}\n      {m}")
PY
