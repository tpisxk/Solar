<?php
session_start();

require_once 'db/conn.php'; // ไฟล์เชื่อมต่อฐานข้อมูลของคุณ

$login_success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // ดึงข้อมูลผู้ใช้ พร้อม JOIN กับตาราง user_type ตามโครงสร้าง
    $stmt = $pdo->prepare("
        SELECT u.*, ut.type_name 
        FROM users u 
        JOIN user_type ut ON u.user_type_id = ut.id 
        WHERE u.username = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // ตรวจสอบรหัสผ่าน
    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = strtolower($user['type_name']); 
        $_SESSION['user_type_id'] = $user['user_type_id']; 
        
        $login_success = true; // ตั้งค่าสถานะเป็นจริงเพื่อเรียกใช้งาน SweetAlert แจ้งเตือนสำเร็จ
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="Pic/456456.png">
    <title>เข้าสู่ระบบ - Solarwing Management</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 สำหรับกล่องแจ้งเตือนสำเร็จ -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4 bg-[url('Pic/bg3.png')] bg-cover bg-center bg-no-repeat">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl overflow-hidden p-8 space-y-8">
        <!-- Header Logo / Title -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-20 h-20">
                <img src="Pic/SW.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Solarwing Stock Management</h1>
            <p class="text-xs text-slate-500">กรุณาเข้าสู่ระบบเพื่อจัดการอุปกรณ์และคลังสินค้า</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl text-xs flex items-center space-x-2 animate-shake">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form id="loginForm" action="login.php" method="POST" class="space-y-5">
            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-700 block">ชื่อผู้ใช้งาน (Username)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <i class="fa-regular fa-user"></i>
                    </span>
                    <input type="text" name="username" required 
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition"
                           placeholder="ระบุชื่อผู้ใช้งาน">
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-700 block">รหัสผ่าน (Password)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <i class="fa-regular fa-lock"></i>
                    </span>
                    <input type="password" name="password" required 
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition"
                           placeholder="••••••••">
                </div>
            </div>

            <button type="submit" id="submitBtn"
                    class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-2xl shadow-lg shadow-emerald-600/30 transition-all text-sm flex items-center justify-center space-x-2">
                <span id="btnText">เข้าสู่ระบบ</span>
                <i id="btnIcon" class="fa-solid fa-arrow-right-to-bracket"></i>
            </button>
        </form>

        <!-- Footer Note -->
        <div class="text-center pt-2 border-t border-slate-100">
            <p class="text-[11px] text-slate-400">Solar Cell Equipment Management &copy; 2026</p>
        </div>
    </div>

    <!-- JavaScript สำหรับจัดการปุ่มหมุน (Loading Spinner) และ Pop-up แจ้งเตือนสำเร็จ -->
    <script>
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnIcon = document.getElementById('btnIcon');

        loginForm.addEventListener('submit', function(e) {
            // เปลี่ยนข้อความและแสดงไอคอนหมุนๆ (Spinner) ทันทีที่กดปุ่ม
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            btnText.innerText = 'กำลังตรวจสอบ...';
            btnIcon.className = 'fa-solid fa-circle-notch fa-spin'; // เปลี่ยนเป็นไอคอนหมุน
        });

        // หาก PHP ตรวจสอบรหัสผ่านผ่านแล้ว ให้แสดง Pop-up แจ้งเตือนสำเร็จขนาดเล็ก
        <?php if ($login_success): ?>
        Swal.fire({
            icon: 'success',
            title: 'เข้าสู่ระบบสำเร็จ!',
            text: 'กำลังพาท่านเข้าสู่ระบบ...',
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false,
            didClose: () => {
                window.location.href = 'User/dashboard.php';
            }
        });
        // เผื่อกรณีกดปิดหรือหมดเวลา ให้เด้งไปหน้า Dashboard ทันที
        setTimeout(() => {
            window.location.href = 'User/dashboard.php';
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>