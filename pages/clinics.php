<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Manila'); 

$user = $_SESSION['user'];
$location = isset($_POST['location']) ? $_POST['location'] : '';
$search = isset($_POST['search']) ? $_POST['search'] : '';
$service_filter = isset($_POST['service_filter']) ? $_POST['service_filter'] : '';
$clinics = [];

$query = "SELECT * FROM clinics WHERE 1=1";
$params = [];
$types = "";

if ($location) {
    $query .= " AND location = ?";
    $params[] = $location;
    $types .= "s";
}

if ($search) {
    $query .= " AND (name LIKE ? OR address LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$query .= " ORDER BY name ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$currentTime = new DateTime('now'); 

$services_result = $conn->query("SELECT services FROM clinics");
$all_services = [];
while ($row = $services_result->fetch_assoc()) {
    $clinic_services = json_decode($row['services'], true);
    if (is_array($clinic_services)) {
        foreach ($clinic_services as $service) {
            if (!in_array($service, $all_services)) {
                $all_services[] = $service;
            }
        }
    }
}
sort($all_services);

while ($row = $result->fetch_assoc()) {
    $row['services'] = json_decode($row['services'], true);

    if ($service_filter && (!is_array($row['services']) || !in_array($service_filter, $row['services']))) {
        continue;
    }

    if ($row['opening_time'] === "00:00:00" && $row['closing_time'] === "23:59:59") {
        $row['open_now'] = true; 
    } else {
        $opening = DateTime::createFromFormat('H:i:s', $row['opening_time']);
        $closing = DateTime::createFromFormat('H:i:s', $row['closing_time']);

        $row['open_now'] = ($currentTime >= $opening && $currentTime <= $closing);
    }

    $clinics[] = $row;
}

if (isset($stmt)) {
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { 
    margin: 0; 
    font-family: 'Poppins', sans-serif; 
    background: linear-gradient(180deg, #e8f5e9 0%, #fff 100%); 
    color: #2e7d32; 
    min-height: 100vh; 
    display: flex; 
    flex-direction: column;
}

.filter-section { 
    background: white; 
    padding: 20px; 
    margin: 20px auto; 
    border-radius: 15px; 
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05); 
    max-width: 1200px; 
    width: 95%;
}

.filter-container { 
    display: flex; 
    flex-wrap: wrap; 
    gap: 15px; 
    align-items: end; 
    justify-content: center;
}

.filter-group { 
    display: flex; 
    flex-direction: column; 
    min-width: 180px;
}

.filter-group label { 
    font-weight: 500; 
    margin-bottom: 5px; 
    color: #388e3c;
}

.filter-group select, 
.filter-group input { 
    padding: 10px 15px; 
    border-radius: 8px; 
    border: 1px solid #ccc; 
    font-size: 16px; 
    background: white;
}

.filter-actions { 
    display: flex; 
    gap: 10px;
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
    transition: background 0.3s ease, transform 0.2s; 
    box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3); 
    cursor: pointer;
}

.btn:hover { 
    background: #43a047; 
    transform: translateY(-2px);
}

.btn-outline { 
    background: transparent; 
    border: 1px solid #4CAF50; 
    color: #4CAF50; 
    box-shadow: none;
}

.btn-outline:hover { 
    background: #e8f5e9;
}


.results-header { 
    text-align: center; 
    margin: 10px 0 20px;
}

.results-count { 
    font-size: 18px; 
    color: #4b604b;
}

.dashboard-container { 
    flex: 1; 
    display: flex; 
    justify-content: center; 
    flex-wrap: wrap; 
    gap: 25px; 
    padding: 0 20px 50px; 
    max-width: 1400px; 
    margin: 0 auto;
}

.dashboard-card { 
    background: white; 
    border-radius: 20px; 
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); 
    padding: 20px; 
    width: 300px; 
    cursor: pointer; 
    transition: transform 0.2s, box-shadow 0.2s; 
    text-align: center; 
    position: relative;
}

.dashboard-card:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.dashboard-card img { 
    width: 100%; 
    border-radius: 15px; 
    margin-bottom: 15px; 
    object-fit: cover; 
    height: 180px;
}

.dashboard-card h2 { 
    font-size: 22px; 
    color: #388e3c; 
    margin-bottom: 10px;
}

.dashboard-card p { 
    font-size: 14px; 
    color: #4b604b; 
    margin: 3px 0;
}

.clinic-status { 
    position: absolute; 
    top: 15px; 
    right: 15px; 
    padding: 5px 10px; 
    border-radius: 20px; 
    font-size: 12px; 
    font-weight: bold;
}

