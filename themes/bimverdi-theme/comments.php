<?php
/**
 * Diskusjon — kommentarseksjon
 *
 * WordPress' innebygde kommentarmotor, rammet inn som aktør-til-aktør-
 * diskusjon (synk m/ Bård 11.08, plan docs/plans/2026-08-11-001). Pilot på
 * Byggchat-siden; aktivering styres av bimverdi_diskusjon_aktiv() i
 * mu-plugins/bimverdi-still-sporsmal.php.
 *
 * Utlogget: navn/dato/badge er synlige (levende aktivitet), brødtekst og
 * mentions rendres ALDRI til DOM — placeholder-linjer med blur-styling i
 * stedet. Ved 0 kommentarer vises verken teller eller blur (R16).
 * Deep-link fra e-post: ?bvk={comment_id}#comment-{id} — utlogget gir
 * kontekstuell login-CTA, innlogget scrolles/highlightes til kommentaren.
 *
 * Design: UI Contract Variant B (dividers/whitespace, ingen bokser).
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Template-vakt (R17): rendrer kun i aktiv diskusjonskontekst. Uten denne
// ville CPT-poster med gamle godkjente kommentarer vist lista selv om
// aktiveringen er gatet av (WP-fella: closed + count > 0 rendrer likevel).
if (!function_exists('bimverdi_diskusjon_aktiv') || !bimverdi_diskusjon_aktiv(get_the_ID())) {
    return;
}

if (!comments_open() && get_comments_number() == 0) {
    return;
}

/**
 * Ett innlegg i diskusjonen.
 */
if (!function_exists('bimverdi_diskusjon_comment')) {
    function bimverdi_diskusjon_comment($comment, $args, $depth) {
        $is_bimverdi = user_can($comment->user_id, 'manage_options');
        $innlogget   = is_user_logged_in();
        ?>
        <div <?php comment_class('bv-diskusjon-item', $comment); ?> id="comment-<?php comment_ID(); ?>">
            <div class="bv-diskusjon-meta">
                <span class="bv-diskusjon-author"><?php echo esc_html(get_comment_author($comment)); ?></span>
                <?php if ($is_bimverdi): ?>
                <span class="bv-diskusjon-badge">BIM Verdi</span>
                <?php endif; ?>
                <span class="bv-diskusjon-date"><?php echo esc_html(get_comment_date('d.m.Y', $comment)); ?></span>
                <?php if ($comment->comment_approved == '0'): ?>
                <span class="bv-diskusjon-pending">Venter på godkjenning</span>
                <?php endif; ?>
            </div>
            <?php if ($innlogget): ?>
                <div class="bv-diskusjon-content">
                    <?php comment_text($comment); ?>
                </div>
                <?php
                comment_reply_link(array_merge($args, [
                    'depth'      => $depth,
                    'max_depth'  => $args['max_depth'],
                    'reply_text' => 'Svar',
                    'login_text' => 'Logg inn for å svare',
                    'before'     => '<div>',
                    'after'      => '</div>',
                ]), $comment);
                ?>
            <?php else: ?>
                <?php // Innholdet skrives aldri til DOM for utloggede — placeholder, ikke CSS over ekte tekst. ?>
                <div class="bv-diskusjon-skjult" aria-hidden="true">
                    <span class="bv-diskusjon-skjult-linje" style="width: 88%"></span>
                    <span class="bv-diskusjon-skjult-linje" style="width: 71%"></span>
                    <span class="bv-diskusjon-skjult-linje" style="width: 42%"></span>
                </div>
            <?php endif; ?>
        <?php // </div> lukkes av wp_list_comments (style => div)
    }
}

$comment_count = (int) get_comments_number();
$innlogget     = is_user_logged_in();
$permalink     = get_permalink();

// Ingressen tilpasses konteksten. På arrangementer er formålet Bård beskrev
// 20.08 spørsmål før, under og etter — teksten sier det, så deltakerne skjønner
// at tråden lever gjennom hele arrangementet og ikke bare i etterkant.
$bv_diskusjon_ingress = get_post_type() === 'arrangement'
    ? 'Har du spørsmål eller innspill til arrangementet? Still dem her &mdash; før, underveis eller i etterkant. Tagg gjerne inn folk med <span class="font-medium">@navn</span>.'
    : 'Del tanker, meninger og spørsmål med andre i nettverket &mdash; og tagg gjerne inn folk med <span class="font-medium">@navn</span>.';

