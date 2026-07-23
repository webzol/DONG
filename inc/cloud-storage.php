<?php
/**
 * 云存储 Offload 模块
 *
 * 媒体库上传的图片 / 附件自动推送到对象存储(阿里云 OSS / 腾讯云 COS / 七牛云 Kodo /
 * 又拍云 USS / 华为云 OBS / AWS S3·MinIO),前端 URL 自动替换为云端 / CDN 域名,
 * 删除附件时同步清理云端。签名全部纯 PHP 手写(hash_hmac),零 SDK / composer 依赖,
 * 延续本主题 captcha(GD 自包含)的风格。
 *
 * 架构:函数式 driver,命名约定 onedong_cloud_{provider}_{op}($cfg, ...);
 *       工厂 onedong_cloud_dispatch() 按「当前启用 provider」分发。新增 provider =
 *       往 inc/cloud-storage/ 丢一个 driver 文件 + 在 onedong_cloud_providers() 注册字段。
 *
 * @package OneDong
 * @since 6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // 禁止直接访问
}

/* ============================================================
 * 0. 载入 driver(存在才载入 → 阶段 2 补文件即自动生效)
 * ============================================================ */
foreach ( array( 'oss', 'cos', 'qiniu', 'upyun', 'obs', 's3' ) as $onedong_cloud_drv ) {
	$onedong_cloud_drv_file = __DIR__ . '/cloud-storage/driver-' . $onedong_cloud_drv . '.php';
	if ( file_exists( $onedong_cloud_drv_file ) ) {
		require_once $onedong_cloud_drv_file;
	}
}
unset( $onedong_cloud_drv, $onedong_cloud_drv_file );


/* ============================================================
 * 1. Provider 元数据:label + 字段 schema + 是否已实现 driver
 *    字段 type:text / password / checkbox。password 不回显明文。
 * ============================================================ */
