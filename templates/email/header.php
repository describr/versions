<?php
/**
 * Template for the email header.
 *
 * This template can be overridden by copying it to {your-theme}/describr/templates/email/header.php
 *
 * @package Describr
 * @since 3.0
 * 
 * @var string $slug
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table role="presentation"###DESCRIBR_EMAIL_MAIN_TABLE_ATTRS###>
    <tr>
    	<td style="vertical-align: baseline; padding: 0; border: 0;">
    		<table role=" presentation" style="width: 100%; border: 0; border-collapse: collapse;"><?php /*<!--T2-->*/ ?>
                {LOGO}
    		    <tr>
                    <td style="padding: 20px 15px; vertical-align: baseline; border: 0;">
                        <table role="presentation" style="width: 100%; border: 0; border-collapse: collapse;"><?php /*<!--T3-->*/ ?>
                            <tr>
                                <td style="vertical-align: baseline; text-align: left; padding: 0; border: 0;"><?php /*<!--td-->*/ ?>
                                    <table role="presentation" style="border-collapse: collapse; width: 100%; border: 0;">

