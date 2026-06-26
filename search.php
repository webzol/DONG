<?php
/**
 * 搜索结果模板
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content site-content--two-col">
	<div class="content-main">
		<header class="page-header">
			<h1 class="page-title">
				<?php
				printf(
					/* translators: %s: 搜索词 */
					esc_html__( '搜索结果:%s', 'onedong' ),
					'<span>' . esc_html( get_search_query() ) . '</span>'
				);
				?>
			</h1>
			<div class="page-description"><?php get_search_form(); ?></div>
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

	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
