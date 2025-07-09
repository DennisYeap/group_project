<?php


$host = 'localhost';
$db = 'group_assignment';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$player_id = isset($_GET['player_ID']) ? intval($_GET['player_ID']) : 0;
$player = null;

$stmt = $conn->prepare("SELECT p.*, t.team_name FROM player p
                        LEFT JOIN team t ON p.team_ID = t.team_ID
                        WHERE p.player_ID = ?");
$stmt->bind_param("i", $player_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $player = $result->fetch_assoc();
} else {
    echo "<p style='text-align:center;'>Player not found.</p>";
    exit();
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($player['player_name']) ?> - Player Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: white;
            color: black;
        }

        .profile-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
            position: relative;
        }

        .edit-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            background-color: purple;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .player-image {
            display: block;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px auto;
        }

        h2 {
            text-align: center;
            color: purple;
            margin-bottom: 10px;
        }

        .player-details {
            margin-top: 20px;
            font-size: 16px;
        }

        .player-details div {
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
            color: #555;
        }
    </style>
</head>
<body>

<div class="profile-container">
    <a href="edit_player.php?player_ID=<?= $player_id ?>" class="edit-btn">Edit Player</a>

    <?php
        $jpg = "images/players/player_$player_id.jpg";
        $png = "images/players/player_$player_id.png";
        $imgPath = file_exists($jpg) ? $jpg : (file_exists($png) ? $png : "images/default_player.png");
    ?>
    <img src="<?= $imgPath ?>" alt="Profile Picture" class="player-image">

    <h2><?= htmlspecialchars($player['player_name']) ?></h2>

    <div class="player-details">
        <div><span class="label">Team:</span> <?= htmlspecialchars($player['team_name'] ?? 'N/A') ?></div>
        <div><span class="label">Position:</span> <?= htmlspecialchars($player['position'] ?? 'N/A') ?></div>
        <div><span class="label">Jersey Number:</span> <?= htmlspecialchars($player['jersey_number'] ?? '-') ?></div>
        <div><span class="label">Date of Birth:</span> <?= htmlspecialchars($player['player_date_of_birth'] ?? '-') ?></div>
        <div><span class="label">Email:</span> <?= htmlspecialchars($player['player_email'] ?? '-') ?></div>
        <div><span class="label">Injury Status:</span> <?= htmlspecialchars($player['injury_status'] ?? '-') ?></div>
        <div><span class="label">Nationality:</span> <?= htmlspecialchars($player['nationality'] ?? '-') ?></div>
        <div><span class="label">Height:</span> <?= htmlspecialchars($player['height_cm'] ?? '-') ?> cm</div>
        <div><span class="label">Weight:</span> <?= htmlspecialchars($player['weight_kg'] ?? '-') ?> kg</div>
    </div>
</div>

</body>
</html>
