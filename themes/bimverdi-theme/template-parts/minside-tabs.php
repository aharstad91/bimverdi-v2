<?php
/**
 * Min Side - Horizontal Tab Navigation
 * 
 * Displays horizontal tab navigation for Min Side pages
 * 
 * @param string $current_tab The current active tab (dashboard, profil, temagrupper, arrangementer)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the current_tab from args (passed via get_template_part)
$current_tab = isset($args['current_tab']) ? $args['current_tab'] : 'dashboard';

$tabs = array(
    'dashboard' => array(
        'label' => '🏠 Dashboard',
        'url' => home_url('/min-side/'),
        'description' => 'Oversikt og hurtigkoblinger',
    ),
    'rediger' => array(
        'label' => '✏️ Rediger Profil',
        'url' => home_url('/rediger-bruker/'),
        'description' => 'Oppdater din personlige informasjon',
    ),
    'foretak' => array(
        'label' => '🏢 Foretak',
        'url' => home_url('/koble-foretak/'),
        'description' => 'Se eller endre foretak-tilknytning',
    ),
    'verktoy' => array(
        'label' => '🛠️ Verktøy',
        'url' => home_url('/min-side/mine-verktoy/'),
        'description' => 'Se og del verktøy',
    ),
    'temagrupper' => array(
        'label' => '🎯 Temagrupper',
        'url' => home_url('/min-side/temagrupper/'),
        'description' => 'Velg temagruppemedimedlemskap',
    ),
    'arrangementer' => array(
        'label' => '📅 Arrangementer',
        'url' => home_url('/min-side/arrangementer/'),
        'description' => 'Se og registrer deg på arrangementer',
    ),
);
?>

<div class="bg-white sticky top-16 z-30 shadow-md">
    <div class="container mx-auto px-4">
        <!-- Tabs Navigation using daisyUI -->
        <div role="tablist" class="tabs tabs-bordered justify-start">
            <?php foreach ($tabs as $tab_key => $tab): ?>
                <?php 
                $is_active = ($current_tab === $tab_key);
                $active_class = $is_active ? 'tab-active' : '';
                $aria_selected = $is_active ? 'true' : 'false';
                ?>
                <a href="<?php echo esc_url($tab['url']); ?>" 
                   role="tab" 
                   class="tab <?php echo $active_class; ?> flex-none"
                   aria-selected="<?php echo $aria_selected; ?>"
                   title="<?php echo esc_attr($tab['description']); ?>">
                    <?php echo esc_html($tab['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
