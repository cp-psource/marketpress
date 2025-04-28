jQuery.validator.addMethod( 'alphanumeric', function( value, element ) {
    return this.optional( element ) || new RegExp( '[a-z0-9]{' + value.length + '}', 'ig' ).test( value );
}, PSOURCE_Metaboxes_Validation_Messages.alphanumeric_error_msg );

jQuery.validator.addMethod( 'lessthan', function( value, element, param ) {
    var $elm = jQuery( element );
    var $parent = ( $elm.closest( '.psource-subfield-group' ).length > 0 ) ? $elm.closest( '.psource-subfield-group' ) : $elm.closest( '.psource-field' );
    return this.optional( element ) || value <= $parent.find( param ).val();
}, jQuery.validator.format( PSOURCE_Metaboxes_Validation_Messages.lessthan_error_msg ) );

( function( $ ) {
    $( document ).ready( function() {
        // Preload working indicator
        $( 'body' ).append( '<img class="psource-metabox-working-indicator" style="display:none" src="' + PSOURCE_Metaboxes.spinner_url + '" alt="" />' );
    } );

    $.fn.isWorking = function( isLoading ) {
        var $spinner = $( '.psource-metabox-working-indicator' );

        return this.each( function() {
            var $this = $( this );

            if ( isLoading ) {
                if ( $this.hasClass( 'working' ) ) {
                    return;
                }

                if ( $this.is( 'input, select, textarea' ) ) {
                    $this.prop( 'disabled', true );
                }

                $this.addClass( 'working' );
                $spinner.insertAfter( $this );
                $spinner.show();
            } else {
                if ( $this.is( 'input, select, textarea' ) ) {
                    $this.prop( 'disabled', false );
                }

                $this.removeClass( 'working' );
                $spinner.hide();
            }
        } );
    };
}( jQuery ) );

