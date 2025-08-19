<?php

namespace App\Http\Controllers\Api;

use App\Events\BuzzerPressed;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\BuzzerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin_id' => 'required|integer',
        ]);

        $team = match ($validated['pin_id']) {
            '0' => Team::where('name', 'Team Blade')->orderBy('id', 'DESC')->firstOrFail(), // White
            '1' => Team::where('name', 'Team Artisan')->orderBy('id', 'DESC')->firstOrFail(), // Red
            '2' => Team::where('name', 'Team Eloquent')->orderBy('id', 'DESC')->firstOrFail(), // Yellow
            '3' => Team::where('name', 'Team Facade')->orderBy('id', 'DESC')->firstOrFail(), // Green
            '4' => Team::where('name', 'Team Illuminate')->orderBy('id', 'DESC')->firstOrFail(),  // Blue
            default => abort(400, 'Invalid buzzer pin ID'),
        };

        try {
            /*$buzzerEvent = $this->buzzerService->registerBuzz(
                $team,
            );*/

            // Broadcast the buzzer event via Livewire
            broadcast(new BuzzerPressed($team))->toOthers();

            return response()->json([
                'success' => true,
                // 'is_first' => $buzzerEvent->is_first,
                'team' => $team->name,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
