<?php
/**
 * 农历 / 24 节气 计算模块
 *
 * 纯 PHP 实现,无扩展依赖(不需要 intl / calendar)。算法为 Meeus《Astronomical
 * Algorithms》定朔(ch.49)+ 定气(ch.25 / VSOP87 截断)+ 无中气置闰,即 1929 年后
 * 紫金山天文台现行农历规则。
 *
 * 为什么不用 lunarInfo / sTermInfo 十六进制数据表:那是 201 项手抄十六进制,
 * 抄错一位就静默算错日期且极难发现;天文算法无大表、可回归验证。
 * 同源实现见 tools/lunar-verify.mjs,回归用例 58/58(春节 2018-2033、闰月含
 * 2033 闰十一月边界、节气跨 2000-2026、农历换算含闰月)。
 *
 * 时区:农历与节气以东八区定义,故本模块内部一律按 UTC+8 判定「日」,
 * 不跟随站点时区(站点设成 UTC 也不会把立秋算到前一天)。
 *
 * @package OneDong
 * @since 6.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // 禁止直接访问
}

/** 算法有效区间(超出返回空,不报错)。 */
const ONEDONG_LUNAR_MIN_YEAR = 1900;
const ONEDONG_LUNAR_MAX_YEAR = 2100;

/**
 * 年份是否在有效区间内。
 *
 * @param int $year 公历年。
 * @return bool
 */
function onedong_lunar_supported( $year ) {
	return $year >= ONEDONG_LUNAR_MIN_YEAR && $year <= ONEDONG_LUNAR_MAX_YEAR;
}

/* ============================================================
   基础数学 / 儒略日
   ============================================================ */

/**
 * 角度制正弦。
 *
 * @param float $deg 角度。
 * @return float
 */
function onedong_lunar_sin( $deg ) {
	return sin( deg2rad( $deg ) );
}

/**
 * 归一到 [0, 360)。
 *
 * @param float $x 角度。
 * @return float
 */
function onedong_lunar_mod360( $x ) {
	$r = fmod( (float) $x, 360.0 );
	return $r < 0 ? $r + 360.0 : $r;
}

/**
 * 公历 → 儒略日(0h UT 为 .5)。
 *
 * @param int   $y 年。
 * @param int   $m 月。
 * @param float $d 日(可含小数)。
 * @return float
 */
function onedong_lunar_greg_to_jd( $y, $m, $d ) {
	if ( $m <= 2 ) {
		$y -= 1;
		$m += 12;
	}
	$a = floor( $y / 100 );
	$b = 2 - $a + floor( $a / 4 );
	return floor( 365.25 * ( $y + 4716 ) ) + floor( 30.6001 * ( $m + 1 ) ) + $d + $b - 1524.5;
}

/**
 * 儒略日 → 公历。
 *
 * @param float $jd 儒略日。
 * @return array{y:int,m:int,d:int}
 */
function onedong_lunar_jd_to_greg( $jd ) {
	$z = floor( $jd + 0.5 );
	$f = $jd + 0.5 - $z;
	$a = $z;
	if ( $z >= 2299161 ) {
		$alpha = floor( ( $z - 1867216.25 ) / 36524.25 );
		$a     = $z + 1 + $alpha - floor( $alpha / 4 );
	}
	$b     = $a + 1524;
	$c     = floor( ( $b - 122.1 ) / 365.25 );
	$d     = floor( 365.25 * $c );
	$e     = floor( ( $b - $d ) / 30.6001 );
	$day   = $b - $d - floor( 30.6001 * $e ) + $f;
	$month = $e < 14 ? $e - 1 : $e - 13;
	$year  = $month > 2 ? $c - 4716 : $c - 4715;
	return array(
		'y' => (int) $year,
		'm' => (int) $month,
		'd' => (int) floor( $day ),
	);
}

/**
 * 公历日期 → 北京日序(JDN 整数)。
 *
 * @param int $y 年。
 * @param int $m 月。
 * @param int $d 日。
 * @return float
 */
