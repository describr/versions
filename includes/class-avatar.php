<?php
namespace describr;

/**
 * Avatar class
 *
 * @package Describr
 * @since 3.0
 */
class Avatar {
    /**
     * Content suitability rating.
     * 
     * @since 3.0
     * @var array
     */
    public $ratings = array( 'G','PG', 'R', 'X' );

    /**
     * Attachment ID.
     * 
     * @since 3.0
     * @var int
     */
    public $ID = 0;
        
    /**
     * Whether the current user has
     * the required capability.
     * 
     * @since 3.0
     * @var bool
     */
    public $user_can = false;
        
    /**
     * Extra arguments to retrieve the avatar.
     * 
     * @see `get_avatar()`.
     * 
     * @since 3.0
     * @var array
     */
    public $args = array();
        
    /**
     * Name of the field used to upload photo.
     * 
     * @since 3.0
     * @var string
     */
    public $field;

    /**
     * Avatar constructor.
     * 
     * @since 3.0
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ), 10 );            
        add_filter( 'pre_get_avatar_data', array( $this, 'pre_get_avatar_data' ), 10, 2 );
    }
        
    /**
     * Adds filters to hooks.
     * 
     * @since 3.0
     */
    public function init() {
        $this->field = describr_photo_key();
        
        if ( ! is_user_logged_in() ) {
            return;
        }
            
        $this->user_can = current_user_can( 'edit_users' );
            
        if ( ! $this->user_can ) {
            $obj = get_post_type_object( 'attachment' );
            
            $this->user_can = $obj && current_user_can( $obj->cap->edit_others_posts );
        }

        if ( wp_doing_ajax() ) {
            add_filter( 'ajax_query_attachments_args', array( $this, 'get_attachments_by_author' ), 10, 1 );
        }
            
        add_filter( 'wp_prepare_attachment_for_js', array( $this, 'remove_edit_link' ), 10, 1 );
    }

    /**
     * Filters attachment query by author if the current user has low permission.
     *
     * @since 3.0
     *
     * @see WP_Query::parse_query().
     *
     * @param array $query An array of query variables.
     * @return array An array of query variables.
     */
    public function get_attachments_by_author( $query ) {
        if ( ! $this->user_can ) {
            $query['author'] = get_current_user_id();
        }

        return $query;
    }
        
    /**
     * Removes the URL to the edit page for the attachment for frontend users having
     * basic capabilities. 
     *
     * @since 3.0
     *
     * @param array $response Array of prepared attachment data. See wp_prepare_attachment_for_js().
     * @return array Array of prepared attachment data.
     */
    public function remove_edit_link( $response ) {
        if ( describr()->is_admin() || $this->user_can ) {
            return $response;
        }            
            
        $page_id = url_to_postid( wp_get_raw_referer() );
        
        //Only alter the URL if the request is from the profile on the front end.
        $remove_edit_link = $page_id && $page_id === describr_get_page_id( 'profile' );

        /**
         * Filters whether to remove the link to edit the attachment
         * for users on the profile on the front end.
         * 
         * @since 3.0
         * 
         * @param bool $remove_edit_link Whether to remove the 
         *                               link to edit the attachment.
         */
        $remove_edit_link = apply_filters( 'describr_remove_edit_attachment_link_frontend', $remove_edit_link );
            
        if ( $remove_edit_link ) {
            $response['editLink'] = '';
        }
            
        return $response;
    }

