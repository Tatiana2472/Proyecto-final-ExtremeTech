/* =========================================================================
    ExtremTech - interacciones del lado del cliente
   -------------------------------------------------------------------------
   Ejemplo de comunicación ASÍNCRONA (sesión 11): en lugar de recargar toda la
   página, se envía la petición al servidor con fetch() y se actualiza solo la
   parte de la interfaz que cambió. El servidor responde en JSON.
   ========================================================================= */

(function () {
    'use strict';

    const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    /* ------------------------------------------------------------------
     | Aviso flotante (toast) para no depender de recargar la página
     | ---------------------------------------------------------------- */

    function contenedorAvisos() {
        let contenedor = document.getElementById('tsAvisos');

        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'tsAvisos';
            contenedor.className = 'toast-container position-fixed top-0 end-0 p-3';
            contenedor.style.zIndex = '1090';
            document.body.appendChild(contenedor);
        }

        return contenedor;
    }

    function avisar(mensaje, tipo = 'success') {
        const aviso = document.createElement('div');
        aviso.className = `toast align-items-center text-bg-${tipo} border-0`;
        aviso.setAttribute('role', 'alert');
        aviso.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${mensaje}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>`;

        contenedorAvisos().appendChild(aviso);

        const instancia = new bootstrap.Toast(aviso, { delay: 3500 });
        instancia.show();
        aviso.addEventListener('hidden.bs.toast', () => aviso.remove());
    }

    /* ------------------------------------------------------------------
     | Contador del carrito en el encabezado
     | ---------------------------------------------------------------- */

    async function refrescarContador() {
        const insignia = document.getElementById('contadorCarrito');
        if (!insignia) return;

        try {
            const respuesta = await fetch(insignia.dataset.url || '/carrito/contador', {
                headers: { Accept: 'application/json' },
            });

            if (!respuesta.ok) return;

            const datos = await respuesta.json();
            insignia.textContent = datos.cantidad;
            insignia.classList.toggle('d-none', datos.cantidad === 0);
        } catch (e) {
            /* Si falla la red simplemente se deja el valor que ya estaba. */
        }
    }

    /* ------------------------------------------------------------------
     | Agregar al carrito sin recargar la página
     | ---------------------------------------------------------------- */

    document.addEventListener('submit', async function (evento) {
        const formulario = evento.target.closest('form[data-carrito-agregar]');
        if (!formulario) return;

        evento.preventDefault();

        const boton = formulario.querySelector('button[type="submit"]');
        const textoOriginal = boton ? boton.innerHTML : '';

        if (boton) {
            boton.disabled = true;
            boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Agregando...';
        }

        try {
            const respuesta = await fetch(formulario.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': tokenCsrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(formulario),
            });

            const datos = await respuesta.json().catch(() => ({}));

            if (respuesta.ok) {
                avisar(datos.mensaje || 'Producto agregado al carrito.', 'success');
                refrescarContador();
            } else {
                avisar(datos.mensaje || 'No se pudo agregar el producto.', 'danger');
            }
        } catch (e) {
            // Si la petición asíncrona falla, se envía el formulario de la
            // forma tradicional (sincrónica) para no dejar al usuario sin
            // poder comprar.
            formulario.removeAttribute('data-carrito-agregar');
            formulario.submit();
            return;
        } finally {
            if (boton) {
                boton.disabled = false;
                boton.innerHTML = textoOriginal;
            }
        }
    });

    /* ------------------------------------------------------------------
     | Carrito: los selectores de cantidad envían el formulario solos
     | ---------------------------------------------------------------- */

    document.querySelectorAll('[data-carrito-cantidad]').forEach(function (campo) {
        campo.addEventListener('change', function () {
            campo.closest('form')?.submit();
        });
    });

    document.querySelectorAll('[data-cantidad-paso]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const campo = document.getElementById(boton.dataset.destino);
            if (!campo) return;

            const paso = parseInt(boton.dataset.cantidadPaso, 10);
            const minimo = parseInt(campo.min || '1', 10);
            const maximo = parseInt(campo.max || '20', 10);
            const actual = parseInt(campo.value || '1', 10);

            campo.value = Math.min(maximo, Math.max(minimo, actual + paso));
        });
    });

    /* ------------------------------------------------------------------
     | Checkout: mostrar solo los campos del método de pago elegido
     | ---------------------------------------------------------------- */

    const radiosPago = document.querySelectorAll('input[name="metodo_pago"]');

    function alternarCamposPago() {
        const seleccionado = document.querySelector('input[name="metodo_pago"]:checked')?.value;

        document.querySelectorAll('[data-campos-pago]').forEach(function (bloque) {
            const visible = bloque.dataset.camposPago === seleccionado;
            bloque.classList.toggle('d-none', !visible);

            // Los campos ocultos no deben exigirse en el navegador.
            bloque.querySelectorAll('input, select').forEach(function (campo) {
                if (campo.dataset.obligatorio === 'si') {
                    campo.required = visible;
                }
            });
        });
    }

    if (radiosPago.length) {
        radiosPago.forEach((radio) => radio.addEventListener('change', alternarCamposPago));
        alternarCamposPago();
    }

    /* ------------------------------------------------------------------
     | Formato del número de tarjeta mientras se escribe (4 en 4)
     | ---------------------------------------------------------------- */

    const campoTarjeta = document.getElementById('numero_tarjeta');

    if (campoTarjeta) {
        campoTarjeta.addEventListener('input', function () {
            const soloDigitos = campoTarjeta.value.replace(/\D/g, '').slice(0, 19);
            campoTarjeta.value = soloDigitos.replace(/(.{4})/g, '$1 ').trim();
        });
    }

    /* ------------------------------------------------------------------
     | Evitar doble envío en los formularios de compra
     | ---------------------------------------------------------------- */

    document.querySelectorAll('form[data-un-solo-envio]').forEach(function (formulario) {
        formulario.addEventListener('submit', function () {
            const boton = formulario.querySelector('button[type="submit"]');
            if (boton) {
                boton.disabled = true;
                boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando pago...';
            }
        });
    });

    /* ------------------------------------------------------------------
     | Confirmación antes de acciones destructivas
     | ---------------------------------------------------------------- */

    document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!window.confirm(formulario.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });
})();
