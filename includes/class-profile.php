<?php
namespace describr;

/**
 * Profile class
 *
 * @package Describr
 * @since 3.0
 */
class Profile {
    /**
     * User ID
     * 
     * @since 3.0
     * @var int
     */
    public $ID = 0;
        
    /**
     * User ID, if indexable
     * 
     * @since 3.0
     * @var int
     */
    public $index = 0;
        
    /**
     * The current tab
     * 
     * @since 3.0
     * @var false|string
     */
    public $current_tab = false;
    
    /**
     * The current subtab
     * 
     * @since 3.0
     * @var false|string
     */
    public $current_subtab = false;

    /**
     * The default tab
     * 
     * @since 3.0
     * @var false|string
     */
    public $default_tab = false;
        
    /**
     * $_POST
     * 
     * @since 3.0
     * @var array
     */
    public $request = array();
    
    /**
     * Filters the profile
     * 
     * @example 'edit' means the current user can edit the profile
     * 
     * @since 3.0
     * @var null|string
     */
    public $filter = null;
    
    /**
     * Stores About tab schema
     * 
     * @since 3.0
     * @var array
     */
    public $about_schema = array();

    /**
     * WP_Comment_Query object
     * 
     * @since 3.0
     * @var WP_Comment_Query
     */
    public $comments;
    /**
     * Array of post objects
     *
     * @since 3.0
     * @var WP_Post[]
     */
    public $posts = array();
    
    /**
     * The profile mode
     *
     * @since 3.0
     * @var null|string
     */
    public $mode = null;

    /**
     * Stores fields having empty saved values
     *
     * @since 3.0
     * @var array
     */
    public $no_saved_data = array();

    /**
     * The number of found results for the current query
     *
     * @since 3.0
     * @var int
     */
    public $found_results = 0;

    /**
     * The number of the current page
     * 
     * @since 3.0
     * @var int
     */
    public $paged = 1;
        
    /**
     * The total number of pages
     * 
     * @since 3.0
     * @var int
     */
    public $max_num_pages;
        
    /**
     * Profile constructor
     * 
     * @since 3.0
     */
    public function __construct() {
        add_action( 'init', array( $this, 'about_schema_init' ), 100 );
        add_action( 'init', array( $this, 'init' ), 101 );
    }
        
    /**
     * Initializes hooks
     * 
     * @since 3.0
     */
    public function init() {
        if ( wp_doing_ajax() ) {
            if ( is_user_logged_in() ) {
                add_action( 'wp_ajax_describr-partner-query', array( $this, 'ajax_partner_query' ), 10 );
                add_action( 'wp_ajax_describr-change-aside', array( $this, 'ajax_update_asides' ), 10 );
                add_action( 'wp_ajax_describr-edit-profile-data', array( $this, 'ajax_update_fields' ), 10 );
                add_action( 'wp_ajax_describr-delete-profile-data', array( $this, 'ajax_delete_fields' ), 10 );
                add_action( 'wp_ajax_describr-send-message', array( $this, 'ajax_send_message' ), 10 );
                
                add_action( 'wp_ajax_describr-block-profile', array( $this, 'ajax_block_user' ), 10 );
                add_action( 'wp_ajax_describr-unblock-profile', array( $this, 'ajax_unblock_user' ), 10 );

                //Tab schema
                add_action( 'wp_ajax_describr-profile-about', array( $this, 'ajax_get_about' ), 10 );
                add_action( 'wp_ajax_describr-profile-posts', array( $this, 'ajax_get_posts' ), 10 );
                add_action( 'wp_ajax_describr-profile-comments', array( $this, 'ajax_get_comments' ), 10 );
                return;
            }
            
            //Tab schema
            add_action( 'wp_ajax_nopriv_describr-profile-about', array( $this, 'ajax_get_about' ), 10 );
            add_action( 'wp_ajax_nopriv_describr-profile-posts', array( $this, 'ajax_get_posts' ), 10 );
            add_action( 'wp_ajax_nopriv_describr-profile-comments', array( $this, 'ajax_get_comments' ), 10 );
        } elseif ( ! is_admin() ) {
            add_shortcode( describr()->shortcodes['user'] , array( $this, 'shortcode_content' ) );
            add_action( 'template_redirect', array( $this, 'profile_init' ), 10 );
            add_action( 'template_redirect', array( $this, 'enqueue_scripts' ), 11 );
            add_action( 'template_redirect', array( $this, 'message_modal_init' ), 11 );
            add_filter( 'language_attributes', array( $this, 'add_open_graph_prx' ), 10, 1 );
            add_filter( 'wp_robots', array( $this, 'profile_page_robots_noindex' ), 10, 1 );
            add_filter( 'document_title_parts', array( $this, 'customize_page_title' ), 10, 1 );
            add_filter( 'body_class', array( $this, 'add_body_class' ), 10, 1 );
            add_action( 'wp_head', array( $this, 'add_social_meta_tags' ) ,10 );
        }
    }

    /**
     * Triggers message modal using JavaScript
     * 
     * The describr_reply cookie is set in wp-content/plugins/describr/includes/class-user.php
     * 
     * @since 3.0
     */
    public function message_modal_init() {
        if ( $this->is_user() && ! empty( $_COOKIE['describr_reply'] ) ) {
            $hash = sanitize_text_field( wp_unslash( $_COOKIE['describr_reply'] ) );
            
            $reply_to = $this->ID;//ID of the user who sent the message

            $current_user = wp_get_current_user();
            
            $messages_keys = $current_user->describr_messages_keys;

            if ( ! empty( $messages_keys[ $hash ] ) && (int) $messages_keys[ $hash ] === $reply_to ) {
                unset( $messages_keys[ $hash ] );

                if ( $messages_keys ) {
                    update_user_meta( $current_user->ID, 'describr_messages_keys', $messages_keys );
                } else {
                    delete_user_meta( $current_user->ID, 'describr_messages_keys' );
                }
                
                $_POST['describr_trigger_message_modal'] = 'true';
            }
            
            $current_url = describr_current_url();

            list( $current_url_path ) = explode( '?', $current_url );

            setcookie( 'describr_reply', ' ', time() - YEAR_IN_SECONDS, $current_url_path, COOKIE_DOMAIN, 'https' === wp_parse_url( $current_url, PHP_URL_SCHEME ), true );
        }
    }
        
    /**
     * Adds the Open Graph prefix attribute to <html> tag
     *
     * @since 3.0
     *
     * @param string $output A space-separated list of language attributes
     * @return string Language attributes
     */
    public function add_open_graph_prx( $output ) {
        if ( ! $this->is_user() || ! get_option( 'blog_public', 1 ) ) {
            return $output;
        }

        $output .= ' prefix="og: https://ogp.me/ns#"';

        return $output;
    }
        
    /**
     * Outputs Open Graph and X (formerly Twitter) Cards
     * 
     * @see https://ogp.me/
     * 
     * @since 3.0
     */
    public function add_social_meta_tags() {
        if ( ! $this->is_user() || ! get_option( 'blog_public', 1 ) ) {
            return;
        }

        $user_id = $this->ID;

        $display_name = describr_display_name( $user_id );

        /**
         * Filters Open Graph's image size
         * 
         * @since 3.0
         * 
         * @param int $size    Open Graph's image size
         * @param int $user_id ID of the user to whom the profile belongs
         */
        $size = (int) apply_filters( 'describr_profile_open_graph_image_size', 200, $user_id );

        $img = get_avatar( $user_id, $size, '', $display_name );
            
        $attrs = wp_kses_attr_parse( $img );
            
        if ( ! $attrs ) {
            return;
        }
            
        foreach ( $attrs as $attr ) {
            $attr = trim( $attr );

            if ( ! isset( $image ) && 0 === mb_stripos( $attr, 'src=' ) ) {
                $attr = preg_replace( '/^src=[\'"]{1}(.*)[\'"]{1}/is', "$1", $attr );
                            
                $attr = trim( $attr );

                if ( ! empty( $attr ) ) {
                    $image = $attr;
                }
            }

            if ( ! isset( $alt ) && 0 === mb_stripos( $attr, 'alt=' ) ) {
                $attr = preg_replace( '/^alt=[\'"]{1}(.*)[\'"]{1}/is', "$1", $attr );
                                    
                $attr = trim( $attr );

                if ( strlen( $attr ) ) {
                    $alt = $attr;
                }
            }

            if ( isset( $image, $alt ) ) {
                break;
            }
        }
            
        //Test if a url is available for the profile photo, for an image is required (per the Open Graph Protocol)
        if ( ! isset( $image ) ) {
            return;
        }

        $title = trim( wp_get_document_title() );

        $description = get_user_meta( $user_id, 'description', true );
        $description = describr_falsey_to_empty_str( $description );
            
        if ( strlen( $description ) ) {
            $description = trim( wp_kses_decode_entities( $description ) );
            $description = describr_unwrap_p( $description );
        } else {
            $description = sprintf(
                /*translators: 1: User's display name. 2: Site's name.*/ 
                __( '%1$s is on %2$s.', 'describr' ), 
                $display_name, 
                get_bloginfo( 'name', 'display' ) 
            );
        }
        
        $url = describr_profile_url( '', $user_id );

        $profile_rdf = array(
            "@context"    => "http://schema.org",
            "@type"       => "Person",
            "name"        => $display_name,
            "description" => stripslashes( $description ),
            "image"       => $image,
            "url"         => $url,
        );
        ?>
        <link rel="image_src" href="<?php echo esc_url( $image ); ?>" />
        <meta property="description" content="<?php echo esc_attr( $description ); ?>" />
        <meta property="og:title" content="<?php echo esc_attr( $title ); ?>" />
        <meta property="og:type" content="profile">
        <meta property="og:url" content="<?php echo esc_url( $url ); ?>" />
        <meta property="og:image" content="<?php echo esc_url( $image ); ?>" />
        <?php
        if ( is_numeric( $size ) ) {
            ?>
            <meta property="og:image:height" content="<?php echo esc_attr( $size ); ?>" />
            <meta property="og:image:width" content="<?php echo esc_attr( $size ); ?>" />
            <?php
        }            
        ?>
        <meta property="og:description" content="<?php echo esc_attr( $description ); ?>" />
        <meta property="og:determiner" content="the" />
        <meta property="og:locale" content="<?php echo esc_attr( str_replace( '_', '-', get_user_locale( $user_id ) ) ); ?>" />
        <meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" />
        <?php
        $user_x = get_user_meta( $user_id, 'x', true );
            
        if ( ! empty( $user_x ) ) {
            $base_url = describr_get_field_( 'x', 'base_url' );

            $user_x = describr_get_handle_from_url( $base_url, $user_x );
            ?>
            <meta name="twitter:card" content="summary" />
            <meta name="twitter:creator"  content="@<?php echo esc_attr( $user_x ); ?>" />
            <?php
            $site_x = get_option( 'describr_x' );
                
            if ( ! empty( $site_x ) ) {
                $site_x = describr_get_handle_from_url( $base_url, $site_x );
            } else {
                $site_x = get_bloginfo( 'name', 'display' );
            }                
            ?>
            <meta name="twitter:site" content="@<?php echo esc_attr( $site_x ); ?>" />
            <?php
            if ( isset( $alt ) ) {
                ?>
                <meta name="twitter:image:alt" content="<?php echo esc_attr( $alt ); ?>" />
                <?php
            }
        }
        ?>
        <script type="application/ld+json"><?php echo json_encode( $profile_rdf ); ?></script>
        <?php
    }

