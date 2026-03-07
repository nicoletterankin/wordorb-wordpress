<?php
/**
 * Plugin Name:       WordOrb Vocabulary
 * Plugin URI:        https://wordorb.ai/integrations/wordpress
 * Description:       Embed rich vocabulary cards, interactive quizzes, and structured lessons from the WordOrb API (162K words, 47 languages). Shortcodes, Gutenberg block, sidebar widget, and REST proxy included.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Lesson of the Day PBC
 * Author URI:        https://wordorb.ai
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wordorb
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WORDORB_VERSION', '1.0.0' );
define( 'WORDORB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WORDORB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WORDORB_API_BASE', 'https://api.wordorb.ai' );
define( 'WORDORB_CACHE_TTL', HOUR_IN_SECONDS );

// ---------------------------------------------------------------------------
// Activation / Deactivation
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'wordorb_activate' );
function wordorb_activate() {
    add_option( 'wordorb_api_key', '' );
    add_option( 'wordorb_default_tone', 'neutral' );
    add_option( 'wordorb_cache_ttl', WORDORB_CACHE_TTL );
}

register_deactivation_hook( __FILE__, 'wordorb_deactivate' );
function wordorb_deactivate() {
    // Clear all transients with our prefix
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wordorb_%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wordorb_%'" );
}

// ---------------------------------------------------------------------------
// Admin Settings Page
// ---------------------------------------------------------------------------

add_action( 'admin_menu', 'wordorb_admin_menu' );
function wordorb_admin_menu() {
    add_options_page(
        __( 'WordOrb Settings', 'wordorb' ),
        __( 'WordOrb', 'wordorb' ),
        'manage_options',
        'wordorb-settings',
        'wordorb_settings_page'
    );
}

add_action( 'admin_init', 'wordorb_register_settings' );
function wordorb_register_settings() {
    register_setting( 'wordorb_options', 'wordorb_api_key', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    register_setting( 'wordorb_options', 'wordorb_default_tone', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'neutral',
    ) );
    register_setting( 'wordorb_options', 'wordorb_cache_ttl', array(
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => HOUR_IN_SECONDS,
    ) );

    add_settings_section( 'wordorb_main', __( 'API Configuration', 'wordorb' ), '__return_false', 'wordorb-settings' );

    add_settings_field( 'wordorb_api_key', __( 'API Key', 'wordorb' ), 'wordorb_field_api_key', 'wordorb-settings', 'wordorb_main' );
    add_settings_field( 'wordorb_default_tone', __( 'Default Tone', 'wordorb' ), 'wordorb_field_default_tone', 'wordorb-settings', 'wordorb_main' );
    add_settings_field( 'wordorb_cache_ttl', __( 'Cache TTL (seconds)', 'wordorb' ), 'wordorb_field_cache_ttl', 'wordorb-settings', 'wordorb_main' );
}

function wordorb_field_api_key() {
    $val = get_option( 'wordorb_api_key', '' );
    echo '<input type="password" name="wordorb_api_key" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="wo_..." />';
    echo '<p class="description">' . esc_html__( 'Get your API key at wordorb.ai/dashboard?ref=wordpress', 'wordorb' ) . '</p>';
}

function wordorb_field_default_tone() {
    $val   = get_option( 'wordorb_default_tone', 'neutral' );
    $tones = array( 'neutral', 'formal', 'playful', 'poetic', 'academic' );
    echo '<select name="wordorb_default_tone">';
    foreach ( $tones as $tone ) {
        printf( '<option value="%s" %s>%s</option>', esc_attr( $tone ), selected( $val, $tone, false ), esc_html( ucfirst( $tone ) ) );
    }
    echo '</select>';
}

function wordorb_field_cache_ttl() {
    $val = get_option( 'wordorb_cache_ttl', HOUR_IN_SECONDS );
    echo '<input type="number" name="wordorb_cache_ttl" value="' . esc_attr( $val ) . '" min="60" step="60" class="small-text" />';
    echo '<p class="description">' . esc_html__( 'Default: 3600 (1 hour). Minimum: 60 seconds.', 'wordorb' ) . '</p>';
}

function wordorb_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <?php
        // Connection test
        $api_key = get_option( 'wordorb_api_key', '' );
        if ( $api_key ) {
            $health = wordorb_api_request( '/api/health' );
            if ( ! is_wp_error( $health ) && isset( $health['status'] ) && 'ok' === $health['status'] ) {
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Connected to WordOrb API.', 'wordorb' ) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Cannot reach WordOrb API. Check your API key.', 'wordorb' ) . '</p></div>';
            }
        }
        ?>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'wordorb_options' );
            do_settings_sections( 'wordorb-settings' );
            submit_button();
            ?>
        </form>

        <hr />
        <h2><?php esc_html_e( 'Shortcode Reference', 'wordorb' ); ?></h2>
        <table class="widefat striped">
            <thead><tr><th>Shortcode</th><th>Description</th><th>Example</th></tr></thead>
            <tbody>
                <tr>
                    <td><code>[wordorb_word]</code></td>
                    <td>Renders a vocabulary card with definition, IPA, etymology, and translations.</td>
                    <td><code>[wordorb_word word="courage" lang="es"]</code></td>
                </tr>
                <tr>
                    <td><code>[wordorb_quiz]</code></td>
                    <td>Renders an interactive quiz from the Quiz Orb.</td>
                    <td><code>[wordorb_quiz day="42" track="learn" count="5"]</code></td>
                </tr>
                <tr>
                    <td><code>[wordorb_lesson]</code></td>
                    <td>Renders a 5-phase lesson (hook, story, wonder, action, wisdom).</td>
                    <td><code>[wordorb_lesson day="1" track="learn" age="adult"]</code></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// API Client
// ---------------------------------------------------------------------------

/**
 * Make an authenticated request to the WordOrb API.
 *
 * @param string $path   API path, e.g. "/api/word/courage".
 * @param array  $args   Additional query params.
 * @return array|WP_Error Decoded JSON body or WP_Error.
 */
