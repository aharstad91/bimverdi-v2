<?php
/**
 * Template Name: Aktiver Konto
 *
 * Complete user registration after email verification.
 * Two-column layout matching login/register pages.
 * Standalone page without site header/footer.
 * URL: /aktiver-konto/?email=xxx&token=xxx
 *
 * @package BIMVerdi
 */

// Get parameters from URL
$email = isset($_GET['email']) ? sanitize_email(urldecode($_GET['email'])) : '';
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
$form_error = isset($_GET['bv_error']) ? sanitize_text_field($_GET['bv_error']) : '';

// Validate token
$is_valid = false;
$error_message = '';
$error_code = '';

if (empty($email) || empty($token)) {
    $error_message = 'Ugyldig lenke. Vennligst bruk lenken fra e-posten eller registrer deg på nytt.';
    $error_code = 'missing_params';
} else {
    // Use our verification system
    if (class_exists('BIMVerdi_Email_Verification')) {
        $verifier = new BIMVerdi_Email_Verification();
        $result = $verifier->verify_token($token, $email);
        $is_valid = $result['valid'];
        if (!$is_valid) {
            $error_message = $result['message'];
            $error_code = $result['code'];
        }
    } else {
        // Fallback validation
        global $wpdb;
        $table_name = $wpdb->prefix . 'bimverdi_pending_registrations';

        $pending = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE token = %s AND email = %s",
            $token, $email
        ));

        if (!$pending) {
            $error_message = 'Ugyldig verifiseringslenke. Vennligst registrer deg på nytt.';
            $error_code = 'invalid_token';
        } elseif ($pending->status !== 'pending') {
            $error_message = 'Denne lenken er allerede brukt. Vennligst logg inn eller registrer deg på nytt.';
            $error_code = 'already_used';
        } elseif (strtotime($pending->expires_at) < time()) {
            $error_message = 'Verifiseringslenken har utløpt. Vennligst registrer deg på nytt.';
            $error_code = 'expired';
        } else {
            $is_valid = true;
        }
    }
}

// If user is already logged in, redirect to min-side
if (is_user_logged_in()) {
    wp_redirect(home_url('/min-side/'));
    exit;
}

// Form error messages (from POST-Redirect-GET)
$form_error_messages = array(
    'weak_password' => 'Passord må være minst 8 tegn.',
    'missing_name'  => 'Vennligst oppgi navnet ditt.',
    'user_exists'   => 'Denne e-postadressen er allerede registrert. <a href="' . esc_url(home_url('/logg-inn/')) . '" style="color: inherit; font-weight: 600;">Logg inn her</a>',
    'nonce'         => 'Noe gikk galt. Vennligst prøv igjen.',
    'system'        => 'En teknisk feil oppstod. Vennligst prøv igjen senere.',
    'token_invalid' => '', // Handled by the token validation above
);
$form_error_text = isset($form_error_messages[$form_error]) ? $form_error_messages[$form_error] : '';

// If form submission returned token_invalid, override the validation state
if ($form_error === 'token_invalid') {
    $is_valid = false;
    $error_message = 'Verifiseringslenken er ugyldig eller utløpt. Vennligst registrer deg på nytt.';
    $error_code = 'invalid_token';
}

