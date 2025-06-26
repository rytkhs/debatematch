# インフラ構成図

## 1. 概要

このドキュメントは、DebateMatchアプリケーションの本番環境におけるインフラストラクチャ全体の構成を視覚的に示したものです。AWSクラウドを中心に、各種マネージドサービス、外部サービス、およびセキュリティ対策がどのように連携しているかを明らかにします。

## 2. 構成図

```mermaid
graph TB
    subgraph "External Users"
        USERS["Users<br/>(Global)"]
    end

    subgraph "DNS & Domain"
        R53["AWS Route 53<br/>(DNS Management)"]
        DOMAIN_PROD["debate-match.com"]
    end

    subgraph "AWS Cloud (ap-northeast-1)"
        subgraph "VPC: debatematch-vpc"
            subgraph "Availability Zone: ap-northeast-1a"
                subgraph "Public Subnet 1a"
                    EC2_PROD["EC2 Instance<br/>(t4g.small - ARM)<br/>Production"]
                    subgraph "Docker Containers"
                        NGINX["Nginx<br/>Port 80/443"]
                        APP["Laravel App<br/>(PHP-FPM)"]
                        WORKER["Laravel Horizon<br/>(Queue Worker)"]
                        REDIS["Redis<br/>(Cache/Session/Queue)"]
                    end
                end
                subgraph "Private Subnet 1a"
                    RDS_A["RDS MySQL<br/>(Primary Instance)"]
                end
            end

            IGW["Internet Gateway<br/>(debatematch-igw)"]
            RT["Route Table<br/>(debatematch-public-route)"]
        end

        subgraph "Security Groups"
            SG_WEB["Web Server SG<br/>Inbound: SSH(22), HTTP(80), HTTPS(443)<br/>Outbound: All"]
            SG_DB["DB Server SG<br/>Inbound: MySQL(3306) from Web SG<br/>Outbound: All"]
        end

        subgraph "SSL/TLS"
            LETSENCRYPT["Let's Encrypt<br/>(SSL Certificates)"]
            CERTBOT["Certbot<br/>(Auto-renewal)"]
        end
    end

    subgraph "External Services"
        subgraph "Real-time & Communication"
            PUSHER["Pusher<br/>(WebSocket)"]
        end

        subgraph "AI Services"
            OPENROUTER["OpenRouter<br/>(GPT Models)"]
        end

        subgraph "Authentication"
            GOOGLE_OAUTH["Google OAuth 2.0"]
        end

        subgraph "Notifications"
            SNS["AWS SNS"]
        end

        subgraph "Monitoring"
            SENTRY["Sentry<br/>(Error Tracking)"]
            CLOUDWATCH["AWS CloudWatch<br/>(Logs)"]
        end
    end

    subgraph "Security Layer"
        FAIL2BAN["Fail2ban<br/>(Inside Docker)<br/>- nginx-sensitive<br/>- nginx-404"]
    end

    subgraph "Data Storage"
        RDS_BACKUP["RDS Automated Backup<br/>(Default: 7 days)"]
    end

    %% Network Flow
    USERS --> R53
    R53 --> DOMAIN_PROD
    DOMAIN_PROD --> IGW
    IGW --> RT
    RT --> EC2_PROD

    %% Docker Internal
    EC2_PROD --> NGINX
    NGINX --> APP
    APP --> WORKER
    APP --> REDIS
    WORKER --> REDIS

    %% Database Connection
    APP --> RDS_A
    WORKER --> RDS_A
    RDS_A --> RDS_BACKUP

    %% Security Groups
    SG_WEB -.->|Applied to| EC2_PROD
    SG_DB -.->|Applied to| RDS_A

    %% External Services
    APP --> PUSHER
    APP --> OPENROUTER
    APP --> GOOGLE_OAUTH
    APP --> SENTRY
    WORKER --> SNS
    WORKER --> SENTRY

    %% Logging
    NGINX --> CLOUDWATCH
    APP --> CLOUDWATCH
    WORKER --> CLOUDWATCH

    %% Security
    FAIL2BAN --> NGINX

    %% SSL
    LETSENCRYPT --> CERTBOT
    CERTBOT --> NGINX

    classDef aws fill:#FF9900,stroke:#232F3E,stroke-width:2px,color:#fff
    classDef container fill:#0db7ed,stroke:#0db7ed,stroke-width:2px,color:#fff
    classDef external fill:#4285F4,stroke:#4285F4,stroke-width:2px,color:#fff
    classDef security fill:#d32f2f,stroke:#d32f2f,stroke-width:2px,color:#fff
    classDef database fill:#336791,stroke:#336791,stroke-width:2px,color:#fff

    class EC2_PROD,RDS_A,IGW,RT,R53,SNS,CLOUDWATCH aws
    class NGINX,APP,WORKER,REDIS container
    class PUSHER,OPENROUTER,GOOGLE_OAUTH,SENTRY external
    class FAIL2BAN,SG_WEB,SG_DB,LETSENCRYPT security
    class RDS_BACKUP database
```

