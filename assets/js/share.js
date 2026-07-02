/**
 * OneDong · 文章分享卡片(渐进增强)· v6.0.5
 * --------------------------------------------------------------
 * 点底部「分享」 → 弹出 .post-share 浮层:
 *   - qrcodejs 据二维码容器 data-url 生成二维码(失败回退 qrserver 在线 API);
 *   - 「保存为图片」用 html2canvas 截 #postShareCard → png 下载。
 * 依赖(仅文章详情页条件加载):qrcodejs(window.QRCode)、html2canvas(window.html2canvas)。
 * 卡片 HTML 由 PHP onedong_share_card() 服务端渲染(头像 / 昵称 / 标题 / 简介 / 二维码容器),
 * 故本脚本只管显隐 / 生码 / 存图,不拼数据。
 * settings 由 wp_localize_script( 'onedong-share', 'onedongPostShare', ... ) 注入(saveText / busyText)。
 */
( function () {
	'use strict';

	var doc = document;
	var card = doc.getElementById( 'postShare' );
	if ( ! card ) {
		return;
	}

	var cfg = window.onedongPostShare || {};
	var trigger = doc.querySelector( '[data-share-trigger]' );
	var qrEl = card.querySelector( '.post-share__qr' );

	/* —— 生成二维码(仅首次)—— */
	function makeQr() {
		if ( ! qrEl || qrEl.getAttribute( 'data-done' ) ) {
			return;
		}
		var url = qrEl.getAttribute( 'data-url' ) || location.href;
		qrEl.innerHTML = '';
		if ( window.QRCode ) {
			try {
				new QRCode( qrEl, { text: url, width: 96, height: 96, correctLevel: QRCode.CorrectLevel.M } );
			} catch ( e ) {
				qrEl.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=96x96&data=' + encodeURIComponent( url ) + '" alt="二维码">';
			}
		} else {
			qrEl.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=96x96&data=' + encodeURIComponent( url ) + '" alt="二维码">';
		}
		qrEl.setAttribute( 'data-done', '1' );
	}

	function open() {
		makeQr();
		card.classList.add( 'is-open' );
		card.setAttribute( 'aria-hidden', 'false' );
		doc.body.style.overflow = 'hidden';
	}

	function close() {
		card.classList.remove( 'is-open' );
		card.setAttribute( 'aria-hidden', 'true' );
		doc.body.style.overflow = '';
	}

	/* —— 保存为图片(html2canvas 截卡片)—— */
	function save() {
		if ( ! window.html2canvas ) {
			return;
		}
		var node = card.querySelector( '#postShareCard' );
		var btn = card.querySelector( '[data-share-save]' );
		if ( ! node || ! btn ) {
			return;
		}
		btn.disabled = true;
		btn.textContent = cfg.busyText || '生成中…';
		html2canvas( node, { useCORS: true, allowTaint: false, scale: 2, backgroundColor: '#ffffff' } )
			.then( function ( canvas ) {
				var titleEl = card.querySelector( '.post-share__title' );
				var name = ( titleEl ? titleEl.textContent.trim() : 'article' ) || 'article';
				var a = doc.createElement( 'a' );
				a.href = canvas.toDataURL( 'image/png' );
				a.download = name + '.png';
				a.click();
			} )
			.catch( function () {} )
			.finally( function () {
				btn.disabled = false;
				btn.textContent = cfg.saveText || '保存为图片';
			} );
	}

	if ( trigger ) {
		trigger.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			open();
		} );
	}

	Array.prototype.forEach.call( card.querySelectorAll( '[data-share-close]' ), function ( el ) {
		el.addEventListener( 'click', close );
	} );

	var saveBtn = card.querySelector( '[data-share-save]' );
	if ( saveBtn ) {
		saveBtn.addEventListener( 'click', save );
	}

	doc.addEventListener( 'keydown', function ( e ) {
		if ( 27 === e.keyCode && card.classList.contains( 'is-open' ) ) {
			close();
		}
	} );
}() );
