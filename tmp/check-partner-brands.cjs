const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const base = 'http://127.0.0.1:8765';
const output = path.resolve('tmp/partner-brands-qa');

(async () => {
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({ headless: true, executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
  const results = [];
  try {
    for (const role of ['public', 'client', 'admin']) {
      const context = await browser.newContext({ viewport: { width: 1920, height: 1000 }, reducedMotion: 'reduce' });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      const ready = async () => {
        await page.locator('[data-site-loader]').waitFor({ state: 'hidden', timeout: 15000 });
        await page.evaluate(() => document.fonts.ready);
        const refuseCookies = page.getByRole('button', { name: 'Refuser', exact: true });
        if (await refuseCookies.isVisible()) await refuseCookies.click();
      };
      if (role !== 'public') {
        await page.goto(base + '/login', { waitUntil: 'networkidle' });
        await ready();
        await page.locator('#inputEmail').fill(`${role}@3skpartsauto.com`);
        await page.locator('#inputPassword').fill(`${role}@3skpartsauto.com`);
        await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('.login-btn').click()]);
      }
      for (const width of role === 'public' ? [2172, 1920, 1440, 1280, 1024, 768, 390, 360, 320] : [1920, 768, 390, 320]) {
        await page.setViewportSize({ width, height: width < 768 ? 844 : 1000 });
        const response = await page.goto(base + '/', { waitUntil: 'networkidle' });
        assert.equal(response.status(), 200);
        await ready();
        if (role === 'public') {
          const header = await page.evaluate(() => {
            const account = document.querySelector('.showcase-join');
            const title = document.querySelector('.showcase-title');
            return {
              label: account.textContent.trim(), href: account.getAttribute('href'),
              duplicate: document.querySelectorAll('.showcase-navbar .showcase-account').length,
              italic: getComputedStyle(title).fontStyle,
              blue: getComputedStyle(document.querySelector('.showcase-topbar')).backgroundColor,
              orange: getComputedStyle(account).backgroundColor,
              accountRight: account.getBoundingClientRect().right,
              titleRight: title.getBoundingClientRect().right,
              originalImage: document.querySelector('.showcase-hero.active img').getAttribute('src'),
            };
          });
          assert.equal(header.label, 'Espace client');
          assert.equal(header.href, '/login');
          assert.equal(header.duplicate, 0);
          assert.equal(header.italic, 'normal');
          assert.equal(header.blue, 'rgb(49, 95, 183)');
          assert.equal(header.orange, 'rgb(255, 138, 0)');
          assert.ok(header.accountRight <= width && header.titleRight <= width);
          assert.ok(header.originalImage.includes('080920251757318688-69ef174b1e570-6a246a350e402.webp'));
          if ([1920, 390, 320].includes(width)) await page.screenshot({ path: path.join(output, `header-${width}.png`) });
          if (width === 390) {
            await page.locator('.showcase-burger').click();
            await page.locator('.showcase-nav.show').waitFor({ state: 'visible' });
            await page.locator('.showcase-nav__link', { hasText: 'Marques' }).click();
            await page.waitForFunction(() => !document.querySelector('.showcase-nav').classList.contains('show'));
          }
        }
        const section = page.locator('.partner-brands');
        const bounds = await section.evaluate(el => ({ y: el.getBoundingClientRect().top + scrollY, height: el.getBoundingClientRect().height }));
        const headerHeight = await page.locator(role === 'public' ? '.showcase-header' : '.site__header').evaluate(el => el.getBoundingClientRect().height);
        for (let y = bounds.y - headerHeight - 16; y < bounds.y + bounds.height; y += 400) {
          await page.evaluate(y => scrollTo({ top: y, behavior: 'instant' }), y);
          await page.waitForTimeout(120);
        }
        await page.waitForFunction(() => [...document.querySelectorAll('.partner-brands img')].every(img => img.complete && img.naturalWidth));
        await page.evaluate(({ top, header }) => scrollTo({ top: top - header - 12, behavior: 'instant' }), { top: bounds.y, header: headerHeight });
        await page.waitForTimeout(750);
        const metrics = await section.evaluate(el => {
          const panel = el.querySelector('.partner-brands__panel');
          const tile = el.querySelector('.brand-item');
          const title = el.querySelector('.section-heading');
          const images = [...el.querySelectorAll('img')];
          return {
            viewport: innerWidth, scrollWidth: document.documentElement.scrollWidth,
            columns: getComputedStyle(el.querySelector('.brand-row')).gridTemplateColumns.split(' ').length,
            logos: images.length, missing: images.filter(img => !img.naturalWidth).length,
            background: getComputedStyle(el).backgroundImage,
            panelRadius: getComputedStyle(panel).borderRadius,
            panelBackground: getComputedStyle(panel).backgroundColor,
            tileRadius: getComputedStyle(tile).borderRadius,
            tileHeight: tile.getBoundingClientRect().height,
            titleSize: getComputedStyle(title).fontSize,
            titleWeight: getComputedStyle(title).fontWeight,
            accentWeight: getComputedStyle(el.querySelector('.heading-accent')).fontWeight,
            titleColor: getComputedStyle(title).color,
            accentColor: getComputedStyle(el.querySelector('.heading-accent')).color,
            clipped: [...el.querySelectorAll('.brand-item, .brands-heading, .section-heading, .section-copy, img')].filter(child => child.getBoundingClientRect().right > panel.getBoundingClientRect().right + 1 || child.scrollWidth > child.clientWidth + 1).map(child => child.className),
            logoPaths: images.map(img => img.getAttribute('src')),
          };
        });
        assert.equal(metrics.scrollWidth, width, `${role} ${width} horizontal overflow`);
        assert.equal(metrics.columns, width <= 575 ? 2 : width <= 1199 ? 3 : 6);
        assert.equal(metrics.logos, 24);
        assert.equal(metrics.missing, 0);
        assert.ok(metrics.background.includes('partner-brands-background.svg'));
        assert.equal(metrics.titleColor, 'rgb(35, 43, 56)');
        assert.equal(metrics.accentColor, 'rgb(49, 95, 183)');
        assert.equal(metrics.titleWeight, '800');
        assert.equal(metrics.accentWeight, '800');
        assert.deepEqual(metrics.clipped, []);
        if ([2172, 1920, 390, 320].includes(width)) {
          await page.screenshot({ path: path.join(output, `${role}-brands-viewport-${width}.png`) });
          await section.screenshot({ path: path.join(output, `${role}-brands-${width}.png`), style: '.showcase-header, .site__header, .whatsapp-float { visibility: hidden !important; }' });
        }
        const stickyTop = await page.locator(role === 'public' ? '.showcase-header' : '.site__header').evaluate(el => el.getBoundingClientRect().top);
        assert.ok(Math.abs(stickyTop) <= 1);
        results.push({ role, ...metrics, stickyTop, errors: [...errors] });
        console.log(`OK ${role} ${width}: ${metrics.columns} columns, 24 logos, no overflow`);
      }
      if (role === 'public') {
        await page.evaluate(() => scrollTo({ top: 0, behavior: 'instant' }));
        await page.locator('.showcase-join').click();
        await page.waitForURL('**/login');
        await ready();
        assert.equal(await page.locator('.showcase-account').count(), 0);
        assert.equal((await page.locator('.showcase-join').textContent()).trim(), 'Espace client');
        await page.evaluate(() => scrollTo({ top: 700, behavior: 'instant' }));
        assert.ok(Math.abs(await page.locator('.showcase-header').evaluate(el => el.getBoundingClientRect().top)) <= 1);
        console.log('OK public CTA opens login and login header remains sticky');
      }
      assert.deepEqual(errors, [], `${role} browser errors`);
      await context.close();
    }
  } finally {
    fs.writeFileSync(path.join(output, 'checks.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
