<?php
declare(strict_types=1);

if (!isset($clients, $types, $answers, $statuses, $canCreate, $canEdit, $canArchive, $listTitle, $emptyMessage)) {
    http_response_code(404);
    exit;
}

?>
<section class="card clients-card">
    <h2><?= e($listTitle) ?></h2>
    <?php if (!$clients): ?>
        <p class="muted"><?= e($emptyMessage) ?></p>
    <?php else: ?>
    <?php require __DIR__ . '/clientes-buscador.php'; ?>
    <div class="table-wrap">
        <table class="clients-table" id="clients-active-table">
            <thead><tr>
                <th scope="col">N.º</th><th scope="col">Razón social</th><th scope="col">RUC</th>
                <th scope="col">Tipo</th><th scope="col">Certificado</th><th scope="col">Acuerdo de seguridad</th>
                <th scope="col">DDJJ</th><th scope="col">Estado</th>
                <?php if ($canEdit || $canArchive): ?><th scope="col">Acciones</th><?php endif; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($clients as $index => $client): ?>
                <tr data-name="<?= e($client['razon_social']) ?>" data-ruc="<?= e($client['ruc']) ?>" data-certificate="<?= e($client['certificado']) ?>" data-year="<?= $client['fecha_registro'] !== null ? e(substr($client['fecha_registro'], 0, 4)) : '' ?>">
                    <td data-label="N.º"><?= $index + 1 ?></td>
                    <td data-label="Razón social"><?= e($client['razon_social']) ?></td>
                    <td data-label="RUC"><?= $client['ruc'] !== '' ? e($client['ruc']) : '—' ?></td>
                    <td data-label="Tipo"><?= e($types[$client['tipo']] ?? $client['tipo']) ?></td>
                    <td data-label="Certificado"><?= $client['certificado'] !== '' ? e($client['certificado']) : '—' ?></td>
                    <td data-label="Acuerdo de seguridad"><?php clientBadge($client['acuerdo_seguridad'], $answers); ?></td>
                    <td data-label="DDJJ"><?php clientBadge($client['ddjj'], $answers); ?></td>
                    <td data-label="Estado"><?php clientBadge($client['estado'], $statuses); ?></td>
                    <?php if ($canEdit || $canArchive): ?>
                    <td data-label="Acciones"><div class="actions">
                        <?php if ($canEdit): ?><a class="button" href="clientes.php?editar=<?= (int) $client['id'] ?>">Editar</a><?php endif; ?>
                        <?php if ($canArchive): ?>
                        <form method="post" class="inline" data-confirm="<?= e('¿Archivar a ' . $client['razon_social'] . '? Podrás restaurarlo desde Clientes archivados.') ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="accion" value="archivar">
                            <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                            <button type="submit" class="clients-button-archive" aria-label="Borrar <?= e($client['razon_social']) ?>">Borrar</button>
                        </form>
                        <?php endif; ?>
                    </div></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
