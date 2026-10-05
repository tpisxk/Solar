<?php
session_start();
require_once '../db/conn.php';

// ตรวจสอบสิทธิ์เฉพาะ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 1) {
    header("Location: ../User/login.php");
    exit();
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: manage_users.php");
    exit();
}

// อัปเดตข้อมูลเมื่อมีการกดส่งฟอร์ม (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $user_type_id = intval($_POST['user_type_id']);
    
    // ตรวจสอบว่ามีการกรอกรหัสผ่านใหม่หรือไม่
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, user_type_id = ?, password = ? WHERE id = ?");
        $stmt->execute([$name, $user_type_id, $password, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, user_type_id = ? WHERE id = ?");
        $stmt->execute([$name, $user_type_id, $user_id]);
    }
    
    header("Location: manage_users.php?success=updated");
    exit();
}

// ดึงข้อมูลผู้ใช้ที่ต้องการแก้ไข
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: manage_users.php");
    exit();
}

// ดึงรายการสิทธิ์ทั้งหมดจากตาราง user_type
$roles = $pdo->query("SELECT * FROM user_type")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลผู้ใช้ - SolarWing Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Prompt', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200 shadow-xl p-8 space-y-6">
        
        <!-- ส่วนหัวข้อ -->
        <div class="text-center space-y-1">
            <h1 class="text-xl font-bold text-slate-900"><i class="fas fa-user-edit text-emerald-600 mr-2"></i> แก้ไขข้อมูลผู้ใช้งาน</h1>
            <p class="text-xs text-slate-500">Username: <span class="text-slate-800 font-mono font-medium"><?php echo htmlspecialchars($user['username']); ?></span></p>
        </div>

        <!-- ฟอร์มแก้ไขข้อมูล -->
        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="text-xs font-semibold text-slate-600 block mb-1">ชื่อ-นามสกุล (Name)</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required 
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600 block mb-1">สิทธิ์การใช้งาน (Role)</label>
                <select name="user_type_id" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo ($user['user_type_id'] == $r['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['type_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600 block mb-1">รหัสผ่านใหม่ <span class="text-slate-400 font-normal">(ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน)</span></label>
                <input type="password" name="password" placeholder="••••••••" 
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
            </div>

            <!-- ปุ่มกดดำเนินการ -->
            <div class="flex gap-3 pt-2">
                <a href="manage_users.php" class="w-1/2 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 text-center text-xs font-semibold rounded-2xl transition border border-slate-200 flex items-center justify-center">
                    ยกเลิก
                </a>
                <button type="submit" class="w-1/2 py-3 bg-emerald-600 hover:bg-emerald-500 text-white text-center text-xs font-semibold rounded-2xl shadow-lg shadow-emerald-600/25 transition">
                    บันทึกข้อมูล
                </button>
            </div>
        </form>

    </div>

</body>
</html>