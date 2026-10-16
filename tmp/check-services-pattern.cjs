const { chromium } = require('playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');
(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    for (const width of [1920, 390]) {
      const results = [];
      for (const phase of ['before', 'after']) {
        const page = await browser.newPage({ viewport: { width, height: 1100 }, reducedMotion: 'reduce' });
        await page.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
        if (phase === 'before') await page.route('**/styles/home-about-services.css?*', route => route.fulfill({ contentType: 'text/css', body: fs.readFileSync('tmp/services-pattern-qa/before.css', 'utf8') }));
        await page.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle' });
        await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
        await page.evaluate(() => document.fonts.ready);
        await page.evaluate(() => bootstrap.Carousel.getOrCreateInstance(document.querySelector('#stockPhotoCarouselV2')).pause());
        await page.locator('#services').scrollIntoViewIfNeeded();
        await page.locator('#services img').evaluateAll(images => Promise.all(images.map(image => { image.loading = 'eager'; return image.decode().catch(() => {}); })));
        const metrics = await page.locator('#services').evaluate(section => {
          const bounds = element => { const rect = element.getBoundingClientRect(); return { width: rect.width, height: rect.height, x: rect.x, y: rect.y }; };
          return { section: bounds(section), content: [...section.querySelectorAll('h2, h3, p, .service-line, .stock-frame, .btn')].map(element => ({ text: element.textContent.trim(), ...bounds(element), size: getComputedStyle(element).fontSize })), photo: getComputedStyle(section, '::before').backgroundImage, contactPattern: getComputedStyle(document.querySelector('#contact')).backgroundImage, motifVisible: getComputedStyle(section, '::after').display !== 'none', images: [...section.querySelectorAll('img')].map(image => image.src), overflow: document.documentElement.scrollWidth > innerWidth };
        });
        assert.equal(metrics.motifVisible, phase === 'before');
        delete metrics.motifVisible;
        assert.equal(metrics.overflow, false);
        results.push(metrics);
        await page.locator('#services').screenshot({ path: `tmp/services-pattern-qa/${width}-${phase}.png`, style: '.showcase-header, .whatsapp-float { visibility: hidden !important; }' });
        await page.close();
      }
      assert.deepEqual(results[1], results[0], 'Only the diagonal motif may change');
      console.log(`OK ${width}: motif removed; layout, texts, carousel images, faint car and contact background unchanged`);
    }
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
