"""Integration checks for restored site structure and contextual enquiries.

Reads the running local site; writes one explicitly invalid-domain test enquiry.
Set SITE_TEST_URL to target a separate test installation. Never run against production.
"""
import json
import os
import re
import uuid
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('SITE_TEST_URL', 'http://localhost:8080').rstrip('/')
LANGUAGES = ['zh-hans', 'zh-hant', 'en', 'fr', 'de', 'it', 'es']
COUNTS = {'print': 7, 'ordering': 3, 'management': 3, 'kitchen': 5}


def read(url):
    with urllib.request.urlopen(url, timeout=30) as response:
        assert response.status == 200, url
        return response.read().decode()


def links(body):
    return re.findall(r'href="([^"]+)"', body)


first_product = None
for language in LANGUAGES:
    body = read(f'{BASE}/{language}/')
    assert len(re.findall(r'\bdata-slide(?:\s|>)', body)) == 5, (language, 'slides')
    assert body.count('class="section product-section"') == 4, language
    assert 'LOCAL DEVELOPMENT PREVIEW' not in body
    paths = links(body)
    catalogue = next(url for url in paths if '/services' in url and '?' not in url)
    for category, count in COUNTS.items():
        page = read(catalogue + '?' + urllib.parse.urlencode({'category': category}))
        assert page.count('class="service-card"') == count, (language, category)
    for key in ['cases', 'activities', 'support', 'privacy', 'resources']:
        target = next(url for url in paths if '/' + key in url)
        page = read(target)
        assert 'page-content section' in page, (language, key)
        if key == 'activities':
            assert page.count('<figure><a') == 10, language
        if key == 'cases':
            assert page.count('<figure><a') == 27, language
    support = read(next(url for url in paths if '/support' in url))
    for key in ['pos-login', 'pos-interface']:
        manual = read(next(url for url in links(support) if '/' + key in url))
        visible=re.sub(r'<(script|style)\b[^>]*>.*?</\1>', '', manual, flags=re.S|re.I)
        visible=re.sub(r'<[^>]+>', '', visible)
        assert '1977' not in visible and '00000' not in visible, 'published default password'
        if not language.startswith('zh'):
            assert 'manual-steps' in manual, (language, 'localized manual')
    catalogue_body = read(catalogue)
    products = list(dict.fromkeys(url for url in links(catalogue_body) if '/products/' in url))
    assert len(products) == 18, (language, len(products))
    for product in products:
        detail = read(product)
        assert 'data-gallery-index="0"' in detail and 'id="product-enquiry"' in detail
        assert 'breadcrumbs' in detail and 'product-summary' in detail
        if language == 'zh-hant' and not first_product:
            first_product = product
    assistant = json.loads(read(BASE + '/wp-json/xdj/v1/assistant?' + urllib.parse.urlencode({'q': 'menu', 'lang': language})))
    assert assistant['mode'] == 'local-search' and assistant['results'], language
    assert all('/' + language + '/' in row['url'] for row in assistant['results']), language
    print(language, 'complete home, categories, pages, manuals, products and assistant OK', flush=True)

# A submitted product enquiry must carry a real product and same-origin source page.
detail = read(first_product)
product_id = int(re.search(r'name="product_id" value="(\d+)"', detail).group(1))
payload = {'name': 'Migration integration test', 'email': 'test@example.invalid', 'phone': '',
           'country': 'US', 'service': 'print', 'message': 'Test record; do not contact.',
           'locale': 'zh-hant', 'consent': True, 'product_id': product_id,
           'source_url': first_product, 'topic': 'Product catalogue'}
request = urllib.request.Request(BASE + '/wp-json/xdj/v1/inquiry', json.dumps(payload).encode(),
                                 {'Content-Type': 'application/json', 'Idempotency-Key': str(uuid.uuid4()), 'Origin': BASE})
with urllib.request.urlopen(request) as response:
    data = json.load(response)
    assert response.status == 201 and data['saved'] and data['notification'] == 'pending'
print('Product enquiry context accepted:', data['reference'])

# Canonical taxonomy routes, legacy compatibility, search and discoverability endpoints.
body=read(BASE+'/zh-hans/')
category_link=next(url for url in links(body) if '/product_category/print-' in url)
assert read(category_link).count('class="service-card"')==7
with urllib.request.urlopen(BASE+'/Products_Detail/1.html',timeout=15) as response:
 assert '/zh-hans/products/' in response.url
assert 'search-results' in read(BASE+'/zh-hans/?s='+urllib.parse.quote('菜单'))
assert read(BASE+'/llms.txt').count('](')==35
print('Taxonomy, old URL redirects, search and llms.txt OK')
