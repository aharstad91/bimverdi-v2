<?php
/**
 * Hero Section Template Part
 * 
 * Displays a full-width hero section with title, subtitle, and CTA
 * 
 * @param array $args {
 *     @type string $title Hero title (required)
 *     @type string $subtitle Hero subtitle
 *     @type string $background_image Background image URL
 *     @type bool $overlay Show dark overlay (default: true)
 *     @type array $buttons Array of button data
 *     @type string $height Height class (default: 'min-h-96')
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default arguments
$defaults = array(
    'title' => '',
    'subtitle' => '',
    'background_image' => '',
    'overlay' => true,
    'buttons' => array(),
    'height' => 'min-h-96',
);

// Merge provided arguments
$args = isset($args) ? array_merge($defaults, $args) : $defaults;

// Extract variables
extract($args);
?>

<section class="hero <?php echo esc_attr($height); ?> bg-gradient-to-r from-bim-orange to-bim-purple relative overflow-hidden"
         <?php if ($background_image): ?>
             style="background-image: url('<?php echo esc_url($background_image); ?>'); background-size: cover; background-position: center;"
         <?php endif; ?>>
    
    <!-- Overlay -->
    <?php if ($overlay): ?>
        <div class="absolute inset-0 bg-black opacity-40"></div>
    <?php endif; ?>
    
    <!-- Content -->
    <div class="hero-content text-center text-white relative z-10">
        <div class="max-w-2xl">
            
            <!-- Title -->
            <?php if ($title): ?>
                <h1 class="mb-5 text-5xl font-bold leading-tight">
                    <?php echo esc_html($title); ?>
                </h1>
            <?php endif; ?>
            
            <!-- Subtitle -->
            <?php if ($subtitle): ?>
                <p class="mb-8 text-xl leading-relaxed">
                    <?php echo esc_html($subtitle); ?>
                </p>
            <?php endif; ?>
            
            <!-- Buttons -->
            <?php if (!empty($buttons)): ?>
                <div class="flex gap-4 justify-center flex-wrap">
                    <?php foreach ($buttons as $button): ?>
                        <a href="<?php echo esc_url($button['url'] ?? '#'); ?>" 
                           class="btn btn-lg <?php echo isset($button['class']) ? esc_attr($button['class']) : 'btn-primary'; ?>">
                            <?php echo esc_html($button['label'] ?? 'Button'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<style>
    .hero {
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
