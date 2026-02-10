<?php
namespace describr;

/**
 * Account class
 *
 * @package Describr
 * @since 3.0
 */
class Account {    
    /**
     * Stores account hashes
     * 
     * @since 3.0
     * @var array
     */
    private $accounts = array();

    /**
     * Stores the allowed tabs
     * 
     * @since 3.0
     * @var array
     */
    private $tabs = array();

    /**
     * Stores whether the user is on the account page
     * 
     * @since 3.0
     * @var bool
     */   
    private $is_account = false;
    
    /**
     * Stores the current action
     * 
     * @since 3.0
     * @var bool
     */ 
    private $action = false;
    
    /**
     * The current tab
     * 
     * @since 3.0
     * @var string
     */
    public $current_tab = 'general';
    
    /**
     * ID to whom the account belongs
     * 
     * @since 3.0
     * @var string
     */
    private $user_id = 0;
     
    /**
     * Account constructor
     * 
     * @since 3.0
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ), 10 );
    }
    
    /**
     * Account constructor
     * 
     * @since 3.0
     */
    public function init() {
        if ( ! is_admin() ) {  
            add_shortcode( describr()->shortcodes['account'] , array( $this, 'describr_account' ) );          
            add_action( 'template_redirect', array( $this, 'verify_account' ), 10 );
            add_action( 'template_redirect', array( $this, 'set_current_action' ), 11 );
            add_action( 'template_redirect', array( $this, 'submit' ), 12 );
            add_action( 'template_redirect', array( $this, 'enqueue_scripts' ), 13 );
            add_filter( 'document_title_parts', array( $this, 'add_tab_title_to_page_title' ), 10, 1 );
            add_filter( 'wp_robots', array( $this, 'make_page_private' ), 10, 1 );            
            add_filter( 'body_class', array( $this, 'add_body_class' ), 10, 1 );
        } elseif ( wp_doing_ajax() ) {
            add_action( 'wp_ajax_describr-destroy-sessions', array( $this, 'ajax_destroy_sessions' ), 10 );
        }
    }

    /**
     * Verifies that the user is logged in before the account page is displayed
     * 
     * @since 3.0
     */
    public function verify_account() {
        if ( describr_is_page( 'account' ) ) {
            nocache_headers();

            if ( ! is_user_logged_in() ) {
                $url = describr_login_url( describr_current_url() );
                wp_safe_redirect( $url );
                exit;
            }

            if ( isset( $_POST['describr_user'] ) ) {
                $user_id = (int) $_POST['describr_user'];
            } elseif ( isset( $_GET['describr_user'] ) ) {
                $user_id = (int) $_GET['describr_user'];
            } else {
                $user_id = get_current_user_id();
            }
                
            if ( 0 < $user_id ) {
                if ( ! describr_is_self( $user_id ) ) {
                    if ( ! get_userdata( $user_id ) ) {
                        wp_die( __( 'Invalid user ID.', 'describr' ) );
                    }
                }

                $this->user_id = $user_id;
            } else {
                wp_die( __( 'Invalid user ID.', 'describr' ) );
            }
            
            if ( get_query_var( 'describr_tab' ) ) {
                $this->current_tab = get_query_var( 'describr_tab' );
            }

            if ( $this->get_tabs() ) {
                $this->is_account = true;
            }
            
            describr()->fields()->mode       = 'account';
            describr()->fields()->is_editing = true;
        }
    }

    /**
     * Gets the current action from the submitted form
     *
     * @since 3.0
     *
     * @return string|false The action name. False if no action was submitted
     */ 
    public function current_action() {
        if ( ! $this->is_account ) {
            return false;
        }
        
        if ( ! isset( $_POST[ describr()->honeypot ] ) ) {
            return false;
        }

        $bot_check = sanitize_text_field( wp_unslash( $_POST[ describr()->honeypot ] ) );

        if ( strlen( $bot_check ) ) {
            return false;
        }

        if ( isset( $_POST['describr_action'] ) ) {
            $action = sanitize_key( wp_unslash( $_POST['describr_action'] ) );

            if ( empty( $action ) ) {
                return false;
            }

            return $action;
        }

        return false;
    }
    
