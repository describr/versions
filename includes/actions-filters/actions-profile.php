<?php
/**
 * Actions for the front end Profile.
 * 
 * @package Describr
 * @since 3.0
 */

//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates ajax request for the profile tabs HTML
 * 
 * @since 3.0
 * 
 * @param Profile $profile The \describr\Profile instance
 */
function describr_authenticate_profile_schema_ajax_request( $profile ) {
    $user_id = (int) $_POST['user_id'];
      
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_structure_nonce'] ) ), 'describr-profile-schema_' . $user_id ) ) {
        wp_send_json_error( __( 'The link you followed has expired.', 'describr' ) );
    }
                   
    if ( ! get_option( 'describr_enable_profile_menu' ) ) {
        wp_send_json_error( __( 'The requested information was not found.', 'describr' ) );
    } 
    
    $user = get_userdata( $user_id );

    if ( ! $user ) {
        wp_send_json_error( __( 'Invalid user ID.', 'describr' ) );
    }

    if ( ! describr_is_user_viewable( $user->ID ) ) {
        wp_send_json_error( __( 'Sorry, this profile is not available.', 'describr' ) );
    }
    
    set_query_var( 'describr_tab', sanitize_text_field( wp_unslash( $_POST['active_tab'] ) ) );
    set_query_var( 'describr_user', $user->user_nicename );
    
    if ( isset( $_POST['active_subtab'] ) ) {
        set_query_var( 'describr_subtab', sanitize_text_field( wp_unslash( $_POST['active_subtab'] ) ) );
    }
    
    if ( isset( $_POST['paged'] ) ) {
        $profile->paged = (int) $_POST['paged'];
    }

    $profile->ID = $user->ID;

    if ( current_user_can( 'edit_user', $user->ID ) ) {
        $profile->filter = 'edit';
    }
}
add_action( 'describr_authenticate_profile_schema_ajax_request', 'describr_authenticate_profile_schema_ajax_request', 10, 1 );

/**
 * Authenticates profile changes request
 * 
 * @since 3.0
 * 
 * @param Profile $profile The \describr\Profile instance
 */
function describr_profile_authenticate_field_changes( $profile ) {
    if ( strlen( $_POST[ describr()->honeypot ] ) ) {
        wp_send_json_error( _x( 'It seems as if this request is not made by a human being.', 'error message', 'describr' ) );
    }

    $user = get_userdata( (int) $_POST['user_id'] );

    if ( $user ) {
        if ( ! current_user_can( 'edit_user', $user->ID ) ) {
            $user = false;
        } elseif ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_nonce'] ) ), 'describr-edit-profile_' . $user->ID ) ) {
            $user = false;
        }
    }
    
    if ( ! $user ) {
        wp_send_json_error( __( 'You do not have permission to edit this user.', 'describr' ) );
    }
    
    if ( isset( $_POST['active_tab'] ) ) {
        set_query_var( 'describr_tab', sanitize_text_field( wp_unslash( $_POST['active_tab'] ) ) );
    }

    if ( isset( $_POST['active_subtab'] ) ) {
        set_query_var( 'describr_subtab', sanitize_text_field( wp_unslash( $_POST['active_subtab'] ) ) );
    }

    set_query_var( 'describr_user', $user->user_nicename );

    $profile->filter = 'edit';
    $profile->ID = $user->ID;
}
add_action( 'describr_profile_authenticate_field_changes', 'describr_profile_authenticate_field_changes', 10, 1 );

/**
 * Retrieves profile $_POST fields
 * 
 * @since 3.0
 * 
 * @param Profile $profile The \describr\Profile instance
 */
function describr_profile_pluck_submitted_fields( $profile ) {
    foreach ( describr_editable_fields() as $field => $settings ) {
        /*WordPress' media JS API is used to upload profile photo*/
        if ( isset( $settings['type'] ) && 'image' === $settings['type'] ) {
            continue;
        }
        
        if ( isset( $_POST['fields'][ $field ] ) ) {
            $profile->request[ $field ] = wp_unslash( $_POST['fields'][ $field ] );
        } elseif ( isset( $_POST[ $field ] ) ) {
            $profile->request[ $field ] = wp_unslash( $_POST[ $field ] );
        }
    }

    if ( ! $profile->request ) {
        wp_send_json_error( __( 'No fields submitted.', 'describr' ) );
    }
    
    $profile->request = map_deep( $profile->request, 'trim' );
}
add_action( 'describr_profile_pluck_submitted_fields', 'describr_profile_pluck_submitted_fields', 10, 1 );

/**
 * Deletes profile fields
 * 
 * @since 3.0
 * 
 * @param Profile $profile The \describr\Profile instance
 */
function describr_profile_delete_fields( $profile ) {
    $user = get_userdata( $profile->ID );

    foreach ( $profile->request as $field => $val ) {
        $settings = describr_get_field( $field );

        if ( ! empty( $settings['required'] ) ) {
            wp_send_json_error( 
                sprintf( 
                    /*translators: %s: Field label.*/  
                    __( '%s is required.', 'describr' ), 
                    $settings['label'] 
                ) 
            );
        } elseif ( ! empty( $settings['emptyable'] ) ) {
            if ( in_array( $field, array( 'user_url' ), true ) ) {
                global $wpdb;
                
                $wpdb->update( 
                    $wpdb->users, 
                    array( 'user_url' => '' ), 
                    array( 'ID' => $profile->ID ) 
                );
            } else {
                update_user_meta( $profile->ID, $field, '', $saved );
            }
        } elseif ( ! empty( $settings['has_hash'] ) ) {
            $hash = sanitize_text_field( $val );
            
            $saved = $user->$field;

            if ( ! isset( $saved[ $hash ] ) ) {
                wp_send_json_error( 
                    sprintf( 
                        /*translators: %s: Field label.*/  
                        __( '%s does not exist.', 'describr' ), 
                        $settings['label'] 
                    ) 
                );
            }

            unset($saved[ $hash ]);
            
            $saved ? update_user_meta( $profile->ID, $field, $saved ) : delete_user_meta( $profile->ID, $field );
        } else {
            delete_user_meta( $profile->ID, $field, $user->$field );
        }

        describr_auditing_fields( $field );
    }
    
    describr_audit_fields( $profile->ID );
}
add_action( 'describr_profile_delete_fields', 'describr_profile_delete_fields', 10, 1 );

/**
 * Validates savable profile data
 * 
 * @since 3.0
 * 
 * @param Profile $profile The \describr\Profile instance
 */
