<?php
namespace describr\admin;

/**
 * Admin_Roles_List_table class
 *
 * @package Describr
 * @since 3.0
 */

/**
 * WP_List_Table class.
 * 
 * Base class for displaying a list of items in an ajaxified HTML table.
 *
 * @since 3.0
 */
class Admin_Roles_List_table extends \WP_List_Table {
	/**
	 * Stores roles
	 * 
     * @since 3.0
	 * @var array 
	 */
	public $roles = array();

	/**
	 * Admin_Roles_List_table constructor
	 * 
     * @since 3.0 
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'singular' => 'role',
				'plural'   => 'roles',
				'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);
	}

	/**
	 * Checks the current user's permissions.
	 *
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function ajax_user_can() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Prepares the users list for display.
	 *
	 * @since 3.0
	 *
	 * @global string $role
	 * @global string $usersearch
	 */
	public function prepare_items() {
		global $role, $usersearch;

		$usersearch = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

		$role = isset( $_REQUEST['role'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['role'] ) ) : '';

		$per_page       = 'describr_roles_per_page';
		$roles_per_page = $this->get_items_per_page( $per_page );

		$paged = $this->get_pagenum();

        $offset = ( $paged - 1 ) * $roles_per_page;

		$order = isset( $_REQUEST['order'] ) ? wp_kses_post( wp_unslash( trim( $_REQUEST['order'] ) ) ) : 'asc';
		
		$editable_roles = ( array ) get_editable_roles();

		if ( 'asc' === $order ) {
			ksort( $editable_roles );
		} else {
			krsort( $editable_roles );
		}
        
        if ( mb_strlen( $usersearch ) ) {
        	array_walk( $editable_roles, function ( &$role_info, $role_name ) use($usersearch) {
        		$search = preg_quote( $usersearch, '/' );
        		
        		if ( ! preg_match( "/^$search/iu", $role_info['name'] ) ) {
        			$role_info = array();
        		}
        	});
        }

        $editable_roles = array_filter( $editable_roles );

		$this->items = array_slice( $editable_roles, $offset, $roles_per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items  = count( $editable_roles ),
				'per_page'    => $roles_per_page,
			)
		);
	}

	/**
	 * Outputs 'no roles' message.
	 *
	 * @since 3.0
	 */
	public function no_items() {
		esc_html_e( 'No roles found.', 'describr' );
	}
    
    /**
	 * Retrieves an associative array of bulk actions available on this table.
	 *
	 * @since 3.0
	 *
	 * @return array Array of bulk action labels keyed by their action.
	 */
	protected function get_bulk_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}

		return array(
			'delete'     => _x( 'Delete', 'user role', 'describr' ),
			'adredirect' => _x( 'Add redirect link', 'user role', 'describr' ),
		);
	}
    
    /**
	 * Outputs the controls to allow extra table nav to enable bulk changes.
	 *
	 * @since 3.0
	 *
	 * @param string $which Whether this is being invoked above ("top")
	 *                      or below the table ("bottom").
	 */
	protected function extra_tablenav( $which ) {
		/**
		 * Fires immediately following the closing "actions" div 
		 * in the tablenav for the Roles list table.
		 *
		 * @since 3.0
		 *
		 * @param string $which The location of the extra table nav markup: "top" or "bottom".
		 */
		do_action( 'describr_manage_roles_extra_tablenav', $which );
	}
	
	/**
	 * Gets a list of columns for the list table.
	 *
	 * @since 3.0
	 *
	 * @return string[] Array of column titles keyed by their column name.
	 */
	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'role'     => __( 'Role', 'describr' ),
			'users'    => __( 'Users', 'describr' ),
			'caps'     => __( 'Capabilities', 'describr' ),
		);
	}

	/**
	 * Gets a list of sortable columns for the list table.
	 *
	 * @since 3.0
	 *
	 * @return array Array of sortable columns.
	 */
	protected function get_sortable_columns() {
		return array(
			'role' => array( 'role', false, __( 'Role', 'describr' ), __( 'Table ordered by Role.', 'describr' ), 'asc' )
		);
	}

	/**
	 * Generates the list table rows.
	 *
	 * @since 3.0
	 */
	public function display_rows() {
		foreach ( $this->items as $role => $role_info ) {
			$user_search = new \WP_User_Query(array(
				'role'    => $role,
				'fields'  => 'ID',
		    ));
            
		    echo "\n\t" . $this->single_row( $role_info, $role, $user_search->get_total() );
		}
	}

	/**
	 * Generates HTML for a single row on the ?page=describr-roles admin panel.
	 * 
	 * @since 3.0
	 *
	 * @param array   $role_info      The current role info.
	 * @param string  $role           The current role
	 * @param int     $num_role_users Optional. User count to display for this role. Defaults
	 *                             to zero, as in, a new role has user assigned.
	 * @return string Output for a single row.
	 */
	public function single_row( $role_info, $role = '', $num_role_users = 0 ) {
		static $default_role = null;

		if ( null === $default_role ) {
			$default_role = get_option( 'default_role' );
		}

		if ( '' === $role ) {
			$role = $role_info['name'];
		}
        
        $id = str_replace( ' ', '_', $role );

        $is_default_role = ( $default_role === $role );

		$display_name = esc_html( translate_user_role( $role_info['name'] ) );
		// Set up the hover actions for this user.
		$actions     = array();
		$checkbox    = '';
		    
        $host = wp_unslash( $_SERVER['HTTP_HOST'] );
        $uri = remove_query_arg( array( 'action', 'roles', 'role', 'order', 'orderby', 's' ), wp_unslash( $_SERVER['REQUEST_URI'] ) );
        $current_url = set_url_scheme( '//' . $host . $uri );
        
        $role_ = rawurlencode( $role );
        
        $alt_url = add_query_arg(
            array( 
                'role'     => $role_, 
            	'_wpnonce' => wp_create_nonce( 'bulk-roles' ),
            ),
            $current_url
        );

        //Check if the user for this row is editable
		if ( current_user_can( 'manage_options' ) ) {
			// Set up the user editing link.
			$edit_link = esc_url(
				add_query_arg(
					array( 
						'action'           => 'edit',
						'role'             => $role_,
						'_wp_http_referer' => rawurlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
				    ),
					$current_url
				)
			);

			$edit  = "<strong><a href=\"{$edit_link}\">{$display_name}</a></strong><br />";
			$actions['edit'] = '<a href="' . $edit_link . '">' . esc_html__( 'Edit', 'describr' ) . '</a>';

            if ( ! in_array( $role, describr_defined_roles(), true ) ) {
            	$actions['delete'] = '<a class="submitdelete" href="' . esc_url( add_query_arg( 'action', 'delete', $alt_url ) ) . '">' . esc_html__( 'Delete', 'describr' ) . '</a>';
            }
            
            $actions['adcap'] = '<a href="' . esc_url( add_query_arg( 'action', 'adcap', $alt_url ) ) . '">' . esc_html__( 'Add capabilities', 'describr' ) . '</a>';
            
            $actions['adredirect'] = '<a href="' . esc_url( add_query_arg( 'action', 'adredirect', $alt_url ) ) . '">' . esc_html__( 'Add redirect link', 'describr' ) . '</a>';
			/**
			 * Filters the action links displayed under each role in the Roles list table.
			 *
			 * @since 2.8.0
			 *
			 * @param string[] $actions   An array of action links to be displayed.
			 *                            Default 'Edit', 'Delete'.
			 * @param array    $role_info Role info for the currently listed role.
			 */
			$actions = apply_filters( 'describr_role_row_actions', $actions, $role_info );

			// Set up the checkbox (because the role is editable, otherwise it's empty).
			$checkbox = sprintf(
				'<input type="checkbox" name="roles[]" id="role_%1$s" class="%2$s" value="%2$s" />' .
				'<label for="role_%1$s"><span class="screen-reader-text">%3$s</span></label>',
				$id,
				esc_attr( $role ),
				/* translators: Hidden accessibility text. %s: User role. */
				sprintf( __( 'Select %s', 'describr' ), $role )
			);

		} else {
			$edit = "<strong>{$display_name}</strong>";
		}

		if ( $is_default_role ) {
			$actions['default_role'] = '<strong style="color:#1d2327;">' . __( 'New User Default Role', 'describr' ) . '</strong>';
		}

		$row = "<tr id='user-$role'>";

		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}
			if ( in_array( $column_name, array( 'caps', 'users' ), true ) ) {
				$classes .= ' num'; // Special case for that column.
			}

			if ( in_array( $column_name, $hidden, true ) ) {
				$classes .= ' hidden';
			}

			$data = 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '"';

			$attributes = "class='$classes' $data";

			if ( 'cb' === $column_name ) {
				$row .= "<th scope='row' class='check-column'>$checkbox</th>";
			} else {
				$row .= "<td $attributes>";
				switch ( $column_name ) {
					case 'role':
						$row .= $edit;
						break;
					case 'users':
					    if ( $num_role_users > 0 ) {
							$row .= sprintf(
								'<a href="%s" class="edit"><span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
								"users.php?role={$role_}",
								number_format_i18n( $num_role_users ),
								sprintf(
									/* translators: Hidden accessibility text. %s: Number of users. */
									_n( '%s user assigned to this role', '%s users assigned to this role', $num_role_users, 'describr' ),
									number_format_i18n( $num_role_users )
								)
							);
						} else {
							$row .= 0;
						}
						break;
					case 'caps':
					    $num_caps = count( $role_info['capabilities'] );

						if ( $num_caps > 0 ) {
							$edit_link = esc_url( add_query_arg( 'action', 'adcap', $alt_url ) );
							$row .= sprintf(
								'<a href="%s" class="edit"><span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
								$edit_link,
								$num_caps,
								sprintf(
									/* translators: Hidden accessibility text. %s: Number of users. */
									_n( '%s capability assigned to this role', '%s capabilities assigned to this role', $num_caps, 'describr' ),
									number_format_i18n( $num_caps )
								)
							);
						} else {
							$row .= 0;
						}
						break;
					default:
						/**
						 * Filters the display output of custom columns in the Roles list table.
						 *
						 * @since 2.8.0
						 *
						 * @param string $output      Custom column output. Default empty.
						 * @param string $column_name Column name.
						 * @param int    $role        The currently listed role.
						 */
						$row .= apply_filters( 'describr_manage_roles_custom_column', '', $column_name, $role );
				}

				if ( $primary === $column_name ) {
					$row .= $this->row_actions( $actions );
				}
				$row .= '</td>';
			}
		}
		$row .= '</tr>';

		return $row;
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 3.0
	 *
	 * @return string Name of the default primary column, in this case, 'role'.
	 */
	protected function get_default_primary_column_name() {
		return 'role';
	}
}