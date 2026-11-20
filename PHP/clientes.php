<?php
declare(strict_types=1);

require __DIR__ . '\\auth.php';
require __DIR__ . '\\layout.php';
exigirPermiso('Clientes', 'ver');

$canCreate = puede('Clientes', 'crear');
$canEdit = puede('Clientes', 'editar');
$canArchive = puede('Clientes', 'eliminar');
$types = ['' => 'Sin indicar', 'nacional' => 'Nacional', 'exterior' => 'Exterior'];
$certificates = ['' => 'Sin indicar', 'OEA' => 'OEA', 'BASC' => 'BASC', 'ISO 2800' => 'ISO 2800'];
$answers = ['' => 'Sin indicar', 'si' => 'Sí', 'no' => 'No', 'no_aplica' => 'No aplica'];
$statuses = ['' => 'Sin indicar', 'pendiente' => 'Pendiente', 'homologado' => 'Homologado'];
$values = [
    'razon_social' => '', 'ruc' => '', 'tipo' => '', 'certificado' => '',
    'acuerdo_seguridad' => '', 'ddjj' => '', 'estado' => '',
];
$notice = $_SESSION['clientes_notice'] ?? '';
unset($_SESSION['clientes_notice']);
$error = '';
$editing = null;

function clientPostString(string $name): string
{
    return is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : '';
}

function clientBadge(string $value, array $options): void
{
    if ($value === '') {
        echo '—';
        return;
    }
    $class = match ($value) {
        'si', 'homologado' => 'clients-badge-positive',
        'no', 'pendiente' => 'clients-badge-negative',
        'no_aplica' => 'clients-badge-neutral',
        default => '',
    };
    echo '<span class="clients-badge ' . $class . '">' . e($options[$value] ?? $value) . '</span>';
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('La sesión del formulario ha caducado. Vuelve a cargar la página.');
        }
        $action = clientPostString('accion');
        if (($action === 'crear' && !$canCreate) || ($action === 'editar' && !$canEdit)
            || (in_array($action, ['archivar', 'restaurar'], true) && !$canArchive)
            || !in_array($action, ['crear', 'editar', 'archivar', 'restaurar'], true)
        ) {
            http_response_code(403);
            exit('Acceso denegado.');
        }
        if ($action === 'archivar' || $action === 'restaurar') {
            $idInput = clientPostString('id');
            $id = ctype_digit($idInput) ? (int) $idInput : 0;
            $archived = $action === 'archivar' ? 1 : 0;
            $statement = $db->prepare('UPDATE clientes SET archivado = ? WHERE id = ? AND archivado = ?');
            $statement->execute([$archived, $id, 1 - $archived]);
            if ($statement->rowCount() !== 1) {
                http_response_code(409);
                exit('El cliente no existe o ya cambió de estado. Vuelve a cargar la página.');
            }
            $_SESSION['clientes_notice'] = $archived
                ? 'Cliente archivado. Puedes restaurarlo desde Clientes archivados.'
                : 'Cliente restaurado correctamente.';
            header('Location: clientes.php', true, 303);
            exit;
        }
        if ($action === 'editar') {
            $idInput = clientPostString('id');
            $id = ctype_digit($idInput) ? (int) $idInput : 0;
            $statement = $db->prepare('SELECT * FROM clientes WHERE id = ? AND archivado = 0');
            $statement->execute([$id]);
            $editing = $statement->fetch();
            if (!$editing) {
                http_response_code(404);
                exit('Cliente no encontrado.');
            }
        }
        foreach (array_keys($values) as $key) {
            $values[$key] = clientPostString($key);
        }
        if ($values['razon_social'] === '' || strlen($values['razon_social']) > 200
            || strlen($values['ruc']) > 30 || strlen($values['certificado']) > 100
            || !array_key_exists($values['tipo'], $types)
            || (!array_key_exists($values['certificado'], $certificates)
                && (!$editing || $values['certificado'] !== $editing['certificado']))
            || !array_key_exists($values['acuerdo_seguridad'], $answers)
            || !array_key_exists($values['ddjj'], $answers)
            || !array_key_exists($values['estado'], $statuses)
        ) {
            $error = 'Revisa la razón social y los datos seleccionados del cliente.';
        } elseif ($action === 'crear') {
            $statement = $db->prepare(
                'INSERT INTO clientes (razon_social, ruc, tipo, certificado, acuerdo_seguridad, ddjj, estado)
                 VALUES (:razon_social, :ruc, :tipo, :certificado, :acuerdo_seguridad, :ddjj, :estado)'
            );
            $statement->execute($values);
        } else {
            $statement = $db->prepare(
                'UPDATE clientes SET razon_social = :razon_social, ruc = :ruc, tipo = :tipo,
                 certificado = :certificado, acuerdo_seguridad = :acuerdo_seguridad,
                 ddjj = :ddjj, estado = :estado WHERE id = :id AND archivado = 0'
            );
            $statement->execute($values + ['id' => $id]);
            if ($statement->rowCount() === 0) {
                $statement = $db->prepare('SELECT id FROM clientes WHERE id = ? AND archivado = 0');
                $statement->execute([$id]);
                if (!$statement->fetch()) {
                    http_response_code(409);
                    exit('El cliente ya no está disponible para editar. Vuelve a cargar la página.');
                }
            }
        }
        if ($error === '') {
            $_SESSION['clientes_notice'] = 'Cliente guardado correctamente.';
            header('Location: clientes.php', true, 303);
            exit;
        }
    } elseif (isset($_GET['editar'])) {
        if (!$canEdit) {
            http_response_code(403);
            exit('Acceso denegado.');
        }
        $editInput = is_string($_GET['editar']) ? $_GET['editar'] : '';
        $statement = $db->prepare('SELECT * FROM clientes WHERE id = ? AND archivado = 0');
        $statement->execute([ctype_digit($editInput) ? (int) $editInput : 0]);
        $editing = $statement->fetch();
        if (!$editing) {
            http_response_code(404);
            exit('Cliente no encontrado.');
        }
        foreach (array_keys($values) as $key) {
            $values[$key] = $editing[$key];
        }
    }
    $clients = $db->query('SELECT * FROM clientes WHERE archivado = 0 ORDER BY razon_social, id')->fetchAll();
    $archivedClients = $canArchive
        ? $db->query('SELECT id, razon_social, ruc FROM clientes WHERE archivado = 1 ORDER BY razon_social, id')->fetchAll()
        : [];
} catch (PDOException $exception) {
    error_log('Smart Business clientes: ' . $exception->getMessage());
    http_response_code(503);
    exit('No fue posible administrar los clientes. Contacta al administrador.');
}

