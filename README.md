# Test Project - Desarrollo de Componentes Joomla con Docker

Entorno de desarrollo completo con Docker para crear componentes Joomla y plugins WordPress con soporte para múltiples CMS y base de datos MySQL.

## 🚀 Características del Proyecto

- **Entorno Docker completo** con WordPress, Joomla, MySQL y phpMyAdmin
- **Componente Joomla** `com_proyectos` para gestión de proyectos
- **Plugin WordPress** `empresa-clientes` con Custom Post Type, API REST y Bloque Gutenberg
- **Base de datos MySQL** preconfigurada
- **Desarrollo rápido** con hot reload

## 📋 Servicios Incluidos

| Servicio | URL | Puerto | Usuario | Contraseña |
|----------|-----|--------|---------|------------|
| WordPress | http://localhost:3001 | 3001 | admin | admin123 |
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
git clone git@github.com:Heriberto-Bazan/Test_Proyect.git
cd Test_Project
```

2. **Construir y ejecutar los contenedores**
```bash
docker-compose up -d --build
```

3. **Completar instalación de WordPress**
- Ve a: http://localhost:3001
- **Base de datos:**
  - Servidor: `mysql`
  - Usuario: `devuser`
  - Contraseña: `devpass123`
  - Base de datos: `wordpress_db`
- **Usuario admin:**
  - Usuario: `admin`
  - Contraseña: `admin123`
  - Email: `admin@test.com`

4. **Completar instalación de Joomla**
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

5. **Activar el plugin WordPress**
```bash
# Acceder al contenedor de WordPress
docker-compose exec wordpress bash

# Activar el plugin desde WP-CLI (opcional)
wp plugin activate empresa-clientes --allow-root
```

6. **Probar las funcionalidades**

**WordPress:**
```
# API REST personalizada
http://localhost:3001/wp-json/empresa/v1/clientes/

# Panel de administración
http://localhost:3001/wp-admin/edit.php?post_type=clientes
```

**Joomla:**
```
# Componente de proyectos
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
│   ├── wordpress/                   # Instalación WordPress
│   │   └── wp-content/
│   │       └── plugins/
│   │           └── empresa-clientes/    # 🎯 Plugin principal WordPress
│   │               ├── empresa-clientes.php    # Plugin principal
│   │               ├── includes/                # Clases del plugin
│   │               │   ├── class-custom-post-type.php
│   │               │   ├── class-database.php
│   │               │   └── class-rest-api.php
│   │               ├── blocks/                  # Bloques Gutenberg
│   │               │   └── cliente-destacado/
│   │               │       ├── block.json
│   │               │       ├── index.js
│   │               │       └── style.css
│   │               └── assets/                  # Recursos estáticos
│   │                   ├── css/
│   │                   └── js/
│   ├── joomla/                      # Instalación Joomla
│   │   └── components/
│   │       └── com_proyectos/       # 🎯 Componente principal Joomla
│   │           ├── proyectos.php    # Controlador principal
│   │           ├── controller.php   # Controlador de acciones
│   │           ├── models/          # Modelos de datos
│   │           │   ├── proyectos.php
│   │           │   └── proyecto.php
│   │           └── views/           # Vistas HTML
│   │               ├── proyectos/   # Lista de proyectos
│   │               └── formulario/  # Crear proyecto
│   └── hubspot/                     # Integraciones HubSpot
├── .env                            # Variables de entorno
├── docker-compose.yml              # Configuración Docker
├── Makefile                        # Comandos automatizados
└── README.md                       # Este archivo
```

## 🎯 Plugin WordPress: empresa-clientes

### Funcionalidades Implementadas

- **📋 Custom Post Type "clientes"**: Gestión completa de clientes
- **🗄️ Tabla personalizada**: `wp_clientes_extra` con campo `origen_cliente`
- **🔌 API REST**: Endpoint `/wp-json/empresa/v1/clientes/`
- **🧱 Bloque Gutenberg**: Cliente destacado configurable

### Características del Plugin

#### Custom Post Type: clientes
```php
// Campos disponibles:
- Nombre (título del post)
- Correo electrónico (meta field)
- Teléfono (meta field)
- Origen del cliente (tabla wp_clientes_extra)
```

#### Base de Datos
```sql
-- Tabla personalizada creada automáticamente
CREATE TABLE wp_clientes_extra (
    id int(11) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    origen_cliente varchar(50) NOT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY post_id (post_id)
);
```

#### API REST Endpoints

```bash
# Obtener todos los clientes
GET http://localhost:3001/wp-json/empresa/v1/clientes/

# Respuesta esperada:
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Juan Pérez",
            "correo": "juan@example.com",
            "telefono": "555-1234",
            "origen_cliente": "web",
            "fecha_creacion": "2025-01-15"
        }
    ]
}

# Obtener cliente específico
GET http://localhost:3001/wp-json/empresa/v1/clientes/{id}

# Crear nuevo cliente
POST http://localhost:3001/wp-json/empresa/v1/clientes/
Content-Type: application/json

{
    "nombre": "María García",
    "correo": "maria@example.com",
    "telefono": "555-5678",
    "origen_cliente": "feria"
}
```

#### Bloque Gutenberg: Cliente Destacado

**Características:**
- **Selector de cliente**: Dropdown con todos los clientes disponibles
- **Configuración de color**: Color picker para el fondo
- **Vista previa en tiempo real**: Actualización automática en el editor
- **Responsive**: Adaptable a diferentes tamaños de pantalla

**Configuración en el editor:**
```javascript
// Controles disponibles en el sidebar:
- SelectControl: Seleccionar cliente
- ColorPicker: Color de fondo
- ToggleControl: Mostrar/ocultar información adicional
```

### URLs del Plugin WordPress

```bash
# Panel de administración - Clientes
http://localhost:3001/wp-admin/edit.php?post_type=clientes

