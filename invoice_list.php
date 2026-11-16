<?php
session_start();
include "config.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$shop = null;

$stmt = mysqli_prepare($con, "
    SELECT c.customer_name, i.* 
    FROM customers c 
    LEFT JOIN customer_import i ON c.customer_id = i.external_id 
    WHERE c.customer_id = ?
");
if($stmt){
    mysqli_stmt_bind_param($stmt, "s", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $shop = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$shop) {
    $shop = [
        'customer_name' => $_SESSION['customer_name'],
        'phone_number' => '-',
        'village' => '-',
        'district' => '-'
    ];
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ລາຍການອິນວອຍ</title>
<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.customer-header-boxes {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}
.customer-box {
    flex: 1;
    background-color: #f0f7ff;
    border: 1px solid #dbeafe;
    border-radius: 12px;
    padding: 12px 15px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.customer-box .top-line {
    font-weight: 600;
    color: #1e3a8a;
    font-size: 15px;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
}
.customer-box .bottom-line {
    color: #475569;
    font-size: 14px;
    text-align: center;
}
.filter-section {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
}
.filter-section .field {
    flex: 1;
    margin-bottom: 0;
}
.full-panel {
    max-width: 600px;
}
.action-row {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    min-width: 0;
}
.btn-secondary {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fca5a5;
    font-size: 16px;
    font-weight: 600;
    padding: 14px;
    border-radius: 8px;
    cursor: pointer;
    flex: 1;
    transition: all 0.2s;
}
.btn-secondary:hover { background: #fee2e2; }
.btn-success {
    background: #f0fdf4;
    color: #22c55e;
    border: 1px solid #86efac;
    font-size: 16px;
    font-weight: 600;
    padding: 14px;
    border-radius: 8px;
    cursor: pointer;
    flex: 1;
    transition: all 0.2s;
}
.btn-success:hover { background: #53fa90; }
.btn-primary { flex: 1; }

/* ແກ້ປຸ່ມລົ້ນ panel: ໃຫ້ປຸ່ມຫົດໄດ້ ແລະ ບໍ່ຕັດແຖວ */
.action-row button {
    flex: 1 1 0;
    min-width: 0;
    width: auto;
    padding: 12px 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pay-count {
    display: inline-block;
    min-width: 20px;
    padding: 0 6px;
    margin-left: 4px;
    border-radius: 10px;
    background: #22c55e;
    color: #fff;
    font-size: 12px;
    line-height: 20px;
    text-align: center;
    vertical-align: middle;
}

/* ===== Pay Modal (custom, no Bootstrap JS needed) ===== */
.pay-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.pay-modal-overlay.show {
    display: flex;
}
.pay-modal {
    background: #fff;
    width: 100%;
    max-width: 380px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}
.pay-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    border-bottom: 1px solid #e2e8f0;
}
.pay-modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #1e3a8a;
}
.pay-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
    padding: 4px 8px;
}
.pay-modal-close:hover { color: #dc2626; }
.pay-modal-body {
    padding: 20px;
    text-align: center;
}
#qr-container {
    width: 240px;
    height: 240px;
    max-width: 100%;
    margin: 0 auto;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 15px;
    background: #f8fafc;
}
#qr-container img,
#qr-container canvas {
    max-width: 100%;
    height: auto;
}
.pay-modal-note {
    margin-top: 12px;
    font-size: 14px;
    color: #475569;
}
.pay-modal-footer {
    padding: 12px 18px 18px;
    text-align: center;
}
.pay-modal-footer button {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
    font-size: 15px;
    font-weight: 600;
    padding: 10px 28px;
    border-radius: 8px;
    cursor: pointer;
}
.pay-modal-footer button:hover { background: #e2e8f0; }

@media (max-width: 640px) {
    .filter-desktop-row {
        flex-direction: column !important;
        gap: 12px !important;
    }
    .filter-desktop-row .field,
    .filter-desktop-row .action-row,
    .filter-desktop-row .filter-section {
        flex: unset !important;
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    .filter-desktop-row .filter-section {
        flex-direction: row !important;
        gap: 10px !important;
    }
    .action-row button {
        flex: 1 1 0 !important;
    }
}

.pay-amount { font-size: 26px; font-weight: 700; color: #1e3a8a; margin-bottom: 12px; }
.pay-countdown { margin-top: 10px; font-size: 14px; color: #475569; }
.pay-error { margin-top: 10px; font-size: 14px; color: #dc2626; }
#qr-container.has-qr { border: none; background: #fff; }
#qr-container.paid {
    border: none;
    background: #f0fdf4;
    color: #16a34a;
    flex-direction: column;
    gap: 8px;
    font-size: 20px;
    font-weight: 700;
}
#qr-container.paid i { font-size: 72px; line-height: 1; }
.pay-modal-footer button { margin: 0 4px; }

/* ===== ເລືອກບິນເພື່ອຈ່າຍ ===== */
.bill-chk, .chk-all {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #22c55e;
    vertical-align: middle;
}
.bill-select-wrap {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px 4px 0;
}
.mobile-select-all {
    padding: 8px 4px 12px;
    font-weight: 600;
    color: #1e3a8a;
}
.mobile-select-all label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}
#selection-bar {
    display: none;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    background: #f0fdf4;
    border: 1px solid #86efac;
    color: #166534;
    border-radius: 8px;
    padding: 10px 14px;
    margin: 0 0 14px;
    font-size: 15px;
}
#selection-bar button {
    background: none;
    border: none;
    color: #dc2626;
    font-weight: 600;
    cursor: pointer;
    padding: 4px 8px;
}
</style>
</head>
<body>
<div class="panel full-panel">

    <!-- Customer Info Header Boxes -->
    <div class="customer-header-boxes">
        <div class="customer-box">
            <div class="top-line"><i class="bi bi-person-fill"></i> <?php echo htmlspecialchars($customer_id); ?></div>
            <div class="bottom-line"><?php echo htmlspecialchars($shop['phone_number'] ?? '-'); ?></div>
        </div>
        <div class="customer-box">
            <div class="top-line"><?php echo htmlspecialchars(!empty($shop['outlet_name_la']) ? $shop['outlet_name_la'] : (!empty($shop['customer_name']) ? $shop['customer_name'] : '-')); ?></div>
            <div class="bottom-line"><?php 
                $village = !empty($shop['Village_LA']) ? $shop['Village_LA'] : (!empty($shop['village']) && !is_numeric(trim($shop['village'])) ? $shop['village'] : '');
                $district = !empty($shop['district']) ? $shop['district'] : '';
                if ($village && $district) {
                    $location = $village . ' - ' . $district;
                } elseif ($village) {
                    $location = $village;
                } elseif ($district) {
                    $location = $district;
                } else {
                    $location = '-';
                }
                echo htmlspecialchars($location);
            ?></div>
        </div>
    </div>
    
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
        <h2 style="margin: 0; font-size: 26px; font-weight: 700; color: #1e3a8a;">ລາຍການອິນວອຍ</h2>
        <button type="button" class="btn-back" onClick="window.location.href='menu.php'">
            <i class="bi bi-arrow-left"></i> ກັບຄືນ
        </button>
    </div>

    <div class="filter-desktop-row">
        <div class="field" style="flex: 1; margin-bottom: 0;">
            <label>ເລືອກສະແດງ :</label>
            <select id="period_select" style="width: 100%; background-color: #fff; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 8px;">
                <option value="custom" selected>ເລືອກວັນທີ :</option>
                <option value="month">ເລືອກເດືອນ</option>
                <option value="all">ທັງໝົດ</option>
            </select>
        </div>
        <div class="filter-section" id="custom-date-section" style="margin-bottom: 0; flex: 2; display: flex;">
            <div class="field">
                <label>ເລີ່ມວັນທີ :</label>
                <input type="text" id="from_date" value="<?php echo date('Y-m-01'); ?>" readonly style="width: 50%; background-color: #fff;">
            </div>
            <div class="field">
                <label>ຫາວັນທີ :</label>
                <input type="text" id="to_date" value="<?php echo date('Y-m-t'); ?>" readonly style="width: 50%; background-color: #fff;">
            </div>
        </div>
        <div class="filter-section" id="month-select-section" style="margin-bottom: 0; flex: 2; display: none;">
            <div class="field">
                <label>ເດືອນ</label>
                <select id="month_picker" style="width: 100%; background-color: #fff; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 8px;">
                    <?php
                    $lao_months = [1=>'ມັງກອນ',2=>'ກຸມພາ',3=>'ມີນາ',4=>'ເມສາ',5=>'ພຶດສະພາ',6=>'ມິຖຸນາ',7=>'ກໍລະກົດ',8=>'ສິງຫາ',9=>'ກັນຍາ',10=>'ຕຸລາ',11=>'ພະຈິກ',12=>'ທັນວາ'];
                    $cur_month = (int)date('n');
                    foreach ($lao_months as $m => $name) {
                        $sel = ($m == $cur_month) ? 'selected' : '';
                        echo "<option value=\"$m\" $sel>$name</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="field">
                <label>ປີ</label>
                <select id="year_picker" style="width: 100%; background-color: #fff; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 8px;">
                    <?php
                    $cur_year = (int)date('Y');
                    for ($y = $cur_year; $y >= $cur_year - 3; $y--) {
                        echo "<option value=\"$y\">$y</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="field" style="flex: 1; margin-bottom: 0;">
            <label class="desktop-only-label">ຄົ້ນຫາບິນ :</label>
            <input type="text" id="search" placeholder="ຄົ້ນຫາເລກທີບິນ....">
        </div>

        <div class="action-row" style="flex: 1; margin-bottom: 0; align-items: flex-end;">
            <button type="button" class="btn-primary" id="btn-search" style="margin-bottom: 0;"><i class="bi bi-search"></i> ຄົ້ນຫາ</button>
            <button type="button" class="btn-secondary" id="btn-pdf" style="margin-bottom: 0;"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            <button type="button" class="btn-success" id="btn-pay" style="margin-bottom: 0;"><i class="bi bi-credit-card"></i> ຈ່າຍ</button>
        </div>
    </div>

    <!-- ແຖບສະຫຼຸບບິນທີ່ເລືອກ -->
    <div id="selection-bar">
        <div>ເລືອກແລ້ວ <b id="sel-count">0</b> ບິນ · ຍອດ ₭ <b id="sel-sum">0</b></div>
        <button type="button" id="sel-clear">ລ້າງ</button>
    </div>

    <div class="desktop-table-container">
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"><input type="checkbox" class="chk-all" title="ເລືອກທັງໝົດ"></th>
                    <th style="width: 40px; text-align: center;"></th>
                    <th style="width: 50px; text-align: center;">ລ/ດ</th>
                    <th>ເລກທີ</th>
                    <th>ເລກທີສັ່ງຊື້</th>
                    <th style="text-align: center;">ວັນທີ</th>
                    <th style="text-align: right;">ຈຳນວນ</th>
                    <th style="text-align: right;">ມູນຄ່າລວມ</th>
                    <th style="text-align: center;">ສະຖານະ</th>
                    <th style="text-align: center;">ຍອດຄ້າງຈ່າຍ</th>
                </tr>
            </thead>
            <tbody id="invoice-tbody">
                <!-- Rendered by JS -->
            </tbody>
        </table>
    </div>

    <div id="invoice-mobile-container" class="invoice-grid mobile-cards-container">
        <!-- Rendered by JS -->
    </div>

</div>

<!-- Pay Modal (QR Code) -->
<div class="pay-modal-overlay" id="pay-modal" aria-hidden="true">
    <div class="pay-modal" role="dialog" aria-modal="true" aria-labelledby="pay-modal-title">
        <div class="pay-modal-header">
            <h3 id="pay-modal-title"><i class="bi bi-credit-card"></i> ຊຳລະເງິນ</h3>
            <button type="button" class="pay-modal-close" id="pay-modal-close" aria-label="ປິດ">&times;</button>
        </div>
        <div class="pay-modal-body">
            <div id="pay-amount" class="pay-amount"></div>
            <div id="qr-container">QR Code</div>
            <div id="pay-countdown" class="pay-countdown"></div>
            <div id="pay-error" class="pay-error"></div>
            <div class="pay-modal-note" id="pay-note">ສະແກນ QR Code ເພື່ອຊຳລະເງິນ</div>
        </div>
        <div class="pay-modal-footer">
            <button type="button" id="pay-modal-regen" style="display:none;">ສ້າງ QR ໃໝ່</button>
            <button type="button" id="pay-modal-cancel">ປິດ</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
// ===== ສະຖານະການເລືອກບິນ (ເກັບໃນຕົວແປ ບໍ່ເກັບໃນໜ້າຕາຕະລາງ) =====
const selectedBills = new Map();   // sale_id -> ຍອດຄ້າງ (ໃຊ້ສະແດງຜົນເທົ່ານັ້ນ ຍອດຈິງຄິດຈາກຖານຂໍ້ມູນ)
const payableIds = new Set();      // sale_id ຂອງບິນທີ່ຍັງຈ່າຍໄດ້ໃນຜົນຄົ້ນຫາປັດຈຸບັນ

// ບິນທີ່ຍັງຈ່າຍໄດ້ = ຍັງບໍ່ຈ່າຍ ແລະ ມີຍອດຄ້າງ (ຕາມເງື່ອນໄຂໃນ create_payment.php)
function isPayable(inv) {
    const paid = inv.status == '2' || inv.status_payment == '2' || inv.status_payment == '3';
    return !paid && Number(inv.remain) > 0;
}

function billCheckbox(inv) {
    if (!isPayable(inv)) return '';
    return `<input type="checkbox" class="bill-chk" data-sale="${inv.sale_id}" data-remain="${Number(inv.remain)}">`;
}

function setBillSelected(saleId, remain, checked) {
    if (checked) {
        selectedBills.set(String(saleId), Number(remain) || 0);
    } else {
        selectedBills.delete(String(saleId));
    }
}

// ອັບເດດຊ່ອງຕິກ, ແຖບສະຫຼຸບ ແລະ ປຸ່ມຈ່າຍ ໃຫ້ຕົງກັບການເລືອກ
function refreshSelectionUI() {
    document.querySelectorAll('.bill-chk').forEach(cb => {
        cb.checked = selectedBills.has(String(cb.dataset.sale));
    });

    const n = selectedBills.size;
    let sum = 0;
    selectedBills.forEach(v => { sum += v; });

    document.querySelectorAll('.chk-all').forEach(cb => {
        cb.disabled = payableIds.size === 0;
        cb.checked = n > 0 && n === payableIds.size;
        cb.indeterminate = n > 0 && n < payableIds.size;
    });

    const bar = document.getElementById('selection-bar');
    if (bar) {
        bar.style.display = n > 0 ? 'flex' : 'none';
        document.getElementById('sel-count').textContent = n;
        document.getElementById('sel-sum').textContent = sum.toLocaleString();
    }

    const btn = document.getElementById('btn-pay');
    if (btn) {
        // ໃຊ້ badge ນ້ອຍໆ ແທນການຕໍ່ຂໍ້ຄວາມ ເພື່ອບໍ່ໃຫ້ປຸ່ມກວ້າງຂຶ້ນຈົນລົ້ນ panel
        btn.innerHTML = '<i class="bi bi-credit-card"></i> ຈ່າຍ' +
            (n > 0 ? '<span class="pay-count">' + n + '</span>' : '');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const periodSelect = document.getElementById('period_select');
    const customDateSection = document.getElementById('custom-date-section');
    const monthSelectSection = document.getElementById('month-select-section');

    function applyMonthSelection() {
        const month = document.getElementById('month_picker').value;
        const year = document.getElementById('year_picker').value;
        const mm = String(month).padStart(2, '0');
        const lastDay = new Date(year, month, 0).getDate();
        document.getElementById('from_date').value = `${year}-${mm}-01`;
        document.getElementById('to_date').value = `${year}-${mm}-${String(lastDay).padStart(2, '0')}`;
        loadInvoices();
    }

    periodSelect.addEventListener('change', function() {
        customDateSection.style.display = (this.value === 'custom') ? 'flex' : 'none';
        monthSelectSection.style.display = (this.value === 'month') ? 'flex' : 'none';

        if (this.value === 'month') {
            applyMonthSelection();
        } else {
            loadInvoices();
        }
    });

    document.getElementById('month_picker').addEventListener('change', applyMonthSelection);
    document.getElementById('year_picker').addEventListener('change', applyMonthSelection);

    flatpickr("#from_date", {
        dateFormat: "Y-m-d",
        onChange: function() { loadInvoices(); }
    });
    
    flatpickr("#to_date", {
        dateFormat: "Y-m-d",
        onChange: function() { loadInvoices(); }
    });

    document.getElementById('search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadInvoices();
        }
    });

    document.getElementById('btn-search').addEventListener('click', function() {
        loadInvoices();
    });

    document.getElementById('btn-pdf').addEventListener('click', function() {
        let from_date = document.getElementById('from_date').value;
        let to_date = document.getElementById('to_date').value;
        const search = document.getElementById('search').value;
        const period = document.getElementById('period_select').value;
        
        if (period === 'all') {
            from_date = '';
            to_date = '';
        }
        
        window.open(`print/export_invoice_pdf.php?from_date=${from_date}&to_date=${to_date}&search=${search}`, '_blank');
    });

    // ===== ຕິກເລືອກບິນ (ໃຊ້ event delegation ເພາະຕາຕະລາງຖືກວາດໃໝ່ທຸກຄັ້ງ) =====
    document.addEventListener('change', function(e) {
        const t = e.target;
        if (t.classList.contains('bill-chk')) {
            setBillSelected(t.dataset.sale, t.dataset.remain, t.checked);
            refreshSelectionUI();
        } else if (t.classList.contains('chk-all')) {
            document.querySelectorAll('.bill-chk').forEach(cb => {
                setBillSelected(cb.dataset.sale, cb.dataset.remain, t.checked);
            });
            refreshSelectionUI();
        }
    });

    document.getElementById('sel-clear').addEventListener('click', function() {
        selectedBills.clear();
        refreshSelectionUI();
    });

    // ===== Pay Modal =====
    const payModal     = document.getElementById('pay-modal');
    const qrBox        = document.getElementById('qr-container');
    const payAmount    = document.getElementById('pay-amount');
    const payCountdown = document.getElementById('pay-countdown');
    const payError     = document.getElementById('pay-error');
    const payNote      = document.getElementById('pay-note');
    const btnRegen     = document.getElementById('pay-modal-regen');

    let countdownTimer = null;
    let pollTimer      = null;
    let pollBusy       = false;   // ກັນຍິງຊ້ອນ ຖ້າຄຳຂໍກ່ອນໜ້າຍັງບໍ່ຕອບ
    let currentBill    = '';      // bill_number ຂອງ QR ທີ່ສະແດງຢູ່
    let paidDone       = false;   // ຈ່າຍສຳເລັດແລ້ວ → ຕອນກົດປິດໃຫ້ໂຫຼດຂໍ້ມູນໃໝ່

    function stopCountdown() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        pollBusy = false;
    }

    // ຕັ້ງຂໍ້ຄວາມໃນກ່ອງ QR ແລະ ລ້າງສະຖານະ has-qr / paid
    function resetQrBox(text) {
        qrBox.classList.remove('has-qr', 'paid');
        qrBox.innerHTML = text;
    }

    function showPaid() {
        if (paidDone) return;
        paidDone = true;
        stopCountdown();
        stopPolling();
        qrBox.classList.remove('has-qr');
        qrBox.classList.add('paid');
        qrBox.innerHTML = '<i class="bi bi-check-circle-fill"></i><div>ຊຳລະສຳເລັດ</div>';
        payCountdown.textContent = '';
        payError.textContent = '';
        payNote.style.display = 'none';
        btnRegen.style.display = 'none';
    }

    function showExpired() {
        if (paidDone) return;
        stopCountdown();
        stopPolling();
        resetQrBox('QR ໝົດອາຍຸແລ້ວ');
        payCountdown.textContent = '';
        btnRegen.style.display = 'inline-block';
    }

    function showPayError(message) {
        stopCountdown();
        stopPolling();
        resetQrBox('QR Code');
        payError.textContent = message;
        btnRegen.style.display = 'inline-block';
    }

    function showNoBills(message) {
        resetQrBox('ບໍ່ມີຍອດຄ້າງຈ່າຍ');
        payError.textContent = message;
        btnRegen.style.display = 'none';   // ກົດສ້າງໃໝ່ກໍບໍ່ມີຜົນ ຈຶ່ງບໍ່ສະແດງ
    }

    // ຖາມສະຖານະຈາກຕາຕະລາງ payments ຂອງເຮົາເອງ (ບໍ່ໄດ້ຖາມທະນາຄານ)
    function checkPayment() {
        const bill = currentBill;
        if (!bill || paidDone) return Promise.resolve(null);

        const fd = new FormData();
        fd.append('bill_number', bill);

        return fetch('api/check_payment.php', { method: 'POST', body: fd })
            .then(response => response.json())
            .then(data => {
                // ຖ້າໃນລະຫວ່າງລໍຖ້າ ໄດ້ປິດ ຫຼື ສ້າງ QR ໃໝ່ແລ້ວ ໃຫ້ຖິ້ມຜົນຂອງຄຳຂໍເກົ່າ
                if (bill !== currentBill) return null;
                if (data.status !== 'success') return null;

                const st = data.data.payment_status;
                if (st === 'paid') {
                    showPaid();
                } else if (st === 'expired' || st === 'failed') {
                    showExpired();
                }
                return st;
            })
            .catch(err => {
                console.error(err);
                return null;
            });
    }

    function startPolling() {
        stopPolling();
        pollTimer = setInterval(function() {
            if (pollBusy) return;
            pollBusy = true;
            checkPayment().finally(function() { pollBusy = false; });
        }, 3000);
    }

    function startCountdown(seconds) {
        stopCountdown();
        let left = seconds;

        const tick = function() {
            if (left <= 0) {
                stopCountdown();
                stopPolling();
                // ກວດຄັ້ງສຸດທ້າຍ ເຜື່ອລູກຄ້າຈ່າຍໃນວິນາທີສຸດທ້າຍ
                checkPayment().then(function(st) {
                    if (st !== 'paid' && !paidDone) showExpired();
                });
                return;
            }
            const m = String(Math.floor(left / 60)).padStart(2, '0');
            const s = String(left % 60).padStart(2, '0');
            payCountdown.textContent = 'ໝົດອາຍຸໃນ ' + m + ':' + s;
            left--;
        };

        tick();
        countdownTimer = setInterval(tick, 1000);
    }

    function createPayment() {
        stopCountdown();
        stopPolling();
        paidDone    = false;
        currentBill = '';
        payError.textContent = '';
        payAmount.textContent = '';
        payCountdown.textContent = '';
        payNote.style.display = '';
        btnRegen.style.display = 'none';
        resetQrBox('ກຳລັງສ້າງ QR...');

        const formData = new FormData();

        if (selectedBills.size > 0) {
            // ເລືອກບິນເອງ → ສົ່ງສະເພາະ sale_ids
            selectedBills.forEach((remain, saleId) => {
                formData.append('sale_ids[]', saleId);
            });
        } else {
            // ບໍ່ໄດ້ເລືອກ → ສົ່ງຕົວກອງ (ຈ່າຍທຸກບິນທີ່ຄ້າງຕາມຕົວກອງ)
            let from_date = document.getElementById('from_date').value;
            let to_date   = document.getElementById('to_date').value;
            const search  = document.getElementById('search').value;
            const period  = document.getElementById('period_select').value;

            if (period === 'all') {
                from_date = '';
                to_date   = '';
            }
            formData.append('from_date', from_date);
            formData.append('to_date', to_date);
            formData.append('search', search);
        }

        fetch('api/create_payment.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'success') {
                    if (data.code === 'no_bills') {
                        showNoBills(data.message);
                    } else {
                        showPayError(data.message || 'ເກີດຂໍ້ຜິດພາດ');
                    }
                    return;
                }

                const d = data.data;
                currentBill = d.bill_number;

                // ວາດ QR (qrcode-generator ເລືອກຂະໜາດໃຫ້ເອງ ຮອງຮັບຂໍ້ຄວາມຍາວ)
                const qr = qrcode(0, 'M');
                qr.addData(d.qr_data);
                qr.make();
                qrBox.classList.remove('paid');
                qrBox.innerHTML = qr.createImgTag(4, 8);
                const img = qrBox.querySelector('img');
                if (img) {
                    img.style.width = '220px';
                    img.style.height = '220px';
                    img.style.imageRendering = 'pixelated';
                }
                qrBox.classList.add('has-qr');

                payAmount.textContent = '₭ ' + Number(d.amount).toLocaleString() + ' (' + d.bill_count + ' ບິນ)';
                startCountdown(d.expires_in);
                startPolling();   // ເລີ່ມຖາມສະຖານະທຸກ 3 ວິນາທີ
            })
            .catch(err => {
                console.error(err);
                showPayError('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່');
            });
    }

    function openPayModal() {
        payModal.classList.add('show');
        payModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        createPayment();
    }

    function closePayModal() {
        stopCountdown();
        stopPolling();
        payModal.classList.remove('show');
        payModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        resetQrBox('QR Code');
        currentBill = '';

        // ຈ່າຍສຳເລັດແລ້ວ → ໂຫຼດຂໍ້ມູນຕາມຕົວກອງທີ່ຄົ້ນຫາຢູ່ໃໝ່ ເພື່ອສະແດງສະຖານະຈ່າຍແລ້ວ
        if (paidDone) {
            paidDone = false;
            loadInvoices();   // ລ້າງການເລືອກບິນໃຫ້ເອງ
        }
    }

    document.getElementById('btn-pay').addEventListener('click', openPayModal);
    document.getElementById('pay-modal-close').addEventListener('click', closePayModal);
    document.getElementById('pay-modal-cancel').addEventListener('click', closePayModal);
    btnRegen.addEventListener('click', createPayment);

    // ກົດພື້ນທີ່ມືດດ້ານນອກ ເພື່ອປິດ
    payModal.addEventListener('click', function(e) {
        if (e.target === payModal) {
            closePayModal();
        }
    });

    // ກົດ Esc ເພື່ອປິດ
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && payModal.classList.contains('show')) {
            closePayModal();
        }
    });

    loadInvoices();
});

