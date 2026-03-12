<?php
/**
 * BIMVerdi Field Component (shadcn-inspired)
 *
 * Wraps form inputs with label, description, and error message.
 *
 * Usage:
 *
 * // Text input
 * <?php bimverdi_field([
 *     'label' => 'Navn',
 *     'name'  => 'name',
 *     'placeholder' => 'Ola Nordmann',
 * ]); ?>
 *
 * // With description
 * <?php bimverdi_field([
 *     'label' => 'E-post',
 *     'name'  => 'email',
 *     'type'  => 'email',
 *     'description' => 'Vi deler aldri e-posten din.',
 * ]); ?>
 *
 * // Textarea
 * <?php bimverdi_field([
 *     'label' => 'Kommentar',
 *     'name'  => 'comment',
 *     'type'  => 'textarea',
 *     'placeholder' => 'Skriv en kommentar...',
 * ]); ?>
 *
 * // Select
 * <?php bimverdi_field([
 *     'label' => 'Rolle',
 *     'name'  => 'role',
 *     'type'  => 'select',
 *     'placeholder' => 'Velg rolle',
 *     'options' => [
 *         'admin' => 'Administrator',
 *         'user'  => 'Bruker',
 *     ],
 * ]); ?>
 *
 * // Checkbox
 * <?php bimverdi_field([
 *     'label' => 'Samme som leveringsadresse',
 *     'name'  => 'same_address',
 *     'type'  => 'checkbox',
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render a form field with label, input, description, and error
 *
 * @param array $args Field configuration
 *   - label (string) Field label
 *   - name (string) Input name attribute
 *   - type (string) 'text' | 'email' | 'password' | 'number' | 'tel' | 'date' | 'url' | 'textarea' | 'select' | 'checkbox'
 *   - value (string) Current value
 *   - placeholder (string) Placeholder text
 *   - description (string) Help text below input
 *   - error (string) Error message
 *   - required (bool) Whether field is required
 *   - disabled (bool) Whether field is disabled
 *   - id (string) Custom ID (defaults to name)
 *   - class (string) Additional CSS classes on wrapper
 *   - input_class (string) Additional CSS classes on input
 *   - options (array) Key-value pairs for select type
 *   - rows (int) Rows for textarea (default 4)
 *   - checked (bool) For checkbox type
 * @return void
 */
function bimverdi_field($args = []) {
    $defaults = [
        'label'       => '',
        'name'        => '',
        'type'        => 'text',
        'value'       => '',
        'placeholder' => '',
        'description' => '',
        'error'       => '',
        'required'    => false,
        'disabled'    => false,
        'id'          => '',
        'class'       => '',
        'input_class' => '',
        'options'     => [],
        'rows'        => 4,
        'checked'     => false,
    ];

    $args = wp_parse_args($args, $defaults);

    $id = $args['id'] ?: $args['name'];
    $has_error = !empty($args['error']);

    // Wrapper classes
    $wrapper_class = 'bv-field';
    if ($has_error) $wrapper_class .= ' bv-field--error';
    if ($args['type'] === 'checkbox') $wrapper_class .= ' bv-field--checkbox';
    if ($args['class']) $wrapper_class .= ' ' . $args['class'];

    // Input classes
    $input_class = 'bv-field__input';
    if ($has_error) $input_class .= ' bv-field__input--error';
    if ($args['input_class']) $input_class .= ' ' . $args['input_class'];

    // Common input attributes
    $input_attrs = [];
    $input_attrs[] = 'id="' . esc_attr($id) . '"';
    $input_attrs[] = 'name="' . esc_attr($args['name']) . '"';
    if ($args['placeholder']) $input_attrs[] = 'placeholder="' . esc_attr($args['placeholder']) . '"';
    if ($args['required']) $input_attrs[] = 'required';
    if ($args['disabled']) $input_attrs[] = 'disabled';
    if ($has_error) $input_attrs[] = 'aria-invalid="true"';
    if ($args['description']) $input_attrs[] = 'aria-describedby="' . esc_attr($id) . '-desc"';

    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>">
        <?php if ($args['type'] === 'checkbox'): ?>
            <div class="bv-field__checkbox-row">
                <input
                    type="checkbox"
                    class="bv-field__checkbox"
                    <?php echo implode(' ', $input_attrs); ?>
                    <?php if ($args['value']) echo 'value="' . esc_attr($args['value']) . '"'; ?>
                    <?php if ($args['checked']) echo 'checked'; ?>
                >
                <?php if ($args['label']): ?>
                    <label for="<?php echo esc_attr($id); ?>" class="bv-field__checkbox-label"><?php echo esc_html($args['label']); ?></label>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <?php if ($args['label']): ?>
                <label for="<?php echo esc_attr($id); ?>" class="bv-field__label">
                    <?php echo esc_html($args['label']); ?>
                    <?php if ($args['required']): ?><span class="bv-field__required" aria-hidden="true">*</span><?php endif; ?>
                </label>
            <?php endif; ?>

            <?php if ($args['type'] === 'textarea'): ?>
                <textarea
                    class="<?php echo esc_attr($input_class); ?>"
                    rows="<?php echo (int) $args['rows']; ?>"
                    <?php echo implode(' ', $input_attrs); ?>
                ><?php echo esc_textarea($args['value']); ?></textarea>

            <?php elseif ($args['type'] === 'select'): ?>
                <select class="<?php echo esc_attr($input_class); ?>" <?php echo implode(' ', $input_attrs); ?>>
                    <?php if ($args['placeholder']): ?>
                        <option value="" disabled <?php if (!$args['value']) echo 'selected'; ?>><?php echo esc_html($args['placeholder']); ?></option>
                    <?php endif; ?>
                    <?php foreach ($args['options'] as $val => $label): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($args['value'], $val); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>

            <?php else: ?>
                <input
                    type="<?php echo esc_attr($args['type']); ?>"
                    class="<?php echo esc_attr($input_class); ?>"
                    value="<?php echo esc_attr($args['value']); ?>"
                    <?php echo implode(' ', $input_attrs); ?>
                >
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($args['description']): ?>
            <p class="bv-field__description" id="<?php echo esc_attr($id); ?>-desc"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>

        <?php if ($has_error): ?>
            <p class="bv-field__error" role="alert"><?php echo esc_html($args['error']); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render a field group (horizontal row of fields)
 *
 * @param string $legend Group legend/title
 * @param string $description Optional group description
 * @return void Outputs opening HTML — close with bimverdi_field_group_end()
 */
function bimverdi_field_group($legend = '', $description = '') {
    echo '<fieldset class="bv-field-group">';
    if ($legend) {
        echo '<legend class="bv-field-group__legend">' . esc_html($legend) . '</legend>';
    }
    if ($description) {
        echo '<p class="bv-field-group__description">' . esc_html($description) . '</p>';
    }
    echo '<div class="bv-field-group__fields">';
}

function bimverdi_field_group_end() {
    echo '</div></fieldset>';
}
