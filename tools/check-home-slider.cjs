const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const base = 'http://127.0.0.1:8765';
const phase = process.argv[2] || 'after';
const output = 'tmp/home-slider';
const widths = [320, 390, 768, 1024, 1440, 1920];
fs.mkdirSync(`${output}/${phase}`, { recursive: true });

async function snapshot(page, role) {
  return page.evaluate(connected => {
    const root = document.querySelector(connected ? '.connected-slider-shell .carousel' : '#showcaseCarousel');
    const active = root.querySelector('.carousel-item.active');
    const style = element => {
      if (!element) return null;
      const css = getComputedStyle(element);
      return Object.fromEntries(['fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'lineHeight', 'color', 'backgroundColor', 'borderRadius', 'padding', 'width', 'height'].map(key => [key, css[key]]));
    };
    return {
      count: root.querySelectorAll('.carousel-item').length,
      height: root.getBoundingClientRect().height,
      width: root.getBoundingClientRect().width,
      content: [...root.querySelectorAll('.carousel-item')].map(slide => ({
        title: slide.querySelector('h1, h2')?.textContent.trim() || '',
        subtitle: slide.querySelector('.hero-text, .showcase-description')?.textContent.trim() || '',
        image: slide.querySelector('img')?.getAttribute('data-src') || slide.querySelector('img')?.getAttribute('src') || '',
        video: slide.querySelector('source')?.getAttribute('data-src') || slide.querySelector('source')?.getAttribute('src') || '',
        actions: [...slide.querySelectorAll('.hero-card a, .showcase-actions a')].map(link => [link.textContent.trim(), link.getAttribute('href')]),
        admin: [...slide.querySelectorAll('.hero-admin-actions form, .showcase-admin-actions form')].map(form => [form.method, form.getAttribute('action'), form.querySelector('input')?.name]),
        edit: slide.querySelector('.hero-admin-actions > a, .showcase-admin-actions > a')?.getAttribute('href') || '',
      })),
      title: style(active.querySelector('h1, h2')),
      description: style(active.querySelector('.hero-text, .showcase-description')),
      media: style(active.querySelector('img, video')),
      overlay: getComputedStyle(active.querySelector('.showcase-hero__media') || active.querySelector('.hero-slide'), '::after').backgroundImage,
      overflow: document.documentElement.scrollWidth > innerWidth,
    };
  }, role !== 'public');
}

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const results = {};
  const intros = {};
  const before = phase === 'after' ? JSON.parse(fs.readFileSync(`${output}/before.json`, 'utf8')) : null;
  try {
    for (const role of ['public', 'client', 'admin']) {
      const context = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1440, height: 1000 } });
      await context.route('**/api/cart**', route => ['GET', 'HEAD', 'OPTIONS'].includes(route.request().method()) ? route.continue() : route.abort());
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
      if (role !== 'public') {
        await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
        await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
        const email = process.env[`DESIGN_${role.toUpperCase()}_EMAIL`] || `${role}@3skpartsauto.com`;
        await page.locator('#inputEmail').fill(email);
        await page.locator('#inputPassword').fill(process.env[`DESIGN_${role.toUpperCase()}_PASSWORD`] || email);
        await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('.login-btn').click()]);
      }
      for (const width of widths) {
        await page.setViewportSize({ width, height: 1000 });
        await page.goto(base, { waitUntil: 'networkidle' });
        await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
        await page.evaluate(async () => {
          await document.fonts.ready;
          const carousel = document.querySelector('.connected-slider-shell .carousel, #showcaseCarousel');
          window.bootstrap.Carousel.getOrCreateInstance(carousel).pause();
        });
        const key = `${role}-${width}`;
        results[key] = await snapshot(page, role);
        assert.equal(results[key].overflow, false, `No horizontal overflow: ${key}`);
        if (before) {
          if (role === 'public') assert.deepEqual(results[key], before[key], `Public slider unchanged: ${width}`);
          else {
            assert.equal(results[key].content.length, before[key].content.length, `Same configured slides: ${key}`);
            for (let index = 0; index < results[key].content.length; index++) {
              if (index === 0) {
                for (const field of ['image', 'video', 'admin', 'edit']) assert.deepEqual(results[key].content[index][field], before[key].content[index][field], `Preserved ${field}: ${key}`);
                for (const field of ['title', 'subtitle', 'actions']) assert.deepEqual(results[key].content[index][field], results[`public-${width}`].content[0][field], `Identical public intro ${field}: ${key}`);
              } else assert.deepEqual(results[key].content[index], before[key].content[index], `Subsequent slide preserved: ${key}`);
            }
            assert.equal(results[key].width, width, `Full-bleed slider: ${key}`);
            const minHeight = await page.locator('.connected-slider-shell .carousel-item.active').evaluate(element => getComputedStyle(element).minHeight);
            assert.equal(minHeight, width >= 1800 ? '510px' : width <= 991 ? '590px' : width <= 1250 ? '470px' : '490px', `Shared responsive height: ${key}`);
            for (const property of ['fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'lineHeight', 'color']) {
              assert.equal(results[key].title[property], results[`public-${width}`].title[property], `Shared title ${property}: ${key}`);
              assert.equal(results[key].description[property], results[`public-${width}`].description[property], `Shared description ${property}: ${key}`);
            }
            assert.equal(results[key].overlay, results[`public-${width}`].overlay, `Shared overlay: ${key}`);
            assert.equal(await page.locator('.showcase-admin-actions').count() > 0, role === 'admin');
            const contentOverflow = await page.locator('.connected-slider-shell .showcase-hero__content').evaluateAll(nodes => nodes.flatMap(root => [...root.querySelectorAll('h1,h2,p,a')].filter(element => {
              const box = element.getBoundingClientRect();
              return box.width > 0 && (box.left < 0 || box.right > innerWidth + 1 || element.scrollWidth > element.clientWidth + 1);
            }).map(element => element.className)));
            assert.deepEqual(contentOverflow, [], `Content stays inside the viewport: ${key}`);
          }
        }
        const intro = await page.locator(role === 'public' ? '#showcaseCarousel .showcase-hero__content' : '#heroCarousel .showcase-hero__content').first().evaluate(element => ({
          html: element.outerHTML,
          elements: [...element.querySelectorAll('p,h1,li,.showcase-benefits__icon,a')].map(node => {
            const css = getComputedStyle(node);
            return Object.fromEntries(['fontFamily','fontSize','fontWeight','lineHeight','color','backgroundColor','borderRadius','padding','height','width'].map(property => [property, css[property]]));
          }),
        }));
        if (role === 'public') intros[width] = intro;
        else if (before) assert.deepEqual(intro, intros[width], `Identical composition and controls: ${key}`);
        if ([390, 1440, 1920].includes(width)) {
          await page.screenshot({ path: `${output}/${phase}/${key}.png` });
          await page.locator(role === 'public' ? '#showcaseCarousel' : '.connected-slider-shell').screenshot({ path: `${output}/${phase}/${key}-slider.png` });
        }
      }
      if (before && role !== 'public') {
        await page.locator('#heroCarousel .showcase-action--secondary').first().click();
        await page.waitForFunction(() => location.hash === '#marques' && document.querySelector('#marques').getBoundingClientRect().top >= document.querySelector('.site__header').getBoundingClientRect().bottom);
        assert.ok(await page.locator('#marques').isVisible());
        await page.goto(base, { waitUntil: 'networkidle' });
        await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
        await Promise.all([page.waitForURL(url => url.pathname === '/articles'), page.locator('#heroCarousel .showcase-action--primary').first().click()]);
      }
      assert.deepEqual(errors, [], `No JavaScript errors: ${role}`);
      await context.close();
    }
    fs.writeFileSync(`${output}/${phase}.json`, JSON.stringify(results, null, 2));
    console.log(JSON.stringify({ phase, checks: Object.keys(results).length, ok: true }));
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
