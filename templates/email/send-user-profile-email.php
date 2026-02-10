<?php
/**
 * Template for "User-to-user Email".
 *
 * This template can be overridden by copying it to {your-theme}/describr/templates/email/send-user-profile-email.php
 *
 * @package Describr
 * @since 3.0
 * @since 3.0.1 Replaces PHP's `sprintf()` formatted strings with placeholders.
 * @since 3.0.1 Uses placeholders to include the header and footer templates instead doing so using functions.
 * 
 * @var string $slug
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
{HEADER}
<tr>
	<td style="padding: 1em 0; vertical-align: baseline; border: 0; text-align: left;"><strong><?php esc_html_e( 'User:', 'describr' ); ?></strong> {DISPLAY_NAME}</td>
</tr>
<tr>
	<td style="padding: 1em 0; vertical-align: baseline; border: 0; text-align: left;"><strong><?php echo esc_html_x( 'Message:', 'email', 'describr' ); ?></strong> {MESSAGE}</td>
</tr>
<tr>
	<td style="padding: 20px 0; vertical-align: baseline; border: 0;">{REPLY_BUTTON}</td>
</tr>
{FOOTER}