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
			printf(
				/* translators: 1: 年份, 2: 站点链接(HTML) */
				esc_html__( '© %1$s %2$s · OneDong 主题', 'onedong' ),
				esc_html( gmdate( 'Y' ) ),
				'<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>'
			);
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
