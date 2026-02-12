<?php
/**
 * Auth Template: Login
 *
 * Custom login page following BIM Verdi design system.
 * Two-column layout: Value proposition left, form right.
 * Standalone page without site header/footer for focused experience.
 * URL: /logg-inn/
 *
 * @package BIMVerdi
 */

// Redirect logged-in users
if (is_user_logged_in()) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

// Get messages from URL params
// Note: Using 'login_error' instead of 'error' because WordPress filters out 'error' query var
$error = isset($_GET['login_error']) ? sanitize_text_field($_GET['login_error']) : '';
$logged_out = isset($_GET['logged_out']);
$reset_success = isset($_GET['reset']) && $_GET['reset'] === 'success';
$username = isset($_GET['username']) ? sanitize_user(urldecode($_GET['username'])) : '';
$redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url('/min-side/');

// Error messages
$error_messages = [
    'empty'            => 'Vennligst fyll ut brukernavn og passord.',
    'invalid'          => 'Ugyldig brukernavn eller passord.',
    'invalid_user'     => 'Denne brukeren finnes ikke.',
    'invalid_password' => 'Feil passord. Prøv igjen.',
    'nonce'            => 'Noe gikk galt. Vennligst prøv igjen.',
];

$error_message = $error_messages[$error] ?? '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logg inn - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
    <style>
        :root {
            --bv-bg-page: #F5F3EE;
            --bv-bg-card: #FFFFFF;
            --bv-text-primary: #1A1A1A;
            --bv-text-secondary: #6B6B6B;
            --bv-text-muted: #9B9B9B;
            --bv-accent: #E07A5F;
            --bv-accent-hover: #C96A52;
            --bv-btn-primary-bg: #1A1A1A;
            --bv-btn-primary-text: #FFFFFF;
            --bv-btn-primary-hover: #333333;
            --bv-btn-secondary-bg: #FFFFFF;
            --bv-btn-secondary-text: #1A1A1A;
            --bv-btn-secondary-border: #1A1A1A;
            --bv-border-light: #E8E8E8;
            --bv-border-focus: #1A1A1A;
            --bv-space-xs: 8px;
            --bv-space-sm: 16px;
            --bv-space-md: 24px;
            --bv-space-lg: 32px;
            --bv-space-xl: 48px;
            --bv-space-2xl: 64px;
            --bv-font-family: 'Inter', -apple-system, sans-serif;
            --bv-text-sm: 14px;
            --bv-text-base: 16px;
            --bv-text-lg: 18px;
            --bv-text-xl: 20px;
            --bv-text-2xl: 24px;
            --bv-text-3xl: 32px;
            --bv-radius-md: 8px;
            --bv-radius-lg: 12px;
            --bv-shadow-card: 0 4px 24px rgba(0,0,0,0.08);
            --bv-transition-normal: 250ms ease;
        }

        .bv-auth-page * { box-sizing: border-box; }

        .bv-auth-page {
            font-family: var(--bv-font-family);
            font-size: var(--bv-text-base);
            line-height: 1.5;
            color: var(--bv-text-primary);
            background-color: var(--bv-bg-page);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            margin: 0;
            padding: 0;
        }

        .auth-container {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            min-height: 100vh;
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--bv-space-xl);
            gap: var(--bv-space-2xl);
            align-items: center;
        }

        .auth-value { padding-right: var(--bv-space-xl); }

        .auth-logo {
            font-size: var(--bv-text-xl);
            font-weight: 700;
            color: var(--bv-text-primary);
            margin-bottom: var(--bv-space-xl);
            text-decoration: none;
            display: inline-block;
        }

        .auth-value h1 {
            font-size: var(--bv-text-3xl);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: var(--bv-space-sm);
        }

        .auth-value .lead {
            font-size: var(--bv-text-lg);
            color: var(--bv-text-secondary);
            margin-bottom: var(--bv-space-lg);
        }

        .benefits-list {
            list-style: none;
            margin: 0 0 var(--bv-space-lg) 0;
            padding: 0 0 var(--bv-space-lg) 0;
            border-bottom: 1px solid var(--bv-border-light);
        }

        .benefits-list li {
            display: flex;
            align-items: center;
            gap: var(--bv-space-sm);
            margin-bottom: var(--bv-space-xs);
            font-size: var(--bv-text-base);
            color: var(--bv-text-secondary);
        }

        .benefits-list li:last-child { margin-bottom: 0; }

        .check-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            color: var(--bv-text-secondary);
        }

        .auth-help {
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
        }

        .auth-help a {
            color: var(--bv-text-primary);
            font-weight: 500;
            text-decoration: none;
        }

        .auth-help a:hover { text-decoration: underline; }

        .auth-form-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .auth-card {
            background: var(--bv-bg-card);
            border-radius: var(--bv-radius-lg);
            box-shadow: var(--bv-shadow-card);
            padding: var(--bv-space-xl);
            width: 100%;
            max-width: 440px;
        }

        .auth-card-header {
            text-align: center;
            margin-bottom: var(--bv-space-lg);
        }

        .auth-card-header h2 {
            font-size: var(--bv-text-2xl);
            font-weight: 700;
            margin: 0 0 var(--bv-space-xs) 0;
        }

        .auth-card-header p {
            color: var(--bv-text-secondary);
            font-size: var(--bv-text-sm);
            margin: 0;
        }

        .form-group { margin-bottom: var(--bv-space-md); }

        .form-label {
            display: block;
            font-size: var(--bv-text-sm);
            font-weight: 500;
            margin-bottom: var(--bv-space-xs);
        }

        .form-label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-link {
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
            text-decoration: none;
        }

        .form-link:hover { text-decoration: underline; }

        .form-input {
            width: 100%;
            padding: var(--bv-space-sm);
            font-size: var(--bv-text-base);
            font-family: inherit;
            border: 1px solid var(--bv-border-light);
            border-radius: var(--bv-radius-md);
            background: var(--bv-bg-card);
            transition: border-color var(--bv-transition-normal);
        }

        .form-input::placeholder { color: var(--bv-text-muted); }

        .form-input:focus {
            outline: none;
            border-color: var(--bv-border-focus);
        }

        .form-checkbox-row {
            display: flex;
            align-items: center;
            gap: var(--bv-space-xs);
            margin-bottom: var(--bv-space-md);
        }

        .form-checkbox-row input {
            width: 18px;
            height: 18px;
            accent-color: var(--bv-btn-primary-bg);
        }

        .form-checkbox-row label {
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
            margin: 0;
        }

        .btn {
            display: block;
            width: 100%;
            padding: var(--bv-space-sm);
            font-size: var(--bv-text-base);
            font-weight: 500;
            font-family: inherit;
            border-radius: var(--bv-radius-md);
            cursor: pointer;
            transition: all var(--bv-transition-normal);
            text-align: center;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--bv-btn-primary-bg);
            color: var(--bv-btn-primary-text);
            border: none;
        }

        .btn-primary:hover { background: var(--bv-btn-primary-hover); }

        .btn-secondary {
            background: var(--bv-btn-secondary-bg);
            color: var(--bv-btn-secondary-text);
            border: 1px solid var(--bv-btn-secondary-border);
        }

        .btn-secondary:hover { background: var(--bv-bg-page); }

        .divider {
            display: flex;
            align-items: center;
            gap: var(--bv-space-sm);
            margin: var(--bv-space-md) 0;
            color: var(--bv-text-muted);
            font-size: var(--bv-text-sm);
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--bv-border-light);
        }

        .auth-footer {
            text-align: center;
            margin-top: var(--bv-space-md);
        }

        .auth-footer a {
            color: var(--bv-accent);
            font-size: var(--bv-text-sm);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--bv-space-xs);
        }

        .auth-footer a:hover { color: var(--bv-accent-hover); }

        /* Alert messages */
        .alert {
            padding: var(--bv-space-sm);
            border-radius: var(--bv-radius-md);
            margin-bottom: var(--bv-space-md);
            font-size: var(--bv-text-sm);
            display: flex;
            align-items: flex-start;
            gap: var(--bv-space-xs);
        }

        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }

        .alert-success {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            color: #166534;
        }

        .alert-info {
            background: var(--bv-bg-page);
            border: 1px solid var(--bv-border-light);
            color: var(--bv-text-secondary);
        }

        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
                padding: var(--bv-space-md);
                gap: var(--bv-space-lg);
            }

            .auth-value {
                padding-right: 0;
                text-align: center;
            }

            .benefits-list li {
                justify-content: center;
            }

            .auth-card { padding: var(--bv-space-lg); }
        }
    </style>
