#!/usr/bin/env bash
# Run only on the server, from an uploaded, immutable release directory.
set -euo pipefail
umask 077
release_dir=$(cd "$(dirname "$0")/../.." && pwd)
[[ "$release_dir" =~ ^/opt/onestep/releases/[0-9a-f]{40}$ ]] || { echo 'Invalid release directory'; exit 1; }
exec 9>/opt/onestep/state/deploy.lock
flock -n 9 || { echo 'Another onestep deployment is running'; exit 1; }
[[ ! -e /opt/onestep/state/pending ]] || { echo 'Resolve previous pending deployment first'; exit 1; }
cd "$release_dir"
# Input files contain only checked image references, never shell commands.
for image in wordpress scheduler; do
  value=$(cat "$image.image")
  [[ "$value" =~ ^ghcr\.io/yuanyuexiang/one-step-(wordpress|scheduler)@sha256:[0-9a-f]{64}$ ]] || exit 1
  [[ "$value" == "ghcr.io/yuanyuexiang/one-step-$image@"* ]] || exit 1
done
ONESTEP_WORDPRESS_IMAGE=$(cat wordpress.image)
ONESTEP_SCHEDULER_IMAGE=$(cat scheduler.image)
export ONESTEP_WORDPRESS_IMAGE ONESTEP_SCHEDULER_IMAGE
compose=(docker compose --project-name onestep --env-file /opt/onestep/config/runtime.env -f "$release_dir/docker-compos.production.yaml")
"${compose[@]}" config --quiet
docker network inspect matrix-network >/dev/null
"${compose[@]}" pull db wordpress scheduler
# First provisioning installs/restores WordPress separately; do not publish an empty installer.
"${compose[@]}" run --rm --no-deps --entrypoint wp scheduler core is-installed
backup_dir="/opt/onestep/backups/$(date -u +%Y%m%dT%H%M%SZ)-${release_dir##*/}"
mkdir -p "$backup_dir"
printf '%s\n' "$release_dir" > /opt/onestep/state/pending
"${compose[@]}" stop scheduler wordpress
# Quiesce application writes before backing up database and all WordPress files.
# shellcheck disable=SC2016 # Expanded inside the database container.
"${compose[@]}" exec -T db sh -c 'MYSQL_PWD="$(cat /run/secrets/db_root_password)" exec mysqldump -uroot --single-transaction --routines --triggers --events --no-tablespaces "$MYSQL_DATABASE"' > "$backup_dir/database.sql"
test -s "$backup_dir/database.sql"
"${compose[@]}" run --rm --no-deps --entrypoint tar -v "$backup_dir:/backup" wordpress -czf /backup/wordpress.tar.gz -C /var/www/html .
tar -tzf "$backup_dir/wordpress.tar.gz" >/dev/null
(cd "$backup_dir" && sha256sum database.sql wordpress.tar.gz > SHA256SUMS)
"${compose[@]}" up -d --no-build --wait --wait-timeout 180 db wordpress
"${compose[@]}" run --rm --no-deps --entrypoint wp scheduler core is-installed
actual=$(docker inspect --format '{{.Config.Image}}' "$("${compose[@]}" ps -q wordpress)")
[[ "$actual" == "$ONESTEP_WORDPRESS_IMAGE" ]]
for lang in zh-hans zh-hant en fr de it es; do
  curl --fail --silent --show-error --location --retry 5 --retry-delay 10 --max-time 60 "https://ai-onestep.com/$lang/" -o /dev/null
done
"${compose[@]}" up -d --no-build scheduler
actual=$(docker inspect --format '{{.Config.Image}}' "$("${compose[@]}" ps -q scheduler)")
[[ "$actual" == "$ONESTEP_SCHEDULER_IMAGE" ]]
if [[ -f /opt/onestep/state/current ]]; then cp /opt/onestep/state/current /opt/onestep/state/previous; fi
printf '%s\n' "$release_dir" > /opt/onestep/state/current
rm /opt/onestep/state/pending
printf 'Published %s\n' "${release_dir##*/}"
