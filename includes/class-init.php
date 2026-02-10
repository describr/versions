<?php
//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describr class
 *
 * @package Describr
 * @since 1.0
 */

/**
 * Core base Config class.
 *
 * @since 3.0
 */
class Describr extends \describr\Config {
    /**
     * The plugin's prefix
     * 
     * @since 3.0
     * @var string
     */
    public $prefix = 'describr_';

    /**
     * Stores ALL classes that the plugin uses
     * 
     * @since 3.0
     * @var array
     */
    public $classes = array();
        
    /**
     * Container for the main instance of the class
     *
     * @since 3.0
     * @var Describr|null
     */
    private static $instance = null;
        
    /**
     * Whether the site is using permalinks
     * 
     * @since 3.0
     * @var bool 
     */
    public $using_permalinks = false;
    
    /**
     * Whether the plugin has jut been inserted into the database
     * 
     * @since 3.0
     * @var bool 
     */
    public static $is_plugin_activated = false;
    
    /**
     * Whether user data saved
     * by the plugin should be
     * deleted during uninstalling
     * the plugin
     * 
     * @since 3.0
     * @var bool|int
     */
    public static $save_user_data;

    /**
     * Honeypot form field
     * 
     * @see https://en.wikipedia.org/wiki/Honeypot_(computing)
     * 
     * @since 3.0
     * @var string
     */
    public $honeypot;
        