function onedong_lunar_bjday_from_greg( $y, $m, $d ) {
	return onedong_lunar_greg_to_jd( $y, $m, $d ) + 0.5;
}

/**
 * 北京日序 → 公历日期。
 *
 * @param float $n 北京日序。
 * @return array{y:int,m:int,d:int}
 */
function onedong_lunar_greg_from_bjday( $n ) {
	return onedong_lunar_jd_to_greg( $n - 0.5 );
}

/**
 * ΔT(TT − UT),Espenak & Meeus 多项式,单位秒。
 *
 * @param int $year  年。
 * @param int $month 月。
 * @return float
 */
function onedong_lunar_delta_t( $year, $month ) {
	$y = $year + ( $month - 0.5 ) / 12;

	if ( $y < 1920 ) {
		$t = $y - 1900;
		return -2.79 + 1.494119 * $t - 0.0598939 * pow( $t, 2 ) + 0.0061966 * pow( $t, 3 ) - 0.000197 * pow( $t, 4 );
	}
	if ( $y < 1941 ) {
		$t = $y - 1920;
		return 21.20 + 0.84493 * $t - 0.076100 * pow( $t, 2 ) + 0.0020936 * pow( $t, 3 );
	}
	if ( $y < 1961 ) {
		$t = $y - 1950;
		return 29.07 + 0.407 * $t - pow( $t, 2 ) / 233 + pow( $t, 3 ) / 2547;
	}
	if ( $y < 1986 ) {
		$t = $y - 1975;
		return 45.45 + 1.067 * $t - pow( $t, 2 ) / 260 - pow( $t, 3 ) / 718;
	}
	if ( $y < 2005 ) {
		$t = $y - 2000;
		return 63.86 + 0.3345 * $t - 0.060374 * pow( $t, 2 ) + 0.0017275 * pow( $t, 3 )
			+ 0.000651814 * pow( $t, 4 ) + 0.00002373599 * pow( $t, 5 );
	}
	if ( $y < 2050 ) {
		$t = $y - 2000;
		return 62.92 + 0.32217 * $t + 0.005589 * pow( $t, 2 );
	}
	if ( $y < 2150 ) {
		return -20 + 32 * pow( ( $y - 1820 ) / 100, 2 ) - 0.5628 * ( 2150 - $y );
	}
	$u = ( $y - 1820 ) / 100;
	return -20 + 32 * $u * $u;
}

/**
 * 力学时 JDE → 北京日序。
 *
 * @param float $jde 力学时儒略日。
 * @return float
 */
function onedong_lunar_tt_to_bjday( $jde ) {
	$g  = onedong_lunar_jd_to_greg( $jde );
	$dt = onedong_lunar_delta_t( $g['y'], $g['m'] ) / 86400;
	return floor( $jde - $dt + 8 / 24 + 0.5 );
}

/* ============================================================
   太阳视黄经(VSOP87D 截断级数 + FK5 + 章动 + 光行差)

   Meeus ch.25 简算式精度 0.01° ≈ 15 分钟,对逼近午夜的节气有整日翻天风险
   (实测 2026 雨水偏早 8 分钟)。这里用 VSOP87 截断,残差 < 0.0001° ≈ 8 秒。
   ============================================================ */

/**
 * 地球日心黄经级数求和。
 *
 * @param array $terms 级数项 [振幅, 相位, 频率]。
 * @param float $tau   儒略千年数。
 * @return float
 */
function onedong_lunar_vsop_series( $terms, $tau ) {
	$sum = 0.0;
	foreach ( $terms as $t ) {
		$sum += $t[0] * cos( $t[1] + $t[2] * $tau );
	}
	return $sum;
}

/**
 * 太阳视黄经(度)。
 *
 * @param float $jde 力学时儒略日。
 * @return float
 */
