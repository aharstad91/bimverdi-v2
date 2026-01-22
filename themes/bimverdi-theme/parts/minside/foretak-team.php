<?php
/**
 * Min Side - Foretak Team Management
 *
 * Allows hovedkontakt to view and manage team members.
 * Features: View members, remove access, transfer hovedkontakt role.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's company (check both meta keys for legacy compatibility)
$company_id = get_user_meta($user_id, 'bimverdi_company_id', true);
if (!$company_id) {
    $company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);
}

// Check if user has a company
if (!$company_id) {
    wp_redirect(bimverdi_minside_url('foretak') . '?ikke_koblet=1');
    exit;
}

// Check if user is hovedkontakt
if (!bimverdi_is_hovedkontakt($user_id, $company_id)) {
    wp_redirect(bimverdi_minside_url('foretak') . '?ikke_hovedkontakt=1');
    exit;
}

// Get company data
$company = get_post($company_id);

// Verify company post exists and is correct post type
if (!$company || $company->post_type !== 'foretak') {
    wp_redirect(bimverdi_minside_url('foretak') . '?ugyldig_foretak=1');
    exit;
}

$company_name = $company->post_title ?: __('Ditt foretak', 'bimverdi');

// Get all users linked to this company
$company_users = get_users([
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => 'bimverdi_company_id',
            'value' => $company_id,
        ],
        [
            'key' => 'bim_verdi_company_id',
            'value' => $company_id,
        ],
    ],
]);

// Get hovedkontakt user ID
$hovedkontakt_id = get_field('hovedkontaktperson', $company_id);

// Mark users with their role
foreach ($company_users as &$user) {
    $user->is_hovedkontakt = ($user->ID == $hovedkontakt_id);
}
unset($user);

// Sort: hovedkontakt first, then by display_name
usort($company_users, function($a, $b) {
    if ($a->is_hovedkontakt && !$b->is_hovedkontakt) return -1;
    if (!$a->is_hovedkontakt && $b->is_hovedkontakt) return 1;
    return strcasecmp($a->display_name, $b->display_name);
});

// Handle AJAX actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['nonce'] ?? '', 'bimverdi_team_nonce')) {
    $ajax_action = sanitize_text_field($_POST['action']);

    // These would typically be handled via AJAX endpoint, but for simplicity we handle here
    if ($ajax_action === 'remove_user' && isset($_POST['remove_user_id'])) {
        $remove_user_id = intval($_POST['remove_user_id']);
        // Don't allow removing self (hovedkontakt)
        if ($remove_user_id !== $user_id) {
            delete_user_meta($remove_user_id, 'bimverdi_company_id');
            delete_user_meta($remove_user_id, 'bim_verdi_company_id');
            wp_redirect(bimverdi_minside_url('foretak/team') . '?bruker_fjernet=1');
            exit;
        }
    }

    if ($ajax_action === 'transfer_hovedkontakt' && isset($_POST['new_hovedkontakt_id'])) {
        $new_hovedkontakt_id = intval($_POST['new_hovedkontakt_id']);
        // Verify new hovedkontakt is part of the company
        $new_user_company = get_user_meta($new_hovedkontakt_id, 'bimverdi_company_id', true);
        if (!$new_user_company) {
            $new_user_company = get_user_meta($new_hovedkontakt_id, 'bim_verdi_company_id', true);
        }

        if ($new_user_company == $company_id && function_exists('update_field')) {
            update_field('hovedkontaktperson', $new_hovedkontakt_id, $company_id);
            wp_redirect(bimverdi_minside_url('foretak/team') . '?hovedkontakt_overfort=1');
            exit;
        }
    }
}
?>

<!-- Breadcrumb -->
<nav class="mb-6" aria-label="Brødsmulesti">
    <ol class="flex items-center gap-2 text-sm text-[#5A5A5A]">
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url()); ?>" class="hover:text-[#1A1A1A] transition-colors">
                <?php _e('Min side', 'bimverdi'); ?>
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li>
            <a href="<?php echo esc_url(bimverdi_minside_url('foretak')); ?>" class="hover:text-[#1A1A1A] transition-colors">
                <?php _e('Foretak', 'bimverdi'); ?>
            </a>
        </li>
        <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="text-[#1A1A1A] font-medium" aria-current="page"><?php _e('Team', 'bimverdi'); ?></li>
    </ol>
</nav>

<!-- Success Messages -->
<?php if (isset($_GET['bruker_fjernet'])): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-green-600">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <p class="text-green-800"><?php _e('Brukeren ble fjernet fra foretaket.', 'bimverdi'); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['hovedkontakt_overfort'])): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-green-600">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <p class="text-green-800"><?php _e('Hovedkontakt-rollen ble overført.', 'bimverdi'); ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => __('Team-administrasjon', 'bimverdi'),
    'description' => sprintf(__('Administrer brukere tilknyttet %s', 'bimverdi'), esc_html($company_name)),
]); ?>

<!-- Team Stats -->
<div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white rounded-lg border border-[#E5E0D8] p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#F2F0EB] flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-[#1A1A1A]"><?php echo count($company_users); ?></p>
                <p class="text-sm text-[#5A5A5A]"><?php _e('Brukere tilknyttet', 'bimverdi'); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg border border-[#E5E0D8] p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#F2F0EB] flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-[#1A1A1A]"><?php echo max(0, count($company_users) - 1); ?></p>
                <p class="text-sm text-[#5A5A5A]"><?php _e('Tilleggskontakter', 'bimverdi'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Team Members List -->
<section class="mb-8">
    <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <?php _e('Teammedlemmer', 'bimverdi'); ?>
    </h2>

    <div class="bg-white rounded-lg border border-[#E5E0D8] divide-y divide-[#E5E0D8]">
        <?php if (empty($company_users)): ?>
            <div class="p-6 text-center text-[#5A5A5A]">
                <?php _e('Ingen brukere tilknyttet ennå.', 'bimverdi'); ?>
            </div>
        <?php else: ?>
            <?php foreach ($company_users as $member): ?>
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <?php echo get_avatar($member->ID, 48, '', '', ['class' => 'rounded-full']); ?>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-[#1A1A1A]"><?php echo esc_html($member->display_name); ?></p>
                                <?php if ($member->is_hovedkontakt): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[#FF8B5E] text-white">
                                        <?php _e('Hovedkontakt', 'bimverdi'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[#F2F0EB] text-[#5A5A5A]">
                                        <?php _e('Tilleggskontakt', 'bimverdi'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-[#5A5A5A]"><?php echo esc_html($member->user_email); ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <?php if (!$member->is_hovedkontakt): ?>
                            <!-- Remove User Button -->
                            <button type="button"
                                    onclick="confirmRemoveUser(<?php echo $member->ID; ?>, '<?php echo esc_js($member->display_name); ?>')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <line x1="17" y1="11" x2="22" y2="11"/>
                                </svg>
                                <?php _e('Fjern', 'bimverdi'); ?>
                            </button>
                        <?php else: ?>
                            <span class="text-sm text-[#9A9488]"><?php _e('Du', 'bimverdi'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Transfer Hovedkontakt Section -->
<?php
$other_users = array_filter($company_users, function($u) use ($hovedkontakt_id) {
    return $u->ID != $hovedkontakt_id;
});
if (!empty($other_users)):
?>
<section class="mb-8">
    <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
            <path d="M16 3h5v5"/>
            <path d="M8 3H3v5"/>
            <path d="M21 3l-7 7"/>
            <path d="M3 3l7 7"/>
            <path d="M3 21l7-7"/>
            <path d="M21 21l-7-7"/>
            <path d="M8 21H3v-5"/>
            <path d="M16 21h5v-5"/>
        </svg>
        <?php _e('Overfør hovedkontakt-rolle', 'bimverdi'); ?>
    </h2>

    <div class="bg-white rounded-lg border border-[#E5E0D8] p-5">
        <p class="text-sm text-[#5A5A5A] mb-4">
            <?php _e('Du kan overføre hovedkontakt-rollen til en annen bruker i foretaket. Du vil da bli tilleggskontakt.', 'bimverdi'); ?>
        </p>

        <form method="post" id="transfer-form">
            <?php wp_nonce_field('bimverdi_team_nonce', 'nonce'); ?>
            <input type="hidden" name="action" value="transfer_hovedkontakt">

            <div class="flex items-end gap-4">
                <div class="flex-1">
                    <label for="new_hovedkontakt" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                        <?php _e('Velg ny hovedkontakt', 'bimverdi'); ?>
                    </label>
                    <select name="new_hovedkontakt_id" id="new_hovedkontakt"
                            class="w-full px-4 py-2.5 border border-[#D1CCC3] rounded-lg focus:ring-2 focus:ring-[#FF8B5E] focus:border-[#FF8B5E] bg-white">
                        <option value=""><?php _e('Velg bruker...', 'bimverdi'); ?></option>
                        <?php foreach ($other_users as $other_user): ?>
                            <option value="<?php echo $other_user->ID; ?>">
                                <?php echo esc_html($other_user->display_name); ?> (<?php echo esc_html($other_user->user_email); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button"
                        onclick="confirmTransfer()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-[#FF8B5E] rounded-lg hover:bg-[#E67A4D] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 3h5v5"/>
                        <path d="M21 3l-7 7"/>
                    </svg>
                    <?php _e('Overfør rolle', 'bimverdi'); ?>
                </button>
            </div>
        </form>
    </div>

    <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-amber-600 flex-shrink-0 mt-0.5">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                <path d="M12 9v4"/>
                <path d="M12 17h.01"/>
            </svg>
            <p class="text-sm text-amber-800">
                <?php _e('Denne handlingen kan ikke angres. Du mister tilgangen til å redigere foretakets informasjon og administrere teamet.', 'bimverdi'); ?>
            </p>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Quick Actions -->
<section class="mb-8 pt-8 border-t border-[#D6D1C6]">
    <h2 class="text-sm font-bold text-[#5A5A5A] uppercase tracking-wider mb-4"><?php _e('Snarveier', 'bimverdi'); ?></h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="<?php echo esc_url(bimverdi_minside_url('invitasjoner')); ?>"
           class="flex items-center gap-4 p-4 bg-white rounded-lg border border-[#E5E0D8] hover:border-[#D6D1C6] transition-colors">
            <div class="w-10 h-10 rounded-lg bg-[#F2F0EB] flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/>
                    <line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-[#1A1A1A]"><?php _e('Inviter nye kolleger', 'bimverdi'); ?></p>
                <p class="text-sm text-[#5A5A5A]"><?php _e('Send invitasjoner til nye tilleggskontakter', 'bimverdi'); ?></p>
            </div>
        </a>

        <a href="<?php echo esc_url(bimverdi_minside_url('foretak')); ?>"
           class="flex items-center gap-4 p-4 bg-white rounded-lg border border-[#E5E0D8] hover:border-[#D6D1C6] transition-colors">
            <div class="w-10 h-10 rounded-lg bg-[#F2F0EB] flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                    <path d="M10 6h4"/>
                    <path d="M10 10h4"/>
                    <path d="M10 14h4"/>
                    <path d="M10 18h4"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-[#1A1A1A]"><?php _e('Foretaksprofil', 'bimverdi'); ?></p>
                <p class="text-sm text-[#5A5A5A]"><?php _e('Se og rediger foretakets informasjon', 'bimverdi'); ?></p>
            </div>
        </a>
    </div>
</section>

<!-- Back Link -->
<div class="pt-6 border-t border-[#E5E0D5]">
    <a href="<?php echo esc_url(bimverdi_minside_url('foretak')); ?>" class="inline-flex items-center gap-2 text-[#5A5A5A] hover:text-[#1A1A1A] transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6"/>
        </svg>
        <?php _e('Tilbake til foretaksprofilen', 'bimverdi'); ?>
    </a>
</div>

<!-- Hidden Forms for JavaScript Actions -->
<form method="post" id="remove-user-form" style="display: none;">
    <?php wp_nonce_field('bimverdi_team_nonce', 'nonce'); ?>
    <input type="hidden" name="action" value="remove_user">
    <input type="hidden" name="remove_user_id" id="remove-user-id" value="">
</form>

<script>
function confirmRemoveUser(userId, userName) {
    if (confirm('<?php echo esc_js(__('Er du sikker på at du vil fjerne', 'bimverdi')); ?> ' + userName + ' <?php echo esc_js(__('fra foretaket?', 'bimverdi')); ?>')) {
        document.getElementById('remove-user-id').value = userId;
        document.getElementById('remove-user-form').submit();
    }
}

function confirmTransfer() {
    const select = document.getElementById('new_hovedkontakt');
    const selectedOption = select.options[select.selectedIndex];

    if (!select.value) {
        alert('<?php echo esc_js(__('Velg en bruker først.', 'bimverdi')); ?>');
        return;
    }

    const confirmMessage = '<?php echo esc_js(__('Er du sikker på at du vil overføre hovedkontakt-rollen til', 'bimverdi')); ?> ' + selectedOption.text.split('(')[0].trim() + '?\n\n<?php echo esc_js(__('Du vil miste tilgangen til å redigere foretaket og administrere teamet.', 'bimverdi')); ?>';

    if (confirm(confirmMessage)) {
        document.getElementById('transfer-form').submit();
    }
}
</script>
