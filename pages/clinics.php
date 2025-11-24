<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Manila'); // Set local timezone

$user = $_SESSION['user'];
$location = isset($_POST['location']) ? $_POST['location'] : '';
$clinics = [];

if ($location) {
    $stmt = $conn->prepare("SELECT * FROM clinics WHERE location = ?");
    $stmt->bind_param("s", $location);
    $stmt->execute();
    $result = $stmt->get_result();

    $currentTime = new DateTime('now'); // Current local time

    while ($row = $result->fetch_assoc()) {
        $row['services'] = json_decode($row['services'], true);

        // Check if clinic is open now
        if ($row['opening_time'] === "00:00:00" && $row['closing_time'] === "23:59:59") {
            $row['open_now'] = true; // 24 hours
        } else {
            $opening = DateTime::createFromFormat('H:i:s', $row['opening_time']);
            $closing = DateTime::createFromFormat('H:i:s', $row['closing_time']);

            $row['open_now'] = ($currentTime >= $opening && $currentTime <= $closing);
        }

        $clinics[] = $row;
    }

    $stmt->close();
}

$locResult = $conn->query("SELECT DISTINCT location FROM clinics");
$locations = [];
while ($row = $locResult->fetch_assoc()) {
    $locations[] = $row['location'];
}
$conn->close();

