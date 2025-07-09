<?php


$host = 'localhost';
$db = 'group_assignment';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $team_name = trim($_POST['team_name']);
    $coach_name = trim($_POST['coach_name']);
    $file = $_FILES['team_logo'];

    if (empty($team_name)) {
        $message = "Team name is required.";
    } else {
        // Check for duplicate
        $check_sql = $conn->prepare("SELECT * FROM team WHERE team_name = ?");
        $check_sql->bind_param("s", $team_name);
        $check_sql->execute();
        $check_result = $check_sql->get_result();

        if ($check_result->num_rows > 0) {
            $message = "A team with this name already exists.";
        } else {
            // Validate image
            $allowed_types = ['image/jpeg', 'image/png'];
            if ($file['error'] === 0 && in_array($file['type'], $allowed_types)) {

                // Insert team
                $stmt = $conn->prepare("INSERT INTO team (team_name, coach_name) VALUES (?, ?)");
                $stmt->bind_param("ss", $team_name, $coach_name);

                if ($stmt->execute()) {
                    $team_id = $stmt->insert_id;

                    // Save file
                    $ext = $file['type'] === 'image/png' ? 'png' : 'jpg';
                    $upload_dir = 'images/logos/';
                    $target_file = $upload_dir . 'logo_' . $team_id . '.' . $ext;

                    if (move_uploaded_file($file['tmp_name'], $target_file)) {
                        header("Location: clubs.php");
                        exit();
                    } else {
                        $message = "Team created, but failed to upload logo.";
                    }
                } else {
                    $message = "Error inserting team into database.";
                }

                $stmt->close();
            } else {
                $message = "Please upload a valid JPG or PNG image.";
            }
        }

        $check_sql->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Team</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: white;
            color: black;
        }

        .form-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f8f8f8;
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

        input[type="text"], input[type="file"] {
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
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Create a New Team</h2>
    <?php if ($message): ?>
        <p class="error"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="team_name">Team Name:</label>
        <input type="text" id="team_name" name="team_name" required>

        <label for="coach_name">Coach Name (optional):</label>
        <input type="text" id="coach_name" name="coach_name">

        <label for="team_logo">Team Logo (JPG or PNG):</label>
        <input type="file" id="team_logo" name="team_logo" accept=".jpg,.jpeg,.png" required>

        <button type="submit" class="submit-btn">Create Team</button>
    </form>
</div>

</body>
</html>
