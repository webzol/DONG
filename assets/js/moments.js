/**
 * OneDong · 朋友圈:图片 lightbox + 「••」操作(赞 / 分享卡片)· v2.5.5
 *
 * 分享卡片:点分享 → 弹海报(第一张图 / 无图用默认缩略图 + 作者头像 + 文字 + 二维码),可保存为图片。
 * 依赖(仅朋友圈页条件加载):qrcodejs(window.QRCode)、html2canvas(window.html2canvas)。
 */
( function () {
	'use strict';

	var imgs = document.querySelectorAll( '.moment__img' );
	if ( imgs.length ) {
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
			if ( ! list.length ) { return; }
			cur = list;
			idx = i;
			bImg.src = cur[ idx ];
			box.classList.add( 'is-open' );
		}
		function close() { box.classList.remove( 'is-open' ); }
		function step( d ) {
			if ( ! cur.length ) { return; }
			idx = ( idx + d + cur.length ) % cur.length;
			bImg.src = cur[ idx ];
		}
		Array.prototype.forEach.call( imgs, function ( img ) {
			img.addEventListener( 'click', function () {
				var group = img.closest( '.moment__imgs' );
				var items = group ? group.querySelectorAll( '.moment__img' ) : [ img ];
				var list = [];
				Array.prototype.forEach.call( items, function ( el ) {
					var u = el.getAttribute( 'data-full' ) || el.src;
					if ( u ) { list.push( u ); }
				} );
				show( list, Array.prototype.indexOf.call( items, img ) );
			} );
		} );
		box.querySelector( '.moment-lightbox__close' ).addEventListener( 'click', close );
		box.querySelector( '.moment-lightbox__prev' ).addEventListener( 'click', function ( e ) { e.stopPropagation(); step( -1 ); } );
		box.querySelector( '.moment-lightbox__next' ).addEventListener( 'click', function ( e ) { e.stopPropagation(); step( 1 ); } );
		box.addEventListener( 'click', function ( e ) { if ( e.target === box ) { close(); } } );
		document.addEventListener( 'keydown', function ( e ) {
			if ( ! box.classList.contains( 'is-open' ) ) { return; }
			if ( 27 === e.keyCode ) { close(); }
			else if ( 37 === e.keyCode ) { step( -1 ); }
			else if ( 39 === e.keyCode ) { step( 1 ); }
		} );
	}
}() );

/**
 * 「••」操作按钮 + 赞 / 分享卡片 · v2.5.5
 */
