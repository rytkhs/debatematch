<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use App\Services\DebateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EarlyTerminationController extends Controller
{
    private DebateService $debateService;

    public function __construct(DebateService $debateService)
    {
        $this->debateService = $debateService;
    }

    /**
     * 早期終了を提案する
     */
    public function request(Debate $debate): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'error' => __('messages.error')
                ], 401);
            }

            $success = $this->debateService->requestEarlyTermination($debate, $userId);

            if ($success) {
                return response()->json([
                    'message' => __('messages.early_termination_requested')
                ]);
            } else {
                return response()->json([
                    'error' => __('messages.early_termination_request_failed')
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Early termination request failed', [
                'debate_id' => $debate->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => __('messages.error')
            ], 500);
        }
    }

    /**
     * 早期終了提案に応答する
     */
    public function respond(Request $request, Debate $debate): JsonResponse
    {
        try {
            $request->validate([
                'agree' => 'required|boolean'
            ]);

            $userId = Auth::id();
            $agree = $request->boolean('agree');

            if (!$userId) {
                return response()->json([
                    'error' => __('messages.error')
                ], 401);
            }

            $success = $this->debateService->respondToEarlyTermination($debate, $userId, $agree);

            if ($success) {
                $message = $agree
                    ? __('messages.early_termination_agreed')
                    : __('messages.early_termination_declined');

                return response()->json([
                    'message' => $message
                ]);
            } else {
                return response()->json([
                    'error' => __('messages.early_termination_response_failed')
                ], 400);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Early termination response failed', [
                'debate_id' => $debate->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => __('messages.error')
            ], 500);
        }
    }

    /**
     * 早期終了の状態を取得する
     */
    public function status(Debate $debate): JsonResponse
    {
        try {
            $status = $this->debateService->getEarlyTerminationStatus($debate);

            return response()->json($status);
        } catch (\Exception $e) {
            Log::error('Failed to get early termination status', [
                'debate_id' => $debate->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => __('messages.error')
            ], 500);
        }
    }
}
