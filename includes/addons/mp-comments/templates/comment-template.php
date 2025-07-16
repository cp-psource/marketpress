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
    </style>
    <?php
}
