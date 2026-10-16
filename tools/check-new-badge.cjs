const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const base = 'http://127.0.0.1:8765';
const widths = [320, 390, 768, 1440, 1920];
const output = 'tmp/new-badge';
fs.mkdirSync(output, { recursive: true });

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const results = [];
  try {
    for (const role of ['client', 'admin']) {
      const context = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1440, height: 1000 } });
      await context.route('**/api/cart**', route => ['GET', 'HEAD', 'OPTIONS'].includes(route.request().method()) ? route.continue() : route.abort());
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
      const email = process.env[`DESIGN_${role.toUpperCase()}_EMAIL`] || `${role}@3skpartsauto.com`;
      await page.locator('#inputEmail').fill(email);
      await page.locator('#inputPassword').fill(process.env[`DESIGN_${role.toUpperCase()}_PASSWORD`] || email);
      await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('.login-btn').click()]);
      const cartBefore = await (await context.request.get(`${base}/api/cart`)).json();

      // Visual fixtures only: keep real products; never write dates, stock or badges to the database.
      let showNew = true;
      await context.route('**/api/articles?**', async route => {
        const response = await route.fetch();
        const payload = await response.json();
        payload.items = payload.items.slice(0, 5).map((item, index) => ({ ...item, isNew: showNew && index < 4, RealStock: index === 0 ? 0 : item.RealStock }));
        payload.total = payload.items.length;
        payload.pages = 1;
        await route.fulfill({ response, json: payload });
      });

      for (const width of widths) {
        await page.setViewportSize({ width, height: 1000 });
        for (const view of ['home', 'catalog']) {
          await page.goto(`${base}${view === 'home' ? '/' : '/articles?sort=created_desc&newDays=90'}`, { waitUntil: 'networkidle' });
          await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
          const selector = view === 'home' ? '[data-new-grid]' : '#grid';
          await page.waitForFunction(root => document.querySelectorAll(root + ' .badge-newimg img').length === 4, selector);
          await page.locator(`${selector} .badge-newimg img`).evaluateAll(images => Promise.all(images.map(image => image.decode())));
          const metrics = await page.locator(`${selector} .product-card`).evaluateAll(cards => cards.map(card => {
            const badge = card.querySelector('.badge-newimg img');
            const photo = card.querySelector('.product-img');
            const box = card.getBoundingClientRect();
            const photoOffset = photo.getBoundingClientRect().top - box.top;
            if (!badge) return { padding: getComputedStyle(card.querySelector('.product-card__image')).paddingTop, photoOffset };
            const ribbon = badge.getBoundingClientRect();
            const intersects = other => {
              const rect = other.getBoundingClientRect();
              return Math.min(rect.right, ribbon.right) > Math.max(rect.left, ribbon.left) && Math.min(rect.bottom, ribbon.bottom) > Math.max(rect.top, ribbon.top);
            };
            return {
              width: ribbon.width,
              ratio: ribbon.width / ribbon.height,
              expectedWidth: Math.min(144, card.querySelector('.product-card__image').clientWidth),
              inside: ribbon.left >= box.left && ribbon.right <= box.right && ribbon.top >= box.top && ribbon.bottom <= box.bottom,
              abovePhoto: ribbon.bottom <= photo.getBoundingClientRect().top,
              overlapsBadge: [...card.querySelectorAll('.discount-badge, .wishlist-button')].some(intersects),
              source: badge.getAttribute('src'),
              decoded: badge.naturalWidth === 1072 && badge.naturalHeight === 223,
              photoOffset,
            };
          }));
          for (const item of metrics) {
            if (!item.width) { assert.equal(item.padding, '76px', 'Mixed cards reserve the same badge row'); continue; }
            assert.ok(Math.abs(item.width - item.expectedWidth) < 1);
            assert.ok(item.width >= 100, `Readable ribbon at ${role} ${view} ${width}: ${JSON.stringify(item)}`);
            assert.ok(Math.abs(item.ratio - 1072 / 223) < .02);
            assert.ok(item.inside && item.abovePhoto && !item.overlapsBadge && item.decoded, JSON.stringify({ role, view, width, item }));
            assert.equal(item.source, '/img/new.png');
          }
          assert.ok(Math.max(...metrics.map(item => item.photoOffset)) - Math.min(...metrics.map(item => item.photoOffset)) < 1, 'Mixed cards keep their photos aligned');
          assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
          if ([390, 1440, 1920].includes(width)) {
            await page.locator(selector).evaluate(grid => {
              const header = document.querySelector('.site__header');
              const filters = document.querySelector('.catalog-page [data-catalog-filters]');
              scrollTo({ top: grid.getBoundingClientRect().top + scrollY - header.offsetHeight - (filters?.offsetHeight || 0) - 12, behavior: 'instant' });
            });
            await page.screenshot({ path: `${output}/${role}-${view}-${width}.png` });
          }
          results.push({ role, view, width, badgeWidths: metrics.filter(item => item.width).map(item => Math.round(item.width)) });
        }
      }
      showNew = false;
      for (const path of ['/', '/articles?sort=created_desc&newDays=90']) {
        await page.goto(base + path, { waitUntil: 'networkidle' });
        await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
        assert.equal(await page.locator('.product-grid .badge-newimg').count(), 0);
        assert.ok((await page.locator('.product-grid .product-card__image').evaluateAll(images => images.map(image => getComputedStyle(image).paddingTop))).every(padding => padding === '0px'), 'No extra spacing when there are no new badges');
      }
      assert.deepEqual(errors, []);
      assert.deepEqual(await (await context.request.get(`${base}/api/cart`)).json(), cartBefore);
      await context.close();
    }
    console.log(JSON.stringify({ ok: true, results }, null, 2));
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
