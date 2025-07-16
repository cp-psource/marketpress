<?php
/**
 * MarketPress Produktbewertungen - Bewertungs-Callback-Funktion
 * Anzeige einer einzelnen Produktbewertung
 */

/**
 * Custom Comment Callback für Produktbewertungen
 */
function mp_custom_comment_template($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    $rating = get_comment_meta($comment->comment_ID, 'rating', true);
    ?>
    <li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
        <article class="mp-review">
            <div class="mp-review-meta">
                <div class="mp-review-author">
                    <?php echo get_avatar($comment, 60); ?>
                    <div class="mp-author-info">
                        <div class="mp-author-name"><?php comment_author(); ?></div>
                        <div class="mp-review-date">
                            <time datetime="<?php echo get_comment_date('c'); ?>">
                                <?php echo get_comment_date(); ?>
                            </time>
                        </div>
                    </div>
                </div>
                
                <?php if ($rating) : ?>
                <div class="mp-review-rating">
                    <?php
                    // Sterne mit leeren Sternen auffüllen
                    $filled_stars = str_repeat('★', $rating);
                    $empty_stars = str_repeat('☆', 5 - $rating);
                    
                    // Bewertungslabel
                    $rating_label = '';
                    switch ($rating) {
                        case 1: $rating_label = __('Schlecht', 'mp'); break;
                        case 2: $rating_label = __('Ausreichend', 'mp'); break;
                        case 3: $rating_label = __('Gut', 'mp'); break;
                        case 4: $rating_label = __('Sehr gut', 'mp'); break;
                        case 5: $rating_label = __('Ausgezeichnet', 'mp'); break;
                    }
                    ?>
                    <div class="mp-review-stars"><?php echo $filled_stars . $empty_stars; ?></div>
                    <div class="mp-review-rating-text"><?php echo $rating_label; ?> (<?php echo $rating; ?>/5)</div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="mp-review-content">
                <?php comment_text(); ?>
            </div>
            
            <div class="mp-review-actions">
                <?php 
                // Nur den Bearbeitungslink anzeigen - kein Antwort-Link
                $rating = get_comment_meta($comment->comment_ID, 'rating', true);
                $can_edit = false;
                
                // Prüfe, ob der Benutzer die Bewertung bearbeiten darf
                if (is_user_logged_in()) {
                    $current_user = wp_get_current_user();
                    if ($comment->user_id == $current_user->ID || current_user_can('moderate_comments')) {
                        $can_edit = true;
                    }
                } else {
                    // Für Gäste: Prüfe die E-Mail-Adresse im Cookie
                    $comment_author_email_cookie = isset($_COOKIE['comment_author_email_' . COOKIEHASH]) ? sanitize_email($_COOKIE['comment_author_email_' . COOKIEHASH]) : '';
                    if ($comment_author_email_cookie === $comment->comment_author_email) {
                        $can_edit = true;
                    }
                }
                
                if ($can_edit && $rating) {
                    $nonce = wp_create_nonce('edit_rating_' . $comment->comment_ID);
                    echo '<a class="comment-edit-rating" href="#" data-comment-id="' . $comment->comment_ID . '" data-nonce="' . $nonce . '" data-rating="' . $rating . '">' . __('Bewertung bearbeiten', 'mp') . '</a>';
                }
                ?>
            </div>
        </article>
    
    <style>
    /* Einzelne Bewertung Styling */
    .mp-review {
        padding: 15px 0;
    }
    
    .mp-review-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    
    .mp-review-author {
        display: flex;
        align-items: center;
    }
    
    .mp-review-author img {
        border-radius: 50%;
        margin-right: 10px;
    }
    
    .mp-author-info {
        display: flex;
        flex-direction: column;
    }
    
    .mp-author-name {
        font-weight: bold;
    }
    
    .mp-review-date {
        color: #666;
        font-size: 0.9em;
    }
    
    .mp-review-rating {
        text-align: right;
    }
    
    .mp-review-stars {
        font-size: 18px;
        color: #FFD700;
        letter-spacing: 1px;
    }
    
    .mp-review-rating-text {
        font-size: 0.9em;
        color: #666;
    }
    
    .mp-review-content {
        margin-left: 70px;
    }
    
    /* Bewertungsaktionen Styling */
    .mp-review-actions {
        text-align: right;
        font-size: 0.9em;
        margin-top: 10px;
    }
    
    .mp-review-actions a {
        margin-left: 10px;
        text-decoration: none;
        color: #0073aa;
    }
    
    .mp-review-actions a:hover {
        color: #00a0d2;
        text-decoration: underline;
    }
    </style>
    <?php
}
