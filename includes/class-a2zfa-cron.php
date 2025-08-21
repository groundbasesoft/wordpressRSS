<?php
namespace A2ZFA;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cron {
    const HOOK = 'a2zfa_refresh_event';

    public static function schedule() {
        $settings = Plugin::get_settings();
        $interval = isset( $settings['refresh_interval'] ) ? $settings['refresh_interval'] : 'hourly';
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time() + 60, $interval, self::HOOK );
        } else {
            // Reschedule to new interval if changed
            self::clear();
            wp_schedule_event( time() + 60, $interval, self::HOOK );
        }
    }

    public static function clear() {
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK );
        }
    }
}
