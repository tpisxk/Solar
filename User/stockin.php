<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once '../db/conn.php';
require_once '../auth_check.php';
$current_page = 'stockin'; // กำหนดหน้าปัจจุบันสำหรับ Sidebar

// 1. ดึงรายการสินค้าทั้งหมดสำหรับใส่ใน Dropdown เลือกสินค้า
try {
    $stmt_products = $pdo->query("SELECT id, name, sku, stock_quantity FROM products ORDER BY name ASC");
    $productsList = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $productsList = [];
}

// 2. ดึงประวัติการรับเข้าสินค้าล่าสุด (type = 'in')
try {
    $stmt_history = $pdo->query("
        SELECT st.*, p.name as product_name, p.sku 
        FROM stock_transactions st
        JOIN products p ON st.product_id = p.id
        WHERE st.type = 'in'
        ORDER BY st.created_at DESC 
        LIMIT 50
    ");
    $historyList = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historyList = [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รับสินค้าเข้าคลัง - SolarStock Pro</title>
    <link rel="icon" type="image/png" href="../Pic/456456.png">
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

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-slate-100">
        
        <!-- Header Bar -->
        <header class="h-20 bg-white border-b border-slate-200/80 flex items-center justify-between px-10 shrink-0 z-10 shadow-xs">
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">รับสินค้าเข้าคลัง (Stock In)</h2>
            <div class="flex items-center gap-3">
                <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/60 px-4 py-1.5 rounded-full text-xs font-semibold">
                    ระบบจัดการคลังสินค้าโซล่าเซลล์
                </span>
            </div>
        </header>

        <!-- Main Body -->
        <div class="flex-1 overflow-y-auto p-10 space-y-6">
            
            <!-- แจ้งเตือนสถานะ -->
            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] == 'success'): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-2xl text-sm font-medium flex items-center justify-between shadow-xs">
                        <span>บันทึกการรับสินค้าเข้าคลังเรียบร้อยแล้ว และเพิ่มจำนวนสต็อกสำเร็จ</span>
                        <a href="stockin.php" class="text-emerald-700 font-bold hover:underline text-xs">ปิด</a>
                    </div>
                <?php elseif ($_GET['status'] == 'error'): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-6 py-4 rounded-2xl text-sm font-medium flex items-center justify-between shadow-xs">
                        <span>เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง</span>
                        <a href="stockin.php" class="text-rose-700 font-bold hover:underline text-xs">ปิด</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ฟอร์มรับเข้าสินค้า -->
            <div class="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-xs space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">ฟอร์มรับเข้าสินค้า (Restock / Purchase)</h3>
                    <p class="text-xs text-slate-500 mt-1">เลือกรายการสินค้า ระบุจำนวนที่ต้องการรับเข้าคลัง และข้อมูลอ้างอิง</p>
                </div>

                <form action="stockin_process.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600">เลือกสินค้า (แสดงจำนวนคงเหลือในคลังปัจจุบัน)</label>
                        <select name="product_id" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs bg-slate-50 focus:outline-none focus:border-emerald-600 font-medium">
                            <option value="">-- กรุณาเลือกสินค้า --</option>
                            <?php foreach ($productsList as $prod): ?>
                                <option value="<?= $prod['id'] ?>">
                                    <?= htmlspecialchars($prod['name']) ?> (SKU: <?= htmlspecialchars($prod['sku'] ?? '-') ?>) — คงเหลือ: <?= $prod['stock_quantity'] ?> ชิ้น
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-600">จำนวนที่รับเข้า</label>
                        <input type="number" name="quantity" min="1" required placeholder="ระบุจำนวน..." class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs bg-slate-50 focus:outline-none focus:border-emerald-600 font-medium">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-600">เลขที่อ้างอิง (Reference / PO)</label>
                        <input type="text" name="reference_no" placeholder="เช่น PO-2026-001..." class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs bg-slate-50 focus:outline-none focus:border-emerald-600 font-medium">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600">หมายเหตุ / ผู้จำหน่าย (Supplier)</label>
                        <input type="text" name="note" placeholder="เช่น รับสินค้าจากบริษัทโซล่าเซลล์จำกัด..." class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs bg-slate-50 focus:outline-none focus:border-emerald-600 font-medium">
                    </div>

                    <div class="md:col-span-3 flex justify-end pt-2">
                        <button type="submit" class="bg-brandGreen hover:bg-emerald-700 text-white px-6 py-3 rounded-2xl text-xs font-semibold shadow-md shadow-emerald-600/25 transition-all">
                            ยืนยันรับเข้าสินค้า
                        </button>
                    </div>
                </form>
            </div>

            <!-- ตารางประวัติการรับเข้าสินค้าล่าสุด -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 px-2">ประวัติการรับเข้าสินค้าล่าสุด</h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 font-semibold">วันที่ / เวลา</th>
                                <th class="py-3 px-4 font-semibold">ชื่อสินค้า</th>
                                <th class="py-3 px-4 font-semibold text-right">จำนวนที่รับเข้า</th>
                                <th class="py-3 px-4 font-semibold">เลขที่อ้างอิง</th>
                                <th class="py-3 px-4 font-semibold">หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <?php if (!empty($historyList)): ?>
                                <?php foreach ($historyList as $row): ?>
                                    <tr class="hover:bg-slate-50/80 transition-all">
                                        <td class="py-3.5 px-4 text-slate-500"><?= htmlspecialchars($row['created_at']) ?></td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900">
                                            <?= htmlspecialchars($row['product_name']) ?>
                                            <div class="text-[10px] text-slate-400 font-normal">SKU: <?= htmlspecialchars($row['sku'] ?? '-') ?></div>
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-bold text-emerald-600">+<?= number_format($row['quantity']) ?></td>
                                        <td class="py-3.5 px-4 text-slate-600"><?= htmlspecialchars($row['reference_no'] ?: '-') ?></td>
                                        <td class="py-3.5 px-4 text-slate-500"><?= htmlspecialchars($row['note'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-400 font-normal">ยังไม่มีประวัติการรับเข้าสินค้าในระบบ</td>
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