<?php
namespace describr\admin;

/**
 * Admin_Menu class
 *
 * @package Describr
 * @since 3.0
 */
class Admin_Menu {        
    /**
     * Admin_Menu constructor
     * 
     * @since 3.0
     */
    public function __construct () {
        if ( is_network_admin() ) {
            add_action( 'network_admin_notices', array( __CLASS__, 'activate_plugin_notice' ), 10 );
            add_action( 'network_admin_menu', array( __CLASS__, 'add_submenu_pages' ), 10 );
        } elseif ( is_user_admin() ) {
            add_action( 'user_admin_notices', array( __CLASS__, 'activate_plugin_notice' ), 10 );
            add_action( 'user_admin_menu', array( __CLASS__, 'add_submenu_pages' ), 10 );
        } else {
            add_action( 'admin_notices', array( __CLASS__, 'activate_plugin_notice' ), 10 );
            add_action( 'admin_menu', array( __CLASS__, 'add_submenu_pages' ), 10 );
        }

        add_filter( 'set_screen_option_describr_roles_per_page', array( __CLASS__, 'set_roles_screen_per_page' ), 10, 3 );
    }    
        
    /**
     * Returns the updated Roles Screen 'per_page' value
     *
     * @since 3.0
     *
     * @see `set_screen_options()`
     *
     * @param mixed   $screen_option The value to save instead of the option value
     * @param string  $option        The option name
     * @param int     $value         The option value
     */
    public static function set_roles_screen_per_page( $screen_option, $option, $value ) {
        return absint( $value );
    }

