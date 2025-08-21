<?php
namespace A2ZFA;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {
    private static $instance = null;
    const OPTION_KEY = 'a2zfa_settings';
    const TRANSIENT_PREFIX = 'a2zfa_cache_';

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'init', [ $this, 'register_shortcode' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest' ] );
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );
        add_action( 'a2zfa_refresh_event', [ $this, 'refresh_all_sources' ] );
    }

    public static function get_settings() {
        $defaults = [
            'sources' => [
                // Example:
                // ['type' => 'rss', 'url' => 'https://example.com/feed', 'label' => 'Example RSS', 'enabled' => true],
            ],
            'refresh_interval' => 'hourly',
            'max_items' => 50
        ];
        $settings = get_option( self::OPTION_KEY, [] );
        return wp_parse_args( $settings, $defaults );
    }

    public static function update_settings( $settings ) {
        update_option( self::OPTION_KEY, $settings );
    }

    public function add_cron_schedules( $schedules ) {
        // Add a 5-minute and 15-minute interval for power users
        $schedules['five_minutes'] = [
            'interval' => 5 * 60,
            'display'  => __( 'Every 5 Minutes', 'a2z-feed-aggregator' )
        ];
        $schedules['fifteen_minutes'] = [
            'interval' => 15 * 60,
            'display'  => __( 'Every 15 Minutes', 'a2z-feed-aggregator' )
        ];
        return $schedules;
    }

    public function register_shortcode() {
        add_shortcode( 'a2z_feeds', [ $this, 'render_shortcode' ] );
    }

    public function register_rest() {
        register_rest_route( 'a2z-fa/v1', '/items', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_get_items' ],
            'permission_callback' => '__return_true',
            'args' => [
                'limit' => [
                    'type' => 'integer',
                    'required' => false,
                    'default' => 20,
                ],
            ],
        ] );
    }

    public function rest_get_items( $request ) {
        $limit = isset( $request['limit'] ) ? absint( $request['limit'] ) : 20;
        $items = $this->get_aggregated_items( $limit );
        return rest_ensure_response( $items );
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'limit' => 20,
            'source' => '', // optional: filter by label or index
        ], $atts, 'a2z_feeds' );
        $items = $this->get_aggregated_items( absint( $atts['limit'] ), $atts['source'] );
        ob_start();
        $template = A2ZFA_PLUGIN_DIR . 'templates/feed-list.php';
        if ( file_exists( $template ) ) {
            include $template;
        } else {
            echo '<ul class="a2zfa-feed-list">';
            foreach ( $items as $item ) {
                printf(
                    '<li><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> <span class="a2zfa-source">(%3$s)</span></li>',
                    esc_url( $item['link'] ),
                    esc_html( $item['title'] ),
                    esc_html( $item['source_label'] )
                );
            }
            echo '</ul>';
        }
        return ob_get_clean();
    }

    /**
     * Aggregate items from all enabled sources.
     */
    public function get_aggregated_items( $limit = 20, $source_filter = '' ) {
        $settings = self::get_settings();
        $items = [];

        foreach ( $settings['sources'] as $index => $source ) {
            if ( empty( $source['enabled'] ) ) continue;
            if ( $source_filter && ! $this->matches_filter( $source, $source_filter, $index ) ) continue;

            $src_items = $this->get_source_items( $source );
            foreach ( $src_items as $it ) {
                $it['source_label'] = isset( $source['label'] ) ? $source['label'] : ( $source['url'] ?? 'Source ' . $index );
                $items[] = $it;
            }
        }

        // Sort by date desc if available
        usort( $items, function( $a, $b ){
            $ad = isset( $a['date_ts'] ) ? $a['date_ts'] : 0;
            $bd = isset( $b['date_ts'] ) ? $b['date_ts'] : 0;
            return $bd <=> $ad;
        });

        return array_slice( $items, 0, $limit );
    }

    private function matches_filter( $source, $filter, $index ) {
        $label = isset( $source['label'] ) ? $source['label'] : '';
        if ( strcasecmp( $label, $filter ) === 0 ) return true;
        return (string)$index === (string)$filter;
    }

    private function cache_key( $source ) {
        $url = isset( $source['url'] ) ? $source['url'] : '';
        return self::TRANSIENT_PREFIX . md5( $source['type'] . '|' . $url );
    }

    private function get_source_items( $source ) {
        $cache_key = $this->cache_key( $source );
        $cached = get_transient( $cache_key );
        if ( $cached !== false ) {
            return $cached;
        }

        $items = [];
        $type = isset( $source['type'] ) ? sanitize_key( $source['type'] ) : 'rss';
        $url  = isset( $source['url'] ) ? esc_url_raw( $source['url'] ) : '';

        if ( $type === 'rss' || $type === 'atom' ) {
            if ( ! function_exists( 'fetch_feed' ) ) {
                include_once ABSPATH . WPINC . '/feed.php';
            }
            $feed = fetch_feed( $url );
            if ( ! is_wp_error( $feed ) ) {
                $maxitems = $feed->get_item_quantity( 20 );
                $feed_items = $feed->get_items( 0, $maxitems );
                foreach ( $feed_items as $item ) {
                    $items[] = [
                        'title'   => wp_strip_all_tags( $item->get_title() ),
                        'link'    => esc_url_raw( $item->get_link() ),
                        'date'    => $item->get_date( 'c' ),
                        'date_ts' => (int) $item->get_date( 'U' ),
                        'summary' => wp_strip_all_tags( $item->get_description() ),
                    ];
                }
            }
        } elseif ( $type === 'json' ) {
            $response = wp_remote_get( $url, [ 'timeout' => 15 ] );
            if ( ! is_wp_error( $response ) ) {
                $code = wp_remote_retrieve_response_code( $response );
                if ( $code >= 200 && $code < 300 ) {
                    $body = wp_remote_retrieve_body( $response );
                    $data = json_decode( $body, true );
                    if ( is_array( $data ) ) {
                        // Heuristics: try a few common shapes
                        $rows = [];
                        if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
                            $rows = $data['items'];
                        } elseif ( isset( $data['entries'] ) && is_array( $data['entries'] ) ) {
                            $rows = $data['entries'];
                        } elseif ( isset( $data[0] ) ) {
                            $rows = $data;
                        }
                        foreach ( $rows as $row ) {
                            if ( ! is_array( $row ) ) continue;
                            $title = isset( $row['title'] ) ? $row['title'] : ( $row['name'] ?? '(no title)' );
                            $link  = isset( $row['url'] ) ? $row['url'] : ( $row['link'] ?? '#' );
                            $date  = isset( $row['date'] ) ? $row['date'] : ( $row['published'] ?? ( $row['pubDate'] ?? '' ) );
                            $summary = isset( $row['summary'] ) ? $row['summary'] : ( $row['description'] ?? '' );
                            $items[] = [
                                'title'   => wp_strip_all_tags( (string) $title ),
                                'link'    => esc_url_raw( (string) $link ),
                                'date'    => $date ? esc_html( $date ) : '',
                                'date_ts' => $date ? strtotime( $date ) : 0,
                                'summary' => wp_strip_all_tags( (string) $summary ),
                            ];
                        }
                    }
                }
            }
        }

        // Cache for chosen interval (use WP-Cron schedule to keep in sync)
        $settings = self::get_settings();
        $interval = isset( $settings['refresh_interval'] ) ? $settings['refresh_interval'] : 'hourly';
        $ttl = 3600;
        if ( $interval === 'five_minutes' ) $ttl = 300;
        elseif ( $interval === 'fifteen_minutes' ) $ttl = 900;
        elseif ( $interval === 'hourly' ) $ttl = HOUR_IN_SECONDS;
        elseif ( $interval === 'twicedaily' ) $ttl = 12 * HOUR_IN_SECONDS;
        elseif ( $interval === 'daily' ) $ttl = DAY_IN_SECONDS;

        set_transient( $cache_key, $items, $ttl );
        return $items;
    }

    public function refresh_all_sources() {
        $settings = self::get_settings();
        foreach ( $settings['sources'] as $source ) {
            delete_transient( $this->cache_key( $source ) );
            // Re-prime
            $this->get_source_items( $source );
        }
    }
}