function onedong_cloud_providers() {
	return array(
		'oss'   => array(
			'label'  => __( '阿里云 OSS', 'onedong' ),
			'ready'  => true,
			'fields' => array(
				'access_key' => array( 'label' => 'AccessKey ID', 'type' => 'text' ),
				'secret_key' => array( 'label' => 'AccessKey Secret', 'type' => 'password' ),
				'bucket'     => array( 'label' => __( 'Bucket 名称', 'onedong' ), 'type' => 'text' ),
				'endpoint'   => array( 'label' => __( 'Endpoint(地域节点)', 'onedong' ), 'type' => 'text', 'placeholder' => 'oss-cn-hangzhou.aliyuncs.com' ),
				'domain'     => array( 'label' => __( '自定义访问域名(可选)', 'onedong' ), 'type' => 'text', 'placeholder' => 'https://cdn.example.com' ),
			),
		),
		'cos'   => array(
			'label'  => __( '腾讯云 COS', 'onedong' ),
			'ready'  => true,
			'fields' => array(
				'access_key' => array( 'label' => 'SecretId', 'type' => 'text' ),
				'secret_key' => array( 'label' => 'SecretKey', 'type' => 'password' ),
				'bucket'     => array( 'label' => __( 'Bucket(含 APPID)', 'onedong' ), 'type' => 'text', 'placeholder' => 'example-1250000000' ),
				'region'     => array( 'label' => __( 'Region(地域)', 'onedong' ), 'type' => 'text', 'placeholder' => 'ap-guangzhou' ),
				'domain'     => array( 'label' => __( '自定义访问域名(可选)', 'onedong' ), 'type' => 'text', 'placeholder' => 'https://cdn.example.com' ),
			),
		),
		'qiniu' => array(
			'label'  => __( '七牛云 Kodo', 'onedong' ),
			'ready'  => false,
			'fields' => array(
				'access_key' => array( 'label' => 'AccessKey', 'type' => 'text' ),
				'secret_key' => array( 'label' => 'SecretKey', 'type' => 'password' ),
				'bucket'     => array( 'label' => __( '空间名称', 'onedong' ), 'type' => 'text' ),
				'region'     => array( 'label' => __( '存储区域(zone)', 'onedong' ), 'type' => 'text', 'placeholder' => 'z0 / z1 / z2 / na0 / as0' ),
				'domain'     => array( 'label' => __( '加速域名(必填)', 'onedong' ), 'type' => 'text', 'placeholder' => 'https://cdn.example.com' ),
			),
		),
		'upyun' => array(
			'label'  => __( '又拍云 USS', 'onedong' ),
			'ready'  => false,
			'fields' => array(
				'bucket'   => array( 'label' => __( '服务名称(空间)', 'onedong' ), 'type' => 'text' ),
				'operator' => array( 'label' => __( '操作员', 'onedong' ), 'type' => 'text' ),
				'password' => array( 'label' => __( '操作员密码', 'onedong' ), 'type' => 'password' ),
				'domain'   => array( 'label' => __( '加速域名(必填)', 'onedong' ), 'type' => 'text', 'placeholder' => 'https://cdn.example.com' ),
			),
		),
		'obs'   => array(
			'label'  => __( '华为云 OBS', 'onedong' ),
			'ready'  => false,
			'fields' => array(
				'access_key' => array( 'label' => 'AK', 'type' => 'text' ),
				'secret_key' => array( 'label' => 'SK', 'type' => 'password' ),
				'bucket'     => array( 'label' => __( 'Bucket 名称', 'onedong' ), 'type' => 'text' ),
				'endpoint'   => array( 'label' => 'Endpoint', 'type' => 'text', 'placeholder' => 'obs.cn-north-4.myhuaweicloud.com' ),
				'domain'     => array( 'label' => __( '自定义访问域名(可选)', 'onedong' ), 'type' => 'text' ),
			),
		),
		's3'    => array(
			'label'  => __( 'AWS S3 / MinIO', 'onedong' ),
			'ready'  => false,
			'fields' => array(
				'access_key' => array( 'label' => 'Access Key ID', 'type' => 'text' ),
				'secret_key' => array( 'label' => 'Secret Access Key', 'type' => 'password' ),
				'bucket'     => array( 'label' => 'Bucket', 'type' => 'text' ),
				'region'     => array( 'label' => 'Region', 'type' => 'text', 'placeholder' => 'us-east-1' ),
				'endpoint'   => array( 'label' => __( '自定义 Endpoint(MinIO / R2,可选)', 'onedong' ), 'type' => 'text', 'placeholder' => 'https://minio.example.com' ),
				'domain'     => array( 'label' => __( '自定义访问域名(可选)', 'onedong' ), 'type' => 'text' ),
				'path_style' => array( 'label' => __( 'Path-Style 寻址(MinIO 需勾选)', 'onedong' ), 'type' => 'checkbox' ),
			),
		),
	);
}


/* ============================================================
 * 2. 选项读取 / 默认值
 * ============================================================ */
function onedong_cloud_defaults() {
	$providers = array();
	foreach ( onedong_cloud_providers() as $id => $def ) {
		$providers[ $id ] = array();
		foreach ( $def['fields'] as $fkey => $fdef ) {
			$providers[ $id ][ $fkey ] = ( 'checkbox' === $fdef['type'] ) ? '0' : '';
		}
	}
	return array(
		'enable'          => '0',
		'provider'        => 'oss',
		'keep_local'      => '1',   // 默认保留本地副本(安全)
		'path_prefix'     => '',    // 云端对象键前缀,如 wp
		'replace_content' => '0',   // 是否替换文章正文里的历史 uploads URL
		'providers'       => $providers,
	);
}

function onedong_cloud_opts() {
	$o = wp_parse_args( (array) get_option( 'onedong_cloud_settings', array() ), onedong_cloud_defaults() );
	// 确保每个 provider 的子数组齐全(新增字段向后兼容)
	$defaults = onedong_cloud_defaults();
	foreach ( $defaults['providers'] as $id => $fields ) {
		$o['providers'][ $id ] = isset( $o['providers'][ $id ] ) ? array_merge( $fields, (array) $o['providers'][ $id ] ) : $fields;
	}
	return $o;
}

/** 当前启用的 provider id(仅当总开关开 + provider 有 driver 时视为「生效」)。 */
function onedong_cloud_active_provider() {
	$o = onedong_cloud_opts();
	return $o['provider'];
}

