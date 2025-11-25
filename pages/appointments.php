<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Manila');
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
            $clinic['open_now'] = true;
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
  <link rel="icon" type="image/png" href="../assets/img/logo.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Appointment - PAWthway</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body { margin:0; font-family:'Poppins',sans-serif; background:linear-gradient(180deg,#e8f5e9 0%,#fff 100%); color:#2e7d32; min-height:100vh; display:flex; flex-direction:column;}
nav { background:#4CAF50; color:white; display:flex; justify-content:space-between; align-items:center; padding:15px 40px; box-shadow:0 4px 10px rgba(0,0,0,0.1); flex-wrap: wrap; }
nav .logo { display:flex; align-items:center;}
nav .logo img { width:50px; margin-right:10px;}
nav .logo span { font-weight:600; font-size:22px;}
nav ul { list-style:none; display:flex; gap:20px; margin:0; padding:0; flex-wrap: wrap; }
nav ul li a { color:white; text-decoration:none; font-weight:500; transition:opacity 0.3s;}
nav ul li a:hover { opacity:0.8;}
.appointment-form { max-width:600px; margin:50px auto; background:white; padding:40px 35px; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.1);}
.appointment-form h2 { color:#388e3c; margin-top:0; text-align:center;}
.appointment-form label { display:block; margin:15px 0 5px; font-weight:500;}
.appointment-form label.required::after { content: " *"; color: red; }
.appointment-form input, .appointment-form select, .appointment-form textarea { width:100%; padding:12px 15px; border-radius:10px; border:1px solid #ccc; margin-bottom:15px; font-size:16px; box-sizing:border-box; transition: all 0.3s ease; }
.appointment-form input:focus, .appointment-form select:focus { border-color:#4CAF50; outline:none; box-shadow:0 0 5px rgba(76,175,80,0.3);}
.appointment-form input.invalid { border-color: red; background-color: #ffe6e6; }
.error-message { color: red; font-size: 14px; margin-top: -10px; margin-bottom: 10px; display: none; }
.age-container { display: flex; gap: 10px; }
.age-container input { flex: 2; }
.age-container select { flex: 1; }
.btn { background:#4CAF50; color:white; padding:12px 15px; border:none; border-radius:10px; text-decoration:none; font-weight:500; display:inline-block; width:100%; text-align:center; transition:background 0.3s ease, transform 0.2s; box-shadow:0 4px 10px rgba(76,175,80,0.3); cursor:pointer; font-size:16px;}
.btn:hover { background:#43a047; transform:translateY(-2px);}
.btn:disabled { background:#cccccc; cursor:not-allowed; transform:none; }
footer { text-align:center; padding:15px; background:#e8f5e9; color:#388e3c; font-size:14px; margin-top:auto;}
.disabled { opacity:0.6; pointer-events:none; }
.clinic-status { padding: 10px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-weight: bold; }
.clinic-status.open { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #4CAF50; }
.clinic-status.closed { background-color: #ffebee; color: #c62828; border: 1px solid #f44336; }

@media (max-width: 768px) {
    nav { flex-direction: column; gap: 15px; padding: 15px 20px; }
    nav ul { gap: 15px; }
    .appointment-form { margin: 20px; padding: 25px 20px; }
    .age-container { flex-direction: column; }
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
    <li><a href="notifications.php">Notifications</a></li>
    <li><a href="profile.php">Profile</a></li>
    <li><a href="logout.php">Logout</a></li>
    <li><a href="reviews.php">My Reviews</a></li>
  </ul>
</nav>

<div class="appointment-form">
<?php if($clinic): ?>
  <h2>Book Appointment at <?php echo htmlspecialchars($clinic['name']); ?></h2>

  <div class="clinic-status <?php echo $clinic['open_now'] ? 'open' : 'closed'; ?>">
    <?php echo $clinic['open_now'] ? 'Clinic is currently OPEN' : 'Clinic is currently CLOSED'; ?>
  </div>

  <form method="POST" action="submit_appointment.php" id="appointmentForm" <?php if(!$clinic['open_now']) echo 'class="disabled"'; ?> onsubmit="return validateForm()">
    <input type="hidden" name="clinic_name" value="<?php echo htmlspecialchars($clinic['name']); ?>">

    <h3>Pet Information</h3> 

    <label for="pet_name" class="required">Pet's Name</label>
    <input type="text" name="pet_name" id="pet_name" required 
           pattern="[A-Za-z\s]+" title="Only letters and spaces allowed"
           oninput="validatePetName(this)" <?php if(!$clinic['open_now']) echo 'disabled'; ?>>
    <div class="error-message" id="petNameError">Please enter a valid pet name (letters only)</div>

    <label for="pet_type" class="required">Animal Type</label>
    <select name="pet_type" id="pet_type" required <?php if(!$clinic['open_now']) echo 'disabled'; ?>>
      <option value="">-- Select Animal Type --</option>
      <option value="Dog">Dog</option>
      <option value="Cat">Cat</option>
      <option value="Bird">Bird</option>
      <option value="Rabbit">Rabbit</option>
      <option value="Hamster">Hamster</option>
      <option value="Guinea Pig">Guinea Pig</option>
      <option value="Other">Other</option>
    </select>
    <input type="text" name="pet_type_other" id="pet_type_other" placeholder="Please specify" 
           style="display:none; margin-top:5px;" <?php if(!$clinic['open_now']) echo 'disabled'; ?>>

    <label for="pet_age" class="required">Age</label>
    <div class="age-container">
      <input type="number" name="pet_age" id="pet_age" min="0" max="50" placeholder="e.g., 3" 
             required oninput="validateAge(this)" <?php if(!$clinic['open_now']) echo 'disabled'; ?>>
      <select name="age_unit" id="age_unit" required <?php if(!$clinic['open_now']) echo 'disabled'; ?>>
        <option value="years">Years</option>
        <option value="months">Months</option>
      </select>
    </div>
    <div class="error-message" id="ageError">Please enter a valid age (0-50 years)</div>

    <label for="pet_gender" class="required">Gender</label>
    <select name="pet_gender" id="pet_gender" required <?php if(!$clinic['open_now']) echo 'disabled'; ?>>
      <option value="">-- Select Gender --</option>
      <option value="Male">Male</option>
      <option value="Female">Female</option>
    </select>

    <label for="service" class="required">Select Service</label>
    <select name="service" id="service" required <?php if(!$clinic['open_now']) echo 'disabled'; ?>>
      <option value="">-- Select Service --</option>
      <?php foreach($clinic['services'] as $service): ?>
        <option value="<?php echo htmlspecialchars($service); ?>"><?php echo htmlspecialchars($service); ?></option>
      <?php endforeach; ?>
    </select>

    <label for="appointment_date" class="required">Select Date & Time</label>
    <input type="datetime-local" name="appointment_date" id="appointment_date" required
      min="<?php echo date('Y-m-d\TH:i'); ?>" 
      onchange="validateAppointmentDate(this)"
      <?php 
        if($clinic['opening_time'] !== "00:00:00" || $clinic['closing_time'] !== "23:59:59") {
          echo 'step="900"';
        }
        if(!$clinic['open_now']) echo 'disabled'; 
      ?>
    >
    <div class="error-message" id="dateError">Please select a future date and time</div>

    <button type="submit" class="btn" <?php if(!$clinic['open_now']) echo 'disabled'; ?>>Submit Appointment</button>
  </form>
<?php else: ?>
  <p>Please select a clinic from the <a href="clinics.php">Clinics page</a>.</p>
<?php endif; ?>
</div>

<footer>
&copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

<script>
// Show "Other" input when "Other" is selected
document.getElementById('pet_type').addEventListener('change', function() {
    const otherInput = document.getElementById('pet_type_other');
    otherInput.style.display = this.value === 'Other' ? 'block' : 'none';
    if (this.value !== 'Other') {
        otherInput.value = '';
    }
});

function validatePetName(input) {
    const error = document.getElementById('petNameError');
    const isValid = /^[A-Za-z\s]+$/.test(input.value);
    input.classList.toggle('invalid', !isValid);
    error.style.display = !isValid && input.value ? 'block' : 'none';
}

function validateAge(input) {
    const error = document.getElementById('ageError');
    const age = parseInt(input.value);
    const isValid = !isNaN(age) && age >= 0 && age <= 50;
    input.classList.toggle('invalid', !isValid);
    error.style.display = !isValid && input.value ? 'block' : 'none';
}

function validateAppointmentDate(input) {
    const error = document.getElementById('dateError');
    const selectedDate = new Date(input.value);
    const now = new Date();
    const isValid = selectedDate > now;
    input.classList.toggle('invalid', !isValid);
    error.style.display = !isValid && input.value ? 'block' : 'none';
}

function validateForm() {
    const petName = document.getElementById('pet_name');
    const age = document.getElementById('pet_age');
    const appointmentDate = document.getElementById('appointment_date');
    
    validatePetName(petName);
    validateAge(age);
    validateAppointmentDate(appointmentDate);
    
    const errors = document.querySelectorAll('.error-message[style*="block"]');
    return errors.length === 0;
}
</script>

</body>
</html>