/**
 * OneDong · 朋友圈后台图片管理 · v2.5.6
 * 「+ 添加图片」(WP Media Frame 多选,最多 9)+ × 移除 + 拖拽排序(HTML5 DnD)。
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
	var listEl = $list[0];

	// 同步 hidden input(逗号分隔 ID,即展示顺序)+ 计数 + 添加按钮禁用态
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

	// 让所有 item 可拖拽(新添加 / 初始都要)
	function makeDraggable() {
		$list.find( '.moment-img-item' ).attr( 'draggable', true );
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
				var li = $( '<li class="moment-img-item" draggable="true"></li>' ).data( 'id', a.id );
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

	// —— 拖拽排序(HTML5 Drag & Drop,事件委托)——
	var dragSrc = null;
	listEl.addEventListener( 'dragstart', function ( e ) {
		var item = e.target.closest( '.moment-img-item' );
		if ( ! item ) { return; }
		dragSrc = item;
		item.classList.add( 'is-dragging' );
		e.dataTransfer.effectAllowed = 'move';
		try { e.dataTransfer.setData( 'text/plain', '' ); } catch ( err ) {}
	} );
	listEl.addEventListener( 'dragover', function ( e ) {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		if ( ! dragSrc ) { return; }
		var item = e.target.closest( '.moment-img-item' );
		if ( ! item || item === dragSrc ) { return; }
		// 按鼠标在目标项的上/下半决定插在其前/后
		var rect = item.getBoundingClientRect();
		var after = ( e.clientY - rect.top ) > rect.height / 2;
		if ( after ) {
			item.parentNode.insertBefore( dragSrc, item.nextSibling );
		} else {
			item.parentNode.insertBefore( dragSrc, item );
		}
	} );
	listEl.addEventListener( 'dragend', function () {
		if ( dragSrc ) {
			dragSrc.classList.remove( 'is-dragging' );
			dragSrc = null;
		}
		sync();
	} );

	makeDraggable();
	sync();
}( jQuery ) );
