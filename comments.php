<?php
/**
 * 评论 / pingback 模板
 *
 * 由 single.php 经 comments_template() 引入。
 * 评论列表走自定义回调 onedong_comment_callback(头像 + 作者 + 日期 + 内容 + 回复);
 * 表单走 comment_form()。
 *
 * @package OneDong
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 密码保护的文章不显示评论
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			$comment_count = get_comments_number();
			printf(
				/* translators: %s: 评论数量 */
				esc_html( _n( '%s 条评论', '%s 条评论', $comment_count, 'onedong' ) ),
				number_format_i18n( $comment_count )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 44,
					'callback'    => 'onedong_comment_callback',
				)
			);
			?>
		</ol>

		<?php
		the_comments_navigation(
			array(
				'prev_text' => __( '← 较旧的评论', 'onedong' ),
				'next_text' => __( '较新的评论 →', 'onedong' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="no-comments"><?php esc_html_e( '评论已关闭。', 'onedong' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'        => __( '发表评论', 'onedong' ),
			'title_reply_to'     => __( '回复 %s', 'onedong' ),
			'cancel_reply_link'  => __( '取消回复', 'onedong' ),
			'label_submit'       => __( '提交评论', 'onedong' ),
			'class_submit'       => 'submit',
			'comment_field'      => '<p class="comment-form-comment"><label for="comment">' . _x( '评论', 'noun', 'onedong' ) . '</label><textarea id="comment" name="comment" cols="45" rows="5" maxlength="65525" required></textarea></p>',
			'comment_notes_before' => '',
		)
	);
	?>
</div>
