# Laravel Jeopardy 🎮

A live, interactive Jeopardy game built with Laravel 12, Livewire 3 with Flux UI, and Tailwind CSS 4 - designed for Laravel Live Denmark and other live events.

![Laravel](https://img.shields.io/badge/Laravel-v12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-v3-FB70A9?style=for-the-badge&logo=livewire&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-^8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)

## 🎯 Features

- **Real-time Game Board**: Interactive Jeopardy board with categories and clues
- **WebSocket Buzzer System**: Hardware buzzer integration via Raspberry Pi (using Pinout package)
- **Team Management**: Multiple teams with real-time score tracking
- **Special Features**:
  - Daily Double clues with wagering
  - Lightning Round for rapid-fire questions
  - Timer system with automatic timeouts
  - Sound effects for enhanced gameplay (correct/incorrect answers, countdown timer)
- **Game State Recovery**: Persistent game state with full recovery capabilities
- **Admin Controls**: Host interface for managing gameplay
- **Real-time Broadcasting**: Laravel Reverb for WebSocket connections
- **Volunteer Picker**: Random volunteer selection from attendee list

## 🚀 Quick Start

### Prerequisites

- PHP 8.2 or higher
- Node.js 18+ and npm
- Composer
- SQLite (or MySQL/PostgreSQL)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/geocodio/laravel-jeopardy.git
   cd laravel-jeopardy
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

6. **Start development server**
   ```bash
   composer run dev
   ```

   This command starts:
   - Laravel server on `http://0.0.0.0:8000`
   - Vite dev server for hot module replacement
   - Queue worker for background jobs
   - Pail for real-time log monitoring
   - Reverb WebSocket server for real-time events

## 🎮 Game Setup

### Creating a New Game

1. Navigate to the admin dashboard
2. Create teams and assign buzzers
3. Configure categories and clues
4. Start the game session

### Buzzer Integration

The game supports hardware buzzers via Raspberry Pi using the Pinout package.

To set up the buzzer system:
1. Connect buzzers to GPIO pins (see `app/Console/Commands/BuzzerServer.php` for pin configuration)
2. Run the buzzer server: `php artisan buzzer-server`
3. The buzzer server sends events to `/api/buzzer` when buttons are pressed

## 🏗️ Architecture

### Tech Stack

- **Backend**: Laravel 12 with Livewire 3
- **UI Components**: Flux UI 2 (Livewire component library)
- **Frontend**: Tailwind CSS 4, Alpine.js (included with Livewire)
- **Real-time**: Laravel Reverb for WebSocket connections
- **Database**: SQLite (default), MySQL/PostgreSQL supported
- **Testing**: Pest PHP 3
- **Asset Bundling**: Vite 7
- **Hardware Integration**: Pinout package for GPIO control

### Project Structure

```
laravel-jeopardy/
├── app/
│   ├── Livewire/          # Livewire components
│   ├── Models/            # Eloquent models
│   ├── Services/          # Business logic services
│   └── Events/            # Broadcasting events
├── resources/
│   ├── views/
│   │   ├── livewire/      # Livewire component views
│   │   └── flux/          # Custom Flux UI components
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── database/
│   ├── migrations/        # Database migrations
│   └── seeders/           # Database seeders
├── public/
│   └── sounds/            # Game sound effects
│       └── buzzer/        # Team-specific buzzer sounds
└── tests/                 # Test files
```

### Key Components

- **GameBoard**: Main game controller and UI
- **ClueDisplay**: Modal for displaying questions
- **TeamScoreboard**: Real-time score tracking
- **BuzzerListener**: WebSocket event handler
- **LightningRound**: Speed round implementation
- **HostControl**: Admin interface for game management
- **Leaderboard**: Display team standings
- **VolunteerPicker**: Random attendee selection

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
composer test

# Run specific test
php artisan test tests/Feature/GameBoardTest.php

# With coverage
php artisan test --coverage
```

## 🛠️ Development

### Code Style

Format code with Laravel Pint:

```bash
./vendor/bin/pint
```

### Available Commands

```bash
# Development
composer run dev          # Start all dev services
php artisan serve         # Laravel server only
npm run dev              # Vite dev server only
php artisan queue:listen # Queue worker
php artisan pail         # Real-time logs

# Building
npm run build            # Build frontend assets
php artisan optimize     # Cache for production

# Database
php artisan migrate      # Run migrations
php artisan migrate:fresh --seed  # Fresh install with data
```

## 🔌 API Endpoints

### Buzzer API

```http
GET /api/buzzer
Query Parameters:
- pin_id: The GPIO pin index of the pressed buzzer
```

Receives buzzer events from the hardware buzzer server running on the Raspberry Pi.

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Acknowledgments

- Built for Laravel Live Denmark
- Inspired by the classic Jeopardy game show

---

**Made with ❤️ by Laravel Denmark**
