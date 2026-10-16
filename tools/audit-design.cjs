const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

const base = process.env.DESIGN_BASE_URL || 'http://127.0.0.1:8765';
const phase = process.argv[2] || 'after';
const directory = path.resolve('tmp/design-audit', phase);
const adminPages = ['/', '/articles', '/panier', '/mes-commandes', '/mon-profil', '/profil', '/mes-notifications', '/admin/dashboard', '/admin/orders', '/admin/clients', '/admin/company-setting', '/admin/import', '/admin/import-source', '/admin/category-reductions', '/admin/user-family-discounts', '/admin/user-family-discounts/new', '/admin/hero-slides/', '/admin/hero-slides/new', '/admin/users/import-wordpress'];
const clientPages = ['/', '/articles', '/panier', '/mes-commandes', '/mon-profil', '/profil', '/mes-notifications'];

function inspectDesign() {
  const visible = element => element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden';
  const colorCanvas = document.createElement('canvas');
  colorCanvas.width = colorCanvas.height = 1;
  const colorContext = colorCanvas.getContext('2d', { willReadFrequently: true });
  const rgb = color => {
    colorContext.clearRect(0, 0, 1, 1);
    colorContext.fillStyle = color;
    colorContext.fillRect(0, 0, 1, 1);
    const [red, green, blue, alpha] = colorContext.getImageData(0, 0, 1, 1).data;
    return [red, green, blue, alpha / 255];
  };
  const luminance = color => rgb(color).slice(0, 3).map(value => {
    const channel = value / 255;
    return channel <= .04045 ? channel / 12.92 : ((channel + .055) / 1.055) ** 2.4;
  }).reduce((sum, value, index) => sum + value * [.2126, .7152, .0722][index], 0);
  const buttons = [...document.querySelectorAll('.btn, .showcase-action, .showcase-account, .showcase-join, .public-cookie-btn, .admin-settings-btn, .admin-settings-modal__logout, .account-menu-item, .addcart, .print-btn')].filter(element => visible(element) && !element.disabled && !element.classList.contains('disabled'));
  const problems = [];
  for (const element of buttons) {
    const label = element.innerText.trim();
    if (!label || !/[a-zA-Z]/.test(label)) continue;
    const style = getComputedStyle(element);
    let background = style.backgroundColor;
    let gradient = style.backgroundImage !== 'none';
    let ancestor = element.parentElement;
    while (rgb(background)[3] === 0 && ancestor) {
      const parent = getComputedStyle(ancestor);
      background = parent.backgroundColor;
      gradient ||= parent.backgroundImage !== 'none';
      ancestor = ancestor.parentElement;
    }
    if (gradient || rgb(background)[3] < 1) continue;
    const foregroundLuminance = luminance(style.color);
    const backgroundLuminance = luminance(background);
    const ratio = (Math.max(foregroundLuminance, backgroundLuminance) + .05) / (Math.min(foregroundLuminance, backgroundLuminance) + .05);
    if (ratio < 4.5) problems.push({ label: label.slice(0, 70), classes: element.className, foreground: style.color, background, contrast: Number(ratio.toFixed(2)) });
  }
  const oldColors = new Set(['rgb(49, 95, 187)', 'rgb(255, 145, 0)']);
  const legacy = [...document.querySelectorAll('body *')].filter(element => visible(element) && !element.closest('.sf-toolbar, .sf-minitoolbar') && ['color', 'backgroundColor'].some(property => oldColors.has(getComputedStyle(element)[property]))).length;
  const sampleType = selector => [...document.querySelectorAll(selector)].filter(visible).map(element => {
    const style = getComputedStyle(element);
    return { label: (element.textContent.trim() || element.getAttribute('aria-label') || element.getAttribute('placeholder') || element.tagName).replace(/\s+/g, ' ').slice(0, 70), size: style.fontSize, lineHeight: style.lineHeight, weight: style.fontWeight, family: style.fontFamily };
  }).filter(item => item.label).slice(0, 30);
  const smallText = [...document.querySelectorAll('body *')].filter(element => visible(element)
    && !element.closest('.sf-toolbar, .sf-minitoolbar, script, style, svg, .site-page-loader')
    && [...element.childNodes].some(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim())
    && parseFloat(getComputedStyle(element).fontSize) > 0 && parseFloat(getComputedStyle(element).fontSize) < 12
    && !['I', 'OPTION'].includes(element.tagName)).map(element => ({ label: element.textContent.trim().replace(/\s+/g, ' ').slice(0, 65), class: element.className, size: getComputedStyle(element).fontSize })).slice(0, 60);
  return {
    title: document.title,
    connected: document.body.classList.contains('app-connected'),
    width: innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    palette: ['--brand-blue', '--brand-orange', '--brand-bg', '--brand-anthracite'].map(name => [name, getComputedStyle(document.documentElement).getPropertyValue(name).trim()]),
    buttons: buttons.length,
    contrastProblems: problems,
    legacyColors: legacy,
    typography: {
      pageTitles: sampleType(':is(.page-hero, .cart-page-hero, .orders-page-hero, .order-show-hero, .profile-page-hero, .clients-page-hero, .settings-page-hero, .import-page-hero, .reduction-page-hero, .ufd-page-hero, .notification-page-hero, .simple-page-hero, .dash-hero, .slide-admin-hero) :is(h1, h2, h3), .login-title, .container > .d-flex > h1.h4, .container-fluid > .d-flex > h1.h4'),
      sectionTitles: sampleType('.section-title, .profile-section-title, .settings-section-title, .dash-section-title, .section-heading, .home-section-head h2, .latest-arrivals-title'),
      labels: sampleType('.form-label, .form-check-label'),
      fields: sampleType('input.form-control, select.form-select, textarea.form-control'),
      buttons: sampleType('.btn:not(.btn-close), .showcase-join, .showcase-action'),
      tableHeaders: sampleType('thead th'),
      body: sampleType('.subtitle, .section-copy, .login-subtitle'),
      smallText,
    },
    links: [...document.querySelectorAll('a[href]')].map(element => element.getAttribute('href')),
  };
}

