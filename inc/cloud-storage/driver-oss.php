<?php
/**
 * 云存储 driver:阿里云 OSS
 *
 * OSS V1 Header 签名(hmac-sha1)+ wp_remote_request(PUT / DELETE)。纯 PHP,无 SDK。
 * 参考:https://help.aliyun.com/zh/oss/developer-reference/include-signatures-in-the-authorization-header
 *
 * @package OneDong
 * @since 6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 组装签名并发送请求。
 *
 * @param array  $cfg    provider 配置(access_key/secret_key/bucket/endpoint/domain)。
 * @param string $method PUT / DELETE。
 * @param string $key    对象键(无前导斜杠)。
 * @param string $body   请求体(文件内容 / 测试文本)。
 * @param string $mime   Content-Type(仅 PUT)。
 * @return true|WP_Error
 */
function onedong_cloud_oss_request( $cfg, $method, $key, $body = '', $mime = '' ) {
	$bucket   = isset( $cfg['bucket'] ) ? trim( $cfg['bucket'] ) : '';
	$endpoint = isset( $cfg['endpoint'] ) ? preg_replace( '#^https?://#', '', rtrim( trim( $cfg['endpoint'] ), '/' ) ) : '';
	$ak       = isset( $cfg['access_key'] ) ? trim( $cfg['access_key'] ) : '';
	$sk       = isset( $cfg['secret_key'] ) ? trim( $cfg['secret_key'] ) : '';
	if ( ! $bucket || ! $endpoint || ! $ak || ! $sk ) {
		return new WP_Error( 'onedong_cloud_cfg', __( '阿里云 OSS 配置不完整(需 AccessKey ID / Secret / Bucket / Endpoint)。', 'onedong' ) );
	}

	$key      = ltrim( $key, '/' );
	$date     = gmdate( 'D, d M Y H:i:s \G\M\T' );
	$ctype    = ( 'PUT' === $method ) ? ( $mime ? $mime : 'application/octet-stream' ) : '';
	$resource = '/' . $bucket . '/' . $key; // CanonicalizedResource(原始 key,不编码)

	// StringToSign = VERB\nContent-MD5\nContent-Type\nDate\nCanonicalizedOSSHeaders + CanonicalizedResource
	// 不加任何 x-oss-* 头 → CanonicalizedOSSHeaders 为空;Content-MD5 留空。
	$string_to_sign = $method . "\n" . '' . "\n" . $ctype . "\n" . $date . "\n" . $resource;
	$signature      = base64_encode( hash_hmac( 'sha1', $string_to_sign, $sk, true ) );
	$authorization  = 'OSS ' . $ak . ':' . $signature;

	$host = $bucket . '.' . $endpoint;
	$enc  = implode( '/', array_map( 'rawurlencode', explode( '/', $key ) ) ); // 保留 '/'
	$url  = 'https://' . $host . '/' . $enc;

	$headers = array(
		'Date'          => $date,
		'Authorization' => $authorization,
		'Host'          => $host,
	);
	if ( 'PUT' === $method ) {
		$headers['Content-Type'] = $ctype;
	}

	$resp = wp_remote_request( $url, array(
		'method'  => $method,
		'headers' => $headers,
		'body'    => $body,
		'timeout' => 30,
	) );
	return onedong_cloud_oss_handle_response( $resp, 'OSS' );
}

/** 统一处理响应:2xx → true,否则 WP_Error(尽量取 XML <Message>)。 */
function onedong_cloud_oss_handle_response( $resp, $label ) {
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
	return new WP_Error( 'onedong_cloud_http', sprintf( __( '%1$s 返回 %2$d:%3$s', 'onedong' ), $label, $code, wp_strip_all_tags( (string) $msg ) ) );
}

function onedong_cloud_oss_upload( $cfg, $local, $key, $mime ) {
	$body = file_get_contents( $local ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === $body ) {
		return new WP_Error( 'onedong_cloud_read', __( '读取本地文件失败。', 'onedong' ) );
	}
	return onedong_cloud_oss_request( $cfg, 'PUT', $key, $body, $mime );
}

function onedong_cloud_oss_delete( $cfg, $key ) {
	return onedong_cloud_oss_request( $cfg, 'DELETE', $key );
}

function onedong_cloud_oss_public_url( $cfg, $key ) {
	$key = ltrim( $key, '/' );
	if ( ! empty( $cfg['domain'] ) ) {
		$base = rtrim( trim( $cfg['domain'] ), '/' );
		if ( ! preg_match( '#^https?://#', $base ) ) {
			$base = 'https://' . $base;
		}
	} else {
		$endpoint = preg_replace( '#^https?://#', '', rtrim( isset( $cfg['endpoint'] ) ? trim( $cfg['endpoint'] ) : '', '/' ) );
		$base     = 'https://' . ( isset( $cfg['bucket'] ) ? trim( $cfg['bucket'] ) : '' ) . '.' . $endpoint;
	}
	$enc = implode( '/', array_map( 'rawurlencode', explode( '/', $key ) ) );
	return $base . '/' . $enc;
}

function onedong_cloud_oss_test( $cfg ) {
	$key = 'onedong-cloud-test-' . time() . '.txt';
	$res = onedong_cloud_oss_request( $cfg, 'PUT', $key, 'OneDong cloud storage connectivity OK.', 'text/plain' );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	return onedong_cloud_oss_public_url( $cfg, $key );
}
