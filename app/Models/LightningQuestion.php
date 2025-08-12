<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LightningQuestion extends Model
{
    protected $fillable = [
        'game_id',
        'question_text',
        'answer_text',
        'order_position',
        'is_current',
        'is_answered',
        'answered_by_team_id',
    ];

    protected $casts = [
        'order_position' => 'integer',
        'is_current' => 'boolean',
        'is_answered' => 'boolean',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function answeredByTeam()
    {
        return $this->belongsTo(Team::class, 'answered_by_team_id');
    }

    public function buzzerEvents()
    {
        return $this->hasMany(BuzzerEvent::class);
    }
}
