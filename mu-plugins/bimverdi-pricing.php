<?php
/**
 * Pricing & Medlemskap (Freemium-modell)
 *
 * Registrerer ACF Options Page (Innstillinger → Priser & medlemskap) hvor
 * Bård kan redigere medlemskaps-tabellen som vises i onboarding og på
 * offentlig pricing-side. Datadrevet — én sannhet, gjenbrukbart element.
 *
 * Plan: WORKLOG.md 2026-05-06 (T1, T2). Synk med Bård 2026-05-06.
 *
 * Verdier (priser, kontaktpers-formuleringer, dato-cutoffs som 1.7.26)
 * skal kunne endres uten kode-deploy. Render-komponent kommer i neste
 * iterasjon (bimverdi_pricing_table()).
 *
 * @package BimVerdi
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// OPTIONS PAGE
// =============================================================================

add_action('acf/init', function () {
    if (!function_exists('acf_add_options_page')) {
        return;
    }

    acf_add_options_page([
        'page_title'  => 'Deltakeravgift og -nivå',
        'menu_title'  => 'Deltakeravgift',
        'menu_slug'   => 'bimverdi-pricing',
        'capability'  => 'manage_options',
        'parent_slug' => 'options-general.php',
        'position'    => '',
        'icon_url'    => '',
        'redirect'    => false,
    ]);
});

/**
 * Hjelp-notice øverst på ACF Options-siden.
 *
 * Forklarer arkitekturen: data redigeres her, men tabellen vises på sider
 * via Synced Pattern «Pricing-tabell» eller bimverdi_render_pattern() i PHP.
 */
add_action('admin_notices', 'bimverdi_pricing_options_help_notice');

function bimverdi_pricing_options_help_notice(): void {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || strpos($screen->id, 'bimverdi-pricing') === false) {
        return;
    }

    $pattern = bimverdi_get_pattern_by_slug('pricing-tabell');
    $pattern_link = $pattern
        ? get_edit_post_link($pattern->ID)
        : admin_url('edit.php?post_type=wp_block');
    $pattern_label = $pattern
        ? __('Rediger Synced Pattern «Deltakeravgift-tabell»', 'bimverdi')
        : __('Opprett Synced Pattern', 'bimverdi');

    ?>
    <div class="notice notice-info" style="border-left-color:#FF8B5E;">
        <p style="margin:0.5em 0;">
            <strong><?php esc_html_e('Slik henger dette sammen:', 'bimverdi'); ?></strong>
        </p>
        <ul style="margin:0.5em 0 0.5em 1.5em; list-style:disc;">
            <li><?php esc_html_e('På denne siden redigerer du tabell-dataene (priser, plan-titler, features).', 'bimverdi'); ?></li>
            <li>
                <?php
                printf(
                    /* translators: %s: link to Synced Pattern editor */
                    esc_html__('For å vise tabellen på en side, sett inn blokken «Deltakeravgift-tabell» — eller bruk %s for å redigere den sentrale plasseringen.', 'bimverdi'),
                    '<a href="' . esc_url($pattern_link) . '">' . esc_html($pattern_label) . '</a>'
                );
                ?>
            </li>
            <li>
                <code>&lt;?php echo bimverdi_render_pattern('pricing-tabell'); ?&gt;</code>
                — <?php esc_html_e('rendrer tabellen i et PHP-template.', 'bimverdi'); ?>
            </li>
        </ul>
    </div>
    <?php
}

// =============================================================================
// FELTGRUPPE: PRICING-TABELL
// =============================================================================

add_action('acf/init', 'bimverdi_register_pricing_fields');

