import json,re,urllib.request,subprocess,concurrent.futures
from pathlib import Path
base='http://localhost:8080'
m=json.load(open('migration/media-map.json'))
files=sorted(set(v for v in m.values() if v))
def check(name):
 u=base+'/wp-content/uploads/legacy/'+name
 with urllib.request.urlopen(u,timeout=20) as r:
  assert r.status==200 and r.headers.get('Content-Type','').startswith('image/'),(u,r.status)
 return name
with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:list(pool.map(check,files))
print('Local image files HTTP verified:',len(files),flush=True)
for video in json.load(open('migration/video-report.json')):
 assert video['state']=='verified-file'
 name=Path(video['file']).name
 r=urllib.request.urlopen(urllib.request.Request(base+'/wp-content/uploads/legacy/'+name,headers={'Range':'bytes=0-1023'}),timeout=20)
 assert r.status==206 and len(r.read())==1024,name
 p=subprocess.run(['ffmpeg','-v','error','-i',video['file'],'-f','null','-'],capture_output=True,text=True)
 assert p.returncode==0,(name,p.stderr)
 print('Video decode + HTTP range OK:',name,flush=True)
for lang in ['zh-hans','zh-hant','en','fr','de','it','es']:
 with urllib.request.urlopen(base+'/'+lang+'/videos-'+lang+'/',timeout=15) as r:
  body=r.read().decode();assert body.count('<video ')==3,(lang,body.count('<video '))
print('Seven language video pages verified',flush=True)
