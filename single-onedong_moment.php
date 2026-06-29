<?php
/**
 * 朋友圈(onedong_moment)单条模板 · v2.5.2
 * 三栏布局(与首页/归档一致):左作者卡 + 中朋友圈卡片 + 右侧栏。
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content site-content--three-col">
	<?php get_sidebar( 'left' ); ?>

	<div class="content-main moments-page">
		<div class="moments-feed">
			<?php
			while ( have_posts() ) :
				the_post();
				onedong_render_moment();
			endwhile;
			?>
		</div>
	</div>

	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
