//Credit: https://github.com/behnammodi/polyfill/blob/master/math.polyfill.js
Math.sign || (Math.sign = function ( x ) {
    return ( (x > 0 ) - ( x < 0 ) ) || + x;
});

//Credit: https://stackoverflow.com/questions/31533963/polyfill-for-push-method-in-javascript
Array.prototype.push || (Array.prototype.push = function() {
    var i = 0;

    while ( i < arguments.length  ) {
        this[this.length] = arguments[i];

        i++;
    }

    return this.length;
});

Array.prototype.unshift || (Array.prototype.unshift = function() {
    var arr = [], i = 0;
    
    if ( ! arguments.length ) {
        return this.length;
    }

    while ( i < arguments.length ) {
        arr[i] = arguments[i];

        i++;
    }
    
    i = 0;

    while ( i < this.length  ) {
        arr[arr.length] = this[i];

        i++;
    }
    
    i = 0;
    
    while ( i < arr.length ) {
        this[i] = arr[i];

        i++;
    }

    return this.length;
});

//Credit: https://stackoverflow.com/questions/4775722/how-can-i-check-if-an-object-is-an-array
Array.isArray || (Array.isArray = function( arr ) {
    return Object.prototype.toString.call( arr ) === "[object Array]";
});

//Credit: https://stackoverflow.com/questions/8511281/check-if-a-value-is-an-object-in-javascript
Object.isObject || (Object.isObject = function( obj ) {
    return Object.prototype.toString.call( obj ) === "[object Object]" && null !== obj;
});

Object.prototype.hasOwnProperty || (Object.prototype.hasOwnProperty = function( prop ) {
    return "undefined" !== typeof this[ prop ];
});

Object.hasOwn || (Object.hasOwn = function( obj, prop ) {
    return Object.prototype.hasOwnProperty.call( obj, prop );
});

String.prototype.trim || (String.prototype.trim = function() {
    return this.replace( /^\s+|\s+$/, "" );
});

//Credit: https://learnersbucket.com/examples/interview/polyfill-for-array-foreach/
Array.prototype.forEach || (Array.prototype.forEach = function ( callback, context ) {
    var  i = 0;

    while ( i < this.length ) {
        callback.call( context, this[i], i, this );

        i++;
    }
});

//Credit: https://medium.com/nerd-for-tech/polyfill-for-array-map-filter-and-reduce-e3e637e0d73b
Array.prototype.map || (Array.prototype.map = function( callbackFn ) {
    var arr = [], i = 0;  

    while ( i < this.length ) { 
        /* call the callback function for every value of this array and push the returned value into our resulting array*/
        arr[ i ] = callbackFn( this[ i ], i, this );

        i++;
    }

    return arr;
});

Array.prototype.indexOf || (Array.prototype.indexOf = function ( item ) {
    var i = 0;

    while ( i < this.length ) {
        if ( item === this[i] ) {
            return i;
        }

        i++;
    }

    return -1;
});

//Array.includes polyfill
Array.prototype.includes || (Array.prototype.includes = function( item ) {
    var i = 0;

    while ( i < this.length ) {
        if ( item === this[i++] ) {
            return true;
        }
    }

    return false;
});

//Object.keys polyfill
Object.keys || (Object.keys = function ( obj ) {
    var i = 0, arr = [], n;
    
    if ( Object.isObject( obj ) ) {
        for ( n in obj ) {

            if ( Object.hasOwn( obj, n ) ) {
                arr.push( "" + n );
            }
        }
    } else if ( "string" === typeof obj || Array.isArray( obj ) ) {
        while ( i < obj.length ) {
            arr.push( "" + i );
            i++;
        }
    } else if ( null === typeof obj || "undefined" === typeof obj ) {
        throw new TypeError( "Expected \"obj\" to be an object, but received " + typeof obj + "." );
    }

    return arr;
});

/**
 * @namespace describr
 */
window.describr = window.describr || {};

