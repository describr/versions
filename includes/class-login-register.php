<?php
namespace describr;

/**
 * Login_Register class
 *
 * @package Describr
 * @since 3.0
 */
class Login_Register {
    /**
     * Stores error codes that shake the login form.
     * 
     * @since 3.0
     * @var array
     */
    public $shake_error_codes = array();

    /**
     * Login_Register constructor.
     * 
     * @since 3.0
     */
    public function __construct() {
        add_action( 'wp_loaded', array( $this, 'init' ), 10 );
    }

    /**
     * Adds the {@see 'login_init'} action that adds login-page-related actions and filters.  
     * 
     * @since 3.0
     */
    public function init() {
        if ( ! is_admin() ) {
            add_action( 'login_init', array( $this, 'login_init' ), 10 );
        }
    }
    
    /**
     * Initializes the login form actions and filters. 
     * 
     * @since 3.0
     */
    public function login_init() {
        add_filter( 'shake_error_codes', array( $this, 'shaked_error_codes' ), 10, 1 );
        
        add_action( 'login_form_login', array( __CLASS__, 'set_confirmation_cookie' ), 10 );
        add_action( 'login_form_login', array( $this, 'authenticate_init' ), 10 );
        
        if ( ! is_multisite() && ! is_user_logged_in() ) {
            add_action( 'login_form_register', array( __CLASS__, 'register_init' ), 10 );
            add_action( 'login_form_checkemail', array( __CLASS__, 'checkemail_init' ), 10 );
            add_action( 'login_form_login', array( __CLASS__, 'approved_init' ), 10 );            
        }
    }

    /**
     * Adds the {@see 'login_form'} action that outputs the script that sets 
     * the value attribute of the `log` field equal to the login name of the
     * newly approved user.  
     * 
     * @since 3.0
     */
    public static function approved_init() {
        /*Set in wp-content/plugins/describr/includes/class-user.php*/
        if ( ! empty( $_GET['_membership_approved'] ) ) {
            add_action( 'login_form', array( __CLASS__, 'approved_login' ), 10 );
            add_filter( 'wp_login_errors', array( __CLASS__, 'approved_message' ), 10, 1 );
            add_filter( 'login_title', array( __CLASS__, 'approved_title' ),10, 2 );
        }
    }

    /**
     * Initializes the registration form actions and filters. 
     * 
     * @since 3.0
     */
    public static function register_init() {
        if ( get_option( 'describr_registration_password_required' ) ) {
            wp_enqueue_style( 'describr-style' );
            $style = '
                    .describr input[type=text],
                    .describr input[type=password] {
                        border:1px solid #8c8f94;
                    }';
        
            wp_add_inline_style( 'describr-style', $style );
            
            add_action( 'register_form', array( __CLASS__, 'password_field' ), 10 );
        }
    }
    
    /**
     * Outputs the first password field in the registration form. 
     * 
     * @since 3.0
     */
    public static function password_field() {
        wp_enqueue_script( 'describr-main' );

        describr_enqueue_pwd_script();

        describr()->fields()->mode = 'register';
        describr()->fields()->id = absint( 'register' );
        
        $field = 'user_pass';

        $settings = describr_get_field( $field );
        
        echo '<div class="describr">';
                    
        echo describr()->fields()->edit_field( $field, $settings );

        echo '</div>';
    }
    
    /**
     * Adds filters that apply on "checkemail" action.
     */
    public static function checkemail_init() {
        add_filter( 'wp_login_errors', array( __CLASS__, 'registered_messages' ), 10, 1 );
        add_filter( 'login_title', array( __CLASS__, 'registered_title' ),10, 2 );
    }

    /**
     * Customizes the title tag content for login page.
     * 
     * @since 3.0
     * 
     * @param string $login_title Page title, with extra context added.
     * @param string $title       Original page title.
     * @return string Content for the title tag on the login page.
     */
    public static function registered_title( $login_title, $title ) {
        if ( ! empty( $_GET['checkemail'] ) ) {
            $checkemail = $_GET['checkemail'];

            if ( 'membership_pending' === $checkemail ) {
                /*translators: Login screen's title. 1: Login screen's name, 2: Site's name.*/
                $title_ = __( '%1$s &lsaquo; %2$s &#8212; Membership Pending', 'describr' );
            } elseif ( 'membership_rejected' === $checkemail ) {
                /*translators: Login screen's title. 1: Login screen's name, 2: Site's name.*/
                $title_ = __( '%1$s &lsaquo; %2$s &#8212; Membership Rejected', 'describr' );
            }
        }
            
        if ( isset( $title_ ) ) {
            $login_title = sprintf( $title_, $title, get_bloginfo( 'name', 'display' ) );
        }

        return $login_title;   
    }

