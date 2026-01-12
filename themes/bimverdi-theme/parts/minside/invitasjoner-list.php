<?php
/**
 * Part: Invitasjoner (Invitations Management)
 * 
 * Allows hovedkontakt to manage company invitations and users.
 * Brukes på /min-side/invitasjoner/
 * 
 * @package BimVerdi_Theme
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = bimverdi_get_user_company($user_id);

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
$er_aktiv_deltaker = bimverdi_is_company_active($company_id);

// Get invitations handler
if (!function_exists('bimverdi_get_invitations')) {
    // Invitations handler not available - show error
    get_template_part('parts/components/page-header', null, [
        'title' => 'Inviter kolleger',
        'description' => 'Administrer brukertilgang for ' . $company->post_title,
    ]);
    ?>
    <wa-alert variant="warning" class="mb-6">
        <strong>Invitasjonssystemet er ikke tilgjengelig</strong><br>
        Ta kontakt på <a href="mailto:post@bimverdi.no" class="underline">post@bimverdi.no</a> for assistanse.
    </wa-alert>
    <?php
    return;
}

$invitations = bimverdi_get_invitations();
$remaining_invitations = $invitations->get_remaining_invitations($company_id);
$pending_invitations = $invitations->get_pending_invitations($company_id);
$all_invitations = $invitations->get_company_invitations($company_id);
$company_users = $invitations->get_company_users($company_id);

// Page header
get_template_part('parts/components/page-header', null, [
    'title' => 'Inviter kolleger',
    'description' => 'Administrer brukertilgang for ' . $company->post_title,
    'icon' => 'user-plus',
]);
?>

<?php if (!$er_aktiv_deltaker): ?>
    <!-- Company not yet approved -->
    <wa-alert variant="warning" class="mb-6">
        <strong>Foretaket er ikke godkjent ennå</strong><br>
        Du kan ikke sende invitasjoner før foretaket er godkjent av BIM Verdi.
        Ta kontakt på <a href="mailto:post@bimverdi.no" class="underline">post@bimverdi.no</a> hvis dette tar lang tid.
    </wa-alert>
<?php else: ?>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-[#E5E2DB] p-4 text-center">
            <div class="text-3xl font-bold text-[#F97316]"><?php echo count($company_users); ?></div>
            <div class="text-sm text-[#5A5A5A]">Brukere tilkoblet</div>
        </div>
        <div class="bg-white rounded-lg border border-[#E5E2DB] p-4 text-center">
            <div class="text-3xl font-bold text-[#7C3AED]"><?php echo count($pending_invitations); ?></div>
            <div class="text-sm text-[#5A5A5A]">Ventende invitasjoner</div>
        </div>
        <div class="bg-white rounded-lg border border-[#E5E2DB] p-4 text-center">
            <div class="text-3xl font-bold <?php echo $remaining_invitations > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo $remaining_invitations; ?>
            </div>
            <div class="text-sm text-[#5A5A5A]">Invitasjoner igjen</div>
        </div>
    </div>

    <!-- Send New Invitation -->
    <section class="mb-8">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4 flex items-center gap-2">
            <?php echo bimverdi_icon('send', 20); ?>
            Send invitasjon
        </h2>
        
        <div class="bg-white rounded-lg border border-[#E5E2DB] p-5">
            <?php if ($remaining_invitations > 0): ?>
                <form id="send-invitation-form" class="space-y-4">
                    <?php wp_nonce_field('bimverdi_invitation_nonce', 'nonce'); ?>
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    
                    <div>
                        <label for="invite-email" class="block text-sm font-medium text-[#1A1A1A] mb-2">
                            E-postadresse til kollega
                        </label>
                        <div class="flex gap-3">
                            <input 
                                type="email" 
                                id="invite-email" 
                                name="email" 
                                placeholder="kollega@bedrift.no" 
                                required
                                class="flex-grow px-4 py-2.5 border border-[#D1CCC3] rounded-lg focus:ring-2 focus:ring-[#F97316] focus:border-[#F97316] bg-white"
                            />
                            <?php echo bimverdi_button([
                                'type' => 'submit',
                                'variant' => 'primary',
                                'icon' => 'send',
                                'text' => 'Send invitasjon',
                                'id' => 'send-invite-btn',
                            ]); ?>
                        </div>
                    </div>
                    
                    <p class="text-sm text-[#5A5A5A] flex items-center gap-2">
                        <?php echo bimverdi_icon('info', 16); ?>
                        Invitasjonen er gyldig i 7 dager. Kollegaen vil motta en e-post med lenke for å registrere seg.
                    </p>
                </form>
                
                <div id="invite-result" class="mt-4 hidden"></div>
            <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <strong class="text-amber-800">Maksimalt antall invitasjoner brukt</strong><br>
                    <span class="text-amber-700">Du kan invitere maksimalt 2 tilleggskontakter. Kontakt <a href="mailto:post@bimverdi.no" class="underline">BIM Verdi</a> hvis du trenger flere.</span>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Current Users -->
    <section class="mb-8">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4 flex items-center gap-2">
            <?php echo bimverdi_icon('users', 20); ?>
            Brukere i <?php echo esc_html($company->post_title); ?>
        </h2>
        
        <div class="bg-white rounded-lg border border-[#E5E2DB] divide-y divide-[#E5E2DB]">
            <?php if (empty($company_users)): ?>
                <div class="p-6 text-center text-[#5A5A5A]">
                    Ingen brukere tilkoblet ennå.
                </div>
            <?php else: ?>
                <?php foreach ($company_users as $user): ?>
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <?php echo get_avatar($user->ID, 40, '', '', array('class' => 'rounded-full')); ?>
                            <div>
                                <div class="font-medium text-[#1A1A1A]">
                                    <?php echo esc_html($user->display_name); ?>
                                    <?php if ($user->is_hovedkontakt): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[#F97316] text-white ml-2">
                                            Hovedkontakt
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-[#5A5A5A]"><?php echo esc_html($user->user_email); ?></div>
                            </div>
                        </div>
                        <div>
                            <?php if (!$user->is_hovedkontakt): ?>
                                <?php echo bimverdi_button([
                                    'variant' => 'danger',
                                    'size' => 'small',
                                    'icon' => 'user-minus',
                                    'text' => 'Fjern tilgang',
                                    'class' => 'remove-user-btn',
                                    'attributes' => [
                                        'data-user-id' => $user->ID,
                                        'data-user-name' => esc_attr($user->display_name),
                                    ],
                                ]); ?>
                            <?php else: ?>
                                <span class="text-sm text-[#9A9488]">Kan ikke fjernes</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Pending Invitations -->
    <?php if (!empty($pending_invitations)): ?>
    <section class="mb-8">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4 flex items-center gap-2">
            <?php echo bimverdi_icon('clock', 20); ?>
            Ventende invitasjoner
        </h2>
        
        <div class="bg-white rounded-lg border border-[#E5E2DB] divide-y divide-[#E5E2DB]">
            <?php foreach ($pending_invitations as $invitation): ?>
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-[#1A1A1A]"><?php echo esc_html($invitation->email); ?></div>
                        <div class="text-sm text-[#5A5A5A]">
                            Sendt <?php echo date('d.m.Y H:i', strtotime($invitation->created_at)); ?>
                            · Utløper <?php echo date('d.m.Y', strtotime($invitation->expires_at)); ?>
                        </div>
                    </div>
                    <?php echo bimverdi_button([
                        'variant' => 'secondary',
                        'size' => 'small',
                        'icon' => 'x',
                        'text' => 'Trekk tilbake',
                        'class' => 'revoke-invite-btn',
                        'attributes' => [
                            'data-invitation-id' => $invitation->id,
                            'data-email' => esc_attr($invitation->email),
                        ],
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Invitation History -->
    <?php 
    $used_invitations = array_filter($all_invitations, function($inv) {
        return $inv->status === 'accepted';
    });
    if (!empty($used_invitations)): 
    ?>
    <section class="mb-8">
        <h2 class="text-lg font-semibold text-[#1A1A1A] mb-4 flex items-center gap-2">
            <?php echo bimverdi_icon('history', 20); ?>
            Invitasjonshistorikk
        </h2>
        
        <div class="bg-white rounded-lg border border-[#E5E2DB] divide-y divide-[#E5E2DB]">
            <?php foreach ($used_invitations as $invitation): ?>
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-[#1A1A1A]"><?php echo esc_html($invitation->email); ?></div>
                        <div class="text-sm text-[#5A5A5A]">
                            Akseptert <?php echo date('d.m.Y H:i', strtotime($invitation->used_at)); ?>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                        <?php echo bimverdi_icon('check', 14); ?>
                        Akseptert
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
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
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800">
                            ${result.data.message}
                        </div>
                    `;
                    inviteForm.reset();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    inviteResult.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">
                            ${result.data.message}
                        </div>
                    `;
                }
            } catch (error) {
                inviteResult.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">
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
