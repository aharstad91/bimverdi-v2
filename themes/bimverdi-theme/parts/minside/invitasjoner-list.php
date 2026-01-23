<?php
/**
 * Part: Invitasjoner (Invitations Management)
 *
 * Allows hovedkontakt to manage company invitations and users.
 * Brukes på /min-side/invitasjoner/
 *
 * Follows UI-CONTRACT.md Variant B (Dividers/Whitespace)
 *
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_data = bimverdi_get_user_company($user_id);

// Check if user has a company
// bimverdi_get_user_company() returns array with 'id', 'name', etc. or false
if (!$company_data) {
    wp_redirect(bimverdi_minside_url('foretak') . '?ikke_koblet=1');
    exit;
}

// Extract company ID from the returned array
$company_id = is_array($company_data) ? $company_data['id'] : (int) $company_data;

// Check if user is hovedkontakt
if (!bimverdi_is_hovedkontakt($user_id, $company_id)) {
    wp_redirect(bimverdi_minside_url('foretak') . '?ikke_hovedkontakt=1');
    exit;
}

// Get company post object
$company = get_post($company_id);

// Verify company post exists and is correct post type
if (!$company || $company->post_type !== 'foretak') {
    wp_redirect(bimverdi_minside_url('foretak') . '?ugyldig_foretak=1');
    exit;
}

$er_aktiv_deltaker = bimverdi_is_company_active($company_id);

// Get invitations handler
if (!function_exists('bimverdi_get_invitations')) {
    ?>
    <div class="p-4 bg-amber-50 border-l-4 border-amber-400 text-amber-800">
        <strong>Invitasjonssystemet er ikke tilgjengelig</strong><br>
        Ta kontakt på <a href="mailto:post@bimverdi.no" class="underline">post@bimverdi.no</a> for assistanse.
    </div>
    <?php
    get_template_part('parts/components/account-layout-end');
    return;
}

$invitations = bimverdi_get_invitations();
$pending_invitations = $invitations->get_pending_invitations($company_id);
$all_invitations = $invitations->get_company_invitations($company_id);
$company_users = $invitations->get_company_users($company_id);

// Calculate remaining invitations based on actual users (excluding hovedkontakt)
$max_tilleggskontakter = 2; // Default max
$acf_max = get_field('antall_invitasjoner_tillatt', $company_id);
if ($acf_max && $acf_max > 0) {
    $max_tilleggskontakter = (int) $acf_max;
}

// Count tilleggskontakter (users minus hovedkontakt)
$tilleggskontakter_count = 0;
foreach ($company_users as $u) {
    if (!$u->is_hovedkontakt) {
        $tilleggskontakter_count++;
    }
}

// Add pending invitations to used count
$pending_count = count($pending_invitations);
$total_used = $tilleggskontakter_count + $pending_count;
$remaining_invitations = max(0, $max_tilleggskontakter - $total_used);

// Get company name safely
$company_name = $company->post_title ?: __('Ditt foretak', 'bimverdi');
?>

<!-- Account Layout with Sidenav -->
<?php get_template_part('parts/components/account-layout', null, [
    'title' => __('Inviter kolleger', 'bimverdi'),
    'description' => sprintf(__('Administrer brukertilgang for %s', 'bimverdi'), esc_html($company_name)),
]); ?>

<?php if (!$er_aktiv_deltaker): ?>
    <!-- Company not yet approved -->
    <div class="p-4 bg-amber-50 border-l-4 border-amber-400 text-amber-800 mb-8">
        <strong>Foretaket er ikke godkjent ennå</strong><br>
        Du kan ikke sende invitasjoner før foretaket er godkjent av BIM Verdi.
        Ta kontakt på <a href="mailto:post@bimverdi.no" class="underline">post@bimverdi.no</a> hvis dette tar lang tid.
    </div>
<?php else: ?>

    <!-- Send New Invitation -->
    <?php if ($remaining_invitations > 0): ?>
    <section class="mb-10">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-1"><?php _e('Send invitasjon', 'bimverdi'); ?></h2>
        <p class="text-sm text-[#5A5A5A] mb-4">
            <?php printf(
                __('Du kan invitere %d tilleggskontakt%s til.', 'bimverdi'),
                $remaining_invitations,
                $remaining_invitations === 1 ? '' : 'er'
            ); ?>
        </p>

        <form id="send-invitation-form">
            <?php wp_nonce_field('bimverdi_invitation_nonce', 'nonce'); ?>
            <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">

            <div class="flex gap-3 max-w-xl">
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
                Invitasjonen er gyldig i 7 dager. Kollegaen vil motta en e-post med lenke for å registrere seg.
            </p>
        </form>

        <div id="invite-result" class="mt-4 hidden max-w-xl"></div>
    </section>

    <hr class="border-[#E5E0D8] mb-10">
    <?php endif; ?>

    <!-- Current Users -->
    <section class="mb-10">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-1">
            <?php printf(__('Brukere i %s', 'bimverdi'), esc_html($company_name)); ?>
        </h2>
        <p class="text-sm text-[#5A5A5A] mb-4">
            <?php echo count($company_users); ?> bruker<?php echo count($company_users) !== 1 ? 'e' : ''; ?> tilkoblet
        </p>

        <?php if (empty($company_users)): ?>
            <p class="text-[#5A5A5A]">Ingen brukere tilkoblet ennå.</p>
        <?php else: ?>
            <div class="divide-y divide-[#E5E0D8]">
                <?php foreach ($company_users as $user): ?>
                    <div class="py-4 flex items-center justify-between <?php echo $user === reset($company_users) ? '' : ''; ?>">
                        <div class="flex items-center gap-3">
                            <?php echo get_avatar($user->ID, 40, '', '', array('class' => 'rounded-full')); ?>
                            <div>
                                <div class="font-medium text-[#1A1A1A]">
                                    <?php echo esc_html($user->display_name); ?>
                                    <?php if ($user->is_hovedkontakt): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[#1A1A1A] text-white ml-2">
                                            Hovedkontakt
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-[#5A5A5A]"><?php echo esc_html($user->user_email); ?></div>
                            </div>
                        </div>
                        <div>
                            <?php if (!$user->is_hovedkontakt): ?>
                                <button type="button"
                                        class="remove-user-btn text-sm text-[#5A5A5A] hover:text-red-600 transition-colors"
                                        data-user-id="<?php echo $user->ID; ?>"
                                        data-user-name="<?php echo esc_attr($user->display_name); ?>">
                                    Fjern tilgang
                                </button>
                            <?php else: ?>
                                <span class="text-sm text-[#9A9488]">Kan ikke fjernes</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Pending Invitations -->
    <?php if (!empty($pending_invitations)): ?>
    <hr class="border-[#E5E0D8] mb-10">

    <section class="mb-10">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-1"><?php _e('Ventende invitasjoner', 'bimverdi'); ?></h2>
        <p class="text-sm text-[#5A5A5A] mb-4">
            <?php echo count($pending_invitations); ?> invitasjon<?php echo count($pending_invitations) !== 1 ? 'er' : ''; ?> venter på svar
        </p>

        <div class="divide-y divide-[#E5E0D8]">
            <?php foreach ($pending_invitations as $invitation): ?>
                <div class="py-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-[#1A1A1A]"><?php echo esc_html($invitation->email); ?></div>
                        <div class="text-sm text-[#5A5A5A]">
                            Sendt <?php echo date('d.m.Y', strtotime($invitation->created_at)); ?>
                            · Utløper <?php echo date('d.m.Y', strtotime($invitation->expires_at)); ?>
                        </div>
                    </div>
                    <button type="button"
                            class="revoke-invite-btn text-sm text-[#5A5A5A] hover:text-red-600 transition-colors"
                            data-invitation-id="<?php echo $invitation->id; ?>"
                            data-email="<?php echo esc_attr($invitation->email); ?>">
                        Trekk tilbake
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Invitation History (only show if there are accepted invitations) -->
    <?php
    $used_invitations = array_filter($all_invitations, function($inv) {
        return $inv->status === 'accepted';
    });
    if (!empty($used_invitations)):
    ?>
    <hr class="border-[#E5E0D8] mb-10">

    <section class="mb-10">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-1"><?php _e('Invitasjonshistorikk', 'bimverdi'); ?></h2>
        <p class="text-sm text-[#5A5A5A] mb-4">Tidligere aksepterte invitasjoner</p>

        <div class="divide-y divide-[#E5E0D8]">
            <?php foreach ($used_invitations as $invitation): ?>
                <div class="py-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-[#1A1A1A]"><?php echo esc_html($invitation->email); ?></div>
                        <div class="text-sm text-[#5A5A5A]">
                            Akseptert <?php echo date('d.m.Y', strtotime($invitation->used_at)); ?>
                        </div>
                    </div>
                    <span class="text-sm text-green-700">Akseptert</span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Info about limits (only show if no remaining invitations) -->
    <?php if ($remaining_invitations <= 0): ?>
    <div class="p-4 bg-[#F7F5EF] border-l-4 border-[#D1CCC3] text-[#5A5A5A]">
        <strong class="text-[#1A1A1A]">Maksimalt antall tilleggskontakter nådd</strong><br>
        Du har <?php echo $tilleggskontakter_count; ?> tilleggskontakt<?php echo $tilleggskontakter_count !== 1 ? 'er' : ''; ?><?php if ($pending_count > 0): ?> og <?php echo $pending_count; ?> ventende invitasjon<?php echo $pending_count !== 1 ? 'er' : ''; ?><?php endif; ?>.
        Kontakt <a href="mailto:post@bimverdi.no" class="underline text-[#1A1A1A]">BIM Verdi</a> hvis du trenger flere.
    </div>
    <?php endif; ?>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                        <div class="p-4 bg-green-50 border-l-4 border-green-500 text-green-800">
                            ${result.data.message}
                        </div>
                    `;
                    inviteForm.reset();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    inviteResult.innerHTML = `
                        <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-800">
                            ${result.data.message}
                        </div>
                    `;
                }
            } catch (error) {
                inviteResult.innerHTML = `
                    <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-800">
                        En feil oppstod. Prøv igjen senere.
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
        btn.addEventListener('click', async function() {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;

            if (!confirm(`Er du sikker på at du vil fjerne tilgangen til ${userName}?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'bimverdi_remove_user_access');
            formData.append('user_id', userId);
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
});
</script>

<?php get_template_part('parts/components/account-layout-end'); ?>
