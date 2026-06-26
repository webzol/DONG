<?php
/**
 * 页头模板
 *
 * @package OneDong
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php
	// Anti-flash:渲染前据 localStorage 偏好(light/dark/auto)与系统设置 data-theme,避免刷新闪白。
	?>
	<script>
	(function(){try{var k='onedong-theme',s=localStorage.getItem(k);var d=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);var r=(s==='light'||s==='dark')?s:(d?'dark':'light');document.documentElement.setAttribute('data-theme',r);}catch(e){document.documentElement.setAttribute('data-theme','light');}})();
	</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( '跳到正文', 'onedong' ); ?></a>

<header id="masthead" class="site-header">
	<div class="site-header__inner">
		<div class="site-brand">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				?>
				<h1 class="site-title">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				</h1>
				<?php
				$onedong_description = get_bloginfo( 'description', 'display' );
				if ( $onedong_description || is_customize_preview() ) {
					printf( '<p class="site-description">%s</p>', esc_html( $onedong_description ) );
				}
			}
			?>
		</div>

		<nav class="primary-nav" aria-label="<?php esc_attr_e( '主导航', 'onedong' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'menu',
						'depth'          => 2,
					)
				);
			} else {
				wp_page_menu(
					array(
						'container' => false,
						'menu_class' => 'menu',
						'before'     => '<ul id="primary-menu" class="menu">',
						'after'      => '</ul>',
					)
				);
			}
			?>
		</nav>

		<div class="header-controls">
			<label class="hue-control" title="<?php esc_attr_e( '主题色', 'onedong' ); ?>">
				<span class="sr-only"><?php esc_html_e( '主题色', 'onedong' ); ?></span>
				<input type="range" min="0" max="360" step="1" value="<?php echo esc_attr( (int) get_theme_mod( 'onedong_hue', 215 ) ); ?>" class="hue-slider" id="hue-slider" aria-label="<?php esc_attr_e( '主题色相', 'onedong' ); ?>">
			</label>

			<button class="theme-toggle" type="button" aria-label="<?php esc_attr_e( '切换深浅色模式', 'onedong' ); ?>" data-pref="auto" title="<?php esc_attr_e( '切换深浅色', 'onedong' ); ?>">
				<span class="theme-toggle__icon theme-toggle__sun" aria-hidden="true">☀️</span>
				<span class="theme-toggle__icon theme-toggle__moon" aria-hidden="true">🌙</span>
				<span class="theme-toggle__icon theme-toggle__auto" aria-hidden="true">🖥️</span>
			</button>
		</div>
	</div>
</header>

<main id="main" class="site-main">
