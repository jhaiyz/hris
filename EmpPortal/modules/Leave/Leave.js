console.log("Leave module loaded");

// ─────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    var leaveModal = document.getElementById('leaveModal');
    if (leaveModal) {
        leaveModal.addEventListener('click', function (e) {
            if (e.target === this) closeLeaveModal();
        });
    }

    // Boot the calendar so it is ready before the modal is opened
    lmCalInit();
});

// ─────────────────────────────────────────────
// EDIT CLICK ENTRY POINT
// ─────────────────────────────────────────────
function lmEditClick(btn) {
    var row = JSON.parse(btn.getAttribute('data-row'));
    openLeaveModal(row);
}

// ─────────────────────────────────────────────
// MODAL
// openLeaveModal()      → new application
// openLeaveModal(row)   → edit existing row
// ─────────────────────────────────────────────
function openLeaveModal(row) {

    var modal = document.getElementById('leaveModal');
    if (!modal) return;

    resetLeaveForm();           // full reset (calendar included)
    lmCalRender();              // make sure calendar grid is painted

    if (row) {
        // ── EDIT MODE ──────────────────────────────────
        document.getElementById('lmModalTitle').textContent  = 'Modify Leave Application';
        document.getElementById('lmSubmitLabel').textContent = 'Save Changes';
        document.getElementById('lm_appID').value            = row.app_ID;

        document.getElementById('lm_leaveType').value = row.lt_ID;

        var radios = document.querySelectorAll('input[name="lm_detail"]');
        radios.forEach(function (radio) {
            if (radio.value === row.dol_b) {
                radio.checked = true;
                var radioRow    = radio.closest('.lm-radio-row');
                var detailInput = radioRow ? radioRow.querySelector('.lm-detail-input') : null;
                if (detailInput) detailInput.value = row.dol_c || '';
            }
        });

        // Pre-fill the readonly fields; calendar selection stays empty in edit
        document.getElementById('lm_nod').value   = row.nod;
        document.getElementById('lm_dates').value = row.inclusive_dates;

    } else {
        // ── NEW MODE ───────────────────────────────────
        document.getElementById('lmModalTitle').textContent  = 'New Leave Application';
        document.getElementById('lmSubmitLabel').textContent = 'Submit Application';
        document.getElementById('lm_appID').value            = '';
    }

    modal.classList.add('show');
}

function closeLeaveModal() {
    var modal = document.getElementById('leaveModal');
    if (modal) modal.classList.remove('show');
}

// ─────────────────────────────────────────────
// RESET FORM
// ─────────────────────────────────────────────
function resetLeaveForm() {

    document.getElementById('lm_appID').value     = '';
    document.getElementById('lm_leaveType').value = '';

    document.querySelectorAll('input[name="lm_detail"]').forEach(function (r) {
        r.checked = false;
    });
    document.querySelectorAll('.lm-detail-input').forEach(function (i) {
        i.value = '';
        i.classList.remove('error');
    });

    document.getElementById('lm_nod').value   = '';
    document.getElementById('lm_dates').value = '';

    // Reset calendar state
    lmCalSelectedDates = [];
    lmCalViewYear  = new Date().getFullYear();
    lmCalViewMonth = new Date().getMonth();

    // Reset half-day radio
    var amR = document.getElementById('lmHalfAM');
    var pmR = document.getElementById('lmHalfPM');
    if (amR) amR.checked = false;
    if (pmR) pmR.checked = false;

    var halfRow = document.getElementById('lmCalHalfRow');
    if (halfRow) halfRow.style.display = 'none';

    var preview = document.getElementById('lmCalPreview');
    if (preview) preview.style.display = 'none';

    var setBtn = document.getElementById('lmCalSetBtn');
    if (setBtn) setBtn.disabled = true;

    var countEl = document.getElementById('lmCalCount');
    if (countEl) countEl.textContent = '0 day(s) selected';

    lmHideAlert();
    lmClearErrors();
}

