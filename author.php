<?php
/**
 * 作者详情页(author 归档)模板 · v6.0.12
 *
 * 点击作者头像 / 昵称 → /author/<slug>/
 *  1) 顶部封面:背景图(可上传)/ 纯色,左下头像 + 昵称 + 签名(空 → 「无限进步」)
 *  2) 下方两栏:左 sticky 信息卡(统计 + 地区 + 性别 + 简介 + 站点 + 加入于)
 *                右 文章列表(主查询,compact) + 朋友圈预览(最新 6 条 + 查看全部)
 *
 * @package OneDong
 */

get_header();

$author     = get_queried_object();          // WP_User
$author_id  = $author->ID;
$keys       = onedong_author_meta_keys();

$signature_raw = get_user_meta( $author_id, $keys['signature'], true );
$region        = get_user_meta( $author_id, $keys['region'], true );
$gender_raw    = get_user_meta( $author_id, $keys['gender'], true );
$cover_url     = get_user_meta( $author_id, $keys['cover'], true );

$display = $author->display_name;
$bio     = trim( $author->description );
$site    = $author->user_url;

// 签名:留空 → 默认「无限进步」
$signature = $signature_raw ? $signature_raw : __( '无限进步', 'onedong' );

// 性别:仅 male/female 显示;空(不公开)不渲染该行
$gender_label = '';
if ( 'male' === $gender_raw ) {
	$gender_label = __( '男', 'onedong' );
} elseif ( 'female' === $gender_raw ) {
	$gender_label = __( '女', 'onedong' );
}

$posts_count   = (int) count_user_posts( $author_id, 'post', true );
$moments_count = (int) count_user_posts( $author_id, 'onedong_moment', true );
$registered    = $author->user_registered ? date_i18n( __( 'Y 年 n 月', 'onedong' ), strtotime( $author->user_registered ) ) : '';

// 朋友圈预览(最新 6 条);无则不显示朋友圈区块
$moments_preview = new WP_Query(
	array(
		'post_type'      => 'onedong_moment',
		'author'         => $author_id,
		'posts_per_page' => 6,
		'no_found_rows'  => true,
	)
);
$has_moments = $moments_preview->have_posts();

// 头像:站点管理员走主题头像来源(与左栏 / 朋友圈封面一致)
$cover_avatar = onedong_author_avatar_html( $author_id, 144, array( 'class' => 'author-cover__avatar', 'alt' => $display ) );
$info_avatar  = onedong_author_avatar_html( $author_id, 96, array( 'class' => 'author-info__avatar', 'alt' => $display ) );

$banner_style = $cover_url ? ' style="background-image:url(' . esc_url( $cover_url ) . ')"' : '';
?>