    /**
     * Sets the current action from the submitted form
     *
     * @since 3.0
     */
    public function set_current_action() {
        $this->action = $this->current_action();
    }
    
    /**
     * Processes form submission
     *
     * @since 3.0
     */
    public function submit() {
        if ( $this->action ) {
            /**
             * Retrieves account fields from $_POST
             * 
             * @since 3.0
             * 
             * @param false|string $action The current action
             */
            do_action( 'describr_submit_account_form', $this->action );
            
            /**
             * Validates account fields
             * 
             * @since 3.0
             * 
             * @param array $form $_POST
             */
            do_action( 'describr_submit_account_validate', describr()->form()->post_form );

            if ( ! describr()->error()->has_errors() ) {
                /**
                 * Updates account
                 * 
                 * @since 3.0
                 * 
                 * @param array $form $_POST
                 */
                do_action( 'describr_submit_account_update', describr()->form()->post_form );
            }
        }
    }
        
    /**
     * Enqueues profile scripts and styles
     * 
     * @since 3.0
     */
    public function enqueue_scripts() {
        if ( $this->is_account ) {  
            wp_enqueue_style( 'dashicons' );
            
            wp_enqueue_script( 'describr-main' );
            wp_enqueue_script( 'describr-account' );

            describr_enqueue_pwd_script();
                        
            wp_enqueue_style( 'describr-fontawesome' );

            wp_enqueue_style( 'describr-style' );
        }
    }
        
    /**
     * Adds current tab title to the document
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
    public function add_tab_title_to_page_title( $title ) {
        if ( $this->is_account ) {
            $current_tab = $this->current_tab;

            $tabs = $this->get_allowed_tabs( false );
            
            if ( ! empty( $tabs[ $current_tab ]['title'] ) ) {
                //The current page title suffices for these tabs
                if ( in_array( $current_tab, array( 'account', 'general' ), true ) ) {
                    return $title;
                }

                $tab_title = $tabs[ $current_tab ]['title'];
                $title_ = array();

                foreach ( $title as $key => $val ) {
                    $title_[ $key ] = $val;

                    if ( 'title' === $key ) {
                        $title_['tab_title'] = $tab_title;
                    }
                }

                $title = $title_;
            }
        }

        return $title;
    }

    /**
     * Adds noindex directive to 'robots' meta tag on account page
     * 
     * @since 3.0
     * 
     * @param array $robots Associative array of directives
     * @return array Directives to 'robots' meta tag, including 
     *               noindex directive if account page is loaded
     */
    public function make_page_private( $robots ) {
        if ( $this->is_account ) {
            return describr_robots_noindex( $robots );
        }

        return $robots;
    }
    
    /**
     * Displays nonce hidden field for forms
     *
     * @since 3.0
     * 
     * @param string $tab The tab
     */
    public function nonce_field( $tab ) {
        if ( ! $this->user_id ) {
            return;
        }
        
        $fields = describr_get_form_fields();

        $fields[] = 'describr_user';

        $fields = array_unique( $fields );
        
        echo '<input type="hidden" name="describr_fields" value="' . esc_attr( implode( ',', $fields ) ) . '" />';
        
        $action = md5( wp_json_encode( $fields ) );

        echo '<input type="hidden" name="describr_nonce" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';
        
        $action_ = $tab;

        if ( 'delete' === $this->action ) {
            $action_ = 'dodelete';
        }
        
        echo '<input type="hidden" name="describr_action_nonce" value="' . esc_attr( wp_create_nonce( "{$action_}_{$this->user_id}" ) ) . '" />';
    }
    
    /**
     * Magic method for accessing private properties
     *
     * @since 3.0
     *
     * @param string $key Property key
     * @return mixed Value of the property
     */
    public function __get( $key ) {
        if ( in_array( $key, array( 'ID', 'id', 'user_id' ), true ) ) {
            return $this->user_id;
        } elseif ( in_array( $key, array( 'is_account', 'action', 'fields', 'tabs' ), true ) ) {
            return $this->$key;
        }
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
        if ( $this->is_account ) {
            $classes[] = 'account';

            $user = get_userdata( $this->user_id );
            $classes[] = 'account-' . sanitize_html_class( $user->user_nicename );
            $classes[] = 'account-' . $user->ID;
        }            
             
        return $classes;
    }
    
