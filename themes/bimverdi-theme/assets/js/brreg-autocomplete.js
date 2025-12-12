/**
 * BIM Verdi - BRreg Autocomplete
 * 
 * Provides autocomplete functionality for company registration
 * using the Brønnøysund Register API.
 * 
 * Søker på foretaksnavn (3+ tegn) og viser resultater med navn + orgnr.
 * Pre-populerer skjemafelt automatisk ved valg.
 */

(function() {
    'use strict';
    
    // Flag to prevent search when filling form programmatically
    let isFillingForm = false;
    
    // Wait for DOM
    document.addEventListener('DOMContentLoaded', function() {
        initBrregAutocomplete();
    });
    
    function initBrregAutocomplete() {
        // Find the company name field (foretak) - this is the primary search field
        // Look for "Foretak" field first (Field ID 2 in Form 2)
        const foretakField = document.querySelector(
            'input[id*="input_2_2"], ' +
            'input[name*="foretak" i], ' +
            'input[name*="bedriftsnavn" i]'
        );
        
        // Also get the org number field for filling (Field ID 1 in Form 2)
        const orgnrField = document.querySelector(
            'input[id*="input_2_1"], ' +
            'input[name*="organisasjonsnummer" i]'
        );
        
        // Use foretak field as primary search, fall back to orgnr if not found
        const searchField = foretakField || orgnrField;
        
        if (!searchField) {
            console.log('BRreg: Search field not found');
            return;
        }
        
        console.log('BRreg: Initializing autocomplete on', searchField.name || searchField.id);
        
        // Create autocomplete container
        const container = createAutocompleteContainer(searchField);
        
        // Add search functionality
        let debounceTimer;
        searchField.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            
            // Skip search if we're programmatically filling the form
            if (isFillingForm) {
                return;
            }
            
            const query = e.target.value.trim();
            
            // Trigger search after 3 characters
            if (query.length < 3) {
                hideResults(container);
                return;
            }
            
            debounceTimer = setTimeout(() => {
                searchBrreg(query, container, searchField, orgnrField);
            }, 300);
        });
        
        // Hide results on blur (with delay for click)
        searchField.addEventListener('blur', function() {
            setTimeout(() => hideResults(container), 250);
        });
        
        // Show results on focus if we have cached results
        searchField.addEventListener('focus', function() {
            if (container.querySelector('.brreg-result')) {
                container.style.display = 'block';
            }
        });
        
        // Also listen on org number field for direct org number lookup
        if (orgnrField && orgnrField !== searchField) {
            orgnrField.addEventListener('input', function(e) {
                const value = e.target.value.replace(/\s/g, '');
                // If 9 digits, do a direct lookup
                if (/^\d{9}$/.test(value)) {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        lookupByOrgnr(value, orgnrField);
                    }, 300);
                }
            });
        }
    }
    
    // Direct lookup by org number
    async function lookupByOrgnr(orgnr, inputField) {
        try {
            if (typeof bimverdiBrreg === 'undefined') return;
            
            const response = await fetch(
                `${bimverdiBrreg.restUrl}company/${orgnr}`,
                {
                    headers: {
                        'X-WP-Nonce': bimverdiBrreg.nonce
                    }
                }
            );
            
            const data = await response.json();
            
            if (data.success && data.company) {
                // Check if already registered
                const checkResponse = await fetch(
                    `${bimverdiBrreg.restUrl}check-registered/${orgnr}`,
                    {
                        headers: {
                            'X-WP-Nonce': bimverdiBrreg.nonce
                        }
                    }
                );
                const checkData = await checkResponse.json();
                
                if (checkData.is_registered) {
                    showWarningMessage(inputField, `Dette foretaket er allerede registrert: ${checkData.foretak_name}`);
                    return;
                }
                
                fillFormFields(data.company);
                showSuccessMessage(inputField, data.company.navn);
            }
        } catch (error) {
            console.error('BRreg lookup error:', error);
        }
    }
    
    function createAutocompleteContainer(inputField) {
        const container = document.createElement('div');
        container.className = 'brreg-autocomplete-container';
        container.style.cssText = `
            position: absolute;
            z-index: 1000;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-height: 300px;
            overflow-y: auto;
            display: none;
            width: 100%;
            max-width: 500px;
            margin-top: 4px;
        `;
        
        // Position relative to input
        const wrapper = inputField.parentElement;
        wrapper.style.position = 'relative';
        wrapper.appendChild(container);
        
        return container;
    }
    
    async function searchBrreg(query, container, inputField, orgnrField) {
        // Show loading
        container.innerHTML = `
            <div class="brreg-loading" style="padding: 1rem; color: #6b7280; display: flex; align-items: center; gap: 0.5rem;">
                <span style="animation: spin 1s linear infinite; display: inline-block;">⏳</span>
                Søker i Brønnøysundregistrene...
            </div>
        `;
        container.style.display = 'block';
        
        try {
            // Check if bimverdiBrreg is available
            if (typeof bimverdiBrreg === 'undefined') {
                throw new Error('BRreg API configuration not found');
            }
            
            const response = await fetch(
                `${bimverdiBrreg.restUrl}search?query=${encodeURIComponent(query)}`,
                {
                    headers: {
                        'X-WP-Nonce': bimverdiBrreg.nonce
                    }
                }
            );
            
            const data = await response.json();
            
            if (!data.success || !data.results || data.results.length === 0) {
                container.innerHTML = `
                    <div class="brreg-no-results" style="padding: 1rem; color: #6b7280;">
                        <p style="margin: 0 0 0.5rem 0;">Ingen treff på "${escapeHtml(query)}"</p>
                        <p style="margin: 0; font-size: 0.75rem; color: #9ca3af;">
                            Tips: Prøv å søke på deler av foretaksnavnet
                        </p>
                    </div>
                `;
                return;
            }
            
            renderResults(data.results, container, inputField, orgnrField);
            
        } catch (error) {
            console.error('BRreg search error:', error);
            container.innerHTML = `
                <div class="brreg-error" style="padding: 1rem; color: #dc2626;">
                    <p style="margin: 0;">Feil ved søk. Prøv igjen.</p>
                </div>
            `;
        }
    }
    
    function renderResults(results, container, inputField, orgnrField) {
        container.innerHTML = '';
        
        // Add header
        const header = document.createElement('div');
        header.style.cssText = `
            padding: 0.5rem 1rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.75rem;
            color: #6b7280;
        `;
        header.textContent = `${results.length} treff funnet`;
        container.appendChild(header);
        
        results.forEach(company => {
            const item = document.createElement('div');
            item.className = 'brreg-result';
            item.style.cssText = `
                padding: 0.75rem 1rem;
                cursor: pointer;
                border-bottom: 1px solid #f3f4f6;
                transition: background 0.15s;
            `;
            
            // Status badges
            let statusBadge = '';
            if (company.konkurs) {
                statusBadge = '<span style="background:#fef2f2;color:#dc2626;padding:2px 6px;border-radius:4px;font-size:0.7rem;margin-left:0.5rem;">Konkurs</span>';
            } else if (company.under_avvikling) {
                statusBadge = '<span style="background:#fef3c7;color:#d97706;padding:2px 6px;border-radius:4px;font-size:0.7rem;margin-left:0.5rem;">Under avvikling</span>';
            }
            
            // Format org number with spaces for readability
            const formattedOrgnr = company.orgnr.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
            
            item.innerHTML = `
                <div style="font-weight: 600; color: #1f2937; margin-bottom: 2px;">
                    ${escapeHtml(company.navn)}${statusBadge}
                </div>
                <div style="font-size: 0.875rem; color: #6b7280;">
                    <span style="font-family: monospace; background: #f3f4f6; padding: 1px 4px; border-radius: 3px;">
                        ${formattedOrgnr}
                    </span>
                    <span style="margin-left: 0.5rem;">${escapeHtml(company.organisasjonsform || '')}</span>
                </div>
                ${company.poststed ? `<div style="font-size: 0.75rem; color: #9ca3af; margin-top: 2px;">📍 ${escapeHtml(company.poststed)}</div>` : ''}
            `;
            
            item.addEventListener('mouseenter', () => {
                item.style.background = '#f0fdf4';
            });
            
            item.addEventListener('mouseleave', () => {
                item.style.background = 'white';
            });
            
            item.addEventListener('click', () => {
                selectCompany(company, inputField, container, orgnrField);
            });
            
            container.appendChild(item);
        });
    }
    
    async function selectCompany(company, inputField, container, orgnrField) {
        // First check if already registered
        try {
            const checkResponse = await fetch(
                `${bimverdiBrreg.restUrl}check-registered/${company.orgnr}`,
                {
                    headers: {
                        'X-WP-Nonce': bimverdiBrreg.nonce
                    }
                }
            );
            
            const checkData = await checkResponse.json();
            
            if (checkData.is_registered) {
                showWarningMessage(inputField, `Dette foretaket er allerede registrert i BIM Verdi: ${checkData.foretak_name}`);
                hideResults(container);
                return;
            }
        } catch (error) {
            console.error('Check registered error:', error);
        }
        
        // Fill form fields with all available data
        fillFormFields(company);
        
        // Hide autocomplete
        hideResults(container);
        
        // Show success message
        showSuccessMessage(inputField, company.navn);
    }
    
    function fillFormFields(company) {
        console.log('BRreg: Filling form with company data:', company);
        
        // Set flag to prevent search from triggering during fill
        isFillingForm = true;
        
        // Field mappings for Gravity Forms Form ID 2
        // Format: [CSS selector, value, field description]
        const fieldMappings = [
            // Org number (Field 1)
            ['input[id*="input_2_1"]', company.orgnr, 'orgnr'],
            ['input[name*="organisasjonsnummer" i]', company.orgnr, 'orgnr-name'],
            
            // Company name / Foretak (Field 2)
            ['input[id*="input_2_2"]', company.navn, 'navn'],
            ['input[name*="foretak" i]', company.navn, 'foretak-name'],
            ['input[name*="bedriftsnavn" i]', company.navn, 'bedriftsnavn-name'],
            
            // Address (Field 5)
            ['input[id*="input_2_5"]', company.adresse, 'adresse'],
            ['input[name*="gateadresse" i]', company.adresse, 'adresse-name'],
            
            // Postal code (Field 6)
            ['input[id*="input_2_6"]', company.postnummer, 'postnummer'],
            ['input[name*="postnummer" i]', company.postnummer, 'postnummer-name'],
            
            // City (Field 7)
            ['input[id*="input_2_7"]', company.poststed, 'poststed'],
            ['input[name*="poststed" i]', company.poststed, 'poststed-name'],
            
            // Website (Field 8)
            ['input[id*="input_2_8"]', company.hjemmeside, 'hjemmeside'],
            ['input[name*="nettside" i]', company.hjemmeside, 'nettside-name'],
        ];
        
        let filledFields = [];
        
        // Try to fill each field
        for (const [selector, value, description] of fieldMappings) {
            if (!value) continue;
            
            // Try to find the field
            const fields = document.querySelectorAll(selector);
            
            fields.forEach(field => {
                if (field && !field.disabled) {
                    field.value = value;
                    // Trigger events for any listeners
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    filledFields.push(description);
                }
            });
        }
        
        console.log('BRreg: Filled fields:', filledFields);
        
        // Store company data for later use
        window.selectedBrregCompany = company;
        
        // Reset flag after a short delay to allow events to complete
        setTimeout(() => {
            isFillingForm = false;
        }, 100);
    }
    
    function showSuccessMessage(inputField, companyName) {
        // Remove any existing message
        removeMessages();
        
        const msg = document.createElement('div');
        msg.className = 'brreg-success-msg';
        msg.style.cssText = `
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        `;
        msg.innerHTML = `
            <span style="font-size: 1.25rem;">✓</span>
            <span>Hentet data for <strong>${escapeHtml(companyName)}</strong> fra Brønnøysundregistrene</span>
        `;
        
        // Find the form wrapper or use inputField's parent
        const form = inputField.closest('form') || inputField.closest('.gform_wrapper');
        if (form) {
            const firstField = form.querySelector('.gfield, .gform_body');
            if (firstField) {
                firstField.parentNode.insertBefore(msg, firstField);
            } else {
                inputField.parentElement.appendChild(msg);
            }
        } else {
            inputField.parentElement.appendChild(msg);
        }
        
        // Remove after 6 seconds
        setTimeout(() => msg.remove(), 6000);
    }
    
    function showWarningMessage(inputField, message) {
        // Remove any existing message
        removeMessages();
        
        const msg = document.createElement('div');
        msg.className = 'brreg-warning-msg';
        msg.style.cssText = `
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        `;
        msg.innerHTML = `
            <span style="font-size: 1.25rem;">⚠️</span>
            <span>${escapeHtml(message)}</span>
        `;
        
        inputField.parentElement.appendChild(msg);
        
        // Remove after 8 seconds
        setTimeout(() => msg.remove(), 8000);
    }
    
    function removeMessages() {
        document.querySelectorAll('.brreg-success-msg, .brreg-warning-msg').forEach(el => el.remove());
    }
    
    function hideResults(container) {
        container.style.display = 'none';
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Add keyframe animation for loading spinner
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .brreg-result:focus {
            outline: 2px solid #3b82f6;
            outline-offset: -2px;
        }
    `;
    document.head.appendChild(style);
})();
