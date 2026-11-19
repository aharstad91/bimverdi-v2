<?php
/**
 * Daglig opprydding (HR-11): påminnelser, deaktivering etter utløp og
 * anonymisering etter ytterligere en periode (Å-4). Hver kjøring logges.
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Cron {

	const LOGG_OPTION = 'bv_hurtigreg_logg';
	const LOGG_MAKS   = 60;

	public static function start(): void {
		add_action( BV_Hurtigreg::CRON_HOOK, array( __CLASS__, 'kjor' ) );
		// Planlegg på nytt hvis hendelsen har forsvunnet (f.eks. etter flytting av databasen).
		add_action( 'admin_init', array( __CLASS__, 'planlegg' ) );
	}

	public static function planlegg(): void {
		if ( wp_next_scheduled( BV_Hurtigreg::CRON_HOOK ) ) {
			return;
		}
		// Kl. 03:17 norsk tid, utenfor trafikktoppen.
		$neste = new DateTimeImmutable( 'tomorrow 03:17', wp_timezone() );
		wp_schedule_event( $neste->getTimestamp(), 'daily', BV_Hurtigreg::CRON_HOOK );
	}

	/** @return array<string,int> Antall per handling. */
	public static function kjor(): array {
		$start = microtime( true );
		$telt  = array( 'sjekket' => 0, 'paaminnelse_1' => 0, 'paaminnelse_2' => 0, 'deaktivert' => 0, 'anonymisert' => 0, 'venter_foretak' => 0, 'feil' => 0 );

		$na       = time();
		$dag      = DAY_IN_SECONDS;
		$utlop    = BV_Hurtigreg_Innstillinger::tall( 'utlop_dager' ) * $dag;
		$slett    = BV_Hurtigreg_Innstillinger::tall( 'slett_dager' ) * $dag;
		$p1       = BV_Hurtigreg_Innstillinger::tall( 'paaminnelse_1_dager' ) * $dag;
		$p2       = max( 0, $utlop - BV_Hurtigreg_Innstillinger::tall( 'paaminnelse_2_for' ) * $dag );

		$ider = get_users( array(
			'fields'     => 'ID',
			'number'     => -1, // Bare ID-er. Billig også med mange tusen.
			'orderby'    => 'ID',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'AND',
				array( 'key' => BV_Hurtigreg::META_OPPRETTET, 'compare' => 'EXISTS' ),
				array( 'key' => BV_Hurtigreg::META_FULLFORT, 'compare' => 'NOT EXISTS' ),
				array( 'key' => BV_Hurtigreg::META_ANONYMISERT, 'compare' => 'NOT EXISTS' ),
			),
		) );

		foreach ( $ider as $id ) {
			$id = (int) $id;
			++$telt['sjekket'];
			try {
				$utlopt = (int) get_user_meta( $id, BV_Hurtigreg::META_UTLOPT, true );
				if ( $utlopt ) {
					if ( $na - $utlopt >= $slett && BV_Hurtigreg_Bruker::anonymiser( $id, 'utløpt' ) ) {
						++$telt['anonymisert'];
					}
					continue;
				}

				$alder = $na - (int) get_user_meta( $id, BV_Hurtigreg::META_OPPRETTET, true );

				if ( $alder >= $utlop ) {
					// Har brukeren registrert et foretak som venter på godkjenning, er
					// de midt i fullføringen. Da skal tilgangen ikke stenges.
					if ( BV_Hurtigreg_Bruker::har_ventende_foretak( $id ) ) {
						++$telt['venter_foretak'];
						continue;
					}
					BV_Hurtigreg_Bruker::deaktiver( $id );
					++$telt['deaktivert'];
					continue;
				}

				$sendt = get_user_meta( $id, BV_Hurtigreg::META_PAAMINNELSE, true );
				$sendt = is_array( $sendt ) ? $sendt : array();

				if ( $alder >= $p2 && empty( $sendt[2] ) ) {
					// Hver påminnelse sendes én gang. Er begge forfalt samtidig, sendes bare den siste.
					$sendt[1] = $sendt[1] ?? $na;
					$sendt[2] = $na;
					update_user_meta( $id, BV_Hurtigreg::META_PAAMINNELSE, $sendt );
					BV_Hurtigreg_Epost::paaminnelse( $id, 2 );
					++$telt['paaminnelse_2'];
				} elseif ( $alder >= $p1 && empty( $sendt[1] ) ) {
					$sendt[1] = $na;
					update_user_meta( $id, BV_Hurtigreg::META_PAAMINNELSE, $sendt );
					BV_Hurtigreg_Epost::paaminnelse( $id, 1 );
					++$telt['paaminnelse_1'];
				}
			} catch ( Throwable $e ) {
				++$telt['feil'];
				BV_Hurtigreg_Bruker::logg( sprintf( 'Cron-feil for #%d: %s', $id, $e->getMessage() ) );
			}
		}

		$telt['sekunder'] = (int) round( microtime( true ) - $start );
		self::skriv_logg( $telt );
		return $telt;
	}

	private static function skriv_logg( array $telt ): void {
		$logg = get_option( self::LOGG_OPTION, array() );
		$logg = is_array( $logg ) ? $logg : array();
		array_unshift( $logg, array( 'tid' => time() ) + $telt );
		update_option( self::LOGG_OPTION, array_slice( $logg, 0, self::LOGG_MAKS ), false );

		$deler = array();
		foreach ( $telt as $k => $v ) {
			$deler[] = $k . '=' . $v;
		}
		BV_Hurtigreg_Bruker::logg( 'Daglig kjøring: ' . implode( ', ', $deler ) );
	}
}
