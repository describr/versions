<?php
/**
 * Global filters.
 * 
 * @package Describr
 * @since 3.0
 */

//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Determines whether a user can be shown
 * 
 * @since 3.0
 * 
 * @param bool        $is_viewable Whether the user can be shown
 * @param int|WP_User $user_id     User ID or WP_User object
 * @return bool True if it can be determined that the user can be shown, otherwise false
 */
function describr_is_user_viewable_filter( $is_viewable, $user_id ) {
    if ( ! $is_viewable ) {
        return $is_viewable;
    }

    if ( empty( $user_id ) ) {
        return true;
    }
    
    if ( is_object( $user_id ) ) {
        $user_id = $user_id->ID;
    }

    if ( ! describr_is_member( $user_id ) ) {
        /**
         * Filters whether the nonmember can be shown
         * 
         * @since 3.0
         * 
         * @param bool $is_non_member_viewable Whether nonmember can be shown
         * @param int  $user_id                ID for nonmember
         */
        $is_non_member_viewable = apply_filters( 'describr_is_non-member_viewable', current_user_can( 'edit_users' ), $user_id );//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

        if ( ! $is_non_member_viewable ) {
            return false;
        }
    }
    
    //The user or admin gets special privilege to view the user
    if ( current_user_can( 'edit_user', $user_id ) ) {
        return true;
    }

    $key = 'show_profile';

    $settings = describr_get_field( $key );
    
    $is_viewable = ! ( user_can( $user_id, 'block_user' ) && get_user_meta( $user_id, 'describr_blocked_user_' . get_current_user_id(), true ) );
    
    if ( $is_viewable && empty( $settings['disabled_by_admin'] ) && user_can( $user_id, 'edit_user', $user_id ) ) {        
        $privacy = trim( get_user_meta( $user_id, $key, true ) );
                        
        switch ( $privacy ) {
            case 'public':
                $is_viewable = true;
                break;
            case 'self':
                $is_viewable = describr_is_self( $user_id );
                break;
            case 'members':
                $is_viewable = is_user_logged_in();
                break;
            default:
                $is_viewable = true;
                break;
        }
    }

    return $is_viewable;
}
add_filter( 'describr_is_user_viewable', 'describr_is_user_viewable_filter', 10, 2 );

/**
 * Retrieves whether to determine if a user is logged in
 * 
 * @since 3.0
 * 
 * @param bool $determine_user_logged_in Whether to determine if a user is logged in
 * @param int  $user_id                  User ID 
 * @return bool True if it can be determined that the user is logged in, otherwise false
 */
function describr_determine_user_logged_in( $determine_user_logged_in, $user_id ) {
    if ( ! $determine_user_logged_in || empty( $user_id ) ) {
        return $determine_user_logged_in;
    }
    
    $key = 'show_login';

    $settings = describr_get_field( $key );    
    
    if ( empty( $settings['disabled_by_admin'] ) && user_can( $user_id, 'edit_user', $user_id ) ) {
        $privacy = trim( get_user_meta( $user_id, $key, true ) );

        switch ( $privacy ) {
            case 'public':
                $determine_user_logged_in = true;
                break;
            case 'self':
                $determine_user_logged_in = describr_is_self( $user_id );
                break;
            case 'members':
                $determine_user_logged_in = is_user_logged_in();
                break;
            default:
                $determine_user_logged_in = $determine_user_logged_in;
                break;
        }
    }

    if ( ! $determine_user_logged_in ) {
        $determine_user_logged_in = current_user_can( 'manage_options' );
    }

    return $determine_user_logged_in;
}
add_filter( 'describr_determine_user_logged_in', 'describr_determine_user_logged_in', 10, 2 );

/**
 * Retrieves whether when a user last logged in
 * can be shown
 * 
 * @since 3.0
 * 
 * @param true $can_view_when_logged_in Whether to display when 
 *                                      the user last logged in
 * @param int  $user_id                 ID for the user whose 
 *                                      last-logged-in time might 
 *                                      be shown
 * @return bool True if when the user last logged in can be shown, 
 *              otherwise false
 */
