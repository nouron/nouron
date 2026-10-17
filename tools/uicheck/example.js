const { chromium } = require('/home/mg/workspace/nouron/node_modules/playwright');
// Vorlage fuer Browser-Checks mit tools/uicheck/uicheck (Login Bart/test123, Port aus UICHECK_PORT, Standard 8012).
// Aufruf: node tools/uicheck/example.js <ausgabeverzeichnis>   (Screenshots landen dort)
const PORT = process.env.UICHECK_PORT || '8012';
const BASE = `http://127.0.0.1:${PORT}`;
const out = (process.argv[2] || '.').replace(/\/?$/, '/');
(async () => {
    const b = await chromium.launch();
    const p = await b.newPage({ viewport: { width: 1500, height: 900 } });
    p.on('response', async (r) => {
        if (r.url().includes('/colony/') && r.request().method() === 'POST')
            console.log('POST', r.url(), r.status(), (await r.text()).slice(0, 300));
    });
    await p.goto(`${BASE}/login`);
    await p.fill('input[name=username]', 'Bart');
    await p.fill('input[type=password]', 'test123');
    await p.keyboard.press('Enter');
    await p.waitForLoadState('networkidle');
    await p.goto(`${BASE}/lobby`);
    await p.click('text=Mission starten');
    await p.waitForLoadState('networkidle');
    await p.goto(`${BASE}/colony/view`);
    await p.waitForTimeout(1500);
    console.log('url', p.url());
    await p.screenshot({ path: out + '1-start.png' });
    await p.click('g[data-q="1"][data-r="0"]');
    await p.waitForTimeout(500);
    await p.screenshot({ path: out + '2-harvester-selected.png' });
    const mv = p.locator('button', { hasText: 'verlegen' });
    console.log('move buttons', await mv.count());
    if (await mv.count()) {
        await mv.first().click();
        await p.waitForTimeout(500);
        await p.screenshot({ path: out + '3-move-mode.png' });
        const t = p.locator('g[data-q="-3"][data-r="1"]');
        await t.click();
        await p.waitForTimeout(400);
        const box = await t.boundingBox();
        await p.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
        await p.mouse.down();
        await p.waitForTimeout(1300);
        await p.screenshot({ path: out + '4-holding.png' });
        await p.mouse.up();
        await p.waitForTimeout(1500);
        await p.screenshot({ path: out + '5-after-move.png' });
    }
    await b.close();
})();
