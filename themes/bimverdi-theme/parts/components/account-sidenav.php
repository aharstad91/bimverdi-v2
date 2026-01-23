<?php
/**
 * Account Sidenav Component
 *
 * Displays navigation for profile and company settings pages.
 * Shows items based on user permissions.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$has_company = bimverdi_user_has_company($user_id);
$is_hovedkontakt = bimverdi_is_hovedkontakt($user_id);
$current_route = bimverdi_get_current_route();

// Build navigation structure
$nav_sections = [
    'profil' => [
        'label' => __('Profil', 'bimverdi'),
        'items' => [
            [
                'route' => 'profil',
                'label' => __('Min profil', 'bimverdi'),
                'icon' => 'user',
            ],
            [
                'route' => 'profil/rediger',
                'label' => __('Rediger profil', 'bimverdi'),
                'icon' => 'square-pen',
            ],
            [
                'route' => 'profil/passord',
                'label' => __('Endre passord', 'bimverdi'),
                'icon' => 'shield',
            ],
        ],
    ],
];

// Add foretak section if user has company
if ($has_company) {
    $foretak_items = [
        [
            'route' => 'foretak',
            'label' => __('Mitt foretak', 'bimverdi'),
            'icon' => 'building-2',
        ],
    ];

    // Add hovedkontakt-only items
    if ($is_hovedkontakt) {
        $foretak_items[] = [
            'route' => 'foretak/rediger',
            'label' => __('Rediger foretak', 'bimverdi'),
            'icon' => 'square-pen',
        ];
        $foretak_items[] = [
            'route' => 'foretak/team',
            'label' => __('Kolleger', 'bimverdi'),
            'icon' => 'users',
        ];
    }

    $nav_sections['foretak'] = [
        'label' => __('Foretak', 'bimverdi'),
        'items' => $foretak_items,
    ];
}

// Icon SVG definitions
$icons = [
    'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'square-pen' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"/></svg>',
    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
    'building-2' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>',
    'users' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
];

/**
 * Check if a route is active
 */
function bimverdi_is_account_route_active($route, $current_route) {
    // Exact match
    if ($route === $current_route) {
        return true;
    }
    // Handle dashboard/empty route
    if ($route === '' && ($current_route === 'dashboard' || $current_route === '')) {
        return true;
    }
    return false;
}
?>

<nav class="w-60 flex-shrink-0 hidden md:block" aria-label="<?php esc_attr_e('Kontoinnstillinger', 'bimverdi'); ?>">
    <?php $first_section = true; ?>
    <?php foreach ($nav_sections as $section_key => $section): ?>
        <div class="<?php echo $first_section ? '' : 'pt-6 mt-6 border-t border-[#D6D1C6]'; ?>">
            <h3 class="text-xs font-semibold text-[#5A5A5A] uppercase tracking-wider mb-3 px-3">
                <?php echo esc_html($section['label']); ?>
            </h3>
            <ul class="space-y-1">
                <?php foreach ($section['items'] as $item):
                    $is_active = bimverdi_is_account_route_active($item['route'], $current_route);
                    $url = bimverdi_minside_url($item['route']);
                    $icon_svg = $icons[$item['icon']] ?? '';
                ?>
                    <li>
                        <a href="<?php echo esc_url($url); ?>"
                           class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors <?php echo $is_active
                               ? 'bg-[#F7F5EF] text-[#1A1A1A] font-medium'
                               : 'text-[#5A5A5A] hover:bg-[#F7F5EF] hover:text-[#1A1A1A]'; ?>"
                           <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                            <span class="flex-shrink-0 <?php echo $is_active ? 'text-[#1A1A1A]' : 'text-[#888888]'; ?>">
                                <?php echo $icon_svg; ?>
                            </span>
                            <?php echo esc_html($item['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php $first_section = false; ?>
    <?php endforeach; ?>
</nav>

<!-- Mobile: Breadcrumb context (sidenav hidden) -->
<div class="md:hidden mb-6">
    <nav class="text-sm text-[#5A5A5A]" aria-label="<?php esc_attr_e('Navigasjon', 'bimverdi'); ?>">
        <ol class="flex items-center gap-2">
            <li>
                <a href="<?php echo esc_url(bimverdi_minside_url('')); ?>" class="hover:text-[#1A1A1A]">
                    <?php _e('Min side', 'bimverdi'); ?>
                </a>
            </li>
            <li>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888]"><path d="m9 18 6-6-6-6"/></svg>
            </li>
            <?php
            // Determine current section
            $primary_route = bimverdi_get_primary_route();
            $section_label = '';
            $parent_url = '';

            if (in_array($primary_route, ['profil'])) {
                $section_label = __('Profil', 'bimverdi');
                $parent_url = bimverdi_minside_url('profil');
            } elseif (in_array($primary_route, ['foretak', 'invitasjoner'])) {
                $section_label = __('Foretak', 'bimverdi');
                $parent_url = bimverdi_minside_url('foretak');
            }

            // Find current item label
            $current_label = '';
            foreach ($nav_sections as $section) {
                foreach ($section['items'] as $item) {
                    if (bimverdi_is_account_route_active($item['route'], $current_route)) {
                        $current_label = $item['label'];
                        break 2;
                    }
                }
            }
            ?>
            <?php if ($section_label && $current_route !== 'profil' && $current_route !== 'foretak'): ?>
                <li>
                    <a href="<?php echo esc_url($parent_url); ?>" class="hover:text-[#1A1A1A]">
                        <?php echo esc_html($section_label); ?>
                    </a>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#888888]"><path d="m9 18 6-6-6-6"/></svg>
                </li>
            <?php endif; ?>
            <li class="text-[#1A1A1A] font-medium">
                <?php echo esc_html($current_label ?: $section_label); ?>
            </li>
        </ol>
    </nav>
</div>
