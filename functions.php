<?php
/**
 * OneDong 主题功能函数
 *
 * @package OneDong
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // 禁止直接访问
}

define( 'ONEDONG_VERSION', '2.0.0' );
define( 'ONEDONG_DIR', get_template_directory() );
define( 'ONEDONG_URI', get_template_directory_uri() );

/**
 * 主题初始化:注册主题支持与菜单位置
 */
function onedong_setup() {
	// 自动生成 <title>
	add_theme_support( 'title-tag' );
	// 文章特色图片(缩略图)
	add_theme_support( 'post-thumbnails' );
	// RSS 订阅链接
	add_theme_support( 'automatic-feed-links' );
	// HTML5 语义化输出
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
			'navigation-widgets',
		)
	);
	// 自定义 Logo
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 64,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	// 响应式嵌入
	add_theme_support( 'responsive-embeds' );
	// 宽块对齐
	add_theme_support( 'align-wide' );
	// 块编辑器样式对齐(让后台编辑器预览贴合)
	add_editor_style( 'assets/css/base.css' );

	// 文章卡封面图专用尺寸(4:3 裁剪);老文章需 Regenerate Thumbnails 回填
	add_image_size( 'onedong-card', 600, 450, true );

	// 菜单位置
	register_nav_menus(
		array(
			'primary' => __( '顶部导航', 'onedong' ),
			'footer'  => __( '页脚导航', 'onedong' ),
		)
	);
}
add_action( 'after_setup_theme', 'onedong_setup' );

/**
 * 内容宽度
 */
function onedong_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'onedong_content_width', 820 );
}
add_action( 'after_setup_theme', 'onedong_content_width', 0 );

/**
 * 加载样式与脚本
 */
