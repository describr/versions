<?php
namespace describr\admin;

/**
 * Admin_Options class
 *
 * @package Describr
 * @since 3.0
 */
class Admin_Options {
	/**
     * Plugin's prefix
     * 
     * @since 3.0
     * @var string
     */
	private $prefix;

	/**
     * Profile tabs option prefix
     * 
     * @since 3.0
     * @var string
     */
	private $tab_setting_prefix;

	/**
     * Stores  the plugin's options
     * 
     * @since 3.0
     * @var array
     */
	private $settings;

	/**
     * Stores the profile page tabs
     * 
     * @since 3.0
     * @var array
     */
	private $tabs;

	/**
     * Stores the profile page tabs options
     * 
     * @since 3.0
     * @var array
     */
	private $tabs_settings = array();

    /**
     * Admin_Options constructor
     * 
     * @since 3.0
     */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ), 10 );
		add_action( 'admin_init', array( $this, 'add_avatar_setting_field' ), 10 );
	}

    /**
     * Registers settings
     * 
     * @since 3.0
     */
	public function register_settings() {
		/*Network admin has its own Describr settings.
		Other than here, the options are also updating in wp-admin/network/site-settings.php.*/
		if ( is_multisite() && is_network_admin() ) {
			return;
		}

		$this->settings = describr()->get_settings();
		$this->tabs = describr()->profile_tabs_settings();
		$this->prefix = describr()->prefix;
		$this->tab_setting_prefix = $this->prefix . 'profile_menu_tab_';

		register_setting(
			'describr',
			'describr_fields',
			array(
				'sanitize_callback' => static function ( $option_value ) {
					if ( ! is_array( $option_value ) || ! wp_is_numeric_array( $option_value ) ) {
						return array();
					}
                    
                    $option_value = array_map( 'trim', $option_value );

                    return array_unique( $option_value );
				}
			)
		);
        
        foreach ( array_keys( describr()->account_tabs_settings() ) as $option_name ) {
        	register_setting( 
        		'describr', 
        		$option_name,
        		array(
        			'sanitize_callback' => function ( $option_value ) {
        				if ( null === $option_value ) {
        					$option_value = 0;
        				} else {
        					$option_value = 1;
        				}

        				return $option_value;
        			}
        		)
        	);
        }

        //The site's X (formerly Twitter) handle
		register_setting( 
			'describr', 
			'describr_x', 
			array(
				'sanitize_callback' => static function ( $option_value ) {
					if ( ! is_string( $option_value ) ) {
						return '';
					}
                    
                    $option_value = trim( $option_value );
                    
                    if ( '' === $option_value ) {
                    	return '';
                    }
                    
                    if ( isset( describr()->social_fields['x']['base_url'] ) ) {
                    	$baseurl = describr()->social_fields['x']['base_url'];
                    	
                    	if ( ! str_starts_with( $option_value, $baseurl ) ) {
                    		$option_value = $baseurl . ltrim( $option_value, '/' );
                    	}
                    }
					
		            return $option_value; 
				}
			) 
		);
        
        foreach ( describr()->pages as $page => $settings ) {
        	$option_name = $settings['id'];

        	register_setting(
        		'describr',
        		$option_name,
        		array(
        			'sanitize_callback' => static function ( $new_page_id ) use ($settings) {
        				$new_page_id = absint( $new_page_id );

        				$post = get_post( $new_page_id );
                        
                        $option = $settings['id'];
                        
        				$current_page_id = absint( get_option( $option ) );

        				if ( ! $post || $post->ID === $current_page_id || 'page' !== $post->post_type ) {
        					return $current_page_id;
        				}
                        
                        $new_page_id = $post->ID;

                        $can_set_error = function_exists( 'add_settings_error' );

                        if ( (int) get_option( 'page_for_posts' ) === $new_page_id || (int) get_option( 'page_on_front' ) === $new_page_id || (int) get_option( 'wp_page_for_privacy_policy' ) === $new_page_id ) {
        		        	if ( $can_set_error ) {
        		        		add_settings_error( 
        		        			$option, 
        		        			"invalid_{$option}",
        	                        __( 'The Privacy Policy, Homepage, or Posts Page cannot be used as Profile and Account pages.', 'describr' ) 
        		        		);
                        	}

        		        	return $current_page_id;
        		        }
                        
                        $update_page = false;
                        
                        //Only allow the current account page to be used as the profile page if the new account page is different from the current account page
        		        if ( 'describr_user_page' === $option && $new_page_id === (int) get_option( 'describr_account_page' ) ) {
        		        	if ( isset( $_POST['describr_account_page'] ) ) {
        		        		$_post = get_post( (int) $_POST['describr_account_page'] );//New account page
                                 
                                //Test if the new page is different from the current page before using the new page
        		        		if ( $_post && (int) get_option( 'describr_account_page' ) !== $_post->ID ) {
        		        			$update_page = true;
        		        		}
        		        	}
        		        	
        		        	if ( ! $update_page ) {
        		        		if ( $can_set_error ) {
        		        		    add_settings_error( 
        		        			    $option, 
        		        			    "invalid_{$option}",
        		        			    __( 'The Account page cannot be used as the Profile page.', 'describr' )
        		        		    );
        		        	    }

        		        		return $current_page_id;
        		        	}

        		        	//Only allow the current profile page to be used as the account page if the new profile page is different from the current profile page
        		        } elseif ( 'describr_account_page' === $option && $new_page_id === (int) get_option( 'describr_user_page' ) ) {
        		        	if ( isset( $_POST['describr_user_page'] ) ) {
        		        		$_post = get_post( (int) $_POST['describr_user_page'] );//New profile page

        		        		if ( $_post && (int) get_option( 'describr_user_page' ) !== $_post->ID ) {
        		        			$update_page = true;
        		        		}
        		        	}
        		        	
        		        	if ( ! $update_page ) {
        		        		if ( $can_set_error ) {
        		        		    add_settings_error( 
        		        			    $option, 
        		        			    "invalid_{$option}",
        		        			    __( 'The Profile page cannot be used as the Account page.', 'describr' )
        		        		    );
        		        	    }

        		        		return $current_page_id;
        		        	}
        		        }

        		        wp_update_post(array(
        		        	'ID'      => $new_page_id,
        		        	'content' => wp_slash( $settings['content'] ),
        		        ));
                        
                        update_option( $option, $new_page_id );
                        
                        describr()->pages()->set_pages();
                        
                        flush_rewrite_rules();
                        
        		        return $new_page_id;
        			}
        		)
        	);
        }
        
        unset( $settings, $option_name );

        $reg_tabs_settings = array();

        //Not added during installation
        $optional_settings = describr()::email_misc_options();
        $optional_settings[] = $this->prefix . 'change_password_notify_admin';
        
        foreach ( $optional_settings as $option_name ) {
        	register_setting( 
        		'describr', 
        		$option_name, 
        		array( 'sanitize_callback' => function ( $option_value ) use ($option_name) {
        			switch (str_replace( $this->prefix, '', $option_name )) {
                    	case 'admin_email':
                    	    if ( ! is_string( $option_value ) ) {
                    	    	$option_value = '';
                    	    }
                            
                            if ( '' === $option_value ) {
                            	break;
                            }
                            
                            $option_value = trim( $option_value, ',' );
                    		
                    		$emails = array_map( 'trim', explode( ',', $option_value ) );
                            
                            if ( $emails !== array_filter( $emails, 'is_email' ) && function_exists( 'add_settings_error' )  ) {
                            	add_settings_error( 
                    				$option_name, 
                    				"invalid_{$option_name}", 
                    				__( 'One or more admin email address is invalid. Please enter a valid email address.', 'describr' ) 
                    			);
                            }

                            $emails = array_map( 'sanitize_email', $emails );

                            $option_value = implode( ',', array_filter( $emails ) );
                    	    break;
                    	case 'email_from_addr':
                    	    if ( is_string( $option_value ) ) {
                    	    	if ( '' !== $option_value ) {
                    	    		if ( ! is_email( $option_value ) && function_exists( 'add_settings_error' ) ) {
                                     	add_settings_error( 
                                     		$option_name, 
                                     		"invalid_{$option_name}", 
                                     		__( 'The "from" email address is invalid. Please enter a valid email address.', 'describr' ) 
                                     	);
                        		    }

                        		    $option_value = sanitize_email( $option_value );
                    	    	}
                    	    } else {
                    	    	$option_value = '';
                    	    }
                    		break;
                    	case 'email_from_name':
                    	    if ( ! is_string( $option_value ) ) {
                    	    	$option_value = '';
                    	    	break;
                    	    }
                    	    
                    	    if ( '' !== $option_value ) {
                    	    	$option_value = esc_html( $option_value );
                    	    }
                    	    break;
                    	case 'user_change_password_notify_admin':
                    	    if ( null === $option_value ) {
                    	    	$option_value = 0;
                    	    } else {
                    	    	$option_value = 1;
                    	    }
                    	    break;
                    }

                    return $option_value;
        		}) 
        	);
        }
        
        unset($option_name);

        //Not added during installation
        $mail_settings = describr()::email_logo_options();

        if ( ! is_multisite() ) {
			$mail_settings = array_merge( 
				$mail_settings, 
				describr()::email_mta_options()/*Not added during installation*/
			);
        }
        
        foreach ( $mail_settings as $option_name ) {
        	register_setting( 
        		'describr', 
        		$option_name ,
        		array( 'sanitize_callback' => function ( $option_value ) use ( $option_name  ) {
        			if ( ! is_string( $option_value ) ) {
        				$option_value = '';
        			}

        			switch (str_replace( $this->prefix, '', $option_name  )) {
        				case 'mailer':
        					if ( ! in_array( $option_value, array( 'smtp', 'mail', 'qmail', 'sendmail' ), true ) ) {
        						$option_value = '';
        					}
        					break;
        				case 'smtp_encrypt_proto':
        					if ( ! in_array( $option_value, array( '', 'ssl', 'tls' ), true ) ) {
        						$option_value = '';
        					}
        					break;
        				case 'smtp_port':
        				    $option_value = absint( $option_value );

        				    if ( ! $option_value ) {
        				    	$option_value = 25;
        				    }
        					break;
        				case 'smtp_host':
        				    if ( ! empty( $option_value ) ) {
        				    	$option_value = array_map( 'trim', explode( ';', wp_kses_post( trim( $option_value, ';' ) ) ) );
        				    	$option_value = implode( ';', $option_value );
        				    } else {
        				    	$option_value = '';
        				    }
        					break;
        				case 'smtp_auth':
        				    if ( 'true' !== $option_value ) {
        				    	$option_value = null;
        				    }
        					break;
        				case 'smtp_username':
        				case 'smtp_pass':
        				    $option_value = wp_kses_post( $option_value );
                            break;
        				case 'mail_logo':
        				    if ( ! in_array( $option_value, array( '', 'custom', 'site' ), true ) ) {
        						$option_value = '';
        					}
        					break;
        				case 'mail_logo_size':
        				    if ( ! in_array( $option_value, array( 'full', 'thumbnail', 'medium', 'medium_large', 'large', 'custom' ), true ) ) {
        						$option_value = 'thumbnail';
        					}
        					break;
        				case 'mail_logo_custom_size_w':
        				case 'mail_logo_custom_size_h':
        				    if ( null === $option_value ) {
        				    	$option_value = '';
        				    } else {
        				    	$option_value = absint( $option_value );
        				    }
        				    break;
        				case 'mail_logo_url':
        				    if ( '' !== $option_value ) {
        				    	$url = sanitize_url( $option_value );
        				    	$protocols = implode( '|', array_map( 'preg_quote', wp_allowed_protocols() ) );
        				    	$option_value = preg_match( '/^(' . $protocols . '):/is', $url ) ? $url : 'http://' . $url;
        					}
        					break;
        			}

        			return $option_value;
        		}) 
        	);
        }

        unset($option_name);

		foreach ( $this->tabs as $tab => $settings ) {
			$option_name = $this->tab_setting_prefix . $tab;

			if ( isset( $this->settings[ $option_name ] ) ) {
				$reg_tabs_settings[] = $option_name;

				//Not registered by default so we'll not check if set
				$tab_roles_priv_setting = $option_name . '_privacy_roles';
                
                $reg_tabs_settings[] = $tab_roles_priv_setting;

				register_setting( 
					'describr', 
					$option_name,
					array(
						'sanitize_callback' => static function ( $show_tab ) {
							return null === $show_tab ? 0 : 1;
						}
					)
				);

				register_setting( 
					'describr', 
					$tab_roles_priv_setting,
					array( 'sanitize_callback' => static function ( $tab_priv_roles ) {
						if ( ! is_array( $tab_priv_roles ) ) {
							return array();
						}
                        
                        $tab_priv_roles = array_map( 'trim', $tab_priv_roles );

                        return array_filter( $tab_priv_roles, array( wp_roles(), 'is_role' ) );
					})
				);
                
				$this->tabs_settings[ $option_name ] = array(
					'name'      => $settings['name'],
					'def_val'   => $this->settings[ $option_name ],
					'roles_key' => $tab_roles_priv_setting,
				);
				
				$tab_priv_setting = $option_name . '_privacy';
                
				if ( isset( $this->settings[ $tab_priv_setting ] ) ) {
					$reg_tabs_settings[] = $tab_priv_setting;

					$this->tabs_settings[ $option_name ]['priv'] = array(
						'key'     => $tab_priv_setting,
						'def_val' => $this->settings[ $tab_priv_setting ],
					);

					register_setting( 
						'describr', 
						$tab_priv_setting,
						array(
							'sanitize_callback' => static function ( $tab_priv ) {
								return is_string( $tab_priv ) ? sanitize_key( $tab_priv ) : 'logged-in_or_logged-out';
							}
						)
					);
				}
			}
		}
        
        unset($option_name);
        
        $handled_elsewhere_settings = array_merge( $optional_settings, $reg_tabs_settings, $mail_settings );
		
		//Options inserted into the database during installation
		foreach ( array_keys( $this->settings ) as $option_name ) {
			if ( in_array( $option_name, $handled_elsewhere_settings, true ) ) {
				continue;
			}
            
			register_setting( 
				$this->prefix . 'avatar' === $option_name ? 'discussion' : 'describr', 
				$option_name,
				array( 'sanitize_callback' => function ( $option_value ) use ($option_name) {
					switch (str_replace( $this->prefix, '', $option_name )) {
						case 'avatar':
                        case 'new_user_moderation_notify':
                        case 'new_user_notify_admin':
                        case 'deleted_user_notify_admin':
                        case 'new_user_notify_user':
                        case 'approved_user_notify_user':
                        case 'active_user_notify_user':
                        case 'inactive_user_notify_user':
                        case 'rejected_user_notify_user':
                        case 'pending_user_notify_user':
                        case 'confirm_user_notify_user':
                        case 'deleted_user_notify_user':
                        case 'author_redirect':
                        case 'enable_profile_messaging':
                        case 'enable_profile_menu':
                        case 'enable_social_media':
                        case 'allow_at_symbol_in_login_nicename':
                        case 'users_can_unsubscribe_from_email_notif':
                        case 'enable_site_mail_logo':
                        case 'save_plugin_data':
                        case 'registration_password_required':
                        case 'require_strong_password':
                        case 'confirm_password':
                            if ( null === $option_value ) {
                            	$option_value = 0;
                            } else {
                            	$option_value = 1;
                            }
                            break;
                        case 'password_min_chars':
                            if ( null === $option_value ) {
                            	$option_value = 6;
                            } else {
                            	$option_value = absint( $option_value );
                            }
                            break;
                        case 'password_max_chars':
                            if ( null === $option_value ) {
                            	$option_value = 38;
                            } else {
                            	$option_value = absint( $option_value );
                            }
                            break;
                        case 'profile_photosize':
                            if ( null === $option_value ) {
                            	$option_value = 320;
                            } else {
                            	$option_value = absint( $option_value );
                            }
                            break;
                        case 'admin_email_content_type':
                        case 'email_content_type':
                            if ( ! in_array( $option_value, array( 'text/html', 'text/plain' ), true ) ) {
                            	$option_value = 'text/plain';
                            }
                            break;
                        case 'default_profile_menu_tab':
                            $tabs = describr()->profile_tabs_settings();

                            if ( ! isset( $tabs[ $option_value ] ) ) {
                            	$option_value = 'about';
                            } else {
                            	$option_value = sanitize_key( $option_value );
                            }
                            break;
						case 'default_user_status':
							$status = describr_account_status_labels();

							if ( isset( $status[ $option_value ] ) ) {
								$option_value = sanitize_text_field( $option_value );
							} else {
								$option_value = 'approved';
							}
							break;						
						default:
							$option_value = sanitize_text_field( $option_value );
							break;
					};

					return $option_value;
				})
			);
		}        
	}
    
    /**
     * Adds Avatar setting field
     * 
     * @since 3.0
     */
	public function add_avatar_setting_field() {
		if ( is_multisite() && is_network_admin() ) {
			return;
		}
		
		add_settings_field( 
			'describr', 
			sprintf(
				/*translators: %s: Plugin's name.*/
				__( '%s Avatar', 'describr' ), 
				DESCRIBR
			), 
			function () {
				$option = 'describr_avatar';

                $desc_id = $option . '_desc';

				echo '<input type="checkbox" name="' . esc_attr( $option ) . '" value="1" id="' . esc_attr( $option ) . '" aria-describedby="' . esc_attr( $desc_id ) . '"' . checked( 1, get_option( $option ), false ) . ' /> <span id="' . esc_attr( $desc_id ) . '">' . esc_html__( 'Use as main Avatar (default to Gravatar)', 'describr' ) . '</span>';
			}, 
			'discussion', 
			'avatars', 
			array( 'label_for' => 'describr_avatar' ) 
		);
	}
    
    /**
     * Outputs the plugin's main settings
     * 
     * @since 3.0
     * @since 3.0.1 Removes "site&#039;s" that precedes "front end".
     */
	public function main_options() {
		?>
		<tr class="show-admin-bar user-admin-bar-front-wrap">
			<th scope="row"><?php esc_html_e( 'Email me whenever', 'describr' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text">
                        <?php
                        /*translators: Hidden accessibility text.*/
                        esc_html_e( 'Admin notifications', 'describr' );
                        ?>
                    </legend>
                    <?php
                    $settings = array(
                    	'user_change_password_notify_admin' => __( 'A user changes password', 'describr' ),
                    	'new_user_notify_admin'             => __( 'A new user is registered', 'describr' ),
                    	'new_user_moderation_notify'        => __( 'A new user&#039;s account is held for moderation', 'describr' ),
                    	'deleted_user_notify_admin'         => __( 'A user is deleted', 'describr' ),
                    );

                    foreach ( $settings as $setting => $label ) {
                    	$setting_name = $this->prefix . $setting;
                    	?>
                    	<label for="<?php echo esc_attr( $setting_name ); ?>"><input name="<?php echo esc_attr( $setting_name ); ?>" type="checkbox" id="<?php echo esc_attr( $setting_name ); ?>" value="1"<?php checked( 1, get_option( $setting_name ) ); ?> /> <?php echo esc_html( $label ); ?></label> <br />
                    	<?php
                    }
                    ?>
				</fieldset>
			</td>
		</tr>
		<tr class="show-admin-bar user-admin-bar-front-wrap">
			<th scope="row"><?php esc_html_e( 'Email user whenever', 'describr' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text">
                        <?php
                        /*translators: Hidden accessibility text.*/
                        esc_html_e( 'Member notifications', 'describr' );
                        ?>
                    </legend>
                    <?php
                    $settings = array(
                    	'new_user_notify_user'      => __( 'A new user is registered', 'describr' ),
                    	'approved_user_notify_user' => __( 'Membership is approved', 'describr' ),
                    	'confirm_user_notify_user'  => __( 'A user confirms membership', 'describr' ),
                    	'pending_user_notify_user'  => __( 'Membership is held for moderation', 'describr' ),
                    	'active_user_notify_user'   => __( 'User is activated', 'describr' ),
                    	'inactive_user_notify_user' => __( 'User is deactivated', 'describr' ),
                    	'rejected_user_notify_user' => __( 'Membership is rejected', 'describr' ),
                    	'deleted_user_notify_user'  => __( 'A user is deleted', 'describr' ),
                    );

                    foreach ( $settings as $setting => $label ) {
                    	$setting_name = $this->prefix . $setting;
                    	?>
                    	<label for="<?php echo esc_attr( $setting_name ); ?>"><input name="<?php echo esc_attr( $setting_name ); ?>" type="checkbox" id="<?php echo esc_attr( $setting_name ); ?>" value="1"<?php checked( 1, get_option( $setting_name ) ); ?> /> <?php echo esc_html( $label ); ?></label> <br />
                    	<?php
                    }
                    ?>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Profile', 'describr' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text">
                        <?php
                        /*translators: Hidden accessibility text.*/
                        esc_html_e( 'Profile', 'describr' );
                        ?>
                    </legend>
                    <?php
                    $settings = array( 
                    	'author_redirect'          => __( 'Use Describr as the default profile page on the front end', 'describr' ),
                        'profile_photosize'        => __( 'Profile photo size', 'describr' ),
                        'enable_profile_messaging' => __( 'Members may send emails to each other', 'describr' ),
                        'enable_profile_menu'      => __( 'Enable profile menu', 'describr' ),
                    );

                    foreach ( $settings as $setting => $label ) {
                        $setting_name = $this->prefix . $setting;
                        $old_value = get_option( $setting_name, '' );
                        ?>
                    	<label for="<?php echo esc_attr( $setting_name ); ?>"><input name="<?php echo esc_attr( $setting_name ); ?>" type="<?php echo 'profile_photosize' === $setting ? 'number' : 'checkbox'; ?>" id="<?php echo esc_attr( $setting_name ); ?>" value="<?php echo 'profile_photosize' === $setting ? esc_attr( $old_value ) : '1'; ?>"<?php
                    	if ( 'profile_photosize' !== $setting ) {
                    	 checked( 1, $old_value );
                    	}
                        ?> /> <?php echo esc_html( $label ); ?></label> <br />
                    	<?php
                    }

                    $default_tab_setting = $this->prefix . 'default_profile_menu_tab';
                    $default_tab = get_option( $default_tab_setting );
                    ?>
                    <label for="<?php echo esc_attr( $default_tab_setting ); ?>"><?php esc_html_e( 'Default tab:', 'describr' );?> <select name="<?php echo esc_attr( $default_tab_setting ); ?>" id="<?php echo esc_attr( $default_tab_setting ); ?>">
                        	<?php
                        	foreach ( $this->tabs as $tab => $tab_settings ) {
                        		?>
                                <option value="<?php echo esc_html( $tab ); ?>"<?php selected( $tab, $default_tab ); ?>><?php echo esc_html( $tab_settings['name'] ); ?></option>
                        		<?php
                        	}
                        	?>
                        </select></label>
                    <?php
                    $roles = wp_roles()->role_names;

                    if ( ! empty( $this->tabs_settings ) ) {
                        ?>
                        <h3 id="describr_active_profile_tabs"><?php esc_html_e( 'Active Tabs', 'describr' ); ?></h3>
                        <?php
          	            foreach ( $this->tabs_settings as $tab_setting => &$tab_setting_ ) {
          	                $tab_name = $tab_setting_['name'];
          	                $tab_def_val = $tab_setting_['def_val'];
          	                $tab_current_val = get_option( $tab_setting );
          	                ?>
          	                <label for="<?php echo esc_attr( $tab_setting ); ?>"><input name="<?php echo esc_attr( $tab_setting ); ?>" type="checkbox" id="<?php echo esc_attr( $tab_setting ); ?>" value="1" aria-describedby="describr_active_profile_tabs"<?php checked( 1, $tab_current_val ); ?> /> <?php echo esc_html( $tab_name ); ?></label>
          	                <?php
                            if ( ! empty( $tab_current_val ) ) {
          	                    if ( isset( $tab_setting_['priv'] ) ) {
          	                		$tab_priv = $tab_setting_['priv'];
          	                		$priv_setting = $tab_priv['key'];
          	                		$tab_priv_currrent_val = get_option( $priv_setting );
          	                		echo '<div style="display: none;">key: ' . $priv_setting . '<br>Value: ' . $tab_priv_currrent_val . '</div>';
          	                		?>
          	                		<div id="<?php echo esc_attr( $priv_setting ); ?>_wrap">
          	                			<p><label for="<?php echo esc_attr( $priv_setting ); ?>"><?php echo esc_html(sprintf(/*translators: %s: Profile tab name.*/ __( 'Who can view %s:', 'describr' ), $tab_name ));?> <select name="<?php echo esc_attr( $priv_setting ); ?>" id="<?php echo esc_attr( $priv_setting ); ?>"><?php $this->tab_priv_choice( $tab_priv_currrent_val ); ?></select></label></p>
          	                			<?php
          	                			if ( in_array( $tab_priv_currrent_val, array( 'with-roles', 'profile-owner_or_with-roles', ), true ) ) {
          	                				$roles_key = $tab_setting_['roles_key'];
          	                				$tab_priv_current_roles = get_option( $roles_key );

                                            if ( is_array( $tab_priv_current_roles ) ) {
                                                $tab_priv_current_roles = array_flip( $tab_priv_current_roles );
                                                $tab_priv_current_roles = array_intersect_key( $tab_priv_current_roles, $roles );
                                                if ( $tab_priv_current_roles ) {
                                                	$tab_priv_current_roles = array_flip( $tab_priv_current_roles );

                                                    $tab_setting_['priv']['current_roles'] = $tab_priv_current_roles;

                                                    foreach ( $tab_priv_current_roles as $tab_priv_current_role ) {
                                                    	echo '<input type="hidden" name="' . esc_attr( $roles_key ) . '[]" value="' . esc_attr( $tab_priv_current_role ) . '" />';
          	                						}

          	                						echo '<p><span role="button" id="_' . esc_attr( $roles_key ) . '_editrole" class="describr-edit" data-tab="' . esc_attr( $tab_setting ) . '">' . esc_html__( 'Edit roles', 'describr' ) . ' <span aria-hidden="true" class="dashicons dashicons-edit"></span></span></p>';
                                                }
          	                				}
          	                			}
          	                			?>          	                				
          	                		</div>
          	                		<?php
          	                		}
          	                } else {
          	                	echo '<br />';
          	                }
          	            }//foreach

          	            unset( $tab_setting_ );
                        
                        $roles_keys = array_keys( $roles );
                        $roles_esc = array();

                        foreach ( $roles_keys as $roles_key ) {
                        	$roles_esc[ $roles_key ] = esc_attr( $roles_key );
                        }

          	            wp_add_inline_script( 
          	                'describr-admin-main', 
          	                sprintf( 
          	                	'describr.profile=%1$s;describr.roles={ obj : %2$s, arr : %3$s, esc : %4$s };',
          	                	json_encode( 
          	                		array(
          	                			'tabs'          => array_keys( $this->tabs_settings ), 
          	                			'settings'      => $this->tabs_settings,
          	                			'tabPrivChoice' => $this->tab_priv_choice( '', false ), 
          	                		) 
          	                	), 
          	                	json_encode( $roles ),
          	                	json_encode( array_keys( $roles ) ),
          	                	json_encode( $roles_esc )
          	                ), 
          	                'before' 
          	            );
                    }//if ( ! empty( $this->tabs_settings ) )
                    ?>
                    <p><button type="button" class="button" id="describr-manage-profile-fields"><?php esc_html_e( 'Manage Fields', 'describr' ); ?></button></p>
                    <p><button type="button" class="button" id="describr-manage-social-media-fields"><?php esc_html_e( 'Manage Social Media Fields', 'describr' ); ?></button></p>
                    <p><label for="describr_enable_social_media"><input type="checkbox" name="describr_enable_social_media" value="1" id="describr_enable_social_media"<?php checked( 1, get_option( 'describr_enable_social_media' ) ); ?> /> <?php esc_html_e( 'Enable social media', 'describr' ); ?></label></p>
                    </fieldset>
                    <?php
                    $option = 'describr_fields';

		            $enabled_fields = get_option( $option, array() );

		            $fields = describr_get_field();

		            foreach ( $fields as $field => $settings ) {
		            	/*This filter is documented in wp-content/plugins/describr/includes/actions-filters/actions-profile.php*/
		            	$fields[ $field ] = apply_filters( "describr_profile_get_edit_field_{$field}", $settings, 0 );
		            }

		            unset( $fields['ID'] );
                    
                    $enabled_fields_ = array();

                    foreach ( $enabled_fields as $field ) {
                    	if ( isset( $fields[ $field ] ) ) {
                    		$enabled_fields_[] = $field;

                    		echo '<input type="hidden" name="' . esc_attr( $option ) . '[]" value="' . esc_attr( $field ) . '" id="describr_' . esc_attr( $field ) . '_field" />';
                    	}
			        }
                    
                    $asides = describr()->get_asides();

                    array_walk( $asides, function ( &$settings, $field ) {
                    	$titles = array();

                    	foreach ( $settings as $key => $val ) {
                    		if ( isset( $val['title'] ) ) {
                    			$titles[] = $val['title'];
                    		} elseif ( isset( $val['label'] ) ) {
                    			$titles[] = $val['label'];
                    		}
                    	}

                    	$settings = $titles;
                    });

		            wp_add_inline_script( 
		            	'describr-main', 
		            	sprintf(
		            		'describr.settings = describr.settings || {}; 
		            		describr.settings.fields = { 
		            			fields     : %1$s, 
		            			fieldsData : %2$s, 
		            			enabled    : %3$s,
		            			social     : %4$s, 
		            			asides     : %5$s
		            		};
		            		describr.settings.listSep = %6$s;',
                            json_encode( array_keys( $fields ) ),
                            json_encode( $fields ),
                            json_encode( $enabled_fields_ ),
                            json_encode( array_keys( describr()->social_fields ) ),
                            json_encode( $asides ),
                            json_encode( wp_get_list_item_separator() )
		                ),
		                'after'
		            );
                    ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Account Settings', 'describr' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text">
                        <?php
                        /*translators: Hidden accessibility text.*/
                        esc_html_e( 'Account Settings', 'describr' );
                        ?>
                    </legend>
                    <button type="button" class="button" id="describr-manage-account-settings-fields"><?php esc_html_e( 'Manage Account Fields', 'describr' ); ?></button>
                    <h3><?php esc_html_e( 'Active Tabs', 'describr' );?></h3>
                    <?php
                    foreach ( describr()->account_tabs_settings() as $tab_option_name => $tab_label ) {
                     	?>
                     	<label for="<?php echo esc_attr( $tab_option_name );?>"><input type="checkbox" id="<?php echo esc_attr( $tab_option_name );?>" name="<?php echo esc_attr( $tab_option_name );?>" value="1"<?php checked( 1, get_option( $tab_option_name ) );?> /> <?php echo esc_html( $tab_label );?></label><br />
                     	<?php
                     } 
                    ?>
                </fieldset>
            </td>
		</tr>
		<?php
		if ( ! empty( describr()->social_fields['x'] ) ) {
			$x = describr()->social_fields['x'];
		    $label = isset( $x['label'] ) ? $x['label'] : '';
		    $baseurl = isset( $x['base_url'] ) ? $x['base_url'] : '';
		    $handle = describr_get_handle_from_url( $baseurl, get_option( 'describr_x', '' ) );
		    $textbox_label = sprintf( 
			    /*translators: %s: X (formerly Twitter). For what is "handle", see https://www.hostinger.com/ca/tutorials/what-is-social-media-handle.*/ 
			    __( 'The site&#039;s %s handle', 'describr' ), 
			    $label 
		    );
		    ?>
		    <tr>
		    	<th scope="row"><?php echo esc_html( $label ); ?></th>
			    <td>
				    <fieldset><legend class="screen-reader-text">
					    <?php echo esc_html(sprintf(
					    	/*translators: Hidden accessibility text. %s: Field label.*/
					        __( 'The site&#039;s %s settings', 'describr' ),
					        $label
				        )); ?>
				        </legend>
					    <label class="screen-reader-text" for="describr_x"><?php echo esc_html( $textbox_label ); ?></label>
                        <span id="describr_x_desc" class="screen-reader-text"><?php echo esc_html( $baseurl ); ?></span>
				        <span aria-hidden="true"><span class="<?php echo esc_attr( $x['icon'] ); ?>" aria-hidden="true"></span>/</span><input type="text" name="describr_x" id="describr_x" aria-describedby="describr_x_desc" value="<?php echo esc_attr( $handle ); ?>" />
				    </fieldset>
			    </td>
		    </tr>
		    <?php
		}
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Email Settings', 'describr' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text">
                        <?php
                        /*translators: Hidden accessibility text.*/
                        esc_html_e( 'The site&#039;s email settings', 'describr' );                        
                        ?>
                    </legend><label for="describr_admin_email"><?php esc_html_e( 'Admin Emails:', 'describr' ); ?></label> <br />
                    <textarea name="describr_admin_email" id="describr_admin_email" aria-describedby="describr_admin_email_desc" rows="5" cols="45" class="large-text"><?php echo esc_textarea( get_option( 'describr_admin_email', '' ) ); ?></textarea>
                    <p class="description" id="describr_admin_email_desc"><?php echo esc_html(sprintf(
                        	/*translators: %s: Plugin's name.*/
                        	__( 'These email addresses are used by %s for admin purposes. Separate emails by commas. Defaults to the Admin email.', 'describr' ), 
                        	DESCRIBR 
                        )); ?></p>

                    <?php $admin_email_content_type = get_option( 'describr_admin_email_content_type' ); ?>

                    <p><label for="describr_admin_email_content_type"><?php esc_html_e( 'Admin Email Format:', 'describr' );?> <select name="describr_admin_email_content_type" id="describr_admin_email_content_type">
                            <option value="text/html"<?php selected( 'text/html', $admin_email_content_type ); ?>><?php echo esc_html_x( 'HTML', 'content type', 'describr' ); ?></option>
                            <option value="text/plain"<?php selected( 'text/plain', $admin_email_content_type ); ?>><?php echo esc_html_x( 'Plain text', 'content type', 'describr' ); ?></option>
                        </select></label></p>
                    
                    <?php $email_content_type = get_option( 'describr_email_content_type' ); ?>

                    <p><label for="describr_email_content_type"><?php esc_html_e( 'Users Email format:', 'describr' );?> <select name="describr_email_content_type" id="describr_email_content_type">
                            <option value="text/html"<?php selected( 'text/html', $email_content_type ); ?>><?php echo esc_html_x( 'HTML', 'content type', 'describr' ); ?></option>
                            <option value="text/plain"<?php selected( 'text/plain', $email_content_type ); ?>><?php echo esc_html_x( 'Plain Text', 'content type', 'describr' ); ?></option>
                        </select></label></p>
                    <?php
                    $from_addr_ex = get_option( 'describr_email_from_addr' );

                    if ( empty( $from_addr_ex ) ) {
                    	$from_addr_ex = get_option( 'admin_email' );
                    	
                    	if ( empty( $from_addr_ex ) ) {
                    		$from_addr_ex = _x( 'contact@example.com', 'email example','describr' );
                    	}
                    }

                    $from_name_ex = get_option( 'describr_email_from_name' );

                    if ( empty( $from_name_ex ) ) {
                    	$from_name_ex = get_option( 'blogname' );
                    	
                    	if ( empty( $from_name_ex ) ) {
                    		$from_name_ex = 'WordPress';
                    	}
                    }
                    ?>
                    <p><label for="describr_email_from_name"><?php echo esc_html_x( 'From name:', 'email "from" name', 'describr' ); ?></label> <input type="text" name="describr_email_from_name" value="<?php echo esc_attr( get_option( 'describr_email_from_name', '' ) ); ?>" id="describr_email_from_name"  class="regular-text" aria-describedby="describr_email_header_from_field_desc describr_email_from_name_default" /> <span class="description" id="describr_email_from_name_default"><?php esc_html_e( 'Defaults to the site&#039;s title', 'describr' ); ?></span></p>
                    <p><label for="describr_email_from_addr"><?php echo esc_html_x( 'From email address:', 'email "from" address', 'describr' ); ?></label> <input type="email" name="describr_email_from_addr" value="<?php echo esc_attr( get_option( 'describr_email_from_addr', '' ) ); ?>" id="describr_email_from_addr"  class="regular-text" aria-describedby="describr_email_header_from_field_desc describr_email_from_addr_default" /> <span class="description" id="describr_email_from_addr_default"><?php esc_html_e( 'Defaults to the site&#039;s Admin email', 'describr' ); ?></span></p>
                    <p class="description" id="describr_email_header_from_field_desc"><?php echo wp_kses_post(sprintf(
                        /*translators: %s: An email's header "From" field example.*/
                        __( 'The email&#039;s header "From" field indicates the site&#039;s name and email address in the receiver&#039;s inbox: %s.', 'describr' ),
                        sprintf( '<code>%1$s</code> &#60;<code>%2$s</code>&#62;', $from_name_ex, $from_addr_ex )
                        )); ?></p>
                    <?php
                    if ( ! is_multisite() ) {
                    	?>
                    	<p><label for="describr_users_can_unsubscribe_from_email_notif"><input type="checkbox" name="describr_users_can_unsubscribe_from_email_notif" id="describr_users_can_unsubscribe_from_email_notif" value="1"<?php checked( 1, get_option( 'describr_users_can_unsubscribe_from_email_notif' ) ); ?> /> <?php esc_html_e( 'Users can unsubscribe from receiving email notifications', 'describr' ); ?></label><p>
                    	<?php
                    }
                    ?>
                </fieldset>
			</td>
		</tr>
		<?php
		if ( is_multisite() ) {
			$this->email_logo();
		} else {
			$this->send_email_using();
			$this->email_logo();
			?>
			<tr>
				<th scope="row"><label for="describr_default_user_status"><?php esc_html_e( 'New User Default Status', 'describr' ); ?></label></th>
                <td>
                	<select name="describr_default_user_status" id="describr_default_user_status">
	                    <?php
	                    $current_status = get_option( 'describr_default_user_status' );

	                    foreach ( describr_account_status_labels() as $status => $label ) {
	    	                echo '<option value="' . esc_attr( $status ) . '"';
	    	                selected( $status, $current_status );
	    	                echo '>' . esc_html( $label ) . '</option>';
	                    }
	                    ?>		
	                </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="describr_allow_at_symbol_in_login_nicename"><?php esc_html_e( 'Allow @ symbol in usernames and nicenames', 'describr' ); ?></label></th>
                <td><input type="checkbox" name="describr_allow_at_symbol_in_login_nicename" id="describr_allow_at_symbol_in_login_nicename" value="1"<?php checked( 1, get_option( 'describr_allow_at_symbol_in_login_nicename' ) ); ?> /></td>
            </tr>
			<?php
			$this->password();
		}
	}

    /**
     * Outputs the font end Profile and Account pages settings
     * 
     * @since 3.0
     */
	public function frontend_pages() {
		$id = describr()->pages['user']['id'];

		$profile_page_id = ( int ) get_option( $id );
		
        $profile_page_exists = false;
		
		$profile_page_error = '';
		
		if ( ! empty( $profile_page_id ) ) {
			$profile_page = get_post( $profile_page_id );
            
            if ( ! is_a( $profile_page, '\WP_Post' ) ) {
				$profile_page_error = __( 'The currently selected Profile page does not exist. Please create or select a new page.', 'describr' );
			} elseif ( ! current_user_can( 'edit_post', $profile_page->ID ) ) {
				$profile_page_error = __( 'You don&#039;t have permission to edit the Profile page.', 'describr' );
			} elseif ( 'trash' === $profile_page->post_status ) {
				$profile_page_error = sprintf(
					/*translators: %s: URL to Pages Trash.*/
					__( 'The currently selected Profile page is in the Trash. Please create or select a new Profile page or <a href="%s">restore the current page</a>.', 'describr' ),
					'edit.php?post_status=trash&post_type=page'
				);
			} else {
				$profile_page_exists = true;
			}
		}

		$has_pages = (bool) get_posts(
			array(
				'post_type'      => 'page',
			    'posts_per_page' => 1,
			    'post_status'    => array(
				    'publish',
				    'draft',
			    ),
		    )
	    );
		?>
		<h2><?php esc_html_e( 'Describr Pages', 'describr' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Profile', 'describr' ); ?></th>
			    <td>
			    	<?php
			    	echo '<span class="screen-reader-text">';
			    	/*translators: Hidden accessibility text.*/
			    	esc_html_e( 'Profile page', 'describr' );
			    	echo '</span>';

			    	if ( $profile_page_exists ) {
			    		$edit_href = add_query_arg(
				        array(
					        'post'   => $profile_page_id,
					        'action' => 'edit',
				        ),
				        admin_url( 'post.php' )
			        );
			        $view_href = get_permalink( $profile_page_id );
			        ?>
				    <p><strong>
				        <?php
				        if ( 'publish' === get_post_status( $profile_page_id ) ) {
					        echo wp_kses_post( sprintf(
						        /*translators: 1: URL to edit Profile page, 2: URL to view Profile page.*/
						        __( '<a href="%1$s">Edit</a> or <a href="%2$s">view</a> your Profile page content.', 'describr' ),
						        esc_url( $edit_href ),
						        esc_url( $view_href )
					        ) );
				        } else {
					        echo wp_kses_post( sprintf(
						        /*translators: 1: URL to edit Profile page, 2: URL to preview Profile page.*/
						        __( '<a href="%1$s">Edit</a> or <a href="%2$s">preview</a> your Profile page content.', 'describr' ),
						        esc_url( $edit_href ),
						        esc_url( $view_href )
					        ) );
				        }
				        ?>
				        </strong></p>
			            <?php
			    	} else if ( ! empty( $profile_page_error ) ) {
			    		echo '<p>' . wp_kses_post( $profile_page_error ) . '</p>';
			    	}

			    	if ( $has_pages ) {
			    		echo '<label for="' . esc_attr( $id ) . '" class="screen-reader-text">';
			    		if ( $profile_page_exists ) {
			    			esc_html_e( 'Change your Profile page', 'describr' );
			    		} else {
			    			esc_html_e( 'Select a Profile page', 'describr' );
			    		}
			    		echo '</label>';
			    		wp_dropdown_pages(
						    array(
							    'name'              => $id,
							    'show_option_none'  => __( '&mdash; Select &mdash;', 'describr' ),
							    'option_none_value' => '0',
							    'selected'          => $profile_page_id,
							    'post_status'       => array( 'draft', 'publish' ),
						    )
					    );

					    $this->page_desc( $id );
			    	} else {
			    		$add_href = add_query_arg(
			    			array(
					            'post_type' => 'page'
				            ),
				            admin_url( 'post-new.php' )
			            );
			            echo wp_kses_post( sprintf(
						        /*translators: %s: URL to create Profile page.*/
						        __( '<a href="%s">Create</a> a profile page.', 'describr' ),
						        esc_url( $add_href )
					        ) );
			    	}
			    	?>
			    </td>
			</tr>
			<?php
			$id = describr()->pages['account']['id'];

			$account_page_id = ( int ) get_option( $id );

            $account_page_exists = false;

			$account_page_error = '';

			if ( ! empty( $account_page_id ) ) {
			    $account_page = get_post( $account_page_id );
            
                if ( ! is_a( $account_page, '\WP_Post' ) ) {
				    $account_page_error = __( 'The currently selected Account page does not exist. Please create or select a new page.', 'describr' );
			    } elseif ( ! current_user_can( 'edit_post', $account_page->ID ) ) {
				    $account_page_error = __( 'You don&#039;t have permission to edit the Account page.', 'describr' );
			    } elseif ( 'trash' === $account_page->post_status ) {
				    $account_page_error = sprintf(
					/*translators: %s: URL to Pages Trash.*/
					__( 'The currently selected Account page is in the Trash. Please create or select a new Account page or <a href="%s">restore the current page</a>.', 'describr' ),
					'edit.php?post_status=trash&post_type=page'
				);
			    } else {
				    $account_page_exists = true;
			    }
		    }
			?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Account', 'describr' ); ?></th>
			    <td>
			    	<?php
			    	echo '<span class="screen-reader-text">';
			    	/*translators: Hidden accessibility text.*/
			    	esc_html_e( 'Account page', 'describr' );
			    	echo '</span>';

			    	if ( $account_page_exists ) {
			    		$edit_href = add_query_arg(
				        array(
					        'post'   => $account_page_id,
					        'action' => 'edit',
				        ),
				        admin_url( 'post.php' )
			        );
			        $view_href = get_permalink( $account_page_id );
			        ?>
				    <p><strong>
				        <?php
				        if ( 'publish' === get_post_status( $account_page_id ) ) {
					        echo wp_kses_post( sprintf(
						        /*translators: 1: URL to edit Account page, 2: URL to view Account page.*/
						        __( '<a href="%1$s">Edit</a> or <a href="%2$s">view</a> your Account page content.', 'describr' ),
						        esc_url( $edit_href ),
						        esc_url( $view_href )
					        ) );
				        } else {
					        echo wp_kses_post( sprintf(
						        /*translators: 1: URL to edit Account page, 2: URL to preview Account page.*/
						        __( '<a href="%1$s">Edit</a> or <a href="%2$s">preview</a> your Account page content.', 'describr' ),
						        esc_url( $edit_href ),
						        esc_url( $view_href )
					        ) );
				        }
				        ?>
				        </strong></p>
			            <?php
			    	} else if ( ! empty( $account_page_error ) ) {
			    		echo '<p>' . wp_kses_post( $account_page_error ) . '</p>';
			    	}

			    	if ( $has_pages ) {
			    		echo '<label for="' . esc_attr( $id ) . '" class="screen-reader-text">';
			    		if ( $account_page_exists ) {
			    			esc_html_e( 'Change your Account page', 'describr' );
			    		} else {
			    			esc_html_e( 'Select an Account page', 'describr' );
			    		}
			    		echo '</label>';
			    		wp_dropdown_pages(
						    array(
							    'name'              => $id,
							    'show_option_none'  => __( '&mdash; Select &mdash;', 'describr' ),
							    'option_none_value' => '0',
							    'selected'          => $account_page_id,
							    'post_status'       => array( 'draft', 'publish' ),
						    )
					    );

					    $this->page_desc( $id );
			    	} else {
			    		$add_href = add_query_arg(
			    			array(
					            'post_type' => 'page'
				            ),
				            admin_url( 'post-new.php' )
			            );
			            echo wp_kses_post( sprintf(
						        /*translators: %s: URL to create Account page.*/
						        __( '<a href="%s">Create</a> an account page.', 'describr' ),
						        esc_url( $add_href )
					        ) );
			    	}
			    	?>
			    </td>
			</tr>
		</table>
		<?php
	}
    
    /**
     * Outputs setting description
     * 
     * @since 3.0
     */
    public function page_desc( $id ) {
    	$id_ = "{$id}_desc";
    	?>
    	<p class="description" id="<?php echo esc_attr( $id_ ); ?>"><?php esc_html_e( 'The selected page&#039;s content will be updated with the Describr shortcode. The Privacy Policy, Homepage, and Posts pages cannot be used.', 'describr' ); ?></p>
    	<?php
    	wp_print_inline_script_tag( 'document.getElementById(' . json_encode( $id ) . ').setAttribute("aria-describedby",' . json_encode( $id_ ) . ');' );
    }

    /**
     * Outputs the Profile tab privacy dropdown
     * 
     * @since 3.0
     */
	public function tab_priv_choice( $selected = false, $echo = true ) {
		$structure = array();

		foreach ( array( 
			'logged-in_or_logged-out'     => __( 'Everyone', 'describr' ), 
			'logged-in'                   => __( 'Members only', 'describr' ), 
			'logged-out'                  => __( 'Logged out users', 'describr' ), 
			'profile-owner'               => __( 'Profile owner'), 
			'with-roles'                  => __( 'Members with roles', 'describr'), 
			'profile-owner_or_with-roles' => __( 'Profile owner and members with roles', 'describr' ),
		) as $key => $val ) {
			if ( $echo ) {
				echo '<option value="'. esc_attr( $key ) .'"';

                if ( $key === $selected ) {
                	echo ' selected="selected"';
                }

				echo '>' . esc_html( $val ) . '</option>';
			} else {
				$structure[] = '<option value="' . esc_attr( $key ) . '">' . esc_html( $val ) . '</option>';
			}
		}

		if ( ! $echo ) {
			return $structure;
		}
	}
    
    /**
     * Outputs the email backend credentials settings
     * 
     * @since 3.0
     * 
     * @return \describr\admin\Admin_Options instance
     */
    public function send_email_using() {
    	$mailer = describr_get_network_option( 'describr_mailer' );
	    $smtp_host = '';
	    $smtp_port = 25;
	    $smtp_auth = '';
	    $smtp_encrypt_proto = '';
	    $smtp_username = '';
	    $smtp_pass = '';
	
	    if ( 'smtp' === $mailer ) {
		    $smtp_host = describr_get_network_option( 'describr_smtp_host', '' );
	        $smtp_port = describr_get_network_option( 'describr_smtp_port', $smtp_port );
	        $smtp_auth = describr_get_network_option( 'describr_smtp_auth', 'false' );
	        $smtp_encrypt_proto = describr_get_network_option( 'describr_smtp_encrypt_proto', '' );
	        $smtp_username = describr_get_network_option( 'describr_smtp_username', '' );
	        $smtp_pass = describr_get_network_option( 'describr_smtp_pass', '' );
        }
	    ?>
	    <tr>
	    	<th scope="row"><?php esc_html_e( 'Send Email Using', 'describr' ); ?></th> 
            <td>
        	    <fieldset><legend class="screen-reader-text">
                        <?php
                        /*translators: Hidden accessibility text.*/
                        esc_html_e( 'Send Email Using', 'describr' );
                        ?>
                    </legend>
                    <p><label for="describr_mailer_smtp"><input type="radio" name="describr_mailer" value="smtp" aria-describedby="describr_smtp_desc" id="describr_mailer_smtp"<?php checked( 'smtp', $mailer ); ?> /> SMTP</label></p>
    	            <p class="description" id="describr_smtp_desc"><?php echo wp_kses_post(sprintf(
    	            	/*translators: %s: SMTP abbreviation.*/
    	            	__( 'Setting the Describr %s PHP constants will override this feature. It&#039;s safer to use the constants since the values will be visible if they are stored in the database and it&#039;s breached. See the <em>Help</em> tab above for descriptions of these constants.', 'describr' ), 
    	            	'<abbr>SMTP</abbr>' )); ?></p>
    	            <?php
    		        if ( 'smtp' !== ( string ) $mailer ) {
    		        	?>
    			        <style<?php echo current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"'; ?>>
    				        #describr-smtp-helper {
    					        display: none;
    				        }
    			        </style>
    			        <noscript>
    				        <style<?php echo current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"'; ?>>
    					            #describr-smtp-helper {
    						        display: block;
    				            }
    			            </style>
    			        </noscript>
    			        <?php
    		        }
    	            ?>
    	            <ul id="describr-smtp-helper">
    	            	<li><label for="describr_smtp_host"><?php echo wp_kses_post(sprintf(
    	            		/*translators: %s: SMTP abbreviation.*/
    	            		__( '%s host', 'describr' ),
    	            		'<abbr>SMTP</abbr>'
    	            		)); ?> <input name="describr_smtp_host" type="text" value="<?php echo esc_attr( $smtp_host ); ?>" autocomplete="off" spellcheck="false" id="describr_smtp_host"  class="regular-text" aria-describedby="describr_smtp_host_desc" /></label>
    		            <p class="description" id="describr_smtp_host_desc"><?php echo wp_kses_post( __( 'Either a single hostname or multiple semicolon-delimited hostnames: for example, <i>smtp1.example.com;smtp2.example.com</i>.', 'describr' ) ); ?></p></li>
    		            <li><label for="describr_smtp_port"><?php echo wp_kses_post(sprintf(
    	            		/*translators: %s: SMTP abbreviation.*/
    	            		__( '%s port', 'describr' ),
    	            		'<abbr>SMTP</abbr>'
    	            		)); ?> <input name="describr_smtp_port" type="number" value="<?php echo esc_attr( ( int ) $smtp_port ); ?>" autocomplete="off" spellcheck="false" id="describr_smtp_port" /></label></li>
                        <li><label for="describr_smtp_auth"><input name="describr_smtp_auth" type="checkbox" value="true" autocomplete="off" spellcheck="false" id="describr_smtp_auth"<?php checked( 'true', $smtp_auth ); ?> /> <?php echo wp_kses_post(sprintf(
    	            		/*translators: %s: SMTP abbreviation.*/
    	            		__( 'Use %s authentication', 'describr' ),
    	            		'<abbr>SMTP</abbr>'
    	            		)); ?></label>
                        </li>
                        <li><label for="describr_smtp_encrypt_proto"><?php echo wp_kses_post(sprintf(
    	            		/*translators: %s: SMTP abbreviation.*/
    	            		__( 'What kind of encryption to use on the %s connection', 'describr' ),
    	            		'<abbr>SMTP</abbr>'
    	            		)); ?> <select name="describr_smtp_encrypt_proto" type="checkbox" value="true" autocomplete="off" spellcheck="false" id="describr_smtp_encrypt_proto">
                    	<option value=""<?php selected( '', $smtp_encrypt_proto ); ?>><?php esc_html_e( 'None', 'describr' ); ?></option>
            	        <option value="tls"<?php selected( 'tls', $smtp_encrypt_proto ); ?>>TLS</option>
            	        <option value="ssl"<?php selected( 'ssl', $smtp_encrypt_proto ); ?>>SSL</option></select></label>
                        </li>
                        <l1><label for="describr_smtp_username"><?php echo wp_kses_post(sprintf(
    	            		/*translators: %s: SMTP abbreviation.*/
    	            		__( '%s username', 'describr' ),
    	            		'<abbr>SMTP</abbr>'
    	            		)); ?> <input name="describr_smtp_username" type="text" value="<?php echo esc_attr( $smtp_username ); ?>" autocomplete="off" spellcheck="false" id="describr_smtp_username" /></label>
                        </li>
                        <li class="describr"><label for="describr_smtp_pass"><?php echo wp_kses_post(sprintf(
    	            		/*translators: %s: SMTP abbreviation.*/
    	            		__( '%s password', 'describr' ),
    	            		'<abbr>SMTP</abbr>'
    	            		)); ?> <span class="wp-pw"><input name="describr_smtp_pass" type="text" data-pw="<?php echo esc_attr( $smtp_pass ); ?>" data-reveal="1" autocomplete="off" spellcheck="false" id="describr_smtp_pass" class="regular-text" /><button type="button" class="button button-secondary describr-hide-pw wp-hide-pw hide-if-no-js" data-toggle="0" data-start-masked="1" aria-label="<?php echo esc_attr(__( 'Show password',  'describr' ))?>">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
					</button></span></label></li>
    		        </ul>
    	    	    <p><label for="describr_mailer_mail"><input type="radio" name="describr_mailer" value="mail" id="describr_mailer_mail"<?php checked( 'mail', $mailer ); ?> /> <?php echo wp_kses_post(sprintf( /*translators: %s: mail().*/ __( 'PHP&#039;s %s function', 'describr' ), '<code>mail()</code>')); ?></label></p>
    	            <p><label for="describr_mailer_sendmail"><input type="radio" name="describr_mailer" value="sendmail" id="describr_mailer_sendmail"<?php checked( 'sendmail', $mailer ); ?> /> <?php  echo esc_html_x( 'Sendmail', 'mail transfer agent', 'describr' ); ?></label></p>
    	            <p><label for="describr_mailer_qmail"><input type="radio" name="describr_mailer" value="qmail" id="describr_mailer_qmail"<?php checked( 'qmail', $mailer ); ?> /> <?php  echo esc_html_x( 'qmail', 'mail transfer agent', 'describr' ); ?></label></p>
        	    </fieldset>
    	    </td>
        </tr>
        <?php
        return $this;
    }

    /**
     * Outputs the email logo settings
     * 
     * @since 3.0
     * 
     * @return \describr\admin\Admin_Options instance
     */
    public function email_logo( $is_network_screen = false ) {
    	?>
    	<tr>
    		<th scope="row"><?php esc_html_e( 'Email Logo', 'describr' ); ?></th>
    	    <td>
    	    	<fieldset><legend class="screen-reader-text">
    	    		<?php
                    /*translators: Hidden accessibility text.*/
                    esc_html_e( 'Email Logo', 'describr' );
                    ?>
                    </legend>
                    <?php
                    if ( $is_network_screen ) {
                    	$mail_logo = get_site_option( 'describr_mail_logo', '' );
                        $mail_logo_size = get_site_option( 'describr_mail_logo_size' );
                        $mail_log_url = get_site_option( 'describr_mail_logo_url', '' );
                        $mail_logo_custom_w = get_site_option( 'describr_mail_logo_custom_size_w', '' );
                        $mail_logo_custom_h = get_site_option( 'describr_mail_logo_custom_size_h', '' );
                    } else {
                    	$mail_logo = get_option( 'describr_mail_logo', '' );
                        $mail_logo_size = get_option( 'describr_mail_logo_size' );
                        $mail_log_url = get_option( 'describr_mail_logo_url', '' );
                        $mail_logo_custom_w = get_option( 'describr_mail_logo_custom_size_w', '' );
                        $mail_logo_custom_h = get_option( 'describr_mail_logo_custom_size_h', '' );
                    }
                    
                    ?>
                    <p><label for="describr_mail_logo_site"><input type="radio" name="describr_mail_logo" value="site" id="describr_mail_logo_site"<?php checked( 'site', $mail_logo ); ?> /> <?php echo esc_html_x( 'Site', 'the website the user is using', 'describr' ); ?></label></p>
                    <div id="describr_mail_logo_size_wrap">
                    	<label for="describr_mail_logo_size"><?php echo esc_html_x( 'Size', 'photo', 'describr' ); ?> <select name="describr_mail_logo_size" id="describr_mail_logo_size" aria-describedby="describr_mail_logo_size_desc"<?php disabled( true, 'site' !== $mail_logo ); ?>>
                            <option value="full"<?php selected( 'full', $mail_logo_size ); ?>><?php echo esc_html_x( 'Full', 'photo size', 'describr' ); ?></option>
                            <option value="thumbnail"<?php selected( 'thumbnail', $mail_logo_size ); ?>><?php echo esc_html_x( 'Thumbnail', 'photo size', 'describr' ); ?></option>
                            <option value="medium"<?php selected( 'medium', $mail_logo_size ); ?>><?php echo esc_html_x( 'Medium', 'photo size', 'describr' ); ?></option>
                            <option value="medium_large"<?php selected( 'medium_large', $mail_logo_size ); ?>><?php echo esc_html_x( 'Medium large', 'photo size', 'describr' ); ?></option>
                            <option value="large"<?php selected( 'large', $mail_logo_size ); ?>><?php echo esc_html_x( 'Large', 'photo size', 'describr' ); ?></option>
                            <option value="custom"<?php selected( 'custom', $mail_logo_size ); ?>><?php echo esc_html_x( 'Custom', 'photo size', 'describr' ); ?></option>
                        </select></label> <span class="description" id="describr_mail_logo_size_desc"><?php esc_html_e( 'The sizes determine the maximum dimensions in pixels to use when adding an image to the Media Library.', 'describr' ); ?></span><br />
                        <div id="describr_mail_logo_size_custom"<?php echo 'custom' !== $mail_logo_size ? ' style="display: none"' : ''; ?>>
                        	<h4><?php esc_html_e( 'Custom Email Logo Size:', 'describr' ); ?></h4>
                    	    <label for="describr_mail_logo_custom_size_w"><?php esc_html_e( 'Width', 'describr' ); ?></label><input name="describr_mail_logo_custom_size_w" type="number" min="0" id="describr_mail_logo_custom_size_w" value="<?php echo esc_attr( $mail_logo_custom_w ); ?>" class="small-text" /><br />
                    	    <label for="describr_mail_logo_custom_size_h"><?php esc_html_e( 'Height', 'describr' ); ?></label><input name="describr_mail_logo_custom_size_h" type="number" min="0" id="describr_mail_logo_custom_size_h" value="<?php echo esc_attr( $mail_logo_custom_h ); ?>" class="small-text" />
                        </div>
                    </div>
                    <p><label for="describr_mail_logo_url" class="screen-reader-text"><?php /*translators: Hidden accessibility text.*/ esc_html_e( 'Custom email logo URL:', 'describr' ); ?></label><label><input type="radio" name="describr_mail_logo" value="custom" id="describr_mail_logo_custom"<?php checked( 'custom', $mail_logo ); ?> /> <?php esc_html_e( 'URL:', 'describr' ); ?><span class="screen-reader-text"> <?php /*translators: Hidden accessibility text.*/ esc_html_e( 'enter a custom email logo in the following field', 'describr' ); ?></span></label> <input type="url" name="describr_mail_logo_url" value="<?php echo esc_attr( $mail_log_url ); ?>" id="describr_mail_logo_url"<?php disabled( true, 'custom' !== $mail_logo ); ?> /></p>
                    <p><label for="describr_mail_logo_none"><input type="radio" name="describr_mail_logo" value="" id="describr_mail_logo_none"<?php checked( '', $mail_logo ); ?> /> <?php esc_html_e( 'None', 'describr' ); ?></label></p>
                    <?php
                    if ( $is_network_screen ) {
                    	?>
                    	<p><label for="describr_enable_site_mail_logo"><input type="checkbox" name="describr_enable_site_mail_logo" value="none" id="describr_enable_site_mail_logo"<?php checked( 1, get_site_option( 'describr_enable_site_mail_logo' ) ); ?> /> <?php esc_html_e( 'Allow individual sites to use their own email logos.', 'describr' ); ?></label></p>
                    	<?php
                    }
                    ?>
                </fieldset>
    	    </td>
    	</tr>
    	<?php
    	return $this;
    }

    /**
     * Outputs the registration form settings
     * 
     * @since 3.0
     */
    public function password() {
    	?>
    	<tr>
    		<th scope="row"><?php esc_html_e( 'Password', 'describr' ); ?></th>
    		<td>
    			<fieldset><legend class="screen-reader-text">
    	    		<?php
                    /*translators: Hidden accessibility text.*/
                    esc_html_e( 'Password', 'describr' );
                    ?>
                    </legend>
                    <?php
                    if ( ! is_multisite() ) {
                    	?>
                    	<label for="describr_registration_password_required">
                        <input type="checkbox" name="describr_registration_password_required" id="describr_registration_password_required" value="1"<?php checked( 1, describr_get_network_option( 'describr_registration_password_required' ) ); ?> /> <?php esc_html_e( 'Enable user-generated password field on the registration form', 'describr' ); ?></label>
                        <br />
                    	<?php
                    }
                    ?>
                    <label for="describr_confirm_password">
                        <input type="checkbox" name="describr_confirm_password" id="describr_confirm_password" value="1"<?php checked( 1, describr_get_network_option( 'describr_confirm_password' ) ); ?> /> <?php esc_html_e( 'Users must confirm passwords', 'describr' ); ?></label>
                    <br />
                    <label for="describr_require_strong_password"><input type="checkbox" name="describr_require_strong_password" id="describr_require_strong_password" value="1" aria-describedby="describr_password_complexity"<?php checked( 1, describr_get_network_option( 'describr_require_strong_password' ) ); ?> /> <?php esc_html_e( 'Require strong passwords', 'describr' ); ?></label>
                    <br />
                    <label for="describr_password_min_chars"><?php esc_html_e( 'Minimum number of characters', 'describr' ); echo '<span class="screen-reader-text"> ' . 
                    /*translators: Hidden accessibility text.*/
                esc_html( 'allowed in passwords', 'describr' ) . '</span>'; ?> <input type="number" name="describr_password_min_chars" id="describr_password_min_chars" value="<?php echo esc_attr( describr_get_network_option( 'describr_password_min_chars', 6 ) ); ?>"<?php disabled( 0, describr_get_network_option( 'describr_require_strong_password' ) ); ?> /></label>
                    <br />
                    <label for="describr_password_max_chars"><?php esc_html_e( 'Maximum number of characters', 'describr' ); echo '<span class="screen-reader-text"> ' . 
                    /*translators: Hidden accessibility text.*/
                esc_html( 'allowed in passwords', 'describr' ) . '</span>'; ?> <input type="number" name="describr_password_max_chars" id="describr_password_max_chars" value="<?php echo esc_attr( describr_get_network_option( 'describr_password_max_chars', 38 ) ); ?>"<?php disabled( 0, describr_get_network_option( 'describr_require_strong_password' ) ); ?> /></label>
                <br />
                <p id="describr_password_complexity" class="description<?php echo '0' === describr_get_network_option( 'describr_require_strong_password' ) ? ' screen-reader-text' : ''; ?>"><?php echo wp_kses_post(sprintf(
                    /*translators: %s: Punctation marks.*/
                    __( 'Password cannot contain identifiable information, such as names and birthdate; and must contain at least one lowercase letter, one uppercase letter, one number, and one character like %s.', 'describr' ),
                        '<code>' . implode( ' ', describr_special_chars_entity_numbers() ) . '</code>'
                )); ?></p>
                </fieldset>
    		</td>
    	</tr>
    	<?php
    }

    /**
     * Outputs the Save Plugin Data HTML
     * 
     * @since 3.0
     */
    public function save_plugin_data() {
    	?>
    	<table class="form-table" role="presentation">
    		<tr>
    			<td><label for="describr_save_plugin_data"><input name="describr_save_plugin_data" type="checkbox" id="describr_save_plugin_data" value="1" <?php checked( 1, get_option( 'describr_save_plugin_data' ) ); ?> /> <?php echo esc_html(sprintf(
                /*translators: 1: The plugin name.*/
                __( 'Save user data saved by %1$s when %1$s is deleted', 'describr' ), 
                DESCRIBR
                )); ?></label></td>
            </tr>
        </table>
    	<?php
    }
}