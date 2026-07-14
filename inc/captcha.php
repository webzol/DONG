<?php
/**
 * 评论图形验证码模块(自包含 · GD 图片 · 无插件/无第三方 API key)
 * --------------------------------------------------------------
 * 工作流程:
 *   1. admin-post.php?action=onedong_captcha 生成扰乱 PNG,答案 hash 存 transient(以
 *      wp_hash(token) 为 key),token 写入 HttpOnly / SameSite=Lax cookie。每取一次图
 *      换一个 token、覆盖 cookie。
 *   2. comment_form_submit_field 过滤器把验证码字段 prepend 到"提交"按钮前。
 *   3. pre_comment_on_post 校验:cookie token → transient → hash_equals 比对,校验后
 *      立即删 transient(单次有效)。失败则暂存评论草稿,重定向回 #respond 并保留正文。
 *
 * 状态不依赖 PHP session(很多主机禁用),用 cookie + transient 跨请求绑定访客。
 * 缺 GD 时本模块整体 return,不注册任何钩子,评论照常工作。
 *
 * @package OneDong
 * @since   6.0.62
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // 禁止直接访问
}

// —— 优雅降级:缺 GD 则整个模块自废武功,评论不受影响 ——
if ( ! extension_loaded( 'gd' )
	|| ! function_exists( 'imagecreatetruecolor' )
	|| ! function_exists( 'imagepng' )
	|| ! function_exists( 'imagecolorallocate' )
	|| ! function_exists( 'imagerotate' ) ) {
	return;
}

define( 'ONEDONG_CAPTCHA_COOKIE', 'onedong_captcha' );
define( 'ONEDONG_CAPTCHA_TTL', 10 * MINUTE_IN_SECONDS );   // 答案有效期
define( 'ONEDONG_CAPTCHA_COOKIE_TTL', 30 * MINUTE_IN_SECONDS ); // cookie 比答案长,过期只会强制重取图
define( 'ONEDONG_CAPTCHA_DRAFT_TTL', 5 * MINUTE_IN_SECONDS );   // 失败草稿保留时长

/**
 * 统一开关判断:GD 可用 + Customizer 开关 + 登录用户按需豁免。
 * 渲染与校验两条路径都先调它,确保表单不出现验证码时就一定不校验,反之亦然。
 *
 * @return bool
 */
function onedong_captcha_active() {
	if ( ! get_theme_mod( 'onedong_comment_captcha', 1 ) ) {
		return false;
	}
	if ( is_user_logged_in() && get_theme_mod( 'onedong_captcha_skip_logged', 1 ) ) {
		return false;
	}
	return true;
}

/**
 * 取验证码图片 URL(admin-post.php 端点 + 随机 cache-buster)。
 *
 * @param string $nonce_buster 任意随机串,仅用于让浏览器不缓存。
 * @return string
 */
function onedong_captcha_url( $nonce_buster = '' ) {
	$url = add_query_arg( 'action', 'onedong_captcha', admin_url( 'admin-post.php' ) );
	if ( '' !== $nonce_buster ) {
		$url = add_query_arg( 'v', $nonce_buster, $url );
	}
	return $url;
}

/**
 * 生成 4 位去歧义字符(去掉 0/O/1/l/I),大小写不敏感比对。
 *
 * @return string
 */
function onedong_captcha_code() {
	$alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
	$len      = strlen( $alphabet );
	$code     = '';
	for ( $i = 0; $i < 4; $i++ ) {
		$code .= $alphabet[ random_int( 0, $len - 1 ) ];
	}
	return $code;
}

/**
 * 画一张扰乱 PNG。不使用透明度,而是让字符临时画布的背景色与主画布完全一致,
 * 旋转后的缝隙填同色,无缝拼接(避免 GD 透明叠加的坑)。
 *
 * @param string $code 4 位验证码。
 * @return GdImage
 */
