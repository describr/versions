<?php
/**
 * Filters for the front end Account settings.
 * 
 * @package Describr
 * @since 3.0
 */

//Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( array( 'receive_message', 'show_profile', 'show_login', 'when_last_login_audience', 'show_join_date', 'receive_notifications' ) as $field ) {
    /**
     * Returns a field's default value if no value is saved.
     * 
     * The dynamic part of the filter's name is the field's name
     * 
     * This filter is applied in wp-content/plugins/describr/includes/describr-functions.php.
     * 
     * @since 3.0
     * 
     * @param mixed $value The field's saved value or false.
     * @return mixed The field saved value or the field's default value.
     */
    add_filter( "describr_user_{$field}_value", function ( $value ) use ( $field ) {
        if ( false === $value && true === describr()->fields()->is_editing && describr_is_page( 'account' ) ) {
            $settings = describr_get_field( $field );

            if ( ! empty( $settings['account_only'] ) && isset( $settings['default'] ) ) {
                $value = $settings['default'];
            }
        }

        return $value;
    });
}

/**
 * Disables username on Account page
 * 
 * @since 3.0
 *
 * @param  array $settings Username settings
 * @return array Username settings
 */
function describr_get_field_user_login( $settings ) {
    if ( describr_is_page( 'account' ) ) {
        $settings['disabled'] = ' disabled="disabled"';
        $settings['description'] = __( 'Usernames cannot be changed.', 'describr' );
    }
    
	return $settings;
}
add_filter( 'describr_get_field_user_login', 'describr_get_field_user_login', 10, 1 );

/**
 * Sets the "confirm email" notice
 * 
 * @since 3.0
 *
 * @param  array $settings Email settings
 * @return array Email settings including the "confirm email" notice
 */
function describr_get_field_user_email( $settings ) {
    if ( ( describr_is_page( 'account' ) && describr()->account()->user_id === get_current_user_id() ) || ( isset( $_REQUEST['describr_user'] ) && (int) $_REQUEST['describr_user'] === get_current_user_id() ) ) {
        $settings['description'] = __( 'If you change this, an email will be sent to your new email address to confirm it. <strong>The new email address will not become active until confirmed.</strong>', 'describr' );
    }
    
    return $settings;
}
add_filter( 'describr_get_field_user_email', 'describr_get_field_user_email', 10, 1 );

/**
 * Disables nicename on Account page
 * 
 * @since 3.0
 *
 * @param  array $settings Nicename settings
 * @return array Nicename settings
 */
function describr_get_field_user_nicename( $settings ) {
    if ( describr_is_page( 'account' ) ) {
        $user_id = describr()->account()->user_id;

        $user = get_userdata( $user_id );

        if ( ! empty( $user->user_nicename ) && describr_is_nicename_locked( $user_id ) ) {
            $settings['description'] = describr_nicename_locked_notice( $user_id );
            
            $settings['disabled'] = ' disabled="disabled"';
        }
    }

	return $settings;
}
add_filter( 'describr_get_field_user_nicename', 'describr_get_field_user_nicename', 10, 1 );

/**
 * Adds the display name options and changes the display name type
 * to select
 * 
 * @since 3.0
 *
 * @param array $settings Display name settings
 * @return array Display name settings
 */
function describr_get_field_display_name( $settings ) {
	$mode = describr()->fields()->mode;

	if ( 'register' === $mode ) {
		return $settings;
	}
    
    if ( 'account' === $mode ) {
    	$user_id = describr()->account()->user_id;
    } elseif ( 'profile' === $mode ) {
    	$user_id = describr_profile_id();
    } else {
    	$user_id = describr_get_user_id();
    }

    if ( ! $user_id ) {
    	return $settings;
    }

    $user = get_userdata( $user_id );
    
    if ( ! isset( $user->ID ) ) {
    	return $settings;
    }

	$public_display = array();
	$public_display['display_nickname'] = $user->nickname;
	$public_display['display_username'] = $user->user_login;

    if ( ! empty( $user->first_name ) ) {
	    $public_display['display_firstname'] = $user->first_name;
    }

	if ( ! empty( $user->last_name ) ) {
		$public_display['display_lastname'] = $user->last_name;
	}

	if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
		$public_display['display_firstlast'] = $user->first_name . ' ' . $user->last_name;
		$public_display['display_lastfirst'] = $user->last_name . ' ' . $user->first_name;
	}
    
    //Only add this if it isn't duplicated elsewhere.
	if ( isset( $user->display_name ) && ! in_array( $user->display_name, $public_display, true ) ) { 
		$public_display = array( 'display_displayname' => $user->display_name ) + $public_display;
	}

	$public_display = array_map( 'trim', $public_display );
	$public_display = array_unique( array_values( $public_display ) );
	
    if ( $public_display ) {
		$settings['options'] = $public_display;
		$settings['type']    = 'select';
	}

	return $settings;
}
add_filter( 'describr_get_field_display_name', 'describr_get_field_display_name', 10, 1 );

