<?php

namespace App\Http\Controllers;

use App\Models\BookingRule;
use App\Models\BookingRun;
use App\Services\MotibroBookingService;
use Inertia\Inertia;

class BookingRunController extends Controller
{
    public function index(MotibroBookingService $bookingService)
    {
        $runs = BookingRun::query()
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (BookingRun $run) => [
                'id' => $run->id,
                'trigger' => $run->trigger,
                'status' => $run->status,
                'started_at' => $run->started_at?->timezone('Europe/Budapest')->format('Y.m.d H:i:s'),
                'finished_at' => $run->finished_at?->timezone('Europe/Budapest')->format('Y.m.d H:i:s'),
                'summary' => $run->summary,
                'exit_code' => $run->exit_code,
            ]);

        return Inertia::render('BookingRuns/Index', [
            'bookingRuns' => $runs,
            'booking' => [
                'is_running' => $bookingService->isRunning(),
                'can_start' => ! $bookingService->isRunning()
                    && BookingRule::query()->where('enabled', true)->exists(),
            ],
        ]);
    }

    public function show(BookingRun $bookingRun)
    {
        $bookingRun->load(['attempts.rule']);

        return Inertia::render('BookingRuns/Show', [
            'bookingRun' => [
                'id' => $bookingRun->id,
                'trigger' => $bookingRun->trigger,
                'status' => $bookingRun->status,
                'started_at' => $bookingRun->started_at?->timezone('Europe/Budapest')->format('Y.m.d H:i:s'),
                'finished_at' => $bookingRun->finished_at?->timezone('Europe/Budapest')->format('Y.m.d H:i:s'),
                'summary' => $bookingRun->summary,
                'exit_code' => $bookingRun->exit_code,
                'raw_output' => $bookingRun->raw_output,
            ],
            'attempts' => $bookingRun->attempts->map(fn ($attempt) => [
                'id' => $attempt->id,
                'slot_starts_at' => $attempt->slot_starts_at?->timezone('Europe/Budapest')->format('Y.m.d H:i'),
                'action' => $attempt->action,
                'message' => $attempt->message,
                'rule_label' => $attempt->rule?->label ?? $attempt->rule?->weekday_label,
            ]),
        ]);
    }

    public function destroy(BookingRun $bookingRun)
    {
        $bookingRun->delete();

        return redirect()->route('booking-runs.index')->with('success', 'Futás törölve.');
    }
}
