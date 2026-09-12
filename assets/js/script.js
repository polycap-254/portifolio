/* =============================================================
   Polycap Portfolio — Main JS
   -------------------------------------------------------------
   Modules:
     - Theme toggle (persisted in localStorage)
     - Mobile nav drawer
     - Smooth scroll for in-page anchors
     - Active nav highlighting
     - Sticky header shadow
     - Scroll reveal (IntersectionObserver)
     - Skill bar animation on scroll
     - Animated counters (data-count)
     - Back-to-top button
     - Project filtering
     - Project modal (open/close, populate from data-* attrs)
     - Toast notifications (window.toast)
     - Contact form client-side validation
     - Admin helpers: confirm-delete
   ============================================================= */

(function () {
  'use strict';

  /* ---------- THEME ---------- */
  const ThemeModule = {
    key: 'theme',
    init() {
      const btn = document.getElementById('themeToggle');
      if (!btn) return;
      this.syncIcon();
      btn.addEventListener('click', () => this.toggle());
      // React to OS changes if user hasn't chosen manually
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change', (e) => {
        if (!localStorage.getItem(this.key)) {
          this.apply(e.matches ? 'dark' : 'light');
        }
      });
    },
    current() { return document.documentElement.getAttribute('data-theme') || 'light'; },
    apply(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      try { localStorage.setItem(this.key, theme); } catch (e) {}
      this.syncIcon();
    },
    toggle() { this.apply(this.current() === 'dark' ? 'light' : 'dark'); },
    syncIcon() {
      const i = document.querySelector('[data-theme-icon]');
      if (!i) return;
      i.className = this.current() === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
  };

  /* ---------- MOBILE NAV ---------- */
  const MobileNav = {
    init() {
      const burger = document.getElementById('navBurger');
      const overlay = document.getElementById('mobileNav');
      if (!burger || !overlay) return;
      const open = () => {
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        burger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
      };
      const close = () => {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        burger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      };
      burger.addEventListener('click', () => {
        overlay.classList.contains('is-open') ? close() : open();
      });
      overlay.querySelectorAll('[data-close-mobile-nav]').forEach(el =>
        el.addEventListener('click', close)
      );
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
      window.addEventListener('resize', () => { if (window.innerWidth > 860) close(); });
    }
  };

  /* ---------- SMOOTH SCROLL ---------- */
  const SmoothScroll = {
    init() {
      document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href*="#"]');
        if (!a) return;
        const href = a.getAttribute('href');
        if (!href || href === '#') return;
        const url = new URL(a.href, location.origin);
        // Only same-page anchors
        if (url.pathname !== location.pathname) return;
        const id = url.hash.slice(1);
        if (!id) return;
        const target = document.getElementById(id);
        if (!target) return;
        e.preventDefault();
        const top = target.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top, behavior: 'smooth' });
        history.replaceState(null, '', '#' + id);
      });
    }
  };

  /* ---------- STICKY HEADER + BACK TO TOP ---------- */
  const ScrollUI = {
    init() {
      const header = document.getElementById('siteHeader');
      const backTop = document.getElementById('backToTop');
      const onScroll = () => {
        const y = window.scrollY;
        if (header) header.classList.toggle('is-scrolled', y > 8);
        if (backTop) backTop.classList.toggle('is-visible', y > 500);
      };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
      backTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }
  };

  /* ---------- ACTIVE NAV ---------- */
  const ActiveNav = {
    init() {
      const path = location.pathname.replace(/\/index\.php$/, '/');
      document.querySelectorAll('[data-nav-link]').forEach(link => {
        try {
          const u = new URL(link.href, location.origin);
          const p = u.pathname.replace(/\/index\.php$/, '/');
          if (p === path && !u.hash) link.classList.add('is-active');
        } catch (e) {}
      });
    }
  };

  /* ---------- SCROLL REVEAL ---------- */
  const Reveal = {
    init() {
      const els = document.querySelectorAll('[data-reveal]');
      if (!els.length) return;
      if (!('IntersectionObserver' in window)) {
        els.forEach(el => el.classList.add('is-visible'));
        return;
      }
      const io = new IntersectionObserver((entries) => {
        entries.forEach(en => {
          if (en.isIntersecting) {
            en.target.classList.add('is-visible');
            io.unobserve(en.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
      els.forEach(el => io.observe(el));
    }
  };

  /* ---------- SKILL BARS ---------- */
  const SkillBars = {
    init() {
      const skills = document.querySelectorAll('.skill[data-level]');
      if (!skills.length) return;
      const io = new IntersectionObserver((entries) => {
        entries.forEach(en => {
          if (en.isIntersecting) {
            const el = en.target;
            const lvl = Math.max(0, Math.min(100, parseInt(el.dataset.level, 10) || 0));
            el.style.setProperty('--level', lvl + '%');
            el.classList.add('is-visible');
            io.unobserve(el);
          }
        });
      }, { threshold: 0.25 });
      skills.forEach(el => io.observe(el));
    }
  };

  /* ---------- COUNTERS ---------- */
  const Counters = {
    init() {
      const els = document.querySelectorAll('[data-count]');
      if (!els.length) return;
      const animate = (el) => {
        const target = parseInt(el.dataset.count, 10) || 0;
        const dur = 1400;
        const start = performance.now();
        const step = (t) => {
          const p = Math.min(1, (t - start) / dur);
          const eased = 1 - Math.pow(1 - p, 3);
          el.textContent = Math.round(target * eased).toString();
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      };
      const io = new IntersectionObserver((entries) => {
        entries.forEach(en => {
          if (en.isIntersecting) { animate(en.target); io.unobserve(en.target); }
        });
      }, { threshold: 0.4 });
      els.forEach(el => io.observe(el));
    }
  };

  /* ---------- PROJECT FILTER ---------- */
  const ProjectFilter = {
    init() {
      const wrap = document.querySelector('[data-project-filter]');
      if (!wrap) return;
      const cards = document.querySelectorAll('[data-project-category]');
      const empty = document.querySelector('[data-project-empty]');
      wrap.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-filter]');
        if (!btn) return;
        wrap.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        const cat = btn.dataset.filter;
        let visible = 0;
        cards.forEach(card => {
          const match = cat === 'all' || card.dataset.projectCategory === cat;
          card.style.display = match ? '' : 'none';
          if (match) visible++;
        });
        if (empty) empty.style.display = visible ? 'none' : '';
      });
    }
  };

  /* ---------- PROJECT MODAL ---------- */
  const ProjectModal = {
    el: null, panel: null,
    init() {
      this.el = document.getElementById('projectModal');
      if (!this.el) return;
      this.panel = this.el.querySelector('.modal__panel');
      this.el.addEventListener('click', (e) => {
        if (e.target.matches('[data-modal-close], .modal__backdrop')) this.close();
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.el.classList.contains('is-open')) this.close();
      });
      document.querySelectorAll('[data-open-project]').forEach(btn => {
        btn.addEventListener('click', () => this.open(btn.dataset));
      });
    },
    open(data) {
      const set = (sel, val) => { const n = this.el.querySelector(sel); if (n) n.textContent = val || ''; };
      const setAttr = (sel, attr, val) => { const n = this.el.querySelector(sel); if (n && val) n.setAttribute(attr, val); };

      const img = this.el.querySelector('[data-modal-image]');
      if (img) {
        if (data.image) { img.src = data.image; img.alt = data.name || 'Project'; img.style.display = ''; }
        else { img.style.display = 'none'; }
      }
      set('[data-modal-category]', data.category);
      set('[data-modal-name]', data.name);
      set('[data-modal-year]', data.year ? '• ' + data.year : '');
      set('[data-modal-description]', data.description);

      const techWrap = this.el.querySelector('[data-modal-tech]');
      if (techWrap) {
        techWrap.innerHTML = '';
        (data.tech || '').split(',').map(s => s.trim()).filter(Boolean).forEach(t => {
          const li = document.createElement('li'); li.textContent = t; techWrap.appendChild(li);
        });
      }

      const show = (sel, href) => {
        const n = this.el.querySelector(sel);
        if (!n) return;
        if (href) { n.href = href; n.style.display = ''; }
        else { n.style.display = 'none'; }
      };
      show('[data-modal-github]', data.github);
      show('[data-modal-live]',   data.live);
      show('[data-modal-docs]',   data.docs);

      this.el.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    },
    close() {
      this.el.classList.remove('is-open');
      document.body.style.overflow = '';
    }
  };

  /* ---------- TOASTS ---------- */
  const Toast = {
    stack: null,
    init() {
      this.stack = document.getElementById('toastStack');
      if (!this.stack) return;
      // Render PHP flashes queued on window
      if (Array.isArray(window.__FLASH__)) {
        window.__FLASH__.forEach(f => this.show(f.type, f.message));
        window.__FLASH__ = [];
      }
    },
    show(type, message, timeout = 5000) {
      if (!this.stack) return;
      const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
      const el = document.createElement('div');
      el.className = 'toast toast--' + (type || 'info');
      el.setAttribute('role', 'status');
      el.innerHTML = `
        <i class="toast__icon fas ${icons[type] || icons.info}"></i>
        <div class="toast__body"></div>
        <button class="toast__close" aria-label="Dismiss"><i class="fas fa-times"></i></button>`;
      el.querySelector('.toast__body').textContent = message;
      const close = () => { el.classList.add('is-leaving'); setTimeout(() => el.remove(), 300); };
      el.querySelector('.toast__close').addEventListener('click', close);
      this.stack.appendChild(el);
      if (timeout) setTimeout(close, timeout);
    }
  };
  window.toast = Toast;

  /* ---------- CONTACT FORM VALIDATION ---------- */
  const ContactForm = {
    init() {
      const form = document.querySelector('[data-contact-form]');
      if (!form) return;
      form.addEventListener('submit', (e) => {
        let ok = true;
        form.querySelectorAll('[data-required]').forEach(field => {
          const group = field.closest('.form-group');
          const err = group?.querySelector('.field-error');
          const val = (field.value || '').trim();
          let bad = !val;
          if (!bad && field.type === 'email') {
            bad = !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
            if (err) err.textContent = 'Please enter a valid email address.';
          } else if (err) {
            err.textContent = 'This field is required.';
          }
          if (bad) { ok = false; group?.classList.add('has-error'); }
          else { group?.classList.remove('has-error'); }
        });
        if (!ok) e.preventDefault();
      });
      // Live clear errors
      form.querySelectorAll('.form-control').forEach(f => {
        f.addEventListener('input', () => f.closest('.form-group')?.classList.remove('has-error'));
      });
    }
  };

  /* ---------- ADMIN: confirm delete ---------- */
  const ConfirmDelete = {
    init() {
      document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
          if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
        });
      });
    }
  };

  /* ---------- BOOT ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    ThemeModule.init();
    MobileNav.init();
    SmoothScroll.init();
    ScrollUI.init();
    ActiveNav.init();
    Reveal.init();
    SkillBars.init();
    Counters.init();
    ProjectFilter.init();
    ProjectModal.init();
    Toast.init();
    ContactForm.init();
    ConfirmDelete.init();
  });
})();

/* ---------- ABOUT MODAL (separate small module) ---------- */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('aboutModal');
    if (!modal) return;
    const titleEl = modal.querySelector('#aboutModalTitle');
    const bodyEl  = modal.querySelector('#aboutModalBody');

    // Content — pulled from data attributes on the page (rendered by PHP)
    // We read from a hidden JSON blob to avoid inline strings in HTML.
    const data = window.__ABOUT__ || {};

    const close = () => {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    };
    const open = (key) => {
      const item = data[key];
      if (!item) return;
      titleEl.textContent = item.title || '';
      bodyEl.innerHTML = item.html || '';
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    };

    document.querySelectorAll('[data-about-open]').forEach(card => {
      card.addEventListener('click', () => open(card.dataset.aboutOpen));
      card.style.cursor = 'pointer';
    });
    modal.addEventListener('click', (e) => {
      if (e.target.matches('[data-modal-close], .modal__backdrop')) close();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
    });
  });
})();

