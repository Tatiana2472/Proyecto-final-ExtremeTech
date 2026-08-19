@include('errors.plantilla', [
    'codigo'  => 403,
    'titulo'  => 'No tiene acceso a esta sección',
    'mensaje' => $exception?->getMessage() ?: 'Esta parte del sistema está reservada. Si cree que es un error, revise con qué cuenta inició sesión.',
    'icono'   => 'bi-shield-lock',
])
