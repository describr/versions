<?php
/**
 * Helper functions for dislaying a user's 
 * posts and comments on their profile.
 * 
 * @package Describr
 * @since 3.0
 */

//Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieves a comment text
 * 
 * @since 3.0
 * 
 * @return string The comment text
 */
function describr_comment_text() {
    global $comment;

	$comment = get_comment();

	if ( ! post_password_required( $comment->comment_post_ID ) ) {
        $comment_text = strip_tags( str_replace( array( "\n", "\r" ), ' ', $comment->comment_content ) );
        
        /**
         * Filters whether to trim the comment text
         *
         * @since 3.0
         *
         * @param bool $trim_comment Whether to trim the comment text
         */
        if ( apply_filters( 'describr_trim_comment', true, $comment ) ) {
            if ( isset( $comment->comment_author ) ) {
                $comment_author = $comment->comment_author;
                /*Empty $comment->comment_author so that get_comment_author() returns $user->display_name instead of $comment->comment_author, since we're viewing one author's comments*/
                $comment->comment_author = '';
            }
            
            $more_link_text_label = sprintf( 
                /*translators: %s: Comment author's display name.*/
                __( 'Continue reading %s comment', 'describr' ), 
                get_comment_author() 
            );
            
            //Test if $comment->comment_author was emptied and reset it to its previous value
            if ( isset( $comment_author ) ) {
                $comment->comment_author = $comment_author;
            }

            $more_link = '<a href="' . esc_url( get_comment_link() ) . '" class="more-link">' . sprintf( 
                '<span aria-label="%1$s">%2$s</span>', 
                esc_attr( $more_link_text_label ), 
                esc_html__( '&hellip; Read more', 'describr' ) 
            ) . '</a>';
            
            /**
             * Filters the link displayed after a trimmed comment
             *
             * @since 3.0
             *
             * @param string $more_link The more link
             */
            $more_link = apply_filters( 'describr_comment_excerpt_more', $more_link );

            /**
             * Filters the maximum number of words in a comment excerpt
             *
             * @since 3.0
             *
             * @param int $excerpt_length The maximum number of words. Default 55
             */
            $excerpt_length = (int) apply_filters( 
                'describr_comment_excerpt_length', 
                /* translators: Maximum number of words used in a comment excerpt.*/
                (int) _x( '55', 'excerpt_length', 'describr' ) 
            );

            $comment_text = apply_filters( 'the_excerpt', wp_trim_words( $comment_text, $excerpt_length, $more_link ) );
        }
    } else {
        $comment_text = __( 'Password protected', 'describr' );
    }
    
    /**
	 * Filters the text of a comment to be displayed
	 *
	 * @since 3.0
	 *
	 * @param string          $comment_text Text of the comment
	 * @param WP_Comment|null $comment      The comment object. Null if not found
	 */
    return apply_filters( 'describr_comment_text', $comment_text, $comment );
}

/**
 * Retrieves a formatted post date
 * 
 * {@see 'get_post_time'} filter
 * 
 * @since 3.0
 * 
 * @param string $date   The post's date
 * @param string $format PHP date format
 * @return string The formatted post date
 */
function describr_post_time( $date, $format ) {
    $post = get_post();

    if ( ! empty( $post->post_date ) && '0000-00-00 00:00:00' !== trim( $post->post_date ) ) {
        $time = $post->post_date;
    } elseif ( ! empty( $post->post_date_gmt ) && '0000-00-00 00:00:00' !== trim( $post->post_date_gmt ) ) {
        $time = $post->post_date_gmt;
    }
   
    if ( isset( $time ) ) {
        $timezone = describr_timezone( wp_get_current_user()->timezone );
    
        $datetime = date_create_immutable_from_format( 'Y-m-d H:i:s', $time, $timezone );
        
        return wp_date( $format, $datetime->getTimestamp(), $timezone );
    }
        
    return $date;
}


/**
 * Retrieves a formatted comment date
 * 
 * {@see 'get_comment_date'} filter
 * 
 * @since 3.0
 * 
 * @param string $date   The comment's date
 * @param string $format PHP date format
 * @return string The formatted comment date
 * 
 * @return string|bool The formatted comment date or false
 */
function describr_comment_date( $date, $format ) {
	$comment = get_comment();

	if ( ! empty( $comment->comment_date ) && '0000-00-00 00:00:00' !== trim( $comment->comment_date ) ) {
        $time = $comment->comment_date;
    } elseif ( ! empty( $comment->comment_date_gmt ) && '0000-00-00 00:00:00' !== trim( $comment->comment_date_gmt ) ) {
        $time = $comment->comment_date_gmt;
    }
    
    if ( isset( $time ) ) {
        $timezone = describr_timezone( wp_get_current_user()->timezone );
    
        $datetime = date_create_immutable_from_format( 'Y-m-d H:i:s', $time, $timezone );
        
        return wp_date( $format, $datetime->getTimestamp(), $timezone );
    }

    return $date;
}

