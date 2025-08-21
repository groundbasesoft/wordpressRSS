<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** @var array $settings */
/** @var string $nonce */
?>
<div class="wrap">
    <h1><?php esc_html_e( 'A2Z Feed Aggregator', 'a2z-feed-aggregator' ); ?></h1>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="a2zfa-form">
        <input type="hidden" name="action" value="a2zfa_save_settings">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">

        <h2 class="title"><?php esc_html_e( 'Sources', 'a2z-feed-aggregator' ); ?></h2>
        <p><?php esc_html_e( 'Add RSS/Atom or JSON endpoints. Toggle on/off as needed.', 'a2z-feed-aggregator' ); ?></p>
        <table class="widefat fixed striped" id="a2zfa-sources">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Enabled', 'a2z-feed-aggregator' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'a2z-feed-aggregator' ); ?></th>
                    <th><?php esc_html_e( 'Label', 'a2z-feed-aggregator' ); ?></th>
                    <th><?php esc_html_e( 'URL', 'a2z-feed-aggregator' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'a2z-feed-aggregator' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $settings['sources'] as $i => $src ): ?>
                <tr>
                    <td><input type="checkbox" name="sources[<?php echo esc_attr($i); ?>][enabled]" <?php checked( ! empty( $src['enabled'] ) ); ?>></td>
                    <td>
                        <select name="sources[<?php echo esc_attr($i); ?>][type]">
                            <option value="rss" <?php selected( $src['type'] ?? '', 'rss' ); ?>>RSS/Atom</option>
                            <option value="json" <?php selected( $src['type'] ?? '', 'json' ); ?>>JSON</option>
                        </select>
                    </td>
                    <td><input type="text" class="regular-text" name="sources[<?php echo esc_attr($i); ?>][label]" value="<?php echo esc_attr( $src['label'] ?? '' ); ?>"></td>
                    <td><input type="url" class="regular-text code" name="sources[<?php echo esc_attr($i); ?>][url]" value="<?php echo esc_url( $src['url'] ?? '' ); ?>"></td>
                    <td><button class="button a2zfa-remove-row" type="button"><?php esc_html_e( 'Remove', 'a2z-feed-aggregator' ); ?></button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><button class="button button-secondary" id="a2zfa-add-row" type="button"><?php esc_html_e( 'Add Source', 'a2z-feed-aggregator' ); ?></button></p>

        <h2 class="title"><?php esc_html_e( 'Refresh & Limits', 'a2z-feed-aggregator' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Refresh interval', 'a2z-feed-aggregator' ); ?></th>
                <td>
                    <select name="refresh_interval">
                        <?php
                        $options = [
                            'five_minutes' => __( 'Every 5 Minutes', 'a2z-feed-aggregator' ),
                            'fifteen_minutes' => __( 'Every 15 Minutes', 'a2z-feed-aggregator' ),
                            'hourly' => __( 'Hourly', 'a2z-feed-aggregator' ),
                            'twicedaily' => __( 'Twice Daily', 'a2z-feed-aggregator' ),
                            'daily' => __( 'Daily', 'a2z-feed-aggregator' ),
                        ];
                        foreach ( $options as $val => $label ) {
                            printf( '<option value="%s" %s>%s</option>',
                                esc_attr( $val ),
                                selected( $settings['refresh_interval'], $val, false ),
                                esc_html( $label )
                            );
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Max items returned', 'a2z-feed-aggregator' ); ?></th>
                <td><input type="number" min="1" step="1" name="max_items" value="<?php echo esc_attr( $settings['max_items'] ); ?>"></td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'a2z-feed-aggregator' ); ?></button>
            <button type="submit" name="a2zfa_refresh" value="1" class="button"><?php esc_html_e( 'Force Refresh Now', 'a2z-feed-aggregator' ); ?></button>
        </p>
    </form>
    <hr/>
    <h2><?php esc_html_e( 'Display', 'a2z-feed-aggregator' ); ?></h2>
    <p><?php echo esc_html__( 'Use shortcode', 'a2z-feed-aggregator' ); ?> <code>[a2z_feeds limit="20"]</code>. <?php echo esc_html__( 'Optional', 'a2z-feed-aggregator' ); ?> <code>source="Label or index"</code>.</p>
</div>
