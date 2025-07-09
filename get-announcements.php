<?php
include 'db.php';

$sql = "SELECT * FROM Announcements ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

$announcements = [];

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
  }
}

header('Content-Type: application/json');
echo json_encode($announcements);
?>
