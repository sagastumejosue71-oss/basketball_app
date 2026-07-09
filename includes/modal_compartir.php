<div class="modal fade" id="modalCompartir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-heading"><i class="bi bi-share-fill me-2"></i>Compartir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2 text-center">
                <p class="text-muted small mb-3">Comparte esta página escaneando el código o copiando el enlace.</p>
                <div id="qrCompartir" class="d-flex justify-content-center align-items-center mx-auto mb-3" style="width:200px;height:200px;background:#fff;border-radius:16px;border:1px solid rgba(123,47,247,.12);"></div>
                <div class="input-group">
                    <input type="text" id="inputEnlaceCompartir" class="form-control" readonly>
                    <button class="btn btn-degradado" type="button" id="btnCopiarEnlace"><i class="bi bi-clipboard me-1"></i>Copiar</button>
                </div>
            </div>
        </div>
    </div>
</div>
