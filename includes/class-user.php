<?php
namespace describr;

/**
 * User class
 *
 * @package Describr
 * @since 3.0
 */
class User {
    /**
     * The target user ID
     * 
     * @since 3.0
     * @var int
     */
    public $ID = 0;

    /**
     * Stores the WP_User object of the target user
     *
     * @since 3.0
     * @var null|WP_User
     */
    public $cur_user = null;
        
    /**
     * Stores WP_User
     *
     * @since 3.0
     * @var WP_User[]
     */
    private $cache = array();

    /**
     * The database {$table_prefix}users table columns
     * 
     * @since 3.0
     * @var array
     */
    public $wp_users = array(
        'ID', 
        'user_login', 
        'user_nicename', 
        'user_email', 
        'user_url', 
        'user_registered', 
        'display_name',
        'user_pass',
        'user_activation_key',
        'user_status',
    );

    /**
     * Viewable database {$table_prefix}users table columns
     * 
     * @since 3.0
     * @var array
     */
    public $viewable_wp_users = array( 
        'ID', 
        'user_login', 
        'user_nicename', 
        'user_email', 
        'user_url', 
        'user_registered', 
        'display_name', 
    );
    
    /**
     * The default user status
     * 
     * @since 3.0
     * @var string
     */
    private $default_user_status = '';
    
    /**
     * Whether to send the user email
     * 
     * @since 3.0
     * @var bool
     */
    public $send_email = false;

    /**
     * User constructor
     * 
     * @since 3.0
     */        
    public function __construct() {
        if ( is_multisite() ) {
            $this->wp_users[] = 'spam';
            $this->wp_users[] = 'deleted';
        }

        add_action( 'init', array( $this, 'check_status' ), 0 );
        add_action( 'init', array( $this, 'init' ), 11 );
    }
    
    /**
     * Initializes actions and filters
     * 
     * @since 3.0
     */
    public function init() {
        if ( is_admin() && ! wp_doing_ajax() ) {
            add_action( 'admin_init', array( $this, 'add_profile_verification_actions' ) );
        }

        //Fires any time a user is inserted into the database. @see wp_insert_user()
        add_action( 'user_register', array( $this, 'set_default_user_status' ), 10, 0 );
        add_action( 'user_register', array( $this, 'set_new_user_status' ), 11, 1 );
        add_action( 'user_register', array( $this, 'maybe_change_notify_admin_email_content' ), 12, 0 );  
        add_filter( 'user_register', array( $this, 'maybe_new_user_hide_admin_bar_front' ), 13, 1 ); 
        
        if ( ! is_user_logged_in() ) {
            add_action( 'register_new_user', array( $this, 'maybe_update_default_password_nag'), 10, 2 );
            add_action( 'register_new_user', array( $this, 'new_user_awaiting_review_notify_admin' ), 11, 1 );
            add_action( 'register_new_user', array( $this, 'maybe_redirect_after_register' ), 12, 1 );
        }
         
        add_action( 'wp_login', array( $this, 'time_last_login' ), 101, 2 );
        add_action( 'wp_logout', array( $this, 'manage_sessions' ), 10 );
        
        add_action( 'wp_head', array( $this, 'maybe_hide_admin_bar_front' ), 99 );
        
        if ( ! is_admin() ) {
            add_action( 'template_redirect', array( $this, 'after_field_change_redirect' ), 1 );
            add_action( 'template_redirect', array( $this, 'validate_reply_to' ), 2 );
            add_action( 'template_redirect', array( $this, 'unsubscribe_from_email_notifications' ), 10 );
            add_action( 'template_redirect', array( $this, 'update_new_user_email' ), 11 );
        } 

        //Fires any time a user is added to, or update in, the database. @see wp_insert_user()
        
            
        //Fires any time a user is updated in the database. @see wp_insert_user()
        add_action( 'profile_update', array( $this, 'profile_update' ), 10, 1 );

        if ( is_multisite() ) {
            add_action( 'wpmu_activate_user', array( $this, 'maybe_update_user' ), 10, 1 );
            add_action( 'added_existing_user', array( $this, 'maybe_update_user' ), 10, 2 );
        }

        foreach ( array( 'added_user_meta', 'updated_user_meta', 'deleted_user_meta', ) as $action ) {
            add_action( $action, array( $this, 'maybe_delete_pending_users_transient' ), 10, 4 );
        }
            
        add_action( 'deleted_user_meta', array( $this, 'maybe_delete_inactive_users_transient' ), 10, 4 );
            
        add_filter( 'pre_user_nicename', array( $this, 'remove_at_symbol' ), 10, 1 );
        add_filter( 'pre_user_login', array( $this, 'remove_at_symbol' ), 10, 1 );
    }
        
