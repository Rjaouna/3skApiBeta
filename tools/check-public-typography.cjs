const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const output = 'tmp/instant-refinement/public';
fs.mkdirSync(output, { recursive: true });

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    const results = [];
    for (const width of [320, 390, 575, 768, 991, 1024, 1199, 1440, 1920]) {
      const page = await browser.newPage({viewport:{width,height:1000}, reducedMotion:'reduce'});
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.addInitScript(() => localStorage.setItem('3sk_cookie_consent', 'accepted'));
      for (const route of ['/', '/login']) {
        await page.goto('http://127.0.0.1:8765' + route, {waitUntil:'networkidle'});
        await page.locator('[data-site-loader]').waitFor({state:'hidden'});
        await page.evaluate(() => document.fonts.ready);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, `Page overflow ${route} ${width}`);
        const overflow = await page.locator('.public-main-v2, .site-footer, .site-stats-strip, .login-experience').evaluateAll(roots => roots.flatMap(root => [...root.querySelectorAll('h2,h3,p,.btn,input,textarea,.site-stats-strip__item,.brand-item')].filter(e => {
          const box = e.getBoundingClientRect();
          return !e.closest('[aria-hidden="true"]') && box.width > 0 && (box.left < -1 || box.right > innerWidth + 1 || e.scrollWidth > e.clientWidth + 1);
        }).map(e => e.className || e.tagName)));
        assert.deepEqual(overflow, [], `Text/control overflow ${route} ${width}`);
        const type = await page.evaluate(() => ({
          body:getComputedStyle(document.body).fontSize,
          headings:[...document.querySelectorAll('.home-reference-section .section-heading')].map(e => getComputedStyle(e).fontSize),
          paragraphs:[...document.querySelectorAll('.home-reference-section .section-copy')].map(e => getComputedStyle(e).fontSize),
          buttons:[...document.querySelectorAll('.stock-frame-copy .btn')].map(e => getComputedStyle(e).fontSize),
          fields:[...document.querySelectorAll('.login-experience input:not([type="hidden"])')].map(e => getComputedStyle(e).fontSize),
          hero:document.querySelector('.showcase-hero')?.getAttribute('style')
        }));
        assert.equal(type.body, '16px');
        assert.ok(type.headings.every(size => size === (width <= 767 ? '32px' : '48px')));
        assert.ok(type.paragraphs.every(size => size === (width <= 767 ? '16px' : '18px')));
        assert.ok(type.buttons.every(size => size === '16px'));
        assert.ok(type.fields.every(size => size === '16px'));
        if (route === '/') {
          for (const selector of ['#qui-sommes-nous > .section-shell','#services > .section-shell','#marques > .partner-brands__panel','#contact .contact-split','.site-footer__inner']) {
            const box = await page.locator(selector).boundingBox();
            const gutter = width <= 575 ? 18 : width <= 991 ? 24 : width <= 1439 ? 40 : width * .0435;
            assert.ok(Math.abs(box.x - gutter) < 1 && Math.abs(width - box.x - box.width - gutter) < 1, 'Home alignment retained: ' + selector);
          }
          await page.evaluate(() => scrollTo({top:800,behavior:'instant'}));
          const header = await page.locator('.showcase-header').boundingBox();
          assert.ok(Math.abs(header.y) < 1, 'Public header remains sticky');
          await page.evaluate(() => scrollTo({top:0,behavior:'instant'}));
          if ([390,1440].includes(width)) {
            for (const id of ['qui-sommes-nous','services']) {
              await page.locator('#' + id).scrollIntoViewIfNeeded();
              await page.locator('#' + id).screenshot({path:`${output}/${id}-${width}.png`,style:'.showcase-header {visibility:hidden!important}'});
            }
          }
        }
        results.push({route,width,type});
        console.log(`OK public ${route} ${width}: type hierarchy, text fit, alignment, sticky`);
      }
      assert.deepEqual(errors, []);
      await page.close();
    }
    fs.writeFileSync(output + '/typography.json',JSON.stringify(results,null,2));
  } finally { await browser.close(); }
})().catch(error => {console.error(error);process.exitCode=1});
