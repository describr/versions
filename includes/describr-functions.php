<?php
/**
 * Core functions that retrieve, validate, and output data.
 * 
 * @package Describr
 * @since 3.0
 */

//Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieves whether the current user can action
 * regarding the profile picture
 * 
 * @since 3.0
 * @return bool True if the current user can take 
 *              action regarding the profile picture, 
 *              otherwise false
 */
function describr_can_take_profile_picture_actions() {
    static $can_take_action = null;
    
    if ( ! is_null( $can_take_action ) ) {
        return $can_take_action;
    }

    if ( describr_can_edit_profile() ) {        
        if ( describr_can_upload() ) {
            if ( ! did_action( 'wp_enqueue_media' ) ) {
                wp_enqueue_media();
            }

            if ( did_action( 'wp_enqueue_media' ) ) {
                $can_take_action = true;

                return $can_take_action;
            }
        }
        
        if ( describr_is_field_editable( describr_photo_key() ) && ! describr_is_field_required( describr_photo_key() ) && describr_has_photo( describr_profile_id() ) ) {
            $can_take_action = true;

            return $can_take_action;
        }
    }
    
    $can_take_action = false;

    return $can_take_action;
}

/**
 * Retrieves whether a password is strong
 * 
 * @since 3.0
 * 
 * @return strong $pass The password
 * @return bool True if password is strong, otherwise false
 */
function describr_is_strong_pass( $pass ) {
    return describr()->validation()::is_strong_pass( $pass );
}

/**
 * Retrieves whether a user is active by admin
 * 
 * @since 3.0
 * 
 * @return WP_User $user WP_User object of the user to check
 * @return bool
 */
function describr_is_user_active( $user ) {
    if ( ! is_object( $user ) ) {
        $user = get_userdata( $user );
    }
    
    return ! $user || 'false' !== $user->active_by_admin; 
}

/**
 * Retrieves whether an option is among a set of options
 * 
 * @since 3.0
 * 
 * @param mixed $option  The option to check if exists
 * @param array $options An array of options
 * @return bool True if the option exists, otherwise false
 */
function describr_is_option( $option, $options ) {
    if ( wp_is_numeric_array( $options ) ) {
        return in_array( $option, $options, true );
    }

    return isset( $options[ $option ] );
}

/**
 * Retrieves the current URL
 * 
 * @since 3.0
 * @param string The current URL
 */
function describr_current_url() {
    return describr()->permalinks()->current_url;
}

/**
 * Retrieves the ID for the photo of a user.
 * 
 * @since 3.0
 * @since 3.0.1 Adds `@return` statement.
 * 
 * @param int $user_id ID for the user to whom the photo belongs.
 * @return int ID for a user's profile photo.
 */
function describr_photo_id( $user_id ) {
    return describr()->avatar()->get_id( $user_id );
}

/**
 * Retrieves an avatar's rating.
 * 
 * @since 3.0.1
 * 
 * @param int $user_id User ID.
 * @return string|false An avatar's rating, otherwise false.
 */
function describr_avatar_rating( $user_id ) {
    return describr()->avatar()->get_rating( $user_id );
}

/**
 * Checks if database query returns an empty value
 * 
 * @since 3.0
 * 
 * @param mixed $data The value returned from the database
 * @return bool
 */
function describr_is_saved_data_empty( $data ) {
    if ( is_string( $data ) ) {
        $data = trim( $data );
    }

    return ( '' === $data || false === $data || null === $data || ( is_array( $data ) && ! $data ) ); 
}

/**
 * Converts false or null to empty string
 * 
 * @since 3.0
 * 
 * @param mixed $data The data to convert to empty string
 * @return mixed An empty string if the $data is null or false,
 *               otherwise the passed data
 */
function describr_falsey_to_empty_str( $data ) {
    $data_ = $data;

    if ( is_string( $data_ ) ) {
        $data_ = trim( $data_ );
    }
    
    if ( in_array( $data_, array( false, null, '' ), true ) ) {
        return '';
    }

    return $data;
}

/**
 * Retrieves whether nicename is locked
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return True if the expiration date has passed, otherwise false
 */
function describr_is_nicename_locked( $user_id ) {
    /**
     * Filters whether to enforce time must elapse before
     * nicename can change
     * 
     * @since 3.0
     * 
     * @param bool $enforce_expiration Whether to enforce nicename expiration
     * @param int $user_id User ID
     */
    if ( ! apply_filters( 'describr_time_must_elapse_before_nicename_can_change', true, $user_id ) ) {
        return false;
    }

    $updated = get_user_meta( $user_id, 'describr_nicename_updated', true );
    
    if ( $updated ) {
        /**
         * Filters the duration of the nicename expiration period
         *
         * @since 3.0
         *
         * @param int  $length   Duration of the expiration period in seconds
         * @param int  $user_id  ID for the user to whom the nicename belongs
         */
        $expired = (int) $updated + apply_filters( 'describr_nicename_expiration', YEAR_IN_SECONDS, $user_id );

        /**
         * Filters whether the time is elapsed before the nicename can change
         * 
         * @since 3.0
         * 
         * @param bool $is_locked Whether the time is elapsed 
         *                        before the nicename can change
         * @param int  $expired   The expiration timestamp
         * @param int  $user_id   ID for the user to whom the nicename belongs
         */
        return apply_filters( 'describr_is_nicename_lock_expired', ( $expired - time() ) > 0, $expired, $user_id );
    }

    return false;
}

/**
 * Retrieves the notice indicating that the nicename
 * can't be changed before a specific date and time
 * 
 * @since 3.0
 * 
 * @param int $user_id ID for the user to whom the nicename belongs
 * @return string Nicename expiration date notice
 */
function describr_nicename_locked_notice( $user_id ) {
    /*translators: 1: Field label. 2: Date.*/
    $string = __( '%1$s cannot be changed before %2$s.', 'describr' );
    
    
    $expired = (int) get_user_meta( $user_id, 'describr_nicename_updated', true );
    /*This filter is documented in wp-content/plugins/describr/includes/describr-functions.php*/
    $expired += apply_filters( 'describr_nicename_expiration', YEAR_IN_SECONDS, $user_id );

    return sprintf( $string, describr_get_field_( 'user_nicename', 'label' ), describr_date( describr_locale_date_format( 'date@time' ), $expired, wp_get_current_user()->timezone ) );
}

/**
 * Retrieves whether the field is that of social media
 * 
 * @since 3.0
 * 
 * @param string The field
 * @return bool
 */
function describr_is_social( $field ) {
    $socials = describr_get_socials();
    return ! empty( $socials[ $field ] );
}

/**
 * Retrieves whether the user has a profile photo
 * 
 * @since 3.0
 * 
 * @param int $user_id The User ID
 * @return bool True if the user has a profile photo, otherwise false
 */
function describr_has_photo( $user_id ) {
    return ! empty( describr_photo_id( $user_id ) );
}

/**
 * Retrieves whether the current user can edit the user
 * 
 * @since 3.0
 * 
 * @param int $user_id         User ID
 * @param bool $suppress_error Whether to set error code
 * @return bool
 */
function describr_can_edit_user( $user_id, $suppress_error = false ) {
    if ( ! $user_id ) {
        if ( ! $suppress_error ) {
            describr()->error()->add( 'err_no_user', '' );
        }
        
        return false;
    }

    if ( describr_is_self( $user_id ) ) {
        $can_edit = current_user_can( 'edit_user', $user_id );
        
        if ( ! $can_edit && ! $suppress_error ) {
            describr()->error()->add( 'err_edit_user', '' );
        }

        return $can_edit;
    }

    if ( is_multisite() 
        && ! current_user_can( 'manage_network_users' )
        /*This filter is documented in wp-admin/user-edit.php*/
        && ! apply_filters( 'enable_edit_any_user_configuration', true )
    ) {
        if ( ! $suppress_error ) {
            describr()->error()->add( 'err_edit_user', '' );
        }
        
        return false;
    }

    $can_edit = current_user_can( 'edit_user', $user_id );

    if ( ! $can_edit && ! $suppress_error ) {
        describr()->error()->add( 'err_edit_user', '' );
    }

    return $can_edit;
}

/**
 * Retrieves whether the current user can delete the user
 *
 * @since 3.0
 * 
 * @param int $user_id         User ID
 * @param bool $suppress_error Whether to set error code
 * @return bool
 */
function describr_can_delete_user( $user_id, $suppress_error = false ) {
    if ( ! $user_id ) {
        if ( ! $suppress_error ) {
            describr()->error()->add( 'err_no_user', '' );
        }
        
        return false;
    }

    $can_delete = is_user_logged_in();
    
    $code = '';

    if ( ! describr_is_self( $user_id ) ) {
        if ( ! current_user_can( 'list_users' ) ) {
            $code = 'err_no_list_users';
            $can_delete = false;
        } elseif ( ! current_user_can( 'delete_users' ) ) {
            $code = 'err_no_delete_users';
            $can_delete = false;
        } elseif ( ! current_user_can( 'delete_user', $user_id ) ) {
            $code = 'err_no_delete_user';
            $can_delete = false;
        }
        //Ensure at least one user remains on the site
    } elseif ( user_can( $user_id, 'manage_options' ) ) {
        $code = 'err_admin_del';
        $can_delete = false;
        //Does user have the capability to delete self from the site's front end?
    } elseif ( ! user_can( $user_id, 'delete_user_front' ) ) {
        $code = 'no_delete_user_front';
        $can_delete = false;
    }

    if ( $code && ! $suppress_error ) {
        describr()->error()->add( $code, '' );
    }

    return $can_delete;
}

/**
 * Checks whether a password is required for the current action
 * 
 * @since 3.0
 * 
 * @param string $tab The current tab
 * @return bool True if password is required, otherwise false
 */
function describr_is_password_required( $tab ) {
    return describr()->account()->is_password_required( $tab );
}

/**
 * Retrieves whether current user has the capability
 * to edit a profile
 * 
 * @since 3.0
 * 
 * @return bool True if the current user can edit the profile, otherwise false
 */
function describr_can_edit_profile() {
    return  describr()->profile()->can_edit();
}

/**
 * Retrieves whether current user has the capability
 * to upload photos
 * 
 * @since 3.0
 * 
 * @return bool True if the current user can upload photos, otherwise false
 */
function describr_can_upload() {
    static $can_upload = null;
    
    if ( ! is_null( $can_upload ) ) {
        return $can_upload;
    }

    $can_upload = describr()->upload_photo()->user_can();
    
    return $can_upload;
}

/**
 * Retrieves whether a URL is in the pretty URL format
 * 
 * @since 3.0
 * 
 * @param string $url The URL
 * @return bool True if the URL is in the pretty URL format, otherwise false
 */
function describr_using_pretty_url( $url ) {
    return describr()->permalinks()->using_pretty_url( $url );
}

/**
 * Adds avatars to display names
 * 
 * @since 3.0
 * 
 * @param int|WP_User $user_id User ID or WP_User object
 * @param int         $size    Avatar size 
 * @return string Avatar with the display name
 */
function describr_floated_avatar( $user_id, $size = 120 ) {
    if ( is_object( $user_id ) ) {
        $display_name = isset( $user_id->display_name ) ? $user_id->display_name : _x( 'Anonymous', 'user', 'describr' );
        $user_id = $user_id->ID;        
    } else {
        $display_name = describr_display_name( $user_id );
    }

    $args = array(
        'id'                => $user_id, 
        'display_name_only' => false, 
        'size'              => $size,
    );

    /*This filter is documented in wp-content/plugins/describr/includes/describr-functions.php*/
    //"display_name" is the dynamic part of the filter's name
    return apply_filters( 'describr_user_display_name', $display_name, $args );
}

/**
 * Escapes textarea content for printing in nontextarea HTML tag
 * 
 * @since 3.0
 * 
 * @param string $content  The content to escape
 * @param array  $settings The field settings 
 * @return string The escaped content
 */
