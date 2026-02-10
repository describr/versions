<?php
/**
 * Actions for Admin.
 * 
 * @package Describr
 * @since 3.0
 */

//Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueues the main admin script.
 * 
 * @since 3.0
 * @since 3.0.1 Enqueues `popper.js`.
 * 
 * @param string $hook_suffix The current admin page.
 */
function describr_enqueue_admin_script( $hook_suffix ) {
    $is_network_settings_page = ( is_multisite() && 'settings.php' === $hook_suffix );
    $is_plugin_page           = str_contains( $hook_suffix, 'describr-roles' ) || str_contains( $hook_suffix, 'describr-options' );
    
    if ( $is_plugin_page || $is_network_settings_page ) {
        wp_enqueue_script( 'describr-main' );
        wp_enqueue_script( 'describr-admin-main' );
        
        if ( str_contains( $hook_suffix, 'describr-roles' ) ) {
            wp_enqueue_script( 'describr-popper' );

            $handle = 'describr-roles-list-table';
            $style = '
                 .widefat .num {
                    text-align: left;
                }';
            wp_register_style( $handle, false );
            wp_add_inline_style( $handle, $style );
            wp_enqueue_style( $handle );
            wp_add_inline_script( 'describr-admin-main', sprintf( 'describr.isMobile = %s;', wp_is_mobile() ? 'true': 'false' ), 'before' );
        }
        
        wp_enqueue_style( 'describr-admin-style' );
        
        if ( str_contains( $hook_suffix, 'describr-options' ) || $is_network_settings_page ) {
            describr_enqueue_pwd_script();
            wp_enqueue_style( 'describr-fontawesome' );
        }
    }
}
add_action( 'admin_enqueue_scripts', 'describr_enqueue_admin_script', 10, 1 );

/**
 * Styles the icon indicating an important user
 * 
 * @since 3.0
 * 
 * @param string $hook_suffix The current admin page
 */
function describr_enqueue_important_user_style( $hook_suffix ) {
    if ( in_array( $hook_suffix, array( 'profile.php', 'user-edit.php' ), true ) ) {
        $handle = 'describr-important-user';
        $style = '
                 .describr-important-user {
                    color: #135e96;
                }';
        wp_register_style( $handle, false );
        wp_add_inline_style( $handle, $style );
        wp_enqueue_style( $handle );
    }
}
add_action( 'admin_enqueue_scripts', 'describr_enqueue_important_user_style', 10, 1 );

/**
 * Adds localized script to {@see 'describr-admin-main'} handle
 * 
 * @since 3.0
 */
