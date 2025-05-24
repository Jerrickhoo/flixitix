<?php

include(__DIR__ . "/../db.php");

$genre = $_GET['genre'] ?? '';
$availability = $_GET['availability'] ?? '';
$rating = $_GET['rating'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($genre && $genre !== 'ALL') {
    $where[] = "genre = ?";
    $params[] = $genre;
}
if ($availability && $availability !== 'ALL') {
    $where[] = "availability = ?";
    $params[] = $availability;
}
if ($rating && $rating !== 'ALL') {
    $where[] = "rating = ?";
    $params[] = $rating;
}
if ($search) {
    $where[] = "title LIKE ?";
    $params[] = "%$search%";
}

$sql = "SELECT * FROM movies";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$stmt = $conn->prepare($sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($movie = $result->fetch_assoc()) {
        ?>
        <a href="../CinemaMenu/CinemaMenu.php?movie_id=<?php echo $movie['movie_id']; ?>" class="movie-menu-card"
           data-name="<?php echo htmlspecialchars($movie['title']); ?>">
          <img src="../Pictures/<?php echo htmlspecialchars($movie['image']); ?>" alt="Movie Poster" class="movie-poster-img">
          <div class="movie-info">
            <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
            <div class="movie-meta">
              <?php echo $movie['duration']; ?> min | 
              <?php echo htmlspecialchars($movie['rating']); ?> | 
              <?php echo htmlspecialchars($movie['availability']); ?>
            </div>
            <div class="movie-description" style="font-size:13px;color:#aaa;">
              <?php echo htmlspecialchars($movie['genre']); ?>
            </div>
          </div>
        </a>
        <?php
    }
} else {
    echo "<div style='color:white;text-align:center;width:100%;'>No movies found.</div>";
}
?>