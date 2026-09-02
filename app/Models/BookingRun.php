<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingRun extends Model
{
    protected $fillable = [
        'trigger',
        'status',
        'started_at',
        'finished_at',
        'summary',
        'exit_code',
        'raw_output',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'exit_code' => 'integer',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(BookingAttempt::class);
    }

    public function slotBookings(): HasMany
    {
        return $this->hasMany(BookingSlotBooking::class);
    }
}