    /**
     * Adds the plugin class names to the list of body class names
     * 
     * @since 3.0
     * 
     * @param array $classes An array of body class names
     * @return array The arguments passed in $classes, along with plugin classes
     */
    public function add_body_class( $classes ) {
        if ( describr_is_page( 'profile' ) ) {
            $classes[] = 'author';

            if ( $this->is_user() ) {
                $user = get_userdata( $this->ID );
                $classes[] = 'author-' . sanitize_html_class( $user->user_nicename );
                $classes[] = 'author-' . $this->ID;
            }

            $page = $this->paged;

            if ( $page && $page > 1 ) {
                $classes[] = 'paged-' . $page;

                if ( $this->is_user() ) {
                    $classes[] = 'author-paged-' . $page;
                }
            }
        }            
             
        return $classes;
    }

    /**
     * Initializes the user profile
     * 
     * @since 3.0
     */
    public function profile_init() {
        $this->ID = 0;

        if ( describr_is_page( 'profile' ) ) {                
            $user = get_user_by( 'slug', get_query_var( 'describr_user' ) );
                
            if ( ! empty( $user->ID ) ) {
                $this->index = $user->ID;

                if ( describr_is_user_viewable( $user->ID ) ) {
                    $this->ID = $user->ID;

                    if ( ! empty( $_GET['paged'] ) ) {
                        $this->paged = (int) $_GET['paged'];
                        set_query_var( 'paged', $this->paged );
                    } elseif ( get_query_var( 'paged', 0 ) ) {
                        $this->paged = (int) get_query_var( 'paged' );
                    }

                    if ( describr_can_edit_user( $this->ID, true ) ) {
                        $this->filter = 'edit';
                        describr()->fields()->is_editing = true;
                    } else {
                        describr()->fields()->is_viewing = true;
                    }

                    describr_fetch_user( $this->ID );

                    $this->sub_tab();
                }
                //If the user is logged in but the query didn't specify a user, redirect to the current user's profile. The cookie prevents infinite redirects.
            } elseif ( false === get_query_var( 'describr_user', false ) && is_user_logged_in() && ! isset( $_COOKIE['describr_redirect_to_profile'] ) ) {
                $current_user = wp_get_current_user();

                $url = get_permalink();
                    
                $path = strtok( $url, '?' );
                    
                setcookie( 'describr_redirect_to_profile', $current_user->user_nicename, 10, $path, COOKIE_DOMAIN, 'https' === wp_parse_url( $url, PHP_URL_SCHEME ), true );
                
                describr_maybe_js_redirect( describr_profile_url( $current_user ) );
            }
        }
    }
        
    /**
     * Enqueues profile scripts and styles
     * 
     * @since 3.0
     */
    public function enqueue_scripts() {
        if ( describr_is_page( 'profile' ) ) {
            if ( $this->is_user() ) {
                if ( describr_can_upload() ) {
                    wp_enqueue_media();
                }

                wp_enqueue_style( 'dashicons' );
                
                /*The handles are registered in wp-content/plugins/describr/includes/class-scripts.php*/       
                wp_enqueue_script( 'describr-libphonenumber' );                        
                wp_enqueue_script( 'describr-popper' );

                wp_enqueue_script( 'describr-main' );

                /**
                 * Filters whether to use jQuery's ui to supplant HTML <select> elements,
                 * as well as the autocomplete feature of <input type="text" /> elements 
                 * 
                 * @since 3.0
                 * 
                 * @param bool $use_jquery_ui Whether to use jQuery ui
                 */
                $use_jquery_ui = apply_filters( 'describr_use_jquery_ui', true );
                
                if ( $use_jquery_ui ) {
                    //Load both autocomplete and selectmenu jQuery UI methods
                    wp_enqueue_script( 'jquery-ui-autocomplete' );
                    wp_enqueue_script( 'jquery-ui-selectmenu' );

                    wp_enqueue_style( 'describr-jquery-ui' );
                    wp_enqueue_style( 'describr-jquery-ui-structure' );
                    wp_enqueue_style( 'describr-jquery-ui-theme' );
                }
                
                wp_enqueue_script( 'describr-profile' );

                if ( $use_jquery_ui ) {
                    wp_add_inline_script( 'describr-profile', 'var describrJqueryUi=true;', 'before' );
                }
                        
                wp_enqueue_style( 'describr-fontawesome' );
                wp_enqueue_style( 'describr-flags' );
                wp_enqueue_style( 'describr-style' );
            }
        }
    }

    /**
     * Determines whether the query is for an existing user archive page
     *
     * @since 3.0
     *
     * @return bool Whether the query is for an existing user archive page
     */
    public function is_user() {
        return ! empty( $this->ID );
    }
        
    /**
     * Determines whether the current user can edit the profile
     *
     * @since 3.0
     *
     * @return bool
     */
    public function can_edit() {
        return 'edit' === $this->filter;
    }

    /**
     * Adds noindex directive to 'robots' meta tag on the profile page
     * 
     * @since 3.0
     * 
     * @param array $robots Associative array of directives
     * @return array Directives to 'robots' meta tag, including 
     *               noindex directive if the current query is 
     *               for a search, the current user is invalid,
     *               or the profile is not public
     */
    public function profile_page_robots_noindex( $robots ) {
        if ( ! describr_can_profile_be_indexed( $this->index ) ) {
            return describr_robots_noindex( $robots );                
        }

        return $robots;
    }
        
    /**
     * Customizes the document title based on the current query
     *
     * @since 3.0
     *
     * @param array $title {
     *     The document title parts
     *
     *     @type string $title   Title of the viewed page
     *     @type string $page    Page number if paginated
     *     @type string $tagline Site description when on home page
     *     @type string $site    Site title when not on home page
     * }
     * @param array The parts of document title
     */
    public function customize_page_title( $title ) {
        if ( describr_is_page( 'profile' ) && $this->is_user() ) {
            $name = describr_display_name( $this->ID );

            if ( strlen( $name ) ) {
                $title['title'] = $name;
            }
        }

        return $title;
    }
        
    /**
     * Magic method for accessing inaccessible properties
     *
     * @since 3.0
     *
     * @param string $prop Property name
     * @return mixed Value of the property
     */
    public function __get( $prop ) {
        if ( 'id' === $prop ) {
            return $this->ID;
        }
    }
        