// Deep-link-mål fra e-postlenke (?bvk={comment_id}) — alltid absint,
// og godtas kun hvis kommentaren finnes, er godkjent og hører til denne siden.
$bvk_id      = absint($_GET['bvk'] ?? 0);
$bvk_comment = $bvk_id ? get_comment($bvk_id) : null;
if (!$bvk_comment
    || $bvk_comment->comment_approved !== '1'
    || (int) $bvk_comment->comment_post_ID !== get_the_ID()) {
    $bvk_comment = null; // Ugyldig/slettet/feil side → stille fallback til generell visning.
}

$login_url = function ($fragment_url) {
    return home_url('/logg-inn/?redirect_to=' . rawurlencode($fragment_url));
};
?>

<style>
/* Diskusjon — egen CSS fordi prekompilert Tailwind mangler arbitrary variants */
#diskusjon .bv-diskusjon-item { padding-top: 24px; scroll-margin-top: 96px; }
#diskusjon .bv-diskusjon-list > .bv-diskusjon-item { border-top: 1px solid #E7E5E4; margin-top: 24px; }
#diskusjon .bv-diskusjon-list > .bv-diskusjon-item:first-child { border-top: 0; margin-top: 0; }
#diskusjon .bv-diskusjon-item .bv-diskusjon-item { padding-left: 24px; border-left: 2px solid #E7E5E4; margin-top: 16px; }
#diskusjon .bv-diskusjon-meta { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; margin-bottom: 6px; }
#diskusjon .bv-diskusjon-author { font-size: 14px; font-weight: 500; color: #111827; }
#diskusjon .bv-diskusjon-badge { font-size: 11px; font-weight: 500; background: #FFF1E9; color: #9A3412; padding: 2px 8px; border-radius: 4px; }
#diskusjon .bv-diskusjon-date { font-size: 12px; color: #78716C; }
#diskusjon .bv-diskusjon-pending { font-size: 12px; color: #854D0E; }
#diskusjon .bv-diskusjon-content { font-size: 14px; color: #57534E; line-height: 1.6; }
#diskusjon .bv-diskusjon-content p { margin-bottom: 8px; }
#diskusjon .bv-diskusjon-content p:last-child { margin-bottom: 0; }
/* Lange innlegg klippes til tre linjer med «Vis mer» — klassen settes av JS,
   så uten JS vises alltid hele innlegget */
