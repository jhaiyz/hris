<?php
/**
 * register-modal.php
 * Registration modal partial — included by index.php.
 * Outputs: modal styles, modal HTML, success popup HTML, and modal scripts.
 */
?>

<!-- ===================== REGISTRATION MODAL STYLES ===================== -->
<style>
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
                    <input type="text" id="reg_emp_no" placeholder="e.g. CDH-2023-244">
                </div>
                <div class="reg-field">
                    <label>Nickname</label>
                    <input type="text" id="reg_nickname" placeholder="e.g. TANGGOL">
                </div>
            </div>

            <!-- Row: First, Middle, Last, Ext -->
            <div class="form-grid cols-4" style="margin-bottom:16px;">
                <div class="reg-field span-2">
                    <label>First Name</label>
                    <input type="text" id="reg_first" placeholder="e.g. MARJOVIE">
                </div>
                <div class="reg-field">
                    <label>Middle Name</label>
                    <input type="text" id="reg_middle" placeholder="e.g. POTACIO">
                </div>
                <div class="reg-field">
                    <label>Last Name</label>
                    <input type="text" id="reg_last" placeholder="e.g. CATUIRAN">
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
                    <label>Contact Person Incase of Emergency <span class="optional-tag">optional</span></label>
                    <input type="text" id="reg_emergency" placeholder="e.g. Chris P. Pata">
                </div>
            </div>

            <!-- Professional Credentials -->
            <div class="section-title">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                Professional Credentials <span style="font-size:.65rem; font-weight:400; letter-spacing:.05em; color:rgba(138,154,181,.5); text-transform:none; margin-left:4px;">(optional)</span>
            </div>

            <div class="form-grid cols-2" style="margin-bottom:16px;">
                <div class="reg-field">
                    <label>PRC No. <span class="optional-tag">optional</span></label>
                    <input type="text" id="reg_prc" placeholder="PRC License No."
                           oninput="toggleRequired('reg_prc_exp', this.value)">
                </div>
                <div class="reg-field">
                    <label>PRC Expiration Date <span class="optional-tag" id="reg_prc_exp_tag">optional</span></label>
                    <input type="date" id="reg_prc_exp" disabled>
                </div>
            </div>

            <div class="form-grid cols-2" style="margin-bottom:16px;">
                <div class="reg-field">
                    <label>S2 No. <span class="optional-tag">optional</span></label>
                    <input type="text" id="reg_s2" placeholder="S2 License No."
                           oninput="toggleRequired('reg_s2_exp', this.value)">
                </div>
                <div class="reg-field">
                    <label>S2 Expiration Date <span class="optional-tag" id="reg_s2_exp_tag">optional</span></label>
                    <input type="date" id="reg_s2_exp" disabled>
                </div>
            </div>

            <div class="form-grid cols-1">
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

<!-- ===== REGISTRATION SCRIPTS ===== -->
<script>
/**
 * Toggles the expiration date input between required and optional
 * based on whether its paired license number field has a value.
 */
function toggleRequired(expId, licValue) {
    const expInput = document.getElementById(expId);
    const tagId    = expId + '_tag';
    const tag      = document.getElementById(tagId);
    const filled   = licValue.trim() !== '';

    expInput.disabled = !filled;
    expInput.required = filled;

    if (tag) {
        tag.textContent = filled ? 'required' : 'optional';
        tag.style.color = filled ? '#e57373' : '';
    }

    if (!filled) expInput.value = '';
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
    const prcDateExp  = g('reg_prc_exp');
    const s2No        = g('reg_s2');
    const s2DateExp   = g('reg_s2_exp');
    const phAccred    = g('reg_ph_accred');

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

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        showRegAlert('⚠ Please enter a valid email address.');
        return;
    }

    let fullName = lastName + ', ' + firstName;
    if (middleName) fullName += ' ' + middleName;
    if (extName)    fullName += ' ' + extName;

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
                PRC_ExpDate:       prcDateExp,
                S2_No:             s2No,
                S2_ExpDate:        s2DateExp,
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