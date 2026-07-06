/**
 * OneDong · 资源导航后台 · v1.0.0
 * 1) 资源编辑:图标三模式切换 + 媒体库选图(限 image)
 * 2) 页面设置:取色器 + Banner 模式切换字段行 + 渐变方向预设→角度
 */
( function ( $ ) {
	'use strict';

	if ( ! $ ) {
		return;
	}

	$( function () {
		var cfg = window.onedongResAdmin || {};

		/* —— 资源编辑:图标三模式切换 —— */
		var $modeRadios = $( 'input[name="onedong_resource_icon_mode"]' );
		function syncIconMode() {
			var m = $modeRadios.filter( ':checked' ).val();
			$( '.res-icon-field' ).hide();
			if ( m ) {
				$( '.res-icon-field--' + m ).show();
			}
		}
		if ( $modeRadios.length ) {
			$modeRadios.on( 'change', syncIconMode );
			syncIconMode();
		}

		/* —— 资源编辑:媒体库选图(限 image)—— */
		var $add     = $( '#res-icon-add' );
		var $remove  = $( '#res-icon-remove' );
		var $idInput = $( '#res-icon-id' );
		var $preview = $( '.res-icon-preview' );

		$add.on( 'click', function ( e ) {
			e.preventDefault();
			if ( ! window.wp || ! wp.media ) {
				return;
			}
			var frame = wp.media( {
				title: cfg.title || '选择资源图标',
				multiple: false,
				library: { type: 'image' },
				button: { text: '设为图标' }
			} );
			frame.on( 'select', function () {
				var a = frame.state().get( 'selection' ).first().toJSON();
				if ( ! a || ! a.id ) {
					return;
				}
				$idInput.val( a.id );
				var s = a.sizes || {};
				var url = ( s.thumbnail && s.thumbnail.url ) || ( s.medium && s.medium.url ) || a.url;
				$preview.html( '<img src="' + url + '" alt="">' );
				$remove.show();
			} );
			frame.open();
		} );

		$remove.on( 'click', function () {
			$idInput.val( '' );
			$preview.empty();
			$remove.hide();
		} );

		/* —— 页面设置:取色器 —— */
		if ( $.fn.wpColorPicker ) {
			$( '.res-color-picker' ).wpColorPicker();
		}

		/* —— 页面设置:Banner 模式切换 solid/gradient 字段行 —— */
		var $bannerRadios = $( 'input[name="onedong_resources_settings[banner_mode]"]' );
		function syncBannerMode() {
			var m = $bannerRadios.filter( ':checked' ).val();
			$( '.res-banner-dep' ).closest( 'tr' ).hide();
			if ( m === 'solid' ) {
				$( '.res-banner-dep--solid' ).closest( 'tr' ).show();
			}
			if ( m === 'gradient' ) {
				$( '.res-banner-dep--gradient' ).closest( 'tr' ).show();
			}
		}
		if ( $bannerRadios.length ) {
			$bannerRadios.on( 'change', syncBannerMode );
			syncBannerMode();
		}

		/* —— 页面设置:渐变方向预设 → 写入角度数字框 —— */
		$( '.res-angle-preset' ).on( 'change', function () {
			var v = $( this ).val();
			if ( v === '' ) {
				return;
			}
			$( 'input[name="onedong_resources_settings[banner_gradient_angle]"]' ).val( v );
			$( this ).val( '' ); // 重置占位,便于再次选择
		} );
	} );
}( jQuery ) );
