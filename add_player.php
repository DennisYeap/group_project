<?php


$host = 'localhost';
$db = 'group_assignment';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$team_id = isset($_GET['team_ID']) ? intval($_GET['team_ID']) : 0;
$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $player_name = trim($_POST['player_name']);
    $position = trim($_POST['position']);
    $jersey_number = intval($_POST['jersey_number']);
    $dob = $_POST['player_date_of_birth'];
    $email = trim($_POST['player_email']);
    $injury_status = $_POST['injury_status'];
    $nationality = trim($_POST['nationality']);
    $height = $_POST['height_cm'];
    $weight = $_POST['weight_kg'];

    $file = $_FILES['profile_pic'];

    if (!empty($player_name)) {
        $stmt = $conn->prepare("INSERT INTO player (player_name, position, jersey_number, player_date_of_birth, player_email, injury_status, nationality, height_cm, weight_kg, team_ID)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisssssdi", $player_name, $position, $jersey_number, $dob, $email, $injury_status, $nationality, $height, $weight, $team_id);

        if ($stmt->execute()) {
            $player_id = $stmt->insert_id;

            // Handle profile picture upload
            $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            if ($file['error'] === 0 && array_key_exists($file['type'], $allowed_types)) {
                $ext = $allowed_types[$file['type']];
                $upload_path = "images/players/player_" . $player_id . "." . $ext;
                move_uploaded_file($file['tmp_name'], $upload_path);
            }

            header("Location: team.php?team_ID=$team_id");
            exit();
        } else {
            $message = "Failed to save player.";
        }

        $stmt->close();
    } else {
        $message = "Player name is required.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Player</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: white;
            color: black;
        }

        .form-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }

        h2 {
            text-align: center;
            color: purple;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }

        .submit-btn {
            background-color: purple;
            color: white;
            padding: 10px;
            width: 100%;
            border: none;
            border-radius: 5px;
            margin-top: 20px;
            cursor: pointer;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Add New Player</h2>
    <?php if ($message): ?>
        <p class="error"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="player_name">Player Name:</label>
        <input type="text" name="player_name" required>

        <label for="position">Position:</label>
        <input type="text" name="position">

        <label for="jersey_number">Jersey Number:</label>
        <input type="number" name="jersey_number">

        <label for="player_date_of_birth">Date of Birth:</label>
        <input type="date" name="player_date_of_birth">

        <label for="player_email">Email:</label>
        <input type="email" name="player_email">

        <label for="injury_status">Injury Status:</label>
        <select name="injury_status">
            <option value="Healthy">Healthy</option>
            <option value="Injured">Injured</option>
            <option value="Suspended">Suspended</option>
        </select>

        <label for="nationality">Nationality:</label>
        <input type="text" name="nationality">

        <label for="height_cm">Height (cm):</label>
        <input type="number" step="0.01" name="height_cm">

        <label for="weight_kg">Weight (kg):</label>
        <input type="number" step="0.01" name="weight_kg">

        <label for="profile_pic">Profile Picture (JPG or PNG):</label>
        <input type="file" name="profile_pic" accept=".jpg,.jpeg,.png">

        <button type="submit" class="submit-btn">Add Player</button>
    </form>
</div>

</body>
</html>
