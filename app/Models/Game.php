<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;
    protected $fillable = [
        'status',
        'current_clue_id',
        'daily_double_used',
    ];

    protected $casts = [
        'daily_double_used' => 'boolean',
    ];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function currentClue()
    {
        return $this->belongsTo(Clue::class, 'current_clue_id');
    }

    public function lightningQuestions()
    {
        return $this->hasMany(LightningQuestion::class);
    }

    public function gameLogs()
    {
        return $this->hasMany(GameLog::class);
    }
}
