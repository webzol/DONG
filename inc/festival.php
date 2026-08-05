<?php
/**
 * 节气 / 节假日提示条模块
 *
 * 紧贴 header 下方(公告条之后)渲染一条可关闭的节日提示卡片:
 * 24 节气 / 法定节假日 / 传统节日 / 现代西方节日,每个节日一套专属配色 + 图标。
 * 日期由 inc/lunar.php 天文算法实时算出,无需逐年维护数据。
 *
 * 配置走 Customizer(section: onedong_festival);关闭状态按「节日 + 日期」存
 * localStorage,次日换了节日自动重新出现。仅用设计 token + 两个注入变量,
 * 深浅色 / 换肤自动跟随。
 *
 * @package OneDong
 * @since 6.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // 禁止直接访问
}

/**
 * 类别优先级:同一天多条命中时,数字大的作主体。
 * 例:清明既是节气又是法定节假日 → 法定胜出。
 *
 * @return array
 */
function onedong_festival_priorities() {
	return array(
		'legal'       => 40, // 法定节假日
		'traditional' => 30, // 传统节日
		'term'        => 20, // 24 节气
		'modern'      => 10, // 现代 / 西方节日
	);
}

/**
 * 节日总表。
 *
 * rule 支持四种:
 *   solar  => array( 月, 日 )          公历定日
 *   lunar  => array( 月, 日 )          农历定日(不匹配闰月)
 *   term   => '节气名'                 由天文算法命中
 *   nth    => array( 月, 周几0=日, 第几个 )
 *   eve    => true                     除夕(腊月最后一天)
 *
 * accent / accent2 为该节日的专属强调色对(渐变、图标底、左边条全部由此派生)。
 *
 * @return array
 */
