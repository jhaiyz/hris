<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../db.php';  // ✅ goes up two levels to hris/EmpPortal/

// --- Fetch leave record ---
$app_ID = isset($_GET['app_ID']) ? (int)$_GET['app_ID'] : 0;

if (!$app_ID) {
    die('Invalid leave application ID.');
}

$db = getDB();

$sql = "
    SELECT
        a.app_ID,
        a.DOF,
        a.TOF,
        a.NOD,
        a.Inclusive_Dates,
        a.Status,
        a.Remarks,
        a.DOL_A,
        a.DOL_B,
        a.DOL_C,
        a.lt_ID,
        a.COLC_By,
        a.Approved_By,
        l.Description AS LeaveDescription,
        e.emp_ID,
        e.Last_Name,
        e.First_Name,
        e.Middle_Name,
        e.Ext_Name,
        e.Position,
        sg.Salary_Grade,
        e.Office,
        cc.As_Of,
        cc.cur_VL_Bal,
        cc.less_VL_Bal,
        cc.cur_SL_Bal,
        cc.less_SL_Bal
    FROM tblappleave a
    LEFT JOIN tbl_lt       l  ON a.lt_ID  = l.lt_ID
    LEFT JOIN tblemp e  ON a.emp_ID = e.emp_ID
    LEFT JOIN tblsg        sg ON e.sg_ID  = sg.sg_ID
    LEFT JOIN tblcolc      cc ON a.app_ID = cc.app_ID
    WHERE a.app_ID = ?
    LIMIT 1
";

$stmt = $db->prepare($sql);
$stmt->bind_param('i', $app_ID);
$stmt->execute();
$res  = $stmt->get_result();
$row  = $res->fetch_assoc();
$stmt->close();
$db->close();

if (!$row) {
    die('Leave application not found.');
}

