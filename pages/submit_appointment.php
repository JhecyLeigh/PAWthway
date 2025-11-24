<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int)$user['id'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: clinics.php");
    exit;
}

// Gather + trim inputs
$clinic_id_post = isset($_POST['clinic_id']) ? (int)$_POST['clinic_id'] : 0;
$clinic_name = trim($_POST['clinic_name'] ?? '');
$pet_name = trim($_POST['pet_name'] ?? '');
$pet_type = trim($_POST['pet_type'] ?? '');
$pet_age = isset($_POST['pet_age']) ? (int)$_POST['pet_age'] : null;
$pet_gender = trim($_POST['pet_gender'] ?? '');
$service = trim($_POST['service'] ?? '');
$appointment_date = trim($_POST['appointment_date'] ?? '');

// Basic required validation
if (empty($clinic_name) || empty($pet_name) || empty($pet_type) || $pet_age === null || $pet_age === '' || empty($pet_gender) || empty($service) || empty($appointment_date)) {
    echo "<script>alert('Please fill out all fields.'); window.history.back();</script>";
    exit;
}

// Normalize date/time
$appointmentTimestamp = strtotime($appointment_date);
if ($appointmentTimestamp === false) {
    echo "<script>alert('Invalid appointment date/time format.'); window.history.back();</script>";
    exit;
}

// Don't allow past bookings
$nowTimestamp = time();
if ($appointmentTimestamp < $nowTimestamp) {
    echo "<script>alert('You cannot book an appointment in the past.'); window.history.back();</script>";
    exit;
}

// Resolve clinic: prefer clinic_id if provided, otherwise lookup by name
$clinic = null;
if ($clinic_id_post > 0) {
    $stmt = $conn->prepare("SELECT id, name, opening_time, closing_time FROM clinics WHERE id = ?");
    $stmt->bind_param("i", $clinic_id_post);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $clinic = $res->fetch_assoc();
    }
    $stmt->close();
}

// If not found by id (or id not provided), try by name
if (!$clinic) {
    $stmt = $conn->prepare("SELECT id, name, opening_time, closing_time FROM clinics WHERE name = ?");
    $stmt->bind_param("s", $clinic_name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $clinic = $res->fetch_assoc();
        // ensure clinic_id_post is set to resolved id
        $clinic_id_post = (int)$clinic['id'];
    }
    $stmt->close();
}

if (!$clinic) {
    echo "<script>alert('Clinic not found.'); window.history.back();</script>";
    exit;
}

// Server-side clinic hours validation
$opening_time = $clinic['opening_time'];
$closing_time = $clinic['closing_time'];

$is24Hours = ($opening_time === "00:00:00" && $closing_time === "23:59:59");

if (!$is24Hours) {
    // convert appointment to minutes since 00:00
    $appointmentMinutes = (int)date("H", $appointmentTimestamp) * 60 + (int)date("i", $appointmentTimestamp);

    list($openH, $openM) = explode(":", $opening_time);
    list($closeH, $closeM) = explode(":", $closing_time);
    $openMinutes = (int)$openH * 60 + (int)$openM;
    $closeMinutes = (int)$closeH * 60 + (int)$closeM;

    $isValidTime = ($openMinutes <= $closeMinutes && $appointmentMinutes >= $openMinutes && $appointmentMinutes <= $closeMinutes)
        || ($openMinutes > $closeMinutes && ($appointmentMinutes >= $openMinutes || $appointmentMinutes <= $closeMinutes));

    if (!$isValidTime) {
        echo "<script>alert('Selected time is outside the clinic hours.'); window.history.back();</script>";
        exit;
    }
}

// Now insert appointment with clinic_id included
$stmt = $conn->prepare("
    INSERT INTO appointments 
    (user_id, clinic_id, clinic_name, pet_name, pet_type, pet_age, pet_gender, service, appointment_date, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
");

if (!$stmt) {
    die("<b>Database Error (prepare):</b> " . htmlspecialchars($conn->error));
}

$clinic_id = (int)$clinic_id_post;
$clinic_name_for_insert = $clinic['name']; // use canonical name from DB to avoid mismatch

// bind param types: i (user_id), i (clinic_id), s (clinic_name), s (pet_name), s (pet_type),
// i (pet_age), s (pet_gender), s (service), s (appointment_date)
$stmt->bind_param(
    "iisssisss",
    $user_id,
    $clinic_id,
    $clinic_name_for_insert,
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
    $err = htmlspecialchars($stmt->error);
    echo "<script>alert('Error booking appointment: {$err}'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
exit;
