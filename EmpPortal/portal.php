<?php
require_once 'db.php';
requireLogin();

// Refresh employee data from DB
$db   = getDB();
$stmt = $db->prepare("SELECT emp_ID, Full_Name, imgPath FROM tblemp WHERE emp_ID = ?");
$stmt->bind_param('i', $_SESSION['emp_ID']);
$stmt->execute();
$emp = $stmt->get_result()->fetch_assoc();
$stmt->close();
$db->close();

if (!$emp) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$_SESSION['imgPath'] = $emp['imgPath'];
$imgSrc = (!empty($emp['imgPath']))
   ? 'http://localhost:8080/hris/photos/' . htmlspecialchars($emp['imgPath'])
    : '';

// Fetch latest leave balances from vw_leaveledger
$db2   = getDB();
$stmt2 = $db2->prepare("
    SELECT
        COALESCE(SUM(EarnedVL), 0) - COALESCE(SUM(UsedVL), 0) AS BalanceVL,
        COALESCE(SUM(EarnedSL), 0) - COALESCE(SUM(UsedSL), 0) AS BalanceSL
    FROM vw_leaveledger
    WHERE emp_ID = ?
");
$stmt2->bind_param('i', $_SESSION['emp_ID']);
$stmt2->execute();
$leave = $stmt2->get_result()->fetch_assoc();
$stmt2->close();
$db2->close();

$balanceVL = $leave['BalanceVL'] ?? 0;
$balanceSL = $leave['BalanceSL'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS — Employee Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:   #0f1b2d;
            --navy2:  #162338;
            --navy3:  #1e2f48;
            --teal:   #0ea5a0;
            --teal2:  #06d6a0;
            --gold:   #f5c842;
            --white:  #ffffff;
            --light:  #d4e4f7;
            --gray:   #8a9ab5;
            --gray2:  #4a5a72;
            --border: rgba(14,165,160,.18);
            --sidebar-w: 260px;
        }

        html, body { height: 100%; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--navy);
            color: var(--white);
            display: flex;
            overflow-x: hidden;
        }

        /* ── SIDEBAR ─────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--navy2);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0; top: 0; bottom: 0;
            z-index: 100;
            transition: transform .3s cubic-bezier(.22,1,.36,1);
        }

        .sidebar-header {
            padding: 28px 24px 20px;
            border-bottom: 1px solid var(--border);
        }

        .brand {
            display: flex; align-items: center; gap: 10px;
        }
        .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--teal), var(--teal2));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-icon svg { width: 20px; height: 20px; fill: white; }
        .brand-name {
            font-size: .9rem; font-weight: 700;
            color: var(--white); letter-spacing: .04em;
        }
        .brand-sub { font-size: .65rem; color: var(--gray); letter-spacing: .08em; }

        /* Employee card in sidebar */
        .emp-card {
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }

        .avatar-wrap {
            position: relative;
            width: 88px; height: 88px;
            margin-bottom: 14px;
            cursor: pointer;
        }

        .avatar {
            width: 88px; height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--teal);
            display: block;
            background: var(--navy3);
            transition: transform .25s, box-shadow .25s;
        }

        .avatar-placeholder {
            width: 88px; height: 88px;
            border-radius: 50%;
            border: 3px solid var(--teal);
            background: var(--navy3);
            display: flex; align-items: center; justify-content: center;
            transition: transform .25s, box-shadow .25s;
        }
        .avatar-placeholder svg { width: 42px; height: 42px; fill: var(--gray); }

        .avatar-wrap:hover .avatar,
        .avatar-wrap:hover .avatar-placeholder {
            transform: scale(1.04);
            box-shadow: 0 0 0 4px rgba(14,165,160,.25), 0 8px 24px rgba(0,0,0,.3);
        }

        .avatar-overlay {
            position: absolute; inset: 0;
            border-radius: 50%;
            background: rgba(14,165,160,.65);
            display: flex; align-items: center; justify-content: center;
            opacity: 0;
            transition: opacity .25s;
            backdrop-filter: blur(2px);
        }
        .avatar-wrap:hover .avatar-overlay { opacity: 1; }
        .avatar-overlay svg { width: 26px; height: 26px; fill: white; }

        .emp-name {
            font-size: .92rem;
            font-weight: 600;
            color: var(--white);
            text-align: center;
            line-height: 1.3;
        }
        .emp-label {
            font-size: .7rem;
            color: var(--teal2);
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* Nav menu */
        .nav-section {
            padding: 16px 14px 0;
        }
        .nav-label {
            font-size: .62rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--gray2);
            padding: 0 10px;
            margin-bottom: 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 12px;
            border-radius: 10px;
            cursor: pointer;
            color: var(--gray);
            font-size: .875rem;
            font-weight: 500;
            transition: background .2s, color .2s, transform .15s;
            user-select: none;
            margin-bottom: 2px;
            text-decoration: none;
        }
        .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; opacity: .75; transition: opacity .2s; }
        .nav-item:hover { background: rgba(14,165,160,.1); color: var(--light); transform: translateX(2px); }
        .nav-item:hover svg { opacity: 1; }
        .nav-item.active { background: rgba(14,165,160,.18); color: var(--teal2); border-left: 3px solid var(--teal); padding-left: 9px; }
        .nav-item.active svg { opacity: 1; fill: var(--teal2); color: var(--teal2); }

        /* Submenu */
        .nav-submenu { padding-left: 14px; display: none; }
        .nav-submenu.open { display: block; }
        .nav-item.parent-active { color: var(--light); }
        .nav-item .chevron { margin-left: auto; width: 14px !important; transition: transform .25s; }
        .nav-item.parent-active .chevron { transform: rotate(90deg); }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 14px;
            border-top: 1px solid var(--border);
        }
        .btn-logout {
            display: flex; align-items: center; gap: 10px;
            width: 100%; padding: 11px 14px;
            background: rgba(255,107,107,.08);
            border: 1px solid rgba(255,107,107,.2);
            border-radius: 10px;
            color: #ff9a9a;
            font-family: 'Sora', sans-serif;
            font-size: .85rem; font-weight: 500;
            cursor: pointer;
            transition: background .2s, border-color .2s;
        }
        .btn-logout:hover { background: rgba(255,107,107,.16); border-color: rgba(255,107,107,.4); }
        .btn-logout svg { width: 16px; height: 16px; }

        /* ── MAIN CONTENT ───────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 32px;
            background: rgba(22,35,56,.6);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(12px);
            position: sticky; top: 0; z-index: 50;
        }

        .topbar-left { display: flex; align-items: center; gap: 14px; }

        .hamburger {
            display: none;
            background: none; border: none; cursor: pointer;
            color: var(--gray); padding: 6px;
        }
        .hamburger svg { width: 22px; height: 22px; }

        .page-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--white);
        }
        .page-sub {
            font-size: .75rem;
            color: var(--gray);
            margin-top: 1px;
        }

        .topbar-right { display: flex; align-items: center; gap: 12px; }

        .date-chip {
            background: rgba(255,255,255,.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: .75rem;
            color: var(--gray);
        }

        /* ── CONTENT PANELS ─────────────────────────── */
        .content {
            flex: 1;
            padding: 32px;
        }

        .panel { display: none; animation: panelIn .4s cubic-bezier(.22,1,.36,1) both; }
        .panel.active { display: block; }
        @keyframes panelIn { from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none} }

        /* Dashboard grid */
        .dash-welcome {
            background: linear-gradient(135deg, rgba(14,165,160,.2), rgba(6,214,160,.1));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 28px 32px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            overflow: hidden;
        }
        .dash-welcome::before {
            content: '';
            position: absolute; right: -20px; top: -30px;
            width: 180px; height: 180px;
            background: radial-gradient(circle, rgba(6,214,160,.2), transparent 70%);
        }

        .welcome-text h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.55rem;
            color: var(--white);
        }
        .welcome-text p { font-size: .85rem; color: var(--gray); margin-top: 4px; }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--navy2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px 22px 18px;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.25); }

        .stat-icon {
            width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
        }
        .stat-icon svg { width: 22px; height: 22px; }
        .stat-icon.teal   { background: rgba(14,165,160,.18); color: var(--teal);  fill: var(--teal); }
        .stat-icon.teal2  { background: rgba(6,214,160,.15);  color: var(--teal2); fill: var(--teal2); }
        .stat-icon.gold   { background: rgba(245,200,66,.15);  color: var(--gold);  fill: var(--gold); }
        .stat-icon.purple { background: rgba(168,85,247,.15);  color: #a855f7;      fill: #a855f7; }
        .stat-icon.rose   { background: rgba(251,113,133,.15); color: #fb7185;      fill: #fb7185; }

        .stat-val { font-size: 1.6rem; font-weight: 700; color: var(--white); }
        .stat-lbl { font-size: .75rem; color: var(--gray); margin-top: 2px; }
        .stat-sublbl {
            font-size: .68rem; color: var(--teal2); font-weight: 600;
            letter-spacing: .05em; text-transform: uppercase; margin-left: 3px;
        }

        /* Generic panel placeholder */
        .panel-placeholder {
            background: var(--navy2);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 60px 32px;
            text-align: center;
        }
        .panel-placeholder .ph-icon {
            width: 64px; height: 64px;
            background: rgba(14,165,160,.12);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        .panel-placeholder .ph-icon svg { width: 32px; height: 32px; fill: var(--teal); }
        .panel-placeholder h3 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.4rem; color: var(--white); margin-bottom: 8px;
        }
        .panel-placeholder p { font-size: .85rem; color: var(--gray); max-width: 360px; margin: 0 auto; }

        /* ── OVERLAY & MOBILE ───────────────────────── */
        .overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.6);
            z-index: 90;
            backdrop-filter: blur(4px);
        }
        .overlay.show { display: block; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .hamburger { display: block; }
            .content { padding: 20px 16px; }
            .topbar { padding: 14px 18px; }
            .dash-welcome { padding: 20px; gap: 14px; flex-wrap: wrap; }
            .welcome-text h2 { font-size: 1.25rem; }
        }

        /* ── MODAL ──────────────────────────────────── */
        .modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.7);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            backdrop-filter: blur(6px);
        }
        .modal-backdrop.show { display: flex; }

        .modal {
            background: var(--navy2);
            border: 1px solid var(--border);
            border-radius: 22px;
            width: min(440px, 100%);
            padding: 36px 34px 30px;
            box-shadow: 0 24px 80px rgba(0,0,0,.5);
            animation: modalIn .35s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes modalIn { from{opacity:0;transform:scale(.94)}to{opacity:1;transform:none} }

        .modal-title {
            font-family: 'DM Serif Display', serif;
            font-size: 1.4rem;
            color: var(--white);
            margin-bottom: 6px;
        }
        .modal-sub { font-size: .82rem; color: var(--gray); margin-bottom: 24px; }

        .drop-zone {
            border: 2px dashed rgba(14,165,160,.35);
            border-radius: 14px;
            padding: 36px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color .25s, background .25s;
            position: relative;
        }
        .drop-zone:hover, .drop-zone.drag-over {
            border-color: var(--teal);
            background: rgba(14,165,160,.06);
        }
        .drop-zone input[type="file"] {
            position: absolute; inset: 0;
            opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .drop-icon { width: 48px; height: 48px; fill: var(--teal); margin: 0 auto 12px; }
        .drop-text { font-size: .88rem; color: var(--gray); }
        .drop-text span { color: var(--teal); font-weight: 600; }

        .preview-wrap {
            display: none;
            margin-top: 16px;
            text-align: center;
        }
        .preview-wrap.show { display: block; }
        .preview-img {
            width: 110px; height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--teal);
            margin-bottom: 8px;
        }
        .preview-name { font-size: .8rem; color: var(--gray); }

        .alert-sm {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: .82rem;
            margin-top: 14px;
            display: none;
        }
        .alert-sm.err { background: rgba(255,107,107,.1); border: 1px solid rgba(255,107,107,.3); color: #ff9a9a; }
        .alert-sm.ok  { background: rgba(6,214,160,.1);  border: 1px solid rgba(6,214,160,.3);  color: #06d6a0; }

        .modal-actions { display:flex; gap:10px; margin-top:20px; }
        .btn-cancel {
            flex:1; padding:12px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 10px; color: var(--gray);
            font-family: 'Sora',sans-serif; font-size:.88rem;
            cursor:pointer; transition: background .2s;
        }
        .btn-cancel:hover { background: rgba(255,255,255,.1); }

        .btn-upload {
            flex:2; padding:12px;
            background: linear-gradient(135deg, var(--teal), #09bfb9);
            border: none; border-radius: 10px; color: white;
            font-family: 'Sora',sans-serif; font-size:.88rem; font-weight:600;
            cursor:pointer; transition: transform .18s, box-shadow .18s, opacity .18s;
            box-shadow: 0 4px 16px rgba(14,165,160,.3);
        }
        .btn-upload:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(14,165,160,.4); }
        .btn-upload:disabled { opacity:.6; cursor:not-allowed; transform:none; }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ── SIDEBAR ────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg>
            </div>
            <div>
                <div class="brand-name">HRIS</div>
                <div class="brand-sub">Employee Portal</div>
            </div>
        </div>
    </div>

    <!-- Employee Info -->
    <div class="emp-card">
        <div class="avatar-wrap" onclick="openPhotoModal()" title="Click to update photo">
            <?php if ($imgSrc): ?>
                <img src="<?= $imgSrc ?>" alt="Profile" class="avatar" id="sidebarAvatar">
            <?php else: ?>
                <div class="avatar-placeholder" id="sidebarAvatarPh">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg>
                </div>
            <?php endif; ?>
            <div class="avatar-overlay">
                <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
            </div>
        </div>
        <div class="emp-name"><?= htmlspecialchars($emp['Full_Name']) ?></div>
        <div class="emp-label">Employee</div>
    </div>

    <!-- Navigation -->
    <nav class="nav-section" style="flex:1;overflow-y:auto;padding-bottom:8px;">
        <div class="nav-label" style="margin-top:6px;">Main</div>

        <a class="nav-item active" onclick="showPanel('dashboard',this)" href="#">
            <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            Dashboard
        </a>

        <div class="nav-label" style="margin-top:14px;">Services</div>

        <a class="nav-item" onclick="showPanel('leave',this)" href="#">
            <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
            Application Leave
        </a>

        <!-- Trainings with submenu -->
        <a class="nav-item" onclick="toggleSubmenu('trainingSub',this)" href="#">
            <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
            Trainings
            <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </a>
        <div class="nav-submenu" id="trainingSub">
            <a class="nav-item" onclick="showPanel('training-request',this)" href="#">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/></svg>
                Request
            </a>
        </div>

        <a class="nav-item" onclick="showPanel('service-records',this)" href="#">
            <svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.3C18 2.12 15.88 0 13.3 0c-1.3 0-2.4.5-3.2 1.6L12 4 9.9 1.6C9.1.5 8 0 6.7 0 4.1 0 2 2.12 2 4.7c0 .42.1.86.18 1.3H0v14c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>
            Service Records
        </a>

        <a class="nav-item" onclick="showPanel('coe',this)" href="#">
            <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-1 7l-3-3 1.41-1.41L12 13.17l4.59-4.58L18 10l-6 6z"/></svg>
            Certificate of Employment
        </a>

        <a class="nav-item" onclick="showPanel('payslip',this)" href="#">
            <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            PaySlip
        </a>
    </nav>

    <div class="sidebar-footer">
        <button class="btn-logout" onclick="logout()">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
            Sign Out
        </button>
    </div>
</aside>

<!-- ── MAIN ───────────────────────────────────────────── -->
<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            </button>
            <div>
                <div class="page-title" id="pageTitle">Dashboard</div>
                <div class="page-sub" id="pageDate"></div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="date-chip" id="topDate"></div>
        </div>
    </div>

    <div class="content">

        <!-- DASHBOARD -->
        <div class="panel active" id="panel-dashboard">
            <div class="dash-welcome">
                <div class="welcome-text">
                    <h2>Good day, <?= htmlspecialchars(explode(' ', $emp['Full_Name'])[0]) ?>!</h2>
                    <p>Welcome to the Employee Self-Service Portal. Here's your overview for today.</p>
                </div>
            </div>

            <div class="cards-grid">
                <!-- Vacation Leave Balance -->
                <div class="stat-card">
                    <div class="stat-icon teal">
                        <svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                    </div>
                    <div class="stat-val"><?= number_format((float)$balanceVL, 3) ?></div>
                    <div class="stat-lbl">Vacation Leave <span class="stat-sublbl">Balance</span></div>
                </div>
                <!-- Sick Leave Balance -->
                <div class="stat-card">
                    <div class="stat-icon teal2">
                        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                    </div>
                    <div class="stat-val"><?= number_format((float)$balanceSL, 3) ?></div>
                    <div class="stat-lbl">Sick Leave <span class="stat-sublbl">Balance</span></div>
                </div>
                <!-- Trainings -->
                <div class="stat-card">
                    <div class="stat-icon gold">
                        <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
                    </div>
                    <div class="stat-val">0</div>
                    <div class="stat-lbl">Trainings</div>
                </div>
                <!-- Latest Payslip -->
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                    </div>
                    <div class="stat-val">—</div>
                    <div class="stat-lbl">Latest Payslip</div>
                </div>
                <!-- Pending Requests -->
                <div class="stat-card">
                    <div class="stat-icon rose">
                        <svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.3C18 2.12 15.88 0 13.3 0c-1.3 0-2.4.5-3.2 1.6L12 4 9.9 1.6C9.1.5 8 0 6.7 0 4.1 0 2 2.12 2 4.7c0 .42.1.86.18 1.3H0v14c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>
                    </div>
                    <div class="stat-val">0</div>
                    <div class="stat-lbl">Pending Requests</div>
                </div>
            </div>
        </div>

        <!-- LEAVE -->
        <div class="panel" id="panel-leave">

            <link rel="stylesheet" href="modules/leave/leave.css">

            <?php include __DIR__ . '/modules/leave/leave.php'; ?>

        </div>

        <?php include __DIR__ . '/modules/leave/leave-modal.php'; ?>

        <script src="modules/leave/leave.js?v=<?= time() ?>"></script>

        <!-- TRAINING REQUEST -->
        <div class="panel" id="panel-training-request">
            <div class="panel-placeholder">
                <div class="ph-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/></svg></div>
                <h3>Training Requests</h3>
                <p>Submit and monitor your training enrollment requests.</p>
            </div>
        </div>

        <!-- SERVICE RECORDS -->
        <div class="panel" id="panel-service-records">
            <div class="panel-placeholder">
                <div class="ph-icon"><svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.3C18 2.12 15.88 0 13.3 0c-1.3 0-2.4.5-3.2 1.6L12 4 9.9 1.6C9.1.5 8 0 6.7 0 4.1 0 2 2.12 2 4.7c0 .42.1.86.18 1.3H0v14c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg></div>
                <h3>Service Records</h3>
                <p>View your complete employment history and service records.</p>
            </div>
        </div>

        <!-- COE -->
        <div class="panel" id="panel-coe">
            <div class="panel-placeholder">
                <div class="ph-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-1 7l-3-3 1.41-1.41L12 13.17l4.59-4.58L18 10l-6 6z"/></svg></div>
                <h3>Certificate of Employment</h3>
                <p>Request and download your certificate of employment.</p>
            </div>
        </div>

        <!-- PAYSLIP -->
        <div class="panel" id="panel-payslip">
            <div class="panel-placeholder">
                <div class="ph-icon"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div>
                <h3>PaySlip</h3>
                <p>View and download your monthly payslips.</p>
            </div>
        </div>

    </div><!-- end .content -->
</main>

<!-- ── PHOTO UPLOAD MODAL ─────────────────────────────── -->
<div class="modal-backdrop" id="photoModal">
    <div class="modal">
        <div class="modal-title">Update Profile Photo</div>
        <div class="modal-sub">Maximum file size: 1.5 MB &nbsp;·&nbsp; JPEG, PNG, GIF, or WEBP</div>

        <div class="drop-zone" id="dropZone">
            <input type="file" id="photoInput" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewFile(this)">
            <svg class="drop-icon" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
            <div class="drop-text"><span>Click to browse</span> or drag & drop your photo here</div>
        </div>

        <div class="preview-wrap" id="previewWrap">
            <img src="" id="previewImg" class="preview-img" alt="Preview">
            <div class="preview-name" id="previewName"></div>
        </div>

        <div class="alert-sm" id="modalAlert"></div>

        <div class="modal-actions">
            <button class="btn-cancel" onclick="closePhotoModal()">Cancel</button>
            <button class="btn-upload" id="uploadBtn" onclick="uploadPhoto()" disabled>Upload Photo</button>
        </div>
    </div>
</div>

<script>
    // ── Date ──
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('topDate').textContent = dateStr;
    document.getElementById('pageDate').textContent = dateStr;

    // ── Panel navigation ──
    function showPanel(id, el) {
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + id).classList.add('active');
        document.querySelectorAll('.nav-item:not(.parent-active)').forEach(n => n.classList.remove('active'));
        if (el && !el.classList.contains('hamburger')) el.classList.add('active');
        const titles = {
            dashboard: 'Dashboard', leave: 'Application Leave',
            'training-request': 'Training — Request',
            'service-records': 'Service Records',
            coe: 'Certificate of Employment', payslip: 'PaySlip'
        };
        document.getElementById('pageTitle').textContent = titles[id] || id;
        if (window.innerWidth <= 768) closeSidebar();
    }

    function toggleSubmenu(id, el) {
        const sub = document.getElementById(id);
        const isOpen = sub.classList.toggle('open');
        el.classList.toggle('parent-active', isOpen);
    }

    // ── Sidebar mobile ──
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('overlay').classList.toggle('show');
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('overlay').classList.remove('show');
    }

    // ── Logout ──
    function logout() {
        window.location.href = 'api/logout.php';
    }

    // ── Photo modal ──
    let selectedFile = null;

    function openPhotoModal() {
        document.getElementById('photoModal').classList.add('show');
        document.getElementById('previewWrap').classList.remove('show');
        document.getElementById('modalAlert').style.display = 'none';
        document.getElementById('uploadBtn').disabled = true;
        document.getElementById('photoInput').value = '';
        selectedFile = null;
    }

    function closePhotoModal() {
        document.getElementById('photoModal').classList.remove('show');
    }

    document.getElementById('photoModal').addEventListener('click', function(e) {
        if (e.target === this) closePhotoModal();
    });

    // Drag & drop
    const dropZone = document.getElementById('dropZone');
    ['dragenter','dragover'].forEach(ev => dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('drag-over'); }));
    ['dragleave','drop'].forEach(ev => dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.remove('drag-over'); }));
    dropZone.addEventListener('drop', e => {
        const file = e.dataTransfer.files[0];
        if (file) handleFileSelect(file);
    });

    function previewFile(input) {
        if (input.files[0]) handleFileSelect(input.files[0]);
    }

    function handleFileSelect(file) {
        const alert = document.getElementById('modalAlert');
        const maxBytes = 1.5 * 1024 * 1024;

        if (file.size > maxBytes) {
            alert.textContent = 'File is too large. Maximum size is 1.5 MB.';
            alert.className = 'alert-sm err';
            alert.style.display = 'block';
            document.getElementById('uploadBtn').disabled = true;
            return;
        }

        const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!allowed.includes(file.type)) {
            alert.textContent = 'Invalid file type. Please use JPEG, PNG, GIF, or WEBP.';
            alert.className = 'alert-sm err';
            alert.style.display = 'block';
            document.getElementById('uploadBtn').disabled = true;
            return;
        }

        alert.style.display = 'none';
        selectedFile = file;
        document.getElementById('uploadBtn').disabled = false;

        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('previewName').textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
            document.getElementById('previewWrap').classList.add('show');
        };
        reader.readAsDataURL(file);
    }

    async function uploadPhoto() {
        if (!selectedFile) return;
        const btn = document.getElementById('uploadBtn');
        const alert = document.getElementById('modalAlert');
        btn.disabled = true;
        btn.textContent = 'Uploading…';

        const formData = new FormData();
        formData.append('photo', selectedFile);

        try {
            const res = await fetch('api/upload-photo.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                alert.textContent = 'Profile photo updated successfully!';
                alert.className = 'alert-sm ok';
                alert.style.display = 'block';

                const newSrc = data.imgUrl + '?t=' + Date.now();
                updateAllAvatars(newSrc);

                setTimeout(closePhotoModal, 1200);
            } else {
                alert.textContent = data.message || 'Upload failed.';
                alert.className = 'alert-sm err';
                alert.style.display = 'block';
            }
        } catch (e) {
            alert.textContent = 'Server error. Please try again.';
            alert.className = 'alert-sm err';
            alert.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Upload Photo';
        }
    }

    function updateAllAvatars(src) {
        const sidebarEl = document.getElementById('sidebarAvatar');
        const sidebarPh = document.getElementById('sidebarAvatarPh');
        if (sidebarEl) { sidebarEl.src = src; }
        else if (sidebarPh) {
            const img = document.createElement('img');
            img.src = src; img.alt = 'Profile'; img.className = 'avatar'; img.id = 'sidebarAvatar';
            sidebarPh.replaceWith(img);
        }
    }
</script>
</body>
</html>