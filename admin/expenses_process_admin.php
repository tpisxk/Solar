<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once '../db/conn.php';
require_once '../auth_check.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าที่ส่งมาจากฟอร์มในหน้า expenses.php
    $title    = isset($_POST['title']) ? trim($_POST['title']) : '';
    $amount   = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $category = isset($_POST['category']) ? trim($_POST['category']) : 'ทั่วไป';

    // ตรวจสอบความถูกต้องเบื้องต้น
    if (empty($title) || $amount <= 0) {
        header("Location: expenses_admin.php?status=error");
        exit();
    }

    try {
        // บันทึกข้อมูลค่าใช้จ่ายทั่วไปลงในตาราง expenses
        // หมายเหตุ: หากในฐานข้อมูลของคุณคอลัมน์เก็บชื่อรายการใช้ชื่อ description แทน title ให้เปลี่ยนในคำสั่ง SQL ด้านล่างนี้ได้ครับ
        $stmt = $pdo->prepare("INSERT INTO expenses (title, amount, category, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$title, $amount, $category]);

        // บันทึกสำเร็จ กลับไปหน้า expenses พร้อมแจ้งเตือน success
        header("Location: admin/expenses_admin.php?status=success");
        exit();

    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด ให้กลับไปหน้า expenses พร้อมแจ้งเตือน error
        header("Location: admin/expenses_admin.php?status=error");
        exit();
    }
} else {
    // ถ้าไม่ได้เข้ามาผ่านวิธีกด Submit จากฟอร์ม ให้เด้งกลับหน้า expenses ทันที
    header("Location: admin/expenses_admin.php");
    exit();
}
?>