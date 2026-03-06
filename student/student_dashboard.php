<?php
session_start();
include("../config/db.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION["user_id"])) {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$roleQuery = "SELECT role_id FROM user_table WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($roleQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userData) {
    session_destroy();
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

if ($userData['role_id'] != 2) {
    if ($userData['role_id'] == 3) {
        header("Location: /ArchivingThesis/faculty/facultyDashboard.php");
    } else {
        header("Location: /ArchivingThesis/authentication/login.php");
    }
    exit;
}
$user_columns = $conn->query("SHOW COLUMNS FROM user_table");
$user_id_column = 'user_id';
while ($column = $user_columns->fetch_assoc()) {
    if (strpos($column['Field'], 'user') !== false || strpos($column['Field'], 'id') !== false) {
        $user_id_column = $column['Field'];
        break;
    }
}

$stmt = $conn->prepare("SELECT first_name, last_name FROM user_table WHERE $user_id_column = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$first = trim($user["first_name"] ?? "");
$last  = trim($user["last_name"] ?? "");

$displayName = trim($first . " " . $last);
$initials = $first && $last ? strtoupper(substr($first, 0, 1) . substr($last, 0, 1)) : "U";

$student_id = $user_id; 
$studentQuery = "SELECT student_id FROM student_table WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$studentResult = $stmt->get_result();
$studentData = $studentResult->fetch_assoc();
$stmt->close();

if ($studentData) {
    $student_id = $studentData['student_id'];
}
$pendingCount = 0;
try {
    $pendingQuery = "SELECT COUNT(*) as total FROM thesis_table 
                    WHERE student_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($pendingQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $pendingCount = $result['total'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Pending count error: " . $e->getMessage());
    $pendingCount = 0;
}

$approvedCount = 0;
try {
    $approvedQuery = "SELECT COUNT(*) as total FROM thesis_table 
                      WHERE student_id = ? AND status = 'approved'";
    $stmt = $conn->prepare($approvedQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $approvedCount = $result['total'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Approved count error: " . $e->getMessage());
    $approvedCount = 0;
}

$rejectedCount = 0;
try {
    $rejectedQuery = "SELECT COUNT(*) as total FROM thesis_table 
                      WHERE student_id = ? AND status = 'rejected'";
    $stmt = $conn->prepare($rejectedQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $rejectedCount = $result['total'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Rejected count error: " . $e->getMessage());
    $rejectedCount = 0;
}

$archivedCount = 0;
try {
    $archivedQuery = "SELECT COUNT(*) as total FROM thesis_table 
                      WHERE student_id = ? AND status IN ('archived', 'completed', 'finished')";
    $stmt = $conn->prepare($archivedQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $archivedCount = $result['total'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Archived count error: " . $e->getMessage());
    $archivedCount = 0;
}

$totalCount = 0;
try {
    $totalQuery = "SELECT COUNT(*) as total FROM thesis_table 
                   WHERE student_id = ?";
    $stmt = $conn->prepare($totalQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $totalCount = $result['total'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Total count error: " . $e->getMessage());
    $totalCount = 0;
}

$unreadCount = 0;
$recentNotifications = [];

try {
    $countQuery = "SELECT COUNT(*) as total FROM notification_table 
                   WHERE user_id = ? AND status = 'unread'";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param("i", $user_id);
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
                    CASE 
                        WHEN n.message LIKE '%feedback%' THEN 'feedback'
                        WHEN n.message LIKE '%approved%' THEN 'approved'
                        WHEN n.message LIKE '%rejected%' THEN 'rejected'
                        ELSE 'other'
                    END as notification_type
                   FROM notification_table n
                   LEFT JOIN thesis_table t ON n.thesis_id = t.thesis_id
                   WHERE n.user_id = ? 
                   ORDER BY n.created_at DESC 
                   LIMIT 10";
    $stmt = $conn->prepare($notifQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['is_read'] = $row['status'];
        $recentNotifications[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    $unreadCount = 0;
    $recentNotifications = [];
}

$recentFeedback = [];
try {
    $feedbackQuery = "SELECT f.*, t.title as thesis_title, 
                             u.first_name as faculty_first, u.last_name as faculty_last
                      FROM feedback_table f
                      JOIN thesis_table t ON f.thesis_id = t.thesis_id
                      JOIN user_table u ON f.faculty_id = u.user_id
                      WHERE t.student_id = ?
                      ORDER BY f.feedback_date DESC
                      LIMIT 5";
    $stmt = $conn->prepare($feedbackQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recentFeedback = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Feedback fetch error: " . $e->getMessage());
}

$feedbackNotificationCount = 0;
foreach ($recentNotifications as $notif) {
    if (strpos($notif['message'], 'feedback') !== false) {
        $feedbackNotificationCount++;
    }
}

$pageTitle = "Student Dashboard";
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
      color: #000000; 
    }

    body.dark-mode {
      background: #2d2d2d;
      color: #e0e0e0;
    }

    .layout {
      min-height: 100vh;
      position: relative;
    }
 
    .main-content {
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
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
      color: #000000;  
    }

    body.dark-mode .topbar {
      background: #3a3a3a;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      color: #e0e0e0;
    }

    .topbar h1 {
      font-size: 1.875rem;
      color: #000000;
    }

    body.dark-mode .topbar h1 {
      color: #000000;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }
 
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
      text-decoration: none;
    }

    .notification-bell:hover {
      color: #000000;
    }

    body.dark-mode .notification-bell {
      color: #e0e0e0;
    }

    body.dark-mode .notification-bell:hover {
      color: #000000;
    }

    .notification-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #000000;
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
      width: 380px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      border: 1px solid #e0e0e0;
      z-index: 1000;
      color: #000000;  
    }

    body.dark-mode .notification-dropdown {
      background: #2d2d2d;
      border-color: #6E6E6E;
      color: #e0e0e0;
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
      color: #000000;  
    }

    body.dark-mode .notification-header {
      border-bottom-color: #6E6E6E;
      color: #e0e0e0;
    }

    .notification-header h4 {
      margin: 0;
      color: #000000;
      font-size: 1rem;
      font-weight: 600;
    }

    body.dark-mode .notification-header h4 {
      color: #FE4853;
    }

    .notification-header a {
      color: #000000;
      text-decoration: none;
      font-size: 0.85rem;
      cursor: pointer;
    }

    .notification-header a:hover {
      text-decoration: underline;
    }

    .notification-list {
      max-height: 350px;
      overflow-y: auto;
    }

    .notification-item {
      padding: 15px 20px;
      border-bottom: 1px solid #f0f0f0;
      transition: background 0.2s;
      cursor: pointer;
      color: #000000; /* Black font */
    }

    body.dark-mode .notification-item {
      border-bottom-color: #3a3a3a;
      color: #e0e0e0;
    }

    .notification-item:hover {
      background: #f5f5f5;
    }

    body.dark-mode .notification-item:hover {
      background: #3a3a3a;
    }

    .notification-item.unread {
      background: #fff3f3;
      border-left: 3px solid #FE4853;
    }

    body.dark-mode .notification-item.unread {
      background: #000000;
    }

    .notif-message {
      font-size: 0.9rem;
      color: #000000; /* Black font */
      margin-bottom: 5px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    body.dark-mode .notif-message {
      color: #e0e0e0;
    }

    .notif-message i {
      color: #10b981;
    }

    .notif-time {
      font-size: 0.75rem;
      color: #000000; /* Black font */
    }

    body.dark-mode .notif-time {
      color: #94a3b8;
    }

    .notif-thesis {
      font-size: 0.8rem;
      color: #FE4853;
      margin-top: 3px;
      font-style: italic;
    }

    .no-notifications {
      text-align: center;
      color: #000000; /* Black font */
      padding: 30px 0;
    }

    body.dark-mode .no-notifications {
      color: #e0e0e0;
    }

    .notification-footer {
      padding: 15px 20px;
      text-align: center;
      border-top: 1px solid #e0e0e0;
      color: #000000; /* Black font */
    }

    body.dark-mode .notification-footer {
      border-top-color: #6E6E6E;
      color: #e0e0e0;
    }

    .notification-footer a {
      color: #FE4853;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .notification-footer a:hover {
      text-decoration: underline;
    }
 
    .avatar-dropdown {
      position: relative;
    }

    .avatar {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      background: linear-gradient(135deg, #FE4853 0%, #000000 100%);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 1rem;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .avatar:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(254, 72, 83, 0.3);
    }

    body.dark-mode .avatar {
      background: linear-gradient(135deg, #FE4853 0%, #732529 100%);
    }

    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      top: 55px;
      background: white;
      min-width: 200px;
      box-shadow: 0 8px 16px rgba(110, 110, 110, 0.15);
      border-radius: 8px;
      z-index: 1000;
      overflow: hidden;
      border: 1px solid #e0e0e0;
      color: #000000; /* Black font */
    }

    body.dark-mode .dropdown-content {
      background: #3a3a3a;
      border-color: #6E6E6E;
      box-shadow: 0 8px 16px rgba(0,0,0,0.3);
      color: #e0e0e0;
    }

    .dropdown-content.show {
      display: block;
      animation: fadeIn 0.2s;
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

    .dropdown-content a {
      color: #000000; /* Black font */
      padding: 12px 16px;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background 0.2s;
    }

    body.dark-mode .dropdown-content a {
      color: #e0e0e0;
    }

    .dropdown-content a i {
      width: 18px;
      color: #FE4853;
    }

    .dropdown-content hr {
      border: none;
      border-top: 1px solid #e0e0e0;
      margin: 4px 0;
    }

    body.dark-mode .dropdown-content hr {
      border-top-color: #6E6E6E;
    }

    .dropdown-content a:hover {
      background: #f5f5f5;
    }

    body.dark-mode .dropdown-content a:hover {
      background: #4a4a4a;
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

    body.dark-mode .hamburger-menu {
      color: #FE4853;
    }

    body.dark-mode .hamburger-menu:hover {
      background: rgba(254, 72, 83, 0.2);
      color: #FE4853;
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

    body.dark-mode .mobile-menu-btn {
      background: #732529;
    }

    /* Welcome Section */
    .welcome-section {
      margin-bottom: 2.5rem;
      padding: 0 1rem;
    }

    .welcome-section h2 {
      color: #000000;
      font-size: 2.1rem;
      margin-bottom: 0.5rem;
    }

    body.dark-mode .welcome-section h2 {
      color: #FE4853;
    }

    .welcome-section p {
      color: #000000; /* Black font */
    }

    body.dark-mode .welcome-section p {
      color: #e0e0e0;
    }

    /* Stats Grid - 5 cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 1rem;
      margin-bottom: 2.5rem;
      padding: 0 1rem;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem 1rem;
      box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
      text-align: center;
      transition: transform 0.18s, background 0.3s, box-shadow 0.3s;
      color: #000000; /* Black font */
    }

    body.dark-mode .stat-card {
      background: #3a3a3a;
      box-shadow: 0 4px 16px rgba(0,0,0,0.3);
      color: #e0e0e0;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 20px rgba(254, 72, 83, 0.15);
    }

    .stat-icon {
      font-size: 2rem;
      color: #FE4853;
      margin-bottom: 0.5rem;
    }

    body.dark-mode .stat-icon {
      color: #FE4853;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: #000000;
    }

    body.dark-mode .stat-value {
      color: #FE4853;
    }

    .stat-label {
      color: #000000; /* Black font */
      font-size: 0.9rem;
      margin-top: 0.3rem;
    }

    body.dark-mode .stat-label {
      color: #e0e0e0;
    }

    /* Color coding for status */
    .stat-card.pending .stat-icon,
    .stat-card.pending .stat-value {
      color: #f59e0b;
    }
    .stat-card.approved .stat-icon,
    .stat-card.approved .stat-value {
      color: #10b981;
    }
    .stat-card.rejected .stat-icon,
    .stat-card.rejected .stat-value {
      color: #ef4444;
    }
    .stat-card.archived .stat-icon,
    .stat-card.archived .stat-value {
      color: #6E6E6E;
    }

    /* Quick Links */
    .quick-links {
      margin: 2rem 1rem;
    }

    .quick-links h3 {
      margin-bottom: 1.2rem;
      color: #000000; /* Black font */
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    body.dark-mode .quick-links h3 {
      color: #e0e0e0;
    }

    .quick-links h3 i {
      color: #FE4853;
    }

    .links-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
    }

    .quick-btn {
      padding: 1rem 1.5rem;
      background: #FE4853;
      color: white;
      text-align: center;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .quick-btn:hover {
      background: #000000;
      transform: translateY(-2px);
    }

    body.dark-mode .quick-btn {
      background: #FE4853;
    }

    body.dark-mode .quick-btn:hover {
      background: #000000;
    }

    /* ====================================
       RECENT FEEDBACK SECTION
    ==================================== */
    .recent-feedback {
      margin: 2rem 1rem;
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
      color: #000000; /* Black font */
    }

    body.dark-mode .recent-feedback {
      background: #3a3a3a;
      color: #e0e0e0;
    }

    .recent-feedback h3 {
      color: #000000;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    body.dark-mode .recent-feedback h3 {
      color: #FE4853;
    }

    .recent-feedback h3 i {
      color: #FE4853;
    }

    .feedback-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .feedback-item {
      padding: 1rem;
      background: #f8fafc;
      border-radius: 8px;
      border-left: 3px solid #FE4853;
      transition: transform 0.2s;
      position: relative;
      color: #000000; /* Black font */
    }

    .feedback-item:hover {
      transform: translateX(5px);
    }

    body.dark-mode .feedback-item {
      background: #4a4a4a;
      color: #e0e0e0;
    }

    .feedback-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
      color: #000000; /* Black font */
    }

    .feedback-thesis {
      font-weight: 600;
      color: #000000;
    }

    body.dark-mode .feedback-thesis {
      color: #FE4853;
    }

    .feedback-date {
      font-size: 0.8rem;
      color: #000000; /* Black font */
    }

    body.dark-mode .feedback-date {
      color: #e0e0e0;
    }

    .feedback-from {
      font-size: 0.85rem;
      color: #000000; /* Black font */
      margin-bottom: 0.5rem;
    }

    body.dark-mode .feedback-from {
      color: #e0e0e0;
    }

    .feedback-from i {
      color: #FE4853;
      margin-right: 0.3rem;
    }

    .feedback-content {
      color: #000000; /* Black font */
      line-height: 1.5;
    }

    body.dark-mode .feedback-content {
      color: #e0e0e0;
    }

    .notification-indicator {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #10b981;
      color: white;
      font-size: 0.7rem;
      padding: 2px 6px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
    }

    .view-all-link {
      display: inline-block;
      margin-top: 1rem;
      color: #FE4853;
      text-decoration: none;
      font-weight: 500;
    }

    .view-all-link:hover {
      text-decoration: underline;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      left: -300px;
      width: 280px;
      height: 100vh;
      background: linear-gradient(180deg, #000000 0%, #732529 100%);
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

    .logout-btn:hover i {
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

    .toggle-label .fa-sun {
      color: white;
    }

    .toggle-label .fa-moon {
      color: rgba(255, 255, 255, 0.8);
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

    /* Animations */
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

    /* Dropdown Enhancements */
    .dropdown-content {
      z-index: 9999 !important;
    }

    .dropdown-content::before {
      content: '';
      position: absolute;
      top: -8px;
      right: 20px;
      width: 0;
      height: 0;
      border-left: 8px solid transparent;
      border-right: 8px solid transparent;
      border-bottom: 8px solid white;
    }

    body.dark-mode .dropdown-content::before {
      border-bottom-color: #3a3a3a;
    }

    .dropdown-content a {
      transition: all 0.2s ease;
      border-left: 3px solid transparent;
    }

    .dropdown-content a:hover {
      border-left-color: #FE4853;
      background-color: #f5f5f5;
      padding-left: 19px;
    }

    body.dark-mode .dropdown-content a:hover {
      border-left-color: #FE4853;
      background-color: #4a4a4a;
    }

    .avatar {
      cursor: pointer;
      user-select: none;
      position: relative;
    }

    .avatar::after {
      content: '▼';
      position: absolute;
      bottom: -5px;
      right: -5px;
      font-size: 8px;
      color: white;
      background: #FE4853;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid white;
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .avatar:hover::after {
      opacity: 1;
    }

    body.dark-mode .avatar::after {
      background: #732529;
      border-color: #1e293b;
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .stats-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 1rem;
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

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .links-grid {
        grid-template-columns: 1fr;
      }

      .welcome-section h2 {
        font-size: 1.5rem;
      }

      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.show {
        transform: translateX(0);
      }

      .mobile-menu-btn {
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .avatar {
        width: 38px;
        height: 38px;
        font-size: 1rem;
      }

      .dropdown-content {
        min-width: 160px;
        right: -10px;
      }

      .notification-dropdown {
        width: 320px;
        right: -50px;
      }

      .feedback-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.3rem;
      }
    }

    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .stat-card {
        padding: 1.2rem;
      }
      
      .quick-btn {
        padding: 0.75rem 1rem;
      }

      .avatar {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
      }

      .notification-bell {
        font-size: 1.1rem;
      }

      .notification-dropdown {
        width: 280px;
        right: -70px;
      }

      .topbar h1 {
        font-size: 1.3rem;
      }
    }
  </style>
</head>
<body>

<!-- OVERLAY -->
<div class="overlay" id="overlay"></div>

<!-- MOBILE MENU BUTTON -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h2>Theses Archive</h2>
    <p>Student Portal</p>
  </div>

  <nav class="sidebar-nav">
    <a href="student_dashboard.php" class="nav-link active">
      <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="projects.php" class="nav-link">
      <i class="fas fa-folder-open"></i> My Projects
    </a>
    <a href="submission.php" class="nav-link">
      <i class="fas fa-upload"></i> Submit Thesis
    </a>
    <a href="archived.php" class="nav-link">
      <i class="fas fa-archive"></i> Archived Theses
    </a>
    <a href="profile.php" class="nav-link">
      <i class="fas fa-user-circle"></i> Profile
    </a>
    <a href="notification.php" class="nav-link">
      <i class="fas fa-bell"></i> Notifications
      <?php if ($unreadCount > 0): ?>
        <span style="background: white; color: #FE4853; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: auto;"><?= $unreadCount ?></span>
      <?php endif; ?>
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
    <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</aside>

<div class="layout">
  <main class="main-content">

    <header class="topbar">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <!-- Three-line menu -->
        <div class="hamburger-menu" id="hamburgerBtn">
          <i class="fas fa-bars"></i>
        </div>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
      </div>

      <div class="user-info">
        <!-- Notification Container with Dropdown -->
        <div class="notification-container">
          <div class="notification-bell" id="notificationBell">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
              <span class="notification-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
          </div>
          
          <!-- Notification Dropdown -->
          <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
              <h4>Notifications</h4>
              <a href="#" id="markAllRead">Mark all as read</a>
            </div>
            <div class="notification-list">
              <?php if (empty($recentNotifications)): ?>
                <div class="notification-item">
                  <div class="no-notifications">No notifications</div>
                </div>
              <?php else: ?>
                <?php foreach ($recentNotifications as $notif): ?>
                  <div class="notification-item <?= $notif['status'] == 'unread' ? 'unread' : '' ?>"
                       data-notification-id="<?= $notif['id'] ?? '' ?>"
                       onclick="markAsRead(this)">
                    <div class="notif-message">
                      <?php if (strpos($notif['message'], 'feedback') !== false): ?>
                        <i class="fas fa-comment"></i>
                      <?php elseif (strpos($notif['message'], 'approved') !== false): ?>
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                      <?php elseif (strpos($notif['message'], 'rejected') !== false): ?>
                        <i class="fas fa-times-circle" style="color: #ef4444;"></i>
                      <?php endif; ?>
                      <?= htmlspecialchars($notif['message'] ?? '') ?>
                    </div>
                    <?php if (!empty($notif['thesis_title'])): ?>
                      <div class="notif-thesis">
                        <i class="fas fa-book"></i> <?= htmlspecialchars($notif['thesis_title']) ?>
                      </div>
                    <?php endif; ?>
                    <div class="notif-time"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="notification-footer">
              <a href="notification.php">View all notifications</a>
            </div>
          </div>
        </div>

        <!-- Avatar Dropdown -->
        <div class="avatar-dropdown">
          <div class="avatar" id="avatarBtn">
            <?= htmlspecialchars($initials) ?>
          </div>
          <div class="dropdown-content" id="dropdownMenu">
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <hr>
            <a href="/ArchivingThesis/authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
          </div>
        </div>
      </div>
    </header>

    <div class="welcome-section">
      <h2>Welcome, <?= htmlspecialchars($first) ?>!</h2>
      <p>Here's an overview of your thesis submissions.</p>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
      <div class="stat-card pending">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= $pendingCount ?></div>
        <div class="stat-label">Pending Review</div>
      </div>

      <div class="stat-card approved">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $approvedCount ?></div>
        <div class="stat-label">Approved</div>
      </div>

      <div class="stat-card rejected">
        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
        <div class="stat-value"><?= $rejectedCount ?></div>
        <div class="stat-label">Rejected</div>
      </div>

      <div class="stat-card archived">
        <div class="stat-icon"><i class="fas fa-archive"></i></div>
        <div class="stat-value"><?= $archivedCount ?></div>
        <div class="stat-label">Archived</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
        <div class="stat-value"><?= $totalCount ?></div>
        <div class="stat-label">Total</div>
      </div>
    </div>
    <?php if (!empty($recentFeedback)): ?>
    <div class="recent-feedback">
      <h3><i class="fas fa-comments"></i> Recent Feedback from Faculty</h3>
      <div class="feedback-list">
        <?php foreach ($recentFeedback as $fb): ?>
          <div class="feedback-item">
            <div class="feedback-header">
              <span class="feedback-thesis">
                <i class="fas fa-book"></i> <?= htmlspecialchars($fb['thesis_title']) ?>
              </span>
              <span class="feedback-date">
                <i class="fas fa-clock"></i> <?= date('M d, Y', strtotime($fb['feedback_date'])) ?>
              </span>
            </div>
            <div class="feedback-from">
              <i class="fas fa-user-tie"></i> From: <?= htmlspecialchars($fb['faculty_first'] . ' ' . $fb['faculty_last']) ?>
            </div>
            <div class="feedback-content">
              <?= nl2br(htmlspecialchars(substr($fb['comments'], 0, 150))) ?>
              <?php if (strlen($fb['comments']) > 150): ?>...<?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <a href="feedback_history.php" class="view-all-link">View all feedback <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php endif; ?>

  </main>
</div>

<script>
  // Dark mode toggle
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

  if (avatarBtn) {
    avatarBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle('show');
      notificationDropdown.classList.remove('show');
    });
  }
  
  const notificationBell = document.getElementById('notificationBell');
  const notificationDropdown = document.getElementById('notificationDropdown');

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

  // Mark all as read
  document.getElementById('markAllRead')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    fetch('/ArchivingThesis/student/mark_all_read.php', {
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
        if (badge) {
          badge.remove();
        }
        
        // Update sidebar badge
        const sidebarBadge = document.querySelector('.sidebar-nav .nav-link:last-child span');
        if (sidebarBadge) {
          sidebarBadge.remove();
        }
      }
    })
    .catch(error => console.error('Error:', error));
  });

  function markAsRead(element) {
    var notificationId = element.getAttribute('data-notification-id');
    
    if (!notificationId) {
      console.error('Notification ID not found');
      return;
    }
    
    fetch('/ArchivingThesis/student/mark_notification_read.php', {
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
        if (badge) {
          let currentCount = parseInt(badge.textContent);
          if (currentCount > 1) {
            badge.textContent = currentCount - 1;
            
            // Update sidebar badge
            const sidebarBadge = document.querySelector('.sidebar-nav .nav-link:last-child span');
            if (sidebarBadge) {
              sidebarBadge.textContent = currentCount - 1;
            }
          } else {
            badge.remove();
            
            // Remove sidebar badge
            const sidebarBadge = document.querySelector('.sidebar-nav .nav-link:last-child span');
            if (sidebarBadge) {
              sidebarBadge.remove();
            }
          }
        }
      }
    })
    .catch(error => console.error('Error:', error));
  }

  // Mobile menu toggle
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');

  if (mobileBtn) {
    mobileBtn.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
      
      const icon = mobileBtn.querySelector('i');
      if (sidebar.classList.contains('show')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
      } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    });
  }
  const hamburgerBtn = document.getElementById('hamburgerBtn');
  if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
      
      const icon = hamburgerBtn.querySelector('i');
      if (sidebar.classList.contains('show')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
      } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    });
  }

  // Close sidebar when clicking on overlay
  if (overlay) {
    overlay.addEventListener('click', function() {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
      
      const mobileIcon = mobileBtn?.querySelector('i');
      if (mobileIcon) {
        mobileIcon.classList.remove('fa-times');
        mobileIcon.classList.add('fa-bars');
      }
      
      const hamburgerIcon = hamburgerBtn?.querySelector('i');
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
        if (mobileIcon) {
          mobileIcon.classList.remove('fa-times');
          mobileIcon.classList.add('fa-bars');
        }
        
        const hamburgerIcon = hamburgerBtn?.querySelector('i');
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