function onedong_lunar_sun_lon( $jde ) {
	// 地球日心黄经 L0..L4(单位 1e-8 弧度),按振幅降序截断。
	static $l0 = null;
	static $l1 = null;
	static $l2 = null;
	static $l3 = null;
	static $l4 = null;

	if ( null === $l0 ) {
		$l0 = array(
			array( 175347046, 0, 0 ), array( 3341656, 4.6692568, 6283.07585 ), array( 34894, 4.6261, 12566.1517 ),
			array( 3497, 2.7441, 5753.3849 ), array( 3418, 2.8289, 3.5231 ), array( 3136, 3.6277, 77713.7715 ),
			array( 2676, 4.4181, 7860.4194 ), array( 2343, 6.1352, 3930.2097 ), array( 1324, 0.7425, 11506.7698 ),
			array( 1273, 2.0371, 529.691 ), array( 1199, 1.1096, 1577.3435 ), array( 990, 5.233, 5884.927 ),
			array( 902, 2.045, 26.298 ), array( 857, 3.508, 398.149 ), array( 780, 1.179, 5223.694 ),
			array( 753, 2.533, 5507.553 ), array( 505, 4.583, 18849.228 ), array( 492, 4.205, 775.523 ),
			array( 357, 2.92, 0.067 ), array( 317, 5.849, 11790.629 ), array( 284, 1.899, 796.298 ),
			array( 271, 0.315, 10977.079 ), array( 243, 0.345, 5486.778 ), array( 206, 4.806, 2544.314 ),
			array( 205, 1.869, 5573.143 ), array( 202, 2.458, 6069.777 ),
		);
		$l1 = array(
			array( 628331966747, 0, 0 ), array( 206059, 2.678235, 6283.07585 ), array( 4303, 2.6351, 12566.1517 ),
			array( 425, 1.59, 3.523 ), array( 119, 5.796, 26.298 ), array( 109, 2.966, 1577.344 ),
			array( 93, 2.59, 18849.23 ), array( 72, 1.14, 529.69 ), array( 68, 1.87, 398.15 ),
			array( 67, 4.41, 5507.55 ),
		);
		$l2 = array(
			array( 52919, 0, 0 ), array( 8720, 1.0721, 6283.0758 ), array( 309, 0.867, 12566.152 ),
			array( 27, 0.05, 3.52 ),
		);
		$l3 = array( array( 289, 5.844, 6283.076 ), array( 35, 0, 0 ) );
		$l4 = array( array( 114, 3.142, 0 ) );
	}

	$tau = ( $jde - 2451545.0 ) / 365250;
	$t   = $tau * 10; // 儒略世纪

	// 地球日心黄经 → 太阳地心黄经
	$l_rad = ( onedong_lunar_vsop_series( $l0, $tau )
		+ onedong_lunar_vsop_series( $l1, $tau ) * $tau
		+ onedong_lunar_vsop_series( $l2, $tau ) * pow( $tau, 2 )
		+ onedong_lunar_vsop_series( $l3, $tau ) * pow( $tau, 3 )
		+ onedong_lunar_vsop_series( $l4, $tau ) * pow( $tau, 4 ) ) / 1e8;
	$theta = onedong_lunar_mod360( rad2deg( $l_rad ) + 180 );

	// FK5 归算(太阳纬度≈0,只剩常数项)
	$theta -= 0.09033 / 3600;

	// 章动(Meeus ch.22 简式,精度 0.5")
	$om   = 125.04452 - 1934.136261 * $t;
	$ls   = 280.4665 + 36000.7698 * $t;
	$lm   = 218.3165 + 481267.8813 * $t;
	$dpsi = ( -17.20 * onedong_lunar_sin( $om ) - 1.32 * onedong_lunar_sin( 2 * $ls )
		- 0.23 * onedong_lunar_sin( 2 * $lm ) + 0.21 * onedong_lunar_sin( 2 * $om ) ) / 3600;

	// 光行差 −20.4898″/R
	$ecc   = 0.016708634 - 0.000042037 * $t - 0.0000001267 * $t * $t;
	$ma    = 357.52911 + 35999.05029 * $t - 0.0001537 * $t * $t;
	$ctr   = ( 1.914602 - 0.004817 * $t - 0.000014 * $t * $t ) * onedong_lunar_sin( $ma )
		+ ( 0.019993 - 0.000101 * $t ) * onedong_lunar_sin( 2 * $ma )
		+ 0.000289 * onedong_lunar_sin( 3 * $ma );
	$rad   = 1.000001018 * ( 1 - $ecc * $ecc ) / ( 1 + $ecc * cos( deg2rad( $ma + $ctr ) ) );
	$aberr = -20.4898 / $rad / 3600;

	return onedong_lunar_mod360( $theta + $dpsi + $aberr );
}

