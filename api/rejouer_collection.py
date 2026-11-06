# Rejoue la collection Postman sans Postman : envoie les 30 requêtes dans l'ordre et compare
# chaque code de statut à celui attendu par la collection.
#   python api/rejouer_collection.py [url]      (par défaut http://127.0.0.1:8080, base réinitialisée avant)
import json, re, urllib.request, urllib.error, os
import sys
os.chdir(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
col = json.load(open('api/pixelwave_api.postman_collection.json', encoding='utf8'))
var = {v['key']: v['value'] for v in col['variable']}
if len(sys.argv) > 1:
    var['baseUrl'] = sys.argv[1].rstrip('/')
def sub(s): return re.sub(r'\{\{(\w+)\}\}', lambda m: var.get(m.group(1), ''), s)
ok = ko = 0
for dossier in col['item']:
    for it in dossier['item']:
        r = it['request']
        url = sub(r['url']['raw'])
        auth = r.get('auth', col['auth'])
        h = {x['key']: x['value'] for x in r['header']}
        if auth['type'] == 'bearer':
            h['Authorization'] = 'Bearer ' + sub(auth['bearer'][0]['value'])
        data = sub(r['body']['raw']).encode() if 'body' in r else None
        req = urllib.request.Request(url, data=data, method=r['method'], headers=h)
        try:
            with urllib.request.urlopen(req) as rep: code, corps = rep.status, rep.read()
        except urllib.error.HTTPError as e: code, corps = e.code, e.read()
        script = '\n'.join(it.get('event', [{}])[0].get('script', {}).get('exec', []))
        attendu = int(re.search(r'have\.status\((\d+)\)', script).group(1))
        for m in re.finditer(r"collectionVariables\.set\('(\w+)', pm\.response\.json\(\)\.(\w+)\)", script):
            var[m.group(1)] = json.loads(corps)[m.group(2)]
        res = 'OK' if code == attendu else 'KO'
        ok += res == 'OK'; ko += res == 'KO'
        print(f"{res}  {code} (attendu {attendu})  {it['name']}")
print(f"\n{ok} OK, {ko} KO")
sys.exit(1 if ko else 0)
