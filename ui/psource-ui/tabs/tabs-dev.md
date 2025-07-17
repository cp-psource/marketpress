# PSource Tabs – Dev-Dokumentation

## Struktur

Das Tab-System besteht aus einer oder mehreren Tab-Gruppen mit folgendem HTML-Aufbau:

```html
<div class="psource-tabs" id="tabs-xyz">
  <div class="psource-tabs-nav">
    <button class="psource-tab active" data-tab="tabs-panel-1">Tab 1</button>
    <button class="psource-tab" data-tab="tabs-panel-2">Tab 2</button>
    <!-- weitere Tabs ... -->
  </div>
  <div class="psource-tabs-content">
    <div class="psource-tab-panel active" id="tabs-panel-1">Inhalt 1</div>
    <div class="psource-tab-panel" id="tabs-panel-2">Inhalt 2</div>
    <!-- weitere Panels ... -->
  </div>
</div>
```

- **.psource-tabs**: Container für eine Tab-Gruppe (beliebig viele pro Seite möglich, auch verschachtelt).
- **.psource-tabs-nav**: Container für die Tab-Buttons.
- **.psource-tab**: Einzelner Tab-Button, steuert das zugehörige Panel.  
  - Attribut `data-tab` muss exakt mit der Panel-ID übereinstimmen.
  - Der aktive Tab erhält die Klasse `active`.
- **.psource-tabs-content**: Container für die Panels.
- **.psource-tab-panel**: Einzelnes Panel, das zum Tab gehört.  
  - Die ID muss mit dem `data-tab`-Wert des Buttons übereinstimmen.
  - Das aktive Panel erhält die Klasse `active`.

## Funktionsweise

- Beim Klick auf einen `.psource-tab`-Button wird nur innerhalb der aktuellen Tab-Gruppe gearbeitet:
  - Alle Tabs und Panels werden deaktiviert (`active` entfernt).
  - Der geklickte Tab und das zugehörige Panel werden aktiviert (`active` gesetzt).
- Mehrere Tab-Gruppen und verschachtelte Tabs funktionieren unabhängig voneinander.
- Es werden keine Seitenreloads ausgelöst (Buttons statt `<a>` verwenden).

## Beispiel-CSS

```css
.psource-tab-panel {
  display: none;
}
.psource-tab-panel.active {
  display: block;
}
.psource-tab.active {
  font-weight: bold;
}
```

## Hinweise

- IDs der Panels müssen eindeutig innerhalb der Seite sein.
- Die Zuordnung erfolgt über das `data-tab`-Attribut am Button.
- Das Script benötigt keine jQuery-UI und funktioniert rein mit Vanilla JS.
- Beim Laden wird automatisch der erste Tab/Panel aktiviert, falls keiner aktiv ist.

## JS-Datei

Die Logik befindet sich in  
`assets/psource-ui/tabs/tabs.js`

## Verschachtelung

Verschachtelte Tabs funktionieren automatisch, da immer nur innerhalb der aktuellen `.psource-tabs`-Gruppe gearbeitet wird.

---
```# PSource Tabs – Dev-Dokumentation

## Struktur

Das Tab-System besteht aus einer oder mehreren Tab-Gruppen mit folgendem HTML-Aufbau:

```html
<div class="psource-tabs" id="tabs-xyz">
  <div class="psource-tabs-nav">
    <button class="psource-tab active" data-tab="tabs-panel-1">Tab 1</button>
    <button class="psource-tab" data-tab="tabs-panel-2">Tab 2</button>
    <!-- weitere Tabs ... -->
  </div>
  <div class="psource-tabs-content">
    <div class="psource-tab-panel active" id="tabs-panel-1">Inhalt 1</div>
    <div class="psource-tab-panel" id="tabs-panel-2">Inhalt 2</div>
    <!-- weitere Panels ... -->
  </div>
</div>
```

- **.psource-tabs**: Container für eine Tab-Gruppe (beliebig viele pro Seite möglich, auch verschachtelt).
- **.psource-tabs-nav**: Container für die Tab-Buttons.
- **.psource-tab**: Einzelner Tab-Button, steuert das zugehörige Panel.  
  - Attribut `data-tab` muss exakt mit der Panel-ID übereinstimmen.
  - Der aktive Tab erhält die Klasse `active`.
- **.psource-tabs-content**: Container für die Panels.
- **.psource-tab-panel**: Einzelnes Panel, das zum Tab gehört.  
  - Die ID muss mit dem `data-tab`-Wert des Buttons übereinstimmen.
  - Das aktive Panel erhält die Klasse `active`.

## Funktionsweise

- Beim Klick auf einen `.psource-tab`-Button wird nur innerhalb der aktuellen Tab-Gruppe gearbeitet:
  - Alle Tabs und Panels werden deaktiviert (`active` entfernt).
  - Der geklickte Tab und das zugehörige Panel werden aktiviert (`active` gesetzt).
- Mehrere Tab-Gruppen und verschachtelte Tabs funktionieren unabhängig voneinander.
- Es werden keine Seitenreloads ausgelöst (Buttons statt `<a>` verwenden).

## Beispiel-CSS

```css
.psource-tab-panel {
  display: none;
}
.psource-tab-panel.active {
  display: block;
}
.psource-tab.active {
  font-weight: bold;
}
```

## Hinweise

- IDs der Panels müssen eindeutig innerhalb der Seite sein.
- Die Zuordnung erfolgt über das `data-tab`-Attribut am Button.
- Das Script benötigt keine jQuery-UI und funktioniert rein mit Vanilla JS.
- Beim Laden wird automatisch der erste Tab/Panel aktiviert, falls keiner aktiv ist.

## JS-Datei

Die Logik befindet sich in  
`assets/psource-ui/tabs/tabs.js`

## Verschachtelung

Verschachtelte Tabs funktionieren automatisch, da immer nur innerhalb der aktuellen `.psource-tabs`-Gruppe gearbeitet wird.

---