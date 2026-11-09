<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

$user         = current_user();
$initialState = state_for_client();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>банкетам.нет — портал банкетных залов</title>
<style>
    :root {
        --logo-width: 220px;
        --logo-top: 14px;
        --logo-left: 20px;

        --cream:   #fffdd0;
        --peach:   #ffdab9;
        --gold:    #daa520;
        --crimson: #dc143c;
        --green:   #006400;
        --dark:    #2b2118;
        --gray:    #6b6257;
        --white:   #ffffff;
        --shadow:  0 6px 24px rgba(43, 33, 24, .12);
        --radius:  14px;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
        margin: 0;
        min-height: 100vh;
        background: var(--cream);
        color: var(--dark);
        font-family: 'Segoe UI', Arial, sans-serif;
        display: flex;
        flex-direction: column;
        line-height: 1.5;
    }

    h1, h2, h3 { margin: 0 0 12px; line-height: 1.25; }
    a { color: inherit; text-decoration: none; }

    /* ==================== ЛОГОТИП ==================== */
    .logo {
        position: fixed;
        top: var(--logo-top);
        left: var(--logo-left);
        width: var(--logo-width);
        z-index: 1200;
        cursor: pointer;
        transition: transform .2s;
    }
    .logo:hover { transform: scale(1.03); }
    .logo img { width: 100%; height: auto; display: block; object-fit: contain; }
    .logo-text {
        font-weight: 900; font-size: 22px; letter-spacing: -.5px;
        color: var(--crimson); text-shadow: 0 1px 0 #fff;
    }
    .logo-text span { color: var(--green); }

    /* ==================== ШЕСТЕРЁНКА ==================== */
    .gear-btn {
        position: fixed; top: 16px; right: 20px;
        width: 48px; height: 48px; border-radius: 50%;
        background: var(--white); border: 2px solid var(--peach);
        box-shadow: var(--shadow); display: grid; place-items: center;
        font-size: 24px; cursor: pointer; z-index: 1300;
        transition: .3s; color: var(--green); user-select: none;
    }
    .gear-btn:hover { transform: rotate(90deg) scale(1.08); border-color: var(--gold); color: var(--crimson); }
    .gear-btn.active { background: var(--green); color: var(--peach); border-color: var(--green); }

    /* ==================== ШАПКА ==================== */
    header.site-header {
        position: sticky; top: 0; z-index: 1100;
        background: rgba(255, 253, 208, .92);
        backdrop-filter: blur(10px);
        border-bottom: 2px solid var(--peach);
        box-shadow: 0 2px 14px rgba(43,33,24,.06);
        display: none;
    }
    header.site-header.visible { display: block; }

    .header-inner {
        max-width: 1180px; margin: 0 auto;
        padding: 14px 80px 14px 260px;
        display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
    }
    nav.main-nav { display: flex; gap: 6px; flex-wrap: wrap; margin-left: auto; }
    nav.main-nav a {
        padding: 9px 16px; border-radius: 999px; font-weight: 600;
        font-size: 15px; color: var(--dark); transition: .2s;
        border: 1px solid transparent; cursor: pointer;
    }
    nav.main-nav a:hover { background: var(--peach); border-color: var(--gold); }
    nav.main-nav a.active { background: var(--green); color: var(--peach); }
    .user-chip {
        display: flex; align-items: center; gap: 10px;
        background: var(--white); border: 1px solid var(--peach);
        border-radius: 999px; padding: 6px 8px 6px 16px;
        font-size: 14px; font-weight: 600;
    }
    .avatar {
        width: 30px; height: 30px; border-radius: 50%;
        background: var(--crimson); color: #fff;
        display: grid; place-items: center; font-size: 14px; font-weight: 700;
    }
    .link-btn {
        background: none; border: none; color: var(--crimson);
        font-weight: 700; cursor: pointer; font-size: 14px; padding: 4px 6px;
    }
    .link-btn:hover { text-decoration: underline; }

    /* ==================== ОБЩИЕ БЛОКИ ==================== */
    main {
        flex: 1; width: 100%; max-width: 1180px;
        margin: 0 auto; padding: 32px 24px 60px;
    }

    .page { display: none; }
    .page.active { display: block; animation: fade .35s ease; }
    @keyframes fade {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: none; }
    }

    .section-title { font-size: 28px; color: var(--green); margin-bottom: 6px; }
    .section-sub { color: var(--gray); margin-bottom: 26px; }

    .btn {
        display: inline-block; padding: 11px 22px; border-radius: 999px;
        border: 2px solid var(--crimson); background: var(--crimson);
        color: #fff; font-weight: 700; font-size: 15px; cursor: pointer;
        transition: .2s; text-align: center;
    }
    .btn:hover { background: #fff; color: var(--crimson); }
    .btn-outline { background: transparent; color: var(--green); border-color: var(--green); }
    .btn-outline:hover { background: var(--green); color: var(--peach); }
    .btn-small { padding: 7px 15px; font-size: 13px; }

    /* ==================== ЭКРАН АВТОРИЗАЦИИ ==================== */
    #page-auth {
        min-height: 100vh;
        display: none;
        place-items: center;
        padding: 100px 20px 40px;
    }
    #page-auth.active { display: grid; }

    .auth-card {
        width: 100%; max-width: 420px; background: #fff;
        border: 1px solid var(--peach); border-radius: var(--radius);
        box-shadow: var(--shadow); padding: 32px 30px;
        animation: pop .35s ease;
    }
    @keyframes pop {
        from { transform: scale(.95); opacity: 0; }
        to   { transform: scale(1); opacity: 1; }
    }

    .auth-brand { text-align: center; margin-bottom: 22px; }
    .auth-brand h2 { color: var(--green); font-size: 24px; margin-bottom: 4px; }
    .auth-brand p { color: var(--gray); font-size: 14px; margin: 0; }

    .auth-screen { display: none; }
    .auth-screen.active { display: block; animation: fade .3s; }

    input {
        width: 100%; padding: 12px 14px; margin: 8px 0;
        background: #fff; color: var(--dark);
        border: 1.5px solid var(--peach); border-radius: 10px;
        font-size: 15px; font-family: inherit; transition: .2s;
    }
    input:focus {
        outline: none; border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(0,100,0,.12);
    }
    .auth-screen button[type="submit"] {
        width: 100%; padding: 13px; margin-top: 16px;
        background: var(--crimson); color: #fff;
        border: 2px solid var(--crimson); border-radius: 999px;
        font-weight: 700; font-size: 15px; cursor: pointer; transition: .2s;
    }
    .auth-screen button[type="submit"]:hover { background: #fff; color: var(--crimson); }

    .auth-switch { text-align: center; margin-top: 18px; font-size: 14px; color: var(--gray); }
    .auth-switch a { color: var(--crimson); font-weight: 700; cursor: pointer; text-decoration: underline; }
    .auth-switch a:hover { color: var(--green); }

    .msg { text-align: center; margin-top: 12px; font-size: 14px; font-weight: 600; min-height: 20px; }
    .msg.error { color: var(--crimson); }
    .msg.ok    { color: var(--green); }

    /* ==================== ГЛАВНАЯ / HERO ==================== */
    .hero {
        background: linear-gradient(135deg, var(--green), #0b8a0b 60%, #2f7d2f);
        color: var(--cream); border-radius: var(--radius);
        padding: 54px 44px; box-shadow: var(--shadow);
        position: relative; overflow: hidden;
    }
    .hero::after {
        content: ""; position: absolute; right: -80px; top: -80px;
        width: 300px; height: 300px; border-radius: 50%;
        background: rgba(255, 218, 185, .18);
    }
    .hero h1 { font-size: 40px; max-width: 640px; margin-bottom: 14px; }
    .hero p { max-width: 620px; font-size: 17px; opacity: .95; margin-bottom: 26px; }
    .hero .btn { border-color: var(--peach); background: var(--peach); color: var(--dark); }
    .hero .btn:hover { background: transparent; color: var(--peach); }

    .features {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 20px; margin-top: 36px;
    }
    .feature {
        background: #fff; border: 1px solid var(--peach); border-radius: var(--radius);
        padding: 22px; box-shadow: var(--shadow); transition: .25s;
    }
    .feature:hover { transform: translateY(-4px); }
    .feature .ico { font-size: 30px; margin-bottom: 10px; }
    .feature h3 { color: var(--crimson); font-size: 17px; }
    .feature p { color: var(--gray); font-size: 14px; margin: 0; }

    /* ==================== ЗАЛЫ ==================== */
    .halls-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
    }
    .hall-card {
        background: #fff; border: 1px solid var(--peach); border-radius: var(--radius);
        overflow: hidden; box-shadow: var(--shadow);
        display: flex; flex-direction: column; transition: .25s;
    }
    .hall-card:hover { transform: translateY(-5px); }

    .hall-img {
        position: relative; width: 100%; aspect-ratio: 16 / 9;
        background: linear-gradient(135deg, var(--peach), var(--gold));
        overflow: hidden;
    }
    .hall-img img {
        width: 100%; height: 100%; object-fit: cover; display: block;
        transition: transform .5s;
    }
    .hall-card:hover .hall-img img { transform: scale(1.06); }

    .hall-img .fallback {
        position: absolute; inset: 0; display: grid; place-items: center;
        font-size: 54px; color: #fff; z-index: 0;
    }
    .hall-img img { position: relative; z-index: 1; }

    .hall-body { padding: 18px; flex: 1; display: flex; flex-direction: column; }
    .hall-body h3 { color: var(--green); font-size: 19px; }
    .hall-meta {
        font-size: 13px; color: var(--gray); display: flex;
        gap: 14px; flex-wrap: wrap; margin-bottom: 12px;
    }
    .hall-price { font-size: 20px; font-weight: 800; color: var(--crimson); margin: 6px 0 14px; }
    .hall-price small { font-size: 13px; color: var(--gray); font-weight: 500; }
    .hall-body .btn { margin-top: auto; }

    /* ==================== БРОНИРОВАНИЕ ==================== */
    .booking-card {
        background: #fff; border: 1px solid var(--peach); border-radius: var(--radius);
        box-shadow: var(--shadow); padding: 28px; max-width: 560px; margin: 0 auto;
    }
    label.field-label {
        font-size: 13px; font-weight: 700; color: var(--gray);
        display: block; margin-top: 10px;
    }
    select, textarea {
        width: 100%; padding: 11px 13px; margin: 7px 0;
        background: #fff; color: var(--dark);
        border: 1.5px solid var(--peach); border-radius: 10px;
        font-size: 15px; font-family: inherit; transition: .2s;
    }
    select:focus, textarea:focus {
        outline: none; border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(0,100,0,.12);
    }
    #bookingForm button[type="submit"] {
        width: 100%; padding: 13px; margin-top: 16px;
        background: var(--crimson); color: #fff;
        border: 2px solid var(--crimson); border-radius: 999px;
        font-weight: 700; font-size: 15px; cursor: pointer; transition: .2s;
    }
    #bookingForm button[type="submit"]:hover { background: #fff; color: var(--crimson); }

    /* ==================== ЛИЧНЫЙ КАБИНЕТ ==================== */
    .cabinet-head {
        display: flex; align-items: center; gap: 20px;
        background: #fff; border: 1px solid var(--peach); border-radius: var(--radius);
        padding: 24px; box-shadow: var(--shadow); margin-bottom: 28px; flex-wrap: wrap;
    }
    .cabinet-avatar {
        width: 72px; height: 72px; border-radius: 50%;
        background: var(--green); color: var(--peach);
        display: grid; place-items: center; font-size: 30px; font-weight: 800;
    }
    .cabinet-head h2 { margin: 0; color: var(--green); }
    .cabinet-head p { margin: 2px 0 0; color: var(--gray); font-size: 14px; }

    .stat-row {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px; margin-bottom: 28px;
    }
    .stat {
        background: #fff; border: 1px solid var(--peach); border-radius: var(--radius);
        padding: 18px; text-align: center; box-shadow: var(--shadow);
    }
    .stat .num { font-size: 30px; font-weight: 800; color: var(--crimson); }
    .stat .lbl { font-size: 13px; color: var(--gray); }

    .tabs-inner { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
    .tabs-inner button {
        padding: 10px 20px; border-radius: 999px; border: 2px solid var(--peach);
        background: #fff; font-weight: 700; cursor: pointer;
        color: var(--gray); transition: .2s;
    }
    .tabs-inner button.active { background: var(--green); border-color: var(--green); color: var(--peach); }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: fade .3s; }

    .booking-item {
        background: #fff; border: 1px solid var(--peach);
        border-left: 5px solid var(--gold); border-radius: 12px;
        padding: 18px 20px; margin-bottom: 14px;
        box-shadow: 0 3px 12px rgba(43,33,24,.07);
    }
    .booking-item h4 { margin: 0 0 8px; color: var(--green); font-size: 17px; }
    .booking-meta {
        display: flex; gap: 18px; flex-wrap: wrap;
        font-size: 13px; color: var(--gray); margin-bottom: 8px;
    }
    .booking-meta b { color: var(--dark); }
    .status {
        display: inline-block; padding: 3px 12px; border-radius: 999px;
        font-size: 12px; font-weight: 700;
    }
    .status.new       { background: #fff3cd; color: #8a6d00; }
    .status.confirm   { background: #d4edda; color: #1a6b2f; }
    .status.assigned  { background: #cce5ff; color: #004085; }
    .status.done      { background: #e2e3e5; color: #383d41; }
    .status.cancel    { background: #f8d7da; color: #a11a2b; }

    .empty {
        text-align: center; padding: 50px 20px; color: var(--gray);
        background: #fff; border: 2px dashed var(--peach); border-radius: var(--radius);
    }
    .empty .ico { font-size: 44px; margin-bottom: 12px; }

    .review-form {
        background: #fff; border: 1px solid var(--peach); border-radius: var(--radius);
        padding: 22px; box-shadow: var(--shadow); margin-bottom: 26px;
    }
    .stars {
        display: flex; gap: 6px; font-size: 28px; color: var(--gold);
        cursor: pointer; user-select: none; margin: 6px 0 12px;
    }
    .stars span { transition: .15s; }
    .stars span:hover { transform: scale(1.15); }

    .review-item {
        background: #fff; border: 1px solid var(--peach); border-radius: 12px;
        padding: 18px 20px; margin-bottom: 14px;
        box-shadow: 0 3px 12px rgba(43,33,24,.07);
    }
    .review-top {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 8px; flex-wrap: wrap; gap: 8px;
    }
    .review-top b { color: var(--green); }
    .review-stars { color: var(--gold); font-size: 17px; }
    .review-text { color: var(--dark); margin: 0; }
    .review-date { font-size: 12px; color: var(--gray); }

    /* ==================== АДМИН-ПАНЕЛЬ ==================== */
    #page-admin { min-height: 100vh; display: none; padding: 100px 24px 40px; }
    #page-admin.active { display: block; }

    .admin-login-card {
        width: 100%; max-width: 400px; margin: 0 auto;
        background: #fff; border: 2px solid var(--gold); border-radius: var(--radius);
        box-shadow: var(--shadow); padding: 32px 30px;
    }
    .admin-login-card h2 { text-align: center; color: var(--crimson); margin-bottom: 6px; }
    .admin-login-card p { text-align: center; color: var(--gray); font-size: 14px; margin: 0 0 18px; }
    .admin-login-card button[type="submit"] {
        width: 100%; padding: 13px; margin-top: 16px;
        background: var(--green); color: var(--peach);
        border: 2px solid var(--green); border-radius: 999px;
        font-weight: 700; font-size: 15px; cursor: pointer; transition: .2s;
    }
    .admin-login-card button[type="submit"]:hover { background: #fff; color: var(--green); }

    .admin-panel { display: none; max-width: 1180px; margin: 0 auto; }
    .admin-panel.active { display: block; }

    .admin-header {
        display: flex; justify-content: space-between; align-items: center;
        background: linear-gradient(135deg, var(--dark), #4a3a2c);
        color: var(--peach); border-radius: var(--radius);
        padding: 22px 28px; margin-bottom: 24px;
        flex-wrap: wrap; gap: 12px; box-shadow: var(--shadow);
    }
    .admin-header h2 { margin: 0; color: var(--gold); }

    .admin-stats {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 12px; margin-bottom: 24px;
    }
    .admin-stat {
        background: #fff; border: 1px solid var(--peach); border-radius: 12px;
        padding: 14px; text-align: center;
        box-shadow: 0 3px 12px rgba(43,33,24,.07);
    }
    .admin-stat .num { font-size: 24px; font-weight: 800; color: var(--crimson); }
    .admin-stat .lbl { font-size: 12px; color: var(--gray); }

    .admin-booking {
        background: #fff; border: 1px solid var(--peach);
        border-left: 5px solid var(--gold); border-radius: 12px;
        padding: 20px; margin-bottom: 16px;
        box-shadow: 0 3px 12px rgba(43,33,24,.07);
    }
    .admin-booking-top {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 10px; margin-bottom: 10px;
    }
    .admin-booking-top h4 { margin: 0; color: var(--green); font-size: 18px; }
    .admin-booking-user {
        font-size: 13px; color: var(--gray); background: var(--cream);
        padding: 3px 12px; border-radius: 999px;
    }
    .admin-booking-meta {
        display: flex; gap: 18px; flex-wrap: wrap;
        font-size: 13px; color: var(--gray); margin-bottom: 10px;
    }
    .admin-booking-meta b { color: var(--dark); }
    .admin-actions {
        display: flex; gap: 8px; flex-wrap: wrap;
        margin-top: 12px; padding-top: 12px;
        border-top: 1px dashed var(--peach);
    }
    .admin-actions button {
        padding: 6px 14px; border-radius: 999px; border: 1.5px solid;
        font-weight: 700; font-size: 12px; cursor: pointer;
        transition: .2s; background: #fff;
    }
    .act-confirm  { border-color: #1a6b2f; color: #1a6b2f; }
    .act-confirm:hover  { background: #1a6b2f; color: #fff; }
    .act-assigned { border-color: #004085; color: #004085; }
    .act-assigned:hover { background: #004085; color: #fff; }
    .act-done     { border-color: #383d41; color: #383d41; }
    .act-done:hover     { background: #383d41; color: #fff; }
    .act-cancel   { border-color: var(--crimson); color: var(--crimson); }
    .act-cancel:hover   { background: var(--crimson); color: #fff; }
    .act-new      { border-color: #8a6d00; color: #8a6d00; }
    .act-new:hover      { background: #8a6d00; color: #fff; }

    /* ==================== ПОДВАЛ ==================== */
    footer.site-footer { background: var(--dark); color: var(--peach); margin-top: auto; display: none; }
    footer.site-footer.visible { display: block; }

    .footer-inner {
        max-width: 1180px; margin: 0 auto; padding: 40px 24px 20px;
        display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 28px;
    }
    .footer-inner h4 {
        color: var(--gold); font-size: 15px; margin-bottom: 12px;
        text-transform: uppercase; letter-spacing: .5px;
    }
    .footer-inner ul { list-style: none; padding: 0; margin: 0; }
    .footer-inner li { margin-bottom: 8px; font-size: 14px; opacity: .85; }
    .footer-inner a { cursor: pointer; transition: .2s; }
    .footer-inner a:hover { color: var(--gold); }
    .footer-social a { display: inline-flex; align-items: center; gap: 8px; }
    .footer-social a::before { content: "→"; opacity: .6; font-size: 12px; }
    .footer-bottom {
        border-top: 1px solid rgba(255,218,185,.2); text-align: center;
        padding: 16px; font-size: 13px; opacity: .75;
    }

    /* ==================== АДАПТИВ ==================== */
    @media (max-width: 860px) {
        .header-inner { padding: 14px 70px 14px 24px; }
        .logo { position: static; width: 160px; margin: 12px auto 0; }
        .hero { padding: 36px 24px; }
        .hero h1 { font-size: 28px; }
        .gear-btn { top: 12px; right: 12px; width: 42px; height: 42px; font-size: 20px; }
    }
</style>
</head>
<body>

<!-- ==================== ЛОГОТИП ==================== -->
<div class="logo" onclick="goHome()" title="На главную">
    <img src="logo.svg" alt="банкетам.нет" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
    <div class="logo-text" style="display:none;">банкетам<span>.нет</span></div>
</div>

<!-- ==================== ШЕСТЕРЁНКА ==================== -->
<div class="gear-btn" id="gearBtn" onclick="openAdmin()" title="Админ-панель">⚙️</div>

<!-- ==================== ШАПКА ==================== -->
<header class="site-header<?= $user ? ' visible' : '' ?>" id="siteHeader">
    <div class="header-inner">
        <nav class="main-nav" id="mainNav">
            <a data-page="home" class="active">Главная</a>
            <a data-page="halls">Залы</a>
            <a data-page="booking">Бронирование</a>
            <a data-page="cabinet">Личный кабинет</a>
        </nav>
        <div id="headerUser"></div>
    </div>
</header>

<!-- ==================== КОНТЕНТ ==================== -->
<main id="mainContent" style="display:<?= $user ? 'block' : 'none' ?>;">

    <!-- ГЛАВНАЯ -->
    <section class="page<?= $user ? ' active' : '' ?>" id="page-home">
        <div class="hero">
            <h1>Найдите идеальный зал для вашего банкета</h1>
            <p>Более 120 проверенных площадок в вашем городе: свадьбы, юбилеи, корпоративы и детские праздники. Бронируйте онлайн за пару минут — без звонков и ожидания.</p>
            <button class="btn" onclick="go('halls')">Смотреть залы</button>
        </div>

        <div class="features">
            <div class="feature">
                <h3>120+ залов</h3>
                <p>От уютных лофтов до больших банкетных залов на 300 гостей.</p>
            </div>
            <div class="feature">
                <h3>Мгновенная заявка</h3>
                <p>Оставьте заявку онлайн — менеджер подтвердит бронь в течение часа.</p>
            </div>
            <div class="feature">
                <h3>Честные цены</h3>
                <p>Никаких скрытых комиссий. Оплата напрямую площадке.</p>
            </div>
            <div class="feature">
                <h3>Реальные отзывы</h3>
                <p>Оценки и впечатления гостей, которые уже отметили событие.</p>
            </div>
        </div>
    </section>

    <!-- ЗАЛЫ -->
    <section class="page" id="page-halls">
        <h2 class="section-title">Банкетные залы</h2>
        <p class="section-sub">Выберите площадку и забронируйте дату прямо на сайте.</p>
        <div class="halls-grid" id="hallsGrid"></div>
    </section>

    <!-- БРОНИРОВАНИЕ -->
    <section class="page" id="page-booking">
        <h2 class="section-title" style="text-align:center;">Бронирование зала</h2>
        <p class="section-sub" style="text-align:center;">Заполните заявку — мы свяжемся с вами для подтверждения.</p>
        <div class="booking-card">
            <form id="bookingForm" onsubmit="return submitBooking(event)">
                <label class="field-label">Ваше имя</label>
                <input type="text" id="bkName" placeholder="Иван Иванов" required>

                <label class="field-label">Телефон</label>
                <input type="tel" id="bkPhone" placeholder="+7 (999) 123-45-67" required>

                <label class="field-label">Зал</label>
                <select id="bkHall" required></select>

                <label class="field-label">Дата банкета</label>
                <input type="date" id="bkDate" required>

                <label class="field-label">Количество гостей</label>
                <input type="number" id="bkGuests" min="1" max="500" value="30" required>

                <label class="field-label">Пожелания</label>
                <textarea id="bkComment" rows="3" placeholder="Живая музыка, детская зона, особое меню..."></textarea>

                <button type="submit">Отправить заявку</button>
                <div class="msg" id="bkMsg"></div>
            </form>
        </div>
    </section>

    <!-- ЛИЧНЫЙ КАБИНЕТ -->
    <section class="page" id="page-cabinet">
        <div class="cabinet-head">
            <div class="cabinet-avatar" id="cabAvatar">И</div>
            <div>
                <h2 id="cabName">Имя</h2>
                <p id="cabLogin">@login</p>
            </div>
            <button class="btn btn-outline btn-small" style="margin-left:auto;" onclick="logout()">Выйти</button>
        </div>

        <div class="stat-row">
            <div class="stat"><div class="num" id="statBookings">0</div><div class="lbl">Заявок</div></div>
            <div class="stat"><div class="num" id="statConfirmed">0</div><div class="lbl">Подтверждено</div></div>
            <div class="stat"><div class="num" id="statReviews">0</div><div class="lbl">Отзывов</div></div>
        </div>

        <div class="tabs-inner">
            <button class="active" onclick="switchCabinet('bookings', this)">Мои заявки</button>
            <button onclick="switchCabinet('reviews', this)">Мои отзывы</button>
            <button onclick="switchCabinet('newReview', this)">Оставить отзыв</button>
        </div>

        <div class="tab-panel active" id="panel-bookings">
            <div id="bookingsList"></div>
        </div>

        <div class="tab-panel" id="panel-reviews">
            <div id="reviewsList"></div>
        </div>

        <div class="tab-panel" id="panel-newReview">
            <div class="review-form">
                <h3>Оставить отзыв о зале</h3>
                <label class="field-label">Зал</label>
                <select id="rvHall"></select>

                <label class="field-label">Оценка</label>
                <div class="stars" id="starsInput">
                    <span data-v="1">☆</span>
                    <span data-v="2">☆</span>
                    <span data-v="3">☆</span>
                    <span data-v="4">☆</span>
                    <span data-v="5">☆</span>
                </div>

                <label class="field-label">Текст отзыва</label>
                <textarea id="rvText" rows="4" placeholder="Расскажите о площадке, обслуживании, атмосфере..."></textarea>

                <button class="btn" style="margin-top:14px;" onclick="submitReview()">Опубликовать отзыв</button>
                <div class="msg" id="rvMsg"></div>
            </div>
        </div>
    </section>

</main>

<!-- ==================== ЭКРАН АВТОРИЗАЦИИ ==================== -->
<div class="page<?= $user ? '' : ' active' ?>" id="page-auth">
    <div class="auth-card">

        <div class="auth-screen active" id="screen-login">
            <div class="auth-brand">
                <h2>Вход</h2>
                <p>Рады видеть вас снова на банкетам.нет</p>
            </div>
            <form onsubmit="return doLogin(event)">
                <input type="text" id="loginUser" placeholder="Логин" required>
                <input type="password" id="loginPass" placeholder="Пароль" required>
                <button type="submit">Войти</button>
                <div class="msg" id="loginMsg"></div>
            </form>
            <div class="auth-switch">
                Нет аккаунта? <a onclick="showAuthScreen('register')">Зарегистрироваться</a>
            </div>
        </div>

        <div class="auth-screen" id="screen-register">
            <div class="auth-brand">
                <h2>Регистрация</h2>
                <p>Создайте аккаунт, чтобы бронировать залы</p>
            </div>
            <form onsubmit="return doRegister(event)">
                <input type="text" id="regUser" placeholder="Логин" required>
                <input type="text" id="regName" placeholder="Ваше имя" required>
                <input type="password" id="regPass" placeholder="Пароль" required>
                <button type="submit">Создать аккаунт</button>
                <div class="msg" id="regMsg"></div>
            </form>
            <div class="auth-switch">
                Уже есть аккаунт? <a onclick="showAuthScreen('login')">Войти</a>
            </div>
        </div>

    </div>
</div>

<!-- ==================== АДМИН-ПАНЕЛЬ ==================== -->
<div class="page" id="page-admin">

    <!-- Экран входа -->
    <div class="admin-login-card" id="adminLoginCard">
        <h2>🔐 Админ-панель</h2>
        <p>Доступ только для администратора</p>
        <form onsubmit="return doAdminLogin(event)">
            <input type="text" id="adminUser" placeholder="Логин" required>
            <input type="password" id="adminPass" placeholder="Пароль" required>
            <button type="submit">Войти</button>
            <div class="msg" id="adminMsg"></div>
        </form>
        <div class="auth-switch">
            <a onclick="closeAdmin()">← Вернуться на сайт</a>
        </div>
    </div>

    <!-- Сам админ-панель -->
    <div class="admin-panel" id="adminPanel">
        <div class="admin-header">
            <div>
                <h2>⚙️ Админ-панель</h2>
                <div style="font-size:13px; opacity:.85;">Управление заявками банкетам.нет</div>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn btn-small" onclick="closeAdmin()">← На сайт</button>
                <button class="btn btn-small" style="background:transparent; border-color:var(--peach); color:var(--peach);" onclick="adminLogout()">Выйти из админки</button>
            </div>
        </div>

        <div class="admin-stats" id="adminStats"></div>

        <h3 style="color:var(--green); margin:20px 0 14px;">Все заявки</h3>
        <div id="adminBookingsList"></div>
    </div>

</div>

<!-- ==================== ПОДВАЛ ==================== -->
<footer class="site-footer<?= $user ? ' visible' : '' ?>" id="siteFooter">
    <div class="footer-inner">
        <div>
            <h4>банкетам.нет</h4>
            <ul>
                <li>Портал банкетных залов</li>
                <li>Бронирование онлайн 24/7</li>
                <li>120+ проверенных площадок</li>
            </ul>
        </div>
        <div>
            <h4>Разделы</h4>
            <ul>
                <li><a onclick="go('home')">Главная</a></li>
                <li><a onclick="go('halls')">Залы</a></li>
                <li><a onclick="go('booking')">Бронирование</a></li>
                <li><a onclick="go('cabinet')">Личный кабинет</a></li>
            </ul>
        </div>
        <div>
            <h4>Контакты</h4>
            <ul>
                <li>📞 +7 (800) 555-35-35</li>
                <li>✉️ hello@банкетам.нет</li>
                <li>🕘 Ежедневно 9:00 – 21:00</li>
            </ul>
        </div>
        <div class="footer-social">
            <h4>Мы в соцсетях</h4>
            <ul>
                <li><a href="https://t.me/yermak3" target="_blank" rel="noopener">Telegram</a></li>
                <li><a href="https://vk.ru/yermak3" target="_blank" rel="noopener">VK</a></li>
                <li><a href="https://max.ru/u/f9LHodD0cOJTSQKzHuC7faZGhciVkV5yKxoXcf3eEvFI1beVrFPY9S3tkEc" target="_blank" rel="noopener">Max</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        © 2025 банкетам.нет — все права защищены. Сделано с любовью к праздникам.
    </div>
</footer>

<script>
/* ============================================================
   ДАННЫЕ С СЕРВЕРА
   ============================================================ */
const HALLS = <?= json_encode(halls(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let STATE   = <?= json_encode($initialState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const STATUS_LABELS = {
    new:      ['Новая', 'new'],
    confirm:  ['Подтверждена', 'confirm'],
    assigned: ['Банкет назначен', 'assigned'],
    done:     ['Банкет завершён', 'done'],
    cancel:   ['Отменена', 'cancel']
};

function statusBadge(status) {
    const [label, cls] = STATUS_LABELS[status] || STATUS_LABELS.new;
    return `<span class="status ${cls}">${label}</span>`;
}

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => (
        {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]
    ));
}

/* ============================================================
   API-КЛИЕНТ
   ============================================================ */
async function api(action, data = {}) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));

    const res = await fetch('api.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
    });

    let json;
    try { json = await res.json(); }
    catch (e) { throw new Error('Сервер вернул некорректный ответ'); }

    if (!json.ok) throw new Error(json.error || 'Неизвестная ошибка');
    if (json.state) STATE = json.state;
    return json;
}

/* ============================================================
   ЭКРАНЫ АВТОРИЗАЦИИ
   ============================================================ */
function showAuthScreen(name) {
    document.querySelectorAll('.auth-screen').forEach(s => s.classList.remove('active'));
    document.getElementById('screen-' + name).classList.add('active');
    document.getElementById('loginMsg').textContent = '';
    document.getElementById('regMsg').textContent   = '';
}

/* ============================================================
   НАВИГАЦИЯ
   ============================================================ */
function go(page) {
    if (!STATE.user) { showAuthOnly(); return; }

    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const el = document.getElementById('page-' + page);
    if (el) el.classList.add('active');

    document.querySelectorAll('#mainNav a').forEach(a => {
        a.classList.toggle('active', a.dataset.page === page);
    });

    if (page === 'cabinet') refreshCabinet();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goHome() {
    if (adminOpened) { closeAdmin(); return; }
    if (STATE.user) go('home');
    else showAuthOnly();
}

document.querySelectorAll('#mainNav a').forEach(a => {
    a.addEventListener('click', () => go(a.dataset.page));
});

/* ============================================================
   ПОКАЗ / СКРЫТИЕ
   ============================================================ */
function showAuthOnly() {
    document.getElementById('siteHeader').classList.remove('visible');
    document.getElementById('mainContent').style.display = 'none';
    document.getElementById('siteFooter').classList.remove('visible');

    document.getElementById('page-auth').classList.add('active');
    document.getElementById('page-admin').classList.remove('active');
    document.querySelectorAll('.page').forEach(p => {
        if (p.id !== 'page-auth') p.classList.remove('active');
    });
}

function showSite() {
    document.getElementById('page-auth').classList.remove('active');
    document.getElementById('page-admin').classList.remove('active');
    document.getElementById('siteHeader').classList.add('visible');
    document.getElementById('mainContent').style.display = 'block';
    document.getElementById('siteFooter').classList.add('visible');
}

/* ============================================================
   АВТОРИЗАЦИЯ
   ============================================================ */
async function doRegister(e) {
    e.preventDefault();
    const msg = document.getElementById('regMsg');
    msg.textContent = '';

    try {
        await api('register', {
            login: document.getElementById('regUser').value.trim(),
            name:  document.getElementById('regName').value.trim(),
            pass:  document.getElementById('regPass').value
        });
        msg.textContent = 'Аккаунт создан! Добро пожаловать.';
        msg.className = 'msg ok';
        updateHeader();
        showSite();
        go('home');
    } catch (err) {
        msg.textContent = err.message;
        msg.className = 'msg error';
    }
    return false;
}

async function doLogin(e) {
    e.preventDefault();
    const msg = document.getElementById('loginMsg');
    msg.textContent = '';

    try {
        await api('login', {
            login: document.getElementById('loginUser').value.trim(),
            pass:  document.getElementById('loginPass').value
        });
        msg.textContent = 'Вход выполнен!';
        msg.className = 'msg ok';
        updateHeader();
        showSite();
        go('home');
    } catch (err) {
        msg.textContent = err.message;
        msg.className = 'msg error';
    }
    return false;
}

async function logout() {
    try { await api('logout'); } catch (e) {}
    updateHeader();
    showAuthOnly();
    showAuthScreen('login');
    document.getElementById('loginUser').value = '';
    document.getElementById('loginPass').value = '';
}

/* ============================================================
   ШАПКА
   ============================================================ */
function updateHeader() {
    const box = document.getElementById('headerUser');
    if (!STATE.user) { box.innerHTML = ''; return; }

    const u = STATE.user;
    const initial = (u.name || u.login).charAt(0).toUpperCase();
    box.innerHTML = `
        <div class="user-chip">
            <span>${esc(u.name || u.login)}</span>
            <div class="avatar">${esc(initial)}</div>
            <button class="link-btn" onclick="logout()">Выйти</button>
        </div>`;
}

/* ============================================================
   ЗАЛЫ
   ============================================================ */
function renderHalls() {
    const grid = document.getElementById('hallsGrid');
    grid.innerHTML = HALLS.map(h => `
        <div class="hall-card">
            <div class="hall-img">
                <div class="fallback">${h.icon}</div>
                <img src="${h.image}" alt="${esc(h.name)}" onerror="this.style.display='none';">
            </div>
            <div class="hall-body">
                <h3>${esc(h.name)}</h3>
                <div class="hall-meta">
                    <span>👥 до ${h.capacity} гостей</span>
                    <span>🏷️ ${esc(h.type)}</span>
                </div>
                <p style="color:var(--gray); font-size:14px; margin:0 0 8px;">${esc(h.desc)}</p>
                <div class="hall-price">${h.price.toLocaleString('ru-RU')} ₽ <small>/ вечер</small></div>
                <button class="btn btn-small" onclick="quickBook('${h.id}')">Забронировать</button>
            </div>
        </div>
    `).join('');
}

function fillHallSelects() {
    const options = HALLS.map(h => `<option value="${h.id}">${esc(h.name)} — до ${h.capacity} чел.</option>`).join('');
    document.getElementById('bkHall').innerHTML = options;
    document.getElementById('rvHall').innerHTML = options;
}

function quickBook(hallId) {
    if (!STATE.user) { showAuthOnly(); return; }
    go('booking');
    document.getElementById('bkHall').value = hallId;
}

/* ============================================================
   БРОНИРОВАНИЕ
   ============================================================ */
async function submitBooking(e) {
    e.preventDefault();
    if (!STATE.user) { showAuthOnly(); return false; }

    const msg = document.getElementById('bkMsg');
    msg.textContent = '';

    try {
        await api('booking.create', {
            name:    document.getElementById('bkName').value.trim(),
            phone:   document.getElementById('bkPhone').value.trim(),
            hallId:  document.getElementById('bkHall').value,
            date:    document.getElementById('bkDate').value,
            guests:  document.getElementById('bkGuests').value,
            comment: document.getElementById('bkComment').value.trim()
        });
        msg.textContent = 'Заявка отправлена! Менеджер свяжется с вами.';
        msg.className = 'msg ok';
        e.target.reset();
        setTimeout(() => { msg.textContent = ''; go('cabinet'); }, 1200);
    } catch (err) {
        msg.textContent = err.message;
        msg.className = 'msg error';
    }
    return false;
}

/* ============================================================
   ЛИЧНЫЙ КАБИНЕТ
   ============================================================ */
function switchCabinet(tab, btn) {
    document.querySelectorAll('#page-cabinet .tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('#page-cabinet .tabs-inner button').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    btn.classList.add('active');
}

function refreshCabinet() {
    if (!STATE.user) { showAuthOnly(); return; }
    const u = STATE.user;

    document.getElementById('cabName').textContent  = u.name || u.login;
    document.getElementById('cabLogin').textContent = '@' + u.login;
    document.getElementById('cabAvatar').textContent = (u.name || u.login).charAt(0).toUpperCase();

    const myBookings = STATE.bookings || [];
    const myReviews  = STATE.reviews  || [];

    document.getElementById('statBookings').textContent  = myBookings.length;
    document.getElementById('statConfirmed').textContent = myBookings.filter(b => b.status === 'confirm').length;
    document.getElementById('statReviews').textContent   = myReviews.length;

    renderBookings(myBookings);
    renderReviews(myReviews);
}

function renderBookings(list) {
    const box = document.getElementById('bookingsList');
    if (!list.length) {
        box.innerHTML = `<div class="empty"><div class="ico">📋</div><b>Заявок пока нет</b><p>Забронируйте зал — заявка появится здесь.</p><button class="btn btn-small" onclick="go('halls')">Выбрать зал</button></div>`;
        return;
    }
    box.innerHTML = list.slice().reverse().map(b => `
        <div class="booking-item">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <h4>${esc(b.hallName)}</h4>
                ${statusBadge(b.status)}
            </div>
            <div class="booking-meta">
                <span>📅 <b>${esc(b.date || '—')}</b></span>
                <span>👥 <b>${esc(b.guests)}</b> гостей</span>
                <span>📞 <b>${esc(b.phone)}</b></span>
            </div>
            ${b.comment ? `<p style="margin:6px 0 0; color:var(--gray); font-size:14px;">💬 ${esc(b.comment)}</p>` : ''}
            <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
                ${b.status === 'new' ? `<button class="btn btn-outline btn-small" onclick="cancelBooking(${b.id})">Отменить</button>` : ''}
                <button class="btn btn-small" onclick="go('booking')">Новая заявка</button>
            </div>
        </div>
    `).join('');
}

async function cancelBooking(id) {
    if (!confirm('Отменить заявку?')) return;
    try {
        await api('booking.cancel', { id });
        refreshCabinet();
    } catch (err) {
        alert(err.message);
    }
}

/* ============================================================
   ОТЗЫВЫ
   ============================================================ */
let currentRating = 0;

function initStars() {
    const stars = document.querySelectorAll('#starsInput span');
    stars.forEach(s => {
        s.addEventListener('click', () => {
            currentRating = +s.dataset.v;
            stars.forEach(x => x.textContent = +x.dataset.v <= currentRating ? '★' : '☆');
        });
        s.addEventListener('mouseenter', () => {
            stars.forEach(x => x.textContent = +x.dataset.v <= +s.dataset.v ? '★' : '☆');
        });
    });
    document.getElementById('starsInput').addEventListener('mouseleave', () => {
        stars.forEach(x => x.textContent = +x.dataset.v <= currentRating ? '★' : '☆');
    });
}

async function submitReview() {
    if (!STATE.user) { showAuthOnly(); return; }
    const msg = document.getElementById('rvMsg');
    msg.textContent = '';

    try {
        await api('review.create', {
            hallId: document.getElementById('rvHall').value,
            rating: currentRating,
            text:   document.getElementById('rvText').value.trim()
        });

        msg.textContent = 'Спасибо! Отзыв опубликован.';
        msg.className = 'msg ok';

        document.getElementById('rvText').value = '';
        currentRating = 0;
        document.querySelectorAll('#starsInput span').forEach(x => x.textContent = '☆');

        setTimeout(() => {
            msg.textContent = '';
            const btns = document.querySelectorAll('#page-cabinet .tabs-inner button');
            switchCabinet('reviews', btns[1]);
            refreshCabinet();
        }, 900);
    } catch (err) {
        msg.textContent = err.message;
        msg.className = 'msg error';
    }
}

function renderReviews(list) {
    const box = document.getElementById('reviewsList');
    if (!list.length) {
        box.innerHTML = `<div class="empty"><div class="ico">⭐</div><b>Отзывов пока нет</b><p>Поделитесь впечатлениями о зале — это поможет другим гостям.</p><button class="btn btn-small" onclick="switchCabinet('newReview', document.querySelectorAll('#page-cabinet .tabs-inner button')[2])">Оставить отзыв</button></div>`;
        return;
    }
    box.innerHTML = list.slice().reverse().map(r => `
        <div class="review-item">
            <div class="review-top">
                <b>${esc(r.hallName)}</b>
                <span class="review-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</span>
            </div>
            <p class="review-text">${esc(r.text)}</p>
            <div class="review-date">${esc(r.date)}</div>
        </div>
    `).join('');
}

/* ============================================================
   АДМИН-ПАНЕЛЬ
   ============================================================ */
let adminOpened = false;

function openAdmin() {
    adminOpened = true;

    document.getElementById('siteHeader').classList.remove('visible');
    document.getElementById('mainContent').style.display = 'none';
    document.getElementById('siteFooter').classList.remove('visible');
    document.getElementById('page-auth').classList.remove('active');

    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById('page-admin').classList.add('active');
    document.getElementById('gearBtn').classList.add('active');

    if (!STATE.admin) {
        document.getElementById('adminLoginCard').style.display = 'block';
        document.getElementById('adminPanel').classList.remove('active');
        document.getElementById('adminUser').value = '';
        document.getElementById('adminPass').value = '';
        document.getElementById('adminMsg').textContent = '';
    } else {
        document.getElementById('adminLoginCard').style.display = 'none';
        document.getElementById('adminPanel').classList.add('active');
        refreshAdminPanel();
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function closeAdmin() {
    adminOpened = false;
    document.getElementById('gearBtn').classList.remove('active');

    if (STATE.user) {
        showSite();
        go('home');
    } else {
        showAuthOnly();
        showAuthScreen('login');
    }
}

async function doAdminLogin(e) {
    e.preventDefault();
    const msg = document.getElementById('adminMsg');
    msg.textContent = '';

    try {
        await api('admin.login', {
            login: document.getElementById('adminUser').value.trim(),
            pass:  document.getElementById('adminPass').value
        });
        STATE.admin = true;
        msg.textContent = 'Доступ разрешён!';
        msg.className = 'msg ok';
        document.getElementById('adminLoginCard').style.display = 'none';
        document.getElementById('adminPanel').classList.add('active');
        refreshAdminPanel();
    } catch (err) {
        msg.textContent = err.message;
        msg.className = 'msg error';
    }
    return false;
}

async function adminLogout() {
    if (!confirm('Выйти из админ-панели?')) return;
    try { await api('admin.logout'); } catch (e) {}
    STATE.admin = false;
    closeAdmin();
}

async function refreshAdminPanel() {
    let data;
    try {
        data = await api('admin.data');
    } catch (err) {
        document.getElementById('adminBookingsList').innerHTML =
            `<div class="empty"><div class="ico">⛔</div><b>${esc(err.message)}</b></div>`;
        return;
    }

    const s = data.stats || {};
    document.getElementById('adminStats').innerHTML = `
        <div class="admin-stat"><div class="num">${s.total    || 0}</div><div class="lbl">Всего заявок</div></div>
        <div class="admin-stat"><div class="num">${s.new      || 0}</div><div class="lbl">Новых</div></div>
        <div class="admin-stat"><div class="num">${s.confirm  || 0}</div><div class="lbl">Подтверждено</div></div>
        <div class="admin-stat"><div class="num">${s.assigned || 0}</div><div class="lbl">Назначено</div></div>
        <div class="admin-stat"><div class="num">${s.done     || 0}</div><div class="lbl">Завершено</div></div>
    `;

    const box = document.getElementById('adminBookingsList');
    const list = data.bookings || [];

    if (!list.length) {
        box.innerHTML = `<div class="empty"><div class="ico">📋</div><b>Заявок пока нет</b><p>Как только клиенты начнут бронировать — заявки появятся здесь.</p></div>`;
        return;
    }

    box.innerHTML = list.map(b => `
        <div class="admin-booking">
            <div class="admin-booking-top">
                <h4>${esc(b.hallName)}</h4>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    ${statusBadge(b.status)}
                    <span class="admin-booking-user">👤 ${esc(b.user)}</span>
                </div>
            </div>
            <div class="admin-booking-meta">
                <span>🙋 <b>${esc(b.name || '—')}</b></span>
                <span>📞 <b>${esc(b.phone)}</b></span>
                <span>📅 <b>${esc(b.date || '—')}</b></span>
                <span>👥 <b>${esc(b.guests)}</b> гостей</span>
            </div>
            ${b.comment ? `<p style="margin:6px 0 0; color:var(--gray); font-size:14px;">💬 ${esc(b.comment)}</p>` : ''}
            <div class="admin-actions">
                <button class="act-new"      onclick="setStatus(${b.id}, 'new')">Новая</button>
                <button class="act-confirm"  onclick="setStatus(${b.id}, 'confirm')">Подтвердить</button>
                <button class="act-assigned" onclick="setStatus(${b.id}, 'assigned')">Банкет назначен</button>
                <button class="act-done"     onclick="setStatus(${b.id}, 'done')">Банкет завершён</button>
                <button class="act-cancel"   onclick="setStatus(${b.id}, 'cancel')">Отменить</button>
            </div>
        </div>
    `).join('');
}

async function setStatus(id, status) {
    try {
        await api('admin.setStatus', { id, status });
        refreshAdminPanel();
    } catch (err) {
        alert(err.message);
    }
}

/* ============================================================
   ИНИЦИАЛИЗАЦИЯ
   ============================================================ */
(function init() {
    renderHalls();
    fillHallSelects();
    initStars();
    updateHeader();

    if (STATE.user) {
        showSite();
        go('home');
    } else {
        showAuthOnly();
        showAuthScreen('login');
    }
})();
</script>
</body>
</html>