    /**
     * Retrieves whether an instance of the Account page already exists
     * 
     * @since 3.0
     * 
     * @param string $account Hash of account shortcode attributes
     * @return bool
     */
    public function exists( $account ) {
        return in_array( $account, $this->accounts, true );
    }    
     
    /**
     * Retrieves the tabs
     * 
     * @since 3.0
     * 
     * @return array The tabs
     */   
    public function get_tabs() {
        static $tabs = array();

        if ( $tabs ) {
            return $tabs;
        }
        
        if ( describr_can_edit_user( $this->user_id, true ) ) {
            $tabs[5]['general'] = array(
                'title'        => __( 'Account', 'describr' ),
                'legend'       => /*translators: Hidden accessibility text.*/ __( 'Personal Information', 'describr' ),
                'button_label' => __( 'Save Changes', 'describr' ),
                'icon'         => 'dashicons dashicons-id',
            );

            $tabs[10]['privacy'] = array(
                'title'        => _x( 'Privacy', 'user access', 'describr' ),
                'button_label' => __( 'Save Changes', 'describr' ),
                'icon'         => 'dashicons dashicons-admin-site',
            );

            $tabs[20]['password'] = array(
                'title'        => __( 'Password', 'describr' ),
                'legend'       => /*translators: Hidden accessibility text.*/ __( 'Update Password', 'describr' ),
                'button_label' => __( 'Update Password', 'describr' ),
                'icon'         => 'dashicons dashicons-lock',
            );
            
            $tabs[40]['sessions'] = array(
                'title'        => _x( 'Sessions', 'user', 'describr' ),
                'legend'       => /*translators: Hidden accessibility text.*/ _x( 'Sessions', 'user', 'describr' ),
                'button_label' => __( 'Save Changes', 'describr' ),
                'icon'         => 'dashicons dashicons-location',
            );
            
            if ( describr_get_network_option( 'describr_users_can_unsubscribe_from_email_notif' ) ) {
                $tabs[80]['notifications'] = array(
                    'title'        => _x( 'Notifications', 'user', 'describr' ),
                    'legend'       => /*translators: Hidden accessibility text.*/ _x( 'Notifications', 'user', 'describr' ),
                    'button_label' => __( 'Save Changes', 'describr' ),
                    'icon'         => 'dashicons dashicons-bell',
                );
            }
            
            global $wpdb;

            //Only show the Blocked Users tab if the Account user has blocked at least one user
            if ( $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta INNER JOIN $wpdb->users ON user_id = ID WHERE user_id = %d AND meta_key LIKE %s LIMIT 1", array( $this->user_id, $wpdb->esc_like( 'describr_blocked_user_' ) . '%' ) ) ) ) {
                $tabs[160]['blocked_users'] = array(
                    'title'        => _x( 'Blocked Users', 'restricted users access', 'describr' ),
                    'legend'       => /*translators: Hidden accessibility text.*/ _x( 'Blocked Users', 'restricted users access', 'describr' ),
                    'button_label' => /*translators: Unblock user.*/ _x( 'Unblock', 'allow user access', 'describr' ),
                    'icon'         => 'dashicons dashicons-warning',
                );
            } elseif ( 'blocked_users' === $this->current_tab ) {
                $this->current_tab = 'general';
            }
        }

        if ( describr_can_delete_user( $this->user_id, true ) ) {
            $tabs[320]['delete'] = array(
                'title'        => __( 'Delete User', 'describr' ),
                'legend'       => /*translators: Hidden accessibility text.*/ __( 'Delete User', 'describr' ),
                'button_label' => __( 'Delete User', 'describr' ),
                'icon'         => 'dashicons dashicons-admin-users',
            );
        }
                
        /**
         * Filters the tabs for the account
         * 
         * @since 3.0
         * 
         * @param array Tabs for the account
         */
        $tabs = (array) apply_filters( 'describr_account_tabs', $tabs );

        return $tabs;
    }
    
