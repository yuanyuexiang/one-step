import json,subprocess,hashlib,concurrent.futures
from pathlib import Path
root=Path('migration');out=root/'videos';out.mkdir(exist_ok=True)
sources=json.load(open(root/'video-sources.json'))
def fetch(u):
 name=u.rsplit('/',1)[-1];p=out/name
 r=subprocess.run(['curl','--noproxy','*','-sS','-L','--retry','2','--max-time','180','-A','Mozilla/5.0','-e','http://uk.xiaodangjiacc.com/','-H','Range: bytes=0-','-o',str(p),'-w','%{http_code} %{size_download}',u],capture_output=True,text=True)
 probe=subprocess.run(['ffprobe','-v','error','-show_entries','format=duration,size:stream=codec_name,codec_type,width,height','-of','json',str(p)],capture_output=True,text=True)
 valid=probe.returncode==0 and bool(json.loads(probe.stdout).get('streams'))
 return dict(source=u,file=str(p) if valid else None,state='verified-file' if valid else 'failed',http=r.stdout,probe=json.loads(probe.stdout) if valid else {},error=probe.stderr[:200])
# Deduplicate video filenames before downloading; aliases refer to identical source IDs.
urls=list({u.rsplit('/',1)[-1]:u for u in sources}.values())
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:report=list(pool.map(fetch,urls))
(root/'video-report.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
for r in report:print(r['state'],r['file'],r['http'],flush=True)
