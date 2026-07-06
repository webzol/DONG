/*!
 * OneDong · 资源导航前端筛选 v1.0.0
 * 纯 DOM 过滤:首屏已渲染全部启用资源,切换分类 0 请求。
 * 无 jQuery,与 reveal.js 风格一致。
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var filters = document.querySelector( '[data-res-filters]' );
		var grid    = document.querySelector( '[data-res-grid]' );
		if ( ! filters || ! grid ) {
			return;
		}

		var cards           = Array.prototype.slice.call( grid.querySelectorAll( '.resource-card' ) );
		var emptyDefault    = grid.querySelector( '[data-res-empty]' );
		var emptyFiltered   = document.querySelector( '[data-res-empty-filtered]' );
		var buttons         = Array.prototype.slice.call( filters.querySelectorAll( '.resource-filter' ) );

		buttons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var filter = btn.getAttribute( 'data-filter' );

				buttons.forEach( function ( b ) { b.classList.remove( 'is-active' ); } );
				btn.classList.add( 'is-active' );

				var visible = 0;
				cards.forEach( function ( card ) {
					var cat = card.getAttribute( 'data-cat' );
					var show = ( filter === 'all' ) || ( cat === filter );
					card.hidden = ! show;
					if ( show ) { visible++; }
				} );

				// 空态:全量无资源显示默认空态;筛选无结果显示筛选空态
				if ( emptyDefault ) {
					emptyDefault.hidden = cards.length > 0;
				}
				if ( emptyFiltered ) {
					emptyFiltered.hidden = ( visible > 0 ) || ( cards.length === 0 );
				}
			} );
		} );

		// 初始:若无任何卡片,显示默认空态
		if ( emptyDefault && cards.length === 0 ) {
			emptyDefault.hidden = false;
		}
	} );
}() );
