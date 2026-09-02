<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRuleRequest;
use App\Http\Requests\UpdateBookingRuleRequest;
use App\Models\BookingRule;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BookingRuleController extends Controller
{
    public function index()
    {
        $rules = BookingRule::query()
            ->orderBy('sort_order')
            ->orderBy('weekday')
            ->orderBy('time')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (BookingRule $rule) => [
                'id' => $rule->id,
                'label' => $rule->label,
                'weekday' => $rule->weekday,
                'weekday_label' => $rule->weekday_label,
                'time' => $rule->time_formatted,
                'enabled' => $rule->enabled,
                'waitlist_ok' => $rule->waitlist_ok,
                'sort_order' => $rule->sort_order,
            ]);

        return Inertia::render('BookingRules/Index', [
            'bookingRules' => $rules,
        ]);
    }

    public function create()
    {
        return Inertia::render('BookingRules/Create', [
            'weekdays' => $this->weekdayOptions(),
        ]);
    }

    public function store(StoreBookingRuleRequest $request)
    {
        DB::transaction(fn () => BookingRule::create([
            ...$request->validated(),
            'enabled' => $request->boolean('enabled', true),
            'waitlist_ok' => $request->boolean('waitlist_ok', false),
            'sort_order' => $request->input('sort_order', 0),
        ]));

        return redirect()->route('booking-rules.index')->with('success', 'Időpont létrehozva.');
    }

    public function edit(BookingRule $bookingRule)
    {
        return Inertia::render('BookingRules/Edit', [
            'bookingRule' => [
                'id' => $bookingRule->id,
                'label' => $bookingRule->label,
                'weekday' => $bookingRule->weekday,
                'time' => $bookingRule->time_formatted,
                'enabled' => $bookingRule->enabled,
                'waitlist_ok' => $bookingRule->waitlist_ok,
                'sort_order' => $bookingRule->sort_order,
            ],
            'weekdays' => $this->weekdayOptions(),
        ]);
    }

    public function update(UpdateBookingRuleRequest $request, BookingRule $bookingRule)
    {
        DB::transaction(fn () => $bookingRule->update([
            ...$request->validated(),
            'enabled' => $request->boolean('enabled'),
            'waitlist_ok' => $request->boolean('waitlist_ok'),
            'sort_order' => $request->input('sort_order', 0),
        ]));

        return redirect()->route('booking-rules.index')->with('success', 'Időpont frissítve.');
    }

    public function destroy(BookingRule $bookingRule)
    {
        if ($bookingRule->attempts()->exists()) {
            return redirect()->route('booking-rules.index')->with('error', 'Nem törölhető: már volt futás ezzel a szabállyal.');
        }

        $bookingRule->delete();

        return redirect()->route('booking-rules.index')->with('success', 'Időpont törölve.');
    }

    private function weekdayOptions(): array
    {
        return collect(BookingRule::WEEKDAY_LABELS)
            ->map(fn (string $label, int $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
