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
    // Søket er display_name LIKE — en lengre streng kan aldri gi treff der
    // en kortere ga null. Husk siste tomme søk så videre skriving på samme
    // prefiks ikke brenner dagsgrensen (50 søk/dag per konto).
    var tomPrefiks = null;

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
        var start = caret - m[0].length;
        // «@» må stå først eller etter blank/«(» — e-postadresser og
        // URL-fragmenter midt i teksten skal aldri trigge søk.
        if (start > 0 && !/[\s(]/.test(foran.charAt(start - 1))) {
            return null;
        }
        return { start: start, tekst: m[1], caret: caret };
    }

    /** Tekst som fortsetter forbi en allerede tagget person er vanlig skriving. */
    function erFerdigMention(tekst) {
        var lower = tekst.toLowerCase();
        return valgte.some(function (v) {
            return lower.indexOf(v.navn.toLowerCase() + ' ') === 0;
        });
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
        // Dropdownen dekker innholdet under tekstfeltet (bl.a. Publiser-
        // knappen), så den åpnes ALDRI uten reelle treff — lastestatus vises
        // kun når lista allerede er åpen og et treffsett raffineres.
        if (!list.hidden) {
            visRad('Søker …', 'bv-mentions-laster');
        }
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
                var data = svar.data || [];
                if (!data.length) {
                    tomPrefiks = token.tekst.toLowerCase();
                    lukk();
                    si('Ingen treff.');
                    return;
                }
                tokenStart = token.start;
                visTreff(data, token);
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
        if (!token
            || token.tekst.length < 2
            || erFerdigMention(token.tekst)
            || (tomPrefiks && token.tekst.toLowerCase().indexOf(tomPrefiks) === 0)) {
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

    /* ------------------------------------------------------------------
     * Krav 34 R2: sjekk før publisering.
     *
     * @-navn som ikke er valgt fra lista, sendes til serveren i ett kall
     * (bimverdi_mention_sjekk — samme serverfunksjon som tagger ved lagring).
     * Unike treff tagges stille. Navn med flere eller ingen treff stopper
     * publiseringen med en advarsel. Feiler kallet, publiseres innlegget
     * uansett (R7) — serveren tagger det den kan ved lagring.
     * ------------------------------------------------------------------ */
    var advarsel = document.createElement('div');
    advarsel.className = 'bv-mentions-advarsel';
    advarsel.setAttribute('role', 'alert');
    advarsel.hidden = true;
    wrap.appendChild(advarsel);

    var sendUtenSjekk = false;
    var sjekkPagar = false;

    /** Indekser for «@» først i teksten eller etter blank/«(», fulgt av bokstav/tall. */
    function finnAlleAt(tekst) {
        var re = /(^|[\s(])@(?=[\p{L}\p{N}])/gu;
        var ut = [];
        var m;
        while ((m = re.exec(tekst)) !== null) {
            ut.push(m.index + m[1].length);
        }
        return ut;
    }

    /** Står et navn valgt fra lista rett etter denne «@»-en? */
    function dekketAvValgt(tekst, i) {
        return valgte.some(function (v) {
            var bit = tekst.substr(i + 1, v.navn.length);
            var etter = tekst.charAt(i + 1 + v.navn.length);
            return bit.toLowerCase() === v.navn.toLowerCase() && !/[\p{L}\p{N}]/u.test(etter);
        });
    }

    function skjulAdvarsel() {
        advarsel.hidden = true;
        advarsel.innerHTML = '';
    }

    function sendSkjema() {
        sendUtenSjekk = true;
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            // WPs knapp heter «submit» og skygger form.submit — kall prototypen.
            HTMLFormElement.prototype.submit.call(form);
        }
    }

    function rettOpp(forste) {
        skjulAdvarsel();
        var tekst = textarea.value;
        var idx = forste ? tekst.indexOf(forste.tekst) : -1;
        var caret = idx >= 0 ? idx + forste.tekst.length : tekst.length;
        textarea.focus();
        textarea.setSelectionRange(caret, caret);
        var token = finnToken();
        if (token && token.tekst.length >= 2) {
            tomPrefiks = null;
            sok(token);
        }
    }

    function lagKnapp(tekst, klasse, handling) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'bv-mentions-knapp ' + klasse;
        b.textContent = tekst;
        b.addEventListener('click', handling);
        return b;
    }

    function visAdvarsel(problemer, maks) {
        advarsel.innerHTML = '';

        if (problemer.length) {
            var tittel = document.createElement('strong');
            tittel.textContent = 'Disse navnene blir ikke tagget og får ikke beskjed:';
            advarsel.appendChild(tittel);

            var ul = document.createElement('ul');
            problemer.forEach(function (p) {
                var li = document.createElement('li');
                li.appendChild(document.createTextNode(p.tekst + ' '));
                var em = document.createElement('em');
                em.textContent = p.status === 'flere'
                    ? '(flere med samme navn — velg riktig i lista)'
                    : '(fant ingen med dette navnet)';
                li.appendChild(em);
                ul.appendChild(li);
            });
            advarsel.appendChild(ul);
        }

        if (maks) {
            var p = document.createElement('p');
            p.textContent = 'Du kan tagge maks ' + maks + ' personer i ett innlegg.';
            advarsel.appendChild(p);
        }

        var knapper = document.createElement('div');
        knapper.className = 'bv-mentions-advarsel-knapper';
        knapper.appendChild(lagKnapp('Publiser likevel', 'bv-mentions-knapp--primar', sendSkjema));
        knapper.appendChild(lagKnapp('Rett opp', '', function () { rettOpp(problemer[0]); }));
        advarsel.appendChild(knapper);

        advarsel.hidden = false;
    }

    textarea.addEventListener('input', function () {
        if (!advarsel.hidden) {
            skjulAdvarsel(); // Teksten er endret — advarselen gjelder ikke lenger.
        }
    });

    form.addEventListener('submit', function (e) {
        if (sendUtenSjekk) {
            return;
        }
        if (sjekkPagar) {
            e.preventDefault();
            return;
        }

        var tekst = textarea.value;
        var udekket = finnAlleAt(tekst).filter(function (i) { return !dekketAvValgt(tekst, i); });
        if (!udekket.length) {
            skjulAdvarsel();
            return; // Ingenting å sjekke — publiser med en gang.
        }

        e.preventDefault();
        sjekkPagar = true;
        lukk();

        var ctrl = typeof AbortController === 'function' ? new AbortController() : null;
        var timer = setTimeout(function () { if (ctrl) { ctrl.abort(); } }, 4000);

        var data = new FormData();
        data.append('action', 'bimverdi_mention_sjekk');
        data.append('nonce', cfg.nonce);
        data.append('tekst', tekst);

        fetch(cfg.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
            signal: ctrl ? ctrl.signal : undefined
        })
            .then(function (r) { return r.json(); })
            .then(function (svar) {
                clearTimeout(timer);
                sjekkPagar = false;
                if (!svar || !svar.success || !svar.data || !Array.isArray(svar.data.treff)) {
                    sendSkjema(); // Rate-grense/feil → publiser uansett (R7).
                    return;
                }

                var problemer = [];
                var sett = {};
                svar.data.treff.forEach(function (t) {
                    if (t.status === 'unik' && t.id) {
                        if (!valgte.some(function (v) { return v.id === t.id; })) {
                            valgte.push({ id: t.id, navn: t.navn });
                        }
                        return;
                    }
                    var nokkel = String(t.navn).toLowerCase();
                    // Valgt fra lista (f.eks. én av to med samme navn) → ikke et problem.
                    if (sett[nokkel] || valgte.some(function (v) { return v.navn.toLowerCase() === nokkel; })) {
                        return;
                    }
                    sett[nokkel] = true;
                    problemer.push(t);
                });
                oppdaterHidden();

                var maks = parseInt(svar.data.maks, 10) || 10;
                var forMange = valgte.length > maks;
                if (!problemer.length && !forMange) {
                    sendSkjema();
                    return;
                }
                visAdvarsel(problemer, forMange ? maks : 0);
            })
            .catch(function () {
                clearTimeout(timer);
                sjekkPagar = false;
                sendSkjema(); // Nettverk/tidsgrense → publiser uansett (R7).
            });
    });
})();
