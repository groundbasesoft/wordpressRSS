<?php
namespace A2ZFA;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
        add_action( 'admin_post_a2zfa_save_settings', [ $this, 'save_settings' ] );
    }

    public static function instance() {
        static $inst = null;
        if ( $inst === null ) {
            $inst = new self();
        }
        return $inst;
    }

    public function menu() {
        add_menu_page(
            __( 'A2Z Feeds', 'a2z-feed-aggregator' ),
            __( 'A2Z Feeds', 'a2z-feed-aggregator' ),
            'manage_options',
            'a2zfa',
            [ $this, 'render_page' ],
            'dashicons-rss',
            81
        );
    }

    public function register_settings() {
        register_setting( 'a2zfa', Plugin::OPTION_KEY );
    }

    public function assets( $hook ) {
        if ( $hook !== 'toplevel_page_a2zfa' ) return;
        wp_enqueue_style( 'a2zfa-admin', A2ZFA_PLUGIN_URL . 'assets/css/admin.css', [], A2ZFA_VERSION );
        wp_enqueue_script( 'a2zfa-admin', A2ZFA_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], A2ZFA_VERSION, true );
    }

    public function render_page() {
        $settings = Plugin::get_settings();
        $nonce = wp_create_nonce( 'a2zfa_save_settings' );
        include A2ZFA_PLUGIN_DIR . 'templates/admin-page.php';
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'a2z-feed-aggregator' ) );
        }
        check_admin_referer( 'a2zfa_save_settings' );
        $settings = Plugin::get_settings();

        $incoming_sources = isset( $_POST['sources'] ) && is_array( $_POST['sources'] ) ? $_POST['sources'] : [];
        $sanitized = [];
        foreach ( $incoming_sources as $src ) {
            $sanitized[] = [
                'enabled' => isset( $src['enabled'] ) ? (bool) $src['enabled'] : false,
                'type'    => isset( $src['type'] ) ? sanitize_key( $src['type'] ) : 'rss',
                'label'   => isset( $src['label'] ) ? sanitize_text_field( $src['label'] ) : '',
                'url'     => isset( $src['url'] ) ? esc_url_raw( $src['url'] ) : '',
            ];
        }
        $settings['sources'] = $sanitized;
        $settings['refresh_interval'] = isset( $_POST['refresh_interval'] ) ? sanitize_key( $_POST['refresh_interval'] ) : 'hourly';
        $settings['max_items'] = isset( $_POST['max_items'] ) ? max( 1, absint( $_POST['max_items'] ) ) : 50;

        Plugin::update_settings( $settings );

        // Reschedule cron
        Cron::clear();
        Cron::schedule();

        // Optional manual refresh
        if ( isset( $_POST['a2zfa_refresh'] ) ) {
            Plugin::instance()->refresh_all_sources();
        }

        wp_safe_redirect( admin_url( 'admin.php?page=a2zfa' ) );
        exit;
    }
}
// Boot the Admin class
add_action( 'plugins_loaded', function() {
    \A2ZFA\Admin::instance();
});
