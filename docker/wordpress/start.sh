#!/bin/sh
set -eu
# Refresh only project-owned code; never replace uploads or the live database.
mkdir -p /var/www/html/wp-content/plugins /var/www/html/wp-content/themes
cp -a /opt/onestep/plugins/polylang /var/www/html/wp-content/plugins/
cp -a /opt/onestep/plugins/xdj-business /var/www/html/wp-content/plugins/
cp -a /opt/onestep/themes/xdj-site /var/www/html/wp-content/themes/
# A new dedicated uploads volume starts root-owned; both Apache and WP-CLI use UID 33.
mkdir -p /var/www/html/wp-content/uploads
chown www-data:www-data /var/www/html/wp-content/uploads
cp /opt/onestep/htaccess /var/www/html/.htaccess
exec docker-entrypoint.sh "$@"
