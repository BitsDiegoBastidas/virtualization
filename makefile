SERVER_DIR=/usr/share/nginx/html

vagrant.start: vagrant.init execute.composer config.database create.database import.database
	cd ./virtualizacion/vagrant && vagrant ssh
vagrant.init:
	cd ./virtualizacion/vagrant && vagrant up
execute.composer:
	@echo "======DELETE COMPOSER.LOCK==========="
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd $(SERVER_DIR) && rm -rf composer.lock"
	@echo "======CLEAR COMPOSER CACHE:==========="
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd && composer clear-cache"
	@echo "========  COMPOSER AUTH  ============="
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd && composer config -a -g http-basic.gitlab.tigocloud.net '$(username)' '$(pass)'"
	@echo "=========COMPOSER INSTALL============="
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd $(SERVER_DIR) && composer install -n --prefer-source"
	@echo "=========COMPOSER UPDATE============="
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd $(SERVER_DIR) && composer update bits/* -n "
	# @echo $(var)
config.database:
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd && sudo systemctl stop mysqld"
	@echo "=================================="
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd && sudo systemctl set-environment MYSQLD_OPTS="--skip-grant-tables""
	@echo "=================================="
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd && sudo systemctl start mysqld"
	@echo "=================================="
create.database:
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd && mysql -uroot -e 'create database $(database_name)'"
import.database:
	@echo "=== IMPORTANDO  $(database_name) POR FAVOR, ESPERE ===="
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd && cd $(SERVER_DIR)/virtualizacion/vagrant/database && mysql -u root $(database_name) < db.mysql"
	@echo "=== base de datos $(database_name) Importada correctamente! ===="
render.assets:
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd && drush -y config-set system.performance css.preprocess 0"
	cd ./virtualizacion/vagrant && vagrant ssh -- "cd && drush -y config-set system.performance js.preprocess 0"



