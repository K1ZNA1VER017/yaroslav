(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc, money, plural, fmtDate, statusBadge, bookingCode, renderStars } = App.Format;
  const { icon } = App.Icons;

  App.Views = App.Views || {};

  const RATING_LABELS = {
    0: 'Выберите оценку',
    1: 'Есть серьёзные замечания',
    2: 'Ниже ожиданий',
    3: 'Неплохо, но есть куда расти',
    4: 'Очень хорошо',
    5: 'Превосходно!'
  };

  let currentRating = 0;

  function switchTab(tab) {
    document.querySelectorAll('#page-cabinet .tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('#cabinetTabs button').forEach(b => {
      b.classList.toggle('active', b.dataset.tab === tab);
    });
    const target = document.getElementById('panel-' + tab);
    if (target) target.classList.add('active');
  }

  function renderStepper(status) {
    if (status === 'cancel') {
      return `
        <div class="note note--danger" style="margin-top:12px; padding:9px 12px; font-size:12.5px;">
          ${icon('alert')}<span>Бронирование отменено. Вы всегда можете оформить новую заявку на удобные даты.</span>
        </div>
      `;
    }

    const stages = [
      { key: 'new',     label: 'Заявка принята' },
      { key: 'confirm', label: 'Подтверждена' },
      { key: 'paid',    label: 'Оплачена' },
      { key: 'done',    label: 'Завершена' }
    ];
    const currentIdx = Math.max(0, stages.findIndex(s => s.key === status));

    return `
      <div class="stepper">
        ${stages.map((st, idx) => {
          const cls = idx < currentIdx ? 'done' : idx === currentIdx ? 'current' : '';
          return `
            <div class="step-dot ${cls}">
              <div class="step-bar"></div>
              <span>${esc(st.label)}</span>
            </div>
          `;
        }).join('')}
      </div>
    `;
  }

  function render() {
    const st = App.Store.get();
    const guestBox = document.getElementById('cabGuestState');
    const userBox = document.getElementById('cabUserState');
    const demoBtn = document.getElementById('cabDemoBtn');

    if (demoBtn) {
      demoBtn.style.display = st.mode === 'php' ? 'none' : 'inline-flex';
    }

    if (!st.user) {
      guestBox.style.display = 'block';
      userBox.style.display = 'none';
      return;
    }

    guestBox.style.display = 'none';
    userBox.style.display = 'block';

    const u = st.user;
    document.getElementById('cabName').textContent = u.name || u.login;
    document.getElementById('cabLogin').textContent = (u.phone ? u.phone + ' · ' : '') + '@' + u.login;
    document.getElementById('cabAvatar').textContent = (u.name || u.login).charAt(0).toUpperCase();

    const myBookings = st.bookings || [];
    const myReviews = st.reviews || [];

    document.getElementById('statBookings').textContent = String(myBookings.length);
    document.getElementById('statNights').textContent = String(
      myBookings.filter(b => b.status !== 'cancel').reduce((sum, b) => sum + (Number(b.nights) || 0), 0)
    );
    document.getElementById('statConfirmed').textContent = String(
      myBookings.filter(b => ['confirm', 'paid', 'done'].includes(b.status)).length
    );
    document.getElementById('statReviews').textContent = String(myReviews.length);

    renderBookings(myBookings);
    renderReviews(myReviews);
  }

  function renderBookings(list) {
    const box = document.getElementById('bookingsList');
    if (!box) return;

    if (!list.length) {
      box.innerHTML = `
        <div class="empty">
          <div class="ico">${icon('cal')}</div>
          <b>Броней пока нет</b>
          <p>Выберите подходящий номер в каталоге и забронируйте его за пару минут.</p>
          <button class="btn btn-sm" type="button" onclick="HotelApp.Router.go('rooms')">Смотреть номера</button>
        </div>
      `;
      return;
    }

    box.innerHTML = list.map(b => {
      const room = App.Store.roomById(b.roomId);
      const extras = Array.isArray(b.extras) ? b.extras : [];
      return `
        <article class="booking-item">
          <div class="booking-thumb">
            <img src="${esc(room ? room.image : 'assets/img/r1.jpg')}" alt="${esc(b.roomName)}">
          </div>
          <div>
            <div class="booking-item-top">
              <h3>${esc(b.roomName)}</h3>
              <span class="booking-code">#${esc(bookingCode(b))}</span>
            </div>
            <div class="booking-meta">
              <span>${icon('cal')} <b>${fmtDate(b.checkin)} — ${fmtDate(b.checkout)}</b></span>
              <span>${icon('moon')} <b>${b.nights}</b> ${plural(b.nights, 'ночь', 'ночи', 'ночей')}</span>
              <span>${icon('users')} <b>${b.guests}</b> ${plural(b.guests, 'гость', 'гостя', 'гостей')}</span>
              <span>${icon('card')} <b>${money(b.total)}</b></span>
            </div>
            ${extras.length ? `
              <div class="booking-extras-tags">
                ${extras.map(ex => `<span class="amenity-pill">${esc(ex.title)}</span>`).join('')}
              </div>
            ` : ''}
            ${b.comment ? `<div class="booking-comment">${icon('chat')} <span>${esc(b.comment)}</span></div>` : ''}
            ${renderStepper(b.status)}
          </div>
          <div class="booking-actions">
            ${statusBadge(b.status)}
            <div class="booking-actions-btns">
              <button class="btn btn-outline btn-xs" type="button" onclick="HotelApp.Views.Cabinet.openVoucher(${b.id})">
                ${icon('printer')} Ваучер
              </button>
              <button class="btn btn-ghost btn-xs" type="button" onclick="HotelApp.Views.Cabinet.reviewRoom('${esc(b.roomId)}')">
                Отзыв
              </button>
              ${b.status === 'new' ? `
                <button class="btn btn-danger-soft btn-xs" type="button" onclick="HotelApp.Views.Cabinet.cancelBooking(${b.id})">
                  Отменить
                </button>
              ` : ''}
            </div>
          </div>
        </article>
      `;
    }).join('');
  }

  function openVoucher(bookingId) {
    const b = (App.Store.get().bookings || []).find(item => Number(item.id) === Number(bookingId));
    if (b) App.VoucherModal.open(b, false);
  }

  function reviewRoom(roomId) {
    const sel = document.getElementById('rvHall');
    if (sel && roomId) sel.value = roomId;
    switchTab('newReview');
  }

  async function cancelBooking(id) {
    const ok = await App.Toast.confirm({
      title: 'Отменить бронирование?',
      message: 'Заявка получит статус «Отменена», и выбранные даты снова станут доступны другим гостям.',
      confirmText: 'Да, отменить',
      danger: true
    });
    if (!ok) return;

    try {
      await App.Api.request('booking.cancel', { id });
      App.Toast.info('Бронирование отменено');
      render();
    } catch (err) {
      App.Toast.error(err.message);
    }
  }

  function renderReviews(list) {
    const box = document.getElementById('reviewsList');
    if (!box) return;

    if (!list.length) {
      box.innerHTML = `
        <div class="empty">
          <div class="ico">${icon('star')}</div>
          <b>Вы ещё не оставляли отзывов</b>
          <p>Расскажите о своих впечатлениях — это помогает другим гостям с выбором.</p>
          <button class="btn btn-sm" type="button" onclick="HotelApp.Views.Cabinet.switchTab('newReview')">Написать отзыв</button>
        </div>
      `;
      return;
    }

    box.innerHTML = list.map(r => `
      <article class="review-card">
        <div class="review-card-top">
          <b>${esc(r.roomName)}</b>
          <span class="review-stars">${renderStars(r.rating)}</span>
        </div>
        <p class="review-quote">${esc(r.text)}</p>
        <div style="font-size:12px; color:var(--muted);">${esc(r.date)}</div>
      </article>
    `).join('');
  }

  function updateStarsUI(hoverVal) {
    const val = hoverVal || currentRating;
    document.querySelectorAll('#starsInput button').forEach(btn => {
      const v = Number(btn.dataset.v);
      btn.classList.toggle('lit', v <= val);
      btn.textContent = v <= val ? '★' : '☆';
    });
    const cap = document.getElementById('starsCaption');
    if (cap) cap.textContent = RATING_LABELS[val] || '';
  }

  function initComposer() {
    const wrap = document.getElementById('starsInput');
    if (wrap) {
      wrap.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', () => {
          currentRating = Number(btn.dataset.v);
          updateStarsUI(0);
        });
        btn.addEventListener('mouseenter', () => updateStarsUI(Number(btn.dataset.v)));
      });
      wrap.addEventListener('mouseleave', () => updateStarsUI(0));
    }

    const textEl = document.getElementById('rvText');
    const counter = document.getElementById('rvCharCount');
    if (textEl && counter) {
      textEl.addEventListener('input', () => {
        const len = textEl.value.trim().length;
        counter.textContent = len < 10 ? `Ещё ${10 - len} симв. (мин. 10)` : `${len} симв. · готово к публикации`;
        counter.style.color = len >= 10 ? 'var(--brand)' : 'var(--muted)';
      });
    }
  }

  async function submitReview(e) {
    if (e) e.preventDefault();
    if (!App.Store.get().user) {
      App.AuthModal.open('login', 'review');
      return false;
    }

    const msg = document.getElementById('rvMsg');
    msg.textContent = '';

    try {
      await App.Api.request('review.create', {
        roomId: document.getElementById('rvHall').value,
        rating: currentRating,
        text: document.getElementById('rvText').value.trim()
      });

      document.getElementById('rvText').value = '';
      currentRating = 0;
      updateStarsUI(0);
      const counter = document.getElementById('rvCharCount');
      if (counter) counter.textContent = 'Минимум 10 символов';

      App.Toast.success('Спасибо! Ваш отзыв опубликован.');
      render();
      App.Views.Home && App.Views.Home.renderPublicReviews();
      switchTab('reviews');
    } catch (err) {
      msg.textContent = err.message;
      msg.className = 'msg error';
    }
    return false;
  }

  App.Views.Cabinet = {
    render,
    switchTab,
    openVoucher,
    reviewRoom,
    cancelBooking,
    initComposer,
    submitReview
  };
})(window);