function describr_esc_textarea( $content, $settings = array() ) {
    if ( empty( $settings['html'] ) ) {
        $content = esc_html( $content );
    } else {
        if ( empty( $settings['no_autop'] ) ) {
            $content = wpautop( $content );
        }

        $content = make_clickable( $content );
        $content = preg_replace_callback( 
            '/<a[^>]+>.*<\/a>/s', 
            function ( $matches ) {
                $a = $matches[0];

                if ( ! preg_match( '/rel=/i', $a ) ) {
                    $a = preg_replace( '/^<a/', '<a rel="ugc nofollow" ', $a );
                }
                
                if ( ! preg_match( '/target=/i', $a ) ) {
                    $a = preg_replace( '/^<a/', '<a target="_blank" ', $a );
                }
                
                if ( preg_match( '/target=("|\')_blank\1/', $a ) ) {
                    $accessibility_text = sprintf(
                        '<span class="describr-a11y-text"> %s</span>',
                        /*translators: Hidden accessibility text.*/
                        esc_html__( '(opens in a new tab)', 'describr' )
                        );

                    $a = preg_replace( '/<\/a>$/', "{$accessibility_text}</a>", $a );
                }
                
                return $a;
            }, 
            $content 
        );
        
        $content = wp_replace_insecure_home_url( $content );
        $content = wp_kses_post( $content );
        
        if ( empty( $settings['no_nl2br'] ) ) {
            $content = nl2br( $content );
        }
    }

    return $content;
}

/**
 * Retrieves the profile ID
 * 
 * @since 3.0
 * 
 * @return int The profile ID
 */
function describr_profile_id() {
    return describr()->profile()->ID;
}

/**
 * Retrieves a field's HTML for displaying on 
 * the profile on the front end
 * 
 * @since 3.0
 *
 * @param string $field    Field
 * @param array  $settings Field settings
 * @return string HTML or or an empty string
 */
function describr_display_value( $field, $settings = array() ) {
    $val = describr_user_var( $field );

    if ( false !== $val && null !== $val ) {
        $val = map_deep( $val, 'trim' );
    }
    
    if ( ! $settings ) {
        $settings = describr_get_field( $field );
    }

    $type = isset( $settings['type'] ) ? $settings['type'] : '';

    /**
     * Filters a field's HTML for displaying on
     * the profile on the front end
     *
     * @since 3.0
     * 
     * @param mixed  $val      The value to be displayed
     * @param array  $settings The field settings
     * @param string $field    The field
     * @param string $type     The field type
     */
    $val = apply_filters( 'describr_profile_field', $val, $settings, $field, $type );

    /**
     * Filters a field's HTML for displaying on
     * the profile on the front end
     * 
     * The dynamic part of the filter's name
     * is the name of the field
     *
     * @since 3.0
     * 
     * @param mixed  $val      The value to be displayed
     * @param array  $settings The field settings
     * @param string $type     The field type
     */
    $val = apply_filters( "describr_profile_field_{$field}", $val, $settings, $type );

    /**
     * Filters a field's HTML for displaying on
     * the profile on the front end
     * 
     * The dynamic part of the filter's name
     * is the name of the type of field
     *
     * @since 3.0
     * 
     * @param string|int|array $val   The value to be displayed
     * @param array            $settings  The schema for the field associated with the value
     * @param string           $field The field
     */
    $val = apply_filters( "describr_profile_field_{$type}", $val, $settings, $field );
    
    if ( $val && ( is_string( $val ) || is_array( $val ) ) ) {
        $val = map_deep( $val, 'convert_smilies' );
    }

    return $val;
}

/**
 * Retrieves the accessible value for a field for 
 * displaying on the profile on the front end
 * 
 * @since 3.0
 *
 * @param string $field    Field
 * @param array  $settings Field settings
 * @return mixed Field's value optimized for accessibility
 */
function describr_field_accessible_value( $field, $settings = array() ) {
    static $fields = array();

    if ( isset( $fields[ $field ] ) ) {
        return $fields[ $field ];
    }

    $val = map_deep( describr_user_var( $field ), 'trim' );
    
    if ( ! $settings ) {
        $settings = describr_get_field( $field );
    }

    $type = isset( $settings['type'] ) ? $settings['type'] : '';

    /**
     * Filters a field's accessible value
     *
     * @since 3.0
     * 
     * @param mixed  $val      The value to be displayed
     * @param array  $settings The field settings
     * @param string $field    The field
     * @param string $type     The field type
     */
    $val = apply_filters( 'describr_profile_field_accessible', $val, $settings, $field, $type );

    /**
     * Filters a field's accessible value
     * 
     * The dynamic part of the filter's name
     * is the name of the field
     *
     * @since 3.0
     * 
     * @param mixed  $val      The value to be displayed
     * @param array  $settings The field settings
     * @param string $type     The field type
     */
    $val = apply_filters( "describr_profile_field_accessible_{$field}", $val, $settings, $type );

    /**
     * Filters a field's accessible value
     * 
     * The dynamic part of the filter's name
     * is the name of the type of field
     *
     * @since 3.0
     * 
     * @param string|int|array $val   The value to be displayed
     * @param array            $settings  The schema for the field associated with the value
     * @param string           $field The field
     */
    $val = apply_filters( "describr_profile_field_accessible_{$type}", $val, $settings, $field );
    
    if ( $val && ( is_string( $val ) || is_array( $val ) ) ) {
        $val = map_deep( $val, 'convert_smilies' );
    }
    
    $fields[ $field ] = $val;

    return $fields[ $field ];
}

/**
 * Retrieves a profile URL
 * 
 * @since 3.0
 * 
 * @param string      $slug    Slug for the URL, more than likely 
 *                             the user's nicename
 * @param int|WP_User $user_id User ID or WP_User object  
 * @return string The profile URL
 */
function describr_profile_url( $slug = '', $user_id = 0 ) {
    if ( $slug instanceof WP_User ) {
        if ( ! empty( $slug->ID ) ) {
            $user_id = $slug->ID;
        }

        $slug = $slug->user_nicename;
    }

    if ( '' !== $slug ) {
        $slug = trim( $slug );
    }
    
    if ( ! strlen( $slug ) ) {
        if ( $user_id instanceof WP_User ) {
            $slug = $user_id->user_nicename;
        } else {
            if ( ! $user_id ) {
                $user_id = describr_profile_id();
            }

            $user = get_userdata( $user_id );
            
            if ( ! isset( $user->user_nicename ) ) {
                return '';
            }

            $slug = $user->user_nicename;
        }
    }    
    
    return describr()->permalinks()->profile_url( $slug );
}

/**
 * Retrieves a profile tab URL
 * 
 * @since 3.0
 * 
 * @param string $tab           Profile tab
 * @param bool   $check_default Whether to check if the 
 *                              tab is the default tab
 * @return string The profile tab URL
 */
function describr_profile_tab_url( $tab, $check_default = false ) {
    return describr()->profile()->tab_url( $tab, $check_default );
}

/**
 * Retrieves whether a user can be shown
 * 
 * @since 3.0
 * 
 * @param int|WP_User $user_id User ID or WP_User object
 * @return bool True if the user can be shown, otherwise false
 */
function describr_is_user_viewable( $user_id = 0 ) { 
    if ( ! $user_id ) {
        $user_id = describr_profile_id();
    }

    /**
     * Filters whether the user can be shown
     * 
     * @since 3.0
     * 
     * @param true        $is_viewable Whether the user can be shown
     * @param int|WP_User $user_id     User ID or WP_User object
     */
    return (bool) apply_filters( 'describr_is_user_viewable', true, $user_id );
}

/**
 * Retrieves whether the profile is viewable
 * 
 * @since 3.0
 * 
 * @return bool
 */
function describr_is_profile_viewable_() {
    $id = describr_profile_id();

    return ( is_int( $id ) && 0 < $id );
}

/**
 * Retrieves whether a profile can be indexed by search engines
 * 
 * @since 3.0
 * 
 * @param int $user_id The profile ID
 * @return bool True if the profile can be indexed, otherwise false
 */
function describr_can_profile_be_indexed( $user_id ) {
    if ( empty( $user_id ) ) {
        return true;
    }

    if ( ! describr_is_member( $user_id ) ) {
        return false;
    }

    $key = 'show_profile';

    $settings = describr_get_field( $key );

    if ( empty( $settings['disabled_by_admin'] ) && user_can( $user_id, 'edit_user', $user_id ) ) {
        $privacy = trim( get_user_meta( $user_id, $key, true ) );
        
        if ( $privacy && 'public' !== $privacy ) {
            return false;
        }
    }

    return true;
}

/**
 * Retrieves whether a user is logged in anywhere
 * 
 * Mainly used to determine whether to display the indicator
 * for a user who is logged in
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return bool True if the user is logged in, otherwise false
 */
function describr_is_user_logged_in_anywhere( $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = describr_user( 'ID' );
    }
    
    /**
     * Filters whether to determine if a user is logged in
     * 
     * @since 3.0
     * 
     * @param bool $can_view_login_status Whether to determine if
     *                                    a user is logged in
     * @param int  $user_id               User ID
     */
    if ( apply_filters( 'describr_determine_user_logged_in', true, $user_id ) ) {
        $sessions = WP_Session_Tokens::get_instance( $user_id );
        
        return ! empty( $sessions->get_all() );
    }

    return false;
}

/**
 * Retrieves whether when a user last logged in
 * can be shown
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return bool True if when the user last logged in can be shown, 
 *              otherwise false
 */
function describr_can_view_when_logged_in( $user_id ) {
    /**
     * Filters whether when the user last logged in can be shown
     * 
     * @since 3.0
     * 
     * @param true $can_view_when_logged_in Whether to display when 
     *                                      the user last logged in
     * @param int  $user_id                 ID for the user whose 
     *                                      last-logged-in time might 
     *                                      be shown
     */
    return (bool) apply_filters( 'describr_can_view_when_logged_in', true, $user_id );
}

/**
 * Retrieves whether when a user registered can be shown
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return bool True if when the user registered can be shown, otherwise false
 */
function describr_can_view_registered( $user_id ) {
    /**
     * Filters whether when a user registered can be shown
     * 
     * @since 3.0
     * 
     * @param true $can_view_registered Whether when a user registered can be shown
     * @param int  $user_id             ID for the user whose registered date might 
     *                                  be shown
     */
    return (bool) apply_filters( 'describr_can_view_registered', true, $user_id );
}

/**
 * Retrieves the time the user last logged in
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return int timestamp
 */
function describr_time_last_login( $user_id ) {
    return get_user_meta( $user_id, 'describr_time_last_login', true );
}

/**
 * Retrieves a user's status
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return string|false The user's status, otherwise false
 */
function describr_user_status( $user_id ) {
    if ( empty( $user_id ) ) {
        return false;
    }
    
    /**
     * Filters whether the current user has the 
     * capability to view a user's status
     * 
     * @since 3.0
     * 
     * @param bool $can_edit_user Whether the current user can view the user's status
     * @param int  $user_id ID for the user whose status is being checked
     * */
    if ( ! apply_filters( 'describr_can_view_user_status', current_user_can( 'edit_user', $user_id ), $user_id ) ) {
        return false;
    }

    $account_status = get_user_meta( $user_id, 'account_status', true );
    
    if ( $account_status ) {
        return trim( $account_status );
    }

    return false;       
}

/**
 * Retrieves a user' status indicator
 * 
 * @since 3.0
 * 
 * @param int $user_id ID for the user whose status might be shown
 * @return string User's status indicator or an empty string
 */
function describr_user_status_indicator( $user_id ) {
    /**
     * Filters the user's status indicator
     * 
     * @since 3.0
     * 
     * @param string       $indicator User's status indicator
     * @param string|false $status    User's status or empty string
     * @param int          $user_id   ID for the user whose 
     *                                status might be shown
     */
    return (string) apply_filters( 'describr_user_status_indicator', '', describr_user_status( $user_id ), $user_id );
}

/**
 * Retrieves whether a user is important
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return bool True if the user is important, otherwise false
 */
function describr_is_user_important( $user_id = 0 ) {
    $user = get_userdata( $user_id );

    return ! empty( $user->important );
}

/**
 * Retrieves the indicator for an important user
 * 
 * @since 3.0
 * 
 *  $user_id 
 * @param int $user_id User ID
 * @return string The indicator for an important user or an empty string
 */
