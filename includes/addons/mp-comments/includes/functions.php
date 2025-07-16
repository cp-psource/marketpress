<?php
/**
 * Funktionen für das Bewertungssystem
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class MP_Ratings_Functions {
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Prüfe, ob der Benutzer bewerten darf
        add_filter('comments_open', array($this, 'check_if_user_can_rate'), 20, 2);
        
        // Füge eine Bearbeitungsfunktion hinzu
        add_action('wp_ajax_mp_edit_rating', array($this, 'edit_rating_ajax'));
        add_action('wp_ajax_nopriv_mp_edit_rating', array($this, 'edit_rating_ajax'));
        
        // Füge Schnellbewertungs-AJAX-Handler hinzu
        add_action('wp_ajax_mp_quick_rating', array($this, 'quick_rating_ajax'));
        add_action('wp_ajax_nopriv_mp_quick_rating', array($this, 'quick_rating_ajax'));
        
        // Füge Skripte für die Bearbeitungsfunktion hinzu
        add_action('wp_enqueue_scripts', array($this, 'enqueue_edit_scripts'));
        
        // Wir verwenden jetzt den Bearbeitungslink direkt im Template
        // add_filter('comment_reply_link', array($this, 'add_edit_link'), 10, 4);
    }
    
    /**
     * Prüfe, ob der Benutzer das Produkt bewerten darf
     */
    public function check_if_user_can_rate($open, $post_id) {
        // Nur für Produkte prüfen
        if (get_post_type($post_id) !== 'product') {
            return $open;
        }
        
        // Wenn Kommentare geschlossen sind, nichts tun
        if (!$open) {
            return $open;
        }
        
        // Hole die Einstellungen aus den MarketPress-Einstellungen
        $mp_settings = get_option('mp_settings', array());
        $allowed_users = isset($mp_settings['comments']['allowed_users']) ? $mp_settings['comments']['allowed_users'] : array('registered', 'guests');
        $require_purchase = isset($mp_settings['comments']['require_purchase']) ? $mp_settings['comments']['require_purchase'] : 'no';
        
        // Wenn der Benutzer nicht angemeldet ist
        if (!is_user_logged_in()) {
            // Prüfe, ob Gäste bewerten dürfen
            if (!in_array('guests', $allowed_users)) {
                return false;
            }
        } else {
            // Prüfe, ob registrierte Benutzer bewerten dürfen
            if (!in_array('registered', $allowed_users)) {
                return false;
            }
            
            // Prüfe, ob der Benutzer das Produkt gekauft haben muss
            if ($require_purchase === 'yes') {
                $user_id = get_current_user_id();
                if (!$this->user_has_purchased_product($user_id, $post_id)) {
                    return false;
                }
            }
        }
        
        return $open;
    }
    
    /**
     * Prüfe auf doppelte Bewertungen
     */
    public function check_for_duplicate_ratings($commentdata) {
        // Nur für Produkte prüfen
        if (get_post_type($commentdata['comment_post_ID']) !== 'product') {
            return $commentdata;
        }
        
        $args = array(
            'post_id' => $commentdata['comment_post_ID'],
            'meta_key' => 'rating',
            'count' => true
        );
        
        // Wenn der Benutzer angemeldet ist, nach Benutzer-ID filtern
        if (is_user_logged_in()) {
            $args['user_id'] = get_current_user_id();
        } else {
            // Für Gäste nach E-Mail filtern
            $args['author_email'] = $commentdata['comment_author_email'];
        }
        
        // Zähle die vorhandenen Bewertungen des Benutzers für dieses Produkt
        $existing_ratings = get_comments($args);
        
        if ($existing_ratings > 0) {
            wp_die(
                __('Du hast dieses Produkt bereits bewertet. Du kannst deine bestehende Bewertung bearbeiten, aber keine neue hinzufügen.', 'mp'),
                __('Doppelte Bewertung', 'mp'),
                array('back_link' => true)
            );
        }
        
        return $commentdata;
    }
    
    /**
     * Prüfe, ob ein Benutzer ein Produkt gekauft hat
     */
    public function user_has_purchased_product($user_id, $product_id) {
        global $wpdb;
        
        // Hole alle Bestellungen des Benutzers
        $orders = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'mp_order' 
            AND post_status IN ('order_paid', 'order_shipped', 'order_completed')
            AND post_author = %d",
            $user_id
        ));
        
        if (empty($orders)) {
            return false;
        }
        
        // Prüfe, ob eines der Produkte in einer der Bestellungen enthalten ist
        foreach ($orders as $order_id) {
            $order_items = get_post_meta($order_id, 'mp_order_items', true);
            if (!is_array($order_items)) {
                continue;
            }
            
            foreach ($order_items as $item) {
                if (isset($item['product_id']) && $item['product_id'] == $product_id) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * AJAX-Callback für die Bearbeitung von Bewertungen
     */
    public function edit_rating_ajax() {
        $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $comment_text = isset($_POST['comment_text']) ? sanitize_textarea_field($_POST['comment_text']) : '';
        
        // Sicherheitscheck
        if (!wp_verify_nonce($_POST['nonce'], 'edit_rating_' . $comment_id)) {
            wp_send_json_error(__('Sicherheitsüberprüfung fehlgeschlagen.', 'mp'));
            exit;
        }
        
        $comment = get_comment($comment_id);
        
        // Prüfe, ob der Kommentar existiert
        if (!$comment) {
            wp_send_json_error(__('Bewertung nicht gefunden.', 'mp'));
            exit;
        }
        
        // Prüfe, ob der Benutzer berechtigt ist
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if ($comment->user_id != $current_user->ID && !current_user_can('moderate_comments')) {
                wp_send_json_error(__('Du bist nicht berechtigt, diese Bewertung zu bearbeiten.', 'mp'));
                exit;
            }
        } else {
            // Für Gäste: Prüfe die E-Mail-Adresse im Cookie
            $comment_author_email_cookie = isset($_COOKIE['comment_author_email_' . COOKIEHASH]) ? sanitize_email($_COOKIE['comment_author_email_' . COOKIEHASH]) : '';
            if ($comment_author_email_cookie !== $comment->comment_author_email) {
                wp_send_json_error(__('Du bist nicht berechtigt, diese Bewertung zu bearbeiten.', 'mp'));
                exit;
            }
        }
        
        // Aktualisiere die Bewertung
        if ($rating >= 1 && $rating <= 5) {
            update_comment_meta($comment_id, 'rating', $rating);
            
            // Aktualisiere den Kommentartext
            if (empty($comment_text)) {
                // Wenn kein Kommentar eingegeben wurde, erstelle einen Standardtext basierend auf der Bewertung
                $rating_text = '';
                switch ($rating) {
                    case 1: $rating_text = __('Schlecht (1 Stern)', 'mp'); break;
                    case 2: $rating_text = __('Ausreichend (2 Sterne)', 'mp'); break;
                    case 3: $rating_text = __('Gut (3 Sterne)', 'mp'); break;
                    case 4: $rating_text = __('Sehr gut (4 Sterne)', 'mp'); break;
                    case 5: $rating_text = __('Ausgezeichnet (5 Sterne)', 'mp'); break;
                }
                $comment_text = sprintf(__('Bewertung: %s', 'mp'), $rating_text);
            }
            
            wp_update_comment(array(
                'comment_ID' => $comment_id,
                'comment_content' => $comment_text
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Bewertung erfolgreich aktualisiert.', 'mp'),
            'rating' => $rating,
            'comment_text' => $comment_text
        ));
        
        exit;
    }
    
    /**
     * AJAX-Handler für die Schnellbewertungsfunktion
     * Ermöglicht Bewertungen ohne Kommentar
     */
    public function quick_rating_ajax() {
        // Sicherheitscheck
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'mp_quick_rating_nonce')) {
            wp_send_json_error(array('message' => __('Sicherheitsprüfung fehlgeschlagen', 'mp')));
            exit;
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $is_quick_rating = isset($_POST['is_quick_rating']) && $_POST['is_quick_rating'] == 'true';
        
        // Prüfe, ob es ein gültiges Produkt ist
        if (!$post_id || get_post_type($post_id) !== 'product') {
            wp_send_json_error(array('message' => __('Ungültiges Produkt', 'mp')));
            exit;
        }
        
        // Prüfe, ob die Bewertung gültig ist
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Ungültige Bewertung', 'mp')));
            exit;
        }
        
        // Sammle Autorendaten
        $user = wp_get_current_user();
        
        if ($user->exists()) {
            $comment_author = $user->display_name;
            $comment_author_email = $user->user_email;
            $user_id = $user->ID;
        } else {
            $comment_author = isset($_POST['author']) ? sanitize_text_field($_POST['author']) : __('Anonym', 'mp');
            $comment_author_email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $user_id = 0;
        }
        
        // Prüfe auf doppelte Bewertungen
        $args = array(
            'post_id' => $post_id,
            'meta_key' => 'rating',
            'count' => true
        );
        
        if ($user_id) {
            $args['user_id'] = $user_id;
        } else if (!empty($comment_author_email)) {
            $args['author_email'] = $comment_author_email;
        }
        
        $existing_ratings = get_comments($args);
        
        if ($existing_ratings > 0) {
            wp_send_json_error(array('message' => __('Du hast dieses Produkt bereits bewertet', 'mp')));
            exit;
        }
        
        // Erstelle einen kurzen Standardkommentartext basierend auf der Bewertung
        $rating_text = '';
        switch ($rating) {
            case 1: $rating_text = __('Schlecht (1 Stern)', 'mp'); break;
            case 2: $rating_text = __('Ausreichend (2 Sterne)', 'mp'); break;
            case 3: $rating_text = __('Gut (3 Sterne)', 'mp'); break;
            case 4: $rating_text = __('Sehr gut (4 Sterne)', 'mp'); break;
            case 5: $rating_text = __('Ausgezeichnet (5 Sterne)', 'mp'); break;
        }
        
        $comment_content = isset($_POST['comment']) && !empty($_POST['comment']) 
            ? sanitize_textarea_field($_POST['comment']) 
            : sprintf(__('Bewertung: %s', 'mp'), $rating_text);
        
        // Kommentar-Daten vorbereiten
        $comment_data = array(
            'comment_post_ID' => $post_id,
            'comment_author' => $comment_author,
            'comment_author_email' => $comment_author_email,
            'comment_content' => $comment_content,
            'comment_type' => 'comment',
            'comment_parent' => 0,
            'user_id' => $user_id,
            'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
            'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
            'comment_date' => current_time('mysql'),
            'comment_approved' => 1,
        );
        
        // Kommentar einfügen
        $comment_id = wp_insert_comment($comment_data);
        
        if ($comment_id) {
            // Bewertung als Metadaten speichern
            add_comment_meta($comment_id, 'rating', $rating, true);
            
            // Erfolgsantwort senden
            wp_send_json_success(array(
                'message' => __('Bewertung erfolgreich gespeichert', 'mp'),
                'rating' => $rating
            ));
        } else {
            wp_send_json_error(array('message' => __('Fehler beim Speichern der Bewertung', 'mp')));
        }
        
        exit;
    }
    
    /**
     * Lade Scripts für die Bearbeitung
     */
    public function enqueue_edit_scripts() {
        if (is_singular('product') && comments_open()) {
            wp_enqueue_script('mp-edit-rating', MP_COMMENTS_PLUGIN_URL . 'assets/js/edit-rating.js', array('jquery'), '1.0.0', true);
            
            wp_localize_script('mp-edit-rating', 'mp_edit_rating', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'error_message' => __('Ein Fehler ist aufgetreten. Bitte versuche es erneut.', 'mp'),
                'rating_text' => array(
                    1 => __('Schlecht (1 Stern)', 'mp'),
                    2 => __('Ausreichend (2 Sterne)', 'mp'),
                    3 => __('Gut (3 Sterne)', 'mp'),
                    4 => __('Sehr gut (4 Sterne)', 'mp'),
                    5 => __('Ausgezeichnet (5 Sterne)', 'mp')
                )
            ));
        }
    }
    
    /**
     * Füge einen Einstellungslink auf der Addon-Seite hinzu
     */
    public function add_settings_link($links, $addon_class) {
        if ($addon_class === 'MP_MARKETPRESS_COMMENTS_Addon' || $addon_class === 'MP_Ratings_Addon') {
            $links[] = '<a href="' . admin_url('admin.php?page=mp-ratings-settings') . '">' . __('Einstellungen', 'mp') . '</a>';
        }
        return $links;
    }
    
    /**
     * Füge einen "Bearbeiten"-Link für Bewertungen hinzu
     */
    public function add_edit_link($reply_link, $args, $comment, $post) {
        // Nur für Produkte
        if (get_post_type($post) !== 'product') {
            return $reply_link;
        }
        
        // Prüfe, ob es eine Bewertung ist
        $rating = get_comment_meta($comment->comment_ID, 'rating', true);
        if (!$rating) {
            return $reply_link;
        }
        
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
        
        if ($can_edit) {
            $nonce = wp_create_nonce('edit_rating_' . $comment->comment_ID);
            $edit_link = '<a class="comment-edit-rating" href="#" data-comment-id="' . $comment->comment_ID . '" data-nonce="' . $nonce . '" data-rating="' . $rating . '">' . __('Bewertung bearbeiten', 'mp') . '</a>';
            $reply_link = $edit_link . ' ' . $reply_link;
        }
        
        return $reply_link;
    }
}

// Initialisiere die Bewertungsfunktionen
new MP_Ratings_Functions();