/**
 * Retrieves whether the current user has the
 * capability to view a post
 * 
 * @since 3.0
 * 
 * @param bool $comment_post Whether the post is being
 *                           checked for its comments
 * @return bool
 */
function describr_is_post_viewable( $comment_post = false ) {
    global $post;
    
    $pt_obj = get_post_type_object( $post->post_type );

	if ( ! $pt_obj || 'page' === $post->post_type || ( ! $comment_post && 'post' !== $post->post_type ) ) {
        /*This filter is documented in wp-content/plugins/describr/includes/describr-comments-posts-functions.php*/
        return apply_filters( 'describr_is_post_viewable', false, $post, $comment_post );
    }
    
    $edit_cap = $pt_obj->cap->edit_post;
    $read_cap = $pt_obj->cap->read_post;
    
    $post_status = get_post_status( $post );

    $post_status_obj = get_post_status_object( $post_status );
    
    $is_post_viewable = true;

    if ( $post_status_obj && ! $post_status_obj->public ) {
        if ( ! is_user_logged_in() ) {
            //User must be logged in to view unpublished posts.
            /*This filter is documented in wp-content/plugins/describr/includes/describr-comments-posts-functions.php*/
            return apply_filters( 'describr_is_post_viewable', false, $post, $comment_post );
        } else {
            if ( $post_status_obj->protected ) {
                // User must have edit permissions on the draft to preview.
                if ( ! current_user_can( $edit_cap, $post->ID ) ) {
                    $is_post_viewable = false;
                } else {
                    if ( 'future' !== $post_status ) {
                        $post->post_date = current_time( 'mysql' );
                    }
                }
            } elseif ( $post_status_obj->private ) {
                $is_post_viewable = current_user_can( $read_cap, $post->ID );
            } else {
                $is_post_viewable = false;
            }
        }
    } elseif ( ! $post_status_obj ) {
        //Post status is not registered, assume it's not public.
        $is_post_viewable = current_user_can( $edit_cap, $post->ID );
    }

    /**
     * Filters whether a post is viewable
     * 
     * @since 3.0
     * 
     * @param bool    $is_post_viewable Whether the post is viewable
     * @param WP_Post $post             WP_Post object
     * @param bool    $comment_post     Whether the post is being
     *                                  checked for its comments
     */
    return apply_filters( 'describr_is_post_viewable', $is_post_viewable, $post, $comment_post );
}

/**
 * Outputs comment bubble
 * 
 * @since 3.0
 * 
 * Courtesy of \WP_List_Table object `comments_bubble()` method
 */
