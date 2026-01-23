<?php
/**
 * Min Side - Kolleger (Team & Invitations Management)
 *
 * Combined view for managing team members and sending invitations.
 * Replaces both foretak-team.php and invitasjoner-list.php.
 *
 * Follows UI-CONTRACT.md Variant B (Dividers/Whitespace)
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's company
$company_data = bimverdi_get_user_company($user_id);

// Check if user has a company
if (!$company_data) {
    wp_redirect(bimverdi_minside_url('foretak') . '?ikke_koblet=1');
    exit;
}

// bimverdi_get_user_company() returns an array with 'id' key
$company_id = is_array($company_data) ? $company_data['id'] : $company_data;

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
$er_aktiv_deltaker = bimverdi_is_company_active($company_id);

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

// Get invitations data
$invitations = null;
$pending_invitations = [];
$max_tilleggskontakter = 2;
$remaining_invitations = 0;

if (function_exists('bimverdi_get_invitations')) {
    $invitations = bimverdi_get_invitations();
    $pending_invitations = $invitations->get_pending_invitations($company_id);

    // Calculate remaining invitations
    $acf_max = get_field('antall_invitasjoner_tillatt', $company_id);
    if ($acf_max && $acf_max > 0) {
        $max_tilleggskontakter = (int) $acf_max;
    }

    $tilleggskontakter_count = 0;
    foreach ($company_users as $u) {
        if (!$u->is_hovedkontakt) {
            $tilleggskontakter_count++;
        }
    }

    $pending_count = count($pending_invitations);
    $total_used = $tilleggskontakter_count + $pending_count;
    $remaining_invitations = max(0, $max_tilleggskontakter - $total_used);
}

// Handle AJAX actions (form submissions)
if (isset($_POST['action']) && wp_verify_nonce($_POST['nonce'] ?? '', 'bimverdi_team_nonce')) {
    $ajax_action = sanitize_text_field($_POST['action']);

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

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => __('Kolleger', 'bimverdi'),
    'description' => sprintf(__('Administrer brukertilgang for %s', 'bimverdi'), esc_html($company_name)),
]); ?>

    <!-- Success Messages -->
    <?php if (isset($_GET['bruker_fjernet'])): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500">
            <p class="text-sm text-green-800"><?php _e('Brukeren ble fjernet fra foretaket.', 'bimverdi'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['hovedkontakt_overfort'])): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500">
            <p class="text-sm text-green-800"><?php _e('Hovedkontakt-rollen ble overført.', 'bimverdi'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Stats Cards (Variant B: minimal borders) -->
    <div class="mb-10 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-5 border border-[#E5E0D8] rounded-lg">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#F7F5EF] flex items-center justify-center flex-shrink-0">
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
        <div class="p-5 border border-[#E5E0D8] rounded-lg">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#F7F5EF] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#5A5A5A]">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" x2="19" y1="8" y2="14"/>
                        <line x1="22" x2="16" y1="11" y2="11"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-[#1A1A1A]"><?php echo max(0, count($company_users) - 1); ?></p>
                    <p class="text-sm text-[#5A5A5A]"><?php _e('Tilleggskontakter', 'bimverdi'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$er_aktiv_deltaker): ?>
        <!-- Company not yet approved -->
        <div class="mb-10 p-4 bg-amber-50 border-l-4 border-amber-400">
            <p class="text-sm text-amber-800">
                <strong><?php _e('Foretaket er ikke godkjent ennå', 'bimverdi'); ?></strong><br>
                <?php _e('Du kan ikke sende invitasjoner før foretaket er godkjent av BIM Verdi.', 'bimverdi'); ?>
                <?php _e('Ta kontakt på', 'bimverdi'); ?> <a href="mailto:post@bimverdi.no" class="underline">post@bimverdi.no</a> <?php _e('hvis dette tar lang tid.', 'bimverdi'); ?>
            </p>
        </div>
    <?php elseif ($invitations && $remaining_invitations > 0): ?>

        <!-- Send New Invitation Section -->
        <section class="mb-10 pb-10 border-b border-[#E5E0D8]">
            <h2 class="text-lg font-semibold text-[#1A1A1A] mb-1"><?php _e('Inviter ny kollega', 'bimverdi'); ?></h2>
            <p class="text-sm text-[#5A5A5A] mb-5">
                <?php printf(
                    __('Du kan invitere %d tilleggskontakt%s til.', 'bimverdi'),
                    $remaining_invitations,
                    $remaining_invitations === 1 ? '' : 'er'
                ); ?>
            </p>

            <form id="send-invitation-form" class="max-w-xl">
                <?php wp_nonce_field('bimverdi_invitation_nonce', 'nonce'); ?>
                <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">

                <div class="flex gap-3">
                    <input
                        type="email"
                        id="invite-email"
                        name="email"
                        placeholder="kollega@bedrift.no"
                        required
                        class="flex-grow px-4 py-2.5 border border-[#D1CCC3] rounded-lg focus:ring-2 focus:ring-[#1A1A1A] focus:border-[#1A1A1A] bg-white text-[#1A1A1A]"
                    />
                    <?php echo bimverdi_button([
                        'type' => 'submit',
                        'variant' => 'primary',
                        'text' => 'Send invitasjon',
                        'id' => 'send-invite-btn',
                    ]); ?>
                </div>

                <p class="text-sm text-[#5A5A5A] mt-3">
                    <?php _e('Invitasjonen er gyldig i 7 dager. Kollegaen vil motta en e-post med lenke for å registrere seg.', 'bimverdi'); ?>
                </p>
            </form>

            <div id="invite-result" class="mt-4 hidden max-w-xl"></div>
        </section>

    <?php elseif ($invitations && $remaining_invitations <= 0): ?>

        <!-- Max invitations reached -->
        <div class="mb-10 p-4 bg-[#F7F5EF] border-l-4 border-[#D1CCC3]">
            <p class="text-sm text-[#5A5A5A]">
                <strong class="text-[#1A1A1A]"><?php _e('Maksimalt antall tilleggskontakter nådd', 'bimverdi'); ?></strong><br>
                <?php
                $tilleggskontakter_count = count($company_users) - 1;
                printf(
                    __('Du har %d tilleggskontakt%s', 'bimverdi'),
                    $tilleggskontakter_count,
                    $tilleggskontakter_count !== 1 ? 'er' : ''
                );
                if (!empty($pending_invitations)) {
                    $pending_count = count($pending_invitations);
                    printf(
                        __(' og %d ventende invitasjon%s', 'bimverdi'),
                        $pending_count,
                        $pending_count !== 1 ? 'er' : ''
                    );
                }
                ?>.
                <?php _e('Kontakt', 'bimverdi'); ?> <a href="mailto:post@bimverdi.no" class="underline text-[#1A1A1A]">BIM Verdi</a> <?php _e('hvis du trenger flere.', 'bimverdi'); ?>
            </p>
        </div>

    <?php endif; ?>

    <!-- Team Members List -->
    <section class="mb-10 <?php echo !empty($pending_invitations) ? 'pb-10 border-b border-[#E5E0D8]' : ''; ?>">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-1"><?php _e('Teammedlemmer', 'bimverdi'); ?></h2>
        <p class="text-sm text-[#5A5A5A] mb-5">
            <?php echo count($company_users); ?> bruker<?php echo count($company_users) !== 1 ? 'e' : ''; ?> tilkoblet
        </p>

        <?php if (empty($company_users)): ?>
            <p class="text-[#5A5A5A]"><?php _e('Ingen brukere tilkoblet ennå.', 'bimverdi'); ?></p>
        <?php else: ?>
            <div class="divide-y divide-[#E5E0D8]">
                <?php foreach ($company_users as $member): ?>
                    <div class="py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <?php echo get_avatar($member->ID, 40, '', '', ['class' => 'rounded-full']); ?>
                            <div>
                                <div class="font-medium text-[#1A1A1A] flex items-center gap-2">
                                    <?php echo esc_html($member->display_name); ?>
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
                                <div class="text-sm text-[#5A5A5A]"><?php echo esc_html($member->user_email); ?></div>
                            </div>
                        </div>

                        <div>
                            <?php if (!$member->is_hovedkontakt): ?>
                                <button type="button"
                                        class="remove-user-btn text-sm text-[#5A5A5A] hover:text-red-600 transition-colors"
                                        data-user-id="<?php echo $member->ID; ?>"
                                        data-user-name="<?php echo esc_attr($member->display_name); ?>">
                                    <?php _e('Fjern', 'bimverdi'); ?>
                                </button>
                            <?php else: ?>
                                <span class="text-sm text-[#9A9488]"><?php _e('Du', 'bimverdi'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Pending Invitations -->
    <?php if (!empty($pending_invitations)): ?>
    <section class="mb-10">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-1"><?php _e('Ventende invitasjoner', 'bimverdi'); ?></h2>
        <p class="text-sm text-[#5A5A5A] mb-5">
            <?php echo count($pending_invitations); ?> invitasjon<?php echo count($pending_invitations) !== 1 ? 'er' : ''; ?> venter på svar
        </p>

        <div class="divide-y divide-[#E5E0D8]">
            <?php foreach ($pending_invitations as $invitation): ?>
                <div class="py-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-[#1A1A1A]"><?php echo esc_html($invitation->email); ?></div>
                        <div class="text-sm text-[#5A5A5A]">
                            <?php _e('Sendt', 'bimverdi'); ?> <?php echo date('d.m.Y', strtotime($invitation->created_at)); ?>
                            · <?php _e('Utløper', 'bimverdi'); ?> <?php echo date('d.m.Y', strtotime($invitation->expires_at)); ?>
                        </div>
                    </div>
                    <button type="button"
                            class="revoke-invite-btn text-sm text-[#5A5A5A] hover:text-red-600 transition-colors"
                            data-invitation-id="<?php echo $invitation->id; ?>"
                            data-email="<?php echo esc_attr($invitation->email); ?>">
                        <?php _e('Trekk tilbake', 'bimverdi'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Hidden Forms for Remove User -->
    <form method="post" id="remove-user-form" style="display: none;">
        <?php wp_nonce_field('bimverdi_team_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="remove_user">
        <input type="hidden" name="remove_user_id" id="remove-user-id" value="">
    </form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Send Invitation Form
    const inviteForm = document.getElementById('send-invitation-form');
    const inviteResult = document.getElementById('invite-result');

    if (inviteForm) {
        inviteForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('send-invite-btn');
            const originalText = btn.textContent;
            btn.textContent = 'Sender...';
            btn.disabled = true;

            const formData = new FormData(this);
            formData.append('action', 'bimverdi_send_invitation');

            try {
                const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                inviteResult.classList.remove('hidden');

                if (result.success) {
                    inviteResult.innerHTML = `
                        <div class="p-4 bg-green-50 border-l-4 border-green-500">
                            <p class="text-sm text-green-800">${result.data.message}</p>
                        </div>
                    `;
                    inviteForm.reset();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    inviteResult.innerHTML = `
                        <div class="p-4 bg-red-50 border-l-4 border-red-500">
                            <p class="text-sm text-red-800">${result.data.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                inviteResult.innerHTML = `
                    <div class="p-4 bg-red-50 border-l-4 border-red-500">
                        <p class="text-sm text-red-800">En feil oppstod. Prøv igjen senere.</p>
                    </div>
                `;
            }

            btn.textContent = originalText;
            btn.disabled = false;
        });
    }

    // Revoke invitation buttons
    document.querySelectorAll('.revoke-invite-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const invitationId = this.dataset.invitationId;
            const email = this.dataset.email;

            if (!confirm(`Er du sikker på at du vil trekke tilbake invitasjonen til ${email}?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'bimverdi_revoke_invitation');
            formData.append('invitation_id', invitationId);
            formData.append('nonce', '<?php echo wp_create_nonce('bimverdi_invitation_nonce'); ?>');

            try {
                const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert(result.data.message);
                }
            } catch (error) {
                alert('En feil oppstod. Prøv igjen senere.');
            }
        });
    });

    // Remove user access buttons
    document.querySelectorAll('.remove-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;

            if (confirm(`Er du sikker på at du vil fjerne tilgangen til ${userName}?`)) {
                document.getElementById('remove-user-id').value = userId;
                document.getElementById('remove-user-form').submit();
            }
        });
    });
});
</script>

<?php get_template_part('parts/components/account-layout-end'); ?>
