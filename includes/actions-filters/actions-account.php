<?php
/**
 * Actions for the front end Account settings.
 * 
 * @package Describr
 * @since 3.0
 */

//Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Outputs the plugin's notices on the Account page
 * 
 * @since 3.0
 */
function describr_account_notices() {
    if ( ! empty( $_GET['updated'] ) ) {
        $_updated = sanitize_text_field( $_GET['updated'] );
        
        //The user will be confirming account deletion at this point so no need to show a success notice
        if ( 'delete' === describr()->account()->action && 'success' === $_updated ) {
            return;
        }
        
        $notices = array();
        
        switch ( $_updated ) {
            case 'new_email':
                $notices[] = array(
                    'message' => __( 'New email address confirmed!', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'updated', 'fade' ),
                        'dismissible'        => true,
                        'strong_wrap'        => true,
                    ), 
                );
                break;
            case 'err_new_email':
                $notices[] = array(
                    'message' => __( 'Sorry, your email address could not be updated. Please try again.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'error' ),
                        'dismissible'        => true,
                        'strong_wrap'        => true,
                    ), 
                );
                break;
            case 'err_edit_user':
                $notices[] = array(
                    'message' => __( 'You do not have persmission to edit this user.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'error' ),
                        'dismissible'        => true,
                        'strong_wrap'        => true,
                    ), 
                );
                break;
            case 'err_fail_del':
                $notices[] = array(
                    'message' => __( 'Sorry, the user could not be deleted. Please try again.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'error' ),
                        'dismissible'        => true,
                    ), 
                );
                break;
            case 'err_no_user':
                $notices[] = array(
                    'message' => __( 'The user is invalid.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'error' ),
                        'dismissible'        => true,
                    ), 
                );
                break;
            case 'err_admin_del':
                $notices[] = array(
                    'message' => __( 'You cannot delete the current user.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'error' ),
                        'dismissible'        => true,
                    ), 
                );
                break; 
            case 'err_no_list_users':
                $notices[] = array(
                    'message' => __( 'You need a higher level of permission. Sorry, you are not allowed to list users.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'error' ),
                        'dismissible'        => true,
                    ), 
                );
                break; 
            case 'err_no_delete_users':
                $notices[] = array(
                    'message' => __( 'You are not allowed to delete users.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'error' ),
                        'dismissible'        => true,
                    ), 
                );
                break;          
            case 'err_no_delete_user':
                $notices[] = array(
                    'message' => __( 'You are not allowed to delete this user.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'error' ),
                        'dismissible'        => true,
                    ), 
                );
                break;
            case 'no_delete_user_front':
                $notices[] = array(
                    'message' => __( 'You need a higher level of permission. Sorry, you are not allowed to delete this user from the site&#039;s front end.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'error' ),
                        'dismissible'        => true,
                    ), 
                );
                break;
            case 'success':
                $notices[] = array(
                    'message' => __( 'Account settings saved.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'updated', 'fade' ),
                        'dismissible'        => true,
                        'strong_wrap'        => true,
                    ), 
                );
                break;
            case 'true':
                $notices[] = array(
                    'message' => __( 'Account settings updated.', 'describr' ),
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'updated', 'fade' ),
                        'dismissible'        => true,
                        'strong_wrap'        => true,
                    ), 
                );
                break;
            case 'users_unblocked':
                $user_count = isset( $_GET['users_count'] ) ? (int) $_GET['users_count'] : 0;
                            
                if ( 1 === $user_count ) {
                    $message = _x( 'User unblocked.', 'allow user access', 'describr' );
                } else {
                    /*translators: %s: Number of users.*/
                    $message = _nx( '%s user unblocked.', '%s users unblocked.', $user_count, 'allow user access', 'describr' );
                }
                $message    = sprintf( $message, number_format_i18n( $user_count ) );
                $notices[] = array(
                    'message' => $message,
                    'args'    => array(
                        'id'                 => 'message',
                        'additional_classes' => array( 'updated' ),
                        'dismissible'        => true,
                    ), 
                );
                break;
            default:
                /**
                 * Fires in the case of a custom account notice
                 * 
                 * @since 3.0
                 * 
                 * @param string $_updated The type of notice
                 */
                do_action( 'describr_account_updated_notice_custom', $_updated );
                break;
        }
        
        /**
         * Filters the of Account page notices
         * 
         * @since 3.0
         * 
         * @param array $notices The Account page notices
         */
        $notices = apply_filters( 'describr_account_updated_notices', $notices );
        
        if ( $notices ) {
            foreach ( $notices as $notice ) {
                if ( isset( $notice['message'] ) && isset( $notice['args'] ) ) {
                    describr_notice( $notice['message'], $notice['args'] );
                }
            }     
        }
    }

    if ( describr()->error()->has_errors() ) {
        describr_notice( 
            __( 'Account settings could not be updated. One or more fields have errors.', 'describr' ), 
            array(
                'id'                 => 'message',
                'additional_classes' => array( 'error' ),
                'dismissible'        => true,
            ) 
        );
    }
}
add_action( 'describr_account_notices', 'describr_account_notices', 10 );

