<?php
namespace describr;

/**
 * Config class
 *
 * @package Describr
 * @since 3.0
 */
class Config {        
    /**
     * Settings for the pages
     * 
     * @since 3.0
     * @var array 
     */
    public $pages = array();

    /**
     * Plugin's capabilities
     * 
     * @since 3.0
     * @var array
     */
	public $caps = array();
        
    /** 
     * Default form fields
     * 
     * @since 3.0
     * @var array
     */
    public $default_fields = array();
        
    /** 
     * Social media fields
     * 
     * @since 3.0
     * @var array
     */
    public $social_fields = array();
        
    /**
     * Fields that can be editted
     * 
     * @since 3.0
     * @var array
     */
    public $editable_fields = array();
        
    /**
     * Fields' supports
     * 
     * @since 3.0
     * @var array
     */
    public $supports = array();

    /** 
     * Fields privacy options
     * 
     * @since 3.0
     * @var array
     */
    public $privacy_choice = array();
    
    /**
     * Core pages shortcodes
     * 
     * @since 3.0
     * @var array
     */   
    public $shortcodes = array(
        'user'    => 'describr_user',
        'account' => 'describr_account',
    );
    
    /**
     * Stores keys for fields whose
     * editors should be logged in
     * the database
     * 
     * @since 3.0
     * @var array
     */
    public $fields_to_audit = array();
    
    /**
     * Stores fields' asides
     * 
     * @since 3.0
     * @var array
     */
    public $asides = array();
    
    /**
     * Stores fields' asides settings
     * 
     * @since 3.0
     * @var array
     */
    public $asides_settings = array();
        
    /**
	 * Config constructor
     * 
     * @since 3.0
	 */
	public function __construct() {
        add_action( 'init', array( $this, 'pages_caps_init' ), 0 );
        add_action( 'init', array( $this, 'default_fields_init' ), 1 );
        add_action( 'init', array( $this, 'editable_fields_init' ), 99 );
        add_action( 'describr_pre_default_fields_init', array( $this, 'set_privacy_choice' ), 10 );
        add_action( 'describr_pre_default_fields_init', array( $this, 'fields_asides_settings_init' ), 12 );
        add_action( 'describr_pre_default_fields_init', array( $this, 'set_social_fields' ), 13 );
    }    

    /**
     * Initializes cores settings and capabilities
     * 
     * @since 3.0
     * @since 3.0.1 Removes "site&#039;s" that precedes "front end".
     */
    public function pages_caps_init() {
        $this->caps = array(
            'receive_html_email'            => __( 'Allows user to receive emails in HTML format', 'describr' ),
            'receive_message'               => __( 'Allows user to receive messages', 'describr' ),
            'send_message'                  => __( 'Allows user to send messages', 'describr' ),
            'block_user'                    => __( 'Allows user to block other users', 'describr' ),
            'view_admin_screen'             => __( 'Allows user to view admin screens', 'describr' ),
            'delete_user_front'             => __( 'Allows user to delete their account from the front end', 'describr' ),
            'view_admin_bar_front'          => __( 'Allows user to view the Toolbar on the front end', 'describr' ),
            'new_user_view_admin_bar_front' => __( 'Allows new user to view the Toolbar on the front end', 'describr' ),
        );
            
        /**
         * Filters the pages used to display the account settings
         * and profile pages
         * 
         * @since 3.0
         * 
         * @param array $pages_settings 
         */
        $this->pages = apply_filters( 
            'describr_pages', 
            array(
                'user'   => array( 
                    'title'   => __( 'User', 'describr' ),
                    'content' => "[{$this->shortcodes['user']}]",
                    'id'      => 'describr_user_page',//Option name
                ),
                'account' => array(
                    'title'   => __( 'Account', 'describr' ),
                    'content' => "[{$this->shortcodes['account']}]",
                    'id'      => 'describr_account_page',//Option name
                ),
            )
        );
        
        /**
         * Filters plugin capabilities assigned to all users
         * 
         * @since 3.0
         * 
         * @param array $cap Plugin capabilities
         * */
        $this->caps = apply_filters( 'describr_caps', $this->caps );
    }
        
    /**
     * Retrieves most plugin settings
     * 
     * @since 3.0
     * 
     * @param bool $is_installing   Whether the plugin is being installed
     * @param bool $is_uninstalling Whether the plugin is being uninstalled
     * @return array An array of most of the plugin's settings
     */
    public function get_settings( $is_installing = false, $is_uninstalling = false ) {
        $options = array(
            'avatar'                         => 1,
            'new_user_moderation_notify'     => 1,
            'new_user_notify_admin'          => 1,
            'deleted_user_notify_admin'      => 1,
            'new_user_notify_user'           => 1,
            'approved_user_notify_user'      => 1,
            'active_user_notify_user'        => 1,
            'inactive_user_notify_user'      => 1,
            'rejected_user_notify_user'      => 1,
            'pending_user_notify_user'       => 1,
            'confirm_user_notify_user'       => 1,
            'deleted_user_notify_user'       => 1,
            'author_redirect'                => 1,
            'enable_profile_messaging'       => 1,
            'enable_profile_menu'            => 1,
            'enable_social_media'            => 1,
            'registration_password_required' => 0,
            'admin_email_content_type'       => 'text/plain',
            'email_content_type'             => 'text/plain',
            'default_profile_menu_tab'       => 'about',
            'profile_photosize'              => 200,
        );

        foreach ( array_keys( $this->profile_tabs_settings() ) as $tab ) {
            $options["profile_menu_tab_{$tab}"]         = 1;
            $options["profile_menu_tab_{$tab}_privacy"] = 'logged-in_or_logged-out';

            if ( $is_uninstalling ) {
                $options["profile_menu_tab_{$tab}_privacy_roles"] = 1;
            }
        }
            
        $prfx = describr()->prefix;

        if ( $is_installing ) {
            $options['unsubscribe_from_emails_key'] = md5( "{$prfx}unsubscribe_from_emails" . time() . wp_rand() );
        }
                        
        foreach ( $options as $option_name => $option_value ) {
            $options[ $prfx . $option_name ] = $option_value;
            unset( $options[ $option_name ] );
        }
        
        if ( ! is_multisite() ) {
            $options += static::network_options();
        }

        if ( $is_uninstalling ) {
            $options += static::network_options();
            $options += $this->account_tabs_settings();

            $options = array_keys( $options );

            foreach ( array( 'version', 'settings_not_added', 'first_activation_date', 'x', 'roles_caps', 'fields', 'fields_', 'user_change_password_notify_admin', 'roles_redirects', 'updated' ) as $option ) {
                $options[] = $prfx . $option;
            }
            
            //Stores array of emails that should not receive notifications, is a network option in multisite
            $options[] = $prfx . 'unsubscribed_from_email_notif_emails';
            $options[] = $prfx . 'unsubscribe_from_emails_key';

            $options = array_merge( 
                $options, 
                static::email_mta_options(), 
                static::email_logo_options(), 
                static::email_misc_options() 
            );

            $options = array_unique( $options );
        }
            
        return $options;
    }
        
    /**
     * Returns array containing network options and their values
     * used by the plugin in multisite
     * 
     * @since 3.0
     * 
     * @return array Network options
     */
    public static function network_options() {
        $prfx = describr()->prefix;
            
        $options = array(
            "{$prfx}save_plugin_data"                       => 0,
            "{$prfx}admin_email_content_type"               => 'text/plain',
            "{$prfx}email_content_type"                     => 'text/plain',
            "{$prfx}allow_at_symbol_in_login_nicename"      => 1,
            "{$prfx}confirm_password"                       => 1,
            "{$prfx}require_strong_password"                => 0,
            "{$prfx}password_min_chars"                     => 6,
            "{$prfx}password_max_chars"                     => 38,
            "{$prfx}default_user_status"                    => 'approved',
            "{$prfx}users_can_unsubscribe_from_email_notif" => 1,
            "{$prfx}enable_site_mail_logo"                  => 1,                
        );

        return $options;
    }
    
    /**
     * Returns an array of email settings names
     * 
     * @since 3.0
     * 
     * @return array
     */
    public static function email_mta_options() {
        return array_map( 
            'describr_prefix',
            array(
                'mailer',
                'smtp_host',
                'smtp_port',
                'smtp_auth',
                'smtp_encrypt_proto',
                'smtp_username',
                'smtp_pass',
            ) 
        );
    }