    /**
     * Filters avatar data early to add avatar url if needed. This filter hooks
     * before Gravatar setup to prevent wasted requests.
     * 
     * @since 3.0
     *
     * @param array $args        Arguments passed to get_avatar_data function, after processing.
     * @param mixed $id_or_email The Gravatar to retrieve. Accepts a user ID, Gravatar MD5 hash,
     *                           user email, WP_User object, WP_Post object, or WP_Comment object.
     * @return array The arguments passed in $args, along with keys found_avatar (true if an avatar was found) 
     *               and url (the URL of the avatar or empty string).
     */
    public function pre_get_avatar_data( $args, $id_or_email ) {
        if ( ! empty( $args['force_default'] ) ) {
            return $args;
        }            

        if ( get_option( 'describr_avatar' ) ) {
            $this->args = $args;

            $this->args['size'] = (int) $this->args['size'];
            
            //Process the user identifier
            if ( is_numeric( $id_or_email ) ) {
                $user = get_userdata( $id_or_email );
            } elseif ( is_string( $id_or_email ) ) {
                $user = get_user_by( 'email', $id_or_email );
            } elseif ( $id_or_email instanceof \WP_User ) {
                $user = $id_or_email;
            } elseif ( $id_or_email instanceof \WP_Post ) {
                $user = get_userdata( (int) $id_or_email->post_author );
            } elseif ( $id_or_email instanceof \WP_Comment ) {
                if ( ! is_avatar_comment_type( get_comment_type( $id_or_email ) ) ) {
                    $args['url'] = false;
                    return $args;
                }
                
                if ( ! empty( $id_or_email->user_id ) ) {
                    $user = get_userdata( $id_or_email->user_id );
                }

                if ( empty( $user ) && ! empty( $id_or_email->comment_author_email ) ) {
                    $user = get_user_by( 'email', $id_or_email->comment_author_email );
                }
            }
                            
            if ( empty( $user->ID ) ) {
                return $args;
            }
            
            $this->get_avatar_url( $user->ID );

            if ( $this->args['url'] ) {
                $alt = $this->args['alt'];

                if ( ! strlen( $alt ) ) {
                    $alt = get_post_meta( $this->ID, '_wp_attachment_image_alt', true );

                    if ( false === $alt ) {
                        $alt = '';
                    }                     

                    if ( ! strlen( $alt ) ) {
                        $alt = sprintf( 
                            //translators: %s: User's display name.
                            __( '%s Picture', 'describr' ), 
                            describr_display_name( $user->ID )
                        );
                    }
                }

                $this->args['alt'] = $alt;
            } else {
                $this->args['url'] = $this->get_default_avatar_url();
            }
                
            if ( ! empty( $this->args['url'] ) ) {
                $this->args['found_avatar'] = true;
            }

            $args = $this->args;
        }
                    
        return $args;
    }
        
    /**
     * Retrieves the URL of the avatar stored by this plugin.
     * 
     * @since 3.0
     * 
     * @param int $user_id User ID.
     * @return string The avatar's URL, otherwise empty string.
     */
    public function get_avatar_url( $user_id ) {
        if ( ! isset( $this->args['url'] ) ) {
            $this->args['url'] = '';
        }

        if ( ! $user_id ) {
            return;
        }

        if ( ! describr_is_field_viewable( $this->field, $user_id ) ) {
            return;
        }
        
        $avatars = get_user_meta( $user_id, $this->field, true );
        
        if ( empty( $avatars['attachment_id'] ) ) {
            return;
        }
        
        $this->ID = (int) $avatars['attachment_id'];

        if ( ! empty( $this->args['rating'] ) ) {
            $user_rating = $this->get_rating( $user_id );

            if ( $user_rating ) {
                $ratings = array_flip( $this->ratings );

                $user_rating       = strtoupper( trim( $user_rating ) );
                $user_rating_order = isset( $ratings[ $user_rating ] ) ? $ratings[ $user_rating ] : 0;

                $admin_rating       = strtoupper( trim( (string) $this->args['rating'] ) );
                $admin_rating_order = isset( $ratings[ $admin_rating ] ) ? $ratings[ $admin_rating ] : 0;               

                //Don't display the avatar if the user's rating is greater than that set by the admin.
                if ( $admin_rating_order < $user_rating_order ) {
                    return;
                }
            }
        }
        
        if ( isset( $avatars['blog_id'] ) ) {
            switch_to_blog( (int) $avatars['blog_id'] );
            $switched_to_blog = true;
        }

        $uploads = wp_get_upload_dir();
                
        $size = $this->args['size'];

        if ( isset( $avatars[ $size ] ) ) {
            if ( describr()->upload_photo()->get_real_path( $uploads['basedir'] . DIRECTORY_SEPARATOR .  $avatars[ $size ] ) ) {
                $this->args['url'] = describr()->upload_photo()->get_real_url( $avatars[ $size ] );
            } else {
                $this->generate_avatar( $avatars, $user_id );
            }
        } else {
            $this->generate_avatar( $avatars, $user_id );
        }

        if ( isset( $switched_to_blog ) ) {
            restore_current_blog();
        }
    }
    
