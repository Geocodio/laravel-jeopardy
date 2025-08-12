<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'position',
        'game_id',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function clues()
    {
        return $this->hasMany(Clue::class);
    }
}
