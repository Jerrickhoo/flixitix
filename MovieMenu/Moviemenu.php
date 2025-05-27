<?php

// Movie Menu Page

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

// Get filter values before HTML so we can use them in the <select>
$genre = isset($_GET['genre']) ? $_GET['genre'] : '';
$cinema_id = isset($_GET['cinema_id']) ? intval($_GET['cinema_id']) : 0;
$availability = isset($_GET['availability']) ? $_GET['availability'] : '';
$rating = isset($_GET['rating']) ? $_GET['rating'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch all cinemas for the dropdown
$cinemas = [];
$cinema_result = $conn->query("SELECT cinema_id, name FROM cinemas");
while ($row = $cinema_result->fetch_assoc()) {
    $cinemas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Movie Menu</title>
  <link rel="stylesheet" href="../Homepage/Homepage.css">
  <link rel="stylesheet" href="MovieMenu.css">
</head>
<body>
  <!-- Progress Bar Header (copied from Homepage) -->
  <div class="custom-progress-bar dark">
  <a href="../Homepage/Homepage.php">
    <img src="../Pictures/Logo.png" alt="Logo" class="progress-logo">
  </a>
    <div class="progress-steps">

    <span class="progress-step active">Main Menu</span>
    <span class="progress-arrow active">&#8594;</span>
    <span class="progress-step active" id="progress-movies">Movies</span>
    <span class="progress-arrow">&#8594;</span>
    <span class="progress-step" >Cinema</span>
    <span class="progress-arrow">&#8594;</span>
    <span class="progress-step" >Get Ticket</span>
    <span class="progress-arrow">&#8594;</span>    <span class="progress-step" >Seat Selection</span>
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

  <!-- Main Content -->
  <main class="movie-menu-main">
    <div class="movie-menu-filters">
      <div class="filters-title">BROWSE MOVIES BY</div>
      <form id="filterForm" method="get">
        <div class="filters-row">
          <div class="filter-group">
            <label for="genre">Genre</label>
            <select id="genre" name="genre" onchange="document.getElementById('filterForm').submit()">
              <option value="" <?php if ($genre == '') echo 'selected'; ?>>ALL</option>
              <option <?php if ($genre == 'Action') echo 'selected'; ?>>Action</option>
              <option <?php if ($genre == 'Adventure') echo 'selected'; ?>>Adventure</option>
              <option <?php if ($genre == 'Comedy') echo 'selected'; ?>>Comedy</option>
              <option <?php if ($genre == 'Drama') echo 'selected'; ?>>Drama</option>
              <option <?php if ($genre == 'Horror') echo 'selected'; ?>>Horror</option>
              <option <?php if ($genre == 'Thriller') echo 'selected'; ?>>Thriller</option>
              <option <?php if ($genre == 'Romance') echo 'selected'; ?>>Romance</option>
              <option <?php if ($genre == 'Animation') echo 'selected'; ?>>Animation</option>
              <option <?php if ($genre == 'Sci-Fi') echo 'selected'; ?>>Sci-Fi</option>
              <option <?php if ($genre == 'Fantasy') echo 'selected'; ?>>Fantasy</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="cinema_id">Cinema</label>
            <select id="cinema_id" name="cinema_id" onchange="document.getElementById('filterForm').submit()">
              <option value="0" <?php if ($cinema_id == 0) echo 'selected'; ?>>ALL</option>
              <?php foreach ($cinemas as $c): ?>
                <option value="<?php echo $c['cinema_id']; ?>" <?php if ($cinema_id == $c['cinema_id']) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($c['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label for="availability">Availability</label>
            <select id="availability" name="availability" onchange="document.getElementById('filterForm').submit()">
              <option value="" <?php if ($availability == '') echo 'selected'; ?>>ALL</option>
              <option <?php if ($availability == 'Now Showing') echo 'selected'; ?>>Now Showing</option>
              <option <?php if ($availability == 'Coming Soon') echo 'selected'; ?>>Coming Soon</option>
            
            </select>
          </div>
          <div class="filter-group">
            <label for="rating">Rating</label>
            <select id="rating" name="rating" onchange="document.getElementById('filterForm').submit()">
              <option value="" <?php if ($rating == '') echo 'selected'; ?>>ALL</option>
              <option <?php if ($rating == 'G') echo 'selected'; ?>>G</option>
              <option <?php if ($rating == 'PG') echo 'selected'; ?>>PG</option>
              <option <?php if ($rating == 'PG-13') echo 'selected'; ?>>PG-13</option>
              <option <?php if ($rating == 'R') echo 'selected'; ?>>R</option>
              <option <?php if ($rating == 'NC-17') echo 'selected'; ?>>NC-17</option>
            </select>
          </div>
          <div class="filter-group search-bar-group" style="margin-left:auto;min-width:220px;max-width:260px;">
            <label for="movieSearch" style="visibility:hidden;height:0;margin:0;padding:0;">Search</label>
            <input type="text" id="movieSearch" name="search" class="movie-search-bar" placeholder="Search Movies" autocomplete="off" value="<?php echo htmlspecialchars($search); ?>" />
          </div>
        </div>
      </form>
      <hr class="filters-divider" />
    </div>
    <div class="movie-menu-grid">
      <?php
        // Build query with optional filters
        $sql = "SELECT m.* FROM movies m";
        $params = [];
        $types = '';

        // Filter by cinema_id if set (assuming movies table has cinema_id column)
        if ($cinema_id && $cinema_id != 0) {
          $sql .= " WHERE m.cinema_id = ?";
          $params[] = $cinema_id;
          $types .= 'i';
        } else {
          $sql .= " WHERE 1=1";
        }

        // --- MULTI-GENRE SUPPORT ---
        if ($genre && $genre !== 'ALL') {
          $sql .= " AND FIND_IN_SET(?, REPLACE(m.genre, ' ', ''))";
          $params[] = str_replace(' ', '', $genre);
          $types .= 's';
        }
        if ($availability && $availability !== 'ALL') {
          $sql .= " AND m.availability = ?";
          $params[] = $availability;
          $types .= 's';
        }
        if ($rating && $rating !== 'ALL') {
          $sql .= " AND m.rating = ?";
          $params[] = $rating;
          $types .= 's';
        }
        if ($search) {
          $sql .= " AND m.title LIKE ?";
          $params[] = "%$search%";
          $types .= 's';
        }
        $sql .= " GROUP BY m.movie_id";

        $stmt = $conn->prepare($sql);
        if ($params) {
          $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
          while ($movie = $result->fetch_assoc()) {
            $movieTitle = htmlspecialchars($movie['title']);
            $movieGenre = htmlspecialchars($movie['genre']);
            $movieAvailability = htmlspecialchars($movie['availability']);
            $movieRating = htmlspecialchars($movie['rating']);
            $movieYear = htmlspecialchars($movie['release_year']);
            $movieDuration = htmlspecialchars($movie['duration']);
            $movieDesc = htmlspecialchars($movie['description']);
            $movieId = $movie['movie_id'];
            // Use the get_movie_image.php script to fetch the poster from the BLOB
            $moviePosterUrl = '../movie%20posters/get_movie_image.php?movie_id=' . $movieId;

            echo '<a href="../CinemaMenu/CinemaMenu.php?movie_id=' . $movieId . '" class="movie-menu-card" data-name="' . $movieTitle . '" data-genre="' . $movieGenre . '" data-availability="' . $movieAvailability . '" data-rating="' . $movieRating . '">';
            echo '<img src="' . $moviePosterUrl . '" alt="Movie Poster" class="movie-poster-img">';
            echo '<div class="movie-info">';
            echo '<div class="movie-title">' . $movieTitle . '</div>';
            echo '<div class="movie-meta">' . $movieGenre . ' | ' . $movieYear . ' | ' . $movieDuration . ' min | ' . $movieRating . ' | ' . $movieAvailability . '</div>';
            echo '<div class="movie-description" style="font-size:13px;color:#aaa;">' . $movieDesc . '</div>';
            echo '</div>';
            echo '</a>';
          }
        } else {
          echo "<div style='color:white;text-align:center;width:100%;'>No movies found.</div>";
        }
        if (isset($stmt)) $stmt->close();
      ?>
    </div>
  </main>

  <footer>
    © 2025 Amaguin | A Website for Database Systems
  </footer>
  <script>
    // Optional: Submit form on Enter in search bar
    document.getElementById('movieSearch').addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        document.getElementById('filterForm').submit();
      }
    });
  </script>
  <script src="MovieMenu.js"></script>
</body>
</html>