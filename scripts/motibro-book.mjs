#!/usr/bin/env node
import { chromium } from 'playwright';
import { login } from './lib/login.mjs';
import {
    budapestSlotKey,
    eventsQueryStart,
    extractShortEventId,
    fetchEvents,
    findEventForSlot,
    getCardAvailability,
} from './lib/calendar.mjs';
import { bookEvent, saveScreenshot } from './lib/book-slot.mjs';

const dryRun = process.argv.includes('--dry-run');

async function readStdin() {
    const chunks = [];
    for await (const chunk of process.stdin) {
        chunks.push(chunk);
    }
    const raw = Buffer.concat(chunks).toString('utf8').trim();
    if (!raw) {
        return {};
    }
    return JSON.parse(raw);
}

function isSkipped(slotIso, skipSlots) {
    const key = budapestSlotKey(slotIso);
    return skipSlots.some((slot) => budapestSlotKey(slot) === key);
}

function requireConfig(payload) {
    const email = payload.email;
    const password = payload.password;
    const baseUrl = (payload.base_url ?? '').replace(/\/$/, '');

    if (!email || !password || !baseUrl) {
        throw new Error('Hiányzó Motibro konfiguráció (email, password, base_url).');
    }

    return {
        email,
        password,
        baseUrl,
        headless: payload.headless !== false,
        portalSiteId: String(payload.portal_site_id ?? '553'),
        waitlist: Boolean(payload.waitlist),
        weeksAhead: Number(payload.weeks_ahead ?? 3),
    };
}

async function main() {
    const payload = await readStdin();
    const config = requireConfig(payload);
    const slots = payload.slots ?? [];
    const skipSlots = payload.skip_slots ?? [];
    const rulesById = Object.fromEntries((payload.rules ?? []).map((rule) => [rule.id, rule]));
    const attempts = [];
    const errors = [];

    let browser;
    let page;

    try {
        browser = await chromium.launch({ headless: config.headless });
        page = await browser.newPage();

        await page.goto(config.baseUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

        const lengthDays = Math.max(7, config.weeksAhead * 7);
        const startDate = eventsQueryStart(slots);
        let events = await fetchEvents(page, {
            portalSiteId: config.portalSiteId,
            startDate,
            lengthDays,
        });

        if (!dryRun) {
            await login(page, {
                baseUrl: config.baseUrl,
                email: config.email,
                password: config.password,
            });

            await page.goto(config.baseUrl, { waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => {});
            events = await fetchEvents(page, {
                portalSiteId: config.portalSiteId,
                startDate,
                lengthDays,
            });
        }

        for (const slot of slots) {
            const slotIso = slot.slot_starts_at;
            const ruleId = slot.rule_id;
            const rule = rulesById[ruleId];
            const slotLabel = rule?.label ?? budapestSlotKey(slotIso);

            if (isSkipped(slotIso, skipSlots)) {
                attempts.push({
                    rule_id: ruleId,
                    slot: slotIso,
                    action: 'skipped',
                    message: 'Slot már foglalt (adatbázis).',
                });
                continue;
            }

            const event = findEventForSlot(events, slotIso);
            if (!event) {
                attempts.push({
                    rule_id: ruleId,
                    slot: slotIso,
                    action: 'unavailable',
                    message: `${slotLabel}: nem található esemény a Motibro naptárban.`,
                });
                continue;
            }

            const availability = getCardAvailability(event.card_html ?? '');
            const shortEventId = extractShortEventId(event.card_html ?? '');
            const numericEventId = event.id ?? null;

            if (dryRun) {
                attempts.push({
                    rule_id: ruleId,
                    slot: slotIso,
                    action: 'skipped',
                    message: `${slotLabel}: dry-run — ${availability}${shortEventId ? ` (${shortEventId}${numericEventId ? `/#${numericEventId}` : ''})` : ''}, hero=${budapestSlotKey(slotIso)}.`,
                });
                continue;
            }

            if (availability === 'already') {
                attempts.push({
                    rule_id: ruleId,
                    slot: slotIso,
                    action: 'skipped',
                    message: `${slotLabel}: már bejelentkezve.`,
                });
                continue;
            }

            if (availability === 'full') {
                attempts.push({
                    rule_id: ruleId,
                    slot: slotIso,
                    action: 'unavailable',
                    message: `${slotLabel}: betelt, várólista nem elérhető.`,
                });
                continue;
            }

            const allowWaitlist = config.waitlist || Boolean(rule?.waitlist_ok);

            if (availability === 'waitlist' && !allowWaitlist) {
                attempts.push({
                    rule_id: ruleId,
                    slot: slotIso,
                    action: 'unavailable',
                    message: `${slotLabel}: csak várólista érhető el, de nincs engedélyezve.`,
                });
                continue;
            }

            if (!shortEventId) {
                attempts.push({
                    rule_id: ruleId,
                    slot: slotIso,
                    action: 'failed',
                    message: `${slotLabel}: esemény azonosító nem található.`,
                });
                continue;
            }

            try {
                const result = await bookEvent(page, {
                    baseUrl: config.baseUrl,
                    portalSiteId: config.portalSiteId,
                    shortEventId,
                    numericEventId,
                    expectedSlotIso: slotIso,
                    allowWaitlist: availability === 'waitlist' || allowWaitlist,
                });

                attempts.push({
                    rule_id: ruleId,
                    slot: slotIso,
                    action: result.action,
                    message: `${slotLabel}: ${result.message}`,
                });
            } catch (error) {
                await saveScreenshot(page, `fail-${ruleId}`).catch(() => {});
                attempts.push({
                    rule_id: ruleId,
                    slot: slotIso,
                    action: 'failed',
                    message: `${slotLabel}: ${error.message}`,
                });
                errors.push(error.message);
            }
        }
    } catch (error) {
        if (page) {
            await saveScreenshot(page, 'fatal').catch(() => {});
        }
        errors.push(error.message);
        console.log(JSON.stringify({
            ok: false,
            dry_run: dryRun,
            attempts,
            errors,
            message: error.message,
        }));
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }

    const booked = attempts.filter((row) => row.action === 'booked').length;
    const waitlisted = attempts.filter((row) => row.action === 'waitlisted').length;
    const skipped = attempts.filter((row) => row.action === 'skipped').length;
    const unavailable = attempts.filter((row) => row.action === 'unavailable').length;
    const failed = attempts.filter((row) => row.action === 'failed').length;

    let message;
    if (dryRun) {
        message = `Dry-run: ${attempts.length} slot ellenőrizve.`;
    } else if (failed === 0 && errors.length === 0 && booked === 0 && waitlisted === 0 && unavailable > 0) {
        message = 'Nincs foglalható időpont';
    } else if (failed === 0 && errors.length === 0) {
        message = `Kész — foglalva: ${booked}, várólista: ${waitlisted}, kihagyva: ${skipped}.`;
    } else {
        message = `Kész — foglalva: ${booked}, várólista: ${waitlisted}, kihagyva: ${skipped}, hiba: ${failed}.`;
    }

    console.log(JSON.stringify({
        ok: failed === 0 && errors.length === 0,
        dry_run: dryRun,
        attempts,
        errors,
        message,
    }));
}

main().catch((error) => {
    console.log(JSON.stringify({
        ok: false,
        attempts: [],
        errors: [error.message],
        message: error.message,
    }));
    process.exit(1);
});
