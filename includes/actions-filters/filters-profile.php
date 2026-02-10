<?php
/**
 * Filters for the front end profile.
 * Formats fields' values with HTML.
 * Also format values for accessibility.
 * 
 * @package Describr
 * @since 3.0
 */

//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Marks up URLs
 * 
 * @since 3.0
 * 
 * @param string $url      The URL
 * @param array  $settings The field settings
 * @param string $field    The field
 * @return string The marked up URL or empty string
 */
function describr_profile_field_url( $url, $settings, $field ) {
    if ( 'user_url' === $field ) {
        return $url;
    }
    
    if ( ! is_scalar( $url ) || describr_is_saved_data_empty( $url ) ) {
        /*Test if field is not a social network so that the Add Social Network button is added in wp-content/plugins/describr/includes/class-profile.php if no social network exists*/
        if ( ! describr_is_social( $field ) ) {
            describr_set_no_data( $field );
        }

        return '';
    }
    
    $base = isset( $settings['base_url'] ) ? $settings['base_url'] : '';
        
    $text = '';  

    if ( describr_is_social( $field ) && $base ) {
        foreach ( (array) $base as $base_ ) {
            if ( 0 === mb_stripos( $url, $base_ ) ) {
                $text = describr_get_handle_from_url( $base_, $url );
                break;
            }
        }

        if ( '' === $text ) {
            $text = $url;
        }
    } else {
        $text = $url;
    }
    
    $target = isset( $settings['target'] ) ? $settings['target'] : '_blank';
    $rel = isset( $settings['rel'] ) ? $settings['rel'] : 'ugc nofollow';

    if ( ! preg_match( '/^https?/i', $url ) ) {
        $url = 'http://' . ltrim( $url, ':/\\' );
    }

    $accessibility_text = '';

    if ( '_blank' === $target ) {
        $accessibility_text = sprintf(
                    '<span class="describr-a11y-text"> %s</span>',
                    /*translators: Hidden accessibility text.*/
                    esc_html__( '(opens in a new tab)', 'describr' )
                );
    }

    $url = '<div class="describr-content top">' . describr_a11y_text( "{$settings['label']}:" ) . '<a href="' . esc_url( $url ) . '" target="' . esc_attr( $target ) . '" rel="'. esc_attr( $rel ) .'">' . esc_html( $text ) . $accessibility_text . '</a></div>' 
                . describr_hidden_title( $settings['title'], 'describr-label' );
    
    return $url;
}
add_filter( 'describr_profile_field_url', 'describr_profile_field_url', 10, 3 );

/**
 * Marks up the user_url field
 * 
 * @since 3.0
 * 
 * @param string $url      The URL
 * @param array  $settings The field settings
 * @return string The marked up url or empty string
 */
function describr_profile_field_user_url( $url, $settings ) {
    if ( describr_is_saved_data_empty( $url ) ) {
        describr_set_no_data( 'user_url' );
        return '';
    }

    $target = isset( $settings['target'] ) ? $settings['target'] : '_blank';
    $rel = isset( $settings['rel'] ) ? $settings['rel'] : 'ugc nofollow';
    $href = $url;

    if ( ! preg_match( '/^https?/i', $href ) ) {
        $href = 'http://' . ltrim( $href, ':/\\' );
    }
    
    $accessibility_text = '';

    if ( '_blank' === $target ) {
        $accessibility_text = sprintf(
                    '<span class="describr-a11y-text"> %s</span>',
                    /*translators: Hidden accessibility text.*/
                    esc_html__( '(opens in a new tab)', 'describr' )
                );
    }

    $val = '<div class="describr-content">' . describr_a11y_text( "{$settings['label']}:" ) . '<a href="' . esc_url( $href ) . '" target="' . esc_attr( $target ) . '" rel="'. esc_attr( $rel ) .'" class="normal">' . esc_html( $url ) . $accessibility_text . '</a></div>';
    
    return $val;
}
add_filter( 'describr_profile_field_user_url', 'describr_profile_field_user_url', 10, 2 );

/**
 * Marks up the user_email field
 * 
 * @since 3.0
 * 
 * @param string $email    The email
 * @param array  $settings The field settings
 * @return string The marked up email or empty string
 */
function describr_profile_field_user_email( $email, $settings ) {
    if ( ! is_string( $email ) || ! str_contains( $email, '@' ) ) {
        describr_set_no_data( 'user_email' );
        return '';
    }
    
    $output = '';

    if ( is_user_logged_in() && describr_profile_id() === get_current_user_id() ) {
        $output = describr_cancel_pending_email_change_notice();
    }

    if ( ! $output ) {
        $output = describr_hidden_title( $settings['title'], 'describr-label' );
    }

    $email = '<div class="describr-content top">' . describr_a11y_text( "{$settings['label']}:" ) . esc_html( $email ) . '</div>' .  $output;
    return $email;
}
add_filter( 'describr_profile_field_user_email', 'describr_profile_field_user_email', 10, 2 );

/**
 * Marks up the tagline field
 * 
 * @since 3.0
 * 
 * @param string $tagline   Tagline
 * @param array  $settings Tagline settings
 * @return string The marked up tagline or empty string
 */
function describr_profile_field_tagline( $tagline, $settings ) {
    if ( ! is_scalar( $tagline ) || describr_is_saved_data_empty( $tagline ) ) {
        describr_set_no_data( 'tagline' );
        return '';
    }

    $tagline = '<div class="describr-content top">' . describr_a11y_text( "{$settings['label']}:" ) . esc_html( $tagline ) . '</div>' .  describr_hidden_title( $settings['title'], 'describr-label' );
    return $tagline;
}
add_filter( 'describr_profile_field_tagline', 'describr_profile_field_tagline', 10, 2 );

/**
 * Marks up the time zone field
 * 
 * @since 3.0
 * 
 * @param string $email    Time zone
 * @param array  $settings Field settings
 * @return string The marked up time zone or empty string
 */
