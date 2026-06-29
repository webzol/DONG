/**
 * OneDong · 深浅色切换(二态:亮 / 暗)· v2.5.12
 * --------------------------------------------------------------
 * 点击在 light ↔ dark 间切换;localStorage 记忆。
 * 首帧 data-theme 由 header.php 内联 anti-flash 脚本设置(无记忆时跟随系统),避免闪白。
 * (原三态含 auto/电脑图标,TD 要求只留日 / 月,已移除 auto。)
 */
( function () {
	'use strict';

	var THEME_KEY = 'onedong-theme';

	function systemDark() {
		return !!( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches );
	}

	// 偏好:light / dark;无记忆时跟随系统(返回具体态,不返回 auto)
	function pref() {
		try {
			var s = localStorage.getItem( THEME_KEY );
			if ( s === 'light' || s === 'dark' ) {
				return s;
			}
		} catch ( e ) {}
		return systemDark() ? 'dark' : 'light';
	}

	function applyPref( p ) {
		document.documentElement.setAttribute( 'data-theme', p );
	}

	function setButtonState( toggle, p ) {
		toggle.setAttribute( 'data-pref', p );
		toggle.setAttribute( 'aria-pressed', p === 'dark' ? 'true' : 'false' );
		toggle.setAttribute( 'title', p === 'dark' ? '当前:暗色(点击切到亮色)' : '当前:亮色(点击切到暗色)' );
	}

	function init() {
		var toggle = document.querySelector( '.theme-toggle' );
		var p = pref();
		applyPref( p );
		if ( toggle ) {
			setButtonState( toggle, p );
			toggle.addEventListener( 'click', function () {
				p = ( p === 'light' ) ? 'dark' : 'light';
				applyPref( p );
				setButtonState( toggle, p );
				try { localStorage.setItem( THEME_KEY, p ); } catch ( e ) {}
			} );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