function onedong_festival_defs() {
	static $defs = null;
	if ( null !== $defs ) {
		return $defs;
	}

	$defs = array(

		/* —— 24 节气(四季基调 + 逐个微调)—— */
		array( 'term', '立春', array( 'term' => '立春' ), '#5FAF6B', '#9AD4A0', 'sprout', '东风解冻,蛰虫始振' ),
		array( 'term', '雨水', array( 'term' => '雨水' ), '#5FA8A0', '#9ACFC8', 'rain', '獭祭鱼,候雁北' ),
		array( 'term', '惊蛰', array( 'term' => '惊蛰' ), '#6FB362', '#A6D69C', 'sprout', '桃始华,仓庚鸣' ),
		array( 'term', '春分', array( 'term' => '春分' ), '#71C29A', '#A8DEC4', 'flower', '玄鸟至,雷乃发声' ),
		// 清明在法定节假日表中亦有一份:两者皆是。同日命中时按优先级归法定,
		// 同名的那条会被副标题去重逻辑丢弃;若后台只勾了「24 节气」,这份保证它照常出现。
		array( 'term', '清明', array( 'term' => '清明' ), '#5F9EA0', '#A6C9C6', 'rain', '桐始华,虹始见' ),
		array( 'term', '谷雨', array( 'term' => '谷雨' ), '#5C9FA8', '#98CBD1', 'rain', '萍始生,鸣鸠拂其羽' ),
		array( 'term', '立夏', array( 'term' => '立夏' ), '#E08A46', '#F3BC85', 'sun-hot', '蝼蝈鸣,蚯蚓出' ),
		array( 'term', '小满', array( 'term' => '小满' ), '#D9A441', '#F0CE85', 'wheat', '苦菜秀,麦秋至' ),
		array( 'term', '芒种', array( 'term' => '芒种' ), '#C99A38', '#E8C67E', 'wheat', '螳螂生,鵙始鸣' ),
		array( 'term', '夏至', array( 'term' => '夏至' ), '#E8763F', '#F7AD82', 'sun-hot', '鹿角解,蝉始鸣' ),
		array( 'term', '小暑', array( 'term' => '小暑' ), '#E2703C', '#F5A67F', 'sun-hot', '温风至,蟋蟀居壁' ),
		array( 'term', '大暑', array( 'term' => '大暑' ), '#DB5F35', '#F2997A', 'sun-hot', '腐草为萤,土润溽暑' ),
		array( 'term', '立秋', array( 'term' => '立秋' ), '#C8862B', '#E9C078', 'leaf', '凉风至,白露生' ),
		array( 'term', '处暑', array( 'term' => '处暑' ), '#C27C33', '#E5B87F', 'leaf', '鹰乃祭鸟,天地始肃' ),
		array( 'term', '白露', array( 'term' => '白露' ), '#A8A08C', '#D2CCBC', 'leaf', '鸿雁来,玄鸟归' ),
		array( 'term', '秋分', array( 'term' => '秋分' ), '#D9922B', '#F0C15C', 'wheat', '雷始收声,蛰虫坯户' ),
		array( 'term', '寒露', array( 'term' => '寒露' ), '#B07C46', '#DBB489', 'leaf', '鸿雁来宾,菊有黄华' ),
		array( 'term', '霜降', array( 'term' => '霜降' ), '#A66B45', '#D4A688', 'leaf', '豺乃祭兽,草木黄落' ),
		array( 'term', '立冬', array( 'term' => '立冬' ), '#5B8FC7', '#9CC0E0', 'snowflake', '水始冰,地始冻' ),
		array( 'term', '小雪', array( 'term' => '小雪' ), '#6E9BCC', '#A8C6E3', 'snowflake', '虹藏不见,闭塞成冬' ),
		array( 'term', '大雪', array( 'term' => '大雪' ), '#5486C2', '#95BADE', 'snowflake', '鹖鴠不鸣,荔挺出' ),
		array( 'term', '冬至', array( 'term' => '冬至' ), '#4A7FB5', '#8FB6D9', 'snowflake', '蚯蚓结,水泉动' ),
		array( 'term', '小寒', array( 'term' => '小寒' ), '#5F8AAE', '#9DBBD1', 'snowflake', '雁北乡,鹊始巢' ),
		array( 'term', '大寒', array( 'term' => '大寒' ), '#4E7C9E', '#93B4C9', 'snowflake', '鸡始乳,水泽腹坚' ),

		/* —— 法定节假日 —— */
		array( 'legal', '元旦', array( 'solar' => array( 1, 1 ) ), '#E5484D', '#FFA07E', 'firework', '一元复始,万象更新' ),
		array( 'legal', '春节', array( 'lunar' => array( 1, 1 ) ), '#D3302F', '#F2B544', 'lantern', '爆竹声中一岁除' ),
		array( 'legal', '清明', array( 'term' => '清明' ), '#5F9EA0', '#A6C9C6', 'rain', '梨花风起正清明' ),
		array( 'legal', '劳动节', array( 'solar' => array( 5, 1 ) ), '#D9453D', '#F5877F', 'flag', '劳动最光荣' ),
		array( 'legal', '端午节', array( 'lunar' => array( 5, 5 ) ), '#4F9D69', '#93C9A4', 'leaf', '菖蒲艾草,粽叶飘香' ),
		array( 'legal', '中秋节', array( 'lunar' => array( 8, 15 ) ), '#3E5C8C', '#E8C46B', 'moon-full', '但愿人长久,千里共婵娟' ),
		array( 'legal', '国庆节', array( 'solar' => array( 10, 1 ) ), '#D3302F', '#F2B544', 'flag', '举国同庆,山河锦绣' ),

		/* —— 传统节日 —— */
		array( 'traditional', '元宵节', array( 'lunar' => array( 1, 15 ) ), '#E8534E', '#FFB86B', 'lantern', '花市灯如昼' ),
		array( 'traditional', '龙抬头', array( 'lunar' => array( 2, 2 ) ), '#4E8FD1', '#96C2E8', 'sprout', '二月二,龙抬头' ),
		array( 'traditional', '上巳节', array( 'lunar' => array( 3, 3 ) ), '#7BAE7F', '#B3D6B6', 'flower', '曲水流觞,踏青祓禊' ),
		array( 'traditional', '七夕', array( 'lunar' => array( 7, 7 ) ), '#C46FA8', '#E8A8CE', 'heart', '金风玉露一相逢' ),
		array( 'traditional', '中元节', array( 'lunar' => array( 7, 15 ) ), '#6B7DA8', '#A9B6D1', 'moon', '秋尝祭祖,慎终追远' ),
		array( 'traditional', '重阳节', array( 'lunar' => array( 9, 9 ) ), '#C98A3C', '#E8BE83', 'flower', '遍插茱萸少一人' ),
		array( 'traditional', '寒衣节', array( 'lunar' => array( 10, 1 ) ), '#8A6E5D', '#BFA995', 'leaf', '十月一,送寒衣' ),
		array( 'traditional', '下元节', array( 'lunar' => array( 10, 15 ) ), '#6B8CA8', '#A8C1D1', 'moon', '水官解厄,祈福消灾' ),
		array( 'traditional', '腊八节', array( 'lunar' => array( 12, 8 ) ), '#A0603C', '#CE9A7B', 'wheat', '腊八粥香,年味渐浓' ),
		array( 'traditional', '小年', array( 'lunar' => array( 12, 23 ) ), '#D9663D', '#F0A47F', 'lantern', '祭灶扫尘,辞旧迎新' ),
		array( 'traditional', '除夕', array( 'eve' => true ), '#C62F2C', '#F2B544', 'firework', '一夜连双岁,五更分二年' ),

		/* —— 现代 / 西方节日 —— */
		array( 'modern', '情人节', array( 'solar' => array( 2, 14 ) ), '#E0567F', '#F5A0BC', 'heart', '愿有情人终成眷属' ),
		array( 'modern', '妇女节', array( 'solar' => array( 3, 8 ) ), '#C46FA8', '#E5AED0', 'flower', '致敬每一位她' ),
		array( 'modern', '植树节', array( 'solar' => array( 3, 12 ) ), '#5FA85F', '#9ACF9A', 'sprout', '种一棵树,最好的时间是现在' ),
		array( 'modern', '青年节', array( 'solar' => array( 5, 4 ) ), '#4A90D9', '#93BEE8', 'flag', '以青春之我,创青春之国' ),
		array( 'modern', '母亲节', array( 'nth' => array( 5, 0, 2 ) ), '#E07B9A', '#F3B5C6', 'flower', '慈母手中线' ),
		array( 'modern', '儿童节', array( 'solar' => array( 6, 1 ) ), '#F0A03C', '#FFD08A', 'gift', '愿你出走半生,归来仍是少年' ),
		array( 'modern', '父亲节', array( 'nth' => array( 6, 0, 3 ) ), '#4E7FA8', '#97B9D1', 'heart', '父爱如山,沉默如金' ),
		array( 'modern', '建党节', array( 'solar' => array( 7, 1 ) ), '#D3302F', '#F0837F', 'flag', '不忘初心,牢记使命' ),
		array( 'modern', '建军节', array( 'solar' => array( 8, 1 ) ), '#C0392B', '#E58E80', 'flag', '致敬最可爱的人' ),
		array( 'modern', '教师节', array( 'solar' => array( 9, 10 ) ), '#C98A3C', '#E8BE83', 'flower', '桃李不言,下自成蹊' ),
		array( 'modern', '记者节', array( 'solar' => array( 11, 8 ) ), '#5B8FC7', '#9CC0E0', 'document', '铁肩担道义,妙手著文章' ),
		array( 'modern', '感恩节', array( 'nth' => array( 11, 4, 4 ) ), '#C4763C', '#E5AC80', 'leaf', '感谢一路同行' ),
		array( 'modern', '平安夜', array( 'solar' => array( 12, 24 ) ), '#3E6B4F', '#8CB39A', 'gift', '愿今夜静好' ),
		array( 'modern', '圣诞节', array( 'solar' => array( 12, 25 ) ), '#C62F2C', '#4F9D69', 'gift', '愿你被温柔以待' ),
	);

	// 转成有名字段,便于阅读
	$out = array();
	foreach ( $defs as $row ) {
		$out[] = array(
			'type'    => $row[0],
			'name'    => $row[1],
			'rule'    => $row[2],
			'accent'  => $row[3],
			'accent2' => $row[4],
			'icon'    => $row[5],
			'desc'    => $row[6],
		);
	}
	$defs = $out;
	return $defs;
}

