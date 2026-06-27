<?php
/**
 * 右侧栏(Sidebar · suxing):可配置模块,顺序可调。
 * 由 home.php / archive.php / search.php 经 get_sidebar() 引入。
 * 模块顺序由「外观 → 自定义 → 右侧栏模块 → 模块显示顺序」控制(逗号分隔 key);
 * 各模块开关控制是否输出;全关时不输出 aside。
 *
 * @package OneDong
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$valid = array( 'cats', 'tags', 'recent', 'popular', 'archive', 'text' );
$defs  = array(
	'cats'    => 1,
	'tags'    => 1,
	'recent'  => 0,
	'popular' => 0,
	'archive' => 0,
	'text'    => 0,
);

// 任一模块开启才输出 aside
$any_right = false;
foreach ( $valid as $k ) {
	if ( get_theme_mod( "onedong_right_{$k}", $defs[ $k ] ) ) {
		$any_right = true;
		break;
	}
}
if ( ! $any_right ) {
	return;
}

// 解析顺序:用户 order 优先,未列出的按默认顺序兜底
$order_str = get_theme_mod( 'onedong_right_order', 'cats,tags,recent,popular,archive,text' );
$ordered   = array_filter( array_map( 'trim', explode( ',', $order_str ) ) );
$sequence  = array();
foreach ( $ordered as $k ) {
	if ( in_array( $k, $valid, true ) && ! in_array( $k, $sequence, true ) ) {
		$sequence[] = $k;
	}
}
foreach ( $valid as $k ) {
	if ( ! in_array( $k, $sequence, true ) ) {
		$sequence[] = $k;
	}
}
?>
<aside id="secondary" class="sidebar">
	<?php foreach ( $sequence as $k ) { onedong_render_right_module( $k ); } ?>
</aside>
