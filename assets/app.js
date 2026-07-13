import './styles/app.css';
import './styles/app.scss';

const initSiteLoader = () => {
  if (window.__siteLoaderInitialized) return;
  window.__siteLoaderInitialized = true;

  const root = document.documentElement;
  const loader = document.querySelector('[data-site-loader]');
  const bar = document.querySelector('[data-site-loader-bar]');
  const percent = document.querySelector('[data-site-loader-percent]');

  if (!loader || !bar || !percent) {
    root.classList.remove('site-is-loading');
    return;
  }

  let progress = 4;
  let done = false;

  const render = (value) => {
    const safeValue = Math.max(0, Math.min(100, Math.round(value)));
    bar.style.width = `${safeValue}%`;
    percent.textContent = `${safeValue}%`;
  };

  const timer = window.setInterval(() => {
    if (done) return;
    const step = progress < 62 ? 7 : progress < 86 ? 3 : 1;
    progress = Math.min(94, progress + step);
    render(progress);
  }, 120);

  const finish = () => {
    if (done) return;
    done = true;
    window.clearInterval(timer);
    render(100);

    window.setTimeout(() => {
      root.classList.remove('site-is-loading');
      loader.setAttribute('aria-hidden', 'true');
    }, 220);
  };

  window.addEventListener('load', finish, { once: true });
  window.setTimeout(finish, 4500);
  render(progress);
};

const loadHeroMedia = (scope) => {
  if (!scope) return;

  scope.querySelectorAll('img[data-hero-lazy][data-src]').forEach((image) => {
    const src = image.getAttribute('data-src');
    if (!src) return;

    image.setAttribute('src', src);
    image.removeAttribute('data-src');
    image.removeAttribute('data-hero-lazy');
  });

  scope.querySelectorAll('video[data-hero-lazy-video]').forEach((video) => {
    let hasSource = false;

    video.querySelectorAll('source[data-src]').forEach((source) => {
      const src = source.getAttribute('data-src');
      if (!src) return;

      source.setAttribute('src', src);
      source.removeAttribute('data-src');
      hasSource = true;
    });

    if (hasSource) {
      video.load();
    }

    video.removeAttribute('data-hero-lazy-video');

    if (video.closest('.carousel-item.active')) {
      video.play().catch(() => {});
    }
  });
};

const initHeroLazyMedia = () => {
  const carousel = document.getElementById('heroCarousel');
  if (!carousel) return;

  loadHeroMedia(carousel.querySelector('.carousel-item.active'));

  carousel.addEventListener('slide.bs.carousel', (event) => {
    loadHeroMedia(event.relatedTarget);
  });

  const active = carousel.querySelector('.carousel-item.active');
  const next = active?.nextElementSibling || carousel.querySelector('.carousel-item');

  if (next && next !== active) {
    window.setTimeout(() => loadHeroMedia(next), 1200);
  }
};

document.addEventListener('DOMContentLoaded', () => {
  initSiteLoader();
  initHeroLazyMedia();
});
