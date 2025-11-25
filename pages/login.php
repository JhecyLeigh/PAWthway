<?php
session_start();
include('../config/db.php');

if (isset($_POST['login'])) {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND password='$password'");
  $user = mysqli_fetch_assoc($query);

  if ($user) {
    $_SESSION['user'] = $user;
    header("Location: dashboard.php");
    exit;
  } else {
    $error = "Invalid email or password.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - PAWthway</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(180deg, #e8f5e9 0%, #ffffff 100%);
      color: #2e7d32;
    }

    .form-container {
      background: #ffffff;
      padding: 10px 30px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      text-align: center;
      width: 90%;
      max-width: 400px;
      animation: fadeIn 0.4s ease;
    }

    .form-container h3 {
      margin-bottom: 10px;
      font-size: 20px;
      text-align: left;
      color: #388e3c;
    }

    form input {
      width: 100%;
      padding: 12px 0px;
      margin: 10px 0;
      border: 1px solid #c8e6c9;
      border-radius: 8px;
      font-size: 16px;
      outline: none;
      transition: border-color 0.3s ease;
    }

    form input:focus {
      border-color: #66bb6a;
      box-shadow: 0 0 5px rgba(102, 187, 106, 0.3);
    }

    button {
      background: #4CAF50;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 12px 0;
      width: 100%;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      margin-top: 15px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
    }

    button:hover {
      background: #43a047;
      transform: translateY(-2px);
    }

    p {
      margin-top: 15px;
      font-size: 14px;
      color: #388e3c;
    }

    p a {
      color: #2e7d32;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    p a:hover {
      text-decoration: underline;
      color: #1b5e20;
    }

    .error {
      color: #d32f2f;
      background: #ffebee;
      padding: 10px;
      border-radius: 8px;
      margin-top: 15px;
      font-size: 14px;
    }

    .logo {
      text-align: center;
      margin-bottom: 5px;
    }

    .logo img {
      width: 300px;
      height: auto;

    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="form-container">
    <div class="logo">
      <img src="../assets/img/logo.png" alt="PAWthway Logo">
    </div>
    <h3>LOGIN</h3>
    <form method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="login">Login</button>
      <p>Don't have an account? <a href="register.php">Register</a></p>
      <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    </form>
  </div>
</body>
</html>
