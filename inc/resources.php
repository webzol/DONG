<?php
/**
 * 资源导航模块(onedong_resource)· v1.0.0
 *
 * CPT onedong_resource + 分类 onedong_resource_cat + 全屏通栏 Banner + 分类筛选 + 卡片网格。
 * 全部后台可视化配置(导航名 / Banner 三模式 / 标题文案 / 分类 / 资源),零代码。
 * 前台 /resources/,JS 前端 DOM 过滤(无刷新筛选)。结构对齐 inc/moments.php。
 *
 * @package OneDong
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================
 * 0. 默认配置 + 取值辅助
 * ============================================================ */
function onedong_resources_defaults() {
	return array(
		'nav_label'             => __( '资源导航', 'onedong' ),
		'banner_mode'           => 'default',
		'banner_color'          => '#3858F6',
		'banner_gradient_from'  => '#3858F6',
		'banner_gradient_to'    => '#2b47d1',
		'banner_gradient_angle' => 90,
		'banner_height'         => 280,
		'banner_image'          => 0,
		'banner_top_gap'        => 0,
		'banner_animate'        => '1',
		'card_radius'           => '',
		'banner_card'           => '0',
		'banner_card_radius'    => '',
		'banner_radius'         => '',
		'banner_opacity'        => 100,
		'banner_title'          => __( '资源导航', 'onedong' ),
		'banner_subtitle'       => __( '精选优质资源,持续更新。', 'onedong' ),
	);
}

/** 合并默认值后返回当前页面配置。 */
function onedong_resources_opts() {
	return wp_parse_args( get_option( 'onedong_resources_settings', array() ), onedong_resources_defaults() );
}


/* ============================================================
 * 1. 注册「资源」CPT + 「资源分类」taxonomy + 图标尺寸
 * ============================================================ */
