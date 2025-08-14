FROM php:8.3-fpm
RUN docker-php-ext-install pdo pdo_mysql
# (ถ้าอยากใช้ Xdebug ค่อยเติม)
WORKDIR /var/www/html