$clinicImages = [
    'DocJohn.jpg',
    'sec.png',
    'third.jpg'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Clinics - PAWthway</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body { margin:0; font-family:'Poppins',sans-serif; background:linear-gradient(180deg,#e8f5e9 0%,#fff 100%); color:#2e7d32; min-height:100vh; display:flex; flex-direction:column;}
nav { background:#4CAF50; color:white; display:flex; justify-content:space-between; align-items:center; padding:15px 40px; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
nav .logo { display:flex; align-items:center;}
nav .logo img { width:50px; margin-right:10px;}
nav .logo span { font-weight:600; font-size:22px;}
nav ul { list-style:none; display:flex; gap:20px; margin:0; padding:0;}
nav ul li a { color:white; text-decoration:none; font-weight:500; transition:opacity 0.3s;}
nav ul li a:hover { opacity:0.8;}
.location-form { text-align:center; margin:30px 0;}
.location-form select { padding:10px 15px; border-radius:8px; border:1px solid #ccc; font-size:16px;}
.location-form button { margin-left:10px;}
.dashboard-container { flex:1; display:flex; justify-content:center; flex-wrap:wrap; gap:20px; padding:0 20px 50px;}
.dashboard-card { background:white; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.1); padding:20px; width:300px; cursor:pointer; transition:transform 0.2s, box-shadow 0.2s; text-align:center;}
.dashboard-card:hover { transform:translateY(-5px); box-shadow:0 15px 30px rgba(0,0,0,0.15);}
.dashboard-card img { width:100%; border-radius:15px; margin-bottom:15px; object-fit:cover; height:180px;}
.dashboard-card h2 { font-size:22px; color:#388e3c; margin-bottom:10px;}
.dashboard-card p { font-size:14px; color:#4b604b; margin:3px 0;}
.clinic-status.open { color:green; font-weight:bold; }
.clinic-status.closed { color:red; font-weight:bold; }
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:999; overflow:auto;}
.modal-content { background:white; padding:25px; border-radius:20px; width:90%; max-width:700px; position:relative; box-shadow:0 15px 30px rgba(0,0,0,0.2); animation:fadeIn 0.4s ease;}
.modal-content h2 { color:#388e3c; margin-top:0;}
.modal-content p, .modal-content a { color:#4b604b; margin:5px 0;}
.modal-content ul { padding-left:20px; color:#4b604b;}
.close { position:absolute; top:15px; right:20px; font-size:24px; font-weight:bold; color:#4b604b; cursor:pointer;}
.close:hover { color:#388e3c;}
.btn { background:#4CAF50; color:white; padding:10px 25px; border:none; border-radius:8px; text-decoration:none; font-weight:500; display:inline-block; margin-top:15px; transition:background 0.3s ease, transform 0.2s; box-shadow:0 4px 10px rgba(76,175,80,0.3); cursor:pointer;}
.btn:hover { background:#43a047; transform:translateY(-2px);}
.open { color:green; font-weight:bold;}
.closed { color:red; font-weight:bold;}
@keyframes fadeIn { from {opacity:0; transform:translateY(30px);} to {opacity:1; transform:translateY(0);} }
footer { text-align:center; padding:15px; background:#e8f5e9; color:#388e3c; font-size:14px; margin-top:auto;}
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

<div class="location-form">
  <form method="POST">
    <select name="location" required>
      <option value="">-- Select Location --</option>
      <?php foreach ($locations as $loc): ?>
        <option value="<?php echo htmlspecialchars($loc); ?>" <?php if ($loc == $location) echo 'selected'; ?>>
          <?php echo htmlspecialchars($loc); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Search</button>
  </form>
</div>

<div class="dashboard-container">
  <?php if ($location && count($clinics) === 0): ?>
    <p>No clinics found in "<?php echo htmlspecialchars($location); ?>"</p>
  <?php endif; ?>

  <?php foreach ($clinics as $index => $clinic): ?>
    <div class="dashboard-card" onclick="openModal(<?php echo $index; ?>)">
      <img src="../assets/img/<?php echo $clinicImages[$index] ?? 'default_clinic.jpg'; ?>" 
           alt="<?php echo htmlspecialchars($clinic['name']); ?>">
      <h2><?php echo htmlspecialchars($clinic['name']); ?></h2>
      <p><?php echo htmlspecialchars($clinic['address']); ?></p>
      <p><?php echo htmlspecialchars($clinic['phone']); ?></p>
      <p class="clinic-status <?php echo $clinic['open_now'] ? 'open' : 'closed'; ?>">
        <?php echo $clinic['open_now'] ? 'Open Now' : 'Closed'; ?>
      </p>
    </div>
  <?php endforeach; ?>
</div>

<div id="clinicModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2 id="modalName"></h2>
    <p id="modalAddress"></p>
    <p id="modalPhone"></p>
    <p id="modalFacebook"></p>
    <p id="modalHours"></p>
    <p id="modalStatus" class=""></p>
    <h3>Services Offered:</h3>
    <ul id="modalServices"></ul>
    <a href="appointments.php" id="bookAppointmentBtn" class="btn">Book Appointment</a>
  </div>
</div>

<script>
let clinics = <?php echo json_encode($clinics); ?>;
const modal = document.getElementById('clinicModal');
const modalName = document.getElementById('modalName');
const modalAddress = document.getElementById('modalAddress');
const modalPhone = document.getElementById('modalPhone');
const modalFacebook = document.getElementById('modalFacebook');
const modalHours = document.getElementById('modalHours');
const modalStatus = document.getElementById('modalStatus');
const modalServices = document.getElementById('modalServices');
const bookAppointmentBtn = document.getElementById('bookAppointmentBtn');

function openModal(index){
  const clinic = clinics[index];
  modalName.textContent = clinic.name;
  modalAddress.textContent = "Address: " + clinic.address;
  modalPhone.textContent = "Phone: " + clinic.phone;
  modalFacebook.innerHTML = 'Website/Facebook: <a href="'+clinic.facebook+'" target="_blank">'+clinic.facebook+'</a>';

  // Hours in 12-hour format
  if(clinic.opening_time=="00:00:00" && clinic.closing_time=="23:59:59"){
      modalHours.textContent = "Hours: Open 24 Hours";
  } else {
      let options = { hour: 'numeric', minute:'numeric', hour12:true };
      let open = new Date("1970-01-01T"+clinic.opening_time).toLocaleTimeString('en-US', options);
      let close = new Date("1970-01-01T"+clinic.closing_time).toLocaleTimeString('en-US', options);
      modalHours.textContent = "Hours: " + open + " - " + close;
  }

  modalStatus.textContent = clinic.open_now ? "Open Now" : "Closed";
  modalStatus.className = clinic.open_now ? "open" : "closed";

  // Services
  modalServices.innerHTML = "";
  clinic.services.forEach(s=>{
      const li = document.createElement('li');
      li.textContent = s;
      modalServices.appendChild(li);
  });

  // Show booking only if open
  bookAppointmentBtn.href = "appointments.php?clinic=" + encodeURIComponent(clinic.name);
  bookAppointmentBtn.style.display = clinic.open_now ? "inline-block" : "none";

  modal.style.display = "flex";
}

function closeModal(){ modal.style.display="none"; }
window.onclick = function(event){ if(event.target==modal) closeModal(); }
</script>

<footer>
&copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>
