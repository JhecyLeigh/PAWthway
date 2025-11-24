<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PAWthway | Home</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      background: linear-gradient(180deg, #e8f5e9 0%, #ffffff 100%);
      font-family: 'Poppins', sans-serif;
      color: #2e7d32;
      overflow: hidden;
      position: relative;
    }

    .home-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      gap: 0px; 
    }

    .logo img {
      width: 650px;
      height: auto;
      margin-bottom: 10px;
      transition: transform 0.3s ease;
    }

    .logo img:hover {
      transform: scale(1.05);
    }

    .buttons {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px; 
    }

    .btn {
      background: #4CAF50;
      color: #fff;
      padding: 12px 25px;
      text-decoration: none;
      border-radius: 8px;
      width: 220px;
      text-align: center;
      font-size: 16px;
      font-weight: 500;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
    }

    .btn:hover {
      background: #43a047;
      transform: translateY(-3px);
    }

    footer {
      position: absolute;
      bottom: 15px;
      text-align: center;
      width: 100%;
      color: #388e3c;
      z-index: 1;
    }

    footer a {
      color: #2e7d32;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    footer a:hover {
      color: #1b5e20;
      text-decoration: underline;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 20;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.4);
      animation: fadeInBg 0.3s ease-in-out;
    }

    .modal-content {
      background: #fff;
      color: #2e7d32;
      margin: 10% auto;
      padding: 25px 30px;
      border-radius: 15px;
      width: 85%;
      max-width: 420px;
      text-align: center;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      animation: floatUp 0.35s ease-in-out;
    }

    .modal-content h2 {
      margin-top: 0;
      color: #388e3c;
    }

    .modal-content p {
      line-height: 1.6;
    }

    .close {
      color: #666;
      float: right;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover {
      color: #000;
    }

    @keyframes fadeInBg {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes floatUp {
      from { transform: translateY(40px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
  </style>
</head>
<body>

  <div class="home-box">
    <div class="logo">
      <img src="assets/img/logo.png" alt="PAWthway Logo">
    </div>

    <div class="buttons">
      <a href="pages/login.php" class="btn">Login</a>
      <a href="pages/register.php" class="btn">Register</a>
    </div>
  </div>

    <footer>
    &copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>
</body>
</html>
