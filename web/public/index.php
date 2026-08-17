<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Live Tracking</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css" />
</head>
<body>
  <div id="map"></div>

    <div id="infoPanel" class="info-panel">
    <div class="info-header">
      <strong id="infoTitle"></strong>
      <button class="info-close" id="infoClose">&times;</button>
    </div>
    <div class="info-row">Last seen: <span id="infoLastSeen"></span></div>
    <div class="info-row">Speed: <span id="infoSpeed"></span> (estimated)</div>
    <div class="info-row">Track: <button class="track-toggle" id="trackToggleBtn"></button></div>
    <div class="info-actions">
      <div class="buttons-container" id="buttonsContainer">
        <button class="save-btn" id="saveBtn">Save Activity</button>
        <button class="clear-btn" id="clearTrackBtn">Clear Track</button>
      </div>
      <div class="name-input-container" id="nameInputContainer">
        <input type="text" id="activityNameInput" maxlength="50" placeholder="Enter activity name...">
        <button class="save-name-btn" id="saveNameBtn"><i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>
  </div>

  <template id="menuControl">
    <div class="menu-toggle"><i class="fa-solid fa-bars"></i></div>
    <div class="menu-content">
      <div class="layer-toggle">
        <label>
          <input type="checkbox" id="explorerTilesCheckbox">
          <span>Explorer Tiles</span>
        </label>
      </div>
      <div class="menu-row">
        <button class="menu-btn" id="activitiesBtn">
          <i class="fa-solid fa-map"></i> Activities
        </button>
      </div>
    </div>
</template>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="js/main.js"></script>
</body>
</html>
