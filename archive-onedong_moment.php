<?php
/**
 * 朋友圈(onedong_moment)归档模板 · v2.5.2
 * 三栏布局(与首页一致):左作者卡 + 中朋友圈流 + 右侧栏。访问 /moments/。
 *
 * @package OneDong
 */

get_header();
?>
<div class="site-content site-content--three-col">
	<?php get_sidebar( 'left' ); ?>

	<div class="content-main moments-page">
		<?php
		// 朋友圈封面(微信朋友圈风):顶部封面背景 + 右下角头像/昵称 · v6.0.11
		$moments_cover  = get_theme_mod( 'onedong_moments_cover', '' );
		$avatar_source  = get_theme_mod( 'onedong_avatar_source', 'logo' );
		$cover_user     = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
		$cover_name     = $cover_user ? $cover_user->display_name : get_bloginfo( 'name' );

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
		?>
		<div class="moments-feed">
			<?php if ( have_posts() ) : ?>
				<?php
				while ( have_posts() ) :
					the_post();
					onedong_render_moment();
				endwhile;
				?>
				<?php get_template_part( 'template-parts/pagination' ); ?>
			<?php else : ?>
				<p class="moments-empty"><?php esc_html_e( '还没有朋友圈动态。', 'onedong' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
