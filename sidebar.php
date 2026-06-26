<?php
/**
 * 右侧栏(Sidebar · suxing)
 * 分类列表(带文章数)+ 标签云(药丸)。作者卡已移至 sidebar-left.php。
 * 由 home.php / archive.php / search.php 经 get_sidebar() 引入。
 *
 * @package OneDong
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<aside id="secondary" class="sidebar">
	<!-- 分类(带计数,按文章数倒序) -->
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

	<!-- 标签云(药丸) -->
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
</aside>
