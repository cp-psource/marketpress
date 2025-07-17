class PSourceSortable {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            items: options.items || '> *',
            handle: options.handle || null,
            placeholder: options.placeholder || 'psource-sortable-placeholder',
            connectWith: options.connectWith || null,
            disabled: options.disabled || false,
            axis: options.axis || null,
            containment: options.containment || null,
            cursor: options.cursor || 'move',
            tolerance: options.tolerance || 'intersect',
            opacity: options.opacity || null,
            forcePlaceholderSize: options.forcePlaceholderSize || false,
            helper: options.helper || 'original',
            // Callbacks
            start: options.start || null,
            sort: options.sort || null,
            stop: options.stop || null,
            update: options.update || null,
            change: options.change || null,
            receive: options.receive || null,
            ...options
        };
        
        this.isDragging = false;
        this.dragElement = null;
        this.placeholder = null;
        this.originalIndex = -1;
        this.currentIndex = -1;
        this.isExternalDrop = false;
        
        this.init();
    }
    
    init() {
        if (this.options.disabled) return;
        
        this.setupItems();
        this.createPlaceholder();
        this.setupExternalDroppables();
    }
    
    setupItems() {
        const items = this.getSortableItems();
        
        items.forEach(item => {
            const handle = this.options.handle ? 
                item.querySelector(this.options.handle) : 
                item;
                
            if (handle) {
                handle.style.cursor = this.options.cursor;
                handle.addEventListener('mousedown', (e) => this.onMouseDown(e, item));
                handle.addEventListener('touchstart', (e) => this.onTouchStart(e, item));
            }
            
            item.addEventListener('dragstart', e => e.preventDefault());
        });
    }
    
    getSortableItems() {
        if (this.options.items === '> *') {
            return Array.from(this.element.children);
        } else {
            return Array.from(this.element.querySelectorAll(this.options.items));
        }
    }
    
    createPlaceholder() {
        this.placeholder = document.createElement('div');
        this.placeholder.className = this.options.placeholder;
        this.placeholder.style.cssText = `
            height: 40px;
            background: #f0f8ff;
            border: 2px dashed #3498db;
            margin: 5px 0;
            border-radius: 4px;
            display: none;
            position: relative;
        `;
        
        this.placeholder.innerHTML = '<div style="text-align: center; line-height: 36px; color: #3498db; font-style: italic;">Hier einfügen</div>';
    }
    
    setupExternalDroppables() {
        if (!this.options.connectWith) return;
        
        const externalElements = document.querySelectorAll(this.options.connectWith);
        externalElements.forEach(element => {
            if (!element._psourceDraggableSetup) {
                element._psourceDraggableSetup = true;
                element.addEventListener('mousedown', (e) => this.onExternalMouseDown(e, element));
                element.addEventListener('touchstart', (e) => this.onExternalTouchStart(e, element));
            }
        });
    }
    
    onMouseDown(e, item) {
        e.preventDefault();
        this.startSort(e.clientX, e.clientY, item, e);
    }
    
    onTouchStart(e, item) {
        if (e.touches.length === 1) {
            e.preventDefault();
            const touch = e.touches[0];
            this.startSort(touch.clientX, touch.clientY, item, e);
        }
    }
    
    onExternalMouseDown(e, element) {
        e.preventDefault();
        this.startExternalDrag(e.clientX, e.clientY, element, e);
    }
    
    onExternalTouchStart(e, element) {
        if (e.touches.length === 1) {
            e.preventDefault();
            const touch = e.touches[0];
            this.startExternalDrag(touch.clientX, touch.clientY, element, e);
        }
    }
    
    startSort(clientX, clientY, item, originalEvent) {
        this.isDragging = true;
        this.dragElement = item;
        this.originalIndex = this.getCurrentIndex(item);
        this.currentIndex = this.originalIndex;
        this.isExternalDrop = false;
        
        item.style.opacity = this.options.opacity || '0.5';
        item.style.transform = 'rotate(2deg)';
        item.style.zIndex = '1000';
        
        this.placeholder.style.display = 'block';
        if (this.options.forcePlaceholderSize) {
            this.placeholder.style.height = item.offsetHeight + 'px';
        }
        item.parentNode.insertBefore(this.placeholder, item.nextSibling);
        
        this.setupEventListeners();
        
        if (this.options.start) {
            this.options.start.call(this, originalEvent, {
                item: item,
                placeholder: this.placeholder
            });
        }
        
        document.body.style.userSelect = 'none';
    }
    
    startExternalDrag(clientX, clientY, element, originalEvent) {
        this.isDragging = true;
        this.isExternalDrop = true;
        
        if (typeof this.options.helper === 'function') {
            this.dragElement = this.options.helper.call(this, originalEvent);
            
            if (this.dragElement && this.dragElement.jquery) {
                this.dragElement = this.dragElement[0];
            }
        } else {
            this.dragElement = element.cloneNode(true);
        }
        
        this.dragElement.style.position = 'absolute';
        this.dragElement.style.zIndex = '1000';
        this.dragElement.style.pointerEvents = 'none';
        this.dragElement.style.opacity = this.options.opacity || '0.8';
        
        Array.from(element.attributes).forEach(attr => {
            if (attr.name.startsWith('data-')) {
                this.dragElement.setAttribute(attr.name, attr.value);
            }
        });
        
        document.body.appendChild(this.dragElement);
        
        this.placeholder.style.display = 'none';
        if (this.options.forcePlaceholderSize) {
            this.placeholder.style.height = '40px';
        }
        
        this.setupEventListeners();
        
        if (this.options.start) {
            this.options.start.call(this, originalEvent, {
                item: this.dragElement,
                placeholder: this.placeholder
            });
        }
        
        document.body.style.userSelect = 'none';
    }
    
    setupEventListeners() {
        this.mouseMoveHandler = (e) => this.onMouseMove(e);
        this.mouseUpHandler = (e) => this.onMouseUp(e);
        this.touchMoveHandler = (e) => this.onTouchMove(e);
        this.touchEndHandler = (e) => this.onTouchEnd(e);
        
        document.addEventListener('mousemove', this.mouseMoveHandler);
        document.addEventListener('mouseup', this.mouseUpHandler);
        document.addEventListener('touchmove', this.touchMoveHandler, { passive: false });
        document.addEventListener('touchend', this.touchEndHandler);
    }
    
    onMouseMove(e) {
        if (!this.isDragging) return;
        e.preventDefault();
        this.updateSort(e.clientX, e.clientY, e);
    }
    
    onTouchMove(e) {
        if (!this.isDragging) return;
        e.preventDefault();
        const touch = e.touches[0];
        this.updateSort(touch.clientX, touch.clientY, e);
    }
    
    updateSort(clientX, clientY, originalEvent) {
        if (this.isExternalDrop) {
            this.dragElement.style.left = (clientX - 50) + 'px';
            this.dragElement.style.top = (clientY - 20) + 'px';
            
            const elementUnder = document.elementFromPoint(clientX, clientY);
            if (!elementUnder) return;
            
            if (this.element.contains(elementUnder) || elementUnder === this.element) {
                this.placeholder.style.display = 'block';
                
                const rect = this.element.getBoundingClientRect();
                if (clientY < rect.top + 50) {
                    this.element.insertBefore(this.placeholder, this.element.firstChild);
                } else {
                    this.element.appendChild(this.placeholder);
                }
            } else {
                this.placeholder.style.display = 'none';
            }
        } else {
            const elementUnder = document.elementFromPoint(clientX, clientY);
            if (!elementUnder) return;
            
            const items = this.getSortableItems();
            let targetItem = null;
            
            for (let item of items) {
                if (item === this.dragElement) continue;
                if (item.contains(elementUnder) || item === elementUnder) {
                    targetItem = item;
                    break;
                }
            }
            
            if (targetItem) {
                const targetRect = targetItem.getBoundingClientRect();
                const middle = targetRect.top + targetRect.height / 2;
                
                if (clientY < middle) {
                    targetItem.parentNode.insertBefore(this.placeholder, targetItem);
                } else {
                    targetItem.parentNode.insertBefore(this.placeholder, targetItem.nextSibling);
                }
                
                const newIndex = this.getPlaceholderIndex();
                if (newIndex !== this.currentIndex) {
                    this.currentIndex = newIndex;
                    
                    if (this.options.change) {
                        this.options.change.call(this, originalEvent, {
                            item: this.dragElement,
                            placeholder: this.placeholder
                        });
                    }
                }
            }
        }
        
        if (this.options.sort) {
            this.options.sort.call(this, originalEvent, {
                item: this.dragElement,
                placeholder: this.placeholder
            });
        }
    }
    
    onMouseUp(e) {
        this.endSort(e);
    }
    
    onTouchEnd(e) {
        this.endSort(e);
    }
    
    endSort(originalEvent) {
        if (!this.isDragging) return;
        
        this.isDragging = false;
        
        if (this.isExternalDrop) {
            if (this.placeholder.style.display === 'block' && this.placeholder.parentNode) {
                this.placeholder.parentNode.insertBefore(this.dragElement, this.placeholder);
                
                this.dragElement.style.position = '';
                this.dragElement.style.zIndex = '';
                this.dragElement.style.pointerEvents = '';
                this.dragElement.style.opacity = '';
                
                if (this.options.receive) {
                    this.options.receive.call(this, originalEvent, {
                        item: this.dragElement
                    });
                }
                
                if (this.options.update) {
                    this.options.update.call(this, originalEvent, {
                        item: this.dragElement
                    });
                }
            } else {
                if (this.dragElement && this.dragElement.parentNode) {
                    this.dragElement.parentNode.removeChild(this.dragElement);
                }
            }
        } else {
            if (this.placeholder.parentNode) {
                this.placeholder.parentNode.insertBefore(this.dragElement, this.placeholder);
            }
            
            this.dragElement.style.opacity = '';
            this.dragElement.style.transform = '';
            this.dragElement.style.zIndex = '';
            
            if (this.originalIndex !== this.currentIndex) {
                if (this.options.update) {
                    this.options.update.call(this, originalEvent, {
                        item: this.dragElement
                    });
                }
            }
        }
        
        if (this.placeholder && this.placeholder.parentNode) {
            this.placeholder.style.display = 'none';
            this.placeholder.parentNode.removeChild(this.placeholder);
        }
        
        this.cleanupEventListeners();
        
        document.body.style.userSelect = '';
        
        if (this.options.stop) {
            this.options.stop.call(this, originalEvent, {
                item: this.dragElement
            });
        }
        
        this.dragElement = null;
        this.isExternalDrop = false;
        this.originalIndex = -1;
        this.currentIndex = -1;
    }
    
    getCurrentIndex(item) {
        const items = this.getSortableItems();
        return items.indexOf(item);
    }
    
    getPlaceholderIndex() {
        const parent = this.placeholder.parentNode;
        return Array.from(parent.children).indexOf(this.placeholder);
    }
    
    cleanupEventListeners() {
        document.removeEventListener('mousemove', this.mouseMoveHandler);
        document.removeEventListener('mouseup', this.mouseUpHandler);
        document.removeEventListener('touchmove', this.touchMoveHandler);
        document.removeEventListener('touchend', this.touchEndHandler);
    }
    
    refresh() {
        this.setupItems();
    }
    
    enable() {
        this.options.disabled = false;
        this.setupItems();
    }
    
    disable() {
        this.options.disabled = true;
        if (this.isDragging) {
            this.endSort();
        }
    }
    
    destroy() {
        this.disable();
        this.cleanupEventListeners();
        if (this.placeholder && this.placeholder.parentNode) {
            this.placeholder.parentNode.removeChild(this.placeholder);
        }
    }
}

// jQuery-Style Wrapper
function psourceSortable(selector, options = {}) {
    const elements = typeof selector === 'string' ? 
        document.querySelectorAll(selector) : 
        [selector];
        
    const instances = [];
    
    elements.forEach(element => {
        if (element && !element._psourceSortable) {
            element._psourceSortable = new PSourceSortable(element, options);
            instances.push(element._psourceSortable);
        }
    });
    
    return instances.length === 1 ? instances[0] : instances;
}

window.PSourceSortable = PSourceSortable;
window.psourceSortable = psourceSortable;

// Auto-Init für data-Attribute
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-psource-sortable]').forEach(element => {
        const options = {};
        
        if (element.dataset.psourceSortableItems) {
            options.items = element.dataset.psourceSortableItems;
        }
        if (element.dataset.psourceSortableHandle) {
            options.handle = element.dataset.psourceSortableHandle;
        }
        if (element.dataset.psourceSortablePlaceholder) {
            options.placeholder = element.dataset.psourceSortablePlaceholder;
        }
        if (element.dataset.psourceSortableAxis) {
            options.axis = element.dataset.psourceSortableAxis;
        }
        
        new PSourceSortable(element, options);
    });
});