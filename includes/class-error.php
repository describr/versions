<?php
namespace describr;

/**
 * Describr Error class.
 * 
 * Container for checking for Describr errors and error messages.
 * Collect fields' errors that are output next to the fields in edit mode.
 *
 * @package Describr
 * @since 3.0
 */

/**
 * Extends WordPress Error class.
 * 
 * @since 3.0
 */
class Error extends \WP_Error {
    /**
     * Adds {@see 'init'} action that further
     * sets errors based on arguments in the URL
     *
     * @since 3.0
     */
    public function error_init() {
        add_action( 'init', array( $this, 'init' ), 10 );
    }
    
    /**
     * Adds {@see 'template_redirect'} filter that sets
     * errors based on arguments in the URL
     *
     * @since 3.0
     */
    public function init() {
        if ( ! is_admin() ) {
            add_action( 'template_redirect', array( $this, 'set_field_error' ), 10 );
        }
    }

    /**
     * Sets field errors
     *
     * @since 3.0
     */
    public function set_field_error() {
        if ( describr_is_page( 'account' ) ) {
            if ( isset( $_GET['error_'] ) && ! empty( $_GET['msg'] ) ) {
                $errCode = sanitize_text_field( wp_unslash( $_GET['error_'] ) );
                $errMsg = sanitize_text_field( wp_unslash( $_GET['msg'] ) );
            
                switch ($errCode) {
                    case 'invalid_user_id':
                        $_GET['updated'] = 'err_no_user';
                        break;
                    case 'user_nicename_too_long':
                        $this->add( 'user_nicename', $errMsg );
                        break;
                    case 'existing_user_email':
                        $this->add( 'user_email', $errMsg );
                        break;
                }
            }

            if ( isset( $_GET['updated'] ) ) {
                $var = sanitize_text_field( wp_unslash( $_GET['updated'] ) );

                if ( str_contains( $var, 'single_user_pass_' ) ) {
                    unset( $_GET['updated'] );
                    $var = str_replace( 'single_user_pass_', '', $var );

                    if ( 'empty' === $var ) {
                        $this->add( 'single_user_pass', __( 'Your password is required.', 'describr' ) );
                    } elseif ( 'incorrect' === $var ) {
                        $this->add( 'single_user_pass', __( 'Your password is incorrect.', 'describr' ) );
                    }
                }
            }
        }
    }
}
