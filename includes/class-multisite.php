<?php
namespace describr;

/**
 * Multisite class
 *
 * @package Describr
 * @since 3.0
 */
class Multisite {
    /**
     * Multisite constructor
     * 
     * @since 3.0
     */
    public function __construct () {
        add_action( 'init', array( __CLASS__, 'init' ), 10 );
    }
        
    /**
     * Adds actions
     * 
     * @since 3.0
     */
    public static function init() {
        if ( version_compare( get_bloginfo( 'version' ), '5.1.0', '<' ) ) {
            add_action( 'wpmu_new_blog', array( __CLASS__, 'activate_plugin_on_new_site' ), 10, 1 );
        } else {
            add_action( 'wp_initialize_site', array( __CLASS__, 'activate_plugin_on_new_site' ), 10, 1 );
        }

        if ( is_network_admin() ) {
            add_action( 'wpmu_options', array( __CLASS__, 'add_to_network_options' ), 10 );
            add_action( 'update_wpmu_options', array( __CLASS__, 'update_network_options' ), 10  );
            add_action( 'admin_head-settings.php', array( __CLASS__, 'help_info' ), 10 );//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        }
    }
    
    /**
     * Adds the plugin's help texts to the Network Settings's Help tab
     * 
     * @since 3.0
     */
    public static function help_info() {
        $plugin = DESCRIBR;

        $help = '<p>' . sprintf(
            /*translators: %s: The plugin's name.*/
            __( 'The %s options that are set and changed here apply to the network as a whole.', 'describr' ),
            $plugin
        ) . '</p>' .

        '<p>' . sprintf(
            /*translators: %s: The plugin's name.*/
            __( 'Each user is assigned an account status that is used to authenticate the user. The <strong>Confirm</strong> account status is to force users to confirm their accounts by following a link sent to their email addresses. You can select one of the New User Default Account Status options. Nevertheless, the <em>Approved</em> account status is assigned to every user during activation of %s.', 'describr' ),
            $plugin
        ) . '</p>' .
                
        '<p>' . sprintf(
                /*translators: 1: The plugin's name.*/
                __( 'By default, %1$s deletes all user data it inserts into the database when the plugin is uninstalled. If you wish for this data to remain after %1$s is uninstalled, you can uncheck the <i>Delete user data saved by %1$s when %1$s is deleted</i> box.', 'describr' ),
                $plugin
            ) . '</p>' .
                
        '<p>' . __( 'WordPress allows the <em>@</em> symbol in usernames and nicenames. However, the <em>@</em> symbol is mandatory in emails, which may cause users not to be authenticated when WordPress misinterprets a username or nicename for an email and vice versa. If you do not want to allow the <em>@</em> symbol in usernames and nicenames, you can uncheck the <i>Allow @ symbol in user usernames and nicenames</i> box.', 'describr' ) . '</p>' .

        '<p>' . sprintf(
                /*translators: %s: Punctuation marks.*/
                __( 'A strong password is one that has minimum and maximum number of characters; cannot have identifiable information such as username, nicename, nickname, first name, last name, full name, email, or birthdate; must have at least one uppercase letter, one lowercase letter, and one number; and must contain at least one character like %s. If you want users to use strong passwords, you can check the <i>Require strong passwords</i> box.', 'describr' ),
                '<code>' . implode( ' ', describr_special_chars_entity_numbers() ) . '</code>'
            ) . '</p>' .
                
        '<p>' . __( 'If you want users to be able to unsubscribe from receiving email notifications, leave the <i>Users can unsubscribe from receiving email notifications</i> box checked. If you want users to always receive email notifications, uncheck the box.', 'describr' ) . '</p>' . 

        '<p>' . sprintf(
            /*translators: 1: Simple Mail Transfer Protocol abbreviation. 2: The plugin's name. 3: define(). 4: wp-config.php.*/
            __( 'You can choose which program to use to send emails by selecting one of the <i>Send Email Using</i> options. If you choose %1$s (Simple Mail Transfer Protocol), the %2$s PHP constants may be used instead. Using the constants is safer because the SMTP password will be saved in plain text in the database. The constants are set with PHP&#039;s %3$s function and must be placed in the %4$s file. Here are the constants, along with brief descriptions:', 'describr' ),
            '<abbr>(SMTP)</abbr>',
            $plugin,
            '<code>define()</code>',
            '<code>wp-config.php</code>'
        ) . '</p>' .
            
        '<p>' . sprintf(
            /*translators: 1: DESCRIBR_USE_SMTP. 2: Simple Mail Transfer Protocol abbreviation. 3: true. 4: false.*/
            __( '%1$s: Whether to use %2$s. Set to %3$s or %4$s.', 'describr' ), 
            '<code>DESCRIBR_USE_SMTP</code>',
            '<abbr>SMTP</abbr>',
            '<code>true</code>',
            '<code>false</code>'
        ) . '</p>' .

        '<p>' . sprintf(
            /*translators: %s: DESCRIBR_SMTP_HOST.*/
            __( '%s: Either a single hostname or multiple semicolon-delimited hostnames. For example, <code>smtp1.example.com;smtp2.example.com</code>.', 'describr' ), 
            '<code>DESCRIBR_SMTP_HOST</code>'
        ) . '</p>' .

        '<p>' . sprintf(
            /*translators: 1: DESCRIBR_SMTP_PORT. 2: Simple Mail Transfer Protocol abbreviation. 3: 25.*/
            __( '%1$s: The %2$s server port. Defaults to %3$s.', 'describr' ), 
            '<code>DESCRIBR_SMTP_PORT</code>',
            '<abbr>SMTP</abbr>',
            '<code>25</code>'
        ) . '</p>' .

        '<p>' . sprintf(
            /*translators: 1: DESCRIBR_SMTP_AUTH. 2: Simple Mail Transfer Protocol abbreviation. 3: true. 4: false.*/
            __( '%1$s: Whether to use %2$s authentication. Set to %3$s or %4$s. Uses the username and password constants if set to %3$s.', 'describr' ), 
            '<code>DESCRIBR_SMTP_AUTH</code>',
            '<abbr>SMTP</abbr>',
            '<code>true</code>',
            '<code>false</code>'
        ) . '</p>' .

        '<p>' . sprintf(
            /*translators: 1: DESCRIBR_SMTP_ENCRYPT_PROTO. 2: Simple Mail Transfer Protocol abbreviation. 3: TLS abbreviation. 4: SSL abbreviation.*/
            __( '%1$s: What kind of encryption to use on the %2$s connection. Options are &#039;&#039;, %3$s (Transport Layer Security), and %4$s (Secure Socket Layer).', 'describr' ), 
            '<code>DESCRIBR_SMTP_ENCRYPT_PROTO</code>',
            '<abbr>SMTP</abbr>',
            '<abbr>TLS</abbr>',
            '<abbr>SSL</abbr>'
        ) . '</p>' .

        '<p>' . sprintf(
            /*translators: 1: DESCRIBR_SMTP_USERNAME. 2: Simple Mail Transfer Protocol abbreviation.*/
            __( '%1$s: The %2$s username.', 'describr' ), 
            '<code>DESCRIBR_SMTP_USERNAME</code>',
            '<abbr>SMTP</abbr>',
        ) . '</p>' .

        '<p>' . sprintf(
            /*translators: 1: DESCRIBR_SMTP_PASS. 2: Simple Mail Transfer Protocol abbreviation.*/
            __( '%1$s: The %2$s password.', 'describr' ), 
            '<code>DESCRIBR_SMTP_PASS</code>',
            '<abbr>SMTP</abbr>',
        ) . '</p>' .
            
        '<p>' . sprintf(
            /*translators: %s: The plugin's name.*/
            __( 'You can add a logo to emails. If you want to use the site&#039;s logo as the email logo, select the <i>Site</i> option and select a size for the logo. If you want to use a custom logo in emails, select the <i>URL</i> option and type the URL. If you do not want to include a logo in emails, select <i>None</i>. The email logo can be used in emails sent by %s from all sites. If you want this feature, uncheck the <i>Allow individual sites to use their own email logos</i> box.', 'describr' ),
            $plugin
        ) . '</p>';

        get_current_screen()->add_help_tab(
            array(
                'id'      => strtolower( $plugin ),
                'title'   => $plugin,
                'content' => $help,
            )
        );
    }

    /**
     * Installs plugin at new site
     * 
     * @since 3.0
     * 
     * @param object|int $new_site New site object or new site ID
     */
    public static function activate_plugin_on_new_site( $new_site ) {
        if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        if ( is_plugin_active_for_network( DESCRIBR_FILE ) ) {
            if ( is_object( $new_site ) ) {
                $new_site = $new_site->blog_id;
            }

            switch_to_blog( $new_site );
            describr()->plugin_data_for_site();
            restore_current_blog();
        }
    }

    /**
     * Adds html markup to network settings screen
     * 
     * The network admin will be able to manage the plugin's network
     * options via here
     * 
     * @since 3.0
     */
    public static function add_to_network_options() {
        if ( ! current_user_can( 'manage_network_options' ) ) {
            return;
        }
            
        $from_addr_ex = get_site_option( 'describr_email_from_addr' );

        if ( empty( $from_addr_ex ) ) {
            $from_addr_ex = get_site_option( 'admin_email' );
            
            if ( empty( $from_addr_ex ) ) {
                $from_addr_ex = _x( 'contact@example.com', 'email example','describr' );
            }
        }

        $from_name_ex = get_site_option( 'describr_email_from_name' );

        if ( empty( $from_name_ex ) ) {
            $from_name_ex = get_network()->site_name;;
                
            if ( empty( $from_name_ex ) ) {
                $from_name_ex = __( 'WordPress', 'describr' );
            }
        }
        ?>
        <h1><?php echo esc_html( DESCRIBR ); ?></h1>
        <table role="presentation" class="form-table">
            <tr>
                <th scope="row"><label for="describr_default_user_status"><?php esc_html_e( 'New User Default Status', 'describr' ); ?></label></th>
                <td>
                    <select name="describr_default_user_status" id="describr_default_user_status">
                        <?php
                        $current_status = get_site_option( 'describr_default_user_status' );

                        foreach ( describr_account_status_labels() as $status => $label ) {
                            echo '<option value="' . esc_attr( $status ) . '"' . selected( $current_status, $status, false ) . '>' . esc_html( $label ) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="describr_allow_at_symbol_in_login_nicename"><?php esc_html_e( 'Allow @ symbol in usernames and nicenames', 'describr' ); ?></label></th>
                <td><input type="checkbox" name="describr_allow_at_symbol_in_login_nicename" id="describr_allow_at_symbol_in_login_nicename" value="1"<?php checked( '1', get_site_option( 'describr_allow_at_symbol_in_login_nicename' ) ); ?> /></td>
            </tr>
        </table>
        <h2><?php esc_html_e( 'Network Email Settings', 'describr' ); ?></h2>
        <table role="presentation" class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Network Email Settings', 'describr' ) ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span>
                            <?php
                            /*translators: Hidden accessibility text.*/
                            esc_html_e( 'Network Email Settings', 'describr' );
                            ?>
                        </span></legend>
                        <label for="describr_admin_email"><?php esc_html_e( 'Network Admin Emails', 'describr' ); ?></label>
                        <br />
                        <textarea name="describr_admin_email" id="describr_admin_email" aria-describedby="describr_admin_email_desc" rows="5" cols="45" class="large-text"><?php echo esc_textarea( get_site_option( 'describr_admin_email', '' ) ); ?></textarea>
                        <p class="description" id="describr_admin_email_desc"><?php esc_html_e( 'These email addresses are used for admin purposes. Separate emails by commas. Defaults to the network&#039;s Admin email', 'describr' ); ?></p>
                        <label for="describr_admin_email_content_type"><?php esc_html_e( 'Admin Email format:', 'describr' );?> <select name="describr_admin_email_content_type" id="describr_admin_email_content_type">
                            <option value="text/html"<?php selected( 'text/html', get_site_option( 'describr_admin_email_content_type' ) ); ?>><?php echo esc_html_x( 'HTML', 'content type','describr' ); ?></option>
                            <option value="text/plain"<?php selected( 'text/plain', get_site_option( 'describr_admin_email_content_type' ) ); ?>><?php echo esc_html_x( 'Plain text', 'content type','describr' ); ?></option>
                        </select></label>
                        <br />
                        <label for="describr_email_content_type"><?php esc_html_e( 'Users Email format:', 'describr' );?> <select name="describr_email_content_type" id="describr_email_content_type">
                            <option value="text/html"<?php selected( 'text/html', get_site_option( 'describr_email_content_type' ) ); ?>><?php echo esc_html_x( 'HTML', 'content type','describr' ); ?></option>
                            <option value="text/plain"<?php selected( 'text/plain', get_site_option( 'describr_email_content_type' ) ); ?>><?php echo esc_html_x( 'Plain text', 'content type','describr' ); ?></option>
                        </select></label>
                        <br />
                        <label for="describr_email_from_name"><?php echo esc_html_x( 'From name:', 'email "from" name', 'describr' ); ?> <input type="text" name="describr_email_from_name" value="<?php echo esc_attr( get_site_option( 'describr_email_from_name' ) ); ?>" id="describr_email_from_name"  class="regular-text" aria-describedby="describr-email-header-from-field-desc describr_email_from_name_desc" /></label> <span class="description" id="describr_email_from_name_desc"><?php esc_html_e( 'Defaults to the network&#039;s title', 'describr' ); ?></span>
                        <br />
                        <label for="describr_email_from_addr"><?php echo esc_html_x( 'From email address:', 'email "from" address', 'describr' ); ?> <input type="email" name="describr_email_from_addr" value="<?php echo esc_attr( get_site_option( 'describr_email_from_addr' ) ); ?>" id="describr_email_from_addr"  class="regular-text" aria-describedby="describr-email-header-from-field-desc describr_email_from_addr_desc" /></label> <span class="description" id="describr_email_from_addr_desc"><?php esc_html_e( 'Defaults to the network&#039;s Admin email', 'describr' ); ?></span> 
                        <p class="description" id="describr-email-header-from-field-desc"><?php echo wp_kses_post(sprintf(
                        /*translators: %s: An email's header "From" field example.*/
                        __( 'The email&#039;s header "From" field indicates the sender&#039;s name and email address: %s.', 'describr' ),
                        sprintf( '<strong>%1$s</strong> &#60;<strong>%2$s</strong>&#62;', $from_name_ex, $from_addr_ex )
                        )); ?></p>
                        <label for="describr_users_can_unsubscribe_from_email_notif"><input type="checkbox" name="describr_users_can_unsubscribe_from_email_notif" id="describr_users_can_unsubscribe_from_email_notif" value="1"<?php checked( '1', get_site_option( 'describr_users_can_unsubscribe_from_email_notif' ) ); ?> /> <?php esc_html_e( 'Users can unsubscribe from email notifications', 'describr' ); ?></label>                      
                    </fieldset>
                </td>
            </tr>
            <?php
            describr()->admin_options()->send_email_using()->email_logo( true )->password();
            ?>
            <tr>
                <td colspan="2"><label for="describr_save_plugin_data"><input name="describr_save_plugin_data" type="checkbox" id="describr_save_plugin_data" value="1" <?php checked( 1, get_site_option( 'describr_save_plugin_data' ) ); ?> /> <?php echo esc_html(sprintf(
                /*translators: 1: The plugin name.*/
                __( 'Save user data saved by %1$s when %1$s is deleted', 'describr' ), 
                DESCRIBR
                )); ?></label></td>
            </tr>
        </table>
        <?php
    }
        
    /**
     * Updates network options
     * 
     * The network admin will be able to manage the plugin's network
     * options via here
     * 
     * @since 3.0
     */
    public static function update_network_options() {
        if ( ! current_user_can( 'manage_network_options' ) ) {
            return;
        }
            
        $options = array_merge( 
            array_keys( describr()::network_options() ),
            describr()::email_mta_options(),                
            describr()::email_logo_options(),
            describr()::email_misc_options()
        );
            
        $options[] = 'describr_save_plugin_data';

        foreach ( array_unique( $options ) as $option ) {
            if ( isset( $_POST[ $option ] ) ) {
                $_POST[ $option ] = map_deep( $_POST[ $option ], 'trim' );
            }

            static::sanitize_option( $option );
        }
    }

    /**
     * Sanitizes plugin settings
     * 
     * @since 3.0
     */
    private static function sanitize_option( $option ) {
        switch ( str_replace( describr()->prefix, '', $option ) ) {
            case 'admin_email':
                $admin_email = '';

                if ( isset( $_POST[ $option ] ) ) {
                    $admin_email = array_map( 'trim', explode( ',', wp_unslash( $_POST[ $option ] ) ) );
                    $admin_email = array_filter( $admin_email, 'is_email' );
                    $admin_email = array_map( 'sanitize_email', $admin_email );
                    $admin_email = array_filter( $admin_email );
                    $admin_email = implode( ',', $admin_email );
                }

                update_site_option( $option, $admin_email );
                break;
            case 'email_from_addr':
                $email = '';
                
                if ( isset( $_POST[ $option ] ) ) {
                    $email = sanitize_email( wp_unslash( $_POST[ $option ] ) );
                }

                update_site_option( $option, $email );
                break;
            case 'admin_email_content_type':
            case 'email_content_type':
                if ( isset( $_POST[ $option ] ) && in_array( $_POST[ $option ], array( 'text/html', 'text/plain' ), true ) ) {
                    $content_type = $_POST[ $option ];
                } else {
                    $content_type = 'text/plain';
                }

                update_site_option( $option, $content_type );
                break;
            case 'email_from_name':
                $from_name = '';
                
                if ( isset( $_POST[ $option ] ) ) {
                    $from_name = wp_kses_post( wp_unslash( $_POST[ $option ] ) );
                    $from_name = esc_html( $from_name );
                }

                update_site_option( $option, $from_name );          
                break;
            case 'default_user_status':
                $user_status = 'approved';

                if ( ! empty( $_POST[ $option ] ) ) {
                    $val = sanitize_text_field( wp_unslash( $_POST[ $option ] ) );
                    
                    $user_statuses = describr_account_status_labels();

                    if ( $val && isset( $user_statuses[ $val ] ) ) {
                        $user_status = $val;
                    }
                }

                update_site_option( $option, $user_status );
                break;
            case 'save_plugin_data':
            case 'allow_at_symbol_in_login_nicename':
            case 'strong_password':
            case 'users_can_unsubscribe_from_email_notif':
            case 'enable_site_mail_logo':
            case 'require_strong_password':
            case 'confirm_password':
                $val = 0;

                if ( ! empty( $_POST[ $option ] ) ) {
                    $val = 1;
                }

                update_site_option( $option, $val );
                break;
            case 'password_min_chars':
                $min_chars = 6;

                if ( ! empty( $_POST[ $option ] ) ) {
                    $min_chars = absint( $_POST[ $option ] );
                }

                update_site_option( $option, $min_chars );
                break;
            case 'password_max_chars':
                $max_chars = 38;
                    
                if ( ! empty( $_POST[ $option ] ) ) {
                    $max_chars = absint( $_POST[ $option ] );
                }

                update_site_option( $option, $max_chars );
                break;
            case 'mailer':
                if ( isset( $_POST[ $option ] ) && in_array( $_POST[ $option ], array( 'smtp', 'mail', 'qmail', 'sendmail', ), true ) ) {
                    $mail_proto = $_POST[ $option ];
                } else {
                    $mail_proto = '';
                }

                update_site_option( $option, $mail_proto );
                break;
            case 'smtp_encrypt_proto':
                if ( isset( $_POST[ $option ] ) && in_array( $_POST[ $option ], array( '', 'ssl', 'tls', ), true ) ) {
                    $mail_encrypt_proto = $_POST[ $option ];
                } else {
                    $mail_encrypt_proto = '';
                }

                update_site_option( $option, $mail_encrypt_proto );
                break;
            case 'smtp_port':
                if ( ! empty( $_POST[ $option ] ) ) {
                    $port = absint( $_POST[ $option ] );
                } else {
                    $port = 25;
                }

                update_site_option( $option, $port );
                break;
            case 'smtp_host':
                if ( ! empty( $_POST[ $option ] ) ) {
                    $mail_host = array_map( 'trim', explode( ';', wp_kses_post( trim( $_POST[ $option ], ';' ) ) ) );
                    $mail_host = implode( ';', $mail_host );
                } else {
                    $mail_host = '';
                }

                update_site_option( $option, $mail_host );
                break;
            case 'smtp_auth':
                $auth = isset( $_POST[ $option ] ) && 'true' === $_POST[ $option ] ? $_POST[ $option ] : null;

                update_site_option( $option, $auth );
                break;
            case 'smtp_username':
            case 'smtp_pass':
                if ( isset( $_POST[ $option ] ) ) {
                    $login = wp_kses_post( $_POST[ $option ] );
                } else {
                    $login = '';
                }                    

                update_site_option( $option, $login );
                break;
            case 'mail_logo':
                if ( isset( $_POST[ $option ] ) && in_array( $_POST[ $option ], array( 'custom', 'site' ), true ) ) {
                    $mail_logo_source = $_POST[ $option ];
                } else {
                    $mail_logo_source = '';
                }

                update_site_option( $option, $mail_logo_source );
                break;
            case 'mail_logo_url':
                $url = '';
                    
                if ( isset( $_POST[ $option ] ) ) {
                    $url = sanitize_url( wp_unslash( $_POST[ $option ] ) );
                    $protocols = implode( '|', array_map( 'preg_quote', wp_allowed_protocols() ) );
                    $url = preg_match( '/^(' . $protocols . '):/is', $url ) ? $url : 'http://' . $url;
                }

                update_site_option( $option, $url );
                break;
            case 'mail_logo_size':
                if ( isset( $_POST[ $option ] ) && in_array( $_POST[ $option ], array( 'full', 'thumbnail', 'medium', 'medium_large', 'large', 'custom' ), true ) ) {
                    $size = $_POST[ $option ];
                } else {
                    $size = 'thumbnail';
                }

                update_site_option( $option, $size );
                break;
            case 'mail_logo_custom_size_w':
            case 'mail_logo_custom_size_h':
                $size = '';

                if ( isset( $_POST[ $option ] ) && '' !== $_POST[ $option ] ) {
                    $size = absint( $_POST[ $option ] );
                }
                    
                update_site_option( $option, $size );
                break;
        }
    }
}


