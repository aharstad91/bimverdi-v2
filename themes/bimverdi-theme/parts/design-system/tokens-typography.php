<?php
// Section: Typografi (Typography)
if (!defined('ABSPATH')) exit;
?>

<h2 class="ds-section__title">Typografi</h2>
<p class="ds-section__desc">Inter er primærfonten. Headings bruker lette vekter (300-500) med tett letter-spacing.</p>

<?php
$type_scale = [
    [
        'label'          => 'H1',
        'sample'         => 'Heading 1 — Den raske brune reven',
        'size'           => '2.5rem',
        'weight'         => '300',
        'line_height'    => '110%',
        'letter_spacing' => '-0.03em',
        'extra_style'    => '',
    ],
    [
        'label'          => 'H2',
        'sample'         => 'Heading 2 — Den raske brune reven',
        'size'           => '2rem',
        'weight'         => '300',
        'line_height'    => '115%',
        'letter_spacing' => '-0.02em',
        'extra_style'    => '',
    ],
    [
        'label'          => 'H3',
        'sample'         => 'Heading 3 — Den raske brune reven',
        'size'           => '1.5rem',
        'weight'         => '500',
        'line_height'    => '125%',
        'letter_spacing' => '-0.01em',
        'extra_style'    => '',
    ],
    [
        'label'          => 'H4',
        'sample'         => 'Heading 4 — Den raske brune reven',
        'size'           => '1.25rem',
        'weight'         => '500',
        'line_height'    => '130%',
        'letter_spacing' => '-0.01em',
        'extra_style'    => '',
    ],
    [
        'label'          => 'H5',
        'sample'         => 'Heading 5 — Den raske brune reven',
        'size'           => '1.125rem',
        'weight'         => '600',
        'line_height'    => '135%',
        'letter_spacing' => '0',
        'extra_style'    => '',
    ],
    [
        'label'          => 'H6',
        'sample'         => 'Heading 6 — Den raske brune reven',
        'size'           => '1rem',
        'weight'         => '600',
        'line_height'    => '140%',
        'letter_spacing' => '0',
        'extra_style'    => '',
    ],
    [
        'label'          => 'Body',
        'sample'         => 'Body — Den raske brune reven hoppet over den late hunden.',
        'size'           => '1rem',
        'weight'         => '400',
        'line_height'    => '155%',
        'letter_spacing' => '0',
        'extra_style'    => '',
    ],
    [
        'label'          => 'Small',
        'sample'         => 'Small — Den raske brune reven hoppet over den late hunden.',
        'size'           => '0.875rem',
        'weight'         => '400',
        'line_height'    => '145%',
        'letter_spacing' => '0',
        'extra_style'    => '',
    ],
    [
        'label'          => 'Caption',
        'sample'         => 'Caption — Den raske brune reven hoppet over den late hunden.',
        'size'           => '0.75rem',
        'weight'         => '500',
        'line_height'    => '140%',
        'letter_spacing' => '0.01em',
        'extra_style'    => '',
    ],
    [
        'label'          => 'Eyebrow',
        'sample'         => 'Eyebrow — Den raske brune reven',
        'size'           => '0.75rem',
        'weight'         => '600',
        'line_height'    => '140%',
        'letter_spacing' => '0.05em',
        'extra_style'    => 'text-transform: uppercase;',
    ],
];

foreach ($type_scale as $item) :
    $ls_display = $item['letter_spacing'] === '0' ? '0' : $item['letter_spacing'];
?>
    <div style="padding: 24px 0; border-bottom: 1px solid #E7E5E4;">
        <div style="font-size: <?php echo esc_attr($item['size']); ?>; font-weight: <?php echo esc_attr($item['weight']); ?>; line-height: <?php echo esc_attr($item['line_height']); ?>; letter-spacing: <?php echo esc_attr($item['letter_spacing']); ?>; color: #1A1A1A; margin-bottom: 8px; <?php echo $item['extra_style']; ?>">
            <?php echo esc_html($item['sample']); ?>
        </div>
        <div style="font-size: 12px; color: #888; font-family: monospace;">
            <?php echo esc_html($item['label']); ?> · <?php echo esc_html($item['size']); ?> · weight <?php echo esc_html($item['weight']); ?> · line-height <?php echo esc_html($item['line_height']); ?> · letter-spacing <?php echo esc_html($ls_display); ?>
        </div>
    </div>
<?php endforeach; ?>
