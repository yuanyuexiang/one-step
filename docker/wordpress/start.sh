#!/bin/sh
set -eu
# Refresh only project-owned code; never replace uploads or the live database.
mkdir -p /var/www/html/wp-content/plugins /var/www/html/wp-content/themes
cp -a /opt/onestep/plugins/polylang /var/www/html/wp-content/plugins/
cp -a /opt/onestep/plugins/xdj-business /var/www/html/wp-content/plugins/
cp -a /opt/onestep/themes/xdj-site /var/www/html/wp-content/themes/
cp /opt/onestep/htaccess /var/www/html/.htaccess
exec docker-entrypoint.sh "$@"
