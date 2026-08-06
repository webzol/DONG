<?php
/**
 * 资源提交页面 - /resources/submit/
 * 公开推荐保存为待审核资源,由管理员审核后发布。
 *
 * @package OneDong
 */
get_header();

$result   = isset( $_GET['resource_submit'] ) ? sanitize_key( wp_unslash( $_GET['resource_submit'] ) ) : '';
$messages = array(
	'success'      => array( 'success', __( '提交成功，感谢推荐！资源将在管理员审核后发布。', 'onedong' ) ),
	'duplicate'    => array( 'warning', __( '这个资源已经提交过了，无需重复推荐。', 'onedong' ) ),
	'rate_limited' => array( 'warning', __( '提交得太快了，请一分钟后再试。', 'onedong' ) ),
	'invalid'      => array( 'error', __( '提交内容不完整或链接无效，请检查后重试。', 'onedong' ) ),
	'error'        => array( 'error', __( '提交失败，请稍后再试。', 'onedong' ) ),
);
?>

<div class="resources-page resources-submit-page">
	<div class="resources-submit-container">
		<a class="resources-submit-back" href="<?php echo esc_url( get_post_type_archive_link( 'onedong_resource' ) ); ?>">&larr; <?php esc_html_e( '返回资源导航', 'onedong' ); ?></a>
		<header class="resources-submit-header">
			<span class="resources-submit-header__icon" aria-hidden="true"><?php onedong_icon( 'plus' ); ?></span>
			<div>
				<h1><?php esc_html_e( '提交资源', 'onedong' ); ?></h1>
				<p><?php esc_html_e( '分享你认为值得收藏的工具或网站，审核通过后会出现在资源导航中。', 'onedong' ); ?></p>
			</div>
		</header>

		<?php if ( isset( $messages[ $result ] ) ) : ?>
			<div class="resources-submit-notice resources-submit-notice--<?php echo esc_attr( $messages[ $result ][0] ); ?>" role="status">
				<?php echo esc_html( $messages[ $result ][1] ); ?>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="resources-submit-form">
			<input type="hidden" name="action" value="onedong_resource_submit">
			<?php wp_nonce_field( 'onedong_resource_submit', 'onedong_resource_submit_nonce' ); ?>
			<div class="resources-submit-honeypot" aria-hidden="true">
				<label for="onedong_resource_website"><?php esc_html_e( '网站', 'onedong' ); ?></label>
				<input type="text" id="onedong_resource_website" name="onedong_resource_website" tabindex="-1" autocomplete="off">
			</div>

			<div class="form-field">
				<label for="onedong_resource_title"><?php esc_html_e( '资源名称', 'onedong' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input type="text" id="onedong_resource_title" name="onedong_resource_title" maxlength="120" required autocomplete="organization">
			</div>

			<div class="form-field">
				<label for="onedong_resource_url"><?php esc_html_e( '资源网址', 'onedong' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input type="url" id="onedong_resource_url" name="onedong_resource_url" required placeholder="https://example.com" inputmode="url">
			</div>

			<div class="form-field">
				<label for="onedong_resource_cat"><?php esc_html_e( '所属分类', 'onedong' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<select id="onedong_resource_cat" name="onedong_resource_cat" required>
					<option value=""><?php esc_html_e( '请选择分类', 'onedong' ); ?></option>
					<?php
					$cats = get_terms(
						array(
							'taxonomy'   => 'onedong_resource_cat',
							'hide_empty' => false,
							'orderby'    => 'name',
							'order'      => 'ASC',
						)
					);
					if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) :
						foreach ( $cats as $cat ) :
							if ( '0' === get_term_meta( $cat->term_id, '_onedong_rescat_enabled', true ) ) {
								continue;
							}
							?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
							<?php
						endforeach;
					endif;
					?>
				</select>
			</div>

			<div class="form-field">
				<label for="onedong_resource_description"><?php esc_html_e( '资源描述', 'onedong' ); ?></label>
				<textarea id="onedong_resource_description" name="onedong_resource_description" rows="5" maxlength="1000" placeholder="<?php esc_attr_e( '简单介绍它解决什么问题、为什么值得推荐。', 'onedong' ); ?>"></textarea>
				<p class="form-hint"><?php esc_html_e( '可选，建议用一两句话说明核心用途。', 'onedong' ); ?></p>
			</div>

			<div class="form-field">
				<label for="onedong_resource_icon_url"><?php esc_html_e( '图标网址', 'onedong' ); ?></label>
				<input type="url" id="onedong_resource_icon_url" name="onedong_resource_icon_url" placeholder="https://example.com/icon.png" inputmode="url">
				<p class="form-hint"><?php esc_html_e( '可选，留空将使用系统默认图标。', 'onedong' ); ?></p>
			</div>

			<div class="form-actions">
				<button type="submit" class="resources-submit-button"><?php esc_html_e( '提交审核', 'onedong' ); ?></button>
				<p><?php esc_html_e( '提交后不会立即公开，请勿重复提交。', 'onedong' ); ?></p>
			</div>
		</form>
	</div>
</div>

<?php get_footer(); ?>
