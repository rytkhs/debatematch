<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Room;
use App\Models\Debate;
use App\Models\User;
use App\Services\DebateService;
use Illuminate\Support\Facades\Lang;

class AIDebateController extends Controller
{
    protected DebateService $debateService;

    public function __construct(DebateService $debateService)
    {
        $this->debateService = $debateService;
    }

    public function create()
    {
        $rawFormats = config('debate.formats');
        $translatedFormats = [];

        // アメリカと日本のフォーマットに分ける
        $usFormats = [
            'format_name_nsda_policy' => $rawFormats['format_name_nsda_policy'],
            'format_name_nsda_ld' => $rawFormats['format_name_nsda_ld'],
            // 'format_name_npda_parliamentary' => $rawFormats['format_name_npda_parliamentary'],
        ];

        $jpFormats = [
            'format_name_nada_high' => $rawFormats['format_name_nada_high'],
            'format_name_henda' => $rawFormats['format_name_henda'],
            'format_name_coda' => $rawFormats['format_name_coda'],
            'format_name_jda' => $rawFormats['format_name_jda'],
        ];

        // ロケールに基づいて順序を決定
        $locale = app()->getLocale();
        if ($locale === 'ja') {
            $sortedFormats = array_merge($jpFormats, $usFormats);
            $languageOrder = ['japanese', 'english']; // 日本語ロケールでは日本語を先に
        } else {
            $sortedFormats = array_merge($usFormats, $jpFormats);
            $languageOrder = ['english', 'japanese']; // その他のロケールでは英語を先に
        }

        foreach ($sortedFormats as $formatKey => $turns) {
            $translatedFormatName = __('debates.' . $formatKey);
            $translatedTurns = [];
            foreach ($turns as $index => $turn) {
                $translatedTurn = $turn;
                $translatedTurn['name'] = __('debates.' . $turn['name']);
                $translatedTurns[$index] = $translatedTurn;
            }
            $translatedFormats[$formatKey] = [
                'name' => $translatedFormatName,
                'turns' => $translatedTurns
            ];
        }

        return view('ai.debate.create', compact('translatedFormats', 'languageOrder'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'topic' => 'required|string|max:255',
            'side'  => 'required|in:affirmative,negative',
            'language' => 'required|in:japanese,english',
            'format_type' => 'required|string',
        ]);

        $customFormatSettings = null;
        if ($validatedData['format_type'] === 'custom') {
            $request->validate([
                'turns' => 'required|array|min:1',
                'turns.*.speaker' => 'required|in:affirmative,negative',
                'turns.*.name' => 'required|string|max:255',
                'turns.*.duration' => 'required|integer|min:1|max:60',
                'turns.*.is_prep_time' => 'nullable|boolean',
                'turns.*.is_questions' => 'nullable|boolean',
            ]);

            $customFormatSettings = [];
            foreach ($request->input('turns') as $index => $turn) {
                $durationInSeconds = (int)$turn['duration'] * 60;
                $isPrepTime = isset($turn['is_prep_time']) && $turn['is_prep_time'] == '1';
                $isQuestions = isset($turn['is_questions']) && $turn['is_questions'] == '1';

                $customFormatSettings[$index + 1] = [
                    'name' => $turn['name'],
                    'duration' => $durationInSeconds,
                    'speaker' => $turn['speaker'],
                    'is_prep_time' => $isPrepTime,
                    'is_questions' => $isQuestions,
                ];
            }
        }

        $user = Auth::user();
        $aiUserId = (int)config('app.ai_user_id', 1);
        $aiUser = User::find($aiUserId);

        if (!$aiUser) {
            Log::critical('AI User not found!', ['ai_user_id' => $aiUserId]);
            return redirect()->route('welcome')->with('error', __('messages.ai_user_not_found'));
        }

        try {
            $debate = DB::transaction(function () use ($validatedData, $customFormatSettings, $user, $aiUser) {
                $room = Room::create([
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

                $userSide = $validatedData['side'];
                $aiSide = ($userSide === 'affirmative') ? 'negative' : 'affirmative';

                $room->users()->attach([
                    $user->id => ['side' => $userSide],
                    $aiUser->id => ['side' => $aiSide],
                ]);

                $debate = Debate::create([
                    'room_id' => $room->id,
                    'affirmative_user_id' => ($userSide === 'affirmative') ? $user->id : $aiUser->id,
                    'negative_user_id' => ($userSide === 'negative') ? $user->id : $aiUser->id,
                ]);

                $this->debateService->startDebate($debate);

                $room->update(['status' => Room::STATUS_DEBATING]);

                Log::info('AI Debate created and started successfully.', ['debate_id' => $debate->id, 'room_id' => $room->id]);

                return $debate;
            });

            return redirect()->route('debate.show', $debate)->with('success', __('messages.ai_debate_started'));
        } catch (\Exception $e) {
            Log::error('Failed to create AI debate', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('welcome')->with('error', __('messages.ai_debate_creation_failed'));
        }
    }

    /**
     * AIディベートを退出する
     */
    public function exit(Debate $debate)
    {
        // ユーザーがこのディベートの参加者であることを確認
        $user = Auth::user();
        if ($debate->affirmative_user_id !== $user->id && $debate->negative_user_id !== $user->id) {
            return redirect()->route('welcome');
        }

        // ルームがAIディベートであることを確認
        if (!$debate->room->is_ai_debate) {
            return redirect()->route('debate.show', $debate);
        }

        try {
            DB::transaction(function () use ($debate) {

                $debate->room->updateStatus(Room::STATUS_DELETED);

                if ($debate->turn_end_time !== null) {
                    $debate->update(['turn_end_time' => null]);
                }

                Log::info('AI Debate exited and room deleted.', [
                    'debate_id' => $debate->id,
                    'room_id' => $debate->room->id,
                    'user_id' => Auth::id()
                ]);
            });

            return redirect()->route('welcome')->with('info', __('flash.ai_debate.exit.success'));
        } catch (\Exception $e) {
            Log::error('Failed to exit AI debate', [
                'user_id' => Auth::id(),
                'debate_id' => $debate->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('debate.show', $debate)->with('error', __('flash.ai_debate.exit.error'));
        }
    }
}
