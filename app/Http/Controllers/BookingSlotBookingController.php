<?php

namespace App\Http\Controllers;

use App\Models\BookingSlotBooking;
use Inertia\Inertia;

class BookingSlotBookingController extends Controller
{
    public function index()
    {
        $bookings = BookingSlotBooking::query()
            ->with('rule')
            ->latest('slot_starts_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (BookingSlotBooking $booking) => [
                'id' => $booking->id,
                'slot_starts_at' => $booking->slot_starts_at?->timezone('Europe/Budapest')->format('Y.m.d H:i'),
                'status' => $booking->status,
                'booked_at' => $booking->booked_at?->timezone('Europe/Budapest')->format('Y.m.d H:i'),
                'rule_label' => $booking->rule?->label ?? $booking->rule?->weekday_label,
            ]);

        return Inertia::render('BookingSlotBookings/Index', [
            'slotBookings' => $bookings,
        ]);
    }

    public function destroy(BookingSlotBooking $bookingSlotBooking)
    {
        $bookingSlotBooking->delete();

        return redirect()->route('booking-slot-bookings.index')->with('success', 'Foglalás törölve.');
    }
}
