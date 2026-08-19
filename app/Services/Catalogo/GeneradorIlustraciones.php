<?php

namespace App\Services\Catalogo;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Genera ilustraciones SVG para los productos, en el mismo estilo que las
 * del catálogo original: fondo con degradado, silueta del equipo según su
 * categoría y el nombre de la marca abajo.
 *
 * Se usa para los productos importados, que de otro modo traerían
 * fotografías y romperían la coherencia visual del catálogo. Además el
 * resultado no depende de internet: las imágenes quedan en el proyecto.
 */
class GeneradorIlustraciones
{
    /** Paletas de degradado. Se elige una de forma estable por producto. */
    private const PALETAS = [
        ['#7e2460', '#491219'],
        ['#367e24', '#124925'],
        ['#1f4e8c', '#10233f'],
        ['#8c5a1f', '#3f2810'],
        ['#1f7f7a', '#0e3a38'],
        ['#5c2f8c', '#2a133f'],
        ['#8c1f2f', '#3f0e15'],
        ['#2f5c8c', '#13293f'],
    ];

    public function __construct(
        private readonly string $carpeta = 'img/productos/generadas',
    ) {}

    /**
     * Crea el archivo SVG del producto y devuelve su ruta relativa.
     */
    public function generar(Product $producto): string
    {
        $relativa = trim($this->carpeta, '/').'/'.$producto->slug.'.svg';
        $destino = public_path($relativa);

        if (! is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0755, true);
        }

        file_put_contents($destino, $this->svg($producto));

