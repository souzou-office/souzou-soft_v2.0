# souzou-soft v2.0

司法書士法人そうぞう 不動産登記決済業務基盤 v2.0。

仕様書: 「souzou-soft v2.0 改修仕様書」 (2026-04-26)

## このリポジトリの位置づけ

仕様書は ~19 週間の実装を見込んでおり、本リポジトリは **Phase 1〜5 の構造的足場**
（マイグレーション・モデル・主要コントローラ・主要ページ・書類生成エンジン骨格・
通知ジョブ）を一括で配置した v2.0 の出発点である。各 Phase の残工数（テストカバレッジ、
業務種別別 task の精緻化、テンプレ統合、Drive 認証手順整備など）はそれぞれの章を参照。

## 技術スタック (2.1)

| レイヤー | 技術 |
|---|---|
| バックエンド | Laravel 11 (PHP 8.2) |
| フロント連携 | Inertia.js |
| UI | React 18 + TypeScript |
| スタイリング | Tailwind CSS 3 |
| ビルド | Vite |
| 帳票 | phpoffice/phpword + 独自 ${IF_xxx} プリプロセッサ |
| クラウド | Google Drive API (サービスアカウント) |

## ディレクトリ構成

```
app/
  Enums/                        TaskStatus, JobType, RoleCode, NotificationType
  Models/                       Matter, Task, Party, Property, TaskComment,
                                TaskFormInput, TaskPlanningTemplate, DocumentTemplate, Notification
  Http/Controllers/             Dashboard / Matter / Task / Matrix / Team / UserDashboard /
                                Notification / Document / Auth
  Services/
    Tasks/TaskPlanner.php       5.2 / 5.3 主担当者の自動割当 + 期日逆算
    Tasks/AssignmentDetector.php 5.6 未割当検知 + 段階的警告
    Notifications/NotificationDispatcher.php 5.4 通知配信
    Documents/TemplateSelector.php           6.2.1 メタデータ駆動の選択
    Documents/ConditionalPreprocessor.php    6.2.2 ${IF_xxx} 記法の独自実装
    Documents/MatterContext.php              6.3 構造化差込変数の組み立て
    Documents/DocumentGenerator.php          6 章本体
    Drive/DriveUploader.php                  7.x Drive アップロード
  Console/Commands/
    SendDailyTaskNotifications.php           5.4.2 / B.3 朝7時のスケジュールジョブ

database/migrations/            8.x の全テーブル
database/seeders/
  TaskPlanningTemplateSeeder.php 付録A 初期データ
  DocumentTemplateSeeder.php     6.4 統合後のテンプレ構成

resources/js/
  Components/                   StatusDot, TaskStepper, SummaryBar, MiniProgressBar, InlineSelect
  Layouts/AppLayout.tsx         サイドバー + 通知ベル
  Pages/
    Home/Index.tsx              3.2 進捗ステッパー埋め込み一覧
    Matter/Show.tsx             3.1 3層構造ヘッダー + 2.3.5 3カラムレイアウト
    Matrix/Index.tsx            3.3 進捗マトリクス
    User/Dashboard.tsx          4.1 マイビュー / 4.3 個別担当者ビュー
    Team/Index.tsx              4.2 チームビュー
    Notification/Index.tsx      通知センター
    Document/Form.tsx           6.2.3 チェックボックス UI
    Auth/Login.tsx
```

## 仕様書 → 実装の対応

