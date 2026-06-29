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

define( 'ONEDONG_VERSION', '2.5.17' );
define( 'ONEDONG_DIR', get_template_directory() );
define( 'ONEDONG_URI', get_template_directory_uri() );

// 功能模块(按需拆分,保持 functions.php 精简)
require_once ONEDONG_DIR . '/inc/moments.php'; // 朋友圈(onedong_moment)— v2.5.0

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
	// 朋友圈九宫格缩略图(1:1 正方形;老图需 Regenerate Thumbnails 回填)— v2.5.0
	add_image_size( 'onedong-moment-thumb', 300, 300, true );

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
 * 性能优化:资源预连接(gravatar + jsdelivr CDN)· v2.5.14
 */
function onedong_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://secure.gravatar.com' );
		$urls[] = array( 'href' => 'https://cdn.jsdelivr.net' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'onedong_resource_hints', 10, 2 );

/**
 * 性能:禁用 WP emoji(主题图标已全用内联 SVG)+ 清理 head 多余标签 · v2.5.14
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
add_filter( 'emoji_svg_url', '__return_false' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

/**
 * WebP:上传时自动转换 + 前端 picture 优先 · v2.5.15
 * 依赖 PHP GD 的 imagewebp;不支持则自动跳过(降级原图,无副作用)。
 */
add_filter( 'wp_generate_attachment_metadata', 'onedong_make_webp', 10, 2 );
function onedong_make_webp( $metadata, $attachment_id ) {
	if ( ! function_exists( 'imagewebp' ) && ! class_exists( 'Imagick' ) ) {
		return $metadata;
	}
	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! file_exists( $file ) ) {
		return $metadata;
	}
	$mime = get_post_mime_type( $attachment_id );
	if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/gif' ), true ) ) {
		return $metadata;
	}
	$targets = array( $file );
	if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
		foreach ( $metadata['sizes'] as $s ) {
			if ( ! empty( $s['file'] ) ) {
				$targets[] = path_join( dirname( $file ), $s['file'] );
			}
		}
	}
	$has = false;
	foreach ( $targets as $t ) {
		if ( onedong_webp_convert( $t ) ) {
			$has = true;
		}
	}
	if ( $has ) {
		update_post_meta( $attachment_id, '_onedong_has_webp', 1 );
	}
	return $metadata;
}

function onedong_webp_convert( $src ) {
	if ( ! file_exists( $src ) ) {
		return false;
	}
	$webp = $src . '.webp';
	if ( file_exists( $webp ) ) {
		return true;
	}
	$info = @getimagesize( $src );
	if ( ! $info ) {
		return false;
	}
	// 优先 Imagick(质量更好;WebP delegate 通常自带)
	if ( class_exists( 'Imagick' ) ) {
		try {
			$im = new Imagick( $src );
			$formats = method_exists( $im, 'queryFormats' ) ? $im->queryFormats() : array();
			if ( is_array( $formats ) && in_array( 'WEBP', $formats, true ) ) {
				$im->setImageFormat( 'webp' );
				$im->setImageCompressionQuality( 82 );
				$written = $im->writeImage( $webp );
				$im->clear();
				if ( $written && file_exists( $webp ) ) {
					return true;
				}
			} else {
				$im->clear();
			}
		} catch ( Exception $e ) {
			// 落到 GD 回退
		}
	}
	// 回退 GD
	if ( ! function_exists( 'imagewebp' ) ) {
		return false;
	}
	switch ( $info[2] ) {
		case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg( $src ); break;
		case IMAGETYPE_PNG:  $im = @imagecreatefrompng( $src ); break;
		case IMAGETYPE_GIF:  $im = @imagecreatefromgif( $src ); break;
		default: return false;
	}
	if ( ! $im ) {
		return false;
	}
	if ( IMAGETYPE_PNG === $info[2] ) {
		imagepalettetotruecolor( $im );
		imagealphablending( $im, true );
		imagesavealpha( $im, true );
	}
	$ok = imagewebp( $im, $webp, 82 );
	imagedestroy( $im );
	return $ok && file_exists( $webp );
}

