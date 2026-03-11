<?php
// Section: Farger (Colors)
if (!defined('ABSPATH')) exit;

/**
 * Render a group of color swatches
 */
function ds_render_swatches($title, $colors) {
    ?>
    <h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin-bottom: 12px;"><?php echo esc_html($title); ?></h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px; margin-bottom: 32px;">
        <?php foreach ($colors as $color) :
            $hex = $color['hex'];
            $var = $color['var'];
            $name = $color['name'];
            // Use dark border for very light colors, subtle border otherwise
            $is_light = in_array($hex, ['#FFFFFF', '#FFF3ED', '#F7F5EF', '#F5F5F4', '#EFE9DE']);
            $border_color = $is_light ? '#D6D1C6' : '#E7E5E4';
        ?>
            <div>
                <div style="width: 100%; height: 64px; border-radius: 8px; background: <?php echo esc_attr($hex); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>;"></div>
                <div style="margin-top: 8px;">
                    <div style="font-size: 13px; font-weight: 500; color: #1A1A1A;"><?php echo esc_html($name); ?></div>
                    <div style="font-size: 12px; color: #888; font-family: monospace;"><?php echo esc_html($hex); ?></div>
                    <div style="font-size: 11px; color: #aaa; font-family: monospace;"><?php echo esc_html($var); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
?>

<h2 class="ds-section__title">Farger</h2>
<p class="ds-section__desc">Fargepaletten for BIM Verdi. Alle farger er definert som CSS-variabler i <code>inc/design-system.php</code>.</p>

<?php

// --- Bakgrunner ---
ds_render_swatches('Bakgrunner', [
    ['name' => 'Page',        'var' => '--color-bg-page',        'hex' => '#F7F5EF'],
    ['name' => 'Surface',     'var' => '--color-bg-surface',     'hex' => '#FFFFFF'],
    ['name' => 'Surface Alt', 'var' => '--color-bg-surface-alt', 'hex' => '#F5F5F4'],
    ['name' => 'Muted',       'var' => '--color-bg-muted',       'hex' => '#EFE9DE'],
]);

// --- Tekst ---
ds_render_swatches('Tekst', [
    ['name' => 'Primary',   'var' => '--color-text-primary',   'hex' => '#1A1A1A'],
    ['name' => 'Secondary', 'var' => '--color-text-secondary', 'hex' => '#5A5A5A'],
    ['name' => 'Muted',     'var' => '--color-text-muted',     'hex' => '#888888'],
    ['name' => 'Inverse',   'var' => '--color-text-inverse',   'hex' => '#FFFFFF'],
]);

// --- Borders ---
ds_render_swatches('Borders', [
    ['name' => 'Border',        'var' => '--color-border',        'hex' => '#E7E5E4'],
    ['name' => 'Border Strong', 'var' => '--color-border-strong', 'hex' => '#D6D1C6'],
]);

// --- Accent ---
ds_render_swatches('Accent', [
    ['name' => 'Accent',       'var' => '--color-accent',       'hex' => '#FF8B5E'],
    ['name' => 'Accent Hover', 'var' => '--color-accent-hover', 'hex' => '#FF7A47'],
    ['name' => 'Accent Light', 'var' => '--color-accent-light', 'hex' => '#FFF3ED'],
]);

// --- Status ---
ds_render_swatches('Status', [
    ['name' => 'Success', 'var' => '--color-success', 'hex' => '#16A34A'],
    ['name' => 'Warning', 'var' => '--color-warning', 'hex' => '#D97706'],
    ['name' => 'Error',   'var' => '--color-error',   'hex' => '#DC2626'],
    ['name' => 'Info',    'var' => '--color-info',     'hex' => '#2563EB'],
]);

// --- Temagrupper ---
ds_render_swatches('Temagrupper', [
    ['name' => 'BIMtech',               'var' => '--color-temagruppe-bimtech',              'hex' => '#3B82F6'],
    ['name' => 'Gronn BIM',             'var' => '--color-temagruppe-gronn-bim',            'hex' => '#22C55E'],
    ['name' => 'Digital samhandling',    'var' => '--color-temagruppe-digital-samhandling',  'hex' => '#A855F7'],
    ['name' => 'BIM i forvaltning',     'var' => '--color-temagruppe-forvaltning',          'hex' => '#14B8A6'],
    ['name' => 'Baerekraft',            'var' => '--color-temagruppe-baerekraft',           'hex' => '#F59E0B'],
    ['name' => 'Standard',              'var' => '--color-temagruppe-standard',             'hex' => '#FF8B5E'],
]);

?>