function describr_profile_validate_edited_fields( $profile ) {
    $updated_fields = array_keys( $profile->request );
    
    $aside_fields = array();

    $user = get_userdata( $profile->ID );
    
    foreach ( $profile->request as $field => $val ) {
        $settings = describr_get_field( $field );
        
        $label = $settings['label'];

        $type = $settings['type'];
        
        $is_already_used = false;

        if ( 'array' !== $type ) {
            if ( ! is_scalar( $val ) ) {
                wp_send_json_error( 
                    sprintf( 
                        /*translators: %s: Field label.*/ 
                        __( '%s is invalid.', 'describr' ), 
                        $label 
                    ) 
                );
            }

            /*A single-value field can't be empty.
            Fields user_nicename and display_name are checked with `empty()` in `wp_insert_user()`, so the same is done here for consistency.*/
            if ( ( in_array( $field, array( 'user_nicename', 'display_name' ), true ) && empty( $val ) ) 
                || 
                0 === mb_strlen( $val ) 
            ) {
                wp_send_json_error( 
                    sprintf( 
                        /*translators: %s: Field label.*/ 
                        __( '%s cannot be empty.', 'describr' ), 
                        $label 
                    ) 
                );
            }

            $val = describr_sanitize_input( $val, $settings ); 
            
            $old_data = $user->$field;
            
            if ( is_string( $old_data ) ) {
                $old_data = trim( $old_data );
            }
            
            $old_data = wp_slash( $old_data );

            if ( 'user_email' === $field ) {
                $is_already_used = 0 == strcasecmp( $val, $old_data );
            } else {
                $is_already_used = $old_data === $val;
            }
            
            $profile->request[ $field ] = $val;           
        } elseif ( ! is_array( $val ) ) {
            wp_send_json_error( 
                sprintf( 
                    /*translators: %s: Field label.*/
                    __( '%s is invalid.', 'describr' ), 
                    $label 
                ) 
            );
        } elseif ( empty( $val ) ) {
            wp_send_json_error( 
                sprintf( 
                    /*translators: %s: Field label.*/ 
                    __( '%s cannot be empty.', 'describr' ), 
                    $label 
                ) 
            );
        } else {
            $is_already_used = $user->$field === $val;
        }
        
        describr_pluck_field_status_and_priv( $field, $profile->ID, $aside_fields );
        
        if ( $is_already_used ) {
            if ( isset( $aside_fields[ $field ] ) ) {
                unset( $profile->request[ $field ] );
                continue;
            }
            
            wp_send_json_error( 
                sprintf( 
                    /*translators: %s: Field label.*/
                    __( 'You did not make any changes to %s. Please try again.', 'describr' ), 
                    $label 
                ) 
            );
        }

        describr_verify_char_length( $settings, $val );

        if ( 'url' === $settings['type'] ) {
            $url = $val;
            
            $social = false;
            
            if ( isset( $settings['base_url'] ) ) {
                $url_ = $url;
                $base_urls = (array) $settings['base_url'];
                    
                foreach ( $base_urls as $base_url ) {
                    $url_ = describr_get_handle_from_url( $base_url, $url );
                    
                    if ( $url_ !== $url ) {
                        break;
                    }
                }
                
                //Test if base URL was not removed and add it
                if ( $url_ === $url ) {
                    $url = $base_urls[0] . preg_replace( '/^https?:\/\//i', '', $url );
                }
            }
            
            //Test if we're dealing with a social media field and retrieve its base URL
            if ( describr_is_social( $field ) && isset( $base_urls ) ) {
                $_url = $url;
                    
                foreach ( $base_urls as $base_url ) {
                    $_url = describr_get_handle_from_url( $base_url, $url );
                        
                    if ( $_url !== $url ) {
                        break;
                    }
                }

                if ( $_url !== $url ) {
                    $social = $base_url;
                } else {
                    $social = $base_urls[0];
                    $url = $social . preg_replace( '/^https?:\/\//i', '', $url );
                }
            }
            
            $url = describr()->validation()->is_url( $url, $social );

            if ( false === $url ) {
                wp_send_json_error( 
                    sprintf( 
                        /*translators: 1: Field label. 2: URL abbreviation.*/
                        __( '%1$s is not a valid %2$s (Uniform Resource Locator).', 'describr' ), 
                        $label, 
                        '<abbr>URL</abbr>' 
                    ) 
                );
            } else {
                $profile->request[ $field ] = $url;
            }

            unset($base_urls);
        }
        
        switch ( $field ) {
            case 'user_nicename':
                global $wpdb;

                if ( describr_is_nicename_locked( $profile->ID ) ) {
                    wp_send_json_error( describr_nicename_locked_notice( $profile->ID ) );
                } elseif ( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND ID != %s LIMIT 1", wp_unslash( $val ), $profile->ID ) ) ) {
                    wp_send_json_error( 
                        sprintf( 
                            /*translators: 1: Field label. 2: User's nicename.*/
                            __( '%1$s %2$s is already taken.', 'describr' ), 
                            $label,
                           '<strong>' . esc_html( $val ) . '</strong>' 
                        ) 
                    );
                }
                break;
            case 'user_email': 
                $email = wp_unslash( $val );
                
                if ( ! is_email( $email ) ) {
                    wp_send_json_error( 
                        sprintf( 
                            /*translators: %s: Field label.*/ 
                            __( '%s is invalid.', 'describr' ), 
                            $label 
                        )
                    );
                }
                
                $is_self = describr_is_self( $profile->ID );

                if ( email_exists( $email ) ) {
                    if ( $is_self ) {
                        delete_user_meta( $profile->ID, '_new_email' );
                    }
                    
                    wp_send_json_error( 
                        sprintf( 
                            /*translators: %s: Field label.*/
                            __( '%s is already used.', 'describr' ), 
                            $label 
                        ) 
                    );
                }

                if ( ! $is_self ) {
                    break;
                }
                
                $hash = md5( $email . time() . wp_rand() );
                
                /**This filter is documented in wp-includes/user.php*/
                $email = apply_filters( 'pre_user_email', $email );

                $new_user_email = array(
                    'hash'     => $hash,
                    'newemail' => $email,
                );

                if ( update_user_meta( $profile->ID, '_new_email', $new_user_email ) ) {
                    describr()->user()->send_confirmation_on_profile_email_change( $profile->ID, $email, $hash );
                
                    describr_auditing_fields( $field );
                }

                

                unset( $profile->request[ $field ] );
                break;
            case 'locale':
            case 'locales':
            case 'gender':
                $langs = array_map( 'trim', explode( ',', $val ) );
                
                $langs = array_unique( $langs );

                foreach ( $langs as $lang ) {
                    if ( ! describr_is_option( $lang, $settings['options'] ) ) {
                        wp_send_json_error( 
                            sprintf( 
                                /*translators: %s: Field label.*/ 
                                __( '%s is invalid.', 'describr' ), 
                                $label 
                            ) 
                        );
                    }
                }

                $profile->request[ $field ] = implode( ',', $langs );
                break;
            case 'birthdate':
                if ( ! describr()->validation()->is_date( $val ) ) {
                    wp_send_json_error( 
                        sprintf( 
                            /*translators: 1: Field label. 2: Date format examples.*/ 
                            __( '%1$s is invalid. Correct date formats: %2$s.', 'describr' ), 
                            $label,
                            implode( wp_get_list_item_separator(), describr_date_formats() ) 
                        ) 
                    );
                }
                break;
            case 'timezone':
                if ( ! describr_is_option( $val, $settings['options'] ) && ! preg_match( '/^UTC(\+|\-)[0-9.]{1,5}$/', $val ) ) {
                    wp_send_json_error( 
                        sprintf( 
                            /*translators: %s: Field label.*/ 
                            __( '%s is invalid.', 'describr' ), 
                            $label 
                        ) 
                    );
                }
                
                if ( preg_match( '/^UTC(\+|\-)/', $val ) ) {
                    $profile->request[ $field ] = preg_replace( '/^UTC\+?/', '', $val );
                }                
                break;
            case 'mobile_number':
            case 'work_number':
            case 'home_number':
                $supports = describr_get_field_supports( $settings['supports'], $field );

                $_val = array();            
                
                foreach ( $supports as $key => $_settings ) {
                    if ( ! empty( $_settings['required'] ) && empty( $val[ $key ] ) ) {
                        wp_send_json_error(array(
                            $_settings['name'] => sprintf( 
                                /*translators: %s: Field label.*/ 
                                __( '%s is required.', 'describr' ), 
                                $_settings['label'] 
                            )
                        ));
                    }

                    if ( isset( $val[ $_settings['name'] ] ) ) {
                        $v = $val[ $_settings['name'] ];
                        
                        if ( ! is_scalar( $v ) ) {
                            wp_send_json_error(array(
                                $_settings['name'] => sprintf( 
                                    /*translators: %s: Field label.*/ 
                                    __( '%s is invalid.', 'describr' ), 
                                    $_settings['label'] 
                                )
                            ));
                        }
                        
                        $v = describr_sanitize_input( $v, $_settings );
                        
                        describr_verify_char_length( $_settings, $v );

                        if ( ! empty( $_settings['options'] ) && ! describr_is_option( $v, $_settings['options'] ) ) {
                            wp_send_json_error(array(
                                $_settings['name'] => sprintf( 
                                    /*translators: %s: Field label.*/
                                    __( '%s is invalid.', 'describr' ), 
                                    $_settings['label']
                                )
                            ));
                        }
                        
                        $_val[ $_settings['name'] ] = $v;
                    }
                }

                $phoneNumber = $_val['number'];
                
                $phoneNumber_ = $phoneNumber;

                if ( isset( $_val['ext'] ) && '' !== $_val['ext'] && ! empty( $supports['ext'] ) ) {
                    $phoneNumber .= " ext. {$_val['ext']}";
                } else {
                    $_val['ext'] = '';
                }
                  
                if ( ! describr()->validation()->is_phonenumber( $phoneNumber ) ) {
                    wp_send_json_error( 
                        sprintf( 
                            /*translators: %s: Field label.*/
                            __( '%s is invalid.', 'describr' ), 
                            $supports['number']['label'] 
                        ) 
                    );
                }
                
                $saved_number = get_user_meta( $profile->ID, $field, true );
                            
                if ( ! $saved_number ) {
                    $saved_number = array();
                }
                
                $_val['type'] = in_array( $field , array( 'mobile_number' ), true ) ? 1 : 0;
                
                $saved_number = array_filter( array_merge( $saved_number, $_val ) , 'strlen' );

                $profile->request[ $field ] = $saved_number;
                break;
            case 'relationship':
                $supports = describr_get_field_supports( $settings['supports'], $field );

                $_val = array();

                foreach ( $supports as $key => $_settings ) {
                    if ( ! empty( $_settings['required'] ) && empty( $val[ $key ] ) ) {
                        wp_send_json_error(array(
                            $_settings['name'] => sprintf( 
                                /*translators: %s: Field label.*/ 
                                __( '%s is required.', 'describr' ), 
                                $_settings['label'] 
                            )
                        ));
                    }

                    if ( isset( $val[ $_settings['name'] ] ) ) {
                        $v_ = $val[ $_settings['name'] ];
                        
                        if ( ! is_scalar( $v_ ) ) {
                            wp_send_json_error(array(
                                $_settings['name'] => sprintf( 
                                    /*translators: %s: Field label.*/
                                    __( '%s is invalid.', 'describr' ), 
                                    $_settings['label'] 
                                )
                            ));
                        }

                        $v = describr_sanitize_input( $v_, $_settings );
                        
                        if ( '' !== $v_ && 'date' === $_settings['type'] && ! describr()->validation()->is_date( $v_ ) ) {
                            wp_send_json_error(array(
                                $_settings['name'] => sprintf( 
                                    /*translators: 1: Field label. 2: Date format examples.*/ 
                                    __( '%1$s is invalid. Correct date formats: %2$s.', 'describr' ), 
                                    $_settings['label'],
                                    implode( wp_get_list_item_separator(), describr_date_formats() ) 
                                )
                            ));
                        }

                        if ( ! empty( $_settings['max_chars'] ) && mb_strlen( $v ) > $_settings['max_chars'] ) {
                            $v = mb_substr( $v, 0, $_settings['max_chars'] );
                        }
                        
                        if ( '' !== $v && ! empty( $_settings['options'] ) && ! describr_is_option( $v, $_settings['options'] ) ) {
                            wp_send_json_error(array(
                                $_settings['name'] => sprintf( 
                                    /*translators: %s: Field label.*/
                                    __( '%s is invalid.', 'describr' ), 
                                    $_settings['label'] 
                                )
                            ));
                        }
                        
                        $_val[ $_settings['name'] ]  = $v;
                    }
                }
                
                if ( empty( $_val['status'] ) && ( ! isset( $_val['partner'] ) || ! mb_strlen( $_val['partner'] ) ) && empty( $_val['since'] ) ) {
                    wp_send_json_error( 
                        sprintf( 
                            /*translators: %s: Field label.*/
                            __( '%s requires more information.', 'describr' ), 
                            $settings['label'] 
                        ) 
                    );
                } 
                
                $partner = isset( $_val['partner'] ) ? $_val['partner'] : '';

                if ( ! empty( $_val['partner_id'] ) ) {
                    if ( '' === $partner ) {
                        $_val['partner_id'] = '';
                    } elseif ( (string) $_val['partner_id'] === (string) $profile->ID || ! describr_is_member( $_val['partner_id'] ) ) {
                        wp_send_json_error( 
                            sprintf( 
                                /*translators: %s: Intimate partner's name.*/
                                __( '%s does not exist.', 'describr' ), 
                                $partner 
                            ) 
                        );
                    }
                } else {
                    $_val['partner_id'] = '';
                }

                $saved_rel = get_user_meta( $profile->ID, $field, true );
                
                if ( ! $saved_rel ) {
                    $saved_rel = array();
                }
                            
                $saved_rel = array_filter( array_merge( $saved_rel, $_val ), 'strlen' );
                
                $profile->request[ $field ] = $saved_rel;
                break;
            case 'college':
            case 'high_school':
            case 'work_history':
            case 'lived_cities':
                $supports = describr_get_field_supports( $settings['supports'], $field );
                
                $_val = array();

                foreach ( $supports as $key => $_settings ) {
                    if ( ! empty( $_settings['required'] ) && empty( $val[ $_settings['name'] ] ) ) {
                        wp_send_json_error(array(
                            $_settings['name'] => sprintf( 
                                /*translators: %s: Field label.*/ 
                                __( '%s is required.', 'describr' ), 
                                $_settings['label'] 
                            )
                        ));
                    }
                    
                    if ( isset( $val[ $_settings['name'] ] ) ) {
                        $v_ = $val[ $_settings['name'] ];

                        if ( ! is_scalar( $v_ ) ) {
                            wp_send_json_error(array(
                                $_settings['name'] => sprintf( 
                                    /*translators: %s: Field label.*/
                                    __( '%s is invalid.', 'describr' ), 
                                    $_settings['label'] 
                                )
                            ));
                        }

                        $v = describr_sanitize_input( $v_, $_settings );
                                    
                        if ( '' !== $v_ && 'date' === $_settings['type'] && ! describr()->validation()->is_date( $v_ ) ) {
                            wp_send_json_error(array(
                                $_settings['name'] => sprintf( 
                                    /*translators: 1: Field label. 2: Date format examples.*/ 
                                    __( '%1$s is invalid. Correct date formats: %2$s.', 'describr' ), 
                                    $_settings['label'],
                                    implode( wp_get_list_item_separator(), describr_date_formats() ) 
                                )
                            ));
                        }
                        
                        describr_verify_char_length( $_settings, $v );

                        $_val[ $_settings['name'] ] = $v;
                    }
                }
                
                if ( 'lived_cities' !== $field ) {
                    if ( isset( $_val['school'] ) ) {
                        if ( ! empty( $_val['graduated'] ) ) {
                            $_val['graduated'] = '1';
                        } else {
                            $_val['graduated'] = '';
                        }
                    } elseif ( ! empty( $_val['present'] ) )  {
                        $_val['present'] = '1';

                        if ( isset( $_val['to'] ) ) {
                            $_val['to'] = '';
                        }
                    } else {
                        $_val['present'] = '';
                    }
                }
                
                $items = get_user_meta( $profile->ID, $field, true );
                
                unset($hash);

                if ( $items ) {
                    if ( isset( $_POST['update'] ) ) {
                        $hash = sanitize_text_field( wp_unslash( $_POST['update'] ) );
                                
                        if ( ! isset( $items[ $hash ] ) ) {
                            wp_send_json_error( __( 'You do not have permission to edit this user.', 'describr' ) );
                        }
                    }
                }
                            
                if ( isset( $hash ) ) {
                    $items[ $hash ] = array_merge( $items[ $hash ], $_val );
                } else {
                    $hash = md5( $field . $profile->ID . wp_rand() . time() );

                    if ( $items ) {
                        $items[ $hash ] = $_val;
                    } else {
                        $items = array( $hash => $_val );
                    }
                }
                            
                $items[ $hash ] = array_filter( $items[ $hash ], 'strlen' );

                $array_unique = function ( $item ) {
                    static $arr = array();

                    $filter = ! in_array( $item, $arr, true );
                    
                    $arr[] = $item;
                    
                    return $filter;
                };
                
                $profile->request[ $field ] = array_filter( $items, $array_unique );
                break;
            case 'discord':
                if ( ! preg_match( '/^[\p{Nd}]+$/u', $val ) ) {
                    wp_send_json_error( 
                        sprintf( 
                            /*translators: %s: Field label.*/ 
                            __( '%s can only contain numbers.', 'describr' ), 
                            $label 
                        ) 
                    );
                }                                
                break;
            case 'line':
                if ( ! preg_match( '/^[a-z\p{Nd}._-]+$/u', $val ) ) {
                    wp_send_json_error( 
                        sprintf( 
                            /*translators: %s: Field label.*/ 
                            __( '%s can only contain lowercase alphabetic characters, numbers, periods (.), hyphens (-), and underscores (_).', 'describr' ), 
                            $label
                        ) 
                    );
                }
                break;
            case 'kakaotalk':
                if ( preg_match( '/[\s\n\t\r]{1}/', $val ) ) {
                    wp_send_json_error( 
                        sprintf( 
                                /*translators: %s: Field label.*/ 
                                __( '%s cannot contain spaces.', 'describr' ), 
                                $label
                            ) 
                    );
                }
                break;
            default:
                /**
                 * Fires before a user meta value is saved
                 * 
                 * @since 3.0
                 * 
                 * @param mixed  $val      Field value
                 * @param string $field    Field
                 * @param array  $settings Field settings
                 * @param int    $user_id  ID of the user to whom the profile belongs
                 */
                do_action( 'describr_profile_before_saved_usermeta', $val, $field, $settings, $profile->ID );
                break;
        }
    }
    
    $profile->request = array( 
        'userdata' => $profile->request,
        'fields'   => $updated_fields,
        'asides'   => $aside_fields,
    );

    unset($aside_fields);
}
add_action( 'describr_profile_validate_edited_fields', 'describr_profile_validate_edited_fields', 10, 1 );

