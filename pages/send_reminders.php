<?php
// send_reminders.php
include('../config/db.php');

echo "Starting appointment reminders...\n";

// Find appointments happening in next 24 hours that are confirmed/pending
$query = "
    SELECT a.*, u.email, u.username 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.appointment_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
    AND a.status IN ('confirmed', 'pending')
    AND a.status != 'cancelled'  
    AND NOT EXISTS (
        SELECT 1 FROM notifications n 
        WHERE n.appointment_id = a.id AND n.type = 'reminder' AND n.status = 'sent'
    )
";

$result = $conn->query($query);
$reminders_sent = 0;

while ($appointment = $result->fetch_assoc()) {
    $to = $appointment['email'];
    $subject = "🐾 Appointment Reminder - PAWthway";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #2e7d32; }
            .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .appointment-details { background: #f8f9fa; padding: 15px; border-radius: 10px; }
            .footer { background: #e8f5e9; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>PAWthway Appointment Reminder</h2>
        </div>
        <div class='content'>
            <p>Hi <strong>{$appointment['username']}</strong>,</p>
            <p>This is a friendly reminder for your pet's upcoming appointment.</p>
            
            <div class='appointment-details'>
                <h3>Appointment Details:</h3>
                <p><strong>Pet:</strong> {$appointment['pet_name']}</p>
                <p><strong>Clinic:</strong> {$appointment['clinic_name']}</p>
                <p><strong>Date & Time:</strong> " . date('F j, Y \a\t g:i A', strtotime($appointment['appointment_date'])) . "</p>
                <p><strong>Service:</strong> {$appointment['service']}</p>
                <p><strong>Status:</strong> " . ucfirst($appointment['status']) . "</p>
            </div>
            
            <p>Please arrive 10-15 minutes early for your appointment.</p>
            <p>If you need to reschedule or cancel, please visit your <a href='http://localhost:8000/pages/appointment_list.php'>appointments page</a>.</p>
        </div>
        <div class='footer'>
            <p>Thank you for choosing PAWthway! 🐕🐈</p>
            <p>&copy; " . date('Y') . " PAWthway. All rights reserved.</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: PAWthway <noreply@pawthway.com>" . "\r\n";
    
    // For testing, we'll just log instead of actually sending email
    $email_sent = true; // Change this to: mail($to, $subject, $message, $headers);
    
    if ($email_sent) {
    // Create a clean message for the database (not the HTML email)
    $clean_message = "Reminder for {$appointment['pet_name']}'s {$appointment['service']} appointment at {$appointment['clinic_name']} on " . date('M j, Y g:i A', strtotime($appointment['appointment_date']));
    
    // Log successful notification with CLEAN message
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, appointment_id, type, message, status, sent_at) VALUES (?, ?, 'reminder', ?, 'sent', NOW())");
    $stmt->bind_param("iis", $appointment['user_id'], $appointment['id'], $clean_message); // ← FIX: storing clean text
    $stmt->execute();
    } else {
    // Log failed notification with clean message
    $clean_message = "Failed to send reminder for {$appointment['pet_name']}'s appointment";
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, appointment_id, type, message, status) VALUES (?, ?, 'reminder', ?, 'failed')");
    $stmt->bind_param("iis", $appointment['user_id'], $appointment['id'], $clean_message); // ← FIX
    }
}

$conn->close();
echo "Reminder process completed! Sent {$reminders_sent} reminders.\n";
?>