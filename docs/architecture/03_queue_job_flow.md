# キューシステム・ジョブフロー図

## 概要

DebateMatchは、Laravel Horizonを使用した堅牢な非同期処理システムを実装しています。リアルタイムディベートの進行管理、AI応答生成、評価処理など、時間のかかる処理やタイミングクリティカルな処理をキューシステムで管理しています。

## キューシステム全体構成

```mermaid
graph TB
    subgraph "Event Triggers"
        E1[ディベート開始]
        E2[ターン終了時刻到達]
        E3[メッセージ送信]
        E4[ディベート終了]
        E5[ユーザー切断]
        E6[早期終了提案]
    end

    subgraph "Queue System"
        Redis[(Redis Queue)]
        Horizon[Laravel Horizon<br/>Dashboard & Monitor]
        Workers[Queue Workers<br/>複数プロセス]
    end

    subgraph "Jobs"
        J1[AdvanceDebateTurnJob]
        J2[GenerateAIResponseJob]
        J3[EvaluateDebateJob]
        J4[HandleUserDisconnection]
        J5[EarlyTerminationTimeoutJob]
    end

    subgraph "Processing Results"
        R1[ターン進行]
        R2[AI応答生成]
        R3[ディベート評価]
        R4[接続状態更新]
        R5[提案タイムアウト]
    end

    E1 --> J1
    E2 --> J1
    E3 --> J2
    E4 --> J3
    E5 --> J4
    E6 --> J5

    J1 --> Redis
    J2 --> Redis
    J3 --> Redis
    J4 --> Redis
    J5 --> Redis

    Redis --> Workers
    Workers --> Horizon

    Workers --> R1
    Workers --> R2
    Workers --> R3
    Workers --> R4
    Workers --> R5
```

## 主要ジョブの詳細フロー

### 1. AdvanceDebateTurnJob - ターン進行ジョブ

```mermaid
sequenceDiagram
    participant Timer as タイマー
    participant Job as AdvanceDebateTurnJob
    participant DB as Database
    participant Service as DebateService
    participant Broadcast as Broadcasting
    participant NextJob as 次のジョブ

    Timer->>Job: ターン終了時刻到達
    Job->>DB: ディベート状態確認

    alt ディベート進行中
        Job->>DB: 現在のターン確認
        Job->>Service: 次のターン番号取得

        alt 次のターンが存在
            Service->>DB: ターン情報更新
            Service->>Broadcast: TurnAdvancedイベント
            Service->>NextJob: 次のAdvanceDebateTurnJob登録
            Note over NextJob: 遅延実行設定
        else 最終ターン完了
            Service->>DB: ディベート終了処理
            Service->>Broadcast: DebateFinishedイベント
            Service->>NextJob: EvaluateDebateJob登録
        end
    else ディベート終了済み
        Job->>Job: 処理スキップ
    end
```

### 2. GenerateAIResponseJob - AI応答生成ジョブ

```mermaid
sequenceDiagram
    participant User as ユーザー
    participant Job as GenerateAIResponseJob
    participant AI as AIService
    participant OpenRouter as OpenRouter API
    participant DB as Database
    participant Broadcast as Broadcasting

    User->>Job: AIディベートでメッセージ送信
    Job->>DB: ディベート状態確認
    Job->>AI: 応答生成リクエスト
    AI->>DB: 過去のメッセージ取得
    AI->>AI: プロンプト生成
    AI->>OpenRouter: API呼び出し

    alt API成功
        OpenRouter-->>AI: AI応答
        AI->>DB: メッセージ保存
        AI->>Broadcast: MessageSentイベント

        alt AIのターン終了
            AI->>Job: AdvanceDebateTurnJob登録
        end
    else API失敗
        AI->>AI: リトライロジック
        alt リトライ成功
            AI->>DB: メッセージ保存
            AI->>Broadcast: MessageSentイベント
        else リトライ失敗
            AI->>DB: エラーメッセージ保存
            AI->>Broadcast: エラー通知
        end
    end
```

### 3. EvaluateDebateJob - ディベート評価ジョブ

```mermaid
flowchart TB
    Start([ディベート終了]) --> Check{評価可能？}

    Check -->|Yes| Fetch[メッセージ取得]
    Check -->|No| SaveEmpty[空の評価保存]

    Fetch --> Analyze[内容分析]
    Analyze --> CallAI[OpenRouter API呼び出し]

    CallAI --> Success{成功？}
    Success -->|Yes| Parse[結果パース]
    Success -->|No| Retry{リトライ可能？}

    Retry -->|Yes| CallAI
    Retry -->|No| SaveError[エラー評価保存]

    Parse --> SaveEval[評価結果保存]
    SaveEval --> Broadcast[結果通知]
    SaveEmpty --> Broadcast
    SaveError --> Broadcast

    Broadcast --> End([完了])
```

### 4. HandleUserDisconnection - ユーザー切断処理ジョブ

```mermaid
stateDiagram-v2
    [*] --> CheckConnection: ユーザー切断検知

    CheckConnection --> InDebate: ディベート中
    CheckConnection --> InRoom: ルーム待機中
    CheckConnection --> NoAction: その他

    InDebate --> CheckReconnect: 再接続待機
    CheckReconnect --> Reconnected: 再接続成功
    CheckReconnect --> Timeout: タイムアウト

    Reconnected --> UpdateStatus(Debate): 接続状態更新
    Timeout --> TerminateDebate: ディベート強制終了

    InRoom --> CheckReconnectRoom: 再接続待機（ルーム）
    CheckReconnectRoom --> ReconnectedRoom: 再接続成功（ルーム）
    CheckReconnectRoom --> TimeoutRoom: タイムアウト（ルーム）

    ReconnectedRoom --> UpdateStatus(Room): 接続状態更新
    TimeoutRoom --> RemoveFromRoom: ルームから退出

    TerminateDebate --> NotifyDebateUsers: ディベート参加者へ通知
    RemoveFromRoom --> NotifyRoomUsers: ルーム参加者へ通知
    UpdateStatus(Room) --> [*]
    UpdateStatus(Debate) --> [*]
    NotifyDebateUsers --> [*]
    NotifyRoomUsers --> [*]
    NoAction --> [*]
```

### 5. EarlyTerminationTimeoutJob - 早期終了タイムアウトジョブ

```mermaid
flowchart LR
    A[早期終了提案] --> B[Job登録<br/>60秒遅延]
    B --> C{タイムアウト時}
    C --> D[Redis状態確認]
    D --> E{まだ提案中？}
    E -->|Yes| F[提案クリア]
    E -->|No| G[処理終了]
    F --> H[タイムアウト通知]
    H --> I[ディベート継続]
```
