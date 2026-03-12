<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Field</h2>
<p class="ds-section__desc">Skjemafelt via <code>bimverdi_field()</code>. Label, input, description og feilmelding i &eacute;n komponent.</p>

<div style="max-width: 480px;">

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Text input</h3>
<?php bimverdi_field([
    'label' => 'Navn p&aring; kort',
    'name'  => 'demo_name',
    'placeholder' => 'Ola Nordmann',
    'value' => '',
]); ?>

<?php bimverdi_field([
    'label' => 'Kortnummer',
    'name'  => 'demo_card',
    'placeholder' => '1234 5678 9012 3456',
    'description' => 'Skriv inn ditt 16-sifrede kortnummer.',
]); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Field group (horisontal)</h3>
<?php bimverdi_field_group(); ?>
    <?php bimverdi_field([
        'label' => 'M&aring;ned',
        'name'  => 'demo_month',
        'type'  => 'select',
        'placeholder' => 'MM',
        'options' => array_combine(range(1,12), array_map(function($m) { return str_pad($m, 2, '0', STR_PAD_LEFT); }, range(1,12))),
    ]); ?>
    <?php bimverdi_field([
        'label' => '&Aring;r',
        'name'  => 'demo_year',
        'type'  => 'select',
        'placeholder' => '&Aring;&Aring;&Aring;&Aring;',
        'options' => array_combine(range(2026,2034), range(2026,2034)),
    ]); ?>
    <?php bimverdi_field([
        'label' => 'CVV',
        'name'  => 'demo_cvv',
        'placeholder' => '123',
    ]); ?>
<?php bimverdi_field_group_end(); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Select</h3>
<?php bimverdi_field([
    'label' => 'Rolle',
    'name'  => 'demo_role',
    'type'  => 'select',
    'placeholder' => 'Velg rolle',
    'options' => [
        'deltaker' => 'Deltaker',
        'prosjektdeltaker' => 'Prosjektdeltaker',
        'partner' => 'Partner',
    ],
    'description' => 'Velg brukerens rolle i systemet.',
]); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Checkbox</h3>
<?php bimverdi_field([
    'label' => 'Samme som leveringsadresse',
    'name'  => 'demo_same_address',
    'type'  => 'checkbox',
    'checked' => true,
]); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Textarea</h3>
<?php bimverdi_field([
    'label' => 'Kommentar',
    'name'  => 'demo_comment',
    'type'  => 'textarea',
    'placeholder' => 'Legg til kommentar...',
]); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med feilmelding</h3>
<?php bimverdi_field([
    'label' => 'E-post',
    'name'  => 'demo_email_error',
    'type'  => 'email',
    'value' => 'ugyldig',
    'error' => 'Vennligst oppgi en gyldig e-postadresse.',
    'required' => true,
]); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">P&aring;krevd</h3>
<?php bimverdi_field([
    'label' => 'Organisasjonsnummer',
    'name'  => 'demo_orgnr',
    'placeholder' => '123 456 789',
    'required' => true,
    'description' => '9-sifret norsk organisasjonsnummer.',
]); ?>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Knapper i skjema</h3>
<div style="display: flex; gap: 8px; margin-bottom: 32px;">
    <?php bimverdi_button(['text' => 'Submit', 'variant' => 'default', 'type' => 'submit']); ?>
    <?php bimverdi_button(['text' => 'Cancel', 'variant' => 'outline']); ?>
</div>

</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">bimverdi_field([
    'label'       => 'E-post',
    'name'        => 'email',
    'type'        => 'email',
    'placeholder' => 'ola@firma.no',
    'description' => 'Vi deler aldri e-posten din.',
    'required'    => true,
]);

// Field group (horisontale felter)
bimverdi_field_group('Utl&oslash;psdato');
    bimverdi_field(['label' => 'M&aring;ned', 'name' => 'month', 'type' => 'select', ...]);
    bimverdi_field(['label' => '&Aring;r', 'name' => 'year', 'type' => 'select', ...]);
bimverdi_field_group_end();</div>
