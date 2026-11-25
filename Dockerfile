FROM php:8.3-apache

# Cài đặt extension PDO MySQL & bật mod_rewrite, mod_headers cho .htaccess
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers

# Cho phép .htaccess hoạt động trong /var/www/html
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Thiết lập thư mục làm việc & sao chép mã nguồn
WORKDIR /var/www/html
COPY . /var/www/html/

# Đảm bảo thư mục uploads tồn tại và Apache có quyền ghi (move_uploaded_file)
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
