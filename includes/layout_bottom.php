<footer class="footer-copa mt-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge-pill-icon"><?= icono_balon(20) ?></span>
                    <span class="fw-heading text-white fs-5"><?= e($torneo['nombre']) ?></span>
                </div>
                <p class="small mb-3"><?= e($torneo['descripcion']) ?></p>
                <?php if (!empty($torneo['instagram'])): ?>
                <a href="<?= url_externa_segura($torneo['instagram']) ?>" target="_blank" rel="noopener" class="small"><i class="bi bi-instagram me-1"></i>Síguenos en Instagram</a>
                <?php endif; ?>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="text-white mb-3">Torneo</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= url('tabla.php') ?>">Tabla de posiciones</a></li>
                    <li><a href="<?= url('calendario.php') ?>">Calendario</a></li>
                    <li><a href="<?= url('equipos.php') ?>">Equipos</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="text-white mb-3">Sitio</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= url('patrocinadores.php') ?>">Patrocinadores</a></li>
                    <li><a href="<?= url('organizador.php') ?>">Organizador</a></li>
                    <li><a href="#" data-bs-toggle="modal" data-bs-target="#modalCompartir">Compartir sitio</a></li>
                    <li><a href="<?= url('login.php') ?>">Panel Organizador</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="text-white mb-3">Sede principal</h6>
                <p class="small mb-1"><i class="bi bi-geo-alt me-2"></i><?= e($torneo['sede_principal'] ?? '') ?></p>
                <p class="small mb-0"><i class="bi bi-calendar3 me-2"></i>Temporada <?= e($torneo['temporada'] ?? '') ?></p>
            </div>
        </div>
        <hr class="border-secondary opacity-25 my-4">
        <p class="small text-center mb-1 opacity-75">© <?= date('Y') ?> <?= e($torneo['nombre']) ?> · <?= e($torneo['subtitulo']) ?></p>
        <p class="small text-center mb-0 opacity-50">By Josué Sagastume</p>
    </div>
</footer>

<?php require __DIR__ . '/modal_compartir.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
