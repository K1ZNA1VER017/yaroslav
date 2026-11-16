(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc, money, plural, nightsBetween, renderStars, todayISO } = App.Format;
  const { icon } = App.Icons;

  App.Views = App.Views || {};

  const HERO_ROOM_IDS = ['r6', 'r3', 'r1', 'r4'];
  let heroIndex = 0;
  let heroTimer = null;

  function getHeroRooms() {
    const rooms = App.Store.get().rooms;
    const mapped = HERO_ROOM_IDS.map(id => rooms.find(r => r.id === id)).filter(Boolean);
    return mapped.length ? mapped : rooms.slice(0, 4);
  }

  function initHero() {
    const slidesEl = document.getElementById('heroSlides');
    const dotsEl = document.getElementById('heroDots');
    if (!slidesEl || !dotsEl) return;

    const heroRooms = getHeroRooms();
    slidesEl.innerHTML = heroRooms.map((r, i) => `
      <div class="hero-slide${i === 0 ? ' on' : ''}">
        <img src="${esc(r.image)}" alt="${esc(r.name)}">
      </div>
    `).join('');

    dotsEl.innerHTML = heroRooms.map((r, i) => `
      <button type="button" class="${i === 0 ? 'on' : ''}" onclick="HotelApp.Views.Home.setHero(${i})" aria-label="Слайд ${i + 1}: ${esc(r.name)}"></button>
    `).join('');

    updateSpotlight();
    restartTimer();
  }

  function restartTimer() {
    clearInterval(heroTimer);
    heroTimer = setInterval(() => {
      const len = getHeroRooms().length;
      if (len > 1) setHero((heroIndex + 1) % len);
    }, 6500);
  }

  function setHero(index) {
    const heroRooms = getHeroRooms();
    if (!heroRooms.length) return;
    heroIndex = (index + heroRooms.length) % heroRooms.length;

    document.querySelectorAll('#heroSlides .hero-slide').forEach((s, k) => {
      s.classList.toggle('on', k === heroIndex);
    });
    document.querySelectorAll('#heroDots button').forEach((d, k) => {
      d.classList.toggle('on', k === heroIndex);
    });

    updateSpotlight();
    restartTimer();
  }

  function stepHero(delta) {
    setHero(heroIndex + delta);
  }

  function updateSpotlight() {
    const box = document.getElementById('heroSpotlight');
    if (!box) return;
    const room = getHeroRooms()[heroIndex];
    if (!room) return;

    box.onclick = () => App.RoomModal.open(room.id);
    box.innerHTML = `
      <div class="hero-spotlight-kicker">В кадре · № ${esc(room.number)}</div>
      <div class="hero-spotlight-title">${esc(room.name)}</div>
      <div class="hero-spotlight-meta">
        <span>${room.size} м² · ${esc(room.view || room.type)}</span>
        <span class="hero-spotlight-price">${money(room.price)}</span>
      </div>
    `;
  }

  function updateSearchNights() {
    const inEl = document.getElementById('sCheckin');
    const outEl = document.getElementById('sCheckout');
    const pill = document.getElementById('sNightsPill');
    if (!inEl || !outEl || !pill) return;

    const n = nightsBetween(inEl.value, outEl.value);
    pill.innerHTML = n > 0
      ? `${icon('moon')} ${n} ${plural(n, 'ночь', 'ночи', 'ночей')}`
      : `${icon('alert')} Даты?`;
  }

  function renderPillars() {
    const box = document.getElementById('pillarsGrid');
    if (!box) return;
    box.innerHTML = App.HOTEL_PILLARS.map(p => `
      <article class="pillar-card">
        <div class="pillar-top">
          <div class="pillar-ico">${icon(p.ico)}</div>
          <span class="pillar-num">${esc(p.num)}</span>
        </div>
        <h3>${esc(p.title)}</h3>
        <p>${esc(p.text)}</p>
      </article>
    `).join('');
  }

  function renderFeatured() {
    const flagshipEl = document.getElementById('flagshipSpot');
    const popularEl = document.getElementById('popularGrid');
    if (!flagshipEl || !popularEl) return;

    const rooms = App.Store.get().rooms;
    if (!rooms.length) return;

    const flagship = rooms.find(r => r.id === 'r6') || rooms[0];
    const others = rooms.filter(r => r.id !== flagship.id).slice(0, 3);

    flagshipEl.innerHTML = `
      <article class="flagship-card">
        <div class="flagship-media" style="cursor:pointer;" onclick="HotelApp.RoomModal.open('${esc(flagship.id)}')">
          <img src="${esc(flagship.image)}" alt="${esc(flagship.name)}">
        </div>
        <div class="flagship-body">
          <div>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
              <span class="chip chip-gold">${icon('sparkles')} Флагман отеля · № ${esc(flagship.number)}</span>
              <span style="font-weight:800; color:var(--gold-deep);">★ ${Number(flagship.rating).toFixed(1)}</span>
            </div>
            <h3>${esc(flagship.name)}</h3>
            <p class="flagship-tagline">${esc(flagship.desc)}</p>
            <div class="room-specs" style="margin-bottom:0;">
              <span class="spec">${icon('size')} ${flagship.size} м²</span>
              <span class="spec">${icon('users')} до ${flagship.capacity} гостей</span>
              <span class="spec">${icon('bed')} ${esc(flagship.beds)}</span>
            </div>
            <div class="flagship-amenities">
              ${(flagship.features || []).slice(0, 5).map(f => `<span class="amenity-pill">${esc(f)}</span>`).join('')}
            </div>
          </div>

          <div class="flagship-foot">
            <div class="price">
              <b style="font-size:26px;">${money(flagship.price)}</b>
              <span>за ночь · завтрак в номер и трансфер включены</span>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
              <button class="btn btn-outline btn-sm" type="button" onclick="HotelApp.RoomModal.open('${esc(flagship.id)}')">Подробнее</button>
              <button class="btn btn-sm" type="button" onclick="HotelApp.Views.Catalog.quickBook('${esc(flagship.id)}')">Забронировать ${icon('arrow')}</button>
            </div>
          </div>
        </div>
      </article>
    `;

    popularEl.innerHTML = others.map(r => App.Views.Catalog.roomCardHTML(r, { showAvailability: false })).join('');
  }

  function renderPrivileges() {
    const list = document.getElementById('privilegesList');
    if (!list) return;
    list.innerHTML = App.HOTEL_PRIVILEGES.map(item => `
      <div class="privilege-item">
        <div class="privilege-item-ico">${icon(item.ico)}</div>
        <h4>${esc(item.title)}</h4>
        <p>${esc(item.text)}</p>
      </div>
    `).join('');
  }

  function renderPublicReviews() {
    const grid = document.getElementById('homeReviewsGrid');
    const avgEl = document.getElementById('homeAvgRating');
    const countEl = document.getElementById('homeReviewCount');
    if (!grid) return;

    const st = App.Store.get();
    const list = (st.publicReviews && st.publicReviews.length) ? st.publicReviews : App.SEED_REVIEWS;

    const avg = list.reduce((sum, r) => sum + Number(r.rating || 5), 0) / Math.max(1, list.length);
    if (avgEl) avgEl.textContent = avg.toFixed(1);
    if (countEl) countEl.textContent = `На основе ${list.length} ${plural(list.length, 'отзыва', 'отзывов', 'отзывов')} гостей`;

    grid.innerHTML = list.slice(0, 4).map(r => {
      const name = r.userName || r.user || 'Гость отеля';
      return `
        <article class="review-card">
          <div>
            <div class="review-card-top">
              <div class="review-author">
                <div class="avatar">${esc(name.charAt(0).toUpperCase())}</div>
                <div class="review-author-meta">
                  <b>${esc(name)}</b>
                  <span>${esc(r.date || '')}</span>
                </div>
              </div>
              <span class="review-stars">${renderStars(r.rating)}</span>
            </div>
            <p class="review-quote" style="margin-top:12px;">«${esc(r.text)}»</p>
          </div>
          <span class="review-room-tag">${icon('bed')} ${esc(r.roomName)}</span>
        </article>
      `;
    }).join('');
  }

  function renderTimeline() {
    const el = document.getElementById('stepsGrid');
    if (!el) return;
    el.innerHTML = App.BOOKING_STEPS.map(s => `
      <article class="timeline-step">
        <div class="timeline-index">${esc(s.num)}</div>
        <h3>${esc(s.title)}</h3>
        <p>${esc(s.text)}</p>
      </article>
    `).join('');
  }

  function fillSearchTypes() {
    const sel = document.getElementById('sType');
    if (!sel) return;
    const types = [...new Set(App.Store.get().rooms.map(r => r.type))];
    sel.innerHTML = `<option value="">Любая категория</option>` +
      types.map(t => `<option value="${esc(t)}">${esc(t)}</option>`).join('');
  }

  async function onSearchSubmit(e) {
    e.preventDefault();
    const ci = document.getElementById('sCheckin').value;
    const co = document.getElementById('sCheckout').value;
    const guests = Number(document.getElementById('sGuests').value) || 2;
    const type = document.getElementById('sType').value;

    if (nightsBetween(ci, co) < 1) {
      App.Toast.error('Дата выезда должна быть позже даты заезда');
      return false;
    }

    App.Store.setSearch({ checkin: ci, checkout: co, guests, type });
    await App.Api.checkAvailability(ci, co);
    App.Router.go('rooms');
    return false;
  }

  function init() {
    const st = App.Store.get();
    const ci = document.getElementById('sCheckin');
    const co = document.getElementById('sCheckout');
    if (ci) {
      ci.min = todayISO(0);
      ci.value = st.search.checkin;
      ci.addEventListener('change', updateSearchNights);
    }
    if (co) {
      co.min = todayISO(1);
      co.value = st.search.checkout;
      co.addEventListener('change', updateSearchNights);
    }
    fillSearchTypes();
    updateSearchNights();
    initHero();
    renderPillars();
    renderFeatured();
    renderPrivileges();
    renderPublicReviews();
    renderTimeline();
  }

  App.Views.Home = {
    init,
    setHero,
    stepHero,
    renderFeatured,
    renderPublicReviews,
    onSearchSubmit
  };
})(window);
