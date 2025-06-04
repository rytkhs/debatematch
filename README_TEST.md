## テスト

このプロジェクトには、JavaScript と PHP（Laravel）の両方に対するテストが含まれています。

### セットアップ

#### JavaScript テスト環境の設定

```bash
# 依存関係のインストール
./vendor/bin/sail npm install

# テストの実行
./vendor/bin/sail npm test

# テストの監視モード（ファイル変更時に自動実行）
./vendor/bin/sail npm run test:watch

# カバレッジレポートの生成
./vendor/bin/sail npm run test:coverage
```

#### PHP テスト

```bash
# PHPUnitテストの実行
./vendor/bin/sail artisan test

# 特定のテストクラスのみ実行
./vendor/bin/sail artisan test tests/Unit/View/Components/DebateFormComponentTest.php

# カバレッジ付きでテスト実行（要xdebug）
./vendor/bin/sail artisan test --coverage
```

### テスト構成

#### JavaScript テスト

-   **場所**: `tests/js/`
-   **フレームワーク**: Jest + jsdom
-   **対象**:
    -   `DebateFormManager` クラス
    -   `StepManager` クラス
    -   `FormatManager` クラス
    -   `CustomFormatManager` クラス
    -   `Utils` クラス
    -   グローバル関数

**主要テストファイル**:

-   `tests/js/debate-form.test.js` - メイン機能のテスト
-   `tests/js/custom-format-manager.test.js` - カスタムフォーマット機能のテスト

#### PHP テスト

-   **場所**: `tests/Unit/View/Components/`
-   **フレームワーク**: PHPUnit
-   **対象**: Blade コンポーネント
    -   `step-indicator.blade.php`
    -   `basic-info-step.blade.php`
    -   `debate-settings-step.blade.php`
    -   `format-preview.blade.php`
    -   `custom-format-settings.blade.php`
    -   `free-format-settings.blade.php`

**主要テストファイル**:

-   `tests/Unit/View/Components/DebateFormComponentTest.php` - Blade コンポーネントのテスト

### テスト内容

#### JavaScript テストカバレッジ

-   **初期化**: クラスのコンストラクタと init()メソッド
-   **ステップ管理**: ナビゲーション、検証、UI 更新
-   **フォーマット管理**: プレビュー生成、表示切り替え
-   **カスタムフォーマット**: ターン追加/削除、入力処理
-   **ユーティリティ**: エラー表示、アニメーション
-   **グローバル関数**: 後方互換性のある関数

#### PHP テストカバレッジ

-   **レンダリング**: 各コンポーネントの正常レンダリング
-   **プロパティ処理**: formType、showRoomName 等の条件分岐
-   **バリデーション**: エラーメッセージの表示
-   **レスポンシブ**: 適切な CSS クラスの適用
-   **アクセシビリティ**: required 属性、ARIA 要素

### テスト実行例

```bash
# JavaScript: すべてのテストを実行
npm test

# JavaScript: カスタムフォーマットのテストのみ
npx jest custom-format-manager.test.js

# PHP: Bladeコンポーネントのテストのみ
php artisan test --filter=DebateFormComponentTest

# PHP: 特定のテストメソッドのみ
php artisan test --filter=step_indicator_renders_correctly
```

### カバレッジレポート

#### JavaScript

カバレッジレポートは `coverage/` ディレクトリに生成されます：

-   `coverage/lcov-report/index.html` - ブラウザで閲覧可能なレポート
-   `coverage/lcov.info` - CI 用のレポート

#### PHP

PHPUnit のカバレッジは xdebug または pcov が必要です：

```bash
# カバレッジレポート生成
php artisan test --coverage-html coverage
```

### 継続的インテグレーション

テストは以下の場合に自動実行されることを推奨します：

-   プルリクエスト作成時
-   main ブランチへのマージ時
-   定期的な実行（nightly build）

### テスト品質指標

-   **JavaScript**: 90%以上のコードカバレッジを目標
-   **PHP**: 85%以上のコードカバレッジを目標
-   **回帰テスト**: 既存機能への影響確認
-   **ユニットテスト**: 各クラス・コンポーネントの独立テスト

## 開発

### ディベートフォームのリファクタリング

2024 年のリファクタリングにより、以下の改善が実現されました：

#### コード削減

-   **rooms/create.blade.php**: 1223 行 → 104 行（91%削減）
-   **ai/debate/create.blade.php**: 1176 行 → 98 行（92%削減）
-   **総削減率**: 約 67%（2399 行 → 約 800 行）

#### 構造改善

-   **共通 JavaScript**: `public/js/debate-form.js`に統合
-   **Blade コンポーネント化**: 再利用可能な小さなコンポーネントに分割
-   **設定ベースの差分管理**: room/ai 形式の違いをプロパティで制御

#### 品質向上

-   **保守性**: 各コンポーネントが単一責任
-   **テスタビリティ**: 独立したユニットテスト
-   **拡張性**: 新しいフォーマット追加が容易
-   **可読性**: ファイルサイズ大幅縮小

詳細な技術仕様については、プロジェクトドキュメントを参照してください。