function describr_can_view_when_logged_in_filter( $can_view_when_logged_in, $user_id ) {
    if ( ! $can_view_when_logged_in || empty( $user_id ) ) {
        return $can_view_when_logged_in;
    }

    $key = 'when_last_login_audience';

    $settings = describr_get_field( $key );

    if ( empty( $settings['disabled_by_admin'] ) && user_can( $user_id, 'edit_user', $user_id ) ) {
        $privacy = trim( get_user_meta( $user_id, $key, true ) );
        
        switch ( $privacy ) {
            case 'public':
                $can_view_when_logged_in = true;
                break;
            case 'self':
                $can_view_when_logged_in = describr_is_self( $user_id );
                break;
            case 'members':
                $can_view_when_logged_in = is_user_logged_in();
                break;
            default:
                $can_view_when_logged_in = $can_view_when_logged_in;
                break;
        }
    }
    
    if ( ! $can_view_when_logged_in ) {
        $can_view_when_logged_in = current_user_can( 'manage_options' );
    }

    return $can_view_when_logged_in;
}
add_filter( 'describr_can_view_when_logged_in', 'describr_can_view_when_logged_in_filter', 10, 2 );

/**
 * Retrieves whether when a user registered can be shown
 * 
 * @since 3.0
 * 
 * @param bool $can_view_registered Whether when the user registered can be shown
 * @param int  $user_id             ID for the user whose registered date might be shown
 * @return bool True if when the user registered can be shown, otherwise false
 */
function describr_can_view_registered_filter( $can_view_registered, $user_id ) {
    if ( ! $can_view_registered || empty( $user_id ) ) {
        return $can_view_registered;
    }

    $key = 'show_join_date';

    $settings = describr_get_field( $key );

    if ( empty( $settings['disabled_by_admin'] ) && user_can( $user_id, 'edit_user', $user_id ) ) {
        $privacy = trim( get_user_meta( $user_id, $key, true ) );
        
        switch ( $privacy ) {
            case 'public':
                $can_view_registered = true;
                break;
            case 'self':
                $can_view_registered = describr_is_self( $user_id );
                break;
            case 'members':
                $can_view_registered = is_user_logged_in();
                break;
            default:
                $can_view_registered = $can_view_registered;
                break;
        }
    }
    
    if ( ! $can_view_registered ) {
        $can_view_registered = current_user_can( 'manage_options' );
    }

    return $can_view_registered;
}
add_filter( 'describr_can_view_registered', 'describr_can_view_registered_filter', 10, 2 );

/**
 * Retrieves whether a field is viewable
 * 
 * @since 3.0
 * 
 * @param bool  $is_viewable Whether the field is viewable
 * @param string $field      Field
 * @param int    $user_id    User ID
 * @return bool
 */
function describr_is_field_viewable_filter( $is_viewable, $field, $user_id ) {
    if ( ! $is_viewable ) {
        return $is_viewable;
    }

    $settings = describr_get_field( $field );

    if ( $settings ) {
        /**
         * Filters the viewable field settings
         * 
         * @since 3.0
         * 
         * @param array  $settings Field settings
         * @param string $field    Field
         * @param int    $user_id  User ID
         */
        $settings = apply_filters( 'describr_viewable_field_settings', $settings, $field, $user_id );
        
        if ( ! empty( $settings['account_only'] ) ) {
            $is_viewable = is_user_logged_in() && current_user_can( 'edit_user', $user_id );
        }

        if ( ! empty( $settings['disabled_by_admin'] ) ) {
            $is_viewable = false;
        }

        if ( empty( $settings['public'] ) && ! current_user_can( 'manage_options' ) ) {
            $is_viewable = false;
        }
        
        if ( $is_viewable && empty( $settings['account_only'] ) ) {
            $asides = describr_get_aside( $field );
            
            if ( ! isset( $asides['privacy'] ) ) {
                $can_view = true;
            }

            if ( ! isset( $asides['status'] ) ) {
                $is_approved = true;
            }

            if ( $asides ) {
                $meta_key = isset( $asides['_cat'] ) ? $asides['_cat'] : $field;
                
                unset($asides['_cat']);
                
                foreach ( $asides as $aside => $settings_ ) {
                    if ( isset( $settings_['name'] ) ) {
                        $meta_key_ = "{$meta_key}_{$settings_['name']}";
                        
                        if ( 'privacy' === $aside && user_can( $user_id, 'edit_user', $user_id ) ) {
                            $privacy = trim( get_user_meta( $user_id, $meta_key_, true ) );
                        
                            switch ( $privacy ) {
                                case 'public':
                                    $can_view = true;
                                    break;
                                case 'self':
                                    $can_view = describr_is_self( $user_id );
                                    break;
                                case 'members':
                                    $can_view = is_user_logged_in();
                                    break;
                                default:
                                    $can_view = true;
                                    break;
                            }
                        } elseif ( 'status' === $aside ) {
                            $status = trim( get_user_meta( $user_id, $meta_key_, true ) );
                            
                            switch ( $status ) {
                                case 'yes':
                                    $is_approved = true;
                                    break;
                                case 'no':
                                    $is_approved = describr_is_self( $user_id );
                                    break;
                                default:
                                    $is_approved = true;
                                    break;
                            }
                        }
                    }
                }
            }
        
            if ( ! empty( $can_view ) ) {
                $is_viewable = true;
            }

            if ( empty( $is_approved ) ) {
                $is_viewable = false;
            }
        }
    } else {
        $is_viewable = false;
    }
    
    if ( ! $is_viewable && current_user_can( 'edit_users' ) ) {
        $is_viewable = true;
    }

    if ( $is_viewable && describr()->is_blacklisted_field( $field ) && ! current_user_can( 'manage_options' ) ) {
        $is_viewable = false;
    }

    return $is_viewable;
}
add_filter( 'describr_is_field_viewable', 'describr_is_field_viewable_filter', 10, 3 );

