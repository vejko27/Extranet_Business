<?php
declare(strict_types=1);

require __DIR__ . '\\auth.php';
require __DIR__ . '\\layout.php';
exigirPermiso('BASC Comercial', 'ver');

$canEdit = puede('BASC Comercial', 'editar');
$canEditDocument = puede('BASC Documento', 'editar');
$questions = [
    'cadena_suministro' => '¿Pertenece a la cadena de suministros del comercio internacional?',
    'contacto_mercaderia' => '¿Está en contacto con la mercadería de exportación o importación o tiene información de la operación?',
    'menos_dos_anos' => '¿Cuenta con menos de dos años de constitución como empresa en marcha?',
    'sin_certificacion' => '¿Carece de una certificación BASC, OEA o ISO 28000?',
    'sin_historial' => '¿Carece de un buen historial crediticio y financiero?',
];
$answers = ['' => 'Sin responder', 'si' => 'Sí', 'no' => 'No'];
$values = array_fill_keys(array_merge(['rubro'], array_keys($questions)), '');
$editing = null;
$error = '';
$notice = $_SESSION['basc_comercial_notice'] ?? '';
unset($_SESSION['basc_comercial_notice']);

try {
    $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
    $action = $isPost ? ($_POST['accion'] ?? 'guardar_evaluacion') : '';
    if ($isPost && !in_array($action, ['guardar_evaluacion', 'guardar_documento'], true)) {
        http_response_code(400);
        exit('Acción no válida.');
    }
    if ($isPost && !validCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('La sesión del formulario ha caducado. Vuelve a cargar la página.');
    }
    $document = $db->query('SELECT codigo, version, fecha FROM basc_documento WHERE id = 1')->fetch();
    if (!$document) {
        throw new RuntimeException('Faltan los datos del documento BASC.');
    }
    $documentDate = DateTimeImmutable::createFromFormat('!Y-m-d', $document['fecha']);
    if (!$documentDate || $documentDate->format('Y-m-d') !== $document['fecha']) {
        throw new RuntimeException('La fecha del documento BASC no es válida.');
    }
    $documentValues = $document;
    $editingDocument = ($isPost && $action === 'guardar_documento') || isset($_GET['editar_documento']);
    if ($editingDocument) {
        if (!$canEditDocument) {
            http_response_code(403);
            exit('Acceso denegado.');
        }
        if ($isPost && $action === 'guardar_documento') {
            foreach (array_keys($documentValues) as $key) {
                if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
                    $error = 'Revisa el código, la versión y la fecha del documento.';
                } else {
                    $documentValues[$key] = trim($_POST[$key]);
                }
            }
            $date = preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $documentValues['fecha']) === 1
                ? DateTimeImmutable::createFromFormat('!Y-m-d', $documentValues['fecha'])
                : false;
            if (preg_match('/\A.{1,100}\z/us', $documentValues['codigo']) !== 1
                || preg_match('/\A.{1,40}\z/us', $documentValues['version']) !== 1
                || !$date || $date->format('Y-m-d') !== $documentValues['fecha']
                || $documentValues['fecha'] < '1000-01-01' || $documentValues['fecha'] > '9999-12-31'
            ) {
                $error = 'Revisa el código, la versión y la fecha del documento.';
            }
            if ($error === '') {
                $statement = $db->prepare('UPDATE basc_documento SET codigo = :codigo, version = :version, fecha = :fecha WHERE id = 1');
                $statement->execute($documentValues);
                if ($statement->rowCount() === 0 && !$db->query('SELECT id FROM basc_documento WHERE id = 1')->fetch()) {
                    throw new RuntimeException('El documento BASC ya no está disponible.');
                }
                $_SESSION['basc_comercial_notice'] = 'Datos del documento actualizados correctamente.';
                header('Location: basc-comercial.php', true, 303);
                exit;
            }
            http_response_code(422);
        }
    }
    if (($isPost && $action === 'guardar_evaluacion') || (!$isPost && isset($_GET['editar']))) {
        if (!$canEdit) {
            http_response_code(403);
            exit('Acceso denegado.');
        }
        $idInput = $isPost ? ($_POST['id'] ?? null) : ($_GET['editar'] ?? null);
        $id = is_string($idInput) && ctype_digit($idInput) ? (int) $idInput : 0;
        $statement = $db->prepare(
            "SELECT c.id, c.razon_social, m.rubro, m.cadena_suministro, m.contacto_mercaderia,
                    m.menos_dos_anos, m.sin_certificacion, m.sin_historial
             FROM clientes c LEFT JOIN basc_matriz_clientes m ON m.cliente_id = c.id
             WHERE c.id = ? AND c.archivado = 0 AND c.estado = 'homologado'"
        );
        $statement->execute([$id]);
        $editing = $statement->fetch();
        if (!$editing) {
            http_response_code(404);
            exit('El cliente no está disponible en la matriz de homologados.');
        }

        foreach (array_keys($values) as $key) {
            if ($isPost) {
                if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
                    $error = 'Revisa el rubro y las respuestas seleccionadas.';
                } else {
                    $values[$key] = trim($_POST[$key]);
                }
            } else {
                $values[$key] = $editing[$key] ?? '';
            }
        }

        if ($isPost) {
            if (preg_match('/\A.{0,100}\z/us', $values['rubro']) !== 1) {
                $error = 'El rubro debe tener como máximo 100 caracteres válidos.';
            }
            foreach (array_keys($questions) as $key) {
                if (!array_key_exists($values[$key], $answers)) {
                    $error = 'Revisa el rubro y las respuestas seleccionadas.';
                }
            }

            if ($error === '') {
                $db->beginTransaction();
                $statement = $db->prepare(
                    "SELECT id FROM clientes WHERE id = ? AND archivado = 0 AND estado = 'homologado' FOR UPDATE"
                );
                $statement->execute([$id]);
                if (!$statement->fetch()) {
                    $db->rollBack();
                    http_response_code(409);
                    exit('El cliente ya no está disponible en la matriz. Vuelve a cargar la página.');
                }
                $statement = $db->prepare(
                    'INSERT INTO basc_matriz_clientes
                        (cliente_id, rubro, cadena_suministro, contacto_mercaderia, menos_dos_anos, sin_certificacion, sin_historial)
                     VALUES (:cliente_id, :rubro, :cadena_suministro, :contacto_mercaderia, :menos_dos_anos, :sin_certificacion, :sin_historial)
                     ON DUPLICATE KEY UPDATE rubro = VALUES(rubro), cadena_suministro = VALUES(cadena_suministro),
                        contacto_mercaderia = VALUES(contacto_mercaderia), menos_dos_anos = VALUES(menos_dos_anos),
                        sin_certificacion = VALUES(sin_certificacion), sin_historial = VALUES(sin_historial)'
                );
                $statement->execute(['cliente_id' => $id] + $values);
                $db->commit();
                $_SESSION['basc_comercial_notice'] = 'Evaluación guardada correctamente.';
                header('Location: basc-comercial.php#matriz', true, 303);
                exit;
            }
            http_response_code(422);
        }
    }

    $statement = $db->prepare(
        'SELECT c.id, c.razon_social, c.ruc, c.certificado, c.fecha_registro,
                m.rubro, m.cadena_suministro, m.contacto_mercaderia, m.menos_dos_anos,
                m.sin_certificacion, m.sin_historial
         FROM clientes c LEFT JOIN basc_matriz_clientes m ON m.cliente_id = c.id
         WHERE c.archivado = 0 AND c.estado = ? ORDER BY c.razon_social, c.id'
    );
    $statement->execute(['homologado']);
    $clients = $statement->fetchAll();
} catch (PDOException | RuntimeException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Smart Business BASC Comercial: ' . $exception->getMessage());
    http_response_code(503);
    exit('No fue posible administrar la matriz. Contacta al administrador.');
}