( function () {
	'use strict';

	var toggles = document.querySelectorAll( '.moment__toggle' );
	if ( ! toggles.length ) {
		return;
	}
	var cfg = window.onedongMomentShare || {};
	var likeSettings = window.onedongLike || {};

	// —— 分享卡片浮层(惰性创建)——
	var cardEl = null;
	var qrEl = null;

	function ensureCard() {
		if ( cardEl ) { return cardEl; }
		cardEl = document.createElement( 'div' );
		cardEl.className = 'moment-share';
		cardEl.innerHTML =
			'<div class="moment-share__mask"></div>' +
			'<div class="moment-share__inner">' +
				'<div class="moment-share__card" id="momentShareCard">' +
					'<div class="moment-share__img-wrap"><img class="moment-share__img" alt="" crossorigin="anonymous"></div>' +
					'<div class="moment-share__body">' +
						'<div class="moment-share__author">' +
							'<img class="moment-share__avatar" alt="" crossorigin="anonymous">' +
							'<span class="moment-share__name"></span>' +
						'</div>' +
						'<div class="moment-share__text"></div>' +
					'</div>' +
					'<div class="moment-share__foot">' +
						'<div class="moment-share__qr" id="momentShareQr"></div>' +
						'<div class="moment-share__brand"><strong></strong><span>扫码查看全文</span></div>' +
					'</div>' +
				'</div>' +
				'<div class="moment-share__bar">' +
					'<button type="button" class="moment-share__save">保存图片</button>' +
					'<button type="button" class="moment-share__close">关闭</button>' +
				'</div>' +
			'</div>';
		document.body.appendChild( cardEl );
		qrEl = cardEl.querySelector( '#momentShareQr' );
		cardEl.querySelector( '.moment-share__mask' ).addEventListener( 'click', closeCard );
		cardEl.querySelector( '.moment-share__close' ).addEventListener( 'click', closeCard );
		cardEl.querySelector( '.moment-share__save' ).addEventListener( 'click', saveCard );
		return cardEl;
	}

	function openCard( data ) {
		ensureCard();
		cardEl.querySelector( '.moment-share__img' ).src = data.img;
		cardEl.querySelector( '.moment-share__avatar' ).src = data.avatar;
		cardEl.querySelector( '.moment-share__name' ).textContent = data.name;
		cardEl.querySelector( '.moment-share__text' ).textContent = data.text;
		cardEl.querySelector( '.moment-share__brand strong' ).textContent = cfg.siteName || 'OneDong';
		// 二维码:qrcodejs 优先,失败回退在线 API
		qrEl.innerHTML = '';
		if ( window.QRCode ) {
			try {
				new QRCode( qrEl, { text: data.url, width: 96, height: 96, correctLevel: QRCode.CorrectLevel.M } );
			} catch ( e ) {
				qrEl.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=96x96&data=' + encodeURIComponent( data.url ) + '" alt="二维码">';
			}
		} else {
			qrEl.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=96x96&data=' + encodeURIComponent( data.url ) + '" alt="二维码">';
		}
		cardEl.classList.add( 'is-open' );
		document.body.style.overflow = 'hidden';
	}

	function closeCard() {
		if ( cardEl ) { cardEl.classList.remove( 'is-open' ); }
		document.body.style.overflow = '';
	}

	function saveCard() {
		if ( ! window.html2canvas || ! cardEl ) { return; }
		var node = cardEl.querySelector( '#momentShareCard' );
		var btn = cardEl.querySelector( '.moment-share__save' );
		btn.disabled = true;
		btn.textContent = '生成中…';
		html2canvas( node, { useCORS: true, allowTaint: false, scale: 2, backgroundColor: '#ffffff' } )
			.then( function ( canvas ) {
				var a = document.createElement( 'a' );
				a.href = canvas.toDataURL( 'image/png' );
				a.download = 'moment.png';
				a.click();
			} )
			.catch( function () {} )
			.finally( function () { btn.disabled = false; btn.textContent = '保存图片'; } );
	}

	function closeAllPops() {
		Array.prototype.forEach.call( document.querySelectorAll( '.moment__pop.is-open' ), function ( p ) {
			p.classList.remove( 'is-open' );
		} );
		Array.prototype.forEach.call( document.querySelectorAll( '.moment__toggle' ), function ( t ) {
			t.setAttribute( 'aria-expanded', 'false' );
		} );
	}

	Array.prototype.forEach.call( toggles, function ( toggle ) {
		var wrap = toggle.closest( '.moment__actions' );
		if ( ! wrap ) { return; }
		var pop = wrap.querySelector( '.moment__pop' );
		var likeBtn = wrap.querySelector( '.moment__pop-btn--like' );
		var shareBtn = wrap.querySelector( '.moment__pop-btn--share' );

		toggle.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			var willOpen = pop && ! pop.classList.contains( 'is-open' );
			closeAllPops();
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
				if ( likeBtn.classList.contains( 'is-liked' ) || likeBtn.classList.contains( 'is-busy' ) || ! likeSettings.url ) {
					return;
				}
				likeBtn.classList.add( 'is-busy' );
				fetch( likeSettings.url, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': likeSettings.nonce },
					body: JSON.stringify( { post_id: parseInt( id, 10 ) } )
				} ).then( function ( r ) { return r.json(); } ).then( function ( data ) {
					if ( data && data.success ) {
						likeBtn.classList.add( 'is-liked' );
						try { localStorage.setItem( key, '1' ); } catch ( err ) {}
					}
				} ).catch( function () {} ).finally( function () {
					likeBtn.classList.remove( 'is-busy' );
					closeAllPops();
				} );
			} );
		}

		if ( shareBtn ) {
			shareBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				var m = shareBtn.closest( '.moment' );
				var av = m && m.querySelector( '.moment__avatar img' );
				var txt = m && m.querySelector( '.moment__content' );
				var first = m && m.querySelector( '.moment__img' );
				var nameEl = m && m.querySelector( '.moment__author' );
				openCard( {
					img:    first ? ( first.getAttribute( 'data-full' ) || first.src ) : ( cfg.defaultThumb || '' ),
					avatar: av ? av.src : '',
					name:   nameEl ? nameEl.textContent.trim() : '',
					text:   txt ? txt.textContent.trim() : '',
					url:    shareBtn.getAttribute( 'data-url' ) || location.href
				} );
				closeAllPops();
			} );
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.moment__actions' ) && ! ( cardEl && cardEl.contains( e.target ) ) ) {
			closeAllPops();
		}
	} );
}() );
