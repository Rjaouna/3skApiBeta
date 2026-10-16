const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const base = process.env.DESIGN_BASE_URL || 'http://127.0.0.1:8765';
const output = path.resolve('tmp/home-harmonization');
const widths = [1920, 1440, 1280, 1024, 768, 390, 360, 320];

async function loadImages(page) {
  await page.locator('img[src]').evaluateAll(images => Promise.all(images.map(image => {
    image.loading = 'eager';
    return image.decode().catch(() => {});
  })));
}

async function inspectHome(page) {
  return page.evaluate(() => {
    const bounds = selector => {
      const element = document.querySelector(selector);
      const rect = element.getBoundingClientRect();
      return { left: rect.left, top: rect.top, right: rect.right, width: rect.width, height: rect.height };
    };
    const visible = element => element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden';
    const overflow = [...document.querySelectorAll('.public-main-v2 *, .site-footer *')].filter(element => {
      if (!visible(element) || element.closest('.contact-form-honeypot, .carousel-item:not(.active)')) return false;
      const rect = element.getBoundingClientRect();
      return rect.width > 0 && (rect.left < -1 || rect.right > innerWidth + 1);
    }).map(element => element.className || element.tagName);
    const headings = [...document.querySelectorAll('.public-main-v2 .section-heading')].map(element => ({
      text: element.textContent.trim(), size: getComputedStyle(element).fontSize,
      family: getComputedStyle(element).fontFamily,
    }));
    const brandHeading = bounds('.brands-heading');
    const brandGrid = bounds('.brand-row');
    return {
      width: innerWidth, scrollWidth: document.documentElement.scrollWidth, overflow,
      aboutColumns: getComputedStyle(document.querySelector('.about-layout')).gridTemplateColumns.split(' ').length,
      servicesColumns: getComputedStyle(document.querySelector('.services-layout')).gridTemplateColumns.split(' ').length,
      brandColumns: getComputedStyle(document.querySelector('.brand-row')).gridTemplateColumns.split(' ').length,
      brandGap: brandGrid.top - brandHeading.top - brandHeading.height,
      partnerImages: document.querySelectorAll('.brand-item img').length,
      brokenImages: [...document.querySelectorAll('img[src]')].filter(image => !image.naturalWidth).map(image => image.src),
      headings, contactClient: bounds('.contact-pane-dark'), contactForm: bounds('.contact-pane-light'),
      stats: [...document.querySelectorAll('.site-stats-strip__value')].map(element => element.textContent.trim()),
      metrics: [...document.querySelectorAll('.about-metric strong')].map(element => element.textContent.trim()),
      services: document.querySelectorAll('.service-line').length,
      carouselImages: document.querySelectorAll('#stockPhotoCarouselV2 .carousel-item img').length,
      formFields: [...document.querySelectorAll('[data-contact-form] input, [data-contact-form] textarea')].map(element => element.name),
      csrf: !!document.querySelector('[name="_token"]').value,
      formAction: new URL(document.querySelector('[data-contact-form]').action).pathname,
      map: document.querySelector('.site-footer__map iframe').src,
      paymentImages: [...document.querySelectorAll('.site-footer__payment img')].map(image => image.src),
      hero: document.querySelector('.showcase-hero__media img').src,
      heroStyle: getComputedStyle(document.querySelector('.showcase-title')).fontStyle,
      cardRadius: getComputedStyle(document.querySelector('.brand-item')).borderRadius,
    };
  });
}

async function fillContact(page) {
  await page.locator('#contact_name').fill('Controle visuel local');
  await page.locator('#contact_email').fill('test@example.com');
  await page.locator('#contact_phone').fill('+212522713572');
  await page.locator('#contact_subject').fill('Controle formulaire');
  await page.locator('#contact_message').fill('Verification locale de la presentation du formulaire.');
}