portalStart('BASC - Comercial');
?>
<div class="clients-header">
    <img src="../Logo/Business_2023_Grande.jpg" alt="Smart Business Corporation">
    <div>
        <h1>Matriz de criticidad clientes</h1>
        <p>BASC - Comercial · Clientes homologados</p>
    </div>
    <div class="basc-document-info" aria-label="Información del documento">
        <p>Código: <strong><?= e($document['codigo']) ?></strong></p>
        <p>Versión: <strong><?= e($document['version']) ?></strong></p>
        <p>Fecha: <strong><?= e($documentDate->format('d/m/Y')) ?></strong></p>
        <?php if ($canEditDocument): ?><a href="basc-comercial.php?editar_documento=1#documento">Editar datos</a><?php endif; ?>
    </div>
</div>
<?php if ($notice !== ''): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>

<?php if ($editingDocument): ?>
<section class="card clients-card" id="documento">
    <h2>Editar información del documento</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="accion" value="guardar_documento">
        <div class="basc-fields">
            <label>Código <input name="codigo" maxlength="100" required value="<?= e($documentValues['codigo']) ?>"></label>
            <label>Versión <input name="version" maxlength="40" required value="<?= e($documentValues['version']) ?>"></label>
            <label>Fecha <input type="date" name="fecha" min="1000-01-01" max="9999-12-31" required value="<?= e($documentValues['fecha']) ?>"></label>
        </div>
        <div class="clients-form-actions">
            <button type="submit">Guardar datos</button>
            <a class="button button-secondary" href="basc-comercial.php">Cancelar</a>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if ($editing): ?>
