/**
 * 农历 / 24 节气 算法回归校验
 *
 * 与 inc/lunar.php 同源实现(逐行对应),用于本机无 PHP 时验证算法正确性。
 * 算法:Meeus《Astronomical Algorithms》定朔(ch.49)+ 定气(ch.25)+ 无中气置闰,
 * 即 1929 年后紫金山天文台现行农历规则。全部按 UTC+8(北京时间)判定日期。
 *
 * 用法:node tools/lunar-verify.mjs
 */

const RAD = Math.PI / 180;
const sin = ( deg ) => Math.sin( deg * RAD );
const mod360 = ( x ) => ( ( x % 360 ) + 360 ) % 360;

/* ============================================================
   儒略日
   ============================================================ */

function gregToJD( y, m, d ) {
	if ( m <= 2 ) {
		y -= 1;
		m += 12;
	}
	const a = Math.floor( y / 100 );
	const b = 2 - a + Math.floor( a / 4 );
	return Math.floor( 365.25 * ( y + 4716 ) ) + Math.floor( 30.6001 * ( m + 1 ) ) + d + b - 1524.5;
}

function jdToGreg( jd ) {
	const z = Math.floor( jd + 0.5 );
	const f = jd + 0.5 - z;
	let a = z;
	if ( z >= 2299161 ) {
		const alpha = Math.floor( ( z - 1867216.25 ) / 36524.25 );
		a = z + 1 + alpha - Math.floor( alpha / 4 );
	}
	const b = a + 1524;
	const c = Math.floor( ( b - 122.1 ) / 365.25 );
	const d = Math.floor( 365.25 * c );
	const e = Math.floor( ( b - d ) / 30.6001 );
	const day = b - d - Math.floor( 30.6001 * e ) + f;
	const month = e < 14 ? e - 1 : e - 13;
	const year = month > 2 ? c - 4716 : c - 4715;
	return { y: year, m: month, d: Math.floor( day ) };
}

/** 公历日期 → 北京日序(JDN 整数)。 */
function bjDayFromGreg( y, m, d ) {
	return gregToJD( y, m, d ) + 0.5;
}

/** 北京日序 → 公历日期。 */
function gregFromBJDay( n ) {
	return jdToGreg( n - 0.5 );
}

/* ============================================================
   ΔT(TT − UT),Espenak & Meeus 多项式
   ============================================================ */

function deltaT( year, month ) {
	const y = year + ( month - 0.5 ) / 12;
	let t;
	if ( y < 1920 ) {
		t = y - 1900;
		return -2.79 + 1.494119 * t - 0.0598939 * t ** 2 + 0.0061966 * t ** 3 - 0.000197 * t ** 4;
	}
	if ( y < 1941 ) {
		t = y - 1920;
		return 21.20 + 0.84493 * t - 0.076100 * t ** 2 + 0.0020936 * t ** 3;
	}
	if ( y < 1961 ) {
		t = y - 1950;
		return 29.07 + 0.407 * t - t ** 2 / 233 + t ** 3 / 2547;
	}
	if ( y < 1986 ) {
		t = y - 1975;
		return 45.45 + 1.067 * t - t ** 2 / 260 - t ** 3 / 718;
	}
	if ( y < 2005 ) {
		t = y - 2000;
		return 63.86 + 0.3345 * t - 0.060374 * t ** 2 + 0.0017275 * t ** 3
			+ 0.000651814 * t ** 4 + 0.00002373599 * t ** 5;
	}
	if ( y < 2050 ) {
		t = y - 2000;
		return 62.92 + 0.32217 * t + 0.005589 * t ** 2;
	}
	if ( y < 2150 ) {
		return -20 + 32 * ( ( y - 1820 ) / 100 ) ** 2 - 0.5628 * ( 2150 - y );
	}
	const u = ( y - 1820 ) / 100;
	return -20 + 32 * u * u;
}

/** 力学时 JDE → 北京日序。 */
function ttToBJDay( jde ) {
	const g = jdToGreg( jde );
	const dt = deltaT( g.y, g.m ) / 86400;
	return Math.floor( jde - dt + 8 / 24 + 0.5 );
}

