<?php

namespace App\Console\Commands;

use App\Services\Motibro\EventCard;
use App\Services\Motibro\MotibroHttpClient;
use Illuminate\Console\Command;

class ProbeMotibroHttp extends Command
{
    protected $signature = 'motibro:probe
                            {--limit=3 : Hány eseményt vizsgáljon a részletek panelen}
                            {--trace : A bejelentkezés HTTP lépéseinek kiírása}
                            {--details : A részletek panel fejléc/azonosító kombinációinak tesztelése}
                            {--slots : A beállított szabályok slotjainak állapota a részletek panel szerint}
                            {--dump= : Egy esemény részletek-paneljének mentése és gombjainak kiírása}';

    protected $description = 'Motibro HTTP kliens ellenőrzése (csak olvasás, nem foglal)';

    public function handle(): int
    {
        if ($this->option('trace')) {
            return $this->trace();
        }

        if ($this->option('details')) {
            return $this->details();
        }

        if ($this->option('slots')) {
            return $this->slots();
        }

        if ($this->option('dump')) {
            return $this->dump((string) $this->option('dump'));
        }

        $client = MotibroHttpClient::fromConfig();

        $this->info('Bejelentkezés...');
        $client->login();
        $this->line('  OK');

        $today = now()->timezone(config('app.timezone'));
        $events = $client->events($today->format('Y-n-j'), 21);
        $this->info("Események: ".count($events));

        $byAvailability = [];
        foreach ($events as $event) {
            $availability = EventCard::availability($event['card_html'] ?? '');
            $byAvailability[$availability] = ($byAvailability[$availability] ?? 0) + 1;
        }
        $this->line('  kártya-állapotok: '.json_encode($byAvailability));

        $limit = (int) $this->option('limit');
        $checked = 0;

        foreach ($events as $event) {
            if ($checked >= $limit) {
                break;
            }

            $cardHtml = $event['card_html'] ?? '';
            $availability = EventCard::availability($cardHtml);

            if (! in_array($availability, ['bookable', 'waitlist'], true)) {
                continue;
            }

            $shortId = EventCard::shortId($cardHtml);
            $details = $client->eventDetails((string) $shortId);
            $state = EventCard::detailsState($details);

            $this->newLine();
            $this->line("slot: ".EventCard::slotKey((string) $event['start'])
                ." | api id: {$event['id']} | short: {$shortId}");
            $this->line("  kártya: {$availability} | panel: {$state['state']} | panel event_id: ".($state['event_id'] ?? '-'));
            $this->line('  panel válasz hossz: '.strlen($details).' byte');

            if ($state['state'] === 'unknown') {
                $this->warn('  panel akció nem felismert — részlet: '
                    .substr(preg_replace('/\s+/', ' ', strip_tags(stripcslashes($details))), 0, 300));
            }

            $checked++;
        }

        return self::SUCCESS;
    }

    private function dump(string $eventId): int
    {
        $client = MotibroHttpClient::fromConfig();
        $panel = stripcslashes($client->eventDetails($eventId));

        $path = storage_path("app/motibro/debug/panel-{$eventId}.html");
        @mkdir(dirname($path), 0775, true);
        file_put_contents($path, $panel);
        $this->line("mentve: {$path} (".strlen($panel).' byte)');

        foreach (['booking_to_event', 'join_waitlist', 'cancel_booking_to_event', 'leave_waitlist'] as $fn) {
            $offset = 0;
            while (($pos = strpos($panel, $fn.'(', $offset)) !== false) {
                $start = max(0, $pos - 400);
                $this->newLine();
                $this->line("--- {$fn} @ {$pos} ---");
                $this->line(preg_replace('/\s+/', ' ', substr($panel, $start, 700)) ?? '');
                $offset = $pos + 1;
            }
        }

        return self::SUCCESS;
    }

