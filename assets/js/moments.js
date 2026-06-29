/**
 * OneDong · 朋友圈图片 lightbox(渐进增强 · 零依赖)· v2.5.0
 * 点击 .moment__img → 全屏大图,同组(同一 .moment__imgs)左右切换,ESC 关闭。
 */
( function () {
	'use strict';

	var imgs = document.querySelectorAll( '.moment__img' );
	if ( ! imgs.length ) {
		return;
	}

	// 建灯箱 DOM
	var box = document.createElement( 'div' );
	box.className = 'moment-lightbox';
	box.innerHTML =
		'<button class="moment-lightbox__close" aria-label="关闭">×</button>' +
		'<button class="moment-lightbox__nav moment-lightbox__prev" aria-label="上一张">‹</button>' +
		'<img class="moment-lightbox__img" alt="">' +
		'<button class="moment-lightbox__nav moment-lightbox__next" aria-label="下一张">›</button>';
	document.body.appendChild( box );

	var bImg = box.querySelector( '.moment-lightbox__img' );
	var cur = [];
	var idx = 0;

	function show( list, i ) {
		if ( ! list.length ) {
			return;
		}
		cur = list;
		idx = i;
		bImg.src = cur[ idx ];
		box.classList.add( 'is-open' );
	}
	function close() {
		box.classList.remove( 'is-open' );
	}
	function step( d ) {
		if ( ! cur.length ) {
			return;
		}
		idx = ( idx + d + cur.length ) % cur.length;
		bImg.src = cur[ idx ];
	}

	// 同一 .moment__imgs 内的图组成一组浏览序列
	Array.prototype.forEach.call( imgs, function ( img ) {
		img.addEventListener( 'click', function () {
			var group = img.closest( '.moment__imgs' );
			var items = group ? group.querySelectorAll( '.moment__img' ) : [ img ];
			var list = [];
			Array.prototype.forEach.call( items, function ( el ) {
				var u = el.getAttribute( 'data-full' ) || el.src;
				if ( u ) {
					list.push( u );
				}
			} );
			show( list, Array.prototype.indexOf.call( items, img ) );
		} );
	} );

	box.querySelector( '.moment-lightbox__close' ).addEventListener( 'click', close );
	box.querySelector( '.moment-lightbox__prev' ).addEventListener( 'click', function ( e ) { e.stopPropagation(); step( -1 ); } );
	box.querySelector( '.moment-lightbox__next' ).addEventListener( 'click', function ( e ) { e.stopPropagation(); step( 1 ); } );
	box.addEventListener( 'click', function ( e ) { if ( e.target === box ) { close(); } } );
	document.addEventListener( 'keydown', function ( e ) {
		if ( ! box.classList.contains( 'is-open' ) ) {
			return;
		}
		if ( 27 === e.keyCode ) { close(); }
		else if ( 37 === e.keyCode ) { step( -1 ); }
		else if ( 39 === e.keyCode ) { step( 1 ); }
	} );
}() );

/**
 * 「••」操作按钮 + 赞 / 分享(纯图标)· v2.5.1
 * 赞:POST onedongLike.url(REST /onedong/v1/like)+localStorage 防重复;分享:navigator.share,降级复制链接。
 */
( function () {
	'use strict';

	var toggles = document.querySelectorAll( '.moment__toggle' );
	if ( ! toggles.length ) {
		return;
	}

	function closeAll() {
		Array.prototype.forEach.call( document.querySelectorAll( '.moment__pop.is-open' ), function ( p ) {
			p.classList.remove( 'is-open' );
		} );
		Array.prototype.forEach.call( document.querySelectorAll( '.moment__toggle' ), function ( t ) {
			t.setAttribute( 'aria-expanded', 'false' );
		} );
	}

	Array.prototype.forEach.call( toggles, function ( toggle ) {
		var wrap = toggle.closest( '.moment__actions' );
		if ( ! wrap ) {
			return;
		}
		var pop = wrap.querySelector( '.moment__pop' );
		var likeBtn = wrap.querySelector( '.moment__pop-btn--like' );
		var shareBtn = wrap.querySelector( '.moment__pop-btn--share' );
		// 优先取全站 onedongLike,回退读容器 data-* 属性
		var settings = window.onedongLike || {
			url: wrap.getAttribute( 'data-like-url' ) || '',
			nonce: wrap.getAttribute( 'data-nonce' ) || ''
		};

		toggle.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			var willOpen = pop && ! pop.classList.contains( 'is-open' );
			closeAll();
			if ( pop && willOpen ) {
				pop.classList.add( 'is-open' );
				toggle.setAttribute( 'aria-expanded', 'true' );
			}
		} );

		if ( likeBtn ) {
			var id = likeBtn.getAttribute( 'data-id' );
			var key = 'onedong-liked-' + id;
			if ( localStorage.getItem( key ) ) {
				likeBtn.classList.add( 'is-liked' );
			}
			likeBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				if ( likeBtn.classList.contains( 'is-liked' ) || likeBtn.classList.contains( 'is-busy' ) || ! settings.url ) {
					return;
				}
				likeBtn.classList.add( 'is-busy' );
				fetch( settings.url, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': settings.nonce },
					body: JSON.stringify( { post_id: parseInt( id, 10 ) } )
				} ).then( function ( r ) { return r.json(); } ).then( function ( data ) {
					if ( data && data.success ) {
						likeBtn.classList.add( 'is-liked' );
						try { localStorage.setItem( key, '1' ); } catch ( err ) {}
					}
				} ).catch( function () {} ).finally( function () {
					likeBtn.classList.remove( 'is-busy' );
					closeAll();
				} );
			} );
		}

		if ( shareBtn ) {
			shareBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				var url = shareBtn.getAttribute( 'data-url' ) || location.href;
				var title = shareBtn.getAttribute( 'data-title' ) || document.title;
				if ( navigator.share ) {
					navigator.share( { title: title, url: url } ).catch( function () {} );
				} else {
					var ta = document.createElement( 'textarea' );
					ta.value = url;
					document.body.appendChild( ta );
					ta.select();
					try { document.execCommand( 'copy' ); } catch ( err ) {}
					document.body.removeChild( ta );
				}
				closeAll();
			} );
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.moment__actions' ) ) {
			closeAll();
		}
	} );
}() );
