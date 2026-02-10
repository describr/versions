<?php
namespace describr;

/**
 * Redirect class
 *
 * @package Describr
 * @since 3.0
 */
class Redirect {
    /**
     * User ID
     * 
     * @since 3.0
     * @var int
     */
    public static $user_id = 0;

    /**
     * Roles redirect locations array keys
     * 
     * @since 3.0
     * @var array
     */
    public static $redirect_afters = array( 
        'register_redirect',
        'login_redirect',
        'logout_redirect',
        'deleteaccount_redirect',
    );

    /**
     * Maps compatibility keys
     * 
     * @since 3.0
     * @var array
     */
    private static $compat_keys;

    /**
     * Redirect constructor
     * 
     * @since 3.0
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ), 10 );
        add_action( 'init', array( $this, 'set_current_user_id' ), 11 );
        add_action( 'init', array( $this, 'user_id_init' ), 11 );
        add_action( 'init', array( $this, 'redirect_after_init' ), 11 );
                
        if ( ! isset( self::$compat_keys ) ) {
            self::$compat_keys = array(
                'after_register' => 'register_redirect',
                'after_login'    => 'login_redirect',
                'after_delete'   => 'deleteaccount_redirect',
                'after_logout'   => 'logout_redirect',
            );
        }
    }
        
    /**
     * Initializes redirects
     * 
     * @since 3.0
     */
    public function init() {
        if ( is_admin() ) {
            if ( ! wp_doing_ajax() ) {
                add_action( 'admin_init', array( $this, 'redirect_unauth_user_from_admin_screen' ), 10 );
            }
        } else {
            add_action( 'template_redirect', array( $this, 'redirect_to_plugin_profile' ), 10 );
        }

        add_filter( 'x_redirect_by', array( $this, 'add_custom_x_redirect_by' ), 10, 0 );
    }

    /**
     * Sets the x_redirect_by header
     * 
     * @since 3.0
     * 
     * @param string The x_redirect_by header
     */
    public function add_custom_x_redirect_by() {
        /**
         * Filters the site's name as the x_redirect_by header
         * 
         * @since 3.0
         * 
         * @param $x_redirect_by The site's name
         * */
        return apply_filters( 'describr_x_redirect_by', get_bloginfo( 'name', 'display' ) );
    }
    
    /**
     * Sets the user ID of the logged-in user
     * 
     * @since 3.0
     */
    public function set_current_user_id() {
        self::$user_id = get_current_user_id();
    }
    
    /**
     * Adds actions that set the user ID for redirects
     * 
     * @since 3.0
     */
    public function user_id_init() {
        add_action( 'register_new_user', array( $this, 'set_registered_user_id' ), 10, 1 );
        add_action( 'wp_login', array( $this, 'set_logged_in_user_id' ), 10, 2 );
        add_action( 'wp_logout', array( $this, 'set_logged_out_user_id' ), 10, 1 );
        add_action( 'deleted_user', array( $this, 'set_deleted_user' ), 10, 3 );
    }
    
    /**
     * Adds actions that redirects a user
     * 
     * @since 3.0
     */
    public function redirect_after_init() {
        foreach ( array( 'register_new_user', 'wp_login', 'wp_logout' ) as $action ) {
            add_action( $action, array( $this, 'redirect_to' ), 11, 0 );
        }
    }
    
    /**
     * Sets $_REQUEST['redirect_to'] after a specific action
     * 
     * @since 3.0
     */
    public function redirect_to() {
        if ( empty( $_REQUEST['redirect_to'] ) ) {
            switch (current_action()) {
                case 'register_new_user':
                    $action = 'register';
                    break;
                case 'wp_logout':
                    $action = 'logout';
                    break;
                case 'wp_login':
                default:
                    $action = 'login';
                    break;
            }

            $redirect_to = describr_redirect_after_url( $action, self::$user_id );

            if ( ! empty( $redirect_to ) ) {
                $redirect_to = wp_slash( $redirect_to );
                self::frontend_redirect_filters();
                $_REQUEST['redirect_to'] = $redirect_to;
                $_POST['redirect_to']    = $redirect_to;
                $_GET['redirect_to']     = $redirect_to;
            }
        }
    }