/**
 * 求指定公历日期命中的全部节日(不做类别过滤),按优先级降序。
 *
 * @param int $y 年。
 * @param int $m 月。
 * @param int $d 日。
 * @return array
 */
function onedong_festival_matches_on( $y, $m, $d ) {
	if ( ! onedong_lunar_supported( $y ) ) {
		return array();
	}

	$lunar = onedong_lunar_from_solar( $y, $m, $d );
	if ( null === $lunar ) {
		return array();
	}
	$term = onedong_lunar_term_on( $y, $m, $d );

	// 除夕:次日为正月初一。用 gmmktime + gmdate 全程走 UTC 做日期加减 —— 若用
	// getdate(),它会按服务器本地时区解释时间戳,站点设在 UTC+13 时会翻到次日。
	$is_eve   = false;
	$next_ts  = gmmktime( 12, 0, 0, $m, $d + 1, $y );
	$next_lun = onedong_lunar_from_solar(
		(int) gmdate( 'Y', $next_ts ),
		(int) gmdate( 'n', $next_ts ),
		(int) gmdate( 'j', $next_ts )
	);
	if ( null !== $next_lun && 1 === $next_lun['month'] && 1 === $next_lun['day'] && ! $next_lun['leap'] ) {
		$is_eve = true;
	}

	$weekday = (int) gmdate( 'w', gmmktime( 12, 0, 0, $m, $d, $y ) );
	$nth     = (int) floor( ( $d - 1 ) / 7 ) + 1; // 当月第几个该星期几

	$prio    = onedong_festival_priorities();
	$matches = array();

	foreach ( onedong_festival_defs() as $def ) {
		$rule = $def['rule'];
		$hit  = false;

		if ( isset( $rule['solar'] ) ) {
			$hit = ( $m === $rule['solar'][0] && $d === $rule['solar'][1] );
		} elseif ( isset( $rule['lunar'] ) ) {
			// 闰月不算节日(闰五月初五不是端午)
			$hit = ( ! $lunar['leap'] && $lunar['month'] === $rule['lunar'][0] && $lunar['day'] === $rule['lunar'][1] );
		} elseif ( isset( $rule['term'] ) ) {
			$hit = ( '' !== $term && $term === $rule['term'] );
		} elseif ( isset( $rule['nth'] ) ) {
			$hit = ( $m === $rule['nth'][0] && $weekday === $rule['nth'][1] && $nth === $rule['nth'][2] );
		} elseif ( isset( $rule['eve'] ) ) {
			$hit = $is_eve;
		}

		if ( $hit ) {
			$def['priority'] = isset( $prio[ $def['type'] ] ) ? $prio[ $def['type'] ] : 0;
			$matches[]       = $def;
		}
	}

	usort(
		$matches,
		function ( $a, $b ) {
			return $b['priority'] - $a['priority'];
		}
	);

	return $matches;
}

