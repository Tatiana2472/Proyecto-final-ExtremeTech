<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Pedido (compra) realizado por un usuario.
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'numero_pedido', 'numero_seguimiento', 'estado',
        'subtotal', 'impuesto', 'envio', 'total', 'tasa_impuesto',
        'metodo_pago', 'estado_pago',
        'envio_nombre', 'envio_telefono', 'envio_direccion',
        'envio_ciudad', 'envio_provincia', 'notas', 'fecha_compra',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'      => 'decimal:2',
            'impuesto'      => 'decimal:2',
            'envio'         => 'decimal:2',
            'total'         => 'decimal:2',
            'tasa_impuesto' => 'decimal:4',
            'fecha_compra'  => 'datetime',
        ];
    }

    /* ----------------------------------------------------------------------
     | Relaciones
     | -------------------------------------------------------------------- */

    /** El pedido pertenece a un usuario. */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** El pedido tiene muchas líneas de detalle. */
    public function lineas(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** El pedido tiene una factura. */
    public function factura(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /** El pedido tiene muchos intentos de pago registrados. */
    public function pagos(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Último pago registrado (el que aparece en el comprobante). */
    public function pago(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /* ----------------------------------------------------------------------
     | Scopes
     | -------------------------------------------------------------------- */

    public function scopePagados(Builder $query): Builder
    {
        return $query->where('estado_pago', 'aprobado');
    }

    public function scopeDelMes(Builder $query, int $anio, int $mes): Builder
    {
        return $query->whereYear('fecha_compra', $anio)
            ->whereMonth('fecha_compra', $mes);
    }

    /* ----------------------------------------------------------------------
     | Generadores de identificadores
     | -------------------------------------------------------------------- */

    /** Consecutivo del pedido, por ejemplo PED-2026-000014. */
    public static function siguienteNumeroPedido(): string
    {
        $consecutivo = static::max('id') + 1;

        return sprintf('PED-%s-%06d', now()->year, $consecutivo);
    }

    /**
     * Número de seguimiento entregado al cliente para rastrear el envío.
     * Se usa una cadena aleatoria para que no sea posible adivinar los
     * pedidos de otras personas a partir del propio.
     */
    public static function generarNumeroSeguimiento(): string
    {
        do {
            $numero = 'TS'.now()->format('ymd').strtoupper(Str::random(6));
        } while (static::where('numero_seguimiento', $numero)->exists());

        return $numero;
    }

    /* ----------------------------------------------------------------------
     | Ayudas
     | -------------------------------------------------------------------- */

    public function estaPagado(): bool
    {
        return $this->estado_pago === 'aprobado';
    }

    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente de pago',
            'pagado'    => 'Pagado',
            'enviado'   => 'Enviado',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
            default     => ucfirst((string) $this->estado),
        };
    }

    public function colorEstado(): string
    {
        return match ($this->estado) {
            'pagado'    => 'success',
            'enviado'   => 'info',
            'entregado' => 'primary',
            'cancelado' => 'danger',
            default     => 'warning',
        };
    }

    public function etiquetaMetodoPago(): string
    {
        return config("tienda.pagos.metodos.{$this->metodo_pago}.etiqueta", ucfirst((string) $this->metodo_pago));
    }

    public function cantidadArticulos(): int
    {
        return (int) $this->lineas()->sum('cantidad');
    }
}