function onedong_scripts() {
	$ver = ONEDONG_VERSION;

	// 设计令牌 → 排版 → 代码 → 布局,按依赖顺序
	wp_enqueue_style( 'onedong-tokens', ONEDONG_URI . '/assets/css/tokens.css', array(), $ver );
	wp_enqueue_style( 'onedong-base', ONEDONG_URI . '/assets/css/base.css', array( 'onedong-tokens' ), $ver );
	wp_enqueue_style( 'onedong-code', ONEDONG_URI . '/assets/css/code.css', array( 'onedong-base' ), $ver );
	wp_enqueue_style( 'onedong-layout', ONEDONG_URI . '/assets/css/layout.css', array( 'onedong-base' ), $ver );

	// 主样式(承载主题头注释版本)
	wp_enqueue_style( 'onedong-style', get_stylesheet_uri(), array( 'onedong-layout' ), $ver );

	// 注入卡片摘要行数(默认 2;非默认时注入 CSS 变量覆盖)
	$excerpt_lines = (int) get_theme_mod( 'onedong_excerpt_lines', 2 );
	if ( 2 !== $excerpt_lines ) {
		wp_add_inline_style( 'onedong-layout', ':root{--excerpt-lines:' . $excerpt_lines . ';}' );
	}

	// 代码高亮 Prism.js —— 默认 CDN;若需离线/自托管,
	// 把 prism-core / autoloader 换成本地 assets/js/vendor/ 路径即可。
	wp_enqueue_style( 'onedong-prism', 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.min.css', array(), '1.29.0' );
	wp_enqueue_script( 'onedong-prism', 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-core.min.js', array(), '1.29.0', true );
	wp_enqueue_script( 'onedong-prism-autoloader', 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js', array( 'onedong-prism' ), '1.29.0', true );

	// 暗色切换
	wp_enqueue_script( 'onedong-toggle', ONEDONG_URI . '/assets/js/theme-toggle.js', array(), $ver, true );

	// 线程评论(若日后开启评论)
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'onedong_scripts' );

/**
 * 给古腾堡代码块(core/code)补 Prism 需要的 language-xxx 类。
 * 用户未显式指定语言时回退为 markup,保证能被高亮。
 *
 * @param string $block_content 已渲染的块 HTML。
 * @param array  $block         块数据。
 */
function onedong_code_block_language( $block_content, $block ) {
	if ( 'core/code' !== $block['blockName'] ) {
		return $block_content;
	}
	if ( preg_match( '/language-[\w-]+/', $block_content ) ) {
		return $block_content;
	}
	return preg_replace( '/<code\b/i', '<code class="language-markup"', $block_content, 1 );
}
add_filter( 'render_block', 'onedong_code_block_language', 10, 2 );

/**
 * 摘要省略符
 *
 * @param string $more 原省略符。
 */
function onedong_excerpt_more( $more ) {
	return '…';
}
add_filter( 'excerpt_more', 'onedong_excerpt_more' );

/**
 * 摘要长度
 *
 * @param int $length 原长度。
 */
function onedong_excerpt_length( $length ) {
	return 28;
}
add_filter( 'excerpt_length', 'onedong_excerpt_length' );

/**
 * 内联 SVG 图标(零依赖;符合「禁用 emoji 当图标」规范)。
 *
 * @param string $name 图标名:calendar / eye / chat / clock / hash / user。
 * @return string SVG 标记(未知名返回空串)。
 */
function onedong_get_icon( $name ) {
	$paths = array(
		'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
		'eye'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
		'chat'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'hash'     => '<path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * 输出内联 SVG 图标(echo 包装)。
 *
 * @param string $name 图标名。
 */
function onedong_icon( $name ) {
	echo onedong_get_icon( $name );
}

/**
 * 浏览计数:仅在单篇文章页、主查询、非管理员时 +1。
 * 用 IP+UA 指纹的 transient(6 小时窗口)防同一访客刷新重复计数。
 * 挂 template_redirect(而非 wp_head,后者会在 feed/REST 等场景误触发)。
 * 当后台关闭「显示浏览数」时,同时停止计数(无展示则无谓写库)。
 */
function onedong_bump_view_count() {
	if ( ! get_theme_mod( 'onedong_show_views', 1 ) ) {
		return;
	}
	if ( ! is_singular( 'post' ) || ! is_main_query() ) {
		return;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return; // 管理员预览不计
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$fwd = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '';
	$ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 64 ) : '';

	$key = 'onedong_viewed_' . $post_id . '_' . md5( $ip . '|' . $fwd . '|' . $ua );
	if ( get_transient( $key ) ) {
		return; // 窗口内已计过
	}
	set_transient( $key, 1, 6 * HOUR_IN_SECONDS );

	update_post_meta( $post_id, '_onedong_views', (int) get_post_meta( $post_id, '_onedong_views', true ) + 1 );
}
add_action( 'template_redirect', 'onedong_bump_view_count', 20 );

/**
 * 读取文章浏览数。
 *
 * @param int $post_id 文章 ID(缺省取当前文章)。
 * @return int
 */
function onedong_get_views( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	return (int) get_post_meta( $post_id, '_onedong_views', true );
}

/**
 * 输出文章字数与预计阅读时长(中文友好,约 300 字/分钟)。
 * 供 template-parts/content.php 卡片复用,对齐 Fuwari 演示的「字数 · 分钟」。
 */
function onedong_reading_stats() {
	$text    = wp_strip_all_tags( get_the_content() );
	$text    = preg_replace( '/\s+/u', '', $text );
	$count   = function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
	$minutes = max( 1, (int) round( $count / 300 ) );
	printf(
		/* translators: 1: 字数, 2: 分钟数 */
		esc_html__( '%1$s 字 · %2$s 分钟', 'onedong' ),
		number_format_i18n( $count ),
		$minutes
	);
}

/**
 * 输出文章元信息(日期 / 作者 / 分类 / 评论数)。
 * 供 template-parts/content.php 与 single.php 复用。
 */
function onedong_entry_meta() {
	echo '<div class="entry-meta">';

	printf(
		'<span class="posted-on">%1$s <a href="%2$s" rel="bookmark"><time datetime="%3$s">%4$s</time></a></span>',
		esc_html__( '发表于', 'onedong' ),
		esc_url( get_permalink() ),
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() )
	);

	printf(
		'<span class="byline">%1$s %2$s</span>',
		esc_html__( '作者', 'onedong' ),
		esc_html( get_the_author() )
	);

	if ( 'post' === get_post_type() ) {
		$categories = get_the_category_list( ', ' );
		if ( $categories ) {
			printf( '<span class="cat-links">%1$s %2$s</span>', esc_html__( '分类', 'onedong' ), $categories );
		}
		if ( get_theme_mod( 'onedong_show_views', 1 ) ) {
			printf(
				'<span class="views-link">%1$s %2$s</span>',
				onedong_get_icon( 'eye' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 受信任的内联主题 SVG
				esc_html( number_format_i18n( onedong_get_views() ) )
			);
		}
		if ( get_theme_mod( 'onedong_show_comments', 1 ) && ( comments_open() || get_comments_number() ) ) {
			printf(
				'<span class="comments-link"><a href="%1$s">%2$s</a></span>',
				esc_url( get_comments_link() ),
				esc_html( sprintf(
					/* translators: %s: 评论数量 */
					_n( '%s 条评论', '%s 条评论', get_comments_number(), 'onedong' ),
					number_format_i18n( get_comments_number() )
				) )
			);
		}
	}

	echo '</div>';
}

/**
 * 单篇文章页:上一篇 / 下一篇 导航(复用相邻文章卡片)。
 */
function onedong_post_nav() {
	$prev = get_previous_post();
	$next = get_next_post();

	if ( ! $prev && ! $next ) {
		return;
	}
	?>
	<nav class="post-nav" aria-label="<?php esc_attr_e( '文章导航', 'onedong' ); ?>">
		<?php if ( $prev ) : ?>
			<a class="nav-prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>" rel="prev">
				<span class="post-nav__label"><?php esc_html_e( '← 上一篇', 'onedong' ); ?></span>
				<span class="post-nav__title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
			</a>
		<?php endif; ?>
		<?php if ( $next ) : ?>
			<a class="nav-next" href="<?php echo esc_url( get_permalink( $next ) ); ?>" rel="next">
				<span class="post-nav__label"><?php esc_html_e( '下一篇 →', 'onedong' ); ?></span>
				<span class="post-nav__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
			</a>
		<?php endif; ?>
	</nav>
	<?php
}

/**
 * Customizer checkbox 净化。
 *
 * @param mixed $value 输入值。
 * @return bool
 */
function onedong_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * 头像来源净化。
 *
 * @param string $value 输入值。
 * @return string
 */
function onedong_sanitize_avatar_source( $value ) {
	$allowed = array( 'logo', 'gravatar', 'none' );
	return in_array( $value, $allowed, true ) ? $value : 'logo';
}

/**
 * Customizer:文章卡 / 侧栏作者卡 显示项(v2.0:已移除主色色相滑块,主色固定 suxing blue)。
 *
 * @param WP_Customize_Manager $wp_customize Customizer 实例。
 */
function onedong_customize_register( $wp_customize ) {
	// —— 文章卡设置 ——
	$wp_customize->add_section(
		'onedong_cards',
		array(
			'title'    => __( '文章卡', 'onedong' ),
			'priority' => 31,
		)
	);

	$card_toggles = array(
		'onedong_show_thumbnail' => __( '显示封面图', 'onedong' ),
		'onedong_show_views'     => __( '显示浏览数(同时控制计数)', 'onedong' ),
		'onedong_show_comments'  => __( '显示评论数', 'onedong' ),
		'onedong_show_reading'   => __( '显示字数 / 阅读时长', 'onedong' ),
		'onedong_show_tags'      => __( '显示标签', 'onedong' ),
	);
	foreach ( $card_toggles as $key => $label ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => 1,
				'sanitize_callback' => 'onedong_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $label,
				'section' => 'onedong_cards',
				'type'    => 'checkbox',
			)
		);
	}

	$wp_customize->add_setting(
		'onedong_excerpt_lines',
		array(
			'default'           => 2,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_excerpt_lines',
		array(
			'label'       => __( '卡片摘要行数', 'onedong' ),
			'description' => __( '超出部分截断(1-6 行)。', 'onedong' ),
			'section'     => 'onedong_cards',
			'type'        => 'range',
			'input_attrs' => array(
				'min'  => 1,
				'max'  => 6,
				'step' => 1,
			),
		)
	);

	// —— 侧栏作者卡设置 ——
	$wp_customize->add_section(
		'onedong_sidebar',
		array(
			'title'    => __( '侧栏作者卡', 'onedong' ),
			'priority' => 32,
		)
	);

	$wp_customize->add_setting(
		'onedong_show_author_stats',
		array(
			'default'           => 1,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_show_author_stats',
		array(
			'label'   => __( '显示文章 / 评论总数', 'onedong' ),
			'section' => 'onedong_sidebar',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'onedong_avatar_source',
		array(
			'default'           => 'logo',
			'sanitize_callback' => 'onedong_sanitize_avatar_source',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_avatar_source',
		array(
			'label'   => __( '头像来源', 'onedong' ),
			'section' => 'onedong_sidebar',
			'type'    => 'select',
			'choices' => array(
				'logo'     => __( '站点 Logo', 'onedong' ),
				'gravatar' => __( 'Gravatar(管理员邮箱)', 'onedong' ),
				'none'     => __( '不显示', 'onedong' ),
			),
		)
	);
}
add_action( 'customize_register', 'onedong_customize_register' );

/**
 * 顶部菜单兜底:未设置菜单时显示页面列表。
 *
 * @param array $args 原菜单参数。
 * @return array
 */
function onedong_primary_menu_fallback( $args ) {
	$defaults = array(
		'menu_class'  => 'menu',
		'container'   => false,
		'before'      => '<ul>',
		'after'       => '</ul>',
	);
	wp_page_menu( array_merge( $defaults, $args ) );
}

/**
 * 小工具区域(预留;当前主题不强制侧栏)。
 */
function onedong_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( '页脚小工具区', 'onedong' ),
			'id'            => 'footer-widgets',
			'description'   => __( '显示在页脚上方,可选。', 'onedong' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'onedong_widgets_init' );
