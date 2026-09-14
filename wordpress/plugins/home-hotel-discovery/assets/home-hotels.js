(() => {
  const modules = document.querySelectorAll('[data-sthhd-module]');
  modules.forEach((module) => {
    const rail = module.querySelector('[data-sthhd-rail]');
    const prev = module.querySelector('[data-sthhd-prev]');
    const next = module.querySelector('[data-sthhd-next]');
    const cards = [...module.querySelectorAll('[data-sthhd-card]')];
    const filters = [...module.querySelectorAll('[data-sthhd-filter]')];
    if (!rail) return;

    const visibleCards = () => cards.filter((card) => !card.hidden);
    const step = () => {
      const visible = visibleCards()[0];
      return visible ? visible.getBoundingClientRect().width + 18 : Math.max(rail.clientWidth * .8, 280);
    };

    const updateControls = () => {
      if (!prev || !next) return;
      const max = Math.max(0, rail.scrollWidth - rail.clientWidth - 2);
      prev.disabled = rail.scrollLeft <= 2;
      next.disabled = rail.scrollLeft >= max || visibleCards().length <= 1;
      prev.setAttribute('aria-disabled', prev.disabled ? 'true' : 'false');
      next.setAttribute('aria-disabled', next.disabled ? 'true' : 'false');
    };

    prev?.addEventListener('click', () => rail.scrollBy({ left: -step(), behavior: 'smooth' }));
    next?.addEventListener('click', () => rail.scrollBy({ left: step(), behavior: 'smooth' }));
    rail.addEventListener('scroll', () => window.requestAnimationFrame(updateControls), { passive: true });
    window.addEventListener('resize', updateControls, { passive: true });
    rail.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        rail.scrollBy({ left: -step(), behavior: 'smooth' });
      }
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        rail.scrollBy({ left: step(), behavior: 'smooth' });
      }
    });

    filters.forEach((button) => {
      button.addEventListener('click', () => {
        const target = button.dataset.sthhdFilter || 'all';
        filters.forEach((item) => {
          const active = item === button;
          item.classList.toggle('is-active', active);
          item.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        cards.forEach((card) => {
          card.hidden = target !== 'all' && card.dataset.city !== target;
        });
        rail.scrollTo({ left: 0, behavior: 'smooth' });
        window.requestAnimationFrame(updateControls);
      });
    });

    updateControls();
  });
})();
