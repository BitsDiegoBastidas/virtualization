FROM amazonlinux:latest
ARG PHP_VERSION
ARG DRUSH_VERSION
# Install and configure Nginx
COPY nginx.repo /etc/yum.repos.d/
RUN yum -y update && yum -y install nginx && yum -y install initscripts
COPY default.conf /etc/nginx/conf.d/
# INSTALL AND CONFIGURE PHP
RUN yum install gcc-c++ zlib-devel amazon-linux-extras -y
RUN amazon-linux-extras enable ${PHP_VERSION} && amazon-linux-extras install ${PHP_VERSION} -y
RUN yum -y install php php-cli php-json php-xml php-opcache php-fpm php-intl php-mbstring gd gd-devel php-gd php-zip wget unzip
RUN systemctl enable nginx.service && systemctl enable php-fpm.service
#RUN systemctl enable nginx.service && systemctl enable php-fpm.service && systemctl restart php-fpm.service && systemctl restart nginx.service
# Install and configure GIT
RUN yum -y install git
# Install and Configure Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"   \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer  \
    && php -r "unlink('composer-setup.php');"                                   \
    && ln -s /usr/local/bin/composer /usr/local/bin/composer.phar               \
    && composer self-update --1                                                 \
    && composer --version

# Install and Configure Drush
RUN curl -sS https://getcomposer.org/installer | php    \
    && mv composer.phar /usr/local/bin/composer         \
    && ln -s /usr/local/bin/composer /usr/bin/composer  \
    && git clone https://github.com/drush-ops/drush.git /usr/local/src/drush \
    && cd /usr/local/src/drush && git checkout ${DRUSH_VERSION}      \
    && ln -s /usr/local/src/drush/drush /usr/bin/drush
RUN cd /usr/local/src/drush && composer install

#Install and Configure Drupal Console -> https://github.com/hechoendrupal/drupal-console-launcher
RUN cd /tmp && curl https://github.com/hechoendrupal/drupal-console-launcher/releases/download/1.9.7/drupal.phar -L -o drupal.phar  \
    && mv drupal.phar /usr/local/bin/drupal \
    && chmod +x /usr/local/bin/drupal

# FIX PHP-FPM
COPY www.conf /etc/php-fpm.d
RUN sed -i 's/listen.acl_users/;listen.acl_users/g' /etc/php-fpm.d/www.conf
RUN sed -i 's/listen.acl_groups/;listen.acl_groups/g' /etc/php-fpm.d/www.conf
