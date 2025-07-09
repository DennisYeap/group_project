<?php
include 'db.php';
header('Content-Type: application/json');


$date = isset($_GET['date']) ? $_GET['date'] : 'all';
$upcoming = isset($_GET['upcoming']) ? (int)$_GET['upcoming'] : 0;

$conditions = ["match_status = 'Scheduled'", "location IS NOT NULL"];

if ($upcoming === 1) {
    $conditions[] = "match_datetime >= NOW()";
}

if ($date !== 'all') {
    $conditions[] = "DATE(match_datetime) = '$date'";
}

$where = implode(" AND ", $conditions);

$sql = "SELECT location, match_datetime FROM Matches WHERE $where";
$result = $conn->query($sql);

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = [
        'location' => $row['location'],
        'datetime' => $row['match_datetime']
    ];
}

echo json_encode($locations);
?>
