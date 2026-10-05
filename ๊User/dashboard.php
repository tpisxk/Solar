<?php
require_once 'conn.php';
$current_page = 'dashboard';

// กำหนดค่าเริ่มต้นเพื่อป้องกัน Error
$total_items = 0;
$total_qty = 0;
$total_value = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;
$normal_stock_count = 0;
$total_project_cost = 0;
$chart_products = [];
$installations_list = [];
$low_stock_products = [];

try {
    // 1. ดึงจำนวนรายการสินค้าทั้งหมดในตาราง products
    $stmt_total_items = $pdo->query("SELECT COUNT(*) FROM products");
    $total_items = $stmt_total_items->fetchColumn();

    // 2. ดึงผลรวมจำนวนชิ้นสินค้าคงเหลือทั้งหมด (stock_quantity)
    $stmt_total_qty = $pdo->query("SELECT SUM(stock_quantity) FROM products");
    $total_qty = $stmt_total_qty->fetchColumn() ?: 0;

    // 3. ดึงมูลค่ารวมของสต็อกทั้งหมด (stock_quantity * cost_price)
    $stmt_total_value = $pdo->query("SELECT SUM(stock_quantity * cost_price) FROM products");
    $total_value = $stmt_total_value->fetchColumn() ?: 0;

    // 4. นับจำนวนสินค้าแยกตามสถานะ
    $stmt_out = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 0");
    $out_of_stock_count = $stmt_out->fetchColumn();

    $stmt_low = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity > 0 AND stock_quantity <= 5");
    $low_stock_count = $stmt_low->fetchColumn();

    $stmt_normal = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity > 5");
    $normal_stock_count = $stmt_normal->fetchColumn();

    // 5. ดึงข้อมูลสำหรับทำกราฟ (สินค้า 6 อันดับแรกที่คงเหลือมากที่สุด)
    $stmt_chart = $pdo->query("SELECT name, stock_quantity FROM products ORDER BY stock_quantity DESC LIMIT 6");
    $chart_products = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    // 6. ดึงข้อมูลโครงการติดตั้งและค่าใช้จ่ายจากตาราง installations
    $stmt_install = $pdo->query("SELECT * FROM installations ORDER BY id DESC");
    $installations_list = $stmt_install->fetchAll(PDO::FETCH_ASSOC);

    // 7. คำนวณผลรวมค่าใช้จ่ายโครงการทั้งหมด
    foreach ($installations_list as $inst) {
        $total_project_cost += floatval($inst['cost'] ?? ($inst['budget'] ?? 0));
    }

    // 8. ดึงรายการสินค้าที่ใกล้หมดหรือหมด (stock_quantity <= 5) สำหรับแจ้งเตือน
    $stmt_alert = $pdo->query("SELECT * FROM products WHERE stock_quantity <= 5 ORDER BY stock_quantity ASC LIMIT 10");
    $low_stock_products = $stmt_alert->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// เตรียมข้อมูลสำหรับส่งให้ JavaScript Chart.js
$chart_labels = [];
$chart_data = [];
foreach ($chart_products as $cp) {
    $chart_labels[] = $cp['name'];
    $chart_data[] = intval($cp['stock_quantity']);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard สรุปภาพรวม - SolarStock Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js สำหรับแสดงกราฟ -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'Inter', 'sans-serif'] },
                    colors: { brandGreen: '#059669' }
                }
            }
        }
    </script>
    <style>::-webkit-scrollbar { width: 6px; height: 6px; } ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }</style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans h-screen flex overflow-hidden">

    <?php 
    if (file_exists('sidebar.php')) {
        include 'sidebar.php'; 
    }
    ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden bg-slate-100">
        
        <!-- Header -->
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0 z-10 shadow-sm">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">ภาพรวมคลังสินค้าและค่าใช้จ่ายโครงการ</h2>
                <p class="text-sm text-slate-500 mt-0.5">ระบบจัดการและวิเคราะห์ข้อมูลสต็อกอุปกรณ์และงบประมาณโซล่าเซลล์</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="calendar.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all flex items-center gap-2">
                    <i class="fa-solid fa-calendar-days text-xs"></i> ไปยังปฏิทินงาน
                </a>
            </div>
        </header>

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto p-8 space-y-6">
            
            <?php if (isset($db_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl text-sm">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: <?= htmlspecialchars($db_error) ?>
                </div>
            <?php endif; ?>

            <!-- Cards สถิติภาพรวม 4 ช่อง -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/85 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">รายการสินค้าทั้งหมด</p>
                        <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($total_items) ?> <span class="text-sm font-normal text-slate-500">รายการ</span></h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-brandGreen flex items-center justify-center text-xl shadow-inner">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/85 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">มูลค่าสต็อกรวม</p>
                        <h3 class="text-2xl font-extrabold text-slate-900">฿<?= number_format($total_value, 2) ?></h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/85 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">ค่าใช้จ่ายโครงการรวม</p>
                        <h3 class="text-2xl font-extrabold text-blue-600">฿<?= number_format($total_project_cost, 2) ?></h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/85 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">สินค้าใกล้หมด / หมด</p>
                        <h3 class="text-2xl font-extrabold text-rose-600"><?= number_format($low_stock_count + $out_of_stock_count) ?> <span class="text-sm font-normal text-slate-500">รายการ</span></h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
            </div>

            <!-- ส่วนแสดงกราฟสถิติ (Charts) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- กราฟแท่ง: สินค้าคงเหลือสูงสุด -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/85 shadow-sm lg:col-span-2 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">สถิติสินค้าคงเหลือสูงสุด (Top Stock Items)</h3>
                            <p class="text-xs text-slate-500">เปรียบเทียบปริมาณสินค้าที่มีจำนวนมากที่สุด 6 อันดับแรก</p>
                        </div>
                    </div>
                    <div class="relative h-72 w-full">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>

                <!-- กราฟโดนัท: สัดส่วนสถานะสินค้า -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/85 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">สัดส่วนสถานะสต็อก</h3>
                        <p class="text-xs text-slate-500">สถานะความพร้อมของสินค้าทั้งหมดในคลัง</p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center my-2">
                        <canvas id="doughnutChart"></canvas>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center text-xs font-semibold pt-2 border-t border-slate-100">
                        <div class="text-emerald-600">ปกติ: <?= $normal_stock_count ?></div>
                        <div class="text-amber-500">ใกล้หมด: <?= $low_stock_count ?></div>
                        <div class="text-rose-600">หมด: <?= $out_of_stock_count ?></div>
                    </div>
                </div>
            </div>

            <!-- สองตารางเปรียบเทียบ: รายการโครงการติดตั้งและค่าใช้จ่าย VS สินค้าใกล้หมด -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- ตารางโครงการติดตั้งและค่าใช้จ่าย -->
                <div class="bg-white rounded-3xl border border-slate-200/85 shadow-sm overflow-hidden p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900"><i class="fa-solid fa-clipboard-list text-brandGreen me-2"></i> โครงการติดตั้งและค่าใช้จ่าย</h3>
                            <p class="text-xs text-slate-500">แสดงงบประมาณและการดำเนินงานแต่ละโครงการ</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-80 overflow-y-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <tr>
                                    <th class="py-3 px-3">โครงการ</th>
                                    <th class="py-3 px-3">ค่าใช้จ่าย</th>
                                    <th class="py-3 px-3">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                                <?php if (count($installations_list) > 0): ?>
                                    <?php foreach ($installations_list as $item): 
                                        $p_name = $item['name'] ?? ($item['project_name'] ?? ($item['customer_name'] ?? 'โครงการ'));
                                        $p_cost = $item['cost'] ?? ($item['budget'] ?? 0);
                                        $p_status = $item['status'] ?? 'pending';

                                        if ($p_status == 'completed' || $p_status == 'สำเร็จ') {
                                            $badge = '<span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold">เสร็จสิ้น</span>';
                                        } elseif ($p_status == 'in_progress' || $p_status == 'กำลังดำเนินการ') {
                                            $badge = '<span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full font-bold">ดำเนินการ</span>';
                                        } else {
                                            $badge = '<span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-bold">รอดำเนินการ</span>';
                                        }
                                    ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-3 px-3 font-bold text-slate-900"><?= htmlspecialchars($p_name) ?></td>
                                            <td class="py-3 px-3 font-semibold text-emerald-600">฿<?= number_format($p_cost, 2) ?></td>
                                            <td class="py-3 px-3"><?= $badge ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-6 text-slate-400">ยังไม่มีข้อมูลโครงการติดตั้ง</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ตารางสินค้าใกล้หมดสต็อก (Low Stock Alert) -->
                <div class="bg-white rounded-3xl border border-slate-200/85 shadow-sm overflow-hidden p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900"><i class="fa-solid fa-triangle-exclamation text-rose-500 me-2"></i> สินค้าใกล้หมด / ต้องเติมสต็อก</h3>
                            <p class="text-xs text-slate-500">รายการสินค้าที่มีจำนวนคงเหลือเหลือน้อยกว่าหรือเท่ากับ 5 ชิ้น</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-80 overflow-y-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <tr>
                                    <th class="py-3 px-3">SKU / ชื่อสินค้า</th>
                                    <th class="py-3 px-3">หมวดหมู่</th>
                                    <th class="py-3 px-3">คงเหลือ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                                <?php if (count($low_stock_products) > 0): ?>
                                    <?php foreach ($low_stock_products as $prod): 
                                        $sku = $prod['sku'] ?? '-';
                                        $name = $prod['name'] ?? 'ไม่มีชื่อ';
                                        $cat = $prod['category'] ?? '-';
                                        $qty = $prod['stock_quantity'] ?? 0;
                                    ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-3 px-3">
                                                <span class="font-bold text-slate-900 block"><?= htmlspecialchars($name) ?></span>
                                                <span class="font-mono text-[10px] text-slate-400"><?= htmlspecialchars($sku) ?></span>
                                            </td>
                                            <td class="py-3 px-3 text-slate-500"><?= htmlspecialchars($cat) ?></td>
                                            <td class="py-3 px-3 font-extrabold text-rose-600"><?= number_format($qty) ?> ชิ้น</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-6 text-slate-400">ยอดเยี่ยม! ไม่มีสินค้าใกล้หมดในขณะนี้</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- สคริปต์สร้างกราฟด้วย Chart.js -->
    <script>
        // ข้อมูลสำหรับกราฟแท่ง
        const barLabels = <?= json_encode($chart_labels) ?>;
        const barDataValues = <?= json_encode($chart_data) ?>;

        const ctxBar = document.getElementById('barChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{
                    label: 'จำนวนคงเหลือ (ชิ้น)',
                    data: barDataValues,
                    backgroundColor: 'rgba(5, 150, 105, 0.8)',
                    borderColor: 'rgba(5, 150, 105, 1)',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // ข้อมูลสำหรับกราฟโดนัท
        const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: ['ปกติ', 'ใกล้หมด', 'หมด'],
                datasets: [{
                    data: [<?= $normal_stock_count ?>, <?= $low_stock_count ?>, <?= $out_of_stock_count ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { family: 'Prompt', size: 12 } }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>