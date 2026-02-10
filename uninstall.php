<?php
/**
 * Uninstall Describr
 * 
 * @package Describr
 * @since 2.0
 */

//Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! current_user_can( 'delete_plugins' ) ) {
	wp_die( __( 'Sorry, you are not allowed to delete plugins for this site.', 'describr' ) );
}

if ( ! defined( 'DESCRIBR_DIR' ) ) {
	define( 'DESCRIBR_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DESCRIBR_TEMPLATE' ) ) {
	define( 'DESCRIBR_TEMPLATE', DESCRIBR_DIR . 'templates' . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'DESCRIBR_FILE' ) ) {
	define( 'DESCRIBR_FILE', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'DESCRIBR_URL' ) ) {
	define( 'DESCRIBR_URL', plugin_dir_url( __FILE__ ) );
}

$files_dir = DESCRIBR_DIR . 'includes' . DIRECTORY_SEPARATOR;

if ( ! function_exists( 'describr_get_field' ) ) {
	require_once $files_dir . 'describr-functions.php';
}

require_once $files_dir . 'class-config.php';
require_once $files_dir . 'class-init.php';
require_once $files_dir . 'describr-locale-functions.php';//Translation functions might be called.

//Remove options.
describr()->plugin_uninstall();

if ( empty( describr()::$save_user_data ) ) {
	$fields = describr_get_field();

    if ( ! $fields ) {
    	$action = 'describr_pre_default_fields_init';

    	if ( ! has_action( $action, array( describr(), 'set_social_fields' ) ) ) {
    		add_action( $action, array( describr(), 'set_privacy_choice' ), 10 );
            add_action( $action, array( describr(), 'fields_asides_settings_init' ), 12 );
            add_action( $action, array( describr(), 'set_social_fields' ), 13 );
    	}

    	describr()->default_fields_init();

    	$fields = describr_get_field();
    }
    
    global $wpdb;

	//Delete avatars from the filesystem.
    foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT ID FROM $wpdb->users INNER JOIN $wpdb->usermeta ON ID = user_id WHERE meta_key = %s", describr_photo_key() ) ) as $user_id ) {
        describr()->upload_photo()->delete_picture( $user_id );
    }    

    unset( $fields['user_pass'], $fields['single_user_pass'], $fields['profile_url'] );
    
    $usermeta_fields_to_delete = array();

    foreach ( $fields as $field => $settings ) {
	    $field_ = $field;

	    if ( isset( $settings['name'] ) && $field_ !== $settings['name'] ) {
		    $field_ = $settings['name'];
	    }
    
        if ( describr()->is_blacklisted_field( $field_ ) ) {
    	    continue;
        }

	    $usermeta_fields_to_delete[] = $field_;

	    $aside_settings = describr_get_aside( $field );

	    if ( $aside_settings ) {
	    	$meta_key = isset( $aside_settings['_cat'] ) ? $aside_settings['_cat'] : $field;
		    
		    unset($aside_settings['_cat']);
		    
		    foreach ( $aside_settings as $aside => $aside_settings_ ) {
		    	if ( isset( $aside_settings_['name'] ) ) {
		    		$usermeta_fields_to_delete[] = "{$meta_key}_{$aside_settings_['name']}";
		    	}
		    }
	    }
    }

    $usermeta_fields_to_delete = array_diff( 
    	$usermeta_fields_to_delete, 
    	array( 
    		'first_name',
    		'last_name', 
    		'nickname', 
    		'description', 
    		'locale', 
    	) 
    );
    
    $usermeta_fields_to_delete = array_diff( $usermeta_fields_to_delete, describr()->user()->wp_users );

    $usermeta_fields_to_delete[] = 'active_by_admin';
    $usermeta_fields_to_delete[] = 'account_status';
        
    $usermeta_fields_to_delete = array_filter( array_unique( $usermeta_fields_to_delete ) );

    if ( $usermeta_fields_to_delete ) {
        $wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key IN ('" . implode( "','", esc_sql( $usermeta_fields_to_delete ) ) . "')" );
    }
    
    //describr_messages_keys, describr_confirmation_key, describr_time_last_login, and describr_nicename_updated.
    $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s", $wpdb->esc_like( 'describr_' ) . '%' ) );
}