    /**
     * Sets membership-related registration messages.
     *
     * @since 3.0
     *
     * @param WP_Error $errors WP_Error object.
     * @return WP_Error.
     */
    public static function registered_messages( $errors ) {
        /*Set in wp-content/plugins/describr/includes/class-user.php*/
        if ( ! empty( $_GET['checkemail'] ) ) {
            $checkemail = $_GET['checkemail'];

            if ( 'membership_pending' === $checkemail ) {
                $errors->add( 'membership_pending', sprintf(
                    /*translators: %s: Link to the login page.*/
                    __( 'Registration complete. Your membership is pending review. We will contact you by email after the review process is completed. You may then visit the <a href="%s">login page</a>.', 'describr' ), 
                        esc_url( describr_login_url() ) 
                ), 'message' );
            } elseif ( 'membership_rejected' === $checkemail ) {
                $errors->add( 'membership_rejected', sprintf(
                    /*translators: 1: Admin email. 2: Admin email.*/
                    __( 'Registration complete. Your membership has been rejected. Check your inbox or contact support at the following email address: <a href="mailto:%1$s">%2$s</a>.', 'describr' ),
                        esc_url( get_bloginfo( 'admin_email' ) ),
                        get_bloginfo( 'admin_email', 'display' )
                    ), 'message' );
                
            }
        }

        return $errors;
    }
    
    /**
     * Sets registration-approved message.
     *
     * @since 3.0
     *
     * @param WP_Error $errors WP_Error object.
     * @return WP_Error.
     */
    public static function approved_message( $errors ) {
        $errors->add( 'welcome', __( 'Registration complete. Log in below.', 'describr' ), 'message' );

        return $errors;
    } 
    
    /**
     * Adds "Welcome!" to the login page title for 
     * users approved upon registration
     * 
     * @since 3.0
     * 
     * @param string $login_title Page title, with extra context added.
     * @param string $title       Original page title.
     * @return string Customized login page title.
     */
    public static function approved_title( $login_title, $title ) {
        $login_title = sprintf( 
            /*translators: Login screen's title. 1: Login screen's name, 2: Site's name.*/
            __( '%1$s &lsaquo; %2$s &#8212; Welcome!', 'describr' ), 
            $title, 
            get_bloginfo( 'name', 'display' ) 
        );

        return $login_title;
    }

    /**
     * Sets the value attribute of the `log` field equal to the login name of the
     * approved, registered user.
     * 
     * @since 3.0
     */
    public static function approved_login() {
        /*Set in wp-content/plugins/describr/includes/class-user.php*/
        if ( ! empty( $_GET['log'] ) ) {
            $login = wp_json_encode( sanitize_text_field( wp_unslash( $_GET['log'] ) ) );
                
            wp_print_inline_script_tag( sprintf( 'document.getElementById("user_login").value=%s;', $login ) );

        }
    }

    /**
     * Adds the {@see 'authenticate'} filters that authenticate the user.  
     * 
     * @since 3.0
     */
    public function authenticate_init() {
        add_filter( 'authenticate', array( __CLASS__, 'check_confirm_account_key' ), 900, 1 );
        add_filter( 'authenticate', array( $this, 'check_activation_status' ), 999, 1 );
    }    
    
    /**
     * Sets cookie during confirm-account process.
     * 
     * Credit to WordPress login page for how to set cookie.
     * 
     * @see wp-login.php
     * 
     * @since 3.0
     */
    public static function set_confirmation_cookie() {
        if ( isset( $_GET['confirmaccount_key'] ) && isset( $_GET['login'] ) ) {
            list( $ca_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );

            $value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['confirmaccount_key'] ) );
                
