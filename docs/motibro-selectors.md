# Motibro selectorok — Reform Me (portal_site_id=553)

> Felderítés: 2026-08-27, `https://reform-me.motibro.com`

## Login

| Elem | Selector |
|------|----------|
| URL | `/login` |
| Email | `#email` (`input[name="email"]`) |
| Jelszó | `#password` |
| Submit | `form[action*="login_login"] input[type="submit"][name="commit"]` |
| Hiba | szöveg: „Hibás email cím vagy jelszó!” |
| Booking redirect | `/login?page=booking&event={shortId}` |

A login űrlap Rails UJS (`data-remote="true"`) — submit után `login_login` XHR válaszra várunk.

## Naptár

| Elem | Selector / API |
|------|----------------|
| Naptár container | `#muser_booking_calendar` |
| Események API | `GET /portals/group_classes_get_events?date=Y-M-D&length_days=N&portal_site_id=553` |
| Esemény kártya | `#event_card_{numericId}` |
| Esemény rövid ID | `show_event_details('{shortId}')` vagy `/events?id={shortId}` |

**Megjegyzés:** az API `start` mezője helyi időt ad `Z` utótaggal (pl. `2026-09-08T08:00:00.000Z` = 08:00 Budapest).

## Kártya állapotok (`card_html`)

| Szöveg | Jelentés |
|--------|----------|
| `Bejelentkezés` gomb | Foglalható |
| `Várólistára` gomb | Betelt, várólista elérhető |
| `betelt` label | Tele |
| `Bejelentkezve` | Már jelentkezett |

## Esemény oldal

| Elem | Selector |
|------|----------|
| URL | `/events?back=true&id={shortId}&portal_site_id=553` |
| Foglalás gomb (bejelentkezve) | `a.btn-success` / `button.btn-success` „Bejelentkezés” (NEM `/login` link) |
| Várólista gomb | „Várólistára” |
| Siker modal | `#SuccessModal`, bezárás: `#success_modal_ok_close_button` |
| Hiba modal | `#ErrorModal` |
| Bérlet hiány | `#PurchasePassInfo` |
| Rendben modal | gomb: „Rendben” |

## Globális modal

- „Rendben” — első belépés / tájékoztató popup

## HTTP API — böngésző nélküli foglalás

> Felderítés: 2026-09-12. A portál minden foglalási művelete AJAX kérés, ezért Node/Playwright
> nélkül, PHP cURL-lel is elvégezhető (`MOTIBRO_DRIVER=http`).

Munkamenet: egyetlen cookie, `_motibro_session` (HttpOnly, Secure, domain `.motibro.com`).

**Fontos:** a portál AJAX végpontjai `Accept: text/javascript` fejlécet igényelnek.
Nélküle a szerver a teljes oldalt vagy a `/login` átirányítást adja vissza.

| Lépés | Kérés |
|-------|-------|
| Login token | `GET /login` → `form[action*=login_login] input[name=authenticity_token]` |
| Login | `POST /portals/login_login?action=login&controller=portals` |
| Login mezők | `authenticity_token`, `club` (aldomain, pl. `reform-me`), `event`, `page`, `language=hu`, `payment_request_token`, `email`, `password`, `commit` |
| Login válasz | `text/javascript`: `window.location.href='/portals/signing_in?...'` |
| Bejelentkezés ellenőrzése | `GET /musers` tartalmazza a `/musers/booking_do` sztringet |
| CSRF a műveletekhez | `meta[name=csrf-token]` a `/musers` oldalról |
| Naptár | `GET /portals/group_classes_get_events?date=Y-n-j&length_days=N&portal_site_id=553` |
| Esemény állapota | `GET /musers/show_event_details?event_id={numericId}&page=` |
| Foglalás | `POST /musers/booking_do` — `event_id`, `function`, `page`, `authenticity_token` |

`function` értékei: `booking`, `waitlist`, `cancel_booking`.

### Azonosítók

- Az events API `id` mezője a **numerikus** esemény azonosító — ez kell a `booking_do`-hoz
  és a `show_event_details`-hez.
- A kártya `show_event_details('{shortId}')` rövid azonosítója **csak** a nyilvános
  `/events` oldalhoz jó; a `/musers/show_event_details` rövid id-re bejelentkezési űrlapot ad.

### Állapot a részletek panelből

A `card_html` a **nyilvános** nézetet adja (bejelentkezés előtt és után azonos), ezért a
felhasználó saját foglalása nem látszik benne. A tényleges állapotot a részletek panel adja:

| Panel akciója | Állapot |
|---------------|---------|
| `cancel_booking_to_event('{id}')` | már bejelentkezett az órára |
| `booking_to_event('{id}')` | foglalható |
| `join_waitlist('{id}')` | csak várólista |

**Csapda:** a `cancel_booking_to_event` sztring tartalmazza a `booking_to_event`-et, ezért a
mintaillesztésnél lookbehind kell (`(?<![a-z_])booking_to_event\(`).

### Elavult

A `/events?back=true&id={shortId}` bejelentkezett munkamenetben a `/musers` oldalra irányít,
így a `#hero_content` alapú időpont-ellenőrzés nem működik. Helyette a részletek panel
`event_id`-ját hasonlítjuk össze a kért esemény azonosítójával.
