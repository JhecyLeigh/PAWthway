<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="../assets/img/logo.png">
<meta charset="UTF-8">
<title>My Profile - PAWthway</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body { margin:0; font-family:'Poppins',sans-serif; background:linear-gradient(180deg,#e8f5e9 0%,#fff 100%); color:#2e7d32; min-height:100vh; display:flex; flex-direction:column; }
nav { background:#4CAF50; color:white; display:flex; justify-content:space-between; align-items:center; padding:15px 40px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
nav .logo { display:flex; align-items:center; }
nav .logo img { width:50px; margin-right:10px; }
nav .logo span { font-weight:600; font-size:22px; }
nav ul { list-style:none; display:flex; gap:20px; margin:0; padding:0; }
nav ul li a { color:white; text-decoration:none; font-weight:500; transition: opacity 0.3s; }
nav ul li a:hover { opacity:0.8; }

.profile-container { max-width:600px; background:white; margin:50px auto; padding:30px; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.1); text-align:center; }
.profile-container h2 { color:#388e3c; margin-bottom:20px; }
.profile-info p { font-size:16px; margin:10px 0; color:#4b604b; }
.btn { background:#4CAF50; color:white; padding:10px 25px; border:none; border-radius:8px; text-decoration:none; font-weight:500; display:inline-block; margin-top:15px; transition:background 0.3s ease, transform 0.2s; box-shadow:0 4px 10px rgba(76,175,80,0.3); cursor:pointer; }
.btn:hover { background:#43a047; transform:translateY(-2px); }

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
    <li><a href="profile.php">Profile</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</nav>

<div class="profile-container">
  <h2>My Profile</h2>
  <div class="profile-info">
    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
  </div>
  <a href="edit_profile.php" class="btn">Edit Profile</a>
</div>

<footer>
&copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>
