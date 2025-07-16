/**
 * MarketPress Bewertungssystem - Bewertungen bearbeiten
 */

jQuery(document).ready(function($) {
    // Zeige das Bearbeitungsformular an, wenn der "Bearbeiten"-Link angeklickt wird
    $(document).on('click', '.comment-edit-rating', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var commentId = $link.data('comment-id');
        var nonce = $link.data('nonce');
        var currentRating = $link.data('rating');
        
        // Finde den Kommentar
        var $comment = $('#comment-' + commentId);
        var $commentContent = $comment.find('.mp-review-content');
        var originalText = $commentContent.text().trim();
        
        // Bewertungsformular erstellen
        var $editForm = $('<div class="mp-edit-rating-form"></div>');
        
        // Sternebewertung erstellen
        var $ratingContainer = $('<div class="mp-edit-rating-stars"></div>');
        $ratingContainer.append('<label>' + mp_edit_rating.rating_label + '</label>');
        
        var $starsContainer = $('<div class="mp-star-rating-edit"></div>');
        for (var i = 5; i >= 1; i--) {
            $starsContainer.append(
                '<input type="radio" id="edit-star' + i + '-' + commentId + '" name="edit-rating" value="' + i + '"' + 
                (i == currentRating ? ' checked' : '') + ' />' +
                '<label for="edit-star' + i + '-' + commentId + '" title="' + i + ' Sterne">★</label>'
            );
        }
        
        $ratingContainer.append($starsContainer);
        $ratingContainer.append('<div class="mp-edit-rating-text">' + mp_edit_rating.rating_text[currentRating] + '</div>');
        
        // Textfeld erstellen
        var $textContainer = $('<div class="mp-edit-comment-text"></div>');
        $textContainer.append('<label for="edit-comment-' + commentId + '">' + mp_edit_rating.comment_label + ' <span class="optional">(' + mp_edit_rating.optional_text + ')</span></label>');
        $textContainer.append('<textarea id="edit-comment-' + commentId + '" rows="5">' + originalText + '</textarea>');
        
        // Buttons erstellen
        var $buttons = $('<div class="mp-edit-buttons"></div>');
        $buttons.append('<button type="button" class="button save-rating">' + mp_edit_rating.save_button + '</button>');
        $buttons.append('<button type="button" class="button cancel-edit">' + mp_edit_rating.cancel_button + '</button>');
        
        // Alles zusammenfügen
        $editForm.append($ratingContainer);
        $editForm.append($textContainer);
        $editForm.append($buttons);
        
        // Formular einfügen und originalen Inhalt verstecken
        $commentContent.after($editForm);
        $commentContent.hide();
        
        // Event-Handler für Sternauswahl mit Live-Update
        $editForm.find('input[name="edit-rating"]').on('change', function() {
            var value = $(this).val();
            var ratingText = mp_edit_rating.rating_text[value];
            
            // Text im Formular aktualisieren
            $editForm.find('.mp-edit-rating-text').text(ratingText);
            
            // Live-Vorschau der neuen Bewertung
            var $stars = $comment.find('.mp-review-stars');
            var $ratingText = $comment.find('.mp-review-rating-text');
            
            // Stern-Anzeige aktualisieren
            if ($stars.length) {
                var filledStars = '★'.repeat(value);
                var emptyStars = '☆'.repeat(5 - value);
                $stars.html('<span class="preview-stars">' + filledStars + emptyStars + '</span>');
            }
            
            // Rating-Text aktualisieren
            if ($ratingText.length) {
                var ratingLabel = '';
                switch (parseInt(value)) {
                    case 1: ratingLabel = mp_edit_rating.rating_label_1; break;
                    case 2: ratingLabel = mp_edit_rating.rating_label_2; break;
                    case 3: ratingLabel = mp_edit_rating.rating_label_3; break;
                    case 4: ratingLabel = mp_edit_rating.rating_label_4; break;
                    case 5: ratingLabel = mp_edit_rating.rating_label_5; break;
                }
                $ratingText.html('<span class="preview-rating">' + ratingLabel + ' (' + value + '/5)</span>');
            }
        });
        
        // Event-Handler für Abbrechen-Button
        $editForm.find('.cancel-edit').on('click', function() {
            // Stelle die ursprüngliche Bewertungsanzeige wieder her
            var $stars = $comment.find('.mp-review-stars');
            var $ratingText = $comment.find('.mp-review-rating-text');
            
            // Stern-Anzeige zurücksetzen
            if ($stars.length) {
                var filledStars = '★'.repeat(currentRating);
                var emptyStars = '☆'.repeat(5 - currentRating);
                $stars.html(filledStars + emptyStars);
            }
            
            // Rating-Text zurücksetzen
            if ($ratingText.length) {
                var ratingLabel = '';
                switch (parseInt(currentRating)) {
                    case 1: ratingLabel = mp_edit_rating.rating_label_1; break;
                    case 2: ratingLabel = mp_edit_rating.rating_label_2; break;
                    case 3: ratingLabel = mp_edit_rating.rating_label_3; break;
                    case 4: ratingLabel = mp_edit_rating.rating_label_4; break;
                    case 5: ratingLabel = mp_edit_rating.rating_label_5; break;
                }
                $ratingText.html(ratingLabel + ' (' + currentRating + '/5)');
            }
            
            $editForm.remove();
            $commentContent.show();
        });
        
        // Event-Handler für Speichern-Button
        $editForm.find('.save-rating').on('click', function() {
            var newRating = $editForm.find('input[name="edit-rating"]:checked').val();
            var newText = $editForm.find('textarea').val();
            
            // AJAX-Anfrage, um die Bewertung zu aktualisieren
            $.ajax({
                url: mp_edit_rating.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mp_edit_rating',
                    comment_id: commentId,
                    rating: newRating,
                    comment_text: newText,
                    nonce: nonce
                },
                beforeSend: function() {
                    $editForm.find('.save-rating').prop('disabled', true).text(mp_edit_rating.saving);
                },
                success: function(response) {
                    if (response.success) {
                        // Aktualisiere die Anzeige der Bewertung
                        $commentContent.text(newText);
                        
                        // Aktualisiere die Sternenanzeige
                        var $stars = $comment.find('.mp-review-stars');
                        if ($stars.length) {
                            var filledStars = '★'.repeat(newRating);
                            var emptyStars = '☆'.repeat(5 - newRating);
                            $stars.html(filledStars + emptyStars);
                        }
                        
                        // Aktualisiere den Bewertungstext
                        var $ratingText = $comment.find('.mp-review-rating-text');
                        if ($ratingText.length) {
                            var ratingLabel = '';
                            switch (parseInt(newRating)) {
                                case 1: ratingLabel = mp_edit_rating.rating_label_1; break;
                                case 2: ratingLabel = mp_edit_rating.rating_label_2; break;
                                case 3: ratingLabel = mp_edit_rating.rating_label_3; break;
                                case 4: ratingLabel = mp_edit_rating.rating_label_4; break;
                                case 5: ratingLabel = mp_edit_rating.rating_label_5; break;
                            }
                            $ratingText.html(ratingLabel + ' (' + newRating + '/5)');
                        }
                        
                        // Aktualisiere die Daten im Link
                        $link.data('rating', newRating);
                        
                        // Zeige eine Erfolgsmeldung
                        var $message = $('<div class="mp-edit-success">' + response.data.message + '</div>');
                        $commentContent.after($message);
                        
                        // Entferne das Formular und zeige den aktualisierten Inhalt
                        $editForm.remove();
                        $commentContent.show();
                        
                        // Entferne die Erfolgsmeldung nach 3 Sekunden
                        setTimeout(function() {
                            $message.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        // Zeige eine Fehlermeldung
                        alert(response.data || mp_edit_rating.error_message);
                    }
                },
                error: function() {
                    alert(mp_edit_rating.error_message);
                },
                complete: function() {
                    $editForm.find('.save-rating').prop('disabled', false).text(mp_edit_rating.save_button);
                }
            });
        });
    });
});
