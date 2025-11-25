<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// Handle review deletion
if (isset($_POST['delete_review'])) {
    $review_id = intval($_POST['review_id']);
    
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $review_id, $user_id);
    
    if ($stmt->execute()) {
        $message = "Review deleted successfully!";
    } else {
        $error = "Error deleting review.";
    }
    $stmt->close();
}

// Fetch user's reviews
$stmt = $conn->prepare("
    SELECT r.*, c.name as clinic_name 
    FROM reviews r 
    JOIN clinics c ON r.clinic_id = c.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all clinics for the dropdown
$clinics_result = $conn->query("SELECT id, name FROM clinics ORDER BY name");
$clinics = $clinics_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - PAWthway</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family:'Poppins',sans-serif; background:linear-gradient(180deg,#e8f5e9 0%,#fff 100%); color:#2e7d32; min-height:100vh; display:flex; flex-direction:column; }
        nav { background:#4CAF50; color:white; display:flex; justify-content:space-between; align-items:center; padding:15px 40px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
        nav .logo { display:flex; align-items:center; }
        nav .logo img { width:50px; margin-right:10px; }
        nav .logo span { font-weight:600; font-size:22px; }
        nav ul { list-style:none; display:flex; gap:20px; margin:0; padding:0; }
        nav ul li a { color:white; text-decoration:none; font-weight:500; transition:opacity 0.3s; }
        nav ul li a:hover { opacity:0.8; }
        
        .container { max-width:800px; margin:30px auto; background:white; padding:30px; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
        .container h2 { color:#388e3c; text-align:center; margin-bottom:30px; }
        
        .review-form { background:#f8f9fa; padding:25px; border-radius:15px; margin-bottom:30px; }
        .review-form h3 { color:#388e3c; margin-top:0; }
        .review-form label { display:block; margin:10px 0 5px; font-weight:500; }
        .review-form select, .review-form textarea, .review-form input { width:100%; padding:12px; border-radius:10px; border:1px solid #ccc; margin-bottom:15px; font-size:16px; box-sizing:border-box; }
        .review-form textarea { height:100px; resize:vertical; }
        
        .rating-stars { display:flex; gap:10px; margin-bottom:15px; }
        .rating-stars input { display:none; }
        .rating-stars label { font-size:24px; cursor:pointer; color:#ddd; margin:0; }
        .rating-stars input:checked ~ label, .rating-stars label:hover, .rating-stars label:hover ~ label { color:#ffc107; }
        
        .reviews-list { margin-top:30px; }
        .review-card { background:white; border:1px solid #e0e0e0; border-radius:15px; padding:20px; margin-bottom:20px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
        .review-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .review-clinic { font-weight:600; color:#2e7d32; font-size:18px; }
        .review-rating { color:#ffc107; font-size:18px; }
        .review-date { color:#666; font-size:14px; }
        .review-comment { color:#333; line-height:1.6; margin:10px 0; }
        .review-actions { display:flex; gap:10px; margin-top:15px; }
        
        .btn { padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:500; text-decoration:none; display:inline-block; transition:0.3s; }
        .btn-primary { background:#4CAF50; color:white; }
        .btn-edit { background:#2196F3; color:white; }
        .btn-delete { background:#f44336; color:white; }
        .btn:hover { opacity:0.9; transform:translateY(-2px); }
        
        .no-reviews { text-align:center; padding:40px; color:#666; font-size:16px; }
        .success { background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:10px; margin-bottom:20px; }
        .error { background:#ffebee; color:#c62828; padding:15px; border-radius:10px; margin-bottom:20px; }
        
        footer { text-align:center; padding:15px; background:#e8f5e9; color:#388e3c; font-size:14px; margin-top:auto; }
    </style>
</head>
<body>

<nav>
  <div class="logo">
    <img src="../assets/img/logo.png" alt="PAWthway Logo">
    <span>PAWthway</span>
  </div>
  <ul>
    <li><a href="dashboard.php">Home</a></li>
    <li><a href="clinics.php">Clinics</a></li>
    <li><a href="appointment_list.php">My Appointments</a></li>
    <li><a href="reviews.php">My Reviews</a></li>
    <li><a href="profile.php">Profile</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</nav>

<div class="container">
    <h2>My Reviews</h2>
    
    <?php if (isset($message)): ?>
        <div class="success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Add Review Form -->
    <div class="review-form">
        <h3>Write a Review</h3>
        <form method="POST" action="submit_review.php">
            <label for="clinic_id">Select Clinic:</label>
            <select name="clinic_id" id="clinic_id" required>
                <option value="">-- Choose a Clinic --</option>
                <?php foreach($clinics as $clinic): ?>
                    <option value="<?php echo $clinic['id']; ?>"><?php echo htmlspecialchars($clinic['name']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <label>Rating:</label>
            <div class="rating-stars">
                <input type="radio" id="star5" name="rating" value="5" required>
                <label for="star5">★</label>
                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4">★</label>
                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3">★</label>
                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2">★</label>
                <input type="radio" id="star1" name="rating" value="1">
                <label for="star1">★</label>
            </div>
            
            <label for="comment">Your Review:</label>
            <textarea name="comment" id="comment" placeholder="Share your experience with this clinic..." required></textarea>
            
            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
    </div>
    
    <!-- User's Reviews List -->
    <div class="reviews-list">
        <h3>My Reviews (<?php echo count($user_reviews); ?>)</h3>
        
        <?php if (count($user_reviews) > 0): ?>
            <?php foreach($user_reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <span class="review-clinic"><?php echo htmlspecialchars($review['clinic_name']); ?></span>
                        <span class="review-rating">
                            <?php echo str_repeat('★', $review['rating']); ?>
                        </span>
                    </div>
                    <div class="review-date">
                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                    </div>
                    <div class="review-comment">
                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                    </div>
                    <div class="review-actions">
                        <a href="edit_review.php?id=<?php echo $review['id']; ?>" class="btn btn-edit">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" name="delete_review" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this review?')">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-reviews">
                <p>You haven't written any reviews yet.</p>
                <p>Share your experience with other pet owners!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>