/**
 * Outputs the profile screen reader alert element
 * 
 * @since 3.0
 */
function describr_profile_global_screen_reader_alert() {
    echo '<div id="describr-global-screen-reader-alert-text" class="describr-a11y-text" role="none"></div>';
}
add_action( 'describr_profile_before_header', 'describr_profile_global_screen_reader_alert', 10, 0 );

/**
 * Outputs profile header
 * 
 * @since 3.0
 * 
 * @param array $args Additional arguments passed to the template
 */
function describr_profile_header( $args ) {
    $user_id = describr_profile_id();
        
    $can_take_picture_actions = describr_can_take_profile_picture_actions();

    $picture_key = describr_photo_key();
    ?>
    <div id="describr-profile-header" class="describr-profile-header">
        <div class="describr-profile-picture"<?php
        if ( $can_take_picture_actions ) {
            echo ' id="describr-profile-picture" role="button" tabindex="0" aria-expanded="false" aria-label="' . esc_attr__( 'Profile picture actions', 'describr' ) . '" data-key="' . esc_attr( $picture_key ) . '"';
        }
        ?>>
            <?php echo wp_kses_post( get_avatar( $user_id, describr_photo_size(), 'mystery' ) ); ?>
            
            <?php
            if ( $can_take_picture_actions ) {
                ?>
                <div aria-hidden="true" class="describr-profile-picture-overlay"></div>
                <?php
                /**
                 * Filters the HTML classes used to display the icon that is
                 * clicked to display the modal from where the profile picture 
                 * can be edited.
                 * 
                 * @since 3.0
                 * 
                 * @param string $classes HTML classes for the "edit" icon 
                 * @param int    $user_id ID of the user to whom the profile belongs
                 */
                $icon = apply_filters( 'describr_profile_picture_edit_icon', 'dashicons dashicons-edit', $user_id );
                ?>
                <div aria-hidden="true" title="<?php echo esc_attr(sprintf(
                    /*translators: %s Field title.*/
                    __( 'Update %s', 'describr' ), 
                    describr_get_field_( $picture_key, 'title' ) 
                )); ?>" class="describr-profile-picture-edit"><span class="<?php echo esc_attr( $icon ); ?>"></span></div>
                <?php
            } 
            ?>
        </div>
        <div class="<?php
        /**
         * Filters the profile summary class
         * 
         * @since 3.0
         * 
         * @param string $summary_class Profile summary class
         * @param int    $user_id       ID of the user to whom the profile belongs
         * @param array  $args          Additional arguments passed to the template
         */
        $summary_classes = apply_filters( 'describr_profile_summary_class', 'describr-profile-summary', $user_id, $args );
        
        if ( $summary_classes ) {
            echo esc_attr( $summary_classes );
        }
        ?>">
            <?php
            /**
             * Prints the profile's summary.
             * 
             * @since 3.1
             * 
             * @param int   $user_id ID of the user to whom the profile belongs.
             * @param array $args    Additional arguments passed to the template.
             */
            do_action( 'describr_profile_summary', $user_id, $args );
            ?>
        </div>
    </div>
    <?php
}
add_action( 'describr_profile_header', 'describr_profile_header', 10, 1 );

/**
 * Prints the profile's summary.
 * 
 * @since 3.1
 * 
 * @param int   $user_id ID of the user to whom the profile belongs.
 * @param array $args    Additional arguments passed to the template.
 */
function describr_profile_summary( $user_id, $args ) {
    $fields = array( 'display_name' );

    if ( describr_is_field_viewable( 'user_login', $user_id ) ) {
        $fields[] = 'user_login';
    }
    
    $fields[] = 'profile_url';

    if ( describr_is_field_viewable( 'current_city', $user_id ) ) {
        $fields[] = 'current_city';
    }

    if ( describr_can_view_when_logged_in( $user_id ) ) {
        $fields[] = 'when_last_login';
    }
    
    if ( describr_can_view_registered( $user_id ) ) {
        $fields[] = 'registered';
    }

    if ( describr_is_field_viewable( 'tagline', $user_id ) ) {
        $fields[] = 'tagline';
    }

    if ( describr_get_socials() ) {
        $socials = array_filter( array_keys( describr_get_socials() ), 'describr_is_field_viewable' );

        if ( $socials ) {
            $fields[] = 'social_media';
        }
    }
    
    /**
     * Filters the fields for the profile summary.
     * 
     * Filters may remove unwanted fields.
     * 
     * @since 3.1
     * 
     * @param array $fields  Fields for the profile summary.
     * @param int   $user_id ID of the user to whom the profile belongs.
     * @param array $args    Additional arguments passed to the template.
     */
    $fields = apply_filters( 'describr_profile_summary_fields', $fields, $user_id, $args );

    if ( $fields ) {
        $user = get_userdata( $user_id );

        $id = 'describr-profile-summary-';
        
        $field = 'display_name';

        if ( in_array( $field, $fields, true ) ) {
            $fields = array_diff( $fields, array( $field ) );
            
            echo '<div id="' . esc_attr( $id . $field ) . '"><span class="describr-profile-summary-part display_name" title="' . esc_attr( describr_get_field_( $field, 'title' ) ) . '">' . wp_kses_post( convert_smilies( $user->display_name ) ) . '</span>';

            $indicators = '';
                
            $verified_indicator = describr_important_user_indicator( $user_id );
                
            if ( $verified_indicator ) {
                $indicators .= $verified_indicator;
            }
                    
            $status_indicator = describr_user_status_indicator( $user_id );

            if ( $status_indicator ) {
                $indicators .= " $status_indicator";
            }
                  
            /**
             * Filters the indicators
             * 
             * @since 3.0
             * 
             * @param string $indicators The indicators
             * @param int    $user_id    ID of the user to whom the profile belongs
             * @param array  $args       Additional arguments passed to the template
             */
            $indicators = apply_filters( 'describr_profile_indicators', $indicators, $user_id, $args );
                    
            $indicators = trim( $indicators );

            if ( ! empty( $indicators ) ) {
                echo wp_kses_post( " $indicators" ) ;
            }

            echo '</div>';
        }
        
        $field = 'user_login';

        if ( in_array( $field, $fields, true ) ) {
            $fields = array_diff( $fields, array( $field ) );

            /**
             * Filters the profile username
             * 
             * @since 3.0
             * 
             * @param string $username Username
             * @param int    $user_id  ID of the user to whom the profile belongs
             * @param array  $args     Additional arguments passed to the template
             */
            $user_login = apply_filters( 'describr_profile_username', '@' . $user->user_login, $user_id, $args );

            if ( $user_login ) {
                echo '<div id="' . esc_attr( $id . $field ) . '" title="'. esc_attr( describr_get_field_( $field, 'title' ) ) .'">' . esc_html( $user_login ) . '</div>';
            }
        }

        if ( $fields ) {
            echo '<div class="describr-profile-summary-fields-inside-header">';

            /**
             * Prints the responsive profile summaries.
             * 
             * @since 3.0
             * 
             * @param array $fields   Summary fields.
             * @param int    $user_id ID of the user to whom the profile belongs.
             * @param array  $args    Additional arguments passed to the template.
             */
            do_action( 'describr_print_profile_summary_fields', $fields, $user_id, $args );

            echo '</div>';
        }
    }
}
add_action( 'describr_profile_summary', 'describr_profile_summary', 10, 2 );

/**
 * Prints responsive profile summaries.
 * 
 * @since 3.1.1
 * 
 * @param array $fields   Summary fields.
 * @param int    $user_id ID of the user to whom the profile belongs.
 * @param array  $args    Additional arguments passed to the template.
 */
function describr_print_profile_summary_fields( $fields, $user_id, $args ) {
    $user = get_userdata( $user_id );
    $class = 'describr-profile-summary-';

    foreach ( array_unique( $fields ) as $field ) {
        if ( 'profile_url' === $field ) {
            /**
             * Filters the profile URL
             * 
             * @since 3.0
             * 
             * @param string $url     The profile URL
             * @param int    $user_id ID of the user to whom the profile belongs
             * @param array  $args    Additional arguments passed to the template
             */
            $url = apply_filters( 'describr_profile_url__', describr_profile_url( $user->user_nicename ), $user_id, $args );

            if ( ! empty( $url ) ) {
                echo '<div class="' . esc_attr( $class . $field ) . '"><span aria-hidden="true" class="' . esc_attr( describr_get_field_( $field, 'icon' ) ) . '"></span><span class="describr-a11y-text">' .
                        /*translators: Hidden accessibility text.*/  
                        esc_html( 'profile URL:', 'describr' ) . 
                        '</span> <span class="describr-profile-summary-part" title="' . esc_attr( describr_get_field_( $field, 'title' ) ) . '">' . esc_html( $url ) . '</span></div>';
            }
        } elseif ( 'when_last_login' === $field ) {
            $is_logged_in = describr_is_user_logged_in_anywhere( $user_id );
               
            $last_login = describr_time_last_login( $user_id );
                
            if ( $last_login ) {
                $login_class = $is_logged_in ? ' describr-user-logged-in' : '';

                echo '<div class="' . esc_attr( $class ) . 'login-time"><span aria-hidden="true" class="dashicons dashicons-lightbulb' . esc_attr( $login_class ) . '"' . 
                       ( $login_class ? ' title="' . esc_attr_x( 'Logged In', 'user', 'describr' ) . '"' : '' )
                        . '></span> <span class="describr-profile-summary-part">' . esc_html(sprintf(
                        /*translators: %s: Human-readable time difference. See https://developer.wordpress.org/reference/functions/human_time_diff.*/
                        __( 'Logged in %s ago', 'describr' ), 
                        human_time_diff( (int) $last_login )
                    )) . '</span>';

                if ( $login_class ) {
                    echo '<span class="describr-a11y-text">' . esc_html(sprintf(
                        /*translators: Hidden accessibility text. %s: User's display name.*/
                        __( '%s is logged in.', 'describr' ),
                        $user->display_name
                        )) . '</span>';
                }

                echo '</div>';
            }
            
            if ( $is_logged_in && empty( $login_class ) ) {
                echo '<div class="' . esc_attr( $class ) . 'logged-in">' .
                    wp_kses_post( describr_logged_in_indicator( $user_id ) ) . 
                    ' <span class="describr-profile-summary-part" aria-hidden="true">' . esc_html_x( 'Logged in', 'user', 'describr' ) . '</span></div>';
            }
        } elseif ( 'registered' === $field ) {
            $user_registered = $user->user_registered;

            if ( ! empty( $user_registered ) && '0000-00-00 00:00:00' !== trim( $user_registered ) ) {
                echo '<div class="' . esc_attr( $class ) . 'registered"><span aria-hidden="true" class="dashicons dashicons-info-outline"></span> <span class="describr-profile-summary-part">' . 
                                    esc_html(sprintf(
                                        /*translators: %s: Date.*/
                                        __( 'Joined %s', 'describr' ), 
                                       wp_date( describr_locale_date_format( 'date' ), strtotime( $user_registered ), new DateTimeZone( 'UTC' ) ) 
                                    )) . '</span></div>';
            }
        } elseif ( 'social_media' === $field ) {
            /**
              * Filters the keys for social media shown first in the profile summary.
              * 
              * @since 3.1.1
              * 
              * @param array $social_media_first An array of keys for social media shown 
              *                                  first in the profile summary.
              * @param int   $user_id            ID of the user to whom the profile belongs.
              * @param array $args               Additional arguments passed to the template.
              */
            $socials_first = apply_filters( 'describr_profile_summary_social_media_show_first', array( 'facebook', 'youtube', 'instagram', 'tiktok', 'x', 'github' ), $user_id, $args );

            $socials_ = array_keys( describr_get_socials() );//All social media keys.
            //Remove social media that will show first, will re-merge below.
            $_socials = array_diff( $socials_, $socials_first );            
            $socials = array_filter( array_merge( $socials_first, $_socials ), 'describr_is_field_viewable' );

            /**
              * Filters the social media keys for printing linked social-media icons in the profile summary.
              * 
              * @since 3.1.1
              * 
              * @param array $socials Social media keys.
              * @param int   $user_id ID of the user to whom the profile belongs.
              * @param array $args    Additional arguments passed to the template.
              */
            $socials = apply_filters( 'describr_profile_summary_social_media_fields', $socials, $user_id, $args );

            if ( $socials ) {
                $urls = array();

                foreach ( $socials as $key ) {
                    $settings = describr_get_field( $key );

                    if ( ! empty( $settings['icon'] ) && isset( $settings['type'] ) && 'url' === $settings['type'] ) {
                        $url = trim( get_user_meta( $user_id, $key, true ) );

                        if ( strlen( $url ) ) {
                            $urls[ $key ] = $url;
                        }
                    }
                }

                if ( $urls ) {
                    $a11y_text = /*translators: Hidden accessibility text.*/ 
                                esc_html__( '(opens in a new tab)', 'describr' );
                    
                    $social_class = "{$class}social-media-accounts";
                    
                    echo '<div class="' . esc_attr( $social_class ) . '"><ul>';
                    
                    $social_class .= '-';

                    foreach ( $urls as $social_key => $url_ ) {
                        $settings = describr_get_field( $social_key );
                        $target = isset( $settings['target'] ) ? $settings['target'] : '_blank';
                        $rel = isset( $settings['rel'] ) ? $settings['rel'] : 'ugc nofollow';

                        if ( ! preg_match( '/^https?/i', $url_ ) ) {
                            $url_ = 'http://' . ltrim( $url_, ':/\\' );
                        }
    
                        $a11y_text_ = $settings['label'];

                        if ( '_blank' === $target ) {
                            $a11y_text_ .= " $a11y_text";
                        }

                        echo '<li><a class="' . esc_attr( $social_class . $social_key ) . '" href="' . esc_url( $url_ ) . '" rel="' . esc_attr( $rel ) . '" target="' . esc_attr( $target ) . '"><span class="describr-a11y-text">' . esc_html( $a11y_text_ ) . '</span><div class="' . esc_attr( $social_class ) . 'icon ' . esc_attr( $social_key ) . ' ' . esc_attr( $settings['icon'] ) . '" title="' . esc_attr( $settings['title'] ) . '" aria-hidden="true"></div></a></li>';
                    }

                    echo '</ul></div>';
                }
            }

            /**
              * Prints linked social-media icons in the profile summary.
              * 
              * @since 3.1.1
              * 
              * @param int   $user_id  ID of the user to whom the profile belongs.
              * @param array $socials_ All Social media keys.
              * @param array $args     Additional arguments passed to the template.
              */
            do_action( 'describr_profile_summary_social_media_accounts', $user_id, $socials_, $args );
        } else {
            $val = trim( get_user_meta( $user_id, $field, true ) );
                
            if ( strlen( $val ) ) {
                $title = describr_get_field_( $field, 'title' );
                $label = describr_get_field_( $field, 'label' );
                $icon = describr_get_field_( $field, 'icon' );

                if ( $icon ) {
                    $icon = '<span aria-hidden="true" class="' . esc_attr( $icon ) . '"></span> ';
                }
                    
                echo '<div id="' . esc_attr( $class . $field ) . '">' . $icon . '<cite class="describr-profile-summary-part" title="' . esc_attr( $title ) . '"><span class="describr-a11y-text">' . esc_html( $label ) . ': </span>' . wp_kses_post( convert_smilies( $val ) ) . '</cite></div>';
            }
        }
    }
}
add_action( 'describr_print_profile_summary_fields', 'describr_print_profile_summary_fields', 10, 3 );

