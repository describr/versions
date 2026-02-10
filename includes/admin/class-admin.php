<?php
namespace describr\admin;

/**
 * Admin class
 *
 * @package Describr
 * @since 3.0
 */
class Admin {
    /**
     * Admin constructor
     * 
     * @since 3.0
     */
    public function __construct() {
        add_filter( 'display_post_states', array( __CLASS__, 'label_plugin_pages' ), 10, 2 );
        add_filter( 'plugin_row_meta', array( __CLASS__, 'add_plugin_row_meta' ), 10, 2 );
    }
        
    /**
     * Identifies pages used by the plugin in the Pages list table
     *
     * @since 3.0
     *
     * @param array    $post_states An array of post display states
     * @param WP_Post  $post        The current post object
     * @return array An array of post display states, including the plugin's label 
     */
    public static function label_plugin_pages( $post_states, $post ) {
        if ( in_array( $post->ID, describr()->pages()->get_pages_ids(), true ) ) {
            $post_states[] = DESCRIBR;
        }

        return $post_states;
    }

    /**
     * Adds row meta for the plugin in the Plugins list table
     * 
     * @since 3.0
     * 
     * @param array  $plugin_meta An array of the plugin's metadata, including
     *                            the version, author, author URI, and plugin URI
     * @param string $plugin_file Path to the plugin file relative to the plugins directory
     * @return array
     */    
    public static function add_plugin_row_meta( $plugin_meta, $plugin_file ) {
        if ( in_array( $plugin_file, array( DESCRIBR_FILE ), true ) ) {
            $plugin_meta[] = sprintf(
                '<a href="%1$s" aria-label="%2$s">%3$s</a>',
                esc_url(admin_url( 'options-general.php?page=describr-options' )),
                esc_attr(sprintf(
                    /*translators: %s: Plugin's name.*/
                    _x( 'View %s settings', 'plugin', 'describr' ),
                    DESCRIBR
                )),
                esc_html_x( 'View Settings', 'plugin', 'describr' )
            );
        
            $plugin_meta[] = sprintf(
                '<a href="%1$s" aria-label="%2$s">%3$s</a>',
                esc_url(sprintf(
                    /*translators: Plugin's review URL. %s: Plugin's name.*/
                    __( 'https://wordpress.org/support/plugin/%s/reviews/?filter=5', 'describr' ), 
                    'describr'
                )),
                esc_attr(sprintf(
                    /*translators: %s: Plugin's name.*/
                    _x( 'Review %s', 'plugin', 'describr' ),
                    DESCRIBR
                )),
                esc_html_x( 'Review', 'plugin', 'describr' )
            );
        }
        
        return $plugin_meta;
    }
}