/**
 * 当前启用的类别。
 *
 * @return array
 */
function onedong_festival_enabled_types() {
	$types = array();
	foreach ( array( 'term', 'legal', 'traditional', 'modern' ) as $t ) {
		if ( get_theme_mod( 'onedong_festival_type_' . $t, 1 ) ) {
			$types[] = $t;
		}
	}
	return $types;
}

/**
 * 计算今天(或提前预告窗口内)要显示的节日载荷。
 *
 * @return array|null 无命中返回 null。
 */
function onedong_festival_payload() {
	static $payload = null;
	static $done    = false;
	if ( $done ) {
		return $payload;
	}
	$done = true;

	if ( ! get_theme_mod( 'onedong_festival_enable', 0 ) ) {
		return null;
	}

	$types = onedong_festival_enabled_types();
	if ( empty( $types ) ) {
		return null;
	}

	$today = onedong_lunar_today_bj();
	$lead  = (int) get_theme_mod( 'onedong_festival_lead', 0 );
	$lead  = max( 0, min( 7, $lead ) );

	$lunar = onedong_lunar_from_solar( $today['y'], $today['m'], $today['d'] );
	if ( null === $lunar ) {
		return null;
	}

	for ( $offset = 0; $offset <= $lead; $offset++ ) {
		// 同上:全程 gmdate,不受服务器时区影响
		$ts = gmmktime( 12, 0, 0, $today['m'], $today['d'] + $offset, $today['y'] );
		$gy = (int) gmdate( 'Y', $ts );
		$gm = (int) gmdate( 'n', $ts );
		$gd = (int) gmdate( 'j', $ts );

		// 逐日结果按日期缓存:天文计算不便每次请求重跑
		$cache_key = 'onedong_fes_' . sprintf( '%04d%02d%02d', $gy, $gm, $gd );
		$matches   = get_transient( $cache_key );
		if ( false === $matches ) {
			$matches = onedong_festival_matches_on( $gy, $gm, $gd );
			set_transient( $cache_key, $matches, 2 * DAY_IN_SECONDS );
		}

		// 按后台启用的类别过滤
		$kept = array();
		foreach ( $matches as $mt ) {
			if ( in_array( $mt['type'], $types, true ) ) {
				$kept[] = $mt;
			}
		}
		if ( empty( $kept ) ) {
			continue;
		}

		$main = $kept[0];

		// 次要命中(如清明同为节气)并入副标题,同名则丢弃
		$extra = '';
		foreach ( array_slice( $kept, 1 ) as $other ) {
			if ( $other['name'] !== $main['name'] ) {
				$extra = $other['name'];
				break;
			}
		}

		$plain = ( 'plain' === get_theme_mod( 'onedong_festival_palette', 'festival' ) );

		$meta_bits = array();
		if ( get_theme_mod( 'onedong_festival_show_lunar', 1 ) ) {
			$meta_bits[] = $lunar['ganzhi'] . $lunar['zodiac'] . '年';
			$meta_bits[] = $lunar['text'];
		}
		if ( '' !== $extra ) {
			$meta_bits[] = '今日亦是' . $extra;
		}

		$payload = array(
			'name'    => $main['name'],
			'title'   => 0 === $offset
				? '今日' . $main['name']
				: sprintf( '距%s还有 %d 天', $main['name'], $offset ),
			'meta'    => implode( ' · ', $meta_bits ),
			'desc'    => get_theme_mod( 'onedong_festival_show_desc', 1 ) ? $main['desc'] : '',
			'icon'    => $main['icon'],
			'accent'  => $plain ? '' : $main['accent'],
			'accent2' => $plain ? '' : $main['accent2'],
			'date'    => sprintf( '%04d-%02d-%02d', $today['y'], $today['m'], $today['d'] ),
			'key'     => substr( md5( $main['name'] . '|' . $offset ), 0, 8 ),
		);
		return $payload;
	}

	return null;
}