/** 力学时 JDE → 北京时间(用于打印/调试)。 */
function ttToBJString( jde ) {
	const g0 = jdToGreg( jde );
	const dt = deltaT( g0.y, g0.m ) / 86400;
	const jdBJ = jde - dt + 8 / 24;
	const g = jdToGreg( jdBJ );
	const frac = jdBJ + 0.5 - Math.floor( jdBJ + 0.5 );
	const hh = Math.floor( frac * 24 );
	const mm = Math.floor( ( frac * 24 - hh ) * 60 );
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return `${ g.y }-${ pad( g.m ) }-${ pad( g.d ) } ${ pad( hh ) }:${ pad( mm ) }`;
}

/* ============================================================
   太阳视黄经(VSOP87D 截断级数 + FK5 + 章动 + 光行差)

   早期版本用 Meeus ch.25 简算式(0.01° ≈ 15 分钟),对逼近午夜的节气
   有整日翻天风险(实测 2026 雨水偏早 8 分钟)。这里改用 VSOP87 截断,
   截断残差 < 0.0001° ≈ 8 秒,足以保证任何节气的「日」判定稳定。
   ============================================================ */

// 地球日心黄经 L0..L4(单位 1e-8 弧度),按振幅降序截断。
const VSOP_L0 = [
	[ 175347046, 0, 0 ], [ 3341656, 4.6692568, 6283.07585 ], [ 34894, 4.6261, 12566.1517 ],
	[ 3497, 2.7441, 5753.3849 ], [ 3418, 2.8289, 3.5231 ], [ 3136, 3.6277, 77713.7715 ],
	[ 2676, 4.4181, 7860.4194 ], [ 2343, 6.1352, 3930.2097 ], [ 1324, 0.7425, 11506.7698 ],
	[ 1273, 2.0371, 529.691 ], [ 1199, 1.1096, 1577.3435 ], [ 990, 5.233, 5884.927 ],
	[ 902, 2.045, 26.298 ], [ 857, 3.508, 398.149 ], [ 780, 1.179, 5223.694 ],
	[ 753, 2.533, 5507.553 ], [ 505, 4.583, 18849.228 ], [ 492, 4.205, 775.523 ],
	[ 357, 2.92, 0.067 ], [ 317, 5.849, 11790.629 ], [ 284, 1.899, 796.298 ],
	[ 271, 0.315, 10977.079 ], [ 243, 0.345, 5486.778 ], [ 206, 4.806, 2544.314 ],
	[ 205, 1.869, 5573.143 ], [ 202, 2.458, 6069.777 ],
];
const VSOP_L1 = [
	[ 628331966747, 0, 0 ], [ 206059, 2.678235, 6283.07585 ], [ 4303, 2.6351, 12566.1517 ],
	[ 425, 1.59, 3.523 ], [ 119, 5.796, 26.298 ], [ 109, 2.966, 1577.344 ],
	[ 93, 2.59, 18849.23 ], [ 72, 1.14, 529.69 ], [ 68, 1.87, 398.15 ],
	[ 67, 4.41, 5507.55 ],
];
const VSOP_L2 = [
	[ 52919, 0, 0 ], [ 8720, 1.0721, 6283.0758 ], [ 309, 0.867, 12566.152 ],
	[ 27, 0.05, 3.52 ],
];
const VSOP_L3 = [ [ 289, 5.844, 6283.076 ], [ 35, 0, 0 ] ];
const VSOP_L4 = [ [ 114, 3.142, 0 ] ];

function vsopSeries( terms, tau ) {
	let sum = 0;
	for ( const [ a, b, c ] of terms ) {
		sum += a * Math.cos( b + c * tau );
	}
	return sum;
}

function sunApparentLon( jde ) {
	const tau = ( jde - 2451545.0 ) / 365250;
	const t = tau * 10; // 儒略世纪

	// 地球日心黄经 → 太阳地心黄经
	const lRad = ( vsopSeries( VSOP_L0, tau )
		+ vsopSeries( VSOP_L1, tau ) * tau
		+ vsopSeries( VSOP_L2, tau ) * tau ** 2
		+ vsopSeries( VSOP_L3, tau ) * tau ** 3
		+ vsopSeries( VSOP_L4, tau ) * tau ** 4 ) / 1e8;
	let theta = mod360( lRad / RAD + 180 );

	// FK5 归算(太阳纬度≈0,只剩常数项)
	theta -= 0.09033 / 3600;

	// 章动(Meeus ch.22 简式,精度 0.5")
	const om = 125.04452 - 1934.136261 * t;
	const ls = 280.4665 + 36000.7698 * t;
	const lm = 218.3165 + 481267.8813 * t;
	const dpsi = ( -17.20 * sin( om ) - 1.32 * sin( 2 * ls )
		- 0.23 * sin( 2 * lm ) + 0.21 * sin( 2 * om ) ) / 3600;

	// 光行差 −20.4898″/R
	const ecc = 0.016708634 - 0.000042037 * t - 0.0000001267 * t * t;
	const ma = 357.52911 + 35999.05029 * t - 0.0001537 * t * t;
	const ctr = ( 1.914602 - 0.004817 * t - 0.000014 * t * t ) * sin( ma )
		+ ( 0.019993 - 0.000101 * t ) * sin( 2 * ma )
		+ 0.000289 * sin( 3 * ma );
	const rad = 1.000001018 * ( 1 - ecc * ecc ) / ( 1 + ecc * Math.cos( ( ma + ctr ) * RAD ) );
	const aberr = -20.4898 / rad / 3600;

	return mod360( theta + dpsi + aberr );
}

