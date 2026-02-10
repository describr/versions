<?php
namespace describr;

/**
 * Mailer class
 * 
 * Container for sending Describr emails.
 *
 * @package Describr
 * @since 3.0
 */
class Mailer {
    /**
     * Style for URLs
     * 
     * @since 3.0
     * @var string
     */
    public $url_style = 'style="color:#135e96;text-decoration:none;"';
        
    /**
     * Default color
     * 
     * @since 3.0
     * @var string
     */
    public $color = '#3c434a';
        
    /**
     * Default font family
     * 
     * @since 3.0
     * @var string
     */
    public $font = 'arial,helvetica,san-serif,serif,monospace';

    /**
     * Default font size
     * 
     * @since 3.0
     * @var string
     */
    public $font_size = '14px';

    /**
     * Email subject
     * 
     * @since 3.0
     * @var string
     */
    public $subject;
        
    /**
     * Alternative email subject
     * 
     * @since 3.0
     * @var string
     */
    public $alt_subject = '';
        
    /**
     * Email content
     * 
     * @since 3.0
     * @var string
     */
    public $message;
        
    /**
     * Email headers
     * 
     * @since 3.0
     * @var string
     */
    public $headers;
    
    /**
     * Email from name
     * 
     * @since 3.0
     * @var string
     */
    public $from_name;

    /**
     * Email from email
     * 
     * @since 3.0
     * @var string
     */
    public $from_email;

    /**
     * Email content type
     * 
     * @since 3.0
     * @var string
     */
    public $content_type;

    /**
     * Email attachments
     * 
     * @since 3.0
     * @var array
     */
    public $attachments = array();
        
    /**
     * Whether the the network admin
     * is the source of the email
     * 
     * @since 3.0
     * @var bool
     */
    public $sent_from_network = false;
        
    /**
     * Whether email should be sent
     * to all network admins
     * 
     * @since 3.0
     * @var bool
     */
    public $sent_to_all_network_admins = false;
        
    /**
     * Whether email should be sent
     * to all single-site admins
     * 
     * @since 3.0
     * @var bool
     */
    public $send_to_all_admins = false;

    /**
     * Whether the email MIME type should be text/html
     * 
     * @since 3.0
     * @var bool
     */
    public $is_html = true;
        
    /**
     * Templates' slug
     * 
     * @since 3.0
     * @var string
     */
    public $slug = '';

    /**
     * Additional arguments passed to the template
     * 
     * @since 3.0
     * @var array
     */
    public $args = array();
        
    /**
     * Constructor
     * 
     * @since 3.0
     */
    public function __construct() { 
        add_action( 'password_reset', array( $this, 'notify_admin_user_change_password' ), 10, 0 );
            
        add_filter( 'wp_new_user_notification_email', array( $this, 'new_user_notification' ), 10, 2 );
            
        add_filter( 'describr_placeholders', array( $this, 'add_placeholder' ), 10, 3 );

        add_filter( 'wp_mail', array( $this, 'maybe_do_not_notify_user' ), 10, 1 );
        add_filter( 'wp_mail', array( $this, 'add_recipient_email_to_template' ), 11, 1 );
        add_filter( 'wp_mail', array( $this, 'add_unsubscribe_link_to_template' ), 12, 1 );
        add_filter( 'wp_mail', array( $this, 'replace_header_footer_placeholders' ), 13, 1 );
        add_filter( 'wp_mail', array( $this, 'retrieve_headers' ), 999, 1 );

        add_filter( 'wp_mail_from', array( $this, 'from' ), 10, 1 );
        add_filter( 'wp_mail_from_name', array( $this, 'from_name_' ), 10, 1 );
        add_filter( 'wp_mail_content_type', array( $this, 'content_type_' ), 10, 1 );

        add_action( 'phpmailer_init', array( $this, 'setup_smtp' ), 10, 1 );
            
        add_filter( 'wp_send_new_user_notification_to_admin', array( $this, 'send_new_user_notification_to_admin' ), 10, 0 );
        add_filter( 'wp_send_new_user_notification_to_user', array( $this, 'send_new_user_notification_to_user' ), 10, 0 );
    }
        
    /**
     * Returns the contents of the new pending user notification email sent to the site admin
     * 
     * This filter is added to the {@see 'wp_new_user_notification_email_admin'}
     * hook in wp-content/plugins/describr/includes/class-user.php
     *
     * @since 3.0
     *
     * @param array   $wp_new_user_notification_email_admin {
     *     Used to build wp_mail()
     *
     *     @type string $to      The intended recipient - site admin email address
     *     @type string $subject The subject of the email
     *     @type string $message The body of the email
     *     @type string $headers The headers of the email
     * }
     * @param WP_User $user     User object for new user
     * @param string  $blogname The site title
     * @return array Arguments used to build wp_mail()
     */
    public function new_pending_user_notification_email_admin( $email, $user, $blogname ) {
        $url = admin_url( 'users.php' );

        $is_admin = true;

        $this->slug = 'send-admin-pending-user-register-notification';

        $this->send( $this->slug, compact( 'url', 'user', 'blogname', 'is_admin' ) );
            
        $subject = $email['subject'];

        /*translators: This is appended to new user registration notification email subject.*/
        $subject .= _x( ': Awaiting Review', 'user', 'describr' );

        $email['subject'] = $subject;

        $email['headers'] = $this->headers;

        $email['message'] = $this->message;

        return $email;
    }

    /**
     * Retrieves whether to send a notification email to the site admin
     * that a new user has been created
     *
     * @since 3.0
     * 
     * @return bool
     */
    public function send_new_user_notification_to_admin() {
        $send = ! empty( get_option( 'describr_new_user_notify_admin' ) );

        return $send;
    }

    /**
     * Retrieves whether to send a notification email to the new user
     *
     * @since 3.0
     * 
     * @return bool
     */
    public function send_new_user_notification_to_user() {
        $send = ! empty( get_option( 'describr_new_user_notify_user' ) );

        return $send;
    }
        
    /**
     * Removes {@see 'wp_password_change_notification'} function from the {@see 'after_password_reset'} 
     * action to prevent the admin from being notified when a user resets a lost password 
     * 
     * @since 3.0
     */
    public function notify_admin_user_change_password() {
        if ( ! get_option( 'describr_user_change_password_notify_admin' ) ) {
            remove_action( 'after_password_reset', 'wp_password_change_notification' );
        }
    }        
                
