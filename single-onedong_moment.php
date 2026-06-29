<?php
/**
 * 朋友圈(onedong_moment)单条模板 · v2.5.0
 * 单条详情页,沿用朋友圈卡片样式。
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content moments-page">
	<div class="moments-feed">
		<?php
		while ( have_posts() ) :
			the_post();
			onedong_render_moment();
		endwhile;
		?>
	</div>
</div>
<?php
get_footer();
