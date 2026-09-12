<?php

namespace App\Services\Motibro;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Motibro portál HTTP kliens — böngésző (Playwright/Node) nélkül.
 *
 * A foglalás a portálon sima AJAX kérés, ezért cURL-lel reprodukálható:
 * login → events API → show_event_details → booking_do.
 */
class MotibroHttpClient
{
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) '
        .'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    private CookieJar $cookies;

    private string $baseUrl;

    private string $club;

    private ?string $csrfToken = null;

    private bool $loggedIn = false;

    private ?int $lastLoginStatus = null;

    private string $lastLoginBody = '';

    public function __construct(
        string $baseUrl,
        private string $email,
        private string $password,
        private string $portalSiteId,
        ?string $club = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookies = new CookieJar();
        $this->club = $club ?: $this->clubFromBaseUrl();
    }

    public static function fromConfig(): self
    {
        return new self(
            (string) config('motibro.base_url'),
            (string) config('motibro.email'),
            (string) config('motibro.password'),
            (string) config('motibro.portal_site_id'),
            config('motibro.club'),
        );
    }

    public function login(): void
    {
        if ($this->loggedIn) {
            return;
        }

        if ($this->email === '' || $this->password === '' || $this->baseUrl === '') {
            throw new RuntimeException('Hiányzó Motibro konfiguráció (email, password, base_url).');
        }

        $loginPage = $this->http()->get($this->url('/login'))->throw()->body();
        $token = $this->formAuthenticityToken($loginPage);

        if ($token === null) {
            throw new RuntimeException('Motibro login: authenticity_token nem található a bejelentkező űrlapon.');
        }

        // Rails UJS remote form: `Accept: text/javascript` nélkül a szerver visszadob a
        // /login oldalra. A válasz JS, amiben a következő lépés URL-je jön.
        $login = $this->http()
            ->withoutRedirecting()
            ->asForm()
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'text/javascript, text/html, application/xml, text/xml, */*',
                'Referer' => $this->url('/login'),
            ])
            ->post($this->url('/portals/login_login?action=login&controller=portals'), [
                'authenticity_token' => $token,
                'club' => $this->club,
                'event' => '',
                'page' => '',
                'language' => 'hu',
                'payment_request_token' => '',
                'email' => $this->email,
                'password' => $this->password,
                'commit' => 'Bejelentkezés',
            ]);

        if ($login->serverError()) {
            $login->throw();
        }

        $this->lastLoginStatus = $login->status();
        $this->lastLoginBody = $login->body();

        // A válasz JS-e ide irányít tovább (pl. /portals/signing_in?...).
        if (preg_match("/window\.location\.href\s*=\s*'([^']+)'/", $login->body(), $next)) {
            $this->http()->get($this->absolute($next[1]));
        }

        $dashboard = $this->http()->get($this->url('/musers'))->throw()->body();

        if (str_contains($dashboard, 'Hibás email cím vagy jelszó')) {
            throw new RuntimeException('Hibás Motibro email cím vagy jelszó.');
        }

        if (! str_contains($dashboard, '/musers/booking_do')) {
            throw new RuntimeException(sprintf(
                'Motibro bejelentkezés sikertelen — a foglalási felület nem érhető el (login HTTP %s: %s).',
                $this->lastLoginStatus ?? '?',
                substr(preg_replace('/\s+/', ' ', strip_tags($this->lastLoginBody)) ?? '', 0, 200),
            ));
        }

        $this->csrfToken = $this->metaCsrfToken($dashboard);

        if ($this->csrfToken === null) {
            throw new RuntimeException('Motibro CSRF token nem található a bejelentkezés után.');
        }

        $this->loggedIn = true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function events(string $date, int $lengthDays): array
    {
        $events = $this->http()
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get($this->url('/portals/group_classes_get_events'), [
                'date' => $date,
                'length_days' => $lengthDays,
                'ts' => now()->getTimestampMs(),
                'portal_site_id' => $this->portalSiteId,
            ])
            ->throw()
            ->json();

        if (! is_array($events)) {
            throw new RuntimeException('Motibro events API: érvénytelen válasz.');
        }

        return $events;
    }

    /**
     * Esemény részletek panel — a bejelentkezett felhasználó tényleges állapotával.
     */
    public function eventDetails(string $eventId): string
    {
        $this->login();

        return $this->http()
            ->withHeaders($this->ajaxHeaders())
            ->get($this->url('/musers/show_event_details'), [
                'event_id' => $eventId,
                'page' => '',
            ])
            ->throw()
            ->body();
    }

    /**
     * Felderítéshez: nyers részletek-kérés választható fejlécekkel.
     */
    public function rawEventDetails(string $eventId, bool $asJavascript): string
    {
        $this->login();

        $headers = $asJavascript
            ? $this->ajaxHeaders()
            : ['Referer' => $this->url('/musers')];

        return $this->http()
            ->withHeaders($headers)
            ->get($this->url('/musers/show_event_details'), [
                'event_id' => $eventId,
                'page' => '',
            ])
            ->body();
    }

    public function dashboardHtml(): string
    {
        $this->login();

        return $this->http()->get($this->url('/musers'))->throw()->body();
    }

    /**
     * @param  'booking'|'waitlist'|'cancel_booking'  $function
     */
    public function bookingDo(string $eventId, string $function): string
    {
        $this->login();

        return $this->http()
            ->asForm()
            ->withHeaders($this->ajaxHeaders())
            ->post($this->url('/musers/booking_do'), [
                'event_id' => $eventId,
                'function' => $function,
                'page' => '',
                'authenticity_token' => $this->csrfToken,
            ])
            ->throw()
            ->body();
    }

    private function http(): PendingRequest
    {
        return Http::withOptions([
            'cookies' => $this->cookies,
            'allow_redirects' => ['max' => 10, 'referer' => true],
        ])
            ->withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept-Language' => 'hu-HU,hu;q=0.9',
            ])
            ->connectTimeout(15)
            ->timeout(45);
    }

    /**
     * A portál AJAX végpontjai `text/javascript` választ adnak; e fejlécek nélkül
     * a teljes oldalt (vagy átirányítást) küldik vissza.
     *
     * @return array<string, string>
     */
    private function ajaxHeaders(): array
    {
        return [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/javascript, text/html, application/xml, text/xml, */*',
            'Referer' => $this->url('/musers'),
        ];
    }

    private function url(string $path): string
    {
        return $this->baseUrl.$path;
    }

    private function absolute(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : $this->url($url);
    }

    private function clubFromBaseUrl(): string
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST) ?: '';

        return explode('.', $host)[0] ?? '';
    }

    private function formAuthenticityToken(string $html): ?string
    {
        preg_match(
            '/<form[^>]*login_login.*?name="authenticity_token"\s+value="([^"]+)"/s',
            $html,
            $matches,
        );

        return $matches[1] ?? $this->metaCsrfToken($html);
    }

    private function metaCsrfToken(string $html): ?string
    {
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches);

        return $matches[1] ?? null;
    }
}
