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
