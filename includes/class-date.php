<?php
namespace describr;

/**
 * Date class
 *
 * @package Describr
 * @since 3.0
 */
class Date {
    /**
     * The date format separator
     *
     * @since 3.0
     * @var string
     */
    public $format_sep;
    
    /**
     * Date constructor
     *
     * @since 3.0
     */
    public function __construct() {
        /**
         * Filters the date format separator
         * 
         * @since 3.0
         * 
         * @param string $sep Date format separator
         */
        $this->format_sep = apply_filters( 'describr_date_format_separator', '/' );
    }

    /**
     * Trims the date separator from a date
     * 
     * @since 3.0
     * 
     * @param string $date Date
     * @return string Trimmed date
     */
    public function date_trim( $date ) {
        $date = trim( $date );
        return trim( $date, $this->format_sep );
    }

    /**
     * Retrieves the time zone as a string
     * 
     * The main purpose of this function is to convert
     * the UTC offset to ±HH:MM format to create a 
     * DateTimeZone object. If the argument is a valid
     * time zone, it's returned
     * 
     * Converting offset to ±HH:MM is courtesy of `wp_timezone_string()`
     * 
     * @since 3.0
     *
     * @param null|string $timezone The time zone
     * @return string PHP time zone name or a ±HH:MM formatted offset
     */
    public static function timezone_string( $timezone = null ) {
        if ( $timezone ) {
            $timezone = trim( $timezone );
        }
        
        //Test if user's time zone is unavailable, and fallback to the site's time zone
        if ( ! $timezone ) {
            $timezone = get_option( 'timezone_string' );
        
            //Return time zone if available
            if ( $timezone ) {
                $timezone = trim( $timezone );

                if ( $timezone ) {
                    return $timezone;
                }
            }
        
            //Then offset is in use
            $timezone = get_option( 'gmt_offset' );
        }
    
        //If we have a time zone, return it
        if ( in_array( $timezone, timezone_identifiers_list( \DateTimeZone::ALL_WITH_BC ), true ) ) {
            return $timezone;
        }

        $timezone = preg_replace( '/UTC\+?/', '', $timezone );
    
        $offset  = (float) $timezone;
        $hours   = (int) $offset;
        $minutes = ($offset - $hours);

        $sign      = $offset < 0 ? '-' : '+';
        $abs_hour  = abs( $hours );
        $abs_mins  = abs( $minutes * 60 );
        $gmt_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

        return $gmt_offset;
    }
    
    /**
     * Replaces the date's date format separator with the favored separator
     * 
     * @since 3.0
     * 
     * @param string $date The date
     * @return string The date containing the favored separator
     */
    public function normalize_sep( $date ) {
        if ( str_contains( $date, $this->format_sep ) ) {
            return $date;
        }

        if ( '/' === $this->format_sep ) {
            return str_replace( '-', $this->format_sep, $date );
        } elseif ( '-' === $this->format_sep ) {
            return str_replace( '/', $this->format_sep, $date );
        }

        return $date;
    }

    /**
     * Creates a full date from partail date
     * 
     * @since 3.0
     * 
     * @param string $date The partial date from which to create a full date
     * @return string A date with YYYY-MM-DD format
     */
    public function normalize_date( $date ) {
        $date = $this->normalize_sep( $this->date_trim( $date ) );
        $sep = $this->format_sep;

        $date_parts = explode( $sep, $date );

        $count = count( $date_parts );

        if ( 2 < $count ) {
            return $date;
        } elseif ( 2 === $count ) {            
            $date .= $sep . '01';
        } else {
            $date .= "{$sep}01{$sep}01";
        }

        return $date;
    }
    
