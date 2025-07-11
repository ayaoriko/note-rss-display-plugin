# note-rss-display-plugin
このプラグインは、ショートコードを使って note.com のRSSフィードをWordPress上に表示するものです。  
noteで投稿された最新記事のタイトル・日付・サムネイル画像を一覧形式で表示できます。

## インストール手順

1. `note-rss-display-plugin.php` を含む `note-rss-display-plugin` フォルダを ZIP 圧縮します。（※ZIP を展開したときに `note-rss-display-plugin` フォルダが見える状態にしてください）
2. WordPress管理画面の「プラグイン」→「新規追加」→「プラグインのアップロード」からZIPファイルを選択してアップロード・有効化してください。
3. 有効化後、ショートコードを追加することで表示されます。

## 使い方

以下のショートコードを、表示したい位置に貼り付けてください：
```
[note_rss feed_url="https://note.com/ayaoriko" items="3"]
```

- `feed_url`：noteユーザーのURL（※ `/rss` は不要です。プラグイン側で自動補完します）
- `items`：表示させたい記事の件数（最大10件程度を推奨）

## 補足

- RSSに `<media:thumbnail>` タグが含まれている場合は、サムネイル画像も表示されます。
- 表示デザインはシンプルなHTML＋CSS構造になっています。必要に応じてテーマ側でスタイル調整してください。
- 表示内容は1時間ごとにキャッシュされます。  デバッグや開発中にキャッシュを無効化したい場合は、PHPファイル内28行目の以下の行のコメントアウトを外してください：

```php
// delete_transient($cache_key);
```