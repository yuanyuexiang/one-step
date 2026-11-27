"""Read-only language negotiation checks. No production inquiries or database writes."""
import os
import urllib.error
import urllib.request

BASE = os.environ.get('SITE_TEST_URL', 'http://localhost:8080').rstrip('/')


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


opener = urllib.request.build_opener(NoRedirect)


def request(path, language, cookie=''):
    headers = {'Accept-Language': language}
    if cookie:
        headers['Cookie'] = cookie
    try:
        return opener.open(urllib.request.Request(BASE + path, headers=headers), timeout=25)
    except urllib.error.HTTPError as response:
        return response


for preference, expected in [
    ('en-GB,en;q=0.9', 'en'), ('fr-FR,fr;q=0.9', 'fr'),
    ('de-DE,de;q=0.9', 'de'), ('it-IT,it;q=0.9', 'it'),
    ('es-ES,es;q=0.9', 'es'), ('zh-CN,zh;q=0.9', 'zh-hans'),
    ('zh-TW,zh;q=0.9', 'zh-hant'), ('ja-JP', 'zh-hans'), ('zh-Hant', 'zh-hant'),
    ('zh-Hans', 'zh-hans'), ('zh-HK', 'zh-hant'), ('zh-MO', 'zh-hant'),
    ('zh-SG', 'zh-hans'), ('fr;q=1,zh-HK;q=0.5', 'fr'),
    ('zh-HK;q=0,en;q=1', 'en'),
    ('fr;q=0.5,de;q=1', 'de'),
]:
    response = request('/', preference)
    assert response.code == 302, (preference, response.code)
    assert response.headers['Location'] == f'{BASE}/{expected}/', (preference, response.headers)
    print(preference, '->', expected, flush=True)

response = request('/', 'de-DE', 'pll_language=fr')
assert response.code == 302 and response.headers['Location'] == BASE + '/fr/'
response = request('/en/', 'de-DE', 'pll_language=fr')
assert response.code == 200, response.code
assert 'pll_language=en' in '\n'.join(response.headers.get_all('Set-Cookie', []))
response = request('/', 'de-DE', 'pll_language=en')
assert response.code == 302 and response.headers['Location'] == BASE + '/en/'
print('Remembered preference wins; explicit language URL wins and refreshes preference.')
