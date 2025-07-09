<?php
$host = 'localhost';
$db = 'group_assignment';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$team_id = isset($_GET['team_ID']) ? intval($_GET['team_ID']) : 0;
$team_name = '';
$players = [];
$all_teams = [];
$unassigned_players = [];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_player'])) {
        $pid = intval($_POST['remove_player']);
        $conn->query("UPDATE player SET team_ID = NULL WHERE player_ID = $pid");
    }
    if (isset($_POST['move_player']) && isset($_POST['target_team'])) {
        $pid = intval($_POST['move_player']);
        $target = intval($_POST['target_team']);
        $conn->query("UPDATE player SET team_ID = $target WHERE player_ID = $pid");
    }
    if (isset($_POST['assign_existing']) && isset($_POST['existing_player_id'])) {
        $pid = intval($_POST['existing_player_id']);
        $conn->query("UPDATE player SET team_ID = $team_id WHERE player_ID = $pid");
    }
}

// Fetch Team
$stmt = $conn->prepare("SELECT team_name FROM team WHERE team_ID = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) $team_name = $row['team_name'];
$stmt->close();

// Fetch Players
$stmt = $conn->prepare("SELECT player_ID, player_name, position, jersey_number FROM player WHERE team_ID = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch Other Teams
$res = $conn->query("SELECT team_ID, team_name FROM team WHERE team_ID != $team_id");
while ($row = $res->fetch_assoc()) $all_teams[] = $row;

// Fetch Unassigned Players
$res = $conn->query("SELECT player_ID, player_name FROM player WHERE team_ID IS NULL");
while ($row = $res->fetch_assoc()) $unassigned_players[] = $row;

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($team_name) ?> - Team Details</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: white; color: black; }
        h1 { text-align: center; color: purple; }
        .add-player-btn {
            display: block; width: 200px; margin: 20px auto;
            text-align: center; padding: 10px; background-color: purple;
            color: white; text-decoration: none; border-radius: 5px; font-weight: bold;
        }
        .player-list { max-width: 800px; margin: 30px auto; }
        .player-card {
            padding: 15px; margin-bottom: 15px; border: 1px solid #ccc;
            border-radius: 8px; background-color: #f9f9f9; transition: 0.3s;
        }
        .player-card:hover { background-color: #f0e6ff; }
        .player-name { font-size: 18px; font-weight: bold; }
        .player-info { margin-top: 5px; color: #555; }
        .player-row {
            display: flex; align-items: center; justify-content: space-between;
        }
        .player-left { display: flex; align-items: center; }
        .player-image {
            width: 50px; height: 50px; border-radius: 50%;
            object-fit: cover; margin-right: 10px;
        }
        .view-text {
            font-style: italic;
            color: #666;
        }
        .actions {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        form.inline { display: inline; }
        select, button {
            padding: 5px;
        }
        .assign-section {
            max-width: 600px; margin: 40px auto;
            padding: 20px; background: #eee; border-radius: 10px;
        }
    </style>
</head>
<body>

<h1><?= htmlspecialchars($team_name) ?></h1>
<a href="add_player.php?team_ID=<?= $team_id ?>" class="add-player-btn">+ Add Player</a>

<div class="player-list">
    <?php if (count($players) > 0): ?>
        <?php foreach ($players as $player): ?>
            <div class="player-card">
                <div class="player-row">
                    <div class="player-left">
                        <?php
                        $jpgPath = "images/players/player_{$player['player_ID']}.jpg";
                        $pngPath = "images/players/player_{$player['player_ID']}.png";
                        $profileImg = file_exists($jpgPath) ? $jpgPath : (file_exists($pngPath) ? $pngPath : "images/default_player.png");
                        ?>
                        <img src="<?= $profileImg ?>" class="player-image">
                        <a href="player.php?player_ID=<?= $player['player_ID'] ?>" class="player-name">
                            <?= htmlspecialchars($player['player_name']) ?>
                        </a>
                    </div>
                    <div class="view-text">view</div>
                </div>
                <div class="player-info" style="margin-left: 60px;">
                    Position: <?= htmlspecialchars($player['position'] ?? 'N/A') ?> |
                    Jersey #: <?= htmlspecialchars($player['jersey_number'] ?? '-') ?>
                </div>
                <div class="actions" style="margin-left: 60px;">
                    <form method="POST" class="inline">
                        <input type="hidden" name="remove_player" value="<?= $player['player_ID'] ?>">
                        <button type="submit">Remove</button>
                    </form>
                    <form method="POST" class="inline">
                        <input type="hidden" name="move_player" value="<?= $player['player_ID'] ?>">
                        <select name="target_team" required>
                            <option value="">Move to...</option>
                            <?php foreach ($all_teams as $t): ?>
                                <option value="<?= $t['team_ID'] ?>"><?= $t['team_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Move</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center;">No players found for this team.</p>
    <?php endif; ?>
</div>

<?php if (count($unassigned_players) > 0): ?>
    <div class="assign-section">
        <h3>Add Existing Unassigned Player</h3>
        <form method="POST">
            <select name="existing_player_id" required>
                <option value="">-- Select Player --</option>
                <?php foreach ($unassigned_players as $p): ?>
                    <option value="<?= $p['player_ID'] ?>"><?= $p['player_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="assign_existing">Assign to <?= htmlspecialchars($team_name) ?></button>
        </form>
    </div>
<?php endif; ?>

</body>
</html>
