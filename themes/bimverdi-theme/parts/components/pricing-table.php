<?php
/**
 * BIM Verdi Pricing Table Component
 *
 * Render-funksjon for pricing-tabell. Tar pricing-data array og returnerer
 * HTML. Brukes av:
 *   - ACF Block (acf/bv-pricing-table) via render-callback
 *   - PHP-templates direkte: bimverdi_pricing_table($data)
 *   - Synced Patterns via bimverdi_render_pattern('pricing-tabell')
 *
 * Data-format:
 *   plans:        [{plan_key, plan_title, plan_highlight}, ...]
 *   header_rows:  [{label, footnote, values: [{plan_key, value}, ...]}, ...]
 *   groups:       [{group_title, rows: [{label, values: [{plan_key, value}, ...]}, ...]}, ...]
 *   disclaimers:  [{marker, text}, ...]
 *
 * Hvis $data er null/tom, leser fra ACF Options (bakoverkompatibilitet for
 * kall-sites som ikke gir egen data).
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render pricing-tabell.
 *
 * @param array|null $data Pricing-data eller null for å lese fra ACF Options.
 * @return string HTML.
 */
function bimverdi_pricing_table($data = null) {
    if (empty($data)) {
        $data = bimverdi_pricing_table_get_options_data();
    }

    if (empty($data['plans'])) {
        return '';
    }

    $plans       = $data['plans'];
    $header_rows = $data['header_rows'] ?? [];
    $groups      = $data['groups'] ?? [];
    $disclaimers = $data['disclaimers'] ?? [];
    $features_id = 'bv-pricing-features-' . wp_unique_id();
    $start_open  = !empty($data['start_open']);

    ob_start();
    ?>
    <section class="bv-pricing not-prose" aria-label="Deltakeravgift og -nivå">
        <div class="bv-pricing__scroll">
            <table class="bv-pricing__table bv-pricing__table--summary">
                <?php echo bimverdi_pricing_colgroup($plans); ?>
                <thead>
                    <tr>
                        <th scope="col" class="bv-pricing__corner"></th>
                        <?php foreach ($plans as $plan): ?>
                            <th
                                scope="col"
                                class="bv-pricing__plan<?php echo !empty($plan['plan_highlight']) ? ' bv-pricing__plan--highlight' : ''; ?>"
                            >
                                <span class="bv-pricing__plan-title"><?php echo esc_html($plan['plan_title']); ?></span>
                                <?php if (!empty($plan['plan_highlight'])): ?>
                                    <span class="bv-pricing__plan-flag">Anbefalt</span>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($header_rows as $row): ?>
                        <tr class="bv-pricing__header-row">
                            <th scope="row" class="bv-pricing__label">
                                <?php echo esc_html($row['label']); ?>
                                <?php if (!empty($row['footnote'])): ?>
                                    <sup class="bv-pricing__footnote-marker"><?php echo esc_html(trim($row['footnote'])); ?></sup>
                                <?php endif; ?>
                            </th>
                            <?php foreach ($plans as $plan): ?>
                                <?php $value = bimverdi_pricing_value_for_plan($row['values'] ?? [], $plan['plan_key']); ?>
                                <td class="bv-pricing__cell bv-pricing__cell--header<?php echo !empty($plan['plan_highlight']) ? ' bv-pricing__cell--highlight' : ''; ?>">
                                    <?php echo bimverdi_pricing_render_cell($value); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>

                    <?php
                    $has_any_cta = false;
                    foreach ($plans as $plan) {
                        if (!empty($plan['cta_label']) && !empty($plan['cta_url'])) {
                            $has_any_cta = true;
                            break;
                        }
                    }
                    ?>
                    <?php if ($has_any_cta): ?>
                        <tr class="bv-pricing__cta-row">
                            <th scope="row" class="bv-pricing__label bv-pricing__label--blank"></th>
                            <?php foreach ($plans as $plan): ?>
                                <td class="bv-pricing__cell bv-pricing__cell--cta<?php echo !empty($plan['plan_highlight']) ? ' bv-pricing__cell--highlight' : ''; ?>">
                                    <?php if (!empty($plan['cta_label']) && !empty($plan['cta_url'])): ?>
                                        <a
                                            class="bv-pricing__cta bv-pricing__cta--<?php echo !empty($plan['plan_highlight']) ? 'primary' : 'secondary'; ?>"
                                            href="<?php echo esc_url(bimverdi_pricing_resolve_url($plan['cta_url'])); ?>"
                                            aria-label="<?php echo esc_attr(sprintf(__('%s — %s', 'bimverdi'), $plan['cta_label'], $plan['plan_title'])); ?>"
                                        >
                                            <?php echo esc_html($plan['cta_label']); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($groups)): ?>
            <button
                type="button"
                class="bv-pricing__toggle"
                aria-expanded="<?php echo $start_open ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($features_id); ?>"
            >
                <span class="bv-pricing__toggle-label">Klikk her for å se hva som inngår i de ulike deltakernivåene</span>
                <svg class="bv-pricing__toggle-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </button>

            <div
                id="<?php echo esc_attr($features_id); ?>"
                class="bv-pricing__features"
                <?php echo $start_open ? '' : 'hidden'; ?>
            >
                <div class="bv-pricing__scroll">
                    <table class="bv-pricing__table bv-pricing__table--features">
                        <?php echo bimverdi_pricing_colgroup($plans); ?>
                        <tbody>
                            <?php foreach ($groups as $group): ?>
                                <tr class="bv-pricing__group-header">
                                    <th
                                        scope="colgroup"
                                        colspan="<?php echo (int) (count($plans) + 1); ?>"
                                        class="bv-pricing__group-title"
                                    >
                                        <?php echo esc_html($group['group_title']); ?>
                                    </th>
                                </tr>
                                <?php foreach (($group['rows'] ?? []) as $row): ?>
                                    <tr class="bv-pricing__feature-row">
                                        <th scope="row" class="bv-pricing__label">
                                            <?php echo esc_html($row['label']); ?>
                                        </th>
                                        <?php foreach ($plans as $plan): ?>
                                            <?php $value = bimverdi_pricing_value_for_plan($row['values'] ?? [], $plan['plan_key']); ?>
                                            <td class="bv-pricing__cell<?php echo !empty($plan['plan_highlight']) ? ' bv-pricing__cell--highlight' : ''; ?>">
                                                <?php echo bimverdi_pricing_render_cell($value); ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($disclaimers)): ?>
            <ul class="bv-pricing__disclaimers" role="list">
                <?php foreach ($disclaimers as $disclaimer): ?>
                    <li class="bv-pricing__disclaimer">
                        <sup class="bv-pricing__footnote-marker"><?php echo esc_html($disclaimer['marker']); ?></sup>
                        <?php echo esc_html($disclaimer['text']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <?php
    return (string) ob_get_clean();
}

