<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

if (isset($_POST['update'])) {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  if (!empty($password)) {
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=? WHERE id=?");
    $stmt->bind_param("sssi", $username, $email, $password, $user_id);
  } else {
    $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
  }

  if ($stmt->execute()) {
    $updatedUser = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'"));
    $_SESSION['user'] = $updatedUser;
    $success = "Profile updated successfully!";
  } else {
    $error = "Error updating profile.";
  }

  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="../assets/img/logo.png">
<meta charset="UTF-8">
<title>Edit Profile - PAWthway</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(180deg, #e8f5e9 0%, #fff 100%);
  color: #2e7d32;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

nav {
  background: #4CAF50;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 40px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

nav .logo {
  display: flex;
  align-items: center;
}

nav .logo img {
  width: 50px;
  margin-right: 10px;
}

nav .logo span {
  font-weight: 600;
  font-size: 22px;
}

nav ul {
  list-style: none;
  display: flex;
  gap: 20px;
  margin: 0;
  padding: 0;
}

nav ul li a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  transition: opacity 0.3s;
}

nav ul li a:hover {
  opacity: 0.8;
}

.edit-container {
  max-width: 500px;
  background: white;
  margin: 60px auto;
  padding: 40px 35px;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  text-align: center;
}

.edit-container h2 {
  color: #388e3c;
  margin-bottom: 25px;
  font-size: 24px;
}

.edit-container form input,
.edit-container form button,
.edit-container a.btn-back {
  width: 100%;
  padding: 12px 15px;
  border-radius: 10px;
  border: 1px solid #ccc;
  margin-bottom: 15px;
  font-size: 16px;
  box-sizing: border-box;
}

.edit-container form input:focus {
  border-color: #4CAF50;
  outline: none;
  box-shadow: 0 0 5px rgba(76,175,80,0.3);
}

.edit-container form button {
  background: #4CAF50;
  color: white;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: background 0.3s ease, transform 0.2s ease;
  box-shadow: 0 4px 10px rgba(76,175,80,0.3);
}

.edit-container form button:hover {
  background: #43a047;
  transform: translateY(-2px);
}

a.btn-back {
  display: inline-block;
  background: #81c784;
  color: white;
  text-decoration: none;
  text-align: center;
  font-weight: 500;
  transition: background 0.3s ease, transform 0.2s ease;
  box-shadow: 0 4px 10px rgba(76,175,80,0.3);
}

a.btn-back:hover {
  background: #66bb6a;
  transform: translateY(-2px);
}

.message {
  margin-top: 10px;
  font-size: 14px;
}

.success { color: green; }
.error { color: red; }

footer {
  text-align: center;
  padding: 15px;
  background: #e8f5e9;
  color: #388e3c;
  font-size: 14px;
  margin-top: auto;
}
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

<div class="edit-container">
  <h2>Edit Profile</h2>
  <form method="POST">
    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
    <input type="password" name="password" placeholder="New Password (optional)">
    <button type="submit" name="update">Update Profile</button>
  </form>

  <?php
    if (isset($success)) echo "<p class='message success'>$success</p>";
    if (isset($error)) echo "<p class='message error'>$error</p>";
  ?>

  <a href="profile.php" class="btn-back">← Back to Profile</a>
</div>

<footer>
&copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>
