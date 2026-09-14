(() => {
  'use strict';

  const shell = document.getElementById('stShell');
  const menu = document.getElementById('stMobileMenu');
  const menuToggle = document.getElementById('stMenuToggle');
  const menuClose = document.getElementById('stMobileClose');
  const backdrop = document.getElementById('stMobileBackdrop');
  if (!shell || !menu || !menuToggle || !backdrop) return;

  const setScrolled = () => shell.classList.toggle('is-scrolled', window.scrollY > 24);

  const closeDesktopDropdowns = (except = null) => {
    document.querySelectorAll('.st-has-dropdown.is-open').forEach(item => {
      if (item === except) return;
      item.classList.remove('is-open');
      const button = item.querySelector('.st-dropdown-trigger');
      if (button) button.setAttribute('aria-expanded', 'false');
    });
  };

  document.querySelectorAll('.st-dropdown-trigger').forEach(button => {
    button.addEventListener('click', event => {
      event.stopPropagation();
      const item = button.closest('.st-has-dropdown');
      const open = !item.classList.contains('is-open');
      closeDesktopDropdowns(item);
      item.classList.toggle('is-open', open);
      button.setAttribute('aria-expanded', String(open));
    });
  });

  const openMenu = () => {
    menu.classList.add('is-open');
    backdrop.classList.add('is-open');
    menuToggle.classList.add('is-active');
    menuToggle.setAttribute('aria-expanded', 'true');
    menu.setAttribute('aria-hidden', 'false');
    document.body.classList.add('st-menu-open');
  };

  const closeMenu = () => {
    menu.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    menuToggle.classList.remove('is-active');
    menuToggle.setAttribute('aria-expanded', 'false');
    menu.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('st-menu-open');
  };

  menuToggle.addEventListener('click', () => menu.classList.contains('is-open') ? closeMenu() : openMenu());
  menuClose?.addEventListener('click', closeMenu);
  backdrop.addEventListener('click', closeMenu);
  menu.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));

  document.querySelectorAll('.st-mobile-group > button').forEach(button => {
    button.addEventListener('click', () => {
      const group = button.parentElement;
      const panel = button.nextElementSibling;
      const open = group.classList.toggle('is-open');
      button.setAttribute('aria-expanded', String(open));
      panel.style.maxHeight = open ? `${panel.scrollHeight}px` : '0px';
    });
  });

  const normalizePath = path => path.replace(/\/+$/, '') || '/';
  const currentPath = normalizePath(window.location.pathname);
  document.querySelectorAll('.st-desktop-nav a, .st-mobile-menu__body a').forEach(link => {
    try {
      const linkPath = normalizePath(new URL(link.href, window.location.origin).pathname);
      if (linkPath === currentPath) link.classList.add('is-active');
    } catch (_) {}
  });

  document.addEventListener('click', event => {
    if (!event.target.closest('.st-has-dropdown')) closeDesktopDropdowns();
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeMenu();
      closeDesktopDropdowns();
    }
  });

  window.addEventListener('scroll', setScrolled, { passive: true });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 1160) closeMenu();
  });
  setScrolled();
})();
