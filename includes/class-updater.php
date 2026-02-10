<?php
namespace describr;

/**
 * Updater class
 *
 * @package Describr
 * @since 3.0
 */
class Updater {
    /**
     * URI to locate info about
     * the plugin
     * 
     * @since 3.0
     * @var string
     */
    private $plugin_uri;

    /**
     * Plugin's slug.
     * 
     * @since 3.0
     * @var string
     */
    private $slug;

    /**
     * Updater constructor.
     * 
     * @since 3.0
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'update_init' ), 10 );            
    }
    
    /**
     * Add the action and fitlers that perform the updating of the plugin.
     * 
     * @since 3.0
     */
    public function update_init() {
        if( ! function_exists('get_plugin_data') ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        } 

        $plugin_data = get_plugin_data( DESCRIBR_DIR . basename( DESCRIBR_FILE ) );

        if ( ! empty( $plugin_data['UpdateURI'] ) && ! empty( $plugin_data['Name'] ) ) {
            $this->slug = strtolower( $plugin_data['Name'] );
            $this->plugin_uri = trailingslashit( $plugin_data['UpdateURI'] ) . "{$this->slug}/versions/main/";
                
            $hostname = wp_parse_url( sanitize_url( $this->plugin_uri ), PHP_URL_HOST );
            add_filter( 'plugins_api', array( $this, 'plugin_details' ), 999, 3 );
            add_filter( "update_plugins_{$hostname}", array( $this, 'update_plugin' ), 999, 3 );
            add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 10, 2 );
        }
    }
        
    /**
     * Displays plugin details when "View version details" is
     * clicked on the Plugins screen.
     * 
     * Credit to Business Bloomer.
     * 
     * @see https://www.businessbloomer.com/woocommerce-update-self-hosted-plugin-wp-dashboard/
     * 
     * @since 3.0
     * 
     * @param object|bool|array $results The result.
     * @param string            $action  The type of information being requested from the Plugin Installation API.
     * @param object            $args    Plugin API arguments.
     * @return object|bool|array The result.
     */
    public function plugin_details( $result, $action, $args ) {
        if ( ! is_object( $args ) ) {
            $args = (object) $args;
        }

        if ( 'plugin_information' !== $action || ! isset( $args->slug ) || $this->slug !== $args->slug ) {
            return $result;
        }
           
        $plugin_latest = $this->http_request_plugin_info();
            
        if ( ! $plugin_latest ) {
            return $result;
        }

        if ( ! isset( $plugin_latest->plugin_name, $plugin_latest->description, $plugin_latest->latest_version, $plugin_latest->download_url ) ) {
            return $result;
        }
        
        if ( ! is_object( $result ) ) {
            $result = new \stdClass();
        }

        $result->name          = $plugin_latest->plugin_name;
        $result->sections      = array( 'description' => $plugin_latest->description );
        $result->description   = $plugin_latest->description;
        $result->version       = $plugin_latest->latest_version;
        $result->download_link = $plugin_latest->download_url;
        $result->slug          = $this->slug;
        $result->external      = false;
        $result->path          = DESCRIBR_FILE;

        if ( isset( $plugin_latest->author ) ) {
            $result->author = $plugin_latest->author;
        } 
        
        if ( isset( $plugin_latest->tested_wp ) ) {
            $result->tested = $plugin_latest->tested_wp;
        }
        
        if ( isset( $plugin_latest->requires_php ) ) {
            $result->requires_php = $plugin_latest->requires_php;
        } 

        if ( isset( $plugin_latest->last_updated ) ) {
            $result->last_updated = $plugin_latest->last_updated;
        }

        if ( isset( $plugin_latest->donate_link ) ) {
            $result->donate_link = $plugin_latest->donate_link;
        }

        return $result;
    }

    /**
     * Instructs WordPress if and how update of plugin should be done.
     * 
     * Credit to Business Bloomer.
     * 
     * @see https://www.businessbloomer.com/woocommerce-update-self-hosted-plugin-wp-dashboard/
     * 
     * @since 3.0
     * 
     * @param array|false $update {
     *     The plugin update data with the latest details. Default false.
     *
     *     @type string   $id           Optional. ID of the plugin for update purposes, should be a URI
     *                                  specified in the `Update URI` header field.
     *     @type string   $slug         Slug of the plugin.
     *     @type string   $version      The version of the plugin.
     *     @type string   $url          The URL for details of the plugin.
     *     @type string   $package      Optional. The update ZIP for the plugin.
     *     @type string   $tested       Optional. The version of WordPress the plugin is tested against.
     *     @type string   $requires_php Optional. The version of PHP which the plugin requires.
     *     @type bool     $autoupdate   Optional. Whether the plugin should automatically update.
     *     @type string[] $icons        Optional. Array of plugin icons.
     *     @type string[] $banners      Optional. Array of plugin banners.
     *     @type string[] $banners_rtl  Optional. Array of plugin RTL banners.
     *     @type array    $translations {
     *         Optional. List of translation updates for the plugin.
     *
     *         @type string $language   The language the translation update is for.
     *         @type string $version    The version of the plugin this translation is for.
     *                                  This is not the version of the language file.
     *         @type string $updated    The update timestamp of the translation file.
     *                                  Should be a date in the `YYYY-MM-DD HH:MM:SS` format.
     *         @type string $package    The ZIP location containing the translation update.
     *         @type string $autoupdate Whether the translation should be automatically installed.
     *     }
     * }
     * @param array       $plugin_data Plugin headers.
     * @param string      $plugin_file Plugin filename. 
     * @return array
     */
    public function update_plugin( $update, $plugin_data, $plugin_file ) {
        //Bail if this plugin is not being updated.
        if ( DESCRIBR_FILE !== $plugin_file ) {
            return $update;
        }
           
        //Bail if update was already done.           
        if ( ! empty( $update ) ) {
            return $update;
        }
        
        $plugin_latest = $this->http_request_plugin_info();
        
        //Bail if no latest version exists.
        if ( ! isset( $plugin_latest->latest_version, $plugin_latest->download_url ) ) {
            return $update; 
        }
        
        //No update is available if the changelog's version is not greater than the current one, so bail.
        if ( ! version_compare( $plugin_data['Version'], $plugin_latest->latest_version, '<' ) ) {
            return $update;
        }
        
        $details = array(
            'slug'        => $this->slug,
            'version'     => $plugin_data['Version'],
            'new_version' => $plugin_latest->latest_version,
            'url'         => $plugin_data['PluginURI'],
            'package'     => $plugin_latest->download_url,
        );
        
        if ( isset( $plugin_latest->tested_wp ) ) {
            $details['tested_wp'] = $plugin_latest->tested_wp;
        }
        
        if ( isset( $plugin_latest->requires_php ) ) {
            $details['requires_php'] = $plugin_latest->requires_php;
        }

        return $details;
    }

    /**
     * Clears plugins' cache after update so 
     * `wp_update_plugins()` knows about the new plugin.
     * 
     * @since 3.0
     * 
     * @param object $upgrader WP_Upgrader object.
     * @param array  $args     Post-update data.
     */
    public function upgrader_process_complete( $upgrader, $args ) {
        if ( 'update' === $args['action'] && 'plugin' === $args['type'] && in_array( DESCRIBR_FILE, $args['plugins'], true ) ) {
            add_site_option( 'describr_updated', get_current_blog_id() );
            wp_clean_plugins_cache();
        }
    }
        
    /**
     * Fetches plugin changelog info remotely.
     * 
     * Credit to Business Bloomer.
     * 
     * @see https://www.businessbloomer.com/woocommerce-update-self-hosted-plugin-wp-dashboard/
     * 
     * @since 3.0
     * 
     * @return false|object.
     */
    private function http_request_plugin_info() {
        $ret = wp_remote_get( 
            $this->plugin_uri . 'latest_version.json', 
            array( 
                'timeout' => 10,   
                'headers' => array( 
                    'Accept' => 'application/json', 
                ),  
            ) 
        );
        
        if ( ! is_wp_error( $ret ) && 200 === wp_remote_retrieve_response_code( $ret ) ) {
            return json_decode( wp_remote_retrieve_body( $ret ) );
        }

        return false;
    }
}
