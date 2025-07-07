# Test Project - Desarrollo de Componentes Joomla con Docker

Entorno de desarrollo completo con Docker para crear componentes Joomla y plugins WordPress con soporte para múltiples CMS y base de datos MySQL.

## 🚀 Características del Proyecto

- **Entorno Docker completo** con WordPress, Joomla, MySQL y phpMyAdmin
- **Componente Joomla** `com_proyectos` para gestión de proyectos
- **Plugin WordPress** para integración HubSpot
- **Base de datos MySQL** preconfigurada
- **Desarrollo rápido** con hot reload

## 📋 Servicios Incluidos

| Servicio | URL | Puerto | Usuario | Contraseña |
|----------|-----|--------|---------|------------|
| WordPress | http://localhost:8080 | 8080 | admin | admin123 |
| Joomla | http://localhost:3003 | 3003 | admin | admin123 |
| phpMyAdmin | http://localhost:3002 | 3002 | devuser | devpass123 |
| MySQL | localhost:3307 | 3307 | devuser | devpass123 |

## 🛠️ Instalación Rápida

### Prerrequisitos

- Docker Desktop instalado y ejecutándose
- Docker Compose
- Git

### Pasos de instalación

1. **Clonar el repositorio**
```bash
git clone <tu-repositorio>
cd Test_Project
```

2. **Construir y ejecutar los contenedores**
```bash
docker-compose up -d --build
```

3. **Completar instalación de Joomla**
- Ve a: http://localhost:3003/installation/
- **Base de datos:**
    - Servidor: `mysql`
    - Usuario: `devuser`
    - Contraseña: `devpass123`
    - Base de datos: `joomla_db`
- **Usuario admin:**
    - Usuario: `admin`
    - Contraseña: `admin123`
    - Email: `admin@test.com`

4. **Probar el componente**
```
http://localhost:3003/index.php?option=com_proyectos&view=proyectos
```

## 📁 Estructura del Proyecto

```
Test_Project/
├── docker/                          # Configuraciones Docker
│   ├── mysql/                       # Configuración MySQL
│   ├── nginx/                       # Configuración Nginx
│   └── php/                         # Configuración PHP
├── logs/                            # Logs de servicios
│   ├── mysql/
│   ├── nginx/
│   └── php/
├── src/                             # Código fuente
│   ├── hubspot/                     # Plugin HubSpot
│   ├── joomla/                      # Instalación Joomla
│   │   └── components/
│   │       └── com_proyectos/       # 🎯 Componente principal
│   │           ├── proyectos.php    # Controlador principal
│   │           ├── controller.php   # Controlador de acciones
│   │           ├── models/          # Modelos de datos
│   │           │   ├── proyectos.php
│   │           │   └── proyecto.php
│   │           └── views/           # Vistas HTML
│   │               ├── proyectos/   # Lista de proyectos
│   │               └── formulario/  # Crear proyecto
│   └── wordpress/                   # Plugins WordPress
├── .env                            # Variables de entorno
├── docker-compose.yml              # Configuración Docker
├── Makefile                        # Comandos automatizados
└── README.md                       # Este archivo
```

## 🎯 Componente com_proyectos

### Funcionalidades

- **📋 Listar proyectos**: Vista completa de todos los proyectos
- **➕ Crear proyecto**: Formulario para nuevos proyectos
- **🗄️ Base de datos**: Tabla `#__proyectos` con campos:
    - `id` (Primary Key)
    - `nombre` (VARCHAR 255)
    - `fecha_inicio` (DATE)
    - `estado` (VARCHAR 50)

### URLs del Componente

```bash
# Listar todos los proyectos
http://localhost:3003/index.php?option=com_proyectos&view=proyectos

# Formulario crear proyecto
http://localhost:3003/index.php?option=com_proyectos&view=formulario

# Guardar proyecto (POST)
http://localhost:3003/index.php?option=com_proyectos&task=guardar
```

### Estructura MVC