    /**
     * Returns the contents of the new user notification email sent to the user
     * 
     * The email contains a link to confirm the user if the user registered
     * using a user-generated password
     *
     * @since 3.0
     *
     * @param array   $email {
     *     Used to build wp_mail()
     *
     *     @type string $to      The intended recipient - site admin email address
     *     @type string $subject The subject of the email
     *     @type string $message The body of the email
     *     @type string $headers The headers of the email
     * }
     * @param WP_User $user User object for new user
     * @return array Array of arguments for wp_mail()
     */
    public function new_user_notification( $email, $user ) {
        if ( ! describr()->password()->is_password_required ) {
            return $email;
        } 
        
        $args = array();
        
        if ( 'approved' === describr()->user()->default_user_status ) {
            $this->slug = 'send-user-welcome';
            
            $args['blogname'] = get_bloginfo( 'name', 'display' );
        } else {
            $url = describr()->permalinks()->confirmation_url( $user );

            if ( ! $url ) {
                return $email;
            }

            $args['url'] = $url;

            $this->slug = 'send-user-confirm-account';
        }
        
        $args['user'] = $user;

        $this->is_html = user_can( $user, 'receive_html_email' );

        $this->subject = '';

        $this->send( $this->slug, $args );
        
        if ( ! ( isset( $this->message ) && $this->message ) ) {
            return $email;
        } 
        
        if ( ! empty( $this->subject ) ) {
            $email['subject'] = $this->subject;
        }

        $email['headers'] = $this->headers;
        $email['message'] = $this->message;

        return $email;
    }

    /**
     * Returns the email subject for a specific template
     * 
     * @since 3.0
     * 
     * @param string $slug Template slug
     * @param string The email subject or empty
     */
    public function subjects( $slug ) {
        /**
         * Filters the email subjects array
         * 
         * @since 3.0
         * 
         * @param array $subject The email subjects keyed by the template slug
         * @param string $slug   The template slug
         */
        $subjects = apply_filters( 
            'describr_email_subjects', 
            array(
                'send-user-welcome'                             => /*translators: %s: Site's name.*/ _x( '[%s] Welcome!', 'new user notification email subject', 'describr' ),
                'send-user-approve'                             => /*translators: %s: Site's name.*/ _x( '[%s] Account Approved', 'approved user notification email subject', 'describr' ),
                'send-user-rejected'                            => /*translators: %s: Site's name.*/ _x( '[%s] Membership Rejected', 'rejected user notification email subject', 'describr' ),
                'send-user-review-pending'                      => /*translators: %s: Site's name.*/ _x( '[%s] Membership Pending Review', 'new user notification email subject', 'describr' ),
                'send-admin-review-pending'                     => /*translators: %s: Site's name.*/ _x( '[%s] New User Registration: Awaiting Review', 'new user notification email subject', 'describr' ),
                'send-user-confirm-account'                     => /*translators: %s: Site's name.*/ _x( '[%s] Confirm Account', 'new user notification email subject', 'describr' ),
                'send-user-deleted-user'                        => /*translators: %s: Site's name.*/ _x( '[%s] Account Deleted', 'deleted user notification email subject', 'describr' ),
                'send-admin-deleted-user'                       => /*translators: %s: Site's name.*/ _x( '[%s] User Account Deleted', 'deleted user notification email subject', 'describr' ),
                'send-user-inactive-account'                    => /*translators: %s: Site's name.*/ _x( '[%s] Attention: Your Account Was Made Inactive', 'inactive user notification email subject', 'describr' ),
                'send-user-active-account'                      => /*translators: %s: Site's name.*/ _x( '[%s] Attention: Your Account Has Been Activated', 'reactivated user notification email subject', 'describr' ),
                'send-admin-pending-user-register-notification' => /*translators: %s: Site's name.*/ __( '[%s] New User Registration: Awaiting Review', 'new user registration notification email subject','describr' ),
                'send-user-email-change-confirm'                => __( '[%s] Confirm Email Change', 'new user registration notification email subject','describr' ),
            ), 
            $slug 
        );
        
        $subject = '';

        if ( isset( $subjects[ $slug ] ) ) {
            $sitename = html_entity_decode( get_bloginfo( 'name', 'display' ), ENT_QUOTES, get_bloginfo( 'charset' ) );
            $subject = sprintf( $subjects[ $slug ], stripslashes( $sitename ) );
        }

        return $subject;
    }

    /**
     * Sets credentials need to send messages using SMTP
     * 
     * @since 3.0
     * 
     * @param object $phpmailer The PHPMailer object used by `wp_mail()` to send messages
     */
    public function setup_smtp( $phpmailer ) {
        if ( defined( 'DESCRIBR_USE_SMTP' ) ) {
            if ( ! DESCRIBR_USE_SMTP ) {
                return;
            }

            //Set mailer to use SMTP
            $phpmailer->isSMTP();

            //Specify main and backup SMTP servers
            if ( defined( 'DESCRIBR_SMTP_HOST' ) ) {
                $phpmailer->Host = DESCRIBR_SMTP_HOST;
            }
                
            //Ask it to authenticate using the Username and Password properties
            if ( defined( 'DESCRIBR_SMTP_AUTH' ) && DESCRIBR_SMTP_AUTH ) {
                $phpmailer->SMTPAuth = DESCRIBR_SMTP_AUTH;
            }
                
            if ( defined( 'DESCRIBR_SMTP_USERNAME' ) ) {
                $phpmailer->Username = DESCRIBR_SMTP_USERNAME;
            }
                
            if ( defined( 'DESCRIBR_SMTP_PASS' ) ) {
                $phpmailer->Password = DESCRIBR_SMTP_PASS;
            }
                
            if ( defined( 'DESCRIBR_SMTP_ENCRYPT_PROTO' ) && DESCRIBR_SMTP_ENCRYPT_PROTO ) {
                $phpmailer->SMTPSecure = DESCRIBR_SMTP_ENCRYPT_PROTO;
            }

            if ( defined( 'DESCRIBR_SMTP_PORT' ) && DESCRIBR_SMTP_PORT ) {
                $phpmailer->Port = DESCRIBR_SMTP_PORT;
            }
        } elseif ( 'smtp' === describr_get_network_option( 'describr_mailer' ) ) {
            $phpmailer->isSMTP(); 
                
            if ( describr_get_network_option( 'describr_smtp_host' ) ) {
                $phpmailer->Host = describr_get_network_option( 'describr_smtp_host' );
            }
                
            if ( describr_get_network_option( 'describr_smtp_auth' ) ) {
                $phpmailer->SMTPAuth = 'true' === describr_get_network_option( 'describr_smtp_auth' );
            }
                
            if ( describr_get_network_option( 'describr_smtp_encrypt_proto' ) ) {
                $phpmailer->SMTPSecure = describr_get_network_option( 'describr_smtp_encrypt_proto' ); 
            }
                
            if ( describr_get_network_option( 'describr_smtp_port' ) ) {
                $phpmailer->Port = (int) describr_get_network_option( 'describr_smtp_port' ); 
            }
                
            if ( false !== describr_get_network_option( 'describr_smtp_username' ) ) {
                $phpmailer->Username = describr_get_network_option( 'describr_smtp_username' ); 
            }

            if ( false !== describr_get_network_option( 'describr_smtp_pass' ) ) {
                $phpmailer->Password = describr_get_network_option( 'describr_smtp_pass' ); 
            }
        } elseif ( 'sendmail' === describr_get_network_option( 'describr_mailer' ) ) {
            $phpmailer->isSendmail();
        } elseif ( 'qmail' === describr_get_network_option( 'describr_mailer' ) ) {
            $phpmailer->isQmail();
        } else if ( 'mail' === describr_get_network_option( 'describr_mailer' ) ) {
            $phpmailer->isMail();
        }
    }
        
