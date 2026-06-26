<?php
/**
 * 博客首页模板(最新文章列表)
 *
 * 当「设置 → 阅读 → 首页显示 = 你的最新文章」时,本模板渲染文章列表。
 * 双栏布局:左文章流 + 右侧栏(对齐 Fuwari 演示)。
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content site-content--two-col">
	<div class="content-main">
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

	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
