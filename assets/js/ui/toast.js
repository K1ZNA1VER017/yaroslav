(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});
  const { esc } = App.Format;
  const { icon } = App.Icons;

  function showToast(message, type) {
    const stack = document.getElementById('toastStack');
    if (!stack) return;

    const kind = type || 'info';
    const icoName = kind === 'success' ? 'check' : kind === 'error' ? 'alert' : 'sparkles';

    const el = document.createElement('div');
    el.className = `toast toast--${kind}`;
    el.setAttribute('role', 'status');
    el.innerHTML = `${icon(icoName)}<span>${esc(message)}</span>`;
    stack.appendChild(el);

    setTimeout(() => {
      el.classList.add('leaving');
      setTimeout(() => el.remove(), 240);
    }, 3600);
  }

  let confirmResolver = null;

  function askConfirm(opts) {
    const modal = document.getElementById('confirmModal');
    if (!modal) {
      return Promise.resolve(root.confirm((opts && opts.message) || 'Подтвердить действие?'));
    }

    document.getElementById('confirmTitle').textContent = (opts && opts.title) || 'Подтвердите действие';
    document.getElementById('confirmText').textContent = (opts && opts.message) || '';

    const okBtn = document.getElementById('confirmOkBtn');
    okBtn.textContent = (opts && opts.confirmText) || 'Подтвердить';
    okBtn.className = 'btn btn-sm ' + (opts && opts.danger ? 'btn-danger-soft' : '');

    modal.classList.add('active');
    document.body.classList.add('modal-open');

    return new Promise(resolve => {
      confirmResolver = resolve;
    });
  }

  function resolveConfirm(answer) {
    const modal = document.getElementById('confirmModal');
    if (modal) modal.classList.remove('active');
    App.Modals && App.Modals.syncBodyLock();
    if (confirmResolver) {
      const fn = confirmResolver;
      confirmResolver = null;
      fn(Boolean(answer));
    }
  }

  App.Toast = {
    show: showToast,
    success: msg => showToast(msg, 'success'),
    error: msg => showToast(msg, 'error'),
    info: msg => showToast(msg, 'info'),
    confirm: askConfirm,
    resolveConfirm
  };
})(window);
