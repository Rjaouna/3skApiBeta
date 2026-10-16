const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const postcss = require('postcss');

const colors = {
  '#315fbb': 'var(--brand-blue)',
  '#ff9100': 'var(--brand-orange)',
  '#ff9307': 'var(--brand-orange)',
  '#102e63': 'var(--brand-blue-dark)',
  '#23498f': 'var(--brand-blue-dark)',
  '#214f9f': 'var(--brand-blue-dark)',
  '#0d47a1': 'var(--brand-blue)',
  '#df7600': 'var(--brand-orange-dark)',
  '#d97706': 'var(--brand-orange-dark)',
  '#070b12': 'var(--brand-anthracite)',
  '#111827': 'var(--brand-ink)',
  '#101828': 'var(--brand-ink)',
  '#1f2937': 'var(--brand-ink)',
  '#0f172a': 'var(--brand-ink)',
  '#344054': 'var(--brand-ink)',
  '#667085': 'var(--brand-muted)',
  '#6b7280': 'var(--brand-muted)',
  '#747b84': 'var(--brand-muted)',
  '#7b8492': 'var(--brand-muted)',
  '#98a2b3': 'var(--brand-muted)',
  '#f8f9fa': 'var(--brand-bg)',
  '#f8fafc': 'var(--brand-bg)',
  '#f5f7fb': 'var(--brand-bg)',
  '#f7f8fa': 'var(--brand-bg)',
  '#f3f5f7': 'var(--brand-bg)',
  '#f3f4f6': 'var(--brand-bg)',
  '#f7f9fb': 'var(--brand-bg)',
  '#f6f9ff': 'var(--brand-bg)',
  '#f7faff': 'var(--brand-bg)',
  '#fff7ed': 'var(--brand-bg)',
  '#dbeafe': 'var(--brand-bg)',
  '#dfe3e8': 'var(--brand-border)',
  '#dee2e6': 'var(--brand-border)',
  '#e5e7eb': 'var(--brand-border)',
  '#e8ebef': 'var(--brand-border)',
  '#cfd5dc': 'var(--brand-border-strong)',
};
const styles = /<style\b[^>]*>([\s\S]*?)<\/style>/gi;
const businessHash = source => crypto.createHash('sha256').update(source.replace(styles, '<style></style>')).digest('hex');
const changes = [];
function normalize(value) {
  return value.replace(/#[a-f0-9]{6}\b/gi, match => colors[match.toLowerCase()] || match)
    .replace(/rgba?\(\s*(49\s*,\s*95\s*,\s*187|255\s*,\s*145\s*,\s*0|16\s*,\s*24\s*,\s*40|17\s*,\s*24\s*,\s*39|15\s*,\s*23\s*,\s*42)\s*(?:,\s*([\d.]+))?\)/gi, (match, channels, alpha) => {
      const first = Number(channels.split(',')[0]);
      const token = first === 49 ? 'blue' : first === 255 ? 'orange' : 'anthracite';
      return alpha === undefined ? `rgb(var(--brand-${token}-rgb))` : `rgba(var(--brand-${token}-rgb), ${alpha})`;
    })
    .replace(/rgb\(255\s+145\s+0\)/g, 'var(--brand-orange)');
}
function visit(directory) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const file = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      if (entry.name !== 'emails') visit(file);
    } else if (file.endsWith('.twig') && !file.endsWith(path.join('order', 'print.html.twig'))) {
      const source = fs.readFileSync(file, 'utf8');
      const updated = source.replace(styles, (block, css) => {
        const ast = postcss.parse(css, { from: file });
        ast.walkDecls(declaration => { declaration.value = normalize(declaration.value); });
        return block.replace(css, ast.toString());
      });
      if (source !== updated) {
        if (businessHash(source) !== businessHash(updated)) throw new Error(`Non-style change: ${file}`);
        if (process.argv.includes('--write')) fs.writeFileSync(file, updated);
        changes.push(file);
      }
    }
  }
}
visit('templates');
console.log(JSON.stringify({ mode: process.argv.includes('--write') ? 'write' : 'dry-run', changed: changes.length, files: changes, businessMarkupUnchanged: true }, null, 2));