function describr_profile_field_timezone( $timezone, $settings ) {
    if ( describr_is_saved_data_empty( $timezone ) ) {
        describr_set_no_data( 'timezone' );
        return '';
    }

    if ( 'UTC' === $timezone ) {
        $val = _x( 'UTC', 'time zone', 'describr' );
    } elseif ( in_array( $timezone, timezone_identifiers_list(), true ) ) {
        $zones = explode( '/', $timezone );

        $timezones = array();

        if ( empty( $zones[1] ) ) {//No city?
            $timezones['continents'] = $zones[0];//Continent (America) only, which is rare
        } else {
            $timezones['cities'] = $zones[1];//City: Jamaica from America/Jamaica

            if ( ! empty( $zones[2] )  ) {
                $timezones['subcities'] = $zones[2];//Subcity: Buenos_Aires from America/Argentina/Buenos_Aires
            }
        }           
        
        describr_load_locale_continents_cities( describr_get_blog_locale() );

        $tanslated_timezones = describr_translated_timezone();

        $val = array();
                    
        foreach ( $timezones as $zone => $display ) {
            if ( ! empty( $tanslated_timezones[ $zone ][ $display ] ) ) {
                $val[] = $tanslated_timezones[ $zone ][ $display ];
            }
        }
                    
        $val = implode( ' - ', $val );
    } else {
        $current_offset = describr_beautify_utc_offset( describr_normalize_selected_utc_offset( $timezone ) );

        $val = sprintf( 
            /*translators: %s: UTC offset.*/
            _x( 'UTC%s', 'time zone', 'describr' ), 
            $current_offset 
        );
    }

    if ( empty( $val ) ) {
        describr_set_no_data( $field );
        return '';
    }
    
    $val = '<div class="describr-content top">' . describr_a11y_text( "{$settings['label']}:" ) . esc_html( $val ) . '</div>' .  describr_hidden_title( $settings['title'], 'describr-label' );
    
    return $val;       
}
add_filter( 'describr_profile_field_timezone', 'describr_profile_field_timezone', 10, 2 );
/**
 * Marks up the birthdate field
 * 
 * @since 3.0
 * 
 * @param string $birthdate The birthdate
 * @return string The marked up age or empty string
 */
function describr_profile_field_age( $birthdate ) {
    if ( ( is_string( $birthdate ) || is_numeric( $birthdate ) ) && ! empty( $birthdate ) ) {
        $label = _x( 'Age', 'user', 'describr' );
        $age = '<div class="describr-content top">' . describr_a11y_text( $label . ':' ) . esc_html( describr_age( $birthdate ) ) . '</div>' . describr_hidden_title( $label, 'describr-label' );
        return $age;
    } else {
        describr_set_no_data( 'birthdate' );
        return '';
    }
}
add_filter( 'describr_profile_field_birthdate', 'describr_profile_field_age', 10, 1 );

/**
 * Marks up the user_nicename field
 * 
 * @since 3.0
 * 
 * @param string $nicename The nicename
 * @param array  $settings The field settings
 * @return string The marked up nicename or empty string
 */
function describr_profile_field_user_nicename( $nicename, $settings ) {
    if ( ! is_scalar( $nicename ) || describr_is_saved_data_empty( $nicename ) ) {
        if ( describr_can_edit_profile() ) {
            if ( describr_is_nicename_locked( describr_profile_id() ) ) {
                //Unlock the nicename for editing since its value is illegal
                delete_user_meta( describr_profile_id(), 'describr_nicename_updated' );
            }

            describr_set_no_data( 'user_nicename' );
        } else {
            return '';
        }
    }

    $notice = '';

    $user_id = describr_profile_id();

    if ( describr_can_edit_profile() && describr_is_nicename_locked( $user_id ) ) {
        $text = describr_nicename_locked_notice( $user_id );
        $notice = describr_a11y_text( $text, '' );
        $notice .= describr_hidden_title( $text, 'describr-description', 'p' );
    }
    
    $val = '<div class="describr-content top has-header">
                <h2>' . esc_html( $settings['title'] ) . '</h2>
            </div>
                <div class="describr-content">' . esc_html( $nicename ) . '</div>' 
                . $notice;

    return $val;
}
add_filter( 'describr_profile_field_user_nicename', 'describr_profile_field_user_nicename', 10, 2 );

/**
 * Marks up fields mobile_number, work_number, home_number, timezone
 * display_name, nickname, first_name, last_name, current_city,
 * and hometown values
 * 
 * @since 3.0
 * 
 * @param string|array $val      The field's value returned from the database
 * @param array        $settings The field settings
 * @param string       $field    The field
 * @return string The marked up value or empty string
 */
function describr_profile_field( $val, $settings, $field ) {
    $phones = array( 
        'mobile_number', 
        'work_number', 
        'home_number', 
    );

    $fields = array( 
        'user_login',
        'display_name', 
        'nickname', 
        'first_name', 
        'last_name', 
        'current_city', 
        'hometown',
    );
    
    if ( ! in_array( $field, array_merge( $fields, $phones ), true ) ) {
        return $val;
    }
    
    if ( in_array( $field, $phones, true ) ) {
        if ( empty( $val['number'] ) ) {
            describr_set_no_data( $field );
            return '';
        }
        
        $val = array_filter( $val, 'is_scalar' );

        if ( ! $val ) {
            describr_set_no_data( $field );
            return '';
        }

        $phone_number = $val['number'];
        
        $ext_ = '';

        if ( isset( $val['ext'] ) && strlen( $val['ext'] ) ) {
            /*translators: Abbreviation for telephone number extension. This will be put in front of any extension component of the telephone number.*/ 
            $ext = _x( 'ext.', 'telephone', 'describr' );
            $phone_number .= sprintf( ' %s %s', $ext, $val['ext'] );
        }

        $number_type = describr_phone_number_type( $val['type'] );
        
        $val = '<div class="describr-content top">' . describr_a11y_text( $settings['label'] . ':' ) . esc_html( $phone_number ) . '</div>'. describr_hidden_title( $number_type, 'describr-label' );
    } else {
        if ( ! is_scalar( $val ) || describr_is_saved_data_empty( $val ) ) {
            describr_set_no_data( $field );
            return '';
        }

        if ( in_array( $field, array( 'current_city', 'hometown' ), true ) ) {
            if ( 'current_city' === $field ) {
                /*translators: %s City.*/
                $string = __( 'Lives in %s', 'describr' );
            } else {
                /*translators: %s City.*/
                $string = __( 'From %s', 'describr' );
            }
            $val = '<div class="describr-content">' . wp_kses_post( sprintf( $string, "<strong>$val</strong>" ) ) . '</div>';
        } else {
            $val = '<div class="describr-content top has-header"><h2>' . esc_html( $settings['title'] ) . '</h2></div><div class="describr-content">' . esc_html( $val ) . '</div>';
        }
    }
    
    return $val;
}
add_filter( 'describr_profile_field', 'describr_profile_field', 10, 3 );

/**
 * Marks up fields whose values are predefined choices that users can select
 * 
 * @since 3.0
 * 
 * @param string $val      The field's value returned from the database
 * @param array  $settings The field settings
 * @param string $field    The field
 * @return string The marked up value or empty string
 */