/**
 * Outputs hidden inputs for account form
 *
 * @param array  $args Additional arguments passed to the template
 * @param string $tab The tab
 */
function describr_account_hidden_fields( $args, $tab ) {
    $tab = isset( $args['tab'] ) ? $args['tab'] : $tab;

    $action = describr()->account()->action;

    if ( 'delete' === $action ) {
        $action = 'dodelete';
    }
    
    if ( ! $action ) {
        $action = $tab;
    }
    ?>
    <input type="hidden" name="describr_user" value="<?php echo esc_attr( describr()->account()->user_id ); ?>" />
    <input type="hidden" name="<?php echo esc_attr( describr()->honeypot ); ?>" value="" />
    <input type="hidden" name="describr_action" value="<?php echo esc_attr( $action ); ?>" />

    <?php
}
add_action( 'describr_account_hidden_fields', 'describr_account_hidden_fields', 10, 2 );

/**
 * Retrieves account fields from $_POST and checks the nonce
 * 
 * @since 3.0
 * 
 * @param false|string $action The current action
 */
function describr_submit_account_form( $action ) {  
    describr_check_account_action_nonce( $action );

    $result = false;

    if ( isset( $_POST['describr_fields'] ) && isset( $_POST['describr_nonce'] ) ) {
        $fields = sanitize_text_field( wp_unslash( $_POST['describr_fields'] ) );
        
        $field_nonce = sanitize_text_field( wp_unslash( $_POST['describr_nonce'] ) );
        
        $fields = explode( ',', $fields );
        
        $action_ = md5( wp_json_encode( $fields ) );

        $result = wp_verify_nonce( $field_nonce, $action_ );
    }
    
    $user_key = 'describr_user';

    if ( ! $result || ! in_array( $user_key, $fields, true ) ) {
        wp_die( __( 'Invalid submitted fields.', 'describr' ) );        
    }
    
    $user_id = isset( $_POST[ $user_key ] ) ? (int) $_POST[ $user_key ] : 0;

    if ( ! $user_id ) {
        wp_die( __( 'Invalid user ID.', 'describr' ) );
    }
    
    describr()->form()->post_form[ $user_key ] = $user_id;

    $asides = array();

    foreach ( $fields as $field ) {
        if ( ! isset( describr()->form()->post_form[ $field ] ) ) {
            $settings = describr_get_field( $field );

            if ( empty( $settings['editable'] ) && ! in_array( $field, array( 'confirm_user_pass', 'current_user_pass', 'users' ), true ) ) {
                continue;
            }

            if ( isset( $_POST[ $field ] ) ) {
                describr()->form()->post_form[ $field ] = map_deep( wp_unslash( $_POST[ $field ] ), 'trim' );
            } else {
                describr()->form()->post_form[ $field ] = '0';
            }

            describr_pluck_field_status_and_priv( $field, $user_id, $asides );
        }
    }
    
    describr()->redirect()::$user_id = $user_id;

    describr_set_user_id( $user_id );
    
    if ( $asides ) {
        describr()->form()->asides = $asides;
    }

    unset($asides);
}
add_action( 'describr_submit_account_form', 'describr_submit_account_form', 10, 1 );

/**
 * Validates submitted fields
 * 
 * @since 3.0
 * 
 * @param array $args Form fields
 */
