<?php

include(__DIR__ . "/../db.php");

// Get movie_id and cinema_id from URL
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$cinema_id = isset($_GET['cinema_id']) ? intval($_GET['cinema_id']) : 0;

// Get selected screen_id from URL if set
$screen_id = isset($_GET['screen_id']) ? intval($_GET['screen_id']) : 0;

$movie = null;
$moviePosterUrl = '../Pictures/Placeholder1.png'; // default poster
if ($movie_id > 0) {
  $stmt = $conn->prepare("SELECT * FROM movies WHERE movie_id = ?");
  $stmt->bind_param("i", $movie_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $movie = $result->fetch_assoc();
    // Use the get_movie_image.php script to fetch the poster from the BLOB
    $moviePosterUrl = '../movie%20posters/get_movie_image.php?movie_id=' . $movie_id;
  }
  $stmt->close();
}

// Get selected cinema name for display
$cinema_name = '';
if ($cinema_id) {
  $stmt = $conn->prepare("SELECT name FROM cinemas WHERE cinema_id = ?");
  $stmt->bind_param("i", $cinema_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $row = $result->fetch_assoc()) {
    $cinema_name = $row['name'];
  }
  $stmt->close();
}

// Get screens for this cinema
$screens = [];
if ($cinema_id) {
  $stmt = $conn->prepare("SELECT screen_id, name, seat_capacity FROM screens WHERE cinema_id = ?");
  $stmt->bind_param("i", $cinema_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $screens[] = $row;
  }
  $stmt->close();
}

// Get showtimes for the selected movie, cinema, and screen
$showtimes = [];
if ($movie_id && $cinema_id && $screen_id) {
  $stmt = $conn->prepare(
    "SELECT show_date, show_time 
     FROM showtimes 
     WHERE movie_id = ? AND screen_id = ? 
     AND screen_id IN (SELECT screen_id FROM screens WHERE cinema_id = ?) 
     ORDER BY show_date, show_time"
  );
  $stmt->bind_param("iii", $movie_id, $screen_id, $cinema_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $showtimes[] = $row;
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Get Ticket</title>
  <link rel="stylesheet" href="../Homepage/Homepage.css">
  <link rel="stylesheet" href="GetTicketTemplate.css">
</head>
<body>
  <!-- Progress Bar Header (Get Ticket step active) -->
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
      <span class="progress-arrow">&#8594;</span>
      <span class="progress-step">Seat Selection</span>
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

  <main class="get-ticket-main">
    <div class="get-ticket-card">
      <div class="get-ticket-movie-row">
        <div class="get-ticket-poster-placeholder">
          <img src="<?php echo $moviePosterUrl; ?>" alt="Movie Poster" class="get-ticket-poster-img" />
        </div>
        <div class="get-ticket-movie-info">
          <div class="get-ticket-title-row">
            <div class="get-ticket-title"><?php echo htmlspecialchars($movie['title'] ?? ''); ?></div>
          </div>
          <div class="get-ticket-meta-row">
            <span class="get-ticket-year"><?php echo htmlspecialchars($movie['release_year'] ?? ''); ?></span>
            <span class="get-ticket-dot">•</span>
            <span class="get-ticket-duration"><?php echo htmlspecialchars($movie['duration'] ?? ''); ?> min</span>
            <span class="get-ticket-dot">•</span>
            <span class="get-ticket-rating"><?php echo htmlspecialchars($movie['rating'] ?? ''); ?></span>
          </div>
          <div class="get-ticket-genre"><?php echo htmlspecialchars($movie['genre'] ?? ''); ?></div>
        </div>
      </div>
      <div class="get-ticket-cinema-row" style="display: flex; align-items: center; justify-content: center; gap: 18px;">
        <div class="get-ticket-cinema-name">
          <?php echo htmlspecialchars($cinema_name); ?>
        </div>
        <div id="showtimes-inline-list-container">
        <?php if ($screen_id && count($showtimes) > 0): ?>
          <div class="showtimes-inline-list">
            <?php foreach ($showtimes as $idx => $show): ?>
              <label class="showtime-inline-item">
                <input type="radio" name="showtime_idx" value="<?php echo $idx; ?>" <?php if ($idx === 0) echo 'checked'; ?> form="showtime-form">
                <?php echo htmlspecialchars(date('M d', strtotime($show['show_date']))); ?>
                &nbsp;|&nbsp;
                <?php echo htmlspecialchars(date('g:i A', strtotime($show['show_time']))); ?>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        </div>
      </div>
      <form id="showtime-form" method="get" action="/flixitix/SeatSelection/SeatSelection.php">
        <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
        <input type="hidden" name="cinema_id" value="<?php echo $cinema_id; ?>">
        <input type="hidden" name="screen_id" value="<?php echo $screen_id; ?>">
        <?php if ($screen_id && count($showtimes) > 0): ?>
          <?php foreach ($showtimes as $idx => $show): ?>
            <input type="hidden" name="show_date_<?php echo $idx; ?>" value="<?php echo htmlspecialchars($show['show_date']); ?>">
            <input type="hidden" name="show_time_<?php echo $idx; ?>" value="<?php echo htmlspecialchars($show['show_time']); ?>">
          <?php endforeach; ?>
          <input type="hidden" id="selected_show_date" name="show_date" value="<?php echo htmlspecialchars($showtimes[0]['show_date']); ?>">
          <input type="hidden" id="selected_show_time" name="show_time" value="<?php echo htmlspecialchars($showtimes[0]['show_time']); ?>">
        <?php endif; ?>
        <select name="screen_id" class="get-ticket-screen-select themed-select" required>
          <option value="">Select Cinema Screen</option>
          <?php foreach ($screens as $screen): ?>
            <option value="<?php echo $screen['screen_id']; ?>"
              <?php if ($screen_id == $screen['screen_id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($screen['name']); ?> (<?php echo $screen['seat_capacity']; ?> seats)
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="get-ticket-select-seats" style="margin-top:18px;">Select Seats</button>
      </form>
    </div>
  </main>

  <footer>
    © 2025 Amaguin | A Website for Database Systems
  </footer>
  <script>
     // When a screen is selected, reload the page with the new screen_id (but don't submit to SeatSelection yet)
     document.querySelector('select[name="screen_id"]').addEventListener('change', function() {
       const params = new URLSearchParams(window.location.search);
       params.set('screen_id', this.value);
       window.location.search = params.toString();
     });

    // Update hidden show_date and show_time fields when a showtime is selected
    document.querySelectorAll('input[name="showtime_idx"]').forEach(function(radio) {
      radio.addEventListener('change', function() {
        var idx = this.value;
        document.getElementById('selected_show_date').value = document.querySelector('input[name="show_date_' + idx + '"]').value;
        document.getElementById('selected_show_time').value = document.querySelector('input[name="show_time_' + idx + '"]').value;
      });
    });
  </script>
  <script src="GetTicketTemplate.js"></script>
</body>
</html>