# Crear nuevo cliente
http://localhost:3001/wp-admin/post-new.php?post_type=clientes

# API REST - Listar clientes
http://localhost:3001/wp-json/empresa/v1/clientes/

# Editor Gutenberg con bloque personalizado
http://localhost:3001/wp-admin/post-new.php
```

## 🎯 Componente Joomla: com_proyectos

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
docker-compose restart wordpress
docker-compose restart joomla

# Parar todos los servicios
docker-compose down

# Reconstruir contenedores
docker-compose up -d --build

# Acceder al contenedor de WordPress
docker-compose exec wordpress bash

# Acceder al contenedor de Joomla
docker-compose exec joomla bash

# Acceder a MySQL
docker-compose exec mysql mysql -u devuser -pdevpass123
```

### WordPress Development

```bash
# WP-CLI en el contenedor
docker-compose exec wordpress wp --allow-root core version

# Activar/desactivar plugin
docker-compose exec wordpress wp --allow-root plugin activate empresa-clientes
docker-compose exec wordpress wp --allow-root plugin deactivate empresa-clientes

# Listar clientes desde WP-CLI
docker-compose exec wordpress wp --allow-root post list --post_type=clientes

# Crear cliente de prueba
docker-compose exec wordpress wp --allow-root post create \
    --post_type=clientes \
    --post_title="Cliente de Prueba" \
    --post_status=publish \
    --meta_input='{"correo":"test@example.com","telefono":"555-0000"}'
```

### Base de Datos

```bash
# Conectar a MySQL
mysql -h localhost -P 3307 -u devuser -pdevpass123

# WordPress Database
USE wordpress_db;

# Ver Custom Post Type clientes
SELECT * FROM wp_posts WHERE post_type = 'clientes';

# Ver tabla personalizada
SELECT * FROM wp_clientes_extra;

# Joomla Database
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

# WordPress Configuration
WORDPRESS_DB_HOST=mysql:3306
WORDPRESS_DB_USER=devuser
WORDPRESS_DB_PASSWORD=devpass123
WORDPRESS_DB_NAME=wordpress_db

# Joomla Configuration
JOOMLA_DB_HOST=mysql:3306
JOOMLA_DB_USER=devuser
JOOMLA_DB_PASSWORD=devpass123
JOOMLA_DB_NAME=joomla_db

# Ports
WORDPRESS_PORT=3001
JOOMLA_PORT=3003
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

# WordPress específico
max_input_vars = 3000
```

## 🧪 Testing y Validación

### Probar WordPress Plugin

```bash
# 1. Verificar que el plugin esté activo
curl http://localhost:3001/wp-json/wp/v2/plugins

# 2. Probar API REST
curl http://localhost:3001/wp-json/empresa/v1/clientes/

# 3. Crear cliente via API
curl -X POST http://localhost:3001/wp-json/empresa/v1/clientes/ \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Test Cliente",
    "correo": "test@test.com",
    "telefono": "555-1234",
    "origen_cliente": "web"
  }'

# 4. Verificar en base de datos
docker-compose exec mysql mysql -u devuser -pdevpass123 -e "
  USE wordpress_db;
  SELECT p.ID, p.post_title, e.origen_cliente 
  FROM wp_posts p 
  LEFT JOIN wp_clientes_extra e ON p.ID = e.post_id 
  WHERE p.post_type = 'clientes';
"
```

### Probar Joomla Component

```bash
# 1. Verificar componente
curl http://localhost:3003/index.php?option=com_proyectos&view=proyectos

# 2. Verificar base de datos
docker-compose exec mysql mysql -u devuser -pdevpass123 -e "
  USE joomla_db;
  SELECT * FROM j_proyectos;
"
```

## 🐛 Solución de Problemas

### WordPress no carga

```bash
# Verificar que los contenedores estén corriendo
docker-compose ps

# Reiniciar servicios
docker-compose restart wordpress

# Verificar logs
docker-compose logs wordpress

# Verificar permisos
docker-compose exec wordpress chown -R www-data:www-data /var/www/html
```

### Plugin no aparece en WordPress

```bash
# Verificar que el plugin esté en la ubicación correcta
docker-compose exec wordpress ls -la /var/www/html/wp-content/plugins/

# Verificar permisos del plugin
docker-compose exec wordpress ls -la /var/www/html/wp-content/plugins/empresa-clientes/

# Activar manualmente
docker-compose exec wordpress wp --allow-root plugin activate empresa-clientes
```

### API REST no funciona

```bash
# Verificar permalinks
docker-compose exec wordpress wp --allow-root rewrite flush

# Verificar que la API esté habilitada
curl http://localhost:3001/wp-json/

# Verificar endpoints registrados
curl http://localhost:3001/wp-json/ | grep empresa
```

### Joomla no carga

```bash
# Verificar que los contenedores estén corriendo
docker-compose ps

# Reiniciar servicios
docker-compose restart joomla

# Verificar logs
docker-compose logs joomla
```

### Error de base de datos

```bash
# Verificar conexión MySQL
docker-compose exec mysql mysql -u devuser -pdevpass123 -e "SHOW DATABASES;"

# Recrear bases de datos
docker-compose exec mysql mysql -u root -prootpassword -e "
  DROP DATABASE IF EXISTS wordpress_db; 
  CREATE DATABASE wordpress_db;
  DROP DATABASE IF EXISTS joomla_db; 
  CREATE DATABASE joomla_db;
"
```

