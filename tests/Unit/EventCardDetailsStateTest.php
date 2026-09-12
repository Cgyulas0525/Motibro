<?php

namespace Tests\Unit;

use App\Services\Motibro\EventCard;
use PHPUnit\Framework\TestCase;

class EventCardDetailsStateTest extends TestCase
{
    public function test_booked_panel_is_not_read_as_bookable(): void
    {
        $panel = '<button type="button" onclick="cancel_booking_to_event(\'7762855\')" '
            .'id="cancel_booking_buttom">Kijelentkezés</button>';

        $this->assertSame(
            ['state' => 'booked', 'event_id' => '7762855'],
            EventCard::detailsState($panel),
        );
    }

    public function test_bookable_panel_is_detected(): void
    {
        $panel = '<button type="button" onclick="start_ladda(this);booking_to_event(\'7762101\')" '
            .'id="booking_to_event_buttom">Bejelentkezés</button>';

        $this->assertSame(
            ['state' => 'bookable', 'event_id' => '7762101'],
            EventCard::detailsState($panel),
        );
    }

    public function test_waitlist_panel_is_detected(): void
    {
        $panel = '<button type="button" onclick="start_ladda(this);join_waitlist(\'7861139\')" '
            .'id="join_waitlist_buttom">Várólistára</button>';

        $this->assertSame(
            ['state' => 'waitlist', 'event_id' => '7861139'],
            EventCard::detailsState($panel),
        );
    }

    public function test_escaped_javascript_response_is_handled(): void
    {
        $panel = '$("#show_event_details_panel_content").html(" '
            .'<button onclick=\\"start_ladda(this);booking_to_event(\\\'7762354\\\')\\">Bejelentkezés</button>");';

        $this->assertSame('bookable', EventCard::detailsState($panel)['state']);
    }

    public function test_panel_without_action_is_unknown(): void
    {
        $this->assertSame(
            ['state' => 'unknown', 'event_id' => null],
            EventCard::detailsState('<div>betelt</div>'),
        );
    }
}
