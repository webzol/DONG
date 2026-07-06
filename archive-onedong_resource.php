<?php
/**
 * 资源导航(onedong_resource)归档模板 · v1.0.0
 * 全宽:全屏通栏 Banner + 分类筛选 + 资源卡片网格。访问 /resources/。
 * 数据由 inc/resources.php 的 pre_get_posts 排序/过滤,前端 JS 无刷新筛选。
 *
 * @package OneDong
 */

get_header();
?>

<div class="resources-page"<?php echo onedong_resource_card_style_attr(); // 卡片圆角(默认空 = 跟随网站) ?>>
	<?php onedong_resource_banner(); ?>

	<div class="resources-main">
		<?php onedong_resource_filter_bar(); ?>

		<div class="resource-grid" data-res-grid>
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<?php onedong_render_resource_card(); ?>
				<?php endwhile; ?>
			<?php else : ?>
				<p class="resource-empty" data-res-empty><?php esc_html_e( '暂无资源。', 'onedong' ); ?></p>
			<?php endif; ?>
		</div>

		<p class="resource-empty resource-empty--filtered" data-res-empty-filtered hidden><?php esc_html_e( '该分类下暂无资源。', 'onedong' ); ?></p>
	</div>
</div>

<?php
get_footer();
