<?php
/**
 * Lett mal for /chat/ (HR-01). Viser bare sideinnholdet, som er [bv_hurtigreg].
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

$bvh_post = get_queried_object();

BV_Hurtigreg_Side::topp(
	'ByggChat',
	BV_Hurtigreg_Innstillinger::turnstile_paa() && BV_Hurtigreg_Innstillinger::aapent_for( BV_Hurtigreg_Skjema::kampanje() ),
	false
);

// Bare shortcodes, ikke hele the_content-kjeden. Den drar inn blokkstiler og tredjepartsfiltre siden ikke trenger.
echo do_shortcode( (string) $bvh_post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput

BV_Hurtigreg_Side::bunn();