    /**
     * Retrieves whether the content type is text/html
     * for admin emails
     * 
     * @since 3.0
     * 
     * @return bool True if the content type is text/html, otherwise false
     */
    public function set_admin_content_type() {
        if ( $this->sent_from_network && is_multisite() ) {
            $type = get_site_option( 'describr_admin_email_content_type' );
        } else {
            $type = get_option( 'describr_admin_email_content_type', 'text/html' );
        }

        $this->is_html = 'text/plain' !== $type;
        
        return $this->is_html;
    }

    /**
     * Sets email headers
     * 
     * @since 3.0
     * 
     * @param array $args Arguments aiding in setting headers
     */       
    public function set_headers( $args = array() ) {
        if ( $this->sent_from_network && is_multisite() ) {
            $from_name = get_site_option( 'describr_email_from_name' );

            if ( empty( $from_name ) ) {
                $from_name = get_site_option( 'site_name' );

                if ( empty( $from_name ) ) {
                    $from_name = 'WordPress';
                }
            }

            $from_email = get_site_option( 'admin_email' );

            if ( empty( $from_email ) ) {
                $from_email = get_bloginfo( 'admin_email' );
            }
        } elseif ( get_option( 'describr_mail_from_addr' ) ) {
            $from_email = get_option( 'describr_mail_from_addr' );
        } else {
            $from_email = get_bloginfo( 'admin_email' );
        }
            
        if ( empty( $from_email ) ) {
            $sitename  = wp_parse_url( network_home_url(), PHP_URL_HOST );
            $from_email = 'wordpress@';

            if ( null !== $sitename ) {
                if ( str_starts_with( $sitename, 'www.' ) ) {
                    $sitename = substr( $sitename, 4 );
                }

                $from_email .= $sitename;
            }
        }

        if ( empty( $from_name ) ) {
            $from_name = get_option( 'describr_mail_from_name' ) ? get_option( 'describr_mail_from_name' ) : get_bloginfo( 'name' );
        }
            
        if ( isset( $args['content_type'] ) ) {
            $content_type = $args['content_type'];
        } elseif ( $this->is_html ) {
            $content_type = get_option( 'html_type', 'text/html' );
        } else {
            $content_type = 'text/plain';
        }

        $charset = get_bloginfo( 'charset' );
                    
        if ( str_contains( $content_type, 'text/plain' ) ) {
            $from_name = html_entity_decode( stripslashes( $from_name ), ENT_QUOTES, $charset );
        } else {
            $from_name = esc_html( $from_name );
        }

        $this->headers = "From: {$from_name} <{$from_email}>\r\nContent-Type: {$content_type}; charset={$charset}\r\n";
    }
        
    /**
     * Sends email.
     * 
     * @since 3.0
     * @since 3.0.1 Passes `$slug` to `describr_apply_placeholders()`.
     * 
     * @param array $args_ {
     *     Arguments used to send email.
     *     @type string $email Destination for the email.
     *     @type string $slug  Template file slug.
     *     @type array  $args  Extra arguments.
     * }
     */
    public function send( ...$args_ ) {
        $args = array();
        $slug = '';

        foreach ( $args_ as $arg ) {
            if ( is_array( $arg ) ) {
                $args = $arg;
            } elseif ( is_string( $arg ) && ! str_contains( $arg, '@' ) ) {
                $slug = $arg;
            }
        }
        
        if ( $slug ) {
            $this->slug = $slug;
        }
        
        $this->args = $args;

        //If an email were passed, it would be the first argument
        if ( is_email( $args_[0] ) ) {
            $to = $args_[0];
        }
            
        if ( isset( $args['is_admin'] ) ) {
            $this->set_admin_content_type();
        }
            
        if ( isset( $to ) ) {
            //Test for user-generated message and substitute any placeholder with a temporary one, ensuring that we don't mistake the user's for ours
            if ( isset( $args['message'] ) ) {
                $_placeholders = array();

                $i = 0;

                if ( function_exists( 'microtime' ) ) {
                    $time = 'microtime';
                } else {
                    $time = 'time';
                }
                
                $_placeholders_ = array_keys( describr_apply_placeholders( $args, $slug ) );
                $_placeholders_[] = '###DESCRIBR_EMAIL_FOOTER###';
                $_placeholders_[] = '###DESCRIBR_EMAIL_ADDRESS_LINK###';
                $_placeholders_[] = '###DESCRIBR_UNSUBSCRIBE_LINK###';
                $_placeholders_[] = '###DESCRIBR_EMAIL_MAIN_TABLE_ATTRS###';

                foreach ( $_placeholders_ as $placeholder ) {
                    $placeholder_key = md5( $placeholder . $time() . $i++ );

                    $args['message'] = str_replace( $placeholder, $placeholder_key, $args['message'] );
            
                    $_placeholders[ $placeholder_key ] = $placeholder;
                }
            }

            if ( isset( $args['subject'] ) ) {
                $subject = $args['subject'];
            }

            if ( isset( $args['user'] ) ) {
                $user_id = $args['user']->ID;
            } else {
                $user = get_user_by( 'email', $to );

                if ( $user ) {
                    $args['user'] = $user;

                    $user_id = $user->ID;
                }
            }

            if ( ! empty( $user_id ) ) {
                $switched_locale = switch_to_user_locale( $user_id );

                if ( ! isset( $args['is_admin'] ) ) {
                    $this->is_html = user_can( $args['user'], 'receive_html_email' );
                }
            }
        }

        $this->set_headers( $args );
        
        $this->message = $this->query_template( $slug, $args );
        
        $this->subject = isset( $subject ) ? $subject : $this->subjects( $slug );
            
        $this->subject = wp_strip_all_tags( $this->subject );

        if ( ! $this->is_html ) {
            $this->subject = html_entity_decode( $this->subject, ENT_QUOTES, get_bloginfo( 'charset' ) );
        }
        
        if ( isset( $to ) ) {
            if ( ! empty( $_placeholders ) ) {
                foreach ( $_placeholders as $placeholder_hash => $placeholder_ ) {
                    $this->message = str_replace( $placeholder_hash, $placeholder_, $this->message );
                }
            }

            $send = wp_mail( $to, $this->subject, $this->message, $this->headers, $this->attachments );

            if ( ! empty( $switched_locale ) ) {
                restore_previous_locale();
            }
        }

        if ( isset( $send ) ) {
            return $send;
        }

        return true;
    }
        
