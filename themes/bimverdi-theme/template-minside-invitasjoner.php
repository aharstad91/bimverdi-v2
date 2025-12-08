<?php
/**
 * Template Name: Min Side - Invitasjoner
 * 
 * Template for hovedkontakt to manage company invitations and users
 * 
 * @package BimVerdi_Theme
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$company_id = get_user_meta($user_id, 'bim_verdi_company_id', true);

// Check if user has a company
if (!$company_id) {
    wp_redirect(home_url('/min-side/foretak/'));
    exit;
}

// Check if user is hovedkontakt
$hovedkontakt_id = get_field('hovedkontaktperson', $company_id);
$is_hovedkontakt = ($hovedkontakt_id == $user_id);

if (!$is_hovedkontakt) {
    // Not hovedkontakt - redirect to foretak page with message
    wp_redirect(add_query_arg('ikke_hovedkontakt', '1', home_url('/min-side/foretak/')));
    exit;
}

// Get company data
$company = get_post($company_id);
$er_aktiv_deltaker = get_field('er_aktiv_deltaker', $company_id);

// Get invitations handler
$invitations = bimverdi_get_invitations();
$remaining_invitations = $invitations->get_remaining_invitations($company_id);
$pending_invitations = $invitations->get_pending_invitations($company_id);
$all_invitations = $invitations->get_company_invitations($company_id);
$company_users = $invitations->get_company_users($company_id);

// Start Min Side layout
get_template_part('template-parts/minside-layout-start', null, array(
    'current_page' => 'invitasjoner',
    'page_title' => 'Inviter kolleger',
    'page_icon' => 'user-plus',
    'page_description' => 'Administrer brukertilgang for ' . $company->post_title,
));
?>

<?php if (!$er_aktiv_deltaker): ?>
    <!-- Company not yet approved -->
    <wa-alert variant="warning" class="mb-6">
        <wa-icon slot="icon" name="clock" library="fa"></wa-icon>
        <strong>Foretaket er ikke godkjent ennå</strong><br>
        Du kan ikke sende invitasjoner før foretaket er godkjent av BIM Verdi.
        Ta kontakt på <a href="mailto:post@bimverdi.no" class="underline">post@bimverdi.no</a> hvis dette tar lang tid.
    </wa-alert>
<?php else: ?>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <wa-card>
            <div class="p-4 text-center">
                <div class="text-3xl font-bold text-orange-600"><?php echo count($company_users); ?></div>
                <div class="text-sm text-gray-600">Brukere tilkoblet</div>
            </div>
        </wa-card>
        <wa-card>
            <div class="p-4 text-center">
                <div class="text-3xl font-bold text-purple-600"><?php echo count($pending_invitations); ?></div>
                <div class="text-sm text-gray-600">Ventende invitasjoner</div>
            </div>
        </wa-card>
        <wa-card>
            <div class="p-4 text-center">
                <div class="text-3xl font-bold <?php echo $remaining_invitations > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $remaining_invitations; ?>
                </div>
                <div class="text-sm text-gray-600">Invitasjoner igjen</div>
            </div>
        </wa-card>
    </div>

    <!-- Send New Invitation -->
    <wa-card class="mb-6">
        <div slot="header" class="flex items-center gap-2">
            <wa-icon name="paper-plane" library="fa"></wa-icon>
            <strong>Send invitasjon</strong>
        </div>
        <div class="p-4">
            <?php if ($remaining_invitations > 0): ?>
                <form id="send-invitation-form" class="space-y-4">
                    <?php wp_nonce_field('bimverdi_invitation_nonce', 'nonce'); ?>
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    
                    <div>
                        <label for="invite-email" class="block text-sm font-medium text-gray-700 mb-1">
                            E-postadresse til kollega
                        </label>
                        <div class="flex gap-3">
                            <input 
                                type="email" 
                                id="invite-email" 
                                name="email" 
                                placeholder="kollega@bedrift.no" 
                                required
                                class="flex-grow px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                            />
                            <wa-button type="submit" variant="brand" id="send-invite-btn">
                                <wa-icon slot="prefix" name="paper-plane" library="fa"></wa-icon>
                                Send invitasjon
                            </wa-button>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-500">
                        <wa-icon name="circle-info" library="fa" class="mr-1"></wa-icon>
                        Invitasjonen er gyldig i 7 dager. Kollegaen vil motta en e-post med lenke for å registrere seg.
                    </p>
                </form>
                
                <div id="invite-result" class="mt-4 hidden"></div>
            <?php else: ?>
                <wa-alert variant="warning">
                    <wa-icon slot="icon" name="ban" library="fa"></wa-icon>
                    <strong>Maksimalt antall invitasjoner brukt</strong><br>
                    Du kan invitere maksimalt 2 tilleggskontakter. Kontakt <a href="mailto:post@bimverdi.no" class="underline">BIM Verdi</a> hvis du trenger flere.
                </wa-alert>
            <?php endif; ?>
        </div>
    </wa-card>

    <!-- Current Users -->
    <wa-card class="mb-6">
        <div slot="header" class="flex items-center gap-2">
            <wa-icon name="users" library="fa"></wa-icon>
            <strong>Brukere i <?php echo esc_html($company->post_title); ?></strong>
        </div>
        <div class="divide-y divide-gray-100">
            <?php if (empty($company_users)): ?>
                <div class="p-4 text-center text-gray-500">
                    Ingen brukere tilkoblet ennå.
                </div>
            <?php else: ?>
                <?php foreach ($company_users as $user): ?>
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <?php echo get_avatar($user->ID, 40, '', '', array('class' => 'rounded-full')); ?>
                            <div>
                                <div class="font-medium text-gray-900">
                                    <?php echo esc_html($user->display_name); ?>
                                    <?php if ($user->is_hovedkontakt): ?>
                                        <wa-badge variant="brand" size="small" class="ml-2">Hovedkontakt</wa-badge>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-gray-500"><?php echo esc_html($user->user_email); ?></div>
                            </div>
                        </div>
                        <div>
                            <?php if (!$user->is_hovedkontakt): ?>
                                <wa-button 
                                    variant="danger" 
                                    size="small" 
                                    outline
                                    class="remove-user-btn"
                                    data-user-id="<?php echo $user->ID; ?>"
                                    data-user-name="<?php echo esc_attr($user->display_name); ?>"
                                >
                                    <wa-icon slot="prefix" name="user-minus" library="fa"></wa-icon>
                                    Fjern tilgang
                                </wa-button>
                            <?php else: ?>
                                <span class="text-sm text-gray-400">Kan ikke fjernes</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </wa-card>

    <!-- Pending Invitations -->
    <?php if (!empty($pending_invitations)): ?>
    <wa-card class="mb-6">
        <div slot="header" class="flex items-center gap-2">
            <wa-icon name="clock" library="fa"></wa-icon>
            <strong>Ventende invitasjoner</strong>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($pending_invitations as $invitation): ?>
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-gray-900"><?php echo esc_html($invitation->email); ?></div>
                        <div class="text-sm text-gray-500">
                            Sendt <?php echo date('d.m.Y H:i', strtotime($invitation->created_at)); ?>
                            · Utløper <?php echo date('d.m.Y', strtotime($invitation->expires_at)); ?>
                        </div>
                    </div>
                    <wa-button 
                        variant="warning" 
                        size="small" 
                        outline
                        class="revoke-invite-btn"
                        data-invitation-id="<?php echo $invitation->id; ?>"
                        data-email="<?php echo esc_attr($invitation->email); ?>"
                    >
                        <wa-icon slot="prefix" name="xmark" library="fa"></wa-icon>
                        Trekk tilbake
                    </wa-button>
                </div>
            <?php endforeach; ?>
        </div>
    </wa-card>
    <?php endif; ?>

    <!-- Invitation History -->
    <?php 
    $used_invitations = array_filter($all_invitations, function($inv) {
        return $inv->status === 'accepted';
    });
    if (!empty($used_invitations)): 
    ?>
    <wa-card>
        <div slot="header" class="flex items-center gap-2">
            <wa-icon name="history" library="fa"></wa-icon>
            <strong>Invitasjonshistorikk</strong>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($used_invitations as $invitation): ?>
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-gray-900"><?php echo esc_html($invitation->email); ?></div>
                        <div class="text-sm text-gray-500">
                            Akseptert <?php echo date('d.m.Y H:i', strtotime($invitation->used_at)); ?>
                        </div>
                    </div>
                    <wa-badge variant="success">
                        <wa-icon slot="prefix" name="check" library="fa"></wa-icon>
                        Akseptert
                    </wa-badge>
                </div>
            <?php endforeach; ?>
        </div>
    </wa-card>
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
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<wa-spinner></wa-spinner> Sender...';
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
                        <wa-alert variant="success">
                            <wa-icon slot="icon" name="check" library="fa"></wa-icon>
                            ${result.data.message}
                        </wa-alert>
                    `;
                    inviteForm.reset();
                    // Reload page after 2 seconds to show updated list
                    setTimeout(() => location.reload(), 2000);
                } else {
                    inviteResult.innerHTML = `
                        <wa-alert variant="danger">
                            <wa-icon slot="icon" name="xmark" library="fa"></wa-icon>
                            ${result.data.message}
                        </wa-alert>
                    `;
                }
            } catch (error) {
                inviteResult.innerHTML = `
                    <wa-alert variant="danger">
                        <wa-icon slot="icon" name="xmark" library="fa"></wa-icon>
                        En feil oppstod. Prøv igjen senere.
                    </wa-alert>
                `;
            }
            
            btn.innerHTML = originalContent;
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

<?php 
get_template_part('template-parts/minside-layout-end');
get_footer(); 
?>
