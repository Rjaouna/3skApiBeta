(() => {
  const registry = document.getElementById('product-brand-logos');
  const logos = JSON.parse(registry?.textContent || '{}');
  const escape = value => String(value).replace(/[&<>"']/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[character]));
  const slug = name => String(name || '').trim().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/œ/g, 'oe').replace(/æ/g, 'ae')
    .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

  function badge(name) {
    const label = String(name || '').trim();
    if (!label) return '';
    const logo = logos[slug(label)];
    if (!logo) return `<span class="meta-chip">${escape(label)}</span>`;
    return `<span class="meta-chip meta-chip--brand-logo" title="${escape(label)}">
      <img src="${escape(logo)}" alt="${escape(label)}" loading="lazy" decoding="async">
      <span class="brand-logo-fallback" hidden>${escape(label)}</span>
    </span>`;
  }

  // A missing/replaced file leaves a readable name in the same reserved space.
  document.addEventListener('error', event => {
    const image = event.target;
    if (image instanceof HTMLImageElement && image.closest('.meta-chip--brand-logo')) {
      image.hidden = true;
      if (image.nextElementSibling) image.nextElementSibling.hidden = false;
    }
  }, true);

  window.ProductBrandLogos = Object.freeze({ badge, slug });
})();