    /**
     * Retrieves fully translated PHP date format
     * depending on user-submitted date
     * 
     * @since 3.0
     * 
     * @param string $date The date
     * @return string Translated date format
     */
    public function get_format_by_date( $date ) {
        $date = $this->normalize_sep( $this->date_trim( $date ) );
        
        $date_parts = explode( $this->format_sep, $date );
        
        $count = count( $date_parts );
        
        if ( 3 === $count ) {
            $format = describr_locale_date_format( 'date' );
        } elseif ( 2 === $count ) {
            /*translators: Localized date format, see https://www.php.net/manual/datetime.format.php*/
            $format = __( 'F Y', 'describr' );
        } else {
            $format = describr_locale_date_format( 'year' );
        }

        return $format;
    }   
    /**
     * Retrieves age based on birthdate
     *
     * @since 3.0
     * 
     * @param string $birthdate The birthdate
     * @return string An age
     */
    public function get_age( $birthdate ) {
        $birthdate = $this->normalize_date( $birthdate );
        $format = 'Y-m-d';
        $origin = date_create( date( $format, strtotime( $birthdate ) ) ); 
        $target = date_create( date( $format ) );
        $interval = date_diff( $origin, $target );

        if ( ! empty( $interval->y ) ) {
            $yrs = $interval->y;
            /*translators: Age. %s: Number of years.*/
            return sprintf( _n( '%s year old', '%s years old', $yrs, 'describr' ), $yrs );
        } elseif ( ! empty( $interval->m ) ) {
            $mm = $interval->m;
            /*translators: Age. %s: Number of months.*/
            return sprintf( _n( '%s month old', '%s months old', $mm, 'describr' ), $mm );
        } elseif ( ! empty( $interval->d ) ) {
            $wk = 7;
            $dd = $interval->d;

            if ( $dd < $wk ) {
                /*translators: Age. %s: Number of days.*/
                return sprintf( _n( '%s day old', '%s days old', $dd, 'describr' ), $dd );
                //Is one week a factor of days? Then we have only week (s) 
            } elseif ( ! ( $dd % $wk ) ) {
                $wks = $dd/$wk;
                /*translators: Age. %s: Number of weeks.*/
                return sprintf( _n( '%s week old', '%s weeks old', $wks, 'describr' ), $wks );
                //Weeks and days
            } else {
                $wks = $dd / $wk;
                $wks = (int) $wks;

                /*translators: Age. %s: Number of weeks.*/
                $age_wks = sprintf( _n( '%s week', '%s weeks', $wks, 'describr' ), $wks );

                $age_join = /*translators: Age.*/ _x( 'and', 'join number of weeks to number of days.', 'describr' );

                $days = $dd % $wk;
                /*translators: Age. %s: Number of days.*/
                $age_days = sprintf( _n( '%s day old', '%s days old', $days, 'describr' ), $days );

                return sprintf( '%s %s %s', $age_wks, $age_join, $age_days );
            }
        } else {
            return /*translators: Age.*/ __( 'Less than 1 day old', 'describr' );
        }
    }
        
    /**
     * Sorts cities by date in descending order
     * 
     * The method is used as the callback for `uasort()`
     * in wp-content/plugins/describr/includes/actions-filters/filters-profile.php
     * 
     * @see https://www.php.net/manual/en/function.uasort.php
     * 
     * @since 3.0
     * 
     * @param array $place_1 {
     *     Once city where the user has lived 
     *     @type string $moved The moved date 
     * }
     * @param array $place_2 {
     *     Another city where the user has lived
     *     @type string $moved The moved date 
     * }
     * @return int Number to pass to uasort()
     */
    public function sort_desc_lived_places( $place_1, $place_2 ) {
        $timezone = new \DateTimeZone( 'UTC' );
        $format = 'Y-m-d';
            
        $date_1 = 0;

        if ( ! empty( $place_1['moved'] ) ) {
            $date = date_create( date( $format, strtotime( $this->normalize_date( $place_1['moved'] ) ) ), $timezone );
            $date_1 = $date->getTimestamp();
        }
            
        $date_2 = 0;

        if ( ! empty( $place_2['moved'] ) ) {
            $date = date_create( date( $format, strtotime( $this->normalize_date( $place_2['moved'] ) ) ), $timezone );
            $date_2 = $date->getTimestamp();
        }

        return $date_2 - $date_1;
    }
        
    /**
     * Sorts jobs and schools in descending order
     * 
     * The method is used as the callback for `uasort()`
     * in wp-content/plugins/describr/includes/actions-filters/filters-profile.php
     * 
     * @see https://www.php.net/manual/en/function.uasort.php
     * 
     * @since 3.0
     * 
     * @param array $period_1 {
     *     School or job
     *     @type string $from The start date 
     * }
     * @param array $period_2 {
     *     Another school or job
     *     @type string $from The start date 
     * }
     * @return int Number to pass to uasort()
     */
    public function sort_desc_timeperiod( $period_1, $period_2 ) {
        $timezone = new \DateTimeZone( 'UTC' );
        $format = 'Y-m-d';

        $from_1 = 0;

        if ( ! empty( $period_1['from'] ) ) {
            $d1 = date_create( date( $format, strtotime( $this->normalize_date( $period_1['from'] ) ) ), $timezone );
                
            $from_1 = $d1->getTimestamp();
        }
            
        $from_2 = 0;

        if ( ! empty( $period_2['from'] ) ) {
            $d2 = date_create( date( $format, strtotime( $this->normalize_date( $period_2['from'] ) ) ), $timezone );
                
            $from_2 = $d2->getTimestamp();
        }

        return $from_2 - $from_1;
    }
    
    /**
     * Retrieves date related to the UTC timezone
     *
     * @since 3.0
     * 
     * @param string $date The date
     * @param false|string $format The date format
     * @return The date formatted in UTC timezone
     */    
    public function utc_date( $date, $format = false ) {
        if ( ! $format ) {
            $format = describr_locale_date_format( 'date' );
        }
        
        return wp_date( $format, strtotime( $this->normalize_date( $date ) ), new \DateTimeZone( 'UTC' ) );
    }
}