/**
 * Retrieves whether the current user
 * can send message to a user
 * 
 * @since 3.0
 * 
 * @param true $can_send_message Whether the current user can
 *                               send message to a user
 * @param int  $recipient_id     ID for the message recipient
 * @return bool
 */
function describr_can_user_send_message_filter( $can_send_message, $recipient_id ) {
    if ( ! $can_send_message ) {
        return $can_send_message;
    }
    
    if ( $recipient_id === get_current_user_id() || ! describr_is_messaging_enabled() || ! user_can( $recipient_id, 'receive_message' ) ) {
        return false;
    }

    $key = 'receive_message';

    $settings = describr_get_field( $key );
    
    if ( empty( $settings['disabled_by_admin'] ) && user_can( $recipient_id, 'edit_user', $recipient_id ) ) {
        switch ( trim( get_user_meta( $recipient_id, $key, true ) ) ) {
            case 'members':
                $can_send_message = current_user_can( 'send_message' );
                break;
            case 'none':
                $can_send_message = false;
                break;
            case 'public':
            default:
                $can_send_message = is_user_logged_in() ? current_user_can( 'send_message' ) : true;
            break;
        }
    }

    return $can_send_message;
}
add_filter( 'describr_can_user_send_message', 'describr_can_user_send_message_filter', 10, 2 );

/**
 * Retrieves the user's status indicator.
 * 
 * @since 3.0
 * @since 3.1.1 Indicator is only returned if the user's status is not "approved".
 * 
 * @param string $indicator User' status indicator.
 * @param string $status    User's status.
 * @param int    $user_id   ID for the user whose status is being shown.
 * @return string.
 */
function describr_user_status_indicator_filter( $indicator, $status, $user_id ) {
    if ( $indicator ) {
        return $indicator;
    }

    if ( $status && ( 'approved' !== $status && ( current_user_can( 'edit_users' ) || ! describr_is_self( $user_id ) ) ) ) {
        $status_labels = describr_account_status_labels();

        if ( ! empty( $status_labels[ $status ] ) ) {
            $settings = describr_get_field( 'account_status' );
            $icon = ! empty( $settings['icon'] ) ? $settings['icon'] : '';
        
            $icon .= " describr-user-status describr-user-status-$status";
            
            $hidden_text = sprintf( 
                /*translators: Hidden accessibility text. 1: User's display name. 2: User's status.*/
                __( '%1$s is %2$s.', 'describr' ), 
                describr_display_name( $user_id ),
                $status_labels[ $status ] 
            );

            $indicator = '<span class="' . esc_attr( $icon ) . '" title="' . esc_attr( $status_labels[ $status ] ) . '" aria-hidden="true"></span>' .
                '<span class="describr-a11y-text">' .  esc_html( $hidden_text ) . '</span>';
        }
    }

    return $indicator;
}
add_filter( 'describr_user_status_indicator', 'describr_user_status_indicator_filter', 10, 3 );

/**
 * Retrieves the indicator for an important user
 * 
 * @since 3.0
 * 
 * @param string $indicator Indicator for an important user
 * @param int    $user_id   User ID for the potentially
 *                          important user
 * @return string
 */
