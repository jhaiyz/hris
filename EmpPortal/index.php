<?php
require_once 'db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: portal.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS — Employee Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #0f1b2d;
            --navy2:   #162338;
            --teal:    #0ea5a0;
            --teal2:   #06d6a0;
            --gold:    #f5c842;
            --light:   #e8f0fe;
            --white:   #ffffff;
            --gray:    #8a9ab5;
            --err:     #ff6b6b;
            --radius:  14px;
            --shadow:  0 24px 64px rgba(0,0,0,.45);
        }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--navy);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            opacity: .18;
            pointer-events: none;
        }
        body::before {
            width: 600px; height: 600px;
            background: var(--teal);
            top: -150px; left: -150px;
            animation: drift1 12s ease-in-out infinite alternate;
        }
        body::after {
            width: 500px; height: 500px;
            background: var(--gold);
            bottom: -120px; right: -120px;
            animation: drift2 15s ease-in-out infinite alternate;
        }

        @keyframes drift1 { to { transform: translate(80px, 60px) scale(1.1); } }
        @keyframes drift2 { to { transform: translate(-60px, -80px) scale(1.05); } }

        .grid-bg {
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(14,165,160,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(14,165,160,.06) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        /* Login Card */
        .card {
            position: relative;
            width: min(420px, 92vw);
            background: rgba(22, 35, 56, .82);
            border: 1px solid rgba(14,165,160,.22);
            border-radius: 24px;
            padding: 48px 42px 44px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(24px);
            animation: slideUp .6s cubic-bezier(.22,1,.36,1) both;
        }

        @keyframes slideUp {
            from { opacity:0; transform: translateY(32px); }
            to   { opacity:1; transform: translateY(0); }
        }

        .logo-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--teal), var(--teal2));
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 32px rgba(14,165,160,.4);
        }

        .logo-icon svg { width: 34px; height: 34px; fill: white; }

        h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.7rem;
            color: var(--white);
            letter-spacing: -.01em;
            text-align: center;
        }

        .subtitle {
            font-size: .8rem;
            color: var(--gray);
            letter-spacing: .12em;
            text-transform: uppercase;
            margin-top: 4px;
            text-align: center;
        }

        /* Notice Banner */
        .notice-banner {
            background: rgba(245,200,66,.08);
            border: 1px solid rgba(245,200,66,.28);
            border-radius: 12px;
            padding: 13px 16px;
            margin-bottom: 22px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .notice-banner svg {
            flex-shrink: 0;
            width: 18px; height: 18px;
            fill: var(--gold);
            margin-top: 1px;
        }

        .notice-banner p {
            font-size: .78rem;
            color: rgba(245,200,66,.85);
            line-height: 1.55;
        }

        .notice-banner p .btn-register-inline {
            background: none;
            border: none;
            color: var(--gold);
            font-family: 'Sora', sans-serif;
            font-size: .78rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: underline;
            text-underline-offset: 2px;
            padding: 0;
            transition: opacity .2s;
        }
        .notice-banner p .btn-register-inline:hover { opacity: .75; }

        /* Form */
        .field { margin-bottom: 20px; }

        label {
            display: block;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--gray);
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,.05);
            border: 1.5px solid rgba(138,154,181,.22);
            border-radius: var(--radius);
            color: var(--white);
            font-family: 'Sora', sans-serif;
            font-size: .95rem;
            outline: none;
            transition: border-color .25s, box-shadow .25s;
        }

        input:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(14,165,160,.18);
        }

        input::placeholder { color: rgba(138,154,181,.5); }

        .pw-wrap { position: relative; }
        .pw-toggle {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--gray); padding: 4px;
            transition: color .2s;
        }
        .pw-toggle:hover { color: var(--teal); }
        .pw-toggle svg { width: 18px; height: 18px; }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--teal), #09bfb9);
            border: none;
            border-radius: var(--radius);
            color: white;
            font-family: 'Sora', sans-serif;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: transform .18s, box-shadow .18s, opacity .18s;
            box-shadow: 0 6px 24px rgba(14,165,160,.35);
            letter-spacing: .03em;
        }

        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(14,165,160,.45); }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: .85rem;
            margin-bottom: 20px;
            display: none;
            animation: fadeIn .3s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform: translateY(-6px); } to { opacity:1; transform:none; } }

        .alert-error   { background: rgba(255,107,107,.12); border: 1px solid rgba(255,107,107,.35); color: #ff9a9a; }
        .alert-success { background: rgba(6,214,160,.1);   border: 1px solid rgba(6,214,160,.35);  color: #06d6a0; }

        .footer-note {
            text-align: center;
            font-size: .75rem;
            color: rgba(138,154,181,.5);
            margin-top: 28px;
        }

        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
            display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 480px) {
            .card { padding: 36px 26px 32px; }
            h1 { font-size: 1.5rem; }
        }

        /* ===================== REGISTRATION MODAL ===================== */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(5,12,25,.75);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 24px 16px 40px;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s ease;
            overflow-y: auto;
        }
        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal {
            position: relative;
            width: min(760px, 100%);
            background: #111e30;
            border: 1px solid rgba(14,165,160,.28);
            border-radius: 24px;
            box-shadow: 0 32px 80px rgba(0,0,0,.6);
            overflow: hidden;
            transform: translateY(24px) scale(.97);
            transition: transform .35s cubic-bezier(.22,1,.36,1);
            margin: auto;
        }
        .modal-overlay.open .modal {
            transform: translateY(0) scale(1);
        }

        /* Modal header */
        .modal-header {
            background: linear-gradient(135deg, rgba(14,165,160,.15), rgba(6,214,160,.08));
            border-bottom: 1px solid rgba(14,165,160,.2);
            padding: 28px 36px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .modal-header-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--teal), var(--teal2));
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(14,165,160,.35);
        }
        .modal-header-icon svg { width: 26px; height: 26px; fill: white; }

        .modal-header-text h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.45rem;
            color: var(--white);
            line-height: 1.2;
        }
        .modal-header-text p {
            font-size: .78rem;
            color: var(--gray);
            margin-top: 3px;
            letter-spacing: .06em;
        }

        .modal-close {
            position: absolute;
            top: 20px; right: 22px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 8px;
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            color: var(--gray);
            transition: background .2s, color .2s;
        }
        .modal-close:hover { background: rgba(255,107,107,.15); color: #ff9a9a; }
        .modal-close svg { width: 16px; height: 16px; }

        /* Modal body */
        .modal-body {
            padding: 30px 36px 36px;
        }

        /* Section dividers */
        .section-title {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--teal);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(14,165,160,.15);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title svg { width: 14px; height: 14px; fill: var(--teal); }

        /* Form grid */
        .form-grid {
            display: grid;
            gap: 16px;
            margin-bottom: 24px;
        }
        .form-grid.cols-2 { grid-template-columns: 1fr 1fr; }
        .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        .form-grid.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
        .form-grid.cols-1-2 { grid-template-columns: 1fr 2fr; }
        .span-2 { grid-column: span 2; }
        .span-3 { grid-column: span 3; }
        .span-4 { grid-column: span 4; }

        .reg-field label {
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--gray);
            margin-bottom: 7px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .reg-field label .optional-tag {
            font-size: .62rem;
            font-weight: 400;
            letter-spacing: .06em;
            color: rgba(138,154,181,.5);
            background: rgba(138,154,181,.08);
            border: 1px solid rgba(138,154,181,.15);
            border-radius: 4px;
            padding: 1px 5px;
            text-transform: none;
        }

        .reg-field input[type="text"],
        .reg-field input[type="email"],
        .reg-field input[type="date"],
        .reg-field select {
            width: 100%;
            padding: 11px 14px;
            background: rgba(255,255,255,.04);
            border: 1.5px solid rgba(138,154,181,.18);
            border-radius: 10px;
            color: var(--white);
            font-family: 'Sora', sans-serif;
            font-size: .88rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            appearance: none;
        }

        .reg-field input:focus,
        .reg-field select:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(14,165,160,.14);
        }

        .reg-field input::placeholder { color: rgba(138,154,181,.4); font-size: .83rem; }

        .reg-field select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='%238a9ab5'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
            cursor: pointer;
        }

        .reg-field select option {
            background: #162338;
            color: var(--white);
        }

        /* Date input */
        .reg-field input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(.5) sepia(1) saturate(2) hue-rotate(140deg);
            cursor: pointer;
            opacity: .6;
        }

        /* Alert inside modal */
        .reg-alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: .83rem;
            margin-bottom: 18px;
            display: none;
            line-height: 1.5;
            animation: fadeIn .3s ease;
        }
        .reg-alert.error   { background: rgba(255,107,107,.1);  border: 1px solid rgba(255,107,107,.3);  color: #ff9a9a; display: block; }
        .reg-alert.success { background: rgba(6,214,160,.09);   border: 1px solid rgba(6,214,160,.3);   color: #06d6a0; display: block; }

        /* Modal footer */
        .modal-footer {
            padding: 20px 36px 30px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            border-top: 1px solid rgba(14,165,160,.1);
        }

        .btn-cancel {
            padding: 12px 24px;
            background: rgba(255,255,255,.05);
            border: 1.5px solid rgba(138,154,181,.2);
            border-radius: 10px;
            color: var(--gray);
            font-family: 'Sora', sans-serif;
            font-size: .88rem;
            font-weight: 500;
            cursor: pointer;
            transition: background .2s, color .2s;
        }
        .btn-cancel:hover { background: rgba(255,255,255,.09); color: var(--white); }

        .btn-save {
            padding: 12px 28px;
            background: linear-gradient(135deg, var(--teal), #09bfb9);
            border: none;
            border-radius: 10px;
            color: white;
            font-family: 'Sora', sans-serif;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform .18s, box-shadow .18s, opacity .18s;
            box-shadow: 0 6px 20px rgba(14,165,160,.35);
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(14,165,160,.45); }
        .btn-save:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .btn-save svg { width: 16px; height: 16px; fill: white; }

        .btn-save .btn-spinner {
            width: 15px; height: 15px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
        }

        /* ===================== SUCCESS POPUP ===================== */
        .success-popup-overlay {
            position: fixed; inset: 0;
            background: rgba(5,12,25,.82);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s ease;
        }
        .success-popup-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .success-popup {
            background: #111e30;
            border: 1px solid rgba(14,165,160,.35);
            border-radius: 22px;
            padding: 48px 44px 40px;
            width: min(380px, 90vw);
            text-align: center;
            box-shadow: 0 32px 80px rgba(0,0,0,.6);
            transform: scale(.88);
            transition: transform .35s cubic-bezier(.22,1,.36,1);
        }
        .success-popup-overlay.open .success-popup {
            transform: scale(1);
        }

        .success-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, var(--teal), var(--teal2));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 30px rgba(14,165,160,.4);
            animation: popIn .5s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes popIn { from { transform: scale(.4); opacity:0; } to { transform: scale(1); opacity:1; } }
        .success-icon svg { width: 36px; height: 36px; fill: white; }

        .success-popup h3 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.4rem;
            color: var(--white);
            margin-bottom: 10px;
        }

        .success-popup p {
            font-size: .85rem;
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 6px;
        }

        .password-badge {
            display: inline-block;
            background: rgba(245,200,66,.1);
            border: 1.5px solid rgba(245,200,66,.35);
            border-radius: 10px;
            padding: 10px 24px;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: .15em;
            margin: 16px 0 20px;
            font-family: 'Sora', sans-serif;
        }

        .success-popup .note {
            font-size: .75rem;
            color: rgba(138,154,181,.6);
            margin-bottom: 24px;
            line-height: 1.55;
        }

        .btn-got-it {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--teal), #09bfb9);
            border: none;
            border-radius: 10px;
            color: white;
            font-family: 'Sora', sans-serif;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform .18s, box-shadow .18s;
            box-shadow: 0 6px 20px rgba(14,165,160,.35);
        }
        .btn-got-it:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(14,165,160,.45); }

        @media (max-width: 640px) {
            .modal-header, .modal-body, .modal-footer { padding-left: 22px; padding-right: 22px; }
            .form-grid.cols-2,
            .form-grid.cols-3,
            .form-grid.cols-4 { grid-template-columns: 1fr 1fr; }
            .form-grid.cols-4 .span-4 { grid-column: span 2; }
            .form-grid.cols-3 .span-3 { grid-column: span 2; }
        }
        @media (max-width: 480px) {
            .form-grid.cols-2,
            .form-grid.cols-3,
            .form-grid.cols-4 { grid-template-columns: 1fr; }
            .span-2, .span-3, .span-4 { grid-column: span 1; }
        }
    </style>
