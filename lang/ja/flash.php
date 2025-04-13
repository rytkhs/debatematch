<?php

return [
    // DebateController
    'debate.show.finished' => 'ディベートは終了しました。',
    'debate.show.terminated' => '切断されました。',
    'debate.terminate.success' => '相手との接続が切断されたため、ディベートを終了しました。',

    // RoomController
    'room.store.success' => 'ルームを作成しました',
    'room.show.forbidden' => 'アクセスできません',
    'room.join.already_joined' => 'すでにこのルームに参加しています。',
    'room.join.full' => 'このルームは既に満員です。',
    'room.join.not_waiting' => 'このルームには参加できません。',
    'room.join.success' => 'ルームに参加しました。',
    'room.exit.already_closed' => 'このルームは既に終了しています。',
    'room.exit.creator_success' => 'ルームを削除しました。',
    'room.exit.participant_success' => 'ルームを退出しました。',
    'room.start_debate.unauthorized' => 'ディベートを開始する権限がありません。',

    // AuthenticatedSessionController
    'auth.login.success' => 'ログインしました',
    'auth.logout.success' => 'ログアウトしました',

    // Livewire/Debates/Chat.php
    'chat.message.received' => 'メッセージを受信しました',

    // Livewire/Debates/Header.php
    'header.turn.my_turn' => 'あなたのパートです',

    // Livewire/Debates/MessageInput.php
    'message_input.send.success' => 'メッセージを送信しました',

    // Livewire/Debates/Participants.php
    'participants.turn.advanced' => 'パートを終了しました',

    // Livewire/Rooms/StartDebateButton.php
    'start_debate.error.not_enough_participants' => 'ディベーターが揃っていません。',
    'start_debate.error.already_started' => 'ディベートはすでに開始されています。',

    // Middleware/CheckUserActiveStatus.php
    'middleware.active_debate' => 'ディベートが進行中です',
    'middleware.active_room' => 'ルーム参加中です',
];