            setcookie( 'describr-ca-' . COOKIEHASH, $value, 0, $ca_path, COOKIE_DOMAIN, is_ssl(), true );

            wp_redirect( remove_query_arg( array( 'confirmaccount_key', 'login' ) ) );
            exit;
        }
    }

    /**
     * Authenticates the confirm-account process.
     * 
     * Credit to WordPress login page for how to get login and key from cookie.
     * 
     * @see /wp-login.php
     * 
     * Credit to `check_password_reset_key()` for how to validate confirmation key
     * and check if the "use by" date has passed.
     * 
     * @since 3.0
     * 
     * @param null|WP_User|WP_Error  $user     WP_User if the user is authenticated.
     *                                         WP_Error or null otherwise.
     * @param string                 $username Username or email address.
     * @return null|WP_User|WP_Error
     */
    public static function check_confirm_account_key( $user ) {
        if ( ! ( $user instanceof \WP_User ) ) {
            return $user;
        }

        $ca_cookie = 'describr-ca-' . COOKIEHASH;

        if ( isset( $_COOKIE[ $ca_cookie ] ) && 0 < strpos( $_COOKIE[ $ca_cookie ], ':' ) ) {
            list( $login, $key ) = explode( ':', wp_unslash( $_COOKIE[ $ca_cookie ] ), 2 );

            $key = preg_replace( '/[^a-zA-Z0-9]+/', '', $key );
                
            if ( empty( $key ) || ! is_string( $key ) ) {
                return new \WP_Error( 'invalid_key', __( 'The confirmation key is invalid.', 'describr' ) );
            }

            if ( empty( $login ) ) {
                return new \WP_Error( 'invalid_key', __( 'The confirmation key is invalid.', 'describr' ) );
            }
      
            $_user = get_user_by( 'login', $login );
                
            if ( ! $_user || $_user->user_login !== $user->user_login ) {
                return new \WP_Error( 'invalid_key', __( 'The confirmation key is invalid.', 'describr' ) );
            }

            /**
             * Filters the expiration time for users to confirm their accounts.
             *
             * @since 3.0
             *
             * @param int $expiration The expiration time in seconds.
             */
            $expiration_duration = apply_filters( 'describr_confirmation_expiration', 2 * DAY_IN_SECONDS );

            if ( str_contains( $user->describr_confirmation_key, ':' ) ) {
                list( $pass_request_time, $pass_key ) = explode( ':', $user->describr_confirmation_key, 2 );
                $expiration_time                      = (int) $pass_request_time + $expiration_duration;
            } else {
                $pass_key        = $user->describr_confirmation_key;
                $expiration_time = false;
            }

            if ( ! $pass_key ) {
                return new \WP_Error( 'invalid_key', __( 'The confirmation key is invalid.', 'describr' ) );
            }
                
            list( $ca_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );

            setcookie( $ca_cookie, ' ', time() - YEAR_IN_SECONDS, $ca_path, COOKIE_DOMAIN, is_ssl(), true );
            
            $hash_is_correct = wp_verify_fast_hash( $key, $pass_key );  
            
            if ( $hash_is_correct && $expiration_time && time() < $expiration_time  )  {
                return static::confirm_user( $user, $user->ID );
            } elseif ( $hash_is_correct && $expiration_time ) {
                //Key has an expiration time that's passed.
                return new \WP_Error( 'expired_key', __( 'The confirmation key has expired.', 'describr' ) );
            }
            
            if ( hash_equals( $user->describr_confirmation_key, $key ) || ( $hash_is_correct && ! $expiration_time ) ) {
                /*This filter is documented in wp-content/plugins/describr/includes/class-login-register.php*/
                $return = \WP_Error( 'expired_key', __( 'The confirmation key has expired.', 'describr' ) );
                $user_id = $user->ID;

                /**
                 * Filters the returned value of an attempt to confirm an account,
                 * but an old-style key or an expired key is used.
                 *
                 * @since 3.0
                 *
                 * @param WP_Error $return  A WP_Error object denoting an expired key.
                 *                          Return a WP_User object to validate the key.
                 * @param int      $user_id The matched user ID.
                 */
                $error = apply_filters( 'describr_confirm_account_key_expired', $return, $user_id );
                
                if ( $error instanceof \WP_User ) {
                    return static::confirm_user( $user, $user_id );
                }

                return $error;
            }
        }

        return $user;
    }
    
    /**
     * Confirms user.
     *
     * @since 3.0
     *
     * @param WP_User $user    The user object.
     * @param int     $user_id User ID.
     * @return WP_User $user The user object.
     */
    public static function confirm_user( $user, $user_id ) {
        delete_user_meta( $user_id, 'describr_confirmation_key' );
        
        $confirm_status = 'confirm' === get_user_meta( $user_id, 'account_status', true );

        if ( $confirm_status ) {
            update_user_meta( $user_id, 'account_status', 'approved' );
        }
        
        clean_user_cache( $user );
        $user = get_userdata( $user->ID );
        
        if ( $confirm_status ) {
            describr()->user()->confirmed( $user->user_email );
        }

        return $user;
    }

    /**
     * Checks if the user is fit for authenticated.
     *
     * @since 3.0
     * 
     *
     * @param null|WP_User|WP_Error  $user     WP_User if the user is authenticated,
     *                                         WP_Error or null otherwise.
     * @param string                 $username Username or email address
     * @return null|WP_User|WP_Error           A WP_User object if the user is activated,
     *                                         WP_Error or null otherwise.
     */
    public function check_activation_status( $user ) {
        if ( $user instanceof \WP_User ) {
            $error = new \WP_Error();
               
            if ( 'pending' === $user->account_status ) {
                $error->add( 'membership_pending', __( 'Your membership is awaiting review.', 'describr' ), 'message' );
            } elseif ( 'rejected' === $user->account_status ) {
                $error->add( 'membership_rejected', sprintf(
                    /*translators: 1: Email address. 2: Email address.*/
                    __( 'Sorry, your membership has been rejected. Check your inbox or contact support at the following email address: <a href="mailto:%1$s">%2$s</a>.', 'describr' ),
                    esc_url( get_bloginfo( 'admin_email' ) ),
                    get_bloginfo( 'admin_email', 'display' )
                ), 'message' );
            } elseif ( 'confirm' === $user->account_status || $user->describr_confirmation_key ) {
                $error->add( 'invalid_email_confirmation', sprintf(
                    /*translators: 1: Email address. 2: Email address.*/
                    __( 'Email confirmation is required. Check your inbox for the confirmation link, or contact support at the following email address: <a href="mailto:%1$s">%2$s</a>.', 'describr' ),
                    esc_url( get_bloginfo( 'admin_email' ) ),
                    get_bloginfo( 'admin_email', 'display' )
                ), 'message' );
            } elseif ( ! describr_is_user_active( $user ) ) {
                $error->add( 'admin_deactivated_account', sprintf(
                    /*translators: 1: Email address. 2: Email address.*/
                    __( 'Your account is inactive. You may contact support at the following email address: <a href="mailto:%1$s">%2$s</a>.', 'describr' ),
                    esc_url( get_bloginfo( 'admin_email' ) ),
                    get_bloginfo( 'admin_email', 'display' )
                ), 'message' );
            }  
                    
            /**
             * Filters authenticate user errors.
             * 
             * Filters must return a WP_Error or a WP_User object.
             * 
             * @since 3.0
             * 
             * @param WP_Error object $errors Authenticate user errors.
             * @param WP_User object  $user   User who failed authentication. 
             */
            $error = apply_filters( 'describr_authenticate_user_errors', $error, $user );
                    
            if ( is_wp_error( $error ) && $error->has_errors() ) {
                $this->shake_error_codes = array_merge( $this->shake_error_codes, $error->get_error_codes() );
                return $error;
            }
        }

        return $user;
    }

    /**
     * Sets error codes that shake the login form.
     * 
     * @since 3.0
     * 
     * @param array $shake_error_codes Error codes.
     * @return array Error codes.
     */
    public function shaked_error_codes( $shake_error_codes ) {
        if ( $this->shake_error_codes ) {
            $shake_error_codes = array_merge( $shake_error_codes, $this->shake_error_codes );

            $this->shake_error_codes = array();
        }

        return $shake_error_codes;
    }
}
