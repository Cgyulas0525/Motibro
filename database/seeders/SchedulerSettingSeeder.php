<?php

namespace Database\Seeders;

use App\Models\SchedulerSetting;
use Illuminate\Database\Seeder;

class SchedulerSettingSeeder extends Seeder
{
    public function run(): void
    {
        SchedulerSetting::updateOrCreate(['id' => 1], [
            'enabled' => true,
            'window_start' => '00:00',
            'window_end' => '02:00',
            'interval_minutes' => 10,
            'timezone' => 'Europe/Budapest',
        ]);
    }
}
