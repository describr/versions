<?php
namespace describr;

/**
 * Rewrite class
 *
 * @package Describr
 * @since 3.0
 */
class Rewrite {
    /**
     * Author comments pagination permalink base
     *
     * @since 3.0
     * @var string
     */
    public $author_comments_pagination_base = 'comment-page';

    /**
     * Rewrite constructor
     * 
     * @since 3.0
     */
    public function __construct() {
        add_filter( 'rewrite_rules_array', array( $this, 'add_rewrite_rules' ), 10, 1 );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ), 10, 1 );
    }

    /**
     * Adds the plugin's rewrite rules to WordPress'
     * 
     * @since 3.0
     * 
     * @param $rules WordPress' rewrite rules 
     * @return array WordPress' rewrite rules including the plugin's
     */
    public function add_rewrite_rules( $rules ) {
        global $wp_rewrite;
        
        if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            return $rules;
        }   

        if ( has_filter( 'wpml_active_languages' ) ) {
            $wpml_active_languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 1 ) );
        }

        $plugin_rules = array();
            
        $pagination_base = trim( $wp_rewrite->pagination_base, '/' );

        foreach ( describr()->pages()->get_pages_ids() as $page_id ) {
            $post = get_post( $page_id );

            if ( isset( $post->post_name ) ) {
                $slug = sanitize_title( $post->post_name, '', 'old-save' );
                
                if ( describr_get_page_id( 'profile' ) === $page_id ) {
                    $rewrites = array();

                    $rewrites['([^/]+)/?$'] = '&describr_user=$matches[1]';
                    $rewrites['([^/]+)/'. $this->author_comments_pagination_base . '-([0-9]+)/?$'] = '&describr_user=$matches[1]&paged=$matches[2]&describr_tab=comments';
                    $rewrites['([^/]+)/' . $pagination_base . '/?([0-9]+)/?$'] = '&describr_user=$matches[1]&paged=$matches[2]&describr_tab=posts';
                    $rewrites['([^/]+)/([^/]+)/?$'] = '&describr_user=$matches[1]&describr_tab=$matches[2]';
                    $rewrites['([^/]+)/([^/]+)/([^/]+)/?$'] = '&describr_user=$matches[1]&describr_tab=$matches[2]&describr_subtab=$matches[3]';
                    
                    foreach ( $rewrites as $regex => $query ) {
                        $plugin_rules[ $slug . '/' . $regex ] = $wp_rewrite->index . "?page_id={$page_id}{$query}";
                        
                        if ( ! empty( $wpml_active_languages ) ) {
                            foreach ( $wpml_active_languages as $language_code => $language ) {
                                $trans_post_id = apply_filters( 'wpml_object_id', $post->ID, 'post', false, $language_code );
                                $trans_post = get_post( $trans_post_id );

                                if ( isset( $trans_post->post_name ) && $trans_post->post_name !== $post->post_name ) {
                                    $slug_ = sanitize_title( $trans_post->post_name, '', 'old-save' );
                                    $plugin_rules[ $slug_ . '/' . $regex ] = $wp_rewrite->index . "?page_id={$trans_post_id}{$query}&lang={$language_code}";
                                }
                            }
                        }
                    }
                } elseif ( describr_get_page_id( 'account' ) === $page_id ) {
                    $rewrites = array();

                    $rewrites['?$'] = '&describr_tab=general';
                    $rewrites['([^/]+)/?$'] = '&describr_tab=$matches[1]';

                    foreach ( $rewrites as $regex => $query ) {
                        $plugin_rules[ $slug . '/' . $regex ] = $wp_rewrite->index . "?page_id={$page_id}{$query}";
                        
                        if ( ! empty( $wpml_active_languages ) ) {
                            foreach ( $wpml_active_languages as $language_code => $language ) {
                                $trans_post_id = apply_filters( 'wpml_object_id', $post->ID, 'post', false, $language_code );
                                $trans_post = get_post( $trans_post_id );

                                if ( isset( $trans_post->post_name ) && $trans_post->post_name !== $post->post_name ) {
                                    $slug_ = sanitize_title( $trans_post->post_name, '', 'old-save' );
                                    $plugin_rules[ $slug_ . '/' . $regex ] = $wp_rewrite->index . "?page_id={$trans_post_id}{$query}&lang=$language_code";
                                }
                            }
                        }
                    }
                }
                    
                
            }
        }
        
        /**
         * Filters the plugin's rewrite rules
         *
         * @since 3.0
         * 
         * @param array $plugin_rules The plugin's rewrite rules
         */
        $plugin_rules = (array) apply_filters( 'describr_rewrite_rules', $plugin_rules );
        
        $rules = $plugin_rules + $rules;
        
        return  $rules;
    }

    /**
     * Adds the plugin's query variables to WordPress' list
     * of public query variables
     * 
     * @since 3.0
     * 
     * @param array $vars WordPress' public query variables
     * @return array WordPress' public query variables including the plugin's
     */
    public function add_query_vars( $vars ) {
        foreach ( array( 
            'user', 
            'tab', 
            'subtab',
        ) as $var ) {
            $vars[] = describr()->prefix . $var;
        }
        
        return $vars;
    }
}
