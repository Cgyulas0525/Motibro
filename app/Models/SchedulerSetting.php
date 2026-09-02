<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulerSetting extends Model
{
    protected $fillable = [
        'enabled',
        'window_start',
        'window_end',
        'interval_minutes',
        'timezone',
        'last_scheduled_run_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'interval_minutes' => 'integer',
            'last_scheduled_run_at' => 'datetime',
        ];
    }

    public static function instance(): self
    {
        return static::firstOrFail();
    }

    public function getWindowStartFormattedAttribute(): string
    {
        return substr((string) $this->window_start, 0, 5);
    }

    public function getWindowEndFormattedAttribute(): string
    {
        return substr((string) $this->window_end, 0, 5);
    }
}
