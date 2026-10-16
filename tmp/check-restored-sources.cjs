const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const assert = require('node:assert/strict');
const root = 'tmp/rollback-design-20261016-1918/restored-baseline';
const normalize = value => value.replace(/\r\n/g, '\n').trimEnd();
const checks = [];
function visit(directory, prefix = '') {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const relative = prefix + entry.name;
    if (entry.isDirectory()) visit(path.join(directory, entry.name), relative + '/');
    else if (relative !== 'recovery-checks.json') {
      const before = normalize(fs.readFileSync(path.join(directory, entry.name), 'utf8'));
      const current = normalize(fs.readFileSync(relative, 'utf8'));
      if (!['CONTRAT_DESIGN.md', 'templates/home/_public_content.html.twig'].includes(relative)) {
        assert.equal(current, before, relative + ': restored source changed');
        checks.push({ file: relative, unchanged: true });
      }
    }
  }
}
visit(root);
const before = normalize(fs.readFileSync(root + '/templates/home/_public_content.html.twig', 'utf8'));
const current = normalize(fs.readFileSync('templates/home/_public_content.html.twig', 'utf8'));
const scripts = source => [...source.matchAll(/<script\b[^>]*>[\s\S]*?<\/script>/g)].map(match => match[0]);
assert.deepEqual(scripts(current), scripts(before), 'Existing form and cookie scripts changed');
const oldContact = before.slice(before.indexOf('    <section id="contact"'), before.indexOf('  </main>'));
const remaining = before.replace(oldContact, '');
const actualRemaining = current.replace('<link rel="stylesheet" href="{{ asset(\'styles/home-contact.css\') }}?v=20261016-1">\n', '').replace("    {% include 'home/sections/_contact.html.twig' %}\n", '');
assert.equal(actualRemaining, remaining, 'Public home changed outside the contact block');
const form = source => source.match(/<form class="contact-form"[\s\S]*?<\/form>/)[0];
assert.equal(form(fs.readFileSync('templates/home/sections/_contact.html.twig', 'utf8')), form(oldContact), 'Contact fields or constraints changed');
const css = require('postcss').parse(fs.readFileSync('public/styles/home-contact.css', 'utf8'));
css.walkRules(rule => assert.ok(rule.selector.includes('#contact.contact-premium'), `Unscoped selector: ${rule.selector}`));
for (const [file, expected] of [
  ['src/Controller/Api/CartApiController.php', '687859F65C316AE24D1CD98710759AF8496EBB91CC0B50E21B9EDEA111A70B21'],
  ['src/Controller/HomeController.php', '073FC348B021E4A5E126A3272459CC642C938429E2FFB13025C8739DCEC6F049'],
  ['public/img/slides/080920251757318688-69ef174b1e570-6a246a350e402.webp', '8425948A7149AE0998FE7A4A2F07D46017EED2DEC2162B7459F802E755F03FA4'],
]) {
  const hash = crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex').toUpperCase();
  assert.equal(hash, expected, file + ': unexpected change');
  checks.push({ file, unchanged: true, sha256: hash });
}
for (const file of ['public/styles/design-tokens.css', 'public/styles/public-home.css', 'tools/check-home-harmonization.cjs']) assert.equal(fs.existsSync(file), false);
fs.writeFileSync('tmp/rollback-design-20261016-1918/source-checks.json', JSON.stringify({ checks, formUnchanged: true, scriptsUnchanged: true, publicHomeOutsideContactUnchanged: true, cssContactScoped: true }, null, 2));
console.log(`${checks.length} protected source/media checks passed; contact form and scripts identical; CSS isolated.`);