function describr_important_user_indicator_filter( $indicator, $user_id ) {
    if ( $indicator ) {
        return $indicator;
    }

    if ( describr_is_user_important( $user_id ) ) {
        $settings = describr_get_field( 'important' );
    
        $icon = ! empty( $settings['icon'] ) ? $settings['icon'] : '';

        $indicator = '<span aria-hidden="true" title="' . esc_attr( $settings['title'] ) . '" class="describr-user-important ' . esc_attr( $icon ) . '"></span><span class="describr-a11y-text">' .  esc_html(sprintf(
                    /*translators: Hidden accessibility text. %s: User's display name.*/
                    __( '%s is important.', 'describr' ),
                    describr_display_name( $user_id )
                 )) . '</span>';
    }

    return $indicator;
}
add_filter( 'describr_important_user_indicator', 'describr_important_user_indicator_filter', 10, 2 );

/**
 * Retrieves the indicator for a user who is logged into the site
 * 
 * @since 3.0
 * 
 * @param string $indicator Indicator for a user who is logged into the site
 * @param int    $user_id   ID for the user who might be logged into the site
 * @return string Indicator for a user who is logged into the site
 */
function describr_logged_in_indicator_filter( $indicator, $user_id ) {
    if ( $indicator ) {
        return $indicator;
    }

    if ( describr_is_user_logged_in_anywhere( $user_id ) ) {
        $indicator = '<span aria-hidden="true" title="' . 
                     esc_attr_x( 'Logged In', 'user', 'describr' ) . 
                     '" class="dashicons dashicons-lightbulb describr-user-logged-in"></span>' . 
                      '<span class="describr-a11y-text">' . esc_html(sprintf(
                        /*translators: Hidden accessibility text. %s: User's display name.*/
                        __( '%s is logged in.', 'describr' ),
                        describr_display_name( $user_id )
                        )) .
                     '</span>';
    }

    return $indicator;
}
add_filter( 'describr_logged_in_indicator', 'describr_logged_in_indicator_filter', 10, 2  );

/**
 * Retrieves the canonical URL for the profile page
 * 
 * @since 3.0
 * 
 * @param string  $canonical_url The post's canonical URL
 * @param WP_Post $post          Post object
 * @return string The canonical URL
 */
function describr_get_canonical_url( $canonical_url, $post ) {
    if ( describr_get_page_id( 'profile' ) === $post->ID ) {
        /**
         * Filters whether to override the profile page's canonical URL
         * 
         * @since 3.0
         * 
         * @param bool    $enable_canonical_url Whether to override the profile page's canonical URL
         * @param string  $canonical_url The post's canonical URL
         * @param WP_Post $post          Post object
         */
        if ( apply_filters( 'describr_enable_canonical_url', true, $canonical_url, $post ) ) {
            if ( describr_profile_id() ) {
                $profile_url = describr_profile_url( '', describr_profile_id() );

                if ( ! get_query_var( 'cpage', 0 ) && ! empty( $profile_url ) && $canonical_url !== $profile_url ) {
                    return $profile_url;
                }
            }
        }
    }

    return $canonical_url;
}
add_filter( 'get_canonical_url', 'describr_get_canonical_url', 99, 2 );

/**
 * Returns display name with accompanying avatar wrapped in HTML
 * 
 * @since 3.0
 * 
 * @param string $display_name The display name
 * @param array  $args {
 *         Arguments for how to operate on the display name
 *         @type int    $id                User ID
 *         @type int    $size              The size of the avatar
 *         @type bool   $is_linkable       Whether the display name should be wrapped in 
 *                                         HTML link 
 *         @type bool   $display_name_only Whether to add markup to the display name
 *        
 * } 
 * @return string The display name and avatar wrapped in HTML      
 */