function describr_submit_account_validate( $args ) {
    $user_id = isset( $args['describr_user'] ) ? (int) $args['describr_user'] : 0;
        
    $user = get_userdata( $user_id );

    if ( ! $user ) {
        wp_die( __( 'Invalid user ID.', 'describr' ) );
    }
    
    $current_user = wp_get_current_user();

    $is_self = describr_is_self( $user_id );

    $sendback = remove_query_arg( array( 'msg', 'error_', 'updated' ) );
    
    $action = describr()->account()->action;

    switch ( $action ) {
        case 'blocked_users':
            describr()->account()->current_tab = 'blocked_users';

            if ( ! current_user_can( 'edit_user', $user_id ) ) {
                wp_redirect( add_query_arg( 'updated', 'err_edit_user', $sendback ) );
                exit;
            }

            if ( isset( $args['users'] ) ) {
                $user_ids = array_map( 'intval', (array) $args['users'] );
                $user_ids = array_filter( $user_ids );

                if ( in_array( $user_id, $user_ids, true ) ) {
                    $user_ids = array_diff( $user_ids, array( $user_id ) );
                }
                   
                $user_ids = array_unique( $user_ids );

                if ( $user_ids ) {
                    $meta_keys = array_map( function ( $id ) {
                        return esc_sql( 'describr_blocked_user_' . $id );
                    }, $user_ids );
                    
                    $users_count = count( $meta_keys );

                    global $wpdb;
                        
                    $meta_keys = implode( "','", $meta_keys );

                    if ( $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE user_id = %s AND meta_key IN ('{$meta_keys}')", $user_id ) ) ) {
                        $updated = 'users_unblocked';
                        wp_redirect( add_query_arg( compact( 'users_count', 'updated' ), $sendback ) );
                        exit;
                    }
                }
            }
            wp_redirect( add_query_arg( 'updated', 'success', $sendback ) );
            exit;
            break;
        case 'delete'://Confirm deletion
            describr()->account()->current_tab = 'delete';

            if ( ! describr_can_delete_user( $user_id ) ) {
                wp_redirect( add_query_arg( 'updated', describr()->error()->get_error_code(), $sendback ) );
                exit;
            } 
            
            if ( describr_is_password_required( 'delete' ) ) {
                if ( ! isset( $args['single_user_pass'] ) || 0 === strlen( $args['single_user_pass'] ) ) {
                    wp_redirect( add_query_arg( 'updated', 'single_user_pass_empty', $sendback ) );
                    exit;
                } elseif ( ! wp_check_password( $args['single_user_pass'], $current_user->user_pass, $current_user->ID ) ) {
                    wp_redirect( add_query_arg( 'updated', 'single_user_pass_incorrect', $sendback ) );
                    exit;
                }
            }

            break;
        case 'dodelete'://Delete user
            if ( ! describr_can_delete_user( $user_id ) ) {
                wp_redirect( add_query_arg( 'updated', describr()->error()->get_error_code(), $sendback ) );
                exit;
            }
            
            
            $deleted = describr()->user()->delete( $user_id );

            if ( $deleted ) {
                describr_redirect_after( 'delete' );
            } else {
                wp_redirect( add_query_arg( 'updated', 'err_fail_del', $sendback ) );
            }
            exit;
            break;
        case 'password':
            describr()->account()->current_tab = 'password';
            
            if ( ! current_user_can( 'edit_user', $user_id ) ) {
                wp_redirect( add_query_arg( 'updated', 'err_edit_user', $sendback ) );
                exit;
            }

            if ( empty( $args['user_pass'] ) ) {
                describr()->error()->add( 'user_pass', __( 'Password is required', 'describr' ) );
                return;
            }
                    
            $settings = describr_get_field( 'user_pass' );

            if ( ! empty( $settings['confirm'] ) ) {
                if ( empty( $args['confirm_user_pass'] ) ) {
                    describr()->error()->add( 'confirm_user_pass', __( 'Password confirmation is required', 'describr' ) );
                    return;
                }

                if ( $args['confirm_user_pass'] !== $args['user_pass'] ) {
                    describr()->error()->add( 'user_pass', __( 'Passwords do not match.', 'describr' ) );
                    return;
                }
            }

            //Check for "\" in password
            if ( str_contains( $args['user_pass'], '\\' ) ) {
                describr()->error()->add( 'user_pass', __( 'Password cannot contain the character &#34;&#92;&#34;.', 'describr' ) );
                return;
            }
            
            if ( describr_is_password_required( 'password' ) ) {
                if ( ! isset( $args['current_user_pass'] ) || 0 === strlen( $args['current_user_pass'] ) ) {
                    describr()->error()->add( 'current_user_pass', __( 'Your current password is required.', 'describr' ) );
                    return;
                } elseif ( ! wp_check_password( $args['current_user_pass'], $current_user->user_pass, $current_user->ID ) ) {
                    describr()->error()->add( 'current_user_pass', __( 'Your current password is incorrect.', 'describr' ) );
                    return;
                }
            }

            if ( wp_check_password( $args['user_pass'], $current_user->user_pass, $user_id ) ) {
                describr()->error()->add( 'user_pass', __( 'Your new password must be different from your current password.', 'describr' ) );
                return;
            }
            
            //According to `wp_check_password()`, passwords longer than 4096 are not supported
            if ( 4096 < strlen( $args['user_pass'] ) ) {
                describr()->error()->add( 'user_pass', __( 'Your password cannot contain more than 4096 characters.', 'describr' ) );
                return;
            }

            if ( ! empty( $settings['strong'] ) ) {
                $min_chars = ! empty( $settings['min_chars'] ) ? $settings['min_chars'] : 6;
                $max_chars = ! empty( $settings['max_chars'] ) ? $settings['max_chars'] : 127;
                
                $pwd_length = mb_strlen( $args['user_pass'] );

                if ( $pwd_length < $min_chars ) {
                    $errMsg = sprintf(
                        /*translators: %s Minimum number of characters.*/
                        __( 'Your password must contain at least %s characters.', 'describr' ),
                        $min_chars
                    );
                    describr()->error()->add( 'user_pass', $errMsg );
                }

                if ( $pwd_length > $max_chars ) {
                    $errMsg = sprintf(
                        /*translators: %s Maximum number of characters.*/
                        __( 'Your password cannot contain more than %s characters.', 'describr' ),
                        $max_chars
                    );
                    describr()->error()->add( 'user_pass', $errMsg );
                }

                $user_logins = array( $user->user_login );

                if ( ! $is_self ) {
                    $user_logins[] = wp_get_current_user()->user_login;
                }

                        
                foreach ( $user_logins as $user_login ) {
                    if ( false !== mb_stripos( $args['user_pass'], $user_login ) ) {
                        describr()->error()->add( 'user_pass', __( 'Your password cannot contain your username.', 'describr' ) );
                        break;
                    }
                }

                $user_emails = array( $user->user_email );
                
                if ( ! $is_self ) {
                    $user_emails[] = $current_user->user_email;
                }

                foreach ( $user_emails as $user_email ) {
                    if ( false !== mb_stripos( $args['user_pass'], $user_email ) ) {
                        describr()->error()->add( 'user_pass', __( 'Your password cannot contain your email address.', 'describr' ) );
                        break;
                    }
                }

                if ( ! describr_is_strong_pass( $args['user_pass'] ) ) {
                    $special_chars = describr_special_chars_entity_numbers();
                    
                    if ( $special_chars ) {
                        $errMsg = sprintf(
                            /*translators: %s: Punctation marks.*/
                            __( 'Your password must contain at least one lowercase letter, one uppercase letter, one number, and one character like %s.', 'describr' ),
                            '<code>' . implode( ' ', $special_chars ) . '</code>'
                        );

                        describr()->error()->add( 'user_pass', $errMsg );
                    } else {
                        describr()->error()->add( 'user_pass', __( 'Your password must contain at least one lowercase letter, one uppercase letter, and one number.', 'describr' ) );
                    }
                }

                describr_check_identifier_in_pass( $args['user_pass'], $user, describr()->error() );
            }
            break;
        case 'general':
        case 'account':
            describr()->account()->current_tab = 'general';
            
            if ( ! current_user_can( 'edit_user', $user_id ) ) {
                wp_redirect( add_query_arg( 'updated', 'err_edit_user', $sendback ) );
                exit;
            }

            $fields = array( 
                'first_name', 
                'last_name', 
                'nickname',
                'display_name', 
                'user_nicename',
                'user_email',
            );
            
            $settings = describr_get_field( $fields );

            foreach ( $fields as $field ) {
                if ( isset( $args[ $field ] ) ) {
                    $new_value = $args[ $field ];

                    if ( 'user_nicename' === $field ) {
                        $new_value = sanitize_user( $new_value, true );
                        $new_value = sanitize_title( $new_value );
                    }

                    $filter = $field;

                    if ( ! str_contains( $filter, 'user' ) ) {
                        $filter = 'user_' . $filter;
                    }
                    
                    /**This filter is documented in wp-includes/user.php*/
                    $new_value = apply_filters( "pre_{$filter}", $new_value );

                    $label = isset( $settings[ $field ]['label'] ) ? $settings[ $field ]['label'] : '';
                    
                    if ( empty( $new_value ) && ! empty( $settings[ $field ]['required'] ) ) {
                        /*translators: %s: Field label.*/
                        describr()->error()->add( $field, sprintf( __( '%s is required.', 'describr' ), $label ) );
                    }
                    
                    if ( ! empty( $settings[ $field ]['min_chars'] ) && ! empty( $settings[ $field ]['max_chars'] ) && ( mb_strlen( $new_value ) < $settings[ $field ]['min_chars'] || mb_strlen( $new_value ) > $settings[ $field ]['max_chars'] ) ) {
                        /*translators: 1: Field label. 2: Minimum number of characters. 3: Maximum number of characters.*/
                        $msg = __( '%1$s must contain at least %2$d characters and contain at most %3$d characters.', 'describr' );
                        $msg = sprintf( $msg, $label, $settings[ $field ]['min_chars'], $settings[ $field ]['max_chars'] );
                        describr()->error()->add( $field, $msg );
                    } elseif ( ! empty( $settings[ $field ]['min_chars'] ) && mb_strlen( $new_value ) < $settings[ $field ]['min_chars'] ) {
                        /*translators: 1: Field label. 2: Minimum number of characters.*/
                        $msg = __( '%1$s must contain at least %2$d characters.', 'describr' );
                        $msg = sprintf( $msg, $label, $settings[ $field ]['min_chars'] );
                        describr()->error()->add( $field, $msg );
                    } elseif ( ! empty( $settings[ $field ]['max_chars'] ) && mb_strlen( $new_value ) > $settings[ $field ]['max_chars'] ) {
                        /*translators: 1: Field label. 2: Maximum number of characters for the field.*/
                        $msg = __( '%1$s cannot contain more than %2$d characters.', 'describr' );
                        $msg = sprintf( $msg, $label, $settings[ $field ]['max_chars'] );
                        describr()->error()->add( $field, $msg );
                    } elseif ( ! empty( $settings[ $field ]['length'] ) && $settings[ $field ]['length'] !== mb_strlen( $new_value ) ) {
                        /*translators: 1: Field label. 2: Field length.*/ 
                        $msg = __( '%1$s must contain %2$d characters.', 'describr' );
                        $msg = sprintf( $msg, $label, $settings['length'] );
                        describr()->error()->add( $field, $msg );
                    }

                    if ( 'user_nicename' === $field && $user->user_nicename !== $new_value ) {
                        if ( describr_is_nicename_locked( $user_id ) ) {
                            describr()->error()->add( $field, describr_nicename_locked_notice( $user_id ) );
                        } elseif ( ! isset( describr()->error()->errors[ $field ] ) ) {
                            global $wpdb;
                                
                            if ( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND ID != %s LIMIT 1", $new_value, $user_id ) ) ) {
                                describr()->error()->add( 
                                    $field, 
                                    sprintf( 
                                        /*translators: 1: Field label. 2: User's nicename.*/
                                        __( '%1$s %2$s is already taken.', 'describr' ), 
                                        $label,
                                        '<strong>' . esc_html( $val ) . '</strong>' 
                                    ) 
                                );
                            }
                        }
                    }

                    if ( describr()->error()->has_errors() ) {
                        return;
                    }
                    
                    if ( 'user_email' === $field ) {
                        if ( ! is_email( $new_value ) ) {
                            describr()->error()->add( $field, __( 'This is not a valid email address.', 'describr' ) );
                        } elseif ( 0 !== strcasecmp( $new_value, $user->user_email ) && email_exists( $new_value ) ) {
                            if ( $is_self ) {
                                delete_user_meta( $user_id, '_new_email' );
                            }
                            
                            describr()->error()->add( $field, __( 'This email address already exists.', 'describr' ) );
                        }
                    }
                }
            }
            
            if ( describr_is_password_required( 'general' ) ) {
                if ( ! isset( $args['single_user_pass'] ) || 0 === strlen( $args['single_user_pass'] ) ) {
                    describr()->error()->add( 'single_user_pass', __( 'Your password is required.', 'describr' ) );
                } elseif ( ! wp_check_password( $args['single_user_pass'], $current_user->user_pass, $current_user->ID ) ) {
                    describr()->error()->add( 'single_user_pass', __( 'Your password is incorrect.', 'describr' ) );
                }
            }
            break;
        default:
            $tab = $action;
            /**
             * Fires before the account user meta are saved
             * 
             * @since 3.0
             * 
             * @param array $args $_POST
             */
            do_action( "describr_submit_account_tab_{$tab}_validate", $args );
            break;
    }
}
add_action( 'describr_submit_account_validate', 'describr_submit_account_validate', 10, 1 );

