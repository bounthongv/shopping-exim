<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

$stmt = mysqli_prepare($con, "SELECT * FROM tb_cta WHERE Customer_ID = ? ORDER BY Id DESC LIMIT 1");
$row = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// ໄຟລ໌ສັນຍາທີ່ເຊັນແລ້ວ (ອັບໂຫລດຈາກ exim/admin/edit_cta.php ເກັບໄວ້ໃນ exim/admin/pdf_file/)
// ດຶງຜ່ານ get_signed_cta.php (proxy) ແທນການອ້າງອີງ path ຂ້າມໂຟນເດີໂດຍກົງ
// ເພາະບໍ່ແນ່ໃຈວ່າ invoice/ ກັບ exim/admin/ ຢູ່ web root ດຽວກັນໃນ server ຈິງ
$signed_file   = $row['File_CTA'] ?? '';
$has_signed    = ($signed_file !== '');
$signed_url    = $has_signed ? "get_signed_cta.php" : '';
$signed_ext    = strtolower(pathinfo($signed_file, PATHINFO_EXTENSION));
$signed_is_pdf = ($signed_ext === 'pdf');

// ຟັງຊັນຊ່ວຍໃສ່ເຄື່ອງໝາຍຖືກໃນ checkbox
function chk($value) {
    return ($value == 1) ? "&#9745;" : "&#9744;"; // ☑ : ☐
}

