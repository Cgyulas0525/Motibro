<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAttempt extends Model
{
    protected $fillable = [
        'booking_run_id',
        'rule_id',
        'slot_starts_at',
        'action',
        'message',
        'screenshot_path',
    ];

    protected function casts(): array
    {
        return [
            'slot_starts_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(BookingRun::class, 'booking_run_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BookingRule::class, 'rule_id');
    }
}