function describr_user_display_name( $display_name, $args = array() ) {
    if ( ! mb_strlen( $display_name ) ) {
        return $display_name;
    }
    
    if ( ! is_array( $args ) ) {
        $args = array();
    }

    $args = wp_parse_args( 
        $args, 
        array(
            'id'                => 0,
            'size'              => wp_is_mobile() ? 32 : 96,
            'is_linkable'       => true,
            'display_name_only' => true,
        ) 
    );
    
    if ( $args['display_name_only'] ) {
        return $display_name;
    }

    $user_id = ! empty( $args['id'] ) ? (int) $args['id'] :  (int) describr_user( 'ID' );
    
    if ( ! $user_id ) {
        return $display_name;
    }

    $avatar  = get_avatar( $user_id, $args['size'], 'mystery', $display_name );

    $url = '';

    $html = '';
        
    if ( $args['is_linkable'] ) {
        $url = describr_profile_url( '', $user_id );
        $url = esc_url( $url );
    }
    
    if ( $avatar ) {
        if ( $url ) {
            $html .= '<a href="' . $url . '" class="describr-img-link describr-floated-avatar">' . $avatar . '</a>';
        } else {
            $html .= '<span class="describr-floated-avatar">' . $avatar . '</span>';
        }
    }
          
    $display_name_wrap = '<span class="describr-display-name';

    if ( $is_self = describr_is_self( $user_id ) ) {
        $display_name_wrap .= ' is-self';
    }
    
    $display_name_wrap .= '">' . esc_html( $display_name ) . '</span>';

    if ( $url ) {
        $display_name_wrap = '<a href="' . $url . '" class="describr-display-name-link">' . $display_name_wrap . '</a>';
    }
    
    if ( $html ) {
        $html .= " $display_name_wrap";
    } else {
        $html = $display_name_wrap;
    }
    
    $verified_indicator = describr_important_user_indicator( $user_id );

    if ( $verified_indicator ) {
        $html .= " $verified_indicator";
    } 

    $status_indicator = describr_user_status_indicator( $user_id );

    if ( $status_indicator ) {
        $html .= " $status_indicator";
    }   

    if ( ! $is_self && describr_logged_in_indicator( $user_id ) ) {
        if ( $avatar ) {
            $html .= '<br />' . describr_logged_in_indicator( $user_id );
        } else {
            $html .= ' ' . describr_logged_in_indicator( $user_id );
        }
    }

    return $html;
}
add_filter( 'describr_user_display_name', 'describr_user_display_name', 0, 2 );

/**
 * Makes required lived_cities, college, and high_school main
 * subfields
 * 
 * Callback for {@see: 'describr_get_field_supports'} filter that is
 * applied in wp-content/plugins/describr/includes/class-config.php
 * 
 * @since 3.0
 * 
 * @param array  $supports Field subfields settings
 * @param string $field    Field
 * @return array The field supports, with required set to true
 */
function describr_add_required_to_supports( $supports, $field ) {
    if ( in_array( $field, array( 'lived_cities' ), true ) && isset( $supports['city'] ) ) {
        $supports['city']['required'] = true;
    } elseif ( in_array( $field, array( 'high_school', 'college' ), true ) && isset( $supports['school'] ) ) {
        $supports['school']['required'] = true;
    }

    return $supports;
}
add_filter( 'describr_get_field_supports', 'describr_add_required_to_supports', 10, 2 );

/**
 * Sets titles and labels for college and high_school main
 * subfields
 * 
 * @since 3.0
 * 
 * @param array  $supports Field subfields settings
 * @param string $field    Field
 * @return array The field supports, with title and label added
 */
function describr_add_college_high_school_title_label( $supports, $field ) {
    if ( in_array( $field, array( 'high_school', 'college' ), true ) && isset( $supports['school'] ) ) {
        if ( 'high_school' === $field ) {
            $supports['school']['title'] = /*translators: Field title.*/ __( 'High School', 'describr' );
            $supports['school']['label'] = /*translators: Field label.*/ __( 'High School', 'describr' );
        } else {
            $supports['school']['title'] = /*translators: Field title.*/ __( 'College', 'describr' );
            $supports['school']['label'] = /*translators: Field label.*/ __( 'College', 'describr' );
        }
    }

    return $supports;
}
add_filter( 'describr_get_field_supports', 'describr_add_college_high_school_title_label', 10, 2 );

/**
 * Returns the current profile subtab
 * 
 * @since 3.0
 * 
 * @return string|false The current subtab or false
 */
function describr_profile_current_subtab() {
    return describr()->profile()->current_subtab;
}
add_filter( 'describr_profile_current_subtab', 'describr_profile_current_subtab', 10, 0 );

/**
 * Adds safe CSS properties for filtering by {@see 'wp_kses*'} functions
 *
 * @since 3.0
 *
 * @param array $properties Safe CSS properties
 * @return array The passed argument, with additional CSS properties added
 */
function describr_kses_safe_style_css( $properties ) {
    $properties[] = 'margin';
    $properties[] = 'margin-top';
    $properties[] = 'padding';
    $properties[] = 'padding-top';
    $properties[] = 'font-family';
    $properties[] = 'display';
    $properties[] = 'position';
    $properties[] = 'box-sizing';
    $properties[] = 'cursor';
    $properties[] = 'white-space';
    $properties[] = 'text-align';
    $properties[] = 'text-decoration';
    $properties[] = 'text-shadow';
    $properties[] = 'border';
    $properties[] = 'border-top';
    $properties[] = 'border-left';
    $properties[] = 'border-radius';
    $properties[] = 'background';
    $properties[] = 'background-color';
    $properties[] = 'font-size';
    $properties[] = 'font-weight';
    $properties[] = 'line-height';
    $properties[] = 'color';
    $properties[] = 'min-height';
    $properties[] = '-webkit-appearance';
    $properties[] = 'border-collapse';
    $properties[] = 'vertical-align';
    $properties[] = 'width';
    $properties[] = 'border-bottom';

    return $properties;
}
add_filter( 'safe_style_css', 'describr_kses_safe_style_css', 10, 1 );

