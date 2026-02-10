<?php
namespace describr;

/**
 * Upload_Photo class
 *
 * @package Describr
 * @since 3.0
 */
class Upload_Photo {
    /**
     * Attachment ID
     * 
     * @since 3.0
     * @var init
     */
    private $ID = 0;
                
    /**
     * Name of the field used to upload photo
     * 
     * @since 3.0
     * @var string
     */
    public $field;
        
    /**
     * Stores pictures to be deleted
     * after the user is deleted
     * 
     * @since 3.0
     * @var array
     */
    public $delete_pictures;

    /**
     * Upload_Photo constructor
     * 
     * @since 3.0
     */                
    public function __construct() {
        add_action( 'init', array( $this, 'init' ), 10 );
    }
        
    /**
     * Adds picture ajax actions and initializes the field property 
     * 
     * @since 3.0
     */
    public function init() {
        $this->field = describr_photo_key();
        
        if ( wp_doing_ajax() && is_user_logged_in() ) {
            add_action( 'wp_ajax_describr-remove-picture', array( $this, 'init_remove_picture' ) );
            add_action( 'wp_ajax_describr-assign-picture', array( $this, 'init_assign_picture' ) );
            add_action( 'wp_ajax_describr-rate-picture', array( $this, 'rate_picture' ) );
        }

        add_action( 'delete_user', array( $this, 'set_picture_to_delete' ), 10, 1 );

        if ( is_multisite() ) {
            add_action( 'wpmu_delete_user', array( $this, 'set_picture_to_delete' ), 10, 1 );
        }        
            
        add_action( 'deleted_user', array( $this, 'delete_picture__' ), 10, 1 );
    }
    
    /**
     * Retrieves from the database and set the photo
     * of the user to potentially be deleted
     * 
     * @see `wp_delete_user()`
     * 
     * @since 3.0
     * 
     * @param int $user_id ID of the deleted user
     */
    public function set_picture_to_delete( $user_id ) {
        if ( ! is_multisite() || 'wpmu_delete_user' === current_action() ) {
            $this->delete_pictures = get_user_meta( $user_id, $this->field, true );
        }
    }

    /**
     * Deletes deleted user's picture
     * 
     * @see `wp_delete_user()`
     * 
     * @since 3.0
     * 
     * @param int $user_id ID of the deleted user
     */
    public function delete_picture__( $user_id ) {
        if ( isset( $this->delete_pictures ) ) {
            $this->delete_picture( $user_id );
        }
    }

    /**
     * Retrieves whether the current user has the capability to upload profile photo
     * 
     * @since 3.0
     * 
     * @return bool
     */
    public function user_can() {
        /**
         * Filters whether the current user has the capability to upload profile photo
         * 
         * @since 3.0
         * 
         * @param bool $can_upload Whether the user can upload profile photo
         */
        return (bool) apply_filters( 'describr_user_can_upload_profile_photo', describr_is_field_editable( $this->field ) && current_user_can( 'upload_files' ) );
    }
        
    /**
     * Assigns picture to current user
     * 
     * @since 3.0
     * 
     * @return void
     */
    public function init_assign_picture() {
        $user = get_userdata( (int) $_POST['user_id'] );
        
        $this->ID = (int) $_POST['photo_id']; 

        if ( $user ) {
            if ( ! current_user_can( 'edit_user', $user->ID ) ) {
                $user = false;
            } elseif ( ! $this->user_can() ) {
                $user = false;
            } elseif ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_photo_nonce'] ) ), 'describr-assign-from-media-library-picture_' . $user->ID ) ) {
                $user = false;
            }
        }
        
