<?php
/**
 * 全站顶部公告条模块
 *
 * 紧贴 header 下方渲染一条可关闭的站点公告(标题 + 正文 + 可选按钮)。
 * 配置走 Customizer(section: onedong_announcement);关闭状态按「内容哈希」存
 * localStorage,公告内容一改即自动重新出现。仅用设计 token,深浅色 / 换肤自动跟随。
 *
 * @package OneDong
 * @since 6.0.67
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // 禁止直接访问
}

/**
 * 语气 / 配色净化:白名单,非法回退 info。
 *
 * @param string $value 输入值。
 * @return string
 */
function onedong_announcement_sanitize_tone( $value ) {
	$allowed = array( 'info', 'primary', 'warn', 'success' );
	return in_array( $value, $allowed, true ) ? $value : 'info';
}

/**
 * 公告是否有可显示内容(标题或正文任一非空)。
 *
 * @return bool
 */
function onedong_announcement_has_content() {
	$title = trim( (string) get_theme_mod( 'onedong_announcement_title', '' ) );
	$text  = trim( (string) get_theme_mod( 'onedong_announcement_text', '' ) );
	return ( '' !== $title || '' !== $text );
}

/**
 * Customizer:公告条设置。
 *
 * @param WP_Customize_Manager $wp_customize Customizer 实例。
 */
