/* BV-pilot/prosjekt - skjemaeditor */
( function () {
	'use strict';

	var counter = 0;

	function rull( el ) {
		try {
			if ( el && typeof el.scrollIntoView === 'function' ) {
				el.scrollIntoView( { block: 'center' } );
			}
		} catch ( e ) {}
	}

	function esc( text ) {
		var d = document.createElement( 'div' );
		d.textContent = text === null || text === undefined ? '' : String( text );
		return d.innerHTML;
	}

	function uid( prefix ) {
		counter += 1;
		return prefix + 'ny' + counter + '_' + Date.now().toString( 36 );
	}

	function renumber( container, selector ) {
		var rows = container.querySelectorAll( ':scope > ' + selector );
		Array.prototype.forEach.call( rows, function ( row, i ) {
			var sort = row.querySelector( ':scope > .bvp-sort' );
			if ( sort ) {
				sort.value = i;
			}
		} );
	}

	function containerOf( row ) {
		return row.parentElement;
	}

	function move( row, direction ) {
		var parent = containerOf( row );
		var sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;

		if ( ! sibling || ! sibling.matches( '.bvp-ed-section, .bvp-ed-field' ) ) {
			return;
		}

		if ( direction < 0 ) {
			parent.insertBefore( row, sibling );
		} else {
			parent.insertBefore( sibling, row );
		}

		renumber( parent, row.matches( '.bvp-ed-section' ) ? '.bvp-ed-section' : '.bvp-ed-field' );
		rull( row );
	}

	function fromTemplate( id, replacements ) {
		var tpl = document.getElementById( id );
		if ( ! tpl ) {
			return null;
		}

		var html = tpl.innerHTML;
		Object.keys( replacements ).forEach( function ( token ) {
			html = html.split( token ).join( replacements[ token ] );
		} );

		var wrap = document.createElement( 'div' );
		wrap.innerHTML = html;
		return wrap.firstElementChild;
	}

	function syncFieldUi( field ) {
		var type = field.querySelector( '.bvp-ed-type' );
		if ( ! type ) {
			return;
		}

		var value = type.value;
		var hasOptions = [ 'select', 'radio', 'checkbox' ].indexOf( value ) !== -1;
		var hasMax = [ 'text', 'textarea' ].indexOf( value ) !== -1;
		var isFile = [ 'file', 'files' ].indexOf( value ) !== -1;

		var erOppslag = value === 'lookup';
		field.querySelectorAll( '.bvp-ed-lookup' ).forEach( function ( el ) { el.hidden = ! erOppslag; } );

		if ( erOppslag ) {
			syncOppslag( field );
		}

		var options = field.querySelector( '.bvp-ed-options' );
		var manglervarsel = field.querySelector( '.bvp-ed-valgvarsel' );
		var max = field.querySelector( '.bvp-ed-maxlength' );
		var required = field.querySelector( '.bvp-ed-check' );
		var tittel = field.querySelector( '.bvp-ed-tittel' );

		// Bare tekstfelt kan gi registreringen tittel.
		if ( tittel ) {
			tittel.hidden = ! hasMax;

			if ( ! hasMax ) {
				var av = tittel.querySelector( '.bvp-ed-tittel-input' );
				if ( av ) { av.checked = false; }
			}
		}

		if ( options ) { options.hidden = ! hasOptions; }

		// Et valgfelt uten alternativer kan ikke vises i skjemaet.
		if ( hasOptions ) {
			var tekstfelt = options ? options.querySelector( 'textarea' ) : null;
			var tomt = ! tekstfelt || '' === tekstfelt.value.trim();

			if ( ! manglervarsel && tomt && options ) {
				manglervarsel = document.createElement( 'span' );
				manglervarsel.className = 'bvp-ed-valgvarsel';
				manglervarsel.textContent = 'Legg inn minst ett svaralternativ - ellers lagres feltet som tekstfelt.';
				options.appendChild( manglervarsel );
			}

			if ( manglervarsel ) {
				manglervarsel.hidden = ! tomt;
			}
		} else if ( manglervarsel ) {
			manglervarsel.hidden = true;
		}
		if ( max ) { max.hidden = ! hasMax; }
		if ( required ) { required.hidden = isFile; }

		field.setAttribute( 'data-type', value );

		var hiddenInput = field.querySelector( '.bvp-ed-hidden-input' );
		if ( hiddenInput ) {
			field.setAttribute( 'data-hidden', hiddenInput.checked ? '1' : '0' );
			if ( hiddenInput.checked && required ) {
				required.hidden = true;
			}
		}

		var badge = field.querySelector( '.bvp-ed-field__type' );
		if ( badge ) {
			badge.textContent = type.options[ type.selectedIndex ].text;
		}
	}

	/* Oppslagsfeltet: vis bare kategoriene og meta-nøklene som hører til valgt kilde. */
	function syncOppslag( field ) {
		var velger = field.querySelector( '.bvp-ed-lookup-kilde' );

		if ( ! velger ) {
			return;
		}

		var kilde = velger.value;

		field.querySelectorAll( '.bvp-ed-lookup-kildetype' ).forEach( function ( el ) {
			el.hidden = kilde !== 'kunnskapskilde';
		} );

		field.querySelectorAll( 'optgroup[data-kilde], option[data-kilde]' ).forEach( function ( el ) {
			var treff = el.getAttribute( 'data-kilde' ) === kilde;

			el.hidden = ! treff;
			el.disabled = ! treff;

			if ( ! treff && el.selected ) {
				el.selected = false;
			}
		} );

		var meta = field.querySelector( '.bvp-ed-lookup-meta' );

		if ( meta && meta.selectedOptions.length && meta.selectedOptions[ 0 ].disabled ) {
			meta.value = '';
		}
	}

	/* Viser svaralternativene til det styrende feltet som forslag. */
	function syncBetingelse( field ) {
		var velger = field.querySelector( '.bvp-ed-dep-felt' );
		var liste = field.querySelector( 'datalist' );

		if ( ! velger || ! liste ) {
			return;
		}

		var valgt = velger.options[ velger.selectedIndex ];
		var valg = [];

		if ( valgt ) {
			try {
				valg = JSON.parse( valgt.getAttribute( 'data-bvp-valg' ) || '[]' ) || [];
			} catch ( err ) {
				valg = [];
			}
		}

		liste.innerHTML = valg.map( function ( v ) {
			return '<option value="' + esc( v ) + '"></option>';
		} ).join( '' );
	}

	function syncSection( section ) {
		var input = section.querySelector( '.bvp-ed-hidden-section' );

		if ( input ) {
			section.setAttribute( 'data-hidden', input.checked ? '1' : '0' );
		}
	}

	function syncLabel( field ) {
		var input = field.querySelector( '.bvp-ed-label-input' );
		var label = field.querySelector( '.bvp-ed-field__label' );

		if ( input && label ) {
			label.textContent = input.value || '(uten ledetekst)';
		}
	}

	/* ---- Kollaps av seksjoner (bare visning i editoren) ---- */

	var LAGER = 'bvp-ed-kollaps';

	function lagret() {
		try {
			return JSON.parse( window.localStorage.getItem( LAGER ) || '{}' );
		} catch ( e ) {
			return {};
		}
	}

	function husk( nokkel, lukket ) {
		try {
			var alle = lagret();
			if ( lukket ) { alle[ nokkel ] = 1; } else { delete alle[ nokkel ]; }
			window.localStorage.setItem( LAGER, JSON.stringify( alle ) );
		} catch ( e ) {}
	}

	function seksjonsNokkel( seksjon ) {
		var n = seksjon.querySelector( 'input[name$="[key]"]' );
		return n && n.value ? n.value : seksjon.getAttribute( 'data-idx' );
	}

	function settKollaps( seksjon, lukket, skrivTilLager ) {
		seksjon.classList.toggle( 'bvp-ed-section--lukket', lukket );

		var knapp = seksjon.querySelector( '.bvp-ed-kollaps' );
		if ( knapp ) {
			knapp.setAttribute( 'aria-expanded', lukket ? 'false' : 'true' );
			var pil = knapp.querySelector( '.bvp-ed-kollaps__pil' );
			if ( pil ) { pil.textContent = lukket ? '▸' : '▾'; }
		}

		var hode = seksjon.querySelector( '.bvp-ed-section__head' );

		if ( hode ) {
			var antall = seksjon.querySelectorAll( '.bvp-ed-field' ).length;
			hode.setAttribute( 'data-antall', lukket ? ' (' + antall + ' felt skjult)' : '' );
		}

		if ( false !== skrivTilLager ) {
			husk( seksjonsNokkel( seksjon ), lukket );
		}
	}

	function gjenopprettKollaps( root ) {
		var alle = lagret();

		root.querySelectorAll( '.bvp-ed-section' ).forEach( function ( seksjon ) {
			settKollaps( seksjon, !! alle[ seksjonsNokkel( seksjon ) ], false );
		} );
	}

	/* ---- Klientvalidering: åpne raden feilen ligger i ---- */

	function finnManglende( form ) {
		var feil = null;

		form.querySelectorAll( '[data-bvp-krav]' ).forEach( function ( input ) {
			if ( feil || input.value.trim() !== '' ) {
				return;
			}

			// Et felt som uansett skal slettes, skal ikke stoppe lagringen.
			var rad = input.closest( '.bvp-ed-field' ) || input.closest( '.bvp-ed-section' );
			var slett = rad ? rad.querySelector( 'input[name$="[delete]"]' ) : null;

			if ( slett && slett.checked ) {
				return;
			}

			feil = input;
		} );

		return feil;
	}

	function visKravfeil( form, feil ) {
		try {
			var seksjon = feil.closest( '.bvp-ed-section' );
			var rad = feil.closest( '.bvp-ed-field' );

			if ( seksjon ) { settKollaps( seksjon, false ); }
			if ( rad ) { rad.open = true; }

			var boks = form.querySelector( '.bvp-ed-klientfeil' );

			if ( boks ) {
				boks.hidden = false;
				boks.className = 'notice notice-error bvp-ed-klientfeil';
				boks.querySelector( 'p' ).textContent = rad
					? 'Et felt mangler ledetekst. Fyll den ut, eller kryss av for «Slett feltet», før du lagrer.'
					: 'En seksjon mangler tittel. Fyll den ut før du lagrer.';
				rull( boks );
			}

			feil.focus();
		} catch ( e ) {}
	}

	/* ---- Flytte et felt til en annen seksjon ---- */

	function seksjonsTittel( seksjon ) {
		var t = seksjon.querySelector( '.bvp-ed-title' );
		var navn = t && t.value.trim() ? t.value.trim() : '(uten tittel)';

		if ( seksjon.querySelector( '.bvp-ed-hidden-section' ) && seksjon.querySelector( '.bvp-ed-hidden-section' ).checked ) {
			navn += ' (skjult)';
		}

		return navn;
	}

	function oppdaterTomvarsel( seksjon ) {
		if ( ! seksjon ) {
			return;
		}

		var varsel = seksjon.querySelector( '.bvp-ed-tomvarsel' );

		if ( ! varsel ) {
			return;
		}

		var synlige = Array.prototype.filter.call(
			seksjon.querySelectorAll( '.bvp-ed-field' ),
			function ( felt ) {
				var skjult = felt.querySelector( '.bvp-ed-hidden-input' );
				var slett = felt.querySelector( 'input[name$="[delete]"]' );

				return ! ( skjult && skjult.checked ) && ! ( slett && slett.checked );
			}
		).length;

		varsel.hidden = synlige > 0;
	}

	function oppdaterAlleTomvarsler( root ) {
		root.querySelectorAll( '.bvp-ed-section' ).forEach( oppdaterTomvarsel );
	}

	function fyllFlyttevalg( root ) {
		var seksjoner = Array.prototype.slice.call( root.querySelectorAll( '.bvp-ed-section' ) );

		root.querySelectorAll( '.bvp-flytt-seksjon' ).forEach( function ( velger ) {
			var min = velger.closest( '.bvp-ed-section' );
			var html = '<option value="">- velg seksjon -</option>';

			seksjoner.forEach( function ( seksjon ) {
				if ( seksjon === min ) {
					return;
				}

				html += '<option value="' + esc( seksjon.getAttribute( 'data-idx' ) ) + '">' +
					esc( seksjonsTittel( seksjon ) ) + '</option>';
			} );

			velger.innerHTML = html;
		} );
	}

	function byttNavneprefiks( rad, fraIdx, tilIdx ) {
		var fra = 'sections[' + fraIdx + ']';
		var til = 'sections[' + tilIdx + ']';

		rad.querySelectorAll( '[name]' ).forEach( function ( input ) {
			var navn = input.getAttribute( 'name' );

			if ( navn && navn.indexOf( fra ) === 0 ) {
				input.setAttribute( 'name', til + navn.slice( fra.length ) );
			}
		} );
	}

	function byttFeltindeks( rad, nyIdx ) {
		rad.querySelectorAll( '[name]' ).forEach( function ( input ) {
			var navn = input.getAttribute( 'name' );

			if ( navn ) {
				input.setAttribute( 'name', navn.replace( /\[fields\]\[[^\]]*\]/, '[fields][' + nyIdx + ']' ) );
			}
		} );
	}

	/* cloneNode tar ikke med verdier brukeren har endret - kopier dem manuelt. */
	function kopierVerdier( fra, til ) {
		var a = fra.querySelectorAll( 'input, select, textarea' );
		var b = til.querySelectorAll( 'input, select, textarea' );

		for ( var i = 0; i < a.length && i < b.length; i++ ) {
			if ( a[ i ].tagName !== b[ i ].tagName ) {
				continue;
			}

			if ( 'SELECT' === a[ i ].tagName ) {
				for ( var j = 0; j < a[ i ].options.length && j < b[ i ].options.length; j++ ) {
					b[ i ].options[ j ].selected = a[ i ].options[ j ].selected;
				}
			} else if ( 'checkbox' === a[ i ].type || 'radio' === a[ i ].type ) {
				b[ i ].checked = a[ i ].checked;
			} else {
				b[ i ].value = a[ i ].value;
			}
		}
	}

	function kopierFelt( rad, root ) {
		var seksjon = rad.closest( '.bvp-ed-section' );
		var liste = seksjon ? seksjon.querySelector( '.bvp-ed-fields' ) : null;

		if ( ! liste ) {
			return null;
		}

		var ny = rad.cloneNode( true );

		byttFeltindeks( ny, uid( 'f' ) );
		kopierVerdier( rad, ny );

		/* Tom nøkkel = serveren lager en ny, unik lagringsnøkkel ved lagring. */
		ny.querySelectorAll( '[name$="[key]"]' ).forEach( function ( input ) { input.value = ''; } );
		ny.querySelectorAll( '.bvp-ed-id-input' ).forEach( function ( input ) { input.value = '0'; } );
		ny.querySelectorAll( '[name$="[delete]"]' ).forEach( function ( input ) { input.checked = false; } );

		/* Bare ett felt kan gi tittelen - kopien arver ikke valget. */
		ny.querySelectorAll( '.bvp-ed-tittel-input' ).forEach( function ( input ) { input.checked = false; } );

		var nokkelvisning = ny.querySelector( '.bvp-ed-key' );
		if ( nokkelvisning ) { nokkelvisning.textContent = '(ny nøkkel ved lagring)'; }

		var idvisning = ny.querySelector( '.bvp-ed-id' );
		if ( idvisning ) { idvisning.textContent = '#ny'; }

		ny.setAttribute( 'data-id', '0' );

		var ledetekst = ny.querySelector( '.bvp-ed-label-input' );
		if ( ledetekst ) { ledetekst.value = 'Kopi av ' + ( ledetekst.value || 'felt' ); }

		/* Avhengighet peker på en annen feltnøkkel - den følger kopien uendret. */
		liste.insertBefore( ny, rad.nextElementSibling );

		settKollaps( seksjon, false );
		ny.open = true;
		renumber( liste, '.bvp-ed-field' );
		syncFieldUi( ny );
		syncLabel( ny );
		fyllFlyttevalg( root );
		oppdaterTomvarsel( seksjon );

		var beskjed = root.querySelector( '.bvp-ed-klientfeil' );

		if ( beskjed ) {
			beskjed.hidden = false;
			beskjed.className = 'notice notice-info bvp-ed-klientfeil';
			beskjed.querySelector( 'p' ).textContent = 'Feltet er kopiert. Endre ledeteksten og lagre skjemaet - kopien får sin egen lagringsnøkkel.';
		}

		if ( ledetekst ) {
			ledetekst.focus();
			ledetekst.select();
		}

		rull( ny );

		return ny;
	}

	function flyttFelt( rad, tilIdx, root ) {
		var fraSeksjon = rad.closest( '.bvp-ed-section' );
		var tilSeksjon = root.querySelector( '.bvp-ed-section[data-idx="' + tilIdx + '"]' );

		if ( ! fraSeksjon || ! tilSeksjon || fraSeksjon === tilSeksjon ) {
			return;
		}

		var fraListe = fraSeksjon.querySelector( '.bvp-ed-fields' );
		var tilListe = tilSeksjon.querySelector( '.bvp-ed-fields' );

		byttNavneprefiks( rad, fraSeksjon.getAttribute( 'data-idx' ), tilIdx );
		tilListe.appendChild( rad );

		renumber( fraListe, '.bvp-ed-field' );
		renumber( tilListe, '.bvp-ed-field' );

		settKollaps( tilSeksjon, false );
		rad.open = true;
		fyllFlyttevalg( root );
		oppdaterTomvarsel( fraSeksjon );
		oppdaterTomvarsel( tilSeksjon );

		var beskjed = root.querySelector( '.bvp-ed-klientfeil' );

		if ( beskjed ) {
			beskjed.hidden = false;
			beskjed.className = 'notice notice-info bvp-ed-klientfeil';
			beskjed.querySelector( 'p' ).textContent = 'Feltet er flyttet til «' + seksjonsTittel( tilSeksjon ) +
				'». Husk å lagre skjemaet.';
		}

		rull( rad );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var root = document.getElementById( 'bvp-editor-form' );
		if ( ! root ) {
			return;
		}

		Array.prototype.forEach.call( root.querySelectorAll( '.bvp-ed-field' ), syncFieldUi );
		Array.prototype.forEach.call( root.querySelectorAll( '.bvp-ed-field' ), syncBetingelse );
		Array.prototype.forEach.call( root.querySelectorAll( '.bvp-ed-section' ), syncSection );
		gjenopprettKollaps( root );
		fyllFlyttevalg( root );
		oppdaterAlleTomvarsler( root );

		root.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( 'button' );
			if ( ! button ) {
				return;
			}

			if ( button.classList.contains( 'bvp-move-up' ) || button.classList.contains( 'bvp-move-down' ) ) {
				event.preventDefault();
				var row = button.closest( '.bvp-ed-field' ) || button.closest( '.bvp-ed-section' );
				if ( row ) {
					move( row, button.classList.contains( 'bvp-move-up' ) ? -1 : 1 );
				}
				return;
			}

			if ( button.classList.contains( 'bvp-kopier-felt' ) ) {
				event.preventDefault();
				var kilderad = button.closest( '.bvp-ed-field' );
				if ( kilderad ) {
					kopierFelt( kilderad, root );
					dirty = true;
				}
				return;
			}

			if ( button.classList.contains( 'bvp-add-field' ) ) {
				event.preventDefault();
				var section = button.closest( '.bvp-ed-section' );
				var list = section.querySelector( '.bvp-ed-fields' );
				var node = fromTemplate( 'bvp-tpl-field', {
					'__SIDX__': section.getAttribute( 'data-idx' ),
					'__FIDX__': uid( 'f' )
				} );

				if ( node ) {
					settKollaps( section, false );
					list.appendChild( node );
					node.open = true;
					renumber( list, '.bvp-ed-field' );
					syncFieldUi( node );
					fyllFlyttevalg( root );
					oppdaterTomvarsel( section );
					var input = node.querySelector( '.bvp-ed-label-input' );
					if ( input ) { input.focus(); }
				}
				return;
			}

			if ( button.classList.contains( 'bvp-ed-kollaps' ) ) {
				event.preventDefault();
				var sek = button.closest( '.bvp-ed-section' );
				if ( sek ) {
					settKollaps( sek, ! sek.classList.contains( 'bvp-ed-section--lukket' ) );
				}
				return;
			}

			if ( button.classList.contains( 'bvp-skjul-alle' ) || button.classList.contains( 'bvp-vis-alle' ) ) {
				event.preventDefault();
				var lukk = button.classList.contains( 'bvp-skjul-alle' );
				root.querySelectorAll( '.bvp-ed-section' ).forEach( function ( sek2 ) {
					settKollaps( sek2, lukk );
				} );
				return;
			}

			if ( button.classList.contains( 'bvp-add-section' ) ) {
				event.preventDefault();
				var sections = document.getElementById( 'bvp-sections' );
				var idx = uid( 's' );
				var fresh = fromTemplate( 'bvp-tpl-section', { '__SIDX__': idx } );

				if ( fresh ) {
					sections.appendChild( fresh );
					renumber( sections, '.bvp-ed-section' );
					fyllFlyttevalg( root );
					oppdaterTomvarsel( fresh );
					var title = fresh.querySelector( '.bvp-ed-title' );
					if ( title ) { title.focus(); }
				}
			}
		} );

		root.addEventListener( 'change', function ( event ) {
			if ( event.target.classList.contains( 'bvp-ed-type' ) || event.target.classList.contains( 'bvp-ed-hidden-input' ) ) {
				syncFieldUi( event.target.closest( '.bvp-ed-field' ) );
			}

			if ( event.target.classList.contains( 'bvp-ed-lookup-kilde' ) ) {
				syncOppslag( event.target.closest( '.bvp-ed-field' ) );
			}

			if ( event.target.classList.contains( 'bvp-ed-dep-felt' ) ) {
				syncBetingelse( event.target.closest( '.bvp-ed-field' ) );
			}

			// Bare ett felt kan gi tittelen - kryss av her, så ryker de andre.
			if ( event.target.classList.contains( 'bvp-ed-tittel-input' ) && event.target.checked ) {
				root.querySelectorAll( '.bvp-ed-tittel-input' ).forEach( function ( annen ) {
					if ( annen !== event.target ) {
						annen.checked = false;
					}
				} );
			}

			if ( event.target.classList.contains( 'bvp-ed-hidden-section' ) ) {
				syncSection( event.target.closest( '.bvp-ed-section' ) );
				fyllFlyttevalg( root );
			}

			if ( event.target.classList.contains( 'bvp-flytt-seksjon' ) ) {
				var mal = event.target.value;

				if ( mal ) {
					flyttFelt( event.target.closest( '.bvp-ed-field' ), mal, root );
				}

				event.target.value = '';
			}

			if ( event.target.classList.contains( 'bvp-ed-hidden-input' ) ) {
				oppdaterTomvarsel( event.target.closest( '.bvp-ed-section' ) );
			}

			if ( event.target.type === 'checkbox' && event.target.name.indexOf( '[delete]' ) !== -1 ) {
				oppdaterTomvarsel( event.target.closest( '.bvp-ed-section' ) );
				var row = event.target.closest( '.bvp-ed-field' ) || event.target.closest( '.bvp-ed-section' );
				if ( row ) {
					row.classList.toggle( 'bvp-ed-marked', event.target.checked );
				}
			}
		} );

		root.addEventListener( 'input', function ( event ) {
			if ( 'TEXTAREA' === event.target.tagName && event.target.closest( '.bvp-ed-options' ) ) {
				syncFieldUi( event.target.closest( '.bvp-ed-field' ) );
			}

			if ( event.target.classList.contains( 'bvp-ed-label-input' ) ) {
				syncLabel( event.target.closest( '.bvp-ed-field' ) );
			}

			if ( event.target.classList.contains( 'bvp-ed-title' ) ) {
				fyllFlyttevalg( root );
			}
		} );

		// Advar hvis man forlater siden med ulagrede endringer.
		var dirty = false;
		root.addEventListener( 'input', function () { dirty = true; } );
		root.addEventListener( 'change', function () { dirty = true; } );
		root.addEventListener( 'submit', function ( event ) {
			var feil = finnManglende( root );

			if ( feil ) {
				event.preventDefault();
				visKravfeil( root, feil );
				return;
			}

			dirty = false;
		} );

		window.addEventListener( 'beforeunload', function ( event ) {
			if ( dirty ) {
				event.preventDefault();
				event.returnValue = '';
			}
		} );
	} );
}() );
