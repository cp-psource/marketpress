<?php
/**
 * MarketPress Erlaube Bewertungen Addon
 */
class MP_MARKETPRESS_COMMENTS_Addon {
    /**
     * Pfad zum Addon-Verzeichnis
     *
     * @var string
     */
    public $plugin_dir;

    /**
     * URL zum Addon-Verzeichnis
     *
     * @var string
     */
    public $plugin_url;
    
    /**
     * Refers to a single instance of the class
     *
     * @since 3.0
     * @access private
     * @var object
     */
    private static $_instance = null;
    
    /**
     * Gets the single instance of the class
     *
     * @since 3.0
     * @access public
     * @return object
     */
    public static function get_instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new MP_MARKETPRESS_COMMENTS_Addon();
        }
        return self::$_instance;
    }

    /**
     * Konstruktor
     * @access private
     */
    private function __construct() {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        // Konstanten definieren für die Abwärtskompatibilität
        if (!defined('MP_COMMENTS_PLUGIN_DIR')) {
            define('MP_COMMENTS_PLUGIN_DIR', $this->plugin_dir);
        }
        if (!defined('MP_COMMENTS_PLUGIN_URL')) {
            define('MP_COMMENTS_PLUGIN_URL', $this->plugin_url);
        }

        // Erforderliche Dateien einbinden
        require_once $this->plugin_dir . 'templates/comment-template.php';
        require_once $this->plugin_dir . 'includes/functions.php';
        
        // Hooks für Admin und Einstellungen
        if (is_admin()) {
            // Initialisiere die Settings-Metaboxen nur wenn wir auf der Einstellungsseite dieses Addons sind
            if (isset($_GET['page']) && $_GET['page'] == 'store-settings-addons' && 
                isset($_GET['addon']) && $_GET['addon'] == 'MP_MARKETPRESS_COMMENTS_Addon') {
                add_action('init', array($this, 'init_settings_metaboxes'));
            }
        }
        
        // Hooks initialisieren
        add_action('init', array($this, 'init'), 20);
    }
    
    /**
     * Initialisiere die Addon-Funktionalität
     */
    public function init() {
        // Hooks für die Integration der Produktbewertungen initialisieren
        $this->init_hooks();
    }
    
    /**
     * Initialisiere Settings Metaboxes
     * 
     * @since 1.0
     * @access public
     * @action init
     */
    public function init_settings_metaboxes() {
        $metabox = new WPMUDEV_Metabox(array(
            'id'          => 'mp-comments-settings-metabox',
            'title'       => __('Bewertungseinstellungen', 'mp'),
            'page_slugs'  => array('store-settings-addons'),
            'option_name' => 'mp_settings',
        ));
        
        // Wer darf Bewertungen abgeben?
        $metabox->add_field('checkbox_group', array(
            'name'          => 'comments[allowed_users]',
            'label'         => array('text' => __('Wer darf Bewertungen abgeben?', 'mp')),
            'options'       => array(
                'registered' => __('Registrierte Benutzer', 'mp'),
                'guests'     => __('Gäste', 'mp'),
            ),
            'default_value' => array('registered', 'guests'),
        ));
        
        // Müssen Käufer das Produkt gekauft haben?
        $metabox->add_field('radio_group', array(
            'name'          => 'comments[require_purchase]',
            'label'         => array('text' => __('Nur Käufer können bewerten', 'mp')),
            'desc'          => __('Wenn aktiviert, können nur Benutzer, die das Produkt gekauft haben, eine Bewertung abgeben.', 'mp'),
            'default_value' => 'no',
            'options'       => array(
                'no'  => __('Nein', 'mp'),
                'yes' => __('Ja', 'mp'),
            ),
        ));
    }


    
    /**
     * Deaktiviere Addon
     */
    public function deactivate() {
        // Bereinigungsaktionen bei der Deaktivierung ausführen
        flush_rewrite_rules();
    }
    
    /**
     * Initialisiere Hooks
     */
    private function init_hooks() {

        // Kommentarunterstützung für Produkte aktivieren
        add_action('init', array($this, 'enable_product_comments'));
        
        // Prüfen, ob eine Bewertung abgegeben wurde
        add_filter('preprocess_comment', array($this, 'verify_comment_rating'));
        
        // Bewertung speichern
        add_action('comment_post', array($this, 'save_comment_rating'), 10, 3);
        
        // Assets und UI-Verbesserungen laden
        add_action('wp_enqueue_scripts', array($this, 'load_rating_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_rating_scripts'));
        
        // Admin-Kommentarspalte für Bewertungen
        add_filter('manage_edit-comments_columns', array($this, 'add_comment_rating_column'));
        add_action('manage_comments_custom_column', array($this, 'comment_rating_column_content'), 10, 2);
        
        // Kommentar-Template überschreiben
        add_filter('comments_template', array($this, 'override_comments_template'));
        
        // Kommentarformular in die Produktseite einfügen
        add_action('mp_product_description_end', array($this, 'display_comments_template'), 20);
        add_action('mp_single_product_end', array($this, 'display_comments_template'), 20);
        
        // Sicherstellen, dass Kommentare für Produkte aktiviert sind mit höherer Priorität als die MarketPress-Kernfunktion (die 10 hat)
        add_filter('comments_open', array($this, 'enable_comments_for_products'), 20, 2);
        
        // Entferne die standard WordPress Comments-Metabox für Produkte und füge eine Bewertungs-Metabox hinzu
        add_action('add_meta_boxes', array($this, 'replace_comments_metabox'), 10);
        
        // Entferne die Diskussions-Metabox für Produkte (nicht benötigt)
        add_action('admin_menu', array($this, 'remove_discussion_metabox'));
        
        // Für die Korrektur des 404-Fehlers der Schriftarten
        add_action('admin_enqueue_scripts', array($this, 'load_admin_fonts'));
    }
    
    /**
     * Aktiviere Kommentarunterstützung für Produkte
     */
    public function enable_product_comments() {
        add_post_type_support('product', 'comments');
    }
    
    /**
     * Prüfe, ob eine Bewertung abgegeben wurde und ob bereits eine Bewertung existiert
     */
    public function verify_comment_rating($commentdata) {
        // Nur für Produkte prüfen und nur wenn ein Rating-Feld im Formular vorhanden war
        if (get_post_type($commentdata['comment_post_ID']) === 'product' && isset($_POST['rating'])) {
            // Prüfe auf doppelte Bewertungen (nur wenn eine Bewertung abgegeben wurde)
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
            
            // Stelle sicher, dass eine Bewertung abgegeben wurde, wenn das Feld vorhanden ist
            if (empty($_POST['rating'])) {
                wp_die(__('Fehler: Bitte wähle eine Bewertung aus.', 'mp'), __('Bewertung fehlt', 'mp'), array('back_link' => true));
            }
            
            // Wenn kein Kommentartext eingegeben wurde, erstellen wir einen Standardtext basierend auf der Bewertung
            if (empty($commentdata['comment_content'])) {
                $rating = intval($_POST['rating']);
                $rating_text = '';
                switch ($rating) {
                    case 1: $rating_text = __('Schlecht (1 Stern)', 'mp'); break;
                    case 2: $rating_text = __('Ausreichend (2 Sterne)', 'mp'); break;
                    case 3: $rating_text = __('Gut (3 Sterne)', 'mp'); break;
                    case 4: $rating_text = __('Sehr gut (4 Sterne)', 'mp'); break;
                    case 5: $rating_text = __('Ausgezeichnet (5 Sterne)', 'mp'); break;
                }
                $commentdata['comment_content'] = sprintf(__('Bewertung: %s', 'mp'), $rating_text);
            }
        }
        
        return $commentdata;
    }
    
    /**
     * Bewertung speichern
     */
    public function save_comment_rating($comment_id, $comment_approved, $commentdata) {
        if (isset($_POST['rating']) && get_post_type($commentdata['comment_post_ID']) === 'product') {
            $rating = intval($_POST['rating']);
            if ($rating >= 1 && $rating <= 5) {
                add_comment_meta($comment_id, 'rating', $rating, true);
            }
        }
    }
    
    /**
     * Kommentarformular in die Produktseite einfügen
     */
    public function display_comments_template() {
        global $post;
        if (get_post_type() === 'product') {
            // Unser eigenes Bewertungstemplate verwenden
            include_once $this->plugin_dir . 'templates/comments.php';
        }
    }
    
    /**
     * Sicherstellen, dass Kommentare für Produkte aktiviert sind
     * Dies ist ein wichtiger Hook, der sicherstellt, dass Kommentare für Produkte immer aktiviert sind,
     * unabhängig von den WordPress-Einstellungen
     */
    public function enable_comments_for_products($open, $post_id) {
        $post_type = get_post_type($post_id);
        if ($post_type === 'product') {
            // Aktiviere Kommentare für alle Produkte, unabhängig von den WordPress-Einstellungen
            return true;
        }
        return $open;
    }

    /**
     * Füge eine Bewertungsspalte zur Kommentar-Admin-Ansicht hinzu
     */
    public function add_comment_rating_column($columns) {
        $columns['rating'] = __('Bewertung', 'mp');
        return $columns;
    }

    /**
     * Fülle die Bewertungsspalte mit Daten
     */
    public function comment_rating_column_content($column, $comment_ID) {
        if ($column !== 'rating') return;
        
        $comment = get_comment($comment_ID);
        if (get_post_type($comment->comment_post_ID) !== 'product') return;
        
        $rating = get_comment_meta($comment_ID, 'rating', true);
        if ($rating) {
            echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . ' (' . $rating . '/5)';
        } else {
            echo '–';
        }
    }

    /**
     * Enqueue Frontend-Skripte für bessere Darstellung
     */
    public function enqueue_rating_scripts() {
        if (is_singular('product')) {
            // Inline-CSS für bessere Sternbewertung im Header
            wp_add_inline_style('mp-style', '
                .comment-rating {
                    display: flex;
                    align-items: center;
                    margin-bottom: 15px;
                    background: #f9f9f9;
                    padding: 10px;
                    border-radius: 5px;
                    font-weight: bold;
                }
                .rating-stars {
                    color: #FFD700;
                    font-size: 1.3em;
                    margin-right: 10px;
                }
                .rating-score {
                    margin-right: 5px;
                    font-weight: bold;
                }
                .rating-label {
                    color: #666;
                }
                .average-rating {
                    display: flex;
                    align-items: center;
                    margin: 20px 0;
                    padding: 15px;
                    background: #f5f5f5;
                    border-radius: 8px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                }
                .rating-count {
                    margin-left: 10px;
                    color: #666;
                }
                
                /* Schnellbewertungs-Button Styling */
                .mp-quick-rating-button {
                    display: inline-block;
                    margin-top: 15px;
                    padding: 8px 15px;
                    background: #4CAF50;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: background-color 0.2s ease;
                }
                .mp-quick-rating-button:hover {
                    background: #3e8e41;
                }
                .mp-quick-rating-button:disabled {
                    background: #cccccc;
                    cursor: not-allowed;
                }
                .comment-form-comment .optional {
                    color: #666;
                    font-size: 0.9em;
                    font-style: italic;
                    font-weight: normal;
                }
                .mp-rating-success {
                    padding: 15px;
                    background-color: #dff0d8;
                    border: 1px solid #d6e9c6;
                    color: #3c763d;
                    border-radius: 4px;
                    text-align: center;
                    font-weight: bold;
                }
                
                /* Live-Vorschau Styling */
                .preview-stars, .preview-rating {
                    animation: rating-preview-pulse 1s infinite alternate;
                    font-weight: bold;
                }
                
                @keyframes rating-preview-pulse {
                    from { opacity: 0.8; }
                    to { opacity: 1; }
                }
                
                .mp-edit-success {
                    padding: 10px;
                    background-color: #dff0d8;
                    border: 1px solid #d6e9c6;
                    color: #3c763d;
                    border-radius: 4px;
                    margin: 10px 0;
                    text-align: center;
                }
            ');
            
            // Lade das Script für Schnellbewertungen
            wp_enqueue_script('mp-quick-ratings', MP_COMMENTS_PLUGIN_URL . 'assets/js/quick-ratings.js', array('jquery'), '1.0', true);
            
            // Nonce für AJAX-Sicherheit
            $post_id = get_the_ID();
            wp_localize_script('mp-quick-ratings', 'mp_ratings_i18n', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'post_id' => $post_id,
                'nonce' => wp_create_nonce('mp_quick_rating_nonce'),
                'rating_1' => __('Schlecht (1 Stern)', 'mp'),
                'rating_2' => __('Ausreichend (2 Sterne)', 'mp'),
                'rating_3' => __('Gut (3 Sterne)', 'mp'),
                'rating_4' => __('Sehr gut (4 Sterne)', 'mp'),
                'rating_5' => __('Ausgezeichnet (5 Sterne)', 'mp'),
                'select_rating' => __('Bitte wähle eine Bewertung aus.', 'mp'),
                'quick_rating_button' => __('Nur Sterne bewerten', 'mp'),
                'processing' => __('Wird gespeichert...', 'mp'),
                'error' => __('Ein Fehler ist aufgetreten.', 'mp'),
                'required_name' => __('Bitte gib deinen Namen ein.', 'mp'),
                'required_email' => __('Bitte gib deine E-Mail-Adresse ein.', 'mp'),
                'optional' => __('optional', 'mp'),
                'your_rating' => __('Deine Bewertung', 'mp'),
            ));
        }
    }

    /**
     * Überschreibe das Standard-Kommentar-Template für Produktseiten
     */
    public function override_comments_template($template) {
        if (get_post_type() === 'product') {
            return $this->plugin_dir . 'templates/comments.php';
        }
        return $template;
    }

    /**
     * Lade Bewertungssystem-Assets und UI-Fixes
     */
    public function load_rating_assets() {
        // Bewertungsbearbeitung-Skript laden
        if (is_singular('product')) {
            wp_enqueue_script('mp-edit-rating', MP_COMMENTS_PLUGIN_URL . 'assets/js/edit-rating.js', array('jquery'), '1.0.0', true);
        }
        
        // UI-Fixes für MarketPress-Produkte
        if (function_exists('mp_product') || is_singular('product') || is_post_type_archive('product') || is_tax('product_category') || is_tax('product_tag')) {
            // Inline CSS für globale MarketPress UI-Fixes
            wp_add_inline_style('mp-style', '
                /* Verhindere unerwünschte Cursor-Positionierung und Textauswahl */
                .mp_product_content, 
                .mp_product_price,
                .mp_product_meta,
                .mp_product_details,
                .mp_product_categories,
                .mp_product_tags,
                .mp-product {
                    user-select: none;
                }
                
                /* Erlaube Textauswahl für wichtige Inhalte */
                .mp_product_content .entry-content,
                .mp_product_content .entry-summary,
                .mp_product_description {
                    user-select: text;
                }
                
                /* Verbessere Input-Elemente und Links */
                .mp_product input, 
                .mp_product textarea,
                .mp_product select,
                .mp_product button,
                .mp_product a {
                    user-select: text;
                    outline: 2px solid transparent;
                }
            ');
            
            // Inline JavaScript für globale MarketPress UI-Fixes
            wp_add_inline_script('mp-global', '
                jQuery(document).ready(function($) {
                    // Verhindere Cursor-Probleme in MarketPress-Elementen
                    $("body").on("mousedown", ".mp_product_content, .mp_product_price, .mp_product_meta, .mp_product_details, .mp-product", function(e) {
                        if (!$(e.target).is("input, textarea, select, button, a") && 
                            !$(e.target).closest(".mp_product_description").length) {
                            e.preventDefault();
                        }
                    });
                });
            ', 'after');
        }
        
        // Bewertungssystem-Assets nur auf Produktseiten laden
        if (is_singular('product')) {
            wp_enqueue_style('mp-ratings-style', MP_COMMENTS_PLUGIN_URL . 'assets/css/ratings.css', array(), '1.0.0');
            wp_enqueue_script('mp-ratings-script', MP_COMMENTS_PLUGIN_URL . 'assets/js/ratings.js', array('jquery'), '1.0.0', true);
            
            // Lokalisierung für JavaScript
            wp_localize_script('mp-ratings-script', 'mp_ratings_i18n', array(
                'rating_1' => __('Schlecht (1 Stern)', 'mp'),
                'rating_2' => __('Ausreichend (2 Sterne)', 'mp'),
                'rating_3' => __('Gut (3 Sterne)', 'mp'),
                'rating_4' => __('Sehr gut (4 Sterne)', 'mp'),
                'rating_5' => __('Ausgezeichnet (5 Sterne)', 'mp'),
                'select_rating' => __('Bitte wähle eine Bewertung aus.', 'mp'),
            ));
            
            // Lokalisierung für das Bearbeitungsformular
            wp_localize_script('mp-edit-rating', 'mp_edit_rating', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'rating_label' => __('Deine Sternebewertung', 'mp'),
                'rating_text' => array(
                    1 => __('Schlecht (1 Stern)', 'mp'),
                    2 => __('Ausreichend (2 Sterne)', 'mp'),
                    3 => __('Gut (3 Sterne)', 'mp'),
                    4 => __('Sehr gut (4 Sterne)', 'mp'),
                    5 => __('Ausgezeichnet (5 Sterne)', 'mp')
                ),
                'rating_label_1' => __('Schlecht', 'mp'),
                'rating_label_2' => __('Ausreichend', 'mp'),
                'rating_label_3' => __('Gut', 'mp'),
                'rating_label_4' => __('Sehr gut', 'mp'),
                'rating_label_5' => __('Ausgezeichnet', 'mp'),
                'save_button' => __('Bewertung speichern', 'mp'),
                'cancel_button' => __('Abbrechen', 'mp'),
                'saving' => __('Wird gespeichert...', 'mp'),
                'error_message' => __('Ein Fehler ist aufgetreten. Bitte versuche es erneut.', 'mp'),
                'comment_label' => __('Dein Kommentar', 'mp'),
                'optional_text' => __('optional', 'mp'),
                'select_rating' => __('Bitte wähle eine Bewertung aus.', 'mp'),
                'success_message' => __('Deine Bewertung wurde aktualisiert.', 'mp'),
                'find_your_review' => __('Zu deiner Bewertung', 'mp'),
                'already_rated' => __('Du hast dieses Produkt bereits bewertet.', 'mp')
            ));
        }
    }
    
    /**
     * Ersetze die standard WordPress Comments-Metabox für Produkte
     */
    public function replace_comments_metabox() {
        // Entferne die Standard-Metabox für Kommentare bei Produkten
        remove_meta_box('commentsdiv', 'product', 'normal');
        
        // Füge unsere eigene Metabox für Produktbewertungen hinzu
        add_meta_box(
            'mp_product_ratings',
            __('Produktbewertungen', 'mp'),
            array($this, 'display_product_ratings_metabox'),
            'product',
            'normal',
            'default'
        );
    }
    
    /**
     * Anzeige der eigenen Bewertungs-Metabox für Produkte im Admin-Bereich
     */
    public function display_product_ratings_metabox($post) {
        // Lade die Bewertungen
        $args = array(
            'post_id' => $post->ID,
            'status' => 'approve',
            'meta_key' => 'rating',
        );
        $reviews = get_comments($args);
        $reviews_count = count($reviews);
        
        echo '<div class="mp-product-ratings-admin">';
        
        if ($reviews_count > 0) {
            // Berechne Durchschnittsbewertung
            $total_rating = 0;
            foreach ($reviews as $review) {
                $rating = get_comment_meta($review->comment_ID, 'rating', true);
                if ($rating) {
                    $total_rating += $rating;
                }
            }
            $average_rating = $total_rating / $reviews_count;
            $stars = str_repeat('★', round($average_rating)) . str_repeat('☆', 5 - round($average_rating));
            
            // Zeige Zusammenfassung
            echo '<div class="mp-ratings-summary">';
            echo '<p class="mp-ratings-average">';
            echo sprintf(
                __('Durchschnittliche Bewertung: <span style="color: #FFD700; font-size: 1.2em;">%s</span> (%s/5) aus %s Bewertungen', 'mp'),
                $stars,
                number_format($average_rating, 1),
                $reviews_count
            );
            echo '</p>';
            echo '</div>';
            
            // Zeige Liste der Bewertungen
            echo '<div class="mp-ratings-list">';
            echo '<h4>' . __('Alle Bewertungen', 'mp') . '</h4>';
            
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Benutzer', 'mp') . '</th>';
            echo '<th>' . __('Bewertung', 'mp') . '</th>';
            echo '<th>' . __('Datum', 'mp') . '</th>';
            echo '<th>' . __('Kommentar', 'mp') . '</th>';
            echo '<th>' . __('Aktionen', 'mp') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($reviews as $review) {
                $rating = get_comment_meta($review->comment_ID, 'rating', true);
                $rating_stars = $rating ? str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) : '–';
                
                echo '<tr>';
                echo '<td>' . esc_html($review->comment_author) . '</td>';
                echo '<td><span style="color: #FFD700;">' . $rating_stars . '</span> (' . $rating . '/5)</td>';
                echo '<td>' . get_comment_date('d.m.Y H:i', $review->comment_ID) . '</td>';
                echo '<td>' . wp_trim_words(strip_tags($review->comment_content), 15) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url(admin_url('comment.php?action=editcomment&c=' . $review->comment_ID)) . '" class="button button-small">' . __('Bearbeiten', 'mp') . '</a> ';
                echo '<a href="' . esc_url(admin_url('comment.php?action=cdc&c=' . $review->comment_ID)) . '" class="button button-small">' . __('Löschen', 'mp') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            
            // Link zu allen Kommentaren
            echo '<p class="mp-view-all-ratings">';
            echo '<a href="' . admin_url('edit-comments.php?p=' . $post->ID) . '" class="button">' . __('Alle Bewertungen verwalten', 'mp') . '</a>';
            echo '</p>';
            
            echo '</div>';
        } else {
            echo '<p>' . __('Dieses Produkt hat noch keine Bewertungen.', 'mp') . '</p>';
        }
        
        echo '</div>';
        
        // Füge etwas CSS für die Darstellung hinzu
        echo '<style>
            .mp-product-ratings-admin {
                padding: 10px 0;
            }
            .mp-ratings-summary {
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
                border-left: 4px solid #4CAF50;
            }
            .mp-ratings-average {
                font-size: 16px;
                margin: 0;
            }
            .mp-ratings-list h4 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .mp-view-all-ratings {
                margin-top: 15px;
                text-align: right;
            }
        </style>';
    }
    
    /**
     * Lade notwendige Schriftarten im Admin-Bereich
     */
    public function load_admin_fonts() {
        global $post;
        
        // Nur auf der Produkt-Bearbeitungsseite laden
        if (is_admin() && isset($post) && $post && get_post_type($post->ID) === 'product') {
            // Füge einen CSS-Fix für die fehlenden Schriftarten hinzu
            wp_add_inline_style('wp-admin', '
                /* Fallback für fehlende Source Sans Pro Schriftart */
                @font-face {
                    font-family: "Source Sans Pro";
                    src: local("Segoe UI"), local("Helvetica Neue"), local("Roboto"), local("Arial"), sans-serif;
                    font-weight: normal;
                    font-style: normal;
                }
                
                @font-face {
                    font-family: "Source Sans Pro";
                    src: local("Segoe UI Bold"), local("Helvetica Neue Bold"), local("Roboto Bold"), local("Arial Bold"), sans-serif;
                    font-weight: bold;
                    font-style: normal;
                }
            ');
        }
    }
    
    /**
     * Entferne die Diskussions-Metabox für Produkte
     * Diese Box ist für unser Bewertungssystem nicht notwendig
     */
    public function remove_discussion_metabox() {
        // Entferne die Diskussions-Metabox für Produkte
        remove_meta_box('commentstatusdiv', 'product', 'normal');
        remove_meta_box('commentstatusdiv', 'product', 'side');
    }
}

/**
 * Hilfsfunktion für den Zugriff auf die Addon-Instanz
 *
 * @since 3.0
 * @access public
 * @return MP_MARKETPRESS_COMMENTS_Addon
 */
function mp_comments() {
    return MP_MARKETPRESS_COMMENTS_Addon::get_instance();
}

// Initialisieren des Addons
mp_comments();