## 3. 主要コンポーネント解説

### 3.1. AWS Cloud (ap-northeast-1)

- **EC2 Instance (t4g.small)**: アプリケーションのメインサーバー。Nginx、Laravel App、Horizon（キューワーカ）、RedisがDockerコンテナとして稼働します。
- **RDS MySQL**: プライマリデータベース。可用性と運用負荷軽減のため、マネージドサービスであるRDSを利用します。データはプライベートサブネットに配置し、Webサーバーからのアクセスのみを許可しています。
- **Route 53**: ドメイン名（debate-match.com）の名前解決を行うDNSサービスです。
- **VPC (debatematch-vpc)**: 論理的に分離されたプライベートネットワーク空間。パブリックサブネットとプライベートサブネットに分割し、セキュリティを確保しています。
- **Security Groups**: インスタンスレベルのファイアウォール。Webサーバー用とDBサーバー用に分離し、最小権限の原則に基づいたアクセス制御を行います。
- **Let's Encrypt & Certbot**: 無料のSSL/TLS証明書と、その自動更新ツール。Nginxと連携し、常時HTTPS通信を保証します。

### 3.2. Dockerコンテナ

- **Nginx**: リバースプロキシとして機能し、ユーザーからのHTTP/HTTPSリクエストを受け付け、Laravelアプリケーション（PHP-FPM）に転送します。
- **Laravel App**: アプリケーション本体です。
- **Laravel Horizon**: Redisキューを監視・処理するバックグラウンドワーカです。AIによる評価など、時間のかかる処理を非同期で実行します。
- **Redis**: 高速なインメモリデータストア。セッション管理、キャッシュ、キューイングに利用します。

### 3.3. 外部サービス

- **Pusher**: WebSocketを利用したリアルタイム通信サービス。ディベート中のメッセージ交換などに使用します。
- **OpenRouter**: 複数の大規模言語モデル（LLM）を統一的なインターフェースで利用できるAPIゲートウェイ。AI評価機能で使用します。
- **Google OAuth**: ソーシャルログイン機能を提供します。
- **AWS SNS**: 信頼性の高い通知サービス。主にバッチ処理の結果通知などに利用します。
- **Sentry**: リアルタイムのエラー追跡・監視サービス。アプリケーションの例外を検知し、開発者に通知します。
- **AWS CloudWatch**: AWSリソースとアプリケーションのログを収集・監視します。

### 3.4. セキュリティ

- **Fail2ban**: ログファイルを監視し、不正なアクセス試行（ブルートフォース攻撃など）を検知したIPアドレスをブロックします。

## 4. ネットワークフロー

1.  ユーザーは `debate-match.com` にアクセスします。
2.  Route 53がドメイン名を解決し、EC2インスタンスのElastic IPアドレスを返します。
3.  リクエストはインターネットゲートウェイを経由し、パブリックサブネットのEC2インスタンスに到達します。
4.  EC2インスタンスのNginxコンテナがリクエストを受け、Laravelアプリケーションに転送します。
5.  アプリケーションは必要に応じて、プライベートサブネットのRDSや外部サービス（Pusher, OpenRouter等）と通信します。
6.  レスポンスは逆の経路を辿ってユーザーに返されます。

## 5. 設計上の考慮事項

- **セキュリティ**: DBをプライベートサブネットに配置し、セキュリティグループで厳格なアクセス制御を行うことで、データ保護を強化しています。
- **運用負荷の軽減**: RDSやPusherなどのマネージドサービスを積極的に利用し、インフラ管理のオーバーヘッドを削減しています。
- **スケーラビリティ**: 現状はシングルインスタンス構成ですが、将来的なアクセス増に対応するため、ロードバランサーとEC2オートスケーリンググループを導入できるよう設計されています。RDSもリードレプリカの追加やインスタンスタイプの変更が容易です。
