<?php
/**
 * Deling og tilgang per registrering.
 *
 * Nivåer:
 *  privat    - kun registrator, tilleggskontakter og administrator
 *  deltakere - alle innloggede brukere på nettstedet
 *  utvalgte  - valgte foretak og valgte brukere (i tillegg til registrator og kontakter)
 *  offentlig - alle, også utloggede
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Access {

	const META_LEVEL    = '_bvp_tilgang';
	const META_CONTACTS = '_bvp_kontakter';
	const META_USERS    = '_bvp_tilgang_brukere';
	const META_FORETAK  = '_bvp_tilgang_foretak';
	const USER_FORETAK  = 'bvp_foretak';

	/**
	 * Nivåene med etikett og forklaring.
	 *
	 * @return array
	 */
	public static function levels() {
		return array(
			'privat'    => array(
				'label' => 'Privat',
				'desc'  => 'Kun du, tilleggskontaktene du velger, og BIM Verdi som administrator.',
			),
			'deltakere' => array(
				'label' => 'Alle registrerte brukere',
				'desc'  => 'Alle innloggede deltakere kan lese registreringen, også de fra andre foretak.',
			),
			'utvalgte'  => array(
				'label' => 'Utvalgte foretak med foretaksprofil i BIM Verdi',
				'desc'  => 'Søk opp foretaket, velg hvem der som skal få lese, i tillegg til deg og kontaktene dine.',
			),
			'offentlig' => array(
				'label' => 'Publisert for alle',
				'desc'  => 'Alle som har lenken kan lese registreringen, også uten å være innlogget.',
			),
		);
	}

	/** Gjeldende nivå. */
	public static function level( $post_id ) {
		$level = get_post_meta( (int) $post_id, self::META_LEVEL, true );
		return isset( self::levels()[ $level ] ) ? $level : 'privat';
	}

	/**
	 * Kort etikett for synlighet, til bruk i lister.
	 *
	 * @param int $post_id
	 * @param int $user_id 0 = gjeldende bruker.
	 * @return array nokkel, etikett, tittel (forklaring)
	 */
	public static function visibility_badge( $post_id, $user_id = 0 ) {
		$post_id = (int) $post_id;
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$nivaa   = self::level( $post_id );
		$nivaer  = self::levels();

		$korte = array(
			'privat'    => 'Privat',
			'deltakere' => 'Reg. brukere',
			'utvalgte'  => 'Utvalgte',
			'offentlig' => 'Alle',
		);

		$etikett = isset( $korte[ $nivaa ] ) ? $korte[ $nivaa ] : $korte['privat'];
		$tittel  = isset( $nivaer[ $nivaa ]['desc'] ) ? $nivaer[ $nivaa ]['desc'] : '';

		// Kladd er alltid privat, uansett hva som er valgt.
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return array(
				'nokkel'  => 'kladd',
				'etikett' => 'Ikke delt',
				'tittel'  => 'Kladd deles ikke. Delingen gjelder først når registreringen er sendt inn.',
			);
		}

		// Er du selv blant de utvalgte, sier vi det.
		if ( 'utvalgte' === $nivaa && $user_id && ! self::er_eier_eller_kontakt( $post_id, $user_id ) ) {
			if ( in_array( $user_id, self::allowed_users( $post_id ), true ) ||
				self::foretak_match( $post_id, $user_id ) ) {
				$etikett .= ' (deg)';
			}
		}

		return array(
			'nokkel'  => $nivaa,
			'etikett' => $etikett,
			'tittel'  => $tittel,
		);
	}

	/** Er brukeren eier eller tilleggskontakt? */
	protected static function er_eier_eller_kontakt( $post_id, $user_id ) {
		return (int) get_post_field( 'post_author', $post_id ) === (int) $user_id ||
			in_array( (int) $user_id, self::contacts( $post_id ), true );
	}

	/** Hører brukeren til et av foretakene som er gitt tilgang? */
	protected static function foretak_match( $post_id, $user_id ) {
		$mitt = (int) BVP_Foretak::for_user( $user_id );

		if ( ! $mitt ) {
			return false;
		}

		foreach ( self::allowed_foretak( $post_id ) as $valgt ) {
			if ( (int) $valgt === $mitt ) {
				return true;
			}
		}

		return false;
	}

	/** Tilleggskontakter (bruker-ID-er). De kan også redigere. */
	public static function contacts( $post_id ) {
		return self::id_liste( get_post_meta( (int) $post_id, self::META_CONTACTS, true ) );
	}

	/** Brukere som er gitt lesetilgang. */
	public static function allowed_users( $post_id ) {
		return self::id_liste( get_post_meta( (int) $post_id, self::META_USERS, true ) );
	}

	/**
	 * Rene ID-lister: tomme meta-verdier gir tom liste, ikke array( 0 ).
	 *
	 * @param mixed $verdi
	 * @return int[]
	 */
	protected static function id_liste( $verdi ) {
		return array_values( array_filter( array_map( 'intval', (array) $verdi ) ) );
	}

	/** Foretak som er gitt lesetilgang. */
	public static function allowed_foretak( $post_id ) {
		return array_filter( (array) get_post_meta( (int) $post_id, self::META_FORETAK, true ) );
	}

	/**
	 * Kan brukeren redigere registreringen?
	 *
	 * @param int|WP_Post $post
	 * @param int         $user_id
	 * @return bool
	 */
	public static function can_edit( $post, $user_id = 0 ) {
		$post    = get_post( $post );
		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! $post || BVP_Post_Type::POST_TYPE !== $post->post_type || ! $user_id ) {
			return false;
		}

		// Slettede registreringer kan ikke redigeres - de hentes tilbake fra papirkurven først.
		if ( 'trash' === $post->post_status ) {
			return false;
		}

		if ( user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		if ( (int) $post->post_author === $user_id ) {
			return true;
		}

		return in_array( $user_id, self::contacts( $post->ID ), true );
	}

	/**
	 * Kan brukeren slette registreringen?
	 *
	 * Strengere enn redigering: bare eieren og administrator. Medredaktører
	 * og tilleggskontakter kan redigere, men ikke slette.
	 *
	 * @param int|WP_Post $post
	 * @param int         $user_id 0 = gjeldende bruker.
	 * @return bool
	 */
	public static function can_delete( $post, $user_id = 0 ) {
		$post    = get_post( $post );
		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! $post || BVP_Post_Type::POST_TYPE !== $post->post_type || ! $user_id ) {
			return false;
		}

		if ( user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		return (int) $post->post_author === $user_id;
	}

	/**
	 * Kan brukeren lese registreringen?
	 *
	 * @param int|WP_Post $post
	 * @param int         $user_id 0 = gjeldende bruker (eller utlogget).
	 * @return bool
	 */
	public static function can_view( $post, $user_id = null ) {
		$post = get_post( $post );

		if ( ! $post || BVP_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$user_id = (int) $user_id;

		if ( self::can_edit( $post, $user_id ) ) {
			return true;
		}

		// Kladd deles aldri - bare innsendte registreringer.
		if ( 'publish' !== $post->post_status ) {
			return false;
		}

		$level = self::level( $post->ID );

		if ( 'offentlig' === $level ) {
			return true;
		}

		if ( ! $user_id ) {
			return false;
		}

		if ( 'deltakere' === $level ) {
			return true;
		}

		if ( 'utvalgte' === $level ) {
			if ( in_array( $user_id, self::allowed_users( $post->ID ), true ) ) {
				return true;
			}

			$foretak_id = BVP_Foretak::for_user( $user_id );
			$foretak    = self::user_foretak( $user_id );

			foreach ( self::allowed_foretak( $post->ID ) as $valgt ) {
				$valgt = trim( (string) $valgt );

				// Nye verdier er foretaks-ID-er, gamle er foretaksnavn.
				if ( ctype_digit( $valgt ) ) {
					if ( $foretak_id && (int) $valgt === $foretak_id ) {
						return true;
					}
					continue;
				}

				if ( '' !== $foretak && 0 === strcasecmp( $valgt, $foretak ) ) {
					return true;
				}

				if ( $foretak_id && 0 === strcasecmp( $valgt, BVP_Foretak::name( $foretak_id ) ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Foretaket brukeren sist registrerte på.
	 *
	 * @param int $user_id
	 * @return string
	 */
	public static function user_foretak( $user_id ) {
		return trim( (string) get_user_meta( (int) $user_id, self::USER_FORETAK, true ) );
	}

	/**
	 * Husk foretaket til brukeren (kalles ved lagring).
	 *
	 * @param int    $user_id
	 * @param string $foretak
	 */
	public static function remember_foretak( $user_id, $foretak ) {
		$foretak = trim( (string) $foretak );

		if ( $user_id && '' !== $foretak ) {
			update_user_meta( (int) $user_id, self::USER_FORETAK, $foretak );
		}
	}

	/**
	 * Registreringer som er delt med brukeren (og som ikke er hans egne).
	 *
	 * @param int $user_id
	 * @return WP_Post[]
	 */
	public static function shared_with( $user_id ) {
		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return array();
		}

		// Kladd tas med: er du medredaktør, skal du finne den igjen også før
		// den er sendt inn. can_view() slipper bare gjennom kladd du kan redigere.
		$posts = get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'posts_per_page' => 300,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'author__not_in' => array( $user_id ),
			)
		);

		return array_values(
			array_filter(
				$posts,
				static function ( $post ) use ( $user_id ) {
					return self::can_view( $post, $user_id );
				}
			)
		);
	}

	/**
	 * Alle foretaksnavn som finnes i registreringene.
	 *
	 * @return array
	 */
	public static function foretak_choices() {
		$valg = array();

		foreach ( BVP_Foretak::all() as $foretak ) {
			$valg[ (string) $foretak->ID ] = $foretak->post_title;
		}

		// Ta med foretaksnavn fra eldre registreringer som ennå ikke har profil.
		foreach ( self::legacy_foretak() as $navn ) {
			if ( ! in_array( $navn, $valg, true ) ) {
				$valg[ $navn ] = $navn . ' (uten profil)';
			}
		}

		return $valg;
	}

	/**
	 * Foretaksnavn slik de er skrevet inn i registreringene.
	 *
	 * @return array
	 */
	protected static function legacy_foretak() {
		global $wpdb;

		$key = BVP_Post_Type::meta_key( 'foretaksnavn' );

		$navn = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s
				   AND pm.meta_value <> ''
				   AND p.post_type = %s
				 ORDER BY pm.meta_value ASC
				 LIMIT 500",
				$key,
				BVP_Post_Type::POST_TYPE
			)
		);

		return array_values( array_unique( array_map( 'trim', (array) $navn ) ) );
	}

	/**
	 * Brukere som kan velges som kontakt eller leser.
	 *
	 * @return WP_User[]
	 */
	public static function user_choices() {
		return get_users(
			array(
				'orderby' => 'display_name',
				'order'   => 'ASC',
				'number'  => 500,
				'fields'  => array( 'ID', 'display_name', 'user_email' ),
			)
		);
	}

	/**
	 * Skjema for deling. Brukes både på frontend og i wp-admin.
	 *
	 * @param int $post_id
	 */
	public static function render_box( $post_id ) {
		$level    = $post_id ? self::level( $post_id ) : 'privat';
		$contacts = $post_id ? self::contacts( $post_id ) : array();
		$users    = $post_id ? self::allowed_users( $post_id ) : array();
		$foretak  = $post_id ? self::allowed_foretak( $post_id ) : array();
		$alle     = self::user_choices();
		$me       = get_current_user_id();

		// Kontaktlista hentes fra foretaket til den som eier registreringen.
		$eier = $post_id ? (int) get_post_field( 'post_author', $post_id ) : $me;
		$eier = $eier ? $eier : $me;

		$foretak_id   = BVP_Foretak::for_user( $eier );
		$foretak_navn = BVP_Foretak::name( $foretak_id );
		$kontakter    = $foretak_id ? BVP_Foretak::contacts( $foretak_id ) : array();
		$kan_velge    = ( $eier === $me ) || current_user_can( 'edit_others_posts' );
		$er_admin     = current_user_can( 'edit_others_posts' );

		// Eiervelgeren: foretak først, så personene som er registrert i foretaket.
		$eier_foretak = $foretak_id;
		$eier_navn    = $foretak_navn;
		$eier_valg    = $eier_foretak ? BVP_Foretak::members( $eier_foretak ) : $alle;

		// Dagens eier skal alltid stå i lista, også uten kobling til foretaket.
		$i_lista = false;

		foreach ( $eier_valg as $user ) {
			if ( (int) $user->ID === (int) $eier ) {
				$i_lista = true;
				break;
			}
		}

		if ( ! $i_lista ) {
			$eierdata = get_userdata( $eier );

			if ( $eierdata ) {
				$eier_valg = array_merge( array( $eierdata ), $eier_valg );
			}
		}

		// Administrator kan velge medredaktører fritt, og overføre registreringen.
		if ( $er_admin ) {
			$kontakter = array_values(
				array_filter(
					$alle,
					static function ( $user ) use ( $eier ) {
						return (int) $user->ID !== (int) $eier;
					}
				)
			);
		}
		?>
		<fieldset class="bvp-section bvp-access" id="bvp-access"
			data-bvp-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-bvp-nonce="<?php echo esc_attr( wp_create_nonce( BVP_Foretak::NONCE ) ); ?>">
			<legend class="bvp-section__title">Deling og tilgang</legend>
			<p class="bvp-section__intro">Du bestemmer hvem som kan lese registreringen. Deling gjelder først når den er sendt inn - kladd er alltid privat.</p>

			<div class="bvp-field bvp-field--radio">
				<span class="bvp-label">Hvem skal se denne registreringen?</span>
				<div class="bvp-options" role="radiogroup">
					<?php foreach ( self::levels() as $value => $info ) : ?>
						<label class="bvp-option">
							<input type="radio" name="bvp_access[level]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $level, $value ); ?> data-bvp-access-level>
							<span><strong><?php echo esc_html( $info['label'] ); ?></strong><br><span class="bvp-desc"><?php echo esc_html( $info['desc'] ); ?></span></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $er_admin ) : ?>
				<div class="bvp-field bvp-eier" id="bvp-eier-boks">
					<span class="bvp-label">Hvem eier registreringen?</span>
					<p class="bvp-desc" id="bvp-eier-desc">
						Eieren kan redigere registreringen og bestemme delingen, uansett delingsnivå - også når den er privat og alt innsendt.
						Bytter du eier, overtar den nye eieren registreringen, den forrige eieren blir stående som medredaktør,
						og du beholder tilgangen som administrator.
					</p>

					<label class="bvp-label bvp-label--liten" for="bvp-eier-foretak">1. Foretak</label>
					<p class="bvp-desc" id="bvp-eier-foretak-desc">
						Skriv de første bokstavene i foretaksnavnet (eller organisasjonsnummeret) og velg i lista.
						Tøm feltet hvis du vil velge blant alle registrerte brukere.
					</p>

					<div class="bvp-combo">
						<input type="text" id="bvp-eier-foretak" class="bvp-eier-foretak-sok" autocomplete="off"
							role="combobox" aria-expanded="false" aria-autocomplete="list"
							aria-controls="bvp-eier-foretak-treff" aria-describedby="bvp-eier-foretak-desc"
							value="<?php echo esc_attr( $eier_navn ); ?>"
							placeholder="Søk etter foretak ...">
						<ul class="bvp-combo__list" id="bvp-eier-foretak-treff" role="listbox" hidden></ul>
						<p class="bvp-combo__status bvp-eier-status" role="status" aria-live="polite"></p>
					</div>

					<input type="hidden" id="bvp-eier-foretak-id" name="bvp_access[eier_foretak]" value="<?php echo (int) $eier_foretak; ?>">

					<label class="bvp-label bvp-label--liten" for="bvp-eier">2. Person i foretaket</label>
					<select id="bvp-eier" name="bvp_access[eier]" aria-describedby="bvp-eier-desc">
						<?php foreach ( $eier_valg as $user ) : ?>
							<?php $brukerforetak = BVP_Foretak::name( BVP_Foretak::for_user( $user->ID ) ); ?>
							<option value="<?php echo (int) $user->ID; ?>" <?php selected( (int) $user->ID, (int) $eier ); ?>>
								<?php echo esc_html( $user->display_name . ( $brukerforetak ? ' - ' . $brukerforetak : '' ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<?php if ( $eier_foretak && count( $eier_valg ) < 2 ) : ?>
						<p class="bvp-desc"><?php echo esc_html( $eier_navn ); ?> har ingen andre registrerte brukere. Tøm foretaksfeltet for å velge blant alle.</p>
					<?php endif; ?>

					<template id="bvp-eier-alle">
						<?php foreach ( $alle as $user ) : ?>
							<?php $bf2 = BVP_Foretak::name( BVP_Foretak::for_user( $user->ID ) ); ?>
							<option value="<?php echo (int) $user->ID; ?>" <?php selected( (int) $user->ID, (int) $eier ); ?>>
								<?php echo esc_html( $user->display_name . ( $bf2 ? ' - ' . $bf2 : '' ) ); ?>
							</option>
						<?php endforeach; ?>
					</template>
				</div>
			<?php endif; ?>

			<div class="bvp-field">
				<label class="bvp-label" for="bvp-kontakter"><?php echo $er_admin ? 'Medredaktører' : 'Tilleggskontakter' . ( $foretak_navn ? ' i ' . esc_html( $foretak_navn ) : '' ); ?></label>

				<?php if ( $er_admin ) : ?>

					<div class="bvp-medred" id="bvp-medred-boks">
						<p class="bvp-desc" id="bvp-kontakter-desc">
							Som administrator kan du velge hvem som helst, også fra andre foretak.
							De kan lese <em>og</em> redigere registreringen. Finn foretaket først, så personen.
						</p>

						<label class="bvp-label bvp-label--liten" for="bvp-medred-foretak">1. Foretak</label>
						<p class="bvp-desc" id="bvp-medred-foretak-desc">Skriv de første bokstavene i foretaksnavnet (eller organisasjonsnummeret) og velg i lista.</p>

						<div class="bvp-combo">
							<input type="text" id="bvp-medred-foretak" class="bvp-medred-foretak-sok" autocomplete="off"
								role="combobox" aria-expanded="false" aria-autocomplete="list"
								aria-controls="bvp-medred-treff" aria-describedby="bvp-medred-foretak-desc"
								placeholder="Søk etter foretak ...">
							<ul class="bvp-combo__list" id="bvp-medred-treff" role="listbox" hidden></ul>
							<p class="bvp-combo__status bvp-medred-status" role="status" aria-live="polite"></p>
						</div>

						<label class="bvp-label bvp-label--liten" for="bvp-medred-person">2. Person</label>
						<p class="bvp-medred__rad">
							<select id="bvp-medred-person" class="bvp-medred-person" disabled>
								<option value="">- velg foretak først -</option>
							</select>
							<button type="button" class="bvp-button bvp-button--secondary bvp-medred-legg-til">Legg til</button>
						</p>

						<ul class="bvp-medred__valgte" id="bvp-medred-valgte">
							<?php foreach ( $contacts as $contact_id ) : ?>
								<?php $contact = get_userdata( (int) $contact_id ); ?>
								<?php if ( ! $contact ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<?php $cf = BVP_Foretak::name( BVP_Foretak::for_user( (int) $contact_id ) ); ?>
								<li class="bvp-medred__kort" data-id="<?php echo (int) $contact_id; ?>">
									<input type="hidden" name="bvp_access[contacts][]" value="<?php echo (int) $contact_id; ?>">
									<span class="bvp-medred__navn"><?php echo esc_html( $contact->display_name ); ?></span>
									<?php if ( $cf ) : ?>
										<span class="bvp-desc"><?php echo esc_html( $cf ); ?></span>
									<?php endif; ?>
									<button type="button" class="bvp-fjern-medred" aria-label="Fjern <?php echo esc_attr( $contact->display_name ); ?>">Fjern</button>
								</li>
							<?php endforeach; ?>
						</ul>

						<p class="bvp-desc bvp-medred__tom"<?php echo $contacts ? ' hidden' : ''; ?>>Ingen medredaktører er valgt ennå.</p>

						<details class="bvp-medred__alle">
							<summary>Finn i hele brukerlista i stedet</summary>
							<p class="bvp-desc" id="bvp-kontakter-alle-desc">
								Her kan du legge til brukere som ikke er koblet til et foretak. Hold inne Ctrl/Cmd for å velge flere.
								De du velger her, legges til i lista over når du lagrer.
							</p>
							<select id="bvp-kontakter" name="bvp_access[contacts][]" multiple size="8" aria-describedby="bvp-kontakter-alle-desc">
								<?php foreach ( $kontakter as $user ) : ?>
									<?php if ( in_array( (int) $user->ID, $contacts, true ) ) : ?>
										<?php continue; ?>
									<?php endif; ?>
									<?php $bf = BVP_Foretak::name( BVP_Foretak::for_user( $user->ID ) ); ?>
									<option value="<?php echo (int) $user->ID; ?>">
										<?php echo esc_html( $user->display_name . ( $bf ? ' - ' . $bf : '' ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</details>
					</div>

				<?php elseif ( ! $foretak_id ) : ?>

					<p class="bvp-desc">Tilleggskontakter hentes fra foretaket ditt. Velg foretaket under, så fyller lista seg når du lagrer.</p>

					<?php if ( $kan_velge && BVP_Foretak::all() ) : ?>
						<select id="bvp-velg-foretak" name="bvp_access[foretak_id]">
							<option value="0">- velg foretak -</option>
							<?php foreach ( BVP_Foretak::all() as $valgbart ) : ?>
								<option value="<?php echo (int) $valgbart->ID; ?>"><?php echo esc_html( $valgbart->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="bvp-desc">Finner du ikke foretaket ditt, ta kontakt med BIM Verdi så oppretter vi en foretaksprofil.</p>
					<?php else : ?>
						<p class="bvp-desc">Ingen foretaksprofiler er opprettet ennå. Ta kontakt med BIM Verdi.</p>
					<?php endif; ?>

				<?php elseif ( empty( $kontakter ) ) : ?>

					<p class="bvp-desc">
						<?php echo esc_html( $foretak_navn ); ?> har ingen registrerte hovedkontakter eller tilleggskontakter ennå.
						Ta kontakt med BIM Verdi for å få lagt dem inn.
					</p>

				<?php else : ?>

					<p class="bvp-desc" id="bvp-kontakter-desc">
						Hovedkontakt og tilleggskontakter i <?php echo esc_html( $foretak_navn ); ?>. Disse kan lese <em>og</em> redigere
						registreringen sammen med deg. Hold inne Ctrl/Cmd for å velge flere.
					</p>
					<select id="bvp-kontakter" name="bvp_access[contacts][]" multiple size="6" aria-describedby="bvp-kontakter-desc">
						<?php foreach ( $kontakter as $user ) : ?>
							<?php if ( (int) $user->ID === (int) $eier ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<option value="<?php echo (int) $user->ID; ?>" <?php selected( in_array( (int) $user->ID, $contacts, true ) ); ?>>
								<?php echo esc_html( $user->display_name . ' - ' . BVP_Foretak::persontyper()[ BVP_Foretak::persontype( $user->ID ) ] ); ?>
							</option>
						<?php endforeach; ?>
					</select>

				<?php endif; ?>
			</div>

			<div class="bvp-field bvp-access-selected" data-bvp-depends-access="utvalgte">
				<label class="bvp-label" for="bvp-foretak-sok">Finn foretak med foretaksprofil</label>
				<p class="bvp-desc" id="bvp-foretak-sok-desc">
					Skriv de første bokstavene i foretaksnavnet (eller organisasjonsnummeret) og velg i lista.
					Når foretaket er valgt, ser du kontaktpersonene der og kan krysse av for riktig person.
				</p>

				<div class="bvp-combo">
					<input type="text" id="bvp-foretak-sok" class="bvp-foretak-sok" autocomplete="off"
						role="combobox" aria-expanded="false" aria-autocomplete="list"
						aria-controls="bvp-foretak-treff" aria-describedby="bvp-foretak-sok-desc"
						placeholder="Søk etter foretak ...">
					<ul class="bvp-combo__list" id="bvp-foretak-treff" role="listbox" hidden></ul>
					<p class="bvp-combo__status" role="status" aria-live="polite"></p>
				</div>

				<div class="bvp-valgte-foretak" id="bvp-valgte-foretak">
					<?php
					foreach ( self::valgte_foretak( $post_id ) as $valgt ) {
						self::render_foretak_kort( $valgt['id'], $valgt['hele'], $users, $valgt['navn'] );
					}
					?>
				</div>

				<p class="bvp-desc bvp-valgte-foretak__tom">Ingen foretak er valgt ennå.</p>
			</div>

		</fieldset>
		<?php
	}

	/**
	 * Foretakene som allerede er valgt på registreringen - både profiler og gamle navn.
	 *
	 * @param int $post_id
	 * @return array [ id, navn, hele ]
	 */
	protected static function valgte_foretak( $post_id ) {
		if ( ! $post_id ) {
			return array();
		}

		$valgte = array();

		foreach ( self::allowed_foretak( $post_id ) as $verdi ) {
			$verdi = trim( (string) $verdi );

			if ( ctype_digit( $verdi ) ) {
				$valgte[ $verdi ] = array(
					'id'   => (int) $verdi,
					'navn' => BVP_Foretak::name( (int) $verdi ),
					'hele' => true,
				);
			} else {
				$valgte[ $verdi ] = array(
					'id'   => 0,
					'navn' => $verdi,
					'hele' => true,
				);
			}
		}

		// Foretak som bare er representert ved en valgt person.
		foreach ( self::allowed_users( $post_id ) as $user_id ) {
			$foretak_id = BVP_Foretak::for_user( $user_id );

			if ( $foretak_id && ! isset( $valgte[ (string) $foretak_id ] ) ) {
				$valgte[ (string) $foretak_id ] = array(
					'id'   => $foretak_id,
					'navn' => BVP_Foretak::name( $foretak_id ),
					'hele' => false,
				);
			}
		}

		return array_values( $valgte );
	}

	/**
	 * Ett valgt foretak med kontaktpersonene sine.
	 *
	 * @param int    $foretak_id 0 for gamle foretaksnavn uten profil.
	 * @param bool   $hele       Er hele foretaket krysset av?
	 * @param array  $valgte_brukere
	 * @param string $navn
	 */
	public static function render_foretak_kort( $foretak_id, $hele, $valgte_brukere, $navn = '' ) {
		$foretak_id = (int) $foretak_id;
		$navn       = $navn ? $navn : BVP_Foretak::name( $foretak_id );
		$orgnr      = $foretak_id ? BVP_Foretak::orgnr( $foretak_id ) : '';
		$kontakter  = $foretak_id ? BVP_Foretak::contacts_payload( $foretak_id ) : array();
		$verdi      = $foretak_id ? (string) $foretak_id : $navn;
		?>
		<div class="bvp-foretak-kort" data-id="<?php echo esc_attr( $verdi ); ?>">
			<div class="bvp-foretak-kort__head">
				<strong><?php echo esc_html( $navn ); ?></strong>
				<?php if ( $orgnr ) : ?>
					<span class="bvp-desc"><?php echo esc_html( $orgnr ); ?></span>
				<?php endif; ?>
				<?php if ( ! $foretak_id ) : ?>
					<span class="bvp-desc">(uten foretaksprofil)</span>
				<?php endif; ?>
				<button type="button" class="bvp-fjern-foretak" aria-label="Fjern <?php echo esc_attr( $navn ); ?>">Fjern</button>
			</div>

			<label class="bvp-option">
				<input type="checkbox" name="bvp_access[foretak][]" value="<?php echo esc_attr( $verdi ); ?>" <?php checked( (bool) $hele ); ?>>
				<span>Hele foretaket får lese</span>
			</label>

			<?php if ( $foretak_id ) : ?>
				<div class="bvp-foretak-kort__kontakter">
					<?php if ( empty( $kontakter ) ) : ?>
						<p class="bvp-desc">Ingen registrerte kontaktpersoner i dette foretaket.</p>
					<?php else : ?>
						<p class="bvp-desc">Eller velg enkeltpersoner:</p>
						<?php foreach ( $kontakter as $kontakt ) : ?>
							<label class="bvp-option">
								<input type="checkbox" name="bvp_access[users][]" value="<?php echo (int) $kontakt['id']; ?>" <?php checked( in_array( (int) $kontakt['id'], array_map( 'intval', $valgte_brukere ), true ) ); ?>>
								<span><?php echo esc_html( $kontakt['navn'] . ' - ' . $kontakt['type'] ); ?></span>
							</label>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Lagre delingsvalgene.
	 *
	 * @param int   $post_id
	 * @param array $raw $_POST['bvp_access']
	 */
	public static function save( $post_id, $raw ) {
		$post_id = (int) $post_id;

		if ( ! $post_id || ! self::can_edit( $post_id ) ) {
			return;
		}

		$raw   = is_array( $raw ) ? $raw : array();
		$level = isset( $raw['level'] ) ? sanitize_key( $raw['level'] ) : 'privat';
		$level = isset( self::levels()[ $level ] ) ? $level : 'privat';

		update_post_meta( $post_id, self::META_LEVEL, $level );

		$eier     = (int) get_post_field( 'post_author', $post_id );
		$er_admin = current_user_can( 'edit_others_posts' );

		// Administrator kan overføre registreringen til en annen bruker.
		$forrige_eier = 0;

		if ( $er_admin && ! empty( $raw['eier'] ) ) {
			$ny_eier = absint( $raw['eier'] );

			if ( $ny_eier && $ny_eier !== $eier && get_userdata( $ny_eier ) ) {
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_author' => $ny_eier,
					)
				);

				$forrige_eier = $eier;
				$eier         = $ny_eier;

				/**
				 * Eierskapet er flyttet.
				 *
				 * @param int $post_id
				 * @param int $ny_eier
				 * @param int $forrige_eier
				 */
				do_action( 'bvp_eier_byttet', $post_id, $ny_eier, $forrige_eier );
			}
		}

		// Brukeren kan koble seg til et foretak herfra hvis han ikke er koblet fra før.
		if ( ! empty( $raw['foretak_id'] ) && ! BVP_Foretak::for_user( $eier ) ) {
			BVP_Foretak::link_user( $eier, absint( $raw['foretak_id'] ) );
		}

		$foretak_id = BVP_Foretak::for_user( $eier );
		$lovlige    = array_map(
			static function ( $user ) {
				return (int) $user->ID;
			},
			$foretak_id ? BVP_Foretak::contacts( $foretak_id ) : array()
		);

		$contacts = isset( $raw['contacts'] ) ? array_map( 'absint', (array) $raw['contacts'] ) : array();
		$contacts = array_values( array_filter( array_unique( $contacts ), 'get_userdata' ) );

		// Bare kontaktpersoner i eierens eget foretak kan legges til - administrator står fritt.
		if ( ! $er_admin ) {
			$contacts = array_values( array_intersect( $contacts, $lovlige ) );
		}

		// Den forrige eieren beholder tilgangen som medredaktør, slik at
		// arbeidet hans ikke blir utilgjengelig når eierskapet flyttes.
		if ( $forrige_eier && ! in_array( $forrige_eier, $contacts, true ) && get_userdata( $forrige_eier ) ) {
			$contacts[] = $forrige_eier;
		}
		$contacts = array_values( array_diff( $contacts, array( $eier ) ) );

		update_post_meta( $post_id, self::META_CONTACTS, $contacts );

		$users = isset( $raw['users'] ) ? array_map( 'absint', (array) $raw['users'] ) : array();
		$users = array_values( array_filter( array_unique( $users ), 'get_userdata' ) );
		update_post_meta( $post_id, self::META_USERS, $users );

		$foretak = isset( $raw['foretak'] ) ? (array) $raw['foretak'] : array();
		$foretak = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $foretak ) ) ) );
		update_post_meta( $post_id, self::META_FORETAK, $foretak );
	}

	/**
	 * Kort beskrivelse av tilgangsnivået.
	 *
	 * @param int $post_id
	 * @return string
	 */
	public static function summary( $post_id ) {
		$level  = self::level( $post_id );
		$levels = self::levels();
		$tekst  = $levels[ $level ]['label'];

		if ( 'utvalgte' === $level ) {
			$antall = count( self::allowed_foretak( $post_id ) ) + count( self::allowed_users( $post_id ) );
			$tekst .= sprintf( ' (%d valgt)', $antall );
		}

		$kontakter = count( self::contacts( $post_id ) );


		if ( $kontakter ) {
			$tekst .= sprintf( ' + %d kontakt%s', $kontakter, 1 === $kontakter ? '' : 'er' );
		}

		return $tekst;
	}
}
