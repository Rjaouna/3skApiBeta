const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const out = path.resolve('tmp/typography-checks');
(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
  const page = await context.newPage();
  const results = [];
  const ready = async () => {
    await page.locator('[data-site-loader]').waitFor({ state: 'hidden', timeout: 15000 });
    await page.evaluate(() => document.fonts.ready);
  };
  try {
    await page.goto('http://127.0.0.1:8765/login', { waitUntil: 'networkidle' }); await ready();
    await page.locator('#inputEmail').fill('admin@3skpartsauto.com');
    await page.locator('#inputPassword').fill('admin@3skpartsauto.com');
    await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('.login-btn').click()]);
    for (const route of ['/articles', '/panier', '/admin/clients', '/admin/dashboard', '/admin/import', '/admin/import-source', '/mon-profil']) {
      for (const width of [1440, 390, 320]) {
        await page.setViewportSize({ width, height: width < 768 ? 844 : 1000 });
        await page.goto('http://127.0.0.1:8765' + route, { waitUntil: 'networkidle' }); await ready();
        const metrics = await page.evaluate(() => {
          const title = document.querySelector(':is(.page-hero, .cart-page-hero, .clients-page-hero, .dash-hero, .import-page-hero, .profile-page-hero) h3');
          return {
            width: innerWidth, scrollWidth: document.documentElement.scrollWidth,
            title: title ? getComputedStyle(title).fontSize : null,
            headers: [...document.querySelectorAll('thead th')].map(el => getComputedStyle(el).fontSize),
            labels: [...document.querySelectorAll('.form-label')].map(el => getComputedStyle(el).fontSize),
            stockBadges: [...document.querySelectorAll('.product-grid [data-stock-badge]')].map(el => getComputedStyle(el).fontSize),
          };
        });
        assert.equal(metrics.scrollWidth, width);
        if (metrics.title) assert.equal(metrics.title, width < 768 ? '24px' : '28px');
        assert.ok(metrics.headers.every(size => size === '12px'));
        assert.ok(metrics.labels.every(size => size === '13px'));
        assert.ok(metrics.stockBadges.every(size => size === '12px'));
        if (width < 768) {
          const brand = await page.locator('.brand-box').evaluate(el => ({ box: el.getBoundingClientRect().right, image: el.querySelector('img').getBoundingClientRect().right }));
          assert.ok(brand.image <= brand.box + 1, 'Mobile logo must not be cropped');
          const originalAmount = await page.locator('#cartTotalMobile').textContent();
          for (const amount of ['141.05 DH', '12345678.90 DH']) {
            const total = await page.locator('#cartTotalMobile').evaluate((el, amount) => {
              el.textContent = amount;
              const r = el.getBoundingClientRect(), p = el.parentElement.getBoundingClientRect();
              return { overflow: el.scrollWidth > el.clientWidth, right: r.right, bottom: r.bottom, parentBottom: p.bottom, font: getComputedStyle(el).fontSize };
            }, amount);
            assert.equal(total.font, '12px');
            assert.equal(total.overflow, false);
            assert.ok(total.right <= width && total.bottom <= total.parentBottom + 1, JSON.stringify(total));
          }
          await page.locator('#cartTotalMobile').evaluate((el, amount) => { el.textContent = amount; }, originalAmount);
          await page.screenshot({ path: path.join(out, `${route.replaceAll('/', '-')}-${width}.png`) });
        }
        results.push({ route, ...metrics });
        console.log(`OK ${route} ${width}: title, table, labels, stock badges, mobile cart total`);
      }
    }
  } finally {
    fs.writeFileSync(path.join(out, 'checks.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(e => { console.error(e); process.exitCode = 1; });
