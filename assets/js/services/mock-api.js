(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { nightsBetween, plural, todayISO, datesOverlap } = App.Format;

  const KEYS = {
    users:   'gn_v2_users',
    current: 'gn_v2_current_user',
    admin:   'gn_v2_admin_session',
    bookings:'gn_v2_bookings',
    reviews: 'gn_v2_reviews',
    seeded:  'gn_v2_seeded'
  };

  function safeRead(key, fallback) {
    try {
      const raw = root.localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function safeWrite(key, value) {
    try {
      if (value === null || value === undefined) {
        root.localStorage.removeItem(key);
      } else {
        root.localStorage.setItem(key, JSON.stringify(value));
      }
    } catch (e) {
      // Storage quota or private mode fallback
    }
  }

  function ensureSeeded() {
    if (safeRead(KEYS.seeded, false)) return;

    const demoUsers = [
      { login: 'maria', name: 'Мария Петрова', phone: '+7 (916) 550-11-22', pass: 'demo1234' },
      { login: 'alexey', name: 'Алексей Соколов', phone: '+7 (925) 310-44-80', pass: '1234' }
    ];

    const demoBookings = [
      {
        id: 41,
        code: 'GN-1041',
        user: 'maria',
        roomId: 'r4',
        roomName: 'Полулюкс «Бутик»',
        name: 'Мария Петрова',
        phone: '+7 (916) 550-11-22',
        email: 'maria@mail.ru',
        checkin: todayISO(4),
        checkout: todayISO(7),
        guests: 2,
        nights: 3,
        extras: [{ id: 'late_out', title: 'Поздний выезд до 16:00', price: 1800 }],
        extrasTotal: 1800,
        total: 20400,
        comment: 'Тихий номер в историческом крыле, заезд около 15:00.',
        status: 'confirm',
        createdAt: '14.11.2026 11:20'
      },
      {
        id: 42,
        code: 'GN-1042',
        user: 'alexey',
        roomId: 'r3',
        roomName: 'Делюкс «Панорама»',
        name: 'Алексей Соколов',
        phone: '+7 (925) 310-44-80',
        email: 'a.sokolov@yandex.ru',
        checkin: todayISO(0),
        checkout: todayISO(2),
        guests: 2,
        nights: 2,
        extras: [{ id: 'transfer', title: 'Трансфер из аэропорта', price: 2500 }],
        extrasTotal: 2500,
        total: 17300,
        comment: 'Прилёт в 13:30, рейс SU-142.',
        status: 'paid',
        createdAt: '15.11.2026 18:05'
      },
      {
        id: 43,
        code: 'GN-1043',
        user: 'maria',
        roomId: 'r6',
        roomName: 'Президентский люкс',
        name: 'Мария Петрова',
        phone: '+7 (916) 550-11-22',
        email: 'maria@mail.ru',
        checkin: todayISO(12),
        checkout: todayISO(14),
        guests: 2,
        nights: 2,
        extras: [{ id: 'romance', title: 'Романтический комплимент', price: 3200 }],
        extrasTotal: 3200,
        total: 40200,
        comment: 'Годовщина — будем признательны за высокий этаж и цветы.',
        status: 'new',
        createdAt: '16.11.2026 09:40'
      }
    ];

    safeWrite(KEYS.users, demoUsers);
    safeWrite(KEYS.bookings, demoBookings);
    safeWrite(KEYS.reviews, App.SEED_REVIEWS.slice());
    safeWrite(KEYS.seeded, true);
  }

  function getRooms() {
    const s = App.Store.get();
    return s.rooms.length ? s.rooms : App.FALLBACK_ROOMS;
  }

  function findRoom(id) {
    return getRooms().find(r => r.id === id) || null;
  }

  function buildClientState() {
    ensureSeeded();
    const user = safeRead(KEYS.current, null);
    const admin = Boolean(safeRead(KEYS.admin, false));
    const allBookings = safeRead(KEYS.bookings, []);
    const allReviews = safeRead(KEYS.reviews, []);

    const myBookings = user
      ? allBookings.filter(b => b.user.toLowerCase() === user.login.toLowerCase())
      : [];
    const myReviews = user
      ? allReviews.filter(r => r.user.toLowerCase() === user.login.toLowerCase())
      : [];

    return {
      user,
      admin,
      bookings: myBookings,
      reviews: myReviews,
      publicReviews: allReviews.slice(0, 24)
    };
  }

  function parseExtras(raw) {
    if (!raw) return { items: [], total: 0 };
    const ids = String(raw).split(',').map(s => s.trim()).filter(Boolean);
    const items = [];
    let total = 0;
    ids.forEach(id => {
      const found = App.BOOKING_EXTRAS.find(e => e.id === id);
      if (found) {
        items.push({ id: found.id, title: found.title, price: found.price });
        total += found.price;
      }
    });
    return { items, total };
  }

  function handle(action, data) {
    ensureSeeded();
    const payload = data || {};
    const users = safeRead(KEYS.users, []);
    const bookings = safeRead(KEYS.bookings, []);
    const reviews = safeRead(KEYS.reviews, []);

    switch (action) {
      case 'state':
        return { ok: true, state: buildClientState() };

      case 'register': {
        const login = String(payload.login || '').trim();
        const name = String(payload.name || '').trim() || login;
        const pass = String(payload.pass || '');
        const phone = String(payload.phone || '').trim();

        if (login.length < 3) throw new Error('Логин — минимум 3 символа');
        if (pass.length < 4) throw new Error('Пароль — минимум 4 символа');
        if (users.some(u => u.login.toLowerCase() === login.toLowerCase())) {
          throw new Error('Такой логин уже занят');
        }

        const newUser = { login, name, phone, pass };
        users.push(newUser);
        safeWrite(KEYS.users, users);
        safeWrite(KEYS.current, { login, name, phone });
        return { ok: true, state: buildClientState() };
      }

      case 'login': {
        const loginRaw = String(payload.login || '').trim().toLowerCase();
        const digits = loginRaw.replace(/\D/g, '');
        const pass = String(payload.pass || '');

        const user = users.find(u => {
          const matchLogin = u.login.toLowerCase() === loginRaw;
          const uDigits = (u.phone || '').replace(/\D/g, '');
          const matchPhone = digits.length >= 6 && uDigits && uDigits.endsWith(digits.slice(-10));
          return (matchLogin || matchPhone) && u.pass === pass;
        });

        if (!user) throw new Error('Неверный логин или пароль');
        safeWrite(KEYS.current, { login: user.login, name: user.name, phone: user.phone || '' });
        return { ok: true, state: buildClientState() };
      }

      case 'logout': {
        safeWrite(KEYS.current, null);
        return { ok: true, state: buildClientState() };
      }

      case 'rooms.availability': {
        const checkin = String(payload.checkin || '');
        const checkout = String(payload.checkout || '');
        if (nightsBetween(checkin, checkout) < 1) {
          return { ok: true, unavailable: [] };
        }
        const busy = new Set();
        bookings.forEach(b => {
          if (b.status !== 'cancel' && datesOverlap(b.checkin, b.checkout, checkin, checkout)) {
            busy.add(b.roomId);
          }
        });
        return { ok: true, unavailable: Array.from(busy) };
      }

      case 'booking.create': {
        const room = findRoom(String(payload.roomId || ''));
        if (!room) throw new Error('Выберите номер');

        const name = String(payload.name || payload.fio || '').trim();
        const phone = String(payload.phone || '').trim();
        const email = String(payload.email || '').trim();
        const checkin = String(payload.checkin || '').trim();
        const checkout = String(payload.checkout || '').trim();
        const guests = Number(payload.guests || 0);
        const comment = String(payload.comment || '').trim();
        const pass = String(payload.pass || '');

        if (!name) throw new Error('Укажите имя гостя');
        if (!phone) throw new Error('Укажите контактный телефон');
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          throw new Error('Проверьте корректность e-mail');
        }

        const nights = nightsBetween(checkin, checkout);
        if (nights < 1) throw new Error('Дата выезда должна быть позже даты заезда');
        if (nights > 60) throw new Error('Максимум 60 ночей для онлайн-бронирования');
        if (guests < 1) throw new Error('Укажите количество гостей');
        if (guests > room.capacity) {
          throw new Error(`В номере «${room.name}» максимум ${room.capacity} ${plural(room.capacity, 'гость', 'гостя', 'гостей')}`);
        }

        const hasConflict = bookings.some(
          b => b.roomId === room.id && b.status !== 'cancel' && datesOverlap(b.checkin, b.checkout, checkin, checkout)
        );
        if (hasConflict) {
          throw new Error(`Номер «${room.name}» уже забронирован на выбранные даты. Выберите другие даты или категорию.`);
        }

        let currentUser = safeRead(KEYS.current, null);
        if (!currentUser) {
          if (pass.length < 4) {
            throw new Error('Придумайте пароль для личного кабинета (минимум 4 символа)');
          }
          const digits = phone.replace(/\D/g, '');
          const login = digits.length >= 6 ? 'g' + digits.slice(-10) : 'guest' + Date.now();
          let existing = users.find(u => u.login.toLowerCase() === login.toLowerCase());
          if (existing) {
            if (existing.pass !== pass) {
              throw new Error('Аккаунт с этим телефоном уже зарегистрирован. Войдите или проверьте пароль.');
            }
            currentUser = { login: existing.login, name: existing.name || name, phone: existing.phone || phone };
          } else {
            const created = { login, name, phone, pass };
            users.push(created);
            safeWrite(KEYS.users, users);
            currentUser = { login, name, phone };
          }
          safeWrite(KEYS.current, currentUser);
        }

        const parsedExtras = parseExtras(payload.extras);
        const total = room.price * nights + parsedExtras.total;
        const nextId = bookings.reduce((m, b) => Math.max(m, Number(b.id) || 0), 43) + 1;

        const now = new Date();
        const createdAt = `${String(now.getDate()).padStart(2, '0')}.${String(now.getMonth() + 1).padStart(2, '0')}.${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

        const newBooking = {
          id: nextId,
          code: 'GN-' + String(1000 + nextId).padStart(4, '0'),
          user: currentUser.login,
          roomId: room.id,
          roomName: room.name,
          name,
          phone,
          email,
          checkin,
          checkout,
          guests,
          nights,
          extras: parsedExtras.items,
          extrasTotal: parsedExtras.total,
          total,
          comment,
          status: 'new',
          createdAt
        };

        bookings.unshift(newBooking);
        safeWrite(KEYS.bookings, bookings);
        return { ok: true, booking: newBooking, state: buildClientState() };
      }

      case 'booking.cancel': {
        const user = safeRead(KEYS.current, null);
        if (!user) throw new Error('Требуется вход');
        const id = Number(payload.id || 0);
        const target = bookings.find(
          b => Number(b.id) === id && b.user.toLowerCase() === user.login.toLowerCase() && b.status === 'new'
        );
        if (!target) throw new Error('Заявка не найдена или уже обработана менеджером');
        target.status = 'cancel';
        safeWrite(KEYS.bookings, bookings);
        return { ok: true, state: buildClientState() };
      }

      case 'review.create': {
        const user = safeRead(KEYS.current, null);
        if (!user) throw new Error('Требуется вход');
        const room = findRoom(String(payload.roomId || ''));
        const rating = Number(payload.rating || 0);
        const text = String(payload.text || '').trim();

        if (!room) throw new Error('Выберите номер');
        if (rating < 1 || rating > 5) throw new Error('Поставьте оценку от 1 до 5');
        if (text.length < 10) throw new Error('Отзыв — минимум 10 символов');

        const now = new Date();
        const date = `${String(now.getDate()).padStart(2, '0')}.${String(now.getMonth() + 1).padStart(2, '0')}.${now.getFullYear()}`;
        const nextId = reviews.reduce((m, r) => Math.max(m, Number(r.id) || 0), 910) + 1;

        reviews.unshift({
          id: nextId,
          user: user.login,
          userName: user.name || user.login,
          roomId: room.id,
          roomName: room.name,
          rating,
          text,
          date
        });
        safeWrite(KEYS.reviews, reviews);
        return { ok: true, state: buildClientState() };
      }

      case 'reviews.list':
        return { ok: true, reviews: reviews.slice(0, 30) };

      case 'admin.login': {
        const login = String(payload.login || '').trim();
        const pass = String(payload.pass || '');
        const valid =
          (login === 'Admin26' && pass === 'Demo26') ||
          (login.toLowerCase() === 'admin' && pass === 'admin123');
        if (!valid) throw new Error('Неверный логин или пароль администратора');
        safeWrite(KEYS.admin, true);
        return { ok: true };
      }

      case 'admin.logout': {
        safeWrite(KEYS.admin, false);
        return { ok: true };
      }

      case 'admin.data': {
        if (!safeRead(KEYS.admin, false)) throw new Error('Нет доступа');
        const today = todayISO(0);
        const stats = {
          total: 0, new: 0, confirm: 0, paid: 0, done: 0, cancel: 0,
          revenue: 0, occupied: 0
        };
        const occupiedSet = new Set();

        bookings.forEach(b => {
          stats.total++;
          if (b.status in stats) stats[b.status]++;
          if (['confirm', 'paid', 'done'].includes(b.status)) {
            stats.revenue += Number(b.total || 0);
          }
          if (b.status !== 'cancel' && b.checkin <= today && b.checkout > today) {
            occupiedSet.add(b.roomId);
          }
        });
        stats.occupied = occupiedSet.size;

        return { ok: true, bookings, stats, reviews };
      }

      case 'admin.setStatus': {
        if (!safeRead(KEYS.admin, false)) throw new Error('Нет доступа');
        const id = Number(payload.id || 0);
        const status = String(payload.status || '');
        const allowed = ['new', 'confirm', 'paid', 'done', 'cancel'];
        if (!allowed.includes(status)) throw new Error('Недопустимый статус');
        const target = bookings.find(b => Number(b.id) === id);
        if (target) {
          target.status = status;
          safeWrite(KEYS.bookings, bookings);
        }
        return { ok: true };
      }

      default:
        throw new Error('Неизвестное действие');
    }
  }

  App.MockApi = {
    handle,
    buildClientState
  };
})(window);