function loadInvoices() {
    let from_date = document.getElementById('from_date').value;
    let to_date = document.getElementById('to_date').value;
    const search = document.getElementById('search').value;
    const period = document.getElementById('period_select') ? document.getElementById('period_select').value : 'all';
    
    if(period === 'all') {
        from_date = '';
        to_date = '';
    }

    // ໂຫຼດຂໍ້ມູນໃໝ່ທຸກຄັ້ງ → ລ້າງການເລືອກເກົ່າ (ກັນເລືອກບິນທີ່ບໍ່ຢູ່ໃນຜົນຄົ້ນຫາແລ້ວ)
    selectedBills.clear();
    payableIds.clear();
    refreshSelectionUI();

    const mobileContainer = document.getElementById('invoice-mobile-container');
    const tbody = document.getElementById('invoice-tbody');
    
    mobileContainer.innerHTML = '<div class="empty-state">ກຳລັງໂຫຼດຂໍ້ມູນ...</div>';
    tbody.innerHTML = '<tr><td colspan="10" class="empty-state" style="text-align: center; padding: 40px;">ກຳລັງໂຫຼດຂໍ້ມູນ...</td></tr>';

    const formData = new FormData();
    formData.append('action', 'list');
    formData.append('from_date', from_date);
    formData.append('to_date', to_date);
    formData.append('search', search);

    fetch('api/get_invoice.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            renderInvoices(data.data);
        } else {
            mobileContainer.innerHTML = `<div class="error-msg">${data.message}</div>`;
            tbody.innerHTML = `<tr><td colspan="10" class="error-msg">${data.message}</td></tr>`;
        }
    })
    .catch(error => {
        mobileContainer.innerHTML = `<div class="error-msg">ເກີດຂໍ້ຜິດພາດໃນການໂຫຼດຂໍ້ມູນ</div>`;
        tbody.innerHTML = `<tr><td colspan="10" class="error-msg">ເກີດຂໍ້ຜິດພາດ</td></tr>`;
    });
}

