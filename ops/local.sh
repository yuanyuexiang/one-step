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
# Docker Compose mounts file secrets with their host mode on Linux.
# The scheduler runs as UID 33; it must be able to read the shared DB password.
# The parent directory remains 0700, preventing other host users from traversing it.
chmod 644 secrets/db_password
export COMPOSE_PROJECT_NAME=onestep-local
exec docker compose --env-file .env.local -f docker-compos.yaml -f docker-compos.local.yaml "$@"
