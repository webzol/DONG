<?php
/**
 * 朋友圈模块(onedong_moment)· v2.5.0
 *
 * 自定义文章类型 + 后台发布(文字 + 多图最多9 + 定位)+ 前端微信朋友圈流展示。
 * 前端模板:archive-onedong_moment.php / single-onedong_moment.php(均调用 onedong_render_moment())。
 * 样式/脚本:assets/css/moments.css + assets/js/moments.js(lightbox)+ 后台 moment-admin.*。
 *
 * @package OneDong
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================
 * 1. 注册「朋友圈」CPT
 * ============================================================ */
function onedong_register_moment_cpt() {
	register_post_type(
		'onedong_moment',
		array(
			'labels'        => array(
				'name'               => __( '朋友圈', 'onedong' ),
				'singular_name'      => __( '朋友圈', 'onedong' ),
				'add_new'            => __( '发布朋友圈', 'onedong' ),
				'add_new_item'       => __( '发布朋友圈', 'onedong' ),
				'edit_item'          => __( '编辑朋友圈', 'onedong' ),
				'new_item'           => __( '新朋友圈', 'onedong' ),
				'view_item'          => __( '查看', 'onedong' ),
				'search_items'       => __( '搜索朋友圈', 'onedong' ),
				'not_found'          => __( '暂无朋友圈', 'onedong' ),
				'not_found_in_trash' => __( '回收站无朋友圈', 'onedong' ),
				'all_items'          => __( '全部朋友圈', 'onedong' ),
				'menu_name'          => __( '朋友圈', 'onedong' ),
			),
			'public'        => true,
			'has_archive'   => 'moments',
			'menu_icon'     => 'dashicons-format-status',
			'menu_position' => 6,
			'hierarchical'  => false,
			'supports'      => array( 'title', 'editor', 'author', 'thumbnail' ),
			'show_in_rest'  => false, // 经典 meta box 发布,不走块编辑器
			'rewrite'       => array( 'slug' => 'moments', 'with_front' => false ),
		)
	);
	// 首次注册刷一次固定链接,确保 /moments/ 可访问(只跑一次)
	if ( ! get_option( 'onedong_moment_flushed' ) ) {
		flush_rewrite_rules();
		update_option( 'onedong_moment_flushed', 1 );
	}
}
add_action( 'init', 'onedong_register_moment_cpt' );

// 切换/启用主题时刷固定链接,防止 CPT archive 404
add_action( 'after_switch_theme', 'flush_rewrite_rules' );


/* ============================================================
 * 2. 后台发布:meta box(图片 + 定位)
 * ============================================================ */
