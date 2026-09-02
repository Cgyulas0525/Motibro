<?php

namespace App\Http\Controllers;

use App\Services\MotibroBookingService;
use Illuminate\Http\RedirectResponse;

class BookingRunTriggerController extends Controller
{
    public function __invoke(MotibroBookingService $service): RedirectResponse
    {
        try {
            $run = $service->run(manual: true);

            return redirect()
                ->route('booking-runs.show', $run)
                ->with('success', "Foglalás lefutott (#{$run->id}): {$run->summary}");
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
