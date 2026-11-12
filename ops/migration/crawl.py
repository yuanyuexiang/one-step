from bs4 import BeautifulSoup
import urllib.request,urllib.parse,concurrent.futures,json,re,hashlib,time
from pathlib import Path
ROOT=Path('migration'); (ROOT/'raw').mkdir(parents=True,exist_ok=True)
opener=urllib.request.build_opener(urllib.request.ProxyHandler({}))
def fetch(url):
 key=hashlib.sha256(url.encode()).hexdigest();p=ROOT/'raw'/f'{key}.html'
 if p.exists() and p.stat().st_size>100:return p.read_text()
 for attempt in range(3):
  try:
   req=urllib.request.Request(url,headers={'User-Agent':'Mozilla/5.0'})
   with opener.open(req,timeout=20) as r:s=r.read().decode('utf-8','replace')
   if len(s)>100:p.write_text(s);return s
  except Exception:pass
 return ''
def crawl(code):
 base=f'http://{code}.xiaodangjiacc.com';queue=[base+'/Home11.html'];seen=set();records=[];errors=[]
 while queue and len(seen)<90:
  pending=[u for u in queue if u not in seen];batch=pending[:12];queue=pending[12:]
  if not batch:break
  with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:results=list(pool.map(fetch,batch))
  for url,html in zip(batch,results):
   seen.add(url)
   if not html:errors.append(url);continue
   soup=BeautifulSoup(html,'html.parser')
   for image in soup.select('img'):
    if image.get('lazy'):image['src']=urllib.parse.urljoin(url,image['lazy'])
   assets=[dict(url=urllib.parse.urljoin(url,i.get('src','')),alt=i.get('alt','')) for i in soup.select('img[src]') if 's.png' not in i.get('src','')]
   for a in soup.select('a[href]'):
    target=urllib.parse.urljoin(url,a['href']);parts=urllib.parse.urlsplit(target)
    if parts.hostname==urllib.parse.urlsplit(base).hostname and re.search(r'\.html$',parts.path,re.I):
     clean=urllib.parse.urlunsplit((parts.scheme,parts.netloc,parts.path,'',''))
     if clean not in seen and clean not in queue:queue.append(clean)
   typ='product' if re.search(r'/(products_detail|products_details)/',url,re.I) else 'page'
   detail=soup.select_one('[id^="c_product_detail"]') if typ=='product' else None
   title=soup.title.get_text(' ',strip=True) if soup.title else url
   title=re.split(r'-(?:One step|南京)',title,flags=re.I)[0]
   if detail:
    t=detail.select_one('[class*=e_text-92]');title=t.get_text(' ',strip=True) if t else title
    rich=detail.select('[class*=e_richText]');body=''.join(str(e) for e in rich if e.find('img') or len(e.get_text(strip=True))>80)
    if not body:body='<p>'+detail.get_text(' ',strip=True)+'</p>'
    images=detail.select('img');featured=images[0] if images else None
   else:
    rich=soup.select('[class*=e_richText]');body=''.join(str(e) for e in rich if len(e.get_text(strip=True))>40)
    featured=None
   if body:
    b=BeautifulSoup(body,'html.parser')
    for e in b.select('script,style,iframe,form,input,button'):e.decompose()
    for e in b.find_all(True):
     for a in list(e.attrs):
      if a not in ['src','href','alt','title','colspan','rowspan']:del e[a]
     for a in ['src','href']:
      if e.get(a):e[a]=urllib.parse.urljoin(url,e[a])
    records.append(dict(source=url,site=code,type=typ,title=title,html=str(b),featured=urllib.parse.urljoin(url,featured.get('src','')) if featured else '',assets=assets))
  # Keep pending links discovered in prior iterations too.
  queue=list(dict.fromkeys(queue))
 return dict(site=code,visited=len(seen),records=records,errors=errors)
with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:sites=list(pool.map(crawl,['cn','uk','fr','de','ita','es']))
(ROOT/'legacy.json').write_text(json.dumps(sites,ensure_ascii=False,indent=2))
for s in sites:print(s['site'],s['visited'],len(s['records']),len(s['errors']),flush=True)
