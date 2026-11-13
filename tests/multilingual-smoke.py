import urllib.request,re,json
base='http://localhost:8080'
for lang in ['zh-hans','zh-hant','en','fr','de','it','es']:
 with urllib.request.urlopen(base+'/'+lang+'/',timeout=15) as r:
  body=r.read().decode();assert r.status==200 and 'site-language' in body,(lang,r.status)
  switcher=re.search(r'<select[^>]*id="site-language"[^>]*>(.*?)</select>',body,re.S)
  assert switcher and switcher.group(1).count('<option ')==7,(lang,'language options')
  links=re.findall(r'href="([^"]+)"',body)
  service=next(x for x in links if '/services' in x)
 with urllib.request.urlopen(service,timeout=15) as r:assert r.status==200
 for key in ['markets','about','contact']:
  target=next(x for x in links if '/'+key in x)
  with urllib.request.urlopen(target,timeout=15) as r:
   page=r.read().decode()
   assert r.status==200 and 'page-content section' in page,(lang,key,'wrong template')
   assert '&lt;span&gt;' not in page,(lang,key,'escaped markup')
   assert '<article>' in page,(lang,key,'missing content')
 print(lang,'home, catalogue and navigation pages OK')
records=json.load(open('migration/import-report.json'))
products=[r for r in records if r.get('state')=='local-preview-review']
for r in products:
 with urllib.request.urlopen(r['target'],timeout=15) as response:assert response.status==200,r
print('product pages OK',len(products))

# A fallback link must preserve the requested language after redirects.
with urllib.request.urlopen(base+'/de/services-de/',timeout=15) as response:
 body=response.read().decode()
 fallback=re.search(r'href="([^"]+)"[^>]*>English catalogue',body).group(1)
with urllib.request.urlopen(fallback,timeout=15) as response:
 assert '/en/' in response.url,response.url
 assert 'Products &amp; services' in response.read().decode()
print('German catalogue fallback resolves to English')
