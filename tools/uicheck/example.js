// Vorlage fuer Browser-Checks des SvelteKit-Frontends gegen die Wegwerf-DB von tools/uicheck/uicheck.
//
// Vorbereitung:
//   tools/uicheck/uicheck start fresh            # API auf Port 8012, Wegwerf-DB, Login Bart/test123
//   cd frontend && VITE_API_URL=http://127.0.0.1:8012/api/v1 npm run dev -- --port 5173 --strictPort --host localhost
//     (Port 5173 ist Pflicht: die CORS-Freigabe der API gilt nur dafuer)
//   Playwright: `npm i playwright-core` in einem Wegwerf-Ordner (nicht im Repo), Pfad per PLAYWRIGHT_PATH,
//     z. B. PLAYWRIGHT_PATH=/tmp/pw/node_modules/playwright-core. Chromium kommt aus ~/.cache/ms-playwright.
// Aufruf: node tools/uicheck/example.js <ausgabeverzeichnis>   (Screenshots landen dort)
// Danach: uicheck stop und den Vite-Prozess per PID beenden (nie `pkill -f`).
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'playwright-core');
const BASE = process.env.UICHECK_FRONTEND || 'http://localhost:5173';
const out = (process.argv[2] || '.').replace(/\/?$/, '/');

(async () => {
    const b = await chromium.launch();
    const p = await b.newPage({ viewport: { width: 1400, height: 900 } });
    // Mitschreiben, was das Frontend an die API schickt (zeigt z. B. gebuendelte Anfragen).
    p.on('request', (r) => {
        if (r.method() === 'POST') console.log('POST', r.url().split('/api/v1')[1], r.postData() ?? '');
    });

    await p.goto(`${BASE}/login`);
    await p.waitForTimeout(1500);
    await p.locator('input').first().fill('Bart');
    await p.locator('input[type=password]').fill('test123');
    await p.keyboard.press('Enter');
    await p.waitForTimeout(2000);

    // Mission Control: eine bereitstehende Mission starten (falls noetig).
    await p.goto(`${BASE}/lobby`);
    await p.waitForTimeout(1500);
    const start = p.locator('button', { hasText: 'Mission starten' });
    if (await start.count()) {
        await start.click();
        await p.waitForTimeout(2500);
    }

    await p.goto(`${BASE}/colony`);
    await p.waitForTimeout(2500);
    await p.screenshot({ path: out + '1-colony.png' });

    // Hex-Feld anklicken (Bildschirmkoordinaten) und das Seitenpanel ansehen.
    await p.mouse.click(405, 497);
    await p.waitForTimeout(1000);
    await p.screenshot({ path: out + '2-tile-panel.png' });

    await b.close();
})();