</head>
<body>
<div class="grid-bg"></div>

<!-- ===== LOGIN CARD ===== -->
<div class="card">
    <div class="logo-wrap">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg>
        </div>
        <h1>Employee Portal</h1>
        <p class="subtitle">Human Resource Information System</p>
    </div>

    <!-- Notice Banner -->
    <div class="notice-banner">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        <p>
            If you do not yet have an employee number, you may proceed to the <strong>HR office</strong> for registration.
            However, if you already have one, please click the
            <button class="btn-register-inline" onclick="openRegModal()">Register</button>
            button to continue your registration.
        </p>
    </div>

    <div class="alert alert-error" id="alertBox"></div>

    <div class="field">
        <label>Full Name</label>
        <input type="text" id="fullName" placeholder="Enter your full name" autocomplete="name">
    </div>

    <div class="field">
        <label>Password</label>
        <div class="pw-wrap">
            <input type="password" id="password" placeholder="Enter your password" autocomplete="current-password">
            <button class="pw-toggle" type="button" onclick="togglePw()" title="Show/hide password">
                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>
    </div>

    <button class="btn-login" id="loginBtn" onclick="doLogin()">
        <span class="spinner" id="spinner"></span>
        Sign In
    </button>

    <p class="footer-note">© <?= date('Y') ?> HRIS &mdash; All rights reserved (JHAIYZSOFT)</p>
