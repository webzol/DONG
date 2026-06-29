<?php
/**
 * 朋友圈(onedong_moment)归档模板 · v2.5.2
 * 三栏布局(与首页一致):左作者卡 + 中朋友圈流 + 右侧栏。访问 /moments/。
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content site-content--three-col">
	<?php get_sidebar( 'left' ); ?>

	<div class="content-main moments-page">
		<div class="moments-feed">
			<?php if ( have_posts() ) : ?>
				<?php
				while ( have_posts() ) :
					the_post();
					onedong_render_moment();
				endwhile;
				?>
				<?php get_template_part( 'template-parts/pagination' ); ?>
			<?php else : ?>
				<p class="moments-empty"><?php esc_html_e( '还没有朋友圈动态。', 'onedong' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
