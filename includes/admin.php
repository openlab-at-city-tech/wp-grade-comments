<?php

/**
 * wp-admin functionality.
 *
 * @since 1.1.0
 */

// Grade column on edit.php.
add_filter( 'manage_post_posts_columns', 'olgc_add_grade_column' );
add_action( 'manage_post_posts_custom_column', 'olgc_add_grade_column_content', 10, 2 );

// Comment editing.
add_action( 'add_meta_boxes_comment', 'olgc_register_meta_boxes' );
add_action( 'edit_comment', 'olgc_save_comment_extras' );

/**
 * Add Grade column to wp-admin Posts list.
 *
 * @since 1.0.0
 *
 * @param array $columns Column info.
 */
function olgc_add_grade_column( $columns ) {
	if ( ! olgc_is_instructor() ) {
		return $columns;
	}

	$columns['grade'] = __( 'Grade', 'wp-grade-comments' );
	return $columns;
}

/**
 * Content of the Grade column.
 *
 * @since 1.0.0
 *
 * @param string $column_name Name of the current column.
 * @param int    $post_id     ID of the post for the current row.
 */
function olgc_add_grade_column_content( $column_name, $post_id ) {
	if ( ! olgc_is_instructor() ) {
		return;
	}

	if ( 'grade' !== $column_name ) {
		return;
	}

	// Find the first available grade on a post comment.
	$comments = get_comments( array(
		'post_id' => $post_id,
	) );

	foreach ( $comments as $comment ) {
		$grade = get_comment_meta( $comment->comment_ID, 'olgc_grade', true );

		if ( $grade ) {
			echo esc_html( $grade );
			break;
		}
	}
}

/**
 * Add the WP Grade Comments meta boxes to the comment edit screen.
 *
 * @since 1.1.0
 *
 * @param WP_Comment $comment Comment object.
 */
function olgc_register_meta_boxes( $comment ) {
	// Grade meta box is visible to instructor or post author.
	$comment_post = get_post( $comment->comment_post_ID );
	if ( ! olgc_is_instructor() && ( empty( $comment_post->post_author ) || $comment_post->post_author !== get_current_user_id() ) ) {
		return;
	}

	wp_enqueue_style( 'olgc-meta-boxes', OLGC_PLUGIN_URL . '/assets/css/meta-boxes.css' );

	add_meta_box(
		'olgc-comment-grade',
		__( 'Grade', 'wp-grade-comments' ),
		'olgc_grade_meta_box',
		'comment',
		'normal'
	);
}

/**
 * Render the Grade meta box.
 *
 * @since 1.1.0
 *
 * @param WP_Comment $comment Comment object.
 */
function olgc_grade_meta_box( $comment ) {
	// Only instructors can edit the grade.
	$disabled = '';
	if ( ! olgc_is_instructor() ) {
		$disabled = 'disabled="disabled"';
	}

	$grade = get_comment_meta( $comment->comment_ID, 'olgc_grade', true );
	var_Dump( $grade );

	?>
	<table class="form-table editcomment">
		<tr>
			<th scope="col">
				<label for="olgc-grade"><?php esc_html_e( 'Grade:', 'wp-grade-comments' ); ?></label>
			</th>

			<td>
				<input id="olgc-grade" name="olgc-grade" value="<?php echo esc_attr( $grade ); ?>" <?php echo $disabled; ?> />
				<?php wp_nonce_field( 'olgc-grade-edit-' . $comment->comment_ID, 'olgc_grade_edit_nonce' ); ?>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save grade settings when saving comment from the admin.
 *
 * @since 1.1.0
 *
 * @param int $comment_id ID of the comment being saved.
 */
function olgc_save_comment_extras( $comment_id ) {
	// Cap check.
	if ( ! olgc_is_instructor() ) {
		return;
	}

	// CSRF check.
	if ( ! isset( $_POST['olgc_grade_edit_nonce'] ) || ! wp_verify_nonce( $_POST['olgc_grade_edit_nonce'], 'olgc-grade-edit-' . $comment_id ) ) {
		return;
	}

	// Sanitize and update.
	if ( isset( $_POST['olgc-grade'] ) ) {
		$grade = wp_unslash( $_POST['olgc-grade'] );
		update_comment_meta( $comment_id, 'olgc_grade', $grade );
	}
}