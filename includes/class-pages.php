<?php
namespace describr;

/**
 * Pages class
 *
 * @package Describr
 * @since 3.0
 */
class Pages {        
    /**
     * Stores pages created by the plugin
     * 
     * Structure: array(
     *   'user'    => $page_id
     *   'account' => $page_id
     * )
     * 
     * @since 3.0
     * @var array
     */
    private $pages = array();
    
    /**
     * Pages constructor
     * 
     * @since 3.0
     */
    public function __construct() {
        add_action( 'init', array( $this, 'set_pages' ), 1 );
    }
        
    /**
     * Fetches the plugin pages from the database and stores them for later use
     * 
     * @since 3.0
     */
    public function set_pages() {
        foreach ( describr()->pages as $page => $settings ) {
            $this->pages[ $page ] = (int) get_option( $settings['id'], 0 );
        }
            
        $this->pages = array_filter( $this->pages );
    }
    
    /**
     * Retrieves the pages' IDs
     * 
     * @since 3.0
     * 
     * @return array An array of page IDs
     */
    public function get_pages_ids() {
        return array_values( $this->pages );
    }
    
    /**
     * Magic method to retrieve pages' information
     * 
     * @since 3.0
     * 
     * @param string $key Name of page
     * @return mixed
     */
    public function __get( $key ) {
        if ( 'profile' === $key ) {
            $key = 'user';
        }

        if ( $this->__isset( $key ) ) {
            if ( 'pages' === $key ) {
                return $this->pages;
            }

            return (int) apply_filters( 'wpml_object_id', $this->pages[ $key ], 'page', true );
        }

        return '';
    }
    
    /**
     * Retrieves whether page exists
     * 
     * @since 3.0
     * 
     * @param string $key Name of page
     * @return True if page exists, otherwise false
     */
    public function __isset( $key ) {
        if ( 'profile' === $key ) {
            $key = 'user';
        }
            
        if ( 'pages' === $key ) {
            return true;
        }

        if ( isset( $this->pages[ $key ] ) ) {
            $trans_page_id = apply_filters( 'wpml_object_id', $this->pages[ $key ], 'page', true );
            $post = get_post( $trans_page_id );

            if ( ! $post ) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }
}