    /**
     * Sets the ID of the newly registered user
     * 
     * @since 3.0
     * 
     * @param int $user_id ID of the registered user
     */
    public function set_registered_user_id( $user_id ) {
        self::$user_id = $user_id;
    }
    
    /**
     * Sets the ID of the logged-in user
     * 
     * @since 3.0
     * 
     * @param string  $user_login Login of the logged-in user
     * @param WP_User $user       WP_User object of the logged-in user
     */
    public function set_logged_in_user_id( $user_login, $user ) {
        self::$user_id = $user->ID;
    }
    
    /**
     * Sets the ID of the logged-out user
     * 
     * @since 3.0
     * 
     * @param int $user_id ID of the logged-out user
     */
    public function set_logged_out_user_id( $user_id ) {
        self::$user_id = $user_id;
    }
    
    /**
     * Sets the WP_User object of the deleted user
     * 
     * @since 3.0
     * 
     * @param int      $user_id  ID of the deleted user
     * @param int|null $reassign ID of the user to reassign posts and links to
     * @param WP_User  $user     WP_User object of the deleted user
     */
    public function set_deleted_user( $user_id, $reassign, $user ) {
        self::$user_id = $user;
    }

    /**
     * Redirects unauthorized users from admin screens
     * 
     * @since 3.0
     */
    public function redirect_unauth_user_from_admin_screen() {
        if ( is_user_logged_in() && ! current_user_can( 'view_admin_screen' ) ) {
            $url = describr_home_url( get_current_user_id() );
            wp_redirect( $url );
            exit;
        }
    }

    /**
     * Redirects from WordPress default profile 
     * page(home_url/author/user_nicename) to the plugin 
     * profile page (home_url/user/user_nicename)
     * 
     * @since 3.0
     */
    public function redirect_to_plugin_profile() {
        if ( is_author() && get_option( 'describr_author_redirect' ) ) {
            $url = describr_profile_url( get_queried_object()->user_nicename ); 
            
            if ( $url ) {
                wp_redirect( $url );
                exit;
            }
                                 
        }
    }
    
    /**
     * Performs a safe redirect
     * 
     * @since 3.0
     * 
     * @param string The URL to redirect to
     */
    public static function safe_redirect( $url ) {
        self::frontend_redirect_filters();
        wp_safe_redirect( $url );
        exit;
    }
    
    /**
     * Adds frontend redirect filters
     * 
     * @since 3.0
     */
    public static function frontend_redirect_filters() {
        add_filter( 'allowed_redirect_hosts', array( __CLASS__, 'allowed_redirect_hosts' ), 10 );
        add_filter( 'wp_safe_redirect_fallback', array( __CLASS__, 'wp_safe_redirect_fallback' ), 10, 2 );
    }
    
    /**
     * Retrieves the list of allowed hosts to redirect to
     * 
     * @since 3.0
     * 
     * @param array $hosts An array of allowed host names
     * @return array The list of allowed hosts to redirect to
     */
    public static function allowed_redirect_hosts( $hosts ) {
        $roles_redirects = get_option( 'describr_roles_redirects', array() );
        $allowed_hosts = array();
        
        $editable_roles = get_editable_roles();
        
        foreach ( $roles_redirects as $role => $locations ) {
            if ( ! empty( $editable_roles[ $role ] ) ) {
                foreach ( self::$redirect_afters as $redirect_after ) {
                    if ( ! empty( $locations[ $redirect_after ] ) ) {
                        $url = describr_location_url( $locations[ $redirect_after ] );

                        if ( '' !== $url ) {
                            $parsed_url = wp_parse_url( $url );

                            if ( isset( $parsed_url['host'] ) ) {
                                $host = mb_strtolower( $parsed_url['host'] );
                                $allowed_hosts[] = $host;

                                if ( str_replace( 'www.', '', $host ) !== $host ) {
                                    $allowed_hosts[] = str_replace( 'www.', '', $host );
                                }
                            }
                        }
                    }
                }
            }
        }

        if ( $allowed_hosts ) {
            $allowed_hosts = array_unique( $allowed_hosts );
        }

        /**
         * Filters the site's allowed redirect hosts
         *
         * @since 3.0
         *
         * @param array $allowed_hosts Describr fallback URL
         * @param array $hosts         WordPress allowed hosts
         */
        $allowed_hosts = apply_filters( 'describr_allowed_redirect_hosts', $allowed_hosts, $hosts );

        $hosts = array_merge( $hosts, $allowed_hosts );

        return $hosts;
    }
    