function describr_profile_options_field( $val, $settings, $field ) {
    if ( ! in_array( $field, array( 'gender', 'locale', 'locales' ), true ) ) {
        return $val;
    }
    
    if ( ! is_scalar( $val ) || describr_is_saved_data_empty( $val ) ) {
        describr_set_no_data( $field );
        return '';
    }
     
    $val = array_map( 'trim', explode( ',', $val ) );
    
    $list_item_separator = wp_get_list_item_separator();
    
    if ( ! empty( $settings['options'] ) ) {
        $options = $settings['options'];
    }
    
    $output = '';
    $gender = '';

    foreach ( $val as $v ) {
        if ( isset( $options[ $v ] ) ) {
            if ( '' !== $output ) {
                $output .= $list_item_separator;
            }

            $output .= $options[ $v ];

            if ( 'gender' === $field ) {
                $gender = $v;
            }
        }
    }
    
    if ( '' === $output ) {
        describr_set_no_data( $field );
        return '';
    }
    
    if ( $gender ) {
        $gender = ' data-gender="' . esc_attr( $gender ) . '"';
    }
    
    $output = '<div' . $gender . ' class="describr-content top">' . describr_a11y_text( "{$settings['label']}:" ) . esc_html( $output ) . '</div>' . describr_hidden_title( $settings['title'], 'describr-label' );

    return $output;
}
add_filter( 'describr_profile_field', 'describr_profile_options_field', 10, 3 );

/**
 * Marks up the user's bio, the value of the `description` field.
 * 
 * @since 3.0
 * @since 3.1.3  Added no autop key to the bio's settings.
 * 
 * @param string $bio      The user's bio.
 * @param array  $settings The field settings.
 * @return string The marked up bio or empty string.
 */
function describr_profile_field_description( $bio, $settings ) {
    if ( ! is_scalar( $bio ) || describr_is_saved_data_empty( $bio ) ) {
        describr_set_no_data( 'description' );
        return '';
    }
    
    $settings['no_autop'] = true;

    $bio = '<div class="describr-content top has-header"><h2>' . esc_html( $settings['title'] ). '</h2></div><div class="describr-content">' . describr_esc_textarea( describr_unwrap_p( $bio ), $settings ) . '</div>';
    return $bio;
}
add_filter( 'describr_profile_field_description', 'describr_profile_field_description', 10, 2 );

/**
 * Marks up relationship
 * 
 * @since 3.0
 * 
 * @param string $val      Relationship
 * @param array  $settings Field settings
 * @return string The marked up relationship or empty string
 */
function describr_profile_field_relationship( $val, $settings ) {
    if ( ! is_array( $val ) || ! $val ) {
        describr_set_no_data( 'relationship' );
        return '';
    }

    $output = '';

    if ( isset( $settings['supports'] ) ) {
        $supports = describr_get_field_supports( $settings['supports'], 'relationship' );
    }
    
    if ( isset( $val['status'] ) && ! empty( $supports['status']['options'][ $val['status'] ] ) ) {
        $status = $val['status'];
        $output .= $supports['status']['options'][ $status ];
    } else {
        $status = '';
    }
    
    $partner = isset( $val['partner'] ) && is_scalar( $val['partner'] ) ? $val['partner'] : '';
    
    if ( ! empty( $val['partner_id'] ) ) {
        $user_id = $val['partner_id'];
        $user = get_userdata( $user_id );

        if ( ! empty( $user->ID ) && describr_is_user_viewable( $user_id ) ) {
            if ( isset( $user->display_name ) ) {
                $partner = $user->display_name;
            }            
            
            $partner_url_set = true;

            $partner = '<a href="' . esc_url( describr_profile_url( $user ) ) . '" target="_blank">' . esc_html( $partner ) . sprintf(
                        '<span class="describr-a11y-text"> %s</span>',
                        /*translators: Hidden accessibility text.*/
                        esc_html__( '(opens in a new tab)', 'describr' )
                        ) . '</a>';
        } else {
            $partner = '';
        }
    }
    
    if ( '' !== $partner ) {
        if ( ! isset( $partner_url_set ) ) {
            $partner = esc_html( $partner );
        }
        
        $partner = '<strong>' . $partner . '</strong>';
    }
    
    if ( '' !== $output ) {
        if ( '' !== $partner ) {
            if ( in_array( $status, array( 'in_a_rel', 'domestic_partner', 'civil_union', 'fwb', 'complicated' ), true ) ) {
                $output = sprintf( 
                    /*translators: 1: Intimate relationship status. 2: Intimate relationship partner.*/
                    __( '%1$s with %2$s', 'describr' ), 
                    $output, 
                    $partner 
                );
            } elseif ( in_array( $status, array( 'engaged', 'married' ), true ) ) {
                $output = sprintf( 
                    /*translators: 1: Intimate relationship status. 2: Intimate relationship partner.*/
                    __( '%1$s to %2$s', 'describr' ), 
                    $output, 
                    $partner 
                );
            } elseif ( in_array( $status, array( 'single', 'separated', 'divorced' ), true ) ) {
                $output = sprintf( 
                    /*translators: 1: Intimate relationship status. 2: Intimate relationship partner.*/
                    __( '%1$s from %2$s', 'describr' ), 
                    $output, 
                    $partner 
                );
            } elseif ( in_array( $status, array( 'widowed' ), true ) ) {
                $output = sprintf( 
                    /*translators: 1: Intimate relationship status. 2: Intimate relationship partner.*/
                    __( '%1$s by %2$s', 'describr' ), 
                    $output, 
                    $partner 
                );
            }
        }
    } elseif ( '' !== $partner ) {
        $output = sprintf(
            /*translators: %: Intimate relationship partner.*/
            __( 'In a relationship with %s', 'describr' ), 
            $partner
        );
    }

    if ( ! empty( $val['since'] ) ) {
        $since = describr_utc_date( $val['since'], describr_get_locale_date_format_by_date( $val['since'] ) );
        
        if  ( '' !== $output ) {
            $output .= ' ' . sprintf( 
                /*translators: %s: Date.*/
                _x( 'since %s', 'intimate relationship', 'describr' ), 
                $since 
            );
        } else {
            $output = sprintf( 
                /*translators: %s: Date.*/
                _x( 'Since %s', 'intimate relationship', 'describr' ), 
                $since 
            );
        }
    }
    
    if ( '' !== $output ) {
        $output = '<div class="describr-content">' . wp_kses_post( $output ) . '</div>';
    } else {
        describr_set_no_data( 'relationship' );
    }

    return $output;
}
add_filter( 'describr_profile_field_relationship', 'describr_profile_field_relationship', 10, 2 );

/**
 * Marks up the lived_cities value
 * 
 * @since 3.0
 * 
 * @param string $val      Lived cities
 * @param array  $settings The field settings
 * @return array|string The marked up lived cities or empty string
 */
