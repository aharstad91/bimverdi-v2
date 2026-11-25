/* BIM Verdi - BV-pilot/prosjekt: tegnteller, betingede felt og enkel klientvalidering. */
( function () {
	'use strict';

	function initCounters( root ) {
		root.querySelectorAll( '.bvp-counter' ).forEach( function ( counter ) {
			var field = counter.closest( '.bvp-field' );
			var input = field && field.querySelector( 'textarea, input[type="text"]' );

			if ( ! input ) {
				return;
			}

			var max = parseInt( counter.getAttribute( 'data-max' ), 10 ) || 0;

			var update = function () {
				counter.textContent = input.value.length + ' / ' + max + ' tegn';
			};

			input.addEventListener( 'input', update );
			update();
		} );
	}

	function initConditionals( root ) {
		var conditionals = root.querySelectorAll( '[data-bvp-depends-field]' );

		if ( ! conditionals.length ) {
			return;
		}

		/*
		 * Verdiene et styrende felt har nå. Radioknapp og nedtrekksliste gir
		 * én verdi, avkryssing kan gi flere - og da holder det at den
		 * forventede verdien er én av dem.
		 */
		var verdier = function ( key ) {
			var ut = [];

			root.querySelectorAll( '[data-bvp-field]' ).forEach( function ( el ) {
				if ( el.getAttribute( 'data-bvp-field' ) !== key ) {
					return;
				}

				if ( 'SELECT' === el.tagName ) {
					Array.prototype.forEach.call( el.selectedOptions || [], function ( valg ) {
						ut.push( valg.value );
					} );
					return;
				}

				if ( 'checkbox' === el.type || 'radio' === el.type ) {
					if ( el.checked ) {
						ut.push( el.value );
					}
					return;
				}

				ut.push( el.value );
			} );

			return ut;
		};

		var evaluate = function () {
			conditionals.forEach( function ( field ) {
				var key = field.getAttribute( 'data-bvp-depends-field' );
				var expected = field.getAttribute( 'data-bvp-depends-value' );

				field.hidden = verdier( key ).indexOf( expected ) === -1;
			} );
		};

		var lytt = function ( event ) {
			if ( event.target.closest && event.target.closest( '[data-bvp-field]' ) ) {
				evaluate();
			} else if ( event.target.matches && event.target.matches( '[data-bvp-field]' ) ) {
				evaluate();
			}
		};

		root.addEventListener( 'change', lytt );
		root.addEventListener( 'input', lytt );

		evaluate();
	}

	function initAccess( root ) {
		var blocks = root.querySelectorAll( '[data-bvp-depends-access]' );

		if ( ! blocks.length ) {
			return;
		}

		var evaluate = function () {
			var valgt = root.querySelector( '[data-bvp-access-level]:checked' );
			var level = valgt ? valgt.value : 'privat';

			blocks.forEach( function ( block ) {
				block.hidden = block.getAttribute( 'data-bvp-depends-access' ) !== level;
			} );
		};

		root.addEventListener( 'change', function ( event ) {
			if ( event.target.matches( '[data-bvp-access-level]' ) ) {
				evaluate();
			}
		} );

		evaluate();
	}

	function esc( text ) {
		var d = document.createElement( 'div' );
		d.textContent = text === null || text === undefined ? '' : String( text );
		return d.innerHTML;
	}

	/*
	 * Sett opp hvert element bare én gang. Nettlesere og innebygde visninger
	 * kan fyre DOMContentLoaded flere ganger, og da ville søkene blitt sendt dobbelt.
	 */
	function engang( el, nokkel ) {
		if ( ! el ) {
			return false;
		}

		var attr = 'data-bvp-init-' + nokkel;

		if ( el.getAttribute( attr ) ) {
			return false;
		}

		el.setAttribute( attr, '1' );

		return true;
	}

	/* Uthever bokstavene brukeren har skrevet i et treff. */
	function uthev( tekst, q ) {
		tekst = String( tekst == null ? '' : tekst );
		q = String( q == null ? '' : q ).trim();

		if ( '' === q ) {
			return esc( tekst );
		}

		var i = tekst.toLowerCase().indexOf( q.toLowerCase() );

		if ( i < 0 ) {
			return esc( tekst );
		}

		return esc( tekst.slice( 0, i ) ) +
			'<mark class="bvp-combo__treff">' + esc( tekst.slice( i, i + q.length ) ) + '</mark>' +
			esc( tekst.slice( i + q.length ) );
	}

	function escAttr( text ) {
		return esc( text ).replace( /"/g, '&quot;' );
	}

	function initForetakSok( root ) {
		var boks = root.querySelector( '.bvp-access' );
		var input = root.querySelector( '.bvp-foretak-sok' );
		var liste = root.querySelector( '#bvp-foretak-treff' );
		var valgte = root.querySelector( '#bvp-valgte-foretak' );
		var tom = root.querySelector( '.bvp-valgte-foretak__tom' );
		var status = root.querySelector( '.bvp-combo__status' );

		if ( ! boks || ! input || ! liste || ! valgte || ! engang( input, 'foretak' ) ) {
			return;
		}

		var ajax = boks.getAttribute( 'data-bvp-ajax' );
		var nonce = boks.getAttribute( 'data-bvp-nonce' );
		var timer = null;
		var aktiv = -1;
		var treff = [];

		function oppdaterTom() {
			if ( tom ) {
				tom.hidden = valgte.querySelector( '.bvp-foretak-kort' ) !== null;
			}
		}

		function post( action, data ) {
			var body = new URLSearchParams();
			body.append( 'action', action );
			body.append( 'nonce', nonce );

			Object.keys( data ).forEach( function ( key ) {
				body.append( key, data[ key ] );
			} );

			return fetch( ajax, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			} ).then( function ( r ) {
				return r.json();
			} );
		}

		function lukk() {
			liste.hidden = true;
			liste.innerHTML = '';
			input.setAttribute( 'aria-expanded', 'false' );
			aktiv = -1;
			treff = [];
		}

		function merk( i ) {
			var rader = liste.querySelectorAll( 'li' );
			Array.prototype.forEach.call( rader, function ( rad, n ) {
				rad.classList.toggle( 'bvp-combo__item--aktiv', n === i );
				rad.setAttribute( 'aria-selected', n === i ? 'true' : 'false' );
			} );
			aktiv = i;
		}

		function vis( rader ) {
			treff = rader || [];
			liste.innerHTML = '';

			if ( ! treff.length ) {
				lukk();
				if ( status ) { status.textContent = 'Ingen foretak matcher søket.'; }
				return;
			}

			treff.forEach( function ( f, i ) {
				var li = document.createElement( 'li' );
				li.setAttribute( 'role', 'option' );
				li.setAttribute( 'data-index', i );
				li.className = 'bvp-combo__item';
				li.innerHTML = '<span>' + esc( f.navn ) + '</span>' + ( f.orgnr ? ' <span class="bvp-desc">' + esc( f.orgnr ) + '</span>' : '' );
				liste.appendChild( li );
			} );

			liste.hidden = false;
			input.setAttribute( 'aria-expanded', 'true' );
			merk( 0 );

			if ( status ) {
				status.textContent = treff.length + ' foretak funnet.';
			}
		}

		function lagKort( data ) {
			if ( valgte.querySelector( '.bvp-foretak-kort[data-id="' + data.id + '"]' ) ) {
				if ( status ) { status.textContent = data.navn + ' er allerede valgt.'; }
				return;
			}

			var kort = document.createElement( 'div' );
			kort.className = 'bvp-foretak-kort';
			kort.setAttribute( 'data-id', data.id );

			var kontakter = '';

			if ( ! data.kontakter || ! data.kontakter.length ) {
				kontakter = '<p class="bvp-desc">Ingen registrerte kontaktpersoner i dette foretaket.</p>';
			} else {
				kontakter = '<p class="bvp-desc">Eller velg enkeltpersoner:</p>';
				data.kontakter.forEach( function ( k ) {
					kontakter += '<label class="bvp-option"><input type="checkbox" name="bvp_access[users][]" value="' +
						parseInt( k.id, 10 ) + '"> <span>' + esc( k.navn + ' - ' + k.type ) + '</span></label>';
				} );
			}

			kort.innerHTML =
				'<div class="bvp-foretak-kort__head"><strong>' + esc( data.navn ) + '</strong>' +
				( data.orgnr ? ' <span class="bvp-desc">' + esc( data.orgnr ) + '</span>' : '' ) +
				'<button type="button" class="bvp-fjern-foretak" aria-label="Fjern ' + esc( data.navn ) + '">Fjern</button></div>' +
				'<label class="bvp-option"><input type="checkbox" name="bvp_access[foretak][]" value="' +
				parseInt( data.id, 10 ) + '" checked> <span>Hele foretaket får lese</span></label>' +
				'<div class="bvp-foretak-kort__kontakter">' + kontakter + '</div>';

			valgte.appendChild( kort );
			oppdaterTom();

			if ( status ) {
				status.textContent = data.navn + ' er lagt til.';
			}
		}

		function velg( i ) {
			var f = treff[ i ];

			if ( ! f ) {
				return;
			}

			lukk();
			input.value = '';

			post( 'bvp_foretak_kontakter', { foretak: f.id } ).then( function ( svar ) {
				if ( svar && svar.success ) {
					lagKort( svar.data );
				} else if ( status ) {
					status.textContent = 'Klarte ikke å hente kontaktene for foretaket.';
				}
			} ).catch( function () {
				if ( status ) { status.textContent = 'Klarte ikke å hente kontaktene for foretaket.'; }
			} );
		}

		input.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			var q = input.value.trim();

			if ( q.length < 2 ) {
				lukk();
				return;
			}

			timer = window.setTimeout( function () {
				post( 'bvp_foretak_sok', { q: q } ).then( function ( svar ) {
					vis( svar && svar.success ? svar.data.treff : [] );
				} ).catch( function () {
					lukk();
					if ( status ) { status.textContent = 'Søket feilet. Prøv igjen.'; }
				} );
			}, 250 );
		} );

		input.addEventListener( 'keydown', function ( event ) {
			if ( liste.hidden ) {
				return;
			}

			if ( 'ArrowDown' === event.key ) {
				event.preventDefault();
				merk( Math.min( aktiv + 1, treff.length - 1 ) );
			} else if ( 'ArrowUp' === event.key ) {
				event.preventDefault();
				merk( Math.max( aktiv - 1, 0 ) );
			} else if ( 'Enter' === event.key ) {
				event.preventDefault();
				velg( aktiv );
			} else if ( 'Escape' === event.key ) {
				lukk();
			}
		} );

		liste.addEventListener( 'mousedown', function ( event ) {
			var li = event.target.closest( 'li' );

			if ( li ) {
				event.preventDefault();
				velg( parseInt( li.getAttribute( 'data-index' ), 10 ) );
			}
		} );

		input.addEventListener( 'blur', function () {
			window.setTimeout( lukk, 150 );
		} );

		valgte.addEventListener( 'click', function ( event ) {
			if ( event.target.classList.contains( 'bvp-fjern-foretak' ) ) {
				var kort = event.target.closest( '.bvp-foretak-kort' );
				if ( kort ) {
					kort.remove();
					oppdaterTom();
				}
			}
		} );

		oppdaterTom();
	}

	/* Eier av registreringen: velg foretak med autocomplete, så person i foretaket. */
	function initEierVelger( root ) {
		var boks = root.querySelector( '.bvp-access' );
		var felt = root.querySelector( '#bvp-eier-boks' );

		if ( ! boks || ! felt ) {
			return;
		}

		var input = felt.querySelector( '.bvp-eier-foretak-sok' );
		var liste = felt.querySelector( '#bvp-eier-foretak-treff' );
		var skjult = felt.querySelector( '#bvp-eier-foretak-id' );
		var velger = felt.querySelector( '#bvp-eier' );
		var status = felt.querySelector( '.bvp-eier-status' );
		var alle = felt.querySelector( '#bvp-eier-alle' );

		if ( ! input || ! liste || ! skjult || ! velger || ! engang( input, 'eier' ) ) {
			return;
		}

		var ajax = boks.getAttribute( 'data-bvp-ajax' );
		var nonce = boks.getAttribute( 'data-bvp-nonce' );
		var timer = null;
		var aktiv = -1;
		var treff = [];
		var sisteNavn = input.value;

		function post( action, data ) {
			var body = new URLSearchParams();
			body.append( 'action', action );
			body.append( 'nonce', nonce );

			Object.keys( data ).forEach( function ( key ) {
				body.append( key, data[ key ] );
			} );

			return fetch( ajax, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			} ).then( function ( r ) {
				return r.json();
			} );
		}

		function lukk() {
			liste.hidden = true;
			liste.innerHTML = '';
			input.setAttribute( 'aria-expanded', 'false' );
			aktiv = -1;
			treff = [];
		}

		function merk( i ) {
			Array.prototype.forEach.call( liste.querySelectorAll( 'li' ), function ( rad, n ) {
				rad.classList.toggle( 'bvp-combo__item--aktiv', n === i );
				rad.setAttribute( 'aria-selected', n === i ? 'true' : 'false' );
			} );
			aktiv = i;
		}

		function vis( rader ) {
			treff = rader || [];
			liste.innerHTML = '';

			if ( ! treff.length ) {
				lukk();
				if ( status ) { status.textContent = 'Ingen foretak matcher søket.'; }
				return;
			}

			treff.forEach( function ( f, i ) {
				var li = document.createElement( 'li' );
				li.setAttribute( 'role', 'option' );
				li.setAttribute( 'data-index', i );
				li.className = 'bvp-combo__item';
				li.innerHTML = '<span>' + esc( f.navn ) + '</span>' + ( f.orgnr ? ' <span class="bvp-desc">' + esc( f.orgnr ) + '</span>' : '' );
				liste.appendChild( li );
			} );

			liste.hidden = false;
			input.setAttribute( 'aria-expanded', 'true' );
			merk( 0 );

			if ( status ) { status.textContent = treff.length + ' foretak funnet.'; }
		}

		function visAlle() {
			skjult.value = '0';
			sisteNavn = '';

			if ( alle && alle.content ) {
				velger.innerHTML = '';
				velger.appendChild( alle.content.cloneNode( true ) );
			}

			if ( status ) { status.textContent = 'Viser alle registrerte brukere.'; }
		}

		function fyllPersoner( data ) {
			var brukere = data && data.brukere ? data.brukere : [];

			velger.innerHTML = '';

			if ( ! brukere.length ) {
				var tom = document.createElement( 'option' );
				tom.value = '';
				tom.textContent = '- ingen registrerte brukere -';
				velger.appendChild( tom );

				if ( status ) {
					status.textContent = data.navn + ' har ingen registrerte brukere. Tøm foretaksfeltet for å velge blant alle.';
				}

				return;
			}

			var forst = document.createElement( 'option' );
			forst.value = '';
			forst.textContent = '- velg person -';
			velger.appendChild( forst );

			brukere.forEach( function ( b ) {
				var o = document.createElement( 'option' );
				o.value = parseInt( b.id, 10 );
				o.textContent = b.navn + ( b.type ? ' - ' + b.type : '' );
				velger.appendChild( o );
			} );

			if ( status ) {
				status.textContent = brukere.length + ' registrerte brukere i ' + data.navn + '.';
			}
		}

		function velg( i ) {
			var f = treff[ i ];

			if ( ! f ) {
				return;
			}

			lukk();
			input.value = f.navn;
			sisteNavn = f.navn;
			skjult.value = parseInt( f.id, 10 );

			post( 'bvp_foretak_kontakter', { foretak: f.id } ).then( function ( svar ) {
				if ( svar && svar.success ) {
					fyllPersoner( svar.data );
				} else if ( status ) {
					status.textContent = 'Klarte ikke å hente brukerne i foretaket.';
				}
			} ).catch( function () {
				if ( status ) { status.textContent = 'Klarte ikke å hente brukerne i foretaket.'; }
			} );
		}

		input.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			var q = input.value.trim();

			if ( '' === q ) {
				lukk();
				visAlle();
				return;
			}

			if ( q.length < 2 ) {
				lukk();
				return;
			}

			timer = window.setTimeout( function () {
				post( 'bvp_foretak_sok', { q: q } ).then( function ( svar ) {
					vis( svar && svar.success ? svar.data.treff : [] );
				} ).catch( function () {
					lukk();
					if ( status ) { status.textContent = 'Søket feilet. Prøv igjen.'; }
				} );
			}, 250 );
		} );

		input.addEventListener( 'keydown', function ( event ) {
			if ( liste.hidden ) {
				return;
			}

			if ( 'ArrowDown' === event.key ) {
				event.preventDefault();
				merk( Math.min( aktiv + 1, treff.length - 1 ) );
			} else if ( 'ArrowUp' === event.key ) {
				event.preventDefault();
				merk( Math.max( aktiv - 1, 0 ) );
			} else if ( 'Enter' === event.key ) {
				event.preventDefault();
				velg( aktiv );
			} else if ( 'Escape' === event.key ) {
				lukk();
			}
		} );

		liste.addEventListener( 'mousedown', function ( event ) {
			var li = event.target.closest( 'li' );

			if ( li ) {
				event.preventDefault();
				velg( parseInt( li.getAttribute( 'data-index' ), 10 ) );
			}
		} );

		input.addEventListener( 'blur', function () {
			window.setTimeout( function () {
				lukk();

				// Halvskrevet navn uten valg: sett tilbake til det som faktisk er valgt.
				if ( input.value.trim() !== '' && input.value !== sisteNavn ) {
					input.value = sisteNavn;
				}
			}, 150 );
		} );
	}

	/* Medredaktører: finn foretaket, så personen, og legg til i lista. */
	function initMedredaktorer( root ) {
		var boks = root.querySelector( '.bvp-access' );
		var felt = root.querySelector( '#bvp-medred-boks' );

		if ( ! boks || ! felt ) {
			return;
		}

		var input = felt.querySelector( '.bvp-medred-foretak-sok' );
		var liste = felt.querySelector( '#bvp-medred-treff' );
		var person = felt.querySelector( '.bvp-medred-person' );
		var knapp = felt.querySelector( '.bvp-medred-legg-til' );
		var valgte = felt.querySelector( '#bvp-medred-valgte' );
		var tom = felt.querySelector( '.bvp-medred__tom' );
		var status = felt.querySelector( '.bvp-medred-status' );

		if ( ! input || ! liste || ! person || ! knapp || ! valgte || ! engang( input, 'medred' ) ) {
			return;
		}

		var ajax = boks.getAttribute( 'data-bvp-ajax' );
		var nonce = boks.getAttribute( 'data-bvp-nonce' );
		var timer = null;
		var aktiv = -1;
		var treff = [];
		var foretaksnavn = '';

		function post( action, data ) {
			var body = new URLSearchParams();
			body.append( 'action', action );
			body.append( 'nonce', nonce );

			Object.keys( data ).forEach( function ( key ) {
				body.append( key, data[ key ] );
			} );

			return fetch( ajax, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			} ).then( function ( r ) {
				return r.json();
			} );
		}

		function oppdaterTom() {
			if ( tom ) {
				tom.hidden = valgte.querySelector( '.bvp-medred__kort' ) !== null;
			}
		}

		function lukk() {
			liste.hidden = true;
			liste.innerHTML = '';
			input.setAttribute( 'aria-expanded', 'false' );
			aktiv = -1;
			treff = [];
		}

		function merk( i ) {
			Array.prototype.forEach.call( liste.querySelectorAll( 'li' ), function ( rad, n ) {
				rad.classList.toggle( 'bvp-combo__item--aktiv', n === i );
				rad.setAttribute( 'aria-selected', n === i ? 'true' : 'false' );
			} );
			aktiv = i;
		}

		function vis( rader, q ) {
			treff = rader || [];
			liste.innerHTML = '';

			if ( ! treff.length ) {
				lukk();
				if ( status ) { status.textContent = 'Ingen foretak matcher søket.'; }
				return;
			}

			treff.forEach( function ( f, i ) {
				var li = document.createElement( 'li' );
				li.setAttribute( 'role', 'option' );
				li.setAttribute( 'data-index', i );
				li.className = 'bvp-combo__item';
				li.innerHTML = '<span>' + uthev( f.navn, q ) + '</span>' +
					( f.orgnr ? ' <span class="bvp-desc">' + esc( f.orgnr ) + '</span>' : '' );
				liste.appendChild( li );
			} );

			liste.hidden = false;
			input.setAttribute( 'aria-expanded', 'true' );
			merk( 0 );

			if ( status ) { status.textContent = treff.length + ' foretak funnet.'; }
		}

		function fyllPersoner( data ) {
			var brukere = data && data.brukere ? data.brukere : [];

			foretaksnavn = data && data.navn ? data.navn : '';
			person.innerHTML = '';

			if ( ! brukere.length ) {
				var ingen = document.createElement( 'option' );
				ingen.value = '';
				ingen.textContent = '- ingen registrerte brukere -';
				person.appendChild( ingen );
				person.disabled = true;

				if ( status ) { status.textContent = foretaksnavn + ' har ingen registrerte brukere.'; }

				return;
			}

			var forst = document.createElement( 'option' );
			forst.value = '';
			forst.textContent = '- velg person -';
			person.appendChild( forst );

			brukere.forEach( function ( b ) {
				var o = document.createElement( 'option' );
				o.value = parseInt( b.id, 10 );
				o.textContent = b.navn + ( b.type ? ' - ' + b.type : '' );
				o.setAttribute( 'data-navn', b.navn );
				person.appendChild( o );
			} );

			person.disabled = false;

			if ( status ) {
				status.textContent = brukere.length + ' registrerte brukere i ' + foretaksnavn + '. Velg person og trykk Legg til.';
			}
		}

		function leggTil() {
			var valg = person.options[ person.selectedIndex ];
			var id = parseInt( person.value, 10 );

			if ( ! id || ! valg ) {
				if ( status ) { status.textContent = 'Velg en person først.'; }
				return;
			}

			if ( valgte.querySelector( '.bvp-medred__kort[data-id="' + id + '"]' ) ) {
				if ( status ) { status.textContent = valg.getAttribute( 'data-navn' ) + ' er allerede medredaktør.'; }
				return;
			}

			var navn = valg.getAttribute( 'data-navn' ) || valg.textContent;
			var li = document.createElement( 'li' );

			li.className = 'bvp-medred__kort';
			li.setAttribute( 'data-id', id );
			li.innerHTML = '<input type="hidden" name="bvp_access[contacts][]" value="' + id + '">' +
				'<span class="bvp-medred__navn">' + esc( navn ) + '</span>' +
				( foretaksnavn ? ' <span class="bvp-desc">' + esc( foretaksnavn ) + '</span>' : '' ) +
				'<button type="button" class="bvp-fjern-medred" aria-label="Fjern ' + escAttr( navn ) + '">Fjern</button>';

			valgte.appendChild( li );
			oppdaterTom();

			if ( status ) { status.textContent = navn + ' er lagt til som medredaktør.'; }
		}

		function velg( i ) {
			var f = treff[ i ];

			if ( ! f ) {
				return;
			}

			lukk();
			input.value = f.navn;

			post( 'bvp_foretak_kontakter', { foretak: f.id } ).then( function ( svar ) {
				if ( svar && svar.success ) {
					fyllPersoner( svar.data );
				} else if ( status ) {
					status.textContent = 'Klarte ikke å hente brukerne i foretaket.';
				}
			} ).catch( function () {
				if ( status ) { status.textContent = 'Klarte ikke å hente brukerne i foretaket.'; }
			} );
		}

		input.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			var q = input.value.trim();

			if ( q.length < 1 ) {
				lukk();
				return;
			}

			timer = window.setTimeout( function () {
				post( 'bvp_foretak_sok', { q: q } ).then( function ( svar ) {
					if ( input.value.trim() !== q ) {
						return;
					}

					vis( svar && svar.success ? svar.data.treff : [], q );
				} ).catch( function () {
					lukk();
					if ( status ) { status.textContent = 'Søket feilet. Prøv igjen.'; }
				} );
			}, 200 );
		} );

		input.addEventListener( 'keydown', function ( event ) {
			if ( liste.hidden ) {
				return;
			}

			if ( 'ArrowDown' === event.key ) {
				event.preventDefault();
				merk( Math.min( aktiv + 1, treff.length - 1 ) );
			} else if ( 'ArrowUp' === event.key ) {
				event.preventDefault();
				merk( Math.max( aktiv - 1, 0 ) );
			} else if ( 'Enter' === event.key ) {
				event.preventDefault();
				velg( aktiv );
			} else if ( 'Escape' === event.key ) {
				lukk();
			}
		} );

		liste.addEventListener( 'mousedown', function ( event ) {
			var li = event.target.closest( 'li' );

			if ( li ) {
				event.preventDefault();
				velg( parseInt( li.getAttribute( 'data-index' ), 10 ) );
			}
		} );

		input.addEventListener( 'blur', function () {
			window.setTimeout( lukk, 150 );
		} );

		knapp.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			leggTil();
		} );

		person.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key ) {
				event.preventDefault();
				leggTil();
			}
		} );

		valgte.addEventListener( 'click', function ( event ) {
			if ( ! event.target.classList.contains( 'bvp-fjern-medred' ) ) {
				return;
			}

			var kort = event.target.closest( '.bvp-medred__kort' );

			if ( kort ) {
				var navn = kort.querySelector( '.bvp-medred__navn' );
				kort.remove();
				oppdaterTom();

				if ( status && navn ) { status.textContent = navn.textContent + ' er fjernet.'; }
			}
		} );

		oppdaterTom();
	}

	function initKildeSok( root ) {
		root.querySelectorAll( '.bvp-lookup' ).forEach( function ( boks ) {
			var input = boks.querySelector( '.bvp-lookup-sok' );
			var liste = boks.querySelector( '.bvp-combo__list' );
			var valgte = boks.querySelector( '.bvp-lookup__valgte' );
			var status = boks.querySelector( '.bvp-combo__status' );
			var tom = boks.querySelector( '.bvp-lookup__tom' );

			if ( ! input || ! liste || ! valgte || ! engang( boks, 'kilde' ) ) {
				return;
			}

			var felt = boks.getAttribute( 'data-bvp-felt' );
			var maks = parseInt( boks.getAttribute( 'data-bvp-maks' ), 10 ) || 0;
			var merker = [];

			try {
				merker = JSON.parse( boks.getAttribute( 'data-bvp-merker' ) || '[]' ) || [];
			} catch ( err ) {
				merker = [];
			}
			var ajax = boks.getAttribute( 'data-bvp-ajax' );
			var nonce = boks.getAttribute( 'data-bvp-nonce' );
			var timer = null;
			var aktiv = -1;
			var treff = [];
			var sisteQ = '';
			var startTekst = input.getAttribute( 'placeholder' ) || 'Søk ...';

			// Forrige svar fra serveren. «komplett» betyr at vi fikk alle treffene,
			// og da kan vi snevre inn videre i nettleseren for hver bokstav.
			var buffer = { q: '', treff: [], komplett: false };

			function antall() {
				return valgte.querySelectorAll( '.bvp-lookup__kilde' ).length;
			}

			function oppdater() {
				if ( tom ) {
					tom.hidden = antall() > 0;
				}

				if ( maks > 0 && antall() >= maks ) {
					input.disabled = true;
					input.placeholder = 'Maks ' + maks + ' valgt';
				} else {
					input.disabled = false;
					input.placeholder = startTekst;
				}
			}

			function lukk() {
				liste.hidden = true;
				liste.innerHTML = '';
				input.setAttribute( 'aria-expanded', 'false' );
				aktiv = -1;
				treff = [];
			}

			function merk( i ) {
				var rader = liste.querySelectorAll( 'li' );
				Array.prototype.forEach.call( rader, function ( rad, n ) {
					rad.classList.toggle( 'bvp-combo__item--aktiv', n === i );
					rad.setAttribute( 'aria-selected', n === i ? 'true' : 'false' );
				} );
				aktiv = i;
			}

			function vis( rader, totalt, q ) {
				treff = rader || [];
				liste.innerHTML = '';
				sisteQ = q || '';

				if ( ! treff.length ) {
					lukk();
					if ( status ) { status.textContent = 'Ingen treff på «' + sisteQ + '». Prøv færre bokstaver.'; }
					return;
				}

				treff.forEach( function ( k, i ) {
					var li = document.createElement( 'li' );
					li.setAttribute( 'role', 'option' );
					li.setAttribute( 'data-index', i );
					li.className = 'bvp-combo__item';

					if ( valgte.querySelector( '.bvp-lookup__kilde[data-id="' + parseInt( k.id, 10 ) + '"]' ) ) {
						li.className += ' bvp-combo__item--valgt';
					}

					li.innerHTML = '<span>' + uthev( k.tittel, sisteQ ) + '</span>' +
						( k.utgiver ? ' <span class="bvp-desc">' + uthev( k.utgiver, sisteQ ) + '</span>' : '' ) +
						( k.type ? ' <span class="bvp-lookup__type">' + esc( k.type ) + '</span>' : '' );
					liste.appendChild( li );
				} );

				liste.hidden = false;
				input.setAttribute( 'aria-expanded', 'true' );
				merk( 0 );

				if ( status ) {
					var sum = typeof totalt === 'number' ? totalt : treff.length;

					status.textContent = sum > treff.length
						? 'Viser ' + treff.length + ' av ' + sum + ' treff - skriv mer for å snevre inn.'
						: treff.length + ( 1 === treff.length ? ' treff.' : ' treff.' );
				}
			}

			function velg( i ) {
				var k = treff[ i ];

				if ( ! k ) {
					return;
				}

				if ( valgte.querySelector( '.bvp-lookup__kilde[data-id="' + k.id + '"]' ) ) {
					if ( status ) { status.textContent = k.tittel + ' er allerede valgt.'; }
					lukk();
					input.value = '';
					return;
				}

				var li = document.createElement( 'li' );
				li.className = 'bvp-lookup__kilde';
				li.setAttribute( 'data-id', k.id );
				var navn = 'bvp[' + felt + '][' + parseInt( k.id, 10 ) + ']';
				var velger = '';

				merker.forEach( function ( gruppe, i ) {
					velger += '<select class="bvp-lookup__merke" name="' + escAttr( navn + '[merker][' + i + ']' ) + '" aria-label="' + escAttr( gruppe.navn + ' for ' + k.tittel ) + '">' +
						'<option value="">- ' + esc( String( gruppe.navn ).toLowerCase() ) + ' -</option>' +
						gruppe.valg.map( function ( m ) { return '<option value="' + escAttr( m ) + '">' + esc( m ) + '</option>'; } ).join( '' ) +
						'</select>';
				} );

				li.innerHTML = '<input type="hidden" name="' + escAttr( navn + '[id]' ) + '" value="' + parseInt( k.id, 10 ) + '">' +
					'<span class="bvp-lookup__tittel">' + esc( k.tittel ) + '</span>' +
					( k.utgiver ? '<span class="bvp-desc">' + esc( k.utgiver ) + '</span>' : '' ) +
					( k.type ? '<span class="bvp-lookup__type">' + esc( k.type ) + '</span>' : '' ) +
					velger +
					'<button type="button" class="bvp-fjern-kilde" aria-label="Fjern ' + escAttr( k.tittel ) + '">Fjern</button>';

				valgte.appendChild( li );
				lukk();
				input.value = '';
				oppdater();

				if ( status ) { status.textContent = k.tittel + ' er lagt til.'; }
			}

			/* Snevrer inn i nettleseren når vi alt har alle treffene for en kortere søketekst. */
			function kanFiltrereLokalt( q ) {
				return buffer.komplett &&
					buffer.q.length > 0 &&
					q.toLowerCase().indexOf( buffer.q.toLowerCase() ) === 0;
			}

			function filtrerLokalt( q ) {
				var ned = q.toLowerCase();

				return buffer.treff.filter( function ( k ) {
					return String( k.tittel || '' ).toLowerCase().indexOf( ned ) !== -1 ||
						String( k.utgiver || '' ).toLowerCase().indexOf( ned ) !== -1;
				} );
			}

			function sok( q ) {
				var body = new URLSearchParams();
				body.append( 'action', 'bvp_kilde_sok' );
				body.append( 'nonce', nonce );
				body.append( 'felt', felt );
				body.append( 'q', q );

				fetch( ajax, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				} ).then( function ( r ) {
					return r.json();
				} ).then( function ( svar ) {
					// Skriver brukeren videre mens svaret er underveis, er det foreldet.
					if ( input.value.trim() !== q ) {
						return;
					}

					var data = svar && svar.success ? svar.data : null;
					var rader = data && data.treff ? data.treff : [];
					var totalt = data && typeof data.totalt === 'number' ? data.totalt : rader.length;

					buffer = {
						q: q,
						treff: rader,
						komplett: totalt <= rader.length
					};

					vis( rader, totalt, q );
				} ).catch( function () {
					lukk();
					if ( status ) { status.textContent = 'Søket feilet. Prøv igjen.'; }
				} );
			}

			input.addEventListener( 'input', function () {
				window.clearTimeout( timer );
				var q = input.value.trim();

				if ( q.length < 1 ) {
					buffer = { q: '', treff: [], komplett: false };
					lukk();
					return;
				}

				// Har vi alt alle treffene for en kortere tekst, snevrer vi inn med én gang.
				if ( kanFiltrereLokalt( q ) ) {
					var lokale = filtrerLokalt( q );

					buffer = { q: q, treff: lokale, komplett: true };
					vis( lokale, lokale.length, q );
					return;
				}

				timer = window.setTimeout( function () { sok( q ); }, 200 );
			} );

			input.addEventListener( 'keydown', function ( event ) {
				if ( liste.hidden ) { return; }

				if ( 'ArrowDown' === event.key ) {
					event.preventDefault();
					merk( Math.min( aktiv + 1, treff.length - 1 ) );
				} else if ( 'ArrowUp' === event.key ) {
					event.preventDefault();
					merk( Math.max( aktiv - 1, 0 ) );
				} else if ( 'Enter' === event.key ) {
					event.preventDefault();
					velg( aktiv );
				} else if ( 'Escape' === event.key ) {
					lukk();
				}
			} );

			liste.addEventListener( 'mousedown', function ( event ) {
				var li = event.target.closest( 'li' );

				if ( li ) {
					event.preventDefault();
					velg( parseInt( li.getAttribute( 'data-index' ), 10 ) );
				}
			} );

			input.addEventListener( 'blur', function () { window.setTimeout( lukk, 150 ); } );

			valgte.addEventListener( 'click', function ( event ) {
				if ( event.target.classList.contains( 'bvp-fjern-kilde' ) ) {
					var rad = event.target.closest( '.bvp-lookup__kilde' );
					if ( rad ) { rad.remove(); oppdater(); }
				}
			} );

			oppdater();
		} );
	}

	/*
	 * Lokal sikkerhetskopi av skjemaet.
	 *
	 * Lagrer det som er skrevet i nettleseren hvert par sekunder, slik at
	 * ingenting går tapt om fanen lukkes, nettet ryker eller innloggingen
	 * går ut. Oppslag og filopplastinger tas ikke med.
	 */
	function initUtkast( root ) {
		var form = root.querySelector( '.bvp-form' );

		if ( ! form || ! engang( form, 'utkast' ) ) {
			return;
		}

		var idFelt = form.querySelector( 'input[name="bvp_id"]' );
		var postId = idFelt ? parseInt( idFelt.value, 10 ) || 0 : 0;
		var nokkel = 'bvp-utkast-' + postId;
		var NY = 'bvp-utkast-0';
		var timer = null;
		var sendes = false;

		function lagerbart( el ) {
			var navn = el.getAttribute( 'name' ) || '';

			if ( navn.indexOf( 'bvp[' ) !== 0 || 'file' === el.type ) {
				return false;
			}

			// Oppslagsfelt lagrer ID-er i skjulte felt - de hoppes over.
			return ! ( 'hidden' === el.type );
		}

		function felter() {
			// Enkelt uttrekk framfor attributtvelger: navnene inneholder
			// klammer, som ikke alle nettlesere slår opp like villig.
			return Array.prototype.filter.call(
				form.querySelectorAll( 'input, select, textarea' ),
				lagerbart
			);
		}

		function les() {
			var data = {};

			felter().forEach( function ( el ) {
				var navn = el.getAttribute( 'name' );

				if ( 'checkbox' === el.type ) {
					if ( el.checked ) {
						data[ navn ] = ( data[ navn ] || [] ).concat( el.value );
					}
				} else if ( 'radio' === el.type ) {
					if ( el.checked ) {
						data[ navn ] = el.value;
					}
				} else if ( '' !== el.value ) {
					data[ navn ] = el.value;
				}
			} );

			return data;
		}

		function tomt( data ) {
			return ! Object.keys( data ).length;
		}

		function lagre( innsendt ) {
			var data = les();

			try {
				if ( tomt( data ) ) {
					window.localStorage.removeItem( nokkel );
					return;
				}

				var kopi = { tid: Date.now(), felt: data };

				// Merk kopien når skjemaet sendes, så vi ikke tilbyr den som
				// et nytt utkast etter at den er lagret på nettstedet.
				if ( innsendt ) {
					kopi.innsendt = Date.now();
				}

				window.localStorage.setItem( nokkel, JSON.stringify( kopi ) );
			} catch ( err ) {
				// Privat vindu eller full lagring - da får vi klare oss uten.
			}
		}

		function hentLagret() {
			try {
				var raa = window.localStorage.getItem( nokkel );

				if ( ! raa ) {
					return null;
				}

				var data = JSON.parse( raa );

				return data && data.felt && Object.keys( data.felt ).length ? data : null;
			} catch ( err ) {
				return null;
			}
		}

		function glem() {
			try {
				window.localStorage.removeItem( nokkel );
			} catch ( err ) {
				// Ingenting å gjøre.
			}
		}

		function fyllInn( felt ) {
			Object.keys( felt ).forEach( function ( navn ) {
				var verdi = felt[ navn ];
				var elementer = felter().filter( function ( el ) {
					return el.getAttribute( 'name' ) === navn;
				} );

				elementer.forEach( function ( el ) {
					if ( 'checkbox' === el.type ) {
						el.checked = [].concat( verdi ).indexOf( el.value ) !== -1;
					} else if ( 'radio' === el.type ) {
						el.checked = el.value === verdi;
					} else {
						el.value = verdi;
					}

					el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
					el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				} );
			} );
		}

		function tid( stempel ) {
			try {
				return new Date( stempel ).toLocaleString( 'no' );
			} catch ( err ) {
				return '';
			}
		}

		function visTilbud( data ) {
			var boks = document.createElement( 'div' );
			var tekst = 'Vi fant et uferdig utkast fra ' + esc( tid( data.tid ) ) + ' i denne nettleseren.';

			// Kopien ble sendt til nettstedet. Ble den lagret, finnes den alt -
			// henter man den inn her, blir det en ny registrering med egen ID.
			if ( data.innsendt && 0 === postId ) {
				tekst = 'Denne nettleseren har en kopi av et skjema du lagret eller sendte inn ' + esc( tid( data.innsendt ) ) +
					'. Sjekk først om det ligger under Mine registreringer. Henter du det inn her, blir det en ny registrering med egen ID.';
			}

			boks.className = 'bvp-notice bvp-notice--warning bvp-utkast';
			boks.innerHTML = '<span>' + tekst + '</span> ' +
				'<button type="button" class="bvp-button bvp-button--secondary bvp-utkast-hent">Hent inn</button> ' +
				'<button type="button" class="bvp-button bvp-button--link bvp-utkast-forkast">Forkast</button>';

			form.insertBefore( boks, form.firstChild );

			boks.querySelector( '.bvp-utkast-hent' ).addEventListener( 'click', function () {
				fyllInn( data.felt );
				boks.remove();
			} );

			boks.querySelector( '.bvp-utkast-forkast' ).addEventListener( 'click', function () {
				glem();
				boks.remove();
			} );
		}

		// Kom vi hit rett etter en vellykket lagring, er kopien utdatert.
		var melding = ( window.location.search.match( /[?&]bvp_msg=([a-z_]+)/ ) || [] )[ 1 ];

		if ( 'kladd' === melding || 'sendt' === melding || 'oppdatert' === melding ) {
			glem();

			// En ny registrering ble nettopp lagret: kopien fra det tomme
			// skjemaet gjelder den, og skal ikke tilbys som nytt utkast.
			try {
				var ny = JSON.parse( window.localStorage.getItem( NY ) || 'null' );

				if ( ny && ny.innsendt ) {
					window.localStorage.removeItem( NY );
				}
			} catch ( err ) {
				// Ingenting å gjøre.
			}
		} else {
			var lagret = hentLagret();

			if ( lagret ) {
				visTilbud( lagret );
			}
		}

		form.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( lagre, 1500 );
		} );

		form.addEventListener( 'change', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( lagre, 1500 );
		} );

		form.addEventListener( 'submit', function ( event ) {
			sendes = true;
			window.clearTimeout( timer );
			lagre( true );

			// Stoppet en sjekk innsendingen, er skjemaet fortsatt uferdig.
			window.setTimeout( function () {
				if ( event.defaultPrevented ) {
					sendes = false;
					lagre();
				}
			}, 0 );
		} );

		window.addEventListener( 'beforeunload', function () {
			if ( ! sendes ) {
				lagre();
			}
		} );
	}

	/*
	 * «Les mer»-lenker som peker til siden man alt står på, kommer fra temaets
	 * standardmal og fører i ring. De fjernes på registreringens egen side.
	 */
	function fjernLesMer( root ) {
		if ( ! root.classList.contains( 'bvp-enkeltside' ) ) {
			return;
		}

		var her = window.location.href.split( '#' )[ 0 ].replace( /\/$/, '' );

		document.querySelectorAll( 'a' ).forEach( function ( lenke ) {
			if ( ! /^(les mer|read more|fortsett å lese|continue reading)\b/i.test( lenke.textContent.trim() ) ) {
				return;
			}

			var mal = ( lenke.getAttribute( 'href' ) || '' ).split( '#' )[ 0 ];

			if ( '' === mal ) {
				return;
			}

			var full = lenke.href.split( '#' )[ 0 ].replace( /\/$/, '' );

			if ( full === her ) {
				var omslag = lenke.closest( '.wp-block-read-more, .more-link, p' );

				if ( omslag && omslag.textContent.trim() === lenke.textContent.trim() ) {
					omslag.remove();
				} else {
					lenke.remove();
				}
			}
		} );
	}

	/*
	 * Diktering: snakk inn teksten i stedet for å skrive.
	 *
	 * Bruker nettleserens egen talegjenkjenning (Chrome og Edge). Finnes den
	 * ikke, vises ingen knapp, og alt virker som før.
	 */
	function initDiktering( root ) {
		var Tale = window.SpeechRecognition || window.webkitSpeechRecognition;
		var aktiv = null;   // { felt, knapp, status, lytter, vil }

		/*
		 * Norsk med mindre noe annet er satt. Nettstedet kjører med
		 * lang="en-US", så sidespråket kan vi ikke gå etter.
		 */
		function sprak() {
			var satt = root.getAttribute( 'data-bvp-tale' );

			if ( satt ) {
				return satt;
			}

			var side = ( document.documentElement.getAttribute( 'lang' ) || '' ).trim();

			return /^(nb|nn|no)\b/i.test( side ) ? side : 'nb-NO';
		}

		function stopp() {
			if ( ! aktiv ) {
				return;
			}

			aktiv.vil = false; // Brukeren stopper selv - ikke start på nytt.

			try {
				aktiv.lytter.stop();
			} catch ( err ) {
				av();
			}
		}

		function av() {
			if ( ! aktiv ) {
				return;
			}

			aktiv.knapp.classList.remove( 'bvp-diktering--pa' );
			aktiv.knapp.setAttribute( 'aria-pressed', 'false' );
			aktiv = null;
		}

		function si( status, tekst ) {
			if ( status ) {
				status.textContent = tekst;
			}
		}

		/* Setter teksten inn der markøren står. */
		function settInn( felt, tekst ) {
			tekst = String( tekst ).trim();

			if ( '' === tekst ) {
				return;
			}

			var maks = parseInt( felt.getAttribute( 'maxlength' ), 10 ) || 0;
			var verdi = felt.value;
			var fra = typeof felt.selectionStart === 'number' ? felt.selectionStart : verdi.length;
			var til = typeof felt.selectionEnd === 'number' ? felt.selectionEnd : verdi.length;

			var for_ = verdi.slice( 0, fra );
			var etter = verdi.slice( til );

			if ( for_ && ! /\s$/.test( for_ ) ) {
				tekst = ' ' + tekst;
			}

			if ( etter && ! /^\s/.test( etter ) ) {
				tekst = tekst + ' ';
			}

			var ny = for_ + tekst + etter;

			if ( maks > 0 && ny.length > maks ) {
				ny = ny.slice( 0, maks );
			}

			felt.value = ny;

			var punkt = Math.min( for_.length + tekst.length, ny.length );

			try {
				felt.setSelectionRange( punkt, punkt );
			} catch ( err ) {
				// Noen felttyper støtter det ikke - da spiller det ingen rolle.
			}

			felt.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}

		function feiltekst( kode ) {
			if ( 'not-allowed' === kode || 'service-not-allowed' === kode ) {
				return 'Nettleseren fikk ikke bruke mikrofonen. Klikk på hengelåsen i adressefeltet og tillat mikrofon for bimverdi.no.';
			}

			if ( 'audio-capture' === kode ) {
				return 'Fant ingen mikrofon. Sjekk at den er koblet til og valgt i lydinnstillingene.';
			}

			if ( 'network' === kode ) {
				return 'Talegjenkjenningen fikk ikke kontakt med nettet. Prøv igjen.';
			}

			if ( 'no-speech' === kode ) {
				return ''; // Helt vanlig - vi starter bare på nytt.
			}

			return 'Dikteringen stoppet (' + kode + '). Trykk for å prøve igjen.';
		}

		function start( felt, knapp, status ) {
			var lytter = new Tale();
			var økt = { felt: felt, knapp: knapp, status: status, lytter: lytter, vil: true, feil: '' };

			lytter.lang = sprak();
			lytter.continuous = true;
			lytter.interimResults = true;
			lytter.maxAlternatives = 1;

			lytter.onstart = function () {
				si( status, 'Hører etter - snakk nå. Trykk på knappen igjen for å stoppe.' );
			};

			lytter.onresult = function ( hendelse ) {
				var underveis = '';

				for ( var i = hendelse.resultIndex; i < hendelse.results.length; i++ ) {
					var treff = hendelse.results[ i ];

					if ( treff.isFinal ) {
						settInn( felt, treff[ 0 ].transcript );
					} else {
						underveis += treff[ 0 ].transcript;
					}
				}

				if ( underveis.trim() ) {
					si( status, 'Hører: ' + underveis.trim() );
				}
			};

			lytter.onerror = function ( hendelse ) {
				var tekst = feiltekst( hendelse.error );

				if ( tekst ) {
					økt.feil = tekst;
					økt.vil = false;
					si( status, tekst );
				}
			};

			lytter.onend = function () {
				// Chrome avslutter av seg selv etter en pause. Står knappen
				// fortsatt på, starter vi igjen, slik at man kan tenke seg om.
				if ( økt.vil && aktiv === økt ) {
					try {
						lytter.start();
						return;
					} catch ( err ) {
						// Faller gjennom og slår av under.
					}
				}

				if ( aktiv === økt ) {
					av();
				}

				if ( ! økt.feil ) {
					si( status, 'Dikteringen er avsluttet.' );
				}
			};

			si( status, 'Starter ...' );

			try {
				lytter.start();
			} catch ( err ) {
				si( status, 'Klarte ikke å starte dikteringen. Last siden på nytt og prøv igjen.' );
				return;
			}

			aktiv = økt;
			knapp.classList.add( 'bvp-diktering--pa' );
			knapp.setAttribute( 'aria-pressed', 'true' );
			felt.focus();
		}

		root.querySelectorAll( '.bvp-field' ).forEach( function ( boks ) {
			var felt = boks.querySelector( 'textarea, input[type="text"]' );

			if ( ! felt || ! engang( felt, 'diktering' ) ) {
				return;
			}

			// Korte felt som navn og e-post trenger ikke diktering.
			var maks = parseInt( felt.getAttribute( 'maxlength' ), 10 ) || 0;

			if ( 'TEXTAREA' !== felt.tagName && ( 0 === maks || maks < 120 ) ) {
				return;
			}

			var knapp = document.createElement( 'button' );

			knapp.type = 'button';
			knapp.className = 'bvp-diktering';
			knapp.setAttribute( 'aria-pressed', 'false' );
			knapp.innerHTML = '<span class="bvp-diktering__ikon" aria-hidden="true">🎤</span>' +
				'<span class="bvp-diktering__tekst">Snakk inn</span>';

			var status = document.createElement( 'span' );

			status.className = 'bvp-diktering__status';
			status.setAttribute( 'role', 'status' );
			status.setAttribute( 'aria-live', 'polite' );

			var rad = document.createElement( 'div' );

			rad.className = 'bvp-diktering__rad';
			rad.appendChild( knapp );
			rad.appendChild( status );

			var teller = boks.querySelector( '.bvp-counter' );

			if ( teller ) {
				teller.parentNode.insertBefore( rad, teller );
			} else {
				felt.parentNode.insertBefore( rad, felt.nextSibling );
			}

			// Uten støtte i nettleseren sier knappen hvorfor den ikke virker.
			if ( ! Tale ) {
				knapp.disabled = true;
				knapp.classList.add( 'bvp-diktering--av' );
				knapp.setAttribute( 'title', 'Diktering virker i Chrome og Edge på PC.' );
				si( status, 'Diktering virker i Chrome og Edge.' );
				return;
			}

			knapp.setAttribute( 'title', 'Lar deg snakke inn teksten i stedet for å skrive.' );

			knapp.addEventListener( 'click', function () {
				if ( aktiv && aktiv.felt === felt ) {
					stopp();
					return;
				}

				if ( aktiv ) {
					stopp();
				}

				start( felt, knapp, status );
			} );
		} );
	}

	/* @-nevning i meldingsfeltet. */
	function initNevning( root ) {
		var boks = root.querySelector( '.bvp-nevn' );

		if ( ! boks || ! engang( boks, 'nevn' ) ) {
			return;
		}

		var felt = boks.querySelector( 'textarea' );
		var liste = boks.querySelector( '.bvp-nevn__liste' );
		var status = boks.querySelector( '.bvp-nevn__status' );
		var valgte = boks.querySelector( '.bvp-nevn__valgte' );

		if ( ! felt || ! liste || ! valgte ) {
			return;
		}

		var ajax = boks.getAttribute( 'data-bvp-ajax' );
		var nonce = boks.getAttribute( 'data-bvp-nonce' );
		var id = boks.getAttribute( 'data-bvp-id' );
		var personer = null;
		var treff = [];
		var aktiv = -1;
		var start = -1;

		function hent() {
			if ( personer ) {
				return Promise.resolve( personer );
			}

			var body = new URLSearchParams();
			body.append( 'action', 'bvp_melding_personer' );
			body.append( 'nonce', nonce );
			body.append( 'bvp_id', id );

			return fetch( ajax, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			} ).then( function ( r ) {
				return r.json();
			} ).then( function ( svar ) {
				personer = svar && svar.success ? svar.data.personer : [];
				return personer;
			} ).catch( function () {
				personer = [];
				return personer;
			} );
		}

		function lukk() {
			liste.hidden = true;
			liste.innerHTML = '';
			treff = [];
			aktiv = -1;
			start = -1;
		}

		function merk( i ) {
			Array.prototype.forEach.call( liste.querySelectorAll( 'li' ), function ( rad, n ) {
				rad.classList.toggle( 'bvp-combo__item--aktiv', n === i );
				rad.setAttribute( 'aria-selected', n === i ? 'true' : 'false' );
			} );
			aktiv = i;
		}

		function vis( rader, q ) {
			treff = rader;
			liste.innerHTML = '';

			if ( ! treff.length ) {
				lukk();
				return;
			}

			treff.forEach( function ( p, i ) {
				var li = document.createElement( 'li' );
				li.className = 'bvp-combo__item';
				li.setAttribute( 'role', 'option' );
				li.setAttribute( 'data-index', i );
				li.innerHTML = '<span>' + uthev( p.navn, q ) + '</span>' +
					( p.foretak ? ' <span class="bvp-desc">' + esc( p.foretak ) + '</span>' : '' );
				liste.appendChild( li );
			} );

			liste.hidden = false;
			merk( 0 );
		}

		/* Finner @-ordet markøren står i. */
		function ordet() {
			var punkt = felt.selectionStart;
			var for_ = felt.value.slice( 0, punkt );
			var match = for_.match( /(^|\s)@([^\s@]*)$/ );

			if ( ! match ) {
				return null;
			}

			return { q: match[ 2 ], fra: punkt - match[ 2 ].length - 1 };
		}

		function velg( i ) {
			var p = treff[ i ];

			if ( ! p || start < 0 ) {
				return;
			}

			var punkt = felt.selectionStart;
			var ny = felt.value.slice( 0, start ) + '@' + p.navn + ' ' + felt.value.slice( punkt );

			felt.value = ny;

			var etter = start + p.navn.length + 2;

			try {
				felt.setSelectionRange( etter, etter );
			} catch ( err ) {
				// Ikke viktig.
			}

			if ( ! valgte.querySelector( 'input[value="' + parseInt( p.id, 10 ) + '"]' ) ) {
				var skjult = document.createElement( 'input' );
				skjult.type = 'hidden';
				skjult.name = 'bvp_nevnt[]';
				skjult.value = parseInt( p.id, 10 );
				valgte.appendChild( skjult );
			}

			if ( status ) {
				status.textContent = p.navn + ' får beskjed når du sender meldingen.';
			}

			lukk();
			felt.focus();
			felt.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}

		felt.addEventListener( 'input', function () {
			var ord = ordet();

			if ( ! ord ) {
				lukk();
				return;
			}

			start = ord.fra;

			hent().then( function ( alle ) {
				var q = ord.q.toLowerCase();
				var rader = alle.filter( function ( p ) {
					return '' === q || String( p.navn ).toLowerCase().indexOf( q ) !== -1;
				} ).slice( 0, 8 );

				vis( rader, ord.q );
			} );
		} );

		felt.addEventListener( 'keydown', function ( event ) {
			if ( liste.hidden ) {
				return;
			}

			if ( 'ArrowDown' === event.key ) {
				event.preventDefault();
				merk( Math.min( aktiv + 1, treff.length - 1 ) );
			} else if ( 'ArrowUp' === event.key ) {
				event.preventDefault();
				merk( Math.max( aktiv - 1, 0 ) );
			} else if ( 'Enter' === event.key || 'Tab' === event.key ) {
				event.preventDefault();
				velg( aktiv );
			} else if ( 'Escape' === event.key ) {
				lukk();
			}
		} );

		liste.addEventListener( 'mousedown', function ( event ) {
			var li = event.target.closest( 'li' );

			if ( li ) {
				event.preventDefault();
				velg( parseInt( li.getAttribute( 'data-index' ), 10 ) );
			}
		} );

		felt.addEventListener( 'blur', function () {
			window.setTimeout( lukk, 150 );
		} );
	}

	function initSubmitCheck( form ) {
		form.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( 'button[name="bvp_submit"]' );

			if ( ! button ) {
				return;
			}

			// Lagrer man fra en seksjon, kommer man tilbake til den samme seksjonen.
			var anker = form.querySelector( '#bvp-anker' );

			if ( anker ) {
				anker.value = button.getAttribute( 'data-bvp-anker' ) || '';
			}

			// Kladd skal aldri blokkeres av påkrevde felt.
			var isDraft = button.value === 'kladd';

			form.querySelectorAll( '[data-bvp-required]' ).forEach( function ( input ) {
				// Felt som er skjult av en betingelse kan ikke fylles ut, og
				// skal heller ikke stoppe innsendingen.
				var skjult = input.closest( '[hidden]' );

				if ( isDraft || skjult ) {
					input.removeAttribute( 'required' );
				} else {
					input.setAttribute( 'required', 'required' );
				}
			} );

			if ( isDraft ) {
				form.setAttribute( 'novalidate', 'novalidate' );
			} else {
				form.removeAttribute( 'novalidate' );
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.bvp' ).forEach( function ( root ) {
			if ( ! engang( root, 'rot' ) ) {
				return;
			}

			initCounters( root );
			initConditionals( root );
			initAccess( root );
			initForetakSok( root );
			initEierVelger( root );
			initMedredaktorer( root );
			initKildeSok( root );

			initUtkast( root );
			initDiktering( root );
			initNevning( root );
			fjernLesMer( root );

			var form = root.querySelector( '.bvp-form' );
			if ( form ) {
				initSubmitCheck( form );
			}
		} );
	} );
}() );
