<?php
namespace describr;

/**
 * Scripts class
 *
 * @package Describr
 * @since 3.0
 */
class Scripts {
    /**
     * Scripts constructor
     * 
     * @since 3.0
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_scripts' ), 10 );
    }
        
    /**
     * Registers scripts
     * 
     * @since 3.0
     * @since 3.0.1 Registers `describr-popper` before returning in admin.
     */
    public function register_scripts() {
        if ( wp_doing_ajax() ) {
            return;
        }
            
        $dir_sep = DIRECTORY_SEPARATOR;
        $assets_url = DESCRIBR_URL . 'assets/';
        $js_url = $assets_url . 'js/';
        $js_lib_url = $js_url . 'lib/';
        $css_url = $assets_url . 'css/';

        $assets_dir = DESCRIBR_DIR . 'assets' . $dir_sep;
        $assets_dir_js  = $assets_dir . "js$dir_sep";
        $assets_dir_css = $assets_dir . "css$dir_sep";

        $dep = array();

        //Inline scripts are added to the handles throughout
            
        /*Inline scripts are added to this handle in wp-content/plugins/describr/includes/actions-filters/actions-global.php*/
        wp_register_script( 'describr-main', "{$js_url}main.min.js", array( 'jquery' ), filemtime( "{$assets_dir_js}main.min.js" ), true );

        wp_register_script( 'describr-user-pw', "{$js_url}user-pw.min.js", array( 'jquery' ), filemtime( "{$assets_dir_js}user-pw.min.js" ), true );
        

        wp_register_style( 'describr-fontawesome', $css_url . 'fontawesome/brands.css', $dep, DESCRIBR_VERSION );

        //Popup library
        wp_register_script( 'describr-popper', $js_lib_url . 'popper.min.js', $dep, DESCRIBR_VERSION, true );
        
        if ( is_admin() ) {
            wp_register_script( 'describr-admin-main', "{$js_url}admin/main.min.js", array( 'jquery' ), filemtime( "{$assets_dir_js}admin{$dir_sep}main.min.js" ), true );
                
            wp_register_style( 'describr-admin-style', $css_url . 'admin/styles.min.css', $dep, filemtime( "{$assets_dir_css}admin{$dir_sep}styles.min.css" ) );
            return;
        } else {
            wp_enqueue_style( 'dashicons' );
        }
            
        /*Enqueues in wp-content/plugins/describr/includes/class-profile.php and
         * wp-content/plugins/describr/includes/class-account.php*/
        wp_register_script( 'describr-profile', "{$js_url}profile.min.js", array( 'jquery', 'underscore' ), filemtime( "{$assets_dir_js}profile.min.js" ), true );
        
        /*Enqueues in wp-content/plugins/describr/includes/class-account.php*/
        wp_register_script( 'describr-account', "{$js_url}account.min.js", array( 'jquery' ), filemtime( "{$assets_dir_js}account.min.js" ), true ); 

        //The libphonenumber library enables the verifying of phone numbers
        wp_register_script( 'describr-libphonenumber', $js_lib_url . 'libphonenumber.min.js', $dep, DESCRIBR_VERSION, true );
        
        wp_register_style( 'describr-style', $css_url . 'styles.min.css', $dep, filemtime( "{$assets_dir_css}styles.min.css" ) );

        //Country flags
        wp_register_style( 'describr-flags', $css_url . 'freakflags.min.css', $dep, filemtime( "{$assets_dir_css}freakflags.min.css" ) );

        //CSS for jQuery UI components
        wp_register_style( 'describr-jquery-ui', $css_url . 'jquery-ui/jquery-ui.min.css', $dep, DESCRIBR_VERSION );
        wp_register_style( 'describr-jquery-ui-structure', $css_url . 'jquery-ui/jquery-ui.structure.min.css', $dep, DESCRIBR_VERSION );
        wp_register_style( 'describr-jquery-ui-theme', $css_url . 'jquery-ui/jquery-ui.theme.min.css', $dep, DESCRIBR_VERSION );
    }
}
