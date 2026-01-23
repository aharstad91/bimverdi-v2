<?php
/**
 * Template Name: Registrer (Email Signup)
 *
 * Første steg i registrering - kun e-post.
 * Sender verifiseringslenke til brukerens e-post.
 * Two-column layout: Value proposition left, form right.
 * Standalone page without site header/footer for focused experience.
 *
 * @package BIMVerdi
 */

// If user is already logged in, redirect to min-side
if (is_user_logged_in()) {
    wp_redirect(home_url('/min-side/'));
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opprett konto - <?php bloginfo('name'); ?></title>
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
            align-items: flex-start;
            gap: var(--bv-space-sm);
            margin-bottom: var(--bv-space-md);
        }

        .benefits-list li:last-child { margin-bottom: 0; }

        .benefit-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            color: var(--bv-text-secondary);
        }

        .benefit-content h3 {
            font-size: var(--bv-text-base);
            font-weight: 600;
            margin: 0 0 2px 0;
        }

        .benefit-content p {
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
            margin: 0;
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

        .form-label .required {
            color: var(--bv-accent);
            margin-left: 4px;
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

        .form-helper {
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
            margin-top: var(--bv-space-xs);
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

        .terms-text {
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
            text-align: center;
            margin-top: var(--bv-space-md);
        }

        .terms-text a {
            color: var(--bv-text-primary);
            text-decoration: underline;
        }

        /* Gravity Forms overrides */
        .bimverdi-email-signup-wrapper .gform_wrapper {
            margin: 0 !important;
            padding: 0 !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gform_body {
            padding: 0 !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gfield {
            margin-bottom: var(--bv-space-md) !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gfield_label {
            font-weight: 500 !important;
            color: var(--bv-text-primary) !important;
            margin-bottom: var(--bv-space-xs) !important;
            font-size: var(--bv-text-sm) !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper input[type="email"],
        .bimverdi-email-signup-wrapper .gform_wrapper input[type="text"] {
            width: 100% !important;
            padding: var(--bv-space-sm) !important;
            border: 1px solid var(--bv-border-light) !important;
            border-radius: var(--bv-radius-md) !important;
            font-size: var(--bv-text-base) !important;
            color: var(--bv-text-primary) !important;
            transition: border-color var(--bv-transition-normal) !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper input[type="email"]:focus,
        .bimverdi-email-signup-wrapper .gform_wrapper input[type="text"]:focus {
            outline: none !important;
            border-color: var(--bv-border-focus) !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gfield_description {
            font-size: var(--bv-text-sm) !important;
            color: var(--bv-text-secondary) !important;
            margin-top: var(--bv-space-xs) !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gform_footer {
            margin-top: var(--bv-space-md) !important;
            padding: 0 !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gform_button,
        .bimverdi-email-signup-wrapper .gform_wrapper input[type="submit"],
        .bimverdi-email-signup-wrapper .gform_wrapper button[type="submit"] {
            width: 100% !important;
            padding: var(--bv-space-sm) !important;
            background: var(--bv-btn-primary-bg) !important;
            color: var(--bv-btn-primary-text) !important;
            border: none !important;
            border-radius: var(--bv-radius-md) !important;
            font-size: var(--bv-text-base) !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            transition: background var(--bv-transition-normal) !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gform_button:hover,
        .bimverdi-email-signup-wrapper .gform_wrapper input[type="submit"]:hover,
        .bimverdi-email-signup-wrapper .gform_wrapper button[type="submit"]:hover {
            background: var(--bv-btn-primary-hover) !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gfield_error input {
            border-color: #dc2626 !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .validation_message {
            color: #dc2626 !important;
            font-size: var(--bv-text-sm) !important;
            margin-top: 4px !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gform_validation_errors {
            background-color: #fef2f2 !important;
            border: 1px solid #fecaca !important;
            border-radius: var(--bv-radius-md) !important;
            padding: var(--bv-space-sm) !important;
            margin-bottom: var(--bv-space-md) !important;
        }

        .bimverdi-email-signup-wrapper .gform_wrapper .gform_validation_errors h2 {
            color: #dc2626 !important;
            font-size: var(--bv-text-sm) !important;
            font-weight: 600 !important;
            margin: 0 !important;
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
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .auth-card { padding: var(--bv-space-lg); }
        }
    </style>
</head>
<body class="bv-auth-page">
    <div class="auth-container">
        <div class="auth-value">
            <a href="<?php echo home_url('/'); ?>" class="auth-logo">BIM Verdi</a>
            <h1>Bli en del av nettverket</h1>
            <p class="lead">Som medlem får du tilgang til Norges ledende nettverk for praktisk bruk av BIM og AI i byggenæringen.</p>
            <ul class="benefits-list">
                <li>
                    <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <div class="benefit-content">
                        <h3>Nettverk</h3>
                        <p>Bli kjent med andre BIM-aktører i Norge</p>
                    </div>
                </li>
                <li>
                    <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                    <div class="benefit-content">
                        <h3>Verktøy</h3>
                        <p>Utforsk og del BIM-verktøy</p>
                    </div>
                </li>
                <li>
                    <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <div class="benefit-content">
                        <h3>Arrangementer</h3>
                        <p>Delta på workshops og meetups</p>
                    </div>
                </li>
            </ul>
            <p class="auth-help">Har du spørsmål? <a href="<?php echo home_url('/kontakt/'); ?>">Kontakt oss</a></p>
        </div>

        <div class="auth-form-wrapper">
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2>Lag en konto</h2>
                    <p>Oppgi e-postadressen din for å starte</p>
                </div>

                <div class="bimverdi-email-signup-wrapper">
                    <?php
                    if (function_exists('gravity_form')) {
                        $email_form_id = (int) get_option('bimverdi_email_form_id', 5);
                        gravity_form(
                            $email_form_id,
                            false,
                            false,
                            false,
                            null,
                            true,
                            0,
                            true
                        );
                    } else {
                        echo '<div style="padding: var(--bv-space-sm); background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--bv-radius-md); color: #991b1b; font-size: var(--bv-text-sm);">';
                        echo 'Gravity Forms er ikke aktivert. Kontakt administrator.';
                        echo '</div>';
                    }
                    ?>
                </div>

                <div class="divider">Har du konto?</div>

                <a href="<?php echo home_url('/logg-inn/'); ?>" class="btn btn-secondary">Logg inn</a>

                <p class="terms-text">
                    Ved å registrere deg godtar du våre <a href="<?php echo home_url('/vilkar/'); ?>">vilkår</a> og <a href="<?php echo home_url('/personvern/'); ?>">personvernerklæring</a>.
                </p>
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
