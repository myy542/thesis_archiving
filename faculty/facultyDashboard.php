<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user_id"])) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

error_log("Faculty Dashboard - User ID from session: " . $user_id);

$roleQuery = "SELECT user_id, first_name, last_name, role_id FROM user_table WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($roleQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

error_log("Faculty Dashboard - User data from DB: " . print_r($userData, true));

if (!$userData) {
    error_log("Faculty Dashboard - User not found in database, destroying session");
    session_destroy();
    header("Location: ../authentication/login.php?error=user_not_found");
    exit;
}

$required_role_id = 3;

if ($userData['role_id'] != $required_role_id) {
    error_log("Faculty Dashboard - Access denied. User role_id: " . $userData['role_id'] . ", Required: " . $required_role_id);
    
    if ($userData['role_id'] == 2) {
        header("Location: /ArchivingThesis/student/student_dashboard.php");
        exit;
    } elseif ($userData['role_id'] == 1) {
        header("Location: /ArchivingThesis/admin/admin_dashboard.php");
        exit;
    } else {
        header("Location: ../authentication/login.php?error=invalid_role");
        exit;
    }
}

$faculty_id = $user_id;

$stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, role_id FROM user_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$faculty) {
    error_log("Faculty Dashboard - Faculty data not found after role verification");
    session_destroy();
    header("Location: ../authentication/login.php?error=invalid_session");
    exit;
}

$first = trim($faculty["first_name"] ?? "");
$last  = trim($faculty["last_name"] ?? "");
$fullName = trim($first . " " . $last);
$initials = $first && $last ? strtoupper(substr($first, 0, 1) . substr($last, 0, 1)) : "FA";

error_log("Faculty Dashboard - Access granted for: " . $fullName . " (ID: " . $faculty_id . ")");

$unreadCount = 0;
$recentNotifications = [];

