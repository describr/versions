<?php
/**
 * Functions specific to the WordPress admin interface.
 *
 * @package Describr
 * @since 3.0
 */

//Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieves the defined roles.
 * 
 * @since 3.0
 * 
 * @return array An array of defined roles.
 */
function describr_defined_roles() {
	/**
     * Filters the defined roles.
     * 
     * @since 3.0
     * 
     * @param array $roles Defined roles.
     */
	return (array) apply_filters( 
		'describr_defined_roles', 
		array( 
			'administrator', 
			'editor', 
			'author', 
			'contributor', 
			'subscriber', 
		)
	);
}