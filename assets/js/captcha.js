/**
 * OneDong · 评论验证码换图(渐进增强 · 零依赖)
 * --------------------------------------------------------------
 * 点击 [data-captcha-refresh] 给同行的验证码图片追加随机 query 换一张。
 * - 无 JS 时图片仍可见、仍可提交(刷新降级为整页重载)。
 * - admin-post.php 端点已发 no-cache 头,但给 src 加随机 v 串可绕过任何中间 CDN 缓存。
 */
( function () {
	'use strict';

	var btns = document.querySelectorAll( '[data-captcha-refresh]' );
	if ( ! btns.length ) {
		return;
	}

	Array.prototype.forEach.call( btns, function ( btn ) {
		btn.addEventListener( 'click', function () {
			var wrap = btn.closest( '.comment-form-captcha' );
			if ( ! wrap ) {
				return;
			}
			var img = wrap.querySelector( '.comment-form-captcha__img' );
			if ( ! img ) {
				return;
			}
			var src = img.getAttribute( 'src' );
			// 去掉旧的 v=...,再加新的随机串。
			src = src.replace( /(&|\?)v=[^&]*/, '' );
			src += ( src.indexOf( '?' ) === -1 ? '?' : '&' ) + 'v=' + Date.now();
			img.setAttribute( 'src', src );
		} );
	} );
}() );