(async () => {
  fs.mkdirSync(output, { recursive: true });
  const results = [];
  const browser = await chromium.launch({ headless: true, executablePath: process.env.DESIGN_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
  try {
    for (const width of widths) {
      const context = await browser.newContext({ viewport: { width, height: width < 768 ? 844 : 1000 }, reducedMotion: 'reduce' });
      await context.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      assert.equal((await page.goto(base, { waitUntil: 'networkidle' })).status(), 200);
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
      await loadImages(page);
      const result = await inspectHome(page);
      assert.equal(result.scrollWidth, width, `Horizontal page overflow at ${width}`);
      assert.deepEqual(result.overflow, [], `Element overflow at ${width}`);
      assert.deepEqual(result.brokenImages, [], `Broken image at ${width}`);
      assert.deepEqual(errors, []);
      assert.equal(result.aboutColumns, width >= 992 ? 2 : 1);
      assert.equal(result.servicesColumns, width >= 992 ? 2 : 1);
      assert.equal(result.brandColumns, width >= 1200 ? 6 : width > 575 ? 3 : 2);
      assert.equal(result.brandGap, 24);
      assert.equal(result.partnerImages, 24);
      assert.deepEqual(result.metrics, ['2014', 'B2B', '1M']);
      assert.equal(result.stats.length, 5);
      assert.equal(result.services, 4);
      assert.equal(result.carouselImages, 5);
      assert.equal(result.heroStyle, 'normal');
      assert.ok(result.hero.includes('080920251757318688-69ef174b1e570-6a246a350e402.webp'));
      assert.equal(result.cardRadius, '8px');
      assert.ok(result.headings.every(heading => heading.family.includes('Roboto')));
      assert.deepEqual(result.formFields, ['_token', 'website', 'name', 'email', 'phone', 'subject', 'message']);
      assert.ok(result.csrf);
      assert.ok(result.map.includes('33.528667,-7.550028'));
      assert.ok(result.paymentImages.some(src => src.includes('cmi-logo.png')));
      assert.ok(result.paymentImages.some(src => src.includes('visa-logo.png')));
      if (width >= 992) assert.ok(result.contactClient.left < result.contactForm.left);
      else assert.ok(result.contactClient.top < result.contactForm.top);
      for (const selector of ['.public-intro-v2', '#qui-sommes-nous', '#services', '#marques', '#contact', '.site-footer']) {
        await page.locator(selector).screenshot({ path: path.join(output, `${width}-${selector.replace(/[^a-z0-9]/gi, '')}.png`) });
      }
      await page.screenshot({ path: path.join(output, `${width}-full.png`), fullPage: true });
      const carousel = page.locator('#stockPhotoCarouselV2');
      await carousel.scrollIntoViewIfNeeded();
      await page.evaluate(() => window.bootstrap.Carousel.getOrCreateInstance(document.querySelector('#stockPhotoCarouselV2')).pause());
      await carousel.locator('.carousel-control-next').click();
      await page.waitForTimeout(100);
      assert.equal(await carousel.locator('.carousel-item.active img').getAttribute('alt'), 'Stock 3SK Parts Auto 4');
      await carousel.locator('.carousel-control-prev').click();
      await page.waitForTimeout(100);
      assert.equal(await carousel.locator('.carousel-item.active img').getAttribute('alt'), 'Stock 3SK Parts Auto 3');
      await carousel.locator('[data-bs-slide-to="0"]').click();
      await page.waitForTimeout(100);
      assert.equal(await carousel.locator('.carousel-item.active img').getAttribute('alt'), 'Stock 3SK Parts Auto 1');
      let posts = 0;
      let mode = 'error';
      let payload = '';
      // Interception deliberately avoids sending email or changing server data.
      await page.route(base + result.formAction, async route => {
        posts++;
        payload = route.request().postData();
        await route.fulfill({ status: mode === 'error' ? 422 : 200, contentType: 'application/json', body: JSON.stringify({ ok: mode !== 'error', message: mode === 'error' ? 'Erreur de validation de test.' : 'Message de test confirme.' }) });
      });
      await page.locator('[data-contact-submit]').click();
      assert.equal(posts, 0, 'Empty form must keep native validation');
      await fillContact(page);
      await page.locator('[data-contact-submit]').click();
      await page.locator('[data-contact-status].is-error').waitFor();
      assert.equal(await page.locator('#contact_name').inputValue(), 'Controle visuel local');
      assert.ok(payload.includes('name="_token"') && payload.includes('name="website"') && payload.includes('name="message"'));
      await page.locator('#contact').screenshot({ path: path.join(output, `${width}-contact-error.png`) });
      mode = 'success';
      await page.locator('[data-contact-submit]').click();
      await page.locator('[data-contact-status].is-success').waitFor();
      assert.equal(await page.locator('#contact_name').inputValue(), '');
      assert.equal(await page.locator('[data-contact-submit]').isEnabled(), true);
      assert.equal(posts, 2);
      await page.locator('#contact').screenshot({ path: path.join(output, `${width}-contact-success.png`) });
      results.push({ ...result, carouselControls: 'passed', mockedFormStates: 'passed', errors });
      console.log(`${width}px: layout, logos, carousel, form validation/error/success passed`);
      await context.close();
    }
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'no-preference' });
    await context.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
    const page = await context.newPage();
    await page.goto(base, { waitUntil: 'networkidle' });
    await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
    await page.locator('#services').scrollIntoViewIfNeeded();
    await page.waitForFunction(() => document.querySelector('#stockPhotoCarouselV2 .carousel-item.active img').alt !== 'Stock 3SK Parts Auto 3', { timeout: 7000 });
    await page.locator('#marques').scrollIntoViewIfNeeded();
    await page.waitForTimeout(900);
    assert.equal(await page.locator('.brands-heading').evaluate(element => getComputedStyle(element).opacity), '1');
    assert.equal(await page.locator('.brand-item').first().evaluate(element => getComputedStyle(element).filter), 'none');
    await context.close();
    console.log('Normal motion: autoplay and lightweight section reveals passed');
  } finally {
    fs.writeFileSync(path.join(output, 'checks.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
