#!/usr/bin/env node
import { chromium } from 'playwright';
import { readFileSync, mkdirSync, writeFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));

function loadEnv() {
    const vars = {};
    for (const line of readFileSync(resolve(__dirname, '../.env'), 'utf8').split('\n')) {
        const m = line.match(/^MOTIBRO_(\w+)=(.*)$/);
        if (m) vars[m[1]] = m[2].trim();
    }
    return vars;
}

const env = loadEnv();
const baseUrl = env.BASE_URL.replace(/\/$/, '');
const debugDir = resolve(__dirname, '../storage/app/motibro/debug');
mkdirSync(debugDir, { recursive: true });

const browser = await chromium.launch({ headless: env.HEADLESS !== 'false' });
const page = await browser.newPage();

await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });

const loginForm = page.locator('form').filter({ has: page.locator('#email') }).first();
await loginForm.locator('#email').fill(env.EMAIL);
await loginForm.locator('#password').fill(env.PASSWORD);
await loginForm.locator('input[type="submit"][name="commit"]').click();

await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

console.log('After login URL:', page.url());
const bodyText = await page.locator('body').innerText();
console.log('Body snippet:', bodyText.slice(0, 500).replace(/\s+/g, ' '));

const hasError = bodyText.includes('Hibás email cím vagy jelszó');
console.log('Login error visible:', hasError);

if (!hasError) {
    await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
    console.log('Home URL:', page.url());
    const hasCalendar = await page.locator('#muser_booking_calendar').count();
    console.log('Calendar present:', hasCalendar > 0);

    const rendben = page.getByRole('button', { name: 'Rendben' });
    if (await rendben.count()) {
        await rendben.first().click({ timeout: 5000 }).catch(() => {});
        console.log('Clicked Rendben modal');
    }
}

const events = await page.evaluate(async (portalSiteId) => {
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth() + 1;
    const d = now.getDate();
    const res = await fetch(`/portals/group_classes_get_events?date=${y}-${m}-${d}&length_days=21&ts=${Date.now()}&portal_site_id=${portalSiteId}`);
    return res.json();
}, '553');

console.log('Events count:', events.length);

const sample = events.find((e) => e.card_html?.includes('Bejelentkezés'))
    ?? events.find((e) => e.card_html?.includes('Várólista'))
    ?? events[0];

if (sample) {
    console.log('Sample start:', sample.start);
    console.log('Sample title:', sample.title);
    const html = sample.card_html || '';
    const bookIdx = html.indexOf('Bejelentkezés');
    const waitIdx = html.indexOf('Várólista');
    console.log('Has Bejelentkezés:', bookIdx >= 0);
    console.log('Has Várólista:', waitIdx >= 0);
    if (bookIdx >= 0) console.log('Book snippet:', html.slice(Math.max(0, bookIdx - 200), bookIdx + 200));
    if (waitIdx >= 0) console.log('Waitlist snippet:', html.slice(Math.max(0, waitIdx - 200), waitIdx + 200));
    writeFileSync(resolve(debugDir, 'sample-card.html'), html);
}

await page.screenshot({ path: resolve(debugDir, 'explore-login.png'), fullPage: true });
await browser.close();
