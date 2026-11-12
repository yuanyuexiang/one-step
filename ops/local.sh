#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
if [ ! -f .env.local ]; then
  cp .env.example .env.local
fi
mkdir -p secrets
chmod 700 secrets
for name in db_password db_root_password; do
  if [ ! -s "secrets/$name" ]; then
    (umask 077; openssl rand -hex 32 > "secrets/$name")
  fi
done
export COMPOSE_PROJECT_NAME=onestep-local
exec docker compose --env-file .env.local -f docker-compos.yaml -f docker-compos.local.yaml "$@"
