<?php
namespace describr;

/**
 * Permalinks class
 *
 * @package Describr
 * @since 3.0
 */
class Permalinks {
	/**
	 * URL of the current request
	 * 
	 * @since 3.0
	 * @var string
	 */
    public $current_url;

	/**
	 * Permalinks constructor
	 * 
	 * @since 3.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'set_current_url' ), 0 );
	}

    /**
     * Sets the current URL
     * 
     * @since 3.0
     */
    public function set_current_url() {
        $this->current_url = $this->get_current_url();
    }

    /**
     * Retrieves the requesting URL
     * 
     * @since 3.0
     * 
     * @return string The requesting URL
     */
	public function get_current_url() {
		$url = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : 'localhost';
		$url .= wp_unslash( $_SERVER['REQUEST_URI'] );

		/**
	     * Filters the URL used to make the current request
	     *
	     * @since 3.0
	     *
	     * @param string $url The requesting URL
	     */
	    $url = apply_filters( 'describr_current_url', set_url_scheme( "//$url" ) );

	   return $url;
	}
	
    /**
     * Retrieves a profile's URL on the front end
     * 
     * @since 3.0
     * 
     * @param string $nicename User's nicename
     * @param string Profile's URL
     */	
	public function profile_url( $nicename = '' ) {
	    if ( ! mb_strlen( $nicename ) ) {
            if ( is_author() ) {
                $user = get_queried_object();

			    if ( $user ) {
					$nicename = $user->user_nicename;
			    } else {
					$nicename = get_query_var( 'author_name' );

					if ( ! $nicename ) {
					    $id = get_query_var( 'author' );
						$user = get_userdata( $id );
                            
                        if ( $user ) {
                            $nicename = $user->user_nicename;
                        } else {
                            $nicename = $id;
                        }
				    }
			    }
			} else {
			    $nicename = describr_user( 'nicename' );
			}
        }
            
        /**
         * Filters whether to use the translated URL for 
         * a profile on the front end
         * 
         * @since 3.0
         * 
         * @param false  $use_locale Whether to use the translated
         *                           URL for a profile on the front end 
         * @param string $nicename   User's nicename
         */
        $localized_profile_url = apply_filters( 'describr_locale_profile_url', false, $nicename );

        if ( false !== $localized_profile_url ) {
            return ! empty( $localized_profile_url ) ? $localized_profile_url : '';
        } 
                        
        $url = $this->profile_url_add_path( get_permalink( describr()->pages()->profile ), $nicename );

        /**
         * Filters the URL for a profile on the front end
         * 
         * @since 3.0
         * 
         * @param string $url      Profile's URL on the front end
         * @param string $nicename User's nicename
         */
        return apply_filters( 'describr_profile_page_url', $url, $nicename );
	}
    
    /**
     * Retrieves the URL for an account on the front end
     * 
     * @since 3.0
     * 
     * @param string $tab Slug for a tab on the account
     * @param string The account's URL
     */ 
	public function account_url( $tab ) {
		/**
         * Filters whether to use the translated URL for 
         * an account on the front end
         * 
         * @since 3.0
         * 
         * @param false  $use_locale Whether to use the translated
         *                           URL for an account on the front end
         * @param string $tab        Slug for a tab on the account
         */
        $localized_account_url = apply_filters( 'describr_locale_account_url', false, $tab );

        if ( false !== $localized_account_url ) {
            return ! empty( $localized_account_url ) ? $localized_account_url : '';
        } 
                        
        $url = $this->account_url_add_path( get_permalink( describr()->pages()->account ), $tab );

        /**
         * Filters account's URL on the front end
         * 
         * @since 3.0
         * 
         * @param string $url Account's URL on the front end
         * @param string $tab Slug for a tab on the account
         */
        return apply_filters( 'describr_account_page_url', $url, $tab );
	}
    
    /**
     * Retrieves the URL to unsubscribe from receiving email
     * notifications
     * 
     * @since 3.0
     * 
     * @param string $email Email address for the recipient
     * @param string The unsubscribe URL
     */
	public function unsubscribe_url( $email ) {
        $key = get_option( 'describr_unsubscribe_from_emails_key', '' );
        $url = add_query_arg( 
            array(
                'unsubscribe_from_emails_key' => rawurlencode( $key ),
                'email'                       => rawurlencode( $email ),
            ),
            describr_home_url( $email )
        );

		/**
         * Filters the URL to unsubscribe 
         * from receiving email notifications
         * 
         * @since 3.0
         * 
         * @param string $url   URL to unsubscribe from receiving
         *                      email notifications
         * @param string $email Email address for the recipient
         */
        return apply_filters( 'describr_unsubscribe_url', $url, $email );
	}

    /**
     * Retrieves a URL to confirm an account
     * 
     * @since 3.0
     * 
     * @param WP_User User object
     * @return string Confirmation URL
     */
    public function confirmation_url( $user ) {
        $key = wp_generate_password( 20, false );

        $hashed = time() . ':' . wp_fast_hash( $key );
        
        if ( update_user_meta( $user->ID, 'describr_confirmation_key', $hashed ) ) {
            $url = add_query_arg(
                array(
                    'action'             => 'login',
                    'confirmaccount_key' => $key,
                    'wp_lang'            => get_user_locale( $user ),
                    'login'              => rawurlencode( $user->user_login ),
                ), 
                describr_login_url( '', $user->ID )
            );
        } else {
            $url = '';
        }
            
        /**
         * Filters the URL to confirm an account
         *
         * @since 3.0
         *
         * @param string $url   Confirmation URL
         * @param WP_User $user WP_User object for the email recipient
         */
        return apply_filters( 'describr_confirmation_url', $url, $user );
    }
    
    /**
     * Adds nicename to a profile's URL on the front end
     * 
     * @since 3.0
     * 
     * @param string $url      Profile's URL
     * @param string $nicename User's nicename
     * @param string Profile's URL
     */
	public function profile_url_add_path( $url, $nicename ) {
        $nicename = rawurlencode( $nicename );
		
        if ( $this->using_pretty_url( $url ) ) {
            $url = rtrim( $url, '/?' ) . "/$nicename";
            $url = user_trailingslashit( $url, 'page' );
        } else {
            $url = add_query_arg( 'describr_user', $nicename, $url );
        }

        return $url;
	}
    
    /**
     * Adds slug of a tab to the URL for an account
     * on the front end
     * 
     * @since 3.0
     * 
     * @param string $url      The account URL
     * @param string $nicename Tab's slug
     * @param string The Account's URL
     */
    public function account_url_add_path( $url, $tab ) {
        $tab = rawurlencode( $tab );

        if ( $this->using_pretty_url( $url ) ) {
            $url = rtrim( $url, '/?' ) . "/$tab";
            $url = user_trailingslashit( $url, 'page' );
        } else {
            $url = add_query_arg( 'describr_tab', $tab, $url );
        }

        return $url;
    }
    
    /**
     * Retrieves whether a URL is in the pretty format
     * 
     * @since 3.0
     * 
     * @param string $url The URL
     * @return bool True if the URL is pretty, otherwise false
     */
    public function using_pretty_url( $url ) {
        return ! preg_match( '/(\?|&|&amp;)(p|page_id|attachment_id)=\d+/', $url );
    }
}

