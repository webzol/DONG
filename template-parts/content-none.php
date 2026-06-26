<?php
/**
 * 空状态(Template Part)
 * 用于无文章 / 无搜索结果 / 空归档。
 *
 * @package OneDong
 */
?>
<section class="content-none">
	<h1 class="page-title">
		<?php
		if ( is_search() ) {
			esc_html_e( '没有找到相关结果', 'onedong' );
		} else {
			esc_html_e( '暂无内容', 'onedong' );
		}
		?>
	</h1>
	<p>
		<?php
		if ( is_search() ) {
			esc_html_e( '没有匹配你搜索的内容,换个关键词试试?', 'onedong' );
		} else {
			esc_html_e( '这里还没有任何内容,稍后再来看看。', 'onedong' );
		}
		?>
	</p>

	<?php
	if ( is_search() || is_404() ) {
		get_search_form();
	}
	?>
</section>
