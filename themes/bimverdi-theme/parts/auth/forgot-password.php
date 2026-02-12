<?php
/**
 * Auth Template: Forgot Password
 *
 * Request password reset link.
 * Two-column layout: Value proposition left, form right.
 * Standalone page without site header/footer for focused experience.
 * URL: /glemt-passord/
 *
 * @package BIMVerdi
 */

// Redirect logged-in users
if (is_user_logged_in()) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

// Get messages from URL params
$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
$success = isset($_GET['success']);

// Error messages
$error_messages = [
    'invalid_email' => 'Vennligst oppgi en gyldig e-postadresse.',
    'nonce'         => 'Noe gikk galt. Vennligst prøv igjen.',
];

$error_message = $error_messages[$error] ?? '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glemt passord - <?php bloginfo('name'); ?></title>
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

        .info-box {
            padding: var(--bv-space-md) 0;
            margin-bottom: var(--bv-space-lg);
        }

        .info-box h3 {
            font-size: var(--bv-text-base);
            font-weight: 600;
            margin: 0 0 var(--bv-space-xs) 0;
        }

        .info-box p {
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
            margin: 0 0 var(--bv-space-xs) 0;
        }

        .info-box p:last-child { margin-bottom: 0; }

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

        .auth-card-icon {
            width: 48px;
            height: 58px;
            margin: 0 auto var(--bv-space-md);
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bv-bg-page);
            border-radius: 50%;
            color: var(--bv-text-secondary);
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

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--bv-space-xs);
            margin-top: var(--bv-space-md);
            color: var(--bv-text-secondary);
            font-size: var(--bv-text-sm);
            text-decoration: none;
        }

        .back-link:hover { color: var(--bv-text-primary); }

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

        .alt-action {
            text-align: center;
            margin-top: var(--bv-space-lg);
            padding-top: var(--bv-space-lg);
            border-top: 1px solid var(--bv-border-light);
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
        }

        .alt-action a {
            color: var(--bv-text-primary);
            font-weight: 500;
            text-decoration: none;
        }

        .alt-action a:hover { text-decoration: underline; }

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

        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* Success state */
        .success-icon-large {
            width: 64px;
            height: 64px;
            margin: 0 auto var(--bv-space-md);
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F0FDF4;
            border-radius: 50%;
            color: #22c55e;
        }

        .success-tip {
            background: var(--bv-bg-page);
            border-radius: var(--bv-radius-md);
            padding: var(--bv-space-sm);
            margin: var(--bv-space-md) 0;
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
        }

        .success-tip strong {
            color: var(--bv-text-primary);
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

            .auth-card { padding: var(--bv-space-lg); }
        }
    </style>
</head>
<body class="bv-auth-page">
    <div class="auth-container">
        <div class="auth-value">
            <a href="<?php echo home_url('/'); ?>" class="auth-logo"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/bimverdi-logo.png'); ?>" alt="BIM Verdi" style="height: 58px; width: auto;"></a>
            <h1>Tilbakestill passord</h1>
            <p class="lead">Det skjer med de beste av oss. Vi hjelper deg raskt tilbake inn i nettverket.</p>

            <div class="info-box">
                <h3>Slik fungerer det</h3>
                <p>1. Skriv inn e-postadressen du registrerte deg med</p>
                <p>2. Vi sender deg en lenke på e-post</p>
                <p>3. Klikk lenken og velg nytt passord</p>
            </div>

            <p class="auth-help">Husker du ikke e-posten? <a href="mailto:post@bimverdi.no">Kontakt oss</a></p>
        </div>

        <div class="auth-form-wrapper">
            <div class="auth-card">
                <?php if ($success): ?>
                    <!-- Success State -->
                    <div class="success-icon-large">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <polyline points="3 7 12 13 21 7"/>
                        </svg>
                    </div>

                    <div class="auth-card-header">
                        <h2>Sjekk e-posten din</h2>
                        <p>Hvis det finnes en konto med denne e-postadressen, har vi sendt en lenke for å tilbakestille passordet.</p>
                    </div>

                    <div class="success-tip">
                        <strong>Tips:</strong> Sjekk søppelpost/spam-mappen hvis du ikke finner e-posten i innboksen.
                    </div>

                    <a href="<?php echo home_url('/logg-inn/'); ?>" class="btn btn-primary">Tilbake til innlogging</a>

                <?php else: ?>
                    <!-- Form State -->
                    <div class="auth-card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </div>

                    <div class="auth-card-header">
                        <h2>Glemt passord?</h2>
                        <p>Oppgi e-postadressen din, så sender vi deg en lenke for å tilbakestille passordet.</p>
                    </div>

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
                        <?php wp_nonce_field('bimverdi_forgot_password'); ?>

                        <div class="form-group">
                            <label class="form-label">E-postadresse</label>
                            <input type="email"
                                   class="form-input"
                                   name="email"
                                   placeholder="din@epost.no"
                                   required
                                   autocomplete="email"
                                   autofocus>
                        </div>

                        <button type="submit" name="bimverdi_forgot_password" value="1" class="btn btn-primary">Send tilbakestillingslenke</button>

                        <a href="<?php echo home_url('/logg-inn/'); ?>" class="back-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Tilbake til innlogging
                        </a>
                    </form>
                <?php endif; ?>
            </div>

            <div class="alt-action">
                Har du ikke en konto? <a href="<?php echo home_url('/registrer/'); ?>">Opprett konto</a>
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