// ---- helpers ----
function val($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$extName    = trim($row['Ext_Name'] ?? '');
$nameParts  = trim($row['Last_Name'] . ', ' . $row['First_Name'] . ' ' . $row['Middle_Name']);
if ($extName) $nameParts .= ' ' . $extName;
$fullName   = val($nameParts);
$position   = val($row['Position']    ?? '');
$salaryGrade = val($row['Salary_Grade'] ?? '');
$department = val($row['Office']      ?? '');
$dof        = $row['DOF'] ? date('F d, Y', strtotime($row['DOF'])) : '';
$nod        = val($row['NOD']         ?? '');
$dates      = val($row['Inclusive_Dates'] ?? '');
$leaveType  = val($row['LeaveDescription'] ?? '');

// Certification / approval fields
$asOf      = $row['As_Of'] ? date('F d, Y', strtotime($row['As_Of'])) : '';
$colcBy    = val($row['COLC_By']     ?? '');
$approvedBy = val($row['Approved_By'] ?? '');

// Leave credits from tblcolc
$curVL   = val($row['cur_VL_Bal']  ?? '');
$lessVL  = val($row['less_VL_Bal'] ?? '');
$balVL   = (is_numeric($row['cur_VL_Bal'] ?? null) && is_numeric($row['less_VL_Bal'] ?? null))
           ? val($row['cur_VL_Bal'] - $row['less_VL_Bal']) : '';
$curSL   = val($row['cur_SL_Bal']  ?? '');
$lessSL  = val($row['less_SL_Bal'] ?? '');
$balSL   = (is_numeric($row['cur_SL_Bal'] ?? null) && is_numeric($row['less_SL_Bal'] ?? null))
           ? val($row['cur_SL_Bal'] - $row['less_SL_Bal']) : '';

// --- Map leave type to checkbox column ---
$leaveMap = [
    'Vacation Leave'                  => 'vacation',
    'Mandatory/Forced Leave'          => 'mandatory',
    'Sick Leave'                      => 'sick',
    'Maternity Leave'                 => 'maternity',
    'Paternity Leave'                 => 'paternity',
    'Special Privilege Leave'         => 'special_privilege',
    'Solo Parent Leave'               => 'solo_parent',
    'Study Leave'                     => 'study',
    '10-Day VAWC Leave'               => 'vawc',
    'Rehabilitation Privilege'        => 'rehabilitation',
    'Special Leave Benefits for Women'=> 'slbw',
    'Special Emergency (Calamity) Leave' => 'calamity',
    'Adoption Leave'                  => 'adoption',
];
$activeLeave = '';
foreach ($leaveMap as $label => $key) {
    if (stripos($row['LeaveDescription'] ?? '', $label) !== false) {
        $activeLeave = $key;
        break;
    }
}
if (!$activeLeave) $activeLeave = 'others';

// Commutation
$dol_c = strtolower($row['DOL_C'] ?? '');
$commutationRequested = (strpos($dol_c, 'request') !== false);

// Checkbox helper
function chk($cond) { return $cond ? '&#10003;' : '&nbsp;'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave Application — CS Form No. 6</title>
<style>
  /* ========================================================
     PRINT-FIRST LAYOUT — A4 portrait, single page fit
     ======================================================== */
  @import url('https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;1,400&family=DM+Mono:wght@400;500&display=swap');

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --black:  #0a0a0a;
    --light:  #ccc;
    --bg:     #f7f6f2;
    --ink:    #111;
    --accent: #1a3a6b;
  }

  body {
    background: var(--bg);
    font-family: 'Source Serif 4', Georgia, serif;
    color: var(--ink);
    font-size: 7.5pt;
    line-height: 1.25;
  }

  /* ---- Screen preview wrapper ---- */
  .page-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px 12px 32px;
    gap: 14px;
  }

  .print-btn-bar {
    display: flex;
    gap: 10px;
  }
  .print-btn-bar button {
    padding: 8px 22px;
    border: none;
    border-radius: 5px;
    font-family: 'DM Mono', monospace;
    font-size: 10pt;
    cursor: pointer;
    transition: background .15s;
  }
  .btn-print  { background: var(--accent); color: #fff; }
  .btn-print:hover  { background: #0e2550; }
  .btn-close  { background: #e0ddd6; color: #333; }
  .btn-close:hover  { background: #ccc; }

  /* ---- The A4 "paper" ---- */
  .form-paper {
    background: #fff;
    width: 210mm;
    height: 297mm;
    padding: 8mm 10mm 8mm;
    box-shadow: 0 4px 24px rgba(0,0,0,.18);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  /* ===== HEADER ===== */
  .form-header {
    display: grid;
    grid-template-columns: 88px 1fr 88px;
    align-items: stretch;
    border: 1.5px solid var(--black);
    flex-shrink: 0;
  }
  .form-header .cs-num {
    padding: 3px 5px;
    border-right: 1px solid var(--black);
    font-size: 6pt;
    font-family: 'DM Mono', monospace;
    line-height: 1.35;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
  }
  .form-header .cs-num .cs-label {
    text-align: center;
    font-size: 6pt;
  }
  .form-header .cs-num img.logo-img {
    width: 48px;
    height: 48px;
    object-fit: contain;
  }
  .form-header .title-block {
    text-align: center;
    padding: 4px 6px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .form-header .title-block .agency {
    font-size: 6.5pt;
    line-height: 1.4;
  }
  .form-header .title-block .form-title {
    font-size: 12pt;
    font-weight: 600;
    letter-spacing: .04em;
    color: var(--accent);
    margin-top: 2px;
  }
  .form-header .logo-block {
    border-left: 1px solid var(--black);
    padding: 4px 5px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  /* ===== GENERIC SECTION / CELL HELPERS ===== */
  .form-body {
    border: 1.5px solid var(--black);
    border-top: none;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .row-band {
    display: flex;
    border-bottom: 1px solid var(--black);
    flex-shrink: 0;
  }
  .row-band:last-child { border-bottom: none; }

  .cell {
    padding: 2px 4px;
    border-right: 1px solid var(--black);
    flex: 1;
  }
  .cell:last-child { border-right: none; }

  .cell-label {
    font-size: 6pt;
    font-family: 'DM Mono', monospace;
    color: #444;
    letter-spacing: .02em;
    display: block;
    margin-bottom: 1px;
  }
  .cell-value {
    font-size: 8pt;
    font-weight: 600;
    min-height: 11px;
    border-bottom: 1px solid var(--light);
    display: block;
    padding-bottom: 1px;
  }

  /* ===== SECTION HEADER ===== */
  .section-header {
    background: #e8e8e8;
    font-family: 'DM Mono', monospace;
    font-size: 6.5pt;
    font-weight: 600;
    padding: 2px 5px;
    border-bottom: 1px solid var(--black);
    letter-spacing: .03em;
    flex-shrink: 0;
  }

  /* ===== LEAVE TYPE / DETAILS GRID ===== */
  .leave-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid var(--black);
    flex-shrink: 0;
  }
  .ld-left {
    border-right: 1px solid var(--black);
    padding: 3px 5px;
  }
  .ld-right {
    padding: 3px 5px;
  }
  .ld-sub-label {
    font-family: 'DM Mono', monospace;
    font-size: 6.5pt;
    color: #333;
    font-weight: 500;
    margin: 2px 0 1px;
    border-bottom: 1px dotted #aaa;
    padding-bottom: 1px;
  }

  /* checkbox row */
  .chk-row {
    display: flex;
    align-items: flex-start;
    gap: 3px;
    margin: 1px 0;
    line-height: 1.25;
    font-size: 7pt;
  }
  .chk-box {
    width: 9px;
    min-width: 9px;
    height: 9px;
    border: 1px solid var(--black);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 7pt;
    line-height: 1;
    flex-shrink: 0;
    margin-top: 1px;
  }
  .chk-box.checked {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
  }

  .fill-line {
    border-bottom: 1px solid var(--black);
    display: block;
    min-height: 9px;
    margin-top: 1px;
    margin-bottom: 1px;
    font-size: 7pt;
    padding-bottom: 1px;
  }

  /* ===== NUMBER OF DAYS + COMMUTATION ===== */
  .nod-comm-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid var(--black);
    flex-shrink: 0;
  }
  .nod-cell {
    border-right: 1px solid var(--black);
    padding: 3px 5px;
  }
  .comm-cell { padding: 3px 5px; }

  .nod-value-big {
    font-size: 13pt;
    font-weight: 600;
    color: var(--accent);
    font-family: 'DM Mono', monospace;
    letter-spacing: .04em;
    border-bottom: 1px solid var(--black);
    display: block;
    min-height: 18px;
    line-height: 1.1;
    padding-bottom: 1px;
  }
  .dates-value {
    font-size: 7.5pt;
    min-height: 10px;
    border-bottom: 1px solid var(--black);
    margin-top: 1px;
    padding-bottom: 1px;
  }

  /* ===== SIGNATURE / LABEL ===== */
  .sig-label {
    font-size: 6pt;
    font-family: 'DM Mono', monospace;
    color: #555;
    letter-spacing: .02em;
  }

  /* ===== SECTION 7 — ACTION ===== */
  .action-header {
    background: #d0d0d0;
    font-family: 'DM Mono', monospace;
    font-size: 6.5pt;
    font-weight: 600;
    padding: 2px 5px;
    border-bottom: 1px solid var(--black);
    letter-spacing: .05em;
    text-transform: uppercase;
    flex-shrink: 0;
  }
  .action-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid var(--black);
    flex-shrink: 0;
  }
  .action-col {
    padding: 3px 5px;
    border-right: 1px solid var(--black);
  }
  .action-col:last-child { border-right: none; }

  .credits-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 6.5pt;
    margin-top: 2px;
  }
  .credits-table th {
    text-align: center;
    font-family: 'DM Mono', monospace;
    font-size: 6pt;
    border: 1px solid var(--black);
    padding: 1px 3px;
    background: #f0f0f0;
  }
  .credits-table td {
    border: 1px solid var(--black);
    padding: 2px 4px;
  }
  .credits-table .row-label {
    font-size: 6pt;
    background: #f8f8f8;
  }

  .approved-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid var(--black);
    flex-shrink: 0;
  }
  .approved-col {
    padding: 3px 5px;
    border-right: 1px solid var(--black);
  }
  .approved-col:last-child { border-right: none; }

  /* ===== FOOTER / SIGNATORIES ===== */
  .footer-sig-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-top: 1px solid var(--black);
    flex-shrink: 0;
  }
  .footer-sig-col {
    padding: 4px 8px 3px;
    border-right: 1px solid var(--black);
    text-align: center;
  }
  .footer-sig-col:last-child { border-right: none; }
  .footer-sig-name {
    font-weight: 600;
    font-size: 7.5pt;
    letter-spacing: .03em;
    border-top: 1px solid var(--black);
    padding-top: 2px;
    margin-top: 18px;
    display: block;
    text-transform: uppercase;
  }

  /* ===== PRINT OVERRIDES ===== */
  @media print {
    body { background: #fff; }
    .page-wrapper { padding: 0; background: #fff; }
    .print-btn-bar { display: none; }
    .form-paper {
      width: 210mm;
      height: 297mm;
      box-shadow: none;
      padding: 8mm 10mm 8mm;
    }
    @page {
      size: A4 portrait;
      margin: 0;
    }
  }
</style>
</head>
<body>
<div class="page-wrapper">

  <!-- Screen-only control bar -->
  <div class="print-btn-bar">
    <button class="btn-print" onclick="window.print()">🖨️ Print</button>
    <button class="btn-close" onclick="window.close()">✕ Close</button>
  </div>

  <!-- ============================================================
       THE FORM PAPER
       ============================================================ -->
  <div class="form-paper">

    <!-- HEADER -->
    <div class="form-header">
      <div class="cs-num">
        <img class="logo-img" src="../../modules/Leave/cdhlogo.jpg" alt="CDH Logo">
        <span class="cs-label">Civil Service Form No. 6<br>Revised 2020</span>
      </div>
      <div class="title-block">
        <div class="agency">
          Republic of the Philippines<br>
          <strong>CONNER DISTRICT HOSPITAL</strong><br>
          Ripang, Conner, Apayao
        </div>
        <div class="form-title">APPLICATION FOR LEAVE</div>
      </div>
      <div class="logo-block">
        <div style="border: 1.5px dashed #888; padding: 8px 6px; text-align: center; font-size: 7pt; font-family: 'DM Mono', monospace; color: #555; line-height: 1.5; min-height: 60px; display: flex; align-items: center; justify-content: center;">
          Stamp of Date<br>of Receipt
        </div>
      </div>
    </div><!-- /form-header -->

    <!-- FORM BODY -->
    <div class="form-body">

      <!-- Row 1: Office / Name -->
      <div class="row-band">
        <div class="cell" style="flex:1.2">
          <span class="cell-label">1. OFFICE / DEPARTMENT</span>
          <span class="cell-value"><?= val($department) ?></span>
        </div>
        <div class="cell" style="flex:2">
          <span class="cell-label">2. NAME &nbsp;(Last)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(First)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Middle)</span>
          <span class="cell-value"><?= $fullName ?></span>
        </div>
      </div>

      <!-- Row 2: Date filed / Position / Salary -->
      <div class="row-band">
        <div class="cell" style="flex:1.2">
          <span class="cell-label">3. DATE OF FILING</span>
          <span class="cell-value"><?= val($dof) ?></span>
        </div>
        <div class="cell" style="flex:1.2">
          <span class="cell-label">4. POSITION</span>
          <span class="cell-value"><?= $position ?></span>
        </div>
        <div class="cell" style="flex:.8">
          <span class="cell-label">5. SALARY GRADE</span>
          <span class="cell-value"><?= $salaryGrade ?></span>
        </div>
      </div>

      <!-- Section 6 header -->
      <div class="section-header">6. DETAILS OF APPLICATION</div>

      <!-- 6A + 6B grid -->
      <div class="leave-details-grid">

        <!-- 6A — Type of Leave -->
        <div class="ld-left">
          <div class="cell-label" style="font-size:8pt;font-weight:600;color:#111;margin-bottom:4px;">6.A &nbsp; TYPE OF LEAVE TO BE AVAILED OF</div>

          <?php
          $types = [
            ['vacation',         'Vacation Leave (Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)'],
            ['mandatory',        'Mandatory / Forced Leave (Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292)'],
            ['sick',             'Sick Leave (Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)'],
            ['maternity',        'Maternity Leave (R.A. No. 11210 / IRR issued by CSC, DOLE and SSS)'],
            ['paternity',        'Paternity Leave (R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended)'],
            ['special_privilege','Special Privilege Leave (Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292)'],
            ['solo_parent',      'Solo Parent Leave (R.A. No. 8972 / CSC MC No. 8, s. 2004)'],
            ['study',            'Study Leave (Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)'],
            ['vawc',             '10-Day VAWC Leave (R.A. No. 9262 / CSC MC No. 15, s. 2005)'],
            ['rehabilitation',   'Rehabilitation Privilege (Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292)'],
            ['slbw',             'Special Leave Benefits for Women (R.A. No. 9710 / CSC MC No. 25, s. 2010)'],
            ['calamity',         'Special Emergency (Calamity) Leave (CSC MC No. 2, s. 2012, as amended)'],
            ['adoption',         'Adoption Leave (R.A. No. 8552)'],
          ];
          foreach ($types as [$key, $label]):
            $isChecked = ($activeLeave === $key);
          ?>
          <div class="chk-row">
            <span class="chk-box <?= $isChecked ? 'checked' : '' ?>"><?= $isChecked ? '&#10003;' : '&nbsp;' ?></span>
            <span><?= htmlspecialchars($label) ?></span>
          </div>
          <?php endforeach; ?>

          <!-- Others -->
          <div class="chk-row" style="margin-top:4px;">
            <span class="chk-box <?= ($activeLeave === 'others') ? 'checked' : '' ?>"><?= ($activeLeave === 'others') ? '&#10003;' : '&nbsp;' ?></span>
            <span>Others: <span class="fill-line" style="display:inline-block;width:120px;vertical-align:bottom;"><?= ($activeLeave === 'others') ? val($leaveType) : '' ?></span></span>
          </div>
        </div><!-- /6A -->

        <!-- 6B — Details of Leave -->
        <div class="ld-right">
          <div class="cell-label" style="font-size:8pt;font-weight:600;color:#111;margin-bottom:4px;">6.B &nbsp; DETAILS OF LEAVE</div>

          <div class="ld-sub-label">In case of Vacation / Special Privilege Leave:</div>
          <div class="chk-row">
            <span class="chk-box">&nbsp;</span>
            <span>Within the Philippines <span class="fill-line" style="display:inline-block;width:80px;vertical-align:bottom;"></span></span>
          </div>
          <div class="chk-row">
            <span class="chk-box">&nbsp;</span>
            <span>Abroad (Specify) <span class="fill-line" style="display:inline-block;width:90px;vertical-align:bottom;"></span></span>
          </div>

          <div class="ld-sub-label" style="margin-top:8px;">In case of Sick Leave:</div>
          <div class="chk-row">
            <span class="chk-box">&nbsp;</span>
            <span>In Hospital (Specify Illness) <span class="fill-line" style="display:inline-block;width:60px;vertical-align:bottom;"></span></span>
          </div>
          <div class="chk-row">
            <span class="chk-box">&nbsp;</span>
            <span>Out Patient (Specify Illness) <span class="fill-line" style="display:inline-block;width:58px;vertical-align:bottom;"></span></span>
          </div>
          <span class="fill-line"></span>

          <div class="ld-sub-label" style="margin-top:8px;">In case of Special Leave Benefits for Women:</div>
          <span style="font-size:8pt;">(Specify Illness)</span>
          <span class="fill-line"></span>
          <span class="fill-line"></span>

          <div class="ld-sub-label" style="margin-top:8px;">In case of Study Leave:</div>
          <div class="chk-row">
            <span class="chk-box">&nbsp;</span>
            <span>Completion of Master's Degree</span>
          </div>
          <div class="chk-row">
            <span class="chk-box">&nbsp;</span>
            <span>BAR / Board Examination Review</span>
          </div>

          <div class="ld-sub-label" style="margin-top:8px;">Other purpose:</div>
          <div class="chk-row">
            <span class="chk-box">&nbsp;</span>
            <span>Monetization of Leave Credits</span>
          </div>
          <div class="chk-row">
            <span class="chk-box">&nbsp;</span>
            <span>Terminal Leave</span>
          </div>
        </div><!-- /6B -->

      </div><!-- /leave-details-grid -->

      <!-- 6C + 6D: Days + Commutation -->
      <div class="nod-comm-grid">
        <div class="nod-cell">
          <div class="cell-label" style="font-size:8pt;font-weight:600;color:#111;margin-bottom:4px;">6.C &nbsp; NUMBER OF WORKING DAYS APPLIED FOR</div>
          <span class="nod-value-big"><?= $nod ?> <small style="font-size:10pt;font-weight:400;color:#555;">day(s)</small></span>
          <div class="cell-label" style="margin-top:5px;">INCLUSIVE DATES</div>
          <div class="dates-value"><?= $dates ?></div>
        </div>
        <div class="comm-cell">
          <div class="cell-label" style="font-size:8pt;font-weight:600;color:#111;margin-bottom:6px;">6.D &nbsp; COMMUTATION</div>
          <div class="chk-row">
            <span class="chk-box">&nbsp;</span>
            <span>Not Requested</span>
          </div>
          <div class="chk-row" style="margin-top:4px;">
            <span class="chk-box checked">&#10003;</span>
            <span>Requested</span>
          </div>
          <!-- Applicant signature inside commutation box -->
          <div style="margin-top:14px;text-align:center;">
            <div style="min-height:15px;"></div>
            <div style="border-bottom:1px solid #000;width:90%;margin:0 auto 2px;"></div>
            <div class="sig-label">(Signature of Applicant)</div>
          </div>
        </div>
      </div>

      <!-- Section 7 header -->
      <div class="action-header">7. DETAILS OF ACTION ON APPLICATION</div>

      <!-- 7A + 7B -->
      <div class="action-grid">
        <!-- 7A: Leave Credits -->
        <div class="action-col">
          <div class="cell-label" style="font-size:8pt;font-weight:600;color:#111;margin-bottom:4px;">7.A &nbsp; CERTIFICATION OF LEAVE CREDITS</div>
          <div style="font-size:8pt;margin-bottom:4px;">As of <?= $asOf ?: '___________________________' ?></div>
          <table class="credits-table">
            <thead>
              <tr>
                <th></th>
                <th>Vacation Leave</th>
                <th>Sick Leave</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Total Earned</td>
                <td><?= $curVL ?></td>
                <td><?= $curSL ?></td>
              </tr>
              <tr>
                <td class="row-label">Less this application</td>
                <td><?= $lessVL ?></td>
                <td><?= $lessSL ?></td>
              </tr>
              <tr>
                <td class="row-label">Balance</td>
                <td><?= $balVL ?></td>
                <td><?= $balSL ?></td>
              </tr>
            </tbody>
          </table>
          <!-- Certifying officer signature -->
          <div style="margin-top:5px;text-align:center;">
              
              <!-- Space for actual signature -->
              <div style="height:20px;"></div>

              <!-- Signature line -->
              <div style="
                  width:90%;
                  margin:0 auto 4px;
                  border-bottom:1px solid #000;
              "></div>

              <!-- Name -->
              <div style="
                  font-weight:600;
                  font-size:8.5pt;
                  text-transform:uppercase;
                  letter-spacing:.03em;
              ">
                  <?= $colcBy ?>
              </div>

          </div>
        </div>

        <!-- 7B: Recommendation -->
        <div class="action-col">
          <div class="cell-label" style="font-size:8pt;font-weight:600;color:#111;margin-bottom:6px;">7.B &nbsp; RECOMMENDATION</div>
          <div class="chk-row">
             <span class="chk-box checked">&#10003;</span>
            <span>For approval</span>
          </div>
          <div class="chk-row" style="margin-top:5px;align-items:flex-start;">
            <span class="chk-box" style="margin-top:2px;">&nbsp;</span>
            <span>
              For disapproval due to<br>
              <span class="fill-line"></span>
              <span class="fill-line"></span>
              <span class="fill-line"></span>
              <span class="fill-line"></span>
            </span>
          </div>
          <!-- Recommending sig -->
          <div style="margin-top:10px;text-align:center;">
            <div class="sig-line" style="width:90%;margin:0 auto 2px;min-height:15px;border-bottom:1px solid #000;"></div>
            <div class="sig-label">(Authorized Officer)</div>
          </div>
        </div>
      </div><!-- /7A+7B -->

      <!-- 7C + 7D Combined -->
<div style="padding:5px 8px; border-top:none;">

    <div style="
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:20px;
    ">

        <!-- 7C -->
        <div>
            <div class="cell-label" style="font-size:8pt;font-weight:600;color:#111;margin-bottom:5px;">
                7.C &nbsp; APPROVED FOR:
            </div>

            <div style="font-size:8.5pt;line-height:1.8;">
                ________ days with pay<br>
                ________ days without pay<br>
                ________ others (Specify) __________________________
            </div>
        </div>

        <!-- 7D -->
        <div>
            <div class="cell-label" style="font-size:8pt;font-weight:600;color:#111;margin-bottom:5px;">
                7.D &nbsp; DISAPPROVED DUE TO:
            </div>

            <span class="fill-line"></span>
            <span class="fill-line"></span>
            <span class="fill-line"></span>
        </div>

    </div>

        <!-- Approving Authority -->
        <div style="
            text-align:center;
            margin-top:25px;
        ">

            <div style="height:25px;"></div>

            <span class="footer-sig-name" style="
                border-top:1.5px solid #000;
                padding-top:3px;
                display:inline-block;
                min-width:320px;
            ">
                <?= $approvedBy ?>
            </span>

        </div>

    </div>

    </div><!-- /form-body -->

    <!-- Footer note -->
    <div style="text-align:center;font-size:6.5pt;font-family:'DM Mono',monospace;color:#888;margin-top:6px;">
      CDH-HRM-FM-16
    </div>

  </div><!-- /form-paper -->

</div><!-- /page-wrapper -->

<script>
  // Auto-print when opened in a popup (optional — remove if you prefer manual)
  // window.onload = function() { window.print(); };
</script>
</body>
</html>