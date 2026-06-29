<?php
/**
 * 朋友圈(onedong_moment)归档模板 · v2.5.0
 * 访问 /moments/ 展示朋友圈流(微信朋友圈风格:头像+昵称+文字+九宫格+定位+时间)。
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content moments-page">
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
<?php
get_footer();