function onedong_cloud_is_active() {
	$o    = onedong_cloud_opts();
	$prov = $o['provider'];
	return '1' === $o['enable'] && function_exists( "onedong_cloud_{$prov}_upload" );
}


/* ============================================================
 * 3. 工厂分发 + 对象键工具 + 高层 API
 * ============================================================ */
function onedong_cloud_provider_config( $provider ) {
	$o = onedong_cloud_opts();
	return isset( $o['providers'][ $provider ] ) ? $o['providers'][ $provider ] : array();
}

/**
 * 分发到 driver 函数 onedong_cloud_{provider}_{op}( $cfg, ...$args )。
 *
 * @param string $provider provider id。
 * @param string $op        upload | delete | public_url | test。
 * @param array  $args      传给 driver 的参数(不含首个 $cfg)。
 * @return mixed|WP_Error
 */
function onedong_cloud_dispatch( $provider, $op, $args = array() ) {
	$fn = "onedong_cloud_{$provider}_{$op}";
	if ( ! function_exists( $fn ) ) {
		return new WP_Error( 'onedong_cloud_not_ready', __( '该云存储的驱动尚未实现(将在阶段 2 支持)。', 'onedong' ) );
	}
	return call_user_func_array( $fn, array_merge( array( onedong_cloud_provider_config( $provider ) ), $args ) );
}

/** 云端对象键:前缀 + uploads 相对路径。 */
function onedong_cloud_build_key( $relative ) {
	$o      = onedong_cloud_opts();
	$prefix = trim( $o['path_prefix'], '/' );
	$relative = ltrim( $relative, '/' );
	return $prefix ? $prefix . '/' . $relative : $relative;
}

/** 同目录兄弟键(把 base_key 的文件名换成 $filename)—— 用于缩略图各尺寸。 */
function onedong_cloud_key_sibling( $base_key, $filename ) {
	$dir = dirname( $base_key );
	return ( '.' === $dir || '' === $dir ) ? $filename : $dir . '/' . $filename;
}

function onedong_cloud_upload_file( $local, $key, $mime ) {
	return onedong_cloud_dispatch( onedong_cloud_active_provider(), 'upload', array( $local, $key, $mime ) );
}

/** 当前 provider 下某 key 的公开 URL。 */
function onedong_cloud_url_for( $key ) {
	return onedong_cloud_dispatch( onedong_cloud_active_provider(), 'public_url', array( $key ) );
}


/* ============================================================
 * 4. Offload:上传 / 删除挂钩
 * ============================================================ */
/**
 * 生成附件元数据后:把原文件 + 各缩略尺寸推送到云。
 * 非图片附件(pdf/zip 等)$metadata 无 sizes,只传原文件。
 */
function onedong_cloud_offload_metadata( $metadata, $attachment_id ) {
	if ( ! onedong_cloud_is_active() ) {
		return $metadata;
	}
	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! file_exists( $file ) ) {
		return $metadata;
	}

	$upload  = wp_upload_dir();
	$basedir = trailingslashit( $upload['basedir'] );
	$rel     = ltrim( str_replace( $basedir, '', $file ), '/' ); // 2026/07/img.jpg
	$key     = onedong_cloud_build_key( $rel );
	$mime    = get_post_mime_type( $attachment_id );

	$res = onedong_cloud_upload_file( $file, $key, $mime ? $mime : 'application/octet-stream' );
	if ( is_wp_error( $res ) ) {
		onedong_cloud_log( 'upload failed: ' . $rel . ' → ' . $res->get_error_message() );
		return $metadata; // 失败则保持本地 URL,不写 meta
	}

	update_post_meta( $attachment_id, '_onedong_cloud_provider', onedong_cloud_active_provider() );
	update_post_meta( $attachment_id, '_onedong_cloud_key', $key );

	$uploaded = array( $file );

	if ( ! empty( $metadata['sizes'] ) ) {
		$dir     = trailingslashit( dirname( $file ) );
		$rel_dir = ltrim( trailingslashit( dirname( $rel ) ), '/' );
		$rel_dir = ( './' === $rel_dir ) ? '' : $rel_dir;
		foreach ( $metadata['sizes'] as $s ) {
			if ( empty( $s['file'] ) ) {
				continue;
			}
			$sfile = $dir . $s['file'];
			if ( ! file_exists( $sfile ) ) {
				continue;
			}
			$skey  = onedong_cloud_build_key( $rel_dir . $s['file'] );
			$smime = ! empty( $s['mime-type'] ) ? $s['mime-type'] : $mime;
			$sres  = onedong_cloud_upload_file( $sfile, $skey, $smime );
			if ( ! is_wp_error( $sres ) ) {
				$uploaded[] = $sfile;
			}
		}
	}

	// 上传后按需删本地(省空间);默认保留。
	if ( '1' !== onedong_cloud_opts()['keep_local'] ) {
		foreach ( $uploaded as $u ) {
			@unlink( $u ); // phpcs:ignore WordPress.PHP.NoSilentErrors
		}
	}

	return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'onedong_cloud_offload_metadata', 20, 2 );

