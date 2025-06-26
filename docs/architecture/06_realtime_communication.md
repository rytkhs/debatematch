# リアルタイム通信設計

## 概要

DebateMatchは、Pusher WebSocketsサービスとLaravel Echoを組み合わせたリアルタイム通信システムを実装しています。ディベートの進行状況、メッセージの送受信、ユーザーの接続状態など、すべてのリアルタイムイベントがWebSocketを通じて配信されます。

## リアルタイム通信アーキテクチャ

```mermaid
graph TB
    subgraph "Client Side"
        Browser[Webブラウザ]
        Echo[Laravel Echo]
        JS[JavaScript Event Handlers]
    end

    subgraph "Server Side"
        Laravel[Laravel Application]
        Broadcasting[Broadcasting Service]
        Events[Event Classes]
        Queue[Queue System]
    end

    subgraph "Pusher Service"
        PusherServer[Pusher WebSocket Server]
        Channels[Channel Management]
        Auth[Channel Authorization]
    end

    subgraph "Communication Flow"
        WS[WebSocket Connection]
        HTTP[HTTP Requests]
    end

    Browser --> Echo
    Echo --> JS
    Echo <--> WS
    WS <--> PusherServer

    Laravel --> Events
    Events --> Broadcasting
    Broadcasting --> Queue
    Queue --> HTTP
    HTTP --> PusherServer

    PusherServer --> Channels
    Channels --> Auth
    Auth <--> Laravel

    style PusherServer fill:#f9f,stroke:#333,stroke-width:4px
    style WS stroke:#f66,stroke-width:2px,stroke-dasharray: 5 5
```

## チャンネル設計

### チャンネル構造

```mermaid
graph TD
    subgraph "Presence Channels"
        PR1["room.{roomId}"]
        PR2["debate.{debateId}"]
    end

    PR1 --> E2[UserJoinedRoom]
    PR1 --> E3[UserLeftRoom]
    PR1 --> E4[DebateStarted]

    PR2 --> E5[TurnAdvanced]
    PR2 --> E6[MessageSent]
    PR2 --> E7[DebateFinished]
    PR2 --> E8[DebateTerminated]
    PR2 --> E9[EarlyTermination Events]
```

### チャンネル認証フロー

```mermaid
sequenceDiagram
    participant Client as クライアント
    participant Echo as Laravel Echo
    participant Pusher as Pusher Service
    participant Laravel as Laravel App
    participant Auth as 認証ミドルウェア

    Client->>Echo: チャンネル購読要求
    Echo->>Pusher: 接続リクエスト

    alt Private/Presence Channel
        Pusher->>Laravel: 認証リクエスト<br/>/broadcasting/auth
        Laravel->>Auth: ユーザー認証確認

        alt 認証成功
            Auth->>Laravel: ユーザー情報
            Laravel->>Laravel: チャンネル権限確認
            Laravel->>Pusher: 認証トークン
            Pusher->>Echo: 購読許可
            Echo->>Client: 接続成功
        else 認証失敗
            Auth->>Laravel: 認証エラー
            Laravel->>Pusher: 403 Forbidden
            Pusher->>Echo: 購読拒否
            Echo->>Client: エラー通知
        end
    else Public Channel
        Pusher->>Echo: 購読許可
        Echo->>Client: 接続成功
    end
```

## イベントブロードキャスト設計

### イベントカテゴリと配信チャンネル
| イベントカテゴリ | イベント名                | チャンネル                | 説明           |
| ---------------- | ------------------------- | ------------------------- | -------------- |
| ルーム管理       | UserJoinedRoom            | room.{roomId}            | ユーザー参加   |
|                  | UserLeftRoom              | room.{roomId}            | ユーザー退出   |
|                  | CreatorLeftRoom           | room.{roomId}            | 作成者退出     |
| ディベート進行   | DebateStarted             | room.{roomId}            | ディベート開始 |
|                  | TurnAdvanced              | debate.{debateId}        | ターン進行     |
|                  | MessageSent               | debate.{debateId}        | メッセージ送信 |
|                  | DebateFinished            | debate.{debateId}        | ディベート終了 |
|                  | DebateTerminated          | debate.{debateId}        | 強制終了       |
| 早期終了         | EarlyTerminationRequested  | debate.{debateId}        | 早期終了提案   |
|                  | EarlyTerminationAgreed     | debate.{debateId}        | 早期終了合意   |
|                  | EarlyTerminationRejected   | debate.{debateId}        | 早期終了拒否   |
|                  | EarlyTerminationExpired    | debate.{debateId}        | 提案期限切れ   |
| 評価             | DebateEvaluated           | debate.{debateId}        | AI評価完了     |