/**
 * Løs CTA-URL: relative URLer (som starter med "/") prepenes med home_url()
 * så de fungerer både i lokal MAMP-subfolder og på prod-rot.
 *
 * @param string $url
 * @return string
 */
function bimverdi_pricing_resolve_url(string $url): string {
    $url = trim($url);
    if ($url === '' || preg_match('#^https?://#i', $url) || str_starts_with($url, '#')) {
        return $url;
    }
    if (str_starts_with($url, '/')) {
        return home_url($url);
    }
    return $url;
}

/**
 * Genererer en <colgroup> som deles mellom topp-tabellen og features-tabellen
 * så kolonnene har samme bredde i begge.
 *
 * @param array $plans
 * @return string
 */
function bimverdi_pricing_colgroup(array $plans): string {
    $count = count($plans);
    if ($count === 0) {
        return '';
    }
    $plan_width = round(72 / $count, 2);
    $html = '<colgroup>';
    $html .= '<col style="width:28%">';
    foreach ($plans as $plan) {
        $highlight = !empty($plan['plan_highlight']) ? ' class="bv-pricing__col--highlight"' : '';
        $html .= '<col' . $highlight . ' style="width:' . $plan_width . '%">';
    }
    $html .= '</colgroup>';
    return $html;
}

/**
 * Finn verdi for en plan i en values-array.
 *
 * @param array  $values   [{plan_key, value}, ...]
 * @param string $plan_key Nøkkel å matche.
 * @return string Verdi (eller tom hvis ikke funnet).
 */
function bimverdi_pricing_value_for_plan(array $values, string $plan_key): string {
    foreach ($values as $entry) {
        if (($entry['plan_key'] ?? '') === $plan_key) {
            return (string) ($entry['value'] ?? '');
        }
    }
    return '';
}

/**
 * Rendre en celle-verdi. Tom = "ikke inkludert" (vises som dash). "✓" = check-ikon.
 * Andre verdier = ren tekst.
 *
 * @param string $value
 * @return string HTML.
 */
function bimverdi_pricing_render_cell(string $value): string {
    $value = trim($value);

    if ($value === '') {
        return '<span class="bv-pricing__excluded" aria-label="Ikke inkludert">–</span>';
    }

    if ($value === '✓') {
        return '<span class="bv-pricing__included" aria-label="Inkludert">'
            . bimverdi_pricing_check_icon()
            . '</span>';
    }

    return '<span class="bv-pricing__value">' . esc_html($value) . '</span>';
}

/**
 * Check-ikon. Bruker bimverdi_get_icon_svg hvis tilgjengelig, ellers inline SVG.
 *
 * @return string SVG-markup.
 */
function bimverdi_pricing_check_icon(): string {
    if (function_exists('bimverdi_get_icon_svg')) {
        return bimverdi_get_icon_svg('check', 18);
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
}

/**
 * Hent pricing-data fra ACF Options.
 *
 * @return array
 */
function bimverdi_pricing_table_get_options_data(): array {
    if (!function_exists('get_field')) {
        return [];
    }

    return [
        'plans'       => get_field('pricing_plans', 'option') ?: [],
        'header_rows' => get_field('pricing_header_rows', 'option') ?: [],
        'groups'      => get_field('pricing_groups', 'option') ?: [],
        'disclaimers' => get_field('pricing_disclaimers', 'option') ?: [],
    ];
}
