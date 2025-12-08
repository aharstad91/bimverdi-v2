<?php
/**
 * Min Side - Vertical Sidebar Navigation
 * 
 * Displays vertical sidebar navigation for all Min Side pages
 * Uses Web Awesome components with grouped menu items
 * 
 * @param string $current_page The current active page slug
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_page = isset($args['current_page']) ? $args['current_page'] : 'dashboard';
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Check if user is hovedkontakt for their company
$user_company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
$is_hovedkontakt = false;
if ($user_company_id) {
    $hovedkontakt_id = get_field('hovedkontaktperson', $user_company_id);
    $is_hovedkontakt = ($hovedkontakt_id == $user_id);
}

// Get counts for badges
$my_tools_count = count(get_posts(array(
    'post_type' => 'verktoy',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => array('publish', 'draft', 'pending'),
)));

$my_articles_count = count(get_posts(array(
    'post_type' => 'artikkel',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => array('publish', 'draft', 'pending'),
)));

$my_ideas_count = count(get_posts(array(
    'post_type' => 'case',
    'author' => $user_id,
    'posts_per_page' => -1,
)));

$my_registrations_count = count(get_posts(array(
    'post_type' => 'pamelding',
    'meta_key' => 'pamelding_bruker',
    'meta_value' => $user_id,
    'posts_per_page' => -1,
)));

// Navigation structure grouped by function
$nav_groups = array(
    'innhold' => array(
        'label' => 'Innhold',
        'items' => array(
            'verktoy' => array(
                'label' => 'Verktøy',
                'icon' => 'wrench',
                'url' => home_url('/min-side/verktoy/'),
                'count' => $my_tools_count,
            ),
            'artikler' => array(
                'label' => 'Artikler',
                'icon' => 'file-lines',
                'url' => home_url('/min-side/artikler/'),
                'count' => $my_articles_count,
            ),
            'prosjektideer' => array(
                'label' => 'Prosjektidéer',
                'icon' => 'lightbulb',
                'url' => home_url('/min-side/prosjektideer/'),
                'count' => $my_ideas_count,
            ),
        ),
    ),
    'aktivitet' => array(
        'label' => 'Aktivitet',
        'items' => array(
            'arrangementer' => array(
                'label' => 'Arrangementer',
                'icon' => 'calendar-days',
                'url' => home_url('/min-side/arrangementer/'),
                'count' => $my_registrations_count,
            ),
        ),
    ),
    'konto' => array(
        'label' => 'Konto',
        'items' => array(
            'profil' => array(
                'label' => 'Profil',
                'icon' => 'user',
                'url' => home_url('/min-side/profil/'),
                'count' => null,
            ),
            'foretak' => array(
                'label' => 'Foretak',
                'icon' => 'building',
                'url' => home_url('/min-side/foretak/'),
                'count' => null,
            ),
            'temagrupper' => array(
                'label' => 'Temagrupper',
                'icon' => 'layer-group',
                'url' => home_url('/min-side/temagrupper/'),
                'count' => null,
            ),
        ),
    ),
);

// Add invitations link for hovedkontakt only
if ($is_hovedkontakt) {
    $nav_groups['konto']['items']['invitasjoner'] = array(
        'label' => 'Inviter kolleger',
        'icon' => 'user-plus',
        'url' => home_url('/min-side/invitasjoner/'),
        'count' => null,
    );
}
?>

<aside class="minside-sidebar">
    <!-- User info header -->
    <div class="minside-sidebar__user">
        <wa-avatar 
            initials="<?php echo esc_attr(substr($current_user->display_name, 0, 2)); ?>" 
            style="--size: 3rem;">
        </wa-avatar>
        <div class="minside-sidebar__user-info">
            <span class="minside-sidebar__user-name">
                <?php echo esc_html($current_user->display_name); ?>
            </span>
            <span class="minside-sidebar__user-email">
                <?php echo esc_html($current_user->user_email); ?>
            </span>
        </div>
    </div>

    <!-- Dashboard link (separate from groups) -->
    <nav class="minside-sidebar__nav">
        <a href="<?php echo esc_url(home_url('/min-side/')); ?>" 
           class="minside-sidebar__item <?php echo $current_page === 'dashboard' ? 'minside-sidebar__item--active' : ''; ?>">
            <wa-icon name="house" library="fa"></wa-icon>
            <span>Dashboard</span>
        </a>
    </nav>

    <!-- Grouped navigation -->
    <?php foreach ($nav_groups as $group_key => $group): ?>
        <div class="minside-sidebar__group">
            <span class="minside-sidebar__group-label"><?php echo esc_html($group['label']); ?></span>
            <nav class="minside-sidebar__nav">
                <?php foreach ($group['items'] as $item_key => $item): ?>
                    <a href="<?php echo esc_url($item['url']); ?>" 
                       class="minside-sidebar__item <?php echo $current_page === $item_key ? 'minside-sidebar__item--active' : ''; ?>">
                        <wa-icon name="<?php echo esc_attr($item['icon']); ?>" library="fa"></wa-icon>
                        <span><?php echo esc_html($item['label']); ?></span>
                        <?php if ($item['count'] !== null && $item['count'] > 0): ?>
                            <wa-badge variant="neutral" class="minside-sidebar__badge">
                                <?php echo esc_html($item['count']); ?>
                            </wa-badge>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    <?php endforeach; ?>

    <!-- Quick action button -->
    <div class="minside-sidebar__action">
        <wa-button variant="brand" class="w-full" href="<?php echo esc_url(home_url('/min-side/registrer-verktoy/')); ?>">
            <wa-icon slot="prefix" name="plus" library="fa"></wa-icon>
            Nytt verktøy
        </wa-button>
    </div>
</aside>

<style>
.minside-sidebar {
    width: 260px;
    flex-shrink: 0;
    background: white;
    border-right: 1px solid #e5e7eb;
    padding: 1.5rem 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    position: sticky;
    top: 80px; /* Adjust based on header height */
    height: calc(100vh - 80px);
    overflow-y: auto;
}

.minside-sidebar__user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0 1.25rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 0.5rem;
}

.minside-sidebar__user-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.minside-sidebar__user-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.9375rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.minside-sidebar__user-email {
    font-size: 0.8125rem;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.minside-sidebar__group {
    margin-top: 0.75rem;
}

.minside-sidebar__group-label {
    display: block;
    padding: 0.5rem 1.25rem;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #9ca3af;
}

.minside-sidebar__nav {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.minside-sidebar__item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 1.25rem;
    color: #4b5563;
    text-decoration: none;
    font-size: 0.9375rem;
    font-weight: 500;
    transition: all 0.15s ease;
    border-left: 3px solid transparent;
}

.minside-sidebar__item:hover {
    background: #f9fafb;
    color: #1f2937;
}

.minside-sidebar__item--active {
    background: #fff7ed;
    color: #ea580c;
    border-left-color: #ea580c;
}

.minside-sidebar__item--active:hover {
    background: #fff7ed;
    color: #ea580c;
}

.minside-sidebar__item wa-icon {
    font-size: 1.125rem;
    width: 1.25rem;
    text-align: center;
}

.minside-sidebar__badge {
    margin-left: auto;
    font-size: 0.75rem;
}

.minside-sidebar__action {
    margin-top: auto;
    padding: 1.25rem;
    border-top: 1px solid #e5e7eb;
}

/* Scrollbar styling */
.minside-sidebar::-webkit-scrollbar {
    width: 4px;
}

.minside-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.minside-sidebar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 2px;
}

.minside-sidebar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
