<?php


include(__DIR__ . "/../db.php");
session_start();

// 1. Get movie_id from URL
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$movie = null;
$availableCinemaIds = [];

if ($movie_id > 0) {
  $stmt = $conn->prepare("SELECT * FROM movies WHERE movie_id = ?");
  $stmt->bind_param("i", $movie_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $movie = $result->fetch_assoc();
    // Now using cinema_id (assume movies.cinema_id is a comma-separated list of IDs)
    $availableCinemaIds = array_map('intval', array_map('trim', explode(',', $movie['cinema_id'])));
  }
  $stmt->close();
}

// Cinema details (add more as needed)
$cinemaDetails = [];
$cinema_result = $conn->query("SELECT * FROM cinemas");
while ($row = $cinema_result->fetch_assoc()) {
    // Use a PHP script to serve the BLOB image for each cinema
    $logoUrl = '../movie%20posters/get_cinema_image.php?cinema_id=' . $row['cinema_id'];
    $cinemaDetails[$row['cinema_id']] = [
        'logo' => $logoUrl,
        'title' => strtoupper($row['name']),
        'address' => nl2br(htmlspecialchars($row['address'] ?? '')),
        'gallery' => [
            '../Pictures/Placeholder3.png',
            '../Pictures/Placeholder4.png',
            '../Pictures/Placeholder5.png',
            '../Pictures/Placeholder1.png'
        ]
    ];
}

// Fetch and display the user's selected avatar
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cinema Menu</title>
  <link rel="stylesheet" href="../Homepage/Homepage.css">
  <link rel="stylesheet" href="CinemaMenu.css">
</head>
<body>
  <!-- Progress Bar Header (reused from Movie Menu, Cinema step active) -->
  <div class="custom-progress-bar dark">
  <a href="../HomePage/Homepage.php">
    <img src="../Pictures/Logo.png" alt="Logo" class="progress-logo">
  </a>
  
    <div class="progress-steps">

       <span class="progress-step active">Main Menu</span>
       <span class="progress-arrow active">&#8594;</span>
       <span class="progress-step active">Movies</span>
       <span class="progress-arrow active">&#8594;</span>
       <span class="progress-step active" id="progress-cinema">Cinema</span>
       <span class="progress-arrow">&#8594;</span>
       <span class="progress-step" >Get Ticket</span>
       <span class="progress-arrow">&#8594;</span>
       <span class="progress-step" >Seat Selection</span>
       <span class="progress-arrow">&#8594;</span>
       <span class="progress-step" >Summary</span>
    </div>
    <div class="header-profile">
      <a href="../ProfilePage/ProfilePage.php" class="header-profile-link-rect" aria-label="Go to Profile Page">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="User Profile" class="header-pfp">
        <span class="header-profile-text">Profile</span>
      </a>
    </div>
  </div>

  <!-- Back Button to Movie Menu -->
  <a href="../MovieMenu/Moviemenu.php" class="back-to-movie-menu-btn" aria-label="Back to Movies">
    <span class="back-arrow" aria-hidden="true">&#8592;</span> <span class="back-label">Back to Movies</span>
  </a>

  <main class="cinema-menu-main">
    <div class="cinema-list">
      <?php
      // Only show cinemas where the movie is available
      foreach ($cinemaDetails as $cinemaId => $cinema) {
        if (!$movie || in_array($cinemaId, $availableCinemaIds)) {
          echo '<div class="cinema-row-banner">';
          echo '<a href="../GetTicketMenu/GetTicketTemplate.php?movie_id=' . $movie_id . '&cinema_id=' . $cinemaId . '" class="cinema-banner-link">';
          echo '<div class="cinema-banner-img-wrap">';
          echo '<img src="' . $cinema['logo'] . '" alt="' . $cinema['title'] . ' Banner" class="cinema-banner-img" />';
          echo '<div class="cinema-banner-overlay">';
          echo '<div class="cinema-banner-title">' . $cinema['title'] . '</div>';
          echo '<div class="cinema-banner-address">' . $cinema['address'] . '</div>';
          echo '</div>';
          echo '</div>';
          echo '</a>';
          echo '</div>';
          echo '<hr class="cinema-divider" />';
        }
      }
      ?>
    </div>
  </main>

  <footer>
    © 2025 Amaguin | A Website for Database Systems
  </footer>
  <div id="image-modal" class="image-modal">
    <div class="image-modal-content">
      <button class="image-modal-close" id="image-modal-close" aria-label="Close">&times;</button>
      <img id="image-modal-img" src="" alt="Cinema Fullscreen" draggable="false" />
    </div>
  </div>
  <script src="CinemaMenu.js"></script>
</body>
</html>