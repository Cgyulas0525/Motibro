<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSlotBooking extends Model
{
    protected $fillable = [
        'slot_starts_at',
        'rule_id',
        'booking_run_id',
        'status',
        'booked_at',
    ];

    protected function casts(): array
    {
        return [
            'slot_starts_at' => 'datetime',
            'booked_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BookingRule::class, 'rule_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(BookingRun::class, 'booking_run_id');
    }
}
