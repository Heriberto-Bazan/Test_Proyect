# Makefile para gestión del proyecto Docker
.PHONY: help build up down restart logs shell-php shell-mysql clean install-wp install-joomla

# Variables
COMPOSE_FILE=docker-compose.yml
PROJECT_NAME=cms-integration

# Comando por defecto
help: ## Mostrar ayuda
	@echo "Comandos disponibles:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $1, $2}'

# Gestión de contenedores
build: ## Construir las imágenes Docker
	@echo "Construyendo imágenes Docker..."
	docker-compose -f $(COMPOSE_FILE) build --no-cache

up: ## Levantar todos los servicios
	@echo "Levantando servicios..."
	docker-compose -f $(COMPOSE_FILE) up -d
	@echo "Servicios levantados!"
	@echo "URLs disponibles:"
	@echo "   - WordPress: http://localhost:3001"
	@echo "   - Joomla: http://localhost:3003"
	@echo "   - HubSpot: http://localhost:3004"
	@echo "   - phpMyAdmin: http://localhost:3002"

down: ## Bajar todos los servicios
	@echo "Bajando servicios..."
	docker-compose -f $(COMPOSE_FILE) down

restart: ## Reiniciar todos los servicios
	@echo "Reiniciando servicios..."
	docker-compose -f $(COMPOSE_FILE) down
	docker-compose -f $(COMPOSE_FILE) up -d

stop: ## Parar los servicios sin eliminar contenedores
	@echo "Parando servicios..."
	docker-compose -f $(COMPOSE_FILE) stop

start: ## Iniciar servicios parados
	@echo "Iniciando servicios..."
	docker-compose -f $(COMPOSE_FILE) start

# Logs y debugging
logs: ## Ver logs de todos los servicios
	docker-compose -f $(COMPOSE_FILE) logs -f

logs-php: ## Ver logs de PHP
	docker-compose -f $(COMPOSE_FILE) logs -f php

logs-nginx: ## Ver logs de Nginx
	docker-compose -f $(COMPOSE_FILE) logs -f nginx

logs-mysql: ## Ver logs de MySQL
	docker-compose -f $(COMPOSE_FILE) logs -f mysql

# Acceso a contenedores
shell-php: ## Acceder al contenedor PHP
	@echo "Accediendo al contenedor PHP..."
	docker-compose -f $(COMPOSE_FILE) exec php bash

shell-mysql: ## Acceder al contenedor MySQL
	@echo "Accediendo al contenedor MySQL..."
	docker-compose -f $(COMPOSE_FILE) exec mysql mysql -u devuser -p

shell-nginx: ## Acceder al contenedor Nginx
	@echo "Accediendo al contenedor Nginx..."
	docker-compose -f $(COMPOSE_FILE) exec nginx sh

# Instalación de CMS
install-wp: ## Descargar e instalar WordPress
	@echo "Descargando WordPress..."
	@if [ ! -d "./src/wordpress" ]; then mkdir -p ./src/wordpress; fi
	@if [ ! -f "./src/wordpress/wp-config.php" ]; then \
		wget -O /tmp/wordpress.tar.gz https://wordpress.org/latest.tar.gz; \
		tar -xzf /tmp/wordpress.tar.gz -C ./src/; \
		rm /tmp/wordpress.tar.gz; \
		echo "WordPress descargado en ./src/wordpress"; \
	else \
		echo "WordPress ya está instalado"; \
	fi

install-joomla: ## Descargar e instalar Joomla
	@echo "Descargando Joomla..."
	@if [ ! -d "./src/joomla" ]; then mkdir -p ./src/joomla; fi
	@if [ ! -f "./src/joomla/configuration.php" ]; then \
		wget -O /tmp/joomla.tar.gz https://github.com/joomla/joomla-cms/releases/download/4.4.2/Joomla_4.4.2-Stable-Full_Package.tar.gz; \
		tar -xzf /tmp/joomla.tar.gz -C ./src/joomla/; \
		rm /tmp/joomla.tar.gz; \
		echo "Joomla descargado en ./src/joomla"; \
	else \
		echo "Joomla ya está instalado"; \
	fi

# Limpieza
clean: ## Limpiar contenedores, imágenes y volúmenes
	@echo "Limpiando recursos Docker..."
	docker-compose -f $(COMPOSE_FILE) down -v --remove-orphans
	docker system prune -f
	docker volume prune -f

clean-all: ## Limpieza completa (incluye imágenes)
	@echo "Limpieza completa..."
	docker-compose -f $(COMPOSE_FILE) down -v --remove-orphans --rmi all
	docker system prune -af
	docker volume prune -f

# Gestión de datos
backup-db: ## Hacer backup de las bases de datos
	@echo "Creando backup de bases de datos..."
	@mkdir -p ./backups
	docker-compose -f $(COMPOSE_FILE) exec mysql mysqldump -u devuser -pdevpass123 --all-databases > ./backups/backup-$(shell date +%Y%m%d-%H%M%S).sql
	@echo "Backup creado en ./backups/"

restore-db: ## Restaurar base de datos (especificar archivo: make restore-db FILE=backup.sql)
	@echo "Restaurando base de datos..."
	@if [ -z "$(FILE)" ]; then echo "Especifica el archivo: make restore-db FILE=backup.sql"; exit 1; fi
	docker-compose -f $(COMPOSE_FILE) exec -T mysql mysql -u devuser -pdevpass123 < $(FILE)
	@echo "Base de datos restaurada"

# Desarrollo
dev: ## Entorno de desarrollo completo
	@echo "Configurando entorno de desarrollo..."
	make build
	make up
	make install-wp
	make install-joomla
	@echo "Entorno de desarrollo listo!"

# Status del proyecto
status: ## Mostrar estado de los servicios
	@echo "Estado de los servicios:"
	@docker-compose -f $(COMPOSE_FILE) ps

# Permisos
fix-permissions: ## Corregir permisos de archivos
	@echo "Corrigiendo permisos..."
	sudo chown -R $(shell id -u):$(shell id -g) ./src/
	sudo chmod -R 755 ./src/
	@echo "Permisos corregidos"