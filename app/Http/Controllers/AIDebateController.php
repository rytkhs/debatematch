<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use App\Http\Requests\AI\AIDebateCreationRequest;
use App\Services\AI\AIDebateCreationService;
use App\Services\Room\FormatManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AIDebateController extends Controller
{
    public function __construct(
        private FormatManager $formatManager,
        private AIDebateCreationService $aiDebateService
    ) {}

    public function create()
    {
        $translatedFormats = $this->formatManager->getTranslatedFormats();
        $languageOrder = $this->formatManager->getLanguageOrder();

        return view('ai.debate.create', compact('translatedFormats', 'languageOrder'));
    }

    public function store(AIDebateCreationRequest $request)
    {
        $validatedData = $request->getProcessedData();
        $user = Auth::user();

        try {
            $debate = $this->aiDebateService->createAIDebate($validatedData, $user);
            return redirect()->route('debate.show', $debate)
                ->with('success', __('ai_debate.ai_debate_started'));
        } catch (\Exception $e) {
            Log::error('Failed to create AI debate', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('welcome')
                ->with('error', __('ai_debate.ai_debate_creation_failed'));
        }
    }

    /**
     * AIディベートを退出する
     */
    public function exit(Debate $debate)
    {
        $user = Auth::user();

        try {
            $this->aiDebateService->exitAIDebate($debate, $user);
            return redirect()->route('welcome')
                ->with('info', __('flash.ai_debate.exit.success'));
        } catch (\Exception $e) {
            Log::error('Failed to exit AI debate', [
                'user_id' => $user->id,
                'debate_id' => $debate->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('debate.show', $debate)
                ->with('error', __('flash.ai_debate.exit.error'));
        }
    }
}