function describr_profile_field_lived_cities( $val, $settings ) {
    if ( ! is_array( $val ) || ! $val ) {
        describr_set_no_data( 'lived_cities' );
        return '';
    }

    $output = array();

    if ( isset( $settings['supports'] ) ) {
        $supports = describr_get_field_supports( $settings['supports'], 'relationship' );

        if ( $supports['moved'] ) {
            $moved = $supports['moved'];
        }
    }
    
    uasort( $val, array( describr()->date(), 'sort_desc_lived_places' ) );
    
    $unknown = '<span aria-hidden="true">&#8212;</span>' . describr_a11y_text( _x( 'Unknown', 'city', 'describr' ), '' );
    
    $a11y_text = _x( 'Lived in', 'city', 'describr' );

    foreach ( $val as $k => $v ) {
        if ( ! isset( $v['city'] ) ) {
            $v['city'] = '';
        }
        
        if ( strlen( $v['city'] ) ) {
            $city = esc_html( $v['city'] );
        } else {
            if ( ! describr_can_edit_profile() ) {
                continue;
            }

            $city = $unknown;
        }
        
        $city = describr_a11y_text( $a11y_text ) . $city;

        $place = '<div class="describr-content';

        if ( ! empty( $v['moved'] ) ) {
            $moved_ = describr_utc_date( $v['moved'], describr_get_locale_date_format_by_date( $v['moved'] ) );
            
            if ( isset( $moved['title'] ) ) {
                $moved_ = "{$moved['title']} $moved_";                
            }
            
            $place .= ' top">' . $city . '</div><div class="describr-label">' . esc_html( $moved_ ) . '</div>';
        } else {
            $place .= '">' . $city . '</div>';
        }

        $output[ $k ] = $place;
    }
    
    if ( ! $output ) {
        $output = '';        
        describr_set_no_data( 'lived_cities' );
    } 

    return $output;
}
add_filter( 'describr_profile_field_lived_cities', 'describr_profile_field_lived_cities', 10, 2 );

/**
 * Marks up work_history
 * 
 * @since 3.0
 * 
 * @param string $val      Work history
 * @param array  $settings Field settings
 * @return array|string The marked up work history or empty string
 */
function describr_profile_field_work_history( $val, $settings ) {
    if ( ! is_array( $val ) || ! $val ) {
        describr_set_no_data( 'work_history' );
        return '';
    }

    $output = array();

    uasort( $val, array( describr()->date(), 'sort_desc_timeperiod' ) );

    $desc_key = 'description';

    $field = 'work_history';
    
    $desc_setting = array();

    if ( isset( $settings['supports'] ) && in_array( $desc_key, $settings['supports'], true ) ) {
        $supports = describr_get_field_supports( $settings['supports'], $field );

        if ( isset( $supports[ $desc_key ] ) ) {
            $desc_setting = $supports[ $desc_key ];
        }
    }
    
    $desc_setting['no_nl2br'] = true;
    $desc_setting['no_autop'] = true;

    $unknown = '<span aria-hidden="true">&#8212;</span>' . describr_a11y_text( _x( 'Unknown', 'organization', 'describr' ), '' );
    
    $sep = ' <span class="describr-profile-bull" aria-hidden="true">&bull;</span> ';
    
    $timezone = new DateTimeZone( 'UTC' );

    foreach ( $val as $k => $v ) {
        if ( ! isset( $v['company'] ) ) {
            $v['company'] = '';
        }
        
        $comp = '';

        if ( strlen( $v['company'] ) ) {
            $comp = esc_html( $v['company'] );
        } else {
            if ( ! describr_can_edit_profile() ) {
                continue;
            }

            $comp = $unknown;
        }

        $string = '<strong class="describr-profile-' . esc_attr( $field ) . '-company">' . $comp . '</strong>';
        
        $timeperiod = null;
        $from       = null; 
        $to         = null;
        
        if ( ! empty( $v['title'] ) ) {
            $string = sprintf(
                /*translators: 1: Job title. 2: Organization.*/
                __( '%1$s at %2$s', 'describr' ), 
                $v['title'], 
                $string 
            );
        }
        
        if ( ! empty( $v['from'] ) ) {
            $from = strtotime( describr_normalize_date( $v['from'] ) );

            if ( ! empty( $v['to'] ) ) {
                $to = strtotime( describr_normalize_date( $v['to'] ) );
            }
        }

        if ( $from ) {
            if ( ! empty( $v['present'] ) ) {
                $timeperiod = wp_date( describr_get_locale_date_format_by_date( $v['from'] ), $from, $timezone );
                $timeperiod .= '-' . _x( 'present', 'date', 'describr' );
            } elseif ( $to ) {
                $timeperiod = wp_date( describr_get_locale_date_format_by_date( $v['from'] ), $from, $timezone );
                $timeperiod .= '-';
                $timeperiod .= wp_date( describr_get_locale_date_format_by_date( $v['to'] ), $to, $timezone );
            } else {
                $timeperiod = wp_date( describr_get_locale_date_format_by_date( $v['from'] ), $from, $timezone );
            }
        }
        
        $string = '<span class="describr-profile-work_history-intro">' . wp_kses_post( $string ) . '</span>';

        if ( $timeperiod ) {
            $string .= ' ' . esc_html( 
                sprintf(
                    /*translators: %s: Time period. For example, 2020-2024.*/ 
                    __( 'from %s', 'describr' ), 
                    $timeperiod 
                ) 
            );
        }
        
        if ( ! empty( $v['city'] ) ) {
            $string .= ' ' . esc_html( 
                sprintf( 
                    /*translators: %s: City.*/
                    _x( 'in %s', 'preposition', 'describr' ), 
                    $v['city'] 
                ) 
            );
        }

        if ( ! empty( $v[ $desc_key ] ) ) {
            $string .= $sep . '<span class="describr-profile-' . esc_attr( $field ) . '-' . esc_attr( $desc_key ) . '">' . describr_esc_textarea( describr_unwrap_p( $v[ $desc_key ] ), $desc_setting ) . '</span>';
        }

        $output[ $k ] = '<div class="describr-content describr-profile-' . esc_attr( $field ) . '">' . $string . '</div>';
    }

    if ( ! $output ) {
        describr_set_no_data( $field );
        $output = '';
    } 

    return $output;
}
add_filter( 'describr_profile_field_work_history', 'describr_profile_field_work_history', 10, 2 );

/**
 * Marks up the high_school and college fields
 * 
 * @since 3.0
 * 
 * @param string $val      High schools or colleges 
 * @param array  $settings Field settings
 * @param string $field    Field
 * @return array|string The marked up High school or college or empty string
 */
