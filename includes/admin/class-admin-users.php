<?php
namespace describr\admin;

/**
 * Admin_Users class
 *
 * @package Describr
 * @since 3.0
 */
class Admin_Users {
	/**
	 * Whether the current Users list table is for Multisite
	 *
	 * @since 3.0
	 * @var bool
	 */
	public $is_site_users;

	/**
	 * Site ID to generate the Users list table for
	 *
	 * @since 3.0
	 * @var int
	 */
	public $site_id;

	/**
	 * Whether a Registered column should be
	 * added to the current Users list table
	 *
	 * @since 3.0
	 * @var bool
	 */
	public $is_registered_column = false;

	/**
	 * Stores screen IDs for screens showing
	 * a Users list table
	 *
	 * @since 3.0
	 * @var bool
	 */
	public static $screen_ids = array( 'users' );
    
    /**
	 * Admin_Users constructor
	 * 
	 * @since 3.0
	 */
	public function __construct() {
		//network/site-users.php uses {@see 'WP_Users_List_Table'} class
		//network/users.php uses {@see 'WP_MS_Users_List_Table'} class
        
        if ( is_multisite() ) {
			static::$screen_ids[] = 'site-users-network';
		}

        add_action( 'admin_head', array( $this, 'screen_specific_filters' ), 10 );
        
        if ( isset( $_REQUEST['action'] ) ) {
        	add_action( 'load-users.php', array( $this, 'handle_bulk_actions_init' ) ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

        	if ( is_multisite() ) {
        		add_action( 'load-site-users.php', array( $this, 'handle_bulk_actions_init' ) ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        	}
        }

        if ( is_multisite() ) {
        	//Applied by {@see 'WP_MS_Users_List_Table'} class
			add_filter( 'wpmu_users_columns', array( $this, 'get_columns' ), 10, 1 );

			//Applied by {@see 'WP_MS_Users_List_Table'} class
			add_filter( 'ms_user_row_actions', array( $this, 'add_send_confirmation_email_link' ), 10, 2 );
        }

		add_filter( 'users_list_table_query_args', array( $this, 'add_users_list_table_query_args' ), 10, 1 );
		add_filter( 'user_row_actions', array( $this, 'add_send_confirmation_email_link' ), 10, 2 );
		add_filter( 'manage_users_custom_column', array( $this, 'customize_column' ), 10, 3 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_get_views_link_style' ), 10, 0 );

		if ( is_network_admin() ) {
            add_action( 'network_admin_notices', array( $this, 'display_admin_notice' ), 10 );
        } elseif ( is_user_admin() ) {
            add_action( 'user_admin_notices', array( $this, 'display_admin_notice' ), 10 );
        } else {
            add_action( 'admin_notices', array( $this, 'display_admin_notice' ), 10 );
        }
	}
    
    /**
     * Adds the callback to handle Users bulk actions
     * 
     * @since 3.0
     */
    public function handle_bulk_actions_init() {
    	$current_screen = get_current_screen();
            
        if ( ! $current_screen ) {
            return;
        }
             
        $screen_id = $current_screen->id;

    	if ( is_multisite() ) {
			//network/site-users.php or network/users.php
			add_filter( "handle_network_bulk_actions-{$screen_id}", array( $this, 'handle_bulk_actions' ), 10, 3 );//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

    	add_filter( "handle_bulk_actions-{$screen_id}", array( $this, 'handle_bulk_actions' ), 10, 3 );//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
    }

    /**
     * Adds callbacks to filters applied on specific screens
     * 
     * @since 3.0
     */
    public function screen_specific_filters() {
    	global $pagenow, $hook_suffix;

    	if ( is_multisite() ) {
			$current_screen = get_current_screen();
            
            if ( $current_screen && ! in_array( $current_screen->id, static::$screen_ids, true ) ) {
            	static::$screen_ids[] = $current_screen->id;
            }
		}
        
        if ( ! str_contains( $hook_suffix, 'user' ) && ! str_contains( $pagenow, 'user' ) ) {
        	return;
        }

		foreach ( static::$screen_ids as $screen_id ) {
			add_filter( "views_{$screen_id}", array( $this, 'get_views' ), 10, 1 );
			add_filter( "manage_{$screen_id}_columns", array( $this, 'get_columns' ), 10, 1 );
			add_filter( "manage_{$screen_id}_sortable_columns", array( $this, 'get_sortable_columns' ), 10, 1 );
			add_filter( "bulk_actions-{$screen_id}", array( $this, 'get_bulk_actions' ), 10, 1 );//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}
    }
    
    /**
     * Enqueues Styles for the "Awaiting review" and "Inacive" view links above the
     * List Users Table on the Users Screen or Site Users Network Screen.
     * 
     * @since 3.0
     */
    public function enqueue_get_views_link_style() {
    	global $pagenow;
        
        $screen = get_current_screen();

        if ( $screen ) {
        	$screen_id = $screen->id;
        } else {
        	$screen_id = '';
        }

    	if ( ( $screen_id && in_array( $screen_id, self::$screen_ids, true ) ) || str_contains( $pagenow, 'user' ) ) {
    	    $style = '
    	    .awaiting_review a:link,
            .awaiting_review a:visited
             {
                color: #ffae19;
            }
            
            .awaiting_review a:hover {
                color: #ffa500;
            }
            
            .awaiting_review a:active {
                color: #ffae19;
            }

            .awaiting_review a.current,
            .awaiting_review a.current:hover,
            .awaiting_review a.current:active {
                color: #ffa500;
            }
            
            .inactive_users a:link,
            .inactive_users a:visited {
            	color: #6200a9;
            }

            .inactive_users a:hover {
            	color: #4b0082;
            }
            
            .inactive_users a.current,
            .inactive_users a.current:hover,
            .inactive_users a.current:active {
                color: #4b0082;
            }';

            $handle = 'describr-users-get-views-link-style';
            
            wp_register_style( $handle, false );
            wp_add_inline_style( $handle, $style );
            wp_enqueue_style( $handle );        
        }
    }
    
    /**
     * Adds views to the list of available Users views
     *
     * @since 3.0
     *
     * @param array $views An array of available Users views
     * @return array
     */
    public function get_views( $views ) {
    	$this->is_site_users = 'site-users-network' === get_current_screen()->id;
        
        if ( $this->is_site_users ) {
        	$this->site_id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;
        }

        if ( ! wp_is_large_user_count() ) {
        	$pending_users_count = $this->get_pending_inactive_users_count();
        	$inactive_users_count = $this->get_pending_inactive_users_count( 'inactive' );
        } else {
        	$pending_users_count = get_transient( 'describr_pending_users_count' );

        	if ( false === $pending_users_count ) {
        		$pending_users_count = $this->get_pending_inactive_users_count();
        		set_transient( 'describr_pending_users_count', $pending_users_count, 30 * DAY_IN_SECONDS );
        	}
        	
        	$inactive_users_count = get_transient( 'describr_inactive_users_count' );

        	if ( false === $inactive_users_count ) {
        		$inactive_users_count = $this->get_pending_inactive_users_count( 'inactive' );
        		set_transient( 'describr_inactive_users_count', $inactive_users_count, 30 * DAY_IN_SECONDS );
        	}
        }
        
        $user_count_links = array();

        if ( $this->is_site_users ) {
			$url = 'site-users.php?id=' . $this->site_id;
		} else {
			$url = 'users.php';
		}
        
        if ( 0 < $pending_users_count ) {
        	$label = sprintf(
				/*translators: %s: Number of users.*/
				_nx(
					'Awaiting review <span class="count">(%s)</span>',
					'Awaiting review <span class="count">(%s)</span>',
					$pending_users_count,
					'users',
					'describr'
				),
				number_format_i18n( $pending_users_count )
			);

			$user_count_links['awaiting_review'] = array(
				'url'     => add_query_arg( 'status', 'awaiting_review', $url ),
				'label'   => $label,
				'current' => ( isset( $_REQUEST['status'] ) && 'awaiting_review' === sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) ),
			);
        }

        if ( 0 < $inactive_users_count ) {
        	$label = sprintf(
				/*translators: %s: Number of users.*/
				_nx(
					'Inactive <span class="count">(%s)</span>',
					'Inactive <span class="count">(%s)</span>',
					$inactive_users_count,
					'users',
					'describr'
				),
				number_format_i18n( $inactive_users_count )
			);

			$user_count_links['inactive_users'] = array(
				'url'     => add_query_arg( 'active', 'false', $url ),
				'label'   => $label,
				'current' => ( isset( $_REQUEST['active'] ) && 'false' === sanitize_text_field( wp_unslash( $_REQUEST['active'] ) ) ),
			);
        }

        if ( $user_count_links ) {
        	foreach ( $user_count_links as $class => $view ) {
        		$current = '';
        		
        		if ( $view['current'] ) {
        			$current = ' class="current" aria-current="page"';
        		}

        		$views[ $class ] = sprintf(
        			'<a href="%1$s"%2$s>%3$s</a>',
        			esc_url( $view['url'] ),
        			$current,
        			wp_kses_post( $view['label'] )        			
        		);
        	}
        }

    	return $views;
    }

    /**
	 * Retrieves the numbers of pending and inactive users
	 *
	 * @since 3.0
	 * 
	 * @return array An array of the numbers of pending and inactive users
	 */
    public function get_pending_inactive_users_count( $type = 'pending' ) {
    	global $wpdb;
        
        $sql = "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} INNER JOIN {$wpdb->users} ON user_id = ID WHERE meta_key = ";
        $sql .= 'pending' === $type ? "'account_status' AND meta_value = 'pending'" : "'active_by_admin' AND meta_value = 'false'";

        if ( $this->is_site_users ) {
        	$site_id = $this->site_id;
        } elseif ( is_multisite() && ! is_network_admin() ) {
        	$site_id = get_current_blog_id();
        }

    	if ( isset( $site_id ) ) {
        	$sql .= " AND user_id IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s)";

            $cap_key = $wpdb->get_blog_prefix( $site_id ) . 'capabilities';
        }
        
        if ( isset( $cap_key ) ) {
        	$users_count = $wpdb->get_var( $wpdb->prepare( $sql, $cap_key ) );
        } else {
        	$users_count = $wpdb->get_var( $sql );
        }

        return (int) $users_count; 
    }

    /**
	 * Adda our columns to the list of columns for the list table
	 *
	 * @since 3.0
	 * 
	 * @return array Array of column titles keyed by their column name
	 * @return array
	 */
	public function get_columns( $columns ) {
		$columns['status']      = _x( 'Status', 'user', 'describr' );
		$columns['active_user'] = _x( 'Active', 'user', 'describr' );
        
        if ( ! isset( $columns['registered'] ) ) {
        	$this->is_registered_column = true;
        	$columns['registered'] = _x( 'Registered', 'user', 'describr' );
        }

        return $columns;
    }

    /**
	 * Adds our sortable columns to the list of sortable columns for the list table
	 *
	 * @since 3.0
	 * 
	 * @param array Array of sortable columns 
	 * @return array
	 */
	public function get_sortable_columns( $columns ) {
		if ( ! isset( $columns['registered'] ) ) {
			$this->is_registered_column = true;
			$columns['registered']   = array( 'registered', false, _x( 'Registered', 'user', 'describr' ), __( 'Table Ordered by User Registered Date.', 'describr' ), );
		}

		return $columns;
	}
    
    /**
	 * Retrieves custom columns output
	 *
	 * @since 3.0
	 *
	 * @param string $output      Custom column output
	 * @param string $column_name Column name
	 * @param int    $user_id     ID of the currently-listed user
	 * @param string
	 */
    public function customize_column( $output, $column_name, $user_id ) {
    	if ( 'status' === $column_name ) {
    		static $status_labels = null;

    		if ( is_null( $status_labels ) ) {
    			$status_labels = describr_account_status_labels();
    		}

    		$user = get_userdata( $user_id );
    		$status = $user->account_status;
            
            if ( ! empty( $status_labels[ $status ] ) ) {
            	$output = esc_html( $status_labels[ $status ] );
            } else {
                $output = '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . esc_html_x( 'Unknown', 'user status', 'describr' ) . '</span>';
            }
    	} elseif ( 'active_user' === $column_name ) {
    		if ( describr_is_user_active( $user_id ) ) {
    			$title = _x( 'Active', 'user', 'describr' );

    			$output = '<span aria-hidden="true" class="dashicons dashicons-yes" title="' . esc_attr( $title ) . '"></span>';
    			$output .= '<span class="screen-reader-text">' . esc_html( $title ) . '</span>';
    		} else {
    			$title = _x( 'Inactive', 'user', 'describr' );

    			$output = '<span aria-hidden="true" class="dashicons dashicons-no" title="' . esc_attr( $title ) . '"></span>';
    			$output .= '<span class="screen-reader-text">' . esc_html( $title ) . '</span>';
    		}
    	} elseif ( 'registered' === $column_name && $this->is_registered_column ) {
    		$user = get_userdata( $user_id );   
    			
    		$output = wp_date( $this->get_date_format(), strtotime( $user->user_registered ), wp_timezone() );
    	}

    	return $output;
    }
    
    /**
	 * Retrieves the fully translated column date format
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
    public function get_date_format() {
    	global $mode;

		if ( 'list' === $mode ) {
			$date = /*translators: Date format for exact date, mainly about timezones, see https://www.php.net/manual/datetime.format.php.*/ __( 'Y/m/d', 'describr' );
		} else {
			$date = /*translators: Date format for exact date and time, mainly about timezones, see https://www.php.net/manual/datetime.format.php.*/ __( 'Y/m/d g:i:s a', 'describr' );
		}

		return $date;
    }

    /**
	 * Add meta queries to the users list table query args
	 *
	 * @since 3.0
	 *
	 * @param array $args Arguments passed to WP_User_Query to retrieve items for the current
	 *                    users list table
	 * @return array
	 */
	public function add_users_list_table_query_args( $args ) {
		$meta_query = array();
        
        //Query users by account status
		if ( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], array( 'awaiting_review' ), true ) ) {
			$meta_query[] = array(
				'key'     => 'account_status',
				'value'   => 'pending',
				'compare' => '=',
			);
		}

        //Inactive users query
		if ( isset( $_REQUEST['active'] ) && in_array( $_REQUEST['active'] , array( 'false' ), true ) ) {
			$meta_query[] = array(
				'key'     => 'active_by_admin',
				'value'   => $_REQUEST['active'],
				'compare' => '=',
			);
		}
		
		if ( $meta_query ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query'] = $meta_query;
		}

		return $args;
	}

