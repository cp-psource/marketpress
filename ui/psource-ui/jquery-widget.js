/**
 * PSource UI - jQuery Widget Factory Minimal Implementation
 * 
 * Eine vereinfachte Version des jQuery Widget Factory, um die $.widget() Aufrufe
 * in der jQuery UI Kompatibilitätsschicht zu unterstützen.
 * 
 * @since 3.4.0
 */

(function($) {
    'use strict';
    
    // Namespace für UI
    $.ui = $.ui || {};
    
    /**
     * Vereinfachte Widget Factory
     */
    $.widget = function(name, base, prototype) {
        var namespace = name.split('.')[0];
        var widgetName = name.split('.')[1];
        var fullName = namespace + '-' + widgetName;
        
        if (!prototype) {
            prototype = base;
            base = $.Widget;
        }
        
        // Widget-Konstruktor erstellen
        var basePrototype = new base();
        var widgetPrototype = $.extend({}, basePrototype, prototype);
        
        // jQuery-Plugin erstellen
        $[namespace] = $[namespace] || {};
        $[namespace][widgetName] = function(element, options) {
            var instance = this;
            
            // Basisoptionen mit übergebenen Optionen überschreiben
            this.options = $.extend(true, {}, widgetPrototype.options, options);
            
            // Element referenzieren
            this.element = $(element);
            
            // Widget-Instanz initialisieren
            if (widgetPrototype._create) {
                widgetPrototype._create.call(this);
            }
            
            return this;
        };
        
        // Prototyp für den Widget-Konstruktor setzen
        $[namespace][widgetName].prototype = widgetPrototype;
        
        // jQuery-Plugin-Methode erstellen
        $.fn[widgetName] = function(options) {
            var isMethodCall = typeof options === 'string';
            var args = Array.prototype.slice.call(arguments, 1);
            var returnValue = this;
            
            if (isMethodCall) {
                // Methodenaufruf
                this.each(function() {
                    var instance = $.data(this, fullName);
                    
                    if (!instance) {
                        return $.error("Cannot call methods on " + widgetName +
                            " prior to initialization; attempted to call method '" +
                            options + "'");
                    }
                    
                    if (!$.isFunction(instance[options]) || options.charAt(0) === "_") {
                        return $.error("No such method '" + options + "' for " + widgetName);
                    }
                    
                    var methodValue = instance[options].apply(instance, args);
                    
                    if (methodValue !== instance && methodValue !== undefined) {
                        returnValue = methodValue;
                        return false;
                    }
                });
            } else {
                // Widget-Initialisierung
                this.each(function() {
                    var instance = $.data(this, fullName);
                    
                    if (instance) {
                        // Update von bestehender Instanz
                        instance.option(options || {});
                        if (instance._init) {
                            instance._init();
                        }
                    } else {
                        // Neue Instanz erstellen
                        $.data(this, fullName, new $[namespace][widgetName](this, options));
                    }
                });
            }
            
            return returnValue;
        };
    };
    
    /**
     * Basis-Widget
     */
    $.Widget = function() {};
    
    $.Widget.prototype = {
        widgetName: "widget",
        widgetEventPrefix: "",
        defaultElement: "<div>",
        options: {},
        
        _createWidget: function(options, element) {
            this.element = $(element || this.defaultElement);
            this.options = $.extend(true, {}, this.options, options);
            
            this._create();
            this._trigger("create", null, this._getCreateEventData());
            this._init();
        },
        
        _getCreateOptions: function() {
            return {};
        },
        
        _getCreateEventData: function() {
            return {};
        },
        
        _create: $.noop,
        _init: $.noop,
        
        destroy: function() {
            this._destroy();
            this.element.removeData(this.widgetFullName);
            return this;
        },
        
        _destroy: $.noop,
        
        widget: function() {
            return this.element;
        },
        
        option: function(key, value) {
            var options = key;
            var parts;
            var curOption;
            var i;
            
            if (arguments.length === 0) {
                // Alle Optionen abrufen
                return $.extend(true, {}, this.options);
            }
            
            if (typeof key === "string") {
                // Eine Option abrufen oder setzen
                parts = key.split(".");
                curOption = options = this.options;
                
                // Falls nur ein key, dann den Wert holen
                if (arguments.length === 1) {
                    for (i = 0; i < parts.length - 1; i++) {
                        curOption = curOption[parts[i]];
                        if (curOption === undefined) {
                            return undefined;
                        }
                    }
                    return curOption[parts[i]];
                }
                
                // Eine Option setzen
                for (i = 0; i < parts.length - 1; i++) {
                    if (!curOption[parts[i]]) {
                        curOption[parts[i]] = {};
                    }
                    curOption = curOption[parts[i]];
                }
                curOption[parts[i]] = value;
            } else {
                // Mehrere Optionen setzen
                for (var key in options) {
                    this.option(key, options[key]);
                }
            }
            
            return this;
        },
        
        _trigger: function(type, event, data) {
            var prop, orig;
            var callback = this.options[type];
            
            data = data || {};
            event = $.Event(event);
            event.type = (type === this.widgetEventPrefix ?
                type :
                this.widgetEventPrefix + type).toLowerCase();
            
            // Event-Daten kopieren
            for (prop in data) {
                if (data.hasOwnProperty(prop)) {
                    event[prop] = data[prop];
                }
            }
            
            // Callback ausführen
            if ($.isFunction(callback)) {
                callback.apply(this.element[0], [event].concat(data));
            }
            
            return event;
        }
    };
    
})(jQuery);
