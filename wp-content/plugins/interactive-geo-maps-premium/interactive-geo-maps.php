<?php

/**
 * Interactive Geo Maps
 *
 * @wordpress-plugin
 * Plugin Name: Interactive Geo Maps PRO
 * Plugin URI:        https://interactivegeomaps.com/
 * Description:       Create interactive geographic vector maps of the world, continents or any country in the world. Color full regions or create markers on specific locations that will have information on hover and can also have actions on click. This plugin uses the online amcharts library to generate the maps.
 * Version:           1.5.6
 * Requires PHP:      7.0
 * Author:            Carlos Moreira
 * Author URI:        https://cmoreira.net/
 * Text Domain:       interactive-geo-maps
 * Domain Path:       /languages
 *
 * @fs_premium_only /src/Plugin/Pro
 */
namespace Saltus\WP\Plugin\Saltus\InteractiveMaps;

// If this file is called directly, quit.
if ( !defined( 'WPINC' ) ) {
    exit;
}
// Only run plugin code if PHP version bigger than 7.0 for now
if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
    return;
}
// Freemius logic

if ( function_exists( __NAMESPACE__ . '\\igmfreemiusinit' ) ) {
    igmfreemiusinit()->set_basename( true, __FILE__ );
} else {
    
    if ( !function_exists( __NAMESPACE__ . '\\igmfreemiusinit' ) ) {
        // Create a helper function for easy SDK access.
        function igmfreemiusinit()
        {
            global  $igmfreemiusinit ;
            
            if ( !isset( $igmfreemiusinit ) ) {
                // Include Freemius SDK.
                if ( file_exists( dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php' ) ) {
                    require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';
                }
				class igmFsNull {
					public function can_use_premium_code__premium_only() {
						return true;
					}
				}
                $igmfreemiusinit = new igmFsNull();
            }
            
            return $igmfreemiusinit;
        }
    }
    
    if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once dirname( __FILE__ ) . '/vendor/autoload.php';
    }
    
    if ( class_exists( \Saltus\WP\Framework\Core::class ) ) {
        /*
         * The path to the plugin root directory is mandatory,
         * so it loads the models from a subdirectory.
         */
        $framework = new \Saltus\WP\Framework\Core( dirname( __FILE__ ) );
        $framework->register();
        /**
         * Initialize plugin
         */
        add_action( 'plugins_loaded', function () use( $framework ) {
            $plugin = new Core(
                'interactive-geo-maps',
                '1.5.6',
                __FILE__,
                $framework
            );
            $plugin->init();
        } );
    }
    
    // add filter to disable sanitize, to allow iframes/scripts
    add_filter(
        'igm_model',
        function ( $model ) {
        $model['meta']['map_info']['sections']['regions']['fields']['regions']['sanitize'] = false;
        return $model;
    },
        10,
        1
    );
}
