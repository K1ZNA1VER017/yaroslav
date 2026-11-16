(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc, money, plural, fmtDate, statusBadge, bookingCode } = App.Format;
  const { icon } = App.Icons;

  function open(booking, isFresh) {
    if (!booking) return;
    const room = App.Store.roomById(booking.roomId);
    const code = bookingCode(booking);
    const extras = Array.isArray(booking.extras) ? booking.extras : [];

    const body = document.getElementById('voucherModalBody');
    body.innerHTML = `
      ${isFresh ? `
        <div class="note note--brand" style="margin-bottom:18px;">
          ${icon('check')}
          <span><b>Бронирование оформлено!</b> Заявка закреплена за номером — администратор подтвердит её в ближайшее время.</span>
        </div>
      ` : ''}

      <div class="voucher-sheet">
        <div class="voucher-header">
          <div>
            <div style="font-size:11px; letter-spacing:0.15em; text-transform:uppercase; color:var(--gold-light); font-weight:700;">
              Гостиница.нет · Подтверждение брони
            </div>
            <h3>${esc(booking.roomName)}</h3>
          </div>
          <span class="voucher-code-pill">#${esc(code)}</span>
        </div>

        <div class="voucher-body">
          <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
            <span style="font-size:13px; color:var(--muted);">
              ${room ? `Категория: ${esc(room.type)} · № ${esc(room.number)} · ${room.size} м²` : 'Бутик-отель «Гостиница.нет»'}
            </span>
            ${statusBadge(booking.status)}
          </div>

          <div class="voucher-grid">
            <div class="voucher-cell">
              <span>Гость</span>
              <b>${esc(booking.name || '—')}</b>
            </div>
            <div class="voucher-cell">
              <span>Контакты</span>
              <b>${esc(booking.phone || '—')}${booking.email ? ' · ' + esc(booking.email) : ''}</b>
            </div>
            <div class="voucher-cell">
              <span>Заезд (с 14:00)</span>
              <b>${fmtDate(booking.checkin)}</b>
            </div>
            <div class="voucher-cell">
              <span>Выезд (до 12:00)</span>
              <b>${fmtDate(booking.checkout)}</b>
            </div>
            <div class="voucher-cell">
              <span>Длительность и гости</span>
              <b>${booking.nights} ${plural(booking.nights, 'ночь', 'ночи', 'ночей')} · ${booking.guests} ${plural(booking.guests, 'гость', 'гостя', 'гостей')}</b>
            </div>
            <div class="voucher-cell">
              <span>Питание и отмена</span>
              <b>Завтрак включён · отмена за 24 ч</b>
            </div>
          </div>

          ${extras.length ? `
            <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--line);">
              <div class="field-label" style="margin-bottom:8px;">Дополнительные опции</div>
              <div class="booking-extras-tags">
                ${extras.map(ex => `<span class="amenity-pill">${esc(ex.title)} · ${ex.price ? money(ex.price) : 'Бесплатно'}</span>`).join('')}
              </div>
            </div>
          ` : ''}

          ${booking.comment ? `
            <div style="margin-top:14px; font-size:13.5px; color:var(--ink-2);">
              <b>Пожелания:</b> ${esc(booking.comment)}
            </div>
          ` : ''}

          <div class="voucher-total-bar">
            <div>
              <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:0.08em;">К оплате при заселении</div>
              <div style="font-size:13px; color:var(--ink-2);">Картой или наличными на ресепшен</div>
            </div>
            <strong>${money(booking.total)}</strong>
          </div>
        </div>
      </div>

      <div class="voucher-actions">
        <button class="btn btn-outline btn-sm" type="button" onclick="window.print()">
          ${icon('printer')} Распечатать ваучер
        </button>
        ${isFresh ? `
          <button class="btn btn-sm" type="button" onclick="HotelApp.VoucherModal.close(); HotelApp.Router.go('cabinet');">
            Открыть личный кабинет ${icon('arrow')}
          </button>
        ` : `
          <button class="btn btn-sm" type="button" onclick="HotelApp.VoucherModal.close()">Готово</button>
        `}
      </div>
    `;

    document.getElementById('voucherModal').classList.add('active');
    document.body.classList.add('modal-open');
  }

  function close() {
    document.getElementById('voucherModal').classList.remove('active');
    App.Modals.syncBodyLock();
  }

  App.VoucherModal = { open, close };
})(window);
