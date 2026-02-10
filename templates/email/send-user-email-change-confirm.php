<?php
/**
 * Template for the "Confirm Email Change Email".
 * 
 * Whether to send the user an email to confirm email change.
 *
 * This template can be overridden by copying it to {your-theme}/describr/templates/email/send-user-email-change-confirm.php
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
		__( 'Howdy {DISPLAY_NAME},', 'describr' )) . "\r\n\r\n"; ?></td>
</tr>
<tr>
	<td style="padding: 1em 0; vertical-align: baseline;"><?php echo esc_html( __( 'You recently requested to have the email address on your account changed.', 'describr' ) ) . "\r\n\r\n"; ?></td>
</tr>
<tr>
	<td style="padding: 20px 0; vertical-align: baseline;">{CONFIRM_EMAIL_BUTTON}</td>
</tr>
<tr>
	<td style="padding: 1em 0; vertical-align: baseline;"><?php echo esc_html( __( 'You can safely ignore and delete this email if you do not want to take this action.', 'describr' ) ) . "\r\n"; ?></td>
</tr>
{FOOTER}
