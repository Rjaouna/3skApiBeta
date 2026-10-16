const {chromium} = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const base='http://127.0.0.1:8765';
const widths=[390,1024,1440,1920];
fs.mkdirSync('tmp/header-topbar-spacing',{recursive:true});

async function snapshot(page) {
  await page.locator('[data-site-loader]').waitFor({state:'hidden'});
  await page.evaluate(()=>document.fonts.ready);
  return page.evaluate(()=>{
    const style=element=>{
      const s=getComputedStyle(element);
      return Object.fromEntries(['fontFamily','fontSize','fontWeight','lineHeight','color'].map(key=>[key,s[key]]));
    };
    const input=document.querySelector('.showcase-search input');
    const image=document.querySelector('.showcase-logo img');
    return {
      contacts:[...document.querySelectorAll('.showcase-contact')].map(element=>({text:element.textContent.trim().replace(/\s+/g,' '),href:element.getAttribute('href'),style:style(element),children:[...element.querySelectorAll('strong,small')].map(style)})),
      search:{height:document.querySelector('.showcase-search').getBoundingClientRect().height,style:style(input)},
      logo:{src:image.getAttribute('src'),width:image.getBoundingClientRect().width},
      topbar:getComputedStyle(document.querySelector('.showcase-topbar')).backgroundColor,
      overflow:document.documentElement.scrollWidth>innerWidth
    };
  });
}

(async()=>{
  const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
  try {
    const reference={};
    const page=await browser.newPage({reducedMotion:'reduce'});
    for(const width of widths){
      await page.setViewportSize({width,height:1000});
      await page.goto(base,{waitUntil:'networkidle'});
      reference[width]=await snapshot(page);
      if(width===1920){
        const spacing=await page.evaluate(()=>{
          const facts=document.querySelector('.showcase-topbar__facts').getBoundingClientRect();
          const button=document.querySelector('.showcase-topbar__inner > .showcase-join').getBoundingClientRect();
          return {actual:button.left-facts.right,expected:parseFloat(getComputedStyle(document.querySelector('.showcase-topbar__inner')).columnGap)};
        });
        assert.ok(Math.abs(spacing.actual-spacing.expected)<1,'Public facts stay beside the account button without an automatic empty margin');
      }
      if([390,1920].includes(width)) await page.locator('.showcase-topbar').screenshot({path:`tmp/header-topbar-spacing/public-${width}.png`});
    }
    await page.close();
    for(const role of ['client','admin']) {
      const context=await browser.newContext({reducedMotion:'reduce'});
      await context.route('**/api/cart**',route=>['GET','HEAD','OPTIONS'].includes(route.request().method())?route.continue():route.abort());
      const page=await context.newPage();
      await page.goto(base+'/login',{waitUntil:'networkidle'});
      await page.locator('[data-site-loader]').waitFor({state:'hidden'});
      const email=process.env[`DESIGN_${role.toUpperCase()}_EMAIL`]||role+'@3skpartsauto.com';
      await page.locator('#inputEmail').fill(email);
      await page.locator('#inputPassword').fill(process.env[`DESIGN_${role.toUpperCase()}_PASSWORD`]||email);
      await Promise.all([page.waitForURL(url=>url.pathname!=='/login'),page.locator('.login-btn').click()]);
      for(const width of widths){
        await page.setViewportSize({width,height:1000});
        await page.goto(base+'/articles',{waitUntil:'networkidle'});
        const actual=await snapshot(page);
        assert.deepEqual(actual.contacts,reference[width].contacts,`Shared contact content/type ${role} ${width}`);
        assert.deepEqual(actual.search,reference[width].search,`Shared search type/size ${role} ${width}`);
        assert.equal(actual.logo.src,reference[width].logo.src);
        assert.ok(Math.abs(actual.logo.width-reference[width].logo.width)<1,'Same logo size');
        assert.equal(actual.topbar,reference[width].topbar);
        assert.equal(actual.overflow,false);
        assert.equal(await page.locator('.showcase-topbar__facts').count(),0,'No facts block rendered after login');
        assert.equal(await page.locator('[title="Compte"]').count(),1);
        assert.equal(await page.locator('[title="Notifications"]').count(),1);
        assert.equal(await page.locator('#voiceSearchBtn').count(),1);
        assert.equal(await page.locator('#cartOffcanvas').count(),1);
        assert.equal(await page.locator('#adminSettingsModal').count(),role==='admin'?1:0);
        if([390,1920].includes(width)) await page.locator('.showcase-topbar').screenshot({path:`tmp/header-topbar-spacing/${role}-${width}.png`});
        console.log(`OK ${role} ${width}: same contacts, typography, logo, search, palette; connected controls preserved`);
      }
      for(const width of [390,1920]){
        await page.setViewportSize({width,height:1000});
        await page.goto(base+'/mon-profil',{waitUntil:'networkidle'});
        await page.locator('[data-site-loader]').waitFor({state:'hidden'});
        assert.equal(await page.locator('.showcase-topbar__facts').count(),0,'Profile also omits the facts block');
        await page.locator('[title="Compte"]').click();
        await page.locator('.account-menu.show').waitFor({state:'visible'});
        assert.equal(await page.locator('.account-menu a[href="/mon-profil"]').count(),1);
        await page.locator('[title="Compte"]').click();
        assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
        console.log(`OK ${role} profile ${width}: facts omitted, account menu functional, no overflow`);
      }
      await context.close();
    }
    fs.writeFileSync('tmp/instant-refinement/header-parity.json',JSON.stringify(reference,null,2));
  } finally {await browser.close()}
})().catch(error=>{console.error(error);process.exitCode=1});