    /**
     * Retrieves the allowed tabs
     * 
     * @since 3.0
     * 
     * @param array $atts describr_account shortcodes attributes
     * @return array The allowed tabs
     */
    public function get_allowed_tabs( $atts ) {
        $tabs = $this->get_tabs();

        ksort($tabs);

        $tabs_ = array();

        foreach ( $tabs as $pos => $arr ) {
            foreach ( $arr as $tab => $settings ) {
                if ( ! empty( $atts['tab'] ) && $tab !== $atts['tab'] ) {
                    continue;
                }
                
                $tabs_[ $tab ] = $settings;
            }
        }

        $this->tabs = $tabs_;

        return $this->tabs;
    }

    /**
     * Retrieves the tab URL
     * 
     * @since 3.0
     * 
     * @param string $tab  The tab
     * @param mixed  $args Query args
     * @return string The tab URL
     */
    public function tab_url( $tab, $args = false ) {
        $url = describr()->permalinks()->account_url( $tab );
        
        if ( $args ) {
            if ( ! is_array( $args ) ) {
                $args = array( 'describr_user' => $args );
            }
        }
        
        if ( ! isset( $args['describr_user'] ) && isset( $_GET['describr_user'] ) ) {
            $user_id = (int) $_GET['describr_user'];

            if ( $user_id ) {
                if ( ! is_array( $args ) ) {
                    $args = array();
                }

                $args['describr_user'] = $user_id;
            }
        }

        if ( $args ) {
            $url = add_query_arg( $args, $url );
        }
        
        return $url;
    }
    
    /**
     * Retrieves shortcode content for the account page
     * 
     * @since 3.0
     * 
     * @param array $atts describr_account shortcodes attributes
     * @return string The shortcode content
     */
    public function describr_account( $atts = array() ) {
        if ( ! is_user_logged_in() ) {
            return '';
        }
        
        $atts = shortcode_atts( 
            array(
                'template'  => 'account',
                'mode'      => 'account',
                'form_id'   => 'describr_account_id',
                'is_block'  => false,
                'tab'       => '',
            ),
            $atts,
            describr()->shortcodes['account']
        );
        
        //Sanitize shortcode arguments
        foreach ( $atts as $key => $value ) {
            if ( 'is_block' === $key ) {
                $atts[ $key ] = (bool) $value;
            } else {
                $atts[ $key ] = (string) $value;
            }
        }

        /**
         * Filters whether the current user can view the `describr_account`
         * shortcode content
         *
         * @since 3.0
         *
         * @param true  $can_view Whether the current user can view 
         *                        the describr_account shortcode content 
         * @param array $atts     Shortcode attributes
         */
        $can_view_account_shortcode_content = apply_filters( 'describr_account_shortcode_can_view_content', describr_can_edit_user( $this->user_id, true ) || current_user_can( 'list_users' ), $atts );

        if ( ! $can_view_account_shortcode_content ) {
            return '';
        }

        $account_hash = md5( wp_json_encode( $atts ) );

        /**
         * Filters whether to use `describr_account` shortcode more than once
         * during script execution
         *
         * @since 3.0
         *
         * @param true  $is_shortcode_singleton_disabled Whether `describr_account` 
         *                                               shortcode is disabled
         * @param array $atts                            Shortcode attributes
         */
        $is_shortcode_singleton_disabled = apply_filters( 'describr_account_shortcode_disable_singleton', true, $atts );
        
        if ( false === $is_shortcode_singleton_disabled && $this->exists( $account_hash ) ) {
            return '';
        }

        ob_start();

        if ( ! is_admin() && ! wp_doing_ajax() ) {
            describr()->dynamic_css( $atts );
        }

        if ( ! empty( $atts['tab'] ) ) {
            if ( 'account' === $atts['tab'] ) {
                $atts['tab'] = 'general';
            }

            $tabs = $this->get_allowed_tabs( $atts );

            $this->current_tab = $atts['tab'];

            if ( ! empty( $tabs[ $atts['tab'] ] ) ) { ?>
                <?php
                /**
                 * Filters the custom shortcode tab main wrapper classes
                 * 
                 * @since 3.0
                 * 
                 * @param string $classes Classes 
                 */
                $custom_classes = apply_filters( 'describr_account_custom_tab_classes', 'describr describr-account-custom-tab' );
                ?>
                <div class="<?php echo esc_attr( $custom_classes ); ?>">
                    <?php
                    /**
                     * Prints account notices
                     * 
                     * @since 3.0
                     */
                    do_action( 'describr_account_notices' );
                    ?>
                    <div class="describr-form">
                        <form method="post" action="">
                            <?php
                            /**
                             * Prints the account form hidden fields
                             *
                             * @since 3.0
                             *
                             * @param array  $atts describr_account shortcode attributes
                             * @param string $tab  The tab
                             */
                            do_action( 'describr_account_hidden_fields', $atts, $atts['tab'] );

                            $this->render_tab_content( $atts['tab'], $tabs[ $atts['tab'] ], $atts );
                            ?>
                        </form>
                    </div>
                </div>
                <?php
            }
        } else {
            $this->get_allowed_tabs( $atts );
            
            /**
             * Filters the current account tab
             *
             * @since 3.0
             *
             * @param string $tab  Current account tab
             * @param array  $atts Shortcode attributes
             */
            $this->current_tab = apply_filters( 'describr_account_current_tab', $this->current_tab, $atts );

            /**
             * Prints data or scripts before the account 
             * shortcode data is printed
             * 
             * The dynamic part of the action's name is 
             * mode of the shortcode
             *
             * @since 3.0
             *
             * @param array $atts Shortcode attributes
             */
            do_action( "describr_before_{$atts['mode']}_shortcode", $atts );

            /**
             * Prints data or scripts before the shortcode 
             * form tag is printed 
             *  
             * @since 3.0
             *
             * @param array $atts Shortcode attributes
             */
            do_action( 'describr_before_form_is_loaded', $atts );

            describr()->get_template( $atts['template'], $atts );
        }
        
        $this->accounts[] = $account_hash; 

        return ob_get_clean();
    }
    
