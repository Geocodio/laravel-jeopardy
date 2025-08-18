<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Support\Facades\DB;

class GameService
{
    public function createGame(): Game
    {
        return DB::transaction(function () {
            $game = Game::create([
                'status' => 'setup',
                'daily_double_used' => false,
            ]);

            $this->logAction($game, 'game_created', ['game_id' => $game->id]);

            return $game;
        });
    }

    public function setupTeams(Game $game): void
    {
        $teams = config('jeopardy.teams');

        foreach ($teams as $teamData) {
            $game->teams()->create($teamData);
        }

        $this->logAction($game, 'teams_created', ['count' => count($teams)]);
    }

    public function generateBoard(int $gameId): void
    {
        $game = Game::findOrFail($gameId);

        $categoriesData = $this->getLaravelJeopardyData();

        $position = 1;
        foreach ($categoriesData as $categoryName => $clues) {
            $category = $game->categories()->create([
                'name' => $categoryName,
                'position' => $position++,
            ]);

            foreach ($clues as $value => $clueData) {
                $category->clues()->create([
                    'question_text' => $clueData['question'],
                    'answer_text' => $clueData['answer'],
                    'value' => $value,
                    'is_daily_double' => false,
                    'is_revealed' => false,
                    'is_answered' => false,
                ]);
            }
        }

        $this->placeDailyDouble($gameId);
        $this->logAction($game, 'board_generated', ['categories' => count($categoriesData)]);
    }

    private function getLaravelJeopardyData(): array
    {
        return [
            'Taylor\'s Version' => [
                100 => [
                    'question' => 'Swift\'s emotional 2012 album title describes what developers see when tests fail',
                    'answer' => 'What is "Red"?',
                ],
                300 => [
                    'question' => 'Taylor\'s 2014 hit about relationships gone wrong also describes what PHP throws when you try to echo an uninitialized variable',
                    'answer' => 'What is "Blank Space"?',
                ],
                500 => [
                    'question' => 'Before creating Laravel, Taylor Otwell programmed in this 1960s business language',
                    'answer' => 'What is COBOL?',
                ],
                1000 => [
                    'question' => 'Taylor Otwell played this sport after school every day growing up',
                    'answer' => 'What is basketball?',
                ],
            ],
            '404: Category Not Found' => [
                100 => [
                    'question' => 'This HTTP status code is returned when the client should authenticate itself',
                    'answer' => 'What is 401 (Unauthorized)?',
                ],
                300 => [
                    'question' => 'This Laravel package provides a beautiful debugging interface to inspect errors',
                    'answer' => 'What is Debugbar for Laravel?',
                ],
                500 => [
                    'question' => 'This error occurs when PHP runs out of allocated memory',
                    'answer' => 'What is "Fatal error: Allowed memory size exhausted"?',
                ],
                1000 => [
                    'question' => 'This HTTP status code means "I\'m a teapot" and was an April Fool\'s joke',
                    'answer' => 'What is 418?',
                ],
            ],
            'Laracon Legends' => [
                100 => [
                    'question' => 'This European city has hosted Laracon EU the most times since 2013',
                    'answer' => 'What is Amsterdam?',
                ],
                300 => [
                    'question' => 'These two Australian cities have hosted Laracon AU',
                    'answer' => 'What are Sydney and Brisbane?',
                ],
                500 => [
                    'question' => 'This U.S. capital city hosted the very first Laracon in 2013',
                    'answer' => 'What is Washington, DC?',
                ],
                1000 => [
                    'question' => 'This entrepreneur had the vision for the first Laracon',
                    'answer' => 'Who is Ian Landsman?',
                ],
            ],
            'Rød Grød Med Fløde' => [
                100 => [
                    'question' => 'This Danish shipping giant is the world\'s second-largest container company',
                    'answer' => 'What is Maersk?',
                ],
                300 => [
                    'question' => 'These seafarers from whom Europe\'s oldest monarchy traces back',
                    'answer' => 'Who are the Vikings?',
                ],
                500 => [
                    'question' => 'This Danish programmer created PHP in 1994 and was born in Greenland',
                    'answer' => 'Who is Rasmus Lerdorf?',
                ],
                1000 => [
                    'question' => 'These three special letters make Danish keyboards unique among Nordic countries',
                    'answer' => 'What are Æ, Ø, and Å?',
                ],
            ],
            'Breaking Prod' => [
                100 => [
                    'question' => 'The worst day of the week to deploy to production',
                    'answer' => 'What is Friday?',
                ],
                300 => [
                    'question' => 'This containerization tool ensures "it works on everyone\'s machine"',
                    'answer' => 'What is Docker?',
                ],
                500 => [
                    'question' => 'This deployment strategy uses color-coded environments to achieve zero-downtime deployment',
                    'answer' => 'What is blue-green deployment?',
                ],
                1000 => [
                    'question' => 'This phenomenon occurs when a popular cache key expires and multiple processes try to regenerate it simultaneously',
                    'answer' => 'What is a cache stampede (or thundering herd)?',
                ],
            ],
            'Eloquently Speaking' => [
                100 => [
                    'question' => 'This relationship represents the inverse of hasMany',
                    'answer' => 'What is belongsTo()?',
                ],
                300 => [
                    'question' => 'This property specifies which attributes can be mass assigned',
                    'answer' => 'What is $fillable?',
                ],
                500 => [
                    'question' => 'This feature allows models to be "deleted" without removing them from the database',
                    'answer' => 'What are soft deletes?',
                ],
                1000 => [
                    'question' => 'This feature allows you to listen to model lifecycle events',
                    'answer' => 'What are model events (or observers)?',
                ],
            ],
        ];
    }

