<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['clinic_name'])) {
    header("Location: clinic_login.php");
    exit;
}

$clinic_name = $_SESSION['clinic_name'];

if (isset($_POST['update_status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status = $_POST['status'];

    $check = $conn->prepare("SELECT status FROM appointments WHERE id = ? AND clinic_name = ?");
    $check->bind_param("is", $appointment_id, $clinic_name);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();
    $check->close();

    if ($row) {
        if ($row['status'] == 'Pending' || $row['status'] == 'Confirmed') {

            if ($new_status == 'Confirmed') {
                $final_status = 'Completed';
            } else {
                $final_status = $new_status;
            }

            $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND clinic_name = ?");
            $stmt->bind_param("sis", $final_status, $appointment_id, $clinic_name);
            $stmt->execute();
            $stmt->close();

            echo "<script>alert('Appointment status updated successfully!'); window.location='clinic_appointments.php';</script>";
            exit;
        } else {
            echo "<script>alert('This appointment can no longer be edited.'); window.location='clinic_appointments.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Appointment not found.'); window.location='clinic_appointments.php';</script>";
        exit;
    }
}

$pending_sql = "SELECT * FROM appointments WHERE clinic_name = ? AND (status = 'Pending' OR status = 'Cancelled') ORDER BY appointment_date DESC";
$completed_sql = "SELECT * FROM appointments WHERE clinic_name = ? AND status = 'Completed' ORDER BY appointment_date DESC";

$stmt1 = $conn->prepare($pending_sql);
$stmt1->bind_param("s", $clinic_name);
$stmt1->execute();
$pending_result = $stmt1->get_result();
$pending_appointments = $pending_result->fetch_all(MYSQLI_ASSOC);
$stmt1->close();

$stmt2 = $conn->prepare($completed_sql);
$stmt2->bind_param("s", $clinic_name);
$stmt2->execute();
$completed_result = $stmt2->get_result();
$completed_appointments = $completed_result->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="../assets/img/logo.png">
<meta charset="UTF-8">
<title>Clinic Dashboard - PAWthway</title>
<link rel="stylesheet" href="../assets/css/style.css">
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

.container {
  max-width: 1000px;
  margin: 40px auto;
  background: white;
  padding: 30px;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.container h2 {
  color: #388e3c;
  text-align: center;
  margin-bottom: 20px;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 15px;
  margin-bottom: 40px;
}

th, td {
  padding: 12px 15px;
  border-bottom: 1px solid #ddd;
  text-align: left;
}

th {
  background: #a5d6a7;
  color: #2e7d32;
}

.status {
  font-weight: bold;
  text-transform: capitalize;
}

.status.Pending { color: #f57f17; }
.status.Completed { color: #2e7d32; }
.status.Cancelled { color: #d32f2f; }

form select, form button {
  padding: 6px 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 14px;
}

form button {
  background: #4CAF50;
  color: white;
  border: none;
  cursor: pointer;
  transition: background 0.3s;
}

form button:hover {
  background: #43a047;
}

footer {
  text-align: center;
  padding: 15px;
  background: #e8f5e9;
  color: #388e3c;
  font-size: 14px;
  margin-top: auto;
}
</style>
</head>
<body>

<?php include('../config/navbar.php'); ?>

<div class="container">
  <h2>Pending & Cancelled Appointments</h2>

  <?php if (count($pending_appointments) > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Pet Name</th>
          <th>Type</th>
          <th>Age</th>
          <th>Gender</th>
          <th>Service</th>
          <th>Date & Time</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pending_appointments as $app): ?>
        <tr>
          <td><?= htmlspecialchars($app['pet_name']); ?></td>
          <td><?= htmlspecialchars($app['pet_type']); ?></td>
          <td><?= htmlspecialchars($app['pet_age']); ?></td>
          <td><?= htmlspecialchars($app['pet_gender']); ?></td>
          <td><?= htmlspecialchars($app['service']); ?></td>
          <td><?= date("M d, Y h:i A", strtotime($app['appointment_date'])); ?></td>
          <td class="status <?= htmlspecialchars($app['status']); ?>"><?= htmlspecialchars($app['status']); ?></td>
          <td>
            <?php if ($app['status'] == 'Pending'): ?>
              <form method="POST" style="display:flex; gap:5px;">
                <input type="hidden" name="appointment_id" value="<?= $app['id']; ?>">
                <select name="status">
                  <option value="Pending" <?= $app['status']=='Pending'?'selected':''; ?>>Pending</option>
                  <option value="Confirmed">Confirm (Mark as Completed)</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
                <button type="submit" name="update_status">Save</button>
              </form>
            <?php else: ?>
              <em>No actions available</em>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="text-align:center;">No pending or cancelled appointments.</p>
  <?php endif; ?>

  <h2>Completed Appointments</h2>
  <?php if (count($completed_appointments) > 0): ?>
    <table>
      <thead>
        <tr>
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
  <?php else: ?>
    <p style="text-align:center;">No completed appointments yet.</p>
  <?php endif; ?>
</div>

<footer>
&copy; <?= date("Y"); ?> PAWthway. Clinic Portal
</footer>

</body>
</html>
