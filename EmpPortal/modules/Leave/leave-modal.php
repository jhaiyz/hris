<!-- ── NEW / EDIT LEAVE APPLICATION MODAL ──────────────────── -->
<div class="leave-modal-backdrop" id="leaveModal">
    <div class="leave-modal">

        <!-- Header -->
        <div class="lm-header">
            <div class="lm-title-wrap">
                <div class="lm-badge">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    Date of filing is real-time
                </div>
                <h2 class="lm-title" id="lmModalTitle">New Leave Application</h2>
            </div>
            <button class="lm-close" onclick="closeLeaveModal()">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>

        <div class="lm-body">

            <!-- Hidden field: tracks whether we are editing an existing application -->
            <input type="hidden" id="lm_appID" value="">

            <!-- 6.A TYPE OF LEAVE -->
            <div class="lm-section">
                <div class="lm-section-label">6.A — Type of Leave to Be Availed Of</div>
                <div class="lm-select-wrap">
                    <select id="lm_leaveType" onchange="lmClearErrors()">
                        <option value="">— Select leave type —</option>
                        <?php foreach ($leaveTypes as $lt): ?>
                            <option value="<?= $lt['lt_ID'] ?>"><?= htmlspecialchars($lt['Description']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="lm-select-arrow" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
                </div>
            </div>

            <!-- 6.B DETAILS OF LEAVE -->
            <div class="lm-section">
                <div class="lm-section-label">6.B — Details of Leave</div>

                <!-- Vacation / SPL -->
                <div class="lm-detail-group">
                    <div class="lm-group-title">In case of Vacation / Special Privilege Leave:</div>
                    <div class="lm-radio-row">
                        <label class="lm-radio-label">
                            <input type="radio" name="lm_detail" value="Within the Philippines" data-group="vacation" data-needs-input="0">
                            <span class="lm-radio-custom"></span>
                            Within the Philippines
                        </label>
                        <input type="text" class="lm-detail-input" id="lm_detail_0" placeholder="Specify destination…">
                    </div>
                    <div class="lm-radio-row">
                        <label class="lm-radio-label">
                            <input type="radio" name="lm_detail" value="Abroad (Specify)" data-group="vacation" data-needs-input="1">
                            <span class="lm-radio-custom"></span>
                            Abroad (Specify)
                        </label>
                        <input type="text" class="lm-detail-input" id="lm_detail_1" placeholder="Specify country/destination…">
                    </div>
                </div>

                <!-- Sick Leave -->
                <div class="lm-detail-group">
                    <div class="lm-group-title">In case of Sick Leave:</div>
                    <div class="lm-radio-row">
                        <label class="lm-radio-label">
                            <input type="radio" name="lm_detail" value="In Hospital (Specify Illness)" data-group="sick" data-needs-input="1">
                            <span class="lm-radio-custom"></span>
                            In Hospital (Specify Illness)
                        </label>
                        <input type="text" class="lm-detail-input" id="lm_detail_2" placeholder="Specify illness…">
                    </div>
                    <div class="lm-radio-row">
                        <label class="lm-radio-label">
                            <input type="radio" name="lm_detail" value="Out Patient (Specify Illness)" data-group="sick" data-needs-input="1">
                            <span class="lm-radio-custom"></span>
                            Out Patient (Specify Illness)
                        </label>
                        <input type="text" class="lm-detail-input" id="lm_detail_3" placeholder="Specify illness…">
                    </div>
                </div>

                <!-- Special Leave for Women -->
                <div class="lm-detail-group">
                    <div class="lm-group-title">In case of Special Leave Benefits for Women:</div>
                    <div class="lm-radio-row">
                        <label class="lm-radio-label">
                            <input type="radio" name="lm_detail" value="(Specify Illness)" data-group="women" data-needs-input="1">
                            <span class="lm-radio-custom"></span>
                            (Specify Illness)
                        </label>
                        <input type="text" class="lm-detail-input" id="lm_detail_4" placeholder="Specify illness…">
                    </div>
                </div>

                <!-- Study Leave -->
                <div class="lm-detail-group">
                    <div class="lm-group-title">In case of Study Leave:</div>
                    <div class="lm-radio-row lm-radio-row--inline">
                        <label class="lm-radio-label">
                            <input type="radio" name="lm_detail" value="Completion of Master's Degree" data-group="study" data-needs-input="0">
                            <span class="lm-radio-custom"></span>
                            Completion of Master's Degree
                        </label>
                        <label class="lm-radio-label">
                            <input type="radio" name="lm_detail" value="BAR/Board Examination Review" data-group="study" data-needs-input="0">
                            <span class="lm-radio-custom"></span>
                            BAR / Board Examination Review
                        </label>
                    </div>
                </div>

                <!-- Other Purpose -->
                <div class="lm-detail-group lm-detail-group--last">
                    <div class="lm-group-title">Other purpose:</div>
                    <div class="lm-radio-row lm-radio-row--inline">
                        <label class="lm-radio-label">
                            <input type="radio" name="lm_detail" value="Monetization of Leave Credits" data-group="other" data-needs-input="0">
                            <span class="lm-radio-custom"></span>
                            Monetization of Leave Credits
                        </label>
                        <label class="lm-radio-label">
                            <input type="radio" name="lm_detail" value="Terminal Leave" data-group="other" data-needs-input="0">
                            <span class="lm-radio-custom"></span>
                            Terminal Leave
                        </label>
                    </div>
                </div>
            </div>

            <!-- 6.C NUMBER OF DAYS -->
            <div class="lm-section">
                <div class="lm-section-label">6.C — Number of Working Days Applied For</div>
                <div class="lm-row-two">
                    <div class="lm-field">
                        <label class="lm-label">Number of Days</label>
                        <input type="number" id="lm_nod" min="0.5" step="0.5" placeholder="e.g. 1" class="lm-input">
                    </div>
                    <div class="lm-field lm-field--wide">
                        <label class="lm-label">Inclusive Dates</label>
                        <input type="text" id="lm_dates" placeholder="e.g. January 10-11, 2025" class="lm-input">
                    </div>
                </div>
            </div>

            <!-- Alert -->
            <div class="lm-alert" id="lmAlert"></div>

        </div><!-- end lm-body -->

        <!-- Footer -->
        <div class="lm-footer">
            <button class="lm-btn-cancel" onclick="closeLeaveModal()">Close</button>
            <button class="lm-btn-submit" id="lmSubmitBtn" onclick="submitLeave()">
                <span class="lm-spinner" id="lmSpinner"></span>
                <span id="lmSubmitLabel">Submit Application</span>
            </button>
        </div>

    </div>
</div>