/* ---------- SKILLS TAB SWITCHER ---------- */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', () => {
    const tabs  = document.querySelectorAll('[data-skill-tab]');
    const cards = document.querySelectorAll('.skill[data-skill-cat]');
    if (!tabs.length || !cards.length) return;

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('is-active'));
        tab.classList.add('is-active');
        const cat = tab.dataset.skillTab;
        cards.forEach(card => {
          const show = card.dataset.skillCat === cat;
          card.style.display = show ? '' : 'none';
          if (show) {
            // Reset the bar animation so it re-fills
            card.classList.remove('is-visible');
            const lvl = Math.max(0, Math.min(100, parseInt(card.dataset.level, 10) || 0));
            card.style.setProperty('--level', lvl + '%');
            requestAnimationFrame(() => card.classList.add('is-visible'));
          }
        });
      });
    });
  });
})();

/* ---------- CONTACT FORM: submitting state ---------- */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-contact-form]');
    if (!form) return;
    form.addEventListener('submit', (e) => {
      // If client-side validation failed, don't set submitting state
      if (form.querySelector('.form-group.has-error')) return;
      form.classList.add('is-submitting');
      const btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending…';
      }
    });
  });
})();

/* ---------- GLOBAL: Esc closes any open modal ---------- */
(function () {
  'use strict';
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.modal.is-open').forEach(m => {
      m.classList.remove('is-open');
      m.setAttribute('aria-hidden', 'true');
    });
    document.body.style.overflow = '';
  });
})();

