<?php
require 'config.php';

$city = filter_input(INPUT_GET, 'city', FILTER_SANITIZE_STRING) ?? 'Delhi';
$stmt = $pdo->prepare("SELECT * FROM hospitals WHERE city = ?");
$stmt->execute([$city]);
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($hospitals);
?>