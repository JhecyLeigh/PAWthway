<?php
session_start();
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_name = $_POST['clinic_name'];

    $stmt = $conn->prepare("SELECT * FROM clinics WHERE name = ?");
    $stmt->bind_param("s", $clinic_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $clinic = $result->fetch_assoc();
    $stmt->close();

    if ($clinic) {
        $_SESSION['clinic_name'] = $clinic_name;
        header("Location: clinic_appointments.php");
        exit;
    } else {
        $error = "Clinic not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <link rel="icon" type="image/png" href="../assets/img/logo.png">
<title>Clinic Login - PAWthway</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body {
  display: flex; align-items: center; justify-content: center;
  height: 100vh; background: #e8f5e9; font-family: 'Poppins', sans-serif;
}
form {
  background: white; padding: 30px; border-radius: 15px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 320px;
}
input, button {
  width: 100%; padding: 10px; margin: 10px 0; border-radius: 8px;
  border: 1px solid #ccc; font-size: 14px;
}
button {
  background: #4CAF50; color: white; border: none;
  cursor: pointer; font-weight: 500;
}
</style>
</head>
<body>
<form method="POST">
  <h2>Clinic Login</h2>
  <input type="text" name="clinic_name" placeholder="Clinic Name" required>
  <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <button type="submit">Login</button>
</form>
</body>
</html>
