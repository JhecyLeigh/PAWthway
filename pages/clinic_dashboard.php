<?php
// pages/clinic_dashboard.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['clinic_id'])) {
    header('Location: clinic_login.php');
    exit;
}

$clinic_id = (int)$_SESSION['clinic_id'];
$clinic_name = $_SESSION['clinic_name'] ?? 'Clinic';

// Gather stats
$sql = "SELECT 
  COUNT(*) AS total,
  SUM(status = 'Pending') AS pending,
  SUM(status = 'Confirmed') AS confirmed,
  SUM(status = 'Completed') AS completed,
  SUM(status = 'Cancelled') AS cancelled
FROM appointments WHERE clinic_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $clinic_id);
$stmt->execute();
$res = $stmt->get_result();
$stats = $res->fetch_assoc() ?: ['total'=>0,'pending'=>0,'confirmed'=>0,'completed'=>0,'cancelled'=>0];
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?=htmlspecialchars($clinic_name)?> — PAWthway Clinic Dashboard</title>
  <link rel="stylesheet" href="/pawthway/assets/css/style.css">
  <link rel="stylesheet" href="/pawthway/assets/css/styles.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    .page-card {
      max-width:1100px;
      margin:40px auto;
      padding:28px;
      border-radius:12px;
      background:#fff;
      box-shadow:0 8px 24px rgba(0,0,0,.04)
    }
    .stat-row {
      display:flex;
      gap:18px;
      flex-wrap:wrap;
      margin-top:18px
    }
    .stat {
      flex:1;
      min-width:160px;
      padding:16px;
      border-radius:8px;
      background:#f6fbf6;
      color:#2a7b2f
    }
    .btn {
      display:inline-block;
      padding:10px 14px;
      border-radius:8px;
      background:#4CAF50;
      color:#fff;
      text-decoration:none
    }
  </style>
</head>
<body>

<header>
  <nav>
    <div class="logo">
      <a href="clinic_dashboard.php" style="display:flex;align-items:center;color:white;text-decoration:none">
        <img src="/pawthway/assets/img/logo.png" alt="PAWthway Logo" style="width:46px;margin-right:10px">
        <span style="font-weight:600;font-size:20px;color:white">PAWthway</span>
      </a>
    </div>
    <ul>
      <li><a href="clinic_dashboard.php">Home</a></li>
      <li><a href="clinic_logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main>
  <div class="page-card content">
    <h2>Welcome back, <?=htmlspecialchars($clinic_name)?>!</h2>
    <p>Manage your clinic appointments below.</p>

    <div class="stat-row">
      <div class="stat"><strong>Total</strong><div style="font-size:22px"><?= (int)$stats['total'] ?></div></div>
      <div class="stat"><strong>Pending</strong><div style="font-size:22px"><?= (int)$stats['pending'] ?></div></div>
      <div class="stat"><strong>Confirmed</strong><div style="font-size:22px"><?= (int)$stats['confirmed'] ?></div></div>
      <div class="stat"><strong>Completed</strong><div style="font-size:22px"><?= (int)$stats['completed'] ?></div></div>
    </div>

    <div class="actions" style="margin-top:20px">
      <a class="btn" href="clinic_appointments.php">Manage Appointments</a>
    </div>
  </div>
</main>

<footer>
  <div style="max-width:1100px;margin:0 auto;padding:12px 20px;">
    &copy; <?= date('Y') ?> PAWthway. All Rights Reserved.
  </div>
</footer>

</body>
</html>
