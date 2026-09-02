import { mkdirSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const DEBUG_DIR = resolve(__dirname, '../../storage/app/motibro/debug');

export async function saveScreenshot(page, label) {
    mkdirSync(DEBUG_DIR, { recursive: true });
    const path = resolve(DEBUG_DIR, `${label}-${Date.now()}.png`);
    await page.screenshot({ path, fullPage: true });
    return path;
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

export async function bookEvent(page, { baseUrl, portalSiteId, shortEventId, allowWaitlist }) {
    const eventUrl = `${baseUrl}/events?back=true&id=${shortEventId}&portal_site_id=${portalSiteId}`;
    await page.goto(eventUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

    const body = await page.locator('body').innerText();
    if (/Bejelentkezve|már jelentkez/i.test(body)) {
        return { action: 'skipped', message: 'Már bejelentkezve a Motibro oldalon.' };
    }

    const loginLink = page.locator('a[href*="/login"]').filter({ hasText: 'Bejelentkezés' });
    if (await loginLink.count()) {
        throw new Error('Nincs aktív Motibro munkamenet — újra be kell jelentkezni.');
    }

    const bookButton = page.locator('a.btn-success, button.btn-success').filter({ hasText: /^Bejelentkezés$/ });
    if (await bookButton.count()) {
        await bookButton.first().click();
        return waitForBookingResult(page, 'booked');
    }

    const waitlistButton = page.locator('a.btn-success, button.btn-success').filter({ hasText: /Várólistára/i });
    if (allowWaitlist && await waitlistButton.count()) {
        await waitlistButton.first().click();
        return waitForBookingResult(page, 'waitlisted');
    }

    if (/betelt/i.test(body)) {
        return { action: 'failed', message: 'Az óra betelt, várólista nincs engedélyezve.' };
    }

    return { action: 'failed', message: 'Nincs elérhető foglalás gomb az esemény oldalon.' };
}
