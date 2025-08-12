<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuzzerEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'team_id',
        'clue_id',
        'lightning_question_id',
        'buzzed_at',
        'is_first',
    ];

    protected $casts = [
        'buzzed_at' => 'datetime',
        'is_first' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function clue()
    {
        return $this->belongsTo(Clue::class);
    }

    public function lightningQuestion()
    {
        return $this->belongsTo(LightningQuestion::class);
    }
}