    public function placeDailyDouble(int $gameId): void
    {
        $game = Game::with('categories.clues')->findOrFail($gameId);

        $eligibleClues = $game->categories->flatMap(function ($category) {
            return $category->clues->where('value', '>=', config('jeopardy.game_settings.daily_double_min_value', 300));
        });

        if ($eligibleClues->isNotEmpty()) {
            $randomClue = $eligibleClues->random();
            $randomClue->update(['is_daily_double' => true]);

            $this->logAction($game, 'daily_double_placed', [
                'clue_id' => $randomClue->id,
                'category' => $randomClue->category->name,
                'value' => $randomClue->value,
            ]);
        }
    }

    public function transitionToLightningRound(int $gameId): void
    {
        $game = Game::findOrFail($gameId);

        DB::transaction(function () use ($game) {
            $game->update(['status' => 'lightning_round']);

            $questions = [
                ['question' => 'What method adds a where clause to an Eloquent query?', 'answer' => 'where()'],
                ['question' => 'What Blade directive creates a CSRF token field?', 'answer' => '@csrf'],
                ['question' => 'What artisan command rolls back the last migration?', 'answer' => 'migrate:rollback'],
                ['question' => 'What facade provides access to the cache?', 'answer' => 'Cache'],
                ['question' => 'What middleware verifies CSRF tokens?', 'answer' => 'VerifyCsrfToken'],
            ];

            foreach ($questions as $index => $questionData) {
                $game->lightningQuestions()->create([
                    'question_text' => $questionData['question'],
                    'answer_text' => $questionData['answer'],
                    'order_position' => $index + 1,
                    'is_current' => $index === 0,
                    'is_answered' => false,
                ]);
            }

            $this->logAction($game, 'lightning_round_started', [
                'questions_count' => count($questions),
            ]);
        });
    }

    public function calculateFinalScores(int $gameId): array
    {
        $game = Game::with('teams')->findOrFail($gameId);

        $scores = $game->teams->map(function ($team) {
            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'score' => $team->score,
            ];
        })->sortByDesc('score')->values()->all();

        $this->logAction($game, 'final_scores_calculated', ['scores' => $scores]);

        return $scores;
    }

    public function saveGameState(int $gameId): void
    {
        $game = Game::with([
            'teams',
            'categories.clues',
            'lightningQuestions',
        ])->findOrFail($gameId);

        $state = [
            'game' => $game->toArray(),
            'timestamp' => now()->toIso8601String(),
        ];

        $this->logAction($game, 'game_state_saved', ['timestamp' => $state['timestamp']]);
    }

    public function restoreGame(int $gameId): Game
    {
        $game = Game::with([
            'teams',
            'categories.clues',
            'lightningQuestions',
            'gameLogs' => function ($query) {
                $query->latest()->limit(1);
            },
        ])->findOrFail($gameId);

        $this->logAction($game, 'game_restored', ['game_id' => $gameId]);

        return $game;
    }

    private function logAction(Game $game, string $action, array $details = []): void
    {
        $game->gameLogs()->create([
            'action' => $action,
            'details' => $details,
        ]);
    }
}
