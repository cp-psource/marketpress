/**
 * MarketPress Schnelle Bewertungen - AJAX-Handler
 * JavaScript für schnelle Sternebewertungen ohne Kommentarpflicht
 */
jQuery(document).ready(function($) {
    // Sterne-Bewertung ohne Kommentar - Initialisierung
    var $ratingContainer = $('.mp-rating-stars-select');
    var $ratingStars = $ratingContainer.find('input[name="rating"]');
    var $ratingText = $ratingContainer.find('.mp-rating-selection-text');
    var $commentField = $('.comment-form-comment');
    var $submitButton = $('#submit');
    var $quickRatingButton = $('<button>', {
        'type': 'button',
        'id': 'mp-quick-rating-submit',
        'class': 'button mp-quick-rating-button',
        'text': mp_ratings_i18n.quick_rating_button
    });
    var $commentForm = $('form#commentform');
    
    // Option zum schnellen Bewerten ohne Kommentar hinzufügen
    if ($ratingContainer.length && $commentField.length) {
        // Schnellbewertungs-Button hinzufügen
        $ratingContainer.append($quickRatingButton);
        
        // Hinweis zum optionalen Kommentar - Überprüfe, ob das Optional-Label bereits vorhanden ist
        if (!$commentField.find('label .optional').length) {
            $commentField.find('label').html(function(_, html) {
                return html.replace(/\*/, '') + ' <span class="optional">(' + mp_ratings_i18n.optional + ')</span>';
            });
        }
        $commentField.find('textarea').prop('required', false);
        
        // Live-Update der Sternebewertungsbeschreibung
        $ratingStars.on('change', function() {
            var rating = $(this).val();
            var ratingText = '';
            
            switch(parseInt(rating)) {
                case 1: ratingText = mp_ratings_i18n.rating_1; break;
                case 2: ratingText = mp_ratings_i18n.rating_2; break;
                case 3: ratingText = mp_ratings_i18n.rating_3; break;
                case 4: ratingText = mp_ratings_i18n.rating_4; break;
                case 5: ratingText = mp_ratings_i18n.rating_5; break;
            }
            
            // Aktualisiere die Sternbeschreibung
            $('.mp-rating-stars-description').html('<strong>' + mp_ratings_i18n.your_rating + ':</strong> ' + ratingText);
            $ratingText.text(ratingText);
        });
        
        // Schnellbewertungs-Event
        $quickRatingButton.on('click', function(e) {
            e.preventDefault();
            
            // Prüfen, ob eine Bewertung ausgewählt wurde
            var selectedRating = $ratingContainer.find('input[name="rating"]:checked').val();
            if (!selectedRating) {
                alert(mp_ratings_i18n.select_rating);
                return false;
            }
            
            // Formular-Daten für Ajax-Request sammeln
            var formData = {
                'action': 'mp_quick_rating',
                'post_id': mp_ratings_i18n.post_id,
                'rating': selectedRating,
                'security': mp_ratings_i18n.nonce,
                'is_quick_rating': true
            };
            
            // Name und E-Mail von den Eingabefeldern übernehmen, wenn vorhanden
            if ($('#author').length) {
                formData.author = $('#author').val();
                if ($('#author').prop('required') && !formData.author) {
                    alert(mp_ratings_i18n.required_name);
                    return false;
                }
            }
            
            if ($('#email').length) {
                formData.email = $('#email').val();
                if ($('#email').prop('required') && !formData.email) {
                    alert(mp_ratings_i18n.required_email);
                    return false;
                }
            }
            
            // Button-Status: Wird verarbeitet...
            $quickRatingButton.prop('disabled', true).text(mp_ratings_i18n.processing);
            
            // Ajax-Anfrage senden
            $.ajax({
                type: 'POST',
                url: mp_ratings_i18n.ajaxurl,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Erfolgreich - Benutzerbenachrichtigung anzeigen
                        $ratingContainer.html('<div class="mp-rating-success">' + response.data.message + '</div>');
                        $commentField.hide();
                        $submitButton.hide();
                        
                        // Nach 2 Sekunden die Seite neu laden, damit der Link zur eigenen Bewertung angezeigt wird
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                        
                        // Nach kurzer Verzögerung die Seite neu laden, um die Bewertung anzuzeigen
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        // Fehlermeldung anzeigen
                        alert(response.data.message || mp_ratings_i18n.error);
                        $quickRatingButton.prop('disabled', false).text(mp_ratings_i18n.quick_rating_button);
                    }
                },
                error: function() {
                    // Allgemeiner Fehler
                    alert(mp_ratings_i18n.error);
                    $quickRatingButton.prop('disabled', false).text(mp_ratings_i18n.quick_rating_button);
                }
            });
        });
    }
});
