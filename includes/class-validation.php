<?php
namespace describr;

/**
 * Validation class
 *
 * @package Describr
 * @since 3.0
 */
class Validation {
	/** 
	 * Digits regular expression
	 * 
	 * @since 3.0
	 * @var string
	 */
	const DIGITS = '\\p{Nd}';

	/** 
	 * Max extension length
	 * 
	 * @since 3.0
	 * @var int
	 */
	const EXT_LIMIT_AFTER_EXPLICIT_LABEL = 20;

	/** 
	 * Max length of extensions that follow likely extension label
	 * 
	 * @since 3.0
	 * @var int
	 */
	const EXT_LIMIT_AFTER_LIKELY_LABEL = 15;

	/** 
	 * Max length of etensions that follow ambiguous extension label
	 * 
	 * @since 3.0
	 * @var int
	 */
	const EXT_LIMIT_AFTER_AMBIGUOUS_LABEL = 9;

	/** 
	 * Max American extension length
	 * 
	 * @since 3.0
	 * @var int
	 */
	const EXT_LIMIT_WHEN_NOT_SURE = 6;

	/** 
	 * Optional extension ending
	 * 
	 * @since 3.0
	 * @var string
	 */
	const OPTIONAL_EXT_SUFFIX = '#?';

	/** 
	 * Flags to use when compiling regular expressions for phone numbers
	 * 
	 * @since 3.0
	 * @var string
	 */
	const REG_FLAG = 'iu';
            
	/**
	 * Validates kakaotalk ID
	 * 
	 * @since 3.0
	 * 
	 * @param string $date The ID to validate
	 * @return bool True for a valid date, otherwise false
	 */
	public function is_date( $date ) {
        $date = describr_normalize_date_sep( $date );
        $sep = describr_date_sep();
        $esc_sep = preg_quote( $sep, '/' );   
        
        foreach ( array_reverse( describr_date_formats() ) as $format ) {
			$pattern = array();

			foreach ( explode( $sep, $format ) as $part ) {
				$pattern[] = '(?<' . $part . '>[' . self::DIGITS . ']{' . strlen( $part ) . '})';
			}

			$pattern = implode( $esc_sep, $pattern );
            
            if ( preg_match( "/^$pattern\$/u", $date, $matches ) ) {
                if ( isset( $matches['MM'] ) && preg_match( '/^[0-9]+$/', $matches['MM'] ) && ! ( 12 >= (int) $matches['MM'] && 1 <= (int) $matches['MM'] ) ) {
                    return false;
                }

                if ( isset( $matches['DD'] ) && preg_match( '/^[0-9]+$/', $matches['DD'] ) && ! ( 31 >= (int) $matches['DD'] && 1 <= (int) $matches['DD'] ) ) {
                    return false;
                }

				return true;
			}
		}

		return false;
    }		

    /**
	 * Validates URL
	 * 
	 * @since 3.0
	 * 
	 * @param string| $url    The URL to validate
	 * @param bool    $social Social media base URL
	 * @return string|false The URL if it's valid, otherwise false
	 */
	public function is_url( $url, $social = false ) {
	    if ( '' === $url || ! ( is_string( $url ) || is_numeric( $url ) ) ) {
		    return false;
	    } 
        
        $protocols = implode( '|', array_map( 'preg_quote', wp_allowed_protocols() ) );
        $url = preg_match( '/^(' . $protocols . '):/is', $url ) ? $url : 'http://' . $url;
                    
        //Social media URLs should have handles
        if ( $social ) {
            $social = preg_quote( $social, '/' );

            if ( ! preg_match( '/^(' . $social . ')/is', $url ) || '' === preg_replace( '/^(' . $social . ')/is', '', $url ) ) {
                return false;
            } 
	    }

	    return $url;
	}		

    /**
     * Validates phone number
     * 
     * @since 3.0
     * 
     * @param string $phoneNumer The phone number
     * @return bool True if the phone number is valid, otherwise false
     */
	public function is_phonenumber( $phoneNumer ) {
        return 1 === preg_match( self::initPhoneNumberPattern(), $phoneNumer );
	}
        
