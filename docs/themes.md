---
layout: psource-theme
title: "MarketPress Themeing"
---

<h2 align="center" style="color:#38c2bb;">📚 MarketPress Themeing</h2>

<div class="menu">
  <a href="https://github.com/cp-psource/marketpress/discussions" style="color:#38c2bb;">💬 Forum</a>
  <a href="https://github.com/cp-psource/marketpress/releases" style="color:#38c2bb;">📝 Download</a>
  <a href="readme-en.html" style="color:#38c2bb;">📝 ENGLISCH</a>
</div>

Themeing MarketPress
-------------------------------------------------------------------------------

Es gibt zwei Methoden, wie du benutzerdefinierte Designs für deinen MarketPress-Shop erstellen kannst:

### Grundlegende Stile:

Grundlegende Stile bestehen aus einer CSS-Datei und einem optionalen Bilderordner, die auf den Shop-Seiten geladen und angewendet werden. Um einen grundlegenden Stil zu erstellen:

1. Erstelle eine CSS-Datei im Verzeichnis `/wp-content/marketpress-styles/` mit deinen benutzerdefinierten Stilen. Am einfachsten ist es, unser `default.css`-Theme zu kopieren und zu bearbeiten.
2. Füge den benutzerdefinierten Theme-Header mit dem Namen deines Themes oben in der CSS-Datei hinzu:
  ```css
  /*
  MarketPress Style: CUSTOMNAME
  */
  ```
  Durch das Hinzufügen dieses Headers wird dein benutzerdefiniertes Theme im Dropdown-Menü auf der Seite **"Store Settings -> Darstellung"** angezeigt, sodass du es auswählen kannst.
3. Optional kannst du auch ein Unterverzeichnis für deine CSS-Bilder im Ordner `/wp-content/marketpress-styles/` erstellen und mit relativen URLs wie `image-folder/my-image.jpg` auf Bilder verweisen.

### Erweiterte Themes:

MarketPress verwendet benutzerdefinierte Beitragstypen, um Produkte anzuzeigen. Das bedeutet, dass du das gleiche WP-Theme-Templating-System verwenden kannst, mit dem du vertraut bist. Wir stellen lediglich eine angepasste Teilmenge von Template-Funktionen bereit, sodass du dich nicht mit Post-Meta-Daten beschäftigen musst. Die Template-Funktionen sind vollständig dokumentiert und befinden sich in der Datei `/marketpress/includes/common/template-functions.php`.

#### Beispiel: Eine benutzerdefinierte Produktseite erstellen

1. Erstelle eine Kopie der Datei `page.php` in deinem Theme-Verzeichnis und benenne sie in `mp_product.php` um.
2. In `mp_product.php` solltest du die `mp_*`-Funktionen anstelle von `the_content()` verwenden. Eine Liste der für Produkte relevanten Funktionen findest du in der Datei `template-functions.php`.

MarketPress durchsucht deinen aktuellen Theme-Ordner nach spezifischen Vorlagendateien für Shop-Seiten. Hier sind mögliche Dateinamen für Shop-Vorlagen in der Reihenfolge:
 
  Single Product Page
    mp_product-PRODUCTNAME.php
    mp_product-PRODUCTID.php
    mp_product.php
    single-PRODUCT_POST_TYPE.php (post type may be "product" or "mp_product" depending on your site's settings)
    single.php
    index.php
    
  Store Page
    mp_store.php
    page.php
    index.php
    
  Cart/Checkout Page
    mp_checkout.php
    mp_cart.php
    page.php
    index.php
    
  Order Status Page
    mp_orderstatus.php
    page.php
    index.php
    
  Product List Page
    mp_productlist.php
    page.php
    index.php
    
  Product Category List Page
    mp_category-CATEGORYSLUG.php
    mp_category-CATEGORYID.php
    mp_category.php
    mp_taxonomy.php
    taxonomy-product_category-CATEGORYSLUG.php
    taxonomy-product_category.php    
    mp_productlist.php
    taxonomy.php
    page.php
    index.php
    
  Product Tag List Page
    mp_tag-TAGSLUG.php
    mp_tag-TAGID.php
    mp_tag.php
    mp_taxonomy.php
    taxonomy-product_tag-TAGSLUG.php
    taxonomy-product_tag.php    
    mp_productlist.php
    taxonomy.php
    page.php
    index.php

  --------------------------------------------------
  Global Listings - Will only work on main site/blog
  --------------------------------------------------
  Product List Page
    mp_global_products.php
    mp_productlist.php
    page.php
    index.php
    
  Product Category List Page
    mp_global_category-CATEGORYSLUG.php
    mp_global_category.php
    mp_global_category_list.php
    taxonomy-product_category-CATEGORYSLUG.php
    taxonomy-product_category.php    
    mp_productlist.php
    taxonomy.php
    page.php
    index.php
    
  Product Tag List Page
    mp_global_tag-TAGSLUG.php
    mp_global_tag.php
    mp_global_tag_list.php
    taxonomy-product_tag-TAGSLUG.php
    taxonomy-product_tag.php    
    mp_productlist.php
    taxonomy.php
    page.php
    index.php