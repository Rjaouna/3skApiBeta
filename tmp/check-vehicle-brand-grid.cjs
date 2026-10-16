const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const phase = process.argv[2] || 'after';
const output = path.resolve('tmp/vehicle-brand-grid', phase);
const widths = [1920, 1440, 1366, 1280, 1024, 768, 575, 390, 360, 320];
const base = 'http://127.0.0.1:8765';
async function measure(page) {
  return page.locator('.showcase-brands').evaluate(section => {
    const bounds = element => { const r = element.getBoundingClientRect(); return { x: r.x, y: r.y, width: r.width, height: r.height, right: r.right }; };
    const brands = container => [...container.querySelectorAll('.showcase-brand')].map(element => {
      const range = document.createRange(); range.selectNodeContents(element.querySelector('span'));
      const textBounds = [...range.getClientRects()].map(rect => ({ x: rect.x, right: rect.right }));
      return { ...bounds(element), name: element.textContent.trim(), label: bounds(element.querySelector('span')), textBounds, image: bounds(element.querySelector('img')), href: element.getAttribute('href'), logo: element.querySelector('img').getAttribute('src') };
    });
    const style = getComputedStyle(section.querySelector('.showcase-brand span'));
    return { width: innerWidth, scrollWidth: document.documentElement.scrollWidth,
      primary: brands(section.querySelector('.showcase-brands__rail')),
      additional: brands(section.querySelector('.showcase-brands__additional')),
      button: bounds(section.querySelector('.showcase-brands__more')),
      rail: bounds(section.querySelector('.showcase-brands__rail')),
      background: getComputedStyle(section).backgroundColor,
      text: { family: style.fontFamily, size: style.fontSize, color: style.color, lineHeight: style.lineHeight },
      border: getComputedStyle(section.querySelector('.showcase-brand')).borderLeft,
      broken: [...section.querySelectorAll('img')].filter(image => !image.naturalWidth).map(image => image.src),
    };
  });
}
(async () => {
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const results = [];
  try {
    for (const width of widths) {
      const page = await browser.newPage({ viewport: { width, height: 1100 }, reducedMotion: 'reduce' });
      await page.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(base, { waitUntil: 'networkidle' });
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
      await page.evaluate(() => document.fonts.ready);
      await page.locator('.showcase-brands img').evaluateAll(images => Promise.all(images.map(image => { image.loading = 'eager'; return image.decode().catch(() => {}); })));
      const strip = page.locator('.showcase-brands');
      await strip.scrollIntoViewIfNeeded();
      const closed = await measure(page);
      await strip.screenshot({ path: path.join(output, `${width}-closed.png`), style: '.showcase-header, .whatsapp-float { visibility: hidden !important; }' });
      const cycles = [];
      for (let cycle = 0; cycle < 3; cycle++) {
        await page.locator('.showcase-brands__more').click();
        await page.locator('#showcaseOtherBrands.show').waitFor();
        await page.waitForFunction(() => !document.querySelector('#showcaseOtherBrands').classList.contains('collapsing'));
        const open = await measure(page);
        if (cycle === 0) await strip.screenshot({ path: path.join(output, `${width}-open.png`), style: '.showcase-header, .whatsapp-float { visibility: hidden !important; }' });
        if (phase !== 'before') {
          const columns = width > 1250 ? 12 : width > 991 ? 6 : width > 575 ? 4 : 3;
          const all = [...open.primary, ...open.additional];
          assert.equal(open.primary.length, 12);
          assert.deepEqual(open.broken, []);
          assert.equal(open.scrollWidth, width);
          assert.ok(Math.abs(open.button.x - closed.button.x) < .1, `Button moved horizontally ${width}`);
          assert.deepEqual(open.primary.map(item => item.x), closed.primary.map(item => item.x));
          for (const [index, cell] of open.additional.entries()) {
            const reference = open.primary[index % columns];
            assert.ok(Math.abs(cell.x - reference.x) < .1, `Column ${index} ${cell.name} ${width}`);
            assert.ok(Math.abs(cell.width - reference.width) < .1, `Column width ${index} ${width}`);
          }
          for (const cell of all) {
            assert.ok(Math.abs(cell.height - all[0].height) < .1, `Cell height ${cell.name} ${width}`);
            assert.ok(Math.abs(cell.image.x + cell.image.width / 2 - cell.x - cell.width / 2) < 1, `Logo center ${cell.name} ${width}`);
            assert.ok(Math.abs(cell.label.y - cell.y - (all[0].label.y - all[0].y)) < .1, `Name alignment ${cell.name} ${width}`);
            assert.ok(cell.x >= -1 && cell.right <= width + 1, `Cell overflow ${cell.name} ${width}`);
            assert.ok(cell.label.height <= cell.height - (cell.label.y - cell.y) + 1, `Name overflow ${cell.name} ${width}`);
            assert.ok(cell.textBounds.every(rect => rect.x >= cell.x - 1 && rect.right <= cell.right + 1), `Name width overflow ${cell.name} ${width}`);
          }
          const baseline = JSON.parse(fs.readFileSync(path.resolve('tmp/vehicle-brand-grid/before/checks.json'))).find(result => result.width === width).closed;
          assert.equal(open.background, baseline.background);
          assert.deepEqual(open.text, baseline.text);
          assert.equal(open.border, baseline.border);
          assert.deepEqual(open.primary.map(({ name, href, logo }) => ({ name, href, logo })), baseline.primary.map(({ name, href, logo }) => ({ name, href, logo })));
          assert.deepEqual(open.additional.map(({ name, href, logo }) => ({ name, href, logo })), baseline.additional.map(({ name, href, logo }) => ({ name, href, logo })));
        }
        cycles.push(open);
        await page.locator('.showcase-brands__more').click();
        await page.locator('#showcaseOtherBrands').waitFor({ state: 'hidden' });
        assert.equal(await page.locator('.showcase-brands__more').getAttribute('aria-expanded'), 'false');
      }
      assert.deepEqual(errors, []);
      results.push({ width, closed, cycles, errors });
      console.log(`${phase} ${width}: ${closed.primary.length} primary brands, three toggle cycles checked`);
      await page.close();
    }
  } finally {
    fs.writeFileSync(path.join(output, 'checks.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
