(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc, money, plural, nightsBetween, fmtDatePretty, todayISO } = App.Format;
  const { icon } = App.Icons;

  App.Views = App.Views || {};

  const selectedExtras = new Set();

  function populateSelects() {
    const rooms = App.Store.get().rooms;
    const bkHall = document.getElementById('bkHall');
    const rvHall = document.getElementById('rvHall');

    const opts = rooms.map(r =>
      `<option value="${esc(r.id)}">${esc(r.name)} · № ${esc(r.number)} — ${money(r.price)}/ночь</option>`
    ).join('');

    if (bkHall) bkHall.innerHTML = opts;
    if (rvHall) rvHall.innerHTML = opts;
  }

  function renderExtras() {
    const box = document.getElementById('bkExtrasGrid');
    if (!box) return;
    const extras = App.Store.get().extras || App.BOOKING_EXTRAS;

    box.innerHTML = extras.map(ex => {
      const checked = selectedExtras.has(ex.id);
      return `
        <label class="extra-option${checked ? ' checked' : ''}">
          <input type="checkbox" value="${esc(ex.id)}" ${checked ? 'checked' : ''} onchange="HotelApp.Views.Booking.toggleExtra('${esc(ex.id)}')">
          <div class="extra-option-copy">
            <b>${esc(ex.title)}</b>
            <span>${ex.price > 0 ? '+ ' + money(ex.price) : 'Бесплатно'}</span>
          </div>
        </label>
      `;
    }).join('');
  }

  function toggleExtra(id) {
    if (selectedExtras.has(id)) selectedExtras.delete(id);
    else selectedExtras.add(id);
    renderExtras();
    updateSummary();
  }

  function syncGuestsForRoom(room, desired) {
    const sel = document.getElementById('bkGuests');
    if (!sel || !room) return;
    const val = Math.min(Number(desired || sel.value || 2), room.capacity);
    let html = '';
    for (let i = 1; i <= room.capacity; i++) {
      html += `<option value="${i}"${i === val ? ' selected' : ''}>${i} ${plural(i, 'гость', 'гостя', 'гостей')}</option>`;
    }
    sel.innerHTML = html;
  }

  function syncAuthUI() {
    const user = App.Store.get().user;
    const passWrap = document.getElementById('bkPassWrap');
    const passInput = document.getElementById('bkPass');
    const authBanner = document.getElementById('bkAuthBanner');
    const loginTrigger = document.getElementById('bkExistingLoginLink');
    const nameEl = document.getElementById('bkName');
    const phoneEl = document.getElementById('bkPhone');

    if (user) {
      if (passWrap) passWrap.style.display = 'none';
      if (passInput) {
        passInput.required = false;
        passInput.value = '';
      }
      if (loginTrigger) loginTrigger.style.display = 'none';
      if (authBanner) {
        authBanner.style.display = 'flex';
        authBanner.innerHTML = `
          ${icon('check')}
          <span>Вы вошли как <b>${esc(user.name || user.login)}</b>. Бронирование автоматически появится в вашем личном кабинете.</span>
        `;
      }
      if (nameEl && !nameEl.value) nameEl.value = user.name || '';
      if (phoneEl && !phoneEl.value && user.phone) phoneEl.value = user.phone;
    } else {
      if (passWrap) passWrap.style.display = 'flex';
      if (passInput) passInput.required = true;
      if (loginTrigger) loginTrigger.style.display = 'inline-flex';
      if (authBanner) authBanner.style.display = 'none';
    }
  }

  function prefill(roomId, checkin, checkout, guests) {
    const hallEl = document.getElementById('bkHall');
    const ciEl = document.getElementById('bkCheckin');
    const coEl = document.getElementById('bkCheckout');

    if (hallEl && roomId) hallEl.value = roomId;
    if (ciEl && checkin) ciEl.value = checkin;
    if (coEl && checkout) coEl.value = checkout;

    const room = App.Store.roomById(hallEl ? hallEl.value : roomId);
    if (room) syncGuestsForRoom(room, guests);
    updateSummary();
  }

  async function updateSummary() {
    const hallEl = document.getElementById('bkHall');
    const ciEl = document.getElementById('bkCheckin');
    const coEl = document.getElementById('bkCheckout');
    const gEl = document.getElementById('bkGuests');
    const imgBox = document.getElementById('sumImg');
    const body = document.getElementById('sumBody');
    const conflictSlot = document.getElementById('bkConflictSlot');
    const submitBtn = document.getElementById('btnSubmitBooking');

    if (!hallEl || !ciEl || !coEl || !body) return;
    const room = App.Store.roomById(hallEl.value);
    if (!room) return;

    imgBox.innerHTML = `
      <img src="${esc(room.image)}" alt="${esc(room.name)}">
      <span class="summary-img-badge">${esc(room.type)} · № ${esc(room.number)}</span>
    `;

    const nights = nightsBetween(ciEl.value, coEl.value);
    const guests = Number(gEl ? gEl.value : 1) || 1;
    const extrasList = (App.Store.get().extras || App.BOOKING_EXTRAS).filter(ex => selectedExtras.has(ex.id));
    const extrasTotal = extrasList.reduce((s, e) => s + Number(e.price || 0), 0);

    let hasConflict = false;
    if (nights > 0) {
      const busyIds = await App.Api.checkAvailability(ciEl.value, coEl.value);
      hasConflict = busyIds.includes(room.id);

      if (conflictSlot) {
        if (hasConflict) {
          const alts = App.Store.get().rooms.filter(r => !busyIds.includes(r.id) && r.capacity >= guests);
          conflictSlot.innerHTML = `
            <div class="conflict-banner">
              <strong>Номер «${esc(room.name)}» уже занят на выбранные даты</strong>
              <span>Измените даты или выберите другой свободный номер в один клик:</span>
              ${alts.length ? `
                <div class="conflict-alts">
                  ${alts.slice(0, 3).map(a => `
                    <button type="button" class="btn btn-outline btn-xs" onclick="HotelApp.Views.Booking.pickAlt('${esc(a.id)}')">
                      ${esc(a.name)} · ${money(a.price)}
                    </button>
                  `).join('')}
                </div>
              ` : ''}
            </div>
          `;
        } else {
          conflictSlot.innerHTML = '';
        }
      }
    } else if (conflictSlot) {
      conflictSlot.innerHTML = '';
    }

    if (submitBtn) {
      submitBtn.disabled = nights < 1 || hasConflict;
    }

    if (nights < 1) {
      body.innerHTML = `
        <div class="summary-room-name">${esc(room.name)}</div>
        <div class="summary-room-meta">${room.size} м² · до ${room.capacity} гостей · ${esc(room.beds)}</div>
        <div class="note note--danger" style="margin-top:16px;">
          ${icon('alert')}
          <span>Дата выезда должна быть позже даты заезда.</span>
        </div>
      `;
      return;
    }

    const roomSubtotal = room.price * nights;
    const grandTotal = roomSubtotal + extrasTotal;

    body.innerHTML = `
      <div class="summary-room-name">${esc(room.name)}</div>
      <div class="summary-room-meta">${room.size} м² · ${esc(room.beds)} · ${esc(room.view || '')}</div>

      <div class="summary-rows">
        <div class="summary-row"><span>Заезд</span><b>${fmtDatePretty(ciEl.value)}</b></div>
        <div class="summary-row"><span>Выезд</span><b>${fmtDatePretty(coEl.value)}</b></div>
        <div class="summary-row"><span>Гости</span><b>${guests} ${plural(guests, 'гость', 'гостя', 'гостей')}</b></div>
        <div class="summary-row">
          <span>${money(room.price)} × ${nights} ${plural(nights, 'ночь', 'ночи', 'ночей')}</span>
          <b>${money(roomSubtotal)}</b>
        </div>
        ${extrasList.map(ex => `
          <div class="summary-row">
            <span>${esc(ex.title)}</span>
            <b>${ex.price > 0 ? money(ex.price) : 'Бесплатно'}</b>
          </div>
        `).join('')}
        <div class="summary-row">
          <span>Завтрак «шведский стол»</span>
          <b style="color:var(--brand);">Включён</b>
        </div>
      </div>

      <div class="summary-total">
        <div class="summary-total-label">
          Итого к оплате<br>при заселении
        </div>
        <div class="summary-total-val">${money(grandTotal)}</div>
      </div>
    `;
  }

  function pickAlt(roomId) {
    const hallEl = document.getElementById('bkHall');
    if (hallEl) hallEl.value = roomId;
    const room = App.Store.roomById(roomId);
    if (room) syncGuestsForRoom(room);
    updateSummary();
  }

  async function onSubmit(e) {
    e.preventDefault();
    const msg = document.getElementById('bkMsg');
    const btn = document.getElementById('btnSubmitBooking');
    msg.textContent = '';
    btn.disabled = true;

    try {
      const payload = {
        roomId: document.getElementById('bkHall').value,
        checkin: document.getElementById('bkCheckin').value,
        checkout: document.getElementById('bkCheckout').value,
        guests: document.getElementById('bkGuests').value,
        name: document.getElementById('bkName').value.trim(),
        phone: document.getElementById('bkPhone').value.trim(),
        email: document.getElementById('bkEmail').value.trim(),
        comment: document.getElementById('bkComment').value.trim(),
        extras: Array.from(selectedExtras).join(',')
      };

      if (!App.Store.get().user) {
        payload.pass = document.getElementById('bkPass').value;
      }

      const res = await App.Api.request('booking.create', payload);
      selectedExtras.clear();
      renderExtras();

      document.getElementById('bkComment').value = '';
      if (document.getElementById('bkPass')) document.getElementById('bkPass').value = '';

      syncAuthUI();
      await App.Api.checkAvailability(payload.checkin, payload.checkout);
      updateSummary();

      App.Toast.success('Заявка успешно оформлена!');
      if (res.booking) {
        App.VoucherModal.open(res.booking, true);
      } else {
        App.Router.go('cabinet');
      }
    } catch (err) {
      msg.textContent = err.message;
      msg.className = 'msg error';
      App.Toast.error(err.message);
    } finally {
      btn.disabled = false;
    }
    return false;
  }

  function init() {
    populateSelects();
    renderExtras();

    const st = App.Store.get();
    const hallEl = document.getElementById('bkHall');
    const ciEl = document.getElementById('bkCheckin');
    const coEl = document.getElementById('bkCheckout');
    const gEl = document.getElementById('bkGuests');

    if (ciEl) {
      ciEl.min = todayISO(0);
      ciEl.value = st.search.checkin;
      ciEl.addEventListener('change', updateSummary);
    }
    if (coEl) {
      coEl.min = todayISO(1);
      coEl.value = st.search.checkout;
      coEl.addEventListener('change', updateSummary);
    }
    if (hallEl) {
      hallEl.addEventListener('change', () => {
        syncGuestsForRoom(App.Store.roomById(hallEl.value));
        updateSummary();
      });
    }
    if (gEl) gEl.addEventListener('change', updateSummary);

    const firstRoom = App.Store.roomById(hallEl ? hallEl.value : 'r1');
    if (firstRoom) syncGuestsForRoom(firstRoom, st.search.guests);
    syncAuthUI();
    updateSummary();
  }

  App.Views.Booking = {
    init,
    prefill,
    syncAuthUI,
    updateSummary,
    toggleExtra,
    pickAlt,
    onSubmit
  };
})(window);
