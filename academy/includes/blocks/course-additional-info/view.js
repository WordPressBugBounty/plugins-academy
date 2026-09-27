/* Course Benefits & Requirements: tabs switch panels (arrow keys move
   between tabs); the accordion keeps one panel open at a time. */
( function () {
	const initTabs = ( root ) => {
		const tabs = Array.from( root.querySelectorAll( '[role="tab"]' ) );
		const select = ( tab, focus ) => {
			tabs.forEach( ( other ) => {
				const on = other === tab;
				other.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				other.tabIndex = on ? 0 : -1;
				const panel = document.getElementById( other.getAttribute( 'aria-controls' ) );
				if ( panel ) {
					panel.hidden = ! on;
				}
			} );
			if ( focus ) {
				tab.focus();
			}
		};
		tabs.forEach( ( tab, index ) => {
			tab.addEventListener( 'click', () => select( tab, false ) );
			tab.addEventListener( 'keydown', ( event ) => {
				const keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
				if ( keys[ event.key ] ) {
					event.preventDefault();
					select( tabs[ ( index + keys[ event.key ] + tabs.length ) % tabs.length ], true );
				} else if ( 'Home' === event.key || 'End' === event.key ) {
					event.preventDefault();
					select( 'Home' === event.key ? tabs[ 0 ] : tabs[ tabs.length - 1 ], true );
				}
			} );
		} );
	};

	const initAccordion = ( root ) => {
		const items = Array.from( root.querySelectorAll( 'details' ) );
		items.forEach( ( item ) => {
			item.addEventListener( 'toggle', () => {
				if ( item.open ) {
					items.forEach( ( other ) => {
						if ( other !== item ) {
							other.open = false;
						}
					} );
				}
			} );
		} );
	};

	const init = () => {
		document.querySelectorAll( '[data-academy-tabs]:not([data-ready])' ).forEach( ( root ) => {
			root.dataset.ready = '1';
			initTabs( root );
		} );
		document.querySelectorAll( '[data-academy-accordion]:not([data-ready])' ).forEach( ( root ) => {
			root.dataset.ready = '1';
			initAccordion( root );
		} );
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
