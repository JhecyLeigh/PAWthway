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

// Get clinic information
$clinic_stmt = $conn->prepare("SELECT * FROM clinics WHERE name = ?");
$clinic_stmt->bind_param("s", $appointment['clinic_name']);
$clinic_stmt->execute();
$clinic_result = $clinic_stmt->get_result();
$clinic = $clinic_result->fetch_assoc();
$clinic['services'] = json_decode($clinic['services'], true);
$clinic_stmt->close();

if (strtolower($appointment['status']) != 'pending') {
    echo "<script>alert('Only pending appointments can be edited.'); window.location='appointment_list.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    $pet_name = $_POST['pet_name'];
    $pet_type = $_POST['pet_type'];
    $pet_age = $_POST['pet_age'];
    $age_unit = $_POST['age_unit'];
    $pet_gender = $_POST['pet_gender'];
    $service = $_POST['service'];
    $appointment_date = $_POST['appointment_date'];

    $update = $conn->prepare("UPDATE appointments SET pet_name=?, pet_type=?, pet_age=?, age_unit=?, pet_gender=?, service=?, appointment_date=? WHERE id=? AND user_id=? AND status='pending'");
    $update->bind_param("ssissssii", $pet_name, $pet_type, $pet_age, $age_unit, $pet_gender, $service, $appointment_date, $appointment_id, $user_id);

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
<link rel="icon" type="image/png" href="../assets/img/logo.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Appointment - PAWthway</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body { 
    margin:0; 
    font-family:'Poppins',sans-serif; 
    background:linear-gradient(180deg,#e8f5e9 0%,#fff 100%); 
    color:#2e7d32; 
    min-height:100vh; 
    display:flex; 
    flex-direction:column;
}
nav { 
    background:#4CAF50; 
    color:white; 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    padding:15px 40px; 
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
nav .logo { 
    display:flex; 
    align-items:center;
}
nav .logo img { 
    width:50px; 
    margin-right:10px;
}
nav .logo span { 
    font-weight:600; 
    font-size:22px;
}
nav ul { 
    list-style:none; 
    display:flex; 
    gap:20px; 
    margin:0; 
    padding:0;
}
nav ul li a { 
    color:white; 
    text-decoration:none; 
    font-weight:500; 
    transition:opacity 0.3s;
}
nav ul li a:hover { 
    opacity:0.8;
}
.appointment-form { 
    max-width:600px; 
    margin:50px auto; 
    background:white; 
    padding:40px 35px; 
    border-radius:20px; 
    box-shadow:0 10px 25px rgba(0,0,0,0.1);
}
.appointment-form h2 { 
    color:#388e3c; 
    margin-top:0; 
    text-align:center;
}
.appointment-form label { 
    display:block; 
    margin:15px 0 5px; 
    font-weight:500;
}
.appointment-form label.required::after { 
    content: " *"; 
    color: red; 
}
.appointment-form input, .appointment-form select { 
    width:100%; 
    padding:12px 15px; 
    border-radius:10px; 
    border:1px solid #ccc; 
    margin-bottom:15px; 
    font-size:16px; 
    box-sizing:border-box; 
    transition: all 0.3s ease; 
}
.appointment-form input:focus, .appointment-form select:focus { 
    border-color:#4CAF50; 
    outline:none; 
    box-shadow:0 0 5px rgba(76,175,80,0.3);
}
.age-container { 
    display: flex; 
    gap: 10px; 
}
.age-container input { 
    flex: 2; 
}
.age-container select { 
    flex: 1; 
}
.btn { 
    background:#4CAF50; 
    color:white; 
    padding:12px 15px; 
    border:none; 
    border-radius:10px; 
    text-decoration:none; 
    font-weight:500; 
    display:inline-block; 
    width:100%; 
    text-align:center; 
    transition:background 0.3s ease, transform 0.2s; 
    box-shadow:0 4px 10px rgba(76,175,80,0.3); 
    cursor:pointer; 
    font-size:16px;
    margin-top: 10px;
}
.btn:hover { 
    background:#43a047; 
    transform:translateY(-2px);
}
.btn-cancel { 
    background:#757575; 
    margin-top: 5px; 
}
.btn-cancel:hover { 
    background:#616161; 
}
footer { 
    text-align:center; 
    padding:15px; 
    background:#e8f5e9; 
    color:#388e3c; 
    font-size:14px; 
    margin-top:auto;
}
.current-appointment {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid #4CAF50;
}
.current-appointment h3 {
    color: #388e3c;
    margin-top: 0;
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
    <li><a href="reviews.php">My Reviews</a></li>
  </ul>
</nav>

<div class="appointment-form">
  <h2>Edit Appointment at <?php echo htmlspecialchars($clinic['name']); ?></h2>
  
  <div class="current-appointment">
    <h3>Current Appointment Details</h3>
    <p><strong>Pet:</strong> <?php echo htmlspecialchars($appointment['pet_name']); ?> (<?php echo htmlspecialchars($appointment['pet_type']); ?>)</p>
    <p><strong>Age:</strong> <?php echo htmlspecialchars($appointment['pet_age']); ?> <?php echo htmlspecialchars($appointment['age_unit'] ?? 'years'); ?></p>
    <p><strong>Gender:</strong> <?php echo htmlspecialchars($appointment['pet_gender']); ?></p>
    <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['service']); ?></p>
    <p><strong>Current Date:</strong> <?php echo date("M d, Y h:i A", strtotime($appointment['appointment_date'])); ?></p>
  </div>

  <h3>Update Appointment Details</h3>
  <form method="POST" action="edit_appointment.php">
    <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment_id); ?>">

    <label for="pet_name" class="required">Pet's Name</label>
    <input type="text" name="pet_name" id="pet_name" value="<?= htmlspecialchars($appointment['pet_name']); ?>" required>

    <label for="pet_type" class="required">Animal Type</label>
    <select name="pet_type" id="pet_type" required>
      <option value="">-- Select Animal Type --</option>
      <option value="Dog" <?= $appointment['pet_type'] == 'Dog' ? 'selected' : ''; ?>>Dog</option>
      <option value="Cat" <?= $appointment['pet_type'] == 'Cat' ? 'selected' : ''; ?>>Cat</option>
      <option value="Bird" <?= $appointment['pet_type'] == 'Bird' ? 'selected' : ''; ?>>Bird</option>
      <option value="Rabbit" <?= $appointment['pet_type'] == 'Rabbit' ? 'selected' : ''; ?>>Rabbit</option>
      <option value="Hamster" <?= $appointment['pet_type'] == 'Hamster' ? 'selected' : ''; ?>>Hamster</option>
      <option value="Other" <?= !in_array($appointment['pet_type'], ['Dog', 'Cat', 'Bird', 'Rabbit', 'Hamster']) ? 'selected' : ''; ?>>Other</option>
    </select>

    <label for="pet_age" class="required">Age</label>
    <div class="age-container">
      <input type="number" name="pet_age" id="pet_age" min="0" max="600" 
             value="<?= htmlspecialchars($appointment['pet_age']); ?>" required>
      <select name="age_unit" id="age_unit" required>
        <option value="years" <?= ($appointment['age_unit'] ?? 'years') == 'years' ? 'selected' : ''; ?>>Years</option>
        <option value="months" <?= ($appointment['age_unit'] ?? 'years') == 'months' ? 'selected' : ''; ?>>Months</option>
      </select>
    </div>

    <label for="pet_gender" class="required">Gender</label>
    <select name="pet_gender" id="pet_gender" required>
      <option value="">-- Select Gender --</option>
      <option value="Male" <?= $appointment['pet_gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
      <option value="Female" <?= $appointment['pet_gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
    </select>

    <label for="service" class="required">Select Service</label>
    <select name="service" id="service" required>
      <option value="">-- Select Service --</option>
      <?php foreach($clinic['services'] as $service): ?>
        <option value="<?php echo htmlspecialchars($service); ?>" <?= $appointment['service'] == $service ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($service); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="appointment_date" class="required">Select New Date & Time</label>
    <input type="datetime-local" name="appointment_date" id="appointment_date" 
           value="<?= date('Y-m-d\TH:i', strtotime($appointment['appointment_date'])); ?>"
           min="<?= date('Y-m-d\TH:i'); ?>" required>

    <button type="submit" name="update_appointment" class="btn">Update Appointment</button>
    <a href="appointment_list.php" class="btn btn-cancel" style="text-decoration: none; display: block;">Cancel</a>
  </form>
</div>

<footer>
&copy; <?= date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>