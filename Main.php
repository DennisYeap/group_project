<?php include 'db.php'; ?>
<?php
if (isset($_GET['action'])) {
  header('Content-Type: application/json');
  if ($_GET['action'] === 'events') {
    $result = $conn->query("SELECT match_ID as id, location as title, match_datetime as start FROM Matches WHERE match_status = 'Scheduled'");
    $events = [];
    while ($row = $result->fetch_assoc()) {
      $events[] = $row;
    }
    echo json_encode($events);
    exit;
  }

  if ($_GET['action'] === 'announcements') {
    $result = $conn->query("SELECT * FROM Announcements ORDER BY created_at DESC");
    $announcements = [];
    while ($row = $result->fetch_assoc()) {
      $announcements[] = $row;
    }
    echo json_encode($announcements);
    exit;
  }

  if ($_GET['action'] === 'locations') {
    $dateFilter = $_GET['date'] ?? 'all';
    $upcoming = isset($_GET['upcoming']) && $_GET['upcoming'] == 1;
    $sql = "SELECT location, match_datetime FROM Matches WHERE match_status = 'Scheduled' AND location IS NOT NULL";
    if ($dateFilter !== 'all') {
      $sql .= " AND DATE(match_datetime) = '" . $conn->real_escape_string($dateFilter) . "'";
    }
    if ($upcoming) {
      $sql .= " AND match_datetime >= NOW()";
    }
    $result = $conn->query($sql);
    $locations = [];
    while ($row = $result->fetch_assoc()) {
      $locations[] = $row;
    }
    echo json_encode($locations);
    exit;
  }
  echo json_encode(['error' => 'Invalid action']);
  exit;
}
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Main Page</title>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      background: #f7f7f7;
      color: #333;
      font-size: 14px;
    }

    header {
      background-color: #1e3d59;
      color: white;
      padding: 12px;
      text-align: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.08);
      font-size: 18px;
    }

    main {
      padding: 15px;
    }

    section {
      margin-bottom: 25px;
      background: white;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    h2 {
      font-size: 18px;
      color: #1e3d59;
      border-bottom: 1px solid #eee;
      padding-bottom: 4px;
      margin-bottom: 10px;
    }

    #map {
      height: 250px;
      width: 100%;
      border-radius: 6px;
      margin-top: 8px;
    }

    .map-controls {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 8px;
      align-items: center;
    }

    .map-controls label,
    .map-controls input,
    .map-controls select {
      font-size: 13px;
    }

    .map-controls input,
    .map-controls select {
      padding: 4px 6px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }

    #calendar {
      max-width: 100%;
      margin: 0 auto;
      background: #fff;
      border-radius: 6px;
      padding: 10px;
      font-size: 14px;
      transform: none;
    }

    .fc {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .fc-event-time {
      white-space: nowrap !important;
    }

    .fc .fc-toolbar-title {
      font-size: 1.3em;
      font-weight: 600;
      color: #1e3d59;
    }

    .fc .fc-daygrid-event {
      font-size: 13px;
      padding: 4px 6px;
      border-radius: 5px;
      background-color: #e7f1ff;
      color: #1e3d59;
      border: 1px solid #cce0f5;
      margin-bottom: 3px;
      white-space: normal !important;
      word-break: break-word;
      cursor: pointer;
    }

    .fc .fc-daygrid-day-number {
      font-weight: 500;
      font-size: 13px;
      padding-right: 4px;
    }

    .fc .fc-scrollgrid-section-header {
      background-color: #f4f4f4;
    }

    #announcements .announcement {
      background-color: #f0f8ff;
      border-left: 4px solid #1e90ff;
      padding: 8px 12px;
      margin-bottom: 8px;
      border-radius: 4px;
    }

    .announcement h3 {
      margin: 0 0 4px 0;
      font-size: 15px;
      color: #1e3d59;
    }

    .announcement p {
      margin: 0 0 4px 0;
      font-size: 13px;
    }

    .announcement small {
      font-size: 11px;
      color: #666;
    }

    .navbar {
      background-color: #1e3d59;
      padding: 10px 0;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }

    .navbar ul {
      list-style: none;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 16px;
      margin: 0;
      padding: 0;
    }

    .navbar li a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      padding: 6px 12px;
      transition: background 0.2s ease;
    }

    .navbar li a:hover {
      background-color: #163045;
      border-radius: 4px;
    }

    .site-footer {
      background-color: #1e3d59;
      color: white;
      text-align: center;
      padding: 10px 0;
      font-size: 13px;
      margin-top: 30px;
    }
  </style>
