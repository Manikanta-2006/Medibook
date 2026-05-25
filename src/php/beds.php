<?php
require 'config.php';

$hospital_id = filter_input(INPUT_GET, 'hospital_id', FILTER_SANITIZE_NUMBER_INT);
$query = "SELECT b.*, h.name as hospital_name FROM beds b JOIN hospitals h ON b.hospital_id = h.id";
$params = [];

if ($hospital_id) {
    $query .= " WHERE b.hospital_id = ?";
    $params[] = $hospital_id;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$beds = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($beds);
?>