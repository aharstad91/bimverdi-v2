<?php
/**
 * Temagruppe Kunnskapskilder Grid
 *
 * Full-width grid of knowledge sources associated with this theme group.
 * Dashboard-style card layout for the theme group page.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type WP_Term|false $temagruppe_term The taxonomy term for this temagruppe
 * }
 */

if (!defined('ABSPATH')) exit;

$temagruppe_term = $args['temagruppe_term'] ?? null;
$max_visible = 6;

// Query knowledge sources
$kunnskapskilder = [];
$total_count = 0;
if ($temagruppe_term) {
    $query = new WP_Query([
        'post_type' => 'kunnskapskilde',
        'posts_per_page' => $max_visible,
        'orderby' => 'title',
        'order' => 'ASC',
        'tax_query' => [
            [
                'taxonomy' => 'temagruppe',
                'field' => 'term_id',
                'terms' => $temagruppe_term->term_id,
            ],
        ],
    ]);

    if ($query->have_posts()) {
        $kunnskapskilder = $query->posts;
        $total_count = $query->found_posts;
    }
    wp_reset_postdata();
}

// Kildetype labels and icons
$kildetype_config = [
    'standard' => ['label' => 'Standard', 'icon' => 'file-badge'],
    'veileder' => ['label' => 'Veileder', 'icon' => 'book-open'],
    'mal' => ['label' => 'Mal', 'icon' => 'file-text'],
    'forskningsrapport' => ['label' => 'Forskning', 'icon' => 'microscope'],
    'casestudie' => ['label' => 'Case', 'icon' => 'briefcase'],
    'opplaering' => ['label' => 'Opplæring', 'icon' => 'graduation-cap'],
    'dokumentasjon' => ['label' => 'Dokumentasjon', 'icon' => 'file-code'],
    'nettressurs' => ['label' => 'Nettressurs', 'icon' => 'globe'],
    'annet' => ['label' => 'Annet', 'icon' => 'file'],
];

// If no items, don't render section
if (empty($kunnskapskilder)) {
    return;
}
?>

<section>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-[#1A1A1A]">
            Kunnskapskilder
            <?php if ($total_count > 0) : ?>
            <span class="text-base font-normal text-[#5A5A5A]">(<?php echo esc_html($total_count); ?>)</span>
            <?php endif; ?>
        </h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($kunnskapskilder as $kilde) :
            $kilde_id = $kilde->ID;
            $kort_beskrivelse = get_field('kort_beskrivelse', $kilde_id);
            $ekstern_lenke = get_field('ekstern_lenke', $kilde_id);
            $utgiver = get_field('utgiver', $kilde_id);
            $kildetype = get_field('kildetype', $kilde_id);
            $utgivelsesaar = get_field('utgivelsesaar', $kilde_id);

            // Get kategori
            $kategori_terms = wp_get_post_terms($kilde_id, 'kunnskapskildekategori', ['fields' => 'names']);
            $kategori = !empty($kategori_terms) ? $kategori_terms[0] : '';

            // Determine type config
            $type_config = $kildetype_config[$kildetype] ?? $kildetype_config['annet'];

            // Determine link
            $resource_url = $ekstern_lenke ?: get_permalink($kilde_id);
            $is_external = !empty($ekstern_lenke);
        ?>
        <article class="bg-white rounded-lg border border-[#E5E0D8] p-5 flex flex-col">
            <!-- Type Badge -->
            <div class="mb-3">
                <?php if ($kategori) : ?>
                <span class="px-2 py-1 bg-gray-100 rounded text-xs font-medium text-[#5A5A5A]">
                    <?php echo esc_html($kategori); ?>
                </span>
                <?php elseif ($kildetype) : ?>
                <span class="px-2 py-1 bg-gray-100 rounded text-xs font-medium text-[#5A5A5A]">
                    <?php echo esc_html($type_config['label']); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Title -->
            <h3 class="text-base font-semibold text-[#1A1A1A] mb-2 line-clamp-2">
                <?php echo esc_html($kilde->post_title); ?>
            </h3>

            <!-- Description -->
            <?php if ($kort_beskrivelse) : ?>
            <p class="text-sm text-[#5A5A5A] mb-3 line-clamp-2 flex-1">
                <?php echo esc_html($kort_beskrivelse); ?>
            </p>
            <?php else : ?>
            <div class="flex-1"></div>
            <?php endif; ?>

            <!-- Meta: Publisher + Year -->
            <div class="space-y-1 mb-4">
                <?php if ($utgiver) : ?>
                <p class="text-xs text-[#5A5A5A]">
                    <?php echo esc_html($utgiver); ?>
                    <?php if ($utgivelsesaar) : ?>
                    (<?php echo esc_html($utgivelsesaar); ?>)
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="pt-4 border-t border-[#E5E0D8]">
                <a
                    href="<?php echo esc_url($resource_url); ?>"
                    <?php if ($is_external) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                    class="text-sm font-medium text-[#5A5A5A] hover:text-[#FF8B5E] inline-flex items-center gap-1"
                >
                    <?php if ($is_external) : ?>
                    Apne ressurs
                    <?php echo bimverdi_icon('external-link', 14); ?>
                    <?php else : ?>
                    Se detaljer
                    <?php echo bimverdi_icon('chevron-right', 14); ?>
                    <?php endif; ?>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- See all link -->
    <?php if ($total_count > $max_visible) : ?>
    <div class="mt-6 text-center">
        <a
            href="<?php echo esc_url(home_url('/kunnskapskilde/')); ?>"
            class="text-sm text-[#FF8B5E] hover:underline inline-flex items-center gap-1"
        >
            Se alle <?php echo esc_html($total_count); ?> kunnskapskilder
            <?php echo bimverdi_icon('arrow-right', 14); ?>
        </a>
    </div>
    <?php endif; ?>
</section>
