# PSource Accordion – Dev-Dokumentation

## Struktur

Das Accordion besteht aus einer oder mehreren Gruppen mit folgendem HTML-Aufbau:

```html
<div class="psource-accordion">
  <div class="psource-accordion-item">
    <div class="psource-accordion-header">Titel</div>
    <div class="psource-accordion-content">Inhalt</div>
  </div>
  <!-- weitere Items ... -->
</div>
```

- **.psource-accordion**: Container für eine Accordion-Gruppe (beliebig viele pro Seite möglich, auch verschachtelt).
- **.psource-accordion-item**: Einzelnes auf- und zuklappbares Element.
- **.psource-accordion-header**: Klickbarer Bereich zum Öffnen/Schließen.
- **.psource-accordion-content**: Der ein- und ausklappbare Inhalt.

## Funktionsweise

- Beim Klick auf `.psource-accordion-header` wird das zugehörige `.psource-accordion-item` innerhalb der aktuellen Accordion-Gruppe geöffnet oder geschlossen (Klasse `active` wird getoggelt).
- Mehrere Accordions und verschachtelte Gruppen werden unabhängig voneinander behandelt.
- Das Öffnen/Schließen steuert ihr per CSS über die Klasse `.active` am Item.

## Beispiel-CSS

```css
.psource-accordion-content {
  display: none;
}
.psource-accordion-item.active .psource-accordion-content {
  display: block;
}
```

## Hinweise

- Es gibt keine Limitierung auf ein geöffnetes Item pro Gruppe (klassisches "Mehrfach-Accordion").
- Für ein "Single-Open"-Verhalten kann das Script leicht angepasst werden.
- Das Script benötigt keine jQuery-UI und funktioniert rein mit Vanilla JS.

## JS-Datei

Die Logik befindet sich in  
`assets/psource-ui/accordion/accordion.js`

## Verschachtelung

Verschachtelte Accordions funktionieren automatisch, da immer nur innerhalb der aktuellen `.psource-accordion`-Gruppe gearbeitet wird.

---
```# PSource Accordion – Dev-Dokumentation

## Struktur

Das Accordion besteht aus einer oder mehreren Gruppen mit folgendem HTML-Aufbau:

```html
<div class="psource-accordion">
  <div class="psource-accordion-item">
    <div class="psource-accordion-header">Titel</div>
    <div class="psource-accordion-content">Inhalt</div>
  </div>
  <!-- weitere Items ... -->
</div>
```

- **.psource-accordion**: Container für eine Accordion-Gruppe (beliebig viele pro Seite möglich, auch verschachtelt).
- **.psource-accordion-item**: Einzelnes auf- und zuklappbares Element.
- **.psource-accordion-header**: Klickbarer Bereich zum Öffnen/Schließen.
- **.psource-accordion-content**: Der ein- und ausklappbare Inhalt.

## Funktionsweise

- Beim Klick auf `.psource-accordion-header` wird das zugehörige `.psource-accordion-item` innerhalb der aktuellen Accordion-Gruppe geöffnet oder geschlossen (Klasse `active` wird getoggelt).
- Mehrere Accordions und verschachtelte Gruppen werden unabhängig voneinander behandelt.
- Das Öffnen/Schließen steuert ihr per CSS über die Klasse `.active` am Item.

## Beispiel-CSS

```css
.psource-accordion-content {
  display: none;
}
.psource-accordion-item.active .psource-accordion-content {
  display: block;
}
```

## Hinweise

- Es gibt keine Limitierung auf ein geöffnetes Item pro Gruppe (klassisches "Mehrfach-Accordion").
- Für ein "Single-Open"-Verhalten kann das Script leicht angepasst werden.
- Das Script benötigt keine jQuery-UI und funktioniert rein mit Vanilla JS.

## JS-Datei

Die Logik befindet sich in  
`assets/psource-ui/accordion/accordion.js`

## Verschachtelung

Verschachtelte Accordions funktionieren automatisch, da immer nur innerhalb der aktuellen `.psource-accordion`-Gruppe gearbeitet wird.

---