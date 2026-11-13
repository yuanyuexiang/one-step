import json, os, uuid, urllib.request, urllib.error
origin=os.environ.get('SITE_TEST_URL','http://localhost:8080').rstrip('/')
base=origin+'/wp-json/xdj/v1/inquiry'
key=str(uuid.uuid4())
payload=dict(name='开发验收测试', email='test@example.invalid', phone='', country='GB', service='print', message='自动测试记录，请勿联系。', consent=True)
def post(body, token):
 request=urllib.request.Request(base,json.dumps(body).encode(),{'Content-Type':'application/json','Idempotency-Key':token,'Origin':origin})
 try:
  with urllib.request.urlopen(request) as response:return response.status,json.load(response)
 except urllib.error.HTTPError as response:return response.code,json.load(response)
a,b=post(payload,key);assert a==201,(a,b)
c,d=post(payload,key);assert c==200 and d['reference']==b['reference'],(c,d)
e,f=post(dict(payload,message='changed'),key);assert e==409,(e,f)
g,h=post(dict(payload,email='',phone=''),str(uuid.uuid4()));assert g==422,(g,h)
print('PASS: persistence, idempotent replay, payload conflict, contact validation',b['reference'])