    /**
     * Retrieves the profile tabs.
     * 
     * @since 3.0
     * 
     * @return array An array containing the profile tabs.
     */
    public function tabs() {
        /**
         * Filters profile menu tabs.
         * 
         * @since 3.0
         * @since 3.1.3 Moved Tagline to the last position.
         * 
         * @param array $tabs The profile tabs.
         */
        $tabs = apply_filters( 
            'describr_profile_tabs',
            array(
                'about' => array(
                    'name'   => _x( 'About', 'user', 'describr' ),
                    'icon'   => 'dashicons dashicons-admin-users',
                    'subnav' => array(
                        'basic_info' => array(
                            'name' => /*translators: "Info" is the four-letter abbreviation of "Information".*/ _x( 'Basic Info', 'user', 'describr' )
                        ),
                        'contact_and_social' => array(
                            'name' => __( 'Contacts and Social Networks', 'describr' )
                        ),
                        'addresses_and_timezone' => array(
                            'name' => __( 'Addresses and Time Zone', 'describr' )
                        ),
                        'relationship_and_languages' => array(
                            'name' => __( 'Relationship and Languages', 'describr' )
                        ),
                        'work_and_education' => array(
                            'name' => __( 'Work and Education', 'describr' )
                        ),
                        'tagline' => array(
                            'name' => _x( 'Tagline', 'slogan', 'describr' )
                        ),
                    ),
                ),
                'posts' => array(
                    'name' => _x( 'Posts', 'tab name', 'describr' ),
                    'icon' => 'dashicons dashicons-admin-post',
                ),
                'comments' => array(
                    'name' => __( 'Comments', 'describr' ),
                    'icon' => 'dashicons dashicons-admin-comments',
                ),
            )
        );

        foreach ( $tabs as $tab => $settings ) {
            if ( $tab !== sanitize_key( $tab ) ) {
                unset( $tabs[ $tab ] );
            }
        }
        
        //Tab privacy on the frontend
        if ( ! is_admin() || wp_doing_ajax() ) {
            foreach ( $tabs as $tab => $settings ) {
                if ( ! $this->is_tab_viewable( $tab, $settings ) ) {
                    unset( $tabs[ $tab ] );
                }
            }
        }

        return $tabs;
    }
        
    /**
     * Retrieves whether a tab is viewable
     * 
     * @since 3.0
     * 
     * @param string $tab  The tab whose privacy status should be checked
     * @param array  $ctrl Tab data
     * @return bool True if the tab is viewable, otherwise false
     */
    public function is_tab_viewable( $tab, $settings = array() ) {
        $is_viewable = false;
        
        $user_id = $this->ID;

        /**
         * Filters menu tab privacy setting
         * 
         * @since 3.0
         * 
         * @param string $setting  The tab privacy setting
         * @param string $tab      The tab
         * @param int    $user_id  User ID
         * @param array  $settings The tab settings
         */
        $privacy = (string) apply_filters( 
            'describr_profile_menu_tab_privacy',
            get_option( "describr_profile_menu_tab_{$tab}_privacy", '' ), 
            $tab, 
            $user_id, 
            $settings 
        );
        
        switch ( trim( $privacy ) ) {
            case 'logged-in_or_logged-out':
                $is_viewable = true;
                break;
            case 'logged-out':
                $is_viewable = ! is_user_logged_in();
                break;
            case 'logged-in':
                $is_viewable = is_user_logged_in();
                break;
            case 'profile-owner':
                $is_viewable = describr_is_self( $user_id );
                break;
            case 'with-roles':
                if ( is_user_logged_in() ) {
                    $menu_roles = get_option( "describr_profile_menu_tab_{$tab}_privacy_roles", array() );
                    $current_roles = array_keys( wp_roles()->role_names );
                    //Make sure the menu roles currently exist
                    $menu_roles = array_intersect( $current_roles, $menu_roles );
                        
                    $is_viewable = ! empty( array_intersect( $menu_roles, wp_get_current_user()->roles ) );
                }
                break;
            case 'profile-owner_or_with-roles':
                if ( is_user_logged_in() ) {
                    $is_viewable = describr_is_self( $user_id );

                    if ( ! $is_viewable ) {
                        $menu_roles = get_option( "describr_profile_menu_tab_{$tab}_privacy_roles", array() );
                        $current_roles = array_keys( wp_roles()->role_names );
                        //Make sure the menu roles currently exist
                        $menu_roles = array_intersect( $current_roles, $menu_roles );

                        $is_viewable = ! empty( array_intersect( $menu_roles, wp_get_current_user()->roles ) );
                    }
                }
                break;
            default:
                /**
                 * Filters whether the tab is viewable
                 * 
                 * @since 3.0
                 * 
                 * @param bool   $is_viewable Whether the tab is viewable
                 * @param string $tab         The tab
                 * @param int    $user_id     User ID
                 * @param array  $settings    The tab settings
                 */
                $is_viewable = apply_filters( 'describr_profile_menu_tab_is_viewable', true, $tab, $user_id, $settings );
                break;
        }
            
        if ( ! $is_viewable ) {
            $is_viewable = current_user_can( 'edit_users' );
        }

        return $is_viewable;
    }

    /**
     * Retrieves allowed tabs
     * 
     * @since 3.0
     * 
     * @return array Allowed tabs
     */
    public function get_allowed_tabs() {
        $tabs = $this->tabs();
        $original_tabs = $tabs;

        foreach ( $tabs as $tab => $settings ) {
            if ( ! get_option( "describr_profile_menu_tab_{$tab}" ) && empty( $settings['custom'] ) ) {
                unset( $tabs[ $tab ] );
            }
        }
            
        /**
         * Filters the allowed profile menu tabs
         * 
         * @since 3.0
         * 
         * @param array $tabs          Allowed tabs
         * @param array $original_tabs All tabs
         */
        return apply_filters( 'describr_profile_allowed_tabs', $tabs, $original_tabs );
    }

    /**
     * Retrieves the current tab
     * 
     * @since 3.0
     * 
     * @param bool $verify Wether to verify a tab
     * @return string|null The current tab
     */
    public function current_tab( $verify = false ) {
        $tabs = $this->get_allowed_tabs();
            
        $query_var = get_query_var( 'describr_tab' );

        if ( ! empty( $tabs[ $query_var ] ) ) {
            $this->current_tab = $query_var;
        } elseif ( ! $verify ) {
            $default_tab = get_option( 'describr_default_profile_menu_tab' );

            if ( ! empty( $tabs[ $default_tab ] ) ) {
                $this->current_tab = $default_tab;
            } else {
                $this->current_tab = array_key_first( $tabs );
            }
        }
            
        /**
         * Filters the current profile menu tab
         * 
         * @since 3.0
         * 
         * @param string $current_tab The current tab
         * @param array  $tabs        Allowed tabs
         */
        $this->current_tab = apply_filters( 'describr_profile_current_tab', $this->current_tab, $tabs );

        return $this->current_tab;
    }
        
    /**
     * Retrieves the profile current subtab
     * 
     * @since 3.0
     */
    public function sub_tab() {
        if ( get_query_var( 'describr_subtab' ) ) {
            $this->current_subtab = get_query_var( 'describr_subtab' );
        }

        return $this->current_subtab;
    }
    
    /**
     * Retrieves the shortcode content
     * 
     * @since 3.0
     * 
     * @param array $atts The shortcode attributes
     * @return string The shortcode content
     */   
    public function shortcode_content( $atts ) { 
        if ( 'publish' !== get_post_status( describr_get_page_id( 'profile' ) ) ) {
            return '';
        }

        $atts = shortcode_atts( 
            array(
                'template'            => 'profile',
                'mode'                => 'profile',
                'is_block'            => false,
                'photosize'           => 'original',
                'mobile_photosize'    => 'original',
                'main_area_max_width' => '',
            ),
            $atts,
            describr()->shortcodes['user']
        );
        
        //Sanitize shortcode arguments
        foreach ( $atts as $key => $value ) {
            if ( 'is_block' === $key ) {
                $atts[ $key ] = (bool) $value;
            } else {
                $atts[ $key ] = (string) $value;
            }
        }

        ob_start();

        if ( ! is_admin() && ! wp_doing_ajax() ) {
            describr()->dynamic_css( $atts );
        }

        echo '<div' . $this->get_class_attr() . '>';

        if ( $this->is_user() ) {
            if ( ! empty( $atts['tab'] ) ) {                
                $tabs = $this->get_allowed_tabs();

                if ( ! empty( $tabs[ $atts['tab'] ] ) ) {
                    $this->current_tab = $atts['tab'];
                }
            } 
            
            /*This action is documented in wp-content/plugins/describr/includes/class-account.php*/
            do_action( "describr_before_{$atts['mode']}_shortcode", $atts );           
            
            describr()->get_template( $atts['template'], $atts );
        } else {
            echo '<h1>' . esc_html__( 'No matching user found', 'describr' ) . '</h1>';
        }
            
        echo '</div>';

        /**
         * Filters shortcode content
         * 
         * @since 3.0
         * 
         * @param string $content Shortcode content
         */
        return apply_filters( 'describr_fetched_profile_shortcode_content', ob_get_clean() );
    }
    
    /**
     * Retrieves a tab URL
     * 
     * @since 3.0
     * 
     * @param string $tab           The tab
     * @param bool   $check_default Whether to check if the 
     *                              tab is the default tab  
     * @return string The tab URL
     */
    public function tab_url( $tab, $check_default = false ) {
        $url = describr_profile_url( get_query_var( 'describr_user' ) );
        
        //Return the profile without any tab identifier if argument is the default tab
        if ( $check_default && $this->default_tab === $tab ) {
            return $url;
        }

        if ( str_contains( $url, 'describr_user=' ) ) {
            $url = add_query_arg( array( 'describr_tab' => $tab ), $url );
        } elseif ( str_ends_with( $url, '/' ) ) {
            $url .= "$tab/";
        } else {
            $url .= "/$tab";
        }

        return $url;
    }
        
