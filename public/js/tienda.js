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

        const fila = document.createElement('div');
        fila.className = 'd-flex';

        // El aviso se arma con nodos y el texto se asigna con textContent, NO
        // con innerHTML: el mensaje viene del servidor e incluye el nombre del
        // producto, que no es texto de confianza (el catálogo se puede
        // importar de una tienda externa con `php artisan catalogo:importar`).
        // Con innerHTML, un nombre con etiquetas HTML se ejecutaría en el
        // navegador de cualquier cliente que agregara ese producto al carrito.
        // Blade ya hace lo equivalente con {{ }} en el resto de la tienda.
        const cuerpo = document.createElement('div');
        cuerpo.className = 'toast-body';
        cuerpo.textContent = mensaje;

        const cerrar = document.createElement('button');
        cerrar.type = 'button';
        cerrar.className = 'btn-close btn-close-white me-2 m-auto';
        cerrar.dataset.bsDismiss = 'toast';
        cerrar.setAttribute('aria-label', 'Cerrar');

        fila.append(cuerpo, cerrar);
        aviso.append(fila);

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
     | Favoritos: marcar y desmarcar sin recargar la página
     |
     | El servidor decide con toggle() si el producto entra o sale de la
     | tabla pivote, y devuelve en «marcado» cómo quedó. El botón se pinta
     | según esa respuesta y no según lo que el navegador supuso.
     | ---------------------------------------------------------------- */

    document.addEventListener('submit', async function (evento) {
        const formulario = evento.target.closest('form[data-favorito]');
        if (!formulario) return;

        evento.preventDefault();

        const boton = formulario.querySelector('button');
        if (boton) boton.disabled = true;

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

            if (!respuesta.ok) {
                avisar(datos.mensaje || 'No se pudo actualizar sus favoritos.', 'danger');
                return;
            }

            if (boton) {
                const icono = boton.querySelector('i');

                boton.classList.toggle('es-favorito', datos.marcado);
                boton.setAttribute('aria-pressed', datos.marcado ? 'true' : 'false');
                boton.title = datos.marcado ? 'Quitar de favoritos' : 'Agregar a favoritos';

                if (icono) {
                    icono.classList.toggle('bi-heart-fill', datos.marcado);
                    icono.classList.toggle('bi-heart', !datos.marcado);
                }
            }

            avisar(datos.mensaje, datos.marcado ? 'success' : 'secondary');
        } catch (e) {
            // Si falla la petición asíncrona se envía el formulario normal.
            formulario.removeAttribute('data-favorito');
            formulario.submit();
            return;
        } finally {
            if (boton) boton.disabled = false;
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

    // El comprobante SINPE es numérico y no debe normalizar letras de forma
    // silenciosa antes de enviarlo al servidor.
    const comprobanteSinpe = document.getElementById('comprobante_sinpe');

    if (comprobanteSinpe) {
        comprobanteSinpe.addEventListener('input', function () {
            comprobanteSinpe.value = comprobanteSinpe.value.replace(/\D/g, '').slice(0, 30);
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
