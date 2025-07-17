/**
 * PSource UI - jQuery UI Kompatibilitätsschicht
 * 
 * Diese Datei bietet eine vollständige Kompatibilitätsschicht für jQuery UI-Komponenten,
 * indem sie die entsprechenden Aufrufe an PSource UI-Komponenten weiterleitet.
 * 
 * @since 3.4.0
 */

(function($) {
    'use strict';
    
    // Überprüfen, ob jQuery Widget Framework verfügbar ist
    if (typeof $.widget !== 'function') {
        console.error('jQuery Widget Framework nicht gefunden. Die Kompatibilitätsschicht wird nicht funktionieren.');
        return;
    }
    
    // Cache für jQuery UI-Namespace
    var ui = $.ui || {};
    
    // jQuery UI Version simulieren
    ui.version = '1.13.2-psource';
    
    /**
     * Tab Widget Kompatibilitätsschicht
     */
    $.widget('ui.tabs', {
        options: {
            active: 0,
            collapsible: false,
            event: 'click',
            heightStyle: 'content',
            hide: null,
            show: null
        },
        
        _create: function() {
            const $el = $(this.element);
            
            // PSource UI Tabs initialisieren
            $el.psource_tabs({
                active: this.options.active
            });
            
            return this;
        },
        
        _init: function() {
            return this;
        },
        
        option: function(key, value) {
            const $el = $(this.element);
            
            if (key === 'active' && typeof value !== 'undefined') {
                $el.psource_tabs('option', { active: value });
            }
            
            return this;
        },
        
        // Tab programmatisch aktivieren
        option: function(key, value) {
            if (key === 'active' && value !== undefined) {
                $(this.element).find('.psource-tab').eq(value).click();
            }
            return this;
        }
    });
    
    /**
     * Accordion Widget Kompatibilitätsschicht
     */
    $.widget('ui.accordion', {
        options: {
            active: 0,
            animate: {},
            collapsible: true,
            event: 'click',
            header: '> li > :first-child, > :not(li):even',
            heightStyle: 'auto',
            icons: {
                activeHeader: 'ui-icon-triangle-1-s',
                header: 'ui-icon-triangle-1-e'
            }
        },
        
        _create: function() {
            const $el = $(this.element);
            
            // PSource UI Accordion initialisieren
            $el.psource_accordion({
                active: this.options.active,
                collapsible: this.options.collapsible
            });
            
            return this;
        }
    });
    
    /**
     * Dialog Widget Kompatibilitätsschicht
     */
    $.widget('ui.dialog', {
        options: {
            appendTo: 'body',
            autoOpen: true,
            buttons: [],
            classes: {},
            closeOnEscape: true,
            closeText: 'Close',
            draggable: true,
            height: 'auto',
            hide: null,
            maxHeight: null,
            maxWidth: null,
            minHeight: 150,
            minWidth: 150,
            modal: false,
            position: { my: 'center', at: 'center', of: window },
            resizable: true,
            show: null,
            title: null,
            width: 300
        },
        
        _create: function() {
            const $el = $(this.element);
            
            // PSource UI Modal initialisieren
            if (this.options.autoOpen) {
                $el.psource_modal('open', {
                    title: this.options.title,
                    width: this.options.width,
                    height: this.options.height
                });
            }
            
            return this;
        },
        
        open: function() {
            const $el = $(this.element);
            $el.psource_modal('open');
            return this;
        },
        
        close: function() {
            const $el = $(this.element);
            $el.psource_modal('close');
            return this;
        },
        
        option: function(key, value) {
            // Optionen für Dialog
            return this;
        }
    });
    
    /**
     * Sortable Widget Kompatibilitätsschicht
     */
    $.widget('ui.sortable', {
        options: {
            appendTo: 'parent',
            axis: false,
            connectWith: false,
            containment: false,
            cursor: 'auto',
            cursorAt: false,
            dropOnEmpty: true,
            forcePlaceholderSize: false,
            forceHelperSize: false,
            grid: false,
            handle: false,
            helper: 'original',
            items: '> *',
            opacity: false,
            placeholder: false,
            revert: false,
            scroll: true,
            scrollSensitivity: 20,
            scrollSpeed: 20,
            scope: 'default',
            tolerance: 'intersect',
            zIndex: 1000
        },
        
        _create: function() {
            const $el = $(this.element);
            
            // PSource UI Sortable initialisieren
            $el.psource_sortable(this.options);
            
            return this;
        }
    });
    
    /**
     * Draggable Widget Kompatibilitätsschicht
     */
    $.widget('ui.draggable', {
        options: {
            addClasses: true,
            appendTo: 'parent',
            axis: false,
            connectToSortable: false,
            containment: false,
            cursor: 'auto',
            cursorAt: false,
            grid: false,
            handle: false,
            helper: 'original',
            opacity: false,
            refreshPositions: false,
            revert: false,
            revertDuration: 500,
            scope: 'default',
            scroll: true,
            scrollSensitivity: 20,
            scrollSpeed: 20,
            snap: false,
            snapMode: 'both',
            snapTolerance: 20,
            stack: false,
            zIndex: false
        },
        
        _create: function() {
            const $el = $(this.element);
            
            // PSource UI Draggable initialisieren
            $el.psource_draggable(this.options);
            
            return this;
        }
    });
    
    /**
     * Droppable Widget Kompatibilitätsschicht
     */
    $.widget('ui.droppable', {
        options: {
            accept: '*',
            activeClass: false,
            addClasses: true,
            greedy: false,
            hoverClass: false,
            scope: 'default',
            tolerance: 'intersect'
        },
        
        _create: function() {
            // Droppable mit Vanilla JS wird noch nicht unterstützt
            console.warn('Droppable mit PSource UI wird derzeit nicht unterstützt');
            return this;
        }
    });
    
    /**
     * Datepicker Widget Kompatibilitätsschicht
     */
    $.widget('ui.datepicker', {
        options: {
            altField: '',
            altFormat: '',
            appendText: '',
            autoSize: false,
            beforeShow: null,
            beforeShowDay: null,
            buttonImage: '',
            buttonImageOnly: false,
            buttonText: '...',
            calculateWeek: null,
            changeMonth: false,
            changeYear: false,
            closeText: 'Done',
            constrainInput: true,
            currentText: 'Today',
            dateFormat: 'mm/dd/yy',
            dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            dayNamesMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            dayNamesShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            defaultDate: null,
            duration: 'normal',
            firstDay: 0,
            gotoCurrent: false,
            hideIfNoPrevNext: false,
            isRTL: false,
            maxDate: null,
            minDate: null,
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            navigationAsDateFormat: false,
            nextText: 'Next',
            numberOfMonths: 1,
            prevText: 'Prev',
            selectOtherMonths: false,
            shortYearCutoff: '+10',
            showAnim: 'show',
            showButtonPanel: false,
            showCurrentAtPos: 0,
            showMonthAfterYear: false,
            showOn: 'focus',
            showOptions: {},
            showOtherMonths: false,
            showWeek: false,
            stepMonths: 1,
            weekHeader: 'Wk',
            yearRange: 'c-10:c+10',
            yearSuffix: ''
        },
        
        _create: function() {
            const $el = $(this.element);
            
            // HTML5 Datumseingabe verwenden, falls verfügbar
            if (Modernizr && Modernizr.inputtypes && Modernizr.inputtypes.date) {
                $el.attr('type', 'date');
            } else {
                // Fallback zur nativen HTML5 Datumseingabe
                $el.attr('type', 'date');
                
                // Datumsformat und andere Optionen anwenden
                if (this.options.dateFormat) {
                    // Format für HTML5-Datum ist immer YYYY-MM-DD
                    $el.attr('data-date-format', this.options.dateFormat);
                }
                
                if (this.options.minDate) {
                    $el.attr('min', this._formatDateForHTML5(this.options.minDate));
                }
                
                if (this.options.maxDate) {
                    $el.attr('max', this._formatDateForHTML5(this.options.maxDate));
                }
                
                // Event-Handler für Änderungen
                $el.on('change', function() {
                    $(this).trigger('datepicker-change');
                });
            }
            
            return this;
        },
        
        _formatDateForHTML5: function(date) {
            if (typeof date === 'string') {
                // Versuchen, das Datum zu parsen
                date = new Date(date);
            }
            
            if (date instanceof Date) {
                var year = date.getFullYear();
                var month = (date.getMonth() + 1).toString().padStart(2, '0');
                var day = date.getDate().toString().padStart(2, '0');
                return year + '-' + month + '-' + day;
            }
            
            return '';
        }
    });
    
    // jQuery UI-Namespace exportieren
    $.ui = ui;
    
})(jQuery);