    /**
     * Outputs the tab content on the Account page
     * 
     * @since 3.0
     * 
     * @param string $tab      The tab
     * @param array  $settings The tab settings
     * @param array  $atts     describr_account shortcode attributes
     */
    public function render_tab_content( $tab, $settings, $atts ) {
        $output = $this->get_tab_fields( $tab, $atts );

        if ( $output ) {
            /**
             * Fires before output of the Account page tab content
             * 
             * Can be used to display the fieldset opening tag
             * 
             * @since 3.0
             *
             * @param string $tab      The tab
             * @param array  $settings The tab settings
             * @param array  $atts     describr_account shortcode attributes
             */
            do_action( 'describr_account_fieldset_opening_tag', $tab, $settings, $atts );
            
            if ( ! empty ( $settings['with_header'] ) ) { 
                ?>
                <h1 class="describr-account-heading"><?php echo esc_html( $settings['title'] ); ?></h1>
                <?php 
            }
            
            /**
             * Fires before output of the Account page tab content
             * 
             * The dynamic part of the hook's name is the tab
             * 
             * @since 3.0
             *
             * @param array $atts describr_account shortcode attributes
             */
            do_action( "describr_before_account_{$tab}_content", $atts );

            echo $output;

            /**
             * Fires after output of the Account page tab content
             * 
             * The dynamic part of the hook's name is the tab
             * 
             * @since 3.0
             *
             * @param array $atts Shortcode attributes
             */
            do_action( "describr_after_account_{$tab}_content", $atts );

            if ( ! isset( $settings['show_button'] ) || false !== $settings['show_button'] ) { 
                if ( 'delete' === $this->action && 'delete' === $tab ) {
                   $submit_label = _x( 'Confirm Deletion', 'user', 'describr' );
                } elseif ( ! empty( $settings['button_label'] ) ) {
                    $submit_label = $settings['button_label'];
                } else {
                    $submit_label = $settings['title'];
                }
                
                ?>
                <div class="describr-account-btn-wrap">

                    <?php $this->nonce_field( $tab ); ?>

                    <?php describr_primary_button( $submit_label, '', array( 'id' => "describr_account_submit_{$tab}" ) ); ?>
                    
                    <?php            
                    /**
                     * Fires after output of the Account page tab form submit button
                     * 
                     * The dynamic part of the hook's name is the tab
                     * 
                     * @since 3.0
                     *
                     * @param array $atts Shortcode attributes
                     */
                   do_action( "describr_after_account_{$tab}_submit_button", $atts ); 
                   ?>
                </div>
                <?php 
            }

            /**
             * Fires after output of the Account page tab content
             * 
             * Can be used to display the fieldset closing tag
             * 
             * @since 3.0
             *
             * @param string $tab      The tab
             * @param array  $settings The tab settings
             * @param array  $atts     describr_account shortcode attributes
             */
            do_action( 'describr_account_fieldset_closing_tag', $tab, $settings, $atts );
        }
    }        
      
