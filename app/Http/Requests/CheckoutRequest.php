<?php

namespace App\Http\Requests;

use App\Services\Pagos\GestorPasarelas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del formulario de compra (datos de envío + datos de pago).
 *
 * Los campos de pago se exigen solo para el método seleccionado, usando
 * required_if. El monto NO se recibe del formulario: se recalcula siempre en
 * el servidor a partir del carrito (ver PedidoService), de modo que nadie
 * pueda alterar el total desde el navegador.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $metodos = app(GestorPasarelas::class)->metodosDisponibles();

        return [
            // --- Datos de envío ---
            'envio_nombre'    => ['required', 'string', 'min:3', 'max:160'],
            'envio_telefono'  => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{8,}$/'],
            'envio_direccion' => ['required', 'string', 'min:8', 'max:255'],
            'envio_ciudad'    => ['required', 'string', 'max:100'],
            'envio_provincia' => ['required', 'string', Rule::in($this->provincias())],
            'notas'           => ['nullable', 'string', 'max:500'],

            // --- Método de pago ---
            'metodo_pago' => ['required', Rule::in($metodos)],

            // --- Tarjeta de crédito / débito ---
            'nombre_tarjeta' => ['required_if:metodo_pago,tarjeta', 'nullable', 'string', 'max:120'],
            'numero_tarjeta' => [
                'required_if:metodo_pago,tarjeta', 'nullable', 'string', 'max:25',
                'regex:/^[0-9\s-]+$/', 'digits_between:13,19',
            ],
            'mes'            => ['required_if:metodo_pago,tarjeta', 'nullable', 'integer', 'between:1,12'],
            'anio'           => ['required_if:metodo_pago,tarjeta', 'nullable', 'integer', 'between:'.now()->year.','.(now()->year + 20)],
            'cvv'            => ['required_if:metodo_pago,tarjeta', 'nullable', 'digits_between:3,4'],

            // --- PayPal ---
            'correo_paypal' => ['required_if:metodo_pago,paypal', 'nullable', 'email:rfc', 'max:160'],

            // --- SINPE Móvil ---
            'comprobante_sinpe' => [
                'required_if:metodo_pago,sinpe', 'nullable', 'string', 'min:6', 'max:30',
                'regex:/^\d{6,30}$/',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'envio_nombre'      => 'nombre de quien recibe',
            'envio_telefono'    => 'teléfono de contacto',
            'envio_direccion'   => 'dirección de entrega',
            'envio_ciudad'      => 'ciudad',
            'envio_provincia'   => 'provincia',
            'metodo_pago'       => 'método de pago',
            'nombre_tarjeta'    => 'nombre en la tarjeta',
            'numero_tarjeta'    => 'número de tarjeta',
            'mes'               => 'mes de vencimiento',
            'anio'              => 'año de vencimiento',
            'cvv'               => 'código de seguridad',
            'correo_paypal'     => 'correo de PayPal',
            'comprobante_sinpe' => 'número de comprobante',
        ];
    }

    public function messages(): array
    {
        return [
            'required_if'            => 'El campo :attribute es obligatorio para el método de pago elegido.',
            'envio_telefono.regex'   => 'Indique un teléfono válido de al menos 8 dígitos.',
            'metodo_pago.in'         => 'El método de pago seleccionado no está disponible.',
            'cvv.digits_between'     => 'El código de seguridad debe tener 3 o 4 dígitos.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Se quitan espacios y guiones del número de tarjeta para poder
        // validarlo y para que "4111 1111 1111 1111" sea aceptado.
        if ($this->filled('numero_tarjeta')) {
            $this->merge([
                'numero_tarjeta' => preg_replace('/[\s-]/', '', (string) $this->input('numero_tarjeta')),
            ]);
        }
    }

    /** Datos de envío ya validados, listos para el PedidoService. */
    public function datosEnvio(): array
    {
        return [
            'nombre'    => $this->validated('envio_nombre'),
            'telefono'  => $this->validated('envio_telefono'),
            'direccion' => $this->validated('envio_direccion'),
            'ciudad'    => $this->validated('envio_ciudad'),
            'provincia' => $this->validated('envio_provincia'),
            'notas'     => $this->validated('notas'),
        ];
    }

    /** Datos que necesita la pasarela según el método elegido. */
    public function datosPago(): array
    {
        return match ($this->validated('metodo_pago')) {
            'tarjeta' => [
                'nombre' => $this->validated('nombre_tarjeta'),
                'numero' => $this->validated('numero_tarjeta'),
                'mes'    => $this->validated('mes'),
                'anio'   => $this->validated('anio'),
                'cvv'    => $this->validated('cvv'),
            ],
            'paypal' => [
                'correo_paypal' => $this->validated('correo_paypal'),
            ],
            'sinpe' => [
                'comprobante_sinpe' => $this->validated('comprobante_sinpe'),
            ],
            default => [],
        };
    }

    /** @return list<string> */
    public function provincias(): array
    {
        return ['San José', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón'];
    }
}