</head>
<body>

<header>
  <h1>Main Page</h1>
</header>

<main>
  <section>
    <h2>Upcoming Events</h2>
    <div id="calendar"></div>
  </section>

  <section>
    <h2>Latest Announcements</h2>
    <div id="announcements"></div>
  </section>

  <section>
    <h2>Nearby Match Locations</h2>
    <div class="map-controls">
      <label for="dateFilter">Filter by Date:</label>
      <select id="dateFilter">
        <option value="all">All Dates</option>
        <?php
        $dates = $conn->query("SELECT DISTINCT DATE(match_datetime) AS match_date FROM Matches WHERE match_status = 'Scheduled'");
        while ($row = $dates->fetch_assoc()) {
          $dateVal = $row['match_date'];
          echo "<option value='$dateVal'>$dateVal</option>";
        }
        ?>
      </select>

      <label for="upcomingToggle">
        <input type="checkbox" id="upcomingToggle" checked />
        Show only upcoming
      </label>

      <input type="text" id="searchInput" placeholder="Search stadium/court..." />
    </div>
    <div id="map"></div>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAbxyQcCKsnyn1T_pKvmdMyjVlpk0oa0LE&libraries=places&callback=initMap" async defer></script>
<script>
let map;
let geocoder;
let allLocations = [];
let allMarkers = [];

function initMap() {
  map = new google.maps.Map(document.getElementById("map"), {
    center: { lat: 3.139, lng: 101.6869 },
    zoom: 12
  });

  geocoder = new google.maps.Geocoder();
  loadMapData();

  document.getElementById("dateFilter").addEventListener("change", loadMapData);
  document.getElementById("upcomingToggle").addEventListener("change", loadMapData);
  document.getElementById("searchInput").addEventListener("input", filterMarkers);
}

function loadMapData() {
  const date = document.getElementById("dateFilter").value;
  const upcoming = document.getElementById("upcomingToggle").checked ? 1 : 0;

  fetch(`main.php?action=locations&date=${date}&upcoming=${upcoming}`)
    .then(res => res.json())
    .then(data => {
      allLocations = data;
      renderMarkers(data);
    });
}

function renderMarkers(locations) {
  allMarkers.forEach(markerObj => markerObj.marker.setMap(null));
  allMarkers = [];

  locations.forEach(item => {
    geocoder.geocode({ address: item.location }, (results, status) => {
      if (status === "OK") {
        const marker = new google.maps.Marker({
          map: map,
          position: results[0].geometry.location,
          title: item.location
        });

        const infowindow = new google.maps.InfoWindow({
          content: `<strong>${item.location}</strong><br>Match: ${item.datetime}`
        });

        marker.addListener("click", () => infowindow.open(map, marker));
        allMarkers.push({ marker, location: item.location.toLowerCase() });
      }
    });
  });
}

function filterMarkers() {
  const query = document.getElementById("searchInput").value.toLowerCase();
  allMarkers.forEach(({ marker, location }) => {
    marker.setMap(location.includes(query) ? map : null);
  });
}

document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    events: 'main.php?action=events',
    eventTimeFormat: {
      hour: 'numeric',
      minute: '2-digit',
      meridiem: 'short'
    }
  });
  calendar.render();
});

fetch('main.php?action=announcements')
  .then(response => response.json())
  .then(data => {
    const container = document.getElementById('announcements');
    if (data.length === 0) {
      container.innerHTML = "<p>No announcements yet.</p>";
    } else {
      const html = data.map(a => `
        <div class="announcement">
          <h3>${a.title}</h3>
          <p>${a.message}</p>
          <small>📅 ${new Date(a.created_at).toLocaleDateString()}</small>
        </div>
      `).join("");
      container.innerHTML = html;
    }
  })
  .catch(err => {
    document.getElementById('announcements').innerHTML = "<p>⚠️ Failed to load announcements.</p>";
  });
</script>

</body>
</html>
<?php include 'footer.php'; ?>
