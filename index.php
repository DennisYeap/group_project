<?php
include 'db.php';

if(isset($_POST['create_match'])) {
  $stmt = $conn->prepare("INSERT INTO Matches (league_ID, home_team_ID, away_team_ID, match_datetime, location) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("iiiss", $_POST['league_ID'], $_POST['home_team_ID'], $_POST['away_team_ID'], $_POST['match_datetime'], $_POST['location']);
  $stmt->execute();
  header("Location: index.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<head>
  <meta charset="UTF-8">
  <title>Match Scheduler</title>
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
        <li class="nav-item"><a class="nav-link active" href="index.php">Matches</a></li>
        <li class="nav-item"><a class="nav-link" href="league.php">Leagues</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <!-- Tabs -->
  <ul class="nav nav-tabs mt-4" id="mainTabs" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabSchedule">Schedule Match</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSearch">Search Matches</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabList">All Matches</button></li>
  </ul>

  <!-- Tab Contents -->
  <div class="tab-content">
    <!-- Schedule Tab -->
    <div class="tab-pane fade show active" id="tabSchedule">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Schedule a New Match</h5>
          <form method="POST">
            <div class="row g-3">
              <div class="col-md-4">
                <label>League</label>
                <select name="league_ID" class="form-select" required>
                  <option value="">Select League...</option>
                  <?php foreach($conn->query("SELECT league_ID, league_name FROM League") as $l): ?>
                    <option value="<?= $l['league_ID'] ?>"><?= htmlspecialchars($l['league_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label>Home Team</label>
                <select name="home_team_ID" class="form-select" required>
                  <option value="">Select Team...</option>
                  <?php foreach($conn->query("SELECT team_ID, team_name FROM Team") as $t): ?>
                    <option value="<?= $t['team_ID'] ?>"><?= htmlspecialchars($t['team_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label>Away Team</label>
                <select name="away_team_ID" class="form-select" required>
                  <option value="">Select Team...</option>
                  <?php foreach($conn->query("SELECT team_ID, team_name FROM Team") as $t): ?>
                    <option value="<?= $t['team_ID'] ?>"><?= htmlspecialchars($t['team_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label>Date & Time</label>
                <input type="datetime-local" name="match_datetime" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label>Location</label>
                <input type="text" name="location" class="form-control" placeholder="e.g. Kota Stadium" required>
              </div>
            </div>
            <button type="submit" name="create_match" class="btn btn-success mt-3">Create Match</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Search Tab -->
    <div class="tab-pane fade" id="tabSearch">
      <div class="card shadow-sm mt-3">
        <div class="card-body">
          <h5 class="card-title">Search Matches</h5>
          <form method="GET">
            <div class="row g-3">
              <div class="col-md-4"><input type="date" name="search_date" class="form-control" placeholder="Date"></div>
              <div class="col-md-4"><input type="text" name="search_team" class="form-control" placeholder="Team name"></div>
              <div class="col-md-4"><input type="text" name="search_location" class="form-control" placeholder="Location"></div>
            </div>
            <button type="submit" class="btn btn-info mt-3">Search</button>
          </form>
        </div>
      </div>
    </div>

    <!-- List Tab -->
    <div class="tab-pane fade" id="tabList">
      <div class="card shadow-sm mt-3">
        <div class="card-body">
          <h5 class="card-title">All Matches</h5>
          <table class="table table-hover">
            <thead><tr><th>#</th><th>League</th><th>Home vs Away</th><th>Date</th><th>Location</th><th>Status</th></tr></thead>
            <tbody>
              <?php
                $query = "SELECT m.*, l.league_name, th.team_name AS home_team, ta.team_name AS away_team
                          FROM Matches m
                          JOIN League l ON m.league_ID = l.league_ID
                          JOIN Team th ON m.home_team_ID = th.team_ID
                          JOIN Team ta ON m.away_team_ID = ta.team_ID
                          WHERE 1";
                if (!empty($_GET['search_date'])) {
                  $d = $_GET['search_date'];
                  $query .= " AND DATE(m.match_datetime)='$d'";
                }
                if (!empty($_GET['search_team'])) {
                  $t = $conn->real_escape_string($_GET['search_team']);
                  $query .= " AND (th.team_name LIKE '%$t%' OR ta.team_name LIKE '%$t%')";
                }
                if (!empty($_GET['search_location'])) {
                  $l = $conn->real_escape_string($_GET['search_location']);
                  $query .= " AND m.location LIKE '%$l%'";
                }
                $res = $conn->query($query);
                $i = 1;
                while($mt = $res->fetch_assoc()):
              ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($mt['league_name']) ?></td>
                <td><?= htmlspecialchars($mt['home_team'].' vs '.$mt['away_team']) ?></td>
                <td><?= htmlspecialchars($mt['match_datetime']) ?></td>
                <td><?= htmlspecialchars($mt['location']) ?></td>
                <td><span class="badge bg-<?= $mt['match_status']=='Finished' ? 'secondary' : ($mt['match_status']=='Live' ? 'warning' : 'info') ?>"><?= $mt['match_status'] ?></span></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
if (isset($_POST['create_match'])) {
  $stmt = $conn->prepare("INSERT INTO Matches (league_ID, home_team_ID, away_team_ID, match_datetime, location) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("iiiss", $_POST['league_ID'], $_POST['home_team_ID'], $_POST['away_team_ID'], $_POST['match_datetime'], $_POST['location']);
  $stmt->execute();
  header("Location: index.php");
}
?>
</body>
</html>
