<?php
/*
 * Plugin Name:       note.com RSS Display Block
 * Description:       note.comのRSSフィードをブロックとして表示します。
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           1.1
 * Author:            ayaoriko
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       note-rss-block
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * ブロックを初期化する関数
 */
function note_rss_display_block_init() {
    register_block_type( __DIR__, [
        'render_callback' => 'render_note_rss_block',
    ] );
}
add_action( 'init', 'note_rss_display_block_init' );

/**
 * ★ここから追加：ショートコードを登録する
 */
function note_rss_shortcode_handler( $atts ) {
    // ショートコードの属性を解析し、デフォルト値を設定
    $atts = shortcode_atts( [
        'feed_url' => '',
        'items'    => 3, // デフォルトの表示件数
        'fullwidth'    => '', // デフォルトの表示件数
    ], $atts, 'note_rss' );
    
    $atts['is_shortcode'] = true;

    // 既存のレンダリング関数を呼び出してHTMLを生成
    return render_note_rss_block( $atts );
}
add_shortcode( 'note_rss', 'note_rss_shortcode_handler' );
/**
 * ★ここまで追加
 */


/**
 * ブロックとショートコードのレンダリング（表示）を行う共通関数
 *
 * @param array $attributes ブロック/ショートコードの属性 (feed_url, items).
 */
function render_note_rss_block( $attributes ) {
    
    // 属性のデフォルト値を設定
    $feed_url = isset($attributes['feed_url']) ? $attributes['feed_url'] : '';
    $items = isset($attributes['items']) ? $attributes['items'] : 3;
    $fullwidth    = isset($attributes['fullwidth ']) ? $attributes['fullwidth'] : ''; // ショートコードからのwidth
    $align    = isset($attributes['align']) ? $attributes['align'] : ''; // ブロックエディタからのalign
    $is_shortcode = isset( $attributes['is_shortcode'] ) && $attributes['is_shortcode'];

    if ( empty( $feed_url ) ) {
        return ''; // 何も表示しないように空文字を返す
    }

    // 元のショートコードのロジックをほぼそのまま流用
    $feed_url_checked = trailingslashit( rtrim( $feed_url, '/' ) );
    if ( substr( $feed_url_checked, -3 ) !== 'rss' ) {
        $feed_url_checked .= 'rss';
    }

    $cache_key = 'note_rss_cache_' . md5( $feed_url_checked . $items.$align.$fullwidth  );
    $cached = get_transient( $cache_key );

    if ( $cached !== false ) {
        return $cached;
    }

    include_once ABSPATH . WPINC . '/feed.php';
    $rss = fetch_feed( $feed_url_checked );

    if ( is_wp_error( $rss ) ) {
        return '<p>noteのフィードを読み込めませんでした。URLが正しいか確認してください。</p>';
    }

    $maxitems  = $rss->get_item_quantity( $items );
    $rss_items = $rss->get_items( 0, $maxitems );
    
    // ケース1: ショートコードからの呼び出し
    if ( $is_shortcode ) {
        $fullwidth = isset($attributes['fullwidth']) ? $attributes['fullwidth'] : '';
        // ★ 最初に基本クラスを必ず設定する
        $classes = 'wp-block-note-rss-block';

        
        if ( $fullwidth === 'true') {
            // widthが指定されていない場合は、alignfullクラスを追加
            $classes .= ' alignfull';
        }
        // 最後にクラスとスタイルを結合する
        $wrapper_attributes = 'class="' . esc_attr( $classes ) . '" ';
    } 
    // ケース2: ブロックエディタからの呼び出し
    else {
        $wrapper_attributes = get_block_wrapper_attributes();
    }

    ob_start();
    ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="swiper">
        <ul class="note-rss-feed-list swiper-wrapper">
            <?php foreach ( $rss_items as $item ) : ?>
                <?php
                    $title     = esc_html( $item->get_title() );
                    $link      = esc_url( $item->get_permalink() );
                    $date = human_time_diff( $item->get_date('U'), current_time('timestamp') ) . '前';
                    $thumbnail = '';
                    $media     = $item->get_item_tags( 'http://search.yahoo.com/mrss/', 'thumbnail' );

                    if ( ! empty( $media ) ) {
                        if ( isset( $media[0]['data'] ) ) {
                            $thumbnail = esc_url( $media[0]['data'] );
                        } elseif ( isset( $media[0]['attribs']['']['url'] ) ) {
                            $thumbnail = esc_url( $media[0]['attribs']['']['url'] );
                        }
                    }
                ?>
                <li class="swiper-slide">
                    <?php if ( $thumbnail ) : ?>
                        <a href="<?php echo $link; ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo $thumbnail; ?>" alt="" width="800" height="420" />
                        </a>
                     <?php else: ?>
                        <div class="img-dmy"></div>
                    <?php endif; ?>
                    <div class="note-rss-feed-list-text">
                        <a href="<?php echo $link; ?>" target="_blank" rel="noopener noreferrer" class="note-rss-feed-list-text-title"><?php echo $title; ?></a>
                        <span class="note-rss-feed-list-text-date"><?php echo $date; ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
          <div class="swiper-pagination"></div>
<div class="note-rss-feed-list-arrow swiper-button-prev"></div>
  <div class="note-rss-feed-list-arrow swiper-button-next"></div>
  </div><!-- .swiper -->
    </div>
    <?php
    $output = ob_get_clean();
    set_transient( $cache_key, $output, HOUR_IN_SECONDS );
    return $output;
}

