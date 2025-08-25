<?php
/*
 * Plugin Name:       note.com RSS Display Block
 * Description:       note.comのRSSフィードを表示するプラグインです。
 * Plugin URI: https://ayaoriko.com/coding/wordpress/note-rss/
 * Requires PHP:      7.3
 * Version:           1.3
 * Author: あやおり子@ayaoriko
 * Author URI: https://ayaoriko.com/
 * Text Domain:       note-rss-block
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * デバッグモードかどうか判定する共通関数
 *
 * 定義されていない場合は false 
 *
 * @return bool
 */
function note_rss_is_debug() {
    return false;
}


/**
 * アップデーター
 */
require 'updater/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://ayaoriko.com/plugin-update-file/note-rss-block/version.json',
	__FILE__,
	'note-rss-block'
);

/**
 * ブロックを初期化する関数
 */
function note_rss_display_block_init() {
    register_block_type( __DIR__, [
        'render_callback' => 'render_note_rss_block',
    ] );

    // JS 側翻訳を読み込む
    wp_set_script_translations(
        'note-rss/block-editor-script', // editorScript のハンドル名
        'note-rss-block',               // テキストドメイン
        plugin_dir_path( __FILE__ ) . 'languages'
    );
}
add_action( 'init', 'note_rss_display_block_init' );

/**
 * ショートコードを登録する
 */
function note_rss_shortcode_handler( $atts ) {
    // ショートコードの属性を解析し、デフォルト値を設定
    $atts = shortcode_atts( [
        'feed_url' => '',
        'items'    => 10, // デフォルトの表示件数
        'slide'    => 'on', // スライドさせるか
        'fullwidth'    => 'on', // 横幅
    ], $atts, 'note_rss' );
    
    $atts['is_shortcode'] = true;

    // 既存のレンダリング関数を呼び出してHTMLを生成
    return render_note_rss_block( $atts );
}
add_shortcode( 'note_rss', 'note_rss_shortcode_handler' );

/**
 * ブロックとショートコードのレンダリング（表示）を行う共通関数
 *
 * @param array $attributes ブロック/ショートコードの属性 (feed_url, items).
 */
function render_note_rss_block( $attributes ) {
    
    // 属性のデフォルト値を設定
    $feed_url = isset($attributes['feed_url']) ? $attributes['feed_url'] : '';
    $items = isset($attributes['items']) ? $attributes['items'] : 3;
    $slide     = isset($attributes['slide']) ? $attributes['slide'] : 'on';
    $fullwidth = isset($attributes['fullwidth']) ? $attributes['fullwidth'] : 'on'; // クラシックエディタ指定
    $align    = isset($attributes['align']) ? $attributes['align'] : ''; // ブロックエディタ指定
    $is_shortcode = isset( $attributes['is_shortcode'] ) && $attributes['is_shortcode'];

    if ( empty( $feed_url ) ) {
        return ''; // 何も表示しないように空文字を返す
    }

    // 元のショートコードのロジックをほぼそのまま流用
    $feed_url_checked = trailingslashit( rtrim( $feed_url, '/' ) );
    if ( substr( $feed_url_checked, -3 ) !== 'rss' ) {
        $feed_url_checked .= 'rss';
    }
    
    $debug_mode = note_rss_is_debug();

    if ( $debug_mode ) {
        $cache_key = ''; 
        $cached = false; // 強制的にキャッシュ無効
    } else {
        $cache_key = 'note_rss_cache_' . md5( $feed_url_checked . $items . $align . $fullwidth . $slide );
        $cached = get_transient( $cache_key );
    }
    
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
        // ★ 最初に基本クラスを必ず設定する
        $classes = 'wp-block-note-rss-block';

        
        if ( $fullwidth === 'on') {
            // widthが指定されていない場合は、alignfullクラスを追加
            $classes .= ' alignfull';
        }
        // 最後にクラスとスタイルを結合する
        $wrapper_attributes = 'class="' . esc_attr( $classes ) . '" ';
    } else {
         // ケース2: ブロックエディタからの呼び出し
        $wrapper_attributes = get_block_wrapper_attributes();
        if ( preg_match( '/class="([^"]+)"/', $wrapper_attributes, $matches ) ) {
            $classes = explode( ' ', $matches[1] );
            if ( !in_array( 'alignfull', $classes, true ) ) {
                // alignfull のときの処理
            }
        }
    }
    if($slide === 'on'){
        $slide_attributes_class = 'swiper';
    }else{
        $slide_attributes_class = 'note-rss-no-slide';
    }

    ob_start();
    ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="<?= $slide_attributes_class ?>">
        <ul class="note-rss-feed-list <?= $slide_attributes_class ?>-wrapper">
            <?php foreach ( $rss_items as $item ) : ?>
                <?php
                    $title     = esc_html( $item->get_title() );
                    $link      = esc_url( $item->get_permalink() );
                    $date = human_time_diff( $item->get_date('U'), current_time('timestamp') );
                    if ( get_locale() === 'en_US' ) {
                        $date .= ' ago';
                    } else {
                        $date .= '前';
                    }
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
                <li class="<?= $slide_attributes_class ?>-slide">
                    <?php if ( $thumbnail ) : ?>
                        <a href="<?php echo $link; ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo $thumbnail; ?>" alt="" width="800" height="420" />
                        </a>
                     <?php else: ?>
                        <div id="note-rss-dumy-img" class="img-dmy"></div>
                    <?php endif; ?>
                    <div class="note-rss-feed-list-text">
                        <a href="<?php echo $link; ?>" target="_blank" rel="noopener noreferrer" class="note-rss-feed-list-text-title"><?php echo $title; ?></a>
                        <span class="note-rss-feed-list-text-date"><?php echo $date; ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
          <div class="note-rss-feed-list-pegnation <?= $slide_attributes_class ?>-pagination"></div>
