<?php
/**
 * @var array $items
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$display = function( $item ){
    $date = '';
    if ( ! empty( $item['date_ts'] ) ) {
        $date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['date_ts'] );
    }
    ?>
    <li class="a2zfa-item">
        <a href="<?php echo esc_url( $item['link'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['title'] ); ?></a>
        <?php if ( $date ): ?>
            <small class="a2zfa-date"> — <?php echo esc_html( $date ); ?></small>
        <?php endif; ?>
        <small class="a2zfa-source"> (<?php echo esc_html( $item['source_label'] ); ?>)</small>
        <?php if ( ! empty( $item['summary'] ) ): ?>
            <div class="a2zfa-summary"><?php echo esc_html( wp_trim_words( $item['summary'], 40 ) ); ?></div>
        <?php endif; ?>
    </li>
    <?php
};
?>
<ul class="a2zfa-feed-list">
    <?php foreach ( $items as $item ) $display( $item ); ?>
</ul>
