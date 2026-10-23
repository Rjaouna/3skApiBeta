import './styles/app.css';
import './styles/app.scss';

const initNavigationProgress = () => {
  if (window.__navigationProgressInitialized) return;
  window.__navigationProgressInitialized = true;

  const root = document.documentElement;
  const finish = () => root.classList.remove('site-is-navigating');
  const startsNavigation = (event, link) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
    if (link.target && link.target !== '_self') return false;
    if (link.hasAttribute('download') || link.dataset.navigationProgress === 'off') return false;

    const href = link.getAttribute('href') || '';
    if (!href || href.startsWith('#') || /^(?:mailto:|tel:|javascript:)/i.test(href)) return false;

    let destination;
    try {
      destination = new URL(link.href, window.location.href);
    } catch {
      return false;
    }

    if (destination.origin !== window.location.origin) return false;
    return destination.href !== window.location.href;
  };

  document.addEventListener('click', (event) => {
    const target = event.target;
    const link = target instanceof Element ? target.closest('a[href]') : null;
    if (link && startsNavigation(event, link)) {
      root.classList.add('site-is-navigating');
    }
  });

  window.addEventListener('pageshow', finish);
  window.addEventListener('load', finish, { once: true });
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

const initCatalogLayout = () => {
  const catalog = document.querySelector('.catalog-page');
  if (!catalog) return;
  const header = document.querySelector('.site__header');
  const updateHeader = () => {
    document.documentElement.style.setProperty('--catalog-header-height', `${header?.offsetHeight || 0}px`);
  };
  updateHeader();
  if (header && 'ResizeObserver' in window) new ResizeObserver(updateHeader).observe(header);
  else window.addEventListener('resize', updateHeader, { passive: true });
};

document.addEventListener('DOMContentLoaded', () => {
  initNavigationProgress();
  initHeroLazyMedia();
  initHomeScrollEffects();
  initCatalogLayout();
});
