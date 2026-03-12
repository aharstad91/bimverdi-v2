<?php
/**
 * BIMVerdi Empty State Component (shadcn-inspired)
 *
 * Displays a centered empty state with icon, title, description, and optional action.
 *
 * Can be used two ways:
 *
 * 1. Function call:
 * <?php bimverdi_empty_state([
 *     'icon'        => 'inbox',
 *     'title'       => 'No results found',
 *     'description' => 'Try adjusting your search or filters.',
 *     'action'      => ['text' => 'Add item', 'href' => '#', 'icon' => 'plus'],
 * ]); ?>
 *
 * 2. Template part (backward compat):
 * get_template_part('parts/components/empty-state', null, [
 *     'icon'        => 'wrench',
 *     'title'       => 'Ingen verktøy',
 *     'description' => 'Du har ikke registrert noen verktøy ennå.',
 *     'cta_text'    => 'Registrer verktøy',
 *     'cta_url'     => '/min-side/registrer-verktoy/',
 * ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render an empty state component
 *
 * @param array $args Empty state configuration
 *   - icon (string) Lucide icon name
 *   - title (string) Heading text
 *   - description (string) Supporting text
 *   - action (array) CTA button args — passed to bimverdi_button()
 *   - variant (string) 'default' | 'outline' | 'compact'
 *   - class (string) Additional CSS classes
 * @return void
 */
function bimverdi_empty_state($args = []) {
    $defaults = [
        'icon'        => '',
        'title'       => '',
        'description' => '',
        'action'      => [],
        'variant'     => 'default',
        'class'       => '',
    ];

    $args = wp_parse_args($args, $defaults);

    $wrapper_class = 'bv-empty';
    if ($args['variant'] === 'outline') $wrapper_class .= ' bv-empty--outline';
    if ($args['variant'] === 'compact') $wrapper_class .= ' bv-empty--compact';
    if ($args['class']) $wrapper_class .= ' ' . $args['class'];

    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>">
        <?php if ($args['variant'] === 'compact'): ?>
            <?php if ($args['icon']): ?>
                <div class="bv-empty__icon">
                    <?php echo bimverdi_icon($args['icon'], 18); ?>
                </div>
            <?php endif; ?>
            <div class="bv-empty__body">
                <?php if ($args['title']): ?>
                    <div class="bv-empty__title"><?php echo esc_html($args['title']); ?></div>
                <?php endif; ?>
                <?php if ($args['description']): ?>
                    <p class="bv-empty__description"><?php echo esc_html($args['description']); ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($args['icon']): ?>
                <div class="bv-empty__icon">
                    <?php echo bimverdi_icon($args['icon'], 24); ?>
                </div>
            <?php endif; ?>
            <?php if ($args['title']): ?>
                <div class="bv-empty__title"><?php echo esc_html($args['title']); ?></div>
            <?php endif; ?>
            <?php if ($args['description']): ?>
                <p class="bv-empty__description"><?php echo esc_html($args['description']); ?></p>
            <?php endif; ?>
            <?php if (!empty($args['action'])): ?>
                <div class="bv-empty__actions">
                    <?php bimverdi_button($args['action']); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

// Template part backward compat: if loaded via get_template_part with $args
if (!empty($args) && is_array($args)) {
    $icon = $args['icon'] ?? 'inbox';
    $title = $args['title'] ?? __('Ingen elementer ennå', 'bimverdi');
    $description = $args['description'] ?? '';
    $cta_text = $args['cta_text'] ?? null;
    $cta_url = $args['cta_url'] ?? null;

    $action = [];
    if ($cta_text && $cta_url) {
        $action = [
            'text'    => $cta_text,
            'variant' => 'default',
            'href'    => home_url($cta_url),
            'icon'    => $args['cta_icon'] ?? null,
        ];
    }

    bimverdi_empty_state([
        'icon'        => $icon,
        'title'       => $title,
        'description' => $description,
        'action'      => $action,
    ]);
}
