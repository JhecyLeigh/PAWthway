<?php 
include('../config/db.php'); 

function validatePassword($password) {
    return preg_match('/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

function formatUsername($username) {
    return ucfirst(strtolower(trim($username)));
}

$error = '';

if (isset($_POST['register'])) {
    $username = formatUsername($_POST['username']);
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (!validatePassword($password)) {
        $error = "Password must be at least 8 characters, include letters, numbers, and special characters.";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered!";
        } else {
            $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
            if (mysqli_query($conn, $sql)) {
                echo "<script>
                        alert('Registered successfully! Redirecting to login page...');
                        window.location.href='login.php';
                      </script>";
                exit;
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - PAWthway</title>
<link rel="stylesheet" href="../assets/css/style.css">
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
}

.form-container {
    background: #fff;
    padding: 10px 30px;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    width: 400px;
    text-align: center;
}

.form-container h3 {
      margin-bottom: 10px;
      font-size: 20px;
      text-align: left;
      color: #388e3c;
    }

input, select {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 16px;
    box-sizing: border-box;
    display: block;
}

button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: #4CAF50;
    color: #fff;
    font-size: 16px;
    margin-top: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

button:hover {
    background: #43a047;
}

.error {
    color: red;
    margin-top: 15px;
}

.logo {
      text-align: center;
      margin-bottom: 15px;
}

.logo img {
      width: 300px;
      height: auto;
}

p a {
    color: #2e7d32;
    text-decoration: none;
    font-weight: 500;
}

p a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="form-container">
  <div class="logo">
      <img src="../assets/img/logo.png" alt="PAWthway Logo">
  </div>
<h3>REGISTER</h3>
<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit" name="register">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login</a></p>

<?php 
if ($error) echo "<p class='error'>$error</p>";
?>

</div>

</body>
</html>
