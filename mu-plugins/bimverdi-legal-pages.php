<?php
/**
 * BIM Verdi - Legal Pages Setup
 *
 * Auto-creates Vilkår and Personvernerklæring pages if they don't exist.
 * These are referenced in the registration flow.
 *
 * NOTE: Pages are created locally with placeholder content.
 * On production, create them manually with real legal text.
 *
 * @package BIMVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    // Only run once per day (avoid running on every page load)
    if (get_transient('bimverdi_legal_pages_checked')) {
        return;
    }
    set_transient('bimverdi_legal_pages_checked', true, DAY_IN_SECONDS);

    $pages = array(
        'vilkar' => array(
            'title' => 'Vilkår for bruk',
            'slug'  => 'vilkar',
            'content' => '<h2>Vilkår for bruk av BIM Verdi</h2>
<p><em>Denne siden inneholder vilkår for bruk av BIM Verdis tjenester. Innholdet må oppdateres med faktiske vilkår før lansering.</em></p>

<h3>1. Generelt</h3>
<p>Ved å opprette en konto på BIM Verdi aksepterer du disse vilkårene.</p>

<h3>2. Bruk av tjenesten</h3>
<p>BIM Verdi er en nettverksportal for byggenæringen. Du er ansvarlig for å holde kontoinformasjonen din oppdatert.</p>

<h3>3. Innhold</h3>
<p>Innhold du publiserer via portalen skal være relevant for bransjen og følge god forretningsskikk.</p>

<h3>4. Personvern</h3>
<p>Se vår <a href="/personvern/">personvernerklæring</a> for informasjon om behandling av personopplysninger.</p>

<h3>5. Endringer</h3>
<p>BIM Verdi kan oppdatere disse vilkårene. Vesentlige endringer varsles via e-post.</p>',
        ),
        'personvern' => array(
            'title' => 'Personvernerklæring',
            'slug'  => 'personvern',
            'content' => '<h2>Personvernerklæring for BIM Verdi</h2>
<p><em>Denne siden inneholder personvernerklæringen for BIM Verdi. Innholdet må oppdateres med faktisk personvernerklæring før lansering.</em></p>

<h3>1. Behandlingsansvarlig</h3>
<p>BIM Verdi er behandlingsansvarlig for personopplysninger som samles inn via denne portalen.</p>

<h3>2. Hvilke opplysninger vi samler inn</h3>
<ul>
<li>Navn og e-postadresse (ved registrering)</li>
<li>Foretakstilknytning (valgfritt)</li>
<li>Aktivitet på portalen</li>
</ul>

<h3>3. Formål</h3>
<p>Opplysningene brukes for å drifte medlemsportalen, sende relevant informasjon, og administrere medlemskap.</p>

<h3>4. Deling</h3>
<p>Vi deler ikke personopplysninger med tredjeparter uten ditt samtykke, med mindre det er pålagt ved lov.</p>

<h3>5. Dine rettigheter</h3>
<p>Du har rett til innsyn, retting, sletting og dataportabilitet. Kontakt oss for å utøve dine rettigheter.</p>

<h3>6. Kontakt</h3>
<p>For spørsmål om personvern, kontakt oss via <a href="/kontakt/">kontaktsiden</a>.</p>',
        ),
    );

    foreach ($pages as $key => $page_data) {
        $existing = get_page_by_path($page_data['slug']);
        if (!$existing) {
            wp_insert_post(array(
                'post_title'   => $page_data['title'],
                'post_name'    => $page_data['slug'],
                'post_content' => $page_data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            ));
        }
    }
});