try {
    $countQuery = "SELECT COUNT(*) as total FROM notification_table 
                   WHERE user_id = ? AND status = 'unread'";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $countResult = $stmt->get_result()->fetch_assoc();
    $unreadCount = $countResult['total'] ?? 0;
    $stmt->close();
    $notifQuery = "SELECT 
                    n.notification_id as id, 
                    n.message,
                    n.status,
                    n.created_at,
                    n.thesis_id,
                    t.title as thesis_title,
                    u.first_name as student_first,
                    u.last_name as student_last
                  FROM notification_table n
                  LEFT JOIN thesis_table t ON n.thesis_id = t.thesis_id
                  LEFT JOIN user_table u ON t.student_id = u.user_id
                  WHERE n.user_id = ? 
                  ORDER BY n.created_at DESC 
                  LIMIT 10";
    
    $stmt = $conn->prepare($notifQuery);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $notifResult = $stmt->get_result();
    
    while ($row = $notifResult->fetch_assoc()) {
        $row['is_read'] = ($row['status'] == 'unread') ? 0 : 1;
        $recentNotifications[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Faculty Dashboard - Notification error: " . $e->getMessage());
}

$pendingTheses = [];

try {
    $query = "SELECT t.*, u.first_name, u.last_name, u.email 
              FROM thesis_table t
              JOIN user_table u ON t.student_id = u.user_id
              WHERE t.status = 'pending'
              ORDER BY t.date_submitted DESC 
              LIMIT 10";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pendingTheses[] = $row;
        }
    } else {
        error_log("Faculty Dashboard - Error in pending theses query: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Thesis query error: " . $e->getMessage());
}

$pendingCount = count($pendingTheses);

$approvedCount = 0;
try {
    $approvedQuery = "SELECT COUNT(*) as total FROM thesis_table WHERE status = 'approved'";
    $approvedResult = $conn->query($approvedQuery);
    if ($approvedResult) {
        $approvedCount = $approvedResult->fetch_assoc()['total'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Approved count error: " . $e->getMessage());
}

$rejectedCount = 0;
try {
    $rejectedQuery = "SELECT COUNT(*) as total FROM thesis_table WHERE status = 'rejected'";
    $rejectedResult = $conn->query($rejectedQuery);
    if ($rejectedResult) {
        $rejectedCount = $rejectedResult->fetch_assoc()['total'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Rejected count error: " . $e->getMessage());
}

$pageTitle = "Faculty Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Theses Archiving System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
     
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f5f5;
        }

        body.dark-mode {
            background: #2d2d2d;
            color: #e0e0e0;
        }

        .layout {
            min-height: 100vh;
            position: relative;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -300px;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #FE4853 0%, #732529 100%);
            color: white;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: left 0.3s ease;
            box-shadow: 5px 0 20px rgba(0,0,0,0.3);
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
            color: white;
            font-weight: 700;
        }

        .sidebar-header p {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
            font-weight: 500;
        }

        .nav-link i {
            width: 20px;
            color: white;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 600;
        }

        .nav-link.active i {
            color: white;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .logout-btn i {
            color: white;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .theme-toggle {
            margin-bottom: 1rem;
        }

        .theme-toggle input {
            display: none;
        }

        .toggle-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            cursor: pointer;
            position: relative;
        }

        .toggle-label i {
            font-size: 1rem;
            z-index: 1;
            padding: 0.25rem;
            color: white;
        }

        .slider {
            position: absolute;
            width: 50%;
            height: 80%;
            background: #732529;
            border-radius: 20px;
            transition: transform 0.3s;
            top: 10%;
            left: 0;
        }

        #darkmode:checked ~ .toggle-label .slider {
            transform: translateX(100%);
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .overlay.show {
            display: block;
        }

        .main-content {
            flex: 1;
            margin-left: 0;
            min-height: 100vh;
            padding: 2rem;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(110, 110, 110, 0.1);
        }

        body.dark-mode .topbar {
            background: #3a3a3a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .topbar h1 {
            font-size: 1.875rem;
            color: #000000;
        }

        body.dark-mode .topbar h1 {
            color: #FE4853;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .hamburger-menu {
            font-size: 1.5rem;
            cursor: pointer;
            color: #FE4853;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .hamburger-menu:hover {
            background: rgba(254, 72, 83, 0.1);
            color: #732529;
        }

        .avatar-dropdown {
            position: relative;
            display: inline-block;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FE4853 0%, #732529 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(254, 72, 83, 0.3);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 55px;
            background-color: white;
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
            z-index: 1000;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .dropdown-content.show {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        .dropdown-content a {
            color: #475569;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s;
            font-size: 0.95rem;
        }

        .dropdown-content a i {
            width: 18px;
            color: #FE4853;
        }

        .dropdown-content a:hover {
            background-color: #f8fafc;
        }

        .dropdown-content hr {
            margin: 0;
            border: none;
            border-top: 1px solid #e9ecef;
        }

        /* Notification Styles */
        .notification-container {
            position: relative;
            display: inline-block;
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            font-size: 1.25rem;
            color: #6E6E6E;
            transition: color 0.2s;
        }

        .notification-bell:hover {
            color: #FE4853;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #FE4853;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            animation: pulse 2s infinite;
        }

        .notification-dropdown {
            display: none;
            position: absolute;
            right: -10px;
            top: 45px;
            width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border: 1px solid #e0e0e0;
            z-index: 1000;
        }

        body.dark-mode .notification-dropdown {
            background: #2d2d2d;
            border-color: #6E6E6E;
        }

        .notification-dropdown.show {
            display: block;
            animation: slideDown 0.2s ease;
        }

        .notification-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h4 {
            margin: 0;
            color: #000000;
            font-size: 1rem;
            font-weight: 600;
        }

        .notification-header a {
            color: #000000;
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f5f5f5;
        }

        .notification-item.unread {
            background: #fff3f3;
        }

        .notif-message {
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 5px;
        }

        .notif-time {
            font-size: 0.75rem;
            color: #6E6E6E;
        }

        .no-notifications {
            text-align: center;
            color: #6E6E6E;
            padding: 20px 0;
        }

        .notification-footer {
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }

        .notification-footer a {
            color: #FE4853;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
 
        .welcome-banner {
            background: linear-gradient(135deg, #FE4853 0%, #732529 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(254, 72, 83, 0.3);
        }

        .welcome-banner h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .welcome-banner p {
            margin: 0;
            opacity: 0.9;
            font-size: 1rem;
        }
 
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
            text-align: center;
            transition: transform 0.18s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(254, 72, 83, 0.15);
        }

        body.dark-mode .stat-card {
            background: #3a3a3a;
        }

        .stat-icon {
            font-size: 2.2rem;
            color: #FE4853;
            margin-bottom: 0.8rem;
        }

        .stat-value {
            font-size: 2.4rem;
            font-weight: 700;
            color: #000000;
            line-height: 1.2;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            color: #6E6E6E;
            font-size: 0.95rem;
            font-weight: 500;
        }
 
        .pending-theses {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
            margin-bottom: 2rem;
        }

        .pending-theses h3 {
            color: #000000;
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.3rem;
        }

        .theses-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .thesis-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            transition: transform 0.2s, background 0.2s;
        }

        .thesis-item:hover {
            transform: translateX(5px);
            background: #f1f5f9;
        }

        .thesis-info h4 {
            margin: 0 0 0.3rem 0;
            color: #333;
            font-size: 1rem;
        }

        .thesis-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #6E6E6E;
        }

        .thesis-info p i {
            margin-right: 0.3rem;
        }

        .btn-review {
            padding: 0.5rem 1rem;
            background: #FE4853;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-review:hover {
            background: #000000;
        }
        .recent-activity {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
        }
        .recent-activity h3 {
            color: #000000;
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.3rem;
        }
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            transition: transform 0.2s;
        }
        .activity-item:hover {
            transform: translateX(5px);
        }
        .activity-icon {
            font-size: 1.2rem;
            color: #FE4853;
            margin-top: 0.2rem;
        }
        .activity-content p {
            margin: 0 0 0.3rem 0;
            color: #333;
            font-size: 0.95rem;
        }
        .activity-time {
            font-size: 0.8rem;
            color: #6E6E6E;
        }
        .badge {
            background: #FE4853;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 8px;
        }
        .mobile-menu-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1001;
            border: none;
            background: #FE4853;
            color: #fff;
            padding: 12px 15px;
            border-radius: 10px;
            cursor: pointer;
            display: none;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(254, 72, 83, 0.3);
            border: 1px solid white;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .user-info {
                width: 100%;
                justify-content: flex-start;
                gap: 1rem;
            }
            .stats-overview {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .thesis-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .thesis-actions {
                width: 100%;
            }
            .btn-review {
                width: 100%;
                justify-content: center;
            }

            .notification-dropdown {
                width: 300px;
                right: -50px;
            }
        }
    </style>
</head>
<body>
<div class="overlay" id="overlay"></div>
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Theses Archive</h2>
        <p>Faculty Portal</p>
    </div>

    <nav class="sidebar-nav">
        <a href="facultyDashboard.php" class="nav-link active">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="reviewThesis.php" class="nav-link">
            <i class="fas fa-book-reader"></i> Review Theses
            <?php if ($pendingCount > 0): ?>
                <span class="badge"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
        <a href="facultyFeedback.php" class="nav-link">
            <i class="fas fa-comment-dots"></i> My Feedback
        </a>
        <a href="#" class="nav-link">
            <i class="fas fa-calendar-check"></i> Schedule
        </a>
        <a href="#" class="nav-link">
            <i class="fas fa-chart-line"></i> Statistics
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="theme-toggle">
            <input type="checkbox" id="darkmode" />
            <label for="darkmode" class="toggle-label">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
                <span class="slider"></span>
            </label>
        </div>
        <a href="../authentication/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<div class="layout">
    <main class="main-content">

        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="hamburger-menu" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </div>
                <h1>Faculty Dashboard</h1>
            </div>

            <div class="user-info">
                <div class="notification-container">
                    <div class="notification-bell" id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                            <a href="#" id="markAllRead">Mark all as read</a>
                        </div>
                        <div class="notification-list">
                            <?php if (empty($recentNotifications)): ?>
                                <div class="notification-item">
                                    <div class="no-notifications">No new thesis submissions</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentNotifications as $notif): ?>
                                    <div class="notification-item <?= isset($notif['is_read']) && $notif['is_read'] == 0 ? 'unread' : '' ?>"
                                         data-notification-id="<?= $notif['id'] ?? '' ?>"
                                         data-thesis-id="<?= $notif['thesis_id'] ?? '' ?>"
                                         onclick="markAsReadAndRedirect(this, <?= $notif['thesis_id'] ?? 0 ?>)">
                                        <div class="notif-message">
                                            <?= htmlspecialchars($notif['message'] ?? '') ?>
                                        </div>
                                        <div class="notif-time"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="notification-footer">
                            <a href="notifications.php">View all notifications</a>
                        </div>
                    </div>
                </div>
                <div class="avatar-dropdown">
                    <div class="avatar" id="avatarBtn">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    <div class="dropdown-content" id="dropdownMenu">
                        <a href="facultyProfile.php">
                            <i class="fas fa-user-circle"></i> Profile
                        </a>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <hr>
                        <a href="../authentication/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <?php if (isset($_GET['debug'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border-radius: 8px;">
            <strong>Debug Info:</strong><br>
            User ID: <?= $user_id ?><br>
            Role ID from DB: <?= $userData['role_id'] ?? 'N/A' ?><br>
            Required Role: <?= $required_role_id ?><br>
            Access: <?= ($userData['role_id'] == $required_role_id) ? 'GRANTED' : 'DENIED' ?><br>
            Full Name: <?= htmlspecialchars($fullName) ?>
        </div>
        <?php endif; ?>

        <div class="welcome-banner">
            <h2>Welcome, <?= htmlspecialchars($fullName) ?>!</h2>
            <p>Here's an overview of your advising and review activities.</p>
        </div>

        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?= $pendingCount ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?= $approvedCount ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-value"><?= $rejectedCount ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <?php if (!empty($pendingTheses)): ?>
        <div class="pending-theses">
            <h3><i class="fas fa-clock"></i> Theses Waiting for Review</h3>
            <div class="theses-list">
                <?php foreach ($pendingTheses as $thesis): ?>
                <div class="thesis-item">
                    <div class="thesis-info">
                        <h4><?= htmlspecialchars($thesis['title']) ?></h4>
                        <p>
                            <i class="fas fa-user"></i> <?= htmlspecialchars($thesis['first_name'] . ' ' . $thesis['last_name']) ?> | 
                            <i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($thesis['date_submitted'])) ?>
                        </p>
                    </div>
                    <div class="thesis-actions">
                        <a href="reviewThesis.php?id=<?= $thesis['thesis_id'] ?>" class="btn-review">
                            <i class="fas fa-search"></i> Review
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="recent-activity">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
            <div class="activity-list">
                <?php 
                $activityShown = 0;
                foreach ($recentNotifications as $notif): 
                    if ($activityShown >= 5) break;
                    $activityShown++;
                ?>
                <div class="activity-item">
                    <i class="fas fa-file-upload activity-icon"></i>
                    <div class="activity-content">
                        <p><?= htmlspecialchars($notif['message']) ?></p>
                        <span class="activity-time"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if ($activityShown == 0): ?>
                <div class="activity-item">
                    <i class="fas fa-info-circle activity-icon"></i>
                    <div class="activity-content">
                        <p>No recent activity</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
   const toggle = document.getElementById('darkmode');
    if (toggle) {
        toggle.addEventListener('change', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', toggle.checked);
        });
        if (localStorage.getItem('darkMode') === 'true') {
            toggle.checked = true;
            document.body.classList.add('dark-mode');
        }
    }
    const avatarBtn = document.getElementById('avatarBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');

    if (avatarBtn) {
        avatarBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
            notificationDropdown.classList.remove('show');
        });
    }

    if (notificationBell) {
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
            dropdownMenu.classList.remove('show');
        });
    }

    window.addEventListener('click', function() {
        if (notificationDropdown) notificationDropdown.classList.remove('show');
        if (dropdownMenu) dropdownMenu.classList.remove('show');
    });

    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    if (dropdownMenu) {
        dropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
 
    document.getElementById('markAllRead')?.addEventListener('click', function(e) {
        e.preventDefault();
        
        fetch('mark_all_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                });
                
                const badge = document.querySelector('.notification-badge');
                if (badge) badge.remove();
                
                const notifStat = document.querySelector('.stat-card:last-child .stat-value');
                if (notifStat) notifStat.textContent = '0';
            }
        })
        .catch(error => console.error('Error:', error));
    });

    function markAsReadAndRedirect(element, thesisId) {
        var notificationId = element.getAttribute('data-notification-id');
        
        if (!notificationId) {
            console.error('Notification ID not found');
            return;
        }
        
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.classList.remove('unread');
                
                const badge = document.querySelector('.notification-badge');
                const notifStat = document.querySelector('.stat-card:last-child .stat-value');
                
                if (badge) {
                    let currentCount = parseInt(badge.textContent);
                    if (currentCount > 1) {
                        badge.textContent = currentCount - 1;
                        if (notifStat) notifStat.textContent = currentCount - 1;
                    } else {
                        badge.remove();
                        if (notifStat) notifStat.textContent = '0';
                    }
                }
                
                if (thesisId && thesisId > 0) {
                    window.location.href = 'reviewThesis.php?id=' + thesisId;
                }
            } else {
                console.error('Failed to mark as read:', data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }
 
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        
        const mobileIcon = mobileBtn?.querySelector('i');
        const hamburgerIcon = hamburgerBtn?.querySelector('i');
        
        if (sidebar.classList.contains('show')) {
            if (mobileIcon) {
                mobileIcon.classList.remove('fa-bars');
                mobileIcon.classList.add('fa-times');
            }
            if (hamburgerIcon) {
                hamburgerIcon.classList.remove('fa-bars');
                hamburgerIcon.classList.add('fa-times');
            }
        } else {
            if (mobileIcon) {
                mobileIcon.classList.remove('fa-times');
                mobileIcon.classList.add('fa-bars');
            }
            if (hamburgerIcon) {
                hamburgerIcon.classList.remove('fa-times');
                hamburgerIcon.classList.add('fa-bars');
            }
        }
    }

    if (mobileBtn) mobileBtn.addEventListener('click', toggleSidebar);
    if (hamburgerBtn) hamburgerBtn.addEventListener('click', toggleSidebar);

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            
            const mobileIcon = mobileBtn?.querySelector('i');
            const hamburgerIcon = hamburgerBtn?.querySelector('i');
            
            if (mobileIcon) {
                mobileIcon.classList.remove('fa-times');
                mobileIcon.classList.add('fa-bars');
            }
            if (hamburgerIcon) {
                hamburgerIcon.classList.remove('fa-times');
                hamburgerIcon.classList.add('fa-bars');
            }
        });
    }

    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                
                const mobileIcon = mobileBtn?.querySelector('i');
                const hamburgerIcon = hamburgerBtn?.querySelector('i');
                
                if (mobileIcon) {
                    mobileIcon.classList.remove('fa-times');
                    mobileIcon.classList.add('fa-bars');
                }
                if (hamburgerIcon) {
                    hamburgerIcon.classList.remove('fa-times');
                    hamburgerIcon.classList.add('fa-bars');
                }
            }
        });
    });
</script>

</body>
</html>