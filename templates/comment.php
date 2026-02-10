<?php
/**
 * Template to output individual comment on the Profile Page
 * 
 * This template can be overridden by copying it to {yourtheme}/describr/templates/comment.php
 *
 * @package Describr
 * @since 3.0
 * 
 * @var array $args
 */

//Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @global WP_Comment $comment
 * @global WP_Post    $comment
 */
global $comment, $post;

$post = get_post( $comment->comment_post_ID );

$post_title = isset( $post->post_title ) ? $post->post_title : '';

if ( ! post_type_supports( $post->post_type , 'title' ) ) {
    $post_title = '';
}

$post_status = get_post_status( $post );

$post_status_obj = get_post_status_object( $post_status );

if ( $post_status_obj && $post_status_obj->private )  {
    $private_title_format = apply_filters( 'private_title_format', /*translators: %s: Private post title.*/ __( 'Private: %s', 'describr' ), $post );

    $post_title = sprintf( $private_title_format, $post_title );
}
                    
$replied_to = ! empty( $comment->comment_parent ) ? __( 'replied:', 'describr' ) : __( 'says:', 'describr' );

$reply_text = _x( 'Reply', 'verb', 'describr' );

$has_post_thumbnail = current_theme_supports( 'post-thumbnails', $post->post_type ) && post_type_supports( $post->post_type, 'thumbnail' ) && has_post_thumbnail();

$is_not_trash = 'trash' !== $post_status;
?>
<div <?php comment_class( 'describr-profile-comment' );?> id="comment-<?php comment_ID(); ?>">
	<?php 
	$avatar = get_avatar( $comment, 50 );

	if ( $avatar ) {
		?>
		<div class="describr-profile-comment-picture"><?php echo wp_kses_post( $avatar ); ?></div>
		<?php
	}
	?>
	<div class="describr-profile-comment-main">
		<div class="describr-profile-comment-header">
			<div class="describr-profile-comment-authorcontent">
				<div class="comment-author vcard"><cite><?php echo esc_html( get_the_author_meta( 'display_name', $comment->user_id ) ); ?></cite> <span class="says"><?php echo esc_html( $replied_to ); ?></span></div>
		        <div class="describr-profile-comment-content"><?php echo wp_kses_post( describr_comment_text() ); ?></div>
			</div>
			<?php
            if ( $has_post_thumbnail ) {
                ?>
	            <div class="post-thumbnail at-side">
	    	        <?php
	                if ( $is_not_trash ) {
	                    echo '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark" title="' . esc_attr( $post_title ) . '">';
	                }
                    
                    the_post_thumbnail();

	                if ( $is_not_trash ) {
	                    echo '</a>';
	                } 
	                ?>
	            </div>
		        <?php
            } elseif ( '' !== $post_title ) {
    	        ?>
                <div class="post-title" title="<?php echo esc_attr(  $post_title  ); ?>">
                	<?php
                    if ( $is_not_trash ) {
            	        printf(
            		        '<a href="%1$s" aria-label="%2$s" class="text">%3$s</a>',
            		        esc_url( get_permalink() ),
            		        esc_attr(sprintf(
            			        /*translators: %s: Post title.*/ 
            		            __( 'Commented on %s', 'describr' ),
            		            $post_title
            		        )),
            		        esc_html( $post_title )
                        );
                    } else {
            	        printf(
            		        '<span class="describr-a11y-text">%1$s<span>' .
            		        '<span aria-hidden="true" class="text">%2$s<span>',
            	            esc_html(sprintf(
            		            /*translators: %s: Post title.*/ 
            		            __( 'Commented on %s.', 'describr' ),
            		            $post_title
            		        )),
            		        esc_html( $post_title )
                        );
                    }
                    ?>
                </div>
                <?php
            }
            ?>
		</div>
		<div class="describr-profile-comment-footer">
			<?php
			//Add moderating link
    	    if ( 0 === (int) $comment->comment_approved ) {
    		    $moderation_title = __( 'Awaiting Moderation', 'describr' );

    		    if ( current_user_can( 'moderate_comments' ) ) {
    		        $mod_url = add_query_arg(
    				    array(
    					    'p'              => $comment->comment_post_ID,
                            'comment_status' => 'moderated',
                        ),
                        admin_url( 'edit-comments.php' )
                    );
                    ?>
                    <div class="comment-mod" title="<?php echo esc_attr( $moderation_title ); ?>">
                    	<a href="<?php echo esc_url( $mod_url );?>" aria-label="<?php esc_attr_e( 'Moderate comment (opens in a new tab)', 'describr' ); ?>" target="_blank"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span class="describr-a11y-text"><?php echo esc_html( $moderation_title );?></span></a>
                    </div>
                <?php
    	        } else {
    	        	?>
    		        <div class="comment-mod" title="<?php echo esc_attr( $moderation_title ); ?>">
    		        	<span class="dashicons dashicons-warning" aria-hidden="true"></span>
    				    <span class="describr-a11y-text"><?php /*translators: Hidden accessibility text.*/ esc_html_e( 'Your comment is awaiting moderation.', 'describr' ); ?></span>
    			    </div>
                    <?php
    		    }
            }

            edit_comment_link( _x( 'Edit', 'verb', 'describr' ), '<div class="comment-edit">', '</div>' );

            comment_reply_link( 
    		    array( 
    				'depth'         => 1, 
    				'max_depth'     => 2, 
    				'reply_text'    => $reply_text,
		            'reply_to_text' => $reply_text,
    				'login_text'    => $reply_text,
    				'before'        => '<div class="comment-reply">',
    				'after'         => '</div>',
    			) 
    		);
            
            $date = get_comment_date( describr_locale_date_format( 'date' ) );

    	    if ( $date ) {
    		    ?>
    		    <div class="comment-date"><?php echo esc_html( $date ); ?></div>
    		    <?php
    	    }
    	    ?>
    	</div>
	</div>
</div>
