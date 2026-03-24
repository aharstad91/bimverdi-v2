<?php
/**
 * Cookie Consent Banner
 *
 * Sticky bottom bar for GDPR cookie consent.
 * Shows until user accepts or rejects. Stores choice in localStorage.
 * Controls loading of analytics scripts (GA4).
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;
?>

<div id="bv-cookie-consent" style="display:none;">
    <style>
        #bv-cookie-consent {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: #1A1A1A;
            color: #E7E5E4;
            border-top: 1px solid #333;
            padding: 16px 24px;
            font-family: 'Inter', -apple-system, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        #bv-cookie-consent .bv-cc-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        #bv-cookie-consent .bv-cc-text {
            flex: 1;
            min-width: 0;
        }
        #bv-cookie-consent .bv-cc-text a {
            color: #FF8B5E;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        #bv-cookie-consent .bv-cc-text a:hover {
            color: #FFBFA8;
        }
        #bv-cookie-consent .bv-cc-buttons {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }
        #bv-cookie-consent .bv-cc-btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }
        #bv-cookie-consent .bv-cc-accept {
            background: #FF8B5E;
            color: #fff;
        }
        #bv-cookie-consent .bv-cc-accept:hover {
            background: #E67A4E;
        }
        #bv-cookie-consent .bv-cc-reject {
            background: transparent;
            color: #E7E5E4;
            border: 1px solid #555;
        }
        #bv-cookie-consent .bv-cc-reject:hover {
            background: #333;
            border-color: #888;
        }
        @media (max-width: 640px) {
            #bv-cookie-consent .bv-cc-inner {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            #bv-cookie-consent .bv-cc-buttons {
                justify-content: stretch;
            }
            #bv-cookie-consent .bv-cc-btn {
                flex: 1;
                text-align: center;
            }
        }
    </style>

    <div class="bv-cc-inner">
        <div class="bv-cc-text">
            Vi bruker informasjonskapsler for å analysere trafikk og forbedre opplevelsen.
            <a href="<?php echo esc_url(home_url('/personvernerklaering/')); ?>">Les mer</a>
        </div>
        <div class="bv-cc-buttons">
            <button class="bv-cc-btn bv-cc-reject" onclick="bvCookieConsent('rejected')">Avvis</button>
            <button class="bv-cc-btn bv-cc-accept" onclick="bvCookieConsent('accepted')">Godta</button>
        </div>
    </div>
</div>

<script>
(function() {
    var consent = localStorage.getItem('bv_cookie_consent');

    // Show banner if no choice made
    if (!consent) {
        document.getElementById('bv-cookie-consent').style.display = 'block';
    }

    // Load analytics if previously accepted
    if (consent === 'accepted') {
        bvLoadAnalytics();
    }

    window.bvCookieConsent = function(choice) {
        localStorage.setItem('bv_cookie_consent', choice);
        localStorage.setItem('bv_cookie_consent_date', new Date().toISOString());
        document.getElementById('bv-cookie-consent').style.display = 'none';

        if (choice === 'accepted') {
            bvLoadAnalytics();
        }
    };

    function bvLoadAnalytics() {
        // Prevent double-loading
        if (window.bvAnalyticsLoaded) return;
        window.bvAnalyticsLoaded = true;

        var script = document.createElement('script');
        script.async = true;
        script.src = 'https://www.googletagmanager.com/gtag/js?id=G-QJJEWJBXZ7';
        document.head.appendChild(script);

        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', 'G-QJJEWJBXZ7');
    }
})();
</script>