// ─────────────────────────────────────────────
// ALERTS & ERRORS
// ─────────────────────────────────────────────
function lmClearErrors() {
    document.querySelectorAll('.error').forEach(function (el) {
        el.classList.remove('error');
    });
}

function lmShowAlert(msg, type) {
    type = type || 'err';
    var el = document.getElementById('lmAlert');
    el.textContent   = msg;
    el.className     = 'lm-alert ' + type;
    el.style.display = 'block';
}

function lmHideAlert() {
    var el = document.getElementById('lmAlert');
    if (!el) return;
    el.style.display = 'none';
    el.className     = 'lm-alert';
}

// ═══════════════════════════════════════════════════════════════
//  INLINE MULTI-DATE CALENDAR
// ═══════════════════════════════════════════════════════════════

var lmCalSelectedDates = [];   // array of "YYYY-MM-DD" strings
var lmCalViewYear  = new Date().getFullYear();
var lmCalViewMonth = new Date().getMonth();   // 0-based

var LM_MONTH_NAMES = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

/** Call once on DOMContentLoaded to paint the initial calendar */
function lmCalInit() {
    lmCalViewYear  = new Date().getFullYear();
    lmCalViewMonth = new Date().getMonth();
    lmCalRender();
}

/** Render the calendar grid for the current view month */
function lmCalRender() {

    var label = document.getElementById('lmCalLabel');
    var grid  = document.getElementById('lmCalDays');
    if (!label || !grid) return;

    label.textContent = LM_MONTH_NAMES[lmCalViewMonth] + ' ' + lmCalViewYear;

    var firstDay  = new Date(lmCalViewYear, lmCalViewMonth, 1).getDay(); // 0=Sun
    var daysInMon = new Date(lmCalViewYear, lmCalViewMonth + 1, 0).getDate();
    var today     = lmCalTodayStr();

    var html = '';

    // Empty cells before day 1
    for (var e = 0; e < firstDay; e++) {
        html += '<div class="lm-cal-day lm-cal-day--empty"></div>';
    }

    for (var d = 1; d <= daysInMon; d++) {
        var dateStr  = lmCalPad(lmCalViewYear) + '-'
                     + lmCalPad(lmCalViewMonth + 1) + '-'
                     + lmCalPad(d);
        var dow      = new Date(lmCalViewYear, lmCalViewMonth, d).getDay();
        var isWknd   = (dow === 0 || dow === 6);
        var isToday  = (dateStr === today);
        var isSelec  = lmCalSelectedDates.indexOf(dateStr) !== -1;

        var cls = 'lm-cal-day';
        if (isWknd)  cls += ' lm-cal-day--weekend';
        if (isToday) cls += ' lm-cal-day--today';
        if (isSelec) cls += ' lm-cal-day--selected';

        var onclick = isWknd
            ? ''
            : ' onclick="lmCalToggle(\'' + dateStr + '\')"';

        html += '<div class="' + cls + '"' + onclick + '>' + d + '</div>';
    }

    grid.innerHTML = html;
}

/** Toggle a date in the selection */
function lmCalToggle(dateStr) {
    var idx = lmCalSelectedDates.indexOf(dateStr);
    if (idx === -1) {
        lmCalSelectedDates.push(dateStr);
    } else {
        lmCalSelectedDates.splice(idx, 1);
    }
    lmCalSelectedDates.sort();
    lmCalRender();
    lmCalUpdateSummary();
}

/** Move the view by ±1 month */
function lmCalMove(dir) {
    lmCalViewMonth += dir;
    if (lmCalViewMonth > 11) { lmCalViewMonth = 0;  lmCalViewYear++; }
    if (lmCalViewMonth < 0)  { lmCalViewMonth = 11; lmCalViewYear--; }
    lmCalRender();
}

