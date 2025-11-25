<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $clinic_name = trim($_POST['clinic_name']);
    $pet_name = trim($_POST['pet_name']);
    $pet_type = trim($_POST['pet_type']);
    $pet_age = trim($_POST['pet_age']);
    $pet_gender = trim($_POST['pet_gender']);
    $service = trim($_POST['service']);
    $appointment_date = trim($_POST['appointment_date']);

    if (empty($clinic_name) || empty($pet_name) || empty($pet_type) || empty($pet_age) || empty($pet_gender) || empty($service) || empty($appointment_date)) {
        echo "<script>alert('Please fill out all fields.'); window.history.back();</script>";
        exit;
    }

    // Fetch clinic times
    $stmt = $conn->prepare("SELECT opening_time, closing_time FROM clinics WHERE name=?");
    $stmt->bind_param("s", $clinic_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows == 0) {
        echo "<script>alert('Clinic not found.'); window.history.back();</script>";
        exit;
    }
    $clinic = $result->fetch_assoc();
    $stmt->close();

    $appointmentTimestamp = strtotime($appointment_date);
    $nowTimestamp = time();
    if($appointmentTimestamp < $nowTimestamp) {
        echo "<script>alert('You cannot book an appointment in the past.'); window.history.back();</script>";
        exit;
    }

    // Server-side clinic hours validation
    $appointmentMinutes = (int)date("H",$appointmentTimestamp)*60 + (int)date("i",$appointmentTimestamp);
    list($openH,$openM) = explode(":",$clinic['opening_time']);
    list($closeH,$closeM) = explode(":",$clinic['closing_time']);
    $openMinutes = $openH*60 + $openM;
    $closeMinutes = $closeH*60 + $closeM;

    $is24Hours = $clinic['opening_time']=="00:00:00" && $clinic['closing_time']=="23:59:59";
    $isValidTime = $is24Hours || 
                  ($openMinutes <= $closeMinutes && $appointmentMinutes >= $openMinutes && $appointmentMinutes <= $closeMinutes) ||
                  ($openMinutes > $closeMinutes && ($appointmentMinutes >= $openMinutes || $appointmentMinutes <= $closeMinutes));

    if(!$isValidTime) {
        echo "<script>alert('Selected time is outside the clinic hours.'); window.history.back();</script>";
        exit;
    }

    // Insert appointment
    $stmt = $conn->prepare("
        INSERT INTO appointments 
        (user_id, clinic_name, pet_name, pet_type, pet_age, pet_gender, service, appointment_date, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
    ");

    if (!$stmt) {
        die("<b>Database Error:</b> " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param(
        "isssisss",
        $user_id,
        $clinic_name,
        $pet_name,
        $pet_type,
        $pet_age,
        $pet_gender,
        $service,
        $appointment_date
    );

    if ($stmt->execute()) {
        echo "<script>alert('Appointment booked successfully!'); window.location.href='appointment_list.php';</script>";
    } else {
        echo "<script>alert('Error booking appointment: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: clinics.php");
    exit;
}
?>