</head>
<body class="bv-auth-page">
    <div class="auth-container">
        <div class="auth-value">
            <a href="<?php echo home_url('/'); ?>" class="auth-logo"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/bimverdi-logo.png'); ?>" alt="BIM Verdi" style="height: 58px; width: auto;"></a>
            <h1>Velkommen tilbake</h1>
            <p class="lead">Som innlogget får du tilgang til å registrere verktøy, delta på arrangementer og bidra med kunnskap til nettverket.</p>
            <ul class="benefits-list">
                <li>
                    <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Registrer og del dine BIM-verktøy
                </li>
                <li>
                    <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Meld deg på workshops og meetups
                </li>
                <li>
                    <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Skriv artikler og del erfaringer
                </li>
            </ul>
            <p class="auth-help">Problemer med innloggingen? <a href="mailto:post@bimverdi.no">Kontakt oss</a></p>
        </div>

        <div class="auth-form-wrapper">
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2>Logg inn</h2>
                    <p>Velkommen tilbake til BIM Verdi</p>
                </div>

                <?php if ($reset_success): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span>Passordet er oppdatert. Du kan nå logge inn med ditt nye passord.</span>
                </div>
                <?php endif; ?>

                <?php if ($logged_out): ?>
                <div class="alert alert-info">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span>Du er nå logget ut.</span>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span><?php echo esc_html($error_message); ?></span>
                </div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php wp_nonce_field('bimverdi_login'); ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                    <div class="form-group">
                        <label class="form-label">E-post eller brukernavn</label>
                        <input type="text"
                               class="form-input"
                               name="username"
                               value="<?php echo esc_attr($username); ?>"
                               required
                               autocomplete="username"
                               autofocus>
                    </div>

                    <div class="form-group">
                        <div class="form-label-row">
                            <label class="form-label">Passord</label>
                            <a href="<?php echo home_url('/glemt-passord/'); ?>" class="form-link">Glemt passord?</a>
                        </div>
                        <input type="password"
                               class="form-input"
                               name="password"
                               required
                               autocomplete="current-password">
                    </div>

                    <div class="form-checkbox-row">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Husk meg på denne enheten</label>
                    </div>

                    <button type="submit" name="bimverdi_login" value="1" class="btn btn-primary">Logg inn</button>
                </form>

                <div class="divider">Ny bruker?</div>

                <a href="<?php echo home_url('/registrer/'); ?>" class="btn btn-secondary">Opprett konto</a>
            </div>

            <div class="auth-footer">
                <a href="<?php echo home_url('/'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Tilbake til forsiden
                </a>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
