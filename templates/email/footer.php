<?php
/**
 * Template for the email footer.
 *
 * This template can be overridden by copying it to {your-theme}/describr/templates/email/footer.php
 *
 * @package Describr
 * @since 3.0
 * @since 3.0.1 Reduces the top and bottom padding for the data cell in the footer's table from 20-4px.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?> 
</table>                                 
<table role="presentation" style="border-collapse: collapse; width: 100%; border: 0;">
    <tr>
        <td style="padding: 0; vertical-align: baseline;">
            <p style="margin: 1em 0;"><?php echo "\r\n" . esc_html_x( 'Regards,', 'email closing', 'describr' ) . "<br />\r\n";
            echo wp_kses_post(
                    /*translators: Do not translate {SITE_NAME}; that is a placeholder.*/
                    __( 'The {SITE_NAME} Team', 'describr' ) 
                ) . "\r\n\r\n";
            ?>
            </p>
        </td>
    </tr>
</table>
             </td><?php /*<!--td-->*/ ?>
            </tr>
        </table><?php /*<!--T3-->*/ ?>
    </td>
</tr>
<?php /*<!--footer begins-->*/ ?>
<tr>
    <td style="padding: 4px 15px; vertical-align: baseline; border-top: 1px solid #f0f0f1; text-align: left;">
    	<table role="presentation" style="border-collapse: collapse; border: 0; font-size: 0.8em; width: 100%; margin: 0;">
            ###DESCRIBR_EMAIL_FOOTER###
    		<tr>
    			<td style="padding: 0; vertical-align: baseline;">
    				<table role="presentation" style="border-collapse: collapse; width: 100%; border: 0;">
                        ###DESCRIBR_EMAIL_ADDRESS_LINK###
                        ###DESCRIBR_UNSUBSCRIBE_LINK###
                    </table>
    			</td>
    		</tr>
    	</table>
    </td>
</tr><?php /*<!--footer ends-->*/ ?>
        </table><?php /*<!--T2-->*/ ?>
    	</td>
    </tr>
</table>