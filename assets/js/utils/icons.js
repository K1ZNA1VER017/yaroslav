(function (root) {
  'use strict';

  const App = (root.HotelApp = root.HotelApp || {});

  const PATHS = {
    pin:       '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>',
    clock:     '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>',
    tag:       '<path d="M20 13.5 12.5 21 3 11.5V3h8.5L20 11Z"/><circle cx="7.5" cy="7.5" r="1.4"/>',
    heart:     '<path d="M12 20s-7-4.4-7-9.3A4.2 4.2 0 0 1 12 7.6a4.2 4.2 0 0 1 7 3.1C19 15.6 12 20 12 20Z"/>',
    search:    '<circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/>',
    bed:       '<path d="M3 18v-7h14a4 4 0 0 1 4 4v3"/><path d="M3 18h18"/><path d="M7 11V8h5v3"/>',
    check:     '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.3 2.3 2.3 4.7-4.9"/>',
    checkRaw:  '<path d="m5 12.5 4.2 4.2L19 7"/>',
    key:       '<circle cx="8" cy="8" r="4.2"/><path d="m11 11 9 9"/><path d="m16 16 2-2"/><path d="m18.5 18.5 2-2"/>',
    users:     '<path d="M15.5 20v-1.6a3.6 3.6 0 0 0-3.6-3.6H6.6A3.6 3.6 0 0 0 3 18.4V20"/><circle cx="9.2" cy="8.2" r="3.2"/><path d="M21 20v-1.6a3.6 3.6 0 0 0-2.7-3.5"/><path d="M15.8 5.2a3.2 3.2 0 0 1 0 6"/>',
    size:      '<path d="M4 9V4h5"/><path d="M20 15v5h-5"/><path d="M4 4l6.5 6.5"/><path d="M20 20l-6.5-6.5"/>',
    cal:       '<rect x="3.2" y="5" width="17.6" height="16" rx="3"/><path d="M8 3v4M16 3v4M3.2 10h17.6"/>',
    moon:      '<path d="M20 14.5A8.2 8.2 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5Z"/>',
    card:      '<rect x="2.6" y="5" width="18.8" height="14" rx="3"/><path d="M2.6 10h18.8"/>',
    phone:     '<path d="M6.2 3.5h3l1.6 4-2 1.4a12 12 0 0 0 5.9 5.9l1.4-2 4 1.6v3a2 2 0 0 1-2.2 2A16.8 16.8 0 0 1 4.2 5.7a2 2 0 0 1 2-2.2Z"/>',
    user:      '<circle cx="12" cy="8" r="3.6"/><path d="M4.8 20.5a7.2 7.2 0 0 1 14.4 0"/>',
    mail:      '<rect x="2.8" y="5" width="18.4" height="14" rx="3"/><path d="m4 7 8 6 8-6"/>',
    chat:      '<path d="M20.5 12a8.5 8.5 0 0 1-12.4 7.6L3.5 21l1.4-4.6A8.5 8.5 0 1 1 20.5 12Z"/>',
    arrow:     '<path d="M4.5 12h15"/><path d="m13.5 6 6 6-6 6"/>',
    arrowLeft: '<path d="M19.5 12h-15"/><path d="m10.5 6-6 6 6 6"/>',
    lock:      '<rect x="4.4" y="10.5" width="15.2" height="10.5" rx="3"/><path d="M8.2 10.5V8a3.8 3.8 0 0 1 7.6 0v2.5"/>',
    star:      '<path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.2 1 5.9-5.2-2.8-5.2 2.8 1-5.9L3.5 9.7l5.9-.8Z"/>',
    alert:     '<path d="M12 4.5 21 19.5H3Z"/><path d="M12 10v4"/><circle cx="12" cy="16.8" r=".9" fill="currentColor" stroke="none"/>',
    list:      '<rect x="4" y="3.6" width="16" height="17" rx="3"/><path d="M8.2 8.6h7.6M8.2 12.4h7.6M8.2 16.2h4.4"/>',
    home:      '<path d="M3.5 10.5 12 3.8l8.5 6.7"/><path d="M6 9.5V20h12V9.5"/><path d="M10 20v-5.5h4V20"/>',
    coffee:    '<path d="M4 9h12v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V9Z"/><path d="M16 10h2.2a2.3 2.3 0 0 1 0 4.6H16"/><path d="M7.5 5.5c0-1 .8-1.5.8-2.5M11.5 5.5c0-1 .8-1.5.8-2.5"/>',
    sparkles:  '<path d="m12 3 1.7 4.6L18.3 9l-4.6 1.7L12 15.3l-1.7-4.6L5.7 9l4.6-1.4Z"/><path d="m18.5 14 .9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9Z"/>',
    concierge: '<path d="M3 18h18"/><path d="M5 18a7 7 0 0 1 14 0"/><path d="M12 11V8M9.5 8h5"/>',
    shield:    '<path d="M12 3.5 19 6.5v5.3c0 4.5-3 7.7-7 8.8-4-1.1-7-4.3-7-8.8V6.5Z"/><path d="m9 12.2 2 2 4.2-4.2"/>',
    sliders:   '<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="2.2" fill="var(--bg-surface, #fff)"/><circle cx="15" cy="17" r="2.2" fill="var(--bg-surface, #fff)"/>',
    compare:   '<rect x="3.5" y="4.5" width="7" height="15" rx="1.8"/><rect x="13.5" y="4.5" width="7" height="15" rx="1.8"/>',
    printer:   '<path d="M7 9V4.5h10V9"/><rect x="4" y="9" width="16" height="8" rx="2"/><path d="M7 14.5h10V20H7Z"/>',
    eye:       '<path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.8"/>',
    floor:     '<path d="M4 20h16M6 20V6l6-2.5L18 6v14"/><path d="M9.5 10h1M13.5 10h1M9.5 14h1M13.5 14h1"/>'
  };

  function icon(name) {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${PATHS[name] || ''}</svg>`;
  }

  App.Icons = { icon };
})(window);
