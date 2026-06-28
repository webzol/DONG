<?php
/**
 * 文章列表项卡片(Template Part) · 上图下文(v2.4.0)
 * 供 home.php / archive.php / search.php / index.php 复用。
 *
 * 布局:封面图(16:9)在顶 → 作者行(头像+黄V+昵称+在线点+日期,在标题上方)
 *      → 标题 → 摘要 → 底部互动数据(浏览/评论/阅读时长 + 标签,微博风水平排列)。
 * 无特色图用内置默认缩略图;关闭封面 → 纯文字卡。
 * 显示项受 Customizer「文章卡」开关控制。
 *
 * @package OneDong
 */

$show_thumb = (bool) get_theme_mod( 'onedong_show_thumbnail', 1 );
$has_thumb  = has_post_thumbnail();
$cats       = get_the_category();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?> data-reveal>
	<?php if ( $show_thumb ) : ?>
		<?php
		// 首屏(主查询第一篇)封面 = LCP:急切加载 + fetchpriority=high;其余懒加载。
		global $wp_query;
		$is_lcp   = $wp_query && $wp_query->is_main_query() && 0 === (int) $wp_query->current_post;
		$img_attr = array(
			'class'    => 'post-card__img',
			'decoding' => 'async',
			'loading'  => $is_lcp ? 'eager' : 'lazy',
			'sizes'    => '(max-width: 768px) 92vw, (max-width: 1180px) 62vw, 720px',
		);
		if ( $is_lcp ) {
			$img_attr['fetchpriority'] = 'high';
		}
		?>
		<a class="post-card__thumb" href="<?php echo esc_url( get_permalink() ); ?>" tabindex="-1" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
			<?php if ( $has_thumb ) : ?>
				<?php echo wp_get_attachment_image( get_post_thumbnail_id(), 'onedong-card', false, $img_attr ); ?>
			<?php else : ?>
				<img class="post-card__img"
					src="<?php echo esc_url( get_theme_file_uri( 'assets/img/default-thumb.png' ) ); ?>"
					alt="<?php the_title_attribute(); ?>"
					decoding="async"
					loading="<?php echo $is_lcp ? 'eager' : 'lazy'; ?>"
					<?php echo $is_lcp ? 'fetchpriority="high"' : ''; ?>
					width="600" height="450">
			<?php endif; ?>
			<?php if ( ! empty( $cats ) ) : ?>
				<span class="post-card__cat-badge"><?php echo esc_html( $cats[0]->name ); ?></span>
			<?php endif; ?>
		</a>
	<?php endif; ?>

	<div class="post-card__body">
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
			</div>
		<?php endif; ?>

		<h2 class="post-card__title">
			<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark"><?php the_title(); ?></a>
		</h2>

		<div class="post-card__summary">
			<?php the_excerpt(); ?>
		</div>

		<div class="post-card__stats">
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