function wordorb_api_request( $path, $args = array() ) {
    $api_key = get_option( 'wordorb_api_key', '' );
    if ( empty( $api_key ) && strpos( $path, '/api/health' ) === false ) {
        return new WP_Error( 'wordorb_no_key', __( 'WordOrb API key not configured.', 'wordorb' ) );
    }

    $url = WORDORB_API_BASE . $path;
    if ( ! empty( $args ) ) {
        $url = add_query_arg( $args, $url );
    }

    $response = wp_remote_get( $url, array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Accept'        => 'application/json',
            'User-Agent'    => 'WordOrb-WordPress/' . WORDORB_VERSION,
        ),
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $code >= 400 ) {
        $msg = isset( $body['error'] ) ? $body['error'] : 'API error';
        return new WP_Error( 'wordorb_api_error', $msg, array( 'status' => $code ) );
    }

    return $body;
}

/**
 * Cached API request using WordPress transients.
 */
function wordorb_cached_request( $path, $args = array() ) {
    $cache_key = 'wordorb_' . md5( $path . serialize( $args ) );
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    $result = wordorb_api_request( $path, $args );

    if ( ! is_wp_error( $result ) ) {
        $ttl = (int) get_option( 'wordorb_cache_ttl', WORDORB_CACHE_TTL );
        set_transient( $cache_key, $result, max( 60, $ttl ) );
    }

    return $result;
}

// ---------------------------------------------------------------------------
// REST API Proxy  (keeps API key server-side)
// ---------------------------------------------------------------------------