function describr_profile_field_schools( $val, $settings, $field ) {
    if ( ! in_array( $field, array( 'high_school', 'college' ), true ) ) {
        return $val;
    }

    if ( ! is_array( $val ) ) {
        describr_set_no_data( $field );
        return '';
    }

    $output = array();

    uasort( $val, array( describr()->date(), 'sort_desc_timeperiod' ) );

    $sep = ' <span class="describr-profile-bull" aria-hidden="true">&bull;</span> ';
    
    $desc_key = 'description';
    
    $desc_setting = array();

    if ( isset( $settings['supports'] ) && in_array( $desc_key, $settings['supports'], true ) ) {
        $supports = describr_get_field_supports( $settings['supports'], $field );

        if ( isset( $supports[ $desc_key ] ) ) {
            $desc_setting = $supports[ $desc_key ];
        }
    }    
    
    $desc_setting['no_nl2br'] = true;
    $desc_setting['no_autop'] = true;

    if ( 'high_school' === $field ) {
        $unknown = _x( 'Unknown', 'high school', 'describr' );
    } else {
        $unknown = _x( 'Unknown', 'college', 'describr' );
    }

    $unknown = '<span aria-hidden="true">&#8212;</span>' . describr_a11y_text( $unknown, '' ); 

    $timezone = new DateTimeZone( 'UTC' );
                
    foreach ( $val as $k => $v ) {
        if ( ! isset( $v['school'] ) ) {
            $v['school'] = '';
        }
        
        $sch = '';

        if ( strlen( $v['school'] ) ) {
            $sch = esc_html( $v['school'] );
        } else {
            if ( ! describr_can_edit_profile() ) {
                continue;
            }

            $sch = $unknown;
        }
        
        $string = '';

        $graduated  = null; 
        $from       = null; 
        $to         = null; 
        $timeperiod = null;
        
        $school = '<strong class="describr-profile-' . esc_attr( $field ) .'">' . $sch . '</strong>';

        if ( ! empty( $v['graduated'] ) ) {
            $graduated = $v['graduated'];
        }
        
        if ( ! empty( $v['from'] ) ) {
            $from = strtotime( describr_normalize_date( $v['from'] ) );

            if ( ! empty( $v['to'] ) ) {
                $to = strtotime( describr_normalize_date( $v['to'] ) );
            }
        }

        if ( 'college' === $field ) {
            $major  = null; 
            $degree = null; 
            
            if ( ! empty( $v['degree'] ) ) {
                $degree = '<span class="describr-profile-college-degree">' . esc_html( $v['degree'] ) . '</span>';
            }
        
            if ( ! empty( $v['major'] ) ) {
                $major = '<span class="describr-profile-college-major">' . esc_html( $v['major'] ) . '</span>';
            }

            if ( $graduated ) {
                if ( $degree ) {
                    /*translators: 1: College degree. 2: Name of college.*/
                    $string = sprintf( __( 'Earned a %1$s from %2$s', 'describr' ), $degree, $school );
                } 

                if ( $major ) {
                    if ( $string ) {
                        /*translators: 1: College degree. 2: College major. 3: Name of college.*/
                        $string = sprintf( __( 'Earned a %1$s in %2$s from %3$s', 'describr' ), $degree, $major, $school );
                    } else {
                        /*translators: 1: College major. 2: Name of college.*/
                        $string = sprintf( __( 'Studied %1$s at %2$s', 'describr' ), $major, $school );
                    }
                }
          
                if ( ! $string ) {
                    /*translators: %s: Name of college.*/
                    $string = sprintf( __( 'Graduated from %s', 'describr' ), $school );
                }
            } elseif ( $to ) {
                if ( time() > $to ) {
                    if ( $degree ) {
                        if ( $major ) {
                            /*translators: 1: College degree. 2: College major. 3: Name of college.*/
                            $string = sprintf( __( 'Studied for a %1$s in %2$s at %3$s', 'describr' ), $degree, $major, $school );
                        } else {
                            /*translators: 1: College degree. 2: Name of college.*/
                            $string = sprintf( __( 'Studied for a %1$s at %2$s', 'describr' ), $degree, $school );
                        }
                    }

                    if ( ! $string ) {
                        if ( $major ) {
                            /*translators: 1: College major. 2: Name of college.*/
                            $string = sprintf( __( 'Studied %1$s at %2$s', 'describr' ), $major, $school );
                        } else {
                            /*translators: %s: Name of college.*/
                            $string = sprintf( __( 'Studied at %s', 'describr' ), $school );
                        }
                    }
                } else {
                    if ( $degree ) {
                        if ( $major ) {
                            /*translators: 1: College degree. 2: College major. 3: Name of college.*/
                            $string = sprintf( __( 'Studying for a %1$s in %2$s at %3$s', 'describr' ), $degree, $major, $school );
                        } else {
                            /*translators: 1: College degree. 2: Name of college.*/
                            $string = sprintf( __( 'Studying for a %1$s at %2$s', 'describr' ), $degree, $school );
                        }
                    }

                    if ( ! $string ) {
                        if ( $major ) {
                            /*translators: 1: College major. 2: Name of college.*/
                            $string = sprintf( __( 'Studying %1$s at %2$s', 'describr' ), $major, $school );
                        } else {
                            /*translators: %s: Name of college.*/
                            $string = sprintf( __( 'Studying at %s', 'describr' ), $school );
                        }
                    }
                }
            } else {
                if ( $degree ) {
                    if ( $major ) {
                        /*translators: 1: College degree. 2: College major. 3: Name of college.*/
                        $string = sprintf( __( 'Studying for a %1$s in %2$s at %3$s', 'describr' ), $degree, $major, $school );
                    } else {
                        /*translators: 1: College degree. 2: Name of college.*/
                        $string = sprintf( __( 'Studying for a %1$s at %2$s', 'describr' ), $degree, $school );
                    }
                }

                if ( ! $string ) {
                    if ( $major ) {
                            /*translators: 1: College major. 2: Name of college.*/
                        $string = sprintf( __( 'Studying %1$s at %2$s', 'describr' ), $major, $school );
                    } else {
                        /*translators: %s: Name of college.*/
                        $string = sprintf( __( 'Studying at %s', 'describr' ), $school );
                    }
                }
            }
            
            if ( ! empty( $v['minor'] ) ) {
                $string .= $sep . '<span class="describr-profile-college-minor">';

                if ( $graduated || ( $to && ( time() > $to ) ) ) {
                    /*translators: %s College minor, a secondary specialization that students choose in addition to their major.*/
                    $string .= esc_html( sprintf( __( 'Also studied %s', 'describr' ), $v['minor'] ) );
                } else {
                    /*translators: %s College minor, a secondary specialization that students choose in addition to their major.*/
                    $string .= esc_html( sprintf( __( 'Also studying %s', 'describr' ), $v['minor'] ) );
                }

                $string .= '</span>';
            }
        } elseif ( $graduated )  {
            /*translators: %s: Name of high school.*/
            $string = sprintf( __( 'Went to %s', 'describr' ), $school );
        } else {
            /*translators: %s: Name of high school.*/
            $string = sprintf(__( 'Attending %s', 'describr' ), $school );
        }
        
        $string = '<span class="describr-profile-' . esc_attr( $field ) . '-intro">' . wp_kses_post( $string ) . '</span>';

        if ( $from ) {
            if ( $graduated ) {
                if ( $to ) {
                    $to_ = wp_date( describr_locale_date_format( 'year' ), $to, $timezone );
                    /*translators: %s: Year.*/
                    $timeperiod = sprintf( __( 'Class of %s', 'describr' ), $to_ );
                } else {
                    $timeperiod = _x( 'Graduated', 'completed school', 'describr' );
                }
            } elseif ( $to ) {
                $timeperiod = wp_date( describr_get_locale_date_format_by_date( $v['from'] ), $from, $timezone );
                $timeperiod .= '-';
                $timeperiod .= wp_date( describr_get_locale_date_format_by_date( $v['to'] ), $to, $timezone );
            } else {
                $timeperiod = wp_date( describr_get_locale_date_format_by_date( $v['from'] ), $from, $timezone );
            }
        }

        if ( $timeperiod ) {
            $string .= $sep . '<span class="describr-profile-' . esc_attr( $field ) . '-timeperiod">' . esc_html( $timeperiod ) . '</span>';
        }

        if ( ! empty( $v[ $desc_key ] ) ) {
            $string .= $sep . '<span class="describr-profile-' . esc_attr( $field ) . '-' . esc_attr( $desc_key ) . '">' . describr_esc_textarea( describr_unwrap_p( $v[ $desc_key ] ), $desc_setting ) . '</span>';
        }

        $output[ $k ] = '<div class="describr-content describr-profile-' . esc_attr( $field ) . '">' . $string .'</div>';
    }

    if ( empty( $output ) ) {
        describr_set_no_data( $field );
        $output = '';
    }

    return $output;
}
add_filter( 'describr_profile_field', 'describr_profile_field_schools', 10, 3 );

