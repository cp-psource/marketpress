/**
 * MarketPress UI Compatibility Layer
 * 
 * Provides compatibility shims for jQuery UI functionality
 * when jQuery UI is not available in ClassicPress
 * 
 * @since 3.3.4
 */

(function($) {
    'use strict';
    
    // Check if jQuery UI is available
    var hasJQueryUI = typeof $.ui !== 'undefined';
    
    // Configuration from PHP
    var config = window.mpUIConfig || {};
    
    // Initialize compatibility layer
    function initCompatibility() {
        if (hasJQueryUI) {
            return;
        }
        
        // Load compatibility shims
        initDatepicker();
        initSortable();
        initTooltip();
        initTabs();
        initEffects();
    }
    
    /**
     * Datepicker compatibility using Flatpickr
     */
    function initDatepicker() {
        if (typeof flatpickr === 'undefined') {
            return;
        }
        
        // Create jQuery UI datepicker compatibility
        $.fn.datepicker = function(options) {
            options = options || {};
            
            return this.each(function() {
                var $input = $(this);
                
                // Convert jQuery UI options to Flatpickr options
                var flatpickrOptions = {
                    dateFormat: options.dateFormat || 'Y-m-d',
                    enableTime: false,
                    locale: options.locale || 'default'
                };
                
                // Handle onSelect callback
                if (options.onSelect) {
                    flatpickrOptions.onChange = function(selectedDates, dateStr) {
                        options.onSelect.call($input[0], dateStr);
                    };
                }
                
                // Initialize Flatpickr
                var picker = flatpickr(this, flatpickrOptions);
                
                // Store reference for potential cleanup
                $input.data('flatpickr', picker);
            });
        };
    }
    
    /**
     * Sortable compatibility using SortableJS
     */
    function initSortable() {
        if (typeof Sortable === 'undefined') {
            return;
        }
        
        $.fn.sortable = function(options) {
            options = options || {};
            
            return this.each(function() {
                var $container = $(this);
                
                // Convert jQuery UI options to SortableJS options
                var sortableOptions = {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag'
                };
                
                // Handle update callback
                if (options.update) {
                    sortableOptions.onUpdate = function(evt) {
                        options.update.call($container[0], evt, {
                            item: $(evt.item)
                        });
                    };
                }
                
                // Initialize SortableJS
                var sortable = Sortable.create(this, sortableOptions);
                
                // Store reference
                $container.data('sortable', sortable);
            });
        };
    }
    
    /**
     * Tooltip compatibility using Tippy.js
     */
    function initTooltip() {
        if (typeof tippy === 'undefined') {
            return;
        }
        
        $.fn.tooltip = function(options) {
            options = options || {};
            
            return this.each(function() {
                var $element = $(this);
                
                // Convert jQuery UI options to Tippy options
                var tippyOptions = {
                    content: options.content || $element.attr('title'),
                    placement: options.position || 'top',
                    theme: 'light',
                    arrow: true
                };
                
                // Initialize Tippy
                var instance = tippy(this, tippyOptions);
                
                // Store reference
                $element.data('tippy', instance);
            });
        };
    }
    
    /**
     * Tabs compatibility using native HTML5 and CSS
     */
    function initTabs() {
        $.fn.tabs = function(options) {
            options = options || {};
            
            return this.each(function() {
                var $tabContainer = $(this);
                var $tabs = $tabContainer.find('.ui-tabs-nav li');
                var $panels = $tabContainer.find('.ui-tabs-panel');
                
                // Hide all panels except the first
                $panels.hide().first().show();
                
                // Mark first tab as active
                $tabs.first().addClass('ui-tabs-active');
                
                // Handle tab clicks
                $tabs.on('click', 'a', function(e) {
                    e.preventDefault();
                    
                    var $link = $(this);
                    var $tab = $link.parent();
                    var panelId = $link.attr('href');
                    
                    // Remove active class from all tabs
                    $tabs.removeClass('ui-tabs-active');
                    
                    // Add active class to clicked tab
                    $tab.addClass('ui-tabs-active');
                    
                    // Hide all panels
                    $panels.hide();
                    
                    // Show target panel
                    $(panelId).show();
                    
                    // Call activate callback if provided
                    if (options.activate) {
                        options.activate.call($tabContainer[0], e, {
                            newTab: $tab,
                            newPanel: $(panelId)
                        });
                    }
                });
            });
        };
    }
    
    /**
     * Effects compatibility (basic implementation)
     */
    function initEffects() {
        // Basic highlight effect
        $.fn.effect = function(effect, options, duration, callback) {
            options = options || {};
            duration = duration || 400;
            
            if (effect === 'highlight') {
                return this.each(function() {
                    var $element = $(this);
                    var originalBg = $element.css('background-color');
                    var highlightColor = options.color || '#ffff99';
                    
                    $element
                        .css('background-color', highlightColor)
                        .animate({
                            backgroundColor: originalBg
                        }, duration, callback);
                });
            }
            
            // For other effects, just call the callback
            if (callback) {
                setTimeout(callback, duration);
            }
            
            return this;
        };
    }
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        initCompatibility();
        
        // Add CSS for compatibility
        var css = `
            .sortable-ghost {
                opacity: 0.4;
            }
            .sortable-chosen {
                background-color: #e0e0e0;
            }
            .ui-tabs-nav {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                border-bottom: 1px solid #ddd;
            }
            .ui-tabs-nav li {
                margin-right: 2px;
                padding: 10px 15px;
                background: #f0f0f0;
                border: 1px solid #ddd;
                border-bottom: none;
                cursor: pointer;
            }
            .ui-tabs-nav li.ui-tabs-active {
                background: #fff;
                border-bottom: 1px solid #fff;
                margin-bottom: -1px;
            }
            .ui-tabs-panel {
                padding: 15px;
                border: 1px solid #ddd;
                border-top: none;
            }
        `;
        
        $('<style>').text(css).appendTo('head');
    });
    
})(jQuery);
