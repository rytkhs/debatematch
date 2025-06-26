# 認証・認可設計図

## 概要

DebateMatchは、Laravel BreezeとLaravel Socialiteを基盤とした認証システムを実装しています。通常のEmail/Password認証に加え、Googleアカウントによるソーシャル認証、ゲストユーザー機能を提供し、きめ細かい権限管理を実現しています。

## 認証システム全体構成

```mermaid
graph TB
    subgraph "認証エントリーポイント"
        Login[ログイン画面]
        Register[登録画面]
        Guest[ゲスト利用]
        Google[Google OAuth]
    end

    subgraph "Laravel Breeze"
        Auth[Auth Guard]
        Session[Session Manager]
        Cookie[Remember Token]
    end

    subgraph "Laravel Socialite"
        OAuth[OAuth Handler]
        Provider[Google Provider]
    end

    subgraph "認証ミドルウェア"
        AM[auth]
        GM[guest]
        AGM[AdminMiddleware]
        CGE[CheckGuestExpiration]
        CUS[CheckUserActiveStatus]
        VDA[ValidateDebateAccess]
    end

    subgraph "ユーザーモデル"
        User[User Model]
        Admin[Admin Role]
        Normal[Normal User]
        GuestUser[Guest User]
    end

    Login --> Auth
    Register --> Auth
    Guest --> GuestUser
    Google --> OAuth

    OAuth --> Provider
    Provider --> Auth

    Auth --> Session
    Auth --> Cookie
    Auth --> User

    User --> Admin
    User --> Normal
    User --> GuestUser

    Auth --> AM
    AM --> GM
    AM --> AGM
    AM --> CGE
    AM --> CUS
    AM --> VDA
```

## 認証フロー

### 1. Email/Password認証フロー

```mermaid
sequenceDiagram
    participant User as ユーザー
    participant Browser as ブラウザ
    participant Login as ログイン画面
    participant Auth as AuthController
    participant Validation as 検証
    participant DB as Database
    participant Session as セッション

    User->>Browser: ログインページアクセス
    Browser->>Login: GET /login
    Login-->>User: ログインフォーム表示

    User->>Browser: 認証情報入力
    Browser->>Auth: POST /login
    Auth->>Validation: 入力検証

    alt 検証成功
        Validation->>DB: ユーザー検索
        DB->>Auth: ユーザー情報
        Auth->>Auth: パスワード照合

        alt 認証成功
            Auth->>Session: セッション生成
            Auth->>Browser: リダイレクト（ダッシュボード）
            Browser-->>User: ログイン成功
        else 認証失敗
            Auth->>Browser: エラーレスポンス
            Browser-->>User: エラー表示
        end
    else 検証失敗
        Validation->>Browser: バリデーションエラー
        Browser-->>User: エラー表示
    end
```

### 2. Google OAuth認証フロー

```mermaid
sequenceDiagram
    participant User as ユーザー
    participant App as DebateMatch
    participant Socialite as Laravel Socialite
    participant Google as Google OAuth
    participant DB as Database

    User->>App: Googleでログイン
    App->>Socialite: OAuth開始
    Socialite->>Google: 認証リクエスト
    Google-->>User: Googleログイン画面

    User->>Google: 認証情報入力
    Google->>Google: 認証処理
    Google->>Socialite: 認証コールバック

    Socialite->>App: ユーザー情報
    App->>DB: ユーザー検索/作成

    alt 既存ユーザー
        DB-->>App: ユーザー情報
        App->>App: ログイン処理
    else 新規ユーザー
        App->>DB: ユーザー作成
        DB-->>App: 新規ユーザー
        App->>App: ログイン処理
    end

    App-->>User: ログイン完了
```

### 3. ゲストユーザー認証フロー

