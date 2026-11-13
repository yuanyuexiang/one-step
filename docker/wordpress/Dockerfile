ARG WORDPRESS_IMAGE=wordpress:php8.3-apache@sha256:8b64037bb2a3bdc41bebccb165f20ad5e4c786b684ae4a2bd5981f98aa1eb107
FROM ${WORDPRESS_IMAGE}
RUN a2enmod headers expires deflate
COPY docker/vendor/polylang-3.9.2.zip /tmp/polylang.zip
RUN php -r 'if (hash_file("sha256", "/tmp/polylang.zip") !== "90d695711f642efd942d9b7ce25698827b7332a7b26222dfd21719c300e5f9cb") { exit(1); } $z = new ZipArchive; if ($z->open("/tmp/polylang.zip") !== true) { exit(1); } $z->extractTo("/opt/onestep/plugins"); $z->close();' && rm /tmp/polylang.zip
COPY wp-content/plugins/xdj-business /opt/onestep/plugins/xdj-business
COPY wp-content/themes/xdj-site /opt/onestep/themes/xdj-site
COPY docker/wordpress/htaccess /opt/onestep/htaccess
COPY docker/wordpress/start.sh /usr/local/bin/onestep-start
ENTRYPOINT ["onestep-start"]
CMD ["apache2-foreground"]
