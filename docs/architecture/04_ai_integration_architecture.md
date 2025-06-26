# AIインテグレーションアーキテクチャ

## 概要

DebateMatchでは、OpenRouter APIを通じて複数のAIモデルと統合し、AIディベート機能とディベート評価機能を提供しています。本ドキュメントでは、AI機能の統合アーキテクチャ、処理フロー、およびプロンプトエンジニアリングの戦略を解説します。

## AI統合全体構成

```mermaid
graph TB
    subgraph "DebateMatch Application"
        US[User Service]
        DS[Debate Service]
        AIS[AI Service]
        AES[AI Evaluation Service]
        AIDCS[AI Debate Creation Service]
        QJ[Queue Jobs]
    end

    subgraph "OpenRouter API Gateway"
        OR[OpenRouter<br/>API Endpoint]
        RM[Route Manager]
    end

    subgraph "AI Models"
        GPT[ChatGPT]
        Gemini[Gemini]
        Claude[Claude]
        Other[Other Models]
    end

    subgraph "Prompt Management"
        PT[Prompt Templates]
        PC[Prompt Composer]
        PV[Prompt Validator]
    end

    US --> AIDCS
    DS --> AIS
    DS --> AES
    AIS --> PC
    AES --> PC
    PC --> PT
    PC --> PV
    PV --> OR

    OR --> RM
    RM --> GPT
    RM --> Gemini
    RM --> Claude
    RM --> Other

    QJ --> AIS
    QJ --> AES

    style OR fill:#f9f,stroke:#333,stroke-width:4px
    style Gemini fill:#9f9,stroke:#333,stroke-width:2px
```

## 主要コンポーネント

### 1. AIService - AI応答生成サービス

```mermaid
sequenceDiagram
    participant User as ユーザー
    participant Debate as ディベート
    participant AIService as AIService
    participant Prompt as プロンプト構築
    participant OpenRouter as OpenRouter API
    participant Error as エラーハンドリング

    User->>Debate: メッセージ送信
    Debate->>AIService: generateResponse()

    AIService->>AIService: API設定確認
    AIService->>Prompt: buildPrompt()

    Prompt->>Prompt: ディベート履歴取得
    Prompt->>Prompt: フォーマット判定
    Prompt->>Prompt: 文字数制限計算
    Prompt->>Prompt: テンプレート選択
    Prompt->>AIService: プロンプト返却

    AIService->>OpenRouter: API呼び出し
    Note over OpenRouter: タイムアウト: 240秒

    alt 成功
        OpenRouter-->>AIService: AI応答
        AIService->>AIService: 応答検証
        AIService-->>Debate: 応答テキスト
    else 失敗
        OpenRouter-->>AIService: エラー
        AIService->>Error: エラーログ
        Error->>AIService: フォールバック応答
        AIService-->>Debate: 代替メッセージ
    end
```

### 2. AIEvaluationService - ディベート評価サービス

```mermaid
flowchart TB
    Start([ディベート終了]) --> Check{評価可能？}

    Check -->|Yes| Prepare[評価準備]
    Check -->|No| Skip[評価スキップ]

    Prepare --> BuildPrompt[プロンプト構築]
    BuildPrompt --> Elements{要素収集}

    Elements --> Topic[トピック]
    Elements --> Messages[メッセージ履歴]
    Elements --> Format[ディベート形式]
    Elements --> Language[言語設定]

    Topic --> Compose[プロンプト合成]
    Messages --> Compose
    Format --> Compose
    Language --> Compose

    Compose --> CallAPI[OpenRouter API呼び出し]

    CallAPI --> Parse{レスポンス解析}
    Parse -->|成功| Extract[評価データ抽出]
    Parse -->|失敗| Retry{リトライ？}

    Extract --> Validate[データ検証]
    Validate --> Save[評価保存]

    Retry -->|Yes| CallAPI
    Retry -->|No| ErrorHandle[エラー処理]

    Save --> Notify[ユーザー通知]
    ErrorHandle --> Notify
    Skip --> End([完了])
    Notify --> End
```

### 文字数/単語数制限の計算

```mermaid
graph LR
    A[時間制限<br/>分単位] --> B{言語判定}

    B -->|日本語| C[320文字/分]
    B -->|英語| D[160単語/分]

    C --> E[総文字数計算]
    D --> F[総単語数計算]

    E --> G{フォーマット}
    F --> G

    G -->|通常| H[制限値そのまま]
    G -->|フリー| I[制限値を半分に]

    H --> J[制限値設定]
    I --> J
```

## エラーハンドリングとフォールバック

### エラー階層

```mermaid
stateDiagram-v2
    [*] --> APICall: API呼び出し

    APICall --> Success: 成功
    APICall --> NetworkError: ネットワークエラー
    APICall --> AuthError: 認証エラー
    APICall --> RateLimit: レート制限
    APICall --> Timeout: タイムアウト

    Success --> ValidateResponse: レスポンス検証
    ValidateResponse --> Valid: 有効
    ValidateResponse --> Invalid: 無効

    Valid --> [*]: 正常終了

    Invalid --> FallbackResponse: フォールバック
    NetworkError --> RetryLogic: リトライ判定
    RateLimit --> BackoffWait: バックオフ待機
    Timeout --> FallbackResponse
    AuthError --> LogError: エラーログ

    RetryLogic --> APICall: リトライ
    RetryLogic --> FallbackResponse: リトライ上限
    BackoffWait --> APICall

    FallbackResponse --> [*]: 代替応答
    LogError --> [*]: 処理中断
```

## AIディベート作成フロー

```mermaid
sequenceDiagram
    participant User as ユーザー
    participant AIDCS as AI Debate Creation Service
    participant Room as Room Service
    participant Debate as Debate Service
    participant AI as AI User
    participant Queue as Queue System

    User->>AIDCS: AIディベート作成リクエスト
    AIDCS->>AIDCS: フォーマット設定処理
    AIDCS->>Room: ルーム作成
    AIDCS->>AI: AIユーザー取得

    AIDCS->>Room: ユーザー参加登録
    Note over Room: ユーザー: 選択したサイド<br/>AI: 反対サイド

    AIDCS->>Debate: ディベート作成
    AIDCS->>Debate: ディベート開始

    Debate->>Room: ステータス更新(debating)
    Debate->>Queue: AdvanceDebateTurnJob登録

    Queue-->>User: ディベート画面へリダイレクト
```
