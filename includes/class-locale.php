<?php
namespace describr;

/**
 * Locale class
 *
 * @package Describr
 * @since 3.0
 */
class Locale {
	/**
	 * Stores translated, common PHP datetime formats
	 * 
	 * @since 3.0
	 * @var array
	 */
	public $date_time_formats = array();

	/**
	 * Stored translated large number names initials
	 * 
	 * @since 3.0
	 * @var array
	 */
	public $large_number_initial = array();
	
	/**
	 * Stores phone number types, keyed by phone number type set by libphonenumber-for-php-lite.
	 * @see https://github.com/giggsey/libphonenumber-for-php-lite
	 * 
	 * @since 3.0
	 * @var array
	 */
	public $phone_number_type = array();
        
	/**
	 * Locale contructor
	 * 
	 * @since 3.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 0 );
	}
    
    /**
	 * Initializes translations
	 * 
	 * @since 3.0
	 */
	public function init() {
		$this->date_time_formats['date'] = /*translators: Date format for exact date, mainly about time zones, see https://www.php.net/manual/datetime.format.php.*/ get_option( 'date_format', _x( 'F j, Y', 'time zone date format', 'describr' ) );
		$this->date_time_formats['time'] = /*translators: Time format for exact time, mainly about time zones, see https://www.php.net/manual/datetime.format.php.*/ get_option( 'time_format', _x( 'g:i a', 'time zone time format', 'describr' ) );
		$this->date_time_formats['year'] = /*translators: Date format for exact year, mainly about time zones, see https://www.php.net/manual/datetime.format.php.*/ _x( 'Y', 'time zone time format', 'describr' );
            
        $this->large_number_initial['K']  = /*translators: One-letter abbreviation of the name for large number.*/ _x( 'K', 'Thousand initial', 'describr' );
        $this->large_number_initial['M']  = /*translators: One-letter abbreviation of the name for large number.*/ _x( 'M', 'Million initial', 'describr' );
        $this->large_number_initial['B']  = /*translators: One-letter abbreviation of the name for large number.*/ _x( 'B', 'Billion initial', 'describr' );
        $this->large_number_initial['T']  = /*translators: One-letter abbreviation of the name for large number.*/ _x( 'T', 'Trillion initial', 'describr' );
        $this->large_number_initial['Qa'] = /*translators: One-letter abbreviation of the name for large number.*/ _x( 'Qa', 'Quadrillion initial', 'describr' );

        $this->phone_number_type[0] = /*translators: Phone number type.*/ __( 'Fixed Line', 'describr' );
        $this->phone_number_type[1] = /*translators: Phone number type.*/ __( 'Mobile', 'describr' );
        $this->phone_number_type[2] = /*translators: Phone number type.*/ __( 'Fixed Line or Mobile', 'describr' );
        $this->phone_number_type[3] = /*translators: Phone number type.*/ __( 'Toll Free', 'describr' );
        $this->phone_number_type[4] = /*translators: Phone number type.*/ __( 'Premium Rate', 'describr' );
        $this->phone_number_type[5] = /*translators: Phone number type. The cost of this call is shared between the caller and the recipient.*/__( 'Shared Cost', 'describr' );
        $this->phone_number_type[6] = /*translators: Phone number type.*/ _x( 'VOIP', 'Voice over IP numbers. This includes TSoIP (Telephony Service over IP).', 'describr' );
        $this->phone_number_type[7] = /*translators: Phone number type. See http://en.wikipedia.org/wiki/Personal_Numbers.*/ _x( 'Personal Number', 'A personal number is associated with a particular person, and may be routed to either a MOBILE or FIXED_LINE number.', 'describr' );
        $this->phone_number_type[8] = /*translators: Phone number type.*/ __( 'Pager', 'describr' );
        $this->phone_number_type[9] = /*translators: Phone number type.*/ _x( 'UAN', 'Used for "Universal Access Numbers" or "Company Numbers". They may be further routed to specific offices, but allow one number to be used for a company.', 'describr' );
        $this->phone_number_type[10] = /*translators: Phone number type.*/ _x( 'Unknown', 'A phone number is of type UNKNOWN when it does not fit any of the known patterns for a specific region.', 'describr' );
        $this->phone_number_type[27] = /*translators: Phone number type.*/ __( 'Emergency', 'describr' );
        $this->phone_number_type[28] = /*translators: Phone number type.*/ __( 'Voicemail', 'describr' );
        $this->phone_number_type[29] = /*translators: Phone number type.*/ __( 'Shortcode', 'describr' );
        $this->phone_number_type[30] = /*translators: Phone number type.*/ __( 'Standard Rate', 'describr' );
	}

    /**
	 * Retrieves the translated date format
	 *
	 * @since 3.0
	 *
	 * @param string $key The type of date format
	 * @return string Translated date format
	 */
	public function get_date_time_format( $key ) {
        return $this->date_time_formats[ $key ];
	}
        
    /**
	 * Retrieves the translated large name for large number
	 *
	 * @since 3.0
	 *
	 * @param string $initial The large number name initial
	 * @return string Translated large number name initial
	 */
	public function get_large_number_name_initial( $initial ) {
		$initial = strtoupper( $initial );
        return isset( $this->large_number_initial[ $initial ] ) ? $this->large_number_initial[ $initial ] : '';
	}
        
    /**
	 * Retrieves the fully translated phone number type
	 *
	 * @since 3.0
	 *
	 * @param int $type_number The number to locate the phone number type
	 * @return string The fully translated phone number type
	 */
	public function get_phone_number_type( $type_number ) {
		return $this->phone_number_type[ $type_number ];
	}
}
