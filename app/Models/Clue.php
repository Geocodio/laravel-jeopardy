<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clue extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question_text',
        'answer_text',
        'value',
        'is_daily_double',
        'is_revealed',
        'is_answered',
        'answered_by_team_id',
    ];

    protected $casts = [
        'value' => 'integer',
        'is_daily_double' => 'boolean',
        'is_revealed' => 'boolean',
        'is_answered' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function answeredByTeam()
    {
        return $this->belongsTo(Team::class, 'answered_by_team_id');
    }

    public function buzzerEvents()
    {
        return $this->hasMany(BuzzerEvent::class);
    }

    public function gameAsCurrent()
    {
        return $this->hasOne(Game::class, 'current_clue_id');
    }
}