</div>

<!-- ===== REGISTRATION MODAL ===== -->
<div class="modal-overlay" id="regOverlay" onclick="overlayClick(event)">
    <div class="modal" id="regModal">

        <div class="modal-header">
            <div class="modal-header-icon">
                <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            <div class="modal-header-text">
                <h2>Employee Registration</h2>
                <p>Complete all required fields to create your account</p>
            </div>
            <button class="modal-close" onclick="closeRegModal()" title="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="modal-body">
            <div class="reg-alert" id="regAlert"></div>

            <!-- Personal Information -->
            <div class="section-title">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg>
                Personal Information
            </div>

            <!-- Row: Employee No + Nickname -->
            <div class="form-grid cols-2" style="margin-bottom:16px;">
                <div class="reg-field">
                    <label>Employee No.</label>
                    <input type="text" id="reg_emp_no" placeholder="e.g. 2024-001">
                </div>
                <div class="reg-field">
                    <label>Nickname</label>
                    <input type="text" id="reg_nickname" placeholder="e.g. Juan">
                </div>
            </div>

            <!-- Row: First, Middle, Last, Ext -->
            <div class="form-grid cols-4" style="margin-bottom:16px;">
                <div class="reg-field span-2">
                    <label>First Name</label>
                    <input type="text" id="reg_first" placeholder="First name">
                </div>
                <div class="reg-field">
                    <label>Middle Name</label>
                    <input type="text" id="reg_middle" placeholder="Middle name">
                </div>
                <div class="reg-field">
                    <label>Last Name</label>
                    <input type="text" id="reg_last" placeholder="Last name">
                </div>
            </div>

            <!-- Row: Ext Name + Birthday -->
            <div class="form-grid cols-2" style="margin-bottom:24px;">
                <div class="reg-field">
                    <label>Extension Name <span class="optional-tag">optional</span></label>
                    <input type="text" id="reg_ext" placeholder="e.g. JR, SR, III">
                </div>
                <div class="reg-field">
                    <label>Birthday</label>
                    <input type="date" id="reg_birthday">
                </div>
            </div>

            <!-- Employment Details -->
            <div class="section-title">
                <svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
                Employment Details
            </div>

            <div class="form-grid cols-2" style="margin-bottom:16px;">
                <div class="reg-field">
                    <label>Office</label>
                    <select id="reg_office">
                        <option value="">— Select Office —</option>
                        <?php
                            $db = getDB();
                            $res = $db->query("SELECT Office FROM tbloffice ORDER BY Office");
                            if ($res) {
                                while ($row = $res->fetch_assoc()) {
                                    echo '<option value="'.htmlspecialchars($row['Office']).'">'.htmlspecialchars($row['Office']).'</option>';
                                }
                            }
                            $db->close();
                        ?>
                    </select>
                </div>
                <div class="reg-field">
                    <label>Employment Status</label>
                    <select id="reg_emp_status">
                        <option value="">— Select Status —</option>
                        <option value="PERMANENT">PERMANENT</option>
                        <option value="JOB ORDER">JOB ORDER</option>
                        <option value="CONTRACT OF SERVICE">CONTRACT OF SERVICE</option>
                        <option value="CONTRACTUAL">CONTRACTUAL</option>
                    </select>
                </div>
            </div>

            <div class="form-grid cols-1" style="margin-bottom:24px;">
                <div class="reg-field">
                    <label>Position</label>
                    <input type="text" id="reg_position" placeholder="Do not abbreviate">
                </div>
            </div>

            <!-- Contact Information -->
            <div class="section-title">
                <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                Contact Information
            </div>

            <div class="form-grid cols-2" style="margin-bottom:16px;">
                <div class="reg-field">
                    <label>Mobile No. <span class="optional-tag">optional</span></label>
                    <input type="text" id="reg_mobile" placeholder="e.g. 09XXXXXXXXX">
                </div>
                <div class="reg-field">
                    <label>Email Address</label>
                    <input type="email" id="reg_email" placeholder="you@example.com">
                </div>
            </div>

            <div class="form-grid cols-1" style="margin-bottom:24px;">
                <div class="reg-field">
                    <label>Emergency Contact No. <span class="optional-tag">optional</span></label>
                    <input type="text" id="reg_emergency" placeholder="e.g. 09XXXXXXXXX">
                </div>
            </div>

            <!-- Professional Credentials -->
            <div class="section-title">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                Professional Credentials <span style="font-size:.65rem; font-weight:400; letter-spacing:.05em; color:rgba(138,154,181,.5); text-transform:none; margin-left:4px;">(optional)</span>
            </div>

            <div class="form-grid cols-2">
                <div class="reg-field">
                    <label>PRC No. <span class="optional-tag">optional</span></label>
                    <input type="text" id="reg_prc" placeholder="PRC License No.">
                </div>
                <div class="reg-field">
                    <label>PhilHealth Accreditation <span class="optional-tag">optional</span></label>
                    <input type="text" id="reg_ph_accred" placeholder="PH Accreditation No.">
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeRegModal()">Cancel</button>
            <button class="btn-save" id="saveBtn" onclick="doRegister()">
                <span class="btn-spinner" id="saveSpinner"></span>
                <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                Save Registration
            </button>
        </div>
    </div>
