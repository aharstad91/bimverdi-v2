(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var widget = document.getElementById('foretak-kobling-widget');
        if (!widget) return;
        new ForetakKobling(widget);
    });

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatOrgnr(orgnr) {
        if (!orgnr) return '';
        return String(orgnr).replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
    }

    function ForetakKobling(container) {
        this.container = container;
        this.searchInput = container.querySelector('[data-search-input]');
        this.resultsContainer = container.querySelector('[data-results]');
        this.detailContainer = container.querySelector('[data-detail]');

        this.restUrl = container.dataset.restUrl;
        this.restNonce = container.dataset.restNonce;
        this.ajaxUrl = container.dataset.ajaxUrl;
        this.autoJoinNonce = container.dataset.autoJoinNonce;
        this.brukerForetakNonce = container.dataset.brukerForetakNonce;
        this.homeUrl = container.dataset.homeUrl || '/';

        this.debounceTimer = null;
        this.isSubmitting = false;

        this.init();
    }

    ForetakKobling.prototype.init = function() {
        var self = this;
        this.searchInput.addEventListener('input', function() {
            self.onInput();
        });
    };

    ForetakKobling.prototype.onInput = function() {
        var self = this;
        clearTimeout(this.debounceTimer);
        var query = this.searchInput.value.trim();

        if (query.length < 3) {
            this.resultsContainer.innerHTML = '';
            this.detailContainer.innerHTML = '';
            return;
        }

        this.debounceTimer = setTimeout(function() {
            self.search(query);
        }, 300);
    };

    ForetakKobling.prototype.search = function(query) {
        var self = this;
        this.resultsContainer.innerHTML = '<p class="px-4 py-3 text-sm text-[#888888]">Søker...</p>';
        this.detailContainer.innerHTML = '';

        fetch(this.restUrl + 'search?query=' + encodeURIComponent(query), {
            headers: { 'X-WP-Nonce': this.restNonce }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            // API returns {success, results} or a plain array
            var companies = Array.isArray(data) ? data : (data && data.results ? data.results : []);
            if (!companies.length) {
                self.resultsContainer.innerHTML = '<p class="px-4 py-3 text-sm text-[#888888]">Ingen treff. Prøv et annet søkeord.</p>';
                return;
            }
            self.renderSearchResults(companies);
        })
        .catch(function() {
            self.resultsContainer.innerHTML = '<p class="px-4 py-3 text-sm text-red-600">Kunne ikke søke. Prøv igjen.</p>';
        });
    };

    ForetakKobling.prototype.renderSearchResults = function(companies) {
        var self = this;
        var html = '';

        for (var i = 0; i < companies.length; i++) {
            var c = companies[i];
            var statusBadge = '';
            if (c.konkurs) {
                statusBadge = '<span class="ml-2 text-xs text-red-600 font-medium">Konkurs</span>';
            } else if (c.under_avvikling) {
                statusBadge = '<span class="ml-2 text-xs text-amber-600 font-medium">Under avvikling</span>';
            }

            html += '<button type="button" '
                + 'class="w-full text-left px-4 py-3 border-b border-[#E7E5E4] last:border-b-0 hover:bg-[#F7F5EF] transition-colors" '
                + 'data-orgnr="' + escapeHtml(c.orgnr) + '" '
                + 'data-navn="' + escapeHtml(c.navn) + '">'
                + '<span class="text-sm font-medium text-[#1A1A1A]">' + escapeHtml(c.navn) + '</span>'
                + statusBadge
                + '<br>'
                + '<span class="text-xs text-[#888888]">Org.nr: ' + escapeHtml(formatOrgnr(c.orgnr)) + '</span>'
                + (c.poststed ? '<span class="text-xs text-[#888888]"> · ' + escapeHtml(c.poststed) + '</span>' : '')
                + '</button>';
        }

        this.resultsContainer.innerHTML = html;

        var buttons = this.resultsContainer.querySelectorAll('button');
        for (var j = 0; j < buttons.length; j++) {
            (function(btn) {
                btn.addEventListener('click', function() {
                    self.selectCompany(btn.dataset.orgnr, btn.dataset.navn);
                });
            })(buttons[j]);
        }
    };

    ForetakKobling.prototype.selectCompany = function(orgnr, navn) {
        var self = this;
        this.detailContainer.innerHTML = '<p class="px-4 py-3 text-sm text-[#888888]">Sjekker...</p>';

        fetch(this.restUrl + 'check-registered/' + encodeURIComponent(orgnr), {
            headers: { 'X-WP-Nonce': this.restNonce }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.is_registered && data.is_deltaker) {
                self.showDeltakerCard(data, navn, orgnr);
            } else {
                self.showBrukerForetakCard(navn, orgnr);
            }
        })
        .catch(function() {
            self.detailContainer.innerHTML = '<p class="px-4 py-3 text-sm text-red-600">Kunne ikke sjekke foretak. Prøv igjen.</p>';
        });
    };

    ForetakKobling.prototype.showDeltakerCard = function(data, navn, orgnr) {
        var self = this;
        this.resultsContainer.innerHTML = '';

        this.detailContainer.innerHTML =
            '<div class="p-5 rounded-lg border border-green-200 bg-green-50">'
            + '<div class="flex items-start gap-3">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">'
            + '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
            + '<div>'
            + '<p class="text-sm font-semibold text-[#1A1A1A]">' + escapeHtml(navn) + '</p>'
            + '<p class="text-xs text-[#5A5A5A] mt-0.5">Org.nr: ' + escapeHtml(formatOrgnr(orgnr)) + ' · Deltaker i BIM Verdi</p>'
            + '<p class="text-sm text-[#5A5A5A] mt-2">'
            + escapeHtml(navn) + ' er deltaker i BIM Verdi! Du vil bli lagt til som tilleggskontakt.'
            + '</p>'
            + '<button type="button" data-action="join" data-foretak-id="' + escapeHtml(String(data.foretak_id)) + '" '
            + 'class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-[#FF8B5E] rounded-lg hover:bg-[#e87a4f] transition-colors">'
            + 'Koble meg til</button>'
            + '</div></div></div>';

        var joinBtn = this.detailContainer.querySelector('[data-action="join"]');
        if (joinBtn) {
            joinBtn.addEventListener('click', function() {
                self.joinDeltaker(joinBtn.dataset.foretakId);
            });
        }
    };

    ForetakKobling.prototype.showBrukerForetakCard = function(navn, orgnr) {
        var self = this;
        this.resultsContainer.innerHTML = '';

        this.detailContainer.innerHTML =
            '<div class="p-5 rounded-lg border border-[#E7E5E4] bg-[#F7F5EF]">'
            + '<div class="flex items-start gap-3">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5">'
            + '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
            + '<div>'
            + '<p class="text-sm font-semibold text-[#1A1A1A]">' + escapeHtml(navn) + '</p>'
            + '<p class="text-xs text-[#5A5A5A] mt-0.5">Org.nr: ' + escapeHtml(formatOrgnr(orgnr)) + ' · Ikke deltaker</p>'
            + '<p class="text-sm text-[#5A5A5A] mt-2">'
            + 'Foretaket er ikke deltaker i BIM Verdi. Du kan likevel koble det til profilen din.'
            + '</p>'
            + '<button type="button" data-action="save-bruker" data-orgnr="' + escapeHtml(orgnr) + '" data-navn="' + escapeHtml(navn) + '" '
            + 'class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[#1A1A1A] bg-white border border-[#D6D1C6] rounded-lg hover:bg-[#F7F5EF] transition-colors">'
            + 'Koble til profil</button>'
            + '</div></div></div>';

        var saveBtn = this.detailContainer.querySelector('[data-action="save-bruker"]');
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                self.saveBrukerForetak(saveBtn.dataset.orgnr, saveBtn.dataset.navn);
            });
        }
    };

    ForetakKobling.prototype.joinDeltaker = function(foretakId) {
        if (this.isSubmitting) return;
        this.isSubmitting = true;
        var self = this;

        var btn = this.detailContainer.querySelector('[data-action="join"]');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Kobler...';
        }

        var formData = new FormData();
        formData.append('action', 'bimverdi_auto_join_foretak');
        formData.append('foretak_id', foretakId);
        formData.append('nonce', this.autoJoinNonce);

        fetch(this.ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                window.location.href = self.homeUrl + 'min-side/?foretak_koblet=deltaker';
            } else {
                self.detailContainer.innerHTML = '<p class="px-4 py-3 text-sm text-red-600">'
                    + escapeHtml(result.data && result.data.message ? result.data.message : 'Noe gikk galt. Prøv å laste siden på nytt.')
                    + '</p>';
                self.isSubmitting = false;
            }
        })
        .catch(function() {
            self.detailContainer.innerHTML = '<p class="px-4 py-3 text-sm text-red-600">Noe gikk galt. Prøv å laste siden på nytt.</p>';
            self.isSubmitting = false;
        });
    };

    ForetakKobling.prototype.saveBrukerForetak = function(orgnr, navn) {
        if (this.isSubmitting) return;
        this.isSubmitting = true;
        var self = this;

        var btn = this.detailContainer.querySelector('[data-action="save-bruker"]');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Lagrer...';
        }

        var formData = new FormData();
        formData.append('action', 'bimverdi_save_bruker_foretak');
        formData.append('orgnr', orgnr);
        formData.append('navn', navn);
        formData.append('nonce', this.brukerForetakNonce);

        fetch(this.ajaxUrl, { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                window.location.href = self.homeUrl + 'min-side/?foretak_koblet=bruker';
            } else {
                self.detailContainer.innerHTML = '<p class="px-4 py-3 text-sm text-red-600">'
                    + escapeHtml(result.data && result.data.message ? result.data.message : 'Noe gikk galt. Prøv å laste siden på nytt.')
                    + '</p>';
                self.isSubmitting = false;
            }
        })
        .catch(function() {
            self.detailContainer.innerHTML = '<p class="px-4 py-3 text-sm text-red-600">Noe gikk galt. Prøv å laste siden på nytt.</p>';
            self.isSubmitting = false;
        });
    };

})();
