const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const crypto = require('node:crypto');
const { execFileSync } = require('node:child_process');
const root = process.cwd();
const log = 'C:/Users/wedigitall/.codex/sessions/2026/10/16/rollout-2026-10-16T15-45-45-019e9e1b-4447-7543-8714-d69d210bf02a_01a144f6-2a6e-7ea0-a80f-b4044b62c8da.jsonl';
const rows = fs.readFileSync(log, 'utf8').trim().split('\n').map(JSON.parse);
const out = path.join(root, 'tmp/rollback-design-20261016-1918/restored-baseline');
const files = new Map();
const normalize = value => value.replace(/^\uFEFF/, '').replace(/\r\n/g, '\n').trimEnd() + '\n';
const relative = file => file.replace(/\\/g, '/').replace(/^F:\/3sk\/3sk \(1\)\//i, '');
const target = 'templates/home/_public_content.html.twig';
const cssTarget = 'public/styles/home-about-services.css';
files.set(target, normalize(execFileSync('git', ['show', `HEAD:${target}`], { encoding: 'utf8' })));

function apply(patch, allowed, reverse = false) {
  const lines = patch.replace(/\r\n/g, '\n').split('\n');
  for (let i = 0; i < lines.length; i++) {
    const header = lines[i].match(/^\*\*\* (Update|Add|Delete) File: (.+)$/);
    if (!header) continue;
    const file = relative(header[2]);
    const kind = header[1];
    const body = [];
    while (i + 1 < lines.length && !lines[i + 1].startsWith('*** ')) body.push(lines[++i]);
    if (!allowed.has(file)) continue;
    if (kind === 'Add') { if (!reverse) files.set(file, body.map(line => line.slice(1)).join('\n') + '\n'); continue; }
    if (kind !== 'Update') throw new Error('Unexpected recovery operation');
    if (!files.has(file)) throw new Error(`Missing source: ${file}`);
    let source = files.get(file);
    const chunks = [];
    for (const line of body) {
      if (line.startsWith('@@')) chunks.push([]);
      else if (chunks.length) chunks[chunks.length - 1].push(line);
    }
    if (reverse) chunks.reverse();
    for (const chunk of chunks) {
      const before = chunk.filter(line => line[0] === (reverse ? '+' : '-') || line[0] === ' ').map(line => line.slice(1)).join('\n') + '\n';
      const after = chunk.filter(line => line[0] === (reverse ? '-' : '+') || line[0] === ' ').map(line => line.slice(1)).join('\n') + '\n';
      const at = source.indexOf(before);
      if (at < 0) throw new Error(`Cannot recover ${file}: ${before.slice(0, 100)}`);
      source = source.slice(0, at) + (after === '\n' ? '' : after) + source.slice(at + before.length);
    }
    files.set(file, source);
  }
}

async function replay(index, allowed) {
  const code = rows[index].payload.input;
  const tools = {
    apply_patch: async patch => { apply(patch, allowed); return {}; },
    exec_command: async ({ cmd }) => {
      const match = cmd.match(/Get-Content(?: -LiteralPath)? ['"]?([^'"\s]+)['"]?(?: -Raw)?/);
      return { exit_code: 0, output: match && files.has(relative(match[1])) ? files.get(relative(match[1])) : '' };
    },
    write_stdin: async () => ({}), view_image: async () => ({}),
  };
  await vm.runInNewContext(`(async () => {${code}\n})()`, { tools, text() {}, image() {} });
}

function outputChunk(id) {
  for (const row of rows) {
    if (row.payload?.type !== 'custom_tool_call_output' || !Array.isArray(row.payload.output)) continue;
    for (const item of row.payload.output) {
      if (item.type !== 'input_text') continue;
      if (item.text.startsWith('{')) {
        try { const value = JSON.parse(item.text); if (value.chunk_id === id) return value.output; } catch {}
      }
      for (const line of item.text.split('\n')) {
        if (!line.startsWith('{')) continue;
        try { const value = JSON.parse(line); if (value.chunk_id === id) return value.output; } catch {}
      }
    }
  }
  throw new Error(`Historical output unavailable: ${id}`);
}

(async () => {
  const normalizer = fs.readFileSync('tools/normalize-design-colors.cjs', 'utf8');
  const normalizeColor = vm.runInNewContext(normalizer.slice(0, normalizer.indexOf('function visit')) + ';normalize;', { require });
  const postcss = require('postcss');
  const events = [];
  const allowed = new Set([target, cssTarget]);
  for (let index = 0; index < rows.length; index++) {
    if (index >= 1900) break;
    const row = rows[index];
    if (index === 345) {
      files.set(target, files.get(target).replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, (block, css) => {
        const ast = postcss.parse(css);
        ast.walkDecls(declaration => { declaration.value = normalizeColor(declaration.value); });
        return block.replace(css, ast.toString());
      }));
    }
    if (row.payload?.type !== 'custom_tool_call' || !/tools.apply_patch/.test(row.payload.input || '') || !/_public_content|home-about-services.css/.test(row.payload.input)) continue;
    try { await replay(index, allowed); events.push({ index, status: 'replayed' }); }
    catch (error) { events.push({ index, status: 'failed', message: error.message }); }
  }
  files.set('CONTRAT_DESIGN.md', normalize(outputChunk('f910b8')));
  files.set('public/styles/site-footer.css', normalize(outputChunk('aaae2c')));
  files.set('public/styles/typography.css', normalize(outputChunk('ae0012')));
  files.set('public/styles/partner-brands.css', normalize(outputChunk('f9db1f')));
  const partials = normalize(outputChunk('78e8d5'));
  const serviceStart = partials.indexOf('<section id="services"');
  files.set('templates/home/sections/_about.html.twig', partials.slice(0, serviceStart));
  files.set('templates/home/sections/_services.html.twig', partials.slice(serviceStart));
  const reverseFiles = new Set(['assets/app.js', 'templates/partials/_public_header.html.twig', 'templates/home/sections/_partner_brands.html.twig']);
  for (const file of reverseFiles) files.set(file, normalize(fs.readFileSync(file, 'utf8')));
  for (let index = 2164; index >= 1994; index--) {
    if (index === 2081) continue;
    const code = rows[index]?.payload?.input;
    if (!code || rows[index].payload.type !== 'custom_tool_call') continue;
    const literals = [...code.matchAll(/tools\.apply_patch\(("(?:[^"\\]|\\.)*")\)/g)].reverse();
    for (const match of literals) apply(JSON.parse(match[1]), reverseFiles, true);
  }
  let showcase = normalize(fs.readFileSync('public/styles/public-showcase.css', 'utf8'));
  showcase = showcase.replaceAll('var(--brand-font)', "'Montserrat', sans-serif")
    .replace('--showcase-gray: var(--brand-gray)', '--showcase-gray: var(--brand-bg)')
    .replace('--showcase-border: var(--brand-border)', '--showcase-border: rgba(35, 43, 56, .16)')
    .replace('min-height: 88px; padding: 14px var(--brand-gutter)', 'min-height: 96px; padding: 16px 2.65%')
    .replace('padding: 6px var(--brand-gutter)', 'padding: 6px 2.65%')
    .replace('height: var(--brand-control-height); border: 1px', 'height: 50px; border: 1px')
    .replace('min-height: 52px', 'min-height: 54px')
    .replace('min-height: 44px; padding: 10px 20px', 'min-height: 46px; padding: 12px 22px')
    .replace('min-height: 50px; padding: 12px 24px', 'min-height: 58px; padding: 15px 26px')
    .replace('min-width: 240px', 'min-width: 264px')
    .replace('font-size: 80px', 'font-size: 88px')
    .replace('padding: 56px 0 46px var(--brand-gutter)', 'padding: 64px 0 46px 2.65%');
  files.set('public/styles/public-showcase.css', showcase);
  let base = normalize(fs.readFileSync('templates/base.html.twig', 'utf8'));
  base = base.replace('<html lang="fr">', '<html>').replace('        <link rel="stylesheet" href="{{ asset(\'styles/design-tokens.css\') }}?v=20261016-1">\n', '').replace("styles/site-footer.css') }}?v=20261016-3", "styles/site-footer.css') }}?v=20261016-2").replace("styles/typography.css') }}?v=20261016-3", "styles/typography.css') }}?v=20261016-2");
  files.set('templates/base.html.twig', base);
  files.set('assets/styles/app.scss', normalize(outputChunk('164c74')));
  let design = normalize(fs.readFileSync('templates/partials/_design_system.html.twig', 'utf8'));
  const oldPrefix = outputChunk('b5e059').replace(/\r\n/g, '\n');
  design = oldPrefix.slice(0, oldPrefix.indexOf('  html {')) + design.slice(design.indexOf('  html {'));
  const oldContact = outputChunk('614a58').replace(/\r\n/g, '\n');
  const contactBlock = oldContact.slice(oldContact.indexOf('  .public-contact-v2 .contact-pane-dark {'), oldContact.indexOf('  .public-cookie-banner {'));
  design = design.replace('  .public-cookie-banner {', contactBlock + '  .public-cookie-banner {');
  design = design.replace('  textarea:not(.contact-form-control),\n', '  textarea,\n  .contact-form-control,\n').replace('  textarea:not(.contact-form-control):focus,\n', '  textarea:focus,\n  .contact-form-control:focus,\n');
  design = design.replace('    min-height: var(--brand-control-height);\n', '    min-height: 46px;\n');
  design = design.replace('    border: 1px solid var(--brand-border-strong) !important;\n', '    border: 1px solid var(--brand-border) !important;\n');
  design = design.replace('  .form-label,\n  .orders-filter-label,', '  .form-label,\n  label,\n  .orders-filter-label,');
  design = design.replace('    font-size: .78rem;\n    font-weight: 500 !important;\n    letter-spacing: 0;', '    font-size: .78rem;\n    font-weight: 500 !important;\n    letter-spacing: .035em;');
  design = design.replace(/^(\s*)body\.app-connected \.latest-arrivals-eyebrow(\s*\{|::before\s*\{)/gm, '$1body.app-public .public-main-v2 .section-eyebrow$2,REPAIR');
  // Restore the original public selectors beside their connected counterparts.
  design = design.replace(/^(\s*)body\.app-public \.public-main-v2 \.section-eyebrow(\s*\{|::before\s*\{),REPAIR/gm, (all, indent, suffix) => {
    const pseudo = suffix.startsWith('::before') ? '::before' : '';
    return `${indent}body.app-public .public-main-v2 .section-eyebrow${pseudo},\n${indent}body.app-connected .latest-arrivals-eyebrow${suffix}`;
  });
  design = design.replace(/^( *)body\.app-connected \.home-section-head h2,/gm, '$1body.app-public .public-main-v2 .section-heading,\n$1body.app-connected .home-section-head h2,');
  design = design.replace('  body.app-connected .latest-arrivals-title .heading-accent {', '  body.app-public .public-main-v2 .section-heading .heading-accent,\n  body.app-connected .latest-arrivals-title .heading-accent {');
  design = design.replaceAll('.section-eyebrow,\n\n', '.section-eyebrow,\n').replaceAll('.section-eyebrow::before,\n\n', '.section-eyebrow::before,\n');
  const storefrontAt = design.indexOf('  /* Storefront composition:');
  design = design.slice(0, storefrontAt) + design.slice(storefrontAt).replace(/^ *body\.app-public \.public-main-v2 \.section-heading,\n/gm, '');
  files.set('templates/partials/_design_system.html.twig', design);
  files.set(target, files.get(target).replace("    {% include 'home/sections/_hero.html.twig' with { hero_full_width: true } %}", "    {% include 'home/sections/_showcase.html.twig' %}"));
  files.set(target, files.get(target).replace('<h1 class="section-heading">Pieces Carrosserie & accessoires <span class="heading-accent">automobile</span></h1>', '<h2 class="section-heading">Pieces Carrosserie & accessoires <span class="heading-accent">automobile</span></h2>'));
  const sourceChecks = [];
  for (const id of ['f3c1f0', 'ae3890', '08f7d7', 'deec50', '2a80fe']) {
    const fragment = outputChunk(id).replace(/\r\n/g, '\n');
    sourceChecks.push({ id, matches: files.get(target).includes(fragment), line: files.get(target).slice(0, files.get(target).indexOf(fragment)).split('\n').length });
    if (!files.get(target).includes(fragment)) {
      const start = id === '2a80fe' ? files.get(target).split('\n').slice(0, 1700).join('\n').length + 1 : files.get(target).indexOf(fragment.split('\n').slice(0, 3).join('\n'));
      const actual = files.get(target).slice(start).split('\n'); const expected = fragment.split('\n');
      const at = expected.findIndex((line, index) => line !== actual[index]);
      console.log({ id, startLine: files.get(target).slice(0, start).split('\n').length, offset: at, expected: expected[at], actual: actual[at] });
    }
  }
  const truncated = rows[1920].payload.output[1].text;
  const opening = truncated.indexOf('"output":"') + 10;
  let escaped = truncated.slice(opening, truncated.indexOf('…'));
  escaped = escaped.slice(0, escaped.lastIndexOf('\\r\\n') + 4);
  const prefix = JSON.parse('"' + escaped + '"').replace(/\r\n/g, '\n');
  sourceChecks.push({ id: 'full-source-prefix', matches: files.get(target).startsWith(prefix), lines: prefix.split('\n').length });
  sourceChecks.push({ id: 'base-prefix', matches: files.get('templates/base.html.twig').startsWith(outputChunk('850fb9').replace(/\r\n/g, '\n')) });
  sourceChecks.push({ id: 'global-style-prefix', matches: files.get('templates/partials/_design_system.html.twig').startsWith(outputChunk('b5e059').replace(/\r\n/g, '\n')) });
  sourceChecks.push({ id: 'scss-original', matches: files.get('assets/styles/app.scss') === normalize(outputChunk('164c74')) });
  sourceChecks.push({ id: 'global-public-type', matches: design.includes(outputChunk('f211f0').replace(/\r\n/g, '\n')) });
  for (const [file, id] of [['templates/base.html.twig', '850fb9'], ['templates/partials/_design_system.html.twig', 'b5e059'], ['assets/styles/app.scss', '164c74']]) {
    const actual = files.get(file).split('\n'); const expected = outputChunk(id).replace(/\r\n/g, '\n').split('\n');
    const at = expected.findIndex((line, index) => line !== actual[index]);
    console.log({ file, firstDifference: at + 1, expected: expected[at], actual: actual[at] });
  }
  if (!files.get(target).startsWith(prefix)) {
    const expected = prefix.split('\n'); const actual = files.get(target).split('\n');
    const at = expected.findIndex((line, index) => line !== actual[index]);
    console.log({ firstDifference: at + 1, expected: expected[at], actual: actual[at] });
  }
  for (const [file, source] of files) {
    const destination = path.join(out, file);
    fs.mkdirSync(path.dirname(destination), { recursive: true });
    fs.writeFileSync(destination, source);
  }
  fs.writeFileSync(path.join(out, 'recovery-checks.json'), JSON.stringify(sourceChecks, null, 2));
  console.log(JSON.stringify({ files: [...files.keys()], events, sourceChecks, lines: files.get(target).split('\n').length, preview: files.get(target).slice(0, 100) }, null, 2));
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
