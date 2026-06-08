# TaskFlow — Sistema Web de Gestión de Tareas

Aplicación web para gestionar tareas mediante operaciones CRUD, con autenticación
de usuarios y almacenamiento en MySQL.

**Tecnologías:** PHP (PDO) · MySQL **o** SQLite · Bootstrap 5 · HTML/CSS · JavaScript.

> 🌐 **Demo en línea (GitHub Pages):** existe una versión **estática** de la app en la
> carpeta [`docs/`](docs), pensada solo para mostrar la interfaz y el flujo. Guarda los
> datos en el navegador del visitante (`localStorage`), **no** es la app PHP real ni
> tiene backend multiusuario. Ver [Demo estática para GitHub Pages](#demo-estática-para-github-pages).

---

## Funcionalidades

- **Autenticación**: registro, inicio y cierre de sesión, validación de credenciales.
- **CRUD de tareas**: crear, listar, editar y eliminar tareas (título, descripción, fecha).
- **Estados**: Pendiente · En progreso · Completada.
- **Dashboard**: resumen de tareas totales, pendientes, en progreso y completadas, con barra de progreso.
- **Búsqueda y filtros**: por nombre (título) y por estado.
- **Multiusuario**: cada usuario ve y gestiona únicamente sus propias tareas.
- **Diseño responsive**: adaptado a computador, tablet y móvil (tabla en pantallas grandes, tarjetas en móvil).

---

## Estructura del proyecto

```
TaskFlow/
├── index.php              Dashboard (panel principal)
├── setup.php              Instalador MySQL (eliminar tras usarlo)
├── setup_sqlite.php       Instalador SQLite + datos de muestra (eliminar tras usarlo)
├── config/
│   └── database.php       Selección de motor (sqlite/mysql) y conexión PDO
├── database/
│   └── taskflow.sqlite    Base de datos SQLite (se genera con setup_sqlite.php)
├── includes/
│   ├── functions.php      Sesión, autenticación, CSRF, helpers
│   ├── header.php         <head> + navbar responsive
│   └── footer.php         Pie + scripts
├── auth/
│   ├── login.php          Inicio de sesión
│   ├── register.php       Registro de usuarios
│   └── logout.php         Cierre de sesión
├── tasks/
│   ├── index.php          Listado + búsqueda + filtros
│   ├── create.php         Crear tarea
│   ├── edit.php           Editar tarea / cambiar estado
│   └── delete.php         Eliminar tarea (POST + CSRF)
├── assets/
│   ├── css/style.css
│   └── js/app.js
└── sql/
    ├── schema.sql         Esquema para MySQL
    └── schema.sqlite.sql  Esquema para SQLite
```

---

## Instalación

### Requisitos
- PHP 7.4 o superior (con extensión PDO MySQL).
- MySQL 5.7+ o MariaDB 10.2+.
- Un servidor web. Lo más sencillo es **XAMPP**, **Laragon** o **MAMP**.

### Pasos

1. **Copia el proyecto** a la carpeta pública de tu servidor:
   - XAMPP: `htdocs/TaskFlow`
   - Laragon: `www/TaskFlow`

2. **Configura la conexión** en [`config/database.php`](config/database.php)
   (por defecto: host `127.0.0.1`, usuario `root`, sin contraseña).

3. **Crea la base de datos.** El motor se elige en [`config/database.php`](config/database.php)
   con `DB_DRIVER` (`sqlite` por defecto, o `mysql`).

   **Opción SQLite (por defecto, no requiere servidor MySQL):**
   - El repositorio **ya incluye** `database/taskflow.sqlite` con datos de
     muestra, por lo que al clonar/descargar el proyecto **funciona al instante**:
     solo abre `http://localhost/TaskFlow/`.
   - Si necesitas **recrear** la base de datos desde cero, abre
     `http://localhost/TaskFlow/setup_sqlite.php` en el navegador
     (o ejecuta `php setup_sqlite.php` en consola). Crea el archivo
     `database/taskflow.sqlite` **con datos de muestra** listos para probar.
   - Cuentas de ejemplo (contraseña `password123`): `ana@taskflow.test`,
     `carlos@taskflow.test`. Usa `?fresh=1` para recargar los datos desde cero.
   - Después **elimina `setup_sqlite.php`**.

   **Opción MySQL** (pon `DB_DRIVER = 'mysql'`), de una de estas dos formas:
   - **Rápida:** abre `http://localhost/TaskFlow/setup.php`. Después **elimina `setup.php`**.
   - **Manual:** importa [`sql/schema.sql`](sql/schema.sql) desde
     phpMyAdmin o con: `mysql -u root -p < sql/schema.sql`

4. **Abre la aplicación** en `http://localhost/TaskFlow/` y, la primera vez,
   crea tu cuenta desde **Registrarse**.

---

## Seguridad incluida

- Contraseñas cifradas con `password_hash()` / `password_verify()` (bcrypt).
- Consultas con **sentencias preparadas** (PDO) — protección contra inyección SQL.
- Escape de salida con `htmlspecialchars()` — protección contra XSS.
- **Tokens CSRF** en todos los formularios que modifican datos.
- Regeneración de id de sesión al iniciar sesión.
- Cada consulta de tareas filtra por `user_id`: un usuario no puede acceder a tareas de otro.

> Nota de producción: si publicas el sitio bajo HTTPS, configura las cookies de
> sesión como `secure`, y elimina `setup.php`.

---

## Demo estática para GitHub Pages

GitHub Pages **solo sirve archivos estáticos y no ejecuta PHP**, por lo que la app
PHP real no puede alojarse ahí. Para tener una vitrina pública se incluye una
**réplica estática** en la carpeta [`docs/`](docs), construida con HTML, Bootstrap 5
y JavaScript puro. Reproduce la misma interfaz y el mismo flujo (login, dashboard,
CRUD, búsqueda y filtros), pero guardando los datos en el navegador del visitante
mediante `localStorage`.

**Limitaciones de la demo** (por diseño, al no tener servidor):

- Los datos viven solo en el navegador de cada visitante; no se comparten ni persisten en un servidor.
- El "login" es simulado: no hay sesiones, hashing real ni protección CSRF/backend.
- Trae datos de muestra precargados (cuentas `ana@taskflow.test` y `carlos@taskflow.test`, contraseña `password123`).

### Cómo publicarla

1. Sube el repositorio a GitHub.
2. En GitHub: **Settings → Pages**.
3. En *Build and deployment → Source* elige **Deploy from a branch**.
4. Selecciona la rama (`main`) y la carpeta **`/docs`**. Guarda.
5. Espera 1–2 minutos: la demo quedará en `https://<usuario>.github.io/<repositorio>/`.

> Para **probarla localmente**: `cd docs && python3 -m http.server 4599`
> y abre `http://localhost:4599/`.

Para desplegar la **aplicación PHP real** (con base de datos compartida y multiusuario)
necesitas un hosting que ejecute PHP (Render, Railway, Fly.io, hosting compartido o un VPS).
