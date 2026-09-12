<?php

namespace App\Services\Motibro;

use Carbon\Carbon;
use Throwable;

/**
 * Foglalás futtatása tisztán PHP-ból (cURL), Node/Playwright nélkül.
 *
 * A visszaadott adatszerkezet megegyezik a Playwright script kimenetével,
 * így a MotibroBookingService feldolgozása változatlan.
 */
class HttpBookingRunner
{
    /**
     * @return array{ok: bool, dry_run: bool, attempts: array<int, array<string, mixed>>, errors: array<int, string>, message: string}
     */
    public function run(array $payload, bool $dryRun = false): array
    {
        $attempts = [];
        $errors = [];

        try {
            $client = new MotibroHttpClient(
                (string) ($payload['base_url'] ?? ''),
                (string) ($payload['email'] ?? ''),
                (string) ($payload['password'] ?? ''),
                (string) ($payload['portal_site_id'] ?? '553'),
                $payload['club'] ?? config('motibro.club'),
            );

            $slots = $payload['slots'] ?? [];
            $rules = collect($payload['rules'] ?? [])->keyBy('id');
            $skipKeys = collect($payload['skip_slots'] ?? [])
                ->map(fn ($slot) => EventCard::slotKeyFromIso((string) $slot))
                ->all();
            $globalWaitlist = (bool) ($payload['waitlist'] ?? false);
            $weeksAhead = max(1, (int) ($payload['weeks_ahead'] ?? 3));

            if (! $dryRun) {
                $client->login();
            }

            $events = $this->indexEvents(
                $client->events($this->queryStartDate($slots), max(7, $weeksAhead * 7))
            );

            foreach ($slots as $slot) {
                $slotIso = (string) $slot['slot_starts_at'];
                $ruleId = $slot['rule_id'] ?? null;
                $rule = $rules->get($ruleId);
                $key = EventCard::slotKeyFromIso($slotIso);
                $label = $rule['label'] ?? $key;

                if (in_array($key, $skipKeys, true)) {
                    $attempts[] = $this->attempt($ruleId, $slotIso, 'skipped', 'Slot már foglalt (adatbázis).');

                    continue;
                }

                $event = $events[$key] ?? null;

                if ($event === null) {
                    $attempts[] = $this->attempt($ruleId, $slotIso, 'unavailable',
                        "{$label}: nem található esemény a Motibro naptárban.");

                    continue;
                }

                $cardState = EventCard::availability($event['card_html'] ?? '');
                $eventId = (string) ($event['id'] ?? '');

                if ($dryRun) {
                    $attempts[] = $this->attempt($ruleId, $slotIso, 'skipped',
                        "{$label}: dry-run — {$cardState} (#{$eventId}).");

                    continue;
                }

                if ($eventId === '') {
                    $attempts[] = $this->attempt($ruleId, $slotIso, 'failed',
                        "{$label}: esemény azonosító nem található.");
                    $errors[] = "{$label}: esemény azonosító nem található.";

                    continue;
                }

                try {
                    $attempts[] = $this->handleSlot(
                        $client,
                        $ruleId,
                        $slotIso,
                        $label,
                        $eventId,
                        $cardState,
                        $globalWaitlist || (bool) ($rule['waitlist_ok'] ?? false),
                    );
                } catch (Throwable $e) {
                    $attempts[] = $this->attempt($ruleId, $slotIso, 'failed', "{$label}: {$e->getMessage()}");
                    $errors[] = $e->getMessage();
                }
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();

            return [
                'ok' => false,
                'dry_run' => $dryRun,
                'attempts' => $attempts,
                'errors' => $errors,
                'message' => $e->getMessage(),
            ];
        }

        return [
            'ok' => $errors === [] && collect($attempts)->where('action', 'failed')->isEmpty(),
            'dry_run' => $dryRun,
            'attempts' => $attempts,
            'errors' => $errors,
            'message' => $this->summarize($attempts, $errors, $dryRun),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleSlot(
        MotibroHttpClient $client,
        ?int $ruleId,
        string $slotIso,
        string $label,
        string $eventId,
        string $cardState,
        bool $allowWaitlist,
    ): array {
        $details = EventCard::detailsState($client->eventDetails($eventId));

        // A panel a kért eseményről szóljon — ez váltja ki a korábbi #hero_content ellenőrzést.
        if ($details['event_id'] !== null && $details['event_id'] !== $eventId) {
            return $this->attempt($ruleId, $slotIso, 'failed',
                "{$label}: a részletek panel más eseményt adott vissza (#{$details['event_id']}).");
        }

        return match ($details['state']) {
            'booked' => $this->attempt($ruleId, $slotIso, 'skipped', "{$label}: már bejelentkezve."),
            'waitlisted' => $this->attempt($ruleId, $slotIso, 'skipped', "{$label}: már várólistán."),
            'bookable' => $this->book($client, $ruleId, $slotIso, $label, $eventId, 'booking'),
            'waitlist' => $allowWaitlist
                ? $this->book($client, $ruleId, $slotIso, $label, $eventId, 'waitlist')
                : $this->attempt($ruleId, $slotIso, 'unavailable',
                    "{$label}: csak várólista érhető el, de nincs engedélyezve."),
            default => $this->attempt($ruleId, $slotIso, 'unavailable',
                "{$label}: nem foglalható (kártya: {$cardState})."),
        };
    }

    /**
     * @param  'booking'|'waitlist'  $function
     * @return array<string, mixed>
     */
    private function book(
        MotibroHttpClient $client,
        ?int $ruleId,
        string $slotIso,
        string $label,
        string $eventId,
        string $function,
    ): array {
        $response = $client->bookingDo($eventId, $function);
        $message = $this->responseMessage($response);

        if (str_contains($response, 'PurchasePassInfo')) {
            return $this->attempt($ruleId, $slotIso, 'unavailable',
                "{$label}: nincs érvényes bérlet a foglaláshoz. {$message}");
        }

        // A tényleges eredményt a panel újraolvasásából hitelesítjük.
        $after = EventCard::detailsState($client->eventDetails($eventId));

        if ($function === 'booking') {
            return $after['state'] === 'booked'
                ? $this->attempt($ruleId, $slotIso, 'booked', "{$label}: sikeres foglalás. {$message}")
                : $this->attempt($ruleId, $slotIso, 'failed',
                    "{$label}: a foglalás nem igazolható vissza (panel: {$after['state']}). {$message}");
        }

        // Ha a panel még mindig jelentkezést kínál a várólistára, nem kerültünk fel.
        return $after['state'] === 'waitlist'
            ? $this->attempt($ruleId, $slotIso, 'failed',
                "{$label}: várólistára kerülés nem igazolható vissza. {$message}")
            : $this->attempt($ruleId, $slotIso, 'waitlisted', "{$label}: várólistára került. {$message}");
    }

    /**
     * @return array<string, mixed>
     */
    private function attempt(?int $ruleId, string $slotIso, string $action, string $message): array
    {
        return [
            'rule_id' => $ruleId,
            'slot' => $slotIso,
            'action' => $action,
            'message' => trim($message),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<string, array<string, mixed>>
     */
    private function indexEvents(array $events): array
    {
        $indexed = [];

        foreach ($events as $event) {
            $key = EventCard::slotKey((string) ($event['start'] ?? ''));

            if ($key !== null && ! isset($indexed[$key])) {
                $indexed[$key] = $event;
            }
        }

        return $indexed;
    }

    private function queryStartDate(array $slots): string
    {
        $tz = config('app.timezone', 'Europe/Budapest');

        $earliest = collect($slots)
            ->map(fn ($slot) => Carbon::parse((string) $slot['slot_starts_at'])->timezone($tz))
            ->sort()
            ->first();

        return ($earliest ?? now()->timezone($tz))->format('Y-n-j');
    }

    /**
     * A portál JS választ ad; ebből olvasható szöveget készítünk a naplóhoz.
     */
    private function responseMessage(string $body): string
    {
        $text = strip_tags(stripcslashes($body));
        $text = preg_replace('/\s+/', ' ', $text) ?? '';
        $text = trim(html_entity_decode($text));

        return mb_substr($text, 0, 200);
    }

    /**
     * @param  array<int, array<string, mixed>>  $attempts
     * @param  array<int, string>  $errors
     */
    private function summarize(array $attempts, array $errors, bool $dryRun): string
    {
        $rows = collect($attempts);
        $counts = fn (string $action) => $rows->where('action', $action)->count();

        if ($dryRun) {
            return 'Dry-run: '.$rows->count().' slot ellenőrizve.';
        }

        $booked = $counts('booked');
        $waitlisted = $counts('waitlisted');
        $failed = $counts('failed');

        if ($failed === 0 && $errors === [] && $booked === 0 && $waitlisted === 0 && $counts('unavailable') > 0) {
            return 'Nincs foglalható időpont';
        }

        if ($failed === 0 && $errors === []) {
            return sprintf('Kész — foglalva: %d, várólista: %d, kihagyva: %d.',
                $booked, $waitlisted, $counts('skipped'));
        }

        return sprintf('Kész — foglalva: %d, várólista: %d, kihagyva: %d, hiba: %d.',
            $booked, $waitlisted, $counts('skipped'), $failed);
    }
}
