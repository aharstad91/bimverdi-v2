<?php
/**
 * Spørsmålslogg med tommel opp/ned.
 *
 * Lagrer spørsmålsteksten, tidspunkt, om det ble funnet kilder og
 * hvem som stilte det (bruker-ID) og eventuell tilbakemelding. Ingen IP-adresse.
 * Maks BBEE_Log::MAX oppføringer; de eldste forsvinner først.
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Log
 */
class BBEE_Log {

	const OPTION = 'bbee_log';
	const MAX    = 500;

	/**
	 * Er loggen slått på?
	 *
	 * @return bool
	 */
	public static function enabled() {
		$settings = BBEE_Plugin::get_settings();
		return ! empty( $settings['log_enabled'] );
	}

	/**
	 * @return array
	 */
	public static function all() {
		$log = get_option( self::OPTION, array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Legg til et spørsmål.
	 *
	 * @param string $question Spørsmålet.
	 * @param int    $sources  Antall kilder funnet.
	 * @return string Logg-ID (tom hvis logging er av).
	 */
	public static function add( $question, $sources ) {
		if ( ! self::enabled() ) {
			return '';
		}
		$question = trim( (string) $question );
		$question = function_exists( 'mb_substr' ) ? mb_substr( $question, 0, 300 ) : substr( $question, 0, 300 );

		$id  = substr( md5( uniqid( '', true ) ), 0, 12 );
		$log = self::all();

		$log[ $id ] = array(
			't'  => time(),
			'u'  => (int) get_current_user_id(),
			'q'  => $question,
			'n'  => (int) $sources,
			'fb' => '',
		);

		if ( count( $log ) > self::MAX ) {
			$log = array_slice( $log, -self::MAX, null, true );
		}
		update_option( self::OPTION, $log, false );
		return $id;
	}

	/**
	 * Registrer tilbakemelding.
	 *
	 * @param string $id    Logg-ID.
	 * @param string $value up|down.
	 * @return bool
	 */
	public static function feedback( $id, $value ) {
		if ( ! in_array( $value, array( 'up', 'down' ), true ) ) {
			return false;
		}
		$log = self::all();
		if ( ! isset( $log[ $id ] ) ) {
			return false;
		}
		$log[ $id ]['fb'] = $value;
		update_option( self::OPTION, $log, false );
		return true;
	}

	/**
	 * Tøm loggen.
	 */
	public static function clear() {
		delete_option( self::OPTION );
	}
}
