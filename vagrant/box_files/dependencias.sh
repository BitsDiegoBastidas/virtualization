sudo su
echo "=========================="
echo "PHP VERSION: $1"
echo "=========================="
# INSTALL AND CONFIGURE NGINX
cp /usr/share/nginx/html/virtualizacion/vagrant/box_files/nginx.repo /etc/yum.repos.d/
yum -y update && yum -y install nginx && yum -y install initscripts
cp /usr/share/nginx/html/virtualizacion/vagrant/box_files/default.conf /etc/nginx/conf.d/

# INSTALL AND CONFIGURE PHP
yum install gcc-c++ zlib-devel amazon-linux-extras -y
amazon-linux-extras enable $1 && amazon-linux-extras install $1 -y
yum -y install php php-cli php-json php-xml php-opcache php-fpm php-intl php-mbstring gd gd-devel php-gd php-zip wget unzip
systemctl start nginx && systemctl enable nginx.service && systemctl enable php-fpm.service

# INSTALL AND CONFIGURE GIT
yum -y install git

# INSTALL AND CONFIGURE COMPOSER
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"   \
&& php composer-setup.php --install-dir=/usr/local/bin --filename=composer  \
&& php -r "unlink('composer-setup.php');"   \
&& ln -s /usr/local/bin/composer /usr/local/bin/composer.phar   \
&& composer self-update --1 \
&& composer --version \
&& composer

# INSTALL AND CONFIGURE DRUSH
curl -sS https://getcomposer.org/installer | php\
&& mv composer.phar /usr/local/bin/composer \
&& ln -s /usr/local/bin/composer /usr/bin/composer  \
&& git clone https://github.com/drush-ops/drush.git /usr/local/src/drush \
&& cd /usr/local/src/drush && git checkout 9.x  \
&& ln -s /usr/local/src/drush/drush /usr/bin/drush
cd /usr/local/src/drush && composer install

# Install and Configure Drupal Console -> https://github.com/hechoendrupal/drupal-console-launcher
cd /tmp && curl https://github.com/hechoendrupal/drupal-console-launcher/releases/download/1.9.7/drupal.phar -L -o drupal.phar  \
&& mv drupal.phar /usr/local/bin/drupal \
&& chmod +x /usr/local/bin/drupal

#INSTALAR MYSQL -> https://tecadmin.net/how-to-install-mysql-8-on-amazon-linux-2/
yum -y install https://dev.mysql.com/get/mysql80-community-release-el7-5.noarch.rpm
amazon-linux-extras install epel -y
yum -y install mysql-community-server
systemctl enable --now mysqld
# FIN INSTALAR MYSQL

# RM Nginx default files:
rm /usr/share/nginx/html/50x.html /usr/share/nginx/html/index.html

# Config Folders
mkdir /usr/share/nginx/html/web/sites/default/files
mkdir /usr/share/nginx/html/web/sites/default/files/translations
cd  /usr/share/nginx/html/web/sites/default/ && chmod -R 777 files

# https://stackoverflow.com/questions/60047819/drupal-installation-default-page-not-loading-css
