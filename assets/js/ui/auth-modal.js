(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { icon } = App.Icons;

  let afterAuthCallback = null;

  function open(screen, context, onSuccess) {
    afterAuthCallback = typeof onSuccess === 'function' ? onSuccess : null;
    switchTab(screen || 'login');

    const badge = document.getElementById('authModalBadge');
    const loginSub = document.getElementById('loginModalSub');
    const regSub = document.getElementById('regModalSub');
    const demoBox = document.getElementById('authDemoBox');

    if (demoBox) {
      demoBox.style.display = App.Store.get().mode === 'php' ? 'none' : 'flex';
    }

    if (context === 'cabinet') {
      badge.style.display = 'inline-flex';
      badge.innerHTML = `${icon('user')}<span>Личный кабинет гостя</span>`;
      loginSub.textContent = 'Войдите, чтобы управлять бронями, ваучерами и отзывами.';
      regSub.textContent = 'Создайте аккаунт — история всех поездок всегда под рукой.';
    } else if (context === 'review') {
      badge.style.display = 'inline-flex';
      badge.innerHTML = `${icon('star')}<span>Отзыв о проживании</span>`;
      loginSub.textContent = 'Войдите, чтобы опубликовать ваш отзыв о номере.';
      regSub.textContent = 'Зарегистрируйтесь за минуту и поделитесь впечатлениями.';
    } else {
      badge.style.display = 'none';
      loginSub.textContent = 'Войдите, чтобы бронировать ещё быстрее и видеть свои ваучеры.';
      regSub.textContent = 'Заполните три поля — личный кабинет готов к работе.';
    }

    document.getElementById('authModal').classList.add('active');
    document.body.classList.add('modal-open');
  }

  function close() {
    document.getElementById('authModal').classList.remove('active');
    afterAuthCallback = null;
    App.Modals.syncBodyLock();
  }

  function switchTab(tab) {
    document.querySelectorAll('#authModal .auth-screen').forEach(el => el.classList.remove('active'));
    const target = document.getElementById('modal-screen-' + tab);
    if (target) target.classList.add('active');

    document.getElementById('modalTabLogin').classList.toggle('active', tab === 'login');
    document.getElementById('modalTabRegister').classList.toggle('active', tab === 'register');

    document.getElementById('loginMsg').textContent = '';
    document.getElementById('regMsg').textContent = '';
  }

  async function handleLogin(e) {
    e.preventDefault();
    const msg = document.getElementById('loginMsg');
    const btn = document.getElementById('btnDoLogin');
    msg.textContent = '';
    btn.disabled = true;

    try {
      await App.Api.request('login', {
        login: document.getElementById('loginUser').value.trim(),
        pass: document.getElementById('loginPass').value
      });
      const cb = afterAuthCallback;
      close();
      App.Toast.success('Добро пожаловать в личный кабинет!');
      App.Router.refreshCurrent();
      if (cb) cb();
    } catch (err) {
      msg.textContent = err.message;
      msg.className = 'msg error';
    } finally {
      btn.disabled = false;
    }
    return false;
  }

  async function handleRegister(e) {
    e.preventDefault();
    const msg = document.getElementById('regMsg');
    const btn = document.getElementById('btnDoRegister');
    msg.textContent = '';
    btn.disabled = true;

    try {
      await App.Api.request('register', {
        login: document.getElementById('regUser').value.trim(),
        name: document.getElementById('regName').value.trim(),
        phone: document.getElementById('regPhone').value.trim(),
        pass: document.getElementById('regPass').value
      });
      const cb = afterAuthCallback;
      close();
      App.Toast.success('Аккаунт создан! Добро пожаловать.');
      App.Router.refreshCurrent();
      if (cb) cb();
    } catch (err) {
      msg.textContent = err.message;
      msg.className = 'msg error';
    } finally {
      btn.disabled = false;
    }
    return false;
  }

  async function demoLogin() {
    try {
      await App.Api.request('login', { login: 'maria', pass: 'demo1234' });
      const cb = afterAuthCallback;
      close();
      App.Toast.success('Вы вошли в демо-аккаунт (Мария Петрова)');
      App.Router.refreshCurrent();
      if (cb) cb();
    } catch (err) {
      App.Toast.error(err.message);
    }
  }

  App.AuthModal = {
    open,
    close,
    switchTab,
    handleLogin,
    handleRegister,
    demoLogin
  };
})(window);