/** 从 jdGuess 出发,迭代求视黄经 = lonTarget 的力学时刻。 */
function solveSunLon( jdGuess, lonTarget ) {
	let jd = jdGuess;
	for ( let i = 0; i < 20; i++ ) {
		const lon = sunApparentLon( jd );
		let diff = ( ( lonTarget - lon + 180 ) % 360 + 360 ) % 360 - 180;
		jd += diff * 365.2422 / 360;
		if ( Math.abs( diff ) < 1e-9 ) {
			break;
		}
	}
	return jd;
}

/**
 * 求某「太阳年」的节气时刻。lonTarget 以春分 0° 为起点,
 * 起算点取 year 年 3 月 20 日,故 285°(小寒)/300°(大寒) 落在 year+1 年 1 月。
 */
function termJDE( year, lonTarget ) {
	return solveSunLon( gregToJD( year, 3, 20.5 ) + lonTarget * 365.2422 / 360, lonTarget );
}

/** 冬至(黄经 270°)所在的北京日序。 */
function winterSolsticeBJDay( year ) {
	return ttToBJDay( solveSunLon( gregToJD( year, 12, 21.5 ), 270 ) );
}

/* ============================================================
   定朔(Meeus ch.49,精度约数秒)
   ============================================================ */

const NEW_MOON_ADDITIONAL = [
	[ 0.000325, 299.77, 0.107408, -0.009173 ],
	[ 0.000165, 251.88, 0.016321, 0 ],
	[ 0.000164, 251.83, 26.651886, 0 ],
	[ 0.000126, 349.42, 36.412478, 0 ],
	[ 0.000110, 84.66, 18.206239, 0 ],
	[ 0.000062, 141.74, 53.303771, 0 ],
	[ 0.000060, 207.14, 2.453732, 0 ],
	[ 0.000056, 154.84, 7.306860, 0 ],
	[ 0.000047, 34.52, 27.261239, 0 ],
	[ 0.000042, 207.19, 0.121824, 0 ],
	[ 0.000040, 291.34, 1.844379, 0 ],
	[ 0.000037, 161.72, 24.198154, 0 ],
	[ 0.000035, 239.56, 25.513099, 0 ],
	[ 0.000023, 331.55, 3.592518, 0 ],
];

