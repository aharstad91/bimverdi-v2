<?php
/**
 * Plugin Name: BIM Verdi - Statusside
 * Description: Felles fremdriftsoversikt for Andreas og Bård på /status/<token>/ — user journeys med kodeverifiserte akseptkriterier, ærlig progress bar og kopier-/goal-knapper.
 * Version: 1.0.0
 *
 * Bakgrunn: Andreas i synken 25.08 — «vi må ha en sånn felles logg på hva som
 * er gjort […] og så bør det også være en sånn to do ut ifra det […] jeg bygger
 * en HTML-versjon som blir en slags nettside med all den teksten». Konseptet er
 * hentet fra statusside-skillet (utviklet i minsponsor-v2, juni 2026) og portet
 * til WordPress: datamodellen og layouten er identisk på tvers av prosjekter,
 * kun teknologien er byttet.
 *
 * Spørsmålet siden svarer på er «hvor langt er vi egentlig?». Den svarer ærlig:
 * ingen kriterier står som ferdige uten fil:linje som bevis, tvil gir gult, og
 * en journey blir ikke grønn før dere har godkjent den i et møte. Baren skal
 * kunne gå ned når dere oppdager nye hull.
 *
 * TILGANG: hemmelig lenke, ingen innlogging — Bård skal kunne åpne den fra
 * mobilen uten å logge inn noe sted. Token settes i wp-config.php:
 *     define('BIMVERDI_STATUS_TOKEN', '<64 hex fra openssl rand -hex 32>');
 * Konstant og ikke option, av samme grunn som varselgatene: databasen kopieres
 * mellom prod og localhost, wp-config gjør ikke det. Fail-closed — udefinert
 * eller tom konstant gir vanlig 404, og siden er noindex + no-store.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/statusside/lib.php';
require_once __DIR__ . '/statusside/data.php';

/** Overskrift og avhengighetsnotat — de eneste prosjektspesifikke tekstene i rammen. */
const BV_STATUS_TITTEL       = 'BIM Verdi 2.0 — status';
const BV_STATUS_AVHENGIGHET  = 'Avhengigheter: 1 → 2 → 3, 4 og 5 · 6 og 7 står på egne bein';
const BV_STATUS_DATAFIL      = 'wp-content/mu-plugins/statusside/data.php';

/**
 * Konstant-tids sammenligning. Begge sider hashes først, så lengdeforskjell
 * ikke lekker via timing eller kaster i hash_equals (som krever like lengder).
 * Fail-closed: udefinert eller tom konstant → alltid false.
 */
function bv_status_token_stemmer($oppgitt) {
    $fasit = defined('BIMVERDI_STATUS_TOKEN') ? (string) BIMVERDI_STATUS_TOKEN : '';
    if ('' === $fasit || '' === (string) $oppgitt) {
        return false;
    }
    return hash_equals(hash('sha256', $fasit), hash('sha256', (string) $oppgitt));
}

/**
 * Ruting på template_redirect, ikke init: treffer ikke tokenet, gjør vi
 * ingenting og WP viser sin vanlige 404. Fail-closed av konstruksjon — det
 * finnes ingen gren her som kan lekke siden ved feil token.
 */
add_action('template_redirect', function () {
    $hjemsti = rtrim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
    $sti     = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    if ('' !== $hjemsti && 0 === strpos($sti, $hjemsti)) {
        $sti = substr($sti, strlen($hjemsti));
    }
    $sti = trim($sti, '/');

    if (0 !== strpos($sti, 'status/')) {
        return;
    }
    if (!bv_status_token_stemmer(substr($sti, strlen('status/')))) {
        return; // Vanlig 404 — siden røper ikke engang at ruta finnes.
    }

    bv_status_render(bv_status_journeys());
    exit;
});

/* -------------------------------------------------------------------------
 * /goal-promptene — broen til agent-arbeidsflyten
 * ---------------------------------------------------------------------- */

/**
 * Kvalitetsrutinen bakes inn i begge promptene og er risikostyrt: ce-code-review
 * spawner alltid 6+ persona-agenter, så rutinen styrer FREKVENS og SCOPE, ikke
 * panelbredden — ellers gir et journey-løp med mange kriterier ett fullt panel
 * per kriterium. Kommandoene er tilpasset dette prosjektet: ingen tsc/eslint,
 * men php -l og verifisering i nettleseren mot localhost.
 */