    /**
     * Retrieves the tab fields' settings
     * 
     * @since 3.0
     * 
     * @param string $tab The tab
     * @param array  $atts The shortcode attributes
     */
    public function get_tab_fields( $tab, $atts ) {
        describr()->form()->fields       = array();
        describr()->fields()->id         = absint( $tab );
        describr()->fields()->mode       = 'account';
        describr()->fields()->is_editing = true;
        
        $user_id = $this->user_id;

        describr_set_user_id( $user_id );
        
        $output = '';

        switch ( $tab ) {
            case 'general':
                $fields = array( 
                    'user_login', 
                    'first_name', 
                    'last_name', 
                    'nickname', 
                    'display_name',
                    'user_nicename',
                    'user_email', 
                );
                
                if ( $this->is_password_required( $tab ) ) {
                    $fields[] = 'single_user_pass';
                }

                /*This filter is documented in wp-content/plugins/describr/includes/class-account.php*/
                $fields = apply_filters( 'describr_account_general_fields', $fields, $atts, $user_id );
                $fields = $this->get_fields( $fields );
                $fields = $this->maybe_unset_fields( $fields, $atts );

                foreach ( $fields as $field => $settings ) {
                    if ( ! empty( $atts['is_blocked'] ) ) {
                        $settings['is_blocked'] = true;
                    }
                    $output .= $this->edit_field( $field, $settings );
                }
                break;
            case 'privacy':
                $fields = array( 
                    'show_join_date', 
                    'when_last_login_audience', 
                    'show_login', 
                    'show_profile',
                    'receive_message',
                );
                
                if ( $this->is_password_required( $tab ) ) {
                    $fields[] = 'single_user_pass';
                }

                /*This filter is documented in wp-content/plugins/describr/includes/class-account.php*/
                $fields = apply_filters( 'describr_account_privacy_fields', $fields, $atts, $user_id );
                $fields = $this->get_fields( $fields );
                $fields = $this->maybe_unset_fields( $fields, $atts );

                foreach ( $fields as $field => $settings ) {
                    if ( ! empty( $atts['is_blocked'] ) ) {
                        $settings['is_blocked'] = true;
                    }
                    $output .= $this->edit_field( $field, $settings );
                }
                break;
            case 'password':
                $fields = array( 'user_pass' );

                /*This filter is documented in wp-content/plugins/describr/includes/class-account.php*/
                $fields = apply_filters( 'describr_account_password_fields', $fields, $atts, $user_id );
                $fields = $this->get_fields( $fields );
                $fields = $this->maybe_unset_fields( $fields, $atts );

                foreach ( $fields as $field => $settings ) {
                    if ( ! empty( $atts['is_blocked'] ) ) {
                        $settings['is_blocked'] = true;
                    }
                    $output .= $this->edit_field( $field, $settings );
                }
                break;
            case 'sessions':
                $sessions = \WP_Session_Tokens::get_instance( $user_id );

                $is_self = describr_is_self( $user_id );

                $can_destroy = false;

                if ( $is_self && count( $sessions->get_all() ) === 1 ) {
                    $output .= '<div class="user-sessions-wrap">';
                    $output .= '<div aria-live="assertive">';
                    $output .= '<div class="destroy-sessions"><button type="button" disabled class="describr-btn describr-btn-link">';
                    $output .= esc_html__( 'Log Out Everywhere Else', 'describr' );
                    $output .= '</button></div>';
                    $output .= '<p class="describr-description">';
                    $output .= esc_html__( 'You are only logged in at this location.', 'describr' );
                    $output .= '</p>';
                    $output .= '</div>';
                    $output .= '</div>';
                } elseif ( $is_self && count( $sessions->get_all() ) > 1 ) {
                    $can_destroy = true;
                    
                    $output .= '<div class="user-sessions-wrap describr-hide-if-no-js">';
                    $output .= '<div aria-live="assertive">';
                    $output .= '<div class="destroy-sessions"><button type="button" class="describr-btn describr-btn-link" id="describr-destroy-sessions">';
                    $output .= esc_html__( 'Log Out Everywhere Else', 'describr' );
                    $output .= '</button></div>';
                    $output .= '<p class="describr-description">';
                    $output .= esc_html__( 'Did you lose your phone or leave your account logged in at a public computer? You can log out everywhere else, and stay logged in here.', 'describr' );
                    $output .= '</p>';
                    $output .= '</div>';
                    $output .= '</div>';
                } elseif ( ! $is_self && $sessions->get_all() ) {
                    $can_destroy = true;

                    $user = get_userdata( $user_id );

                    $output .= '<div class="user-sessions-wrap describr-hide-if-no-js">';
                    $output .= '<div aria-live="assertive">';
                    $output .= '<div class="destroy-sessions"><button type="button" class="describr-btn describr-btn-link" id="describr-destroy-sessions">';
                    $output .= esc_html__( 'Log Out Everywhere', 'describr' );
                    $output .= '</button></div>';
                    $output .= '<p class="describr-description">';
                    $output .= esc_html(sprintf( 
                        /*translators: %s: User's display name.*/
                        __( 'Log %s out of all locations.', 'describr' ), $user->display_name 
                    ));
                    $output .= '</p>';
                    $output .= '</div>';
                    $output .= '</div>';
                }
                
                /**
                 * Filters the destroy sessions output
                 * 
                 * @since 3.0
                 * 
                 * @param string $output      The output to destroy sessions
                 * @param bool   $can_destroy Whether user sessions can be destroyed
                 * @param int    $user_id     ID of user whose sessions can be destroyed
                 */
                $output = apply_filters( 'describr_account_destroy_sessions', $output, $can_destroy, $user_id );
                
                $fields = array( 'logout_everywhere' );

                /*This filter is documented in wp-content/plugins/describr/includes/class-account.php*/
                $fields = apply_filters( 'describr_account_sessions_fields', $fields, $atts, $user_id );
                $fields = $this->get_fields( $fields );
                $fields = $this->maybe_unset_fields( $fields, $atts );

                foreach ( $fields as $field => $settings ) {
                    if ( ! empty( $atts['is_blocked'] ) ) {
                        $settings['is_blocked'] = true;
                    }
                    $output .= $this->edit_field( $field, $settings );
                }
                break;
            case 'notifications':
                $fields = array( 'receive_notifications' );

                /*This filter is documented in wp-content/plugins/describr/includes/class-account.php*/
                $fields = apply_filters( 'describr_account_notifications_fields', $fields, $atts, $user_id );
                $fields = $this->get_fields( $fields );                
                $fields = $this->maybe_unset_fields( $fields, $atts );                

                foreach ( $fields as $field => $settings ) {
                    if ( ! empty( $atts['is_blocked'] ) ) {
                        $settings['is_blocked'] = true;
                    }
                    $output .= $this->edit_field( $field, $settings );
                }
                break;
            case 'delete':
                if ( 'delete' === $this->action ) {
                    $output .= '<p>' . esc_html__( 'You have specified this user for deletion:', 'describr' ) . '</p>';
                    
                    $output .= '<div>' . describr_floated_avatar( $this->user_id ) . '</div><div class="describr-clear"></div>';

                    /**
                     * Filters Confirm Deletion output
                     * 
                     * @since 3.0
                     * 
                     * @param string $output  Tab output
                     * @param array  $atts    Shortcode attributes
                     * @param int    $user_id User ID
                     */
                    $output = apply_filters( 'describr_account_delete_confirm_deletion', $output, $atts, $user_id );
                } else {
                    $fields = array();

                    if ( $this->is_password_required( $tab ) ) {
                        $fields[] = 'single_user_pass';
                    }

                    /*This filter is documented in wp-content/plugins/describr/includes/class-account.php*/
                    $fields = apply_filters( 'describr_account_delete_fields', $fields, $atts, $user_id );
                    $fields = $this->get_fields( $fields );
                    $fields = $this->maybe_unset_fields( $fields, $atts );

                    foreach ( $fields as $field => $settings ) {
                        if ( ! empty( $atts['is_blocked'] ) ) {
                            $settings['is_blocked'] = true;
                        }
                        $output .= $this->edit_field( $field, $settings );
                    }
                }
                break;
            default:
                /**
                 * Filters the tab fields
                 * 
                 * The dynamic part of the hook's name is the tab name
                 * 
                 * @since 3.0
                 * 
                 * @param array $fields  Tab fields
                 * @param array $atts    Shortcode attributes
                 * @param int   $user_id User ID
                 */
                $fields = apply_filters( "describr_account_{$tab}_fields", array(), $atts, $user_id );
                
                $fields = $this->get_fields( $fields );
                
                /**
                 * Filters the tab output
                 * 
                 * The dynamic part of the hook's name is the tab name
                 * 
                 * @since 3.0
                 * 
                 * @param array $output  Tab output
                 * @param array $fields  Tab fields
                 * @param array $atts    Shortcode attributes
                 * @param int   $user_id User ID
                 */
                $output = apply_filters( "describr_account_{$tab}_content", $output, $fields, $atts, $user_id );
                break;
        }

        return $output;
    }

