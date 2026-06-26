<?php
/**
 * 404 模板
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content">
	<section class="content-none">
		<h1 class="page-title"><?php esc_html_e( '页面找不到了 (404)', 'onedong' ); ?></h1>
		<p><?php esc_html_e( '你访问的页面不存在,可能已被移动或删除。试试搜索?', 'onedong' ); ?></p>
		<?php get_search_form(); ?>
		<p style="margin-top:1.25rem;">
			<a class="read-more" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '← 返回首页', 'onedong' ); ?></a>
		</p>
	</section>
</div>
<?php
get_footer();
