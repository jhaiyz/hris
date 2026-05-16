<?php
/**
 * update-profile-modal.php
 * Update Profile modal partial — included by portal.php.
 * Outputs: modal styles, modal HTML, success popup HTML, and modal scripts.
 * Replace the inline #panel-update-profile-panel block in portal.php with:
 *   <?php include __DIR__ . '/update-profile-modal.php'; ?>
 */
?>

<!-- ===================== UPDATE PROFILE MODAL STYLES ===================== -->
<style>
    /* ===================== UPDATE PROFILE MODAL ===================== */
    .up-modal-overlay {
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
    .up-modal-overlay.open {
        opacity: 1;
        pointer-events: all;
    }

    .up-modal {
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
    .up-modal-overlay.open .up-modal {
        transform: translateY(0) scale(1);
    }

    /* Modal header */
    .up-modal-header {
        background: linear-gradient(135deg, rgba(14,165,160,.15), rgba(6,214,160,.08));
        border-bottom: 1px solid rgba(14,165,160,.2);
        padding: 28px 36px 24px;
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .up-modal-header-icon {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, var(--teal), var(--teal2));
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 6px 20px rgba(14,165,160,.35);
    }
    .up-modal-header-icon svg { width: 26px; height: 26px; fill: white; }

    .up-modal-header-text h2 {
        font-family: 'DM Serif Display', serif;
        font-size: 1.45rem;
        color: var(--white);
        line-height: 1.2;
    }
    .up-modal-header-text p {
        font-size: .78rem;
        color: var(--gray);
        margin-top: 3px;
        letter-spacing: .06em;
    }

    .up-modal-close {
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
    .up-modal-close:hover { background: rgba(255,107,107,.15); color: #ff9a9a; }
    .up-modal-close svg { width: 16px; height: 16px; }

    /* Modal body */
    .up-modal-body {
        padding: 30px 36px 36px;
    }

    /* Section dividers */
    .up-section-title {
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
    .up-section-title svg { width: 14px; height: 14px; fill: var(--teal); }

    /* Form grid */
    .up-form-grid {
        display: grid;
        gap: 16px;
        margin-bottom: 24px;
    }
    .up-form-grid.cols-2 { grid-template-columns: 1fr 1fr; }
    .up-form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
    .up-form-grid.cols-1 { grid-template-columns: 1fr; }

    .up-field label {
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
    .up-field label .up-optional-tag {
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

    .up-field input[type="text"],
    .up-field input[type="email"],
    .up-field input[type="date"],
    .up-field select {
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

    .up-field input:focus,
    .up-field select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(14,165,160,.14);
    }

    .up-field input::placeholder { color: rgba(138,154,181,.4); font-size: .83rem; }

    .up-field input[readonly] {
        background: rgba(255,255,255,.02);
        border-color: rgba(138,154,181,.1);
        color: rgba(138,154,181,.5);
        cursor: not-allowed;
    }

    .up-field select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='%238a9ab5'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
        cursor: pointer;
    }

    .up-field select option { background: #162338; color: var(--white); }

    .up-field input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(.5) sepia(1) saturate(2) hue-rotate(140deg);
        cursor: pointer;
        opacity: .6;
    }

    /* Alert inside modal */
    .up-alert {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: .83rem;
        margin-bottom: 18px;
        display: none;
        line-height: 1.5;
        animation: fadeIn .3s ease;
    }
    .up-alert.error   { background: rgba(255,107,107,.1);  border: 1px solid rgba(255,107,107,.3);  color: #ff9a9a; display: block; }
    .up-alert.success { background: rgba(6,214,160,.09);   border: 1px solid rgba(6,214,160,.3);   color: #06d6a0; display: block; }

    /* Modal footer */
    .up-modal-footer {
        padding: 20px 36px 30px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        border-top: 1px solid rgba(14,165,160,.1);
    }

    .up-btn-cancel {
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
    .up-btn-cancel:hover { background: rgba(255,255,255,.09); color: var(--white); }

    .up-btn-save {
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
    .up-btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(14,165,160,.45); }
    .up-btn-save:disabled { opacity: .6; cursor: not-allowed; transform: none; }
    .up-btn-save svg { width: 16px; height: 16px; fill: white; }

    .up-btn-save .up-btn-spinner {
        width: 15px; height: 15px;
        border: 2px solid rgba(255,255,255,.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin .7s linear infinite;
        display: none;
    }

    /* ===================== SUCCESS POPUP ===================== */
    .up-success-overlay {
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
    .up-success-overlay.open {
        opacity: 1;
        pointer-events: all;
    }

    .up-success-popup {
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
    .up-success-overlay.open .up-success-popup {
        transform: scale(1);
    }

    .up-success-icon {
        width: 72px; height: 72px;
        background: linear-gradient(135deg, var(--teal), var(--teal2));
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 8px 30px rgba(14,165,160,.4);
        animation: upPopIn .5s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes upPopIn { from { transform: scale(.4); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .up-success-icon svg { width: 36px; height: 36px; fill: white; }

    .up-success-popup h3 {
        font-family: 'DM Serif Display', serif;
        font-size: 1.4rem;
        color: var(--white);
        margin-bottom: 10px;
    }
    .up-success-popup p {
        font-size: .85rem;
        color: var(--gray);
        line-height: 1.6;
        margin-bottom: 24px;
    }

    .up-btn-got-it {
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
    .up-btn-got-it:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(14,165,160,.45); }

    @media (max-width: 640px) {
        .up-modal-header, .up-modal-body, .up-modal-footer { padding-left: 22px; padding-right: 22px; }
        .up-form-grid.cols-2,
        .up-form-grid.cols-3 { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .up-form-grid.cols-2,
        .up-form-grid.cols-3 { grid-template-columns: 1fr; }
    }
</style>

<!-- ===== UPDATE PROFILE MODAL ===== -->
<div class="up-modal-overlay" id="upOverlay" onclick="upOverlayClick(event)">
    <div class="up-modal" id="upModal">

        <div class="up-modal-header">
            <div class="up-modal-header-icon">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg>
            </div>
            <div class="up-modal-header-text">
                <h2>Update Profile</h2>
                <p>Edit your personal and employment information</p>
            </div>
            <button class="up-modal-close" onclick="closeUpModal()" title="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="up-modal-body">
            <div class="up-alert" id="upModalAlert"></div>

            <!-- Personal Information -->
            <div class="up-section-title">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg>
                Personal Information
            </div>

            <!-- Row: Employee No + Nickname -->
            <div class="up-form-grid cols-2" style="margin-bottom:16px;">
                <div class="up-field">
                    <label>Employee No.</label>
                    <input type="text" id="up_emp_no" readonly>
                </div>
                <div class="up-field">
                    <label>Nickname</label>
                    <input type="text" id="up_nickname" placeholder="e.g. TANGGOL">
                </div>
            </div>

            <!-- Row: First, Middle, Last -->
            <div class="up-form-grid cols-3" style="margin-bottom:16px;">
                <div class="up-field">
                    <label>First Name</label>
                    <input type="text" id="up_first" placeholder="e.g. MARJOVIE">
                </div>
                <div class="up-field">
                    <label>Middle Name</label>
                    <input type="text" id="up_middle" placeholder="e.g. POTACIO">
                </div>
                <div class="up-field">
                    <label>Last Name</label>
                    <input type="text" id="up_last" placeholder="e.g. CATUIRAN">
                </div>
            </div>

            <!-- Row: Extension Name + Birthday -->
            <div class="up-form-grid cols-2" style="margin-bottom:24px;">
                <div class="up-field">
                    <label>Extension Name <span class="up-optional-tag">optional</span></label>
                    <input type="text" id="up_ext" placeholder="e.g. JR, SR, III">
                </div>
                <div class="up-field">
                    <label>Birthday</label>
                    <input type="date" id="up_birthday">
                </div>
            </div>

            <!-- Employment Details -->
            <div class="up-section-title">
                <svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
                Employment Details
            </div>

            <div class="up-form-grid cols-2" style="margin-bottom:16px;">
                <div class="up-field">
                    <label>Office</label>
                    <select id="up_office">
                        <option value="">— Select Office —</option>
                        <?php
                            $dbOff = getDB();
                            $resOff = $dbOff->query("SELECT Office FROM tbloffice ORDER BY Office");
                            if ($resOff) {
                                while ($rowOff = $resOff->fetch_assoc()) {
                                    echo '<option value="'.htmlspecialchars($rowOff['Office']).'">'.htmlspecialchars($rowOff['Office']).'</option>';
                                }
                            }
                            $dbOff->close();
                        ?>
                    </select>
                </div>
                <div class="up-field">
                    <label>Employment Status</label>
                    <select id="up_emp_status">
                        <option value="">— Select Status —</option>
                        <option value="PERMANENT">PERMANENT</option>
                        <option value="JOB ORDER">JOB ORDER</option>
                        <option value="CONTRACT OF SERVICE">CONTRACT OF SERVICE</option>
                        <option value="CONTRACTUAL">CONTRACTUAL</option>
                    </select>
                </div>
            </div>

            <div class="up-form-grid cols-1" style="margin-bottom:24px;">
                <div class="up-field">
                    <label>Position</label>
                    <input type="text" id="up_position" placeholder="Do not abbreviate">
                </div>
            </div>

            <!-- Contact Information -->
            <div class="up-section-title">
                <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                Contact Information
            </div>

            <div class="up-form-grid cols-2" style="margin-bottom:16px;">
                <div class="up-field">
                    <label>Mobile No. <span class="up-optional-tag">optional</span></label>
                    <input type="text" id="up_mobile" placeholder="09XXXXXXXXX">
                </div>
                <div class="up-field">
                    <label>Email Address</label>
                    <input type="email" id="up_email" placeholder="you@example.com">
                </div>
            </div>

            <div class="up-form-grid cols-1" style="margin-bottom:24px;">
                <div class="up-field">
                    <label>Contact Person in Case of Emergency <span class="up-optional-tag">optional</span></label>
                    <input type="text" id="up_emergency" placeholder="e.g. Chris P. Pata">
                </div>
            </div>

            <!-- Professional Credentials -->
            <div class="up-section-title">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                Professional Credentials
                <span style="font-size:.65rem;font-weight:400;letter-spacing:.05em;color:rgba(138,154,181,.5);text-transform:none;margin-left:4px;">(optional)</span>
            </div>

            <div class="up-form-grid cols-2" style="margin-bottom:16px;">
                <div class="up-field">
                    <label>PRC No. <span class="up-optional-tag">optional</span></label>
                    <input type="text" id="up_prc" placeholder="PRC License No."
                           oninput="upToggleRequired('up_prc_exp', this.value)">
                </div>
                <div class="up-field">
                    <label>PRC Expiration Date <span class="up-optional-tag" id="up_prc_exp_tag">optional</span></label>
                    <input type="date" id="up_prc_exp" disabled>
                </div>
            </div>

            <div class="up-form-grid cols-2" style="margin-bottom:16px;">
                <div class="up-field">
                    <label>S2 No. <span class="up-optional-tag">optional</span></label>
                    <input type="text" id="up_s2" placeholder="S2 License No."
                           oninput="upToggleRequired('up_s2_exp', this.value)">
                </div>
                <div class="up-field">
                    <label>S2 Expiration Date <span class="up-optional-tag" id="up_s2_exp_tag">optional</span></label>
                    <input type="date" id="up_s2_exp" disabled>
                </div>
            </div>

            <div class="up-form-grid cols-1" style="margin-bottom:16px;">
                <div class="up-field">
                    <label>PhilHealth Accreditation <span class="up-optional-tag">optional</span></label>
                    <input type="text" id="up_ph_accred" placeholder="PH Accreditation No.">
                </div>
            </div>

            <div class="up-form-grid cols-1" style="margin-bottom:0;">
                <div class="up-field">
                    <label>Professional Suffixes <span class="up-optional-tag">optional</span></label>
                    <input type="text" id="up_prof_suffixes" placeholder="e.g., MPA, RN, LPT, etc.">
                </div>
            </div>
        </div>

        <div class="up-modal-footer">
            <button class="up-btn-cancel" onclick="closeUpModal()">Cancel</button>
            <button class="up-btn-save" id="upSaveBtn" onclick="doUpdateProfile()">
                <span class="up-btn-spinner" id="upSaveSpinner"></span>
                <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                Save Changes
            </button>
        </div>
    </div>
</div>

<!-- ===== SUCCESS POPUP ===== -->
<div class="up-success-overlay" id="upSuccessOverlay">
    <div class="up-success-popup">
        <div class="up-success-icon">
            <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <h3>Profile Updated!</h3>
        <p>Your profile information has been saved successfully.</p>
        <button class="up-btn-got-it" onclick="closeUpSuccessPopup()">Got it, back to dashboard</button>
    </div>
</div>

<!-- ===== UPDATE PROFILE SCRIPTS ===== -->
<script>
/**
 * Toggles the expiration date input between required and optional
 * based on whether its paired license number field has a value.
 */
function upToggleRequired(expId, licValue) {
    const expInput = document.getElementById(expId);
    const tag      = document.getElementById(expId + '_tag');
    const filled   = licValue.trim() !== '';

    expInput.disabled = !filled;
    expInput.required = filled;

    if (tag) {
        tag.textContent = filled ? 'required' : 'optional';
        tag.style.color = filled ? '#e57373' : '';
    }

    if (!filled) expInput.value = '';
}

/* ========== UPDATE PROFILE MODAL ========== */
function openUpModal() {
    document.getElementById('upOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    loadUpProfileData();
}

function closeUpModal() {
    document.getElementById('upOverlay').classList.remove('open');
    document.body.style.overflow = '';
    const a = document.getElementById('upModalAlert');
    a.className = 'up-alert';
    a.textContent = '';
}

function upOverlayClick(e) {
    if (e.target === document.getElementById('upOverlay')) closeUpModal();
}

function showUpModalAlert(msg, type = 'error') {
    const a = document.getElementById('upModalAlert');
    a.textContent = msg;
    a.className = 'up-alert ' + type;
    a.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function loadUpProfileData() {
    try {
        const res  = await fetch('api/get-profile.php');
        const data = await res.json();
        if (!data.success) return;
        const p = data.profile;
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };
        setVal('up_emp_no',     p.Employee_No);
        setVal('up_nickname',   p.Nick_Name);
        setVal('up_first',      p.First_Name);
        setVal('up_middle',     p.Middle_Name);
        setVal('up_last',       p.Last_Name);
        setVal('up_ext',        p.Ext_Name);
        setVal('up_birthday',   p.Birthday);
        setVal('up_office',     p.Office);
        setVal('up_emp_status', p.Employment_Status);
        setVal('up_position',   p.Position);
        setVal('up_mobile',     p.Mobile_No);
        setVal('up_email',      p.Email);
        setVal('up_emergency',  p.CP_Emergency);
        setVal('up_prc',           p.PRC_No);
        setVal('up_ph_accred',     p.PH_Accred);
        setVal('up_prof_suffixes', p.Prof_Suffixes);
        setVal('up_s2',            p.S2_No);

        // Re-enable expiration fields if license numbers are already set
        if (p.PRC_No) {
            upToggleRequired('up_prc_exp', p.PRC_No);
            setVal('up_prc_exp', p.PRC_ExpDate);
        }
        if (p.S2_No) {
            upToggleRequired('up_s2_exp', p.S2_No);
            setVal('up_s2_exp', p.S2_ExpDate);
        }
    } catch (e) {
        console.error('Failed to load profile', e);
    }
}

async function doUpdateProfile() {
    // g() reads value even from disabled inputs (e.g. expiration date fields)
    const g = id => { const el = document.getElementById(id); return el ? el.value.trim() : ''; };
    const gAny = id => { const el = document.getElementById(id); return el ? (el.value || '').trim() : ''; };

    const nickname  = g('up_nickname');
    const firstName = g('up_first');
    const middleName= g('up_middle');
    const lastName  = g('up_last');
    const extName   = g('up_ext');
    const birthday  = g('up_birthday');
    const office    = g('up_office');
    const empStatus = g('up_emp_status');
    const position  = g('up_position');
    const mobileNo  = g('up_mobile');
    const email     = g('up_email');
    const emergency = g('up_emergency');
    const prcNo      = g('up_prc');
    const prcDateExp = gAny('up_prc_exp');
    const s2No       = g('up_s2');
    const s2DateExp  = gAny('up_s2_exp');
    const phAccred   = g('up_ph_accred');
    const profSuffixes = g('up_prof_suffixes');

    const required = [
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
        if (!val) { showUpModalAlert(`⚠ ${label} is required.`); return; }
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        showUpModalAlert('⚠ Please enter a valid email address.');
        return;
    }

    const btn     = document.getElementById('upSaveBtn');
    const spinner = document.getElementById('upSaveSpinner');
    btn.disabled  = true;
    spinner.style.display = 'inline-block';

    try {
        const res  = await fetch('api/update-profile.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                Nick_Name:         nickname,
                First_Name:        firstName,
                Middle_Name:       middleName,
                Last_Name:         lastName,
                Ext_Name:          extName,
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
                PH_Accred:         phAccred,
                Prof_Suffixes:     profSuffixes,
            })
        });
        const data = await res.json();

        if (data.success) {
            // Update sidebar name live
            if (data.full_name) {
                const nameEl = document.querySelector('.emp-name');
                if (nameEl) nameEl.textContent = data.full_name;
            }
            closeUpModal();
            document.getElementById('upSuccessOverlay').classList.add('open');
        } else {
            showUpModalAlert('⚠ ' + (data.message || 'Failed to update profile. Please try again.'));
        }
    } catch (err) {
        showUpModalAlert('⚠ Server error. Please try again.');
    } finally {
        btn.disabled = false;
        spinner.style.display = 'none';
    }
}

/* ========== SUCCESS POPUP ========== */
function closeUpSuccessPopup() {
    document.getElementById('upSuccessOverlay').classList.remove('open');
    document.body.style.overflow = '';
    // Navigate back to dashboard
    const dashNav = document.querySelector('.nav-item[onclick*="dashboard"]');
    showPanel('dashboard', dashNav);
    if (dashNav) dashNav.classList.add('active');
}
</script>