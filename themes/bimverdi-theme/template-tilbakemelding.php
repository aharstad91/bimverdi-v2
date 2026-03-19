<?php
/**
 * Template Name: Tilbakemelding
 *
 * Ros & Ris feedback form for BIM Verdi.
 * Follows UI-CONTRACT.md Variant B (Dividers/Whitespace).
 *
 * @package BimVerdi_Theme
 */

get_header();

$status = sanitize_text_field($_GET['status'] ?? '');
$status_messages = [
    'success'         => ['type' => 'success', 'text' => 'Takk for tilbakemeldingen! Vi setter stor pris på at du tar deg tid.'],
    'error'           => ['type' => 'error',   'text' => 'Noe gikk galt. Vennligst prøv igjen.'],
    'rate_limit'      => ['type' => 'warning', 'text' => 'For mange forsøk. Vennligst vent litt før du prøver igjen.'],
    'missing_message' => ['type' => 'error',   'text' => 'Meldingen må være minst 10 tegn.'],
];
$status_info = $status_messages[$status] ?? null;
?>

<main class="bg-white min-h-screen">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-12 md:py-16">

        <!-- Header -->
        <div class="mb-10">
            <p class="text-xs font-semibold tracking-[0.15em] uppercase text-[#FF8B5E] mb-3">Tilbakemelding</p>
            <h1 class="text-3xl md:text-4xl font-light text-[#111827] mb-3" style="letter-spacing: -0.02em;">
                Ros og ris
            </h1>
            <p class="text-[#57534E] text-base leading-relaxed">
                Hjelp oss å bli bedre! Del det som fungerer bra, eller fortell oss hva vi kan forbedre.
                Alle tilbakemeldinger leses av teamet.
            </p>
        </div>

        <?php if ($status_info): ?>
            <?php
            $colors = [
                'success' => 'bg-green-50 border-green-200 text-green-800',
                'error'   => 'bg-red-50 border-red-200 text-red-800',
                'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
            ];
            $color_class = $colors[$status_info['type']] ?? $colors['error'];
            ?>
            <div class="p-4 border rounded-lg text-sm mb-8 <?php echo $color_class; ?>">
                <?php echo esc_html($status_info['text']); ?>
            </div>

            <?php if ($status === 'success'): ?>
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-50 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4a7c29" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <p class="text-[#57534E] mb-6">Vi leser alle tilbakemeldinger og bruker dem til å forbedre BIM Verdi.</p>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center gap-2 text-sm font-medium text-[#FF8B5E] hover:text-[#e87a4f]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Tilbake til forsiden
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($status !== 'success'): ?>
        <form method="post" action="" class="space-y-8">
            <?php wp_nonce_field('bimverdi_feedback'); ?>
            <input type="hidden" name="bimverdi_feedback_submit" value="1">

            <!-- Honeypot -->
            <div style="position:absolute;left:-9999px;" aria-hidden="true">
                <input type="text" name="bv_website_url" tabindex="-1" autocomplete="off">
            </div>

            <!-- Type -->
            <fieldset>
                <legend class="text-sm font-semibold text-[#111827] mb-3">Hva slags tilbakemelding?</legend>
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer">
                        <div class="flex flex-col items-center gap-3 p-4 rounded-lg border-2 border-[#E7E5E4] hover:border-[#4a7c29] hover:bg-green-50/50 transition-all text-center bv-feedback-card" data-color="#4a7c29" data-bg="rgb(240 253 244)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4a7c29" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a3.13 3.13 0 0 1 3 3.88Z"/></svg>
                            <div class="flex items-center gap-2">
                                <input type="radio" name="feedback_type" value="ros" required
                                       class="w-4 h-4 accent-[#4a7c29]">
                                <span class="text-sm font-medium text-[#111827]">Ros</span>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <div class="flex flex-col items-center gap-3 p-4 rounded-lg border-2 border-[#E7E5E4] hover:border-[#c53030] hover:bg-red-50/50 transition-all text-center bv-feedback-card" data-color="#c53030" data-bg="rgb(254 242 242)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c53030" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22h0a3.13 3.13 0 0 1-3-3.88Z"/></svg>
                            <div class="flex items-center gap-2">
                                <input type="radio" name="feedback_type" value="ris"
                                       class="w-4 h-4 accent-[#c53030]">
                                <span class="text-sm font-medium text-[#111827]">Ris</span>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <div class="flex flex-col items-center gap-3 p-4 rounded-lg border-2 border-[#E7E5E4] hover:border-[#FF8B5E] hover:bg-orange-50/50 transition-all text-center bv-feedback-card" data-color="#FF8B5E" data-bg="rgb(255 247 237)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FF8B5E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>
                            <div class="flex items-center gap-2">
                                <input type="radio" name="feedback_type" value="forslag"
                                       class="w-4 h-4 accent-[#FF8B5E]">
                                <span class="text-sm font-medium text-[#111827]">Forslag</span>
                            </div>
                        </div>
                    </label>
                </div>
                <script>
                document.querySelectorAll('.bv-feedback-card').forEach(card => {
                    const radio = card.querySelector('input[type="radio"]');
                    radio.addEventListener('change', () => {
                        document.querySelectorAll('.bv-feedback-card').forEach(c => {
                            c.style.borderColor = '#E7E5E4';
                            c.style.backgroundColor = '';
                        });
                        card.style.borderColor = card.dataset.color;
                        card.style.backgroundColor = card.dataset.bg;
                    });
                });
                </script>
            </fieldset>

            <!-- Message -->
            <div>
                <label for="feedback_message" class="block text-sm font-semibold text-[#111827] mb-2">
                    Din tilbakemelding <span class="text-red-500">*</span>
                </label>
                <textarea id="feedback_message" name="feedback_message" rows="5" required minlength="10"
                          placeholder="Fortell oss hva du tenker..."
                          class="w-full px-4 py-3 border border-[#D6D1C6] rounded-lg text-sm text-[#111827] placeholder:text-[#A8A29E] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent resize-y"
                ></textarea>
                <p class="text-xs text-[#A8A29E] mt-1">Minst 10 tegn</p>
            </div>

            <!-- Optional: Which page -->
            <div>
                <label for="feedback_page" class="block text-sm font-semibold text-[#111827] mb-2">
                    Gjelder en bestemt side? <span class="text-xs font-normal text-[#A8A29E]">(valgfritt)</span>
                </label>
                <input type="text" id="feedback_page" name="feedback_page"
                       placeholder="F.eks. Forsiden, Verktøykatalogen, Min side..."
                       class="w-full px-4 py-3 border border-[#D6D1C6] rounded-lg text-sm text-[#111827] placeholder:text-[#A8A29E] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
            </div>

            <div class="border-t border-[#E7E5E4] pt-6">
                <p class="text-xs font-semibold tracking-[0.1em] uppercase text-[#A8A29E] mb-4">Kontaktinfo (valgfritt)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="feedback_name" class="block text-sm text-[#57534E] mb-1">Navn</label>
                        <input type="text" id="feedback_name" name="feedback_name"
                               <?php if (is_user_logged_in()): ?>value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>"<?php endif; ?>
                               class="w-full px-4 py-3 border border-[#D6D1C6] rounded-lg text-sm text-[#111827] placeholder:text-[#A8A29E] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>
                    <div>
                        <label for="feedback_email" class="block text-sm text-[#57534E] mb-1">E-post</label>
                        <input type="email" id="feedback_email" name="feedback_email"
                               <?php if (is_user_logged_in()): ?>value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>"<?php endif; ?>
                               class="w-full px-4 py-3 border border-[#D6D1C6] rounded-lg text-sm text-[#111827] placeholder:text-[#A8A29E] bg-white focus:outline-none focus:ring-2 focus:ring-[#FF8B5E] focus:border-transparent">
                    </div>
                </div>
                <p class="text-xs text-[#A8A29E] mt-2">Oppgi e-post hvis du vil ha svar på tilbakemeldingen.</p>
            </div>

            <!-- Submit -->
            <div>
                <?php
                if (function_exists('bimverdi_button')) {
                    bimverdi_button([
                        'text'    => 'Send tilbakemelding',
                        'variant' => 'primary',
                        'icon'    => 'send',
                        'type'    => 'submit',
                    ]);
                } else { ?>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-[#1A1A1A] text-white text-sm font-semibold rounded-lg hover:bg-[#333] transition-colors">
                        Send tilbakemelding
                    </button>
                <?php } ?>
            </div>
        </form>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