    /**
     * Adds our bulk actions to list of bulk actions
     * 
     * @since 3.0
     * 
     * @param array $bulk_actions Bulk actions
     * @param array
     */
	public function get_bulk_actions( $bulk_actions ) {
		$bulk_actions['confirmaccount'] = _x( 'Send account confirmation', 'user', 'describr' );
		$bulk_actions['approve']        = __( 'Approve membership', 'describr' );
		$bulk_actions['reject']         = __( 'Reject membership', 'describr' );
		$bulk_actions['pending']        = __( 'Membership pending', 'describr' );
		$bulk_actions['activate']       = _x( 'Activate', 'user', 'describr' );
		$bulk_actions['deactivate']     = _x( 'Deactivate', 'user', 'describr' );

		return $bulk_actions;
	}

	/**
	 * Adds actions "Send account confirmation" link in the Users list table
	 *
	 * @since 3.0
	 *
	 * @param array   $actions     An array of action links to be displayed
	 * @param WP_User $user_object WP_User object for the currently listed user
	 * @return array Action links
	 */
	public function add_send_confirmation_email_link( $actions, $user ) {
    	if ( ! is_object( $user ) ) {
		    $user = get_userdata( $user );
	    }

	    if ( ! $user || ! $user->exists() ) {
		    return $actions;
	    }
	    
	    if ( get_current_user_id() === $user->ID || ! current_user_can( 'edit_user', $user->ID ) ) {
	    	return $actions;
	    }

	    $allow = true;
	
	    if ( is_multisite() && is_user_spammy( $user ) ) {
		    $allow = false;
	    }

	    /**
	     * Filters whether to allow actions "Send account confirmation"
	     * link in the Users list table
	     *
	     * @since 2.7.0
	     *
	     * @param bool $allow   Whether to allow an actions "Send account confirmation"
	     *                      link in the Users list table
	     * @param int  $user_id User ID
	     */
	    if ( true !== apply_filters( 'describr_allow_account_confirmation', $allow, $user->ID ) ) {
	    	return $actions;
	    }
        
        if ( $this->is_site_users ) {
			$url = "site-users.php?id={$this->site_id}&amp;";
		} else {
			$url = 'users.php?';
		}

        $actions['confirmaccount'] = "<a href='" . wp_nonce_url( "{$url}action=confirmaccount&amp;users={$user->ID}", 'bulk-users' ) . "'>" . esc_html__( 'Send account confirmation', 'describr' ) . '</a>';

    	return $actions;
    }