/**
 * Validates the Account Privacy tab fields
 * 
 * @since 3.0
 * 
 * @param array $args Form fields
 */
function describr_submit_account_tab_privacy_validate( $args ) {
    if ( ! isset( $args['describr_user'] ) || ! current_user_can( 'edit_user', (int) $args['describr_user'] ) ) {
        $goto = remove_query_arg( array( 'msg', 'error_' ) );
        wp_redirect( add_query_arg( 'updated', 'err_edit_user', $goto ) );
        exit;
    }

    foreach ( array( 
        'show_join_date', 
        'when_last_login_audience', 
        'show_login', 
        'show_profile',
        'receive_message',
    ) as $field ) {
        if ( isset( $args[ $field ] ) ) {
            $val = $args[ $field ];
            $options = describr_settings_options( $field, describr_get_field( $field ) );
                 
            if ( $options && ! describr_is_option( $val, $options ) ) {
                describr()->error()->add( $field, __( 'This value is invalid.', 'describr' ) );
            }
        }
    }
    
    if ( describr_is_password_required( 'privacy' ) ) {
        $current_user = wp_get_current_user();

        if ( ! isset( $args['single_user_pass'] ) || 0 === strlen( $args['single_user_pass'] ) ) {
            describr()->error()->add( 'single_user_pass', __( 'Your password is required.', 'describr' ) );
        } elseif ( ! wp_check_password( $args['single_user_pass'], $current_user->user_pass, $current_user->ID ) ) {
            describr()->error()->add( 'single_user_pass', __( 'Your password is incorrect.', 'describr' ) );
        }
    }
}
add_action( 'describr_submit_account_tab_privacy_validate', 'describr_submit_account_tab_privacy_validate', 10, 1 );

