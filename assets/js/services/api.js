(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});

  async function callPhp(action, data) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(data || {}).forEach(([key, val]) => {
      if (val !== undefined && val !== null) {
        fd.append(key, String(val));
      }
    });

    const res = await fetch('api.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    });

    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      throw new Error('Сервер PHP недоступен или вернул некорректный ответ');
    }

    if (!json.ok) {
      throw new Error(json.error || 'Неизвестная ошибка сервера');
    }
    return json;
  }

  async function request(action, data) {
    const storeState = App.Store.get();
    let result;

    if (storeState.mode === 'php') {
      result = await callPhp(action, data);
    } else {
      result = App.MockApi.handle(action, data);
    }

    if (result && result.state) {
      App.Store.applyServerState(result.state);
    }
    return result;
  }

  async function checkAvailability(checkin, checkout) {
    try {
      const res = await request('rooms.availability', { checkin, checkout });
      const unavailable = Array.isArray(res.unavailable) ? res.unavailable : [];
      App.Store.patch({ unavailableIds: unavailable });
      return unavailable;
    } catch (err) {
      App.Store.patch({ unavailableIds: [] });
      return [];
    }
  }

  App.Api = {
    request,
    checkAvailability
  };
})(window);
