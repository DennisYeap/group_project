<?php include 'db.php'; ?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Main Page</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
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
</main>

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

  fetch(`get-locations.php?date=${date}&upcoming=${upcoming}`)
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
    if (location.includes(query)) {
      marker.setMap(map);
    } else {
      marker.setMap(null);
    }
  });
}

 document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    events: 'events.php',
    eventTimeFormat: {
      hour: 'numeric',
      minute: '2-digit',
      meridiem: 'short' 
    }
  });
  calendar.render();
});

fetch('get-announcements.php')
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
