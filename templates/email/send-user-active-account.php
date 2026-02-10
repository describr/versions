<?php
/**
 * Template for the "Activate Account Email".
 * 
 * Whether to send the user an email when account is activated.
 *
 * This template can be overridden by copying it to {your-theme}/describr/templates/email/send-user-active-account.php
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
	<td style="padding: 0; vertical-align: baseline;"><?php echo wp_kses_post(
		/*translators: Do not translate {DISPLAY_NAME}; that is a placeholder.*/
		__( 'Hello {DISPLAY_NAME},', 'describr' )) . "\r\n\r\n"; ?></td>
</tr>
<tr>
	<td style="padding: 20px 0; vertical-align: baseline;"><?php esc_html_e( 'Your account has been activated.', 'describr' ) . "\r\n"; ?></td>
</tr>
{LOGIN_BUTTON}
{FOOTER}