/** 删除附件时清理云端(原图 + 各尺寸)。 */
function onedong_cloud_offload_delete( $attachment_id ) {
	$prov = get_post_meta( $attachment_id, '_onedong_cloud_provider', true );
	$key  = get_post_meta( $attachment_id, '_onedong_cloud_key', true );
	if ( ! $prov || ! $key ) {
		return;
	}
	onedong_cloud_dispatch( $prov, 'delete', array( $key ) );

	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( ! empty( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $s ) {
			if ( ! empty( $s['file'] ) ) {
				onedong_cloud_dispatch( $prov, 'delete', array( onedong_cloud_key_sibling( $key, $s['file'] ) ) );
			}
		}
	}
}
add_action( 'delete_attachment', 'onedong_cloud_offload_delete' );


/* ============================================================
 * 5. 前端 URL 替换:full URL / 各尺寸 src / srcset /(可选)正文
 * ============================================================ */
function onedong_cloud_meta( $attachment_id ) {
	$prov = get_post_meta( $attachment_id, '_onedong_cloud_provider', true );
	$key  = get_post_meta( $attachment_id, '_onedong_cloud_key', true );
	return ( $prov && $key ) ? array( $prov, $key ) : false;
}

/** 附件完整 URL(附件页 / 链接 / 音视频等)。 */
function onedong_cloud_filter_url( $url, $attachment_id ) {
	$m = onedong_cloud_meta( $attachment_id );
	if ( ! $m ) {
		return $url;
	}
	$cloud = onedong_cloud_dispatch( $m[0], 'public_url', array( $m[1] ) );
	return is_wp_error( $cloud ) ? $url : $cloud;
}
add_filter( 'wp_get_attachment_url', 'onedong_cloud_filter_url', 10, 2 );

/** 各尺寸 <img src>(image_downsize 不走 attachment_url,需单独 filter)。 */
function onedong_cloud_filter_image_src( $image, $attachment_id ) {
	if ( ! is_array( $image ) || empty( $image[0] ) ) {
		return $image;
	}
	$m = onedong_cloud_meta( $attachment_id );
	if ( ! $m ) {
		return $image;
	}
	$fname = basename( wp_parse_url( $image[0], PHP_URL_PATH ) );
	$cloud = onedong_cloud_dispatch( $m[0], 'public_url', array( onedong_cloud_key_sibling( $m[1], $fname ) ) );
	if ( ! is_wp_error( $cloud ) ) {
		$image[0] = $cloud;
	}
	return $image;
}
add_filter( 'wp_get_attachment_image_src', 'onedong_cloud_filter_image_src', 10, 2 );

/** 响应式 srcset 各候选 URL。 */
function onedong_cloud_filter_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	if ( ! is_array( $sources ) ) {
		return $sources;
	}
	$m = onedong_cloud_meta( $attachment_id );
	if ( ! $m ) {
		return $sources;
	}
	foreach ( $sources as $w => $src ) {
		$fname = basename( wp_parse_url( $src['url'], PHP_URL_PATH ) );
		$cloud = onedong_cloud_dispatch( $m[0], 'public_url', array( onedong_cloud_key_sibling( $m[1], $fname ) ) );
		if ( ! is_wp_error( $cloud ) ) {
			$sources[ $w ]['url'] = $cloud;
		}
	}
	return $sources;
}
add_filter( 'wp_calculate_image_srcset', 'onedong_cloud_filter_srcset', 10, 5 );

