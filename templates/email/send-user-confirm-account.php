<?php
/**
 * Template for the "Confirm Account Email".
 * 
 * Whether to send the user an email to confirm account.
 *
 * This template can be overridden by copying it to {your-theme}/describr/templates/email/send-user-confirm-account.php
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
		/*translators: Do not translate {USERNAME}; that is a placeholder.*/
		__( '<strong>Username:</strong> {USERNAME}', 'describr' )) . "\r\n\r\n"; ?>
	</td>
</tr>
<tr>
	<td style="padding: 20px 0; vertical-align: baseline;">{CONFIRM_ACCOUNT_BUTTON}</td>
</tr>
{FOOTER}