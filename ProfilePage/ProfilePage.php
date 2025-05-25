<?php


// ProfilePage.php
include(__DIR__ . "/../db.php");
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_email'])) {
  header("Location: ../login.php");
  exit();
}

// Handle bio, name, and profile picture update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bio'])) {
  $new_bio = trim($_POST['bio']);
  $new_name = trim($_POST['name']);
  $new_avatar = isset($_POST['profile_picture']) ? $_POST['profile_picture'] : '';
  $user_email = $_SESSION['user_email'];
  $stmt = $conn->prepare("UPDATE users SET bio = ?, name = ?, profile_picture = ? WHERE email = ?");
  $stmt->bind_param("ssss", $new_bio, $new_name, $new_avatar, $user_email);
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
// Set profile picture (default if not set)
$profile_picture = isset($user['profile_picture']) && $user['profile_picture'] ? $user['profile_picture'] : '../Pictures/Placeholder2.png';

// Fetch user's bookings (most recent first), grouped by booking action (all seats booked together)
$bookings = [];
$stmt = $conn->prepare("SELECT 
    MIN(b.booking_id) AS booking_id, 
    m.title AS movie_title, 
    c.name AS cinema_name,
    b.show_date, 
    b.show_time, 
    b.created_at, 
    m.price AS movie_price, /* fetch price */
    b.movie_id, /* fetch movie_id for price lookup */
    GROUP_CONCAT(b.seat ORDER BY b.seat) AS seats
FROM booking b
LEFT JOIN movies m ON b.movie_id = m.movie_id
LEFT JOIN cinemas c ON b.cinema_id = c.cinema_id
WHERE b.user_email = ?
GROUP BY m.title, c.name, b.show_date, b.show_time, b.created_at, m.price, b.movie_id
ORDER BY b.created_at DESC, booking_id DESC");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$grouped_bookings = [];
while ($row = $result->fetch_assoc()) {
    $row['seats'] = explode(',', $row['seats']);
    $row['seat_count'] = count($row['seats']);
    $row['total_price'] = $row['movie_price'] * $row['seat_count'];
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
          <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-picture-img" />
        </div>
        <div class="profile-info">
          <div class="profile-top">
            <span class="profile-name">
              <?php echo htmlspecialchars($user['name'] ? $user['name'] : $user['email']); ?>
            </span>
            <button class="edit-profile-btn" id="edit-profile-btn" type="button">EDIT PROFILE</button>
          </div>
          <div class="profile-tags">
            
          </div>
          <!-- Bio Section -->
          <div id="bio-section">
            <div id="bio-display" class="bio-display">
              <strong>Bio:</strong> <span id="bio-text"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></span>
            </div>
            <form id="edit-bio-form" class="edit-bio-form" method="post" style="display:none;">
              <label for="edit-name"><strong>Name:</strong></label><br>
              <input type="text" id="edit-name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($user['email']); ?>" required style="width:100%;max-width:400px;"><br>
              <label for="edit-bio"><strong>Bio:</strong></label><br>
              <textarea id="edit-bio" name="bio" rows="3" required><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea><br>
              <label><strong>Profile Picture:</strong></label><br>
              <div class="avatar-selection" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                <?php
                  $avatar_dir = __DIR__ . '/../Avatar/';
                  $avatar_files = array_values(array_filter(scandir($avatar_dir), function($f) use ($avatar_dir) {
                    return preg_match('/\.(png|jpg|jpeg|gif|webp)$/i', $f) && is_file($avatar_dir . $f);
                  }));
                  foreach ($avatar_files as $avatar) {
                    $avatar_path = '../Avatar/' . $avatar;
                    $checked = ($profile_picture === $avatar_path) ? 'checked' : '';
                    echo '<label style="display:inline-block;text-align:center;cursor:pointer;">';
                    echo '<input type="radio" name="profile_picture" value="' . htmlspecialchars($avatar_path) . '" style="display:block;margin:auto;" ' . $checked . '>';
                    echo '<img src="' . htmlspecialchars($avatar_path) . '" alt="avatar" style="width:48px;height:48px;border-radius:50%;border:2px solid #ccc;margin:4px 0;">';
                    echo '</label>';
                  }
                ?>
              </div>
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
                  <span>Booking IDs: <strong>
                    <?php
                      // Fetch all booking_ids for this booking group (same movie, cinema, showtime, created_at)
                      $ids_stmt = $conn->prepare("SELECT booking_id FROM booking WHERE user_email = ? AND movie_id = ? AND cinema_id = (SELECT cinema_id FROM cinemas WHERE name = ?) AND show_date = ? AND show_time = ? AND created_at = (SELECT created_at FROM booking WHERE booking_id = ? LIMIT 1) ORDER BY seat");
                      $ids_stmt->bind_param("sisssi", $user_email, $booking['movie_id'], $booking['cinema_name'], $booking['show_date'], $booking['show_time'], $booking['booking_id']);
                      $ids_stmt->execute();
                      $ids_result = $ids_stmt->get_result();
                      $ids = [];
                      while ($id_row = $ids_result->fetch_assoc()) { $ids[] = $id_row['booking_id']; }
                      $ids_stmt->close();
                      echo htmlspecialchars(implode(', ', $ids));
                    ?>
                  </strong></span>
                  <span>Price per seat: <strong>₱<?php echo number_format($booking['movie_price'], 2); ?></strong></span>
                  <span>Total price: <strong>₱<?php echo number_format($booking['total_price'], 2); ?></strong></span>
                  <!-- View Details Button -->
                  <button type="button" class="view-details-btn" 
                    data-movie-title="<?php echo htmlspecialchars($booking['movie_title']); ?>"
                    data-cinema-name="<?php echo htmlspecialchars($booking['cinema_name']); ?>"
                    data-seats="<?php echo htmlspecialchars(implode(', ', $booking['seats'])); ?>"
                    data-show-date="<?php echo htmlspecialchars($booking['show_date']); ?>"
                    data-show-time="<?php echo htmlspecialchars(date('g:i A', strtotime($booking['show_time']))); ?>"
                    data-booking-id="<?php echo htmlspecialchars($booking['booking_id']); ?>"
                    data-booking-ids="<?php echo htmlspecialchars(implode(', ', $ids)); ?>"
                    data-movie-price="<?php echo number_format($booking['movie_price'], 2); ?>"
                    data-total-price="<?php echo number_format($booking['total_price'], 2); ?>"
                    >View Details</button>
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
    // Modal logic for transaction details
    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('transaction-modal');
      const modalClose = document.getElementById('transaction-modal-close');
      const modalMovieTitle = document.getElementById('modal-movie-title');
      const modalTransactionList = document.getElementById('modal-transaction-list');
      document.querySelectorAll('.view-details-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          modalMovieTitle.textContent = btn.getAttribute('data-movie-title');
          modalTransactionList.innerHTML = `
            <div><strong>Cinema:</strong> ${btn.getAttribute('data-cinema-name')}</div>
            <div><strong>Seats:</strong> ${btn.getAttribute('data-seats')}</div>
            <div><strong>Date:</strong> ${btn.getAttribute('data-show-date')}</div>
            <div><strong>Time:</strong> ${btn.getAttribute('data-show-time')}</div>
            <div><strong>Booking IDs:</strong> ${btn.getAttribute('data-booking-ids')}</div>
            <div><strong>Price per seat:</strong> ₱${btn.getAttribute('data-movie-price')}</div>
            <div><strong>Total price:</strong> ₱${btn.getAttribute('data-total-price')}</div>
          `;
          modal.style.display = 'block';
        });
      });
      if(modalClose) {
        modalClose.addEventListener('click', function() {
          modal.style.display = 'none';
        });
      }
      window.onclick = function(event) {
        if (event.target == modal) {
          modal.style.display = 'none';
        }
      }
    });
  </script>
  <script src="ProfilePage.js"></script>
</body>
</html>