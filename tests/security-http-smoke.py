"""Read-only HTTP checks for server-level protections, including JSON REST routing."""
import os
import urllib.error
import urllib.request

base = os.environ.get('SITE_TEST_URL', 'http://localhost:8080').rstrip('/')
for path in ['/xmlrpc.php', '/wp-content/xdj-private/security.log',
             '/wp-content/uploads/xdj-security-probe.php']:
    try:
        with urllib.request.urlopen(base + path, timeout=20) as response:
            status = response.status
    except urllib.error.HTTPError as error:
        status = error.code
    assert status == 403, (path, status)
    print(path, status)
request = urllib.request.Request(base + '/wp-json/wp/v2/posts', data=b'{}',
                                 headers={'Content-Type': 'application/json'})
try:
    with urllib.request.urlopen(request, timeout=20) as response:
        status = response.status
except urllib.error.HTTPError as error:
    status = error.code
assert status in (401, 403), ('Anonymous post creation', status)
print('Anonymous post creation denied', status)
