/**
 * OneDong · 暗色模式切换
 * --------------------------------------------------------------
 * 首次 data-theme 由 header.php 内联的 anti-flash 脚本在渲染前设置;
 * 本脚本仅负责:点击切换按钮时翻转主题 + 写入 localStorage 记忆。
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'onedong-theme';

	function applyTheme( theme ) {
		document.documentElement.setAttribute( 'data-theme', theme );
	}

	function currentTheme() {
		var t = document.documentElement.getAttribute( 'data-theme' );
		if ( t === 'light' || t === 'dark' ) {
			return t;
		}
		try {
			var saved = localStorage.getItem( STORAGE_KEY );
			if ( saved === 'light' || saved === 'dark' ) {
				return saved;
			}
		} catch ( e ) {}
		if ( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches ) {
			return 'dark';
		}
		return 'light';
	}

	function init() {
		var toggle = document.querySelector( '.theme-toggle' );
		if ( ! toggle ) {
			return;
		}

		// 同步初始按钮状态
		toggle.setAttribute( 'aria-pressed', currentTheme() === 'dark' ? 'true' : 'false' );

		toggle.addEventListener( 'click', function () {
			var next = currentTheme() === 'dark' ? 'light' : 'dark';
			applyTheme( next );
			try {
				localStorage.setItem( STORAGE_KEY, next );
			} catch ( e ) {}
			toggle.setAttribute( 'aria-pressed', next === 'dark' ? 'true' : 'false' );
		} );

		// 用户切换系统主题时,若未手动设置过,则跟随系统
		if ( window.matchMedia ) {
			var mql = window.matchMedia( '(prefers-color-scheme: dark)' );
			var onChange = function ( e ) {
				var saved = null;
				try {
					saved = localStorage.getItem( STORAGE_KEY );
				} catch ( err ) {}
				if ( saved !== 'light' && saved !== 'dark' ) {
					applyTheme( e.matches ? 'dark' : 'light' );
					toggle.setAttribute( 'aria-pressed', e.matches ? 'true' : 'false' );
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
