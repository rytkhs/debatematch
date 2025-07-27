<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Debate UI Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for the debate interface and
    | real-time debate UI components throughout the application.
    |
    */

    // Chat and Messages
    'all' => '全て',
    'affirmative_side_label' => '肯定側',
    'negative_side_label' => '否定側',
    'no_messages_yet' => 'まだメッセージはありません。',
    'new_message' => '新しいメッセージ',
    'enter_message_placeholder' => 'メッセージを入力...',
    'send_message' => 'メッセージを送信',
    'resize_input_area' => 'リサイズハンドル',
    'expand_input_area' => '最大化',
    'shrink_input_area' => '最小化',
    'toggle_input_visibility' => '表示/非表示',

    // Turn Management
    'your_turn' => 'あなたのターン',
    'prep_time_turn' => '準備時間',
    'opponent_turn' => '相手のターン',
    'confirm_end_turn' => ':currentTurnName を終了し、:nextTurnName に進みますか？',
    'end_turn' => 'パート終了',
    'current_turn_info' => 'Now',
    'next_turn_info' => 'Next',

    // Time Management
    'remaining_time' => '残り',
    'remaining_time_label' => '残り時間:',
    'prep_time_in_progress' => '準備時間中です...',
    'questioning_in_progress' => '質疑応答中です...',

    // Participants
    'debaters' => 'ディベーター',
    'speaking' => '発言中',
    'online' => 'オンライン',
    'offline' => 'オフライン',

    // Status Messages
    'ready_to_send' => '送信可能です',
    'cannot_send_message_now' => '今はメッセージを送信できません',
    'progress' => '進行状況',
    'questions_allowed' => '質疑応答可',
    'completed' => '完了',

    // Tabs (if not in records.php)
    'debate_information_tab' => 'ディベート情報',
    'timeline_tab' => 'タイムライン',

    // Debate Finish
    'debate_finished_title' => 'ディベートが終了しました',
    'evaluating_message' => 'AIによる評価を行っています。しばらくお待ちください...',
    'evaluation_complete_title' => 'ディベート評価が完了しました',
    'redirecting_to_results' => '結果ページへ移動します。',
    'host_left_terminated' => '相手との接続が切断されたため、ディベートを終了します。',
    'debate_finished_overlay_title' => 'ディベート終了',
    'evaluating_overlay_message' => 'ディベートが終了しました。現在、AIが評価を行っています...',
    'go_to_results_page' => '結果ページへ',

    // Connection Messages
    'connection_lost_title' => '接続が切断されました',
    'connection_lost_message' => 'サーバーとの接続が切断されました。再接続を試みています...',
    'reconnecting_message' => '再接続中...',
    'reconnecting_failed_message' => '再接続に失敗しました。ページを再読み込みしてください。',
    'redirecting_after_termination' => '5秒後にトップページへ移動します...',

    // Early Termination
    'early_termination_request' => '早期終了を提案',
    'early_termination_requested' => '早期終了を提案しました',
    'early_termination_request_failed' => '早期終了の提案に失敗しました',
    'early_termination_agree' => '同意する',
    'early_termination_decline' => '拒否する',
    'early_termination_agreed' => '早期終了に合意しました。ディベートを終了します。',
    'early_termination_declined' => '早期終了が拒否されました。ディベートを継続します。',
    'early_termination_response_failed' => '早期終了への応答に失敗しました',
    'early_termination_proposal' => ':name さんが早期終了を提案しています',
    'early_termination_waiting_response' => '相手の応答を待っています...',
    'early_termination_proposal_expired' => '早期終了の提案が期限切れになりました',
    'early_termination_timeout_message' => '早期終了の提案は1分で期限切れになりました。ディベートを継続します。',
    'early_termination_expired_notification' => '早期終了提案がタイムアウトしました',
    'early_termination_completed' => 'ディベートを早期終了しました。',
    'early_termination_proposal_sent' => '早期終了を提案しました',
    'early_termination_response_sent_agree' => '早期終了への同意を送信しました',
    'early_termination_response_sent_decline' => '早期終了への拒否を送信しました',
    'early_termination_agreed_result' => '早期終了に合意しました。ディベートを終了します。',
    'early_termination_declined_result' => '早期終了が拒否されました。ディベートを継続します。',
    'early_termination_ai_desc' => 'ディベートを早期終了できます',
    'early_termination_human_desc' => 'ディベートの早期終了を提案できます',
    'early_termination_ai_button' => '早期終了',
    'early_termination_human_button' => '早期終了を提案',
    'early_termination_participant_only' => 'は参加者のみ可能です',
    'early_termination_wait_response' => '相手の応答をお待ちください',
];
