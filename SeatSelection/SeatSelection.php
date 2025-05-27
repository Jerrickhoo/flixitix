<?php

include(__DIR__ . "/../db.php");
session_start();
$profile_picture = '../Avatar/Placeholder2.png';
if (isset($_SESSION['user_email'])) {
  $user_email = $_SESSION['user_email'];
  $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE email = ?");
  $stmt->bind_param("s", $user_email);
  $stmt->execute();
  $stmt->bind_result($pic);
  if ($stmt->fetch() && $pic) $profile_picture = $pic;
  $stmt->close();
}

// Get parameters from URL
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$cinema_id = isset($_GET['cinema_id']) ? intval($_GET['cinema_id']) : 0;
$screen_id = isset($_GET['screen_id']) ? intval($_GET['screen_id']) : 0;
$show_date = isset($_GET['show_date']) ? $_GET['show_date'] : '';
$show_time = isset($_GET['show_time']) ? $_GET['show_time'] : '';

// Get movie price
$movie_price = 0;
if ($movie_id) {
    $stmt = $conn->prepare("SELECT price FROM movies WHERE movie_id = ?");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $stmt->bind_result($price);
    if ($stmt->fetch()) {
        $movie_price = $price;
    }
    $stmt->close();
}

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
  <link rel="stylesheet" href="SeatSelection.css">  <script>
    // Pass booked seats and price from PHP to JS
    const BOOKED_SEATS = <?php echo json_encode($booked_seats); ?>;
    const TICKET_PRICE = <?php echo json_encode($movie_price); ?>;
  </script>
</head>
<body>
  
<form id="seat-confirm-form" method="post" action="../Confirmation/thankyou.php" style="display:none;">
  <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
  <input type="hidden" name="cinema_id" value="<?php echo $cinema_id; ?>">
  <input type="hidden" name="screen_id" value="<?php echo $screen_id; ?>">
  <input type="hidden" name="show_date" value="<?php echo htmlspecialchars($show_date); ?>">
  <input type="hidden" name="show_time" value="<?php echo htmlspecialchars($show_time); ?>">
  <input type="hidden" name="confirm_booking" value="1">
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
      <span class="progress-step active">Get Ticket</span>
      <span class="progress-arrow active">&#8594;</span>      <span class="progress-step active" id="progress-seat-selection">Seat Selection</span>
      <span class="progress-arrow">&#8594;</span>
      <span class="progress-step">Summary</span>
    </div>

    <div class="header-profile">
      <a href="../ProfilePage/ProfilePage.php" class="header-profile-link-rect" aria-label="Go to Profile Page">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="User Profile" class="header-pfp">
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
    <?php endif; ?>    <div class="seat-booking-options" style="text-align:center; margin-bottom:18px;">
      <label style="font-weight:600; color:#fff; display: flex; align-items: center; justify-content: center; gap: 12px;">
        Number of Seats:
        <input type="number" id="multi-seat-count" min="1" max="10" value="1" style="width:60px; padding:4px 8px; border-radius:4px; border:1px solid #333; background: #333; color: #fff; font-size: 1rem;">
        <span style="color:#bbb; font-size:0.9rem; font-weight: 400;">(Max: 10)</span>
      </label>
    </div>
    <div class="seat-legend">
      <span class="seat-legend-item unavailable"><span class="seat-box">1</span> UNAVAILABLE</span>
      <span class="seat-legend-item available"><span class="seat-box">1</span> AVAILABLE</span>
    </div>    <div class="seat-screen">SCREEN</div>
    <div id="seat-map" class="seat-map"></div>
    <div style="text-align: center; margin-top: 30px; color: #fff; font-size: 1rem;">
      Total Price: ₱<span id="total-price">0.00</span>
    </div>
  </main>
  <div class="seat-confirm-container">
    <button id="seat-confirm-btn" class="seat-confirm-btn" disabled>Confirm Selection</button>
  </div>  <!-- Confirmation Modal -->
  <div id="payment-modal" class="payment-modal-overlay">
    <div class="payment-modal">
      <h2>Confirm Booking</h2>
      <div class="payment-amount">
        Total Amount: ₱<span id="modal-total-price">0.00</span>
      </div>
      <div class="confirmation-message">
        <p>Are you sure you want to proceed with this booking?</p>
      </div>
      <div class="modal-buttons">
        <button id="gcash-button" class="confirm-booking-btn">
          Confirm Booking
        </button>
        <button id="cancel-payment" class="cancel-payment-btn">Cancel</button>
      </div>
    </div>
  </div>

  <footer>
    © 2025 Amaguin | A Website for Database Systems
  </footer>
  <script src="SeatSelection.js"></script>
</body>
</html>