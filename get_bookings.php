<?php
include 'db.php';

$user = $_GET['user'] ?? '';
$stmt = $conn->prepare("SELECT * FROM bookings WHERE user_login = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode($bookings);
$stmt->close();
$conn->close();
?>