jQuery(function ( $ ) {
    describr.uniqueID = 0;
    describr.uniqueId = function ( prefix ) {
        var id = ++this.uniqueID;

        if ( prefix ) {
            id = prefix + id;
        }
        
        return "" + id;
    };
    
    describr.pushStateTabs = [];
    describr.pushStatePaging = [];
    describr.pagingTabs = [];
    describr.pagingTabsRewriteBase = {};
    describr.pagingTabsRegExp = [];

    describr.displaySPAContent = function( tab ) {};
    describr.displaySPAPagingContent = function( specs ) {};
    
    describr.escapeHtml = function( str ) {
        var map = {
                "<" : "&lt;",
                ">" : "&gt;",
                "'" : "&#039;", // This is for single quotes, equivalent to ENT_QUOTES in PHP
                "\"": "&quot;"
            };
        return str.replace( /&(?![A-Za-z]{2,8}[0-9]{0,2};|#0*[0-9]{1,7};|#[Xx]0*[0-9A-Fa-f]{1,6};)/g, "&amp;" ).replace(/[<>"']/g, function(m) { return map[m]; });
    };

    describr.blogLocale = function() {
        return $( "html" ).eq( 0 ).attr( "lang" ) || "en-US";
    };

    describr.replaceState = function( curTabId ) {
        try {
            if ( 0 === $( "#" + curTabId ).length ) {
                return;
            }

            var href        = document.location.href,
                tabId       = curTabId,
                isPagingTab = describr.isPagingTab( href );

            if ( isPagingTab ) {
                tabId += "__paging=" + describr.determinePaged( href );
            }
            
            /**
             * @param {string} tabId Current tab ID
             * @param {string} ''    This is needed for backward compatibility with legacy sites, and should always be an empty string
             * @param {string} href  Document href
             */
            history.replaceState( tabId, "", href );

            if ( isPagingTab ) {
                describr.pushStatePaging.push( tabId );
            } else {
                describr.pushStateTabs.push( tabId );
            }
        } catch ( error ) {
            console.error( error.message );
        }
    };
    
    /**
     * Displays the current nonpaging tab's content and adds a new entry to the session history
     * 
     * Paging tabs (1, 2, 3, ...) are handled in profile.js
     * 
     * @param {jQuery object} tab The current tab ID
     * @return bool True if state is pushed, false otherwise
     */
    describr.pushState = function( tab ) {
        try {
            var id   = tab.attr( "id" ),
                href = tab.attr( "href" );
            
            //Add a new entry to the history
            history.pushState( id, "", href );

            if ( ! describr.pushStateTabs.includes( id ) ) {
                describr.pushStateTabs.push( id );
            }

            describr.displaySPAContent( tab );
        } catch ( error ) {
            console.error( error.message );
            return false;
        }

        return true;
    };

    describr.popState = function () {
        window.addEventListener( "popstate", function ( e ) {
            if ( e.state && describr.pushStateTabs.includes( e.state ) ) {
                describr.displaySPAContent( $( "#" + e.state ) );
            } else if ( e.state && describr.pushStatePaging.includes( e.state ) ) {
                describr.displaySPAPagingContent( e.state );
            }
        });
    };
    
    describr.isPagingTab = function( href ) {
        var i = 0;

        if ( 0 === describr.pagingTabsRegExp.length ) {
            return false;
        } else if ( /paged=([0-9]+)/.test( href ) ) {
            return true;
        }

        while ( i < describr.pagingTabsRegExp.length ) {
            if ( describr.pagingTabsRegExp[i++].test( href ) ) {
                return true;
            }
        }

        return false;
    };
    
    describr.determinePaged = function( href ) {
        var i = 0;

        if ( /paged=([0-9]+)/.test( href ) ) {
            return href.match( /paged=([0-9]+)/ )[1];
        }

        while ( i < describr.pagingTabsRegExp.length ) {
            if ( describr.pagingTabsRegExp[i].test( href ) ) {
                return href.match( describr.pagingTabsRegExp[i] )[1];
            }

            i++;
        }

        return 0;
    };

    describr.pagingTabsRegExpInit = function() {
        var i = 0;

        while ( i < describr.pagingTabs.length ) {
            describr.pagingTabsRegExp.push( new RegExp( "\\/" + ( /\-$/.test( describr.pagingTabsRewriteBase[ describr.pagingTabs[i] ] ) ? describr.escRegex( describr.pagingTabsRewriteBase[ describr.pagingTabs[i] ] ) : describr.escRegex( describr.pagingTabsRewriteBase[ describr.pagingTabs[i] ] ) + "\\/" ) + "([0-9]+)\\/?$" ) );
            
            i++;
        }
    };

    /**
     * Triggers the click event when the tab key is clicked on a tab
     * 
     * @param {jQuery object} tab Active Tab
     */
    describr.processTabKey = function( tab ) { 
        if ( tab instanceof jQuery ) {
            tab.on( "keydown", spacebarKeydownHandler );
        } else {
            $( document ).on( "keydown", tab, spacebarKeydownHandler );
        }
    };

    describr.poppers = {};
    
    //Toggle popups
    describr.togglePopper = function() {
        var pop, popper_, newPoppers = {};
        
        if ( ! this.objHasOwn( this, "createPopperInstance" ) ) {
            //create popper instance
            this.createPopperInstance = function( popper ) {
                if ( popper.instance ) {
                    return;
                }

                popper.instance = Popper.createPopper( popper.button[0], popper.popup[0], {
                    placement: "auto",//preferred placement of popper
                    modifiers: [
                        {
                            name: "offset",//offsets popper from the reference/button
                            options: {
                                offset: [0, 8],
                            },
                        },
                        { 
                            name: "flip",//flips popper with allowed placements
                            options: {
                                allowedAutoPlacements: ["top", "bottom"],
                                rootBoundary: "viewport",
                            }
                        }
                    ]
                });
            };

            //destroy popper instance
            this.destroyPopperInstance = function( popper ) {
                if (popper.instance) {
                    popper.instance.destroy();
                }
            };

            //show and create popper
            this.showPopper = function( popper ) {
                this.createPopperInstance( popper );
                popper.popup.attr("data-show", "");
                this.expand( popper.button );
                popper.show = false;
            };

            //hide and destroy popper instance
            this.hidePopper = function( popper ) {
                popper.popup.remove();
                this.noexpand( popper.button );
                this.destroyPopperInstance( popper );
            };
        }

        for ( pop in this.poppers ) {
            popper_ = this.poppers[pop];

            if ( popper_.show ) {
                this.showPopper(popper_);
                newPoppers[pop] = popper_;
            } else {
                this.hidePopper(popper_);
            }
        }
            
        this.poppers = newPoppers;
    };

    function spacebarKeydownHandler( e ) {
        var code;

        if ( Object.hasOwn( e, "key" ) ) {
            code = e.key;

            if ( " " === code ) {
                code = "Spacebar";
            }
        } else {
            code = e.keyCode || e.which;
                    
            if ( 32 === code ) {
                code = "Spacebar";
            }
        }

        if ( "Spacebar" === code ) {
            $( this ).trigger( "click" );
        }
    }

    /**
     * Removes element from array
     * 
     * @param array The array
     * @param mixed The element to remove
     * @return array An array with element removed
     */
    describr.arrSplice = function ( arr, elem ) {
        if ( ! arr.includes( elem ) ) {
            return arr;
        }

        var $arr = [];

        arr.forEach( function ( u ) {
            if ( u !== elem ) {
                $arr.push( u );
            }
        });
        
        return $arr;
    };
    
    /**
     * Decodes HTML entities
     * 
     * @param {string} text Text containing HTML entities
     * @return {string} Text containing decoded HTML entities
     */
    describr.decodeHTMLEntities = function ( text ) {
        var textArea = document.createElement( "textarea" );
        textArea.innerHTML = text;
        return textArea.value;
    };

    /**
     * Encodes URI component according to RFC3986
     * 
     * @param string str The URI component
     * @return string The encoded URI component
     */
    describr.encodeRFC3986URIComponent = function ( str ) {
        if ( str === this.decodeComponent( str ) ) {
            try {
                return encodeURIComponent( str ).replace( /[!'()*]/g, function ( c ) {
                    return "%" + c.charCodeAt(0).toString(16).toUpperCase();
                });
            } catch(e) {
                return str;
            }
        }
        
        return str;
    };        
    
    /**
     * Decodes URI component
     * 
     * @param string str The URI component
     * @return string The decoded URI component
     */
    describr.decodeComponent = function( str ) {
        try {
            return decodeURIComponent( str );
        } catch(e) {
            return str;
        }
    };

    describr.objHasOwn = function( obj, prop ) {
        return Object.hasOwn( obj, prop );
    };
    
    describr.isObject = function( obj ) {
        return Object.isObject( obj );
    };

    /**
     * Retrieves whether a value is scalar, of type boolean, int, float or string
     * 
     * @param {mixed} val The value to check
     * @return True if the value is scalar, otherwise false
     */
    describr.isScalar = function( val ) {
        var $typeof = typeof val;

        return $typeof === "boolean" || $typeof === "string" || ( $typeof === "number" && ! Number.isNaN( val ) );
    };

    describr.pageX = null;
    describr.click = typeof window.ontouchstart !== "undefined" ? "click touchstart touchend" : "click";
    describr.ajax = {
        url  : describr.ajaxurl || false,
        post : function ( action, data ) {
           return describr.ajax.send({
                data : Object.isObject( action ) ? action : $.extend( data || {}, { action : action } )
            });
        },
        send : function ( action, options ) {
            var deferred;
            if ( Object.isObject( action ) ) {
                options = action
            } else {
                options = options || {};
                options.data = $.extend( options.data || {}, { action : action } );
            }

            options = $.extend( options || {}, {
                type : "POST",
                url  : describr.ajax.url
            });
             
            deferred = $.Deferred( function ( deferred ) {
                deferred.jqXHR = $.ajax( options ).done( function ( response ) {
                    if ( Object.isObject( response ) && Object.hasOwn( response, "success" ) ) {
                        var context = this;
                        deferred.done(function(){
                            if ( 
                                action && 
                                action.data && 
                                action.data.action &&
                                ( "describr-profile-posts" === action.data.action || "describr-profile-comments" === action.data.action ) &&
                                Object.hasOwn( deferred.jqXHR, "getResponseHeader" )
                            ) {
                                context.totalPages = deferred.jqXHR.getResponseHeader( "X-DESCRIBR-Total" ) ? deferred.jqXHR.getResponseHeader( "X-DESCRIBR-Total" ) : 0;
                                context.results    = deferred.jqXHR.getResponseHeader( "X-DESCRIBR-Result" ) ? deferred.jqXHR.getResponseHeader( "X-DESCRIBR-Result" ) : 0;
                            }
                        });
                        deferred[ response.success ? "resolveWith" : "rejectWith" ]( this, [response.data] );
                    } else {
                        deferred.rejectWith( this, [response] );
                    }
                })/*.fail( function (response) {
                    if ( Object.isObject( response ) && response.hasOwnProperty( 'success' ) ) {
                        deferred.rejectWith( this, [response.data] );
                    } else {
                        deferred.rejectWith( this, ['_fail_'] );
                    }
                })*/;
            });
            
            return deferred.promise();
        }
    };
    
    /**
     * Returns a formatted string
     */
    describr.sprintf = function () {
        if ( ! arguments.length ) {
            return "";
        } else if ( 2 > arguments.length ) {
            return arguments[0];
        }

        var args = arguments,
            str  = args[0], 
            index = 0;
        if ( ["number","boolean"].includes( typeof str ) || null === str ) {
            str = String( str );
        }
        
        try {
            return str.replace( /%(([1-9]{1,1}[0-9]*)\$)?(?:s|d)/g, function ( match, capture1, capture2 ) {
                var replaced = capture1 ? args[ capture2 ] : args[ ++index ];
            
                return replaced;
            });
        } catch ( error ) {
            console.error( error.message );
        }
    };
    
    /**
     * Differentiates between click and swipe on supported devices
     */
    describr.isClicked = function ( e ) {
        if ( "click" === e.type ) {
            return true;
        } else if ( "touchstart" === e.type ) {
            //X-coordinate of first finger down
            describr.pageX = e.changedTouches[e.changedTouches.length-1].pageX;
            return false;
        } else if ( "touchend" === e.type ) {
            //X-coordinate of last finger up
            return e.changedTouches[e.changedTouches.length-1].pageX === describr.pageX;
        } else {
            return false;
        }
    };

    /**
     * Retrieves whether an argument is empty
     * 
     * @param mixed data
     * @return bool
     */
    describr.empty = function ( data ) {
        if ( typeof data === "undefined" || null === data ) {
            return true;
        } else if ( typeof data === "boolean" ) {
            return false === data;
        } else if ( Object.isObject( data ) ) {
            return ( 1 > Object.keys( data ).length );
        } else if ( Array.isArray( data ) ) {
            return ( 1 > data.length );
        } else if ( typeof data === "string" ) {
            return /^$/.test( data.trim() );
        } else if ( typeof data === "number" ) {
            return Number.isNaN( data );
        } else {
            return false;
        }
    };

    /**
     * Retrieves whether string has non-ASCII characters
     * 
     * @param string str
     * @return bool
     */
    describr.hasNonAscii = function ( str ) {
        return /[^\x00-\x7E]/g.test( str );
    };

    /**
     * Checks if unicode regex flag is supported
     * 
     * @return bool
     */
    describr.isUFlagSupported = function () {
        try {
            new RegExp("", "u");
            return true;
        } catch (e) {
            return false;
        }
    };
    
    /**
     * Retrieves string length
     * 
     * Retrieves string length the usual manner if the string doesn't have non-ASCII characters,
     * else do so using the Regex match method with the unicode flag set
     * 
     * @return int|bool
     */
    describr.getLength = function ( val ) {
        if ( ! describr.hasNonAscii( val ) ) {
            return val.length;
        }

        return describr.getUnicodeLength( val );
    };
    
    /**
     * Retrieves string length via regex
     * 
     * @return int|bool
     */
    describr.getUnicodeLength = function ( val ) {
        if ( "" === val ) {
            return 0;
        }

        if ( ! describr.isUFlagSupported() ) {
            return false;
        }

        return val.match( /[\s\S]/gu ).length;
    };
    
    describr.escRegex = function ( val ) {
        return val.replace( /[/\\.$*()^[\]+{}<>?|-]/g, "\\$&" );
    };

    /**
     * Retrieves whether Intl.Segmenter is supported
     * 
     * @param bool
     * @return Intl.Segmenter object|bool
     */
    describr.isIntl = (function(){
        try {
            var seg = new Intl.Segmenter( "en-US", { granularity : "grapheme" } );
        } catch(e){
            return false;
        };
        return seg;
    }());

    /**
     * Normalizes key codes
     * 
     * @param Event e
     * @return string Key code
     */
    describr.key = function ( e ) {
        var code = e.key || e.keyCode || e.which;
        
        if ( "Left" === code ) {
            code = "ArrowLeft";
        } else if ( "Right" === code ) {
            code = "ArrowRight";
        } else if ( "Up" === code ) {
            code = "ArrowUp";
        } else if ( "Down" === code ) {
            code = "ArrowDown";
        } else if ( 37 === code ) {
            code = "ArrowLeft";
        } else if ( 38 === code ) {
            code = "ArrowUp";
        } else if ( 39 === code ) {
            code = "ArrowRight";
        } else if ( 40 === code ) {
            code = "ArrowDown";
        } else if( 36 === code ) {
            code = "Home";
        } else if ( 35 === code ) {
            code = "End";
        } else if ( 33 === code ) {
            code = "PageUp";
        } else if ( 34 === code ) {
            code = "PageDown";
        }

        return code;
    };
    
    describr.retractControls = function() {
        $( ".describr" ).each( function() {
            $( this ).find( "[aria-expanded=true]" ).each( function() {
                describr.noexpand( $( this ) );
            });
        });
    };
    
    describr.expand = function( elem ) {
        return elem.attr( "aria-expanded", "true" );
    };
    
    describr.noexpand = function( elem ) {
        return elem.attr( "aria-expanded", "false" );
    };

    //Unhide HTML elements if JavaScript is supported
    $( ".describr-hide-if-no-js", $( document ) ).removeClass( "describr-hide-if-no-js" );
    
    //Make select and input elements whose types are neither checkbox nor radio valid on input
    $( ".describr-field-wrap input,.describr-field-wrap select" ).not( "input[type=checkbox],input[type=radio]" ).on( "input", function () {
        var that = $( this );
        
        $( "#" + that.attr( "id" ) + "-error" ).remove();

        that.attr( "aria-invalid" ) === "true" && that.attr( "aria-invalid" , "false" ).removeAttr( "aria-errormessage" );
    });

    //Remove notice element when the Dismiss button is clicked
    $( document ).on( describr.click, ".describr-notice-dismiss", function ( e ) {
        if ( describr.isClicked( e ) ) {
            $(this).closest( ".describr-notice" ).fadeOut( 200, "linear", function () {
                $(this).remove();
                $( "#describr-popup-center" ).hide();
            });
        }
    });

    //Close the modals when their backdrops are clicked
    $( ".describr-modal-backdrop" ).on( describr.click, function ( e ) {
        if ( describr.isClicked( e ) ) {
            $( this ).parent().hide();
            describr.retractControls();
        }
    });

    //Close modals when their Close buttons or Cancel buttons are clicked
    $( ".describr-close-modal,.describr-btn-cancel" ).on( describr.click, function ( e ) {
        if ( describr.isClicked( e ) ) {
            $( this ).closest( "[role=dialog]" ).parent().hide();
            describr.retractControls();
        }
    });
});