add_action( 'rest_api_init', 'wordorb_register_rest_routes' );
function wordorb_register_rest_routes() {
    register_rest_route( 'wordorb/v1', '/word/(?P<word>[a-zA-Z\'-]+)', array(
        'methods'             => 'GET',
        'callback'            => 'wordorb_rest_word',
        'permission_callback' => '__return_true',
        'args'                => array(
            'word' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'lang' => array( 'sanitize_callback' => 'sanitize_text_field' ),
        ),
    ) );

    register_rest_route( 'wordorb/v1', '/lesson', array(
        'methods'             => 'GET',
        'callback'            => 'wordorb_rest_lesson',
        'permission_callback' => '__return_true',
        'args'                => array(
            'day'   => array( 'sanitize_callback' => 'absint', 'default' => 1 ),
            'track' => array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'learn' ),
            'age'   => array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'adult' ),
        ),
    ) );

    register_rest_route( 'wordorb/v1', '/quiz', array(
        'methods'             => 'GET',
        'callback'            => 'wordorb_rest_quiz',
        'permission_callback' => '__return_true',
        'args'                => array(
            'day'   => array( 'sanitize_callback' => 'absint', 'default' => 1 ),
            'track' => array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'learn' ),
            'count' => array( 'sanitize_callback' => 'absint', 'default' => 5 ),
        ),
    ) );

    register_rest_route( 'wordorb/v1', '/quiz/random', array(
        'methods'             => 'GET',
        'callback'            => 'wordorb_rest_quiz_random',
        'permission_callback' => '__return_true',
        'args'                => array(
            'track' => array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'learn' ),
            'count' => array( 'sanitize_callback' => 'absint', 'default' => 5 ),
            'type'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
        ),
    ) );
}

function wordorb_rest_word( WP_REST_Request $request ) {
    $word = strtolower( $request->get_param( 'word' ) );
    $args = array();
    if ( $request->get_param( 'lang' ) ) {
        $args['lang'] = $request->get_param( 'lang' );
    }
    $data = wordorb_cached_request( '/api/word/' . rawurlencode( $word ), $args );
    if ( is_wp_error( $data ) ) {
        return new WP_REST_Response( array( 'error' => $data->get_error_message() ), 502 );
    }
    // Strip internal fields
    unset( $data['_source'], $data['_tier'] );
    return new WP_REST_Response( $data, 200 );
}

function wordorb_rest_lesson( WP_REST_Request $request ) {
    $args = array(
        'day'   => $request->get_param( 'day' ),
        'track' => $request->get_param( 'track' ),
        'age'   => $request->get_param( 'age' ),
    );
    $data = wordorb_cached_request( '/api/lesson', $args );
    if ( is_wp_error( $data ) ) {
        return new WP_REST_Response( array( 'error' => $data->get_error_message() ), 502 );
    }
    unset( $data['_product'], $data['_tier'] );
    return new WP_REST_Response( $data, 200 );
}

function wordorb_rest_quiz( WP_REST_Request $request ) {
    $args = array(
        'day'   => $request->get_param( 'day' ),
        'track' => $request->get_param( 'track' ),
    );
    $data = wordorb_cached_request( '/api/quiz', $args );
    if ( is_wp_error( $data ) ) {
        return new WP_REST_Response( array( 'error' => $data->get_error_message() ), 502 );
    }
    unset( $data['_product'], $data['_tier'] );
    return new WP_REST_Response( $data, 200 );
}

function wordorb_rest_quiz_random( WP_REST_Request $request ) {
    $args = array(
        'track' => $request->get_param( 'track' ),
        'count' => $request->get_param( 'count' ),
    );
    if ( $request->get_param( 'type' ) ) {
        $args['type'] = $request->get_param( 'type' );
    }
    $data = wordorb_cached_request( '/api/quiz/random', $args );
    if ( is_wp_error( $data ) ) {
        return new WP_REST_Response( array( 'error' => $data->get_error_message() ), 502 );
    }
    unset( $data['_product'], $data['_tier'] );
    return new WP_REST_Response( $data, 200 );
}

