const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
  const page = await browser.newPage({ viewport: { width: 1920, height: 1000 } });
  await page.goto('http://127.0.0.1:8765/', { waitUntil: 'networkidle' });
  console.log(JSON.stringify(await page.evaluate(() => ({
    body: { margin: getComputedStyle(document.body).margin, padding: getComputedStyle(document.body).padding, y: document.body.getBoundingClientRect().y },
    html: { margin: getComputedStyle(document.documentElement).margin, padding: getComputedStyle(document.documentElement).padding },
    text: [...document.body.childNodes].filter(n => n.nodeType === 3).map(n => [...n.textContent].map(c => c.charCodeAt(0))),
    ancestors: [...document.querySelector('.showcase-header').parentElement.parentElement.children].map(el => ({ tag: el.tagName, cls: el.className, y: el.getBoundingClientRect().y, h: el.getBoundingClientRect().height, margin: getComputedStyle(el).margin, padding: getComputedStyle(el).padding, display: getComputedStyle(el).display })),
    carousel: document.getElementById('showcaseCarousel').outerHTML.slice(0, 200),
    weight: getComputedStyle(document.querySelector('.showcase-title')).fontWeight,
  })), null, 2));
  await browser.close();
})().catch(console.error);