/**
 * Retrieves whether a nicename can be updated
 * 
 * @since 3.0
 * 
 * @param bool $is_locked Whether the nicename can be updated
 * @return bool True if the nicename can be updated, otherwise false
 */
function describr_is_nicename_lock_expired_filter( $is_locked ) {
    return $is_locked && ! current_user_can( 'edit_users' );
}
add_filter( 'describr_is_nicename_lock_expired', 'describr_is_nicename_lock_expired_filter', 10, 1 );

/**
 * Adds social fields to the profile's Contact and Social tab
 * 
 * @since 3.0
 * 
 * @param array $schema The About tab schema
 * @return array
 */
function describr_profile_add_social_fields_to_contact_social_tabs( $schema ) {
    $key = 'contact_and_social';

    if ( isset( $schema[ $key ] ) ) {
        $socials = describr_get_socials();
        
        if ( $socials ) {
            $socials = implode( ',', array_keys( $socials ) );
            
            $contact_and_social = $schema[ $key ];

            if ( $contact_and_social ) {
                $contact_and_social .= ",{$socials}";
            } else {
                $contact_and_social = $socials;
            }

            $schema[ $key ] = $contact_and_social; 
        }
    }

    return $schema;
}
add_filter( 'describr_profile_about_schema', 'describr_profile_add_social_fields_to_contact_social_tabs', 10, 1 );

/**
 * Removes display name, username, and nicename from About if
 * the current user cannot edit the profile, for these fields
 * are also displyed in the profile's header.
 * 
 * @since 3.1.3
 * 
 * @param array $schema The About tab schema.
 * @return array.
 */
function describr_profile_about_schema( $schema ) {    
    if ( isset( $_POST['user_id'] ) && is_user_logged_in() ) {
        $user_id = ( int ) $_POST['user_id'];
    }  

    if ( ! empty( $user_id ) && ! empty( $schema['basic_info'] ) && ! current_user_can( 'edit_user', $user_id ) ) {
        $fields_arr = explode( ',', $schema['basic_info'] );

        foreach ( $fields_arr as $key => $field ) {
            if ( in_array( $field, array( 'display_name', 'user_login', 'user_nicename' ), true ) ) {
                unset( $fields_arr[ $key ] );
            }
        }

        $schema['basic_info'] = implode( ',', $fields_arr );
    }

    return $schema;
}
add_filter( 'describr_profile_about_schema', 'describr_profile_about_schema', 10, 1 );

/**
 * Returns subtab-tailored "No data available" message
 * 
 * @since 3.0
 * 
 * @param string $no_data_available_message "No data available" message
 * @param string $subtab                    Current subtab
 * @return string A subtab-tailored "No data available" message 
 */
function describr_profile_no_data_available_message( $no_data_available_message, $subtab ) {
    if ( 'tagline' === $subtab ) {
        $message = _x( 'No Tagline available.', 'slogan', 'describr' );
    } elseif ( 'basic_info' === $subtab ) {
        $message = /*translators: "Info" is the four-letter abbreviation of "Information".*/ _x( 'No Basic Info available.', 'user', 'describr' );
    } elseif ( 'contact_and_social' === $subtab ) {
        $message = __( 'No Contact and Social Networks available.', 'describr' );
    } elseif ( 'addresses_and_timezone' === $subtab ) {
        $message = __( 'No Addresses and Time Zone available.', 'describr' );
    } elseif ( 'relationship_and_languages' === $subtab ) {
        $message = __( 'No Relationship and Languages available.', 'describr' );
    } elseif ( 'work_and_education' === $subtab ) {
        $message = __( 'No Work and Education available.', 'describr' );
    }
    
    if ( isset( $message ) ) {
        $message .= ' ' . __( 'Please try again later.', 'describr' );
        $no_data_available_message = $message;
    }

    return $no_data_available_message;
}
add_filter( 'describr_profile_no_data_available_message', 'describr_profile_no_data_available_message', 10, 2 );