        return $relativa;
    }

    /** Arma el SVG completo. */
    private function svg(Product $producto): string
    {
        [$claro, $oscuro] = $this->paleta($producto);
        $silueta = $this->silueta($producto->categoria?->slug ?? '');
        $marca = htmlspecialchars(
            Str::limit((string) ($producto->marca ?: $producto->nombre), 22, ''),
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 450" width="600" height="450" role="img" aria-label="{$marca}">
          <defs>
            <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$claro}"/>
              <stop offset="1" stop-color="{$oscuro}"/>
            </linearGradient>
          </defs>
          <rect width="600" height="450" fill="url(#g)"/>
          <circle cx="530" cy="70" r="140" fill="#ffffff" opacity="0.06"/>
          <circle cx="60" cy="410" r="110" fill="#ffffff" opacity="0.05"/>
          <g>
        {$silueta}
          </g>
          <text x="300" y="426" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif"
                font-size="26" font-weight="600" fill="#ffffff" opacity="0.9">{$marca}</text>
        </svg>
        SVG;
    }

    /**
     * La paleta se deriva del slug, así que un mismo producto siempre
     * recibe el mismo color aunque se regenere la ilustración.
     */
    private function paleta(Product $producto): array
    {
        $indice = crc32($producto->slug) % count(self::PALETAS);

        return self::PALETAS[$indice];
    }

    /** Silueta del equipo según la categoría local. */
    private function silueta(string $categoria): string
    {
        return match ($categoria) {
            'laptops' => $this->laptop(),
            'celulares-y-tablets' => $this->telefono(),
            'audio' => $this->audifonos(),
            'monitores' => $this->monitor(),
            'gaming' => $this->control(),
            default => $this->accesorio(),
        };
    }

    private function laptop(): string
    {
        return <<<'SVG'
            <rect x="140" y="120" width="320" height="196" rx="12" fill="#f4f7fb"/>
            <rect x="154" y="134" width="292" height="168" rx="6" fill="#1e2530"/>
            <rect x="180" y="164" width="130" height="12" rx="6" fill="#ffffff" opacity="0.55"/>
            <rect x="180" y="192" width="200" height="9" rx="4" fill="#ffffff" opacity="0.28"/>
            <rect x="180" y="214" width="150" height="9" rx="4" fill="#ffffff" opacity="0.28"/>
            <path d="M108 316 h384 l26 40 H82 Z" fill="#f4f7fb"/>
            <rect x="252" y="330" width="96" height="8" rx="4" fill="#c3ccd8"/>
        SVG;
    }

    private function telefono(): string
    {
        return <<<'SVG'
            <rect x="222" y="82" width="156" height="286" rx="26" fill="#f4f7fb"/>
            <rect x="236" y="104" width="128" height="238" rx="12" fill="#1e2530"/>
            <rect x="278" y="90" width="44" height="7" rx="4" fill="#c3ccd8"/>
            <rect x="256" y="140" width="72" height="10" rx="5" fill="#ffffff" opacity="0.55"/>
            <rect x="256" y="166" width="88" height="8" rx="4" fill="#ffffff" opacity="0.28"/>
            <rect x="256" y="188" width="60" height="8" rx="4" fill="#ffffff" opacity="0.28"/>
            <circle cx="300" cy="352" r="9" fill="#c3ccd8"/>
        SVG;
    }

    private function audifonos(): string
    {
        return <<<'SVG'
            <path d="M170 250 v-40 a130 130 0 0 1 260 0 v40" fill="none" stroke="#f4f7fb" stroke-width="26" stroke-linecap="round"/>
            <rect x="138" y="228" width="66" height="108" rx="28" fill="#f4f7fb"/>
            <rect x="396" y="228" width="66" height="108" rx="28" fill="#f4f7fb"/>
            <rect x="152" y="246" width="38" height="72" rx="19" fill="#1e2530"/>
            <rect x="410" y="246" width="38" height="72" rx="19" fill="#1e2530"/>
        SVG;
    }

    private function monitor(): string
    {
        return <<<'SVG'
            <rect x="96" y="96" width="408" height="238" rx="14" fill="#f4f7fb"/>
            <rect x="110" y="110" width="380" height="198" rx="8" fill="#1e2530"/>
            <rect x="140" y="140" width="150" height="12" rx="6" fill="#ffffff" opacity="0.55"/>
            <rect x="140" y="168" width="250" height="9" rx="4" fill="#ffffff" opacity="0.28"/>
            <rect x="140" y="190" width="190" height="9" rx="4" fill="#ffffff" opacity="0.28"/>
            <rect x="278" y="334" width="44" height="38" fill="#c3ccd8"/>
            <rect x="210" y="368" width="180" height="16" rx="8" fill="#f4f7fb"/>
        SVG;
    }

    private function control(): string
    {
        return <<<'SVG'
            <path d="M196 168 h208 a86 86 0 0 1 74 130 l-26 44 a44 44 0 0 1-72 4 l-30-40 h-100 l-30 40 a44 44 0 0 1-72-4 l-26-44 a86 86 0 0 1 74-130 Z" fill="#f4f7fb"/>
            <rect x="196" y="222" width="16" height="52" rx="8" fill="#1e2530"/>
            <rect x="178" y="240" width="52" height="16" rx="8" fill="#1e2530"/>
            <circle cx="388" cy="232" r="13" fill="#1e2530"/>
            <circle cx="420" cy="258" r="13" fill="#1e2530"/>
            <circle cx="356" cy="258" r="13" fill="#1e2530"/>
            <circle cx="388" cy="284" r="13" fill="#1e2530"/>
            <circle cx="262" cy="292" r="20" fill="#1e2530"/>
            <circle cx="338" cy="292" r="20" fill="#1e2530"/>
        SVG;
    }

    private function accesorio(): string
    {
        return <<<'SVG'
            <rect x="176" y="150" width="248" height="180" rx="18" fill="#f4f7fb"/>
            <rect x="176" y="150" width="248" height="52" rx="18" fill="#c3ccd8"/>
            <rect x="286" y="150" width="28" height="180" fill="#1e2530" opacity="0.15"/>
            <circle cx="300" cy="262" r="34" fill="#1e2530"/>
            <circle cx="300" cy="262" r="14" fill="#f4f7fb"/>
            <rect x="212" y="348" width="176" height="14" rx="7" fill="#ffffff" opacity="0.35"/>
        SVG;
    }
}
