<?php
include(__DIR__ . '/../db.php');
$cinema_id = isset($_GET['cinema_id']) ? intval($_GET['cinema_id']) : 0;
if ($cinema_id > 0) {
    $stmt = $conn->prepare('SELECT image_blob FROM cinemas WHERE cinema_id = ?');
    $stmt->bind_param('i', $cinema_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($image_blob);
        $stmt->fetch();
        if ($image_blob) {
            header('Content-Type: image/jpeg'); // Change to image/png if needed
            echo $image_blob;
            exit;
        }
    }
    $stmt->close();
}
// fallback image if not found
header('Content-Type: image/png');
readfile(__DIR__ . '/../Pictures/Placeholder1.png');
exit;