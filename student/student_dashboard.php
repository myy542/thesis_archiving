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

// FIXED: Rejected count - use DISTINCT to count unique theses only
$rejectedCount = 0;
try {
    $rejectedQuery = "SELECT COUNT(DISTINCT thesis_id) as total FROM thesis_table 
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

// =============== UPDATED NOTIFICATION QUERIES ===============
$unreadCount = 0;
$recentNotifications = [];

try {
    // SIMPLIFIED NOTIFICATION QUERY - direct from notification_table
    $notifQuery = "SELECT 
                    notification_id as id, 
                    message, 
                    status,
                    created_at,
                    thesis_id
                   FROM notification_table 
                   WHERE user_id = ? 
                   ORDER BY created_at DESC 
                   LIMIT 10";
    $stmt = $conn->prepare($notifQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recentNotifications = [];
    while ($row = $result->fetch_assoc()) {
        // Get thesis title if thesis_id exists
        if (!empty($row['thesis_id'])) {
            $titleQuery = "SELECT title FROM thesis_table WHERE thesis_id = ?";
            $titleStmt = $conn->prepare($titleQuery);
            $titleStmt->bind_param("i", $row['thesis_id']);
            $titleStmt->execute();
            $titleResult = $titleStmt->get_result();
            if ($titleRow = $titleResult->fetch_assoc()) {
                $row['thesis_title'] = $titleRow['title'];
            }
            $titleStmt->close();
        } else {
            $row['thesis_title'] = '';
        }
        $row['is_read'] = $row['status'];
        $recentNotifications[] = $row;
    }
    $stmt->close();
    
    // Calculate unread count
    $unreadCount = 0;
    foreach ($recentNotifications as $notif) {
        if ($notif['status'] == 'unread') {
            $unreadCount++;
        }
    }
    
} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    $unreadCount = 0;
    $recentNotifications = [];
}

// UPDATED: Recent feedback query for table format with thesis_id
$recentFeedback = [];
try {
    $feedbackQuery = "SELECT 
                        f.*, 
                        t.title as thesis_title, 
                        t.thesis_id,
                        u.first_name as faculty_first, 
                        u.last_name as faculty_last,
                        t.status as thesis_status,
                        f.comments as feedback_text,
                        f.feedback_date
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
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
      background: #f8f9fa;
      color: #333333;
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
      padding: 1rem 1.5rem;
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(211, 47, 47, 0.08);
      border: 1px solid #ffcdd2;
      color: #333333;
    }

    body.dark-mode .topbar {
      background: #3a3a3a;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      color: #e0e0e0;
      border-color: #d32f2f;
    }

    .topbar h1 {
      font-size: 1.875rem;
      color: #333333;
      font-weight: 700;
    }

    body.dark-mode .topbar h1 {
      color: #ffffff;
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
      color: #d32f2f;
      transition: color 0.2s;
      text-decoration: none;
      width: 45px;
      height: 45px;
      background: #ffebee;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .notification-bell:hover {
      background: #ffcdd2;
      color: #b71c1c;
    }

    body.dark-mode .notification-bell {
      background: #d32f2f;
      color: white;
    }

    body.dark-mode .notification-bell:hover {
      background: #b71c1c;
      color: white;
    }

    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #d32f2f;
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
      top: 55px;
      width: 380px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 30px rgba(211, 47, 47, 0.15);
      border: 1px solid #ffcdd2;
      z-index: 1000;
      color: #333333;
    }

    body.dark-mode .notification-dropdown {
      background: #3a3a3a;
      border-color: #d32f2f;
      color: #e0e0e0;
    }

    .notification-dropdown.show {
      display: block;
      animation: slideDown 0.2s ease;
    }

    .notification-header {
      padding: 15px 20px;
      border-bottom: 1px solid #ffebee;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #333333;
    }

    body.dark-mode .notification-header {
      border-bottom-color: #d32f2f;
      color: #e0e0e0;
    }

    .notification-header h4 {
      margin: 0;
      color: #b71c1c;
      font-size: 1rem;
      font-weight: 600;
    }

    body.dark-mode .notification-header h4 {
      color: #ffcdd2;
    }

    .notification-header a {
      color: #d32f2f;
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
      border-bottom: 1px solid #ffebee;
      transition: background 0.2s;
      cursor: pointer;
      color: #333333;
    }

    body.dark-mode .notification-item {
      border-bottom-color: #d32f2f;
      color: #e0e0e0;
    }

    .notification-item:hover {
      background: #ffebee;
    }

    body.dark-mode .notification-item:hover {
      background: #b71c1c;
    }

    .notification-item.unread {
      background: #ffebee;
      border-left: 3px solid #d32f2f;
    }

    body.dark-mode .notification-item.unread {
      background: #b71c1c;
    }

    .notif-message {
      font-size: 0.9rem;
      color: #333333;
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
      color: #d32f2f;
    }

    .notif-time {
      font-size: 0.75rem;
      color: #666666;
    }

    body.dark-mode .notif-time {
      color: #94a3b8;
    }

    .notif-thesis {
      font-size: 0.8rem;
      color: #d32f2f;
      margin-top: 3px;
      font-style: italic;
    }

    .no-notifications {
      text-align: center;
      color: #666666;
      padding: 30px 0;
    }

    body.dark-mode .no-notifications {
      color: #e0e0e0;
    }

    .notification-footer {
      padding: 15px 20px;
      text-align: center;
      border-top: 1px solid #ffebee;
      color: #333333;
    }

    body.dark-mode .notification-footer {
      border-top-color: #d32f2f;
      color: #e0e0e0;
    }

    .notification-footer a {
      color: #d32f2f;
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
      border-radius: 12px;
      background: #d32f2f;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .avatar:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
    }

    body.dark-mode .avatar {
      background: #b71c1c;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      top: 55px;
      background: white;
      min-width: 200px;
      box-shadow: 0 8px 30px rgba(211, 47, 47, 0.15);
      border-radius: 12px;
      z-index: 1000;
      overflow: hidden;
      border: 1px solid #ffcdd2;
      color: #333333;
    }

    body.dark-mode .dropdown-content {
      background: #3a3a3a;
      border-color: #d32f2f;
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
      color: #333333;
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
      color: #d32f2f;
    }

    .dropdown-content hr {
      border: none;
      border-top: 1px solid #ffebee;
      margin: 4px 0;
    }

    body.dark-mode .dropdown-content hr {
      border-top-color: #d32f2f;
    }

    .dropdown-content a:hover {
      background: #ffebee;
    }

    body.dark-mode .dropdown-content a:hover {
      background: #b71c1c;
    }

     .hamburger-menu {
      font-size: 1.5rem;
      cursor: pointer;
      color: #d32f2f;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      transition: all 0.3s ease;
      background: #ffebee;
    }

    .hamburger-menu:hover {
      background: #ffcdd2;
      color: #b71c1c;
    }

    body.dark-mode .hamburger-menu {
      background: #d32f2f;
      color: white;
    }

    body.dark-mode .hamburger-menu:hover {
      background: #b71c1c;
    }

     .mobile-menu-btn {
      position: fixed;
      top: 16px;
      right: 16px;
      z-index: 1001;
      border: none;
      background: #d32f2f;
      color: #fff;
      padding: 12px 15px;
      border-radius: 12px;
      cursor: pointer;
      display: none;
      font-size: 1.2rem;
      box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
      border: 1px solid #ffcdd2;
    }

    body.dark-mode .mobile-menu-btn {
      background: #b71c1c;
    }

    /* Welcome Section */
    .welcome-section {
      margin-bottom: 2.5rem;
      padding: 0 1rem;
    }

    .welcome-section h2 {
      color: #333333;
      font-size: 2.1rem;
      margin-bottom: 0.5rem;
      font-weight: 700;
    }

    body.dark-mode .welcome-section h2 {
      color: #ffcdd2;
    }

    .welcome-section p {
      color: #666666;
    }

    body.dark-mode .welcome-section p {
      color: #e0e0e0;
    }

    /* Stats Grid - 5 cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
      padding: 0 1rem;
    }

    .stat-card {
      background: white;
      border-radius: 20px;
      padding: 1.5rem 1rem;
      box-shadow: 0 4px 20px rgba(211, 47, 47, 0.08);
      text-align: center;
      transition: transform 0.18s, border-color 0.3s;
      color: #333333;
      border: 1px solid #ffcdd2;
    }

    body.dark-mode .stat-card {
      background: #3a3a3a;
      border-color: #d32f2f;
      color: #e0e0e0;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      border-color: #d32f2f;
      box-shadow: 0 10px 30px rgba(211, 47, 47, 0.15);
    }

    .stat-icon {
      font-size: 2.5rem;
      color: #d32f2f;
      margin-bottom: 0.8rem;
    }

    body.dark-mode .stat-icon {
      color: #ffcdd2;
    }

    .stat-value {
      font-size: 2.2rem;
      font-weight: 700;
      color: #333333;
    }

    body.dark-mode .stat-value {
      color: #ffffff;
    }

    .stat-label {
      color: #666666;
      font-size: 0.9rem;
      margin-top: 0.3rem;
      font-weight: 500;
    }

    body.dark-mode .stat-label {
      color: #e0e0e0;
    }

    /* Color coding for status - but with red theme */
    .stat-card.pending .stat-icon {
      color: #ef9a9a;
    }
    .stat-card.approved .stat-icon {
      color: #81c784;
    }
    .stat-card.rejected .stat-icon {
      color: #b71c1c;
    }
    .stat-card.archived .stat-icon {
      color: #999999;
    }

    /* Charts Section - New */
    .charts-section {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      margin: 2rem 1rem;
    }

    .chart-card {
      background: white;
      border-radius: 20px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(211, 47, 47, 0.08);
      border: 1px solid #ffcdd2;
    }

    body.dark-mode .chart-card {
      background: #3a3a3a;
      border-color: #d32f2f;
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .chart-header h3 {
      color: #333333;
      font-size: 1.2rem;
      font-weight: 600;
    }

    body.dark-mode .chart-header h3 {
      color: #ffcdd2;
    }

    .chart-header select {
      padding: 0.5rem 1rem;
      border: 1px solid #ffcdd2;
      border-radius: 10px;
      outline: none;
      font-size: 0.9rem;
      color: #333333;
      background: #f8f9fa;
    }

    body.dark-mode .chart-header select {
      background: #4a4a4a;
      color: #e0e0e0;
      border-color: #d32f2f;
    }

    .chart-container {
      height: 250px;
      position: relative;
    }

    /* Quick Links */
    .quick-links {
      margin: 2rem 1rem;
    }

    .quick-links h3 {
      margin-bottom: 1.2rem;
      color: #333333;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    body.dark-mode .quick-links h3 {
      color: #e0e0e0;
    }

    .quick-links h3 i {
      color: #d32f2f;
    }

    .links-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
    }

    .quick-btn {
      padding: 1rem 1.5rem;
      background: #d32f2f;
      color: white;
      text-align: center;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .quick-btn:hover {
      background: #b71c1c;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3);
    }

    body.dark-mode .quick-btn {
      background: #d32f2f;
    }

    body.dark-mode .quick-btn:hover {
      background: #b71c1c;
    }

    /* UPDATED: Recent Feedback Section - Table Format */
    .recent-feedback {
      margin: 2rem 1rem;
      background: white;
      border-radius: 20px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(211, 47, 47, 0.08);
      border: 1px solid #ffcdd2;
      color: #333333;
    }

    body.dark-mode .recent-feedback {
      background: #3a3a3a;
      border-color: #d32f2f;
      color: #e0e0e0;
    }

    .recent-feedback h3 {
      color: #333333;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    body.dark-mode .recent-feedback h3 {
      color: #ffcdd2;
    }

    .recent-feedback h3 i {
      color: #d32f2f;
    }

    .table-responsive {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      text-align: left;
      padding: 15px 10px;
      color: #666666;
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid #ffcdd2;
    }

    td {
      padding: 15px 10px;
      border-bottom: 1px solid #ffebee;
      color: #333333;
      font-size: 0.95rem;
    }

    .feedback-preview {
      max-width: 250px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: #666666;
    }

    .btn-view {
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 0.85rem;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      background: #ffebee;
      color: #333333;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .btn-view:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 10px rgba(211, 47, 47, 0.2);
      background: #ffcdd2;
    }

    body.dark-mode .btn-view {
      background: #d32f2f;
      color: white;
    }

    body.dark-mode .btn-view:hover {
      background: #b71c1c;
    }

    .view-all-link {
      display: inline-block;
      margin-top: 1rem;
      color: #d32f2f;
      text-decoration: none;
      font-weight: 500;
    }

    .view-all-link:hover {
      text-decoration: underline;
    }

    /* Sidebar - Updated to match Admin/Dean */
    .sidebar {
      position: fixed;
      top: 0;
      left: -300px;
      width: 280px;
      height: 100vh;
      background: linear-gradient(180deg, #b71c1c 0%, #d32f2f 50%, #e57373 100%);
      color: white;
      display: flex;
      flex-direction: column;
      z-index: 1000;
      transition: left 0.3s ease;
      box-shadow: 4px 0 20px rgba(211, 47, 47, 0.3);
    }

    .sidebar.show {
      left: 0;
    }

    .sidebar-header {
      padding: 2rem 1.5rem 1rem;
      text-align: center;
    }

    .sidebar-header h2 {
      font-size: 1.8rem;
      margin-bottom: 0.25rem;
      color: white;
      font-weight: 800;
      background: linear-gradient(135deg, #ffcdd2 0%, #ffffff 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .sidebar-header p {
      font-size: 0.9rem;
      color: #ffebee;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-top: 5px;
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
      color: #ffebee;
      text-decoration: none;
      border-radius: 12px;
      margin-bottom: 0.25rem;
      transition: all 0.2s;
      font-weight: 500;
    }

    .nav-link i {
      width: 20px;
      color: #ffebee;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.15);
      color: white;
      transform: translateX(5px);
    }

    .nav-link.active {
      background: #b71c1c;
      color: white;
      font-weight: 600;
      box-shadow: 0 10px 20px rgba(183, 28, 28, 0.4);
    }

    .nav-link.active i {
      color: white;
    }

    .sidebar-footer {
      padding: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
    }

    .logout-btn {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.875rem 1rem;
      color: #ffebee;
      text-decoration: none;
      border-radius: 10px;
      transition: all 0.2s;
      font-weight: 500;
    }

    .logout-btn i {
      color: #ffebee;
    }

    .logout-btn:hover {
      background: rgba(211, 47, 47, 0.5);
      color: white;
      transform: translateX(5px);
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
      background: #b71c1c;
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
      border-left-color: #d32f2f;
      background-color: #ffebee;
      padding-left: 19px;
    }

    body.dark-mode .dropdown-content a:hover {
      border-left-color: #ffcdd2;
      background-color: #b71c1c;
    }

    .avatar {
      cursor: pointer;
      user-select: none;
      position: relative;
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .stats-grid {
        grid-template-columns: repeat(3, 1fr);
      }
      .charts-section {
        grid-template-columns: 1fr;
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
    <h2>ThesisManager</h2>
    <p>STUDENT</p>
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
                       data-thesis-id="<?= $notif['thesis_id'] ?? 0 ?>"
                       onclick="markAsRead(this)">
                    <div class="notif-message">
                      <?php if (strpos($notif['message'], 'feedback') !== false): ?>
                        <i class="fas fa-comment"></i>
                      <?php elseif (strpos($notif['message'], 'approved') !== false): ?>
                        <i class="fas fa-check-circle" style="color: #81c784;"></i>
                      <?php elseif (strpos($notif['message'], 'rejected') !== false): ?>
                        <i class="fas fa-times-circle" style="color: #b71c1c;"></i>
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
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <!-- CHARTS SECTION -->
    <div class="charts-section">
      <div class="chart-card">
        <div class="chart-header">
          <h3>Project Status Distribution</h3>
          <select id="chartPeriod">
            <option>All Time</option>
            <option>This Semester</option>
            <option>This Year</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="projectStatusChart"></canvas>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-header">
          <h3>Submission Timeline</h3>
          <select id="timelinePeriod">
            <option>Last 6 Months</option>
            <option>Last Year</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="timelineChart"></canvas>
        </div>
      </div>
    </div>
    
    <!-- UPDATED: Recent Feedback from Research Adviser - Table Format with Thesis Link -->
    <?php if (!empty($recentFeedback)): ?>
    <div class="recent-feedback">
      <h3><i class="fas fa-comments"></i> Recent Feedback from Research Adviser</h3>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>PROJECT TITLE</th>
              <th>FROM</th>
              <th>FEEDBACK</th>
              <th>DATE</th>
              <th>ACTION</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentFeedback as $fb): ?>
            <tr>
              <td><?= htmlspecialchars($fb['thesis_title']) ?></td>
              <td><?= htmlspecialchars($fb['faculty_first'] . ' ' . $fb['faculty_last']) ?></td>
              <td class="feedback-preview"><?= htmlspecialchars(substr($fb['feedback_text'], 0, 100)) ?><?= strlen($fb['feedback_text']) > 100 ? '...' : '' ?></td>
              <td><?= date('M d, Y', strtotime($fb['feedback_date'])) ?></td>
              <td>
                <a href="projects.php?thesis_id=<?= $fb['thesis_id'] ?>" class="btn-view">
                  <i class="fas fa-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
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
  const notificationBell = document.getElementById('notificationBell');
  const notificationDropdown = document.getElementById('notificationDropdown');

  if (avatarBtn) {
    avatarBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle('show');
      if (notificationDropdown) notificationDropdown.classList.remove('show');
    });
  }
  
  if (notificationBell) {
    notificationBell.addEventListener('click', function(e) {
      e.stopPropagation();
      notificationDropdown.classList.toggle('show');
      if (dropdownMenu) dropdownMenu.classList.remove('show');
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

  // =============== NOTIFICATION FUNCTIONS ===============
  
  // Mark all as read
  document.getElementById('markAllRead')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    fetch('notification_handler.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ action: 'mark_all_read' })
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
      }
    })
    .catch(error => console.error('Error:', error));
  });

  // Mark as read function
  function markAsRead(element) {
    var notificationId = element.getAttribute('data-notification-id');
    var thesisId = element.getAttribute('data-thesis-id');
    
    if (!notificationId) {
      return;
    }
    
    element.style.opacity = '0.5';
    element.style.pointerEvents = 'none';
    
    fetch('notification_handler.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ 
        action: 'mark_read',
        notification_id: notificationId 
      })
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
          } else {
            badge.remove();
          }
        }
        
        if (thesisId && thesisId > 0 && thesisId != '0') {
          window.location.href = 'projects.php';
        }
      } else {
        element.style.opacity = '1';
        element.style.pointerEvents = 'auto';
      }
    })
    .catch(error => {
      element.style.opacity = '1';
      element.style.pointerEvents = 'auto';
    });
  }

  // Mobile menu toggle
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const hamburgerBtn = document.getElementById('hamburgerBtn');

  function toggleSidebar() {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
    
    const icon = hamburgerBtn?.querySelector('i');
    if (icon) {
      if (sidebar.classList.contains('show')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
      } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    }
  }

  if (mobileBtn) {
    mobileBtn.addEventListener('click', toggleSidebar);
  }
  
  if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', toggleSidebar);
  }

  if (overlay) {
    overlay.addEventListener('click', function() {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
      
      const icon = hamburgerBtn?.querySelector('i');
      if (icon) {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    });
  }
  
  const navLinks = document.querySelectorAll('.nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        
        const icon = hamburgerBtn?.querySelector('i');
        if (icon) {
          icon.classList.remove('fa-times');
          icon.classList.add('fa-bars');
        }
      }
    });
  });

  // CHARTS - Project Status Distribution
  new Chart(document.getElementById('projectStatusChart'), {
    type: 'doughnut',
    data: {
      labels: ['Pending', 'Approved', 'Rejected', 'Archived'],
      datasets: [{
        data: [<?= $pendingCount ?>, <?= $approvedCount ?>, <?= $rejectedCount ?>, <?= $archivedCount ?>],
        backgroundColor: ['#ef9a9a', '#81c784', '#b71c1c', '#999999'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 15,
            usePointStyle: true,
            pointStyle: 'circle',
            color: '#333333'
          }
        }
      },
      cutout: '70%'
    }
  });

  // Timeline Chart
  new Chart(document.getElementById('timelineChart'), {
    type: 'line',
    data: {
      labels: ['Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
      datasets: [{
        label: 'Submissions',
        data: [2, 3, 1, 4, 3, 5, 2],
        borderColor: '#d32f2f',
        backgroundColor: 'rgba(211, 47, 47, 0.1)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(211, 47, 47, 0.1)'
          },
          ticks: {
            stepSize: 1,
            color: '#666666'
          }
        },
        x: {
          ticks: {
            color: '#666666'
          }
        }
      }
    }
  });
</script>

</body>
</html>