function onedong_captcha_render_image( $code ) {
	$w   = 150;
	$h   = 42;
	$bg  = array( 243, 244, 246 ); // 浅灰,两种主题下都可读
	$im  = imagecreatetruecolor( $w, $h );
	$col = imagecolorallocate( $im, $bg[0], $bg[1], $bg[2] );
	imagefilledrectangle( $im, 0, 0, $w, $h, $col );

	$palette = array(
		imagecolorallocate( $im, 20, 80, 160 ),   // 蓝
		imagecolorallocate( $im, 30, 110, 70 ),   // 绿
		imagecolorallocate( $im, 160, 50, 40 ),   // 红
		imagecolorallocate( $im, 90, 40, 140 ),   // 紫
		imagecolorallocate( $im, 25, 25, 25 ),    // 近黑
		imagecolorallocate( $im, 180, 90, 0 ),    // 橙
	);

	// 干扰点
	for ( $i = 0; $i < 140; $i++ ) {
		imagesetpixel( $im, random_int( 0, $w - 1 ), random_int( 0, $h - 1 ), $palette[ random_int( 0, 5 ) ] );
	}

	// 每个字符单独画在 26x30 小画布上,旋转后贴回主画布(同色背景无缝)。
	$len  = strlen( $code );
	$slot = (int) floor( $w / $len );
	for ( $i = 0; $i < $len; $i++ ) {
		$tmp = imagecreatetruecolor( 26, 30 );
		$tbg = imagecolorallocate( $tmp, $bg[0], $bg[1], $bg[2] );
		imagefilledrectangle( $tmp, 0, 0, 26, 30, $tbg );
		imagestring( $tmp, 5, 5, 6, $code[ $i ], $palette[ random_int( 0, 5 ) ] );

		$rot = imagerotate( $tmp, random_int( -22, 22 ), $tbg );
		$rw  = imagesx( $rot );
		$rh  = imagesy( $rot );
		$dx  = $i * $slot + (int) ( ( $slot - $rw ) / 2 );
		$dy  = (int) ( ( $h - $rh ) / 2 );
		imagecopy( $im, $rot, $dx, $dy, 0, 0, $rw, $rh );
		imagedestroy( $tmp );
		imagedestroy( $rot );
	}

	// 干扰线(画在字符之上,增加 OCR 难度)
	for ( $i = 0; $i < 3; $i++ ) {
		imageline( $im, random_int( 0, $w ), random_int( 0, $h ), random_int( 0, $w ), random_int( 0, $h ), $palette[ random_int( 0, 5 ) ] );
	}

	return $im;
}

/**
 * 画一张仅含提示文字的占位图(限流命中时用)。
 *
 * @param string $text
 */
function onedong_captcha_render_text_image( $text ) {
	$w  = 150;
	$h  = 42;
	$im = imagecreatetruecolor( $w, $h );
	$bg = imagecolorallocate( $im, 243, 244, 246 );
	$fg = imagecolorallocate( $im, 150, 150, 150 );
	imagefilledrectangle( $im, 0, 0, $w, $h, $bg );
	imagestring( $im, 3, 12, 14, $text, $fg );
	return $im;
}

/**
 * 客户端 IP + UA 哈希键(沿用 onedong_bump_view_count 的写法),用于限流。
 *
 * @return string
 */
function onedong_captcha_fingerprint() {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$fwd = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '';
	$ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 64 ) : '';
	return md5( $ip . '|' . $fwd . '|' . $ua );
}

/**
 * 图片生成端点:admin-post.php?action=onedong_captcha
 * 对登录/未登录都触发(admin_action_{action} 是单一钩子)。
 */
function onedong_captcha_image() {
	// —— 限流:每 IP+UA 60s 内最多 15 次,超出返回占位图 ——
	$throttle_key = 'onedong_captcha_ip_' . onedong_captcha_fingerprint();
	$count        = (int) get_transient( $throttle_key );
	if ( $count >= 15 ) {
		$im = onedong_captcha_render_text_image( '稍候重试' );
		nocache_headers();
		header( 'Content-Type: image/png' );
		imagepng( $im );
		imagedestroy( $im );
		exit;
	}
	if ( $count > 0 ) {
		set_transient( $throttle_key, $count + 1, 60 );
	} else {
		set_transient( $throttle_key, 1, 60 );
	}

	$token = bin2hex( random_bytes( 16 ) ); // 32 位 hex
	$code  = onedong_captcha_code();
	set_transient( 'onedong_captcha_' . wp_hash( $token ), wp_hash( strtolower( $code ) ), ONEDONG_CAPTCHA_TTL );

	// 清掉可能残留的输出缓冲,确保 header 能发出。
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	setcookie(
		ONEDONG_CAPTCHA_COOKIE,
		$token,
		array(
			'expires'  => time() + ONEDONG_CAPTCHA_COOKIE_TTL,
			'path'     => '/',
			'httponly' => true,
			'samesite' => 'Lax',
			'secure'   => is_ssl(),
		)
	);

	nocache_headers();
	header( 'Content-Type: image/png' );
	$im = onedong_captcha_render_image( $code );
	imagepng( $im );
	imagedestroy( $im );
	exit;
}
add_action( 'admin_action_onedong_captcha', 'onedong_captcha_image' );

