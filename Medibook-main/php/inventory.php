<?php
require 'config.php';

$hospital_id = filter_input(INPUT_GET, 'hospital_id', FILTER_SANITIZE_NUMBER_INT);
$query = "SELECT i.*, h.name as hospital_name FROM inventory i JOIN hospitals h ON i.hospital_id = h.id";
$params = [];

if ($hospital_id) {
    $query .= " WHERE i.hospital_id = ?";
    $params[] = $hospital_id;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($inventory);
?>