    /**
     * Retrieves the fallback URL when Describr uses the function
     * on the site's front end
     * 
     * @since 3.0
     * 
     * @param string $url    The fallback URL to use by default
     * @param int    $status The HTTP response status code to use
     * @return string The fallback URL
     */
    public static function wp_safe_redirect_fallback( $url, $status ) {
        $user_id = self::$user_id;

        if ( is_object( $user_id ) ) {
            $user_id = $user_id->ID;
        }

        /**
         * Filters the safe redirect fallback URL when {@see 'wp_safe_redirect'} 
         * function is used by Describr on the site's front end.
         * 
         * The default is `admin_url()`. On the frontend, we change the default to `home_url()`
         *
         * @since 3.0
         *
         * @param string $url    Describr fallback URL
         * @param string $wp_url WordPress fallback URL
         * @param string $status Redirect status
         */
        return apply_filters( 'describr_wp_safe_redirect_fallback', describr_home_url( $user_id ), $url, $status );
    }
    
    /**
     * Retrieves, by user role, the name of a location or a custom URL to
     * redirect the user after a specific action
     * 
     * @since 3.0
     * 
     * @param string $after   The action prefixed with 'after_'
     * @param int    $user_id User ID
     * @return string The location or URL
     */
    public function role_redirect_location( $after, $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = self::$user_id;
        }
        
        if ( is_a( $user_id, '\WP_User' ) ) {
            $user = $user_id;
            $user_id = $user->ID;
        } else {
            $user = get_userdata( $user_id );
        }

        if ( ! $user ) {
            return '';
        }

        $after_ = $after;

        if ( isset( self::$compat_keys[ $after_ ] ) ) {
            $after_ = self::$compat_keys[ $after_ ];
        }

        $user_roles = $user->roles;

        $roles_redirects = get_option( 'describr_roles_redirects', array() );
        
        $location = '';

        foreach ( $user_roles as $user_role ) {
            if ( isset( $roles_redirects[ $user_role ] ) ) {
                $locations = $roles_redirects[ $user_role ];

                if ( isset( $locations[ $after_ ] ) ) {
                    $location = $locations[ $after_ ];
                    break;
                }
            }
        }
        
        /**
         * Filters the redirect_to location: 'home', 'dashboard', 'profile', 'login'
         * or a custom URL
         * 
         * @since 3.0
         *
         * @param string $location The redirect_to location
         * @param string $after    The action that precedes the redirect
         * @param string $user_id  User ID
         */
        return apply_filters( 'describr_redirect_to_location', $location, $after, $user_id );
    }
    
    /**
     * Retrieves the location URL
     * 
     * @since 3.0
     * 
     * @param string $location The location or custom URL
     * @param int    $user_id  User ID
     * @return string The location URL
     */
    public function map_location_to_url( $location, $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = self::$user_id;
        }

        if ( is_object( $user_id ) ) {
            $user_id = $user_id->ID;
        }

        switch ( $location ) {
            case 'home':
                $url = describr_home_url( $user_id );
                break;
            case 'login':
                $url = describr_login_url( '', $user_id );
                break;
            case 'dashboard':
                $url = get_dashboard_url( $user_id );
                break;
            case 'profile':
                $url = describr_profile_url( '', $user_id );
                break;
            default:
                $url = $location;
                $location = 'custom';
                break;
        }
        
        /**
         * Filters the location URL
         * 
         * The dynamic part of the hook is the location
         * 
         * @since 3.0
         * 
         * @param string $url     URL
         * @param int    $user_id ID of the user who is being redirected
         */
        $url = apply_filters( "describr_location_{$location}_url", $url, $user_id );

        return trim( $url );
    }
    
    /**
     * Retrieves the URL to redirect to after a specific action
     * 
     * @since 3.0
     * 
     * @param string $action  The action
     * @param int    $user_id User ID
     * @return string The redirect to URL
     */
    public function redirect_after_url( $action, $user_id = 0 ) {
        $action = 'after_' . $action;
        
        $location = $this->role_redirect_location( $action, $user_id ); 
        
        return describr_location_url( $location, $user_id );

    }
}
