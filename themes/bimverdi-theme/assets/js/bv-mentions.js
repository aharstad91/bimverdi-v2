/**
 * BIM Verdi — @-mentions i diskusjonstråden (pilot: Byggchat).
 *
 * Mønster: assets/js/brreg-autocomplete.js (debounce + dropdown), tilpasset
 * flerlinjers textarea der «@» kan stå midt i teksten. Dropdownen legges
 * under tekstfeltet (forutsigbart også med skjermtastatur på mobil).
 *
 * Bindingen {id, navn} sendes i skjult felt `bv_mentions`; serveren
 * (mu-plugins/bimverdi-diskusjon-mentions.php) validerer alt på nytt —
 * feiler noe her, faller teksten stille tilbake til ren tekst og skriving
 * blokkeres aldri (R7).
 */
(function () {
    'use strict';

    var cfg = window.bvMentions || {};
    var textarea = document.getElementById('comment');
    if (!textarea || !textarea.form || !cfg.ajaxUrl || !cfg.nonce) {
        return;
    }
    var form = textarea.form;

    // Skjult felt med valgte bindinger.
    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'bv_mentions';
    hidden.value = '[]';
    form.appendChild(hidden);
    var valgte = [];

    // Dropdown (listbox) + live-region for skjermlesere.
    var wrap = document.createElement('div');
    wrap.className = 'bv-mentions-wrap';
    textarea.parentNode.insertBefore(wrap, textarea.nextSibling);

    var list = document.createElement('ul');
    list.id = 'bv-mentions-list';
    list.className = 'bv-mentions-list';
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', 'Forslag til personer');
    list.hidden = true;
    wrap.appendChild(list);

    var live = document.createElement('div');
    live.className = 'sr-only';
    live.setAttribute('aria-live', 'polite');
    wrap.appendChild(live);

    textarea.setAttribute('aria-controls', 'bv-mentions-list');
    textarea.setAttribute('aria-expanded', 'false');

    var treff = [];
    var aktivIndex = -1;
    var tokenStart = -1;
    var debounceTimer = null;
    var reqSeq = 0;

    function si(melding) {
        live.textContent = melding;
    }

    function lukk() {
        list.hidden = true;
        list.innerHTML = '';
        treff = [];
        aktivIndex = -1;
        tokenStart = -1;
        textarea.setAttribute('aria-expanded', 'false');
        textarea.removeAttribute('aria-activedescendant');
    }

    function oppdaterHidden() {
        hidden.value = JSON.stringify(valgte);
    }

    /** Aktivt @-token rett før markøren, eller null. Navn kan ha mellomrom. */
    function finnToken() {
        var caret = textarea.selectionStart;
        var foran = textarea.value.slice(0, caret);
        var m = foran.match(/@([\p{L}\p{N}][\p{L}\p{N} .\-]{0,40})$/u);
        if (!m) {
            return null;
        }
        return { start: caret - m[0].length, tekst: m[1], caret: caret };
    }

    function visRad(tekst, klasse) {
        list.innerHTML = '';
        var li = document.createElement('li');
        li.className = 'bv-mentions-rad ' + klasse;
        li.setAttribute('role', 'option');
        li.setAttribute('aria-disabled', 'true');
        li.textContent = tekst;
        list.appendChild(li);
        list.hidden = false;
        textarea.setAttribute('aria-expanded', 'true');
    }

    function markerAktiv(index) {
        aktivIndex = index;
        var rader = list.querySelectorAll('[data-index]');
        rader.forEach(function (rad) {
            var er = parseInt(rad.dataset.index, 10) === index;
            rad.classList.toggle('bv-mentions-aktiv', er);
            rad.setAttribute('aria-selected', er ? 'true' : 'false');
            if (er) {
                textarea.setAttribute('aria-activedescendant', rad.id);
            }
        });
    }

    function velg(index) {
        var person = treff[index];
        if (!person || tokenStart < 0) {
            return;
        }
        var caret = textarea.selectionStart;
        var innsetting = '@' + person.navn + ' ';
        textarea.value = textarea.value.slice(0, tokenStart) + innsetting + textarea.value.slice(caret);
        var nyCaret = tokenStart + innsetting.length;
        textarea.setSelectionRange(nyCaret, nyCaret);
        textarea.focus();

        if (!valgte.some(function (v) { return v.id === person.id; })) {
            valgte.push({ id: person.id, navn: person.navn });
            oppdaterHidden();
        }
        si(person.navn + ' er tagget.');
        lukk();
    }

    function visTreff(data, token) {
        treff = data;
        list.innerHTML = '';
        if (!treff.length) {
            visRad('Ingen treff', 'bv-mentions-tom');
            si('Ingen treff.');
            return;
        }
        treff.forEach(function (person, i) {
            var li = document.createElement('li');
            li.className = 'bv-mentions-rad';
            li.id = 'bv-mention-valg-' + i;
            li.dataset.index = i;
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', 'false');

            var navn = document.createElement('span');
            navn.className = 'bv-mentions-navn';
            navn.textContent = person.navn;
            li.appendChild(navn);

            if (person.foretak) {
                var foretak = document.createElement('span');
                foretak.className = 'bv-mentions-foretak';
                foretak.textContent = person.foretak;
                li.appendChild(foretak);
            }

            // mousedown, ikke click — ellers mister textarea fokus/caret først.
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                tokenStart = token.start;
                velg(i);
            });
            list.appendChild(li);
        });
        list.hidden = false;
        textarea.setAttribute('aria-expanded', 'true');
        markerAktiv(0);
        si(treff.length + (treff.length === 1 ? ' treff.' : ' treff.') + ' Bruk piltastene og Enter.');
    }

    function sok(token) {
        var seq = ++reqSeq;
        visRad('Søker …', 'bv-mentions-laster');
        si('Søker …');
        var url = cfg.ajaxUrl
            + '?action=bimverdi_mention_sok'
            + '&nonce=' + encodeURIComponent(cfg.nonce)
            + '&q=' + encodeURIComponent(token.tekst);

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (svar) {
                if (seq !== reqSeq) {
                    return; // Utdatert respons.
                }
                if (!svar || !svar.success) {
                    lukk(); // Rate-limit/feil → stille fallback til ren tekst.
                    return;
                }
                tokenStart = token.start;
                visTreff(svar.data || [], token);
            })
            .catch(function () {
                if (seq === reqSeq) {
                    lukk(); // Nettverksfeil → skriving blokkeres aldri.
                }
            });
    }

    textarea.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var token = finnToken();
        if (!token || token.tekst.length < 2) {
            lukk();
            return;
        }
        debounceTimer = setTimeout(function () { sok(token); }, 250);
    });

    textarea.addEventListener('keydown', function (e) {
        if (list.hidden || !treff.length) {
            if (e.key === 'Escape' && !list.hidden) {
                lukk();
            }
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            markerAktiv((aktivIndex + 1) % treff.length);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            markerAktiv((aktivIndex - 1 + treff.length) % treff.length);
        } else if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            velg(aktivIndex >= 0 ? aktivIndex : 0);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            lukk();
        }
    });

    textarea.addEventListener('blur', function () {
        // Liten forsinkelse så mousedown i lista rekker å kjøre.
        setTimeout(lukk, 150);
    });
})();