function describr_important_user_indicator( $user_id ) {
    /**
     * Filters the indicator for an important user
     * 
     * @since 3.0
     * 
     * @param string $indicator Indicator for an important user
     * @param int    $user_id   User ID
     */
    return (string) apply_filters( 'describr_important_user_indicator', '', $user_id );
}

/**
 * Enqueues password script
 * 
 * @since 3.0
 */
function describr_enqueue_pwd_script() {
    $string = array( 
        'show'     => __( 'Show password' ,'describr' ),
        'hide'     => __( 'Hide password' ,'describr' ),
        'Mismatch' => __( 'Passwords do not match.', 'describr' ),
        'curPass'  => __( 'Your current password is required.', 'describr' ),  
        'none'     => __( 'Password is required.', 'describr' ),
        'req'      => __( 'A strong password is required.', 'describr' ),
        'same'     => __( 'Your new password must be different from your current password.', 'describr' ),
    );

    $data = describr_get_field( 'user_pass' );
    
    unset( $data['asides'] );  

    if ( ! empty( $data['strong'] ) ) {
        if ( ! is_admin() ) {
            wp_enqueue_script( 'password-strength-meter' );
        }
        
        $string['short']    = __( 'Very weak', 'describr' );
        $string['bad']      = __( 'Weak', 'describr' );
        $string['good']     = _x( 'Medium', 'password strength', 'describr' );
        $string['strong']   = __( 'Strong', 'describr' );
        $string['mismatch'] = __( 'Mismatch', 'describr' );
        $string['unknown']  = _x( 'Unknown', 'password','describr' );
        $string['minLen']   = /*translators: %s: Minimum number of characters.*/ __( 'Your password must contain at least %s characters.', 'describr' );
        $string['maxLen']   = /*translators: %s: Maximum number of characters.*/ __( 'Your password must contain no more than %s characters.', 'describr' );
        $string['login']    = __( 'Your password cannot contain your username.', 'describr' );
        $string['email']    = __( 'Your password cannot contain your email address.', 'describr' );
        
        $special_chars = describr_special_chars_entity_numbers();

        $data['isLoggedIn'] = is_user_logged_in();
        
        if ( $special_chars ) {
            $data['puncts'] = implode( '', array_keys( $special_chars ) );

            $string['puncts'] = sprintf(
                /*translators: %s: Punctation marks.*/
                __( 'Your password must contain at least one character like %s.', 'describr' ),
                '<code>' . implode( ' ', $special_chars ) . '</code>'
            );
        }
        
        $string['complexity'] = __( 'Your password must contain at least one lowercase letter, one uppercase letter, and one number.', 'describr' );

        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $data['login'] = $current_user->user_login;
            $data['email'] = $current_user->user_email;
        }
    }

    $handle = 'describr-user-pw';
    
    wp_enqueue_script( $handle );

    wp_add_inline_script( $handle, sprintf( 'describr.pwd = %s;', json_encode( $data ) ), 'before' );

    wp_localize_script( $handle, 'describrPwdL10n', $string );
}

/**
 * Verifies whether any part of the password identifies the user
 * 
 * @since 3.0
 * 
 * @param string   $password Password
 * @param WP_User  $user     WP_User object of the user
 * @param WP_Error $error    WP_Error object
 */
function describr_check_identifier_in_pass( $password, $user, $error ) {
    if ( is_user_logged_in() && ! describr_is_self( $user->ID ) ) {
        describr_check_identifier_in_pass( $password, wp_get_current_user(), $error );
    }

    $blacklist = array( 
            'first_name'    => __( 'Your password cannot contain your first name.', 'describr' ),
            'last_name'     => __( 'Your password cannot contain your last name.', 'describr' ),
            'display_name'  => __( 'Your password cannot contain your display name.', 'describr' ),
            'nickname'      => __( 'Your password cannot contain your nickname.', 'describr' ),
            'birthdate'     => __( 'Your password cannot contain part of your birthdate.', 'describr' ),
            'user_nicename' => __( 'Your password cannot contain your nicename.', 'describr' ),
        );

    foreach ( $blacklist as $key => $msg ) {
        $data = trim( $user->$key );
        
        if ( '' !== $data ) {
            if ( 'birthdate' === $key ) {
                if ( describr_is_bdate_in_password( $password, $data ) ) {
                    $error->add( 'user_pass', $msg );
                }
            } elseif ( false !== mb_stripos( $password, $data ) ) {
                $error->add( 'user_pass', $msg );
            }
        }
    }
}

/**
 * Retrieves whether any part of the user's birthdate is
 * found in the user's password
 * 
 * @since 3.0
 * 
 * @param string $password Password
 * @param string $bdate    User's Birthdate
 * @return bool True if any part of the user's birthdate is found in the password, otherwise false
 */
