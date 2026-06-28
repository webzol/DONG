<?php
/**
 * 文章列表项卡片(Template Part) · v2.4.1 重新设计
 * 供 home.php / archive.php / search.php / index.php 复用。
 *
 * 布局(自上而下):作者行(头像+黄V+昵称+在线点 + 发布时间)→ 标题 → 摘要
 *      → 封面图(16:9 全宽)→ 底部三项(左阅读 / 中字数 / 右点赞)。
 * 点赞可点击 +1(REST _onedong_likes;likes.js 驱动,localStorage 防重复)。
 * 无特色图用内置默认缩略图;关闭封面则无图区。
 *
 * @package OneDong
 */

$show_thumb = (bool) get_theme_mod( 'onedong_show_thumbnail', 1 );
$has_thumb  = has_post_thumbnail();
$cats       = get_the_category();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?> data-reveal>
	<div class="post-card__body">
		<?php if ( 'post' === get_post_type() ) : ?>
			<div class="post-card__meta">
				<span class="post-card__author">
					<span class="post-card__avatar-wrap">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 96, '', '', array( 'class' => 'post-card__avatar' ) ); ?>
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
	</div>

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
			<?php
			else :
				$default_thumb = get_theme_mod( 'onedong_default_thumb', '' );
				$default_src   = $default_thumb ? $default_thumb : get_theme_file_uri( 'assets/img/default-thumb.png' );
			?>
				<img class="post-card__img"
					src="<?php echo esc_url( $default_src ); ?>"
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

	<div class="post-card__stats">
		<span class="post-card__stat">
			<?php onedong_icon( 'eye' ); ?>
			<span class="post-card__stat-count"><?php echo esc_html( number_format_i18n( onedong_get_views() ) ); ?></span>
		</span>
		<span class="post-card__stat">
			<?php onedong_icon( 'type' ); ?>
			<span class="post-card__stat-count"><?php onedong_word_count(); ?></span>
		</span>
		<button class="post-card__like" type="button" data-id="<?php the_ID(); ?>" aria-label="<?php esc_attr_e( '点赞', 'onedong' ); ?>">
			<?php onedong_icon( 'heart' ); ?>
			<span class="post-card__like-count"><?php echo esc_html( number_format_i18n( onedong_get_likes() ) ); ?></span>
		</button>
	</div>
</article>