/** Update count label, preview, half-day row, and SET button state */
function lmCalUpdateSummary() {

    var n         = lmCalSelectedDates.length;
    var halfRow   = document.getElementById('lmCalHalfRow');
    var preview   = document.getElementById('lmCalPreview');
    var previewTx = document.getElementById('lmCalPreviewText');
    var countEl   = document.getElementById('lmCalCount');
    var setBtn    = document.getElementById('lmCalSetBtn');

    // Show half-day option only when exactly 1 date is selected
    if (halfRow) {
        halfRow.style.display = (n === 1) ? 'flex' : 'none';
        if (n !== 1) {
            // Clear half-day radios if deselected
            var amR = document.getElementById('lmHalfAM');
            var pmR = document.getElementById('lmHalfPM');
            if (amR) amR.checked = false;
            if (pmR) pmR.checked = false;
        }
    }

    // Compute effective days (0.5 for half-day, else count)
    var halfChecked = document.querySelector('input[name="lm_half"]:checked');
    var isHalf = (n === 1 && halfChecked);
    var days   = isHalf ? 0.5 : n;

    if (n === 0) {
        if (countEl) countEl.textContent = '0 day(s) selected';
        if (preview) preview.style.display = 'none';
        if (setBtn)  setBtn.disabled = true;
        return;
    }

    if (countEl) {
        countEl.textContent = days + ' day' + (days !== 1 ? 's' : '') + ' selected';
    }

    // Generate inclusive-dates caption
    var caption = lmCalBuildCaption(isHalf, halfChecked ? halfChecked.value : null);
    if (previewTx) previewTx.textContent = caption;
    if (preview)   preview.style.display = 'flex';
    if (setBtn)    setBtn.disabled = false;
}

/**
 * Build the human-readable caption for the inclusive dates field.
 * Groups consecutive dates in the same month into ranges.
 * e.g. "January 6-8, 10, 2025" or "January 6, 2025 (AM)"
 */
function lmCalBuildCaption(isHalf, halfSuffix) {

    if (lmCalSelectedDates.length === 0) return '';

    // Group by year-month
    var byMonth = {};
    lmCalSelectedDates.forEach(function (ds) {
        var parts = ds.split('-');
        var key   = parts[0] + '-' + parts[1];
        if (!byMonth[key]) byMonth[key] = [];
        byMonth[key].push(parseInt(parts[2], 10));
    });

    var keys    = Object.keys(byMonth).sort();
    var parts   = [];

    keys.forEach(function (key) {
        var yp    = key.split('-');
        var year  = yp[0];
        var month = parseInt(yp[1], 10);
        var days  = byMonth[key].slice().sort(function(a,b){ return a-b; });

        // Build ranges: e.g. [6,7,8,10] → "6-8, 10"
        var ranges = [];
        var start  = days[0], end = days[0];
        for (var i = 1; i < days.length; i++) {
            if (days[i] === end + 1) {
                end = days[i];
            } else {
                ranges.push(start === end ? String(start) : start + '-' + end);
                start = end = days[i];
            }
        }
        ranges.push(start === end ? String(start) : start + '-' + end);

        parts.push(LM_MONTH_NAMES[month - 1] + ' ' + ranges.join(', ') + ', ' + year);
    });

    var caption = parts.join('; ');
    if (isHalf && halfSuffix) caption += ' (' + halfSuffix + ')';
    return caption;
}

/** Apply selection → fill the NOD and Inclusive Dates fields */
function lmCalApply() {

    var n = lmCalSelectedDates.length;
    if (n === 0) return;

    var halfChecked = document.querySelector('input[name="lm_half"]:checked');
    var isHalf = (n === 1 && halfChecked);
    var days   = isHalf ? 0.5 : n;

    var caption = lmCalBuildCaption(isHalf, halfChecked ? halfChecked.value : null);

    document.getElementById('lm_nod').value   = days;
    document.getElementById('lm_dates').value = caption;

    // Visual feedback on the SET button
    var btn = document.getElementById('lmCalSetBtn');
    var orig = btn.textContent;
    btn.textContent = '✔ Applied!';
    btn.style.background = 'linear-gradient(135deg,#06d6a0,#00b894)';
    setTimeout(function () {
        btn.textContent = '✔ Set Dates & Days';
        btn.style.background = '';
    }, 1400);

    lmHideAlert();
    document.getElementById('lm_nod').classList.remove('error');
    document.getElementById('lm_dates').classList.remove('error');
}