/**
 * 把验证码字段 prepend 到提交按钮前(登录/未登录都能贴在"提交"正上方)。
 *
 * @param string $submit_field 原提交按钮 HTML。
 * @return string
 */
function onedong_captcha_render_field( $submit_field ) {
	if ( ! onedong_captcha_active() ) {
		return $submit_field;
	}

	$src    = onedong_captcha_url( bin2hex( random_bytes( 4 ) ) );
	$note   = __( '验证码(不区分大小写)', 'onedong' );
	$refresh = __( '换一张', 'onedong' );

	$field = '<p class="comment-form-captcha">'
		. '<label for="onedong_captcha_code">' . esc_html( $note ) . '</label>'
		. '<span class="comment-form-captcha__row">'
		. '<img class="comment-form-captcha__img" src="' . esc_url( $src ) . '" alt="' . esc_attr__( '验证码图片', 'onedong' ) . '" width="150" height="42" loading="lazy" />'
		. '<button type="button" class="comment-form-captcha__refresh" data-captcha-refresh aria-label="' . esc_attr__( '换一张验证码', 'onedong' ) . '">' . esc_html( $refresh ) . '</button>'
		. '<input type="text" name="onedong_captcha_code" id="onedong_captcha_code" class="comment-form-captcha__code" autocomplete="off" inputmode="text" required />'
		. '</span>'
		. '</p>';

	return $field . $submit_field;
}
add_filter( 'comment_form_submit_field', 'onedong_captcha_render_field' );

/**
 * 校验验证码。挂 pre_comment_on_post(action,优先级 20),在 flood/duplicate 检查之前。
 *
 * @param int $post_id 评论目标文章 ID。
 */
function onedong_captcha_validate( $post_id ) {
	if ( ! onedong_captcha_active() ) {
		return;
	}
	// 非 POST 提交(如 trackback/pingback 走别的入口)直接放行。
	if ( empty( $_POST['comment'] ) ) {
		return;
	}

	$code  = isset( $_POST['onedong_captcha_code'] ) ? sanitize_text_field( wp_unslash( $_POST['onedong_captcha_code'] ) ) : '';
	$token = isset( $_COOKIE[ ONEDONG_CAPTCHA_COOKIE ] ) ? preg_replace( '/[^a-f0-9]/', '', wp_unslash( $_COOKIE[ ONEDONG_CAPTCHA_COOKIE ] ) ) : '';

	$key     = $token ? ( 'onedong_captcha_' . wp_hash( $token ) ) : '';
	$stored  = $key ? get_transient( $key ) : false;

	// 单次有效:无论成败删掉,一图一猜。
	if ( $key ) {
		delete_transient( $key );
	}

	$ok = ( $stored && hash_equals( $stored, wp_hash( strtolower( trim( $code ) ) ) ) );

	if ( $ok ) {
		return;
	}

	// —— 失败:暂存草稿,重定向回表单并保留正文 ——
	$draft = array(
		'comment' => isset( $_POST['comment'] ) ? wp_kses_post( wp_unslash( $_POST['comment'] ) ) : '',
		'author'  => isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '',
		'email'   => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'url'     => isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '',
	);
	set_transient( 'onedong_captcha_draft_' . wp_hash( $token ), $draft, ONEDONG_CAPTCHA_DRAFT_TTL );

	$redirect = add_query_arg(
		array(
			'captcha_error' => '1',
			'ce_token'      => $token,
		),
		get_permalink( $post_id )
	) . '#respond';

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'pre_comment_on_post', 'onedong_captcha_validate', 20 );

/**
 * 读取当前失败草稿(由 ce_token 决定)。
 * 首次读取即删除 transient(单次有效),并在请求内用静态缓存保存,
 * 因为 comment_form_default_fields 与 comment_form_field_comment 两个过滤器都会取它,
 * 若不在请求内缓存,后执行的 textarea 回填就拿不到正文。
 *
 * @return array|false
 */
