<?php
/**
 * Mal: Bumblebee – fullskjerm chat.
 *
 * Eget HTML-dokument uten temaets header og footer. wp_head() og wp_footer()
 * kjøres, så skript, stiler og adminlinjen virker som vanlig.
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bbee_logo  = get_template_directory_uri() . '/assets/img/bimverdi-logo.png';
$bbee_login = home_url( '/logg-inn/?redirect_to=' . rawurlencode( get_permalink() ) );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="robots" content="noindex, nofollow">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bbee-app-body' ); ?>>
<?php wp_body_open(); ?>

<div class="bbee-app is-empty" id="bbee-app">

	<header class="bbee-app__bar">
		<a class="bbee-app__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Til forsiden av BIM Verdi', 'bumblebee' ); ?>">
			<img src="<?php echo esc_url( $bbee_logo ); ?>" alt="BIM Verdi" height="28">
		</a>
		<span class="bbee-app__name">Bumblebee <span class="bbee-app__tag"><?php esc_html_e( 'Test', 'bumblebee' ); ?></span></span>

		<?php if ( ! post_password_required() && BBEE_Plugin::user_can_chat() ) : ?>
			<button type="button" class="bbee-app__new" id="bbee-new" hidden>
				<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"/></svg>
				<span><?php esc_html_e( 'Ny samtale', 'bumblebee' ); ?></span>
			</button>
		<?php endif; ?>
	</header>

	<main class="bbee-app__main">
		<?php if ( post_password_required() ) : ?>

			<section class="bbee-app__hero bbee-app__col">
				<h1 class="bbee-app__title"><?php esc_html_e( 'Siden er beskyttet med passord', 'bumblebee' ); ?></h1>
				<?php echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</section>

		<?php elseif ( ! is_user_logged_in() ) : ?>

			<section class="bbee-app__hero bbee-app__col">
				<h1 class="bbee-app__title"><?php esc_html_e( 'Logg inn for å bruke Bumblebee', 'bumblebee' ); ?></h1>
				<p class="bbee-app__lead"><?php esc_html_e( 'Bumblebee svarer på fagspørsmål fra kunnskapskildene på BIM Verdi. Chatten er bare åpen for innloggede brukere.', 'bumblebee' ); ?></p>
				<a class="bbee-app__login" href="<?php echo esc_url( $bbee_login ); ?>"><?php esc_html_e( 'Logg inn', 'bumblebee' ); ?></a>
			</section>

		<?php elseif ( ! BBEE_Plugin::user_can_chat() ) : ?>

			<section class="bbee-app__hero bbee-app__col">
				<h1 class="bbee-app__title"><?php esc_html_e( 'Du har ikke tilgang ennå', 'bumblebee' ); ?></h1>
				<p class="bbee-app__lead"><?php esc_html_e( 'Bumblebee er i test og er bare åpen for noen få brukere.', 'bumblebee' ); ?></p>
			</section>

		<?php else : ?>

			<div class="bbee-app__scroll" id="bbee-scroll">
				<div class="bbee-app__col">
					<section class="bbee-app__hero" id="bbee-hero">
						<h1 class="bbee-app__title" id="bbee-greeting"><?php esc_html_e( 'Hva lurer du på?', 'bumblebee' ); ?></h1>
						<p class="bbee-app__lead"><?php esc_html_e( 'Still et fagspørsmål om bygg, BIM eller regelverk. Bumblebee svarer fra kunnskapskildene på BIM Verdi og viser hvor svaret kommer fra.', 'bumblebee' ); ?></p>
					</section>
					<div class="bbee-app__thread" id="bbee-thread" aria-live="polite"></div>
				</div>
			</div>

			<div class="bbee-app__dock">
				<div class="bbee-app__col">
					<form class="bbee-app__composer" id="bbee-form" autocomplete="off">
						<label class="screen-reader-text" for="bbee-input"><?php esc_html_e( 'Skriv spørsmålet ditt', 'bumblebee' ); ?></label>
						<textarea id="bbee-input" rows="1" placeholder="<?php esc_attr_e( 'Spør om bygg, BIM eller regelverk …', 'bumblebee' ); ?>"></textarea>
						<button type="submit" class="bbee-app__send" id="bbee-send" disabled aria-label="<?php esc_attr_e( 'Send', 'bumblebee' ); ?>">
							<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
						</button>
					</form>
					<div class="bbee-app__suggestions" id="bbee-suggestions"></div>
					<p class="bbee-app__note"><?php esc_html_e( 'Bumblebee kan ta feil. Sjekk viktige opplysninger i kilden.', 'bumblebee' ); ?></p>
				</div>
			</div>

		<?php endif; ?>
	</main>
</div>

<?php wp_footer(); ?>
</body>
</html>
