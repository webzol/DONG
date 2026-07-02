<?php
/**
 * 页头模板
 *
 * @package OneDong
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php
	// Anti-flash:渲染前据 localStorage 偏好(light/dark/auto)与系统设置 data-theme,避免刷新闪白。
	?>
	<script>
	(function(){try{var k='onedong-theme',s=localStorage.getItem(k);var d=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);var r=(s==='light'||s==='dark')?s:(d?'dark':'light');document.documentElement.setAttribute('data-theme',r);}catch(e){document.documentElement.setAttribute('data-theme','light');}})();
	document.documentElement.className=document.documentElement.className.replace(/\bno-js\b/,' js');
	</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( '跳到正文', 'onedong' ); ?></a>

<header id="masthead" class="site-header">
	<div class="site-header__inner">
		<?php $dark_logo = get_theme_mod( 'onedong_logo_dark', '' ); ?>
		<div class="site-brand<?php echo $dark_logo ? ' site-brand--has-dark' : ''; ?>">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
				if ( $dark_logo ) {
					?>
					<img class="site-logo--dark" src="<?php echo esc_url( $dark_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" loading="lazy">
					<?php
				}
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
			<button class="mobile-menu-toggle" type="button" aria-label="<?php esc_attr_e( '菜单', 'onedong' ); ?>" aria-controls="primary-menu" aria-expanded="false">
				<?php onedong_icon( 'menu' ); ?>
			</button>
			<button class="theme-toggle" type="button" aria-label="<?php esc_attr_e( '切换深浅色模式', 'onedong' ); ?>" data-pref="auto" title="<?php esc_attr_e( '切换深浅色', 'onedong' ); ?>">
				<span class="theme-toggle__icon theme-toggle__sun" aria-hidden="true"><?php onedong_icon( 'sun' ); ?></span>
				<span class="theme-toggle__icon theme-toggle__moon" aria-hidden="true"><?php onedong_icon( 'moon' ); ?></span>
			</button>
		</div>
	</div>
	<script>
	// 汉堡菜单:移动端点击 → 左侧抽屉滑出 + 遮罩;点遮罩 / ESC / 菜单链接关闭,锁定背景滚动
	(function(){
		var btn = document.querySelector('.mobile-menu-toggle');
		var nav = document.querySelector('.primary-nav');
		var overlay = document.querySelector('.nav-overlay');
		if ( ! btn || ! nav ) { return; }
		function set( open ) {
			nav.classList.toggle( 'is-open', open );
			if ( overlay ) { overlay.classList.toggle( 'is-open', open ); }
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			document.body.style.overflow = open ? 'hidden' : '';
		}
		btn.addEventListener( 'click', function(){
			set( ! nav.classList.contains( 'is-open' ) );
		} );
		if ( overlay ) { overlay.addEventListener( 'click', function(){ set( false ); } ); }
		document.addEventListener( 'keydown', function( e ){
			if ( e.key === 'Escape' && nav.classList.contains( 'is-open' ) ) { set( false ); }
		} );
		nav.addEventListener( 'click', function( e ){
			if ( e.target.closest( 'a' ) ) { set( false ); }
		} );
	})();
	</script>
</header>

<div class="nav-overlay" aria-hidden="true"></div>

<main id="main" class="site-main">
