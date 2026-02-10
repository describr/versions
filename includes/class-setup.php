<?php
namespace describr;

/**
 * Setup class.
 * 
 * Inserts the plugin's settings and pages into the database.
 *
 * @package Describr
 * @since 3.0
 */
class Setup {
    /**
	 * Installs the plugin
     * 
     * @since 3.0
	 */
	public function install() {
		$this->add_caps_to_roles();
		$this->add_settings();
		$this->add_plugin_page_settings();
		$this->add_field_settings();
        $this->add_user_default_account_status();
	}

	/**
     * Adds the plugin's settings
     * 
     * @since 3.0
     */
    private function add_settings() {        
        $updater = md5( DESCRIBR . time() . DESCRIBR_VERSION );

        foreach ( describr()->get_settings( true ) as $option_name => $option_value ) {
            $current_value = get_option( $option_name, $updater );

            if ( $updater === $current_value ) {
                add_option( $option_name, $option_value );
            }
            
        }
            
        if ( is_multisite() ) {
            foreach ( describr()->network_options() as $wpmu_option_name => $wpmu_option_value ) {
                $current_value = get_site_option( $wpmu_option_name, $updater );

                if ( $updater === $current_value ) {
                    add_site_option( $wpmu_option_name, $wpmu_option_value );
                }
            }
        }

        foreach ( array_keys( describr()->account_tabs_settings() ) as $option_name ) {
            $current_value = get_option( $option_name, $updater );

            if ( $updater === $current_value ) {
                add_option( $option_name, 1 );
            }
        }
    }

    /**
     * Assigns capabilities to user roles
     * 
     * @since 3.0
     */
	private function add_caps_to_roles() {
        global $wp_roles, $wp_user_roles;
        
        $switched_site_roles = is_multisite();

        if ( $switched_site_roles ) {
            $_wp_roles = $wp_roles;
            $_wp_user_roles = $wp_user_roles;

            //Ensure current blog roles are load in a multisite environment
            unset( $wp_roles, $wp_user_roles );
        }
        
        $caps = (array) describr()->caps;
        
        foreach ( array_keys( $caps ) as $cap ) {
            foreach ( wp_roles()->role_objects as $role => $role_obj ) {
                /*delete_user_front capability only allows users to delete their own accounts on the site's front end.
                WordPress only grant admins the capability to delete users, so we maintain this by allowing admin to grant the delete_account_front capability*/
                if ( 'delete_user_front' === $cap ) {
                    continue;
                }

                $role_obj->add_cap( $cap );
            }
        }
        
        if ( $switched_site_roles ) {
            //Reset global roles
            $wp_roles = $_wp_roles;
            $wp_user_roles = $_wp_user_roles;
        }
    }
                
    /**
     * Inserts the pages containing the shortcodes into the database
     * 
     * @since 3.0
     */
    public function add_plugin_page_settings() {
        $is_page = false;

        foreach ( describr()->pages as $page => $settings ) {
            $option = $settings['id'];

            if ( 0 === get_option( $option, 0 ) ) {
                $page_id = wp_insert_post(
                    array(
                        'post_type'    => 'page',
                        'post_title'   => $settings['title'],
                        'post_content' => $settings['content'],
                        'post_status'  => 'publish',
                        'post_name'    => $page,
                    )
                );

                if ( $page_id ) {
                    $is_page = true;
                    add_option( $option, $page_id );
                }
            }
        }

        if ( $is_page ) {
            describr()->pages()->set_pages();
            flush_rewrite_rules();
        }
    }
    
    /**
     * Insert the fields into the database
     * 
     * @since 3.0
     */
    public function add_field_settings() {
        $fields = describr_get_all_field_keys();

        unset($fields['ID']);
        
        //These fields are never updated by admin
        $fields_ = get_option( 'describr_fields_', array() );
        
        if ( $fields_ ) {
            //Only update new fields
            $fields_to_update = array_diff( $fields, $fields_ );
        } else {
            $fields_to_update = $fields;
        }
        
        //Test for changes and update
        if ( $fields_ !== $fields ) {
            update_option( 'describr_fields_', $fields );
        }
        
        //These fields are managed by admin
        $_fields = get_option( 'describr_fields', array() );
        
        $_fields_ = $_fields;

        //Test if admin is managing fields that no longer exist and remove them
        if ( $_fields && $_fields !== array_intersect( $_fields, $fields ) ) {
            $_fields = array_values( array_intersect( $_fields, $fields ) );
        }
        
        if ( $fields_to_update || $_fields_ !== $_fields ) {
            $fields_to_update = array_merge( $_fields, $fields_to_update );

            update_option( 'describr_fields', array_filter( array_unique( $fields_to_update ) ) );
        }
    }
        
    /**
     * Approves users without account status
     * 
     * @since 3.0
     */
    private function add_user_default_account_status() {
        static $is_user_status_added = false;

        if ( $is_user_status_added ) {
            return;
        }

        global $wpdb;

        $user_ids = $wpdb->get_col( "SELECT DISTINCT ID FROM $wpdb->users INNER JOIN $wpdb->usermeta ON ID = user_id WHERE user_id NOT IN (SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'account_status' )" );
        
        if ( $user_ids ) {
            $is_user_status_added = true;
            
            foreach ( $user_ids as $user_id ) {
                $wpdb->insert( 
                    $wpdb->usermeta, 
                    array( 
                        'meta_key'   => 'account_status', 
                        'meta_value' => 'approved',
                        'user_id'    => $user_id,
                    )
                );
            }
        }
    }
}
