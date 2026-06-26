/**
 * OneDong · 暗色模式切换(三态)+ 主题色相滑块
 * --------------------------------------------------------------
 * 三态:light(亮) → dark(暗) → auto(跟随系统) → light …
 * 首帧 data-theme 由 header.php 内联的 anti-flash 脚本在渲染前设置,避免闪白;
 * 本脚本负责:点击按钮在三态间循环、跟随系统实时变化、色相滑块联动。
 */
( function () {
	'use strict';

	var THEME_KEY = 'onedong-theme';
	var HUE_KEY    = 'onedong-hue';

	function systemDark() {
		return !!( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches );
	}

	// 偏好:light / dark / auto(默认 auto,即跟随系统)
	function pref() {
		try {
			var s = localStorage.getItem( THEME_KEY );
			if ( s === 'light' || s === 'dark' || s === 'auto' ) {
				return s;
			}
		} catch ( e ) {}
		return 'auto';
	}

	function resolve( p ) {
		if ( p === 'light' || p === 'dark' ) {
			return p;
		}
		return systemDark() ? 'dark' : 'light';
	}

	function applyPref( p ) {
		var el = document.documentElement;
		el.setAttribute( 'data-theme', resolve( p ) );
		el.setAttribute( 'data-theme-pref', p );
	}

	function setButtonState( toggle, p ) {
		toggle.setAttribute( 'data-pref', p );
		toggle.setAttribute( 'aria-pressed', resolve( p ) === 'dark' ? 'true' : 'false' );
		toggle.setAttribute(
			'title',
			{ light: '当前:亮色', dark: '当前:暗色', auto: '当前:跟随系统' }[ p ] || ''
		);
	}

	function nextPref( p ) {
		return p === 'light' ? 'dark' : ( p === 'dark' ? 'auto' : 'light' );
	}

	// —— 主题色相滑块:实时改 --hue 并记忆 ——
	function initHue( slider ) {
		if ( ! slider ) {
			return;
		}
		try {
			var saved = localStorage.getItem( HUE_KEY );
			if ( saved !== null && saved !== '' ) {
				document.documentElement.style.setProperty( '--hue', saved );
				slider.value = saved;
			}
		} catch ( e ) {}
		slider.addEventListener( 'input', function () {
			document.documentElement.style.setProperty( '--hue', this.value );
			try { localStorage.setItem( HUE_KEY, this.value ); } catch ( e ) {}
		} );
	}

	function init() {
		var toggle = document.querySelector( '.theme-toggle' );
		var slider = document.getElementById( 'hue-slider' );

		var p = pref();
		applyPref( p );
		if ( toggle ) {
			setButtonState( toggle, p );
		}

		if ( toggle ) {
			toggle.addEventListener( 'click', function () {
				p = nextPref( p );
				applyPref( p );
				setButtonState( toggle, p );
				try { localStorage.setItem( THEME_KEY, p ); } catch ( e ) {}
			} );
		}

		// auto 偏好时,系统主题变化实时跟随
		if ( window.matchMedia ) {
			var mql = window.matchMedia( '(prefers-color-scheme: dark)' );
			var onChange = function () {
				if ( pref() === 'auto' ) {
					document.documentElement.setAttribute( 'data-theme', systemDark() ? 'dark' : 'light' );
				}
			};
			if ( mql.addEventListener ) {
				mql.addEventListener( 'change', onChange );
			} else if ( mql.addListener ) {
				mql.addListener( onChange );
			}
		}

		initHue( slider );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