/* ============================================================
   Customizer
   ============================================================ */

/**
 * 提前预告天数净化:0–7。
 *
 * @param mixed $value 输入值。
 * @return int
 */
function onedong_festival_sanitize_lead( $value ) {
	$n = (int) $value;
	return max( 0, min( 7, $n ) );
}

/**
 * 配色模式净化:白名单,非法回退 festival。
 *
 * @param string $value 输入值。
 * @return string
 */
function onedong_festival_sanitize_palette( $value ) {
	return in_array( $value, array( 'festival', 'plain' ), true ) ? $value : 'festival';
}

/**
 * Customizer:节日提示条设置。
 *
 * @param WP_Customize_Manager $wp_customize Customizer 实例。
 */
function onedong_festival_customize( $wp_customize ) {
	$wp_customize->add_section(
		'onedong_festival',
		array(
			'title'       => __( '节气 / 节假日提示', 'onedong' ),
			'description' => __( '全站顶部(公告条下方)自动提示当天的节气与节日,日期由天文算法实时计算,无需逐年维护。', 'onedong' ),
			'priority'    => 31,
		)
	);

	// 总开关
	$wp_customize->add_setting(
		'onedong_festival_enable',
		array(
			'default'           => 0,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_festival_enable',
		array(
			'label'   => __( '启用节日提示条', 'onedong' ),
			'section' => 'onedong_festival',
			'type'    => 'checkbox',
		)
	);

	// 类别开关
	$types = array(
		'term'        => __( '24 节气(立春 / 清明 / 立秋…)', 'onedong' ),
		'legal'       => __( '法定节假日(春节 / 端午 / 中秋 / 国庆…)', 'onedong' ),
		'traditional' => __( '传统节日(元宵 / 七夕 / 重阳 / 除夕…)', 'onedong' ),
		'modern'      => __( '现代 / 西方节日(情人节 / 母亲节 / 圣诞…)', 'onedong' ),
	);
	foreach ( $types as $key => $label ) {
		$wp_customize->add_setting(
			'onedong_festival_type_' . $key,
			array(
				'default'           => 1,
				'sanitize_callback' => 'onedong_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'onedong_festival_type_' . $key,
			array(
				'label'   => $label,
				'section' => 'onedong_festival',
				'type'    => 'checkbox',
			)
		);
	}

	// 提前预告
	$wp_customize->add_setting(
		'onedong_festival_lead',
		array(
			'default'           => 0,
			'sanitize_callback' => 'onedong_festival_sanitize_lead',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_festival_lead',
		array(
			'label'       => __( '提前预告天数', 'onedong' ),
			'description' => __( '0 = 仅当天显示。设为 3 则节日前 3 天开始显示「距中秋还有 3 天」。', 'onedong' ),
			'section'     => 'onedong_festival',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0,
				'max'  => 7,
				'step' => 1,
			),
		)
	);

	// 配色模式
	$wp_customize->add_setting(
		'onedong_festival_palette',
		array(
			'default'           => 'festival',
			'sanitize_callback' => 'onedong_festival_sanitize_palette',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_festival_palette',
		array(
			'label'   => __( '配色', 'onedong' ),
			'section' => 'onedong_festival',
			'type'    => 'select',
			'choices' => array(
				'festival' => __( '节日专属配色(春节朱红 / 中秋靛蓝…)', 'onedong' ),
				'plain'    => __( '素雅统一(全部走主题色)', 'onedong' ),
			),
		)
	);

	// 显示农历
	$wp_customize->add_setting(
		'onedong_festival_show_lunar',
		array(
			'default'           => 1,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_festival_show_lunar',
		array(
			'label'   => __( '显示农历日期(丙午马年 · 六月廿三)', 'onedong' ),
			'section' => 'onedong_festival',
			'type'    => 'checkbox',
		)
	);

	// 显示短句
	$wp_customize->add_setting(
		'onedong_festival_show_desc',
		array(
			'default'           => 1,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_festival_show_desc',
		array(
			'label'   => __( '显示物候 / 寄语短句', 'onedong' ),
			'section' => 'onedong_festival',
			'type'    => 'checkbox',
		)
	);

	// 允许关闭
	$wp_customize->add_setting(
		'onedong_festival_dismissible',
		array(
			'default'           => 1,
			'sanitize_callback' => 'onedong_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'onedong_festival_dismissible',
		array(
			'label'       => __( '允许访客关闭', 'onedong' ),
			'description' => __( '关闭状态按「节日 + 日期」记忆,次日换了节日自动重新出现。', 'onedong' ),
			'section'     => 'onedong_festival',
			'type'        => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'onedong_festival_customize' );

/**
 * 按需加载节日条样式(仅启用且当天有命中时)。
 */
function onedong_festival_assets() {
	if ( null === onedong_festival_payload() ) {
		return;
	}
	wp_enqueue_style(
		'onedong-festival',
		ONEDONG_URI . '/assets/css/festival.css',
		array( 'onedong-layout' ),
		ONEDONG_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'onedong_festival_assets' );

/**
 * 渲染节日提示条。由 header.php 在公告条之后调用。
 */
function onedong_festival_bar() {
	$data = onedong_festival_payload();
	if ( null === $data ) {
		return;
	}

	$dismissible = (bool) get_theme_mod( 'onedong_festival_dismissible', 1 );

	// 专属配色经内联变量注入;素雅模式不注入,CSS 侧回退到 --primary。
	$style = '';
	if ( '' !== $data['accent'] ) {
		$style = sprintf(
			'--fes-accent:%s;--fes-accent-2:%s;',
			$data['accent'],
			$data['accent2']
		);
	}
	?>
	<section class="site-festival" role="region" aria-label="<?php esc_attr_e( '节气与节日提示', 'onedong' ); ?>" data-key="<?php echo esc_attr( $data['key'] ); ?>" data-date="<?php echo esc_attr( $data['date'] ); ?>"<?php echo $dismissible ? ' data-dismissible="1"' : ''; ?><?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
		<div class="site-festival__inner">
			<div class="site-festival__card">
				<span class="site-festival__icon" aria-hidden="true"><?php onedong_icon( $data['icon'] ); ?></span>
				<div class="site-festival__body">
					<strong class="site-festival__title"><?php echo esc_html( $data['title'] ); ?></strong>
					<?php if ( '' !== $data['meta'] ) : ?>
						<span class="site-festival__meta"><?php echo esc_html( $data['meta'] ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $data['desc'] ) : ?>
						<span class="site-festival__desc"><?php echo esc_html( $data['desc'] ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $dismissible ) : ?>
					<button class="site-festival__close" type="button" aria-label="<?php esc_attr_e( '关闭提示', 'onedong' ); ?>">&times;</button>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<script>
	// 节日条:anti-flash 预隐藏 + 关闭记忆 + 防页面缓存串天。与公告条同款内联约定,零额外请求。
	( function () {
		var el = document.currentScript.previousElementSibling;
		if ( ! el || ! el.classList || ! el.classList.contains( 'site-festival' ) ) { return; }
		var STORE = 'onedong-festival';
		var key = el.getAttribute( 'data-key' );
		var date = el.getAttribute( 'data-date' );
		var dismissible = el.getAttribute( 'data-dismissible' ) === '1';

		// 防串天:WP Super Cache 之类会把「今日立秋」缓存到第二天。
		// 服务端写的是北京日期,这里也反推访客侧的北京日期比对,对不上直接隐藏。
		try {
			var bj = new Date( Date.now() + ( 8 * 60 + new Date().getTimezoneOffset() ) * 60000 );
			var p = function ( n ) { return ( n < 10 ? '0' : '' ) + n; };
			var todayBJ = bj.getFullYear() + '-' + p( bj.getMonth() + 1 ) + '-' + p( bj.getDate() );
			if ( date && date !== todayBJ ) { el.classList.add( 'is-dismissed' ); return; }
		} catch ( e ) {}

		// 绘制前:今天这条已关闭 → 立即隐藏,无闪烁。
		try {
			if ( dismissible && localStorage.getItem( STORE ) === date + ':' + key ) {
				el.classList.add( 'is-dismissed' );
			}
		} catch ( e ) {}

		if ( ! dismissible ) { return; }
		var btn = el.querySelector( '.site-festival__close' );
		if ( ! btn ) { return; }
		btn.addEventListener( 'click', function () {
			try { localStorage.setItem( STORE, date + ':' + key ); } catch ( e ) {}
			var done = function () { el.classList.add( 'is-dismissed' ); el.classList.remove( 'is-closing' ); };
			var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			if ( reduce ) { done(); return; }
			el.classList.add( 'is-closing' );
			el.addEventListener( 'transitionend', done, { once: true } );
			setTimeout( done, 400 ); // 兜底:过渡未触发时也能移除
		} );
	} )();
	</script>
	<?php
}