function bv_status_kvalitetsrutine() {
    return implode("\n", [
        'Kvalitetsrutine (risikostyrt — bredde skal matche risiko, jf. globalt CLAUDE.md):',
        '- Noter diffens base-sha ved løpets start (git -C wp-content rev-parse HEAD).',
        '- Høyrisiko-endring (tilgangskontroll, e-postutsending til ekte medlemmer, datamutasjon, migrasjon): kjør /ce-code-review mode:autofix på diffen FØR ship, og fiks P0/P1-funn.',
        '- Alt annet: IKKE review per kriterium. Kjør ÉN samlet /ce-code-review mode:autofix base:<start-sha> over hele løpets diff før løpet avsluttes.',
        '- Mellomstor lavrisiko-diff du er usikker på underveis: bruk innebygd /code-review (én agent), ikke ce-panelet.',
        '- Verifisering i dette prosjektet: php -l på hver endret fil, og flyten sjekket i nettleseren på localhost:8888 (bruk /run). MAMP må kjøre — start med /Applications/MAMP/bin/startMysql.sh og startApache.sh.',
        '- Sender kriteriet e-post: bygg låst til andreas@aharstad.no først, med hard allowlist rett før wp_mail. Aldri ekte medlemmer før eksplisitt go.',
        '- Trengs design/avveininger først (flere rimelige løsninger): start med /ce-plan.',
        '- Ikke-opplagte læringer: samle opp underveis og fang dem med ÉN /ce-compound på slutten av løpet.',
        '',
        'Modellstrategi: hold hovedløkka (orkestrering, verifisering mot kode, statusoppdatering i ' . BV_STATUS_DATAFIL . ') på Fable. Deleger avgrensede implementasjonsoppgaver til subagenter med Opus (Agent-verktøyet, model: "opus") — én subagent per oppgave, seriellt med mindre oppgavene er genuint uavhengige.',
    ]);
}

const BV_STATUS_LABEL = [
    'done'    => 'ferdig (kodeverifisert)',
    'partial' => 'delvis',
    'missing' => 'mangler',
];

/**
 * /goal for ett kriterium. Sluttbetingelsen er målbar, så en evaluator kan
 * drive løpet turn for turn til den faktisk holder — i stedet for én omgang.
 */
function bv_status_goal_kriterium(array $k, array $journey) {
    $betingelse = 'done' === $k['status']
        ? sprintf('Kriterium %s i %s er revalidert mot koden: verifisert-datoen er oppdatert hvis det fortsatt stemmer, ellers er status nedgradert med notat om hva som har driftet — og php -l er ren på hver endret fil.', $k['id'], BV_STATUS_DATAFIL)
        : sprintf('Kriterium %s i %s står som done med oppdatert bevis (fil:linje) og verifisert-dato — eller er eksplisitt markert blokkert på menneske i notatet — og php -l er ren på hver endret fil.', $k['id'], BV_STATUS_DATAFIL);

    $linjer = [
        '/goal ' . $betingelse,
        '',
        'Kontekst fra statussiden:',
        sprintf('Journey %d: %s', $journey['nr'], $journey['tittel']),
        sprintf('Kriterium %s: %s', $k['id'], $k['tekst']),
        'Status nå: ' . BV_STATUS_LABEL[$k['status']],
    ];
    if (!empty($k['notat'])) {
        $linjer[] = 'Notat: ' . $k['notat'];
    }
    if (!empty($k['bevis'])) {
        $linjer[] = 'Bevis/referanse: ' . $k['bevis'];
    }
    $linjer[] = '';
    $linjer[] = 'done' === $k['status']
        ? 'Revalider kriteriet mot koden. Stemmer det fortsatt, oppdater verifisert-datoen; hvis ikke, nedgrader status med notat om hva som har driftet.'
        : 'Finn ut hva som mangler og implementer det. Når det er kodeverifisert: oppdater status, bevis (fil:linje/commit) og verifisert-dato i ' . BV_STATUS_DATAFIL . ' i samme commit som fiksen. Aldri done uten bevis; tvil = partial.';
    $linjer[] = '';
    $linjer[] = bv_status_kvalitetsrutine();

    return implode("\n", $linjer);
}

