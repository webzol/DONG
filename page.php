<?php
/**
 * 独立页面模板
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content post-single">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'page' ); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="post-thumbnail">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>

			<div class="entry-content">
				<?php
				the_content();

				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( '页面:', 'onedong' ),
						'after'  => '</div>',
					)
				);
				?>
			</div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