    /**
     * Redirects to profile after a field is updated
     * 
     * @since 3.0
     */
    public function after_field_change_redirect() {
        if ( isset( $_GET['describr_changed_field'] ) ) {
            $user_id = (int) $_GET['user_id'];
            
            $field = sanitize_text_field( wp_unslash( $_GET['describr_changed_field'] ) );

            if ( ! ( $field && $user_id && current_user_can( 'edit_user', $user_id ) ) ) {
                wp_die( __( 'You do not have permission to edit this user.', 'describr' ) );
            }

            $action = 'describr-edit-';
            
            if ( str_ends_with( $field, 'nicename' ) ) {
                $action .= 'nicename';
            } else {
                $action .= 'profile';
            }

            $action .= '_' . $user_id;
            
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), $action ) ) {
                wp_nonce_ays( $action );
                die();
            }

            set_query_var( 'describr_user', get_the_author_meta( 'nicename', $user_id ) );

            wp_redirect( describr()->profile()->get_url_by_field( $field ) );
            exit;
        }
    }
        
    /**
     * Validates reply-to credentials
     * 
     * The message modal is handled in wp-content/plugins/describr/includes/class-profile.php
     * 
     * @since 3.0
     */
    public function validate_reply_to() {
        if ( ! empty( $_GET['reply_key'] ) && ! empty( $_GET['sender_id'] ) ) {
            $sender_id = (int) $_GET['sender_id'];

            if ( ! is_user_logged_in() ) {
                $url = describr_login_url( describr_current_url(), $sender_id );
                wp_redirect( $url );
                exit;
            }

            if ( ! ( describr_can_send_message( $sender_id ) && describr_is_user_viewable( $sender_id ) ) ) {
                return;
            }
            
            //Receives message and is attempting to reply
            $current_user = wp_get_current_user();

            $messages_keys = get_user_meta( $current_user->ID, 'describr_messages_keys', true );

            $hash = sanitize_text_field( wp_unslash( $_GET['reply_key'] ) );

            if ( ! empty( $messages_keys[ $hash ] ) && $sender_id === (int) $messages_keys[ $hash ] ) {
                $goto = describr_profile_url( '', $sender_id );

                list( $reply_path ) = explode( '?', $goto );

                setcookie( 'describr_reply', $hash, time() + 30, $reply_path, COOKIE_DOMAIN, 'https' === wp_parse_url( $goto, PHP_URL_SCHEME ), true );

                wp_redirect( $goto );
                exit;
            }
        }
    }
        
    /**
     * Updates new user-email
     * 
     * @since 3.0
     */
    public function update_new_user_email() {
        if ( ! empty( $_GET['newuseremail'] ) ) {
            if ( ! is_user_logged_in() ) {
                $url = describr_login_url( describr_current_url() );
                wp_redirect( $url );
                exit;
            }
                
            $current_user = wp_get_current_user();

            $new_email = get_user_meta( $current_user->ID, '_new_email', true );
                
            if ( $new_email && is_array( $new_email ) && hash_equals( $new_email['hash'], sanitize_text_field( wp_unslash( $_GET['newuseremail'] ) ) ) ) {
                $user = new \stdClass();
                $user->ID = $current_user->ID;
                $user->user_email = esc_html( trim( $new_email['newemail'] ) );                   

                global $wpdb;

                if ( is_multisite() && $wpdb->get_var( $wpdb->prepare( "SELECT user_login FROM $wpdb->signups WHERE user_login = %s", $current_user->user_login ) ) ) {
                    $wpdb->update( 
                        $wpdb->signups, 
                        array( 'user_email' => $user->user_email ), 
                        array( 'user_login' => $current_user->user_login ) 
                    );
                }
                    
                wp_update_user( $user );
                    
                delete_user_meta( $current_user->ID, '_new_email' );

                $updated = 'new_email';                 
            } else {
                $updated = 'err_new_email';
            }
            
            $goto = add_query_arg( compact( 'updated' ), describr()->account()->tab_url( 'general' ) );
            wp_redirect( $goto );
            exit;
        }
    }

    /**
     * Unsubscribes an email from receiving notifications
     *
     * @since 3.0
     */
    public function unsubscribe_from_email_notifications() {
        if ( ! empty( $_GET['unsubscribe_from_emails_key'] ) && ! empty( $_GET['email'] ) ) {
            $key = sanitize_text_field( wp_unslash( $_GET['unsubscribe_from_emails_key'] ) );
            $email = trim( wp_unslash( $_GET['email'] ) );
            
            $goto = remove_query_arg( array( 'email', 'unsubscribe_from_emails_key' ) );
            
            if ( empty( $key ) 
                || 
                ! is_email( $email ) 
                || 
                ! describr_get_network_option( 'describr_users_can_unsubscribe_from_email_notif' ) //Bail if admin wants all users to receive emails
                ||
                $key !== get_option( 'describr_unsubscribe_from_emails_key' ) //Bail if the key in the URL is different from the site's
            ) {
                wp_redirect( $goto );
                exit;
            }
            
            $user = get_user_by( 'email', $email );

            if ( $user ) {
                if ( ! is_user_logged_in() ) {
                    wp_redirect( describr_login_url( describr_current_url(), $user->ID ) );
                    exit;
                }
                
                //Bail if the email doesn't belong to the logged-in user
                if ( $user->ID !== get_current_user_id() ) {
                    wp_redirect( $goto );
                    exit;
                }

                update_user_meta( $user->ID, 'receive_notifications', 0, 1 );
            } else {
                $unsubscribed_list = describr_get_network_option( 'describr_unsubscribed_from_email_notif_emails', array() );
                
                if ( in_array( $email, $unsubscribed_list, true ) ) {
                    wp_redirect( $goto );
                    exit;
                }

                $unsubscribed_list[] = $email;

                describr_update_network_option( 'describr_unsubscribed_from_email_notif_emails', $unsubscribed_list );
            }
            
            if ( is_user_logged_in() && describr_get_page_id( 'account' ) ) {
                $goto = add_query_arg( array( 'updated' => 'success' ) , describr()->account()->tab_url( 'notifications' ) );
            }

            wp_redirect( $goto );
            exit;
        }
    }

    /**
     * Deletes the transient that stores the number of users awaiting 
     * review when a user's status is updated
     *
     * @since 3.0
     *
     * @param array  $meta_ids An array of metadata entry IDs to delete
     * @param int    $user_id  ID of the object metadata is for
     * @param string $meta_key Metadata key
     */        
    public function maybe_delete_pending_users_transient( $meta_id, $user_id, $meta_key ) {
        if ( 'account_status' === $meta_key ) {
            describr_delete_transient_once( 'describr_pending_users_count' );
        }
    }
        
    /**
     * Deletes the transient that stores the number of inactive users when
     * the admin activates a user
     *
     * @since 3.0
     *
     * @param array  $meta_ids   An array of metadata entry IDs to delete
     * @param int    $user_id    ID of the object metadata is for
     * @param string $meta_key   Metadata key
     * @param mixed  $meta_value Metadata value
     */
    public function maybe_delete_inactive_users_transient( $meta_id, $user_id, $meta_key, $meta_value ) {
        if ( 'active_by_admin' === $meta_key && 'false' === $meta_value ) {
            describr_delete_transient_once( 'describr_inactive_users_count' );
        }
    }

    /**
     * Hides the Admin Bar on the site's front end for new users
     * 
     * @since 3.0
     * 
     * @see `wp_insert_user()`
     * 
     * @param int $user_id ID of the new user
     */
    public function maybe_new_user_hide_admin_bar_front( $user_id ) {
        if ( ! user_can( $user_id, 'new_user_view_admin_bar_front' ) ) {
            update_user_meta( $user_id, 'show_admin_bar_front', 'false', 'true' );
        }
    }

    /**
     * Hide the Toolbar on the front end
     * 
     * @since 3.0 
     */   
    public function maybe_hide_admin_bar_front() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( ! current_user_can( 'view_admin_bar_front' ) ) {
            /**
             * Filters whether the Admin Bar should be shown
             * on the front end
             * 
             * @see "Show Toolbar when viewing site" on the profile
             * on the back end
             * 
             * @since 3.0
             * 
             * @param false $show_admin_bar Whether the admin bar should be shown
             */
            if ( ! apply_filters( 'describr_show_admin_bar_front', false ) ) {
                add_filter( 'show_admin_bar', '__return_false', 10, 0 );
            }
        }
    }

    /**
     * Sets redirect URL post user-registration
     * 
     * @since 3.0
     * 
     * @see register_new_user()
     * 
     * @param int $user_id User ID 
     */
    public function maybe_redirect_after_register( $user_id ) {
        $user = get_userdata( $user_id );

        if ( $user ) {
            $account_status = $user->account_status;
                
            $args = array();
            
            if ( 'rejected' === $account_status ) {
                $args['checkemail'] = 'membership_rejected';
            } elseif ( 'pending' === $account_status ) {
                $args['checkemail'] = 'membership_pending';
            } elseif ( 'approved' === $account_status && get_option( 'describr_registration_password_required' ) ) {
                $args['_membership_approved'] = 1;
                $args['log'] = rawurlencode( $user->user_login );
            } elseif ( 'confirm' === $account_status ) {
                //WordPress displays a message similar to the one that would go here.
            }
            
            if ( isset( $args['_membership_approved'] ) && ! empty( $_POST['redirect_to'] ) ) {
                return;
            }

            if ( $args ) {
                $_POST['redirect_to'] = add_query_arg( $args, describr_login_url( '', $user->ID ) );
            }
        }
    }

    /**
     * Updates the user's default_password_nag meta value
     * 
     * @since 3.0
     * 
     * @param int $user_id User ID 
     */
    public function maybe_update_default_password_nag( $user_id ) {
        if ( get_option( 'describr_registration_password_required' ) ) {
            update_user_meta( $user_id, 'default_password_nag', false );
        }
    }
    
    /**
     * Retrieves the default user status
     * 
     * @since 3.0
     */
    public function set_default_user_status() {
        $default_user_status = sanitize_text_field( describr_get_network_option( 'describr_default_user_status', '' ) );

        if ( '' === $default_user_status ) {
            $default_user_status = 'approved';
        }

        $this->default_user_status = $default_user_status;
    }

    /**
     * Sets the new user's status
     * 
     * @since 3.0
     * 
     * @param int $user_id User ID 
     */
    public function set_new_user_status( $user_id ) {
        global $wpdb;
            
        $insert_id = $wpdb->insert( 
            $wpdb->usermeta, 
            array( 
                'meta_key'   => 'account_status', 
                'meta_value' => $this->default_user_status,
                'user_id'    => $user_id,
            )
        );

        if ( $insert_id && 'pending' === $this->default_user_status ) {
            describr_delete_transient_once( 'describr_pending_users_count' );
        }
    }
    
    /**
     * Sets admin notification email for pending new user
     *
     * @since 3.0
     */
    public function maybe_change_notify_admin_email_content() {
        if ( 'pending' === $this->default_user_status ) {
            add_filter( 'wp_new_user_notification_email_admin', array( describr()->mailer() ,'new_pending_user_notification_email_admin' ), 11, 3 );
        }
    }

    /**
     * Adds actions to display and edit Profile verification on the Admin Profile screen
     * 
     * @since 3.0
     */
    public function add_profile_verification_actions() {
        foreach ( array( 'show_user_profile', 'edit_user_profile' ) as $hook ) {
            add_action( $hook, array( $this, 'print_important_user' ), 10, 1 );
        }
        
        foreach ( array( 'personal_options_update', 'edit_user_profile_update' ) as $hook1 ) {
            add_action( $hook1, array( $this, 'important_user_update' ), 10, 1 );
        }
    }
        
    /**
     * Prints the output for an important user.
     * 
     * @since 3.0
     * @since 3.1.1 Data is only printed if the current user can edit users.
     * 
     * @param WP_User $user The current WP_User object.
     */
    public function print_important_user( $user ) {
        if ( ! current_user_can( 'edit_users' ) ) {
            return;
        }
        
        $settings = describr_get_field( 'important' );
    
        $icon = '<span aria-hidden="true" class="describr-important-user ';
        $icon .= ! empty( $settings['icon'] ) ? esc_attr( $settings['icon'] ) : '';
        $icon .= '"></span>';

        $disabled = ! current_user_can( 'edit_users' );

        if ( $user->ID === get_current_user_id() ) {
            /*This filter is documented in wp-content/plugins/describr/includes/class-user.php*/
            $disabled = ! apply_filters( 'describr_make_self_important', current_user_can( 'manage_options' ), $user->ID );
        }
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="describr_important_user"><?php echo esc_html( $settings['label'] ); ?></label></th>
                <td><input id="describr_important_user"<?php echo ( ! $disabled && $icon ) ? ' aria-describedby="describr_important_user_desc"' : ''; ?> type="checkbox" name="describr_important_user" value="1"<?php checked( 1, $user->important ); disabled( $disabled ); ?> /><?php if ( $icon ) {
                    $icon .= '<span class="screen-reader-text">' . 
                    /*translators: Hidden accessibility text.*/
                    esc_html__( 'Important user indicator', 'describr' );
                    if ( ! $disabled ) {
                        $icon .= ' </span>';
                        $icon = '<span class="description" id="describr_important_user_desc">' . sprintf(
                        /*translators: %s: An image.*/
                        __( '%s will be displayed next to the user&#039;s profile picture.','describr' ),
                        $icon
                        ) . '</span>';
                    } else {
                        $icon .= '</span>';
                    }

                    echo ' ' . wp_kses_post( $icon );
                } ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Updates an important user
     * 
     * @since 3.0
     * 
     * @param int $user_id User ID
     */
    public function important_user_update( $user_id ) {
        if ( ! current_user_can( 'edit_users' ) ) {
            return;
        }

        if ( $user_id === get_current_user_id() ) {
            /**
             * Filters whether the current user can make him or herself important
             * 
             * @since 3.0
             * 
             * @param bool $can_verify_self Whether the current user can make 
             *                              him or herself as important
             * @param int  $user_id         ID for the user who is being made 
             *                              important 
             *                                
             */
            if ( ! apply_filters( 'describr_make_self_important', current_user_can( 'manage_options' ), $user_id ) ) {
                return;
            }
        }

        $verify = isset( $_POST['describr_important_user'] ) ? 1 : 0;
        update_user_meta( $user_id, 'important', $verify );
    }

    /**
     * Updates cache after a user profile is updated
     *
     * @since 3.0
     * @param int $user_id User ID
     */
    public function profile_update( $user_id ) {
        if ( isset( $this->cache[ $user_id ] ) ) {
            $this->cache[ $user_id ] = get_userdata( $user_id );
                
            if ( $user_id === $this->ID ) {
                $this->cur_user = $this->cache[ $user_id ];
            }
        }            
    }
    
    /**
     * Updates current user after being assigned to a site
     * 
     * @see 2.0
     * 
     * @see add_existing_user_to_blog() when assigned to site
     * @see wpmu_activate_signup() when user is activated
     * 
     * @param int           $user_id User ID
     * @param true|WP_Error $result  True on success or a WP_Error object if the user doesn't exist
     *                               or could not be added
     */
    public function maybe_update_user( $user_id, $result = false ) {
        if ( is_wp_error( $result ) ) {
            return;
        }
        $this->profile_update( $user_id );
    }

    /**
     * Verifies that the logged in user is authorized
     * 
     * @since 3.0
     */
    public function check_status() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $current_user = wp_get_current_user();

        if ( ! describr_is_member( $current_user ) ) {
            wp_logout();
            $sessions = \WP_Session_Tokens::get_instance( $current_user->ID );
            $sessions->destroy_all();
            session_unset();
            if ( ! empty( $_REQUEST['redirect_to'] ) ) {
                $goto = wp_unslash( $_REQUEST['redirect_to'] );
            } else {
                $goto = describr_login_url( '', $current_user->ID );
            }
            
            wp_safe_redirect( esc_url_raw( $goto ) );
            exit;
        }
    }

    /**
     * Updates the time a user logs in
     * 
     * @since 3.0
     * 
     * @param string  $user_login User's login
     * @param WP_User $user       WP_User object      
     */
    public function time_last_login( $user_login, $user ) {
        update_user_meta( $user->ID, 'describr_time_last_login', time() );
    }
        
    /**
     * Sets a user's WP_User object
     * 
     * @since 3.0
     * 
     * @param int $user_id User ID. Defaults to the current user
     */
    public function set( $user_id = 0 ) {
        if ( $user_id ) {
            $this->ID = $user_id;
        } elseif ( is_user_logged_in() ) {
            $this->ID = get_current_user_id();
        } else {
            $this->ID = $user_id;
        }
            
        if ( isset( $this->cache[ $this->ID ] ) ) {
            $this->cur_user = $this->cache[ $this->ID ];
        } else {
            $this->setup( $this->ID );
        }
    }
        
    /**
     * Sets the WP_User object of the targetted user
     * 
     * @since 3.0
     * 
     * @param int|string|WP_POST|WP_User|WP_Comment $user_id User ID, email, slug, 
     *                                                       WP_POST obejct, WP_Comment obejct, 
     *                                                       or WP_User object
     * @param string                                $by      What to get the user by
     */
    public function setup( $user_id, $by = '' ) {
        if ( isset( $this->cache[ $user_id ] ) ) {
            $this->ID = $user_id;
            $this->cur_user = $this->cache[ $this->ID ];
            return;
        }

        if ( '' !== $by ) {
            $user_id = sanitize_user( $user_id );

            if ( 'login' === $by ) {
                $data = get_user_by( 'login', $user_id );

                if ( ! $data && str_contains( $user_id, '@' ) ) {
                    $data = get_user_by( 'email', $user_id );
                }
            } elseif ( 'slug' === $by ) {
                $data = get_user_by( 'slug', $user_id );
            } elseif ( str_contains( $user_id, '@' ) ) {
                $data = get_user_by( 'email', $user_id );
            } elseif ( is_numeric( $user_id ) ) {
                $data = get_userdata( $user_id );
            }
        } elseif ( is_a( $user_id, '\WP_User' ) ) {
            $data = $user_id;
        } elseif ( is_numeric( $user_id ) ) {
            $data = get_userdata( $user_id );
        }
        
        if ( empty( $data->ID ) ) {
            $this->clear_props();
            return;
        }
        
        $this->cur_user = $data;
        $this->ID = $data->ID;
        
        $this->check_user();

        $this->update_cache( $this->ID, $this->cur_user );
    }

    /**
     * Removes unauthorised user
     * 
     * @since 3.0
     */
    private function check_user() {
        if ( ! $this->is_valid() ) {
            $this->clear_props();
        }
    }

    /**
     * Unsets the current user
     * 
     * @since 3.0
     */
    private function clear_props() {
        $this->clear_cache( $this->ID );
        //Create anonymous user object
        $this->cur_user = new \WP_User();
        $this->ID       = 0;
    }    
        
    /**
     * Caches a user's data
     * 
     * @since 3.0
     * 
     * @param int     $user_id User ID
     * @param WP_User $value   WP_User Object
     */
    private function update_cache( $user_id, $value ) {
        if ( ! empty( $user_id ) ) {
            $this->cache[ $user_id ] = $value;
        }
    }

    /**
     * Clears the cache
     * 
     * @since 3.0
     *
     * @param int $key The ID of the user whose data should be removed from the cache
     */
    private function clear_cache( $key ) {
        unset( $this->cache[ $key ] );
    }
        
    /**
     * Checks if the user is authorised by admin
     * 
     * @since 3.0
     * 
     * @param false|WP_User $user The WP_User object or false
     * @return bool True if the user has been authorized, otherwise false
     */
    public function is_valid( $user = false ) {
        if ( $user ) {
            if ( ! is_a( $user, '\WP_User' ) ) {
                $user = get_userdata( $user );
            }
                
            if ( ! $user ) {
                return true;
            }
            
            $user_id = $user->ID;
        } else {
            $user_id = $this->ID;
        }
            
        return describr_is_user_active( $user_id ) && in_array( trim( get_user_meta( $user_id, 'account_status', true ) ), array( 'approved', '' ), true ) && ! get_user_meta( $user_id, 'describr_confirmation_key', true );
    }
        
    /**
     * Checks whether to destroy all sessions after the user has logged out
     * 
     * @since 3.0
     * 
     * @param int $user_id User ID
     */
    public function manage_sessions( $user_id ) {
        if ( user_can( $user_id, 'edit_user', $user_id ) ) {
            $user = get_userdata( $user_id );
            
            if ( ! empty( $user->logout_everywhere ) ) {
                $session = \WP_Session_Tokens::get_instance( $user_id );
                $session->destroy_all();
            }
        }
    }

    /**
     * Determines whether the user exists
     * 
     * Courtesy of {@see 'WP_User::exists'} method
     *
     * @since 3.0
     *
     * @return bool True if user exists, otherwise false
     */
    public function exists() {
        return ! empty( $this->ID );
    }
        
    /**
     * Updates profile fields
     * 
     * @since 3.0
     * 
     * @param int   $user_id User ID
     * @param array $data    User data
     */
    public function update_profile( $user_id, $data ) {
        //Describr allows updating these columns, along with user_pass, in the {$table_prefix}users table
        $wp_users = array(
            'user_url',
            'user_nicename',
            'display_name',
            'user_email',
        );
        
        if ( ! empty( $data['userdata'] ) ) {
            foreach ( $data['userdata'] as $key => $val ) {
                if ( ! in_array( $key, $wp_users, true ) ) {
                    if ( update_user_meta( $user_id, $key, $val ) ) {
                        describr_auditing_fields( $key );
                    }
                } else {
                    $user[ $key ] = $val;
                }
            }
        }
                    
        unset( $user['ID'] );

        if ( $user ) {
            global $wpdb;
                
            if ( $wpdb->update( $wpdb->users, wp_unslash( $user ), array( 'ID' => $user_id ) ) ) {
                /*This filter is documented in wp-content/plugins/describr/includes/describr-functions.php*/
                if ( isset( $user['user_nicename'] ) && apply_filters( 'describr_time_must_elapse_before_nicename_can_change', true, $user_id ) ) {
                    update_user_meta( $user_id, 'describr_nicename_updated', time() );
                }

                array_map( 'describr_auditing_fields', array_keys( $user ) );
            }
        }
            
        if ( ! empty( $data['asides'] ) ) {
            foreach ( $data['asides'] as $field => $asides ) {
                describr_update_field_status_and_priv( $asides, $field, $user_id );
            }
        }

        describr_audit_fields( $user_id );
    }
        
    /**
     * Maps active and id keys to active_by_admin and ID keys, respectively
     * 
     * @since 3.0
     * 
     * @param string $key The key
     * @return string
     */
    private function map_key( $key ) {
        if ( 'active' === $key ) {
            $key = 'active_by_admin';
        } elseif ( 'id' === $key ) {
            $key = 'ID';
        }

        return $key;
    }

    /**
     * Magic method for accessing custom fields
     *
     * @since 3.0
     *
     * @param string $key User meta key to retrieve
     * @return mixed Value of the given users (if set) key or user meta key
     */
    public function __get( $key ) {
        $key = $this->map_key( $key );
        
        if ( 'ID' === $key ) {
            return $this->ID;
        }

        if ( $this->ID ) {
            $value = $this->cur_user->$key;
        } else {
            $value = '';
        }

        return $value;
    }
        
    /**
     * Magic method for checking the existence of a certain custom field
     * for the user in question
     *
     * @since 3.0
     *
     * @param string $key User meta key to check, if set
     * @return bool True if the given user meta key exists, otherwise false
     */
    public function __isset( $key ) {
        $key = $this->map_key( $key );
        
        if ( $this->ID ) {
            return isset( $this->cur_user->$key );
        }
                    
        return metadata_exists( 'user', $this->ID, $key );
    }
    
    /**
     * Sets whether the user opted to receive email notifications
     * 
     * @since 3.0
     * 
     * @param int $user_id ID of the user
     */
    public function set_can_email( $user_id ) {
        $user = get_userdata( $user_id );
        $this->send_email = ! isset( $user->receive_notifications ) || ! empty( $user->receive_notifications );
    }
        
    /**
     * Notifies the user and admin after a user is deleted
     *
     * @since 3.0
     *
     * @param int      $id       ID of the deleted user
     * @param int|null $reassign ID of the user to reassign posts and links to
     *                           Default null, for no reassignment
     * @param WP_User  $user     WP_User object of the deleted user
     */
    public function deleted_notify( $id, $reassign, $user ) {
        /**
         * Filters whether to send admin email notifications whenever
         * a user is deleted, overriding the site setting
         *
         * @since 3.0
         *
         * @param bool    $maybe_notify Whether to notify admin
         * @param WP_User $user         WP_User object of the deleted user
         */
        $maybe_notify = apply_filters( 'describr_deleted_user_notify_admin', '1' === (string) get_option( 'describr_deleted_user_notify_admin' ), $user );

        if ( $maybe_notify ) {
            $args = array( 
                'is_admin' => true, 
                'blogname' => get_bloginfo( 'name', 'display' ),
                'user'     => $user,
            );
                
            foreach ( describr_admin_emails() as $email ) {
                describr()->mailer()->send( $email, 'send-admin-deleted-user', $args );
            }
        }
        
        $maybe_notify = $this->send_email && '1' === (string) get_option( 'describr_deleted_user_notify_user' ); 
        
        /**
         * Filters whether to send the deleted user email notifications, 
         * overriding the site and user settings
         *
         * @since 3.0
         *
         * @param bool    $maybe_notify Whether to notify the deleted user
         * @param WP_User $user         WP_User object of the deleted user
         */
        $maybe_notify = apply_filters( 'describr_deleted_user_notify_user', $maybe_notify, $user );
            
        if ( $maybe_notify ) {
            $url = describr_registration_url( $user->ID );

            describr()->mailer()->send( $user->user_email, 'send-user-deleted-user', compact( 'user', 'url' ) );
        }
    }

    /**
     * Notifies site admin of new user whose account is awaiting review
     * 
     * @since 3.0
     * 
     * @param int $user_id User ID
     */
    public function new_user_awaiting_review_notify_admin( $user_id ) {
        if ( 'pending' !== $this->default_user_status ) {
            return;
        }

        /**
         * Filters whether to notify admin regarding a new user
         * whose membership is awaiting review
         *
         * @since 3.0
         *
         * @param bool $maybe_notify Whether to notify moderator
         * @param int  $user_id      User ID
         */
        $maybe_notify = apply_filters( 'describr_new_user_notify_moderator', 1 === (int) get_option( 'describr_new_user_moderation_notify' ) , $user_id );

        if ( ! $maybe_notify ) {
            return;
        }
        
        $admin_emails = describr_admin_emails();
        
        //Test if the admin_email has already been notified, and remove it from the list of admin emails
        if ( get_option( 'describr_new_user_notify_admin' ) ) {
            $admin_email = get_bloginfo( 'admin_email' );
            $admin_email = trim( $admin_email );

            if ( in_array( $admin_email, $admin_emails, true ) ) {
                $admin_emails = array_diff( $admin_emails, array( $admin_email ) );
            }
        }
        
        if ( empty( $admin_emails ) ) {
            return;
        }

        $user = get_userdata( $user_id );
            
        $is_admin = true;
            
        $blogname = get_bloginfo( 'name', 'display' );

        $is_html = describr()->mailer()->set_admin_content_type();

        $url = admin_url( 'users.php' );

        $args = compact( 'url' , 'user', 'is_admin', 'blogname' );

        foreach ( $admin_emails as $email ) {
            describr()->mailer()->send( $email, 'send-admin-pending-user-register-notification', $args );
        }
    }

    /**
     * Sends approved email notification to user after membership
     * is approved
     * 
     * @since 3.0
     * 
     * @param string $email The email address of the recipient
     * @return bool True if the message is sent, otherwise false
     */
    public function approve( $email ) {
        /**
         * Filters whether to send email notification to the user 
         * whose membership is approved, overriding the site setting
         *
         * @since 3.0
         *
         * @param bool   $maybe_notify Whether to notify the user 
         *                             whose membership is approved
         * @param string $email        The email address of the user 
         *                             whose membership is approved
         */
        $maybe_notify = apply_filters( 'describr_approved_user_notify_user', '1' === (string) get_option( 'describr_approved_user_notify_user' ), $email );

        if ( ! $maybe_notify ) {
            return false;
        }
            
        return describr()->mailer()->send( $email, 'send-user-approve' );
    }

    /**
     * Sends confirm account email to user
     * 
     * @since 3.0
     * 
     * @param string       $email Email address of the recipient
     * @param false|string $url   false or confirmation URL
     * @return bool True if the message is sent, otherwise false
     */
    public function confirm_account( $email, $url = false ) {
        $user = get_user_by( 'email', $email );
        
        if ( ! $url ) {
            $url = describr()->permalinks()->confirmation_url( $user );
        }

        return describr()->mailer()->send( $user->user_email, 'send-user-confirm-account', array( 'url' => $url ) );
    }
    
    /**
     * Sends confirmed account email to user
     * 
     * @since 3.0
     * 
     * @param string $email Email address of the recipient
     * @return bool True if the message is sent, otherwise false
     */   
    public function confirmed( $email ) {
        $user = get_user_by( 'email', $email );

        /**
         * Filters whether to send email notification to the user 
         * whose membership has been confirmed
         *
         * @since 3.0
         *
         * @param bool    $maybe_notify Whether to notify the user 
         *                              whose membership is confirmed
         * @param WP_User $user         User object of the email recipient
         */
        $maybe_notify = apply_filters( 'describr_confirm_user_notify_user', '1' === (string) get_option( 'describr_confirm_user_notify_user' ), $user );

        if ( ! $maybe_notify ) {
            return false;
        }

        return describr()->mailer()->send( $email, 'send-user-approve' );
    }

    /**
     * Sends email to the user whose membership is awaiting review
     * 
     * @since 3.0
     * 
     * @param string $email The email address of the recipient
     * @return bool True if the message is sent, otherwise false
     */
    public function pending( $email ) {
        /**
         * Filters whether to send email notifications to the user 
         * whose membership is awaiting review, overriding the site setting
         *
         * @since 3.0
         *
         * @param bool   $maybe_notify Whether to notify the user 
         *                             whose membership is awaiting review
         * @param string $email        The email address of the user 
         *                             whose membership is awaiting review
         */
        $maybe_notify = apply_filters( 'describr_pending_user_notify_user', '1' === (string) get_option( 'describr_pending_user_notify_user' ), $email );

        if ( ! $maybe_notify ) {
            return false;
        }

        return describr()->mailer()->send( $email, 'send-user-review-pending', array( 'blogname' => get_bloginfo( 'name', 'display' ) ) );
    }

    /**
     * Sends email to the user whose membership to the site is rejected
     * 
     * @since 3.0
     * 
     * @param string $email The email address of the recipient
     * @return bool True if the message is sent, otherwise false
     */
    public function rejected( $email ) {
        /**
         * Filters whether to send the rejected user email notification, overriding the site setting
         *
         * @since 3.0
         *
         * @param bool   $maybe_notify Whether to notify the rejected user
         * @param string $email        The email address of the rejected user
         */
        $maybe_notify = apply_filters( 'describr_rejected_user_notify_user', '1' === (string) get_option( 'describr_rejected_user_notify_user' ), $email );

            if ( ! $maybe_notify ) {
                return false;
            }

        return describr()->mailer()->send( $email, 'send-user-rejected', array( 'blogname' => get_bloginfo( 'name', 'display' ) ) );
    }
        
    /**
     * Sends email to the user whose account has been deactivated by admin
     * 
     * @since 3.0
     * 
     * @param string $email The email address of the recipient
     * @return bool True if the message is sent, otherwise false
     */
    public function inactive( $email ) {
        /**
         * Filters whether to send the inactive user email notification, overriding the site setting
         *
         * @since 3.0
         *
         * @param bool   $maybe_notify Whether to notify the inactive user
         * @param string $email        The email address of the inactive user
         */
        $maybe_notify = apply_filters( 'describr_inactive_user_notify_user', '1' === (string) get_option( 'describr_inactive_user_notify_user' ), $email );

        if ( ! $maybe_notify ) {
            return false;
        }
        
        $user = get_user_by( 'email', $email );

        return describr()->mailer()->send( $email, 'send-user-inactive-account', array( 'display_name' => $user->display_name ) );
    }
        
    /**
     * Sends email to the user whose account is reactivated by admin
     * 
     * @since 3.0
     * 
     * @param string $email The email address of the recipient
     * @return bool True if the message is sent, otherwise false
     */
    public function active( $email ) {
        /**
         * Filters whether to send the activated user email notification, overriding the site setting
         *
         * @since 3.0
         *
         * @param bool   $maybe_notify Whether to notify the activated user
         * @param string $email        The email address of the activated user
         */
        $maybe_notify = apply_filters( 'describr_active_user_notify_user', '1' === (string) get_option( 'describr_active_user_notify_user' ), $email );

        if ( ! $maybe_notify ) {
            return false;
        }
        
        return describr()->mailer()->send( $email, 'send-user-active-account' );
    }
        
    /**
     * Sends email change confirmation email
     * 
     * @since 3.0
     *
     * @param int    $user_id   User ID
     * @param string $new_email New email
     * @param string $hash      md5 hash in the confirmation url
     * @return bool True if the message is sent, otherwise false
     */
    public function send_confirmation_on_profile_email_change( $user_id, $new_email, $hash ) {
        $user = get_userdata( $user_id );
        $user->new_email = $new_email;

        /**
         * Fires before the new email confirmation email is sent
         *
         * @since 3.0
         *
         * @see wp_insert_user() For `$user` fields
         *
         * @param WP_User $user     The WP_User object
         * @param string $new_email New email
         * @param string  $hash     md5 hash for the confirmation URL
         */
        do_action( 'describr_confirm_email_change_email', $user, $hash );

        /**
         * Filters whether to send the confirm email change email
         *
         * @since 3.0
         *
         * @see wp_insert_user() For `$user` fields
         *
         * @param bool    $send Whether to send the email
         * @param WP_User $user The WP_User object
         * @param string  $hash md5 hash in the confirmation URL
         */
        if ( apply_filters( 'describr_send_confirm_email_change_email', true, $user, $hash ) ) {
            $url = add_query_arg( 'newuseremail', $hash, describr_home_url( $user->ID ) );
            return describr()->mailer()->send( $new_email, 'send-user-email-change-confirm', compact( 'user', 'url' ) );
        } 

        return false; 
    }

    /**
     * Sends email message originating from a profile.
     * 
     * @since 3.0
     * @since 3.0.1 Uncomments `describr_messages_keys`.
     *
     * @param int    $recipient_id Email recipent's ID.
     * @param string $subject      Email subject.
     * @param string $message      Email message.
     * @return bool True is the message is sent, otherwise false.
     */
    public function send_profile_email( $recipient_id, $subject, $message ) {
        $recipient = get_userdata( $recipient_id );
                    
        $current_user = wp_get_current_user();

        $switched_locale = switch_to_user_locale( $recipient->ID );

        if  ( '' === $subject ) {
            $subject = sprintf( 
                /*translators: %s: User's display name.*/
                _x( 'You Have a New Message from %s', 'email subject', 'describr' ), 
                $current_user->display_name 
            );
        } else {
            $subject = sprintf( 
                /*translators: 1: User's display name. 1: Email subject.*/
                _x( 'You Have a New Message from %1$s: %2$s', 'email subject', 'describr' ), 
                $current_user->display_name, 
                $subject 
            );
        }
        
        $subject = sprintf( '[%s] ', stripslashes( get_bloginfo( 'name', 'display' ) ) ) . $subject;
        
        if ( $switched_locale ) {
            restore_previous_locale();
        }
            
        $hash = md5( $current_user->ID . time() . wp_rand() );
            
        $reply_url = add_query_arg( 
            array( 
                'sender_id' => $current_user->ID, 
                'reply_key' => rawurlencode( $hash ), 
            ), 
            describr_home_url( $recipient->ID )
        );
        
        $messages_keys = $recipient->describr_messages_keys; 

        if ( ! $messages_keys ) {
            $messages_keys = array();
        }
        
        $messages_keys[ $hash ] = $current_user->ID;
        
        update_user_meta( $recipient->ID, 'describr_messages_keys', $messages_keys );

        $user = $recipient;     
        
        $sender_name = $current_user->display_name;

        $args = compact( 'user', 'hash', 'reply_url', 'subject', 'message', 'sender_name' );
            
        /**
         * Fires before the email is sent
         *
         * @since 3.0
         * 
         * @param array $args {
         *     Array of arguments to send the email
         * 
         *     @type WP_User $user      The WP_User object of the recipient
         *     @type string  $hash      Hashed key to identify the message 
         *                              in the recipients `describr_messages_keys` 
         *                              user meta array
         *     @type string  $reply_url URL that the recipient can follow to reply to the message
         *     @type string  $subject   The email subject
         *     @type string  $message   The email message   
         * } 
         */
        do_action( 'describr_profile_email', $args );

        /**
         * Filters the email message
         * 
         * @since 3.0
         * 
         * @param array $args {
         *     Array of arguments to send the email
         * 
         *     @type WP_User $user      The WP_User object of the recipient
         *     @type string  $hash      Hashed key to identify the message 
         *                              in the recipients `describr_messages_keys` 
         *                              user meta array
         *     @type string  $reply_url URL that the recipient can follow to reply to the message
         *     @type string  $subject   The email subject
         *     @type string  $message   The email message   
         * }
         */
        $args = apply_filters( 'describr_profile_email_filter', $args );
            
        return describr()->mailer()->send( $user->user_email, 'send-user-profile-email', $args );  
    }
                
    /**
     * Removes @ from user logins and user nicenames.
     * 
     * This prevents conflicts between usernames, nicenames, and email addresses, especially
     * when the user attempts to log in with a username instead of an email address
     *
     * @since 3.0.3
     *
     * @param string $sanitized_user_login Username after it has been sanitized.
     */
    public function remove_at_symbol( $name ) {
        $symb = '@';

        if ( describr_get_network_option( 'describr_allow_at_symbol_in_login_nicename' ) || ! is_string( $name ) || ! str_contains( $name, $symb ) ) {
            return $name;
        }

        $name = trim( $name, $symb );
            
        $name_ = $name;

        $name = str_replace( $symb, '', $name );
            
        if ( $name_ !== $name ) {
            $name = preg_replace( '/\s+/', ' ', $name );
        }
            
        return $name;
    }
    
    /**
     * Deletes user
     * 
     * @since 3.0
     * 
     * @param int $user_id User ID
     * @param bool True if the user was deleted, false otherwise
     */
    public function delete( $user_id ) {
        add_action( 'deleted_user', array( $this, 'deleted_notify' ), 10, 3 );
        
        if ( is_multisite() ) {
            if ( ! function_exists( 'wpmu_delete_user' ) ) {
                require_once ABSPATH . 'wp-admin/includes/ms.php';
            }
            
            add_action( 'wpmu_delete_user', array( $this, 'set_can_email' ), 10, 1 );

            $deleted = wpmu_delete_user( $user_id );
        } else {
            if ( ! function_exists( 'wp_delete_user' ) ) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
            }
            
            add_action( 'delete_user', array( $this, 'set_can_email' ), 10, 1 );
            
            $deleted = wp_delete_user( $user_id );
        }

        return $deleted;
    }
}