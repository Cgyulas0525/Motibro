import { mkdirSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';
import { budapestSlotKey } from './calendar.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const DEBUG_DIR = resolve(__dirname, '../../storage/app/motibro/debug');

export async function saveScreenshot(page, label) {
    mkdirSync(DEBUG_DIR, { recursive: true });
    const path = resolve(DEBUG_DIR, `${label}-${Date.now()}.png`);
    await page.screenshot({ path, fullPage: true });
    return path;
}

function parseHeroSlotKey(heroText) {
    const match = heroText.match(/(\d{4})\.\s*([a-záéíóöőúüű]+)\.?\s*(\d{1,2})[.,]?\s*[^0-9]*(\d{1,2}):(\d{2})/i);
    if (!match) {
        return null;
    }

    const months = {
        jan: '01', feb: '02', mar: '03', ápr: '04', apr: '04', máj: '05', maj: '05',
        jún: '06', jun: '06', júl: '07', jul: '07', aug: '08', szept: '09', sep: '09',
        okt: '10', oct: '10', nov: '11', dec: '12',
    };
    const monthKey = match[2].slice(0, 4).toLowerCase();
    const month = months[monthKey] ?? months[monthKey.slice(0, 3)];
    if (!month) {
        return null;
    }

    const day = String(match[3]).padStart(2, '0');
    const hour = String(match[4]).padStart(2, '0');
    const minute = String(match[5]).padStart(2, '0');

    return `${match[1]}-${month}-${day}T${hour}:${minute}`;
}

async function readHeroSlotKey(page) {
    const hero = page.locator('#hero_content');
    if (!(await hero.count())) {
        return null;
    }
    return parseHeroSlotKey(await hero.innerText());
}

async function waitForBookingResult(page, expectedAction) {
    const successModal = page.locator('#SuccessModal');
    await successModal.waitFor({ state: 'visible', timeout: 15000 }).catch(() => {});

    if (await successModal.isVisible()) {
        await page.locator('#success_modal_ok_close_button').click({ timeout: 3000 }).catch(() => {});
        return {
            action: expectedAction,
            message: expectedAction === 'waitlisted'
                ? 'Sikeres várólistára jelentkezés.'
                : 'Sikeres bejelentkezés az órára.',
        };
    }

    const purchaseModal = page.locator('#PurchasePassInfo');
    if (await purchaseModal.isVisible()) {
        return { action: 'failed', message: 'Érvényes bérlet szükséges a foglaláshoz.' };
    }

    const errorModal = page.locator('#ErrorModal');
    if (await errorModal.isVisible()) {
        const message = (await errorModal.innerText()).replace(/\s+/g, ' ').trim().slice(0, 400);
        await page.locator('#error_modal_close_button').click({ timeout: 3000 }).catch(() => {});
        return { action: 'failed', message: message || 'MotiBro hiba modal.' };
    }

    const body = await page.locator('body').innerText();
    if (/Bejelentkezve|már jelentkez/i.test(body)) {
        return { action: 'skipped', message: 'Már bejelentkezve (UI).' };
    }

    return {
        action: expectedAction,
        message: 'Foglalás gomb megnyomva, egyértelmű visszajelzés nem jött.',
    };
}

export async function bookEvent(page, { baseUrl, portalSiteId, shortEventId, allowWaitlist, expectedSlotIso, numericEventId }) {
    const expectedKey = budapestSlotKey(expectedSlotIso);
    const eventUrl = `${baseUrl}/events?back=true&id=${shortEventId}&portal_site_id=${portalSiteId}`;
    await page.goto(eventUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

    const heroKey = await readHeroSlotKey(page);
    if (!heroKey) {
        throw new Error('Az esemény oldalon nem olvasható az időpont (#hero_content).');
    }
    if (heroKey !== expectedKey) {
        throw new Error(`Rossz esemény oldal: várt ${expectedKey}, a hero ${heroKey} (event ${shortEventId}${numericEventId ? ` / #${numericEventId}` : ''}).`);
    }

    const body = await page.locator('body').innerText();
    if (/Bejelentkezve|már jelentkez/i.test(body)) {
        return { action: 'skipped', message: 'Már bejelentkezve a Motibro oldalon.' };
    }

    const loginLink = page.locator('a[href*="/login"]').filter({ hasText: 'Bejelentkezés' });
    if (await loginLink.count()) {
        throw new Error('Nincs aktív Motibro munkamenet — újra be kell jelentkezni.');
    }

    const heroCard = page.locator('.card.mb-8').first();
    const bookButton = heroCard.locator('a.btn-success.btn-xlg, button.btn-success.btn-xlg').filter({ hasText: /^Bejelentkezés$/ });
    if (await bookButton.count()) {
        await bookButton.first().click();
        return waitForBookingResult(page, 'booked');
    }

    const waitlistButton = heroCard.locator('a.btn-success, button.btn-success').filter({ hasText: /Várólistára/i });
    if (allowWaitlist && await waitlistButton.count()) {
        await waitlistButton.first().click();
        return waitForBookingResult(page, 'waitlisted');
    }

    if (/betelt/i.test(body)) {
        return { action: 'unavailable', message: 'Az óra betelt, várólista nincs engedélyezve.' };
    }

    return { action: 'unavailable', message: 'Nincs elérhető foglalás gomb az esemény hero szekciójában.' };
}
