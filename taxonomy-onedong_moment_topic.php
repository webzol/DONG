<?php
/**
 * 话题归档模板(onedong_moment_topic)· v6.1.7
 * 访问 /moments/topic/{话题}/,列出该话题下所有朋友圈。
 * 复用 /moments/ 的三栏 + 封面布局,feed 顶部加话题标题块;
 * have_posts() 在 term 归档上天然已是该话题下的 moments。
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content site-content--three-col">
	<?php get_sidebar( 'left' ); ?>

	<div class="content-main moments-page">
		<?php
		// 朋友圈封面(与 /moments/ 一致:顶部封面背景 + 右下角头像 / 昵称)
		$moments_cover = get_theme_mod( 'onedong_moments_cover', '' );
		$avatar_source = get_theme_mod( 'onedong_avatar_source', 'logo' );
		$cover_user    = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
		$cover_name    = $cover_user ? $cover_user->display_name : get_bloginfo( 'name' );

		$cover_avatar_html = '';
		if ( 'logo' === $avatar_source && has_custom_logo() ) {
			$cover_avatar_html = wp_get_attachment_image(
				get_theme_mod( 'custom_logo' ),
				array( 144, 144 ),
				false,
				array( 'class' => 'moments-cover__avatar', 'alt' => esc_attr( $cover_name ) )
			);
		} elseif ( 'gravatar' === $avatar_source ) {
			$cover_avatar_html = get_avatar( get_bloginfo( 'admin_email' ), 144, 'retro', esc_attr( $cover_name ), array( 'class' => 'moments-cover__avatar' ) );
		} else {
			$custom_av = get_theme_mod( 'onedong_avatar_custom', '' );
			if ( $custom_av ) {
				$cover_avatar_html = '<img class="moments-cover__avatar" src="' . esc_url( $custom_av ) . '" alt="' . esc_attr( $cover_name ) . '">';
			}
		}

		if ( $moments_cover || $cover_avatar_html ) :
			?>
			<div class="moments-cover">
				<div class="moments-cover__banner"<?php echo $moments_cover ? ' style="background-image:url(' . esc_url( $moments_cover ) . ')"' : ''; ?>></div>
				<?php if ( $cover_avatar_html ) : ?>
					<div class="moments-cover__id">
						<span class="moments-cover__name"><?php echo $cover_user ? '<a href="' . esc_url( get_author_posts_url( $cover_user->ID ) ) . '">' . esc_html( $cover_name ) . '</a>' : esc_html( $cover_name ); ?></span>
						<?php echo $cover_avatar_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — WP 核心 API 已转义 ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		endif;

		$term = get_queried_object();
		?>
		<div class="moments-feed">
			<?php if ( $term && ! is_wp_error( $term ) ) : ?>
				<div class="moments-topic-head">
					<h1 class="moments-topic-head__title">#<?php echo esc_html( $term->name ); ?></h1>
					<div class="moments-topic-head__meta">
						<?php if ( isset( $term->count ) && $term->count ) : ?>
							<span class="moments-topic-head__count"><?php echo esc_html( sprintf( _n( '%d 条动态', '%d 条动态', $term->count, 'onedong' ), $term->count ) ); ?></span>
						<?php endif; ?>
						<a class="moments-topic-head__back" href="<?php echo esc_url( get_post_type_archive_link( 'onedong_moment' ) ); ?>"><?php esc_html_e( '← 全部朋友圈', 'onedong' ); ?></a>
					</div>
					<?php if ( ! empty( $term->description ) ) : ?>
						<p class="moments-topic-head__desc"><?php echo esc_html( $term->description ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>
				<?php
				while ( have_posts() ) :
					the_post();
					onedong_render_moment();
				endwhile;
				?>
				<?php get_template_part( 'template-parts/pagination' ); ?>
			<?php else : ?>
				<p class="moments-empty"><?php esc_html_e( '该话题下还没有动态。', 'onedong' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
