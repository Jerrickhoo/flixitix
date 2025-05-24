<?php

include(__DIR__ . "/../db.php");
$movie_id = intval($_GET['movie_id']);
$stmt = $conn->prepare("SELECT image_blob FROM movies WHERE movie_id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$stmt->bind_result($image);
if ($stmt->fetch() && $image) {
    header("Content-Type: image/jpeg"); // or image/png if that's your format
    echo $image;
} else {
    // fallback image if no blob exists
    readfile("../Pictures/Placeholder1.png");
}
$stmt->close();
?>