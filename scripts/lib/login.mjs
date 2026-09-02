export async function dismissModals(page) {
    const rendben = page.getByRole('button', { name: 'Rendben' });
    if (await rendben.count()) {
        await rendben.first().click({ timeout: 3000 }).catch(() => {});
    }

    const cookieOk = page.locator('.cc-btn.cc-btn-primary, button:has-text("Elfogad")');
    if (await cookieOk.count()) {
        await cookieOk.first().click({ timeout: 2000 }).catch(() => {});
    }
}

export async function login(page, { baseUrl, email, password, eventId = null }) {
    const loginUrl = eventId
        ? `${baseUrl}/login?page=booking&event=${eventId}`
        : `${baseUrl}/login`;

    await page.goto(loginUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

    const loginForm = page.locator('form[action*="login_login"]').first();
    await loginForm.locator('#email').fill(email);
    await loginForm.locator('#password').fill(password);

    await Promise.all([
        page.waitForResponse((response) => response.url().includes('login_login'), { timeout: 45000 }),
        loginForm.locator('input[type="submit"][name="commit"]').click(),
    ]);

    await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

    const body = await page.locator('body').innerText();
    if (body.includes('Hibás email cím vagy jelszó')) {
        throw new Error('Hibás Motibro email cím vagy jelszó.');
    }

    if (page.url().includes('/login') && !eventId) {
        throw new Error('Bejelentkezés sikertelen — még mindig a login oldalon vagyunk.');
    }

    await dismissModals(page);
}
