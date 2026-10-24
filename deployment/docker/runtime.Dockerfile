FROM composer:2 AS composer
FROM php:8.3.36-fpm-bookworm@sha256:6b4641a875dd3e0f7551b2751e1f5909084856d18166af7fbb522ac230feb702

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl nginx supervisor cron mariadb-client python3 util-linux \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libicu-dev libzip-dev libsqlite3-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 bcmath ctype mbstring pcntl pdo_mysql pdo_sqlite gd intl zip \
    && rm -rf /var/lib/apt/lists/* \
    && useradd --system --create-home --home-dir /var/lib/cikscoffee --gid www-data cikscoffee \
    && install -d -m 0755 /run/php /run/lock /usr/local/lib/cikscoffee \
    && rm /etc/nginx/sites-enabled/default

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY ubuntu/nginx.conf /etc/nginx/sites-enabled/cikscoffee
COPY ubuntu/backup.sh /usr/local/bin/cikscoffee-backup
COPY ubuntu/ci-entry.sh /usr/local/sbin/cikscoffee-ci-entry
COPY ubuntu/validate-release.py /usr/local/lib/cikscoffee/validate-release.py
COPY docker/deploy-release.sh /usr/local/sbin/cikscoffee-deploy-release
COPY docker/health.sh /usr/local/bin/cikscoffee-health
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-cikscoffee.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zzzz-cikscoffee.conf
COPY docker/cron /etc/cron.d/cikscoffee

RUN sed -i 's/listen 127.0.0.1:80/listen 0.0.0.0:80/;s@/run/php/php8.3-fpm.sock@/run/php/cikscoffee-fpm.sock@' /etc/nginx/sites-enabled/cikscoffee \
    && chmod 0755 /usr/local/bin/cikscoffee-* /usr/local/sbin/cikscoffee-* \
    && chmod 0644 /etc/cron.d/cikscoffee

WORKDIR /var/www/cikscoffee
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
