<?php


// Database connection
$host = 'localhost';
$db = 'group_assignment';
$user = 'root';
$pass = ''; // adjust if your DB has a password
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch all teams
$sql = "SELECT team_ID, team_name FROM team ORDER BY team_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teams Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: white;
            color: black;
        }

        h1 {
            text-align: center;
            color: purple;
        }

        .create-btn-container {
            text-align: center;
            margin-top: 20px;
        }

        .create-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: purple;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .team-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 30px;
        }

        .team-card {
            width: 180px;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            background-color: #f9f9f9;
            transition: 0.3s;
        }

        .team-card:hover {
            background-color: #e2d6f3;
            cursor: pointer;
        }

        .team-logo {
            width: 100px;
            height: 100px;
            margin-bottom: 10px;
        }

        a {
            text-decoration: none;
            color: black;
        }
    </style>
</head>
<body>

<h1>Football Clubs</h1>

<div class="create-btn-container">
    <a href="create_team.php" class="create-btn">+ Create New Team</a>
</div>

<div class="team-container">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $teamID = $row['team_ID'];
            $teamName = htmlspecialchars($row['team_name']);

            // Check if .png or .jpg logo exists
            $pngPath = "images/logos/logo_$teamID.png";
            $jpgPath = "images/logos/logo_$teamID.jpg";

            if (file_exists($pngPath)) {
                $logoPath = $pngPath;
            } elseif (file_exists($jpgPath)) {
                $logoPath = $jpgPath;
            } else {
                $logoPath = "images/default_logo.png";
            }

            echo "<a href='team.php?team_ID=$teamID'>
                    <div class='team-card'>
                        <img src='$logoPath' class='team-logo'>
                        <div><strong>$teamName</strong></div>
                    </div>
                  </a>";
        }
    } else {
        echo "<p style='text-align:center;'>No teams found in the database.</p>";
    }

    $conn->close();
    ?>
</div>

</body>
</html>
