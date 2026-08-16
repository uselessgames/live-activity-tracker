<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Activities</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css" />
</head>
<body>
  <div class="activities-container">
    <div class="activities-header">
      <button class="back-btn" id="backBtn">
        <i class="fa-solid fa-arrow-left"></i> Back
      </button>
      <h1>Activities</h1>
    </div>
    <table class="activities-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Tracker</th>
          <th>Date</th>
          <th>Duration</th>
          <th>Points</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="activitiesBody">
      </tbody>
    </table>
  </div>

  <script src="js/activities.js"></script>
</body>
</html>
