(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});

  const STATUS_LABELS = {
    new:     'Новая',
    confirm: 'Подтверждена',
    paid:    'Оплачена',
    done:    'Завершена',
    cancel:  'Отменена'
  };

  const WEEKDAYS = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
  const MONTHS_SHORT = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];

  function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function money(amount) {
    return Number(amount || 0).toLocaleString('ru-RU') + ' ₽';
  }

  function plural(n, one, few, many) {
    const a = Math.abs(n) % 100;
    const b = a % 10;
    if (a > 10 && a < 20) return many;
    if (b > 1 && b < 5) return few;
    if (b === 1) return one;
    return many;
  }

  function todayISO(offsetDays) {
    const d = new Date();
    d.setHours(12, 0, 0, 0);
    d.setDate(d.getDate() + (offsetDays || 0));
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  function fmtDate(iso) {
    if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '—';
    const parts = iso.split('-');
    return `${parts[2]}.${parts[1]}.${parts[0]}`;
  }

  function fmtDatePretty(iso) {
    if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '—';
    const [y, m, d] = iso.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    return `${d} ${MONTHS_SHORT[m - 1]} (${WEEKDAYS[dt.getDay()]})`;
  }

  function nightsBetween(checkin, checkout) {
    if (!checkin || !checkout) return 0;
    const a = new Date(checkin + 'T00:00:00Z');
    const b = new Date(checkout + 'T00:00:00Z');
    if (isNaN(a.getTime()) || isNaN(b.getTime())) return 0;
    const diff = Math.round((b.getTime() - a.getTime()) / 86400000);
    return diff > 0 ? diff : 0;
  }

  function statusLabel(status) {
    return STATUS_LABELS[status] || STATUS_LABELS.new;
  }

  function statusBadge(status) {
    const key = STATUS_LABELS[status] ? status : 'new';
    return `<span class="status ${key}">${esc(STATUS_LABELS[key])}</span>`;
  }

  function renderStars(rating) {
    const r = Math.max(0, Math.min(5, Math.round(Number(rating) || 0)));
    return '★'.repeat(r) + '☆'.repeat(5 - r);
  }

  function bookingCode(b) {
    if (b && b.code) return b.code;
    const id = Number((b && b.id) || 0);
    return 'GN-' + String(1000 + (id % 9000)).padStart(4, '0');
  }

  function datesOverlap(startA, endA, startB, endB) {
    if (!startA || !endA || !startB || !endB) return false;
    return startA < endB && endA > startB;
  }

  App.Format = {
    STATUS_LABELS,
    esc,
    money,
    plural,
    todayISO,
    fmtDate,
    fmtDatePretty,
    nightsBetween,
    statusLabel,
    statusBadge,
    renderStars,
    bookingCode,
    datesOverlap
  };
})(window);
