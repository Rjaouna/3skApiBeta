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

const initHomeScrollEffects = () => {
  if (window.__homeScrollEffectsInitialized) return;

  const home = document.querySelector('.public-home');
  if (!home) return;

  window.__homeScrollEffectsInitialized = true;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const progress = document.createElement('div');
  const hero = home.querySelector('.public-slider-shell');
  const introSection = home.querySelector('.public-intro-v2');

  progress.className = 'home-scroll-progress';
  progress.setAttribute('aria-hidden', 'true');
  document.body.appendChild(progress);

  const revealTargets = [];
  const revealDefinitions = [
    { selector: '.site-stats-strip__item', direction: 'home-reveal-up', stagger: 45 },
    { selector: '.public-intro-v2 .intro-copy', direction: 'home-reveal-left' },
    { selector: '.public-intro-v2 .intro-facts', direction: 'home-reveal-right', delay: 80 },
    { selector: '.public-about-v2 .about-visual', direction: 'home-reveal-left' },
    { selector: '.public-about-v2 .about-copy', direction: 'home-reveal-right', delay: 80 },
    { selector: '.public-services-v2 .row > .col-12:first-child', direction: 'home-reveal-left' },
    { selector: '.public-services-v2 .stock-frame', direction: 'home-reveal-right', delay: 80 },
    { selector: '.public-brands-v2 .brands-heading', direction: 'home-reveal-up' },
    { selector: '.public-brands-v2 .brand-item', direction: 'home-reveal-zoom', stagger: 24 },
    { selector: '[data-new-section] .latest-arrivals-eyebrow, [data-new-section] .latest-arrivals-title, [data-new-section] .latest-arrivals-subtitle', direction: 'home-reveal-left', stagger: 40 },
    { selector: '[data-new-section] .product-card', direction: 'home-reveal-up', stagger: 36 },
    { selector: '.public-contact-v2 .contact-pane-dark .contact-pane-inner', direction: 'home-reveal-left' },
    { selector: '.public-contact-v2 .contact-pane-light .contact-pane-inner', direction: 'home-reveal-right', delay: 80 }
  ];

  revealDefinitions.forEach((definition) => {
    home.querySelectorAll(definition.selector).forEach((element, localIndex) => {
      if (element.dataset.homeScrollReveal === '1') return;

      element.dataset.homeScrollReveal = '1';
      element.classList.add('home-scroll-reveal', definition.direction);
      element.style.setProperty(
        '--home-reveal-delay',
        `${Math.min((definition.delay || 0) + (localIndex * (definition.stagger || 55)), 280)}ms`
      );
      revealTargets.push(element);
    });
  });

  if ('IntersectionObserver' in window && !reducedMotion.matches) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, {
      rootMargin: '0px 0px -12% 0px',
      threshold: 0.16
    });

    revealTargets.forEach((element) => observer.observe(element));
  } else {
    revealTargets.forEach((element) => element.classList.add('is-visible'));
  }

  if (hero && !reducedMotion.matches) {
    hero.classList.add('is-home-parallax-ready');
  }

  let animationFrame = null;

  const updateEffects = () => {
    const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
    const maxScroll = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
    const scrollRatio = Math.min(1, Math.max(0, scrollTop / maxScroll));

    progress.style.setProperty('--home-scroll-progress', String(scrollRatio));

    if (!reducedMotion.matches && hero) {
      const bounds = hero.getBoundingClientRect();
      const heroTravel = Math.max(1, bounds.height + window.innerHeight);
      const visibilityProgress = Math.min(1, Math.max(0, (window.innerHeight - bounds.top) / heroTravel));
      const imageShift = Math.max(-32, Math.min(32, -bounds.top * .075));
      const textShift = Math.max(0, Math.min(26, -bounds.top * .055));
      const textFade = Math.max(.72, 1 - (visibilityProgress * .26));

      hero.style.setProperty('--home-hero-y', `${imageShift}px`);
      hero.style.setProperty('--home-hero-text-y', `${textShift}px`);
      hero.style.setProperty('--home-hero-fade', textFade.toFixed(3));
    }

    if (!reducedMotion.matches && introSection && window.innerWidth >= 768) {
      const bounds = introSection.getBoundingClientRect();
      const sectionCenter = bounds.top + (bounds.height / 2);
      const viewportCenter = window.innerHeight / 2;
      const offset = Math.max(-28, Math.min(28, (viewportCenter - sectionCenter) * .08));

      introSection.style.setProperty('--intro-parallax-y', `${offset}px`);
    }

    animationFrame = null;
  };

  const requestUpdate = () => {
    if (animationFrame !== null) return;
    animationFrame = window.requestAnimationFrame(updateEffects);
  };

  window.addEventListener('scroll', requestUpdate, { passive: true });
  window.addEventListener('resize', requestUpdate, { passive: true });
  reducedMotion.addEventListener?.('change', requestUpdate);
  updateEffects();
};

document.addEventListener('DOMContentLoaded', () => {
  initSiteLoader();
  initHeroLazyMedia();
  initHomeScrollEffects();
});
