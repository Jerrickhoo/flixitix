<?php // Homepage.php 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title>Movie Page</title>
	<link rel="stylesheet" href="Homepage.css">
</head>
<body>
<!-- Progress Bar (replaces header) -->
<div class="custom-progress-bar dark">
  <a href="../Homepage/Homepage.php">
    <img src="../Pictures/Logo.png" alt="Logo" class="progress-logo">
  </a>
  <div class="progress-steps">
    <span class="progress-step active" id="progress-main-menu">Main Menu</span>
    <span class="progress-arrow">&#8594;</span>
    <span class="progress-step" id="progress-movies">Movies</span>
    <span class="progress-arrow">&#8594;</span>
    <span class="progress-step" id="progress-cinema">Cinema</span>
    <span class="progress-arrow">&#8594;</span>
    <span class="progress-step" id="progress-get-ticket">Get Ticket</span>
    <span class="progress-arrow">&#8594;</span>    <span class="progress-step" id="progress-seat-selection">Seat Selection</span>
    <span class="progress-arrow">&#8594;</span>
    <span class="progress-step" id="progress-confirmation">Summary</span>
  </div>
  <div class="header-profile">
    <a href="../ProfilePage/ProfilePage.php" class="header-profile-link-rect" aria-label="Go to Profile Page">
      <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="User Profile" class="header-pfp">
      <span class="header-profile-text">Profile</span>
    </a>
  </div>
</div>

<div class="tabs">
	<span class="tab active" onclick="switchTab(this)">NOW SHOWING</span>
	<span class="tab" onclick="switchTab(this)">COMING SOON</span>
</div>

<div class="carousel-container">
	<button class="carousel-button left" onclick="scrollCarousel(-1)">&#10094;</button>
	<div class="scroll-wrapper" id="carousel">
		<div class="movie-card"><img src="../movie posters/Twisters.jpg" alt="Twisters" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/The Garfield Movie.jpg" alt="The Garfield Movie" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/The Fall Guy.jpg" alt="The Fall Guy" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/Superman Legacy.jpeg" alt="Superman Legacy" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/Mission Impossible – Dead Reckoning Part Two.jpg" alt="Mission Impossible – Dead Reckoning Part Two" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/Kung Fu Panda 4.jpg" alt="Kung Fu Panda 4" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/Joker Folie à Deux.jpg" alt="Joker Folie à Deux" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/Inside Out 2.jpg" alt="Inside Out 2" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/Furiosa A Mad Max Saga.jpg" alt="Furiosa A Mad Max Saga" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/Fantastic Four.jpg" alt="Fantastic Four" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/Dune.jpg" alt="Dune" style="width:100%;height:100%;object-fit:cover;"></div>
		<div class="movie-card"><img src="../movie posters/Deadpool & Wolverine.jpg" alt="Deadpool & Wolverine" style="width:100%;height:100%;object-fit:cover;"></div>
	</div>
	<button class="carousel-button right" onclick="scrollCarousel(1)">&#10095;</button>
</div>

<div class="browse-button">
	<button onclick="window.location.href='../MovieMenu/Moviemenu.php'">Browse All Movies</button>
</div>

<footer>
	© 2025 Amaguin | A Website for Database Systems
</footer>


<script src="HomePage.js"></script>


</body>
</html>
