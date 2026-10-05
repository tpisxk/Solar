<?php
require_once 'conn.php';
$current_page = 'User/stockout'; // กำหนดหน้าปัจจุบันสำหรับ Sidebar

// 1. ดึงยอดรวมจำนวนสินค้าทั้งหมดในคลัง สำหรับแสดงที่ Sidebar (ใช้ stock_quantity)
try {
    $stmt_count = $pdo->query("SELECT SUM(stock_quantity) as total_stock FROM products");
    $row_count = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $total_inventory_count = $row_count['total_stock'] ?? 0;
} catch (PDOException $e) {
    $total_inventory_count = 0;
}

// 2. ดึงรายการสินค้าทั้งหมด พร้อมระบุจำนวนสต็อกปัจจุบัน เพื่อแสดงใน Dropdown
try {
    $stmt_products = $pdo->query("SELECT id, name, stock_quantity FROM products ORDER BY name ASC");
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

// 3. ดึงประวัติการจ่ายออกสินค้าล่าสุดจาก stock_transactions (กรองเฉพาะ type = 'out')
try {
    $stmt_history = $pdo->query("
        SELECT stock_transactions.*, products.name as product_name 
        FROM stock_transactions 
        LEFT JOIN products ON stock_transactions.product_id = products.id 
        WHERE stock_transactions.type = 'out' 
        ORDER BY stock_transactions.created_at DESC 
        LIMIT 50
    ");
    $stockOutHistory = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stockOutHistory = [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จ่ายออกสินค้า - SolarStock Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'Inter', 'sans-serif'] },
                    colors: { darkSidebar: '#000007', brandGreen: '#059669' }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans h-screen flex overflow-hidden">

    <!-- เรียกใช้งาน Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-slate-100">
        
        <!-- Header Bar -->
        <header class="h-20 bg-white border-b border-slate-200/80 flex items-center justify-between px-10 shrink-0 z-10 shadow-xs">
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">จ่ายออกสินค้าจากคลัง</h2>
            <div class="flex items-center gap-3">
                <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/60 px-4 py-1.5 rounded-full text-xs font-semibold">
                    ระบบจัดการคลังสินค้า
                </span>
            </div>
        </header>

        <!-- Main Body -->
        <div class="flex-1 overflow-y-auto p-10 space-y-6">
            
            <!-- แจ้งเตือนสถานะ -->
            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] == 'success'): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-2xl text-sm font-medium flex items-center justify-between shadow-xs">
                        <span>บันทึกจ่ายออกสินค้าเรียบร้อยแล้ว และตัดสต็อกสำเร็จ</span>
                        <a href="stockout.php" class="text-emerald-700 font-bold hover:underline text-xs">ปิด</a>
                    </div>
                <?php elseif ($_GET['status'] == 'insufficient'): ?>
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-6 py-4 rounded-2xl text-sm font-medium flex items-center justify-between shadow-xs">
                        <span>สินค้าในสต็อกไม่เพียงพอต่อจำนวนที่ต้องการจ่ายออก</span>
                        <a href="stockout.php" class="text-amber-700 font-bold hover:underline text-xs">ปิด</a>
                    </div>
                <?php elseif ($_GET['status'] == 'error'): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-6 py-4 rounded-2xl text-sm font-medium flex items-center justify-between shadow-xs">
                        <span>เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง</span>
                        <a href="stockout.php" class="text-rose-700 font-bold hover:underline text-xs">ปิด</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ฟอร์มบันทึกจ่ายออกสินค้า -->
            <div class="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-xs space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">ฟอร์มจ่ายออกสินค้า (เบิกใช้งาน/ติดตั้ง)</h3>
                    <p class="text-xs text-slate-500 mt-1">เลือกรายการสินค้า ระบุจำนวนที่ต้องการเบิก และรายละเอียดโครงการหรือหน้างาน</p>
                </div>

                <form action="stockout_process.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-600">เลือกสินค้า (แสดงจำนวนคงเหลือในคลัง)</label>
                        <select name="product_id" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs bg-slate-50 focus:outline-none focus:border-emerald-600 font-medium">
                            <option value="">-- กรุณาเลือกสินค้า --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['name']) ?> — (คงเหลือ: <?= number_format($p['stock_quantity'] ?? 0) ?> ชิ้น)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-600">จำนวนที่จ่ายออก</label>
                        <input type="number" name="quantity" min="1" required placeholder="ระบุจำนวน..." class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs bg-slate-50 focus:outline-none focus:border-emerald-600 font-medium">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-600">หน้างาน / โครงการ / หมายเหตุ</label>
                        <input type="text" name="note" placeholder="เช่น หน้างานบ้านคุณ A..." class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs bg-slate-50 focus:outline-none focus:border-emerald-600 font-medium">
                    </div>
                                <!-- เพิ่มส่วนนี้เข้าไปในฟอร์มของ stockout.php -->
<div class="space-y-1.5 md:col-span-2">
    <label class="block text-xs font-semibold text-slate-600">Mหน้างาน / โครงการที่เบิกไปติดตั้ง</label>
    <select name="installation_id" class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs bg-slate-50 focus:outline-none focus:border-emerald-600 font-medium">
        <option value="">-- ไม่ระบุโครงการ (เบิกกลาง / สต็อกทั่วไป) --</option>
        <?php
        // ดึงรายการโครงการทั้งหมดมาแสดง
        $stmt_inst = $pdo->query("SELECT id, name FROM installations ORDER BY id DESC");
        while ($inst = $stmt_inst->fetch(PDO::FETCH_ASSOC)) {
            echo '<option value="'.$inst['id'].'">'.htmlspecialchars($inst['name']).'</option>';
        }
        ?>
    </select>
</div>


                    <div class="md:col-span-3 flex justify-end pt-2">
                        <button type="submit" class="bg-brandGreen hover:bg-emerald-700 text-white px-6 py-3 rounded-2xl text-xs font-semibold shadow-md shadow-emerald-600/25 transition-all">
                            ยืนยันจ่ายออกสินค้า
                        </button>
                    </div>
                </form>
            </div>

            <!-- ตารางประวัติการจ่ายออกสินค้า -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 px-2">ประวัติการจ่ายออกสินค้าล่าสุด</h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 font-semibold">วันที่ / เวลา</th>
                                <th class="py-3 px-4 font-semibold">ชื่อสินค้า</th>
                                <th class="py-3 px-4 font-semibold">จำนวนที่จ่ายออก</th>
                                <th class="py-3 px-4 font-semibold">หน้างาน / หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <?php if (!empty($stockOutHistory)): ?>
                                <?php foreach ($stockOutHistory as $row): ?>
                                    <tr class="hover:bg-slate-50/80 transition-all">
                                        <td class="py-3.5 px-4 text-slate-500"><?= htmlspecialchars($row['created_at']) ?></td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($row['product_name'] ?? 'ไม่พบสินค้า') ?></td>
                                        <td class="py-3.5 px-4 text-rose-600 font-bold">-<?= htmlspecialchars($row['quantity']) ?></td>
                                        <td class="py-3.5 px-4 text-slate-500"><?= htmlspecialchars($row['note'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-slate-400 font-normal">ยังไม่มีประวัติการจ่ายออกสินค้า</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>