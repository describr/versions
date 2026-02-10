jQuery(function( $ ) {
	var sprintf         = describr.sprintf,
	    click_          = describr.click,
        isClicked       = describr.isClicked,
        empty           = describr.empty,
        arrSplice       = describr.arrSplice,
        roles           = describr.roles || {},
        rolesArr        = roles.arr || [],
        rolesObj        = roles.obj || {},
        rolesEsc        = roles.esc || {},
        currentTabRoles = [],
        enabledFields,
        enabledFieldsCount,
        displayedFieldsCount,
        doc, 
        modal;

    function showTabRolesModal( tab, key_ ) {
        var displayRoles = "", 
            i = 1, 
            settings = describr.profile.settings[tab];

        currentTabRoles = Object.isObject( settings.priv ) && Object.hasOwn( settings.priv, "current_roles" ) ? settings.priv.current_roles : [];

        rolesArr.forEach( function ( role ) {
            var $for = "describr-tab-role-modal-" + i;
                
            displayRoles += "<li><label for=\"";
            displayRoles += $for;
            displayRoles += "\"><input type=\"checkbox\" value=\""
            displayRoles += rolesEsc[role];
            displayRoles += "\" id=\"";
            displayRoles += $for;
            displayRoles += "\" class=\"describr-select-tab-priv-role\" data-tab=\"";
            displayRoles += tab;
            displayRoles += "\" data-key=\"";
            displayRoles += key_;
            displayRoles += "\"";

            if ( currentTabRoles.includes( role ) ) {
                displayRoles += " checked=\"checked\"";
            }

            displayRoles += " /> ";
            displayRoles += rolesObj[role];
            displayRoles += "</label></li>";
            i++;
        });

        if ( ! empty( displayRoles ) ) {
            displayRoles = "<ul>" + displayRoles + "</ul>";
            modal
            .find( "#describr-admin-modal-title" ).html( sprintf( describrL10n.editTabRoleTitle, settings.name ) )
            .end()
            .find( "#describr-admin-modal-main" ).html( displayRoles )
            .end()
            .show();
        }
    }
    
    function setTabPrivChoice( settings ) {
        var privSettings = settings.priv, 
            tab          = privSettings.key,
            tabName      = settings.name,
            tabCtrl      = $( "#" + tab.replace( /_privacy$/, "" ) ).closest( "label" ),
            elem         = "<div id=\"" + tab + "_wrap\">";
                     
        elem += "<p><label for=\"" + tab + "\">" + sprintf( describrL10n.tabPrivLabel, tabName ) + " <select name=\"" + tab + "\" id=\"" + tab + "\">";
        elem += describr.profile.tabPrivChoice.join( "\n" ).replace( "value=\"logged-in_or_logged-out\"", "value=\"logged-in_or_logged-out\" selected=\"selected\"" );
        elem += "</select></label></p>";
        elem += "</div>";

        if ( tabCtrl.next( "br" ).length ) {
            tabCtrl.next( "br" ).replaceWith( elem );
        } else {
            tabCtrl.after( elem );
        }
    }

    function tableFieldsMarkup( fieldType ) {
        fieldType = fieldType || "profile";
        enabledFieldsCount = 0;
        displayedFieldsCount = 0;

        var tRows = tableRowFieldsMarkup( fieldType ), table = [];
               
        if ( tRows ) {
            table.push( "<table class=\"describr-table describr-table-striped\"><caption class=\"screen-reader-text\">" + describrL10n.fieldsTableCap[fieldType] + "</caption>" );
            table.push( "<thead><td class=\"check-column\"><input type=\"checkbox\" id=\"describr-cb-select-all-1\"" );

            if ( enabledFieldsCount === displayedFieldsCount ) {
                table.push( " checked=\"checked\"" );
            }

            table.push( " /><label for=\"describr-cb-select-all-1\"><span class=\"screen-reader-text\">" + describrL10n.selectAll + "</span></label>" );
            table.push( "</td><th scope=\"col\" colspan=\"19\">" + describrL10n.fields + "</th>" );
            table.push( "</thead><tbody>" );
            table.push( tRows );
            table.push( "</tbody><tfoot><td class=\"check-column\">" );
            table.push( "<input type=\"checkbox\" id=\"describr-cb-select-all-2\"" );

            if ( enabledFieldsCount === displayedFieldsCount ) {
                table.push( " checked=\"checked\"" );
            }

            table.push( " /><label for=\"describr-cb-select-all-2\"><span class=\"screen-reader-text\">" + describrL10n.selectAll + "</span></label></td>" );
            table.push( "<th scope=\"col\" colspan=\"19\">" + describrL10n.fields + "</th></tfoot></table>" );

            modal
            .find( "#describr-admin-modal-title" ).html( describrL10n.fieldsModalTitle[fieldType] )
            .end()
            .find( "#describr-admin-modal-main" ).html( table.join("") )
            .end()
            .show();
        }
    }
	
    function tableRowFieldsMarkup( fieldType ) {
        enabledFields = enabledFields || describr.settings.fields.enabled;
        
        var fields           = describr.settings.fields.fields,
            fieldsData       = describr.settings.fields.fieldsData,
            asides           = describr.settings.fields.asides,
            socialFields     = describr.settings.fields.social,
            tRows            = "", 
            fieldLabelLocale = describrL10n.fieldsColname,
            yes              = "<span class=\"dashicons dashicons-yes\" aria-hidden=\"true\" title=\"" + describrL10n.yes + "\"></span><span class=\"screen-reader-text\">" + describrL10n.yes + "</span>",
            no               = "<span class=\"dashicons dashicons-no\" aria-hidden=\"true\" title=\"" + describrL10n.no + "\"></span><span class=\"screen-reader-text\">" + describrL10n.no + "</span>",
            none             = "<span aria-hidden=\"true\" title=\"" + describrL10n.none + "\">&#8212;</span><span class=\"screen-reader-text\">" + describrL10n.none + "</span>",
            tru              = "<span class=\"dashicons dashicons-yes\" aria-hidden=\"true\" title=\"" + describrL10n["true"] + "\"></span><span class=\"screen-reader-text\">" + describrL10n["true"] + "</span>",
            false_           = "<span class=\"dashicons dashicons-no\" aria-hidden=\"true\" title=\"" + describrL10n["false"] + "\"></span><span class=\"screen-reader-text\">" + describrL10n["false"] + "</span>",
            spellcheckTru    = "<span class=\"dashicons dashicons-editor-spellcheck\" aria-hidden=\"true\" title=\"" + describrL10n["true"] + "\"></span><span class=\"screen-reader-text\">" + describrL10n["true"] + "</span>",
            spellcheckFalse  = "<span class=\"dashicons dashicons-no\" aria-hidden=\"true\" title=\"" + describrL10n["false"] + "\"></span><span class=\"screen-reader-text\">" + describrL10n["false"] + "</span>",
            autocompleteOn   = "<span class=\"dashicons dashicons-yes\" aria-hidden=\"true\" title=\"" + describrL10n.on + "\"></span><span class=\"screen-reader-text\">" + describrL10n.on + "</span>",
            autocompleteOff  = "<span class=\"dashicons dashicons-no\" aria-hidden=\"true\" title=\"" + describrL10n.off + "\"></span><span class=\"screen-reader-text\">" + describrL10n.off + "</span>",
            i,
            item,
            fieldData,
            id_,
            label,
            title;
        
        if ( ! Object.isObject( asides ) ) {
            asides = {};
        } 
        
        for ( i = 0; i < fields.length; i++ ) {
            item = fields[i];
            fieldData = fieldsData[item];
            id_ = "describr-field-" + ( i + 1 );
            
            title = "";
            label = "";

            if ( Object.hasOwn( fieldData, "title" ) ) {
                title = fieldData.title;
            } else if ( Object.hasOwn( fieldData, "label" ) ) {
                title = fieldData.label;
            }
            
            if ( Object.hasOwn( fieldData, "label" ) ) {
                label = fieldData.label;
            } else if ( Object.hasOwn( fieldData, "legend" ) ) {
                label = fieldData.legend;
            } else if ( title ) {
                label = title;
            } 

            if ( empty( label ) ) {
                continue;
            }
            
            if ( empty( title ) ) {
                title = label;
            }
            
            if ( "profile" === fieldType ) {
                if ( Object.hasOwn( fieldData, "account_only" ) && fieldData.account_only ) {
                    continue;
                }
            } else if ( "social" === fieldType ) {
                if ( ! socialFields.includes( item ) ) {
                    continue;
                }
            } else if ( "account" === fieldType ) {
                if ( ! Object.hasOwn( fieldData, "account_only" ) || ! fieldData.account_only ) {
                    continue;
                }
            }

            tRows += "<tr><th scope=\"row\" class=\"check-column\">";
            tRows += "<input type=\"checkbox\" class=\"describr-select-fields\" value=\""+ item +"\" id=\"" + id_ + "\"";
                
            if ( enabledFields.includes( item ) ) {
                enabledFieldsCount++;
                tRows += " checked=\"checked\"";
            }
            
            displayedFieldsCount++;

            tRows += "\" /><label for=\""+ id_ +"\">";                
            tRows += "<span class=\"screen-reader-text\">" + sprintf( describrL10n.select, title ) + "</span></label></th>";
            tRows += "<td class=\"column-primary\"><strong>"+ title + "</strong> <br /><button type=\"button\" class=\"describr-toggle-row\" aria-label=\"";
            tRows += describrL10n.moreDetails;
            tRows += "\"></button></td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.label + "\">" + label + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.name + "\">" + fieldData.name + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.subkeys + "\">" + ( Object.hasOwn( fieldData, "supports" ) ? fieldData.supports.join( describr.settings.listSep ) : none ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.type + "\">" + ( Object.hasOwn( fieldData, "type" ) ? fieldData.type : none ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.desc + "\">" + ( Object.hasOwn( fieldData, "description" ) ? fieldData.description : none ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.cap + "\">" + ( Object.hasOwn( fieldData, "cap" ) ? fieldData.cap : none ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.html + "\">" + ( Object.hasOwn( fieldData, "html" ) ? yes : no ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.autocomplete + "\">";
                    
            if ( Object.hasOwn( fieldData, "autocomplete" ) ) {
                tRows += typeof fieldData.autocomplete === "bool" ? ( fieldData.autocomplete ? autocompleteOn : autocompleteOff ) : fieldData.autocomplete;
            } else {
                tRows += autocompleteOff;
            }

            tRows += "</td><td data-colname=\"" + fieldLabelLocale.spellcheck + "\">";

            if ( Object.hasOwn( fieldData, "spellcheck" ) && fieldData.spellcheck ) {
                tRows += spellcheckTru;
            } else {
                tRows += spellcheckFalse;
            }
                    
            tRows += "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale["required"] + "\">" + ( Object.hasOwn( fieldData, "required" ) && fieldData["required"] ? tru : false_ ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.hash + "\">" + ( Object.hasOwn( fieldData, "hasHash" ) ? yes : no ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.min_chars + "\">" + ( Object.hasOwn( fieldData, "min_chars" ) ? fieldData.min_chars : none ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.max_chars + "\">" + ( Object.hasOwn( fieldData, "max_chars" ) ? fieldData.max_chars : none ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.exactLength + "\">" + ( Object.hasOwn( fieldData, "length" ) ? fieldData["length"] : none ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.icon + "\">" + ( Object.hasOwn( fieldData, "icon" ) ? "<span class=\"" + fieldData.icon + "\" aria-hidden=\"true\"></span><span class=\"screen-reader-text\">" + fieldLabelLocale.hasIcon + "</span>" : none ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.baseurl + "\">" + ( Object.hasOwn( fieldData, "base_url" ) ? ( Array.isArray( fieldData.base_url ) ? fieldData.base_url.join( describr.settings.listSep ) : fieldData.base_url ) : none ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale["required"] + "\">" + ( Object.hasOwn( fieldData, "editable" ) && fieldData["editable"] ? yes : no ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale["public"] + "\">" + ( Object.hasOwn( fieldData, "public" ) ? ( 1 === parseInt( fieldData["public"] ) ? yes : no ) : yes ) + "</td>";
            tRows += "<td data-colname=\"" + fieldLabelLocale.admin + "\">" + ( Object.hasOwn( asides, item ) ? asides[item].join( describr.settings.listSep ) : none ) + "</td>";
                        
            tRows += "</tr>";
        }
        
        return tRows;
    }

    $( function () {
        doc   = $( document );
        modal = $( "#describr-admin-modal-wrapper" );
        
        var manyCaps = true,
            caps     = $( "#describr-caps p" ),
            capsNum  = caps.length,
            isMobile = Object.hasOwn( describr, "isMobile" ) ? describr.isMobile : false;
        
        if ( 0 < capsNum ) {
            if ( isMobile ) {
                if ( 6 >= capsNum ) {
                    manyCaps = false;
                }
            } else if ( 14 >= capsNum ) {
                manyCaps = false;
            }
            
            if ( ! manyCaps ) {
                $( "#describr-caps" ).css( { "max-height" : "none", "overflow" : "auto" } );
            }
        }
        
        //Add events to the profile tabs
        if ( Object.isObject( describr.profile ) ) {
            describr.profile.tabs.forEach( function ( tab ) {
                var settings     = describr.profile.settings[tab], 
                    privSettings = settings.priv || false,
                    key_         = privSettings ? privSettings.key : false,
                    rolesKey     = settings.roles_key || key_ + "_roles",
                    privRoles;
                
                //(un)check tab
                $( "#" + tab ).on( "change", function () {
                    if ( this.checked ) {
                        if ( privSettings ) {
                            setTabPrivChoice( settings );
                        }
                    } else {
                        //Remove privacy levels and roles
                        doc.find( "#" + ( key_ ? key_ : tab + "_privacy" ) + "_wrap" ).replaceWith( "<br />" );
                    }
                });

                if ( key_ ) {
                    //Privacy level is selected
                    doc.on( "change", "#" + key_, function () {
                        var choice = $( this ).val();
                        
                        if ( ["with-roles", "profile-owner_or_with-roles"].includes( choice ) ) {
                            showTabRolesModal( tab, key_ );
                        } else {
                            describr.profile.settings[tab].priv = describr.profile.settings[tab].priv || {};
                            privRoles = describr.profile.settings[tab].priv.current_roles || [];
                            //Remove the tab"s privacy roles that would be sent to the server
                            $( "input[name=\"" + rolesKey + "[]\"]" ).each( function () {
                                privRoles = arrSplice( privRoles, $( this ).val() );
                                
                                $( this ).remove();
                            });
                            
                            describr.profile.settings[tab].priv.current_roles = privRoles;  

                            //Remove \"Edit roles\" button
                            $( "#_" + rolesKey + "_editrole" ).remove();
                        }
                    });
                    
                    //Show the modal to select tab privacy roles when the "Edit roles" button is clicked
                    doc.on( click_, "#_" + rolesKey + "_editrole", function () {
                        showTabRolesModal( tab, key_ );
                    });
                }
            });
        }
        
        //Toggle the role that can view the profile tab
        doc.on( "change", ".describr-select-tab-priv-role", function () {
            var checkbox    = $( this ),
                role        = checkbox.val(),
                tab         = checkbox.data( "tab" ),
                selectWrap  = $( "#" + checkbox.data( "key" ) + "_wrap" ),
                roleKey     = describr.profile.settings[tab].roles_key || checkbox.data( "key" ) + "_roles",
                editRoleBtn =  "_" + roleKey + "_editrole";
            
            if ( this.checked ) {
                if ( ! currentTabRoles.includes( role ) ) {
                    currentTabRoles.push( role );
                    describr.profile.settings[tab].priv.current_roles = currentTabRoles;
                    
                    selectWrap.append( "<input type=\"hidden\" name=\"" + roleKey + "[]\" value=\"" + role + "\" />" );

                    if ( 0 === $( "#" + editRoleBtn ).length ) {
                        selectWrap.append( "<p><span role=\"button\" id=\"" + editRoleBtn + "\" class=\"describr-edit\" data-tab=\"" + tab  + "\">" + describrL10n.ediRoles + " <span aria-hidden=\"true\" class=\"dashicons dashicons-edit\"></span></span></p>" );
                    }       
                }
            } else {
                currentTabRoles = arrSplice( currentTabRoles, role );
                describr.profile.settings[tab].priv.current_roles = currentTabRoles;
                
                $( "input[name=\"" + roleKey + "[]\"]" ).each( function () {
                    if ( role === $( this ).val() ) {
                        $( this ).remove();
                    }
                });
                
                if ( 0 === currentTabRoles.length ) {
                    $( "#" + editRoleBtn ).closest( "p" ).remove();
                }
            }
        });
        
        //Checkboxes
        doc.on( "change", ".describr-check-column input[type=checkbox]", function ( e ) {
            var checked = this.checked,
                elem = $( this ),
                id = elem.attr( "id" ),
                isAll  = /cb\-select\-all/.test( id );

            if ( isAll ) {
                elem.closest( "table" ).find( "input[type=checkbox]" ).each( function () {
                    var box = $( this ).prop( "checked", checked );
                    updateCheckbox( this );
                });
            } else if ( checked ) {
                [1,2].forEach( function ( item ) {
                    elem.closest( "table" ).find( "#describr-cb-select-all-" + item ).prop( "checked", checked );
                });
            }
        });
        
        //Add/remove fields in the form to be submitted when the respective checkbox in the modal is toggled 
        doc.on( "change", ".describr-select-fields[type=checkbox]", function() {
            updateCheckbox( this );
        });

        //Toggle Fields content
        doc.on( click_, ".describr-toggle-row", function () {
            var tr = $( this ).closest( "tr" ),
                cls = "describr-is-expanded";

            if ( tr.hasClass( cls ) ) {
                tr.removeClass( cls );
            } else {
                tr.addClass( cls );
            }
        });

        $( "input[type=radio][name=describr_mailer]" ).on( "change", function () {
            var isChecked = ( "smtp" === $( this ).val().trim() ? this.checked : false );

            $( "#describr-smtp-helper" )
            .css( "display", isChecked ? "block" : "none" )
            .find( "input,select" ).prop( "disabled", ! isChecked );
        });
        
        $( "#describr_require_strong_password" ).on( "change", function () {
            var disabled = ! this.checked;
            
            ["in", "ax"].forEach( function ( item ) {
                $( "#describr_password_m" + item + "_chars" ).prop( "disabled", disabled );
            });

            if ( disabled ) {
                $( "#describr_password_complexity" ).addClass( "screen-reader-text" );
            } else {
                $( "#describr_password_complexity" ).removeClass( "screen-reader-text" )
            }
        });

        //Toggle the email"s logo size element based on the checked state of the email logo radio button
        $( "input[type=radio][name=describr_mail_logo]" ).on( "change", function () {
            var val = $( this ).val().trim();

            if ( "site" === val ) {
                if ( "custom" === $( "#describr_mail_logo_size" ).prop( "disabled", ! this.checked ).val() ) {
                    $( "#describr_mail_logo_size_custom" ).show();
                } else {
                    $( "#describr_mail_logo_size_custom" ).hide();
                }
            } else {
                $( "#describr_mail_logo_size" ).prop( "disabled", true );
                $( "#describr_mail_logo_size_custom" ).hide();
            } 

            if ( "custom" === val ) {
                $( "#describr_mail_logo_url" ).prop( "disabled", ! this.checked );
            } else {
                $( "#describr_mail_logo_url" ).prop( "disabled", true );
            }
        });
        
        //Toggle custom width/height based on whether the "custom" option is selected
        $( "#describr_mail_logo_size" ).on( "change", function () {
            if ( "custom" === $( this ).val() ) {
                $( "#describr_mail_logo_size_custom" ).show();
            } else {
                $( "#describr_mail_logo_size_custom" ).hide();
            }
        });

        //Display modal containing Profile fields
        $( "#describr-manage-profile-fields" ).on( click_, function ( e ) {
            if ( isClicked( e ) ) {
                tableFieldsMarkup();   
            }
        });
        
        //Display modal containing Social Media fields
        $( "#describr-manage-social-media-fields" ).on( click_, function ( e ) {
            if ( isClicked( e ) ) {
                tableFieldsMarkup( "social" );   
            }
        });
        
        //Display modal containing Account Settings fields
        $( "#describr-manage-account-settings-fields" ).on( click_, function ( e ) {
            if ( isClicked( e ) ) {
                tableFieldsMarkup( "account" );   
            }
        });
        
        //Check the custom URL radio button when its accompanying input[type=url] is focused
        ["register","login","logout","deleteaccount"].forEach( function ( item ) {
            var customId = "#describr_" + item + "_redirect_custom";
            $( customId ).focus( function () {
                $( customId + "_radio" ).prop( "checked", true );
            });
        });
        
        //Display capability info popup when the trigger element is clicked
        doc.on( click_, function ( e ) {
            var popupID, descID, button;

            if ( ! isClicked( e ) ) {
                return true;
            }
            
            //The popup is being triggered, so there's no need to toggle.
            if ( $( e.target ).closest( ".describr-cap-desc-popup-trigger" ).length ) {
                if ( ! $( ".describr-popper-wrapper" ).length ) {
                    $( "body" ).append( "<div class=\"describr describr-popper-wrapper\"></div>" );
                }
                
                button = $( e.target ).closest( ".describr-cap-desc-popup-trigger" );
                descID = button.data( "capdesc" );
                popupID = descID + "_popup";

                if ( ! $( "#" + popupID ).length ) {
                    $( ".describr-popper-wrapper" ).html( "<div class=\"describr-popup\" id=\"" + popupID + "\"><div class=\"describr-popup-menu\"><div style=\"padding: 4px; margin: 6px 4px;\">" + $( "#" + descID ).html() + "</div></div><span data-popper-arrow aria-hidden=\"true\"></span></div>" );
                    
                    describr.poppers[popupID] = {
                        popup  : $( "#" + popupID ),
                        button : button,
                        show   : true
                    };
                }

                describr.togglePopper();

                return true;
            }

            if ( $( e.target ).closest( ".describr-popup" ).length ) {
                popupID = $( e.target ).closest( ".describr-popup" ).attr( "id" );

                if ( Object.hasOwn( describr.poppers, popupID ) ) {
                    describr.poppers[popupID].show = true;
                }
            }
            
            describr.togglePopper();
        });

        function updateCheckbox( elem ) {
            elem = $( elem );
            var field = elem.val(),
                _id = "describr_" + field + "_field";

            if ( elem[0].checked ) {
                if ( 0 === $( "#" + _id ).length ) {
                    if ( ! enabledFields.includes() ) {
                        enabledFields.push( field );
                    }

                    $( "#describr-manage-profile-fields" ).closest( "form" ).append( "<input type=\"hidden\" name=\"describr_fields[]\" value=\"" + field + "\" id=\"" + _id + "\" />" );
                }
            } else {
                enabledFields = arrSplice( enabledFields, field );
                $( "#" + _id ).remove();
            }
        }
    });
});