// 2026-06-21 -> 21/06/2026 (ຮູບແບບດຽວກັບໜ້າ admin)
function formatDate(d) {
    if (!d) return '-';
    const s = String(d).split(' ')[0];   // ຕັດເວລາອອກ ຖ້າມີຕິດມາ
    const p = s.split('-');
    if (p.length !== 3) return s;        // ຮູບແບບບໍ່ຄືທີ່ຄາດ → ສະແດງຄ່າດິບ
    return `${p[2]}/${p[1]}/${p[0]}`;
}

function renderInvoices(invoices) {
    const mobileContainer = document.getElementById('invoice-mobile-container');
    const tbody = document.getElementById('invoice-tbody');
    
    mobileContainer.innerHTML = '';
    tbody.innerHTML = '';
    
    if(invoices.length === 0) {
        mobileContainer.innerHTML = '<div class="empty-state">ບໍ່ພົບຂໍ້ມູນ</div>';
        tbody.innerHTML = '<tr><td colspan="10" class="empty-state" style="text-align: center; padding: 40px;">ບໍ່ພົບຂໍ້ມູນ</td></tr>';
        refreshSelectionUI();
        return;
    }

    // ລວບລວມບິນທີ່ຈ່າຍໄດ້ ກ່ອນວາດຕາຕະລາງ
    payableIds.clear();
    invoices.forEach(inv => {
        if (isPayable(inv)) payableIds.add(String(inv.sale_id));
    });

    let mobileHtml = '';
    let desktopHtml = '';

    invoices.forEach((inv, index) => {
        let payStatusText = 'ຕິດໜີ້';
        let statusClass = 'badge-debt';
        if(inv.status_payment == '2') {
            payStatusText = 'ສົດ';
            statusClass = 'badge-paid';
        } else if(inv.status_payment == '3') {
            payStatusText = 'ເງິນໂອນ';
            statusClass = 'badge-transfer';
        }

        // 1. ປະກາດຕົວປ່ຽນ ແລະ ຕັ້ງຄ່າ default ເປັນ 'ຄ້າງຈ່າຍ'
        let mainStatus = 'ຄ້າງຈ່າຍ';

        // 2. ເຊັກສະຖານະທີ່ "ຈ່າຍແລ້ວ"
        if(inv.status == '2' || inv.status_payment == '2' || inv.status_payment == '3') {
            mainStatus = 'ຈ່າຍແລ້ວ';
        }

        const chkHtml = billCheckbox(inv);

        // Mobile HTML
        mobileHtml += `
            <div class="invoice-card" onclick="toggleDetails('${inv.sale_id}')">
                <div class="invoice-header">
                    <div>
                        ${chkHtml ? `<span class="bill-select-wrap" onclick="event.stopPropagation()">${chkHtml}</span>` : ''}
                        <span class="invoice-no">${inv.sale_id}</span>
                        ${inv.order_id ? `<br><span style="font-size: 13px; color: #666;">ເລກທີສັ່ງຊື້: ${inv.order_id}</span>` : ''}
                    </div>
                    <div class="invoice-date">${formatDate(inv.sale_date)}</div>
                </div>
                <div class="invoice-body">
                    <div class="invoice-amt">₭ ${Number(inv.total_amt).toLocaleString()}</div>
                    <div class="badge-status ${statusClass}">${payStatusText}</div>
                </div>
                <div style="text-align: center; margin-top: 10px; border-top: 1px dashed #e2e8f0; padding-top: 12px;">
                    <button type="button" class="btn-primary" style="width: auto; padding: 6px 24px; font-size: 14px; border-radius: 20px; pointer-events: none;">ລາຍລະອຽດ</button>
                </div>
                <div id="details-${inv.sale_id}" class="invoice-details">
                    <div style="text-align: center; padding: 10px; color: #666;">ກຳລັງໂຫຼດ...</div>
                </div>
            </div>
        `;

        // Desktop HTML (10 columns)
        desktopHtml += `
            <tr onclick="toggleDetailsDesktop('${inv.sale_id}')" style="cursor: pointer; border-bottom: 1px solid #e2e8f0; color: #000;">
                <td style="text-align: center;" onclick="event.stopPropagation()">${chkHtml}</td>
                <td style="text-align: center;"><i id="icon-${inv.sale_id}" class="bi bi-plus-circle-fill" style="color: #3b82f6; font-size: 22px;"></i></td>
                <td style="text-align: center; color: #000;">${index + 1}</td>
                <td style="color: #000; font-weight: 600;">${inv.sale_id}</td>
                <td style="color: #000;">${inv.order_id || '-'}</td>
                <td style="text-align: center; color: #000; white-space: nowrap;">${formatDate(inv.sale_date)}</td>
                <td style="text-align: right; color: #000;">${Number(inv.total_qty).toLocaleString()}</td>
                <td style="text-align: right; font-weight: 600; color: #000;">${Number(inv.total_amt).toLocaleString()}</td>
                <td style="text-align: center; color: #000;">${mainStatus}</td>
                <td style="text-align: center; color: #000;">${Number(inv.remain).toLocaleString()}</td>
            </tr>
            <tr id="desktop-details-${inv.sale_id}" class="desktop-details-row" style="display: none;">
                <td colspan="10" style="padding: 0; border-bottom: 2px solid #e2e8f0;">
                    <div id="desktop-details-content-${inv.sale_id}" style="padding: 20px;"></div>
                </td>
            </tr>
        `;

    });

    // ຄິດໄລ່ຍອດລວມ (ຈຳນວນ, ມູນຄ່າລວມ, ຍອດຄ້າງຈ່າຍ)
    let sumQty = 0, sumAmt = 0, sumRemain = 0;
    invoices.forEach(inv => {
        sumQty += Number(inv.total_qty) || 0;
        sumAmt += Number(inv.total_amt) || 0;
        sumRemain += Number(inv.remain) || 0;
    });

    const totalRowHtml = `
        <tr style="background: #f8fafc; border-top: 2px solid #cbd5e1; font-weight: 700; color: #000;">
            <td colspan="6" style="text-align: right; padding: 10px;">ລວມທັງໝົດ :</td>
            <td style="text-align: right; padding: 10px;">${sumQty.toLocaleString()}</td>
            <td style="text-align: right; padding: 10px;">${sumAmt.toLocaleString()}</td>
            <td></td>
            <td style="text-align: center; padding: 10px; color: #dc2626;">${sumRemain.toLocaleString()}</td>

        </tr>
    `;

    const mobileTotalHtml = `
        <div class="invoice-card" style="background: #f8fafc; border: 1px solid #cbd5e1;">
            <div class="invoice-body" style="justify-content: space-between; display: flex;">
                <div style="font-weight: 700;">ລວມທັງໝົດ (${invoices.length} ບິນ)</div>
            </div>
            <div class="invoice-body" style="justify-content: space-between; display: flex; margin-top: 6px;">
                <div>ມູນຄ່າລວມ: <b>₭ ${sumAmt.toLocaleString()}</b></div>
            </div>
            <div class="invoice-body" style="justify-content: space-between; display: flex; margin-top: 6px;">
                <div style="color: #dc2626;">ຍອດຄ້າງຈ່າຍລວມ: <b>₭ ${sumRemain.toLocaleString()}</b></div>
            </div>
        </div>
    `;

    // ແຖວ "ເລືອກທັງໝົດ" ສຳລັບມືຖື (ສະແດງສະເພາະເມື່ອມີບິນທີ່ຈ່າຍໄດ້)
    const mobileSelectAll = payableIds.size > 0 ? `
        <div class="mobile-select-all">
            <label><input type="checkbox" class="chk-all"> ເລືອກບິນຄ້າງຈ່າຍທັງໝົດ</label>
        </div>
    ` : '';

    mobileContainer.innerHTML = mobileSelectAll + mobileHtml + mobileTotalHtml;
    tbody.innerHTML = desktopHtml + totalRowHtml;

    refreshSelectionUI();
}