/**
 * Maybe add action buttons indicator class
 * to the profile summary element.
 * 
 * The 'action-buttons-exist' class adds bottom margin the
 * profile summary element so that both Send Message and Block buttons
 * can be situated in the lower right of the profile header container
 * on desktop
 * 
 * @since 3.0
 *
 * @param string $classes Profile summary classes
 * @param int    $user_id ID for the user to whom the profile belongs
 * @return string
 */
function describr_maybe_add_action_buttons_indicator_class( $classes, $user_id ) {
    if ( describr_can_block_user( $user_id ) || describr_can_send_message() ) {
        $classes .= ' action-buttons-exist';
    }

    return $classes;
}
add_filter( 'describr_profile_summary_class', 'describr_maybe_add_action_buttons_indicator_class', 10, 2 );

/**
 * Adds fields supports
 *
 * @since 3.0
 *
 * @param array $fields Editable fields
 * @return array Editable fields
 */
function describr_add_fields_supports( $fields ) {
    foreach ( $fields as $field => &$settings ) {
        if ( ! empty( $settings['supports'] ) ) {
            $settings['supports_'] = $settings['supports'];
            $settings['supports']  = describr_get_field_supports( $settings['supports'], $field );
        }

        $settings = describr()->remove_cap( $settings );
    }
    
    unset($settings);

    return $fields;
}
add_filter( 'describr_profile_get_edit_fields', 'describr_add_fields_supports', 10, 1 );

/**
 * Adds username to list of editable fields
 *
 * @since 3.0
 *
 * @param array $fields Editable fields
 * @return array Editable fields
 */
function describr_profile_edit_add_username( $fields ) {
    $username_settings = describr_get_field( 'user_login' );

    if ( $username_settings ) {
        $fields['user_login'] = $username_settings;
    }

    return $fields;
}
add_filter( 'describr_profile_get_edit_fields', 'describr_profile_edit_add_username', 10, 1 );

/**
 * Removes profile editable fields capabilities
 *
 * @since 3.0
 *
 * @param array $fields Fields
 * @return array Fields less capabilities
 */
function describr_profile_edit_field_remove_caps( $fields ) {
    foreach ( $fields as $field => &$settings ) {
        $settings = describr()->remove_cap( $settings );
    }
    
    unset($settings);

    return $fields;
}
add_filter( 'describr_profile_get_edit_fields', 'describr_profile_edit_field_remove_caps', 10, 1 );

/**
 * Removes profile editable fields asides
 * 
 * The asides are separately sent to the browser 
 *
 * @since 3.0
 *
 * @param array $fields Fields
 * @return array Fields less asides
 */
function describr_profile_edit_field_remove_asides( $fields ) {
    foreach ( $fields as $field => &$settings ) {
        unset($settings['asides']);
    }
    
    unset($settings);

    return $fields;
}
add_filter( 'describr_profile_get_edit_fields', 'describr_profile_edit_field_remove_asides', 10, 1 );

/**
 * Verfies that the profile picture field is allowed
 * and adds admin details to its settings.
 *
 * @since 3.0
 * @since 3.0.1 Added the avatar's rating to the settings for the
 *              profile picture.
 *
 * @param array $fields Editable fields.
 * @param int   $user_id ID for the user to whom the profile belongs.
 * @return array Editable fields.
 */
function describr_profile_validate_editable_picture( $fields, $user_id ) {
    $key = describr_photo_key();

    if ( isset( $fields[ $key ] ) ) {
        if ( describr_can_take_profile_picture_actions() ) {
            $settings = $fields[ $key ];
            
            $settings['rating'] = describr_avatar_rating( $user_id );
            
            $settings['can_upload'] = describr_can_upload() && did_action( 'wp_enqueue_media' );
        
            $settings['id'] = describr_photo_id( $user_id );

            $fields[ $key ] = $settings;
        } else {
            unset( $fields[ $key ] );
        }
    }
    
    return $fields;
}
add_filter( 'describr_profile_get_edit_fields', 'describr_profile_validate_editable_picture', 10, 2 );

/**
 * Sets the edit profile nicename description
 *
 * @since 3.0
 *
 * @param array $settings nicename settings
 * @return array nicename settings including description
 */
