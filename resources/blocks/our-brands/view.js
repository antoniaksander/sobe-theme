(function () {
  var block = document.querySelector('[data-block="our-brands"]');
  if (!block) return;

  var header = document.querySelector('.site-header');

  // ── Smooth scroll on letter click, offset for fixed site-header ───────────
  block.querySelectorAll('.brands-alpha-nav__letter').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var target = document.querySelector(link.getAttribute('href'));
      if (!target) return;

      var offset = -(header?.offsetHeight ?? 0);

      if (window.lenis) {
        window.lenis.scrollTo(target, { offset: offset });
      } else {
        var top = target.getBoundingClientRect().top + window.scrollY + offset;
        window.scrollTo({ top: top, behavior: 'smooth' });
      }
    });
  });

  // ── IntersectionObserver — highlight active letter as user scrolls ────────
  var navLinks = block.querySelectorAll('.brands-alpha-nav__letter');

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        var letter = entry.target.dataset.section;
        navLinks.forEach(function (l) {
          l.classList.toggle('is-active', l.dataset.letter === letter);
        });
      }
    });
  }, {
    rootMargin: '-25% 0px -65% 0px',
    threshold: 0,
  });

  block.querySelectorAll('.brands-section').forEach(function (section) {
    observer.observe(section);
  });
})();