function describr_add_admin_localized_script() {
    if ( ! wp_script_is('describr-admin-main') ) {
        return;
    }

	$strings = array(
    	'tabPrivLabel'     => /*translators: %s: Profile tab name.*/ _x( 'Who can view %s:', 'profile tab', 'describr' ),
    	'editTabRoleTitle' => /*translators: %s: Profile tab name.*/ _x( 'Select %s Privacy Roles', 'profile tab', 'describr' ),
    	'ediRoles'         => __( 'Edit roles', 'describr' ),
        'moreDetails'      => __( 'Show more details', 'describr' ),
        'select'           => /*translators: Hidden accessibility text. %s: Field title.*/ __( 'Select %s', 'describr' ),
        'fields'           => _x( 'Fields', 'profile', 'describr' ),
        'selectAll'        => /*translators: Hidden accessibility text.*/ __( 'Select All', 'describr' ),
        'yes'              => /*translators: Hidden accessibility text.*/ _x( 'Yes', 'profile field setting', 'describr' ),
        'no'               => /*translators: Hidden accessibility text.*/ _x( 'No', 'profile field setting', 'describr' ),
        'none'             => /*translators: Hidden accessibility text.*/ _x( 'None', 'profile field setting','describr' ),
        'true'             => /*translators: Hidden accessibility text.*/ _x( 'True', 'spellcheck', 'describr' ),
        'false'            => /*translators: Hidden accessibility text.*/ _x( 'False', 'spellcheck', 'describr' ),
        'on'               => /*translators: Hidden accessibility text.*/ _x( 'On', 'autocomplete', 'describr' ),
        'off'              => /*translators: Hidden accessibility text.*/ _x( 'Off', 'autocomplete', 'describr' ),
        'fieldsTableCap'   => array(
            'profile' => /*translators: Hidden accessibility text.*/ __( 'Table of Profile Fields', 'describr' ),
            'social'  => /*translators: Hidden accessibility text.*/ __( 'Table of Social Media Fields', 'describr' ),
            'account' => /*translators: Hidden accessibility text.*/ __( 'Table of Account Settings Fields', 'describr' ),
        ),
        'fieldsModalTitle' => array(
            'profile' => __( 'Profile Fields', 'describr' ),
            'account' => __( 'Account Settings Fields', 'describr' ),
            'social'  => __( 'Social Media Fields', 'describr' ),
        ),
        'fieldsColname'    => array(
            'name'         => __( 'Meta key', 'describr' ),
            'label'        => __( 'Label', 'describr' ),
            'desc'         => _x( 'Description', 'profile field','describr' ),
            'cap'          => _x( 'Capability', 'user', 'describr' ),
            'type'         => _x( 'Type', 'profile field', 'describr' ),
            'html'         => __( 'HTML tags allowed', 'describr' ),
            'autocomplete' => __( 'Autocomplete','describr' ),
            'spellcheck'   => __( 'Spellcheck', 'describr' ), 
            'required'     => _x( 'Required', 'profile field','describr' ),
            'hash'         => __( 'Has hash key', 'describr' ), 
            'min_chars'    => __( 'Minumum number of characters','describr' ),
            'max_chars'    => __( 'Maximum number of characters', 'describr' ),
            'exactLength'  => __( 'Exact number of characters', 'describr' ), 
            'icon'         => _x( 'Icon', 'image', 'describr' ), 
            'hasIcon'      => /*translators: Hidden accessibility text.*/ _x( 'Has icon', 'image', 'describr' ),
            'baseurl'      => __( 'Base URL', 'describr' ), 
            'editable'     => _x( 'Editable', 'profile field', 'describr' ),
            'public'       => _x( 'Public', 'profile field', 'describr' ),
            'subkeys'      => _x( 'Subkeys', 'profile field', 'describr' ),
            'admin'        => _x( 'Administration', 'profile field', 'describr' ),
        ),
    );

    /**
     * Filters the plugin's admin strings
     *
     * @since 3.0
     *
     * @param array $strings An Array of admin strings keyed by 
     *                       the name they'll be referenced by 
     *                       in JavaScript
     */
    $strings = apply_filters( 'describr_admin_strings', $strings );

	wp_localize_script( 'describr-admin-main', 'describrL10n', $strings );
	
}
add_action( 'admin_footer', 'describr_add_admin_localized_script', 10, 0 );

/**
 * Outputs the HTML modal to manage the plugin's fields
 * 
 * @since 3.0
 */
function describr_output_manage_fields_modal() {
    global $hook_suffix;

    if ( ! str_contains( $hook_suffix, 'describr-options' ) ) {
        return;
    }
    ?>
    <div id="describr-admin-modal-wrapper" class="describr-admin-modal" tabindex="-1">
        <div role="dialog" tabindex="0" aria-labelledby="describr-admin-modal-title" id="describr-admin-modal">
            <div id="describr-admin-modal-wrap">
                <h1 id="describr-admin-modal-title"></h1>
                <button type="button" id="describr-admin-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'describr' ); ?>" class="describr-close-modal"><span aria-hidden="true" class="dashicons dashicons-no-alt"></span></button>
                <div id="describr-admin-modal-main"></div>
                <div id="describr-admin-modal-footer">
                    <div id="describr-admin-modal-footer-cancel-btn-wrap"><?php describr_secondary_button(); ?></div>
                </div>
            </div>
        </div>
        <div class="describr-modal-backdrop"></div>
    </div>
    <?php
}
add_action( 'admin_footer', 'describr_output_manage_fields_modal', 10, 0 );