function describr_profile_edit_nicename_description( $settings ) {
    $settings['description'] = sprintf(
                /*translators: 1: URL abbreviation. 2: Site's name.*/
                __( 'This is the part of the %1$s (Uniform Resource Locator) that identifies your %2$s profile.', 'describr' ), 
                '<abbr>URL</abbr>',
                get_bloginfo( 'name', 'display' )
            );

    return $settings;
}
add_filter( 'describr_profile_get_edit_field_user_nicename', 'describr_profile_edit_nicename_description', 10, 1 );

/**
 * Sets the edit profile biography description
 *
 * @since 3.0
 *
 * @param array $settings biography settings
 * @return array Biography settings including description
 */
function describr_profile_edit_description_description( $settings ) {
    $settings['description'] = __( 'Write something about yourself&#8230;', 'describr' );

    return $settings;
}
add_filter( 'describr_profile_get_edit_field_description', 'describr_profile_edit_description_description', 10, 1 );

/**
 * Sets the "confirm email" notice
 *
 * @since 3.0
 *
 * @param array $settings Email settings
 * @param int   $user_id  ID for the user to whom the profile belongs
 * @return array Email settings including the "confirm email" notice
 */
function describr_profile_edit_confirm_email_notice( $settings, $user_id ) {
    if ( get_current_user_id() === $user_id || ( 0 === $user_id && describr()->is_admin() ) ) {
        $settings['description'] = __( 'If you change this, an email will be sent to your new email address to confirm it. <strong>The new email address will not become active until confirmed.</strong>', 'describr' );
    }
    
    return $settings;
}
add_filter( 'describr_profile_get_edit_field_user_email', 'describr_profile_edit_confirm_email_notice', 10, 2 );

/**
 * Sets the edit profile time zone description
 *
 * @since 3.0
 *
 * @param array $settings Time zone settings
 * @return array Time zone settings including description
 */
function describr_profile_edit_timezone_description( $settings ) {
    $settings['description'] = sprintf( 
                /*translators: %s: UTC abbreviation.*/
                __( 'Choose either a city in the same time zone as you or a %s (Coordinated Universal Time) time offset.', 'describr' ), 
                '<abbr>UTC</abbr>',
            );
    return $settings;
}
add_filter( 'describr_profile_get_edit_field_timezone', 'describr_profile_edit_timezone_description', 10, 1 );

/**
 * Sets the edit profile college description
 *
 * @since 3.0
 *
 * @param array $settings College settings
 * @return array College settings including description
 */
function describr_college_description( $settings ) {
    $settings['description'] = __( 'Describe your college experience&#8230;', 'describr' );
    return $settings;
}
add_filter( 'describr_profile_get_edit_field_college_support_description', 'describr_college_description', 10, 1 );

/**
 * Sets the edit profile high school description
 *
 * @since 3.0
 *
 * @param array $settings High school settings
 * @return array High school settings including description
 */
function describr_high_school_description( $settings ) {
    $settings['description'] = __( 'Describe your high school experience&#8230;', 'describr' );
    return $settings;
}
add_filter( 'describr_profile_get_edit_field_high_school_support_description', 'describr_high_school_description', 10, 1 );

foreach ( array( 'birthdate', 'support_from' , 'support_to', 'support_moved', 'support_since' ) as $filter ) {
    add_filter( "describr_profile_get_edit_field_{$filter}", 'describr_date_format_examples', 10, 1 );
}

/**
 * Retrieves fields' saved asides
 *
 * @since 3.0
 *
 * @param array $saved_asides Saved asides
 * @param array $fields       Fields
 * @return array Saved asides
 */
function describr_profile_edit_fields_saved_asides( $saved_asides, $fields ) {
    if ( $fields ) {
        $asides = describr_get_saved_asides( $fields );

        if ( $asides ) {
            $saved_asides += $asides;
        }
    }

    return $saved_asides;
}
add_filter( 'describr_profile_edit_fields_saved_asides', 'describr_profile_edit_fields_saved_asides', 10, 2 );

/**
 * Returns the admin URL as the safe redirect fallback URL
 * if the current user is in admin or can manage options.
 * 
 * It might be called in admin if the user is invalid.
 *
 * @since 3.0
 *
 * @param string $url Fallback URL
 * @param string
 */
function describr_wp_safe_redirect_fallback( $url ) {
    if ( describr()->is_admin() ) {
        return admin_url();
    }
    
    foreach ( array( 'manage_options', 'edit_users', 'edit_others_posts' ) as $cap ) {
        if ( current_user_can( $cap ) ) {
            return admin_url();
        }
    }

    return $url;
}
add_filter( 'describr_wp_safe_redirect_fallback', 'describr_wp_safe_redirect_fallback', 10, 1 );