/** Ett /goal for hele journeyen — Claude tar kriterium for kriterium. */
function bv_status_goal_journey(array $journey) {
    $linjer = [
        sprintf(
            '/goal Alle kriterier i journey %d («%s») i %s står som done med bevis (fil:linje) og oppdatert verifisert-dato — eller er eksplisitt markert blokkert på menneske i notatet — og hver endring er shippet med ren php -l.',
            $journey['nr'],
            $journey['tittel'],
            BV_STATUS_DATAFIL
        ),
        '',
        'Kontekst fra statussiden:',
        sprintf('Journey %d: %s (aktør: %s)', $journey['nr'], $journey['tittel'], $journey['aktor']),
        'Hvorfor: ' . $journey['hvorfor'],
        '',
        'Kriterier nå:',
    ];
    foreach ($journey['kriterier'] as $k) {
        $linjer[] = sprintf(
            '- %s [%s]: %s%s',
            $k['id'],
            ['done' => 'ferdig', 'partial' => 'delvis', 'missing' => 'mangler'][$k['status']],
            $k['tekst'],
            !empty($k['notat']) ? ' (' . $k['notat'] . ')' : ''
        );
    }
    $linjer[] = '';
    $linjer[] = 'Ta kriteriene i fornuftig rekkefølge (avhengigheter først). For hvert: implementer, verifiser mot koden, og oppdater status, bevis og verifisert-dato i ' . BV_STATUS_DATAFIL . ' i samme commit som fiksen. Aldri done uten bevis; tvil = partial. Det som krever menneskelig handling (Bårds beslutninger, salg, kontoer, godkjenninger): marker tydelig i notatet og gå videre.';
    $linjer[] = '';
    $linjer[] = bv_status_kvalitetsrutine();

    return implode("\n", $linjer);
}

/* -------------------------------------------------------------------------
 * Rendering — layouten er standardisert (statusside-skillets LAYOUT.md) og
 * skal se lik ut på tvers av prosjekter. Bevisst nøytral: ingen BIM Verdi-
 * brandfarger, fordi dette er et arbeidsverktøy, ikke en produktflate.
 * ---------------------------------------------------------------------- */

function bv_status_ikon($status) {
    if ('done' === $status) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="ikon gr"><path d="M20 6 9 17l-5-5"/></svg>';
    }
    if ('partial' === $status) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="ikon gu"><circle cx="12" cy="12" r="9" stroke-dasharray="3 3"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="ikon ro"><path d="M18 6 6 18M6 6l12 12"/></svg>';
}

function bv_status_kopiikon() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>';
}