/**
 * Sets the text describing the site's password complexity policy
 * as the password field's description if strong password is in
 * use
 *
 * @since 3.0
 *
 * @param array $settings Password settings
 * @return array Password settings, with description
 */
function describr_get_field_user_pass( $settings ) {
	if ( ! empty( $settings['strong'] ) ) {    
        add_filter( 'password_hint', array( describr()->password(), 'hint_filter' ), 10, 1 );

        $description = wp_get_password_hint();

        remove_filter( 'password_hint', array( describr()->password(), 'hint_filter' ), 10 );

        if ( ! empty( $description ) ) {
            $settings['description'] = $description;
        }
    }

    return $settings;
}
add_filter( 'describr_get_field_user_pass', 'describr_get_field_user_pass', 10, 1 );

/**
 * Retrieves the Blocked Users tab output
 *
 * @since 3.0
 *
 * @param string $output Blocked Users tab output
 * @param array $fields  Tab fields
 * @param array $atts    Shortcode attributes
 * @param int   $user_id User ID
 * @return string Output of blocked users
 */
function describr_account_blocked_users_content( $output, $fields, $atts, $user_id ) {
    if ( ! ( empty( $output ) && is_int( $user_id ) && $user_id ) ) {
        return $output;
    }
    
    global $wpdb;
    
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value AS blocked_date FROM $wpdb->usermeta INNER JOIN $wpdb->users ON user_id = ID WHERE user_id = %s AND meta_key LIKE %s ORDER BY blocked_date DESC", array( $user_id, $wpdb->esc_like( 'describr_blocked_user_' ) . '%' ) ) );
    $items = 0;
    
    $table_rows = '';

    if ( $results ) {        
        $timezone = wp_get_current_user()->timezone;
        
        $users = isset( $_POST['describr_action'] ) && isset( $_POST['users'] ) ? array_map( 'intval', (array) $_POST['users'] ) : array();
        
        $checked_num = 0;
        
        foreach ( $results as $result ) {
            $user = get_userdata( str_replace( 'describr_blocked_user_', '', $result->meta_key ) );

            if ( ! describr_is_user_viewable( $user ) ) {
                continue;
            }
            
            $date = describr_date( describr_locale_date_format( 'date@time' ), $result->blocked_date, $timezone );
            
            $date_ = sprintf(
                /*translators: %s: Date and time.*/
                _x( 'Blocked on %s', 'restricted user', 'describr' ),
                $date
            );

            $checkbox_id = 'describr-account-blocked-user_' . $user->ID;

            $table_rows .= '<tr id="describr-account-blocked-user-' . esc_attr( $user->ID ) . '"><th scope="row" class="check-column"><input type="checkbox" name="users[]" id="' . esc_attr( $checkbox_id ) . '" value="' . esc_attr( $user->ID ) . '"';
            
            if ( in_array( $user->ID, $users, true ) ) {
                $table_rows .= ' checked="checked"';
                $checked_num++;
            }

            $table_rows .= ' />';
            $table_rows .= '<label for="' . esc_attr( $checkbox_id ) . '"><span class="describr-a11y-text">';
            $table_rows .= sprintf( 
                /*translators: %s: User's display name.*/
                __( 'Select %s', 'describr' ), 
                $user->display_name 
            );
            $table_rows .= '</span></label></th><td class="column-primary" title="' . esc_attr( $date_ ) . '">';
            $table_rows .= describr_floated_avatar( $user, 96 );
            $table_rows .= '<br /><span aria-hidden="true">' . esc_html( $date ) . '</span><span class="describr-a11y-text">' . esc_html(sprintf(
                /*translators: Hidden accessibility text. 1: User's display name. 2: Date and time.*/
                _x( '%1$s was blocked on %2$s.', 'restricted user', 'describr' ),
                $user->display_name,
                $date
            )) . '</span></td></tr>';
            
            $items++;
        }

        if ( $items && $table_rows ) {
            $select_all = /*translators: Hidden accessibility text.*/ esc_html__( 'Select All', 'describr' );
            
            $primary_col = esc_html_x( 'User', 'table column name','describr' );
            
            $output = '<table role="presentation" class="describr-table">';
            $output .= '<caption class="describr-a11y-text">';

            $output .= esc_html(sprintf(
                /*translators: Hidden accessibility text. %s: User's display name.*/
                _x( 'Table Ordered by the Date %s Blocked User. Descending.', 'restricted users', 'describr' ),
                describr_display_name( $user_id )
            ));
            $output .= '</caption><thead><tr>';
            $output .= '<td class="check-column"><input type="checkbox" id="describr-cb-select-all-1"';
            
            if ( $checked_num === $items ) {
                $output .= ' checked="checked"';
            }

            $output .= ' /><label for="describr-cb-select-all-1"><span class="describr-a11y-text">';
            $output .= $select_all;
            $output .= '</span></label></td><th scope="col">' . $primary_col . '</th>';
            $output .= '</tr></thead><tbody>';
            $output .= $table_rows;
            $output .= '</tbody><tfoot><tr>';
            $output .= '<td class="check-column"><input type="checkbox" id="describr-cb-select-all-2"';

            if ( $checked_num === $items ) {
                $output .= ' checked="checked"';
            }

            $output .=' /><label for="describr-cb-select-all-2"><span class="describr-a11y-text">';
            $output .= $select_all;
            $output .= '</span></label></td><th scope="col">' . $primary_col . '</th></tr></tfoot></table>';
            $output .= '<div class="describr-tablenav"><div class="describr-tablenav-pages"><span class="describr-displaying-num">';
            $output .= esc_html(sprintf(
                /*translators: %s Number of users.*/
                _nx( '%s blocked user', '%s blocked users', $items, 'restricted users', 'describr' ),
                number_format_i18n( $items )
            ));
            $output .= '</span></div></div>'; 

            describr_set_form_field( 'users' );          
        }
    }

    return $output;
}
add_filter( 'describr_account_blocked_users_content', 'describr_account_blocked_users_content', 10, 4 );