// Prefill name from failed submission (passed via URL would be insecure, so we don't)
$prefill_name = '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktiver konto - <?php bloginfo('name'); ?></title>
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

        .steps-list {
            list-style: none;
            margin: 0 0 var(--bv-space-lg) 0;
            padding: 0 0 var(--bv-space-lg) 0;
            border-bottom: 1px solid var(--bv-border-light);
        }

        .steps-list li {
            display: flex;
            align-items: flex-start;
            gap: var(--bv-space-sm);
            margin-bottom: var(--bv-space-sm);
            font-size: var(--bv-text-base);
            color: var(--bv-text-secondary);
        }

        .steps-list li:last-child { margin-bottom: 0; }

        .step-number {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--bv-text-primary);
            color: white;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .step-number.completed {
            background: #22C55E;
        }

        .step-content strong {
            display: block;
            color: var(--bv-text-primary);
            font-weight: 600;
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
            color: #DC2626;
            margin-left: 2px;
        }

        .form-hint {
            font-size: 12px;
            color: var(--bv-text-muted);
            margin-top: 4px;
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

        .form-input::placeholder {
            color: var(--bv-text-muted);
            font-style: italic;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--bv-border-focus);
        }

        .form-input.has-error {
            border-color: #DC2626;
        }

        .email-display {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: var(--bv-space-sm);
            background: var(--bv-bg-page);
            border: 1px solid var(--bv-border-light);
            border-radius: var(--bv-radius-md);
        }

        .email-display svg {
            flex-shrink: 0;
            color: var(--bv-text-secondary);
        }

        .email-display span {
            flex: 1;
            color: var(--bv-text-primary);
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
            margin-top: var(--bv-space-sm);
        }

        .btn-secondary:hover { background: var(--bv-bg-page); }

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

        .auth-footer-links {
            margin-top: var(--bv-space-md);
            padding-top: var(--bv-space-md);
            border-top: 1px solid var(--bv-border-light);
            text-align: center;
        }

        .auth-footer-links p {
            font-size: var(--bv-text-sm);
            color: var(--bv-text-secondary);
            margin: 0 0 var(--bv-space-xs) 0;
        }

        .auth-footer-links a {
            color: var(--bv-text-primary);
            font-weight: 500;
            text-decoration: none;
        }

        .auth-footer-links a:hover { text-decoration: underline; }

        /* Alert messages */
        .alert {
            padding: var(--bv-space-sm);
            border-radius: var(--bv-radius-md);
            margin-bottom: var(--bv-space-md);
            font-size: var(--bv-text-sm);
            display: flex;
            align-items: flex-start;
            gap: var(--bv-space-xs);
            line-height: 1.5;
        }

        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }

        .alert-error a {
            color: #991B1B;
        }

        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* Error state card */
        .error-card {
            text-align: center;
        }

        .error-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto var(--bv-space-md);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-icon.expired { background: #FEF2F2; color: #DC2626; }
        .error-icon.used { background: #FEF3C7; color: #D97706; }
        .error-icon.invalid { background: #FEF2F2; color: #DC2626; }

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

            .steps-list li {
                text-align: left;
            }

            .auth-card { padding: var(--bv-space-lg); }
        }
    </style>
</head>
<body class="bv-auth-page">
    <div class="auth-container">
        <div class="auth-value">
            <a href="<?php echo home_url('/'); ?>" class="auth-logo">BIM Verdi</a>

            <?php if ($is_valid): ?>
                <h1>Fullfør registreringen</h1>
                <p class="lead">Du er nesten i mål. Oppgi navn og velg et passord for å aktivere kontoen din.</p>

                <ul class="steps-list">
                    <li>
                        <span class="step-number completed">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="step-content">
                            <strong>E-post bekreftet</strong>
                            Vi har verifisert e-postadressen din
                        </span>
                    </li>
                    <li>
                        <span class="step-number">2</span>
                        <span class="step-content">
                            <strong>Fullfør profilen</strong>
                            Oppgi navn og velg passord
                        </span>
                    </li>
                    <li>
                        <span class="step-number">3</span>
                        <span class="step-content">
                            <strong>Utforsk portalen</strong>
                            Koble til foretak, registrer verktøy og mer
                        </span>
                    </li>
                </ul>
            <?php else: ?>
                <h1>Noe gikk galt</h1>
                <p class="lead">Vi kunne ikke validere verifiseringslenken din. Dette kan skyldes at lenken har utløpt eller allerede er brukt.</p>
            <?php endif; ?>

            <p class="auth-help">Trenger du hjelp? <a href="<?php echo home_url('/kontakt/'); ?>">Kontakt oss</a></p>
        </div>

        <div class="auth-form-wrapper">
            <div class="auth-card">
                <?php if ($is_valid): ?>
                    <!-- Valid Token - Show Form -->
                    <div class="auth-card-header">
                        <h2>Fullfør registreringen</h2>
                        <p>Oppgi navn og velg passord for å aktivere kontoen din</p>
                    </div>

                    <?php if ($form_error_text): ?>
                        <div class="alert alert-error">
                            <?php echo $form_error_text; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Email Display (locked) -->
                    <div class="form-group">
                        <label class="form-label">E-postadresse</label>
                        <div class="email-display">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <polyline points="3 7 12 13 21 7"/>
                            </svg>
                            <span><?php echo esc_html($email); ?></span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Plain HTML Form -->
                    <form method="post" action="" novalidate>
                        <?php wp_nonce_field('bimverdi_verify_account'); ?>
                        <input type="hidden" name="email" value="<?php echo esc_attr($email); ?>">
                        <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">

                        <div class="form-group">
                            <label class="form-label" for="bv-name">Fullt navn <span class="required">*</span></label>
                            <input type="text" id="bv-name" name="full_name" required
                                   placeholder="Ola Nordmann"
                                   value="<?php echo esc_attr($prefill_name); ?>"
                                   class="form-input<?php echo $form_error === 'missing_name' ? ' has-error' : ''; ?>"
                                   autocomplete="name">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="bv-password">Velg et passord <span class="required">*</span></label>
                            <input type="password" id="bv-password" name="password" required
                                   placeholder="Minimum 8 tegn"
                                   class="form-input<?php echo $form_error === 'weak_password' ? ' has-error' : ''; ?>"
                                   autocomplete="new-password" minlength="8">
                            <p class="form-hint">Minimum 8 tegn. Velg noe du husker!</p>
                        </div>

                        <button type="submit" name="bimverdi_verify_account" value="1" class="btn btn-primary">
                            Aktiver kontoen min
                        </button>
                    </form>

                    <div class="auth-footer-links">
                        <p>Har du allerede en konto? <a href="<?php echo home_url('/logg-inn/'); ?>">Logg inn</a></p>
                    </div>

                <?php else: ?>
                    <!-- Invalid Token - Show Error -->
                    <div class="error-card">
                        <div class="error-icon <?php echo esc_attr($error_code === 'expired' ? 'expired' : ($error_code === 'already_used' ? 'used' : 'invalid')); ?>">
                            <?php if ($error_code === 'expired'): ?>
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            <?php elseif ($error_code === 'already_used'): ?>
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            <?php else: ?>
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                            <?php endif; ?>
                        </div>

                        <h2 style="margin-bottom: var(--bv-space-sm);">
                            <?php
                            if ($error_code === 'expired') {
                                echo 'Lenken har utløpt';
                            } elseif ($error_code === 'already_used') {
                                echo 'Allerede aktivert';
                            } else {
                                echo 'Ugyldig lenke';
                            }
                            ?>
                        </h2>

                        <p style="color: var(--bv-text-secondary); margin-bottom: var(--bv-space-lg);">
                            <?php echo esc_html($error_message); ?>
                        </p>

                        <?php if ($error_code === 'already_used'): ?>
                            <a href="<?php echo home_url('/logg-inn/'); ?>" class="btn btn-primary">Logg inn</a>
                        <?php else: ?>
                            <a href="<?php echo home_url('/registrer/'); ?>" class="btn btn-primary">Registrer deg på nytt</a>
                        <?php endif; ?>

                        <a href="<?php echo home_url('/'); ?>" class="btn btn-secondary">Gå til forsiden</a>
                    </div>
                <?php endif; ?>
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
