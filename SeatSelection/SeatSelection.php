<?php

include(__DIR__ . "/../db.php");

// Get parameters from URL
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$cinema_id = isset($_GET['cinema_id']) ? intval($_GET['cinema_id']) : 0;
$screen_id = isset($_GET['screen_id']) ? intval($_GET['screen_id']) : 0;
$show_date = isset($_GET['show_date']) ? $_GET['show_date'] : '';
$show_time = isset($_GET['show_time']) ? $_GET['show_time'] : '';

// Fetch booked seats for this showtime
$booked_seats = [];
if ($movie_id && $cinema_id && $screen_id && $show_date && $show_time) {
    $stmt = $conn->prepare("SELECT seat FROM booking WHERE movie_id = ? AND cinema_id = ? AND screen_id = ? AND show_date = ? AND show_time = ?");
    $stmt->bind_param("iiiss", $movie_id, $cinema_id, $screen_id, $show_date, $show_time);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $booked_seats[] = $row['seat'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Seat Selection</title>
  <link rel="stylesheet" href="../Homepage/Homepage.css">
  <link rel="stylesheet" href="SeatSelection.css">
  <script>
    // Pass booked seats from PHP to JS
    const BOOKED_SEATS = <?php echo json_encode($booked_seats); ?>;
  </script>
</head>
<body>
  
<form id="seat-confirm-form" method="post" action="../Confirmation/thankyou.php" style="display:none;">
  <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
  <input type="hidden" name="cinema_id" value="<?php echo $cinema_id; ?>">
  <input type="hidden" name="screen_id" value="<?php echo $screen_id; ?>">
  <input type="hidden" name="show_date" value="<?php echo htmlspecialchars($show_date); ?>">
  <input type="hidden" name="show_time" value="<?php echo htmlspecialchars($show_time); ?>">
  <div id="selected-seats-container"></div>
</form>
  <!-- Progress Bar Header (Seat Selection step active) -->
  <div class="custom-progress-bar dark">
  <a href="../HomePage/Homepage.php">
    <img src="../Pictures/Logo.png" alt="Logo" class="progress-logo">
  </a>
    <div class="progress-steps">
      <span class="progress-step active">Main Menu</span>
      <span class="progress-arrow active">&#8594;</span>
      <span class="progress-step active">Movies</span>
      <span class="progress-arrow active">&#8594;</span>
      <span class="progress-step active">Cinema</span>
      <span class="progress-arrow active">&#8594;</span>
      <span class="progress-step active">get Ticket</span>
      <span class="progress-arrow active">&#8594;</span>
      <span class="progress-step active">Seat Selection</span>
      <span class="progress-arrow">&#8594;</span>
      <span class="progress-step">Confirmation</span>
    </div>

    <div class="header-profile">
      <a href="../ProfilePage/ProfilePage.php" class="header-profile-link-rect" aria-label="Go to Profile Page">
        <img src="../Pictures/Placeholder2.png" alt="User Profile" class="header-pfp">
        <span class="header-profile-text">Profile</span>
      </a>
    </div>
  </div>

    <a href="../MovieMenu/Moviemenu.php" class="back-to-movie-menu-btn" aria-label="Back to Movies">
    <span class="back-arrow" aria-hidden="true">&#8592;</span> <span class="back-label">Back to Movies</span>
  </a>


  <main class="seat-selection-main">
    <?php if ($show_date && $show_time): ?>
      <div class="selected-showtime" style="text-align:center; margin-bottom:18px;">
        <strong>Showtime:</strong>
        <?php echo htmlspecialchars(date('M d, Y', strtotime($show_date))); ?>
        |
        <?php echo htmlspecialchars(date('g:i A', strtotime($show_time))); ?>
      </div>
    <?php endif; ?>
    <div class="seat-booking-options" style="text-align:center; margin-bottom:18px;">
      <label style="font-weight:600; color:#fff; margin-right:10px;">
        <input type="checkbox" id="multi-seat-toggle" style="margin-right:6px;"> Book multiple seats
      </label>
      <input type="number" id="multi-seat-count" min="1" max="10" value="1" style="width:60px; display:none; margin-left:8px; padding:4px 8px; border-radius:4px; border:1px solid #888;">
      <span id="multi-seat-hint" style="color:#fff; font-size:0.98rem; margin-left:10px; display:none;">How many seats? (1-10)</span>
    </div>
    <div class="seat-legend">
      <span class="seat-legend-item unavailable"><span class="seat-box">1</span> UNAVAILABLE</span>
      <span class="seat-legend-item available"><span class="seat-box">1</span> AVAILABLE</span>
    </div>
    <div class="seat-screen">SCREEN</div>
    <div id="seat-map" class="seat-map"></div>
  </main>
  <div class="seat-confirm-container">
    <button id="seat-confirm-btn" class="seat-confirm-btn" disabled>Confirm Selection</button>
  </div>

  <footer>
    © 2025 Amaguin | A Website for Database Systems
  </footer>
  <script src="SeatSelection.js"></script>
</body>
</html>