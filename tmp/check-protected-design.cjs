const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const { PNG } = require('pngjs');
const assert = require('node:assert/strict');
const phase = process.argv[2] || 'rollback';
const baselinePhase = 'rollback-normalized';
const isBaseline = phase.startsWith('rollback');
const base = 'http://127.0.0.1:8765';
const output = path.resolve('tmp/rollback-design-20261016-1918', phase);
const protectedSections = ['.showcase-header', '.showcase-hero', '.showcase-brands', '.public-intro-v2', '#qui-sommes-nous', '#services', '#marques', '.site-stats-strip', '.site-footer'];

(async () => {
  const pixelmatch = (await import(require('node:url').pathToFileURL(require.resolve('pixelmatch')).href)).default;
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({ headless: true, executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
  const results = [];
  try {
    for (const width of [1920, 1440, 1024, 768, 390, 320]) {
      const page = await browser.newPage({ viewport: { width, height: width < 768 ? 844 : 1100 }, reducedMotion: 'reduce' });
      await page.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
      if (phase === baselinePhase) {
        // Re-render the saved rollback markup in the test browser only.
        await page.route(base + '/', async route => {
          const response = await route.fetch();
          let html = await response.text();
          const token = html.match(/name="_token" value="([^"]+)"/)[1];
          const action = html.match(/<form class="contact-form"[^>]*action="([^"]+)"/)[1];
          const saved = fs.readFileSync(path.resolve('tmp/rollback-design-20261016-1918/restored-baseline/templates/home/_public_content.html.twig'), 'utf8');
          let oldContact = saved.slice(saved.indexOf('    <section id="contact"'), saved.indexOf('  </main>'));
          oldContact = oldContact.replaceAll("{{ path('app_login') }}", '/login').replaceAll("{{ path('home_contact') }}", action).replaceAll("{{ csrf_token('public_contact') }}", token);
          const start = html.indexOf('<section id="contact"');
          const end = html.indexOf('  </main>', start);
          html = html.slice(0, start) + oldContact + html.slice(end);
          html = html.replace(/<link[^>]+home-contact\.css[^>]*>\s*/, '');
          await route.fulfill({ response, body: html });
        });
      }
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(base, { waitUntil: 'networkidle' });
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
      await page.evaluate(() => document.fonts.ready);
      await page.locator('img[src]').evaluateAll(images => Promise.all(images.map(image => { image.loading = 'eager'; return image.decode().catch(() => {}); })));
      await page.evaluate(() => { const carousel = bootstrap.Carousel.getOrCreateInstance(document.querySelector('#stockPhotoCarouselV2')); carousel.pause(); carousel.to(2); });
      await page.waitForTimeout(200);
      await page.addStyleTag({ content: '.home-scroll-reveal { opacity: 1 !important; transform: none !important; filter: none !important; transition: none !important; } .home-scroll-progress, .whatsapp-float { visibility: hidden !important; } .public-intro-v2 { --intro-parallax-y: 0px !important; }' });
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth), width);
      const row = { width, sections: [], errors };
      for (const selector of protectedSections) {
        const element = page.locator(selector).first();
        await element.scrollIntoViewIfNeeded();
        await page.waitForTimeout(100);
        const dimensions = await element.evaluate(element => {
          const rect = element.getBoundingClientRect(); const style = getComputedStyle(element);
          return { width: rect.width, height: rect.height, background: style.backgroundColor, radius: style.borderRadius,
            headings: [...element.querySelectorAll('h1, h2, h3')].map(heading => ({ text: heading.textContent.trim(), size: getComputedStyle(heading).fontSize, font: getComputedStyle(heading).fontFamily })) };
        });
        const name = `${width}-${selector.replace(/[^a-z0-9]/gi, '')}.png`;
        const file = path.join(output, name);
        // Align subpixel document positions before raster comparison.
        await element.evaluate(element => {
          const top = element.getBoundingClientRect().top;
          element.style.transform = `translateY(${Math.round(top) - top}px)`;
        });
        await element.screenshot({ path: file, style: selector === '.showcase-header' ? '' : '.showcase-header { visibility: hidden !important; }', mask: selector === '.site-footer' ? [page.locator('.site-footer__map iframe')] : [] });
        await element.evaluate(element => element.style.removeProperty('transform'));
        let difference = null;
        if (!isBaseline) {
          const before = PNG.sync.read(fs.readFileSync(path.resolve('tmp/rollback-design-20261016-1918', baselinePhase, name)));
          const after = PNG.sync.read(fs.readFileSync(file));
          assert.equal(after.width, before.width, `${name}: width changed`);
          assert.equal(after.height, before.height, `${name}: height changed`);
          const baseline = JSON.parse(fs.readFileSync(path.resolve('tmp/rollback-design-20261016-1918', baselinePhase, 'checks.json'))).find(result => result.width === width).sections.find(section => section.selector === selector);
          assert.deepEqual(dimensions, baseline.dimensions, `${name}: protected styles changed`);
          difference = pixelmatch(before.data, after.data, null, after.width, after.height, { threshold: .05 }) / (after.width * after.height);
          // The lower sections move with contact height. Keep their pixel delta
          // diagnostic; exact CSS/source/geometry checks remain mandatory above.
          if (!['.site-stats-strip', '.site-footer'].includes(selector)) assert.ok(difference < .002, `${name}: protected pixels changed ${difference}`);
        }
        row.sections.push({ selector, dimensions, difference });
      }
      await page.locator('#contact').screenshot({ path: path.join(output, `${width}-contact.png`), style: '.showcase-header { visibility: hidden !important; }' });
      assert.deepEqual(errors, []);
      results.push(row);
      console.log(`${phase} ${width}: nine protected sections ${isBaseline ? 'recorded' : 'unchanged'}`);
      await page.close();
    }
  } finally {
    fs.writeFileSync(path.join(output, 'checks.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