function onedong_announcement_customize( $wp_customize ) {
	$wp_customize->add_section(
		'onedong_announcement',
		array(
			'title'       => __( '公告条', 'onedong' ),
			'description' => __( '全站顶部(header 下方)显示一条公告。开启开关且标题或正文任一非空时显示。', 'onedong' ),
			'priority'    => 30,
		)
	);

	// 总开关
	$wp_customize->add_setting(
		'onedong_announcement_enable',
		array(
			'default'           => 0,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_announcement_enable',
		array(
			'label'   => __( '启用公告条', 'onedong' ),
			'section' => 'onedong_announcement',
			'type'    => 'checkbox',
		)
	);

	// 语气 / 配色
	$wp_customize->add_setting(
		'onedong_announcement_tone',
		array(
			'default'           => 'info',
			'sanitize_callback' => 'onedong_announcement_sanitize_tone',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_announcement_tone',
		array(
			'label'   => __( '语气 / 配色', 'onedong' ),
			'section' => 'onedong_announcement',
			'type'    => 'select',
			'choices' => array(
				'info'    => __( '信息(主题蓝)', 'onedong' ),
				'primary' => __( '主题色', 'onedong' ),
				'warn'    => __( '警示(黄)', 'onedong' ),
				'success' => __( '成功(绿)', 'onedong' ),
			),
		)
	);

	// 标题
	$wp_customize->add_setting(
		'onedong_announcement_title',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_announcement_title',
		array(
			'label'       => __( '标题', 'onedong' ),
			'description' => __( '可留空。加粗显示在正文前。', 'onedong' ),
			'section'     => 'onedong_announcement',
			'type'        => 'text',
		)
	);

	// 正文
	$wp_customize->add_setting(
		'onedong_announcement_text',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_announcement_text',
		array(
			'label'   => __( '正文', 'onedong' ),
			'section' => 'onedong_announcement',
			'type'    => 'textarea',
		)
	);

	// 按钮文字
	$wp_customize->add_setting(
		'onedong_announcement_btn_text',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_announcement_btn_text',
		array(
			'label'       => __( '按钮文字', 'onedong' ),
			'description' => __( '留空则不显示按钮。', 'onedong' ),
			'section'     => 'onedong_announcement',
			'type'        => 'text',
		)
	);

	// 按钮链接
	$wp_customize->add_setting(
		'onedong_announcement_btn_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_announcement_btn_url',
		array(
			'label'   => __( '按钮链接', 'onedong' ),
			'section' => 'onedong_announcement',
			'type'    => 'url',
		)
	);

	// 新标签打开
	$wp_customize->add_setting(
		'onedong_announcement_btn_blank',
		array(
			'default'           => 0,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_announcement_btn_blank',
		array(
			'label'   => __( '按钮在新标签打开', 'onedong' ),
			'section' => 'onedong_announcement',
			'type'    => 'checkbox',
		)
	);

	// 允许关闭
	$wp_customize->add_setting(
		'onedong_announcement_dismissible',
		array(
			'default'           => 1,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_announcement_dismissible',
		array(
			'label'       => __( '允许访客关闭', 'onedong' ),
			'description' => __( '关闭后记住(基于内容);公告内容修改后自动重新出现。', 'onedong' ),
			'section'     => 'onedong_announcement',
			'type'        => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'onedong_announcement_customize' );

/**
 * 按需加载公告条样式(仅启用且有内容时)。
 */
function onedong_announcement_assets() {
	if ( ! get_theme_mod( 'onedong_announcement_enable', 0 ) || ! onedong_announcement_has_content() ) {
		return;
	}
	wp_enqueue_style(
		'onedong-announcement',
		ONEDONG_URI . '/assets/css/announcement.css',
		array( 'onedong-layout' ),
		ONEDONG_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'onedong_announcement_assets' );

/**
 * 渲染公告条。由 header.php 在 header 与 main 之间调用。
 */
function onedong_announcement_bar() {
	if ( ! get_theme_mod( 'onedong_announcement_enable', 0 ) || ! onedong_announcement_has_content() ) {
		return;
	}

	$title       = trim( (string) get_theme_mod( 'onedong_announcement_title', '' ) );
	$text        = trim( (string) get_theme_mod( 'onedong_announcement_text', '' ) );
	$btn_text    = trim( (string) get_theme_mod( 'onedong_announcement_btn_text', '' ) );
	$btn_url     = get_theme_mod( 'onedong_announcement_btn_url', '' );
	$btn_blank   = (bool) get_theme_mod( 'onedong_announcement_btn_blank', 0 );
	$tone        = onedong_announcement_sanitize_tone( get_theme_mod( 'onedong_announcement_tone', 'info' ) );
	$dismissible = (bool) get_theme_mod( 'onedong_announcement_dismissible', 1 );
	$has_btn     = ( '' !== $btn_text && '' !== $btn_url );

	// 内容指纹:任一字段变化 → key 变 → 已关闭状态失效,公告重新出现。
	$key = substr( md5( $title . '|' . $text . '|' . $btn_text . '|' . $btn_url . '|' . $tone ), 0, 10 );
	?>
	<section class="site-announcement" role="region" aria-label="<?php esc_attr_e( '站点公告', 'onedong' ); ?>" data-tone="<?php echo esc_attr( $tone ); ?>" data-key="<?php echo esc_attr( $key ); ?>"<?php echo $dismissible ? ' data-dismissible="1"' : ''; ?>>
		<div class="site-announcement__inner">
			<div class="site-announcement__card">
				<span class="site-announcement__icon" aria-hidden="true"><?php onedong_icon( 'info' ); ?></span>
				<div class="site-announcement__body">
					<?php if ( '' !== $title ) : ?>
						<strong class="site-announcement__title"><?php echo esc_html( $title ); ?></strong>
					<?php endif; ?>
					<?php if ( '' !== $text ) : ?>
						<span class="site-announcement__text"><?php echo esc_html( $text ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $has_btn ) : ?>
					<a class="site-announcement__btn" href="<?php echo esc_url( $btn_url ); ?>"<?php echo $btn_blank ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $btn_text ); ?></a>
				<?php endif; ?>
				<?php if ( $dismissible ) : ?>
					<button class="site-announcement__close" type="button" aria-label="<?php esc_attr_e( '关闭公告', 'onedong' ); ?>">&times;</button>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<script>
	// 公告条:anti-flash 预隐藏 + 关闭记忆(内容哈希)。与 header 汉堡/主题切换同款内联约定,零额外请求。
	( function () {
		var el = document.currentScript.previousElementSibling;
		if ( ! el || ! el.classList || ! el.classList.contains( 'site-announcement' ) ) { return; }
		var STORE = 'onedong-announce';
		var key = el.getAttribute( 'data-key' );
		var dismissible = el.getAttribute( 'data-dismissible' ) === '1';
		// 绘制前:已关闭且内容未变 → 立即隐藏,无闪烁。
		try { if ( dismissible && localStorage.getItem( STORE ) === key ) { el.classList.add( 'is-dismissed' ); } } catch ( e ) {}
		if ( ! dismissible ) { return; }
		var btn = el.querySelector( '.site-announcement__close' );
		if ( ! btn ) { return; }
		btn.addEventListener( 'click', function () {
			try { localStorage.setItem( STORE, key ); } catch ( e ) {}
			var done = function () { el.classList.add( 'is-dismissed' ); el.classList.remove( 'is-closing' ); };
			var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			if ( reduce ) { done(); return; }
			el.classList.add( 'is-closing' );
			el.addEventListener( 'transitionend', done, { once: true } );
			setTimeout( done, 400 ); // 兜底:过渡未触发时也能移除
		} );
	} )();
	</script>
	<?php
}
