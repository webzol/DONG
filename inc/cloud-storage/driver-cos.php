<?php
/**
 * 云存储 driver:腾讯云 COS
 *
 * 签名 q-sign-algorithm=sha1 + wp_remote_request(PUT / DELETE)。纯 PHP,无 SDK。
 * 参考:https://cloud.tencent.com/document/product/436/7778
 *
 * @package OneDong
 * @since 6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** COS 请求域名:{bucket}.cos.{region}.myqcloud.com(bucket 含 APPID)。 */
function onedong_cloud_cos_host( $cfg ) {
	$bucket = isset( $cfg['bucket'] ) ? trim( $cfg['bucket'] ) : '';
	$region = isset( $cfg['region'] ) ? trim( $cfg['region'] ) : '';
	return $bucket . '.cos.' . $region . '.myqcloud.com';
}

/**
 * 组装 q-sign 签名并发送。
 *
 * @param array  $cfg    provider 配置(access_key=SecretId/secret_key=SecretKey/bucket/region/domain)。
 * @param string $method PUT / DELETE。
 * @param string $key    对象键(无前导斜杠)。
 * @param string $body   请求体。
 * @param string $mime   Content-Type(仅 PUT)。
 * @return true|WP_Error
 */
function onedong_cloud_cos_request( $cfg, $method, $key, $body = '', $mime = '' ) {
	$bucket = isset( $cfg['bucket'] ) ? trim( $cfg['bucket'] ) : '';
	$region = isset( $cfg['region'] ) ? trim( $cfg['region'] ) : '';
	$ak     = isset( $cfg['access_key'] ) ? trim( $cfg['access_key'] ) : '';
	$sk     = isset( $cfg['secret_key'] ) ? trim( $cfg['secret_key'] ) : '';
	if ( ! $bucket || ! $region || ! $ak || ! $sk ) {
		return new WP_Error( 'onedong_cloud_cfg', __( '腾讯云 COS 配置不完整(需 SecretId / SecretKey / Bucket / Region)。', 'onedong' ) );
	}

	$key      = ltrim( $key, '/' );
	$host     = onedong_cloud_cos_host( $cfg );
	$pathname = '/' . $key; // UriPathname:原始路径

	$now     = time();
	$keytime = ( $now - 60 ) . ';' . ( $now + 3600 );

	// 仅把 host 纳入签名头。
	$header_list    = 'host';
	$header_string  = 'host=' . rawurlencode( $host );
	$url_param_list = '';

	// HttpString = method\nUriPathname\nHttpParameters\nHttpHeaders\n
	$http_string    = strtolower( $method ) . "\n" . $pathname . "\n" . '' . "\n" . $header_string . "\n";
	// StringToSign = sha1\nKeyTime\nSHA1(HttpString)\n
	$string_to_sign = "sha1\n" . $keytime . "\n" . sha1( $http_string ) . "\n";
	$sign_key       = hash_hmac( 'sha1', $keytime, $sk );          // hex
	$signature      = hash_hmac( 'sha1', $string_to_sign, $sign_key ); // hex

	$authorization = 'q-sign-algorithm=sha1'
		. '&q-ak=' . $ak
		. '&q-sign-time=' . $keytime
		. '&q-key-time=' . $keytime
		. '&q-header-list=' . $header_list
		. '&q-url-param-list=' . $url_param_list
		. '&q-signature=' . $signature;

	$enc = implode( '/', array_map( 'rawurlencode', explode( '/', $key ) ) );
	$url = 'https://' . $host . '/' . $enc;

	$headers = array(
		'Authorization' => $authorization,
		'Host'          => $host,
	);
	if ( 'PUT' === $method && $mime ) {
		$headers['Content-Type'] = $mime;
	}

	$resp = wp_remote_request( $url, array(
		'method'  => $method,
		'headers' => $headers,
		'body'    => $body,
		'timeout' => 30,
	) );

	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$code = (int) wp_remote_retrieve_response_code( $resp );
	if ( $code >= 200 && $code < 300 ) {
		return true;
	}
	$msg = wp_remote_retrieve_body( $resp );
	if ( preg_match( '#<Message>(.*?)</Message>#s', $msg, $m ) ) {
		$msg = $m[1];
	}
	/* translators: 1: HTTP status code, 2: error message. */
	return new WP_Error( 'onedong_cloud_http', sprintf( __( 'COS 返回 %1$d:%2$s', 'onedong' ), $code, wp_strip_all_tags( (string) $msg ) ) );
}

function onedong_cloud_cos_upload( $cfg, $local, $key, $mime ) {
	$body = file_get_contents( $local ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === $body ) {
		return new WP_Error( 'onedong_cloud_read', __( '读取本地文件失败。', 'onedong' ) );
	}
	return onedong_cloud_cos_request( $cfg, 'PUT', $key, $body, $mime );
}

function onedong_cloud_cos_delete( $cfg, $key ) {
	return onedong_cloud_cos_request( $cfg, 'DELETE', $key );
}

function onedong_cloud_cos_public_url( $cfg, $key ) {
	$key = ltrim( $key, '/' );
	if ( ! empty( $cfg['domain'] ) ) {
		$base = rtrim( trim( $cfg['domain'] ), '/' );
		if ( ! preg_match( '#^https?://#', $base ) ) {
			$base = 'https://' . $base;
		}
	} else {
		$base = 'https://' . onedong_cloud_cos_host( $cfg );
	}
	$enc = implode( '/', array_map( 'rawurlencode', explode( '/', $key ) ) );
	return $base . '/' . $enc;
}

function onedong_cloud_cos_test( $cfg ) {
	$key = 'onedong-cloud-test-' . time() . '.txt';
	$res = onedong_cloud_cos_request( $cfg, 'PUT', $key, 'OneDong cloud storage connectivity OK.', 'text/plain' );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	return onedong_cloud_cos_public_url( $cfg, $key );
}
