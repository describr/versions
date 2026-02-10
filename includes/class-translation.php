<?php
namespace describr;

/**
 * Translation class
 *
 * @package Describr
 * @since 3.0
 */
class Translation {
	/**
	 * Translation constructor
	 * 
	 * @since 3.0
	 */
	public function __construct() {
		add_filter( 'describr_locale_profile_url', array( $this, 'locale_profile_url' ), 10, 2 );
		add_filter( 'describr_locale_account_url', array( $this, 'locale_account_url' ), 10, 2 );
	}

	/**
	 * Detects whether WPML is active
	 * 
	 * @since 3.0
	 * 
	 * @return bool True if WPML is active, otherwise fals
	 */
	public function is_wpml_active() {
		static $is_active = null;

		if ( null !== $is_active ) {
			return $is_active;
		}

		$is_active = (bool) apply_filters( 'wpml_default_language', null );

		return $is_active;
	}

    /**
	 * Retrieves the translated URL for a 
	 * profile on the front end
	 * 
	 * @since 3.0
	 * 
	 * @param string $url      Profile's URL
	 * @param string $nicename User's nicename
	 * @return string Profile's translated URL
	 */
	public function locale_profile_url( $url, $nicename ) {
		if ( ! $this->is_wpml_active() ) {
			return $url;
		}

		$page_id = describr_get_page_id( 'profile' );

		$trans_page_id = apply_filters( 'wpml_object_id', $page_id, 'page', true );

		if ( $trans_page_id && $trans_page_id !== $page_id ) {
			$url = describr()->permalinks()->profile_url_add_path( get_permalink( $trans_page_id ), $nicename );
		}

		return $url;
	}
    
    /**
	 * Retrieves the translated URL for an
	 * account on the front end
	 * 
	 * @since 3.0
	 * 
	 * @param string $url Account's URL
	 * @param string $tab Slug for a tab on the account
	 * @return string Account's translated URL
	 */
	public function locale_account_url( $url, $tab ) {
		if ( ! $this->is_wpml_active() ) {
			return $url;
		}

		$page_id = describr_get_page_id( 'account' );

		$trans_page_id = apply_filters( 'wpml_object_id', $page_id, 'page', true );

		if ( $trans_page_id && $trans_page_id !== $page_id ) {
			$url = describr()->permalinks()->account_url_add_path( get_permalink( $trans_page_id ), $tab );
		}

		return $url;
	}
}