<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['full_name'];
            echo json_encode(['success' => true, 'redirect' => 'home.php']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        }
    } elseif ($action === 'signup') {
        $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }

        // Dynamically generate a clean, unique username from email prefix
        $base_username = explode('@', $email)[0];
        $base_username = preg_replace('/[^a-zA-Z0-9_\.]/', '', $base_username);
        $username = $base_username;
        $counter = 1;
        while (true) {
            $stmt_u = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_u->execute([$username]);
            if (!$stmt_u->fetch()) {
                break;
            }
            $username = $base_username . $counter;
            $counter++;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, phone) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $full_name, $email, $hashed_password, $phone])) {
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $full_name;
            echo json_encode(['success' => true, 'redirect' => 'home.php']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Signup failed']);
        }
    } elseif ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'redirect' => 'login.php']);
    }
}
?>