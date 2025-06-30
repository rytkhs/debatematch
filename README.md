## DebateMatch

[DebateMatch](http://debate-match.com)は、オンラインディベートを通じて、議論力、論理的思考力、表現力を向上させることを目的としたWebアプリケーションです。ユーザーはリアルタイムで他のユーザーやAIとディベートを行い、終了後にはAIによる評価とフィードバックを受け取ることができます。

![Image](https://github.com/user-attachments/assets/7ff8c821-8e9e-4af8-ba09-493155544c3f)

### 主な機能

- **ルーム管理:** ディベートルームの作成、参加、管理。
- **ディベート進行:** 設定された形式に基づいた進行。
- **テキストチャット:** ディベート中のリアルタイムテキストチャット機能。
- **AIディベート:** AIを対戦相手としてディベート練習が可能。
- **AIによる評価とフィードバック:**
  ディベート終了後、AIが議論内容を分析し、評価とフィードバックを行う。
- **多言語サポート:** 英語と日本語に対応。
- **ディベート履歴:** 過去のディベートとその結果を記録。
- **ユーザー管理:**
  ユーザー登録、ログイン、Googleアカウントでのログイン、プロフィール編集。

### 技術スタック

- **バックエンド:** Laravel 11, PHP 8.3
- **フロントエンド:** Livewire 3, Alpine.js, JavaScript, Tailwind CSS, Vite
- **データベース:** MySQL
- **キャッシング & キュー:** Redis, Laravel Horizon
- **リアルタイム通信:** Pusher, Laravel Echo
- **認証:** Laravel Breeze, Laravel Socialite
- **コンテナ:** Docker (Laravel Sail)
- **AI連携:** OpenRouter API

### インストール

#### 前提条件

- PHP 8.2以上
- Composer
- Docker & Docker Compose (推奨)
- Node.js & npm

#### Dockerでのセットアップ

1.  リポジトリをクローンします:

    ```bash
    git clone https://github.com/rytkhs/debatematch.git
    cd debatematch
    ```

2.  PHPの依存関係をインストールします:

    ```bash
    composer install
    ```

3.  環境ファイルをコピーします:

    ```bash
    cp .env.example .env
    ```

4.  Dockerコンテナを起動します:

    ```bash
    ./vendor/bin/sail up -d
    ```

5.  アプリケーションキーを生成します:

    ```bash
    ./vendor/bin/sail artisan key:generate
    ```

6.  マイグレーションを実行します:

    ```bash
    ./vendor/bin/sail artisan migrate --seed # AIユーザーを含む初期データを作成
    ```

7.  NPMの依存関係をインストールし、アセットをビルドします:

    ```bash
    ./vendor/bin/sail npm install
    ./vendor/bin/sail npm run dev
    ```

### 設定

#### 必要な環境変数

`.env`ファイルで以下を設定します:

- データベース設定
- リアルタイムコミュニケーションのためのPusherの認証情報
- AI機能のためのOpenRouter APIキー
- AWSサービスを使用する場合のAWS認証情報
- AIユーザーのID

Pusher設定:

- Pusher ダッシュボードでアプリを作成し、クレデンシャルを `.env` に設定します。
- Webhook を設定し、`member_added` および `member_removed`
  イベントを有効にします。Webhook URL は `/webhook/pusher` です。

### 使い方

1.  アプリケーションにアクセスします: `http://localhost`
    (または設定されたポート)
2.  アカウントを登録またはログインします
3.  利用可能なディベートルームを閲覧するか、独自のルームを作成します。
4.  ルームに参加し、ディベートの立場を選択します
5.  ルームの形式とルールに従ってディベートに参加します
6.  ディベートの履歴と結果を表示します。

### 開発

詳細な設計ドキュメントは`docs/architecture`を参照してください。

#### キューワーカー

バックグラウンド処理のためにLaravel Horizonキューワーカーを実行します:

```bash
./vendor/bin/sail artisan horizon
```

#### 言語ファイル

アプリケーションは多言語をサポートしています。翻訳ファイルは`lang`ディレクトリにあり、Markdownコンテンツは`resources/markdown/{locale}/`にあります。

### 関連記事

- [【個人開発】オンラインディベートアプリ「DebateMatch」を開発しました！AI講評機能あり](https://qiita.com/rtkhs/items/1ae5fea5d6fa315edbca)