    /**
     * Replaces site placeholders with network placeholders.
     * 
     * @since 3.0
     * @since 3.0.1 Adds arguments `$args` and `$slug`.
     * @since 3.0.1 States the type of data returned.
     * @since 3.0.1 Adds placeholders `{LOGIN_BUTTON}`, `{HEADER}`, `{FOOTER}`, 
     *              `{LOGO}`, `{BLOGNAME}`, `{USERNAME}`, `{EMAIL}`, `{DISPLAY_NAME}`, 
     *              `{MESSAGE}`, `{APPROVE_BUTTON}`, `{CONFIRM_ACCOUNT_BUTTON}`, 
     *              `{CONFIRM_EMAIL_BUTTON}`, `{MESSAGE}`, `{REPLY_BUTTON}`.
     * 
     * @param array  $placeholders Placeholders.
     * @param array  $args         Additional arguments passed to the template.
     * @param string $slug         Template slug.
     * @return Array An array of placeholders and their replacements.
     */
    public function add_placeholder( $placeholders, $args, $slug ) {
        if ( $this->sent_from_network && is_multisite() ) {
            $placeholders['{SITE_NAME}'] = get_site_option( 'site_name' );
            $placeholders['{SITE_URL}']  = network_home_url();
        }
        
        if ( ! isset( $args['slug'] ) ) {
            $args['slug'] = $slug;
        }

        ob_start();
        $this->login_btn();
        $placeholders['{LOGIN_BUTTON}'] = ob_get_clean();
        
        $placeholders['{HEADER}'] = $this->get_template( 'header', $args, true );
        
        $placeholders['{FOOTER}'] = $this->get_template( 'footer', $args, true );

        $is_html = $this->is_html;

        if ( $is_html ) {
            ob_start();
            $this->get_logo( $slug, $args );
            $placeholders['{LOGO}'] = ob_get_clean();
        } else {
            $placeholders['{LOGO}'] = '';
        }
        
        if ( isset( $args['blogname'] ) ) {
            $placeholders['{BLOGNAME}'] = $args['blogname'];
        }

        if ( isset( $args['user'] ) && ( $args['user'] instanceof \WP_User ) ) {
            $user = $args['user'];

            $placeholders['{USERNAME}']     = $user->user_login;
            $placeholders['{EMAIL}']        = $user->user_email;
            $placeholders['{DISPLAY_NAME}'] = $user->display_name;
        }
        
        if ( isset( $args['display_name'] ) ) {
            $placeholders['{DISPLAY_NAME}'] = $args['display_name'];
        }

        if ( isset( $args['sender_name'] ) ) {
            $placeholders['{DISPLAY_NAME}'] = $args['sender_name'] . "\r\n";
        }
        
        if ( isset( $args['message'] ) ) {
            $placeholders['{MESSAGE}'] = $this->make_clickable( $args['message'], $slug, $args ) . "\r\n";
        }

        if ( isset( $args['url'] ) ) {
            $url = $args['url'];
            
            if ( 'send-admin-pending-user-register-notification' === $slug ) {
                if ( $is_html ) {
                    $placeholders['{APPROVE_BUTTON}'] = $this->btn( $url, /*Verb.*/ __( 'Approve User', 'describr' ), 'admin_approve_user' );
                } else {
                    $placeholders['{APPROVE_BUTTON}'] = sprintf( 
                        /*translators: %s: Users Screen URL.*/
                        __( 'You may approve this user by visiting the following link: %s.', 'describr' ),
                        $url
                        ) . "\r\n";
                }
            } elseif ( 'send-user-confirm-account' === $slug ) {
                if ( $is_html ) {
                    $placeholders['{CONFIRM_ACCOUNT_BUTTON}'] = $this->btn( $url, _x( 'Confirm Account', 'user', 'describr' ), 'confirm_account' );
                } else {
                    $placeholders['{CONFIRM_ACCOUNT_BUTTON}'] = sprintf( 
                        /*translators: %s: User's confirmation URL.*/
                        _x( 'To confirm your account, visit the following address: %s.', 'user', 'describr' ),
                        $url
                        ) . "\r\n";
                }
            } elseif ( 'send-user-email-change-confirm' === $slug ) {
                if ( $is_html ) {
                    $placeholders['{CONFIRM_EMAIL_BUTTON}'] = $this->btn( $url, __( 'Confirm Email Change', 'describr' ), 'confirm_user_email_change' );
                } else {
                    $placeholders['{CONFIRM_EMAIL_BUTTON}'] = sprintf(
                        /*translators: %s: Confirm email change URL.*/
                        __( 'To confirm this change, please click on the following link: %s.', 'describr' ), 
                        $url 
                        ) . "\r\n\r\n";
                }
            } elseif ( 'send-user-deleted-user' === $slug ) {
                if ( $is_html ) {
                    $placeholders['{MESSAGE}'] = $this->make_clickable(sprintf(
                                                /*translators: %s: Registration URL.*/
                                                __( 'Your account has been deleted. If you did not intend to delete your account and wish to sign up again, you may do so <a href="%s">here</a>.', 'describr' ), 
                                                $url 
                                                ),  $slug, $args );
                } else {
                    $placeholders['{MESSAGE}'] = sprintf(
                                               /*translators: %s: Registration URL.*/
                                               __( 'Your account has been deleted. If you did not intend to delete your account and wish to sign up again, you may do so by visiting the following address: %s.', 'describr' ),
                                              $url
                                              ) . "\r\n";
                }
            }
        }

        if ( isset( $args['reply_url'] ) ) {
            if ( $is_html ) {
                $placeholders['{REPLY_BUTTON}'] = $this->btn( $args['reply_url'], _x( 'Reply', 'email', 'describr' ), 'reply' );
            } else {
                $placeholders['{REPLY_BUTTON}'] = sprintf(
                    /*translators: %s: Reply URL.*/
                    _x( 'To reply to this message, visit the following address: %s.', 'email', 'describr' ), 
                    $args['reply_url'] ). "\r\n";
            }
        }
        
        return $placeholders;        
    }
        