/** Clear all selected dates */
function lmCalClearAll() {
    lmCalSelectedDates = [];

    var amR = document.getElementById('lmHalfAM');
    var pmR = document.getElementById('lmHalfPM');
    if (amR) amR.checked = false;
    if (pmR) pmR.checked = false;

    document.getElementById('lm_nod').value   = '';
    document.getElementById('lm_dates').value = '';

    lmCalRender();
    lmCalUpdateSummary();
}

// ── Helpers ──────────────────────────────────────────────────
function lmCalTodayStr() {
    var t = new Date();
    return lmCalPad(t.getFullYear()) + '-' + lmCalPad(t.getMonth() + 1) + '-' + lmCalPad(t.getDate());
}
function lmCalPad(n) { return String(n).padStart(2, '0'); }

// ═══════════════════════════════════════════════════════════════
//  SUBMIT  (INSERT or UPDATE)
// ═══════════════════════════════════════════════════════════════
async function submitLeave() {

    lmHideAlert();
    lmClearErrors();

    var valid = true;

    var appID     = document.getElementById('lm_appID').value;
    var leaveType = document.getElementById('lm_leaveType').value;
    var nod       = document.getElementById('lm_nod').value;
    var dates     = document.getElementById('lm_dates').value.trim();

    if (!leaveType) {
        document.getElementById('lm_leaveType').classList.add('error');
        lmShowAlert('Please select leave type.');
        valid = false;
    }

    var selectedRadio = document.querySelector('input[name="lm_detail"]:checked');

    if (!selectedRadio) {
        if (valid) lmShowAlert('Please select leave details.');
        valid = false;
    }

    var detailC = null;

    if (selectedRadio) {
        var needsInput  = selectedRadio.dataset.needsInput === '1';
        var radioRow    = selectedRadio.closest('.lm-radio-row');
        var detailInput = radioRow ? radioRow.querySelector('.lm-detail-input') : null;

        if (needsInput && detailInput) {
            if (detailInput.value.trim() === '') {
                detailInput.classList.add('error');
                if (valid) lmShowAlert('Please complete the required detail.');
                valid = false;
            } else {
                detailC = detailInput.value.trim();
            }
        } else if (detailInput) {
            detailC = detailInput.value.trim() || null;
        }
    }

    if (!nod || parseFloat(nod) <= 0) {
        document.getElementById('lm_nod').classList.add('error');
        if (valid) lmShowAlert('Please select dates using the calendar above, then click "Set Dates & Days".');
        valid = false;
    }

    if (!dates) {
        document.getElementById('lm_dates').classList.add('error');
        if (valid) lmShowAlert('Please select dates using the calendar above, then click "Set Dates & Days".');
        valid = false;
    }

    if (!valid) return;

    var groupMap = {
        vacation: 'In case of Vacation/Special Privelege Leave',
        sick:     'In case of Sick Leave',
        women:    'In case of Special Leave Benefits for Women',
        study:    'In case of Study Leave',
        other:    'Other purpose'
    };

    var dolA = groupMap[selectedRadio.dataset.group] || '';
    var dolB = selectedRadio.value;

    var btn     = document.getElementById('lmSubmitBtn');
    var spinner = document.getElementById('lmSpinner');
    btn.disabled          = true;
    spinner.style.display = 'inline-block';

    try {

        // Convert selected dates into MM/DD/YYYY,MM/DD/YYYY format
        var leaveDate = lmCalSelectedDates.map(function(dateStr) {

            // dateStr is YYYY-MM-DD
            var p = dateStr.split('-');

            return p[1] + '/' + p[2] + '/' + p[0];

        }).join(',');

        var payload = {
            app_ID:          appID ? parseInt(appID) : null,
            lt_ID:           leaveType,
            dol_a:           dolA,
            dol_b:           dolB,
            dol_c:           detailC,
            nod:             parseFloat(nod),
            inclusive_dates: dates,
            leave_date:      leaveDate
        };

        var res  = await fetch('./modules/leave/submit-leave.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });

        var data = await res.json();

        if (data.success) {

            lmShowAlert(
                appID ? 'Leave application updated successfully!' : 'Leave application submitted successfully!',
                'ok'
            );

            if (appID) {
                lmPatchRow(data.row);
            } else {
                lmPrependRow(data.row);
            }

            setTimeout(closeLeaveModal, 1200);

        } else {
            lmShowAlert(data.message || 'Submission failed.');
        }

    } catch (err) {
        console.error(err);
        lmShowAlert('Server error. Please try again.');
    } finally {
        btn.disabled          = false;
        spinner.style.display = 'none';
    }
}

// ─────────────────────────────────────────────
// DOM HELPERS
// ─────────────────────────────────────────────

function lmStatusClass(status) {
    if (status === 'Approved')    return 'status-approved';
    if (status === 'Disapproved') return 'status-disapproved';
    return 'status-pending';
}

function lmBuildActionCell(row) {

    var isPending = (row.status === 'Pending Approval');
    var printBtn = '<button class="icon-btn btn-print" onclick="printLeave(' + row.app_ID + ')" title="Print Leave Application">🖨️</button>';

    if (isPending) {
        var encoded = JSON.stringify(row.edit_data)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;');
        return '<div class="action-buttons">'
            + '<button class="icon-btn btn-edit" data-row="' + encoded + '" onclick="lmEditClick(this)">✏️</button>'
            + '<button class="icon-btn btn-delete" onclick="deleteLeave(' + row.app_ID + ')">🗑️</button>'
            + printBtn
            + '</div>';
    }

    return '<div class="action-buttons">'
        + '<button class="icon-btn btn-edit" disabled>✏️</button>'
        + '<button class="icon-btn btn-delete" disabled>🗑️</button>'
        + printBtn
        + '</div>';
}

// ─────────────────────────────────────────────
// CARD HELPERS
// ─────────────────────────────────────────────

function lmEscape(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function lmBuildCard(row) {

    var statusClass = lmStatusClass(row.status || '');
    var isPending   = (row.status || '').toLowerCase() === 'pending approval';

    var encoded = JSON.stringify(row.edit_data || {})
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;');

    var remarksHtml = row.remarks
        ? `
            <div class="leave-item-remarks">
                <span class="remark-label">Remarks:</span>
                ${lmEscape(row.remarks)}
            </div>
        `
        : '';

    var editBtn = isPending
        ? `
            <button class="lv-btn lv-btn-edit"
                    data-row="${encoded}"
                    onclick="lmEditClick(this)">
                ✏️ Edit
            </button>
        `
        : `
            <button class="lv-btn lv-btn-edit" disabled>
                ✏️ Edit
            </button>
        `;

    var deleteBtn = isPending
        ? `
            <button class="lv-btn lv-btn-delete"
                    onclick="deleteLeave(${row.app_ID})">
                🗑️ Delete
            </button>
        `
        : `
            <button class="lv-btn lv-btn-delete" disabled>
                🗑️ Delete
            </button>
        `;

    return `
        <div class="leave-item" id="leave-row-${row.app_ID}">

            <div class="leave-item-top">
                <div class="leave-item-type">
                    ${lmEscape(row.leave_description)}
                </div>

                <span class="status-chip ${statusClass}">
                    ${lmEscape(row.status)}
                </span>
            </div>

            <div class="leave-item-meta">

                <div class="leave-meta-pill">
                    <span class="meta-icon">📅</span>
                    <span>
                        Filed:
                        <strong>${lmEscape(row.dof_formatted)}</strong>

                        <span style="opacity:.5;font-size:.72rem;">
                            &nbsp;${lmEscape(row.tof)}
                        </span>
                    </span>
                </div>

                <div class="leave-meta-pill">
                    <span class="meta-icon">🗓️</span>
                    <span>
                        Inclusive:
                        <strong>${lmEscape(row.inclusive_dates)}</strong>
                    </span>
                </div>

                <div class="leave-meta-pill">
                    <span class="meta-icon">⏱️</span>
                    <span>
                        Days:
                        <strong>${lmEscape(row.nod)}</strong>
                    </span>
                </div>

            </div>

            ${remarksHtml}

            <div class="leave-item-actions">

                ${editBtn}

                ${deleteBtn}

                <button class="lv-btn lv-btn-print"
                        onclick="printLeave(${row.app_ID})"
                        title="Print Leave Application">
                    🖨️ Print
                </button>

            </div>

        </div>
    `;
}

// ─────────────────────────────────────────────
// UPDATE EXISTING CARD
// ─────────────────────────────────────────────
function lmPatchRow(row) {

    var oldCard = document.getElementById('leave-row-' + row.app_ID);
    if (!oldCard) return;

    var wrapper = document.createElement('div');
    wrapper.innerHTML = lmBuildCard(row);

    var newCard = wrapper.firstElementChild;

    oldCard.replaceWith(newCard);

    newCard.style.transition = 'background .4s';
    newCard.style.background = 'rgba(14,165,160,.15)';

    setTimeout(function () {
        newCard.style.background = '';
    }, 1400);
}

// ─────────────────────────────────────────────
// ADD NEW CARD
// ─────────────────────────────────────────────
function lmPrependRow(row) {

    var list = document.getElementById('leaveTableBody');
    if (!list) return;

    // remove empty state if exists
    var empty = list.querySelector('.leave-empty');
    if (empty) empty.remove();

    var wrapper = document.createElement('div');
    wrapper.innerHTML = lmBuildCard(row);

    var card = wrapper.firstElementChild;

    list.insertBefore(card, list.firstChild);

    card.style.transition = 'background .4s';
    card.style.background = 'rgba(14,165,160,.15)';

    setTimeout(function () {
        card.style.background = '';
    }, 1400);
}

// ─────────────────────────────────────────────
// DELETE
// ─────────────────────────────────────────────
function deleteLeave(appID) {

    var row = document.getElementById('leave-row-' + appID);
    if (!row) return;

    // CARD VALUES
    var particularsEl = row.querySelector('.leave-item-type');

    var metaPills = row.querySelectorAll('.leave-meta-pill strong');

    var dates = '';
    var nod   = '';

    if (metaPills[0]) {
        // first strong = filed date
    }

    if (metaPills[1]) {
        dates = metaPills[1].innerText;
    }

    if (metaPills[2]) {
        nod = metaPills[2].innerText;
    }

    document.getElementById('del_appID').value = appID;

    document.getElementById('del_particulars').innerText =
        particularsEl ? particularsEl.innerText : '';

    document.getElementById('del_nod').innerText = nod;
    document.getElementById('del_dates').innerText = dates;

    document.getElementById('deleteLeaveModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteLeaveModal').classList.remove('show');
}

async function confirmDeleteLeave() {

    var appID   = document.getElementById('del_appID').value;
    var btn     = document.querySelector('#deleteLeaveModal .lm-btn-submit');
    var spinner = document.getElementById('delSpinner');

    btn.disabled = true;
    spinner.style.display = 'inline-block';

    try {

        var res = await fetch('./modules/leave/delete-leave.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ app_ID: appID })
        });

        var data = await res.json();

        if (data.success) {
            var tr = document.getElementById('leave-row-' + appID);
            if (tr) {
                tr.style.transition = 'opacity .3s';
                tr.style.opacity    = '0';
                setTimeout(() => tr.remove(), 300);
            }
            closeDeleteModal();
        } else {
            alert(data.message || 'Delete failed.');
        }

    } catch (err) {
        console.error(err);
        alert('Server error.');
    }

    btn.disabled = false;
    spinner.style.display = 'none';
}

function printLeave(appID) {
    window.open(
        'modules/leave/leave-print.php?app_ID=' + appID,
        'leave_print_' + appID,
        'width=870,height=960,scrollbars=yes,resizable=yes'
    );
}