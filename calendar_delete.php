<?php
require_once 'conn.php';

// รับค่าเดือนและปีสำหรับ redirect กลับหน้าปฏิทินให้ตรงกับเดือนเดิม
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->execute([$id]);

        // ส่ง action=delete เพื่อให้หน้า calendar.php แสดง Popup แจ้งเตือนลบสำเร็จ
        header("Location: calendar.php?status=success&action=delete&month={$month}&year={$year}");
        exit();
    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาดในการลบข้อมูล
        header("Location: calendar.php?status=error&month={$month}&year={$year}");
        exit();
    }
}

// หากไม่มีการส่ง ID มา ให้กลับหน้าปฏิทินปกติ
header("Location: calendar.php?month={$month}&year={$year}");
exit();