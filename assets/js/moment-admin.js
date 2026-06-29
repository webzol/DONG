/**
 * OneDong · 朋友圈后台图片管理 · v2.5.11
 * 添加图片(WP Media 多选)+ 移除 + 拖拽排序 + 实况视频配对(每图可选一段视频)。
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
	var $live = $( '#moment-live' );
	var $count = $( '#moment-img-count' );
	var $add = $( '#moment-img-add' );
	var listEl = $list[0];

	// 同步两个 hidden:图片顺序 IDs + 实况配对(img_id => video_id)
	function sync() {
		var ids = [];
		var live = {};
		$list.find( '.moment-img-item' ).each( function () {
			var id = $( this ).data( 'id' );
			ids.push( id );
			var vid = $( this ).attr( 'data-video' );
			if ( vid ) {
				live[ id ] = parseInt( vid, 10 );
			}
		} );
		$ids.val( ids.join( ',' ) );
		$live.val( JSON.stringify( live ) );
		$count.text( '已选 ' + ids.length + '/' + max );
		$add.prop( 'disabled', ids.length >= max );
	}

	function thumbUrl( a ) {
		var s = a.sizes || {};
		return ( s.thumbnail && s.thumbnail.url ) || ( s.medium && s.medium.url ) || ( s.full && s.full.url ) || a.url || a.icon;
	}

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
				li.append( '<button type="button" class="moment-img-live" data-video="" title="实况视频">＋实况</button>' );
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

	// 实况视频配对:点「实况」按钮 → 媒体库选一段视频
	$list.on( 'click', '.moment-img-live', function ( e ) {
		e.preventDefault();
		e.stopPropagation();
		var $li = $( this ).closest( '.moment-img-item' );
		var $btn = $( this );
		var frame = wp.media( {
			title: '选择实况视频',
			library: { type: 'video' },
			button: { text: '设为实况' }
		} );
		frame.on( 'select', function () {
			var a = frame.state().get( 'selection' ).first().toJSON();
			if ( a && a.id ) {
				$li.attr( 'data-video', a.id );
				$btn.attr( 'data-video', a.id ).text( '✓实况' );
				sync();
			}
		} );
		frame.open();
	} );

	// —— 拖拽排序(HTML5 DnD)——
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