/**
 * Marks up non-URL social network fields values
 * 
 * @since 3.0
 * 
 * @param string $val      The social network 
 * @param array  $settings The field settings
 * @return string The marked-up social network or empty string
 */
function describr_profile_field_socials( $val, $settings, $field ) {
    if ( ! in_array( $field, array( 'whatsapp', 'wechat', 'qq', 'line', 'kik', 'skype', 'oculus', 'kakaotalk' ), true ) ) {
        return $val;
    }
    
    /*The empty field is not cached with {@see 'describr_set_no_data'} function so that the Add Social Network button is added in wp-content/plugins/describr/includes/class-profile.php if no social network exists*/
    if ( ! is_scalar( $val ) || describr_is_saved_data_empty( $val ) ) {
        return '';
    }
    
    if ( isset( $settings['base_url'] ) ) {
        $base_url = (array) $settings['base_url'];
        $base_url = $base_url[0];

        $val = $base_url . describr_get_handle_from_url( $base_url, $val );
    }

    $val = '<div class="describr-content top">' . describr_a11y_text( "{$settings['label']}:" ) . esc_html( $val ) . '</div>' . describr_hidden_title( $settings['title'], 'describr-label' );

    return $val;
}
add_filter( 'describr_profile_field', 'describr_profile_field_socials', 10, 3 );

/**
 * Optimizes birthdate for accessibility in edit mode
 * 
 * @since 3.0
 * 
 * @param string $birthdate Birthdate
 * @return string Firthdate optimized for accessibility
 */
function describr_profile_field_accessible_birthdate( $birthdate ) {
    return describr_utc_date( $birthdate, describr_get_locale_date_format_by_date( $birthdate ) );
}
add_filter( 'describr_profile_field_accessible_birthdate', 'describr_profile_field_accessible_birthdate', 10, 1 );

/**
 * Optimizes mobile number, work number, home number, and time zone
 * for accessibility in edit mode
 * 
 * @since 3.0
 * 
 * @param string|array $val      Saved data
 * @param array        $settings Field settings
 * @param string       $field    Field
 * @return string Data optimized for accessibility
 */
function describr_profile_field_accessible( $val, $settings, $field ) {
    $phones = array( 
        'mobile_number', 
        'work_number', 
        'home_number', 
    );

    if ( ! in_array( $field, array_merge( array( 'timezone' ), $phones ), true ) ) {
        return $val;
    }
    
    if ( in_array( $field, $phones, true ) ) {
        if ( ! isset( $val['number'] ) ) {
            return $val;
        }

        $phone_number = $val['number'];
        
        $ext_ = '';

        if ( isset( $val['ext'] ) && strlen( $val['ext'] ) ) {
            /*translators: This will be put in front of any extension component of the phone number.*/ 
            $ext = __( 'ext.', 'describr' );
            $phone_number .= sprintf( ' %s %s', $ext, $val['ext'] );
        }
        
        $val = $phone_number;
    } elseif ( 'UTC' === $val ) {
        $val = _x( 'UTC', 'time zone', 'describr' ); 
    } elseif ( in_array( $val, timezone_identifiers_list(), true ) ) {
        $zones = explode( '/', $val );

        $timezones = array();

        if ( empty( $zones[1] ) ) {
            $timezones['continents'] = $zones[0];
        } else {
            $timezones['cities'] = $zones[1];

            if ( !  empty( $zones[2] )  ) {
                $timezones['subcities'] = $zones[2];
            }
        }
                    
        $tanslated_timezones = describr_translated_timezone();

        $val = array();
                    
        foreach ( $timezones as $timezone => $display ) {
            if ( ! empty( $tanslated_timezones[ $timezone ][ $display ] ) ) {
                $val[] = $tanslated_timezones[ $timezone ][ $display ];
            }
        }
                    
        $val = implode( ' - ', $val );
    } else {
        $current_offset = describr_beautify_utc_offset( describr_normalize_selected_utc_offset( $val ) );

        $val = sprintf( 
            /*translators: %s: UTC offset.*/
            _x( 'UTC%s', 'time zone', 'describr' ), 
            $current_offset 
        );
    }
    
    return $val;
}
add_filter( 'describr_profile_field_accessible', 'describr_profile_field_accessible', 10, 3 );

/**
 * Optimizes fields having options for accessibility in edit mode
 * 
 * @since 3.0
 * 
 * @param string|array $val      Saved data
 * @param array        $settings Field settings
 * @param string       $field    Field
 * @return string Data optimized for accessibility
 */