( function( $ ) {
    window.onload = function() {
        /* initializing conditional logic here instead of document.ready() to prevent
         issues with wysiwyg editor not getting proper height */
        initConditionals();
        $( '.psource-postbox' ).find( ':checkbox, :radio, select' ).on("change",  initConditionals );
    }

    $( document ).on( 'psource_repeater_field/after_add_field_group', function( e ) {
        initConditionals();
    } );

    jQuery( document ).ready( function( $ ) {
        initValidation();
        initRowShading();
        initToolTips();
        initPostboxAccordions();
    } );

    var initPostboxAccordions = function() {
        $( '#mp-main-form' ).find( '.psource-postbox' ).find( '.hndle, .handlediv' ).on('click', function() {
            var $this = $( this ),
                $postbox = $this.closest( '.psource-postbox' );

            $postbox.toggleClass( 'closed' );
            $( document ).trigger( 'postbox-toggled', $postbox );

            $.post( ajaxurl, {
                "action": "psource_metabox_save_state",
                "closed": $postbox.hasClass( 'closed' ),
                "id": $postbox.attr( 'id' )
            } );
        } );
    }

    var initToolTips = function() {
        $( '.psource-field' ).on( 'click', '.psource-metabox-tooltip', function() {
            var $this = $( this ),
                $button = $this.find( '.psource-metabox-tooltip-button' );

            if ( $button.length == 0 ) {
                $this.children( 'span' ).append( '<a class="psource-metabox-tooltip-button" href="#">x</a>' );
            }

            $this.children( 'span' ).css( 'display', 'block' ).position( {
                my: "left center",
                at: "right center",
                of: $this,
                using: function( pos, feedback ) {
                    $( this ).css( pos ).removeClass( 'right left' ).addClass( feedback.horizontal );
                }
            } );
        } );

        $( '.psource-field' ).on( 'click', '.psource-metabox-tooltip-button', function( e ) {
            e.preventDefault();
            e.stopPropagation();
            $( this ).parent().fadeOut( 250 );
        } );
    }

    var initRowShading = function() {
        $( '.psource-postbox' ).each( function() {
            var $rows = $( this ).find( '.psource-field:visible' );
            $rows.filter( ':odd' ).addClass( 'shaded' );
            $rows.filter( ':even' ).removeClass( 'shaded' );
        } );

        $( '.psource-field-section' ).each( function() {
            var $this = $( this ),
                shaded = $this.hasClass( 'shaded' ) ? true : false;

            if ( shaded ) {
                $this.nextUntil( '.psource-field-section' ).addClass( 'shaded' );
            } else {
                $this.nextUntil( '.psource-field-section' ).removeClass( 'shaded' );
            }
        } )
    }

    var testConditionals = function( conditionals, $obj ) {
        var numValids = 0;

        //Incomplete string escaping or encoding
        /*$.each( conditionals, function( i, conditional ) {
            if ( conditional.name.indexOf( '[' ) >= 0 && $obj.closest( '.psource-subfield-group' ).length ) {
                var nameParts = conditional.name.split( '[' );
                var $input = $obj.closest( '.psource-subfield-group' ).find( '[name^="' + nameParts[0] + '"][name*="[' + nameParts[1].replace( ']', '' ) + ']"]' );
            } else {
                var $input = $( '[name="' + conditional.name + '"]' );
            }*/
        $.each( conditionals, function( i, conditional ) {
            if (conditional.name.indexOf('[') >= 0 && $obj.closest('.psource-subfield-group').length) {
                var nameParts = conditional.name.split('[');
                var sanitizedPart = nameParts[1].replace(/\]/g, ''); // Entferne alle "]" Zeichen
                var $input = $obj.closest('.psource-subfield-group').find('[name^="' + nameParts[0] + '"][name*="[' + sanitizedPart + ']"]');
            } else {
                var sanitizedName = conditional.name.replace(/"/g, ''); // Entferne alle doppelten Anführungszeichen
                var $input = $('[name="' + sanitizedName + '"]');
            }

            if ( !$input.is( ':radio' ) && !$input.is( ':checkbox' ) && !$input.is( 'select' ) ) {
                // Conditional logic only works for radios, checkboxes and select dropdowns
                return;
            }

            var val = getInputValue( $input );

            if ( $.inArray( val, conditional.value ) >= 0 ) {
                numValids++;
            }
        } );

        return numValids;
    };

    var parseConditionals = function( elm ) {
        var conditionals = [ ];
        $.each( elm.attributes, function( i, attrib ) {
            if ( attrib.name.indexOf( 'data-conditional-name' ) >= 0 ) {
                var index = attrib.name.replace( 'data-conditional-name-', '' );

                if ( conditionals[index] === undefined ) {
                    conditionals[index] = { };
                }

                conditionals[index]['name'] = attrib.value;
            }

            if ( attrib.name.indexOf( 'data-conditional-value' ) >= 0 ) {
                var index = attrib.name.replace( 'data-conditional-value-', '' );

                if ( conditionals[index] === undefined ) {
                    conditionals[index] = { };
                }

                conditionals[index]['value'] = attrib.value.split( '||' );
            }
        } );

        return conditionals;
    };

    var getInputValue = function( $input ) {
        if ( $input.is( 'select' ) ) {
            var val = $input.val();
        }

        if ( $input.is( ':checkbox' ) ) {
            var val = ( $input.prop( 'checked' ) ) ? $input.val() : "-1";
        }

        if ( $input.is( ':radio' ) ) {
            var val = $input.filter( ':checked' ).val();
        }

        return val;
    }

    var initConditionals = function() {
        $( '.psource-field-has-conditional, .psource-metabox-has-conditional' ).each( function() {
            var $this = $( this ),
                operator = $this.attr( 'data-conditional-operator' ),
                action = $this.attr( 'data-conditional-action' ),
                numValids = 0;

            if ( operator === undefined || action === undefined ) {
                // Skip elements that don't have conditional attributes defined
                return;
            }

            operator = operator.toUpperCase();
            action = action.toLowerCase();

            var conditionals = parseConditionals( this );

            if ( $this.hasClass( 'psource-metabox-has-conditional' ) ) {
                $container = $this;
            } else {
                $container = ( $this.closest( '.psource-subfield' ).length ) ? $this.closest( '.psource-subfield' ) : $this.closest( '.psource-field' )
            }

            if ( action == 'show' ) {
                if ( operator == 'AND' ) {
                    if ( testConditionals( conditionals, $this ) != conditionals.length ) {
                        hideContainer( $container );
                    } else {
                        showContainer( $container );
                    }
                } else {
                    if ( testConditionals( conditionals, $this ) == 0 ) {
                        $container.hide().next( 'p.submit' ).hide();
                    } else {
                        $container.fadeIn( 500 ).next( 'p.submit' ).fadeIn( 500 )
                    }
                }
            }

            if ( action == 'hide' ) {
                if ( operator == 'AND' ) {
                    if ( testConditionals( conditionals, $this ) == conditionals.length ) {
                        $container.hide().next( 'p.submit' ).hide();
                    } else {
                        $container.fadeIn( 500 ).next( 'p.submit' ).fadeIn( 500 )
                    }
                } else {
                    if ( testConditionals( conditionals, $this ) > 0 ) {
                        $container.hide().next( 'p.submit' ).hide();
                    } else {
                        $container.fadeIn( 500 ).next( 'p.submit' ).fadeIn( 500 )
                    }
                }
            }

            initRowShading();
        } );


        $( '.meta-box-sortables.shop-einstellungen_page_shop-einstellungen-payments' ).fadeTo( 0, 100 );

    };

    var hideContainer = function( $container ) {
        /**
         * Triggers right before a field container is hidden
         *
         * @since 3.0
         * @access public
         * @param jQuery $container The jQuery object to be hidden.
         */
        $( document ).trigger( 'psource_metaboxes/before_hide_field_container', [ $container ] );

        $container.hide().next( 'p.submit' ).hide();

        /**
         * Triggers right after a field container is hidden
         *
         * @since 3.0
         * @access public
         * @param jQuery $container The jQuery object that was hidden.
         */
        $( document ).trigger( 'psource_metaboxes/after_hide_field_container', [ $container ] );

    };

    var showContainer = function( $container ) {
        /**
         * Triggers right before a field container is show
         *
         * @since 3.0
         * @access public
         * @param jQuery $container The jQuery object to be shown.
         */
        $( document ).trigger( 'psource_metaboxes/before_show_field_container', [ $container ] );

        $container.fadeIn( 500, function() {
            /**
             * Triggers right after a field container is fully shown
             *
             * @since 3.0
             * @access public
             * @param jQuery $container The jQuery object that was shown.
             */
            $( document ).trigger( 'psource_metaboxes/after_show_field', [ $container ] );
        } ).next( 'p.submit' ).fadeIn( 500 )
    };

    var initValidation = function() {
        var $form = $( "form#post, form#mp-main-form, form.bulk-form" );

        $form.find( '[data-custom-validation]' ).each( function() {
            var $this = $( this );
            var atts = this.attributes;
            var rule = { };

            $.each( atts, function( index, attr ) {
                if ( attr.name.indexOf( 'data-rule-custom-' ) >= 0 ) {
                    rule.name = attr.name.replace( 'data-rule-custom-', '' );
                    rule.val = attr.value;
                }
            } );

            rule.message = $this.attr( 'data-msg-' + rule.name );

            $.validator.addMethod( ruleName, function( value, element, params ) {
                return this.optional( element ) || new RegExp( rule.val + '{' + value.length + '}', 'ig' ).test( value );
            }, rule.message );
        } );

        //initialize the form validation		
        var validator = $form.validate( {
            errorPlacement: function( error, element ) {
                error.appendTo( element.parent() );
            },
            focusInvalid: false,
            highlight: function( element, errorClass ) {
                var $elm = $( element );
                var $tabWrap = $elm.closest( '.psource-field-tab-wrap' );

                if ( $tabWrap.length > 0 ) {
                    var slug = $tabWrap.attr( 'data-slug' );
                    var $tabWrapParent = $elm.closest( '.psource-subfield-group, .psource-fields' );
                    var $tabLink = $tabWrapParent.find( '.psource-field-tab-label-link' ).filter( '[href="#' + slug + '"]' );
                    $tabLink.addClass( 'has-error' );
                }
            },
            unhighlight: function( element, errorClass, validClass ) {
                var $elm = $( element );
                var $tabWrap = $elm.closest( '.psource-field-tab-wrap' );

                if ( $tabWrap.length > 0 ) {
                    if ( $tabWrap.find( 'label.error' ).filter( ':visible' ).length > 0 ) {
                        // There are other errors in this tab group - bail
                        return;
                    }

                    var slug = $tabWrap.attr( 'data-slug' );
                    var $tabWrapParent = $elm.closest( '.psource-subfield-group, .psource-fields' );
                    var $tabLink = $tabWrapParent.find( '.psource-field-tab-label-link' ).filter( '[href="#' + slug + '"]' );
                    $tabLink.removeClass( 'has-error' );
                }
            },
            ignore: function( index, element ) {
                var $elm = $( element );
                // ignore all elements that are hidden or disabled
                return ( $elm.is( ':hidden' ) || $elm.prop( 'disabled' ) );
            },
            wrapper: "div"
        } );

        $form.on( 'invalid-form.validate', function() {
            var errorCount = validator.numberOfInvalids();
            var msg = PSOURCE_Metaboxes.form_error_msg;

            if ( errorCount == 1 ) {
                msg = msg.replace( /%s1/g, errorCount + ' ' + PSOURCE_Metaboxes.error ).replace( /%s2/g, PSOURCE_Metaboxes.has );
            } else {
                msg = msg.replace( /%s1/g, errorCount + ' ' + PSOURCE_Metaboxes.errors ).replace( /%s2/g, PSOURCE_Metaboxes.have );
            }

            alert( msg );
        } );

        $form.find( '#publish, #save-post,.save-bulk-form, [type="submit"]' ).on('click', function( e ) {
            if ( !$form.valid() ) {
                e.preventDefault();
            }
        } );
    }

}( jQuery ) );