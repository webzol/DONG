<?php
/**
 * 左侧栏:可配置模块(suxing 风格)。
 * 由 home.php / archive.php / search.php 经 get_sidebar( 'left' ) 引入。
 * 模块(固定顺序):作者卡 → 自定义文本 → 最新文章 → 热门文章。
 * 各模块由后台「外观 → 自定义 → 左侧栏模块」开关控制;全关时不输出 aside。
 * ≤1180px 时由 layout.css 隐藏(.sidebar-left { display:none })。
 *
 * @package OneDong
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$any_left =
	get_theme_mod( 'onedong_left_author', 1 ) ||
	get_theme_mod( 'onedong_left_text', 0 ) ||
	get_theme_mod( 'onedong_left_recent', 0 ) ||
	get_theme_mod( 'onedong_left_popular', 0 );

if ( ! $any_left ) {
	return; // 全关:不输出空侧栏
}
?>
<aside id="profile" class="sidebar-left" aria-label="<?php esc_attr_e( '作者资料与侧栏模块', 'onedong' ); ?>">

	<?php if ( get_theme_mod( 'onedong_left_author', 1 ) ) : ?>
		<section class="widget widget-profile">
			<?php
			$avatar_source = get_theme_mod( 'onedong_avatar_source', 'logo' );
			if ( 'logo' === $avatar_source && has_custom_logo() ) :
				$logo_id = get_theme_mod( 'custom_logo' );
				echo wp_get_attachment_image(
					$logo_id,
					array( 96, 96 ),
					false,
					array(
						'class' => 'widget-profile__avatar',
						'alt'   => esc_attr( get_bloginfo( 'name' ) ),
					)
				);
			elseif ( 'gravatar' === $avatar_source ) :
				echo get_avatar(
					get_bloginfo( 'admin_email' ),
					96,
					'retro',
					esc_attr( get_bloginfo( 'name' ) ),
					array( 'class' => 'widget-profile__avatar' )
				);
			endif;
			?>
			<h2 class="widget-profile__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2>
			<?php if ( get_bloginfo( 'description' ) ) : ?>
				<p class="widget-profile__desc"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			<?php endif; ?>

			<?php if ( get_theme_mod( 'onedong_show_author_stats', 1 ) ) : ?>
				<?php
				$post_count    = (int) wp_count_posts()->publish;
				$comment_count = (int) wp_count_comments()->approved;
				?>
				<div class="widget-profile__stats">
					<span class="widget-profile__stat">
						<strong><?php onedong_icon( 'hash' ); ?><?php echo esc_html( number_format_i18n( $post_count ) ); ?></strong>
						<small><?php esc_html_e( '文章', 'onedong' ); ?></small>
					</span>
					<span class="widget-profile__stat">
						<strong><?php onedong_icon( 'chat' ); ?><?php echo esc_html( number_format_i18n( $comment_count ) ); ?></strong>
						<small><?php esc_html_e( '评论', 'onedong' ); ?></small>
					</span>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( get_theme_mod( 'onedong_left_text', 0 ) ) { onedong_widget_text( 'left' ); } ?>
	<?php if ( get_theme_mod( 'onedong_left_recent', 0 ) ) { onedong_widget_recent_posts(); } ?>
	<?php if ( get_theme_mod( 'onedong_left_popular', 0 ) ) { onedong_widget_popular_posts(); } ?>

</aside>
