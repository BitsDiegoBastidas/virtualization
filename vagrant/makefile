SERVER_DIR=/usr/share/nginx/html

vagrant.start: vagrant.init execute.composer config.database create.database import.database render.assets
	vagrant ssh

vagrant.init:
	vagrant up

execute.composer:
	@echo "======DELETE COMPOSER.LOCK==========="
	vagrant ssh -- "cd $(SERVER_DIR) && rm -rf composer.lock"
	@echo "======CLEAR COMPOSER CACHE:==========="
	vagrant ssh -- "cd && composer clear-cache"
	@echo "========  COMPOSER AUTH  ============="
	vagrant ssh -- "cd && composer config -a -g http-basic.gitlab.tigocloud.net '$(username)' '$(pass)'"
	@echo "=========COMPOSER INSTALL============="
	vagrant ssh -- "cd $(SERVER_DIR) && composer install -n --prefer-source"
	@echo "=========COMPOSER UPDATE============="
	vagrant ssh -- "cd $(SERVER_DIR) && composer update bits/* -n "
	# @echo $(var)


config.database:
	vagrant ssh -- "cd && sudo systemctl stop mysqld"
	@echo "=================================="
	vagrant ssh -- "cd && sudo systemctl set-environment MYSQLD_OPTS="--skip-grant-tables""
	@echo "=================================="
	vagrant ssh -- "cd && sudo systemctl start mysqld"
	@echo "=================================="

create.database:
	vagrant ssh -- "cd && mysql -uroot -e 'create database $(database_name)'"

import.database:
	@echo "=== IMPORTANDO  $(database_name) POR FAVOR, ESPERE ===="
	vagrant ssh -- "cd && cd $(SERVER_DIR)/virtualizacion/vagrant/database && mysql -u root $(database_name) < db.mysql"
	@echo "=== base de datos $(database_name) Importada correctamente! ===="

render.assets:
	vagrant ssh -- "cd && drush -y config-set system.performance css.preprocess 0"
	vagrant ssh -- "cd && drush -y config-set system.performance js.preprocess 0"