</div>


<!-- ===== SUCCESS POPUP ===== -->
<div class="success-popup-overlay" id="successOverlay">
    <div class="success-popup">
        <div class="success-icon">
            <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <h3>Registration Successful!</h3>
        <p>Your account has been created. Your default password is:</p>
        <div class="password-badge">123456</div>
        <p class="note">Please use this password to log in. You will be prompted to change it upon your first sign-in.</p>
        <button class="btn-got-it" onclick="closeSuccessPopup()">Got it, proceed to login</button>
    </div>
</div>


<script>
/* ========== LOGIN ========== */
function togglePw() {
    const pw = document.getElementById('password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
}

function showAlert(msg, type = 'error') {
    const box = document.getElementById('alertBox');
    box.textContent = msg;
    box.className = 'alert alert-' + type;
    box.style.display = 'block';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !document.getElementById('regOverlay').classList.contains('open')) doLogin();
});

async function doLogin() {
    const fullName = document.getElementById('fullName').value.trim();
    const password = document.getElementById('password').value;
    const btn      = document.getElementById('loginBtn');
    const spinner  = document.getElementById('spinner');

    if (!fullName || !password) { showAlert('Please enter your full name and password.'); return; }

    btn.disabled = true;
    spinner.style.display = 'inline-block';

    try {
        const res  = await fetch('api/login.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ full_name: fullName, password })
        });
        const data = await res.json();

        if (data.success) {
            window.location.href = data.mustChangePassword ? 'change-password.php' : 'portal.php';
        } else {
            showAlert(data.message || 'Invalid credentials. Please try again.');
        }
    } catch (err) {
        showAlert('Server error. Please try again.');
    } finally {
        btn.disabled = false;
        spinner.style.display = 'none';
    }
}

