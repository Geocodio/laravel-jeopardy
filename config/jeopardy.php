<?php

return [
    'teams' => [
        [
            'name' => 'Team Illuminate',
            'color_hex' => '#3B82F6',
            'buzzer_pin' => 1,
        ],
        [
            'name' => 'Team Facade',
            'color_hex' => '#10B981',
            'buzzer_pin' => 2,
        ],
        [
            'name' => 'Team Eloquent',
            'color_hex' => '#EAB308',
            'buzzer_pin' => 3,
        ],
        [
            'name' => 'Team Blade',
            'color_hex' => '#FFFFFF',
            'buzzer_pin' => 4,
        ],
        [
            'name' => 'Team Artisan',
            'color_hex' => '#EF4444',
            'buzzer_pin' => 5,
        ],
    ],

    'game_settings' => [
        'daily_double_min_value' => 200,
        'lightning_round_time_limit' => 30,
        'points_multiplier' => 1,
    ],

    'sound_effects' => [
        'daily_double' => 'sounds/daily-double.mp3',
        'times_up' => 'sounds/times-up.mp3',
        'right_answer' => 'sounds/right-answer.mp3',
    ],
];