export PUID=$(shell id -u)
export PGID=$(shell id -g)

# Инициализация проекта
init: fix-ssh-permissions build start composer-install

fix-ssh-permissions:
	@chmod 0700 ~/.ssh && chmod 0600 ~/.ssh/* && chmod 0644 ~/.ssh/*.pub && ssh-add ~/.ssh/id_ed25519

# Запуск сборки образов
build: fix-ssh-permissions
	@DOCKER_BUILDKIT=1 docker compose -f docker-compose.local.yml build --no-cache --ssh=default --build-arg PUID=${PUID} --build-arg PGID=${PGID}

# Запуск контейнеров в фоне
start: fix-ssh-permissions
	@docker compose -f docker-compose.local.yml up -d

# Перезагрузка контейнеров
restart: fix-ssh-permissions
	@docker-compose -f docker-compose.local.yml restart

# Зайти в контейнер с php-fpm
sh:
	@docker exec -it digital-backend sh

# Остановка контейнеров
stop:
	@docker-compose -f docker-compose.local.yml stop

# Удаление контейнеров
rm:
	@docker-compose -f docker-compose.local.yml down

# Установка зависимостей
composer-install:
	@docker exec -t digital-backend composer install

# Запуск тестов в контейнере
test:
	@docker exec -t digital-backend sh -c "vendor/bin/phpunit -c phpunit.xml.dist --colors"

# Запуск тестов в контейнере
coverage:
	@docker exec -t digital-backend sh -c "XDEBUG_MODE=coverage vendor/bin/phpunit --colors --coverage-text"