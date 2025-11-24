<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Manila'); // Local time
$user = $_SESSION['user'];

$clinicName = isset($_GET['clinic']) ? $_GET['clinic'] : '';
$clinic = null;

if ($clinicName) {
    $stmt = $conn->prepare("SELECT * FROM clinics WHERE name = ?");
    $stmt->bind_param("s", $clinicName);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $clinic = $result->fetch_assoc();
        $clinic['services'] = json_decode($clinic['services'], true);

        // Check if clinic is open now
        if ($clinic['opening_time'] === "00:00:00" && $clinic['closing_time'] === "23:59:59") {
            $clinic['open_now'] = true; // 24 hours
        } else {
            $currentTime = new DateTime('now');
            $opening = DateTime::createFromFormat('H:i:s', $clinic['opening_time']);
            $closing = DateTime::createFromFormat('H:i:s', $clinic['closing_time']);
            $clinic['open_now'] = ($currentTime >= $opening && $currentTime <= $closing);
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Book Appointment - PAWthway</title>
<link rel="stylesheet" href="../assets/css/styles.css">
<style>
body { margin:0; font-family:'Poppins',sans-serif; background:linear-gradient(180deg,#e8f5e9 0%,#fff 100%); color:#2e7d32; min-height:100vh; display:flex; flex-direction:column;}
nav { background:#4CAF50; color:white; display:flex; justify-content:space-between; align-items:center; padding:15px 40px; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
nav .logo { display:flex; align-items:center;}
nav .logo img { width:50px; margin-right:10px;}
nav .logo span { font-weight:600; font-size:22px;}
nav ul { list-style:none; display:flex; gap:20px; margin:0; padding:0;}
nav ul li a { color:white; text-decoration:none; font-weight:500; transition:opacity 0.3s;}
nav ul li a:hover { opacity:0.8;}
.appointment-form { max-width:600px; margin:50px auto; background:white; padding:40px 35px; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.1);}
.appointment-form h2 { color:#388e3c; margin-top:0; text-align:center;}
.appointment-form label { display:block; margin:10px 0 5px; font-weight:500;}
.appointment-form input, .appointment-form select, .appointment-form textarea { width:100%; padding:12px 15px; border-radius:10px; border:1px solid #ccc; margin-bottom:15px; font-size:16px; box-sizing:border-box;}
.appointment-form input:focus, .appointment-form select:focus { border-color:#4CAF50; outline:none; box-shadow:0 0 5px rgba(76,175,80,0.3);}
.btn { background:#4CAF50; color:white; padding:12px 15px; border:none; border-radius:10px; text-decoration:none; font-weight:500; display:inline-block; width:100%; text-align:center; transition:background 0.3s ease, transform 0.2s; box-shadow:0 4px 10px rgba(76,175,80,0.3); cursor:pointer; font-size:16px;}
.btn:hover { background:#43a047; transform:translateY(-2px);}
footer { text-align:center; padding:15px; background:#e8f5e9; color:#388e3c; font-size:14px; margin-top:auto;}
.disabled { opacity:0.6; pointer-events:none; }
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

<div class="appointment-form">
<?php if($clinic): ?>
  <h2>Book Appointment at <?php echo htmlspecialchars($clinic['name']); ?></h2>

  <?php if (!$clinic['open_now']): ?>
    <p style="color:red; font-weight:bold;">This clinic is currently closed. You cannot book an appointment now.</p>
  <?php endif; ?>

  <form method="POST" action="submit_appointment.php" <?php if(!$clinic['open_now']) echo 'class="disabled"'; ?>>
    <!-- IMPORTANT: submit the clinic id so appointments get linked properly -->
    <input type="hidden" name="clinic_id" value="<?php echo (int)$clinic['id']; ?>">
    <input type="hidden" name="clinic_name" value="<?php echo htmlspecialchars($clinic['name']); ?>">

    <h3>Pet Information</h3> 

    <label for="pet_name">Pet Name</label>
    <input type="text" name="pet_name" id="pet_name" required <?php if(!$clinic['open_now']) echo 'disabled'; ?>>

    <label for="pet_type">Pet Type</label>
    <input type="text" name="pet_type" id="pet_type" required <?php if(!$clinic['open_now']) echo 'disabled'; ?>>

    <label for="pet_age">Pet Age</label>
    <input type="number" name="pet_age" id="pet_age" min="0" placeholder="e.g., 3" required <?php if(!$clinic['open_now']) echo 'disabled'; ?>>

    <label for="pet_gender">Pet Gender</label>
    <select name="pet_gender" id="pet_gender" required <?php if(!$clinic['open_now']) echo 'disabled'; ?>>
      <option value="">-- Select Gender --</option>
      <option value="Male">Male</option>
      <option value="Female">Female</option>
    </select>

    <label for="service">Select Service</label>
    <select name="service" id="service" required <?php if(!$clinic['open_now']) echo 'disabled'; ?>>
      <?php foreach((array)$clinic['services'] as $service): ?>
        <option value="<?php echo htmlspecialchars($service); ?>"><?php echo htmlspecialchars($service); ?></option>
      <?php endforeach; ?>
    </select>

    <label for="appointment_date">Select Date & Time</label>
    <input type="datetime-local" name="appointment_date" id="appointment_date" required
      min="<?php echo date('Y-m-d\TH:i'); ?>" 
      <?php 
        if($clinic['opening_time'] !== "00:00:00" || $clinic['closing_time'] !== "23:59:59") {
          echo 'step="900"'; // 15-min increments
        }
        if(!$clinic['open_now']) echo 'disabled'; 
      ?>
    >

    <button type="submit" class="btn" <?php if(!$clinic['open_now']) echo 'disabled'; ?>>Submit Appointment</button>
  </form>
<?php else: ?>
  <p>Please select a clinic from the <a href="clinics.php">Clinics page</a>.</p>
<?php endif; ?>
</div>

<footer>
&copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>
