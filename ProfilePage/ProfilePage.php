<?php


// ProfilePage.php
include(__DIR__ . "/../db.php");
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_email'])) {
  header("Location: ../login.php");
  exit();
}

// Handle bio update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bio'])) {
  $new_bio = trim($_POST['bio']);
  $user_email = $_SESSION['user_email'];
  $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE email = ?");
  $stmt->bind_param("ss", $new_bio, $user_email);
  $stmt->execute();
  $stmt->close();
}

// Handle booking deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
  $booking_id = intval($_POST['booking_id']);
  $user_email = $_SESSION['user_email'];
  // Only allow deletion if the booking belongs to the logged-in user

$stmt = $conn->prepare("DELETE FROM booking WHERE booking_id = ? AND user_email = ?");
  $stmt->bind_param("is", $booking_id, $user_email);
  $stmt->execute();
  $stmt->close();
}

// Fetch user info from database
$user_email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Fetch user's bookings (most recent first), grouped by booking action (all seats booked together)
$bookings = [];
$stmt = $conn->prepare("SELECT 
    MIN(b.booking_id) AS booking_id, 
    m.title AS movie_title, 
    c.name AS cinema_name,
    b.show_date, 
    b.show_time, 
    b.created_at, 
    GROUP_CONCAT(b.seat ORDER BY b.seat) AS seats
FROM booking b
LEFT JOIN movies m ON b.movie_id = m.movie_id
LEFT JOIN cinemas c ON b.cinema_id = c.cinema_id
WHERE b.user_email = ?
GROUP BY m.title, c.name, b.show_date, b.show_time, b.created_at
ORDER BY b.created_at DESC, booking_id DESC");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$grouped_bookings = [];
while ($row = $result->fetch_assoc()) {
    $row['seats'] = explode(',', $row['seats']);
    $grouped_bookings[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Page</title>
  <link rel="stylesheet" href="ProfilePage.css">
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .edit-bio-form { margin-top: 10px; }
    .edit-bio-form textarea { width: 100%; max-width: 400px; }
    .bio-display { margin-top: 10px; }
    .delete-booking-btn {
      background: #e74c3c;
      color: #fff;
      border: none;
      border-radius: 4px;
      padding: 4px 12px;
      margin-left: 10px;
      cursor: pointer;
      font-size: 0.95rem;
      transition: background 0.2s;
    }
    .delete-booking-btn:hover {
      background: #c0392b;
    }
    .non-clickable {
      pointer-events: none;
      cursor: default;
    }
    .transaction-history-list-wrapper {
      display: flex;
      justify-content: center;
    }
  </style>
</head>
<body>
  <div class="profile-container">
    <!-- Exit and Logout Buttons (inside profile container, top) -->
    <div class="profile-actions-bar">
      <a href="../Homepage/Homepage.php" class="exit-btn" id="exit-btn" aria-label="Exit to homepage">
        <i class="fas fa-arrow-left" aria-hidden="true"></i> <span class="exit-text">Exit</span>
      </a>
      <a href="../logout.php" class="logout-btn" id="logout-btn" aria-label="Logout">
        <i class="fas fa-sign-out-alt" aria-hidden="true"></i> <span class="logout-text">Logout</span>
      </a>
    </div>
    <!-- Profile Section -->
    <section class="profile-section">
      <div class="profile-header">
        <div class="profile-avatar">
          <img src="../Pictures/Placeholder2.png" alt="Profile Picture" class="profile-picture-img" />
        </div>
        <div class="profile-info">
          <div class="profile-top">
            <span class="profile-name">
              <?php echo htmlspecialchars($user['email']); ?>
            </span>
            <button class="edit-profile-btn" id="edit-profile-btn" type="button">EDIT PROFILE</button>
          </div>
          <div class="profile-tags">
           
          </div>
          <!-- Bio Section -->
          <div id="bio-section">
            <div id="bio-display" class="bio-display">
              <strong>Bio:</strong>
              <span id="bio-text"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></span>
            </div>
            <form id="edit-bio-form" class="edit-bio-form" method="post" style="display:none;">
              <textarea name="bio" rows="3" required><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea><br>
              <button type="submit" name="save_bio">Save</button>
              <button type="button" id="cancel-edit-bio">Cancel</button>
            </form>
          </div>
        </div>
      </div>
    </section>
<!-- Title Tabs Section -->
    <section class="title-tabs-section">
      <div class="movie-title-row" style="justify-content: center;">
        <div class="movie-title-container" style="justify-content: center;">
          <div class="movie-section-title active selected non-clickable" tabindex="0" data-title="TRANSACTION HISTORY">TRANSACTION&nbsp;HISTORY</div>
        </div>
        <div class="movie-title-container">
          <div class="movie-section-title" style="visibility:hidden" tabindex="-1" data-title=".">.</div>
        </div>
      </div>
    </section>
    <!-- Transaction History Section -->
    <section id="transaction-section" class="transaction-history-section" style="display: block;">
      <div class="transaction-history-list-wrapper">
        <div id="transaction-history-list">
          <?php if (count($grouped_bookings) === 0): ?>
            <div class="transaction-card">
              <div class="transaction-card-header">
                <span class="transaction-movie-title">No bookings yet.</span>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($grouped_bookings as $booking): ?>
              <div class="transaction-card">
                <div class="transaction-card-header">
                  <span class="transaction-movie-title"><?php echo htmlspecialchars($booking['movie_title']); ?></span>
                  <span class="transaction-date"><?php echo htmlspecialchars($booking['show_date']); ?></span>
                </div>
                <div class="transaction-details">
                  <span>Seats: <strong><?php echo htmlspecialchars(implode(', ', $booking['seats'])); ?></strong></span>
                  <span>Time: <strong><?php echo htmlspecialchars(date('g:i A', strtotime($booking['show_time']))); ?></strong></span>
                  <!-- Delete Booking Button (delete all seats in this booking action) -->
                  <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                    <button type="submit" name="delete_booking" class="delete-booking-btn">Delete</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Transaction Modal -->
    <div id="transaction-modal" class="transaction-modal">
      <div class="transaction-modal-content">
        <span class="transaction-modal-close" id="transaction-modal-close">&times;</span>
        <h2 id="modal-movie-title">Movie Title</h2>
        <div id="modal-transaction-list">
          <!-- Transaction details will be dynamically injected here -->
        </div>
      </div>
    </div>
  </div>
  <footer>
    <div class="footer-top">
      <div class="social-icons">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-github"></i></a>
        <a href="#"><i class="fab fa-youtube"></i></a>
      </div>
      <nav class="footer-nav">
        <ul>
          <li><a href="#">Home</a></li>
          <li><a href="#">News</a></li>
          <li><a href="#">About</a></li>
          <li><a href="#">Our Team</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </nav>
      <div class="footer-logos">
        <img src="../Pictures/CICT_Logo.png" alt="Logo 1">
        <img src="../Pictures/WVSULogo.png" alt="Logo 2">
      </div>
    </div>
    <div class="footer-bottom">
      &copy; 2025 Amaguin | A Website for Database Systems
    </div>
  </footer>
  <script>
    // Show edit bio form when edit profile is clicked
    document.addEventListener('DOMContentLoaded', function() {
      const editBtn = document.getElementById('edit-profile-btn');
      const editForm = document.getElementById('edit-bio-form');
      const bioDisplay = document.getElementById('bio-display');
      const cancelBtn = document.getElementById('cancel-edit-bio');
      if(editBtn) {
        editBtn.addEventListener('click', function() {
          editForm.style.display = 'block';
          bioDisplay.style.display = 'none';
        });
      }
      if(cancelBtn) {
        cancelBtn.addEventListener('click', function() {
          editForm.style.display = 'none';
          bioDisplay.style.display = 'block';
        });
      }
    });
  </script>
  <script src="ProfilePage.js"></script>
</body>
</html>