function describr_profile_field_accessible_options_field( $val, $settings, $field ) {
    if ( ! in_array( $field, array( 'gender', 'locale', 'locales' ), true ) ) {
        return $val;
    }
     
    $val = array_map( 'trim', explode( ',', $val ) );
    
    if ( ! empty( $settings['options'] ) ) {
        $options = $settings['options'];
    }
    
    $list_item_separator = wp_get_list_item_separator();

    $output = '';

    foreach ( $val as $v ) {
        if ( isset( $options[ $v ] ) ) {
            if ( '' !== $output ) {
                $output .= $list_item_separator;
            }

            $output .= $options[ $v ];
        }
    }
    
    return $output;
}
add_filter( 'describr_profile_field_accessible', 'describr_profile_field_accessible_options_field', 10, 3 );

/**
 * Optimizes relationship for accessibility in edit mode
 * 
 * @since 3.0
 * 
 * @param string|array $val      Relationship
 * @param array        $settings Field settings
 * @return string Relationship optimized for accessibility
 */
function describr_profile_field_accessible_relationship( $val, $settings ) {
    $output = '';

    if ( isset( $settings['supports'] ) ) {
        $supports = describr_get_field_supports( $settings['supports'], 'relationship' );
    }
    
    if ( isset( $val['status'] ) && ! empty( $supports['status']['options'][ $val['status'] ] ) ) {
        $status = $val['status'];
        $output .= $supports['status']['options'][ $status ];
    } else {
        $status = '';
    }
    
    $partner = isset( $val['partner'] ) && is_scalar( $val['partner'] ) ? $val['partner'] : '';

    if ( ! empty( $val['partner_id'] ) ) {
        $user_id = $val['partner_id'];
        $user = get_userdata( $user_id );

        if ( ! empty( $user->ID ) && describr_is_user_viewable( $user_id ) ) {
            if ( isset( $user->display_name ) ) {
                $partner = $user->display_name;
            }
        } else {
            $partner = '';
        }
    }
    
    if ( '' !== $output ) {
        if ( '' !== $partner ) {
            if ( in_array( $status, array( 'in_a_rel', 'domestic_partner', 'civil_union', 'fwb', 'complicated' ), true ) ) {
                $output = sprintf( 
                    /*translators: 1: Intimate relationship status. 2: Intimate relationship partner.*/
                    __( '%1$s with %2$s', 'describr' ), 
                    $output, 
                    $partner 
                );
            } elseif ( in_array( $status, array( 'engaged', 'married' ), true ) ) {
                $output = sprintf( 
                    /*translators: 1: Intimate relationship status. 2: Intimate relationship partner.*/
                    __( '%1$s to %2$s', 'describr' ), 
                    $output, 
                    $partner 
                );
            } elseif ( in_array( $status, array( 'single', 'separated', 'divorced' ), true ) ) {
                $output = sprintf( 
                    /*translators: 1: Intimate relationship status. 2: Intimate relationship partner.*/
                    __( '%1$s from %2$s', 'describr' ), 
                    $output, 
                    $partner 
                );
            } elseif ( in_array( $status, array( 'widowed' ), true ) ) {
                $output = sprintf( 
                    /*translators: 1: Intimate relationship status. 2: Intimate relationship partner.*/
                    __( '%1$s by %2$s', 'describr' ), 
                    $output, 
                    $partner 
                );
            }
        }
    } elseif ( '' !== $partner ) {
        $output = sprintf(
            /*translators: %: Intimate relationship partner.*/
            __( 'In a relationship with %s', 'describr' ), 
            $partner
        );
    }

    if ( ! empty( $val['since'] ) ) {
        $since = describr_utc_date( $val['since'], describr_get_locale_date_format_by_date( $val['since'] ) );
        
        if  ( '' !== $output ) {
            $output .= ' ' . sprintf( 
                /*translators: %s: Date.*/
                _x( 'since %s', 'intimate relationship', 'describr' ), 
                $since 
            );
        } else {
            $output = sprintf( 
                /*translators: %s: Date.*/
                _x( 'Since %s', 'intimate relationship', 'describr' ), 
                $since 
            );
        }
    }
    
    return $output;
}
add_filter( 'describr_profile_field_accessible_relationship', 'describr_profile_field_accessible_relationship', 10, 2 );

/**
 * Optimizes high school and college for accessibility in edit mode
 * 
 * @since 3.0
 * 
 * @param string $val      High schools or colleges 
 * @param array  $settings Field settings
 * @param string $field    Field
 * @return array|string An array of high schools or colleges optimized for accessibility or empty string
 */
