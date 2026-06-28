/**
 * OneDong · 文章卡点赞(渐进增强 · 零依赖)
 * --------------------------------------------------------------
 * 点击 .post-card__like → POST /onedong/v1/like { post_id } → 点赞数 +1。
 * localStorage 标记同一浏览器只赞一次(防重复刷;服务端开放,非强制)。
 * settings 由 wp_localize_script( 'onedong-likes', 'onedongLike', ... ) 注入(url + nonce)。
 */
( function () {
	'use strict';

	var settings = window.onedongLike || {};
	var btns = document.querySelectorAll( '.post-card__like' );
	if ( ! btns.length || ! settings.url ) {
		return;
	}

	Array.prototype.forEach.call( btns, function ( btn ) {
		var id = btn.getAttribute( 'data-id' );
		if ( ! id ) {
			return;
		}
		var key = 'onedong-liked-' + id;

		// 已赞(本浏览器):置态
		if ( localStorage.getItem( key ) ) {
			btn.classList.add( 'is-liked' );
			btn.setAttribute( 'aria-pressed', 'true' );
		}

		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation(); // 阻止 stretched-link 整卡跳转
			if ( btn.classList.contains( 'is-liked' ) || btn.classList.contains( 'is-busy' ) ) {
				return;
			}
			btn.classList.add( 'is-busy' );

			fetch( settings.url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': settings.nonce,
				},
				body: JSON.stringify( { post_id: parseInt( id, 10 ) } ),
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					if ( data && data.success ) {
						btn.classList.add( 'is-liked' );
						btn.setAttribute( 'aria-pressed', 'true' );
						try { localStorage.setItem( key, '1' ); } catch ( err ) {}
						var count = btn.querySelector( '.post-card__like-count' );
						if ( count && 'number' === typeof data.likes ) {
							count.textContent = data.likes;
						}
					}
				} )
				.catch( function () {} )
				.finally( function () {
					btn.classList.remove( 'is-busy' );
				} );
		} );
	} );
}() );
