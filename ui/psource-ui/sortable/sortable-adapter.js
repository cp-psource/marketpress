/**
 * PSource UI - Sortable Adapter für jQuery UI
 * 
 * Diese Datei stellt einen Adapter bereit, um die PSourceSortable-Klasse
 * mit der jQuery UI Sortable API kompatibel zu machen.
 * 
 * @since 3.4.0
 */

(function($) {
    'use strict';
    
    // Prüfen ob PSourceSortable existiert
    if (!window.PSourceSortable) {
        console.error('PSourceSortable nicht definiert. Bitte stellen Sie sicher, dass psource-sortable.js geladen wurde.');
        return;
    }
    
    // jQuery UI Sortable Adapter
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
            zIndex: 1000,
            
            // Callbacks
            start: null,
            sort: null,
            change: null,
            beforeStop: null,
            stop: null,
            update: null,
            receive: null,
            remove: null,
            over: null,
            out: null,
            activate: null,
            deactivate: null
        },
        
        _create: function() {
            const element = this.element[0];
            
            // Vermeiden Sie Mehrfachinstanziierung
            if (element._psourceSortable) {
                return;
            }
            
            try {
                // PSourceSortable-Instanz erstellen
                const sortableOptions = {
                    items: this.options.items,
                    handle: this.options.handle,
                    placeholder: this.options.placeholder || 'psource-sortable-placeholder',
                    connectWith: this.options.connectWith,
                    disabled: false,
                    axis: this.options.axis,
                    containment: this.options.containment,
                    cursor: this.options.cursor,
                    tolerance: this.options.tolerance,
                    opacity: this.options.opacity,
                    forcePlaceholderSize: this.options.forcePlaceholderSize,
                    helper: this.options.helper,
                    
                    // Callback-Adapter
                    start: (e, ui) => {
                        if (this.options.start) {
                            this.options.start.call(element, e, ui);
                        }
                    },
                    sort: (e, ui) => {
                        if (this.options.sort) {
                            this.options.sort.call(element, e, ui);
                        }
                    },
                    change: (e, ui) => {
                        if (this.options.change) {
                            this.options.change.call(element, e, ui);
                        }
                    },
                    stop: (e, ui) => {
                        if (this.options.beforeStop) {
                            this.options.beforeStop.call(element, e, ui);
                        }
                        if (this.options.stop) {
                            this.options.stop.call(element, e, ui);
                        }
                    },
                    update: (e, ui) => {
                        if (this.options.update) {
                            this.options.update.call(element, e, ui);
                        }
                    },
                    receive: (e, ui) => {
                        if (this.options.receive) {
                            this.options.receive.call(element, e, ui);
                        }
                    }
                };
                
                // Erstellen der PSourceSortable-Instanz
                element._psourceSortable = new window.PSourceSortable(element, sortableOptions);
                
                // Instanz an das jQuery-Element anhängen
                this.element.data('psource-sortable', element._psourceSortable);
                
                console.log('jQuery UI Sortable: PSourceSortable Adapter initialisiert', element);
            } catch (error) {
                console.error('Fehler beim Erstellen der PSourceSortable-Instanz:', error);
            }
        },
        
        // Sortable-Widget-Methoden
        
        destroy: function() {
            const element = this.element[0];
            if (element._psourceSortable) {
                element._psourceSortable.destroy();
                delete element._psourceSortable;
            }
            this.element.removeData('psource-sortable');
            return this;
        },
        
        disable: function() {
            const element = this.element[0];
            if (element._psourceSortable) {
                element._psourceSortable.disable();
            }
            return this;
        },
        
        enable: function() {
            const element = this.element[0];
            if (element._psourceSortable) {
                element._psourceSortable.enable();
            }
            return this;
        },
        
        refresh: function() {
            const element = this.element[0];
            if (element._psourceSortable) {
                element._psourceSortable.refresh();
            }
            return this;
        },
        
        option: function(key, value) {
            // Option-Methode für Kompatibilität
            if (arguments.length === 0) {
                // Alle Optionen zurückgeben
                return this.options;
            }
            
            if (typeof key === 'string') {
                if (value === undefined) {
                    // Einzelnen Optionswert abrufen
                    return this.options[key];
                }
                
                // Einzelne Option setzen
                this.options[key] = value;
            } else {
                // Mehrere Optionen setzen (Objekt)
                this.options = $.extend(this.options, key);
            }
            
            return this;
        },
        
        toArray: function() {
            // ID-Array erstellen
            const items = this.element.find(this.options.items).get();
            return items.map(item => item.id || '');
        },
        
        serialize: function(key) {
            // Serialisierte Darstellung erstellen
            key = key || 'sortable';
            const items = this.element.find(this.options.items).get();
            
            return items.map((item, i) => {
                const itemId = item.id || '';
                return key + '[]=' + encodeURIComponent(itemId);
            }).join('&');
        }
    });
    
    console.log('jQuery UI Sortable Adapter geladen.');
    
})(jQuery);
