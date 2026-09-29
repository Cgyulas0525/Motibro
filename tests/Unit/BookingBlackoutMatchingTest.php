<?php

namespace Tests\Unit;

use App\Models\BookingBlackout;
use Tests\TestCase;

class BookingBlackoutMatchingTest extends TestCase
{
    public function test_single_day_covers_that_slot(): void
    {
        $blackouts = [['starts_on' => '2026-10-05', 'ends_on' => '2026-10-05', 'note' => 'orvosi']];

        $this->assertSame(
            'orvosi',
            BookingBlackout::matching('2026-10-05T08:00:00+02:00', $blackouts)['note'],
        );
    }

    public function test_range_covers_every_day_inside(): void
    {
        $blackouts = [['starts_on' => '2026-10-01', 'ends_on' => '2026-10-08', 'note' => 'szabadság']];

        $this->assertNotNull(BookingBlackout::matching('2026-10-01T08:00:00+02:00', $blackouts));
        $this->assertNotNull(BookingBlackout::matching('2026-10-05T18:00:00+02:00', $blackouts));
        $this->assertNotNull(BookingBlackout::matching('2026-10-08T07:00:00+02:00', $blackouts));
    }

    public function test_days_outside_the_range_are_not_skipped(): void
    {
        $blackouts = [['starts_on' => '2026-10-01', 'ends_on' => '2026-10-08', 'note' => 'szabadság']];

        $this->assertNull(BookingBlackout::matching('2026-09-30T08:00:00+02:00', $blackouts));
        $this->assertNull(BookingBlackout::matching('2026-10-09T08:00:00+02:00', $blackouts));
    }

    public function test_empty_list_never_matches(): void
    {
        $this->assertNull(BookingBlackout::matching('2026-10-05T08:00:00+02:00', []));
    }
}
