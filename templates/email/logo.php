<?php
/**
 * Template for the email logo.
 *
 * This template can be overridden by copying it to {your-theme}/describr/templates/email/logo.php
 *
 * @package Describr
 * @since 3.0
 * 
 * @var false|string $img
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $img ) {
    ?>
    <tr>
        <td style="vertical-align: baseline; padding: 0; border: 0;">
            <table role="presentation" style="border-collapse: collapse; border: 0; width: 100%; box-sizing: border-box;">
                <tr>
                    <td style="padding: 10px 15px 8px; vertical-align: baseline; text-align: left;">
                        <a href="{SITE_URL}" style="margin: 0; padding: 0;"><?php echo wp_kses_post( $img ); ?></a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <?php
}


