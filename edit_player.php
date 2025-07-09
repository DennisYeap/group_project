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
$message = '';

if (isset($_POST['delete'])) {
    // Delete player and associated image
    $conn->query("DELETE FROM player WHERE player_ID = $player_id");
    @unlink("images/players/player_$player_id.jpg");
    @unlink("images/players/player_$player_id.png");
    header("Location: clubs.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update'])) {
    $player_name = trim($_POST['player_name']);
    $position = trim($_POST['position']);
    $jersey_number = intval($_POST['jersey_number']);
    $dob = $_POST['player_date_of_birth'];
    $email = trim($_POST['player_email']);
    $injury_status = $_POST['injury_status'];
    $nationality = trim($_POST['nationality']);
    $height = $_POST['height_cm'];
    $weight = $_POST['weight_kg'];

    $stmt = $conn->prepare("UPDATE player SET player_name=?, position=?, jersey_number=?, player_date_of_birth=?, player_email=?, injury_status=?, nationality=?, height_cm=?, weight_kg=? WHERE player_ID=?");
    $stmt->bind_param("ssisssssdi", $player_name, $position, $jersey_number, $dob, $email, $injury_status, $nationality, $height, $weight, $player_id);

    if ($stmt->execute()) {
        // Handle optional image upload
        $file = $_FILES['profile_pic'];
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

        if ($file['error'] === 0 && array_key_exists($file['type'], $allowed_types)) {
            $ext = $allowed_types[$file['type']];
            $upload_path = "images/players/player_$player_id.$ext";

            // Delete old images before saving new one
            @unlink("images/players/player_$player_id.jpg");
            @unlink("images/players/player_$player_id.png");

            move_uploaded_file($file['tmp_name'], $upload_path);
        }

        $message = "Player updated successfully.";
    } else {
        $message = "Failed to update player.";
    }

    $stmt->close();
}

// Fetch player data for form
$stmt = $conn->prepare("SELECT * FROM player WHERE player_ID = ?");
$stmt->bind_param("i", $player_id);
$stmt->execute();
$result = $stmt->get_result();
$player = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Player</title>
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

        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }

        .submit-btn, .delete-btn {
            padding: 10px;
            border: none;
            width: 48%;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .submit-btn {
            background-color: purple;
            color: white;
        }

        .delete-btn {
            background-color: crimson;
            color: white;
        }

        .message {
            color: green;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit Player</h2>
    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="player_name">Player Name:</label>
        <input type="text" name="player_name" value="<?= htmlspecialchars($player['player_name']) ?>" required>

        <label for="position">Position:</label>
        <input type="text" name="position" value="<?= htmlspecialchars($player['position']) ?>">

        <label for="jersey_number">Jersey Number:</label>
        <input type="number" name="jersey_number" value="<?= $player['jersey_number'] ?>">

        <label for="player_date_of_birth">Date of Birth:</label>
        <input type="date" name="player_date_of_birth" value="<?= $player['player_date_of_birth'] ?>">

        <label for="player_email">Email:</label>
        <input type="email" name="player_email" value="<?= htmlspecialchars($player['player_email']) ?>">

        <label for="injury_status">Injury Status:</label>
        <select name="injury_status">
            <option value="Healthy" <?= $player['injury_status'] == 'Healthy' ? 'selected' : '' ?>>Healthy</option>
            <option value="Injured" <?= $player['injury_status'] == 'Injured' ? 'selected' : '' ?>>Injured</option>
            <option value="Suspended" <?= $player['injury_status'] == 'Suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>

        <label for="nationality">Nationality:</label>
        <input type="text" name="nationality" value="<?= htmlspecialchars($player['nationality']) ?>">

        <label for="height_cm">Height (cm):</label>
        <input type="number" step="0.01" name="height_cm" value="<?= $player['height_cm'] ?>">

        <label for="weight_kg">Weight (kg):</label>
        <input type="number" step="0.01" name="weight_kg" value="<?= $player['weight_kg'] ?>">

        <label for="profile_pic">Change Profile Picture (JPG or PNG):</label>
        <input type="file" name="profile_pic" accept=".jpg,.jpeg,.png">

        <div class="btn-group">
            <button type="submit" name="update" class="submit-btn">Update Player</button>
            <button type="submit" name="delete" class="delete-btn" onclick="return confirm('Are you sure you want to delete this player?');">Delete Player</button>
        </div>
    </form>
</div>

</body>
</html>
