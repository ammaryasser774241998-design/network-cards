FROM php:8.2-apache

# تثبيت الحزم المطلوبة وادوات النظام
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# تثبيت إضافات PHP الضرورية للارافيل وقاعدة البيانات
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تحديد مجلد العمل
WORKDIR /var/www/html

# نسخ ملفات المشروع إلى الحاوية
COPY . /var/www/html

# تغيير مجلد الـ Document Root الخاص بـ Apache ليوجه إلى public الخاصة بلارافيل
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# ضبط البورت ليتوافق مع منصة Render (بورت 10000)
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# تفعيل Rewrite Module
RUN a2enmod rewrite

# ضبط صلاحيات مجلد التخزين والـ cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache