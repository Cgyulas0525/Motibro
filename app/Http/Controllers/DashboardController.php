<?php

namespace App\Http\Controllers;

use App\Models\BookingRule;
use App\Models\BookingRun;
use App\Models\SchedulerSetting;
use App\Services\MotibroBookingService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(MotibroBookingService $bookingService): Response
    {
        $scheduler = SchedulerSetting::query()->find(1);

        $bookingRules = BookingRule::query()
            ->orderBy('sort_order')
            ->orderBy('weekday')
            ->orderBy('time')
            ->get()
            ->map(fn (BookingRule $rule) => [
                'id' => $rule->id,
                'label' => $rule->label,
                'weekday' => $rule->weekday,
                'weekday_label' => $rule->weekday_label,
                'time' => $rule->time_formatted,
                'enabled' => $rule->enabled,
                'waitlist_ok' => $rule->waitlist_ok,
                'sort_order' => $rule->sort_order,
            ]);

        $recentRuns = BookingRun::query()
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (BookingRun $run) => [
                'id' => $run->id,
                'trigger' => $run->trigger,
                'status' => $run->status,
                'started_at' => $run->started_at?->timezone('Europe/Budapest')->format('Y.m.d H:i'),
                'finished_at' => $run->finished_at?->timezone('Europe/Budapest')->format('Y.m.d H:i'),
                'summary' => $run->summary,
            ]);

        $activeRulesCount = $bookingRules->where('enabled', true)->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'active_rules' => $activeRulesCount,
                'total_rules' => $bookingRules->count(),
                'scheduler_enabled' => $scheduler?->enabled ?? false,
                'window_start' => $scheduler?->window_start_formatted ?? '—',
                'window_end' => $scheduler?->window_end_formatted ?? '—',
                'interval_minutes' => $scheduler?->interval_minutes ?? '—',
                'timezone' => $scheduler?->timezone ?? '—',
                'last_scheduled_run_at' => $scheduler?->last_scheduled_run_at
                    ?->timezone('Europe/Budapest')
                    ->format('Y.m.d H:i'),
            ],
            'bookingRules' => $bookingRules->values(),
            'recentRuns' => $recentRuns->values(),
            'booking' => [
                'is_running' => $bookingService->isRunning(),
                'can_start' => ! $bookingService->isRunning() && $activeRulesCount > 0,
            ],
        ]);
    }
}
