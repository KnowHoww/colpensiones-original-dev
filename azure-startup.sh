#!/bin/bash

# 1. Cambiar la raíz de Apache a la carpeta public de Laravel
sed -i "s|/var/www/html|/var/www/html/public|g" /etc/apache2/sites-available/000-default.conf

# 2. Reiniciar el servicio de Apache
service apache2 reload

# 3. Dar permisos a las carpetas de Laravel por si acaso
chmod -R 775 /home/site/wwwroot/storage /home/site/wwwroot/bootstrap/cache
chown -R www-data:www-data /home/site/wwwroot/storage /home/site/wwwroot/bootstrap/cache