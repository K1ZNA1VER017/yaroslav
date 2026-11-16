(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc, money, plural, fmtDate, statusBadge, bookingCode, renderStars, todayISO } = App.Format;
  const { icon } = App.Icons;

  App.Views = App.Views || {};

  let lastData = { bookings: [], stats: {}, reviews: [] };

  function render() {
    const isAdmin = App.Store.get().admin;
    const loginCard = document.getElementById('adminLoginCard');
    const panel = document.getElementById('adminPanel');

    if (!isAdmin) {
      loginCard.style.display = 'block';
      panel.style.display = 'none';
      document.getElementById('adminMsg').textContent = '';
    } else {
      loginCard.style.display = 'none';
      panel.style.display = 'block';
      loadDashboard();
    }
  }

  function fillDemoCredentials() {
    document.getElementById('adminUser').value = 'Admin26';
    document.getElementById('adminPass').value = 'Demo26';
  }

  async function login(e) {
    e.preventDefault();
    const msg = document.getElementById('adminMsg');
    msg.textContent = '';

    try {
      await App.Api.request('admin.login', {
        login: document.getElementById('adminUser').value.trim(),
        pass: document.getElementById('adminPass').value
      });
      App.Store.patch({ admin: true });
      App.Toast.success('Вход в панель администратора выполнен');
      render();
    } catch (err) {
      msg.textContent = err.message;
      msg.className = 'msg error';
    }
    return false;
  }

  async function logout() {
    const ok = await App.Toast.confirm({
      title: 'Выйти из админ-панели?',
      message: 'Текущая сессия администратора будет завершена.',
      confirmText: 'Выйти'
    });
    if (!ok) return;

    try { await App.Api.request('admin.logout'); } catch (e) {}
    App.Store.patch({ admin: false });
    App.Router.go('home');
  }

  async function loadDashboard() {
    try {
      const data = await App.Api.request('admin.data');
      lastData = {
        bookings: data.bookings || [],
        stats: data.stats || {},
        reviews: data.reviews || []
      };
      renderKPIs();
      renderActiveSubtab();
    } catch (err) {
      document.getElementById('adminBookingsList').innerHTML = `
        <div class="empty">
          <div class="ico">${icon('alert')}</div>
          <b>${esc(err.message)}</b>
        </div>
      `;
    }
  }

  function renderKPIs() {
    const s = lastData.stats || {};
    const totalRooms = App.Store.get().rooms.length || 6;
    const confirmedAndPaid = (s.confirm || 0) + (s.paid || 0);

    document.getElementById('adminStats').innerHTML = `
      <div class="admin-kpi admin-kpi--revenue">
        <div class="kpi-val">${money(s.revenue || 0)}</div>
        <div class="kpi-lbl">Подтверждённая выручка</div>
      </div>
      <div class="admin-kpi">
        <div class="kpi-val">${s.total || 0}</div>
        <div class="kpi-lbl">Всего заявок</div>
      </div>
      <div class="admin-kpi${(s.new || 0) > 0 ? ' admin-kpi--alert' : ''}">
        <div class="kpi-val">${s.new || 0}</div>
        <div class="kpi-lbl">Новых (ждут ответа)</div>
      </div>
      <div class="admin-kpi">
        <div class="kpi-val">${confirmedAndPaid}</div>
        <div class="kpi-lbl">Подтверждено / Оплачено</div>
      </div>
      <div class="admin-kpi">
        <div class="kpi-val">${s.occupied || 0} / ${totalRooms}</div>
        <div class="kpi-lbl">Занято сегодня</div>
      </div>
    `;
  }

  function switchSubtab(tab) {
    App.Store.patch({ adminActiveTab: tab });
    document.querySelectorAll('#adminSubtabs button').forEach(b => {
      b.classList.toggle('active', b.dataset.tab === tab);
    });
    document.querySelectorAll('#adminPanel .tab-panel').forEach(p => p.classList.remove('active'));
    const target = document.getElementById('admin-tab-' + tab);
    if (target) target.classList.add('active');
    renderActiveSubtab();
  }

  function renderActiveSubtab() {
    const tab = App.Store.get().adminActiveTab || 'bookings';
    if (tab === 'bookings') renderBookingsList();
    else if (tab === 'rooms') renderInventory();
    else if (tab === 'reviews') renderAdminReviews();
  }

  function setStatusFilter(status) {
    App.Store.patch({ adminFilterStatus: status });
    renderBookingsList();
  }

  function setSearchQuery(q) {
    App.Store.patch({ adminSearchQuery: q });
    renderBookingsList();
  }

  function renderBookingsList() {
    const st = App.Store.get();
    const s = lastData.stats || {};
    const filterBar = document.getElementById('adminStatusFilters');

    const filters = [
      { key: '',        label: 'Все',          count: s.total || 0 },
      { key: 'new',     label: 'Новые',        count: s.new || 0 },
      { key: 'confirm', label: 'Подтверждены', count: s.confirm || 0 },
      { key: 'paid',    label: 'Оплачены',     count: s.paid || 0 },
      { key: 'done',    label: 'Завершены',    count: s.done || 0 },
      { key: 'cancel',  label: 'Отменены',     count: s.cancel || 0 }
    ];

    if (filterBar) {
      filterBar.innerHTML = filters.map(f => `
        <button
          type="button"
          class="filter-chip${st.adminFilterStatus === f.key ? ' on' : ''}"
          onclick="HotelApp.Views.Admin.setStatusFilter('${f.key}')"
        >
          ${esc(f.label)} <span class="cnt">${f.count}</span>
        </button>
      `).join('');
    }

    const q = (st.adminSearchQuery || '').trim().toLowerCase();
    const list = (lastData.bookings || []).filter(b => {
      if (st.adminFilterStatus && b.status !== st.adminFilterStatus) return false;
      if (q) {
        const hay = `${bookingCode(b)} ${b.roomName} ${b.name} ${b.phone} ${b.email || ''} ${b.user}`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    });

    const box = document.getElementById('adminBookingsList');
    if (!list.length) {
      box.innerHTML = `
        <div class="empty">
          <div class="ico">${icon('list')}</div>
          <b>Заявок по выбранному фильтру нет</b>
          <p>Измените статус фильтра или очистите строку поиска.</p>
        </div>
      `;
      return;
    }

    const statusChoices = [
      { key: 'new',     label: 'Новая' },
      { key: 'confirm', label: 'Подтвердить' },
      { key: 'paid',    label: 'Оплачена' },
      { key: 'done',    label: 'Завершена' },
      { key: 'cancel',  label: 'Отменить' }
    ];

    box.innerHTML = list.map(b => {
      const extras = Array.isArray(b.extras) ? b.extras : [];
      return `
        <article class="admin-row">
          <div class="admin-row-top">
            <div>
              <div class="booking-item-top">
                <h3>${esc(b.roomName)} · ${money(b.total)}</h3>
                <span class="booking-code">#${esc(bookingCode(b))}</span>
              </div>
              <div class="booking-meta">
                <span>${icon('user')} <b>${esc(b.name || '—')}</b></span>
                <span>${icon('phone')} <b>${esc(b.phone)}</b></span>
                <span>${icon('cal')} <b>${fmtDate(b.checkin)} — ${fmtDate(b.checkout)}</b> (${b.nights} ${plural(b.nights, 'ночь', 'ночи', 'ночей')})</span>
                <span>${icon('users')} <b>${b.guests}</b></span>
              </div>
              ${b.email ? `<div class="booking-comment">${icon('mail')} <span>${esc(b.email)}</span></div>` : ''}
              ${extras.length ? `
                <div class="booking-extras-tags">
                  ${extras.map(ex => `<span class="amenity-pill">${esc(ex.title)}</span>`).join('')}
                </div>
              ` : ''}
              ${b.comment ? `<div class="booking-comment">${icon('chat')} <span>${esc(b.comment)}</span></div>` : ''}
            </div>

            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
              ${statusBadge(b.status)}
              <span class="admin-user-chip">${icon('user')} @${esc(b.user)}</span>
            </div>
          </div>

          <div class="admin-actions">
            <span style="font-size:12px; color:var(--muted); font-weight:700; margin-right:4px;">Статус:</span>
            ${statusChoices.map(ch => `
              <button
                type="button"
                class="status-btn${b.status === ch.key ? ' current' : ''}"
                onclick="HotelApp.Views.Admin.setStatus(${b.id}, '${ch.key}')"
              >${esc(ch.label)}</button>
            `).join('')}
          </div>
        </article>
      `;
    }).join('');
  }

  function renderInventory() {
    const grid = document.getElementById('adminInventoryGrid');
    if (!grid) return;
    const rooms = App.Store.get().rooms;
    const bookings = lastData.bookings || [];
    const today = todayISO(0);

    grid.innerHTML = rooms.map(r => {
      const roomBookings = bookings.filter(b => b.roomId === r.id && b.status !== 'cancel');
      const occupiedToday = roomBookings.some(b => b.checkin <= today && b.checkout > today);
      return `
        <article class="inventory-card">
          <div class="inventory-card-img">
            <img src="${esc(r.image)}" alt="${esc(r.name)}">
            <div class="room-media-top">
              <span class="room-tag">№ ${esc(r.number)} · ${esc(r.type)}</span>
              <span class="avail-pill ${occupiedToday ? 'avail-pill--busy' : 'avail-pill--free'}">
                ${occupiedToday ? 'Занят сегодня' : 'Свободен сегодня'}
              </span>
            </div>
          </div>
          <div class="inventory-card-body">
            <h3>${esc(r.name)}</h3>
            <div style="font-size:13px; color:var(--muted); margin-top:3px;">${r.size} м² · до ${r.capacity} гостей · ${money(r.price)}/ночь</div>
            <div class="inventory-stats">
              <span>Активных броней: <b>${roomBookings.length}</b></span>
              <span>Рейтинг: <b>★ ${Number(r.rating).toFixed(1)}</b></span>
            </div>
          </div>
        </article>
      `;
    }).join('');
  }

  function renderAdminReviews() {
    const box = document.getElementById('adminReviewsList');
    if (!box) return;
    const list = lastData.reviews || [];

    if (!list.length) {
      box.innerHTML = `<div class="empty"><div class="ico">${icon('star')}</div><b>Отзывов пока нет</b></div>`;
      return;
    }

    box.innerHTML = list.map(r => `
      <article class="review-card">
        <div class="review-card-top">
          <div>
            <b>${esc(r.roomName)}</b>
            <span style="font-size:12.5px; color:var(--muted); margin-left:8px;">${esc(r.userName || r.user)} · ${esc(r.date)}</span>
          </div>
          <span class="review-stars">${renderStars(r.rating)}</span>
        </div>
        <p class="review-quote">${esc(r.text)}</p>
      </article>
    `).join('');
  }

  async function setStatus(id, status) {
    try {
      await App.Api.request('admin.setStatus', { id, status });
      App.Toast.success('Статус заявки обновлён');
      await loadDashboard();
    } catch (err) {
      App.Toast.error(err.message);
    }
  }

  App.Views.Admin = {
    render,
    login,
    logout,
    fillDemoCredentials,
    switchSubtab,
    setStatusFilter,
    setSearchQuery,
    setStatus
  };
})(window);