    /**
     * Retrieves whether a password is strong
     * 
     * @since 3.0
     * 
     * @return strong $pass The password
     * @return bool True if password is strong, otherwise false
     */
    public static function is_strong_pass( $pass ) {
        $complexity = array( 
            '/[\p{Lu}]/u', 
            '/[\p{Ll}]/u', 
            '/[\p{N}]/u', 
        );
        
        $special_chars = describr_special_chars_entity_numbers();
        
        if ( $special_chars ) {
            $special_chars = implode( '', array_keys( $special_chars ) );
            $special_chars_ = preg_quote( $special_chars, '/' );
            
            $complexity[] = "/[{$special_chars_}]/";
        }

        foreach ( $complexity as $regexp ) {
            if ( ! preg_match( $regexp, $pass ) ) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * Returns regular expression for phone number
     * 
     * @since 3.0
     * 
     * @return string
     */
    protected static function initPhoneNumberPattern() {
        $possibleSeparatorsBetweenNumberAndExtLabel = "[ \xC2\xA0\\t,]*";
        
        //Optional full stop (.) or colon, followed by zero or more spaces/tabs/commas.
        $possibleCharsAfterExtLabel = "[:\\.\xEf\xBC\x8E]?[ \xC2\xA0\\t,-]*";
        
        //Here the extension is called out in more explicit way, i.e mentioning it obvious patterns
        //like "ext.". Canonical-equivalence doesn't seem to be an option with Android java, so we
        //allow two options for representing the accented o - the character itself, and one in the
        //unicode decomposed form with the combining acute accent.
        $explicitExtLabels = "(?:e?xt(?:ensi(?:o\xCC\x81?|\xC3\xB3))?n?|\xEF\xBD\x85?\xEF\xBD\x98\xEF\xBD\x94\xEF\xBD\x8E?|\xD0\xB4\xD0\xBE\xD0\xB1|anexo)";
            
        //One-character symbols that can be used to indicate an extension, and less commonly used
        //or more ambiguous extension labels.
        $ambiguousExtLabels = "(?:[x\xEF\xBD\x98#\xEF\xBC\x83~\xEF\xBD\x9E]|int|\xEF\xBD\x89\xEF\xBD\x8E\xEF\xBD\x94)";
        
        //When extension is not separated clearly.
        $ambiguousSeparator = '[- ]+';

        $rfcExtn = ';ext=' . self::extnDigits(self::EXT_LIMIT_AFTER_EXPLICIT_LABEL);
        $explicitExtn = $possibleSeparatorsBetweenNumberAndExtLabel . $explicitExtLabels
                . $possibleCharsAfterExtLabel . self::extnDigits(self::EXT_LIMIT_AFTER_EXPLICIT_LABEL)
                . self::OPTIONAL_EXT_SUFFIX;
        $ambiguousExtn = $possibleSeparatorsBetweenNumberAndExtLabel . $ambiguousExtLabels
                . $possibleCharsAfterExtLabel . self::extnDigits(self::EXT_LIMIT_AFTER_AMBIGUOUS_LABEL) . self::OPTIONAL_EXT_SUFFIX;
        $americanStyleExtnWithSuffix = $ambiguousSeparator . self::extnDigits(self::EXT_LIMIT_WHEN_NOT_SURE) . '#';
        
        //The first regular expression covers RFC 3966 format, where the extension is added using
        //";ext=". The second more generic where extension is mentioned with explicit labels like
        //"ext:". In both the above cases we allow more numbers in extension than any other extension
        //labels. The third one captures when single character extension labels or less commonly used
        //labels are used. In such cases we capture fewer extension digits in order to reduce the
        //chance of falsely interpreting two numbers beside each other as a number + extension. The
        //fourth one covers the special case of American numbers where the extension is written with a
        //hash at the end, such as "- 503#".
        $extensionPattern =
                $rfcExtn . '|'
                . $explicitExtn . '|'
                . $ambiguousExtn . '|'
                . $americanStyleExtnWithSuffix;
            
        //This is same as possibleSeparatorsBetweenNumberAndExtLabel, but not matching comma as
        //extension label may have it.
        $possibleSeparatorsNumberExtLabelNoComma = "[ \xC2\xA0\\t]*";
            
        //",," is commonly used for auto dialling the extension when connected. First comma is matched
        //through possibleSeparatorsBetweenNumberAndExtLabel, so we do not repeat it here. Semi-colon
        //works in Iphone and Android also to pop up a button with the extension number following.
        $autoDiallingAndExtLabelsFound = '(?:,{2}|;)';

        $autoDiallingExtn = $possibleSeparatorsNumberExtLabelNoComma
                    . $autoDiallingAndExtLabelsFound . $possibleCharsAfterExtLabel
                    . self::extnDigits(self::EXT_LIMIT_AFTER_LIKELY_LABEL) . self::OPTIONAL_EXT_SUFFIX;
        $onlyCommasExtn = $possibleSeparatorsNumberExtLabelNoComma
                    . '(?:,)+' . $possibleCharsAfterExtLabel . self::extnDigits(self::EXT_LIMIT_AFTER_AMBIGUOUS_LABEL)
                    . self::OPTIONAL_EXT_SUFFIX;

        //Here the first pattern is exclusively for extension autodialling formats which are used
        //when dialling and in this case we accept longer extensions. However, the second pattern
        //is more liberal on the number of commas that acts as extension labels, so we have a strict
        //cap on the number of digits in such extensions.
        $extensionPattern = $extensionPattern 
                    . '|' .$autoDiallingExtn 
                    . '|' . $onlyCommasExtn;
        
        //The minimum length of the national significant number.
        $minLengthForNSN = 2;
        //The plus sign signifies the international prefix.
        $plusChars = '+＋';

        //Regular expression of acceptable punctuation found in phone numbers, used to find numbers in
        //text and to decide what is a viable phone number. This excludes diallable characters.
        //This consists of dash characters, white space characters, full stops, slashes,
        //square brackets, parentheses and tildes. It also includes the letter 'x' as that is found as a
        //placeholder for carrier information in some phone numbers. Full-width variants are also
        //present.
        $validPunctuation = "-x\xE2\x80\x90-\xE2\x80\x95\xE2\x88\x92\xE3\x83\xBC\xEF\xBC\x8D-\xEF\xBC\x8F \xC2\xA0\xC2\xAD\xE2\x80\x8B\xE2\x81\xA0\xE3\x80\x80()\xEF\xBC\x88\xEF\xBC\x89\xEF\xBC\xBB\xEF\xBC\xBD.\\[\\]\\/~\xE2\x81\x93\xE2\x88\xBC";
        $starSign = '*';
        $minLengthPhoneNumberPattern = '[' . self::DIGITS . ']{' . $minLengthForNSN . '}';
        $validPhoneNumber = '[' . $plusChars . ']*(?:[' . $validPunctuation . $starSign . ']*[' . self::DIGITS . ']){3,}[' . $validPunctuation . $starSign . 'a-z' . self::DIGITS . ']*';
        $pattern = '/^' . $minLengthPhoneNumberPattern . '$|^' . $validPhoneNumber . '(?:' . $extensionPattern . ')?$/' . self::REG_FLAG;

        return $pattern;
    }
        
    /**
     * Returns regular expression for phone extension
     * 
     * @since 3.0
     * 
     * @return string
     */
    public static function initExtnPattern() {
        $extnPattern = self::extnDigits(self::EXT_LIMIT_AFTER_EXPLICIT_LABEL)
                       . '|' . self::extnDigits(self::EXT_LIMIT_AFTER_EXPLICIT_LABEL) 
                       . self::OPTIONAL_EXT_SUFFIX
                       .  '|' . self::extnDigits(self::EXT_LIMIT_AFTER_AMBIGUOUS_LABEL) 
                       . self::OPTIONAL_EXT_SUFFIX
                       . '|' . self::extnDigits(self::EXT_LIMIT_AFTER_LIKELY_LABEL) 
                       . '|' . self::extnDigits(self::EXT_LIMIT_WHEN_NOT_SURE) . '#';

        $pattern = '/^(' . $extnPattern . ')$/' . self::REG_FLAG;

        return $pattern;
    }

    /**
     * Helper method for constructing regular expressions for parsing. Creates an expression that
     * captures up to maxLength digits of extensions.
     *  
     * @since 3.0
     * 
     * @return string
     */
	protected static function extnDigits( $max_length ) {
	    return '([' . self::DIGITS . ']{1,' . $max_length . '})';
	}
}
