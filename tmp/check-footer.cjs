const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const base = 'http://127.0.0.1:8765';
const out = path.resolve('tmp/footer-qa');
(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch({ headless: true, executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
  const results = [];
  try {
    for (const role of ['public', 'client']) {
      const context = await browser.newContext({ reducedMotion: 'reduce' });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', e => errors.push(e.message));
      const ready = async () => {
        await page.locator('[data-site-loader]').waitFor({ state: 'hidden', timeout: 15000 });
        await page.evaluate(() => document.fonts.ready);
        const refuse = page.getByRole('button', { name: 'Refuser', exact: true });
        if (await refuse.isVisible()) await refuse.click();
      };
      if (role === 'client') {
        await page.goto(base + '/login', { waitUntil: 'networkidle' }); await ready();
        await page.locator('#inputEmail').fill('client@3skpartsauto.com');
        await page.locator('#inputPassword').fill('client@3skpartsauto.com');
        await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('.login-btn').click()]);
      }
      for (const width of [1920, 1680, 1440, 1024, 768, 390, 320]) {
        await page.setViewportSize({ width, height: width < 768 ? 844 : 1000 });
        await page.goto(base + '/', { waitUntil: 'networkidle' }); await ready();
        const footer = page.locator('.site-footer');
        await footer.scrollIntoViewIfNeeded();
        await page.waitForTimeout(400);
        await page.waitForFunction(() => document.querySelector('.site-footer__logo img').naturalWidth > 0);
        const metrics = await page.evaluate(() => {
          const footer = document.querySelector('.site-footer');
          const clipped = [...footer.querySelectorAll('p, a, button, h2, .site-footer__inner > *, .site-footer__payment')].filter(el => el.getBoundingClientRect().right > innerWidth + 1 || el.scrollWidth > el.clientWidth + 1 && el.clientWidth > 0).map(el => ({ class: el.className, label: el.textContent.slice(0, 50) }));
          return {
            width: innerWidth, scrollWidth: document.documentElement.scrollWidth,
            columns: getComputedStyle(document.querySelector('.site-footer__inner')).gridTemplateColumns.split(' ').length,
            stats: [...document.querySelectorAll('.site-stats-strip__value')].map(el => el.textContent.trim()),
            statsColumns: getComputedStyle(document.querySelector('.site-stats-strip__grid')).gridTemplateColumns.split(' ').length,
            titleSizes: [...footer.querySelectorAll('h2')].map(el => getComputedStyle(el).fontSize),
            address: footer.querySelector('.site-footer__address').textContent.trim(),
            mail: footer.querySelector('[href^="mailto:"]').getAttribute('href'),
            map: footer.querySelector('iframe').getAttribute('src'),
            bodyColor: getComputedStyle(footer).color,
            clipped,
          };
        });
        results.push({ role, ...metrics, errors: [...errors] });
        assert.equal(metrics.scrollWidth, width, `${role} ${width} page overflow`);
        assert.deepEqual(metrics.clipped, [], `${role} ${width} clipped footer content`);
        assert.equal(metrics.stats.length, 5);
        assert.equal(metrics.stats[4], '2014');
        assert.equal(metrics.columns, width <= 767 ? 1 : width <= 1199 ? 3 : 4);
        assert.equal(metrics.address, 'HAY ENNASR 2 Nr 6 lahraouyine Casablanca');
        assert.equal(metrics.mail, 'mailto:3sk.partsauto@gmail.com');
        assert.ok(metrics.map.includes('33.528667,-7.550028'));
        if ([1920, 1680, 390, 320].includes(width)) {
          await footer.screenshot({ path: path.join(out, `${role}-footer-${width}.png`), style: '.site__header, .showcase-header, .whatsapp-float { visibility: hidden !important; }' });
          await page.locator('.site-stats-strip').screenshot({ path: path.join(out, `${role}-stats-${width}.png`), style: '.site__header, .showcase-header, .whatsapp-float { visibility: hidden !important; }' });
        }
        await footer.locator('.phone-contact-trigger').click();
        await page.locator('#phoneContactModal').waitFor({ state: 'visible' });
        assert.equal(await page.locator('#phoneContactModal a[href^="tel:"]').count(), 4);
        await page.locator('#phoneContactModal [data-bs-dismiss="modal"]').first().click();
        await page.locator('#phoneContactModal').waitFor({ state: 'hidden' });
        console.log(`OK ${role} ${width}: footer, 5 real stats, 4 phone links`);
      }
      if (role === 'public') {
        await page.goto(base + '/login', { waitUntil: 'networkidle' }); await ready();
        assert.equal(await page.locator('.site-footer--showcase').count(), 1);
        await page.locator('.site-footer').screenshot({ path: path.join(out, 'login-footer-320.png'), style: '.showcase-header, .whatsapp-float { visibility: hidden !important; }' });
      }
      assert.deepEqual(errors, []);
      await context.close();
    }
  } finally {
    fs.writeFileSync(path.join(out, 'checks.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(e => { console.error(e); process.exitCode = 1; });
