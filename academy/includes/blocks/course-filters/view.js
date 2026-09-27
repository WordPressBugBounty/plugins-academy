/**
 * Course Filters and Course Sort.
 *
 * Choices update the course grid in place: the page is fetched with the new
 * filters and the grids, filters and sort menus are swapped in, the address
 * is updated (so the filtered list can be shared or bookmarked), focus stays
 * on the control that changed, and screen readers hear the new number of
 * courses. Filter changes replace the current history entry rather than adding
 * one, so Back returns to the page before the course list. Without
 * JavaScript, or if anything fails, the forms simply load the filtered page.
 */
( function () {
	var FORMS = '.academy-course-filters__form, .academy-course-sort__form';
	var REGIONS = [ '.wp-block-query', '.wp-block-academy-course-sort', '.wp-block-academy-course-filters' ];
	var status;
	var busy = false;

	function each( list, callback ) {
		Array.prototype.forEach.call( list, callback );
	}

	function markForms() {
		each( document.querySelectorAll( 'form[data-academy-autosubmit="1"]' ), function ( form ) {
			form.classList.add( 'is-auto-apply' );
		} );
	}

	function announce( message ) {
		if ( ! status ) {
			status = document.createElement( 'div' );
			status.className = 'screen-reader-text';
			status.setAttribute( 'role', 'status' );
			status.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( status );
		}
		status.textContent = '';
		window.setTimeout( function () {
			status.textContent = message;
		}, 50 );
	}

	function formUrl( form ) {
		var url = new URL( form.getAttribute( 'action' ) || window.location.href, window.location.href );
		url.search = '';
		new FormData( form ).forEach( function ( value, name ) {
			if ( 'string' === typeof value && '' === value.trim() && 'academy_search' === name ) {
				return;
			}
			url.searchParams.append( name, value );
		} );
		return url.toString();
	}

	function describeFocus() {
		var active = document.activeElement;
		if ( ! active || ! active.name || ! active.closest( FORMS ) ) {
			return null;
		}
		return { name: active.name, value: active.type === 'checkbox' ? active.value : null };
	}

	function restoreFocus( target ) {
		if ( ! target ) {
			return;
		}
		var selector = '[name="' + target.name.replace( /"/g, '\\"' ) + '"]';
		if ( null !== target.value ) {
			selector += '[value="' + target.value.replace( /"/g, '\\"' ) + '"]';
		}
		var field = document.querySelector( '.wp-block-academy-course-filters ' + selector + ', .wp-block-academy-course-sort ' + selector );
		if ( field ) {
			field.focus( { preventScroll: true } );
		}
	}

	function courseCount( doc ) {
		var count = doc.querySelector( '.academy-course-sort__count' );
		if ( count ) {
			return count.textContent.trim();
		}
		var cards = doc.querySelectorAll( '.wp-block-query .wp-block-post' ).length;
		return 1 === cards ? '1 course' : cards + ' courses';
	}

	function navigate( url, options ) {
		if ( busy || ! window.fetch || ! window.DOMParser ) {
			if ( window.console ) {
				window.console.warn( 'Academy course filters: loading the page instead (busy or unsupported).' );
			}
			window.location.href = url;
			return;
		}
		busy = true;
		var focus = describeFocus();
		each( document.querySelectorAll( '.wp-block-query' ), function ( query ) {
			query.setAttribute( 'aria-busy', 'true' );
		} );

		window
			.fetch( url, { credentials: 'same-origin' } )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( response.status );
				}
				return response.text();
			} )
			.then( function ( html ) {
				var doc = new window.DOMParser().parseFromString( html, 'text/html' );

				// The new page must have the same regions, or fall back to loading it.
				var matches = REGIONS.every( function ( selector ) {
					return document.querySelectorAll( selector ).length === doc.querySelectorAll( selector ).length;
				} );
				if ( ! matches ) {
					if ( window.console ) {
						window.console.warn( 'Academy course filters: loading the page instead (page layout differs).', REGIONS.map( function ( selector ) {
							return selector + ' ' + document.querySelectorAll( selector ).length + '/' + doc.querySelectorAll( selector ).length;
						} ).join( ', ' ) );
					}
					window.location.href = url;
					return;
				}

				// Layout rules for markup that only the new page has.
				var styles = doc.getElementById( 'core-block-supports-inline-css' );
				var current = document.getElementById( 'core-block-supports-inline-css' );
				if ( styles && current ) {
					current.textContent = styles.textContent;
				}

				REGIONS.forEach( function ( selector ) {
					var next = doc.querySelectorAll( selector );
					each( document.querySelectorAll( selector ), function ( element, index ) {
						element.replaceWith( document.importNode( next[ index ], true ) );
					} );
				} );

				window.history.replaceState( window.history.state, '', url );
				document.title = doc.title;
				markForms();
				restoreFocus( focus );
				announce( courseCount( document ) );

				if ( options.scroll ) {
					var first = document.querySelector( '.wp-block-query' );
					if ( first && first.getBoundingClientRect().top < 0 ) {
						var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
						first.scrollIntoView( { behavior: reduce ? 'auto' : 'smooth', block: 'start' } );
					}
				}
			} )
			.catch( function ( error ) {
				if ( window.console ) {
					window.console.warn( 'Academy course filters: loading the page instead.', error );
				}
				window.location.href = url;
			} )
			.then( function () {
				busy = false;
				each( document.querySelectorAll( '.wp-block-query[aria-busy]' ), function ( query ) {
					query.removeAttribute( 'aria-busy' );
				} );
			} );
	}

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target.closest && event.target.closest( FORMS );
		if ( ! form ) {
			return;
		}
		event.preventDefault();
		navigate( formUrl( form ), {} );
	} );

	document.addEventListener( 'change', function ( event ) {
		var form = event.target.closest && event.target.closest( 'form[data-academy-autosubmit="1"]' );
		if ( form && event.target.matches( 'input[type="checkbox"], input[type="radio"], select' ) ) {
			navigate( formUrl( form ), {} );
		}
	} );

	// Pagination of course grids on pages with course filters or sorting.
	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest && event.target.closest( '.wp-block-query-pagination a' );
		if ( ! link || event.metaKey || event.ctrlKey || event.shiftKey || event.button > 0 ) {
			return;
		}
		if ( ! document.querySelector( '.wp-block-academy-course-filters, .wp-block-academy-course-sort' ) ) {
			return;
		}
		event.preventDefault();
		navigate( link.href, { scroll: true } );
	} );

	markForms();
} )();
