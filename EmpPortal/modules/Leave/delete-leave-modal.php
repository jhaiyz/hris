<!-- DELETE LEAVE MODAL -->
        <div id="deleteLeaveModal" class="leave-modal-backdrop">

            <div class="leave-modal" style="max-width: 520px;">

                <div class="lm-header">
                    <div>
                        <div class="lm-badge" style="background: rgba(255,107,107,.12); border-color: rgba(255,107,107,.3); color:#ff7b7b;">
                            ⚠ Confirm Delete
                        </div>
                        <div class="lm-title" style="font-size: 1.3rem;">
                            Delete Leave Application
                        </div>
                    </div>

                    <button class="lm-close" onclick="closeDeleteModal()">
                        ✕
                    </button>
                </div>

                <div class="lm-body">

                    <input type="hidden" id="del_appID">

                    <div class="lm-section">
                        <div class="lm-section-label">Leave Details</div>

                        <div class="lm-detail-group">
                            <div class="lm-group-title">Particulars</div>
                            <div id="del_particulars" style="color:#fff; font-size:.9rem;"></div>
                        </div>

                        <div class="lm-detail-group">
                            <div class="lm-group-title">No. of Days</div>
                            <div id="del_nod" style="color:#fff;"></div>
                        </div>

                        <div class="lm-detail-group lm-detail-group--last">
                            <div class="lm-group-title">Inclusive Dates</div>
                            <div id="del_dates" style="color:#fff;"></div>
                        </div>
                    </div>

                    <div class="lm-alert err" id="delAlert"></div>

                </div>

                <div class="lm-footer">

                    <button class="lm-btn-cancel" onclick="closeDeleteModal()">
                        Cancel
                    </button>

                    <button class="lm-btn-submit" style="background: linear-gradient(135deg,#ff6b6b,#ff8d8d);" onclick="confirmDeleteLeave()">
                        <span id="delSpinner" class="lm-spinner"></span>
                        Delete
                    </button>

                </div>

            </div>

        </div>