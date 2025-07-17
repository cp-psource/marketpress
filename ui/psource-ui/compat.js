/**
 * Kompatibilitätsschicht für mp-modern-ui
 * 
 * Diese Datei stellt sicher, dass bestehender Code, der auf mp-modern-ui
 * basiert, weiterhin funktioniert, indem alle Aufrufe an psource-ui
 * weitergeleitet werden.
 * 
 * @since 3.4.0
 */

(function($) {
    'use strict';

    // Stellen sicher, dass das Namespace existiert
    window.MP_ModernUI = window.MP_ModernUI || {};

    /**
     * Tabs Kompatibilität
     */
    $.fn.mp_tabs = function(options) {
        return $(this).psource_tabs(options);
    };

    /**
     * Accordion Kompatibilität
     */
    $.fn.mp_accordion = function(options) {
        return $(this).psource_accordion(options);
    };

    /**
     * Modal Kompatibilität
     */
    $.fn.mp_modal = function(options) {
        return $(this).psource_modal(options);
    };

    MP_ModernUI.open_modal = function(content, options) {
        return PSourceUI.openModal(content, options);
    };

    MP_ModernUI.close_modal = function() {
        return PSourceUI.closeModal();
    };

    /**
     * Sortable Kompatibilität
     */
    $.fn.mp_sortable = function(options) {
        return $(this).psource_sortable(options);
    };

    /**
     * Tooltip Kompatibilität
     */
    $.fn.mp_tooltip = function(options) {
        return $(this).psource_tooltip(options);
    };

    /**
     * Draggable Kompatibilität
     */
    $.fn.mp_draggable = function(options) {
        return $(this).psource_draggable(options);
    };

    /**
     * Tabs (jQuery UI Stil)
     */
    $.fn.tabs = function(options) {
        // Für Legacy-Code, der direkt jQuery UI tabs verwendet
        return $(this).psource_tabs(options);
    };

    /**
     * Accordion (jQuery UI Stil)
     */
    $.fn.accordion = function(options) {
        // Für Legacy-Code, der direkt jQuery UI accordion verwendet
        return $(this).psource_accordion(options);
    };

    /**
     * Dialog (jQuery UI Stil)
     */
    $.fn.dialog = function(options) {
        // Für Legacy-Code, der direkt jQuery UI dialog verwendet
        if (typeof options === 'string' && options === 'close') {
            PSourceUI.closeModal();
            return this;
        }
        
        return $(this).psource_modal(options);
    };

    /**
     * Sortable (jQuery UI Stil)
     */
    $.fn.sortable = function(options) {
        // Für Legacy-Code, der direkt jQuery UI sortable verwendet
        return $(this).psource_sortable(options);
    };

    /**
     * Datepicker Kompatibilität
     * Dies ist ein komplexerer Fall, der ein Polyfill benötigt
     */
    $.fn.datepicker = function(options) {
        // Fallback zur nativen HTML5 Datumseingabe mit einigen Verbesserungen
        return $(this).each(function() {
            var $this = $(this);
            
            // Attribut auf Datum setzen
            $this.attr('type', 'date');
            
            if (options && typeof options === 'object') {
                // Datumsformat und andere Optionen anwenden
                if (options.dateFormat) {
                    // Format für HTML5-Datum ist immer YYYY-MM-DD
                    // Aber wir können ein Daten-Attribut für Anzeigezwecke setzen
                    $this.attr('data-date-format', options.dateFormat);
                }
                
                if (options.minDate) {
                    $this.attr('min', formatDateForHTML5(options.minDate));
                }
                
                if (options.maxDate) {
                    $this.attr('max', formatDateForHTML5(options.maxDate));
                }
            }
            
            // Event-Handler für Änderungen hinzufügen
            $this.on('change', function() {
                $(this).trigger('datepicker-change');
            });
        });
    };

    /**
     * Hilfsfunktion zum Formatieren von Daten für HTML5-Eingaben
     */
    function formatDateForHTML5(date) {
        if (typeof date === 'string') {
            // Versuchen, das Datum zu parsen
            date = new Date(date);
        }
        
        if (date instanceof Date) {
            var year = date.getFullYear();
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var day = date.getDate().toString().padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        return '';
    }

    // Hier können wir später weitere Kompatibilitätslayer für andere jQuery UI-Komponenten hinzufügen

})(jQuery);
