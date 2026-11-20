<?php
declare(strict_types=1);

require __DIR__ . '\\PHP\\session.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: PHP/dashboard.php', true, 303);
    exit;
}

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
$csrfToken = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#173b49">
    <title>Iniciar sesión | Smart Business</title>
    <link rel="icon" type="image/png" sizes="64x64" href="Logo/favicon-business.png">
    <link rel="stylesheet" href="Estilos_CSS/login.css?v=<?= filemtime(__DIR__ . '\\Estilos_CSS\\login.css') ?>">
</head>
<body>
    <main class="login-shell">
        <section class="brand-panel" aria-label="Smart Business">
            <div class="brand-top">
                <div class="logo-frame">
                    <img src="Logo/Business_Poca_Resolucion.jpg" alt="Smart Business Corporation, Freight Forwarder &amp; Logistics Operator">
                </div>
            </div>

            <div class="brand-content">
                <p class="eyebrow">Logística que conecta</p>
                <h1>Tu negocio,<br><span>en movimiento</span><br>por el mundo.</h1>
                <p class="brand-description">Gestiona tus operaciones de carga internacional con la confianza, visibilidad y respaldo que tu empresa necesita.</p>

                <div class="route-card">
                    <div class="route-icon" aria-hidden="true">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="none">
                            <path d="M3 17.5h18M5 17.5l1.5-6h11l1.5 6M8 11.5l2-5h4l2 5M9 15h.01M15 15h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <strong>Su operación, en buenas manos</strong>
                        <span>Freight forwarding · Logistics operator</span>
                    </div>
                </div>
            </div>

            <div class="brand-footer">
                <span>Smart Business Corporation</span>
                <span class="footer-mark"><i aria-hidden="true"></i> Conectando mercados</span>
            </div>
        </section>

        <section class="form-panel" aria-labelledby="login-title">
            <div class="login-card">
                <div class="mobile-logo">
                    <img src="Logo/Business_Poca_Resolucion.jpg" alt="Smart Business Corporation">
                </div>

                <p class="form-eyebrow">Portal de clientes</p>
                <h2 id="login-title">Bienvenido de nuevo</h2>
                <p class="form-intro">Ingresa tus credenciales para acceder a tu espacio de trabajo.</p>

                <form id="login-form" action="PHP/login.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <div class="field">
                        <label for="username">Usuario</label>
                        <div class="input-wrap">
                            <input type="text" id="username" name="username" placeholder="Ingresa tu usuario" autocomplete="username" maxlength="100" required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Contraseña</label>
                        <div class="input-wrap">
                            <input class="password-input" type="password" id="password" name="password" placeholder="Ingresa tu contraseña" autocomplete="current-password" required>
                            <button class="password-toggle" type="button" aria-label="Mostrar contraseña" aria-pressed="false">
                                <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="1.7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button class="submit-button" type="submit">
                        Ingresar al portal
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <?php if (is_string($loginError)): ?>
                        <p class="login-feedback" role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                    <?php endif; ?>
                </form>

                <div class="security-note">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3 19 6v5c0 4.7-2.9 8.3-7 10-4.1-1.7-7-5.3-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Acceso seguro y exclusivo para clientes y colaboradores autorizados.</span>
                </div>

                <p class="help-text">¿Necesitas ayuda? Contacta al administrador de tu cuenta.</p>
                <div class="form-footer">© Smart Business Corporation</div>
            </div>
        </section>
    </main>

    <script>
        const passwordInput = document.getElementById("password");
        const passwordToggle = document.querySelector(".password-toggle");
        passwordToggle.addEventListener("click", () => {
            const isVisible = passwordInput.type === "text";
            passwordInput.type = isVisible ? "password" : "text";
            passwordToggle.setAttribute("aria-pressed", String(!isVisible));
            passwordToggle.setAttribute("aria-label", isVisible ? "Mostrar contraseña" : "Ocultar contraseña");
        });

    </script>
</body>
</html>
