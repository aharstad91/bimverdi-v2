<?php
/**
 * BIMVerdi Alert Component (shadcn-inspired)
 *
 * Displays a callout for important information, warnings, or errors.
 *
 * Variants:
 * - default: Subtle background with dark text
 * - destructive: Red/error styling
 * - success: Green/success styling
 * - warning: Amber/warning styling
 *
 * Usage:
 *
 * <?php bimverdi_alert(['title' => 'Heads up!', 'description' => 'You can add components.']); ?>
 * <?php bimverdi_alert(['title' => 'Error', 'description' => 'Something went wrong.', 'variant' => 'destructive']); ?>
 * <?php bimverdi_alert(['title' => 'Success!', 'description' => 'Account activated.', 'variant' => 'success', 'icon' => 'check-circle']); ?>
 * <?php bimverdi_alert(['description' => 'Simple message without title.']); ?>
 *
 * // Return HTML instead of echoing
 * $html = bimverdi_alert(['title' => 'Info', 'description' => 'Note this.', 'echo' => false]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render an alert component
 *
 * @param array $args Alert configuration
 *   - title (string) Alert title (optional)
 *   - description (string) Alert description/body text
 *   - variant (string) 'default' | 'destructive' | 'success' | 'warning'
 *   - icon (string) Lucide icon name (optional, auto-set per variant if omitted)
 *   - dismissible (bool) Show close button (default false)
 *   - class (string) Additional CSS classes
 *   - echo (bool) Echo or return HTML (default true)
 * @return string|void HTML string if echo is false
 */
function bimverdi_alert($args = []) {
    $defaults = [
        'title'       => '',
        'description' => '',
        'variant'     => 'default',
        'icon'        => null,
        'dismissible' => false,
        'class'       => '',
        'echo'        => true,
    ];

    $args = wp_parse_args($args, $defaults);

    // Auto-assign icons per variant if not explicitly set
    $default_icons = [
        'default'     => 'info',
        'destructive' => 'alert-circle',
        'success'     => 'check-circle',
        'warning'     => 'alert-triangle',
    ];

    $icon = $args['icon'];
    if ($icon === null && isset($default_icons[$args['variant']])) {
        $icon = $default_icons[$args['variant']];
    }

    // Build CSS classes
    $classes = ['bv-alert'];
    $classes[] = 'bv-alert--' . $args['variant'];

    if (empty($args['title'])) {
        $classes[] = 'bv-alert--no-title';
    }

    if ($args['class']) {
        $classes[] = $args['class'];
    }

    $class_string = implode(' ', $classes);

    ob_start();
    ?>
    <div class="<?php echo esc_attr($class_string); ?>" role="alert">
        <?php if ($icon): ?>
            <span class="bv-alert__icon"><?php echo bimverdi_get_icon_svg($icon, 16); ?></span>
        <?php endif; ?>

        <div class="bv-alert__content">
            <?php if ($args['title']): ?>
                <h5 class="bv-alert__title"><?php echo esc_html($args['title']); ?></h5>
            <?php endif; ?>

            <?php if ($args['description']): ?>
                <div class="bv-alert__description"><?php echo wp_kses_post($args['description']); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($args['dismissible']): ?>
            <button type="button" class="bv-alert__close" aria-label="<?php esc_attr_e('Lukk', 'bimverdi'); ?>">
                <?php echo bimverdi_get_icon_svg('x', 16); ?>
            </button>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();

    if ($args['echo']) {
        echo $html;
    } else {
        return $html;
    }
}
