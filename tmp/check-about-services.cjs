const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const out = path.resolve('tmp/about-services-qa');

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const results = [];
  try {
    for (const width of [1983, 1920, 1672, 1440, 1280, 1024, 768, 390, 320]) {
      const page = await browser.newPage({ viewport: { width, height: width < 768 ? 844 : 1100 }, reducedMotion: 'reduce' });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle' });
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
      await page.evaluate(() => document.fonts.ready);
      const refuse = page.getByRole('button', { name: 'Refuser', exact: true });
      if (await refuse.isVisible()) await refuse.click();
      await page.evaluate(() => bootstrap.Carousel.getOrCreateInstance(document.querySelector('#stockPhotoCarouselV2')).pause());
      const sections = [];
      for (const selector of ['#qui-sommes-nous', '#services']) {
        const section = page.locator(selector);
        await section.scrollIntoViewIfNeeded();
        await page.waitForFunction(selector => [...document.querySelectorAll(selector + ' img')].filter(img => img.getBoundingClientRect().height).every(img => img.complete && img.naturalWidth), selector);
        await page.waitForTimeout(600);
        const metrics = await section.evaluate(el => {
          const rect = el.getBoundingClientRect();
          const grid = el.querySelector('.row');
          return {
            id: el.id, width: rect.width, height: rect.height,
            columns: getComputedStyle(grid).gridTemplateColumns.split(' ').length,
            headingSize: getComputedStyle(el.querySelector('h2')).fontSize,
            copySize: getComputedStyle(el.querySelector('.section-copy')).fontSize,
            background: getComputedStyle(el).backgroundImage,
            headingColor: getComputedStyle(el.querySelector('h2')).color,
            accent: getComputedStyle(el.querySelector('.heading-accent')).color,
            clipped: [...el.querySelectorAll('h2, h3, p, .about-metric, .service-line, .stock-frame, .btn')].filter(child => child.getBoundingClientRect().right > rect.right + 1 || child.scrollWidth > child.clientWidth + 1 && child.clientWidth > 0).map(child => ({ class: child.className, text: child.textContent.slice(0, 60) })),
          };
        });
        assert.equal(metrics.columns, width <= 991 ? 1 : 2);
        assert.equal(metrics.headingColor, 'rgb(35, 43, 56)');
        assert.equal(metrics.accent, 'rgb(49, 95, 183)');
        if (selector === '#qui-sommes-nous') {
          assert.ok(metrics.background.includes('home-about-background.svg'));
          assert.equal(metrics.headingSize, width >= 1800 ? '64px' : width >= 1440 ? '56px' : width >= 576 ? '44px' : '32px');
        } else {
          assert.equal(metrics.headingSize, width >= 1440 ? '60px' : width >= 576 ? '44px' : '32px');
        }
        assert.deepEqual(metrics.clipped, [], `${width} ${selector}: overflow`);
        if ([1983, 1920, 1672, 390, 320].includes(width)) await section.screenshot({ path: path.join(out, `${metrics.id}-${width}.png`), style: '.showcase-header, .whatsapp-float { visibility: hidden !important; }' });
        sections.push(metrics);
      }
      const metrics = await page.evaluate(() => ({
        width: innerWidth, scrollWidth: document.documentElement.scrollWidth,
        photo: document.querySelector('.about-visual img').getAttribute('src'),
        values: [...document.querySelectorAll('.about-metric strong')].map(el => el.textContent.trim()),
        services: document.querySelectorAll('.service-line').length,
        images: [...document.querySelectorAll('#stockPhotoCarouselV2 .carousel-item img')].map(img => img.getAttribute('src')),
        indicators: document.querySelectorAll('#stockPhotoCarouselV2 [data-bs-slide-to]').length,
        login: document.querySelector('.stock-frame-copy .btn').getAttribute('href'),
        mainHero: document.querySelector('.showcase-hero.active img').getAttribute('src'),
      }));
      assert.equal(metrics.scrollWidth, width);
      assert.deepEqual(metrics.values, ['2014', 'B2B', '1M']);
      assert.equal(metrics.services, 4);
      assert.equal(metrics.images.length, 5);
      assert.equal(metrics.indicators, 5);
      assert.equal(metrics.login, '/login');
      assert.ok(metrics.photo.includes('stock-entrepot-pieces-carrosserie-automobile-3sk-parts-auto'));
      assert.ok(metrics.mainHero.includes('080920251757318688-69ef174b1e570-6a246a350e402.webp'));
      if ([1672, 390].includes(width)) {
        const carousel = page.locator('#stockPhotoCarouselV2');
        await carousel.locator('.carousel-control-next').click();
        await page.waitForFunction(() => document.querySelector('#stockPhotoCarouselV2 .carousel-item.active img').getAttribute('src').endsWith('/4.webp'));
        await carousel.locator('.carousel-control-prev').click();
        await page.waitForFunction(() => document.querySelector('#stockPhotoCarouselV2 .carousel-item.active img').getAttribute('src').endsWith('/3.webp'));
        await carousel.locator('[data-bs-slide-to="0"]').click();
        await page.waitForFunction(() => document.querySelector('#stockPhotoCarouselV2 .carousel-item.active img').getAttribute('src').endsWith('/1.webp'));
        await page.evaluate(() => bootstrap.Carousel.getOrCreateInstance(document.querySelector('#stockPhotoCarouselV2')).to(2));
        await page.waitForFunction(() => document.querySelector('#stockPhotoCarouselV2 .carousel-item.active img').getAttribute('src').endsWith('/3.webp'));
        if (width === 1672) {
          await page.evaluate(() => bootstrap.Carousel.getOrCreateInstance(document.querySelector('#stockPhotoCarouselV2')).cycle());
          await page.waitForFunction(() => document.querySelector('#stockPhotoCarouselV2 .carousel-item.active img').getAttribute('src').endsWith('/4.webp'), null, { timeout: 7000 });
          await page.evaluate(() => bootstrap.Carousel.getOrCreateInstance(document.querySelector('#stockPhotoCarouselV2')).pause());
        }
      }
      assert.deepEqual(errors, []);
      results.push({ ...metrics, sections, errors });
      console.log(`OK ${width}: two sections, original images and information, responsive layout`);
      await page.close();
    }
  } finally {
    fs.writeFileSync(path.join(out, 'checks.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