/* ---------- TOAST PROGRESS ENHANCEMENT ---------- */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', () => {
    if (!window.toast || typeof window.toast.show !== 'function') return;
    const original = window.toast.show.bind(window.toast);
    window.toast.show = function (type, message, timeout = 5000) {
      original(type, message, timeout);
      const stack = document.getElementById('toastStack');
      if (!stack) return;
      const last = stack.lastElementChild;
      if (last) last.style.setProperty('--toast-duration', timeout + 'ms');
    };
  });
})();

/* ---------- GLOBAL: Esc closes any open modal ---------- */
(function () {
  'use strict';
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.modal.is-open').forEach(m => {
      m.classList.remove('is-open');
      m.setAttribute('aria-hidden', 'true');
    });
    document.body.style.overflow = '';
  });
})();

/* ---------- PAGE READY MARKER (drives skeleton fade) ---------- */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', () => {
    // Small delay to let IntersectionObserver register reveals
    requestAnimationFrame(() => document.body.classList.add('is-ready'));
  });
})();

/* ---------- ADMIN KEYBOARD SHORTCUTS ---------- */
(function () {
  'use strict';
  if (!document.body.classList.contains('admin-body')) return;

  document.addEventListener('keydown', (e) => {
    const meta = e.ctrlKey || e.metaKey;

    // Ctrl+S / Cmd+S — save nearest form
    if (meta && (e.key === 's' || e.key === 'S')) {
      e.preventDefault();
      const forms = [...document.querySelectorAll('form')].filter(f =>
        f.offsetParent !== null &&
        !f.querySelector('[data-skip-shortcut]') &&
        f.method.toLowerCase() === 'post'
      );
      if (forms.length) {
        forms[0].requestSubmit ? forms[0].requestSubmit() : forms[0].submit();
      } else {
        window.toast && window.toast.show('info', 'No form to save on this page.');
      }
      return;
    }

    // Ctrl+/ — jump to Dashboard
    if (meta && e.key === '/') {
      e.preventDefault();
      const base = document.querySelector('link[rel="icon"]')?.href?.split('/assets/')[0] || '';
      location.href = base + '/admin/dashboard.php';
      return;
    }
  });

  // Small hint in the topbar
  document.addEventListener('DOMContentLoaded', () => {
    const bar = document.querySelector('.admin-topbar__actions');
    if (!bar || bar.querySelector('.admin-shortcut-hint')) return;
    const hint = document.createElement('span');
    hint.className = 'admin-shortcut-hint hide-sm';
    hint.innerHTML = '<i class="fas fa-keyboard"></i> Ctrl+S to save · Ctrl+/ Dashboard';
    hint.style.cssText = 'font-size:.75rem;color:var(--muted);margin-left:.5rem;white-space:nowrap;';
    bar.appendChild(hint);
  });
})();
