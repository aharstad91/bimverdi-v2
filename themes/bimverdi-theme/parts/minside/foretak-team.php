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

// Check if user is hovedkontakt - tilleggskontakter see read-only view
$is_hovedkontakt = bimverdi_is_hovedkontakt($user_id, $company_id);
$is_readonly = !$is_hovedkontakt;

// Get company data
$company = get_post($company_id);

// Verify company post exists and is correct post type
if (!$company || $company->post_type !== 'foretak') {
    wp_redirect(bimverdi_minside_url('foretak') . '?ugyldig_foretak=1');
    exit;
}

$company_name = $company->post_title ?: __('Ditt foretak', 'bimverdi');
$is_active_member = bimverdi_is_company_active($company_id);

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
$max_tilleggskontakter = 50;  // Default from DEFAULT_MAX_INVITATIONS constant
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

// Handle POST actions (form submissions)
if (isset($_POST['action']) && wp_verify_nonce($_POST['nonce'] ?? '', 'bimverdi_team_nonce')) {
    // SECURITY: Always check hovedkontakt authorization FIRST
    if (!bimverdi_is_hovedkontakt($user_id, $company_id)) {
        wp_redirect(bimverdi_minside_url('foretak/kolleger') . '?bv_error=ikke_autorisert');
        exit;
    }

    $ajax_action = sanitize_text_field($_POST['action']);

    if ($ajax_action === 'remove_user' && isset($_POST['remove_user_id'])) {
        $remove_user_id = absint($_POST['remove_user_id']);

        // Don't allow removing self (hovedkontakt)
        if ($remove_user_id === $user_id) {
            wp_redirect(bimverdi_minside_url('foretak/kolleger') . '?bv_error=kan_ikke_fjerne_deg_selv');
            exit;
        }

        // Validate target user belongs to same company
        $target_company = get_user_meta($remove_user_id, 'bimverdi_company_id', true);
        if (!$target_company) {
            $target_company = get_user_meta($remove_user_id, 'bim_verdi_company_id', true);
        }
        if ($target_company != $company_id) {
            wp_redirect(bimverdi_minside_url('foretak/kolleger') . '?bv_error=feil_foretak');
            exit;
        }

        delete_user_meta($remove_user_id, 'bimverdi_company_id');
        delete_user_meta($remove_user_id, 'bim_verdi_company_id');
        wp_redirect(bimverdi_minside_url('foretak/kolleger') . '?bruker_fjernet=1');
        exit;
    }

    if ($ajax_action === 'transfer_hovedkontakt' && isset($_POST['new_hovedkontakt_id'])) {
        $new_hovedkontakt_id = absint($_POST['new_hovedkontakt_id']);

        // Verify new hovedkontakt is part of the company
        $new_user_company = get_user_meta($new_hovedkontakt_id, 'bimverdi_company_id', true);
        if (!$new_user_company) {
            $new_user_company = get_user_meta($new_hovedkontakt_id, 'bim_verdi_company_id', true);
        }

        if ($new_user_company == $company_id && function_exists('update_field')) {
            update_field('hovedkontaktperson', $new_hovedkontakt_id, $company_id);
            wp_redirect(bimverdi_minside_url('foretak/kolleger') . '?hovedkontakt_overfort=1');
            exit;
        } else {
            wp_redirect(bimverdi_minside_url('foretak/kolleger') . '?bv_error=feil_foretak');
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

    <?php if (isset($_GET['bv_error'])): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500">
            <p class="text-sm text-red-800">
                <?php
                $error_code = sanitize_text_field($_GET['bv_error']);
                switch ($error_code) {
                    case 'ikke_autorisert':
                        _e('Du har ikke tillatelse til å utføre denne handlingen.', 'bimverdi');
                        break;
                    case 'feil_foretak':
                        _e('Brukeren tilhører ikke dette foretaket.', 'bimverdi');
                        break;
                    case 'kan_ikke_fjerne_deg_selv':
                        _e('Du kan ikke fjerne deg selv fra foretaket.', 'bimverdi');
                        break;
                    default:
                        _e('En feil oppstod.', 'bimverdi');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($is_readonly): ?>
        <!-- Read-only info banner for tilleggskontakter -->
        <div class="mb-8 px-4 py-3 bg-[#F7F5EF] border-l-4 border-[#D6D1C6]">
            <p class="text-sm text-[#5A5A5A]">
                <strong class="text-[#1A1A1A]"><?php _e('Oversikt over kolleger', 'bimverdi'); ?></strong><br>
                <?php _e('Kun hovedkontakt kan invitere eller fjerne brukere.', 'bimverdi'); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Stats (Variant B: inline with dividers) -->
    <div class="grid grid-cols-2 mb-10">
        <div class="py-4 pr-6 border-r border-[#D6D1C6]">
            <div class="flex items-center gap-2 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888]">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span class="text-sm text-[#5A5A5A]"><?php _e('Brukere tilknyttet', 'bimverdi'); ?></span>
            </div>
            <p class="text-2xl font-semibold text-[#1A1A1A]"><?php echo count($company_users); ?></p>
        </div>
        <div class="py-4 pl-6">
            <div class="flex items-center gap-2 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#888]">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" x2="19" y1="8" y2="14"/>
                    <line x1="22" x2="16" y1="11" y2="11"/>
                </svg>
                <span class="text-sm text-[#5A5A5A]"><?php _e('Tilleggskontakter', 'bimverdi'); ?></span>
            </div>
            <p class="text-2xl font-semibold text-[#1A1A1A]"><?php echo max(0, count($company_users) - 1); ?></p>
        </div>
    </div>

    <?php if (!$is_readonly): ?>
        <?php if (!$is_active_member): ?>
            <!-- Company not yet approved -->
            <div class="mb-10 p-4 bg-[#FEF9E7] border-l-4 border-[#D6A74A]">
                <p class="text-sm text-[#5A5A5A]">
                    <strong class="text-[#1A1A1A]"><?php _e('Foretaket er ikke godkjent ennå', 'bimverdi'); ?></strong><br>
                    <?php _e('Du kan ikke sende invitasjoner før foretaket er godkjent av BIM Verdi.', 'bimverdi'); ?>
                    <?php _e('Ta kontakt på', 'bimverdi'); ?> <a href="mailto:post@bimverdi.no" class="underline text-[#1A1A1A]">post@bimverdi.no</a> <?php _e('hvis dette tar lang tid.', 'bimverdi'); ?>
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
    <?php endif; ?>

    <!-- Team Members List -->
    <section class="mb-10 <?php echo (!$is_readonly && !empty($pending_invitations)) ? 'pb-10 border-b border-[#E5E0D8]' : ''; ?>">
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
                            <?php if ($member->is_hovedkontakt): ?>
                                <?php if ($is_hovedkontakt): ?>
                                    <span class="text-sm text-[#9A9488]"><?php _e('Du', 'bimverdi'); ?></span>
                                <?php else: ?>
                                    <span class="text-sm text-[#9A9488]"><?php _e('Hovedkontakt', 'bimverdi'); ?></span>
                                <?php endif; ?>
                            <?php elseif (!$is_readonly): ?>
                                <button type="button"
                                        class="remove-user-btn text-sm text-[#5A5A5A] hover:text-red-600 transition-colors"
                                        data-user-id="<?php echo $member->ID; ?>"
                                        data-user-name="<?php echo esc_attr($member->display_name); ?>">
                                    <?php _e('Fjern', 'bimverdi'); ?>
                                </button>
                            <?php elseif ($member->ID === $user_id): ?>
                                <span class="text-sm text-[#9A9488]"><?php _e('Du', 'bimverdi'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Pending Invitations (only visible to hovedkontakt for privacy) -->
    <?php if (!$is_readonly && !empty($pending_invitations)): ?>
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

<?php if (!$is_readonly): ?>
    <!-- Hidden Forms for Remove User (only rendered for hovedkontakt) -->
    <form method="post" id="remove-user-form" style="display: none;">
        <?php wp_nonce_field('bimverdi_team_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="remove_user">
        <input type="hidden" name="remove_user_id" id="remove-user-id" value="">
    </form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // State guards for preventing race conditions
    let isSubmittingInvite = false;
    let pendingReloadTimeout = null;

    // Send Invitation Form
    const inviteForm = document.getElementById('send-invitation-form');
    const inviteResult = document.getElementById('invite-result');

    if (inviteForm) {
        inviteForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Double-submit prevention
            if (isSubmittingInvite) return;
            isSubmittingInvite = true;

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
                    // Clear any pending reload and set new one
                    if (pendingReloadTimeout) clearTimeout(pendingReloadTimeout);
                    pendingReloadTimeout = setTimeout(() => location.reload(), 2000);
                    // Don't re-enable button since we're reloading
                    return;
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

            // Only re-enable on error
            btn.textContent = originalText;
            btn.disabled = false;
            isSubmittingInvite = false;
        });
    }

    // Revoke invitation buttons
    document.querySelectorAll('.revoke-invite-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            // Prevent double-click
            if (this.disabled) return;

            const invitationId = this.dataset.invitationId;
            const email = this.dataset.email;

            if (!confirm(`Er du sikker på at du vil trekke tilbake invitasjonen til ${email}?`)) {
                return;
            }

            // Immediate visual feedback
            this.disabled = true;
            const originalText = this.textContent;
            this.textContent = 'Trekker tilbake...';

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
                    // Clear pending reload and reload
                    if (pendingReloadTimeout) clearTimeout(pendingReloadTimeout);
                    location.reload();
                } else {
                    alert(result.data.message);
                    this.disabled = false;
                    this.textContent = originalText;
                }
            } catch (error) {
                alert('En feil oppstod. Prøv igjen senere.');
                this.disabled = false;
                this.textContent = originalText;
            }
        });
    });

    // Remove user access buttons
    document.querySelectorAll('.remove-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Prevent double-click
            if (this.disabled) return;

            const userId = this.dataset.userId;
            const userName = this.dataset.userName;

            if (confirm(`Er du sikker på at du vil fjerne tilgangen til ${userName}?`)) {
                this.disabled = true;
                this.textContent = 'Fjerner...';
                document.getElementById('remove-user-id').value = userId;
                document.getElementById('remove-user-form').submit();
            }
        });
    });
});
</script>
<?php endif; // !$is_readonly ?>

<?php get_template_part('parts/components/account-layout-end'); ?>
