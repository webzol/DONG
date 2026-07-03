/**
 * OneDong · 作者详情页 文章 / 朋友圈 标签切换 · v6.0.13
 * 纯渐进增强:无 JS 时文章标签默认展示(is-active),朋友圈隐藏。
 */
( function () {
	'use strict';

	var btns = document.querySelectorAll( '.author-tabs__btn' );
	if ( ! btns.length ) {
		return;
	}
	var panels = document.querySelectorAll( '.author-tab' );

	function activate( name ) {
		Array.prototype.forEach.call( btns, function ( b ) {
			var on = b.getAttribute( 'data-tab' ) === name;
			b.classList.toggle( 'is-active', on );
			b.setAttribute( 'aria-selected', on ? 'true' : 'false' );
		} );
		Array.prototype.forEach.call( panels, function ( p ) {
			p.classList.toggle( 'is-active', p.id === 'author-tab-' + name );
		} );
	}

	Array.prototype.forEach.call( btns, function ( b ) {
		b.addEventListener( 'click', function () {
			activate( b.getAttribute( 'data-tab' ) );
		} );
	} );
} )();