    /**
     * Returns content in template file.
     * 
     * @since 3.0
     * @since 3.0.1 Passes `$slug` to `describr_replace_placeholders()`.
     * @since 3.0.1 Removes regular expression that removes placeholders 
     *              prefixed with `HTML` and `TEXT`.
     * 
     * @param string $slug Template slug.
     * @param array  $args Extra arguments.
     * @return string|boolean Template path, otherwise false.
     */
    public function query_template( $slug, $args = array() ) {
        ob_start();

        if ( $this->is_html ) {
            /**
             * Filters HTML document type tag
             * 
             * @since 3.0
             * 
             * @param string $Doctype Html document type tag
             * @param string $slug    Template slug
             * @param array  $args    Extra arguments
             */
            echo apply_filters( 'describr_email_html_document_declaration', '<!DOCTYPE html>', $slug, $args );
                
            $text_direction = 'ltr';
                
            /*translators: 'rtl' or 'ltr'. This sets the text direction for html emails.*/
            if ( 'rtl' === _x( 'ltr', 'text direction', 'describr' ) ) {
                $text_direction = 'rtl';
            }

            /*
             * translators: Translate this to the correct language tag for your locale,
             * see https://www.w3.org/International/articles/language-tags/ for reference.
             * Do not translate into your own language.
             */
            $lang = __( 'html_lang_attribute', 'describr' );
                
            if ( 'html_lang_attribute' === $lang || preg_match( '/[^a-zA-Z0-9-]/', $lang ) ) {
                if ( isset( $args['user'] ) ) {
                    $user_id = $args['user']->ID;
                } elseif ( isset( $args['user_id'] ) ) {
                    $user_id = $args['user_id'];
                } else {
                    $user_id = get_current_user_id();
                }

                $lang = str_replace( '_', '-', get_user_locale( $user_id ) );
            }

            $html_tag = '<html xmlns="http://www.w3.org/1999/xhtml" dir="' . esc_attr( $text_direction ) . '" lang="' . esc_attr( $lang ) . '">';
                
            /**
             * Filters the email's "html" tag
             * 
             * @since 3.0
             * 
             * @param string $html "html" tag
             * @param string $slug Template slug
             * @param array  $args Additional arguments passed to the template
             */
            echo apply_filters( 'describr_email_html_tag', $html_tag, $slug, $args  );
            ?>
            <head>
                <?php
                /**
                 * Filters email's charset meta tag
                 * 
                 * @since 3.0
                 * 
                 * @param string $charset_meta The email's charset meta tag
                 * @param string $slug         Template slug
                 * @param array  $args         Additional arguments passed to the template
                 */
                echo apply_filters( 'describr_email_charset_meta_tag', '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '" />', $slug, $args );
                ?>
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <style type="text/css">
                    a[x-apple-data-detectors] {color: inherit !important;}
                </style>
            </head>
            <body <?php
                    /**
                     * Filters email's body tag attributes
                     * 
                     * @since 3.0
                     * 
                     * @param string $attrs Body attributes
                     * @param string $slug  Template slug
                     * @param array  $args  Additional arguments passed to the template
                     */
                    echo apply_filters( 'describr_email_html_body_attrs', 'style="padding:0;margin:0;background:#fff;"', $slug, $args ); ?>>
                    <?php
                    echo  $this->get_template( $slug, $args, true );
                    ?>
                </body>
            </html>
            <?php
        } else {
            $plain_text_template = wp_strip_all_tags( $this->get_template( $slug, $args, true ) );
            $plain_text_template = html_entity_decode( $plain_text_template, ENT_QUOTES, get_bloginfo( 'charset' ) );
                
            echo preg_replace( '/\s+/', ' ', $plain_text_template );
        }
        
        $message = describr_replace_placeholders( ob_get_clean(), $args, $slug );
         
        /**
         * Filters email message
         * 
         * @since 3.0
         * 
         * @param string $message Email message
         * @param string $slug    Template slug
         * @param array  $args    Additional arguments passed to the template
         */
        return apply_filters( 'describr_email_message', $message, $slug, $args );
    }

    /**
     * Includes the email template file
     * 
     * @since 3.0
     * 
     * @param string $slug   Template slug
     * @param array  $args   Additional arguments passed to the template
     * @param bool   $buffer Whether to buffer the templete content
     * @return string The contents in template file
     */
    public function get_template( $slug, $args = array(), $buffer = false ) {
        if ( ! isset( $args['slug'] ) ) {
            $args['slug'] = $slug;
        }
        
        if ( $buffer ) {
            ob_start();
        }

        $this->locate_template( "$slug.php", $args );

        if ( $buffer ) {
            return ob_get_clean();
        }

        return '';
    }

    /**
     * Retrieves the name of the highest priority email template file that exists
     * 
     * @since 3.0
     *
     * @param string $template_name Template to search for
     * @param array  $args          Additional arguments passed to the template
     * @return string The template filename, if one is located
     */
    public function locate_template( $template_name, $args = array() ) {       
        $located = '';
        
        $paths = array( get_stylesheet_directory() . '/describr/templates/email/' );
        
        if ( is_child_theme() ) {
            $paths[] = get_template_directory() . '/describr/templates/email/';
        }
        
        $paths[] = DESCRIBR_TEMPLATE . 'email' . DIRECTORY_SEPARATOR;

        foreach ( $paths as $path ) {
            $file = $path . $template_name;

            if ( file_exists( $file ) && 0 === mb_stripos( wp_normalize_path( realpath( $file ) ), wp_normalize_path( $path ), 0, 'UTF-8' ) ) {
                $located = $file;
                break;
            }
        }

        if ( '' !== $located ) {
            $this->load_template( $located, $args );
        }

        return $located;
    }

    /**
     * Include the plugin template file
     * 
     * @since 3.0
     *
     * @param string $_template_file Path to template file
     * @param array $args            Additional arguments passed to the template
     */
    function load_template( $_template_file, $args = array() ) {
        if ( is_array( $args ) ) {
            unset( $args['template'] );
            /*
             * This use of extract() cannot be removed. There are many possible ways that
             * templates could depend on variables that it creates existing, and no way to
             * detect and deprecate it.
             *
             * Passing the EXTR_SKIP flag is the safest option, ensuring globals and
             * function variables cannot be overwritten.
             */
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            extract( $args, EXTR_SKIP );
        }

        /**
         * Fires before an email template file is loaded
         *
         * @since 3.0
         *
         * @param string $_template_file The full path to the template file
         * @param array  $args           Additional arguments passed to the template
         */
        do_action( 'describr_email_before_load_template', $_template_file, $args );
        
        include $_template_file;

        /**
         * Fires after an email template file is loaded
         *
         * @since 3.0
         *
         * @param string $_template_file The full path to the template file
         * @param array  $args           Additional arguments passed to the template
         */
        do_action( 'describr_email_after_load_template', $_template_file, $args );
    }

    /**
     * Retrieves the email logo template
     * 
     * @since 3.0
     */
    public function get_logo( $slug, $args ) {        
        $img = false;
        $use_network_logo = false;

        if ( is_multisite() ) {
            if ( get_site_option( 'describr_enable_site_mail_logo' ) ) {
                $output_email_logo = get_option( 'describr_mail_logo', '' );
            } else {
                $output_email_logo = get_site_option( 'describr_mail_logo', '' );
                $use_network_logo = true;
            }
        } else {
            $output_email_logo = get_option( 'describr_mail_logo', '' );
        }

        $output_email_logo = trim( $output_email_logo );

        if ( $output_email_logo ) {
            if ( 'site' === $output_email_logo ) {        
                $blog_id = $use_network_logo ? get_main_site_id() : 0;
        
                if ( has_site_icon( $blog_id ) ) {
                    $size = $use_network_logo ? get_site_option( 'describr_mail_logo_size', 'thumbnail' ) : get_option( 'describr_mail_logo_size', 'thumbnail' );

                    if ( 'custom' === $size ) {
                        if ( $use_network_logo ) {
                            $width  = (int) get_site_option( 'describr_mail_logo_custom_size_w' );
                            $height = (int) get_site_option( 'describr_mail_logo_custom_size_h' );
                        } else {
                            $width  = (int) get_option( 'describr_mail_logo_custom_size_w' );
                            $height = (int) get_option( 'describr_mail_logo_custom_size_h' );
                        }
                    } elseif ( 'full' !== $size ) {                       
                        $sizes = wp_get_registered_image_subsizes();

                        if ( isset( $sizes[ $size ] ) ) {
                            $width  = (int) $sizes[ $size ]['width'];
                            $height = (int) $sizes[ $size ]['height'];
                        }
                    }
                    
                    if ( empty( $width ) ) {
                        $width = 50;
                    }

                    if ( empty( $height ) ) {
                        $height = 40;
                    }
                    
                    if ( 'full' === $size ) {
                        $size_data = 'full';
                        $img_hwstring = '';
                    } else {
                        $size_data = array( $width, $height );
                        $img_hwstring = ' width="' . $width . '" height="' . $height . '"';
                    }
                    
                    if ( $use_network_logo ) {
                        switch_to_blog( $blog_id );
                    }
            
                    $site_icon_id = (int) get_option( 'site_icon' );
                    
                    $url = wp_get_attachment_image_url( $site_icon_id, $size_data );

                    $url = apply_filters( 'get_site_icon_url', $url, $size, $blog_id );
                    
                    if ( $url ) {
                        $img_alt = get_post_meta( $site_icon_id, '_wp_attachment_image_alt', true );

                        if ( ! $img_alt ) {
                            $img_alt = get_bloginfo( 'name', 'display' );
                        }

                        $img = '<img src="' . esc_url( $url ) . '"' . $img_hwstring . ' alt="' . esc_attr( $img_alt ) . '" />';
                    }

                    if ( $use_network_logo ) {
                        restore_current_blog();
                    }
                }
            } else {
                $url = $use_network_logo ? get_site_option( 'describr_mail_logo_url', '' ) : get_option( 'describr_mail_logo_url', '' );
        
                $url = trim( $url );

                if ( $url ) {
                    if ( $use_network_logo ) {
                        switch_to_blog( get_main_site_id() );
                    }

                    $alt = get_bloginfo( 'name', 'display' );

                    $img = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" />';

                    if ( $use_network_logo ) {
                        restore_current_blog();
                    }
                }
            }
        }

        $args['img'] = $img;

        $this->get_template( 'logo', $args );
    } 
        
    /**
     * Returns email content with clickable links
     * 
     * @since 3.0
     * 
     * @param string $content Email content
     * @param string $slug    Template slug
     * @param array  $args    Additional arguments passed to the template
     * @return string Email content with clickable links
     */
    public function make_clickable( $content, $slug, $args ) {
        $target = ' target="_blank" rel="noopener"';
            
        if ( is_email( $content ) ) {
            $target = '';
        }

        /**
         * Filters inline style for hyperlinks in the body of emails
         * 
         * The dynamic part of the hook's name is the template slug
         * 
         * @since 3.0
         * 
         * @param string $url_style Link style
         * @param string $slug      Template slug
         * @param array  $args      Additional arguments passed to the template
         */
        $attr = (string) apply_filters( 'describr_email_link_style', $this->url_style, $slug, $args ); 
            
        $attr = trim( $attr );

        $attr .= $target; 

        return preg_replace( '/<a([^>]+)>(.*)<\/a>/s', '<a$1 ' . $attr . '>$2</a>', make_clickable( $content ) );          
    }

    /**
     * Removes email address from list of recipients if user
     * opts not to receive email notifications
     * 
     * @since 3.0
     * 
     * @param array $email {
     *     Array of the `wp_mail()` arguments
     *
     *     @type string $to      The email address of the recipient
     *     @type string $subject The subject of the email
     *     @type string $message The content of the email
     *     @type string $headers Headers
     * }
     * @return array Array of `wp_mail()` arguments
     */
    public function maybe_do_not_notify_user( $email ) {
        //Bail if admin wants all users to receive emails
        if ( ! describr_get_network_option( 'describr_users_can_unsubscribe_from_email_notif' ) ) {
            return $email;
        }
            
        $tos = $email['to'];

        if ( ! is_array( $tos ) ) {
            $tos = explode( ',', $tos );
        }

        $tos = array_map( 'trim', $tos );
            
        $admin_emails = describr_admin_emails();
            
        if ( is_multisite() ) {
            $admin_emails = array_merge( $admin_emails, describr_wpmu_admin_emails() );
        }
            
        $unsubscribed_list = describr_get_network_option( 'describr_unsubscribed_from_email_notif_emails', array() );
            
        $admin_emails = array_unique( $admin_emails );
            
        $tos_ = array();

        foreach ( $tos as $to_ ) {
            //Admins always receive email notifications
            if ( in_array( $to_, $admin_emails, true ) ) {
                $tos_[] = $to_;
                continue;
            }
                
            $user = get_user_by( 'email', $to_ );

            //Test if the user doesn't want to receive email notifications
            if ( ( isset( $user->receive_notifications ) && empty( $user->receive_notifications ) ) || in_array( $to_, $unsubscribed_list, true ) ) {
                continue;
            }

            $tos_[] = $to_;
        }
            
        $email['to'] = $tos_;

        return $email;
    }
        
    /**
     * Adds unsubscribe link to the email content
     * 
     * @since 3.0
     * 
     * @param array $email {
     *     Array of the `wp_mail()` arguments
     *
     *     @type string $to      The email address of the recipient
     *     @type string $subject The subject of the email
     *     @type string $message The content of the email
     *     @type string $headers Headers
     * }
     * @return array Array of `wp_mail()` arguments
     */
    public function add_unsubscribe_link_to_template( $email ) {
        if ( ! isset( $email['message'] ) ) {
            return $email;
        }
        
        $message = $email['message'];

        $replace = '###DESCRIBR_UNSUBSCRIBE_LINK###';

        if ( ! str_contains( $message, $replace ) ) {
            return $email;
        }

        $to = $email['to'];

        if ( ! is_array( $to ) ) {
            $to = explode( ',', $to );
        }

        /*Bail if email is destined for multiple recipients, or users are not allowed to unsubscribe from email notifications*/
        if ( 1 < count( $to ) || ! describr_get_network_option( 'describr_users_can_unsubscribe_from_email_notif' ) ) {
            $email['message'] = str_replace( $replace, '', $message );

            return $email;
        }
            
        $to = trim( $to[0] );

        $url = describr()->permalinks()->unsubscribe_url( $to );
        
        $args = $this->args;
        $slug = $this->slug;

        if ( $this->is_html ) {
            /*This filter is documented in wp-content/plugins/describr/includes/class-mailer.php*/
            $attr = apply_filters( "describr_email_url_style_{$slug}", $this->url_style, $args );

            $attr = trim( $attr );

            if ( ! empty( $attr ) ) {
                $attr = " $attr ";
            } else {
                $attr = ' ';
            }

            $content = '<tr>
                            <td style="padding: 0; vertical-align: baseline;">
                               <table role="presentation" style="border-collapse: collapse; width: 100%; border: 0;">
                                    <tr>
                                        <td style="padding: 0; vertical-align: baseline;">' .
                                            sprintf(
                                               /*translators: %s: Link to unsubscribe from email notifications.*/
                                               __( 'If you do not wish to receive email notifications, please %s.', 'describr'),
                                               sprintf( 
                                                   '<a%1$shref="%2$s">%3$s</a>', 
                                                   $attr, 
                                                   esc_url( $url ),
                                                   esc_html_x( 'unsubscribe', 'email', 'describr' ) 
                                               )
                                           ) . 
                                        '</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>';
        } else {
            $content = sprintf(
                /*translators: %s: Link to unsubscribe from email notifications.*/
                __( 'If you do not wish to receive email notifications, please click on the following link to unsubscribe: %s.', 'describr' ),
                $url 
            ) . "\r\n";
        }
             
        /**
         * Filters the part of the email that handles 
         * unsubscribing from receiving email notifications
         * 
         * @since 3.0
         * 
         * @param string $content "Unsubscribe" part of email
         * @param string $to      Email recipient
         * @param string $slug    Template slug
         * @param array  $args    Additional arguments passed to the template
         */
        $content = apply_filters( 'describr_email_unsubscribe_from_emails', $content, $to, $slug, $args );
                            
        if ( ! strlen( $content ) ) {
            $content = '';
        }
            
        $email['message'] = str_replace( $replace, $content, $message );
            
        return $email;
    }
    
    /**
     * Replaces the ###DESCRIBR_EMAIL_FOOTER### and ###DESCRIBR_EMAIL_MAIN_TABLE_ATTRS### placeholders
     * 
     * @since 3.0
     * 
     * @param array $email {
     *     Array of the `wp_mail()` arguments
     *
     *     @type string $to      The email address of the recipient
     *     @type string $subject The subject of the email
     *     @type string $message The content of the email
     *     @type string $headers Headers
     * }
     * @return array Array of `wp_mail()` arguments
     */
    public function replace_header_footer_placeholders( $email ) {
        if ( ! isset( $email['message'] ) ) {
            return $email;
        }
        
        //Filters could replace this placeholder with extra content; however, we replace it if it's still present at this juncture
        if ( str_contains( $email['message'], '###DESCRIBR_EMAIL_FOOTER###' ) ) {
            $email['message'] = str_replace( '###DESCRIBR_EMAIL_FOOTER###', '', $email['message'] );
        }
        
        if ( ! str_contains( $email['message'], '###DESCRIBR_EMAIL_MAIN_TABLE_ATTRS###' ) ) {
            return $email;
        }
        
        /**
        * Filters the email template's main table attributes
        * 
        * @since 3.0
        * 
        * @param string $attrs Main table attributes
        * @param string $slug  Template slug
        * @param array  $args  Additional arguments passed to the template
        */
        $attrs = apply_filters( 'describr_email_main_html_table_attrs', "style='border-collapse: collapse; border: 0; width: 100%; padding: 0; margin: 0; background: #fff; line-height: 1.5; font-family: {$this->font}; font-size: {$this->font_size}; color: {$this->color};'", $this->slug, $this->args );

        if ( $attrs ) {
            $attrs =  ' ' . trim( $attrs );
        } else {
            $attrs = '';
        }

        $email['message'] = str_replace( '###DESCRIBR_EMAIL_MAIN_TABLE_ATTRS###', $attrs, $email['message'] );

        return $email;
    }

    /**
     * Adds the sent-to email address and surrounding content
     * to the body of the email
     * 
     * @since 3.0
     * 
     * @param array $email {
     *     Array of the `wp_mail()` arguments
     *
     *     @type string $to      The email address of the recipient
     *     @type string $subject The subject of the email
     *     @type string $message The content of the email
     *     @type string $headers Headers
     * }
     * @return array Array of `wp_mail()` arguments
     */
    public function add_recipient_email_to_template( $email ) {
        if ( empty( $email['to'] ) || null === $email['message'] ) {
            return $email;
        }

        $message = $email['message'];
        
        $replace = '###DESCRIBR_EMAIL_ADDRESS_LINK###';

        if ( ! str_contains( $message, $replace ) ) {
            return $email;
        }

        $to = $email['to'];

        if ( ! is_array( $to ) ) {
            $to = explode( ',', $to );
        }
        
        /*Bail if email is destined for multiple recipients*/   
        if ( 1 < count( $to ) ) {
            $email['message'] = str_replace( $replace, '', $message );
            return $email;
        }

        $to = trim( $to[0] );

        $args = $this->args;
        $slug = $this->slug;

        if ( $this->is_html ) {
            /*This filter is documented in wp-content/plugins/describr/includes/class-mailer.php*/
            $attr = apply_filters( "describr_email_url_style_{$slug}", $this->url_style, $args );

            $attr = trim( $attr );

            if ( ! empty( $attr ) ) {
                $attr = " $attr ";
            } else {
                $attr = ' ';
            }

            $content = '<tr>
                            <td style="padding: 0; vertical-align: baseline;">
                                <table role="presentation" style="border-collapse: collapse; width: 100%; border: 0;">
                                    <tr>
                                        <td style="padding: 0; vertical-align: baseline;">
                                            <p style="padding: 0; margin: 1em 0;">' .
                                                sprintf(
                                                    /*translators: %s: Email address.*/
                                                    __( 'This email was sent to %s.', 'describr'),
                                                    sprintf( 
                                                        '<a%1$shref="mailto:%2$s">%3$s</a>',
                                                        $attr, 
                                                        esc_url( $to ),
                                                        esc_html( $to ) 
                                                    )
                                                ) .
                                            '</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>';
        } else {
            $content = sprintf(
                        /*translators: %s: Email address.*/
                        __( 'This email was sent to %s.', 'describr'),
                        $to
                    ) . "\r\n";
        }
            
        /**
         * Filters the part of the content of emails 
         * that handles printing the email address of the 
         * email recipient
         * 
         * @since 3.0
         * 
         * @param string $content The sent-to email address
         *                        and surrounding content
         * @param string $to      The recipient's email address
         * @param string $slug    Template slug
         * @param array  $args    Additional arguments passed to the template
         */
        $content = apply_filters( 'describr_email_footer_sent-to_message', $content, $to, $slug, $args );//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
                
        if ( ! strlen( $content ) ) {
            $content = '';
        }
        
        $email['message'] = str_replace( $replace, $content, $message );
            
        return $email;
    }
    
    /**
     * Retrieves the email headers
     * 
     * @since 3.0
     * 
     * @param array $email {
     *     Array of the `wp_mail()` arguments
     *
     *     @type string $to      The email address of the recipient
     *     @type string $subject The subject of the email
     *     @type string $message The content of the email
     *     @type string $headers Headers
     * }
     * @return array Array of `wp_mail()` arguments
     */
    public function retrieve_headers( $email ) {
        $this->from_name    = '';
        $this->from_email   = '';
        $this->content_type = '';

        if ( ! empty( $email['headers'] ) ) {
            $headers = $email['headers'];

            if ( ! is_array( $headers ) ) {
                /*
                 * Explode the headers out, so this function can take
                 * both string headers and an array of headers.
                 */
                $tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
            } else {
                $tempheaders = $headers;
            }
            
            // If it's actually got contents.
            if ( ! empty( $tempheaders ) ) {
                // Iterate through the raw headers.
                foreach ( (array) $tempheaders as $header ) {
                    if ( ! str_contains( $header, ':' ) ) {
                        continue;
                    }
                    // Explode them out.
                    list( $name, $content ) = explode( ':', trim( $header ), 2 );

                    // Cleanup crew.
                    $name    = trim( $name );
                    $content = trim( $content );

                    switch ( strtolower( $name ) ) {
                        // Mainly for legacy -- process a "From:" header if it's there.
                        case 'from':
                            $bracket_pos = strpos( $content, '<' );
                            if ( false !== $bracket_pos ) {
                                // Text before the bracketed email is the "From" name.
                                if ( $bracket_pos > 0 ) {
                                    $from_name = substr( $content, 0, $bracket_pos );
                                    $from_name = str_replace( '"', '', $from_name );
                                    $this->from_name = trim( $from_name );
                                }

                                $from_email = substr( $content, $bracket_pos + 1 );
                                $from_email = str_replace( '>', '', $from_email );
                                $this->from_email = trim( $from_email );

                            // Avoid setting an empty $from_email.
                            } elseif ( '' !== trim( $content ) ) {
                                $this->from_email = trim( $content );
                            }
                            break;
                        case 'content-type':
                            if ( str_contains( $content, ';' ) ) {
                                list( $type, $charset_content ) = explode( ';', $content );
                                $this->content_type             = trim( $type );

                                if ( false !== stripos( $charset_content, 'boundary=' ) ) {
                                    $boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
                                    
                                    if ( preg_match( '~^multipart/(\S+)~', $this->content_type, $matches ) ) {
                                        $this->content_type = 'multipart/' . strtolower( $matches[1] ) . '; boundary="' . $boundary . '"';
                                    }
                                }

                                // Avoid setting an empty $content_type.
                            } elseif ( '' !== trim( $content ) ) {
                                $this->content_type = trim( $content );
                            }
                            break;
                    }
                }
            }
        }
        
        if ( ! $this->content_type ) {
            if ( $this->sent_from_network && is_multisite() ) {
                $this->content_type = get_site_option( 'describr_email_content_type' );
            } else {
                $this->content_type = get_option( 'describr_email_content_type' );
            }

            if ( $this->content_type ) {
                $this->content_type = trim( $this->content_type );
            } else {
                $this->content_type = 'text/plain';
            }
        }

        return $email;
    }
    
    /**
     * Returns the email address to send from.
     *
     * @since 3.0
     *
     * @param string $from_email Email address to send from.
     * @return string
     */
    public function from( $from_email ) {
        if ( $this->from_email ) {
            return $from_email;
        }

        if ( $this->sent_from_network && is_multisite() ) {
            $from_email = get_site_option( 'describr_email_from_addr', '' );
            $from_email = trim( $from_email );

            if ( ! is_email( $from_email ) ) {
                $from_email = trim( get_site_option( 'admin_email', '' ) );
            }
        } else {
            $from_email = get_option( 'describr_email_from_addr', '' );
            $from_email = trim( $from_email );

            if ( ! is_email( $from_email ) ) {
                $from_email = trim( get_bloginfo( 'admin_email' ) );
            }
        }
        
        return $from_email;
    }  

    /**
     * Returns the name to associate with the "from" email address.
     *
     * @since 3.0
     *
     * @param string $from_name Name associated with the "from" email address.
     * @param string
     */
    public function from_name_( $from_name ) {
        if ( $this->from_name ) {
            return $from_name;
        }
        
        if ( $this->sent_from_network && is_multisite() ) {
            $from_name = get_site_option( 'describr_email_from_name', '' );
            $from_name = trim( $from_name );

            if ( ! $from_name ) {
                $from_name = trim( get_site_option( 'site_name', '' ) );
            }
        } else {
            $from_name = get_option( 'describr_email_from_name', '' );
            $from_name = trim( $from_name );

            if ( ! $from_name ) {
                $from_name = trim( get_bloginfo( 'name' ) );
            }
        }
        
        if ( 'text/plain' === $this->content_type ) {
            $from_name = html_entity_decode( $from_name, ENT_QUOTES, get_bloginfo( 'charset' ) );
        }

        return $from_name;
    }

    /**
     * Returns the wp_mail() content type.
     *
     * @since 3.0
     *
     * @param string $content_type Default wp_mail() content type.
     * @param string
     */
    public function content_type_( $content_type ) {
        if ( $this->content_type ) {
            return $this->content_type;
        }

        return $content_type;
    }

    /**
     * Creates call-to-action, linked button that is added to the email content
     * 
     * @since 3.0
     * 
     * @param string $url     Button URL
     * @param string $text    Button text
     * @param string $context Button context
     * @param string The button
     */
    public function btn( $url, $text, $context ) {
        /**
         * Filters the style attributes for the button
         * 
         * The dynamic part of the hook's name is the context of the button. 
         * For example, "reset_password" if the button is to reset the user's password.
         * 
         * @since 3.0
         * 
         * @param string $style_attr The style attribute
         */ 
        $styles = apply_filters( 
            "describr_email_button_{$context}", 
            array(
                'display'            => 'inline-block',
                'position'           => 'relative',
                'box-sizing'         => 'border-box',
                'cursor'             => 'pointer',
                'white-space'        => 'nowrap',
                'text-decoration'    => 'none',
                'text-shadow'        => 'none',
                'margin'             => '0',
                'border'             => '1px solid #2271b1',
                'border-radius'      => '0', 
                'background'         => '#2271b1',    
                'font-size'          => '1.2em',
                'font-weight'        => '400',
                'line-height'        => '2.15384615',
                'color'              => '#fff',
                'padding'            => '0 10px',    
                'min-height'         => '30px',
                '-webkit-appearance' => 'none',
            ) 
        );
        
        $styles_ = '';
        $p_holder = array( 'DOUBLE_QUOTE', 'SINGLE_QUOTE' );
        $quotes = array( '"', "'" );
        
        foreach ( $styles as $prop => $v ) {
            $v = str_replace( $quotes, $p_holder, $v );
            $v = esc_attr( $v );
            $v = str_replace( $p_holder, $quotes, $v );
            $styles_ .= esc_attr( $prop ) . ":{$v};";
        }

        if ( $styles_ ) {
            $styles_ = ' style="' . addslashes( $styles_ ) . '"';
        }

        $btn = '<a href="' . esc_url( $url ) . '"' . $styles_ . ' rel="noopener" target="_blank">' . wp_kses_post( $text ) . '</a>';
        
        return $btn;
    }
        
    /**
     * Outputs a log-in button.
     * 
     * @since 3.0
     * @since 3.0.1 Displays URL based on `$this->is_html` check.
     */
    public function login_btn() {
        ?>
        <tr>
            <td style="padding: 20px 0; vertical-align: baseline; border: 0;"><?php if ( $this->is_html ) {
                echo $this->btn( describr_login_url(), __( 'Log In', 'describr' ), 'login' );
            } else {
                echo "\r\n" . esc_html( sprintf(
                    /*translators: %s: Log-in URL.*/ 
                    _x( 'To log in to your account, visit the following address: %s.', 'user', 'describr' ), 
                        describr_login_url() 
                    ) ) . "\r\n";
            }
            ?>
            </td>
        </tr>
        <?php
    }
}
