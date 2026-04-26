# UI プレビュー (docs/)

仕様書「souzou-soft v2.0 改修仕様書」の主要画面を、依存関係ゼロで開ける
**静的 HTML プレビュー** として配置。Tailwind CDN で React 版（`resources/js/Pages/`）と
同じ見た目を再現している。

## ローカルで開く

`docs/index.html` をブラウザで開くだけ。

```bash
# macOS
open docs/index.html
# Linux
xdg-open docs/index.html
```

## GitHub Pages で公開する

GitHub リポジトリの **Settings → Pages** で次を設定:

- **Source**: Deploy from a branch
- **Branch**: `claude/souzou-soft-v2-rebuild-rAsgy`（または `main`）
- **Folder**: `/docs`

数分後に `https://<owner>.github.io/<repo>/` で `docs/index.html` が公開される。

## 画面一覧

| ファイル | 仕様書対応 |
|---|---|
| `index.html`         | プレビュー一覧 + StatusDot 凡例 |
| `home.html`          | 3.2 ホーム（事件一覧 + 進捗ステッパー埋め込み） |
| `matter.html`        | 3.1 事件詳細（3層構造ヘッダー + 3カラム） |
| `matrix.html`        | 3.3 進捗マトリクス（対象外セル斜線） |
| `my.html`            | 4.1 マイビュー（4セクション） |
| `team.html`          | 4.2 チームビュー（モードA/B 切替） |
| `notifications.html` | 5.4 / B.1 通知センター |
| `document.html`      | 6.2.3 書類生成チェックボックス UI |
| `login.html`         | 認証画面 |

## 注意

これは「見た目の確認用」プレビューで、データはハードコードされたモック。
実際のデータ駆動・対話操作は Laravel + Inertia + React 版で動く。
