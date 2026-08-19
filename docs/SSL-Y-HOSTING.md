# Certificado SSL y despliegue en hosting

Cubre los puntos «Utilizar certificado SSL (gratuito)», «Utilizar hosting si es posible
(gratuito)» e «Implementación de HTTPS para transacciones seguras» del documento del
proyecto.

---

## 1. ¿Por qué HTTPS en una tienda virtual?

Sin HTTPS, los datos viajan en texto plano: cualquier persona en la misma red Wi-Fi
podría leer la contraseña del cliente o los datos que escribe en el checkout. Con HTTPS
el tráfico va cifrado con TLS.

Además, como se mencionó en la sesión 11, la desconfianza en los pagos electrónicos es
una de las desventajas del comercio electrónico, y **los sellos de confianza y la
encriptación SSL ayudan a reducirla**. Ninguna pasarela de pago real (Tilopay, BAC,
Onvo Pay, PayPal) autoriza cobros desde un sitio sin certificado.

---

## 2. Lo que la aplicación ya trae implementado

El middleware `app/Http/Middleware/ForzarHttps.php` se aplica a **todas** las peticiones:

| Comportamiento | Cuándo se activa |
|---|---|
| Redirige `http://` a `https://` con un 301 | `APP_ENV=production` o `TIENDA_FORZAR_HTTPS=true` |
| Fuerza que todos los enlaces y formularios generados usen `https://` (`URL::forceScheme`) | igual que arriba |
| Envía `Strict-Transport-Security: max-age=31536000; includeSubDomains` (**HSTS**) | igual que arriba |
| Envía `X-Content-Type-Options: nosniff` | siempre |
| Envía `X-Frame-Options: SAMEORIGIN` | siempre |
| Envía `Referrer-Policy: strict-origin-when-cross-origin` | siempre |

En desarrollo local se deja pasar HTTP para poder trabajar sin certificado. Los tres
últimos encabezados se verifican en la prueba
`SeguridadTest::test_envia_los_encabezados_de_seguridad`.

Además, cuando el sitio ya está en HTTPS conviene marcar las cookies como seguras en
`.env`:

```
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

---

## 3. Certificado SSL gratuito

### 3.1 Opción recomendada: Let's Encrypt (producción)

**Let's Encrypt** es una autoridad certificadora gratuita y automatizada. Los
certificados duran 90 días y se renuevan solos.

En un servidor Linux con Apache:

```bash
sudo apt install certbot python3-certbot-apache
```

```bash
sudo certbot --apache -d taketech.example.com -d www.taketech.example.com
```

Certbot pide un correo, configura el VirtualHost de HTTPS, instala el certificado y crea
la tarea programada de renovación. Se comprueba con:

```bash
sudo certbot renew --dry-run
```

### 3.2 Opción sin configurar nada: SSL del proveedor

Los servicios de la sección 4 (Railway, Render, Fly.io, InfinityFree) **ya incluyen
HTTPS con certificado válido** en el dominio que asignan. Es la vía más rápida para la
entrega del proyecto: no hay que instalar nada.

### 3.3 Certificado local para pruebas (XAMPP en Windows)

Para probar el HTTPS en la computadora se usa un certificado autofirmado. El navegador
mostrará una advertencia (es normal: nadie confía en un certificado que uno mismo firmó).

1. Genere el certificado con OpenSSL, que viene incluido en XAMPP:

```bash
"C:/xampp/apache/bin/openssl.exe" req -x509 -nodes -days 365 -newkey rsa:2048 -keyout "C:/xampp/apache/conf/ssl.key/taketech.key" -out "C:/xampp/apache/conf/ssl.crt/taketech.crt" -subj "/C=CR/ST=San Jose/L=San Jose/O=Take Tech CR/CN=taketech.test"
```

2. Agregue el dominio de prueba a `C:\Windows\System32\drivers\etc\hosts`
   (hay que abrir el archivo como administrador):

```
127.0.0.1    taketech.test
```

3. En `C:\xampp\apache\conf\extra\httpd-vhosts.conf` agregue:

```apache
<VirtualHost *:80>
    ServerName taketech.test
    # Todo el tráfico HTTP se manda a HTTPS
    Redirect permanent / https://taketech.test/
</VirtualHost>

<VirtualHost *:443>
    ServerName taketech.test
    DocumentRoot "C:/xampp/htdocs/taketech/public"

    SSLEngine on
    SSLCertificateFile "conf/ssl.crt/taketech.crt"
    SSLCertificateKeyFile "conf/ssl.key/taketech.key"

    <Directory "C:/xampp/htdocs/taketech/public">
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/taketech-error.log"
    CustomLog "logs/taketech-access.log" common