// ---------------------------------------------------------------------------
// Enqueue Assets
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'wordorb_enqueue_assets' );
function wordorb_enqueue_assets() {
    wp_register_style( 'wordorb', WORDORB_PLUGIN_URL . 'assets/css/wordorb.css', array(), WORDORB_VERSION );
    wp_register_script( 'wordorb', WORDORB_PLUGIN_URL . 'assets/js/wordorb.js', array(), WORDORB_VERSION, true );
    wp_localize_script( 'wordorb', 'WordOrb', array(
        'rest_url' => esc_url_raw( rest_url( 'wordorb/v1/' ) ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
    ) );
}

// ---------------------------------------------------------------------------
// Shortcode: [wordorb_word word="courage" lang="es"]
// ---------------------------------------------------------------------------

add_shortcode( 'wordorb_word', 'wordorb_shortcode_word' );
function wordorb_shortcode_word( $atts ) {
    $atts = shortcode_atts( array(
        'word' => '',
        'lang' => '',
        'tone' => '',
    ), $atts, 'wordorb_word' );

    if ( empty( $atts['word'] ) ) {
        return '<p class="wordorb-error">' . esc_html__( 'Please specify a word: [wordorb_word word="example"]', 'wordorb' ) . '</p>';
    }

    $word = sanitize_text_field( strtolower( $atts['word'] ) );
    $args = array();
    if ( ! empty( $atts['lang'] ) ) {
        $args['lang'] = sanitize_text_field( $atts['lang'] );
    }

    $data = wordorb_cached_request( '/api/word/' . rawurlencode( $word ), $args );

    if ( is_wp_error( $data ) ) {
        return '<p class="wordorb-error">' . esc_html( $data->get_error_message() ) . '</p>';
    }

    wp_enqueue_style( 'wordorb' );

    $html  = '<div class="wordorb-card" data-word="' . esc_attr( $word ) . '">';

    // Image
    if ( ! empty( $data['image_url'] ) ) {
        $html .= '<div class="wordorb-card__image">';
        $html .= '<img src="' . esc_url( $data['image_url'] ) . '" alt="' . esc_attr( $word ) . '" loading="lazy" />';
        $html .= '</div>';
    }

    $html .= '<div class="wordorb-card__body">';

    // Header
    $html .= '<div class="wordorb-card__header">';
    $html .= '<h3 class="wordorb-card__word">' . esc_html( $data['word'] ?? $word ) . '</h3>';
    if ( ! empty( $data['ipa'] ) ) {
        $html .= '<span class="wordorb-card__ipa">' . esc_html( $data['ipa'] ) . '</span>';
    }
    if ( ! empty( $data['pos'] ) ) {
        $html .= '<span class="wordorb-card__pos">' . esc_html( $data['pos'] ) . '</span>';
    }
    $html .= '</div>';

    // Definition
    if ( ! empty( $data['def'] ) ) {
        $html .= '<p class="wordorb-card__def">' . esc_html( $data['def'] ) . '</p>';
    }

    // Etymology
    if ( ! empty( $data['etym'] ) ) {
        $html .= '<div class="wordorb-card__etym">';
        $html .= '<strong>' . esc_html__( 'Etymology:', 'wordorb' ) . '</strong> ';
        $html .= esc_html( $data['etym'] );
        $html .= '</div>';
    }

    // Translations
    if ( ! empty( $data['langs'] ) && is_array( $data['langs'] ) ) {
        $html .= '<div class="wordorb-card__langs">';
        $html .= '<strong>' . esc_html__( 'Translations:', 'wordorb' ) . '</strong>';
        $html .= '<ul class="wordorb-card__lang-list">';
        $shown = 0;
        foreach ( $data['langs'] as $lang_entry ) {
            if ( $shown >= 6 ) break;
            if ( is_array( $lang_entry ) && isset( $lang_entry['lang'], $lang_entry['translation'] ) ) {
                $html .= '<li><span class="wordorb-card__lang-code">' . esc_html( $lang_entry['lang'] ) . '</span> ';
                $html .= esc_html( $lang_entry['translation'] ) . '</li>';
                $shown++;
            }
        }
        $html .= '</ul>';
        $html .= '</div>';
    }

    $html .= '</div>'; // __body
    $html .= '<div class="wordorb-card__footer">';
    $html .= '<a href="https://wordorb.ai?ref=wordpress" target="_blank" rel="noopener">' . esc_html__( 'Powered by WordOrb', 'wordorb' ) . '</a>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

// ---------------------------------------------------------------------------
// Shortcode: [wordorb_quiz day="1" track="learn" count="5"]
// ---------------------------------------------------------------------------

add_shortcode( 'wordorb_quiz', 'wordorb_shortcode_quiz' );
function wordorb_shortcode_quiz( $atts ) {
    $atts = shortcode_atts( array(
        'day'   => '1',
        'track' => 'learn',
        'count' => '5',
        'type'  => '',
    ), $atts, 'wordorb_quiz' );

    $day   = absint( $atts['day'] );
    $track = sanitize_text_field( $atts['track'] );
    $count = min( absint( $atts['count'] ), 20 );

    $args = array( 'day' => $day, 'track' => $track );
    if ( ! empty( $atts['type'] ) ) {
        $args['type'] = sanitize_text_field( $atts['type'] );
    }

    $data = wordorb_cached_request( '/api/quiz', $args );

    if ( is_wp_error( $data ) ) {
        return '<p class="wordorb-error">' . esc_html( $data->get_error_message() ) . '</p>';
    }

    if ( empty( $data['interactions'] ) ) {
        return '<p class="wordorb-error">' . esc_html__( 'No quiz questions found for this day/track.', 'wordorb' ) . '</p>';
    }

    wp_enqueue_style( 'wordorb' );
    wp_enqueue_script( 'wordorb' );

    $interactions = array_slice( $data['interactions'], 0, $count );
    $quiz_id      = 'wordorb-quiz-' . wp_unique_id();

    $html  = '<div class="wordorb-quiz" id="' . esc_attr( $quiz_id ) . '" data-total="' . count( $interactions ) . '">';
    $html .= '<div class="wordorb-quiz__header">';
    $html .= '<h3 class="wordorb-quiz__title">' . sprintf( esc_html__( 'Quiz: Day %d &mdash; %s', 'wordorb' ), $day, esc_html( ucfirst( $track ) ) ) . '</h3>';
    $html .= '<div class="wordorb-quiz__progress"><span class="wordorb-quiz__current">1</span> / ' . count( $interactions ) . '</div>';
    $html .= '</div>';

    foreach ( $interactions as $i => $q ) {
        $active = ( 0 === $i ) ? ' wordorb-quiz__question--active' : '';
        $html  .= '<div class="wordorb-quiz__question' . $active . '" data-index="' . $i . '" data-answer="' . esc_attr( $q['answer'] ?? '' ) . '">';
        $html  .= '<p class="wordorb-quiz__prompt">' . esc_html( $q['prompt'] ?? '' ) . '</p>';

        if ( ! empty( $q['choices'] ) && is_array( $q['choices'] ) ) {
            $html .= '<div class="wordorb-quiz__choices">';
            foreach ( $q['choices'] as $ci => $choice ) {
                $label = is_array( $choice ) ? ( $choice['text'] ?? $choice['label'] ?? '' ) : $choice;
                $value = is_array( $choice ) ? ( $choice['key'] ?? chr( 65 + $ci ) ) : chr( 65 + $ci );
                $html .= '<button class="wordorb-quiz__choice" data-value="' . esc_attr( $value ) . '">';
                $html .= '<span class="wordorb-quiz__choice-key">' . esc_html( $value ) . '</span> ';
                $html .= esc_html( $label );
                $html .= '</button>';
            }
            $html .= '</div>';
        } else {
            // Open-ended
            $html .= '<textarea class="wordorb-quiz__open" rows="3" placeholder="' . esc_attr__( 'Type your answer...', 'wordorb' ) . '"></textarea>';
            $html .= '<button class="wordorb-quiz__submit-open">' . esc_html__( 'Check', 'wordorb' ) . '</button>';
        }

        if ( ! empty( $q['explanation'] ) ) {
            $html .= '<div class="wordorb-quiz__explanation" style="display:none;">';
            $html .= '<p>' . esc_html( $q['explanation'] ) . '</p>';
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    $html .= '<div class="wordorb-quiz__nav">';
    $html .= '<button class="wordorb-quiz__prev" disabled>' . esc_html__( 'Previous', 'wordorb' ) . '</button>';
    $html .= '<button class="wordorb-quiz__next">' . esc_html__( 'Next', 'wordorb' ) . '</button>';
    $html .= '</div>';

    $html .= '<div class="wordorb-quiz__results" style="display:none;">';
    $html .= '<h4>' . esc_html__( 'Results', 'wordorb' ) . '</h4>';
    $html .= '<p class="wordorb-quiz__score"></p>';
    $html .= '<button class="wordorb-quiz__retry">' . esc_html__( 'Try Again', 'wordorb' ) . '</button>';
    $html .= '</div>';

    $html .= '<div class="wordorb-quiz__footer">';
    $html .= '<a href="https://wordorb.ai?ref=wordpress" target="_blank" rel="noopener">' . esc_html__( 'Powered by WordOrb', 'wordorb' ) . '</a>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

// ---------------------------------------------------------------------------
// Shortcode: [wordorb_lesson day="1" track="learn" age="adult"]
// ---------------------------------------------------------------------------

add_shortcode( 'wordorb_lesson', 'wordorb_shortcode_lesson' );
function wordorb_shortcode_lesson( $atts ) {
    $atts = shortcode_atts( array(
        'day'       => '1',
        'track'     => 'learn',
        'age'       => 'adult',
        'archetype' => '',
    ), $atts, 'wordorb_lesson' );

    $day   = absint( $atts['day'] );
    $track = sanitize_text_field( $atts['track'] );
    $age   = sanitize_text_field( $atts['age'] );

    $args = array( 'day' => $day, 'track' => $track, 'age' => $age );
    if ( ! empty( $atts['archetype'] ) ) {
        $args['archetype'] = sanitize_text_field( $atts['archetype'] );
    }

    $data = wordorb_cached_request( '/api/lesson', $args );

    if ( is_wp_error( $data ) ) {
        return '<p class="wordorb-error">' . esc_html( $data->get_error_message() ) . '</p>';
    }

    if ( empty( $data['phases'] ) ) {
        return '<p class="wordorb-error">' . esc_html__( 'No lesson found for this configuration.', 'wordorb' ) . '</p>';
    }

    wp_enqueue_style( 'wordorb' );
    wp_enqueue_script( 'wordorb' );

    $phase_names = array(
        'hook'   => __( 'Hook', 'wordorb' ),
        'story'  => __( 'Story', 'wordorb' ),
        'wonder' => __( 'Wonder', 'wordorb' ),
        'action' => __( 'Action', 'wordorb' ),
        'wisdom' => __( 'Wisdom', 'wordorb' ),
    );
    $phase_icons = array(
        'hook'   => '&#x1F3A3;',
        'story'  => '&#x1F4D6;',
        'wonder' => '&#x2728;',
        'action' => '&#x1F3AF;',
        'wisdom' => '&#x1F9E0;',
    );

    $lesson_id = 'wordorb-lesson-' . wp_unique_id();

    $html  = '<div class="wordorb-lesson" id="' . esc_attr( $lesson_id ) . '">';
    $html .= '<div class="wordorb-lesson__header">';
    if ( ! empty( $data['title'] ) ) {
        $html .= '<h3 class="wordorb-lesson__title">' . esc_html( $data['title'] ) . '</h3>';
    }
    $html .= '<div class="wordorb-lesson__meta">';
    $html .= '<span>' . sprintf( esc_html__( 'Day %d', 'wordorb' ), $day ) . '</span>';
    $html .= '<span>' . esc_html( ucfirst( $track ) ) . '</span>';
    if ( ! empty( $data['theme'] ) ) {
        $html .= '<span>' . esc_html( $data['theme'] ) . '</span>';
    }
    $html .= '</div>';
    $html .= '</div>';

    // Phase tabs
    $html .= '<div class="wordorb-lesson__tabs">';
    $idx   = 0;
    foreach ( $phase_names as $key => $label ) {
        if ( ! isset( $data['phases'][ $key ] ) ) continue;
        $active = ( 0 === $idx ) ? ' wordorb-lesson__tab--active' : '';
        $html  .= '<button class="wordorb-lesson__tab' . $active . '" data-phase="' . esc_attr( $key ) . '">';
        $html  .= '<span class="wordorb-lesson__tab-icon">' . $phase_icons[ $key ] . '</span> ';
        $html  .= esc_html( $label );
        $html  .= '</button>';
        $idx++;
    }
    $html .= '</div>';

    // Phase content
    $idx = 0;
    foreach ( $phase_names as $key => $label ) {
        if ( ! isset( $data['phases'][ $key ] ) ) continue;
        $active = ( 0 === $idx ) ? ' wordorb-lesson__phase--active' : '';
        $html  .= '<div class="wordorb-lesson__phase' . $active . '" data-phase="' . esc_attr( $key ) . '">';
        $html  .= wp_kses_post( wpautop( $data['phases'][ $key ] ) );
        $html  .= '</div>';
        $idx++;
    }

    // Navigation
    $html .= '<div class="wordorb-lesson__nav">';
    $html .= '<button class="wordorb-lesson__prev" disabled>' . esc_html__( 'Previous Phase', 'wordorb' ) . '</button>';
    $html .= '<button class="wordorb-lesson__next">' . esc_html__( 'Next Phase', 'wordorb' ) . '</button>';
    $html .= '</div>';

    $html .= '<div class="wordorb-lesson__footer">';
    $html .= '<a href="https://wordorb.ai?ref=wordpress" target="_blank" rel="noopener">' . esc_html__( 'Powered by WordOrb', 'wordorb' ) . '</a>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

// ---------------------------------------------------------------------------
// Widget: Word of the Day
// ---------------------------------------------------------------------------

class WordOrb_WOTD_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'wordorb_wotd',
            __( 'WordOrb: Word of the Day', 'wordorb' ),
            array( 'description' => __( 'Displays a daily vocabulary word from WordOrb.', 'wordorb' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Word of the Day', 'wordorb' );

        // Use day-of-year as seed for word selection
        $day  = (int) gmdate( 'z' ) + 1; // 1-366
        $data = wordorb_cached_request( '/api/lesson', array( 'day' => min( $day, 365 ), 'track' => 'learn', 'age' => 'adult' ) );

        // Get the word for this day's lesson title
        $word_text = '';
        if ( ! is_wp_error( $data ) && ! empty( $data['title'] ) ) {
            $word_text = $data['title'];
        }

        // Also fetch a random word for display
        $random = wordorb_cached_request( '/api/quiz/random', array( 'track' => 'learn', 'count' => '1', 'type' => 'mc' ) );
        $quiz_prompt = '';
        if ( ! is_wp_error( $random ) && ! empty( $random['interactions'][0]['prompt'] ) ) {
            $quiz_prompt = $random['interactions'][0]['prompt'];
        }

        wp_enqueue_style( 'wordorb' );

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title'];

        echo '<div class="wordorb-wotd">';
        if ( $word_text ) {
            echo '<p class="wordorb-wotd__title">' . esc_html( $word_text ) . '</p>';
        }
        if ( ! is_wp_error( $data ) && ! empty( $data['theme'] ) ) {
            echo '<p class="wordorb-wotd__theme">' . esc_html( $data['theme'] ) . '</p>';
        }
        if ( ! is_wp_error( $data ) && ! empty( $data['phases']['hook'] ) ) {
            echo '<p class="wordorb-wotd__hook">' . esc_html( wp_trim_words( $data['phases']['hook'], 30 ) ) . '</p>';
        }
        if ( $quiz_prompt ) {
            echo '<p class="wordorb-wotd__quiz"><strong>' . esc_html__( 'Quick Quiz:', 'wordorb' ) . '</strong> ' . esc_html( wp_trim_words( $quiz_prompt, 20 ) ) . '</p>';
        }
        echo '<a class="wordorb-wotd__link" href="https://wordorb.ai?ref=wordpress" target="_blank" rel="noopener">' . esc_html__( 'Explore on WordOrb', 'wordorb' ) . '</a>';
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Word of the Day', 'wordorb' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'wordorb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
        return $instance;
    }
}

add_action( 'widgets_init', function () {
    register_widget( 'WordOrb_WOTD_Widget' );
} );

// ---------------------------------------------------------------------------
// Gutenberg Block: WordOrb Word Card
// ---------------------------------------------------------------------------

add_action( 'init', 'wordorb_register_block' );
function wordorb_register_block() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    wp_register_script(
        'wordorb-block-editor',
        WORDORB_PLUGIN_URL . 'assets/js/wordorb-block.js',
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
        WORDORB_VERSION,
        true
    );

    register_block_type( 'wordorb/word-card', array(
        'editor_script'   => 'wordorb-block-editor',
        'render_callback' => 'wordorb_render_block',
        'attributes'      => array(
            'word' => array( 'type' => 'string', 'default' => '' ),
            'lang' => array( 'type' => 'string', 'default' => '' ),
        ),
    ) );
}

function wordorb_render_block( $attributes ) {
    return wordorb_shortcode_word( array(
        'word' => $attributes['word'] ?? '',
        'lang' => $attributes['lang'] ?? '',
    ) );
}

// Gutenberg block editor script (inline, registered above)
add_action( 'admin_footer', 'wordorb_block_editor_inline' );
function wordorb_block_editor_inline() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }
    ?>
    <script id="wordorb-block-editor-inline">
    (function() {
        if ( typeof wp === 'undefined' || ! wp.blocks ) return;

        var el = wp.element.createElement;
        var __ = wp.i18n.__;
        var TextControl = wp.components.TextControl;
        var InspectorControls = wp.blockEditor ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls;
        var PanelBody = wp.components.PanelBody;
        var ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;

        wp.blocks.registerBlockType('wordorb/word-card', {
            title: __('WordOrb Word Card', 'wordorb'),
            description: __('Display a vocabulary card from WordOrb.', 'wordorb'),
            icon: 'book-alt',
            category: 'widgets',
            keywords: [ 'vocabulary', 'dictionary', 'word', 'definition' ],
            attributes: {
                word: { type: 'string', default: '' },
                lang: { type: 'string', default: '' }
            },
            edit: function(props) {
                return [
                    el(InspectorControls, { key: 'inspector' },
                        el(PanelBody, { title: __('Word Settings', 'wordorb'), initialOpen: true },
                            el(TextControl, {
                                label: __('Word', 'wordorb'),
                                value: props.attributes.word,
                                onChange: function(val) { props.setAttributes({ word: val }); },
                                placeholder: 'courage'
                            }),
                            el(TextControl, {
                                label: __('Language (optional)', 'wordorb'),
                                value: props.attributes.lang,
                                onChange: function(val) { props.setAttributes({ lang: val }); },
                                placeholder: 'es'
                            })
                        )
                    ),
                    props.attributes.word
                        ? el(ServerSideRender, { key: 'preview', block: 'wordorb/word-card', attributes: props.attributes })
                        : el('div', { key: 'placeholder', className: 'wordorb-block-placeholder', style: { padding: '20px', background: '#f9f9f9', border: '2px dashed #ccc', textAlign: 'center' } },
                            el('p', {}, __('Enter a word in the block settings to display a WordOrb card.', 'wordorb'))
                          )
                ];
            },
            save: function() {
                return null; // Dynamic block rendered server-side
            }
        });
    })();
    </script>
    <?php
}
