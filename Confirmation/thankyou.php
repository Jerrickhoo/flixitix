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

if (!isset($_SESSION['user_email'])) {
  header("Location: ../login.php");
  exit();
}
$user_email = $_SESSION['user_email'];

// Fetch user's name for display (show name if set, else email)
$user_display_name = $user_email;
$stmt = $conn->prepare("SELECT name FROM users WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$stmt->bind_result($fetched_name);
if ($stmt->fetch() && !empty($fetched_name)) {
  $user_display_name = $fetched_name;
}
$stmt->close();

// Handle booking confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
  $movie_id = intval($_POST['movie_id']);
  $cinema_id = intval($_POST['cinema_id']);
  $screen_id = intval($_POST['screen_id']);
  $show_date = $_POST['show_date'];
  $show_time = $_POST['show_time'];
  $seats = isset($_POST['seat']) ? (array)$_POST['seat'] : [];
  $user_email = $_SESSION['user_email'];

  $already_booked = [];
  foreach ($seats as $seat) {
    // Check if seat is already booked for this showtime
    $stmt = $conn->prepare("SELECT COUNT(*) FROM booking WHERE movie_id = ? AND cinema_id = ? AND screen_id = ? AND show_date = ? AND show_time = ? AND seat = ?");
    $stmt->bind_param("iiisss", $movie_id, $cinema_id, $screen_id, $show_date, $show_time, $seat);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count > 0) {
      $already_booked[] = $seat;
    }
  }
  if (count($already_booked) > 0) {
    echo "<h2 style='color:red;text-align:center;'>Sorry, the following seats are already booked: <b>" . htmlspecialchars(implode(", ", $already_booked)) . "</b></h2>";
    echo "<p style='text-align:center;'><a href='../SeatSelection/SeatSelection.php?movie_id=$movie_id&cinema_id=$cinema_id&screen_id=$screen_id&show_date=$show_date&show_time=$show_time'>Go back to seat selection</a></p>";
    exit;
  }
  // Insert all seats with the same created_at timestamp
  $created_at = date('Y-m-d H:i:s');
  $booking_ids = [];
  foreach ($seats as $seat) {
    $stmt = $conn->prepare("INSERT INTO booking (user_email, movie_id, cinema_id, screen_id, show_date, show_time, seat, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiissss", $user_email, $movie_id, $cinema_id, $screen_id, $show_date, $show_time, $seat, $created_at);
    $stmt->execute();
    $booking_ids[] = $stmt->insert_id;
    $stmt->close();
  }
  // Fetch movie and cinema details for session
  $stmt = $conn->prepare("SELECT title, price FROM movies WHERE movie_id = ?");
  $stmt->bind_param("i", $movie_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $movie_title = '';
  $movie_price = 0;
  if ($row = $result->fetch_assoc()) {
    $movie_title = $row['title'];
    $movie_price = $row['price'];
  }
  $stmt->close();
  $stmt = $conn->prepare("SELECT name FROM cinemas WHERE cinema_id = ?");
  $stmt->bind_param("i", $cinema_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $cinema_name = '';
  if ($row = $result->fetch_assoc()) $cinema_name = $row['name'];
  $stmt->close();
  // Store ticket details in session
  $_SESSION['ticket_details'] = [
    'movie_id' => $movie_id,
    'cinema_id' => $cinema_id,
    'screen_id' => $screen_id,
    'show_date' => $show_date,
    'show_time' => $show_time,
    'seats' => $seats,
    'movie_title' => $movie_title,
    'movie_price' => $movie_price,
    'cinema_name' => $cinema_name,
    'booking_ids' => $booking_ids
  ];
  header("Location: thankyou.php");
  exit();
}

// On GET, fetch ticket details from session
$ticket_details = isset($_SESSION['ticket_details']) ? $_SESSION['ticket_details'] : null;
$show_confirm_form = false;
if ($ticket_details) {
  $movie_id = $ticket_details['movie_id'];
  $cinema_id = $ticket_details['cinema_id'];
  $screen_id = $ticket_details['screen_id'];
  $show_date = $ticket_details['show_date'];
  $show_time = $ticket_details['show_time'];
  $seats = $ticket_details['seats'];
  $movie_title = $ticket_details['movie_title'];
  $movie_price = $ticket_details['movie_price'];
  $cinema_name = $ticket_details['cinema_name'];
  $booking_ids = $ticket_details['booking_ids'];
  // Clear after displaying to prevent re-showing on refresh
  unset($_SESSION['ticket_details']);
  $show_confirm_form = false;
} else {
  // If not coming from a booking, show confirm form (legacy/manual access)
  $show_confirm_form = true;
  // No ticket details, redirect to movie menu
  header("Location: ../MovieMenu/Moviemenu.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Thank You | <?php echo htmlspecialchars($user_email); ?></title>
  <link rel="stylesheet" href="../Homepage/Homepage.css">
  <link rel="stylesheet" href="thankyou.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
 <div class="custom-progress-bar dark" style="margin-bottom: 32px;">
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
      <span class="progress-arrow active">&#8594;</span>
      <span class="progress-step active">Seat Selection</span>
      <span class="progress-arrow active">&#8594;</span>
      <span class="progress-step active" id="progress-confirmation">Confirmation</span>
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

  <main>
    <section class="thankyou-container">
  <h1>Thank you, <?php echo htmlspecialchars($user_display_name); ?>!</h1>
  <div class="ticket-details">
    <div class="detail-row">
      <span class="label">Cinema:</span>
      <span class="value"><u><b><?php echo htmlspecialchars($cinema_name); ?></b></u></span>
    </div>
    <div class="detail-row">
      <span class="label">Movie:</span>
      <span class="value"><u><b><?php echo htmlspecialchars($movie_title); ?></b></u></span>
    </div>
    <div class="detail-row">
      <span class="label">Seat(s):</span>
      <span class="value"><u><b><?php echo htmlspecialchars(implode(', ', $seats)); ?></b></u></span>
    </div>
    <div class="detail-row">
      <span class="label">Time:</span>
      <span class="value"><u><b>
        <?php echo htmlspecialchars(date('M d, Y', strtotime($show_date))); ?>
        <?php echo htmlspecialchars(date('g:i A', strtotime($show_time))); ?>
      </b></u></span>
    </div>
    <div class="detail-row">
      <span class="label">Price per seat:</span>
      <span class="value"><u><b>₱<?php echo number_format($movie_price, 2); ?></b></u></span>
    </div>    <div class="detail-row">
      <span class="label">Total price:</span>
      <span class="value"><u><b>₱<?php echo number_format($movie_price * count($seats), 2); ?></b></u></span>
    </div>
  </div>
  <div class="qr-code-wrapper">
    <div class="qr-code-section">
      <img src="../Pictures/QR_Code_Picture.jpeg" alt="Booking QR Code" class="qr-code-image">
      <div class="qr-code-instructions">
        <p class="verification-text">Please present this QR code at the cinema payment centers for verification</p>
        <p class="save-text">Save or screenshot this page</p>
      </div>
    </div>
  </div>

  <?php if ($show_confirm_form): ?>
  <form method="post" style="margin-top:24px;">
    <input type="hidden" name="confirm_booking" value="1">
    <input type="hidden" name="movie_id" value="<?php echo htmlspecialchars($movie_id); ?>">
    <input type="hidden" name="cinema_id" value="<?php echo htmlspecialchars($cinema_id); ?>">
    <input type="hidden" name="screen_id" value="<?php echo htmlspecialchars($screen_id); ?>">
    <input type="hidden" name="show_date" value="<?php echo htmlspecialchars($show_date); ?>">
    <input type="hidden" name="show_time" value="<?php echo htmlspecialchars($show_time); ?>">
    <?php foreach ($seats as $seat): ?>
    <input type="hidden" name="seat[]" value="<?php echo htmlspecialchars($seat); ?>">
    <?php endforeach; ?>
    <button class="confirm-btn" type="submit">CONFIRM</button>
  </form>
  <?php else: ?>
  <div style="margin-top:24px; text-align:center;">
    <a href="../ProfilePage/ProfilePage.php" class="confirm-btn">Go to My Profile</a>
  </div>
  <?php endif; ?>
</section>
  </main>
  <footer>
    © 2025 Amaguin | A Website for Database Systems
  </footer>
</body>
</html>