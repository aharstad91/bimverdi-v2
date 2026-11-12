<?php
/**
 * PDF-tekstuttrekk via medfølgende smalot/pdfparser (ren PHP).
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Pdf
 */
class BBEE_Pdf {

	/** @var bool */
	protected static $booted = false;

	/**
	 * Registrer autoloader for Smalot\PdfParser (én gang).
	 */
	public static function bootstrap() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		$base = BBEE_PLUGIN_DIR . 'includes/vendor/pdfparser/';
		spl_autoload_register(
			function ( $class ) use ( $base ) {
				if ( 0 !== strpos( $class, 'Smalot\\PdfParser\\' ) ) {
					return;
				}
				$path = $base . str_replace( '\\', '/', $class ) . '.php';
				if ( is_readable( $path ) ) {
					require_once $path;
				}
			}
		);
	}

	/**
	 * @return bool
	 */
	public static function available() {
		self::bootstrap();
		return class_exists( '\\Smalot\\PdfParser\\Parser' ) && function_exists( 'gzuncompress' );
	}

	/**
	 * @param string $path Filsti.
	 * @return string|WP_Error
	 */
	public static function extract_from_file( $path ) {
		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'bbee_pdf_unreadable', __( 'Kan ikke lese PDF-filen.', 'bumblebee' ) );
		}
		if ( ! self::available() ) {
			return new WP_Error( 'bbee_pdf_unavailable', __( 'PDF-tolkning er ikke tilgjengelig på serveren.', 'bumblebee' ) );
		}
		try {
			$parser = new \Smalot\PdfParser\Parser();
			$pdf    = $parser->parseFile( $path );
			return self::clean( $pdf->getText() );
		} catch ( \Throwable $e ) {
			return new WP_Error( 'bbee_pdf_parse', $e->getMessage() );
		}
	}

	/**
	 * @param string $data Binærdata.
	 * @return string|WP_Error
	 */
	public static function extract_from_string( $data ) {
		if ( '' === (string) $data ) {
			return new WP_Error( 'bbee_pdf_empty', __( 'Tom PDF.', 'bumblebee' ) );
		}
		if ( ! self::available() ) {
			return new WP_Error( 'bbee_pdf_unavailable', __( 'PDF-tolkning er ikke tilgjengelig på serveren.', 'bumblebee' ) );
		}
		try {
			$parser = new \Smalot\PdfParser\Parser();
			$pdf    = $parser->parseContent( $data );
			return self::clean( $pdf->getText() );
		} catch ( \Throwable $e ) {
			return new WP_Error( 'bbee_pdf_parse', $e->getMessage() );
		}
	}

	/**
	 * Finn lenker i en PDF: klikkbare lenker (annotasjoner) og nettadresser
	 * som står som tekst.
	 *
	 * @param string $data Binærdata.
	 * @param string $text Uttrukket tekst (valgfri, for synlige adresser).
	 * @param int    $max  Maks antall lenker.
	 * @return array Absolutte http(s)-URL-er, unike.
	 */
	public static function extract_links_from_string( $data, $text = '', $max = 50 ) {
		$urls = array();

		if ( '' !== (string) $data && self::available() ) {
			try {
				$parser = new \Smalot\PdfParser\Parser();
				$pdf    = $parser->parseContent( $data );
				foreach ( $pdf->getObjects() as $obj ) {
					if ( ! $obj->has( 'Subtype' ) || 'Link' !== (string) $obj->get( 'Subtype' ) ) {
						continue;
					}
					$action = $obj->get( 'A' );
					if ( $action && is_object( $action ) && method_exists( $action, 'get' ) ) {
						$uri = $action->get( 'URI' );
						if ( $uri ) {
							$urls[] = (string) ( is_object( $uri ) && method_exists( $uri, 'getContent' ) ? $uri->getContent() : $uri );
						}
					}
				}
			} catch ( \Throwable $e ) {
				// Ingen lenker fra annotasjoner; vi bruker teksten under.
				unset( $e );
			}
		}

		// Adresser som står som vanlig tekst.
		if ( '' !== (string) $text && preg_match_all( '#https?://[^\s<>"\')\]]+#i', (string) $text, $m ) ) {
			foreach ( $m[0] as $u ) {
				$urls[] = $u;
			}
		}

		$out = array();
		foreach ( $urls as $u ) {
			$u = trim( (string) $u );
			$u = rtrim( $u, ".,;:!?" );
			if ( ! preg_match( '#^https?://#i', $u ) || ! wp_http_validate_url( $u ) ) {
				continue;
			}
			$out[ $u ] = true;
			if ( count( $out ) >= $max ) {
				break;
			}
		}
		return array_keys( $out );
	}

	/**
	 * @param string $text Tekst.
	 * @return string
	 */
	protected static function clean( $text ) {
		// PDF-leseren kan gi ugyldig UTF-8. Da gir preg_replace med /u null,
		// og hele teksten forsvinner. Rett tegnkodingen først. NUL-tegn mellom
		// bokstavene (enkelte fonter) fjernes, så ordene henger sammen.
		$text = BBEE_Crawler::gyldig_utf8( str_replace( "\0", '', (string) $text ) );
		$text = (string) preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $text );
		$text = (string) preg_replace( "/[ \t]+/u", ' ', $text );
		$text = (string) preg_replace( "/\s*\n\s*/u", "\n", $text );
		$text = (string) preg_replace( "/\n{3,}/u", "\n\n", $text );
		return trim( $text );
	}
}
