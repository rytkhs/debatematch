<?php

return [
    // 汎用
    'app_name' => 'DebateMatch',
    'confirm' => '確認',
    'cancel' => 'キャンセル',
    'close' => '閉じる',
    'submit' => '送信',
    'loading' => '読み込み中...',
    'error' => 'エラーが発生しました。',
    'success' => '成功しました。',
    'yes' => 'はい',
    'no' => 'いいえ',
    'search' => '検索',
    'save' => '保存',
    'delete' => '削除',
    'edit' => '編集',
    'create' => '作成',
    'back' => '戻る',
    'logout' => 'ログアウト',
    'login' => 'ログイン',
    'register' => '新規登録',
    'optional' => '任意',

    // ナビゲーション
    'home' => 'ホーム',
    'debate_rooms' => 'ディベートルーム',
    'create_room' => 'ルーム作成',
    'my_profile' => 'マイプロフィール',
    'debate_history' => 'ディベート履歴',
    'language_en_label' => 'English',
    'language_ja_label' => '日本語',
    'current_language_indicator' => '(現在)',

    // ルーム関連 (共通)
    'room_name' => 'ルーム名',
    'waiting_rooms' => '待機中のルーム',
    'no_waiting_rooms' => '待機中のルームはありません。',
    'join_room' => 'ルームに参加',
    'room_created_successfully' => 'ルームを作成しました。',
    'failed_to_create_room' => 'ルームの作成に失敗しました。',
    'confirm_join_room' => ':roomName に参加しますか？',
    'joining_room' => 'ルームに参加中...',
    'failed_to_join_room' => 'ルームへの参加に失敗しました。',
    'user_joined' => ':name さんが参加しました。',
    'start_debate' => 'ディベート開始',

    // ディベート関連
    'debate' => 'ディベート',
    'topic' => '論題',
    'language' => '言語設定', // ルーム作成時の言語設定ラベル
    'language_used' => '使用言語', // 詳細表示用
    'remarks' => '備考',
    'host' => 'ホスト',
    'participants' => '参加者',
    'affirmative_side' => '肯定側',
    'negative_side' => '否定側',
    'waiting_for_opponent' => '対戦相手の参加を待っています...',
    'debate_start_notification' => 'ディベートを開始します！',
    'your_turn' => 'あなたのターンです',
    'opponents_turn' => '相手のターンです',
    'time_remaining' => '残り時間: :time',
    'argument' => '議論',
    'send' => '送信',
    'debate_finished' => 'ディベート終了',
    'result' => '結果',
    'winner' => '勝者',
    'feedback' => 'フィードバック',
    'affirmative' => '肯定', // 短縮形
    'negative' => '否定', // 短縮形
    'side' => 'サイド', // 一般的な「サイド」

    // 認証関連
    'email' => 'メールアドレス',
    'password' => 'パスワード',
    'remember_me' => 'ログイン状態を維持する',
    'forgot_password' => 'パスワードをお忘れですか？',
    'confirm_password' => 'パスワード確認',
    'name' => '名前',
    // ルーム作成 (create.blade.php)
    'create_new_room' => '新しいルームを作成', // ページタイトル
    'basic_information' => '基本情報',
    'placeholder_topic' => '論題を入力',
    'topic_guideline' => 'ディベートのテーマとなる明確な命題を入力してください。',
    'placeholder_room_name' => 'ルーム名を入力',
    'placeholder_remarks' => '特別なルールや注意事項があれば入力してください',
    'debate_settings' => 'ディベート設定',
    'your_side' => 'あなたのサイド',
    'agree_with_topic' => '論題に賛成',
    'disagree_with_topic' => '論題に反対',
    'format' => 'フォーマット',
    'format_suffix' => 'フォーマット', // 例: 'NAFA 形式'
    'select_format' => 'フォーマットを選択',
    'custom_format' => 'カスタムフォーマット', // フォーマット選択肢用
    'format_selection_guide' => 'ディベートの進行形式を選択してください。「カスタムフォーマット」を選ぶと自由に設定できます。',
    'format_preview' => 'フォーマットプレビュー',
    'configure_custom_format' => 'カスタムフォーマットの設定',
    'custom_format_guide' => '各パートの「サイド」「パート名」「時間」を設定してください。「準備時間」や「質疑応答」を含めることも可能です。最低1パートは必要です。',
    'parts' => 'パート構成', // カスタム設定内ラベル
    'part' => 'パート', // 例: 'パート 1'
    'add_part' => 'パートを追加',
    'remove_part' => 'パートを削除', // 削除ボタン（未使用だが念のため）
    'part_name' => 'パート名',
    'placeholder_part_name' => '例: 第一立論, 第二反駁',
    'speaker' => '話者', // カスタム設定内ラベル (プレビューでは 'side' を使用)
    'duration_minutes' => '時間(分)',
    'question_time' => '質疑', // チェックボックスラベル
    'prep_time' => '準備時間', // チェックボックスラベル (英語キー `prep_time` と対応)

    // プロフィール
    'update_profile' => 'プロフィール更新',
    'profile_updated' => 'プロフィールを更新しました。',
    // データリストの提案 (suggestions)
    'suggestion_constructive' => '立論',
    'suggestion_first_constructive' => '第一立論',
    'suggestion_second_constructive' => '第二立論',
    'suggestion_rebuttal' => '反駁',
    'suggestion_first_rebuttal' => '第一反駁',
    'suggestion_second_rebuttal' => '第二反駁',
    'suggestion_questioning' => '質疑', // question_time と同じ翻訳
    'suggestion_prep_time' => '準備時間', // prep_time と同じ翻訳

    // 言語切り替え
    'language' => '言語',
    'japanese' => '日本語',
    'english' => '英語',
    // バリデーションなど (必要に応じて `validation.php` から移動または追加)
    'validation.required' => ':attribute は必須です。',
    'validation.string' => ':attribute は文字列である必要があります。',
    'validation.max.string' => ':attribute は :max 文字以内で入力してください。',


    // Welcome Page
    'start_online_debate' => 'オンラインでディベートを始めよう',
    'welcome_description' => 'DebateMatch(ディベートマッチ)は、誰でも簡単に参加できるオンラインディベートプラットフォームです。意見を交わし、新しい視点を見つけましょう。',
    'search_room' => 'ルームを探す',
    'features_title' => 'DebateMatchの特徴',
    'realtime_chat' => 'リアルタイムチャット',
    'realtime_chat_description' => '場所や時間に縛られることなく、いつでもどこでもディベーターとマッチング。すぐにディベートを始めることができます。',
    'time_management' => 'タイムマネジメント',
    'time_management_description' => '自動化されたタイマーと進行管理により、効率的な討論の場を提供します。',
    'ai_feedback' => 'AIフィードバック', // 'feedback' とは区別
    'ai_feedback_description' => 'ディベート終了後、AIジャッジが議論を分析。勝敗判定と詳細な講評を提供します。具体的な改善点もフィードバックします。',
    'how_to_use' => '使い方',
    'step1_title' => 'ルームを選択 / 作成',
    'step1_description' => 'ルームを探して参加するか、新しいルームを作成します',
    'step2_title' => 'ディベート開始', // 'start_debate' とは区別
    'step2_description' => 'ディベーターが揃ったら、システムの進行に従いディベートを行います',
    'step3_title' => 'AI講評',
    'step3_description' => 'AIジャッジからディベートの講評とフィードバックを受け取ります',
    'faq_title' => 'よくある質問',
    'faq1_question' => 'ディベート初心者でも参加できますか？',
    'faq1_answer' => 'はい、大歓迎です！ DebateMatchは、ディベート経験がない方でも気軽に参加できます。AIによるフィードバック機能も、スキルアップの助けになります。まずは<a href=":url" class="text-primary hover:underline">使い方ガイド</a>をご覧にな
    り、簡単なルームから参加してみることをお勧めします。',
    'usage_guide' => '使い方ガイド',
    'faq2_question' => '利用料金はかかりますか？',
    'faq2_answer' => '現在、DebateMatchのすべての機能を無料でご利用いただけます。',
    'faq3_question' => 'ディベートに参加するために必要なものは何ですか？',
    'faq3_answer' => 'インターネットに接続されたパソコンやタブレット、スマートフォンと、最新版のウェブブラウザ（Google Chrome, Firefox, Safari, Edgeなど）があれば参加できます。特別なソフトウェアのインストールは不要です。安定したディベートのためには、Wi-Fiなどの安定したネットワーク環境を推奨します。',
    'faq4_question' => 'スマートフォンやタブレットでも利用できますか？',
    'faq4_answer' => 'はい、スマートフォンやタブレットでもご利用いただけます。ただし、ディベート中は多くの情報を表示するため、より快適にご利用いただくためには、画面の大きいパソコンやタブレットでの利用を推奨します。',
    'faq5_question' => 'AIフィードバックとは具体的にどのようなものですか？',
    'faq5_answer' => 'ディベート終了後、AIが議論全体の内容を分析します。公平な視点から勝敗を判定し、その理由を説明します。さらに、肯定側・否定側それぞれに対し、議論の良かった点や改善すべき点を具体的に指摘するフィードバックを提供します。これにより、客観的な視点から自身のディベートを振り返ることができます。',

    // Guide Page
    'guide_title' => 'DebateMatch 使い方ガイド',
    'guide_description' => 'DebateMatchへようこそ！このガイドでは、サービスの基本的な使い方から応用的な機能までをわかりやすく解説します。',
    'main_features' => '主な機能',
    'room_management' => 'ルーム管理',
    'room_management_description' => 'ディベートルームを自由に作成・検索し、他のユーザーとマッチングできます。',
    'realtime_chat_feature_description' => 'テキストベースのチャットで、スムーズなディベート進行を実現します。', //
    'auto_progress_timer' => '自動進行 & タイマー',
    'auto_progress_timer_description' => '設定されたフォーマットに基づき、タイマーと進行が自動で管理されます。',
    'ai_critique' => 'AIによる講評', // ai_feedback とは区別
    'ai_critique_description' => 'ディベート終了後、AIが議論を分析し、勝敗判定と詳細なフィードバックを提供します。',
    'debate_flow' => 'ディベートの流れ',
    'step1_preparation' => 'ステップ1：準備',
    'prep_step1_title' => '1. ユーザー登録・ログイン',
    'prep_step1_desc1' => '初めての方は<a href=":register_url" class="text-primary hover:underline">新規登録</a>からアカウントを作成して
    ください。登録済みの方は<a href=":login_url" class="text-primary hover:underline">ログイン</a>してください。',
    'prep_step1_desc2' => 'メール認証が必要な場合があります。',
    'prep_step2_title' => '2. ルームを探す or 作成する',
    'prep_step2_desc1' => '<a href=":index_url" class="text-primary hover:underline">ルームを探す</a>ページで参加したいルームを見つける
    か、<a href=":create_url" class="text-primary hover:underline">ルーム作成</a>ページで新しいルームを作成します。',
    'prep_step2_desc2' => 'ルーム作成時には、論題、ルーム名、備考、使用言語、ディベートフォーマットを選択します。カスタムフォーマットも設定可能です。',
    'step2_matching' => 'ステップ2：マッチング',
    'match_step1_title' => '3. ルームに参加する',
    'match_step1_desc1' => '参加したいルームを見つけたら、ルーム詳細ページで「肯定側」または「否定側」のどちらで参加するかを選択し、参加ボタンを押します。',
    'match_step1_desc2' => '既に他のユーザーが参加しているサイドには参加できません。',
    'match_step2_title' => '4. 待機と開始',
    'match_step2_desc1' => 'ルームに参加すると待機画面に移ります。肯定側・否定側の両方のプレイヤーが揃うと、ルーム作成者（ホスト）がディベートを開始できます。',
    'match_step2_desc2' => '準備ができたら、ホストは「ディベート開始」ボタンを押してください。',
    'step3_debate' => 'ステップ3：ディベート',
    'debate_step1_title' => '5. ディベート画面の見方と操作',
    'debate_timeline' => 'タイムライン',
    'debate_timeline_desc' => '現在のパート、残り時間、全体の進行状況が表示されます。',
    'debate_chat_area' => 'チャットエリア',
    'debate_chat_area_desc' => 'ディベートの発言がリアルタイムで表示されます。',
    'debate_message_input' => 'メッセージ入力欄',
    'debate_message_input_desc' => '自分のパートの時に発言を入力し、送信します。',
    'debate_participant_list' => '参加者リスト',
    'debate_participant_list_desc' => '肯定側・否定側の参加者が表示されます。',
    'debate_timer' => 'タイマー',
    'debate_timer_desc' => '各パートの制限時間がカウントダウンされます。時間切れになると自動的に次のパートへ移行します。',
    'debate_prep_time' => '準備時間',
    'debate_prep_time_desc' => 'フォーマットによっては準備時間が設けられています。この時間は相手の発言はありません。',
    'debate_qa_time' => '質疑応答',
    'debate_qa_time_desc' => 'フォーマットによっては質疑応答の時間が設けられています。質問側と応答側に分かれます。',
    'debate_leave_interrupt' => '退出/中断',
    'debate_leave_interrupt_desc' => 'ディベート中の退出は原則できません。相手の接続が切れた場合など、システムが異常を検知した場合はディベートが
    中断されることがあります。',
    'step4_critique_history' => 'ステップ4：講評と履歴',
    'critique_step1_title' => '6. ディベート終了とAI講評',
    'critique_step1_desc1' => '最後のパートが終了すると、ディベートは自動的に完了します。その後、AIによる評価がバックグラウンドで開始されます。',
    'critique_step1_desc2' => '評価には数十秒〜数分程度かかる場合があります。評価が完了すると、結果ページへ自動的に移動します。',
    'critique_step2_title' => '7. 結果の確認',
    'critique_step2_desc1' => '結果ページでは、AIによる以下の講評を確認できます。',
    'critique_result_win_loss' => '勝敗判定 (肯定側/否定側)',
    'critique_result_point_analysis' => '論点の分析',
    'critique_result_reason' => '判定理由',
    'critique_result_feedback' => '各サイドへのフィードバック',
    'critique_step2_desc2' => 'タブを切り替えることで、ディベート中のチャットログも確認できます。',
    'critique_step3_title' => '8. ディベート履歴の確認',
    'critique_step3_desc1' => 'ナビゲーションメニューの<a href=":url" class="text-primary hover:underline">ディベート履歴</a>から、過去に
    参加したディベートの結果と内容を確認できます。',
    'critique_step3_desc2' => 'フィルターやソート機能を使って、特定のディベートを探すことも可能です。',
    'debate_formats' => 'ディベートフォーマット',
    'debate_formats_description' => 'DebateMatchでは、主要なディベート大会の公式ルールに基づいたフォーマットを複数用意しています。ルーム作成時
    に、希望のフォーマットを選択してください。',
    'available_formats' => '現在利用可能なフォーマット',
    'custom_format_description' => '上記の既存フォーマット以外に、自分でパート構成（話者、名称、時間など）を自由に設定できる「カスタム」フォーマ
    ットも利用可能です。ルーム作成時に「カスタム」を選択し、詳細を設定してください。',
    'faq_guide1_q' => 'ディベート経験がなくても参加できますか？',
    'faq_guide1_a' => 'はい、大歓迎です！ DebateMatchは、ディベート経験がない方でも気軽に参加し、楽しみながら学べます。AIによ
    るフィードバック機能も、スキルアップの助けになります。まずは、簡単なルームから参加してみることをお勧めします',
    'faq_guide2_q' => '1回のディベートにどのくらいの時間がかかりますか？',
    'faq_guide2_a' => '選択するフォーマットによって異なります。例えば、「ディベート甲子園(高校の部)」形式では約1時間程度です。カスタムフォーマット
    では自由に時間を設定できます。各パートの時間はディベート画面のタイムラインで確認できます。',
    'faq_guide3_q' => 'どんな論題でディベートできますか？',
    'faq_guide3_a' => 'ルーム作成者が自由に論題を設定できます。社会問題、科学技術、倫理、政策など、様々なテーマでのディベートが可能です。',
    'faq_guide4_q' => 'AIフィードバックはどのように機能しますか？',
    'faq_guide4_a' => 'ディベート中の全発言をテキストデータとしてAIに渡し、評価基準に基づいて分析を行います。分析結果から、最終的な勝敗判定、判定理
    由、各サイドへの具体的な改善点を含むフィードバックを生成します。',
    'faq_guide5_q' => '途中で接続が切れてしまった場合はどうなりますか？',
    'faq_guide5_a' => 'ネットワーク接続が不安定になると、一時的に相手との接続が切断された旨の通知が表示されることがあります。一定時間内に再接続でき
    ない場合、ディベートは強制的に中断・終了となる場合があります。安定したネットワーク環境でのご利用を推奨します。',
    'when_in_trouble' => '困ったときは',
    'trouble_description' => '不明な点や問題が発生した場合は、以下のリンクをご確認いただくか、お問い合わせください。',
    'terms_of_service' => '利用規約',
    'privacy_policy' => 'プライバシーポリシー',
    'contact_us' => 'お問い合わせ',

    // Dashboard
    'dashboard' => 'ダッシュボード', // 既存のキー 'Dashboard' を日本語に合わせるか検討
    'logged_in' => 'ログインしました！', // You're logged in!

    // Auth Pages
    'confirm_password_message' => '続けるにはパスワードを確認してください。',
    'forgot_password_message' => 'パスワードをお忘れですか？問題ありません。メールアドレスをお知らせいただければ、パスワードリセットリンクをメール
    でお送りします。',
    'reset_password' => 'パスワードをリセット',
    'verify_email_message' => '登録ありがとうございます！始める前に、メールでお送りしたリンクをクリックしてメールアドレスを確認していただけます
    か？メールが届かない場合は、再送いたします。',
    'verification_link_sent' => '新しい確認リンクが、登録時に提供されたメールアドレスに送信されました。',
    'resend_verification_email' => '確認メールを再送',

    // Profile Edit
    'profile_information' => 'プロフィール情報',
    'update_profile_description' => 'アカウントのプロフィール情報とメールアドレスを更新します。',
    'saved' => '保存しました。',
    'update_password' => 'パスワード更新',
    'update_password_description' => 'アカウントのセキュリティを確保するために、長くてランダムなパスワードを使用してください。',
    'current_password' => '現在のパスワード',
    'new_password' => '新しいパスワード',
    'confirm_new_password' => '新しいパスワードを確認', // confirm_password と重複するが、文脈で使い分ける可能性
    'delete_account' => 'アカウント削除',
    'delete_account_warning' => 'アカウントを削除すると、そのすべてのリソースとデータは完全に削除されます。アカウントを削除する前に、保持したいデ
    ータや情報をダウンロードしてください。',
    'confirm_delete_account' => 'アカウントを削除してもよろしいですか？',
    'confirm_delete_account_message' => 'アカウントを削除すると、そのすべてのリソースとデータは完全に削除されます。アカウントを完全に削除すること
    を確認するには、パスワードを入力してください。',

    // 他のビューで使用される一般的な単語もここに追加していく
    'status' => 'ステータス',
    'action' => '操作',
    // ...

    // ルーム作成・詳細
    'notes' => '備考',
    'debate_format' => 'ディベートフォーマット',
    'format_details' => 'フォーマット詳細',
    'format_name' => 'フォーマット名',
    'total_time' => '合計時間',
    'time_minutes' => '時間(分)',
    'save_format' => 'フォーマットを保存',
    'standard_format' => '標準フォーマット',
    'custom' => 'カスタム',
    'join_as_affirmative' => '肯定側で参加',
    'join_as_negative' => '否定側で参加',

    // ルーム一覧 (index.blade.php)
    'confirm_delete_room' => 'このルームを削除してもよろしいですか？',
    'deleting_room' => 'ルームを削除中...',
    'failed_to_delete_room' => 'ルームの削除に失敗しました。',
    'room_list' => 'ルーム一覧',
    'no_rooms_available' => '利用可能なルームはありません。',
    'created_at' => '作成日時',
    'view_details' => '詳細を見る',
    'lets_create_room' => '新しいディベートルームを作成してみましょう',

    // ルーム詳細/プレビュー (show.blade.php / preview.blade.php)
    'back_to_room_list' => 'ルーム一覧に戻る',
    'room_details' => 'ルーム詳細',
    'room_status' => 'ルームステータス',
    'created_by' => '作成者',
    'waiting' => '待機中', // General waiting state
    'ready' => '準備完了',   // General ready state
    'debating' => 'ディベート中',
    'evaluating' => '評価中',
    'finished' => '終了',
    'terminated' => '強制終了',
    'join_affirmative' => '肯定側で参加',
    'join_negative' => '否定側で参加',
    'leave_room' => 'ルームから退出',
    'confirm_leave_room' => 'このルームから退出してもよろしいですか？', // Generic leave confirmation
    'leaving_room' => 'ルームから退出中...',
    'failed_to_leave_room' => 'ルームからの退出に失敗しました。',
    'confirm_start_debate' => 'ディベートを開始してもよろしいですか？',
    'starting_debate' => 'ディベートを開始中...',
    'failed_to_start_debate' => 'ディベートの開始に失敗しました。',
    'not_participating' => '参加していません',
    'join_debate' => 'ディベートに参加',
    'select_side_to_join' => '参加したいサイドを選択してください。',
    'room_is_full' => 'このルームは既に満員です。',
    'cannot_join_room' => '現在このルームには参加できません。',
    'confirm_join_room_side' => '選択したサイドでルームに参加しますか？',
    'debate_room' => 'ディベートルーム', // ページタイトル等
    'exit_room' => 'ルーム退出', // ボタン
    'starting_debate_title' => 'ディベートを開始中', // モーダルタイトル等
    'redirecting_to_debate_soon' => 'まもなくディベートページへ移動します...',
    'confirm_exit_creator' => 'ホストが退出するとルームは閉鎖されます。本当に退出しますか？',
    'confirm_exit_participant' => '本当にルームから退出しますか？',


    // Livewire コンポーネント
    'network_disconnected' => 'ネットワーク接続が切断されました。',
    'reconnecting_in_seconds' => ':seconds 秒後に再接続を試みます...',
    'connection_restored' => '接続が回復しました。', // JSと共有
    'recruiting' => '募集中...',
    'waiting_for_participants' => '参加者を待っています...',
    'debaters_ready' => '参加者が揃いました！',
    'please_start_debate' => 'ディベートを開始してください。',
    'wait_for_debate_start' => 'ホストがディベートを開始するのを待っています...',
    'auto_redirect_on_start' => '開始されると自動的にリダイレクトされます。',
    'debate_in_progress_message' => 'ディベートは進行中です。',
    'click_if_no_redirect' => 'リダイレクトされない場合はこちらをクリック',
    'waiting_status' => '待機中', // Specific for room status display
    'ready_status' => '準備完了',   // Specific for room status display
    'closed_status' => '閉鎖済',
    'unknown_status' => '不明',
    'peer_connection_unstable' => '相手の接続が不安定です。',
    'waiting_for_reconnection' => '再接続を待っています...',


    // エラーページ
    'go_back_page' => '前のページに戻る',
    'back_to_home' => 'ホームに戻る',
    'find_debate_room' => 'ディベートルームを探す',
    'refresh_page' => 'ページを更新',
    'update' => '更新',
    'error_401_title' => '認証エラー (401)',
    'error_401_message' => 'このページにアクセスするには認証が必要です。',
    'error_401_action' => 'ログインしてから再度お試しください。',
    'error_403_title' => 'アクセス権限エラー (403)',
    'error_403_message' => 'このリソースへのアクセス権限がありません。',
    'error_403_action' => '別のアカウントでログインするか、ホームページに戻ってください。',
    'error_404_title' => 'ページが見つかりません (404)',
    'error_404_message' => 'お探しのページは存在しないか、移動された可能性があります。',
    'error_404_action' => 'URLをご確認いただくか、以下のリンクからホームページへお戻りください。',
    'error_419_title' => 'ページ有効期限切れ (419)',
    'error_419_message' => 'セッションの有効期限が切れました。操作を完了できませんでした。',
    'error_419_action' => 'ページを更新して、もう一度お試しください。',
    'error_429_title' => 'リクエスト過多 (429)',
    'error_429_message' => '短時間のうちにリクエストが集中しました。',
    'error_429_action' => 'しばらく時間をおいてから再度お試しください。',
    'error_500_title' => 'サーバーエラー (500)',
    'error_500_message' => 'サーバー内部でエラーが発生しました。',
    'error_500_action' => '問題が解決しない場合は、管理者にお問い合わせください。',
    'error_503_title' => 'サービス利用不可 (503)',
    'error_503_message' => '現在、サービスはメンテナンス中です。',
    'error_503_action' => 'しばらく時間をおいてから再度アクセスしてください。',

    // JavaScript Translations (for window.translations)
    'debate_finished_title' => 'ディベートが終了しました',
    'evaluating_message' => 'AIによる評価を行っています。しばらくお待ちください...',
    'evaluation_complete_title' => 'ディベート評価が完了しました',
    'redirecting_to_results' => '結果ページへ移動します。',
    'host_left_terminated' => '相手との接続が切断されたため、ディベートを終了します。',
    'debate_finished_overlay_title' => 'ディベート終了',
    'evaluating_overlay_message' => 'ディベートが終了しました。現在、AIが評価を行っています...',
    'go_to_results_page' => '結果ページへ',
    'user_joined_room' => ':name さんが参加しました。',
    'user_left_room' => ':name さんが退出しました。',
    'host_left_room_closed' => 'ホストが退出したため、ルームは閉鎖されました。',
    'debate_starting_message' => 'ディベートを開始します。ページ移動の準備をしています...',
    'redirecting_in_seconds' => ':seconds 秒後にディベートページへ移動します...',
    'format_info_missing' => 'フォーマット情報が見つかりません。', // JS用
    'minute_suffix' => '分', // JS用 (プレビュー表示)

    // ダッシュボード

    // 認証
    'or' => 'または',
    'login_with_google' => 'Googleアカウントでログイン',
    'login_with_x' => 'Xアカウントでログイン',
    'agree_terms_privacy' => '利用規約とプライバシーポリシーに同意して続行します。',
    'create_account' => 'アカウント作成',

    // フッター
    'copyright' => '© :year DebateMatch. All rights reserved.',
    'support' => 'サポート',

    // ディベートチャット & インターフェース
    'all' => '全て',
    'affirmative_side_label' => '肯定側', // チャットフィルター用
    'negative_side_label' => '否定側', // チャットフィルター用
    'no_messages_yet' => 'まだメッセージはありません。',
    'new_message' => '新しいメッセージ',
    'prep_time_turn' => '準備時間', // ターン表示名
    'opponent_turn' => "相手のターン",
    'remaining_time' => '残り',
    'prep_time_in_progress' => '準備時間中です...',
    'ready_to_send' => '送信可能です',
    'questioning_in_progress' => '質疑応答中です...',
    'enter_message_placeholder' => 'メッセージを入力...',
    'debaters' => 'ディベーター',
    'speaking' => '発言中',
    'online' => 'オンライン',
    'offline' => 'オフライン',
    'confirm_end_turn' => ':currentTurnName を終了し、:nextTurnName に進みますか？',
    'processing' => '処理中...',
    'end_turn' => 'パート終了',
    'current_turn_info' => '現在のターン情報',
    'remaining_time_label' => '残り時間:',
    'progress' => '進行状況',
    'questions_allowed' => '質疑応答可',
    'completed' => '完了',

    // ディベート履歴 (Records)
    'room_label' => 'ルーム:',
    'host_label' => 'ホスト:',
    'winner_label' => '勝者',
    'result_tab' => '結果',
    'debate_content_tab' => 'ディベート内容',
    'analysis_of_points' => '論点の分析',
    'judgment_result' => '判定結果',
    'winner_is' => '勝者:',
    'feedback_for_affirmative' => '肯定側へのフィードバック',
    'feedback_for_negative' => '否定側へのフィードバック',
    'no_evaluation_available' => 'このディベートの評価データはありません。',
    'view_debate_history' => 'ディベート履歴を見る',


    // プロフィール編集
    'already_have_account' => 'すでにアカウントをお持ちですか？',

    'google_login' => 'Googleアカウントでログイン',
    'twitter_login' => 'Twitterアカウントでログイン',

    'debate_information_tab' => 'ディベート情報',
    'timeline_tab' => 'タイムライン',
    'minute_unit' => '分',

    'your_stats' => 'あなたの統計',
    'total_debates' => '総ディベート数',
    'wins_count' => '勝利数',
    'losses_count' => '敗北数',
    'win_rate' => '勝率',
    'all_sides' => 'すべてのサイド',
    'all_results' => 'すべての結果',
    'win' => '勝利',
    'loss' => '敗北',
    'newest_first' => '新しい順',
    'oldest_first' => '古い順',
    'search_topic_placeholder' => '論題を検索',
    'reset_filters' => 'リセット',
    'apply_filters' => '適用',
    'records_count' => '表示: :first～:last / 全:total件',
    'filter_applied_indicator' => '(フィルター適用中)',
    'view_format_label' => '表示形式:',
    'opponent' => '対戦相手',
    'evaluation_label' => '評価',
    'no_debate_records' => 'ディベート履歴がありません',
    'evidence_usage' => '証拠資料の使用',
    'evidence_allowed' => '証拠資料あり',
    'evidence_not_allowed' => '証拠資料なし',
    'can_use_evidence' => '外部資料の引用可能',
    'cannot_use_evidence' => '個人の知識のみ',
    'google_login_failed' => 'Googleログインに失敗しました。もう一度お試しください。',
];
