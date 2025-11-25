<?php
session_start();
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
  <title>Dashboard - PAWthway</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(180deg, #e8f5e9 0%, #ffffff 100%);
      color: #2e7d32;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .dashboard-container {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding: 50px 20px;
    }

    .dashboard-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      padding: 40px;
      max-width: 900px;
      width: 100%;
      animation: fadeIn 0.4s ease;
    }

    .dashboard-card h2 {
      font-size: 28px;
      color: #388e3c;
      margin-bottom: 20px;
    }

    .dashboard-card p {
      font-size: 16px;
      color: #4b604b;
    }

    .btn {
      background: #4CAF50;
      color: white;
      padding: 10px 25px;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      display: inline-block;
      margin-top: 20px;
      transition: background 0.3s ease;
      box-shadow: 0 4px 10px rgba(76,175,80,0.3);
    }

    .btn:hover {
      background: #43a047;
      transform: translateY(-2px);
    }

    footer {
      text-align: center;
      padding: 15px;
      background: #e8f5e9;
      color: #388e3c;
      font-size: 14px;
      margin-top: auto;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <?php include('../config/navbar.php'); ?>

  <div class="dashboard-container">
    <div class="dashboard-card">
      <h2>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h2>
      <p>
        You’ve successfully logged in to your <strong>PAWthway Dashboard</strong> — your digital path to smarter veterinary care.  
        From here, you can browse nearby clinics, schedule appointments, and manage your pet’s health records.
      </p>

      <a href="clinics.php" class="btn">Explore Clinics</a>
    </div>
  </div>

  <footer>
    &copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
  </footer>
</body>
</html>