function describr_is_bdate_in_password( $password, $bdate ) {
    $bdate_no_change = $bdate;
    $bdate = describr_normalize_date_sep( $bdate );
    $bdate_compact = implode( '', explode( describr_date_sep(), $bdate ) );
    $bdate_ = describr_utc_date( $bdate, describr_get_locale_date_format_by_date( $bdate ) );
    $birthyear = describr_utc_date( $bdate, describr_locale_date_format( 'year' ) );
                
    $bdate_types = array( 
        $bdate_no_change,
        $bdate,
        $bdate_compact,
        $bdate_,
        $birthyear,
    );

    foreach ( array_filter( $bdate_types ) as $bdate_type ) {
        if ( false !== mb_stripos( $password, $bdate_type ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Caches a targeted user's ID
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return init The user ID
 */
function describr_set_user_id( $user_id = 0 ) {
    static $id = 0;

    if ( $user_id ) {
        $id = $user_id;
    }

    return $id;
}

/**
 * Retrieves a targeted user's ID
 * 
 * @since 3.0
 * 
 * @return init The user ID
 */
function describr_get_user_id() {
    return describr_set_user_id();
}

/**
 * Sets the WP_User object of the targetted user
 * 
 * @since 3.0
 * 
 * @param int|string|WP_POST|WP_User|WP_Comment $user_id User ID, email, slug, 
 *                                                       WP_POST obejct, WP_Comment obejct, 
 *                                                       or WP_User object
 * @param string                                $by      What to get the user by
 */
function describr_fetch_user( $user_id, $by = '' ) {
    if ( '' === $by && is_numeric( $user_id )  ) {
        describr()->user()->set( $user_id );
    } else {
        describr()->user()->setup( $user_id, $by );
    }
}

/**
 * Resets user 
 * 
 * @since 3.0
 */
function describr_reset_user() {
    describr()->user()->set();
}

/**
 * Retrieves user meta for a targeted user
 * 
 * @since 3.0
 * 
 * @param string $key Meta key
 * @return mixed Meta value
 */
function describr_user_var( $key ) {
    if ( isset( describr()->user()->cur_user->$key ) ) {
        $value = describr()->user()->cur_user->$key;
    } else {
        $value = false;
    }
    
    /**
     * Filters the meta value for the targeted user
     * 
     * The dynamic part of the filter's name is
     * the key used to retrieve the value
     *  
     * @since 3.0
     * 
     * @param mixed $value Meta value
     */
    return apply_filters( "describr_user_{$key}_value", $value );
}

/**
 * Retrieves data for a targeted user
 * 
 * @since 3.0
 * @param string $key Data key
 * @param mixed  $args Additional argument needed to retrieve the data
 * @return mixed The data
 */
function describr_user( $key, $args = '' ) {
    switch ( $key ) {
        case 'login':
            $val = describr_user_var( 'user_login' );
            break;        
        case 'url':
            $val = describr_user_var( 'user_url' );
            break;
        case 'email':
            $val = describr_user_var( 'user_email' );
            break;        
        case 'nicename':
            $val = describr_user_var( 'user_nicename' );
            break;
        case 'fullname':
            $val = '';
            
            $first_name = trim( describr_user( 'first_name' ) );

            if ( strlen( $first_name ) ) {
                $last_name = trim( describr_user( 'last_name' ) );

                if ( strlen( $last_name ) ) {
                    $val = sprintf( '%1$s %2$s', $first_name, $last_name );
                } else {
                    $val = $first_name;
                }
            }
            break;
        case 'fullname_lastfirst':
            $val = '';
            
            $last_name = trim( describr_user( 'last_name' ) );
            
            if ( strlen( $last_name ) ) {
                $val = $last_name;
                
                $first_name = trim( describr_user( 'first_name' ) );

                if ( strlen( $first_name ) ) {
                    $val .= ' ' . $first_name;
                }
            } else {
                $first_name = trim( describr_user( 'first_name' ) );

                if ( strlen( $first_name ) ) {
                    $val = $first_name;
                }
            }
            break;
        default:
           $val = describr_user_var( $key );
           break;
    }
    
    /**
     * Filters the targeted user's data
     * 
     * The dynamic part of the filter's name
     * is the key used to retrieve the user's data
     *  
     * @since 3.0
     * 
     * @param mixed $val  User's data
     * @param mixed $args Additional arguments for fetching the data
     */
    return apply_filters( "describr_user_{$key}", $val, $args );
}

/**
 * Checks if the target user is the current user
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return bool
 */
function describr_is_self( $user_id = 0 ) {
    return $user_id && $user_id === get_current_user_id();
}

/**
 * Retrieves whether field is viewable in display mode
 * 
 * @since 3.0
 * 
 * @param string $field              The field
 * @param int    $user_id            User ID
 * @param bool   $skip_profile_check Whether to check access to the user profile also
 * @return bool
 */
function describr_is_field_viewable( $field, $user_id = 0, $skip_profile_check = false ) {
    if ( ! $user_id ) {
        $user_id = describr_profile_id();
    }    
    
    if ( ! $user_id ) {
        return false;
    }

    if ( ! $skip_profile_check && ! describr_is_profile_viewable_() && ! describr_is_user_viewable( $user_id ) ) {
        return false;
    }
    
    /**
     * Filters whether the field is viewable
     * 
     * @since 3.0
     * 
     * @param true   $is_viewable Whether the field is viewable
     * @param string $field      The field
     * @param int    $user_id    User ID
     */
    return (bool) apply_filters( 'describr_is_field_viewable', true, $field, $user_id );
}

/**
 * Retrieves whether a field is editable
 * 
 * @since 3.0
 * 
 * @param string $field The field
 * @return bool True if the field is editable, otherwise false
 */
function describr_is_field_editable( $field ) {
    $fields = describr_editable_fields();
    return isset( $fields[ $field ] );
}

/**
 * Retrieves whether a field is required
 * 
 * @since 3.0
 * 
 * @param string $field The field
 * @return bool True if the field is required, otherwise false
 */
function describr_is_field_required( $field ) {
    $fields = describr_editable_fields();
    return ! empty( $fields[ $field ]['required'] );
}

/**
 * Retrieves whether a field's aside is editable
 * 
 * @since 3.0
 * 
 * @param string $field Field
 * @param string $aside Aside
 * @return bool True if the aside is editable, otherwise false
 */
function describr_is_aside_editable( $field, $aside = '' ) {
    $settings = describr_get_field( $field );

    if ( ! empty( $settings['disabled_by_admin'] ) || ( empty( $settings['public'] ) && ! current_user_can( 'manage_options' ) ) ) {
        return false;
    }
    
    $asides = describr_get_aside( $field );
    
    unset( $asides['_cat'] );

    if ( ! $asides ) {
        return false;
    }

    if ( ! $aside ) { 
        foreach ( array_keys( $asides ) as $aside_ ) {
            if ( describr_is_aside_editable( $field, $aside_ ) ) {
                return true;
            }
        }

        return false;
    }

    if ( ! isset( $asides[ $aside ] ) ) {
        return false;
    }

    $aside_settings = $asides[ $aside ];

    return ! empty( $aside_settings['name'] ) && ! empty( $aside_settings['editable'] );
}

/**
 * Retrieves whether the current user can block a user
 * 
 * @since 3.0
 * 
 * @param int $user_id User ID
 * @return bool True if the user can be block, otherwise false
 */
function describr_can_block_user( $user_id ) {
    return current_user_can( 'block_user' ) && ! describr_is_self( $user_id );
}

/**
 * Retrieves whether messaging is enabled
 * 
 * @since 3.0
 * 
 * @return bool True if messaging is enable, otherwise false
 */
function describr_is_messaging_enabled() {
    return ! empty( get_option( 'describr_enable_profile_messaging' ) );
}

/**
 * Retrieves whether the current user can send message
 * to a user
 * 
 * @since 3.0
 * 
 * @param int $recipient_id ID for the message recipient
 * @return bool
 */
function describr_can_send_message( $recipient_id = 0 ) {
    if ( ! $recipient_id ) {
        $recipient_id = describr_profile_id();
    }
    
    /**
     * Filters whether the current user
     * can send message to a user
     * 
     * @since 3.0
     * 
     * @param true $can_send_message Whether the current user can
     *                               send message to a user
     * @param int  $recipient_id     ID for the message recipient
     */
    return (bool) apply_filters( 'describr_can_user_send_message', true, $recipient_id );
}

/**
 * Retrieves a user's display name
 * 
 * @since 3.0
 * 
 * @param int User ID
 * @return string The user's display name or an empty string
 */
function describr_display_name( $user_id ) {
    return get_the_author_meta( 'display_name', $user_id );
}

/**
 * Retrieves a plugin page ID
 *
 * @param string $page The page slug
 *
 * @return int The page ID
 */
function describr_get_page_id( $page ) {
    return describr()->pages()->$page;
}

/**
 * Retrieves whether a page is a plugin page
 *
 * @param string $page The page
 *
 * @return bool
 */
function describr_is_page( $page ) {
    global $post;

    if ( empty( $post->ID ) ) {
        return false;
    }

    return $post->ID === describr_get_page_id( $page );
}

/**
 * Retrieves placeholders.
 *
 * @since 3.0
 * @since 3.0.1 Adds the `$slug` parameter.
 *
 * @param array  $args Additional arguments passed to the template.
 * @param string $slug Template slug.
 * @return array An array of content keyed by placeholders.
 */
function describr_apply_placeholders( $args = array(), $slug = '' ) {
    $placeholders = array(
        '{SITE_NAME}'   => get_bloginfo( 'name', 'display' ),
        '{SITE_URL}'    => describr_home_url(),
        '{LOGIN_URL}'   => describr_login_url(),
        '{ADMIN_EMAIL}' => get_option( 'admin_email' ),
    );
        
    /**
     * Filters placeholders.
     * 
     * @since 3.0
     * @since 3.0.1 Adds the `$slug` argument.
     * 
     * @param array  $placeholders Placeholders.
     * @param array  $args         Additional arguments passed to the template.
     * @param string $slug         Template slug.
     */
    return apply_filters( 'describr_placeholders', $placeholders, $args, $slug );
}

/**
 * Replaces email placeholders with content.
 *
 * @since 3.0
 * @since 3.0.1 Adds the `$slug` argument.
 * @since 3.0.1 Replaces placeholders in replacements.
 * @since 3.0.1 Passes `$slug` to `describr_apply_placeholders()`.
 *
 * @param string $content The string containing placeholders.
 * @param array  $args    Additional arguments passed to the template.
 * @param string $slug    Template slug.
 * @return string The content whose placeholders have been replaced with content.
 */
function describr_replace_placeholders( $content, $args = array(), $slug = '' ) {
    if ( isset( $args['describr_replace_placeholders_in_replacement'] ) ) {
        unset( $args['describr_replace_placeholders_in_replacement'] );
        $replace_placeholders_in_replacement = true;
    }

    foreach ( describr_apply_placeholders( $args, $slug ) as $placeholder_name => $placeholder_value ) {
        $content = str_replace( $placeholder_name, $placeholder_value, $content );
    }
    
    if ( isset( $replace_placeholders_in_replacement ) ) {
        return $content;
    } else {
        $args['describr_replace_placeholders_in_replacement'] = true;

        return describr_replace_placeholders( $content, $args, $slug );
    }
}

/**
 * Prefixes a value with the plugin's name
 * 
 * Mainly used with `array_map()`
 * 
 * @since 3.0
 * 
 * @param string|int $val The value to prefix
 * @return string The value prefixed with the plugin's name 
 */
function describr_prefix( $val ) {
    return describr()->prefix . $val;
}

/**
 * Fetches single site admin emails
 * 
 * @since 3.0
 * 
 * @param string $email Admin email
 * @return array Admin emails
 */
function describr_admin_emails( $email = '' ) {
    $admin_emails = get_option( 'describr_admin_email', '' );
    
    $admin_emails = explode( ',', $admin_emails );

    $admin_emails[] = get_bloginfo( 'admin_email' );

    if ( $email ) {
        $admin_emails[] = $email;
    }
    
    $admin_emails = array_map( 'trim', $admin_emails );

    return array_unique( array_filter( $admin_emails ) );
}

/**
 * Fetches network admin emails
 * 
 * @since 3.0
 * 
 * @param string $email Network Admin email
 * @return array Network admin emails
 */
function describr_wpmu_admin_emails( $email = '') {
    if ( ! is_multisite() ) {
        return describr_admin_emails( $email );
    }

    $admin_emails = get_site_option( 'describr_admin_email', '' );
    
    $admin_emails = explode( ',', $admin_emails );

    $admin_emails[] = get_site_option( 'admin_email' );

    if ( $email ) {
        $admin_emails[] = $email;
    }
    
    $admin_emails = array_map( 'trim', $admin_emails );

    return array_unique( array_filter( $admin_emails ) );
}

/**
 * Adds noindex directives for the "robots" meta tag
 * 
 * @since 3.0
 * 
 * @param array $robots Robots meta tag directives
 * @return array An array containing noindex directives
 */
function describr_robots_noindex( $robots ) {
    $robots['noindex']      = true;
    $robots['nosnippet']    = true;
    $robots['noarchive']    = true;
    $robots['nocache']      = true;
    $robots['noimageindex'] = true;
    
    return $robots;
}

/**
 * Retrieves the number of records to retrieve from the database
 * 
 * @since 3.0
 * 
 * @return int The number of records to return retrieve the database
 */
function describr_per_page() {
    /**
     * Filters the number of records to return retrieve the database
     * 
     * @since 3.0
     * 
     * @param int $limit The number of records to return retrieve the database
     */
    return absint( apply_filters( 'describr_per_page', get_option( 'posts_per_page', 20 ) ) );
}

/**
 * Returns the indicator for a user who is logged
 * into the site
 * 
 * @since 3.0
 * 
 * @param int $user_id ID for the user who might be logged into the site
 * @return string Indicator for a user who is logged into the site if 
 *                the user is logged in or an empty string
 */
function describr_logged_in_indicator( $user_id ) {
    /**
     * Filters the indicator for a user who is logged
     * into the site
     * 
     * @since 3.0
     * 
     * @param string $indicator Indicator for a user who is logged into 
     *                          the site
     * @param int    $user_id   ID for the user for whom the indicator
     *                          might be shown
     */
    return (string) apply_filters( 'describr_logged_in_indicator', '', $user_id );
}

/**
 * Retrieves whether a user is a valid member of the site
 * 
 * @since 3.0
 * 
 * @param $user User ID
 * @return bool
 */
function describr_is_member( $user_id ) {
    if ( ! is_object( $user_id ) ) {
        $user = get_userdata( $user_id );
    } else {
        $user = $user_id;
    }
    
    return ! empty( $user->ID ) && describr()->user()->is_valid( $user );
}

/**
 * Retrieves the handle from a social network's URL
 * 
 * @since 3.0
 * 
 * @param string $base URL's base
 * @param string $url  Full URL
 * @return string The handle
 */
function describr_get_handle_from_url( $base, $url ) {
    return preg_replace( '/^(' . preg_quote( $base, '/' ) . ')/siu', '', trim( $url ) );
}

/**
 * Converts birthdate to age
 * 
 * @since 3.0
 * 
 * @param string $birthdate The birthdate
 * @return string The age
 */
function describr_age( $birthdate ) {
    return describr()->date()->get_age( $birthdate );
}

/**
 * Retrieves fully translated date
 * 
 * @since 3.0
 * 
 * @param string      $format    PHP date format
 * @param null|int    $timestamp Unix timestamp
 * @param null|string $timezone  Time zone to output result in
 * @return string|false The fully translated date or false if there is an error
 */
function describr_date( $format, $timestamp = null, $timezone = null ) {
    if ( empty( $format ) ) {
        $format = describr_locale_date_format();
    }

    if ( $timestamp && ! is_numeric( $timestamp ) ) {
        $timestamp = strtotime( $timestamp );
    }
    
    return wp_date( $format, $timestamp, describr_timezone( $timezone ) );
}

/**
 * Retrieves fully translated date based on UTC DateTimeZone object
 * 
 * Used on dates like bithdates that don't require a local timezone
 * 
 * @since 3.0
 * 
 * @param string       $date   The date
 * @param false|string $format PHP date format
 * @return string|false The fully translated date or false if there is an error
 */
function describr_utc_date( $date, $format = false ) {
    return describr()->date()->utc_date( $date, $format );
}

/**
 * Retrieves the given time zone as a DateTimeZone object
 * 
 * @since 3.0
 * 
 * @param null|string $timezone The time zone
 * @return DateTimeZone object
 */
function describr_timezone( $timezone ) {
    return new DateTimeZone( describr()->date()::timezone_string( $timezone ) );
}

/**
 * Converts decimal part of UTC offset to minutes
 * 
 * @since 3.0
 * 
 * @return string $offset The UTC offset
 * @return string The UTC offset, with decimal converted to minutes
 */
function describr_utc_decimal_to_minutes( $offset ) {
    return str_replace( array( '.25', '.5', '.75' ), array( ':15', ':30', ':45' ), $offset );
}

/**
 * Retrieves fully translated PHP date format
 * 
 * @since 3.0
 * 
 * @param string $format The date format
 * @return string Translated date format
 */
function describr_locale_date_format( $format = '' ) {
    if ( ! $format ) {
        $format = 'date_time';
    }
    
    if ( 'date@time' === $format ) {
        $at = _x( 'at', 'between date and time', 'describr' );
        $at = addcslashes( $at, 'A..Za..z' );
        $at = " $at ";
        return describr_locale_date_format( 'date' ) . $at . describr_locale_date_format( 'time' );
    } elseif ( 'date_time' === $format ) {
        return describr_locale_date_format( 'date' ) . ' ' . describr_locale_date_format( 'time' );
    }

    return describr()->locale()->get_date_time_format( $format );
}

/**
 * Retrieves fully translated PHP date format
 * depending on user-submitted date. Suited if a 
 * user supplies a year, or month and 
 * 
 * @since 3.0
 * 
 * @param string $date The date
 * @return string Translated date format
 */
function describr_get_locale_date_format_by_date( $date ) {
    return describr()->date()->get_format_by_date( $date );
}

/**
 * Replaces the date format separator with the favored separator
 * 
 * @since 3.0
 * 
 * @param string $date The date
 * @return string The date, with favored separator
 */
function describr_normalize_date_sep( $date ) {
    return describr()->date()->normalize_sep( $date );
}

/**
 * Retrieves a full date from partial date. 
 * 
 * For example, convert 2025 to 2025-01-01
 * 
 * @since 3.0
 * 
 * @param string $date The partial date from which to create a full date
 * @return string A date in YYYY-MM-DD format
 */
function describr_normalize_date( $date = '' ) {
    return describr()->date()->normalize_date( $date );
}

/**
 * Retrieves fully translated phone number type
 * 
 * @since 3.0
 * 
 * @param int $index The index to locate the phone number type 
 *                   in the phone number type array
 * @return string The fully translated phone number type
 */
function describr_phone_number_type( $type_number ) {
    return describr()->locale()->get_phone_number_type( (int) $type_number );
}

/**
 * Sanitizes user-submitted data
 * 
 * @since 3.0
 * 
 * @param mixed $val      The submitted data
 * @param array $settings The field settings
 * @return mixed The sanitized data
 */
function describr_sanitize_input( $val, $settings = array() ) {
    if ( ! isset( $settings['type'] ) ) {
        $settings['type'] = '';
    }
    
    $type = $settings['type'];

    if ( is_array( $val ) ) {
        foreach ( $val as $k => $v ) {
            $val[ $k ] = describr_sanitize_input( $v, $settings );
        }
    } else {
        switch ( $type ) {
            case 'email':
                $val = sanitize_email( $val );
                $val = addslashes( wp_kses( $val, current_filter() ) );
            break;
            case 'url':
                $val = wp_strip_all_tags( $val );
                $val = sanitize_url( $val );
                $val = addslashes( wp_kses( $val, current_filter() ) );
            break;
            case 'textarea':
                if ( ! empty( $settings['html'] ) ) {
                    $val = addslashes( wp_kses_post( $val ) );
                    $val = wp_encode_emoji( $val );
                } else {                    
                    $val = describr_sanitize_input( $val, array( 'type' => '_textarea' ) );
                }
                break;
            default:
                if ( ! empty( $settings['name'] ) && 'user_nicename' === strtolower( $settings['name'] ) ) {
                    $val = sanitize_user( $val, true );
                    $val = sanitize_title( $val );
                } else {
                    $val = '_textarea' === $type ? sanitize_textarea_field( $val ) : sanitize_text_field( $val );
                    $val = addslashes( wp_kses( $val, current_filter() ) );
                    $val = wp_kses_normalize_entities( $val );
                    $val = htmlspecialchars( $val, ENT_NOQUOTES, get_bloginfo( 'charset' ), false );
                }

                if ( ( isset( $settings['name'] ) && 'tagline' === $settings['name'] ) || '_textarea' === $type ) {
                    $val = wp_encode_emoji( $val );
                }                
                break;
        }
    }
    
    return $val;
}

/**
 * Retrieves a field's aside
 * 
 * @since 3.0
 * 
 * @param string $field The field
 * @return array|false The field's aside or false if the field has no aside
 */
function describr_get_aside( $field ) {
    $asides = describr()->get_field_asides( $field );
    
    if ( ! empty( $asides ) ) {
        return $asides;
    }

    return false;
}

/**
 * Retrieves fields saved asides
 * 
 * @since 3.0
 * 
 * @param string $fields The fields
 * @return array The saved asides
 */
function describr_get_saved_asides( $fields ) {
    $output = array();

    foreach ( (array) $fields as $field ) {
        $output[ $field ] = describr()->profile_field_controls()->get_saved_asides( $field );
    }

    return array_filter( $output );
}

/**
 * Retrieves fully translated large number initial
 * 
 * @since 3.0
 * 
 * @param string $initial The initial
 * @return string The fully translated initial
 */
function describr_large_num_initial( $initial ) {
    return describr()->locale()->get_large_number_name_initial( $initial );
}

/**
 * Retrieves a rounded-down number with the accompanying
 * fully translated one-letter abbreviated large number 
 * initial
 * 
 * @since 3.0
 * 
 * @param init $num The number
 * @return array An array of the rounded number and initial
 */
function describr_number_initial( $num ) {
    $initials = array( 'K', 'M', 'B', 'T', 'Qa' );

    $divisor = 1000;
    $i = -1;
   
    while ( $divisor <= $num ) {
        $num /= $divisor;
        $i++;
    }

    return array( 
        'num'     => $num,
        'initial' => isset( $initials[ $i ] ) ? describr_large_num_initial( $initials[ $i ] ) : '',
    );
}

/**
 * Retrieves the Add $field button
 * 
 * @since 3.0
 * 
 * @param string $field The field
 * @return string
 */
function describr_add_profile_data_trigger( $field ) {
    $is_social = in_array( $field, array( 'describr' ), true );

    if ( $is_social ) {
        foreach ( describr_get_socials() as $social => $settings ) {
            if ( describr_field_exists( $social ) && describr_is_field_editable( $social ) ) {
                $is_editable = true;
                break;
            }
        }

        if ( ! isset( $is_editable ) ) {
            return '';
        }
    } elseif ( ! describr_is_field_editable( $field ) ) {
        return '';
    }

    if ( $is_social ) {
        $title = __( 'Social Network', 'describr' );
        $field = 'social_network';
    } elseif ( in_array( $field, array( 'lived_cities' ), true ) )  {
        $title = __( 'City', 'describr' );
    } else {
        $title = describr_get_field_( $field, 'title' );
    }
    
    $text = sprintf( 
        /*translators: %s: Field title.*/ 
        __( 'Add %s', 'describr' ), 
        $title 
    );
    
    $attr = esc_attr( $field );

    $add_elem = '<div class="describr-profile-menu-tab-field" data-mainfield="' . $attr . '"><div role="button" aria-expanded="false" tabindex="0" data-field="' . $attr . '" data-action="addfield" class="describr-profile-add-field">' . esc_html( $text ) . ' <span aria-hidden="true" class="dashicons dashicons-insert"></span></div></div>';
    
    return $add_elem;
}

/**
 * Retrieves all field keys
 * 
 * @since 3.0
 * 
 * @return array An array containing all field keys
 */
function describr_get_all_field_keys() {
    return array_keys( describr_get_field() );
}

/**
 * Retrieves field (s) settings
 * 
 * @since 3.0
 * 
 * @param string|array|false $field The field key, 
 *                                  an array of field keys,
 *                                  or false for all fields
 * @return array The field (s) settings
 */
function describr_get_field( $field = false ) {
    return describr()->get_fields( $field );
}

/**
 * Retrieves fields that are in the same asides' category
 * as that of the passed field
 * 
 * @since 3.0
 * 
 * @param string|array  Field or array of fields
 * @return array|string The field if it doesn't share asides' category,
 *                      an array of fields sharing the same asides' category
 *                      as the passed field, or the field if no asides are found
 */
function describr_get_fields_by_aside( $field ) {
    if ( is_array( $field ) ) {
        $fields = array();

        foreach ( $field as $key ) {
            if ( ! in_array( $key, $fields, true ) ) {
                $_fields = (array) describr_get_fields_by_aside( $key );

                $fields = array_merge( $fields, $_fields );
            }
        }

        return $fields;
    }

    return describr()->get_fields_by_aside( $field );
}

/**
 * Retrieves authorized fields
 * 
 * @since 3.0
 * 
 * @param int          $user_id ID for the user to whom the fields belong   
 * @param string|array $fields  Field or array of fields to authorize
 * @return array An array of authorized vields
 */
function describr_get_viewable_fields( $user_id, $fields ) {
    return describr()->get_viewable_fields( $user_id, $fields );
}

/**
 * Retrieves editiable fields
 * 
 * @since 3.0
 * 
 * @return An array of editable fields
 */
function describr_editable_fields() {
    return describr()->editable_fields;
}

/**
 * Retrieves whether a field exists
 * 
 * @since 3.0
 * 
 * @param string $field The field to verify
 * @return bool True if the field exist, otherwise false
 */
function describr_field_exists( $field ) {
    return describr()->field_exists( $field );
}

/**
 * Retrieves social media fields
 * 
 * @since 3.0
 * @return array An array of social fields
 */
function describr_get_socials() {
    return describr()->get_socials();
}

/**
 * Retrieves a field's setting
 * 
 * @since 3.0
 * 
 * @param string $field   The field
 * @param string $setting The setting. For example, "title" could be 
 *                        passed to retrieve the field's title
 * @return mixed
 */
function describr_get_field_( $field, $setting ) {
    $settings = describr_get_field( $field );

    if ( isset( $settings[ $setting ] ) ) {
        return $settings[ $setting ];
    }

    return '';
}

/**
 * Retrieves list fields display in the profile header that
 * should not be wrapped in HTML during ajax request
 * 
 * @since 3.0
 * 
 * @return array The profile header fields
 */
function describr_profile_header_fields() {
    /**
     * Filters fields display in the profile header wthat
     * should not be wrapped in HTML during ajax request
     * 
     * @since 3.0
     * 
     * @param array The profile header fields
     */
    return apply_filters( 
        'describr_profile_header_fields',
        array( 
            'show_login', 
            'profile_url', 
            describr_photo_key(), 
            'user_registered', 
        )
    );
}

/**
 * Retrieves fields whose values are adjusted when displayed in edit mode
 * 
 * @since 3.0
 * 
 * @return array Fields
 */
function describr_profile_field_edit_mode_html() {
    static $fields = null;

    if ( ! is_null( $fields ) ) {
        return $fields;
    }

    /**
     * Filters list of fields whose values are adjusted when displayed in edit mode
     * 
     * @since 3.0
     * 
     * @param array Fields
     */
    $fields = apply_filters( 
        'describr_profile_field_edit_mode_html',
        array( 
            'description',
            'mobile_number', 
            'work_number', 
            'home_number',
            'timezone',
            'relationship',
            'gender',
            'locale',
            'locales',
            'birthdate',
            'work_history',
            'lived_cities',
            'high_school',
            'college',
        )
    );

    return $fields;
}

/**
 * Retrieves options for a field or for a field's aside
 * 
 * @since 3.0
 *
 * @param  string $key      Field or aside key
 * @param  array  $settings Field or aside settings
 * @return array The options
 */
function describr_settings_options( $key, $settings ) {
    return describr()->fields()->options( $key, $settings );
}

/**
 * Sets field to audit
 *
 * @since 3.0
 * 
 * @param string The field
 */
function describr_auditing_fields( $field ) {
    describr()->auditing_fields( $field );
}

/**
 * Audits fields
 *
 * @since 3.0
 * 
 * @param int $user_id User ID
 */
function describr_audit_fields( $user_id ) {
    describr()->audit_fields( $user_id );
}

/**
 * Retrieves submitted asides from $_POST
 * 
 * @since 3.0
 * 
 * @param string $field
 * @param int    $user_id User ID
 * @param array  $arr     An array that caches the submitted values, passed by reference
 */
function describr_pluck_field_status_and_priv( $field, $user_id, &$arr = array() ) {
    if ( ! empty( $_POST["{$field}_asides"] ) && ! isset( $arr[ $field ] ) ) {
        $submit = map_deep( wp_unslash( $_POST["{$field}_asides"] ), 'sanitize_text_field' );
        
        $settings = describr_get_aside( $field );
        
        $meta_key = isset( $settings['_cat'] ) ? $settings['_cat'] : $field;
        
        unset( $settings['_cat'] );
        
        if ( $settings ) {
            foreach ( $settings as $key => $_arr ) {
                if ( isset( $_arr['name'] ) && ! empty( $submit[ $_arr['name'] ] ) && describr_is_aside_editable( $field, $key ) ) {                        
                    $new_aside = $submit[ $_arr['name'] ];
                                        
                    if ( describr_is_option( $new_aside, describr_settings_options( "{$key}_aside", $_arr ) ) ) {
                        $meta_key_ = "{$meta_key}_{$_arr['name']}";

                        $current_aside = get_user_meta( $user_id, $meta_key_, true );

                        if ( $current_aside !== $new_aside ) {
                            $arr[ $field ][ $meta_key_ ] = $new_aside;
                        }
                    }
                }
            }
        }
    }
}

/**
 * Updates the field's status and privacy settings
 *
 * @since 3.0
 * 
 * @param array  $asides  Field privacy and status asides
 * @param string $field   The field
 * @param int    $user_id User ID
 */
function describr_update_field_status_and_priv( $asides, $field, $user_id ) {
    static $updated = array();

    foreach ( add_magic_quotes( $asides ) as $meta_key => $meta_value ) {
        //Test against duplicate asides that could occur when multiple fields are in the same category
        if ( ! isset( $updated[ $meta_key ] ) ) {
            if ( update_user_meta( $user_id, $meta_key, $meta_value ) ) {
                describr_auditing_fields( $field );
            }
            $updated[ $meta_key ] = true;
        }
    }
}

/**
 * Retrieves a field's subfields (supports) settings
 * 
 * For example, the lived_cities field's subfields are city and moved
 *
 * @since 3.0
 * 
 * @param null|array  $supports Subfields
 * @param null|string $field    Field
 */
function describr_get_field_supports( $supports = null, $field = null ) {
    return describr()->get_field_supports( $supports, $field );
}

/**
 * Sets date format examples for either a field (birthdate, for example)
 * or a field's support ("to" or "from", for example)
 * 
 * This is mostly used as a filter
 * 
 * @since 3.0
 * 
 * @param array $settings Field or field's support's settings
 * @param array Field or field's support's settings containing date format examples
 */
function describr_date_format_examples( $settings ) {
    $settings['description'] = sprintf(
        /*translators: "Ex" is the two-letter abbreviation of "example". %s: Date format examples.*/
        __( 'Ex: %s', 'describr' ),
        implode( wp_get_list_item_separator(), describr_date_formats() )
    );
    
    return $settings;
}

/**
 * Caches fields that return no accompanying data while fetching
 * all the fields for a profile tab
 * 
 * @since 3.0
 * 
 * @param array $field The field
 */
function describr_set_no_data( $field ) {
    if ( describr_can_edit_profile() ) {
        describr()->profile()->no_saved_data[] = $field;
    }
}

/**
 * Outputs a primary button
 * 
 * @since 3.0
 * 
 * @param string      $text  The text of the button. Defaults to "Save Changes"
 * @param string      $type  Value for the button's type attribute. Defaults to "submitt"
 * @param false|array $attrs Additional attributes for the button
 * @param string      $class Classes for button
 */
function describr_primary_button( $text = '', $type = '', $attrs = false, $class = '' ) {
    if ( ! $text ) {
        $text = __( 'Save Changes', 'describr' );
    }

    if ( ! $type ) {
        $type = 'submit';
    }
    ?>
    <button type="<?php echo esc_attr( $type );?>" class="describr-btn describr-btn-primary <?php echo esc_attr( $class );?>"<?php
    if ( is_array( $attrs ) ) {
        foreach ( $attrs as $attr => $val ) {
            echo ' ' . esc_attr( $attr ) . '="' . wp_kses_post( $val ) . '"';
        }
    } elseif ( ! empty( $attrs ) ) {
        echo ' ' . wp_kses_post( $attrs );
    }
    ?>><?php echo wp_kses_post( $text ); ?></button>
    <?php
}

/**
 * Outputs a secondary button
 * 
 * @since 3.0
 * 
 * @param string             $text  The text of the button. Defaults to "Cancel"
 * @param false|array|string $attrs Additional attributes for the button
 * @param string             $class Classes for button. Defaults to "describr-btn-cancel"
 */
function describr_secondary_button( $text = '', $attrs = false, $class = '' ) {
    if ( ! $text ) {
        $text = __( 'Cancel', 'describr' );
    }

    if ( ! $class ) {
        $class = 'describr-btn-cancel';
    }
    ?>
    <button type="button" class="describr-btn describr-btn-secondary <?php echo esc_attr( $class );?>"<?php
    if (  is_array( $attrs ) ) {
        foreach ( $attrs as $attr => $val ) {
            echo ' ' . esc_attr( $attr ) . '="' . wp_kses_post( $val ) . '"';
        }
    } elseif ( ! empty( $attrs ) ) {
        echo ' ' . wp_kses_post( $attrs );
    }
    ?>><?php echo wp_kses_post( $text ); ?></button>
    <?php
}

/**
 * Normalizes the UTC offset selected by the user
 * 
 * Prepends the offset with a plus (+) sign if the offset is greater than or equal to zero.
 * This is done when the offset is to be output to the browser
 * 
 * @since 3.0
 * 
 * @param string $current_offset The UTC offset
 * @return string The normalized UTC offset
 */
function describr_normalize_selected_utc_offset( $current_offset ) {
    if ( describr_is_saved_data_empty( $current_offset ) ) {
        return '';
    }

    $current_offset = (float) $current_offset;

    if ( 0 <= $current_offset ) {
        $current_offset = '+' . $current_offset;
    } else {
        $current_offset = (string) $current_offset;
    }

    return $current_offset;
}

/**
 * Replaces a UTC offset decimal (relative to 60 seconds) with
 * its colon-followed-by-minute equivalent
 * 
 * Prepends the offset with a plus (+) sign if the offset is greater than or equal to zero.
 * This is done when the offset is to be output to the browser
 * 
 * @since 3.0
 * 
 * @param string $utc_offset The UTC offset
 * @return string The normalized UTC offset
 */
function describr_beautify_utc_offset( $utc_offset ) {
    return str_replace( array( '.25', '.5', '.75' ), array( ':15', ':30', ':45' ), $utc_offset );
}

/**
 * Loads the continent-cities locale
 * 
 * @since 3.0
 * 
 * @param null|string $locale Null or the locale to load the time zones in
 */
function describr_load_locale_continents_cities( $locale = null ) {
    static $mo_loaded = false, $locale_loaded = null;

    //Load translations for continents and cities.
    if ( ! $mo_loaded || $locale !== $locale_loaded ) {
        $locale_loaded = $locale ? $locale : get_locale();
        $mofile        = WP_LANG_DIR . '/continents-cities-' . $locale_loaded . '.mo';
        unload_textdomain( 'continents-cities', true );
        load_textdomain( 'continents-cities', $mofile, $locale_loaded );
        $mo_loaded = true;
    }
}

/**
 * Retrieves a nicely-formatted list of timezone strings
 * 
 * Adapted, in part, from `wp_timezone_choice()`
 * 
 * @since 3.0
 * 
 * @param null|string $locale Null or the locale to load the timezones in
 * @return array
 */
function describr_timezone_choice( $locale = null ) {
    $continents = array( 
        'Africa', 
        'America', 
        'Antarctica', 
        'Arctic', 
        'Asia', 
        'Atlantic', 
        'Australia', 
        'Europe', 
        'Indian', 
        'Pacific',
    );
    
    describr_load_locale_continents_cities( $locale );

    $tz_identifiers = timezone_identifiers_list();
    $zonen          = array();
    $translated_tz  = describr_translated_timezone();

    foreach ( $tz_identifiers as $zone ) {
        $zone = explode( '/', $zone );
        if ( ! in_array( $zone[0], $continents, true ) ) {
            continue;
        }

        // This determines what gets set and translated - we don't translate Etc/* strings here, they are done later.
        $exists    = array(
            0 => ( isset( $zone[0] ) && $zone[0] ),
            1 => ( isset( $zone[1] ) && $zone[1] ),
            2 => ( isset( $zone[2] ) && $zone[2] ),
        );
        $exists[3] = ( $exists[0] && 'Etc' !== $zone[0] );
        $exists[4] = ( $exists[1] && $exists[3] );
        $exists[5] = ( $exists[2] && $exists[3] );
        
        $zonen[] = array(
            'continent'   => ( $exists[0] ? $zone[0] : '' ),
            'city'        => ( $exists[1] ? $zone[1] : '' ),
            'subcity'     => ( $exists[2] ? $zone[2] : '' ),
            't_continent' => ( $exists[3] && isset( $translated_tz['continents'][ $zone[0] ] ) ? $translated_tz['continents'][ $zone[0] ] : '' ),
            't_city'      => ( $exists[4] && isset( $translated_tz['cities'][ $zone[1] ] ) ? $translated_tz['cities'][ $zone[1] ] : '' ),
            't_subcity'   => ( $exists[5] && isset( $translated_tz['subcities'][ $zone[2] ] ) ? $translated_tz['subcities'][ $zone[2] ] : '' ),
        );
    }
    
    /*The comparison function is adapted in whole from `_wp_timezone_choice_usort_callback()`*/
    usort( $zonen, function ( $a, $b ) {
        // Don't use translated versions of Etc.
        if ( 'Etc' === $a['continent'] && 'Etc' === $b['continent'] ) {
            // Make the order of these more like the old dropdown.
            if ( str_starts_with( $a['city'], 'GMT+' ) && str_starts_with( $b['city'], 'GMT+' ) ) {
                return -1 * ( strnatcasecmp( $a['city'], $b['city'] ) );
            }

            if ( 'UTC' === $a['city'] ) {
                if ( str_starts_with( $b['city'], 'GMT+' ) ) {
                    return 1;
                }

                return -1;
            }

            if ( 'UTC' === $b['city'] ) {
                if ( str_starts_with( $a['city'], 'GMT+' ) ) {
                    return -1;
                }

                return 1;
            }

            return strnatcasecmp( $a['city'], $b['city'] );
        }

        if ( $a['t_continent'] === $b['t_continent'] ) {
            if ( $a['t_city'] === $b['t_city'] ) {
                return strnatcasecmp( $a['t_subcity'], $b['t_subcity'] );
            }

            return strnatcasecmp( $a['t_city'], $b['t_city'] );
        } else {
            //Force Etc to the bottom of the list
            if ( 'Etc' === $a['continent'] ) {
                return 1;
            }

            if ( 'Etc' === $b['continent'] ) {
                return -1;
            }

            return strnatcasecmp( $a['t_continent'], $b['t_continent'] );
        }
    });
    
    $structure = array();

    foreach ( $zonen as $key => $zone ) {
        //Build value in an array to join later
        $value = array( $zone['continent'] );

        if ( empty( $zone['city'] ) ) {
            //It's at the continent level (generally won't happen)
            $display = $zone['t_continent'];
        } else {
            //It's inside a continent group

            //Continent optgroup
            if ( ! isset( $zonen[ $key - 1 ] ) || $zonen[ $key - 1 ]['continent'] !== $zone['continent'] ) {
                $label       = $zone['t_continent'];
                $structure[] = '<optgroup label="' . esc_attr( $label ) . '">';
            }

            //Add the city to the value
            $value[] = $zone['city'];

            $display = $zone['t_city'];
            if ( ! empty( $zone['subcity'] ) ) {
                //Add the subcity to the value
                $value[]  = $zone['subcity'];
                $display .= ' - ' . $zone['t_subcity'];
            }
        }

        //Build the value
        $value    = implode( '/', $value );
        $selected = '';
        
        $structure[] = '<option ' . $selected . 'value="' . esc_attr( $value ) . '">' . esc_html( $display ) . '</option>';

        //Close continent optgroup
        if ( ! empty( $zone['city'] ) && ( ! isset( $zonen[ $key + 1 ] ) || ( isset( $zonen[ $key + 1 ] ) && $zonen[ $key + 1 ]['continent'] !== $zone['continent'] ) ) ) {
            $structure[] = '</optgroup>';
        }
    }

    //Do UTC
    $structure[] = '<optgroup label="' . esc_attr__( 'UTC', 'describr' ) . '">';
    $structure[] = '<option value="' . esc_attr( 'UTC' ) . '">' . esc_html__( 'UTC', 'describr' ) . '</option>';
    $structure[] = '</optgroup>';

    //Do manual UTC offsets
    $structure[]  = '<optgroup label="' . esc_attr__( 'Manual Offsets', 'describr' ) . '">';

    $offset_range = array(
        -12,
        -11.5,
        -11,
        -10.5,
        -10,
        -9.5,
        -9,
        -8.5,
        -8,
        -7.5,
        -7,
        -6.5,
        -6,
        -5.5,
        -5,
        -4.5,
        -4,
        -3.5,
        -3,
        -2.5,
        -2,
        -1.5,
        -1,
        -0.5,
        0,
        0.5,
        1,
        1.5,
        2,
        2.5,
        3,
        3.5,
        4,
        4.5,
        5,
        5.5,
        5.75,
        6,
        6.5,
        7,
        7.5,
        8,
        8.5,
        8.75,
        9,
        9.5,
        10,
        10.5,
        11,
        11.5,
        12,
        12.75,
        13,
        13.75,
        14,
    );
    
    $utc_text = /*translators: %s: UTC offset.*/ __( 'UTC%s', 'describr' );
    
    foreach ( $offset_range as $offset ) {
        $offset_name = describr_normalize_selected_utc_offset( $offset );
        
        $offset_value = $offset_name;
        $offset_name  = sprintf( $utc_text, describr_beautify_utc_offset( $offset_name ) );
        $offset_value = 'UTC' . $offset_value;

        $structure[] = '<option value="' . esc_attr( $offset_value ) . '">' . esc_html( $offset_name ) . '</option>';

    }

    $structure[] = '</optgroup>';

    return $structure;
}

/**
 * Retrieves date formats for user-generated dates
 * 
 * @since 3.0
 *
 * @param array An array of date formats
 */
function describr_date_formats() {
    static $date_formats = null;
    
    if ( is_array( $date_formats ) ) {
        return $date_formats;
    }

    $sep = describr_date_sep();

    $formats = array( 'YYYY' );

    $formats[] = $formats[0] . $sep . 'MM';

    $formats[] = $formats[1] . $sep . 'DD';

    /**
     * Filters the date formats for user-generated dates
     * 
     * @since 3.0
     *
     * @param array $date_formats Date formats
     */
    $date_formats = (array) apply_filters( 'describr_user_date_formats', $formats );

    return $date_formats;    
}

/**
 * Retrieves the date-part separator
 * 
 * @since 3.0
 * 
 * @return array
 */
function describr_date_sep() {
    return describr()->date()->format_sep;
}

/**
 * Returns an array of account status descriptions
 * 
 * @since 3.0
 * 
 * @return array
 */
function describr_account_status_labels() {
    /**
     * Filters the account status labels
     * 
     * @since 3.0
     * 
     * @param array Account status description, keyed by account status
     * 
     */
    return (array) apply_filters( 
        'describr_account_status_labels', 
        array(
            'approved' => _x( 'Approved', 'user status', 'describr' ),
            'pending'  => _x( 'Awaiting review', 'user status', 'describr' ),
            'rejected' => _x( 'Rejected', 'user status', 'describr' ),
            'confirm'  => _x( 'Awaiting confirmation', 'user status', 'describr' ),
        )
    );
}

/**
 * Enqueues the toggle-password-button-related styles
 * 
 * @since 3.0
 */
function describr_enqueue_toggle_pwd_button_styles() {
    static $enqueued = false;

    if ( $enqueued ) {
        return;
    }

    $toggle_pwd_button_styles = '
    .describr-hide-pw {
        background: 0 0;
        border: 1px solid transparent;
        box-shadow: none;
        font-size: 1em;
        line-height: 2;
        width: 2.5rem;
        min-width: 40px;
        margin: 0;
        padding: 0 9px;
        position: absolute;
        right: 0;
        top: 0;
    }';

    $hanlde = 'describr-toggle-pw-button-styles';
    wp_register_style( $hanlde, false );
    wp_add_inline_style( $hanlde, $toggle_pwd_button_styles );
    wp_enqueue_style( $hanlde );

    $enqueued = true;
}

/**
 * Deletes transient once per script execution.
 * 
 * @since 3.0
 * 
 * @param string $transient    Transient name.
 * @param bool   $network_wide Whether to delete the transient networkwide. Multisite only.
 */
function describr_delete_transient_once( $transient, $network_wide = true ) {
    static $transients = array();

    if ( ! isset( $transients[ $transient ] ) ) {
        if ( $network_wide && is_multisite() ) {
            foreach ( get_sites() as $site ) {
                switch_to_blog( $site->blog_id );
                delete_transient( $transient );
                restore_current_blog();
            }
        } else {
            delete_transient( $transient );
        }
        
        $transients[ $transient ] = true;
    }
}

/**
 * Retrieves a list of entity numbers keyed by their characters
 * 
 * @since 3.0
 * 
 * @param true $remove_backslash Whether to remove the element keyed 
 *                               by the backlash character (\) from 
 *                               the returned array
 * @return array
 */
function describr_special_chars_entity_numbers( $remove_backslash = true ) {
    /**
     * Filters the special character entities
     * 
     * @since 3.0
     * 
     * @param array $chars Special character entities
     */
    $chars = (array) apply_filters(
        'describr_special_chars_entity_numbers',
        array(
            '.'  => '&#046;',
            ','  => '&#044;',
            "'"  => '&#039;',
            '}'  => '&#0125;',
            '"'  => '&#034;',
            '?'  => '&#063;',
            '>'  => '&#062;',
            ':'  => '&#058;',
            '!'  => '&#033;',
            ';'  => '&#059;',
            ')'  => '&#041;',
            ']'  => '&#093;',
            '{'  => '&#0123;',
            '<'  => '&#060;',
            '['  => '&#091;',
            '|'  => '&#0124;',
            '/'  => '&#047;',
            '\\' => '&#092;',
            '—'  => '&#08212;',
            '('  => '&#040;',
            '`'  => '&#096;',
            '~'  => '&#0126;',
            '#'  => '&#035;',
            '@'  => '&#064;',
            '$'  => '&#036;',
            '%'  => '&#037;',
            '^'  => '&#094;',
            '&'  => '&#038;',
            '*'  => '&#042;',
            '+'  => '&#043;',
            '='  => '&#061;',
            '-'  => '&#08211;',
            '_'  => '&#095;',
        )
    );

    if ( $remove_backslash ) {
        unset($chars['\\']);
    }

    return $chars;
}

/**
 * Sets a form field
 * 
 * @since 3.0
 * 
 * @param string $field The field key
 */
function describr_set_form_field( $field ) {
    describr()->form()->fields[] = $field;
}

/**
 * Retrieves the form fields
 * 
 * @since 3.0
 * 
 * @return array An array of previously set fields
 */
function describr_get_form_fields() {
    return describr()->form()->fields;
}

/**
 * Removes a form field from the set
 * of stored fields
 * 
 * @since 3.0
 * 
 * @param string $field The field key to remove
 */
function describr_remove_form_field( $field ) {
    if ( in_array( $field, describr()->form()->fields, true ) ) {
        describr()->form()->fields = array_diff( describr()->form()->fields, array( $field ) );
    }
}

/**
 * Retrieves the home URL
 *
 * @since 3.0
 *
 * @param int|string $user_id User ID or email
 * @param string     $path    URL's path
 * @return string The home URL
 */
function describr_home_url( $user_id = 0, $path = '/' ) {
    /**
     * Filters the default home URL
     *
     * @since 3.0
     *
     * @param string     $url     The default home URL
     * @param int|string $user_id User ID or email
     */
    return apply_filters( 'describr_home_url', home_url( $path ), $user_id );
}

/**
 * Retrieves the registration URL
 *
 * @since 3.0
 *
 * @param int $user_id  User Id
 * @return string The registration URL
 */
function describr_registration_url( $user_id = 0 ) {
    /**
     * Filters the default registration URL
     *
     * @since 3.0
     *
     * @param string $url     The default registration URL
     * @param int    $user_id User ID
     */
    return apply_filters( 'describr_registration_url', wp_registration_url(), $user_id );
}

/**
 * Retrieves the login URL
 *
 * @since 3.0
 *
 * @param string $redirect Redirect path to redirect to on log in
 * @param int    $user_id  User Id
 * @return string The login URL
 */
function describr_login_url( $redirect = '', $user_id = 0 ) {
    /**
     * Filters the default login URL
     *
     * @since 3.0
     *
     * @param string $url     The default login URL
     * @param int    $user_id User ID
     */
    return apply_filters( 'describr_login_url', wp_login_url( $redirect ), $user_id );
}

/**
 * Retrieves the logout URL
 *
 * @since 3.0
 *
 * @param string $redirect Redirect path to redirect to on log in
 * @param int    $user_id  User Id
 * @return string The logout URL
 */
function describr_logout_url( $redirect = '', $user_id = 0 ) {
    /**
     * Filters the default logout URL
     *
     * @since 3.0
     *
     * @param string $url     The default logout URL
     * @param int    $user_id User ID
     */
    return apply_filters( 'describr_logout_url', wp_logout_url( $redirect ), $user_id );
}

/**
 * Performs a safe redirect on the site's front end
 *
 * @since 3.0
 *
 * @param string $url Redirect URL
 */
function describr_safe_redirect( $url ) {
    describr()->redirect()::safe_redirect( $url );
}

/**
 * Retrieves a URL based on a specific location
 *
 * @since 3.0
 *
 * @param string $location Redirect to location
 * @param int    $user_id  User Id
 * @return string The redirect_to URL
 */
function describr_location_url( $location, $user_id = 0 ) {
    return describr()->redirect()->map_location_to_url( $location, $user_id );
}

/**
 * Retrieves the URL to redirect_to after an action
 *
 * @since 3.0
 *
 * @param string $action  The action
 * @param int    $user_id User ID
 * @return string The redirect_to URL
 */
function describr_redirect_after_url( $action, $user_id = 0 ) {
    $url = describr()->redirect()->redirect_after_url( $action, $user_id );

    if ( ! $user_id ) {
        $user_id = describr()->redirect()::$user_id;
    }

    /**
     * Filters the redirect-to-after URL
     * 
     * The dynamic part of the hook's name is the preceding action
     *
     * @since 3.0
     *
     * @param string      $url The redirect_to URL
     * @param int|WP_User $user_id User ID or WP_User object
     */
    return apply_filters( "describr_redirect_to_after_{$action}_url", $url, $user_id );
}

/**
 * Performs a redirect after a specific action
 *
 * @since 3.0
 *
 * @param string $action  The action
 * @param int    $user_id User Id
 */
function describr_redirect_after( $action, $user_id = 0 ) {
    $url = describr_redirect_after_url( $action, $user_id );

    if ( empty( $url ) ) {
        $url = describr_home_url( $user_id );
    }

    describr_safe_redirect( $url );
}

/**
 * Maybe redirects in the browser if the headers
 * have already been sent, else does so normally
 *
 * @since 3.0
 *
 * @param string $url URL
 */
function describr_maybe_js_redirect( $url ) {
    if ( empty( $url ) ) {
        $url = wp_unslash( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
        $url = set_url_scheme( "//$url" );
    }

    if ( headers_sent() ) {
        register_shutdown_function( function ( $url ) {
            wp_print_inline_script_tag( sprintf( 'window.location.href = %s;', json_encode( $url ) ) );
        }, $url );

        while ( 1 < ob_get_level() ) {
            ob_end_clean();
        }
        
        exit;
    } else {
        wp_redirect( $url );
        exit;
    }
}

/**
 * Outputs a notice.
 * 
 * Adapted from `wp_admin_notice()`
 * 
 * @since 3.0
 * 
 * @param string $message The message to output.
 * @param array  $args    {
 *     An array of arguments for the admin notice
 *     @type string $type               The type of notice. For example, 'error', 'success', 'warning', 'info'.
 *     @type bool   $dismissible        Whether the admin notice is dismissible.  Default false.
 *     @type string $id                 The value of the notice’s ID attribute. Default empty string.
 *     @type array  $additional_classes A string array of class names.
 *     @type array  $attributes         Additional attributes for the notice div.
 *     @type bool   $paragraph_wrap     Whether to wrap the message in paragraph tags. Default true.
 *     @type bool   $strong_wrap        Whether to wrap the message in strong tags. Default true.
 * }
 */
function describr_notice( $message, $args = array() ) {
    /**
     * Fires before a notice is output
     *
     * @since 3.0
     *
     * @param string $message The message for the admin notice
     * @param array  $args    The arguments for the admin notice
     */
    do_action( 'describr_notice', $message, $args );

    echo wp_kses_post( describr_get_notice( $message, $args ) );
}

/**
 * Creates and returns the markup for a notice.
 * 
 * Adapted from `wp_get_admin_notice()`
 * 
 * @since 3.0
 * 
 * @param string $message The message.
 * @param array  $args    {
 *     An array of arguments for the admin notice
 *     @type string $type               The type of notice. For example, 'error', 'success', 'warning', 'info'.
 *     @type bool   $dismissible        Whether the admin notice is dismissible.  Default false.
 *     @type string $id                 The value of the notice’s ID attribute. Default empty string.
 *     @type array  $additional_classes A string array of class names.
 *     @type array  $attributes         Additional attributes for the notice div.
 *     @type bool   $paragraph_wrap     Whether to wrap the message in paragraph tags. Default true.
 *     @type bool   $strong_wrap        Whether to wrap the message in strong tags. Default false.
 * }
 */
function describr_get_notice( $message, $args = array() ) {
    $defaults = array(
        'type'               => '',
        'dismissible'        => false,
        'id'                 => '',
        'additional_classes' => array(),
        'attributes'         => array(),
        'paragraph_wrap'     => true,
        'strong_wrap'        => false,
    );

    $args = wp_parse_args( $args, $defaults );

    /**
     * Filters the arguments for a notice.
     *
     * @since 3.0
     *
     * @param array  $args    The arguments for the notice.
     * @param string $message The message for the notice.
     */
    $args       = apply_filters( 'describr_notice_args', $args, $message );
    $id         = '';
    $classes    = 'describr-notice';
    $attributes = '';

    if ( is_string( $args['id'] ) ) {
        $trimmed_id = trim( $args['id'] );

        if ( '' !== $trimmed_id ) {
            $id = 'id="' . $trimmed_id . '" ';
        }
    }

    if ( is_string( $args['type'] ) ) {
        $type = trim( $args['type'] );

        if ( str_contains( $type, ' ' ) ) {
            _doing_it_wrong(
                __FUNCTION__,
                sprintf(
                    /*translators: %s: Type of notice: error, success, info.*/
                    __( 'The %s key must be a string without spaces.' ),
                    '<code>type</code>'
                ),
                '6.8.1'
            );
        }

        if ( '' !== $type ) {
            $classes .= ' describr-notice-' . $type;
        }
    }

    if ( true === $args['dismissible'] ) {
        $classes .= ' is-dismissible';
    }

    if ( is_array( $args['additional_classes'] ) && ! empty( $args['additional_classes'] ) ) {
        $classes .= ' ' . implode( ' ', $args['additional_classes'] );
    }

    if ( is_array( $args['attributes'] ) && ! empty( $args['attributes'] ) ) {
        $attributes = '';
        foreach ( $args['attributes'] as $attr => $val ) {
            if ( is_bool( $val ) ) {
                $attributes .= $val ? ' ' . $attr : '';
            } elseif ( is_int( $attr ) ) {
                $attributes .= ' ' . esc_attr( trim( $val ) );
            } elseif ( $val ) {
                $attributes .= ' ' . $attr . '="' . esc_attr( trim( $val ) ) . '"';
            }
        }
    }
    
    if ( false !== $args['strong_wrap'] ) {
        $message = "<strong>$message</strong>";
    }

    if ( false !== $args['paragraph_wrap'] ) {
        $message = "<p>$message</p>";
    }

    $markup = sprintf( '<div %1$sclass="%2$s"%3$s>%4$s</div>', $id, $classes, $attributes, $message );

    /**
     * Filters the markup for a notice.
     *
     * @since 3.0
     *
     * @param string $markup  The HTML markup for the notice.
     * @param string $message The message for the notice.
     * @param array  $args    The arguments for the notice.
     */
    return apply_filters( 'describr_notice_markup', $markup, $message, $args );
}

/**
 * Ensures intent by verifying the correct security nonce
 * 
 * @since 3.0
 * 
 * @param string $action    The nonce action
 * @param string $query_arg Key to check for nonce in $_POST
 * @return int|false 1 if the nonce is valid and generated between 0-12 hours ago,
 *         2 if the nonce is valid and generated between 12-24 hours ago.
 *         False if the nonce is invalid
 */
function describr_check_account_action_nonce( $action, $query_arg = 'describr_action_nonce' ) {
    $action .= '_';
    
    if ( isset( $_POST['describr_user'] ) ) {
        $action .= (int) $_POST['describr_user'];
    }

    $result = isset( $_POST[ $query_arg ] ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $query_arg ] ) ), $action ) : false;

    /**
     * Fires once the account request has been validated or not.
     *
     * @since 3.0
     *
     * @param string    $action The nonce action.
     * @param false|int $result False if the nonce is invalid, 1 if the nonce is valid and generated between
     *                          0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
     */
    do_action( 'describr_check_account_action_nonce', $action, $result );

    if ( ! $result ) {
        wp_nonce_ays( $action );
        die();
    }

    return $result;
}

/**
 * Retrieves the profile photo size
 * 
 * @since 3.0
 * 
 * @return int
 */
function describr_photo_size() {
    static $size = null;

    if ( null !== $size ) {
        return $size;
    }

    $size = (int) get_option( 'describr_profile_photosize', 120 );

    return $size;
}

/**
 * Retrieves accessibility text markup
 * 
 * @since 3.0
 * 
 * @param string $text  Accessibility text
 * @param string $space Space
 * @return string
 */
function describr_a11y_text( $text, $space = ' ' ) {
    return '<span class="describr-a11y-text">' . esc_html( $text . $space ) . '</span>';
}

/**
 * Retrieves the field's title made hidden from users using screen readers
 * 
 * @since 3.0
 * 
 * @param string $text  Hidden title
 * @param string $class HTML class attribute's value
 * @param string $tag   Name of HTML tag
 * @return string
 */
function describr_hidden_title( $title, $class = '', $tag = 'div' ) {
    if ( $class ) {
        $class = ' class="' . esc_attr( $class ) . '"';
    }

    return '<' . $tag . $class . ' aria-hidden="true">' . esc_html( $title ) . '</' . $tag . '>';
}

/**
 * Retrieves the profile photo key
 * 
 * @since 3.0
 * 
 * @return string The profile photo key
 */
function describr_photo_key() {
    return describr_get_field_( 'profile_photo', 'name' );
}

/**
 * Prints HTML for displaying fields' admin modals
 * 
 * @since 3.0
 */
function describr_print_field_admin_modal_template( $page ) {
    $user_col = _x( 'User', 'table column name', 'describr' );
    $date_col = _x( 'Date', 'table column name', 'describr' );
    ?>
    <div id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-wrapper" class="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal describr" tabindex="-1">
        <div role="dialog" tabindex="0" aria-labelledby="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-label" id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal">
            <span class="describr-a11y-text" id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-label"></span>
            <div id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-wrap">
                <h1 id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-title"><?php echo esc_html_x( 'Administration', 'header', 'describr' ); ?></h1>
                <button type="button" id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'describr' ); ?>" class="describr-close-modal"><span aria-hidden="true" class="dashicons dashicons-no-alt"></span></button>
                <div id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-main">
                    <p aria-hidden="true" id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-main-field" class="describr-no-overflow"></p>
                    <table role="presentation" class="describr-table">
                        <caption class="describr-a11y-text"></caption>
                        <thead>
                            <tr>
                                <th scope="col"><?php echo esc_html( $user_col ); ?></th>
                                <th scope="col"><?php echo esc_html( $date_col ); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th scope="col"><?php echo esc_html( $user_col ); ?></th>
                                <th scope="col"><?php echo esc_html( $date_col ); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-footer">
                    <div id="describr-<?php echo esc_attr( $page ); ?>-field-audit-modal-footer-cancel-btn-wrap"><?php describr_secondary_button(); ?></div>
                    </div>
                </div>
            </div>
        <div class="describr-modal-backdrop"></div>
    </div>
    <?php
}

/**
 * Retrieves the blog's current locale.
 * 
 * The lang attribute of the "html" tag is
 * used to determine the blog's locale because language 
 * translation plugins are likely to set the
 * translated locale code there.
 * 
 * @since 3.0
 * 
 * @return string The blog's current locale
 */
function describr_get_blog_locale() {
    $blog_lang = get_language_attributes();
        
    if ( preg_match( '/lang\="([^"]+)"/', $blog_lang, $match ) && ! empty( $match[1] ) ) {
        $locale = trim( $match[1] );

        if ( $locale ) {
            $user_locale = str_replace( '-', '_', $locale );
        }
    }
        
    if ( ! isset( $user_locale ) ) {
        $user_locale = get_user_locale();
    }

    return $user_locale;
}

/**
 * Retrieves the "cancel pending email change" notice
 * 
 * @since 3.0
 * 
 * @return string The "cancel pending email change" notice
 */
function describr_cancel_pending_email_change_notice() {
    $current_user = wp_get_current_user();
                    
    $new_email = get_user_meta( $current_user->ID, '_new_email', true );

    if ( $new_email && $new_email['newemail'] !== $current_user->user_email ) {
        $pending_change_message = sprintf(
            /*translators: %s: New email.*/
            __( 'There is a pending change of your email to %s.', 'describr' ),
            '<code>' . esc_html( $new_email['newemail'] ) . '</code>'
        );
        
        $arg = "{$current_user->ID}_new_email";
        
        if ( wp_doing_ajax() ) {
            $url = add_query_arg( 'dismiss', $arg, describr_profile_url( $current_user->user_nicename ) );
        } else {
            $url = add_query_arg( 'dismiss', $arg );
        }
        
        $pending_change_message .= sprintf(
            ' <a href="%1$s">%2$s</a>',
            esc_url( add_query_arg( 'describr_nonce', wp_create_nonce( "dismiss-{$current_user->ID}_new_email" ), $url ) ),
            esc_html__( 'Cancel', 'describr' )
        );
                      
        return describr_get_notice(
            $pending_change_message,
            array(
                'additional_classes' => array( 'updated', 'inline', 'static' ),
            )
        );
    }

    return '';
}

/**
 * Verifies data length
 * 
 * @since 3.0
 * 
 * @param array  $settings Field settings
 * @param string $val      Data
 */
function describr_verify_char_length( $settings, $val ) {
    if ( ! empty( $settings['min_chars'] ) && ! empty( $settings['max_chars'] ) && ( mb_strlen( $val ) < $settings['min_chars'] || mb_strlen( $val ) > $settings['max_chars'] ) ) {
        wp_send_json_error(
            sprintf(
                /*translators: 1: Field label. 2: Minimum number of characters. 3: Maximum number of characters.*/
                __( '%1$s must have at least %2$d characters and at most %3$d characters.', 'describr' ),
                $settings['label'],
                $settings['min_chars'],
                $settings['max_chars']
            )
        );
    } elseif ( ! empty( $settings['min_chars'] ) && mb_strlen( $val ) < $settings['min_chars'] ) {
        wp_send_json_error(
            sprintf(
                /*translators: 1: Field label. 2: Minimum number of characters.*/
                __( '%1$s must have at least %2$d characters.', 'describr' ),
                $settings['label'],
                $settings['min_chars']
            )
        );
    } elseif ( ! empty( $settings['max_chars'] ) && mb_strlen( $val ) > $settings['max_chars'] ) {
        wp_send_json_error(
            sprintf(
                /*translators: 1: Field label. 2: Maximum number of characters.*/
                __( '%1$s cannot have more than %2$d characters.', 'describr' ),
                $settings['label'],
                $settings['max_chars']
            )
        );
    } elseif ( ! empty( $settings['length'] ) && $settings['length'] !== mb_strlen( $val ) ) {
        wp_send_json_error( 
            sprintf( 
                /*translators: 1: Field label. 2: Number of characters.*/  
                __( '%1$s must have %2$d characters.', 'describr' ), 
                $settings['label'],
                $settings['length']
            ) 
        );
    }
}

/**
 * Removes text from paragraph tag
 * 
 * @since 3.0
 * 
 * @param string $string The string to remove from paragraph tag
 * @param string  The passed string removed from paragraph tag 
 * */
function describr_unwrap_p( $string ) {
    if ( preg_match( '/^<p>.*<\/p>$/s', $string ) ) {
        $string = preg_replace( '/^<p>(.*)<\/p>$/s', '$1', $string );
    }
    
    return $string;
}

/**
 * Retrieves an option value for the current network based on name of option.
 * 
 * @since 3.0
 * 
 * @param string $option        Name of the option to retrieve.
 * @param string $default_value Value to return if the option doesn't exist. 
 * @return mixed Value set for the option.
 */
function describr_get_network_option( $option, $default_value = false ) {
    if ( ! is_multisite() ) {
        return get_option( $option, $default_value );
    } else {
        return get_network_option( null, $option, $default_value );
    }
}

/**
 * Adds a new option for the current network.
 * 
 * @since 3.0
 * 
 * @param string $option        Name of the option to add.
 * @param string $default_value Option value, can be anything.  
 * @return bool True if the option was added, false otherwise.
 */
function describr_add_network_option( $option, $value ) {
    if ( ! is_multisite() ) {
        return add_option( $option, $value );
    } else {
        return add_network_option( null, $option, $value );
    }
}

/**
 * Updates the value of an option that was already added for the current network.
 * 
 * @since 3.0
 * 
 * @param string $option        Name of the option.
 * @param string $default_value Option value. 
 * @return True if the value was updated, false otherwise.
 */
function describr_update_network_option( $option, $value ) {
    if ( ! is_multisite()  ) {
        return update_option( $option, $value );
    } else {
        return update_network_option( null, $option, $value );
    }
}