/** 可选:把文章正文里历史的 uploads 基础 URL 整体替换为云端(含前缀)。 */
function onedong_cloud_filter_content( $content ) {
	$o = onedong_cloud_opts();
	if ( '1' !== $o['replace_content'] || ! onedong_cloud_is_active() ) {
		return $content;
	}
	$upload     = wp_upload_dir();
	$local_base = $upload['baseurl'];
	$cloud_base = onedong_cloud_url_for( trim( $o['path_prefix'], '/' ) ); // host/prefix
	if ( is_wp_error( $cloud_base ) || ! $cloud_base ) {
		return $content;
	}
	return str_replace( $local_base, untrailingslashit( $cloud_base ), $content );
}
add_filter( 'the_content', 'onedong_cloud_filter_content', 20 );


/* ============================================================
 * 6. 后台设置页:顶级菜单「云存储」
 * ============================================================ */
function onedong_cloud_admin_menu() {
	add_menu_page(
		__( '云存储', 'onedong' ),
		__( '云存储', 'onedong' ),
		'manage_options',
		'onedong-cloud',
		'onedong_cloud_settings_page_cb',
		'dashicons-cloud',
		8
	);
}
add_action( 'admin_menu', 'onedong_cloud_admin_menu' );

function onedong_cloud_settings_init() {
	register_setting( 'onedong_cloud_group', 'onedong_cloud_settings', 'onedong_cloud_sanitize' );
}
add_action( 'admin_init', 'onedong_cloud_settings_init' );

function onedong_cloud_sanitize( $in ) {
	$in        = (array) $in;
	$old       = (array) get_option( 'onedong_cloud_settings', array() );
	$providers = onedong_cloud_providers();
	$out       = onedong_cloud_defaults();

	$out['enable']          = empty( $in['enable'] ) ? '0' : '1';
	$out['keep_local']      = empty( $in['keep_local'] ) ? '0' : '1';
	$out['replace_content'] = empty( $in['replace_content'] ) ? '0' : '1';
	$prov                   = isset( $in['provider'] ) ? sanitize_key( $in['provider'] ) : 'oss';
	$out['provider']        = isset( $providers[ $prov ] ) ? $prov : 'oss';
	$out['path_prefix']     = isset( $in['path_prefix'] ) ? trim( sanitize_text_field( $in['path_prefix'] ), '/' ) : '';

	foreach ( $providers as $id => $def ) {
		foreach ( $def['fields'] as $fkey => $fdef ) {
			$raw = isset( $in['providers'][ $id ][ $fkey ] ) ? $in['providers'][ $id ][ $fkey ] : '';
			if ( 'checkbox' === $fdef['type'] ) {
				$out['providers'][ $id ][ $fkey ] = empty( $raw ) ? '0' : '1';
			} else {
				$val = sanitize_text_field( $raw );
				// 密钥留空 → 保留旧值(免每次保存重填)
				if ( 'password' === $fdef['type'] && '' === $val && ! empty( $old['providers'][ $id ][ $fkey ] ) ) {
					$val = $old['providers'][ $id ][ $fkey ];
				}
				$out['providers'][ $id ][ $fkey ] = $val;
			}
		}
	}
	return $out;
}

