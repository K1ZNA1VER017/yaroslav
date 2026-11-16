(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc, money, plural } = App.Format;
  const { icon } = App.Icons;

  function renderDock() {
    const dock = document.getElementById('compareDock');
    if (!dock) return;

    const ids = App.Store.get().compareIds;
    const rooms = ids.map(id => App.Store.roomById(id)).filter(Boolean);

    if (!rooms.length) {
      dock.classList.remove('visible');
      return;
    }

    dock.innerHTML = `
      <div class="compare-dock-thumbs">
        ${rooms.map(r => `<div class="compare-dock-thumb" title="${esc(r.name)}"><img src="${esc(r.image)}" alt=""></div>`).join('')}
      </div>
      <span class="compare-dock-text">К сравнению: ${rooms.length} из 3</span>
      <button class="btn btn-gold btn-xs" type="button" onclick="HotelApp.CompareModal.open()" ${rooms.length < 2 ? 'disabled' : ''}>
        Сравнить
      </button>
      <button class="btn btn-glass btn-xs" type="button" onclick="HotelApp.Store.clearCompare()" aria-label="Очистить сравнение">
        Сбросить
      </button>
    `;
    dock.classList.add('visible');
  }

  function open() {
    const rooms = App.Store.get().compareIds.map(id => App.Store.roomById(id)).filter(Boolean);
    if (rooms.length < 2) {
      App.Toast.info('Выберите хотя бы 2 номера для сравнения');
      return;
    }

    const body = document.getElementById('compareModalBody');
    body.innerHTML = `
      <span class="eyebrow">Сравнение категорий</span>
      <h2 style="font-size:34px;">Сравнение выбранных номеров</h2>
      <div class="compare-table-wrap">
        <table class="compare-table">
          <thead>
            <tr>
              <th>Параметр</th>
              ${rooms.map(r => `
                <td class="compare-room-col">
                  <img src="${esc(r.image)}" alt="${esc(r.name)}">
                  <div style="font-family:var(--font-serif); font-size:22px; font-weight:600; line-height:1.15;">${esc(r.name)}</div>
                  <div style="font-size:12.5px; color:var(--muted); margin-top:3px;">${esc(r.type)} · № ${esc(r.number)}</div>
                </td>
              `).join('')}
            </tr>
          </thead>
          <tbody>
            <tr>
              <th>Цена за ночь</th>
              ${rooms.map(r => `<td><b style="font-size:18px; color:var(--brand);">${money(r.price)}</b></td>`).join('')}
            </tr>
            <tr>
              <th>Рейтинг</th>
              ${rooms.map(r => `<td><b style="color:var(--gold-deep);">★ ${Number(r.rating).toFixed(1)}</b></td>`).join('')}
            </tr>
            <tr>
              <th>Площадь</th>
              ${rooms.map(r => `<td><b>${r.size} м²</b></td>`).join('')}
            </tr>
            <tr>
              <th>Вместимость</th>
              ${rooms.map(r => `<td>до ${r.capacity} ${plural(r.capacity, 'гостя', 'гостей', 'гостей')}</td>`).join('')}
            </tr>
            <tr>
              <th>Кровати</th>
              ${rooms.map(r => `<td>${esc(r.beds)}</td>`).join('')}
            </tr>
            <tr>
              <th>Этаж и вид</th>
              ${rooms.map(r => `<td>${esc(r.floor || '—')}<br><span style="color:var(--muted);">${esc(r.view || '—')}</span></td>`).join('')}
            </tr>
            <tr>
              <th>Удобства</th>
              ${rooms.map(r => `<td><div style="display:flex; flex-wrap:wrap; gap:5px;">${(r.features || []).map(f => `<span class="amenity-pill">${esc(f)}</span>`).join('')}</div></td>`).join('')}
            </tr>
            <tr>
              <th>Выбор</th>
              ${rooms.map(r => `
                <td>
                  <button class="btn btn-sm btn-block" type="button" onclick="HotelApp.CompareModal.selectRoom('${esc(r.id)}')">
                    Выбрать номер ${icon('arrow')}
                  </button>
                </td>
              `).join('')}
            </tr>
          </tbody>
        </table>
      </div>
    `;

    document.getElementById('compareModal').classList.add('active');
    document.body.classList.add('modal-open');
  }

  function selectRoom(roomId) {
    close();
    const s = App.Store.get().search;
    App.Views.Booking.prefill(roomId, s.checkin, s.checkout, s.guests);
    App.Router.go('booking');
  }

  function close() {
    document.getElementById('compareModal').classList.remove('active');
    App.Modals.syncBodyLock();
  }

  App.CompareModal = { renderDock, open, close, selectRoom };
})(window);
