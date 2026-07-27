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
<title>ລາຍງານຍອດຊື້ / Purchase Summary</title>
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

.stat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 8px;
}
.stat-tile {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 10px;
    text-align: center;
}
.stat-tile .stat-icon {
    font-size: 20px;
    color: #3b82f6;
    margin-bottom: 6px;
}
.stat-tile .stat-value {
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
    word-break: break-word;
}
.stat-tile .stat-label {
    font-size: 12px;
    color: #64748b;
}
.stat-tile.highlight {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.stat-tile.highlight .stat-icon,
.stat-tile.highlight .stat-value {
    color: #1e3a8a;
}
.stat-tile.debt {
    background: #fef2f2;
    border-color: #fecaca;
}
.stat-tile.debt .stat-icon,
.stat-tile.debt .stat-value {
    color: #b91c1c;
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e3a8a;
    margin: 24px 0 12px;
}

.top-products-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
}

@media (max-width: 640px) {
    .filter-desktop-row {
        flex-direction: column !important;
        gap: 12px !important;
    }
    .filter-desktop-row .field,
    .filter-desktop-row .filter-section {
        flex: unset !important;
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    .filter-desktop-row .filter-section {
        flex-direction: row !important;
        gap: 10px !important;
    }
    .stat-grid {
        grid-template-columns: repeat(2, 1fr);
    }
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
        <h2 style="margin: 0; font-size: 26px; font-weight: 700; color: #1e3a8a;">ລາຍງານຍອດຊື້</h2>
        <button type="button" class="btn-back" onclick="window.location.href='menu.php'">
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
                <input type="text" id="from_date" value="<?php echo date('Y-m-01'); ?>" readonly style="width: 100%; background-color: #fff;">
            </div>
            <div class="field">
                <label>ຫາວັນທີ :</label>
                <input type="text" id="to_date" value="<?php echo date('Y-m-t'); ?>" readonly style="width: 100%; background-color: #fff;">
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
    </div>

    <!-- KPI Tiles -->
    <div class="stat-grid">
        <div class="stat-tile">
            <div class="stat-icon"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-value" id="stat-first-purchase">-</div>
            <div class="stat-label">ວັນທີ່ຊື້ຄັ້ງທຳອິດ</div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon"><i class="bi bi-receipt"></i></div>
            <div class="stat-value" id="stat-invoice-count">-</div>
            <div class="stat-label">ຈຳນວນອິນວອຍ</div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
            <div class="stat-value" id="stat-total-qty">-</div>
            <div class="stat-label">ຈຳນວນຊື້</div>
        </div>
        <div class="stat-tile highlight">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value" id="stat-total-amt">-</div>
            <div class="stat-label">ລວມມູນຄ່າຊື້ (₭)</div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
            <div class="stat-value" id="stat-avg">-</div>
            <div class="stat-label">ສະເລ່ຍຕໍ່ບິນ (₭)</div>
        </div>
        <div class="stat-tile debt">
            <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
            <div class="stat-value" id="stat-debt">-</div>
            <div class="stat-label">ຍອດຄ້າງຈ່າຍ (₭)</div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="section-title">ສິນຄ້າທີ່ຊື້ຫຼາຍທີ່ສຸດ (Top 5)</div>

    <div class="desktop-table-container">
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">ລ/ດ</th>
                    <th>ລາຍການ</th>
                    <th style="text-align: right;">ຈຳນວນ</th>
                    <th style="text-align: right;">ມູນຄ່າ</th>
                    <th style="text-align: center;">ຊື້ຄັ້ງລ່າສຸດ</th>
                </tr>
            </thead>
            <tbody id="top-products-tbody">
                <!-- Rendered by JS -->
            </tbody>
        </table>
    </div>

    <div class="top-products-card mobile-cards-container" id="top-products-mobile">
        <!-- Rendered by JS -->
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
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
        loadSummary();
    }

    periodSelect.addEventListener('change', function() {
        customDateSection.style.display = (this.value === 'custom') ? 'flex' : 'none';
        monthSelectSection.style.display = (this.value === 'month') ? 'flex' : 'none';

        if (this.value === 'month') {
            applyMonthSelection();
        } else {
            loadSummary();
        }
    });

    document.getElementById('month_picker').addEventListener('change', applyMonthSelection);
    document.getElementById('year_picker').addEventListener('change', applyMonthSelection);

    flatpickr("#from_date", {
        dateFormat: "Y-m-d",
        onChange: function() { loadSummary(); }
    });

    flatpickr("#to_date", {
        dateFormat: "Y-m-d",
        onChange: function() { loadSummary(); }
    });

    loadSummary();
});

