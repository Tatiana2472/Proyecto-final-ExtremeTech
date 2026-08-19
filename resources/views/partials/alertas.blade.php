{{-- Mensajes de éxito / error y errores de validación --}}

@if (session('exito'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-start" role="alert">
        <i class="bi bi-check-circle-fill me-2 mt-1"></i>
        <div>{{ session('exito') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-start">
            <i class="bi bi-x-octagon-fill me-2 mt-1"></i>
            <div>
                <strong>Revise los siguientes datos:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        {{-- Blade escapa el contenido con {{ }}, evitando XSS --}}
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    </div>
@endif
