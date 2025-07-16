<?php
/**
 * MarketPress Produktbewertungen Template
 * Überschreibt das Standard-WordPress-Kommentartemplate für Produkte
 */

// Sicherstellen, dass das Skript nicht direkt aufgerufen wird
if (!defined('ABSPATH')) exit;
?>

<div id="mp-product-reviews" class="mp-product-reviews">
    <h3 class="mp-reviews-title"><?php _e('Kundenbewertungen', 'mp'); ?></h3>

    <?php if (have_comments()) : ?>
        <div class="mp-reviews-list">
            <?php
            // Durchschnittliche Bewertung anzeigen
            global $post;
            $comments = get_comments(['post_id' => $post->ID, 'meta_key' => 'rating']);
            $ratings = array_map(function($comment) {
                return intval(get_comment_meta($comment->comment_ID, 'rating', true));
            }, $comments);

            if (!empty($ratings)) {
                $average = array_sum($ratings) / count($ratings);
                $count = count($ratings);
                
                // Sterne berechnen
                $full_stars = floor($average);
                $half_star = ($average - $full_stars) >= 0.5 ? 1 : 0;
                $empty_stars = 5 - $full_stars - $half_star;
                
                $stars = str_repeat('★', $full_stars);
                $stars .= $half_star ? '½' : '';
                $stars .= str_repeat('☆', $empty_stars);
                ?>
                <div class="mp-average-rating">
                    <div class="mp-rating-summary">
                        <span class="mp-rating-number"><?php echo number_format($average, 1); ?></span>
                        <span class="mp-rating-max">/5</span>
                    </div>
                    <div class="mp-rating-stars">
                        <span class="mp-stars-display"><?php echo $stars; ?></span>
                    </div>
                    <div class="mp-rating-count">
                        <?php echo sprintf(_n('%s Bewertung', '%s Bewertungen', $count, 'mp'), $count); ?>
                    </div>
                </div>
                <?php
            }
            ?>

            <ol class="mp-review-list">
                <?php
                wp_list_comments(array(
                    'style'       => 'ol',
                    'short_ping'  => true,
                    'avatar_size' => 50,
                    'callback'    => 'mp_custom_comment_template',
                ));
                ?>
            </ol>

            <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
            <nav class="mp-comment-navigation">
                <div class="mp-nav-previous"><?php previous_comments_link(__('Ältere Bewertungen', 'mp')); ?></div>
                <div class="mp-nav-next"><?php next_comments_link(__('Neuere Bewertungen', 'mp')); ?></div>
            </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!have_comments() && comments_open()) : ?>
        <p class="mp-no-reviews"><?php _e('Noch keine Bewertungen. Sei der Erste, der dieses Produkt bewertet!', 'mp'); ?></p>
    <?php endif; ?>

    <?php if (comments_open()) : ?>
        <?php
        $current_user_id = get_current_user_id();
        $post_id = get_the_ID();
        $has_already_rated = false;
        $user_rating_comment_id = 0;
        
        // Prüfen, ob der Benutzer bereits eine Bewertung abgegeben hat
        if ($current_user_id > 0) {
            $args = array(
                'user_id' => $current_user_id,
                'post_id' => $post_id,
                'meta_key' => 'rating',
                'count' => false
            );
            $existing_ratings = get_comments($args);
            
            if (!empty($existing_ratings)) {
                $has_already_rated = true;
                $user_rating_comment_id = $existing_ratings[0]->comment_ID;
            }
        } elseif (!empty($commenter['comment_author_email'])) {
            // Für nicht angemeldete Benutzer (Gäste) nach E-Mail-Adresse prüfen
            $args = array(
                'author_email' => $commenter['comment_author_email'],
                'post_id' => $post_id,
                'meta_key' => 'rating',
                'count' => false
            );
            $existing_ratings = get_comments($args);
            
            if (!empty($existing_ratings)) {
                $has_already_rated = true;
                $user_rating_comment_id = $existing_ratings[0]->comment_ID;
            }
        }
        
        if ($has_already_rated && $user_rating_comment_id > 0) :
            // Zeige einen Link zur Bewertung des Benutzers
            ?>
            <div class="mp-user-has-rated">
                <p><?php _e('Du hast dieses Produkt bereits bewertet.', 'mp'); ?></p>
                <a href="#comment-<?php echo $user_rating_comment_id; ?>" class="button mp-find-your-review">
                    <?php _e('Zu deiner Bewertung', 'mp'); ?>
                </a>
            </div>
            <script>
            jQuery(document).ready(function($) {
                // Scrolle zur Bewertung des Benutzers, wenn auf den Link geklickt wird
                $('.mp-find-your-review').on('click', function(e) {
                    e.preventDefault();
                    var target = $(this).attr('href');
                    var $target = $(target);
                    
                    if ($target.length) {
                        // Hebe die Bewertung des Benutzers kurz hervor
                        $('html, body').animate({
                            scrollTop: $target.offset().top - 100
                        }, 500, function() {
                            $target.addClass('mp-highlight-review');
                            setTimeout(function() {
                                $target.removeClass('mp-highlight-review');
                            }, 2000);
                        });
                    }
                });
            });
            </script>
            <style>
                .mp-user-has-rated {
                    margin: 20px 0;
                    padding: 15px;
                    background-color: #f0f0f0;
                    border-left: 4px solid #4CAF50;
                    border-radius: 4px;
                }
                .mp-find-your-review {
                    display: inline-block;
                    margin-top: 10px;
                    padding: 8px 15px;
                    background: #4CAF50;
                    color: white !important;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: 600;
                }
                .mp-find-your-review:hover {
                    background: #3e8e41;
                    text-decoration: none;
                }
                .mp-highlight-review {
                    animation: highlight-pulse 1.5s ease;
                }
                @keyframes highlight-pulse {
                    0% { background-color: transparent; }
                    30% { background-color: rgba(76, 175, 80, 0.2); }
                    100% { background-color: transparent; }
                }
            </style>
        <?php else : ?>
            <div id="mp-review-form">
                <h3 class="mp-review-form-title"><?php _e('Schreibe eine Bewertung', 'mp'); ?></h3>
                
                <?php
                $commenter = wp_get_current_commenter();
                $req = get_option('require_name_email');
                $aria_req = ($req ? " aria-required='true'" : '');
                
                $fields = array(
                    'author' => '<p class="comment-form-author"><label for="author">' . __('Name', 'mp') . ($req ? ' <span class="required">*</span>' : '') . '</label>' .
                        '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30"' . $aria_req . ' /></p>',
                    'email'  => '<p class="comment-form-email"><label for="email">' . __('E-Mail', 'mp') . ($req ? ' <span class="required">*</span>' : '') . '</label>' .
                        '<input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email']) . '" size="30"' . $aria_req . ' /></p>',
                );
                
                $comment_field = '<div class="mp-comment-form-rating">
                    <label for="rating">' . __('Deine Bewertung', 'mp') . ' <span class="required">*</span></label>
                    <div class="mp-rating-stars-select">
                        <p class="mp-rating-stars-description">' . __('Klicke auf einen Stern:', 'mp') . '</p>
                        <div class="mp-star-rating-container">
                            <input type="radio" id="mp-star5" name="rating" value="5" required />
                            <label for="mp-star5" title="5 Sterne">★</label>
                            <input type="radio" id="mp-star4" name="rating" value="4" />
                            <label for="mp-star4" title="4 Sterne">★</label>
                            <input type="radio" id="mp-star3" name="rating" value="3" />
                            <label for="mp-star3" title="3 Sterne">★</label>
                            <input type="radio" id="mp-star2" name="rating" value="2" />
                            <label for="mp-star2" title="2 Sterne">★</label>
                            <input type="radio" id="mp-star1" name="rating" value="1" />
                            <label for="mp-star1" title="1 Stern">★</label>
                        </div>
                        <div class="mp-rating-selection-text">' . __('Keine Bewertung ausgewählt', 'mp') . '</div>
                    </div>
                </div>
                <p class="comment-form-comment">
                    <label for="comment">' . __('Dein Kommentar', 'mp') . ' <span class="optional">(' . __('optional', 'mp') . ')</span></label>
                    <textarea id="comment" name="comment" cols="45" rows="8" aria-required="false"></textarea>
                </p>';
                
                comment_form(array(
                    'fields'               => $fields,
                    'comment_field'        => $comment_field,
                    'title_reply'          => '',
                    'title_reply_to'       => __('Auf Bewertung antworten', 'mp'),
                    'comment_notes_before' => '<p class="comment-notes">' . __('Deine E-Mail-Adresse wird nicht veröffentlicht. Erforderliche Felder sind mit * markiert.', 'mp') . '</p>',
                    'comment_notes_after'  => '',
                    'label_submit'         => __('Bewertung abschicken', 'mp')
                ));
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
/* Bewertungssystem Styling wird aus der externen CSS-Datei geladen */