portalStart('Clientes');
?>
<div class="clients-header">
    <img src="../Logo/Business_2023_Grande.jpg" alt="Smart Business Corporation">
    <div>
        <h1>Listado de clientes Smart Business</h1>
        <p>Consulta y administra los clientes del área comercial.</p>
    </div>
</div>
<?php if ($notice !== ''): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>

<?php if ($editing || $canCreate): ?>
<section class="card clients-card" id="registrar-cliente">
    <h2><?= $editing ? 'Editar cliente' : 'Registrar cliente' ?></h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="accion" value="<?= $editing ? 'editar' : 'crear' ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
        <div class="clients-fields">
            <label>Razón social <input name="razon_social" maxlength="200" required value="<?= e($values['razon_social']) ?>"></label>
            <label>RUC <input name="ruc" maxlength="30" value="<?= e($values['ruc']) ?>"></label>
            <label>Tipo
                <select name="tipo">
                    <?php foreach ($types as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $values['tipo'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Certificado
                <select name="certificado">
                    <?php foreach ($certificates as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $values['certificado'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                    <?php if ($editing && !array_key_exists($editing['certificado'], $certificates)): ?>
                        <option value="<?= e($editing['certificado']) ?>" <?= $values['certificado'] === $editing['certificado'] ? 'selected' : '' ?>><?= e($editing['certificado']) ?> (existente)</option>
                    <?php endif; ?>
                </select>
            </label>
            <label>Acuerdo de seguridad
                <select name="acuerdo_seguridad">
                    <?php foreach ($answers as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $values['acuerdo_seguridad'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>DDJJ
                <select name="ddjj">
                    <?php foreach ($answers as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $values['ddjj'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Estado
                <select name="estado">
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $values['estado'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="clients-form-actions">
            <button type="submit"><?= $editing ? 'Guardar cambios' : 'Registrar cliente' ?></button>
            <?php if ($editing): ?><a class="button button-secondary" href="clientes.php">Cancelar edición</a><?php endif; ?>
        </div>
        <p class="muted">Solo la razón social es obligatoria. El estado se selecciona manualmente.</p>
    </form>
</section>
<?php endif; ?>

<section class="card clients-card">
    <h2>Clientes registrados</h2>
    <?php if (!$clients): ?>
        <p class="muted">Aún no hay clientes registrados.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table class="clients-table">
            <thead><tr>
                <th scope="col">N.º</th><th scope="col">Razón social</th><th scope="col">RUC</th>
                <th scope="col">Tipo</th><th scope="col">Certificado</th><th scope="col">Acuerdo de seguridad</th>
                <th scope="col">DDJJ</th><th scope="col">Estado</th>
                <?php if ($canEdit || $canArchive): ?><th scope="col">Acciones</th><?php endif; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($clients as $index => $client): ?>
                <tr>
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
<?php if ($canArchive): ?>
<section class="card clients-card">
    <details class="clients-archived">
        <summary>Clientes archivados (<?= count($archivedClients) ?>)</summary>
        <?php if (!$archivedClients): ?>
            <p class="muted">No hay clientes archivados.</p>
        <?php else: ?>
        <p class="muted">Los clientes archivados no aparecen en el listado principal.</p>
        <div class="table-wrap">
            <table class="clients-table">
                <thead><tr><th scope="col">Razón social</th><th scope="col">RUC</th><th scope="col">Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($archivedClients as $client): ?>
                    <tr>
                        <td data-label="Razón social"><?= e($client['razon_social']) ?></td>
                        <td data-label="RUC"><?= $client['ruc'] !== '' ? e($client['ruc']) : '—' ?></td>
                        <td data-label="Acciones">
                            <form method="post" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="accion" value="restaurar">
                                <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                                <button type="submit" aria-label="Restaurar <?= e($client['razon_social']) ?>">Restaurar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </details>
</section>
<?php endif; ?>
<?php portalEnd(); ?>