    /**
	 * Handles bulk action taken on the Users or Site Users screen
	 *
	 * @since 3.0
	 *
	 * @param string $sendback The redirect URL
	 * @param string $doaction The action being taken
	 * @param array  $user_ids The user IDs to take the action on
	 */
	public function handle_bulk_actions( $sendback, $doaction, $user_ids ) {
		$sendback = remove_query_arg( array( 'update_', 'user_count' ), $sendback );
        
        if ( ! in_array( $doaction, array( 'approve', 'reject', 'pending', 'activate', 'deactivate', 'confirmaccount' ), true ) ) {
        	return $sendback;
        }

		if ( ! current_user_can( 'edit_users' ) ) {
			return add_query_arg( 'update_', "{$doaction}_no_edit_users", $sendback );
		}

		$user_ids = array_filter( array_unique( (array) $user_ids ), function( $id ) {
			return 0 < $id;
		});

		if ( ! $user_ids ) {
			return $sendback;
		}
        
        $update = $doaction;

		$user_count = 0;

        $current_user = wp_get_current_user();
        
        foreach ( $user_ids as $id ) {
            if ( ! current_user_can( 'edit_user', $id ) ) {
        		$update = "{$doaction}_no_edit_user";
				break;
			}
		}
        
        if ( $update !== $doaction ) {
        	return add_query_arg( 'update_', $update, $sendback );
        }

        global $wpdb;

        foreach ( $user_ids as $id ) {
        	if ( $current_user->ID === $id ) {
        		$update = "{$doaction}_no_admin";
        		continue;
        	}
            
            //Some of these operations could be carried out by the *_user_meta functions, but querying the database directly is more optimal in these cases.
            switch ( $doaction ) {
			    case 'approve':
			        if ( $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->usermeta SET meta_value = 'approved' WHERE user_id = %s AND meta_key = 'account_status' AND meta_value != 'approved'", $id ) ) ) {
			        	$user = get_userdata( $id );
                        describr()->user()->approve( $user->user_email );

			        	describr_delete_transient_once( 'describr_pending_users_count' );

			        	$user_count++;
			        }
				    break;
			    case 'reject':
				    if ( $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->usermeta SET meta_value = 'rejected' WHERE user_id = %s AND meta_key = 'account_status' AND meta_value != 'rejected'", $id ) ) ) {
			        	$user = get_userdata( $id );

			        	describr()->user()->rejected( $user->user_email );

			        	describr_delete_transient_once( 'describr_pending_users_count' );

			        	$user_count++;
			        }
				    break;
			    case 'pending':
				    if ( $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->usermeta SET meta_value = 'pending' WHERE user_id = %s AND meta_key = 'account_status' AND meta_value != 'pending'", $id ) ) ) {
			        	$user = get_userdata( $id );

			        	describr()->user()->pending( $user->user_email );
                        
                        describr_delete_transient_once( 'describr_pending_users_count' );
                        
                        $user_count++;
			        }
				    break;
			    case 'activate':
			        if ( $wpdb->update( 
			        	    $wpdb->usermeta, 
			        	    array( 
			        		    'meta_value' => 'true' 
			        	    ), 
			        	    array( 
			        	    	'user_id'    => $id,
			        		    'meta_key'   => 'active_by_admin', 
			        		    'meta_value' => 'false', 
			        	    ) 
			        	) 
			        ) {
			        	$user = get_userdata( $id );

			        	describr()->user()->active( $user->user_email );
                        
                        describr_delete_transient_once( 'describr_inactive_users_count' );

			        	$user_count++;
			        }
				    break;
			    case 'deactivate':
			        switch ( $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %s AND meta_key = 'active_by_admin' LIMIT 1", $id ) ) ) {
			        	case 'false':
			        	    //Already deactivated, no further action needed.
			        		break;
			        	case 'true': //Previously activated, so deactivate.
			        	    if ( $wpdb->update( 
			        	    	    $wpdb->usermeta, 
			        	            array( 
			        		            'meta_value' => 'false' 
			        	            ), 
			        	            array( 
			        	    	        'user_id'    => $id,
			        		            'meta_key'   => 'active_by_admin', 
			        		            'meta_value' => 'true', 
			        		        ) 
			        	        ) 
				            ) {
			        	        $user = get_userdata( $id );

			        	        describr()->user()->inactive( $user->user_email );
                        
                                describr_delete_transient_once( 'describr_inactive_users_count' );
                                $user_count++;
			                }
			                break;
			        	default: //Never previously deactivated, so do so.
			        	    if ( $wpdb->insert(
			        	            $wpdb->usermeta,
			        	            array(
			        	    	        'user_id'    => $id,
			        	    	        'meta_key'   => 'active_by_admin',
			        	    	        'meta_value' => 'false',
			        	            )
			                    ) 
			                ) {
			        	        $user = get_userdata( $id );

			        	        describr()->user()->inactive( $user->user_email );
                        
                                describr_delete_transient_once( 'describr_inactive_users_count' );
                                
			        	        $user_count++;
			                }
			        		break;
			        }
				    break;
				case 'confirmaccount':
				    $user = get_userdata( $id );
                    
                    $url = describr()->permalinks()->confirmation_url( $user );

                    if ( $url ) {
                    	describr()->user()->confirm_account( $user->user_email, $url );

                    	$user_count++;
                    }
				    break;
		    }
        }
		
		return add_query_arg( 
			array( 
				'update_'    => $update,
				'user_count' => $user_count,
			), 
			$sendback 
		);
	}
    