<div class="note-rss-feed-list-arrow <?= $slide_attributes_class ?>-button-prev"></div>
  <div class="note-rss-feed-list-arrow <?= $slide_attributes_class ?>-button-next"></div>
  </div><!-- .<?= $slide_attributes_class ?> -->
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
            'note-rss-block-editor-style', // ユニークな名前を付ける
            plugins_url( $editor_style_path, __FILE__ ),
            [], // 依存関係
            filemtime( plugin_dir_path( __FILE__ ) . $editor_style_path ) // キャッシュ対策
        );
    }
}
add_action( 'enqueue_block_editor_assets', 'note_rss_block_enqueue_editor_assets' );


/**
 * Swiperの読み込み設定
 */

function note_rss_block_enqueue_swiper() {
    // 現在の投稿内容を取得
    $post_content = get_post_field( 'post_content', get_the_ID() );
    
    $load_swiper = false;

    // ブロックもショートコードも存在しない場合は、処理を中断
    if ( ! has_block( 'note-rss/block', $post_content ) && ! has_shortcode( $post_content, 'note_rss' ) ) {
        return;
    }
    // ブロックがある場合
    if ( has_block( 'note-rss/block', $post_content ) ) {
        $blocks = parse_blocks( $post_content );
        foreach ( $blocks as $block ) {
            if ( $block['blockName'] === 'note-rss/block' ) {
                $slide = $block['attrs']['slide'] ?? 'on';
                if ( $slide !== 'off' ) {
                    $load_swiper = true;
                    break;
                }
            }
        }
    }

    
    // ショートコードがある場合
    if ( ! $load_swiper && has_shortcode( $post_content, 'note_rss' ) ) {
        preg_match_all( '/\[note_rss[^\]]*slide=(["\'])(on|off)\1[^\]]*\]/', $post_content, $matches );
        if ( ! empty( $matches[2] ) && in_array( 'on', $matches[2], true ) ) {
            $load_swiper = true;
        }
    }

    
    if ( ! $load_swiper ) {
        return; // slideがoffなら読み込まない
    }
    // --- Swiper.js本体の読み込み ---
    // もし 'swiper-js' という名前でスクリプトがまだ登録されていなければ、自分がSwiper.jsを登録する
    if ( ! wp_script_is( 'swiper-js', 'registered' ) ) {
        wp_register_script(
            'swiper-js',
            plugin_dir_url( __FILE__ ) . 'assets/swiper-bundle.min.js', // ファイルの場所
            [],
            '11.0.5', 
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


    // --- スライダーを動かすためのJS ---
    wp_enqueue_script(
        'note-rss-swiper-init', // 専用のユニークな名前
        plugin_dir_url( __FILE__ ) . 'assets/slider-init.js',
        ['swiper-js'], 
        '1.0.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'note_rss_block_enqueue_swiper' );

/**
 * アンケートと支援のお願い
 */
function note_plugin_admin_notice() {
    // 管理者のみ
    if (!current_user_can('manage_options')) return;

    $user_id = get_current_user_id();
    
    $debug_mode = note_rss_is_debug();
    
    if (! $debug_mode ) {
        // 既に表示済みか確認
        if ( get_user_meta( $user_id, 'note_plugin_notice_shown', true ) ) {
            return; // すでに表示済みなら終了
        }
    }

    // 通知を出す
    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>' . esc_html__( 'note.com RSS Display Block プラグインをご利用いただき、ありがとうございます！！', 'note-rss-block' ) . '</strong></p>';

    echo '<p><strong>' . esc_html__( '■ アンケートご協力のお願い', 'note-rss-block' ) . '</strong><br>' .
        esc_html__( '今後の活動に役立てるためのアンケートにご協力いただけると嬉しいです。', 'note-rss-block' ) . '<br>' .
        sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url("https://ayaoriko.studio.site/chip"),
            esc_html__( 'アンケートはこちら', 'note-rss-block' )
        ) .
        '</p>';

    echo '<p><strong>' . esc_html__( '■ 支援のお願い', 'note-rss-block' ) . '</strong><br>' .
        esc_html__( '私の活動を応援していただく形で、ほしい物リストからご支援いただけると、開発のモチベーションがアップします。', 'note-rss-block' ) . '<br>' .
        sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url("https://www.amazon.jp/hz/wishlist/ls/2FAK8W49YO9DT?ref_=wl_share"),
            esc_html__( 'ほしい物リストを見る', 'note-rss-block' )
        ) .
        '</p>';
    echo '</div>';

    update_user_meta( $user_id, 'note_plugin_notice_shown', 1 );
}

add_action('admin_notices', 'note_plugin_admin_notice');


