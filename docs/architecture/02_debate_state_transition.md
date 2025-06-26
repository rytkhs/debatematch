# ディベート状態遷移図

## 概要

DebateMatchでは、ルームとディベートの状態を厳密に管理し、適切な状態遷移のみを許可することで、システムの整合性を保っています。本ドキュメントでは、これらの状態遷移と各状態での可能なアクションを定義します。

## ルーム状態遷移図

```mermaid
stateDiagram-v2
    [*] --> waiting: ルーム作成

    waiting --> ready: 両サイドに参加者が揃う
    waiting --> deleted: ルーム削除
    waiting --> terminated: 異常終了

    ready --> waiting: 参加者が退出
    ready --> debating: ディベート開始
    ready --> deleted: ルーム削除
    ready --> terminated: 異常終了

    debating --> finished: ディベート正常終了
    debating --> terminated: 早期終了/強制終了
    debating --> deleted: ルーム削除

    finished --> [*]
    terminated --> [*]
    deleted --> [*]

    note right of waiting
        - ルーム作成者のみ存在
        - 参加者待機中
        - ルーム情報編集可能
    end note

    note right of ready
        - 両サイド参加者確定
        - ディベート開始可能
        - サイド変更可能
    end note

    note right of debating
        - ディベート進行中
        - メッセージ送信可能
        - 早期終了提案可能
    end note

    note right of finished
        - ディベート完了
        - AI評価待機/完了
        - 結果閲覧可能
    end note

    note right of terminated
        - 早期終了合意
        - 接続異常による終了
        - 結果閲覧不可
    end note
```

## ディベート進行状態遷移図

```mermaid
stateDiagram-v2
    [*] --> turn0: ディベート開始

    turn0 --> turn1: 時間切れ/手動進行
    turn1 --> turn2: 時間切れ/手動進行
    turn2 --> turn3: 時間切れ/手動進行
    turn3 --> turn4: 時間切れ/手動進行
    turn4 --> turn5: 時間切れ/手動進行
    turn5 --> turn6: 時間切れ/手動進行
    turn6 --> turn7: 時間切れ/手動進行
    turn7 --> Finished: 最終ターン終了

    turn0 --> EarlyTermination: 早期終了提案
    turn1 --> EarlyTermination: 早期終了提案
    turn2 --> EarlyTermination: 早期終了提案
    turn3 --> EarlyTermination: 早期終了提案
    turn4 --> EarlyTermination: 早期終了提案
    turn5 --> EarlyTermination: 早期終了提案
    turn6 --> EarlyTermination: 早期終了提案
    turn7 --> EarlyTermination: 早期終了提案

    EarlyTermination --> terminated: 相手が承認
    EarlyTermination --> turn0: 相手が拒否(元のターンに戻る)
    EarlyTermination --> turn1: 相手が拒否
    EarlyTermination --> turn2: 相手が拒否
    EarlyTermination --> turn3: 相手が拒否
    EarlyTermination --> turn4: 相手が拒否
    EarlyTermination --> turn5: 相手が拒否
    EarlyTermination --> turn6: 相手が拒否
    EarlyTermination --> turn7: 相手が拒否

    Finished --> [*]: AI評価完了
    terminated --> [*]

    note right of turn0
        立論（肯定側）
        時間: 形式により可変
    end note

    note right of turn1
        立論（否定側）
        時間: 形式により可変
    end note

    note right of EarlyTermination
        - 提案者待機状態
        - 相手の応答待ち
        - タイムアウト機能あり
    end note
```

## 早期終了フロー

```mermaid
sequenceDiagram
    participant User1 as 提案者
    participant System as システム
    participant Redis as Redis
    participant User2 as 相手
    participant Queue as キューシステム

    User1->>System: 早期終了を提案
    System->>Redis: 提案状態を保存
    System->>User2: 提案通知を送信
    System->>Queue: タイムアウトジョブ登録（60秒）

    alt 相手が承認
        User2->>System: 承認
        System->>Redis: 状態をクリア
        System->>Queue: タイムアウトジョブキャンセル
        System->>System: ディベート終了処理
        System->>User1: 終了通知
        System->>User2: 終了通知
    else 相手が拒否
        User2->>System: 拒否
        System->>Redis: 状態をクリア
        System->>Queue: タイムアウトジョブキャンセル
        System->>User1: 拒否通知
        System->>System: ディベート継続
    else タイムアウト
        Queue->>System: タイムアウト処理実行
        System->>Redis: 状態をクリア
        System->>User1: タイムアウト通知
        System->>System: ディベート継続
    end
```

## 状態定義

### ルーム状態（Room Status）

| 状態         | 値           | 説明                                             |
| ------------ | ------------ | ------------------------------------------------ |
| 待機中       | `waiting`    | ルームが作成され、参加者を待っている状態         |
| 準備完了     | `ready`      | 両サイドに参加者が揃い、ディベート開始可能な状態 |
| ディベート中 | `debating`   | ディベートが進行中の状態                         |
| 終了         | `finished`   | ディベートが正常に終了した状態                   |
| 削除済み     | `deleted`    | ルームが削除された状態                           |
| 強制終了     | `terminated` | 早期終了や異常により強制終了した状態             |

### ディベートターン

| ターン | 説明               | 話者   | 時間           |
| ------ | ------------------ | ------ | -------------- |
| 0      | 立論（肯定側）     | 肯定側 | 形式により可変 |
| 1      | 立論（否定側）     | 否定側 | 形式により可変 |
| 2      | 準備時間           | なし   | 形式により可変 |
| 3      | 質疑（否定→肯定）  | 両方   | 形式により可変 |
| 4      | 第一反駁（否定側） | 否定側 | 形式により可変 |
| 5      | 第一反駁（肯定側） | 肯定側 | 形式により可変 |
| 6      | 最終弁論（否定側） | 否定側 | 形式により可変 |
| 7      | 最終弁論（肯定側） | 肯定側 | 形式により可変 |

### 早期終了状態

| 状態        | 説明                       |
| ----------- | -------------------------- |
| `none`      | 通常状態                   |
| `requested` | 早期終了が提案された状態   |
| `agreed`    | 早期終了が承認された状態   |
| `rejected`  | 早期終了が拒否された状態   |
| `timeout`   | 提案がタイムアウトした状態 |

## 実装詳細

### 状態遷移の検証

```php
// Room.php の updateStatus メソッド
$validTransitions = [
    self::STATUS_WAITING => [self::STATUS_READY, self::STATUS_DELETED, self::STATUS_TERMINATED],
    self::STATUS_READY => [self::STATUS_DEBATING, self::STATUS_WAITING, self::STATUS_DELETED, self::STATUS_TERMINATED],
    self::STATUS_DEBATING => [self::STATUS_FINISHED, self::STATUS_DELETED, self::STATUS_TERMINATED],
    self::STATUS_FINISHED => [self::STATUS_FINISHED],
    self::STATUS_DELETED => [self::STATUS_DELETED],
    self::STATUS_TERMINATED => [self::STATUS_TERMINATED],
];
```

### ターン進行の管理

1. **自動進行**: `turn_end_time`に基づいてキューシステムが自動的に次のターンへ
2. **手動進行**: 現在の話者が終了ボタンで進行
3. **準備時間**: 発言不可、時間経過または次の話者が終了ボタンで進行
4. **質疑応答**: 両者が発言可能、時間制限あり
