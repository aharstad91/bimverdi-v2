<?php
/**
 * BIMVerdi Switch Component (shadcn-inspired)
 *
 * A toggle control — pure CSS, no JS required.
 *
 * Usage:
 *
 * <?php bimverdi_switch([
 *     'label' => 'Airplane Mode',
 *     'name'  => 'airplane_mode',
 * ]); ?>
 *
 * <?php bimverdi_switch([
 *     'label'   => 'Notifications',
 *     'name'    => 'notifications',
 *     'checked' => true,
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a switch/toggle component
 *
 * @param array $args Switch configuration
 *   - label (string) Label text displayed next to the switch
 *   - name (string) Input name attribute
 *   - id (string) Custom ID (defaults to name)
 *   - checked (bool) Whether switch is on
 *   - disabled (bool) Whether switch is disabled
 *   - value (string) Input value when checked (default '1')
 *   - description (string) Help text below switch
 *   - class (string) Additional CSS classes on wrapper
 * @return void
 */
function bimverdi_switch($args = []) {
    $defaults = [
        'label'       => '',
        'name'        => '',
        'id'          => '',
        'checked'     => false,
        'disabled'    => false,
        'value'       => '1',
        'description' => '',
        'class'       => '',
    ];

    $args = wp_parse_args($args, $defaults);

    $id = $args['id'] ?: $args['name'];

    $wrapper_class = 'bv-switch';
    if ($args['disabled']) $wrapper_class .= ' bv-switch--disabled';
    if ($args['class']) $wrapper_class .= ' ' . $args['class'];

    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>">
        <div class="bv-switch__row">
            <label class="bv-switch__track" for="<?php echo esc_attr($id); ?>">
                <input
                    type="checkbox"
                    class="bv-switch__input"
                    id="<?php echo esc_attr($id); ?>"
                    name="<?php echo esc_attr($args['name']); ?>"
                    value="<?php echo esc_attr($args['value']); ?>"
                    <?php if ($args['checked']) echo 'checked'; ?>
                    <?php if ($args['disabled']) echo 'disabled'; ?>
                    role="switch"
                    aria-checked="<?php echo $args['checked'] ? 'true' : 'false'; ?>"
                >
                <span class="bv-switch__thumb"></span>
            </label>
            <?php if ($args['label']): ?>
                <label for="<?php echo esc_attr($id); ?>" class="bv-switch__label"><?php echo esc_html($args['label']); ?></label>
            <?php endif; ?>
        </div>
        <?php if ($args['description']): ?>
            <p class="bv-switch__description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
    </div>
    <?php
}
