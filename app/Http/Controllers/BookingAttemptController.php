<?php

namespace App\Http\Controllers;

use App\Models\BookingAttempt;
use Inertia\Inertia;

class BookingAttemptController extends Controller
{
    public function index()
    {
        $attempts = BookingAttempt::query()
            ->with(['rule', 'run'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (BookingAttempt $attempt) => [
                'id' => $attempt->id,
                'booking_run_id' => $attempt->booking_run_id,
                'slot_starts_at' => $attempt->slot_starts_at?->timezone('Europe/Budapest')->format('Y.m.d H:i'),
                'action' => $attempt->action,
                'message' => $attempt->message,
                'rule_label' => $attempt->rule?->label ?? $attempt->rule?->weekday_label,
                'run_status' => $attempt->run?->status,
            ]);

        return Inertia::render('BookingAttempts/Index', [
            'bookingAttempts' => $attempts,
        ]);
    }

    public function destroy(BookingAttempt $bookingAttempt)
    {
        $bookingAttempt->delete();

        return redirect()->route('booking-attempts.index')->with('success', 'Kísérlet törölve.');
    }
}