/**
 * ブロック用のCSSをフロント画面とエディターの両方で読み込む
 */
function note_rss_block_enqueue_assets() {
    $style_path = 'build/style-index.css';
    // ファイルが存在する場合のみ読み込む
    if ( file_exists( plugin_dir_path( __FILE__ ) . $style_path ) ) {
        wp_enqueue_style(
            'note-rss-block-style',
            plugins_url( $style_path, __FILE__ ),
            [],
            filemtime( plugin_dir_path( __FILE__ ) . $style_path )
        );
    }
}
add_action( 'enqueue_block_assets', 'note_rss_block_enqueue_assets' );

/**
 * ブロック用のCSSを【エディター画面でのみ】読み込む
 */
function note_rss_block_enqueue_editor_assets() {
    // editor.scss から生成されるCSSファイル（通常は build/index.css）
    $editor_style_path = 'build/index.css';

    // ファイルが存在する場合のみ読み込む
    if ( file_exists( plugin_dir_path( __FILE__ ) . $editor_style_path ) ) {
        wp_enqueue_style(
            'note-rss-block-editor-style', // 既存のスタイル名と被らないように、ユニークな名前を付ける
            plugins_url( $editor_style_path, __FILE__ ),
            [], // 依存関係
            filemtime( plugin_dir_path( __FILE__ ) . $editor_style_path ) // キャッシュ対策
        );
    }
}
// ★★★ フック名が enqueue_block_editor_assets になっているのがポイント ★★★
add_action( 'enqueue_block_editor_assets', 'note_rss_block_enqueue_editor_assets' );


function note_rss_block_enqueue_swiper() {
    // 現在の投稿内容を取得
    $post_content = get_post_field( 'post_content', get_the_ID() );

    // ブロックもショートコードも存在しない場合は、処理を中断
    if ( ! has_block( 'note-rss/block', $post_content ) && ! has_shortcode( $post_content, 'note_rss' ) ) {
        return;
    }

    // --- Swiper.js本体の読み込み ---
    // もし 'swiper-js' という名前でスクリプトがまだ登録されていなければ
    if ( ! wp_script_is( 'swiper-js', 'registered' ) ) {
        // 自分がSwiper.jsを登録する
        wp_register_script(
            'swiper-js', // ハンドル名（共通の名前を使うのが一般的）
            plugin_dir_url( __FILE__ ) . 'assets/swiper-bundle.min.js', // ファイルの場所
            [],
            '11.0.5', // バージョン番号
            true // </body>の直前で読み込む
        );
        wp_register_style(
            'swiper-css',
            plugin_dir_url( __FILE__ ) . 'assets/swiper-bundle.min.css'
        );
    }
    // 'swiper-js'を実際にページに読み込ませる
    wp_enqueue_script( 'swiper-js' );
    wp_enqueue_style( 'swiper-css' );


    // --- 自分のスライダーを動かすためのJS ---
    wp_enqueue_script(
        'note-rss-swiper-init', // これは自分専用のユニークな名前
        plugin_dir_url( __FILE__ ) . 'assets/slider-init.js',
        ['swiper-js'], // ★重要：swiper-jsが読み込まれた後に実行する
        '1.0.0',
        true
    );
}
// wp_enqueue_scripts はフロント画面でのみ実行される
add_action( 'wp_enqueue_scripts', 'note_rss_block_enqueue_swiper' );