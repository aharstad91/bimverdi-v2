<?php
/**
 * Skjemaeditor: legg til, endre, flytt og slett felt og seksjoner fra wp-admin.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Editor {

	const PAGE       = 'bvp-form-editor';
	const PREVIEW    = 'bvp-form-preview';
	const CAP        = 'manage_options';
	const NONCE      = 'bvp_schema_editor';
	const SAVE       = 'bvp_save_schema';
	const RESET      = 'bvp_reset_schema';
	const EXPORT     = 'bvp_export_schema';
	const IMPORT     = 'bvp_import_schema';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_' . self::SAVE, array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_' . self::RESET, array( __CLASS__, 'handle_reset' ) );
		add_action( 'admin_post_bvp_force_schema', array( __CLASS__, 'handle_force' ) );
		add_action( 'admin_post_' . self::EXPORT, array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_' . self::IMPORT, array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function menu() {
		$parent = 'edit.php?post_type=' . BVP_Post_Type::POST_TYPE;

		add_submenu_page( $parent, 'Skjemaeditor', 'Skjemaeditor', self::CAP, self::PAGE, array( __CLASS__, 'render' ) );
		add_submenu_page( $parent, 'Forhåndsvis skjema', 'Forhåndsvis skjema', self::CAP, self::PREVIEW, array( __CLASS__, 'render_preview' ) );
	}

	public static function assets( $hook ) {
		if ( ! is_string( $hook ) || false === strpos( $hook, self::PAGE ) ) {
			if ( ! is_string( $hook ) || false === strpos( $hook, self::PREVIEW ) ) {
				return;
			}
		}

		wp_enqueue_style( 'bvp-form', BVP_URL . 'assets/css/bvp-form.css', array(), BVP_VERSION );
		wp_enqueue_style( 'bvp-editor', BVP_URL . 'assets/css/bvp-editor.css', array( 'bvp-form' ), BVP_VERSION );
		wp_enqueue_script( 'bvp-form', BVP_URL . 'assets/js/bvp-form.js', array(), BVP_VERSION, true );
		wp_enqueue_script( 'bvp-editor', BVP_URL . 'assets/js/bvp-editor.js', array(), BVP_VERSION, true );
	}

	/* ------------------------------------------------------------------ */
	/* Visning                                                             */
	/* ------------------------------------------------------------------ */

	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Du har ikke tilgang til skjemaeditoren.' );
		}

		$sections = BVP_Fields::sections();
		$all      = BVP_Fields::all( 'all' );
		?>
		<div class="wrap bvp-editor">
			<h1>Skjemaeditor - BV-pilot/prosjekt</h1>
			<?php self::notices(); ?>

			<p class="bvp-ed-intro">
				Her endrer du feltene deltakerne fyller ut. Endringer gjelder umiddelbart for nye og eksisterende registreringer.
				<strong>Sletter du et felt, blir svarene liggende i databasen</strong>, men de vises ikke lenger i skjema, e-post eller CSV.
				<?php if ( BVP_Schema::is_customized() ) : ?>
					<br><em>Skjemaet er tilpasset. Du kan når som helst gå tilbake til standardoppsettet nederst på siden.</em>
				<?php else : ?>
					<br><em>Skjemaet kjører på standardoppsettet fra Formidable-skjema 39.</em>
				<?php endif; ?>
			</p>

			<?php self::render_tittelinfo(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="bvp-editor-form">
				<?php wp_nonce_field( self::NONCE, 'bvp_nonce' ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE ); ?>">

				<input type="hidden" name="bvp_basis" value="<?php echo esc_attr( (string) get_option( 'bvp_schema_saved', '0' ) ); ?>">

				<p class="bvp-ed-toolbar">
					<button type="submit" class="button button-primary">Lagre skjema</button>
					<button type="button" class="button bvp-skjul-alle">Skjul alle seksjoner</button>
					<button type="button" class="button bvp-vis-alle">Vis alle</button>
					<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . BVP_Post_Type::POST_TYPE . '&page=' . self::PREVIEW ) ); ?>">Forhåndsvis</a>
					<button type="button" class="button bvp-add-section">+ Legg til seksjon</button>
				</p>

				<div class="notice notice-error bvp-ed-klientfeil" hidden><p></p></div>

				<div id="bvp-sections">
					<?php
					$index = 0;
					foreach ( $sections as $section ) {
						self::render_section( 's' . $index, $index, $section, $all );
						$index++;
					}
					?>
				</div>

				<p class="bvp-ed-toolbar">
					<button type="button" class="button bvp-add-section">+ Legg til seksjon</button>
					<button type="submit" class="button button-primary">Lagre skjema</button>
				</p>
			</form>

			<?php self::render_templates( $all ); ?>

			<hr>

			<h2>Sikkerhetskopi og gjenoppretting</h2>

			<div class="bvp-ed-tools">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( self::NONCE, 'bvp_nonce' ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::EXPORT ); ?>">
					<button type="submit" class="button">Last ned oppsett (JSON)</button>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( self::NONCE, 'bvp_nonce' ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::IMPORT ); ?>">
					<input type="file" name="bvp_schema_file" accept=".json,application/json" required>
					<button type="submit" class="button">Importer oppsett</button>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Sette skjemaet tilbake til standardoppsettet? Egne felt du har lagt til forsvinner fra skjemaet.');">
					<?php wp_nonce_field( self::NONCE, 'bvp_nonce' ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::RESET ); ?>">
					<button type="submit" class="button button-link-delete">Tilbakestill til standardoppsett</button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Én seksjon.
	 *
	 * @param string $sidx    Indeks i skjemaet (s0, s1, sny3 ...).
	 * @param int    $sort    Sorteringsverdi.
	 * @param array  $section
	 * @param array  $all     Alle felt (for betingelser).
	 */
	protected static function render_section( $sidx, $sort, $section, $all ) {
		$base = 'sections[' . $sidx . ']';

		$synlige_felt = count(
			array_filter(
				$section['fields'],
				static function ( $field ) {
					return empty( $field['hidden'] );
				}
			)
		);
		?>
		<div class="bvp-ed-section" data-idx="<?php echo esc_attr( $sidx ); ?>" data-id="<?php echo (int) ( $section['id'] ?? 0 ); ?>">
			<input type="hidden" class="bvp-sort" name="<?php echo esc_attr( $base ); ?>[sort]" value="<?php echo (int) $sort; ?>">
			<input type="hidden" name="<?php echo esc_attr( $base ); ?>[key]" value="<?php echo esc_attr( $section['key'] ); ?>">
			<input type="hidden" class="bvp-ed-id-input" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo (int) ( $section['id'] ?? 0 ); ?>">

			<div class="bvp-ed-section__head">
				<span class="bvp-ed-id" title="Unik ID for seksjonen">S<?php echo esc_html( ! empty( $section['id'] ) ? (int) $section['id'] : 'ny' ); ?></span>

				<span class="bvp-ed-handle">
					<button type="button" class="button-link bvp-move-up" title="Flytt opp" aria-label="Flytt seksjonen opp">▲</button>
					<button type="button" class="button-link bvp-move-down" title="Flytt ned" aria-label="Flytt seksjonen ned">▼</button>
				</span>

				<button type="button" class="button-link bvp-ed-kollaps" aria-expanded="true" title="Vis/skjul feltene">
					<span class="bvp-ed-kollaps__pil" aria-hidden="true">▾</span>
					<span class="screen-reader-text">Vis eller skjul feltene i seksjonen</span>
				</button>

				<label class="bvp-ed-grow">
					<span class="screen-reader-text">Seksjonstittel</span>
					<?php if ( ! empty( $section['hidden'] ) ) : ?>
						<span class="bvp-ed-badge bvp-ed-badge--hidden">skjult seksjon</span>
					<?php endif; ?>
					<input type="text" class="bvp-ed-title" name="<?php echo esc_attr( $base ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" placeholder="Seksjonstittel" data-bvp-krav="1">
				</label>

				<label>
					Vises for
					<select name="<?php echo esc_attr( $base ); ?>[audience]">
						<option value="user" <?php selected( $section['audience'], 'user' ); ?>>Bruker (frontend)</option>
						<option value="admin" <?php selected( $section['audience'], 'admin' ); ?>>Kun administrator</option>
					</select>
				</label>

				<span class="bvp-ed-section-flags">
					<label class="bvp-ed-hide">
						<input type="checkbox" class="bvp-ed-hidden-section" name="<?php echo esc_attr( $base ); ?>[hidden]" value="1" <?php checked( ! empty( $section['hidden'] ) ); ?>>
						Skjul seksjonen
					</label>

					<label class="bvp-ed-delete">
						<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[delete]" value="1"> Slett seksjonen
					</label>
				</span>
			</div>

			<?php if ( ! empty( $section['hidden'] ) ) : ?>
				<p class="bvp-ed-hidden-note">Hele seksjonen er skjult: den vises verken i skjemaet, i wp-admin, i e-posten eller i CSV-eksporten. Lagrede svar beholdes.</p>
			<?php endif; ?>

			<label class="bvp-ed-intro-row">
				Hjelpetekst for seksjonen
				<input type="text" name="<?php echo esc_attr( $base ); ?>[intro]" value="<?php echo esc_attr( isset( $section['intro'] ) ? $section['intro'] : '' ); ?>">
			</label>

			<p class="bvp-ed-tomvarsel notice notice-warning" <?php echo $synlige_felt ? 'hidden' : ''; ?>>
				Ingen synlige felt - denne seksjonen vises ikke i skjemaet. Legg til minst ett felt under.
			</p>

			<div class="bvp-ed-fields">
				<?php
				$findex = 0;
				foreach ( $section['fields'] as $field ) {
					self::render_field( $sidx, 'f' . $sidx . '_' . $findex, $findex, $field, $all );
					$findex++;
				}
				?>
			</div>

			<p>
				<button type="button" class="button bvp-add-field">+ Legg til felt i denne seksjonen</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Ett felt.
	 *
	 * @param string $sidx
	 * @param string $fidx
	 * @param int    $sort
	 * @param array  $field
	 * @param array  $all
	 */
	protected static function render_field( $sidx, $fidx, $sort, $field, $all ) {
		$base      = 'sections[' . $sidx . '][fields][' . $fidx . ']';
		$type      = $field['type'];
		$options   = isset( $field['options'] ) ? implode( "\n", $field['options'] ) : '';
		$dep_field = isset( $field['depends']['field'] ) ? $field['depends']['field'] : '';
		$dep_value = isset( $field['depends']['value'] ) ? $field['depends']['value'] : '';
		$types     = BVP_Schema::types();
		?>
		<details class="bvp-ed-field" data-type="<?php echo esc_attr( $type ); ?>">
			<summary>
				<span class="bvp-ed-id" title="Unik ID for feltet">#<?php echo esc_html( ! empty( $field['id'] ) ? (int) $field['id'] : 'ny' ); ?></span>
				<span class="bvp-ed-field__label"><?php echo esc_html( $field['label'] ); ?></span>
				<span class="bvp-ed-field__type"><?php echo esc_html( isset( $types[ $type ] ) ? $types[ $type ] : $type ); ?></span>
				<?php if ( ! empty( $field['required'] ) ) : ?>
					<span class="bvp-ed-badge">påkrevd</span>
				<?php endif; ?>
				<?php if ( ! empty( $field['hidden'] ) ) : ?>
					<span class="bvp-ed-badge bvp-ed-badge--hidden">skjult</span>
				<?php endif; ?>
				<code class="bvp-ed-key"><?php echo esc_html( $field['key'] ); ?></code>
			</summary>

			<input type="hidden" class="bvp-sort" name="<?php echo esc_attr( $base ); ?>[sort]" value="<?php echo (int) $sort; ?>">
			<input type="hidden" name="<?php echo esc_attr( $base ); ?>[key]" value="<?php echo esc_attr( $field['key'] ); ?>">
			<input type="hidden" class="bvp-ed-id-input" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo (int) ( $field['id'] ?? 0 ); ?>">

			<div class="bvp-ed-grid">
				<label class="bvp-ed-span2">
					Ledetekst
					<input type="text" class="bvp-ed-label-input" name="<?php echo esc_attr( $base ); ?>[label]" value="<?php echo esc_attr( $field['label'] ); ?>" data-bvp-krav="1">
				</label>

				<label>
					Felttype
					<select class="bvp-ed-type" name="<?php echo esc_attr( $base ); ?>[type]">
						<?php foreach ( $types as $value => $etikett ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>><?php echo esc_html( $etikett ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<label class="bvp-ed-check">
					<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?>>
					Påkrevd ved innsending
				</label>

				<label class="bvp-ed-check bvp-ed-tittel">
					<input type="checkbox" class="bvp-ed-tittel-input" name="<?php echo esc_attr( $base ); ?>[tittel]" value="1" <?php checked( ! empty( $field['tittel'] ) ); ?>>
					Bruk som tittel i oversiktene
				</label>


				<label class="bvp-ed-span2">
					Hjelpetekst under ledeteksten
					<textarea name="<?php echo esc_attr( $base ); ?>[desc]" rows="2"><?php echo esc_textarea( isset( $field['desc'] ) ? $field['desc'] : '' ); ?></textarea>
					<span class="description">Enkel HTML er lov, så lenker blir klikkbare: &lt;a href="https://bimverdi.no/min-side/"&gt;min side&lt;/a&gt;. Også &lt;strong&gt;, &lt;em&gt; og &lt;br&gt;. Lenker åpnes i ny fane.</span>
				</label>

				<label class="bvp-ed-maxlength">
					Maks antall tegn (0 = ingen grense)
					<input type="number" min="0" step="1" name="<?php echo esc_attr( $base ); ?>[maxlength]" value="<?php echo (int) ( isset( $field['maxlength'] ) ? $field['maxlength'] : 0 ); ?>">
				</label>

				<label class="bvp-ed-span2 bvp-ed-lookup">
					Hva skal det søkes i?
					<select class="bvp-ed-lookup-kilde" name="<?php echo esc_attr( $base ); ?>[kilde]">
						<?php foreach ( BVP_Lookup::kilder() as $verdi => $etikett ) : ?>
							<option value="<?php echo esc_attr( $verdi ); ?>" <?php selected( BVP_Lookup::kilde( $field ), $verdi ); ?>><?php echo esc_html( $etikett ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="description">Innholdstypen oppslaget henter fra, f.eks. Kunnskapskilder (/kunnskapskilder/) eller Verktøy og digitale tjenester (/verktoy/).</span>
				</label>

				<label class="bvp-ed-span2 bvp-ed-lookup bvp-ed-lookup-kildetype">
					Kildetyper det skal søkes i
					<select name="<?php echo esc_attr( $base ); ?>[kildetyper][]" multiple size="6">
						<?php foreach ( BVP_Lookup::kildetyper() as $verdi => $etikett ) : ?>
							<option value="<?php echo esc_attr( $verdi ); ?>" <?php selected( in_array( (string) $verdi, array_map( 'strval', (array) ( $field['kildetyper'] ?? array() ) ), true ) ); ?>><?php echo esc_html( $etikett ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="description">Gjelder kunnskapskilder. Ingen valgt = alle kildetyper.</span>
				</label>

				<label class="bvp-ed-span2 bvp-ed-lookup">
					Begrens til kategorier
					<select name="<?php echo esc_attr( $base ); ?>[temagrupper][]" multiple size="6">
						<?php foreach ( self::lookup_kategorier() as $kilde => $grupper ) : ?>
							<?php foreach ( $grupper as $gruppe => $termer ) : ?>
								<optgroup label="<?php echo esc_attr( $gruppe ); ?>" data-kilde="<?php echo esc_attr( $kilde ); ?>">
									<?php foreach ( $termer as $term_id => $navn ) : ?>
										<option value="<?php echo (int) $term_id; ?>" data-kilde="<?php echo esc_attr( $kilde ); ?>" <?php selected( in_array( (int) $term_id, array_map( 'intval', (array) ( $field['temagrupper'] ?? array() ) ), true ) ); ?>><?php echo esc_html( $navn ); ?></option>
									<?php endforeach; ?>
								</optgroup>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</select>
					<span class="description">Valgfritt. Viser kategoriene som hører til kilden du har valgt.</span>
				</label>

				<label class="bvp-ed-lookup">
					Begrens på felt (valgfritt)
					<select class="bvp-ed-lookup-meta" name="<?php echo esc_attr( $base ); ?>[meta_nokkel]">
						<option value="">- ingen -</option>
						<?php foreach ( self::lookup_metanokler() as $kilde => $nokler ) : ?>
							<?php foreach ( $nokler as $nokkel ) : ?>
								<option value="<?php echo esc_attr( $nokkel ); ?>" data-kilde="<?php echo esc_attr( $kilde ); ?>" <?php selected( (string) ( $field['meta_nokkel'] ?? '' ), (string) $nokkel ); ?>><?php echo esc_html( $nokkel ); ?></option>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</select>
					<span class="description">F.eks. feltet «type» på verktøy.</span>
				</label>

				<label class="bvp-ed-lookup">
					... som inneholder
					<input type="text" name="<?php echo esc_attr( $base ); ?>[meta_verdi]" value="<?php echo esc_attr( (string) ( $field['meta_verdi'] ?? '' ) ); ?>" placeholder="f.eks. Programvare">
				</label>

				<label class="bvp-ed-lookup">
					Maks antall treff som kan velges (0 = ingen grense)
					<input type="number" min="0" step="1" name="<?php echo esc_attr( $base ); ?>[maks]" value="<?php echo (int) ( $field['maks'] ?? 0 ); ?>">
				</label>

				<label class="bvp-ed-span2 bvp-ed-lookup">
					Merking av valgte treff - én gruppe per linje
					<textarea name="<?php echo esc_attr( $base ); ?>[merker]" rows="3" placeholder="Status: Pålagt, Anbefalt&#10;Type: Klassifiseringsstandard, Kravstandard, Annen type"><?php echo esc_textarea( BVP_Lookup::merker_tekst( $field ) ); ?></textarea>
					<span class="description">Valgfritt. Skriv «Gruppenavn: valg, valg». Hver gruppe blir en nedtrekksliste ved hvert valgt treff. Tomt = ingen merking.</span>
				</label>

				<label class="bvp-ed-span3 bvp-ed-options">
					Svaralternativer - ett per linje
					<textarea name="<?php echo esc_attr( $base ); ?>[options]" rows="5"><?php echo esc_textarea( $options ); ?></textarea>
				</label>

				<label>
					Vis feltet bare hvis
					<select name="<?php echo esc_attr( $base ); ?>[dep_field]" class="bvp-ed-dep-felt">
						<option value="">- alltid vis -</option>
						<?php foreach ( $all as $key => $other ) : ?>
							<?php if ( $key === $field['key'] || ! in_array( $other['type'], BVP_Fields::depends_types(), true ) ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<?php $typenavn = BVP_Schema::types()[ $other['type'] ] ?? $other['type']; ?>
							<option value="<?php echo esc_attr( $key ); ?>" data-bvp-valg="<?php echo esc_attr( wp_json_encode( array_values( (array) ( $other['options'] ?? array() ) ) ) ); ?>" <?php selected( $dep_field, $key ); ?>>
								<?php echo esc_html( $other['label'] . ' (' . $typenavn . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="description">Radioknapper, nedtrekkslister og avkryssinger kan styre. Er det en avkryssing, vises feltet når verdien er krysset av.</span>
				</label>

				<label>
					... har verdien
					<input type="text" name="<?php echo esc_attr( $base ); ?>[dep_value]" value="<?php echo esc_attr( $dep_value ); ?>" placeholder="f.eks. Ja" class="bvp-ed-dep-verdi" list="bvp-dep-<?php echo esc_attr( $sidx . '-' . $fidx ); ?>">
					<datalist id="bvp-dep-<?php echo esc_attr( $sidx . '-' . $fidx ); ?>"></datalist>
					<span class="description">Skriv svaralternativet nøyaktig slik det står i det styrende feltet.</span>
				</label>

				<div class="bvp-ed-span3 bvp-ed-flags">
					<label class="bvp-ed-hide">
						<input type="checkbox" class="bvp-ed-hidden-input" name="<?php echo esc_attr( $base ); ?>[hidden]" value="1" <?php checked( ! empty( $field['hidden'] ) ); ?>>
						<strong>Skjul feltet</strong>
						<span class="description">Spørsmålet tas ut av skjemaet, e-posten og CSV-eksporten, men blir stående her og kan slås på igjen. Svar som allerede er lagret, beholdes.</span>
					</label>

					<label class="bvp-ed-delete">
						<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[delete]" value="1">
						<strong>Slett feltet</strong>
						<span class="description">Spørsmålet fjernes helt fra skjemaoppsettet ved lagring. Kan ikke angres uten å importere et tidligere oppsett.</span>
					</label>
				</div>
			</div>

			<p class="bvp-ed-field__actions">
				<button type="button" class="button bvp-move-up">▲ Flytt opp</button>
				<button type="button" class="button bvp-move-down">▼ Flytt ned</button>
				<button type="button" class="button bvp-kopier-felt">⧉ Kopier feltet</button>

				<label class="bvp-ed-flytt">
					Flytt til seksjon
					<select class="bvp-flytt-seksjon">
						<option value="">- velg seksjon -</option>
					</select>
				</label>

				<span class="description">ID <code>#<?php echo esc_html( ! empty( $field['id'] ) ? (int) $field['id'] : 'ny' ); ?></code> er en fast referanse til feltet og endres aldri. Feltnøkkelen (<code><?php echo esc_html( $field['key'] ); ?></code>) er lagringsnøkkelen og kan ikke endres. En kopi får sin egen ID og nøkkel når du lagrer.</span>
			</p>
		</details>
		<?php
	}

	/**
	 * Kategoriene for alle kilder, hentet én gang per sidevisning.
	 *
	 * @return array kilde => array( gruppenavn => array( term_id => navn ) )
	 */
	protected static function lookup_kategorier() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$cache = array();

		foreach ( array_keys( BVP_Lookup::kilder() ) as $kilde ) {
			$grupper = array();

			foreach ( BVP_Lookup::kategorier( $kilde ) as $gruppe => $termer ) {
				$grupper[ $gruppe ] = $termer;
			}

			if ( $grupper ) {
				$cache[ $kilde ] = $grupper;
			}
		}

		return $cache;
	}

	/**
	 * Meta-nøklene for alle kilder, hentet én gang per sidevisning.
	 *
	 * @return array kilde => nøkler
	 */
	protected static function lookup_metanokler() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$cache = array();

		foreach ( array_keys( BVP_Lookup::kilder() ) as $kilde ) {
			$nokler = BVP_Lookup::meta_nokler( $kilde );

			if ( $nokler ) {
				$cache[ $kilde ] = $nokler;
			}
		}

		return $cache;
	}

	/**
	 * Maler JS bruker når du legger til nytt felt eller ny seksjon.
	 *
	 * @param array $all
	 */
	protected static function render_templates( $all ) {
		$blank_field = array(
			'id'       => 0,
			'key'      => '',
			'label'    => '',
			'type'     => 'text',
			'required' => false,
			'hidden'   => false,
			'desc'     => '',
			'options'  => array(),
		);

		$blank_section = array(
			'id'       => 0,
			'key'      => '',
			'title'    => '',
			'intro'    => '',
			'audience' => 'user',
			'hidden'   => false,
			'fields'   => array(),
		);

		echo '<template id="bvp-tpl-field">';
		self::render_field( '__SIDX__', '__FIDX__', 0, $blank_field, $all );
		echo '</template>';

		echo '<template id="bvp-tpl-section">';
		self::render_section( '__SIDX__', 0, $blank_section, $all );
		echo '</template>';
	}

	/**
	 * Forhåndsvisning av skjemaet.
	 */
	public static function render_preview() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Du har ikke tilgang til denne siden.' );
		}
		?>
		<div class="wrap">
			<h1>Forhåndsvisning av skjemaet</h1>
			<p>
				Slik ser feltene ut. Ingenting lagres herfra.
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . BVP_Post_Type::POST_TYPE . '&page=' . self::PAGE ) ); ?>">Tilbake til skjemaeditoren</a>
			</p>

			<div class="bvp bvp-preview">
				<?php foreach ( BVP_Fields::sections() as $section ) : ?>
					<p class="bvp-preview__tag">
						<?php
						if ( ! empty( $section['hidden'] ) ) {
							echo 'Skjult seksjon - vises ingen steder';
						} else {
							echo 'admin' === $section['audience'] ? 'Kun synlig i wp-admin' : 'Synlig for deltakeren';
						}
						?>
					</p>
					<fieldset disabled>
						<?php BVP_Renderer::section( $section, 0, array(), array() ); ?>
					</fieldset>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Meldinger.
	 */
	protected static function notices() {
		$msg = isset( $_GET['bvp_msg'] ) ? sanitize_key( wp_unslash( $_GET['bvp_msg'] ) ) : '';
		$txt = isset( $_GET['bvp_txt'] ) ? sanitize_text_field( wp_unslash( $_GET['bvp_txt'] ) ) : '';

		$advarsler = get_transient( 'bvp_skjema_advarsler_' . get_current_user_id() );

		if ( is_array( $advarsler ) && $advarsler ) {
			delete_transient( 'bvp_skjema_advarsler_' . get_current_user_id() );

			echo '<div class="notice notice-warning"><p><strong>Merk:</strong></p><ul style="margin-left:1.5em;list-style:disc">';

			foreach ( $advarsler as $advarsel ) {
				echo '<li>' . esc_html( $advarsel ) . '</li>';
			}

			echo '</ul></div>';
		}

		$map = array(
			'lagret'       => array( 'success', trim( 'Skjemaet er lagret. ' . $txt ) ),
			'tilbakestilt' => array( 'success', 'Skjemaet er satt tilbake til standardoppsettet.' ),
			'importert'    => array( 'success', 'Oppsettet er importert.' ),
			'feil'         => array( 'error', $txt ? $txt : 'Skjemaet ble ikke lagret.' ),
		);

		if ( 'konflikt' === $msg ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong>Skjemaet ble ikke lagret.</strong>
					Det er endret et annet sted – i en annen fane, eller av en annen administrator – etter at du åpnet denne siden.
					Endringene dine er tatt vare på, men ikke lagret.
				</p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . BVP_Post_Type::POST_TYPE . '&page=' . self::PAGE ) ); ?>">Last siden på nytt (forkast mine endringer)</a>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=bvp_force_schema' ), self::NONCE ) ); ?>">Lagre mine endringer likevel</a>
				</p>
			</div>
			<?php
			return;
		}

		if ( isset( $map[ $msg ] ) ) {
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $map[ $msg ][0] ),
				esc_html( $map[ $msg ][1] )
			);
		}
	}

	/* ------------------------------------------------------------------ */
	/* Handlinger                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * Hold titlene i takt med tittelfeltet.
	 *
	 * Er tittelfeltet byttet, får alle registreringer nytt navn fra det nye
	 * feltet. Ellers fylles bare de som står uten tittel.
	 *
	 * @param string $tittel_for Tittelfeltet før lagring.
	 * @return int Antall registreringer som fikk nytt navn.
	 */
	protected static function oppdater_titler( $tittel_for ) {
		$byttet = BVP_Fields::title_key() !== (string) $tittel_for;

		return (int) BVP_Repository::refresh_titles( ! $byttet );
	}

	protected static function titler_tekst( $antall ) {
		if ( $antall < 1 ) {
			return '';
		}

		return 1 === $antall
			? '1 registrering fikk nytt navn fra tittelfeltet.'
			: sprintf( '%d registreringer fikk nytt navn fra tittelfeltet.', $antall );
	}

	/**
	 * Vis hvilket felt som gir registreringene navn.
	 */
	protected static function render_tittelinfo() {
		$key  = BVP_Fields::title_key();
		$alle = BVP_Fields::all( 'all', true );

		if ( ! $key || ! isset( $alle[ $key ] ) ) {
			echo '<div class="notice notice-warning inline"><p><strong>Ingen felt gir registreringene navn.</strong> Kryss av «Bruk som tittel i oversiktene» på feltet med kort navn.</p></div>';
			return;
		}

		$felt = $alle[ $key ];
		$id   = ! empty( $felt['id'] ) ? '#' . (int) $felt['id'] . ' ' : '';

		if ( 'textarea' === ( $felt['type'] ?? '' ) ) {
			printf(
				'<div class="notice notice-warning inline"><p><strong>Navnet i oversiktene hentes fra et langt tekstfelt: %s.</strong> Er det riktig? Kryss av «Bruk som tittel i oversiktene» på feltet med kort navn i stedet.</p></div>',
				esc_html( $id . $felt['label'] )
			);
			return;
		}

		printf(
			'<p class="bvp-ed-tittelinfo"><strong>Navnet i oversiktene hentes fra:</strong> %s</p>',
			esc_html( $id . $felt['label'] )
		);
	}

	protected static function guard() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Du har ikke tilgang til skjemaeditoren.', 'Ingen tilgang', array( 'response' => 403 ) );
		}

		if ( ! isset( $_POST['bvp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bvp_nonce'] ) ), self::NONCE ) ) {
			wp_die( 'Sikkerhetssjekken feilet. Gå tilbake og prøv igjen.', 'Ugyldig forespørsel', array( 'response' => 403 ) );
		}
	}

	protected static function back( $msg, $txt = '' ) {
		$url = admin_url( 'edit.php?post_type=' . BVP_Post_Type::POST_TYPE . '&page=' . self::PAGE );
		$url = add_query_arg( 'bvp_msg', $msg, $url );

		if ( $txt ) {
			$url = add_query_arg( 'bvp_txt', rawurlencode( $txt ), $url );
		}

		wp_safe_redirect( $url );
		exit;
	}

	public static function handle_save() {
		self::guard();

		$raw = isset( $_POST['sections'] ) && is_array( $_POST['sections'] ) ? wp_unslash( $_POST['sections'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$sections = self::collect( $raw );

		// Er skjemaet lagret et annet sted etter at denne siden ble lastet?
		$basis = isset( $_POST['bvp_basis'] ) ? sanitize_text_field( wp_unslash( $_POST['bvp_basis'] ) ) : '';
		$naa   = (string) get_option( 'bvp_schema_saved', '0' );

		if ( '' !== $basis && $basis !== $naa ) {
			set_transient( self::stash_key(), $sections, HOUR_IN_SECONDS );
			self::back( 'konflikt' );
		}

		$tittel_for = BVP_Fields::title_key();
		$result     = BVP_Schema::save( $sections );

		if ( is_wp_error( $result ) ) {
			self::back( 'feil', $result->get_error_message() );
		}

		$oppdatert = self::oppdater_titler( $tittel_for );

		$advarsler = BVP_Schema::advarsler();

		if ( $advarsler ) {
			set_transient( 'bvp_skjema_advarsler_' . get_current_user_id(), $advarsler, HOUR_IN_SECONDS );
		}

		self::back( 'lagret', self::titler_tekst( $oppdatert ) );
	}

	/**
	 * Bygg seksjonsstrukturen fra skjemaposten.
	 *
	 * @param array $raw
	 * @return array
	 */
	protected static function collect( $raw ) {
		// Sorter seksjoner.
		uasort(
			$raw,
			static function ( $a, $b ) {
				return (int) ( $a['sort'] ?? 0 ) <=> (int) ( $b['sort'] ?? 0 );
			}
		);

		$sections = array();
		$order    = array();

		foreach ( $raw as $sidx => $section ) {
			if ( ! empty( $section['delete'] ) ) {
				continue;
			}

			$fields = isset( $section['fields'] ) && is_array( $section['fields'] ) ? $section['fields'] : array();

			uasort(
				$fields,
				static function ( $a, $b ) {
					return (int) ( $a['sort'] ?? 0 ) <=> (int) ( $b['sort'] ?? 0 );
				}
			);

			$kept = array();

			foreach ( $fields as $field ) {
				if ( ! empty( $field['delete'] ) ) {
					continue;
				}

				$kept[] = array(
					'id'        => $field['id'] ?? 0,
					'key'       => $field['key'] ?? '',
					'label'     => $field['label'] ?? '',
					'type'      => $field['type'] ?? 'text',
					'required'  => ! empty( $field['required'] ),
					'tittel'    => ! empty( $field['tittel'] ),
					'hidden'    => ! empty( $field['hidden'] ),
					'desc'      => $field['desc'] ?? '',
					'maxlength' => $field['maxlength'] ?? 0,
					'options'   => $field['options'] ?? '',
					'kildetyper' => $field['kildetyper'] ?? array(),
					'temagrupper' => $field['temagrupper'] ?? array(),
					'maks'      => $field['maks'] ?? 0,
					'merker'      => $field['merker'] ?? '',
					'kilde'       => $field['kilde'] ?? '',
					'meta_nokkel' => $field['meta_nokkel'] ?? '',
					'meta_verdi'  => $field['meta_verdi'] ?? '',
					'depends'   => array(
						'field' => $field['dep_field'] ?? '',
						'value' => $field['dep_value'] ?? '',
					),
				);
			}

			$sections[] = array(
				'id'       => $section['id'] ?? 0,
				'key'      => $section['key'] ?? '',
				'title'    => $section['title'] ?? '',
				'intro'    => $section['intro'] ?? '',
				'audience' => $section['audience'] ?? 'user',
				'hidden'   => ! empty( $section['hidden'] ),
				'fields'   => $kept,
			);

			$order[] = $sidx;
		}

		return $sections;
	}

	/** Nøkkel for mellomlagring ved konflikt. */
	protected static function stash_key() {
		return 'bvp_editor_konflikt_' . get_current_user_id();
	}

	/**
	 * Lagre den versjonen som ble avvist på grunn av konflikt.
	 */
	public static function handle_force() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Du har ikke tilgang til skjemaeditoren.', 'Ingen tilgang', array( 'response' => 403 ) );
		}

		check_admin_referer( self::NONCE );

		$sections = get_transient( self::stash_key() );

		if ( ! is_array( $sections ) || empty( $sections ) ) {
			self::back( 'feil', 'Fant ingen mellomlagret versjon. Last siden på nytt og gjør endringen om igjen.' );
		}

		delete_transient( self::stash_key() );

		$tittel_for = BVP_Fields::title_key();
		$result     = BVP_Schema::save( $sections );

		if ( is_wp_error( $result ) ) {
			self::back( 'feil', $result->get_error_message() );
		}

		self::back( 'lagret', self::titler_tekst( self::oppdater_titler( $tittel_for ) ) );
	}

	public static function handle_reset() {
		self::guard();
		BVP_Schema::reset();
		self::back( 'tilbakestilt' );
	}

	public static function handle_export() {
		self::guard();

		$json     = BVP_Schema::to_json();
		$filename = 'bv-pilot-prosjekt-skjema-' . gmdate( 'Y-m-d' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	public static function handle_import() {
		self::guard();

		if ( empty( $_FILES['bvp_schema_file']['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $_FILES['bvp_schema_file']['error'] ) {
			self::back( 'feil', 'Ingen fil ble lastet opp.' );
		}

		$size = (int) $_FILES['bvp_schema_file']['size'];

		if ( $size <= 0 || $size > 1048576 ) {
			self::back( 'feil', 'Filen er tom eller større enn 1 MB.' );
		}

		$json   = file_get_contents( $_FILES['bvp_schema_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$result = BVP_Schema::from_json( $json );

		if ( is_wp_error( $result ) ) {
			self::back( 'feil', $result->get_error_message() );
		}

		self::back( 'importert' );
	}
}
