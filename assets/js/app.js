(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc } = App.Format;
  const { icon } = App.Icons;

  const VALID_PAGES = ['home', 'rooms', 'booking', 'cabinet', 'admin'];

  function syncBodyLock() {
    const hasOpenModal = document.querySelectorAll('.modal.active').length > 0;
    document.body.classList.toggle('modal-open', hasOpenModal);
  }

  App.Modals = { syncBodyLock };

  function updateHeader() {
    const st = App.Store.get();
    const slot = document.getElementById('headerUser');
    if (!slot) return;

    if (!st.user) {
      slot.innerHTML = `
        <button class="btn btn-outline btn-sm" type="button" onclick="HotelApp.AuthModal.open('login')">Войти</button>
        <button class="btn btn-sm" type="button" onclick="HotelApp.Router.go('booking')" style="display:inline-flex;">Бронь</button>
      `;
    } else {
      const name = st.user.name || st.user.login;
      const initial = name.charAt(0).toUpperCase();
      const activeCount = (st.bookings || []).filter(b => b.status !== 'cancel').length;

      slot.innerHTML = `
        <button class="nav-user-pill" type="button" onclick="HotelApp.Router.go('cabinet')" title="Открыть личный кабинет">
          <div class="avatar">${esc(initial)}</div>
          <span class="nav-user-name">${esc(name)}</span>
          ${activeCount > 0 ? `<span class="nav-badge-count">${activeCount}</span>` : ''}
        </button>
        <button class="link-btn" type="button" onclick="HotelApp.Auth.logout()">Выйти</button>
      `;
    }

    const modeBadge = document.getElementById('footerModeBadge');
    if (modeBadge) {
      modeBadge.textContent = st.mode === 'php'
        ? 'PHP + MySQL · активное подключение'
        : 'Интерактивный прототип · localStorage';
    }
  }

  async function logout() {
    try { await App.Api.request('logout'); } catch (e) {}
    App.Toast.info('Вы вышли из аккаунта');
    refreshCurrent();
  }

  App.Auth = { logout };

  function go(page, pushHash) {
    const target = VALID_PAGES.includes(page) ? page : 'home';
    App.Store.patch({ activePage: target });

    document.querySelectorAll('.page').forEach(el => {
      el.classList.toggle('active', el.id === 'page-' + target);
    });

    document.querySelectorAll('#mainNav a, #mobileNav button').forEach(el => {
      el.classList.toggle('active', el.dataset.page === target);
    });

    const gearBtn = document.getElementById('gearBtn');
    if (gearBtn) gearBtn.classList.toggle('active', target === 'admin');

    if (pushHash !== false && root.location.hash !== '#' + target) {
      try {
        root.history.replaceState(null, '', '#' + target);
      } catch (e) {
        root.location.hash = target;
      }
    }

    refreshCurrent();
    root.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function refreshCurrent() {
    updateHeader();
    App.CompareModal.renderDock();
    const page = App.Store.get().activePage;

    if (page === 'home') {
      App.Views.Home.renderFeatured();
      App.Views.Home.renderPublicReviews();
    } else if (page === 'rooms') {
      App.Views.Catalog.render();
    } else if (page === 'booking') {
      App.Views.Booking.syncAuthUI();
      App.Views.Booking.updateSummary();
    } else if (page === 'cabinet') {
      App.Views.Cabinet.render();
    } else if (page === 'admin') {
      App.Views.Admin.render();
    }
  }

  App.Router = { go, refreshCurrent };

  async function loadCatalog() {
    const boot = root.__APP_BOOTSTRAP__;
    if (boot && Array.isArray(boot.rooms) && boot.rooms.length) {
      return boot.rooms;
    }
    try {
      const res = await fetch('data/rooms.json', { cache: 'no-cache' });
      if (res.ok) {
        const list = await res.json();
        if (Array.isArray(list) && list.length) return list;
      }
    } catch (e) {
      // Fallback for file:// or offline usage
    }
    return App.FALLBACK_ROOMS;
  }

  function hydrateIcons() {
    document.querySelectorAll('[data-icon]').forEach(el => {
      el.innerHTML = icon(el.dataset.icon);
    });
  }

  async function bootstrap() {
    hydrateIcons();

    const boot = root.__APP_BOOTSTRAP__ || null;
    const rooms = await loadCatalog();

    if (boot && boot.mode === 'php') {
      App.Store.patch({
        mode: 'php',
        dbReady: Boolean(boot.dbReady),
        rooms,
        extras: Array.isArray(boot.extras) && boot.extras.length ? boot.extras : App.BOOKING_EXTRAS
      });
      App.Store.applyServerState(boot.state);
    } else {
      App.Store.patch({
        mode: 'standalone',
        dbReady: false,
        rooms,
        extras: App.BOOKING_EXTRAS
      });
      App.Store.applyServerState(App.MockApi.buildClientState());
      if (boot && boot.dbError) {
        App.Toast.info('MySQL не запущен — включён автономный режим прототипа.');
      }
    }

    // Bind navigation
    document.querySelectorAll('#mainNav a, #mobileNav button').forEach(el => {
      el.addEventListener('click', () => go(el.dataset.page));
    });

    // Sticky topbar elevation
    root.addEventListener('scroll', () => {
      const bar = document.getElementById('topbar');
      if (bar) bar.classList.toggle('scrolled', root.scrollY > 10);
    }, { passive: true });

    // Close open modals on Escape
    root.addEventListener('keydown', e => {
      if (e.key !== 'Escape') return;
      if (document.getElementById('confirmModal').classList.contains('active')) {
        App.Toast.resolveConfirm(false);
      } else if (document.getElementById('roomModal').classList.contains('active')) {
        App.RoomModal.close();
      } else if (document.getElementById('voucherModal').classList.contains('active')) {
        App.VoucherModal.close();
      } else if (document.getElementById('compareModal').classList.contains('active')) {
        App.CompareModal.close();
      } else if (document.getElementById('authModal').classList.contains('active')) {
        App.AuthModal.close();
      }
    });

    // Subscribe dock & header to state changes
    App.Store.subscribe(() => {
      App.CompareModal.renderDock();
      updateHeader();
    });

    // Initialize views
    App.Views.Home.init();
    App.Views.Catalog.bind();
    App.Views.Booking.init();
    App.Views.Cabinet.initComposer();

    // Pre-fetch initial availability
    const s = App.Store.get().search;
    await App.Api.checkAvailability(s.checkin, s.checkout);

    // Route from URL hash
    const initialHash = (root.location.hash || '').replace('#', '').trim();
    const mappedHash = initialHash === 'halls' ? 'rooms' : initialHash;
    go(VALID_PAGES.includes(mappedHash) ? mappedHash : 'home', false);

    root.addEventListener('hashchange', () => {
      const h = (root.location.hash || '').replace('#', '').trim();
      const target = h === 'halls' ? 'rooms' : h;
      if (VALID_PAGES.includes(target) && target !== App.Store.get().activePage) {
        go(target, false);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', bootstrap);
})(window);
