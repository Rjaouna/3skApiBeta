const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const postcss = require('postcss');
const backup = 'tmp/vehicle-brand-grid/before-source/';
const normalize = source => source.replace(/\r\n/g, '\n');
const read = file => normalize(fs.readFileSync(file, 'utf8'));
const unrelatedStyles = source => {
  const rules = [];
  postcss.parse(source).walkRules(rule => {
    if (rule.selector.includes('.showcase-brand')) return;
    rules.push([rule.parent.type === 'atrule' ? rule.parent.params : '', rule.toString()]);
  });
  return rules;
};
assert.deepEqual(unrelatedStyles(read('public/styles/public-showcase.css')), unrelatedStyles(read(backup + 'public/styles/public-showcase.css')));
const markup = read('templates/home/sections/_showcase.html.twig');
const oldMarkup = read(backup + 'templates/home/sections/_showcase.html.twig');
assert.equal(markup.slice(0, markup.indexOf('{% if homeVehicleBrands')), oldMarkup.slice(0, oldMarkup.indexOf('{% if homeVehicleBrands')));
assert.equal(markup.slice(markup.indexOf('<script src=')), oldMarkup.slice(oldMarkup.indexOf('<script src=')));
assert.equal(read('templates/partials/_public_header.html.twig').replace('v=20261016-8', 'v=20261016-7'), read(backup + 'templates/partials/_public_header.html.twig'));

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const results = [];
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1100 }, reducedMotion: 'no-preference' });
    await page.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
    await page.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle' });
    await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
    const button = page.locator('.showcase-brands__more');
    for (const width of [1440, 768, 390]) {
      await page.setViewportSize({ width, height: 1100 });
      for (let cycle = 0; cycle < 3; cycle++) {
        await button.scrollIntoViewIfNeeded();
        const x = (await button.boundingBox()).x;
        await button.click();
        await page.waitForTimeout(80);
        assert.ok(Math.abs((await button.boundingBox()).x - x) < .1, 'Horizontal movement during opening');
        await page.locator('#showcaseOtherBrands.show').waitFor();
        await button.click();
        await page.waitForTimeout(80);
        assert.ok(Math.abs((await button.boundingBox()).x - x) < .1, 'Horizontal movement during closing');
        await page.locator('#showcaseOtherBrands').waitFor({ state: 'hidden' });
      }
      results.push({ width, normalMotionToggleCycles: 3, horizontalShift: false });
    }
    await button.click();
    await page.locator('#showcaseOtherBrands.show').waitFor();
    for (const width of [1920, 1280, 1024, 768, 390, 320, 1920]) {
      await page.setViewportSize({ width, height: 1100 });
      const columns = await page.evaluate(() => {
        const first = document.querySelector('.showcase-brands__rail .showcase-brand').getBoundingClientRect();
        const extra = document.querySelector('.showcase-brands__additional .showcase-brand').getBoundingClientRect();
        return { first: first.x, extra: extra.x, firstWidth: first.width, extraWidth: extra.width, scrollWidth: document.documentElement.scrollWidth, width: innerWidth };
      });
      assert.ok(Math.abs(columns.first - columns.extra) < .1);
      assert.ok(Math.abs(columns.firstWidth - columns.extraWidth) < .1);
      assert.equal(columns.scrollWidth, width);
    }
    await button.click();
    await page.locator('#showcaseOtherBrands').waitFor({ state: 'hidden' });
    console.log('Unrelated CSS, header and hero unchanged. Normal animation and open-state resizing passed.');
  } finally {
    fs.writeFileSync(path.resolve('tmp/vehicle-brand-grid/scope-motion.json'), JSON.stringify({ unrelatedStylesUnchanged: true, headerUnchangedExceptCacheVersion: true, heroUnchanged: true, normalMotion: results }, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