.mp-reviews-title {
    font-size: 24px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

/* Durchschnittliche Bewertung */
.mp-average-rating {
    display: flex;
    align-items: center;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.mp-rating-summary {
    margin-right: 15px;
}

.mp-rating-number {
    font-size: 36px;
    font-weight: bold;
    color: #333;
}

.mp-rating-max {
    font-size: 18px;
    color: #666;
}

.mp-rating-stars {
    flex-grow: 1;
}

.mp-stars-display {
    font-size: 24px;
    color: #FFD700;
    letter-spacing: 2px;
}

.mp-rating-count {
    font-size: 14px;
    color: #666;
}

/* Bewertungsliste */
.mp-review-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.mp-review-list li {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.mp-review-list li:last-child {
    border-bottom: none;
}

/* Bewertungsformular */
.mp-review-form-title {
    margin-top: 40px;
    margin-bottom: 20px;
    font-size: 22px;
}

.mp-comment-form-rating {
    margin: 20px 0;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 5px;
}

.mp-comment-form-rating label {
    display: block;
    margin-bottom: 10px;
    font-weight: bold;
}

.mp-rating-stars-description {
    margin-bottom: 10px;
    font-style: italic;
    color: #666;
}

.mp-star-rating-container {
    direction: rtl;
    unicode-bidi: bidi-override;
    text-align: left;
    display: inline-block;
}

.mp-star-rating-container > input {
    display: none;
}

.mp-star-rating-container > label {
    display: inline-block;
    position: relative;
    width: 1.1em;
    font-size: 30px;
    color: #ccc;
    cursor: pointer;
    margin: 0 5px;
}

.mp-star-rating-container > label:hover,
.mp-star-rating-container > label:hover ~ label,
.mp-star-rating-container > input:checked ~ label {
    color: #FFD700;
}

.mp-rating-selection-text {
    margin-top: 10px;
    font-weight: bold;
    min-height: 20px;
}

.mp-no-reviews {
    font-style: italic;
    color: #666;
    margin: 30px 0;
}

.comment-form-author,
.comment-form-email,
.comment-form-comment {
    margin-bottom: 15px;
}

.comment-form-author label,
.comment-form-email label,
.comment-form-comment label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.comment-form-author input,
.comment-form-email input {
    width: 100%;
    max-width: 300px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.comment-form-comment textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.form-submit input[type="submit"] {
    background-color: #0073aa;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

.form-submit input[type="submit"]:hover {
    background-color: #005177;
}
</style>

<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
    // Bewertungssterne Interaktion
    var stars = document.querySelectorAll(".mp-star-rating-container input");
    var ratingText = document.querySelector(".mp-rating-selection-text");
    
    if (stars.length > 0 && ratingText) {
        stars.forEach(function(star) {
            star.addEventListener("change", function() {
                var rating = this.value;
                var text = "";
                switch(parseInt(rating)) {
                    case 1: text = "<?php echo esc_js(__('Schlecht (1 Stern)', 'mp')); ?>"; break;
                    case 2: text = "<?php echo esc_js(__('Ausreichend (2 Sterne)', 'mp')); ?>"; break;
                    case 3: text = "<?php echo esc_js(__('Gut (3 Sterne)', 'mp')); ?>"; break;
                    case 4: text = "<?php echo esc_js(__('Sehr gut (4 Sterne)', 'mp')); ?>"; break;
                    case 5: text = "<?php echo esc_js(__('Ausgezeichnet (5 Sterne)', 'mp')); ?>"; break;
                }
                ratingText.textContent = text;
            });
        });
    }
});
</script>
