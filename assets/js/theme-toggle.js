/**
 * OneDong · 暗色模式切换(三态)
 * --------------------------------------------------------------
 * 三态:light(亮) → dark(暗) → auto(跟随系统) → light …
 * 首帧 data-theme 由 header.php 内联的 anti-flash 脚本在渲染前设置,避免闪白;
 * 本脚本负责:点击按钮在三态间循环、跟随系统实时变化。
 * v2.0:已移除主题色相滑块逻辑(主色固定 suxing blue)。
 */
( function () {
	'use strict';

	var THEME_KEY = 'onedong-theme';

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

	function init() {
		var toggle = document.querySelector( '.theme-toggle' );

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
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
