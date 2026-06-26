<?php
/**
 * 文章列表项卡片(Template Part)
 * 供 home.php / archive.php / search.php 复用。
 * 对齐 Fuwari 演示:纯文字卡、单列、整卡可点、含「字数 · 阅读时长」。
 *
 * @package OneDong
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<div class="post-card__body">
		<h2 class="post-card__title">
			<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark"><?php the_title(); ?></a>
		</h2>

		<div class="post-card__meta">
			<time class="post-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>

			<?php if ( 'post' === get_post_type() ) : ?>
				<?php $cats = get_the_category(); ?>
				<?php if ( ! empty( $cats ) ) : ?>
					<span class="post-card__cats">
						<?php
						$links = array();
						foreach ( $cats as $cat ) {
							$links[] = '<a href="' . esc_url( get_category_link( $cat ) ) . '" rel="tag">' . esc_html( $cat->name ) . '</a>';
						}
						echo wp_kses_post( implode( ' <span class="sep" aria-hidden="true">/</span> ', $links ) );
						?>
					</span>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<div class="post-card__summary">
			<?php the_excerpt(); ?>
		</div>

		<div class="post-card__stats">
			<?php onedong_reading_stats(); ?>
		</div>
	</div>
</article>