function bv_status_render(array $journeys) {
    $total     = bv_status_total_progress($journeys);
    $fordeling = bv_status_farge_fordeling($journeys);
    $verifisert = bv_status_format_dato(bv_status_sist_verifisert($journeys));

    $farger = [
        'green'  => ['label' => 'Godkjent',     'klasse' => 'gr'],
        'yellow' => ['label' => 'Underveis',    'klasse' => 'gu'],
        'red'    => ['label' => 'Ikke startet', 'klasse' => 'ro'],
    ];

    nocache_headers();
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    status_header(200);
    ?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html(BV_STATUS_TITTEL); ?></title>
<style>
:root {
  --bg: #F4F4F5; --kort: #FFFFFF; --kant: #E4E4E7; --tekst: #18181B;
  --dempet: #71717A; --skinne: #E4E4E7;
  --gronn: #16A34A; --gul: #D97706; --rod: #DC2626;
}
* { box-sizing: border-box; }
body {
  margin: 0; background: var(--bg); color: var(--tekst);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  font-size: 15px; line-height: 1.5; -webkit-font-smoothing: antialiased;
}
main { max-width: 672px; margin: 0 auto; padding: 40px 16px; display: flex; flex-direction: column; gap: 24px; }

/* Header */
.topp { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; }
h1 { font-size: 22px; font-weight: 700; letter-spacing: -.01em; margin: 0 0 2px; }
.undertekst { font-size: 13px; color: var(--dempet); margin: 0; }
.prosent { font-size: 44px; font-weight: 700; font-variant-numeric: tabular-nums; letter-spacing: -.02em; line-height: 1; }
.bar { height: 10px; border-radius: 999px; background: var(--skinne); overflow: hidden; }
.bar > i { display: block; height: 100%; border-radius: 999px; background: var(--tekst); }
.piller { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 12px; font-weight: 500; }
.pille { border-radius: 999px; padding: 4px 10px; }
.pille.gr { background: #DCFCE7; color: #166534; }
.pille.gu { background: #FEF3C7; color: #92400E; }
.pille.ro { background: #FEE2E2; color: #991B1B; }
.avhengighet { margin-left: auto; color: var(--dempet); font-weight: 400; }

/* Journey-kort */
.kort-liste { display: flex; flex-direction: column; gap: 12px; }
.kort {
  display: block; width: 100%; text-align: left; cursor: pointer;
  background: var(--kort); border: 1px solid var(--kant); border-radius: 12px;
  padding: 16px 20px; font: inherit; color: inherit;
  box-shadow: 0 1px 2px rgba(0,0,0,.04); transition: box-shadow .15s;
}
.kort:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.kort:focus-visible { outline: 3px solid rgba(24,24,27,.25); outline-offset: 2px; }
.kort-rad1 { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
.jnr { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--dempet); }
.hoyre { display: flex; align-items: center; gap: 6px; }
.merke { font-size: 11px; font-weight: 600; border-radius: 999px; padding: 3px 9px; }
.merke.gr { background: #DCFCE7; color: #166534; }
.merke.gu { background: #FEF3C7; color: #92400E; }
.merke.ro { background: #FEE2E2; color: #991B1B; }
.chevron { width: 16px; height: 16px; color: var(--dempet); }
.kort-tittel { font-weight: 600; line-height: 1.35; margin: 0 0 10px; }
.kort-rad3 { display: flex; align-items: center; gap: 8px; }
.minibar { height: 6px; flex: 1; border-radius: 999px; background: var(--skinne); overflow: hidden; }
.minibar > i { display: block; height: 100%; border-radius: 999px; }
.minibar > i.gr { background: var(--gronn); }
.minibar > i.gu { background: var(--gul); }
.minibar > i.ro { background: var(--rod); }
.telling { font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; color: var(--dempet); }

/* Detalj-panel */
.overlegg { position: fixed; inset: 0; background: rgba(0,0,0,.45); opacity: 0; pointer-events: none; transition: opacity .2s; z-index: 40; }
.panel {
  position: fixed; top: 0; right: 0; bottom: 0; width: 100%; max-width: 576px; z-index: 50;
  background: var(--kort); border-left: 1px solid var(--kant); box-shadow: -8px 0 24px rgba(0,0,0,.12);
  display: flex; flex-direction: column; transform: translateX(100%); transition: transform .25s ease;
}
body.apen .overlegg { opacity: 1; pointer-events: auto; }
body.apen .panel { transform: translateX(0); }
/* display: flex over overstyrer hidden-attributtet, så uten denne ville alle
   panelene ligget oppå hverandre og det siste i DOM vunnet. */
.panel[hidden], .overlegg[hidden] { display: none; }
.panel-topp { flex: none; border-bottom: 1px solid var(--kant); padding: 20px 24px 16px; position: relative; }
.panel-tittel { font-size: 20px; font-weight: 700; letter-spacing: -.01em; margin: 8px 0 0; }
.panel-hvorfor { font-size: 14px; line-height: 1.6; color: var(--tekst); margin: 6px 0 0; }
.panel-aktor { font-size: 12px; color: var(--dempet); margin: 4px 0 0; }
.lukk { position: absolute; top: 14px; right: 14px; background: none; border: 0; border-radius: 6px; padding: 5px; cursor: pointer; color: var(--dempet); }
.lukk:hover { background: var(--bg); color: var(--tekst); }
.lukk svg { width: 16px; height: 16px; display: block; }
.panel-innhold { overflow-y: auto; padding: 16px 24px 32px; }
.seksjon { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--dempet); margin: 0 0 10px; }
ul.kriterier { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
li.kriterium { display: flex; align-items: flex-start; gap: 12px; background: var(--kort); border: 1px solid var(--kant); border-radius: 10px; padding: 12px 14px; }
.ikon { width: 16px; height: 16px; flex: none; margin-top: 2px; }
.ikon.gr { color: var(--gronn); } .ikon.gu { color: var(--gul); } .ikon.ro { color: var(--rod); }
.kr-midt { flex: 1; min-width: 0; }
.kr-tekst { font-size: 14px; font-weight: 500; line-height: 1.4; margin: 0; }
.kr-notat { font-size: 12px; line-height: 1.55; color: var(--dempet); margin: 4px 0 0; }
.kr-bevis { margin: 6px 0 0; }
.kr-bevis code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; background: var(--bg); border-radius: 4px; padding: 2px 6px; color: var(--dempet); word-break: break-all; }
.kopi { flex: none; background: none; border: 0; border-radius: 6px; padding: 5px; cursor: pointer; color: var(--dempet); }
.kopi:hover { background: var(--bg); color: var(--tekst); }
.kopi svg { width: 14px; height: 14px; display: block; }
.kopi.ok { color: var(--gronn); }
.goal-knapp { display: inline-flex; align-items: center; gap: 6px; margin-top: 12px; background: none; border: 1px solid var(--kant); border-radius: 8px; padding: 6px 11px; font: inherit; font-size: 12px; font-weight: 500; color: var(--dempet); cursor: pointer; }
.goal-knapp:hover { background: var(--bg); color: var(--tekst); }
.goal-knapp svg { width: 14px; height: 14px; }
.goal-knapp.ok { color: var(--gronn); }
.godkjent { font-size: 12px; background: rgba(228,228,231,.5); border-radius: 8px; padding: 10px 12px; margin: 16px 0 0; }
.godkjent.ja { color: #15803D; font-weight: 500; }
.godkjent.nei { color: var(--dempet); }

footer { border-top: 1px solid var(--kant); padding-top: 20px; text-align: center; font-size: 12px; color: var(--dempet); }
@media (max-width: 640px) { .avhengighet { margin-left: 0; width: 100%; } }
</style>
</head>
<body>
<main>

  <header>
    <div class="topp">
      <div>
        <h1><?php echo esc_html(BV_STATUS_TITTEL); ?></h1>
        <p class="undertekst">Verifisert mot kode <?php echo esc_html($verifisert); ?></p>
      </div>
      <span class="prosent"><?php echo (int) $total; ?>%</span>
    </div>
    <div class="bar" style="margin-top:16px" role="progressbar" aria-valuenow="<?php echo (int) $total; ?>" aria-valuemin="0" aria-valuemax="100">
      <i style="width: <?php echo (int) $total; ?>%"></i>
    </div>
    <div class="piller" style="margin-top:16px">
      <span class="pille gr"><?php echo (int) $fordeling['green']; ?> godkjent</span>
      <span class="pille gu"><?php echo (int) $fordeling['yellow']; ?> underveis</span>
      <span class="pille ro"><?php echo (int) $fordeling['red']; ?> ikke startet</span>
      <span class="avhengighet"><?php echo esc_html(BV_STATUS_AVHENGIGHET); ?></span>
    </div>
  </header>

  <div class="kort-liste">
    <?php foreach ($journeys as $j):
        $farge  = bv_status_journey_farge($j);
        $f      = $farger[$farge];
        $done   = bv_status_antall_done($j);
        $antall = count($j['kriterier']);
        $pst    = $antall ? ($done / $antall) * 100 : 0;
    ?>
    <button type="button" class="kort" data-journey="<?php echo esc_attr($j['id']); ?>">
      <span class="kort-rad1">
        <span class="jnr">Journey <?php echo (int) $j['nr']; ?></span>
        <span class="hoyre">
          <span class="merke <?php echo esc_attr($f['klasse']); ?>"><?php echo esc_html($f['label']); ?></span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </span>
      </span>
      <p class="kort-tittel"><?php echo esc_html($j['tittel']); ?></p>
      <span class="kort-rad3">
        <span class="minibar"><i class="<?php echo esc_attr($f['klasse']); ?>" style="width: <?php echo esc_attr($pst); ?>%"></i></span>
        <span class="telling"><?php echo (int) $done; ?>/<?php echo (int) $antall; ?></span>
      </span>
    </button>
    <?php endforeach; ?>
  </div>

  <footer>Oppdateres via <?php echo esc_html(BV_STATUS_DATAFIL); ?></footer>
</main>

<div class="overlegg" hidden></div>

<?php foreach ($journeys as $j):
    $farge  = bv_status_journey_farge($j);
    $f      = $farger[$farge];
    $done   = bv_status_antall_done($j);
    $antall = count($j['kriterier']);
    $pst    = $antall ? ($done / $antall) * 100 : 0;
?>
<aside class="panel" id="panel-<?php echo esc_attr($j['id']); ?>" hidden aria-label="Journey <?php echo (int) $j['nr']; ?>">
  <div class="panel-topp">
    <span class="jnr">Journey <?php echo (int) $j['nr']; ?></span>
    <span class="merke <?php echo esc_attr($f['klasse']); ?>" style="margin-left:8px"><?php echo esc_html($f['label']); ?></span>
    <button type="button" class="lukk" aria-label="Lukk">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
    <h2 class="panel-tittel"><?php echo esc_html($j['tittel']); ?></h2>
    <p class="panel-hvorfor"><?php echo esc_html($j['hvorfor']); ?></p>
    <p class="panel-aktor">Aktør: <?php echo esc_html($j['aktor']); ?></p>
    <div class="kort-rad3" style="margin-top:12px">
      <span class="minibar"><i class="<?php echo esc_attr($f['klasse']); ?>" style="width: <?php echo esc_attr($pst); ?>%"></i></span>
      <span class="telling"><?php echo (int) $done; ?>/<?php echo (int) $antall; ?> kriterier</span>
    </div>
    <button type="button" class="goal-knapp" data-goal="<?php echo esc_attr(bv_status_goal_journey($j)); ?>">
      <?php echo bv_status_kopiikon(); ?><span>Kopier /goal for hele journeyen</span>
    </button>
  </div>

  <div class="panel-innhold">
    <h3 class="seksjon">Akseptkriterier</h3>
    <ul class="kriterier">
      <?php foreach ($j['kriterier'] as $k): ?>
      <li class="kriterium">
        <?php echo bv_status_ikon($k['status']); ?>
        <div class="kr-midt">
          <p class="kr-tekst"><?php echo esc_html($k['tekst']); ?></p>
          <?php if (!empty($k['notat'])): ?><p class="kr-notat"><?php echo esc_html($k['notat']); ?></p><?php endif; ?>
          <?php if (!empty($k['bevis'])): ?><p class="kr-bevis"><code><?php echo esc_html($k['bevis']); ?></code></p><?php endif; ?>
        </div>
        <button type="button" class="kopi" title="Kopier /goal — lim inn i Claude, så jobber den til punktet er i mål"
                data-goal="<?php echo esc_attr(bv_status_goal_kriterium($k, $j)); ?>">
          <?php echo bv_status_kopiikon(); ?>
        </button>
      </li>
      <?php endforeach; ?>
    </ul>

    <?php if (!empty($j['godkjentAvTeam'])): $g = $j['godkjentAvTeam']; ?>
      <p class="godkjent ja">Godkjent av teamet <?php echo esc_html(bv_status_format_dato($g['dato'])); ?> (<?php echo esc_html($g['av']); ?>)<?php echo !empty($g['notat']) ? ' — ' . esc_html($g['notat']) : ''; ?></p>
    <?php else: ?>
      <p class="godkjent nei">Ikke godkjent av teamet ennå</p>
    <?php endif; ?>
  </div>
</aside>
<?php endforeach; ?>

<script>
(function () {
  var overlegg = document.querySelector('.overlegg');
  var apent = null;

  function apne(id) {
    var panel = document.getElementById('panel-' + id);
    if (!panel) return;
    lukk();
    panel.hidden = false;
    overlegg.hidden = false;
    // Tvinger reflow så transform-overgangen faktisk kjører fra 100%.
    void panel.offsetWidth;
    document.body.classList.add('apen');
    apent = panel;
    panel.querySelector('.lukk').focus();
  }

  function lukk() {
    document.body.classList.remove('apen');
    if (!apent) { overlegg.hidden = true; return; }
    var panel = apent;
    apent = null;
    // Vent ut utglidningen før hidden settes, ellers hopper panelet bort.
    setTimeout(function () {
      if (!document.body.classList.contains('apen')) {
        panel.hidden = true;
        overlegg.hidden = true;
      }
    }, 250);
  }

  document.querySelectorAll('.kort').forEach(function (kort) {
    kort.addEventListener('click', function () { apne(kort.dataset.journey); });
  });
  document.querySelectorAll('.lukk').forEach(function (b) {
    b.addEventListener('click', lukk);
  });
  overlegg.addEventListener('click', lukk);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') lukk(); });

  // Kopier /goal. clipboard-API krever sikker kontekst; på http://localhost
  // regnes den som sikker, men reservevegen finnes for alle andre tilfeller.
  function kopier(knapp) {
    var tekst = knapp.dataset.goal;
    var ferdig = function () {
      knapp.classList.add('ok');
      var etikett = knapp.querySelector('span');
      var opprinnelig = etikett ? etikett.textContent : null;
      if (etikett) etikett.textContent = '/goal kopiert';
      setTimeout(function () {
        knapp.classList.remove('ok');
        if (etikett) etikett.textContent = opprinnelig;
      }, 2000);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(tekst).then(ferdig);
      return;
    }
    var ta = document.createElement('textarea');
    ta.value = tekst;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); ferdig(); } finally { ta.remove(); }
  }

  document.querySelectorAll('[data-goal]').forEach(function (knapp) {
    knapp.addEventListener('click', function () { kopier(knapp); });
  });
})();
</script>
</body>
</html>
    <?php
}
