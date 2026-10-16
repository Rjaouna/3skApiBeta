const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const base = 'http://127.0.0.1:8765';
const output = 'tmp/instant-refinement';
fs.mkdirSync(output, { recursive: true });

async function ready(page) {
  await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
  await page.waitForFunction(() => !document.querySelector('#statusLine')?.textContent.includes('Chargement'));
}

async function verifyItems(page, data) {
  assert.equal(data.ok, true);
  const codes = (data.items || []).map(item => String(item.Id));
  await page.waitForFunction(expected => JSON.stringify([...document.querySelectorAll('#grid .product-card')].map(e => e.dataset.code)) === JSON.stringify(expected), codes);
  const count = await page.locator('#statusLine').textContent();
  assert.ok(count.includes(`/ ${data.total} article(s)`));
}

async function action(page, fn) {
  const response = page.waitForResponse(r => new URL(r.url()).pathname === '/api/articles' && r.status() === 200);
  const [, result] = await Promise.all([fn(), response]);
  const data = await result.json();
  await verifyItems(page, data);
  return { data, url: new URL(result.url()) };
}

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    for (const role of ['admin', 'client']) {
      const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
      const page = await context.newPage();
      const errors = [];
      const mutations = [];
      await context.route('**/api/cart**', route => {
        if (['GET', 'HEAD', 'OPTIONS'].includes(route.request().method())) return route.continue();
        return route.abort();
      });
      page.on('request', request => {
        const url = new URL(request.url());
        if (url.origin === base && !['GET', 'HEAD', 'OPTIONS'].includes(request.method()) && url.pathname !== '/login') mutations.push([request.method(), url.pathname]);
      });
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
      await ready(page);
      const email = process.env[`DESIGN_${role.toUpperCase()}_EMAIL`] || `${role}@3skpartsauto.com`;
      await page.locator('#inputEmail').fill(email);
      await page.locator('#inputPassword').fill(process.env[`DESIGN_${role.toUpperCase()}_PASSWORD`] || email);
      await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('.login-btn').click()]);
      const cartBefore = await (await page.request.get(`${base}/api/cart`)).json();
      for (const width of (process.argv.includes('--quick') ? [390] : [320, 390, 575, 768, 991, 1024, 1199, 1440, 1920])) {
        await page.setViewportSize({ width, height: 1000 });
        await page.goto(`${base}/articles`, { waitUntil: 'networkidle' });
        await ready(page);
        await page.evaluate(() => scrollTo({top:0,behavior:'instant'}));
        const disclosure = page.locator('[data-catalog-filters]');
        assert.equal(await disclosure.evaluate(e => e.open), width >= 992);
        if (width < 992) await disclosure.locator('.filters-panel__label').click();
        assert.equal(await page.locator('.whatsapp-float').isVisible(), width >= 992);
        const filters = await disclosure.boundingBox();
        const results = await page.locator('.catalog-results-area').boundingBox();
        const grid = await page.locator('#grid').boundingBox();
        assert.ok(results.y + 1 >= filters.y + filters.height, 'Results remain below filters: ' + JSON.stringify({width, filters, results}));
        assert.ok(Math.abs(grid.x - (await page.locator('#f_q').boundingBox()).x) < 1, 'Filters and results share horizontal alignment');
        assert.ok(Math.abs(filters.y - (await page.locator('.site__header').boundingBox()).height) < 2, 'Filter bar touches the menu');
        assert.equal(await page.locator('.catalog-page .page-hero, #applyFilters, #clearFilters').count(), 0);
        assert.equal(await page.locator('#resetFilters').count(), 1);
        if (width >= 992) {
          const fields = [];
          for (const id of ['f_q', 'f_family', 'f_brand', 'f_vehicle']) fields.push(await page.locator(`#${id}`).boundingBox());
          assert.ok(fields.every(field => Math.abs(field.y - fields[0].y) < 1), 'Four filters share the first line');
          assert.ok(filters.height < (width >= 1200 ? 180 : 270), `Compact desktop panel: ${filters.height}px`);
          if (width >= 1200) {
            const year = await page.locator('.filters-section--year').boundingBox();
            assert.ok(Math.abs(year.y - (await page.locator('.filters-section--keyword').boundingBox()).y) < 1, 'All desktop criteria on one row');
          }
        }
        await page.locator('#f_q').evaluate(e => { e.value = 'Saisie conservée '; });
        for (let i = 0; i < 3; i++) {
          await disclosure.locator('.filters-panel__label').click();
          await disclosure.locator('.filters-panel__label').click();
          assert.equal(await page.locator('#f_q').inputValue(), 'Saisie conservée ');
        }
        await page.locator('#f_q').evaluate(e => { e.value = ''; });
        if (width < 992) {
          await disclosure.locator('.filters-panel__label').click();
          assert.equal(await page.locator('.whatsapp-float').isVisible(), true);
          await disclosure.locator('.filters-panel__label').click();
          assert.equal(await page.locator('.whatsapp-float').isVisible(), false);
        }
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        const overflow = await disclosure.evaluate(root => [...root.querySelectorAll('input:not([type="hidden"]), select, button, summary, .filters-actions, .filters-meta')].filter(e => {
          const r = e.getBoundingClientRect();
          return r.width > 0 && (r.left < 0 || r.right > innerWidth + 1 || e.clientWidth && e.scrollWidth > e.clientWidth + 1);
        }).map(e => e.id || e.className));
        assert.deepEqual(overflow, [], `No filter overflow ${role} ${width}`);
        const cardOverflow = await page.locator('#grid .bottomFooterWrapper').evaluateAll(footers => footers.flatMap(footer => {
          const parent = footer.getBoundingClientRect();
          return [...footer.querySelectorAll('button, input')].filter(element => {
            const box = element.getBoundingClientRect();
            return box.left < parent.left - 1 || box.right > parent.right + 1;
          }).map(element => element.className);
        }));
        assert.deepEqual(cardOverflow, [], 'Quantity and cart controls fit inside every card');
        const stockRows = await page.locator('#grid .catalog-info-row').evaluateAll(rows => rows.filter(e => e.querySelector('.catalog-info-label')?.textContent === 'Stock').length);
        assert.equal(stockRows, role === 'admin' ? await page.locator('#grid .product-card').count() : 0);
        if (role === 'client') for (const badge of await page.locator('#grid [data-stock-badge]').allTextContents()) assert.doesNotMatch(badge, /\d/);
        if ([390, 1024, 1440].includes(width)) {
          await page.evaluate(() => scrollTo({ top: 0, behavior: 'instant' }));
          if (width < 992) await disclosure.locator('.filters-panel__label').click();
          await page.screenshot({ path: `${output}/catalog-${role}-${width}.png` });
          if (width < 992) {
            await disclosure.locator('.filters-panel__label').click();
            await disclosure.screenshot({ path: `${output}/filters-open-${role}-${width}.png` });
          }
        }
        console.log(`OK ${role} ${width}: top filters, responsive grid, 3 toggle cycles, stock permissions`);
      }

      await page.setViewportSize({ width: 1440, height: 1000 });
      await page.goto(`${base}/articles`, { waitUntil: 'networkidle' });
      await ready(page);
      const initial = await (await page.request.get(`${base}/api/articles?page=1&limit=24&facets=1`)).json();
      const first = initial.items[0];
      const searched = await action(page, () => page.locator('#f_q').fill('GRILLE '));
      assert.equal(searched.url.searchParams.get('q'), 'GRILLE');
      assert.equal(await page.locator('#f_q').inputValue(), 'GRILLE ');
      await action(page, () => page.locator('#resetFilters').click());

      const fields = [['f_family', 'family', first.FamilyName], ['f_brand', 'brand', first.xx_Marque], ['f_vehicle', 'vehicle', first.xx_Vehicule]];
      const expected = {};
      for (const [id, key, value] of fields) {
        const options = await page.locator(`#${id} option`).evaluateAll(es => es.map(e => e.value).filter(Boolean));
        expected[key] = options.includes(value) ? value : options[0];
        const selected = await action(page, () => page.locator(`#${id}`).selectOption(expected[key]));
        assert.equal(selected.url.searchParams.get(key), expected[key]);
      }
      const stock = await action(page, () => page.locator('#f_inStock').check());
      assert.equal(stock.url.searchParams.get('inStock'), '1');
      await page.locator('#yearSlider').evaluate(e => e.noUiSlider.set([2009, 2020]));
      const filtered = await action(page, () => page.locator('#yearSlider .noUi-handle-lower').press('ArrowRight'));
      for (const [key, value] of Object.entries(expected)) assert.equal(filtered.url.searchParams.get(key), value);
      assert.equal(filtered.url.searchParams.get('inStock'), '1');
      assert.equal(filtered.url.searchParams.get('anneeFrom'), '2010');
      assert.equal(filtered.url.searchParams.get('anneeTo'), '2020');
      const direct = await (await page.request.get(filtered.url.href)).json();
      assert.equal(filtered.data.total, direct.total);
      assert.deepEqual(filtered.data.items, direct.items);
      await page.reload({ waitUntil: 'networkidle' });
      await ready(page);
      for (const [id, key] of fields) assert.equal(await page.locator(`#${id}`).inputValue(), expected[key]);
      assert.equal(await page.locator('#f_inStock').isChecked(), true);
      assert.equal(await page.locator('#f_yearFrom').inputValue(), '2010');
      assert.equal(await page.locator('#f_yearTo').inputValue(), '2020');
      await action(page, () => page.locator('#resetFilters').click());
      const wasOpen = await page.locator('[data-catalog-filters]').evaluate(e => e.open);
      await page.locator('#f_q').fill('texte en attente');
      const reset = await action(page, () => page.locator('#resetFilters').click());
      await page.waitForTimeout(500);
      assert.equal(new URL(page.url()).searchParams.has('q'), false, 'Pending keyword is cancelled by reset');
      assert.equal(await page.locator('[data-catalog-filters]').evaluate(e => e.open), wasOpen, 'Reset does not toggle the panel');
      assert.equal(reset.url.searchParams.has('new'), false);
      assert.deepEqual(await page.evaluate(() => ['CITROËN', 'YIH SHENG', '  EURO  STAMP ', 'DE-GA', 'Marque œæ'].map(ProductBrandLogos.slug)), ['citroen', 'yih-sheng', 'euro-stamp', 'de-ga', 'marque-oeae']);
      const registry = await page.locator('#product-brand-logos').textContent();
      const logos = JSON.parse(registry);
      const catalogBrands = await page.locator('#f_brand option').evaluateAll(es => es.map(e => e.value).filter(Boolean));
      const brands = await page.evaluate(({names, logos}) => names.map(name => ({name, file: logos[ProductBrandLogos.slug(name)] || null})), {names:catalogBrands, logos});
      fs.writeFileSync(output + '/brand-inventory-' + role + '.json', JSON.stringify(brands, null, 2));
      assert.ok(Object.keys(logos).length >= 28);
      for (const [slug, url] of Object.entries(logos)) {
        assert.match(slug, /^[a-z0-9]+(?:-[a-z0-9]+)*$/);
        assert.ok(url.includes('/brand-logos/'));
        const response = await page.request.get(new URL(url, base).href);
        assert.equal(response.status(), 200, 'Real logo file serves successfully: ' + slug);
      }
      const logoBrand = (await page.locator('#f_brand option').evaluateAll(es => es.map(e => e.value))).find(name => Object.hasOwn(logos, name.toLowerCase().replace(/\s+/g, '-')));
      assert.ok(logoBrand, 'At least one real catalogue brand has an image');
      await action(page, () => page.locator('#f_brand').selectOption(logoBrand));
      await page.locator('#grid .meta-chip--brand-logo img').first().scrollIntoViewIfNeeded();
      await page.waitForFunction(() => [...document.querySelectorAll('#grid .meta-chip--brand-logo img')].filter(e => e.getBoundingClientRect().top < innerHeight).every(e => e.complete && e.naturalWidth > 0));
      assert.ok((await page.locator('#grid .meta-chip--brand-logo img').count()) > 0);
      await page.screenshot({ path: output + '/phira-logos-' + role + '.png' });
      await page.locator('#grid .meta-chip--brand-logo img').first().evaluate(e => e.dispatchEvent(new Event('error')));
      assert.equal(await page.locator('#grid .brand-logo-fallback').first().isVisible(), true);
      assert.match(await page.evaluate(() => ProductBrandLogos.badge('<Unknown>')), /&lt;Unknown&gt;/);
      await action(page, () => page.locator('#resetFilters').click());
      await page.evaluate(() => scrollTo({top:0,behavior:'instant'}));
      const limited = await action(page, () => page.locator('#limitSelect').selectOption('10'));
      assert.equal(limited.data.items.length, 10);
      const second = await action(page, () => page.locator('#nextBtn').click());
      assert.equal(second.url.searchParams.get('page'), '2');
      const appended = page.waitForResponse(r => new URL(r.url()).pathname === '/api/articles' && new URL(r.url()).searchParams.get('page') === '3');
      await page.locator('#infiniteScrollSentinel').scrollIntoViewIfNeeded();
      const more = await (await appended).json();
      await page.waitForFunction(count => document.querySelectorAll('#grid .product-card').length === count, second.data.items.length + more.items.length);
      assert.deepEqual(await page.locator('#grid .product-card').evaluateAll(es => es.map(e => e.dataset.code)), [...second.data.items, ...more.items].map(e => String(e.Id)));
      await page.goto(`${base}/articles/${encodeURIComponent(first.Id)}`, { waitUntil: 'networkidle' });
      await ready(page);
      const detailStock = await page.locator('#stockBadgeValue').textContent();
      if (role === 'client') assert.doesNotMatch(detailStock, /\d/);
      else assert.match(detailStock, /Stock/);
      const cartAfter = await (await page.request.get(`${base}/api/cart`)).json();
      assert.deepEqual(mutations, [], 'Visual verification sends no mutations');
      const unchanged = JSON.stringify(cartAfter) === JSON.stringify(cartBefore);
      console.log(`Cart ${role}: ${unchanged ? 'unchanged' : 'concurrent change observed; no mutation sent by this test and no data restored'}`);
      assert.deepEqual(errors, []);
      console.log(`OK ${role}: keyword spaces, family/brand/vehicle/years/stock filters, URL reload, reset, pagination, infinite scroll, detail access, no mutation requests`);
      await context.close();
    }
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
