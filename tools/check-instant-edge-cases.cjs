const {chromium} = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const base = 'http://127.0.0.1:8765';
const output = 'tmp/instant-refinement';

(async () => {
  const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
  try {
    const context = await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
    await context.route('**/api/cart**',route => ['GET','HEAD','OPTIONS'].includes(route.request().method()) ? route.continue() : route.abort());
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror',error => errors.push(error.message));
    await page.goto(base + '/login',{waitUntil:'networkidle'});
    await page.locator('[data-site-loader]').waitFor({state:'hidden'});
    const email = process.env.DESIGN_CLIENT_EMAIL || 'client@3skpartsauto.com';
    await page.locator('#inputEmail').fill(email);
    await page.locator('#inputPassword').fill(process.env.DESIGN_CLIENT_PASSWORD || email);
    await Promise.all([page.waitForURL(url=>url.pathname !== '/login'),page.locator('.login-btn').click()]);
    await page.goto(base + '/articles',{waitUntil:'networkidle'});
    await page.locator('[data-site-loader]').waitFor({state:'hidden'});
    await page.waitForFunction(()=>document.querySelector('#grid').getAttribute('aria-busy')==='false');

    let releaseOld, releaseNew;
    const oldGate = new Promise(resolve=>releaseOld=resolve);
    const newGate = new Promise(resolve=>releaseNew=resolve);
    const handler = async route => {
      const query = new URL(route.request().url()).searchParams.get('q');
      if (query === 'ancienne-requete-introuvable') await oldGate;
      if (query === 'GRILLE') await newGate;
      await route.continue().catch(()=>{});
    };
    await page.route('**/api/articles?*',handler);
    const oldRequest = page.waitForRequest(r=>new URL(r.url()).searchParams.get('q')==='ancienne-requete-introuvable');
    await page.locator('#f_q').fill('ancienne-requete-introuvable');
    await oldRequest;
    const newRequest = page.waitForRequest(r=>new URL(r.url()).searchParams.get('q')==='GRILLE');
    await page.locator('#f_q').fill('GRILLE');
    await newRequest;
    await page.waitForTimeout(100);
    assert.equal(await page.locator('#grid').getAttribute('aria-busy'),'true','Old abort must not unlock a pending newer request');
    const latestResponse = page.waitForResponse(r=>new URL(r.url()).searchParams.get('q')==='GRILLE');
    releaseNew();
    const data = await (await latestResponse).json();
    const ids = data.items.map(item=>String(item.Id));
    await page.waitForFunction(ids=>JSON.stringify([...document.querySelectorAll('#grid .product-card')].map(e=>e.dataset.code))===JSON.stringify(ids),ids);
    releaseOld();
    await page.waitForTimeout(250);
    assert.deepEqual(await page.locator('#grid .product-card').evaluateAll(es=>es.map(e=>e.dataset.code)),ids,'Late older request never replaces newer results');
    assert.equal(await page.locator('#grid').getAttribute('aria-busy'),'false');
    await page.unroute('**/api/articles?*',handler);
    await page.locator('#resetFilters').click();
    await page.waitForFunction(()=>document.querySelector('#grid').getAttribute('aria-busy')==='false');

    await page.waitForFunction(()=>!document.querySelector('#yearSlider').classList.contains('noUi-state-tap'));
    const track = await page.locator('#yearSlider').boundingBox();
    const lower = await page.locator('#yearSlider .noUi-handle-lower').boundingBox();
    const dragResponse = page.waitForResponse(r=>new URL(r.url()).pathname==='/api/articles' && new URL(r.url()).searchParams.has('anneeFrom'));
    await page.mouse.move(lower.x+lower.width/2,lower.y+lower.height/2);
    await page.mouse.down();
    await page.mouse.move(track.x+track.width*.3,lower.y+lower.height/2,{steps:8});
    await page.mouse.up();
    const dragged = new URL((await dragResponse).url());
    assert.ok(Number(dragged.searchParams.get('anneeFrom'))>1976);
    await page.waitForFunction(()=>document.querySelector('#grid').getAttribute('aria-busy')==='false');
    await page.evaluate(()=>scrollTo({top:700,behavior:'instant'}));
    const header = await page.locator('.site__header').boundingBox();
    const filters = await page.locator('.catalog-filters-area').boundingBox();
    assert.ok(Math.abs(header.y)<1 && Math.abs(filters.y-header.height)<2,'Header and compact desktop filters remain joined while scrolling');
    const link = page.locator('#mainMenuDesktop a.main-menu__link').first();
    await link.hover();
    const menu = await link.evaluate(element=>({background:getComputedStyle(element).backgroundColor,radius:getComputedStyle(element).borderRadius,shadow:getComputedStyle(element).boxShadow}));
    assert.equal(menu.radius,'0px');
    assert.equal(menu.shadow,'none');
    assert.equal(menu.background,'rgba(0, 0, 0, 0)');
    await page.goto(base + '/articles?new=1&newDays=90',{waitUntil:'networkidle'});
    await page.locator('[data-site-loader]').waitFor({state:'hidden'});
    await page.waitForFunction(()=>document.querySelector('#grid').getAttribute('aria-busy')==='false');
    assert.equal(await page.locator('#catalogNewArrivals').evaluate(e=>e.classList.contains('is-active')),true);
    await page.locator('#resetFilters').click();
    await page.waitForFunction(()=>document.querySelector('#grid').getAttribute('aria-busy')==='false');
    assert.equal(new URL(page.url()).searchParams.has('new'),false);
    assert.equal(await page.locator('#catalogNewArrivals').evaluate(e=>e.classList.contains('is-active')),false);
    // Use existing real articles as an isolated response fixture when no recent ones exist locally.
    const seed = await (await page.request.get(base+'/api/articles?brand=METELLI&limit=5')).json();
    assert.ok(seed.items.length > 0);
    await page.route('**/api/articles?*',route=>{
      const url = new URL(route.request().url());
      return url.searchParams.get('new')==='1' && url.searchParams.get('limit')==='5'
        ? route.fulfill({json:seed}) : route.continue();
    });
    await page.goto(base,{waitUntil:'networkidle'});
    await page.locator('[data-site-loader]').waitFor({state:'hidden'});
    await page.waitForFunction(()=>document.querySelector('[data-new-section] .meta-chip--brand-logo img'));
    await page.locator('[data-new-section]').screenshot({path:output+'/home-card-logos.png'});
    assert.deepEqual(errors,[]);
    console.log('OK: overlapping filter requests, slider drag, sticky header/filters, flat menu hover, arrivals/reset, home card logos with real-article response fixture; no JS error');
    await context.close();

    const publicPage = await browser.newPage({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
    await publicPage.goto(base,{waitUntil:'networkidle'});
    await publicPage.locator('[data-site-loader]').waitFor({state:'hidden'});
    const frame = await publicPage.locator('.stock-frame').evaluate(element=>({
      client:element.clientHeight,scroll:element.scrollHeight,
      rect:element.getBoundingClientRect().toJSON(),
      copy:element.querySelector('.stock-frame-copy').getBoundingClientRect().toJSON(),
      css:{height:getComputedStyle(element).height,clip:getComputedStyle(element).clipPath,overflow:getComputedStyle(element).overflow}
    }));
    fs.writeFileSync(output+'/service-frame.json',JSON.stringify(frame,null,2));
    await publicPage.close();
  } finally {await browser.close()}
})().catch(error=>{console.error(error);process.exitCode=1});
