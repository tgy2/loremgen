<?php
/**
 * Plugin Name: Lorem Gen
 * Description: REST endpoint for paragraphs (as array), sentences/words/chars (as string), and lists (as array).
 * Version:     1.0.6
 * Author:      You
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/vendor/autoload.php';
use joshtronic\LoremIpsum;

add_action( 'rest_api_init', function() {
    register_rest_route( 'lorem/v1', '/generate', [
        'methods'             => 'GET',
        'callback'            => 'lorem_gen_handler',
        'permission_callback' => '__return_true',
        'args'                => [
            'type' => [
                'required' => true,
                'validate_callback' => function( $param ) {
                    return in_array( $param, [ 'paragraphs','sentences','words','chars','lists' ], true );
                },
            ],
            'count' => [
                'required' => true,
                'validate_callback' => function( $param ) {
                    return is_numeric( $param ) && (int) $param >= 1;
                },
                'sanitize_callback' => 'absint',
            ],
        ],
    ]);
});

add_action('wp_enqueue_scripts', function() {
    // Only load on frontend (not admin)
    if (is_admin()) return;

    // Adjust these paths if your plugin folder changes!
    wp_enqueue_style(
        'lorem-gen-css',
        plugins_url('lorem-gen.css', __FILE__),
        [],
        null
    );

    wp_enqueue_script(
        'lorem-gen-js',
        plugins_url('lorem-gen.js', __FILE__),
        [],
        null,
        true
    );
});

function lorem_gen_handler( WP_REST_Request $request ) {
    $type  = $request->get_param( 'type' );
    $count = absint( $request->get_param( 'count' ) );
    $li    = new LoremIpsum();

    switch ( $type ) {
        case 'paragraphs':
            $paras = $li->paragraphsArray( $count );
            return rest_ensure_response( $paras );

        case 'sentences':
            // Generate extra sentences and skip a random offset at the start
            $sentences = $li->sentencesArray( $count + 8 );
            $offset = rand(0, 7); // randomize starting point
            $sliced = array_slice($sentences, $offset, $count);
            $text = implode(' ', $sliced);
            return rest_ensure_response($text);

        case 'words':
            // Generate extra words and skip a random offset at the start
            $words = $li->wordsArray( $count + 15 );
            $offset = rand(0, 14); // randomize starting point
            $sliced = array_slice($words, $offset, $count);
            $text = implode(' ', $sliced);
            return rest_ensure_response($text);

        case 'chars':
            // Generate a big blob, then start at a random character offset
            $words = $li->wordsArray( $count * 2 );
            $blob = implode(' ', $words);
            $start = rand(0, min(20, max(0, mb_strlen($blob) - $count - 1))); // safety!
            $trunc = mb_substr($blob, $start, $count);
            return rest_ensure_response($trunc);

        case 'lists':
            $items = $li->paragraphsArray( $count );
            return rest_ensure_response( $items );

        default:
            return new WP_Error( 'invalid_type', 'Invalid type parameter', [ 'status' => 400 ] );
    }
}