function bimverdi_register_pricing_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'      => 'group_bv_pricing',
        'title'    => 'Deltakeravgift-tabell',
        'fields'   => [
            // -----------------------------------------------------------------
            // PLANER (4 stk: gratis, deltaker, prosjektdeltaker, partner)
            // -----------------------------------------------------------------
            [
                'key'          => 'field_bv_pricing_intro',
                'label'        => 'Introduksjon',
                'name'         => 'pricing_intro',
                'type'         => 'message',
                'message'      => '<strong>Tabellen vises i onboarding og på offentlig deltakeravgift-side.</strong><br>'
                                . 'Endringer her slår igjennom alle steder tabellen vises. '
                                . 'Bruk «✓» for inkludert, la feltet stå tomt for ikke inkludert, '
                                . 'eller skriv egen tekst (f.eks. «kommer», «1/uke før 1.7.26»).',
            ],
            [
                'key'          => 'field_bv_pricing_plans',
                'label'        => 'Planer (kolonner)',
                'name'         => 'pricing_plans',
                'type'         => 'repeater',
                'instructions' => 'De 4 deltakernivåene som vises som kolonner. Rekkefølgen her bestemmer rekkefølgen i tabellen.',
                'min'          => 1,
                'max'          => 6,
                'layout'       => 'table',
                'button_label' => 'Legg til plan',
                'sub_fields'   => [
                    [
                        'key'      => 'field_bv_pricing_plan_key',
                        'label'    => 'Nøkkel',
                        'name'     => 'plan_key',
                        'type'     => 'text',
                        'required' => 1,
                        'instructions' => 'Brukes internt og i URL/CSS. Lowercase, ingen mellomrom (f.eks. "gratis", "deltaker").',
                    ],
                    [
                        'key'      => 'field_bv_pricing_plan_title',
                        'label'    => 'Tittel',
                        'name'     => 'plan_title',
                        'type'     => 'text',
                        'required' => 1,
                    ],
                    [
                        'key'      => 'field_bv_pricing_plan_highlight',
                        'label'    => 'Fremhev?',
                        'name'     => 'plan_highlight',
                        'type'     => 'true_false',
                        'instructions' => 'Marker som anbefalt plan (visuell uthevelse).',
                        'ui'       => 1,
                    ],
                    [
                        'key'      => 'field_bv_pricing_plan_cta_label',
                        'label'    => 'CTA-tekst',
                        'name'     => 'cta_label',
                        'type'     => 'text',
                        'instructions' => 'Knapp-tekst som vises i tabellen, f.eks. «Velg Deltaker» eller «Bli gratisbruker». La stå tom for å skjule knappen.',
                    ],
                    [
                        'key'      => 'field_bv_pricing_plan_cta_url',
                        'label'    => 'CTA-URL',
                        'name'     => 'cta_url',
                        'type'     => 'text',
                        'instructions' => 'Hvor knappen skal lede. F.eks. «/min-side/foretak/registrer/?nivaa=deltaker». Relative URLer fungerer.',
                    ],
                ],
            ],

            // -----------------------------------------------------------------
            // HEADER-RADER (Juridisk enhet, Pris, Kontaktpers.)
            // -----------------------------------------------------------------
            [
                'key'          => 'field_bv_pricing_header_rows',
                'label'        => 'Header-rader',
                'name'         => 'pricing_header_rows',
                'type'         => 'repeater',
                'instructions' => 'Rader som vises øverst i tabellen, før feature-grupper. Typisk: Juridisk enhet, Pris, Antall kontaktpersoner.',
                'layout'       => 'block',
                'button_label' => 'Legg til header-rad',
                'sub_fields'   => [
                    [
                        'key'      => 'field_bv_pricing_header_label',
                        'label'    => 'Etikett',
                        'name'     => 'label',
                        'type'     => 'text',
                        'required' => 1,
                    ],
                    [
                        'key'      => 'field_bv_pricing_header_footnote',
                        'label'    => 'Fotnote-markør',
                        'name'     => 'footnote',
                        'type'     => 'text',
                        'instructions' => 'Valgfri. F.eks. "*" eller "**". Kobles til Disclaimers-tekst nederst.',
                        'maxlength' => 4,
                    ],
                    [
                        'key'      => 'field_bv_pricing_header_values',
                        'label'    => 'Verdier per plan',
                        'name'     => 'values',
                        'type'     => 'repeater',
                        'layout'   => 'table',
                        'instructions' => 'Én rad per plan, samme rekkefølge som planer over.',
                        'sub_fields' => [
                            [
                                'key'   => 'field_bv_pricing_header_value_plan_key',
                                'label' => 'Plan-nøkkel',
                                'name'  => 'plan_key',
                                'type'  => 'text',
                                'instructions' => 'Må matche plan_key over (gratis/deltaker/...).',
                                'required' => 1,
                            ],
                            [
                                'key'   => 'field_bv_pricing_header_value',
                                'label' => 'Verdi',
                                'name'  => 'value',
                                'type'  => 'text',
                                'instructions' => 'F.eks. "Person", "8 000 kr", "ubegrenset (før 1.7.26)".',
                                'required' => 1,
                            ],
                        ],
                    ],
                ],
            ],

            // -----------------------------------------------------------------
            // FEATURE-GRUPPER (Teams-grupper, Rådgivning, etc.)
            // -----------------------------------------------------------------
            [
                'key'          => 'field_bv_pricing_groups',
                'label'        => 'Feature-grupper',
                'name'         => 'pricing_groups',
                'type'         => 'repeater',
                'instructions' => 'Hver gruppe har en overskrift og inneholder rader med features. F.eks. "Teams-grupper" med radene "Diskusjoner" og "Egne grupper for rådgivning".',
                'layout'       => 'block',
                'button_label' => 'Legg til gruppe',
                'sub_fields'   => [
                    [
                        'key'      => 'field_bv_pricing_group_title',
                        'label'    => 'Gruppetittel',
                        'name'     => 'group_title',
                        'type'     => 'text',
                        'required' => 1,
                    ],
                    [
                        'key'        => 'field_bv_pricing_group_rows',
                        'label'      => 'Rader',
                        'name'       => 'rows',
                        'type'       => 'repeater',
                        'layout'     => 'block',
                        'sub_fields' => [
                            [
                                'key'      => 'field_bv_pricing_row_label',
                                'label'    => 'Etikett',
                                'name'     => 'label',
                                'type'     => 'text',
                                'required' => 1,
                            ],
                            [
                                'key'        => 'field_bv_pricing_row_values',
                                'label'      => 'Verdier per plan',
                                'name'       => 'values',
                                'type'       => 'repeater',
                                'layout'     => 'table',
                                'instructions' => 'Bruk "✓" for inkludert, tom for ikke inkludert, eller egen tekst (f.eks. "kommer").',
                                'sub_fields' => [
                                    [
                                        'key'      => 'field_bv_pricing_row_value_plan_key',
                                        'label'    => 'Plan-nøkkel',
                                        'name'     => 'plan_key',
                                        'type'     => 'text',
                                        'required' => 1,
                                    ],
                                    [
                                        'key'      => 'field_bv_pricing_row_value',
                                        'label'    => 'Verdi',
                                        'name'     => 'value',
                                        'type'     => 'text',
                                        'instructions' => '"✓", "" (tom), eller egen tekst.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // -----------------------------------------------------------------
            // DISCLAIMERS (footnotes)
            // -----------------------------------------------------------------
            [
                'key'          => 'field_bv_pricing_disclaimers',
                'label'        => 'Disclaimers (fotnoter)',
                'name'         => 'pricing_disclaimers',
                'type'         => 'repeater',
                'instructions' => 'Fotnote-tekster som vises under tabellen. Markør (*, **) kobles til header-rader eller verdier som refererer til samme markør.',
                'layout'       => 'block',
                'button_label' => 'Legg til disclaimer',
                'sub_fields'   => [
                    [
                        'key'      => 'field_bv_pricing_disclaimer_marker',
                        'label'    => 'Markør',
                        'name'     => 'marker',
                        'type'     => 'text',
                        'required' => 1,
                        'maxlength' => 4,
                        'instructions' => 'F.eks. "*", "**".',
                    ],
                    [
                        'key'      => 'field_bv_pricing_disclaimer_text',
                        'label'    => 'Tekst',
                        'name'     => 'text',
                        'type'     => 'textarea',
                        'rows'     => 2,
                        'required' => 1,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'bimverdi-pricing',
                ],
            ],
        ],
        'menu_order'      => 0,
        'position'        => 'normal',
        'style'           => 'default',
        'label_placement' => 'top',
        'active'          => true,
    ]);
}

// =============================================================================
// SEED: BÅRDS PRICING-DATA (idempotent)
// =============================================================================

/**
 * Populerer ACF options med Bårds initial-tabell første gang plugin lastes.
 *
 * Kjøres én gang — beskyttet av bimverdi_pricing_seeded-flagg. Etter det er
 * tabellen redigerbar i wp-admin uten at seed overstyrer endringene.
 *
 * For å re-seede (f.eks. etter feilredigering): slett option-keyen via
 * WP-CLI eller PHP: delete_option('bimverdi_pricing_seeded') + slett
 * options-rader manuelt.
 */
add_action('acf/init', 'bimverdi_seed_pricing_data', 20);

function bimverdi_seed_pricing_data() {
    if (get_option('bimverdi_pricing_seeded')) {
        return;
    }

    if (!function_exists('update_field')) {
        return;
    }

    // Planer (4 kolonner)
    update_field('pricing_plans', bimverdi_pricing_default_plans(), 'option');

    // Header-rader
    update_field('pricing_header_rows', [
        [
            'label'    => 'Juridisk enhet',
            'footnote' => '',
            'values'   => [
                ['plan_key' => 'gratis',           'value' => 'Person'],
                ['plan_key' => 'deltaker',         'value' => 'Foretak'],
                ['plan_key' => 'prosjektdeltaker', 'value' => 'Foretak'],
                ['plan_key' => 'partner',          'value' => 'Foretak'],
            ],
        ],
        [
            'label'    => 'Pris/år',
            'footnote' => '* **',
            'values'   => [
                ['plan_key' => 'gratis',           'value' => 'gratis'],
                ['plan_key' => 'deltaker',         'value' => '8 000 kr'],
                ['plan_key' => 'prosjektdeltaker', 'value' => '24 000 kr'],
                ['plan_key' => 'partner',          'value' => '48 000 kr'],
            ],
        ],
        [
            'label'    => 'Antall kontaktpersoner',
            'footnote' => '',
            'values'   => [
                ['plan_key' => 'gratis',           'value' => '1'],
                ['plan_key' => 'deltaker',         'value' => 'ubegrenset (ved registrering før 1.7.26)'],
                ['plan_key' => 'prosjektdeltaker', 'value' => 'ubegrenset (ved registrering før 1.7.26)'],
                ['plan_key' => 'partner',          'value' => 'ubegrenset (ved registrering før 1.7.26)'],
            ],
        ],
    ], 'option');

    // Feature-grupper
    update_field('pricing_groups', [
        [
            'group_title' => 'Nyhetsbrev',
            'rows' => [
                [
                    'label'  => 'Tilpasset nyhetsbrev',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '✓', 'deltaker' => '✓', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
            ],
        ],
        [
            'group_title' => 'Åpne møter og webinarer',
            'rows' => [
                [
                    'label'  => 'Delta',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '✓', 'deltaker' => '✓', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
                [
                    'label'  => 'Se opptak',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '', 'deltaker' => '✓', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
            ],
        ],
        [
            'group_title' => 'Teams-grupper',
            'rows' => [
                [
                    'label'  => 'Diskusjoner og erfaringsbygging',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '', 'deltaker' => '✓', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
                [
                    'label'  => 'Egne teams-grupper for rådgivning m.m.',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '', 'deltaker' => '', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
            ],
        ],
        [
            'group_title' => 'Artikler og verktøy-publisering',
            'rows' => [
                [
                    'label'  => 'Digitale verktøy og tjenester',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '', 'deltaker' => '✓', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
                [
                    'label'  => 'Artikler',
                    'values' => bimverdi_pricing_seed_row([
                        'gratis'           => '',
                        'deltaker'         => 'én per uke før 1.7.26',
                        'prosjektdeltaker' => 'én per uke',
                        'partner'          => 'én per uke',
                    ]),
                ],
            ],
        ],
        [
            'group_title' => 'Kunnskapsbase og KI-søk',
            'rows' => [
                [
                    'label'  => 'Registrere kunnskapskilder (egne og andres)',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '✓', 'deltaker' => '✓', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
                [
                    'label'  => 'ByggChat ALFA',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '', 'deltaker' => 'kommer', 'prosjektdeltaker' => 'kommer', 'partner' => 'kommer']),
                ],
            ],
        ],
        [
            'group_title' => 'Rådgivning og søknadsutforming',
            'rows' => [
                [
                    'label'  => 'Innledende',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '', 'deltaker' => '✓', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
                [
                    'label'  => 'Utvidet',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '', 'deltaker' => '', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
            ],
        ],
        [
            'group_title' => 'Innovasjonsprosjekter',
            'rows' => [
                [
                    'label'  => 'Registrere forslag',
                    'values' => bimverdi_pricing_seed_row(['gratis' => 'kommer', 'deltaker' => 'kommer', 'prosjektdeltaker' => 'kommer', 'partner' => 'kommer']),
                ],
                [
                    'label'  => 'Aktiv deltakelse',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '', 'deltaker' => '', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
            ],
        ],
        [
            'group_title' => 'Prosjekt- og partnerforum',
            'rows' => [
                [
                    'label'  => 'Deltakelse (avklares)',
                    'values' => bimverdi_pricing_seed_row(['gratis' => '', 'deltaker' => '', 'prosjektdeltaker' => '✓', 'partner' => '✓']),
                ],
            ],
        ],
    ], 'option');

    // Disclaimers
    update_field('pricing_disclaimers', [
        [
            'marker' => '*',
            'text'   => 'Rabatter for oppstartbedrifter, utdanningsinstitusjoner og foretak med omsetning lavere enn 5 MNOK.',
        ],
        [
            'marker' => '**',
            'text'   => 'Årsavgift og oppgraderinger beregnes fra inneværende kvartal.',
        ],
    ], 'option');

    update_option('bimverdi_pricing_seeded', current_time('mysql'));
}

/**
 * Default plans-data (brukt av seed + backfill).
 *
 * @return array
 */
function bimverdi_pricing_default_plans(): array {
    return [
        [
            'plan_key'       => 'gratis',
            'plan_title'     => 'Gratisbruker',
            'plan_highlight' => false,
            'cta_label'      => 'Velg',
            'cta_url'        => '/min-side/foretak/registrer/?nivaa=gratis',
        ],
        [
            'plan_key'       => 'deltaker',
            'plan_title'     => 'Deltaker',
            'plan_highlight' => true,
            'cta_label'      => 'Velg',
            'cta_url'        => '/min-side/foretak/registrer/?nivaa=deltaker',
        ],
        [
            'plan_key'       => 'prosjektdeltaker',
            'plan_title'     => 'Prosjektdeltaker',
            'plan_highlight' => false,
            'cta_label'      => 'Velg',
            'cta_url'        => '/min-side/foretak/registrer/?nivaa=prosjektdeltaker',
        ],
        [
            'plan_key'       => 'partner',
            'plan_title'     => 'Partner',
            'plan_highlight' => false,
            'cta_label'      => 'Velg',
            'cta_url'        => '/min-side/foretak/registrer/?nivaa=partner',
        ],
    ];
}

/**
 * Backfill v3: bytt gamle CTA-labels til generisk "Velg". Beskytter
 * Bård-redigerte verdier ved å bare overskrive hvis nåværende label
 * matcher en av v2-defaultene.
 */
add_action('acf/init', 'bimverdi_seed_pricing_data_v3', 26);

function bimverdi_seed_pricing_data_v3(): void {
    if (get_option('bimverdi_pricing_seeded_v3')) {
        return;
    }

    if (!function_exists('get_field') || !function_exists('update_field')) {
        return;
    }

    $current_plans = get_field('pricing_plans', 'option');
    if (!$current_plans) {
        return;
    }

    $old_labels = [
        'Bli gratisbruker',
        'Velg Deltaker',
        'Velg Prosjektdeltaker',
        'Velg Partner',
    ];

    $changed = false;
    foreach ($current_plans as &$plan) {
        if (in_array(($plan['cta_label'] ?? ''), $old_labels, true)) {
            $plan['cta_label'] = 'Velg';
            $changed = true;
        }
    }
    unset($plan);

    if ($changed) {
        update_field('pricing_plans', $current_plans, 'option');
    }

    update_option('bimverdi_pricing_seeded_v3', current_time('mysql'));
}

/**
 * Backfill CTA-felter på planer hvis de mangler. Kjøres etter at v1-seed
 * er kjørt — beskyttet av eget v2-flagg så den ikke overskriver Bårds
 * manuelle endringer.
 */
add_action('acf/init', 'bimverdi_seed_pricing_data_v2', 25);

function bimverdi_seed_pricing_data_v2(): void {
    if (get_option('bimverdi_pricing_seeded_v2')) {
        return;
    }

    if (!function_exists('get_field') || !function_exists('update_field')) {
        return;
    }

    $current_plans = get_field('pricing_plans', 'option');
    if (!$current_plans) {
        return;
    }

    $defaults = bimverdi_pricing_default_plans();
    $defaults_by_key = [];
    foreach ($defaults as $d) {
        $defaults_by_key[$d['plan_key']] = $d;
    }

    $changed = false;
    foreach ($current_plans as &$plan) {
        $key = $plan['plan_key'] ?? '';
        $default = $defaults_by_key[$key] ?? null;
        if (!$default) {
            continue;
        }
        if (empty($plan['cta_label']) && !empty($default['cta_label'])) {
            $plan['cta_label'] = $default['cta_label'];
            $changed = true;
        }
        if (empty($plan['cta_url']) && !empty($default['cta_url'])) {
            $plan['cta_url'] = $default['cta_url'];
            $changed = true;
        }
    }
    unset($plan);

    if ($changed) {
        update_field('pricing_plans', $current_plans, 'option');
    }

    update_option('bimverdi_pricing_seeded_v2', current_time('mysql'));
}

/**
 * Helper: bygger values-array for én feature-rad fra en plan_key => value map.
 *
 * @param array $map ['gratis' => '✓', 'deltaker' => '', ...]
 * @return array Format ACF repeater forventer.
 */
function bimverdi_pricing_seed_row(array $map): array {
    $values = [];
    foreach ($map as $plan_key => $value) {
        $values[] = ['plan_key' => $plan_key, 'value' => $value];
    }
    return $values;
}

// =============================================================================
// SEED: SYNCED PATTERN «pricing-tabell»
// =============================================================================

/**
 * Lager en Synced Pattern som inneholder pricing-blokken første gang plugin
 * lastes. Bård redigerer den i Gutenberg, og PHP-templates kan kalle:
 *
 *     echo bimverdi_render_pattern('pricing-tabell');
 *
 * Idempotent — beskyttet av bimverdi_pricing_pattern_seeded-flagg. Hvis
 * Bård sletter pattern-en, kan vi re-seede ved å slette flagget.
 */
add_action('init', 'bimverdi_seed_pricing_pattern', 30);

function bimverdi_seed_pricing_pattern(): void {
    if (get_option('bimverdi_pricing_pattern_seeded')) {
        return;
    }

    // Kjør først etter at wp_block er registrert (init=30 er etter standard-CPTs).
    if (!post_type_exists('wp_block')) {
        return;
    }

    // Sjekk om en pattern med samme slug allerede finnes (manuelt opprettet).
    $existing = get_posts([
        'name'             => 'pricing-tabell',
        'post_type'        => 'wp_block',
        'post_status'      => 'any',
        'numberposts'      => 1,
        'suppress_filters' => false,
    ]);

    if ($existing) {
        update_option('bimverdi_pricing_pattern_seeded', current_time('mysql'));
        return;
    }

    $post_id = wp_insert_post([
        'post_type'    => 'wp_block',
        'post_status'  => 'publish',
        'post_title'   => 'Deltakeravgift-tabell',
        'post_name'    => 'pricing-tabell',
        'post_content' => "<!-- wp:acf/bv-pricing-table /-->",
    ], true);

    if (is_wp_error($post_id)) {
        return;
    }

    update_option('bimverdi_pricing_pattern_seeded', current_time('mysql'));
}

// =============================================================================
// ACF BLOCK: bimverdi/pricing-table
// =============================================================================

/**
 * Registrer ACF Block som rendrer pricing-tabellen i Gutenberg.
 *
 * Første versjon: blokken har ingen egne felter — render-callback delegerer
 * til bimverdi_pricing_table() som leser fra ACF Options. Det gir én
 * sannhetskilde og lar blokken brukes som "drop anywhere"-element via
 * Synced Patterns.
 *
 * Hvis vi senere trenger per-instans-overstyring (f.eks. én plan som er
 * unik for én side), kan vi legge til ACF-felter på blokken og passe dem
 * inn som $data-argument til bimverdi_pricing_table().
 */
add_action('acf/init', 'bimverdi_register_pricing_block');

function bimverdi_register_pricing_block() {
    if (!function_exists('acf_register_block_type')) {
        return;
    }

    acf_register_block_type([
        'name'            => 'bv-pricing-table',
        'title'           => __('Deltakeravgift-tabell', 'bimverdi'),
        'description'     => __('Tabell over deltakernivåer: Gratis / Deltaker / Prosjektdeltaker / Partner. Data fra Innstillinger → Deltakeravgift.', 'bimverdi'),
        'render_callback' => 'bimverdi_render_pricing_block',
        'category'        => 'bimverdi',
        'icon'            => 'list-view',
        'keywords'        => ['deltakeravgift', 'deltakernivå', 'priser', 'tabell', 'deltaker'],
        'mode'            => 'preview',
        'supports'        => [
            'align'  => ['wide', 'full'],
            'anchor' => true,
            'jsx'    => false,
        ],
        'example' => [
            'attributes' => [
                'mode' => 'preview',
                'data' => [],
            ],
        ],
    ]);
}

/**
 * Render-callback for ACF Pricing Block.
 *
 * @param array  $block      Block-instans.
 * @param string $content    Inner blocks (ikke brukt).
 * @param bool   $is_preview Editor-preview eller frontend.
 * @param int    $post_id    Post-ID som inneholder blokken.
 */
function bimverdi_render_pricing_block($block, $content = '', $is_preview = false, $post_id = 0) {
    $align = !empty($block['align']) ? 'align' . $block['align'] : '';
    $anchor = !empty($block['anchor']) ? ' id="' . esc_attr($block['anchor']) . '"' : '';

    $classes = ['wp-block-bimverdi-pricing-table'];
    if ($align) {
        $classes[] = $align;
    }
    if (!empty($block['className'])) {
        $classes[] = $block['className'];
    }

    echo '<div' . $anchor . ' class="' . esc_attr(implode(' ', $classes)) . '">';

    if (function_exists('bimverdi_pricing_table')) {
        echo bimverdi_pricing_table();
    } elseif ($is_preview) {
        echo '<p style="padding:1rem;background:#FEF3C7;border:1px solid #F59E0B;">'
            . esc_html__('bimverdi_pricing_table() er ikke lastet. Sjekk theme-functions.php.', 'bimverdi')
            . '</p>';
    }

    echo '</div>';
}

/**
 * Registrer egen blokk-kategori «BIM Verdi» så våre blokker er lett å finne.
 */
add_filter('block_categories_all', 'bimverdi_register_block_category', 10, 2);

function bimverdi_register_block_category($categories, $editor_context) {
    foreach ($categories as $cat) {
        if (($cat['slug'] ?? '') === 'bimverdi') {
            return $categories;
        }
    }

    array_unshift($categories, [
        'slug'  => 'bimverdi',
        'title' => __('BIM Verdi', 'bimverdi'),
        'icon'  => null,
    ]);

    return $categories;
}
