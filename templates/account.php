<?php
/**
 * Template for the Account Page
 * 
 * This template can be overridden by copying it to {yourtheme}/describr/templates/account.php
 *
 * Page: "Account"
 *
 * @package Describr
 * @since 3.0
 *
 * @var string $mode
 * @var int    $form_id
 * @var array  $args
 */

//Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = describr()->account()->id;

$avatar = describr_floated_avatar( $user_id, 100 );

$tabs = describr()->account()->tabs;
$current_tab = describr()->account()->current_tab;
?>
<div class="describr <?php echo esc_attr( $this->get_class( $mode, $args ) ); ?> describr-<?php echo esc_attr( $form_id ); ?>">
	<?php
	/*This action is documented in wp-content/plugins/describr/includes/class-account.php*/
    do_action( 'describr_account_notices' );
    ?>
    
    <div id="describr-account-content">
    	<div class="describr-account-side">

		    <div class="describr-account-meta">

			    <?php echo wp_kses_post( $avatar ); ?>
        
                <br />
		    
		        <?php
		        $profile_url = describr_profile_url( '', $user_id );
		    
		        if ( current_user_can( 'edit_user', $user_id ) ) {
		    	    ?>

		    	    <a href="<?php echo esc_url( $profile_url ); ?>" class="describr-profile-link"> <?php esc_html_e( 'Edit profile', 'describr' ); ?></a>

		    	    <?php
		        } else {
		    	    ?>

		    	    <a href="<?php echo esc_url( $profile_url ); ?>" class="describr-profile-link"> <?php esc_html_e( 'View profile', 'describr' ); ?></a>

		    	    <?php
		        }
		        ?>
		    
            </div>
        
            <div class="describr-clear"></div>
        
            <ul class="describr-account-tablist" role="tablist" aria-orientation="vertical">

			    <?php
                foreach ( $tabs as $tab => $settings ) {
				    $tab_enabled = get_option( 'describr_account_tab_' . $tab );
				
				    if ( isset( $settings['custom'] ) || $tab_enabled ) {
					    ?>

					    <li role="none">
				            <a role="tab" data-tab="<?php echo esc_attr( $tab ); ?>" href="<?php echo esc_url( describr()->account()->tab_url( $tab ) ); ?>" class="describr-account-tab" id="describr-account-tab-<?php echo esc_attr( $tab );?>" aria-selected="<?php echo $tab === $current_tab ? 'true' : 'false'; ?>">
				        	    <?php
				        	    if ( ! empty( $settings['icon'] ) ) {
				        		    echo '<span aria-hidden="true" class="describr-account-tab-icon describr-tab-icon ' . esc_attr( $settings['icon'] ) . '"></span>';
				        	    }
				        	    ?>
				        	
				        	    <span class="describr-account-tab-title describr-tab-title"><?php echo esc_html( $settings['title'] ); ?></span>
					        </a>
                        </li> 

			            <?php
				    }
			    }
			    ?>
		    </ul>
	    </div>

	    <div id="describr-account-main">
		    <?php
	        unset($settings);
        
	        foreach ( $tabs as $tab => $settings ) {
		        $tab_enabled = get_option( 'describr_account_tab_' . $tab );

		        if ( isset( $settings['custom'] ) || $tab_enabled ) {
			        ?>

			        <div role="tabpanel" tabindex="<?php echo $tab === $current_tab ? '0' : '-1'; ?>" aria-labelledby="describr-account-tab-<?php echo esc_attr( $tab ); ?>" class="describr-account-tab-content<?php echo $tab === $current_tab ? ' current' : ''; ?>" data-tab="<?php echo esc_attr( $tab  ); ?>">
                        <form method="post" action="">
			    		    <?php
				            /*This action is documented in wp-content/plugins/describr/includes/class-account.php*/
				            do_action( 'describr_account_hidden_fields', $args, $tab );

				            $settings['with_header'] = true;

				            describr()->account()->render_tab_content( $tab, $settings, $args );
				            ?>
			            </form>

			        </div>

			        <?php
		        }
	        }
	        ?>

        </div>
    	
    </div>

</div>