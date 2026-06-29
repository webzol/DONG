/**
 * OneDong · 朋友圈后台图片管理(WP Media Frame 多选)· v2.5.0
 * 「+ 添加图片」→ WP 媒体库多选(最多 9)→ 缩略图预览 + × 移除。
 */
( function ( $ ) {
	'use strict';

	if ( ! window.wp || ! wp.media || ! $ ) {
		return;
	}

	var cfg = window.onedongMomentAdmin || {};
	var max = cfg.max || 9;
	var $list = $( '#moment-img-list' );
	var $ids = $( '#moment-img-ids' );
	var $count = $( '#moment-img-count' );
	var $add = $( '#moment-img-add' );

	function sync() {
		var ids = [];
		$list.find( '.moment-img-item' ).each( function () {
			ids.push( $( this ).data( 'id' ) );
		} );
		$ids.val( ids.join( ',' ) );
		$count.text( '已选 ' + ids.length + '/' + max );
		$add.prop( 'disabled', ids.length >= max );
	}

	// 缩略图 URL 兜底:thumbnail → medium → full → icon
	function thumbUrl( a ) {
		var s = a.sizes || {};
		return ( s.thumbnail && s.thumbnail.url ) || ( s.medium && s.medium.url ) || ( s.full && s.full.url ) || a.url || a.icon;
	}

	$add.on( 'click', function ( e ) {
		e.preventDefault();
		if ( $add.prop( 'disabled' ) ) {
			return;
		}
		var frame = wp.media( {
			title: cfg.title || '选择图片',
			multiple: true,
			library: { type: 'image' },
			button: { text: '添加到朋友圈' }
		} );
		frame.on( 'select', function () {
			var sel = frame.state().get( 'selection' ).toJSON();
			var cur = $ids.val() ? $ids.val().split( ',' ).map( Number ) : [];
			sel.forEach( function ( a ) {
				if ( cur.length >= max || cur.indexOf( a.id ) !== -1 ) {
					return;
				}
				cur.push( a.id );
				var li = $( '<li class="moment-img-item"></li>' ).data( 'id', a.id );
				li.append( '<img src="' + thumbUrl( a ) + '" alt="">' );
				li.append( '<button type="button" class="moment-img-remove" aria-label="移除">×</button>' );
				$list.append( li );
			} );
			sync();
		} );
		frame.open();
	} );

	// 移除(事件委托)
	$list.on( 'click', '.moment-img-remove', function () {
		$( this ).closest( '.moment-img-item' ).remove();
		sync();
	} );

	sync();
}( jQuery ) );
