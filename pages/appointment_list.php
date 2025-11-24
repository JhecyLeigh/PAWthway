<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// Handle user cancellation (only allowed for Pending)
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $appointment_id, $user_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Appointment cancelled successfully!'); window.location='appointment_list.php';</script>";
    exit;
}

// Fetch appointments grouped by status
$pending_sql = "SELECT * FROM appointments WHERE user_id = ? AND status IN ('Pending','Cancelled') ORDER BY appointment_date DESC";
$confirmed_sql = "SELECT * FROM appointments WHERE user_id = ? AND status = 'Confirmed' ORDER BY appointment_date DESC";
$completed_sql = "SELECT * FROM appointments WHERE user_id = ? AND status = 'Completed' ORDER BY appointment_date DESC";

// Pending & Cancelled
$stmt1 = $conn->prepare($pending_sql);
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$pending_result = $stmt1->get_result();
$pending_appointments = $pending_result->fetch_all(MYSQLI_ASSOC);
$stmt1->close();

// Confirmed
$stmt2 = $conn->prepare($confirmed_sql);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$confirmed_result = $stmt2->get_result();
$confirmed_appointments = $confirmed_result->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Completed
$stmt3 = $conn->prepare($completed_sql);
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$completed_result = $stmt3->get_result();
$completed_appointments = $completed_result->fetch_all(MYSQLI_ASSOC);
$stmt3->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Appointments - PAWthway</title>
<link rel="stylesheet" href="../assets/css/styles.css">
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

nav {
  background: #4CAF50;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 40px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

nav .logo { display: flex; align-items: center; }
nav .logo img { width: 50px; margin-right: 10px; }
nav .logo span { font-weight: 600; font-size: 22px; }

nav ul { list-style: none; display: flex; gap: 20px; margin: 0; padding: 0; }
nav ul li a { color: white; text-decoration: none; font-weight: 500; transition: opacity 0.3s; }
nav ul li a:hover { opacity: 0.8; }

.container {
  max-width: 950px;
  margin: 50px auto;
  background: white;
  padding: 30px;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.container h2 { color: #388e3c; text-align: center; margin-bottom: 20px; }

.table-container { overflow-x: auto; margin-bottom: 30px; }

table { width: 100%; border-collapse: collapse; font-size: 15px; }
th, td { padding: 12px 15px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: middle; }
th { background: #a5d6a7; color: #2e7d32; }

.status { font-weight: bold; text-transform: capitalize; }
.status.Pending { color: #f57f17; }
.status.Confirmed { color: #2e7d32; }
.status.Cancelled { color: #d32f2f; }
.status.Completed { color: #1976d2; }

.action-btns { display: flex; gap: 8px; }
.btn { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: 0.3s; }
.edit-btn { background: #4CAF50; color: white; }
.cancel-btn { background: #d32f2f; color: white; }
.edit-btn:hover { background: #43a047; } .cancel-btn:hover { background: #b71c1c; }

.no-appointments { text-align: center; padding: 30px; color: #555; font-size: 16px; }

footer { text-align: center; padding: 15px; background: #e8f5e9; color: #388e3c; font-size: 14px; margin-top: auto; }
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

<div class="container">
  <h2>My Appointments</h2>

  <!-- Pending & Cancelled -->
  <h3 style="color:#2e7d32;">Pending & Cancelled</h3>
  <?php if (count($pending_appointments) > 0): ?>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Clinic</th>
            <th>Pet Name</th>
            <th>Type</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Service</th>
            <th>Date & Time</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending_appointments as $app): ?>
          <tr>
            <td><?= htmlspecialchars($app['clinic_name']); ?></td>
            <td><?= htmlspecialchars($app['pet_name']); ?></td>
            <td><?= htmlspecialchars($app['pet_type']); ?></td>
            <td><?= htmlspecialchars($app['pet_age']); ?></td>
            <td><?= htmlspecialchars($app['pet_gender']); ?></td>
            <td><?= htmlspecialchars($app['service']); ?></td>
            <td><?= date("M d, Y h:i A", strtotime($app['appointment_date'])); ?></td>
            <td class="status <?= htmlspecialchars($app['status']); ?>"><?= htmlspecialchars($app['status']); ?></td>
            <td>
              <div class="action-btns">
                <?php if ($app['status'] == 'Pending'): ?>
                  <form method="POST" action="edit_appointment.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?= $app['id']; ?>">
                    <button type="submit" class="btn edit-btn">Edit</button>
                  </form>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?= $app['id']; ?>">
                    <button type="submit" name="cancel_appointment" class="btn cancel-btn" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</button>
                  </form>
                <?php else: ?>
                  <em>No actions</em>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="no-appointments">No pending or cancelled appointments.</p>
  <?php endif; ?>

  <!-- Confirmed -->
  <h3 style="color:#2e7d32;">Confirmed Appointments</h3>
  <?php if (count($confirmed_appointments) > 0): ?>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Clinic</th>
            <th>Pet Name</th>
            <th>Type</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Service</th>
            <th>Date & Time</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($confirmed_appointments as $app): ?>
          <tr>
            <td><?= htmlspecialchars($app['clinic_name']); ?></td>
            <td><?= htmlspecialchars($app['pet_name']); ?></td>
            <td><?= htmlspecialchars($app['pet_type']); ?></td>
            <td><?= htmlspecialchars($app['pet_age']); ?></td>
            <td><?= htmlspecialchars($app['pet_gender']); ?></td>
            <td><?= htmlspecialchars($app['service']); ?></td>
            <td><?= date("M d, Y h:i A", strtotime($app['appointment_date'])); ?></td>
            <td class="status <?= htmlspecialchars($app['status']); ?>"><?= htmlspecialchars($app['status']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="no-appointments">No confirmed appointments.</p>
  <?php endif; ?>

  <!-- Completed -->
  <h3 style="color:#2e7d32;">Completed Appointments</h3>
  <?php if (count($completed_appointments) > 0): ?>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Clinic</th>
            <th>Pet Name</th>
            <th>Type</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Service</th>
            <th>Date & Time</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($completed_appointments as $app): ?>
          <tr>
            <td><?= htmlspecialchars($app['clinic_name']); ?></td>
            <td><?= htmlspecialchars($app['pet_name']); ?></td>
            <td><?= htmlspecialchars($app['pet_type']); ?></td>
            <td><?= htmlspecialchars($app['pet_age']); ?></td>
            <td><?= htmlspecialchars($app['pet_gender']); ?></td>
            <td><?= htmlspecialchars($app['service']); ?></td>
            <td><?= date("M d, Y h:i A", strtotime($app['appointment_date'])); ?></td>
            <td class="status <?= htmlspecialchars($app['status']); ?>"><?= htmlspecialchars($app['status']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="no-appointments">No completed appointments yet.</p>
  <?php endif; ?>

</div>

<footer>
&copy; <?= date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>
