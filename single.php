<?php
/**
 * 单篇文章模板
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
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'post' ); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<?php onedong_entry_meta(); ?>
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

			<?php
			$tags = get_the_tag_list( '<div class="entry-tags" style="margin-top:1.25rem;">' . esc_html__( '标签:', 'onedong' ) . ' ', ', ', '</div>' );
			if ( $tags ) {
				echo wp_kses_post( $tags ); // get_the_tag_list 输出已转义,二次校验
			}
			?>
		</article>

		<?php onedong_post_nav(); ?>

		<?php
		// 评论(开启评论或有评论时显示)
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	<?php endwhile; ?>
</div>
<?php
get_footer();
