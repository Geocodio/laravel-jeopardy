# Laravel Jeopardy Game Specification

## Overview
A web-based Jeopardy game built with Laravel, Livewire, Tailwind CSS 4, and Alpine.js for Laravel Live Denmark. The game will be played live on stage with 5 teams competing in a 20-25 minute session.

## Game Format

### Teams
- 5 teams, each with 2 members
- Laravel-themed team names (configured in config/jeopardy.php):
  1. **Team Illuminate** (Blue - #3B82F6)
  2. **Team Facade** (Green - #10B981)
  3. **Team Eloquent** (Yellow - #EAB308)
  4. **Team Blade** (White - #FFFFFF)
  5. **Team Artisan** (Red - #EF4444)

### Main Game (20 minutes)
- 5-6 Laravel-themed categories
- 3-4 clues per category
- Values: $100, $200, $300, $400
- Teams alternate picking categories/values
- Answers must be in question format ("What is...?")
- Correct answers add points, wrong answers subtract points
- 1 Daily Double hidden randomly on the board
- 15-30 second time limit per clue

### Lightning Round (5 minutes)
- 3-5 rapid-fire Laravel questions
- First team to buzz in can answer
- $200 per correct answer
- No penalty for wrong answers
- Highest total score wins

## Technical Architecture

### Database Schema

```sql
games
- id
- status (enum: 'setup', 'main_game', 'lightning_round', 'finished')
- current_clue_id (nullable)
- daily_double_used (boolean, default: false)
- created_at
- updated_at

teams
- id
- name (varchar)
- color_hex (varchar)
- score (integer, default: 0)
- game_id
- buzzer_pin (integer)
- created_at
- updated_at

categories
- id
- name (varchar)
- position (integer, 1-6)
- game_id
- created_at

clues
- id
- category_id
- question_text (text)
- answer_text (text)
- value (integer: 100, 200, 300, 400)
- is_daily_double (boolean, default: false)
- is_revealed (boolean, default: false)
- is_answered (boolean, default: false)
- answered_by_team_id (nullable)
- created_at
- updated_at

buzzer_events
- id
- team_id
- clue_id (nullable)
- lightning_question_id (nullable)
- buzzed_at (timestamp)
- is_first (boolean)
- created_at

lightning_questions
- id
- game_id
- question_text (text)
- answer_text (text)
- order_position (integer)
- is_current (boolean, default: false)
- is_answered (boolean, default: false)
- answered_by_team_id (nullable)
- created_at
- updated_at

game_logs
- id
- game_id
- action (varchar)
- details (json)
- created_at
```

### Livewire Components

#### 1. GameBoard Component
**File:** `app/Http/Livewire/GameBoard.php`
**View:** `resources/views/livewire/game-board.blade.php`

Responsibilities:
- Display 6x4 grid of categories and values
- Handle clue selection
- Manage game state transitions
- Control game flow
- Show/hide clue display modal

Properties:
- `$game` - Current game instance
- `$categories` - Collection of categories with clues
- `$selectedClue` - Currently selected clue
- `$showClueModal` - Boolean for modal visibility

Methods:
- `selectClue($clueId)`
- `returnToBoard()`
- `startLightningRound()`
- `endGame()`

#### 2. ClueDisplay Component
**File:** `app/Http/Livewire/ClueDisplay.php`
**View:** `resources/views/livewire/clue-display.blade.php`

Responsibilities:
- Display current clue in full-screen modal
- Show countdown timer
- Display buzzer status
- Provide host controls for scoring

Properties:
- `$clue` - Current clue object
- `$timeRemaining` - Seconds left on timer
- `$buzzerTeam` - Team that buzzed in
- `$isDailyDouble` - Boolean for special handling
- `$wagerAmount` - For Daily Double

Methods:
- `startTimer()`
- `handleBuzzer($teamId)`
- `markCorrect()`
- `markIncorrect()`
- `skipClue()`
- `setWager($amount)`

#### 3. TeamScoreboard Component
**File:** `app/Http/Livewire/TeamScoreboard.php`
**View:** `resources/views/livewire/team-scoreboard.blade.php`

Responsibilities:
- Display all team scores
- Show buzzer status indicators
- Animate score changes
- Highlight active team

Properties:
- `$teams` - Collection of team objects
- `$activeTeamId` - Currently answering team

Methods:
- `refreshScores()`
- `highlightTeam($teamId)`

#### 4. LightningRound Component
**File:** `app/Http/Livewire/LightningRound.php`
**View:** `resources/views/livewire/lightning-round.blade.php`

Responsibilities:
- Display rapid-fire questions
- Handle multiple buzzer inputs
- Track lightning round scoring
- No penalty system

Properties:
- `$currentQuestion` - Active lightning question
- `$questionsRemaining` - Count of remaining questions
- `$buzzerOrder` - Array of team IDs in buzz order

Methods:
- `nextQuestion()`
- `handleLightningBuzzer($teamId)`
- `markLightningCorrect()`
- `markLightningIncorrect()`

#### 5. BuzzerListener Component
**File:** `app/Http/Livewire/BuzzerListener.php`

Responsibilities:
- Listen for WebSocket buzzer events
- Determine first buzzer
- Manage buzzer lockouts
- Trigger sound effects

Properties:
- `$isListening` - Boolean for active listening
- `$lockedOut` - Array of locked out team IDs

Methods:
- `enableBuzzers()`
- `disableBuzzers()`
- `resetBuzzers()`
- `processBuzz($teamId, $timestamp)`

### Service Classes

#### GameService
**File:** `app/Services/GameService.php`

Methods:
- `createGame()`
- `setupTeams()`
- `generateBoard($gameId)`
- `placeDailyDouble($gameId)`
- `transitionToLightningRound($gameId)`
- `calculateFinalScores($gameId)`
- `saveGameState($gameId)`
- `restoreGame($gameId)`

#### BuzzerService
**File:** `app/Services/BuzzerService.php`

Methods:
- `registerBuzz($teamId, $pin, $timestamp)`
- `determineFirstBuzzer($clueId)`
- `lockoutTeam($teamId, $duration)`
- `resetAllBuzzers()`
- `testBuzzer($pin)`

#### ScoringService
**File:** `app/Services/ScoringService.php`

Methods:
- `awardPoints($teamId, $amount)`
- `deductPoints($teamId, $amount)`
- `handleDailyDouble($teamId, $wager, $correct)`
- `getLeaderboard()`
- `recordAnswer($clueId, $teamId, $correct)`

### API Endpoints

#### Buzzer Webhook
**Route:** `POST /api/buzzer`
**Controller:** `app/Http/Controllers/Api/BuzzerController.php`

Request:
```json
{
  "team_id": 1,
  "timestamp": "2024-01-15 10:30:45.123"
}
```

Response:
```json
{
  "success": true,
  "is_first": true,
  "team": "Team Eloquent"
}
```

### Broadcasting Events

**Channel:** `presence-game.{gameId}`

Events:
- `ClueRevealed` - When a clue is selected
- `BuzzerPressed` - When a team buzzes in
- `ScoreUpdated` - After scoring changes
- `TimerExpired` - When time runs out
- `GameStateChanged` - Game phase transitions
- `DailyDoubleTriggered` - Special event for Daily Double

### UI/UX Specifications

#### Color Scheme
- Background: Jeopardy blue gradient (#060CE9 to #0A0F3D)
- Text: White (#FFFFFF)
- Values: Gold (#FFD700)
- Categories: Dark blue (#1E3A8A)
- Answered clues: Dark gray (#374151)

#### Layout Structure
```
┌──────────────────────────────────────────────────┐
│                LARAVEL JEOPARDY                  │
│                                                   │
├─────────┬─────────┬─────────┬─────────┬─────────┬─────────┐
│ LARAVEL │ ELOQUENT│  BLADE  │ ARTISAN │ PACKAGES│ HISTORY │
│  BASICS │   ORM   │TEMPLATES│ COMMANDS│   DEV   │         │
├─────────┼─────────┼─────────┼─────────┼─────────┼─────────┤
│  $100   │  $100   │  $100   │  $100   │  $100   │  $100   │
├─────────┼─────────┼─────────┼─────────┼─────────┼─────────┤
│  $200   │  $200   │  $200   │  $200   │  $200   │  $200   │
├─────────┼─────────┼─────────┼─────────┼─────────┼─────────┤
│  $300   │  $300   │  $300   │  $300   │  $300   │  $300   │
├─────────┼─────────┼─────────┼─────────┼─────────┼─────────┤
│  $400   │  $400   │  $400   │  $400   │  $400   │  $400   │
└─────────┴─────────┴─────────┴─────────┴─────────┴─────────┘
│                                                   │
│  [Illuminate: $800] [Facade: $600] [Eloquent: $1200] │
│  [Blade: $400]    [Artisan: $1000]                 │
└──────────────────────────────────────────────────┘
```

#### Typography
- Categories: Bold, 24px, uppercase
- Values: Bold, 48px
- Clue text: Regular, 64px (full screen)
- Scores: Bold, 32px

#### Animations
- Clue reveal: Zoom in effect (0.3s)
- Score changes: Pulse animation
- Buzzer press: Team color flash
- Daily Double: Spin and glow effect

### Host Controls

#### Keyboard Shortcuts
- `Space` - Start/Stop timer
- `C` - Mark answer correct
- `X` - Mark answer incorrect
- `N` - Next question (lightning round)
- `R` - Reset buzzers
- `Esc` - Return to game board
- `D` - Trigger Daily Double (testing)
- `+/-` - Manual score adjustment

#### Mouse Controls
- Click on clue to reveal
- Click host buttons for scoring
- Drag to adjust wager (Daily Double)

### Sound Effects

Files stored in `public/sounds/`:
- `daily-double.mp3` - Play when Daily Double is revealed
- `times-up.mp3` - Play when timer expires
- `buzzer.mp3` - Play when team buzzes in (optional)
- `correct.mp3` - Play for correct answer (optional)
- `incorrect.mp3` - Play for wrong answer (optional)

### Game Flow State Machine

```
SETUP
  ↓
MAIN_GAME
  ↓
  ├─→ SELECT_CLUE
  │     ↓
  │   REVEAL_CLUE
  │     ↓
  │   [Is Daily Double?] → DAILY_DOUBLE_WAGER
  │     ↓                         ↓
  │   START_TIMER ←───────────────┘
  │     ↓
  │   ACCEPT_BUZZER
  │     ↓
  │   JUDGE_ANSWER
  │     ↓
  │   UPDATE_SCORE
  │     ↓
  └─← RETURN_TO_BOARD
  ↓
LIGHTNING_ROUND
  ↓
  ├─→ SHOW_QUESTION
  │     ↓
  │   ACCEPT_BUZZERS
  │     ↓
  │   JUDGE_ANSWER
  │     ↓
  │   UPDATE_SCORE
  │     ↓
  └─← NEXT_QUESTION
  ↓
GAME_FINISHED
```

### Performance Optimizations

1. **Eager Loading**
   - Load all categories and clues at game start
   - Preload team data with scores

2. **Caching**
   - Cache static assets (sounds, images)
   - Store game state in Redis for quick access

3. **Debouncing**
   - Debounce buzzer inputs (100ms)
   - Throttle score updates (500ms)

4. **Frontend Optimizations**
   - Use Alpine.js for micro-interactions
   - Minimize Livewire round trips
   - Lazy load sound files

### Recovery & Resilience

1. **Auto-save**
   - Save game state after every action
   - Store in database and cache

2. **Recovery Options**
   - Resume from any point in game
   - Manual score adjustment
   - Reset current clue
   - Skip problematic clues

3. **Buzzer Testing**
   - Pre-game buzzer test mode
   - Visual and audio confirmation
   - Pin mapping verification

### Directory Structure

```
laravel-jeopardy/
├── app/
│   ├── Events/
│   │   ├── BuzzerPressed.php
│   │   ├── ClueRevealed.php
│   │   ├── DailyDoubleTriggered.php
│   │   ├── GameStateChanged.php
│   │   ├── ScoreUpdated.php
│   │   └── TimerExpired.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── BuzzerController.php
│   │   └── Livewire/
│   │       ├── BuzzerListener.php
│   │       ├── ClueDisplay.php
│   │       ├── GameBoard.php
│   │       ├── LightningRound.php
│   │       └── TeamScoreboard.php
│   ├── Models/
│   │   ├── BuzzerEvent.php
│   │   ├── Category.php
│   │   ├── Clue.php
│   │   ├── Game.php
│   │   ├── GameLog.php
│   │   ├── LightningQuestion.php
│   │   └── Team.php
│   └── Services/
│       ├── BuzzerService.php
│       ├── GameService.php
│       └── ScoringService.php
├── database/
│   ├── migrations/
│   │   ├── create_games_table.php
│   │   ├── create_teams_table.php
│   │   ├── create_categories_table.php
│   │   ├── create_clues_table.php
│   │   ├── create_buzzer_events_table.php
│   │   ├── create_lightning_questions_table.php
│   │   └── create_game_logs_table.php
│   └── seeders/
│       ├── CategorySeeder.php
│       ├── ClueSeeder.php
│       └── LightningQuestionSeeder.php
├── public/
│   └── sounds/
│       ├── buzzer.mp3
│       ├── correct.mp3
│       ├── daily-double.mp3
│       ├── incorrect.mp3
│       └── times-up.mp3
├── resources/
│   ├── css/
│   │   └── jeopardy.css
│   ├── js/
│   │   ├── buzzer-handler.js
│   │   └── game-timer.js
│   └── views/
│       ├── layouts/
│       │   └── game.blade.php
│       └── livewire/
│           ├── clue-display.blade.php
│           ├── game-board.blade.php
│           ├── lightning-round.blade.php
│           └── team-scoreboard.blade.php
└── routes/
    ├── api.php
    └── web.php
```

### Sample Categories and Clues

#### Category 1: Laravel Basics
- $100: This artisan command creates a new Laravel project
- $200: This file contains all of your application's routes
- $300: This configuration file stores database connection details
- $400: This directory contains all of your Blade template files

#### Category 2: Eloquent ORM
- $100: This method retrieves all records from a database table
- $200: This relationship type represents a one-to-many connection
- $300: This feature automatically manages created_at and updated_at
- $400: This method creates query constraints that are always applied

#### Category 3: Blade Templates
- $100: This directive is used to display escaped data
- $200: This directive includes another Blade view
- $300: This directive defines a section that can be overridden
- $400: This feature compiles Blade components into PHP classes

#### Category 4: Artisan Commands
- $100: This command displays all available routes
- $200: This command creates a new database migration
- $300: This command clears all cached configuration
- $400: This command runs your application's database seeders

#### Category 5: Package Development
- $100: This file defines a package's dependencies and metadata
- $200: This class registers package services with Laravel
- $300: This command publishes package configuration files
- $400: This method registers package views with Laravel

#### Category 6: Laravel History
- $100: This person created the Laravel framework
- $200: This year Laravel was first released
- $300: This was Laravel's original codename
- $400: This version introduced Laravel Sanctum

### Lightning Round Questions
1. What method adds a where clause to an Eloquent query?
2. What Blade directive creates a CSRF token field?
3. What artisan command rolls back the last migration?
4. What facade provides access to the cache?
5. What middleware verifies CSRF tokens?

### Implementation Timeline

1. **Phase 1: Setup (2 hours)**
   - Initialize Laravel project
   - Install Livewire, Tailwind CSS 4, Alpine.js
   - Setup database and migrations
   - Configure broadcasting

2. **Phase 2: Core Components (4 hours)**
   - Build GameBoard component
   - Build ClueDisplay component
   - Build TeamScoreboard component
   - Implement basic game flow

3. **Phase 3: Buzzer Integration (3 hours)**
   - Create buzzer API endpoint
   - Implement BuzzerListener component
   - Add WebSocket broadcasting
   - Test with simulated buzzers

4. **Phase 4: Game Features (3 hours)**
   - Add Daily Double functionality
   - Implement Lightning Round
   - Add timer system
   - Add sound effects

5. **Phase 5: Polish (2 hours)**
   - Style with Jeopardy theme
   - Add animations
   - Implement host controls
   - Add recovery features

6. **Phase 6: Testing (2 hours)**
   - Test buzzer integration
   - Full game simulation
   - Performance optimization
   - Bug fixes

Total estimated time: 16 hours

### Testing Checklist

- [ ] All 5 buzzers register correctly
- [ ] First buzzer detection works
- [ ] Scores update correctly
- [ ] Daily Double appears randomly
- [ ] Timer counts down properly
- [ ] Sound effects play at right times
- [ ] Lightning round transitions smoothly
- [ ] Game state persists through refresh
- [ ] Host controls work as expected
- [ ] UI displays correctly on projector
- [ ] Recovery from disconnection works
- [ ] Manual score adjustment works
- [ ] All animations perform smoothly
- [ ] Keyboard shortcuts function properly

### Deployment Considerations

1. **Environment Setup**
   - Ensure Pusher/Soketi credentials are configured
   - Set up Redis for caching
   - Configure session driver for persistence
   - Test audio output on venue system

2. **Pre-show Setup**
   - Run buzzer test with all teams
   - Verify projector resolution
   - Test sound system volume
   - Create backup game instance
   - Brief host on controls

### Contingency Plans

1. **Buzzer Failure**
   - Manual buzzer mode (host picks team)
   - Verbal buzzing with host judgment
   - Pre-assigned answer order

2. **Display Issues**
   - Backup laptop with mirrored display
   - PDF printout of clues
   - Manual game board on whiteboard

3. **Network Issues**
   - Local hosting without internet
   - SQLite database instead of MySQL
   - Disable real-time features, use polling

4. **Software Crash**
   - Resume from last saved state
   - Switch to backup instance
   - Continue with manual scoring
