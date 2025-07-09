<?php
include 'db.php';

// Handle league creation
if (isset($_POST['create_league'])) {
  $stmt = $conn->prepare("INSERT INTO League (league_name, season) VALUES (?, ?)");
  $stmt->bind_param("ss", $_POST['league_name'], $_POST['season']);
  $stmt->execute();
  header("Location: league.php");
  exit();
}

// Handle adding team to league
if (isset($_POST['add_team'])) {
  $stmt = $conn->prepare("UPDATE Team SET league_ID = ? WHERE team_ID = ?");
  $stmt->bind_param("ii", $_POST['league_ID'], $_POST['team_ID']);
  $stmt->execute();
  header("Location: league.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>League Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">League Scheduler</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Matches</a></li>
        <li class="nav-item"><a class="nav-link active" href="league.php">Leagues</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">

  <!-- Create League -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">Create New League</div>
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <input type="text" name="league_name" class="form-control" placeholder="League Name" required>
          </div>
          <div class="col-md-4">
            <input type="text" name="season" class="form-control" placeholder="Season (e.g. 2025/26)">
          </div>
          <div class="col-md-2">
            <button type="submit" name="create_league" class="btn btn-success w-100">Create</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Team to League -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-info text-white">Add Team to League</div>
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <select name="team_ID" class="form-select" required>
              <option value="">Select Team</option>
              <?php
              $teams = $conn->query("SELECT team_ID, team_name FROM Team WHERE league_ID IS NULL");
              while ($team = $teams->fetch_assoc()) {
                echo "<option value='{$team['team_ID']}'>" . htmlspecialchars($team['team_name']) . "</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-md-4">
            <select name="league_ID" class="form-select" required>
              <option value="">Select League</option>
              <?php
              $leagues = $conn->query("SELECT league_ID, league_name FROM League");
              while ($league = $leagues->fetch_assoc()) {
                echo "<option value='{$league['league_ID']}'>" . htmlspecialchars($league['league_name']) . "</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" name="add_team" class="btn btn-primary w-100">Add</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- League Standings -->
  <div class="card shadow-sm">
    <div class="card-header bg-dark text-white">League Standings</div>
    <div class="card-body">
      <?php
      $leagues = $conn->query("SELECT * FROM League");
      while ($league = $leagues->fetch_assoc()):
        $leagueID = $league['league_ID'];
      ?>
        <h5 class="mt-4"><?= htmlspecialchars($league['league_name']) ?> (<?= $league['season'] ?>)</h5>
        <table class="table table-bordered table-striped">
          <thead class="table-light">
            <tr>
              <th>Team</th>
              <th>Played</th>
              <th>Wins</th>
              <th>Draws</th>
              <th>Losses</th>
              <th>GF</th>
              <th>GA</th>
              <th>GD</th>
              <th>Points</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $sql = "
                SELECT 
                  t.team_name,
                  COUNT(m.match_ID) AS played,
                  SUM(CASE WHEN r.home_score > r.away_score AND m.home_team_ID = t.team_ID THEN 1
                           WHEN r.away_score > r.home_score AND m.away_team_ID = t.team_ID THEN 1 ELSE 0 END) AS wins,
                  SUM(CASE WHEN r.home_score = r.away_score AND (m.home_team_ID = t.team_ID OR m.away_team_ID = t.team_ID) THEN 1 ELSE 0 END) AS draws,
                  SUM(CASE WHEN r.home_score < r.away_score AND m.home_team_ID = t.team_ID THEN 1
                           WHEN r.away_score < r.home_score AND m.away_team_ID = t.team_ID THEN 1 ELSE 0 END) AS losses,
                  SUM(CASE WHEN m.home_team_ID = t.team_ID THEN r.home_score
                           WHEN m.away_team_ID = t.team_ID THEN r.away_score ELSE 0 END) AS GF,
                  SUM(CASE WHEN m.home_team_ID = t.team_ID THEN r.away_score
                           WHEN m.away_team_ID = t.team_ID THEN r.home_score ELSE 0 END) AS GA,
                  SUM(CASE WHEN m.home_team_ID = t.team_ID THEN r.home_score - r.away_score
                           WHEN m.away_team_ID = t.team_ID THEN r.away_score - r.home_score ELSE 0 END) AS GD,
                  SUM(CASE WHEN r.home_score > r.away_score AND m.home_team_ID = t.team_ID THEN 3
                           WHEN r.away_score > r.home_score AND m.away_team_ID = t.team_ID THEN 3
                           WHEN r.home_score = r.away_score AND (m.home_team_ID = t.team_ID OR m.away_team_ID = t.team_ID) THEN 1 ELSE 0 END) AS Points
                FROM Team t
                LEFT JOIN Matches m ON t.team_ID IN (m.home_team_ID, m.away_team_ID) AND m.league_ID = $leagueID
                LEFT JOIN Result r ON m.match_ID = r.match_ID
                WHERE t.league_ID = $leagueID
                GROUP BY t.team_ID
                ORDER BY Points DESC, GD DESC
              ";
              $standings = $conn->query($sql);
              while($row = $standings->fetch_assoc()):
            ?>
              <tr>
                <td><?= htmlspecialchars($row['team_name']) ?></td>
                <td><?= $row['played'] ?></td>
                <td><?= $row['wins'] ?></td>
                <td><?= $row['draws'] ?></td>
                <td><?= $row['losses'] ?></td>
                <td><?= $row['GF'] ?></td>
                <td><?= $row['GA'] ?></td>
                <td><?= $row['GD'] ?></td>
                <td><?= $row['Points'] ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endwhile; ?>
    </div>
  </div>
</div>
</body>
</html>
