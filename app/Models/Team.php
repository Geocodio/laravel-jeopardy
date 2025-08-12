<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'color_hex',
        'score',
        'game_id',
        'buzzer_pin',
    ];

    protected $casts = [
        'score' => 'integer',
        'buzzer_pin' => 'integer',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function buzzerEvents()
    {
        return $this->hasMany(BuzzerEvent::class);
    }

    public function answeredClues()
    {
        return $this->hasMany(Clue::class, 'answered_by_team_id');
    }

    public function answeredLightningQuestions()
    {
        return $this->hasMany(LightningQuestion::class, 'answered_by_team_id');
    }
}