/**
 * 从 $jd_guess 出发,迭代求视黄经 = $lon_target 的力学时刻。
 *
 * @param float $jd_guess   起算儒略日。
 * @param float $lon_target 目标黄经(度)。
 * @return float 力学时 JDE。
 */
function onedong_lunar_solve_sun_lon( $jd_guess, $lon_target ) {
	$jd = $jd_guess;
	for ( $i = 0; $i < 20; $i++ ) {
		$lon  = onedong_lunar_sun_lon( $jd );
		$diff = onedong_lunar_mod360( $lon_target - $lon + 180 ) - 180;
		$jd  += $diff * 365.2422 / 360;
		if ( abs( $diff ) < 1e-9 ) {
			break;
		}
	}
	return $jd;
}

/**
 * 求某「太阳年」的节气时刻。$lon_target 以春分 0° 为起点,起算取该年 3 月 20 日,
 * 故 285°(小寒)/ 300°(大寒)落在次年 1 月。
 *
 * @param int   $year       公历年。
 * @param float $lon_target 目标黄经。
 * @return float 力学时 JDE。
 */
function onedong_lunar_term_jde( $year, $lon_target ) {
	return onedong_lunar_solve_sun_lon(
		onedong_lunar_greg_to_jd( $year, 3, 20.5 ) + $lon_target * 365.2422 / 360,
		$lon_target
	);
}

/**
 * 冬至(黄经 270°)所在的北京日序。
 *
 * @param int $year 公历年。
 * @return float
 */
function onedong_lunar_winter_solstice_bjday( $year ) {
	return onedong_lunar_tt_to_bjday(
		onedong_lunar_solve_sun_lon( onedong_lunar_greg_to_jd( $year, 12, 21.5 ), 270 )
	);
}

/* ============================================================
   定朔(Meeus ch.49,精度约数秒)
   ============================================================ */

/**
 * 第 $k 个朔(k=0 为 2000-01-06)的力学时 JDE。
 *
 * @param int $k 朔序号。
 * @return float
 */