function toggleDetailsDesktop(id) {
    const detailsRow = document.getElementById('desktop-details-' + id);
    const contentDiv = document.getElementById('desktop-details-content-' + id);
    const icon = document.getElementById('icon-' + id);
    
    if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
        detailsRow.style.display = 'table-row';
        icon.classList.remove('bi-plus-circle-fill');
        icon.classList.add('bi-dash-circle-fill');
        
        if (contentDiv.innerHTML.trim() === '') {
            contentDiv.innerHTML = '<div style="text-align: center; color: #666;">ກຳລັງໂຫຼດ...</div>';
            
            const formData = new FormData();
            formData.append('action', 'details');
            formData.append('sale_id', id);

            fetch('api/get_invoice.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    let dHtml = `
                    <table class="invoice-table" style="width: 100%; background: #fff; border: 1px solid #e2e8f0; margin: 0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <thead>
                            <tr style="background: #dbeafe; color: #000;">
                                <th style="padding: 10px; text-align: center;">ລ/ດ</th>
                                <th style="padding: 10px;">ລາຍການ</th>
                                <th style="padding: 10px; text-align: center;">ຫົວໜ່ວຍ</th>
                                <th style="padding: 10px; text-align: right;">ລາຄາ</th>
                                <th style="padding: 10px; text-align: right;">ຈຳນວນ</th>
                                <th style="padding: 10px; text-align: right;">ລວມເງິນ</th>
                            </tr>
                        </thead>
                        <tbody>`;
                    data.data.forEach((item, index) => {
                        dHtml += `
                            <tr style="color: #000;">
                                <td style="padding: 10px; text-align: center; border-bottom: 1px solid #f1f5f9;">${index + 1}</td>
                                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">${item.Product_Name}</td>
                                <td style="padding: 10px; text-align: center; border-bottom: 1px solid #f1f5f9;">${item.Unit}</td>
                                <td style="padding: 10px; text-align: right; border-bottom: 1px solid #f1f5f9;">${Number(item.price).toLocaleString()}</td>
                                <td style="padding: 10px; text-align: right; border-bottom: 1px solid #f1f5f9;">${Number(item.qty).toLocaleString()}</td>
                                <td style="padding: 10px; text-align: right; font-weight: 600; border-bottom: 1px solid #f1f5f9;">${Number(item.amount).toLocaleString()}</td>
                            </tr>
                        `;
                    });
                    dHtml += '</tbody></table>';
                    contentDiv.innerHTML = dHtml;
                } else {
                    contentDiv.innerHTML = '<div style="color: red;">ບໍ່ພົບລາຍລະອຽດ</div>';
                }
            })
            .catch(error => {
                contentDiv.innerHTML = '<div style="color: red;">ເກີດຂໍ້ຜິດພາດໃນການໂຫຼດຂໍ້ມູນ</div>';
            });
        }
    } else {
        detailsRow.style.display = 'none';
        icon.classList.remove('bi-dash-circle-fill');
        icon.classList.add('bi-plus-circle-fill');
    }
}

function toggleDetails(id) {
    const detailsDiv = document.getElementById('details-' + id);
    if (detailsDiv.style.display === 'block') {
        detailsDiv.style.display = 'none';
    } else {
        detailsDiv.style.display = 'block';
        if (detailsDiv.innerHTML.includes('ກຳລັງໂຫຼດ')) {
            const formData = new FormData();
            formData.append('action', 'details');
            formData.append('sale_id', id);

            fetch('api/get_invoice.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    let dHtml = '';
                    data.data.forEach(item => {
                        let unitText = item.Unit ? ` ${item.Unit}` : '';
                        dHtml += `
                            <div class="item-row">
                                <div class="item-name">${item.Product_Name}</div>
                                <div class="item-meta">
                                    <span class="item-qty">${Number(item.qty).toLocaleString()}${unitText}</span>
                                    <span class="item-total">₭ ${Number(item.amount).toLocaleString()}</span>
                                </div>
                            </div>
                        `;
                    });
                    detailsDiv.innerHTML = dHtml;
                } else {
                    detailsDiv.innerHTML = '<div class="error-msg">ບໍ່ພົບຂໍ້ມູນ</div>';
                }
            })
            .catch(error => {
                detailsDiv.innerHTML = '<div class="error-msg">ເກີດຂໍ້ຜິດພາດໃນການໂຫຼດຂໍ້ມູນ</div>';
            });
        }
    }
}
</script>
</body>
</html>