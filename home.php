<?php
/**
 * 博客首页模板(最新文章列表)
 *
 * 当「设置 → 阅读 → 首页显示 = 你的最新文章」时,本模板渲染文章列表。
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content">
	<header class="page-header">
		<h1 class="page-title">
			<?php
			if ( is_front_page() && is_home() ) {
				esc_html_e( '最新文章', 'onedong' );
			} else {
				single_post_title();
			}
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="post-list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content' );
			endwhile;
			?>
		</div>

		<?php get_template_part( 'template-parts/pagination' ); ?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
