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
.btn-primary { flex: 1; }
.action-row button { flex: none; width: auto; padding: 12px 20px; }

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
        flex: 1 !important;
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
        <h2 style="margin: 0; font-size: 26px; font-weight: 700; color: #1e3a8a;">ລາຍການອິນວອຍ</h2>
        <button type="button" class="btn-back" onclick="window.location.href='menu.php'">
            <i class="bi bi-arrow-left"></i> ກັບຄືນ
        </button>
    </div>

    <div class="filter-desktop-row">
        <div class="field" style="flex: 1; margin-bottom: 0;">
            <label>ຊ່ວງເວລາ</label>
            <select id="period_select" style="width: 100%; background-color: #fff; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 8px;">
                <option value="all">ທັງໝົດ</option>
                <option value="custom">ເລືອກວັນທີ</option>
            </select>
        </div>
        <div class="filter-section" id="custom-date-section" style="margin-bottom: 0; flex: 2; display: none;">
            <div class="field">
                <label>ເລີ່ມວັນທີ</label>
                <input type="text" id="from_date" value="<?php echo date('Y-m-01'); ?>" readonly style="width: 100%; background-color: #fff;">
            </div>
            <div class="field">
                <label>ຫາວັນທີ</label>
                <input type="text" id="to_date" value="<?php echo date('Y-m-t'); ?>" readonly style="width: 100%; background-color: #fff;">
            </div>
        </div>
        
        <div class="field" style="flex: 1; margin-bottom: 0;">
            <label class="desktop-only-label">ຄົ້ນຫາບິນ</label>
            <input type="text" id="search" placeholder="ຄົ້ນຫາເລກທີບິນ....">
        </div>

        <div class="action-row" style="flex: 1; margin-bottom: 0; align-items: flex-end;">
            <button type="button" class="btn-primary" id="btn-search" style="margin-bottom: 0;">ຄົ້ນຫາ</button>
            <button type="button" class="btn-secondary" id="btn-pdf" style="margin-bottom: 0;">ໂຫລດ PDF</button>
        </div>
    </div>

    <div class="desktop-table-container">
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"></th>
                    <th style="width: 50px; text-align: center;">ລ/ດ</th>
                    <th>ເລກທີ</th>
                    <th>ເລກທີສັ່ງຊື້</th>
                    <th style="text-align: right;">ຈຳນວນ</th>
                    <th style="text-align: right;">ມູນຄ່າລວມ</th>
                    <th style="text-align: center;">ສະຖານະ</th>
                    <th style="text-align: center;">ການຈ່າຍ</th>
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

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const periodSelect = document.getElementById('period_select');
    const customDateSection = document.getElementById('custom-date-section');
    
    periodSelect.addEventListener('change', function() {
        if(this.value === 'custom') {
            customDateSection.style.display = 'flex';
        } else {
            customDateSection.style.display = 'none';
        }
        loadInvoices();
    });

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

    const mobileContainer = document.getElementById('invoice-mobile-container');
    const tbody = document.getElementById('invoice-tbody');
    
    mobileContainer.innerHTML = '<div class="empty-state">ກຳລັງໂຫຼດຂໍ້ມູນ...</div>';
    tbody.innerHTML = '<tr><td colspan="8" class="empty-state" style="text-align: center; padding: 40px;">ກຳລັງໂຫຼດຂໍ້ມູນ...</td></tr>';

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
            tbody.innerHTML = `<tr><td colspan="8" class="error-msg">${data.message}</td></tr>`;
        }
    })
    .catch(error => {
        mobileContainer.innerHTML = `<div class="error-msg">ເກີດຂໍ້ຜິດພາດໃນການໂຫຼດຂໍ້ມູນ</div>`;
        tbody.innerHTML = `<tr><td colspan="8" class="error-msg">ເກີດຂໍ້ຜິດພາດ</td></tr>`;
    });
}

function renderInvoices(invoices) {
    const mobileContainer = document.getElementById('invoice-mobile-container');
    const tbody = document.getElementById('invoice-tbody');
    
    mobileContainer.innerHTML = '';
    tbody.innerHTML = '';
    
    if(invoices.length === 0) {
        mobileContainer.innerHTML = '<div class="empty-state">ບໍ່ພົບຂໍ້ມູນ</div>';
        tbody.innerHTML = '<tr><td colspan="8" class="empty-state" style="text-align: center; padding: 40px;">ບໍ່ພົບຂໍ້ມູນ</td></tr>';
        return;
    }

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

        let mainStatus = inv.status ? inv.status : '-';
        
        // Mobile HTML
        mobileHtml += `
            <div class="invoice-card" onclick="toggleDetails('${inv.sale_id}')">
                <div class="invoice-header">
                    <div>
                        <span class="invoice-no">${inv.sale_id}</span>
                        ${inv.order_id ? `<br><span style="font-size: 13px; color: #666;">ເລກທີສັ່ງຊື້: ${inv.order_id}</span>` : ''}
                    </div>
                    <div class="invoice-date">${inv.sale_date}</div>
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

        // Desktop HTML (8 columns)
        desktopHtml += `
            <tr onclick="toggleDetailsDesktop('${inv.sale_id}')" style="cursor: pointer; border-bottom: 1px solid #e2e8f0; color: #000;">
                <td style="text-align: center;"><i id="icon-${inv.sale_id}" class="bi bi-plus-circle-fill" style="color: #3b82f6; font-size: 22px;"></i></td>
                <td style="text-align: center; color: #000;">${index + 1}</td>
                <td style="color: #000; font-weight: 600;">${inv.sale_id}</td>
                <td style="color: #000;">${inv.order_id || '-'}</td>
                <td style="text-align: right; color: #000;">${Number(inv.total_qty).toLocaleString()}</td>
                <td style="text-align: right; font-weight: 600; color: #000;">${Number(inv.total_amt).toLocaleString()}</td>
                <td style="text-align: center; color: #000;">${mainStatus}</td>
                <td style="text-align: center;"><span class="inv-status ${statusClass}" style="display:inline-block; padding: 4px 10px; border-radius: 20px;">${payStatusText}</span></td>
            </tr>
            <tr id="desktop-details-${inv.sale_id}" class="desktop-details-row" style="display: none;">
                <td colspan="8" style="padding: 0; border-bottom: 2px solid #e2e8f0;">
                    <div id="desktop-details-content-${inv.sale_id}" style="padding: 20px;"></div>
                </td>
            </tr>
        `;
    });

    mobileContainer.innerHTML = mobileHtml;
    tbody.innerHTML = desktopHtml;
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
                                <div class="item-qty">${Number(item.qty).toLocaleString()}${unitText}</div>
                                <div class="item-total">₭ ${Number(item.amount).toLocaleString()}</div>
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