    /**
     * Describr constructor
     * 
     * @since 3.0
     */
    private function __construct() {
        spl_autoload_register( array( $this, 'autoloader' ) );
            
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { 
            //We use the mb_* functions extensively, so we make sure the mb internal encoding is set globally
            if ( ! function_exists( 'wp_set_internal_encoding' ) ) {
                if ( function_exists( 'mb_internal_encoding' ) ) {
                    $charset = get_bloginfo( 'charset' );
                    //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                    if ( ! $charset || ! @mb_internal_encoding( $charset ) ) {
                        mb_internal_encoding( 'UTF-8' );
                    }
                }
            }

            $this->classes_init();

            add_action( 'plugins_loaded', array( $this, 'require_plugin_actions_filters' ), 10 );
            
            if ( ! wp_doing_ajax() ) {
                add_action( 'plugins_loaded', array( $this, 'maybe_update_plugin' ), 11 );

                add_action( 'init', array( $this, 'maybe_activate_plugin' ), 10 );
                
                if ( is_multisite() ) {
                    add_action( 'init', array( $this, 'maybe_network_activation' ), 12 );
                }

                if ( is_admin() ) {
                    add_action( 'wp_loaded', array( __CLASS__, 'post_activated_redirect' ), 99 );
                }
            }
            
            register_activation_hook( DESCRIBR_FILE, array( __CLASS__, 'activation' ), 10, 0 );
            
            require_once 'describr-functions.php';
            require_once 'describr-locale-functions.php';
            require_once 'describr-admin-functions.php';
            require_once 'describr-comments-posts-functions.php';
        }
    }

    /**
     * Magic method for dynamically "creating" methods that
     * instantiate plugin classes
     * 
     * This class is the hub for all classes and is where ALL
     * classes are instantiated.
     * 
     * The dynamic methods, in turn, intantiate classes, and then
     * the `autoloader()` method includes the files containing these classes.
     * For example, if a method named `user` is called, `$this->user()`,
     * this magic method will create the `user()` method dynamically,
     * and `user()` will return the instance of a class named `User`
     * 
     * @see https://www.php.net/manual/en/language.oop5.overloading.php
     * 
     * @since 3.0
     * 
     * @param string $name      The name of the method being called
     * @param mixed  $arguments An enumerated array containing the 
     *                          parameters passed to the $name'ed method
     * @return object Instance of class with a name similar (not exact) to $method
     */
    public function __call( $name, $arguments ) {
        $name = strtolower( $name );
        
        if ( isset( $this->classes[ $name ] ) ) {
            return $this->classes[ $name ];
        }
        
        $namespace_sep = '\\';
        $name_ = explode( '_', $name );//The method might have an underscore
        $name_ = array_map( 'ucfirst', $name_ );//Class names are capitalized
        $name_ = implode( '_', $name_ );//Recreate class name
            
        /*Is the class to instantiate related to WordPress' admin interface?
        if yes, the class is in the sub-namespace `admin`*/
        if ( str_contains( $name, 'admin' ) ) {
            $name_ = "admin{$namespace_sep}{$name_}";
        }
            
        if ( 'cpt' === $name ) {
            $name_ = 'CPT';
        }

        //`\describr` is the global namespace for our classes
        $name_ = "{$namespace_sep}describr{$namespace_sep}$name_";
            
        //Instantiate the class and save the instance
        /****Note: PHP will use our autoload method to include dynamically the file contaning the class that we instantiate here, so we don't hard-code `require_once "path/to/class.php"` for every class*/
        $this->classes[ $name ] = new $name_();
        
        return $this->classes[ $name ];
    }

    /**
     * Autoloads classes
     * 
     * Coverts class names into file names and includes the files containing the respective classes
     * 
     * @see https://www.php.net/manual/en/function.spl-autoload-register.php
     * 
     * @since 3.0
     * 
     * @param string $fully_qualified_classname The fully qualified class name
     */
    public function autoloader( $fully_qualified_classname ) {
        $namespace_sep = '\\';
        
        //Remove leading backslash from the class name and separate sub-namespaces
        $fully_qualified_classname   = explode( $namespace_sep, ltrim( $fully_qualified_classname, $namespace_sep ) );

        //Get the class name: for example, Mynamespace\{$classname}
        $classname = $fully_qualified_classname[ count( $fully_qualified_classname )-1 ];
                
        //All our file names are lowercased
        $classname = strtolower( $classname );
            
        //Our files containing classes have dashes, not underscores
        $classname = str_replace( '_', '-', $classname );
            
        if ( str_contains( $classname, 'admin' ) ) {
            $path = 'admin' . DIRECTORY_SEPARATOR;
        } else {
            $path = '';
        }
                        
        $full_path = DESCRIBR_DIR . 'includes' . DIRECTORY_SEPARATOR . "{$path}class-{$classname}.php";

        if ( file_exists( $full_path ) ) {
            require_once $full_path;
        }
    }

    /**
     * Utility method to retrieve the main instance of the class
     *
     * The instance will be created if it does not yet exist
     *
     * @since 3.0
     *
     * @return Describr The main instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self;
        }
        
        return self::$instance; 
    }

    /**
     * Initializes classes containing actions and filters
     * 
     * @since 3.0
     */
    private function classes_init() {
        $this->using_permalinks = ! empty( get_option( 'permalink_structure' ) );
        
        $this->honeypot = 'describr_val';
        
        parent::__construct();
        
        $this->upload_photo();
        $this->avatar();
        $this->scripts();
        $this->profile();
        $this->account();
        $this->locale();
        $this->mailer();
        $this->permalinks();
        $this->redirect();
        $this->rewrite();
        $this->pages();
        $this->translation();
        $this->user();
        $this->error()->error_init();
        $this->login_register();
           
        if ( $this->is_admin() ) {
            $this->admin_options();
            $this->admin();
            $this->admin_menu();
            $this->admin_users();
        } 

        if ( is_admin() ) {
            $this->updater();
        } else {
            $this->password();
        }

        if ( is_multisite() ) {
            $this->multisite();
        }
    }
        
    /**
     * Determines if the user is strictly the WordPress Dashboard
     * and not doing a cron or ajax request 
     * 
     * This function is necessary because `is_admin()` also returns true when
     * server requests are made via ajax
     * 
     * @since 3.0
     * 
     * @return bool True if the user is in the Dashboard, otherwise false
     */
    public function is_admin() {
        return is_admin() && ( ! wp_doing_ajax() || wp_doing_cron() );
    }
        
    /**
     * Determines if the user is on the frontend
     *  
     * @since 3.0
     * 
     * @return bool True if the user is on the frontend, otherwise false
     */
    public function is_frontend() {
        if ( ! is_admin() || ( ! wp_doing_cron() && wp_doing_ajax() ) ) {
            return true;
        }

        return false;
    }
        
    /**
     * Activates plugin
     * 
     * @since 3.0
     */
    public static function activation() {
        update_option( 'describr_settings_not_added', 'yes' );

        if ( is_multisite() ) {
            update_site_option( 'describr_maybe_network_wide_activation', 1 );
        }
    }
    
    /**
     * Deletes transients
     * 
     * @since 3.0
     */
    public static function delete_transients() {
        delete_transient( 'describr_inactive_users_count' );
        delete_transient( 'describr_pending_users_count' );
    }

    /**
     * Checks whether the plugin has been activated,
     * and add call the method that adds plugin data 
     * to the site (s)
     * 
     * @since 3.0
     */
    public function maybe_activate_plugin() {
        if ( get_option( 'describr_settings_not_added' ) ) {
            delete_option( 'describr_settings_not_added' );
            $this->plugin_data_for_site();
            self::$is_plugin_activated = true;
        }
    }
    
    /**
     * Maybe updates the plugin
     * 
     * @since 3.0
     */
    public function maybe_update_plugin() {
        $blog_id = get_site_option( 'describr_updated' );
        
        if ( false !== $blog_id ) {
            delete_site_option( 'describr_updated' );

            if ( ! ( is_multisite() && is_plugin_active_for_network( DESCRIBR_FILE ) ) ) {
                switch_to_blog( (int) $blog_id );
                $this->plugin_data_for_site();
                restore_current_blog();
            } else {
                foreach ( get_sites() as $site ) {
                    switch_to_blog( $site->blog_id );
                    $this->plugin_data_for_site();
                    restore_current_blog();
                }
            }
        }
    }
    
    /**
     * Maybe activates plugin networkwide
     * 
     * @since 3.0
     */
    public function maybe_network_activation() {
        if ( get_site_option( 'describr_maybe_network_wide_activation' ) ) {
            delete_site_option( 'describr_maybe_network_wide_activation' );
            
            if ( is_plugin_active_for_network( DESCRIBR_FILE ) ) {
                foreach ( get_sites() as $site ) {
                    switch_to_blog( $site->blog_id );
                    $this->plugin_data_for_site();
                    restore_current_blog();
                }
            }
        }
    }

    /**
     * Adds plugin data to site (s)
     * 
     * @since 3.0
     */
    public function plugin_data_for_site() {
        $plugin_version = get_option( 'describr_version' );

        if ( $plugin_version ) {
            if ( DESCRIBR_VERSION !== $plugin_version ) {
                update_option( 'describr_version', DESCRIBR_VERSION );
            } else {
                return;
            }
        } else {
            add_option( 'describr_version', DESCRIBR_VERSION );
            add_option( 'describr_first_activation_date', gmdate( 'Y-m-d H:i:s' ) );
        }

        describr()->setup()->install();
    }  
    
    /**
     * Redirects to the plugin's settings screen after activation
     * 
     * @since 3.0
     */
    public static function post_activated_redirect() {
        if ( self::$is_plugin_activated && ( ! is_multisite() || ! is_network_admin() ) ) {
            $plugin_url = admin_url( 'options-general.php?page=describr-options&view=start' );
                        
            if ( headers_sent() ) {
                echo "<meta http-equiv='refresh' content='" . esc_attr( "0;url=$plugin_url" ) . "' />";
            } else {
                wp_redirect( $plugin_url );
            }
            exit;
        }
    } 
    
    /**
     * Requires plugin actions and filters
     * 
     * @since 3.0
     */
    public function require_plugin_actions_filters() {
        $base = 'actions-filters' . DIRECTORY_SEPARATOR;
        
        foreach ( array( 'actions-account', 'actions-global', 'actions-profile', 'filters-account', 'filters-misc', 'filters-profile', ) as $file ) {
            require_once $base . "{$file}.php";
        }
        
        if ( $this->is_admin() ) {
            require_once $base . 'actions-admin.php';
        }
    } 
        
    /**
     * Helper to uninstall the plugin
     * 
     * Called in `uninstall.php`
     * 
     * @since 3.0
     */
    public function plugin_uninstall() {
        if ( ! current_user_can( 'delete_plugins' ) ) {
            wp_die( __( 'Sorry, you are not allowed to delete plugins for this site.', 'describr' ) );
        }
        
        self::$save_user_data = describr_get_network_option( 'describr_save_plugin_data' );

        if ( is_multisite() ) {
            foreach ( get_sites() as $site ) {
                switch_to_blog( $site->blog_id );
                $this->plugin_data_for_site_remove();
                restore_current_blog();
            }
            
            $this->remove_network_options();
        } else {
            $this->plugin_data_for_site_remove();
        }
    }

    /**
     * Removes plugin data to site (s)
     * 
     * @since 3.0
     */

    private function plugin_data_for_site_remove() {
        $shortcode_pages_ids = array();

        if ( ! $this->pages ) {
            $this->pages_caps_init();
        }

        foreach ( $this->pages as $page => $settings ) {
            $page_id_ = (int) get_option( $settings['id'] );
            
            if ( $page_id_ ) {
                $shortcode_pages_ids[] = $page_id_;
            }
        }
        
        foreach ( $shortcode_pages_ids as $page_id ) {
            wp_delete_post( $page_id, true );

            $trans_page_id = apply_filters( 'wpml_object_id', $page_id, 'page', true );

            if ( $trans_page_id && $trans_page_id !== $page_id ) {
                wp_delete_post( $trans_page_id, true );
            }
        }
            
        $plugin_options = $this->get_settings( false, true );
        
        foreach ( $this->pages as $page => $settings ) {
            $plugin_options[] = $settings['id'];
        }

        foreach ( $plugin_options as $option ) {
            delete_option( $option );
        }
        
        self::delete_transients(); 
        $this->remove_caps_for_site_roles();

        flush_rewrite_rules();
    }
        
    /**
     * Removes plugin roles capabilities per site
     * 
     * @since 3.0
     */
    public function remove_caps_for_site_roles() {
        global $wp_roles, $wp_user_roles;
        
        $switched_site_roles = is_multisite();

        if ( $switched_site_roles ) {
            $_wp_roles = $wp_roles;
            $_wp_user_roles = $wp_user_roles;
        } 
        
        //Ensure current blog roles are load in a multisite environment
        unset( $wp_roles, $wp_user_roles );

        $blog_roles = wp_roles();

        foreach ( array_keys( $this->caps ) as $cap ) {
            foreach ( $blog_roles->role_objects as $role => $role_obj ) {
                $role_obj->remove_cap( $cap );
            }
        }
        
        if ( $switched_site_roles ) {
            //Reset global roles
            $wp_roles = $_wp_roles;
            $wp_user_roles = $_wp_user_roles;
        }   
    }
        
    /**
     * Deletes network settings
     * 
     * @since 3.0
     */
    public function remove_network_options() {
        $options = array_merge( 
            array_keys( static::network_options() ), 
            static::email_misc_options(), 
            static::email_mta_options(),
            static::email_logo_options()
        );
        
        foreach ( array( 'unsubscribed_from_email_notif_emails', 'updated', 'maybe_network_wide_activation' ) as $option ) {
            $options[] = $this->prefix . $option;
        }

        foreach ( $options as $option ) {
            delete_site_option( $option );
        }
    }
}


/**
 * Returns DESCRIBR object
 * 
 * @since 3.0
 * 
 * @return DESCRIBR object
 */
function describr() {
    static $instance = null;

    if ( null === $instance ) {
        $instance = Describr::get_instance();
    }

    return $instance;
}

describr();