<div class="author-page">

	<!-- 1) 封面:背景图 / 纯色 + 左下头像 + 昵称 + 签名 -->
	<section class="author-cover">
		<div class="author-cover__banner"<?php echo $banner_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — esc_url 已转义 ?>></div>
		<div class="author-cover__id">
			<?php if ( $cover_avatar ) : ?>
				<div class="author-cover__avatar-wrap">
					<?php echo $cover_avatar; // phpcs:ignore — onedong_author_avatar_html 已转义 ?>
				</div>
			<?php endif; ?>
			<div class="author-cover__meta">
				<h1 class="author-cover__name"><?php echo esc_html( $display ); ?></h1>
				<p class="author-cover__signature"><?php echo esc_html( $signature ); ?></p>
			</div>
		</div>
	</section>

	<!-- 2) 主体:左 sticky 信息卡 + 右内容 -->
	<div class="author-body">

		<aside class="author-info" aria-label="<?php esc_attr_e( '作者资料', 'onedong' ); ?>">
			<?php if ( $info_avatar ) : ?>
				<div class="author-info__head">
					<?php echo $info_avatar; // phpcs:ignore — 已转义 ?>
					<div class="author-info__head-text">
						<h2 class="author-info__name"><?php echo esc_html( $display ); ?></h2>
						<?php if ( $region ) : ?>
							<p class="author-info__region">
								<span class="author-info__chip"><?php onedong_icon( 'map-pin' ); ?><?php echo esc_html( $region ); ?></span>
							</p>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="author-info__stats">
				<div class="author-info__stat">
					<strong><?php echo esc_html( number_format_i18n( $posts_count ) ); ?></strong>
					<small><?php esc_html_e( '文章', 'onedong' ); ?></small>
				</div>
				<div class="author-info__stat">
					<strong><?php echo esc_html( number_format_i18n( $moments_count ) ); ?></strong>
					<small><?php esc_html_e( '朋友圈', 'onedong' ); ?></small>
				</div>
			</div>

			<dl class="author-info__list">
				<?php if ( $gender_label ) : ?>
					<div class="author-info__row">
						<dt><?php onedong_icon( 'gender' ); ?><span><?php esc_html_e( '性别', 'onedong' ); ?></span></dt>
						<dd><?php echo esc_html( $gender_label ); ?></dd>
					</div>
				<?php endif; ?>

				<?php if ( $bio ) : ?>
					<div class="author-info__row">
						<dt><?php onedong_icon( 'info' ); ?><span><?php esc_html_e( '简介', 'onedong' ); ?></span></dt>
						<dd><?php echo wp_kses_post( wpautop( $bio ) ); ?></dd>
					</div>
				<?php endif; ?>

				<?php if ( $site ) : ?>
					<div class="author-info__row">
						<dt><?php onedong_icon( 'link' ); ?><span><?php esc_html_e( '站点', 'onedong' ); ?></span></dt>
						<dd>
							<a href="<?php echo esc_url( $site ); ?>" target="_blank" rel="noopener">
								<?php
								$host = wp_parse_url( $site, PHP_URL_HOST );
								echo esc_html( $host ? $host : $site );
								?>
							</a>
						</dd>
					</div>
				<?php endif; ?>

				<?php if ( $registered ) : ?>
					<div class="author-info__row">
						<dt><?php onedong_icon( 'calendar' ); ?><span><?php esc_html_e( '加入于', 'onedong' ); ?></span></dt>
						<dd><?php echo esc_html( $registered ); ?></dd>
					</div>
				<?php endif; ?>
			</dl>
		</aside>

		<div class="author-feed">

			<?php if ( $has_moments ) : ?>
				<div class="author-tabs" role="tablist" aria-label="<?php esc_attr_e( '作者内容', 'onedong' ); ?>">
					<button type="button" class="author-tabs__btn is-active" role="tab" id="author-tabbtn-articles" data-tab="articles" aria-selected="true" aria-controls="author-tab-articles"><?php esc_html_e( '文章', 'onedong' ); ?></button>
					<button type="button" class="author-tabs__btn" role="tab" id="author-tabbtn-moments" data-tab="moments" aria-selected="false" aria-controls="author-tab-moments"><?php esc_html_e( '朋友圈', 'onedong' ); ?></button>
				</div>
			<?php endif; ?>

			<!-- 文章(主查询,分页) -->
			<section id="author-tab-articles" class="author-tab author-tab--articles is-active" role="tabpanel" aria-labelledby="author-tabbtn-articles">
				<?php if ( have_posts() ) : ?>
					<ul class="author-posts">
						<?php
						while ( have_posts() ) :
							the_post();
							?>
							<li class="author-posts__item">
								<a class="author-posts__link" href="<?php the_permalink(); ?>">
									<?php if ( has_post_thumbnail() ) : ?>
										<span class="author-posts__thumb"><?php the_post_thumbnail( array( 120, 90 ) ); ?></span>
									<?php else : ?>
										<span class="author-posts__thumb author-posts__thumb--ph"><?php onedong_icon( 'image' ); ?></span>
									<?php endif; ?>
									<span class="author-posts__body">
										<span class="author-posts__name"><?php the_title(); ?></span>
										<span class="author-posts__meta">
											<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
											<span class="author-posts__meta-item"><?php onedong_icon( 'eye' ); ?><?php echo esc_html( number_format_i18n( onedong_get_views() ) ); ?></span>
											<span class="author-posts__meta-item"><?php onedong_icon( 'chat' ); ?><?php echo get_comments_number(); ?></span>
										</span>
									</span>
								</a>
							</li>
							<?php
						endwhile;
						?>
					</ul>
					<?php get_template_part( 'template-parts/pagination' ); ?>
				<?php else : ?>
					<p class="author-empty"><?php esc_html_e( '还没有发布文章。', 'onedong' ); ?></p>
				<?php endif; ?>
			</section>

			<!-- 朋友圈(最新 6 条 + 查看全部) -->
			<?php if ( $has_moments ) : ?>
				<section id="author-tab-moments" class="author-tab author-tab--moments" role="tabpanel" aria-labelledby="author-tabbtn-moments">
					<div class="moments-feed">
						<?php
						while ( $moments_preview->have_posts() ) :
							$moments_preview->the_post();
							onedong_render_moment();
						endwhile;
						?>
					</div>
					<a class="author-tab__more" href="<?php echo esc_url( get_post_type_archive_link( 'onedong_moment' ) ); ?>"><?php esc_html_e( '查看全部朋友圈 →', 'onedong' ); ?></a>
				</section>
				<?php
				wp_reset_postdata();
			endif;
			?>
		</div>

	</div>
</div>

<?php
get_footer();