</VirtualHost>
```

4. En `.env` ponga:

```
APP_URL=https://taketech.test
TIENDA_FORZAR_HTTPS=true
SESSION_SECURE_COOKIE=true
```

5. Limpie la configuración y reinicie Apache desde el panel de XAMPP:

```bash
php artisan config:clear
```

> **Importante:** el `DocumentRoot` debe apuntar a la carpeta `public/`, **nunca** a la
> raíz del proyecto. Si se expone la raíz, quedarían accesibles por internet el archivo
> `.env` (con la `APP_KEY` y las credenciales) y la base de datos SQLite.

---

## 4. Hosting gratuito

### 4.1 Comparación de opciones

| Servicio | SSL incluido | Soporta SQLite | Notas |
|---|---|---|---|
| **Railway** | Sí | Sí, con volumen persistente | Despliegue desde GitHub. Plan gratuito con horas limitadas. |
| **Render** | Sí | Sí, con disco persistente | Igual que Railway; el plan gratuito «duerme» tras un rato sin uso. |
| **Fly.io** | Sí | Sí, con volumen | Requiere Docker; el plan gratuito es generoso. |
| **InfinityFree** | Sí | Solo MySQL | Hosting PHP clásico por FTP. Habría que cambiar la base de datos. |
| **000webhost** | Sí | Solo MySQL | Similar al anterior. |

Para este proyecto **Railway o Render son las mejores opciones**, porque permiten
conservar SQLite (que es lo que exige el documento) y dan HTTPS automáticamente.

### 4.2 Despliegue en Railway (paso a paso)

1. Suba el proyecto a GitHub (sección 5).
2. Cree una cuenta en <https://railway.app> e inicie sesión con GitHub.
3. **New Project › Deploy from GitHub repo** y elija el repositorio.
4. En **Variables**, agregue como mínimo:

```
APP_NAME=Take Tech CR
APP_ENV=production
APP_DEBUG=false
APP_KEY=            # pegue aquí la salida de: php artisan key:generate --show
APP_URL=https://su-proyecto.up.railway.app
DB_CONNECTION=sqlite
DB_DATABASE=/data/database.sqlite
LOG_CHANNEL=stderr
SESSION_SECURE_COOKIE=true
```

5. En **Settings › Volumes**, cree un volumen montado en `/data`. Sin esto la base de
   datos se borraría en cada despliegue.
6. En **Settings › Deploy › Start Command**:

```
php artisan migrate --force --seed && php artisan serve --host=0.0.0.0 --port=$PORT
```

7. Railway asigna un dominio `*.up.railway.app` **con HTTPS ya configurado**.

### 4.3 Lista de verificación antes de publicar

- [ ] `APP_ENV=production` y `APP_DEBUG=false` (con `true` se mostrarían rutas internas y
      fragmentos de código en los errores).
- [ ] `APP_KEY` generada y guardada solo en las variables de entorno.
- [ ] `.env` **no** está en el repositorio (ya está en `.gitignore`).
- [ ] `SESSION_SECURE_COOKIE=true`.
- [ ] El `DocumentRoot` apunta a `public/`.
- [ ] Credenciales reales de la pasarela puestas como variables de entorno y
      `TIENDA_PAGOS_MODO=live`.
- [ ] Cambiada la contraseña del usuario administrador de demostración.
- [ ] Caché de producción generada:

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## 5. GitHub

El proyecto usa Git. Para publicarlo:

1. Cree un repositorio vacío en <https://github.com/new>, por ejemplo
   `proyecto-final-taketech`. **No** marque la opción de agregar README, porque el
   proyecto ya tiene uno.

2. Conéctelo y súbalo:

```bash
git remote add origin https://github.com/USUARIO/proyecto-final-taketech.git
```

```bash
git branch -M main
```

```bash
git push -u origin main
```

3. Agregue a los demás integrantes del grupo en **Settings › Collaborators**.

### Qué queda fuera del repositorio

El archivo `.gitignore` de Laravel excluye:

- `/vendor` — se reinstala con `composer install`.
- `.env` — **contiene la `APP_KEY` y las credenciales; nunca debe subirse.**
- `/node_modules`, cachés y logs.

`.env.example` sí se sube: sirve de plantilla para que otra persona configure su copia.

### Sobre la base de datos

`database/database.sqlite` **no** se sube al repositorio: el archivo
`database/.gitignore` que trae Laravel excluye `*.sqlite*`. Es lo correcto, porque una
base de datos contendría datos de clientes y además generaría conflictos constantes en Git
al ser un archivo binario que cambia con cada compra.

Quien descargue el proyecto reconstruye la base con datos de demostración ejecutando:

```bash
php artisan migrate:fresh --seed
```

Si para la entrega necesita incluirla de todos modos, se puede forzar con
`git add -f database/database.sqlite`.

### Flujo de trabajo sugerido para el grupo

```bash
git checkout -b nombre-de-la-funcionalidad
```

```bash
git add . && git commit -m "Descripción breve del cambio"
```

```bash
git push -u origin nombre-de-la-funcionalidad
```

Luego se abre un *Pull Request* en GitHub para que otro integrante lo revise antes de
integrarlo a `main`.
