<?php
/**
 * Den lette sidemalen for /chat/ og hjelpesidene under den (HR-01):
 * ingen meny, ingen hero og ingen temaskript. Bare logoene, én kolonne og
 * inline-CSS, så siden er rask over 4G.
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Side {

	public static function topp( string $tittel, bool $turnstile = false, bool $noindex = true ): void {
		$tema = get_template_directory_uri() . '/assets/img/';
		?>
<!DOCTYPE html>
<html lang="nb">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo esc_html( $tittel ); ?> – BIM Verdi</title>
<?php if ( $noindex ) : ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<meta name="theme-color" content="#F5F3EE">
<link rel="icon" href="<?php echo esc_url( get_site_icon_url( 64 ) ?: $tema . 'favicon/favicon.ico' ); ?>">
<?php if ( $turnstile ) : ?>
<link rel="preconnect" href="https://challenges.cloudflare.com">
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<style>
:root{--bg:#F5F3EE;--kort:#fff;--tekst:#1A1A1A;--dempet:#6B6B6B;--linje:#D6D1C6;--aksent:#FF8B5E;--aksent-mork:#E5764A;--feil:#B42318;--feil-bg:#FEF3F2}
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--bg);color:var(--tekst);font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif}
.bvh-side{max-width:440px;margin:0 auto;padding:24px 16px calc(32px + env(safe-area-inset-bottom))}
.bvh-logoer{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:4px 0 24px}
.bvh-logoer img{display:block;height:32px;width:auto}
.bvh-kort{background:var(--kort);border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:24px 20px}
.bvh-h,.bvh-kort h1{font-size:22px;line-height:1.25;margin:0 0 12px}
.bvh-intro{margin:0 0 20px;font-size:17px}
.bvh-felt{margin:0 0 16px}
.bvh-felt label{display:block;font-weight:600;font-size:15px;margin:0 0 6px}
.bvh-felt input[type=text],.bvh-felt input[type=email],.bvh-felt input[type=tel],.bvh-felt input[type=password]{width:100%;font:inherit;font-size:17px;padding:12px 14px;border:1px solid var(--linje);border-radius:8px;background:#fff;color:var(--tekst);-webkit-appearance:none;appearance:none}
.bvh-felt input:focus{outline:2px solid var(--aksent);outline-offset:1px;border-color:var(--aksent)}
.bvh-felt input[readonly]{background:#F7F6F3;color:var(--dempet)}
.bvh-felt.har-feil input{border-color:var(--feil)}
.bvh-feil{color:var(--feil);font-size:14px;margin:6px 0 0;min-height:0}
.bvh-feil:empty{display:none}
.bvh-hjelp{color:var(--dempet);font-size:14px;margin:6px 0 0}
.bvh-avkryss label{display:flex;gap:10px;align-items:flex-start;font-weight:400;font-size:15px}
.bvh-avkryss input{width:22px;height:22px;margin:1px 0 0;flex:none;accent-color:var(--aksent)}
.bvh-hp{position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden}
.bvh-knapp{display:block;width:100%;font:inherit;font-size:17px;font-weight:600;color:#fff;background:var(--aksent);border:0;border-radius:8px;padding:14px 16px;cursor:pointer;text-align:center;text-decoration:none}
.bvh-knapp:hover,.bvh-knapp:focus{background:var(--aksent-mork)}
.bvh-knapp[disabled]{opacity:.7}
.bvh-knapp.sekundaer{background:#1A1A1A}
.bvh-liten{text-align:center;color:var(--dempet);font-size:15px;margin:20px 0 0}
.bvh a{color:var(--aksent-mork)}
.bvh-feilboks{background:var(--feil-bg);color:var(--feil);border-radius:8px;padding:12px 14px;margin:0 0 16px;font-size:15px}
.bvh-boks p{margin:0 0 12px}
.cf-turnstile{margin:0 0 12px}
</style>
</head>
<body>
<main class="bvh-side">
	<div class="bvh-logoer">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( $tema . 'bimverdi-logo.png' ); ?>" alt="BIM Verdi" height="32"></a>
		<img src="<?php echo esc_url( $tema . 'byggchat-alfa-logo.png' ); ?>" alt="ByggChat" height="32">
	</div>
	<div class="bvh-kort bvh">
		<?php
	}

	public static function bunn(): void {
		?>
	</div>
</main>
</body>
</html>
		<?php
	}

	/** En enkel melding på en lett side. Avslutter forespørselen. */
	public static function melding( string $tittel, string $html, int $status = 200 ): void {
		status_header( $status );
		nocache_headers();
		self::topp( $tittel );
		echo '<h1>' . esc_html( $tittel ) . '</h1>' . $html; // phpcs:ignore WordPress.Security.EscapeOutput
		self::bunn();
		exit;
	}
}