function onedong_lunar_new_moon_jde( $k ) {
	// 附加修正 A1..A14:[振幅, 常数项, k 系数, T² 系数]
	static $additional = null;
	if ( null === $additional ) {
		$additional = array(
			array( 0.000325, 299.77, 0.107408, -0.009173 ),
			array( 0.000165, 251.88, 0.016321, 0 ),
			array( 0.000164, 251.83, 26.651886, 0 ),
			array( 0.000126, 349.42, 36.412478, 0 ),
			array( 0.000110, 84.66, 18.206239, 0 ),
			array( 0.000062, 141.74, 53.303771, 0 ),
			array( 0.000060, 207.14, 2.453732, 0 ),
			array( 0.000056, 154.84, 7.306860, 0 ),
			array( 0.000047, 34.52, 27.261239, 0 ),
			array( 0.000042, 207.19, 0.121824, 0 ),
			array( 0.000040, 291.34, 1.844379, 0 ),
			array( 0.000037, 161.72, 24.198154, 0 ),
			array( 0.000035, 239.56, 25.513099, 0 ),
			array( 0.000023, 331.55, 3.592518, 0 ),
		);
	}

	$t   = $k / 1236.85;
	$jde = 2451550.09766 + 29.530588861 * $k
		+ 0.00015437 * $t * $t - 0.000000150 * pow( $t, 3 ) + 0.00000000073 * pow( $t, 4 );

	$e  = 1 - 0.002516 * $t - 0.0000074 * $t * $t;
	$m  = 2.5534 + 29.10535670 * $k - 0.0000014 * $t * $t - 0.00000011 * pow( $t, 3 );
	$mp = 201.5643 + 385.81693528 * $k + 0.0107582 * $t * $t
		+ 0.00001238 * pow( $t, 3 ) - 0.000000058 * pow( $t, 4 );
	$f  = 160.7108 + 390.67050284 * $k - 0.0016118 * $t * $t
		- 0.00000227 * pow( $t, 3 ) + 0.000000011 * pow( $t, 4 );
	$om = 124.7746 - 1.56375588 * $k + 0.0020672 * $t * $t + 0.00000215 * pow( $t, 3 );

	$s = 'onedong_lunar_sin';

	$jde += -0.40720 * $s( $mp )
		+ 0.17241 * $e * $s( $m )
		+ 0.01608 * $s( 2 * $mp )
		+ 0.01039 * $s( 2 * $f )
		+ 0.00739 * $e * $s( $mp - $m )
		- 0.00514 * $e * $s( $mp + $m )
		+ 0.00208 * $e * $e * $s( 2 * $m )
		- 0.00111 * $s( $mp - 2 * $f )
		- 0.00057 * $s( $mp + 2 * $f )
		+ 0.00056 * $e * $s( 2 * $mp + $m )
		- 0.00042 * $s( 3 * $mp )
		+ 0.00042 * $e * $s( $m + 2 * $f )
		+ 0.00038 * $e * $s( $m - 2 * $f )
		- 0.00024 * $e * $s( 2 * $mp - $m )
		- 0.00017 * $s( $om )
		- 0.00007 * $s( $mp + 2 * $m )
		+ 0.00004 * $s( 2 * $mp - 2 * $f )
		+ 0.00004 * $s( 3 * $m )
		+ 0.00003 * $s( $mp + $m - 2 * $f )
		+ 0.00003 * $s( 2 * $mp + 2 * $f )
		- 0.00003 * $s( $mp + $m + 2 * $f )
		+ 0.00003 * $s( $mp - $m + 2 * $f )
		- 0.00002 * $s( $mp - $m - 2 * $f )
		- 0.00002 * $s( 3 * $mp + $m )
		+ 0.00002 * $s( 4 * $mp );

	foreach ( $additional as $a ) {
		$jde += $a[0] * $s( $a[1] + $a[2] * $k + $a[3] * $t * $t );
	}

	return $jde;
}

/**
 * 第 $k 个朔所在的北京日序(带缓存)。
 *
 * @param int $k 朔序号。
 * @return float
 */
function onedong_lunar_new_moon_day( $k ) {
	static $cache = array();
	$key = (string) $k;
	if ( ! isset( $cache[ $key ] ) ) {
		$cache[ $key ] = onedong_lunar_tt_to_bjday( onedong_lunar_new_moon_jde( $k ) );
	}
	return $cache[ $key ];
}

/**
 * 找出使 new_moon_day(k) <= $day < new_moon_day(k+1) 的 k。
 *
 * @param float $day 北京日序。
 * @return int
 */
function onedong_lunar_new_moon_index_on_or_before( $day ) {
	$k = (int) round( ( $day - 2451550.09766 ) / 29.530588861 );
	while ( onedong_lunar_new_moon_day( $k ) > $day ) {
		$k--;
	}
	while ( onedong_lunar_new_moon_day( $k + 1 ) <= $day ) {
		$k++;
	}
	return $k;
}

/* ============================================================
   农历排月:冬至定十一月 + 无中气置闰
   ============================================================ */

/**
 * [$start_day, $end_day) 内是否含中气(黄经 30 的倍数)。
 *
 * @param float $start_day 月首北京日序。
 * @param float $end_day   次月首北京日序。
 * @return bool
 */