function formatDate(d) {
    if (!d) return '-';
    const s = String(d).split(' ')[0];
    const p = s.split('-');
    if (p.length !== 3) return s;
    return `${p[2]}/${p[1]}/${p[0]}`;
}

function loadSummary() {
    let from_date = document.getElementById('from_date').value;
    let to_date = document.getElementById('to_date').value;
    const period = document.getElementById('period_select').value;

    if (period === 'all') {
        from_date = '';
        to_date = '';
    }

    const formData = new FormData();
    formData.append('from_date', from_date);
    formData.append('to_date', to_date);

    fetch('api/get_purchase_summary.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            renderSummary(data.data);
        }
    })
    .catch(error => {
        console.error('Purchase summary load error:', error);
    });
}

function renderSummary(d) {
    document.getElementById('stat-first-purchase').textContent = d.first_purchase_date ? formatDate(d.first_purchase_date) : '-';
    document.getElementById('stat-invoice-count').textContent = Number(d.invoice_count).toLocaleString();
    document.getElementById('stat-total-qty').textContent = Number(d.total_qty).toLocaleString();
    document.getElementById('stat-total-amt').textContent = Number(d.total_amt).toLocaleString();
    document.getElementById('stat-avg').textContent = Number(d.avg_per_invoice).toLocaleString(undefined, {maximumFractionDigits: 0});
    document.getElementById('stat-debt').textContent = Number(d.debt_amt).toLocaleString();

    renderTopProducts(d.top_products || []);
}

function renderTopProducts(products) {
    const tbody = document.getElementById('top-products-tbody');
    const mobileCard = document.getElementById('top-products-mobile');

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state" style="text-align:center; padding:30px;">ບໍ່ພົບຂໍ້ມູນ</td></tr>';
        mobileCard.innerHTML = '<div class="empty-state">ບໍ່ພົບຂໍ້ມູນ</div>';
        return;
    }

    let desktopHtml = '';
    let mobileHtml = '';

    products.forEach((p, index) => {
        let unitText = p.Unit ? ` ${p.Unit}` : '';
        let lastDate = formatDate(p.last_purchase_date);

        desktopHtml += `
            <tr style="border-bottom: 1px solid #e2e8f0; color: #000;">
                <td style="text-align: center;">${index + 1}</td>
                <td>${p.Product_Name || '-'}</td>
                <td style="text-align: right;">${Number(p.qty).toLocaleString()}${unitText}</td>
                <td style="text-align: right; font-weight: 600;">${Number(p.amt).toLocaleString()}</td>
                <td style="text-align: center; white-space: nowrap;">${lastDate}</td>
            </tr>
        `;

        mobileHtml += `
            <div class="item-row">
                <div class="item-name">${index + 1}. ${p.Product_Name || '-'}</div>
                <div class="item-meta">
                    <span class="item-qty">${Number(p.qty).toLocaleString()}${unitText}</span>
                    <span class="item-total">₭ ${Number(p.amt).toLocaleString()}</span>
                </div>
                <div style="font-size: 12px; color: #94a3b8;">ຊື້ຄັ້ງລ່າສຸດ: ${lastDate}</div>
            </div>
        `;
    });

    tbody.innerHTML = desktopHtml;
    mobileCard.innerHTML = mobileHtml;
}
</script>
</body>
</html>
