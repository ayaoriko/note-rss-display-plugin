<?php
/*
Plugin Name: note.com RSS Display Plugin
Description: note.com のRSSフィードを表示するサムネイル付きショートコード
Version: 1.0
Author: ayaoriko
*/

function note_rss_display_shortcode($atts) {
    $atts = shortcode_atts([
        'feed_url' => '',
        'items' => 5,
    ], $atts);
	
	if (empty($atts['feed_url'])) {
    return '<p>表示するnoteのURLを指定してください。</p>';
	}

    // RSS URL補正
    $feed_url = trailingslashit(rtrim($atts['feed_url'], '/'));
    if (substr($feed_url, -3) !== 'rss') {
        $feed_url .= 'rss';
    }

    $cache_key = 'note_rss_cache_' . md5($feed_url);

    // キャッシュクリア（管理画面などで明示的に呼び出す用途があればここを調整）
    // delete_transient($cache_key);

    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached;

    include_once ABSPATH . WPINC . '/feed.php';
    $rss = fetch_feed($feed_url);
    if (is_wp_error($rss)) return '<p>noteのフィードを読み込めませんでした。</p>';

    $maxitems = $rss->get_item_quantity($atts['items']);
    $rss_items = $rss->get_items(0, $maxitems);

    ob_start();
    echo '<div id="note-rss-feed">';
    echo '<ul class="note-rss-feed-list" style="list-style:none; margin:0; padding:0;">';
    foreach ($rss_items as $item) {
        $title = esc_html($item->get_title());
        $link = esc_url($item->get_permalink());
        $date = esc_html($item->get_date('Y/m/d'));
	
        // サムネイル取得（<media:thumbnail>）
        $thumbnail = '';
        $media = $item->get_item_tags('http://search.yahoo.com/mrss/', 'thumbnail');
        if (!empty($media)) {
            if (isset($media[0]['data'])) {
                $thumbnail = esc_url($media[0]['data']);
            } elseif (isset($media[0]['attribs']['']['url'])) {
                $thumbnail = esc_url($media[0]['attribs']['']['url']);
            }
        }
        echo "<li style='display:flex; gap:10px; margin-bottom:15px;'>";
        if ($thumbnail) {
            echo "<a href='{$link}' target='_blank' rel='noopener noreferrer'>";
            echo "<img src='{$thumbnail}' alt='' width='800' height='420' style='width:120px;aspect-ratio: 800 / 420;object-fit:cover; border-radius:4px;' />";
            echo "</a>";
        }
        echo "<div>";
        echo "<a href='{$link}' target='_blank' rel='noopener noreferrer' style='font-weight:bold; text-decoration:none;'>{$title}</a><br />";
        echo "<span style='font-size:0.85em; color:#666;'>({$date})</span>";
        echo "</div>";
        echo "</li>";
    }

    echo '</ul>';
    echo '</div>';

    $output = ob_get_clean();
    set_transient($cache_key, $output, HOUR_IN_SECONDS);

    return $output;
}
add_shortcode('note_rss', 'note_rss_display_shortcode');