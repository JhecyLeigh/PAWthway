<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_id = intval($_POST['clinic_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    // Check if user already reviewed this clinic
    $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND clinic_id = ?");
    $check_stmt->bind_param("ii", $user_id, $clinic_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        echo "<script>alert('You have already reviewed this clinic.'); window.location='reviews.php';</script>";
        exit;
    }
    $check_stmt->close();
    
    // Insert new review
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, clinic_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $clinic_id, $rating, $comment);
    
    if ($stmt->execute()) {
        echo "<script>alert('Review submitted successfully!'); window.location='reviews.php';</script>";
    } else {
        echo "<script>alert('Error submitting review.'); window.location='reviews.php';</script>";
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

header("Location: reviews.php");
exit;
?>