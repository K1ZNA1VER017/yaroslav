<?php
include 'db.php';

$user_login = $_POST['user'] ?? '';
$hall_id = $_POST['hallId'] ?? '';
$hall_name = $_POST['hallName'] ?? '';
$rating = (int)($_POST['rating'] ?? 0);
$text = $_POST['text'] ?? '';

$stmt = $conn->prepare("INSERT INTO reviews (user_login, hall_id, hall_name, rating, text) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssis", $user_login, $hall_id, $hall_name, $rating, $text);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>