/** 第 k 个朔(k=0 为 2000-01-06)的力学时 JDE。 */
function newMoonJDE( k ) {
	const t = k / 1236.85;
	let jde = 2451550.09766 + 29.530588861 * k
		+ 0.00015437 * t * t - 0.000000150 * t ** 3 + 0.00000000073 * t ** 4;

	const e = 1 - 0.002516 * t - 0.0000074 * t * t;
	const m = 2.5534 + 29.10535670 * k - 0.0000014 * t * t - 0.00000011 * t ** 3;
	const mp = 201.5643 + 385.81693528 * k + 0.0107582 * t * t
		+ 0.00001238 * t ** 3 - 0.000000058 * t ** 4;
	const f = 160.7108 + 390.67050284 * k - 0.0016118 * t * t
		- 0.00000227 * t ** 3 + 0.000000011 * t ** 4;
	const om = 124.7746 - 1.56375588 * k + 0.0020672 * t * t + 0.00000215 * t ** 3;

	jde += -0.40720 * sin( mp )
		+ 0.17241 * e * sin( m )
		+ 0.01608 * sin( 2 * mp )
		+ 0.01039 * sin( 2 * f )
		+ 0.00739 * e * sin( mp - m )
		- 0.00514 * e * sin( mp + m )
		+ 0.00208 * e * e * sin( 2 * m )
		- 0.00111 * sin( mp - 2 * f )
		- 0.00057 * sin( mp + 2 * f )
		+ 0.00056 * e * sin( 2 * mp + m )
		- 0.00042 * sin( 3 * mp )
		+ 0.00042 * e * sin( m + 2 * f )
		+ 0.00038 * e * sin( m - 2 * f )
		- 0.00024 * e * sin( 2 * mp - m )
		- 0.00017 * sin( om )
		- 0.00007 * sin( mp + 2 * m )
		+ 0.00004 * sin( 2 * mp - 2 * f )
		+ 0.00004 * sin( 3 * m )
		+ 0.00003 * sin( mp + m - 2 * f )
		+ 0.00003 * sin( 2 * mp + 2 * f )
		- 0.00003 * sin( mp + m + 2 * f )
		+ 0.00003 * sin( mp - m + 2 * f )
		- 0.00002 * sin( mp - m - 2 * f )
		- 0.00002 * sin( 3 * mp + m )
		+ 0.00002 * sin( 4 * mp );

	for ( const [ amp, a0, a1, a2 ] of NEW_MOON_ADDITIONAL ) {
		jde += amp * sin( a0 + a1 * k + a2 * t * t );
	}
	return jde;
}

const nmDayCache = new Map();
function newMoonBJDay( k ) {
	if ( ! nmDayCache.has( k ) ) {
		nmDayCache.set( k, ttToBJDay( newMoonJDE( k ) ) );
	}
	return nmDayCache.get( k );
}

/** 找出使 newMoonBJDay(k) <= day < newMoonBJDay(k+1) 的 k。 */
function newMoonIndexOnOrBefore( day ) {
	let k = Math.round( ( day - 2451550.09766 ) / 29.530588861 );
	while ( newMoonBJDay( k ) > day ) {
		k--;
	}
	while ( newMoonBJDay( k + 1 ) <= day ) {
		k++;
	}
	return k;
}

/* ============================================================
   农历排月:冬至定十一月 + 无中气置闰
   ============================================================ */

/** [startDay, endDay) 内是否含中气(黄经 30 的倍数)。 */
function hasMajorTerm( startDay, endDay ) {
	// 该月首日 0 时(北京)对应的力学时,用于取当时太阳黄经
	const jdApprox = startDay - 0.5 - 8 / 24;
	const lon = sunApparentLon( jdApprox );
	const target = mod360( Math.floor( lon / 30 ) * 30 + 30 );
	const jde = solveSunLon( jdApprox, target );
	const day = ttToBJDay( jde );
	return day >= startDay && day < endDay;
}

const yearCache = new Map();

/**
 * 排出「十一月(year−1) → 十月(year)」这一整轮农历月。
 * 返回 { months: [ { start, num, leap, lunarYear } ], nextEleventh }
 */
function lunarMonths( year ) {
	if ( yearCache.has( year ) ) {
		return yearCache.get( year );
	}

	const ws0 = winterSolsticeBJDay( year - 1 );
	const ws1 = winterSolsticeBJDay( year );
	const k0 = newMoonIndexOnOrBefore( ws0 ); // 含冬至的朔望月 = 十一月
	const k1 = newMoonIndexOnOrBefore( ws1 );
	const n = k1 - k0; // 12(平) 或 13(闰)

	let leapIndex = -1;
	if ( n === 13 ) {
		for ( let i = 1; i <= 12; i++ ) {
			if ( ! hasMajorTerm( newMoonBJDay( k0 + i ), newMoonBJDay( k0 + i + 1 ) ) ) {
				leapIndex = i;
				break;
			}
		}
	}

	const months = [];
	let num = 11;
	let prevNum = 11;
	let lunarYear = year - 1;
	for ( let i = 0; i < n; i++ ) {
		const isLeap = i === leapIndex;
		if ( ! isLeap && num === 1 ) {
			lunarYear = year;
		}
		months.push( {
			start: newMoonBJDay( k0 + i ),
			num: isLeap ? prevNum : num,
			leap: isLeap,
			lunarYear,
		} );
		if ( ! isLeap ) {
			prevNum = num;
			num = ( num % 12 ) + 1;
		}
	}

	const result = { months, nextEleventh: newMoonBJDay( k1 ) };
	yearCache.set( year, result );
	return result;
}

/* ============================================================
   对外:公历 → 农历
   ============================================================ */

