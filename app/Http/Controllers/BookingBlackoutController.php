<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingBlackoutRequest;
use App\Http\Requests\UpdateBookingBlackoutRequest;
use App\Models\BookingBlackout;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BookingBlackoutController extends Controller
{
    public function index()
    {
        $blackouts = BookingBlackout::query()
            ->orderBy('starts_on')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (BookingBlackout $row) => [
                'id' => $row->id,
                'period' => $row->period_label,
                'starts_on' => $row->starts_on->toDateString(),
                'ends_on' => $row->ends_on->toDateString(),
                'note' => $row->note,
            ]);

        return Inertia::render('BookingBlackouts/Index', [
            'blackouts' => $blackouts,
        ]);
    }

    public function create()
    {
        return Inertia::render('BookingBlackouts/Create');
    }

    public function store(StoreBookingBlackoutRequest $request)
    {
        DB::transaction(fn () => BookingBlackout::create($request->validated()));

        return redirect()->route('booking-blackouts.index')->with('success', 'Szabadság időszak létrehozva.');
    }

    public function edit(BookingBlackout $bookingBlackout)
    {
        return Inertia::render('BookingBlackouts/Edit', [
            'blackout' => [
                'id' => $bookingBlackout->id,
                'starts_on' => $bookingBlackout->starts_on->toDateString(),
                'ends_on' => $bookingBlackout->ends_on->toDateString(),
                'note' => $bookingBlackout->note ?? '',
            ],
        ]);
    }

    public function update(UpdateBookingBlackoutRequest $request, BookingBlackout $bookingBlackout)
    {
        DB::transaction(fn () => $bookingBlackout->update($request->validated()));

        return redirect()->route('booking-blackouts.index')->with('success', 'Szabadság időszak frissítve.');
    }

    public function destroy(BookingBlackout $bookingBlackout)
    {
        $bookingBlackout->delete();

        return redirect()->route('booking-blackouts.index')->with('success', 'Szabadság időszak törölve.');
    }
}
