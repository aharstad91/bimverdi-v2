<?php
/**
 * Announcement Bar - Floating bottom-center pill
 *
 * Dismissible with localStorage persistence.
 * To remove: delete the get_template_part() calls in header.php and header-minside.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$nye_bimverdi_url = home_url('/nye-bimverdi/');
?>

<div id="bv-announcement-bar" class="bv-announcement-bar" style="display:none;">
    <div class="bv-announcement-bar__inner">
        <p class="bv-announcement-bar__text">
            <strong>Velkommen til nye bimverdi.no</strong> &middot; Sidene er under oppdatering.
            <a href="<?php echo esc_url($nye_bimverdi_url); ?>" class="bv-announcement-bar__link">
                Les om hva som er nytt <span aria-hidden="true">&rarr;</span>
            </a>
        </p>
        <button type="button" id="bv-announcement-close" class="bv-announcement-bar__close" aria-label="Lukk">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
</div>

<style>
.bv-announcement-bar {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 60;
    pointer-events: none;
}

.bv-announcement-bar__inner {
    pointer-events: auto;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background-color: #1A1A1A;
    color: #E5E5E5;
    font-size: 0.8125rem;
    line-height: 1.4;
    padding: 12px 16px 12px 20px;
    border-radius: 999px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
    white-space: nowrap;
}

.bv-announcement-bar__text {
    margin: 0;
}

.bv-announcement-bar__link {
    color: #FF8B5E;
    text-decoration: none;
    font-weight: 600;
    margin-left: 2px;
}

.bv-announcement-bar__link:hover {
    color: #FFB08A;
    text-decoration: underline;
}

.bv-announcement-bar__close {
    background: none;
    border: none;
    color: #666666;
    cursor: pointer;
    padding: 4px;
    line-height: 0;
    border-radius: 50%;
    transition: color 0.15s ease;
    flex-shrink: 0;
}

.bv-announcement-bar__close:hover {
    color: #FFFFFF;
}

@media (max-width: 640px) {
    .bv-announcement-bar {
        bottom: 16px;
        left: 16px;
        right: 16px;
        transform: none;
    }
    .bv-announcement-bar__inner {
        white-space: normal;
        border-radius: 16px;
        width: 100%;
        font-size: 0.75rem;
    }
}
</style>

<script>
(function() {
    var STORAGE_KEY = 'bv_announcement_dismissed_v3';
    var bar = document.getElementById('bv-announcement-bar');
    var closeBtn = document.getElementById('bv-announcement-close');

    if (!bar) return;

    try {
        if (localStorage.getItem(STORAGE_KEY)) return;
    } catch (e) {}

    bar.style.display = 'block';

    closeBtn.addEventListener('click', function() {
        bar.style.display = 'none';
        try {
            localStorage.setItem(STORAGE_KEY, '1');
        } catch (e) {}
    });
})();
</script>
