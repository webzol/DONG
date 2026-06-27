<?php
/**
 * 文章列表项卡片(Template Part)
 * 供 home.php / archive.php / search.php / index.php 复用。
 *
 * v2.0.0:suxing.me list-item 卡(上图下文 · 16:9 封面在顶 + 标题 + 摘要 + 底部 stats)。
 * 无特色图时不渲染封面区,卡片自然降级为纯文字竖向卡;显示项受 Customizer「文章卡」开关控制。
 *
 * @package OneDong
 */

$show_thumb = (bool) get_theme_mod( 'onedong_show_thumbnail', 1 );
$has_thumb  = has_post_thumbnail();
$cats       = get_the_category();
// 有特色图用特色图;无特色图但开启封面 → 用内置默认缩略图;关闭封面 → 纯文字卡
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?> data-reveal>
	<?php if ( $show_thumb ) : ?>
		<a class="post-card__thumb" href="<?php echo esc_url( get_permalink() ); ?>" tabindex="-1" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
			<?php if ( $has_thumb ) : ?>
				<?php the_post_thumbnail( 'onedong-card', array( 'loading' => 'lazy', 'class' => 'post-card__img' ) ); ?>
			<?php else : ?>
				<img class="post-card__img" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/default-thumb.png' ) ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" width="600" height="450">
			<?php endif; ?>
			<?php if ( ! empty( $cats ) ) : ?>
				<span class="post-card__cat-badge"><?php echo esc_html( $cats[0]->name ); ?></span>
			<?php endif; ?>
		</a>
	<?php endif; ?>

	<div class="post-card__body">
		<h2 class="post-card__title">
			<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark"><?php the_title(); ?></a>
		</h2>

		<?php if ( 'post' === get_post_type() ) : ?>
			<div class="post-card__meta">
				<span class="post-card__author">
					<span class="post-card__avatar-wrap">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 48, '', '', array( 'class' => 'post-card__avatar' ) ); ?>
						<span class="post-card__verified" aria-label="<?php esc_attr_e( '认证作者', 'onedong' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="11" fill="#FFB300"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</span>
					</span>
					<span class="post-card__author-name"><?php the_author(); ?><span class="online-dot" aria-label="<?php esc_attr_e( '在线', 'onedong' ); ?>"></span></span>
				</span>

				<time class="post-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
					<?php onedong_icon( 'calendar' ); ?>
					<?php echo esc_html( get_the_date() ); ?>
				</time>

				<?php if ( ! $show_thumb && ! empty( $cats ) ) : ?>
					<span class="post-card__cats">
						<?php onedong_icon( 'hash' ); ?>
						<?php
						$links = array();
						foreach ( $cats as $cat ) {
							$links[] = '<a href="' . esc_url( get_category_link( $cat ) ) . '" rel="tag">' . esc_html( $cat->name ) . '</a>';
						}
						echo wp_kses_post( implode( ' <span class="sep" aria-hidden="true">/</span> ', $links ) );
						?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="post-card__summary">
			<?php the_excerpt(); ?>
		</div>

		<div class="post-card__stats">
			<?php if ( get_theme_mod( 'onedong_show_views', 1 ) ) : ?>
				<span class="post-card__stat"><?php onedong_icon( 'eye' ); ?> <?php echo esc_html( number_format_i18n( onedong_get_views() ) ); ?></span>
			<?php endif; ?>

			<?php if ( get_theme_mod( 'onedong_show_comments', 1 ) && ( comments_open() || get_comments_number() ) ) : ?>
				<span class="post-card__stat"><?php onedong_icon( 'chat' ); ?> <?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?></span>
			<?php endif; ?>

			<?php if ( get_theme_mod( 'onedong_show_reading', 1 ) ) : ?>
				<span class="post-card__stat"><?php onedong_icon( 'clock' ); ?> <?php onedong_reading_stats(); ?></span>
			<?php endif; ?>

			<?php
			if ( get_theme_mod( 'onedong_show_tags', 1 ) ) :
				$tags = get_the_tags();
				if ( $tags && ! is_wp_error( $tags ) ) :
					foreach ( array_slice( $tags, 0, 3 ) as $t ) :
						printf( '<a class="post-card__tag" href="%1$s" rel="tag">%2$s</a>', esc_url( get_tag_link( $t ) ), esc_html( $t->name ) );
					endforeach;
				endif;
			endif;
			?>
		</div>
	</div>
</article>