/* ========== REGISTRATION MODAL ========== */
function openRegModal() {
    document.getElementById('regOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    clearRegForm();
}

function closeRegModal() {
    document.getElementById('regOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

function overlayClick(e) {
    if (e.target === document.getElementById('regOverlay')) closeRegModal();
}

function clearRegForm() {
    ['reg_emp_no','reg_nickname','reg_first','reg_middle','reg_last','reg_ext',
     'reg_birthday','reg_office','reg_emp_status','reg_position',
     'reg_mobile','reg_email','reg_emergency','reg_prc','reg_ph_accred'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const a = document.getElementById('regAlert');
    a.className = 'reg-alert';
    a.textContent = '';
}

function showRegAlert(msg, type = 'error') {
    const a = document.getElementById('regAlert');
    a.textContent = msg;
    a.className = 'reg-alert ' + type;
    a.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function doRegister() {
    const g = id => document.getElementById(id).value.trim();

    const empNo       = g('reg_emp_no');
    const nickname    = g('reg_nickname');
    const firstName   = g('reg_first');
    const middleName  = g('reg_middle');
    const lastName    = g('reg_last');
    const extName     = g('reg_ext');
    const birthday    = g('reg_birthday');
    const office      = g('reg_office');
    const empStatus   = g('reg_emp_status');
    const position    = g('reg_position');
    const mobileNo    = g('reg_mobile');
    const email       = g('reg_email');
    const emergency   = g('reg_emergency');
    const prcNo       = g('reg_prc');
    const phAccred    = g('reg_ph_accred');

    // Required field validation
    const required = [
        [empNo,     'Employee No.'],
        [nickname,  'Nickname'],
        [firstName, 'First Name'],
        [lastName,  'Last Name'],
        [birthday,  'Birthday'],
        [office,    'Office'],
        [empStatus, 'Employment Status'],
        [position,  'Position'],
        [email,     'Email Address'],
    ];

    for (const [val, label] of required) {
        if (!val) { showRegAlert(`⚠ ${label} is required.`); return; }
    }

    // Build Full_Name
    const fullName = lastName + ', ' + firstName + (extName ? ' ' + extName : '');

    const btn     = document.getElementById('saveBtn');
    const spinner = document.getElementById('saveSpinner');
    btn.disabled  = true;
    spinner.style.display = 'inline-block';

    try {
        const res  = await fetch('api/register.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                Employee_No:       empNo,
                Nick_Name:         nickname,
                First_Name:        firstName,
                Middle_Name:       middleName,
                Last_Name:         lastName,
                Ext_Name:          extName,
                Full_Name:         fullName,
                Birthday:          birthday,
                Office:            office,
                Employment_Status: empStatus,
                Position:          position,
                Mobile_No:         mobileNo,
                Email:             email,
                CP_Emergency:      emergency,
                PRC_No:            prcNo,
                PH_Accred:         phAccred
            })
        });
        const data = await res.json();

        if (data.success) {
            closeRegModal();
            document.getElementById('successOverlay').classList.add('open');
        } else {
            showRegAlert('⚠ ' + (data.message || 'Registration failed. Please try again.'));
        }
    } catch (err) {
        showRegAlert('⚠ Server error. Please try again.');
    } finally {
        btn.disabled = false;
        spinner.style.display = 'none';
    }
}

/* ========== SUCCESS POPUP ========== */
function closeSuccessPopup() {
    document.getElementById('successOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
</script>
</body>
</html>