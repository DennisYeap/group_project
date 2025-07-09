<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>League Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-primary navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Match Scheduler</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Matches</a></li>
        <li class="nav-item"><a class="nav-link active" href="league.php">Leagues</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title">Create a League</h5>
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <label>League Name</label>
            <input type="text" name="league_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label>Season</label>
            <input type="text" name="season" class="form-control" placeholder="e.g. 2024/25" required>
          </div>
        </div>
        <button type="submit" name="create_league" class="btn btn-primary mt-3">Create League</button>
      </form>
    </div>
  </div>

  <div class="card mt-4 shadow-sm">
    <div class="card-body">
      <h5 class="card-title">Add Teams to a League</h5>
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <label>Select League</label>
            <select name="league_ID" class="form-select" required>
              <option value="">Select League...</option>
              <?php foreach($conn->query("SELECT league_ID, league_name FROM League") as $l): ?>
                <option value="<?= $l['league_ID'] ?>"><?= htmlspecialchars($l['league_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label>Select Team</label>
            <select name="team_ID" class="form-select" required>
              <option value="">Select Team...</option>
              <?php foreach($conn->query("SELECT team_ID, team_name FROM Team") as $t): ?>
                <option value="<?= $t['team_ID'] ?>"><?= htmlspecialchars($t['team_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button type="submit" name="add_team" class="btn btn-success mt-3">Add Team to League</button>
      </form>
    </div>
  </div>

  <div class="card mt-4 shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title">League Standings</h5>
      <?php
      $leagues = $conn->query("SELECT * FROM League");
      while($league = $leagues->fetch_assoc()):
        echo "<h6 class='mt-4'>" . htmlspecialchars($league['league_name']) . " (" . htmlspecialchars($league['season']) . ")</h6>";
        $sql = "
          SELECT T.team_name,
              COALESCE(SUM(CASE 
                  WHEN M.home_team_ID = T.team_ID AND R.home_score > R.away_score THEN 3
                  WHEN M.away_team_ID = T.team_ID AND R.away_score > R.home_score THEN 3
                  WHEN R.home_score = R.away_score THEN 1
                  ELSE 0 END), 0) AS points,
              COALESCE(SUM(CASE WHEN T.team_ID = M.home_team_ID THEN R.home_score ELSE R.away_score END), 0) -
              COALESCE(SUM(CASE WHEN T.team_ID = M.home_team_ID THEN R.away_score ELSE R.home_score END), 0) AS goal_diff
          FROM Team T
          JOIN LeagueTeam LT ON T.team_ID = LT.team_ID
          LEFT JOIN Matches M ON T.team_ID IN (M.home_team_ID, M.away_team_ID) AND LT.league_ID = M.league_ID
          LEFT JOIN Result R ON M.match_ID = R.match_ID
          WHERE LT.league_ID = " . $league['league_ID'] . "
          GROUP BY T.team_ID
          ORDER BY points DESC, goal_diff DESC
        ";
        $standings = $conn->query($sql);
        echo "<table class='table table-striped mt-2'><thead><tr><th>#</th><th>Team</th><th>Points</th><th>Goal Difference</th></tr></thead><tbody>";
        $rank = 1;
        while ($row = $standings->fetch_assoc()):
          echo "<tr><td>$rank</td><td>".htmlspecialchars($row['team_name'])."</td><td>{$row['points']}</td><td>{$row['goal_diff']}</td></tr>";
          $rank++;
        endwhile;
        echo "</tbody></table>";
      endwhile;
      ?>
    </div>
  </div>
</div>

<?php
if (isset($_POST['create_league'])) {
  $stmt = $conn->prepare("INSERT INTO League (league_name, season) VALUES (?, ?)");
  $stmt->bind_param("ss", $_POST['league_name'], $_POST['season']);
  $stmt->execute();
  header("Location: league.php");
}

if (isset($_POST['add_team'])) {
  $stmt = $conn->prepare("INSERT IGNORE INTO LeagueTeam (league_ID, team_ID) VALUES (?, ?)");
  $stmt->bind_param("ii", $_POST['league_ID'], $_POST['team_ID']);
  $stmt->execute();
  header("Location: league.php");
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