/**
 * Sets nonce for use to destroy user sessions
 * 
 * @since 3.0
 * 
 * @param string $output      The output to destroy sessions
 * @param bool   $can_destroy Whether user sessions can be destroyed
 * @param int    $user_id     ID of user whose sessions can be destroyed
 * @return string
 */
function describr_account_destroy_sessions_nonce( $output, $can_destroy, $user_id ) {
    if ( $can_destroy ) {
        $nonce = wp_create_nonce( 'describr-destroy-session-' . $user_id );

        $output .= '<input type="hidden" value="' . esc_attr( $nonce ) . '" id="describr-destroy-session-nonce" data-userid="' . esc_attr( $user_id ) . '" />';
    }

    return $output;
}
add_filter( 'describr_account_destroy_sessions', 'describr_account_destroy_sessions_nonce', 10, 3 );

/**
 * Sets the "account_only" setting to true if the Account page
 * is being viewed and the field is any one of user_login, first_name, 
 * last_name, nickname, display_name, and user_nicename
 * 
 * @since 3.0
 * 
 * @param array  $settings Field settings
 * @param string $field    Field
 * @return array Fields settings, with "account_only" set to true
 */
function describr_extend_account_fields( $settings, $field ) {
    if ( describr_is_page( 'account' ) && in_array( $field, array( 'user_login', 'first_name', 'last_name', 'nickname', 'display_name', 'user_nicename', 'user_email' ), true ) ) {
        $settings['account_only'] = true;
    }

    return $settings;
}
add_filter( 'describr_viewable_field_settings', 'describr_extend_account_fields', 10, 2 );