    /**
     * Displays bulk action notices on Users or Site Users screen
     * 
     * @since 3.0
     */
    public function display_admin_notice() {
	    if ( ! empty( $_GET['update_'] ) ) {
	    	switch ( sanitize_text_field( wp_unslash( $_GET['update_'] ) ) ) {
                case 'approve':
                    $user_count = isset( $_GET['user_count'] ) ? (int) $_GET['user_count'] : 0;
                            
                    if ( 1 === $user_count ) {
                        $message = __( 'User approved.', 'describr' );
                    } else {
                        /*translators: %s: Number of users.*/
                        $message = _n( '%s user approved.', '%s users approved.', $user_count, 'describr' );
                    }

                    $message = sprintf( $message, number_format_i18n( $user_count ) );
                    wp_admin_notice(
                        $message,
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'reject':
                    $user_count = isset( $_GET['user_count'] ) ? (int) $_GET['user_count'] : 0;
                            
                    if ( 1 === $user_count ) {
                        $message = __( 'User rejected.', 'describr' );
                    } else {
                        /*translators: %s: Number of users.*/
                        $message = _n( '%s user rejected.', '%s users rejected.', $user_count, 'describr' );
                    }

                    $message = sprintf( $message, number_format_i18n( $user_count ) );
                    wp_admin_notice(
                        $message,
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'pending':
                    $user_count = isset( $_GET['user_count'] ) ? (int) $_GET['user_count'] : 0;
                            
                    if ( 1 === $user_count ) {
                        $message = __( 'User membership awaiting review.', 'describr' );
                    } else {
                        /*translators: %s: Number of users.*/
                        $message = _n( '%s user membership awaiting review.', '%s users membership awaiting review.', $user_count, 'describr' );
                    }

                    $message = sprintf( $message, number_format_i18n( $user_count ) );
                    wp_admin_notice(
                        $message,
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'activate':
                    $user_count = isset( $_GET['user_count'] ) ? (int) $_GET['user_count'] : 0;
                            
                    if ( 1 === $user_count ) {
                        $message = __( 'User activated.', 'describr' );
                    } else {
                        /*translators: %s: Number of users.*/
                        $message = _n( '%s user activated.', '%s users activated.', $user_count, 'describr' );
                    }

                    $message = sprintf( $message, number_format_i18n( $user_count ) );
                    wp_admin_notice(
                        $message,
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'deactivate':
                    $user_count = isset( $_GET['user_count'] ) ? (int) $_GET['user_count'] : 0;
                            
                    if ( 1 === $user_count ) {
                        $message = __( 'User deactivated.', 'describr' );
                    } else {
                        /*translators: %s: Number of users.*/
                        $message = _n( '%s user deactivated.', '%s users deactivated.', $user_count, 'describr' );
                    }

                    $message = sprintf( $message, number_format_i18n( $user_count ) );
                    wp_admin_notice(
                        $message,
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;

                case 'confirmaccount':
                    $user_count = isset( $_GET['user_count'] ) ? (int) $_GET['user_count'] : 0;
                            
                    if ( 1 === $user_count ) {
                        $message = __( 'User account confirmation sent.', 'describr' );
                    } else {
                        /*translators: %s: Number of users.*/
                        $message = _n( '%s user account confirmation sent.', '%s users account confirmation sent.', $user_count, 'describr' );
                    }

                    $message = sprintf( $message, number_format_i18n( $user_count ) );
                    wp_admin_notice(
                        $message,
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'approve_no_edit_users':
                    wp_admin_notice(
                        __( 'You are not allowed to approve users.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'reject_no_edit_users':
                    wp_admin_notice(
                        __( 'You are not allowed to reject membership.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'pending_no_edit_users':
                    wp_admin_notice(
                        __( 'You are not allowed to moderate users.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'activate_no_edit_users':
                    wp_admin_notice(
                        __( 'You are not allowed to activate users.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'deactivate_no_edit_users':
                    wp_admin_notice(
                        __( 'You are not allowed to deactivate users.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'confirmaccount_no_edit_users':
                    wp_admin_notice(
                        __( 'You are not allowed to edit users.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'approve_no_edit_user':
                    wp_admin_notice(
                        __( 'You are not allowed to approve a user&#039;s membership.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'reject_no_edit_user':
                    wp_admin_notice(
                        __( 'You are not allowed to reject a user&#039;s membership.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'pending_no_edit_user':
                    wp_admin_notice(
                        __( 'You are not allowed to moderate a user.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'activate_no_edit_user':
                    wp_admin_notice(
                        __( 'You are not allowed to activate a user.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'deactivate_no_edit_user':
                    wp_admin_notice(
                        __( 'You are not allowed to deactivate a user.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'confirmaccount_no_edit_user':
                    wp_admin_notice(
                        __( 'You are not allowed to edit a user.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'approve_no_admin':
                    wp_admin_notice(
                        __( 'You cannot approve the current user&#039;s membership.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    wp_admin_notice(
                        __( 'Other user&#039;s memberships have been approved.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'reject_no_admin':
                    wp_admin_notice(
                        __( 'You cannot reject the current user&#039;s membership.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    wp_admin_notice(
                        __( 'Other users&#039; memberships have been rejected.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'pending_no_admin':
                    wp_admin_notice(
                        __( 'You cannot moderate the current user.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    wp_admin_notice(
                        __( 'Other users have been moderated.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'activate_no_admin':
                    wp_admin_notice(
                        __( 'You cannot activate the current user.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    wp_admin_notice(
                        __( 'Other users have been activated.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'deactivate_no_admin':
                    wp_admin_notice(
                        __( 'You cannot deactivate the current user.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    wp_admin_notice(
                        __( 'Other users have been deactivated.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'confirmaccount_no_admin':
                    wp_admin_notice(
                        __( 'You cannot confirm the current user.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    wp_admin_notice(
                        __( 'Other users have been confirmed.', 'describr' ),
                        array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
            }
	    }	
	}
}