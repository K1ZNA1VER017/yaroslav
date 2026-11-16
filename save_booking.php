<?php
include 'db.php';

$user_login = $_POST['user'] ?? '';
$hall_id = $_POST['hallId'] ?? '';
$hall_name = $_POST['hallName'] ?? '';
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$date = $_POST['date'] ?? '';
$guests = $_POST['guests'] ?? 0;
$comment = $_POST['comment'] ?? '';

$stmt = $conn->prepare("INSERT INTO bookings (user_login, hall_id, hall_name, name, phone, date, guests, comment, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new')");
$stmt->bind_param("ssssssis", $user_login, $hall_id, $hall_name, $name, $phone, $date, $guests, $comment);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>