<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Accordion</h2>
<p class="ds-section__desc">Sammenleggbare innholdsseksjoner via <code>bimverdi_accordion()</code>. Bruker native <code>&lt;details&gt;</code>/<code>&lt;summary&gt;</code>.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Standard (single)</h3>
<p style="font-size: 13px; color: #71717A; margin-bottom: 12px;">Kun &eacute;n &aring;pen om gangen.</p>
<div style="max-width: 560px; margin-bottom: 32px;">
    <?php bimverdi_accordion([
        'type' => 'single',
        'items' => [
            [
                'title'   => 'Hva koster medlemskap?',
                'content' => 'Vi tilbyr ulike medlemsnivåer: Deltaker, Prosjektdeltaker og Partner. Kontakt oss for en uforpliktende samtale om hva som passer best for din bedrift.',
                'open'    => true,
            ],
            [
                'title'   => 'Hvordan melder jeg inn bedriften?',
                'content' => 'Opprett en gratis profil, koble til bedriften din via organisasjonsnummer, og velg ønsket medlemsnivå. Du kan også bli invitert av en eksisterende hovedkontakt.',
            ],
            [
                'title'   => 'Kan jeg delta på arrangementer uten medlemskap?',
                'content' => 'Noen arrangementer er åpne for alle, mens andre er forbeholdt medlemmer. Se arrangementsoversikten for detaljer om hvert arrangement.',
            ],
        ],
    ]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Multiple</h3>
<p style="font-size: 13px; color: #71717A; margin-bottom: 12px;">Flere kan v&aelig;re &aring;pne samtidig.</p>
<div style="max-width: 560px; margin-bottom: 32px;">
    <?php bimverdi_accordion([
        'type' => 'multiple',
        'items' => [
            [
                'title'   => 'Verktøyoversikt',
                'content' => 'Se og administrer alle BIM-verktøy registrert av din bedrift. Du kan legge til nye, redigere eksisterende, eller fjerne utdaterte verktøy.',
                'open'    => true,
            ],
            [
                'title'   => 'Prosjektideer',
                'content' => 'Del ideer til pilotprosjekter med andre medlemmer. Ideene kan stemmes på og utvikles til fullverdige prosjekter.',
                'open'    => true,
            ],
            [
                'title'   => 'Temagrupper',
                'content' => 'Bli med i temagrupper for å samarbeide med andre i bransjen om spesifikke temaer som IFC, BIM-krav, eller bærekraft.',
            ],
        ],
    ]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Bordered</h3>
<div style="max-width: 560px; margin-bottom: 32px;">
    <?php bimverdi_accordion([
        'variant' => 'bordered',
        'items' => [
            [
                'title'   => 'Hvordan fungerer fakturering?',
                'content' => 'Vi sender faktura kvartalsvis. Betalingsfrist er 30 dager. Du kan når som helst endre eller si opp medlemskapet med virkning fra neste kvartal.',
                'open'    => true,
            ],
            [
                'title'   => 'Er dataene mine trygge?',
                'content' => 'All data lagres sikkert i Norge med kryptert overføring. Vi følger GDPR og norske personvernregler.',
            ],
            [
                'title'   => 'Hvilke integrasjoner støttes?',
                'content' => 'Vi støtter import fra IFC-filer, integrasjon med Brønnøysundregistrene, og eksport til Excel/CSV.',
            ],
        ],
    ]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Disabled item</h3>
<div style="max-width: 560px; margin-bottom: 32px;">
    <?php bimverdi_accordion([
        'items' => [
            [
                'title'   => 'Aktiv seksjon',
                'content' => 'Denne kan åpnes og lukkes som vanlig.',
                'open'    => true,
            ],
            [
                'title'   => 'Deaktivert seksjon',
                'content' => 'Denne kan ikke åpnes.',
                'disabled' => true,
            ],
            [
                'title'   => 'En annen aktiv seksjon',
                'content' => 'Denne fungerer også helt normalt.',
            ],
        ],
    ]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">// Standard (kun én åpen)
bimverdi_accordion([
    'type'  => 'single',
    'items' => [
        ['title' => 'Spørsmål 1', 'content' => 'Svar 1', 'open' => true],
        ['title' => 'Spørsmål 2', 'content' => 'Svar 2'],
    ],
]);

// Flere åpne samtidig
bimverdi_accordion([
    'type'  => 'multiple',
    'items' => [...],
]);

// Med ramme
bimverdi_accordion([
    'variant' => 'bordered',
    'items'   => [...],
]);</div>
