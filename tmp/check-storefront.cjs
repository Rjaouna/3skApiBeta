const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const base = 'http://127.0.0.1:8765';
const output = path.resolve('tmp/design-audit/storefront-detail');

(async () => {
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({ headless: true, executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
  const results = [];
  try {
    for (const role of ['public', 'admin', 'client']) {
      const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
      const page = await context.newPage();
      const ready = async () => page.locator('[data-site-loader]').waitFor({ state: 'hidden' }).catch(() => {});
      if (role !== 'public') {
        await page.goto(base + '/login', { waitUntil: 'networkidle' });
        await ready();
        await page.locator('#inputEmail').fill(`${role}@3skpartsauto.com`);
        await page.locator('#inputPassword').fill(`${role}@3skpartsauto.com`);
        await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('.login-btn').click()]);
      }
      for (const width of [1440, 390]) {
        await page.setViewportSize({ width, height: width === 390 ? 844 : 1000 });
        await page.goto(base + '/', { waitUntil: 'networkidle' });
        await ready();
        const height = await page.evaluate(() => document.documentElement.scrollHeight);
        for (let y = 0; y < height; y += 600) {
          await page.evaluate(y => scrollTo({ top: y, behavior: 'instant' }), y);
          await page.waitForTimeout(150);
        }
        await page.waitForTimeout(1000);
        await page.evaluate(() => scrollTo({ top: 0, behavior: 'instant' }));
        await page.screenshot({ path: path.join(output, `${role}-home-${width}.png`), fullPage: true });
      }
      if (role !== 'public') {
        for (const width of [1920, 1440, 768, 390, 320]) {
          await page.setViewportSize({ width, height: width < 768 ? 844 : 1000 });
          await page.goto(base + '/articles', { waitUntil: 'networkidle' });
          await ready();
          await page.locator('#grid .product-card').first().waitFor();
          const layout = await page.evaluate(() => {
            const cards = [...document.querySelectorAll('#grid .product-card')];
            const clipped = cards.flatMap(card => [...card.querySelectorAll('.bottomFooterWrapper, .qty-stepper, .addcart, .product-img')].filter(element => element.getBoundingClientRect().right > card.getBoundingClientRect().right + 1).map(element => element.className));
            return { width: innerWidth, scrollWidth: document.documentElement.scrollWidth, cards: cards.length, clipped, filtersOpen: document.querySelector('[data-catalog-filters]').open, exactStockRows: document.querySelectorAll('.catalog-info-label').length ? [...document.querySelectorAll('.catalog-info-label')].filter(element => element.textContent.trim() === 'Stock').length : 0 };
          });
          if (layout.scrollWidth > width || layout.clipped.length || layout.filtersOpen !== (width >= 992)) throw new Error(`Bad layout ${role} ${width}: ${JSON.stringify(layout)}`);
          if (role === 'client' && layout.exactStockRows) throw new Error('Client stock visibility changed');
          const card = page.locator('#grid .product-card').first();
          await card.scrollIntoViewIfNeeded();
          await page.waitForTimeout(200);
          await page.screenshot({ path: path.join(output, `${role}-catalog-${width}.png`) });
          if (width >= 992) {
            await page.evaluate(() => scrollTo({ top: 650, behavior: 'instant' }));
            await page.waitForTimeout(200);
            const sticky = await page.evaluate(() => ({
              headerBottom: document.querySelector('.site__header').getBoundingClientRect().bottom,
              filtersTop: document.querySelector('[data-catalog-filters]').getBoundingClientRect().top,
              filtersBottom: document.querySelector('[data-catalog-filters]').getBoundingClientRect().bottom,
            }));
            if (sticky.filtersTop < sticky.headerBottom || sticky.filtersBottom > 1000) throw new Error(`Filters hidden by header: ${JSON.stringify(sticky)}`);
          }
          results.push({ role, ...layout });
        }
      }
      await context.close();
    }
    console.log(JSON.stringify(results, null, 2));
  } finally {
    fs.writeFileSync(path.join(output, 'checks.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