    /**
     * Display Activate Plugin notice if the plugin is not activated
     * 
     * @since 3.0
     */
    public static function activate_plugin_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        
        if ( ! get_option( 'describr_version' ) ) {
            $plugin = DESCRIBR;

            if ( ! is_multisite() ) {
                $plugins_url = admin_url( 'plugins.php' );
            } elseif ( current_user_can( 'manage_network_options' ) ) {
                $plugins_url = network_admin_url( 'plugins.php' );
            } else {
                $menu_perms = get_site_option( 'menu_items' );

                if ( isset( $menu_perms['plugins'] ) ) {
                    $plugins_url = admin_url( 'plugins.php' );
                }
            }
            
            if ( is_plugin_active( DESCRIBR_FILE ) ) {
                if ( isset( $plugins_url ) ) {
                    $activate_plugin_notice = sprintf(
                        /*translators: 1: Plugin's name. 2: Plugins screen's URL.*/
                        __( '%1$s is activated, but its version cannot be found in the database. Maybe it needs to be <a href="%2$s">reactivated</a>.', 'describr' ),
                        esc_html( $plugin ),
                        esc_url( $plugins_url )
                    );
                } else {
                    $activate_plugin_notice = sprintf(
                        /*translators: %s: Plugin's name.*/
                        __( '%s is activated, but its version cannot be found in the database.', 'describr' ),
                        esc_html( $plugin )
                    );
                }
                
            } elseif ( isset( $plugins_url ) ) {
                $activate_plugin_notice = sprintf(
                        /*translators: 1: Plugin's name. 2: Plugins screen's URL.*/
                        __( '%1$s is not yet activated. <a href="%2$s">Please activate it now</a>.', 'describr' ),
                        esc_html( $plugin ),
                        esc_url( $plugins_url )
                    );
            }

            if ( isset( $activate_plugin_notice ) ) {
                wp_admin_notice( 
                    $activate_plugin_notice, 
                    array( 
                        'type'        => 'warning', 
                        'dismissible' => true, 
                    ) 
                );
            }
        }
    }
    
    /**
     * Removes the Activation notice from the current page
     * 
     * @since 3.0
     */
    public static function remove_activate_plugin_notice() {
        if ( is_network_admin() ) {
            remove_action( 'network_admin_notices', array( __CLASS__, 'activate_plugin_notice' ), 10 );
        } elseif ( is_user_admin() ) {
            remove_action( 'user_admin_notices', array( __CLASS__, 'activate_plugin_notice' ), 10 );
        } else {
            remove_action( 'admin_notices', array( __CLASS__, 'activate_plugin_notice' ), 10 );
        }
    }

    /**
     * Adds the plugin's submenus the Settings top-level menu
     * 
     * @since 3.0
     */
    public static function add_submenu_pages() {
        $page_hook = add_options_page( 
            __( 'Describr Settings', 'describr' ), 
            DESCRIBR, 
            'manage_options', 
            'describr-options', 
            array( __CLASS__, 'settings_page_output' ), 
            0 
        );
        
        foreach ( array( 'options_screen_aside', 'remove_activate_plugin_notice', ) as $method ) {
            add_action( "load-{$page_hook}", array( __CLASS__, $method ), 10 );//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        }
                
        $page_hook = add_options_page( 
            __( 'Roles', 'describr' ), 
            __( 'Roles' ,'describr' ), 
            'manage_options', 
            'describr-roles', 
            array( __CLASS__, 'roles_page_output' ), 
            1 
        );
        
        foreach ( array( 'roles_screen_aside', 'remove_activate_plugin_notice', 'do_role_screen_actions' ) as $method ) {
            add_action( "load-{$page_hook}", array( __CLASS__, $method ), 10 );//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        }
    }
    
    /**
     * Outputs the settings page
     * 
     * @since 3.0
     */
    public static function settings_page_output() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permission to access this page.', 'describr' ) );
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <hr class="wp-header-end" />
            <div id="describr-specs">
                <p id="describr-specs-version"><strong><?php echo esc_html(sprintf(
                    /*translators: Plugin's version.*/
                    _x( 'Version %s', 'plugin', 'describr' ),
                    DESCRIBR_VERSION
                )); ?></strong></p>
                <p class="alignright"><?php echo esc_html(sprintf(
                    /*translators: %s: Plugin's activation date and time.*/
                    _x( 'Activated %s', 'plugin', 'describr' ),
                    wp_date( describr_locale_date_format(), strtotime( get_option( 'describr_first_activation_date' ) ) )
                )); ?></p>
            </div> 
            <form method="post" action="options.php">
                <?php settings_fields( 'describr' ); ?>
                <table class="form-table" role="presentation">
                    <?php describr()->admin_options()->main_options(); ?>
                </table>
                <?php describr()->admin_options()->frontend_pages(); ?>

                <?php
                if ( ! is_multisite() ) {
                    describr()->admin_options()->save_plugin_data();
                }
                ?>

                <?php do_settings_sections( 'describr' ); ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Outputs the Roles page.
     * 
     * @since 3.0
     * @since 3.0.1 Deletes capabilities' descriptions' popups.
     * @since 3.0.1 Removes "site&#039;s" that precedes "front end".
     */
    public static function roles_page_output(){
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permission to access this page.', 'describr' ) );
        }

        $notices = array();
                
        if ( isset( $_GET['update'] ) ) {
            switch ( $_GET['update'] ) {
                case 'del':
                    $delete_count = isset( $_GET['delete_count'] ) ? (int) $_GET['delete_count'] : 0;
                            
                    if ( 1 === $delete_count ) {
                        $message = __( 'Role deleted.', 'describr' );
                    } else {
                        /*translators: %s: Number of user roles.*/
                        $message = _n( '%s role deleted.', '%s roles deleted.', $delete_count, 'describr' );
                    }
                    $message    = sprintf( $message, number_format_i18n( $delete_count ) );
                    $notices[] = array(
                        'msg'  => $message,
                        'args' => array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'admin_no_cap':
                    $notices[] = array(
                        'msg'  => __( 'The Administrator role must have capabilities.', 'describr' ),
                        'args' => array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'error' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'err_defined_role_del':
                        $notices[] = array(
                            'msg'  => __( 'The Administrator, Editor, Author, Contributor, and Subscriber roles cannot be deleted.', 'describr' ),
                            'args' => array(
                                'id'                 => 'message',
                                'additional_classes' => array( 'error' ),
                                'dismissible'        => true,
                            )
                        );
                        $notices[] = array(
                            'msg'  => __( 'Other roles have been deleted.', 'describr' ),
                            'args' => array(
                                'id'                 => 'message',
                                'additional_classes' => array( 'updated' ),
                                'dismissible'        => true,
                            )        
                        );
                        break;
                case 'redirect':
                        $role_count = isset( $_GET['role_count'] ) ? (int) $_GET['role_count'] : 0;
                            
                        if ( 1 === $role_count ) {
                            $message = __( 'Redirects added to role.', 'describr' );
                        } else {
                            /*translators: %s: Number of user roles.*/
                            $message = _n( 'Redirects added to %s role.', 'Redirects added to %s roles.', $role_count, 'describr' );
                        }
                        $message    = sprintf( $message, number_format_i18n( $role_count ) );
                        $notices[] = array(
                            'msg' => $message,
                            'args' => array(
                                'id'                 => 'message',
                                'additional_classes' => array( 'updated' ),
                                'dismissible'        => true,
                            )
                        );
                        break;
                case 'add_cap':
                    $notices[] = array(
                        'msg'  => __( 'User role capability added.', 'describr' ),
                        'args' => array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated', 'fade' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'edit_caps':
                    $notices[] = array(
                        'msg'  => __( 'User role capabilities edited.', 'describr' ),
                        'args' => array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated', 'fade' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
                case 'true':
                    $notices[] = array(
                        'msg'  => __( 'User role edited.', 'describr' ),
                        'args' => array(
                            'id'                 => 'message',
                            'additional_classes' => array( 'updated', 'fade' ),
                            'dismissible'        => true,
                        )
                    );
                    break;
            }
        }

        if ( isset( $errors ) && is_wp_error( $errors ) ) {
            $error_message = '';
            foreach ( $errors->get_error_messages() as $err ) {
                $error_message .= "<li>$err</li>\n";
            }
            wp_admin_notice(
                '<ul>' . $error_message . '</ul>',
                array(
                    'additional_classes' => array( 'error' ),
                )
            );
        }
        
        if ( ! empty( $notices ) ) {
            foreach ( $notices as $notice ) {
                wp_admin_notice( $notice['msg'], $notice['args'] );
            }
        }
        
        list( $url ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
        
        if ( isset( $_GET['page'] ) ) {
            $page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
            $url = add_query_arg( 'page', rawurlencode( $page ), $url );
        }

        switch ( describr()->admin_roles_list_table()->current_action() ) {
            case 'delete':
                echo '<form method="post"><div class="wrap"><h1>' . esc_html__( 'Delete Roles', 'describr' ) . '</h1>';

                $delete_roles = describr()->admin_roles_list_table()->roles;
                $defined_roles = describr_defined_roles();
                $editable_roles = get_editable_roles();
                        
                echo '<p>';

                if ( 1 === count( $delete_roles ) ) {
                    esc_html_e( 'You have specified this role for deletion:', 'describr' );
                } else {
                    esc_html_e( 'You have specified these roles for deletion:', 'describr' );
                }
                        
                echo '</p><ul>';

                $go_delete = 0;
                        
                foreach ( $delete_roles as $role ) {
                    $name = translate_user_role( $editable_roles[ $role ]['name'] );
                        
                    if ( in_array(  $role, $defined_roles, true ) ) {
                        echo '<li>';
                        printf(
                            /*translators: 1: Role, 2: Translated role name.*/
                            __( 'Role&#8211;%1$s: %2$s <strong>This role is defined by WordPress and cannot be deleted.</strong>', 'describr' ),
                            esc_html( $role ),
                            esc_html( $name )
                        );
                        echo "</li>\n";
                    } else {
                        echo '<li>';
                        printf(
                            '<input type="hidden" name="roles[]" value="%s" />',
                            esc_attr( $role )
                        );
                            printf(
                                /*translators: 1: Role, 2: Translated role name.*/
                                __( 'Role&#8211;%1$s: %2$s' ),
                                esc_html( $role ),
                                esc_html( $name )
                            );
                            echo "</li>\n";

                            ++$go_delete;
                        }
                }
                echo '</ul>';

                if ( $go_delete ) {
                    wp_nonce_field( 'delete-roles' );

                    echo '<input type="hidden" name="action" value="dodelete" />';

                    if ( isset( $_GET['page'] ) ) {
                        echo '<input type="hidden" name="page" value="' . esc_attr( wp_unslash( $_GET['page'] ) ) . '" />';
                    }

                    submit_button( __( 'Confirm Deletion', 'describr' ), 'primary' );
                } else {
                    echo '<p>' . esc_html__( 'There are no valid roles selected for deletion.', 'describr' ) . '</p>';
                }
                echo '</div></form>';
                break;
            case 'adredirect':
                echo '<form method="post" action="' . esc_url( $url ) . '"><div class="wrap"><h1>' . esc_html__( 'Add Roles Redirect Links', 'describr' ) . '</h1>';
                $adredirect_roles = describr()->admin_roles_list_table()->roles;
                        
                $editable_roles = get_editable_roles();
                        
                $roles_names = array();

                foreach ( $adredirect_roles as $role ) {
                    echo '<input type="hidden" name="roles[]" value="' . esc_attr( $role ) . '" />';

                    $roles_names[] = translate_user_role( $editable_roles[ $role ]['name'] );
                }

                echo '<p>';

                if ( 1 === count( $adredirect_roles ) ) {
                    esc_html_e( 'You have specified this role to add redirect links:', 'describr' );
                } else {
                    esc_html_e( 'You have specified these roles to add redirect links:', 'describr' );
                }

                echo ' ' . esc_html( implode( wp_get_list_item_separator(), $roles_names ) ) . '</p>';

                static::redirects_markup();

                wp_nonce_field( 'adredirect-roles' );

                echo '<input type="hidden" name="action" value="doadredirect" />';
                            
                submit_button( __( 'Add Redirect Links', 'describr' ), 'primary' );
                
                echo '</div></form>';
                break;
            case 'edit':
                $role = describr()->admin_roles_list_table()->roles[0];
                
                $plugin_caps = (array) describr()->caps;

                $plugin_roles_caps = get_option( 'describr_roles_caps', array() );

                if ( ! empty( $plugin_roles_caps[ $role ] ) ) {
                    $plugin_caps = array_merge( $plugin_roles_caps[ $role ], $plugin_caps );
                }

                $editable_roles = get_editable_roles();

                $role_info = $editable_roles[ $role ];
                
                $display_name = translate_user_role( $role_info['name'] );

                $title = sprintf( 
                    /*translators: %s: Role's display name.*/ 
                    __( 'Edit Role %s','describr' ), 
                    $display_name
                );

                $remove_text = sprintf(
                    /*translators: %s: Role's display name.*/ 
                    __( 'Remove role %s unchecked capabilities', 'describr' ),
                    $display_name
                );

                $caps = $role_info['capabilities'];
                $can_delete_self = ! empty( $caps['delete_user_front'] );
                ?>
                <form method="post" action="<?php echo esc_url( $url ); ?>">
                    <div class="wrap">
                        <h1><?php echo esc_html( $title ); ?></h1>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Capabilities', 'describr' ); ?></th>
                                <td>
                                    <fieldset><legend class="screen-reader-text">
                                        <?php
                                        /*translators: Hidden accessibility text.*/ 
                                        esc_html_e( 'Capabilities', 'describr' ); 
                                        ?>
                                        </legend>
                                        <div id="describr-caps">
                                            <?php
                                            foreach ( $caps as $cap => $has_cap ) {
                                                if ( 'delete_user_front' === $cap ) {
                                                    continue;
                                                }
                                                $cap_id = wp_unique_id( 'describr_cap_' );
                                                echo '<p><label><input type="checkbox" name="caps[' . esc_attr( $cap ) . ']" value="1" id="' .
                                                     esc_html( $cap_id ) .
                                                     '"' .
                                                     checked( 1, $has_cap, false ) .
                                                     ( isset( $plugin_caps[ $cap ] ) ? ' aria-describedby="' . esc_attr( $cap_id ) . '_desc"' : '' ) .
                                                     ' /> ' .
                                                     esc_html( $cap ) .
                                                     '</label>';
                                                if ( isset( $plugin_caps[ $cap ] ) ) {
                                                    $desc = $cap_id . '_desc';
                                                    echo ' <span aria-hidden="true" class="dashicons dashicons-info describr-cap-desc-popup-trigger hide-if-no-js" data-capdesc="' . esc_attr( $desc ) . '"></span><span class="screen-reader-text" id="' . esc_attr( $desc ) . '">' . esc_html( $plugin_caps[ $cap ] ) . '</span>';
                                                }
                                                echo '</p>';
                                            }
                                            ?>
                                        </div>
                                        <p><label for="describr_delete_user_front"><input type="checkbox" name="delete_user_front" value="1" id="describr_delete_user_front"<?php checked( true, $can_delete_self ); ?> /> <?php esc_html_e( 'Delete self on the front end', 'describr' ); ?></label></p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'New Capability', 'describr' ); ?></th>
                                <td>
                                    <fieldset><legend class="screen-reader-text">
                                        <?php
                                        /*translators: Hidden accessibility text.*/ 
                                        esc_html_e( 'New Capability', 'describr' ); 
                                        ?>
                                        </legend>
                                        
                                        <p><label for="describr_new_cap"><?php esc_html_e( 'Capability', 'describr' ); ?> <input type="text" name="new_cap" id="describr_new_cap" /></label></p>
                                        <p><label for="describr_new_cap_description"><?php esc_html_e( 'Description', 'describr' ); ?> <textarea rows="2" name="new_cap_desc" id="describr_new_cap_description" style="vertical-align: top;"></textarea></label></p>
                                        <p><label for="describr_new_cap_enable"><?php esc_html_e( 'Enable', 'describr' ); ?> <input type="checkbox" name="new_cap_enabled" value="1" id="describr_new_cap_enable" checked="checked" /></label></p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><label for="describr_remove_cap"><input type="checkbox" name="remove_cap" value="1" id="describr_remove_cap" /> <?php echo esc_html( $remove_text ); ?></label></td>
                            </tr>
                        </table>
                        <h2><?php esc_html_e( 'Redirects', 'describr' ); ?></h2>
                        <?php 
                        static::redirects_markup(); 
                        wp_nonce_field( 'edit-role' );
                        submit_button( __( 'Save Changes', 'describr' ), 'primary' );
                        ?>
                    </div>
                    <input type="hidden" name="action" value="doedit" />
                    <input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>" />
                    <?php if ( isset( $_GET['page'] ) ) { ?>
                        <input type="hidden" name="page" value="<?php echo esc_attr( wp_unslash( $_GET['page'] ) ); ?>" />
                    <?php } ?>
                </form>
                <?php
                break;
            case 'adcap':
                $role = describr()->admin_roles_list_table()->roles[0];
                
                $plugin_caps = (array) describr()->caps;

                $plugin_roles_caps = get_option( 'describr_roles_caps', array() );

                if ( ! empty( $plugin_roles_caps[ $role ] ) ) {
                    $plugin_caps = array_merge( $plugin_roles_caps[ $role ], $plugin_caps );
                }
                            
                $all_caps = array(); 

                $editable_roles = get_editable_roles();

                foreach ( $editable_roles as $role_name => $role_info ) {
                    $all_caps = array_merge( $all_caps, (array) $role_info['capabilities'] );
                }

                $all_caps = array_merge( $all_caps, $plugin_caps );                
                $all_caps = array_unique( array_keys( $all_caps ) );

                $role_info = $editable_roles[ $role ];

                $role_caps = $role_info['capabilities'];
                
                $can_delete_self = ! empty( $role_caps['delete_user_front'] );
                
                $display_name = translate_user_role( $role_info['name'] );
                
                $title = sprintf( 
                    /*translators: %s: Role's display name.*/ 
                    __( 'Add Role %s Capabilities','describr' ), 
                    $display_name
                );

                $remove_text = sprintf(
                    /*translators: %s: Role's display name.*/ 
                    __( 'Remove role %s unchecked capabilities', 'describr' ),
                    $display_name
                );
                ?>
                <form method="post" action="<?php echo esc_url( $url ); ?>">
                    <?php?>
                    <div class="wrap">
                        <h1><?php echo esc_html( $title ); ?></h1>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'All Capabilities', 'describr' ); ?></th>
                                <td>
                                    <fieldset><legend class="screen-reader-text">
                                        <?php
                                        /*translators: Hidden accessibility text.*/ 
                                        esc_html_e( 'Capabilities', 'describr' ); 
                                        ?>
                                        </legend>
                                        <style<?php echo current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"'; ?>>
                                            #describr-caps {
                                                max-height: none;
                                                overflow-y: auto;
                                            }
                                        </style>
                                        <div id="describr-caps">
                                            <?php
                                            foreach ( $all_caps as $cap ) {
                                                if ( 'delete_user_front' === $cap ) {
                                                    continue;
                                                }

                                                $cap_id = wp_unique_id( 'describr_cap_' );
                                                echo '<p><label><input type="checkbox" name="caps[' . esc_attr( $cap ) . ']" value="1" id="' .
                                                     esc_html( $cap_id ) .
                                                     '"' .
                                                     checked( true, ! empty( $role_caps[ $cap ] ), false ) .
                                                     ( isset( $plugin_caps[ $cap ] ) ? ' aria-describedby="' . esc_attr( $cap_id ) . '_desc"' : '' ) .
                                                     ' /> ' .
                                                     esc_html( $cap ) .
                                                     '</label>';
                                                if ( isset( $plugin_caps[ $cap ] ) ) {
                                                    $desc = $cap_id . '_desc';
                                                    echo ' <span class="describr-cap-desc-wrap"><span aria-hidden="true" class="dashicons dashicons-info describr-cap-desc-popup-trigger hide-if-no-js" data-capdesc="' . esc_attr( $desc ) . '"></span>
                                                        </span><span class="screen-reader-text" id="' . esc_attr( $desc ) . '">' . esc_html( $plugin_caps[ $cap ] ) . '</span>';
                                                }
                                                echo '</p>';
                                            }
                                            ?>
                                        </div>
                                        <p><label for="describr_delete_user_front"><input type="checkbox" name="delete_user_front" value="1" id="describr_delete_user_front"<?php checked( true, $can_delete_self ); ?> /> <?php esc_html_e( 'Delete self on the front end', 'describr' ); ?></label></p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'New Capability', 'describr' ); ?></th>
                                <td>
                                    <fieldset><legend class="screen-reader-text">
                                        <?php
                                        /*translators: Hidden accessibility text.*/ 
                                        esc_html_e( 'New Capability', 'describr' ); 
                                        ?>
                                        </legend>
                                        
                                        <p><label for="describr_new_cap"><?php esc_html_e( 'Capability', 'describr' ); ?> <input type="text" name="new_cap" id="describr_new_cap" /></label></p>
                                        <p><label for="describr_new_cap_description"><?php esc_html_e( 'Description', 'describr' ); ?> <textarea rows="2" name="new_cap_desc" id="describr_new_cap_description" style="vertical-align: top;"></textarea></label></p>
                                        <p><label for="describr_new_cap_enable"><?php esc_html_e( 'Enable', 'describr' ); ?> <input type="checkbox" name="new_cap_enabled" value="1" id="describr_new_cap_enable" checked="checked" /></label></p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><label for="describr_remove_cap"><input type="checkbox" name="remove_cap" value="1" id="describr_remove_cap" /> <?php echo esc_html( $remove_text ); ?></label></td>
                            </tr>
                        </table>
                        <?php wp_nonce_field( 'doadcap-role' ); ?>

                        <?php submit_button( __( 'Save Changes', 'describr' ), 'primary' ); ?>
                    </div>
                    <input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>" />
                    <input type="hidden" name="action" value="doadcap" />
                    <?php if ( isset( $_GET['page'] ) ) { ?>
                        <input type="hidden" name="page" value="<?php echo esc_attr( wp_unslash( $_GET['page'] ) ); ?>" />
                    <?php } ?>
                </form>
                <?php
                break;
            default:
                global $title, $usersearch;                
                ?>
                <div class="wrap">
                    <h1 class="wp-heading-inline">
                        <?php echo esc_html( $title ); ?>
                    </h1>
                    <?php
                    if ( strlen( $usersearch ) ) {
                        echo '<span class="subtitle">';
                        echo wp_kses_post(sprintf(
                            /*translators: %s: Search query.*/
                            __( 'Search results for: %s', 'describr' ),
                            '<strong>' . esc_html( $usersearch ) . '</strong>'
                        ));
                        echo '</span>';
                    }
                    ?>
                    <hr class="wp-header-end">

                    <?php describr()->admin_roles_list_table()->views(); ?>

                    <form method="get">
                        <?php describr()->admin_roles_list_table()->search_box( __( 'Search Roles', 'describr' ), 'role' ); ?>

                        <?php if ( ! empty( $_REQUEST['role'] ) ) { ?>
                            <input type="hidden" name="role" value="<?php echo esc_attr( wp_unslash( $_REQUEST['role'] ) ); ?>" />
                        <?php } ?>
                        
                        <?php if ( isset( $_GET['page'] ) ) { ?>
                            <input type="hidden" name="page" value="<?php echo esc_attr( wp_unslash( $_GET['page'] ) ); ?>" />
                        <?php } ?>

                        <?php describr()->admin_roles_list_table()->display(); ?>
                    </form>
                    <div class="clear"></div>
                </div><!-- .wrap -->
            <?php
            break;
        }
    }

    /**
     * Do actions from the plugin's Role screen
     * 
     * @since 3.0
     */
    public static function do_role_screen_actions() {
        global $title;

        $pagenum = describr()->admin_roles_list_table()->get_pagenum();

        if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
            $redirect = remove_query_arg( array( '_wp_http_referer', 'update', 'delete_count' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
        } else {
            $redirect = self_admin_url( 'options-general.php?page=describr-roles' );
        }
        
        switch ( describr()->admin_roles_list_table()->current_action() ) {
            case 'adcap':
                check_admin_referer( 'bulk-roles' );
            
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( __( 'You cannot add capability to this role.', 'describr' ), 403 );
                }

                if ( empty( wp_unslash( $_REQUEST['role'] ) ) || ! wp_roles()->is_role( wp_unslash( $_REQUEST['role'] ) ) ) {
                    wp_redirect( $redirect );
                    exit;
                }
                
                $role = wp_unslash( $_REQUEST['role'] );
                
                $editable_roles = get_editable_roles();
                

                if ( empty( $editable_roles[ $role ] ) ) {
                    wp_die( __( 'Sorry, you are not allowed to edit this role.', 'describr' ), 403 );
                }
            
                describr()->admin_roles_list_table()->roles[] = $role;
                break;
            case 'doadcap':
                check_admin_referer( 'doadcap-role' );
            
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( __( 'You cannot add capability to this role.', 'describr' ), 403 );
                }
                
                $wp_roles = wp_roles();

                if ( ! isset( $_POST['role'] ) || ! $wp_roles->is_role( wp_unslash( $_POST['role'] ) ) ) {
                    wp_redirect( $redirect );
                    exit;
                }
                
                $role = wp_unslash( $_POST['role'] );

                $editable_roles = get_editable_roles();
                
                if ( empty( $editable_roles[ $role ] ) ) {
                    wp_die( __( 'Sorry, you are not allowed to edit this role.', 'describr' ), 403 );
                }

                if ( isset( $_POST['caps'] ) ) {
                    $caps = array_filter( wp_unslash( (array) $_POST['caps'] ), 'intval' );
                } else {
                    $caps = array();
                }      
                
                if ( isset( $_POST['delete_user_front'] ) ) {
                    $caps['delete_user_front'] = 1;
                }

                $role_data = $editable_roles[ $role ];
                
                $new_caps = array_merge( (array) $role_data['capabilities'], $caps );

                $old_caps = array_diff_key( $new_caps, $caps );

                foreach ( $old_caps as $cap => $has_cap ) {
                    $old_caps[ $cap ] = 0;
                }

                $new_caps = array_merge( $new_caps, $old_caps );
                
                $plugin_roles_caps = get_option( 'describr_roles_caps', array() );
                
                if ( isset( $_POST['remove_cap'] ) ) {
                    $new_caps = array_intersect_key( $new_caps, $caps );

                    if ( isset( $plugin_roles_caps[ $role ] ) ) {
                        $plugin_roles_caps[ $role ] = array_intersect_key( $plugin_roles_caps[ $role ], $new_caps );
                    }
                }
                
                $update = 'edit_caps';

                if ( ! empty( $_POST['new_cap'] ) && is_string( $_POST['new_cap'] ) ) {
                    $new_cap = wp_kses( wp_unslash( $_POST['new_cap'] ), 'strip' );
                    $new_cap = trim( $new_cap );

                    if ( ! empty( $new_cap ) ) {
                        $new_caps[ $new_cap ] = isset( $_POST['new_cap_enabled'] );
                        $update = 'add_cap';

                        if ( ! empty( $_POST['new_cap_desc'] ) && is_string( $_POST['new_cap_desc'] ) ) {
                            $cap_desc = wp_kses( wp_unslash( $_POST['new_cap_desc'] ), 'strip' );
                            $cap_desc = trim( $cap_desc );

                            if ( ! empty( $cap_desc ) ) {
                                $cap_desc = str_replace( "\n", '', $cap_desc );
                                $cap_desc = preg_replace( '/\s+/', ' ', $cap_desc );
                                                                                            
                                $plugin_roles_caps[ $role ][ $new_cap ] = esc_html( $cap_desc );
                            }
                        }
                    }
                }

                if ( in_array( $role, array( 'administrator' ), true ) && empty( $new_caps ) ) {
                    wp_redirect( add_query_arg( 'update', 'admin_no_cap', $redirect ) );
                    exit;
                }
                
                $roles = get_option( $wp_roles->role_key, array() );
                
                $role_data['capabilities'] = $new_caps;
                
                $roles[ $role ] = $role_data;
                
                update_option( $wp_roles->role_key, $roles );

                if ( empty( $plugin_roles_caps[ $role ] ) ) {
                    unset( $plugin_roles_caps[ $role ] );
                }

                update_option( 'describr_roles_caps', $plugin_roles_caps );

                wp_redirect( add_query_arg( 'update', $update, $redirect ) );
                exit;
                break;
            case 'delete':
                check_admin_referer( 'bulk-roles' );
            
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( __( 'You cannot delete this role.', 'describr' ), 403 );
                }

                if ( empty( $_REQUEST['roles'] ) && empty( $_REQUEST['role'] ) ) {
                    wp_redirect( $redirect );
                    exit;
                }
                
                if ( empty( $_REQUEST['roles'] ) ) {
                    $roles = $_REQUEST['role'];
                } else {
                    $roles = $_REQUEST['roles'];
                }
                
                $roles = array_filter( ( array ) $roles, array( wp_roles(), 'is_role' ) );

                if ( empty( $roles ) ) {
                    wp_redirect( $redirect );
                    exit;
                }

                $editable_roles = get_editable_roles();

                foreach ( $roles as $role ) {
                    if ( empty( $editable_roles[ $role ] ) ) {
                        wp_die( __( 'Sorry, you are not allowed to edit this role.', 'describr' ), 403 );
                    }
                }
            
                describr()->admin_roles_list_table()->roles = $roles;
                break;
            case 'dodelete':
                check_admin_referer( 'delete-roles' );
            
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( __( 'You cannot delete this role.', 'describr' ), 403 );
                }

                if ( empty( $_POST['roles'] ) ) {
                    wp_redirect( $redirect );
                    exit;
                }
                
                $wp_roles = wp_roles();

                $roles = array_filter( (array) $_POST['roles'], array( $wp_roles, 'is_role' ) );

                if ( empty( $roles ) ) {
                    wp_redirect( $redirect );
                    exit;
                }

                $defined_roles = describr_defined_roles();
                $editable_roles = get_editable_roles();
                
                $delete_count = 0;
                
                $update = 'del';

                foreach ( $roles as $role ) {
                    if ( empty( $editable_roles[ $role ] ) ) {
                        wp_die( __( 'Sorry, you are not allowed to delete that role.', 'describr' ), 403 );
                    }
                    
                    if ( in_array( $role, $defined_roles, true ) ) {
                        $update = 'err_defined_role_del';
                    } else {
                        $wp_roles->remove_role( $role );
                        $delete_count++;
                    }
                    
                }
 
                $redirect = add_query_arg(
                    array(
                        'delete_count' => $delete_count,
                        'update'       => $update,
                    ),
                    remove_query_arg( 'action', $redirect )
                );

                wp_redirect( $redirect );
                break;
            case 'adredirect':
                check_admin_referer( 'bulk-roles' );
            
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( __( 'You cannot delete this role.', 'describr' ), 403 );
                }

                if ( empty( $_REQUEST['roles'] ) && empty( $_REQUEST['role'] ) ) {
                    wp_redirect( $redirect );
                    exit;
                }
                
                if ( empty( $_REQUEST['roles'] ) ) {
                    $roles = $_REQUEST['role'];
                } else {
                    $roles = $_REQUEST['roles'];
                }
                
                $roles = array_filter( ( array ) $roles, array( wp_roles(), 'is_role' ) );

                if ( empty( $roles ) ) {
                    wp_redirect( $redirect );
                    exit;
                }

                $editable_roles = get_editable_roles();

                foreach ( $roles as $role ) {
                    if ( empty( $editable_roles[ $role ] ) ) {
                        wp_die( __( 'Sorry, you are not allowed to edit this role.', 'describr' ), 403 );
                    }
                }
            
                describr()->admin_roles_list_table()->roles = $roles;
                break;
            case 'doadredirect':
                check_admin_referer( 'adredirect-roles' );
                
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( __( 'You cannot add redirect URL this role.', 'describr' ), 403 );
                }
                
                if ( empty( $_POST['roles'] ) 
                    || 
                    ( ! isset( $_POST['register_redirect'] ) 
                        && ! isset( $_POST['login_redirect'] ) 
                        && ! isset( $_POST['logout_redirect'] ) 
                        && ! isset( $_POST['deleteaccount_redirect'] ) 
                    ) 
                ) {
                    wp_redirect( $redirect );
                    exit;
                }
                
                $roles = array_filter( (array) $_REQUEST['roles'], array( wp_roles(), 'is_role' ) );

                $redirect_afters = array( 
                    'register_redirect',
                    'login_redirect',
                    'logout_redirect',
                    'deleteaccount_redirect',
                );
                
                $for_db = array();
                
                foreach ( $redirect_afters as $redirect_after ) {
                    $custom_redirect_key = $redirect_after . '_custom';

                    $redirect_to = trim( wp_unslash( $_POST[ $redirect_after ] ) );
                    
                    if ( '\c\u\s\t\o\m' === $redirect_to && isset( $_POST[ $custom_redirect_key ] ) ) {
                        $redirect_to = trim( wp_unslash( $_POST[ $custom_redirect_key ] ) );
                        $redirect_to = wp_strip_all_tags( $redirect_to );
                        $redirect_to = sanitize_url( $redirect_to, array( 'http', 'https' ) );
                        $redirect_to = wp_kses( $redirect_to, 'strip' );

                        $redirect = add_query_arg( $custom_redirect_key, rawurlencode( $redirect_to ), $redirect );
                    } else {
                        $redirect_to = sanitize_text_field( $redirect_to );
                    }
                    
                    $for_db[ $redirect_after ] = $redirect_to;
                }
                
                $for_db = array_filter( $for_db );

                $redirect_roles = get_option( 'describr_roles_redirects', array() );

                $editable_roles = get_editable_roles();
                
                $role_count = 0;

                foreach ( $roles as $role ) {
                    if ( empty( $editable_roles[ $role ] ) ) {
                        wp_die( __( 'Sorry, you are not allowed to add redirect URL to that role.', 'describr' ), 403 );
                    }
                    
                    $redirect_roles[ $role ] = $for_db;

                    $role_count++;
                }
                
                update_option( 'describr_roles_redirects', $redirect_roles );

                $for_url = array_map( 'rawurlencode', $for_db );

                $for_url['role_count'] = $role_count;

                $for_url['update'] = 'redirect';                

                $redirect = add_query_arg( $for_url, remove_query_arg( 'roles', $redirect ) );
                $redirect .= '&roles%5B%5D=' . implode( '&roles%5B%5D=', $roles );

                wp_redirect( $redirect );
                exit;

                break;
            case 'edit':            
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( __( 'You cannot edit this role.', 'describr' ), 403 );
                }

                if ( empty( $_REQUEST['role'] ) ) {
                    wp_redirect( $redirect );
                    exit;
                }
                
                $role = trim( wp_unslash( $_REQUEST['role'] ) );

                if ( ! wp_roles()->is_role( $role ) ) {
                    wp_redirect( $redirect );
                    exit;
                }

                $editable_roles = get_editable_roles();
                
                if ( empty( $editable_roles[ $role ] ) ) {
                    wp_die( __( 'Sorry, you are not allowed to edit that role.', 'describr' ), 403 );
                }
                
                describr()->admin_roles_list_table()->roles[] = $role;
                break;
            case 'doedit':
                check_admin_referer( 'edit-role' );

                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( __( 'You cannot edit this role.', 'describr' ), 403 );
                }

                if ( empty( $_POST['role'] ) ) {
                    wp_redirect( $redirect );
                    exit;
                }
                
                $role = trim( wp_unslash( $_POST['role'] ) );
                
                $wp_roles = wp_roles();

                if ( ! $wp_roles->is_role( $role ) ) {
                    wp_redirect( $redirect );
                    exit;
                }

                $editable_roles = get_editable_roles();
                
                if ( empty( $editable_roles[ $role ] ) ) {
                    wp_die( __( 'Sorry, you are not allowed to edit that role.', 'describr' ), 403 );
                }
                
                if ( isset( $_POST['caps'] ) ) {
                    $caps = array_filter( wp_unslash( (array) $_POST['caps'] ), 'intval' );
                } else {
                    $caps = array();
                }
                
                if ( isset( $_POST['delete_user_front'] ) ) {
                    $caps['delete_user_front'] = 1;
                }

                $role_data = $editable_roles[ $role ];
                
                $new_caps = array_merge( (array) $role_data['capabilities'], $caps );
                
                $old_caps = array_diff_key( $new_caps, $caps );

                foreach ( $old_caps as $cap => $has_cap ) {
                    $old_caps[ $cap ] = 0;
                }

                $new_caps = array_merge( $new_caps, $old_caps );

                $plugin_roles_caps = get_option( 'describr_roles_caps', array() );
                
                if ( isset( $_POST['remove_cap'] ) ) {
                    $new_caps = array_intersect_key( $new_caps, $caps );

                    if ( isset( $plugin_roles_caps[ $role ] ) ) {
                        $plugin_roles_caps[ $role ] = array_intersect_key( $plugin_roles_caps[ $role ], $new_caps );
                    }
                }
                
                if ( ! empty( $_POST['new_cap'] ) && is_string( $_POST['new_cap'] ) ) {
                    $new_cap = wp_kses( wp_unslash( $_POST['new_cap'] ), 'strip' );
                    $new_cap = trim( $new_cap );

                    if ( ! empty( $new_cap ) ) {
                        $new_caps[ $new_cap ] = isset( $_POST['new_cap_enabled'] );

                        if ( ! empty( $_POST['new_cap_desc'] ) && is_string( $_POST['new_cap_desc'] ) ) {
                            $cap_desc = wp_kses( wp_unslash( $_POST['new_cap_desc'] ), 'strip' );
                            $cap_desc = trim( $cap_desc );

                            if ( ! empty( $cap_desc ) ) {
                                $cap_desc = str_replace( "\n", '', $cap_desc );
                                $cap_desc = preg_replace( '/\s+/', ' ', $cap_desc );
                                                                                            
                                $plugin_roles_caps[ $role ][ $new_cap ] = esc_html( $cap_desc );
                            }
                        }
                    }
                }

                if ( in_array( $role, array( 'administrator' ), true ) && empty( $new_caps ) ) {
                    wp_redirect( add_query_arg( 'update', 'admin_no_cap', $redirect ) );
                    exit;
                }
                
                $roles = get_option( $wp_roles->role_key, array() );
                
                $role_data['capabilities'] = $new_caps;
                
                $roles[ $role ] = $role_data;
                
                update_option( $wp_roles->role_key, $roles );

                if ( empty( $plugin_roles_caps[ $role ] ) ) {
                    unset( $plugin_roles_caps[ $role ] );
                }

                update_option( 'describr_roles_caps', $plugin_roles_caps );
                
                $redirects_for_db = array();

                foreach ( array( 
                    'register_redirect',
                    'login_redirect',
                    'logout_redirect',
                    'deleteaccount_redirect',
                ) as $redirect_after ) {
                    if ( isset( $_POST[ $redirect_after ] ) ) {
                        $custom_redirect_key = $redirect_after . '_custom';

                        $redirect_to = trim( wp_unslash( $_POST[ $redirect_after ] ) );
                        
                        if ( '\c\u\s\t\o\m' === $redirect_to && isset( $_POST[ $custom_redirect_key ] ) ) {
                            $redirect_to = trim( wp_unslash( $_POST[ $custom_redirect_key ] ) );
                            $redirect_to = wp_strip_all_tags( $redirect_to );
                            $redirect_to = sanitize_url( $redirect_to, array( 'http', 'https' ) );
                            $redirect_to = wp_kses( $redirect_to, 'strip' );
                        } else {
                            $redirect_to = sanitize_text_field( $redirect_to );
                        }
                    
                        $redirects_for_db[ $redirect_after ] = $redirect_to;
                    } else {
                        $redirects_for_db[ $redirect_after ] = '';
                    }
                }
                
                $redirect_roles = get_option( 'describr_roles_redirects', array() );

                $redirect_roles[ $role ] = array_filter( $redirects_for_db );

                update_option( 'describr_roles_redirects', $redirect_roles );

                wp_redirect( add_query_arg( 'update', 'true', $redirect ) );
                exit;
                break;
            default:
                if ( ! empty( $_GET['_wp_http_referer'] ) ) {
                    wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
                    exit;
                }
                
                if ( describr()->admin_roles_list_table()->current_action() && ! empty( $_REQUEST['roles'] ) ) {
                    check_admin_referer( 'bulk-roles' );
                    $screen   = get_current_screen()->id;
                    $sendback = wp_get_referer();
                    $roles = array_filter( (array) $_REQUEST['roles'], array( wp_roles(), 'is_role' ) );

                    /** This action is documented in wp-admin/edit.php */
                    $sendback = apply_filters( "handle_bulk_actions-{$screen}", $sendback, describr()->admin_roles_list_table()->current_action(), $roles ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

                    wp_safe_redirect( $sendback );
                    exit;
                }

                describr()->admin_roles_list_table()->prepare_items();

                $total_pages = describr()->admin_roles_list_table()->get_pagination_arg( 'total_pages' );

                if ( $pagenum > $total_pages && $total_pages > 0 ) {
                    wp_redirect( add_query_arg( 'paged', $total_pages ) );
                    exit;
                }
                
                break;
        }
    }

    /**
     * Adds Help information to the plugin's page.
     * 
     * @since 3.0
     * @since 3.0.1 Removes "site&#039;s" that precedes "front end".
     */
    public static function options_screen_aside() {
        $plugin = DESCRIBR;

        $help = '<p>' . sprintf(
            /*translators: %s: The plugin's name.*/
            __( 'This screen provides many options for controlling the management and display of %s features. So many that, in fact, they will not all fit here.', 'describr' ),
            $plugin
        ) . '</p>';
        
        $help .= '<p>' . __( 'The notifications options allow you to control when the administrators and members of the site are notified. If you want the administrator not to receive an email each time a member changes password, check the <i>A member changes password</i> box. If you want the administrator to receive an email every time a memeber changes password, leave the box unchecked.', 'describr' ) . '</p>';
        
        if ( ! is_multisite() ) {
            $help .= '<p>' . sprintf(
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
                
                '<p>' . __( 'The default registration form does not come with a password field; instead, users get to create a password after following the link sent to their emails. If you want users to register using a password, check the <i>Enable user-generated password field on the registration form</i> box.', 'describr' ) . '</p>' .
                
                '<p>' . sprintf(
                    /*translators: %s: Punctuation marks.*/
                    __( 'A strong password is one that has minimum and maximum number of characters; cannot have identifiable information such as username, nicename, nickname, first name, last name, full name, email, or birthdate; must have at least one uppercase letter, one lowercase letter, and one number; and must contain at least one character like %s. If you want users to use strong passwords, you can check the <i>Require strong passwords</i> box.', 'describr' ),
                    '<code>' . implode( ' ', describr_special_chars_entity_numbers() ) . '</code>'
                ) . '</p>';
        }

        $help .= '<p>' . sprintf(
            /*translators: 1: The plugin's name. 2: /author.*/
            __( 'If you want not to redirect users from the default %1$s profile page to the %2$s profile page, uncheck the <i>Use %2$s as default profile page on the front end</i> box. Otherwise, leave the box checked.', 'describr' ),
            '<code>/author</code>',
            $plugin
        ) . '</p>';
        
        $help .= '<p>' . sprintf(
            /*translators: 1: The plugin's name.*/
            __( 'You can change what pages %1$s should use as account and profile pages on the front end. Nevertheless, these pages are created when %1$s is activated.', 'describr' ), 
            $plugin
        ) . '</p>';
        
        $help .= '<p>' . __( 'The <strong>X (formerly Twitter)</strong> option allows you set the sites <strong>X</strong> handle for use by the Open Graph Protocol when linking back to a profile on the site.', 'describr' ) . '</p>';
        
        $help .= '<p>' . sprintf(
            /*translators: 1: options-general.php. 2: text/plain. 3: text/html.*/
            __( 'You can set multiple administrators&#039; email addresses, which will receive emails when emails are sent to the administrator&#039;s email address set from the %1$s screen. You can also set the content type for emails destined for these email addresses. The two content types of concern are %2$s and %3$s. With %2$s, HTML tags are removed from emails. %3$s allows HTML tags. By default, WordPress sends emails using %2$s.', 'describr' ),
            '<code>options-general.php</code>',
            '<code>text/plain</code>',
            '<code>text/html</code>'
        );
        
        if ( ! is_multisite() ) {
            $help .= ' ' . __( 'If you want users to be able to unsubscribe from receiving email notifications, leave the <i>Users can unsubscribe from receiving email notifications</i> box checked. If you want users to always receive email notifications, uncheck the box.', 'describr' );
        }
        
        $help .= '</p><p>' . __( 'The email header "From" field indicates the site&#039;s name and email address in the receiver&#039;s inbox, helping to identify emails sent from your site. By default the site&#039;s name and administrator&#039;s email are used in the email header "From" field; however, you can change them here.', 'describr' ) . '</p>';
    
        if ( ! is_multisite() ) {
            $help .= '<p>' . sprintf(
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
            
            '<p>' . __( 'You can add a logo to emails. If you want to use the site&#039;s logo as the email logo, select the <i>Site</i> option and select a size for the logo. If you want to use a custom logo in emails, select the <i>URL</i> option and type the URL. If you do not want to include a logo in emails, select <i>None</i>.', 'describr' ) . '</p>';            
        }

        $help .= '<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.', 'describr' ) . '</p>';

        get_current_screen()->add_help_tab(
            array(
                'id'      => 'overview',
                'title'   => __( 'Overview', 'describr' ),
                'content' => $help,
            )
        );

        get_current_screen()->set_help_sidebar(
            '<p><strong>' . __( 'For more information:', 'describr' ) . '</strong></p>' .
            '<p>' . __( '<a href="https://ogp.me/">The Open Graph Protocol</a>', 'describr' ) . '</p>' .
            '<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>', 'describr' ) . '</p>' .
            '<p>' . __( '<a href="https://en.wikipedia.org/wiki/Message_transfer_agent">Mail transfer agent</a>', 'describr' ) . '</p>'
        );
    }

    /**
     * Adds Help information to the plugin's Roles page.
     * 
     * @since 3.0
     * @since 3.0.1 Removes "site&#039;s" that precedes "front end".
     * @since 3.0.1 Replaces "delete_users" with "<code>delete_users</code>".
     */
    public static function roles_screen_aside() {
        switch ( describr()->admin_roles_list_table()->current_action() ) {
            case 'edit':
                $display_name = '';
                
                if ( isset( $_REQUEST['role'] ) ) {
                    $role = wp_kses_post( wp_unslash( $_REQUEST['role'] ) );
                    
                    $editable_roles = get_editable_roles();
                    
                    if ( isset( $editable_roles[ $role ] ) ) {
                        $display_name = translate_user_role( $editable_roles[ $role ]['name'] );
                        $display_name =" $display_name";
                    }
                }

                get_current_screen()->add_help_tab(
                    array(
                        'id'      => 'overview',
                        'title'   => __( 'Overview', 'describr' ),
                        'content' =>  '<p>' . sprintf(
                            /*translators: %s: Role's display name.*/
                            __( 'This screen lists all capabilities granted to this role and redirect locations. If you want not to grant this role a capability, uncheck the box beside that capability. If you want to remove a capability from this role completely, both uncheck the box beside that capability and check the <i>Remove role%s unchecked capabilities</i> box.', 'describr' ),
                            $display_name
                    ) .
                        '</p>'
                    )
                );
                get_current_screen()->add_help_tab(
                    array(
                        'id'      => 'screen-content',
                        'title'   => __( 'Screen Content', 'describr' ),
                        'content' => '<p>' . sprintf(
                            /*translators: delete_users.*/
                            __( 'The capability to delete users is typically restricted to the Administrator (by way of having the %s capability) and Super Admin (on multisite) roles. However, <strong>if you want to give users having this role the capability to delete themselves on the front end</strong>, you may check the <i>Delete self on the front end</i> box. If you do not want to give users having this role the capability to delete themselves on the front end, leave the <i>Delete self on the front end</i> box unchecked.', 'describr' ), 
                            '<code>delete_users</code>' 
                        ) . '</p>',
                    )
                );
                break;
            case 'adcap':
                $display_name = '';
                
                if ( isset( $_REQUEST['role'] ) ) {
                    $role = wp_kses_post( wp_unslash( $_REQUEST['role'] ) );
                    
                    $editable_roles = get_editable_roles();
                    
                    if ( isset( $editable_roles[ $role ] ) ) {
                        $display_name = translate_user_role( $editable_roles[ $role ]['name'] );
                        $display_name =" $display_name";
                    }
                }

                get_current_screen()->add_help_tab(
                    array(
                        'id'      => 'overview',
                        'title'   => __( 'Overview', 'describr' ),
                        'content' =>  '<p>' . sprintf(
                            /*translators: 1: Role's display name. 2: Plugin's name.*/
                            __( 'This screen lists all capabilities for all roles for your site. Capabilities beside checked boxes are associated with this role. If you want to grant this role a capability, check the box besides that capability. If you want not to grant this role a capability, uncheck the box beside that capability. If you want to remove a capability from this role completely, both uncheck the box beside that capability and check the <i>Remove role%1$s unchecked capabilities</i> box. %2$s&#039;s capabilities will always be displayed, even if they are not granted to this role.', 'describr' ),
                            $display_name,
                            DESCRIBR
                    ) .
                        '</p>'
                    )
                );
                get_current_screen()->add_help_tab(
                    array(
                        'id'      => 'screen-content',
                        'title'   => __( 'Screen Content', 'describr' ),
                        'content' => '<p>' . __( 'The capability to delete users is typically restricted to the Administrator (by way of having the delete_users capability) and Super Admin (on multisite) roles. However, <strong>if you want to give users having this role the capability to delete themselves on the front end of the site</strong>, you may check the <i>Delete self on the front end</i> box. If you do not want to give users having this role the capability to delete themselves on the front end, leave the <i>Delete self on the front end</i> box unchecked.', 'describr' ) . '</p>',
                    )
                );
                break;
            case 'adredirect':
            case 'delete':
                break;
            default:
                add_screen_option( 'per_page', array( 'option' => 'describr_roles_per_page' ) );
                // Contextual help - choose Help on the top right of admin panel to preview this.
                get_current_screen()->add_help_tab(
                    array(
                        'id'      => 'overview',
                        'title'   => __( 'Overview', 'describr' ),
                        'content' =>  '<p>' . __( 'This screen lists all the existing roles for your site. There are five defined roles: Administrator, Editor, Author, Contributor, and Subscriber.', 'describr' ) .
                        '</p>'
                    )
                );

                get_current_screen()->add_help_tab(
                    array(
                        'id'      => 'screen-content',
                        'title'   => __( 'Screen Content', 'describr' ),
                        'content' => '<p>' . __( 'You can customize the display of this screen in a number of ways:', 'describr' ) . '</p>' .
                                     '<ul>' .
                                     '<li>' . __( 'You can hide/display columns based on your needs and decide how many roles to list per screen using the Screen Options tab.', 'describr' ) . '</li>' .
                                     '<li>' . __( 'You can view all users assigned a role by clicking on the number under the Users column.', 'describr' ) . '</li>' .
                                     '</ul>',
                    )
                );

                get_current_screen()->add_help_tab(
                    array(
                        'id'      => 'action-links',
                        'title'   => __( 'Available Actions', 'describr' ),
                        'content' => '<p>' . __( 'Hovering over a row in the roles list will display action links that allow you to manage roles. You can perform the following actions:', 'describr' ) . '</p>' .
                                    '<ul>' .
                                    '<li>' . __( '<strong>Edit</strong> takes you to the screen to edit that role. You can also reach that screen by clicking on the role.', 'describr' ) . '</li>' .
                                    '<li>' . sprintf(
                                        /*translators: Plugin's name.*/
                                        __( '<strong>Delete</strong> brings you to the Delete Roles screen for confirmation, where you can permanently remove a role. You can also delete multiple roles at once by using bulk actions. However, %s does not allow deletion of WordPress&#039; defined roles.', 'describr' ), 
                                        DESCRIBR 
                                    ) . '</li>' .
                                    '<li>' . __( '<strong>Add redirect link</strong> takes you to the screen to add redirect links to that role. You can also add redirect links to a role on the Edit Role screen.', 'describr' ) . '</li>' .
                                    '<li>' . __( '<strong>Add capabilities</strong> takes you to the screen to add capabilities to or remove capabilities from that role. You can also add capabilities to or remove capabilities from that role on the Edit Role screen.', 'describr' ) . '</li>' .
                                    '</ul>',
                    )
                );
        
                get_current_screen()->set_help_sidebar(
                    '<p><strong>' . __( 'For more information:', 'describr' ) . '</strong></p>' .
                    '<p>' . __( '<a href="https://wordpress.org/documentation/article/roles-and-capabilities/">Descriptions of Roles and Capabilities</a>', 'describr' ) . '</p>' .
                    '<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>', 'describr' ) . '</p>'
                );

                get_current_screen()->set_screen_reader_content(
                    array(
                        'heading_views'      => __( 'Filter roles list', 'describr' ),
                        'heading_pagination' => __( 'Roles list navigation', 'describr' ),
                        'heading_list'       => __( 'Roles list', 'describr' ),
                    )
                );
                break;
        }
    }
    
    /**
     * Outputs the markup for role redirect links
     * 
     * @since 3.0
     */
    private static function redirects_markup() {
        $home      = __( 'The site&#039;s Home page', 'describr' );
        $login     = __( 'The site&#039;s Log-in page', 'describr' );
        $dashboard = __( 'The Dashboard', 'describr' );
        $profile   = __( 'The Profile page', 'describr' );

        $redirects = array(
            'register' => array(
                'header' => __( 'After registration, redirect to', 'describr' ),
                'custom_instruct' => /*translators: Hidden accessibility text.*/ __( 'in the following field, enter a custom URL to redirect to after registration', 'describr' ),
                'custom_label'    => /*translators: Hidden accessibility text.*/ __( 'Custom URL to redirect to after registration:', 'describr' ),
                'radios' => array(
                    'home'      => $home,
                    'dashboard' => $dashboard,
                    'profile'   => $profile,
                    'login'     => $login,
                ),
            ),
            'login' => array(
                'header' => __( 'After logging in, redirect to', 'describr' ),
                'custom_instruct' => /*translators: Hidden accessibility text.*/ __( 'in the following field, enter a custom URL to redirect to after logging in', 'describr' ),
                'custom_label'    => /*translators: Hidden accessibility text.*/ __( 'Custom URL to redirect to after logging in:', 'describr' ),
                'radios' => array(
                    'home'      => $home,
                    'dashboard' => $dashboard,
                    'profile'   => $profile,
                ),
            ),
            'logout' => array(
                'header' => __( 'After logging out, redirect to', 'describr' ),
                'custom_instruct' => /*translators: Hidden accessibility text.*/ __( 'in the following field, enter a custom URL to redirect to after logging out', 'describr' ),
                'custom_label'    => /*translators: Hidden accessibility text.*/ __( 'Custom URL to redirect to after logging out:', 'describr' ),
                'radios' => array(
                    'home'  => $home,
                    'login' => $login,
                ),
            ),
            'deleteaccount' => array(
                'header'          => __( 'After account deletion, redirect to', 'describr' ),
                'custom_instruct' => /*translators: Hidden accessibility text.*/ __( 'in the following field, enter a custom URL to redirect to after account deletion', 'describr' ),                        
                'custom_label'    => /*translators: Hidden accessibility text.*/ __( 'Custom URL to redirect to after account deletion:', 'describr' ),
                'radios'          => array(
                    'home'  => $home,
                    'login' => $login,
                ),
            ), 
        );
        ?>
        <span id="describr-home-url" class="screen-reader-text"><?php echo esc_html( describr_home_url() ); ?></span>
        <span id="describr-dashboard-url" class="screen-reader-text"><?php echo esc_html( admin_url() ); ?></span>
        <span id="describr-profile-url" class="screen-reader-text"><?php echo esc_html( describr_home_url( 0, __( 'user' , 'describr' ) ) ); ?></span>
        <span id="describr-logout-url" class="screen-reader-text"><?php echo esc_html( remove_query_arg( 'amp;_wpnonce', describr_logout_url() ) ); ?></span>
        <table class="form-table" role="presentation">
        <?php
        $custom = __( 'Custom:', 'describr' );
        $custom_val = '\c\u\s\t\o\m';

        //Multiple roles have the same redirects that are fetched from the URLs        
        if ( isset( $_REQUEST['role'] ) ) {
            $role = wp_kses( wp_unslash( $_REQUEST['role'] ), 'strip' );
            $redirect_roles = get_option( 'describr_roles_redirects' );

            if ( isset( $redirect_roles[ $role ] ) ) {
                $cur_redirects = $redirect_roles[ $role ];
            }
        }

        foreach ( $redirects as $name => $settings ) {
            $name_ = $name . '_redirect';
            $id = "describr_{$name_}";
            $id_custom = "{$id}_custom";
            $name_custom = $name_ . '_custom';
            
            if ( isset( $_GET[ $name_ ] ) ) {
                $cur_val = wp_kses( trim( wp_unslash( $_GET[ $name_ ] ) ), 'strip' );
            } elseif ( isset( $cur_redirects[ $name_ ] ) ) {
                $cur_val = trim( $cur_redirects[ $name_ ] );
            } else {
                $cur_val = '';
            }
            
            if ( isset( $_GET[ $name_custom ] ) ) {
                $cur_val = wp_kses( trim( wp_unslash( $_GET[ $name_custom ] ) ), 'strip' );
                $cur_custom_val = $cur_val;
            } elseif ( ! in_array( $cur_val, array( 'home', 'dashboard', 'profile', 'login' ), true ) ) {
                $cur_custom_val = $cur_val;
            } else {
                $cur_custom_val = '';
            }
            ?>
            <tr>
                <th scope="row"><?php echo esc_html( $settings['header'] ); ?></th>
                <td>
                    <fieldset><legend class="screen-reader-text"><?php echo esc_html( $settings['header'] ); ?></legend>
                        <?php 
                        foreach ( $settings['radios'] as $val => $label ) {
                            $id_ = "{$id}_{$val}";
                            echo '<label for="' . esc_attr( $id_ ) . '"><input type="radio" name="' . esc_attr( $name_ ) . '" value="' . esc_attr( $val ) . '" id="' . esc_attr( $id_ ) . '" aria-describedby="describr-' . esc_attr( $val ) . '-url"' .
                            checked( $val, $cur_val, false )
                            . ' /> ' . esc_html( $label ) . '</label><br />';
                        }
                                
                        echo '<label><input type="radio" name="' . esc_attr( $name_ ) . '" id="' . esc_attr( $id_custom ) . '_radio" value="' . esc_attr( $custom_val ) . '"' .
                        checked( ! empty( $cur_custom_val) && $cur_custom_val === $cur_val, true, false )
                        . ' /> <span>' . esc_html( $custom ) . '<span class="screen-reader-text"> ' . esc_html( $settings['custom_instruct'] ) . '</span></span></label>' .
                        '<label for="' .esc_attr( $id_custom ) . '" class="screen-reader-text">' .
                            esc_html( $settings['custom_label'] ) .
                            '</label>' .
                            ' <input type="url" name="' . esc_attr( $name_custom ) . '" value="' . esc_attr( $cur_custom_val ) . '" id="' . esc_attr( $id_custom ) . '" class="regular-text" />' .
                            '<br />';
                        ?>
                    </fieldset>
                </td>
            </tr>
            <?php
        }
        ?>
        </table>
    <?php
    }
}
