@include('errors.plantilla', [
    'codigo'  => 500,
    'titulo'  => 'Ocurrió un error en el servidor',
    'mensaje' => 'Ya estamos revisando qué pasó. Vuelva a intentarlo en unos minutos.',
    'icono'   => 'bi-exclamation-triangle',
])
