(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc, money, plural, nightsBetween, todayISO } = App.Format;
  const { icon } = App.Icons;

  App.Views = App.Views || {};

  function roomCardHTML(room, options) {
    const opts = options || {};
    const st = App.Store.get();
    const isCompared = st.compareIds.includes(room.id);
    const isBusy = Boolean(opts.showAvailability && st.unavailableIds.includes(room.id));
    const nights = opts.nights || 0;
    const totalForStay = nights > 1 ? room.price * nights : 0;

    return `
      <article class="room-card">
        <div class="room-media" onclick="HotelApp.RoomModal.open('${esc(room.id)}')">
          <div class="room-media-top">
            <span class="room-tag">${esc(room.type)}</span>
            <span class="room-rating"><span class="st">★</span>${Number(room.rating).toFixed(1)}</span>
          </div>
          <img src="${esc(room.image)}" alt="${esc(room.name)}" loading="lazy">
          <div class="room-media-bottom">
            ${opts.showAvailability ? (
              isBusy
                ? `<span class="avail-pill avail-pill--busy">Занят на эти даты</span>`
                : `<span class="avail-pill avail-pill--free">Свободен</span>`
            ) : (
              room.badge ? `<span class="chip chip-glass" style="padding:4px 10px; font-size:11.5px;">${esc(room.badge)}</span>` : '<span></span>'
            )}
            <button
              class="room-compare-btn${isCompared ? ' active' : ''}"
              type="button"
              onclick="event.stopPropagation(); HotelApp.Views.Catalog.onToggleCompare('${esc(room.id)}')"
              title="Добавить в сравнение"
            >
              ${icon('compare')}
              <span>${isCompared ? 'В сравнении' : 'Сравнить'}</span>
            </button>
          </div>
        </div>

        <div class="room-body">
          <div class="room-title-row">
            <h3 onclick="HotelApp.RoomModal.open('${esc(room.id)}')">${esc(room.name)}</h3>
            <span class="room-num">№ ${esc(room.number)}</span>
          </div>
          <div class="room-meta-line">
            <span>${esc(room.floor || 'Основной корпус')}</span>
            <span>·</span>
            <span>${esc(room.view || 'Вид на город')}</span>
          </div>
          <p class="room-desc">${esc(room.desc)}</p>

          <div class="room-specs">
            <span class="spec">${icon('users')} до ${room.capacity}</span>
            <span class="spec">${icon('bed')} ${esc(room.beds)}</span>
            <span class="spec">${icon('size')} ${room.size} м²</span>
          </div>

          <div class="room-foot">
            <div class="price">
              <b>${money(room.price)}</b>
              <span>${totalForStay ? `${money(totalForStay)} за ${nights} ${plural(nights, 'ночь', 'ночи', 'ночей')}` : 'за ночь · завтрак включён'}</span>
            </div>
            <div class="room-foot-actions">
              <button class="btn btn-outline btn-xs" type="button" onclick="HotelApp.RoomModal.open('${esc(room.id)}')" title="Подробнее о номере">
                Подробнее
              </button>
              <button class="btn btn-xs" type="button" onclick="HotelApp.Views.Catalog.quickBook('${esc(room.id)}')" ${isBusy ? 'disabled' : ''}>
                Выбрать ${icon('arrow')}
              </button>
            </div>
          </div>
        </div>
      </article>
    `;
  }

  function renderFilterChips() {
    const bar = document.getElementById('catalogFilterChips');
    if (!bar) return;

    const st = App.Store.get();
    const types = [...new Set(st.rooms.map(r => r.type))];
    const active = st.search.type || '';

    const chips = [
      { value: '', label: 'Все номера', count: st.rooms.length },
      ...types.map(t => ({
        value: t,
        label: t,
        count: st.rooms.filter(r => r.type === t).length
      }))
    ];

    bar.innerHTML = chips.map(c => `
      <button
        type="button"
        class="filter-chip${active === c.value ? ' on' : ''}"
        onclick="HotelApp.Views.Catalog.setType('${esc(c.value)}')"
      >
        ${esc(c.label)} <span class="cnt">${c.count}</span>
      </button>
    `).join('');
  }

  function syncControlsFromStore() {
    const st = App.Store.get();
    const ci = document.getElementById('catCheckin');
    const co = document.getElementById('catCheckout');
    const g = document.getElementById('catGuests');
    const q = document.getElementById('catQuery');
    const sort = document.getElementById('sortRooms');

    if (ci && ci.value !== st.search.checkin) ci.value = st.search.checkin;
    if (co && co.value !== st.search.checkout) co.value = st.search.checkout;
    if (g) g.value = String(st.search.guests || 1);
    if (q && q.value !== (st.search.query || '')) q.value = st.search.query || '';
    if (sort) sort.value = st.search.sort || 'pop';
  }

  function render() {
    const grid = document.getElementById('hallsGrid');
    if (!grid) return;

    const st = App.Store.get();
    const s = st.search;
    renderFilterChips();
    syncControlsFromStore();

    const countEl = document.getElementById('roomsCountLabel');
    if (countEl) {
      countEl.textContent = `${st.rooms.length} ${plural(st.rooms.length, 'категория', 'категории', 'категорий')}`;
    }

    const nights = nightsBetween(s.checkin, s.checkout);
    const q = (s.query || '').trim().toLowerCase();

    let list = st.rooms.filter(r => {
      if (s.type && r.type !== s.type) return false;
      if (s.guests && r.capacity < Number(s.guests)) return false;
      if (q) {
        const hay = `${r.name} ${r.type} ${r.desc} ${r.view || ''} ${r.beds} ${(r.features || []).join(' ')}`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    });

    if (s.sort === 'cheap') list.sort((a, b) => a.price - b.price);
    else if (s.sort === 'expensive') list.sort((a, b) => b.price - a.price);
    else if (s.sort === 'size') list.sort((a, b) => b.size - a.size);
    else list.sort((a, b) => b.rating - a.rating || a.price - b.price);

    const statusLine = document.getElementById('catalogStatusLine');
    if (statusLine) {
      const freeCount = list.filter(r => !st.unavailableIds.includes(r.id)).length;
      statusLine.textContent = nights > 0
        ? `Показано: ${list.length} · Свободно на выбранные ${nights} ${plural(nights, 'ночь', 'ночи', 'ночей')}: ${freeCount}`
        : `Показано номеров: ${list.length}`;
    }

    if (!list.length) {
      grid.innerHTML = `
        <div class="empty" style="grid-column:1/-1;">
          <div class="ico">${icon('search')}</div>
          <b>По этим параметрам номеров не найдено</b>
          <p>Попробуйте сбросить фильтр категории, уменьшить количество гостей или очистить поисковый запрос.</p>
          <button class="btn btn-sm" type="button" onclick="HotelApp.Views.Catalog.resetFilters()">Сбросить фильтры</button>
        </div>
      `;
      return;
    }

    grid.innerHTML = list.map(r => roomCardHTML(r, {
      showAvailability: nights > 0,
      nights
    })).join('');
  }

  async function applyDateBar(e) {
    if (e) e.preventDefault();
    const ci = document.getElementById('catCheckin').value;
    const co = document.getElementById('catCheckout').value;
    const guests = Number(document.getElementById('catGuests').value) || 1;
    const query = document.getElementById('catQuery').value;

    if (nightsBetween(ci, co) < 1) {
      App.Toast.error('Дата выезда должна быть позже даты заезда');
      return false;
    }

    App.Store.setSearch({ checkin: ci, checkout: co, guests, query });
    await App.Api.checkAvailability(ci, co);
    render();
    return false;
  }

  function setType(type) {
    App.Store.setSearch({ type });
    render();
  }

  function setSort(sort) {
    App.Store.setSearch({ sort });
    render();
  }

  function onSearchInput(val) {
    App.Store.setSearch({ query: val });
    render();
  }

  function resetFilters() {
    App.Store.setSearch({
      type: '',
      guests: 1,
      query: '',
      sort: 'pop',
      checkin: todayISO(1),
      checkout: todayISO(3)
    });
    App.Api.checkAvailability(todayISO(1), todayISO(3)).then(render);
  }

  function onToggleCompare(roomId) {
    const res = App.Store.toggleCompare(roomId);
    if (res.limitReached) {
      App.Toast.info('Можно сравнивать не более 3 номеров одновременно');
      return;
    }
    App.Toast.info(res.added ? 'Номер добавлен к сравнению' : 'Номер убран из сравнения');
    render();
    App.Views.Home && App.Views.Home.renderFeatured();
  }

  function quickBook(roomId) {
    const s = App.Store.get().search;
    App.Views.Booking.prefill(roomId, s.checkin, s.checkout, s.guests);
    App.Router.go('booking');
  }

  function bind() {
    const ci = document.getElementById('catCheckin');
    const co = document.getElementById('catCheckout');
    if (ci) ci.min = todayISO(0);
    if (co) co.min = todayISO(1);
  }

  App.Views.Catalog = {
    roomCardHTML,
    render,
    applyDateBar,
    setType,
    setSort,
    onSearchInput,
    resetFilters,
    onToggleCompare,
    quickBook,
    bind
  };
})(window);
