<?php
declare(strict_types=1);

if (!isset($clients, $canCreate)) {
    http_response_code(404);
    exit;
}

$years = [];
foreach ($clients as $client) {
    if ($client['fecha_registro'] !== null) {
        $year = substr($client['fecha_registro'], 0, 4);
        $years[$year] = $year;
    }
}
rsort($years, SORT_NUMERIC);
?>
<div class="clients-search" id="clients-search" hidden>
    <form class="clients-search-bar" id="clients-search-form" role="search">
        <label for="clients-search-year">Año de registro:</label>
        <select id="clients-search-year">
            <option value="">Todos</option>
            <?php foreach ($years as $year): ?><option value="<?= e($year) ?>"><?= e($year) ?></option><?php endforeach; ?>
            <option value="sin_fecha">Sin fecha</option>
        </select>
        <label class="sr-only" for="clients-search-input">Buscar clientes</label>
        <input type="search" id="clients-search-input" placeholder="Buscar por razón social, RUC o certificado" autocomplete="off">
        <button type="submit" class="button-secondary">Buscar</button>
        <?php if ($canCreate): ?><a class="button" href="clientes.php#registrar-cliente">+ Agregar cliente</a><?php endif; ?>
    </form>
    <p class="muted" id="clients-search-status" role="status" aria-live="polite"></p>
</div>