.clinic-status.open { 
    background: #e8f5e9; 
    color: green;
}

.clinic-status.closed { 
    background: #ffebee; 
    color: red;
}

.clinic-services { 
    margin-top: 10px; 
    display: flex; 
    flex-wrap: wrap; 
    gap: 5px; 
    justify-content: center;
}

.service-tag { 
    background: #e8f5e9; 
    color: #2e7d32; 
    padding: 3px 8px; 
    border-radius: 12px; 
    font-size: 11px;
}

.modal { 
    display: none; 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%; 
    background-color: rgba(0, 0, 0, 0.6); 
    justify-content: center; 
    align-items: center; 
    z-index: 999; 
    overflow: auto;
}

.modal-content { 
    background: white; 
    padding: 25px; 
    border-radius: 20px; 
    width: 90%; 
    max-width: 700px; 
    position: relative; 
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2); 
    animation: fadeIn 0.4s ease;
}

.modal-content h2 { 
    color: #388e3c; 
    margin-top: 0;
}

.modal-content p, 
.modal-content a { 
    color: #4b604b; 
    margin: 5px 0;
}

.modal-content ul { 
    padding-left: 20px; 
    color: #4b604b;
}

.close { 
    position: absolute; 
    top: 15px; 
    right: 20px; 
    font-size: 24px; 
    font-weight: bold; 
    color: #4b604b; 
    cursor: pointer;
}

.close:hover { 
    color: #388e3c;
}

.open { 
    color: green; 
    font-weight: bold;
}

.closed { 
    color: red; 
    font-weight: bold;
}

