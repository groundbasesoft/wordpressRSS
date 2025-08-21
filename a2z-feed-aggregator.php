<?php
/**
 * Plugin Name:       A2Z Feed Aggregator
 * Plugin URI:        https://example.com/a2z-feed-aggregator
 * Description:       Pulls data from RSS/Atom and JSON sources and displays them via shortcode or block. Includes admin settings for configuring sources and refresh.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       a2z-feed-aggregator
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'A2ZFA_VERSION', '1.0.0' );
define( 'A2ZFA_PLUGIN_FILE', __FILE__ );
define( 'A2ZFA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'A2ZFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once A2ZFA_PLUGIN_DIR . 'includes/class-a2zfa.php';
require_once A2ZFA_PLUGIN_DIR . 'includes/class-a2zfa-admin.php';
require_once A2ZFA_PLUGIN_DIR . 'includes/class-a2zfa-cron.php';

/**
 * Initialize plugin
 */
function a2zfa_init() {
    $plugin = \A2ZFA\Plugin::instance();
    $plugin->init();
}
add_action( 'plugins_loaded', 'a2zfa_init' );

/**
 * Activation / Deactivation
 */
function a2zfa_activate() {
    \A2ZFA\Cron::schedule();
}
register_activation_hook( __FILE__, 'a2zfa_activate' );

function a2zfa_deactivate() {
    \A2ZFA\Cron::clear();
}
register_deactivation_hook( __FILE__, 'a2zfa_deactivate' );
