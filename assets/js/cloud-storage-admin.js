/**
 * OneDong 云存储 · 后台设置页脚本
 *
 * 1) provider 标签页切换;2) 连接测试(收集当前面板字段值 → AJAX → 显示结果)。
 * 依赖 jQuery 与 wp_localize_script 注入的 OneDongCloud。
 */
( function ( $ ) {
	'use strict';

	var C = window.OneDongCloud || {};

	function showTab( prov ) {
		$( '.onedong-cloud-tabs .nav-tab' ).removeClass( 'nav-tab-active' );
		$( '.onedong-cloud-tabs .nav-tab[data-tab="' + prov + '"]' ).addClass( 'nav-tab-active' );
		$( '.onedong-cloud-panel' ).attr( 'hidden', 'hidden' );
		$( '.onedong-cloud-panel[data-panel="' + prov + '"]' ).removeAttr( 'hidden' );
	}

	$( function () {
		if ( ! $( '.onedong-cloud-tabs' ).length ) {
			return;
		}

		// 初始标签:当前启用 provider,回退第一个。
		var init = C.active || $( '.onedong-cloud-tabs .nav-tab' ).first().data( 'tab' );
		showTab( init );

		$( '.onedong-cloud-tabs' ).on( 'click', '.nav-tab', function ( e ) {
			e.preventDefault();
			showTab( $( this ).data( 'tab' ) );
		} );

		$( '.onedong-cloud-test' ).on( 'click', function () {
			var $btn    = $( this ),
				prov    = $btn.data( 'provider' ),
				$result = $( '.onedong-cloud-test-result[data-for="' + prov + '"]' ),
				$panel  = $( '.onedong-cloud-panel[data-panel="' + prov + '"]' ),
				cfg     = {};

			$panel.find( '.onedong-cloud-input' ).each( function () {
				var $i = $( this ),
					f  = $i.data( 'field' );
				if ( ! f ) {
					return;
				}
				cfg[ f ] = ( 'checkbox' === $i.attr( 'type' ) ) ? ( $i.prop( 'checked' ) ? '1' : '0' ) : $i.val();
			} );

			$btn.prop( 'disabled', true );
			$result.css( 'color', '' ).text( C.testing || 'Testing…' );

			$.post( C.ajax, {
				action: 'onedong_cloud_test',
				nonce: C.nonce,
				provider: prov,
				cfg: cfg
			} ).done( function ( res ) {
				if ( res && res.success ) {
					var html = '✓ ' + ( ( res.data && res.data.message ) || 'OK' );
					if ( res.data && res.data.url ) {
						html += ' <a href="' + res.data.url + '" target="_blank" rel="noopener">' + res.data.url + '</a>';
					}
					$result.css( 'color', '#227a2f' ).html( html );
				} else {
					$result.css( 'color', '#d63638' ).text( '✗ ' + ( ( res && res.data && res.data.message ) || 'Error' ) );
				}
			} ).fail( function () {
				$result.css( 'color', '#d63638' ).text( '✗ ' + ( '请求失败（网络 / 服务器错误）' ) );
			} ).always( function () {
				$btn.prop( 'disabled', false );
			} );
		} );

		// 统计刷新:点 → AJAX 重算 → 替换统计区块 HTML。事件委托到 wrap(replaceWith 后新按钮仍生效)。
		$( '.onedong-cloud-wrap' ).on( 'click', '.onedong-cloud-stats-refresh', function () {
			var $btn  = $( this ),
				$stat = $( '#onedong-cloud-stats' );
			if ( ! $stat.length || $btn.hasClass( 'is-loading' ) ) {
				return;
			}
			$btn.addClass( 'is-loading' ).prop( 'disabled', true );
			$.post( C.ajax, {
				action: 'onedong_cloud_stats_refresh',
				nonce:  C.statsNonce
			} ).done( function ( res ) {
				if ( res && res.success && res.data && res.data.html ) {
					$stat.replaceWith( res.data.html );
				} else {
					$btn.removeClass( 'is-loading' ).prop( 'disabled', false );
				}
			} ).fail( function () {
				$btn.removeClass( 'is-loading' ).prop( 'disabled', false );
			} );
		} );
	} );
}( jQuery ) );
