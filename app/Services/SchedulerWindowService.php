<?php

namespace App\Services;

use App\Models\SchedulerSetting;
use Carbon\Carbon;

class SchedulerWindowService
{
    public function shouldRunNow(?Carbon $now = null): bool
    {
        $settings = SchedulerSetting::instance();

        if (! $settings->enabled) {
            return false;
        }

        $tz = $settings->timezone ?? config('app.timezone', 'Europe/Budapest');
        $now = ($now ?? now())->timezone($tz);

        if (! $this->isWithinWindow($now, $settings)) {
            return false;
        }

        if ($settings->last_scheduled_run_at === null) {
            return true;
        }

        $nextAllowed = $settings->last_scheduled_run_at
            ->copy()
            ->timezone($tz)
            ->addMinutes($settings->interval_minutes);

        return $now->gte($nextAllowed);
    }

    public function nextRunAt(?Carbon $from = null): ?Carbon
    {
        $settings = SchedulerSetting::instance();

        if (! $settings->enabled) {
            return null;
        }

        $tz = $settings->timezone ?? config('app.timezone', 'Europe/Budapest');
        $from = ($from ?? now())->timezone($tz);

        if ($this->shouldRunNow($from)) {
            return $from->copy();
        }

        if ($this->isWithinWindow($from, $settings) && $settings->last_scheduled_run_at !== null) {
            $next = $settings->last_scheduled_run_at
                ->copy()
                ->timezone($tz)
                ->addMinutes($settings->interval_minutes);

            if ($next->gt($from) && $this->isWithinWindow($next, $settings)) {
                return $next;
            }
        }

        $cursor = $from->copy()->startOfDay();
        if ($this->isWithinWindow($from, $settings)) {
            $cursor = $from->copy()->addDay()->startOfDay();
        }

        for ($i = 0; $i < 8; $i++) {
            [$hour, $minute] = $this->parseTime($settings->window_start_formatted);
            $candidate = $cursor->copy()->setTime($hour, $minute, 0);

            if ($candidate->gt($from)) {
                return $candidate;
            }

            $cursor->addDay();
        }

        return null;
    }

    public function markScheduledRun(?Carbon $at = null): void
    {
        $settings = SchedulerSetting::instance();
        $tz = $settings->timezone ?? config('app.timezone', 'Europe/Budapest');

        $settings->update([
            'last_scheduled_run_at' => ($at ?? now())->timezone($tz),
        ]);
    }

    private function isWithinWindow(Carbon $now, SchedulerSetting $settings): bool
    {
        $current = $now->format('H:i');
        $start = $settings->window_start_formatted;
        $end = $settings->window_end_formatted;

        if ($start <= $end) {
            return $current >= $start && $current <= $end;
        }

        return $current >= $start || $current <= $end;
    }

    private function parseTime(string $time): array
    {
        [$hour, $minute] = array_pad(explode(':', substr($time, 0, 5)), 2, '0');

        return [(int) $hour, (int) $minute];
    }
}
