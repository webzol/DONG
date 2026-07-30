<?php
/**
 * 朋友圈模块(onedong_moment)· v2.5.11
 *
 * CPT + 后台发布(文字 + 多图最多9 + 定位 + 实况视频)+ 前端微信朋友圈流。
 * 实况(Live Photo):每张图可选配一个视频附件,前端 hover(桌面)/ 长按(移动)播放 + 角标。
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
			'show_in_rest'  => false,
			'rewrite'       => array( 'slug' => 'moments', 'with_front' => false ),
		)
	);
	if ( ! get_option( 'onedong_moment_flushed' ) ) {
		flush_rewrite_rules();
		update_option( 'onedong_moment_flushed', 1 );
	}
}
add_action( 'init', 'onedong_register_moment_cpt' );
add_action( 'after_switch_theme', 'flush_rewrite_rules' );


/* ============================================================
 * 1b. 注册「话题」分类法(扁平标签式;关联 onedong_moment)· v6.1.7
 * 后台发布时用原生标签框自由输入多个话题;前端做成可点 chip,
 * 点击走原生 term 归档页(/moments/topic/{slug})聚合同话题内容。
 * ============================================================ */
function onedong_register_moment_topic_taxonomy() {
	register_taxonomy(
		'onedong_moment_topic',
		array( 'onedong_moment' ),
		array(
			'labels'            => array(
				'name'                       => __( '话题', 'onedong' ),
				'singular_name'              => __( '话题', 'onedong' ),
				'search_items'               => __( '搜索话题', 'onedong' ),
				'popular_items'              => __( '热门话题', 'onedong' ),
				'all_items'                  => __( '所有话题', 'onedong' ),
				'edit_item'                  => __( '编辑话题', 'onedong' ),
				'update_item'                => __( '更新话题', 'onedong' ),
				'add_new_item'               => __( '添加新话题', 'onedong' ),
				'new_item_name'              => __( '新话题名称', 'onedong' ),
				'separate_items_with_commas' => __( '多个话题用空格或逗号分隔', 'onedong' ),
				'add_or_remove_items'        => __( '添加或移除话题', 'onedong' ),
				'choose_from_most_used'      => __( '从常用话题中选择', 'onedong' ),
				'not_found'                  => __( '暂无话题', 'onedong' ),
				'menu_name'                  => __( '话题', 'onedong' ),
			),
			'public'            => true,
			'hierarchical'      => false,                 // 扁平标签式
			'show_admin_column' => true,
			'show_in_rest'      => false,                 // 经典编辑器
			'meta_box_cb'       => 'post_tags_meta_box',  // 原生标签框 → 后台自由输入,零额外后台代码
			// slug 不能与 CPT 的 'moments' 共享前缀(如 'moments/topic'),
			// 否则 rewrite 规则冲突、term 归档 404(v6.1.7 踩坑 → v6.1.8 改独立 slug)。
			'rewrite'           => array( 'slug' => 'moments-topic', 'with_front' => false ),
		)
	);
	// 刷固定链接;slug 变化后用 v2 option 强制再刷一次,让新规则即时生效(免手动保存固定链接)。
	if ( ! get_option( 'onedong_moment_topic_flushed_v2' ) ) {
		flush_rewrite_rules();
		update_option( 'onedong_moment_topic_flushed_v2', 1 );
	}
}
add_action( 'init', 'onedong_register_moment_topic_taxonomy' );


/* ============================================================
 * 2. 后台发布:meta box(图片 + 实况 + 定位)
 * ============================================================ */
