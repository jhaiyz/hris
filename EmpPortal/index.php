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
    <link rel="icon" type="image/png" href="icon.png">
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
            No employee number? Visit <strong>HR office</strong>. Already have one? Click Register.
            <button class="btn-register-inline" onclick="openRegModal()">Register</button>
        </p>
    </div>

    <div class="alert alert-error" id="alertBox"></div>

    <div class="field">
        <label>Nickname</label>
        <input type="text" id="fullName" placeholder="Enter your nickname" autocomplete="nickname">
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

<?php include 'api/register-modal.php'; ?>

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

    if (!fullName || !password) { showAlert('Please enter your nickname and password.'); return; }

    btn.disabled = true;
    spinner.style.display = 'inline-block';

    try {
        const res  = await fetch('api/login.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ nick_name: fullName, password })
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
</script>
</body>
</html>