**Modelo** (`models/proyectos.php`):
- Conexión con base de datos
- CRUD de proyectos
- Validaciones

**Vista** (`views/proyectos/`):
- Renderizado HTML
- Templates responsive
- Formularios

**Controlador** (`controller.php`):
- Lógica de negocio
- Gestión de requests
- Redirecciones

## 🛠️ Comandos de Desarrollo

### Docker

```bash
# Iniciar todos los servicios
docker-compose up -d

# Ver logs en tiempo real
docker-compose logs -f

# Reiniciar un servicio específico
docker-compose restart joomla

# Parar todos los servicios
docker-compose down

# Reconstruir contenedores
docker-compose up -d --build

# Acceder al contenedor de Joomla
docker-compose exec joomla bash

# Acceder a MySQL
docker-compose exec mysql mysql -u devuser -pdevpass123
```

### Base de Datos

```bash
# Conectar a MySQL
mysql -h localhost -P 3307 -u devuser -pdevpass123

# Usar base de datos de Joomla
USE joomla_db;

# Ver tabla de proyectos
SELECT * FROM j_proyectos;

# Crear nuevo proyecto
INSERT INTO j_proyectos (nombre, fecha_inicio, estado) 
VALUES ('Mi Proyecto', '2025-01-01', 'activo');
```

## 🔧 Configuración de Desarrollo

### Variables de Entorno (.env)

```env
# MySQL Configuration
MYSQL_ROOT_PASSWORD=rootpassword
MYSQL_DATABASE=joomla_db
MYSQL_USER=devuser
MYSQL_PASSWORD=devpass123

# Joomla Configuration
JOOMLA_DB_HOST=mysql:3306
JOOMLA_DB_USER=devuser
JOOMLA_DB_PASSWORD=devpass123
JOOMLA_DB_NAME=joomla_db

# WordPress Configuration
WORDPRESS_DB_HOST=mysql:3306
WORDPRESS_DB_USER=devuser
WORDPRESS_DB_PASSWORD=devpass123
WORDPRESS_DB_NAME=wordpress_db

# Ports
JOOMLA_PORT=3003
WORDPRESS_PORT=8080
PHPMYADMIN_PORT=3002
MYSQL_PORT=3307
```

### Personalizar PHP

Edita `docker/php/php.ini`:

```ini
# Configuración para desarrollo
error_reporting = E_ALL & ~E_WARNING & ~E_DEPRECATED
display_errors = On
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 256M
max_execution_time = 300
```

## 🐛 Solución de Problemas

### Joomla no carga

```bash
# Verificar que los contenedores estén corriendo
docker-compose ps

# Reiniciar servicios
docker-compose restart

# Verificar logs
docker-compose logs joomla
```

### Error de base de datos

```bash
# Verificar conexión MySQL
docker-compose exec mysql mysql -u devuser -pdevpass123 -e "SHOW DATABASES;"

# Recrear base de datos
docker-compose exec mysql mysql -u root -prootpassword -e "DROP DATABASE IF EXISTS joomla_db; CREATE DATABASE joomla_db;"
```

### Problemas de permisos

```bash
# Cambiar permisos
sudo chown -R $USER:$USER src/
chmod -R 755 src/joomla/
```

### Puerto ocupado

Si el puerto 3003 está ocupado, cambiar en `docker-compose.yml`:

```yaml
joomla:
  ports:
    - "3004:80"  # Cambiar puerto
```

## 📚 Desarrollo del Componente

### Agregar nueva funcionalidad

1. **Modelo**: Crear método en `models/proyectos.php`
```php
public function miFuncion() {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    // Tu lógica aquí
}
```

2. **Vista**: Crear template en `views/proyectos/tmpl/`
```php
<?php defined('_JEXEC') or die; ?>
<div class="mi-vista">
    <!-- Tu HTML aquí -->
</div>
```

3. **Controlador**: Agregar tarea en `controller.php`
```php
public function miTarea() {
    // Tu lógica aquí
    $this->setRedirect('index.php?option=com_proyectos');
}
```
