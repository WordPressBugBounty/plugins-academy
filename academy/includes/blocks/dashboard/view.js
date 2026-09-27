/**
 * Dashboard store: the menu collapsing on large screens, sliding in on small
 * ones, and the account menu. Only these controls are interactive; the frame
 * around them follows through its classes.
 */
import { store, getElement } from '@wordpress/interactivity';

const COLLAPSED_KEY = 'academy_dashboard_collapsed';
const SMALL = '(max-width: 1024px)';

const read = ( key ) => {
	try {
		return window.localStorage.getItem( key );
	} catch ( error ) {
		return null;
	}
};

const write = ( key, value ) => {
	try {
		window.localStorage.setItem( key, value );
	} catch ( error ) {
		// Private windows: the choice lasts for this page only.
	}
};

// The state comes from the server (collapsed, menuOpen, userOpen): defaults
// set here would win over it.
const { state, actions } = store( 'academy/dashboard', {
	actions: {
		toggleCollapse() {
			state.collapsed = ! state.collapsed;
			state.userOpen = false;
			write( COLLAPSED_KEY, state.collapsed ? '1' : '0' );
		},
		openMenu() {
			state.menuOpen = true;
		},
		closeMenu() {
			state.menuOpen = false;
		},
		toggleUser() {
			state.userOpen = ! state.userOpen;
		},
		onKeydown( event ) {
			if ( 'Escape' === event.key ) {
				state.userOpen = false;
				state.menuOpen = false;
			}
		},
		onDocumentClick( event ) {
			if ( state.userOpen && ! event.target.closest( '.academy-dash-user' ) ) {
				state.userOpen = false;
			}
		},
	},
	callbacks: {
		init() {
			// Back on a large screen, the slide-in menu is put away.
			const small = window.matchMedia( SMALL );
			small.addEventListener( 'change', () => {
				if ( ! small.matches ) {
					actions.closeMenu();
				}
			} );
		},
		syncFrame() {
			const frame = getElement().ref?.closest( '.academy-dash' );
			if ( frame ) {
				frame.classList.toggle( 'is-collapsed', state.collapsed );
				frame.classList.toggle( 'is-menu-open', state.menuOpen );
			}
		},
	},
} );

// Someone's own open or closed menu wins over the site's default.
const saved = read( COLLAPSED_KEY );
if ( '1' === saved || '0' === saved ) {
	state.collapsed = '1' === saved;
}
