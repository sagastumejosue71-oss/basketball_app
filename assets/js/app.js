document.addEventListener('DOMContentLoaded', function () {
    // Navbar: fondo sólido al hacer scroll
    var nav = document.querySelector('.navbar-copa');
    if (nav) {
        var onScroll = function () {
            nav.classList.toggle('scrolled', window.scrollY > 24);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // Confirmación para acciones destructivas en el panel del organizador
    document.querySelectorAll('[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Formulario de encuentros: el campo "Jornada" solo aplica a la fase de grupos
    var selectFase = document.getElementById('selectFase');
    var grupoJornada = document.getElementById('grupoJornada');
    if (selectFase && grupoJornada) {
        var actualizarVisibilidadJornada = function () {
            grupoJornada.style.display = selectFase.value === 'grupos' ? '' : 'none';
        };
        actualizarVisibilidadJornada();
        selectFase.addEventListener('change', actualizarVisibilidadJornada);
    }

    // Auto-cierre de alertas flash
    document.querySelectorAll('.alert[data-autoclose]').forEach(function (alerta) {
        setTimeout(function () {
            var instancia = bootstrap.Alert.getOrCreateInstance(alerta);
            instancia.close();
        }, 4500);
    });

    // Modal de compartir: genera el QR de la página actual y permite copiar el enlace
    var modalCompartir = document.getElementById('modalCompartir');
    if (modalCompartir) {
        modalCompartir.addEventListener('show.bs.modal', function () {
            var url = window.location.href;
            var input = document.getElementById('inputEnlaceCompartir');
            if (input) {
                input.value = url;
            }
            var contenedorQr = document.getElementById('qrCompartir');
            if (contenedorQr) {
                contenedorQr.innerHTML = '';
                if (window.QRCode) {
                    new QRCode(contenedorQr, {
                        text: url,
                        width: 180,
                        height: 180,
                        colorDark: '#241a3a',
                        colorLight: '#ffffff',
                    });
                }
            }
        });

        var btnCopiar = document.getElementById('btnCopiarEnlace');
        if (btnCopiar) {
            btnCopiar.addEventListener('click', function () {
                var input = document.getElementById('inputEnlaceCompartir');
                var textoOriginal = btnCopiar.innerHTML;
                var marcarCopiado = function () {
                    btnCopiar.innerHTML = '<i class="bi bi-check-lg me-1"></i>¡Copiado!';
                    setTimeout(function () { btnCopiar.innerHTML = textoOriginal; }, 2000);
                };
                var copiarConExecCommand = function () {
                    input.removeAttribute('readonly');
                    input.select();
                    input.setSelectionRange(0, input.value.length);
                    try {
                        document.execCommand('copy');
                    } catch (e) { /* noop: último recurso, ya se seleccionó el texto para copiar manualmente */ }
                    input.setAttribute('readonly', true);
                    marcarCopiado();
                };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(input.value).then(marcarCopiado, copiarConExecCommand);
                } else {
                    copiarConExecCommand();
                }
            });
        }
    }
});
