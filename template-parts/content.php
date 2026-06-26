<?php
/**
 * 文章列表项卡片(Template Part)
 * 供 home.php / archive.php / search.php 复用。
 *
 * @package OneDong
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="post-card__thumb" href="<?php the_permalink(); ?>" rel="bookmark" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'medium_large' ); ?>
		</a>
	<?php endif; ?>

	<header class="post-card__header">
		<?php
		the_title( '<h2 class="post-card__title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		?>
		<div class="post-card__meta">
			<span class="posted-on">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
			</span>
			<?php
			if ( 'post' === get_post_type() ) {
				$cats = get_the_category_list( ', ' );
				if ( $cats ) {
					echo '<span class="cat-links">' . esc_html__( '分类:', 'onedong' ) . ' ' . $cats . '</span>';
				}
			}
			?>
		</div>
	</header>

	<div class="post-card__summary">
		<?php the_excerpt(); ?>
	</div>

	<a class="read-more" href="<?php the_permalink(); ?>" rel="bookmark">
		<?php esc_html_e( '阅读全文', 'onedong' ); ?> →
	</a>
</article>
