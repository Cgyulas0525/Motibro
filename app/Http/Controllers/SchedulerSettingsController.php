<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSchedulerSettingsRequest;
use App\Models\SchedulerSetting;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SchedulerSettingsController extends Controller
{
    public function edit()
    {
        $setting = SchedulerSetting::query()->findOrFail(1);

        return Inertia::render('Settings/Scheduler', [
            'scheduler' => [
                'enabled' => $setting->enabled,
                'window_start' => $setting->window_start_formatted,
                'window_end' => $setting->window_end_formatted,
                'interval_minutes' => $setting->interval_minutes,
                'timezone' => $setting->timezone,
                'last_scheduled_run_at' => $setting->last_scheduled_run_at
                    ?->timezone('Europe/Budapest')
                    ->format('Y.m.d H:i'),
            ],
            'timezones' => [
                ['value' => 'Europe/Budapest', 'label' => 'Europe/Budapest'],
            ],
        ]);
    }

    public function update(UpdateSchedulerSettingsRequest $request)
    {
        $setting = SchedulerSetting::query()->findOrFail(1);

        DB::transaction(fn () => $setting->update($request->validated()));

        return redirect()->route('settings.scheduler.edit')->with('success', 'Ütemezés mentve.');
    }
}