    /**
     * Returns an array of email logo settings names
     * 
     * @since 3.0
     * 
     * @return array
     */
    public static function email_logo_options() {
        return array_map( 
            'describr_prefix',
            array(
                'mail_logo',
                'mail_logo_size',
                'mail_logo_url',
                'mail_logo_custom_size_w',
                'mail_logo_custom_size_h',
            ) 
        );
    }
    
    /**
     * Returns an array of miscellaneous options used to send emails
     * 
     * @since 3.0
     * 
     * @return array
     */
    public static function email_misc_options() {
        return array_map( 
            'describr_prefix',
            array(
                'email_from_addr',
                'email_from_name',
                'admin_email',
            ) 
        );
    }
    
    /**
     * Returns an array of Account Page tab settings
     * 
     * @since 3.0
     * 
     * @return array
     */
    public function account_tabs_settings() {
        $prfx = describr()->prefix;
        
        $options = array(
            'general'       => _x( 'Account', 'user', 'describr' ),
            'privacy'       => _x( 'Privacy', 'user', 'describr' ),
            'password'      => __( 'Password', 'describr' ),
            'sessions'      => _x( 'Sessions', 'user', 'describr' ),
            'notifications' => __( 'Notifications', 'describr' ),
            'blocked_users' => _x( 'Blocked Users', 'restricted users', 'describr' ),
            'delete'        => __( 'Delete User', 'describr' ),
        );

        foreach ( $options as $option_name => $option_value ) {
            $options["{$prfx}account_tab_{$option_name}"] = $option_value;
            unset( $options[ $option_name ] );
        }
        
        /**
         * Filters the site's settings for the account tabs
         * on the front end
         * 
         * @since 3.0
         * 
         * @param array $options Tab settings
         */
        return apply_filters( 'describr_account_tabs_settings', $options );
    }
    
    /**
     * Retrieves the Profile Pge tab settings
     * 
     * @since 3.0
     * 
     * @return array
     */
    public function profile_tabs_settings() {
        /**
         * Filters profile menu tabs
         * 
         * @since 3.0
         * 
         * @param array $tabs The profile tabs
         */
        return apply_filters( 
            'describr_profile_tabs_config',
            array(
                'about' => array(
                    'name'   => __( 'About', 'describr' ),
                    'icon'   => 'dashicons-admin-users',
                    'subnav' => array(
                        'tagline' => array(
                            'name' => __( 'Tagline', 'describr' )
                        ),
                        'basic_info' => array(
                            'name' => __( 'Basic Info', 'describr' )
                        ),
                        'contact_and_social' => array(
                            'name' => __( 'Contacts and Social Networks', 'describr' )
                        ),
                        'addresses_and_timezone' => array(
                            'name' => __( 'Addresses and Time zone', 'describr' )
                        ),
                        'relationship_and_languages' => array(
                            'name' => __( 'Relationship and Languages', 'describr' )
                        ),
                        'work_and_education' => array(
                            'name' => __( 'Work and Education', 'describr' )
                        ),
                    ),
                ),
                'posts' => array(
                    'name' => __( 'Posts', 'describr' ),
                    'icon'  => 'dashicons-admin-post',
                ),
                'comments' => array(
                    'name' => __( 'Comments', 'describr' ),
                    'icon'     => 'dashicons-admin-comments',
                ),
            )
        );
    }
        
    /**
     * Initializes the privacy options
     * 
     * @since 3.0
     */
    public function set_privacy_choice() {
        $this->privacy_choice = describr_translated_privacy();
    }
        
    /**
     * Initializes social media fields
     * 
     * @since 3.0
     */    
    public function set_social_fields() {
        $this->social_fields = array(
            'facebook' => array(
                'title'    => __( 'Facebook', 'describr' ),
                'label'    => __( 'Facebook', 'describr' ),
                'name'     => 'facebook',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-facebook',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://facebook.com/',
            ),
            'youtube' => array(
                'title'    => __( 'YouTube', 'describr' ),
                'label'    => __( 'YouTube', 'describr' ),
                'name'     => 'youtube',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-youtube',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => array(
                    'https://youtube.com/',
                    'https://youtu.be/',
                ),
            ),
            'whatsapp' => array(
                'title' => __( 'WhatsApp', 'describr' ),
                'label' => __( 'WhatsApp Number', 'describr' ),
                'name'  => 'whatsapp',
                'type'  => 'text',
                'icon'  => 'fa-brands fa-whatsapp',
            ),
            'instagram' => array(
                'title'    => __( 'Instagram', 'describr' ),
                'label'    => __( 'Instagram', 'describr' ),
                'name'     => 'instagram',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-instagram',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://instagram.com/',
            ),
            'tiktok' => array(
                'title'    => __( 'TikTok', 'describr' ),
                'label'    => __( 'TikTok', 'describr' ),
                'name'     => 'tiktok',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-tiktok',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://tiktok.com/@',
            ),
            'snapchat' => array(
                'title'    => __( 'Snapchat', 'describr' ),
                'label'    => __( 'Snapchat', 'describr' ),
                'name'     => 'snapchat',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-square-snapchat',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://www.snapchat.com/add/',
            ),
            'pinterest' => array(
                'title'    => __( 'Pinterest', 'describr' ),
                'label'    => __( 'Pinterest', 'describr' ),
                'name'     => 'pinterest',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-square-pinterest',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://www.pinterest.com/',
                ),
            'reddit' => array(
                'title'    => __( 'Reddit', 'describr' ),
                'label'    => __( 'Reddit', 'describr' ),
                'name'     => 'reddit',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-square-reddit',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://www.reddit.com/user/',
            ),
            'linkedin' => array(
                'title'    => __( 'LinkedIn', 'describr' ),
                'label'    => __( 'LinkedIn', 'describr' ),
                'name'     => 'linkedin',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-linkedin',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://linkedin.com/',
            ),
            'x' => array(
                'title'    => __( 'X', 'describr' ),
                'label'    => __( 'X (formerly Twitter)', 'describr' ),
                'name'     => 'x',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-x-twitter',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://x.com/',
            ),
            'twitch' => array(
                'title'    => __( 'Twitch', 'describr' ),
                'label'    => __( 'Twitch', 'describr' ),
                'name'     => 'twitch',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-twitch',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://twitch.tv/',
            ),
            'telegram' => array(
                'title'    => __( 'Telegram', 'describr' ),
                'label'    => __( 'Telegram', 'describr' ),
                'name'     => 'telegram',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-telegram',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://t.me/',
            ),
            'github' => array(
                'title'    => __( 'Github', 'describr' ),
                'label'    => __( 'Github', 'describr' ),
                'name'     => 'github',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-github',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://github.com/',
            ),
            'wechat' => array(
                'title'     => __( 'WeChat', 'describr' ),
                'label'     => __( 'WeChat ID' , 'describr' ),
                'name'      => 'wechat',
                'type'      => 'text',
                'min_chars' => 6,
                'max_chars' => 20,
                'icon'      => 'fa-brands fa-weixin',
            ),
            'qq' => array(
                'title' => __( 'QQ', 'describr' ),
                'label' => __( 'QQ ID', 'describr' ),
                'name'  => 'qq',
                'type'  => 'text',
                'icon'  => 'fa-brands fa-qq',
            ),
            'douyin' => array(
                'title'    => __( 'Douyin', 'describr' ),
                'label'    => __( 'Douyin', 'describr' ),
                'name'     => 'douyin',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-tiktok',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://www.douyin.com/user/',
            ),
            'vkontakte' => array(
                'title'    => __( 'VKontakte', 'describr' ),
                'label'    => __( 'VKontakte', 'describr' ),
                'name'     => 'vkontakte',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-vk',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://vk.com/id',
            ),
            'line' => array(
                'title' => __( 'Line', 'describr' ),
                'label' => __( 'Line ID', 'describr' ),
                'name'  => 'line',
                'type'  => 'text',
                'icon'  => 'fa-brands fa-line',
            ),
            'kik' => array(
                'title' => __( 'Kik', 'describr' ),
                'label' => __( 'Kik ID', 'describr' ),
                'name'  => 'kik',
                'type'  => 'text',
                'icon'  => 'kk',
            ),
            'spotify' => array(
                'title'    => __( 'Spotify', 'describr' ),
                'label'    => __( 'Spotify', 'describr' ),
                'name'     => 'spotify',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-spotify',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://open.spotify.com/user/',
            ),
            'soundcloud' => array(
                'title'    => __( 'SoundCloud', 'describr' ),
                'label'    => __( 'SoundCloud', 'describr' ),
                'name'     => 'soundcloud',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-soundcloud',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://soundcloud.com/',
            ),
            'skype' => array(
                'title'    => __( 'Skype', 'describr' ),
                'label'    => __( 'Skype ID', 'describr' ),
                'name'     => 'skype',
                'type'     => 'text',
                'icon'     => 'fa-brands fa-skype',
                'base_url' => /*translators: First part of Skype ID.*/ __( 'live:', 'describr' ),
            ),
            'odnoklassniki' => array(
                'title'    => /*translators: Abbreviation for odnoklassniki.*/ __( 'OK', 'describr' ),
                'label'    => __( 'Odnoklassniki', 'describr' ),
                'name'     => 'odnoklassniki',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-odnoklassniki',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://ok.ru/',
            ),
            'oculus' => array(
                'title' => __( 'Oculus', 'describr' ),
                'name'  => 'oculus',
                'type'  => 'text',
                'label' => __( 'Oculus Username', 'describr' ),
                'icon'  => 'fa-solid fa-vr-cardboard',
            ),
            'kakaotalk' => array(
                'title'     => __( 'KakaoTalk', 'describr' ),
                'name'      => 'kakaotalk',
                'type'      => 'text',
                'label'     => __( 'KakaoTalk ID', 'describr' ),
                'min_chars' => 4,
                'max_chars' => 20,
                'icon'      => 'klk',
            ),
            'tumblr' => array(
                'title'    => __( 'Tumblr', 'describr' ),
                'label'    => __( 'Tumblr', 'describr' ),
                'name'     => 'tumblr',
                'type'     => 'url',
                'icon'     => 'fa-brands fa-tumblr',
                'target'   => '_blank',
                'rel'      => 'ugc nofollow me',
                'base_url' => 'https://www.tumblr.com/',
            ),
            'viber' => array(
                'title'=> __( 'Viber', 'describr' ),
                'label'=> __( 'Viber number', 'describr' ),
                'name' => 'viber',
                'type' => 'text',
                'icon' => 'fa-brands fa-viber',
            ),
            'discord' => array(
                'title'  => __( 'Discord', 'describr' ),
                'label'  => __( 'Discord ID', 'describr' ),
                'name'   => 'discord',
                'type'   => 'text',
                'icon'   => 'fa-brands fa-discord',
                'length' => 18,
            ),
        );
            
