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
        'daily_double_min_value' => 300,
    ],

    'categories' => [
        '404: Category Not Found' => [
            'clues' => [
                100 => [
                    'question' => 'This Laravel tool provides an interactive shell for testing code',
                    'answer' => 'What is Tinker?',
                ],
                300 => [
                    'question' => 'This Laravel blade directive is used to display unescaped HTML content',
                    'answer' => 'What is "{!! !!}"?',
                ],
                500 => [
                    'question' => 'This HTTP header is commonly missing when CORS errors occur',
                    'answer' => 'What is "Access-Control-Allow-Origin"?',
                ],
                1000 => [
                    'question' => 'This HTTP status code means "I\'m a teapot" and was an April Fool\'s joke',
                    'answer' => 'What is 418?',
                ],
            ],
        ],
        'Laracon Legends' => [
            'clues' => [
                100 => [
                    'question' => 'This European city has hosted Laracon EU the most times since 2013',
                    'answer' => 'What is Amsterdam?',
                ],
                300 => [
                    'question' => 'These two Australian cities have hosted Laracon AU',
                    'answer' => 'What are Sydney and Brisbane?',
                ],
                500 => [
                    'question' => 'This U.S. city hosted the very first Laracon in 2013',
                    'answer' => 'What is Washington, DC?',
                ],
                1000 => [
                    'question' => 'This entrepreneur had the vision for the first Laracon.', // Hint: He is also known as the "Godfather of Laravel"
                    'answer' => 'Who is Ian Landsman?',
                ],
            ],
        ],
        'Rød Grød Med Fløde' => [
            'clues' => [
                100 => [
                    'question' => 'This Danish beer brand modestly claims to be "probably" the world\'s best',
                    'answer' => 'What is Carlsberg?',
                ],
                300 => [
                    'question' => 'This voice and messaging service was co-founded by a Danish-Estonian duo before being sold to Microsoft for $8.5B',
                    'answer' => 'What is Skype?',
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
        ],
        'Breaking Prod' => [
            'clues' => [
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
        ],
        'Taylor\'s Version' => [
            'clues' => [
                100 => [
                    'question' => 'Swift\'s emotional 2012 album title describes what developers see when tests fail',
                    'answer' => 'What is "Red"?',
                ],
                300 => [
                    'question' => 'Taylor Otwell\'s take on server management and deployment, this SaaS platform lets you provision and manage servers without the command line hassle',
                    'answer' => 'What is Laravel Forge?',
                ],
                500 => [
                    'question' => 'Taylor\'s 2014 hit about relationships gone wrong also describes what PHP displays when you echo an uninitialized variable',
                    'answer' => 'What is "Blank Space"?',
                ],
                1000 => [
                    'question' => 'Before creating Laravel, Taylor Otwell programmed in this 1960s business language',
                    'answer' => 'What is COBOL?',
                ],
            ],
        ],
        'Eloquently Speaking' => [
            'clues' => [
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
        ],
    ],

    'lightning_questions' => [
        // Laravel/PHP Quick Recognition
        ['question' => 'What is the default Laravel database driver in Laravel 12?', 'answer' => 'SQLite'],
        ['question' => 'What is Laravel\'s CLI tool name?', 'answer' => 'Artisan'],
        ['question' => 'What is Laravel\'s ORM called?', 'answer' => 'Eloquent'],
        ['question' => 'What is the default Laravel testing framework?', 'answer' => 'Pest or PHPUnit'],
        ['question' => 'What is Laravel\'s real-time event broadcasting tool?', 'answer' => 'Echo'],
        ['question' => 'What is Laravel\'s blade directive for CSRF protection?', 'answer' => '@csrf'],
        ['question' => 'Who is the founder of Laravel News?', 'answer' => 'Eric Barnes'],

        // Laravel Commands
        ['question' => 'What is the command to list all routes?', 'answer' => 'php artisan route:list'],
        ['question' => 'What is the command to create a new Artisan console command?', 'answer' => 'php artisan make:command'],

        // HTTP & Web Basics
        ['question' => 'What is the HTTP method commonly used for creating resources?', 'answer' => 'POST'],
        ['question' => 'What are the HTTP methods commonly used for updating resources?', 'answer' => 'PUT or PATCH'],
        ['question' => 'What is the HTTP status code for success/OK?', 'answer' => '200'],
        ['question' => 'What is the HTTP status code for not found?', 'answer' => '404'],
        ['question' => 'What is the HTTP status code for server error?', 'answer' => '500'],
        ['question' => 'What are the HTTP status codes for redirect?', 'answer' => '301 or 302'],
        ['question' => 'What is the HTTP status code for unauthorized?', 'answer' => '401'],
        ['question' => 'What is the HTTP status code for forbidden?', 'answer' => '403'],

        // Frontend/CSS
        ['question' => 'What is the CSS framework used in this project?', 'answer' => 'Tailwind CSS'],
        ['question' => 'What is the JavaScript framework used for reactivity in Livewire?', 'answer' => 'Alpine.js'],
        ['question' => 'What is the Tailwind CSS utility for spacing between flex items?', 'answer' => 'gap'],
        ['question' => 'What is Tailwind\'s dark mode prefix?', 'answer' => 'dark:'],
        ['question' => 'What is CSS Grid\'s main competitor for layouts?', 'answer' => 'Flexbox'],

        // Danish/Copenhagen Tech
        ['question' => 'What is Denmark\'s domain extension (tld)?', 'answer' => '.dk'],
        ['question' => 'What is the Danish word for "developer"?', 'answer' => 'Udvikler'],
        ['question' => 'What is the Danish currency?', 'answer' => 'Kroner or DKK'],

        // Fun/Trivia
        ['question' => 'What did PHP originally stand for?', 'answer' => 'Personal Home Page'],
        ['question' => 'What year was Laravel first released?', 'answer' => '2011'],
        ['question' => 'What is PHP\'s elephant mascot called?', 'answer' => 'ElePHPant'],
        ['question' => 'What is Laravel\'s annual conference called?', 'answer' => 'Laracon'],
        ['question' => 'What database typically uses port 3306?', 'answer' => 'MySQL'],
        ['question' => 'What database typically uses port 6379?', 'answer' => 'Redis'],

        // Additional Laravel-specific questions
        ['question' => 'What method skips X rows in an Eloquent query?', 'answer' => 'offset() or skip()'],
        ['question' => 'What artisan command rolls back the last migration batch?', 'answer' => 'migrate:rollback'],
        ['question' => 'What facade provides access to the cache?', 'answer' => 'Cache'],
        ['question' => 'What middleware verifies CSRF tokens on POST requests?', 'answer' => 'VerifyCsrfToken'],

        ['question' => 'Which speaker has travelled the furthest to get to Laravel Live Denmark?', 'answer' => 'Leah Thompson'],
        ['question' => 'Name a speaker that currently works for Laravel', 'answer' => 'Leah Thompson, Nuno Maduro, Ashley Hindle'],
        ['question' => 'Name a speaker that currently works for Tailwind Labs', 'answer' => 'Peter Suhm'],
    ],
];
