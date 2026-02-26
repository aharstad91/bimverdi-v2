<?php
/**
 * Announcement Bar - Soft launch banner
 *
 * Dark, discreet bar shown at top of every page.
 * Dismissible with localStorage persistence.
 *
 * To remove: delete the get_template_part() calls in header.php and header-minside.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Link to the "nye bimverdi" page
$nye_bimverdi_url = home_url('/nye-bimverdi/');
?>

<div id="bv-announcement-bar" class="bv-announcement-bar" style="display:none;">
    <div class="bv-announcement-bar__inner">
        <p class="bv-announcement-bar__text">
            Velkommen til nye bimverdi.no!
            <a href="<?php echo esc_url($nye_bimverdi_url); ?>" class="bv-announcement-bar__link">
                Les om hva som er nytt <span aria-hidden="true">&rarr;</span>
            </a>
        </p>
        <button type="button" id="bv-announcement-close" class="bv-announcement-bar__close" aria-label="Lukk">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
</div>

<style>
.bv-announcement-bar {
    background-color: #1A1A1A;
    color: #E5E5E5;
    font-size: 0.8125rem;
    line-height: 1;
    position: relative;
    z-index: 60;
}

.bv-announcement-bar__inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 10px 48px 10px 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.bv-announcement-bar__text {
    margin: 0;
    text-align: center;
}

.bv-announcement-bar__link {
    color: #FF8B5E;
    text-decoration: none;
    font-weight: 600;
    margin-left: 6px;
    white-space: nowrap;
}

.bv-announcement-bar__link:hover {
    color: #FFB08A;
    text-decoration: underline;
}

.bv-announcement-bar__close {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #888888;
    cursor: pointer;
    padding: 4px;
    line-height: 0;
    border-radius: 4px;
    transition: color 0.15s ease;
}

.bv-announcement-bar__close:hover {
    color: #FFFFFF;
}

@media (max-width: 640px) {
    .bv-announcement-bar__inner {
        padding: 10px 40px 10px 12px;
    }
    .bv-announcement-bar__text {
        font-size: 0.75rem;
    }
}
</style>

<script>
(function() {
    var STORAGE_KEY = 'bv_announcement_dismissed';
    var bar = document.getElementById('bv-announcement-bar');
    var closeBtn = document.getElementById('bv-announcement-close');

    if (!bar) return;

    // Show bar only if not previously dismissed
    try {
        if (localStorage.getItem(STORAGE_KEY)) return;
    } catch (e) {
        // localStorage unavailable, show anyway
    }

    bar.style.display = 'block';

    closeBtn.addEventListener('click', function() {
        bar.style.display = 'none';
        try {
            localStorage.setItem(STORAGE_KEY, '1');
        } catch (e) {
            // Ignore storage errors
        }
    });
})();
</script>
