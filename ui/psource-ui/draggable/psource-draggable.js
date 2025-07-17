class PSourceDraggable {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            handle: options.handle || null,
            containment: options.containment || null,
            connectToSortable: options.connectToSortable || null,
            grid: options.grid || null,
            axis: options.axis || null,
            disabled: options.disabled || false,
            zIndex: options.zIndex || 1000,
            opacity: options.opacity || null,
            helper: options.helper || 'original',
            revert: options.revert || false,
            snap: options.snap || false,
            snapTolerance: options.snapTolerance || 20,
            cursor: options.cursor || 'move',
            // Neue Callback-Optionen
            start: options.start || null,
            drag: options.drag || null,
            stop: options.stop || null,
            ...options
        };
        
        this.isDragging = false;
        this.startPos = { x: 0, y: 0 };
        this.offset = { x: 0, y: 0 };
        this.originalPosition = { x: 0, y: 0 };
        this.helperElement = null;
        this.sortableTarget = null;
        this.dropIndicator = null;
        
        this.init();
    }
    
    init() {
        if (this.options.disabled) return;
        
        const handle = this.options.handle ? 
            this.element.querySelector(this.options.handle) : 
            this.element;
            
        if (!handle) return;
        
        handle.style.cursor = this.options.cursor;
        handle.addEventListener('mousedown', this.onMouseDown.bind(this));
        handle.addEventListener('touchstart', this.onTouchStart.bind(this));
        
        this.element.addEventListener('dragstart', e => e.preventDefault());
        
        // Sortable-Target finden
        if (this.options.connectToSortable) {
            this.sortableTarget = typeof this.options.connectToSortable === 'string' ?
                document.querySelector(this.options.connectToSortable) :
                this.options.connectToSortable;
        }
    }
    
    onMouseDown(e) {
        e.preventDefault();
        this.startDrag(e.clientX, e.clientY, e);
    }
    
    onTouchStart(e) {
        if (e.touches.length === 1) {
            e.preventDefault();
            const touch = e.touches[0];
            this.startDrag(touch.clientX, touch.clientY, e);
        }
    }
    
    startDrag(clientX, clientY, originalEvent) {
        if (this.options.disabled) return;
        
        this.isDragging = true;
        this.startPos = { x: clientX, y: clientY };
        
        const rect = this.element.getBoundingClientRect();
        this.offset = {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
        
        this.originalPosition = {
            x: this.element.offsetLeft,
            y: this.element.offsetTop
        };
        
        this.createHelper(originalEvent);
        this.setupEventListeners();
        
        // Start-Callback aufrufen
        if (this.options.start) {
            this.options.start.call(this, originalEvent, {
                helper: this.helperElement,
                position: this.originalPosition
            });
        }
        
        this.trigger('dragstart', originalEvent);
        
        document.body.style.userSelect = 'none';
        document.body.style.webkitUserSelect = 'none';
        
        // Drop-Indicator für Sortable erstellen
        if (this.sortableTarget) {
            this.createDropIndicator();
        }
    }
    
    createHelper(originalEvent) {
        if (typeof this.options.helper === 'function') {
            // Custom Helper-Funktion aufrufen
            this.helperElement = this.options.helper.call(this, originalEvent);
            
            // Wenn jQuery-Element zurückgegeben wird, konvertieren
            if (this.helperElement && this.helperElement.jquery) {
                this.helperElement = this.helperElement[0];
            }
            
            // Helper positionieren
            this.helperElement.style.position = 'absolute';
            this.helperElement.style.zIndex = this.options.zIndex;
            this.helperElement.style.pointerEvents = 'none';
            document.body.appendChild(this.helperElement);
            
        } else if (this.options.helper === 'clone') {
            this.helperElement = this.element.cloneNode(true);
            this.helperElement.style.position = 'absolute';
            this.helperElement.style.zIndex = this.options.zIndex;
            this.helperElement.style.pointerEvents = 'none';
            document.body.appendChild(this.helperElement);
        } else {
            this.helperElement = this.element;
            this.element.style.position = 'relative';
            this.element.style.zIndex = this.options.zIndex;
        }
        
        if (this.options.opacity !== null) {
            this.helperElement.style.opacity = this.options.opacity;
        }
    }
    
    createDropIndicator() {
        this.dropIndicator = document.createElement('div');
        this.dropIndicator.className = 'psource-drop-indicator';
        this.dropIndicator.style.cssText = `
            height: 3px;
            background: #3498db;
            margin: 5px 0;
            border-radius: 2px;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        `;
    }
    
    updatePosition(clientX, clientY, originalEvent) {
        let newX = clientX - this.offset.x;
        let newY = clientY - this.offset.y;
        
        // Achsen-Beschränkungen
        if (this.options.axis === 'x') {
            newY = this.originalPosition.y;
        } else if (this.options.axis === 'y') {
            newX = this.originalPosition.x;
        }
        
        // Grid-Snapping
        if (this.options.grid) {
            newX = Math.round(newX / this.options.grid[0]) * this.options.grid[0];
            newY = Math.round(newY / this.options.grid[1]) * this.options.grid[1];
        }
        
        // Containment
        if (this.options.containment) {
            const container = typeof this.options.containment === 'string' ?
                document.querySelector(this.options.containment) :
                this.options.containment;
                
            if (container) {
                const containerRect = container.getBoundingClientRect();
                const elementRect = this.helperElement.getBoundingClientRect();
                
                newX = Math.max(containerRect.left, Math.min(newX, containerRect.right - elementRect.width));
                newY = Math.max(containerRect.top, Math.min(newY, containerRect.bottom - elementRect.height));
            }
        }
        
        // Position setzen
        this.helperElement.style.left = newX + 'px';
        this.helperElement.style.top = newY + 'px';
        
        // Sortable-Logik
        if (this.sortableTarget) {
            this.handleSortableInteraction(clientX, clientY);
        }
        
        // Drag-Callback
        if (this.options.drag) {
            this.options.drag.call(this, originalEvent, {
                helper: this.helperElement,
                position: { left: newX, top: newY }
            });
        }
        
        this.trigger('drag', originalEvent, { position: { left: newX, top: newY } });
    }
    
    handleSortableInteraction(clientX, clientY) {
        const elementUnderPointer = document.elementFromPoint(clientX, clientY);
        
        if (elementUnderPointer && this.sortableTarget.contains(elementUnderPointer)) {
            // Finde das nächste sortierbare Element
            let sortableItem = elementUnderPointer;
            while (sortableItem && !sortableItem.classList.contains('tnpc-row') && sortableItem !== this.sortableTarget) {
                sortableItem = sortableItem.parentElement;
            }
            
            if (sortableItem && sortableItem !== this.sortableTarget) {
                // Zeige Drop-Indicator
                this.showDropIndicator(sortableItem, clientY);
            } else {
                this.hideDropIndicator();
            }
        } else {
            this.hideDropIndicator();
        }
    }
    
    showDropIndicator(targetElement, mouseY) {
        if (!this.dropIndicator) return;
        
        const rect = targetElement.getBoundingClientRect();
        const middle = rect.top + rect.height / 2;
        
        // Entscheiden ob vor oder nach dem Element
        if (mouseY < middle) {
            // Vor dem Element einfügen
            targetElement.parentNode.insertBefore(this.dropIndicator, targetElement);
        } else {
            // Nach dem Element einfügen
            targetElement.parentNode.insertBefore(this.dropIndicator, targetElement.nextSibling);
        }
        
        this.dropIndicator.style.opacity = '1';
    }
    
    hideDropIndicator() {
        if (this.dropIndicator && this.dropIndicator.parentNode) {
            this.dropIndicator.style.opacity = '0';
        }
    }
    
    endDrag(originalEvent) {
        if (!this.isDragging) return;
        
        this.isDragging = false;
        this.cleanupEventListeners();
        
        // Sortable-Drop-Logik
        if (this.sortableTarget && this.dropIndicator && this.dropIndicator.style.opacity === '1') {
            this.handleSortableDrop();
        }
        
        // Revert-Logik
        if (this.options.revert) {
            this.revertToOriginalPosition();
        }
        
        // Helper aufräumen
        if (this.helperElement !== this.element && this.helperElement.parentNode) {
            this.helperElement.parentNode.removeChild(this.helperElement);
        }
        
        // Drop-Indicator aufräumen
        if (this.dropIndicator && this.dropIndicator.parentNode) {
            this.dropIndicator.parentNode.removeChild(this.dropIndicator);
        }
        
        // Styles zurücksetzen
        if (this.options.opacity !== null) {
            this.element.style.opacity = '';
        }
        this.element.style.zIndex = '';
        
        document.body.style.userSelect = '';
        document.body.style.webkitUserSelect = '';
        
        // Stop-Callback
        if (this.options.stop) {
            this.options.stop.call(this, originalEvent, {
                helper: this.helperElement
            });
        }
        
        this.trigger('dragstop', originalEvent);
    }
    
    handleSortableDrop() {
        // Erstelle ein neues Element basierend auf dem Helper
        const newElement = this.createSortableElement();
        
        // Füge es an der Drop-Position ein
        if (this.dropIndicator.parentNode) {
            this.dropIndicator.parentNode.insertBefore(newElement, this.dropIndicator);
        }
    }
    
    createSortableElement() {
        // Erstelle ein neues sortable Element basierend auf den Daten
        const dataId = this.element.dataset.id;
        const dataName = this.element.dataset.name;
        
        // Template für Newsletter-Blöcke erstellen
        const newElement = document.createElement('div');
        newElement.className = 'tnpc-row';
        newElement.innerHTML = `
            <div class="tnpc-block" data-id="${dataId}">
                <div class="tnpc-block-content">
                    ${dataName}
                </div>
            </div>
        `;
        
        return newElement;
    }
    
    // ... Rest der Methoden bleiben gleich ...
    setupEventListeners() {
        this.mouseMoveHandler = this.onMouseMove.bind(this);
        this.mouseUpHandler = this.onMouseUp.bind(this);
        this.touchMoveHandler = this.onTouchMove.bind(this);
        this.touchEndHandler = this.onTouchEnd.bind(this);
        
        document.addEventListener('mousemove', this.mouseMoveHandler);
        document.addEventListener('mouseup', this.mouseUpHandler);
        document.addEventListener('touchmove', this.touchMoveHandler, { passive: false });
        document.addEventListener('touchend', this.touchEndHandler);
    }
    
    onMouseMove(e) {
        if (!this.isDragging) return;
        e.preventDefault();
        this.updatePosition(e.clientX, e.clientY, e);
    }
    
    onTouchMove(e) {
        if (!this.isDragging) return;
        e.preventDefault();
        const touch = e.touches[0];
        this.updatePosition(touch.clientX, touch.clientY, e);
    }
    
    onMouseUp(e) {
        this.endDrag(e);
    }
    
    onTouchEnd(e) {
        this.endDrag(e);
    }
    
    trigger(eventName, originalEvent, data = {}) {
        const event = new CustomEvent(`psource-${eventName}`, {
            detail: {
                element: this.element,
                helper: this.helperElement,
                originalEvent: originalEvent,
                ...data
            },
            bubbles: true
        });
        this.element.dispatchEvent(event);
    }
    
    revertToOriginalPosition() {
        if (this.helperElement === this.element) {
            this.element.style.left = this.originalPosition.x + 'px';
            this.element.style.top = this.originalPosition.y + 'px';
        }
    }
    
    cleanupEventListeners() {
        document.removeEventListener('mousemove', this.mouseMoveHandler);
        document.removeEventListener('mouseup', this.mouseUpHandler);
        document.removeEventListener('touchmove', this.touchMoveHandler);
        document.removeEventListener('touchend', this.touchEndHandler);
    }
    
    enable() {
        this.options.disabled = false;
        this.init();
    }
    
    disable() {
        this.options.disabled = true;
        if (this.isDragging) {
            this.endDrag();
        }
    }
    
    destroy() {
        this.disable();
        this.cleanupEventListeners();
        if (this.helperElement !== this.element && this.helperElement.parentNode) {
            this.helperElement.parentNode.removeChild(this.helperElement);
        }
    }
}

// jQuery-kompatible Wrapper-Funktion
function psourceDraggable(selector, options = {}) {
    const elements = typeof selector === 'string' ? 
        document.querySelectorAll(selector) : 
        [selector];
        
    const instances = [];
    
    elements.forEach(element => {
        if (element && !element._psourceDraggable) {
            element._psourceDraggable = new PSourceDraggable(element, options);
            instances.push(element._psourceDraggable);
        }
    });
    
    return instances.length === 1 ? instances[0] : instances;
}

window.PSourceDraggable = PSourceDraggable;
window.psourceDraggable = psourceDraggable;