import json,hashlib,urllib.request,urllib.parse,concurrent.futures,re
from pathlib import Path
from bs4 import BeautifulSoup
root=Path('migration');out=root/'media';out.mkdir(exist_ok=True)
data=json.loads((root/'legacy.json').read_text());urls=set()
for site in data:
 for r in site['records']:
  urls.update(a['url'] for a in r.get('assets',[]) if a.get('url'))
  if r['type']=='product':
   urls.update(i['src'] for i in BeautifulSoup(r['html'],'html.parser').select('img[src]'))
   if r['featured']:urls.add(r['featured'])
opener=urllib.request.build_opener(urllib.request.ProxyHandler({}))
def download(u):
 try:
  ext=Path(urllib.parse.urlsplit(u).path).suffix.lower()
  if ext not in ['.jpg','.jpeg','.png','.webp','.gif']:ext='.jpg'
  name=hashlib.sha256(u.encode()).hexdigest()+ext;p=out/name
  if not p.exists():
   with opener.open(urllib.request.Request(u,headers={'User-Agent':'Mozilla/5.0','Referer':'http://uk.xiaodangjiacc.com/','Range':'bytes=0-'}),timeout=25) as r:
    raw=r.read(15*1024*1024)
    if not r.headers.get('Content-Type','').startswith('image/'):return u,None
   p.write_bytes(raw)
  return u,name
 except Exception:return u,None
with concurrent.futures.ThreadPoolExecutor(max_workers=10) as pool:m=dict(pool.map(download,urls))
(root/'media-map.json').write_text(json.dumps(m,ensure_ascii=False,indent=2));print('media',len(m),'downloaded',sum(bool(x) for x in m.values()))