const CN_NUM = [ '', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十' ];
const CN_MONTH = [ '', '正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '冬', '腊' ];
const GAN = [ '甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸' ];
const ZHI = [ '子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥' ];
const ZODIAC = [ '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪' ];

function lunarDayCN( d ) {
	if ( d === 10 ) return '初十';
	if ( d === 20 ) return '二十';
	if ( d === 30 ) return '三十';
	if ( d < 10 ) return '初' + CN_NUM[ d ];
	if ( d < 20 ) return '十' + CN_NUM[ d - 10 ];
	return '廿' + CN_NUM[ d - 20 ];
}

function solarToLunar( y, m, d ) {
	const day = bjDayFromGreg( y, m, d );
	let info = lunarMonths( y );
	if ( day < info.months[ 0 ].start ) {
		info = lunarMonths( y - 1 );
	} else if ( day >= info.nextEleventh ) {
		info = lunarMonths( y + 1 );
	}

	let idx = 0;
	for ( let i = info.months.length - 1; i >= 0; i-- ) {
		if ( day >= info.months[ i ].start ) {
			idx = i;
			break;
		}
	}
	const mo = info.months[ idx ];
	const dayNum = day - mo.start + 1;
	const ganzhiIdx = mo.lunarYear - 4;

	return {
		year: mo.lunarYear,
		month: mo.num,
		day: dayNum,
		leap: mo.leap,
		text: ( mo.leap ? '闰' : '' ) + CN_MONTH[ mo.num ] + '月' + lunarDayCN( dayNum ),
		ganzhi: GAN[ ( ( ganzhiIdx % 10 ) + 10 ) % 10 ] + ZHI[ ( ( ganzhiIdx % 12 ) + 12 ) % 12 ],
		zodiac: ZODIAC[ ( ( ganzhiIdx % 12 ) + 12 ) % 12 ],
	};
}

/** 某公历年的农历正月初一(春节)。 */
function springFestival( year ) {
	const info = lunarMonths( year );
	const mo = info.months.find( ( x ) => x.num === 1 && ! x.leap && x.lunarYear === year );
	return gregFromBJDay( mo.start );
}

/**
 * 某公历年的闰月(无则 0)。
 * 闰十一月 / 闰十二月(如 2033 年)落在下一轮「冬至→冬至」窗口,故需查两轮。
 */
function leapMonthOf( year ) {
	for ( const w of [ year, year + 1 ] ) {
		const mo = lunarMonths( w ).months.find( ( x ) => x.leap && x.lunarYear === year );
		if ( mo ) {
			return mo.num;
		}
	}
	return 0;
}

/* —— 24 节气 —— */

const TERM_NAMES = [
	'春分', '清明', '谷雨', '立夏', '小满', '芒种',
	'夏至', '小暑', '大暑', '立秋', '处暑', '白露',
	'秋分', '寒露', '霜降', '立冬', '小雪', '大雪',
	'冬至', '小寒', '大寒', '立春', '雨水', '惊蛰',
];

/** 返回 year 年全部 24 节气 { name, y, m, d }(按公历年归集)。 */
function termsOfYear( year ) {
	const out = [];
	for ( let i = 0; i < 24; i++ ) {
		// 每个目标黄经在 year−1 与 year 两个太阳年各解一次,取落在 year 内的
		for ( const base of [ year - 1, year ] ) {
			const jde = termJDE( base, i * 15 );
			const g = gregFromBJDay( ttToBJDay( jde ) );
			if ( g.y === year ) {
				out.push( { name: TERM_NAMES[ i ], ...g, at: ttToBJString( jde ) } );
			}
		}
	}
	out.sort( ( a, b ) => a.m - b.m || a.d - b.d );
	return out;
}

function termOn( y, m, d ) {
	return termsOfYear( y ).find( ( t ) => t.m === m && t.d === d ) || null;
}

/* ============================================================
   回归用例
   ============================================================ */

let pass = 0;
let fail = 0;

function check( label, actual, expected ) {
	const ok = actual === expected;
	if ( ok ) {
		pass++;
		console.log( `  ok   ${ label.padEnd( 30 ) } ${ actual }` );
	} else {
		fail++;
		console.log( `  FAIL ${ label.padEnd( 30 ) } got ${ actual }  want ${ expected }` );
	}
}

const fmt = ( g ) => `${ g.y }-${ String( g.m ).padStart( 2, '0' ) }-${ String( g.d ).padStart( 2, '0' ) }`;

console.log( '\n[1] 春节(农历正月初一)' );
const SPRING = {
	2018: '2018-02-16', 2019: '2019-02-05', 2020: '2020-01-25', 2021: '2021-02-12',
	2022: '2022-02-01', 2023: '2023-01-22', 2024: '2024-02-10', 2025: '2025-01-29',
	2026: '2026-02-17', 2027: '2027-02-06', 2028: '2028-01-26', 2029: '2029-02-13',
	2030: '2030-02-03', 2031: '2031-01-23', 2032: '2032-02-11', 2033: '2033-01-31',
};
for ( const [ y, want ] of Object.entries( SPRING ) ) {
	check( `${ y } 春节`, fmt( springFestival( Number( y ) ) ), want );
}

console.log( '\n[2] 闰月(0 = 平年)' );
const LEAP = {
	2017: 6, 2018: 0, 2019: 0, 2020: 4, 2021: 0, 2022: 0, 2023: 2, 2024: 0,
	2025: 6, 2026: 0, 2027: 0, 2028: 5, 2029: 0, 2030: 0, 2031: 3, 2032: 0, 2033: 11,
};
for ( const [ y, want ] of Object.entries( LEAP ) ) {
	check( `${ y } 闰月`, leapMonthOf( Number( y ) ), want );
}

console.log( '\n[3] 节气日期' );
const TERMS = [
	[ 2024, '立春', '2024-02-04' ], [ 2024, '春分', '2024-03-20' ],
	[ 2024, '清明', '2024-04-04' ], [ 2024, '夏至', '2024-06-21' ],
	[ 2024, '秋分', '2024-09-22' ], [ 2024, '冬至', '2024-12-21' ],
	[ 2025, '立春', '2025-02-03' ], [ 2025, '清明', '2025-04-04' ],
	[ 2025, '立秋', '2025-08-07' ], [ 2025, '冬至', '2025-12-21' ],
	[ 2026, '立春', '2026-02-04' ], [ 2026, '清明', '2026-04-05' ],
	[ 2026, '立秋', '2026-08-07' ], [ 2026, '秋分', '2026-09-23' ],
	// 冬至北京时间逐年漂移 +5h49m(闰年 −24h):2025 是 12-21 23:03,故 2026 落到 12-22
	[ 2026, '冬至', '2026-12-22' ], [ 2000, '春分', '2000-03-20' ],
];
for ( const [ y, name, want ] of TERMS ) {
	const t = termsOfYear( y ).find( ( x ) => x.name === name );
	check( `${ y } ${ name }`, t ? fmt( t ) : 'none', want );
}

console.log( '\n[4] 农历日期换算' );
// 2026 六月初一 = 7/14,7/14→7/31 为十八,8/5 即廿三
check( '2026-08-05 农历', solarToLunar( 2026, 8, 5 ).text, '六月廿三' );
check( '2026-02-17 农历', solarToLunar( 2026, 2, 17 ).text, '正月初一' );
check( '2026-09-25 中秋', solarToLunar( 2026, 9, 25 ).text, '八月十五' );
check( '2025-10-06 农历', solarToLunar( 2025, 10, 6 ).text, '八月十五' );
check( '2024-06-10 农历', solarToLunar( 2024, 6, 10 ).text, '五月初五' );
check( '2025-08-01 农历', solarToLunar( 2025, 8, 1 ).text, '闰六月初八' );
// 2033 闰十一月:著名边界,闰月落在「冬至→冬至」的下一轮窗口。十一月按传统写作「冬月」
check( '2033-12-25 农历', solarToLunar( 2033, 12, 25 ).text, '闰冬月初四' );
check( '2026 生肖', solarToLunar( 2026, 8, 5 ).zodiac, '马' );
check( '2026 干支', solarToLunar( 2026, 8, 5 ).ganzhi, '丙午' );

console.log( '\n[5] 今日(2026-08-05)' );
const today = solarToLunar( 2026, 8, 5 );
console.log( `  农历 ${ today.ganzhi }${ today.zodiac }年 ${ today.text }` );
console.log( '  本年节气:' );
for ( const t of termsOfYear( 2026 ) ) {
	console.log( `    ${ t.name }  ${ fmt( t ) }  ${ t.at }` );
}

console.log( `\n结果:${ pass } 通过 / ${ fail } 失败\n` );
process.exit( fail === 0 ? 0 : 1 );