| 仕様書 | 実装場所 |
|---|---|
| 1.1 進捗の可視性不足 | `SummaryBar` / `TaskStepper` / `MatrixController` |
| 1.2 担当者軸の業務管理 | `tasks` migration の `assignee_user_id` 等 + `TeamController` |
| 1.3 書類生成エンジン保守性 | `Services/Documents/*` ＋ `DocumentTemplate.metadata_yaml` |
| 2.2 デザイントークン | `tailwind.config.js` の `colors.brand` / `colors.status` |
| 2.3.1 情報密度 | `resources/css/app.css` の `.table-dense` |
| 2.3.2 色＋形状の二重表現 | `Components/StatusDot.tsx` |
| 2.3.3 インライン編集 | `Components/InlineSelect.tsx` |
| 3.1 3層構造ヘッダー | `Pages/Matter/Show.tsx` の sticky 配置 |
| 3.3 マトリクス | `MatrixController` ＋ `Pages/Matrix/Index.tsx` |
| 4.1 マイビュー | `UserDashboardController::me` ＋ `Pages/User/Dashboard.tsx` |
| 4.2 チームビュー | `TeamController` ＋ `Pages/Team/Index.tsx`（モードA/B トグル） |
| 5.2 主担当者の自動割当 | `TaskPlanner` |
| 5.3 期日逆算 | `TaskPlanner::calculatePlannedDate` |
| 5.4 通知 6 種 | `NotificationType` enum + `NotificationDispatcher` |
| 5.6 未割当検知 | `AssignmentDetector` |
| 5.7 task コメント | `task_comments` + `TaskController::addComment` |
| 6.2.1 YAML 駆動選択 | `DocumentTemplate::appliesWhen()` ＋ `TemplateSelector` |
| 6.2.2 ${IF_xxx} 記法 | `ConditionalPreprocessor` |
| 6.2.3 チェックボックス UI | `Pages/Document/Form.tsx` （UI 定義 YAML から自動生成） |
| 6.3 構造化差込変数 | `MatterContext` |
| 7.x Drive 連携 | `DriveUploader` ＋ `matter_files.drive_folder_id` |
| 8.x データモデル | `database/migrations/2026_04_26_*` |
| 付録A 初期データ | `TaskPlanningTemplateSeeder` |
| 付録B 通知テンプレ | `NotificationType::subjectTemplate()` / `bodyTemplate()` |

## セットアップ

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

開発用シードユーザー: `admin@souzou-office.local` / `password`

## UI プレビュー（依存ゼロで見たい人向け）

`docs/index.html` をブラウザで開くだけで、全主要画面の見た目を確認できる。
Tailwind CDN で React 版と同じデザイントークンを再現した静的 HTML 版。

GitHub Pages を有効化する場合は **Settings → Pages → Source: branch / Folder: /docs**。
詳細は `docs/README.md`。

## 並走運用 (9.2)

v1.x との並走期間中、新規事件のみ v2.0 で作成し、進行中事件は v1.x で完了まで運用する。
本実装は新規事件専用の構造のため、既存事件への遡及適用ロジックは持たない（仕様書 1.2.1 原則4）。

## 並列処理モデル（v2.0 拡張）

不動産決済の現実は直列ではない。事務所運用では：

- **収集タスクが当事者別に並列で走る**（売主・買主・既存銀行・新規銀行・仲介）
- **書類は task の付属物ではなく独立エンティティ**（自前の状態機械を持つ）
- **書類ごとに確定締切が違う**（売主用押印郵送は -10日、融資関係は -2日）
- **マイルストーンは 4 つ**（事前郵送 / 押印返送 / 融資受領 / 全確定）

これを表現するため、v2.0 に以下を追加した：

| エンティティ | 役割 |
|---|---|
| `document_definitions` | 書類マスタ（業務種別ごとに発生する書類を体系定義） |
| `documents` | 事件ごとの書類インスタンス。kind=collection\|creation・状態・確定締切・配送経路・依存書類 |
| `milestones` | 事件ごとの 4 マイルストーン |
| `task_dependencies` | task の DAG（順序ではなく依存） |
| `tasks.milestone_key` | task が属するマイルストーン |

## v2.1 以降のバックログ (9.5)

| 機能 | 状態 |
|---|---|
| 商業登記・相続業務への拡張 | v3.0 |
| recast 連携書類生成 | 未着手 |
| Slack 連携 | NotificationDispatcher を Webhook 化する余地あり |
| 鑑表紙 QR コード | 未着手 |
| スマホ編集モード | 未着手（現行は閲覧専用） |