/**
 * Prints responsive profile summaries after the header.
 * 
 * @since 3.1.1
 * 
 * @since array $args Additional arguments passed to the template.
 */
function describr_profile_summary_after_header( $args ) {
    $user_id = describr_profile_id();
    $fields = array( 'profile_url' );

    if ( describr_is_field_viewable( 'current_city', $user_id ) ) {
        $fields[] = 'current_city';
    }

    if ( describr_can_view_when_logged_in( $user_id ) ) {
        $fields[] = 'when_last_login';
    }
    
    if ( describr_can_view_registered( $user_id ) ) {
        $fields[] = 'registered';
    }

    if ( describr_is_field_viewable( 'tagline', $user_id ) ) {
        $fields[] = 'tagline';
    }

    if ( describr_get_socials() ) {
        $socials = array_filter( array_keys( describr_get_socials() ), 'describr_is_field_viewable' );

        if ( $socials ) {
            $fields[] = 'social_media';
        }
    }
    
    /*This filter is documented in wp-content/plugins/describr/includes/actions-filters/actions-profile.php*/
    $fields = apply_filters( 'describr_profile_summary_fields', $fields, $user_id, $args );

    if ( $fields ) {
        echo '<div class="describr-profile-summary-fields-after-header">';

        /*This action is documented in wp-content/plugins/describr/includes/actions-filters/actions-profile.php*/
        do_action( 'describr_print_profile_summary_fields', $fields, $user_id, $args );

        echo '</div>';
    }
}
add_action( 'describr_profile_after_header', 'describr_profile_summary_after_header', 10, 1 );


/**
 * Outputs profile action buttons after 
 * the profile header is output
 * 
 * @since 3.0
 * 
 * @since array $args Additional arguments passed to the template
 */
