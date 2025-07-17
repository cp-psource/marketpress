/**
 * MarketPress Modern UI System
 * 
 * Replaces jQuery UI with modern alternatives
 * 
 * @since 3.3.4
 */

(function($) {
    'use strict';
    
    // Configuration
    var config = window.mpModernUI || {};
    
    // Initialize modern UI components
    function initModernUI() {
        
        // Preserve WordPress native metabox functionality
        if (typeof postboxes !== 'undefined') {
            // Let WordPress handle metabox open/close
            postboxes.init();
        }
        
        // Initialize components
        initDatepickers();
        initSortables();
        initTooltips();
        initTabs();
        initDialogs();
        initProgressBars();
        initButtons();
    }
    
    /**
     * Initialize Flatpickr date pickers (replaces jQuery UI datepicker)
     */
    function initDatepickers() {
        if (typeof flatpickr === 'undefined') {
            return;
        }
        
        // Find all date inputs and convert them
        $('.mp-datepicker, input[type="date"]').each(function() {
            var $input = $(this);
            
            // Skip if already initialized
            if ($input.hasClass('mp-datepicker-initialized')) {
                return;
            }
            
            var options = {
                dateFormat: 'Y-m-d',
                allowInput: true,
                locale: 'de' // German locale
            };
            
            // Get options from data attributes
            if ($input.data('date-format')) {
                options.dateFormat = $input.data('date-format');
            }
            
            if ($input.data('min-date')) {
                options.minDate = $input.data('min-date');
            }
            
            if ($input.data('max-date')) {
                options.maxDate = $input.data('max-date');
            }
            
            // Initialize Flatpickr
            var picker = flatpickr(this, options);
            
            // Store reference
            $input.data('flatpickr', picker);
            $input.addClass('mp-datepicker-initialized');
        });
        
        // Create jQuery UI compatibility layer
        $.fn.datepicker = function(options) {
            if (typeof flatpickr === 'undefined') {
                return this;
            }
            
            return this.each(function() {
                var $input = $(this);
                
                if ($input.hasClass('mp-datepicker-initialized')) {
                    return;
                }
                
                var flatpickrOptions = {
                    dateFormat: 'Y-m-d',
                    allowInput: true
                };
                
                // Convert jQuery UI options to Flatpickr options
                if (options && typeof options === 'object') {
                    if (options.dateFormat) {
                        flatpickrOptions.dateFormat = options.dateFormat;
                    }
                    if (options.minDate) {
                        flatpickrOptions.minDate = options.minDate;
                    }
                    if (options.maxDate) {
                        flatpickrOptions.maxDate = options.maxDate;
                    }
                    if (options.onSelect) {
                        flatpickrOptions.onChange = function(selectedDates, dateStr) {
                            options.onSelect.call($input[0], dateStr);
                        };
                    }
                }
                
                var picker = flatpickr(this, flatpickrOptions);
                $input.data('flatpickr', picker);
                $input.addClass('mp-datepicker-initialized');
            });
        };
    }
    
    /**
     * Initialize SortableJS (replaces jQuery UI sortable)
     */
    function initSortables() {
        if (typeof Sortable === 'undefined') {
            return;
        }
        
        // Find all sortable containers
        $('.mp-sortable, .ui-sortable').each(function() {
            var $container = $(this);
            
            // Skip if already initialized
            if ($container.hasClass('mp-sortable-initialized')) {
                return;
            }
            
            var options = {
                animation: 150,
                ghostClass: 'mp-sortable-ghost',
                chosenClass: 'mp-sortable-chosen',
                dragClass: 'mp-sortable-drag',
                handle: $container.data('handle') || false,
                onUpdate: function(evt) {
                    $container.trigger('sortupdate', [evt]);
                }
            };
            
            // Initialize SortableJS
            var sortable = Sortable.create(this, options);
            
            // Store reference
            $container.data('sortable', sortable);
            $container.addClass('mp-sortable-initialized');
        });
        
        // Create jQuery UI compatibility layer
        $.fn.sortable = function(options) {
            if (typeof Sortable === 'undefined') {
                return this;
            }
            
            return this.each(function() {
                var $container = $(this);
                
                if ($container.hasClass('mp-sortable-initialized')) {
                    return;
                }
                
                var sortableOptions = {
                    animation: 150,
                    ghostClass: 'mp-sortable-ghost',
                    chosenClass: 'mp-sortable-chosen',
                    dragClass: 'mp-sortable-drag'
                };
                
                if (options && typeof options === 'object') {
                    if (options.handle) {
                        sortableOptions.handle = options.handle;
                    }
                    if (options.update) {
                        sortableOptions.onUpdate = function(evt) {
                            options.update.call($container[0], evt, {
                                item: $(evt.item)
                            });
                        };
                    }
                }
                
                var sortable = Sortable.create(this, sortableOptions);
                $container.data('sortable', sortable);
                $container.addClass('mp-sortable-initialized');
            });
        };
    }
    
    /**
     * Initialize Tippy.js tooltips (replaces jQuery UI tooltip)
     */
    function initTooltips() {
        if (typeof tippy === 'undefined') {
            return;
        }
        
        // Find all tooltip elements
        $('.mp-tooltip, [data-tooltip], [title]').each(function() {
            var $element = $(this);
            
            // Skip if already initialized
            if ($element.hasClass('mp-tooltip-initialized')) {
                return;
            }
            
            var content = $element.data('tooltip') || $element.attr('title');
            
            if (content) {
                var options = {
                    content: content,
                    theme: 'light',
                    arrow: true,
                    placement: $element.data('placement') || 'top'
                };
                
                // Initialize Tippy
                var instance = tippy(this, options);
                
                // Store reference
                $element.data('tippy', instance);
                $element.addClass('mp-tooltip-initialized');
                
                // Remove title attribute to prevent default tooltip
                $element.removeAttr('title');
            }
        });
        
        // Create jQuery UI compatibility layer
        $.fn.tooltip = function(options) {
            if (typeof tippy === 'undefined') {
                return this;
            }
            
            return this.each(function() {
                var $element = $(this);
                
                if ($element.hasClass('mp-tooltip-initialized')) {
                    return;
                }
                
                var content = $element.attr('title');
                if (options && options.content) {
                    content = options.content;
                }
                
                if (content) {
                    var tippyOptions = {
                        content: content,
                        theme: 'light',
                        arrow: true,
                        placement: 'top'
                    };
                    
                    if (options && typeof options === 'object') {
                        if (options.position) {
                            tippyOptions.placement = options.position;
                        }
                    }
                    
                    var instance = tippy(this, tippyOptions);
                    $element.data('tippy', instance);
                    $element.addClass('mp-tooltip-initialized');
                    $element.removeAttr('title');
                }
            });
        }
    }
    
    /**
     * Initialize modern tabs (replaces jQuery UI tabs)
     */
    function initTabs() {
        $('.mp-tabs, .ui-tabs').each(function() {
            var $container = $(this);
            
            // Skip if already initialized
            if ($container.hasClass('mp-tabs-initialized')) {
                return;
            }
            
            var $tabs = $container.find('.mp-tabs-nav li, .ui-tabs-nav li');
            var $panels = $container.find('.mp-tabs-panel, .ui-tabs-panel');
            
            // Hide all panels except the first
            $panels.hide().first().show();
            
            // Mark first tab as active
            $tabs.first().addClass('mp-tabs-active');
            
            // Handle tab clicks
            $tabs.on('click', 'a', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var $tab = $link.parent();
                var panelId = $link.attr('href');
                
                // Remove active class from all tabs
                $tabs.removeClass('mp-tabs-active');
                
                // Add active class to clicked tab
                $tab.addClass('mp-tabs-active');
                
                // Hide all panels
                $panels.hide();
                
                // Show target panel
                $(panelId).show();
                
                // Trigger custom event
                $container.trigger('tabsactivate', [{ newTab: $tab, newPanel: $(panelId) }]);
            });
            
            $container.addClass('mp-tabs-initialized');
        });
        
        // Create jQuery UI compatibility layer
        $.fn.tabs = function(options) {
            return this.each(function() {
                var $container = $(this);
                
                if ($container.hasClass('mp-tabs-initialized')) {
                    return;
                }
                
                var $tabs = $container.find('.ui-tabs-nav li, .mp-tabs-nav li');
                var $panels = $container.find('.ui-tabs-panel, .mp-tabs-panel');
                
                $panels.hide().first().show();
                $tabs.first().addClass('mp-tabs-active');
                
                $tabs.on('click', 'a', function(e) {
                    e.preventDefault();
                    
                    var $link = $(this);
                    var $tab = $link.parent();
                    var panelId = $link.attr('href');
                    
                    $tabs.removeClass('mp-tabs-active');
                    $tab.addClass('mp-tabs-active');
                    
                    $panels.hide();
                    $(panelId).show();
                    
                    if (options && options.activate) {
                        options.activate.call($container[0], e, {
                            newTab: $tab,
                            newPanel: $(panelId)
                        });
                    }
                });
                
                $container.addClass('mp-tabs-initialized');
            });
        };
    }
    
    /**
     * Initialize modern dialogs (replaces jQuery UI dialog)
     */
    function initDialogs() {
        // Simple modal implementation
        $.fn.dialog = function(options) {
            return this.each(function() {
                var $element = $(this);
                
                if (!$element.hasClass('mp-dialog-initialized')) {
                    // Create modal wrapper
                    var $modal = $('<div class="mp-modal-overlay"></div>');
                    var $dialog = $('<div class="mp-modal-dialog"></div>');
                    
                    $dialog.append($element.clone());
                    $modal.append($dialog);
                    
                    // Add close button
                    var $close = $('<button class="mp-modal-close">&times;</button>');
                    $dialog.prepend($close);
                    
                    // Handle close
                    $close.on('click', function() {
                        $modal.remove();
                    });
                    
                    // Handle overlay click
                    $modal.on('click', function(e) {
                        if (e.target === this) {
                            $modal.remove();
                        }
                    });
                    
                    // Add to body
                    $('body').append($modal);
                    
                    $element.addClass('mp-dialog-initialized');
                }
            });
        };
    }
    
    /**
     * Initialize progress bars (replaces jQuery UI progressbar)
     */
    function initProgressBars() {
        $('.mp-progressbar, .ui-progressbar').each(function() {
            var $bar = $(this);
            
            if (!$bar.hasClass('mp-progressbar-initialized')) {
                var value = $bar.data('value') || 0;
                var $fill = $('<div class="mp-progressbar-fill"></div>');
                
                $fill.css('width', value + '%');
                $bar.append($fill);
                
                $bar.addClass('mp-progressbar-initialized');
            }
        });
        
        $.fn.progressbar = function(options) {
            return this.each(function() {
                var $bar = $(this);
                
                if (!$bar.hasClass('mp-progressbar-initialized')) {
                    var value = (options && options.value) || 0;
                    var $fill = $('<div class="mp-progressbar-fill"></div>');
                    
                    $fill.css('width', value + '%');
                    $bar.append($fill);
                    
                    $bar.addClass('mp-progressbar-initialized');
                }
            });
        };
    }
    
    /**
     * Initialize modern buttons (replaces jQuery UI button)
     */
    function initButtons() {
        $('.mp-button, .ui-button').each(function() {
            var $button = $(this);
            
            if (!$button.hasClass('mp-button-initialized')) {
                $button.addClass('mp-button-modern');
                $button.addClass('mp-button-initialized');
            }
        });
        
        $.fn.button = function(options) {
            return this.each(function() {
                var $button = $(this);
                
                if (!$button.hasClass('mp-button-initialized')) {
                    $button.addClass('mp-button-modern');
                    $button.addClass('mp-button-initialized');
                }
            });
        };
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        initModernUI();
        
        // Re-initialize on dynamic content
        $(document).on('mp-reinit-ui', function() {
            initModernUI();
        });
    });
    
    // Global reinit function
    window.mpReinitUI = function() {
        initModernUI();
    };
    
})(jQuery);