    /**
     * Removes field if instructed by shortcode attr
     * 
     * @since 3.0
     * 
     * @param array $fields The fields
     * @param array $atts   The shortcode attributes
     * @return array The fields, less the ones removed per shortcode
     */
    public function maybe_unset_fields( $fields, $atts ) {
        foreach ( $fields as $key => $settings ) {
            if ( isset( $settings['name'] ) && isset( $atts[ $settings['name'] ] ) && empty( $atts[ $settings['name'] ] ) ) {
                unset( $fields[ $key ] );
            }
        }

        return $fields;
    }
    
    /**
     * Retrieves Account fields' settings
     * 
     * @since 3.0
     * 
     * @param array $fields The fields
     * @return array The fields' settings, keyed by the name of the fields
     */
    public function get_fields( $fields ) {
        if ( ! is_array( $fields ) ) {
            $fields = array( $fields );
        }
        
        $fields_ = array();

        foreach ( $fields  as $field ) {
            if ( describr_is_field_viewable( $field, $this->user_id, true ) ) {
                $fields_[ $field ] = describr_get_field( $field );
            }
        }

        return $fields_;
    }
    
    /**
     * Retrieves the field in edit mode
     * 
     * @since 3.0
     * 
     * @param string $field    The field 
     * @param string $settings The field settings
     * @return string
     */
    public function edit_field( $field, $settings ) {
        return describr()->fields()->edit_field( $field, $settings );
    }

