import email,email.policy,hashlib,json,mimetypes,re
from pathlib import Path
root=Path('migration');folder=root/'media';folder.mkdir(exist_ok=True)
m=json.loads((root/'media-map.json').read_text())
archive=email.message_from_bytes(Path('/tmp/onestep-legacy-uk.mhtml').read_bytes(),policy=email.policy.default)
count=0
for part in archive.walk():
 if not part.get_content_type().startswith('image/'):continue
 url=part.get('Content-Location');raw=part.get_payload(decode=True)
 if not url or not raw:continue
 ext=mimetypes.guess_extension(part.get_content_type()) or '.jpg'
 name=hashlib.sha256(url.encode()).hexdigest()+ext
 (folder/name).write_bytes(raw);m[url]=name;count+=1
for url,name in list(m.items()):
 if name:m[re.sub(r'_(\d+)xaf\.[a-z]+$','',url)]=name
(root/'media-map.json').write_text(json.dumps(m,ensure_ascii=False,indent=2));print('Recovered images:',count)
