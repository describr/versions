<?php
/**
 * Template to output individual post on the Profile Page
 * 
 * This template can be overridden by copying it to {yourtheme}/describr/templates/post.php
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
 * @global WP_Post $post
 */
global $post;

$post_title = isset( $post->post_title ) ? $post->post_title : '';

if ( ! post_type_supports( $post->post_type , 'title' ) ) {
	$post_title = '';
}

$post_status = get_post_status( $post );

$is_not_trash = 'trash' !== $post_status;

$classes = 'describr-profile-post author-' . ( get_current_user_id() === (int) $post->post_author ? 'self' : 'other' );

if ( ! function_exists( 'wp_check_post_lock' ) ) {
    $sep = DIRECTORY_SEPARATOR;

    include ABSPATH . "wp-admin{$sep}includes{$sep}post.php";
}

$lock_holder = wp_check_post_lock( $post->ID );

if ( $lock_holder ) {
	$classes .= ' wp-locked';
}

if ( $post->post_parent ) {
	$count    = count( get_post_ancestors( $post->ID ) );
	$classes .= ' level-' . $count;
} else {
	$classes .= ' level-0';
}

$classes = implode( ' ', get_post_class( $classes, $post->ID ) );

if ( post_password_required( $post ) ) {
	$protected_title_format = apply_filters( 'protected_title_format', /*translators: %s: Protected post title.*/ __( 'Protected: %s', 'describr' ), $post );

	$post_title = sprintf( $protected_title_format, $post_title );

    echo '<div id="post-' . esc_attr( $post->ID ) . '" class="' . esc_attr( $classes ) . '"><div class="describr-profile-post-title post-title"><h2>' . esc_html( $post_title ) . '</h2></div>';
} else {
	$post_status_obj = get_post_status_object( $post_status ); 

	if ( $post_status_obj && $post_status_obj->private ) {
		$private_title_format = apply_filters( 'private_title_format', /*translators: %s: Private post title.*/ __( 'Private: %s', 'describr' ), $post );

		$post_title = sprintf( $private_title_format, $post_title );
	}
	?>
	<div id="post-<?php echo esc_attr( $post->ID ); ?>" class="<?php echo esc_attr( $classes );?>">
		<?php
		if ( current_theme_supports( 'post-thumbnails', $post->post_type ) && post_type_supports( $post->post_type, 'thumbnail' ) && has_post_thumbnail() ) {
			?>
			<div class="post-thumbnail">
			    <?php
				if ( $is_not_trash ) {
					echo '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">';
				}

				the_post_thumbnail();

				if ( $is_not_trash ) {
					echo '</a>';
				}
				?>
			</div>
			<?php
		}
		?>
		<div class="describr-profile-post-main-wrap">
			<?php
			if ( '' !== $post_title && post_type_supports( $post->post_type, 'title' ) ) {
				?>
				<div class="post-title">
					<h2>
						<?php
						if ( $is_not_trash ) {
						    echo '<a href="' . get_permalink() . '" rel="bookmark">';
					    }

					    echo esc_html( $post_title );

					    if ( $is_not_trash ) {
						    echo '</a>';
					    }
						?>
					</h2>
				</div>
				<?php
			}
            ?>
			<div class="post-content">
				<?php
			    if ( isset( $post->post_content ) && '' !== $post->post_content ) {
					if ( post_type_supports( $post->post_type, 'excerpt' ) ) {
					    the_excerpt();
				    } else {
				    	the_content();
				    }
				} else {
				    echo '<span aria-hidden="true">&nbsp;</span>';
				}
				?>
			</div>
			<div class="describr-profile-post-footer">
				<?php
				if ( post_type_supports( $post->post_type, 'comments' ) ) {
					?>
					<div title="<?php esc_attr_e( 'Comments', 'describr' ); ?>" class="post-comment"><?php describr_comments_bubble(); ?></div>
					<?php
				}
                        
                if ( $is_not_trash ) {
                       $reply_link = get_post_reply_link( 
						array( 
							'reply_text' => _x( 'Reply', 'verb', 'describr' ),
							'login_text' => _x( 'Reply', 'verb', 'describr' ),
						) 
					);

					if ( $reply_link ) {
						   ?>
						   <div class="post-reply"><?php echo wp_kses_post( $reply_link ); ?></div>
						<?php
					}
                }
                        
                $date = get_post_time( describr_locale_date_format( 'date' ), false, $post, true );

    			if ( $date ) {
    				?>
    				<div class="post-date"><?php echo esc_html( $date ); ?></div>
    				<?php
    			}
				?>
			</div>
	    </div>
	</div>
	<?php
}