#diskusjon .bv-diskusjon-content.bv-diskusjon-klipp { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; line-clamp: 3; overflow: hidden; }
#diskusjon .bv-diskusjon-vis-mer { display: block; margin-top: 6px; padding: 0; border: 0; background: none; font-size: 12px; font-weight: 500; color: #111827; cursor: pointer; transition: color .15s; }
#diskusjon .bv-diskusjon-vis-mer:hover { color: #F97316; }
#diskusjon .comment-reply-link { display: inline-block; margin-top: 8px; font-size: 12px; font-weight: 500; color: #111827; transition: color .15s; }
#diskusjon .comment-reply-link:hover { color: #F97316; }
/* Blurret placeholder for utloggede — pynt over tomme elementer, aldri ekte tekst */
#diskusjon .bv-diskusjon-skjult { display: flex; flex-direction: column; gap: 7px; padding: 2px 0; }
#diskusjon .bv-diskusjon-skjult-linje { display: block; height: 11px; border-radius: 6px; background: linear-gradient(90deg, #D6D3D1, #E7E5E4 60%, #D6D3D1); filter: blur(3px); opacity: .8; }
/* Deep-link-highlight: kort fade når ankeret treffes */
#diskusjon .bv-diskusjon-item:target { animation: bv-diskusjon-highlight 2.5s ease-out 1; }
@keyframes bv-diskusjon-highlight { 0% { background: #FFF1E9; } 100% { background: transparent; } }
#diskusjon .bv-diskusjon-item:focus { outline: none; }
/* Bundet mention i publisert innlegg (rendres av bimverdi-diskusjon-mentions.php) */
#diskusjon .bv-mention { background: #FFF1E9; color: #9A3412; font-weight: 500; padding: 1px 5px; border-radius: 4px; }
/* Mention-autocomplete (bv-mentions.js) */
#diskusjon .bv-mentions-wrap { position: relative; max-width: 720px; }
#diskusjon .bv-mentions-list { position: absolute; top: 2px; left: 0; right: 0; z-index: 30; margin: 0; padding: 4px; list-style: none; background: #fff; border: 1px solid #D6D3D1; border-radius: 8px; box-shadow: 0 8px 24px rgba(17, 24, 39, .10); max-height: 260px; overflow-y: auto; }
#diskusjon .bv-mentions-rad { display: flex; align-items: baseline; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 14px; cursor: pointer; }
#diskusjon .bv-mentions-rad.bv-mentions-aktiv { background: #FFF7ED; }
#diskusjon .bv-mentions-navn { font-weight: 500; color: #111827; }
#diskusjon .bv-mentions-foretak { font-size: 12px; color: #78716C; }
#diskusjon .bv-mentions-tom, #diskusjon .bv-mentions-laster { color: #78716C; cursor: default; font-size: 13px; }
</style>

<section id="diskusjon" class="border-t border-[#E7E5E4] pt-10">
    <h2 class="text-lg font-bold text-[#111827] mb-2">Diskusjon</h2>
    <p class="text-sm text-[#57534E] mb-1 max-w-prose">
        <?php echo wp_kses($bv_diskusjon_ingress, ['span' => ['class' => []]]); ?>
    </p>
    <p class="text-xs text-[#78716C] mb-8 max-w-prose">
        Inntil videre er dette en tjeneste for gratisbrukere og deltakere.
    </p>

    <?php if (!$innlogget && $bvk_comment): ?>
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-8 p-4 bg-[#FFF7ED] border border-[#FED7AA] rounded-lg">
            <p class="text-sm text-[#57534E] flex-1">
                Du følger en lenke til en kommentar fra
                <span class="font-medium text-[#111827]"><?php echo esc_html(get_comment_author($bvk_comment)); ?></span>.
                Logg inn for å se kommentaren.
            </p>
            <?php bimverdi_button([
                'text'    => 'Logg inn',
                'variant' => 'primary',
                'size'    => 'small',
                'href'    => $login_url($permalink . '?bvk=' . $bvk_comment->comment_ID . '#comment-' . $bvk_comment->comment_ID),
            ]); ?>
        </div>
    <?php endif; ?>

    <?php if ($comment_count > 0): ?>
    <div class="mb-10">
        <div class="flex items-baseline justify-between gap-4 pb-2 border-b border-[#E7E5E4]">
            <h3 class="text-xs font-bold text-[#57534E] uppercase tracking-wider">
                <?php echo esc_html($comment_count === 1 ? '1 kommentar' : $comment_count . ' kommentarer'); ?>
            </h3>
            <?php if (!$innlogget): ?>
                <a class="text-xs font-medium text-[#F97316] hover:text-[#EA580C]"
                   href="<?php echo esc_url($login_url($permalink . '#diskusjon')); ?>">Logg inn for å lese</a>
            <?php endif; ?>
        </div>
        <div class="bv-diskusjon-list">
            <?php
            wp_list_comments([
                'style'    => 'div',
                'callback' => 'bimverdi_diskusjon_comment',
            ]);
            ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (comments_open()): ?>
        <?php if ($innlogget): ?>
            <?php
            comment_form([
                'title_reply'         => $comment_count === 0 ? 'Vær den første til å dele en tanke eller et spørsmål' : 'Del en tanke eller et spørsmål',
                'title_reply_to'      => 'Svar til %s',
                'title_reply_before'  => '<h3 id="reply-title" class="text-sm font-bold text-[#111827] mb-3">',
                'title_reply_after'   => '</h3>',
                'cancel_reply_before' => ' <span class="text-xs font-normal">',
                'cancel_reply_after'  => '</span>',
                'cancel_reply_link'   => 'Avbryt svar',
                'comment_field'       => '<p class="comment-form-comment mb-4"><label for="comment" class="screen-reader-text">Innlegg</label><textarea id="comment" name="comment" rows="4" required placeholder="Skriv innlegget ditt her&hellip;" class="w-full max-w-[720px] border border-[#D6D3D1] rounded-lg px-4 py-3 text-sm text-[#111827] placeholder-[#A8A29E] focus:outline-none focus:ring-2 focus:ring-[#F97316]/40 focus:border-[#F97316] bg-white"></textarea></p>',
                'logged_in_as'        => '',
                'comment_notes_before' => '',
                'comment_notes_after' => '',
                'submit_button'       => '<button type="submit" name="%1$s" id="%2$s" class="%3$s bv-btn bv-btn--primary bv-btn--medium">%4$s</button>',
                'submit_field'        => '<p class="form-submit">%1$s %2$s</p>',
                'label_submit'        => 'Publiser innlegg',
                'class_form'          => 'comment-form',
            ]);
            ?>
        <?php else: ?>
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 py-6 <?php echo $comment_count > 0 ? 'border-t border-[#E7E5E4]' : ''; ?>">
                <p class="text-sm text-[#57534E] flex-1">
                    <?php if ($comment_count === 0): ?>
                        Vær den første til å dele en tanke eller et spørsmål &mdash; logg inn eller registrer deg for å delta.
                    <?php else: ?>
                        Logg inn for å lese og delta i diskusjonen.
                    <?php endif; ?>
                </p>
                <div class="flex items-center gap-3">
                    <?php bimverdi_button([
                        'text'    => 'Logg inn',
                        'variant' => 'secondary',
                        'size'    => 'small',
                        'href'    => $login_url($permalink . '#diskusjon'),
                    ]); ?>
                    <a class="text-sm font-medium text-[#F97316] hover:text-[#EA580C]"
                       href="<?php echo esc_url(home_url('/registrer/')); ?>">Registrer deg</a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($innlogget && $comment_count > 0): ?>
<script>
(function () {
    /* Lange innlegg klippes til tre linjer med «Vis mer»/«Vis mindre».
       Klipp-klassen settes her (ikke i PHP) så innlegg aldri kan bli
       utilgjengelige uten JS. */
    document.querySelectorAll('#diskusjon .bv-diskusjon-content').forEach(function (innhold, i) {
        innhold.classList.add('bv-diskusjon-klipp');
        if (innhold.scrollHeight <= innhold.clientHeight + 2) {
            innhold.classList.remove('bv-diskusjon-klipp'); // Får plass — ingen knapp.
            return;
        }
        innhold.id = innhold.id || 'bv-diskusjon-innhold-' + i;
        var knapp = document.createElement('button');
        knapp.type = 'button';
        knapp.className = 'bv-diskusjon-vis-mer';
        knapp.textContent = 'Vis mer';
        knapp.setAttribute('aria-expanded', 'false');
        knapp.setAttribute('aria-controls', innhold.id);
        knapp.addEventListener('click', function () {
            var klippes = innhold.classList.toggle('bv-diskusjon-klipp');
            knapp.textContent = klippes ? 'Vis mer' : 'Vis mindre';
            knapp.setAttribute('aria-expanded', klippes ? 'false' : 'true');
        });
        innhold.insertAdjacentElement('afterend', knapp);
    });

    /* Deep-link-fokus: :target gir visuell highlight; dette gir tastatur-/
       skjermleserbrukere samme signal ved å flytte fokus til kommentaren —
       og folder ut innlegget lenken peker på. */
    var m = window.location.hash.match(/^#comment-\d+$/);
    if (!m) return;
    var el = document.getElementById(window.location.hash.slice(1));
    if (!el) return;
    el.setAttribute('tabindex', '-1');
    el.focus({ preventScroll: true });
    var klippet = el.querySelector(':scope > .bv-diskusjon-content.bv-diskusjon-klipp');
    if (klippet && klippet.nextElementSibling && klippet.nextElementSibling.classList.contains('bv-diskusjon-vis-mer')) {
        klippet.nextElementSibling.click();
    }
})();
</script>
<?php endif; ?>
