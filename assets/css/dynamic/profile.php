<?php
//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! isset( $args['photosize'] ) || 'original' === $args['photosize'] ) {
	$args['photosize'] = describr_photo_size(); // Cannot be more than metadefault value.
}

if ( ! isset( $args['mobile_photosize'] ) || 'original' === $args['mobile_photosize']  ) {
	$args['mobile_photosize'] = 64;
}

$args['photosize'] = absint( $args['photosize'] );
$args['mobile_photosize'] = absint( $args['mobile_photosize'] );
?>
<style<?php echo current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"'; ?>> 
	<?php if ( ! empty( $args['main_area_max_width'] ) ) { ?>
		.describr-<?php echo esc_attr( $args['mode'] ); ?> .describr-profile-main {
			max-width: <?php echo esc_attr( $args['main_area_max_width'] ); ?>;
		}
	<?php } ?>
	.describr-<?php echo esc_attr( $args['mode'] ); ?> .describr-profile-picture img {
		width: <?php echo esc_attr( $args['photosize'] ); ?>px;
		height: <?php echo esc_attr( $args['photosize'] ); ?>px;
	}
	@media only screen and (max-width: 768px) {
		.describr-<?php echo esc_attr( $args['mode'] ); ?> .describr-profile-picture img {
		    width: <?php echo esc_attr( $args['mobile_photosize'] ); ?>px;
		    height: <?php echo esc_attr( $args['mobile_photosize'] ); ?>px;
	    }
	}
</style>
<?php