@keyframes fadeIn { 
    from { 
        opacity: 0; 
        transform: translateY(30px); 
    } 
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

.no-results { 
    text-align: center; 
    padding: 40px; 
    color: #4b604b;
}

.no-results i { 
    font-size: 50px; 
    margin-bottom: 15px; 
    color: #ccc;
}

footer { 
    text-align: center; 
    padding: 15px; 
    background: #e8f5e9; 
    color: #388e3c; 
    font-size: 14px; 
    margin-top: auto;
}

@media (max-width: 768px) {
    nav { 
        padding: 15px 20px; 
        flex-direction: column; 
        gap: 15px; 
    }
    
    nav ul { 
        gap: 15px; 
    }
    
    .filter-container { 
        flex-direction: column; 
        align-items: stretch; 
    }
    
    .filter-group { 
        min-width: auto; 
    }
    
    .dashboard-card { 
        width: 100%; 
        max-width: 350px; 
    }
}
</style>
</head>
<body>

<?php include('../config/navbar.php'); ?>

<div class="filter-section">
  <form method="POST" id="filterForm">
    <div class="filter-container">
      <div class="filter-group">
        <label for="search"><i class="fas fa-search"></i> Search Clinics</label>
        <input type="text" id="search" name="search" placeholder="Clinic name or address..." value="<?php echo htmlspecialchars($search); ?>">
      </div>
      
      <div class="filter-group">
        <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
        <select id="location" name="location">
          <option value="">-- All Locations --</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?php echo htmlspecialchars($loc); ?>" <?php if ($loc == $location) echo 'selected'; ?>>
              <?php echo htmlspecialchars($loc); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="filter-group">
        <label for="service_filter"><i class="fas fa-stethoscope"></i> Service</label>
        <select id="service_filter" name="service_filter">
          <option value="">-- All Services --</option>
          <?php foreach ($all_services as $service): ?>
            <option value="<?php echo htmlspecialchars($service); ?>" <?php if ($service == $service_filter) echo 'selected'; ?>>
              <?php echo htmlspecialchars($service); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="filter-actions">
        <button type="submit" class="btn"><i class="fas fa-filter"></i> Apply Filters</button>
        <button type="button" id="resetBtn" class="btn btn-outline"><i class="fas fa-redo"></i> Reset</button>
      </div>
    </div>
  </form>
</div>

<div class="results-header">
  <div class="results-count">
    <?php if ($location || $search || $service_filter): ?>
      Found <?php echo count($clinics); ?> clinic<?php echo count($clinics) !== 1 ? 's' : ''; ?>
      <?php if ($location): ?> in <strong><?php echo htmlspecialchars($location); ?></strong><?php endif; ?>
      <?php if ($search): ?> matching "<strong><?php echo htmlspecialchars($search); ?></strong>"<?php endif; ?>
      <?php if ($service_filter): ?> offering <strong><?php echo htmlspecialchars($service_filter); ?></strong><?php endif; ?>
    <?php else: ?>
      Showing All Clinics (<?php echo count($clinics); ?>)
    <?php endif; ?>
  </div>
</div>

<div class="dashboard-container">
  <?php if (count($clinics) === 0): ?>
    <div class="no-results">
      <i class="fas fa-clinic-medical"></i>
      <h3>No clinics found</h3>
      <p>Try adjusting your search filters or browse all clinics.</p>
      <a href="clinics.php" class="btn">View All Clinics</a>
    </div>
  <?php endif; ?>

  <?php foreach ($clinics as $index => $clinic): ?>
    <div class="dashboard-card" onclick="openModal(<?php echo $index; ?>)">
      <img src="../assets/img/<?php echo $clinicImages[$index % count($clinicImages)] ?? 'default_clinic.jpg'; ?>" 
           alt="<?php echo htmlspecialchars($clinic['name']); ?>">
      <div class="clinic-status <?php echo $clinic['open_now'] ? 'open' : 'closed'; ?>">
        <?php echo $clinic['open_now'] ? 'Open Now' : 'Closed'; ?>
      </div>
      <h2><?php echo htmlspecialchars($clinic['name']); ?></h2>
      <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($clinic['address']); ?></p>
      <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($clinic['phone']); ?></p>
      
      <?php if (is_array($clinic['services']) && count($clinic['services']) > 0): ?>
        <div class="clinic-services">
          <?php 
          $displayed_services = array_slice($clinic['services'], 0, 3);
          foreach ($displayed_services as $service): 
          ?>
            <span class="service-tag"><?php echo htmlspecialchars($service); ?></span>
          <?php endforeach; ?>
          <?php if (count($clinic['services']) > 3): ?>
            <span class="service-tag">+<?php echo count($clinic['services']) - 3; ?> more</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<div id="clinicModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2 id="modalName"></h2>
    <p id="modalAddress"><i class="fas fa-map-marker-alt"></i> </p>
    <p id="modalPhone"><i class="fas fa-phone"></i> </p>
    <p id="modalFacebook"><i class="fab fa-facebook"></i> </p>
    <p id="modalHours"><i class="fas fa-clock"></i> </p>
    <p id="modalStatus" class=""></p>
    <h3><i class="fas fa-stethoscope"></i> Services Offered:</h3>
    <ul id="modalServices"></ul>
    <a href="appointments.php" id="bookAppointmentBtn" class="btn"><i class="fas fa-calendar-check"></i> Book Appointment</a>
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
const resetBtn = document.getElementById('resetBtn');

function openModal(index){
  const clinic = clinics[index];
  modalName.textContent = clinic.name;
  modalAddress.innerHTML = "<i class='fas fa-map-marker-alt'></i> " + clinic.address;
  modalPhone.innerHTML = "<i class='fas fa-phone'></i> " + clinic.phone;
  modalFacebook.innerHTML = "<i class='fab fa-facebook'></i> Website/Facebook: <a href='"+clinic.facebook+"' target='_blank'>"+clinic.facebook+"</a>";

  if(clinic.opening_time=="00:00:00" && clinic.closing_time=="23:59:59"){
      modalHours.innerHTML = "<i class='fas fa-clock'></i> Hours: Open 24 Hours";
  } else {
      let options = { hour: 'numeric', minute:'numeric', hour12:true };
      let open = new Date("1970-01-01T"+clinic.opening_time).toLocaleTimeString('en-US', options);
      let close = new Date("1970-01-01T"+clinic.closing_time).toLocaleTimeString('en-US', options);
      modalHours.innerHTML = "<i class='fas fa-clock'></i> Hours: " + open + " - " + close;
  }

  modalStatus.textContent = clinic.open_now ? "Open Now" : "Closed";
  modalStatus.className = clinic.open_now ? "open" : "closed";

  modalServices.innerHTML = "";
  clinic.services.forEach(s=>{
      const li = document.createElement('li');
      li.textContent = s;
      modalServices.appendChild(li);
  });


  bookAppointmentBtn.href = "appointments.php?clinic=" + encodeURIComponent(clinic.name);
  bookAppointmentBtn.style.display = clinic.open_now ? "inline-block" : "none";

  modal.style.display = "flex";
}

function closeModal(){ 
  modal.style.display="none"; 
}

window.onclick = function(event){ 
  if(event.target==modal) closeModal(); 
}

resetBtn.addEventListener('click', function() {
  document.getElementById('search').value = '';
  document.getElementById('location').value = '';
  document.getElementById('service_filter').value = '';
  document.getElementById('filterForm').submit();
});
</script>

<footer>
&copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>