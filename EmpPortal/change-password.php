<?php
require_once 'db.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS — Change Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy: #0f1b2d; --navy2: #162338; --teal: #0ea5a0; --teal2: #06d6a0;
            --gold: #f5c842; --white: #ffffff; --gray: #8a9ab5; --err: #ff6b6b;
        }
        body {
            font-family: 'Sora', sans-serif;
            background: var(--navy);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        body::before, body::after {
            content: ''; position: fixed; border-radius: 50%;
            filter: blur(90px); opacity: .15; pointer-events: none;
        }
        body::before { width:500px;height:500px;background:var(--teal);top:-100px;left:-100px;animation:d1 12s ease-in-out infinite alternate; }
        body::after  { width:400px;height:400px;background:var(--gold);bottom:-80px;right:-80px;animation:d2 15s ease-in-out infinite alternate; }
        @keyframes d1 { to { transform: translate(60px,50px); } }
        @keyframes d2 { to { transform: translate(-50px,-60px); } }

        .card {
            width: min(460px, 92vw);
            background: rgba(22,35,56,.88);
            border: 1px solid rgba(14,165,160,.22);
            border-radius: 24px;
            padding: 48px 42px 44px;
            box-shadow: 0 24px 64px rgba(0,0,0,.45);
            backdrop-filter: blur(24px);
            animation: up .6s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes up { from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:none} }

        .badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(245,200,66,.12);
            border: 1px solid rgba(245,200,66,.3);
            border-radius: 50px;
            padding: 6px 14px;
            color: var(--gold);
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .badge svg { width:14px;height:14px;fill:var(--gold); }

        h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.8rem;
            color: var(--white);
            margin-bottom: 8px;
        }
        .desc {
            color: var(--gray);
            font-size: .88rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .highlight { color: var(--teal); font-weight: 600; }

        .field { margin-bottom: 20px; }
        label {
            display: block; font-size: .73rem; font-weight: 600;
            letter-spacing: .08em; text-transform: uppercase;
            color: var(--gray); margin-bottom: 8px;
        }
        .pw-wrap { position: relative; }
        input[type="password"] {
            width:100%; padding: 14px 42px 14px 16px;
            background: rgba(255,255,255,.05);
            border: 1.5px solid rgba(138,154,181,.22);
            border-radius: 12px;
            color: var(--white);
            font-family: 'Sora', sans-serif; font-size: .95rem;
            outline: none;
            transition: border-color .25s, box-shadow .25s;
        }
        input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14,165,160,.18); }
        input::placeholder { color: rgba(138,154,181,.5); }

        .pw-toggle {
            position:absolute; right:12px; top:50%; transform:translateY(-50%);
            background:none; border:none; cursor:pointer; color:var(--gray); padding:4px;
            transition: color .2s;
        }
        .pw-toggle:hover { color: var(--teal); }
        .pw-toggle svg { width:18px;height:18px; }

        /* Strength meter */
        .strength-bar { display:flex; gap:4px; margin-top:8px; }
        .strength-seg {
            flex:1; height:4px; border-radius:2px;
            background: rgba(255,255,255,.1);
            transition: background .3s;
        }
        .strength-label { font-size:.72rem; color:var(--gray); margin-top:4px; }

        .req-list {
            background: rgba(255,255,255,.04);
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 20px;
        }
        .req-item {
            display:flex; align-items:center; gap:8px;
            font-size: .8rem; color: var(--gray);
            margin-bottom: 6px;
            transition: color .3s;
        }
        .req-item:last-child { margin-bottom:0; }
        .req-item.ok { color: var(--teal2); }
        .req-dot { width:6px;height:6px;border-radius:50%;background:var(--gray);flex-shrink:0;transition:background .3s; }
        .req-item.ok .req-dot { background: var(--teal2); }

        .alert {
            padding:12px 16px; border-radius:10px; font-size:.85rem;
            margin-bottom:20px; display:none;
            animation: fadeIn .3s ease;
        }
        @keyframes fadeIn { from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none} }
        .alert-error   { background:rgba(255,107,107,.12);border:1px solid rgba(255,107,107,.35);color:#ff9a9a; }
        .alert-success { background:rgba(6,214,160,.1);border:1px solid rgba(6,214,160,.35);color:#06d6a0; }

        .btn {
            width:100%; padding:15px;
            background: linear-gradient(135deg,var(--teal),#09bfb9);
            border:none; border-radius:12px; color:white;
            font-family:'Sora',sans-serif; font-size:.95rem; font-weight:600;
            cursor:pointer; transition: transform .18s, box-shadow .18s, opacity .18s;
            box-shadow: 0 6px 24px rgba(14,165,160,.35);
        }
        .btn:hover { transform:translateY(-2px); box-shadow:0 10px 32px rgba(14,165,160,.45); }
        .btn:disabled { opacity:.6;cursor:not-allowed;transform:none; }

        @media(max-width:480px){.card{padding:32px 22px 28px;}h1{font-size:1.5rem;}}
    </style>
</head>
<body>
<div class="card">
    <div class="badge">
        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.5-2.33 6.79-5 7.93-2.67-1.14-5-4.43-5-7.93V7.18L12 5z"/></svg>
        Security Notice
    </div>

    <h1>Set New Password</h1>
    <p class="desc">
        Welcome, <span class="highlight"><?= htmlspecialchars($_SESSION['Full_Name']) ?></span>!<br>
        You are using the default password. Please create a new secure password to continue.
    </p>

    <div class="alert" id="alertBox"></div>

    <div class="field">
        <label>New Password</label>
        <div class="pw-wrap">
            <input type="password" id="newPw" placeholder="Create a strong password" oninput="checkStrength()">
            <button class="pw-toggle" type="button" onclick="togglePw('newPw')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>
        <div class="strength-bar">
            <div class="strength-seg" id="s1"></div>
            <div class="strength-seg" id="s2"></div>
            <div class="strength-seg" id="s3"></div>
            <div class="strength-seg" id="s4"></div>
        </div>
        <div class="strength-label" id="strengthLabel">Enter a password</div>
    </div>

    <div class="req-list">
        <div class="req-item" id="r1"><div class="req-dot"></div>At least 8 characters</div>
        <div class="req-item" id="r2"><div class="req-dot"></div>Contains uppercase letter</div>
        <div class="req-item" id="r3"><div class="req-dot"></div>Contains a number</div>
        <div class="req-item" id="r4"><div class="req-dot"></div>Not the default password (123456)</div>
    </div>

    <div class="field">
        <label>Confirm Password</label>
        <div class="pw-wrap">
            <input type="password" id="confirmPw" placeholder="Re-enter your password">
            <button class="pw-toggle" type="button" onclick="togglePw('confirmPw')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>
    </div>

    <button class="btn" id="saveBtn" onclick="savePassword()">Update Password</button>
</div>

<script>
function togglePw(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

function checkStrength() {
    const pw = document.getElementById('newPw').value;
    const segs = [document.getElementById('s1'),document.getElementById('s2'),document.getElementById('s3'),document.getElementById('s4')];
    const label = document.getElementById('strengthLabel');

    const ok1 = pw.length >= 8;
    const ok2 = /[A-Z]/.test(pw);
    const ok3 = /[0-9]/.test(pw);
    const ok4 = pw !== '123456' && pw.length > 0;

    document.getElementById('r1').className = 'req-item' + (ok1?' ok':'');
    document.getElementById('r2').className = 'req-item' + (ok2?' ok':'');
    document.getElementById('r3').className = 'req-item' + (ok3?' ok':'');
    document.getElementById('r4').className = 'req-item' + (ok4?' ok':'');

    const score = [ok1,ok2,ok3,ok4].filter(Boolean).length;
    const colors = ['#ff6b6b','#f5c842','#0ea5a0','#06d6a0'];
    const labels = ['Weak','Fair','Good','Strong'];

    segs.forEach((s,i) => {
        s.style.background = i < score ? colors[score-1] : 'rgba(255,255,255,.1)';
    });
    label.textContent = pw.length ? labels[score-1] || 'Weak' : 'Enter a password';
    label.style.color = pw.length ? colors[score-1] : 'var(--gray)';
}

function showAlert(msg, type='error') {
    const box = document.getElementById('alertBox');
    box.textContent = msg;
    box.className = 'alert alert-' + type;
    box.style.display = 'block';
}

async function savePassword() {
    const newPw = document.getElementById('newPw').value;
    const confirmPw = document.getElementById('confirmPw').value;
    const btn = document.getElementById('saveBtn');

    if (!newPw || !confirmPw) { showAlert('Please fill in all fields.'); return; }
    if (newPw !== confirmPw) { showAlert('Passwords do not match.'); return; }
    if (newPw.length < 8) { showAlert('Password must be at least 8 characters.'); return; }
    if (newPw === '123456') { showAlert('You cannot reuse the default password.'); return; }

    btn.disabled = true;
    btn.textContent = 'Saving…';

    try {
        const res = await fetch('api/change-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ new_password: newPw })
        });
        const data = await res.json();
        if (data.success) {
            showAlert('Password updated successfully! Redirecting…', 'success');
            setTimeout(() => window.location.href = 'portal.php', 1800);
        } else {
            showAlert(data.message || 'Failed to update password.');
        }
    } catch(e) {
        showAlert('Server error. Please try again.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Update Password';
    }
}
</script>
</body>
</html>