        if ( ! $user ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Could not assign profile picture. Please try again.', 'describr' ),
                )
            );
        }

        if ( wp_attachment_is_image( $this->ID ) ) {
            $this->assign_picture( $user->ID );
        } else {
            wp_send_json_error(
                array(
                    'message' => __( 'The attachment is not an image. Please try again.', 'describr' ),
                )
            );
        }
        
        wp_send_json_success(
            array(
                'img' => get_avatar( $user->ID, describr_photo_size() ),
                'id'  => $this->ID,
            )
        );
    }

    /**
     * Deletes picture by the way of ajax
     * 
     * @since 3.0
     * 
     * @return void
     */
    public function init_remove_picture() {
        if ( ! describr_is_field_editable( $this->field ) ) {
            $user = false;
        } else {
            $user = get_userdata( (int) $_POST['user_id'] );
        }
        
        $this->ID = (int) $_POST['photo_id'];

        if ( $user ) {
            if ( ! current_user_can( 'edit_user', $user->ID ) ) {
                $user = false;
            } elseif ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_photo_nonce'] ) ), 'describr-remove-picture_' . $user->ID ) ) {
                $user = false;
            }
        }
        
        if ( ! $user ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Could not delete profile picture. Please try again.', 'describr' )
                )
            );
        }
         
        $this->delete_picture( $user->ID );

        $data = array(
                'img' => get_avatar( $user->ID, describr_photo_size() )
            );
        
        describr_audit_fields( $user->ID );

        $saved_asides = describr_get_saved_asides( $this->field );

        if ( $saved_asides ) {
            $data['savedAsides'] = $saved_asides;
        }

        wp_send_json_success( $data );
    }

    /**
     * Assign picture to user
     *
     * @since 3.0
     * 
     * @param int $user_id User ID
     */
    public function assign_picture( $user_id ) {
        $user = get_userdata( $user_id );

        //Delete old picture     
        $this->delete_picture( $user->ID );
            
        $attachment = wp_get_attachment_url( $this->ID );
        
        if ( $attachment ) {
            $photos = array();

            $photos['attachment_id'] = $this->ID;
            
            $full = $this->remove_baseurl( $attachment );
            
            $photos['full'] = $full;
            
            //Taken from `_wp_upload_dir()`
            if ( is_multisite() && ! ( is_main_network() && is_main_site() && defined( 'MULTISITE' ) ) ) {
                $photos['blog_id'] = get_current_blog_id();
            }

            //Start adding the default image sizes
            $image_meta = get_post_meta( $this->ID, '_wp_attachment_metadata', true );
                        
            if ( isset( $image_meta['sizes'] ) ) {
                $subdir = preg_replace( '/(' . preg_quote( wp_basename( $full ), '/' ) . ')$/u', '', $full );

                foreach ( $image_meta['sizes'] as $size_name => $size_meta ) {
                    if ( isset( $size_meta['file'] ) ) {
                        $file = ltrim( $size_meta['file'], $this->dir_seps() );
                        
                        $photos[ $size_meta['width'] ] = $subdir . $file;
                    }
                }
            }
                
            if ( update_user_meta( $user->ID, $this->field, $photos ) ) {
                describr_auditing_fields( $this->field );
            }
        }

        describr_audit_fields( $user->ID );
    }
        
    /**
     * Deletes picture
     *
     * @since 3.0
     * 
     * @param int $user_id User ID
     * @param int $blog_id Blog ID
     */
    public function delete_picture( $user_id ) {
        if ( ! isset( $this->field ) ) {
            $this->field = describr_photo_key();
        }
        
        $is_deleted_user = isset( $this->delete_pictures );
        
        $is_required = describr_is_field_required( $this->field );
        
        //Don't delete if
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) //The plugin is not being uninstalled
            && ! $is_deleted_user //The user is not being deleted
            && $is_required //The field is required
        ) {
            return;
        }
            
        if ( $is_deleted_user ) {
            $photos = (array) $this->delete_pictures;

            unset($this->delete_pictures);
        } elseif ( ! describr_has_photo( $user_id ) ) {
            return;
        }
            
        if ( ! isset( $photos ) ) {
            $photos = get_user_meta( $user_id, $this->field, true );
        }
            
        if ( ! isset( $photos['attachment_id'] ) ) {
            return;
        }
        
        if ( $this->ID !== (int) $photos['attachment_id'] && wp_doing_ajax() && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Could not delete profile picture. Please try again.', 'describr' )
                )
            );
        }

        if ( isset( $photos['blog_id'] ) ) {
            switch_to_blog( $photos['blog_id'] );
            $switched_to_blog = true;
        }

        wp_delete_attachment( (int) $photos['attachment_id'], true );
                
        unset( $photos['attachment_id'], $photos['blog_id'] );
                
        $uploads = wp_get_upload_dir();

        $basedir = $uploads['basedir'] . DIRECTORY_SEPARATOR;

        foreach ( $photos as $size => $rel_path ) {
            wp_delete_file( $this->get_real_path( $basedir . $rel_path ) );
        }
        
        if ( isset( $switched_to_blog ) ) {
            restore_current_blog();
        }
         
        //The usermeta is already deleted if we're deleting a deleted user's photo  
        if ( $is_deleted_user ) {
            return;
        }

        if ( delete_user_meta( $user_id, $this->field ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            $asides = describr_get_aside( $this->field );
            
            if ( $asides ) {
                $meta_key = isset( $asides['_cat'] ) ? $asides['_cat'] : $this->field;

                foreach ( $asides as $aside => $settings ) {
                    if ( isset( $settings['name'] ) ) {
                        delete_user_meta( $user_id, "{$meta_key}_{$settings['name']}" );
                    }
                }
            }

            describr_auditing_fields( $this->field );
        }

        
    }
    
    /**
     * Rates picture picture.
     *
     * @since 3.0.1
     */
    public function rate_picture() {
        if ( strlen( $_POST[ describr()->honeypot ] ) ) {
            wp_send_json_error(
                array(
                    'message' => _x( 'It seems as if this request is not made by a human being.', 'error message', 'describr' )
                )
            );
        }

        if ( ! describr_is_field_editable( $this->field ) ) {
            $user = false;
        } else {
            $user = get_userdata( (int) $_POST['user_id'] );
        }
        
        $rating = trim( $_POST['rating'] );

        $this->ID = (int) $_POST['photo_id'];

        if ( ! ( 0 < $this->ID && in_array( $rating, describr()->avatar()->ratings, true ) ) ) {
            $user = false;
        }

        if ( $user ) {
            if ( ! current_user_can( 'edit_user', $user->ID ) ) {
                $user = false;
            } elseif ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_photo_nonce'] ) ), 'describr-rate-picture_' . $user->ID ) ) {
                $user = false;
            }
        }
        
        if ( ! $user ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Could not rate profile picture. Please try again.', 'describr' )
                )
            );
        }
         
        if ( describr_photo_id( $user->ID ) !== $this->ID ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Could not rate profile picture. Please try again.', 'describr' )
                )
            );
        }
        
        $photos = get_user_meta( $user->ID, $this->field, true );

        if ( isset( $photos['blog_id'] ) ) {
            switched_to_blog( (int) $photos['blog_id'] );
        }
        
        if ( update_post_meta( $this->ID, '_describr_attachment_image_rating', $rating ) ) {
            describr_auditing_fields( $this->field );
        }
        
        if ( isset( $photos['blog_id'] ) ) {
            restore_current_blog();
        }
        
        $data = array(
                'img'    => get_avatar( $user->ID, describr_photo_size() ),
                'rating' => $rating,
            );
        
        describr_audit_fields( $user->ID );

        $saved_asides = describr_get_saved_asides( $this->field );

        if ( $saved_asides ) {
            $data['savedAsides'] = $saved_asides;
        }
        
        wp_send_json_success( $data );
    } 

    /**
     * Retrieves relative path from absolute upload path
     * 
     * @see `wp_upload_dir()`
     * 
     * @since 3.0
     * 
     * @param string $abs_path Absolute path
     * @return string Relative file path
     */
    public function remove_baseurl( $abs_path ) {
        $uploads = wp_get_upload_dir();

        $rel_path = preg_replace( '/^(' . preg_quote( $uploads['baseurl'], '/' ) . '|' . preg_quote( $uploads['basedir'], '/' ) . ')/u', '', $abs_path );

        $rel_path = ltrim( $rel_path, $this->dir_seps() );

        return $rel_path;
    }
    
    /**
     * Retrieves directory separators
     * 
     * @since 3.0
     * 
     * @return string Directory separators
     */
    public function dir_seps() {
        $dir_seps = '/\\';

        if ( ! str_contains( $dir_seps, DIRECTORY_SEPARATOR ) ) {
            $dir_seps .= DIRECTORY_SEPARATOR;
        }

        return $dir_seps;
    }
        
    /**
     * Creates a unique basename for manually uploaded pictures
     *
     * @since 3.0
     * 
     * @param  string $dir  File path
     * @param  string $name File name
     * @param  string $ext  File extension (e.g., .jpg)
     * @return string Unique filename
     */
    public function unique_filename_callback( $dir, $name, $ext ) {
        $user = wp_get_current_user();            
        
        if ( $user ) {
            $user_name = $user->user_login;
        } else {
            $user_name = time();
        }
            
        $created_filename = $user_name . '_photo';
        $hashed_filename  = wp_hash( $created_filename );
        $unique_filename  = sanitize_file_name( $hashed_filename );
        $base_filename    = $unique_filename;
            
        $number = 1;

        //Ensure no conflict with existing file names
        while ( file_exists( $dir . "/$unique_filename$ext" ) ) {
            $unique_filename = $base_filename . '_' . $number;

            $number++;
        }

        return $unique_filename . $ext;
    }
        
    /**
     * Return a file's absolute directory path, taking multisite into consideration
     *
     * @since 3.0
     * 
     * @param string $path       File's absolute directory path
     * @param bool   $return_url Whether to return the URL related
     *                           to the file directory path
     * @return string The absolute directory path if the file exists, 
     *                full URL if argument $return_url is true, 
     *                or empty string
     */
    public function get_real_path( $path, $return_url = false ) {
        if ( file_exists( $path ) ) {
            if ( $return_url ) {
                $uploads = wp_get_upload_dir();

                $url = str_replace( $uploads['basedir'], $uploads['baseurl'], $path );

                return $url;
            }

            return $path;
        }
            
        if ( is_multisite() ) {
            foreach ( $this->ms_dir() as $ms_dir_and_sep ) {
                $path_ = str_replace( $ms_dir_and_sep[0], $ms_dir_and_sep[1], $path );
                
                if ( file_exists( $path_ ) ) {
                    if ( $return_url ) {
                        $uploads = wp_get_upload_dir();

                        $url = str_replace( $uploads['basedir'], $uploads['baseurl'], $path );
                        $url = str_replace( $ms_dir_and_sep[0], $ms_dir_and_sep[1], $url );

                        return $url;
                    }

                    return $path_;
                }
            }
        }

        return '';
    }
    
    /**
     * Retrieves a file's URL
     *
     * @since 3.0
     * 
     * @param string $filename File name potentially prepended with yyyy/mm/
     * @return string URL or empty string
     */
    public function get_real_url( $filename ) {
        $uploads = wp_get_upload_dir();
        
        $url = $this->get_real_path( $uploads['basedir'] . DIRECTORY_SEPARATOR . $filename, true );
        
        if ( $url ) {
            $url = preg_replace( '/(' . preg_quote( $filename, '/' ). ')$/u', '', $url );
            $url = rtrim( $url, $this->dir_seps() );
            $url .= "/$filename";
        }
        
        return $url;
    }
    
    /**
     * Retrieves uploads multisite subdirectory
     *
     * @since 3.0
     * 
     * @return An array whose elements are arrays containing a multisite subdirectory and a directory separator
     */
    public function ms_dir() {
        $sep = DIRECTORY_SEPARATOR;

        $blog_id = get_current_blog_id();

        /**
         * For multisite, uploads are stored in WP_CONTENT_DIR/uploads/sites/$site_id/...,
         * except the main site, whose uploads are stored in
         * WP_CONTENT_DIR/uploads/... Here we go down to the main site's uploads folder
         * to see if the file was initially uploaded there before multisite
         * was activated. 
         * 
         * @see `_wp_upload_dir()` for how these folders are put together
         */
        return array(
            array( "{$sep}sites{$sep}$blog_id{$sep}", $sep ),
            array( "/sites/$blog_id/", '/' ),
            array( "{$sep}$blog_id{$sep}", $sep ),
            array( "/$blog_id/", '/' ),
        );
    }
}
