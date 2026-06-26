<?php
/**
 * 分页(Template Part)
 *
 * @package OneDong
 */

if ( ! function_exists( 'the_posts_pagination' ) ) {
	return;
}

if ( $GLOBALS['wp_query']->max_num_pages <= 1 ) {
	return;
}

the_posts_pagination(
	array(
		'prev_text'          => __( '←', 'onedong' ),
		'next_text'          => __( '→', 'onedong' ),
		'mid_size'           => 1,
		'before_page_number' => '<span class="screen-reader-text">' . __( '第', 'onedong' ) . ' </span>',
		'after_page_number'  => '<span class="screen-reader-text"> ' . __( '页', 'onedong' ) . '</span>',
	)
);