    private function slots(): int
    {
        $client = MotibroHttpClient::fromConfig();
        $client->login();

        $today = now()->timezone(config('app.timezone'));
        $events = collect($client->events($today->format('Y-n-j'), 28))
            ->mapWithKeys(fn ($e) => [EventCard::slotKey((string) $e['start']) => $e]);

        $rules = \App\Models\BookingRule::where('enabled', true)->get();
        $checked = 0;

        foreach ($events as $key => $event) {
            $matches = $rules->first(function ($rule) use ($key) {
                $at = \Carbon\Carbon::parse($key);

                return (int) $at->isoWeekday() === (int) $rule->weekday
                    && $at->format('H:i') === substr((string) $rule->time_formatted, 0, 5);
            });

            if ($matches === null || $checked >= 4) {
                continue;
            }

            $eventId = (string) $event['id'];
            $panel = $client->eventDetails($eventId);
            $state = EventCard::detailsState($panel);
            $text = preg_replace('/\s+/', ' ', strip_tags(stripcslashes($panel))) ?? '';

            $this->newLine();
            $this->line("{$key} | #{$eventId} | kártya: ".EventCard::availability($event['card_html'] ?? '')
                ." | panel: {$state['state']}");
            foreach (['booking_to_event', 'join_waitlist', 'cancel_booking_to_event', 'leave_waitlist'] as $fn) {
                if (str_contains(stripcslashes($panel), $fn.'(')) {
                    $this->line("  akció a panelen: {$fn}");
                }
            }
            $this->line('  gombszövegek: '.implode(' / ', $this->buttonTexts($panel)));
            $this->line('  szöveg: '.mb_substr(trim(html_entity_decode($text)), 0, 180));

            $checked++;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function buttonTexts(string $panel): array
    {
        preg_match_all('/<(?:button|a)[^>]*>([^<]{2,40})</', stripcslashes($panel), $m);

        return array_values(array_unique(array_filter(array_map(
            fn ($t) => trim(preg_replace('/\s+/', ' ', html_entity_decode($t)) ?? ''),
            $m[1],
        ))));
    }

    private function details(): int
    {
        $client = MotibroHttpClient::fromConfig();
        $client->login();
        $this->line('bejelentkezés OK');

        $today = now()->timezone(config('app.timezone'));
        $events = $client->events($today->format('Y-n-j'), 21);

        $target = collect($events)->first(
            fn ($e) => EventCard::availability($e['card_html'] ?? '') === 'bookable'
        );

        if ($target === null) {
            $this->warn('nincs foglalható esemény');

            return self::FAILURE;
        }

        $numericId = (string) $target['id'];
        $shortId = (string) EventCard::shortId($target['card_html'] ?? '');
        $this->line("cél: ".EventCard::slotKey((string) $target['start'])." | numerikus: {$numericId} | rövid: {$shortId}");

        foreach ([
            'numerikus + js' => [$numericId, true],
            'numerikus + html' => [$numericId, false],
            'rövid + js' => [$shortId, true],
            'rövid + html' => [$shortId, false],
        ] as $label => [$id, $js]) {
            $body = $client->rawEventDetails($id, $js);
            $text = preg_replace('/\s+/', ' ', strip_tags(stripcslashes($body))) ?? '';
            $state = EventCard::detailsState($body);

            $this->newLine();
            $this->line("[{$label}] {$id} → ".strlen($body).' byte | állapot: '.$state['state']
                .' | event_id: '.($state['event_id'] ?? '-'));
            $this->line('  szöveg: '.substr(trim($text), 0, 200));
        }

        $dashboard = $client->dashboardHtml();
        $this->newLine();
        $this->line('dashboard: '.strlen($dashboard).' byte');
        foreach (['booking_to_event', 'join_waitlist', 'cancel_booking_to_event'] as $fn) {
            preg_match_all('/'.$fn."\('(\d+)'\)/", $dashboard, $m);
            $this->line("  {$fn}: ".implode(', ', array_unique($m[1])) ?: "  {$fn}: -");
        }

        return self::SUCCESS;
    }

    private function trace(): int
    {
        $base = rtrim((string) config('motibro.base_url'), '/');
        $jar = new \GuzzleHttp\Cookie\CookieJar();

        $http = fn () => \Illuminate\Support\Facades\Http::withOptions([
            'cookies' => $jar,
            'allow_redirects' => false,
        ])->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'Accept-Language' => 'hu-HU,hu;q=0.9',
        ])->timeout(30);

        $loginPage = $http()->get($base.'/login');
        $this->line("GET /login → {$loginPage->status()} | cookie-k: ".$this->cookieNames($jar));

        preg_match('/<form[^>]*login_login.*?name="authenticity_token"\s+value="([^"]+)"/s', $loginPage->body(), $m);
        $token = $m[1] ?? null;
        $this->line('  authenticity_token: '.($token ? 'megvan ('.strlen($token).' karakter)' : 'NINCS'));

        foreach (['form' => false, 'form+xhr' => true] as $label => $xhr) {
            $request = $http()->asForm();
            if ($xhr) {
                $request = $request->withHeaders([
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Accept' => 'text/javascript, text/html, application/xml, text/xml, */*',
                    'Referer' => $base.'/login',
                ]);
            }

            $login = $request->post($base.'/portals/login_login?action=login&controller=portals', [
                'authenticity_token' => $token,
                'club' => explode('.', (string) parse_url($base, PHP_URL_HOST))[0],
                'event' => '',
                'page' => '',
                'language' => 'hu',
                'payment_request_token' => '',
                'email' => (string) config('motibro.email'),
                'password' => (string) config('motibro.password'),
                'commit' => 'Bejelentkezés',
            ]);

            $this->newLine();
            $this->line("POST login_login [{$label}] → {$login->status()}");
            $this->line('  Location: '.($login->header('Location') ?: '-'));
            $this->line('  Content-Type: '.($login->header('Content-Type') ?: '-'));
            $this->line('  cookie-k: '.$this->cookieNames($jar));
            $this->line('  body: '.substr(preg_replace('/\s+/', ' ', strip_tags($login->body())) ?? '', 0, 300));

            $dashboard = $http()->get($base.'/musers');
            $this->line("  GET /musers → {$dashboard->status()} | Location: ".($dashboard->header('Location') ?: '-'));
            $this->line('  booking_do a válaszban: '.(str_contains($dashboard->body(), '/musers/booking_do') ? 'IGEN' : 'nem'));

            if (str_contains($dashboard->body(), '/musers/booking_do')) {
                $this->info("  ✓ bejelentkezés sikeres ezzel: {$label}");

                return self::SUCCESS;
            }
        }

        return self::FAILURE;
    }

    private function cookieNames(\GuzzleHttp\Cookie\CookieJar $jar): string
    {
        $names = array_column($jar->toArray(), 'Name');

        return $names === [] ? '(nincs)' : implode(', ', $names);
    }
}