    /**
     * Generates avatar.
     * 
     * @since 3.0
     *
     * @param string $path    File path.
     * @param array  $avatars User meta.
     */
    public function generate_avatar( $avatars, $user_id ) {
        $full_dir_path = get_attached_file( $this->ID );
            
        if ( ! file_exists( $full_dir_path ) ) {
            $uploads = wp_get_upload_dir();

            $full_dir_path = describr()->upload_photo()->get_real_path( $uploads['basedir'] . DIRECTORY_SEPARATOR . $avatars['full'] );

            if ( ! $full_dir_path ) {
                describr()->upload_photo()->delete_picture( $user_id );
                return;
            }
        }

        $this->generate_avatar_( $full_dir_path, $avatars );
        
        $size = $this->args['size'];

        //Was the avatar, of a new size, created?            
        if ( isset( $avatars[ $size ] ) ) {
            $this->args['url'] = set_url_scheme( $avatars[ $size ] );

            $avatars[ $size ] = describr()->upload_photo()->remove_baseurl( $avatars[ $size ] );

            update_user_meta( $user_id, $this->field, $avatars );
        }
    }
    
    /**
     * Helper function for generate_avatar. Generates avatar.
     * 
     * @since 3.0
     *
     * @param string $path    File path.
     * @param array  $avatars User meta.
     */
    public function generate_avatar_( $path, &$avatars ) {        
        $editor = wp_get_image_editor( $path );

        if ( is_wp_error( $editor ) ) {
            return;
        }
        
        $size = $this->args['size'];

        if ( is_wp_error( $editor->resize( $size, $size ) ) ) {
            return;
        }
        
        $dest_file = $editor->generate_filename();
        
        if ( is_wp_error( $editor->save( $dest_file ) ) ) {
            return;
        }
        
        $uploads = wp_get_upload_dir();

        $avatars[ $size ] = str_replace( $uploads['basedir'], $uploads['baseurl'], $dest_file );
    }

    /**
     * Creates default avatar URL.
     * 
     * @since 3.0
     *
     * @return string Avatar default URL.
     */
    public function get_default_avatar_url() {
        $default = ! empty( $this->args['default'] ) ? $this->args['default'] : get_option( 'avatar_default', 'mystery' );
            
        $this->args['alt'] = $default;

        switch ( $default ) {
            case 'mm':
            case 'mystery':
            case 'mysteryman':
                $default = 'mm';
                break;
            case 'gravatar_default':
                $default = false;
                break;
        }
            
        if ( ! empty( $this->args['rating'] ) ) {
            $rating = $this->args['rating'];
        } else {
            $rating = get_option( 'avatar_rating', '' );
        }

        $url_args = array(
            'd' => $default,
            's' => $this->args['size'],
            'r' => strtolower( $rating ),
        );

        $url = add_query_arg(
            rawurlencode_deep( array_filter( $url_args ) ),
            'https://secure.gravatar.com/avatar'
        );

        return $url;
    }
        
    /**
     * Retrieves a user's avatar's ID.
     * 
     * @since 3.0
     * @since 3.0.1 Checks whether the post for the avatar's ID exists.
     * 
     * @param int $user_id User ID.
     * @return int The avatar's ID, otherwise 0.
     */
    public function get_id( $user_id ) {
        if ( ! isset( $this->field ) ) {
            $this->field = describr_photo_key();
        }
        
        $avatars = get_user_meta( $user_id, $this->field, true );

        if ( empty( $avatars['attachment_id'] ) ) {
            return 0;
        }
        
        $id = (int) $avatars['attachment_id'];

        if ( ! get_post( $id ) ) {
            return 0;
        }

        return $id;
    }
    
    /**
     * Retrieves an avatar's rating.
     * 
     * @since 3.0.1
     * 
     * @param int $user_id User ID.
     * @return string|false An avatar's rating, otherwise false.
     */
    public function get_rating( $user_id ) {
        $id = $this->get_id( $user_id );

        if ( ! $id ) {
            return false;
        }
        
        $avatars = get_user_meta( $user_id, $this->field, true );

        if ( isset( $avatars['blog_id'] ) ) {
            switch_to_blog( (int) $avatars['blog_id'] );
        }
        
        $rating = get_post_meta( $id, '_describr_attachment_image_rating', true );

        if ( isset( $avatars['blog_id'] ) ) {
            restore_current_blog();
        }

        return $rating ? $rating : false;
    }  
}