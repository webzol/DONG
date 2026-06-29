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
