<?php

namespace Tests\Feature;

use App\Models\BookingBlackout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingBlackoutTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_guest_cannot_manage_blackouts(): void
    {
        $this->get(route('booking-blackouts.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_a_single_day_blackout(): void
    {
        $this->actingAs($this->user())
            ->post(route('booking-blackouts.store'), [
                'starts_on' => '2026-10-05',
                'note' => 'orvosi vizsgálat',
            ])
            ->assertRedirect(route('booking-blackouts.index'));

        $row = BookingBlackout::query()->sole();
        $this->assertSame('2026-10-05', $row->starts_on->toDateString());
        $this->assertSame('2026-10-05', $row->ends_on->toDateString());
        $this->assertSame('orvosi vizsgálat', $row->note);
    }

    public function test_user_can_create_a_date_range_blackout(): void
    {
        $this->actingAs($this->user())
            ->post(route('booking-blackouts.store'), [
                'starts_on' => '2026-10-01',
                'ends_on' => '2026-10-08',
                'note' => 'nyári szabadság',
            ])
            ->assertRedirect(route('booking-blackouts.index'));

        $row = BookingBlackout::query()->sole();
        $this->assertSame('2026-10-01', $row->starts_on->toDateString());
        $this->assertSame('2026-10-08', $row->ends_on->toDateString());
        $this->assertSame('nyári szabadság', $row->note);
    }

    public function test_ends_on_cannot_be_before_starts_on(): void
    {
        $this->actingAs($this->user())
            ->from(route('booking-blackouts.create'))
            ->post(route('booking-blackouts.store'), [
                'starts_on' => '2026-10-08',
                'ends_on' => '2026-10-01',
            ])
            ->assertRedirect(route('booking-blackouts.create'))
            ->assertSessionHasErrors('ends_on');
    }

    public function test_user_can_update_a_blackout(): void
    {
        $blackout = BookingBlackout::create([
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-03',
            'note' => 'régi',
        ]);

        $this->actingAs($this->user())
            ->patch(route('booking-blackouts.update', $blackout), [
                'starts_on' => '2026-10-10',
                'ends_on' => '2026-10-12',
                'note' => 'új',
            ])
            ->assertRedirect(route('booking-blackouts.index'));

        $blackout->refresh();
        $this->assertSame('2026-10-10', $blackout->starts_on->toDateString());
        $this->assertSame('2026-10-12', $blackout->ends_on->toDateString());
        $this->assertSame('új', $blackout->note);
    }

    public function test_user_can_delete_a_blackout(): void
    {
        $blackout = BookingBlackout::create([
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-01',
            'note' => null,
        ]);

        $this->actingAs($this->user())
            ->delete(route('booking-blackouts.destroy', $blackout))
            ->assertRedirect(route('booking-blackouts.index'));

        $this->assertDatabaseCount('booking_blackouts', 0);
    }
}
