/**
 * Learn page store: sidebar, panels, theme and width, progress, and moving
 * between topics without reloading the page.
 */
import * as interactivity from '@wordpress/interactivity';

const { store, getContext, getElement, getServerState } = interactivity;
// Actions that call preventDefault() must run synchronously with the event.
const withSyncEvent = interactivity.withSyncEvent || ( ( callback ) => callback );

const THEME_KEY = 'academy_theme';
const WIDTH_KEY = 'academy_learn_width';
const SIDEBAR_KEY = 'academy_learn_sidebar';
const WIDTHS = [ 'standard', 'wide', 'focused' ];
const SMALL = '(max-width: 1023px)';

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

// "%1$d of %2$d", "%d%%" and "%s" placeholders, filled in order.
const format = ( text, ...values ) => {
	let next = 0;
	return String( text || '' )
		.replace( /%(\d+)\$[ds]/g, ( match, position ) => values[ position - 1 ] )
		.replace( /%[ds]/g, () => values[ next++ ] )
		.replace( /%%/g, '%' );
};

const isModifiedClick = ( event ) =>
	event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

const { state, actions } = store( 'academy/learn', {
	state: {
		userTheme: '',
		small: false,
		navigating: false,
		get isDark() {
			return 'dark' === state.userTheme;
		},
		get completedCount() {
			return Object.values( state.completedKeys || {} ).filter( Boolean ).length;
		},
		get percent() {
			return state.total ? Math.round( ( state.completedCount / state.total ) * 100 ) : 0;
		},
		get percentText() {
			return `${ state.percent }%`;
		},
		get percentComplete() {
			return format( state.i18n.percentComplete, state.percent );
		},
		get leftText() {
			const left = Math.max( state.total - state.completedCount, 0 );
			return format( 1 === left ? state.i18n.topicLeft : state.i18n.topicsLeft, left );
		},
		get progressCount() {
			return format( state.i18n.progressCount, state.completedCount, state.total );
		},
		get progressLabel() {
			return format( state.i18n.progressLabel, state.percent );
		},
		get progressOffset() {
			return ( 2 * Math.PI * 15 * ( 1 - state.percent / 100 ) ).toFixed( 2 );
		},
		get isCurrent() {
			return getContext().key === state.currentKey;
		},
		get ariaCurrent() {
			return getContext().key === state.currentKey ? 'page' : null;
		},
		get isCompleted() {
			return !! ( state.completedKeys || {} )[ getContext().key ];
		},
		get completeLabel() {
			return state.isCompleted ? state.i18n.completed : state.i18n.markComplete;
		},
		get isDrawerOpen() {
			return state.drawer === getContext().drawer;
		},
		get sidebarInert() {
			return state.small && ! state.sidebarOpen;
		},
		get widthLabel() {
			return format( state.i18n.widthLabel, state.i18n.widths[ state.width ] || state.width );
		},
		// Reviews open once the student is far enough through the course.
		get canReview() {
			return !! state.canReviewCourse && state.percent >= ( state.reviewMinimum || 0 );
		},
		get favoriteLabel() {
			return getContext().favorited ? state.i18n.removeFavorite : state.i18n.addFavorite;
		},
	},

	actions: {
		toggleSidebar() {
			state.sidebarOpen = ! state.sidebarOpen;
			state.drawer = '';
			state.menuOpen = false;
			if ( ! state.small ) {
				write( SIDEBAR_KEY, state.sidebarOpen ? '1' : '0' );
			}
		},

		toggleDrawer() {
			const { drawer } = getContext();
			state.drawer = state.drawer === drawer ? '' : drawer;
			state.menuOpen = false;
			if ( state.small ) {
				state.sidebarOpen = false;
			}
		},

		openReview() {
			state.menuOpen = false;
			window.dispatchEvent(
				new window.CustomEvent( 'academy:learn-open-review', {
					detail: { courseId: state.courseId },
				} )
			);
		},

		toggleMenu() {
			state.menuOpen = ! state.menuOpen;
		},

		closeOverlays() {
			state.drawer = '';
			state.menuOpen = false;
			if ( state.small ) {
				state.sidebarOpen = false;
			}
		},

		onKeydown( event ) {
			if ( 'Escape' === event.key && ( state.drawer || state.menuOpen || ( state.small && state.sidebarOpen ) ) ) {
				actions.closeOverlays();
			}
		},

		toggleTheme() {
			state.userTheme = state.isDark ? 'light' : 'dark';
			write( THEME_KEY, state.userTheme );
			document.body.setAttribute( 'data-academy-user-theme', state.userTheme );
		},

		cycleWidth() {
			const index = WIDTHS.indexOf( state.width );
			state.width = WIDTHS[ ( index + 1 ) % WIDTHS.length ];
			write( WIDTH_KEY, state.width );
		},

		toggleSection() {
			const context = getContext();
			context.open = ! context.open;
		},

		dismissNotice() {
			state.notice = '';
		},

		navigate: withSyncEvent( function* ( event ) {
			if ( isModifiedClick( event ) ) {
				return;
			}
			const { ref } = getElement();
			const href = ref && ref.getAttribute( 'href' );
			if ( ! href ) {
				return;
			}
			event.preventDefault();

			const { type } = getContext();
			// Topics that bring their own scripts load as a whole page.
			if ( ( state.fullReloadTypes || [] ).includes( type ) || ( state.fullReloadTypes || [] ).includes( state.currentType ) ) {
				window.location.assign( href );
				return;
			}

			state.drawer = '';
			state.menuOpen = false;
			if ( state.small ) {
				state.sidebarOpen = false;
			}
			window.dispatchEvent( new window.CustomEvent( 'academy:learn-before-navigate', { detail: { href } } ) );
			state.navigating = true;
			try {
				const router = yield import( '@wordpress/interactivity-router' );
				yield router.actions.navigate( href );
			} catch ( error ) {
				window.console.error( 'academy learn navigate', error );
				window.location.assign( href );
				return;
			} finally {
				state.navigating = false;
			}
			window.dispatchEvent( new window.CustomEvent( 'academy:learn-navigated', { detail: { href } } ) );

			const main = document.getElementById( 'academy-learn-main' );
			window.scrollTo( 0, 0 );
			if ( main ) {
				main.scrollTop = 0;
				main.focus( { preventScroll: true } );
			}
		} ),

		*prefetch() {
			const { ref } = getElement();
			const href = ref && ref.getAttribute( 'href' );
			const { type } = getContext();
			if ( ! href || ( state.fullReloadTypes || [] ).includes( type ) ) {
				return;
			}
			try {
				const router = yield import( '@wordpress/interactivity-router' );
				yield router.actions.prefetch( href );
			} catch ( error ) {
				// Prefetching is only a head start.
			}
		},

		*toggleComplete() {
			if ( ! state.loggedIn ) {
				state.notice = state.i18n.loginToTrack;
				return;
			}
			if ( state.busy ) {
				return;
			}
			const { key, id, type } = getContext();
			const was = !! ( state.completedKeys || {} )[ key ];
			state.busy = true;
			state.notice = '';
			state.completedKeys = { ...state.completedKeys, [ key ]: ! was };

			try {
				const body = new window.URLSearchParams();
				body.set( 'action', 'academy/mark_topic_complete' );
				body.set( 'security', state.nonce );
				body.set( 'course_id', state.courseId );
				body.set( 'topic_type', type );
				body.set( 'topic_id', id );
				const response = yield window.fetch( state.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } );
				const result = yield response.json();

				if ( ! result || ! result.success ) {
					state.completedKeys = { ...state.completedKeys, [ key ]: was };
					state.notice = result && 'string' === typeof result.data ? result.data : state.i18n.failed;
					return;
				}

				// The server sends back every completed topic, so the page matches it.
				const saved = 'string' === typeof result.data ? JSON.parse( result.data ) : result.data;
				const keys = {};
				Object.entries( saved || {} ).forEach( ( [ savedType, ids ] ) => {
					Object.keys( ids || {} ).forEach( ( savedId ) => {
						keys[ `${ savedType }-${ savedId }` ] = true;
					} );
				} );
				state.completedKeys = keys;
			} catch ( error ) {
				state.completedKeys = { ...state.completedKeys, [ key ]: was };
				state.notice = state.i18n.failed;
			} finally {
				state.busy = false;
			}
		},

		*toggleFavorite() {
			const context = getContext();
			try {
				const body = new window.URLSearchParams();
				body.set( 'action', 'academy/course_add_to_favorite' );
				body.set( 'security', state.nonce );
				body.set( 'course_id', state.courseId );
				const response = yield window.fetch( state.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } );
				const result = yield response.json();
				if ( result && result.success ) {
					context.favorited = !! ( result.data && result.data.is_added );
				}
			} catch ( error ) {
				state.notice = state.i18n.failed;
			}
		},
	},

	callbacks: {
		init() {
			const saved = read( THEME_KEY );
			let theme = 'dark' === saved || 'light' === saved ? saved : state.defaultTheme;
			if ( 'system' === theme ) {
				theme = window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches ? 'dark' : 'light';
			}
			state.userTheme = theme;
			document.body.setAttribute( 'data-academy-user-theme', theme );

			const width = read( WIDTH_KEY );
			if ( WIDTHS.includes( width ) ) {
				state.width = width;
			}

			const query = window.matchMedia( SMALL );
			const applySize = () => {
				state.small = query.matches;
				if ( state.small ) {
					state.sidebarOpen = false;
				} else {
					const open = read( SIDEBAR_KEY );
					if ( null !== open ) {
						state.sidebarOpen = '1' === open;
					}
				}
			};
			applySize();
			query.addEventListener( 'change', applySize );

			// A video that finishes can complete its lesson and move on.
			window.addEventListener( 'academy:learn-topic-completed', ( event ) => {
				const { type, id, next } = event.detail || {};
				if ( type && id ) {
					state.completedKeys = { ...state.completedKeys, [ `${ type }-${ id }` ]: true };
				}
				if ( next ) {
					const link = document.querySelector( '.academy-learn-footer__nav--next[href]' );
					if ( link ) {
						link.click();
					}
				}
			} );
		},

		// The section holding the topic on screen stays open.
		openCurrentSection() {
			const context = getContext();
			if ( ( context.keys || [] ).includes( state.currentKey ) ) {
				context.open = true;
			}
		},

		// After moving to another topic, take the new page's facts.
		syncServerState() {
			const server = getServerState();
			if ( ! server ) {
				return;
			}
			state.currentKey = server.currentKey;
			state.currentType = server.currentType;
			state.completedKeys = { ...( server.completedKeys || {} ) };
			if ( server.pageTitle ) {
				document.title = server.pageTitle;
			}
			window.requestAnimationFrame( () => {
				const current = document.querySelector( '.academy-learn-topic.is-current' );
				if ( current && current.scrollIntoView ) {
					current.scrollIntoView( { block: 'nearest' } );
				}
			} );
		},
	},
} );
