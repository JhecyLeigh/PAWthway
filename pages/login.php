<?php
session_start();
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if ($email === '' || $password === '') {
    $error = "Please enter both email and password.";
  } else {
    // First, try to find user in users table
    $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if ($user && $password === $user['password']) {
      // User found in users table (client)
      $_SESSION['user'] = ['id'=>$user['id'],'username'=>$user['username'],'email'=>$user['email']];
      header("Location: dashboard.php");
      exit;
    }

    // If not found in users table, try clinic_users table
    $stmt = $conn->prepare("SELECT id, clinic_id, email, password, name FROM clinic_users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $clinic = $res->fetch_assoc();
    $stmt->close();

    if ($clinic && $password === $clinic['password']) {
      // User found in clinic_users table (clinic staff)
      $_SESSION['clinic_user_id'] = $clinic['id'];
      $_SESSION['clinic_id'] = $clinic['clinic_id'];
      $_SESSION['clinic_name'] = $clinic['name'];
      header("Location: clinic_appointments.php");
      exit;
    }

    // If not found in either table, show error
    $error = "Invalid email or password.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - PAWthway</title>

  <!-- Try both filenames (one will work). Keep these links as-is if they already worked before -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/styles.css">

  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- IMPORTANT INLINE OVERRIDES: these clamp logo & force centered card even if main CSS fails -->
  <style>
    /* Reset any stray rules that make the logo huge */
    header nav .logo img,
    .logo img {
      max-width: 240px !important;
      width: auto !important;
      height: auto !important;
      display: block !important;
    }

    /* Ensure nav doesn't push content off-screen */
    header nav { box-sizing: border-box; padding: 12px 20px; }

    /* Center the form and keep card a fixed width */
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(180deg, #e8f5e9 0%, #ffffff 100%);
      color: #2e7d32;
      min-height: 100vh;
    }

    .page-wrap { display:flex; align-items:center; justify-content:center; min-height: calc(100vh - 80px); padding: 40px 20px; box-sizing:border-box; }

    .form-container {
      background: #ffffff;
      padding: 22px 30px;
      border-radius: 18px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
      text-align: center;
      width: 420px;
      max-width: 96%;
      animation: fadeIn 0.35s ease;
      margin: 0 auto;
    }

    .form-container .logo { margin-bottom: 6px; }
    .form-container h3 { margin-bottom: 8px; color: #388e3c; text-align:left; font-size:20px; }
    .form-container input { width:100%; padding:12px 14px; margin:10px 0; border:1px solid #c8e6c9; border-radius:8px; box-sizing:border-box; }
    .form-container button { width:100%; padding:12px; border-radius:8px; border:none; background:#4CAF50; color:#fff; font-weight:500; cursor:pointer; box-shadow:0 4px 10px rgba(76,175,80,0.2); }

    .role-row { display:flex; gap:18px; justify-content:center; margin-bottom:6px; }
    .role-row label { color:#388e3c; font-size:14px; display:flex; align-items:center; gap:6px; }
    .muted {font-size:14px; color:#388e3c; margin-top:12px;}
    .error { color:#d32f2f; background:#ffebee; padding:10px; border-radius:8px; margin-top:10px; font-size:14px; }

    /* Prevent horizontal scroll caused by oversized elements */
    html, body { overflow-x: hidden; }

    @media screen and (max-width:480px) {
      .form-container{ width:94% }
      header nav .logo img { max-width:160px !important; }
    }
  </style>
</head>
<body>
  <!-- inline header (simple) -->
  <header>
    <nav>
      <div class="logo" style="display:flex;align-items:center;">
        <a href="../index.php" style="display:flex;align-items:center;text-decoration:none;color:inherit;">
          <img src="../assets/img/logo.png" alt="PAWthway Logo" style="max-width:240px">
        </a>
      </div>
      <ul style="list-style:none;display:flex;gap:18px;margin:0;padding:0;align-items:center;">
        <li><a href="../index.php" style="color:white;text-decoration:none;">Home</a></li>
        <li><a href="clinics.php" style="color:white;text-decoration:none;">Clinics</a></li>
        <li><a href="appointment_list.php" style="color:white;text-decoration:none;">My Appointments</a></li>
      </ul>
    </nav>
  </header>

  <div class="page-wrap">
    <div class="form-container">
      <div class="logo">
        <img src="../assets/img/logo.png" alt="PAWthway Logo" style="max-width:220px;">
      </div>

      <h3>LOGIN</h3>

      <form method="POST" novalidate>
        <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Password" required>

        <button type="submit" name="login">Login</button>

        <p class="muted">Don't have an account? <a href="register.php">Register</a></p>

        <?php if (isset($error)): ?>
          <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <footer style="text-align:center;padding:16px;background:#e8f5e9;color:#388e3c;">
    &copy; <?= date('Y') ?> PAWthway. All Rights Reserved.
  </footer>
</body>
</html>