        //Add max_chars to fields without one
        foreach ( $this->social_fields as $field => &$settings ) {
            if ( ! isset( $settings['length'] ) && ! isset( $settings['max_chars'] ) ) {
                $settings['max_chars'] = 250;
            }

            $settings['spellcheck']   = false;
            $settings['autocomplete'] = false;
            $settings['required']     = false;
            $settings['editable']     = true;
            $settings['public']       = 1;
        }
            
        unset( $settings );

        /** 
         * Filters the social network fields
         * 
         * @since 3.0
         * 
         * @param array $social_fields Social network fields
         */
        $this->social_fields = (array) apply_filters( 'describr_social_fields', $this->social_fields );        
    }

    /** 
     * Initializes default form fields
     * 
     * The fields are initialized in the {@see 'init'} hook not to
     * have the gettext functions called prior
     * 
     * @since 3.0
     */
    public function default_fields_init() {
        /** 
         * Fires before initialization of the default fields
         * 
         * @since 3.0
         */
        do_action( 'describr_pre_default_fields_init' );
            
        $this->asides = array(
            'name'          => array(
                'fields' => array( 
                    'first_name', 
                    'last_name', 
                    'nickname',
                ),
                'asides'  => $this->get( 'asides' ),
            ),
            'addresses'     => array( 
                'fields' => array( 
                    'current_city', 
                    'hometown', 
                    'lived_cities', 
                ),
                'asides'  => $this->get( 'asides' ),
            ),
            'langs'         => array( 
                'fields' => array( 
                    'locale', 
                    'locales', 
                ), 
                'asides'  => $this->get( 'asides' ), 
            ),
            'phone_numbers' => array( 
                'fields' => array( 
                    'mobile_number', 
                    'home_number', 
                    'work_number', 
                ), 
                'asides' => $this->get( 'asides' ), 
            ),
            'social_media'  => array( 
                'fields' => array_keys( $this->social_fields ), 
                'asides'  => $this->get( 'asides' ),
            ),
            'education' => array(
                'fields' => array(
                    'high_school', 
                    'college', 
                ),
                'asides'  => $this->get( 'asides' ),
            ),
        );
            
        $this->supports = array(
            'city' => array(
                'title'        => /*translators: Field title.*/ _x( 'City', 'user', 'describr' ),
                'label'        => /*translators: Field label.*/ _x( 'City', 'user', 'describr' ),
                'type'         => 'text',
                'name'         => 'city',
                'max_chars'    => 250,
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'moved' => array(
                'title'        => /*translators: Field title.*/ _x( 'Moved', 'date', 'describr' ),
                'label'        => /*translators: Field label.*/ _x( 'Moved', 'date', 'describr' ),
                'type'         => 'date',
                'name'         => 'moved',
                'autocomplete' => false,
                'spellcheck'   => false,
                'required'     => false,
            ),
            'status' => array(
                'title'        => /*translators: Field title.*/ _x( 'Status', 'intimate relationship', 'describr' ),
                'label'        => /*translators: Field label.*/ _x( 'Status', 'intimate relationship', 'describr' ),
                'type'         => 'select',
                'name'         => 'status',
                'options'      => describr_translated_relationship_status(),
                'autocomplete' => false,
                'spellcheck'   => false,
                'required'     => false,
            ),
            'partner' => array(
                'title'        => /*translators: Field title.*/ _x( 'Partner', 'intimate relationship', 'describr' ),
                'label'        => /*translators: Field label.*/ _x( 'Partner', 'intimate relationship', 'describr' ),
                'type'         => 'text',
                'name'         => 'partner',
                'max_chars'    => 250,
                'suggest'      => true,
                'autocomplete' => false,
                'spellcheck'   => false,
                'required'     => false,
            ),
            'partner_id' => array(
                'type'         => 'hidden',
                'name'         => 'partner_id',
                'autocomplete' => false,
                'spellcheck'   => false,
                'required'     => false,
            ),
            'since' => array(
                'title'        => /*translators: Field title.*/ _x( 'Since', 'date', 'describr' ),
                'label'        => /*translators: Field label.*/ _x( 'Since', 'date', 'describr' ),
                'type'         => 'date',
                'name'         => 'since',
                'autocomplete' => false,
                'spellcheck'   => false,
                'required'     => false,
            ),
            'number'    => array(
                'label'        => /*translators: Field title.*/ _x( 'Number', 'phone', 'describr' ),
                'label'        => /*translators: Field label.*/ _x( 'Number', 'phone', 'describr' ),
                'type'         => 'tel',
                'name'         => 'number',
                'required'     => true,
                'spellcheck'   => false,
                'autocomplete' => 'tel-national',
                'max_chars'    => 250,
            ),
            'country' => array(
                'title'        => /*translators: Field title.*/ __( 'Country', 'describr' ),
                'label'        => /*translators: Field label.*/ __( 'Country', 'describr' ),
                'type'         => 'select',
                'name'         => 'country',
                'required'     => true,
                'spellcheck'   => false,
                'autocomplete' => 'country',
                'options'      => $this->get( 'countries' ),
                'length'       => 2,
            ),
            'ext' => array(
                'title'        => /*translators: Field title. Abbreviation for telephone number extension.*/ _x( 'Ext.', 'telephone', 'describr' ),
                'label'        => /*translators: Field label. Abbreviation for telephone number extension.*/ _x( 'Ext.', 'telephone', 'describr' ),
                'type'         => 'number',
                'name'         => 'ext',
                'required'     => false,
                'spellcheck'   => false,
                'autocomplete' => 'tel-extension',
                'max_chars'    => 20,
            ),
            'company' => array(
                'title'        => /*translators: Field title.*/ _x( 'Company', 'organization', 'describr' ),
                'label'        => /*translators: Field label.*/ _x( 'Company', 'organization', 'describr' ),
                'type'         => 'text',
                'name'         => 'company',
                'required'     => true,
                'autocomplete' => 'organization',
                'spellcheck'   => false,
                'max_chars'    => 250,
            ),
            'title' => array(
                'title'        => /*translators: Field title.*/ _x( 'Title', 'job', 'describr' ),
                'label'        => /*translators: Field label.*/ _x( 'Title', 'job', 'describr' ),
                'type'         => 'text',
                'name'         => 'title',
                'required'     => false,
                'autocomplete' => 'organization-title',
                'spellcheck'   => false,
                'max_chars'    => 250,
            ),
            'description' => array(
                'title'        => /*translators: Field title.*/ __( 'Description', 'describr' ),
                'label'        => /*translators: Field label.*/ __( 'Description', 'describr' ),
                'type'         => 'textarea',
                'name'         => 'description',
                'required'     => false,                    
                'autocomplete' => false,
                'spellcheck'   => false,
                'max_chars'    => 500,
                'html'         => true,
            ),
            'present' => array(
                'label'        => /*translators: Field label.*/ _x( 'I still work here', 'organization', 'describr' ),
                'type'         => 'checkbox',
                'name'         => 'present',
                'required'     => false,
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'from' => array(
                'label'        => /*translators: Field label.*/ _x( 'From', 'date', 'describr' ),
                'type'         => 'date',
                'name'         => 'from',
                'required'     => false,
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'to' => array(
                'label'        => /*translators: Field label.*/ _x( 'To', 'date', 'describr' ),
                'type'         => 'date',
                'name'         => 'to',
                'required'     => false,
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'school' => array(
                'label'        => /*translators: Field label.*/ __( 'School', 'describr' ),
                'name'         => 'school',
                'type'         => 'text',
                'required'     => true,
                'autocomplete' => false,
                'spellcheck'   => false,
                'max_chars'    => 250,
            ),
            'graduated' => array(
                'label'        => /*translators: Field label.*/ _x( 'Graduated', 'school', 'describr' ),
                'name'         => 'graduated',
                'type'         => 'checkbox',
                'required'     => false,
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'major' => array(
                'title'        => /*translators: Field title. A specific subject area in which college students earn a degree.*/_x( 'Major', 'college', 'describr' ),
                'label'        => /*translators: Field label. A specific subject area in which college students earn a degree.*/_x( 'Major', 'college', 'describr' ),
                'name'         => 'major',
                'type'         => 'text',
                'required'     => false,
                'autocomplete' => false,
                'spellcheck'   => false,
                'max_chars'    => 250,
            ),
            'minor' => array(
                'title'        => /*translators: Field title. A secondary specialization that students choose in addition to their major.*/ _x( 'Minor', 'college', 'describr' ),
                'label'        => /*translators: Field label. A secondary specialization that students choose in addition to their major.*/ _x( 'Minor', 'college', 'describr' ),
                'name'         => 'minor',
                'type'         => 'text',
                'required'     => false,
                'autocomplete' => false,
                'spellcheck'   => false,
                'max_chars'    => 250,
            ),
            'degree' => array(
                'title'        => /*translators: Field title.*/ _x( 'Degree', 'academic', 'describr' ),
                'label'        => /*translators: Field label.*/ _x( 'Degree', 'academic', 'describr' ),
                'name'         => 'degree',
                'type'         => 'text',
                'required'     => false,
                'autocomplete' => false,
                'spellcheck'   => false,
                'max_chars'    => 250,
            ),
        );
        
        $account_fields_priv = $this->privacy_choice;
        $account_fields_priv['public'] = __( 'Everyone', 'describr' );
        
        /**
         * Filters the fields' supports
         * 
         * @since 3.0
         * 
         * @param array $supports Fields' supports
         */
        $this->supports = apply_filters( 'describr_field_supports', $this->supports );
            
        $this->default_fields = array(
            'user_email' => array(
                'title'        => __( 'Email Address', 'describr' ),
                'name'         => 'user_email',
                'type'         => 'email',
                'label'        => __( 'Email Address', 'describr' ),
                'required'     => true,
                'public'       => 1,
                'editable'     => true,
                'icon'         => 'dashicons dashicons-email',
                'asides'       => $this->get( 'asides' ),
                'autocomplete' => 'email',
                'spellcheck'   => false,
            ),
            //external url
            'user_url' => array(
                'title'        => __( 'Website', 'describr' ),
                'name'         => 'user_url',
                'type'         => 'url',
                'label'        => __( 'Website', 'describr' ),
                'required'     => false,
                'emptyable'    => true,
                'public'       => 1,
                'editable'     => true,
                'icon'         => 'dashicons dashicons-admin-links',
                'max_chars'    => 100,
                'target'       => '_blank',
                'rel'          => 'ugc nofollow me',
                'asides'       => $this->get( 'asides' ),
                'autocomplete' => 'url',
                'spellcheck'   => false,
            ),
            //Always public
            'display_name' => array(
                'title'        => __( 'Display Name', 'describr' ),
                'label'        => __( 'Display Name', 'describr' ),
                'name'         => 'display_name',
                'type'         => 'text',
                'required'     => true,
                'public'       => 1,
                'editable'     => true,
                'max_chars'    => 250,
                'asides'       => $this->get( 'audit' ),
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            //Always public
            'user_nicename'    => array(
                'title'        => __( 'Nicename', 'describr' ),
                'label'        => __( 'Nicename', 'describr' ),
                'type'         => 'text',
                'name'         => 'user_nicename',
                'required'     => true,
                'public'       => 1,
                'editable'     => true,
                'max_chars'    => 50,
                'asides'       => $this->get( 'audit' ),
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'nickname' => array(
                'title'        => __( 'Nickname', 'describr' ),
                'name'         => 'nickname',
                'type'         => 'text',
                'label'        => __( 'Nickname', 'describr' ),
                'required'     => true,
                'public'       => 1,
                'editable'     => true,
                'max_chars'    => 250,
                'autocomplete' => 'nickname',
                'spellcheck'   => false,
            ),
            'first_name' => array(
                'title'        => __( 'First Name', 'describr' ),
                'name'         => 'first_name',
                'type'         => 'text',
                'label'        => __( 'First Name', 'describr' ),
                'required'     => false,
                'emptyable'    => true,
                'public'       => 1,
                'editable'     => true,
                'max_chars'    => 250,
                'autocomplete' => 'given-name',
                'spellcheck'   => false,
            ),
            'last_name' => array(
                'title'        => __( 'Last Name', 'describr' ),
                'name'         => 'last_name',
                'type'         => 'text',
                'label'        => __( 'Last Name', 'describr' ),
                'required'     => false,
                'emptyable'    => true,
                'public'       => 1,
                'editable'     => true,
                'max_chars'    => 250,
                'autocomplete' => 'family-name',
                'spellcheck'   => false,
            ),
            'user_login' => array(
                'title'    => __( 'Username', 'describr' ),
                'label'    => __( 'Username', 'describr' ),
                'type'     => 'text',
                'public'   => 1,
                'editable' => false,
                'required' => true,
                'asides'   => $this->get( 'asides' ),
            ),
            //URL for profile page
            'profile_url' => array(
                'title'    => __( 'Profile URL', 'describr' ),
                'label'    => __( 'Profile URL', 'describr' ),
                'public'   => 1,
                'editable' => false,
                'icon'     => 'dashicons dashicons-admin-site-alt3',
            ),
            //Change the value of the name field, not this key: profile_photo
            'profile_photo' => array(
                'title'    => __( 'Profile Picture', 'describr' ),
                'label'    => __( 'Profile Picture', 'describr' ),
                'type'     => 'image',
                'name'     => 'profile_photo',
                'required' => false,
                'public'   => 1,
                'editable' => true,
                'asides'   => $this->get( 'asides' ),
            ),
            'user_registered' => array(
                'title'    => __( 'Registration Date', 'describr' ),
                'label'    => __( 'Registration Date', 'describr' ),
                'type'     => 'date',
                'public'   => 1,
                'editable' => false, 
            ),
            'user_status' => array(
                'public' => 0,
            ),
            'user_activation_key' => array(
                'public' => 0,
            ),
            'description' => array(
                'title'        => /*translators: Field title. Three-letter abbreviation of biography.*/ __( 'Bio', 'describr' ),
                'name'         => 'description',
                'type'         => 'textarea',
                'label'        => /*translators: Field label. Three-letter abbreviation of biography.*/ __( 'Bio' ,'describr' ),
                'required'     => false,
                'emptyable'    => true,
                'public'       => 1,
                'editable'     => true,
                'max_chars'    => 500,
                'html'         => true,
                'asides'       => $this->get( 'asides' ),
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'locale' => array(
                'title'        => __( 'Primary Language', 'describr' ),
                'name'         => 'locale',
                'type'         => 'select',
                'label'        => __( 'Primary Language', 'describr' ),
                'required'     => false,
                'emptyable'    => true,
                'public'       => 1,
                'editable'     => true,
                'default'      => get_user_locale(),
                'options'      => $this->get( 'langs' ),
                'icon'         => 'dashicons dashicons-translation',
                'autocomplete' => 'language',
                'spellcheck'   => false,
            ),
            'locales' => array(
                'title'        => __( 'Other Languages', 'describr' ),
                'name'         => 'locales',
                'type'         => 'text',
                'label'        => __( 'Other Languages', 'describr' ),
                'required'     => false,
                'public'       => 1,
                'editable'     => true,
                'options'      => $this->get( 'langs' ),
                'icon'         => 'dashicons dashicons-translation',
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            //profile
            'timezone' => array(
                'title'        => __( 'Time Zone', 'describr' ),
                'name'         => 'timezone',
                'type'         => 'select',
                'label'        => __( 'Time Zone', 'describr' ),
                'required'     => false,
                'public'       => 1,
                'editable'     => true,
                'options'      => timezone_identifiers_list(),
                'icon'         => 'dashicons dashicons-clock',
                'asides'       => $this->get( 'asides' ),
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'tagline' => array(
                'title'        => _x( 'Tagline', 'slogan','describr' ),
                'name'         => 'tagline',
                'type'         => 'text',
                'label'        => _x( 'Tagline', 'slogan', 'describr' ),
                'required'     => false,
                'public'       => 1,
                'editable'     => true,
                'max_chars'    => 20,
                'icon'         => 'dashicons dashicons-editor-quote',
                'asides'       => $this->get( 'asides' ),
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'gender' => array(
                'title'        => __( 'Gender', 'describr' ),
                'name'         => 'gender',
                'type'         => 'select',
                'label'        => __( 'Gender', 'describr' ),
                'required'     => false,
                'public'       => 1,
                'editable'     => true,
                'asides'       => $this->get( 'asides' ),
                'autocomplete' => 'sex',
                'spellcheck'   => false,
                'options'      => describr_translated_gender(),
                'icons'        => array(
                    'male'   => 'dashicons dashicons-businessman',
                    'female' => 'dashicons dashicons-businesswoman',
                ),
            ),
            'birthdate' => array(
                'title'        => __( 'Birthdate', 'describr' ),
                'name'         => 'birthdate',
                'type'         => 'date',
                'label'        => __( 'Birthdate', 'describr' ),
                'required'     => false,
                'public'       => 1,
                'editable'     => true,
                'icon'         => 'dashicons dashicons-buddicons-community',
                'asides'       => $this->get( 'asides' ),
                'autocomplete' => 'off',
                'spellcheck'   => false,
            ),
            'current_city' => array(
                'title'        => __( 'Current City', 'describr' ),
                'name'         => 'current_city',
                'type'         => 'text',
                'label'        => __( 'Current City', 'describr' ),
                'required'     => false,
                'public'       => 1,
                'editable'     => true,
                'max_chars'    => 250,
                'icon'         => 'dashicons dashicons-location',
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'hometown' => array(
                'title'        => __( 'Hometown', 'describr' ),
                'name'         => 'hometown',
                'type'         => 'text',
                'label'        => __( 'Hometown', 'describr' ),
                'required'     => false,
                'public'       => 1,
                'editable'     => true,
                'max_chars'    => 250,
                'icon'         => 'dashicons dashicons-location',
                'autocomplete' => false,
                'spellcheck'   => false,
            ),
            'lived_cities' => array(
                'title'     => __( 'Cities Lived', 'describr' ),
                'label'     => __( 'Cities Lived', 'describr' ),
                'name'      => 'lived_cities',
                'type'      => 'array',
                'required'  => false,
                'has_hash'  => true,
                'public'    => 1,
                'editable'  => true,
                'icon'      => 'dashicons dashicons-location',
                'supports'  => array( 
                    'city', 
                    'moved', 
                ),
            ),
            'relationship' => array(
                'title'     => /*translators: Field title. Intimate relationship.*/ __( 'Relationship', 'describr' ),
                'label'     => /*translators: Field label. Intimate relationship.*/ __( 'Relationship', 'describr' ),
                'name'      => 'relationship',
                'type'      => 'array',
                'required'  => false,
                'public'    => 1,
                'editable'  => true,
                'icon'      => 'dashicons dashicons-heart',
                'asides'    => $this->get( 'asides' ),
                'supports'  => array( 
                    'status', 
                    'partner', 
                    'partner_id', 
                    'since', 
                ),
            ),
            'mobile_number' => array(
                'title'     => /*translators: Field title. Mobile phone number.*/ __( 'Mobile Number', 'describr' ),
                'label'     => /*translators: Field label. Mobile phone number.*/ __( 'Mobile Number', 'describr' ),
                'name'      => 'mobile_number',
                'type'      => 'array',
                'public'    => 1,
                'required'  => false,
                'editable'  => true,
                'icon'      => 'dashicons dashicons-phone',
                'supports'  => array( 
                    'country',
                    'number',  
                ),
            ),
            'work_number' => array(
                'title'     => /*translators: Field title. Work phone number.*/ __( 'Work Number', 'describr' ),
                'label'     => /*translators: Field label. Work phone number.*/ __( 'Work Number', 'describr' ),
                'name'      => 'work_number',
                'type'      => 'array',
                'public'    => 1,
                'required'  => false,
                'editable'  => true,
                'icon'      => 'dashicons dashicons-phone',
                'supports'  => array( 
                    'country',
                    'number', 
                    'ext', 
                ),
            ),
            'home_number' => array(
                'title'     => /*translators: Field title. Home phone number.*/ __( 'Home Number', 'describr' ),
                'label'     => /*translators: Field label. Home phone number.*/ __( 'Home Number', 'describr' ),
                'name'      => 'home_number',
                'type'      => 'array',
                'public'    => 1,
                'required'  => false,
                'editable'  => true,
                'icon'      => 'dashicons dashicons-phone',
                'supports'  => array( 
                    'country',
                    'number', 
                    'ext', 
                ),
            ),
            'work_history' => array(
                'title'    => __( 'Work History', 'describr' ),
                'label'    => __( 'Work History', 'describr' ),
                'name'     => 'work_history',
                'type'     => 'array',
                'public'   => 1,
                'required' => false,
                'has_hash' => true,
                'editable' => true,
                'asides'   => $this->get( 'asides' ),
                'icon'     => 'dashicons dashicons-hammer',
                'supports' => array( 
                    'company', 
                    'title', 
                    'city',
                    'description', 
                    'present',
                    'from',
                    'to',
                ),
            ),
            'high_school' => array(
                'title'     => __( 'High School', 'describr' ),
                'label'     => __( 'High School', 'describr' ),
                'name'      => 'high_school',
                'type'      => 'array',
                'public'    => 1,
                'required'  => false,
                'has_hash'  => true,
                'editable'  => true,
                'icon'      => 'dashicons dashicons-welcome-learn-more',
                'supports' => array( 
                    'school', 
                    'description', 
                    'graduated',
                    'from',
                    'to',
                ),
            ),
            'college' => array(
                'title'    => __( 'College', 'describr' ),
                'label'    => __( 'College', 'describr' ),
                'name'     => 'college',
                'type'     => 'array',
                'public'   => 1,
                'required' => false,
                'has_hash' => true,
                'editable' => true,
                'icon'     => 'dashicons dashicons-welcome-learn-more',
                'supports' => array( 
                    'school', 
                    'major',
                    'minor',
                    'degree',
                    'description', 
                    'graduated',
                    'from',
                    'to',
                ),
            ),
            //Admin
            'important' => array(
                'title'     => __( 'Important Person', 'describr' ),
                'name'      => 'important',
                'label'     => __( 'Important Person', 'describr' ),
                'public'    => 0,
                'editable'  => true,
                'icon'      => 'dashicons dashicons-superhero',
            ),
            'account_status' => array(
                'title'     => __( 'User Status', 'describr' ),
                'name'      => 'account_status',
                'label'     => __( 'User Status', 'describr' ),
                'public'    => 0,
                'editable'  => true,
                'icon'      => 'dashicons dashicons-post-status',
            ),
            'describr_confirmation_key' => array(
                'name'      => 'describr_confirmation_key',
                'public'    => 0,
                'editable'  => true,
            ),
            /*This usermeta field stores the Unix timestamp when the user logged in*/
            'describr_time_last_login' => array(
                'name'          => 'describr_time_last_login',
                'required'      => 1,
                /*This will merely prevent the user's editing the field, which is updated in a `wp_login` action documented in wp-content/plugins/describr/includes/class-user.php*/
                'public'        => 0,
                'editable'      => true,
            ),
            /***Account page fields***/
            'receive_notifications' => array(
                'label'        => __( 'Send Me Email Notifications', 'describr' ),
                'name'         => 'receive_notifications',
                'type'         => 'checkbox',
                'required'     => false,
                'default'      => 1,
                'public'       => 1,
                'editable'     => true,
                'account_only' => true,
            ),
            'show_join_date' => array(
                /*translators: %s: Site's name.*/
                'legend'        => sprintf( __( 'Who can see when I joined %s', 'describr' ), get_bloginfo( 'name', 'display' ) ),
                'name'          => 'show_join_date',
                'type'          => 'radio',
                'required'      => false,
                'public'        => 1,
                'editable'      => true,
                'default'       => 'public',
                'options'       => $account_fields_priv,
                'account_only'  => true,
            ),
            /*This feature controls displaying the actual time ($time_last_login field) when the profile user last logged in*/
            'when_last_login_audience' => array(
                'legend'        => __( 'Who can see when I last logged in', 'describr' ),
                'name'          => 'when_last_login_audience',
                'type'          => 'radio',
                'required'      => true,
                'public'        => 1,
                'editable'      => true,
                'default'       => 'public',
                'options'       => $account_fields_priv,
                'account_only'  => true,
            ),
            /*This feature controls displaying the logged-in icon when the profile user is currently logged in*/
            'show_login' => array(
                'legend'        => __( 'Who can see that I&#039;m logged in', 'describr' ),
                'name'          => 'show_login',
                'type'          => 'radio',
                'required'      => true,
                'public'        => 1,
                'editable'      => true,
                'default'       => 'public',
                'options'       => $account_fields_priv,
                'account_only'  => true,
            ),
            'show_profile' => array(
                'legend'        => __( 'Who can see my profile', 'describr' ),
                'name'          => 'show_profile',
                'type'          => 'radio',
                'required'      => true,
                'public'        => 1,
                'editable'      => true,
                'default'       => 'public',
                'options'       => $account_fields_priv,
                'account_only'  => true,
            ),
            'receive_message' => array(
                'legend'       => __( 'Who can send me messages', 'describr' ),
                'name'         => 'receive_message',
                'type'         => 'radio',
                'required'     => true,
                'public'       => 1,
                'editable'     => true,
                'default'      => 'public',
                'options'      => describr_translated_message_privacy(),
                'account_only' => true,
            ),
            'logout_everywhere' => array(
                'legend'        => __( 'What happens to my active sessions when I log out', 'describr' ),
                'name'          => 'logout_everywhere',
                'type'          => 'checkbox',
                'label'         => __( 'Log Me Out Everywhere', 'describr' ),
                'required'      => false,
                'public'        => 1,
                'editable'      => true,
                'account_only'  => true,
            ),
            'user_pass'    => array(   
                'title'        => __( 'Password', 'describr' ),                 
                'name'         => 'user_pass',
                'type'         => 'password',
                'label'        => __( 'Password', 'describr' ),
                'required'     => true,
                'editable'     => true,
                'spellcheck'   => false,
                'confirm'      => ! empty( describr_get_network_option( 'describr_confirm_password' ) ),
                'strong'       => ! empty( describr_get_network_option( 'describr_require_strong_password' ) ),
                'toggle'       => true,
                'autocomplete' => 'on',
                'public'       => 1,
                'min_chars'    => (int) describr_get_network_option( 'describr_password_min_chars' ),
                'max_chars'    => (int) describr_get_network_option( 'describr_password_max_chars' ),
                'asides'       => $this->get( 'audit' ),
                'account_only' => true,
            ),
            'single_user_pass' => array(
                'title'        => __( 'Current Password', 'describr' ),
                'name'         => 'single_user_pass',
                'type'         => 'password',
                'label'        => __( 'Current Password', 'describr' ),
                'required'     => true,
                'editable'     => true,
                'spellcheck'   => false,
                'toggle'       => true,
                'autocomplete' => 'current-password',
                'public'       => 1,
                'account_only' => true,
            ),
        );
            
        /** 
         * Filters default fields
         * 
         * @since 3.0
         * 
         * @param array $default_fields Default fields
         */
        $this->default_fields = apply_filters( 'describr_default_fields', $this->default_fields + $this->social_fields );

        $this->set_disabled_fields();
    }
        
    /**
     * Retrieves whether a field exists
     * 
     * @since 3.0
     * 
     * @param string $field The field to verify
     * @return bool True if the field exist, otherwise false
     */
    public function field_exists( $field ) {
        return isset( $this->default_fields[ $field ] ) && empty( $this->default_fields[ $field ]['disabled_by_admin'] );
    }

    /**
     * Retrieves a field's settings
     * 
     * @since 3.0
     * 
     * @param string $field The field
     * @return array The field's settings
     */
    public function get_field( $field ) {
        if ( isset( $this->default_fields[ $field ] ) ) {
            return (array) $this->default_fields[ $field ];
        }

        return array();
    }

    /**
     * Retrieves field (s) settings
     * 
     * @since 3.0
     * 
     * @param string|array|false $field The field key, 
     *                                  an array of field keys,
     *                                  or false for all fields
     * @return array The field (s) settings
     */
    public function get_fields( $fields = array() ) {
        $arr = array();
            
        if ( is_string( $fields ) ) {
            return $this->get_field( $fields );
        } elseif ( ! $fields ) {
            return $this->default_fields;
        }

        foreach ( $fields as $field ) {
            $arr[ $field ] = $this->get_field( $field );
        }

        return $arr;
    }
        
    /**
     * Retrieves social networks fields
     * 
     * @since 3.0
     * 
     * @return array
     */
    public function get_socials() {
        return array_intersect_key( $this->default_fields, $this->social_fields );
    }

    /**
     * Retrieves authorized fields
     * 
     * @since 3.0
     * 
     * @param int                $user_id ID of the user to whom the fields belong   
     * @param false|string|array $pluck   Fields to authorize
     * @return array An array of authorized vields
     */
    public function get_viewable_fields( $user_id, $pluck = false ) {
        $fields = array_diff_key( $this->default_fields, array_flip( describr()->user()->wp_users ) );

        $allowed_wp_users_fields = array_intersect_key( $this->default_fields, array_flip( describr()->user()->viewable_wp_users ) );

        $fields = $fields + $allowed_wp_users_fields;
        
        if ( $pluck ) {
            if ( ! is_array( $pluck ) ) {
                $pluck = explode( ',', $pluck );
            }
            
            $pluck = array_flip( $pluck );
            $fields = array_intersect_key( $fields, $pluck );
            $fields = array_merge( $pluck, $fields );
        }
        
        unset( $fields['ID'] );
          
        $allowed_fields = array();
        
        foreach ( array_keys( $fields ) as $field ) {
            if ( ! describr_is_field_viewable( $field, $user_id ) ) {
                continue;
            }
                
            $allowed_fields[] = $field;
        }

        return $allowed_fields;
    }

    /**
     * Retrieves the field supports
     * 
     * @since 3.0
     * 
     * @param null|array  $supports The field supports to retrieve
     * @param null|string $field    The field to whom the supports belong
     */
    public function get_field_supports( $supports = null, $field = null ) {
        $supports_ = array();

        if ( empty( $supports ) ) {
            /*This filter is documented in wp-content/plugins/describr/includes/class-config.php*/
            return apply_filters( 'describr_get_field_supports', $supports_, $field );
        }

        foreach ( (array) $supports as $support ) {
            if ( isset( $this->supports[ $support ] ) ) {
                $supports_[ $support ] = $this->supports[ $support ];
            }
        }
            
        /**
         * Filters the field's subfields
         * 
         * @since 3.0
         * 
         * @param array       $supports_ The field's subfields
         * @param null|string $field     The field to which the subfields belong
         */
        return apply_filters( 'describr_get_field_supports', $supports_, $field );
    }
        
    /**
     * Retrieves asides
     * 
     * @since 3.0
     * 
     * @return array Asides
     */
    public function get_asides() {
        $asides = array();
            
        //Flip the asides so that the fields are keys and the respective values are arrays containing the category and types of asides
        foreach ( $this->asides as $cat => $arr ) {
            if ( ! empty( $arr['fields'] ) && ! empty( $arr['asides'] ) ) {
                $_asides = $arr['asides'];
                $_asides['_cat'] = $cat;
                $asides += array_fill_keys( $arr['fields'], $_asides );
            }
        }
        
        foreach ( $this->default_fields as $field => $settings ) {
            if ( ! isset( $asides[ $field ] ) && ! empty( $settings['asides'] ) ) {
                $asides[ $field ] = $settings['asides'];
            }
        }

        $asides = array_intersect_key( $asides, $this->default_fields );
            
        return $asides;
    }
        
    /**
     * Retrieves a field's asides
     * 
     * @since 3.0
     * 
     * @return array The field's asides
     */
    public function get_field_asides( $field ) {
        $asides = $this->get_asides();

        if ( ! empty( $asides[ $field ] ) ) {
            return $asides[ $field ];
        }

        return array();
    }

    /**
     * Retrieves fields that are in the same asides' category
     * as that of the passed field
     * 
     * @since 3.0
     * 
     * @param string        Field
     * @return array|string The field if it doesn't share asides' category,
     *                      an array of fields sharing the same asides' category
     *                      as the passed field, or the field if no asides are found
     */
    public function get_fields_by_aside( $field ) {
        $settings = $this->get_field( $field );

        if ( isset( $settings['asides'] ) ) {
            return $field;
        }

        foreach ( $this->asides as $cat => $arr ) {
            if ( ! empty( $arr['fields'] ) && in_array( $field, $arr['fields'], true ) ) {
                return $arr['fields'];
            }
        }

        return $field;
    }
        
    /**
     * Retrieves configuration data
     * 
     * @since 3.0
     * 
     * @param string $type The type of data to retrieve
     * @return mixed The data
     */
    public function get( $type ) {
        switch ( $type ) {
            case 'countries':
                $val = describr_translated_countries();
                break;
            case 'langs':
                $val = describr_translated_locale();
                break;
            case 'asides':
                $val = $this->asides_settings;
                break;
            case 'privacy':
                $aside = $this->get( 'asides' );

                unset( $aside['status'], $aside['audit'] );

                $val = $aside;
                break;
            case 'status':
                $aside = $this->get( 'asides' );

                unset( $aside['privacy'], $aside['audit'] );
                    
                $val = $aside;
                break;
            case 'audit':
                $aside = $this->get( 'asides' );

                unset( $aside['privacy'], $aside['status'] );

                $val = $aside;
                break;
            default:
                $val = '';
                break;
        }

        /**
         * Filters the configuration data
         * 
         * @since 3.0
         * 
         * @param mixed  $val  Configuration data
         * @param string $type The type of data
         */
        return apply_filters( 'describr_config_data', $val, $type );
    }
        
    /**
     * Disables fields per admin
     * 
     * @since 3.0
     */
    public function set_disabled_fields() {
        $enabled_fields = get_option( 'describr_fields', array() );
        $is_social_enabled = ! empty( get_option( 'describr_enable_social_media' ) );

        foreach ( $this->default_fields as $field => &$settings ) {
            if ( ! in_array( $field, $enabled_fields, true ) || ( isset( $this->social_fields[ $field ] ) && ! $is_social_enabled ) ) {
                $settings['disabled_by_admin'] = true;
            }
        }
            
        unset($settings);
    }
        
    /**
     * Removes a blacklisted field
     * 
     * @since 3.0
     * 
     * @param string $field The field to check
     * @return string The field if it's not blacklisted, otherwise empty string
     */
    public function is_blacklisted_field( $field ) {
        if ( preg_match( '/^manage[a-zA-Z_0-9-]+columnshidden$/', $field ) || preg_match( '/^[a-zA-Z_0-9-]+_per_page$/', $field ) ) {
            return true;
        }
            
        foreach ( $this->get_blacklisted_fields() as $val ) {
            if ( preg_match( "/^$val\$/", $field ) ) {
                return true;
            }
        }
            
        return false;
    }

    /**
     * Retrieves blacklisted fields in valid regex format
     * 
     * We allow additional default fields to be added 
     * by the {@see 'describr_default_fields'} filter,
     * but certains blacklisted fields can never be 
     * change by the user on the frontend
     * 
     * @since 3.0
     * 
     * @return array An array of blacklisted fields
     */
    private function get_blacklisted_fields() {
        static $blacklist = null;

        if ( null !== $blacklist ) {
            return $blacklist;
        }

        $blacklist = array(
            'ID',
            'user_registered',
            'user_activation_key',
            'user_status',
            'roles',
            'role',
            'data',
            'caps',
            'cap_key',
            'allcaps',
            'filter',
            'site_id',
            'back_compat_keys',
            'session_tokens',
            'dismissed_wp_pointers',
            '_new_email',
            'spam',
            'show_admin_bar_front',
            'use_ssl',
            'admin_color',
            'comment_shortcuts',
            'syntax_highlighting',
            'rich_editing',
            'dismissed_wp_pointers',
            'show_welcome_panel',
            'metaboxhidden_page',
            'enable_custom_fields',
            'metaboxhidden_nav-menus',
            'nav_menu_recently_edited',
            'edit_page_per_page',
            'edit_post_per_page',
            'edit_comments_per_page',
            'managenav-menuscolumnshidden',
            'reset_password',
            'default_password_nag',
            'community-events-location',
        );

        $usermata_suffixes = 'persisted_preferences,dashboard_quick_press_last_post_id,user-settings,user-settings-time,user_level,capabilities';

        $site_usermeta = explode( ',', $usermata_suffixes );
            
        global $wpdb;
        
        foreach ( $site_usermeta as $suffix ) {
            $blacklist[] =  $wpdb->base_prefix . $suffix;
        }

        if ( is_multisite() ) {
            $blacklist[] = 'source_domain';
            $blacklist[] = 'primary_blog';

            foreach ( get_sites() as $site ) {
                $blog_prefix = $wpdb->get_blog_prefix( $site->blog_id );
                
                if ( $wpdb->base_prefix === $blog_prefix ) {
                    continue;
                } 

                foreach ( $site_usermeta as $suffix ) {
                    $blacklist[] =  $blog_prefix . $suffix;
                }
            }
        }
        
        //Necessary to escape characters like "-"
        $blacklist = array_map( 'preg_quote', $blacklist );
        
        foreach ( array( 'closedpostboxes', 'metaboxhidden', 'meta-box-order', 'screen_layout', ) as $field ) {
            $blacklist[] = preg_quote( $field . '_' ) . '.';
        }
        
        $blacklist[] = 'level_[0-9]*';
        
        /**
         * Filters the fields blacklist
         * 
         * Fields added to the list should be valid
         * regular expressions
         * 
         * @since 3.0
         * 
         * @param array $blacklist The blacklisted fields
         */
        return apply_filters( 'describr_fields_blacklist', $blacklist );
    }
        
    /**
     * Initializes editable profile fields
     * 
     * @since 3.0
     * 
     * @param Profile $profile The \describr\Profile object
     */
    public function editable_fields_init() {
        foreach ( $this->default_fields as $field => $settings ) {
            if ( ! is_array( $settings ) /*Field settings don't exist*/
                || empty( $settings['editable'] ) /*Field marked as not editable*/
                || ( empty( $settings['public'] ) && ! current_user_can( 'manage_options' ) ) /*Private fields can only be editted by admins*/
                || ! empty( $settings['disabled_by_admin'] ) /*Disabled by admin*/
                || ! array_key_exists( 'name', $settings ) /*An editable field must have a name*/
                || ! array_key_exists( 'type', $settings ) /*An editable field must have a type*/
                || ! empty( $settings['account_only'] )/*Account only field*/
            ) {
                continue;
            }
                
            $field_ = trim( sanitize_key( $field ) );
                                
            if ( ! $field_ || $field !== $field_ || $this->is_blacklisted_field( $field ) ) {
                continue;
            }
                
            $name = trim( sanitize_key( $settings['name'] ) );
                
            if ( $name !== $settings['name'] ) {
                continue;
            }

            $this->editable_fields[ $field ] = $settings;
        }
    }
    
    /**
     * Initializes the asides settings for fields
     * 
     * @since 3.0
     */
    public function fields_asides_settings_init() {
        /**
         * Filters the fields asides settings
         * 
         * @since 3.0
         * 
         * @param array $asides_settings Fields asides settings
         */
        $this->asides_settings = apply_filters( 
            'describr_fields_asides_settings',
            array(
                'privacy' => array(
                    'title'        => __( 'Audience', 'describr' ),
                    'label'        => __( 'Audience', 'describr' ),
                    'name'         => 'privacy',
                    'type'         => 'radio',//and select
                    'default'      => 'public',
                    'editable'     => true,
                    'required'     => true,
                    'spellcheck'   => false,
                    'autocomplete' => 'off',
                    'public'       => 1,
                    'options'      => $this->privacy_choice,
                    'icon'         => 'dashicons dashicons-privacy',
                    'icons'        => array(
                                        'public'  => 'dashicons dashicons-universal-access-alt', 
                                        'self'    => 'dashicons dashicons-privacy', 
                                        'members' => 'dashicons dashicons-groups', 
                                    ),
                ),
                'status'  => array(
                    'title'        => __( 'Availability', 'describr' ),
                    'label'        => __( 'Availability', 'describr' ),
                    'name'         => 'status',
                    'type'         => 'radio',
                    'default'      => 'yes',
                    'editable'     => current_user_can( 'edit_users' ),
                    'required'     => true,
                    'spellcheck'   => false,
                    'autocomplete' => 'off',
                    'public'       => 1,
                    'cap'          => 'edit_users',
                    'options'      => describr_tanslated_field_status(),
                    'icon'         => 'dashicons dashicons-format-status',
                    'icons'        => array(
                                        'yes' => 'dashicons dashicons-yes-alt', 
                                        'no'  => 'dashicons dashicons-no', 
                                    ),
                ),
                'audit' => array(
                    'title'    => __( 'Administration', 'describr' ),
                    'label'    => __( 'Administration', 'describr' ),
                    'name'     => 'audit',
                    'editable' => false,
                    'cap'      => 'manage_options',
                    'public'   => 1,
                    'icon'     => 'dashicons dashicons-dashboard',
                ),
            )
        );
    }
    /**
     * Sets field to audit
     *
     * @since 3.0
     * 
     * @param string The field
     */
    public function auditing_fields( $field ) {
        $asides = $this->get_field_asides( $field );
           
        if ( ! empty( $asides['audit'] ) && ( empty( $asides['cap'] ) || current_user_can( $asides['cap'] ) ) ) {
            if ( ! empty( $asides['_cat'] ) ) {
                $key = $asides['_cat'];
            } else {
                $key = $field;
            }

            $key .= '_audit';

            $this->fields_to_audit[] = $key;
        }
    }

    /**
     * Audits fields
     *
     * @since 3.0
     * 
     * @param int $user_id User ID
     */
    public function audit_fields( $user_id ) {
        if ( ! $this->fields_to_audit || empty( $user_id ) || ! is_numeric( $user_id ) ) {
            return;
        }

        $audit_keys = array_filter( array_unique( $this->fields_to_audit ) );

        if ( ! $audit_keys ) {
            return;
        }

        $current_user_id = get_current_user_id();

        if ( 1 > $current_user_id ) {
            return;
        }

        $log = array(
            'user_id' => $current_user_id,
            'time'    => time(), 
        );
                
        foreach ( $audit_keys as $audit_key ) {
            $saved_audit = get_user_meta( $user_id, $audit_key, true );

            if ( is_array( $saved_audit ) ) {
                $saved_audit[] = $log;
                update_user_meta( $user_id, $audit_key, $saved_audit );
            } else {
                add_user_meta( $user_id, $audit_key, array( $log ), true );
            }
        }

        $this->fields_to_audit = array();
    }

    /**
     * Removes capability element from array
     * 
     * @since 3.0
     * 
     * @param array $arr Array from which to remove capability
     * @return array The passed array with capability removed
     */
    public function remove_cap( $arr ) {
        if ( ! is_array( $arr ) ) {
            return $arr;
        }

        foreach ( $arr as $k => $v ) {
            if ( 'cap' === $k ) {
                unset( $arr[ $k ] );
            } elseif ( is_array( $v ) ) {
                $arr[ $k ] = $this->remove_cap( $v );
            }
        }

        return $arr;
    }
    
    /**
     * Retrieves path to a template
     * 
     * @since 3.0
     *
     * @param string $type Filename without extension
     * @param array  $args Additional arguments passed to the template
     * @return string Full path to template file
     */
    public function get_template( $type, $args = array() ) {
        return $this->locate_template( "{$type}.php", true, $args );
    }
    
    /**
     * Retrieves the name of the highest priority template file that exists
     * 
     * @since 3.0
     *
     * @param string $template_name Template to search for
     * @param bool   $load          Whether the template file will be loaded if found
     * @param array  $args          Additional arguments passed to the template
     * @return string The template filename if one is located
     */
    public function locate_template( $template_name, $load = true, $args = array() ) {       
        $located = '';

        $paths = array( get_stylesheet_directory() . '/describr/templates/' );
        
        if ( is_child_theme() ) {
            $paths[] = get_template_directory() . '/describr/templates/';
        }
        
        $paths[] = DESCRIBR_TEMPLATE;
        
        foreach ( $paths as $path ) {
            $file = $path . $template_name;

            if ( file_exists( $file ) && 0 === mb_stripos( wp_normalize_path( realpath( $file ) ), wp_normalize_path( $path ), 0, 'UTF-8' ) ) {
                $located = $file;
                break;
            }
        }
        
        if ( $load && '' !== $located ) {
            $this->load_template( $located, $args );
        }

        return $located;
    }

    /**
     * Includes the plugin template file
     * 
     * @since 3.0
     *
     * @param string $_template_file Path to template file
     * @param array $args            Additional arguments passed to the template
     */
    function load_template( $_template_file, $args = array() ) {
        if ( is_array( $args ) ) {
            unset( $args['template'], $args['settings'], $args['tabs'], $args['tab'], $args['current_tab'], $args['subtab'] );
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
         * Fires before a template file is loaded
         *
         * @since 3.0
         *
         * @param string $_template_file The full path to the template file
         * @param array  $args           Additional arguments passed to the template
         */
        do_action( 'describr_before_load_template', $_template_file, $args );
        
        include $_template_file;

        /**
         * Fires after a template file is loaded
         *
         * @since 3.0
         *
         * @param string $_template_file The full path to the template file
         * @param array  $args           Additional arguments passed to the template
         */
        do_action( 'describr_after_load_template', $_template_file, $args );
    }
    
    /**
     * Retrieves the classes for a template
     * 
     * @since 3.0
     * 
     * @param string $mode The current mode
     * @param array  $args Additional arguments passed to the template
     * @return string The template's classes
     */
    public function get_class( $mode, $args = array() ) {
        $classes = array( 'describr-' . $mode );

        if ( isset( $args['template'] ) && $args['template'] != $args['mode'] ) {
            $classes[] = 'describr-' . $args['template'];
        }
        
        if ( describr()->error()->has_errors() ) {
            $classes[] = 'describr-error';
        }
            
        if ( wp_is_mobile() ) {
            $classes[] = 'describr-mobile';
        }

        if ( is_multisite() ) {
            $classes[] = 'describr-multisite';
        }

        if ( true === describr()->fields()->is_editing ) {
            $classes[] = 'describr-editing';
        }

        if ( true === describr()->fields()->is_viewing ) {
            $classes[] = 'describr-viewing';
        }
        
        $classes[] = 'describr-logged-' . ( is_user_logged_in() ? 'in' : 'out' );
        
        /**
         * Filters classes for a template
         * 
         * @since 3.0
         * 
         * @param array  $classes The classes
         * @param string $mode    The current mode
         * @param array  $args    Additional arguments passed to the template
         */
        $classes = apply_filters( 'describr_template_classes', $classes, $mode, $args );

        return implode( ' ', array_unique( $classes ) );
    }
    
    /**
     * Includes the plugin template dynamic css file
     * 
     * @since 3.0
     *
     * @param array $args Additional arguments passed to the template
     * @return string Empty string
     */
    public function dynamic_css( $args = array() ) {
        if ( empty( $args['mode'] ) ) {
            return '';
        }
        
        $sep = DIRECTORY_SEPARATOR;
        
        $file = DESCRIBR_DIR . "assets{$sep}css{$sep}dynamic{$sep}" . $args['mode'] . '.php';
            
        if ( file_exists( $file ) ) {
            include_once $file;
        }

        return '';
    }
}
