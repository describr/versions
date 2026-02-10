jQuery(function( $ ) {
	var click_ = describr.click;
	var isClicked = describr.isClicked;
	var blacklist = []; 
	var pass1;
	var pass2;
	var currentPass;
	var passMinLen = 6;
	var passMaxLen = 127;
	var locale = describr.blogLocale();
	var enforceStrongPass = false;
	var confirmPass = false;
	var isLoggedIn = false;
	var puncts = false;
	var passStrengthResult = $( ".describr-field-wrap #describr-pass-strength-result" );
	var errStr;
	var toggleButton;
    
    /**
	 * Generates password
	 */
	function generatePassword() {
		if ( typeof zxcvbn !== "function" && "describr_smtp_pass" !== pass1.attr( "id" ) ) {
			setTimeout( generatePassword, 50 );
			return;
		} else if ( ! pass1.val() ) {
			// zxcvbn loaded before user entered password, or generating new password.
			pass1.val( pass1.data( "pw" ) );
		} else {
			// zxcvbn loaded after the user entered password, check strength.
			checkPassStrength();
		}

		/*
		 * This works around a race condition when zxcvbn loads quickly and
		 * causes `generatePassword()` to run prior to the toggle button being
		 * bound.
		 */
		bindToggleButton();
		
		if ( "describr_smtp_pass" ===  pass1.attr("id") && 1 == parseInt( toggleButton.data( "start-masked" ), 10 ) ) {
			//Otherwise, mask the password
			toggleButton.trigger( "click" );
		}

		//Focus the password field
		if ( "describr_smtp_pass" !== pass1.prop("id" ) ) {
			$( pass1 ).trigger( "focus" );
		}
	}

	function bindPasswordForm() {
		if ( Object.hasOwn( describr, "pwd" ) ) {
			enforceStrongPass = describr.pwd.strong;
			confirmPass = describr.pwd.confirm;

			if ( enforceStrongPass ) {
				isLoggedIn = describr.pwd.isLoggedIn;
			    passMinLen = parseInt( describr.pwd.min_chars, 10 );
			    passMaxLen = parseInt( describr.pwd.max_chars, 10 );
			    blacklist = blacklist.concat( [ "pwd", "pwd1", "pwd2" ] );
			    
			    if ( describr.pwd.puncts ) {
			    	puncts = new RegExp( "[" + describr.escRegex( describr.pwd.puncts ) + "]" );
			    } else {
			    	puncts = false;
			    }
			}
		}
        
        pass1 = $( "#describr-user-pass,#describr_smtp_pass" );
		
		if ( pass1.length ) {
			bindPass1();
		}

		bindToggleButton();

		/*
		 * Fix a LastPass mismatch issue, LastPass only changes pass2.
		 *
		 * This fixes the issue by copying any changes from the hidden
		 * pass2 field to the pass1 field, then running check_pass_strength.
		 */
		pass2 = $( "#describr-confirm-user-pass" );
		pass2.on( "input", function () {
			if ( "hidden" === pass2.attr( "type" ) && pass2.val().length > 0 ) {
				pass1.val( pass2.val() );
				currentPass = "";
				pass1.trigger( "pwupdate" );
			}
		});
    }
    
    function bindPass1() {
    	currentPass = pass1.val();

		if ( 1 === parseInt( pass1.data( "reveal" ), 10 ) ) {
			generatePassword();
		}

		pass1.on( "input pwupdate", function () {
			if ( pass1.val() === currentPass ) {
				return;
			}

			currentPass = pass1.val();

			//Refresh password strength area
			pass1.removeClass( "short bad good strong empty" );
			addPassStrengthIndicatorToPass1();
		});
    }
    
    /**
	 * Initializes the button that toggles pass1
	 * 
	 * One event listener is used on both pass1 and pass2"s toggle buttons
	 */
    function bindToggleButton() {
    	if ( !! toggleButton ) {
			//Do not rebind
			return;
		}

		toggleButton = $( ".describr-hide-pw" );
		//Toggle passwords using button
        toggleButton.show().on( click_, function ( e ) {
        	if ( isClicked( e ) ) {
        		var toggleBtn = $( this ),
        		   _pass      = toggleBtn.prev( "input" );

        		if ( "password" === _pass.attr( "type" ) ) {
        			_pass.attr( "type", "text" );
        			resetToggle( toggleBtn, false );
        		} else {
        			_pass.attr( "type", "password" );
        			resetToggle( toggleBtn, true );
        		}
        	}
        });
    }
    
    /**
	 * Resets the toggle button
	 * 
	 * @param {jQuery Object} toggleBtn The button element
	 * @param {bool}          show      Whether the toggle is in hide/show mode
	 */
    function resetToggle( toggleBtn, show ) {
        toggleBtn
            .attr({
                "aria-label": show ? describrPwdL10n.show : describrPwdL10n.hide
            })
            .find( ".dashicons" )
                .removeClass( show ? "dashicons-hidden" : "dashicons-visibility" )
                .addClass( show ? "dashicons-visibility" : "dashicons-hidden" );
    }
    
    /**
	 * Resets the password element"s aria-invalid attribute
	 * 
	 * @param {jQuery object|string} elem The password element
	 * @param {bool}                 isInvalid Whether the password element contains invalid data
	 */
    function ariaInvalid( elem, isInvalid ) {
    	if ( ! ( elem instanceof jQuery ) ) {
    		elem = $( elem );
    	}

    	elem.attr( "aria-invalid", isInvalid ? "true" : "false" );
        
        var errId = elem.attr( "id" ) + "-error";

    	if ( isInvalid ) {
            elem.attr( "aria-errormessage", errId );
        } else {
        	elem.removeAttr( "aria-errormessage" );
        	$( "#" + errId ).remove();
        }
    }
    
    /**
     * Checks password stength
     * 
     * Credit to Benjamin Intal
     * 
     * @see https://code.tutsplus.com/using-the-included-password-strength-meter-script-in-wordpress--wp-34736a
     * 
     * @since 2.0
     * 
     * @return bool True if the password strength is strong, otherwise false
     */
    function checkPassStrength() {
    	if ( ! enforceStrongPass ) {
    		return;
    	}

    	var strengthResult = passStrengthResult.removeClass( "short bad good strong empty" ), 
    	    pass1_         = pass1.val(),
    	    errors         = strongPass( pass1_ ),
    	    strength;
        
        if ( ! pass1_ || "" === pass1_.trim() ) {
        	strengthResult.addClass( "empty" ).html( "&nbsp;" )
        	return false;
        }

        strength = wp.passwordStrength.meter( pass1_, enforceStrongPass ? blacklist.concat( wp.passwordStrength.userInputDisallowedList() ) : wp.passwordStrength.userInputDisallowedList(), pass1_ );
        
        if ( 0 !== errors.length ) {
       	    strength = 6;
        }

        switch ( strength ) {
            case -1:
            	strengthResult.addClass( "bad" ).html( describrPwdL10n.unknown );
            	break;
            case 2:
                strengthResult.addClass( "bad" ).html( describrPwdL10n.bad );
                break;
            case 3:
                strengthResult.addClass( "good" ).html( describrPwdL10n.good );
                break;
            case 4:
                strengthResult.addClass( "strong" ).html( describrPwdL10n.strong );
                break;
            case 5:
                strengthResult.addClass( "short" ).html( describrPwdL10n.mismatch );
                break;
            default:
                strengthResult.addClass( "short" ).html( describrPwdL10n.short );
                break;
        }
    }

    function addPassStrengthIndicatorToPass1() {
    	if ( passStrengthResult.length ) {
			var passStrength = passStrengthResult[0];

			if ( passStrength.className ) {
				pass1.addClass( passStrength.className );
			}
		}
    }
    
    /**
	 * Returns jQuery password error element
	 * 
	 * @param {string} id    ID of error element
	 * @param {string|array} Error object key or array of errors
	 */
    function createErrElem( id, err ) {
    	$( "#" + id ).remove();

    	return $( 
    		"<p />",
    		{
    		    "id"    : id, 
                "class" : "describr-field-error",
                "role"  : "alert",
                html    : Array.isArray( err ) ? err.join( "\n<br />" ) : errStr[err] 
            }
        );
    }
    
    function strongPass( pass ) {
    	if ( ! enforceStrongPass ) {
    		return [];
    	}

    	pass = pass.trim();
    	
    	var errors = [],
            pwdLen = describr.getLength( pass ),
            login  = "",
            email  = ""; 
                                        
        if ( puncts && ! puncts.test( pass ) ) {
            errors.push( errStr.puncts );
        }

        if ( false !== pwdLen ) {
            if ( pwdLen < passMinLen ) {
                errors.push( sprintf( errStr.minLen, passMinLen ) );
            }

            if ( pwdLen > passMaxLen ) {
                errors.push( sprintf( errStr.maxLen, passMaxLen ) );
            }
        }
        
        if ( Object.hasOwn( describr, "pwd" ) && Object.isObject( describr.pwd ) && Object.hasOwn( describr.pwd, "login" ) ) {
			login = describr.pwd.login;
		} else if ( 0 !== $( "#user_login" ).length ) {
			login = $( "#user_login" ).val().trim();
		}

		if ( "" !== login && -1 !== pass.toLocaleLowerCase( locale ).indexOf( login.toLocaleLowerCase( locale ) ) ) {
            errors.push( errStr.login );
        }

		if ( Object.hasOwn( describr, "pwd" ) && Object.isObject( describr.pwd ) && Object.hasOwn( describr.pwd, "email" ) ) {
			email = describr.pwd.email;
		} else if ( 0 !== $( "#user_email" ).length ) {
			email = $( "#user_email" ).val().trim();
		}

		if ( "" !== email && -1 !== pass.toLocaleLowerCase( locale ).indexOf( email.toLocaleLowerCase( locale ) ) ) {
            errors.push( errStr.email );
        }
                    
        if ( describr.isUFlagSupported() ) {
            //At least one lowercase letter, one uppercase letter, and one number
            if ( ! /[\p{N}]+/u.test( pass ) || ! /[\p{Ll}]+/u.test( pass ) || ! /[\p{Lu}]+/u.test( pass ) ) {
                errors.push( errStr.complexity );
            }
        }

        return errors;
    }

	$( function() {
		errStr = describrPwdL10n;

		$( "#describr-user-pass" ).on( "input pwupdate", checkPassStrength );        
        $( "#describr-user-pass" ).on( "input pwupdate", function () {
        	ariaInvalid( $( this ), false );
        });

        $( "#describr-confirm-user-pass,#describr-single-user-pass").on( "input", function () {
        	ariaInvalid( $( this ), false );
        });

		$( "#describr-user-pass,#describr-confirm-user-pass" ).val("").on( "blur", function (e) {
			if ( confirmPass ) {
				var elem  = $( this ),
				    wrap  = elem.closest( ".describr-field-wrap" ),   
                    id    = elem.attr( "id" ),
                    errId = id + "-error",
                    val1  = elem.val().trim();
                
                if ( 0 === val1.length ) {
                	return;
                }

                if ( "describr-user-pass" === id ) {
                	if ( val1 === pass2.val().trim() ) {
                        ariaInvalid( $( "#describr-confirm-user-pass" ), false );
                	} else if ( 0 !== $( "#describr-confirm-user-pass" ).val().length ) {
                		ariaInvalid( elem, true );
                        wrap.before( createErrElem( errId, "Mismatch" ) );
                	}
                } else {
                	if ( val1 === pass1.val().trim() ) {
                        ariaInvalid( $( "#describr-user-pass" ), false );
                	} else if ( 0 !== $( "#describr-user-pass" ).val().length ) {
                		ariaInvalid( elem, true );
                        wrap.before( createErrElem( errId, "Mismatch" ) );
                	}
                }
			}
			
		});
        
        //Toggle passwords using checkbox
        $( ".describr-toggle-pass" ).on( "change", function ( e ) {
        	var that = this,
        	    show = that.checked;
        	$( that ).data( "toggle" ).split( "," ).forEach( function( pwd ) {
        		$( "#" + pwd ).attr( "type", show ? "text" : "password" );
        	});
        });

        var singlePass = $( "#describr-single-user-pass" );
        singlePass.closest( "form" ).submit( function ( e ) {
        	var curPass = singlePass.val().trim();

        	if ( 0 === curPass.length ) {
        		singlePass.closest( ".describr-field-wrap" ).before( createErrElem( singlePass.attr( "id" ) + "-error", "none" ) );                
                ariaInvalid( singlePass, true );
                e.preventDefault();
        	}
        });

        var pass3 = $( "#describr-current-user-pass" );

        pass3.on( "input", function () {
        	ariaInvalid( $( this ), false );
        });

		$( "#describr-user-pass" ).closest( "form" ).submit( function ( e ) {		
			var pass1ErrId = pass1.attr( "id" ) + "-error",
			    pass1Wrap  = pass1.closest( ".describr-field-wrap" ),
			    pass1Val   = pass1.val().trim(),
		        pass2Val   = ( pass2.val() || "" ).trim(),		        
		        errors;

           	if ( 0 === pass1Val.length  ) {
                pass1Wrap.before( createErrElem( pass1ErrId, "none" ) );                
                ariaInvalid( pass1, true );
                e.preventDefault();
            } else if ( 0 !== pass3.length && 0 === pass3.val().trim().length ) {
            	pass3.closest( ".describr-field-wrap" ).before( createErrElem( pass3.attr( "id" ) + "-error", "curPass" ) );                
                ariaInvalid( pass3, true );
                e.preventDefault();
            } else if ( 0 !== pass3.length && pass3.val().trim() === pass1Val ) {
            	pass1Wrap.before( createErrElem( pass1ErrId, "same" ) );                
                ariaInvalid( pass1, true );
                e.preventDefault();
            } else if ( confirmPass && pass1Val !== pass2Val ) {
                pass1Wrap.before( createErrElem( pass1ErrId, "Mismatch" ) );
                ariaInvalid( pass1, true );
                e.preventDefault();
            } else if ( enforceStrongPass ) {
				checkPassStrength();
				errors = strongPass( pass1Val );

				if ( 0 !== errors.length ) {
					pass1Wrap.before( createErrElem( pass1ErrId, errors ) );
                    ariaInvalid( pass1, true );
                    e.preventDefault();
				}
			}
		});
		bindPasswordForm();
	});
});