/**
 * Updates the user
 * 
 * @since 3.0
 * 
 * @param array $args Form fields
 */
function describr_submit_account_update( $args ) {
    //Fall through to Confirm Deletion
    if ( 'delete' === describr()->account()->action ) {
        return;
    }

    $user_id = isset( $args['describr_user'] ) ? (int) $args['describr_user'] : 0;
    
    $user = get_userdata( $user_id );

    if ( ! $user ) {
        wp_die( __( 'Invalid user ID.', 'describr' ) );
    }
    
    $goto = remove_query_arg( array( 'updated', 'msg', 'error_' ) );

    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        wp_redirect( add_query_arg( 'updated', 'err_edit_user', $goto ) );
        exit;
    }

    $privacy = array( 
        'show_join_date', 
        'when_last_login_audience', 
        'show_login', 
        'show_profile',
        'receive_message',
    );

    $checkboxes = array( 
        'logout_everywhere', 
        'receive_notifications',
    );
    
    //Not updating username, in keeping with WordPress' default behavior
    $personal_info = array( 
        'first_name', 
        'last_name', 
        'nickname',
        'display_name', 
        'user_nicename',
        'user_email',
        'user_pass',
    );

    $custom_meta = array();

    $userdata = array();

    foreach ( $checkboxes as $checkbox ) {
        if ( isset( $args[ $checkbox ] ) ) {
            $_v = absint( $args[ $checkbox ] );

            if ( $user->$checkbox !== $_v ) {
                $custom_meta[ $checkbox ] = $_v;
            }
        }
    }

    foreach( $privacy as $k ) {
        if ( isset( $args[ $k ] ) ) {
            $v = sanitize_text_field( $args[ $k ] );

            if ( ! empty( $v ) && $user->$k !== $v  ) {
                $custom_meta[ $k ] = $v;
            }            
        }
    }
    
    if ( $custom_meta ) {
        $userdata['meta_input'] = $custom_meta;
    }
    
    foreach ( $personal_info as $k ) {
        if ( ! empty( $args[ $k ] ) ) {
            $new_value = $args[ $k ];

            if ( 'user_nicename' === $k ) {
                $new_value = sanitize_user( $new_value, true );
                $new_value = sanitize_title( $new_value );
            }
            
            if ( 'user_pass' !== $k ) {
                $filter = $k;

                if ( ! str_contains( $filter, 'user' ) ) {
                    $filter = 'user_' . $filter;
                }
                    
                /**This filter is documented in wp-includes/user.php*/
                $new_value = apply_filters( "pre_{$filter}", $new_value );
            }
            

            if ( 'user_email' === $k ) {
                if ( 0 !== strcasecmp( $new_value, $user->user_email ) ) {
                    $userdata[ $k ] = $args[ $k ];
                }
            } elseif ( 'user_pass' === $k ) {
                $userdata[ $k ] = $args[ $k ];
            } elseif ( $user->$k !== $new_value ) {
                $userdata[ $k ] = $args[ $k ];
            }
        }
    }
    
    /**
     * Fires before the user is updated
     * 
     * @since 3.0
     * 
     * @param array $userdata An array of user data
     * @param int   $user_id  User ID
     */
    do_action( 'describr_account_before_update', $userdata, $user_id );

    if ( isset( $userdata['user_email'] ) && $user_id === get_current_user_id() ) {
        $email_ = $userdata['user_email'];

        unset( $userdata['user_email'] );
        
        /**This filter is documented in wp-includes/user.php*/
        $email = apply_filters( 'pre_user_email', $email_ );

        $hash = md5( $email . time() . wp_rand() );

        $new_user_email = array(
            'hash'     => $hash,
            'newemail' => $email,
        );

        if ( update_user_meta( $user_id, '_new_email', $new_user_email ) ) {
            describr()->user()->send_confirmation_on_profile_email_change( $user_id, $email, $hash );
        }
    }
    
    $updated = false;

    if ( $userdata ) {
        $userdata = add_magic_quotes( $userdata );
        $userdata['ID'] = $user_id;
        $updated = wp_update_user( $userdata );
    }
    
    if ( isset( $userdata['meta_input'] ) ) {
        $userdata = $userdata + $userdata['meta_input'];

        unset( $userdata['meta_input'] );
    }
    
    if ( isset( $email_ ) ) {
        $userdata['user_email'] = $email_;
    }
    
    unset( $userdata['ID'] );

    /**
     * Fires after the user is updated
     * 
     * @since 3.0
     * 
     * @param array $userdata    An array of user data
     * @param int   $user_id     User ID
     * @param false|int|WP_Error $updated False if fields are available for update,
     *                                    The user ID if the user was updated, or
     *                                    WP_Error object if the user could not be updated
     */
    do_action( 'describr_account_after_update', $userdata, $user_id, $updated );
    
    /*This filter is documented in wp-content/plugins/describr/includes/describr-functions.php*/
    if ( is_int( $updated ) && isset( $userdata['user_nicename'] ) && apply_filters( 'describr_time_must_elapse_before_nicename_can_change', true, $user_id ) ) {
        update_user_meta( $user_id, 'describr_nicename_updated', time() );
    }
    
    if ( is_int( $updated ) ) {        
        if ( $userdata ) {
            array_map( 'describr_auditing_fields', array_keys( $userdata ) );
        }

        if ( ! empty( $userdata['receive_notifications'] ) ) {
            $unsubscribed_list = describr_get_network_option( 'describr_unsubscribed_from_email_notif_emails', array() );

            if ( $unsubscribed_list && in_array( $user->user_email, $unsubscribed_list, true ) ) {
                $unsubscribed_list = array_diff( $unsubscribed_list, array( $user->user_email ) );

                describr_update_network_option( 'describr_unsubscribed_from_email_notif_emails', $unsubscribed_list );
            }
        }
    }
    
    if ( describr()->form()->asides ) {
        /**
         * Filters the Account fields asides values
         * 
         * @since 3.0
         * 
         * @param array $asides  Field asides  
         * @param int   $user_id User ID
         */
        $asides = apply_filters( 'describr_account_asides_update', describr()->form()->asides, $user_id );
        
        foreach ( $asides as $field => $meta ) {
            describr_update_field_status_and_priv( $meta, $field, $user_id );
        }
    }

    describr_audit_fields( $user_id );
    
    $args = array();
    
    if ( is_wp_error( $updated ) ) {
        $args['error_'] = $updated->get_error_code();
        $args['msg'] = rawurlencode( $updated->get_error_message() );
    } else {
        $args['updated'] = 'success';
    }

    describr_maybe_js_redirect( add_query_arg( $args, $goto ) );
}
add_action( 'describr_submit_account_update', 'describr_submit_account_update', 10, 1 );

