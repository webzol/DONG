<?php
/**
 * 页脚模板
 *
 * @package OneDong
 */
?>
</main><!-- #main -->

<?php if ( is_active_sidebar( 'footer-widgets' ) ) : ?>
	<div class="footer-widgets">
		<div class="footer-widgets__inner">
			<?php dynamic_sidebar( 'footer-widgets' ); ?>
		</div>
	</div>
<?php endif; ?>

<footer id="colophon" class="site-footer">
	<div class="site-footer__inner">
		<div class="site-info">
			<?php
			$copyright = get_theme_mod( 'onedong_footer_copyright', '' );
			if ( trim( wp_strip_all_tags( $copyright ) ) ) {
				echo '<span class="site-copyright">' . wp_kses_post( $copyright ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 已 wp_kses_post
			} else {
				echo '<span class="site-copyright">';
				printf(
					/* translators: 1: 年份, 2: 站点链接(HTML) */
					esc_html__( '© %1$s %2$s · OneDong 主题', 'onedong' ),
					esc_html( gmdate( 'Y' ) ),
					'<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>'
				);
				echo '</span>';
			}

			$icp     = get_theme_mod( 'onedong_footer_icp', '' );
			$icp_url = get_theme_mod( 'onedong_footer_icp_url', 'https://beian.miit.gov.cn' );
			if ( trim( $icp ) ) {
				echo '<span class="site-icp">';
				if ( $icp_url ) {
					echo '<a href="' . esc_url( $icp_url ) . '" target="_blank" rel="noopener nofollow">' . esc_html( $icp ) . '</a>';
				} else {
					echo esc_html( $icp );
				}
				echo '</span>';
			}
			?>
		</div>

		<?php
		if ( has_nav_menu( 'footer' ) ) {
			echo '<nav class="footer-nav" aria-label="' . esc_attr__( '页脚导航', 'onedong' ) . '">';
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'menu',
					'depth'          => 1,
				)
			);
			echo '</nav>';
		}
		?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
