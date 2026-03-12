<?php
/**
 * BIMVerdi Avatar Component (shadcn-inspired)
 *
 * Circular avatar with image, fallback initials, status badge, and group support.
 *
 * Usage:
 *
 * // Image avatar
 * <?php bimverdi_avatar(['src' => '/path/to/image.jpg', 'alt' => 'Andreas Harstad']); ?>
 *
 * // Initials fallback
 * <?php bimverdi_avatar(['initials' => 'AH', 'alt' => 'Andreas Harstad']); ?>
 *
 * // With status badge
 * <?php bimverdi_avatar(['initials' => 'AH', 'badge' => 'online']); ?>
 *
 * // Avatar group
 * <?php bimverdi_avatar_group([
 *     ['src' => '/img/1.jpg', 'alt' => 'User 1'],
 *     ['initials' => 'AH'],
 *     ['initials' => 'BK'],
 * ], ['max' => 3]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render an avatar component
 *
 * @param array $args Avatar configuration
 *   - src (string) Image URL
 *   - alt (string) Alt text for image / tooltip
 *   - initials (string) Fallback initials (1-2 chars)
 *   - size (string) 'sm' | 'default' | 'lg'
 *   - badge (string) Status indicator: 'online' | 'offline' | 'busy' | 'away' | '' (none)
 *   - color (string) Background color for initials (default #18181B)
 *   - class (string) Additional CSS classes
 *   - echo (bool) Whether to echo (true) or return HTML (false). Default true.
 * @return string|void HTML string if echo is false
 */
function bimverdi_avatar($args = []) {
    $defaults = [
        'src'      => '',
        'alt'      => '',
        'initials' => '',
        'size'     => 'default',
        'badge'    => '',
        'color'    => '#18181B',
        'class'    => '',
        'echo'     => true,
    ];

    $args = wp_parse_args($args, $defaults);

    $wrapper_class = 'bv-avatar';
    if ($args['size'] !== 'default') $wrapper_class .= ' bv-avatar--' . $args['size'];
    if ($args['badge']) $wrapper_class .= ' bv-avatar--has-badge';
    if ($args['class']) $wrapper_class .= ' ' . $args['class'];

    ob_start();
    ?>
    <span class="<?php echo esc_attr($wrapper_class); ?>" <?php if ($args['alt']): ?>title="<?php echo esc_attr($args['alt']); ?>"<?php endif; ?>>
        <?php if ($args['src']): ?>
            <img class="bv-avatar__image" src="<?php echo esc_url($args['src']); ?>" alt="<?php echo esc_attr($args['alt']); ?>" />
        <?php else: ?>
            <span class="bv-avatar__fallback" style="background: <?php echo esc_attr($args['color']); ?>;">
                <?php echo esc_html($args['initials']); ?>
            </span>
        <?php endif; ?>
        <?php if ($args['badge']): ?>
            <span class="bv-avatar__badge bv-avatar__badge--<?php echo esc_attr($args['badge']); ?>" aria-label="<?php echo esc_attr($args['badge']); ?>"></span>
        <?php endif; ?>
    </span>
    <?php
    $html = ob_get_clean();

    if ($args['echo']) {
        echo $html;
    } else {
        return $html;
    }
}

/**
 * Render an avatar group (overlapping avatars with optional overflow count)
 *
 * @param array $avatars Array of avatar args (each passed to bimverdi_avatar)
 * @param array $group_args Group configuration
 *   - max (int) Max avatars to show before "+N" (default 4)
 *   - size (string) Size applied to all avatars (default 'default')
 *   - class (string) Additional CSS classes for group wrapper
 * @return void
 */
function bimverdi_avatar_group($avatars = [], $group_args = []) {
    $defaults = [
        'max'   => 4,
        'size'  => 'default',
        'class' => '',
    ];

    $group_args = wp_parse_args($group_args, $defaults);

    $total = count($avatars);
    $max = $group_args['max'];
    $visible = array_slice($avatars, 0, $max);
    $overflow = $total - $max;

    $wrapper_class = 'bv-avatar-group';
    if ($group_args['class']) $wrapper_class .= ' ' . $group_args['class'];

    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>">
        <?php foreach ($visible as $avatar): ?>
            <?php
            $avatar['size'] = $group_args['size'];
            $avatar['echo'] = true;
            bimverdi_avatar($avatar);
            ?>
        <?php endforeach; ?>
        <?php if ($overflow > 0): ?>
            <span class="bv-avatar bv-avatar--overflow <?php echo $group_args['size'] !== 'default' ? 'bv-avatar--' . esc_attr($group_args['size']) : ''; ?>">
                <span class="bv-avatar__fallback bv-avatar__fallback--overflow">+<?php echo (int) $overflow; ?></span>
            </span>
        <?php endif; ?>
    </div>
    <?php
}
