const TZ = 'Europe/Budapest';

export function budapestSlotKey(iso) {
    const d = new Date(iso);
    const local = d.toLocaleString('sv-SE', { timeZone: TZ });
    const [date, time] = local.split(' ');
    return `${date}T${time.slice(0, 5)}`;
}

export function eventSlotKey(start) {
    const match = String(start).match(/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/);
    if (!match) {
        return null;
    }
    return `${match[1]}T${match[2]}`;
}

export function extractShortEventId(cardHtml) {
    const fromOnclick = cardHtml.match(/show_event_details\('([^']+)'\)/);
    if (fromOnclick) {
        return fromOnclick[1];
    }

    const fromHref = cardHtml.match(/[?&]id=([^&"']+)/);
    return fromHref?.[1] ?? null;
}

export function getCardAvailability(cardHtml) {
    if (/Bejelentkezve|már jelentkez/i.test(cardHtml)) {
        return 'already';
    }
    if (cardHtml.includes('Bejelentkezés')) {
        return 'bookable';
    }
    if (cardHtml.includes('Várólistára')) {
        return 'waitlist';
    }
    if (cardHtml.includes('betelt')) {
        return 'full';
    }
    return 'unknown';
}

export async function fetchEvents(page, { portalSiteId, startDate, lengthDays }) {
    return page.evaluate(
        async ({ portalSiteId: siteId, startDate: date, lengthDays: days }) => {
            const response = await fetch(
                `/portals/group_classes_get_events?date=${date}&length_days=${days}&ts=${Date.now()}&portal_site_id=${siteId}`,
            );
            if (!response.ok) {
                throw new Error(`Events API hiba: ${response.status}`);
            }
            return response.json();
        },
        { portalSiteId, startDate, lengthDays },
    );
}

export function findEventForSlot(events, slotIso) {
    const key = budapestSlotKey(slotIso);
    return events.find((event) => eventSlotKey(event.start) === key) ?? null;
}

export function eventsQueryStart(slots) {
    if (!slots.length) {
        const now = new Date();
        return `${now.getFullYear()}-${now.getMonth() + 1}-${now.getDate()}`;
    }

    const earliest = slots
        .map((slot) => new Date(slot.slot_starts_at))
        .sort((a, b) => a - b)[0];

    return earliest.toLocaleString('sv-SE', { timeZone: TZ }).split(' ')[0];
}
