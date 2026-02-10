<?php
namespace describr;

/**
 * Form class
 *
 * @package Describr
 * @since 3.0
 */
class Form {
	/**
	 * Stores $_POST
	 * 
	 * @since 3.0
	 * @var array
	 */
	public $post_form = array();

	/**
	 * Stores asides fetched from $_POST
	 * 
	 * @since 3.0
	 * @var array
	 */
	public $asides = array();
    
    /**
	 * Stores form fields sent to the browser
	 * 
	 * @since 3.0
	 * @var array
	 */
	public $fields = array();
}