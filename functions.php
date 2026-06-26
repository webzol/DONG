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

define( 'ONEDONG_VERSION', '1.0.0' );
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

	// 注入主色相(Customizer 可覆盖默认 250)
	$hue = (int) get_theme_mod( 'onedong_hue', 250 );
	if ( 250 !== $hue ) {
		wp_add_inline_style( 'onedong-tokens', ':root{--hue:' . $hue . ';}' );
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
		if ( comments_open() || get_comments_number() ) {
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
 * Customizer:主色色相调节。
 *
 * @param WP_Customize_Manager $wp_customize Customizer 实例。
 */
function onedong_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'onedong_theme',
		array(
			'title'    => __( 'OneDong 主题', 'onedong' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'onedong_hue',
		array(
			'default'           => 250,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'onedong_hue',
		array(
			'label'       => __( '主色色相', 'onedong' ),
			'description' => __( '示例:200 青绿、250 蓝紫(默认)、310 紫粉、345 粉红。', 'onedong' ),
			'section'     => 'onedong_theme',
			'type'        => 'range',
			'input_attrs' => array(
				'min'  => 0,
				'max'  => 360,
				'step' => 1,
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