(async () => {
  fs.mkdirSync(directory, { recursive: true });
  const browser = await chromium.launch({ headless: true, executablePath: process.env.DESIGN_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
  const results = [];
  try {
    for (const role of ['public', 'admin', 'client']) {
      if (process.argv.includes('--previews-only')) break;
      const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      const ready = async () => {
        await page.locator('[data-site-loader]').waitFor({ state: 'hidden', timeout: 12000 }).catch(() => {});
        await page.waitForTimeout(450);
      };
      if (role !== 'public') {
        await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
        await ready();
        const email = process.env[`DESIGN_${role.toUpperCase()}_EMAIL`] || `${role}@3skpartsauto.com`;
        const password = process.env[`DESIGN_${role.toUpperCase()}_PASSWORD`] || email;
        await page.locator('#inputEmail').fill(email);
        await page.locator('#inputPassword').fill(password);
        await Promise.all([page.waitForURL(url => url.pathname !== '/login', { timeout: 15000 }), page.locator('.login-btn').click()]);
        await ready();
      }
      const pages = role === 'public' ? ['/', '/login'] : role === 'admin' ? [...adminPages] : [...clientPages];
      if (process.argv.includes('--focus')) {
        pages.splice(0, pages.length, ...(role === 'public' ? ['/', '/login'] : role === 'admin' ? ['/mon-profil', '/admin/clients'] : ['/mon-profil', '/articles']));
      }
      for (let index = 0; index < pages.length; index++) {
        const route = pages[index];
        for (const width of phase === 'before' ? [1440] : [1440, 390]) {
          await page.setViewportSize({ width, height: width < 768 ? 844 : 1000 });
          const response = await page.goto(base + route, { waitUntil: 'networkidle', timeout: 60000 });
          await ready();
          const result = await page.evaluate(inspectDesign);
          const name = `${role}-${route.replace(/[^a-z0-9]+/gi, '-') || 'home'}-${width}`;
          await page.screenshot({ path: path.join(directory, `${name}.png`) });
          const links = result.links;
          delete result.links;
          results.push({ role, route, status: response.status(), ...result, errors: errors.splice(0) });
          if (route === '/articles' && phase === 'storefront') {
            const disclosure = page.locator('[data-catalog-filters]');
            const isOpen = await disclosure.evaluate(element => element.open);
            if (isOpen !== (width >= 992)) throw new Error('Responsive filter disclosure state');
            const value = await page.locator('#f_family').inputValue();
            await disclosure.locator('summary').click();
            if (await disclosure.evaluate(element => element.open) === isOpen) throw new Error('Filters must open and close');
            if (await page.locator('#f_family').inputValue() !== value) throw new Error('Filter disclosure changed a selected value');
            await disclosure.locator('summary').click();
          }
          if (width === 1440) {
            const patterns = role === 'admin' ? [/^\/articles\/[^/?]+$/, /^\/admin\/orders\/\d+$/, /^\/admin\/hero-slides\/\d+\/edit$/, /^\/admin\/user-family-discounts\/\d+\/edit$/] : [/^\/articles\/[^/?]+$/, /^\/mes-commandes\/\d+$/];
            for (const pattern of patterns) {
              const link = links.find(value => pattern.test(value));
              if (link && !pages.some(value => pattern.test(value))) pages.push(link);
            }
          }
          if (role === 'admin' && route === '/admin/clients' && await page.locator('[data-edit]').count()) {
            await page.locator('[data-edit]').first().click();
            await page.locator('#clientModal').waitFor({ state: 'visible' });
            await page.waitForTimeout(350);
            const modal = await page.locator('#clientModal').evaluate(element => {
              const content = element.querySelector('.modal-content').getBoundingClientRect();
              const body = element.querySelector('.modal-body');
              return { top: content.top, bottom: content.bottom, viewport: innerHeight, bodyScrollable: getComputedStyle(body).overflowY, fields: element.querySelectorAll('input, textarea, select').length };
            });
            const floatingButtonZ = await page.locator('.whatsapp-float').evaluate(element => Number(getComputedStyle(element).zIndex));
            const modalZ = await page.locator('#clientModal').evaluate(element => Number(getComputedStyle(element).zIndex));
            results.push({ role, route: 'client-edit-modal', width, modal, ...await page.evaluate(inspectDesign) });
            if (phase !== 'before' && (floatingButtonZ >= modalZ || modal.top < 0 || modal.bottom > modal.viewport || modal.bodyScrollable !== 'auto')) throw new Error('Modal obscured or not scrollable');
            await page.screenshot({ path: path.join(directory, `client-modal-${width}.png`) });
            await page.locator('#clientModal [data-bs-dismiss="modal"]').first().click();
            await page.locator('#clientModal').waitFor({ state: 'hidden' });
          }
          if (role === 'admin' && route === '/mon-profil') {
            await page.locator('[data-bs-target="#adminSettingsModal"]').first().click();
            await page.locator('#adminSettingsModal').waitFor({ state: 'visible' });
            await page.waitForTimeout(350);
            const settingsBounds = await page.locator('#adminSettingsModal .modal-content').evaluate(element => ({ top: element.getBoundingClientRect().top, height: element.getBoundingClientRect().height, viewport: innerHeight }));
            if (phase !== 'before' && (settingsBounds.top !== 0 || Math.abs(settingsBounds.height - settingsBounds.viewport) > 1)) throw new Error('Settings menu must fill the screen');
            if (phase !== 'before' && (await page.evaluate(inspectDesign)).contrastProblems.length) throw new Error('Settings menu contrast');
            await page.screenshot({ path: path.join(directory, `settings-menu-${width}.png`) });
            await page.locator('#adminSettingsModal [data-bs-dismiss="modal"]').first().click();
            await page.locator('#adminSettingsModal').waitFor({ state: 'hidden' });
          }
          if (role !== 'public' && route === '/mon-profil' && phase !== 'before') {
            const action = page.locator('.profile-page-hero .btn-outline-secondary').first();
            await page.locator('.modal-backdrop').waitFor({ state: 'hidden' });
            await page.mouse.move(0, 0);
            await action.hover();
            await page.waitForFunction(() => {
              const element = document.querySelector('.profile-page-hero .btn-outline-secondary');
              const style = getComputedStyle(element);
              return element.matches(':hover') && style.backgroundColor === 'rgb(49, 95, 183)' && style.color === 'rgb(255, 255, 255)';
            }, null, { timeout: 5000 });
            const hovered = await action.evaluate(element => ({ background: getComputedStyle(element).backgroundColor, color: getComputedStyle(element).color }));
            if (hovered.background !== 'rgb(49, 95, 183)' || hovered.color !== 'rgb(255, 255, 255)') throw new Error(`Profile hover colors ${width}: ${JSON.stringify(hovered)}`);
            await page.mouse.move(0, 0);
            await page.locator('[title="Compte"]').click();
            await page.locator('.account-menu.show').waitFor({ state: 'visible' });
            await page.waitForTimeout(250);
            const accountProblems = (await page.evaluate(inspectDesign)).contrastProblems;
            if (accountProblems.length) throw new Error(`Account menu contrast: ${JSON.stringify(accountProblems)}`);
            await page.screenshot({ path: path.join(directory, `account-menu-${role}-${width}.png`) });
            await page.locator('[title="Compte"]').click();
            if (width === 390) {
              await page.locator('.mobile-menu-toggle').click();
              await page.locator('#mainMenuOffcanvas').waitFor({ state: 'visible' });
              await page.waitForTimeout(350);
              const menu = await page.locator('#mainMenuOffcanvas .mobile-menu-body').evaluate(element => {
                element.scrollTop = element.scrollHeight;
                return { text: element.textContent.trim().length, overflow: getComputedStyle(element).overflowY };
              });
              if (!menu.text || menu.overflow !== 'auto') throw new Error('Mobile menu must show real, scrollable content');
              await page.screenshot({ path: path.join(directory, `burger-${role}.png`) });
              await page.locator('#mainMenuOffcanvas [data-bs-dismiss="offcanvas"]').first().click();
              await page.locator('#mainMenuOffcanvas').waitFor({ state: 'hidden' });
            }
            await page.locator(width === 390 ? '.mobile-actions [data-bs-target="#cartOffcanvas"]' : '.indicator-cart').click();
            await page.locator('#cartOffcanvas').waitFor({ state: 'visible' });
            await page.waitForTimeout(350);
            const cart = await page.locator('#cartOffcanvas').evaluate(element => ({ top: element.getBoundingClientRect().top, bottom: element.getBoundingClientRect().bottom, viewport: innerHeight }));
            if (cart.top !== 0 || Math.abs(cart.bottom - cart.viewport) > 1) throw new Error('Cart panel must fill viewport height');
            if ((await page.evaluate(inspectDesign)).contrastProblems.length) throw new Error('Cart panel contrast');
            await page.screenshot({ path: path.join(directory, `cart-panel-${role}-${width}.png`) });
            await page.locator('#cartOffcanvas [data-bs-dismiss="offcanvas"]').first().click();
            await page.locator('#cartOffcanvas').waitFor({ state: 'hidden' });
          }
          console.log(`${role} ${width} ${route}: HTTP ${response.status()}, contrast ${result.contrastProblems.length}, old colors ${result.legacyColors}`);
        }
      }
      await context.close();
    }
    if (process.argv.includes('--previews')) {
      const context = await browser.newContext();
      const page = await context.newPage();
      await page.addInitScript(() => { window.print = () => {}; });
      const previews = path.resolve('tmp/design-audit/previews');
      for (const file of fs.readdirSync(previews).filter(file => file.endsWith('.html'))) {
        const url = `${base}/__design_preview/${file}`;
        await page.route(url, route => route.fulfill({ contentType: 'text/html', body: fs.readFileSync(path.join(previews, file), 'utf8') }));
        for (const width of [1440, 390]) {
          await page.setViewportSize({ width, height: width === 390 ? 844 : 1000 });
          await page.goto(url, { waitUntil: 'networkidle' });
          await page.locator('[data-site-loader]').waitFor({ state: 'hidden', timeout: 12000 }).catch(() => {});
          await page.screenshot({ path: path.join(directory, `${file}-${width}.png`) });
          const result = await page.evaluate(inspectDesign);
          delete result.links;
          results.push({ role: 'in-memory-preview', route: file, ...result });
          if (file === 'order-print.html') {
            await page.emulateMedia({ media: 'print' });
            const print = await page.evaluate(() => ({
              header: getComputedStyle(document.querySelector('thead th')).backgroundColor,
              headerColumns: getComputedStyle(document.querySelector('.order-head')).gridTemplateColumns,
              tableHeaders: [...document.querySelectorAll('thead th')].map(element => element.textContent.trim()),
            }));
            if (print.header !== 'rgb(49, 95, 183)' || print.tableHeaders.join(',') !== 'Reference,Article,Qte') throw new Error('Print design or columns changed');
            await page.screenshot({ path: path.join(directory, `print-media-${width}.png`) });
            await page.emulateMedia({ media: 'screen' });
          }
          console.log(`preview ${width} ${file}: contrast ${result.contrastProblems.length}`);
        }
      }
      await context.close();
    }
    if (phase !== 'before') {
      const failures = results.filter(result => result.contrastProblems.length || result.legacyColors || result.scrollWidth > result.width || result.errors?.length);
      if (failures.length) throw new Error(`Visual regressions: ${failures.map(result => `${result.role}:${result.route}:${result.width}`).join(', ')}`);
    }
  } finally {
    fs.writeFileSync(path.join(directory, 'results.json'), JSON.stringify(results, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
