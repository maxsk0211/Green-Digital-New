# ใช้ PHP 8.0 ให้ตรงกับเซิร์ฟเวอร์จริง (8.1 ขึ้นไป mysqli จะโยน exception เมื่อ query ผิดพลาด ทำให้หน้าเว็บพัง)
FROM php:8.0-apache

# PHP extension ที่เว็บใช้ (เชื่อมต่อ MariaDB ผ่าน mysqli)
RUN docker-php-ext-install mysqli

# ใช้ค่า php.ini แบบ production: ไม่แสดง error บนหน้าเว็บ (ดูได้จาก docker compose logs web)
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# ตั้งเวลาเป็นเวลาไทย และขยายขนาดไฟล์อัปโหลดรูปบทความ
RUN { \
      echo "date.timezone=Asia/Bangkok"; \
      echo "upload_max_filesize=10M"; \
      echo "post_max_size=12M"; \
    } > /usr/local/etc/php/conf.d/app.ini

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads/articles \
    && chown -R www-data:www-data /var/www/html/uploads
