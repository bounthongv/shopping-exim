FROM php:7.4-apache
RUN a2enmod rewrite
RUN docker-php-ext-install pdo pdo_mysql mysqli
# Custom PHP config - match exim-billing (hide warnings, log errors)
RUN { \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /var/log/php_errors.log'; \
        echo 'date.timezone = Asia/Vientiane'; \
        echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT'; \
    } > /usr/local/etc/php/conf.d/custom.ini
WORKDIR /var/www/html
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
EXPOSE 80