    /**
     * Retrieves a sub tab URL
     * 
     * @since 3.0
     * 
     * @param string The sub tab
     * @return string The sub tab URL
     */
    public function subtab_url( $subtab, $url ) {
        if ( str_contains( $url, 'describr_user=' ) ) {
            $url = add_query_arg( array( 'describr_subtab' => $subtab ), $url );
        } elseif ( str_ends_with( $url, '/' ) ) {
            $url .= "$subtab/";
        } else {
            $url .= "/$subtab";
        }

        return $url;
    }

    /**
     * Retrieves URL by field
     * 
     * @since 3.0
     * 
     * @param string $field Field
     * @return string Tab URL*/
    public function get_url_by_field( $field ) {
        $tabs = $this->get_allowed_tabs();

        if ( ! $tabs ) {
            return describr_profile_url( get_query_var( 'describr_user' ) );
        }
        
        if ( in_array( $field, array( 'login', 'nicename', 'email', 'url' ), true ) ) {
            $field = 'user_' . $field;
        }

        foreach ( $this->about_schema as $subtab => $fields ) {
            if ( ! is_array( $fields ) ) {
                $fields = explode( ',', $fields );
            }

            if ( in_array( $field, $fields, true ) ) {
                foreach ( $tabs as $tab => $settings ) {
                    if ( isset( $settings['subnav'][ $subtab ] ) ) {
                        $tab_ = $tab;
                        break 2;
                    }
                }
            }
        }

        if ( isset( $tab_ ) ) {
            return $this->subtab_url( $subtab, $this->tab_url( $tab_ ) );
        } else {
            return describr_profile_url( get_query_var( 'describr_user' ) );
        }
    }

    /**
     * Retrieves the class attribute for the profile's
     * main wrapper
     * 
     * @since 3.0
     * 
     * @return string The class attribute for the profile's
     *                main wrapper
     */
    public function get_class_attr() {
        /**
         * Filters the list of classes for the
         * profile's main wrapper
         * 
         * @since 3.0
         * 
         * @param array $classes A list of profile's main wrapper
         */
        $classes = apply_filters( 'describr_profile_class', array() );

        return ' class="' . esc_attr( implode( ' ', (array) $classes ) ) . '"';
    }

