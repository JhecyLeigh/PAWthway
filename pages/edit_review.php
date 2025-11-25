<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$review_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch review details
$stmt = $conn->prepare("
    SELECT r.*, c.name as clinic_name 
    FROM reviews r 
    JOIN clinics c ON r.clinic_id = c.id 
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->bind_param("ii", $review_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Review not found.'); window.location='reviews.php';</script>";
    exit;
}

$review = $result->fetch_assoc();
$stmt->close();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_review'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    $update_stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ? AND user_id = ?");
    $update_stmt->bind_param("isii", $rating, $comment, $review_id, $user_id);
    
    if ($update_stmt->execute()) {
        echo "<script>alert('Review updated successfully!'); window.location='reviews.php';</script>";
    } else {
        echo "<script>alert('Error updating review.');</script>";
    }
    
    $update_stmt->close();
    $conn->close();
    exit;
}
?>

<!-- Edit Review Form (similar to reviews.php but pre-filled) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review - PAWthway</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Same styles as reviews.php */
        body { margin:0; font-family:'Poppins',sans-serif; background:linear-gradient(180deg,#e8f5e9 0%,#fff 100%); color:#2e7d32; min-height:100vh; display:flex; flex-direction:column; }
        nav { background:#4CAF50; color:white; display:flex; justify-content:space-between; align-items:center; padding:15px 40px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
        /* ... include all the same styles from reviews.php ... */
    </style>
</head>
<body>

<?php include('../config/navbar.php'); ?>

<div class="container">
    <h2>Edit Review for <?php echo htmlspecialchars($review['clinic_name']); ?></h2>
    
    <div class="review-form">
        <form method="POST" action="edit_review.php?id=<?php echo $review_id; ?>">
            <label>Rating:</label>
            <div class="rating-stars">
                <?php for($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" 
                           <?php echo $review['rating'] == $i ? 'checked' : ''; ?> required>
                    <label for="star<?php echo $i; ?>">★</label>
                <?php endfor; ?>
            </div>
            
            <label for="comment">Your Review:</label>
            <textarea name="comment" id="comment" required><?php echo htmlspecialchars($review['comment']); ?></textarea>
            
            <button type="submit" name="update_review" class="btn btn-primary">Update Review</button>
            <a href="reviews.php" class="btn" style="background:#757575; display:block; text-align:center; margin-top:10px;">Cancel</a>
        </form>
    </div>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>