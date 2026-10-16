const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const base = 'http://127.0.0.1:8765';
const output = 'tmp/latest-articles';
fs.mkdirSync(output, { recursive: true });

const expected = JSON.parse(execFileSync('C:/wamp64/bin/php/php8.4.15/php.exe', ['-r', `
require 'vendor/autoload.php';
(new Symfony\\Component\\Dotenv\\Dotenv())->bootEnv('.env');
$kernel = new App\\Kernel('dev', true);
$kernel->boot();
$connection = $kernel->getContainer()->get('doctrine')->getConnection();
echo json_encode($connection->fetchAllAssociative('SELECT Id, UniqueId, sysCreatedDate FROM article WHERE sysCreatedDate IS NOT NULL ORDER BY sysCreatedDate DESC, UniqueId ASC LIMIT 30'));
$kernel->shutdown();
`], { encoding: 'utf8' }));

async function ready(page) {
  await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
  await page.evaluate(() => document.fonts.ready);
}

(async () => {
  assert.ok(expected.length >= 5, 'The local test catalog needs at least five dated articles');
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
      await ready(page);
      const email = process.env[`DESIGN_${role.toUpperCase()}_EMAIL`] || `${role}@3skpartsauto.com`;
      await page.locator('#inputEmail').fill(email);
      await page.locator('#inputPassword').fill(process.env[`DESIGN_${role.toUpperCase()}_PASSWORD`] || email);
      await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('.login-btn').click()]);
      const cartBefore = await (await context.request.get(`${base}/api/cart`)).json();
      const latest = await (await context.request.get(`${base}/api/articles?sort=created_desc&newDays=90&limit=5`)).json();
      assert.deepEqual(latest.items.map(item => item.UniqueId), expected.slice(0, 5).map(item => item.UniqueId));

      for (const width of [390, 768, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.goto(base, { waitUntil: 'networkidle' });
        await ready(page);
        await page.waitForFunction(() => document.querySelectorAll('[data-new-grid] .product-card').length === 5);
        assert.deepEqual(await page.locator('[data-new-grid] .product-card').evaluateAll(cards => cards.map(card => card.dataset.cardCode)), expected.slice(0, 5).map(item => item.Id));
        assert.equal(await page.locator('[data-new-error]').isVisible(), false);
        assert.equal(await page.locator('[data-new-grid] .badge-newimg').count(), latest.items.filter(item => item.isNew).length);
        if (role === 'client') assert.ok((await page.locator('[data-new-grid] [data-stock-badge]').allTextContents()).every(value => ['En stock', 'Non dispo'].includes(value)));
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        if (width < 992) {
          await page.locator('[data-new-section]').evaluate(section => {
            const header = document.querySelector('.site__header');
            scrollTo({ top: section.getBoundingClientRect().top + scrollY - header.offsetHeight - 12, behavior: 'instant' });
          });
          await page.screenshot({ path: `${output}/${role}-${width}.png` });
        } else {
          await page.locator('[data-new-section]').screenshot({ path: `${output}/${role}-${width}.png` });
        }
        results.push({ role, width, cards: 5, codes: latest.items.map(item => item.Id) });
      }

      const response = page.waitForResponse(response => {
        const url = new URL(response.url());
        return url.pathname === '/api/articles' && url.searchParams.get('sort') === 'created_desc' && url.searchParams.get('page') === '1';
      });
      await page.locator('.latest-arrivals-link').click();
      const catalog = await (await response).json();
      assert.ok(page.url().includes('sort=created_desc'));
      assert.deepEqual(catalog.items.map(item => item.UniqueId), expected.slice(0, 24).map(item => item.UniqueId));
      await page.waitForFunction(() => document.querySelectorAll('#grid .product-card').length >= 24);
      const next = page.waitForResponse(response => {
        const url = new URL(response.url());
        return url.pathname === '/api/articles' && url.searchParams.get('page') === '2';
      });
      await page.locator('#infiniteScrollSentinel').scrollIntoViewIfNeeded();
      const nextResponse = await next;
      assert.equal(new URL(nextResponse.url()).searchParams.get('sort'), 'created_desc');
      await page.waitForFunction(() => document.querySelectorAll('#grid .product-card').length >= 48);
      assert.deepEqual(await page.locator('#grid .product-card').evaluateAll(cards => cards.slice(0, 30).map(card => card.dataset.code)), expected.map(item => item.Id));

      const resetResponse = page.waitForResponse(response => {
        const url = new URL(response.url());
        return url.pathname === '/api/articles' && !url.searchParams.has('sort');
      });
      await page.locator('#resetFilters').click();
      assert.equal((await resetResponse).status(), 200);
      assert.equal(new URL(page.url()).searchParams.has('sort'), false);

      for (const mode of ['empty', 'error']) {
        await context.route('**/api/articles?**', route => {
          if (new URL(route.request().url()).searchParams.get('limit') !== '5') return route.continue();
          return route.fulfill({ status: mode === 'empty' ? 200 : 500, contentType: 'application/json', body: JSON.stringify(mode === 'empty' ? { ok: true, items: [] } : { ok: false }) });
        });
        await page.goto(base, { waitUntil: 'networkidle' });
        await ready(page);
        if (mode === 'empty') await page.locator('[data-new-grid]').getByText('Aucun article à afficher.').waitFor();
        else await page.locator('[data-new-error]').waitFor({ state: 'visible' });
        assert.equal(await page.locator('[data-new-grid] .skeleton').count(), 0);
        await context.unroute('**/api/articles?**');
      }
      assert.deepEqual(await (await context.request.get(`${base}/api/cart`)).json(), cartBefore);
      assert.deepEqual(errors, []);
      await context.close();
    }
    console.log(JSON.stringify({ ok: true, results, checks: ['SQL creation order', 'mobile and desktop', 'stock visibility', 'view all', 'infinite scroll', 'reset', 'empty and error states', 'unchanged cart'] }, null, 2));
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
