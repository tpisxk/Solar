<?php
require_once 'conn.php';

// รับค่าเดือนและปีสำหรับ redirect กลับหน้าปฏิทิน
$redirect_month = isset($_POST['redirect_month']) ? intval($_POST['redirect_month']) : date('n');
$redirect_year = isset($_POST['redirect_year']) ? intval($_POST['redirect_year']) : date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id               = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $title            = trim($_POST['title']);
    $appointment_date = $_POST['appointment_date'];
    $start_time       = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time         = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $status           = !empty($_POST['status']) ? $_POST['status'] : 'pending';
    $details          = trim($_POST['details']);

    // ตรวจสอบข้อมูลเบื้องต้น
    if (!empty($title) && !empty($appointment_date)) {
        try {
            // ดึงเดือน/ปี จากวันที่นัดหมาย เพื่อให้ปฏิทินแสดงผลตรงกับวันที่บันทึก
            $date_parts = explode('-', $appointment_date);
            if (count($date_parts) === 3) {
                $redirect_month = intval($date_parts[1]);
                $redirect_year = intval($date_parts[0]);
            }

            if ($id) {
                // 1. อัปเดตข้อมูลเดิม (Update)
                $stmt = $pdo->prepare("UPDATE appointments SET title = ?, appointment_date = ?, start_time = ?, end_time = ?, status = ?, details = ? WHERE id = ?");
                $stmt->execute([$title, $appointment_date, $start_time, $end_time, $status, $details, $id]);
                
                header("Location: calendar.php?status=success&action=edit&month={$redirect_month}&year={$redirect_year}");
                exit();
            } else {
                // 2. เพิ่มข้อมูลใหม่ (Create)
                $stmt = $pdo->prepare("INSERT INTO appointments (title, appointment_date, start_time, end_time, status, details) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $appointment_date, $start_time, $end_time, $status, $details]);
                
                header("Location: calendar.php?status=success&action=add&month={$redirect_month}&year={$redirect_year}");
                exit();
            }
        } catch (PDOException $e) {
            // กรณีเกิดข้อผิดพลาดจากฐานข้อมูล
            header("Location: calendar.php?status=error&month={$redirect_month}&year={$redirect_year}");
            exit();
        }
    }
}

// หากเข้าไฟล์นี้โดยตรงหรือข้อมูลไม่ครบถ้วน
header("Location: calendar.php?month={$redirect_month}&year={$redirect_year}");
exit();