    /**
     * Retrieves whether the account action requires 
     * authentication with a password
     *
     * @param string $tab_key
     *
     * @param string $tab The tab
     * @return bool
     */
    public function is_password_required( $tab ) {
        $require_password = false;

        switch ( $tab ) {
            case 'delete':
            case 'password':
                $require_password = true;
                break;
        }
        
        /**
         * Filters whether the account1 action requires 
         * authentication with a password
         * 
         * The dynamic part of the hook's name is the tab
         *
         * @param bool $require_password Whether the tab action requires a password
         */
        return apply_filters( "describr_account_{$tab}_action_require_password", $require_password );
    }
    
    /**
     * Handles destroying multiple open sessions for a user via AJAX
     * 
     * @since 3.0
     * 
     * Adapted, in part, from `wp_ajax_destroy_sessions()`
     */
    function ajax_destroy_sessions() {
        $user = get_userdata( (int) $_POST['user_id'] );

        if ( $user ) {
            if ( ! current_user_can( 'edit_user', $user->ID ) ) {
                $user = false;
            } elseif ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'describr-destroy-session-' . $user->ID ) ) {
                $user = false;
            }
        }

        if ( ! $user ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Could not log out user sessions. Please try again.', 'describr' ),
                )
            );
        }

        $sessions = \WP_Session_Tokens::get_instance( $user->ID );

        if ( get_current_user_id() === $user->ID ) {
            $sessions->destroy_others( wp_get_session_token() );
            $message = __( 'You are now logged out everywhere else.', 'describr' );
        } else {
            $sessions->destroy_all();
            /*translators: %s: User's display name.*/
            $message = sprintf( __( '%s has been logged out.', 'describr' ), $user->display_name );
        }

        wp_send_json_success( array( 'message' => $message ) );
    }
}
