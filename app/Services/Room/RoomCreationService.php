<?php

namespace App\Services\Room;

use App\Models\Room;
use App\Models\User;
use App\Services\SlackNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomCreationService
{
    public function __construct(
        private FormatManager $formatManager,
        private SlackNotifier $slackNotifier
    ) {}

    /**
     * ルームを作成する
     */
    public function createRoom(array $validatedData, User $creator): Room
    {
        $customFormatSettings = $this->processFormatSettings(
            $validatedData['format_type'],
            $validatedData
        );

        return DB::transaction(function () use ($validatedData, $customFormatSettings, $creator) {
            $room = Room::create([
                'name' => $validatedData['name'],
                'topic' => $validatedData['topic'],
                'remarks' => $validatedData['remarks'] ?? null,
                'status' => Room::STATUS_WAITING,
                'language' => $validatedData['language'],
                'format_type' => $validatedData['format_type'],
                'custom_format_settings' => $customFormatSettings,
                'evidence_allowed' => $validatedData['evidence_allowed'],
                'created_by' => $creator->id,
            ]);

            // ユーザーが選択した側でルームに参加させる
            $room->users()->attach($creator->id, [
                'side' => $validatedData['side'],
            ]);

            $this->sendSlackNotification($room, $creator);

            return $room;
        });
    }

    /**
     * フォーマット設定を処理する
     */
    private function processFormatSettings(string $formatType, array $validatedData): ?array
    {
        if ($formatType === 'custom') {
            return $this->formatManager->generateCustomFormat($validatedData['turns']);
        } elseif ($formatType === 'free') {
            return $this->formatManager->generateFreeFormat(
                $validatedData['turn_duration'],
                $validatedData['max_turns']
            );
        }

        return null;
    }

    /**
     * Slack通知を送信する
     */
    private function sendSlackNotification(Room $room, User $creator): void
    {
        $message = "新しいルームが作成されました。\n"
            . "ルーム名: " . ($room->name ?? $room->topic) . "\n"
            . "トピック: {$room->topic}\n"
            . "作成者: {$creator->name}\n"
            . "URL: " . route('rooms.preview', $room);

        $result = $this->slackNotifier->send($message);
        if (!$result) {
            Log::warning("Slack通知の送信に失敗しました(ルーム作成)。 Room ID: {$room->id}");
        }
    }
}
