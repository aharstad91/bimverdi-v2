(function () {
  // Fade-in: main+footer glir opp og fader inn
  gsap.fromTo('main, footer',
    { opacity: 0, y: 5 },
    { opacity: 1, y: 0, duration: 0.2, ease: 'power2.out' }
  );

  // Fade-out: fanger interne lenker, fader ut, navigerer
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (!link) return;

    var href = link.getAttribute('href');
    if (!href) return;

    // Hopp over: eksterne lenker, ankere, tel/mailto, nye faner, wp-admin
    if (
      link.target === '_blank' ||
      href.startsWith('#') ||
      href.startsWith('tel:') ||
      href.startsWith('mailto:') ||
      href.indexOf('wp-admin') !== -1 ||
      href.indexOf('wp-login') !== -1 ||
      link.hasAttribute('download') ||
      e.ctrlKey || e.metaKey || e.shiftKey
    ) return;

    // Kun same-origin
    try {
      var url = new URL(href, window.location.origin);
      if (url.origin !== window.location.origin) return;
    } catch (err) {
      return;
    }

    e.preventDefault();

    gsap.to('main, footer', {
      opacity: 0,
      y: -3,
      duration: 0.12,
      ease: 'power2.in',
      onComplete: function () {
        window.location.href = href;
      }
    });
  });
})();
