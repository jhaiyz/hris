console.log("Leave module loaded");

// ─────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    const leaveModal = document.getElementById('leaveModal');

    if (leaveModal) {
        leaveModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeLeaveModal();
            }
        });
    }

});

// ─────────────────────────────────────────────
// MODAL
// ─────────────────────────────────────────────
function openLeaveModal() {

    const modal = document.getElementById('leaveModal');

    if (modal) {
        modal.classList.add('show');
        resetLeaveForm();
    }

}

function closeLeaveModal() {

    const modal = document.getElementById('leaveModal');

    if (modal) {
        modal.classList.remove('show');
    }

}

// ─────────────────────────────────────────────
// RESET FORM
// ─────────────────────────────────────────────
function resetLeaveForm() {

    document.getElementById('lm_leaveType').value = '';

    document.querySelectorAll('input[name="lm_detail"]').forEach(r => {
        r.checked = false;
    });

    document.querySelectorAll('.lm-detail-input').forEach(i => {
        i.value = '';
        i.classList.remove('error');
    });

    document.getElementById('lm_nod').value = '';
    document.getElementById('lm_dates').value = '';

    lmHideAlert();
    lmClearErrors();

}

// ─────────────────────────────────────────────
// ERRORS
// ─────────────────────────────────────────────
function lmClearErrors() {

    document.querySelectorAll('.error').forEach(el => {
        el.classList.remove('error');
    });

}

function lmShowAlert(msg, type = 'err') {

    const el = document.getElementById('lmAlert');

    el.textContent = msg;
    el.className = 'lm-alert ' + type;
    el.style.display = 'block';

}

function lmHideAlert() {

    const el = document.getElementById('lmAlert');

    el.style.display = 'none';
    el.className = 'lm-alert';

}

// ─────────────────────────────────────────────
// SUBMIT
// ─────────────────────────────────────────────
async function submitLeave() {

    lmHideAlert();
    lmClearErrors();

    let valid = true;

    const leaveType = document.getElementById('lm_leaveType').value;
    const nod = document.getElementById('lm_nod').value;
    const dates = document.getElementById('lm_dates').value.trim();

    // Leave type
    if (!leaveType) {

        document.getElementById('lm_leaveType').classList.add('error');
        lmShowAlert('Please select leave type.');
        valid = false;

    }

    // Radio
    const selectedRadio = document.querySelector('input[name="lm_detail"]:checked');

    if (!selectedRadio) {

        if (valid) {
            lmShowAlert('Please select leave details.');
        }

        valid = false;

    }

    // Detail input
    let detailC = null;

    if (selectedRadio) {

        const needsInput = selectedRadio.dataset.needsInput === '1';

        const row = selectedRadio.closest('.lm-radio-row');

        const input = row ? row.querySelector('.lm-detail-input') : null;

        if (needsInput && input) {

            if (input.value.trim() === '') {

                input.classList.add('error');

                if (valid) {
                    lmShowAlert('Please complete the required detail.');
                }

                valid = false;

            } else {

                detailC = input.value.trim();

            }

        } else if (input) {

            detailC = input.value.trim();

        }

    }

    // NOD
    if (!nod || parseFloat(nod) <= 0) {

        document.getElementById('lm_nod').classList.add('error');

        if (valid) {
            lmShowAlert('Invalid number of days.');
        }

        valid = false;

    }

    // Dates
    if (!dates) {

        document.getElementById('lm_dates').classList.add('error');

        if (valid) {
            lmShowAlert('Inclusive dates required.');
        }

        valid = false;

    }

    if (!valid) return;

    // Group map
    const groupMap = {
        vacation: 'In case of Vacation/Special Privelege Leave',
        sick: 'In case of Sick Leave',
        women: 'In case of Special Leave Benefits for Women',
        study: 'In case of Study Leave',
        other: 'Other purpose'
    };

    const dolA = groupMap[selectedRadio.dataset.group] || '';
    const dolB = selectedRadio.value;

    // Button
    const btn = document.getElementById('lmSubmitBtn');
    const spinner = document.getElementById('lmSpinner');

    btn.disabled = true;
    spinner.style.display = 'inline-block';

    try {

        const payload = {
            lt_ID: leaveType,
            dol_a: dolA,
            dol_b: dolB,
            dol_c: detailC,
            nod: parseFloat(nod),
            inclusive_dates: dates
        };

        const res = await fetch('./modules/leave/submit-leave.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (data.success) {

            lmShowAlert('Leave application submitted successfully!', 'ok');

            setTimeout(() => {
                closeLeaveModal();
                location.reload();
            }, 1200);

        } else {

            lmShowAlert(data.message || 'Submission failed.');

        }

    } catch (err) {

        console.error(err);
        lmShowAlert('Server error.');

    } finally {

        btn.disabled = false;
        spinner.style.display = 'none';

    }

}