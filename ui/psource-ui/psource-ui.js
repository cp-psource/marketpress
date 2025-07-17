/**
 * PSource UI - Hauptdatei
 * 
 * Diese Datei dient als zentraler Einstiegspunkt für das PSource UI System,
 * welches als moderner Ersatz für jQuery UI im MarketPress Plugin dient.
 * 
 * @since 3.4.0
 */

(function($) {
    'use strict';
    
    // PSource UI Namespace
    window.PSourceUI = window.PSourceUI || {};
    
    // Konfiguration
    const config = window.psourceUIConfig || {};
    
    /**
     * Initialisiert alle PSource UI Komponenten
     */
    function initPSourceUI() {
        console.log('PSource UI: Initialisierung...');
        
        // Komponenten automatisch initialisieren
        initTabs();
        initAccordion();
        initSortable();
        initModal();
        initTooltip();
        
        // Kompatibilitätsschicht für jQuery UI
        setupJQueryCompatibility();
        
        console.log('PSource UI: Initialisierung abgeschlossen');
    }
    
    /**
     * Tabs initialisieren
     */
    function initTabs() {
        // Hier wird die externe Tabs-Implementierung eingebunden
        // Die psource-tabs.js kümmert sich um die Initialisierung
        
        // Zusätzliche jQuery-Methode für programmatischen Zugriff
        $.fn.psourceTabs = function(action, options) {
            return this.each(function() {
                const $container = $(this);
                
                // Für bestehende jQuery UI Aufrufe: Init als Standard-Aktion
                if (typeof action === 'object' || !action) {
                    options = action || {};
                    action = 'init';
                }
                
                switch (action) {
                    case 'init':
                        // Tabs initialisieren falls noch nicht erfolgt
                        if (!$container.hasClass('psource-tabs')) {
                            convertLegacyTabs($container, options);
                        }
                        break;
                        
                    case 'activate':
                    case 'option':
                        // Tab programmatisch aktivieren
                        if (options && options.active !== undefined) {
                            const index = options.active;
                            const $tabs = $container.find('.psource-tab');
                            if ($tabs.length > index) {
                                $tabs[index].click();
                            }
                        }
                        break;
                }
            });
        };
    }
    
    /**
     * Konvertiert legacy jQuery UI Tabs Struktur zu PSource Tabs
     */
    function convertLegacyTabs($container, options) {
        // Prüfen ob es sich um jQuery UI Tabs handelt
        if ($container.hasClass('ui-tabs') || $container.find('.ui-tabs-nav').length) {
            // Klassen umwandeln
            $container.addClass('psource-tabs').removeClass('ui-tabs mp-tabs');
            
            const $nav = $container.find('.ui-tabs-nav, .mp-tabs-nav').addClass('psource-tabs-nav')
                .removeClass('ui-tabs-nav mp-tabs-nav');
            
            // Tabs umwandeln
            $nav.find('li').each(function(i) {
                const $li = $(this);
                const $a = $li.find('a');
                const href = $a.attr('href');
                let panelId = href;
                
                // # entfernen falls vorhanden
                if (href && href.indexOf('#') === 0) {
                    panelId = href.substring(1);
                }
                
                // Neue Tab-Struktur erstellen
                const $button = $('<button>')
                    .addClass('psource-tab')
                    .attr('data-tab', panelId)
                    .html($a.html());
                
                if ($li.hasClass('ui-tabs-active') || $li.hasClass('mp-tabs-active') || i === 0) {
                    $button.addClass('active');
                }
                
                $li.empty().append($button);
            });
            
            // Panels umwandeln
            $container.find('.ui-tabs-panel, .mp-tabs-panel').each(function() {
                const $panel = $(this);
                $panel.addClass('psource-tab-panel')
                     .removeClass('ui-tabs-panel mp-tabs-panel');
                
                // Container erstellen falls nötig
                if (!$panel.parent().hasClass('psource-tabs-content')) {
                    const $content = $('<div class="psource-tabs-content"></div>');
                    $panel.wrap($content);
                }
                
                // Ersten Panel aktivieren
                if ($panel.index() === 0) {
                    $panel.addClass('active');
                }
            });
        }
    }
    
    /**
     * Accordion initialisieren
     */
    function initAccordion() {
        // jQuery-Methode für programmatischen Zugriff
        $.fn.psourceAccordion = function(action, options) {
            return this.each(function() {
                const $container = $(this);
                
                // Für bestehende jQuery UI Aufrufe: Init als Standard-Aktion
                if (typeof action === 'object' || !action) {
                    options = action || {};
                    action = 'init';
                }
                
                switch (action) {
                    case 'init':
                        // Accordion initialisieren falls noch nicht erfolgt
                        if (!$container.hasClass('psource-accordion')) {
                            convertLegacyAccordion($container, options);
                        }
                        break;
                }
            });
        };
    }
    
    /**
     * Konvertiert legacy jQuery UI Accordion Struktur zu PSource Accordion
     */
    function convertLegacyAccordion($container, options) {
        if ($container.hasClass('ui-accordion')) {
            $container.addClass('psource-accordion').removeClass('ui-accordion');
            
            // Kopfzeilen und Inhalte finden und umwandeln
            $container.children('h3, .ui-accordion-header').each(function(i) {
                const $header = $(this);
                const $content = $header.next('.ui-accordion-content');
                
                // Wrapper erstellen
                const $item = $('<div class="psource-accordion-item"></div>');
                if ($header.hasClass('ui-accordion-header-active')) {
                    $item.addClass('active');
                }
                
                // Header und Content in den Item-Container verschieben
                $header.addClass('psource-accordion-header')
                       .removeClass('ui-accordion-header')
                       .detach()
                       .appendTo($item);
                       
                $content.addClass('psource-accordion-content')
                       .removeClass('ui-accordion-content')
                       .detach()
                       .appendTo($item);
                       
                // Item in den Accordion-Container einfügen
                $container.append($item);
            });
        }
    }
    
    /**
     * Sortable initialisieren
     */
    function initSortable() {
        // jQuery-Methode für Sortable
        $.fn.psourceSortable = function(options) {
            return this.each(function() {
                const $container = $(this);
                
                // Prüfen ob bereits initialisiert
                if ($container.data('psource-sortable')) {
                    return;
                }
                
                try {
                    // Verwenden der globalen PSourceSortable-Klasse
                    if (typeof window.PSourceSortable === 'function') {
                        const sortable = new window.PSourceSortable(this, options);
                        $container.data('psource-sortable', sortable);
                    } else {
                        console.error('PSourceSortable-Klasse nicht gefunden');
                    }
                } catch (e) {
                    console.error('Fehler beim Initialisieren des Sortable:', e);
                }
            });
        };
    }
    
    /**
     * Modal/Dialog initialisieren
     */
    function initModal() {
        // jQuery-Methode für Modal/Dialog
        $.fn.psourceModal = function(action, options) {
            return this.each(function() {
                const $modal = $(this);
                
                if (typeof action === 'object' || !action) {
                    options = action || {};
                    action = 'open';
                }
                
                switch (action) {
                    case 'open':
                        if (!$modal.hasClass('psource-modal')) {
                            convertLegacyModal($modal);
                        }
                        $modal.addClass('active');
                        break;
                        
                    case 'close':
                        $modal.removeClass('active');
                        break;
                }
            });
        };
    }
    
    /**
     * Konvertiert legacy jQuery UI Dialog zu PSource Modal
     */
    function convertLegacyModal($modal) {
        if ($modal.hasClass('ui-dialog')) {
            $modal.addClass('psource-modal').removeClass('ui-dialog');
            
            // Header, Content, etc. entsprechend umwandeln...
        }
    }
    
    /**
     * Tooltip initialisieren
     */
    function initTooltip() {
        // jQuery-Methode für Tooltip
        $.fn.psourceTooltip = function(options) {
            return this.each(function() {
                const $element = $(this);
                
                // Prüfen ob bereits initialisiert
                if ($element.data('psource-tooltip')) {
                    return;
                }
                
                // Tooltip-Text aus title oder data-tooltip
                const content = $element.attr('title') || $element.data('tooltip') || '';
                if (!content) return;
                
                // Title-Attribut entfernen um Browser-Tooltip zu verhindern
                $element.attr('data-tooltip-original', content).removeAttr('title');
                
                // Tooltip-Element erstellen
                const $tooltip = $('<div class="psource-tooltip"></div>')
                    .html(content)
                    .appendTo('body')
                    .hide();
                
                // Events binden
                $element.on('mouseenter', function() {
                    const pos = $element.offset();
                    $tooltip.css({
                        top: pos.top - $tooltip.outerHeight() - 5,
                        left: pos.left
                    }).fadeIn(200);
                }).on('mouseleave', function() {
                    $tooltip.fadeOut(100);
                });
                
                // Referenz speichern
                $element.data('psource-tooltip', $tooltip);
            });
        };
    }
    
    /**
     * Kompatibilitätsschicht für jQuery UI
     * Damit bestehender Code weiterhin funktioniert
     */
    function setupJQueryCompatibility() {
        // Tabs
        $.fn.tabs = function(options) {
            return this.psourceTabs(options);
        };
        
        // Accordion
        $.fn.accordion = function(options) {
            return this.psourceAccordion(options);
        };
        
        // Sortable
        $.fn.sortable = function(options) {
            return this.psourceSortable(options);
        };
        
        // Dialog
        $.fn.dialog = function(action, options) {
            return this.psourceModal(action, options);
        };
        
        // Tooltip
        $.fn.tooltip = function(options) {
            return this.psourceTooltip(options);
        };
    }
    
    // DOM Ready Handler
    $(document).ready(function() {
        // System initialisieren
        initPSourceUI();
    });
    
    // Öffentliche API
    window.PSourceUI = {
        init: initPSourceUI,
        tabs: function($el, options) {
            return $el.psourceTabs(options);
        },
        accordion: function($el, options) {
            return $el.psourceAccordion(options); 
        },
        sortable: function($el, options) {
            return $el.psourceSortable(options);
        },
        modal: function($el, action, options) {
            return $el.psourceModal(action, options);
        },
        tooltip: function($el, options) {
            return $el.psourceTooltip(options);
        }
    };
    
})(jQuery);
