/**
 * OneDong · 文章详情页脚本(渐进增强 · 零依赖)
 * --------------------------------------------------------------
 * 1) 阅读进度条:按 <article> 的可滚动进度填充顶部进度条。
 * 2) 代码块复制按钮:给 .entry-content / .comment-content 的 <pre> 注入复制按钮。
 * 3) TOC 当前段高亮(scrollspy):滚动时高亮目录里当前可见标题的链接。
 * - 无 IO / 无 Clipboard API 时优雅降级(reduce + 兜底)。
 * - reduced-motion 由 CSS 接管。
 */
( function () {
	'use strict';

	var doc = document;

	/* ---------- 1) 阅读进度条 ---------- */
	( function () {
		var bar = doc.querySelector( '.reading-progress__bar' );
		var article = doc.querySelector( '.post-single article.post, .post-single article' );
		if ( ! bar || ! article ) {
			return;
		}
		var ticking = false;

		function update() {
			ticking = false;
			var rect = article.getBoundingClientRect();
			var total = rect.height - window.innerHeight;
			var scrolled;
			if ( total <= 0 ) {
				// 文章短于一屏:进视口即满
				scrolled = rect.top < window.innerHeight * 0.5 ? rect.height : 0;
			} else {
				scrolled = Math.min( Math.max( -rect.top, 0 ), total );
			}
			var pct = total > 0 ? ( scrolled / total ) * 100 : ( scrolled ? 100 : 0 );
			bar.style.width = pct + '%';
		}

		function onScroll() {
			if ( ! ticking ) {
				window.requestAnimationFrame( update );
				ticking = true;
			}
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', update );
		update();
	}() );

	/* ---------- 2) 代码块复制按钮 ---------- */
	( function () {
		if ( ! ( 'querySelectorAll' in doc ) ) {
			return;
		}
		var pres = doc.querySelectorAll( '.entry-content pre, .comment-content pre' );
		if ( ! pres.length ) {
			return;
		}

		Array.prototype.forEach.call( pres, function ( pre ) {
			if ( pre.querySelector( '.code-copy-btn' ) ) {
				return;
			}
			var btn = doc.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'code-copy-btn';
			btn.setAttribute( 'aria-label', '复制代码' );
			btn.textContent = '复制';

			btn.addEventListener( 'click', function () {
				var code = pre.querySelector( 'code' );
				var text = code ? code.innerText : pre.innerText;
				var done = function () {
					btn.textContent = '已复制';
					btn.classList.add( 'is-copied' );
					window.setTimeout( function () {
						btn.textContent = '复制';
						btn.classList.remove( 'is-copied' );
					}, 1500 );
				};
				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( text ).then( done, done );
				} else {
					// 兜底:临时 textarea + execCommand(老浏览器 / 非 HTTPS)
					var ta = doc.createElement( 'textarea' );
					ta.value = text;
					ta.style.position = 'fixed';
					ta.style.opacity = '0';
					doc.body.appendChild( ta );
					ta.select();
					try { doc.execCommand( 'copy' ); } catch ( e ) {}
					doc.body.removeChild( ta );
					done();
				}
			} );

			pre.appendChild( btn );
		} );
	}() );

	/* ---------- 3) TOC 当前段高亮(scrollspy)---------- */
	( function () {
		if ( ! ( 'IntersectionObserver' in window ) || ! Array.prototype.find ) {
			return;
		}
		var nav = doc.querySelector( '.toc' );
		if ( ! nav ) {
			return;
		}
		var links = nav.querySelectorAll( '.toc__item a' );
		if ( ! links.length ) {
			return;
		}

		var entries = [];
		Array.prototype.forEach.call( links, function ( link ) {
			var id = link.getAttribute( 'href' );
			if ( ! id || '#' !== id.charAt( 0 ) ) {
				return;
			}
			var el = doc.getElementById( id.slice( 1 ) );
			if ( el ) {
				entries.push( { el: el, link: link } );
			}
		} );
		if ( ! entries.length ) {
			return;
		}

		var current = null;
		var io = new IntersectionObserver(
			function ( records ) {
				records.forEach( function ( r ) {
					if ( r.isIntersecting ) {
						if ( current ) {
							current.link.classList.remove( 'is-active' );
						}
						current = entries.find( function ( e ) { return e.el === r.target; } ) || null;
						if ( current ) {
							current.link.classList.add( 'is-active' );
						}
					}
				} );
			},
			{
				rootMargin: '0px 0px -70% 0px', // 标题进入顶部 30% 区域才算「当前」
				threshold: 0,
			}
		);
		entries.forEach( function ( e ) {
			io.observe( e.el );
		} );
	}() );

}() );
