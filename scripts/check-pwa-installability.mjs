/**
 * Asks Chrome itself whether the app is installable, via the same
 * Page.getInstallabilityErrors check Chrome runs before firing
 * `beforeinstallprompt`. Manifest/icon/service-worker mistakes are otherwise
 * invisible until someone clicks "Instalar aplicativo" and nothing happens —
 * this catches them in CI/local dev instead.
 *
 * Requires a running server and a local Chrome install. Usage:
 *   node scripts/check-pwa-installability.mjs [url]
 */
import { chromium } from 'playwright-core';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const CHROME_PATH =
    process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const url = process.argv[2] ?? 'http://127.0.0.1/login';

const userDataDir = await mkdtemp(join(tmpdir(), 'chrome-pwa-check-'));

// A real (non-incognito) profile: Chrome refuses to install PWAs from
// incognito/private windows (errorId "in-incognito"), which would otherwise
// masquerade as a manifest/service-worker bug.
const context = await chromium.launchPersistentContext(userDataDir, {
    executablePath: CHROME_PATH,
    headless: true,
});

try {
    const page = context.pages()[0] ?? (await context.newPage());
    const client = await context.newCDPSession(page);

    let beforeInstallPromptFired = false;
    await page.exposeFunction('__markBip', () => {
        beforeInstallPromptFired = true;
    });
    await page.addInitScript(() => {
        window.addEventListener('beforeinstallprompt', () => window.__markBip());
    });

    await page.goto(url, { waitUntil: 'networkidle' });
    // The service worker needs a moment to register/activate before Chrome
    // finishes its installability pass.
    await page.waitForTimeout(4000);

    const { installabilityErrors } = await client.send('Page.getInstallabilityErrors');

    if (installabilityErrors.length > 0) {
        console.error(`PWA is NOT installable at ${url}:`);
        for (const err of installabilityErrors) {
            console.error(`  - ${err.errorId}${err.errorArguments?.length ? ` ${JSON.stringify(err.errorArguments)}` : ''}`);
        }
        process.exitCode = 1;
    } else if (!beforeInstallPromptFired) {
        console.error(
            `Chrome reports no installability errors at ${url}, but beforeinstallprompt did not fire within 4s.`,
        );
        process.exitCode = 1;
    } else {
        console.log(`PWA is installable at ${url} (beforeinstallprompt fired).`);
    }
} finally {
    await context.close();
    await rm(userDataDir, { recursive: true, force: true });
}