// ຟັງຊັນຈັດຮູບແບບວັນທີ
function fmt_date($date) {
    if (empty($date) || $date == "0000-00-00") return "";
    return date("d/m/Y", strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ຂໍ້ມູນສັນຍາ<?php echo $row ? " - " . htmlspecialchars($row['Outlet_Name']) : ""; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    * {
        box-sizing: border-box;
        font-family: 'Phetsarath OT', sans-serif !important;
    }
    body {
        font-size: 14px;
        color: #000;
        max-width: 800px;
        margin: 20px auto;
        padding: 0 20px 40px;
        background: #f1f5f9;
    }
    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .btn-back, .btn-download {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 14px;
        border: none;
        cursor: pointer;
    }
    .btn-back { background: #e2e8f0; color: #1e293b; }
    .btn-download { background: #0d6efd; color: #fff; }

    .view-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .btn-tab {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        background: #fff;
        color: #1e293b;
        border: 1px solid #cbd5e1;
    }
    .btn-tab.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
        font-weight: 600;
    }

    .view-hidden { display: none; }

    .signed-preview img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 4px;
    }
    .signed-preview iframe {
        width: 100%;
        height: 80vh;
        border: none;
        border-radius: 4px;
    }

    .sheet {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    }

    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .header-table td { vertical-align: top; border: none; }
    .header-table img { width: 90px; }
    .company-info { font-weight: bold; line-height: 1.5; }
    h2, h3 { text-align: center; margin: 4px 0; }
    .intro { text-align: left; margin: 15px 0; line-height: 1.6; }

    table.info-table, table.credit-table, table.bank-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    table.info-table td, table.credit-table td, table.bank-table td,
    table.bank-table th {
        border: 1px solid #000;
        padding: 6px 8px;
        vertical-align: middle;
    }
    .label-cell { width: 30%; font-weight: normal; }
    .value-cell { font-weight: bold; }
    .bank-table th { text-align: center; }
    .bank-table td { text-align: center; font-weight: bold; }

    .checkbox-row td { border: 1px solid #000; }

    .signature-table { width: 100%; margin-top: 50px; border: none; }
    .signature-table td { border: none; text-align: center; width: 50%; }
    .signature-line { margin-top: 60px; border-top: 1px dotted #000; width: 80%; margin-left: auto; margin-right: auto; }

    .empty-state {
        background: #fff;
        border-radius: 8px;
        padding: 60px 20px;
        text-align: center;
        color: #64748b;
    }

    @media print {
        body { background: #fff; margin: 0; padding: 0; }
        .toolbar, .view-tabs, #signed-view { display: none !important; }
        #informal-view { display: block !important; }
        .sheet { box-shadow: none; padding: 0; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <a class="btn-back" href="menu.php"><i class="bi bi-arrow-left"></i> ກັບຄືນໄປໜ້າເມນູ</a>
    <?php if ($row): ?>
    <button type="button" class="btn-download<?php echo $has_signed ? ' view-hidden' : ''; ?>" id="btn-print-informal" onclick="window.print()"><i class="bi bi-download"></i> ດາວໂຫລດ / ພິມ PDF</button>
    <?php endif; ?>
</div>

<?php if ($row && $has_signed): ?>
<div class="view-tabs">
    <button type="button" class="btn-tab active" id="tab-signed" onclick="showView('signed')">
        <i class="bi bi-patch-check-fill"></i> ໃບສັນຍາທີ່ເຊັນແລ້ວ
    </button>
    <button type="button" class="btn-tab" id="tab-informal" onclick="showView('informal')">
        <i class="bi bi-file-earmark-text"></i> ໃບສັນຍາ (ບໍ່ເປັນທາງການ)
    </button>
</div>
<?php endif; ?>

<?php if (!$row): ?>

<div class="empty-state">
    <i class="bi bi-file-earmark-x" style="font-size: 40px;"></i>
    <p style="margin-top: 15px;">ຍັງບໍ່ພົບຂໍ້ມູນສັນຍາສຳລັບບັນຊີນີ້</p>
</div>

<?php else: ?>

<?php if ($has_signed): ?>
<div class="sheet" id="signed-view">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
        <h3 style="text-align: left; margin: 0;">ໃບສັນຍາທີ່ເຊັນແລ້ວ</h3>
        <a class="btn-download" href="<?php echo $signed_url; ?>" target="_blank" download>
            <i class="bi bi-box-arrow-up-right"></i> ເປີດ / ດາວໂຫລດ
        </a>
    </div>
    <div class="signed-preview">
        <?php if ($signed_is_pdf): ?>
        <iframe src="<?php echo $signed_url; ?>" title="ໃບສັນຍາທີ່ເຊັນແລ້ວ"></iframe>
        <p style="text-align: center; color: #64748b; margin-top: 10px;">
            ຖ້າເບິ່ງເອກະສານບໍ່ໄດ້ ກົດປຸ່ມ "ເປີດ / ດາວໂຫລດ" ຢູ່ດ້ານເທິງ
        </p>
        <?php else: ?>
        <img src="<?php echo $signed_url; ?>" alt="ໃບສັນຍາທີ່ເຊັນແລ້ວ">
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="sheet<?php echo $has_signed ? ' view-hidden' : ''; ?>" id="informal-view">

<table class="header-table">
    <tr>
        <td class="company-info">
            EXIM SERVICES SOLE CO., LTD.<br>
            #888 Hom 22, Unit 46 Nakhuay Tai Village, Xaysettha District, Vientiane, Lao PDR<br>
            Tel: +856-21-264087 &nbsp; website: www.exim.la
        </td>
         <td style="width:100px;"><img src="../EXIM_logo.png" alt="logo"></td>
    </tr>
</table>

<div align="Right" style="width:100%">ເລກທີ: <?php echo htmlspecialchars($row['number_cta']); ?></div>

<h2>ໃບສັນຍາການໃຫ້ເຄດິດຮ້ານຄ້າ</h2>
<h3>Credit Term Agreement</h3>

<p class="intro">
ສັນຍາສະບັບນີ້ ແມ່ນເຮັດຂື້ນມາເພື່ອເປັນການຢັ້ງຢືນການຕົກລົງເຫັນດີຮ່ວມກັນທັງສອງຝ່າຍກ່ຽວກັບເງື່ອນໄຂຕ່າງໆ
ໃນການໃຫ້ Credit Term ສຳລັບການຊື້ຂາຍຜະລິດຕະພັນຂອງບໍລິສັດ Heineken Brewery Laos
ລະຫວ່າງຕົວແທນ Exim ແລະຮ້ານຄ້າ (ລູກຄ້າ), ດັ່ງລາຍລະອຽດລຸ່ມນີ້
</p>

<table class="info-table">
    <tr>
        <td class="label-cell">ຊື່ຮ້ານລູກຄ້າ Outlet Name:</td>
        <td class="value-cell" colspan="3"><?php echo htmlspecialchars($row['Outlet_Name']); ?></td>
    </tr>
    <tr>
        <td class="label-cell">ທີ່ຢູ່ Address</td>
        <td class="value-cell" colspan="3"><?php echo htmlspecialchars($row['Address']); ?></td>
    </tr>
    <tr>
        <td class="label-cell">ຜູ້ຕິດຕໍ່ Contact Person:</td>
        <td class="value-cell" colspan="3"><?php echo htmlspecialchars($row['Contact_Person']); ?></td>
    </tr>
    <tr>
        <td class="label-cell">ເບີໂທ Tel:</td>
        <td class="value-cell" colspan="3"><?php echo htmlspecialchars($row['Tel']); ?></td>
    </tr>
    <tr>
        <td class="label-cell">ລະຫັດລູກຄ້າ Customer ID (OMNI) ຫລື ເລກທີ່ສັນຍາ</td>
        <td class="value-cell"><?php echo htmlspecialchars($row['Customer_ID']); ?></td>
    </tr>
    <tr>
        <td class="label-cell" style="width:15%;">ວັນທີເຊັນສັນຍາ Date</td>
        <td class="value-cell"><?php echo fmt_date($row['Date']); ?></td>
    </tr>
    <tr class="checkbox-row">
        <td class="label-cell">ຊ່ອງທາງການຈຳໜ່າຍ<br>Outlet Sales Channels:</td>
        <td colspan="3">
            <?php echo chk($row['MONT_SEP']); ?> MONT (SEP) &nbsp;&nbsp;
            <?php echo chk($row['MOFT_SEP']); ?> MOFT (SEP) &nbsp;&nbsp;
            <?php echo chk($row['TONT']); ?> TONT &nbsp;&nbsp;
            <?php echo chk($row['TOFT_SPP_SLP']); ?> TOFT (SPP/SLP)
        </td>
    </tr>
    <tr>
        <td class="label-cell">ລະຫັດສາຍທາງ Route Number:</td>
        <td class="value-cell" colspan="3"><?php echo htmlspecialchars($row['Route_Number']); ?></td>
    </tr>
</table>

<table class="credit-table">
    <tr>
        <td colspan="2" style="text-align:center; font-weight:bold;">ເງື່ອນໄຂການໃຫ້ເຄດິດ Credit Terms</td>
    </tr>
    <tr>
        <td class="label-cell">ຈຳນວນວັນ</td>
        <td class="value-cell"><?php echo htmlspecialchars($row['Number_days']); ?></td>
    </tr>
    <tr>
        <td class="label-cell">ຈຳນວນໃບບິນ</td>
        <td class="value-cell"><?php echo htmlspecialchars($row['Number_bills']); ?></td>
    </tr>
    <tr>
        <td class="label-cell">ວົງເງິນເຄດິດສູງສຸດ Limited Amount</td>
        <td class="value-cell"><?php echo htmlspecialchars($row['Limited_Amount']); ?></td>
    </tr>
    <tr>
        <td class="label-cell">ກຳນົດມື້ໝົດສັນຍາ Validation Date</td>
        <td class="value-cell"><?php echo htmlspecialchars($row['Validation_Date']); ?></td>
    </tr>
</table>

<table class="bank-table">
    <tr>
        <th colspan="3">ຊ່ອງທາງການຊຳລະ:</th>
    </tr>
    <tr>
        <th>ເລກບັນຊີ Account #</th>
        <th>Account Name</th>
        <th>Bank Name</th>
    </tr>
    <tr>
        <td>162.12.00.00335568.001</td>
        <td>EXIM SOLE CO., LTD</td>
        <td>BCEL</td>
    </tr>
    <tr>
        <td>910801118888</td>
        <td>EXIM SERVICES SOLE CO., LTD</td>
        <td>Vietin Bank</td>
    </tr>
    <tr>
        <td>0302300010000131</td>
        <td>EXIM SERVICES SOLE CO., LTD</td>
        <td>LDB</td>
    </tr>
</table>

<table class="signature-table">
    <tr>
        <td>
            <div class="signature-line"></div>
            ລາຍເຊັນ-ຊື່ແຈ້ງ ຂອງພະນັກງານຂາຍຕົວແທນ
        </td>
        <td>
            <div class="signature-line"></div>
            ລາຍເຊັນ-ຊື່ແຈ້ງ ຂອງລູກຄ້າ
        </td>
    </tr>
</table>

</div>

<?php if ($has_signed): ?>
<script>
    function showView(view) {
        var signedActive = (view === 'signed');
        document.getElementById('signed-view').classList.toggle('view-hidden', !signedActive);
        document.getElementById('informal-view').classList.toggle('view-hidden', signedActive);
        document.getElementById('tab-signed').classList.toggle('active', signedActive);
        document.getElementById('tab-informal').classList.toggle('active', !signedActive);
        // ປຸ່ມພິມ PDF ໃຊ້ໄດ້ສະເພາະໃບບໍ່ເປັນທາງການ
        document.getElementById('btn-print-informal').classList.toggle('view-hidden', signedActive);
    }
</script>
<?php endif; ?>

<?php endif; ?>

</body>
</html>
