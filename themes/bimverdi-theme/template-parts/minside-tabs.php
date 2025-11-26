<?php
/**
 * Min Side - Horizontal Tab Navigation using Web Awesome
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
        'label' => 'Dashboard',
        'icon' => 'house',
        'url' => home_url('/min-side/'),
        'description' => 'Oversikt og hurtigkoblinger',
    ),
    'rediger' => array(
        'label' => 'Profil',
        'icon' => 'user-pen',
        'url' => home_url('/rediger-bruker/'),
        'description' => 'Oppdater din personlige informasjon',
    ),
    'foretak' => array(
        'label' => 'Foretak',
        'icon' => 'building',
        'url' => home_url('/koble-foretak/'),
        'description' => 'Se eller endre foretak-tilknytning',
    ),
    'verktoy' => array(
        'label' => 'Verktøy',
        'icon' => 'wrench',
        'url' => home_url('/min-side/mine-verktoy/'),
        'description' => 'Se og del verktøy',
    ),
    'temagrupper' => array(
        'label' => 'Temagrupper',
        'icon' => 'layer-group',
        'url' => home_url('/min-side/temagrupper/'),
        'description' => 'Velg temagruppemedimedlemskap',
    ),
    'arrangementer' => array(
        'label' => 'Arrangementer',
        'icon' => 'calendar-days',
        'url' => home_url('/min-side/arrangementer/'),
        'description' => 'Se og registrer deg på arrangementer',
    ),
);
?>

<div class="bg-white sticky top-16 z-30 border-b border-gray-200">
    <div class="container mx-auto px-4">
        <wa-tab-group class="minside-tabs">
            <?php foreach ($tabs as $tab_key => $tab): ?>
                <?php 
                $is_active = ($current_tab === $tab_key);
                ?>
                <wa-tab 
                    slot="nav" 
                    panel="<?php echo esc_attr($tab_key); ?>"
                    <?php echo $is_active ? 'active' : ''; ?>
                    onclick="window.location.href='<?php echo esc_url($tab['url']); ?>'"
                    style="cursor: pointer;">
                    <wa-icon slot="prefix" name="<?php echo esc_attr($tab['icon']); ?>" library="fa"></wa-icon>
                    <?php echo esc_html($tab['label']); ?>
                </wa-tab>
            <?php endforeach; ?>
        </wa-tab-group>
    </div>
</div>

<style>
.minside-tabs {
    --indicator-color: var(--wa-color-brand-600);
}
.minside-tabs wa-tab {
    padding: 1rem 1.25rem;
    font-weight: 500;
}
.minside-tabs wa-tab[active] {
    color: var(--wa-color-brand-600);
}
.minside-tabs wa-tab::part(base):hover {
    color: var(--wa-color-brand-600);
}
</style>