<section class="card clients-card" id="evaluar">
    <h2>Evaluar a <?= e($editing['razon_social']) ?></h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="accion" value="guardar_evaluacion">
        <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
        <div class="basc-fields">
            <label>Rubro
                <input name="rubro" maxlength="100" value="<?= e($values['rubro']) ?>">
            </label>
            <?php foreach ($questions as $key => $question): ?>
            <label><?= e($question) ?>
                <select name="<?= e($key) ?>">
                    <?php foreach ($answers as $answer => $label): ?>
                    <option value="<?= e($answer) ?>" <?= $values[$key] === $answer ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="clients-form-actions">
            <button type="submit">Guardar evaluación</button>
            <a class="button button-secondary" href="basc-comercial.php#matriz">Cancelar</a>
        </div>
        <p class="muted">Puedes guardar respuestas pendientes; la calificación cuenta únicamente los «Sí» registrados.</p>
    </form>
</section>
<?php endif; ?>

<section class="card clients-card" id="matriz">
    <h2>Asociados de negocios · Clientes</h2>
    <p class="muted">0: sin clasificar · 1–2: no crítico · 3–5: crítico. La calificación se calcula también cuando faltan respuestas.</p>
    <?php if (!$clients): ?>
        <p class="muted">No hay clientes homologados activos.</p>
    <?php else: ?>
    <?php $canCreate = false; require __DIR__ . '\\clientes-buscador.php'; ?>
    <p class="muted basc-scroll-hint">Desplázate horizontalmente para ver toda la matriz.</p>
    <div class="table-wrap basc-table-wrap" tabindex="0" role="region" aria-label="Matriz de criticidad de clientes">
        <table class="basc-table" id="clients-active-table">
            <colgroup>
                <col class="basc-col-number"><col class="basc-col-client"><col class="basc-col-ruc"><col class="basc-col-sector">
                <col class="basc-col-question" span="5"><col class="basc-col-score"><col class="basc-col-classification">
                <?php if ($canEdit): ?><col class="basc-col-action"><?php endif; ?>
            </colgroup>
            <thead>
                <tr>
                    <th colspan="4" scope="colgroup">Asociados de negocios · Clientes</th>
                    <th colspan="5" scope="colgroup">Criterios de evaluación</th>
                    <th colspan="2" scope="colgroup">Calificación</th>
                    <?php if ($canEdit): ?><th rowspan="2" scope="col">Acciones</th><?php endif; ?>
                </tr>
                <tr>
                    <th scope="col">N.º</th><th scope="col">Clientes</th><th scope="col">RUC</th><th scope="col">Rubro</th>
                    <?php $number = 0; foreach ($questions as $question): ?>
                    <th scope="col"><span class="basc-question-number"><?= ++$number ?></span><?= e($question) ?></th>
                    <?php endforeach; ?>
                    <th scope="col">Puntaje</th><th scope="col">Clasificación</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $index => $client): ?>
                <?php
                $score = 0;
                $missing = false;
                foreach (array_keys($questions) as $key) {
                    $score += ($client[$key] ?? '') === 'si' ? 1 : 0;
                    $missing = $missing || ($client[$key] ?? '') === '';
                }
                $classification = $score === 0 ? 'Sin clasificar' : ($score <= 2 ? 'NO CRÍTICO' : 'CRÍTICO');
                $class = $score === 0 ? 'basc-unrated' : ($score <= 2 ? 'basc-not-critical' : 'basc-critical');
                ?>
                <tr data-name="<?= e($client['razon_social']) ?>" data-ruc="<?= e($client['ruc']) ?>" data-certificate="<?= e($client['certificado']) ?>" data-year="<?= $client['fecha_registro'] !== null ? e(substr($client['fecha_registro'], 0, 4)) : '' ?>">
                    <td><?= $index + 1 ?></td>
                    <td class="basc-client-name"><?= e($client['razon_social']) ?></td>
                    <td><?= $client['ruc'] !== '' ? e($client['ruc']) : '—' ?></td>
                    <td><?= ($client['rubro'] ?? '') !== '' ? e($client['rubro']) : '—' ?></td>
                    <?php foreach (array_keys($questions) as $key): ?>
                    <td><?= ($client[$key] ?? '') !== '' ? e($answers[$client[$key]] ?? $client[$key]) : '—' ?></td>
                    <?php endforeach; ?>
                    <td class="basc-score"><?= $score ?></td>
                    <td><span class="basc-classification <?= e($class) ?>"><?= e($classification) ?></span><?php if ($missing): ?><small class="basc-pending">Respuestas pendientes</small><?php endif; ?></td>
                    <?php if ($canEdit): ?><td><a class="button" href="basc-comercial.php?editar=<?= (int) $client['id'] ?>#evaluar" aria-label="Evaluar <?= e($client['razon_social']) ?>">Evaluar</a></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php portalEnd(); ?>
