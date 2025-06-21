<?php

namespace App\Services\AI;

use App\Models\Room;
use App\Models\Debate;
use App\Models\User;
use App\Services\Room\FormatManager;
use App\Services\DebateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIDebateCreationService
{
    public function __construct(
        private FormatManager $formatManager,
        private DebateService $debateService
    ) {}

    /**
     * AIディベートを作成する
     */
    public function createAIDebate(array $validatedData, User $user): Debate
    {
        $aiUser = $this->getAIUser();

        $customFormatSettings = $this->processFormatSettings(
            $validatedData['format_type'],
            $validatedData
        );

        return DB::transaction(function () use ($validatedData, $customFormatSettings, $user, $aiUser) {
            $room = $this->createRoom($validatedData, $customFormatSettings, $user);

            $userSide = $validatedData['side'];
            $aiSide = ($userSide === 'affirmative') ? 'negative' : 'affirmative';

            // ユーザーとAIをルームに参加させる
            $room->users()->attach([
                $user->id => ['side' => $userSide],
                $aiUser->id => ['side' => $aiSide],
            ]);

            // ディベートを作成
            $debate = Debate::create([
                'room_id' => $room->id,
                'affirmative_user_id' => ($userSide === 'affirmative') ? $user->id : $aiUser->id,
                'negative_user_id' => ($userSide === 'negative') ? $user->id : $aiUser->id,
            ]);

            // ディベートを開始
            $this->debateService->startDebate($debate);

            // ルームステータスを更新
            $room->update(['status' => Room::STATUS_DEBATING]);

            // debateのリレーションを更新して最新の状態を反映
            $debate->load('room');

            Log::info('AI Debate created and started successfully.', [
                'debate_id' => $debate->id,
                'room_id' => $room->id,
                'user_id' => $user->id
            ]);

            return $debate;
        });
    }

    /**
     * AIディベートルームを退出する
     */
    public function exitAIDebate(Debate $debate, User $user): void
    {
        // 参加者確認
        if (!$this->isParticipant($debate, $user)) {
            throw new \Exception('You are not a participant in this debate');
        }

        // AIディベート確認
        if (!$debate->room->is_ai_debate) {
            throw new \Exception('This is not an AI debate');
        }

        DB::transaction(function () use ($debate, $user) {
            $debate->room->updateStatus(Room::STATUS_DELETED);

            if ($debate->turn_end_time !== null) {
                $debate->update(['turn_end_time' => null]);
            }

            Log::info('AI Debate exited and room deleted.', [
                'debate_id' => $debate->id,
                'room_id' => $debate->room->id,
                'user_id' => $user->id
            ]);
        });
    }

    /**
     * AIユーザーを取得する
     */
    private function getAIUser(): User
    {
        $aiUserId = (int)config('app.ai_user_id', 1);
        $aiUser = User::find($aiUserId);

        if (!$aiUser) {
            Log::critical('AI User not found!', ['ai_user_id' => $aiUserId]);
            throw new \Exception('AI User not found');
        }

        return $aiUser;
    }

    /**
     * フォーマット設定を処理する
     */
    private function processFormatSettings(string $formatType, array $validatedData): ?array
    {
        return $this->formatManager->processFormatSettings($formatType, $validatedData);
    }

    /**
     * AIディベートルームを作成する
     */
    private function createRoom(array $validatedData, ?array $customFormatSettings, User $user): Room
    {
        return Room::create([
            'name' => 'AI Debate',
            'topic' => $validatedData['topic'],
            'remarks' => null,
            'status' => Room::STATUS_READY,
            'language' => $validatedData['language'],
            'format_type' => $validatedData['format_type'],
            'custom_format_settings' => $customFormatSettings,
            'evidence_allowed' => false,
            'created_by' => $user->id,
            'is_ai_debate' => true,
        ]);
    }

    /**
     * ユーザーがディベートの参加者かどうかを確認
     */
    private function isParticipant(Debate $debate, User $user): bool
    {
        return $debate->affirmative_user_id === $user->id || $debate->negative_user_id === $user->id;
    }
}
