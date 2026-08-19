<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de la tienda. Los precios están en colones costarricenses.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalogo() as $slugCategoria => $productos) {
            $categoria = Category::where('slug', $slugCategoria)->first();

            if (! $categoria) {
                continue;
            }

            foreach ($productos as $producto) {
                $producto['category_id'] = $categoria->id;
                $producto['imagen'] = 'img/productos/'.$producto['slug'].'.svg';

                Product::updateOrCreate(['slug' => $producto['slug']], $producto);
            }
        }
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function catalogo(): array
    {
        return [
            'laptops' => [
                [
                    'nombre' => 'Laptop Lenovo IdeaPad 3 15"',
                    'slug' => 'laptop-lenovo-ideapad-3-15',
                    'sku' => 'LAP-LEN-001',
                    'marca' => 'Lenovo',
                    'precio' => 329000,
                    'precio_anterior' => 379000,
                    'existencias' => 12,
                    'destacado' => true,
                    'resumen' => 'Ryzen 5, 8 GB RAM, SSD 512 GB, pantalla Full HD de 15.6".',
                    'descripcion' => 'Ideal para estudiantes y teletrabajo. Procesador AMD Ryzen 5 5500U de 6 núcleos, 8 GB de memoria DDR4 ampliables a 16 GB, disco de estado sólido de 512 GB NVMe y pantalla Full HD antirreflejo de 15.6 pulgadas. Incluye Windows 11 Home, lector de huella y batería de hasta 7 horas.',
                ],
                [
                    'nombre' => 'Laptop HP Pavilion 14 Core i5',
                    'slug' => 'laptop-hp-pavilion-14-core-i5',
                    'sku' => 'LAP-HP-002',
                    'marca' => 'HP',
                    'precio' => 452000,
                    'existencias' => 8,
                    'destacado' => true,
                    'resumen' => 'Intel Core i5 de 12.ª generación, 16 GB RAM, SSD 512 GB.',
                    'descripcion' => 'Equipo delgado de 1.4 kg con procesador Intel Core i5-1235U, 16 GB de RAM, SSD de 512 GB y pantalla IPS de 14 pulgadas. Puertos USB-C con carga rápida, HDMI 2.1 y Wi-Fi 6. Excelente balance entre potencia y portabilidad.',
                ],
                [
                    'nombre' => 'MacBook Air 13" M2',
                    'slug' => 'macbook-air-13-m2',
                    'sku' => 'LAP-APL-003',
                    'marca' => 'Apple',
                    'precio' => 685000,
                    'existencias' => 5,
                    'destacado' => true,
                    'resumen' => 'Chip M2, 8 GB de memoria unificada, SSD 256 GB, hasta 18 h de batería.',
                    'descripcion' => 'Diseño sin ventilador, pantalla Liquid Retina de 13.6 pulgadas con 500 nits, chip Apple M2 de 8 núcleos y hasta 18 horas de autonomía. Incluye MagSafe 3, dos puertos Thunderbolt y cámara FaceTime HD de 1080p.',
                ],
                [
                    'nombre' => 'Laptop Asus TUF Gaming F15',
                    'slug' => 'laptop-asus-tuf-gaming-f15',
                    'sku' => 'LAP-ASU-004',
                    'marca' => 'Asus',
                    'precio' => 598000,
                    'existencias' => 6,
                    'resumen' => 'Core i7, RTX 4050, 16 GB RAM, pantalla 144 Hz.',
                    'descripcion' => 'Portátil para juegos con certificación militar MIL-STD-810H. Procesador Intel Core i7-12700H, tarjeta NVIDIA GeForce RTX 4050 de 6 GB, 16 GB de RAM DDR5 y pantalla Full HD de 144 Hz. Sistema de enfriamiento con dos ventiladores autolimpiantes.',
                ],
                [
                    'nombre' => 'Laptop Dell Inspiron 15 Celeron',
                    'slug' => 'laptop-dell-inspiron-15-celeron',
                    'sku' => 'LAP-DEL-005',
                    'marca' => 'Dell',
                    'precio' => 218000,
                    'existencias' => 15,
                    'resumen' => 'Opción económica: Celeron N4020, 8 GB RAM, SSD 256 GB.',
                    'descripcion' => 'Alternativa accesible para tareas de oficina, navegación y clases virtuales. Procesador Intel Celeron N4020, 8 GB de RAM, SSD de 256 GB y pantalla HD de 15.6 pulgadas.',
                ],
            ],

            'celulares-y-tablets' => [
                [
                    'nombre' => 'Samsung Galaxy A55 5G 256 GB',
                    'slug' => 'samsung-galaxy-a55-5g-256gb',
                    'sku' => 'CEL-SAM-001',
                    'marca' => 'Samsung',
                    'precio' => 245000,
                    'precio_anterior' => 279000,
                    'existencias' => 20,
                    'destacado' => true,
                    'resumen' => 'Pantalla Super AMOLED 120 Hz, cámara de 50 MP, batería 5000 mAh.',
                    'descripcion' => 'Pantalla Super AMOLED de 6.6 pulgadas a 120 Hz, procesador Exynos 1480, 8 GB de RAM y 256 GB de almacenamiento ampliable. Triple cámara con estabilizador óptico y batería de 5000 mAh con carga de 25 W. Resistencia al agua IP67.',
                ],
                [
                    'nombre' => 'Xiaomi Redmi Note 13 Pro',
                    'slug' => 'xiaomi-redmi-note-13-pro',
                    'sku' => 'CEL-XIA-002',
                    'marca' => 'Xiaomi',
                    'precio' => 178000,
                    'existencias' => 25,
                    'resumen' => 'Cámara de 200 MP, carga turbo de 67 W, 8 GB RAM.',
                    'descripcion' => 'Cámara principal de 200 MP con estabilización óptica, pantalla AMOLED de 6.67 pulgadas con 1800 nits de brillo máximo y carga turbo de 67 W que llena la batería en poco más de 45 minutos.',
                ],
                [
                    'nombre' => 'iPhone 15 128 GB',
                    'slug' => 'iphone-15-128gb',
                    'sku' => 'CEL-APL-003',
                    'marca' => 'Apple',
                    'precio' => 545000,
                    'existencias' => 7,
                    'destacado' => true,
                    'resumen' => 'Chip A16 Bionic, Dynamic Island, cámara de 48 MP, USB-C.',
                    'descripcion' => 'Pantalla Super Retina XDR de 6.1 pulgadas, chip A16 Bionic, sistema de dos cámaras con principal de 48 MP y conector USB-C. Incluye Detección de Choques y SOS de emergencia vía satélite.',
                ],
                [
                    'nombre' => 'Tablet Samsung Galaxy Tab A9+',
                    'slug' => 'tablet-samsung-galaxy-tab-a9-plus',
                    'sku' => 'TAB-SAM-004',
                    'marca' => 'Samsung',
                    'precio' => 132000,
                    'existencias' => 14,
                    'resumen' => 'Pantalla de 11", 64 GB, parlantes cuádruples Dolby Atmos.',
                    'descripcion' => 'Tableta de 11 pulgadas a 90 Hz con Snapdragon 695, 64 GB ampliables por microSD y cuatro parlantes con Dolby Atmos. Perfecta para estudiar, ver contenido y tomar notas.',
                ],
                [
                    'nombre' => 'Motorola Moto G24 128 GB',
                    'slug' => 'motorola-moto-g24-128gb',
                    'sku' => 'CEL-MOT-005',
                    'marca' => 'Motorola',
                    'precio' => 89000,
                    'existencias' => 30,
                    'resumen' => 'Pantalla de 90 Hz, batería de 5000 mAh, Android 14.',
                    'descripcion' => 'Teléfono de entrada con pantalla de 6.56 pulgadas a 90 Hz, 128 GB de almacenamiento, batería de 5000 mAh y sonido estéreo Dolby Atmos. Android 14 sin capas innecesarias.',
                ],
            ],

            'audio' => [
                [
                    'nombre' => 'Audífonos Sony WH-1000XM5',
                    'slug' => 'audifonos-sony-wh-1000xm5',
                    'sku' => 'AUD-SON-001',
                    'marca' => 'Sony',
                    'precio' => 215000,
                    'precio_anterior' => 249000,
                    'existencias' => 9,
                    'destacado' => true,
                    'resumen' => 'Cancelación de ruido líder, 30 h de batería, multipunto.',
                    'descripcion' => 'Los audífonos de referencia en cancelación activa de ruido, con ocho micrófonos, 30 horas de batería, carga rápida de 3 minutos para 3 horas de uso y conexión simultánea a dos dispositivos.',
                ],
                [
                    'nombre' => 'Audífonos JBL Tune 520BT',
                    'slug' => 'audifonos-jbl-tune-520bt',
                    'sku' => 'AUD-JBL-002',
                    'marca' => 'JBL',
                    'precio' => 32000,
                    'existencias' => 40,
                    'resumen' => 'Bluetooth 5.3, 57 horas de batería, sonido JBL Pure Bass.',
                    'descripcion' => 'Audífonos inalámbricos livianos y plegables con hasta 57 horas de reproducción, carga rápida por USB-C y controles en el auricular para llamadas y música.',
                ],
                [
                    'nombre' => 'Parlante Bluetooth JBL Flip 6',
                    'slug' => 'parlante-bluetooth-jbl-flip-6',
                    'sku' => 'AUD-JBL-003',
                    'marca' => 'JBL',
                    'precio' => 68000,
                    'existencias' => 18,
                    'resumen' => 'Resistente al agua IP67, 12 horas de reproducción.',
                    'descripcion' => 'Parlante portátil con radiador de graves, certificación IP67 contra agua y polvo, 12 horas de reproducción y función PartyBoost para enlazar varios parlantes.',
                ],
                [
                    'nombre' => 'Micrófono HyperX SoloCast',
                    'slug' => 'microfono-hyperx-solocast',
                    'sku' => 'AUD-HYP-004',
                    'marca' => 'HyperX',
                    'precio' => 42000,
                    'existencias' => 16,
                    'resumen' => 'USB plug and play, silenciador táctil, patrón cardioide.',
                    'descripcion' => 'Micrófono de condensador USB con patrón cardioide, ideal para transmisiones, reuniones y grabación de voz. Incluye base ajustable y compatibilidad con brazos articulados.',
                ],
            ],

            'gaming' => [
                [
                    'nombre' => 'Consola PlayStation 5 Slim',
                    'slug' => 'consola-playstation-5-slim',
                    'sku' => 'GAM-SON-001',
                    'marca' => 'Sony',
                    'precio' => 385000,
                    'existencias' => 6,
                    'destacado' => true,
                    'resumen' => 'Lectora de discos, SSD de 1 TB, control DualSense.',
                    'descripcion' => 'Consola de nueva generación con SSD ultrarrápido de 1 TB, trazado de rayos, salida 4K a 120 Hz y control DualSense con retroalimentación háptica y gatillos adaptativos.',
                ],
                [
                    'nombre' => 'Control Xbox Series inalámbrico',
                    'slug' => 'control-xbox-series-inalambrico',
                    'sku' => 'GAM-MIC-002',
                    'marca' => 'Microsoft',
                    'precio' => 39000,
                    'existencias' => 22,
                    'resumen' => 'Compatible con Xbox, PC y móviles. Conector de audio de 3.5 mm.',
                    'descripcion' => 'Control con superficie texturizada en gatillos y bumpers, botón de compartir dedicado y conexión Bluetooth para PC, tableta y teléfono.',
                ],
                [
                    'nombre' => 'Teclado mecánico Redragon Kumara',
                    'slug' => 'teclado-mecanico-redragon-kumara',
                    'sku' => 'GAM-RED-003',
                    'marca' => 'Redragon',
                    'precio' => 24500,
                    'existencias' => 28,
                    'resumen' => 'Switches azules, retroiluminación RGB, formato TKL en español.',
                    'descripcion' => 'Teclado mecánico compacto con switches azules de accionamiento táctil, anti-ghosting, cable trenzado desmontable y distribución en español de Latinoamérica.',
                ],
                [
                    'nombre' => 'Mouse gamer Logitech G502 Hero',
                    'slug' => 'mouse-gamer-logitech-g502-hero',
                    'sku' => 'GAM-LOG-004',
                    'marca' => 'Logitech',
                    'precio' => 33500,
                    'precio_anterior' => 39000,
                    'existencias' => 24,
                    'resumen' => 'Sensor HERO de 25 600 DPI, 11 botones, pesas ajustables.',
                    'descripcion' => 'Mouse con sensor HERO 25K, once botones programables, rueda de desplazamiento con doble modo y sistema de pesas para ajustar el balance.',
                ],
                [
                    'nombre' => 'Silla gamer Cougar Armor One',
                    'slug' => 'silla-gamer-cougar-armor-one',
                    'sku' => 'GAM-COU-005',
                    'marca' => 'Cougar',
                    'precio' => 145000,
                    'existencias' => 4,
                    'resumen' => 'Respaldo reclinable 180°, cojines lumbar y cervical incluidos.',
                    'descripcion' => 'Silla ergonómica con estructura de acero, espuma de alta densidad, apoyabrazos 2D y reclinación de hasta 180 grados. Soporta hasta 120 kg.',
                ],
            ],

            'monitores' => [
                [
                    'nombre' => 'Monitor LG UltraGear 24" 165 Hz',
                    'slug' => 'monitor-lg-ultragear-24-165hz',
                    'sku' => 'MON-LG-001',
                    'marca' => 'LG',
                    'precio' => 118000,
                    'existencias' => 11,
                    'destacado' => true,
                    'resumen' => 'IPS Full HD, 1 ms, AMD FreeSync Premium.',
                    'descripcion' => 'Monitor para juegos de 23.8 pulgadas con panel IPS Full HD, 165 Hz de refresco, 1 ms de respuesta y compatibilidad con AMD FreeSync Premium. Base ajustable en altura e inclinación.',
                ],
                [
                    'nombre' => 'Monitor Samsung 27" Full HD',
                    'slug' => 'monitor-samsung-27-full-hd',
                    'sku' => 'MON-SAM-002',
                    'marca' => 'Samsung',
                    'precio' => 96000,
                    'existencias' => 13,
                    'resumen' => 'Panel IPS de 27", 75 Hz, modo cuidado visual.',
                    'descripcion' => 'Monitor de 27 pulgadas con marcos delgados en tres lados, panel IPS con 178° de ángulo de visión, filtro de luz azul y modo sin parpadeo para largas jornadas de trabajo.',
                ],
                [
                    'nombre' => 'Monitor Dell UltraSharp 27" 4K',
                    'slug' => 'monitor-dell-ultrasharp-27-4k',
                    'sku' => 'MON-DEL-003',
                    'marca' => 'Dell',
                    'precio' => 298000,
                    'existencias' => 5,
                    'resumen' => 'Resolución 4K, 99 % sRGB, concentrador USB-C de 90 W.',
                    'descripcion' => 'Monitor profesional 4K con cobertura del 99 % del espacio sRGB, calibración de fábrica, USB-C con entrega de 90 W y base con ajuste de altura, giro y pivote.',
                ],
            ],

            'accesorios' => [
                [
                    'nombre' => 'Disco SSD externo Samsung T7 1 TB',
                    'slug' => 'disco-ssd-externo-samsung-t7-1tb',
                    'sku' => 'ACC-SAM-001',
                    'marca' => 'Samsung',
                    'precio' => 72000,
                    'existencias' => 17,
                    'destacado' => true,
                    'resumen' => 'Lectura de hasta 1050 MB/s, USB 3.2, carcasa de aluminio.',
                    'descripcion' => 'Unidad de estado sólido portátil con velocidades de hasta 1050 MB/s de lectura y 1000 MB/s de escritura, protección contra caídas de hasta 2 metros y cifrado por contraseña AES de 256 bits.',
                ],
                [
                    'nombre' => 'Memoria USB Kingston 128 GB',
                    'slug' => 'memoria-usb-kingston-128gb',
                    'sku' => 'ACC-KIN-002',
                    'marca' => 'Kingston',
                    'precio' => 9500,
                    'existencias' => 60,
                    'resumen' => 'USB 3.2 Gen 1, diseño retráctil sin tapa que perder.',
                    'descripcion' => 'Memoria flash de 128 GB con conector USB 3.2 Gen 1, cuerpo retráctil y argolla para llavero. Compatible con Windows, macOS y Linux.',
                ],
                [
                    'nombre' => 'Batería portátil Anker 20 000 mAh',
                    'slug' => 'bateria-portatil-anker-20000-mah',
                    'sku' => 'ACC-ANK-003',
                    'marca' => 'Anker',
                    'precio' => 34500,
                    'existencias' => 21,
                    'resumen' => 'Carga rápida de 22.5 W, USB-C bidireccional, tres salidas.',
                    'descripcion' => 'Batería externa de 20 000 mAh con carga rápida de 22.5 W, puerto USB-C de entrada y salida y pantalla que muestra el porcentaje restante. Carga un teléfono hasta cuatro veces.',
                ],
                [
                    'nombre' => 'Cámara web Logitech C920 HD Pro',
                    'slug' => 'camara-web-logitech-c920-hd-pro',
                    'sku' => 'ACC-LOG-004',
                    'marca' => 'Logitech',
                    'precio' => 46000,
                    'existencias' => 19,
                    'resumen' => 'Video 1080p a 30 fps, dos micrófonos con reducción de ruido.',
                    'descripcion' => 'Cámara web Full HD con enfoque automático, corrección de luz HD y dos micrófonos omnidireccionales. Compatible con Zoom, Teams, Meet y OBS.',
                ],
                [
                    'nombre' => 'Combo teclado y mouse Logitech MK270',
                    'slug' => 'combo-teclado-mouse-logitech-mk270',
                    'sku' => 'ACC-LOG-005',
                    'marca' => 'Logitech',
                    'precio' => 21000,
                    'existencias' => 26,
                    'resumen' => 'Inalámbrico 2.4 GHz, receptor unificador, teclado en español.',
                    'descripcion' => 'Combo inalámbrico con alcance de 10 metros, ocho teclas de acceso directo, hasta 24 meses de batería en el teclado y 12 meses en el mouse.',
                ],
                [
                    'nombre' => 'Base refrigerante para laptop Cooler Master',
                    'slug' => 'base-refrigerante-laptop-cooler-master',
                    'sku' => 'ACC-COO-006',
                    'marca' => 'Cooler Master',
                    'precio' => 18500,
                    'existencias' => 0,
                    'resumen' => 'Cuatro ventiladores, altura ajustable, para laptops de hasta 17".',
                    'descripcion' => 'Base con cuatro ventiladores silenciosos, cuatro niveles de altura y dos puertos USB adicionales. Compatible con equipos de hasta 17 pulgadas.',
                ],
            ],
        ];
    }
}
