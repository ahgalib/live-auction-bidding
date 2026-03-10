<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auction extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'starting_price',
        'current_price',
        'current_winner_id',
        'end_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starting_price' => 'decimal:2',
            'current_price' => 'decimal:2',
            'end_time' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function currentWinner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_winner_id');
    }

    public function bidLogs(): HasMany
    {
        return $this->hasMany(BidLog::class);
    }
}
