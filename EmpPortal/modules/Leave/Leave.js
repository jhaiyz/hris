console.log("Leave module loaded");

// ─────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    var leaveModal = document.getElementById('leaveModal');

    if (leaveModal) {
        leaveModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeLeaveModal();
            }
        });
    }

});

// ─────────────────────────────────────────────
// EDIT CLICK ENTRY POINT
// Called via onclick="lmEditClick(this)" on the edit button.
// Receives the button element as a real argument — avoids the
// `this === window` trap in onclick="fn(this.dataset.x)".
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

    resetLeaveForm();

    if (row) {
        // ── EDIT MODE ──────────────────────────────────
        document.getElementById('lmModalTitle').textContent  = 'Modify Leave Application';
        document.getElementById('lmSubmitLabel').textContent = 'Save Changes';
        document.getElementById('lm_appID').value            = row.app_ID;

        // Leave type dropdown
        document.getElementById('lm_leaveType').value = row.lt_ID;

        // Find the matching radio and check it
        var radios = document.querySelectorAll('input[name="lm_detail"]');
        radios.forEach(function (radio) {
            if (radio.value === row.dol_b) {
                radio.checked = true;

                // Fill companion text input with dol_c
                var radioRow    = radio.closest('.lm-radio-row');
                var detailInput = radioRow ? radioRow.querySelector('.lm-detail-input') : null;
                if (detailInput) {
                    detailInput.value = row.dol_c || '';
                }
            }
        });

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
    if (modal) {
        modal.classList.remove('show');
    }

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
    el.style.display = 'none';
    el.className     = 'lm-alert';
}

// ─────────────────────────────────────────────
// SUBMIT  (INSERT or UPDATE)
// ─────────────────────────────────────────────
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
        if (valid) lmShowAlert('Invalid number of days.');
        valid = false;
    }

    if (!dates) {
        document.getElementById('lm_dates').classList.add('error');
        if (valid) lmShowAlert('Inclusive dates required.');
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

        var payload = {
            app_ID:          appID ? parseInt(appID) : null,
            lt_ID:           leaveType,
            dol_a:           dolA,
            dol_b:           dolB,
            dol_c:           detailC,
            nod:             parseFloat(nod),
            inclusive_dates: dates
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

function lmPatchRow(row) {

    var tr = document.getElementById('leave-row-' + row.app_ID);
    if (!tr) return;

    tr.querySelector('td:nth-child(1)').innerHTML   = lmBuildActionCell(row);
    tr.querySelector('td:nth-child(3)').textContent = row.leave_description;
    tr.querySelector('td:nth-child(4)').textContent = row.nod;
    tr.querySelector('td:nth-child(5)').textContent = row.inclusive_dates;
    tr.querySelector('td:nth-child(6)').innerHTML   =
        '<span class="status-chip ' + lmStatusClass(row.status) + '">' + row.status + '</span>';
    tr.querySelector('td:nth-child(7)').textContent = row.remarks || '—';

    tr.style.transition = 'background 0.4s';
    tr.style.background = 'rgba(14,165,160,0.15)';
    setTimeout(function () { tr.style.background = ''; }, 1400);

}

function lmPrependRow(row) {

    var tbody = document.getElementById('leaveTableBody');
    if (!tbody) return;

    var tr = document.createElement('tr');
    tr.id  = 'leave-row-' + row.app_ID;

    tr.innerHTML =
        '<td>' + lmBuildActionCell(row) + '</td>'
        + '<td>' + row.dof_formatted + '<br><small>' + row.tof + '</small></td>'
        + '<td>' + row.leave_description + '</td>'
        + '<td>' + row.nod + '</td>'
        + '<td>' + row.inclusive_dates + '</td>'
        + '<td><span class="status-chip ' + lmStatusClass(row.status) + '">' + row.status + '</span></td>'
        + '<td>' + (row.remarks || '—') + '</td>';

    tbody.insertBefore(tr, tbody.firstChild);

    tr.style.transition = 'background 0.4s';
    tr.style.background = 'rgba(14,165,160,0.15)';
    setTimeout(function () { tr.style.background = ''; }, 1400);

}

// ─────────────────────────────────────────────
// DELETE
// ─────────────────────────────────────────────
function deleteLeave(appID) {

    var row = document.getElementById('leave-row-' + appID);
    if (!row) return;

    // extract values from table row
    var particulars = row.querySelector('td:nth-child(3)').innerText;
    var nod         = row.querySelector('td:nth-child(4)').innerText;
    var dates       = row.querySelector('td:nth-child(5)').innerText;

    // fill modal
    document.getElementById('del_appID').value = appID;
    document.getElementById('del_particulars').innerText = particulars;
    document.getElementById('del_nod').innerText = nod;
    document.getElementById('del_dates').innerText = dates;

    // show modal
    document.getElementById('deleteLeaveModal').classList.add('show');
}
function closeDeleteModal() {
    document.getElementById('deleteLeaveModal').classList.remove('show');
}
async function confirmDeleteLeave() {

    var appID = document.getElementById('del_appID').value;

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
                tr.style.opacity = '0';
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