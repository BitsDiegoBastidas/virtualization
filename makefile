SERVER_DIR=/usr/share/nginx/html
ifeq ($(DRUSH_VERSION),)
DRUSH_VERSION := 9.x
endif
ifeq ($(PHP_VERSION),)
PHP_VERSION := php7.4
endif
vagrant.start: vagrant.init execute.composer config.database create.database import.database execute.shells
	cd ./virtualization/vagrant && vagrant ssh
vagrant.init:
	cd ./virtualization/vagrant && PHP_VERSION=$(PHP_VERSION) DRUSH_VERSION=$(DRUSH_VERSION) vagrant up
execute.composer:
	@echo "======DELETE COMPOSER.LOCK==========="
	cd ./virtualization/vagrant && vagrant ssh -- "cd $(SERVER_DIR) && rm -rf composer.lock"
	@echo "======CLEAR COMPOSER CACHE:==========="
	cd ./virtualization/vagrant && vagrant ssh -- "cd && composer clear-cache"
	@echo "========  COMPOSER AUTH  ============="
	cd ./virtualization/vagrant && vagrant ssh -- "cd && composer config -a -g http-basic.gitlab.tigocloud.net '$(USERNAME)' '$(PASS)'"
	@echo "=========COMPOSER INSTALL============="
	cd ./virtualization/vagrant && vagrant ssh -- "cd $(SERVER_DIR) && composer install -n --prefer-source"
	@echo "=========COMPOSER UPDATE============="
	cd ./virtualization/vagrant && vagrant ssh -- "cd $(SERVER_DIR) && composer update bits/* -n "
	# @echo $(var)
config.database:
	cd ./virtualization/vagrant && vagrant ssh -- "cd && sudo systemctl stop mysqld"
	@echo "=================================="
	cd ./virtualization/vagrant && vagrant ssh -- "cd && sudo systemctl set-environment MYSQLD_OPTS="--skip-grant-tables""
	@echo "=================================="
	cd ./virtualization/vagrant && vagrant ssh -- "cd && sudo systemctl start mysqld"
	@echo "=================================="
create.database:
	cd ./virtualization/vagrant && vagrant ssh -- "cd && mysql -uroot -e 'create database $(DATABASE_NAME)'"
import.database:
	@echo "=== IMPORTANDO  $(DATABASE_NAME) POR FAVOR, ESPERE ===="
	cd ./virtualization/vagrant && vagrant ssh -- "cd && cd $(SERVER_DIR)/virtualization/database && mysql -u root $(DATABASE_NAME) < db.mysql"
	@echo "=== base de datos $(DATABASE_NAME) Importada correctamente! ===="
execute.shells:
	@echo "=== EJECUTANDO SHELL SCRIPTING ==="
	cd ./virtualization/vagrant && vagrant ssh -- "cd && cd $(SERVER_DIR)/virtualization/sync_files && bash git_hooks.sh"
render.assets:
	cd ./virtualization/vagrant && vagrant ssh -- "cd && drush -y config-set system.performance css.preprocess 0"
	cd ./virtualization/vagrant && vagrant ssh -- "cd && drush -y config-set system.performance js.preprocess 0"


vagrant.destroy:
	cd ./virtualization/vagrant && vagrant halt && PHP_VERSION=$(PHP_VERSION) DRUSH_VERSION=$(DRUSH_VERSION) vagrant destroy -f

vagrant.up:
	cd ./virtualization/vagrant && vagrant up
vagrant.end:
	cd ./virtualization/vagrant && vagrant stop
vagrant.ssh:
	cd ./virtualization/vagrant && vagrant ssh

#############################
##########DOCKER#############
#############################

docker.start: docker.init docker.composer docker.database docker.shells docker.render
	cd ./virtualization/docker && docker exec -it oneapp_bo_project bash
docker.init:
	docker builder prune -f
	cd ./virtualization/docker && docker compose build --build-arg PHP_VERSION="$(PHP_VERSION)" --build-arg DRUSH_VERSION="$(DRUSH_VERSION)"
	cd ./virtualization/docker && docker compose up -d
docker.composer:
	docker exec -it oneapp_bo_project rm -rf composer.lock
	docker exec -it oneapp_bo_project composer clear-cache
	docker exec -it oneapp_bo_project composer config -a -g http-basic.gitlab.tigocloud.net '$(USERNAME)' '$(PASS)'
	docker exec -it oneapp_bo_project composer install -n --prefer-source
	docker exec -it oneapp_bo_project composer update bits/* -n
docker.database:
	docker cp ./virtualization/database/db.mysql oneapp_bo_db:/var/lib
	@echo "==============================================="
	docker exec -it oneapp_bo_db pwd
	docker exec -it oneapp_bo_db ls
	@echo "==============================================="
	docker exec -i oneapp_bo_db bash -l -c "mysql -uroot -p12345678 oneapp_bo < db.mysql"
docker.shells:
	docker exec -u 0 -i oneapp_bo_project bash  -c "whoami; cd virtualization/sync_files; bash git_hooks.sh"
docker.up:
	cd ./virtualization/docker && docker compose up -d
docker.end:
	cd ./virtualization/docker && docker compose down
docker.restart:
	cd ./virtualization/docker && docker compose restart
docker.it.project:
	cd ./virtualization/docker && docker exec -it oneapp_bo_project bash
docker.it.bd:
	cd ./virtualization/docker && docker exec -it oneapp_bo_db bash
docker.destroy:
	docker rm -f oneapp_bo_db oneapp_bo_project
	docker rmi oneapp_bo_project:latest
	docker volume rm docker_oneapp_bo
	docker network rm docker_oneapp_bo_net
docker.render:
	@echo "==========="
	@echo "Environment successfully builded... Please, type: 10.5.0.6 into your web browser to render the Drupal Web Site, (If you are in MacOS, type localhost:8081 into your web browser)"
	@echo "==========="