function describr_profile_field_accessible_schools( $val, $settings, $field ) {
    if ( ! in_array( $field, array( 'high_school', 'college' ), true ) ) {
        return $val;
    }

    if ( ! is_array( $val ) ) {
        return '';
    }

    $output = array();
    
    $sep = '. ';

    if ( 'high_school' === $field ) {
        $unknown = _x( 'Unknown', 'high school', 'describr' );
    } else {
        $unknown = _x( 'Unknown', 'college', 'describr' );
    }
                
    foreach ( $val as $k => $v ) {
        if ( ! isset( $v['school'] ) ) {
            $v['school'] = '';
        }
        
        if ( strlen( $v['school'] ) ) {
            $school = $v['school'];
        } else {
            $school = $unknown;
        }
        
        $string = '';
        
        $to        = null; 
        $graduated = null;

        if ( ! empty( $v['from'] ) && ! empty( $v['to'] ) ) {
            $to = strtotime( describr_normalize_date( $v['to'] ) );
        }
        
        if ( ! empty( $v['graduated'] ) ) {
            $graduated = $v['graduated'];
        }

        if ( 'college' === $field ) {
            $major  = null; 
            $degree = null; 
            
            if ( ! empty( $v['degree'] ) ) {
                $degree = $v['degree'];
            }
        
            if ( ! empty( $v['major'] ) ) {
                $major = $v['major'];
            }

            if ( $graduated ) {
                if ( $degree ) {
                    /*translators: 1: College degree. 2: Name of college.*/
                    $string = sprintf( __( 'Earned a %1$s from %2$s', 'describr' ), $degree, $school );
                } 

                if ( $major ) {
                    if ( $string ) {
                        /*translators: 1: College degree. 2: College major. 3: Name of college.*/
                        $string = sprintf( __( 'Earned a %1$s in %2$s from %3$s', 'describr' ), $degree, $major, $school );
                    } else {
                        /*translators: 1: College major. 2: Name of college.*/
                        $string = sprintf( __( 'Studied %1$s at %2$s', 'describr' ), $major, $school );
                    }
                }
          
                if ( ! $string ) {
                    /*translators: %s: Name of college.*/
                    $string = sprintf( __( 'Graduated from %s', 'describr' ), $school );
                }
            } elseif ( $to ) {
                if ( time() > $to ) {
                    if ( $degree ) {
                        if ( $major ) {
                            /*translators: 1: College degree. 2: College major. 3: Name of college.*/
                            $string = sprintf( __( 'Studied for a %1$s in %2$s at %3$s', 'describr' ), $degree, $major, $school );
                        } else {
                            /*translators: 1: College degree. 2: Name of college.*/
                            $string = sprintf( __( 'Studied for a %1$s at %2$s', 'describr' ), $degree, $school );
                        }
                    }

                    if ( ! $string ) {
                        if ( $major ) {
                            /*translators: 1: College major. 2: Name of college.*/
                            $string = sprintf( __( 'Studied %1$s at %2$s', 'describr' ), $major, $school );
                        } else {
                            /*translators: %s: Name of college.*/
                            $string = sprintf( __( 'Studied at %s', 'describr' ), $school );
                        }
                    }
                } else {
                    if ( $degree ) {
                        if ( $major ) {
                            /*translators: 1: College degree. 2: College major. 3: Name of college.*/
                            $string = sprintf( __( 'Studying for a %1$s in %2$s at %3$s', 'describr' ), $degree, $major, $school );
                        } else {
                            /*translators: 1: College degree. 2: Name of college.*/
                            $string = sprintf( __( 'Studying for a %1$s at %2$s', 'describr' ), $degree, $school );
                        }
                    }

                    if ( ! $string ) {
                        if ( $major ) {
                            /*translators: 1: College major. 2: Name of college.*/
                            $string = sprintf( __( 'Studying %1$s at %2$s', 'describr' ), $major, $school );
                        } else {
                            /*translators: %s: Name of college.*/
                            $string = sprintf( __( 'Studying at %s', 'describr' ), $school );
                        }
                    }
                }
            } else {
                if ( $degree ) {
                    if ( $major ) {
                        /*translators: 1: College degree. 2: College major. 3: Name of college.*/
                        $string = sprintf( __( 'Studying for a %1$s in %2$s at %3$s', 'describr' ), $degree, $major, $school );
                    } else {
                        /*translators: 1: College degree. 2: Name of college.*/
                        $string = sprintf( __( 'Studying for a %1$s at %2$s', 'describr' ), $degree, $school );
                    }
                }

                if ( ! $string ) {
                    if ( $major ) {
                            /*translators: 1: College major. 2: Name of college.*/
                        $string = sprintf( __( 'Studying %1$s at %2$s', 'describr' ), $major, $school );
                    } else {
                        /*translators: %s: Name of college.*/
                        $string = sprintf( __( 'Studying at %s', 'describr' ), $school );
                    }
                }
            }
            
            if ( ! empty( $v['minor'] ) ) {
                $string .= $sep;

                if ( $graduated || ( $to && ( time() > $to ) ) ) {
                    /*translators: %s College minor, a secondary specialization that students choose in addition to their major.*/
                    $string .= sprintf( __( 'Also studied %s', 'describr' ), $v['minor'] );
                } else {
                    /*translators: %s College minor, a secondary specialization that students choose in addition to their major.*/
                    $string .= sprintf( __( 'Also studying %s', 'describr' ), $v['minor'] );
                }
            }
        } elseif ( $graduated )  {
            /*translators: %s: Name of high school.*/
            $string = sprintf( __( 'Went to %s', 'describr' ), $school );
        } else {
            /*translators: %s: Name of high school.*/
            $string = sprintf(__( 'Attending %s', 'describr' ), $school );
        }

        $output[ $k ] = $string;
    }

    return $output;
}
add_filter( 'describr_profile_field_accessible', 'describr_profile_field_accessible_schools', 10, 3 );

/**
 * Optimizes work history for accessibility in edit mode
 * 
 * @since 3.0
 * 
 * @param string $work_history Work history
 * @return array|string An array of work history optimized for accessibility or empty string
 */
function describr_profile_field_accessible_work_history( $work_history ) {
    if ( ! is_array( $work_history ) || ! $work_history ) {
        return '';
    }

    $output = array();

    $unknown = _x( 'Unknown', 'organization', 'describr' );
    
    $sep = '. ';
    
    $timezone = new DateTimeZone( 'UTC' );

    foreach ( $work_history as $k => $v ) {
        if ( ! isset( $v['company'] ) ) {
            $v['company'] = '';
        }
        
        if ( strlen( $v['company'] ) ) {
            $string = $v['company'];
        } else {
            $string = $unknown;
        }
        
        if ( ! empty( $v['title'] ) ) {
            $string = sprintf(
                /*translators: 1: Job title. 2: Organization.*/
                __( '%1$s at %2$s', 'describr' ), 
                $v['title'], 
                $string 
            );
        }

        $output[ $k ] = $string;
    } 

    return $output;
}
add_filter( 'describr_profile_field_accessible_work_history', 'describr_profile_field_accessible_work_history', 10, 1 );

/**
 * Optimizes lived cities for accessibility in edit mode
 * 
 * @since 3.0
 * 
 * @param string $lived_cities Lived cities
 * @return array|string An array of lived cities optimized for accessibility or empty string
 */
function describr_profile_field_accessible_lived_cities( $lived_cities ) {
    if ( ! is_array( $lived_cities ) || ! $lived_cities ) {
        return '';
    }

    $output = array();

    $unknown = _x( 'Unknown', 'city', 'describr' );
    
    foreach ( $lived_cities as $k => $v ) {
        if ( ! isset( $v['city'] ) ) {
            $v['city'] = '';
        }
        
        if ( strlen( $v['city'] ) ) {
            $city = $v['city'];
        } else {
            $city = $unknown;
        }
        
        $output[ $k ] = $city;
    } 

    return $output;
}
add_filter( 'describr_profile_field_accessible_lived_cities', 'describr_profile_field_accessible_lived_cities', 10, 1 );

/**
 * Truncates the user's description for accessibility in edit mode
 * 
 * @since 3.0
 * 
 * @param string $bio Bio
 * @return string Truncated bio, if more than one hundred characters in length, 
 *                or empty string if bio is invalid
 */
function describr_profile_field_accessible_description_excerpt( $bio ) {
    if ( ! ( is_numeric( $bio ) || is_string( $bio ) ) ) {
        return '';
    }

    return wp_trim_words( $bio, 20 );
}
add_filter( 'describr_profile_field_accessible_description', 'describr_profile_field_accessible_description_excerpt', 10, 1 );