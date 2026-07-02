<?php
/**
 * 文章列表项卡片(Template Part) · v2.4.1 重新设计
 * 供 home.php / archive.php / search.php / index.php 复用。
 *
 * @package OneDong
 */

$show_thumb = true;
$has_thumb  = has_post_thumbnail();
$cats       = get_the_category();
$format     = get_post_format();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( array( 'post-card', 'post-card--format-' . ( $format ? $format : 'standard' ) ) ); ?> data-reveal>
	<!-- 1. 头部: 头像 + (昵称/日期) -->
	<div class="post-card__header">
		<div class="post-card__avatar-wrap">
			<?php echo get_avatar( get_the_author_meta( 'ID' ), 96, '', '', array( 'class' => 'post-card__avatar' ) ); ?>
			<span class="post-card__verified" title="<?php esc_attr_e( '认证作者', 'onedong' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="#FFB300"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</span>
		</div>
		<div class="post-card__author-info">
			<span class="post-card__author-name">
				<?php the_author(); ?>
				<span class="online-dot" title="<?php esc_attr_e( '在线', 'onedong' ); ?>"></span>
			</span>
			<time class="post-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</div>
	</div>

	<!-- 2. 标题 -->
	<h2 class="post-card__title">
		<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark"><?php the_title(); ?></a>
	</h2>

	<!-- 4. 文章介绍 -->
	<div class="post-card__summary">
		<?php the_excerpt(); ?>
	</div>

	<!-- 5. 封面 / 多图(无图则纯文字卡,不渲染 media) -->
	<?php
	$gallery = ( 'gallery' === $format ) ? get_post_gallery( get_the_ID(), false ) : '';
	$gids    = ! empty( $gallery['ids'] ) ? array_filter( explode( ',', $gallery['ids'] ) ) : array();
	$gcount  = count( $gids );
	?>
	<?php if ( $has_thumb || $gcount > 0 ) : ?>
		<div class="post-card__media">
			<?php if ( ! empty( $cats ) ) : ?>
				<span class="post-card__cat-badge"><?php echo esc_html( $cats[0]->name ); ?></span>
			<?php endif; ?>

			<?php if ( $gcount > 0 ) :
				// 多图:默认展示前 3 张,第 3 张叠加 +N,点击跳文章查看更多
				$display_ids = array_slice( $gids, 0, 3 );
				$rest_count  = $gcount - 3;
				$grid_class  = 'post-card__grid post-card__grid--' . min( $gcount, 3 );
				?>
				<div class="<?php echo esc_attr( $grid_class ); ?>">
					<?php foreach ( $display_ids as $i => $img_id ) : ?>
						<a href="<?php echo esc_url( get_permalink() ); ?>" class="post-card__grid-item">
							<?php echo wp_get_attachment_image( $img_id, 'thumbnail', false, array( 'class' => 'post-card__grid-img' ) ); ?>
							<?php if ( 2 === $i && $rest_count > 0 ) : ?>
								<span class="post-card__grid-more">+<?php echo (int) $rest_count; ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<a href="<?php echo esc_url( get_permalink() ); ?>" class="post-card__thumb-link">
					<?php the_post_thumbnail( 'large', array( 'class' => 'post-card__img' ) ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- 5. 底部统计栏 -->
	<footer class="post-card__footer">
		<span class="post-card__stat" title="<?php esc_attr_e( '浏览量', 'onedong' ); ?>">
			<?php onedong_icon( 'eye' ); ?>
			<span><?php echo esc_html( number_format_i18n( onedong_get_views() ) ); ?></span>
		</span>
		<span class="post-card__stat" title="<?php esc_attr_e( '字数', 'onedong' ); ?>">
			<?php onedong_icon( 'type' ); ?>
			<span><?php onedong_word_count(); ?></span>
		</span>
		<span class="post-card__stat" title="<?php esc_attr_e( '评论', 'onedong' ); ?>">
			<?php onedong_icon( 'chat' ); ?>
			<span><?php echo get_comments_number(); ?></span>
		</span>
		<button class="post-card__like" data-id="<?php the_ID(); ?>" aria-label="<?php esc_attr_e( '点赞', 'onedong' ); ?>">
			<?php onedong_icon( 'heart' ); ?>
			<span class="count"><?php echo esc_html( onedong_get_likes() ); ?></span>
		</button>
	</footer>
</article>
