FROM php:8.2-apache

# SQLite3 support is already built into the official PHP image.

# Keep the database and setup files from being served over HTTP
RUN printf '<DirectoryMatch "^/var/www/html/(data|docker)">\n    Require all denied\n</DirectoryMatch>\n' \
    > /etc/apache2/conf-available/deny-private.conf \
    && a2enconf deny-private

WORKDIR /var/www/html
COPY . /var/www/html/
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod +x /var/www/html/docker/start.sh

# Render sends traffic to $PORT (default 10000)
ENV PORT=10000
EXPOSE 10000

CMD ["/var/www/html/docker/start.sh"]
