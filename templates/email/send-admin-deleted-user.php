<?php
/**
 * Template for the "User Deleted Email".
 * 
 * Whether to send the admin an email when a user is deleted.
 *
 * This template can be overridden by copying it to {your-theme}/describr/templates/email/send-admin-deleted-user.php
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
		/*translators: Do not translate {BLOGNAME}; that is a placeholder.*/
		__( 'A user has been deleted from <strong>{BLOGNAME}</strong>.', 'describr' )) . "\r\n\r\n"; ?>
	</td>
</tr>
<tr>
	<td style="padding: 1em 0; vertical-align: baseline;"><?php echo wp_kses_post(
			/*translators: Do no translate {USERNAME}; that is a placeholder.*/
		__( '<strong>Username:</strong> {USERNAME}', 'describr' )) . "\r\n\r\n"; ?></td>
</tr>
<tr>
	<td style="padding: 0; vertical-align: baseline;"><?php echo wp_kses_post(
		/*translators: Do no translate {EMAIL}; that is a placeholder.*/
		__( '<strong>Email:</strong> {EMAIL}', 'describr' )) . "\r\n"; ?>
	</td>
</tr>
{FOOTER}