(() => {
  'use strict';
  const element = document.getElementById('catalog-photo-refresh-config');
  if (!element) return;
  const config = JSON.parse(element.textContent);
  const states = new Map();
  const controls = new Map();
  const icons = {
    missing: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h10M4 4v16h16V10M7 16l4-4 4 4 2-2 3 3M18 2v8m-3-3 3 3 3-3"/></svg>',
    update: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M19 12a7 7 0 0 0-12-5L4 10m16 4-3 3A7 7 0 0 1 5 12"/></svg>',
    queued: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>',
  };
  function render(control) {
    const state = states.get(control.id);
    const label = state === 'queued' ? 'Demande enregistrée' : state === 'sending' ? 'Enregistrement de la demande…' : control.missing ? 'Récupérer la photo manquante' : 'Mettre à jour la photo';
    control.button.title = label;
    control.button.setAttribute('aria-label', label);
    control.button.disabled = state === 'sending' || state === 'queued';
    control.button.setAttribute('aria-busy', String(state === 'sending'));
    control.button.classList.toggle('is-queued', state === 'queued');
    control.button.classList.toggle('is-missing', control.missing);
    control.button.innerHTML = icons[state === 'queued' ? 'queued' : control.missing ? 'missing' : 'update'];
    if (state === 'queued') control.message.textContent = 'Demande enregistrée';
    if (state === 'sending') control.message.textContent = 'Enregistrement…';
  }
  window.CatalogPhotoRefresh = {
    attach(card, {uniqueId, fallback}) {
      if (!/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i.test(uniqueId || '')) return;
      const container = card.querySelector('.product-card__image');
      const image = container?.querySelector('.product-img');
      if (!container || !image) return;
      const id = uniqueId.toUpperCase();
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'catalog-photo-action';
      const message = document.createElement('span');
      message.className = 'catalog-photo-message';
      message.setAttribute('role', 'status');
      message.setAttribute('aria-live', 'polite');
      const control = {id, button, message, missing: false};
      if (!controls.has(id)) controls.set(id, []);
      controls.set(id, controls.get(id).filter(item => item.button.isConnected));
      controls.get(id).push(control);
      container.append(button, message);
      const checkImage = (failed = false) => {
        const isFallback = fallback && image.src === new URL(fallback, document.baseURI).href;
        control.missing = failed || isFallback || (image.complete && image.naturalWidth === 0);
        render(control);
      };
      image.addEventListener('error', () => checkImage(true));
      image.addEventListener('load', () => checkImage());
      checkImage();
      button.addEventListener('click', async event => {
        event.preventDefault();
        event.stopPropagation();
        if (states.has(id)) return;
        states.set(id, 'sending');
        controls.get(id).forEach(render);
        try {
          const response = await fetch(config.url.replace(config.placeholder, encodeURIComponent(uniqueId)), {
            method: 'POST', credentials: 'same-origin', signal: AbortSignal.timeout(25000),
            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': config.token},
          });
          let data;
          try { data = await response.json(); } catch { throw new Error('Réponse indisponible. Rechargez la page puis réessayez.'); }
          if (!response.ok || data.ok !== true) throw new Error(data.message || 'La demande n’a pas été enregistrée.');
          states.set(id, 'queued');
        } catch (error) {
          states.delete(id);
          controls.get(id).forEach(item => { item.message.textContent = error.name === 'TimeoutError' ? 'Délai dépassé. Réessayez.' : error.message || 'Connexion impossible. Réessayez.'; });
        }
        controls.get(id).forEach(render);
      });
    },
  };
})();
