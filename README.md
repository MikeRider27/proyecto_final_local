# Sistema de Ventas y Compras

Aplicación web para la gestión de un negocio: categorías, productos, proveedores,
clientes, compras y ventas, con reportes en PDF y control de acceso por roles.

- **Framework:** Laravel 10 (PHP 8.2)
- **Frontend:** Blade + Vue 2 + Bootstrap 4 (compilado con Laravel Mix)
- **Base de datos:** PostgreSQL
- **Entorno de desarrollo:** Laravel Sail (Docker)
- **Moneda:** Guaraníes (Gs)

## Requisitos

- Docker y Docker Compose
- Acceso a un servidor PostgreSQL

No se necesita PHP ni Composer instalados en la máquina; todo corre dentro de Sail.

> **Credenciales:** nunca se versionan. `.env.example` solo trae valores de
> ejemplo; los datos reales van en `.env` (ignorado por Git). Pedí el `.env` a
> un integrante del equipo o completá los valores manualmente.

## Puesta en marcha

```bash
# 1. Clonar y entrar al proyecto
git clone <repo> proyecto_final_local
cd proyecto_final_local

# 2. Crear el archivo de entorno
cp .env.example .env

# 3. Instalar dependencias PHP (usando un contenedor de Composer)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs

# 4. Levantar el contenedor
./vendor/bin/sail up -d

# 5. Generar la clave de la aplicación
./vendor/bin/sail artisan key:generate

# 6. Ejecutar las migraciones
./vendor/bin/sail artisan migrate

# 7. Compilar los assets (opcional para desarrollo)
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

La aplicación queda disponible en **http://localhost:8094**
(el puerto se controla con `APP_PORT` en `.env`).

## Configuración de la base de datos

El contenedor de Sail **no** levanta un PostgreSQL propio: se conecta a un
servidor PostgreSQL externo. Completar en `.env` con los datos reales del
servidor (no commitear este archivo):

```env
DB_CONNECTION=pgsql
DB_HOST=<host-del-servidor>
DB_PORT=<puerto>
DB_DATABASE=proyectolaravel
DB_USERNAME=<usuario>
DB_PASSWORD=<contraseña>
```

La base indicada en `DB_DATABASE` debe existir en el servidor:

```bash
PGPASSWORD=<contraseña> psql -h <host> -p <puerto> -U <usuario> \
    -c "CREATE DATABASE proyectolaravel;"
```

## Acceso

Las migraciones crean los 3 roles (`Administrador`, `Vendedor`, `Comprador`).
El usuario administrador inicial es:

| Usuario   | Contraseña      |
|-----------|-----------------|
| `ucevito` | `ab/*06011952`  |

Si la base está vacía, insertar el usuario:

```bash
./vendor/bin/sail artisan tinker --execute="
DB::table('users')->updateOrInsert(['usuario'=>'ucevito'], [
  'nombre'=>'Administrador','email'=>'admin@example.com',
  'password'=>bcrypt('ab/*06011952'),'condicion'=>1,'idrol'=>1,
]);"
```

## Roles y permisos

El acceso a los módulos se controla con middlewares (`app/Http/Middleware`):

| Rol           | Módulos                                                              |
|---------------|--------------------------------------------------------------------|
| Administrador | Todo (incluye usuarios y roles)                                    |
| Comprador     | Categorías, productos, proveedores, compras + PDF de compras       |
| Vendedor      | Categorías, productos, clientes, ventas + PDF de ventas            |

## Reportes PDF

Generados con `barryvdh/laravel-dompdf`:

- `GET /listarProductoPdf` — listado de productos
- `GET /pdfCompra/{id}` — comprobante de compra
- `GET /pdfVenta/{id}` — comprobante de venta

## Moneda

Todos los montos se muestran en guaraníes con el formato `Gs 1.275.000`
(sin decimales, punto como separador de miles). Las columnas monetarias en la
base siguen siendo `decimal(11,2)`; el cambio es solo de presentación.

## Comandos útiles

```bash
./vendor/bin/sail up -d            # levantar
./vendor/bin/sail down             # detener
./vendor/bin/sail artisan migrate  # migraciones
./vendor/bin/sail artisan tinker   # consola
./vendor/bin/sail npm run watch    # recompilar assets al vuelo
./vendor/bin/sail shell            # bash dentro del contenedor
```

Sugerencia: crear un alias `alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'`.

## Solución de problemas

- **`all predefined address pools have been fully subnetted`**: hay demasiadas
  redes Docker. Ejecutar `docker network prune -f`.
- **`Bind for 0.0.0.0:8094 failed: port is already allocated`**: cambiar
  `APP_PORT` en `.env` por un puerto libre y `./vendor/bin/sail up -d` de nuevo.
- **Error de conexión a la base**: verificar que el servidor PostgreSQL es
  accesible desde la red, que las credenciales de `.env` son correctas y que la
  base existe.

## 👨‍💻 Autor

Miguel Villalba
Desarrollador Full Stack
✉️ mike.mavc27@gmail.com

## 📄 Licencia

Este proyecto está bajo la licencia MIT.
