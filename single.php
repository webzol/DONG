<?php
/**
 * 单篇文章模板(三栏:左作者卡 + 中正文 + 右侧栏,与首页统一)
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content site-content--three-col">
	<?php get_sidebar( 'left' ); ?>

	<div class="content-main">
		<?php // 阅读进度条(fixed 顶部,single.js 驱动) ?>
		<div class="reading-progress" aria-hidden="true"><span class="reading-progress__bar"></span></div>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'post' ); ?>>
				<?php // 分类面包屑:首页 / 分类 / 标题 ?>
				<?php $bc_cats = get_the_category(); ?>
				<nav class="breadcrumb" aria-label="<?php esc_attr_e( '面包屑', 'onedong' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '首页', 'onedong' ); ?></a>
					<?php if ( ! empty( $bc_cats ) ) : ?>
						<span class="breadcrumb__sep" aria-hidden="true">/</span>
						<a href="<?php echo esc_url( get_category_link( $bc_cats[0]->term_id ) ); ?>"><?php echo esc_html( $bc_cats[0]->name ); ?></a>
					<?php endif; ?>
					<span class="breadcrumb__sep" aria-hidden="true">/</span>
					<span class="breadcrumb__current"><?php the_title(); ?></span>
				</nav>

				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
					<?php onedong_entry_meta(); ?>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="post-thumbnail">
						<?php
						// 文章内页头图 = LCP:急切加载 + 高优先级 + 异步解码。
						the_post_thumbnail(
							'large',
							array(
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'decoding'      => 'async',
							)
						);
						?>
					</div>
				<?php endif; ?>

				<?php onedong_toc(); ?>

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

				<?php
				// 底部互动栏:浏览 / 字数 / 评论 / 点赞(复用 .post-card__stat / .post-card__like;
				// 点赞沿用 .post-card__like 类,供 likes.js 直接绑定,无需改 JS)。
				?>
				<div class="entry-actions">
					<button class="entry-share" type="button" data-share-trigger aria-label="<?php esc_attr_e( '分享', 'onedong' ); ?>">
						<?php onedong_icon( 'share' ); ?>
					</button>
					<span class="post-card__stat" title="<?php esc_attr_e( '阅读量', 'onedong' ); ?>">
						<?php onedong_icon( 'eye' ); ?>
						<span><?php echo esc_html( number_format_i18n( onedong_get_views() ) ); ?></span>
					</span>
					<span class="post-card__stat" title="<?php esc_attr_e( '评论', 'onedong' ); ?>">
						<?php onedong_icon( 'chat' ); ?>
						<span><?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?></span>
					</span>
					<button class="post-card__like" type="button" data-id="<?php the_ID(); ?>" aria-label="<?php esc_attr_e( '点赞', 'onedong' ); ?>">
						<?php onedong_icon( 'heart' ); ?>
						<span class="count"><?php echo esc_html( onedong_get_likes() ); ?></span>
					</button>
				</div>
			</article>

			<?php onedong_share_card(); ?>

			<?php onedong_post_nav(); ?>

			<?php onedong_related_posts(); ?>

			<?php
			// 评论(开启评论或有评论时显示)
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		<?php endwhile; ?>
	</div>

	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
