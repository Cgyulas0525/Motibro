<?php

namespace Tests\Feature;

use App\Models\SchedulerSetting;
use App\Services\SchedulerWindowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerNotificationDueTest extends TestCase
{
    use RefreshDatabase;

    private function settings(array $overrides = []): SchedulerSetting
    {
        SchedulerSetting::query()->delete();

        return SchedulerSetting::create(array_merge([
            'enabled' => true,
            'window_start' => '00:00:00',
            'window_end' => '02:00:00',
            'interval_minutes' => 10,
            'timezone' => 'Europe/Budapest',
            'last_scheduled_run_at' => $this->at('01:45'),
        ], $overrides));
    }

    private function at(string $time): Carbon
    {
        return Carbon::parse("2026-09-13 {$time}", 'Europe/Budapest');
    }

    private function service(): SchedulerWindowService
    {
        return app(SchedulerWindowService::class);
    }

    public function test_notifies_after_the_window_closes(): void
    {
        $this->settings();

        $this->assertTrue($this->service()->shouldNotifyNow($this->at('02:01')));
    }

    public function test_does_not_notify_inside_the_window(): void
    {
        $this->settings();

        $this->assertFalse($this->service()->shouldNotifyNow($this->at('01:50')));
    }

    /**
     * A korábbi hiba: kimaradó ütemező tickek miatt az ablak záró sávjába nem esett futás,
     * ezért az értesítés elmaradt. Az utolsó futás korától függetlenül el kell mennie.
     */
    public function test_notifies_even_when_the_last_run_is_far_from_the_window_end(): void
    {
        $this->settings(['last_scheduled_run_at' => $this->at('01:20')]);

        $this->assertTrue($this->service()->shouldNotifyNow($this->at('02:01')));
    }

    public function test_window_end_minute_still_counts_as_inside(): void
    {
        $this->settings();

        $this->assertFalse($this->service()->shouldNotifyNow($this->at('02:00:30')));
    }

    public function test_does_not_notify_twice_for_the_same_window(): void
    {
        $settings = $this->settings();

        $this->service()->markNotified($this->at('02:01'));
        $settings->refresh();

        $this->assertFalse($this->service()->shouldNotifyNow($this->at('02:02')));
        $this->assertFalse($this->service()->shouldNotifyNow($this->at('09:00')));
    }

    public function test_notifies_again_after_a_newer_run(): void
    {
        $this->settings([
            'last_notified_at' => $this->at('02:01'),
            'last_scheduled_run_at' => $this->at('02:01')->addDay()->setTime(1, 40),
        ]);

        $this->assertTrue($this->service()->shouldNotifyNow($this->at('02:01')->addDay()->setTime(2, 5)));
    }

    public function test_does_not_notify_without_any_run(): void
    {
        $this->settings(['last_scheduled_run_at' => null]);

        $this->assertFalse($this->service()->shouldNotifyNow($this->at('02:01')));
    }

    public function test_disabled_scheduler_never_notifies(): void
    {
        $this->settings(['enabled' => false]);

        $this->assertFalse($this->service()->shouldNotifyNow($this->at('02:01')));
    }
}