/**
 * Outputs the opening fieldset tag and legend tags
 * 
 * @since 3.0
 *
 * @param string $tab      The tab
 * @param array  $settings The tab settings
 */
function describr_account_fieldset_opening_tag( $tab, $settings ) {
    if ( ! empty( $settings['legend'] ) ) {
        echo '<fieldset><legend class="describr-a11y-text">' . esc_html( $settings['legend'] ) . '</legend>';
    }
}
add_action( 'describr_account_fieldset_opening_tag', 'describr_account_fieldset_opening_tag', 10, 2 );

/**
 * Outputs the closing fieldset tag
 * 
 * @since 3.0
 *
 * @param string $tab      The tab
 * @param array  $settings The tab settings
 */
function describr_account_fieldset_closing_tag( $tab, $settings ) {
    if ( ! empty( $settings['legend'] ) ) {
        echo '</fieldset>';
    }
}
add_action( 'describr_account_fieldset_closing_tag', 'describr_account_fieldset_closing_tag', 10, 2 );

/**
 * Adds inline scripts for the Account page
 * 
 * @since 3.0
 */
function describr_account_print_scripts() {
    $strings = array( 
        'dismiss' =>  __( 'Dismiss this notice.', 'describr' ),
        'required' => /*translators: %s: Field label.*/ __( '%s is required.', 'describr' ),
    );

    if ( describr()->fields()->audits ) {
        $audits = describr()->fields()->audits;
        
        array_walk( $audits, function ( &$value, $key ) {
            if ( isset( $value['log'] ) ) {
                usort( $value['log'], function( $a, $b ) {
                    return (int) $b['time'] - (int) $a['time'];
                });
                
                $value['log'] = array_map( function ( $log ) {
                    $user_id = $log['user_id'];
                    $time    = (int) $log['time'];

                    $user = get_userdata( $user_id );
                    
                    if ( isset( $user->display_name ) ) {
                        $display_name = $user->display_name;
                    } else {
                        $display_name = _x( 'Anonymous', 'user', 'describr' );
                    }
                    
                    $date = describr_date( describr_locale_date_format(), $time, wp_get_current_user()->timezone );

                    if ( empty( $date ) ) {
                        $date = '<span aria-hidden="true">—</span><span class="describr-a11y-text">';
                        /*translators: Hidden accessibility text.*/
                        $date .= _x( 'Unknown', 'date', 'describr' );
                        $date .= '</span>';
                    }
                    
                    unset( $log['user_id'], $log['time'] );

                    $log['displayName'] = $display_name;
                    $log['date'] = $date;
                    return $log;
                }, $value['log'] );

                $value['log'] = array_filter( $value['log'] );

                if ( ! $value['log'] ) {
                    $value = array();
                }
            } else {
                $value = array();
            }
        });

        $audits = array_filter( $audits );

        if ( $audits ) {
            wp_add_inline_script( 'describr-account', sprintf( 'describr.fieldLogs=%s;', wp_json_encode( $audits ) ), 'before' );
            $strings['modalLabel'] = /*translators: Hidden accessibility text. %s: Field label.*/ __( '%s administration', 'describr' );
            $strings['modalTableCaption'] = /*translators: Hidden accessibility text. %s: Field label.*/ __( 'Table Ordered by Administration Date for %s. Descending.', 'describr' );
        }

        describr()->fields()->audits = $audits;
    }
    
    $current_tab = describr()->account()->current_tab;

    $tab_titles = array();

    foreach ( array_keys( describr()->account()->tabs ) as $tab ) {
        describr()->account()->current_tab = $tab;

        $tab_titles[ $tab ] = wp_get_document_title();
    }

    describr()->account()->current_tab = $current_tab;

    wp_add_inline_script( 'describr-account', sprintf( 'describr.tabTitles=%s;', wp_json_encode( $tab_titles ) ), 'before' );
    wp_add_inline_script( 'describr-account', sprintf( 'describr.currentTab=%s;', wp_json_encode( $current_tab ) ), 'before' );       
    wp_localize_script( 'describr-account', 'describrL10n', $strings );
}
add_action( 'wp_footer', 'describr_account_print_scripts', 0 );

/**
 * Prints Account fields audit modal
 * 
 * @since 3.0
 */
function describr_print_account_audit_modal() {
    if ( describr()->fields()->audits ) {
        describr_print_field_admin_modal_template( 'account' );
    }
}
add_action( 'wp_footer', 'describr_print_account_audit_modal' );