add_filter( 'wp_get_attachment_image', 'onedong_webp_picture', 12, 2 );
function onedong_webp_picture( $html, $attachment_id ) {
	if ( empty( $html ) || false !== strpos( $html, '<picture' ) ) {
		return $html;
	}
	if ( ! get_post_meta( $attachment_id, '_onedong_has_webp', true ) ) {
		return $html;
	}
	$srcset = '';
	$src    = '';
	$sizes  = '';
	if ( preg_match( '/\ssrcset="([^"]+)"/i', $html, $m ) ) {
		$srcset = $m[1];
	} elseif ( preg_match( '/\ssrc="([^"]+)"/i', $html, $m ) ) {
		$src = $m[1];
	}
	if ( preg_match( '/\ssizes="([^"]+)"/i', $html, $m ) ) {
		$sizes = $m[1];
	}
	$webp_source = '';
	if ( $srcset ) {
		$parts      = array_map( 'trim', explode( ',', $srcset ) );
		$webp_parts = array();
		foreach ( $parts as $p ) {
			$webp_parts[] = $p . '.webp';
		}
		$webp_source = '<source srcset="' . esc_attr( implode( ', ', $webp_parts ) ) . '" type="image/webp"' . ( $sizes ? ' sizes="' . esc_attr( $sizes ) . '"' : '' ) . '>';
	} elseif ( $src ) {
		$webp_source = '<source srcset="' . esc_attr( $src ) . '.webp" type="image/webp">';
	}
	if ( ! $webp_source ) {
		return $html;
	}
	return '<picture>' . $webp_source . $html . '</picture>';
}

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

	// 注入站点宽度(默认 1280;非默认时覆盖 --site-width,clamp 到 1100–1600)
	$site_width = (int) get_theme_mod( 'onedong_site_width', 1280 );
	if ( 1280 !== $site_width ) {
		$site_width = max( 1100, min( 1600, $site_width ) );
		wp_add_inline_style( 'onedong-layout', ':root{--site-width:' . $site_width . 'px;}' );
	}

	// 代码高亮 Prism.js —— 默认 CDN;若需离线/自托管,
	// 把 prism-core / autoloader 换成本地 assets/js/vendor/ 路径即可。
	// 代码高亮 Prism.js —— 仅文章详情页加载(列表 / 首页无代码块,省 3 个 CDN 请求)· v2.5.14
	if ( is_singular( 'post' ) ) {
		wp_enqueue_style( 'onedong-prism', 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.min.css', array(), '1.29.0' );
		wp_enqueue_script( 'onedong-prism', 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-core.min.js', array(), '1.29.0', true );
		wp_enqueue_script( 'onedong-prism-autoloader', 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js', array( 'onedong-prism' ), '1.29.0', true );
	}

	// 暗色切换
	wp_enqueue_script( 'onedong-toggle', ONEDONG_URI . '/assets/js/theme-toggle.js', array(), $ver, true );

	// 滚动入场动画(渐进增强 · 零依赖)
	wp_enqueue_script( 'onedong-reveal', ONEDONG_URI . '/assets/js/reveal.js', array(), $ver, true );

	// 文章卡点赞(列表页;post_meta _onedong_likes + REST /onedong/v1/like)
	wp_enqueue_script( 'onedong-likes', ONEDONG_URI . '/assets/js/likes.js', array(), $ver, true );
	wp_localize_script(
		'onedong-likes',
		'onedongLike',
		array(
			'url'   => esc_url_raw( rest_url( 'onedong/v1/like' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		)
	);

	// 文章详情页脚本(阅读进度条 / 代码块复制 / TOC 当前段高亮)
	if ( is_singular( 'post' ) ) {
		wp_enqueue_script( 'onedong-single', ONEDONG_URI . '/assets/js/single.js', array(), $ver, true );
	}

	// 朋友圈(列表 / 详情):九宫格样式 + 图片 lightbox — v2.5.0
	if ( is_post_type_archive( 'onedong_moment' ) || is_singular( 'onedong_moment' ) ) {
		wp_enqueue_style( 'onedong-moments', ONEDONG_URI . '/assets/css/moments.css', array( 'onedong-layout' ), $ver );
		wp_enqueue_script( 'onedong-moments', ONEDONG_URI . '/assets/js/moments.js', array(), $ver, true );
		// 分享卡片:二维码(qrcodejs)+ 转图片(html2canvas),CDN;v2.5.5
		wp_enqueue_script( 'onedong-qrcode', 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js', array(), '1.0.0', true );
		wp_enqueue_script( 'onedong-html2canvas', 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js', array(), '1.4.1', true );
		wp_localize_script(
			'onedong-moments',
			'onedongMomentShare',
			array(
				'defaultThumb' => get_theme_file_uri( 'assets/img/default-thumb.png' ),
				'siteName'     => get_bloginfo( 'name' ),
			)
		);
	}

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
 * @param string $name 图标名:calendar / eye / chat / clock / hash / user / sun / moon / monitor / document / heart / type。
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
		'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
		'moon'     => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
		'monitor'  => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
		'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
		'heart'    => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
		'type'     => '<line x1="21" y1="6" x2="3" y2="6"/><line x1="15" y1="12" x2="3" y2="12"/><line x1="17" y1="18" x2="3" y2="18"/>',
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
 * 输出文章字数(中文友好:去标签去空白后 mb_strlen)。供文章卡「字数」数据项复用。
 */
function onedong_word_count() {
	$text  = wp_strip_all_tags( get_the_content() );
	$text  = preg_replace( '/\s+/u', '', $text );
	$count = function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
	echo esc_html( number_format_i18n( $count ) );
}

/**
 * 读取文章点赞数。
 *
 * @param int $post_id 文章 ID(缺省取当前)。
 * @return int
 */
function onedong_get_likes( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	return (int) get_post_meta( $post_id, '_onedong_likes', true );
}

/**
 * 注册点赞 REST 路由:POST /wp-json/onedong/v1/like { post_id }。
 * 匿名可赞(permission 开放);防刷由前端 localStorage 标记同一浏览器只赞一次。
 */
function onedong_register_likes_route() {
	register_rest_route(
		'onedong/v1',
		'/like',
		array(
			'methods'             => 'POST',
			'callback'            => 'onedong_handle_like',
			'permission_callback' => '__return_true',
			'args'                => array(
				'post_id' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
					'validate_callback' => function ( $v ) {
						return in_array( get_post_type( (int) $v ), array( 'post', 'onedong_moment' ), true );
					},
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'onedong_register_likes_route' );

/**
 * 点赞处理:post_meta _onedong_likes +1。
 *
 * @param WP_REST_Request $request REST 请求。
 * @return WP_REST_Response
 */
function onedong_handle_like( WP_REST_Request $request ) {
	$post_id = (int) $request['post_id'];
	$likes   = onedong_get_likes( $post_id ) + 1;
	update_post_meta( $post_id, '_onedong_likes', $likes );
	return rest_ensure_response( array( 'success' => true, 'likes' => $likes ) );
}

/**
 * 单篇文章输出 BlogPosting JSON-LD 结构化数据(SEO 富结果)。
 * 挂 wp_head,仅 is_singular('post') 输出。无特色图时图片字段回退站点图标。
 */
function onedong_article_schema() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return;
	}

	$author_id = (int) get_post_field( 'post_author', $post_id );

	// 图片:特色图 full,缺则站点图标
	$image_url = '';
	if ( has_post_thumbnail( $post_id ) ) {
		$img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
		if ( ! empty( $img[0] ) ) {
			$image_url = $img[0];
		}
	}
	if ( ! $image_url ) {
		$image_url = get_site_icon_url();
	}

	$description = wp_strip_all_tags( get_the_excerpt( $post_id ) );
	if ( '' === $description ) {
		$description = wp_strip_all_tags( get_the_title( $post_id ) );
	}

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => get_permalink( $post_id ),
		),
		'headline'         => wp_strip_all_tags( get_the_title( $post_id ) ),
		'description'      => $description,
		'datePublished'    => get_the_date( 'c', $post_id ),
		'dateModified'     => get_the_modified_date( 'c', $post_id ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', $author_id ),
			'url'   => get_author_posts_url( $author_id ),
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( $image_url ) {
		$schema['image'] = array(
			'@type' => 'ImageObject',
			'url'   => $image_url,
		);
	}

	$logo = get_site_icon_url();
	if ( $logo ) {
		$schema['publisher']['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => $logo,
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
}
add_action( 'wp_head', 'onedong_article_schema', 20 );

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
 * 单条评论 / pingback 渲染回调(wp_list_comments callback)。
 * 评论:头像 + 作者 + 日期 + 内容 + 回复/编辑;pingback/trackback 简化为一行。
 *
 * @param WP_Comment $comment 当前评论对象。
 * @param array      $args     wp_list_comments 参数。
 * @param int        $depth    嵌套深度。
 */
function onedong_comment_callback( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- 评论回调惯例,comment_text/author 等依赖全局

	// pingback / trackback:简化渲染
	if ( 'pingback' === $comment->comment_type || 'trackback' === $comment->comment_type ) {
		?>
		<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'pingback' ); ?>>
			<div class="pingback__body">
				<?php onedong_icon( 'chat' ); ?>
				<span class="pingback__text"><?php comment_author_link(); ?> · <?php echo esc_html( get_comment_date() ); ?></span>
			</div>
		<?php
		return;
	}
	?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'comment' ); ?>>
		<article class="comment__body" id="div-comment-<?php comment_ID(); ?>">
			<div class="comment__avatar">
				<?php
				echo get_avatar(
					$comment,
					empty( $args['avatar_size'] ) ? 44 : $args['avatar_size'],
					'',
					'',
					array( 'class' => 'avatar' )
				);
				?>
			</div>
			<div class="comment__content">
				<header class="comment__meta">
					<cite class="comment__author"><?php comment_author_link(); ?></cite>
					<time class="comment__date" datetime="<?php echo esc_attr( get_comment_time( 'c' ) ); ?>">
						<?php onedong_icon( 'calendar' ); ?>
						<?php echo esc_html( get_comment_date() . ' · ' . get_comment_time() ); ?>
					</time>
				</header>

				<?php if ( '0' === $comment->comment_approved ) : ?>
					<p class="comment__pending"><?php esc_html_e( '该评论正在等待审核。', 'onedong' ); ?></p>
				<?php endif; ?>

				<div class="comment__text"><?php comment_text(); ?></div>

				<footer class="comment__actions">
					<?php
					comment_reply_link(
						array_merge(
							$args,
							array(
								'add_below' => 'div-comment',
								'depth'     => $depth,
								'max_depth' => $args['max_depth'],
								'before'    => '<span class="comment-reply-wrap">',
								'after'     => '</span>',
							)
						)
					);
					edit_comment_link( __( '编辑', 'onedong' ), '<span class="comment-edit-wrap">', '</span>' );
					?>
				</footer>
			</div>
		</article>
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
	$allowed = array( 'logo', 'gravatar', 'custom', 'none' );
	return in_array( $value, $allowed, true ) ? $value : 'logo';
}

/**
 * 右侧栏模块顺序净化:逗号分隔 key,仅留合法值并去重;空则回退默认顺序。
 *
 * @param string $value 原始输入。
 * @return string
 */
function onedong_sanitize_order( $value ) {
	$valid = array( 'cats', 'tags', 'recent', 'popular', 'archive', 'text', 'image' );
	$parts = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
	$out   = array();
	foreach ( $parts as $p ) {
		if ( in_array( $p, $valid, true ) && ! in_array( $p, $out, true ) ) {
			$out[] = $p;
		}
	}
	if ( empty( $out ) ) {
		return 'cats,tags,recent,popular,archive,text,image';
	}
	return implode( ',', $out );
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

	// 默认缩略图(无特色图时用;留空回退主题内置 default-thumb.png)
	$wp_customize->add_setting(
		'onedong_default_thumb',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'onedong_default_thumb',
			array(
				'label'       => __( '默认缩略图', 'onedong' ),
				'description' => __( '文章无特色图时显示的默认封面;留空用主题内置占位图。', 'onedong' ),
				'section'     => 'onedong_cards',
			)
		)
	);

	// —— 侧栏作者卡设置 ——
	$wp_customize->add_section(
		'onedong_sidebar',
		array(
			'title'       => __( '左侧栏模块', 'onedong' ),
			'description' => __( '三栏布局左侧栏显示哪些模块(按固定顺序渲染:作者卡 → 文本 → 最新文章 → 热门文章)。', 'onedong' ),
			'priority'    => 32,
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
				'custom'   => __( '自定义上传', 'onedong' ),
				'none'     => __( '不显示', 'onedong' ),
			),
		)
	);

	// 自定义头像上传(头像来源选「自定义上传」时使用)— v2.5.0
	$wp_customize->add_setting(
		'onedong_avatar_custom',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'onedong_avatar_custom',
			array(
				'label'       => __( '自定义头像', 'onedong' ),
				'description' => __( '头像来源选「自定义上传」后,在此上传或粘贴图片地址。', 'onedong' ),
				'section'     => 'onedong_sidebar',
			)
		)
	);

	// —— 左侧栏模块开关(固定顺序渲染;作者卡默认开,其余默认关)——
	$left_toggles = array(
		'onedong_left_author'  => __( '显示作者卡', 'onedong' ),
		'onedong_left_text'    => __( '显示自定义文本块', 'onedong' ),
		'onedong_left_recent'  => __( '显示最新文章', 'onedong' ),
		'onedong_left_popular' => __( '显示热门文章(按浏览数)', 'onedong' ),
	);
	foreach ( $left_toggles as $key => $label ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => ( 'onedong_left_author' === $key ) ? 1 : 0,
				'sanitize_callback' => 'onedong_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $label,
				'section' => 'onedong_sidebar',
				'type'    => 'checkbox',
			)
		);
	}

	$wp_customize->add_setting(
		'onedong_left_textarea',
		array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_left_textarea',
		array(
			'label'       => __( '左侧自定义文本', 'onedong' ),
			'description' => __( '支持基础 HTML(粗体 / 链接 / 列表)。开启「自定义文本块」后显示。', 'onedong' ),
			'section'     => 'onedong_sidebar',
			'type'        => 'textarea',
		)
	);

	// 左侧栏:图片模块(开关 + 上传/URL + 标题 + 描述)
	$wp_customize->add_setting(
		'onedong_left_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_left_image',
		array(
			'label'   => __( '显示图片模块', 'onedong' ),
			'section' => 'onedong_sidebar',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'onedong_left_image_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'onedong_left_image_url',
			array(
				'label'       => __( '图片', 'onedong' ),
				'description' => __( '点击选择 / 上传,或直接粘贴图片地址。开启「显示图片模块」后显示。', 'onedong' ),
				'section'     => 'onedong_sidebar',
			)
		)
	);

	$wp_customize->add_setting(
		'onedong_left_image_title',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_left_image_title',
		array(
			'label'   => __( '图片标题', 'onedong' ),
			'section' => 'onedong_sidebar',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'onedong_left_image_desc',
		array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_left_image_desc',
		array(
			'label'       => __( '图片描述', 'onedong' ),
			'description' => __( '支持基础 HTML。', 'onedong' ),
			'section'     => 'onedong_sidebar',
			'type'        => 'textarea',
		)
	);

	// 左侧栏:图片点击跳转链接(留空则图片不可点击;v2.4.7)
	$wp_customize->add_setting(
		'onedong_left_image_link',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_left_image_link',
		array(
			'label'       => __( '图片点击跳转链接', 'onedong' ),
			'description' => __( '留空则图片不可点击;填写后点击图片(及标题)在新标签打开该链接。', 'onedong' ),
			'section'     => 'onedong_sidebar',
			'type'        => 'url',
		)
	);

	// —— 右侧栏模块(新 section;分类/标签默认开,其余默认关)——
	$wp_customize->add_section(
		'onedong_sidebar_right',
		array(
			'title'       => __( '右侧栏模块', 'onedong' ),
			'description' => __( '三栏布局右侧栏显示哪些模块(固定顺序:分类 → 标签 → 最新 → 热门 → 归档 → 文本)。', 'onedong' ),
			'priority'    => 33,
		)
	);

	$right_toggles = array(
		'onedong_right_cats'    => __( '显示分类', 'onedong' ),
		'onedong_right_tags'    => __( '显示标签云', 'onedong' ),
		'onedong_right_recent'  => __( '显示最新文章', 'onedong' ),
		'onedong_right_popular' => __( '显示热门文章(按浏览数)', 'onedong' ),
		'onedong_right_archive' => __( '显示归档(按月)', 'onedong' ),
		'onedong_right_text'    => __( '显示自定义文本块', 'onedong' ),
	);
	foreach ( $right_toggles as $key => $label ) {
		$is_default = ( 'onedong_right_cats' === $key || 'onedong_right_tags' === $key );
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $is_default ? 1 : 0,
				'sanitize_callback' => 'onedong_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $label,
				'section' => 'onedong_sidebar_right',
				'type'    => 'checkbox',
			)
		);
	}

	$wp_customize->add_setting(
		'onedong_right_textarea',
		array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_right_textarea',
		array(
			'label'       => __( '右侧自定义文本', 'onedong' ),
			'description' => __( '支持基础 HTML(粗体 / 链接 / 列表)。开启「自定义文本块」后显示。', 'onedong' ),
			'section'     => 'onedong_sidebar_right',
			'type'        => 'textarea',
		)
	);

	// 右侧栏:图片模块(开关 + 上传/URL + 标题 + 描述;与左侧栏一致)
	$wp_customize->add_setting(
		'onedong_right_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_right_image',
		array(
			'label'   => __( '显示图片模块', 'onedong' ),
			'section' => 'onedong_sidebar_right',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'onedong_right_image_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'onedong_right_image_url',
			array(
				'label'       => __( '图片', 'onedong' ),
				'description' => __( '点击选择 / 上传,或直接粘贴图片地址。开启「显示图片模块」后显示。', 'onedong' ),
				'section'     => 'onedong_sidebar_right',
			)
		)
	);

	$wp_customize->add_setting(
		'onedong_right_image_title',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_right_image_title',
		array(
			'label'   => __( '图片标题', 'onedong' ),
			'section' => 'onedong_sidebar_right',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'onedong_right_image_desc',
		array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_right_image_desc',
		array(
			'label'       => __( '图片描述', 'onedong' ),
			'description' => __( '支持基础 HTML。', 'onedong' ),
			'section'     => 'onedong_sidebar_right',
			'type'        => 'textarea',
		)
	);

	// 右侧栏:图片点击跳转链接(留空则图片不可点击;v2.4.7)
	$wp_customize->add_setting(
		'onedong_right_image_link',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_right_image_link',
		array(
			'label'       => __( '图片点击跳转链接', 'onedong' ),
			'description' => __( '留空则图片不可点击;填写后点击图片(及标题)在新标签打开该链接。', 'onedong' ),
			'section'     => 'onedong_sidebar_right',
			'type'        => 'url',
		)
	);

	// 右侧栏:模块显示顺序(逗号分隔 key;空/非法回退默认)
	$wp_customize->add_setting(
		'onedong_right_order',
		array(
			'default'           => 'cats,tags,recent,popular,archive,text,image',
			'sanitize_callback' => 'onedong_sanitize_order',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_right_order',
		array(
			'label'       => __( '模块显示顺序', 'onedong' ),
			'description' => __( '从上到下的模块顺序,逗号分隔。可选:cats(分类)/ tags(标签)/ recent(最新)/ popular(热门)/ archive(归档)/ text(文本)/ image(图片)。例:tags,cats,recent', 'onedong' ),
			'section'     => 'onedong_sidebar_right',
			'type'        => 'text',
		)
	);

	// —— 布局:站点宽度 + 模块文章条数 ——
	$wp_customize->add_section(
		'onedong_layout',
		array(
			'title'    => __( '布局', 'onedong' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'onedong_site_width',
		array(
			'default'           => 1280,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_site_width',
		array(
			'label'       => __( '站点宽度(px)', 'onedong' ),
			'description' => __( '三栏容器最大宽度,1100–1600,默认 1280。', 'onedong' ),
			'section'     => 'onedong_layout',
			'type'        => 'range',
			'input_attrs' => array(
				'min'  => 1100,
				'max'  => 1600,
				'step' => 20,
			),
		)
	);

	$wp_customize->add_setting(
		'onedong_widget_count',
		array(
			'default'           => 5,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_widget_count',
		array(
			'label'       => __( '侧栏文章列表条数', 'onedong' ),
			'description' => __( '最新 / 热门文章模块显示的条数(3–10)。', 'onedong' ),
			'section'     => 'onedong_layout',
			'type'        => 'range',
			'input_attrs' => array(
				'min'  => 3,
				'max'  => 10,
				'step' => 1,
			),
		)
	);

	// —— 页脚:版权 + 备案号 ——
	$wp_customize->add_section(
		'onedong_footer',
		array(
			'title'    => __( '页脚', 'onedong' ),
			'priority' => 34,
		)
	);

	$wp_customize->add_setting(
		'onedong_footer_copyright',
		array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_footer_copyright',
		array(
			'label'       => __( '版权信息', 'onedong' ),
			'description' => __( '自定义页脚版权;留空显示默认「© 年份 站点 · OneDong 主题」。支持基础 HTML。', 'onedong' ),
			'section'     => 'onedong_footer',
			'type'        => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'onedong_footer_icp',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_footer_icp',
		array(
			'label'       => __( 'ICP 备案号', 'onedong' ),
			'description' => __( '如「沪ICP备12345678号」;留空不显示。', 'onedong' ),
			'section'     => 'onedong_footer',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'onedong_footer_icp_url',
		array(
			'default'           => 'https://beian.miit.gov.cn',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_footer_icp_url',
		array(
			'label'       => __( '备案号链接网址', 'onedong' ),
			'description' => __( '点击备案号跳转的网址,默认工信部 beian.miit.gov.cn。', 'onedong' ),
			'section'     => 'onedong_footer',
			'type'        => 'url',
		)
	);

	// —— 文章详情页:TOC 目录 / 相关文章 ——
	$wp_customize->add_section(
		'onedong_single',
		array(
			'title'    => __( '文章详情页', 'onedong' ),
			'priority' => 35,
		)
	);

	$wp_customize->add_setting(
		'onedong_show_toc',
		array(
			'default'           => 1,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_show_toc',
		array(
			'label'       => __( '显示文章目录(TOC)', 'onedong' ),
			'description' => __( '正文前自动生成 h2/h3 目录(少于 2 个标题时不显示)。', 'onedong' ),
			'section'     => 'onedong_single',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'onedong_show_related',
		array(
			'default'           => 1,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_show_related',
		array(
			'label'   => __( '显示相关文章', 'onedong' ),
			'section' => 'onedong_single',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'onedong_related_count',
		array(
			'default'           => 4,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_related_count',
		array(
			'label'       => __( '相关文章条数', 'onedong' ),
			'description' => __( '2–8 篇。', 'onedong' ),
			'section'     => 'onedong_single',
			'type'        => 'range',
			'input_attrs' => array(
				'min'  => 2,
				'max'  => 8,
				'step' => 1,
			),
		)
	);
}
add_action( 'customize_register', 'onedong_customize_register' );

/**
 * 侧栏模块:最新文章列表(条数取 onedong_widget_count)。
 * 复用 onedong-card 之外的小缩略图;无特色图显示占位图标。
 */
function onedong_widget_recent_posts() {
	$count = (int) get_theme_mod( 'onedong_widget_count', 5 );
	if ( $count < 1 ) {
		$count = 5;
	}
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => $count,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	if ( ! $q->have_posts() ) {
		return;
	}
	?>
	<section class="widget widget-posts">
		<h2 class="widget-title"><?php esc_html_e( '最新文章', 'onedong' ); ?></h2>
		<ul class="widget-posts__list">
			<?php
			while ( $q->have_posts() ) :
				$q->the_post();
				?>
				<li class="widget-posts__item">
					<a class="widget-posts__thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( array( 72, 54 ) ); ?>
						<?php else : ?>
							<span class="widget-posts__thumb-ph"><?php onedong_icon( 'hash' ); ?></span>
						<?php endif; ?>
					</a>
					<div class="widget-posts__body">
						<a class="widget-posts__title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<time class="widget-posts__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					</div>
				</li>
				<?php
			endwhile;
			?>
		</ul>
	</section>
	<?php
	wp_reset_postdata();
}

/**
 * 侧栏模块:热门文章(按浏览数 _onedong_views 倒序;复用浏览计数 meta)。
 * 无浏览记录的文章不出现(老文章需产生浏览后才上榜)。
 */
function onedong_widget_popular_posts() {
	$count = (int) get_theme_mod( 'onedong_widget_count', 5 );
	if ( $count < 1 ) {
		$count = 5;
	}
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => $count,
			'meta_key'            => '_onedong_views',
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	if ( ! $q->have_posts() ) {
		return;
	}
	?>
	<section class="widget widget-posts">
		<h2 class="widget-title"><?php esc_html_e( '热门文章', 'onedong' ); ?></h2>
		<ul class="widget-posts__list">
			<?php
			while ( $q->have_posts() ) :
				$q->the_post();
				?>
				<li class="widget-posts__item">
					<a class="widget-posts__thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( array( 72, 54 ) ); ?>
						<?php else : ?>
							<span class="widget-posts__thumb-ph"><?php onedong_icon( 'eye' ); ?></span>
						<?php endif; ?>
					</a>
					<div class="widget-posts__body">
						<a class="widget-posts__title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<span class="widget-posts__date"><?php onedong_icon( 'eye' ); ?> <?php echo esc_html( number_format_i18n( onedong_get_views() ) ); ?></span>
					</div>
				</li>
				<?php
			endwhile;
			?>
		</ul>
	</section>
	<?php
	wp_reset_postdata();
}

/**
 * 侧栏模块:归档(按月,带文章数,近 12 个月)。
 */
function onedong_widget_archive() {
	?>
	<section class="widget widget-archive">
		<h2 class="widget-title"><?php esc_html_e( '归档', 'onedong' ); ?></h2>
		<ul class="widget-archive__list">
			<?php
			wp_get_archives(
				array(
					'type'            => 'monthly',
					'limit'           => 12,
					'show_post_count' => true,
				)
			);
			?>
		</ul>
	</section>
	<?php
}

/**
 * 侧栏模块:自定义文本块(读 onedong_{side}_textarea,wp_kses_post 输出)。
 *
 * @param string $side 'left' 或 'right'。
 */
function onedong_widget_text( $side ) {
	$text = get_theme_mod( "onedong_{$side}_textarea", '' );
	if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
		return;
	}
	?>
	<section class="widget widget-text">
		<?php echo wp_kses_post( $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 已 wp_kses_post 校验 ?>
	</section>
	<?php
}

/**
 * 侧栏模块:分类(带文章数,按文章数倒序,前 12)。
 */
function onedong_widget_cats() {
	$cats = get_categories(
		array(
			'orderby' => 'count',
			'order'   => 'DESC',
			'number'  => 12,
		)
	);
	if ( empty( $cats ) || is_wp_error( $cats ) ) {
		return;
	}
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
	<?php
}

/**
 * 侧栏模块:标签云(药丸)。
 */
function onedong_widget_tags() {
	$tags = get_tags();
	if ( empty( $tags ) || is_wp_error( $tags ) ) {
		return;
	}
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
	<?php
}

/**
 * 侧栏模块:图片(上传 / URL)+ 标题 + 描述。左/右栏共用,$side 读对应 setting。
 *
 * @param string $side 'left' 或 'right'。
 */
function onedong_widget_image( $side = 'left' ) {
	$url   = get_theme_mod( "onedong_{$side}_image_url", '' );
	$title = get_theme_mod( "onedong_{$side}_image_title", '' );
	$desc  = get_theme_mod( "onedong_{$side}_image_desc", '' );
	$link  = get_theme_mod( "onedong_{$side}_image_link", '' );
	if ( ! $url ) {
		return;
	}
	?>
	<section class="widget widget-image">
		<?php if ( $link ) : ?>
			<a class="widget-image__link" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer">
		<?php endif; ?>
		<img class="widget-image__img" src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( $title ) ); ?>" loading="lazy" decoding="async">
		<?php if ( $title ) : ?>
			<h2 class="widget-image__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>
		<?php if ( $link ) : ?>
			</a>
		<?php endif; ?>
		<?php if ( trim( wp_strip_all_tags( $desc ) ) ) : ?>
			<div class="widget-image__desc"><?php echo wp_kses_post( $desc ); ?></div>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * 右侧栏模块分发(按 key 渲染对应模块;内部按各自开关判断是否输出)。
 *
 * @param string $k cats / tags / recent / popular / archive / text。
 */
function onedong_render_right_module( $k ) {
	switch ( $k ) {
		case 'cats':
			if ( get_theme_mod( 'onedong_right_cats', 1 ) ) {
				onedong_widget_cats();
			}
			break;
		case 'tags':
			if ( get_theme_mod( 'onedong_right_tags', 1 ) ) {
				onedong_widget_tags();
			}
			break;
		case 'recent':
			if ( get_theme_mod( 'onedong_right_recent', 0 ) ) {
				onedong_widget_recent_posts();
			}
			break;
		case 'popular':
			if ( get_theme_mod( 'onedong_right_popular', 0 ) ) {
				onedong_widget_popular_posts();
			}
			break;
		case 'archive':
			if ( get_theme_mod( 'onedong_right_archive', 0 ) ) {
				onedong_widget_archive();
			}
			break;
		case 'text':
			if ( get_theme_mod( 'onedong_right_text', 0 ) ) {
				onedong_widget_text( 'right' );
			}
			break;
		case 'image':
			if ( get_theme_mod( 'onedong_right_image', 0 ) ) {
				onedong_widget_image( 'right' );
			}
			break;
	}
}

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

/**
 * 给正文 h2/h3 注入锚点 id,并收集到全局 $onedong_toc 供目录渲染。
 * 挂 the_content(仅 single post 主循环)。已有 id 沿用;中文标题用 sanitize_title 生成 slug 并去重。
 *
 * @param string $content 正文 HTML。
 * @return string
 */
function onedong_inject_heading_ids( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	global $onedong_toc;
	$onedong_toc = array();
	$used        = array();

	$content = preg_replace_callback(
		'/<h([23])\b([^>]*)>(.*?)<\/h\1>/is',
		function ( $m ) use ( &$onedong_toc, &$used ) {
			$level = (int) $m[1];
			$attrs = $m[2];
			$inner = $m[3];
			$text  = trim( wp_strip_all_tags( $inner ) );

			if ( preg_match( '/\bid=["\']([^"\']+)["\']/i', $attrs, $idm ) ) {
				$id = $idm[1];
			} else {
				$id = sanitize_title( $text );
				if ( '' === $id ) {
					$id = 'section';
				}
				$base = $id;
				$i    = 2;
				while ( in_array( $id, $used, true ) ) {
					$id = $base . '-' . $i;
					$i++;
				}
			}
			$used[]        = $id;
			$onedong_toc[] = array(
				'level' => $level,
				'id'    => $id,
				'text'  => $text,
			);

			if ( preg_match( '/\bid=/i', $attrs ) ) {
				return $m[0]; // 已有 id,原样返回
			}
			return '<h' . $level . ' id="' . esc_attr( $id ) . '"' . $attrs . '>';
		},
		$content
	);

	return $content;
}
add_filter( 'the_content', 'onedong_inject_heading_ids', 20 );

/**
 * 渲染文章目录(TOC)。读取 onedong_inject_heading_ids 收集的全局 $onedong_toc。
 * 受 Customizer「显示文章目录」开关控制;少于 2 个标题不显示。
 */
function onedong_toc() {
	if ( ! get_theme_mod( 'onedong_show_toc', 1 ) ) {
		return;
	}
	global $onedong_toc;
	if ( empty( $onedong_toc ) || count( $onedong_toc ) < 2 ) {
		return;
	}
	?>
	<nav class="toc" aria-label="<?php esc_attr_e( '文章目录', 'onedong' ); ?>">
		<div class="toc__header">
			<?php onedong_icon( 'document' ); ?>
			<span class="toc__title"><?php esc_html_e( '目录', 'onedong' ); ?></span>
		</div>
		<ol class="toc__list">
			<?php foreach ( $onedong_toc as $h ) : ?>
				<li class="toc__item toc__item--l<?php echo (int) $h['level']; ?>">
					<a href="#<?php echo esc_attr( $h['id'] ); ?>"><?php echo esc_html( $h['text'] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * 相关文章:按当前文章分类(不足按标签)取若干篇;post__not_in 排除自身。
 * 受 Customizer「显示相关文章」开关与条数控制;仅在 single post 调用。
 */
function onedong_related_posts() {
	if ( ! get_theme_mod( 'onedong_show_related', 1 ) ) {
		return;
	}
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return;
	}
	$count = (int) get_theme_mod( 'onedong_related_count', 4 );
	if ( $count < 2 ) {
		$count = 4;
	}

	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => $count,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	$cats = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
	if ( $cats ) {
		$args['category__in'] = $cats;
	} else {
		$tags = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
		if ( $tags ) {
			$args['tag__in'] = $tags;
		}
	}

	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		return;
	}
	?>
	<section class="related-posts">
		<h2 class="related-posts__title"><?php esc_html_e( '相关文章', 'onedong' ); ?></h2>
		<ul class="related-posts__list">
			<?php
			while ( $q->have_posts() ) :
				$q->the_post();
				?>
				<li class="related-posts__item">
					<a class="related-posts__link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<span class="related-posts__thumb"><?php the_post_thumbnail( array( 120, 90 ) ); ?></span>
						<?php endif; ?>
						<span class="related-posts__body">
							<span class="related-posts__name"><?php the_title(); ?></span>
							<time class="related-posts__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</span>
					</a>
				</li>
				<?php
			endwhile;
			?>
		</ul>
	</section>
	<?php
	wp_reset_postdata();
}