function describr_comments_bubble() {
    $post_id = $GLOBALS['post']->ID;

    if ( ! function_exists( 'get_pending_comments_num' ) ) {
        $sep = DIRECTORY_SEPARATOR;

        include ABSPATH . "wp-admin{$sep}includes{$sep}comment.php";
    }

    $comment_pending_count = get_pending_comments_num( $post_id );

    if ( is_array( $comment_pending_count ) ) {
        $pending_comments = isset( $comment_pending_count[ $post_id ] ) ? $comment_pending_count[ $post_id ] : 0;
    } else {
        $pending_comments = $comment_pending_count;
    }
    
    $approved_comments = get_comments_number();
            
    $num_parts = describr_number_initial( $approved_comments );
    
    $decimals = 0;
    
    if ( is_float( $num_parts['num'] ) ) {
        $decimals = 1;
    }

    $approved_comments_number = number_format_i18n( $num_parts['num'], $decimals ) . $num_parts['initial'];
            
    $num_parts = describr_number_initial( $pending_comments );
    
    $decimals = 0;

    if ( is_float( $num_parts['num'] ) ) {
        $decimals = 1;
    }
    
    $pending_comments_number  = number_format_i18n( $num_parts['num'], $decimals ) . $num_parts['initial'];

    $approved_only_phrase = sprintf(
        /*translators: %s: Number of comments.*/
        _n( '%s comment', '%s comments', $approved_comments, 'describr' ),
        number_format_i18n( $approved_comments )
    );

    $approved_phrase = sprintf(
        /*translators: %s: Number of comments.*/
        _n( '%s approved comment', '%s approved comments', $approved_comments, 'describr' ),
        number_format_i18n( $approved_comments )
    );

    $pending_phrase = sprintf(
        /*translators: %s: Number of comments.*/
        _n( '%s pending comment', '%s pending comments', $pending_comments, 'describr' ),
        number_format_i18n( $pending_comments )
    );
    
    $comment_count_pending_exist = '';

    if ( $pending_comments ) {
        $can_view_pending_comments = current_user_can( 'moderate_comments' );

        if ( ! $can_view_pending_comments ) {
            $ptobj = get_post_type_object( get_post_type() );

            $can_view_pending_comments = $ptobj && current_user_can( $ptobj->cap->edit_post, $post_id );
        }

        if ( $can_view_pending_comments ) {
            $comment_count_pending_exist = ' comment-count-pending-exist';
        }
    }
    
    if ( ! $approved_comments && ! $pending_comments ) {
        //No comments at all.
        printf(
            '<a href="%s" class="post-com-count post-com-count-no-comments">'.
                '<span class="dashicons dashicons-admin-comments" aria-hidden="true"></span>' .
                '<span class="describr-a11y-text">%s</span>' .
            '</a>',
            esc_url( get_comments_link() ),
            esc_html__( 'No comments', 'describr' )
        );
    } elseif ( $approved_comments && 'trash' === get_post_status( $post_id ) ) {
        // Don't link the comment bubble for a trashed post.
        printf(
            '<span class="post-com-count post-com-count-approved">' .
                '<span class="dashicons dashicons-admin-comments" aria-hidden="true"></span>' .
                ' <span class="comment-count-approved%s" aria-hidden="true">%s</span>' .
                '<span class="describr-a11y-text">%s</span>' .
            '</span>',
            esc_attr( $comment_count_pending_exist ),
            esc_html( $approved_comments_number ),
            esc_html( $pending_comments ? $approved_phrase : $approved_only_phrase )
        );
    } elseif ( $approved_comments ) {
        // Link the comment bubble to approved comments.
        printf(
            '<a href="%s" class="post-com-count post-com-count-approved">' .
                '<span class="dashicons dashicons-admin-comments" aria-hidden="true"></span>' .
                ' <span class="comment-count-approved%s" aria-hidden="true">%s</span>' .
                '<span class="describr-a11y-text">%s</span>' .
            '</a>',
            esc_url( get_comments_link() ),
            esc_attr( $comment_count_pending_exist ),
            esc_html( $approved_comments_number ),
            esc_html( $pending_comments ? $approved_phrase : $approved_only_phrase )
        );
    } else {
        printf(
            '<a href="%s" class="post-com-count post-com-count-no-comments">' .
                '<span class="dashicons dashicons-admin-comments" aria-hidden="true"></span>' .
                '<span class="describr-a11y-text">%s</span>' .
            '</a>',
            esc_url( get_comments_link() ),
            $pending_comments ?
            /* translators: Hidden accessibility text. */
            esc_html__( 'No approved comments', 'describr' ) :
            /* translators: Hidden accessibility text. */
            esc_html__( 'No comments', 'describr' )
        );
    }
    
    if ( ! empty( $can_view_pending_comments ) ) {
        printf(
            '<span class="post-com-count post-com-count-pending" title="%s">' .
                '<span class="comment-count" aria-hidden="true">%s</span>' .
                '<span class="describr-a11y-text">%s</span>' .
            '</span>',
            esc_attr__( 'Pending Comments', 'describr' ),
            esc_html( $pending_comments_number ),
            esc_html( $pending_phrase )
        );
    }
}

/**
 * Filters the "more" link displayed after a trimmed post's
 * content
 * 
 * Added and removed when the post is being displayed
 * 
 * @since 3.0
 *
 * @param string $more_link The link being filtered
 * @return string The passed argument if it contains a link or a customized "more" link
 */
function describr_excerpt_more( $more_link ) {
    if ( preg_match( '/<a[^>]+>.+<\/a>/s', $more_link ) ) {
        return $more_link;
    }
            
    $more_link_text_label = sprintf(
        /*translators: %s: Post title.*/   
        __( 'Continue reading %s.', 'describr' ),
        the_title_attribute( array( 'echo' => false ) )
    );

    $more_link_text = sprintf( 
        '<span aria-label="%1$s">%2$s</span>', 
        esc_attr( $more_link_text_label ), 
        esc_html__( '&hellip; Read more', 'describr' ) 
    );
    
    $more_link = '<a href="' . esc_url( get_permalink() . '#more-' . $GLOBALS['post']->ID ) . '" class="more-link">' . $more_link_text . '</a>';

    return $more_link;
}