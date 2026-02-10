jQuery(function ( $ ) {
    var empty                       = describr.empty,
        click_                      = describr.click,
        isClicked                   = describr.isClicked,
        isScalar                    = describr.isScalar,
        isIntl                      = describr.isIntl,
        sprintf                     = describr.sprintf,
        getLength                   = describr.getLength,
        escRegex                    = describr.escRegex,
        encodeURIComponent_         = describr.encodeRFC3986URIComponent,
        escapeHtml                  = describr.escapeHtml,
        retractControls             = describr.retractControls,
        expand                      = describr.expand,
        noexpand                    = describr.noexpand,
        objHasOwn                   = describr.objHasOwn,
        isObject                    = describr.isObject,
        arrSplice                   = describr.arrSplice,
        DIGITS                      = "0-9\u0660-\u0669\u06F0-\u06F9\u07C0-\u07C9\u0966-\u096F\u09E6-\u09EF\u0A66-\u0A6F\u0AE6-\u0AEF\u0B66-\u0B6F\u0BE6-\u0BEF\u0C66-\u0C6F\u0CE6-\u0CEF\u0D66-\u0D6F\u0DE6-\u0DEF\u0E50-\u0E59\u0ED0-\u0ED9\u0F20-\u0F29\u1040-\u1049\u1090-\u1099\u17E0-\u17E9\u1810-\u1819\u1946-\u194F\u19D0-\u19D9\u1A80-\u1A89\u1A90-\u1A99\u1B50-\u1B59\u1BB0-\u1BB9\u1C40-\u1C49\u1C50-\u1C59\uA620-\uA629\uA8D0-\uA8D9\uA900-\uA909\uA9D0-\uA9D9\uA9F0-\uA9F9\uAA50-\uAA59\uABF0-\uABF9\uFF10-\uFF19",
        removeNoticeTimer           = null,
        modalField                  = false,
        modalHash                   = false,
        popperInstance              = null,
        extLimitAfterExplicitLabel  = 20,
        extLimitAfterLikelyLabel    = 15,
        extLimitAfterAmbiguousChar  = 9,
        extLimitWhenNotSure         = 6,
        optionalExtnSuffix          = "#?",
        localeCompareSupportsLocale = localeCompareSupportsLocales(),
        pictureWrap                 = $( "#describr-profile-picture" ),
        pictureKey                  = pictureWrap.data( "key" ),
        modalWrapper                = $( "#describr-modal-wrapper" ),
        fieldEditorModalWrapper     = $( "#describr-profile-field-editor-modal-wrapper" ),
        tinyMCEHtmlId               = "describr-profile-mce",
        describrMCETextarea         = $( "#" + tinyMCEHtmlId ),
        isTinyMCEEnabled            = false,
        isTinyMCEAutocompleteOff    = "off" === describrMCETextarea.attr( "autocomplete" ),
        messageTextareaHtmlId       = "describr-profile-message",
        tinyMCEMessageHtmlId        = messageTextareaHtmlId + "-mce",
        tinyMCEMessageTextarea      = $( "#" + tinyMCEMessageHtmlId ),
        isTinyMCEMessageEnabled     = false,
        messageSpellcheck           = "undefined" !== typeof describrMessageSpellcheck ? describrMessageSpellcheck : false,
        messageAutocomplete         = "undefined" !== typeof describrMessageAutocomplete ? describrMessageAutocomplete : false,
        fieldEditorsWrap            = $( "#describr-profile-field-editor-modal-main" ),
        rowClass                    = " describr-row",//Set margin between stacked block elements
        useJqueryUi                 = "undefined" !== typeof describrJqueryUi,
        accessibleFields            = {},
        updatingFields              = {},
        selectmenus                 = [],
        blogLocale                  = describr.blogLocale(),
        selectedAside,
        returnTo,
        activeFieldEditor,
        savedAsides,
        checkKey, 
        nonces, 
        userId,
        activeTabs;
    
    window.addEventListener( "load", function () {
        var i, sendMessageForm;

        if ( "undefined" !== typeof tinymce ) {
            for ( i = 0; i < tinymce.editors.length; i++ ) {
                if ( tinyMCEHtmlId === tinymce.editors[i].id ) {
                    isTinyMCEEnabled = true;
                } else if ( tinyMCEMessageHtmlId === tinymce.editors[i].id ) {
                    isTinyMCEMessageEnabled = true;
                }
            }
        }
        
        if ( isTinyMCEEnabled ) {
            tinymce.get( tinyMCEHtmlId ).getBody().addEventListener( "keydown", function () {
                $( "#" + tinyMCEHtmlId + "_ifr" ).removeClass( "describr-invalid" );
                
                //contenteditable element in iframe
                this.removeAttribute( "aria-invalid" );
                this.removeAttribute( "aria-errormessage" );
                
                describrMCETextarea.removeAttr( "aria-invalid aria-errormessage" );//tinymce textbox

                if ( $( "#" + tinyMCEHtmlId + "-error" ).is( ":visible" ) ) {
                    $( "#" + tinyMCEHtmlId + "-error" ).hide();
                }
            });

            describrMCETextarea.on( "keydown", function () {
                $( "#" + tinyMCEHtmlId + "_ifr" ).removeClass( "describr-invalid" );
                $( this ).removeAttr( "aria-invalid aria-errormessage" );

                tinymce.get( tinyMCEHtmlId ).getBody().removeAttribute( "aria-invalid" );
                tinymce.get( tinyMCEHtmlId ).getBody().removeAttribute( "aria-errormessage" );

                if ( $( "#" + tinyMCEHtmlId + "-error" ).is( ":visible" ) ) {
                    $( "#" + tinyMCEHtmlId + "-error" ).hide();
                }
            });
        }
        
        //Profile messaging
        if ( "undefined" !== typeof describrMessageActive && describrMessageActive ) {
            if ( isTinyMCEMessageEnabled ) {
                tinymce.get( tinyMCEMessageHtmlId ).getBody().addEventListener( "keydown", function () {
                    if ( $( "#" + messageTextareaHtmlId + "-error" ).is( ":visible" ) ) {
                        $( "#" + tinyMCEMessageHtmlId + "_ifr" ).removeClass( "describr-invalid" );
                
                        this.removeAttribute( "aria-invalid" );
                        this.removeAttribute( "aria-errormessage" );
                
                        tinyMCEMessageTextarea.removeAttr( "aria-invalid aria-errormessage" );

                        $( "#" + messageTextareaHtmlId + "-error" ).hide();
                    }
                });
            
                tinyMCEMessageTextarea.attr({ "autocomplete": messageAutocomplete ? "on" : "off", "spellcheck" : messageSpellcheck ? "true" : "false" }).on( "keydown", function () {
                    if ( $( "#" + messageTextareaHtmlId + "-error" ).is( ":visible" ) ) {
                        $( "#" + tinyMCEMessageHtmlId + "_ifr" ).removeClass( "describr-invalid" );

                        $( this ).removeAttr( "aria-invalid aria-errormessage" );

                        tinymce.get( tinyMCEMessageHtmlId ).getBody().removeAttribute( "aria-invalid" );
                        tinymce.get( tinyMCEMessageHtmlId ).getBody().removeAttribute( "aria-errormessage" );
                        
                        $( "#" + messageTextareaHtmlId + "-error" ).hide();
                    }
                });
            
                tinymce.get( tinyMCEMessageHtmlId ).getBody().setAttribute( "autocomplete", messageAutocomplete ? "on" : "off" );
                tinymce.get( tinyMCEMessageHtmlId ).getBody().setAttribute( "spellcheck", messageSpellcheck ? "true" : "false" );
            
                tinymce.get( tinyMCEMessageHtmlId ).getContainer().setAttribute( "aria-labelledby", "describr-profile-message-label" );
                tinymce.get( tinyMCEMessageHtmlId ).getBody().setAttribute( "aria-labelledby", "describr-profile-message-label" );
                tinymce.get( tinyMCEMessageHtmlId ).getBody().setAttribute( "title", $( "#" + messageTextareaHtmlId ).attr( "title" ) );
            } else {
                $( "#wp-" + tinyMCEMessageHtmlId + "-wrap" ).hide();
                
                $( "#" + messageTextareaHtmlId ).show().on( "keydown", function() {
                    if ( $( "#" + messageTextareaHtmlId + "-error" ).is( ":visible" ) ) {
                        $( this ).removeAttr( "aria-invalid aria-errormessage" );
                        $( "#" + messageTextareaHtmlId + "-error" ).hide();
                    }
                });

                $( "[for=\"" + tinyMCEMessageHtmlId + "\"]" ).attr( "for", messageTextareaHtmlId );
            }
            
            sendMessageForm = $( "#describr-profile-message-modal-wrap" );
            
            //Submit message
            sendMessageForm.on( "submit", function( e ) {
                e.preventDefault();
                //checkKey
                var form_          = $( this ),
                    submitButton   = form_.find( "button[type=submit]" ),
                    check          = form_.find( "[name=\"" + checkKey + "\"]" ),
                    message        = ( isTinyMCEMessageEnabled ? tinymce.get( tinyMCEMessageHtmlId ).getContent() : $( "#" + messageTextareaHtmlId ).val() ).trim(),
                    messageFields  = {};
            
                if ( ! message ) {
                    messageFieldErr( describrL10n.message["required"] );
                    return false;
                }

                if ( ! check.length || isCheck( check.val() ) ) {
                    messageErrNotice( describrL10n.check );
                    return false;
                }

                messageFields[checkKey] = "";

                messageFields.message_nonce = nonces.sendMessage;

                messageFields.user_id = userId;

                messageFields.subject = $( "#describr-profile-message-subject" ).val().trim();

                messageFields.message = message;

                submitButton.text( describrL10n.message.sending ).prop( "disabled", true );

                globalScreenReaderAlertNotice( describrL10n.message.sending_ );

                describr.ajax.post( "describr-send-message", messageFields ).then( function ( response ) {
                    submitButton.text( describrL10n.message.send ).prop( "disabled", false );
                    closeModals();
                    centerAlert( response, true );
                    isTinyMCEMessageEnabled ? tinymce.get( tinyMCEMessageHtmlId ).setContent( "" ) : $( "#" + messageTextareaHtmlId ).val( "" );
                    $( "#describr-profile-message-subject" ).val( "" );
                },
                function ( response ) {
                    messageErrNotice( response );
                    submitButton.text( describrL10n.message.send ).prop( "disabled", false );   
                });
            });
        
            //Show the message modal when the "Send Message" button is clicked       
            $( "#describr-profile-send-message" ).on( click_, function ( e ) {
                if ( isClicked( e ) ) {
                    $( "#describr-profile-message-modal-wrapper" ).show();
                    
                    if ( sendMessageForm.find( "button[type=submit]" ).prop( "disabled" ) ) {
                        sendMessageForm.find( "button[type=submit]" ).text( describrL10n.message.send ).prop( "disabled", false );
                    }
                    
                }                
            });

            if ( "undefined" !== typeof describrTriggerMessageModal && describrTriggerMessageModal ) {
                document.getElementById( "describr-profile-send-message" ).click();
            }
        }   
    });

    describr.settings = describr.settings || {};
    
    describr.pagingTabs = describr.settings.pagingTabs || describr.pagingTabs;
    
    describr.pagingTabsRewriteBase = describr.settings.pagingTabsRewriteBase || describr.pagingTabsRewriteBase;
    
    describr.settings.fieldsToEdit = describr.settings.fieldsToEdit || {};

    describr.profile = describr.profile || {};

    describr.profile.fields = describr.profile.fields || {};
    
    checkKey = describr.settings._check;

    activeTabs = describr.settings.activeTabs || [];

    nonces = describr.settings.nonces;

    userId = describr.profile.ID;

    savedAsides = objHasOwn( describr.profile, "savedAsides" ) && isObject( describr.profile.savedAsides ) ? describr.profile.savedAsides : {};
 
    describr.settings.asides = objHasOwn( describr.settings, "asides" ) && isObject( describr.settings.asides ) ? describr.settings.asides : {};
    
    describr.settings.locales = objHasOwn( describr.settings, "locales" ) && isObject( describr.settings.locales ) ? describr.settings.locales : {};
    
    describr.pagingTabsRegExpInit();

    /**
     * Retrieves whether a field is legit
     * 
     * @param string field The field
     * @return bool
     */
    describr.settings.isField = function ( field ) {        
        return objHasOwn( describr.settings.fieldsToEdit, field );
    };    
    
    /**
     * Retrieves whether a field is a social network
     * 
     * @param string field The field
     * @return bool
     */
    describr.settings.isSocial = function ( field ) {
        return ( describr.settings.socials || [] ).includes( field );
    };
    
    /**
     * Retrieves whether a field is editable
     * 
     * @param string field The field
     * @return bool
     */
    describr.settings.isEditable = function ( field ) {            
        field = fieldSettings( field );
        
        return objHasOwn( field, "editable" ) && field.editable;
    };

    /**
     * Retrieves a field's label
     * 
     * @param string field The field
     * @return object
     */
    describr.settings.getLabel = function ( field ) {
        field = field || describr.profile.activeField || modalField;

        return fieldSettings( field )["label"] || "";
    };

    /**
     * Retrieves a field's title
     * 
     * @param string field The field
     * @return object
     */
    describr.settings.getTitle = function ( field ) {
        field = field || describr.profile.activeField || modalField;
    
        return fieldSettings( field )["title"] || "";
    };

    /**
     * Retrieves a field's icon
     * 
     * @param string field The field
     * @return bool|string
     */
    describr.settings.getIcon = function ( field ) {
        field = field || describr.profile.activeField || modalField;
    
        return fieldSettings( field )["icon"] || "";
    };

    /**
     * Retrieves a field's form name attribute value (should be the same as the field itself)
     * 
     * @param string field The field
     * @return bool|string
     */
    describr.settings.getName = function ( field ) {
        if ( ! describr.settings.isField( field ) ) {
            return false;
        }
        
        return fieldSettings( field )["name"];
    };
    
    /**
     * Retrieves a field's type: text, radio, select, textarea, date, array, tel
     * 
     * @param string field The field
     * @return bool|string
     */
    describr.settings.getType = function ( field ) {
        if ( ! describr.settings.isField( field ) ) {
            return false;
        }
        
        return fieldSettings( field )["type"] || false;
    };

    /**
     * Retrieves whether field has privacy aside
     * 
     * @param string field The field
     * @return bool
     */
    describr.settings.hasPrivacy = function ( field ) {
        return objHasOwn( describr.settings.getAsides( field ), "privacy" );
    };
    
    /**
     * Retrieves whether field has status aside
     * 
     * @param string field The field
     * @return bool
     */
    describr.settings.hasStatus = function ( field ) {
        return objHasOwn( describr.settings.getAsides( field ), "status" );
    };
    
    /**
     * Retrieves whether field has audit aside
     * 
     * @param string field The field
     * @return bool
     */
    describr.settings.hasAudit = function ( field ) {
        return objHasOwn( describr.settings.getAsides( field ), "audit" ) && describr.profile.hasAudit( field );
    };
    
    /**
     * Retrieves a field's asides
     * 
     * @param string field The field
     * @return object
     */
    describr.settings.getAsides = function ( field ) {
        return  objHasOwn( describr.settings.asides, field ) && isObject( describr.settings.asides[ field ] ) ? describr.settings.asides[ field ] : {};
    };
    
    /**
     * Retrieves a field's privacy aside
     * 
     * @param string field The field
     * @return object
     */
    describr.settings.getPrivacy = function ( field ) {
        return describr.settings.getAsides( field )["privacy"] || {};
    };

    /**
     * Retrieves a field's status aside
     * 
     * @param string field The field
     * @return object
     */
    describr.settings.getStatus = function ( field ) {
        return describr.settings.getAsides( field )["status"] || {};
    };
    
    /**
     * Retrieves a field's audit asides
     * 
     * @param string field The field
     * @return object
     */
    describr.settings.getAudit = function ( field ) {
        return describr.settings.getAsides( field )["audit"] || {};
    };
    
    /**
     * Retrieves language associated with its code
     * 
     * @param string code Language code
     * @return string
     */
    describr.settings.getLang = function ( code ) {
        return objHasOwn( describr.settings.locales, code ) ? describr.settings.locales[code] : "";
    };
    
    describr.settings.tel = {
        countryFlag : function ( countryCode ) {
            if ( ! isCountry( countryCode ) ) {
                return "";
            }

            return this.hasFlag( countryCode ) ? "fflag ff-md fflag-" + countryCode : "describr-sprite flag-24";
        },
        displayFlag : function ( countryCode ) {
            if ( ! isCountry( countryCode ) ) {
                return "";
            }

            return "<span aria-hidden=\"true\" class=\"" + this.countryFlag( countryCode ) + "\"></span>";
        }, 
        renderCountryBtn : function ( countryCode ) {
            return ( this.displayFlag( countryCode ) + " " + describr.settings.choice.country[countryCode] ).trim();
        },
        hasFlag : function( countryCode ) {
            return !["AC","SJ","MF","IO","TA","AQ","BV","TF","HM","PN","UM","GS"].includes( countryCode );
        }
    };

    describr.settings.fieldsToValidate = ["kakaotalk", "line","discord"];

    describr.profile.changes = {
        removerClass : "describr-profile-field-editor-remover",
        showEditModal : function ( field, hash ) {
            this.activeField      = field;
            this.fieldHash        = hash;
            this.isSocial         = describr.settings.isSocial( field );
            this.isTinyMCEShowing = false;
            this.savedAsides      = {};
            this.settings         = {};
            this.asideCount       = 0;
            this.asides           = [];
            this.fields           = [];      

            describr.profile.activeField = field;

            $( document ).find( "." + this.removerClass ).each( function() {
                $( this ).remove();
            });

            var fieldData = this.getFieldModalData(), that = this;
            
            fieldData.forEach( function ( data ) {
                addFieldToModal( data );
            });
            
            fieldData.length && ["privacy","status"].forEach( function( aside ) {
                that.addAsideToModal( aside );
            });
            
            $( "#describr-profile-field-editor-modal-title" ).html( sprintf( "adding" === describr.profile.mode ? describrL10n.fieldEditor.add : describrL10n.fieldEditor.update, "lived_cities" === field ? describrL10n.city : fieldSetting( field, "title" ) ) );
            
            addEditorDialogLabel();

            updateSubmitButton();
            
            fieldEditorModalWrapper.show();
        },
        getFieldModalData : function () {
            var field     = this.activeField,
                settings  = fieldSettings( field ),
                structure = [],
                i         = 0;

            this.support = false;
            
            if ( objHasOwn( settings, "supports_" ) ) {
                var that = this, 
                supports = fieldSupport( false, field );

                settings.supports_.forEach( function ( item ) {
                    that.support = item;
                    structure.push( addFieldToModal_( supports[item] ) );
                });
            } else {
                structure.push( addFieldToModal_( settings ) );
            }

            this.activeField = field;

            return structure;
        },
        /**
         * Helper function for `addFieldToModal()` that prepares field's data
         * 
         * @param {object} fieldSettings_ Field settings
         * @return object
         */
        addFieldToModal_ : function ( fieldSettings_ ) {
            var support   = this.support,
                field     = this.activeField,
                id        = "describr-profile-field-editor-" + field,
                savedVal  = profileFieldSavedValue( field ),
                hasHash   = fieldSettings( field ).has_hash,
                choice    = false, 
                structure = {
                    type         : fieldSettings_.type,
                    name_        : fieldSettings_["name"],
                    isHTML       : fieldSettings_.html,
                    required     : fieldSettings_.required,
                    autocomplete : fieldSettings_.autocomplete,
                    spellcheck   : fieldSettings_.spellcheck,
                    hasHash      : hasHash,
                    minLength    : objHasOwn( fieldSettings_, "min_chars" ) ? parseInt( fieldSettings_.min_chars, 10 ) : false,
                    maxLength    : objHasOwn( fieldSettings_, "max_chars" ) ? parseInt( fieldSettings_.max_chars, 10 ) : false,
                    length_      : objHasOwn( fieldSettings_, "length" ) ? parseInt( fieldSettings_.length, 10 ) : false,
                    icon         : fieldSettings_.icon,
                    title        : fieldSettings_.title || "",
                    label        : fieldSettings_.label || fieldSettings_.title || false,
                    desc         : fieldSettings_.description,
                    options      : fieldSettings_.options,
                    baseUrl      : fieldSettings_.base_url,
                    isCustom     : fieldSettings_.isCustom
                };

            if ( support ) {
                support = structure.name_;
                
                id += "-" + support;
            }
            
            if ( structure.isCustom ) {
                id += "-custom";
            }
            
            structure.id = id;
            /*{
                hash1 : {},
                hash2 : {},
                hashn...
            }*/
            if ( hasHash && this.fieldHash && isObject( savedVal ) && objHasOwn( savedVal, this.fieldHash ) ) {
                savedVal = savedVal[this.fieldHash];
                /*{
                    hash1 : {
                        support1 : value1
                        support2 : value2
                        supportn...
                    }
                }*/
                if ( support && isObject( savedVal ) && objHasOwn( savedVal, support ) ) {
                    savedVal = savedVal[support];
                }
            }
            
            if ( ! this.fieldHash && support && isObject( savedVal ) && objHasOwn( savedVal, support ) ) {
                savedVal = savedVal[support];
            }

            if ( ! isScalar( savedVal ) || empty( savedVal ) ) {
                savedVal = "";
            }
            
            structure.savedVal = trim_( savedVal );
            
            if ( objHasOwn( describr.profile.choices, field ) ) {
                structure.choices = describr.profile.choices[field];
                choice = field;
            }

            if ( support && objHasOwn( describr.profile.choices, support ) ) {
                structure.choices = describr.profile.choices[support];
                choice = support;
            }
            
            if ( "timezone" === field ) {
                structure.savedVal = selectableUTC( structure.savedVal );                
                structure.choices = describr.settings.timezones;
                choice = field;
            }

            if ( choice ) {
                structure.defVal = describrL10n.defaultVal[choice];
            }            
            
            if ( structure.isCustom ) {
                structure.savedVal = "";
            } else {
                this.settings[structure.name_] = structure;
                this.fields.push(structure.name_);
            }
            
            return structure;
        },
        /**
         * Adds field's markup to the modal
         * 
         * @param {object} settings Field settings
         * @param {bool}   _return  Whether to return the markup
         * @return string
         */
        addFieldToModal : function ( data, return_ ) {
            var type         = data.type, 
                name_        = data.name_, 
                isHTML       = data.isHTML, 
                isRequired   = data.required,
                autocomplete = data.autocomplete,
                spellcheck   = data.spellcheck,
                minLength    = data.minLength, 
                maxLength    = data.maxLength, 
                length_      = data.length_,
                icon         = data.icon, 
                title        = data.title, 
                label        = data.label, 
                desc         = data.desc, 
                options      = data.options, 
                id           = data.id, 
                choices      = data.choices,
                val          = data.savedVal, 
                defVal       = data.defVal || "", 
                baseUrl      = data.baseUrl; 
                
            var elemError     = "",
                elemDesc      = "",
                elemIcon      = "",
                elemRequired  = "",
                elemUrls      = "",
                elemLabel     = "",
                elemAltLabel  = "",
                elemLabelId   = "",
                elemAux       = "",
                elemAll       = "";

            var attrStyle        = "",
                attrAriaDesc     = "",
                attrRequired     = "",
                attrMaxLength    = "",
                attrMinLength    = "",
                attrAriaLabel    = "",
                attrId           = "",
                attrAria         = "",
                attrTitle        = "",
                attrName         = " name=\"" + name_ + "\"",
                attrValue        = " value=\"%s\"",
                attrAutocomplete = " autocomplete=\"" + ( autocomplete ? autocomplete : "off" ) + "\"",
                attrSpellcheck   = " spellcheck=\"" + ( spellcheck ? "true" : "false" ) + "\"",
                attrData         = " ";

            var tagOpen  = "", 
                tagClose = "", 
                tagType  = "";

            var role = "";    

            var maxWidth = "";

            var tinyMCEAttrs    = {},
                tinyMCELabels   = {},
                describrMCEWrap = $( "#wp-describr-profile-mce-wrap" ),
                isTinyMCE       = false,
                tinyMCEEditor;

            var isCustom = data.isCustom;

            var fieldClass = "describr-profile-field-editors";

            var descIds = [];

            var rowClass_ = [rowClass.trim(),this.removerClass].join( " " );
            
            var field = this.activeField;

            var $for;

            var helperType = false;

            var i = 0; 
            var curUrl = "";
            var sep = "";
            var iconClass = "describr-profile-field-editor-url-icon";
            var _attrs;
            var locale;
            var radioId;
            var baseUrl_;
            var urlParts;

            if ( "to" === name_ && objHasOwn( this.settings, "present" ) ) {
                if ( return_ ) {
                    if ( $( "#" + this.settings["present"].id )[0].checked ) {
                        return;
                    }
                } else if ( ! empty( this.settings["present"].savedVal ) ) {
                    return;
                }
            }
            
            if ( isCustom ) {
                if ( label ) {
                    label = describrL10n.custom + " " + label;
                }

                if ( title ) {
                    title = describrL10n.custom + " " + title;
                }
            }

            if ( "textarea" === type && isHTML && isTinyMCEEnabled ) {
                _attrs = ["spellcheck","aria-invalid", "aria-errormessage", "aria-describedby","minlength","maxlength","title"];
                
                this.settings[name_].isTinyMCE = true;
                
                isTinyMCE = true;
                                
                id = tinyMCEHtmlId;
                
                tinyMCEEditor = tinymce.get( id );

                this.settings[name_].id = id;

                tinyMCEAttrs.spellcheck = spellcheck ? "true" : "false";
                
                if ( title ) {
                    tinyMCEAttrs.title = title;
                }

                if ( ! isTinyMCEAutocompleteOff ) {
                    _attrs.push( "autocomplete" );
                }

                $( "#" + id + "_ifr" ).removeClass( "describr-invalid" );
                
                _attrs.forEach( function( item ) {
                    describrMCETextarea.removeAttr( item );
                    tinyMCEEditor.getBody().removeAttribute( item );
                });

                tinyMCEEditor.getBody().removeAttribute( "aria-labelledby" );
                tinyMCEEditor.getContainer().removeAttribute( "aria-labelledby" );
                
                if ( _attrs.includes( "autocomplete" ) ) {
                    tinyMCEAttrs.autocomplete = autocomplete ? autocomplete : "off";
                    tinyMCEEditor.getBody().removeAttribute( "autocomplete", tinyMCEAttrs.autocomplete );
                }

                if ( title ) {
                    tinyMCEEditor.getBody().setAttribute( "title", title );
                }
            } else if ( isTinyMCEEnabled && ! this.isTinyMCEShowing ) {
                describrMCEWrap.hide();
            }
            
            if ( title ) {
                attrTitle = " title=\"" + title + "\"";
            }
            
            elemLabelId = id + "-label";

            $for = " data-for=\"" + id + "\" for=\"" + id + "\" id=\"" + elemLabelId + "\"";

            if ( ["city","partner","display_name","locale","locales"].includes( name_ ) ) {
                attrData += " data-value=\"" + val + "\" data-editaction=\"" + name_ + "\"";

                if ( "display_name" !== name_ ) {
                    helperType = "autocomplete";
                }
            } else if ( options ) {
                attrData += " data-value=\"" + val + "\" data-editaction=\"" + name_ + "\"";
            }

            if ( desc ) {
                descIds.push( id + "-desc" );

                elemDesc += "<div id=\""+ id + "-desc\" class=\"describr-description " + rowClass_ + "\">" + desc + "</div>";
            }
            
            if ( isRequired ) {
                label += requiredIcon();
                
                if ( ! isTinyMCE ) {
                    attrRequired = " aria-required=\"true\" required";
                }
            }

            
            if ( minLength ) {
                if ( isTinyMCE ) {
                    tinyMCEEditor.getBody().setAttribute( "minlength", minLength );
                    tinyMCEAttrs["minlength"] = minLength;
                } else {
                    attrMinLength = " minlength=\"" + minLength + "\"";
                }
            }

            if ( maxLength ) {
                if ( isTinyMCE ) {
                    tinyMCEEditor.getBody().setAttribute( "maxlength", maxLength );
                    tinyMCEAttrs["maxlength"] = maxLength;
                } else {
                    attrMaxLength = " maxlength=\"" + maxLength + "\"";
                }
            }

            if ( length_ ) {
                if ( isTinyMCE ) {
                    tinyMCEEditor.getBody().setAttribute( "minlength", length_ );
                    tinyMCEEditor.getBody().setAttribute( "maxlength", length_ );
                    tinyMCEAttrs["minlength"] = length_;
                    tinyMCEAttrs["maxlength"] = length_;
                } else {
                    attrMinLength = " minlength=\"" + length_ + "\"";
                    attrMaxLength = " maxlength=\"" + length_ + "\"";
                }
            }

            if ( baseUrl ) {
                tagOpen  = "<input";
                tagClose = " />";
                tagType  = "text";
                baseUrl_ = baseUrl;//Base url of the current url
                
                if ( "url" === type ) {
                    if ( Array.isArray( baseUrl ) ) {
                        baseUrl_ = baseUrl[0];
                    }
                    
                    if ( ! empty( val ) ) {
                        urlParts = removeBaseUrl( baseUrl, val );
                        baseUrl_ = urlParts.baseUrl;
                        val = urlParts.path;
                    }
                                
                    if ( Array.isArray( baseUrl ) ) {
                        baseUrl.forEach( function( url ) {
                            radioId = "describr-profile-field-editor-baseurl-choice-" + (++i);
                            elemUrls += "<div class=\"" + rowClass_ + "\">";
                            elemUrls += "<label><input type=\"radio\" name=\"baseurl\" value=\"";
                            elemUrls += url;
                            elemUrls += "\" id=\"";
                            elemUrls += radioId;
                            elemUrls += "\"";

                            if ( empty( curUrl ) && ( url === baseUrl_ || ( empty( val ) && 1 === i ) ) ) {
                                curUrl = url;
                                elemUrls += " checked=\"checked\""
                            };

                            elemUrls += " class=\"describr-profile-field-editor-baseurl\" data-for=\"" + id + "\" /> ";
                            elemUrls += url;
                            elemUrls += "</label></div>";
                        });

                        baseUrl_ = curUrl;
                        attrData += " data-baseurl=\"" + baseUrl_ + "\"";
                    }
                } else if ( ! empty( val ) ) {
                    val = val.replace( new RegExp( "^(" + escRegex( baseUrl_ ) + ")", "i" ), "" );
                }
                
                descIds.push( id + "-baseurl-desc" );

                elemIcon += "<span class=\"describr-a11y-text\" id=\"" + id + "-baseurl-desc\">" + baseUrl_ + "</span>";

                //Change the label to "facebook handle", for example
                if ( this.isSocial && /^https?:/.test( baseUrl_  ) ) {
                    $for = "";
                    elemIcon += "<label class=\"describr-a11y-text\" for=\"" + id + "\">" + sprintf( describrL10n.socialLabel, label ) + "</label>";
                }

                if ( /@$/.test( baseUrl_ ) || "x" === field ) {
                    sep = "@"
                } else if ( /^https?:/.test( baseUrl_ ) ) {
                    sep = "/";
                } else {
                    sep = ":";
                    baseUrl_ = baseUrl_.replace( /:$/, "" );
                }
                
                if ( "url" === type ) {
                    if ( icon ) {
                        elemIcon += "<span title=\"" + title + "\" class=\"" + icon + " " + iconClass + "\" aria-hidden=\"true\"></span>";
                    } else { 
                        elemIcon += "<span class=\"" + iconClass + "\" aria-hidden=\"true\">" + baseUrl_ + "</span>";
                    }
                }
                
                if ( sep ) {
                    if ( ":" == sep ) {
                        sep = "<span class=\"" + iconClass + "\">" + sep + "</span> ";//Note the space at the end
                    } else {
                        sep = "<span class=\"" + iconClass + "\">" + sep + "</span>";
                    }
                }
                
                if ( "url" !== type ) {
                    sep = "";
                }

                elemIcon += sep;
            } else if ( "textarea" === type && ! isTinyMCE ) {
                tagOpen  = "<textarea rows=\"5\" cols=\"40\"";
                attrValue = ">%s</textarea>";
                tagClose = "";
            } else if ( ["url","email","text","date","number","tel"].includes( type ) ) {
                if ( "date" === type ) {
                    type = "text";
                    maxWidth = "100";
                }

                tagOpen  = "<input";
                tagClose = " />";
                tagType  = type;
            } else if ( choices || "locale" === name_ ) {
                if ( "locale" === name_ ) {
                    choices = "";
                    defVal = describrL10n.defaultVal.locale;

                    if ( isObject( options ) ) {
                        for ( locale in options ) {
                            choices += "<option value=\"" + locale + "\">" + options[locale] + "</option>";
                        }
                    }
                }

                tagOpen  = "<select";
                attrValue = ">%s</select>";
                tagClose = "";
                val = this.normalizeChoices( choices, val, defVal );
                helperType = "menu";
            } else if ( "checkbox" === type ) {
                tagOpen  = "<input";

                if ( val.length ) {
                    tagOpen += " checked=\"checked\"";
                } else {
                    val = 1;
                }

                tagClose = " />";
                tagType  = type;
                
                elemAltLabel = " <label" + $for + ">" + label + "</label>";
            }
            
            if ( ! isCustom && "display_name" === name_ ) {
                tagOpen  = "<select";
                attrValue = ">%s</select>";
                tagClose = "";
                
                val = this.normalizeChoices( describr.profile.displayNameChoice(), val );
                helperType = "menu";
            }

            if ( 0 < descIds.length ) {
                descIds = descIds.join( " " );

                if ( isTinyMCE ) {
                    tinyMCEEditor.getBody().setAttribute( "aria-describedby", descIds );
                    tinyMCEEditor.getContainer().setAttribute( "aria-describedby", descIds );
                    tinyMCEAttrs["aria-describedby"] = descIds;
                } else {
                    attrAriaDesc = " aria-describedby=\"" + descIds + "\"";
                }
            }

            if ( empty( elemAltLabel ) ) {
                elemLabel ="<div class=\"" + rowClass_ + "\"><label" + $for + ">" + label + "</label></div>";
            }
            
            if ( ! $for ) {
                elemLabel = "";
            }
            
            if ( isTinyMCE && $for && elemLabel ) {
                tinyMCEEditor.getContainer().setAttribute( "aria-labelledby", elemLabelId );
                tinyMCEEditor.getBody().setAttribute( "aria-labelledby", elemLabelId );
            }

            if ( ["city","current_city","hometown"].includes( name_ ) ) {
                helperType = "autocomplete";
                role = " role=\"combobox\"";
                attrAria += " aria-autocomplete=\"list\"";
            }
            
            if ( "partner" === name_ ) {
                role = " role=\"combobox\"";
                attrAria += " aria-autocomplete=\"both\"";
            }
            
            if ( "locales" === name_ && ! empty( val ) ) {
                elemIcon = this.langs.grid( val );
            }
            
            if ( /combobox/.test( role ) ) {
                if ( useJqueryUi ) {
                    attrAria += " aria-expanded=\"false\"";
                } else {
                    role = "";
                }
            }

            if ( ! role ) {
                attrAria = "";
            }

            if ( "locales" === name_ ) {
                maxWidth = "80";

                if ( useJqueryUi ) {
                    attrAria += " aria-haspopup=\"listbox\"";
                }
            }

            if ( ! empty( maxWidth ) ) {
                maxWidth = "max-width:"+ maxWidth + "px;";
            }
            
            if ( ! empty( elemUrls ) ) {
                elemUrls = "<div style=\"margin:0;padding:0;\" id=\"" + id + "_choice_\"><span class=\"describr-a11y-text\">" + describrL10n.baseUrl + "</span>" + elemUrls + "</div>";
            }
            
            elemError = "<div role=\"none\" id=\""+ id +"-error\" class=\"describr-hidden describr-error " + rowClass_ + "\"></div>";
            
            if ( tagType ) {
                tagType = " type=\"" + tagType + "\"";
            }
            
            if ( ! isTinyMCE ) {
                attrId = " id=\"" + id + "\"";
            }

            if ( "hidden" === type ) {
                fieldClass = "";
                role = "";
                attrAria = "";
                maxWidth = "";
                attrMinLength = "";
                attrMaxLength = "";
                elemLabel = "";
                elemUrls = "";
                elemError = "";
                elemDesc = "";
                elemAltLabel = "";
                attrAriaLabel = "";
                elemIcon = "";
                tagOpen  = "<input";
                tagClose = " />";
                tagType  = " type=\"" + type + "\"";
                attrId += " class=\"" + this.removerClass + "\"";
            } else {
                attrId += " class=\"" + fieldClass + "\"";
            }
            
            if ( "menu" === helperType ) {
                if ( useJqueryUi ) {
                    attrRequired = "";
                }

                attrMinLength = "";
                attrMaxLength = "";
            }

            elemAux = elemLabel + elemUrls + elemDesc + elemError;
            
            attrStyle = maxWidth;
            
            if ( "locales" === name_ ) {
                attrStyle += "border:none;display:inline-block;box-shadow:none;";
                attrValue = "";
            }

            if ( "textarea" === type && /textarea/.test( val ) ) {
                val = val.replace( "</textarea", "&lt;/textarea" )
            }
            
            if ( ! empty( attrStyle ) ) {
                attrStyle = " style=\"" + attrStyle + "\"";
            }

            if ( isTinyMCE ) {
                tinyMCEEditor.setContent( val );
            } else {
                attrValue = attrValue.replace( "%s", val );
            }
            
            if ( ! isTinyMCE ) {
                elemAll += elemAux + ( "hidden" !== type ? ( "<div class=\"" + rowClass_ + ( "locales" === name_ ? " describr-focusable-wrap\" style=\"display:inline-block;" : "" ) + "\">" ) : "" ) + elemIcon + tagOpen + tagType + role + attrAria + attrName + attrId + attrAutocomplete + attrSpellcheck + attrMinLength + attrMaxLength + attrData + attrAriaLabel + attrAriaDesc + attrAriaDesc + attrRequired + attrStyle + attrTitle + attrValue + tagClose + elemAltLabel + ( "hidden" !== type ? "</div>" : "" );
            }
            
            if ( return_ ) {
                return elemAll;
            } else if ( isTinyMCE ) {
                if (  ! this.isTinyMCEShowing ) {
                    this.isTinyMCEShowing = true;
                };
                
                describrMCETextarea.attr( tinyMCEAttrs ).addClass( fieldClass );

                describrMCEWrap.show();

                if ( ! empty( elemAux ) ) {
                    describrMCEWrap.before( elemAux );
                }
            } else if ( this.isTinyMCEShowing ) {
                fieldEditorsWrap.append( elemAll );
            } else {
                describrMCEWrap.before( elemAll );
            }
            
            if ( helperType ) {
                addHelperToDom( helperType, name_ );
            }
        },
        addHelperToDom : function ( helper_, name_ ) {
            if ( ! useJqueryUi ) {
                return;
            }

            this.settings[name_].hasJqueryHelper = helper_;

            if ( "menu" === helper_ ) {
                initJquerySelectmenu( this.settings[name_].id );
            } else {
               initJqueryAutocomplete( this.settings[name_].id );
            }
        },
        /**
         * Removes a field editor (for example, an input element and all associated elements)
         * 
         * @param {int} id ID of the editor
         */
        removeFieldEditor : function( id ) {
            var id_         = "#" + id, 
                remove_     = [id_, id_ + "-error", id_ + "_choice_", id_ + "-desc", "[for=\""+ id + "\"]"], 
                removerClss = "." + this.removerClass;

            if ( useJqueryUi ) {
                remove_.push( "[data-for=\"" + id + "\"]" );
            }
            
            remove_.forEach( function( _id ) {
                $( _id ).closest( removerClss ).remove();

                if ( $( _id ).length ) {
                    $( _id ).remove()
                }
            });
        },
        langs : {
            gridClass: "describr-langs-grid",
            grid : function ( langs ) {
                return langs ? ( "<span role=\"grid\" aria-label=\"" + describrL10n.langs + "\" class=\"" + this.gridClass + "\">" + this.row( langs ) + "</span>" ) : "";
            },
            row : function ( langs ) {
                langs = langs ? langs.split( "," ) : [];
                
                var row  = "", 
                    i    = 0, 
                    clss = this.gridClass + "-row";

                this.gridrowClass = clss;

                while ( i < langs.length ) {               
                    row += this.gridcell( langs[i++] );
                }
            
                if ( row ) {
                    row = "<span role=\"row\" class=\"" + clss + "\">" + row + "</span>";
                }

                return row;
            },
            gridcell : function ( langCode ) {
                var role = "gridcell",
                    clss = this.gridClass + "-row-" + role,
                    lang = describr.settings.getLang( langCode );

                lang && ( this.gridcellClass = clss );
                
                return lang ? ( "<span class=\""+ clss + "\" tabindex=\"-1\" role=\"" + role + "\"><span class=\"" + clss + "-lang\" tabindex=\"0\">" + lang + "</span><button type=\"button\" data-action=\"removelang\" data-lang=\"" + langCode + "\" data-field=\"locales\" class=\"" + clss + "-remove\" aria-label=\"" + describrL10n.remove + " " + lang + "\"><span class=\"dashicons-before dashicons-no-alt\"></span></button></span>" ) : "";
            },
            /**
             * Gets the number of rows and column, as well as sets the data-row and data-col attributes on each cell.
             * 
             * Gets called when a new language is selected, a language is deleted, and the language section is instantiated. 
             * 
             * Credit: https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Roles/grid_role
             */
            jsUpdate : function () {
                var lang        = this,
                    cellClass   = lang.gridcellClass || false,
                    clss        = "." + cellClass,
                    selectables = cellClass ? $( clss ) : "",//Gets all grid cells.
                    gridrows    = cellClass ? $( "." + lang.gridrowClass ) : "",//Get all the grid's rows.
                    row         = 0,
                    col         = 0;
            
                if ( ! selectables.length ) {
                    return;
                }
            
                //All the cells" tabindices are set to -1, which makes them not focusable by default, hence we set the first cell's tabindex to 0 to make the cell focusable.
                selectables.eq( 0 ).attr( "tabindex", "0" );
            
                lang.maxrow = gridrows.length -1;
                lang.maxcol = 0;

                //Iterate of over rows
                gridrows.length && gridrows.each( function () {
                    //Gets the cells of the current row, iterates over them, and adds the relevant row and columns
                    $( this ).find( clss ).each( function() {
                        $( this ).attr({ 
                            "data-row" : row, 
                            "data-col" : col 
                        });
                        
                        col++;     
                    });

                    //Updates the maximum number of columns.
                    if ( col > lang.maxcol ) {
                        lang.maxcol = col - 1;
                    }

                    col = 0;//Resets the column so that cells that line up with each other vertically share the same column (data-col="colx").
                    row++ //Ensures that cells that line up with each other horizontally share the same row (data-row="rowx").
                });
            },//jsUpdate
            /**
             * Focuses on the cell to which the user has chosen to relocate.
             * 
             * @param int newrow The row's number of the new cell.
             * @param int newcol The column's number of the new cell.
             * @return bool True if the new cell was located, otherwise False.
             */
            jsMoveto : function ( newrow, newcol ) {
                var gridcell = "." + this.gridcellClass,//The class of the cells
                    newcell  = $( gridcell + "[data-row=\"" + newrow + "\"][data-col=\"" + newcol + "\"]" );//The new cell
                
                //Is the element actually a gridcell?
                if ( "gridcell" === newcell.attr( "role" ) ) {
                    //Makes all cells unfocusable.
                    $( gridcell ).each( function() {
                        $( this ).attr( "tabindex", "-1" );
                    });
                
                    newcell.attr( "tabindex", "0" );//Tabindex=0 so the cell can be focused();
                    newcell[0].focus();//Sets focus on the element to which the user wants to locate.

                    return true;//Returns true as the element was found.
                } else {
                    return false;//Returns false as the element was not found.
                }
            },//jsMoveTo
            /**
             * Processes the keydown event according to ARIA accessability specifications on the grid element.
             * 
             * @param object event The API for the keydown event.
             */
            jsEvent : function( event ) {
                var gridcell = $( event.target ).closest( "[role=gridcell]" ),
                    u        = this, 
                    col      = parseInt( gridcell.data( "col" ), 10 ),//The column the user wants to move from 
                    row      = parseInt( gridcell.data( "row" ), 10 ), //The row the user wants to move from 
                    maxrow   = u.maxrow, 
                    maxcol   = u.maxcol,
                    newrow   = 0;
                
                switch ( describr.key( event ) ) {
                    case "ArrowRight": 
                        var newcol = col === maxcol ? 0 : col + 1;
                        u.jsMoveto(newrow, newcol);
                        break;
                    case "ArrowLeft": 
                        var newcol = col === 0 ? maxcol : col - 1;
                        u.jsMoveto(newrow, newcol);
                        break;
                    case "ArrowDown":
                        u.jsMoveto(row + 1, col);
                        break;
                    case "ArrowUp":
                        u.jsMoveto(row - 1, col);
                        break;
                    case "Home": 
                        if (event.ctrlKey) {
                            var i = 0,
                                result;
                            do {
                                var j = 0;
                                do {
                                    result = u.jsMoveto(i, j);
                                    j++;
                                } while (!result);
                                i++;
                            } while (!result);
                        } else {
                            u.jsMoveto(row, 0);
                        }       
                        break;
                    case "End": 
                        if (event.ctrlKey) {
                            var i = maxrow,
                                result;
                            do {
                                var j = maxcol;
                                do {
                                    result = u.jsMoveto(i, j);
                                    j--;
                                }     while (!result);
                            i--;
                            }     while (!result);
                        } else {
                            u.jsMoveto(
                                row,
                                $( "." + u.gridcellClass +"[data-row=\"" + gridcell.data( "row" ) + "\"]" ).last().data( "col" )
                            );
                        }
                        break;
                    case "PageUp":
                        var i = 0,
                            result;
                        do {
                            result = u.jsMoveto(i, col);
                            i++;
                        } while (!result);
                        break;
                    case "PageDown": 
                        var i = maxrow,
                            result;
                        do {
                            result = u.jsMoveto(i, col);
                            i--;
                        } while (!result);
                        break;
                    default:
                        break;
                }
                event.preventDefault();//Cancel the default action to avoid it being handled twice.
            }
        },
        addAsideToModal : function ( aside ) {
            var field        = this.activeField, 
                removerClass = this.removerClass,
                id           = "describr-profile-field-editor-aside-" + field + "-" + aside,
                _name        = "",
                html         = "",
                aside_       = altAside( aside ), 
                settings,
                choices,
                savedAside,
                required;

            if ( ! isAsideEditable( field, aside ) || ! objHasOwn( describr.settings, "has"+aside_ ) || ! describr.settings["has"+aside_]( field ) ) {
                return;
            }
            
            savedAside = describr.profile["getSaved"+aside_]( field );

            settings = $.extend( true, {}, describr.settings["get"+aside_]( field ) );

            settings.id = id;

            _name = "_" + settings["name"];
            
            this.asides.push( _name );
            this.fields.push( _name );

            this.savedAsides[_name] = savedAside;
            
            savedAside = savedAside || getDefaultSavedAside( aside );
            
            this.settings[_name] = settings;

            required = !useJqueryUi && settings.required ? " aria-required=\"true\" required" : "";
            
            if ( ! this.asideCount ) {
                html += "<br class=\"" + removerClass + "\" />";
            }

            html += "<div role=\"none\" id=\""+ id +"-error\" class=\"describr-hidden describr-error " + removerClass + rowClass + "\"></div>";
            html += "<div class=\"" + removerClass + rowClass + "\"><select spellcheck=\"" + ( settings.spellcheck ? "true" : "false" ) + "\"" + required + " autocomplete=\"" + settings.autocomplete + "\" data-value=\"" + savedAside + "\" data-editaction=\"" + _name + "\" name=\"" + _name + "\" title=\"" + settings["title"] + "\" aria-label=\"" + sprintf( describrL10n.listBoxLabel[_name], maybeCityLabel( field ) ) + "\" id=\"" + id + "\">";
            html += this.normalizeChoices( describr.profile.choices["_"+aside], savedAside );
            html += "</select></div>";
            
            fieldEditorsWrap.append( html );
            this.asideCount++;
            this.addHelperToDom( "menu", _name );
        },
        normalizeChoices: function ( choices, selected, defVal ) {
            choices = Array.isArray( choices ) ? choices.join( "\n" ) : choices;
            
            if ( selected && -1 < choices.indexOf( "value=\"" + selected + "\"" ) ) {
                choices = choices.replace( "value=\"" + selected + "\"", "value=\"" + selected + "\" selected=\"selected\"" );
            } else if ( defVal ) {
                choices = "<option value=\"\">" + defVal + "</option>" + choices;
            }

            return choices;
        },
        toggleFieldError : function ( settings, isErr ) {
            if ( isString( settings ) ) {
                settings = settings.toLowerCase();
                
                if ( ! objHasOwn( this, "settings" ) || ! objHasOwn( this.settings, settings ) ) {
                    return;
                }

                settings = this.settings[settings];
            }
            
            var id        = settings.id + ( settings.isCustom ? "-custom" : "" ),//Input element ID
                //ID of the element on which aria-invalid and aria-errormessage will be added. Different from original element when jquery is used to display select elements" options
                id_       = id + ( "menu" === settings.hasJqueryHelper && ! settings.isCustom ? "-button" : "" ),
                elem      = $( "#" + id_ ),
                elemErrId = id + "-error";
            
            if ( isErr ) {
                elem.attr( { "aria-invalid" : "true", "aria-errormessage" : elemErrId } );
                
                if ( id === tinyMCEHtmlId ) {
                    //Add the attributes on the editable element in the iframe
                    tinymce.get( id ).getBody().setAttribute( "aria-invalid", "true" );
                    tinymce.get( id ).getBody().setAttribute( "aria-errormessage", elemErrId );
                    $( "#" + id + "_ifr" ).addClass( "describr-invalid" );
                }
            } else {
                elem.removeAttr( "aria-errormessage aria-invalid" );
                
                if ( $( "#" + elemErrId ).is( ":visible" ) ) {
                    $( "#" + elemErrId ).hide();
                    if ( id === tinyMCEHtmlId ) {
                        //Remove the attributes from the editable element in the iframe
                        tinymce.get( id ).getBody().removeAttribute( "aria-invalid" );
                        tinymce.get( id ).getBody().removeAttribute( "aria-errormessage" );
                        $( "#" + id + "_ifr" ).removeClass( "describr-invalid" );
                    }
                }
            }
                         
            //Test if the input element is for locales. Error indicator is added on input's parent because the input element is not stationary and doesn't have a border           
            if ( "locales" === settings.name_ ) {
                isErr ? elem.parent().addClass( "describr-invalid" ) : elem.parent().removeClass( "describr-invalid" );
            }
        }
    };

    /**
     * Retrieves whether a field has saved audits
     * 
     * @return bool
     */
    describr.profile.hasAudit = function ( field ) {
        return ( 0 < describr.profile.getSavedAudit( field ).length );
    };

    /**
     * Retrieves saved field status
     * 
     * @return bool
     */
    describr.profile.getSavedStatus = function ( field ) {
        return getFieldSavedAside( "status", field );
    };
    
    /**
     * Retrieves saved field privacy status
     * 
     * @return bool
     */
    describr.profile.getSavedPrivacy = function ( field ) {
        return getFieldSavedAside( "privacy", field );
    };    
    
    /**
     * Retrieves saved field audits
     * 
     * @return array
     */
    describr.profile.getSavedAudit = function( field ) {
        return getFieldSavedAside( "audit", field );
    };
    
    describr.profile.getVal = function( field ) {
        return objHasOwn( describr.profile.fields, field ) ? describr.profile.fields[field] : "";
    };

    describr.profile.options = {
        audit  : function() {
            return "<div role=\"menuitem\" tabindex=\"0\" data-state=\"audit\" class=\"describr-popup-menu-item\"><div aria-hidden=\"true\" class=\"describr-popup-menu-item-icon-wrap\"><span class=\"dashicons dashicons-dashboard describr-popup-menu-item-icon\"></span></div><div class=\"describr-popup-menu-item-text\">" + describrL10n.viewAdmin + "</div></div>";
        },
        edit   : function() {
            return "<div role=\"menuitem\" tabindex=\"0\" data-action=\"edit\" class=\"describr-popup-menu-item\"><div aria-hidden=\"true\" class=\"describr-popup-menu-item-icon-wrap\"><span class=\"dashicons dashicons-edit describr-popup-menu-item-icon\"></span></div><div class=\"describr-popup-menu-item-text\">" + describrL10n.edit + "</div></div>";
        },
        delete : function() {
            return "<div role=\"menuitem\" tabindex=\"0\" data-action=\"delete\" class=\"describr-popup-menu-item\"><div aria-hidden=\"true\" class=\"describr-popup-menu-item-icon-wrap\"><span class=\"dashicons dashicons-trash describr-popup-menu-item-icon\"></span></div><div class=\"describr-popup-menu-item-text\">" + describrL10n.delete + "</div></div>";
        }
    };

    function getFieldSavedAside( aside, field ) {
        var asides = objHasOwn( savedAsides, field ) ? savedAsides[field] : {}, 
            val    = objHasOwn( asides, aside ) ? asides[aside] : false;
        
        if ( "audit" === aside && false === val ) {
            val = [];
        }

        return val;
    }

    function getDefaultSavedAside( aside ) {
        var val;
        
        switch(aside) {
            case "status":
                val = "yes";
                break;
            case "privacy":
                val = "public";
                break;
            default:
                val = "";
                break;
        }
        
        return val;
    }
    
    describr.profile.choices = {
        init : function () {
            var choicesList = describr.settings.choice, c, options;

            for ( c in choicesList ) {
                var l;

                options = [];

                for ( l in choicesList[c] ) {
                    options.push( "<option value=\"" + l + "\">" + choicesList[c][l] + "</option>" );
                }
                
                describr.profile.choices[c] = options;
            }
        }
    };

    /**
     * Retrieves display name choices
     * 
     * @return array
     */
    describr.profile.displayNameChoice = function () {
        var displayNames = [], 
            arr          = [],
            fName        = "", 
            lName        = "";

        if ( profileFieldSavedValue( "nickname" ) ) {
            displayNames.push( profileFieldSavedValue( "nickname" ) );
        }

        if ( profileFieldSavedValue( "user_login" ) ) {
            displayNames.push( profileFieldSavedValue( "user_login" ) );
        }

        if ( profileFieldSavedValue( "first_name" ) ) {
            fName += profileFieldSavedValue( "first_name" ); 
            displayNames.push( fName );
        }

        if ( profileFieldSavedValue( "last_name" ) ) {
            lName += profileFieldSavedValue( "last_name" );
            displayNames.push( lName );
        }

        if ( fName.length && lName.length ) {
            displayNames.push( fName + " " + lName );
            displayNames.push( lName + " " + fName );
        }

        if ( profileFieldSavedValue( "display_name" ) && ! displayNames.includes( profileFieldSavedValue( "display_name" ) ) ) {
            displayNames.unshift( profileFieldSavedValue( "display_name" ) );
        }

        displayNames.forEach( function ( item ) {
            item = "<option value=\"" + item + "\">" + item + "</option>";

            if ( ! arr.includes( item ) ) {
                arr.push( item );
            }
        });
        
        arr.push( "<option value=\"\">" + describrL10n.other + "</option>" );

        return arr;
    };
    
    //Handles events
    describr.profile.events = function () {
        var doc                      = $( document ),
            editPictureModalWrap     = $( "#describr-profile-picture-modal-wrapper" ),
            fieldEditors             = ".describr-profile-field-editors",
            menuTabs                 = $( ".describr-profile-menu-tab[role=tab]" ),
            menuTabpanels            = $( ".describr-profile-menu-tab-content[role=tabpanel]" ),
            contentTabs              = $( ".describr-profile-menu-tab-content-subtab[role=tab]" ),
            contentTabpanels         = $( ".describr-profile-menu-tab-content-subtab-content[role=tabpanel]" ),
            msg                      = $( "#describr-message" ).attr( { required : "required", "aria-required" : "true", "aria-invalid" : "false" } ),
            msgErr                   = $( "#describr-message-error" ),
            msgNotice                = $( "#describr-message-alert-container" ),
            tabCache                 = {},
            fetching                 = {},
            pictureFrame             = false,
            msgTimer                 = null,
            modalTimer               = null,
            profileEditorModalTimer  = null,
            removeEditAttchLinkTimer = null;        

        if ( describr.profile.activeTab ) {
            var curTabId = "describr-profile-menu-tab-" + describr.profile.activeTab;

            if ( describr.profile.queriedSubtab ) {
                curTabId += "-" + describr.profile.queriedSubtab;
            }

            describr.replaceState( curTabId );
            
            if ( ! describr.profile.queriedSubtab && describr.profile.activeSubtab ) {
                curTabId += "-" + describr.profile.activeSubtab;
            }

            onLoad( curTabId );
        }

        [menuTabs,contentTabs].forEach( function( tabs ) {
            handleClickAndKeydown( tabs );
            createArrowNavigation( tabs );
            tabs.each(function () {
                determineTabindex( $( this ) );
            });
        });
        
        pagingEventsInit();
        
        describr.popState();

        if ( useJqueryUi ) {
            $( window ).resize(function() {
                if ( selectmenus.length ) {
                    selectmenus.forEach(function( id ) {
                        var $this = $( "#" + id );
                        
                        if ( $this.selectmenu("instance") && $this.selectmenu("instance").isOpen ) {
                            $this.selectmenu("close");
                            $this.selectmenu("open");
                        }
                    });
                }
            });
        }
        
        //Close modals when their Close buttons or Cancel buttons are clicked
        $( ".describr-btn-cancel" ).on( "describr_click", function () {
            $( this ).closest( "[role=dialog]" ).parent().hide();
            retractControls();
        });

        //Profile picture modal when profile photo wrapper is clicked
        pictureWrap.on( click_, function ( e ) {
            if ( ! ( isClicked( e ) && describr.settings.isField( pictureKey ) ) ) {
                return;
            }
            
            var settings = fieldSettings( pictureKey ),
                label_   = settings.label || settings.title || "",   
                output   = "",
                label,
                title,
                icon,
                saved,
                asideSettings;
                
            if ( describr.settings.hasPrivacy( pictureKey ) ) {
                saved = savedPrivacy( pictureKey );
                label = privacyTitle( pictureKey, saved );
                asideSettings = describr.settings.getPrivacy( pictureKey );
                
                icon = "";

                if ( asideSettings.icons && asideSettings.icons[saved] ) {
                    icon = asideSettings.icons[saved];
                }

                output += "<div role=\"button\" tabindex=\"0\" aria-expanded=\"false\" data-state=\"privacy\" aria-label=\"" + sprintf( describrL10n.photo.privacy.label, label_, label ) + "\" title=\"" + label + "\" class=\"describr-profile-field-state\"><span class=\"describr-profile-field-state-icon\" aria-hidden=\"true\"><span class=\"" + icon + "\"></span></span></div>";
            }
                
            if ( describr.settings.hasStatus( pictureKey ) ) {
                saved = savedStatus( pictureKey );
                title = statusTitle( pictureKey, saved );
                asideSettings = describr.settings.getStatus( pictureKey );
                
                if ( asideSettings.editable ) {
                    label = sprintf( describrL10n.photo.status.perm, label_, title );
                } else {
                    label = sprintf( describrL10n.photo.status.no_perm, label_, title );
                }
                    
                if ( ! asideSettings.editable ) {
                    output += "<span class=\"describr-a11y-text\">" + label + "</span>";
                }

                output += "<div class=\"describr-profile-field-state\" role=\"button\" tabindex=\"0\" aria-expanded=\"false\" title=\"" + title + "\" ";

                if ( asideSettings.editable ) {
                    output += "aria-label=\"" + label + "\" data-state=\"status\"";
                } else {
                    output += "aria-hidden=\"true\"";
                }

                output += "><span class=\"describr-profile-field-state-icon\" aria-hidden=\"true\"><span class=\"" + asideSettings.icon + " describr-profile-field-status-" + saved + "\"></span></span></div>";
            }
              
            if ( hasSettings( pictureKey, "audit" ) ) {
                asideSettings = describr.settings.getAudit( pictureKey );
                                        
                output += "<div role=\"button\" tabindex=\"0\" title=\"" + ( asideSettings.title || asideSettings.label || "" ) + "\" label=\"" + describrL10n.photo.audit.label + "\" data-state=\"audit\" class=\"describr-profile-field-state\"><span class=\"describr-profile-field-state-icon\" aria-hidden=\"true\"><span class=\"" + ( asideSettings.icon || "" ) + "\"></span></span></div>";
            }
                
            if ( output ) {
                output = "<div class=\"describr-profile-picture-modal-item\"><div class=\"describr-profile-field-states\" data-field=\"" + pictureKey + "\">" + output + "</div></div>";
            }
            
            if ( ! settings.id ) {
                output = "";
            } else if ( objHasOwn( settings, 'rating' ) ) {
                output += "<div class=\"describr-profile-picture-modal-item\"><button type=\"button\" aria-expanded=\"false\" data-field=\"" + pictureKey + "\" data-action=\"rateProfilePicture\">" + describrL10n.photo.actions.rate + "</button></div>";
            } 

            if ( settings.can_upload ) {
                if ( settings.id ) {
                    output += "<div class=\"describr-profile-picture-modal-item\"><button type=\"button\" aria-expanded=\"false\" data-action=\"upload\">" + describrL10n.photo.actions.change + "</button></div>";
                        
                    if ( ! settings.required ) {
                        output += "<div class=\"describr-profile-picture-modal-item\"><button type=\"button\" data-field=\"" + pictureKey + "\" data-action=\"delete\">" + describrL10n.photo.actions.delete + "</button></div>";
                    }
                } else {
                    output += "<div class=\"describr-profile-picture-modal-item\"><button type=\"button\" aria-expanded=\"false\" data-action=\"upload\">" + describrL10n.photo.actions.upload + "</button></div>";
                }
            } else if ( ! settings.required && settings.id ) {
                output += "<div class=\"describr-profile-picture-modal-item\"><button type=\"button\" data-field=\"" + pictureKey + "\" data-action=\"delete\">" + describrL10n.photo.actions.delete + "</button></div>";
            }

            if ( output ) {
                editPictureModalWrap
                .find( "#describr-profile-picture-modal-main" ).html( output )
                .end()
                .show();
                pictureWrap.attr( "aria-expanded", "true" );
            }
        });
        
        //Aside modals
        doc.on( click_, ".describr [data-state]", function ( e ) {
            if ( ! isClicked(e ) ) {
                return;
            }
            
            var button    = $( this ),
                fieldWrap = button.closest( "[data-field]" ),
                aside     = button.data( "state" ),
                hash      = fieldWrap.data( "hash" ) || "";
            
            if ( ! fieldWrap.length ) {
                return;
            }

            modalField = fieldWrap.data( "field" );
            
            modalHash = hash;

            switch ( aside ) {
                case "privacy":
                case "status":
                    if ( selectAsideModal( modalField, aside, hash ) ) {
                        expand( button );
                    }                    
                    break;
                case "audit":
                    if ( describr.settings.hasAudit( modalField ) ) {
                        var logs    = "", 
                            modal   = $( "#describr-profile-field-audit-modal" ),
                            modalId = modal.attr( "id" ),
                            valSettings, 
                            fieldValue, 
                            fieldHtmlValue,
                            modalLabel, 
                            caption,
                            l10nStrings;
                        
                        describr.profile.getSavedAudit( modalField ).forEach( function ( log ) {
                            logs += "<tr><td><strong>" + log.displayName + "</strong></td><td>" + log.date + "</td></tr>";
                        });

                        if ( "" !== logs ) {

                            valSettings = getFieldA11yValue( modalField, hash );
        
                            fieldValue = valSettings.val;
                            
                            aside = "audit";
                            
                            modalLabel = getFielAsideDialogLabel( modalField, aside, valSettings.hasVal ? fieldValue : false );
        
                            fieldHtmlValue = getFieldModalHtmlValue( modalField, fieldValue );

                            l10nStrings = describrL10n.aside.modal.caption[aside];
                            
                            if ( ! valSettings.hasVal || isFieldNoWhichIs( modalField ) ) {
                                caption = sprintf( l10nStrings.noWhichIs, fieldValue );
                            } else {
                                caption = sprintf( l10nStrings.whichIs, maybeCityLabel( modalField ), fieldValue );
                            }
                            
                            $( "#" + modalId + "-label" ).html( modalLabel );//Modal label
                            $( "#" + modalId + "-main-field" ).html( fieldHtmlValue );//Modal subtitle (field's value)
                            modal.find( "caption" ).html( escapeHtml( caption ) )
                            .end()
                            .find( "tbody" ).html( logs )//Add log records to the table
                            .end()
                            .parent().show();//Show the modal
                            
                            expand( button );
                        }
                    }
                    break;
            }
        });
        
        //Show fields" options
        doc.on( click_, ".describr [data-action=showoptions]", function(e) {
            if ( ! isClicked( e ) ) {
                return true;
            }
            
            e.preventDefault();   

            var button   = $( this ),
                fieldWrap = button.closest( "[data-field]" ),
                field     = fieldWrap.data( "field" ),
                hash      = fieldWrap.data( "hash" ) || "",
                baseId    = "describr-profile-field-options-menu",
                menuId    = baseId + field + hash,
                menuItems = "",
                label,
                valSettings;
                    
            if ( ! $( "#" + menuId ).length ) {
                button.data( "options" ).split( "," ).forEach( function( option ) {
                    if ( objHasOwn( describr.profile.options, option ) ) {
                        menuItems += describr.profile.options[option]();
                    }
                });
                    
                if ( menuItems ) {
                    valSettings = getFieldA11yValue( field, hash );
                    
                    if ( ! valSettings.hasVal || isFieldNoWhichIs( field ) ) {
                        label = sprintf( describrL10n.moreOptionsMenuLabel.noWhichIs, valSettings.val );
                    } else {
                        label = sprintf( describrL10n.moreOptionsMenuLabel.whichIs, maybeCityLabel( field ), valSettings.val );
                    }

                    $( ".describr-popper-wrapper" ).html( "<div class=\"describr-popup\" id=\"" + menuId + "\"><div role=\"menu\" tabindex=\"0\" aria-label=\"" + escapeHtml( label ) + "\" class=\"describr-popup-menu\" data-field=\""+ field +"\" data-hash=\"" + hash + "\">" + menuItems + "</div><span data-popper-arrow aria-hidden=\"true\"></span></div>" );
                    
                    describr.poppers[menuId] = {
                        popup  : $( "#" + menuId ),
                        button : button,
                        show   : true
                    };
                }
            }
                
            describr.togglePopper();
        });
        
        //Toggle poppers
        doc.on( click_, function(e) {
            if ( ! isClicked( e ) ) {
                return true;
            }
            
            //The popup is being triggered, so there's no need to toggle.
            if ( $( e.target ).closest( "[data-action=showoptions]" ).length ) {
                return true;
            }
            
            var elem = $( e.target ).closest( ".describr-popup" ), id;
            
            if ( elem.length ) {
                id = elem.attr( "id" );

                if ( objHasOwn( describr.poppers, id ) ) {
                    describr.poppers[id].show = true;
                }
            }
            
            describr.togglePopper();
        });

        //Process fields' actions on click
        doc.on( click_, ".describr [data-action]", function( e ) {
            if ( ! isClicked( e ) ) {
                return true;
            }
            
            var button    = $( this ),
                fieldWrap = button.closest( "[data-field]" ),
                field     = fieldWrap.data( "field" ),
                hash      = fieldWrap.data( "hash" ) || "";

            switch( button.data( "action" ) ) {
                case "upload":
                    expand( button );
                    closeModals();
                    
                    if ( pictureFrame ) {
                        pictureFrame.open();
                        editPictureModalWrap.hide();
                        break;
                    }

                    //Initialize media library modal
                    pictureFrame = wp.media({
                        title    : describrL10n.picture.frameTitle,
                        button   : { text: describrL10n.picture.frameButton },
                        library  : { type : "image" },
                        multiple : false
                    });

                    pictureFrame.on( "select", function() {
                        describr.ajax.post( "describr-assign-picture", {
                            photo_id            : parseInt( pictureFrame.state().get( "selection" ).first().toJSON().id, 10 ), 
                            user_id             : userId, 
                            profile_photo_nonce : nonces.assignPicture
                        }).done( function( response ) {
                            describr.settings.fieldsToEdit[pictureKey].id = response.id; 
                            
                            updateDOMPicture( response );                           
                            
                            globalScreenReaderAlertNotice( describrL10n.picture.assigned );

                            noexpand( button );
                        }).fail( function( response ) {
                            noexpand( button );

                            centerAlert( response.message );
                        });
                    });

                    pictureFrame.on( "close", function() {
                        var pictureId = fieldSetting( pictureKey, "id" ),
                            hasPicture = false;
                
                        //Is there an current picture?
                        if ( pictureId ) {
                            //Get the attachments in media library, and check if any one of them has a attachment id matching the id of the user's current picture. If a match doesn't exist, remove the picture from the user
                            doc.find( ".media-modal .attachment" ).each( function () {
                                if ( parseInt( $( this ).data( "id" ), 10 ) === pictureId ) {
                                    hasPicture = true;
                                    return false;
                                }
                            });
                    
                            if ( ! hasPicture ) {
                                removePicture();
                            }
                        }
                    });
                        
                    pictureFrame.open();
                    editPictureModalWrap.hide();
                    break;
                case "rateProfilePicture":
                    var rating  = fieldSettings( field ).rating;
                    var ratings = describr.settings.ratings;
                    var i       = 0;
                    var str     = "";

                    if ( ! rating ) {
                        rating = ratings[0];
                    }

                    while ( i < ratings.length ) {
                        str += "<label><input type=\"radio\" name=\"rating\" value=\"" + ratings[i] + "\"";

                        if ( rating === ratings[i] ) {
                            str += " checked=\"checked\"";
                        }

                        str += " /> " + describrL10n.photo.ratings[ ratings[i] ] + "</label>";

                        if ( ( i + 1 ) !== ratings.length ) {
                            str += "<br />";
                        }

                        i++;
                    }
                    
                    expand( button );
                    closeModals();
                    
                    $( "#describr-profile-picture-rating-modal-main" ).html( str );
                    updateRatePictureButton();
                    $( "#describr-profile-picture-rating-modal" ).parent().show();
                    break;
                case "delete":
                    if ( field === pictureKey ) {
                        removePicture();
                    } else if ( ! isFieldUpdating( field, hash ) ) {
                        describr.togglePopper();

                        var ajax_ = {
                                profile_nonce : nonces.editProfile,
                                user_id       : userId
                            };
                        
                        ajax_[checkKey] = "";
                        ajax_[field] = hash;
                        
                        globalScreenReaderAlertNotice( describrL10n.updating );
                        
                        fieldUpdating( field, hash );

                        describr.ajax.post( "describr-delete-profile-data", ajax_ ).then( function( response ) {
                            prepReturnedServerMarkup( response );
                            
                            globalScreenReaderAlertNotice( describrL10n.updated );

                            if ( ! isObject( response ) || ! objHasOwn( response, "field" ) ) {
                                fieldUpdated( field, hash );
                            }
                        },
                        function ( response ) {
                            centerAlert( response );

                            fieldUpdated( field, hash );
                        });
                    };                    
                    break;
                case "block"://Block user
                    button.prop( "disabled", true );
                    
                    globalScreenReaderAlertNotice( describrL10n.blockUser.blocking );
                    
                    describr.ajax.post( "describr-block-profile", { 
                        user_id     : userId, 
                        block_nonce : nonces.blockUser
                    }).then( function( response ) {
                        globalScreenReaderAlertNotice( response.message );
                        button.prop( "disabled", false ).text( describrL10n.blockUser.unblock["title"] ).attr( { "data-action" : "unblock", "title" : describrL10n.blockUser.unblock["title"], "aria-label" : describrL10n.blockUser.unblock["label"] } ).data( "action", "unblock" );
                    },
                    function ( response ) {
                        button.prop( "disabled", false );
                        blockUserErr( response.message, button );
                    });
                    break;
                case "unblock"://Unblock user
                    button.prop( "disabled", true );
                    
                    globalScreenReaderAlertNotice( describrL10n.blockUser.unblocking );
                
                    describr.ajax.post( "describr-unblock-profile", { 
                        user_id       : userId, 
                        unblock_nonce : nonces.blockUser
                    }).then( function( response ) {
                        globalScreenReaderAlertNotice( response.message );
                        button.prop( "disabled", false ).text( describrL10n.blockUser.block["title"] ).attr( { "data-action" : "block", "title" : describrL10n.blockUser.block["title"], "aria-label" : describrL10n.blockUser.block["label"] } ).data( "action", "block" );
                    },
                    function ( response ) {
                        button.prop( "disabled", false );
                        blockUserErr( response.message, button );
                    });
                    break;
                case "edit":
                    expand( button );                        
                    describr.profile.mode = "editing";
                    describr.profile.changes.showEditModal( field, hash );
                    break;
                case "addfield":
                    describr.profile.mode = "adding";
                    
                    if ( "social_network" === field ) {
                        var socials = "", selectSocialNetwork = $( "#describr-profile-select-social-network" );
                        
                        socialNetworks().length && socialNetworks().forEach( function ( social_network ) {
                            if ( empty( profileFieldSavedValue( social_network ) ) ) {
                                socials += "<option value=\"" + social_network + "\">" + fieldSetting( social_network, "title" ) + "</option>";
                            }
                        });

                        if ( socials && selectSocialNetwork.length ) {
                            selectSocialNetwork.html( "<option value=\"\">" + describrL10n.defaultVal.social + "</option>" + socials );
                            
                            $( "#describr-profile-select-social-network-modal-wrapper" ).show();
                            
                            useJqueryUi && initJquerySelectmenu( selectSocialNetwork.attr( "id" ) );
                            
                            expand( button );
                        }
                    } else {
                        describr.profile.changes.showEditModal( field );
                        expand( button );
                    }
                    break;
                case "removelang":
                    var $input = button.closest( "div" ).find( "input[name=\"" + field + "\"]" ),
                        locales = arrSplice( $input.data( "value" ).split( "," ), button.data( "lang" ) );

                    $input.data( "value", locales.join( "," ) );
                    
                    if ( 0 === locales.length ) {
                        button.closest( "[role=grid]" ).remove();
                    } else {
                        button.closest( "[role=gridcell]" ).remove();
                        describr.profile.changes.langs.jsUpdate();
                    }
                    break;
            }
        });
        
        //Show the editor modal upon social media selection
        doc.on( "change", "#describr-profile-select-social-network", function () {
            var selectedValue = $( this ).val().trim();
            
            if ( selectedValue.length ) {
                showSocialModal( $( this ), selectedValue );
            }
        });

        //Remove "Edit Attachment" links in the media modal
        ! describr.settings.userCan && doc.on( click_, ".media-modal-content", function ( e ) {
            if ( ! isClicked( e ) ) {
                return true;
            }

            var modal   = $( this ),
                counter = 250;
                
            clearInterval( removeEditAttchLinkTimer );

            removeEditAttchLinkTimer = setInterval( function () {
                if ( 5000 <= counter ) {
                    clearInterval( removeEditAttchLinkTimer );
                }
                                
                if ( modal.find( "a.edit-attachment" ).length ) {
                    modal.find( "a.edit-attachment" ).remove();
                    clearInterval( removeEditAttchLinkTimer );
                }

                counter += 250;
            }, 250 );
            
        });

        //Remove error indicators from fields that are not checkboxes or <select>
        doc.on( "keydown", fieldEditors, function () {
            if ( "checkbox" !== this.type.toLowerCase() && "select" !== this.tagName.toLowerCase() ) {
                toggleFieldError( this.name );
            }
        });
        
        //Move focus to the appropriate language gridcell
        doc.on( "keydown", ".describr-langs-grid", function ( e ) {
            describr.profile.changes.langs.jsEvent( e );
        });
        
        //Remove error indicators from fields that are not checkboxes or <select>
        doc.on( "change", fieldEditors, function() {
            var type    = this.type.toLowerCase(),
                _name   = this.name.toLowerCase(), 
                tagName = this.tagName.toLowerCase(),
                changes = describr.profile.changes, 
                settings, 
                to, 
                toId;

            //Removes error indicators from checkboxes, radio buttons, and <select>
            if ( "checkbox" === type || "select" === tagName ) {
                toggleFieldError( _name );

                if ( "display_name" === _name && "select" === tagName ) {
                    changeDisplayName( $( this ).val().trim(), $( this ) );
                }
            }

            //Test if present or graduated
            if ( "checkbox" === type && "present" === _name ) {
                settings = changes.settings;

                //Test for To date
                if ( objHasOwn( settings, "to" ) ) {
                    to = settings.to;
                    toId = to.id;
                    
                    //Test if present or graduated has been checked
                    if ( this.checked ) {
                        removeFieldEditor( toId );//Remove To if checked
                    } else if ( ! $( "#" + toId ).length ) {
                        $( "#" + settings.from.id ).parent().after( addFieldToModal( addFieldToModal_( to ), true ) );
                    }
                }
            }
        });
        
        //Empty text boxes using jQuery autocomplete data-value attributes if they are empty
        doc.on( "blur", fieldEditors, function () {
            var elem = $( this ),
                name_ = elem.attr( "name" ).toLowerCase(),
                val   = elem.val().trim();
            
            if ( ["city","hometown","current_city","partner"].includes( name_ ) ) {
                if ( empty( val ) && describr.profile.changes.settings[name_].hasJqueryHelper ) {
                    elem.data( "value", "" );

                    if ( "partner" === name_ && objHasOwn( describr.profile.changes.settings, "partner_id" ) ) {
                        $( "#" + describr.profile.changes.settings.partner_id.id ).val( "" );
                    }
                }
            }

            if ( "partner" === name_ && describr.profile.changes.settings.partner.hasJqueryHelper ) {
                /*Test if 1) the input of partner's value is being aided by jQuery's Autocomplete ui, 
                2) the input value is not the same as the selected value, and 3) the partner ID can be set.*/
                if ( val !== trim_( elem.data( "value" ) ) && objHasOwn( describr.profile.changes.settings, "partner_id" ) ) {
                    //The partner ID can only be set dynamically, so unset it if the user chooses a different partner.
                    $( "#" + describr.profile.changes.settings.partner_id.id ).val( "" );
                }
            }
        });
        
        //Update the handle's data-baseurl attribute's value when base url radio button is checked
        doc.on( "change", ".describr-profile-field-editor-baseurl", function () {
            if ( this.checked ) {
                var radioButton = $( this );
                $( "#" + radioButton.data( "for" ) ).data( "baseurl", radioButton.val() );
            }
        });
        
        //Submit modal asides
        $( "#describr-profile-select-aside-modal-wrap" ).on( "submit", function( e ) {
            e.preventDefault();
            var form_        = $( this ),
                submitButton = form_.find( "button[type=submit]" ),
                specs        = form_.find( "input[type=radio]" ).eq(0),
                asideName    = specs.attr( "name" ),
                checkedValue = form_.find( "input[name=\"" + asideName + "\"]:checked" ).val(),
                check        = form_.find( "[name=\"" + checkKey + "\"]" ).val(),
                update       = {};
            
            if ( isFieldUpdating( modalField, modalHash ) ) {
                return false;
            }

            if ( isCheck( check ) ) {
                return;
            }
            
            if ( checkedValue === getFieldSavedAside( selectedAside, modalField ) || ! checkedValue ) {
                asidesErrNotice( describrL10n.errorMessages.field.nochange  );
                return;
            }

            update[modalField + "_asides"]            = {};
            update[modalField + "_asides"][asideName] = checkedValue;
            
            update.profile_nonce = nonces.editProfile;
            update.user_id       = userId;
            update.field         = modalField;
            
            update[checkKey] = "";      
            
            globalScreenReaderAlertNotice( describrL10n.updating );
            
            updateSubmitButton( true, true );
            
            fieldUpdating( modalField, modalHash );
            
            describr.ajax.post( "describr-change-aside", update ).then( function( response ) {
                prepReturnedServerMarkup( response );

                updateSubmitButton( false, true );

                fieldUpdated( modalField, modalHash );

                globalScreenReaderAlertNotice( describrL10n.updated );
                
                closeModalPostUpdate( form_ );
            },
            function( response ) {
                asidesErrNotice( response );
                
                updateSubmitButton( false, true );

                fieldUpdated( modalField, modalHash );
            });
        });
        
        //Submit rating for the profile photo
        $( "#describr-profile-picture-rating-modal-wrap" ).on( "submit", function( e ) {
            e.preventDefault();
            var form_        = $( this ),
                dialog       = form_.closest( '[role=dialog]' ),
                submitButton = form_.find( "button[type=submit]" ),
                ratingName   = form_.find( "input[type=radio]" ).eq(0).attr( "name" ),
                checkedValue = form_.find( "input[name=\"" + ratingName + "\"]:checked" ).val(),
                check        = form_.find( "[name=\"" + checkKey + "\"]" ).val(),
                rate         = {},
                settings     = fieldSettings( pictureKey );
            
            if ( isCheck( check ) ) {
                return;
            }
            
            if ( checkedValue === settings.rating || ! checkedValue ) {
                dialog.find( ".describr-notice" ).not( ".static" ).remove();
                dialog.prepend( "<div class=\"describr-notice describr-notice-error is-dismissible\" role=\"alert\"><p>" + describrL10n.errorMessages.field.nochange + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" );
                removeNotice();
                return;
            }

            rate[ratingName] = checkedValue; 
            rate[checkKey] = "";           
            rate.profile_photo_nonce = nonces.ratePicture;
            rate.user_id = userId;
            rate.photo_id = settings.id;
                    
            globalScreenReaderAlertNotice( describrL10n.photo.rating.screenReader.rating );
            
            updateRatePictureButton( true );
            
            describr.ajax.post( "describr-rate-picture", rate ).then( function( response ) {
                describr.settings.fieldsToEdit[pictureKey].rating = response.rating;

                dialog.find( ".describr-notice" ).not( ".static" ).remove();
                dialog.prepend( "<div class=\"describr-notice describr-notice-success is-dismissible\" role=\"alert\"><p>" + sprintf( describrL10n.photo.rating.screenReader.rated, "<strong>" + response.rating + "</strong>" ) + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" );
                removeNotice();

                updateDOMPicture( response );
                
                prepReturnedServerMarkup( response );

                updateRatePictureButton();
            },
            function( response ) {
                closeModalPostUpdate( form_ );

                updateRatePictureButton();

                centerAlert( response.message );
            });
        });

        //Submit fields changes
        $( "#describr-profile-field-editor-modal-wrap" ).on( "submit", function( e ) {
            e.preventDefault();
            var _check           = $( "[name=" + checkKey + "]", this ),
                fieldEditForm    = $( this ),
                changes          = describr.profile.changes,
                settings_        = changes.settings,
                fields           = changes.fields,
                field            = changes.activeField,
                hash             = changes.fieldHash,
                activeAsides     = changes.asides,
                savedAsides_     = changes.savedAsides,
                errorMessages    = describrL10n.errorMessages,
                hasChange        = false,
                canSubmit        = true,
                isNotEmpty       = false,
                hasNonEmptyField = false,
                updates          = { fields : {} },
                fieldsCount      = 0,
                f,
                i,
                fields_,
                type,
                id,
                _id_,
                curVal,
                newVal,
                valLength,
                settings,
                elem,
                label,
                name_,
                key_,
                maxLength, 
                minLength, 
                length_,
                isRequired,
                errors,
                options,
                data,
                regEx,
                userOptions,
                newUrl, 
                settingsBaseurl, 
                userBaseurl,
                email, 
                emailEr, 
                domain_,
                formats, 
                dateErr,
                sep,
                escSep,
                dateMatch,
                pattern;
            
            if ( isFieldUpdating( field, hash ) ) {
                return false;
            }

            for ( f = 0; f < fields.length; f++  ) {
                key_ = fields[f];

                if ( activeAsides.includes( key_ ) ) {
                    continue;
                }

                fieldsCount++;
                
                settings   = settings_[key_];
                label      = settings.label;
                id         = settings.id;
                type       = settings.type;
                name_      = settings.name_;
                curVal     = settings.savedVal;
                maxLength  = settings.maxLength;
                minLength  = settings.minLength;
                length_    = settings.length_;
                isRequired = settings.required;
                options    = settings.options;
                
                errors = [];

                _id_ = id;

                if ( settings.isCustom ) {
                    _id_ += "-custom";
                }

                settings_[key_]["id_"] = _id_;

                elem = $( "#" + _id_ );
                
                data = elem.data();
                
                newVal = updatingFieldValue( settings );

                if ( "checkbox" === type && ! elem.prop( "checked" ) ) {
                    newVal = "";
                }
                
                //Empty the "to" field value if the user is still present
                if ( "to" === name_ && objHasOwn( settings_, "present" ) && $( "#" + settings_.present.id ).prop( "checked" ) ) {
                    newVal = "";
                }

                isNotEmpty = ! empty( newVal );
                
                if ( ["display_name","user_nicename"].includes( name_ ) && "0" === newVal ) {
                    isNotEmpty = false;
                }

                if ( isNotEmpty ) {
                    hasNonEmptyField = true;
                }
                
                valLength = getLength( newVal );
                
                if ( isNotEmpty ) {
                    if ( options ) {
                        if ( "timezone" === name_ ) {
                            options = Array.isArray( options ) ? options : [];

                            if ( ! ( options.includes( newVal ) || /^UTC(\+|\-)[0-9.]{1,5}$/.test( newVal ) ) ) {
                                errors.push( sprintf( errorMessages.field.invalid, label ) );
                            }
                        } else if ( isObject( options ) ) {
                            userOptions = newVal.split( "," );
                            i = 0;
                            
                            while ( i < userOptions.length ) {
                                if ( ! objHasOwn( options, userOptions[i] ) ) {
                                    errors.push( sprintf( errorMessages.field.invalid, label ) );
                                    break;
                                }

                                i++  
                            }
                        }
                    }
                } else if ( isRequired ) {
                    errors.push( sprintf( errorMessages.field.required, label ) );
                }
                
                if ( false !== valLength ) {
                    if ( minLength && maxLength && ( valLength < minLength || valLength > maxLength ) ) {
                        errors.push( sprintf( errorMessages.limits.both, label, minLength, maxLength ) );
                    } else if ( maxLength && valLength > maxLength ) {
                        errors.push( sprintf( errorMessages.limits.upper, label, maxLength ) );
                    } else if ( minLength && valLength < minLength  ) {
                        errors.push( sprintf( errorMessages.limits.lower, label, minLength ) );
                    } else if ( length_ && length_ !== valLength ) {
                        if ( "country" !== name_ || 0 === errors.length ) {
                            errors.push( sprintf( errorMessages.limits.exact, label, length_ ) );
                        }
                    }
                }

                if ( isNotEmpty ) {
                    if ( "url" === type ) {
                        newUrl = newVal;
                        
                        if ( settings.baseUrl ) {
                            settingsBaseurl = Array.isArray( settings.baseUrl ) ? settings.baseUrl : [settings.baseUrl];

                            if ( objHasOwn( data, "baseurl" ) ) {
                                userBaseurl = data.baseurl;
                                
                                if ( ! settingsBaseurl.includes( userBaseurl ) ) {
                                    errors.push( sprintf( errorMessages.field.invalid, label ) );
                                } else {
                                    regEx = new RegExp( "^(" + escRegex( userBaseurl ) + ")", "i" );
                                    newUrl = newUrl.replace( regEx, "" );

                                    if ( empty( newUrl ) ) {
                                        errors.push( sprintf( errorMessages.field.invalid, label ) );
                                    }

                                    newUrl = userBaseurl + newUrl;
                                }
                            } else {
                                settingsBaseurl = settingsBaseurl[0];
                                regEx = new RegExp( "^(" + escRegex( settingsBaseurl ) + ")", "i" );
                                newUrl = settingsBaseurl + newUrl.replace( regEx, "" );
                            }
                        }
                        
                        newUrl = newUrl.replace( /^[:\/]+/, "" );
                        
                        if ( !/^https?/i.test( newUrl ) ) {
                            newUrl = "http://" + newUrl;
                        }

                        newVal = newUrl;
                    } else if ( "email" === type || "user_email" === name_ ) {
                        email    = newVal; 
                        emailErr = false;
                    
                        if ( 
                            ! /@/.test( email ) //Test for @ symbol
                            ||
                            ( 6 > email.length ) //Test min length
                            ||
                           ( -1 === email.indexOf( "@", 1 ) ) //Test for an @ character after the first position
                        ) {
                            emailErr = true;
                        }

                        domain_ = email.substring( email.lastIndexOf( "@" ) + 1 );
            
                        //Test for sequences of periods
                        if ( /\.{2,}/.test( domain_ ) ) {
                            emailErr = true;
                        }
                        
                        //Test for leading and trailing periods and whitespace.
                        if ( /^[ \t\n\r\0\x0B.]+|[ \t\n\r\0\x0B.]+$/.test( domain_ ) ) {
                            emailErr = true;
                        }
            
                        //Test for at least two domain sub parts: [part1].[part2]
                        if ( 2 > domain_.replace( /^\.+|\.+$/, "" ).split( "." ).length ) {
                            emailErr = true;
                        }

                        if ( emailErr ) {
                            errors.push( sprintf( errorMessages.field.invalid, label ) + " " + describrL10n.emailEx );
                        }
                    } else if ( "date" === type || "birthdate" === name_ ) {
                        formats = describr.settings.dateFormats; 
                        dateErr = true;
                        i       = 0;
                        sep     = describr.settings.dateFormatSep;
                        escSep  = escRegex( sep );
                                                
                        while ( i < formats.length  ) {
                            pattern = [];

                            formats[i].split( sep ).forEach( function ( date_ ) {
                                pattern.push( "(?<" + date_+ ">[" + DIGITS + "]{" + date_.length + "})" );
                            });
                            
                            regEx = new RegExp( "^" + pattern.join( escSep ) + "$" );

                            dateMatch = newVal.match( regEx );
                            
                            if ( dateMatch ) {
                                if ( dateMatch.groups.MM && /^[0-9]+$/.test( dateMatch.groups.MM ) && ! ( 12 >= parseInt( dateMatch.groups.MM, 10 ) && 1 <= parseInt( dateMatch.groups.MM, 10 ) ) ) {
                                    break;
                                }

                                if ( dateMatch.groups.DD && /^[0-9]+$/.test( dateMatch.groups.DD ) && ! ( 31 >= parseInt( dateMatch.groups.DD, 10 ) && 1 <= parseInt( dateMatch.groups.DD, 10 ) ) ) {
                                    break;
                                }

                                dateErr = false;
                                break;
                            }

                            i++;
                        }

                        if ( dateErr ) {
                            errors.push( sprintf( errorMessages.field.invalid, label ) + " " + describrL10n.dateFormatEx );
                        }
                    }
                }

                if ( ! hasChange ) {
                    hasChange = ( newVal !== curVal );
                }

                updates.fields[name_] = newVal;
                
                switch(name_) {
                    case "kakaotalk":
                        if ( /[\s\n\t\r]{1}/.test( newVal ) ) {
                            errors.push( sprintf( errorMessages.illegalChars.kakaotalk, label ) );
                        }
                        break;
                    case "line":
                        regEx = new RegExp( "^[a-z" + DIGITS + "._-]+$" );
                        
                        if ( ! regEx.test( newVal ) ) {
                            errors.push( sprintf( errorMessages.illegalChars.line, label ) );
                        }
                        break;
                    case "discord":
                        regEx = new RegExp( "^[" + DIGITS + "]+$" );

                        if ( ! regEx.test( newVal ) ) {
                            errors.push( sprintf( errorMessages.illegalChars.digits, label ) );
                        }
                        break;
                    case "ext":
                        if ( isNotEmpty ) {
                            regEx = new RegExp( "^[" + DIGITS + "]+$" );
                            
                            if ( ! regEx.test( newVal ) ) {
                                errors.push( sprintf( errorMessages.illegalChars.digits, label ) );
                            }
                        }
                        break;
                    case "number":
                        var id_         = "#" + id.replace( /number$/, "" ),
                            elemCountry = $( id_ + "country" ),
                            country     = ( isNotUsingSelect( elemCountry ) ? elemCountry.data( "value" ) : elemCountry.val() ).trim(),
                            phoneNumber = newVal;
                        
                        if ( objHasOwn( settings_, "ext" ) ) {
                            var ext = $( id_ + "ext" ).val().trim();

                            if ( ! empty( ext ) ) {
                                regEx = new RegExp( "^[" + DIGITS + "]+$" );
                                
                                if ( regEx.test( ext ) ) {
                                    phoneNumber += " ext. " + ext;
                                }
                            }
                        }
                        
                        if ( ! phoneNumberPatterns().test( phoneNumber ) ) {
                            errors.push( sprintf( errorMessages.field.invalid, label ) );
                        } else if ( "undefined" !== typeof libphonenumber ) {
                            try {
                                libphonenumber.parsePhoneNumberWithError( phoneNumber, { "extract" : false, "defaultCountry" : country } );
                            } catch ( error ) {
                                if ( error instanceof ParseError) {
                                    if ( error.message && objHasOwn( errorMessages.phoneNumber, error.message ) ) {
                                        errors.push( sprintf( errorMessages.phoneNumber[error.message], label ) );
                                    } else {
                                        errors.push( sprintf( errorMessages.field.invalid, label ) );
                                    }
                                }
                            }
                        }

                        break;
                }
                
                if ( 0 < errors.length ) {
                    addFieldError( id, errors, settings );
                    canSubmit = false;
                }
            }
            
            if ( ! canSubmit ) {
                if ( 2 < fieldsCount ) {
                    fieldEditNotice( errorMessages.errors, 1 );
                }

                return false;
            }

            //Test if relationship has at least one nonempty field because it doesn't have a required field
            if ( "relationship" === field ) {
                var isValidfield = false, 
                    relFields    = ["status","partner","since"],//Partner_id is also a relationship field
                    g            = 0, 
                    relVal;
                
                while ( g < relFields.length ) {
                    if ( objHasOwn( settings_, relFields[g] ) ) {
                        relVal = updatingFieldValue( settings_[relFields[g]] );
                        
                        if ( "partner" !== relFields[g] ) {
                            if ( "0" !== relFields[g] && ! empty( relVal ) ) {
                                isValidfield = true;
                                break;
                            }
                        } else if ( ! empty( relVal ) ) {
                            isValidfield = true;
                            break;
                        }
                    }

                    g++;
                }

                if ( ! isValidfield ) {
                    fieldEditNotice( sprintf( errorMessages.field.moreInfo, fieldSetting( field, "label" ) ), 1 );
                    return false;
                }
            }

            //Test if an empty, single field is being submitted
            if ( ! hasNonEmptyField ) {
                fieldEditNotice( sprintf( errorMessages.field.moreInfo, fieldSetting( field, "label" ) ) + ( ! fieldSetting( field, "required" ) ? ( " " + errorMessages.field.howToDelete ) : "" ), 1 );
                return false;
            }
            
            activeAsides.length && activeAsides.forEach( function ( item ) {
                var currentSavedAside = savedAsides_[item],//was set to false to ensure that the user can submit the default on first update
                    asideSettings     = settings_[item],
                    asideName         = asideSettings["name"],
                    htmlId            = asideSettings.id,
                    asideHtmlElem     = $( "#" + htmlId ),
                    newSavedAside     = updatingFieldValue( asideSettings );
                
                if ( currentSavedAside === newSavedAside ) {
                    return;
                }
                
                if ( canSubmit && objHasOwn( asideSettings, "options" ) && isObject( asideSettings.options ) ) {
                    if ( ! objHasOwn( asideSettings.options, newSavedAside ) ) {
                        addFieldError( htmlId, sprintf( errorMessages.field.invalid, asideSettings["label"] ), asideSettings );
                        canSubmit = false;
                        return;
                    }
                    
                    if ( ! objHasOwn( updates, field + "_asides" ) ) {
                        updates[field + "_asides"] = {};
                    }

                    updates[field + "_asides"][asideName] = newSavedAside;

                    hasChange = true;
                }
            });
            
            if ( ! canSubmit ) {
                fieldEditNotice( errorMessages.errors, 1 );
                return false;
            }
            
            if ( ! hasChange ) {
                fieldEditNotice( errorMessages.field.nochange, 1 );
                return false;
            }

            if ( 1 < fieldsCount ) {
                fields_ = $.extend( true, {}, updates.fields );

                updates.fields = {};//fields is now an empty object

                updates.fields[field] = fields_;//fields contains one field whose value is an object containing multiple subfields
            }
            
            if ( hash ) {
                updates["update"] = hash;
            }

            updates.profile_nonce = nonces.editProfile;
            updates.user_id       = userId;
            
            if ( isCheck( _check.val() ) ) {
                fieldEditNotice( describrL10n.check, 1 );
                return false;
            }

            updates[_check.attr( "name" )] = _check.val();
            
            updateSubmitButton( true );

            globalScreenReaderAlertNotice( describrL10n.updating );

            fieldUpdating( field, hash );
            
            describr.ajax.post( "describr-edit-profile-data", updates ).then( function( response ) {
                if ( isObject( response ) ) {
                    if ( objHasOwn( response, "raw" ) && isObject( response.raw ) ) {
                        if ( objHasOwn( response.raw, "user_nicename" ) ) {
                            window.location.href = ( location.protocol + "//" + location.host + "/?user_id=" + userId + "&nonce=" + describr.settings.urlNonces.nicename + "&describr_changed_field=nicename" );
                        } else if ( objHasOwn( response.raw, "display_name" ) ) {
                            window.location.href = ( location.protocol + "//" + location.host + "/?user_id=" + userId + "&nonce=" + describr.settings.urlNonces.editProfile + "&describr_changed_field=display_name" );
                        }
                    }
                    
                    prepReturnedServerMarkup( response );
                }
                
                updateSubmitButton();

                fieldUpdated( field, hash );
                
                globalScreenReaderAlertNotice( describrL10n.updated );

                closeModalPostUpdate( fieldEditForm );
            },
            function ( response ) {
                if ( isObject( response ) ) {
                    fieldEditNotice( describrL10n.fieldErrors, 1 );

                    var _key;

                    for ( _key in response ) {
                        if ( objHasOwn( settings_, _key ) ) {
                            addFieldError( settings_[_key].id_, response[_key], settings_[_key] );
                        }
                    }
                } else {
                    fieldEditNotice( response, 1 );
                }
                
                updateSubmitButton();

                fieldUpdated( field, hash );
            });
        });

        /**
         * Sets tabindex and aria-selected on the clicked tab
         * 
         * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
         * 
         * @param {jQuery object} tab Active Tab
         */
        function setSelectedTab( tab ) {
            tab.closest( "[role=tablist]" ).find( "[role=tab]" ).each( function () {
                $( this ).attr( "aria-selected", "false" );
            });

            tab.attr( "aria-selected", "true" );
        }

        /**
         * Sets click and keydown events handlers
         * 
         * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
         */
        function handleClickAndKeydown( tabs ) {
            tabs.each( function () {
                var tab = $( this );
                tab.on( click_, function ( e ) {
                    if ( isClicked( e ) ) {
                        if ( describr.pushState( tab ) ) {
                            e.preventDefault();
                            return false;
                        }
                    }
                });

                describr.processTabKey( tab );
            });
        }

        /**
         * Prevents focus on tabpanels that have focusable elements
         * 
         * The focus will jump to the first focusable element in the tabpanel
         * 
         * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
         */
         function determineTabindex( tab ) {
            var tabpanel         = $( "[aria-labelledby=\"" + tab.attr( "id" ) + "\"]" ),
                detPanelTabindex = function( panel ) {
                    panel.find( "button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"]):not([disabled]), details:not([disabled]), summary:not(:disabled)" ).length ? panel.attr( "tabindex", "-1" ) : panel.attr( "tabindex", "0" );
                };
            detPanelTabindex(tabpanel);
            tabpanel.siblings( "[role=tabpanel]" ).each(function(){
                detPanelTabindex( $( this ) );
            });
        }
        
        /**
         * Moves tab focus when arrow keys are pressed
         * 
         * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
         */
        function createArrowNavigation( tabs ) {
            tabs.each( function () {
                $( this ).on( "keydown", function ( e ) {
                    var activeTab = determineActiveTab( $( this ), tabs, e );

                    if ( activeTab ) {
                        describr.pushState( activeTab );
                        activeTab.focus();
                    }
                });
            });
        }
        
        function determineActiveTab( tab, tabs, e ) {
            var firstTab  = tabs.eq( 0 ),
                lastTab   = tabs.eq( tabs.length - 1 ),
                activeTab = false,
                i         = 0;

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

            return activeTab;
        }

        /**
         * Adds click and keydown events to paging links
         */        
        function pagingEventsInit() {
            describr.pagingTabs.forEach( function( item ) {
                var tab      = "describr-profile-menu-tab-" + item,
                    selector = "[aria-labelledby=\"" + tab + "\"] .post-page-numbers";

                doc.on( click_, selector, function( e ) {
                    if ( isClicked( e ) ) {
                        if ( pushStatePaging( $( this ), tab ) ) {
                            e.preventDefault();
                            return false;
                        }
                    } 
                });
                
                doc.on( "keydown", selector, function ( e ) {
                    var link      = $( this ),
                        activeTab = determineActiveTab( link, link.parent().find( ".post-page-numbers" ), e );
                    
                    if ( activeTab ) {
                        pushStatePaging( activeTab, tab );
                    }
                });

                describr.processTabKey( selector );
            });
        }
        
        function pushStatePaging( link, tab ) {
            if ( link.attr( "href" ) ) {
                var href = link.attr( "href" ),
                    pagedMatch = describr.determinePaged( href ),
                    paged      = 1;
                
                if ( pagedMatch ) {
                    paged = pagedMatch;

                    var pagingSpecs = tab + "__paging=" + paged;

                    try {
                        history.pushState( pagingSpecs, "", href );
                                
                        if ( ! describr.pushStatePaging.includes( pagingSpecs ) ) {
                            describr.pushStatePaging.push( pagingSpecs );
                        }
                    } catch ( err ) {
                        console.log( err );
                        return false;
                    }
                }
                            
                fetchTabSchema( tab, { "paged" : paged } );
            }

            return true;
        }

        /**
         * Fetches the active tab content on page load
         * 
         * @param {string} id The active tab's HTML ID
         */
        function onLoad( id ) {
            var loadedTab  = $( "#" + id ),
                contentWrap = $( "[aria-labelledby=\"" + id + "\"]" ),
                content_   =  "" + contentWrap.html(),
                hasContent = false;
            
            if ( isActiveTab( loadedTab ) && 0 !== content_.trim().length ) {
                hasContent = true;

                if ( ! describr.pagingTabs.includes( loadedTab.data( "tab" ) ) ) {
                    tabCache[id] = true;
                }
            }
            
            if ( ! hasContent ) {
                fetchTabSchema( loadedTab );
            }
        }
        
        /**
         * Fetches tab data from the server
         * 
         * @param {jQuery object} Tab
         * @param {data}          data Additional data to send to the server. Optional
         */
        function fetchTabSchema( tab, data ) {
            if ( ! isObject( tab ) ) {
                tab = $( "#" + tab );
            }

            var tabName   = tab.data( "tab" ),
                id        = tab.attr( "id" ),
                action    = "describr-profile-",
                ajax      = {},
                parentTab,
                d;

            if ( tab.data( "menutab" ) ) {
                action += tabName;
                ajax.active_tab = tabName;
            } else {
                parentTab = tab.data( "parenttab" );
                action += parentTab;
                ajax.active_tab = parentTab;
                ajax.active_subtab = tabName;
            }
            
            if ( fetching[ajax.active_tab] ) {
                return;
            }
            
            if ( data ) {
                for ( d in data ) {
                    ajax[d] = data[d];
                }
            }

            fetching[ajax.active_tab] = tab;

            ajax.user_id = userId;

            ajax.profile_structure_nonce = nonces.profileSchema;
            
            alertUser( tab );
            
            describr.ajax.post( action, ajax ).then( function( response ) {
                var id, _id, tabpanel, subtab, regExp, html_;
                
                if ( isObject( response ) && objHasOwn( response, "tab" ) ) {
                    tab = fetching[response.tab];
                    fetching[response.tab] = false;
                    id = tab.attr( "id" );                    
                    tabpanel = $( "[aria-labelledby=\"" + id + "\"]" );//Menu tab or subtabs" tabpanel
                    
                    tabpanel.find( ".describr-loading" ).remove();
                     
                    if ( describr.pagingTabs.includes( response.tab ) ) {
                        if ( ! ( "" + tabpanel.html() ).trim() ) {
                            html_ = "<span class=\"describr-a11y-text\" id=\"" + id + "-caption\"></span>";
                            html_ += "<span class=\"describr-a11y-text\" id=\"" + id + "-count\"></span>";
                            html_ += "<div id=\"" + id + "-search-results\"></div>";
                            
                            tabpanel.html( html_ );

                            $( "#" + id + "-caption" ).text( describrL10n[ tab.data( "tab" ) + "Caption" ] );
                        }
                        
                        $( "#" + id + "-search-results" ).html( Array.isArray( response.results ) ? response.results.join( "\n" ) : response.results );
                        $( "#" + id + "-count" ).html( sprintf( describrL10n[ tab.data( "tab" ) + "Show" ], this.results, this.totalPages ) );
                        $( "#" + id + "-search-results .post-page-numbers.current" ).focus();
                    } else {
                        if ( objHasOwn( response, "subtab" ) ) {
                            regExp = new RegExp( "(" + escRegex( response.tab + "-" + response.subtab ) + ")$" );

                            if ( ! regExp.test( id ) ) {
                                _id = id + "-" + response.subtab;

                                tabpanel = $( "[aria-labelledby=\"" + _id + "\"]" );

                                tabCache[_id] = true;
                            }
                        }

                        describr.profile.markupWrap = tabpanel;

                        prepReturnedServerMarkup( response );

                        if ( id ) {
                            //Cache tab to prevent repeated server request for its content
                            tabCache[id] = true;
                            tabCache[tab.closest( "[aria-labelledby]" ).attr( "aria-labelledby" )] = true;
                        }
                    }
                    
                    determineTabindex( tab );                          
                }

                $( "[aria-labelledby=\"" + tab.attr( "id" ) + "\"] .describr-loading" ).remove();
            },
            function ( response ) {
                var id = tab.attr( "id" ), 
                    _id, 
                    errElem, 
                    regExp;

                $( ".describr-notice" ).not( ".static" ).remove();

                if ( isObject( response ) ) {
                    errElem = "<div class=\"describr-notice describr-notice-error is-dismissible\" role=\"alert\"><p>" + response.message + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>";

                    if ( objHasOwn( response, "tab" ) ) {
                        if ( describr.pagingTabs.includes( response.tab ) || ! objHasOwn( response, "subtab" ) ) {
                            $( "[aria-labelledby=\"" + id + "\"]" ).html( errElem );
                        } else if ( objHasOwn( response, "subtab" ) && response.subtab ) {
                            _id = id + "-" + response.subtab;
                            
                            $( "[aria-labelledby=\"" + id + "\"]" ).find( ".describr-loading" ).remove();
                            
                            regExp = new RegExp( "(" + escRegex( response.tab + "-" + response.subtab ) + ")$" );

                            if ( ! regExp.test( id ) ) {                     
                                if ( $( "[aria-labelledby=\"" + _id + "\"]" ).length ) {
                                    $( "[aria-labelledby=\"" + _id + "\"]" ).html( errElem );
                                } else {
                                    $( "[aria-labelledby=\"" + id + "\"]" ).html( errElem );
                                }
                            } else {
                                $( "[aria-labelledby=\"" + _id + "\"]" ).html( errElem );
                            }
                        } else {
                            $( "[aria-labelledby=\"" + id + "\"]" ).find( ".describr-loading" ).remove();
                            $( "[aria-labelledby=\"" + id + "\"]" ).prepend( "<div class=\"describr-notice describr-notice-error is-dismissible\" role=\"alert\" style=\"position: absolute; top: 16px; left: 10px; right: 16px; z-index: 2;\"><p>" + response.message + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" );
                            removeNotice();
                        }
                    } else {
                        $( "[aria-labelledby=\"" + id + "\"]" ).find( ".describr-loading" ).remove();
                        $( "[aria-labelledby=\"" + id + "\"]" ).prepend( "<div class=\"describr-notice describr-notice-error is-dismissible\" role=\"alert\" style=\"position: absolute; top: 16px; left: 10px; right: 16px; z-index: 2;\"><p>" + response.message + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" );
                        removeNotice();
                    }
                } else {
                    $( "[aria-labelledby=\"" + id + "\"]" ).html( "<div class=\"describr-notice describr-notice-error is-dismissible\" role=\"alert\"><p>" + response + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" );
                }
                
                fetching[ajax.active_tab] = false;
            });
        }

        function alertUser( tab ) {
            var tabName = tab.data( "tab" ),
                tabpanel = $( "[aria-labelledby=\"" + tab.attr( "id" ) + "\"]" ),
                text;
            
            if ( objHasOwn( describrL10n, "tabLoading" ) && objHasOwn( describrL10n.tabLoading, tabName ) ) {
                text = describrL10n.tabLoading[tabName];
            }

            if ( ! text ) {
                text = describrL10n.loading;
            }

            globalScreenReaderAlertNotice( text );

            if ( 0 === tabpanel.find( ".describr-loading" ).length ) {
                tabpanel.css( "min-height", "50px" ).append( "<div class=\"describr-loading\" aria-hidden=\"true\"></div>" );
            }
        }

        /**
         * Shows the active tabpanel when its related tab is clicked
         * 
         * Adapted from https://blog.logrocket.com/build-accessible-user-interface-tabs-javascript/
         * 
         * @param jQuery object element The active tab
         */
        function showActivePanel( tab ) {
            //Retrieve the tab panel controlled by this tab
            var curCls         = "current",
                tabId          = tab.attr( "id" ),
                activeTabpanel = $( "[aria-labelledby=\"" + tabId + "\"]" ).removeClass( curCls ),
                firstSubtab;
                
            //Hide all siblings tabpanels
            activeTabpanel.siblings( "[role=tabpanel]" ).each( function () {
                $( this ).removeClass( curCls );
            });

            //Show the tab panel controlled by this tab
            activeTabpanel.addClass( curCls ); 
            
            if ( ! objHasOwn( tabCache, tabId ) && isActiveTab( tab ) ) {
                fetchTabSchema( tab );
            } else {
                if ( activeTabs.includes( tab.data( "tab" ) ) ) {
                    firstSubtab = activeTabpanel.find( "[data-parenttab]" ).eq( 0 );
                    
                    if ( firstSubtab.length ) {
                        describr.displaySPAContent( firstSubtab );
                        determineTabindex( firstSubtab );
                        return;
                    }
                }

                determineTabindex( tab );
            }
        }
        
        describr.displaySPAContent = function( tab ) {
            showActivePanel( tab );
            setSelectedTab( tab );
        };

        describr.displaySPAPagingContent = function( specs ) {
            var reg   = /__paging=([0-9]+)$/,
                paged = specs.match( reg )[1],
                tab   = specs.replace( reg, "" );

            fetchTabSchema( tab, { "paged" : paged } );
        };

        $( ".describr .post-page-numbers.current" ).attr( "tabindex", "0" );
    };//profile.events

    /**
     * Updates profile photo accessibility text
     * 
     * @param string msg Accessibility text
     */
    function newUpdateStatus( msg ) {
        container.find( "[role=status]" ).text( msg );
    }
        
    /**
     * Augments the native <select> element with jQuery selectmenu widget
     * 
     * @param string menu The <select> ID
     */
    function initJquerySelectmenu( menu ) {
        var menu1 = $( "#" + menu );

        if ( ! selectmenus.includes( menu ) ) {
            selectmenus.push( menu );
        } else if ( menu1.selectmenu( "instance" ) ) {
            menu1.selectmenu( "destroy" );
        }

        menu1.selectmenu({
            position : { "my" : "left top", "at" : "left bottom", "collision" : "flip" },
            create : function ( event, ui ) {
                var select         = $( this ),
                    data           = select.data(),
                    action         = data.editaction,
                    label          = $( "[data-for=\"" + select.attr( "id" ) + "\"]" ).length ? $( "[data-for=\"" + select.attr( "id" ) + "\"]" ).text().replace( new RegExp( escRegex( describrL10n.requiredSymbol ) + "$" ), "" ) : select.attr( "aria-label" ),
                    widget         = select.selectmenu( "widget" ),
                    btn            = widget.find( ".ui-selectmenu-text" ),
                    selectedOption = select.find( "option:selected" ),
                    selectedText   = selectedOption.text().trim(),
                    selected       = selectedOption.val().trim();
                    
                if ( "social_network" === action ) {
                    label = $( "[data-for=\"describr-profile-select-social-network\"]" ).text();
                }
                
                widget.attr( "aria-label", label );

                //Display previously selected value in combobox
                if ( "country" === action && selected.length ) {
                    btn.html( describr.settings.tel.renderCountryBtn( selected ) );
                } else if ( "social_network" === action && selected.length ) {
                    var icon = fieldSetting( selected, "icon" );
                    
                    if ( icon ) {
                        icon = "<span aria-hidden=\"true\" class=\"" + icon + "\"></span> ";
                    }

                    btn.html( icon + selectedText );
                } else if ( "gender" === action && selected.length ) {
                    btn.html( "<span class=\"" + fieldSettings( action ).icons[selected] + "\"></span> " + selectedText );
                } else {
                    btn.text( selectedText );
                }                    
                
                select.selectmenu("instance")._renderMenu = function( ul, items ) {
                    var that = this;               
                                                              
                    $.each( items, function( index, item ) {
                        that._renderItemData( ul, item );
                    });                    

                    ul.attr( "aria-label", label ).css( "max-height", "250px" );
                };

                select.selectmenu("instance")._renderItem = function( ul, item ) {
                    var li   = $( "<li>", { role : "option", "class" : "describr" } ),
                        val  = item.value,
                        icon = "";
                    
                    if ( val.length ) {
                        if ( "social_network" === action ) {
                            icon = fieldSetting( val, "icon" );
                        } else if ( "_privacy" === action ) {
                            icon = describr.profile.changes.settings[action].icons[val];
                        } else if ( "_status" === action ) {
                            icon = describr.profile.changes.settings[action].icons[val];
                        } else if ( "gender" === action ) {
                            icon = fieldSettings( action ).icons[val]
                        } else if ( "country" === action ) {
                            icon = describr.settings.tel.countryFlag( val );
                        }
                    }   
                    
                    if ( icon ) {
                        icon = "<span aria-hidden=\"true\" class=\"" + icon + "\"></span> ";
                    }  
                    
                    return li.append( $( "<div>", { html : icon + item.label } ) ).appendTo( ul );
                };
            },
            change : function ( event, ui ) {
                var select        = $( this ),
                    selectName    = select.data( "name" ),
                    selectAction  = select.data( "editaction" ),
                    selectedValue = ui.item.value.trim(),
                    selectedText  = ui.item.label.trim(),
                    btn           = select.selectmenu( "widget" ).find( ".ui-selectmenu-text" );
                
                if ( this.name ) {
                    if ( objHasOwn( describr.profile.changes, "settings" ) && objHasOwn( describr.profile.changes.settings, this.name ) ) {
                        describr.profile.changes.settings[this.name].isCustom = false;
                    }

                    toggleFieldError( this.name );
                }

                if ( "country" === selectAction && selectedValue.length ) {
                    btn.html( describr.settings.tel.renderCountryBtn( selectedValue ) );
                } else if ( "gender" === selectAction && selectedValue.length ) {
                    btn.html( "<span class=\"" + fieldSettings( selectAction ).icons[selectedValue] + "\"></span> " + selectedText );
                } else {
                    btn.text( selectedText );
                }
                
                select.data( "value", selectedValue );

                if ( "display_name" === selectAction ) {
                    changeDisplayName( selectedValue, select );
                } else if ( "social_network" === selectAction && selectedValue.length ) {
                    showSocialModal( select, selectedValue );
                    
                    if ( select.selectmenu( "instance" ) ) {
                        select.selectmenu( "destroy" );
                    }
                }
            }
        });
    }

    /**
     * Adds jQuery autocomplete ui to relevant textboxes
     * 
     * @since 2.0
     * 
     * @param {string} id The textbox ID
     */
    function initJqueryAutocomplete( textboxId ) {
        var menu = $( "#" + textboxId ), 
            delay_ = /(city|hometown|locales)$/.test( textboxId ) ? 500 : 2000,
            searchTerm, 
            autocompleteInstance; 

        if ( menu.autocomplete( "instance" ) ) {
            menu.autocomplete( "destroy" );
        }
        
        autocompleteInstance = menu.autocomplete({
            minLength : 1,
            delay : delay_,
            autoFocus : true,
            position : { "my" : "left top", "at" : "left bottom", "collision" : "flip" },
            create: function( event, ui ) {
                var widget        = $(this).data( "ui-autocomplete" ),
                    ul            = widget.menu.element,
                    positionProps = $.extend( { "of" : widget.element }, widget.options.position );

                //Reposition autocomplete dropdown when window resizes
                $( window ).on( "resize", function() {
                    ul.position( positionProps );
                });
            },
            source : function ( request, response ) {
                var elem = $( this.element ).removeClass( "ui-autocomplete-loading" ), partnerQuery, langs = [], j, k;
                searchTerm = request.term.trim();

                if ( ! searchTerm ) {
                    response([]);
                    return;
                }

                if ( "partner" === elem.data( "editaction" ) ) {
                    partnerQuery = {
                        profile_nonce : nonces.editProfile,
                        user_id       : userId
                    };
                        
                    partnerQuery.q = searchTerm;
                    partnerQuery[checkKey] = "";

                    describr.ajax.post( "describr-partner-query", partnerQuery ).then( function( res ) {
                        response( res );
                    },
                    function( res ) {
                        response([]);
                        fieldEditNotice( res, true );
                    });
                } else if ( "locales" === elem.data( "editaction" ) ) {
                    for ( j in describr.settings.locales ) {
                        k = describr.settings.locales[j];
                        
                        if ( 0 === k.toLocaleLowerCase( blogLocale ).indexOf( searchTerm.toLocaleLowerCase( blogLocale ) ) ) {
                            langs.push( { "label" : k, "value" : j } );
                        }
                    }

                    response( sliceSuggests( langs ) );
                } else {
                    response( suggestCities( searchTerm ) );
                }
            },//source
            //Fires onblur
            change : function () {
                var elem = $( this );
                ///Clear the user ID returned from the server if the user inputs another partner's name                      
                if ( "partner" === elem.data( "editaction" ) && elem.val().trim() !== elem.data( "value" ) ) {
                    $( "#" + elem.attr( "id" ).replace( "-partner", "-partner_id" ) ).val( "" );
                }
            },
            close : function () {//close: This event is triggered whenever the autocomplete menu closes
                noexpand( $( this ) );
            },
            open() {
                expand( $( this ) );
            },
            select : function ( event, ui ) {
                var elem     = $( this ),
                    parent   = elem.parent(),
                    savedVal = elem.data( "value" ),
                    label    = ui.item.label,
                    val      = ui.item.value,
                    grid;
                
                if ( "locales" === elem.data( "editaction" ) ) {
                    event.preventDefault();

                    if ( savedVal ) {
                        savedVal = savedVal.split( "," );
                    } else {
                        savedVal = [];
                    }
                            
                    if ( ! savedVal.includes( val ) ) {
                        savedVal.push( val  );//Add the code to the language-code array
                        
                        elem.val("").data( "value", savedVal.join( "," ) );
                                  
                        //Selected languages are shown in grid format, so we attach the new gridcell
                        grid = parent.find( "span[role=grid]" );
                                        
                        if ( grid.length ) {
                            grid.find( "span[role=row]" ).append( describr.profile.changes.langs.gridcell( val ) );
                        } else {
                            parent.prepend( describr.profile.changes.langs.grid( val ) );
                        }

                        describr.profile.changes.langs.jsUpdate();
                    }
                } else if ( "partner" === elem.data( "editaction" ) ) {
                    elem.data( "value", val );

                    if ( ui.item.userId ) {
                        $( "#" + elem.attr( "id" ).replace( "-partner", "-partner_id" ) ).val( ui.item.userId );
                    }
                } else {
                    elem.data( "value", val );
                }    
            }
        }).on( "focus", function () {
            $(this).autocomplete("search");
        });
        autocompleteInstance.data("ui-autocomplete")._renderMenu = function( ul, items ) {
            var that = this;

            $.each( items, function( index, item ) {
                that._renderItemData( ul, item );
            });
                
            $( ul ).attr({ 
                "role"       : "listbox", 
                "aria-label" : $( "[for=\"" + $( that.element ).attr( "id" ) + "\"]" ).text(),
                "id"         : $( that.element ).attr( "id" ) + "-listbox"
            }).addClass( "describr" );
        };
        autocompleteInstance.data("ui-autocomplete")._resizeMenu = function () {
            var $input   = $(this.element),
                maxWidth = "locales" === $input.data( "editaction" ) ? "252.641px" : $input.outerWidth();
            this.menu.element.css( "max-width", maxWidth );
        };
        autocompleteInstance.data("ui-autocomplete")._renderItem = function ( ul, item ) {
            var regEx = new RegExp( "^(" + $.ui.autocomplete.escapeRegex(this.term.trim()) + ")", "i" );
            return $( "<li>", { "role" : "option" } )
                .append( "<div>" + item.label.replace( regEx, "<strong>$1</strong>" ) + "</div>" )
                .appendTo( ul );
        };
    }

    function blockUserErr( message, btn ) {
        var bottom = parseInt( btn.css( "bottom" ), 10 ) + btn.outerHeight();
        $( ".describr-notice" ).not( ".static" ).remove();
        btn.before( "<div class=\"describr-notice describr-notice-error popup is-dismissible\" role=\"alert\" style=\"bottom:" + bottom + "px;\"><p>" + message + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" );
        removeNotice();
    }
        
    function updateSubmitButton( isUpdating, isAside ) {
        var submitButton = $( "#describr-profile-" + ( isAside ? "select-aside" : "field-editor" ) + "-modal-wrap" ).find( "button[type=submit]" );

        if ( isUpdating ) {
            submitButton.text( describrL10n.updating ).prop( "disabled", true );
        } else {
            submitButton.text( describrL10n.update ).prop( "disabled", false );
        }
    }
    
    function updateRatePictureButton( isUpdating ) {
        var submitButton = $( "#describr-profile-picture-rating-modal-wrap" ).find( "button[type=submit]" );

        if ( isUpdating ) {
            submitButton.text( describrL10n.photo.rating.rating ).prop( "disabled", true );
        } else {
            submitButton.text( describrL10n.photo.rating.rate ).prop( "disabled", false );
        }
    }
    
    function setAutocompleteMenuHeight( input$ ) {
        var d      = 0.75,
            parent = input$.parent(),
            offset = parent.offset(),
            top    = offset.top + parent.outerHeight() + d,
            left   = offset.left - d,
            menu$  = $( "#" + parent.attr( "id" ) + "-listbox" );

        if ( menu$.length ) {
            menu$.css( { "top" : top, "left" : left } );
        }
    }

    function showSocialModal( select, selectedValue ) {
        select.closest( "[role=dialog]" ).find( ".describr-btn-cancel" ).trigger( "describr_click" );
        describr.profile.changes.showEditModal( selectedValue );
    }
    
    /**
     * Suggests cities using user's search term
     * 
     * @param {string} searchTerm Search term
     * @return {array} An array of cities matching the search term
     */
    function suggestCities( searchTerm ) {
        var cities = getCities( searchTerm );

        if ( cities.length ) {
            cities = cities.sort( cityCompare ).map( function ( item ) {
                if ( objHasOwn( item, "city" ) ) {
                    return item.city + describrL10n.listItemSeparator + item.state;
                } else {
                    return item.state + describrL10n.listItemSeparator + item.countryName;
                }
            });
        }

        return cities;
    }

    /**
     * Suggests cities using keywords
     * 
     * @param {array}  searchTerm  Search term
     * @return An array of cities matching the search term
     */
    function getCities( searchTerm ) {
        var citiesFound = [], countryCode;
        
        searchTerm = searchTerm.replace( new RegExp( "(" + escRegex( describrL10n.listItemSeparator ) + ")", "g" ), " " ).replace( /\s+/g, " " ).trim();
        
        if ( ! searchTerm ) {
            return citiesFound;
        }
        
        searchTerm = searchTerm.toLocaleLowerCase( blogLocale );

        ( describrStatesCities || [] ).forEach( function ( countries ) {
            var country, countryName, states, state, stateName, cities;

            for ( countryCode in countries ) {
                country     = countries[countryCode]; 
                countryName = getCountry( countryCode );
                for ( states in country ) {
                    state     = country[states];
                    stateName = state.name.trim();
                    cities    = state.cities || [];
                    
                    if ( cities.length ) {
                        cities.forEach( function ( cityName ) {
                            cityName = cityName.trim();
                            if ( 0 === (cityName + " " + stateName + " " + countryName).toLocaleLowerCase( blogLocale ).indexOf( searchTerm ) ) {
                                citiesFound.push({ 
                                    "city"        : cityName, 
                                    "state"       : stateName,
                                    "countryCode" : countryCode,
                                    "countryName" : countryName                                
                                });
                            }
                        });
                    } else if ( 0 === (stateName + " " + countryName).toLocaleLowerCase( blogLocale ).indexOf( searchTerm ) ) {
                        citiesFound.push({ 
                            "state"       : stateName,
                            "countryCode" : countryCode,
                            "countryName" : countryName                                
                        });
                    }
                }
            }
        });
        
        return sliceSuggests( citiesFound );
    }
    
    /**
     * Retrieves the name of country identified by a country code
     * 
     * @param string Country code
     * @return string Country
     */
    function getCountry( countryCode ) {
        return ( objHasOwn( describr.settings.choice.country, countryCode ) ? describr.settings.choice.country[countryCode] : "" ).trim();
    }

    /**
     * Compare function to sort cities in ascending order
     *
     * @return int
     */
    function cityCompare( a, b ) {
        var boston   = describrL10n.specialCities.boston,
            kingston = describrL10n.specialCities.kingston,
            santoDom = describrL10n.specialCities.santoDomingo,
            city1    = a.city || a.state,
            city2    = b.city || b.state,
            order;
        
        //Test for locale support
        if ( localeCompareSupportsLocale ) {
            order = city1.localeCompare( city2, blogLocale );
        } else {
            //Use without locale support
            order = city1.localeCompare( city2 );
        }

        if ( 0 === city1.indexOf( santoDom ) && 0 === city2.indexOf( santoDom ) && "DO" == a.countryCode && "DO" == b.countryCode ) {
            order = -1;
        } else if ( 0 === city1.indexOf( kingston ) && 0 === city2.indexOf( kingston ) && "JM" == a.countryCode && "JM" == b.countryCode ) {
            order = -1;
        }

        if ( boston === city2 && "PH" === b.countryCode  ) {
            order = -1;
        } 

        if ( 0 === city1.indexOf( boston ) && boston !== city1 ) {
            order = 1;
        } 
            
        if ( 0 < order ) {
            order = 1;
        } else if ( 0 > order ) {
            order = -1;
        }

        return order;
    }

    function sliceSuggests( suggest ) {
        if ( 20 < suggest.length ) {
            return suggest.slice( 0, 10 );
        }

        return suggest;
    }

    /**
     * Checks browser support for extended arguments in `localecompare()`
     * 
     * Courtesy of https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/localeCompare
     * 
     * @return bool
     */
    function localeCompareSupportsLocales() {
        try {
            "foo".localeCompare( "bar", "i" );
        } catch (e) {
            return e.name === "RangeError";
        }

        return false;
    }
    
    /**
     * Adds field's markup to the modal
     * 
     * @param {object} settings Field settings
     * @param {bool}   _return  Whether to return the markup
     * @return string
     */
    function addFieldToModal( settings, _return ) {
        return describr.profile.changes.addFieldToModal( settings, _return );
    }
    
    /**
     * Helper function for `addFieldToModal()` that prepares field's data
     * 
     * @param {object} settings Field settings
     * @return object
     */
    function addFieldToModal_( settings ) {
        return describr.profile.changes.addFieldToModal_( settings );
    }
    
    /**
     * Adds jQuery ui widget to a field
     * 
     * @param {string} helper_ Type of widget: menu, autocomplete
     * @param {string} name_   HTML name attribute's value for the field
     */
    function addHelperToDom( helper_, name_ ) {
        return describr.profile.changes.addHelperToDom( helper_, name_ );
    }

    /**
     * Removes a field editor (for example, an input element and all associated elements)
     * 
     * @param {int} id ID of the editor
     */
    function removeFieldEditor( id ) {
        describr.profile.changes.removeFieldEditor( id );
    }

    function toggleFieldError( _name, isErr ) {
        describr.profile.changes.toggleFieldError( _name, isErr );
    }

    function removeBaseUrl( baseUrl, url ) {
        var i        = 0, 
            path     = url, 
            baseUrl_ = baseUrl,//String or array
            regEx;

        if ( ! Array.isArray( baseUrl ) ) {
            baseUrl = [baseUrl];
        } else{
            baseUrl_ = baseUrl[0]; 
        }
        
        while ( i < baseUrl.length ) {
            baseUrl_ = baseUrl[i];
            regEx = new RegExp( "^(" + escRegex( baseUrl_ ) + ")" , "i" );
            path = path.replace( regEx, "" );

            if ( path !== url ) {
                break;
            }

            i++;
        }

        return { "baseUrl" : baseUrl_, "path" : path };
    }

    /**
     * Creates variable phone extension lengths
     * 
     * Adapted from the {@see "PhoneNumberUtil"} class
     * of the `libphonenumber-for-php-lite` library
     * 
     * @see https://github.com/giggsey/libphonenumber-for-php-lite
     * 
     * @var int maxLength Max length for extension
     * @return string Extension subpattern
     */
    function extnDigits( maxLength ) {
        return "([" + DIGITS + "]{1," + maxLength + "})";
    }
    
    /**
     * Returns phone extension regex
     * 
     * @return RegExp
     */
    function createExtnPattern() {
        var extnPattern = extnDigits(extLimitAfterExplicitLabel)
            + "|" + extnDigits(extLimitAfterExplicitLabel) 
            + optionalExtnSuffix +  "|"
            + extnDigits(extLimitAfterAmbiguousChar) 
            + optionalExtnSuffix + "|"
            + extnDigits(extLimitAfterLikelyLabel) + "|"
            + extnDigits(extLimitWhenNotSure) + "#";
        new RegExp( "^(" + extnPattern + ")$" );
    }

    /**
     * Builds regex to validate phone numbers
     * 
     * Adapted from the {@see "PhoneNumberUtil"} class
     * of the `libphonenumber-for-php-lite` library
     * 
     * @see https://github.com/giggsey/libphonenumber-for-php-lite
     * 
     * @return Regex for validating phone numbers
     */
    function phoneNumberPatterns() {
        var possibleSeparatorsBetweenNumberAndExtLabel = "[ \xC2\xA0\\t,]*";
        //Optional full stop (.) or colon, followed by zero or more spaces/tabs/commas.
        var possibleCharsAfterExtLabel = "[:\\.\xEf\xBC\x8E]?[ \xC2\xA0\\t,-]*";
        
        //Here the extension is called out in more explicit way, i.e mentioning it obvious patterns
        //like 'ext.'. Canonical-equivalence doesn't seem to be an option with Android java, so we
        //allow two options for representing the accented o - the character itself, and one in the
        //unicode decomposed form with the combining acute accent.
        var explicitExtLabels = "(?:e?xt(?:ensi(?:o\xCC\x81?|\xC3\xB3))?n?|\xEF\xBD\x85?\xEF\xBD\x98\xEF\xBD\x94\xEF\xBD\x8E?|\xD0\xB4\xD0\xBE\xD0\xB1|anexo)";
        //One-character symbols that can be used to indicate an extension, and less commonly used
        //or more ambiguous extension labels.
        var ambiguousExtLabels = "(?:[x\xEF\xBD\x98#\xEF\xBC\x83~\xEF\xBD\x9E]|int|\xEF\xBD\x89\xEF\xBD\x8E\xEF\xBD\x94)";
        //When extension is not separated clearly.
        var ambiguousSeparator = "[- ]+";

        var rfcExtn = ";ext=" + extnDigits(extLimitAfterExplicitLabel);
        var explicitExtn = possibleSeparatorsBetweenNumberAndExtLabel + explicitExtLabels
            + possibleCharsAfterExtLabel + extnDigits(extLimitAfterExplicitLabel)
            + optionalExtnSuffix;
        var ambiguousExtn = possibleSeparatorsBetweenNumberAndExtLabel + ambiguousExtLabels
            + possibleCharsAfterExtLabel + extnDigits(extLimitAfterAmbiguousChar) + optionalExtnSuffix;
        var americanStyleExtnWithSuffix = ambiguousSeparator + extnDigits(extLimitWhenNotSure) + "#";

        //The first regular expression covers RFC 3966 format, where the extension is added using
        //\";ext=\". The second more generic where extension is mentioned with explicit labels like
        //\"ext:\". In both the above cases we allow more numbers in extension than any other extension
        //labels. The third one captures when single character extension labels or less commonly used
        //labels are used. In such cases we capture fewer extension digits in order to reduce the
        //chance of falsely interpreting two numbers beside each other as a number + extension. The
        //fourth one covers the special case of American numbers where the extension is written with a
        //hash at the end, such as \"- 503#\".
        var extensionPattern =
            rfcExtn + "|"
            + explicitExtn + "|"
            + ambiguousExtn + "|"
            + americanStyleExtnWithSuffix;
        //This is same as possibleSeparatorsBetweenNumberAndExtLabel, but not matching comma as
        //extension label may have it.
        var possibleSeparatorsNumberExtLabelNoComma = "[ \xC2\xA0\\t]*";
        //\",,\" is commonly used for auto dialling the extension when connected. First comma is matched
        //through possibleSeparatorsBetweenNumberAndExtLabel, so we do not repeat it here. Semi-colon
        //works in Iphone and Android also to pop up a button with the extension number following.
        var autoDiallingAndExtLabelsFound = "(?:,{2}|;)";

        var autoDiallingExtn = possibleSeparatorsNumberExtLabelNoComma
                + autoDiallingAndExtLabelsFound + possibleCharsAfterExtLabel
                + extnDigits(extLimitAfterLikelyLabel) + optionalExtnSuffix;
        var onlyCommasExtn = possibleSeparatorsNumberExtLabelNoComma
                + "(?:,)+" + possibleCharsAfterExtLabel + extnDigits(extLimitAfterAmbiguousChar)
                + optionalExtnSuffix;
        //Here the first pattern is exclusively for extension autodialling formats which are used
        //when dialling and in this case we accept longer extensions. However, the second pattern
        //is more liberal on the number of commas that acts as extension labels, so we have a strict
        //cap on the number of digits in such extensions.
        extensionPattern = extensionPattern + "|"
                + autoDiallingExtn + "|"
                + onlyCommasExtn;
        
        //The minimum length of the national significant number.
        var minLengthForNSN = 2;
        //The plus sign signifies the international prefix.
        var plusChars = "+＋";
        //Regular expression of acceptable punctuation found in phone numbers, used to find numbers in
        //text and to decide what is a viable phone number. This excludes diallable characters.
        //This consists of dash characters, white space characters, full stops, slashes,
        //square brackets, parentheses and tildes. It also includes the letter "x" as that is found as a
        //placeholder for carrier information in some phone numbers. Full-width variants are also
        //present.
        var validPunctuation = "-x\xE2\x80\x90-\xE2\x80\x95\xE2\x88\x92\xE3\x83\xBC\xEF\xBC\x8D-\xEF\xBC\x8F \xC2\xA0\xC2\xAD\xE2\x80\x8B\xE2\x81\xA0\xE3\x80\x80()\xEF\xBC\x88\xEF\xBC\x89\xEF\xBC\xBB\xEF\xBC\xBD.\\[\\]/~\xE2\x81\x93\xE2\x88\xBC";
        var starSign = "*";
        var minLengthPhoneNumberPattern = "[" + DIGITS + "]{" + minLengthForNSN + "}";
        var validPhoneNumber = "[" + plusChars + "]*(?:[" + validPunctuation + starSign + "]*[" + DIGITS + "]){3,}[" + validPunctuation + starSign + DIGITS + "]*";
        return new RegExp( "^" + minLengthPhoneNumberPattern + "$|^" + validPhoneNumber + "(?:" + extensionPattern + ")?$", "i" );
    }
    
    function socialNetworks() {
        if ( objHasOwn( describr.settings, "editableSocials" ) ) {
            return describr.settings.editableSocials;
        }

        return [];
    }

    function isSocialNetwork( field ) {
        return socialNetworks().includes( field );
    }

    function updateProfileFields( field, hash ) {
        if ( hash ) {
            if ( objHasOwn( describr.profile.fields, field ) && isObject( describr.profile.fields[field] ) ) {
                describr.profile.fields[field] = removeObjProp( describr.profile.fields[field], hash );

                if ( ! Object.keys( describr.profile.fields[field] ).length ) {
                    updateProfileFields( field );
                }
            }            
        } else {
            describr.profile.fields = removeObjProp( describr.profile.fields, field );
        }
    }
    
    function removeObjProp( curObj, prop ) {
        var newObj = {}, n;

        for ( n in curObj ) {
            if ( n !== prop ) {
                newObj[n] = curObj[n];
            }
        }

        return newObj;
    }
    
    function fieldEditNotice( message, isErr ) {
        var wrap = $( "#describr-profile-field-editor-modal" );
        wrap.find( ".describr-notice" ).not( ".static" ).remove();
        wrap.prepend( "<div class=\"describr-notice describr-notice-" + ( isErr ? "error" : "success" ) + " is-dismissible\" role=\"alert\"><p>" + message + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" );
        removeNotice();
    }
    
    function removeNotice() {
        clearTimeout( removeNoticeTimer );
        removeNoticeTimer = setTimeout(function() {
                $( ".describr-notice" ).not( ".static" ).fadeOut( 100, "linear", function () {
                    $(this).remove();
                    $( "#describr-popup-center" ).hide();
                });
            },
            7*1000
        );
    }
    
    function closeModalPostUpdate( form_ ) {
        form_.closest( "[role=dialog]" ).find( ".describr-btn-cancel" ).trigger( "describr_click" );
    }

    function trim_( str ) {
        if ( ! isString( str ) && "number" !== typeof str ) {
            throw new TypeError( "Expected \"str\" to be a string, but received " + typeof str + "." );
        }
        
        return ( "" + str ).trim();

    }
    
    function trimDot( str ) {
        return ( "" + str ).replace( /^\.+|\.+$/, "" );
    }

    function failAjaxMsg( msg ) {
        return "_fail_" === msg ? describrL10n.failReq : msg;
    }

    function centerAlert( message, notErr ) {
        $( "#describr-popup-center" ).html( "<div class=\"describr-notice describr-notice-" + ( notErr ? "success" : "error" ) + " is-dismissible\" role=\"alert\"><p>" + message + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" ).show();
        removeNotice();
    }

    function requiredIcon() {
        return "<span class=\"describr-required\">" + describrL10n.requiredSymbol + "</span>";
    }
    
    /**
     * Sets asides error notices
     * 
     * @param {string} message Error message
     */
    function asidesErrNotice( message ) {
        var wrap = $( "#describr-profile-select-aside-modal" );
        wrap.find( ".describr-notice" ).not( ".static" ).remove();
        wrap.prepend( "<div class=\"describr-notice describr-notice-error is-dismissible\" role=\"alert\"><p>" + message + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" );
        removeNotice();
    }
    
    /**
     * Applies global screen-reader-specific alerts
     * 
     * @param {string} message Alert message
     */
    function globalScreenReaderAlertNotice( message ) {
        var elem = $( "#describr-global-screen-reader-alert-text" );

        if ( "alert" !== elem.attr( "role" ) ) {
            elem.attr( "role", "alert" );
        }

        elem.text( message );
    }

    function messageFieldErr( message ) {
        var errMsgElemId = messageTextareaHtmlId + "-error",
            textAreaAttr = { "aria-invalid" : "true", "aria-errormessage" : errMsgElemId };

        $( "#" + errMsgElemId ).show().text( message );

        if ( isTinyMCEMessageEnabled ) {
            $( "#" + tinyMCEMessageHtmlId + "_ifr" ).addClass( "describr-invalid" );
                
            tinymce.get( tinyMCEMessageHtmlId ).getBody().setAttribute( "aria-invalid", "true" );
            tinymce.get( tinyMCEMessageHtmlId ).getBody().setAttribute( "aria-errormessage", errMsgElemId );
                
            tinyMCEMessageTextarea.attr( textAreaAttr );
        } else {
            $( "#" + messageTextareaHtmlId ).attr( textAreaAttr );
        }
    }
    
    /**
     * Sets message error notices
     * 
     * @param {string} message Error message
     */
    function messageErrNotice( message ) {
        var wrap = $( "#describr-profile-message-modal" );
        wrap.find( ".describr-notice" ).not( ".static" ).remove();
        wrap.prepend( "<div class=\"describr-notice describr-notice-error is-dismissible\" role=\"alert\"><p>" + message + "</p><button type=\"button\" class=\"describr-notice-dismiss\" aria-label=\"" + describrL10n.dismiss + "\"></button></div>" );
        removeNotice();
    }

    //Removes profile picture
    function removePicture() {
        globalScreenReaderAlertNotice( describrL10n.picture.deleting );
        
        describr.ajax.post( "describr-remove-picture", {
            photo_id            : fieldSetting( pictureKey, "id" ),
            user_id             : userId, 
            profile_photo_nonce : nonces.deletePicture
        }).done( function( response ) {
            updateDOMPicture( response );

            globalScreenReaderAlertNotice( describrL10n.picture.deleted );

            describr.settings.fieldsToEdit[pictureKey].id = 0;
            
            prepReturnedServerMarkup( response );

            $( "#describr-profile-picture-modal-wrapper" ).hide();
        }).fail( function( response ) {
            centerAlert( response.message );

            $( "#describr-profile-picture-modal-wrapper" ).hide();
        });
    }

    function updateDOMPicture( response ) {
        pictureWrap.find( "img" ).remove();
        pictureWrap.prepend( response.img );
    }
    
    /**
     * Updates fields markup and values
     * 
     * @param object data Data from server
     */
    function prepReturnedServerMarkup( data ) {
        var isObj     = isObject( data ),
            newRaw    = isObj && objHasOwn( data, "raw" ) ? data.raw : false,
            newMarkup = isObj && objHasOwn( data, "markup" ) ? data.markup : false,
            newAsides = isObj && objHasOwn( data, "savedAsides" ) ? data.savedAsides : false, 
            fieldData = isObj && objHasOwn( data, "field" ) ? data.field : false,
            fieldHash = fieldData ? fieldData.hash : false,
            fieldKey  = fieldData ? fieldData.key_ : false;
            
        describr.profile.mode = isObj && objHasOwn( data, "mode" ) ? data.mode : false;

        if ( isObj && objHasOwn( data, "accessibleFields" ) && isObject( data.accessibleFields ) ) {
            accessibleFields = $.extend( true, accessibleFields, data.accessibleFields );
        }
            
        if ( newAsides ) {
            savedAsides = $.extend( true, savedAsides, newAsides );
            updateSavedAsidesByCategory( newAsides );
        }

        if ( newRaw ) {
            describr.profile.fields = $.extend( true, describr.profile.fields, newRaw );
                
            //A field's subfield was deleted
            if ( fieldHash && objHasOwn( describr.profile.fields, fieldKey ) && isObject( describr.profile.fields[fieldKey] ) ) {
                describr.profile.fields[fieldKey] = removeObjProp( describr.profile.fields[fieldKey], fieldHash );
            }
        }
        
        //Field data was deleted  
        if ( fieldKey ) {
            //Field stop updating
            fieldUpdated( fieldKey, fieldHash );
        }

        switch ( describr.profile.mode ) {
            case "building":
                if (  isString( newMarkup ) && ! empty( newMarkup ) ) {
                    describr.profile.markupWrap.html( newMarkup );
                }
                break;
            case "deleting":
            case "editing":
                var field, fieldHtml, markup;

                if ( newMarkup && isObject( newMarkup ) ) {
                    for ( field in newMarkup ) {
                        markup = newMarkup[field].join( "\n" );

                        fieldHtml = $( ".describr [data-mainfield=\"" + field + "\"]" );
                            
                        if ( ! fieldHtml.length ) {
                            if ( isSocialNetwork( field ) ) {
                                $( ".describr [data-mainfield=social_network]" ).before( markup );
                            }

                            continue;
                        }

                        fieldHtml.hide();
                        fieldHtml.last().after( markup );
                        fieldHtml.remove();
                    }
                } else if ( fieldKey ) {
                    $( ".describr [data-mainfield=\"" + fieldKey + "\"]" ).remove();
                }

                fieldHash && $( ".describr [data-hash=\"" + fieldHash + "\"]" ).remove();
                break;
        }
           
        describr.profile.mode = false;          
    }

    function addFieldError( id, message, settings ) {
        if ( isObject( settings ) && settings.isCustom ) {
            id += "-custom";
        }

        var errElem = $( "#" + id + "-error" );

        if ( "alert" !== errElem.attr( "role" ) ) {
            errElem.attr( "role", "alert" );
        }
        
        errElem.show().html( Array.isArray( message ) ? message.join( "<br />" ) : message );

        if ( settings ) {
            toggleFieldError( settings, true );
        }
    }

    function closeModals() {
        $( ".describr" ).each( function() {
            $( this ).find( "[role=dialog]" ).each( function() {
                $( this ).parent().hide();
            });
        });
    }
    
    function profileFieldSavedValue( field ) {
        if ( isObject( describr.profile.fields ) && objHasOwn( describr.profile.fields, field ) ) {
            return describr.profile.fields[ field ];
        } else {
            return "";
        }
    }
    
    /**
     * Toggles the display name field between options and custom
     * 
     * @string {string}        selectedValue Selected value
     * @param  {jquery object} select        Select element
     */
    function changeDisplayName( selectedValue, select ) {
        var name_               = "display_name",
            displayNameSettings = $.extend( true, {}, fieldSettings( name_ ) ),
            isRequired          = displayNameSettings.required,
            requiredIco         = requiredIcon(),
            labelElem           = $( "[data-for=\"" + select.attr( "id" ) + "\"]" ),
            id_                 = select.attr( "id" ) + "-custom",
            label               = labelElem.html();

        if ( ! selectedValue.length ) {
            describr.profile.changes.settings[name_].isCustom = true;
                        
            displayNameSettings.isCustom = true;
                        
            select.parent().after( addFieldToModal( addFieldToModal_( displayNameSettings ), true ) );
                    
            $( "#" + id_ )[0].focus();
                        
            if ( isRequired ) {
                select.removeAttr( "aria-required" ).prop( "required", false );
                labelElem.html( label.replace( requiredIco, "" ) );
            }
        } else {
            describr.profile.changes.settings[name_].isCustom = false;
            removeFieldEditor( id_ );

            if ( isRequired ) {
                select.attr( "aria-required", "true" ).prop( "required", true );
                            
                labelElem.html( label.replace( requiredIco, "" ) + requiredIco );
            }
        }
    }

    function isString( str ) {
        return "string" === typeof str;
    }
    
    /**
     * Retrieves whether the native select element
     * is not being used
     * 
     * @param {jquery object} elem Select element
     * @return bool
     */
    function isNotUsingSelect( elem ) {
        return useJqueryUi && ! elem.is( ":visible" );
    }

    /**
     * Prepends UTC offset with "UTC", making it
     * selectable
     * 
     * @param {string] tzStr Time zone
     * @return Time zone prepended with "UTC"
     */
    function selectableUTC( tzStr ) {
        if ( ! /^[.0-9-]+$/.test( tzStr ) ) {
            return tzStr;
        }
        
        tzStr = parseFloat( tzStr );

        if ( 0 === tzStr ) {
            tzStr = "UTC+0";
        } else if ( tzStr < 0 ) {
            tzStr = "UTC" + tzStr;
        } else {
            tzStr = "UTC+" + tzStr;
        }

        return tzStr;
    }
    
    /**
     * Retrieves the new value being submitted by user
     * 
     * @param {object} Modal Field settings
     * @return string The submitting value
     */
    function updatingFieldValue( settings ) {
        var elem = $( "#" + settings.id ), val;
        
        if ( settings.isTinyMCE ) {
            val = tinymce.get( settings.id ).getContent();
        } else if ( settings.isCustom ) {
            val = $( "#" + settings.id + "-custom" ).val();
        } else if ( settings.hasJqueryHelper ) {
            if ( "menu" === settings.hasJqueryHelper ) {
                val = elem.data( "value" );
            } else {
                var name_ = settings.name_;

                if ( "locales" === name_ ) {
                    val = elem.data( "value" );
                } else {
                    val = elem.val().trim();

                    if ( ! empty( val ) ) {
                        val = elem.data( "value" );
                    
                        //The user doesn't have to select a partner, so default to the text box's value if no value has been selected
                        if ( empty( val ) && "partner" === name_ ) {
                            val = elem.val();
                        }
                    }
                }
            }
        } else {
            val = elem.val();
        }
        
        if ( "undefined" === typeof val ) {
            val = "";
        }

        return val.trim();
    }

    function fieldUpdating( field, hash ) {
        if ( objHasOwn( updatingFields, field ) ) {
            if ( isObject( updatingFields[field] ) ) {
                if ( hash ) {
                    updatingFields[field][hash] = true;
                }
            } else {
                updatingFields[field] = true;
            }
        } else if ( hash ) {
            updatingFields[field] = {};
            updatingFields[field][hash] = true;
        } else {
            updatingFields[field] = true;
        }
    }
    
    function isFieldUpdating( field, hash ) {
        if ( objHasOwn( updatingFields, field ) ) {
            if ( hash ) {
                if ( isObject( updatingFields[field] ) && objHasOwn( updatingFields[field], hash ) ) {
                    return updatingFields[field][hash];
                }

                return false;
            }
            
            return updatingFields[field];
        }

        return false;
    }
    
    function fieldUpdated( field, hash ) {
        if ( objHasOwn( updatingFields, field ) ) {
            if ( isObject( updatingFields[field] ) ) {
                if ( hash ) {
                    updatingFields[field][hash] = false;
                }
            } else {
                updatingFields[field] = false;
            }
        }
    }
    
    /**
     * Retrieves a field settings
     * 
     * @param {string} field The field
     * @return object
     */
    function fieldSettings( field ) {
        field = field || modalField;

       return isObject( describr.settings.fieldsToEdit ) && objHasOwn( describr.settings.fieldsToEdit, field ) ? describr.settings.fieldsToEdit[field] : {};
    }

    /**
     * Retrieves a specific field setting
     * 
     * @param {string} field   The field
     * @param {string} setting The index for the specific setting
     * @return mixed The setting or an empty string
     */
    function fieldSetting( field, setting ) {
        field = fieldSettings( field );
        
        return objHasOwn( field, setting ) ? field[setting] : "";
    }

    function fieldSupport( support, field ) {
        field = field || describr.profile.activeField;

        var supports = fieldSettings( field )["supports"];
            
        if ( ! support ) {
            return supports;
        }

        return objHasOwn( supports, support ) ? supports[support] : false;
    }

    function isFieldEditable( field ) {
        return describr.settings.isEditable( field );
    }

    function selectAsideModal( field, aside, hash ) {
        var fieldSettings_ = fieldSettings( field ),
            str            = "",
            fieldValue     = "",
            fieldLabel,
            asideSettings, 
            saved, 
            altAside_, 
            options,
            option, 
            images, 
            markupFields,            
            html,
            modalTitle,
            modalLabel,
            modalBaseId,
            getFieldModalVal;

        if ( ! aside || ! Object.keys( fieldSettings_ ).length ) {
            return false;
        }
        
        altAside_ = altAside( aside );

        if ( ! describr.settings["has" + altAside_](field) ) {
            return false;
        }

        asideSettings = describr.settings["get" + altAside_]( field );

        if ( ! objHasOwn( asideSettings, "options" ) ) {
            return false;
        }
        
        getFieldModalVal = getFieldA11yValue( field, hash );
        
        fieldValue = getFieldModalVal.val;

        modalLabel = getFielAsideDialogLabel( field, aside, getFieldModalVal.hasVal ? fieldValue : false );
        
        modalTitle = describrL10n.aside.modal.title[aside];

        html = getFieldModalHtmlValue( field, fieldValue );

        saved = describr.profile["getSaved"+ altAside_]( field ) || getDefaultSavedAside( aside );
        
        //Track the field
        modalField = field;  
        modalHash  = hash;

        //Track the aside      
        selectedAside = aside;
        
        options = asideSettings.options;

        for ( option in options ) {
            str += "<p><label><input type=\"radio\" name=\"" + asideSettings.name + "\" value=\"" + option + "\""; 

            if ( option === saved ) {
                str += " checked=\"checked\"";
            }

            str += "/> " + options[option] + "</label></p>";
        }
        
        updateSubmitButton( false, true );

        modalBaseId = "#describr-profile-select-aside-modal-";
        
        $( modalBaseId + "label" ).html( modalLabel );
        $( modalBaseId + "title" ).html( modalTitle );
        $( modalBaseId + "main-field" ).html( html );
        $( modalBaseId + "main" ).find( "p" ).not( ".describr-no-overflow" ).remove();
        $( modalBaseId + "main" ).append( str );
        
        $( modalBaseId + "wrapper" ).show();

        return true;
    }
    
    function getFieldA11yValue( field, hash ) {
        var markupFields  = describr.settings.specialA11yFields || [], 
            hasValue      = true,
            settings      = fieldSettings( field ),
            fieldValue    = "";
        
        if ( markupFields.includes( field ) ) {
            if ( objHasOwn( accessibleFields, field ) ) {
                fieldValue = accessibleFields[field];

                if ( hash ) {
                    if ( isObject( fieldValue ) && objHasOwn( fieldValue, hash ) ) {
                        fieldValue = fieldValue[hash];
                    } else {
                        fieldValue = "";
                    }
                }
            }
        } else {
            fieldValue = profileFieldSavedValue( field );

            if ( hash && isObject( fieldValue ) && objHasOwn( fieldValue, hash ) ) {
                fieldValue = fieldValue[hash];
            }
        }
        
        if ( ! isScalar( fieldValue ) ) {
            fieldValue = "";
        }
        
        fieldValue = ( "" + fieldValue ).trim();
        
        if ( empty( fieldValue ) ) {
            fieldValue = maybeCityLabel( field ) || settings.title || "";
            hasValue = false;
        }

        return { 
            val    : trimDot( fieldValue ), 
            hasVal : hasValue 
        };
    }
    
    function isFieldNoWhichIs( field ) {
        return [ "lived_cities", "work_history", "high_school", "college", "relationship" ].includes( field );
    }

    function addEditorDialogLabel() {
        var field       = describr.profile.changes.activeField,
            valSettings = getFieldA11yValue( field, describr.profile.changes.fieldHash ),
            l10nStrings = describrL10n.fieldEditor,
            determineLabel;
        
        if ( ! valSettings.hasVal ) {
            determineLabel = sprintf( "adding" === describr.profile.mode ? l10nStrings.add : l10nStrings.update, maybeCityLabel( field ) );
        } else if ( isFieldNoWhichIs( field ) ) {
            determineLabel = sprintf( l10nStrings.noWhichIs, valSettings.val );
        } else {
            determineLabel = sprintf( l10nStrings.whichIs, getFieldLabel( field ), valSettings.val );
        }

        $( "#describr-profile-field-editor-modal-label" ).html( escapeHtml( determineLabel ) );
    }
    
    /**
     * Retrieves the accessibility label for status and privacy asides modal.
     * Audit aside is handled separately
     * 
     * @param string field The field
     * @param string aside The aside
     * @param false|string fieldValue The current aside value for the field or false if no value is saved
     * @return string The accessibility label
     */
    function getFielAsideDialogLabel( field, aside, fieldValue ) {
        var title       = "",
            l10nStrings = describrL10n.aside.modal.label[aside],
            determineLabel;

        if ( "status" === aside ) {
            title = statusTitle( field, savedStatus( field ) );
        } else if ( "privacy" === aside ) {
            title = privacyTitle( field, savedPrivacy( field ) );
        }
        
        if ( false === fieldValue ) {
            determineLabel = sprintf( l10nStrings.noWhichIs, maybeCityLabel( field ), title );
        } else if ( isFieldNoWhichIs( field ) ) {
            determineLabel = sprintf( l10nStrings.noWhichIs, fieldValue, title );
        } else {
            determineLabel = sprintf( l10nStrings.whichIs, maybeCityLabel( field ), fieldValue, title );
        }
        
        return escapeHtml( determineLabel );
    }
    
    function maybeCityLabel( field ) {
        return "lived_cities" === field ? describrL10n.city : getFieldLabel( field );
    }

    function getFieldModalHtmlValue( field, fieldValue ) {
        //Test if HTML is not allowed
        if ( ! fieldSettings( field ).html ) {
            var placeholder  = false,
                placeholders = {}, 
                i,
                n, 
                images;
            
            //Test for smilies and replace them, so they are not escaped
            if ( /wp\-smiley/.test( fieldValue ) ) {
                images = fieldValue.match( /<img [^>]*class="wp\-smiley"[^>]*>/g );

                if ( images ) {
                    for ( i = 0; i < images.length; i++ ) {
                        placeholder = "###DESCRIBR##" + i;
                        fieldValue = fieldValue.replace( images[i], placeholder );
                        placeholders[placeholder] = images[i];
                    }
                }
            }

            fieldValue = escapeHtml( fieldValue );
            
            if ( placeholder ) {
                for ( n in placeholders ) {
                    fieldValue = fieldValue.replace( n, placeholders[n] );
                }
            }
        }

        return fieldValue;
    }
    
    function getFieldLabel( field ) {
        var settings = fieldSettings( field );
        
        return settings.label || settings.title;
    }
    
    function isCountry( countryCode ) {
        return objHasOwn( describr.settings.choice.country, countryCode );
    }

    function isCheck( val ) {
        return "" !== val;
    }
    
    function altAside( aside ) {
        return aside[0].toUpperCase() + aside.slice(1);
    }

    function savedStatus( field ) {
        return describr.settings.hasStatus( field ) ? ( describr.profile.getSavedStatus( field ) || getDefaultSavedAside( "status" ) ) : "";
    }

    function savedPrivacy( field ) {
        return describr.settings.hasPrivacy( field ) ? ( describr.profile.getSavedPrivacy( field ) || getDefaultSavedAside( "privacy" ) ) : "";
    }

    function savedAudit( field ) {
        return describr.settings.hasAudit( field ) ? describr.profile.getSavedAudit( field ) : [];
    }
    
    function statusTitle( field, value ) {
        var settings = describr.settings.getStatus( field );
        return settings.options && settings.options[value] ? settings.options[value] : "";
    }

    function privacyTitle( field, value ) {
        var settings = describr.settings.getPrivacy( field );
        return settings.options && settings.options[value] ? settings.options[value] : "";
    }
    
    function hasSettings( field, setting ) {
        var prop;
        setting = altAside( setting );
        prop = "has" + setting;

        return objHasOwn( describr.settings, prop ) && describr.settings[prop](field);
    }

    function isAsideEditable( field, aside ) {
        return hasSettings( field, aside  ) && describr.settings["get"+altAside(aside)](field).editable;
    }
    
    //Helper function for updateSavedAsidesByCategory() that updates saved aside values by category
    function updateSavedAsidesByCategory_( field ) {
        if ( ! ( objHasOwn( savedAsides, field ) && objHasOwn( describr.settings.getAsides( field ), "_cat" ) ) ) {
            return;
        }

        var fieldSavedAside = savedAsides[field],
            category        = describr.settings.getAsides( field )._cat, 
            field_, 
            settings;

        for ( field_ in describr.settings.asides ) {
            settings = describr.settings.asides[field_];

            if ( isObject( settings ) && objHasOwn( settings, "_cat" ) && category === settings._cat ) {
                savedAsides[ field_ ] = fieldSavedAside;
            }
        }
    }

    function isActiveTab( tab ) {
        return activeTabs.includes( tab.data( "parenttab" ) ) || ( tab.data( "menutab" ) && activeTabs.includes( tab.data( "tab" ) ) );
    }
    
    //updates saved aside values by category
    function updateSavedAsidesByCategory( newAsides ) {
        var field;
        
        newAsides = newAsides || savedAsides;
        
        for ( field in newAsides ) {
            updateSavedAsidesByCategory_( field );
        }
    }

    updateSavedAsidesByCategory();
    
    describr.profile.choices.init();
    describr.profile.events();
});