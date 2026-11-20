<?php
declare(strict_types=1);

$types = ['' => 'Sin indicar', 'nacional' => 'Nacional', 'exterior' => 'Exterior'];
$certificates = ['' => 'Sin indicar', 'OEA' => 'OEA', 'BASC' => 'BASC', 'ISO 2800' => 'ISO 2800'];
$answers = ['' => 'Sin indicar', 'si' => 'Sí', 'no' => 'No', 'no_aplica' => 'No aplica'];
$statuses = ['' => 'Sin indicar', 'pendiente' => 'Pendiente', 'homologado' => 'Homologado'];

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
