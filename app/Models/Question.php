<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'test_id',
        'body',
        'position',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'score' => 'integer',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class)->orderBy('position');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
