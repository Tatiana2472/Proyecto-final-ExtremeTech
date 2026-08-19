ExtremeTech — Tienda virtual

Curso: Tecnologías y Sistemas Web II (ITI-523) Proyecto final · Valor 25% · Docente: Ing. Milena Vargas Blanco

Integrantes: Tatiana, Li, Anguar, Camila

Tienda virtual de productos de tecnología desarrollada con PHP + Laravel 12, SQLite como base de datos y Bootstrap 5 en el frontend. Permite a un visitante navegar el catálogo, registrarse, armar su carrito, pagar en línea y dar seguimiento a sus pedidos; y le da a la administración un panel con mantenimiento del catálogo, gestión de pedidos y reportes de ventas en PDF.

La documentación completa (manual de uso, arquitectura, modelo de datos, diagrama de caso de uso y pruebas) está en un documento Word aparte, entregado junto con este repositorio. Este README solo cubre lo necesario para instalar y correr el proyecto.

1. Requisitos
PHP 8.2 o superior (con pdo_sqlite, sqlite3, mbstring, openssl, dom, fileinfo)
Composer 2.x
Apache (XAMPP) o el servidor de desarrollo de Laravel

No se necesita Node.js ni npm: Bootstrap, Bootstrap Icons y Chart.js ya están incluidos en public/vendor/.

2. Instalación
bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed

El último comando crea database/database.sqlite con las tablas y carga datos de demostración (categorías, productos, usuarios e historial de ventas).

Ejecutar la aplicación
bash
php artisan serve

Abra http://localhost:8000. Si prefiere Apache con XAMPP, apunte el DocumentRoot a la carpeta public/ del proyecto.

3. Cuentas de demostración
Rol	Correo	Contraseña
Administrador	admin@extremetech.cr	Admin1234*
Cliente	maria@example.com	Cliente1234*

La pasarela de pago trabaja en modo simulado (no se envían datos a ningún banco real). Con la tarjeta 4111 1111 1111 1111 el pago siempre se aprueba.

4. Pruebas
bash
php artisan test

210 pruebas / 639 aserciones, todas en verde.

Las pruebas corren contra una base SQLite en memoria, por lo que no modifican la base de datos de desarrollo. Los correos (confirmación de compra y recuperación de contraseña) no se envían de verdad en desarrollo: quedan escritos en storage/logs/laravel.log.

5. Tecnologías utilizadas
Backend: PHP 8.2 · Laravel 12 (MVC, Eloquent ORM, migraciones, Blade)
Base de datos: SQLite
Frontend: HTML5 · CSS3 · Bootstrap 5.3 · JavaScript (Fetch API)
PDF: barryvdh/laravel-dompdf
Pruebas: PHPUnit 11
Control de versiones: Git / GitHub
6. Autores
Tatiana
Li
Anguar
Camila
