<?php
/**
 * 右侧栏(Sidebar · suxing):可配置模块。
 * 由 home.php / archive.php / search.php 经 get_sidebar() 引入。
 * 模块(固定顺序):分类 → 标签云 → 最新文章 → 热门文章 → 归档 → 自定义文本。
 * 各模块由后台「外观 → 自定义 → 右侧栏模块」开关控制;全关时不输出 aside。
 *
 * @package OneDong
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$right_keys = array( 'cats', 'tags', 'recent', 'popular', 'archive', 'text' );
$right_defs = array(
	'cats'    => 1,
	'tags'    => 1,
	'recent'  => 0,
	'popular' => 0,
	'archive' => 0,
	'text'    => 0,
);
$any_right = false;
foreach ( $right_keys as $k ) {
	if ( get_theme_mod( "onedong_right_{$k}", $right_defs[ $k ] ) ) {
		$any_right = true;
		break;
	}
}
if ( ! $any_right ) {
	return; // 全关:不输出空侧栏
}
?>
<aside id="secondary" class="sidebar">

	<?php if ( get_theme_mod( 'onedong_right_cats', 1 ) ) : ?>
		<?php
		$cats = get_categories(
			array(
				'orderby' => 'count',
				'order'   => 'DESC',
				'number'  => 12,
			)
		);
		if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) :
			?>
			<section class="widget widget-cats">
				<h2 class="widget-title"><?php esc_html_e( '分类', 'onedong' ); ?></h2>
				<ul class="widget-cats__list">
					<?php foreach ( $cats as $cat ) : ?>
						<li>
							<a href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
							<span class="count"><?php echo esc_html( number_format_i18n( $cat->count ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( get_theme_mod( 'onedong_right_tags', 1 ) ) : ?>
		<?php
		$tags = get_tags();
		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) :
			?>
			<section class="widget widget-tags">
				<h2 class="widget-title"><?php esc_html_e( '标签', 'onedong' ); ?></h2>
				<div class="widget-tags__cloud">
					<?php
					foreach ( $tags as $tag ) {
						printf(
							'<a href="%1$s" class="tag-link">%2$s</a>',
							esc_url( get_tag_link( $tag ) ),
							esc_html( $tag->name )
						);
					}
					?>
				</div>
			</section>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( get_theme_mod( 'onedong_right_recent', 0 ) ) { onedong_widget_recent_posts(); } ?>
	<?php if ( get_theme_mod( 'onedong_right_popular', 0 ) ) { onedong_widget_popular_posts(); } ?>
	<?php if ( get_theme_mod( 'onedong_right_archive', 0 ) ) { onedong_widget_archive(); } ?>
	<?php if ( get_theme_mod( 'onedong_right_text', 0 ) ) { onedong_widget_text( 'right' ); } ?>

</aside>