function onedong_lunar_has_major_term( $start_day, $end_day ) {
	// 该月首日 0 时(北京)对应的力学时,用于取当时太阳黄经
	$jd_approx = $start_day - 0.5 - 8 / 24;
	$lon       = onedong_lunar_sun_lon( $jd_approx );
	$target    = onedong_lunar_mod360( floor( $lon / 30 ) * 30 + 30 );
	$jde       = onedong_lunar_solve_sun_lon( $jd_approx, $target );
	$day       = onedong_lunar_tt_to_bjday( $jde );
	return $day >= $start_day && $day < $end_day;
}

/**
 * 排出「十一月($year−1)→ 十月($year)」这一整轮农历月。
 *
 * @param int $year 公历年。
 * @return array{months:array,next_eleventh:float}
 */
function onedong_lunar_months( $year ) {
	static $cache = array();
	$key = (string) $year;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$ws0 = onedong_lunar_winter_solstice_bjday( $year - 1 );
	$ws1 = onedong_lunar_winter_solstice_bjday( $year );
	$k0  = onedong_lunar_new_moon_index_on_or_before( $ws0 ); // 含冬至的朔望月 = 十一月
	$k1  = onedong_lunar_new_moon_index_on_or_before( $ws1 );
	$n   = $k1 - $k0; // 12(平)或 13(闰)

	$leap_index = -1;
	if ( 13 === $n ) {
		for ( $i = 1; $i <= 12; $i++ ) {
			if ( ! onedong_lunar_has_major_term(
				onedong_lunar_new_moon_day( $k0 + $i ),
				onedong_lunar_new_moon_day( $k0 + $i + 1 )
			) ) {
				$leap_index = $i;
				break;
			}
		}
	}

	$months     = array();
	$num        = 11;
	$prev_num   = 11;
	$lunar_year = $year - 1;
	for ( $i = 0; $i < $n; $i++ ) {
		$is_leap = ( $i === $leap_index );
		if ( ! $is_leap && 1 === $num ) {
			$lunar_year = $year;
		}
		$months[] = array(
			'start'      => onedong_lunar_new_moon_day( $k0 + $i ),
			'num'        => $is_leap ? $prev_num : $num,
			'leap'       => $is_leap,
			'lunar_year' => $lunar_year,
		);
		if ( ! $is_leap ) {
			$prev_num = $num;
			$num      = ( $num % 12 ) + 1;
		}
	}

	$cache[ $key ] = array(
		'months'        => $months,
		'next_eleventh' => onedong_lunar_new_moon_day( $k1 ),
	);
	return $cache[ $key ];
}

/* ============================================================
   对外:公历 → 农历
   ============================================================ */

/**
 * 农历日中文(初一 / 十五 / 廿三 / 三十)。
 *
 * @param int $d 农历日 1-30。
 * @return string
 */
