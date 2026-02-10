<?php
/**
 * Global actions.
 * 
 * @package Describr
 * @since 3.0
 */

//Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initializes the JavaScript plugin namespace
 * 
 * @since 3.0
 */
function describr_js_namespace() {
    if ( ! describr_is_profile_viewable_() && ! ( describr_is_page( 'account' ) && is_user_logged_in() ) ) {
        return;
    }
    
    $script = 'window.describr = { 
                    settings : {}, 
                    ajaxurl  : %s';
    
    if ( describr_is_profile_viewable_() ) {
        $script .= ', 
                    profile : { 
                        tabs   : {}, 
                        fields : {}, 
                        mode   : false 
                    }';
    }
    
    $script .= '};';
    
    //The descibr-main handle is registered in wp-content/plugins/describr/includes/class-scripts.php
    wp_add_inline_script( 
        'describr-main', 
        sprintf( $script, json_encode( admin_url( 'admin-ajax.php', 'relative' ) ) ), 
        'before' 
    );
}
add_action( 'wp_head', 'describr_js_namespace', 0 );

/**
 * Deletes the user meta for the pending email change
 * 
 * @since 3.0
 */
function describr_dismiss_newemail() {
    if ( ! empty( $_GET['dismiss'] ) && is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        
        if ( $current_user->ID . '_new_email' === $_GET['dismiss'] ) {
            $query_arg = 'describr_nonce';
            
            $action = 'dismiss-' . $current_user->ID . '_new_email';
            
            $result = isset( $_GET[ $query_arg ] ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $query_arg ] ) ), $action ) : false;

            if ( ! $result ) {
                wp_nonce_ays( $action );
                die();
            }

            delete_user_meta( $current_user->ID, '_new_email' );
            
            $goto = wp_get_referer();
            
            if ( ! $goto ) {
                $goto = describr_current_url();
            }

            $goto = remove_query_arg( array( 'dismiss', $query_arg ), $goto );
            wp_redirect( add_query_arg( 'updated', 'true', $goto ) );
            die();
        }
    }
}
add_action( 'template_redirect', 'describr_dismiss_newemail', 11 );
