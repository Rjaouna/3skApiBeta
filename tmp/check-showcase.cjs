const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
  const dir = path.resolve('tmp/showcase-qa');
  fs.mkdirSync(dir, { recursive: true });
  const results = [];
  for (const width of [1920, 1440, 1280, 1024, 768, 390, 360, 320]) {
    const page = await browser.newPage({ viewport: { width, height: width < 768 ? 844 : 1000 }, deviceScaleFactor: 1 });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const response = await page.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle', timeout: 60000 });
    await page.waitForFunction(() => !document.documentElement.classList.contains('site-is-loading'), null, { timeout: 15000 }).catch(() => {});
    await page.locator('[data-site-loader]').waitFor({ state: 'hidden', timeout: 15000 }).catch(() => {});
    const metrics = await page.evaluate(() => {
      const rect = selector => {
        const el = document.querySelector(selector);
        return el ? { x: el.getBoundingClientRect().x, right: el.getBoundingClientRect().right, height: el.getBoundingClientRect().height, width: el.getBoundingClientRect().width } : null;
      };
      const badImages = [...document.querySelectorAll('.showcase img, .showcase-brands img, .showcase-logo img')].filter(img => img.complete && !img.naturalWidth).map(img => img.src);
      const links = [...document.querySelectorAll('.showcase-nav__link')].map(a => ({ label: a.textContent.trim(), found: !!document.querySelector(a.hash) }));
      return { viewport: window.innerWidth, scrollWidth: document.documentElement.scrollWidth, header: rect('.showcase-header'), title: rect('.showcase-title'), hero: rect('.showcase-hero.active'), brands: document.querySelectorAll('.showcase-brand').length, badImages, links, titleWeight: getComputedStyle(document.querySelector('.showcase-title')).fontWeight };
    });
    await page.screenshot({ path: path.join(dir, `home-${width}.png`), fullPage: false });
    const checks = {};
    checks.introAtLoad = await page.evaluate(() => ({
      scrollY: window.scrollY,
      blocks: [...document.querySelectorAll('.public-intro-v2 .intro-copy, .public-intro-v2 .intro-facts')].map(element => ({
        opacity: getComputedStyle(element).opacity,
        filter: getComputedStyle(element).filter,
        transform: getComputedStyle(element).transform,
        scrollReveal: element.classList.contains('home-scroll-reveal'),
      })),
      laterSectionsAnimated: document.querySelectorAll('.public-about-v2 .home-scroll-reveal').length,
    }));
    if (checks.introAtLoad.scrollY !== 0 || checks.introAtLoad.blocks.length !== 2 || checks.introAtLoad.blocks.some(block => block.opacity !== '1' || block.filter !== 'none' || block.transform !== 'none' || block.scrollReveal)) throw new Error(`Intro must be visible without scrolling at ${width}`);
    if (!checks.introAtLoad.laterSectionsAnimated) throw new Error('Later sections must keep their scroll effects');
    if (width === 1440 || width === 390) await page.locator('.public-intro-v2').screenshot({ path: path.join(dir, `intro-${width}.png`) });
    checks.originalSlideImage = await page.locator('.showcase-hero.active .showcase-hero__media img').getAttribute('src');
    checks.colors = await page.evaluate(() => ({
      blue: getComputedStyle(document.querySelector('.showcase-topbar')).backgroundColor,
      orange: getComputedStyle(document.querySelector('.showcase-join')).backgroundColor,
      brandStrip: getComputedStyle(document.querySelector('.showcase-brands')).backgroundColor,
      brandText: getComputedStyle(document.querySelector('.showcase-brand')).color,
      overlay: getComputedStyle(document.querySelector('.showcase-hero__media'), '::after').backgroundImage,
    }));
    await page.evaluate(() => window.scrollTo({ top: 1200, behavior: 'instant' }));
    await page.waitForTimeout(250);
    checks.stickyTop = await page.locator('.showcase-header').evaluate(el => el.getBoundingClientRect().top);
    if (width === 1440 || width === 390) await page.screenshot({ path: path.join(dir, `sticky-${width}.png`) });
    if (Math.abs(checks.stickyTop) > 1) throw new Error(`Header not sticky at ${width}: ${checks.stickyTop}`);
    if (checks.colors.blue !== 'rgb(49, 95, 183)' || checks.colors.orange !== 'rgb(255, 138, 0)') throw new Error('Incorrect brand colors');
    if (checks.colors.brandStrip !== 'rgb(245, 246, 248)' || checks.colors.brandText !== 'rgb(35, 43, 56)') throw new Error('Vehicle strip must be light gray with anthracite labels');
    if (!checks.colors.overlay.includes('35, 43, 56') || checks.colors.overlay.includes('49, 95')) throw new Error('Hero overlay must be neutral, not blue');
    if (!checks.originalSlideImage.includes('080920251757318688-69ef174b1e570-6a246a350e402.webp')) throw new Error('Original slide media not restored');
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
    if (width === 390) {
      await page.locator('.showcase-burger').click();
      await page.waitForTimeout(450);
      checks.menuVisible = await page.locator('.showcase-nav').isVisible();
      await page.screenshot({ path: path.join(dir, 'mobile-menu.png') });
      await page.locator('.showcase-nav__link', { hasText: 'Services' }).click();
      await page.waitForTimeout(1000);
      checks.menuClosed = !(await page.locator('.showcase-nav').isVisible());
      checks.anchorBelowHeader = await page.evaluate(() => document.querySelector('#services').getBoundingClientRect().top >= document.querySelector('.showcase-header').getBoundingClientRect().bottom - 1);
      if (!checks.anchorBelowHeader) throw new Error('Section hidden behind sticky mobile header');
      await page.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle' });
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' }).catch(() => {});
      await page.locator('.showcase-contact.phone-contact-trigger').click();
      await page.locator('#phoneContactModal').waitFor({ state: 'visible' });
      checks.phoneLinks = await page.locator('#phoneContactModal a[href^="tel:"]').count();
    }
    if (width === 1440) {
      const more = page.locator('.showcase-brands__more');
      if (await more.count()) {
        await more.click();
        await page.waitForTimeout(450);
        checks.otherBrandsVisible = await page.locator('#showcaseOtherBrands').isVisible();
      }
      const next = page.locator('.showcase-controls button[data-bs-slide="next"]');
      if (await next.count()) {
        await next.click();
        await page.waitForTimeout(750);
        checks.managedSlideVisible = await page.locator('.showcase-hero--managed.active').count() === 1;
      }
      await page.locator('.showcase-search input').fill('pare choc');
      await page.locator('.showcase-search button').click();
      await page.waitForURL('**/login');
      checks.searchRespectsLogin = page.url().endsWith('/login');
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' }).catch(() => {});
      await page.screenshot({ path: path.join(dir, 'login-1440.png') });
      await page.evaluate(() => window.scrollTo({ top: 800, behavior: 'instant' }));
      checks.loginStickyTop = await page.locator('.showcase-header').evaluate(el => el.getBoundingClientRect().top);
      if (Math.abs(checks.loginStickyTop) > 1) throw new Error('Login header not sticky');
    }
    results.push({ width, status: response.status(), ...metrics, errors, checks });
    await page.close();
  }
  console.log(JSON.stringify(results, null, 2));
  fs.writeFileSync(path.join(dir, 'checks.json'), JSON.stringify(results, null, 2));
  await browser.close();
})().catch(error => { console.error(error); process.exitCode = 1; });
