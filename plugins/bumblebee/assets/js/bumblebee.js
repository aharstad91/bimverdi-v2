/* Bumblebee – frontend-logikk (uten avhengigheter) */
(function () {
	'use strict';

	if (typeof window.BBEE_CONFIG === 'undefined') {
		return;
	}
	var CFG = window.BBEE_CONFIG;

	function init(widget) {
		if (widget.dataset.bbeeReady === '1') {
			return;
		}
		widget.dataset.bbeeReady = '1';

		var log = widget.querySelector('.bbee-log');
		var form = widget.querySelector('.bbee-form');
		var input = widget.querySelector('.bbee-input');
		var sendBtn = widget.querySelector('.bbee-send');
		var history = [];

		// Låst widget (f.eks. «logg inn»-boks for utloggede) har ingen chatfelt –
		// avslutt trygt så resten av oppstarten (popup-knappen) fortsatt kjører.
		if (!log || !form || !input || !sendBtn) {
			return;
		}

		if (CFG.welcome) {
			addMessage('bot', CFG.welcome, []);
		}

		input.addEventListener('input', function () {
			input.style.height = 'auto';
			input.style.height = Math.min(input.scrollHeight, 120) + 'px';
		});

		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				form.requestSubmit ? form.requestSubmit() : submit();
			}
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			submit();
		});

		function submit() {
			var text = (input.value || '').trim();
			if (!text) {
				return;
			}
			addMessage('user', text, []);
			history.push({ role: 'user', content: text });
			input.value = '';
			input.style.height = 'auto';
			setBusy(true);

			var typing = addTyping();

			fetch(CFG.restUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce },
				body: JSON.stringify({ message: text, history: history.slice(0, -1), nonce: CFG.nonce })
			})
				.then(function (res) {
					return res.json().then(function (data) {
						return { ok: res.ok, data: data };
					});
				})
				.then(function (r) {
					removeNode(typing);
					if (!r.ok) {
						var msg = (r.data && r.data.message) ? r.data.message : CFG.errorMessage;
						addMessage('error', msg, []);
						return;
					}
					var answer = (r.data && r.data.answer) ? r.data.answer : '';
					var sources = (r.data && r.data.sources) ? r.data.sources : [];
					var botEl = addMessage('bot', answer, sources);
					if (r.data && r.data.log_id) {
						botEl.appendChild(renderFeedback(r.data.log_id));
					}
					history.push({ role: 'assistant', content: answer });
				})
				.catch(function () {
					removeNode(typing);
					addMessage('error', CFG.errorMessage, []);
				})
				.then(function () {
					setBusy(false);
					input.focus();
				});
		}

		function setBusy(busy) {
			sendBtn.disabled = busy;
			input.disabled = busy;
		}

		function addMessage(kind, text, sources) {
			var el = document.createElement('div');
			el.className = 'bbee-msg bbee-msg-' + (kind === 'user' ? 'user' : (kind === 'error' ? 'error' : 'bot'));

			if (kind === 'bot') {
				el.appendChild(renderWithCitations(text, sources));
				if (sources && sources.length) {
					el.appendChild(renderSources(sources));
				}
			} else {
				el.textContent = text;
			}

			log.appendChild(el);
			scrollToBottom();
			return el;
		}

		function addTyping() {
			var el = document.createElement('div');
			el.className = 'bbee-typing';
			el.setAttribute('aria-label', CFG.thinkingLabel || '');
			el.innerHTML = '<span></span><span></span><span></span>';
			log.appendChild(el);
			scrollToBottom();
			return el;
		}

		function removeNode(node) {
			if (node && node.parentNode) {
				node.parentNode.removeChild(node);
			}
		}

		function scrollToBottom() {
			log.scrollTop = log.scrollHeight;
		}
	}

	function renderWithCitations(text, sources) {
		var frag = document.createDocumentFragment();
		var byN = {};
		(sources || []).forEach(function (s) {
			byN[String(s.n)] = s;
		});

		var re = /\[(\d+)\]/g;
		var lastIndex = 0;
		var m;
		text = String(text == null ? '' : text);

		while ((m = re.exec(text)) !== null) {
			if (m.index > lastIndex) {
				frag.appendChild(document.createTextNode(text.slice(lastIndex, m.index)));
			}
			var n = m[1];
			var src = byN[n];
			if (src && src.url) {
				var a = document.createElement('a');
				a.className = 'bbee-cite';
				a.href = src.url;
				a.target = '_blank';
				a.rel = 'noopener';
				a.textContent = '[' + n + ']';
				a.title = src.title || '';
				frag.appendChild(a);
			} else {
				frag.appendChild(document.createTextNode(m[0]));
			}
			lastIndex = re.lastIndex;
		}
		if (lastIndex < text.length) {
			frag.appendChild(document.createTextNode(text.slice(lastIndex)));
		}
		return frag;
	}

	function renderFeedback(logId) {
		var wrap = document.createElement('div');
		wrap.className = 'bbee-feedback';

		var label = document.createElement('span');
		label.className = 'bbee-feedback-label';
		label.textContent = window.BBEE_CONFIG.feedbackLabel || 'Var svaret nyttig?';
		wrap.appendChild(label);

		function send(value, btn) {
			var buttons = wrap.querySelectorAll('button');
			for (var i = 0; i < buttons.length; i++) {
				buttons[i].disabled = true;
			}
			btn.classList.add('is-selected');
			fetch(window.BBEE_CONFIG.feedbackUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.BBEE_CONFIG.nonce },
				body: JSON.stringify({ log_id: logId, value: value, nonce: window.BBEE_CONFIG.nonce })
			}).catch(function () { /* stille feil: tilbakemelding er valgfritt */ });
			label.textContent = window.BBEE_CONFIG.feedbackThanks || 'Takk!';
		}

		[['up', '\uD83D\uDC4D', window.BBEE_CONFIG.feedbackUp || 'Nyttig'], ['down', '\uD83D\uDC4E', window.BBEE_CONFIG.feedbackDown || 'Ikke nyttig']].forEach(function (def) {
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'bbee-feedback-btn';
			b.textContent = def[1];
			b.title = def[2];
			b.setAttribute('aria-label', def[2]);
			b.addEventListener('click', function () { send(def[0], b); });
			wrap.appendChild(b);
		});

		return wrap;
	}

	function renderSources(sources) {
		var wrap = document.createElement('div');
		wrap.className = 'bbee-sources';

		var label = document.createElement('div');
		label.className = 'bbee-sources-label';
		label.textContent = (window.BBEE_CONFIG.sourcesLabel || 'Kilder');
		wrap.appendChild(label);

		sources.forEach(function (s) {
			var a = document.createElement('a');
			a.className = 'bbee-source';
			a.href = s.url || '#';
			a.target = '_blank';
			a.rel = 'noopener';

			var num = document.createElement('span');
			num.className = 'bbee-source-n';
			num.textContent = String(s.n);

			var title = document.createElement('span');
			title.className = 'bbee-source-title';
			title.textContent = s.title || s.url || '';

			a.appendChild(num);
			a.appendChild(title);

			if (s.group) {
				var badge = document.createElement('span');
				badge.className = 'bbee-source-group';
				badge.textContent = s.group;
				a.appendChild(badge);
			}

			wrap.appendChild(a);
		});

		return wrap;
	}

	function boot() {
		var widgets = document.querySelectorAll('.bbee-widget');
		for (var i = 0; i < widgets.length; i++) {
			try {
				init(widgets[i]);
			} catch (e) {
				/* ikke la én widget hindre popup-knappen i å fungere */
			}
		}
		bootPopups();
	}

	function bootPopups() {
		var popups = document.querySelectorAll('.bbee-popup');
		for (var i = 0; i < popups.length; i++) {
			initPopup(popups[i]);
		}
	}

	function initPopup(popup) {
		if (popup.dataset.bbeeReady === '1') {
			return;
		}
		popup.dataset.bbeeReady = '1';

		var launcher = popup.querySelector('.bbee-launcher');
		var panel = popup.querySelector('.bbee-popup-panel');
		if (!launcher || !panel) {
			return;
		}

		function toggle(open) {
			panel.hidden = !open;
			launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) {
				var inp = panel.querySelector('.bbee-input');
				if (inp) {
					inp.focus();
				}
			}
		}

		launcher.addEventListener('click', function () {
			toggle(panel.hidden);
		});

		var closeBtn = panel.querySelector('.bbee-close');
		if (closeBtn) {
			closeBtn.addEventListener('click', function () {
				toggle(false);
				launcher.focus();
			});
		}

		// Esc lukker panelet.
		panel.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				toggle(false);
				launcher.focus();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
