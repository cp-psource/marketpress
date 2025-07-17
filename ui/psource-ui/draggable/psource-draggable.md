# PSource Draggable – Dev-Dokumentation

## 🎯 Übersicht

Das PSource Draggable-System ist ein moderner Ersatz für das veraltete jQuery UI Draggable. Es bietet Touch-Unterstützung, bessere Performance und ist vollständig in Vanilla JavaScript geschrieben.

## 📋 Inhaltsverzeichnis

- [🚀 Schnellstart](#-schnellstart)
- [⚙️ Konfiguration](#️-konfiguration)
- [🎮 API-Methoden](#-api-methoden)
- [📱 Events](#-events)
- [💡 Beispiele](#-beispiele)
- [🔄 Migration von jQuery UI](#-migration-von-jquery-ui)

## 🚀 Schnellstart

### HTML mit Data-Attributen
```html
<!-- Einfaches Draggable -->
<div data-psource-draggable>Zieh mich!</div>

<!-- Mit Handle -->
<div data-psource-draggable data-psource-draggable-handle=".handle">
    <div class="handle">⋮⋮ Handle</div>
    <div>Inhalt</div>
</div>

<!-- Mit Achsen-Beschränkung -->
<div data-psource-draggable data-psource-draggable-axis="x">
    Nur horizontal verschiebbar
</div>
```

### JavaScript-Initialisierung
```javascript
// Einfache Initialisierung
const draggable = new PSourceDraggable(element);

// Mit Optionen
const draggable = new PSourceDraggable(element, {
    handle: '.drag-handle',
    containment: 'parent',
    grid: [10, 10],
    axis: 'x'
});

// jQuery-Style (für einfache Migration)
psourceDraggable('.draggable-items', { axis: 'y' });
```

## ⚙️ Konfiguration

### Verfügbare Optionen

| Option | Typ | Standard | Beschreibung |
|--------|-----|----------|--------------|
| `handle` | String/Element | `null` | CSS-Selektor oder Element als Drag-Handle |
| `containment` | String/Element | `null` | Begrenzung des Drag-Bereichs |
| `grid` | Array | `null` | Raster-Snapping `[x, y]` |
| `axis` | String | `null` | Bewegungsachse: `'x'`, `'y'` oder `null` |
| `disabled` | Boolean | `false` | Draggable deaktivieren |
| `zIndex` | Number | `1000` | Z-Index während dem Dragging |
| `opacity` | Number | `null` | Transparenz während dem Dragging |
| `helper` | String/Function | `'original'` | Helper-Element: `'original'`, `'clone'` oder Funktion |
| `revert` | Boolean/String | `false` | Zurückspringen zur Startposition |
| `snap` | String/Array | `false` | Snapping an andere Elemente |
| `snapTolerance` | Number | `20` | Snapping-Toleranz in Pixeln |
| `cursor` | String | `'move'` | CSS-Cursor beim Hovern |

### Data-Attribute

```html
<div data-psource-draggable
     data-psource-draggable-handle=".handle"
     data-psource-draggable-axis="x"
     data-psource-draggable-containment="parent"
     data-psource-draggable-grid="10,10"
     data-psource-draggable-revert="true">
</div>
```

## 🎮 API-Methoden

```javascript
const draggable = new PSourceDraggable(element, options);

// Aktivieren/Deaktivieren
draggable.enable();
draggable.disable();

// Komplett entfernen
draggable.destroy();

// Status prüfen
console.log(draggable.isDragging);
```

## 📱 Events

### Event-Listener
```javascript
element.addEventListener('psource-dragstart', function(e) {
    console.log('Drag started', e.detail);
});

element.addEventListener('psource-drag', function(e) {
    console.log('Dragging', e.detail.position);
});

element.addEventListener('psource-dragstop', function(e) {
    console.log('Drag stopped', e.detail);
});
```

### Callback-Optionen
```javascript
new PSourceDraggable(element, {
    dragstart: function(event, data) {
        console.log('Start dragging');
    },
    drag: function(event, data) {
        console.log('Currently at:', data.position);
    },
    dragstop: function(event, data) {
        console.log('Stopped dragging');
    }
});
```

## 💡 Beispiele

### Sortierbare Liste
```html
<div class="sortable-container">
    <div class="sortable-item" data-psource-draggable 
         data-psource-draggable-containment=".sortable-container"
         data-psource-draggable-axis="y">Item 1</div>
    <div class="sortable-item" data-psource-draggable 
         data-psource-draggable-containment=".sortable-container"
         data-psource-draggable-axis="y">Item 2</div>
</div>
```

### Dashboard-Widgets
```javascript
document.querySelectorAll('.dashboard-widget').forEach(widget => {
    new PSourceDraggable(widget, {
        handle: '.widget-header',
        containment: '.dashboard',
        grid: [20, 20],
        opacity: 0.8,
        helper: 'clone',
        dragstop: function(event, data) {
            // Position speichern
            saveWidgetPosition(widget.id, data.position);
        }
    });
});
```

### Newsletter-Block Editor
```javascript
// Für den Newsletter Composer
new PSourceDraggable('.newsletter-block', {
    handle: '.block-handle',
    containment: '.newsletter-canvas',
    snap: '.newsletter-block',
    snapTolerance: 10,
    helper: function(element) {
        const helper = element.cloneNode(true);
        helper.style.transform = 'rotate(5deg)';
        return helper;
    }
});
```

## 🔄 Migration von jQuery UI

### Vorher (jQuery UI)
```javascript
$('.draggable').draggable({
    handle: '.handle',
    containment: 'parent',
    grid: [10, 10],
    axis: 'x',
    start: function(event, ui) { /* ... */ },
    drag: function(event, ui) { /* ... */ },
    stop: function(event, ui) { /* ... */ }
});
```

### Nachher (PSource)
```javascript
psourceDraggable('.draggable', {
    handle: '.handle',
    containment: 'parent',
    grid: [10, 10],
    axis: 'x',
    dragstart: function(event, data) { /* ... */ },
    drag: function(event, data) { /* ... */ },
    dragstop: function(event, data) { /* ... */ }
});
```

## 🎨 CSS-Integration

```css
/* Basis-Styling */
.draggable-item {
    cursor: move;
    transition: transform 0.2s ease;
}

/* Während dem Dragging */
.draggable-item:hover {
    transform: scale(1.02);
}

/* Custom Handle */
.drag-handle {
    cursor: grab;
    padding: 5px;
    background: #f0f0f0;
}

.drag-handle:active {
    cursor: grabbing;
}
```

## 🔧 Performance-Tipps

1. **Containment verwenden** - Beschränkt Berechnungen auf einen Bereich
2. **Grid-Snapping** - Reduziert Update-Häufigkeit
3. **Helper-Elemente** - Für komplexe Inhalte Clone verwenden
4. **Event-Throttling** - Bei vielen Drag-Events

## 🐛 Troubleshooting

### Häufige Probleme

**Problem:** Touch funktioniert nicht  
**Lösung:** `touch-action: none` auf das Element setzen

**Problem:** Dragging in iFrames  
**Lösung:** Event-Listeners auch auf iFrame-Document setzen

**Problem:** Performance bei vielen Elementen  
**Lösung:** Event-Delegation verwenden oder Lazy-Loading

---

**📝 Hinweis:** Das PSource Draggable-System ist vollständig kompatibel mit modernen Browsern und unterstützt sowohl Desktop- als auch Touch-Geräte.