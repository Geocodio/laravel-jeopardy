# Laravel Jeopardy 🎮

A live, interactive Jeopardy game built with Laravel 12, Livewire, and Flux UI - designed for Laravel Live Denmark and other live events.

![Laravel](https://img.shields.io/badge/Laravel-v12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-v3-FB70A9?style=for-the-badge&logo=livewire&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-^8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)

## 🎯 Features

- **Real-time Game Board**: Interactive Jeopardy board with categories and clues
- **WebSocket Buzzer System**: Hardware buzzer integration via Raspberry Pi
- **Team Management**: Multiple teams with real-time score tracking
- **Special Features**:
  - Daily Double clues with wagering
  - Lightning Round for rapid-fire questions
  - Timer system with automatic timeouts
  - Sound effects for enhanced gameplay
- **Game State Recovery**: Persistent game state with full recovery capabilities
- **Admin Controls**: Host interface for managing gameplay

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
   - Laravel server on `http://localhost:8000`
   - Vite dev server for hot module replacement
   - Queue worker for background jobs
   - Pail for real-time log monitoring

## 🎮 Game Setup

### Creating a New Game

1. Navigate to the admin dashboard
2. Create teams and assign buzzers
3. Configure categories and clues
4. Start the game session

### Buzzer Integration

The game supports hardware buzzers via Raspberry Pi. Configure your buzzer endpoint:

```env
BUZZER_API_ENDPOINT=http://your-raspberry-pi:3000
```

The buzzer system sends POST requests to `/api/buzzer` with team identification.

## 🏗️ Architecture

### Tech Stack

- **Backend**: Laravel 12 with Livewire
- **UI Components**: Flux UI (Livewire component library)
- **Frontend**: Tailwind CSS 4, Alpine.js
- **Real-time**: Laravel Broadcasting with Pusher/Ably/Soketi
- **Database**: SQLite (default), MySQL/PostgreSQL supported
- **Testing**: Pest PHP
- **Asset Bundling**: Vite

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
└── tests/                 # Test files
```

### Key Components

- **GameBoard**: Main game controller and UI
- **ClueDisplay**: Modal for displaying questions
- **TeamScoreboard**: Real-time score tracking
- **BuzzerListener**: WebSocket event handler
- **LightningRound**: Speed round implementation

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
POST /api/buzzer
Content-Type: application/json

{
  "team_id": 1,
  "timestamp": "2024-01-01T12:00:00Z"
}
```

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