function onedong_moment_add_meta_box() {
	add_meta_box( 'onedong_moment_media', __( '图片与定位', 'onedong' ), 'onedong_moment_meta_box_cb', 'onedong_moment', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'onedong_moment_add_meta_box' );

function onedong_moment_meta_box_cb( $post ) {
	wp_nonce_field( 'onedong_moment_save', 'onedong_moment_nonce' );
	$ids      = get_post_meta( $post->ID, '_onedong_moment_images', true );
	$location = get_post_meta( $post->ID, '_onedong_moment_location', true );
	$lives    = get_post_meta( $post->ID, '_onedong_moment_live', true );
	if ( ! is_array( $ids ) ) {
		$ids = array();
	}
	if ( ! is_array( $lives ) ) {
		$lives = array();
	}
	?>
	<p class="description"><?php esc_html_e( '文字写在上方「正文」框;图片最多 9 张(1 张大图 / 2–9 张九宫格);每张图可点「实况」配一段视频,前端悬停 / 长按播放;定位可选。', 'onedong' ); ?></p>

	<h4 style="margin:1em 0 .4em;"><?php esc_html_e( '图片(最多 9 张)', 'onedong' ); ?></h4>
	<ul class="moment-img-list" id="moment-img-list">
	<?php foreach ( $ids as $id ) : ?>
		<?php if ( wp_get_attachment_image( $id, 'thumbnail' ) ) : ?>
			<?php $has_live = isset( $lives[ $id ] ) ? absint( $lives[ $id ] ) : 0; ?>
			<li class="moment-img-item" data-id="<?php echo esc_attr( $id ); ?>" data-video="<?php echo $has_live ? esc_attr( $has_live ) : ''; ?>">
				<?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?>
				<button type="button" class="moment-img-live" data-video="<?php echo $has_live ? esc_attr( $has_live ) : ''; ?>" title="<?php esc_attr_e( '实况视频', 'onedong' ); ?>"><?php echo $has_live ? '✓实况' : '＋实况'; ?></button>
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
	<input type="hidden" id="moment-live" name="onedong_moment_live" value="<?php echo esc_attr( wp_json_encode( $lives ) ); ?>">

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
	// 实况视频配对(img_id => video_id;video 必须 attachment)
	if ( isset( $_POST['onedong_moment_live'] ) ) {
		$raw  = json_decode( wp_unslash( $_POST['onedong_moment_live'] ), true );
		$live = array();
		if ( is_array( $raw ) ) {
			foreach ( $raw as $img_id => $vid_id ) {
				$img_id = absint( $img_id );
				$vid_id = absint( $vid_id );
				if ( $img_id && $vid_id && 'attachment' === get_post_type( $vid_id ) ) {
					$live[ $img_id ] = $vid_id;
				}
			}
		}
		update_post_meta( $post_id, '_onedong_moment_live', $live );
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
 * 点赞昵称的确定性伪随机种子 → [0, 2^32)。
 * 与 onedong_like_names 配套;独立成函数便于复用、避免闭包捕获。
 *
 * @param int    $post_id 文章 ID(种子主源)。
 * @param int    $i       第几个昵称(0 起)。
 * @param int    $r       去重重试轮次。
 * @param string $salt    区分姓 / 名 / 风格等不同维度的盐。
 * @return int
 */
function onedong_like_roll( $post_id, $i, $r, $salt ) {
	return abs( crc32( $post_id . '|' . $i . '|' . $r . '|' . $salt ) );
}

/**
 * 按 post ID 确定性生成「点赞者」昵称(装饰用:点赞匿名、只存数量,无真实身份)。
 * 同一篇文章每次渲染结果一致(crc32 种子);点赞数变化时只在尾部增减、不重排
 * ——故用户多赞一次,表现为「多一个昵称」而非整列洗牌,贴近真实。
 *
 * @param int $post_id 文章 ID(作种子)。
 * @param int $count   昵称数量。
 * @return string[] 昵称数组(已去重)。
 */
function onedong_like_names( $post_id, $count ) {
	$count = max( 0, (int) $count );
	if ( $count <= 0 ) {
		return array();
	}
	$surnames = array( '王','李','张','刘','陈','杨','黄','赵','周','吴','徐','孙','马','朱','胡','郭','林','何','高','罗','郑','梁','谢','宋','唐','许','韩','冯','邓','曹','彭','萧','蔡','卢','苏','蒋','丁','魏','叶','沈','吕' );
	$given    = array( '伟','芳','娜','敏','静','强','磊','洋','艳','勇','军','杰','娟','涛','明','超','秀','欣','宇','轩','晨','佳','婷','鑫','哲','文','健','峰','鹏','辉','平','刚','红','玲','霞','波','宁','贵','华','斌','燕','凯','成','翔','飞','莉','丹','倩','璐','诚','悦' );
	$prefix   = array( '小','阿','大' );
	$ns = count( $surnames );
	$ng = count( $given );
	$np = count( $prefix );
	$out  = array();
	$used = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$name = '';
		for ( $r = 0; $r < 8; $r++ ) { // 碰撞最多重选 8 轮,仍撞就接受(极罕)
			if ( onedong_like_roll( $post_id, $i, $r, 'style' ) % 10 < 7 ) {
				// 全名:姓 + 1~2 个名
				$name = $surnames[ onedong_like_roll( $post_id, $i, $r, 's' ) % $ns ] . $given[ onedong_like_roll( $post_id, $i, $r, 'g1' ) % $ng ];
				if ( ! ( onedong_like_roll( $post_id, $i, $r, 'g2b' ) % 2 ) ) {
					$name .= $given[ onedong_like_roll( $post_id, $i, $r, 'g2' ) % $ng ];
				}
			} else {
				// 昵称风:前缀 + 名
				$name = $prefix[ onedong_like_roll( $post_id, $i, $r, 'p' ) % $np ] . $given[ onedong_like_roll( $post_id, $i, $r, 'g1' ) % $ng ];
			}
			if ( ! in_array( $name, $used, true ) ) {
				break;
			}
		}
		$used[] = $name;
		$out[]  = $name;
	}
	return $out;
}

/**
 * 渲染「点赞展示行」HTML(微信朋友圈式:空心 ♡ + 昵称 + 「等N人赞过」折叠)· v6.1.6
 * 单一真相源:模板渲染与点赞 REST 成功回包共用本函数 → 前端点赞后实时刷新昵称与后端一致。
 * 昵称由 onedong_like_names() 按计数确定性生成(装饰性社交证明)。
 *
 * @param int $post_id   文章 ID。
 * @param int $like_max  直显昵称上限,超出转「等N人赞过」。默认 6。
 * @return string 计数 > 0 返回整块 .moment__likes HTML;否则空串。
 */
function onedong_render_moment_likes( $post_id, $like_max = 6 ) {
	$like_count = (int) onedong_get_likes( $post_id );
	if ( $like_count <= 0 ) {
		return '';
	}
	$names = onedong_like_names( $post_id, min( $like_count, $like_max ) );
	ob_start();
	?>
	<div class="moment__likes">
		<svg class="moment__like-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
		<span class="moment__like-names"><?php echo esc_html( implode( '、', $names ) ); ?></span><?php if ( $like_count > $like_max ) : ?><span class="moment__like-fold"> 等<?php echo (int) $like_count; ?>人赞过</span><?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function onedong_render_moment() {
	$ids      = get_post_meta( get_the_ID(), '_onedong_moment_images', true );
	$location = get_post_meta( get_the_ID(), '_onedong_moment_location', true );
	$lives    = get_post_meta( get_the_ID(), '_onedong_moment_live', true );
	if ( ! is_array( $ids ) ) {
		$ids = array();
	}
	if ( ! is_array( $lives ) ) {
		$lives = array();
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

			<?php
			// 话题标签(正文下方一行 chip,可点跳话题归档页)· v6.1.7
			$moment_topics = get_the_terms( get_the_ID(), 'onedong_moment_topic' );
			if ( $moment_topics && ! is_wp_error( $moment_topics ) ) :
				?>
				<div class="moment__topics">
					<?php foreach ( $moment_topics as $mt ) : ?>
						<a class="moment__topic" href="<?php echo esc_url( get_term_link( $mt ) ); ?>">#<?php echo esc_html( $mt->name ); ?></a>
					<?php endforeach; ?>
				</div>
				<?php
			endif;
			?>

			<?php if ( $count > 0 ) : ?>
				<div class="moment__imgs moment__imgs--<?php echo ( 1 === $count ) ? 'single' : 'grid'; ?>">
					<?php
					foreach ( $ids as $id ) :
						$full  = wp_get_attachment_image_url( $id, 'large' );
						$size  = ( 1 === $count ) ? 'large' : 'onedong-moment-thumb';
						$video = isset( $lives[ $id ] ) ? wp_get_attachment_url( $lives[ $id ] ) : '';
						$attr  = array(
							'class'     => 'moment__img',
							'data-full' => esc_url( $full ),
							'loading'   => 'lazy',
							'decoding'  => 'async',
						);
						if ( $video ) {
							$attr['data-video'] = esc_url( $video );
							echo '<span class="moment__live-wrap">' . wp_get_attachment_image( $id, $size, false, $attr ) . '<span class="moment__live-badge">实况</span></span>';
						} else {
							echo wp_get_attachment_image( $id, $size, false, $attr );
						}
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

				<?php $like_url = esc_url_raw( rest_url( 'onedong/v1/like' ) ); ?>
				<div class="moment__actions" data-like-url="<?php echo esc_attr( $like_url ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
					<button class="moment__toggle" type="button" aria-label="<?php esc_attr_e( '操作', 'onedong' ); ?>" aria-expanded="false">
						<span class="moment__dot"></span><span class="moment__dot"></span>
					</button>
					<div class="moment__pop">
						<button class="moment__pop-btn moment__pop-btn--like" type="button" data-id="<?php the_ID(); ?>" aria-label="<?php esc_attr_e( '赞', 'onedong' ); ?>">
							<svg class="moment__pop-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
							<span class="moment__pop-num"><?php echo esc_html( (int) onedong_get_likes() ); ?></span>
						</button>
						<span class="moment__pop-sep"></span>
						<button class="moment__pop-btn moment__pop-btn--share" type="button" data-url="<?php the_permalink(); ?>" data-title="<?php the_title_attribute(); ?>" aria-label="<?php esc_attr_e( '分享', 'onedong' ); ?>">
							<svg class="moment__pop-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12l16-7-5 16-3-7-8-2z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
						</button>
					</div>
				</div>
			</div>

			<?php
			// 点赞展示行:排在日期/操作行「下方」(微信朋友圈式)· v6.1.5 / v6.1.6 实时刷新(模板与 REST 共用同一函数)
			echo onedong_render_moment_likes( get_the_ID() ); // 计数 0 时返回空串 → 不输出该行
			?>
		</div>
	</article>
	<?php
}
