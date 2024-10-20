<?php
/**
 * Plugin Name:         Andizer - Internal links
 * Plugin URI:          https://github.com/andizer/andizer-internal-links
 * Description:         Shows the internal links saved in Yoast SEO
 * Author:              Andy Meerwaldt
 * Author URI:          https://github.com/andizer/andizer-internal-links
 *
 * Version:             1.0.0
 * Requires at least:   6.2.0
 * Tested up to:        6.3
 * Requires PHP:        8.2
 */

use Andizer\Plugin\YoastInternalLinks\AdminPage;
use Andizer\Plugin\YoastInternalLinks\RowActions;

require_once( 'vendor/autoload.php' );

add_action( 'plugins_loaded', static function() {
	$admin_page = new AdminPage();
	$rowactions = new RowActions();

	$admin_page->register();
	$rowactions->register();
} );