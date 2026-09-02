<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingRule extends Model
{
    public const WEEKDAY_LABELS = [
        1 => 'Hétfő',
        2 => 'Kedd',
        3 => 'Szerda',
        4 => 'Csütörtök',
        5 => 'Péntek',
        6 => 'Szombat',
        7 => 'Vasárnap',
    ];

    protected $fillable = [
        'label',
        'weekday',
        'time',
        'enabled',
        'waitlist_ok',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'enabled' => 'boolean',
            'waitlist_ok' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(BookingAttempt::class, 'rule_id');
    }

    public function slotBookings(): HasMany
    {
        return $this->hasMany(BookingSlotBooking::class, 'rule_id');
    }

    public function getWeekdayLabelAttribute(): string
    {
        return self::WEEKDAY_LABELS[$this->weekday] ?? (string) $this->weekday;
    }

    public function getTimeFormattedAttribute(): string
    {
        return substr((string) $this->time, 0, 5);
    }
}
