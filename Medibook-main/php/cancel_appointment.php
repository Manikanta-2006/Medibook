<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $appt_id = filter_input(INPUT_POST, 'appt_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if ($appt_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ? AND user_id = ?");
            $stmt->execute([$appt_id, $user_id]);
            echo json_encode(['success' => true, 'message' => 'Appointment cancelled']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
}
?>