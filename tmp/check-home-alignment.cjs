const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');

const output = 'tmp/home-alignment-qa';
fs.mkdirSync(output, { recursive: true });
const rails = [
  '.public-intro-v2 > .section-shell',
  '#qui-sommes-nous > .section-shell',
  '#services > .section-shell',
  '#marques > .partner-brands__panel',
  '#contact .contact-split',
  '.site-stats-strip__grid',
  '.site-footer__inner',
  '.site-footer__bottom-inner',
];

async function snapshot(page) {
  return page.locator('.public-home').evaluate(home => {
    const rect = element => {
      const r = element.getBoundingClientRect();
      return { x: r.x, width: r.width, height: r.height };
    };
    const style = element => {
      const s = getComputedStyle(element);
      return Object.fromEntries(['color', 'backgroundColor', 'backgroundImage', 'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'lineHeight', 'borderRadius', 'borderColor'].map(p => [p, s[p]]));
    };
    const protectedSelectors = ['.showcase-header', '.showcase-hero', '.showcase-brands'];
    return {
      text: home.innerText,
      images: [...home.querySelectorAll('img')].map(e => [e.getAttribute('src'), e.alt]),
      links: [...home.querySelectorAll('a')].map(e => [e.textContent, e.getAttribute('href')]),
      form: [...home.querySelectorAll('form, input, textarea, button')].map(e => [e.tagName, e.name, e.type, e.getAttribute('action'), e.getAttribute('required'), e.getAttribute('minlength'), e.getAttribute('maxlength'), e.getAttribute('data-bs-target')]),
      styles: [...home.querySelectorAll('h1, h2, h3, p, a, button, label, input, textarea, img, section, .site-footer')].map(style),
      innerPadding: [...home.querySelectorAll('.partner-brands__panel, .stock-frame-copy, .about-metric, .contact-pane-light, .site-stats-strip__grid, .site-stats-strip__item, .site-footer__panel')].map(e => {
        const s = getComputedStyle(e);
        return [s.paddingTop, s.paddingRight, s.paddingBottom, s.paddingLeft];
      }),
      protected: protectedSelectors.map(selector => {
        const e = home.querySelector(selector);
        return { selector, rect: rect(e), style: style(e), children: [...e.querySelectorAll('*')].map(child => ({ rect: rect(child), style: style(child) })) };
      }),
      sectionHeights: ['#qui-sommes-nous', '#services'].map(s => rect(home.querySelector(s)).height),
    };
  });
}

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    for (const width of [320, 390, 575, 768, 991, 1024, 1439, 1440, 1920]) {
      const phases = [];
      for (const phase of ['before', 'after']) {
        const page = await browser.newPage({ viewport: { width, height: 1000 }, reducedMotion: 'reduce' });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
        if (phase === 'before') await page.route('**/styles/home-layout.css?*', route => route.fulfill({ contentType: 'text/css', body: '' }));
        await page.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle' });
        await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
        await page.evaluate(() => document.fonts.ready);
        await page.evaluate(() => document.querySelectorAll('.carousel').forEach(e => bootstrap.Carousel.getOrCreateInstance(e).pause()));
        await page.locator('.public-home img').evaluateAll(images => Promise.all(images.map(e => { e.loading = 'eager'; return e.decode().catch(() => {}); })));
        phases.push(await snapshot(page));
        assert.deepEqual(errors, [], 'No browser errors');
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, `No page overflow at ${width}`);
        if (phase === 'after') {
          const gutter = width <= 575 ? 18 : width <= 991 ? 24 : width <= 1439 ? 40 : width * .0435;
          for (const selector of rails) {
            const box = await page.locator(selector).boundingBox();
            assert.ok(Math.abs(box.x - gutter) < .05, `${selector} left ${box.x} instead of ${gutter} at ${width}`);
            assert.ok(Math.abs(width - box.x - box.width - gutter) < .05, `${selector} right at ${width}`);
          }
          const overflow = await page.locator('.public-main-v2, .site-footer, .site-stats-strip').evaluateAll(roots => roots.flatMap(root => [...root.querySelectorAll('h2, h3, p, .btn, input, textarea, .site-stats-strip__item, .brand-item')].filter(e => {
            const r = e.getBoundingClientRect();
            return !e.closest('[aria-hidden="true"]') && r.width > 0 && (r.left < -.5 || r.right > innerWidth + .5 || e.scrollWidth > e.clientWidth + 1);
          }).map(e => e.className || e.tagName)));
          assert.deepEqual(overflow, [], `No content overflow at ${width}`);
          assert.equal(await page.locator('#services').evaluate(e => getComputedStyle(e, '::after').display), 'none');
          await page.locator('#contact button[data-bs-target="#phoneContactModal"]').click();
          await page.locator('#phoneContactModal').waitFor({ state: 'visible' });
          assert.equal(await page.locator('#phoneContactModal a[href^="tel:"]').count(), 4);
          await page.locator('#phoneContactModal [data-bs-dismiss="modal"]').first().click();
          await page.locator('#phoneContactModal').waitFor({ state: 'hidden' });
          assert.equal(await page.locator('[data-contact-form]').evaluate(form => form.checkValidity()), false);
        }
        if ([390, 1440, 1920].includes(width)) {
          await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
          await page.screenshot({ path: `${output}/${width}-${phase}.png`, fullPage: true });
          await page.locator('#contact').screenshot({ path: `${output}/contact-${width}-${phase}.png`, style: '.showcase-header { visibility: hidden !important; }' });
        }
        await page.close();
      }
      for (let i = 0; i < phases[0].protected.length; i++) {
        const before = phases[0].protected[i];
        const after = phases[1].protected[i];
        for (const [a, b] of [[before, after], ...before.children.map((child, index) => [child, after.children[index]])]) {
          assert.deepEqual(b.style, a.style);
          for (const key of ['x', 'width', 'height']) assert.ok(Math.abs(a.rect[key] - b.rect[key]) < .5, `Protected ${before.selector} ${key} at ${width}`);
        }
      }
      for (const phase of phases) delete phase.protected;
      assert.deepEqual(phases[1], phases[0], `Visual identity, content and behavior attributes remain unchanged at ${width}`);
      console.log(`OK ${width}: 8 aligned rails, content/colors/fonts/images/inner spacing/header/hero/vehicle strip unchanged; modal and validation OK`);
    }
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
