<?php

namespace Database\Seeders;

use App\Models\BookingRule;
use Illuminate\Database\Seeder;

class BookingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['weekday' => 1, 'time' => '08:00', 'label' => 'Hétfő reggel', 'sort_order' => 1],
            ['weekday' => 3, 'time' => '07:00', 'label' => 'Szerda reggel', 'sort_order' => 2],
            ['weekday' => 4, 'time' => '18:00', 'label' => 'Csütörtök este', 'sort_order' => 3],
        ];

        foreach ($defaults as $row) {
            BookingRule::updateOrCreate(
                ['weekday' => $row['weekday'], 'time' => $row['time']],
                [...$row, 'enabled' => true, 'waitlist_ok' => false],
            );
        }
    }
}
