<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BuzzerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BuzzerController extends Controller
{
    protected BuzzerService $buzzerService;

    public function __construct(BuzzerService $buzzerService)
    {
        $this->buzzerService = $buzzerService;
    }

    /**
     * Handle buzzer press webhook from Raspberry Pi
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'timestamp' => 'required|string',
        ]);

        try {
            // Get the team to verify pin
            $team = \App\Models\Team::findOrFail($validated['team_id']);
            
            $buzzerEvent = $this->buzzerService->registerBuzz(
                $validated['team_id'],
                $team->buzzer_pin,
                $validated['timestamp']
            );

            // Broadcast the buzzer event via Livewire
            broadcast(new \App\Events\BuzzerPressed(
                $team->id,
                $validated['timestamp'],
                $team->game_id
            ))->toOthers();

            return response()->json([
                'success' => true,
                'is_first' => $buzzerEvent->is_first,
                'team' => $team->name,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Test buzzer connection
     */
    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => 'required|integer',
        ]);

        $result = $this->buzzerService->testBuzzer($validated['pin']);

        return response()->json($result);
    }

    /**
     * Get buzzer status for all teams
     */
    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => 'required|integer|exists:games,id',
        ]);

        $status = $this->buzzerService->getBuzzerStatus($validated['game_id']);

        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    /**
     * Reset all buzzers
     */
    public function reset(): JsonResponse
    {
        $this->buzzerService->resetAllBuzzers();

        return response()->json([
            'success' => true,
            'message' => 'All buzzers have been reset',
        ]);
    }
}
