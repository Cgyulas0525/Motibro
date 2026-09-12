<?php

namespace Tests\Feature;

use App\Models\SchedulerSetting;
use App\Services\SchedulerWindowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerWindowLastRunTest extends TestCase
{
    use RefreshDatabase;

    private function settings(array $overrides = []): void
    {
        SchedulerSetting::query()->delete();

        SchedulerSetting::create(array_merge([
            'enabled' => true,
            'window_start' => '00:00:00',
            'window_end' => '02:00:00',
            'interval_minutes' => 10,
            'timezone' => 'Europe/Budapest',
        ], $overrides));
    }

    private function at(string $time): Carbon
    {
        return Carbon::parse("2026-09-14 {$time}", 'Europe/Budapest');
    }

    public function test_run_at_window_end_is_the_last_one(): void
    {
        $this->settings();

        $this->assertTrue(app(SchedulerWindowService::class)->isLastRunOfWindow($this->at('02:00')));
    }

    public function test_run_close_to_window_end_is_the_last_one(): void
    {
        $this->settings();

        // 01:55 + 10 perc = 02:05, ami már kiesik az ablakból.
        $this->assertTrue(app(SchedulerWindowService::class)->isLastRunOfWindow($this->at('01:55')));
    }

    public function test_earlier_run_is_not_the_last_one(): void
    {
        $this->settings();

        $this->assertFalse(app(SchedulerWindowService::class)->isLastRunOfWindow($this->at('01:40')));
    }

    public function test_run_outside_the_window_is_not_the_last_one(): void
    {
        $this->settings();

        $this->assertFalse(app(SchedulerWindowService::class)->isLastRunOfWindow($this->at('09:00')));
    }

    public function test_disabled_scheduler_never_notifies(): void
    {
        $this->settings(['enabled' => false]);

        $this->assertFalse(app(SchedulerWindowService::class)->isLastRunOfWindow($this->at('02:00')));
    }
}
