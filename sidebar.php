<?php
/**
 * 侧栏(Sidebar)
 * Fuwari 风格:个人资料卡 + 分类列表(带文章数)+ 标签云。
 * 由 home.php / archive.php / search.php 经 get_sidebar() 引入。
 *
 * @package OneDong
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<aside id="secondary" class="sidebar">
	<!-- 个人资料(作者卡:头像 + 站点名 + 副标题 + 文章/评论总数) -->
	<section class="widget widget-profile">
		<?php
		$avatar_source = get_theme_mod( 'onedong_avatar_source', 'logo' );
		if ( 'logo' === $avatar_source && has_custom_logo() ) :
			$logo_id = get_theme_mod( 'custom_logo' );
			echo wp_get_attachment_image(
				$logo_id,
				array( 80, 80 ),
				false,
				array(
					'class' => 'widget-profile__avatar',
					'alt'   => esc_attr( get_bloginfo( 'name' ) ),
				)
			);
		elseif ( 'gravatar' === $avatar_source ) :
			echo get_avatar(
				get_bloginfo( 'admin_email' ),
				80,
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

	<!-- 分类(带计数,按文章数倒序) -->
	<?php
	$cats = get_categories(
		array(
			'orderby' => 'count',
			'order'   => 'DESC',
			'number'  => 12,
		)
	);
	if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) :
		?>
		<section class="widget widget-cats">
			<h2 class="widget-title"><?php esc_html_e( '分类', 'onedong' ); ?></h2>
			<ul class="widget-cats__list">
				<?php foreach ( $cats as $cat ) : ?>
					<li>
						<a href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
						<span class="count"><?php echo esc_html( number_format_i18n( $cat->count ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<!-- 标签云 -->
	<?php
	$tags = get_tags();
	if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) :
		?>
		<section class="widget widget-tags">
			<h2 class="widget-title"><?php esc_html_e( '标签', 'onedong' ); ?></h2>
			<div class="widget-tags__cloud">
				<?php
				foreach ( $tags as $tag ) {
					printf(
						'<a href="%1$s" class="tag-link">%2$s</a>',
						esc_url( get_tag_link( $tag ) ),
						esc_html( $tag->name )
					);
				}
				?>
			</div>
		</section>
	<?php endif; ?>
</aside>
