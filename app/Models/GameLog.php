<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameLog extends Model
{
    protected $fillable = [
        'game_id',
        'action',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
