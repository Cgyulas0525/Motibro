<?php

namespace App\Services\Motibro;

use Carbon\Carbon;

/**
 * A Motibro events API kártya-HTML és részletek-panel értelmezése.
 */
class EventCard
{
    /**
     * A portál kártya durva állapota (nem hitelesíti a bejelentkezett felhasználót).
     */
    public static function availability(string $cardHtml): string
    {
        return match (true) {
            (bool) preg_match('/Bejelentkezve|már jelentkez/iu', $cardHtml) => 'already',
            str_contains($cardHtml, 'Bejelentkezés') => 'bookable',
            str_contains($cardHtml, 'Várólistára') => 'waitlist',
            str_contains($cardHtml, 'betelt') => 'full',
            default => 'unknown',
        };
    }

    public static function shortId(string $cardHtml): ?string
    {
        $normalized = str_replace('&amp;', '&', $cardHtml);

        if (preg_match("/show_event_details\('([^']+)'/", $normalized, $matches)) {
            return $matches[1];
        }

        return preg_match('/[?&]id=([^&"\'\s]+)/', $normalized, $matches) ? $matches[1] : null;
    }

    /**
     * Az events API `start` mezője helyi időt ad `Z` utótaggal (pl. 2026-09-14T07:00:00.000Z = 07:00 Budapest).
     */
    public static function slotKey(string $start): ?string
    {
        return preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/', $start, $matches)
            ? "{$matches[1]}T{$matches[2]}"
            : null;
    }

    public static function slotKeyFromIso(string $iso): string
    {
        return Carbon::parse($iso)
            ->timezone(config('app.timezone', 'Europe/Budapest'))
            ->format('Y-m-d\TH:i');
    }

    /**
     * A részletek-panel válaszából kiolvasott tényleges akció.
     *
     * @return array{state: string, event_id: string|null}
     */
    public static function detailsState(string $html): array
    {
        $normalized = stripcslashes($html);

        foreach ([
            'booked' => 'cancel_booking_to_event',
            'waitlisted' => 'leave_waitlist',
            'bookable' => 'booking_to_event',
            'waitlist' => 'join_waitlist',
        ] as $state => $function) {
            // A lookbehind kell, mert a `cancel_booking_to_event` tartalmazza a `booking_to_event`-et.
            if (preg_match('/(?<![a-z_])'.$function."\('(\d+)'\)/", $normalized, $matches)) {
                return ['state' => $state, 'event_id' => $matches[1]];
            }
        }

        return ['state' => 'unknown', 'event_id' => null];
    }
}
