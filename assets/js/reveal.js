/**
 * OneDong · 滚动入场动画(渐进增强 · 零依赖)
 * --------------------------------------------------------------
 * 观察所有 [data-reveal] 元素,进入视口时加 .is-revealed 触发 CSS 过渡。
 * - 首帧隐藏由 <html class="js"> + layout.css 的 `html.js [data-reveal]{opacity:0}` 负责
 *   (no-js→js 替换在 header.php 的 anti-flash 脚本中首帧前同步完成,避免闪现)。
 * - 无 IntersectionObserver 支持时:全部直接显示(兜底)。
 * - reduced-motion 由 CSS 媒体查询接管(元素始终可见),本脚本不做额外判断。
 * - 可选 data-reveal-delay="ms" 实现交错出场。
 */
( function () {
	'use strict';

	var els = document.querySelectorAll( '[data-reveal]' );
	if ( ! els.length ) {
		return;
	}

	// 不支持 IO:直接全部可见,避免内容永久隐藏。
	if ( ! ( 'IntersectionObserver' in window ) ) {
		Array.prototype.forEach.call( els, function ( el ) {
			el.classList.add( 'is-revealed' );
		} );
		return;
	}

	var io = new IntersectionObserver(
		function ( entries, obs ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}
				var el = entry.target;
				var delay = parseInt( el.getAttribute( 'data-reveal-delay' ), 10 ) || 0;
				if ( delay > 0 ) {
					window.setTimeout( function () {
						el.classList.add( 'is-revealed' );
					}, delay );
				} else {
					el.classList.add( 'is-revealed' );
				}
				obs.unobserve( el );
			} );
		},
		{
			rootMargin: '0px 0px -8% 0px',
			threshold: 0.08,
		}
	);

	Array.prototype.forEach.call( els, function ( el ) {
		io.observe( el );
	} );
}() );