function onedong_captcha_get_draft() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	if ( empty( $_GET['captcha_error'] ) || empty( $_GET['ce_token'] ) ) {
		$cache = false;
		return $cache;
	}
	$token = preg_replace( '/[^a-f0-9]/', '', wp_unslash( $_GET['ce_token'] ) );
	if ( '' === $token ) {
		$cache = false;
		return $cache;
	}
	$key   = 'onedong_captcha_draft_' . wp_hash( $token );
	$cache = get_transient( $key );
	delete_transient( $key ); // 首读即删,防止刷新页面后仍回填旧草稿。
	return $cache ? $cache : false;
}

/**
 * 表单顶部红色错误提示(role="alert" 便于读屏器朗读)。
 */
function onedong_captcha_error_note() {
	if ( ! onedong_captcha_active() ) {
		return;
	}
	if ( empty( $_GET['captcha_error'] ) ) {
		return;
	}
	echo '<p class="captcha-error" role="alert">' . esc_html__( '验证码错误,请重新输入。', 'onedong' ) . '</p>';
}
add_action( 'comment_form_top', 'onedong_captcha_error_note' );

/**
 * 把草稿正文回填到 textarea。
 *
 * @param string $field 原 textarea 字段 HTML。
 * @return string
 */
function onedong_captcha_prefill_comment( $field ) {
	if ( empty( $_GET['captcha_error'] ) ) {
		return $field;
	}
	$draft = onedong_captcha_get_draft();
	if ( empty( $draft['comment'] ) ) {
		return $field;
	}
	$pos = strpos( $field, '</textarea>' );
	if ( false === $pos ) {
		return $field;
	}
	return substr_replace( $field, esc_textarea( $draft['comment'] ), $pos, 0 );
}
add_filter( 'comment_form_field_comment', 'onedong_captcha_prefill_comment' );

/**
 * 把草稿的昵称/邮箱/网址回填到对应 input。
 *
 * @param array $fields 默认字段 HTML 数组(author/email/url/cookies)。
 * @return array
 */
function onedong_captcha_prefill_fields( $fields ) {
	if ( empty( $_GET['captcha_error'] ) ) {
		return $fields;
	}
	$draft = onedong_captcha_get_draft();
	if ( ! $draft ) {
		return $fields;
	}

	$map = array(
		'author' => isset( $draft['author'] ) ? esc_attr( $draft['author'] ) : '',
		'email'  => isset( $draft['email'] ) ? esc_attr( $draft['email'] ) : '',
		'url'    => isset( $draft['url'] ) ? esc_attr( $draft['url'] ) : '',
	);

	foreach ( $map as $name => $value ) {
		if ( '' === $value || empty( $fields[ $name ] ) ) {
			continue;
		}
		// 处理该字段第一个 <input ...>:先去掉既有的 value="..."(默认字段会带一个空的),
		// 再紧随 <input 注入我们的 value。limit=1 只改第一个 input。
		$res = preg_replace_callback(
			'/<input\b([^>]*?)>/',
			function ( $m ) use ( $value ) {
				$attrs = preg_replace( '/\svalue=(["\']).*?\1/S', '', $m[1] );
				return '<input value="' . $value . '"' . $attrs . '>';
			},
			$fields[ $name ],
			1
		);
		if ( null !== $res ) {
			$fields[ $name ] = $res;
		}
	}

	return $fields;
}
add_filter( 'comment_form_default_fields', 'onedong_captcha_prefill_fields' );

/**
 * Customizer:评论验证码开关。
 *
 * @param WP_Customize_Manager $wp_customize
 */
function onedong_captcha_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'onedong_comments',
		array(
			'title'    => __( '评论', 'onedong' ),
			'priority' => 33,
		)
	);

	$toggles = array(
		'onedong_comment_captcha'     => __( '评论启用图形验证码', 'onedong' ),
		'onedong_captcha_skip_logged' => __( '登录用户免验证', 'onedong' ),
	);
	foreach ( $toggles as $key => $label ) {
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
				'section' => 'onedong_comments',
				'type'    => 'checkbox',
			)
		);
	}
}
add_action( 'customize_register', 'onedong_captcha_customize_register' );

/**
 * 仅在文章页且评论开启时加载换图脚本。
 */
function onedong_captcha_enqueue_script() {
	if ( ! ( is_singular() && comments_open() ) ) {
		return;
	}
	wp_enqueue_script( 'onedong-captcha', ONEDONG_URI . '/assets/js/captcha.js', array(), ONEDONG_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'onedong_captcha_enqueue_script' );
