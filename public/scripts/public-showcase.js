(() => {
  const header = document.querySelector('.showcase-header');
  const updateHeaderHeight = () => {
    if (!header || header.querySelector('.showcase-nav.show, .showcase-nav.collapsing')) return;
    document.documentElement.style.setProperty('--showcase-header-height', `${header.offsetHeight}px`);
  };
  updateHeaderHeight();
  if (header && 'ResizeObserver' in window) {
    new ResizeObserver(updateHeaderHeight).observe(header);
  } else {
    window.addEventListener('resize', updateHeaderHeight);
  }

  const carousel = document.getElementById('showcaseCarousel');
  if (carousel && window.bootstrap) {
    new bootstrap.Carousel(carousel, { interval: false, ride: false, touch: true });
    carousel.addEventListener('slid.bs.carousel', () => {
      carousel.querySelectorAll('[data-showcase-video]').forEach(video => {
        if (video.closest('.carousel-item').classList.contains('active')) {
          video.play().catch(() => {});
        } else {
          video.pause();
        }
      });
    });
  }

  const navigation = document.querySelector('.showcase-nav');
  navigation?.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', event => {
      if (window.bootstrap && navigation.classList.contains('show')) {
        const destination = new URL(link.href);
        const section = document.getElementById(destination.hash.slice(1));
        if (section && destination.pathname === window.location.pathname) {
          event.preventDefault();
          navigation.addEventListener('hidden.bs.collapse', () => {
            updateHeaderHeight();
            if (window.location.hash !== destination.hash) {
              window.history.pushState(null, '', destination.hash);
            }
            section.scrollIntoView({
              behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
              block: 'start',
            });
          }, { once: true });
        }
        bootstrap.Collapse.getOrCreateInstance(navigation, { toggle: false }).hide();
      }
    });
  });

  if ('IntersectionObserver' in window) {
    const links = [...document.querySelectorAll('.showcase-nav__link')];
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        links.forEach(link => {
          const active = link.hash === `#${entry.target.id}`;
          link.classList.toggle('is-current', active);
          if (active) link.setAttribute('aria-current', 'location');
          else link.removeAttribute('aria-current');
        });
      });
    }, { rootMargin: '-20% 0px -55% 0px' });
    links.forEach(link => {
      const section = document.getElementById(link.hash.slice(1));
      if (section) observer.observe(section);
    });
  }
})();
