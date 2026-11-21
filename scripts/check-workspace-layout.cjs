// Static Blade preview with synthetic data, not a login bypass or live server test.
const path = require('node:path');
const { createRequire } = require('node:module');
const { pathToFileURL } = require('node:url');
const assert = require('node:assert/strict');
const load = createRequire(path.join(process.env.HF_VISUAL_RUNTIME, 'package.json'));
const { chromium } = load('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    try {
        const page = await browser.newPage();
        await page.route('http**', route => route.abort());
        const directory = path.resolve('storage/app/visual-preview');
        for (const [name, width, height] of [['desktop', 1440, 1100], ['mobile', 390, 844]]) {
            await page.setViewportSize({ width, height });
            await page.goto(pathToFileURL(path.join(directory, 'overview.html')).href);
            await page.addScriptTag({ path: load.resolve('alpinejs/dist/cdn.min.js') });
            await page.screenshot({ path: path.join(directory, `${name}.png`), fullPage: true });
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, `${name} overflows horizontally`);
            await page.keyboard.press('Control+k');
            await page.getByRole('dialog', { name: 'Ke mana kita?' }).waitFor({ state: 'visible' });
            await page.getByRole('searchbox', { name: 'Cari menu', exact: true }).fill('Music');
            await page.waitForFunction(() => [...document.querySelectorAll('dialog[x-ref="search"] .hf-command-results button')].filter(button => button.getClientRects().length > 0).length === 1);
            assert.equal(await page.locator('dialog[x-ref="search"] button[wire\\:click]:visible').count(), 1);
            await page.keyboard.press('Escape');
            await page.getByRole('dialog', { name: 'Ke mana kita?' }).waitFor({ state: 'hidden' });
            if (name === 'mobile') {
                await page.getByRole('button', { name: 'Buka menu', exact: true }).click();
                await page.getByRole('dialog', { name: 'Hollowfang workspace' }).waitFor({ state: 'visible' });
                assert.equal(await page.locator('dialog[x-ref="mobileNav"] nav button').count(), 11);
                await page.getByRole('button', { name: 'Tutup menu', exact: true }).click();
            }
            console.log(`${name}: no overflow; menu search and dialog checks passed`);
        }
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
