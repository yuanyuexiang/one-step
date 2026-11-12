#!/usr/bin/env python3
"""Provision only onestep and configure its GitHub Actions credentials.

Requires an authenticated gh CLI and an explicitly supplied server admin key.
Never prints private keys or application credentials. Does not deploy site content.
"""
import argparse
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]
REPOSITORY = 'yuanyuexiang/one-step'


def run(args, **kwargs):
    return subprocess.run(args, check=True, **kwargs)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--admin-key', type=Path, required=True)
    args = parser.parse_args()
    if not args.admin_key.is_file():
        parser.error('Admin SSH key does not exist')
    # Check authorization before creating keys or changing the server.
    run(['gh', 'auth', 'status'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    run(['gh', 'api', f'repos/{REPOSITORY}/actions/secrets/public-key'],
        stdout=subprocess.DEVNULL)
    key = ROOT / 'secrets/onestep-deploy-key'
    key.parent.mkdir(mode=0o700, exist_ok=True)
    if not key.exists():
        run(['ssh-keygen', '-t', 'ed25519', '-N', '', '-C', 'onestep-github-actions',
             '-f', str(key)], stdout=subprocess.DEVNULL)
    key.chmod(0o600)
    public_key = Path(str(key) + '.pub').read_text().strip()
    # This administrator payload touches only the new onestep user and directory.
    remote = '''import os, pathlib, pwd, secrets, subprocess
public_key = PUBLIC_KEY
name = 'onestep-deploy'
try:
    account = pwd.getpwnam(name)
except KeyError:
    subprocess.run(['useradd', '--create-home', '--shell', '/bin/bash', name], check=True)
    account = pwd.getpwnam(name)
# Docker access is privileged; this is an independent CI identity, not Beacon's account.
subprocess.run(['usermod', '-aG', 'docker', name], check=True)
folder = pathlib.Path(account.pw_dir) / '.ssh'
folder.mkdir(mode=0o700, exist_ok=True)
keys = folder / 'authorized_keys'
entry = 'restrict ' + public_key
existing = keys.read_text() if keys.exists() else ''
if entry not in existing.splitlines():
    keys.write_text(existing.rstrip() + '\\n' + entry + '\\n')
for path, mode in ((folder, 0o700), (keys, 0o600)):
    os.chown(path, account.pw_uid, account.pw_gid)
    path.chmod(mode)
for part in ('', 'config', 'config/secrets', 'releases', 'state', 'backups'):
    path = pathlib.Path('/opt/onestep') / part
    path.mkdir(mode=0o700, parents=True, exist_ok=True)
    os.chown(path, account.pw_uid, account.pw_gid)
config = pathlib.Path('/opt/onestep/config')
runtime = config / 'runtime.env'
if not runtime.exists():
    runtime.write_text(RUNTIME_CONTENT)
    os.chown(runtime, account.pw_uid, account.pw_gid)
    runtime.chmod(0o600)
for name in ('db_password', 'db_root_password'):
    path = config / 'secrets' / name
    if not path.exists():
        path.write_text(secrets.token_hex(32) + '\\n')
        os.chown(path, account.pw_uid, 33)
        path.chmod(0o640)
print('Independent onestep deployment identity and runtime prepared.')
'''.replace('PUBLIC_KEY', repr(public_key)).replace(
        'RUNTIME_CONTENT', repr((ROOT / 'ops/deploy/runtime.env.example').read_text()))
    run(['ssh', '-i', str(args.admin_key.resolve()), '-o', 'IdentitiesOnly=yes',
         '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=yes',
         '-o', 'UserKnownHostsFile=' + str(ROOT / 'ops/deploy/known_hosts'),
         'ubuntu@18.175.156.130', 'sudo', '-n', 'python3', '-'], input=remote, text=True)
    # Restrict settings to this repository. Secrets go through stdin, not argv/logs.
    for name, value in (
        ('ONESTEP_DEPLOY_SSH_KEY', key.read_text()),
        ('ONESTEP_DEPLOY_KNOWN_HOSTS', (ROOT / 'ops/deploy/known_hosts').read_text()),
    ):
        run(['gh', 'secret', 'set', name, '--repo', REPOSITORY], input=value, text=True)
    for name, value in (('ONESTEP_DEPLOY_HOST', '18.175.156.130'),
                        ('ONESTEP_DEPLOY_USER', 'onestep-deploy')):
        run(['gh', 'variable', 'set', name, '--repo', REPOSITORY, '--body', value])
    run(['gh', 'api', '--method', 'PUT', f'repos/{REPOSITORY}/environments/production'],
        stdout=subprocess.DEVNULL)
    print('one-step Actions SSH credentials configured; Beacon was not modified.')
    print('Data restore and first deployment still require verification.')


if __name__ == '__main__':
    main()
