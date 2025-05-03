<?php
/*
Plugin Name: MarketPress Erlaube Bewertungen
Version: 1.0
Plugin URI: https://github.com/cp-psource/marketpress
Description: Ein Add-On, mit dem Bewertungen (1-5 Sterne) zu Produkten hinzugefügt werden können.
Author: DerN3rd
Author URI: https://github.com/cp-psource
*/

add_action('init', 'shurf_wpml_marketpress_init');
function shurf_wpml_marketpress_init() {
    add_post_type_support('product', 'comments');
}

// Bewertungsfeld zum Kommentarformular hinzufügen
add_filter('comment_form_fields', 'mp_add_rating_field');
function mp_add_rating_field($fields) {
    if (get_post_type() === 'product') {
        $fields['rating'] = '<p class="comment-form-rating">
            <label for="rating">' . __('Bewertung', 'mp') . ' <span class="required">*</span></label>
            <select name="rating" id="rating" required>
                <option value="">' . __('Wähle eine Bewertung', 'mp') . '</option>
                <option value="1">1 Stern</option>
                <option value="2">2 Sterne</option>
                <option value="3">3 Sterne</option>
                <option value="4">4 Sterne</option>
                <option value="5">5 Sterne</option>
            </select>
        </p>';
    }
    return $fields;
}

// Bewertung speichern
add_action('comment_post', 'mp_save_comment_rating', 10, 3);
function mp_save_comment_rating($comment_id, $comment_approved, $commentdata) {
    if (isset($_POST['rating']) && get_post_type($commentdata['comment_post_ID']) === 'product') {
        $rating = intval($_POST['rating']);
        if ($rating >= 1 && $rating <= 5) {
            add_comment_meta($comment_id, 'rating', $rating, true);
        }
    }
}

// Bewertung im Kommentar anzeigen
add_filter('comment_text', 'mp_display_comment_rating');
function mp_display_comment_rating($comment_text) {
    if (get_post_type(get_comment($comment_ID)->comment_post_ID) === 'product') {
        $rating = get_comment_meta(get_comment_ID(), 'rating', true);
        if ($rating) {
            $stars = str_repeat('⭐', $rating);
            $comment_text = '<p class="comment-rating">' . $stars . '</p>' . $comment_text;
        }
    }
    return $comment_text;
}

// Durchschnittliche Bewertung berechnen und anzeigen
add_action('woocommerce_single_product_summary', 'mp_display_average_rating', 15);
function mp_display_average_rating() {
    global $post;
    if ($post->post_type === 'product') {
        $comments = get_comments(['post_id' => $post->ID, 'meta_key' => 'rating']);
        $ratings = array_map(function($comment) {
            return intval(get_comment_meta($comment->comment_ID, 'rating', true));
        }, $comments);

        if (!empty($ratings)) {
            $average = array_sum($ratings) / count($ratings);
            echo '<p class="average-rating">' . __('Durchschnittliche Bewertung:', 'mp') . ' ' . number_format($average, 1) . ' ⭐</p>';
        } else {
            echo '<p class="average-rating">' . __('Noch keine Bewertungen.', 'mp') . '</p>';
        }
    }
}