function describr_profile_actions( $args ) {
    $user_id = describr_profile_id();
    $can_block = describr_can_block_user( $user_id ) || ( is_user_logged_in() && get_user_meta( get_current_user_id(), "describr_blocked_user_{$user_id}", true ) );
    $can_send_message = describr_can_send_message();
    $can_edit = current_user_can( 'edit_user', $user_id );

    $add_wrap = $can_block || $can_send_message || $can_edit;
    
    if ( $add_wrap ) {
        echo '<div class="describr-profile-actions">';
    }
    
    if ( $can_edit ) {
        /**
         * Fires in the profile action buttons wrapper
         * 
         * @since 3.0
         * 
         * @param int   $user_id ID of the user to whom the profile belongs
         * @param array $args    Additional arguments passed to the template
         */
        do_action( 'describr_profile_action_edit-profile', $user_id, $args ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
    }

    if ( $can_send_message ) {
        /**
         * Fires after the Edit Profile button
         * 
         * @since 3.0
         * 
         * @param int   $user_id ID of the user to whom the profile belongs
         * @param array $args    Additional arguments passed to the template
         */
        do_action( 'describr_profile_action_send-message', $user_id, $args ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
    }

    if ( $can_block ) {
        /**
         * Fires after the Send Message button
         * 
         * @since 3.0
         * 
         * @param int   $user_id ID of the user to whom the profile belongs
         * @param array $args    Additional arguments passed to the template
         */
        do_action( 'describr_profile_action_block-profile', $user_id, $args ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
    }
    
    if ( $add_wrap ) {
        echo '</div>';
    }
}
add_action( 'describr_profile_after_header', 'describr_profile_actions', 10, 1 );

/**
 * Outputs the Edit Profile button in the profile header
 * 
 * @since 3.0
 */
function describr_profile_edit_profile( $user_id ) {
    $url = describr_profile_url( '', $user_id );
    $url = describr()->profile()->subtab_url( 'about', $url );
    $url .= '#describr-profile-menu-tab-about';

    /** 
     * Fires before the Edit Field icon is displayed
     * 
     * @since 3.0
     * 
     * @param string $url     URL to edit the profile
     * @param int    $user_id ID of the user to whom the field belongs
     */
    $url = apply_filters( 'describr_edit_profile_url', $url, $user_id );
    
    if ( $url ) {
        $label = sprintf(
            /*translators: User's display name.*/
            __( 'Edit %s profile', 'describr' ), 
            describr_display_name( $user_id ) 
        );

        $title = __( 'Edit Profile', 'describr' );
        ?>
        <a href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" title="<?php echo esc_attr( $title ); ?>" class="action describr-profile-edit-profile"><span class="text"><?php echo esc_html( $title ); ?></span><span aria-hidden="true" class="dashicons dashicons-edit"></span>
        </a>
        <?php
    }
}
add_action( 'describr_profile_action_edit-profile', 'describr_profile_edit_profile', 10, 1 ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/**
 * Outputs the Send Message button in the profile header
 * 
 * @since 3.0
 */
function describr_profile_message( $user_id ) {
    $text = esc_html_x( 'Send Message', 'email', 'describr' );

    $label = sprintf(
            /*translators: User's display name.*/
            _x( 'Send %s a message', 'email', 'describr' ), 
            describr_display_name( $user_id ) 
        );
    
    if ( is_user_logged_in() ) {
        ?>
        <button type="button" title="<?php echo esc_attr( $text ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" class="describr-profile-send-message action" id="describr-profile-send-message"><span class="text"><?php echo esc_html( $text ); ?></span><span aria-hidden="true" class="dashicons dashicons-email"></span>
        </button>
        <?php
    } else {
        //Triggers the Send Message modal when the user returns after logging in
        //Addressed in {@see 'describr_enqueue_profile_translated_strings_and_settings'} function
        $redirect_to = add_query_arg( 'send_msg_to', $user_id );
        $login_url   = describr_login_url( $redirect_to, $user_id );
        ?>
        <a href="<?php echo esc_url( $login_url ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" title="<?php echo esc_attr( $text ); ?>" class="logged-out action describr-profile-send-message"><span class="text"><?php echo esc_html( $text ); ?></span><span aria-hidden="true" class="dashicons dashicons-email"></span>
        </a>
        <?php
    }
}
add_action( 'describr_profile_action_send-message', 'describr_profile_message', 10, 1 ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/**
 * Outputs the (Un)block button in the profile header
 * 
 * @since 3.0
 * 
 * @param int $user_id Profile user ID
 */
function describr_block_profile( $user_id ) {
    $blocked_date = get_user_meta( get_current_user_id(), "describr_blocked_user_{$user_id}", true );
    
    $display_name = describr_display_name( $user_id );

    if ( $blocked_date ) {
        $text = _x( 'Unblock', 'allow user access', 'describr' );
        
        $label = sprintf(
            /*translators: User's display name.*/
            _x( 'Unblock %s', 'allow user access', 'describr' ),
            $display_name
        );

        echo '<span class="describr-a11y-text">' . esc_html(sprintf(
            /*translators: Hidden accessibility text. 1: User's display name. 2: Date and time.*/
            _x( '%1$s was blocked on %2$s. You can unblock %1$s by clicking the following button', 'restricted user', 'describr' ),
            $display_name,
            describr_date( describr_locale_date_format( 'date@time' ), $blocked_date, wp_get_current_user()->timezone )
        )) . ' </span>';
    }  else {
        $text = _x( 'Block', 'restrict user access', 'describr' );

        $label = sprintf(
            /*translators: User's display name.*/
            _x( 'Block %s', 'restrict user access', 'describr' ),
            $display_name
        );
    }    
    ?>
    <button type="button" id="describr-profile-block" class="action" data-action="<?php echo $blocked_date ? 'unblock' : 'block'; ?>" aria-label="<?php echo esc_attr( $label ); ?>" title="<?php echo esc_attr( $text ); ?>"><?php echo esc_html( $text ); ?></button>
    <?php
}
add_action( 'describr_profile_action_block-profile', 'describr_block_profile', 10, 1 ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/**
 * Outputs profile tablist
 * 
 * @since 3.0
 * 
 * @since array $args Profile template data
 */
function describr_profile_menu( $args ) {
    if ( ! get_option( 'describr_enable_profile_menu' ) ) {
        return;
    }
    
    describr()->profile()->sub_tab();

    $current_tab = describr()->profile()->current_tab();
    $tabs = describr()->profile()->get_allowed_tabs();
    
    if ( empty( $tabs ) ) {
        describr()->profile()->current_tab    = false;
        describr()->profile()->current_subtab = false;
        return;
    }

    if ( ! isset( $tabs[ $current_tab ] ) ) {
        $current_tab = 'about';

        if ( ! isset( $tabs[ $current_tab ] ) ) {
            $current_tab = false;
        }

        describr()->profile()->current_tab    = $current_tab;
        describr()->profile()->current_subtab = false;
    }

    if ( 1 === count( $tabs ) ) {
        $one_tab = true;
    }    
    
    //Put the default tab at the beginning if more than one tab exists
    if ( ! isset( $one_tab ) ) {
        $default_tab = get_option( 'describr_default_profile_menu_tab' );
        
        //Test if the default tab is authorized and set it to "about"
        if ( ! isset( $tabs[ $default_tab ] ) ) {
            $d_tab = 'about';
            
            //Make sure "about" tab is authorized by admin
            if ( isset( $tabs[ $d_tab ] ) ) {
                //Test if the current tab is the unauthorized default tab and change it to "about"
                if ( $current_tab === $default_tab ) {
                    $current_tab = $d_tab;
                    describr()->profile()->current_tab = $current_tab;
                }

                $default_tab = $d_tab;
            }
        }

        if ( isset( $tabs[ $default_tab ] ) ) {
            $dtab = array( $default_tab => $tabs[ $default_tab ] );
            unset( $tabs[ $default_tab ] );
            $tabs = $dtab + $tabs;
        } else {
            $default_tab = null;
        }

        describr()->profile()->default_tab = $default_tab;
    }
    ?>
    <div id="describr-profile-menu" class="describr-profile-menu"> 
        <?php
        /**
         * Fires before output of the profile menu tablist
         * 
         * @since 3.0
         */
        do_action( 'describr_before_profile_menu' );
        ?>
        <div id="describr-profile-menu-tablist" class="describr-profile-menu-tablist" aria-orientation="horizontal" role="tablist"> 
            <?php
            /**
             * Fires before output of the profile menu tabs
             * 
             * @since 3.0
             */
            do_action( 'describr_before_profile_menu_tabs' );
                   
            foreach ( $tabs as $tab => $tab_args ) {
                /**
                 * Filters the profile tab URL
                 * 
                 * The dynamic part of the hook name is the name of the profile tab
                 * 
                 * @since 3.0
                 * 
                 * @param string $url      The tab URL
                 * @param string $tab_args The tab data
                 * @param array  $args     The profile template data
                 */
                $tab_url = apply_filters( "describr_profile_tab_url_{$tab}", describr_profile_tab_url( $tab, true ), $tab_args, $args );
            
                /**
                 * Filters extra profile tab attributes
                 * 
                 * @since 3.0
                 * 
                 * @param array  $attr     The extra attributes
                 * @param string $tab_args The tab data
                 * @param array  $args     The profile template data
                 */
                $tab_url_attr = apply_filters( "describr_profile_tab_url_{$tab}_attrs", array(), $tab_args, $args );
            
                $tab_class = '';

                if ( $tab === $current_tab ) {
                    $tab_class .= ' current';
                }
                ?>
                <a href="<?php echo esc_url( $tab_url ); ?>" id="describr-profile-menu-tab-<?php echo esc_attr( $tab );?>" class="describr-profile-menu-tab <?php echo esc_attr( $tab_class ); ?>" aria-selected="<?php echo $tab === $current_tab ? 'true' : 'false'; ?>" role="tab" data-menutab="true" data-tab="<?php echo esc_attr( $tab ); ?>"<?php 
                if ( $tab_url_attr ) {
                    foreach ( $tab_url_attr as $attr => $attr_val ) {
                        echo ' ' . esc_attr( $attr ) . '="' . wp_kses_post( $attr_val ) . '"';
                    }
                }
                ?>>
                    <?php
                    if ( ! empty( $tab_args['notifier'] ) ) {
                        echo '<span aria-hidden="true" class="' . esc_attr( "describr-tab-icon describr-profile-menu-tab-notifier {$tab_args['notifier']}" ). '"></span>';
                    }

                    if ( ! empty( $tab_args['icon'] ) ) {
                        echo '<span aria-hidden="true" class="' . esc_attr( "describr-tab-icon describr-profile-menu-tab-icon {$tab_args['icon']}" ). '"></span>';
                    }
                    ?>
                    <span class="describr-tab-title"><?php echo esc_html( $tab_args['name'] ); ?></span>
                    <span aria-hidden="true" class="describr-profile-menu-tab-highlight"></span>
                </a>
                <?php
            }

            /**
             * Fires after output of the profile menu tabs
             * 
             * @since 3.0
             */
            do_action( 'describr_after_profile_menu_tabs' );
            ?>
        </div>
        <?php
        /**
         * Fires after output of the profile menu tablist
         * 
         * @since 3.0
         */
        do_action( 'describr_after_profile_menu' );
        ?>
    </div>
    <?php
}
add_action( 'describr_profile_menu', 'describr_profile_menu', 10, 1 );

/**
 * Outputs About tab content
 * 
 * @since 3.0
 * 
 * @param string $current_tab Current tab
 * @param array  $settings    About settings
 * @param array  $args        Additional arguments passed to the template
 */
function describr_profile_menu_tab_content_about( $current_tab, $settings, $args ) {
    $tab = 'about';

    /**
     * Filters the current subtab
     * 
     * @since 3.0
     * 
     * @return false $current_subtab The current subtab
     */
    $current_subtab = apply_filters( 'describr_profile_current_subtab', false );

    //About tabpanel
    ?>
    <div class="describr-profile-menu-tab-content describr-profile-menu-tab-content-flex describr-profile-menu-tab-content-about<?php echo $tab === $current_tab ? ' current' : ''; ?>" role="tabpanel" tabindex="<?php echo $tab === $current_tab ? '0' : '-1'; ?>" aria-labelledby="describr-profile-menu-tab-about">
        <?php
        $tab_ids = array();
        
        $has_subtab = false;

        if ( ! empty( $settings['subnav'] ) ) {
            $has_subtab = true;

            $tabs = $settings['subnav'];
            
            /**
             * Filters the About tab default subtab
             * 
             * The dynamic part of the filter's name is the name of the tab
             * 
             * @since 3.0
             * 
             * @param string $default_subtab About tab default subtab
             */
            $default_subtab = apply_filters( "describr_profile_tab_{$tab}_default_subtab", '' );
            
            if ( empty( $default_subtab ) ) {
                $default_subtab = array_key_first( $tabs );
            }
            
            $current_subtab_ = isset( $tabs[ $current_subtab ] ) ? $current_subtab : $default_subtab;
            
            if ( $current_subtab_ !== $current_subtab ) {
                $current_subtab = $current_subtab_;
            }

            /*This filter is documented in wp-content/plugins/describr/includes/actions-filters/actions-profile.php*/
            $tab_url = apply_filters( "describr_profile_tab_url_{$tab}", describr_profile_tab_url( $tab ), $settings, $args );
            ?>
            <div class="describr-profile-menu-tab-content-side describr-profile-menu-tab-content-flex-item-side">
                <div id="describr-profile-about-tablist" class="describr-profile-about-tablist describr-profile-menu-tab-content-flex-item-side-tablist" aria-orientation="vertical" role="tablist">
                    <div class="describr-profile-menu-tab-content-flex-item-side-tablist-heading-wrap">
                        <h2><?php echo esc_html( $settings['name'] ); ?></h2>
                    </div>
                    <?php
                    foreach ( $tabs as $subtab => $tab_args ) {
                        $id = "describr-profile-menu-tab-$tab-$subtab";
                        $tab_ids[$subtab] = $id;
                        $subtab_url = describr()->profile()->subtab_url( $subtab, $tab_url );

                        /** 
                         * Filters the subtab URL
                         * 
                         * The dynamic parts of the filter's name are the names of the
                         * tab and subtab, respectively
                         * 
                         * @since 3.0
                         * 
                         * @param string $url      Subtab URL
                         * @param string $tab_args Subtab data
                         * @param array  $args     Additional arguments passed to the template
                         */
                        $subtab_url = apply_filters( "describr_profile_{$tab}_subtab_url_{$subtab}", $subtab_url, $tab_args, $args );

                        /**
                         * Filters extra subtab attributes
                         * 
                         * The dynamic parts of the filter's name are the names of the
                         * tab and subtab, respectively
                         * 
                         * @since 3.0
                         * 
                         * @param array  $attr     Extra attributes
                         * @param string $tab_args Subtab data
                         * @param array  $args     Additional arguments passed to the template
                         */
                        $subtab_url_attr = apply_filters( "describr_profile_{$tab}_subtab_url_{$subtab}_attrs", array(), $tab_args, $args );
            
                        $subtab_class = '';
                        ?>
                        <div role="none">
                            <a href="<?php echo esc_attr( $subtab_url ); ?>" class="describr-profile-menu-tab-content-subtab describr-profile-menu-tab-content-subtab-about <?php echo esc_attr( $subtab_class ); ?>" role="tab" data-parenttab="about" data-tab="<?php echo esc_attr( $subtab ); ?>" id="<?php echo esc_attr( $id ); ?>" aria-selected="<?php echo $current_subtab === $subtab ? 'true' : 'false';?>"<?php
                            if ( $subtab_url_attr ) {
                                foreach ( $subtab_url_attr as $attr => $attr_val ) {
                                    echo ' ' . esc_attr( $attr ) . '="' . wp_kses_post( $attr_val ) . '"';
                                }
                            }
                            ?>>
                                <?php
                                if ( ! empty( $tab_args['notifier'] ) ) {
                                    echo '<span aria-hidden="true" class="' . esc_attr( "describr-tab-icon describr-profile-subtab-notifier {$tab_args['notifier']}" ) . '"></span>';
                                }

                                if ( ! empty( $tab_args['icon'] ) ) {
                                    echo '<span aria-hidden="true" class="' . esc_attr( "describr-tab-icon describr-profile-subtab-icon {$tab_args['icon']}" ) . '"></span>';
                                }
                                ?>
                                <span class="describr-profile-tab-about-title describr-tab-title"><?php echo esc_html( $tab_args['name'] ); ?></span>
                            </a>
                        </div>
                        <?php
                    }
                    ?>   
                </div>
            </div>
            <?php
        }
        
        //Subtab tabpanels
        foreach ( $tab_ids as $subtab => $id ) {
            echo '<div role="tabpanel" class="describr-profile-menu-tab-content-subtab-content describr-profile-menu-tab-content-flex-item-main describr-profile-menu-tab-content-subtab-content-about';
            if ( $subtab === $current_subtab ) {
                echo ' current';
            }
            echo '" aria-labelledby="' . esc_attr( $id ) . '" tabindex="' . ( $subtab === $current_subtab ? '0' : '-1' ) . '" aria-live="polite"></div>';
        }

        if ( $tab === $current_tab ) {
            if ( ! $has_subtab ) {
                describr()->profile()->current_subtab = false;
            } else {
                describr()->profile()->current_subtab = $current_subtab;
            }
        }
        ?>
    </div>
    <?php
}
add_action( 'describr_profile_menu_tab_content_about', 'describr_profile_menu_tab_content_about', 10, 3 );

/**
 * Outputs Posts tab content
 * 
 * @since 3.0
 * 
 * @since string $current_tab The current tab
 */
function describr_profile_menu_tab_content_posts( $current_tab ) {
    $tab = 'posts';
    ?>
    <div role="tabpanel" tabindex="<?php echo $tab === $current_tab ? '0' : '-1'; ?>" aria-labelledby="describr-profile-menu-tab-posts" class="describr-profile-menu-tab-content describr-profile-menu-tab-content-posts<?php echo $tab === $current_tab ? ' current' : ''; ?>" aria-live="polite">
        <?php
        if ( $tab === $current_tab ) {
            $posts = describr()->profile()->get_posts();
            $found_viewable_posts = count( $posts );
            ?>
            <span class="describr-a11y-text" id="describr-profile-menu-tab-posts-caption"><?php /*translators: Hidden accessibility text.*/ esc_html_e( 'Posts ordered by date. Descending.', 'describr' ); ?></span>
            <span class="describr-a11y-text" id="describr-profile-menu-tab-posts-count">
                <?php
                if ( $found_viewable_posts ) {
                    /*translators: Hidden accessibility text. 1: Number of posts. 2: Total number of posts.*/
                    echo esc_html(
                        sprintf( 
                            __( 'Showing %1$s of %2$s posts.', 'describr' ), 
                            $found_viewable_posts, 
                            number_format_i18n( describr()->profile()->found_results ) 
                        ) 
                    );
                }
                ?>
            </span>
            <div id="describr-profile-menu-tab-posts-search-results">
                 <?php
                if ( describr()->profile()->found_results ) {
                    describr()->profile()->display_posts( true );
                } else {
                    esc_html_e( 'No posts found.', 'describr' );
                }
                ?>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}
add_action( 'describr_profile_menu_tab_content_posts', 'describr_profile_menu_tab_content_posts', 10, 1 );

/**
 * Outputs Comments tab content
 * 
 * @since 3.0
 * 
 * @since string $current_tab The current tab
 */
function describr_profile_menu_tab_content_comments( $current_tab ) {
    $tab = 'comments';
    ?>
    <div role="tabpanel" tabindex="<?php echo $tab === $current_tab ? '0' : '-1'; ?>" aria-labelledby="describr-profile-menu-tab-comments" class="describr-profile-menu-tab-content describr-profile-menu-tab-content-comments<?php echo $tab === $current_tab ? ' current' : ''; ?>" aria-live="polite">
        <?php
        if ( $tab === $current_tab ) {
            $_comments = describr()->profile()->get_comments();
            $found_viewable_comments = count( $_comments->comments );
            ?>
            <span class="describr-a11y-text" id="describr-profile-menu-tab-comments-caption"><?php /*translators: Hidden accessibility text.*/ esc_html_e( 'Comments ordered by date. Descending.', 'describr' ); ?></span>
            <span class="describr-a11y-text" id="describr-profile-menu-tab-comments-count">
                <?php
                if ( $found_viewable_comments ) {
                    /*translators: Hidden accessibility text. 1: Number of comments being displayed. 2: Total number of comments.*/
                    echo esc_html( sprintf( __( 'Showing %1$s of %2$s comments.', 'describr' ), $found_viewable_comments, $_comments->found_comments ) );
                }
                ?>
            </span>
            <div id="describr-profile-menu-tab-comments-search-results">
                <?php
                if ( $_comments->found_comments ) {
                    describr()->profile()->display_comments( true );
                } else {
                    esc_html_e( 'No comments found.' ,'describr' );
                }
                ?>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}
add_action( 'describr_profile_menu_tab_content_comments', 'describr_profile_menu_tab_content_comments', 10, 1 );

/**
 * Enqueues translated strings and settings for the profile
 * 
 * @since 3.0
 */
function describr_enqueue_profile_translated_strings_and_settings() {
    if ( ! describr_is_profile_viewable_() ) {
        return;
    }
    
    $user_id = describr_profile_id();

    $charset = get_bloginfo( 'charset' );

    $html_entity_decode = function ( $str ) use ( $charset ) {
        return html_entity_decode( $str, ENT_QUOTES, $charset );
    };

    $settings = array( 'nonces' => array( 'profileSchema' => wp_create_nonce( 'describr-profile-schema_' . $user_id ) ) );
    
    $strings  = array(        
        'loading' => /*translators: Hidden accessibility text.*/ $html_entity_decode(__( 'Loading&#8230;', 'describr' )),
        'error'   => __( 'Error:', 'describr' ),
        'dismiss' => __( 'Dismiss this notice', 'describr' ),
    );
    
    $tabs = describr()->profile()->get_allowed_tabs();
    
    $tab_loading_str = /*translators: Hidden accessibility text. %s: Name of tab.*/ $html_entity_decode(__( 'Loading %s&#8230;', 'describr' ));
    
    foreach ( $tabs as $t_key => $t_settings ) {
        if ( isset( $t_settings['name'] ) ) {
            $strings['tabLoading'][ $t_key ] = sprintf( $tab_loading_str, $t_settings['name'] );
        }

        if ( isset( $t_settings['subnav'] ) ) {
            foreach ( $t_settings['subnav'] as $ts_key => $ts_settings ) {
                if ( isset( $ts_settings['name'] ) ) {
                    $strings['tabLoading'][ $ts_key ] = sprintf( $tab_loading_str, $ts_settings['name'] );
                }
            }
        }
    }
    
    $display_name = describr_display_name( $user_id );

    if ( describr_can_send_message( $user_id ) ) {
        //Redirected after logging in
        if ( isset( $_GET['send_msg_to'] ) && (int) $_GET['send_msg_to'] === $user_id ) {
            $trigger_message_modal = true;
        }

        $settings['nonces']['sendMessage'] = wp_create_nonce( 'describr-profile-message_' . $user_id );
        
        $settings['_check'] = describr()->honeypot;
        
        $strings['message'] = array(
            'required' => _x( 'A message is required.', 'email', 'describr' ),
            'sending'  => $html_entity_decode(_x( 'Sending&#8230;', 'email', 'describr' )),
            'sending_' => $html_entity_decode(_x( 'Sending message&#8230;', 'email', 'describr' )),
            'send'     => /*translators: Email.*/ _x( 'Send', 'verb', 'describr' ),
        );
    } 
    
    if ( isset( $_POST['describr_trigger_message_modal'] ) ) {
        $trigger_message_modal = true;
    }   
    
    if ( isset( $trigger_message_modal ) ) {
        wp_add_inline_script( 'describr-profile', 'var describrTriggerMessageModal=true;', 'before' );
    }

    $tabs = array_keys( $tabs ); 

    $settings['activeTabs'] = $tabs; 

    $js = sprintf( 
        'describr.profile.ID = %1$s;
        describr.profile.activeTab = %2$s;
        describr.profile.activeSubtab = %3$s;
        describr.profile.queriedSubtab = %4$s;', 
        $user_id,
        json_encode( describr()->profile()->current_tab ),
        json_encode( describr()->profile()->current_subtab ),
        json_encode( get_query_var( 'describr_subtab', false ) )
    );
    
    if ( describr_can_take_profile_picture_actions() ) {
        $settings['nonces']['deletePicture'] = wp_create_nonce( "describr-remove-picture_{$user_id}" );
                
        if ( describr_can_upload() && did_action( 'wp_enqueue_media' ) ) {
            $settings['nonces']['assignPicture'] = wp_create_nonce( "describr-assign-from-media-library-picture_{$user_id}" );
        }
        
        $strings['picture'] = array( 
            'frameTitle'  => __( 'Choose a Picture', 'describr' ), 
            'frameButton' => __( 'Choose a Picture', 'describr' ),
            'deleting'    => sprintf(
                /*translators: User's display name.*/
                $html_entity_decode(__( 'Deleting %s profile picture&#8230;', 'describr' )), 
                $display_name 
            ),
            'deleted'    => sprintf(
                /*translators: User's display name.*/
                __( '%s profile picture deleted.', 'describr' ), 
                $display_name 
            ),
            'assigned'    => sprintf(
                /*translators: User's display name.*/
                __( 'Profile picture assigned to %s.', 'describr' ), 
                $display_name 
            ),
        );
    }

    $paging_tab = array();
    $paging_tabs_rewrite_base = array();

    if ( in_array( 'posts', $tabs, true ) ) {
        $strings['postsCaption'] = /*translators: Hidden accessibility text.*/ __( 'Posts ordered by date. Descending.', 'describr' );
        $strings['postsShow'] = /*translators: Hidden accessibility text. 1: Number of posts being displayed. 2: Total number of posts.*/ __( 'Showing %1$s of %2$s posts.' );

        $paging_tab[] = 'posts'; 
        
        global $wp_rewrite;

        $paging_tabs_rewrite_base['posts'] = trim( $wp_rewrite->pagination_base, '/' );
    }

    if ( in_array( 'comments', $tabs, true ) ) {
        /*translators: Hidden accessibility text.*/
        $strings['commentsCaption'] = __( 'Comments ordered by date. Descending.', 'describr' );
        /*translators: Hidden accessibility text. 1: Number of comments being displayed. 2: Total number of comments.*/
        $strings['commentsShow']    = __( 'Showing %1$s of %2$s comments.', 'describr' );

        $paging_tab[] = 'comments';

        $paging_tabs_rewrite_base['comments'] = describr()->rewrite()->author_comments_pagination_base . '-';
    }

    /**
     * Filters the paging tabs
     * 
     * @since 3.0
     * 
     * @param array  $paging_tab Paging tabs
     * @param string $user_id    ID of the user to whom the profile belongs
     */
    $settings['pagingTabs'] = apply_filters('describr_profile_paging_tabs', $paging_tab, $user_id );
    
    /**
     * Filters the paging tabs rewrite bases
     * 
     * @since 3.0
     * 
     * @param array  $paging_tabs_rewrite_base Paging tabs rewrite bases
     * @param string $user_id                  ID of the user to whom the profile belongs
     */
    $settings['pagingTabsRewriteBase'] = apply_filters('describr_profile_paging_tabs_rewrite_base', $paging_tabs_rewrite_base, $user_id );
    
    if ( describr_can_block_user( $user_id ) ) {
        $settings['nonces']['blockUser'] = wp_create_nonce( 'describr-block-profile_' . $user_id );
        $strings['blockUser'] = array(
            'block'      => array( 
                'label' => sprintf(
                    /*translators: User's display name.*/
                    _x( 'Block %s', 'restrict user access', 'describr' ), 
                    $display_name
                ), 
                'title' => _x( 'Block', 'restrict user access', 'describr' ),
            ), 
            'unblock'    => array(
                'label' => sprintf(
                    /*translators: User's display name.*/
                    _x( 'Unblock %s', 'allow user access', 'describr' ), 
                    $display_name
                ),
                'title' => _x( 'Unblock', 'allow user access', 'describr' ),
            ),
            'blocking'   => sprintf( 
                /*translators: User's display name.*/
                $html_entity_decode(_x( 'Blocking %s&#8230;', 'restrict user access', 'describr' )), 
                $display_name
            ),
            'unblocking' => sprintf( 
                /*translators: User's display name.*/
                $html_entity_decode(_x( 'Unblocking %s&#8230;', 'allow user access', 'describr' )), 
                $display_name
            ), 
        );
    }
    
    if ( get_option( 'describr_enable_social_media' ) ) {
        $socials = describr_get_socials();
        $settings['socials'] = array_keys( $socials );
    }
    
    if ( describr_can_edit_profile() ) { 
        $settings['userCan'] = current_user_can( 'edit_users' ) && current_user_can( 'edit_user', $user_id );
        $settings['timezones'] = describr_timezone_choice( describr_get_blog_locale() );
        
        $settings['choice'] = array(
            'country'  => describr_translated_countries(),
            'status'   => describr_translated_relationship_status(),
            'gender'   => describr_translated_gender(),
            '_privacy' => describr_translated_privacy(),
            '_status'  => describr_tanslated_field_status(),
        );

        $settings['locales']          = describr_translated_locale();        
        $settings['_check']           = describr()->honeypot;        
        $strings['listItemSeparator'] = wp_get_list_item_separator();

        $strings['errorMessages'] = array(
            'illegalChars' => array(
                'digits'    => /*translators: %s: Field label.*/ __( '%s can only contain digits.', 'describr' ),
                'line'      => /*translators: %s: Field label.*/ __( '%s can only contain lowercase alphabetic characters, numbers, periods (.), hyphens (-), and underscores (_).', 'describr' ),
                'kakaotalk' => /*translators: %s: Field label.*/ __( '%s cannot contain spaces.', 'describr' ),
            ),
            'limits' => array(
                'upper' => /*translators: 1: Field label. 2: Maximum number of characters.*/ __( '%1$s cannot have more than %2$d characters.', 'describr' ),
                'lower' => /*translators: 1: Field label. 2: Minimum number of characters.*/ __( '%1$s must have at least %2$d characters.', 'describr' ),
                'both'  => /*translators: 1: Field label. 2: Minimum number of characters. 3: Maximum number of characters.*/ __( '%1$s must have at least %2$d characters and have at most %3$d characters.', 'describr' ),
                'exact' => /*translators: 1: Field label. 2: Number of characters.*/ __( '%1$s must have %2$d characters.', 'describr' ),
            ),
            'field' => array(
                'required'    => /*translators: %s: Field label.*/ __( '%s is required.', 'describr' ),
                'invalid'     => /*translators: %s: Field label.*/ __( '%s is invalid.', 'describr' ),
                'moreInfo'    => /*translators: %s: Field label.*/ __( '%s requires more information.', 'describr' ),
                'nochange'    => __( 'You did not make any changes. Please try again.', 'describr' ),
                'howToDelete' => _x( 'You can delete it from the More Options menu.', 'profile field' ,'describr' ),
            ),
            'phoneNumber' => array(
                'NOT_A_NUMBER'     => /*translators: %s: Field label.*/ __( 'No %s was supplied. Phone number must have area code and cannot include alphabetic characters.', 'describr' ),
                'INVALID_COUNTRY'  => /*translators: %s: Field label.*/ __( '%s country is invalid. Make sure to add the correct area code.', 'describr' ),
                'TOO_SHORT'        => /*translators: %s: Field label.*/ __( '%s is too short.', 'describr' ),
                'TOO_LONG'         => /*translators: %s: Field label.*/ __( '%s is too long.', 'describr' ),
                'INCORRECT_FORMAT' => /*translators: %s: Field label.*/ __( '%s format does not match that of the country selected.', 'describr' ),
            ),
            'errors'   => __( 'One or more fields have errors.', 'describr' )
        );    
        
        $strings['defaultVal'] = array(
            'social'   => __( 'Select a social network', 'describr' ),
            'country'  => __( 'Select a country', 'describr' ),
            'status'   => __( 'Select a status', 'describr' ),
            'gender'   => __( 'Select a gender', 'describr' ),
            'timezone' => __( 'Select a city', 'describr' ),
            'locale'   => __( 'Select a language', 'describr' ),
        );

        $strings['specialCities'] = array(
            'boston'       => /*translators: City.*/ __( 'Boston', 'describr' ),
            'kingston'     => /*translators: City.*/ __( 'Kingston', 'describr' ),
            'santoDomingo' => /*translators: City.*/ __( 'Santo Domingo', 'describr' ),
        );        
        
        $strings['baseUrl'] = /*translators: Hidden accessibility text.*/ __( 'Base URL', 'describr' );        
        $strings['requiredSymbol'] = _x( '*', 'required symbol', 'describr' );        
        $strings['required'] = _x( 'Required', 'profile field', 'describr' );        
        
        $strings['socialLabel'] = /*translators: %s: Name of social network. For what is "handle", see https://www.hostinger.com/ca/tutorials/what-is-social-media-handle.*/ _x( '%s handle', 'user', 'describr' );
                
        $strings['edit']   = _x( 'Edit', 'profile field', 'describr' );
        $strings['delete'] = _x( 'Delete', 'profile field', 'describr' );
        
        $strings['moreOptionsMenuLabel'] = array(
            'whichIs' => /*translators: 1: Field label. 2: Field value.*/ __( 'Actions for %1$s, which is %2$s', 'describr' ),
            'noWhichIs' => /*translators: %: Field value.*/ __( 'Actions for %s', 'describr' ),
        );

        $strings['emailEx'] = _x( 'Correct email address format: <strong>contact@example.com</strong>.', 'email address example', 'describr' );
        $strings['failReq'] = __( 'Request failed.', 'describr' );
        $strings['updated'] = __( 'Profile updated.', 'describr' );
        $strings['fieldErrors'] = __( 'One or more fields have errors.', 'describr' );
        $strings['updating'] = $html_entity_decode(__( 'Updating profile&#8230;', 'describr' ));
        $strings['update'] = __( 'Update Profile', 'describr' );
        
        $strings['custom'] = __( 'Custom', 'describr' );
        $strings['customDisplayName'] = __( 'Custom Display Name', 'describr' );
        $strings['options'] = _x( 'Options', 'profile field', 'describr' );
        $strings['confirm'] = __( 'You have unsaved changes. Leave page?', 'describr' );
        
        $strings['listBoxLabel'] = array( 
            '_privacy' => /*translators: %s Field label.*/ _x( '%s audience', 'profile field', 'describr' ),
            '_status'  => /*translators: %s Field label.*/ _x( '%s availability', 'profile field', 'describr' ),
        );

        $strings['other'] = _x( 'Other', 'choose a different item', 'describr' );
        
        /**
         * Filters the editable profile fields
         * 
         * @since 3.0
         *
         * @param array $editable_fields Editable fields
         * @param int   $user_id         ID of the user to whom the profile belongs
         */
        $editable_fields = apply_filters( 'describr_profile_get_edit_fields', describr_editable_fields(), $user_id );
        
        unset($editable_fields['single_user_pass'], $editable_fields['user_pass']);

        foreach ( $editable_fields as $field => $settings_ ) {
            /**
             * Filters an editable profile field settings
             * 
             * This filter is also applied in the admin interface. There
             * $user_id has a value of zero (0)
             * 
             * The dynamic part of the filter's name is the field's name
             * 
             * @since 3.0
             *
             * @param array $settings_ Field settings
             * @param int   $user_id   ID of the user to whom the profile belongs
             */
            $settings_ = apply_filters( "describr_profile_get_edit_field_{$field}", $settings_, $user_id );

            if ( isset( $settings_['supports'] ) ) {
                foreach ( $settings_['supports'] as $support => $support_settings ) {
                    /**
                     * Filters an editable profile field's support's settings
                     * 
                     * The dynamic part of the filter's name is the support's name
                     * 
                     * @since 3.0
                     *
                     * @param array $support_settings Support settings
                     * @param int   $user_id          ID of the user to whom the profile belongs
                     */
                    $support_settings = apply_filters( "describr_profile_get_edit_field_support_{$support}", $support_settings, $user_id );
                    
                    /**
                     * Filters an editable profile field's support's settings
                     * 
                     * The dynamic parts of the filter's name are the field and the support's name
                     * 
                     * @since 3.0
                     *
                     * @param array $support_settings Support settings
                     * @param int   $user_id          ID of the user to whom the profile belongs
                     */
                    $support_settings = apply_filters( "describr_profile_get_edit_field_{$field}_support_{$support}", $support_settings, $user_id );
                    
                    $settings_['supports'][ $support ] = $support_settings;
                }
            }

            $editable_fields[ $field ] = $settings_;
        }
        
        $url_nonces = array();

        $is_nicename_locked = describr_is_nicename_locked( $user_id );
        
        if ( ! $is_nicename_locked ) {
            $_nonce = wp_create_nonce( 'describr-edit-nicename_' . $user_id );
            $settings['nonces']['nicename'] = $_nonce;
            $url_nonces['nicename'] = rawurlencode( $_nonce );
        }
            
        $saved_asides_fields = array_keys( $editable_fields );

        $photo_key = describr_photo_key();
        
        if ( isset( $editable_fields[ $photo_key ] ) ) {
            $saved_asides_fields[] = $photo_key;
            
            $settings['nonces']['ratePicture'] = wp_create_nonce( "describr-rate-picture_{$user_id}" );

            $settings['ratings'] = describr()->avatar()->ratings;
            
            $strings['photo'] = array(
                'ratings'     => array_combine( $settings['ratings'], array(
                    /*translators: Content suitability rating: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system.*/
                    __( 'G &#8212; Suitable for all audiences', 'describr' ),
                    /*translators: Content suitability rating: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system.*/
                    __( 'PG &#8212; Possibly offensive, usually for audiences 13 and above', 'describr' ),
                    /*translators: Content suitability rating: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system.*/
                    __( 'R &#8212; Intended for adult audiences above 17', 'describr' ),
                    /*translators: Content suitability rating: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system.*/
                    __( 'X &#8212; Even more mature than above', 'describr' ),
                )),
                'rating' => array(
                    'screenReader' => array(
                        'rating' => $html_entity_decode(
                            /*translators: Content suitability rating: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system.*/
                            __( 'Rating profile picture&#8230;', 'describr' )),
                        'rated'  => /*translators: %s: Content suitability rating: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system.*/ __( 'Profile picture rated %s.', 'describr' ),
                    ),
                    'rating' => $html_entity_decode(
                        /*translators: Content suitability rating: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system.*/
                        _x( 'Rating&#8230;', 'profile picture', 'describr' )),
                    'rate'   => /*translators: Content suitability rating: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system.*/ _x( 'Rate', 'profile picture', 'describr' ),
                ),
                'privacy' => array( 'label' => /*translators: 1: Field label. 2: Field audience. For example, "Public".*/ __( 'Edit audience for %1$s. Currently sharing with %2$s', 'describr' ) ),
                'status'  => array(
                    'perm'    => /*translators: 1: Field label. 2: Field availability. For example, "Available".*/ __( 'Edit availability for %1$s. Currently %2$s', 'describr' ),
                    'no_perm' => /*translators: 1: Field label. 2: Field availability. For example, "Available".*/ __( '%1$s is currently %2$s', 'describr' ),
                ),
                'audit'   => array( 'label' => __( 'View profile picture administration', 'describr' ) ),
                'actions' => array(
                    'upload' => __( 'Upload Picture', 'describr' ),
                    'change' => __( 'Change Picture', 'describr' ),
                    'delete' => __( 'Delete Picture', 'describr' ),
                    'rate'   => __( 'Rate Picture', 'describr' ),
                ),
            );
        }

        /**
         * Filters the editable profile fields saved asides
         * 
         * @since 3.0
         *
         * @param array $saved_asides        Saved asides
         * @param array $saved_asides_fields Editable fields
         * @param int   $user_id             ID of the user to whom the profile belongs
         */
        $saved_asides = apply_filters( 'describr_profile_edit_fields_saved_asides', array(), $saved_asides_fields, $user_id );
        
        if ( $saved_asides ) {
            $js .= sprintf( 'describr.profile.savedAsides = %s;', json_encode( $saved_asides ) );
        }
        
        $_nonce = wp_create_nonce( 'describr-edit-profile_' . $user_id );
        $settings['nonces']['editProfile'] = $_nonce;
        $url_nonces['editProfile'] = rawurlencode( $_nonce );       
        
        $settings['urlNonces'] = $url_nonces;
        
        if ( isset( $editable_fields['user_nicename'] ) && $is_nicename_locked ) {
            $editable_fields['user_nicename']['editable'] = false;
        }

        $settings['fieldsToEdit'] = $editable_fields;

        if ( isset( $socials ) ) {
            $editable_socials = array_intersect_key( $editable_fields, $socials );
            $settings['editableSocials'] = array_keys( $editable_socials ); 
        }
        
        $date_formats = describr_date_formats();

        $settings['dateFormats'] = array_reverse( $date_formats );
        $settings['dateFormatSep'] = describr_date_sep();
        
        $settings['specialA11yFields'] = describr_profile_field_edit_mode_html();

        $strings['dateFormatEx'] = sprintf(
            /*translators: %s: Date format examples.*/
            _x( 'Correct date formats: %s.', 'date format examples', 'describr' ),
            implode( wp_get_list_item_separator(), $date_formats )
        );

        $settings['asides'] = describr()->remove_cap( describr()->get_asides() );

        $strings['city'] = _x( 'City', 'user', 'describr' );
        $strings['job'] = _x( 'Job', 'user', 'describr' );
        $strings['langs'] = _x( 'Languages', 'user', 'describr' );
        $strings['remove'] = _x( 'Remove', 'verb', 'describr' );
        
        $strings['aside'] = array(
            'modal' => array(
                'title' => array(
                    'privacy' => _x( 'Select Audience', 'profile field', 'describr'),
                    'status'  => _x( 'Select Availability', 'profile field', 'describr' ),
                ),
                'label' => array(
                    'privacy' => array( 
                        'whichIs'   => /*translators: 1: Field label. 2: Field value. 3: Field audience.*/ _x( 'Select audience for %1$s, which is %2$s. Currently sharing with %3$s', 'profile field', 'describr'), 
                        'noWhichIs' => /*translators: 1: Field label or field value. 2: Field audience.*/ _x( 'Select audience for %1$s. Currently sharing with %2$s', 'profile field', 'describr'),
                    ),
                    'status'  => array( 
                        'whichIs'   => /*translators: 1: Field label. 2: Field value. 3: Field availability.*/ _x( 'Select availability for %1$s, which is %2$s. Currently %3$s', 'profile field', 'describr' ), 
                        'noWhichIs' => /*translators: 1: Field label or field value. 2: Field availability.*/ _x( 'Select availability for %1$s. Currently %2$s', 'profile field', 'describr'), 
                    ),
                    'audit'   => array( 
                        'whichIs'   => /*translators: 1: Field label. 2: Field value.*/ _x( 'Administration for %1$s, which is %2$s', 'profile field', 'describr' ),
                        'noWhichIs' => /*translators: %s: Field label or field value.*/ _x( 'Administration for %s', 'profile field', 'describr' ),
                    ),
                ),
                'caption' => array(
                    'audit' => array(
                        'whichIs'   => /*translators: 1: Field label. 2: Field value.*/ __( 'Table Ordered by Administration Date for %1$s, which is %2$s. Descending.', 'describr' ),
                        'noWhichIs' => /*translators: Hidden accessibility text.%s: Field label.*/ __( 'Table Ordered by Administration Date for %s. Descending.', 'describr' ),
                    ),
                ),
            ),
        );
        
        $strings['fieldEditor'] = array(
            'whichIs'   => /*translators: 1: Field label. 2: Field value.*/ __( 'Edit %1$s, which is %2$s', 'describr' ),
            'noWhichIs' => /*translators: %: Field value.*/ __( 'Edit %s', 'describr' ),
            'add'       => /*translators: %s: Field title.*/ _x( 'Add %s', 'profile field', 'describr' ),
            'update'    => /*translators: %s: Field title.*/ _x( 'Update %s', 'profile field', 'describr' ),
        );

        $strings['viewAdmin'] = _x( 'View Administration', 'profile field', 'describr' );
    }
    
    if ( isset( $settings['nonces']['sendMessage'] ) || isset( $settings['_check'] ) ) {
        $strings['check'] = __( 'It seems as if this action is not taken by a human being.', 'describr' );
    }

    /**
     * Filters the profile strings
     *
     * @since 3.0
     *
     * @param array $strings Array of profile strings keyed by the name they'll be referenced by in JavaScript
     * @param int   $user_id The profile ID
     */
    $strings = apply_filters( 'describr_profile_strings', $strings, $user_id );

    wp_localize_script( 'describr-main', 'describrL10n', $strings );

    /**
     * Filters the profile settings
     *
     * @since 3.0
     *
     * @param array $settings List of profile settings
     * @param int   $user_id  The profile ID
     */
    $settings = apply_filters( 'describr_profile_settings', $settings, $user_id );
    
    wp_add_inline_script( 'describr-main', sprintf( 'describr.settings = %1$s;%2$s', json_encode( $settings ), $js ), 'before' );
}
add_action( 'wp_footer', 'describr_enqueue_profile_translated_strings_and_settings', 4 );

/**
 * Retrieves and prints the fully translated states/provinces and cities in JavaScript
 * 
 * @since 3.0
 */
function describr_print_translated_states_and_cities_script() {
    if ( ! describr_can_edit_profile() ) {
        return;
    }
    
    wp_print_inline_script_tag( 'var describrStatesCities = [];' );
    
    $dir_sep = DIRECTORY_SEPARATOR;

    foreach ( glob( DESCRIBR_DIR . "includes{$dir_sep}cities-locale{$dir_sep}cities-[1-9]*.php" ) as $file ) {
        if ( file_exists( $file ) ) {
            $locales = include_once $file;
            wp_print_inline_script_tag( sprintf( 'describrStatesCities.push(%s);', json_encode( $locales ) ) );
        }
    }
}
add_action( 'wp_footer', 'describr_print_translated_states_and_cities_script', PHP_INT_MAX );

/**
 * Outputs profile modals
 * 
 * @since 3.0
 */
function describr_print_profile_modals() {
    if ( ! describr_is_profile_viewable_() ) {
        return;
    }

    if ( describr_can_edit_profile() ) {
        ?>
        <div id="describr-profile-field-editor-modal-wrapper" class="describr-profile-field-editor-modal describr" tabindex="-1">
            <div role="dialog" aria-modal="true" tabindex="0" aria-labelledby="describr-profile-field-editor-modal-label" id="describr-profile-field-editor-modal">
                <span class="describr-a11y-text" id="describr-profile-field-editor-modal-label"></span>
                <form id="describr-profile-field-editor-modal-wrap">
                    <h1 id="describr-profile-field-editor-modal-title"></h1>
                    <button type="button" id="describr-profile-field-editor-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'describr' ); ?>" class="describr-close-modal"><span aria-hidden="true" class="dashicons dashicons-no-alt"></span></button>
                    <div id="describr-profile-field-editor-modal-main" data-main="true"><?php wp_editor( '', 'describr-profile-mce', array( 'media_buttons' => false, 'textarea_rows' => 5, ) ); ?></div>
                    <input type="hidden" name="<?php echo esc_attr( describr()->honeypot ); ?>" value="" />
                    <div id="describr-profile-field-editor-modal-footer">
                        <div id="describr-profile-field-editor-modal-footer-submit-btn-wrap"><?php describr_primary_button( __( 'Update Profile', 'describr' ) ); ?></div>
                        <div id="describr-profile-field-editor-modal-footer-cancel-btn-wrap"><?php describr_secondary_button(); ?></div>
                    </div>
                </form>
            </div>
            <div class="describr-modal-backdrop"></div>
        </div>
        
        <div id="describr-profile-select-aside-modal-wrapper" class="describr-profile-select-aside-modal describr" tabindex="-1">
            <div role="dialog" aria-modal="true" tabindex="0" aria-labelledby="describr-profile-select-aside-modal-label" id="describr-profile-select-aside-modal">
                <span class="describr-a11y-text" id="describr-profile-select-aside-modal-label"></span>
                <form id="describr-profile-select-aside-modal-wrap">
                    <h1 id="describr-profile-select-aside-modal-title"></h1>
                    <button type="button" id="describr-profile-select-aside-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'describr' ); ?>" class="describr-close-modal"><span aria-hidden="true" class="dashicons dashicons-no-alt"></span></button>
                    <div id="describr-profile-select-aside-modal-main" data-main="true">
                        <p aria-hidden="true" id="describr-profile-select-aside-modal-main-field" class="describr-no-overflow"></p>
                    </div>
                    <input type="hidden" name="<?php echo esc_attr( describr()->honeypot ); ?>" value="" />
                    <div id="describr-profile-select-aside-modal-footer">
                        <div id="describr-profile-select-aside-modal-footer-submit-btn-wrap"><?php describr_primary_button( __( 'Update Profile', 'describr' ) ); ?></div>
                        <div id="describr-profile-select-aside-modal-footer-cancel-btn-wrap"><?php describr_secondary_button(); ?></div>
                    </div>
                </form>
            </div>
            <div class="describr-modal-backdrop"></div>
        </div>
        
        <?php
        if ( array_intersect_key( describr_editable_fields(), describr_get_socials() ) ) {
            ?>
            <div id="describr-profile-select-social-network-modal-wrapper" class="describr-profile-select-social-network-modal describr" tabindex="-1">
                <div role="dialog" tabindex="0" aria-labelledby="describr-profile-select-social-network-modal-label" id="describr-profile-select-social-network-modal">
                    <form id="describr-profile-select-social-network-modal-wrap" onsubmit="return false;">
                        <h1 id="describr-profile-select-social-network-modal-title"><?php echo esc_html_x( 'Select a Social Network', 'header', 'describr' ); ?></h1>
                        <button type="button" id="describr-profile-select-social-network-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'describr' ); ?>" class="describr-close-modal"><span aria-hidden="true" class="dashicons dashicons-no-alt"></span></button>
                        <div id="describr-profile-select-social-network-modal-main">
                            <label data-for="describr-profile-select-social-network" for="describr-profile-select-social-network"><?php esc_html_e( 'Social Network', 'describr' ); ?></label>
                            <p><select id="describr-profile-select-social-network" title="<?php esc_attr_e( 'Select a Social Network', 'describr' ); ?>" data-editaction="social_network"></select></p>
                        </div>
                        <div id="describr-profile-select-social-network-modal-footer">
                            <div id="describr-profile-select-social-network-modal-footer-cancel-btn-wrap"><?php describr_secondary_button(); ?></div>
                        </div>
                    </form>
                </div>
                <div class="describr-modal-backdrop"></div>
            </div>
            <?php
        }
        ?>

        <?php describr_print_field_admin_modal_template( 'profile' ); ?>
        
        <div class="describr describr-popper-wrapper"></div>
        
        <div id="describr-popup-center" class="describr"></div>
        <?php
        $center_alert = true;
    }

    if ( describr_can_send_message() ) {
        $display_name = describr_display_name( describr_profile_id() );
        
        /**
         * Filters whether to allow spellcheck when sending message
         *
         * @since 3.0
         *
         * @param false Whether to allow spellcheck when sending message
         */
        $allow_spellcheck = apply_filters( 'describr_profile_message_allow_spellcheck', false );
                
        /**
         * Filters whether to allow autocomplete when sending message
         *
         * @since 3.0
         *
         * @param false Whether to allow autocomplete when sending message
         */
        $allow_autocomplete = apply_filters( 'describr_profile_message_allow_autocomplete', false );
        ?>
        <div id="describr-profile-message-modal-wrapper" class="describr-profile-message-modal describr" tabindex="-1">
            <div role="dialog" aria-modal="true" tabindex="0" aria-labelledby="describr-profile-message-modal-label" id="describr-profile-message-modal">
                <span class="describr-a11y-text" id="describr-profile-message-modal-label"><?php echo esc_html(sprintf(
                    /*translators: User's display name.*/
                    _x( 'Send message to %s', 'email', 'describr' ), 
                    $display_name
                    )); ?></span>
                <form id="describr-profile-message-modal-wrap">
                    <h1 id="describr-profile-message-modal-title"><?php echo esc_html_x( 'New Message', 'email', 'describr' ); ?></h1>
                    <button type="button" id="describr-profile-message-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'describr' ); ?>" class="describr-close-modal"><span aria-hidden="true" class="dashicons dashicons-no-alt"></span></button>
                    <div id="describr-profile-message-modal-main" data-main="true">
                        <span class="describr-a11y-text"><?php echo esc_html(sprintf(
                            /*translators: Hidden accessibility text. User's display name.*/
                            _x( 'To %s.', 'email', 'describr' ),
                            $display_name
                            )); ?></span>
                        <div aria-hidden="true" style="margin-bottom: 4px"><?php echo '<span id="describr-profile-message-to-label">' . esc_html_x( 'To:', 'email', 'describr' ) . '</span> <span id="describr-profile-message-to-name">' . esc_html( $display_name ) . '</span>' ; ?></div>
                        <label for="describr-profile-message-subject"><?php echo esc_html_x( 'Subject:', 'email', 'describr' ); ?></label> <input type="text" name="subject" id="describr-profile-message-subject" autocomplete="<?php echo $allow_autocomplete ? 'on' : 'off'; ?>" spellcheck="<?php echo $allow_spellcheck ? 'true' : 'false'; ?>" />
                        <label class="describr-a11y-text" id="describr-profile-message-label" for="describr-profile-message-mce"><?php echo esc_html_x( 'Message', 'email', 'describr' ); ?></label>
                        <p id="describr-profile-message-error" class="describr-error" role="alert"></p>
                        <?php wp_editor( '', 'describr-profile-message-mce', array( 'media_buttons' => false ) ); ?>
                        <textarea row="5" cols="40" name="describr-profile-message" id="describr-profile-message" title="<?php echo esc_attr_x( 'Message', 'email', 'describr' ); ?>" autocomplete="<?php echo $allow_autocomplete ? 'on' : 'off'; ?>" spellcheck="<?php echo $allow_spellcheck ? 'true' : 'false'; ?>"></textarea>
                    </div>
                    <input type="hidden" name="<?php echo esc_attr( describr()->honeypot ); ?>" value="" />
                    <div id="describr-profile-message-modal-footer">
                        <div id="describr-profile-message-modal-footer-submit-btn-wrap"><?php describr_primary_button( _x( 'Send', 'email', 'describr' ) ); ?></div>
                        <div id="describr-profile-message-modal-footer-cancel-btn-wrap"><?php describr_secondary_button(); ?></div>
                    </div>
                </form>
            </div>
            <div class="describr-modal-backdrop"></div>
        </div>
        <?php
        $script = "var describrMessageActive=true";

        if ( $allow_spellcheck ) {
            $script .= ",describrMessageSpellcheck=true";
        }
        if ( $allow_autocomplete ) {
            $script .= ",describrMessageAutocomplete=true";
        }
        
        $script .= ';';

        wp_print_inline_script_tag( $script );

        if ( ! isset( $center_alert ) ) {
            echo '<div id="describr-popup-center" class="describr"></div>';
        }
    }
}
add_action( 'wp_footer', 'describr_print_profile_modals', 5 );

/**
 * Prints profile picture modal
 * 
 * @since 3.0
 */
function describr_print_profile_picture_modal() {
    if ( ! describr_can_take_profile_picture_actions() ) {
        return;
    }
    ?>
    <div id="describr-profile-picture-modal-wrapper" class="describr-profile-picture-modal describr" tabindex="-1">
        <div role="dialog" aria-modal="true" aria-labelledby="describr-profile-picture-modal-title" tabindex="0" id="describr-profile-picture-modal">
            <div id="describr-profile-picture-modal-wrap">
                <h1 id="describr-profile-picture-modal-title"><?php esc_html_e( 'Update Profile Picture', 'describr' ); ?></h1>
                <button type="button" id="describr-profile-picture-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'describr' ); ?>" class="describr-close-modal"><span aria-hidden="true" class="dashicons dashicons-no-alt"></span></button>
                <div id="describr-profile-picture-modal-main"></div>
                <div id="describr-profile-picture-modal-footer">
                    <div id="describr-profile-picture-modal-footer-cancel-btn-wrap"><?php describr_secondary_button(); ?></div>
                </div>
            </div>
        </div>
        <div class="describr-modal-backdrop"></div>
    </div>
    <?php
}
add_action( 'wp_footer', 'describr_print_profile_picture_modal' );

/**
 * Prints profile picture rating modal
 * 
 * @since 3.0.1
 */
function describr_print_profile_picture_rating_modal() {
    if ( ! describr_can_take_profile_picture_actions() ) {
        return;
    }
    ?>
    <div id="describr-profile-picture-rating-modal-wrapper" class="describr-profile-picture-rating-modal describr" tabindex="-1">
        <div role="dialog" aria-modal="true" tabindex="0" aria-labelledby="describr-profile-picture-rating-modal-title" id="describr-profile-picture-rating-modal">
            <form id="describr-profile-picture-rating-modal-wrap">
                <h1 id="describr-profile-picture-rating-modal-title"><?php esc_html_e( 'Rate Profile Picture', 'describr' ); ?></h1>
                <button type="button" id="describr-profile-picture-rating-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'describr' ); ?>" class="describr-close-modal"><span aria-hidden="true" class="dashicons dashicons-no-alt"></span></button>
                <div id="describr-profile-picture-rating-modal-main"></div>
                <input type="hidden" name="<?php echo esc_attr( describr()->honeypot ); ?>" value="" />
                <div id="describr-profile-picture-rating-modal-footer">
                    <div id="describr-profile-picture-rating-modal-footer-submit-btn-wrap"><?php describr_primary_button( _x( 'Rate', 'profile picture', 'describr' ) ); ?></div>
                    <div id="describr-profile-picture-rating-modal-footer-cancel-btn-wrap"><?php describr_secondary_button(); ?></div>
                </div>
            </form>
        </div>
        <div class="describr-modal-backdrop"></div>
    </div>
    <?php
}
add_action( 'wp_footer', 'describr_print_profile_picture_rating_modal' );
