<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BookingBlackout extends Model
{
    protected $fillable = [
        'starts_on',
        'ends_on',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function isSingleDay(): bool
    {
        return $this->starts_on->equalTo($this->ends_on);
    }

    public function getPeriodLabelAttribute(): string
    {
        $from = $this->starts_on->format('Y.m.d');

        if ($this->isSingleDay()) {
            return $from;
        }

        return $from.' – '.$this->ends_on->format('Y.m.d');
    }

    /**
     * @return array<int, array{starts_on: string, ends_on: string, note: string|null}>
     */
    public static function payloadRows(): array
    {
        return static::query()
            ->orderBy('starts_on')
            ->get(['starts_on', 'ends_on', 'note'])
            ->map(fn (self $row) => [
                'starts_on' => $row->starts_on->toDateString(),
                'ends_on' => $row->ends_on->toDateString(),
                'note' => $row->note,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $blackouts
     * @return array<string, mixed>|null
     */
    public static function matching(string $slotIso, array $blackouts): ?array
    {
        $date = Carbon::parse($slotIso)
            ->timezone(config('app.timezone', 'Europe/Budapest'))
            ->toDateString();

        foreach ($blackouts as $row) {
            $from = (string) ($row['starts_on'] ?? '');
            $to = (string) ($row['ends_on'] ?? $from);

            if ($from !== '' && $date >= $from && $date <= $to) {
                return $row;
            }
        }

        return null;
    }
}
