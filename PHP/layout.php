<?php
declare(strict_types=1);

function sidebarLink(string $url, string $label, string $icon, bool $active = false): void
{
    $current = $active ? ' aria-current="page"' : '';
    echo '<a class="sidebar-link' . ($active ? ' is-active' : '') . '" href="' . e($url)
        . '" aria-label="' . e($label) . '" title="' . e($label) . '"' . $current . '>'
        . '<span class="sidebar-icon" aria-hidden="true">' . e($icon) . '</span>'
        . '<span class="sidebar-label">' . e($label) . '</span></a>';
}

function portalStart(string $title): void
{
    $page = basename($_SERVER['SCRIPT_NAME']);
    $canViewUsers = puede('Usuarios y roles', 'ver');
    $canCreateUsers = $canViewUsers && puede('Usuarios y roles', 'crear');
    $canViewClients = puede('Clientes', 'ver');
    $canViewBasc = puede('BASC Comercial', 'ver');
    $isAdmin = (int) $_SESSION['rol_id'] === 1;
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | Smart Business</title>
    <link rel="icon" type="image/png" sizes="64x64" href="../Logo/favicon-business.png">
    <link rel="stylesheet" href="../Estilos_CSS/portal.css?v=<?= filemtime(dirname(__DIR__) . '\\Estilos_CSS\\portal.css') ?>">
    <script src="../Desarrollos_JS/portal.js?v=<?= filemtime(dirname(__DIR__) . '\\Desarrollos_JS\\portal.js') ?>" defer></script>
</head>
<body>
<div class="portal-shell" id="portal-shell">
    <aside class="sidebar" aria-label="Menú lateral">
        <div class="sidebar-heading">
            <a class="sidebar-brand" href="dashboard.php" aria-label="Extranet Smart Business">
                <img class="brand-mark" src="../Logo/favicon-business.png" alt="">
                <span class="sidebar-label">Extranet <strong>Smart Business</strong></span>
            </a>
            <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Reducir menú lateral" aria-pressed="false" title="Reducir menú lateral" hidden>
                <span aria-hidden="true">‹</span>
            </button>
        </div>
        <nav class="sidebar-nav" aria-label="Navegación principal">
            <?php sidebarLink('dashboard.php', 'Inicio', 'I', $page === 'dashboard.php'); ?>
            <?php if ($canViewClients): ?>
                <details class="sidebar-group"<?= $page === 'clientes.php' ? ' open' : '' ?>>
                    <summary class="sidebar-link sidebar-group-heading" aria-label="Área Comercial">
                        <span class="sidebar-icon" aria-hidden="true">A</span>
                        <span class="sidebar-label">Área Comercial</span>
                        <span class="sidebar-group-chevron" aria-hidden="true">▾</span>
                    </summary>
                    <div class="sidebar-submenu">
                        <?php sidebarLink('clientes.php', 'Control de clientes homologados', 'C', $page === 'clientes.php'); ?>
                    </div>
                </details>
            <?php endif; ?>
            <?php if ($canViewBasc): ?>
                <details class="sidebar-group"<?= $page === 'basc-comercial.php' ? ' open' : '' ?>>
                    <summary class="sidebar-link sidebar-group-heading" aria-label="BASC">
                        <span class="sidebar-icon" aria-hidden="true">B</span>
                        <span class="sidebar-label">BASC</span>
                        <span class="sidebar-group-chevron" aria-hidden="true">▾</span>
                    </summary>
                    <div class="sidebar-submenu">
                        <?php sidebarLink('basc-comercial.php', 'Comercial', 'C', $page === 'basc-comercial.php'); ?>
                    </div>
                </details>
            <?php endif; ?>
            <?php if ($canViewUsers): ?>
                <?php sidebarLink('usuarios.php', 'Usuarios y roles', 'U', $page === 'usuarios.php'); ?>
            <?php endif; ?>
            <?php if ($canCreateUsers): ?>
                <?php sidebarLink('usuarios.php#crear-usuario', 'Registrar usuario', '+'); ?>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <?php sidebarLink('permisos.php', 'Roles y permisos', 'P', $page === 'permisos.php'); ?>
            <?php endif; ?>
        </nav>
        <form class="sidebar-logout" action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <button type="submit" class="sidebar-link" aria-label="Cerrar sesión" title="Cerrar sesión">
                <span class="sidebar-icon" aria-hidden="true">↪</span>
                <span class="sidebar-label">Cerrar sesión</span>
            </button>
        </form>
    </aside>
    <div class="portal-content">
        <?php if ($page !== 'dashboard.php' && $page !== 'usuarios.php' && $page !== 'permisos.php' && $page !== 'clientes.php' && $page !== 'basc-comercial.php'): ?>
        <header class="portal-header">
            <img src="../Logo/Business_Poca_Resolucion.jpg" alt="Smart Business Corporation">
            <div class="portal-heading">
                <span class="eyebrow">Extranet Smart Business</span>
                <span class="portal-heading-title"><?= e($title) ?></span>
            </div>
        </header>
        <?php endif; ?>
        <main<?= $page === 'dashboard.php' ? ' class="dashboard-main"' : ($page === 'usuarios.php' ? ' class="users-main"' : ($page === 'permisos.php' ? ' class="permissions-main"' : (in_array($page, ['clientes.php', 'basc-comercial.php'], true) ? ' class="clients-main"' : ''))) ?>>
    <?php
}

function portalEnd(): void
{
    echo '</main></div></div></body></html>';
}
