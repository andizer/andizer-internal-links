<?php
/**
 * Plugin Name:         Andizer - Internal links
 * Plugin URI:          https://github.com/andizer/andizer-internal-links
 * Description:         Shows the internal links saved in Yoast SEO
 * Author:              Andy Meerwaldt
 * Author URI:          https://github.com/andizer/andizer-internal-links
 * Text-domain          andizer-internal-links
 * Domain Path:         /languages
 *
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Version:             1.0.0
 * Requires at least:   6.2.0
 * Tested up to:        6.3
 * Requires PHP:        8.2
 */

use Andizer\Plugin\YoastInternalLinks\AdminPage;
use Andizer\Plugin\YoastInternalLinks\RowActions;

require_once( 'vendor/autoload.php' );

add_action( 'admin_init', static function() {
	// Deactivates the plugin when the Yoast plugin is not active.
	if ( ! \defined( 'WPSEO_PATH' ) ) {
		\deactivate_plugins( [ \plugin_basename( __FILE__ ) ] );
	}

	// Loads the translations.
	\load_plugin_textdomain( 'andizer-internal-links', false, \dirname( \plugin_basename( __FILE__ ) ) . '/languages' );
} );

add_action( 'plugins_loaded', static function() {
	$admin_page = new AdminPage();
	$rowactions = new RowActions();

	$admin_page->register();
	$rowactions->register();
} );
