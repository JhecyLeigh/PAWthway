<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// Handle individual notification deletion
if (isset($_POST['delete_notification'])) {
    $notification_id = intval($_POST['notification_id']);
    
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    if ($stmt->execute()) {
        $message = "Notification deleted successfully!";
    } else {
        $error = "Error deleting notification.";
    }
    $stmt->close();
}

// Handle clear all notifications
if (isset($_POST['clear_all_notifications'])) {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $message = "All notifications cleared!";
    } else {
        $error = "Error clearing notifications.";
    }
    $stmt->close();
}

// Fetch user's notifications
$stmt = $conn->prepare("
    SELECT n.*, a.pet_name, a.clinic_name 
    FROM notifications n 
    JOIN appointments a ON n.appointment_id = a.id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - PAWthway</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family:'Poppins',sans-serif; background:linear-gradient(180deg,#e8f5e9 0%,#fff 100%); color:#2e7d32; min-height:100vh; display:flex; flex-direction:column; }
        
        .container { max-width:800px; margin:30px auto; background:white; padding:30px; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
        .container h2 { color:#388e3c; text-align:center; margin-bottom:30px; }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e8f5e9;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
        }
        
        .notification-card { 
            background:white; 
            border-left: 4px solid #4CAF50; 
            border-radius:10px; 
            padding:20px; 
            margin-bottom:15px; 
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .notification-card.unread { 
            background:#f0f9f0; 
            border-left-color: #ff9800; 
        }
        .notification-header-inner { 
            display:flex; 
            justify-content:space-between; 
            align-items:center; 
            margin-bottom:10px; 
        }
        .notification-type { 
            font-weight:600; 
            color:#2e7d32; 
            text-transform:capitalize; 
        }
        .notification-status { 
            padding:4px 12px; 
            border-radius:20px; 
            font-size:12px; 
            font-weight:bold; 
        }
        .status-sent { background:#e8f5e8; color:#2e7d32; }
        .status-pending { background:#fff3e0; color:#ef6c00; }
        .status-failed { background:#ffebee; color:#c62828; }
        .notification-date { 
            color:#666; 
            font-size:14px; 
        }
        .notification-pet { 
            font-weight:500; 
            color:#333; 
            margin:5px 0; 
        }
        .notification-message { 
            color:#555; 
            line-height:1.5; 
            margin-top:10px; 
        }
        
        .notification-actions-card {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn { 
            padding:8px 16px; 
            border:none; 
            border-radius:6px; 
            cursor:pointer; 
            font-weight:500; 
            text-decoration:none; 
            display:inline-block; 
            transition:0.3s; 
            font-size:14px;
        }
        .btn-clear { 
            background:#ff9800; 
            color:white; 
        }
        .btn-clear-all { 
            background:#f44336; 
            color:white; 
        }
        .btn-delete { 
            background:#f44336; 
            color:white; 
            padding: 4px 8px;
            font-size: 12px;
        }
        .btn:hover { 
            opacity:0.9; 
            transform:translateY(-1px);
        }
        
        .no-notifications { 
            text-align:center; 
            padding:40px; 
            color:#666; 
            font-size:16px; 
        }
        
        .success { 
            background:#e8f5e9; 
            color:#2e7d32; 
            padding:15px; 
            border-radius:10px; 
            margin-bottom:20px; 
        }
        .error { 
            background:#ffebee; 
            color:#c62828; 
            padding:15px; 
            border-radius:10px; 
            margin-bottom:20px; 
        }
        
        footer { text-align:center; padding:15px; background:#e8f5e9; color:#388e3c; font-size:14px; margin-top:auto; }
    </style>
</head>
<body>

<?php include('../config/navbar.php'); ?>

<div class="container">
    <div class="notification-header">
        <h2>My Notifications</h2>
        <div class="notification-actions">
            <?php if (count($notifications) > 0): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to clear ALL notifications?')">
                    <button type="submit" name="clear_all_notifications" class="btn btn-clear-all">Clear All</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (count($notifications) > 0): ?>
        <p>You have <?php echo count($notifications); ?> notification(s)</p>
        
        <?php foreach($notifications as $notification): ?>
            <div class="notification-card <?php echo $notification['status'] == 'pending' ? 'unread' : ''; ?>">
                <div class="notification-header-inner">
                    <span class="notification-type"><?php echo $notification['type']; ?> • <?php echo htmlspecialchars($notification['pet_name']); ?></span>
                    <span class="notification-status status-<?php echo $notification['status']; ?>">
                        <?php echo ucfirst($notification['status']); ?>
                    </span>
                </div>
                <div class="notification-date">
                    <?php echo date('F j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                    <?php if ($notification['sent_at']): ?>
                        • Sent: <?php echo date('M j, g:i A', strtotime($notification['sent_at'])); ?>
                    <?php endif; ?>
                </div>
                <div class="notification-pet">
                    Clinic: <?php echo htmlspecialchars($notification['clinic_name']); ?>
                </div>
                <div class="notification-message">
                    <?php 
                    // Create a friendly message based on the notification type
                    if ($notification['type'] == 'reminder') {
                        echo "📅 Appointment reminder sent for " . htmlspecialchars($notification['pet_name']) . 
                             " at " . htmlspecialchars($notification['clinic_name']) . 
                             ". Check your email for details.";
                    } elseif ($notification['type'] == 'cancellation') {
                        echo "❌ Appointment cancelled for " . htmlspecialchars($notification['pet_name']) . 
                             " at " . htmlspecialchars($notification['clinic_name']);
                    } else {
                        echo "Notification: " . ucfirst($notification['type']);
                    }
                    ?>
                </div>
                
                <div class="notification-actions-card">
                    <form method="POST" onsubmit="return confirm('Delete this notification?')" style="display: inline;">
                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                        <button type="submit" name="delete_notification" class="btn btn-delete">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        
    <?php else: ?>
        <div class="no-notifications">
            <p>No notifications yet.</p>
            <p>You'll see appointment reminders and updates here!</p>
        </div>
    <?php endif; ?>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> PAWthway. All Rights Reserved.
</footer>

</body>
</html>