```mermaid
flowchart TB
    Start([ゲスト利用開始]) --> Create[ゲストユーザー作成]

    Create --> Generate{ユーザー生成}
    Generate --> Name[ランダムな名前生成]
    Generate --> Password[ランダムパスワード]
    Generate --> Expiry[有効期限設定<br/>2時間]

    Name --> Save[データベース保存]
    Password --> Save
    Expiry --> Save

    Save --> Login[自動ログイン]
    Login --> Session[セッション生成]
    Session --> Redirect[利用開始]

    Redirect --> Monitor[有効期限監視]
    Monitor --> Check{期限チェック}

    Check -->|有効| Continue[利用継続]
    Check -->|期限切れ| Logout[自動ログアウト]

    Continue --> Monitor
    Delete --> End([終了])
```

## 権限管理システム

### ミドルウェア階層

```mermaid
graph TD
    Request[HTTPリクエスト] --> Global[グローバルミドルウェア]

    Global --> SetLocale[SetLocale<br/>言語設定]
    SetLocale --> Route[ルートミドルウェア]

    Route --> A{ルート種別}

    A -->|認証必須| Auth[auth]
    A -->|管理者| Admin[AdminMiddleware]

    Auth --> CGE[CheckGuestExpiration<br/>ゲスト期限確認]
    CGE --> CUS[CheckUserActiveStatus<br/>アクティブ状態確認]
    CUS --> VDA[ValidateDebateAccess<br/>ディベートアクセス検証]

    Admin --> AdminCheck{管理者チェック}
    AdminCheck -->|OK| Controller
    AdminCheck -->|NG| Redirect[リダイレクト]

    VDA --> Controller[コントローラー]
```

### 権限レベル定義

| 権限レベル   | 説明             | 可能な操作                                 |
| ------------ | ---------------- | ------------------------------------------ |
| ゲスト       | 一時的なユーザー | 全機能利用可能、（2時間限定）　　　　　　　　　　 |
| 通常ユーザー | 登録済みユーザー | 全機能利用可能、履歴保存                   　　　|
| 管理者       | is_admin = true  | 全機能 + 管理画面アクセス                  　|

### ミドルウェア詳細

#### 1. CheckGuestExpiration

```php
// ゲストユーザーの有効期限をチェック
if ($user->isGuest() && $user->isExpired()) {
    Auth::logout();
    return redirect()->route('welcome')
        ->with('info', 'ゲストアカウントの有効期限が切れました');
}
```

#### 2. CheckUserActiveStatus

```mermaid
stateDiagram-v2
    [*] --> Check: リクエスト受信

    Check --> Authenticated: 認証確認
    Check --> Pass: 未認証

    Authenticated --> DebateCheck: ディベート中確認
    DebateCheck --> InDebate: ディベート中
    DebateCheck --> RoomCheck: ディベートなし

    InDebate --> AllowDebate: ディベート関連
    InDebate --> BlockOther: その他

    RoomCheck --> InRoom: ルーム参加中
    RoomCheck --> Pass: ルームなし

    InRoom --> AllowRoom: ルーム関連
    InRoom --> BlockOther2: その他

    AllowDebate --> [*]: 許可
    AllowRoom --> [*]: 許可
    Pass --> [*]: 許可
    BlockOther --> [*]: ブロック
    BlockOther2 --> [*]: ブロック
```

#### 3. ValidateDebateAccess

- ディベートへのアクセス権限を検証
- 参加者でないユーザーのアクセスをブロック
- ディベートの状態に応じた適切なリダイレクト

#### 4. AdminMiddleware

```php
public function handle($request, $next)
{
    $user = $request->user();

    // 管理者権限チェック
    if (!$user || !$user->isAdmin() || $user->isGuest()) {
        return redirect()->route('welcome');
    }

    return $next($request);
}
```

## セッション管理

### セッション構成

```mermaid
graph LR
    subgraph "セッションデータ"
        A[user_id]
        B[_token<br/>CSRFトークン]
        C[locale<br/>言語設定]
        D[flash<br/>フラッシュメッセージ]
        E[url.intended<br/>リダイレクト先]
    end

    subgraph "Redis Storage"
        R[(Redis)]
    end

    A --> R
    B --> R
    C --> R
    D --> R
    E --> R

    subgraph "Cookie"
        S[laravel_session]
        RT[remember_token]
    end

    R --> S
    R --> RT
```
