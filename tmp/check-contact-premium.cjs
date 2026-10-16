const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const output = path.resolve('tmp/rollback-design-20261016-1918/contact-qa');
const base = 'http://127.0.0.1:8765';
(async () => {
  fs.mkdirSync(output, { recursive: true });
  const results = [];
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    for (const width of [1920, 1440, 1280, 1024, 768, 390, 360, 320]) {
      const page = await browser.newPage({ viewport: { width, height: width < 768 ? 844 : 1100 }, reducedMotion: 'reduce' });
      await page.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(base, { waitUntil: 'networkidle' });
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
      await page.evaluate(() => document.fonts.ready);
      await page.locator('#contact').scrollIntoViewIfNeeded();
      const layout = await page.locator('#contact').evaluate(section => {
        const visible = element => element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden';
        const rect = section.getBoundingClientRect();
        return {
          width: innerWidth, scrollWidth: document.documentElement.scrollWidth,
          columns: getComputedStyle(section.querySelector('.contact-split')).gridTemplateColumns.split(' ').length,
          clipped: [...section.querySelectorAll('*')].filter(element => visible(element) && !element.closest('.contact-form-honeypot') && (element.getBoundingClientRect().right > rect.right + 1 || element.getBoundingClientRect().left < rect.left - 1)).map(element => element.className),
          fields: [...section.querySelectorAll('input, textarea')].map(element => element.name),
          csrf: !!section.querySelector('[name="_token"]').value,
          action: new URL(section.querySelector('form').action).pathname,
          labelSize: getComputedStyle(section.querySelector('label[for="contact_name"]')).fontSize,
          inputSize: getComputedStyle(section.querySelector('#contact_name')).fontSize,
          rowInputs: [...section.querySelectorAll('input.contact-form-control')].map(element => element.getBoundingClientRect().top),
        };
      });
      assert.equal(layout.scrollWidth, width);
      assert.deepEqual(layout.clipped, []);
      assert.equal(layout.columns, width >= 992 ? 2 : 1);
      assert.deepEqual(layout.fields, ['_token', 'website', 'name', 'email', 'phone', 'subject', 'message']);
      assert.ok(layout.csrf);
      assert.equal(layout.labelSize, '14px');
      assert.equal(layout.inputSize, '16px');
      if (width >= 576) assert.equal(layout.rowInputs[0], layout.rowInputs[1], `First input row alignment ${width}`);
      await page.locator('#contact').screenshot({ path: path.join(output, `${width}-contact.png`), style: '.showcase-header, .whatsapp-float { visibility: hidden !important; }' });
      let posts = 0;
      let mode = 'error';
      let payload = '';
      await page.route(base + layout.action, async route => {
        posts++; payload = route.request().postData();
        await route.fulfill({ status: mode === 'error' ? 422 : 200, contentType: 'application/json', body: JSON.stringify({ ok: mode !== 'error', message: mode === 'error' ? 'Erreur de validation de test.' : 'Message de test confirme.' }) });
      });
      await page.locator('[data-contact-submit]').click();
      assert.equal(posts, 0);
      for (const [id, value] of Object.entries({ contact_name: 'Controle local', contact_email: 'test@example.com', contact_phone: '+212522713572', contact_subject: 'Controle formulaire', contact_message: 'Verification locale sans envoi reel du message.' })) await page.locator('#' + id).fill(value);
      await page.locator('[data-contact-submit]').click();
      await page.locator('[data-contact-status].is-error').waitFor();
      assert.equal(await page.locator('#contact_name').inputValue(), 'Controle local');
      assert.ok(payload.includes('name="_token"') && payload.includes('name="website"') && payload.includes('name="message"'));
      mode = 'success';
      await page.locator('[data-contact-submit]').click();
      await page.locator('[data-contact-status].is-success').waitFor();
      assert.equal(await page.locator('#contact_name').inputValue(), '');
      assert.ok(await page.locator('[data-contact-submit]').isEnabled());
      assert.equal(posts, 2);
      await page.locator('#contact .contact-coordinates button').click();
      await page.locator('#phoneContactModal').waitFor({ state: 'visible' });
      assert.equal(await page.locator('#phoneContactModal a[href^="tel:"]').count(), 4);
      assert.deepEqual(errors, []);
      results.push({ ...layout, validation: true, errorAndSuccess: true, phoneModal: true, errors });
      console.log(`OK ${width}: contact layout, native validation, mocked error/success, phone modal`);
      await page.close();
    }
  } finally {
    fs.writeFileSync(path.join(output, 'checks.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
