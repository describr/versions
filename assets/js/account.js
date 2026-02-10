jQuery(function($){
	var doc       = $( document );
    var click_    = describr.click;
    var isClicked = describr.isClicked;
    var sprintf   = describr.sprintf;
    var empty     = describr.empty;
    var tablist   = $( ".describr-account-tablist[role=tablist]" );
    var tabs      = tablist.find( "[role=tab]" );
    var tabpanels = $( ".describr-account-tab-content[role=tabpanel]" );
    var tab;

    handleClickKeydown();
    createArrowNavigation();
    determineTabindex();
    
    if ( Object.hasOwn( describr, "tabTitles" ) ) {
        for ( tab in describr.tabTitles ) {
            describr.tabTitles[ tab ] = describr.decodeHTMLEntities( describr.tabTitles[ tab ] );
        }
    }
    
    describr.replaceState( "describr-account-tab-" + describr.currentTab );
        
    //Make input elements whose types are checkbox and radio valid on change
    $( ".describr-field-wrap input[type=checkbox],.describr-field input[type=radio]" ).on( "change", function () {
        var $this = $( this );
        
        $( "#" + $this.attr( "id" ) + "-error" ).remove();

        if ( $this.attr( "aria-invalid" ) ) {
            $this.attr( "aria-invalid" ) === "true" && $this.attr("aria-invalid" , "false" ).removeAttr( "aria-errormessage" );
        } else if ( $this.closest( ".describr-field" ).attr( "aria-invalid" ) ) {
            $this.closest( ".describr-field" ).attr( "aria-invalid" ) === "true" && $this.closest( ".describr-field" ).attr( "aria-invalid", "false" ).removeAttr( "aria-errormessage" );
        }
    });
    
    //Validate required form fields
    tabpanels.each(function () {
        $( this ).find( "form" ).submit( function( e ) {
            var wrap       = $( this ).find( ".describr-field-wrap" ), 
                isRequired = false;
              
            wrap.find( "input,select" ).not( "input[type=checkbox],input[type=radio]" ).each( function() {
                //Password validation is handled in the user-pw.js file
                if ( ! ["describr-user-pass","describr-single-user-pass","describr-confirm-user-pass","describr-current-user-pass"].includes( $( this ).attr( "id" ) ) ) {
                    var $this = $( this ),
                        id    = $this.attr( "id" ),
                        label = $( "[for=\"" + id + "\"]" ).html() || "",
                        errId = id + "-error";

                    if ( $this.prop( "required" ) && empty( $this.val() ) ) {
                        $( "#" + errId ).remove();
                    
                        if ( /class\="describr\-req"/.test( label ) ) {
                            label = label.replace( /<span class\="describr\-req".*>.*<\/span>/s, "" ).trim();
                        };
                                        
                        $this.attr( { "aria-errormessage" : errId, "aria-invalid": "true" })
                        .closest( ".describr-field-wrap" ).before( "<p role=\"alert\" id=\"" + errId + "\" class=\"describr-field-error\">" + sprintf( describrL10n.required, label ) + "</p>" );
                        isRequired = true;
                    }
                }
                
            });

            if ( isRequired ) {
                e.preventDefault();
            }
        });
    });

    //Checkboxes
    doc.on( "change", ".describr .check-column input[type=checkbox]", function () {
        var checked = this.checked,
            elem = $( this ),
            id = elem.attr( "id" ),
            isAll  = /cb\-select\-all/.test( id );

        if ( isAll ) {
            elem.closest( "table" ).find( "input[type=checkbox]" ).each( function () {
                $( this ).prop( "checked", checked );
            });
        } else if ( checked ) {
            [1,2].forEach( function ( item ) {
                elem.closest( "table" ).find( "#describr-cb-select-all-" + item ).prop( "checked", checked );
            });
        }
    });
    
    //Destroy session
    $( "#describr-destroy-sessions" ).on( click_, function( e ) {
        if ( isClicked( e ) ) {
            var $this = $(this);

            describr.ajax.post( "describr-destroy-sessions", {
                nonce: $( "#describr-destroy-session-nonce" ).val(),
                user_id: $( "#describr-destroy-session-nonce" ).data( "userid" )
            }).done( function( response ) {
                $this.prop( "disabled", true );
                $this.siblings( ".describr-notice" ).remove();
                $this.before( "<div class=\"describr-notice describr-notice-success inline\" role=\"alert\"><p>" + response.message + "</p></div>" );
            }).fail( function( response ) {
                $this.siblings( ".describr-notice" ).remove();
                $this.before( "<div class=\"describr-notice describr-notice-error inline\" role=\"alert\"><p>" + response.message + "</p></div>" );
            });

            e.preventDefault();
        }
    });
    
    //Display the field's audit log
    $( ".describr-field-aside-audit" ).on( click_, function ( e ) {
        if ( isClicked( e ) ) {
            var $this      = $(this),
                audit      = describr.fieldLogs[ $this.data( "field" ) ] || {},
                fieldLabel = audit.label || false,
                modal      = $( "#describr-account-field-audit-modal" ),
                modalId    = modal.attr( "id" ),
                logs       = "";

            if ( ! fieldLabel ) {
                return;
            }

            audit.log.forEach( function ( log ) {
                logs += "<tr><td><strong>" + log.displayName + "</strong></td><td>" + log.date + "</td></tr>";
            });
            
            if ( "" !== logs ) {
                $( "#" + modalId + "-label" ).html( sprintf( describrL10n.modalLabel, fieldLabel ) );//Modal label
                $( "#" + modalId + "-main-field" ).html( fieldLabel );//Modal subtitle
                
                modal.find( "tbody" ).html( logs )//Add log records to the table
                .find("caption").html( sprintf( describrL10n.modalTableCaption, fieldLabel ) ) //Add table caption
                .end()
                .end()
                .parent().show();//Show the modal

                describr.expand( $this );
            }
        }
    });
    
    //Add this Dismiss button to notice elements and fadeout those with "fade" class
    $( ".describr-notice" ).each( function () {
        var that = $(this);
        
        if ( that.hasClass( "is-dismissible" ) ) {
            that.append( "<button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button>" );
        }

        if ( that.hasClass( "fade" ) ) {
            that.fadeOut( 30*1000, "linear", function () {
                $(this).remove();
            });
        }
    });
    
    describr.popState();
    
    describr.displaySPAContent = function( tab ) {
        setPageTitle( tab );
        showActivePanel( tab );
        setSelectedTab( tab );
    };

    /**
     * Sets tabindex and aria-selected on the clicked tab
     * 
     * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
     * 
     * @param {jQuery object} tab Active tab
     */
    function setSelectedTab( tab ) {
        tabs.each( function () {
            $( this ).attr( "aria-selected", "false" );
        });

        tab.attr( "aria-selected", "true" );
    }

    /**
     * Sets click and keydown events handlers
     * 
     * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
     */
    function handleClickKeydown() {
        tabs.each( function () {
            var tab = $( this );
            tab.on( click_, function ( e ) {
                if ( isClicked( e ) ) {
                    describr.pushState( tab );
                    e.preventDefault();
                }
            });
            
            describr.processTabKey( tab );
        });
    }

    /**
     * Shows the active tabpanel when its related tab is clicked
     * 
     * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
     * 
     * @param jQuery object element The active tab
     */
    function showActivePanel( element ) {
        //Hide all tabpanels
        tabpanels.each( function () {
            $( this ).removeClass( "current" );
        }); 
            
        //Show the tab panel controlled by this tab
        $( "[aria-labelledby=\"" + element.attr( "id" ) + "\"]" ).addClass( "current" );
        determineTabindex();
    }
    
    /**
     * Prevents focus on tabpanels that have focusable elements
     * 
     * The focus will jump to the first focusable element in the tabpanel
     * 
     * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
     */
    function determineTabindex() {
        tabpanels.each( function () {
            var panel = $( this );
            panel.find( "button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"]):not([disabled]), details:not([disabled]), summary:not(:disabled)" ).length ? panel.attr( "tabindex", "-1" ) : panel.attr( "tabindex", "0" );
        });
    }

    /**
     * Moves tab focus when arrow keys are pressed
     * 
     * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
     */
    function createArrowNavigation() {
        var firstTab = tabs.eq( 0 ),
            lastTab  = tabs.eq( tabs.length - 1 );
        tabs.each( function () {
            $( this ).on( "keydown", function ( e ) {
                var tab       = $( this ),
                    activeTab = false,
                    i;

                if ( "ArrowUp" === describr.key( e ) || "ArrowLeft" === describr.key( e ) ) {
                    e.preventDefault();
                    
                    if ( tab.is( firstTab ) ) {
                        activeTab = lastTab;
                    } else {
                        i = parseInt( tabs.index( tab ), 10 ) - 1;                        
                        activeTab = tabs.eq( i );
                    }
                } else if ( "ArrowRight" === describr.key( e ) || "ArrowDown" === describr.key( e ) ) {
                    e.preventDefault();
                    
                    if ( tab.is( lastTab ) ) {
                        activeTab = firstTab;
                    } else {
                        i = parseInt( tabs.index( tab ), 10 ) + 1;                        
                        activeTab = tabs.eq( i );
                    }
                }

                if ( activeTab ) {
                    describr.pushState( activeTab );
                    activeTab.focus();
                }
            });
        });
    }
    
    /**
     * Updates the page's title
     * 
     * @param {jQuery object} tab The current tab ID
     */
    function setPageTitle( tab ) {
        var tabName = tab.data( "tab" );
        
        if ( Object.hasOwn( describr, "tabTitles" ) && describr.tabTitles[tabName] ) {
            document.title = describr.tabTitles[tabName];
        }
    }
});