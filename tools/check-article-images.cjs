const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { randomUUID } = require('node:crypto');

const base = 'http://127.0.0.1:8765';
// PHP's built-in server does not rewrite missing .png files without a router script.
const imageBase = base + (process.argv.includes('--front-controller') ? '/index.php' : '');
const directory = path.resolve(__dirname, '../export_images');
const createdDirectory = !fs.existsSync(directory);
const id = randomUUID().toUpperCase();
const fixture = path.join(directory, `${id}.png`);
const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLttAAAAABJRU5ErkJggg==', 'base64');

(async () => {
  let browser;
  let createdFixture = false;
  try {
    fs.mkdirSync(directory, { recursive: true });
    fs.writeFileSync(fixture, png, { flag: 'wx' });
    createdFixture = true;
    browser = await chromium.launch({
      executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
      headless: true,
    });

    const guest = await browser.newContext();
    for (const prefix of ['ebpsynch', 'export_images']) {
      const response = await guest.request.get(`${imageBase}/${prefix}/${id}.png`, { maxRedirects: 0 });
      assert.equal(response.status(), 302, 'Image fallback requires a connected account');
      assert.ok(new URL(response.headers().location, base).pathname.endsWith('/login'));
    }
    await guest.close();

    const results = [];
    for (const role of ['client', 'admin']) {
      const context = await browser.newContext({ reducedMotion: 'reduce' });
      await context.route('**/api/cart**', route => ['GET', 'HEAD', 'OPTIONS'].includes(route.request().method())
        ? route.continue()
        : route.abort());
      const page = await context.newPage();
      await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
      await page.locator('[data-site-loader]').waitFor({ state: 'hidden' });
      const email = process.env[`DESIGN_${role.toUpperCase()}_EMAIL`] || `${role}@3skpartsauto.com`;
      await page.locator('#inputEmail').fill(email);
      await page.locator('#inputPassword').fill(process.env[`DESIGN_${role.toUpperCase()}_PASSWORD`] || email);
      await Promise.all([
        page.waitForURL(url => url.pathname !== '/login'),
        page.locator('.login-btn').click(),
      ]);
      const cartBefore = await (await context.request.get(`${base}/api/cart`)).json();

      for (const prefix of ['ebpsynch', 'export_images']) {
        const url = `${imageBase}/${prefix}/${id}.png`;
        const response = await context.request.get(url);
        assert.equal(response.status(), 200);
        assert.equal(response.headers()['content-type'], 'image/png');
        assert.equal(response.headers()['x-content-type-options'], 'nosniff');
        assert.match(response.headers()['cache-control'], /private/);
        assert.deepEqual(await response.body(), png);

        const head = await context.request.head(url);
        assert.equal(head.status(), 200);
        assert.equal(head.headers()['content-length'], String(png.length));
        assert.equal((await head.body()).length, 0);

        const cached = await context.request.get(url, { headers: { 'If-None-Match': response.headers().etag } });
        assert.equal(cached.status(), 304);
        assert.equal((await cached.body()).length, 0);

        const decoded = await page.evaluate(async source => {
          const image = new Image();
          image.src = source;
          await image.decode();
          return [image.naturalWidth, image.naturalHeight];
        }, url);
        assert.deepEqual(decoded, [1, 1]);
        assert.equal((await context.request.get(`${imageBase}/${prefix}/${randomUUID()}.png`)).status(), 404);
        assert.equal((await context.request.get(`${imageBase}/${prefix}/items.csv`)).status(), 404);
        assert.equal((await context.request.get(`${imageBase}/${prefix}/%2e%2e%2fitems.csv`)).status(), 404);
        results.push({ role, prefix, image: 200, head: 200, cached: 304, decoded, csv: 404, traversal: 404 });
      }

      const cartAfter = await (await context.request.get(`${base}/api/cart`)).json();
      assert.deepEqual(cartAfter, cartBefore, 'Image loading does not change the cart');
      await context.close();
    }
    console.log(JSON.stringify({ ok: true, imageBase, guest: 'login required', results }, null, 2));
  } finally {
    if (browser) await browser.close();
    if (createdFixture) fs.unlinkSync(fixture);
    if (createdDirectory && fs.existsSync(directory) && fs.readdirSync(directory).length === 0) fs.rmdirSync(directory);
  }
})().catch(error => {
  console.error(error);
  process.exitCode = 1;
});
