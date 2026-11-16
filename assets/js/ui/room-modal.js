(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc, money, plural, nightsBetween, renderStars, todayISO } = App.Format;
  const { icon } = App.Icons;

  let activeRoomId = null;

  async function open(roomId) {
    const room = App.Store.roomById(roomId);
    if (!room) return;
    activeRoomId = room.id;

    const st = App.Store.get();
    const modal = document.getElementById('roomModal');
    const container = document.getElementById('roomModalBody');

    const reviews = (st.publicReviews || []).filter(r => r.roomId === room.id);
    const checkin = st.search.checkin || todayISO(1);
    const checkout = st.search.checkout || todayISO(3);
    const guests = Math.min(st.search.guests || 2, room.capacity);

    let guestsOptions = '';
    for (let i = 1; i <= room.capacity; i++) {
      guestsOptions += `<option value="${i}"${i === guests ? ' selected' : ''}>${i} ${plural(i, 'гость', 'гостя', 'гостей')}</option>`;
    }

    container.innerHTML = `
      <div class="room-modal-layout">
        <div class="room-modal-main">
          <div class="room-modal-hero">
            <img src="${esc(room.image)}" alt="${esc(room.name)}">
            <div class="room-modal-badges">
              <span class="room-tag">${esc(room.type)}</span>
              ${room.badge ? `<span class="chip chip-gold">${esc(room.badge)}</span>` : ''}
              <span class="room-rating"><span class="st">★</span>${Number(room.rating).toFixed(1)}</span>
            </div>
          </div>

          <div class="room-modal-head">
            <span class="eyebrow">Номер № ${esc(room.number)}</span>
            <h2>${esc(room.name)}</h2>
            ${room.tagline ? `<p class="room-modal-tagline">${esc(room.tagline)}</p>` : ''}
            <p style="margin-top:12px; color:var(--ink-2);">${esc(room.desc)}</p>
          </div>

          <div class="room-modal-specs-grid">
            <div class="room-modal-spec">
              <span>Площадь и вместимость</span>
              <b>${room.size} м² · до ${room.capacity} ${plural(room.capacity, 'гостя', 'гостей', 'гостей')}</b>
            </div>
            <div class="room-modal-spec">
              <span>Спальные места</span>
              <b>${esc(room.beds)}</b>
            </div>
            <div class="room-modal-spec">
              <span>Расположение и вид</span>
              <b>${esc(room.floor || 'Основной корпус')} · ${esc(room.view || 'Вид на город')}</b>
            </div>
            <div class="room-modal-spec">
              <span>Ванная комната</span>
              <b>${esc(room.bathroom || 'Собственная ванная комната')}</b>
            </div>
          </div>

          <div class="room-modal-section">
            <h4>Включено в номер</h4>
            <ul class="room-modal-features">
              ${(room.features || []).map(f => `<li>${icon('checkRaw')}<span>${esc(f)}</span></li>`).join('')}
            </ul>
          </div>

          <div class="room-modal-section">
            <h4>Отзывы гостей о номере (${reviews.length})</h4>
            ${reviews.length ? `
              <div class="room-modal-reviews">
                ${reviews.slice(0, 3).map(r => `
                  <div class="room-mini-review">
                    <div class="room-mini-review-top">
                      <b>${esc(r.userName || r.user)}</b>
                      <span class="review-stars">${renderStars(r.rating)}</span>
                    </div>
                    <p>${esc(r.text)}</p>
                  </div>
                `).join('')}
              </div>
            ` : `<p style="font-size:14px; color:var(--muted);">Пока нет отзывов именно об этом номере — станьте первым гостем, поделившимся впечатлениями.</p>`}
          </div>
        </div>

        <aside class="room-modal-side">
          <div class="room-modal-price-head">
            <div>
              <strong>${money(room.price)}</strong>
              <span>за ночь · завтрак включён</span>
            </div>
            <div id="rmAvailSlot"></div>
          </div>

          <div class="field">
            <label class="field-label" for="rmCheckin">Дата заезда</label>
            <input type="date" id="rmCheckin" value="${esc(checkin)}" min="${todayISO(0)}">
          </div>
          <div class="field">
            <label class="field-label" for="rmCheckout">Дата выезда</label>
            <input type="date" id="rmCheckout" value="${esc(checkout)}" min="${todayISO(1)}">
          </div>
          <div class="field">
            <label class="field-label" for="rmGuests">Гостей</label>
            <select id="rmGuests">${guestsOptions}</select>
          </div>

          <div class="room-modal-calc-box" id="rmCalcBox"></div>

          <button class="btn btn-block" id="rmBookBtn" type="button">
            Перейти к бронированию ${icon('arrow')}
          </button>

          <div class="note note--brand">
            ${icon('shield')}
            <span>Оплата при заселении на ресепшен. Заезд с 14:00, выезд до 12:00.</span>
          </div>
        </aside>
      </div>
    `;

    modal.classList.add('active');
    document.body.classList.add('modal-open');

    const inEl = document.getElementById('rmCheckin');
    const outEl = document.getElementById('rmCheckout');
    const gEl = document.getElementById('rmGuests');
    const bookBtn = document.getElementById('rmBookBtn');

    inEl.addEventListener('change', () => updateCalc(room));
    outEl.addEventListener('change', () => updateCalc(room));
    gEl.addEventListener('change', () => updateCalc(room));

    bookBtn.addEventListener('click', () => {
      const ci = inEl.value;
      const co = outEl.value;
      if (nightsBetween(ci, co) < 1) {
        App.Toast.error('Дата выезда должна быть позже даты заезда');
        return;
      }
      App.Store.setSearch({ checkin: ci, checkout: co, guests: Number(gEl.value) || 1 });
      close();
      App.Views.Booking.prefill(room.id, ci, co, Number(gEl.value) || 1);
      App.Router.go('booking');
    });

    await updateCalc(room);
  }

  async function updateCalc(room) {
    const inEl = document.getElementById('rmCheckin');
    const outEl = document.getElementById('rmCheckout');
    const box = document.getElementById('rmCalcBox');
    const availSlot = document.getElementById('rmAvailSlot');
    const btn = document.getElementById('rmBookBtn');
    if (!inEl || !outEl || !box) return;

    const nights = nightsBetween(inEl.value, outEl.value);
    if (nights < 1) {
      box.innerHTML = `<span style="color:var(--danger); font-weight:600;">Выберите дату выезда позже даты заезда</span>`;
      availSlot.innerHTML = '';
      btn.disabled = true;
      return;
    }

    const busyIds = await App.Api.checkAvailability(inEl.value, outEl.value);
    const isBusy = busyIds.includes(room.id);

    availSlot.innerHTML = isBusy
      ? `<span class="avail-pill avail-pill--busy">Занят</span>`
      : `<span class="avail-pill avail-pill--free">Свободен</span>`;

    const subtotal = room.price * nights;
    box.innerHTML = `
      <div class="summary-row"><span>${money(room.price)} × ${nights} ${plural(nights, 'ночь', 'ночи', 'ночей')}</span><b>${money(subtotal)}</b></div>
      <div class="summary-row"><span>Завтрак «шведский стол»</span><b style="color:var(--brand);">Включён</b></div>
      <div class="summary-row" style="padding-top:8px; border-top:1px dashed var(--line-strong);">
        <span><b>Итого за проживание</b></span>
        <b style="font-size:18px; color:var(--brand);">${money(subtotal)}</b>
      </div>
      ${isBusy ? `<div style="font-size:12.5px; color:var(--danger); font-weight:600;">На эти даты номер уже забронирован — выберите другие даты.</div>` : ''}
    `;
    btn.disabled = isBusy;
  }

  function close() {
    activeRoomId = null;
    document.getElementById('roomModal').classList.remove('active');
    App.Modals.syncBodyLock();
  }

  App.RoomModal = { open, close };
})(window);
