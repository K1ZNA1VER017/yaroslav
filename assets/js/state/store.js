(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { todayISO } = App.Format;

  const listeners = new Set();

  const state = {
    mode: 'standalone',
    dbReady: false,
    rooms: [],
    extras: App.BOOKING_EXTRAS.slice(),
    user: null,
    admin: false,
    bookings: [],
    reviews: [],
    publicReviews: [],
    unavailableIds: [],
    compareIds: [],
    activePage: 'home',
    adminActiveTab: 'bookings',
    adminFilterStatus: '',
    adminSearchQuery: '',
    search: {
      checkin: todayISO(1),
      checkout: todayISO(3),
      guests: 2,
      type: '',
      query: '',
      sort: 'pop'
    }
  };

  function get() {
    return state;
  }

  function roomById(id) {
    return state.rooms.find(r => r.id === id) || null;
  }

  function patch(partial) {
    Object.assign(state, partial);
    notify();
  }

  function applyServerState(serverState) {
    if (!serverState) return;
    if ('user' in serverState) state.user = serverState.user;
    if ('admin' in serverState) state.admin = Boolean(serverState.admin);
    if (Array.isArray(serverState.bookings)) state.bookings = serverState.bookings;
    if (Array.isArray(serverState.reviews)) state.reviews = serverState.reviews;
    if (Array.isArray(serverState.publicReviews)) {
      state.publicReviews = serverState.publicReviews;
    }
    notify();
  }

  function setSearch(partial) {
    state.search = Object.assign({}, state.search, partial);
    notify();
  }

  function toggleCompare(roomId) {
    const idx = state.compareIds.indexOf(roomId);
    if (idx >= 0) {
      state.compareIds.splice(idx, 1);
      notify();
      return { added: false };
    }
    if (state.compareIds.length >= 3) {
      return { added: false, limitReached: true };
    }
    state.compareIds.push(roomId);
    notify();
    return { added: true };
  }

  function clearCompare() {
    state.compareIds = [];
    notify();
  }

  function subscribe(fn) {
    listeners.add(fn);
    return function () {
      listeners.delete(fn);
    };
  }

  function notify() {
    listeners.forEach(fn => {
      try { fn(state); } catch (err) { console.error(err); }
    });
  }

  App.Store = {
    get,
    roomById,
    patch,
    applyServerState,
    setSearch,
    toggleCompare,
    clearCompare,
    subscribe
  };
})(window);