function onedong_lunar_day_cn( $d ) {
	$cn = array( '', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十' );
	if ( 10 === $d ) {
		return '初十';
	}
	if ( 20 === $d ) {
		return '二十';
	}
	if ( 30 === $d ) {
		return '三十';
	}
	if ( $d < 10 ) {
		return '初' . $cn[ $d ];
	}
	if ( $d < 20 ) {
		return '十' . $cn[ $d - 10 ];
	}
	return '廿' . $cn[ $d - 20 ];
}

/**
 * 公历 → 农历。
 *
 * @param int $y 公历年。
 * @param int $m 公历月。
 * @param int $d 公历日。
 * @return array|null 超出有效区间返回 null。
 */
function onedong_lunar_from_solar( $y, $m, $d ) {
	if ( ! onedong_lunar_supported( $y ) ) {
		return null;
	}

	// 十一月按传统写作「冬月」,十二月写作「腊月」
	$month_cn = array( '', '正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '冬', '腊' );
	$gan      = array( '甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸' );
	$zhi      = array( '子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥' );
	$zodiac   = array( '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪' );

	$day  = onedong_lunar_bjday_from_greg( $y, $m, $d );
	$info = onedong_lunar_months( $y );
	if ( $day < $info['months'][0]['start'] ) {
		$info = onedong_lunar_months( $y - 1 );
	} elseif ( $day >= $info['next_eleventh'] ) {
		$info = onedong_lunar_months( $y + 1 );
	}

	$idx = 0;
	for ( $i = count( $info['months'] ) - 1; $i >= 0; $i-- ) {
		if ( $day >= $info['months'][ $i ]['start'] ) {
			$idx = $i;
			break;
		}
	}

	$mo       = $info['months'][ $idx ];
	$day_num  = (int) ( $day - $mo['start'] + 1 );
	$gz_index = $mo['lunar_year'] - 4;
	$gz_10    = ( ( $gz_index % 10 ) + 10 ) % 10;
	$gz_12    = ( ( $gz_index % 12 ) + 12 ) % 12;

	$month_text = ( $mo['leap'] ? '闰' : '' ) . $month_cn[ $mo['num'] ] . '月';
	$day_text   = onedong_lunar_day_cn( $day_num );

	return array(
		'year'       => $mo['lunar_year'],
		'month'      => $mo['num'],
		'day'        => $day_num,
		'leap'       => $mo['leap'],
		'month_text' => $month_text,
		'day_text'   => $day_text,
		'text'       => $month_text . $day_text,
		'ganzhi'     => $gan[ $gz_10 ] . $zhi[ $gz_12 ],
		'zodiac'     => $zodiac[ $gz_12 ],
	);
}

/* ============================================================
   对外:24 节气
   ============================================================ */

/**
 * 24 节气名(按黄经 0°/15°/…/345° 顺序,即春分起算)。
 *
 * @return string[]
 */
function onedong_lunar_term_names() {
	return array(
		'春分', '清明', '谷雨', '立夏', '小满', '芒种',
		'夏至', '小暑', '大暑', '立秋', '处暑', '白露',
		'秋分', '寒露', '霜降', '立冬', '小雪', '大雪',
		'冬至', '小寒', '大寒', '立春', '雨水', '惊蛰',
	);
}

/**
 * 某公历年的全部 24 节气,返回 [ 'Y-m-d' => 节气名 ](带缓存)。
 *
 * @param int $year 公历年。
 * @return array
 */
function onedong_lunar_terms_of_year( $year ) {
	static $cache = array();
	$key = (string) $year;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	if ( ! onedong_lunar_supported( $year ) ) {
		return array();
	}

	$names = onedong_lunar_term_names();
	$out   = array();
	for ( $i = 0; $i < 24; $i++ ) {
		// 每个目标黄经在 year−1 与 year 两个太阳年各解一次,取落在 year 内的那次
		foreach ( array( $year - 1, $year ) as $base ) {
			$g = onedong_lunar_greg_from_bjday(
				onedong_lunar_tt_to_bjday( onedong_lunar_term_jde( $base, $i * 15 ) )
			);
			if ( $g['y'] === $year ) {
				$out[ sprintf( '%04d-%02d-%02d', $g['y'], $g['m'], $g['d'] ) ] = $names[ $i ];
			}
		}
	}

	$cache[ $key ] = $out;
	return $out;
}

/**
 * 指定日期是否为节气,是则返回节气名,否则空串。
 *
 * @param int $y 公历年。
 * @param int $m 公历月。
 * @param int $d 公历日。
 * @return string
 */
function onedong_lunar_term_on( $y, $m, $d ) {
	$terms = onedong_lunar_terms_of_year( $y );
	$key   = sprintf( '%04d-%02d-%02d', $y, $m, $d );
	return isset( $terms[ $key ] ) ? $terms[ $key ] : '';
}

/* ============================================================
   对外:北京「今天」
   ============================================================ */

/**
 * 北京时间的今天。农历 / 节气以东八区定义,故不跟随站点时区。
 *
 * @return array{y:int,m:int,d:int}
 */
function onedong_lunar_today_bj() {
	$dt = new DateTime( '@' . time() );
	$dt->setTimezone( new DateTimeZone( 'Asia/Shanghai' ) );
	return array(
		'y' => (int) $dt->format( 'Y' ),
		'm' => (int) $dt->format( 'n' ),
		'd' => (int) $dt->format( 'j' ),
	);
}