function onedong_moment_add_meta_box() {
	add_meta_box( 'onedong_moment_media', __( '图片与定位', 'onedong' ), 'onedong_moment_meta_box_cb', 'onedong_moment', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'onedong_moment_add_meta_box' );

function onedong_moment_meta_box_cb( $post ) {
	wp_nonce_field( 'onedong_moment_save', 'onedong_moment_nonce' );
	$ids      = get_post_meta( $post->ID, '_onedong_moment_images', true );
	$location = get_post_meta( $post->ID, '_onedong_moment_location', true );
	if ( ! is_array( $ids ) ) {
		$ids = array();
	}
	?>
	<p class="description"><?php esc_html_e( '文字写在上方「正文」框;图片最多 9 张(1 张大图 / 2–9 张九宫格);定位可选。', 'onedong' ); ?></p>

	<h4 style="margin:1em 0 .4em;"><?php esc_html_e( '图片(最多 9 张)', 'onedong' ); ?></h4>
	<ul class="moment-img-list" id="moment-img-list">
	<?php foreach ( $ids as $id ) : ?>
		<?php if ( wp_get_attachment_image( $id, 'thumbnail' ) ) : ?>
			<li class="moment-img-item" data-id="<?php echo esc_attr( $id ); ?>">
				<?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?>
				<button type="button" class="moment-img-remove" aria-label="<?php esc_attr_e( '移除', 'onedong' ); ?>">×</button>
			</li>
		<?php endif; ?>
	<?php endforeach; ?>
	</ul>
	<p>
		<button type="button" class="button" id="moment-img-add"><?php esc_html_e( '+ 添加图片', 'onedong' ); ?></button>
		<span class="description" id="moment-img-count"><?php echo esc_html( sprintf( __( '已选 %d/9', 'onedong' ), count( $ids ) ) ); ?></span>
	</p>
	<input type="hidden" id="moment-img-ids" name="onedong_moment_images" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">

	<h4 style="margin:1.2em 0 .4em;"><?php esc_html_e( '定位', 'onedong' ); ?></h4>
	<input type="text" name="onedong_moment_location" value="<?php echo esc_attr( $location ); ?>" placeholder="<?php esc_attr_e( '如:杭州·西湖断桥', 'onedong' ); ?>" class="widefat">
	<?php
}

function onedong_moment_save( $post_id ) {
	if ( ! isset( $_POST['onedong_moment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['onedong_moment_nonce'] ) ), 'onedong_moment_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// 图片 ID(仅 attachment、最多 9、去重)
	if ( isset( $_POST['onedong_moment_images'] ) ) {
		$raw = explode( ',', sanitize_text_field( wp_unslash( $_POST['onedong_moment_images'] ) ) );
		$out = array();
		foreach ( $raw as $r ) {
			$r = absint( $r );
			if ( $r && 'attachment' === get_post_type( $r ) ) {
				$out[] = $r;
			}
		}
		update_post_meta( $post_id, '_onedong_moment_images', array_slice( array_unique( $out ), 0, 9 ) );
	}
	if ( isset( $_POST['onedong_moment_location'] ) ) {
		update_post_meta( $post_id, '_onedong_moment_location', sanitize_text_field( wp_unslash( $_POST['onedong_moment_location'] ) ) );
	}
}
add_action( 'save_post_onedong_moment', 'onedong_moment_save' );


/* ============================================================
 * 3. 后台资源:媒体上传器 + 图片管理 JS/CSS(仅朋友圈编辑页)
 * ============================================================ */
function onedong_moment_admin_assets() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'onedong_moment' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'onedong-moment-admin', ONEDONG_URI . '/assets/js/moment-admin.js', array( 'jquery' ), ONEDONG_VERSION, true );
	wp_enqueue_style( 'onedong-moment-admin', ONEDONG_URI . '/assets/css/moment-admin.css', array(), ONEDONG_VERSION );
	wp_localize_script(
		'onedong-moment-admin',
		'onedongMomentAdmin',
		array(
			'max'   => 9,
			'title' => __( '选择朋友圈图片', 'onedong' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'onedong_moment_admin_assets' );


/* ============================================================
 * 4. 前端渲染辅助:相对时间 + 单条朋友圈 HTML
 * ============================================================ */

/**
 * 微信朋友圈风格相对时间(刚刚 / X 分钟前 / X 小时前 / 昨天 / 日期)。
 * 需在循环内调用(依赖 get_the_time)。
 */
function onedong_moment_time_format() {
	$diff = (int) current_time( 'timestamp' ) - (int) get_the_time( 'U' );
	if ( $diff < 60 ) {
		return __( '刚刚', 'onedong' );
	} elseif ( $diff < 3600 ) {
		return sprintf( __( '%d 分钟前', 'onedong' ), (int) floor( $diff / 60 ) );
	} elseif ( $diff < 86400 ) {
		return sprintf( __( '%d 小时前', 'onedong' ), (int) floor( $diff / 3600 ) );
	} elseif ( $diff < 172800 ) {
		return __( '昨天', 'onedong' );
	} else {
		return get_the_date();
	}
}

/**
 * 渲染单条朋友圈(头像 + 昵称 + 文字 + 图片网格 + 定位 + 时间)。
 * 供 archive-onedong_moment.php / single-onedong_moment.php 复用。需在循环内。
 */
function onedong_render_moment() {
	$ids      = get_post_meta( get_the_ID(), '_onedong_moment_images', true );
	$location = get_post_meta( get_the_ID(), '_onedong_moment_location', true );
	if ( ! is_array( $ids ) ) {
		$ids = array();
	}
	$count = count( $ids );
	?>
	<article <?php post_class( 'moment' ); ?>>
		<div class="moment__avatar">
			<?php echo get_avatar( get_the_author_meta( 'ID' ), 80 ); ?>
		</div>
		<div class="moment__main">
			<div class="moment__author"><?php the_author(); ?></div>

			<?php if ( get_the_content() ) : ?>
				<div class="moment__content"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php if ( $count > 0 ) : ?>
				<div class="moment__imgs moment__imgs--<?php echo ( 1 === $count ) ? 'single' : 'grid'; ?>">
					<?php
					foreach ( $ids as $id ) :
						$full = wp_get_attachment_image_url( $id, 'large' );
						$size = ( 1 === $count ) ? 'large' : 'onedong-moment-thumb';
						echo wp_get_attachment_image(
							$id,
							$size,
							false,
							array(
								'class'      => 'moment__img',
								'data-full'  => esc_url( $full ),
								'loading'    => 'lazy',
								'decoding'   => 'async',
							)
						);
					endforeach;
					?>
				</div>
			<?php endif; ?>

			<div class="moment__foot">
				<time class="moment__time" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( onedong_moment_time_format() ); ?></time>
				<?php if ( $location ) : ?>
					<span class="moment__location">
						<svg class="moment__loc-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z" fill="currentColor"/></svg>
						<?php echo esc_html( $location ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}
