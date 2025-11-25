<?php
/**
 * Shared Navigation Bar Component
 * Dynamically displays different menus for clients vs clinics
 */

// Determine user type and initialize navbar variables
$is_clinic = isset($_SESSION['clinic_id']) && isset($_SESSION['clinic_name']);
$is_client = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
$username = '';
$user_type = '';

if ($is_client) {
    $user_type = 'client';
    $username = $_SESSION['user']['username'] ?? 'Client';
} elseif ($is_clinic) {
    $user_type = 'clinic';
    $username = $_SESSION['clinic_name'] ?? 'Clinic';
}
?>

<!DOCTYPE html>
<style>
    nav {
        background: #4CAF50;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 40px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        flex-wrap: wrap;
    }

    nav .logo {
        display: flex;
        align-items: center;
    }

    nav .logo img {
        width: 50px;
        height: auto;
        margin-right: 10px;
    }

    nav .logo span {
        font-weight: 600;
        font-size: 22px;
    }

    nav ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    nav ul li {
        display: inline;
    }

    nav ul li a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        transition: opacity 0.3s;
    }

    nav ul li a:hover {
        opacity: 0.8;
    }

    .nav-user-info {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 14px;
    }

    .nav-user-info span {
        color: white;
    }

    @media (max-width: 768px) {
        nav {
            padding: 10px 20px;
        }

        nav .logo span {
            font-size: 18px;
        }

        nav ul {
            gap: 10px;
        }

        nav ul li a {
            font-size: 14px;
        }
    }
</style>

<nav>
    <div class="logo">
        <a href="<?php echo ($is_client) ? '../index.php' : '#'; ?>" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
            <img src="../assets/img/logo.png" alt="PAWthway Logo">
            <span>PAWthway</span>
        </a>
    </div>

    <ul>
        <?php if ($is_client): ?>
            <!-- Client Navigation Menu -->
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="clinics.php">Clinics</a></li>
            <li><a href="appointment_list.php">My Appointments</a></li>
            <li><a href="notifications.php">Notifications</a></li>
            <li><a href="reviews.php">My Reviews</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>

        <?php elseif ($is_clinic): ?>
            <!-- Clinic Navigation Menu -->
            <li><a href="clinic_appointments.php">Appointments</a></li>
            <li><a href="clinic_logout.php">Logout</a></li>

        <?php else: ?>
            <!-- Fallback for unauthenticated users -->
            <li><a href="../index.php">Home</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>

    <?php if ($is_client || $is_clinic): ?>
        <div class="nav-user-info">
            <span><?php echo htmlspecialchars($username); ?></span>
        </div>
    <?php endif; ?>
</nav>
