const { chromium } = require('playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const baseline = process.argv.includes('--before');
const directory = 'tmp/login-redesign-qa';
fs.mkdirSync(directory, { recursive: true });

async function sharedSnapshot(page) {
  return page.evaluate(() => {
    const style = e => {
      const s = getComputedStyle(e), r = e.getBoundingClientRect();
      return { x: r.x, width: r.width, height: r.height, color: s.color, background: s.backgroundColor, image: s.backgroundImage, font: s.font, padding: s.padding, border: s.border, radius: s.borderRadius };
    };
    return {
      header: [...document.querySelectorAll('.showcase-header, .showcase-header *')].map(style),
      footer: [...document.querySelectorAll('.site-footer, .site-footer *:not(iframe)')].map(style),
      form: [...document.querySelectorAll('.login-card form, .login-card input')].map(e => Object.fromEntries(['method', 'action', 'novalidate', 'name', 'id', 'type', 'autocomplete', 'required', 'autofocus'].map(a => [a, e.getAttribute(a)]))),
      csrf: document.querySelector('[name="_csrf_token"]').value.length > 0,
      destinations: [...document.querySelectorAll('.showcase-header a, .site-footer a')].map(e => e.getAttribute('href')),
    };
  });
}

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    for (const width of (baseline ? [1440, 390] : [320, 390, 575, 768, 991, 1024, 1280, 1440, 1920])) {
      const page = await browser.newPage({ viewport: { width, height: 1000 }, reducedMotion: 'reduce' });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto('http://127.0.0.1:8765/login', { waitUntil: 'networkidle' });
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
      await page.evaluate(() => document.fonts.ready);
      await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
      const shared = await sharedSnapshot(page);
      if (baseline) {
        fs.writeFileSync(`${directory}/before-${width}.json`, JSON.stringify(shared));
      } else {
        if ([1440, 390].includes(width)) {
          const expected = JSON.parse(fs.readFileSync(`${directory}/before-${width}.json`, 'utf8'));
          for (const section of ['header', 'footer']) {
            assert.equal(shared[section].length, expected[section].length);
            shared[section].forEach((actual, i) => {
              for (const key of ['x', 'width', 'height']) {
                assert.ok(Math.abs(actual[key] - expected[section][i][key]) < .5, `${section}[${i}] ${key} differs at ${width}: ${actual[key]} vs ${expected[section][i][key]}`);
                delete actual[key]; delete expected[section][i][key];
              }
            });
          }
          assert.deepEqual(shared, expected, 'Header/footer styles, destinations and authentication fields preserved');
        }
        assert.equal(shared.csrf, true);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        const overflow = await page.locator('.login-experience').evaluate(root => [...root.querySelectorAll('h1, h2, p, label, input, button, a, img')].filter(e => {
          const r = e.getBoundingClientRect();
          return r.width > 0 && (r.left < -.5 || r.right > innerWidth + .5 || e.clientWidth && e.scrollWidth > e.clientWidth + 1);
        }).map(e => e.id || e.className || e.tagName));
        assert.deepEqual(overflow, [], `No overflow at ${width}`);
        const password = page.locator('#inputPassword');
        await password.fill('Verification-UI-1234');
        await page.locator('#togglePassword').click();
        assert.equal(await password.getAttribute('type'), 'text');
        assert.equal(await page.locator('#togglePassword').getAttribute('aria-pressed'), 'true');
        assert.equal(await page.locator('#togglePassword').getAttribute('aria-label'), 'Masquer le mot de passe');
        await page.locator('#togglePassword').click();
        assert.equal(await password.getAttribute('type'), 'password');
        assert.equal(await password.inputValue(), 'Verification-UI-1234');
        await password.fill('');
        assert.equal(await page.locator('.login-card form').evaluate(form => form.checkValidity()), false);
        await page.locator('.login-help button').click();
        await page.locator('#phoneContactModal').waitFor({ state: 'visible' });
        assert.equal(await page.locator('#phoneContactModal a[href^="tel:"]').count(), 4);
        await page.locator('#phoneContactModal [data-bs-dismiss="modal"]').first().click();
        await page.locator('#phoneContactModal').waitFor({ state: 'hidden' });
        await page.locator('.login-experience img').evaluateAll(images => Promise.all(images.map(e => e.decode())));
        assert.deepEqual(errors, []);
      }
      if ([390, 1440, 1920].includes(width)) {
        await page.locator('input').evaluateAll(elements => elements.forEach(e => e.blur()));
        await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
        await page.screenshot({ path: `${directory}/${baseline ? 'before' : 'after'}-${width}.png` });
        await page.locator('.login-page').screenshot({ path: `${directory}/main-${baseline ? 'before' : 'after'}-${width}.png` });
      }
      console.log(`OK ${baseline ? 'baseline' : 'redesign'} ${width}`);
      await page.close();
    }
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
