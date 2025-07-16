/**
 * MarketPress Bewertungssystem
 * JavaScript für Produktbewertungen
 */

jQuery(document).ready(function($) {
    // Verhindere, dass der Textcursor beim Klicken in Nicht-Eingabefeldern erscheint
    $('.mp-product-reviews').on('mousedown', function(e) {
        // Erlaube Textauswahl nur in Eingabefeldern und bestimmten Elementen
        if (
            !$(e.target).is('input') && 
            !$(e.target).is('textarea') && 
            !$(e.target).is('.mp-review-content') &&
            !$(e.target).is('.mp-author-name') &&
            !$(e.target).is('.mp-review-date') &&
            !$(e.target).closest('form').length &&
            !$(e.target).is('label[for]')
        ) {
            e.preventDefault();
        }
    });

    // Sternebewertung Interaktion
    var $stars = $('.mp-star-rating-container input');
    var $ratingText = $('.mp-rating-selection-text');
    
    if ($stars.length && $ratingText.length) {
        $stars.on('change', function() {
            var rating = $(this).val();
            var text = '';
            
            switch(parseInt(rating)) {
                case 1:
                    text = mp_ratings_i18n.rating_1;
                    break;
                case 2:
                    text = mp_ratings_i18n.rating_2;
                    break;
                case 3:
                    text = mp_ratings_i18n.rating_3;
                    break;
                case 4:
                    text = mp_ratings_i18n.rating_4;
                    break;
                case 5:
                    text = mp_ratings_i18n.rating_5;
                    break;
            }
            
            $ratingText.text(text);
        });
        
        // Visuelle Verbesserung: Hover-Effekte für Sterne mit optimierter Performance
        $('.mp-star-rating-container label').hover(
            function() {
                $(this).css('transform', 'scale(1.2)');
            },
            function() {
                $(this).css('transform', 'scale(1)');
            }
        );
    }
    
    // Bewertungsformular Validierung
    $('#mp-review-form form').on('submit', function(e) {
        var $ratingInputs = $(this).find('input[name="rating"]:checked');
        
        if ($ratingInputs.length === 0) {
            e.preventDefault();
            alert(mp_ratings_i18n.select_rating);
            return false;
        }
        
        return true;
    });
    
    // Zusätzliche Funktion, um Cursor-Probleme zu verhindern
    // Diese Funktion verhindert, dass der Fokus auf nicht-interaktive Elemente gesetzt wird
    $('body').on('mousedown', '.mp-rating-stars, .mp-stars-display, .mp-review-stars, .rating-stars', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Verhindere, dass der Tab-Fokus auf nicht-interaktive Elemente gesetzt wird
    $('.mp-product-reviews').find('div, p, span').not('input, textarea, select, button, a, label').attr('tabindex', '-1');
});