function onedong_cloud_settings_page_cb() {
	$o         = onedong_cloud_opts();
	$providers = onedong_cloud_providers();
	?>
	<div class="wrap onedong-cloud-wrap">
		<h1><?php esc_html_e( '云存储 · 媒体 Offload', 'onedong' ); ?></h1>
		<p class="description"><?php esc_html_e( '开启后,媒体库上传的图片 / 附件会自动推送到所选对象存储,前端 URL 替换为云端 / CDN 域名;删除附件时同步清理云端。密钥仅存于本站数据库,不会输出到前端。', 'onedong' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'onedong_cloud_group' ); ?>

			<h2 class="title"><?php esc_html_e( '通用', 'onedong' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( '启用云存储', 'onedong' ); ?></th>
					<td><label><input type="checkbox" name="onedong_cloud_settings[enable]" value="1" <?php checked( $o['enable'], '1' ); ?>> <?php esc_html_e( '开启后新上传的媒体自动 Offload', 'onedong' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="onedong-cloud-provider"><?php esc_html_e( '当前服务商', 'onedong' ); ?></label></th>
					<td>
						<select name="onedong_cloud_settings[provider]" id="onedong-cloud-provider">
							<?php foreach ( $providers as $id => $def ) : ?>
								<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $o['provider'], $id ); ?>>
									<?php echo esc_html( $def['label'] . ( $def['ready'] ? '' : __( '(即将支持)', 'onedong' ) ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( '一次启用一家。下方各标签页可分别填写配置,只有此处所选的服务商会实际生效。', 'onedong' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '云端路径前缀', 'onedong' ); ?></th>
					<td><input type="text" name="onedong_cloud_settings[path_prefix]" value="<?php echo esc_attr( $o['path_prefix'] ); ?>" class="regular-text" placeholder="wp"><p class="description"><?php esc_html_e( '可选。对象键前缀,如填 wp 则存到 云端/wp/2026/07/…。留空存到根。', 'onedong' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '上传后本地文件', 'onedong' ); ?></th>
					<td><label><input type="checkbox" name="onedong_cloud_settings[keep_local]" value="1" <?php checked( $o['keep_local'], '1' ); ?>> <?php esc_html_e( '保留本地副本(取消勾选则上传成功后删除本地,省服务器空间)', 'onedong' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '替换正文历史 URL', 'onedong' ); ?></th>
					<td><label><input type="checkbox" name="onedong_cloud_settings[replace_content]" value="1" <?php checked( $o['replace_content'], '1' ); ?>> <?php esc_html_e( '把文章正文里 /wp-content/uploads 的旧链接实时替换为云端域名(适合已迁移历史文件的场景)', 'onedong' ); ?></label></td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( '服务商配置', 'onedong' ); ?></h2>
			<nav class="onedong-cloud-tabs nav-tab-wrapper">
				<?php foreach ( $providers as $id => $def ) : ?>
					<a href="#" class="nav-tab" data-tab="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $def['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<?php foreach ( $providers as $id => $def ) : ?>
				<div class="onedong-cloud-panel" data-panel="<?php echo esc_attr( $id ); ?>"<?php echo ( $id === $o['provider'] ) ? '' : ' hidden'; ?>>
					<?php if ( ! $def['ready'] ) : ?>
						<p class="notice notice-info inline" style="padding:8px 12px;"><?php esc_html_e( '此服务商的驱动将在阶段 2 提供。配置可以先填,暂不能测试 / 生效。', 'onedong' ); ?></p>
					<?php endif; ?>
					<table class="form-table" role="presentation">
						<?php foreach ( $def['fields'] as $fkey => $fdef ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( $fdef['label'] ); ?></th>
								<td><?php onedong_cloud_render_field( $id, $fkey, $fdef, $o['providers'][ $id ][ $fkey ] ); ?></td>
							</tr>
						<?php endforeach; ?>
						<tr>
							<th scope="row"><?php esc_html_e( '连接测试', 'onedong' ); ?></th>
							<td>
								<button type="button" class="button onedong-cloud-test" data-provider="<?php echo esc_attr( $id ); ?>"<?php disabled( ! $def['ready'] ); ?>><?php esc_html_e( '测试连接', 'onedong' ); ?></button>
								<span class="onedong-cloud-test-result" data-for="<?php echo esc_attr( $id ); ?>"></span>
								<p class="description"><?php esc_html_e( '会向云端上传一个极小的测试文件以验证密钥 / 权限 / 配置(用当前表单里的值,无需先保存)。', 'onedong' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			<?php endforeach; ?>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/** 单字段渲染(text / password / checkbox)。password 不回显明文。 */
function onedong_cloud_render_field( $prov, $fkey, $fdef, $val ) {
	$name = sprintf( 'onedong_cloud_settings[providers][%s][%s]', esc_attr( $prov ), esc_attr( $fkey ) );
	$type = $fdef['type'];
	if ( 'checkbox' === $type ) {
		printf( '<label><input type="checkbox" name="%1$s" value="1" %2$s class="onedong-cloud-input" data-field="%3$s"></label>', $name, checked( $val, '1', false ), esc_attr( $fkey ) );
		return;
	}
	if ( 'password' === $type ) {
		$saved       = ( '' !== (string) $val );
		$placeholder = $saved ? esc_attr__( '已保存(留空则不修改)', 'onedong' ) : ( isset( $fdef['placeholder'] ) ? esc_attr( $fdef['placeholder'] ) : '' );
		printf(
			'<input type="password" name="%1$s" value="" autocomplete="new-password" class="regular-text onedong-cloud-input" data-field="%2$s" placeholder="%3$s">',
			$name,
			esc_attr( $fkey ),
			$placeholder
		);
		return;
	}
	$placeholder = isset( $fdef['placeholder'] ) ? esc_attr( $fdef['placeholder'] ) : '';
	printf(
		'<input type="text" name="%1$s" value="%2$s" class="regular-text onedong-cloud-input" data-field="%3$s" placeholder="%4$s">',
		$name,
		esc_attr( $val ),
		esc_attr( $fkey ),
		$placeholder
	);
}


/* ============================================================
 * 7. AJAX:连接测试(用表单当前值,无需先保存)
 * ============================================================ */
function onedong_cloud_ajax_test() {
	check_ajax_referer( 'onedong_cloud_test', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( '权限不足。', 'onedong' ) ) );
	}
	$prov      = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
	$providers = onedong_cloud_providers();
	if ( ! isset( $providers[ $prov ] ) ) {
		wp_send_json_error( array( 'message' => __( '未知服务商。', 'onedong' ) ) );
	}

	// 收集该 provider 字段;password 若空则回退已保存值。
	$saved = onedong_cloud_provider_config( $prov );
	$cfg   = array();
	$raw   = isset( $_POST['cfg'] ) && is_array( $_POST['cfg'] ) ? wp_unslash( $_POST['cfg'] ) : array();
	foreach ( $providers[ $prov ]['fields'] as $fkey => $fdef ) {
		if ( 'checkbox' === $fdef['type'] ) {
			$cfg[ $fkey ] = ! empty( $raw[ $fkey ] ) ? '1' : '0';
			continue;
		}
		$v = isset( $raw[ $fkey ] ) ? sanitize_text_field( $raw[ $fkey ] ) : '';
		if ( 'password' === $fdef['type'] && '' === $v && ! empty( $saved[ $fkey ] ) ) {
			$v = $saved[ $fkey ];
		}
		$cfg[ $fkey ] = $v;
	}

	$fn = "onedong_cloud_{$prov}_test";
	if ( ! function_exists( $fn ) ) {
		wp_send_json_error( array( 'message' => __( '该服务商驱动尚未实现(阶段 2)。', 'onedong' ) ) );
	}
	$res = call_user_func( $fn, $cfg );
	if ( is_wp_error( $res ) ) {
		wp_send_json_error( array( 'message' => $res->get_error_message() ) );
	}
	wp_send_json_success( array(
		'message' => __( '连接成功,测试文件已上传并可访问。', 'onedong' ),
		'url'     => is_string( $res ) ? $res : '',
	) );
}
add_action( 'wp_ajax_onedong_cloud_test', 'onedong_cloud_ajax_test' );


/* ============================================================
 * 8. 后台资源 + 日志
 * ============================================================ */
function onedong_cloud_admin_assets( $hook ) {
	if ( 'toplevel_page_onedong-cloud' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'onedong-cloud-admin', ONEDONG_URI . '/assets/js/cloud-storage-admin.js', array( 'jquery' ), ONEDONG_VERSION, true );
	wp_localize_script( 'onedong-cloud-admin', 'OneDongCloud', array(
		'ajax'    => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'onedong_cloud_test' ),
		'testing' => __( '测试中…', 'onedong' ),
		'active'  => onedong_cloud_active_provider(),
	) );
}
add_action( 'admin_enqueue_scripts', 'onedong_cloud_admin_assets' );

/** 轻量日志(仅 WP_DEBUG 时写 error_log)。 */
function onedong_cloud_log( $msg ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[OneDong Cloud] ' . $msg ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
}
