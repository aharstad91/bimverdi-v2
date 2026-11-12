/* Bumblebee – fullskjerm chat. Ingen avhengigheter. */
(function () {
	'use strict';

	var CFG = window.BBEE_APP;
	if (!CFG || !CFG.loggedIn) {
		return;
	}
	var T = CFG.i18n || {};

	var app = document.getElementById('bbee-app');
	var scroll = document.getElementById('bbee-scroll');
	var thread = document.getElementById('bbee-thread');
	var form = document.getElementById('bbee-form');
	var input = document.getElementById('bbee-input');
	var send = document.getElementById('bbee-send');
	var newBtn = document.getElementById('bbee-new');
	var suggestions = document.getElementById('bbee-suggestions');
	var greeting = document.getElementById('bbee-greeting');

	var STORE_KEY = 'bbee-app-samtale';
	var history = [];   // {role, content} som sendes til REST
	var turns = [];     // alt som vises, for å kunne gjenopprette samtalen
	var busy = false;

	var ICONS = {
		copy: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>',
		check: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
		up: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>',
		down: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/></svg>'
	};

	/* ---------- Oppstart ---------- */

	if (greeting && CFG.firstName) {
		greeting.textContent = 'Hva lurer du på, ' + CFG.firstName + '?';
	}

	(CFG.suggestions || []).forEach(function (q) {
		var b = document.createElement('button');
		b.type = 'button';
		b.className = 'bbee-suggestion';
		b.textContent = q;
		b.addEventListener('click', function () { ask(q); });
		suggestions.appendChild(b);
	});

	restore();
	autosize();
	input.focus();

	input.addEventListener('input', function () {
		autosize();
		updateSend();
	});

	input.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
			e.preventDefault();
			form.requestSubmit ? form.requestSubmit() : submit();
		}
	});

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		submit();
	});

	if (newBtn) {
		newBtn.addEventListener('click', reset);
	}

	/* ---------- Samtale ---------- */

	function submit() {
		var text = input.value.trim();
		if (!text || busy) {
			return;
		}
		input.value = '';
		autosize();
		ask(text);
	}

	function ask(text) {
		if (busy) {
			return;
		}
		setEmpty(false);
		addUser(text);
		turns.push({ kind: 'user', text: text });
		var priorHistory = history.slice();
		history.push({ role: 'user', content: text });
		save();

		setBusy(true);
		var thinking = addThinking();

		fetch(CFG.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce },
			body: JSON.stringify({ message: text, history: priorHistory, nonce: CFG.nonce })
		})
			.then(function (res) {
				return res.json().then(function (data) { return { ok: res.ok, data: data }; });
			})
			.then(function (r) {
				remove(thinking);
				if (!r.ok) {
					var msg = (r.data && r.data.message) ? r.data.message : T.error;
					addError(msg);
					history.pop();
					return;
				}
				var answer = (r.data && r.data.answer) ? String(r.data.answer) : '';
				var sources = (r.data && r.data.sources) ? r.data.sources : [];
				var logId = (r.data && r.data.log_id) ? r.data.log_id : '';
				addBot(answer, sources, logId);
				history.push({ role: 'assistant', content: answer });
				turns.push({ kind: 'bot', text: answer, sources: sources, logId: logId });
				save();
			})
			.catch(function () {
				remove(thinking);
				addError(T.error);
				history.pop();
			})
			.then(function () {
				setBusy(false);
				input.focus();
			});
	}

	function reset() {
		history = [];
		turns = [];
		thread.innerHTML = '';
		save();
		setEmpty(true);
		input.value = '';
		autosize();
		updateSend();
		input.focus();
	}

	/* ---------- Visning ---------- */

	function addUser(text) {
		var el = div('bbee-msg bbee-msg--user');
		var bubble = div('bbee-msg__bubble');
		bubble.textContent = text;
		el.appendChild(bubble);
		thread.appendChild(el);
		scrollDown();
	}

	function addBot(text, sources, logId) {
		var el = div('bbee-msg bbee-msg--bot');
		var body = div('bbee-msg__body');
		body.innerHTML = renderMarkdown(text, sources);
		el.appendChild(body);

		var cited = citedNumbers(text);
		if (sources && sources.length) {
			el.appendChild(renderSources(sources, cited));
		}
		el.appendChild(renderActions(text, logId));

		thread.appendChild(el);
		scrollDown();
		return el;
	}

	function addError(msg) {
		var el = div('bbee-msg bbee-msg--error');
		var body = div('bbee-msg__body');
		body.textContent = msg;
		el.appendChild(body);
		thread.appendChild(el);
		scrollDown();
	}

	function addThinking() {
		var el = div('bbee-msg bbee-msg--bot');
		el.innerHTML = '<div class="bbee-thinking" role="status"><span class="bbee-thinking__dot"></span><span class="bbee-thinking__text"></span></div>';
		el.querySelector('.bbee-thinking__text').textContent = (T.thinking || 'Leter i kunnskapskildene') + ' …';
		thread.appendChild(el);
		scrollDown();
		return el;
	}

	function renderSources(sources, cited) {
		var wrap = div('bbee-sources');
		var label = document.createElement('p');
		label.className = 'bbee-sources__label';
		label.textContent = (T.sources || 'Kilder') + ' (' + sources.length + ')';
		wrap.appendChild(label);

		var list = document.createElement('ul');
		list.className = 'bbee-sources__list';

		// Kilder svaret viser til, kommer først.
		var ordered = sources.slice().sort(function (a, b) {
			return (cited[String(b.n)] ? 1 : 0) - (cited[String(a.n)] ? 1 : 0) || a.n - b.n;
		});

		ordered.forEach(function (s) {
			var li = document.createElement('li');
			var a = document.createElement('a');
			a.className = 'bbee-source' + (cited[String(s.n)] ? ' is-cited' : '');
			a.href = s.url || '#';
			a.target = '_blank';
			a.rel = 'noopener';
			a.title = decode(s.title) || s.url || '';

			var n = document.createElement('span');
			n.className = 'bbee-source__n';
			n.textContent = String(s.n);

			var title = document.createElement('span');
			title.className = 'bbee-source__title';
			title.textContent = decode(s.title) || s.url || '';

			var host = document.createElement('span');
			host.className = 'bbee-source__host';
			host.textContent = hostOf(s.url);

			a.appendChild(n);
			a.appendChild(title);
			a.appendChild(host);
			li.appendChild(a);
			list.appendChild(li);
		});

		wrap.appendChild(list);
		return wrap;
	}

	function renderActions(text, logId) {
		var bar = div('bbee-actions');

		var copy = actionButton(ICONS.copy, T.copy || 'Kopier');
		copy.addEventListener('click', function () {
			copyText(text).then(function () {
				copy.innerHTML = ICONS.check;
				copy.setAttribute('aria-label', T.copied || 'Kopiert');
				setTimeout(function () {
					copy.innerHTML = ICONS.copy;
					copy.setAttribute('aria-label', T.copy || 'Kopier');
				}, 1500);
			});
		});
		bar.appendChild(copy);

		if (logId) {
			var note = document.createElement('span');
			note.className = 'bbee-actions__note';
			var up = actionButton(ICONS.up, T.useful || 'Nyttig');
			var down = actionButton(ICONS.down, T.notUseful || 'Ikke nyttig');
			var vote = function (value, btn) {
				up.disabled = true;
				down.disabled = true;
				btn.classList.add('is-selected');
				note.textContent = T.thanks || 'Takk';
				fetch(CFG.feedbackUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce },
					body: JSON.stringify({ log_id: logId, value: value, nonce: CFG.nonce })
				}).catch(function () { /* tilbakemelding er valgfri */ });
			};
			up.addEventListener('click', function () { vote('up', up); });
			down.addEventListener('click', function () { vote('down', down); });
			bar.appendChild(up);
			bar.appendChild(down);
			bar.appendChild(note);
		}
		return bar;
	}

	function actionButton(icon, label) {
		var b = document.createElement('button');
		b.type = 'button';
		b.className = 'bbee-action';
		b.innerHTML = icon;
		b.setAttribute('aria-label', label);
		b.title = label;
		return b;
	}

	/* ---------- Enkel og trygg Markdown ---------- */

	function escapeHtml(s) {
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function inline(s, byN) {
		s = escapeHtml(s);
		s = s.replace(/`([^`]+)`/g, '<code>$1</code>');
		s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
		s = s.replace(/(^|[^*])\*([^*\s][^*]*)\*/g, '$1<em>$2</em>');
		s = s.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, function (m, label, url) {
			return '<a href="' + url + '" target="_blank" rel="noopener">' + label + '</a>';
		});
		s = s.replace(/\[(\d+(?:\s*,\s*\d+)*)\]/g, function (m, nums) {
			return nums.split(/\s*,\s*/).map(function (n) {
				var src = byN[n];
				if (!src || !src.url) {
					return '[' + n + ']';
				}
				return '<a class="bbee-cite" href="' + escapeHtml(src.url) + '" target="_blank" rel="noopener" title="' + escapeHtml(decode(src.title || '')) + '">' + n + '</a>';
			}).join('');
		});
		return s;
	}

	function renderMarkdown(text, sources) {
		var byN = {};
		(sources || []).forEach(function (s) { byN[String(s.n)] = s; });

		var lines = String(text || '').replace(/\r\n/g, '\n').split('\n');
		var html = [];
		var para = [];
		var list = null; // 'ul' | 'ol'

		function flushPara() {
			if (para.length) {
				html.push('<p>' + inline(para.join(' '), byN) + '</p>');
				para = [];
			}
		}
		function closeList() {
			if (list) {
				html.push('</' + list + '>');
				list = null;
			}
		}

		lines.forEach(function (raw) {
			var line = raw.trim();
			var m;
			if (!line) {
				flushPara();
				closeList();
				return;
			}
			if ((m = line.match(/^#{1,6}\s+(.*)$/))) {
				flushPara();
				closeList();
				html.push('<h3>' + inline(m[1], byN) + '</h3>');
				return;
			}
			if ((m = line.match(/^[-*•]\s+(.*)$/))) {
				flushPara();
				if (list !== 'ul') { closeList(); html.push('<ul>'); list = 'ul'; }
				html.push('<li>' + inline(m[1], byN) + '</li>');
				return;
			}
			if ((m = line.match(/^\d+[.)]\s+(.*)$/))) {
				flushPara();
				if (list !== 'ol') { closeList(); html.push('<ol>'); list = 'ol'; }
				html.push('<li>' + inline(m[1], byN) + '</li>');
				return;
			}
			closeList();
			para.push(line);
		});
		flushPara();
		closeList();
		return html.join('');
	}

	function citedNumbers(text) {
		var out = {};
		var re = /\[(\d+(?:\s*,\s*\d+)*)\]/g;
		var m;
		while ((m = re.exec(String(text || ''))) !== null) {
			m[1].split(/\s*,\s*/).forEach(function (n) { out[n] = true; });
		}
		return out;
	}

	/* ---------- Hjelpere ---------- */

	/* Titler fra WordPress kan inneholde HTML-entiteter som &#8211;. */
	var decoder = null;
	function decode(s) {
		decoder = decoder || document.createElement('textarea');
		decoder.innerHTML = String(s == null ? '' : s);
		return decoder.value;
	}

	function div(cls) {
		var el = document.createElement('div');
		el.className = cls;
		return el;
	}

	function remove(node) {
		if (node && node.parentNode) {
			node.parentNode.removeChild(node);
		}
	}

	function hostOf(url) {
		try {
			var h = new URL(url, window.location.href).hostname.replace(/^www\./, '');
			if (h === CFG.homeHost || h === 'localhost') {
				return T.internalHost || 'bimverdi.no';
			}
			return h;
		} catch (e) {
			return '';
		}
	}

	function copyText(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}
		return new Promise(function (resolve) {
			var ta = document.createElement('textarea');
			ta.value = text;
			document.body.appendChild(ta);
			ta.select();
			try { document.execCommand('copy'); } catch (e) { /* ignorer */ }
			document.body.removeChild(ta);
			resolve();
		});
	}

	function setEmpty(empty) {
		app.classList.toggle('is-empty', empty);
		if (newBtn) {
			newBtn.hidden = empty;
		}
	}

	function setBusy(b) {
		busy = b;
		updateSend();
	}

	function updateSend() {
		send.disabled = busy || !input.value.trim();
	}

	function autosize() {
		input.style.height = 'auto';
		input.style.height = Math.min(input.scrollHeight, 200) + 'px';
	}

	function scrollDown() {
		if (scroll) {
			scroll.scrollTop = scroll.scrollHeight;
		}
	}

	/* Samtalen overlever en omlasting i samme fane. */
	function save() {
		try {
			window.sessionStorage.setItem(STORE_KEY, JSON.stringify({ history: history, turns: turns }));
		} catch (e) { /* lagring er valgfri */ }
	}

	function restore() {
		var data = null;
		try {
			data = JSON.parse(window.sessionStorage.getItem(STORE_KEY) || 'null');
		} catch (e) {
			data = null;
		}
		if (!data || !data.turns || !data.turns.length) {
			setEmpty(true);
			return;
		}
		history = data.history || [];
		turns = data.turns;
		setEmpty(false);
		turns.forEach(function (t) {
			if (t.kind === 'user') {
				addUser(t.text);
			} else {
				addBot(t.text, t.sources || [], t.logId || '');
			}
		});
	}
})();
