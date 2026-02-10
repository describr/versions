<?php
/**
 * Template for the profile Page
 * 
 * This template can be overridden by copying it to {yourtheme}/describr/templates/profile.php
 *
 * Page: "Profile"
 *
 * @package Describr
 * @since 3.0
 *
 * @var string $mode
 * @var array  $args
 */

//Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="describr <?php echo esc_attr( $this->get_class( $mode, $args ) ); ?>">
	<?php
	/**
	 * Prints data or scripts before the header
	 * of the profile on the front end
	 * 
	 * @since 3.0
	 * 
	 * @since array $args Additional arguments passed to the template
	 */
	do_action( 'describr_profile_before_header', $args );
	
	/**
	 * Prints the header of the profile on the front end
	 * 
	 * @since 3.0
	 * 
	 * @since array $args Additional arguments passed to the template
	 */
	do_action( 'describr_profile_header', $args );

	/**
	 * Prints data or scripts after the header
	 * of the profile on the front end
	 * 
	 * @since 3.0
	 * 
	 * @since array $args Additional arguments passed to the template
	 */
	do_action( 'describr_profile_after_header', $args );

	/**
	 * Prints the menu of the profile on the front end
	 * 
	 * @since 3.0
	 * 
	 * @since array $args Additional arguments passed to the template
	 */
	do_action( 'describr_profile_menu', $args );

	if ( get_option( 'describr_enable_profile_menu' ) ) {
    	$tabs = describr()->profile()->get_allowed_tabs();
    	$current_tab = describr()->profile()->current_tab;

    	if ( $tabs ) {
    		?>
    		<div class="describr-profile-main">
    			<?php
		        foreach ( $tabs as $tab => $settings ) {
		        	/**
                     * Prints data or scripts for tabs at the 
                     * opening of the main tag of the profile 
                     * on the front end
                     * 
                     * @since 3.0
                     * 
                     * @param string $tab         Tab
                     * @param string $current_tab Current tab
                     * @param array  $settings    Tab settings
                     * @param array  $args        Additional arguments passed to the template
                     */
		    	    do_action( 'describr_profile_menu_tab_content', $tab, $current_tab, $settings, $args );
                
                    /**
                     * Prints data or scripts for a specific tab
                     * in the main tag of the profile on the front end
                     * 
                     * The dynamic part of the action's name is
                     * the name of the tab
                     * 
                     * @since 3.0
                     * 
                     * @param string $current_tab Current tab
                     * @param string $settings    Tab settings
                     * @param array  $args        Additional arguments passed to the template
                     */
			        do_action( "describr_profile_menu_tab_content_{$tab}", $current_tab, $settings, $args );
		        }
		        ?>
    		</div>
    		<?php
	    }
    }
	?>
</div>