    /**
     * Sends message via ajax
     * 
     * @since 3.0
     */
    public function ajax_send_message() {
        if ( strlen( $_POST[ describr()->honeypot ] ) ) {
            wp_send_json_error( _x( 'It seems as if this message is not being sent by a human being.', 'email', 'describr' ) );
        }
        
        $user = get_userdata( (int) $_POST['user_id'] );

        if ( $user ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['message_nonce'] ) ), 'describr-profile-message_' . $user->ID ) ) {
                $user = false;
            } elseif ( ! describr_can_send_message( $user->ID ) ) {
                $user = false;
            } elseif ( ! describr_is_user_viewable( $user->ID ) ) {
                $user = false;
            }
        }

        if ( ! $user ) {
            wp_send_json_error( __( 'Could not send message. Please try again.', 'describr' ) );
        }
        
        $message = '';

        if ( is_string( $_POST['message'] ) ) {
            $message = trim( $_POST['message'] );
        }
                
        if ( ! strlen( $message ) ) {
            wp_send_json_error( _x( 'A message is required.', 'email', 'describr' ) );
        }
        
        $message = esc_html( describr_unwrap_p( $message ) );
        
        if ( isset( $_POST['subject'] ) && is_string( $_POST['subject'] ) ) {
            $subject = esc_html( trim( $_POST['subject'] ) );
        } else {
            $subject = '';
        }
        
        if ( describr()->user()->send_profile_email( $user->ID, $subject, $message ) ) {
            wp_send_json_success( _x( 'Message sent.', 'email', 'describr' ) );
        } else {
            wp_send_json_error( _x( 'Message not sent.', 'email', 'describr' ) );
        }
    }
        
    /**
     * Handles request to edit profile fields via ajax
     * 
     * @since 3.0
     */
    public function ajax_update_fields() {
        /**
         * Authenticates and authorizes ajax request
         * to make changes to profile fields
         * 
         * @since 3.0
         * 
         * @param Profile $profile \describr\Profile object
         */
        do_action( 'describr_profile_authenticate_field_changes', $this );
            
        /**
         * Retrieves profile's fields from $_POST
         * 
         * @since 3.0
         * 
         * @param Profile $profile \describr\Profile object
         */
        do_action( 'describr_profile_pluck_submitted_fields', $this );
            
        /**
         * Validates profile data
         * 
         * @since 3.0
         * 
         * @param Profile $profile \describr\Profile object
         */
        do_action( 'describr_profile_validate_edited_fields', $this );
            
        /**
         * Filters profile data after validation of the fields
         * 
         * @since 3.0
         * 
         * @param array $request {
         *     Profile data
         *     @type array $userdata User meta
         *     @type array $fields   User's meta keys
         * }
         */
        $this->request = apply_filters( 'describr_edit_profile_after_validated', $this->request );

        if ( ! empty( $this->request['userdata'] ) || ! empty( $this->request['asides'] ) ) {
            describr()->user()->update_profile( $this->ID, $this->request );
        }
            
        $this->mode = 'editing';
            
        $data = array(); 

        if ( ! empty( $this->request['fields'] ) ) {
            $fields = describr_get_viewable_fields( $this->ID, describr_get_fields_by_aside( $this->request['fields'] ) );

            if ( $fields ) {
                $data = $this->get_fields_data( $fields );
            }
        }

        wp_send_json_success( $data );
    }
        
    /**
     * Handles request to delete profile fields via ajax
     * 
     * @since 3.0
     */
    public function ajax_delete_fields() {
        /*This action is documented in wp-content/plugins/describr/includes/class-profile.php.*/
        do_action( 'describr_profile_authenticate_field_changes', $this );
            
        /*This action is documented in wp-content/plugins/describr/includes/class-profile.php.*/
        do_action( 'describr_profile_pluck_submitted_fields', $this );
            
        /**
         * Deletes profile data
         * 
         * @since 3.0
         * 
         * @param Profile $profile The \describr\Profile instance
         */
        do_action( 'describr_profile_delete_fields', $this );

        $this->mode = 'deleting';
        
        $data = $this->get_fields_data( array_keys( $this->request ) );

        foreach ( $this->request as $key => $val ) {
            $data['field'] = array( 
                'key_' => $key,
                'hash' => $val ? $val : false,
            );

            break;
        }

        wp_send_json_success( $data );
    }
        
    /**
     * Handles request to update profile fields asides via ajax
     * 
     * @since 3.0
     */
    public function ajax_update_asides() {
        /*This action is documented in wp-content/plugins/describr/includes/class-profile.php.*/
        do_action( 'describr_profile_authenticate_field_changes', $this );

        if ( ! empty( $_POST['field'] ) ) {
            $field = sanitize_text_field( wp_unslash( $_POST['field'] ) );
            
            $asides = array();
                
            $user_id = $this->ID;

            describr_pluck_field_status_and_priv( $field, $user_id, $asides );

            $this->mode = 'editing';

            if ( $asides ) {
                /**
                 * Filters the asides for a field before they are updated
                 * 
                 * @since 3.0
                 * 
                 * @param array $asides  Asides
                 * @param string $field  Field  
                 * @param int   $user_id ID of the user to whom the profile belongs
                 */
                $asides = apply_filters( 'describr_profile_asides_before_update', $asides, $field, $user_id );
        
                foreach ( $asides as $field => $meta ) {
                    describr_update_field_status_and_priv( $meta, $field, $user_id );
                }

                describr_audit_fields( $user_id );

                $fields = describr_get_viewable_fields( $user_id, describr_get_fields_by_aside( $field ) );
                
                $data = $this->get_fields_data( $fields );

                if ( get_query_var( 'describr_tab' ) ) {
                    $data['tab'] = get_query_var( 'describr_tab' );
                }
                    
                if ( get_query_var( 'describr_subtab' ) ) {
                    $data['subtab'] = get_query_var( 'describr_subtab' );
                }

                wp_send_json_success( $data );
            }

            unset($asides);
        }

        wp_send_json_error( __( 'The requested information was not found.', 'describr' ) );
    }

    /**
     * Initializes the schema of the About tab
     * 
     * @since 3.0
     */
    public function about_schema_init() {
        /**
         * Filters the schema of the About tab
         * 
         * @since 3.0
         * 
         * @param array $about_schema About tab schema
         */
        $this->about_schema = apply_filters( 
            'describr_profile_about_schema',
            array(
                'tagline'                    => 'tagline',
                'basic_info'                 => 'first_name,last_name,display_name,user_login,nickname,user_nicename,gender,birthdate,description',
                'contact_and_social'         => 'user_email,mobile_number,work_number,home_number,user_url',
                'addresses_and_timezone'     => 'current_city,hometown,lived_cities,timezone',
                'relationship_and_languages' => 'relationship,locale,locales',
                'work_and_education'         => 'work_history,college,high_school',
            )
        );
    }
        
    /**
     * Retrieves content for the About navs via ajax
     * 
     * @since 3.0
     */
    public function ajax_get_about() {
        /**
         * Authenticates ajax request for the profile About tab HTML
         * 
         * @since 3.0
         * 
         * @param Profile $profile The \describr\Profile instance
         */
        do_action( 'describr_authenticate_profile_schema_ajax_request', $this );
        
        $tab = 'about';

        if ( $tab !== $this->current_tab( true ) ) {
            wp_send_json_error( __( 'Sorry, this tab is not available.', 'describr' ) );
        }
                    
        $this->sub_tab();

        if ( ! $this->current_subtab ) {
            /*This filter is documented in wp-content/plugins/describr/includes/actions-filters/actions-profile.php*/
            $default_subtab = apply_filters( "describr_profile_tab_{$tab}_default_subtab", '' );
            
            if ( empty( $default_subtab ) ) {
                $tabs = $this->get_allowed_tabs();

                if ( isset( $tabs[ $tab ]['subnav'] ) ) {
                    $default_subtab = array_key_first( $tabs[ $tab ]['subnav'] );
                }
            }

            $this->current_subtab = $default_subtab;

            set_query_var( 'describr_subtab', $this->current_subtab );
        }
                  
        if ( ! empty( $this->about_schema[ $this->current_subtab ] ) ) {
            $this->mode = 'building';
            
            $fields = describr_get_viewable_fields( $this->ID, $this->about_schema[ $this->current_subtab ] );
            
            if ( $fields ) {
                $data = $this->get_fields_data( $fields );
                
                $data['tab']    = $this->current_tab;
                $data['subtab'] = $this->current_subtab;
                                        
                wp_send_json_success( $data );
            }
        }

        wp_send_json_error(array(
            'message' => __( 'The requested information was not found.', 'describr' ),
            'tab'     => $this->current_tab,
            'subtab'  => $this->current_subtab,
        ));
    }

    /**
     * Retrieves profile fields including markup.
     * 
     * @since 3.0
     * @since 3.1.2 The profile's cache is cleaned.
     * @since 3.1.3 Cleaned profile cache if not in building mode.
     * @since 3.1.3 Positioned full name in place of first name.
     * 
     * 
     * @param array  $fields Fields to retrieve .  
     * @return array The fields.
     */
    public function display_fields_markup( $fields ) {
        $output = array();

        $user_id = $this->ID;
        
        if ( 'building' !== $this->mode ) {
            clean_user_cache( $user_id );
        }
        
        describr_fetch_user( $user_id );

        $header_fields = describr_profile_header_fields();
        
        foreach ( describr()->get_fields( $fields ) as $field => $settings ) {
            if ( in_array( $field, $header_fields, true ) ) {
                continue;
            }

            $val = describr_display_value( $field, $settings );
            
            if ( describr_is_saved_data_empty( $val ) && ! in_array( $field, $this->no_saved_data, true ) ) {
                continue;
            }

            $output[ $field ] = $val;
        }
        
        /*Test if the current user can't edit the user, and change the first and last names fields to full name*/   
        if ( ! $this->can_edit() ) {
            $first_name = '';
            $last_name  = '';

            if ( isset( $output['first_name'] ) ) {
                $first_name = get_user_meta( $user_id, 'first_name', true );
                $first_name = trim( $first_name );
            }
                
            if ( strlen( $first_name ) ) {
                if ( isset( $output['last_name'] ) ) {
                    $last_name = get_user_meta( $user_id, 'last_name', true );
                    $last_name = trim( $last_name );
                }
            }
                
            if ( strlen( $first_name ) && strlen( $last_name ) ) {
                $name = sprintf( 
                    /*translators: 1: User's first name. 2: User's last name.*/
                    _x( '%1$s %2$s', 'Name based on first name and last name', 'describr' ),
                    $first_name,
                    $last_name
                );
                
                $label = _x( 'Name', 'user', 'describr' );
            } elseif ( strlen( $first_name ) ) {
                $label = __( 'First name', 'describr' );
                $name = $first_name;
            }

            if ( isset( $name ) ) {
                $_output = array();
                
                foreach ( $output as $key => $html ) {
                    //Positions full name in place of first name.
                    if ( 'first_name' === $key ) {
                        $_output['full name'] = '<div class="describr-content top has-header"><h2>' . esc_html( $label ) . '</h2></div><div class="describr-content">' . esc_html( $name ) .'</div>';
                    }

                    $_output[ $key ] = $html;
                }

                $output = $_output;
            }

            unset( $output['first_name'], $output['last_name'] );
        }
            
        /**
         * Filters the HTML for the fields
         * 
         * @since 3.0
         * 
         * @param array $output  HTML for the fields
         * @param array $fields  Fields
         * @param int   $user_id User ID of the user to whom the profile belongs
         */
        $output = apply_filters( 'describr_profile_fields_after_html', $output, $fields, $user_id );
         
        return $this->build( $output );
    }
        
    /**
     * Adds the marked up fields values under their respective HTML headings.
     * Adds fields icons
     * 
     * @since 3.0
     * 
     * @param array  $fields The fields   
     * @return string|array The marked up fields
     */
    public function build( $fields ) {
        $mode           = $this->mode;
        $user_id        = $this->ID;
        $current_tab    = $this->current_tab;
        $current_subtab = $this->current_subtab;

        $is_building = 'building' === $mode;

        if ( ! $fields ) {
            if ( $is_building ) {
                /**
                 * Filters the "No data available" message
                 * 
                 * @since 3.0
                 * 
                 * @param string $no_data_available_message "No data available" text
                 * @param string $current_subtab            Current subtab
                 * @param string $curent_tab                Current tab
                 * @param string $mode                      Current mode
                 * @param int    $user_id                   ID of the user to whom the profile belongs
                 */
                return apply_filters( 
                    'describr_profile_no_data_available_message', 
                    __( 'No data available. Please try again later.', 'describr' ),
                    $current_subtab,
                    $current_tab, 
                    $mode,
                    $user_id
                );
            }

            return $fields;
        }
            
        if ( $is_building ) {
            /**
             * Filters the tab schema
             * 
             * @since 3.0
             * 
             * @param array  $tab_schema     Tab schema
             * @param array  $fields         Fields
             * @param string $curent_tab     Current tab
             * @param string $current_subtab Current subtab
             * @param string $mode           Current mode
             * @param int    $user_id        ID of the user to whom the profile belongs
             */
            $tab_schema = apply_filters( 
                'describr_profile_tabs_schema',
                array(
                    'social_network' => array(
                        'fields' => array_merge( array( 'describr' ), array_keys( describr_get_socials() ) ),
                        'heading' => /*translators: Header.*/ __( 'Social Networks', 'describr' ),
                    ),
                    'contact' => array(
                        'fields' => array( 'user_email', 'mobile_number', 'work_number', 'home_number', ),
                        'heading' => /*translators: Header.*/ __( 'Contact Info', 'describr' ),
                    ),
                    'website' => array(
                        'fields'  => array( 'user_url' ),
                        'heading' => /*translators: Header.*/ __( 'Website', 'describr' ),
                    ),
                    'addresses' => array(
                        'fields' => array( 'current_city', 'hometown', 'lived_cities', ),
                        'heading' => /*translators: Header.*/ __( 'Addresses', 'describr' ),
                    ),
                    'work' => array(
                        'fields' => array( 'work_history' ),
                        'heading' => /*translators: Header.*/ __( 'Work History', 'describr' ),
                    ),
                    'college' => array(
                        'fields' => array( 'college' ),
                        'heading' => /*translators: Header.*/ __( 'College', 'describr' ),
                    ),
                    'high_school' => array(
                        'fields' => array( 'high_school' ),
                        'heading' => /*translators: Header.*/ __( 'High School', 'describr' ),
                    ),
                ),
                $fields,
                $current_tab, 
                $current_subtab, 
                $mode,
                $user_id 
            );
        }
        
        $can_edit = $this->can_edit();

        if ( $is_building && 'contact_and_social' === $current_subtab ) {
            $doing_socials = array_intersect_key( describr_get_socials(), $fields );
                
            //Test if no saved social media and add the Add Social Network button
            if ( ! $doing_socials && $can_edit && get_option( 'describr_enable_social_media' ) ) {
                //We use the plugin name to add the Add Social Network button
                $fields['describr'] = true;//Add to the foreach loop
                $this->no_saved_data[] = 'describr';//So that the button is added
            }
        }

        $build_schema  = array();
        $fields_schema = array();
            
        $field_wrap_open  = '<div class="describr-profile-menu-tab-field"';
        $field_wrap_close = '</div>';
        
        $main_div_open  = '<div class="describr-flex-item main">';
        $main_div_close = '</div>';
        
        foreach ( $fields as $field => $markup ) {
            $val = '';

            if ( in_array( $field, $this->no_saved_data, true ) ) {
                $val = describr_add_profile_data_trigger( $field );

                if ( ! $val ) {
                    continue;
                }
            } else {
                $settings = describr_get_field( $field );
                
                $icon = ! empty( $settings['icon'] ) ? $settings['icon']: '';

                if ( 'gender' === $field && preg_match( '/data\-gender="((fe)?male)"/', $markup, $match ) && ! empty( $settings['icons'][ $match[1] ] ) ) {
                    $icon = $settings['icons'][ $match[1] ];
                    $markup = preg_replace( '/\s*data\-gender="((fe)?male)"/', '', $markup );
                }
                
                if ( $icon ) {
                    $icon = '<div class="describr-flex-item icon" aria-hidden="true"><span class="' . esc_attr( $icon ) . '"></span></div> ';
                }

                if ( is_array( $markup ) ) {
                    $arr = array();

                    foreach( $markup as $k => $v ) {
                        $arr[ $k ] = $field_wrap_open;
                        
                        if ( $can_edit ) {
                            $arr[ $k ] .= ' data-field="' . esc_attr( $field ) . '" data-mainfield="' . esc_attr( $field ) . '" data-hash="'  . esc_attr( $k ) . '"';
                        }
                        
                        $arr[ $k ] .= '>'. $icon . $main_div_open . $v . $main_div_close . describr()->profile_field_controls()->add( $field, $k ) . $field_wrap_close;
                    }

                    $val = $arr;
                } else {
                    $val = $field_wrap_open;

                    if ( $can_edit ) {
                        $val .= ' data-field="' . esc_attr( $field ) . '" data-mainfield="' . esc_attr( $field ) . '"';
                    } 

                    $val .= '>'. $icon . $main_div_open . $markup . $main_div_close . describr()->profile_field_controls()->add( $field ) . $field_wrap_close;
                }
            }

            if ( $is_building ) {
                $found_schema = false;

                foreach ( $tab_schema as $section => $settings ) {
                    if ( isset( $settings['fields'] ) && is_array( $settings['fields'] ) && in_array( $field, $settings['fields'], true ) ) {
                        $found_schema = true;
                        break;
                    }
                }

                if ( $found_schema ) {
                    //Append the Add $field button to array-like fields
                    if ( $can_edit 
                        && ! in_array( $field, $this->no_saved_data, true ) 
                        && ( is_array( $val ) || 'social_network' === $section ) 
                    ) {
                        if ( 'social_network' === $section ) {
                            $add_social = true;
                        } else {
                            $add_ = describr_add_profile_data_trigger( $field );
                                
                            if ( $add_ ) {
                                if ( is_array( $val ) ) {
                                    $val['&'] = $add_;//& is likely to be a unique key
                                } else {
                                    $val .= $add_;
                                }
                            }
                        }
                    }

                    if ( isset( $build_schema[ $section ] ) ) {
                        $build_schema[ $section ]['fields'][] = array( $field => $val );
                    } else {
                        $build_schema[ $section ] = array( 
                            'fields'  => array( array( $field => $val ) ), 
                            'heading' => $settings['heading'], 
                        );
                    }
                } else {
                    $build_schema[ wp_unique_id( 'describr-section-' ) ] = array( 'fields' => $val );
                }
            } else {
                $fields_schema[ $field ] = is_array( $val ) ? array_values( $val ) : array( $val );
                
                if ( is_array( $val ) ) {
                    $addFieldButton = describr_add_profile_data_trigger( $field );

                    if ( $addFieldButton ) {
                        $fields_schema[ $field ][] = $addFieldButton;
                    }
                }
            }
        }
            
        $output = '';

        if ( $build_schema ) {
            foreach ( $build_schema as $section_ => $block ) {
                if ( isset( $block['heading'] ) ) {
                    $output .= '<div class="describr-profile-field-h1-wrap"><h1>' . esc_html( $block['heading'] ) . '</h1></div>';

                    foreach ( $block['fields'] as $arr ) {
                        foreach ( $arr as $k => $v ) {
                            if ( 'lived_cities' === $k ) {
                                $output .= '<div class="describr-profile-field-h2-wrap"><h2>' . esc_html__( 'Other Cities', 'describr' ) . '</h2></div>';
                            }

                            if ( is_array( $v ) ) {
                                $output .= implode( '', $v );
                            } else {
                                $output .= $v;
                            }
                        }
                    }
                } elseif ( is_array( $block['fields'] ) ) {
                    $output .= implode( '', $block['fields'] );
                } else {
                    $output .= $block['fields'];
                }

                if ( 'social_network' === $section_ && isset( $add_social ) ) {
                    $output .= describr_add_profile_data_trigger( 'describr' );
                }
            }
        } else {
            $output = $fields_schema;
        }
            
        /**
         * Filters the tab output
         * 
         * @since 3.0
         * 
         * @param string|array $output         Output for the subtab or fields
         * @param array        $tab_schema     Subtab schema (with fields and headings)    
         * @param array        $fields         Fields
         * @param string       $mode           Current mode
         * @param string       $current_tab    Current tab
         * @param string       $current_subtab Current subtab
         * @param int          $user_id        ID of the user to whom the profile belongs
         */
        return apply_filters( 'describr_profile_tab_output', $output, $tab_schema, $fields, $mode, $current_tab, $current_subtab, $user_id );
    }

    /**
     * Retrieves the raw profile fields values
     * 
     * @since 3.0
     * 
     * @param array  $fields Fields to retrieve   
     * @return array Raw values keyed by fields names
     */
    public function display_fields_raw( $fields ) {
        $output = array();
            
        $user = get_userdata( $this->ID );
        
        $photo_field = describr_photo_key();

        foreach ( describr()->get_fields( $fields ) as $field => $settings ) {
            //The markup for the updated photo is handled separately in wp-content/plugins/describr/includes/class-profile.php
            if ( $photo_field !== $field ) {
                $val = $user->$field;
                
                $val = map_deep( describr_falsey_to_empty_str( $val ), 'trim' );
                
                $output[ $field ] = '' === $val ? $val : $this->esc_raw( $val, $field, $settings );
            }
        }
            
        return $output;
    }

    /**
     * Escapes a field's value that potentially might be edited in the browser
     * 
     * @since 3.0
     * 
     * @param string|array $val      Field's value to escape
     * @param string       $field    Field
     * @param array        $settings Field settings 
     * @return string|array The escaped value
     */
    public function esc_raw( $val, $field, $settings ) {
        if ( is_array( $val ) ) {
            if ( ! empty( $settings['supports'] ) ) {
                $supports = describr_get_field_supports( $settings['supports'], $field );
            }

            foreach ( $val as $k => $v ) {
                if ( ! empty( $supports[ $k ] ) ) {
                    $val[ $k ] = $this->esc_raw( $v, $field, $supports[ $k ] );
                } else {
                    $val[ $k ] = $this->esc_raw( $v, $field, $settings );
                }
            }
        } elseif ( ! empty( $settings['type'] ) && in_array( $settings['type'], array( 'textarea' ) , true ) ) {
            if ( ! empty( $settings['html'] ) ) {
                $val = wp_kses_post( $val );

                if ( str_contains( $val, '</textarea' ) ) {
                    $val = str_replace( '</textarea', '&lt;/textarea', $val );
                }
            } else {
                $val = esc_textarea( $val );
            }
        } else {
            $val = esc_attr( $val );
        }

        return $val;
    }

    /**
     * Retrieves profile fields' HTML in edit mode
     * 
     * @since 3.0
     * 
     * @param array  $fields Fields to retrieve   
     * @return array The HTML fields
     */
    public function field_accessible( $fields ) {
        $output = array();
            
        describr_fetch_user( $this->ID );
        
        $markup_fields = describr_profile_field_edit_mode_html(); 
        
        $header_fields = describr_profile_header_fields();

        foreach ( describr()->get_fields( $fields ) as $field => $settings ) {
            if ( ! in_array( $field, $markup_fields, true ) 
                || in_array( $field, $header_fields, true ) 
                || in_array( $field, $this->no_saved_data, true ) 
            ) {
                continue;
            }

            $val = describr_field_accessible_value( $field, $settings );
             
            if ( describr_is_saved_data_empty( $val ) ) {
                $val = '';
            }

            $output[ $field ] = $val;
        }
         
        return $output;
    }

    /**
     * Retrieves raw and marked-up fields
     * 
     * @since 3.0
     * 
     * @param array $fields The fields
     * @return array An array of raw and marked up fields
     */
    public function get_fields_data( $fields ) {
        $data = array( 'mode' => $this->mode );
        
        if ( ! is_array( $fields ) ) {
            $fields = array( $fields );
        }

        $markup_fields = $this->display_fields_markup( $fields );

        if ( ! empty( $markup_fields ) ) {
            $data['markup'] = $markup_fields;
        }

        if ( $this->can_edit() ) {
            $raw_fields = $this->display_fields_raw( $fields );

            if ( $raw_fields ) {
                $data['raw'] = $raw_fields;
            }
            
            $field_accessible = $this->field_accessible( $fields );
            
            if ( $field_accessible ) {
                $data['accessibleFields'] = $field_accessible;
            }

            $saved_asides = describr_get_saved_asides( $fields );

            if ( $saved_asides ) {
                $data['savedAsides'] = $saved_asides;
            }
        }

        return $data;
    }
    
    /**
     * Queries for partner via ajax
     * 
     * @since 3.0
     */   
    public function ajax_partner_query() {
        /*This action is documented in wp-content/plugins/describr/includes/class-profile.php*/
        do_action( 'describr_profile_authenticate_field_changes', $this );

        $search_term = trim( wp_kses_post( $_POST['q'] ) );

        if ( ! strlen( $search_term ) ) {
            wp_send_json_error( __( 'Invalid search term.', 'describr' ) );
        }

        global $wpdb;
        
        $usermeta = "{$wpdb->usermeta}";

        $current_user_id = get_current_user_id();

        $args = array();

        $sql = "SELECT DISTINCT user_id FROM $usermeta INNER JOIN {$wpdb->users} ON user_id = ID WHERE";

        //Of course, we don't want the current user
        $sql .= " user_id != %s";
        $args[] = $current_user_id;//user_id

        //Users who have been approved
        $sql .= " AND user_id IN (SELECT user_id FROM $usermeta WHERE meta_key = 'account_status' AND meta_value = 'approved')";
        
        //Users who have not been deactivated by admin
        $sql .= " AND user_id NOT IN (SELECT user_id FROM $usermeta WHERE meta_key = 'active_by_admin' AND meta_value = 'false')";
        
        //Users who have not been confirmed
        $sql .= " AND user_id NOT IN (SELECT user_id FROM $usermeta WHERE meta_key = 'describr_confirmation_key')";

        //Users that have not blocked the current user
        $sql .= " AND user_id NOT IN (SELECT user_id FROM $usermeta WHERE meta_key = %s)";        
        $args[] = 'describr_blocked_user_' . $current_user_id;//meta_key

        //Users whose profiles are not private
        $sql .= " AND user_id NOT IN (SELECT user_id FROM $usermeta WHERE meta_key = 'show_profile' AND meta_value = 'self')";
        
        //Like...
        $sql .= " AND (user_login LIKE %s OR display_name LIKE %s OR user_nicename LIKE %s OR (meta_key = 'nickname' AND meta_value LIKE %s) OR ";
            
        $like = $wpdb->esc_like( $search_term ) . '%';

        $args[] = $like;//user_login LIKE %s
        $args[] = $like;//display_name LIKE %s
        $args[] = $like;//user_nicename LIKE %s
        $args[] = $like;//meta_key = 'nickname' AND meta_value LIKE %s
        
        //Test for whitespace. If true, assume the search term is a first and last names   
        if ( preg_match( '/\s+/u', $search_term ) ) {
            $search_term = preg_replace( '/\s+/u', ' ', $search_term );//Consolidate white spaces
            
            $names = explode( ' ', $search_term, 2 );//Split search term into first and last names
            
            list( $f_name, $l_name ) = $names;
            
            $search_term = implode( ' ', $names );//Reconvene search term into first and last names, for later use

            $sql .= "((meta_key = 'first_name' AND meta_value LIKE %s) AND (meta_key = 'last_name' AND meta_value LIKE %s))";

            $args[] = $wpdb->esc_like( $f_name ) . '%';//meta_key = 'first_name' AND meta_value LIKE %s
            $args[] = $wpdb->esc_like( $l_name ) . '%';//meta_key = 'last_name' AND meta_value LIKE %s
        } else {
            $sql .= "(meta_key = 'first_name' AND meta_value LIKE %s)";
            $args[] = $like;//meta_key = 'first_name' AND meta_value LIKE %s
        }

        $sql .= ')';

        $order = 'ASC';//a-z
           
        $sql .= " ORDER BY display_name $order, user_nicename $order, user_login $order, meta_value $order LIMIT 10";

        $results = $wpdb->get_col( $wpdb->prepare( $sql, $args ) );
            
        $response = array();

        if ( $results ) {
            $users = array_map( 'get_userdata', $results );

            foreach ( array_filter( $users ) as $user ) {
                $name = '';                              
                
                if ( 0 === mb_stripos( $user->display_name, $search_term ) ) {
                    $name = $user->display_name;
                }
                  
                if ( 0 === strlen( $name ) ) {
                    $fullname = trim( $user->first_name . ' ' . $user->last_name );
                    
                    if ( 0 === mb_stripos( $fullname, $search_term ) && describr_is_field_viewable( 'first_name' ) ) {
                        $name = $fullname;
                    }
                }
                    
                if ( 0 === strlen( $name ) && 0 === mb_stripos( $user->nickname, $search_term ) && describr_is_field_viewable( 'nickname' )  ) {
                    $name = $user->nickname;
                }
                    
                if ( 0 === strlen( $name ) && 0 === mb_stripos( $user->user_login, $search_term ) && describr_is_field_viewable( 'user_login' )  ) {
                    $name = $user->user_login;
                };
                    
                if ( 0 === strlen( $name ) && 0 === mb_stripos( $user->user_nicename, $search_term ) ) {
                    $name = $user->user_nicename;
                };
                
                if ( strlen( $name ) ) {
                    $response[] = array( 
                        'userId' => $user->ID,
                        'value'  => $name, 
                        'label'  => $name, 
                    );
                }
            }

            $response = array_unique( $response ); 
        }
        
        wp_send_json_success( $response );
    }
    
    /**
     * Retrieves an array of posts authored by the profile user
     * 
     * @since 3.0
     * 
     * @return array
     */
    public function get_posts() {
        $per_page = describr_per_page();
        
        $this->paged = max( 1, $this->paged );

        $paged = $this->paged;

        $query = new \WP_Query();
            
        $posts = $query->query(
            array( 
                'author'         => $this->ID, 
                'order'          => 'DESC', 
                'orderby'        => 'date', 
                'no_found_rows'  => false, 
                'posts_per_page' => $per_page, 
                'offset'         => ( $paged-1 ) * $per_page,
            )
        );

        if ( $posts ) {
            global $post;

            $_post = $post;

            foreach( $posts as $post ) {
                if ( ! describr_is_post_viewable() ) {
                    continue;
                }

                $this->posts[] = $post;
            }

            $post = $_post;
        }
            
        /**
         * Filters the array of viewable posts authored by the profile user
         * 
         * @since 3.0
         * 
         * @param WP_Post[] $posts   An array of WP_Post objects
         * @param int       $user_id ID of the user to whom the profile belongs
         * @param int       $paged   The current page
         */
        $this->posts = (array) apply_filters( 'describr_profile_posts_query_results', $this->posts, $this->ID, $this->paged );

        $this->found_results = $query->found_posts;
        $this->max_num_pages = $query->max_num_pages;

        return $this->posts;
    }

    /**
     * Displays profile user posts
     * 
     * @since 3.0
     * 
     * @param bool $echo Whether to output or return the posts
     * @return array Marked-up posts
     */
    public function display_posts( $echo = false ) {
        add_filter( 'excerpt_more', 'describr_excerpt_more', 10, 1 );
        
        /**
         * Retrieves whether to use the current user's 
         * time zone to display posts' dates
         * 
         * @since 3.0
         * 
         * @param true $use_user_timezone
         */
        $user_timezone_for_post_time = apply_filters( 'describr_user_timezone_for_post_time', true );

        if ( $user_timezone_for_post_time ) {
            add_filter( 'get_post_time', 'describr_post_time', 10, 2 );
        }

        $results = array();
        
        global $post;

        $_post = $post;

        foreach ( $this->posts as $post ) {
            if ( ! $echo ) {
                ob_start();
            }   
                
            describr()->get_template( 'post' ); 
                
            if ( ! $echo ) {
                $results[] = ob_get_clean();
            }
        }
        
        $post = $_post;

        if ( $user_timezone_for_post_time ) {
            remove_filter( 'get_post_time', 'describr_post_time', 10 );
        }

        remove_filter( 'excerpt_more', 'describr_excerpt_more', 10 );

        $results[] = $this->link_pages( $echo );

        return $results;
    }
        
    /**
     * Retrieves profile user marked-up posts via ajax
     * 
     * @since 3.0
     */
    public function ajax_get_posts() {
        /*This action is documented in wp-content/plugins/describr/includes/class-profile.php*/
        do_action( 'describr_authenticate_profile_schema_ajax_request', $this );
            
        if ( 'posts' !== $this->current_tab( true ) ) {
            wp_send_json_error( __( 'Sorry, this tab is not available.', 'describr' ) );
        }
            
        $found_viewable_posts = count( $this->get_posts() );

        header( 'X-DESCRIBR-Total: ' . $this->found_results );
        header( 'X-DESCRIBR-Result: ' . $found_viewable_posts );
        
        $data = array( 'tab' => $this->current_tab );
            
        if ( $found_viewable_posts ) {  
            $data['results'] = array_filter( $this->display_posts() );
        } else {
            $data['results'] = __( 'No posts found.', 'describr' );
        }
            
        wp_send_json_success( $data );
    }

    /**
     * Retrieves comments authored by the profile user
     * 
     * @since 3.0
     * 
     * @return WP_Comment_Query instance
     */
    public function get_comments() {
        $per_page = describr_per_page();
        
        $this->paged = max( 1, $this->paged );

        $paged = $this->paged;

        $comment_args = array(
            'user_id'       => $this->ID,
            'orderby'       => 'comment_date_gmt',
            'order'         => 'DESC',
            'status'        => 'approve',
            'type'          => 'comment',
            'no_found_rows' => false,
            'number'        => $per_page,
            'offset'        =>  ( $paged-1 ) * $per_page,
        );
            
        if ( $this->ID === get_current_user_id() || current_user_can( 'moderate_comments' ) ) {
            $comment_args['include_unapproved'] = array( $this->ID );
        }

        $_comments = new \WP_Comment_Query();

        $comments = $_comments->query( $comment_args );

        $comments_ = array();
        
        global $post;

        $_post = $post;

        foreach ( $comments as $comment ) {
            if ( ! ( $comment instanceof \WP_Comment ) ) {
                continue;
            }

            $post = get_post( $comment->comment_post_ID );

            if ( ! $post || post_password_required() || ! describr_is_post_viewable( true ) ) {
                continue;
            }

            $comments_[] = $comment;
        }
            
        $post = $_post;

        /**
         * Filters the array of viewable comments authored by the profile user
         * 
         * @since 3.0
         * 
         * @param WP_Comment[] $comments_ An array of WP_Comment objects
         * @param int          $user_id   ID of the user to whom the profile belongs
         * @param int          $paged     The current page
         */
        $comments_ = (array) apply_filters( 'describr_profile_comments_query_results', $comments_, $this->ID, $this->paged );

        $_comments->comments = $comments_;

        $this->comments = $_comments;

        return $this->comments;
    }

    /**
     * Displays profile user comments
     * 
     * @since 3.0
     * 
     * @param bool $echo Whether to output or return the comments
     * @return array Marked-up comments
     */
    public function display_comments( $echo = false ) {
        global $post, $comment;

        $_post = $post;

        $_comment = $comment;
                
        $user = get_userdata( $this->ID );
        
        $results = array();
        
        /*This filter is documented in wp-content/plugins/describr/includes/class-profile.php*/
        $user_timezone_for_comment_time = apply_filters( 'describr_user_timezone_for_post_time', true );

        if ( $user_timezone_for_comment_time ) {
            add_filter( 'get_comment_date', 'describr_comment_date', 10, 2 );
        }

        foreach ( $this->comments->comments as $comment ) {
            if ( ! $echo ) {
                ob_start();
            }   
                
            describr()->get_template( 'comment' );
                
            if ( ! $echo ) {
                $results[] = ob_get_clean();
            }
        }
        
        if ( $user_timezone_for_comment_time ) {
            remove_filter( 'get_comment_date', 'describr_comment_date', 10 );
        }

        $post = $_post;
            
        $comment = $_comment;            
            
        $this->max_num_pages = $this->comments->max_num_pages;

        $results[] = $this->link_pages( $echo );

        return $results;
    }
        
    /**
     * Retrieves profile user marked-up comments via ajax
     * 
     * @since 3.0
     */        
    public function ajax_get_comments() {
        /*This action is documented in wp-content/plugins/describr/includes/class-profile.php*/
        do_action( 'describr_authenticate_profile_schema_ajax_request', $this );

        if ( 'comments' !== $this->current_tab( true ) ) {
            wp_send_json_error( __( 'Sorry, this tab is not available.', 'describr' ) );
        }
            
        $this->get_comments();
            
        $found_viewable_comments = count( $this->comments->comments );

        header( 'X-DESCRIBR-Total: ' . $this->comments->found_comments );
        header( 'X-DESCRIBR-Result: ' . $found_viewable_comments );

        $data = array( 'tab' => $this->current_tab );
            
        if ( $found_viewable_comments ) {  
            $data['results'] = array_filter( $this->display_comments() );
        } else {
            $data['results'] = __( 'No comments found.', 'describr' );
        }
            
        wp_send_json_success( $data );
    }
    
    /**
     * Blocks user via ajax
     * 
     * @since 3.0
     */
    public function ajax_block_user() {
        $user = get_userdata( (int) $_POST['user_id'] );
        
        if ( $user ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['block_nonce'] ) ), 'describr-block-profile_' . $user->ID ) ) {
                $user = false;
            } elseif ( ! describr_can_block_user( $user->ID ) ) {
                $user = false;
            }
        }

        if ( ! $user ) {
            wp_send_json_error(
                array(
                    'message' => _x( 'Could not block user. Please try again.', 'restrict user access', 'describr' ),
                )
            );
        }
        
        add_user_meta( get_current_user_id(), "describr_blocked_user_{$user->ID}", gmdate( 'Y-m-d H:i:s' ), true );

        wp_send_json_success( 
            array( 
                'message' => sprintf(
                    /*translators: User's display name.*/
                   _x( '%s is blocked.', 'restrict user access', 'describr' ), 
                   $user->display_name
                ) 
            ) 
        );
    }
    
    /**
     * Unblocks user via ajax
     * 
     * @since 3.0
     */
    public function ajax_unblock_user() {
        $user = get_userdata( (int) $_POST['user_id'] );
        
        if ( $user && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['unblock_nonce'] ) ), 'describr-block-profile_' . $user->ID ) ) {
            $user = false;
        }

        if ( ! $user ) {
            wp_send_json_error(
                array(
                    'message' => _x( 'Could not unblock user. Please try again.', 'allow user access', 'describr' ),
                )
            );
        }

        delete_user_meta( get_current_user_id(), "describr_blocked_user_{$user->ID}" );

        wp_send_json_success( 
            array( 
                'message' => sprintf(
                    /*translators: User's display name.*/
                   _x( '%s is unblocked.', 'allow user access', 'describr' ), 
                   $user->display_name
                ) 
            ) 
        );
    }

    /**
     * Builds paging links
     * 
     * @since 3.0
     * 
     * @param bool $echo Whether to output the links
     * @return string Links
     */
    public function link_pages( $echo = false ) {
        $numpages = (int) $this->max_num_pages;

        if ( 1 >= $numpages ) {
            if ( $echo ) {
                echo '';
            }
                
            return '';
        }
            
        $page = $this->paged;

        if ( $page > $numpages ) {
            $page = $numpages;
        }
            
        $output = '<p class="post-nav-links">';
            
        if ( 1 < $page ) {
            $prev = $page - 1;

            $link = '<a aria-label="';
            $link .= esc_attr(sprintf(
                            /*translators: Pagination. %d: Page number.*/
                            __( 'Previous page: %d.', 'describr' ),
                            $prev
                        ));

            $link .= '" href="' . $this->link_page( $prev, false ) . '" class="post-page-numbers prev" rel="prev">&laquo;</a>';
                
            /*This filter is documented in wp-content/plugins/describr/includes/class-profile.php*/
            $output .= apply_filters( 'describr_link_pages_link', $link, $prev );
        }
        
        $min = $page - 4;
        $max = $page + 4;
        
        if ( 10 >= $numpages ) {
            $max = $numpages;
            $min = 1;
        }
        
        $min = max( 1, $min );
        $max = min( $max, $numpages );

        if ( 0 <= ($min-4) ) {
            $i = 1;

            $link = $this->link_page( $i ) . $i . '</a>';
            
            /*This filter is documented in wp-content/plugins/describr/includes/class-profile.php*/
            $link = apply_filters( 'describr_link_pages_link', $link, $i );

            $output .= " $link";
        }

        for ( $i = $min; $i <= $max; $i++ ) {
            if ( $i !== $page ) {
                $link = $this->link_page( $i ) . $i . '</a>';
            } else {
                $link = '<span class="post-page-numbers current" tabindex="0" aria-current="page">' . $i . '</span>';
            }

            /**
             * Filters the HTML output of individual page number links
             *
             * @since 3.0
             *
             * @param string $link The page number HTML output
             * @param int    $i    Page number for paginated users' page links
             */
            $link = apply_filters( 'describr_link_pages_link', $link, $i );

            $output .= " $link";
        }

        if ( $page < $max ) {
            $next = $page + 1;

            $link = '<a aria-label="';
            $link .= esc_attr(sprintf(
                            /*translators: Pagination. %d: Page number*/
                            __( 'Next page: %d.', 'describr' ),
                            $next 
                        ));
            $link .= '" href="' . $this->link_page( $next, false ) . '" class="post-page-numbers next" rel="next">&raquo;</a>';

            /*This filter is documented in wp-content/plugins/describr/includes/class-profile.php*/
            $link = apply_filters( 'describr_link_pages_link', $link, $next );
            
            $output .= " $link";
        }

        $output .= '</p>';
            
        if ( $echo ) {
            echo wp_kses_post( $output );

            return '';
        } else {
            return $output;
        }   
    }

    /**
     * Builds paged URL
     * 
     * @since 3.0
     * 
     * @param int $i Page number
     * @param bool $add_url_a_tag Whether to add the URL to the opening <a> tag
     * @return string Link
     */
    public function link_page( $i, $add_url_a_tag = true ) {
        $link = describr_profile_url( get_query_var( 'describr_user' ) );
        
        if ( describr_using_pretty_url( $link ) ) {
            if ( 'comments' === $this->current_tab ) {
                $uri = describr()->rewrite()->author_comments_pagination_base . "-$i/";
            } elseif ( 'posts' === $this->current_tab ) {
                $uri = trim( $GLOBALS['wp_rewrite']->pagination_base, '/' ) . "/$i/";
            } else {
                $uri = '';
            }
            
            if ( str_contains( $link, '/?' ) ) {
                $link = str_replace( '?', "$uri?", $link );
            } elseif ( str_contains( $link, '?' ) ) {
                $link = str_replace( '?', "/$uri?", $link );
            } else {
                $link = trailingslashit( $link ) . $uri;
            }

            $link = user_trailingslashit( $link, 'page' );
        } else {
            $args = array( 'paged' => $i );
                    
            if ( $this->current_tab ) {
                $args['describr_tab'] = $this->current_tab;
            }

            $link = add_query_arg( $args, $link );
        }
                      
        $link = esc_url( $link );

        if ( ! $add_url_a_tag ) {
            return $link;
        }

        $label = sprintf(
            /*translators: Pagination. %d: Page number.*/
            __( 'Page %d', 'describr' ),
            number_format_i18n( $i )
        );

        return '<a aria-label="' . esc_attr( $label ) . '" href="' . $link . '" class="post-page-numbers">';
    }
}