function onedong_register_resource_cpt() {
	register_post_type(
		'onedong_resource',
		array(
			'labels'              => array(
				'name'               => __( '资源', 'onedong' ),
				'singular_name'      => __( '资源', 'onedong' ),
				'add_new'            => __( '添加资源', 'onedong' ),
				'add_new_item'       => __( '添加资源', 'onedong' ),
				'edit_item'          => __( '编辑资源', 'onedong' ),
				'new_item'           => __( '新资源', 'onedong' ),
				'view_item'          => __( '查看', 'onedong' ),
				'search_items'       => __( '搜索资源', 'onedong' ),
				'not_found'          => __( '暂无资源', 'onedong' ),
				'not_found_in_trash' => __( '回收站无资源', 'onedong' ),
				'all_items'          => __( '全部资源', 'onedong' ),
				'menu_name'          => __( '资源导航', 'onedong' ),
			),
			'public'              => true,
			'has_archive'         => 'resources',
			'publicly_queryable'  => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-screenoptions',
			'menu_position'       => 7,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor' ), // 标题=资源名,正文=介绍
			'rewrite'             => array( 'slug' => 'resources', 'with_front' => false ),
			'show_in_menu'        => 'onedong-resources', // 挂到自建顶级菜单
		)
	);

	register_taxonomy(
		'onedong_resource_cat',
		array( 'onedong_resource' ),
		array(
			'labels'            => array(
				'name'              => __( '资源分类', 'onedong' ),
				'singular_name'     => __( '资源分类', 'onedong' ),
				'search_items'      => __( '搜索分类', 'onedong' ),
				'all_items'         => __( '全部资源分类', 'onedong' ),
				'parent_item'       => __( '父级分类', 'onedong' ),
				'parent_item_colon' => __( '父级分类:', 'onedong' ),
				'edit_item'         => __( '编辑分类', 'onedong' ),
				'update_item'       => __( '更新分类', 'onedong' ),
				'add_new_item'      => __( '添加新分类', 'onedong' ),
				'new_item_name'     => __( '新分类名称', 'onedong' ),
				'menu_name'         => __( '资源分类', 'onedong' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => false,
			'rewrite'           => array( 'slug' => 'resource-category' ),
		)
	);

	add_image_size( 'onedong-resource-icon', 96, 96, true ); // 卡片图标小尺寸(省流)

	if ( ! get_option( 'onedong_res_flushed' ) ) {
		flush_rewrite_rules();
		update_option( 'onedong_res_flushed', 1 );
	}
}
add_action( 'init', 'onedong_register_resource_cpt' );
add_action( 'after_switch_theme', 'flush_rewrite_rules' );


/* ============================================================
 * 2. 后台:资源编辑 meta box(网址 / 分类 / 排序 / 启停 / 图标三模式)
 * ============================================================ */
function onedong_resource_add_meta_box() {
	add_meta_box( 'onedong_resource_meta', __( '资源信息', 'onedong' ), 'onedong_resource_meta_box_cb', 'onedong_resource', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'onedong_resource_add_meta_box' );

function onedong_resource_meta_box_cb( $post ) {
	wp_nonce_field( 'onedong_resource_save', 'onedong_resource_nonce' );
	$url      = get_post_meta( $post->ID, '_onedong_resource_url', true );
	$order    = (int) get_post_meta( $post->ID, '_onedong_resource_order', true );
	$enabled  = get_post_meta( $post->ID, '_onedong_resource_enabled', true );
	$mode     = get_post_meta( $post->ID, '_onedong_resource_icon_mode', true );
	$mode     = $mode ? $mode : 'default';
	$icon_id  = (int) get_post_meta( $post->ID, '_onedong_resource_icon_id', true );
	$icon_url = get_post_meta( $post->ID, '_onedong_resource_icon_url', true );
	$cur_cats = get_the_terms( $post->ID, 'onedong_resource_cat' );
	$cur_cat  = ( $cur_cats && ! is_wp_error( $cur_cats ) ) ? (int) $cur_cats[0]->term_id : 0;
	$cats     = get_terms( array( 'taxonomy' => 'onedong_resource_cat', 'hide_empty' => false ) );
	?>
	<p><label><strong><?php esc_html_e( '资源网址', 'onedong' ); ?></strong></label>
		<input type="url" name="onedong_resource_url" value="<?php echo esc_attr( $url ); ?>" class="widefat" required placeholder="https://"></p>

	<p><label><strong><?php esc_html_e( '所属分类', 'onedong' ); ?></strong></label>
		<select name="onedong_resource_cat" class="widefat">
			<option value="0"><?php esc_html_e( '— 选择分类 —', 'onedong' ); ?></option>
			<?php if ( ! is_wp_error( $cats ) ) : foreach ( $cats as $c ) : ?>
				<option value="<?php echo esc_attr( $c->term_id ); ?>" <?php selected( $cur_cat, (int) $c->term_id ); ?>><?php echo esc_html( $c->name ); ?></option>
			<?php endforeach; endif; ?>
		</select></p>

	<p><label><strong><?php esc_html_e( '排序权重', 'onedong' ); ?></strong>
			<input type="number" name="onedong_resource_order" value="<?php echo esc_attr( $order ); ?>"></label>
		<span class="description"><?php esc_html_e( '数字越大越靠前。', 'onedong' ); ?></span></p>

	<p><label><input type="checkbox" name="onedong_resource_enabled" value="1" <?php checked( $enabled !== '0' ); ?>>
			<?php esc_html_e( '启用(取消则前台隐藏)', 'onedong' ); ?></label></p>

	<h4 style="margin:1.2em 0 .4em;"><?php esc_html_e( '资源图标(三选一)', 'onedong' ); ?></h4>
	<div class="res-icon-mode">
		<?php
		foreach ( array(
			'default' => __( '系统默认', 'onedong' ),
			'upload'  => __( '本地上传', 'onedong' ),
			'remote'  => __( '远程 URL', 'onedong' ),
		) as $k => $label ) :
			?>
			<label style="margin-right:1em;"><input type="radio" name="onedong_resource_icon_mode" value="<?php echo esc_attr( $k ); ?>" <?php checked( $mode, $k ); ?>> <?php echo esc_html( $label ); ?></label>
		<?php endforeach; ?>
	</div>

	<div class="res-icon-field res-icon-field--default" <?php echo $mode !== 'default' ? 'style="display:none;"' : ''; ?>>
		<p class="description"><?php esc_html_e( '将使用统一的系统默认图标。', 'onedong' ); ?></p>
	</div>
	<div class="res-icon-field res-icon-field--upload" <?php echo $mode !== 'upload' ? 'style="display:none;"' : ''; ?>>
		<div class="res-icon-preview"><?php echo $icon_id && wp_attachment_is_image( $icon_id ) ? wp_get_attachment_image( $icon_id, 'thumbnail' ) : ''; ?></div>
		<button type="button" class="button" id="res-icon-add"><?php esc_html_e( '从媒体库选择', 'onedong' ); ?></button>
		<button type="button" class="button" id="res-icon-remove" <?php echo $icon_id ? '' : 'style="display:none;"'; ?>><?php esc_html_e( '移除', 'onedong' ); ?></button>
		<input type="hidden" name="onedong_resource_icon_id" id="res-icon-id" value="<?php echo esc_attr( $icon_id ); ?>">
	</div>
	<div class="res-icon-field res-icon-field--remote" <?php echo $mode !== 'remote' ? 'style="display:none;"' : ''; ?>>
		<input type="url" name="onedong_resource_icon_url" value="<?php echo esc_attr( $icon_url ); ?>" class="widefat" placeholder="https://...">
	</div>
	<?php
}

function onedong_resource_save( $post_id ) {
	if ( ! isset( $_POST['onedong_resource_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['onedong_resource_nonce'] ) ), 'onedong_resource_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// 网址(必填,完整 URL)
	if ( isset( $_POST['onedong_resource_url'] ) ) {
		update_post_meta( $post_id, '_onedong_resource_url', esc_url_raw( wp_unslash( $_POST['onedong_resource_url'] ) ) );
	}
	// 排序权重
	update_post_meta( $post_id, '_onedong_resource_order', isset( $_POST['onedong_resource_order'] ) ? (int) $_POST['onedong_resource_order'] : 0 );
	// 启停
	update_post_meta( $post_id, '_onedong_resource_enabled', isset( $_POST['onedong_resource_enabled'] ) ? '1' : '0' );
	// 图标模式
	$mode = isset( $_POST['onedong_resource_icon_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['onedong_resource_icon_mode'] ) ) : 'default';
	if ( ! in_array( $mode, array( 'default', 'upload', 'remote' ), true ) ) {
		$mode = 'default';
	}
	update_post_meta( $post_id, '_onedong_resource_icon_mode', $mode );
	// 上传图 ID(必须为图片附件)
	$iid = isset( $_POST['onedong_resource_icon_id'] ) ? absint( $_POST['onedong_resource_icon_id'] ) : 0;
	update_post_meta( $post_id, '_onedong_resource_icon_id', ( $iid && wp_attachment_is_image( $iid ) ) ? $iid : 0 );
	// 远程图 URL
	if ( isset( $_POST['onedong_resource_icon_url'] ) ) {
		update_post_meta( $post_id, '_onedong_resource_icon_url', esc_url_raw( wp_unslash( $_POST['onedong_resource_icon_url'] ) ) );
	}
	// 分类(单选绑定)
	if ( isset( $_POST['onedong_resource_cat'] ) ) {
		$cat = absint( $_POST['onedong_resource_cat'] );
		if ( $cat ) {
			$t = get_term( $cat, 'onedong_resource_cat' );
			if ( $t && ! is_wp_error( $t ) ) {
				wp_set_object_terms( $post_id, array( $cat ), 'onedong_resource_cat', false );
			}
		} else {
			wp_set_object_terms( $post_id, array(), 'onedong_resource_cat', false );
		}
	}
}
add_action( 'save_post_onedong_resource', 'onedong_resource_save' );


/* ============================================================
 * 3. 后台资源:媒体上传器 + 取色器 + 图标/模式切换 JS/CSS
 * ============================================================ */
function onedong_resource_admin_assets() {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen     = get_current_screen();
	$is_res     = $screen && 'onedong_resource' === $screen->post_type;
	$is_settings = isset( $_GET['page'] ) && 'onedong-resources-settings' === $_GET['page']; // phpcs:ignore
	if ( ! $is_res && ! $is_settings ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'onedong-resource-admin', ONEDONG_URI . '/assets/js/resource-admin.js', array( 'jquery', 'wp-color-picker' ), ONEDONG_VERSION, true );
	wp_enqueue_style( 'onedong-resource-admin', ONEDONG_URI . '/assets/css/resource-admin.css', array(), ONEDONG_VERSION );
	wp_localize_script(
		'onedong-resource-admin',
		'onedongResAdmin',
		array( 'title' => __( '选择资源图标', 'onedong' ) )
	);
}
add_action( 'admin_enqueue_scripts', 'onedong_resource_admin_assets' );


/* ============================================================
 * 4. 资源分类:term meta(排序权重 + 启停)+ 列表列
 * ============================================================ */
function onedong_rescat_add_fields() {
	wp_nonce_field( 'onedong_rescat_save', 'onedong_rescat_nonce' );
	?>
	<div class="form-field">
		<label><?php esc_html_e( '排序权重', 'onedong' ); ?></label>
		<input type="number" name="onedong_rescat_order" value="0">
		<p class="description"><?php esc_html_e( '数字越小越靠前。', 'onedong' ); ?></p>
	</div>
	<div class="form-field">
		<label><input type="checkbox" name="onedong_rescat_enabled" value="1" checked> <?php esc_html_e( '启用此分类', 'onedong' ); ?></label>
		<p class="description"><?php esc_html_e( '禁用后前台不显示,该分类下资源也不展示。', 'onedong' ); ?></p>
	</div>
	<?php
}
add_action( 'onedong_resource_cat_add_form_fields', 'onedong_rescat_add_fields' );

function onedong_rescat_edit_fields( $term ) {
	wp_nonce_field( 'onedong_rescat_save', 'onedong_rescat_nonce' );
	$order   = (int) get_term_meta( $term->term_id, '_onedong_rescat_order', true );
	$enabled = get_term_meta( $term->term_id, '_onedong_rescat_enabled', true );
	?>
	<tr class="form-field">
		<th scope="row"><label><?php esc_html_e( '排序权重', 'onedong' ); ?></label></th>
		<td><input type="number" name="onedong_rescat_order" value="<?php echo esc_attr( $order ); ?>">
			<p class="description"><?php esc_html_e( '数字越小越靠前。', 'onedong' ); ?></p></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><?php esc_html_e( '启用', 'onedong' ); ?></th>
		<td><label><input type="checkbox" name="onedong_rescat_enabled" value="1" <?php echo $enabled === '0' ? '' : 'checked'; ?>> <?php esc_html_e( '启用此分类', 'onedong' ); ?></label>
			<p class="description"><?php esc_html_e( '禁用后前台不显示,该分类下资源也不展示。', 'onedong' ); ?></p></td>
	</tr>
	<?php
}
add_action( 'onedong_resource_cat_edit_form_fields', 'onedong_rescat_edit_fields' );

function onedong_rescat_save( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( ! isset( $_POST['onedong_rescat_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['onedong_rescat_nonce'] ) ), 'onedong_rescat_save' ) ) {
		return;
	}
	$order   = isset( $_POST['onedong_rescat_order'] ) ? (int) $_POST['onedong_rescat_order'] : 0;
	$enabled = isset( $_POST['onedong_rescat_enabled'] ) ? '1' : '0';
	update_term_meta( $term_id, '_onedong_rescat_order', $order );
	update_term_meta( $term_id, '_onedong_rescat_enabled', $enabled );
}
add_action( 'created_onedong_resource_cat', 'onedong_rescat_save' );
add_action( 'edited_onedong_resource_cat', 'onedong_rescat_save' );

/** 分类列表:加「排序」「状态」列。 */
function onedong_rescat_columns( $columns ) {
	$new = array();
	foreach ( $columns as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'name' === $k ) {
			$new['rescat_order']   = __( '排序', 'onedong' );
			$new['rescat_enabled'] = __( '状态', 'onedong' );
		}
	}
	return $new;
}
add_filter( 'manage_edit-onedong_resource_cat_columns', 'onedong_rescat_columns' );

function onedong_rescat_column( $content, $column_name, $term_id ) {
	if ( 'rescat_order' === $column_name ) {
		return (string) (int) get_term_meta( $term_id, '_onedong_rescat_order', true );
	}
	if ( 'rescat_enabled' === $column_name ) {
		$e = get_term_meta( $term_id, '_onedong_rescat_enabled', true );
		return $e === '0'
			? '<span style="color:#999;">' . esc_html__( '禁用', 'onedong' ) . '</span>'
			: '<span style="color:#46b450;">' . esc_html__( '启用', 'onedong' ) . '</span>';
	}
	return $content;
}
add_filter( 'manage_onedong_resource_cat_custom_column', 'onedong_rescat_column', 10, 3 );

/** 有资源绑定的分类禁止删除。 */
function onedong_rescat_pre_delete( $term_id, $tt_id, $taxonomy ) {
	if ( 'onedong_resource_cat' !== $taxonomy ) {
		return;
	}
	$t = get_term( $term_id );
	if ( $t && ! is_wp_error( $t ) && $t->count > 0 ) {
		wp_die( sprintf( esc_html__( '该分类下还有 %d 个资源,无法删除。请先迁移或删除这些资源。', 'onedong' ), (int) $t->count ) );
	}
}
add_action( 'pre_delete_term', 'onedong_rescat_pre_delete', 10, 3 );


/* ============================================================
 * 5. 后台:顶级菜单「资源导航」+ 子页「页面设置」
 * ============================================================ */
function onedong_resources_admin_menu() {
	add_menu_page( __( '资源导航', 'onedong' ), __( '资源导航', 'onedong' ), 'manage_options', 'onedong-resources', 'onedong_resources_settings_page_cb', 'dashicons-screenoptions', 7 );
	add_submenu_page( 'onedong-resources', __( '页面设置', 'onedong' ), __( '页面设置', 'onedong' ), 'manage_options', 'onedong-resources-settings', 'onedong_resources_settings_page_cb' );
}
add_action( 'admin_menu', 'onedong_resources_admin_menu' );

function onedong_resources_settings_page_cb() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( '资源导航 · 页面设置', 'onedong' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'onedong_resources_group' );
			do_settings_sections( 'onedong-resources-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}


/* ============================================================
 * 6. Settings API:页面配置注册 + sanitize + 字段回调
 * ============================================================ */
function onedong_resources_settings_init() {
	register_setting( 'onedong_resources_group', 'onedong_resources_settings', 'onedong_resources_sanitize' );

	$page = 'onedong-resources-settings';
	add_settings_section( 'onedong_resources_nav_section', __( '导航', 'onedong' ), '__return_false', $page );
	add_settings_section( 'onedong_resources_banner_section', __( '顶部 Banner', 'onedong' ), '__return_false', $page );
	add_settings_section( 'onedong_resources_text_section', __( '文案', 'onedong' ), '__return_false', $page );
	add_settings_section( 'onedong_resources_card_section', __( '卡片样式', 'onedong' ), '__return_false', $page );

	add_settings_field( 'nav_label', __( '导航菜单名称', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_nav_section', array( 'key' => 'nav_label', 'type' => 'text', 'desc' => __( '为空则不在导航显示。', 'onedong' ) ) );

	add_settings_field( 'banner_mode', __( '背景模式', 'onedong' ), 'onedong_resources_field_mode_cb', $page, 'onedong_resources_banner_section' );
	add_settings_field( 'banner_image', __( '背景图片', 'onedong' ), 'onedong_resources_field_image_cb', $page, 'onedong_resources_banner_section' );
	add_settings_field( 'banner_color', __( '自定义纯色', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_color', 'type' => 'color', 'wrap_class' => 'res-banner-dep res-banner-dep--solid' ) );
	add_settings_field( 'banner_gradient_from', __( '渐变起点色', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_gradient_from', 'type' => 'color', 'wrap_class' => 'res-banner-dep res-banner-dep--gradient' ) );
	add_settings_field( 'banner_gradient_to', __( '渐变终点色', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_gradient_to', 'type' => 'color', 'wrap_class' => 'res-banner-dep res-banner-dep--gradient' ) );
	add_settings_field( 'banner_gradient_angle', __( '渐变角度 / 方向', 'onedong' ), 'onedong_resources_field_angle_cb', $page, 'onedong_resources_banner_section' );
	add_settings_field( 'banner_height', __( 'Banner 高度(px)', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_height', 'type' => 'number', 'min' => 120, 'max' => 600, 'desc' => __( '移动端会自动缩小。', 'onedong' ) ) );
	add_settings_field( 'banner_top_gap', __( '与顶部导航间距(px)', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_top_gap', 'type' => 'number', 'min' => 0, 'max' => 200, 'desc' => __( 'Banner 与上方菜单的留白,0 为紧贴。', 'onedong' ) ) );
	add_settings_field( 'banner_animate', __( '背景动效', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_animate', 'type' => 'checkbox', 'desc' => __( '渐变流动 / 图片呼吸缩放(自动尊重系统「减少动态效果」设置)。', 'onedong' ) ) );
	add_settings_field( 'banner_card', __( '内容卡片', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_card', 'type' => 'checkbox', 'desc' => __( '把标题 / 副标题放进半透明卡片(玻璃磨砂),增强在彩色 / 图片背景上的层次与可读性。', 'onedong' ) ) );
	add_settings_field( 'banner_card_radius', __( '内容卡片圆角', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_card_radius', 'type' => 'select', 'options' => array(
		''    => __( '跟随网站(默认)', 'onedong' ),
		'0'   => __( '直角(0px)', 'onedong' ),
		'8'   => __( '小(8px)', 'onedong' ),
		'16'  => __( '中(16px)', 'onedong' ),
		'24'  => __( '大(24px)', 'onedong' ),
		'999' => __( '药丸', 'onedong' ),
	) ) );
	add_settings_field( 'banner_radius', __( 'Banner 圆角', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_radius', 'type' => 'select', 'options' => array(
		''     => __( '直角(默认)', 'onedong' ),
		'site' => __( '跟随网站', 'onedong' ),
		'8'    => __( '小(8px)', 'onedong' ),
		'16'   => __( '中(16px)', 'onedong' ),
		'24'   => __( '大(24px)', 'onedong' ),
		'999'  => __( '药丸', 'onedong' ),
	) ) );
	add_settings_field( 'banner_opacity', __( '背景透明度(纯色)', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_banner_section', array( 'key' => 'banner_opacity', 'type' => 'number', 'min' => 0, 'max' => 100, 'desc' => __( '0-100,仅「系统默认 / 自定义纯色」生效;100 为不透明。', 'onedong' ) ) );

	add_settings_field( 'banner_title', __( '主标题', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_text_section', array( 'key' => 'banner_title', 'type' => 'text' ) );
	add_settings_field( 'banner_subtitle', __( '副标题', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_text_section', array( 'key' => 'banner_subtitle', 'type' => 'textarea' ) );
	add_settings_field( 'card_radius', __( '卡片圆角', 'onedong' ), 'onedong_resources_field_cb', $page, 'onedong_resources_card_section', array( 'key' => 'card_radius', 'type' => 'select', 'options' => array(
		''    => __( '跟随网站(默认)', 'onedong' ),
		'0'   => __( '直角(0px)', 'onedong' ),
		'6'   => __( '小(6px)', 'onedong' ),
		'12'  => __( '中(12px)', 'onedong' ),
		'20'  => __( '大(20px)', 'onedong' ),
		'999' => __( '药丸', 'onedong' ),
	) ) );
}
add_action( 'admin_init', 'onedong_resources_settings_init' );

/** 通用字段回调:text / number / color / textarea。 */
function onedong_resources_field_cb( $args ) {
	$o    = onedong_resources_opts();
	$key  = $args['key'];
	$type = $args['type'];
	$val  = isset( $o[ $key ] ) ? $o[ $key ] : '';
	$wrap = ! empty( $args['wrap_class'] ) ? ' class="' . esc_attr( $args['wrap_class'] ) . '"' : '';
	echo '<div' . $wrap . '>';
	if ( 'textarea' === $type ) {
		printf( '<textarea name="onedong_resources_settings[%1$s]" rows="3" class="large-text">%2$s</textarea>', esc_attr( $key ), esc_textarea( $val ) );
	} elseif ( 'number' === $type ) {
		$min = isset( $args['min'] ) ? ' min="' . esc_attr( $args['min'] ) . '"' : '';
		$max = isset( $args['max'] ) ? ' max="' . esc_attr( $args['max'] ) . '"' : '';
		printf( '<input type="number" name="onedong_resources_settings[%1$s]" value="%2$s"%3$s%4$s class="regular-text">', esc_attr( $key ), esc_attr( $val ), $min, $max );
	} elseif ( 'color' === $type ) {
		printf( '<input type="text" name="onedong_resources_settings[%1$s]" value="%2$s" class="res-color-picker" data-default-color="#3858F6">', esc_attr( $key ), esc_attr( $val ) );
	} elseif ( 'select' === $type ) {
		echo '<select name="onedong_resources_settings[' . esc_attr( $key ) . ']">';
		foreach ( $args['options'] as $ov => $ol ) {
			echo '<option value="' . esc_attr( $ov ) . '" ' . selected( (string) $val, (string) $ov, false ) . '>' . esc_html( $ol ) . '</option>';
		}
		echo '</select>';
	} elseif ( 'checkbox' === $type ) {
		printf( '<label><input type="checkbox" name="onedong_resources_settings[%1$s]" value="1" %2$s></label>', esc_attr( $key ), checked( (string) $val, '1', false ) );
	} else {
		printf( '<input type="text" name="onedong_resources_settings[%1$s]" value="%2$s" class="regular-text">', esc_attr( $key ), esc_attr( $val ) );
	}
	if ( ! empty( $args['desc'] ) ) {
		echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
	}
	echo '</div>';
}

/** Banner 模式三选。 */
function onedong_resources_field_mode_cb() {
	$o    = onedong_resources_opts();
	$name = 'onedong_resources_settings[banner_mode]';
	foreach ( array(
		'default'  => __( '系统默认(品牌蓝)', 'onedong' ),
		'solid'    => __( '自定义纯色', 'onedong' ),
		'gradient' => __( '自定义渐变', 'onedong' ),
		'image'    => __( '上传图片', 'onedong' ),
	) as $k => $label ) {
		printf( '<label style="margin-right:1.2em;"><input type="radio" name="%1$s" value="%2$s" %3$s> %4$s</label>', esc_attr( $name ), esc_attr( $k ), checked( $o['banner_mode'], $k, false ), esc_html( $label ) );
	}
	echo '<p class="description">' . esc_html__( '纯颜色或上传图片作背景。', 'onedong' ) . '</p>';
}

/** Banner 背景图片(上传模式)。 */
function onedong_resources_field_image_cb() {
	$o   = onedong_resources_opts();
	$id  = (int) $o['banner_image'];
	$src = $id ? wp_get_attachment_image_src( $id, 'large' ) : false;
	echo '<div class="res-banner-dep res-banner-dep--image">';
	echo '<div class="res-banner-image-preview">';
	if ( $src ) {
		echo '<img src="' . esc_url( $src[0] ) . '" alt="">';
	}
	echo '</div>';
	echo '<button type="button" class="button" id="res-banner-image-add">' . esc_html__( '上传 / 选择图片', 'onedong' ) . '</button> ';
	echo '<button type="button" class="button" id="res-banner-image-remove"' . ( $id ? '' : ' style="display:none;"' ) . '>' . esc_html__( '移除', 'onedong' ) . '</button>';
	echo '<input type="hidden" name="onedong_resources_settings[banner_image]" id="res-banner-image-id" value="' . esc_attr( $id ) . '">';
	echo '<p class="description">' . esc_html__( '图片将自适应铺满 Banner 背景(cover)。', 'onedong' ) . '</p>';
	echo '</div>';
}

/** 渐变角度 + 方向预设。 */
function onedong_resources_field_angle_cb() {
	$o = onedong_resources_opts();
	$a = (int) $o['banner_gradient_angle'];
	echo '<div class="res-banner-dep res-banner-dep--gradient">';
	printf( '<input type="number" name="onedong_resources_settings[banner_gradient_angle]" value="%1$d" min="0" max="360" class="small-text"> ', $a );
	echo '<span class="description">' . esc_html__( '或选方向:', 'onedong' ) . '</span> ';
	echo '<select class="res-angle-preset">';
	echo '<option value="">' . esc_html__( '— 角度 —', 'onedong' ) . '</option>';
	foreach ( array( 0 => '↑', 45 => '↗', 90 => '→', 135 => '↘', 180 => '↓', 225 => '↙', 270 => '←', 315 => '↖' ) as $deg => $arrow ) {
		printf( '<option value="%1$d" %2$s>%1$d° %3$s</option>', $deg, selected( $a, $deg, false ), $arrow );
	}
	echo '</select>';
	echo '</div>';
}

function onedong_resources_sanitize( $in ) {
	$out                       = onedong_resources_defaults();
	$out['nav_label']          = isset( $in['nav_label'] ) ? sanitize_text_field( $in['nav_label'] ) : '';
	$mode                      = isset( $in['banner_mode'] ) ? $in['banner_mode'] : 'default';
	$out['banner_mode']        = in_array( $mode, array( 'default', 'solid', 'gradient', 'image' ), true ) ? $mode : 'default';
	$out['banner_image']       = isset( $in['banner_image'] ) ? absint( $in['banner_image'] ) : 0;
	$out['banner_top_gap']     = max( 0, min( 200, (int) ( isset( $in['banner_top_gap'] ) ? $in['banner_top_gap'] : 0 ) ) );
	$out['banner_animate']     = isset( $in['banner_animate'] ) ? '1' : '0';
	$cr                        = isset( $in['card_radius'] ) ? $in['card_radius'] : '';
	$out['card_radius']        = in_array( $cr, array( '', '0', '6', '12', '20', '999' ), true ) ? $cr : '';
	$out['banner_card']        = isset( $in['banner_card'] ) ? '1' : '0';
	$bcr                       = isset( $in['banner_card_radius'] ) ? $in['banner_card_radius'] : '';
	$out['banner_card_radius'] = in_array( $bcr, array( '', '0', '8', '16', '24', '999' ), true ) ? $bcr : '';
	$brr                       = isset( $in['banner_radius'] ) ? $in['banner_radius'] : '';
	$out['banner_radius']      = in_array( $brr, array( '', '0', '8', '16', '24', '999', 'site' ), true ) ? $brr : '';
	$out['banner_opacity']     = max( 0, min( 100, (int) ( isset( $in['banner_opacity'] ) ? $in['banner_opacity'] : 100 ) ) );
	$out['banner_color']       = sanitize_hex_color( isset( $in['banner_color'] ) ? $in['banner_color'] : '' ) ? : $out['banner_color'];
	$out['banner_gradient_from'] = sanitize_hex_color( isset( $in['banner_gradient_from'] ) ? $in['banner_gradient_from'] : '' ) ? : $out['banner_gradient_from'];
	$out['banner_gradient_to']   = sanitize_hex_color( isset( $in['banner_gradient_to'] ) ? $in['banner_gradient_to'] : '' ) ? : $out['banner_gradient_to'];
	$out['banner_gradient_angle'] = max( 0, min( 360, (int) ( isset( $in['banner_gradient_angle'] ) ? $in['banner_gradient_angle'] : 90 ) ) );
	$out['banner_height']      = max( 120, min( 600, (int) ( isset( $in['banner_height'] ) ? $in['banner_height'] : 280 ) ) );
	$out['banner_title']       = isset( $in['banner_title'] ) ? sanitize_text_field( $in['banner_title'] ) : $out['banner_title'];
	$out['banner_subtitle']    = isset( $in['banner_subtitle'] ) ? wp_kses_post( $in['banner_subtitle'] ) : '';
	return $out;
}


/* ============================================================
 * 7. 前台查询:排除禁用资源 + 禁用分类下的资源 + 按权重排序
 * ============================================================ */
function onedong_resource_pre_get_posts( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_post_type_archive( 'onedong_resource' ) ) {
		return;
	}
	$q->set( 'posts_per_page', -1 );
	$q->set( 'meta_key', '_onedong_resource_order' );
	$q->set( 'orderby', array( 'meta_value_num' => 'DESC', 'title' => 'ASC' ) );
	$q->set( 'meta_query', array(
		array( 'key' => '_onedong_resource_enabled', 'value' => '1', 'compare' => '=' ),
	) );
	// 排除挂在「禁用分类」下的资源
	$disabled = get_terms( array(
		'taxonomy'   => 'onedong_resource_cat',
		'hide_empty' => false,
		'fields'     => 'ids',
		'meta_query' => array( array( 'key' => '_onedong_rescat_enabled', 'value' => '0', 'compare' => '=' ) ),
	) );
	if ( ! is_wp_error( $disabled ) && ! empty( $disabled ) ) {
		$q->set( 'tax_query', array(
			array(
				'taxonomy' => 'onedong_resource_cat',
				'terms'    => $disabled,
				'operator' => 'NOT IN',
			),
		) );
	}
}
add_action( 'pre_get_posts', 'onedong_resource_pre_get_posts' );


/* ============================================================
 * 8. 前台渲染辅助:Banner / 筛选栏 / 卡片
 * ============================================================ */

/** 取「启用(默认启用,仅排除显式禁用)+ 按权重升序」的分类(先查再 usort,get_terms 不支持 orderby term meta)。
 *  与后台语义一致:资源 / 分类「不是 '0' 即启用」。老分类未设 _onedong_rescat_enabled meta 同样视为启用,
 *  修复「筛选栏只有『全部』、其他分类不显示」(此前严格匹配 enabled='1' 漏掉未设 meta 的分类)。 */
function onedong_resource_get_cats() {
	$cats = get_terms( array(
		'taxonomy'   => 'onedong_resource_cat',
		'hide_empty' => false,
		'meta_query' => array(
			'relation' => 'OR',
			array( 'key' => '_onedong_rescat_enabled', 'value' => '0', 'compare' => '!=' ),
			array( 'key' => '_onedong_rescat_enabled', 'compare' => 'NOT EXISTS' ),
		),
	) );
	if ( is_wp_error( $cats ) || empty( $cats ) ) {
		return array();
	}
	usort( $cats, function ( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, '_onedong_rescat_order', true );
		$ob = (int) get_term_meta( $b->term_id, '_onedong_rescat_order', true );
		if ( $oa === $ob ) {
			return strcmp( $a->name, $b->name );
		}
		return $oa <=> $ob;
	} );
	return $cats;
}

/** 生成 Banner 内联 style(纯颜色,绝不输出图片)。 */
function onedong_resource_banner_style() {
	$o   = onedong_resources_opts();
	$h   = max( 120, min( 600, (int) $o['banner_height'] ) );
	$gap = max( 0, min( 200, (int) $o['banner_top_gap'] ) );
	$brr  = $o['banner_radius'];
	if ( 'site' === $brr ) {
		$brad = 'var(--radius-large)';
	} else {
		$brad = '' === $brr ? '0px' : ( ( '999' === $brr ) ? '999px' : ( (int) $brr ) . 'px' );
	}
	$op  = max( 0, min( 100, (int) $o['banner_opacity'] ) );
	$bg  = 'var(--primary)';
	switch ( $o['banner_mode'] ) {
		case 'solid':
			$c   = sanitize_hex_color( $o['banner_color'] );
			$bg  = $c ? $c : 'var(--primary)';
			break;
		case 'gradient':
			$from = sanitize_hex_color( $o['banner_gradient_from'] ) ? : '#3858F6';
			$to   = sanitize_hex_color( $o['banner_gradient_to'] ) ? : '#2b47d1';
			$ang  = max( 0, min( 360, (int) $o['banner_gradient_angle'] ) );
			$bg   = sprintf( 'linear-gradient(%ddeg, %s, %s)', $ang, $from, $to );
			break;
		case 'image':
			$iid = (int) $o['banner_image'];
			if ( $iid ) {
				$src = wp_get_attachment_image_src( $iid, 'full' );
				if ( $src ) {
					// 图片走 --res-bg 变量 + CSS ::before(支持呼吸缩放);section 底色用 primary 兜底
					return 'background:var(--primary);--res-bg:url(' . esc_url( $src[0] ) . ');--res-h:' . $h . 'px;--res-gap:' . $gap . 'px;--res-banner-radius:' . $brad . ';';
				}
			}
			break;
	}
	if ( in_array( $o['banner_mode'], array( 'default', 'solid' ), true ) && $op < 100 ) {
		$bg = sprintf( 'color-mix(in srgb, %s %d%%, transparent)', $bg, $op );
	}
	return 'background:' . $bg . ';--res-h:' . $h . 'px;--res-gap:' . $gap . 'px;--res-banner-radius:' . $brad . ';';
}

function onedong_resource_banner() {
	$o       = onedong_resources_opts();
	$mode    = $o['banner_mode'];
	$animate = ( '1' === $o['banner_animate'] ) && in_array( $mode, array( 'gradient', 'image' ), true );
	$card    = '1' === $o['banner_card'];
	?>
	<section class="resource-banner" data-mode="<?php echo esc_attr( $mode ); ?>"<?php echo $animate ? ' data-animate' : ''; ?> style="<?php echo esc_attr( onedong_resource_banner_style() ); ?>">
		<div class="resource-banner__inner<?php echo $card ? ' resource-banner__inner--card' : ''; ?>"<?php echo $card ? ' style="' . esc_attr( onedong_resource_banner_card_style() ) . '"' : ''; ?>>
			<h1 class="resource-banner__title"><?php echo esc_html( $o['banner_title'] ); ?></h1>
			<?php if ( $o['banner_subtitle'] ) : ?>
				<p class="resource-banner__subtitle"><?php echo wp_kses_post( $o['banner_subtitle'] ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/** 内容卡片的圆角 + 内边距内联 style。 */
function onedong_resource_banner_card_style() {
	$o      = onedong_resources_opts();
	$cr     = $o['banner_card_radius'];
	$radius = '' === $cr ? 'var(--radius-large)' : ( ( '999' === $cr ) ? '999px' : ( (int) $cr ) . 'px' );
	return 'border-radius:' . $radius;
}

/** 卡片圆角 CSS 变量(供模板 .resources-page 输出;默认空 = 跟随网站 --radius-large)。 */
function onedong_resource_card_style_attr() {
	$o  = onedong_resources_opts();
	$cr = $o['card_radius'];
	if ( '' === $cr ) {
		return '';
	}
	$val = ( '999' === $cr ) ? '999px' : ( (int) $cr ) . 'px';
	return ' style="--res-card-radius:' . esc_attr( $val ) . '"';
}

function onedong_resource_filter_bar() {
	$cats = onedong_resource_get_cats();
	?>
	<div class="resource-filters" data-res-filters>
		<button type="button" class="resource-filter is-active" data-filter="all"><?php esc_html_e( '全部', 'onedong' ); ?></button>
		<?php foreach ( $cats as $c ) : ?>
			<button type="button" class="resource-filter" data-filter="<?php echo esc_attr( $c->term_id ); ?>"><?php echo esc_html( $c->name ); ?></button>
		<?php endforeach; ?>
	</div>
	<?php
}

/** 输出单条资源卡片(模板 loop 内调用)。 */
function onedong_render_resource_card() {
	$id    = get_the_ID();
	$url   = get_post_meta( $id, '_onedong_resource_url', true );
	$mode  = get_post_meta( $id, '_onedong_resource_icon_mode', true );
	$icon  = '';
	switch ( $mode ) {
		case 'upload':
			$iid = (int) get_post_meta( $id, '_onedong_resource_icon_id', true );
			if ( $iid && wp_attachment_is_image( $iid ) ) {
				$icon = wp_get_attachment_image( $iid, 'onedong-resource-icon', false, array(
					'class'     => 'resource-card__icon',
					'loading'   => 'lazy',
					'decoding'  => 'async',
				) );
			}
			break;
		case 'remote':
			$iu = get_post_meta( $id, '_onedong_resource_icon_url', true );
			if ( $iu ) {
				$icon = '<img class="resource-card__icon" src="' . esc_url( $iu ) . '" alt="' . esc_attr( get_the_title() ) . '" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.classList.add(\'is-broken\')">';
			}
			break;
	}
	if ( ! $icon ) {
		// 默认图标:2×2 圆角网格(apps / 资源集合)——与「资源导航」菜单 dashicons-screenoptions 同语义,
		// 矩形几何在任意尺寸渲染锐利;fill=currentColor 跟随品牌蓝,深浅色自动适配。
		$icon = '<span class="resource-card__icon resource-card__icon--default" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="2.2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2.2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2.2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2.2"/></svg></span>';
	}
	$cats   = get_the_terms( $id, 'onedong_resource_cat' );
	$cat    = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0] : null;
	$cat_id = $cat ? (int) $cat->term_id : 0;
	// 完整描述进 DOM(去 shortcode / HTML / 折行)· 卡片 CSS 默认 2 行省略号截断,hover 展开显示全文。
	// 不再用 wp_trim_words 截 30 词 —— 那会把英文/混排描述砍到刚好 2 行,导致 hover「没有折叠文字可展开」。
	$desc   = wp_strip_all_tags( strip_shortcodes( get_the_content() ), true );
	?>
	<article class="resource-card" data-cat="<?php echo esc_attr( $cat_id ); ?>" data-name="<?php echo esc_attr( strtolower( get_the_title() ) ); ?>">
		<a class="resource-card__link" href="<?php echo esc_url( $url ? $url : '#' ); ?>" target="_blank" rel="noopener noreferrer nofollow">
			<div class="resource-card__head">
				<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput — 已转义 ?>
				<h3 class="resource-card__title"><?php the_title(); ?></h3>
			</div>
			<?php if ( $desc ) : ?>
				<p class="resource-card__desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
			<div class="resource-card__foot">
				<?php if ( $cat ) : ?>
					<span class="resource-card__cat"><?php echo esc_html( $cat->name ); ?></span>
				<?php else : ?>
					<span aria-hidden="true"></span>
				<?php endif; ?>
				<span class="resource-card__visit">
					<?php esc_html_e( '访问', 'onedong' ); ?>
					<svg class="resource-card__arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</span>
			</div>
		</a>
	</article>
	<?php
}


/* ============================================================
 * 9. 导航入口:filter 注入(不改 header.php)
 *    覆盖 header.php 两条分支:wp_nav_menu(primary) + wp_page_menu 兜底。
 * ============================================================ */
function onedong_resource_nav_item() {
	$o     = onedong_resources_opts();
	$label = trim( (string) $o['nav_label'] );
	if ( '' === $label ) {
		return '';
	}
	$url = get_post_type_archive_link( 'onedong_resource' );
	if ( ! $url ) {
		return '';
	}
	// 本入口由 wp_nav_menu_items filter 注入,非真实 WP 菜单对象 → WP 不会自动加 current-* 类,
	// 在资源归档 / 单资源页时手动补 current-menu-item,使 layout.css 的主题色高亮生效(菜单文字激活态)
	$is_current = is_post_type_archive( 'onedong_resource' ) || is_singular( 'onedong_resource' );
	$classes    = 'menu-item res-nav-item' . ( $is_current ? ' current-menu-item' : '' );
	return '<li class="' . esc_attr( $classes ) . '"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
}

add_filter( 'wp_nav_menu_items', function ( $items, $args ) {
	if ( is_admin() ) {
		return $items;
	}
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		$item = onedong_resource_nav_item();
		if ( $item ) {
			$items .= $item;
		}
	}
	return $items;
}, 10, 2 );

add_filter( 'wp_page_menu', function ( $menu ) {
	if ( is_admin() ) {
		return $menu;
	}
	$item = onedong_resource_nav_item();
	if ( ! $item ) {
		return $menu;
	}
	$pos = strripos( $menu, '</ul>' );
	if ( false !== $pos ) {
		$menu = substr( $menu, 0, $pos ) . $item . substr( $menu, $pos );
	}
	return $menu;
} );
