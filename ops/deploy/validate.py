"""Validate the production Compose model without server access or real credentials."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

root = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory() as tmp:
    config = Path(tmp)
    (config / 'secrets').mkdir()
    for key in ('db_password', 'db_root_password'):
        (config / 'secrets' / key).write_text('validation-only')
    env = dict(os.environ, ONESTEP_CONFIG_DIR=tmp, SITE_URL='https://ai-onestep.com',
               SITE_HOST='ai-onestep.com', MYSQL_IMAGE='mysql:8.4',
               ANYTHINGLLM_IMAGE='mintplexlabs/anythingllm:latest')
    for service in ('wordpress', 'scheduler'):
        env['ONESTEP_' + service.upper() + '_IMAGE'] = (
            'ghcr.io/yuanyuexiang/one-step-' + service + '@sha256:' + 'a' * 64)
    result = subprocess.run(['docker', 'compose', '-f', str(root / 'docker-compos.production.yaml'),
                             'config', '--format', 'json'], env=env, check=True,
                            capture_output=True, text=True)
    model = json.loads(result.stdout)
    assert model['name'] == 'onestep'
    assert model['networks']['proxy']['name'] == 'matrix-network'
    for service in model['services'].values():
        assert not service.get('ports'), 'Production stack must not publish host ports'
        assert 'build' not in service, 'Build images in CI, not on the shared server'
    labels = model['services']['wordpress']['labels']
    assert labels['traefik.http.routers.onestep.rule'] == 'Host(`ai-onestep.com`)'
    assert labels['traefik.http.routers.onestep.tls.certresolver'] == 'route53'
    assert all(v['name'].startswith('onestep-') for v in model['volumes'].values())
print('Production Compose isolation, routing, and image-only deployment verified.')
