<?php
// ตรวจสอบชื่อไฟล์ปัจจุบันเพื่อใช้ไฮไลต์เมนูที่กำลังใช้งานอยู่
$current_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!-- 🌐 ส่วนเมนูด้านซ้าย (Sidebar) -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รับเข้าสินค้า - SolarStock Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome CSS -->
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
    <aside class="w-64 bg-darkSidebar text-slate-300 flex flex-col h-full shrink-0 border-r border-slate-800">
        <div class="h-20 flex items-center px-6 border-b border-slate-800/80">
            <h1 class="text-white font-bold text-lg tracking-tight">SolarStock <span class="text-emerald-500">Pro</span></h1>
        </div>

        <div class="flex-1 overflow-y-auto py-5 px-3.5 space-y-1.5 text-xs font-medium">
            <a href="dashboard.php" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'dashboard') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <i class="fas fa-calendar-alt w-5 text-center text-sm"></i> ข้อมูล Dashboard
            </a>
            <a href="calendar.php" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'calendar') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <i class="fas fa-calendar-alt w-5 text-center text-sm"></i> Calendar (ปฏิทินงาน)
            </a>
            <a href="inventory.php" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'inventory') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <i class="fas fa-boxes w-5 text-center text-sm"></i> สต็อกคงเหลือ
            </a>
            <a href="stockin.php" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'stockin') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <i class="fas fa-cart-arrow-down w-5 text-center text-sm"></i> รับเข้าสินค้า
            </a>
            <a href="stockout.php" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'stockout') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <i class="fas fa-truck-loading w-5 text-center text-sm"></i> จ่ายออกสินค้า
            </a>
            <a href="expenses.php" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'expenses') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <i class="fas fa-receipt w-5 text-center text-sm"></i> ค่าใช้จ่ายอื่นๆ
            </a>
            <a href="addproduct.php" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'addproduct') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <i class="fas fa-plus-circle w-5 text-center text-sm"></i> เพิ่มสินค้าใหม่
            </a>
            <a href="locations.php" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'locations') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <i class="fas fa-map-marker-alt w-5 text-center text-sm"></i> สถานที่ติดตั้ง
            </a>
            
            <div class="pt-3 pb-2"><hr class="border-slate-800/80"></div>

            <a href="orders.php" class="w-full flex items-center justify-between px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'orders') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <div class="flex items-center gap-3.5"><i class="fas fa-shopping-bag w-5 text-center text-sm"></i> สั่งซื้อสินค้า</div>
                <span class="bg-slate-800 text-slate-400 text-[10px] font-bold px-2 py-0.5 rounded-full">0</span>
            </a>
            <a href="pending.php" class="w-full flex items-center justify-between px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'pending') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <div class="flex items-center gap-3.5"><i class="fas fa-shipping-fast w-5 text-center text-sm"></i> รอรับสินค้า</div>
                <span class="bg-emerald-950/80 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-800/50">0</span>
            </a>
            <a href="employees.php" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all <?= ($current_page == 'employees') ? 'bg-brandGreen text-white shadow-md shadow-emerald-900/20' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>">
                <i class="fas fa-users w-5 text-center text-sm"></i> รายชื่อพนักงาน
            </a>
        </div>
    </aside>