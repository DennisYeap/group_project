<?php
include 'db.php';
header('Content-Type: application/json');

$sql = "SELECT match_ID AS id, match_datetime AS start, location AS title 
        FROM Matches 
        WHERE match_status = 'Scheduled'";

$result = $conn->query($sql);
$events = [];

while ($row = $result->fetch_assoc()) {
  $events[] = [
    "id" => $row["id"],
    "title" => $row["title"],
    "start" => $row["start"]
  ];
}

echo json_encode($events);
?>
