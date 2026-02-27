<?php
/**
 * Tag Cloud Component
 *
 * Renders taxonomy terms or meta filter values as animated pill-tags.
 * Supports clickable mode (triggers filter-bar checkboxes) and decorative mode.
 *
 * Usage:
 *   get_template_part('parts/components/tag-cloud', null, [
 *       'taxonomies' => [
 *           ['taxonomy' => 'temagruppe', 'filter_class' => 'filter-temagruppe'],
 *           ['taxonomy' => 'kunnskapskildekategori', 'filter_class' => 'filter-kategori'],
 *       ],
 *       'meta_filters' => [
 *           ['options' => ['key' => 'Label', ...], 'filter_class' => 'filter-formaal'],
 *       ],
 *       'max_tags' => 20,
 *   ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$taxonomies   = $args['taxonomies'] ?? [];
$meta_filters = $args['meta_filters'] ?? [];
$max_tags     = $args['max_tags'] ?? 12;

// Collect all tags: ['label' => string, 'filter_class' => string|null, 'filter_value' => string]
$tags = [];

// Get taxonomy terms
foreach ($taxonomies as $tax_config) {
    $taxonomy     = $tax_config['taxonomy'] ?? '';
    $filter_class = $tax_config['filter_class'] ?? null;

    if (!$taxonomy) {
        continue;
    }

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        continue;
    }

    foreach ($terms as $term) {
        $tags[] = [
            'label'        => $term->name,
            'filter_class' => $filter_class,
            'filter_value' => $term->slug,
        ];
    }
}

// Get meta filter options
foreach ($meta_filters as $meta_config) {
    $options      = $meta_config['options'] ?? [];
    $filter_class = $meta_config['filter_class'] ?? null;

    foreach ($options as $value => $label) {
        $tags[] = [
            'label'        => $label,
            'filter_class' => $filter_class,
            'filter_value' => $value,
        ];
    }
}

// Nothing to render
if (empty($tags)) {
    return;
}

// Shuffle with date-based seed for daily variation but consistent within a day
$seed = crc32(date('Y-m-d'));
mt_srand($seed);
usort($tags, function () {
    return mt_rand(-1, 1);
});
mt_srand(); // Reset seed

// Limit tags
$tags = array_slice($tags, 0, $max_tags);

// Pre-compute rotation angles (-2 to 2 degrees)
$rotations = [-2, -1.5, -1, -0.5, 0, 0.5, 1, 1.5, 2];
?>

<div class="bv-tag-cloud" aria-label="Emneord">
    <?php foreach ($tags as $index => $tag):
        $rotation = $rotations[$index % count($rotations)];
        $delay    = $index * 0.04; // staggered fade-in
        $is_clickable = !empty($tag['filter_class']);
        $tag_element  = $is_clickable ? 'button' : 'span';
    ?>
        <<?php echo $tag_element; ?>
            class="bv-tag-cloud__tag"
            style="--tag-rotate: <?php echo esc_attr($rotation); ?>deg; --tag-delay: <?php echo esc_attr($delay); ?>s;"
            <?php if ($is_clickable): ?>
                type="button"
                data-filter-class="<?php echo esc_attr($tag['filter_class']); ?>"
                data-filter-value="<?php echo esc_attr($tag['filter_value']); ?>"
                title="Filtrer på <?php echo esc_attr($tag['label']); ?>"
            <?php endif; ?>
        ><?php echo esc_html($tag['label']); ?></<?php echo $tag_element; ?>>
    <?php endforeach; ?>
</div>

<?php
// Output JS only once per page
static $tag_cloud_js_output = false;
if (!$tag_cloud_js_output):
    $tag_cloud_js_output = true;
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.bv-tag-cloud__tag[data-filter-class]').forEach(function(tag) {
        tag.addEventListener('click', function() {
            var filterClass = this.dataset.filterClass;
            var filterValue = this.dataset.filterValue;
            var checkbox = document.querySelector('.' + CSS.escape(filterClass) + '[value="' + CSS.escape(filterValue) + '"]');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
});
</script>
<?php endif; ?>
