<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

if (!isset($_POST['appointment_id']) && !isset($_GET['id'])) {
    echo "<script>alert('Invalid appointment.'); window.location='appointment_list.php';</script>";
    exit;
}

$appointment_id = isset($_POST['appointment_id']) ? $_POST['appointment_id'] : $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Appointment not found.'); window.location='appointment_list.php';</script>";
    exit;
}

$appointment = $result->fetch_assoc();
$stmt->close();

if ($appointment['status'] != 'Pending') {
    echo "<script>alert('Only pending appointments can be edited.'); window.location='appointment_list.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    $pet_name = $_POST['pet_name'];
    $pet_type = $_POST['pet_type'];
    $pet_age = $_POST['pet_age'];
    $pet_gender = $_POST['pet_gender'];
    $service = $_POST['service'];
    $appointment_date = $_POST['appointment_date'];

    $update = $conn->prepare("UPDATE appointments SET pet_name=?, pet_type=?, pet_age=?, pet_gender=?, service=?, appointment_date=? WHERE id=? AND user_id=? AND status='Pending'");
    $update->bind_param("ssisssii", $pet_name, $pet_type, $pet_age, $pet_gender, $service, $appointment_date, $appointment_id, $user_id);

    if ($update->execute()) {
        echo "<script>alert('Appointment updated successfully!'); window.location='appointment_list.php';</script>";
    } else {
        echo "<script>alert('Error updating appointment.');</script>";
    }

    $update->close();
    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Appointment - PAWthway</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(180deg, #e8f5e9 0%, #fff 100%);
  color: #2e7d32;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
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

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  backdrop-filter: blur(5px);
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: fadeIn 0.3s ease-in-out;
}

.modal-container {
  background: #fff;
  width: 90%;
  max-width: 550px;
  border-radius: 20px;
  padding: 30px 40px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  animation: popUp 0.3s ease-in-out;
  position: relative;
}

.modal-container h2 {
  color: #388e3c;
  text-align: center;
  margin-bottom: 25px;
}

form label {
  font-weight: 500;
  color: #2e7d32;
}

form input, form select {
  width: 100%;
  padding: 12px 15px;
  border-radius: 10px;
  border: 1px solid #ccc;
  margin-bottom: 15px;
  font-size: 15px;
  box-sizing: border-box;
}

form input:focus, form select:focus {
  outline: none;
  border-color: #4CAF50;
  box-shadow: 0 0 5px rgba(76,175,80,0.3);
}

.btn {
  background: #4CAF50;
  color: white;
  padding: 12px 15px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 500;
  width: 100%;
  transition: 0.3s;
  font-size: 16px;
}

.btn:hover {
  background: #43a047;
  transform: translateY(-2px);
}

.back-btn {
  background: #ccc;
  color: #333;
  text-align: center;
  padding: 12px;
  border-radius: 10px;
  text-decoration: none;
  display: block;
  margin-top: 10px;
}

.back-btn:hover {
  background: #bdbdbd;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes popUp {
  from { transform: scale(0.9); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

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

<div class="modal-overlay">
  <div class="modal-container">
    <h2>Edit Appointment</h2>
    <form method="POST">
      <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment_id); ?>">

      <label>Pet Name:</label>
      <input type="text" name="pet_name" value="<?= htmlspecialchars($appointment['pet_name']); ?>" required>

      <label>Pet Type:</label>
      <input type="text" name="pet_type" value="<?= htmlspecialchars($appointment['pet_type']); ?>" required>

      <label>Pet Age:</label>
      <input type="number" name="pet_age" value="<?= htmlspecialchars($appointment['pet_age']); ?>" required>

      <label>Pet Gender:</label>
      <select name="pet_gender" required>
        <option value="Male" <?= $appointment['pet_gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
        <option value="Female" <?= $appointment['pet_gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
      </select>

      <label>Service:</label>
      <select name="service" required>
        <option value="Check-up" <?= $appointment['service'] == 'Check-up' ? 'selected' : ''; ?>>Check-up</option>
        <option value="Vaccination" <?= $appointment['service'] == 'Vaccination' ? 'selected' : ''; ?>>Vaccination</option>
        <option value="Surgery" <?= $appointment['service'] == 'Surgery' ? 'selected' : ''; ?>>Surgery</option>
      </select>

      <label>Appointment Date & Time:</label>
      <input type="datetime-local" name="appointment_date" value="<?= date('Y-m-d\TH:i', strtotime($appointment['appointment_date'])); ?>" required>

      <button type="submit" name="update_appointment" class="btn">Update Appointment</button>
      <a href="appointment_list.php" class="back-btn">Cancel</a>
    </form>
  </div>
</div>

<footer>
&copy; <?= date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>
