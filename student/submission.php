<?php
session_start();
include("../config/db.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user_id"])) {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

/* ================================
   GET STUDENT ID - WITH DEBUGGING
================================ */
error_log("=== STUDENT ID DEBUG ===");
error_log("User ID from session: " . $user_id);

// Check student_table columns
$studentColumns = $conn->query("SHOW COLUMNS FROM student_table");
$studentCols = [];
while ($col = $studentColumns->fetch_assoc()) {
    $studentCols[] = $col['Field'];
}
error_log("Student table columns: " . implode(', ', $studentCols));

// Get student_id
$studentQuery = "SELECT student_id FROM student_table WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$studentResult = $stmt->get_result();
$studentData = $studentResult->fetch_assoc();
$stmt->close();

if (!$studentData) {
    error_log("No student record found for user_id: " . $user_id);
    
    // Get user details
    $userQuery = "SELECT * FROM user_table WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userData = $userResult->fetch_assoc();
    $stmt->close();
    
    if ($userData) {
        error_log("User data found: " . json_encode($userData));
        
        // Build insert query
        $insertFields = ['user_id'];
        $insertValues = [$user_id];
        $paramTypes = "i";
        
        if (in_array('first_name', $studentCols) && isset($userData['first_name'])) {
            $insertFields[] = 'first_name';
            $insertValues[] = $userData['first_name'];
            $paramTypes .= "s";
        }
        
        if (in_array('last_name', $studentCols) && isset($userData['last_name'])) {
            $insertFields[] = 'last_name';
            $insertValues[] = $userData['last_name'];
            $paramTypes .= "s";
        }
        
        if (in_array('email', $studentCols) && isset($userData['email'])) {
            $insertFields[] = 'email';
            $insertValues[] = $userData['email'];
            $paramTypes .= "s";
        }
        
        $placeholders = implode(', ', array_fill(0, count($insertValues), '?'));
        $insertStudent = "INSERT INTO student_table (" . implode(', ', $insertFields) . ") VALUES ($placeholders)";
        
        error_log("Insert student query: " . $insertStudent);
        
        $stmt = $conn->prepare($insertStudent);
        if ($stmt) {
            $stmt->bind_param($paramTypes, ...$insertValues);
            
            if ($stmt->execute()) {
                $student_id = $stmt->insert_id;
                error_log("Created new student record with ID: " . $student_id);
            } else {
                error_log("Failed to insert student record: " . $stmt->error);
                $student_id = $user_id;
                error_log("Using user_id as fallback: " . $student_id);
            }
            $stmt->close();
        } else {
            error_log("Failed to prepare student insert: " . $conn->error);
            $student_id = $user_id;
            error_log("Using user_id as fallback: " . $student_id);
        }
    } else {
        error_log("No user data found for user_id: " . $user_id);
        $student_id = $user_id;
        error_log("Using user_id as fallback: " . $student_id);
    }
} else {
    $student_id = $studentData['student_id'];
    error_log("Found existing student record with ID: " . $student_id);
}

error_log("Final student_id to use: " . $student_id);
error_log("=== END STUDENT ID DEBUG ===");

// Fetch user information for display
$stmt = $conn->prepare("SELECT first_name, last_name FROM user_table WHERE user_id = ? LIMIT 1");
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

// Get unread notifications count
$notificationCount = 0;
try {
    $notif_query = "SELECT COUNT(*) as total FROM notification_table WHERE user_id = ? AND status = 'unread'";
    $stmt = $conn->prepare($notif_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $notifResult = $stmt->get_result()->fetch_assoc();
    $notificationCount = $notifResult['total'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    $notificationCount = 0;
}

// Handle form submission
$successMessage = "";
$formErrors = [];
$uploadDir = __DIR__ . "/../uploads/manuscripts/";

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title    = trim($_POST["title"] ?? "");
    $abstract = trim($_POST["abstract"] ?? "");
    $adviser  = trim($_POST["adviser"] ?? "");
    $keywords = trim($_POST["keywords"] ?? "");   

    // Validation
    if (empty($title)) $formErrors[] = "Thesis title is required.";
    if (empty($abstract)) $formErrors[] = "Abstract is required.";
    if (empty($adviser)) $formErrors[] = "Adviser name is required.";
    
    if (strlen($title) < 5) $formErrors[] = "Title must be at least 5 characters long.";
    if (strlen($title) > 255) $formErrors[] = "Title must not exceed 255 characters.";
    
    if (strlen($abstract) < 50) $formErrors[] = "Abstract must be at least 50 characters long.";
    if (strlen($abstract) > 5000) $formErrors[] = "Abstract must not exceed 5000 characters.";

    // File validation
    if (empty($_FILES["manuscript"]["name"])) {
        $formErrors[] = "Please upload the manuscript (PDF).";
    } else {
        $file = $_FILES["manuscript"];
        $fileName = $file["name"];
        $fileTmp = $file["tmp_name"];
        $fileSize = $file["size"];
        $fileError = $file["error"];
        
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if ($ext !== "pdf") {
            $formErrors[] = "Only PDF files are allowed.";
        }
        
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        if ($fileSize > $maxFileSize) {
            $formErrors[] = "File size must not exceed 10MB.";
        }
        
        if ($fileError !== 0) {
            $formErrors[] = "Error uploading file. Please try again.";
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);
        
        if ($mimeType !== 'application/pdf') {
            $formErrors[] = "The file must be a valid PDF document.";
        }
    }

    if (empty($formErrors)) {
        // Upload file
        $timestamp = time();
        $uniqueId = uniqid();
        $safeTitle = preg_replace('/[^a-zA-Z0-9]/', '_', $title);
        $safeTitle = substr($safeTitle, 0, 50);
        $newFileName = $timestamp . '_' . $uniqueId . '_' . $safeTitle . '.pdf';
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($fileTmp, $uploadPath)) {
            chmod($uploadPath, 0644);
            
            $dbFilePath = 'uploads/manuscripts/' . $newFileName;
            
            /* ================================
               INSERT THESIS
            ================================ */
            error_log("=== THESIS INSERT DEBUG ===");
            error_log("Student ID: " . $student_id);
            error_log("Title: " . $title);
            
            // Validate student_id
            if (empty($student_id) || $student_id <= 0) {
                error_log("WARNING: Invalid student_id, using user_id");
                $student_id = $user_id;
            }
            
            $sql = "INSERT INTO thesis_table (student_id, title, abstract, adviser, status, file_path, date_submitted) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $status = 'pending';
                $date_submitted = date('Y-m-d H:i:s');
                
                $stmt->bind_param("issssss", $student_id, $title, $abstract, $adviser, $status, $dbFilePath, $date_submitted);
                
                if ($stmt->execute()) {
                    $thesisId = $stmt->insert_id;
                    error_log("Thesis inserted successfully with ID: " . $thesisId);

                    /* ================================
                       NOTIFY FACULTY - UPDATED VERSION
                       Based on your notification_table structure
                    ================================ */
                    try {
                        error_log("=== START NOTIFICATION ===");
                        
                        // Get all faculty members (role_id = 3)
                        $facultyQuery = "SELECT user_id FROM user_table WHERE role_id = 3";
                        $facultyResult = $conn->query($facultyQuery);
                        
                        if ($facultyResult && $facultyResult->num_rows > 0) {
                            $studentName = $first . ' ' . $last;
                            $shortTitle = substr($title, 0, 50) . (strlen($title) > 50 ? '...' : '');
                            $message = "New thesis from $studentName: \"$shortTitle\"";
                            
                            $notificationsInserted = 0;
                            
                            while ($faculty = $facultyResult->fetch_assoc()) {
                                $facultyId = $faculty['user_id'];
                                
                                // Insert notification with all required fields
                                $notifSql = "INSERT INTO notification_table (user_id, thesis_id, message, status, created_at) 
                                            VALUES (?, ?, ?, 'unread', NOW())";
                                $notifStmt = $conn->prepare($notifSql);
                                $notifStmt->bind_param("iis", $facultyId, $thesisId, $message);
                                
                                if ($notifStmt->execute()) {
                                    $notificationsInserted++;
                                    error_log("Notification sent to faculty: $facultyId for thesis ID: $thesisId");
                                } else {
                                    error_log("Error sending to faculty $facultyId: " . $notifStmt->error);
                                }
                                $notifStmt->close();
                            }
                            
                            error_log("Total notifications sent: $notificationsInserted");
                            
                            if ($notificationsInserted > 0) {
                                $successMessage = "Thesis submitted successfully! Faculty members have been notified.";
                            } else {
                                $successMessage = "Thesis submitted successfully!";
                            }
                        } else {
                            error_log("No faculty members found with role_id = 3");
                            $successMessage = "Thesis submitted successfully!";
                        }
                        
                    } catch (Exception $e) {
                        error_log("Notification error: " . $e->getMessage());
                        $successMessage = "Thesis submitted successfully!";
                    }
                    
                    $_POST = [];
                    
                } else {
                    $formErrors[] = "Database error: Failed to save thesis information.";
                    error_log("SQL Error: " . $stmt->error);
                }
                $stmt->close();
            } else {
                $formErrors[] = "System error: Failed to prepare query.";
                error_log("Prepare Error: " . $conn->error);
            }
            error_log("=== END THESIS INSERT DEBUG ===");
        } else {
            $formErrors[] = "Failed to upload file. Please check directory permissions.";
            error_log("Upload Error: Failed to move file to " . $uploadPath);
        }
    }
}

// Get recent submissions
$recentSubmissions = [];
try {
    $recentQuery = "SELECT thesis_id, title, status, date_submitted 
                   FROM thesis_table 
                   WHERE student_id = ? 
                   ORDER BY date_submitted DESC 
                   LIMIT 5";
    $stmt = $conn->prepare($recentQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentSubmissions[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Recent submissions error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Thesis - Theses Archiving System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ====================================
       RESET AND BASE STYLES
    ==================================== */
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

    /* ====================================
       SIDEBAR - RED BACKGROUND
    ==================================== */
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

    .logout-btn:hover i {
      color: white;
    }

    /* ====================================
       THEME TOGGLE
    ==================================== */
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

    /* ====================================
       OVERLAY
    ==================================== */
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

    /* ====================================
       MAIN CONTENT
    ==================================== */
    .main-content {
      flex: 1;
      margin-left: 0;
      min-height: 100vh;
      padding: 2rem;
    }

    /* ====================================
       TOPBAR
    ==================================== */
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
      color: #732529;
    }

    body.dark-mode .topbar h1 {
      color: #FE4853;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    /* Three-line menu */
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

    /* ====================================
       NOTIFICATION STYLES
    ==================================== */
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
      color: #FE4853;
    }

    body.dark-mode .notification-bell {
      color: #e0e0e0;
    }

    body.dark-mode .notification-bell:hover {
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

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    /* ====================================
       AVATAR DROPDOWN
    ==================================== */
    .avatar-dropdown {
      position: relative;
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
    }

    body.dark-mode .dropdown-content {
      background: #3a3a3a;
      border-color: #6E6E6E;
      box-shadow: 0 8px 16px rgba(0,0,0,0.3);
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
      color: #6E6E6E;
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

    /* ====================================
       SUBMISSION CONTAINER
    ==================================== */
    .submission-container {
      max-width: 900px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }

    .submission-card {
      background: white;
      border-radius: 16px;
      padding: 2.5rem;
      box-shadow: 0 4px 20px rgba(110, 110, 110, 0.1);
      margin-bottom: 2rem;
    }

    body.dark-mode .submission-card {
      background: #3a3a3a;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .submission-card h2 {
      color: #732529;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.8rem;
      font-size: 1.8rem;
    }

    body.dark-mode .submission-card h2 {
      color: #FE4853;
    }

    .submission-card h2 i {
      color: #FE4853;
    }

    .form-note {
      color: #6E6E6E;
      margin-bottom: 2rem;
      font-size: 0.95rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #e9ecef;
    }

    body.dark-mode .form-note {
      color: #94a3b8;
      border-bottom-color: #6E6E6E;
    }

    /* ====================================
       FORM GROUPS
    ==================================== */
    .form-group {
      margin-bottom: 1.8rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #732529;
      font-size: 0.95rem;
    }

    body.dark-mode .form-group label {
      color: #FE4853;
    }

    .form-group label i {
      color: #FE4853;
      margin-right: 0.5rem;
      width: 18px;
    }

    .required {
      color: #FE4853;
      margin-left: 0.25rem;
    }

    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group textarea {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: white;
    }

    body.dark-mode .form-group input[type="text"],
    body.dark-mode .form-group textarea {
      background: #4a4a4a;
      border-color: #6E6E6E;
      color: #e0e0e0;
    }

    .form-group input[type="text"]:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #FE4853;
      box-shadow: 0 0 0 3px rgba(254, 72, 83, 0.1);
    }

    body.dark-mode .form-group input[type="text"]:focus,
    body.dark-mode .form-group textarea:focus {
      border-color: #FE4853;
      box-shadow: 0 0 0 3px rgba(254, 72, 83, 0.2);
    }

    .form-group textarea {
      min-height: 150px;
      resize: vertical;
    }

    .form-text {
      display: block;
      margin-top: 0.5rem;
      font-size: 0.85rem;
      color: #6E6E6E;
    }

    body.dark-mode .form-text {
      color: #94a3b8;
    }

    /* ====================================
       FILE UPLOAD
    ==================================== */
    .file-upload-wrapper {
      margin-top: 0.5rem;
    }

    .file-upload-wrapper input[type="file"] {
      padding: 0.75rem;
      background: #f8fafc;
      border: 2px dashed #e2e8f0;
      cursor: pointer;
    }

    body.dark-mode .file-upload-wrapper input[type="file"] {
      background: #4a4a4a;
      border-color: #6E6E6E;
    }

    .file-upload-wrapper input[type="file"]:hover {
      border-color: #FE4853;
      background: #fff3f3;
    }

    body.dark-mode .file-upload-wrapper input[type="file"]:hover {
      border-color: #FE4853;
      background: #5a5a5a;
    }

    .file-upload-info {
      margin-top: 0.75rem;
      padding: 0.75rem;
      background: #f8fafc;
      border-radius: 6px;
      font-size: 0.9rem;
      color: #6E6E6E;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    body.dark-mode .file-upload-info {
      background: #4a4a4a;
      color: #e0e0e0;
    }

    .file-upload-info i {
      color: #FE4853;
    }

    /* ====================================
       FORM FOOTER
    ==================================== */
    .form-footer {
      display: flex;
      gap: 1rem;
      margin-top: 2.5rem;
      flex-wrap: wrap;
    }

    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn.primary {
      background: #FE4853;
      color: white;
    }

    .btn.primary:hover:not(:disabled) {
      background: #732529;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(254, 72, 83, 0.3);
    }

    .btn.primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .btn.secondary {
      background: #e2e8f0;
      color: #6E6E6E;
    }

    .btn.secondary:hover {
      background: #cbd5e1;
      transform: translateY(-2px);
    }

    body.dark-mode .btn.secondary {
      background: #4a4a4a;
      color: #e0e0e0;
    }

    body.dark-mode .btn.secondary:hover {
      background: #5a5a5a;
    }

    /* ====================================
       SUCCESS MESSAGE
    ==================================== */
    .success-message {
      background: #dcfce7;
      color: #166534;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      border-left: 4px solid #22c55e;
      animation: slideDown 0.3s ease;
    }

    body.dark-mode .success-message {
      background: #1a3a2a;
      color: #86efac;
      border-left-color: #4ade80;
    }

    .success-message i {
      font-size: 1.5rem;
    }

    /* ====================================
       ERROR MESSAGES
    ==================================== */
    .error-container {
      background: #fee2e2;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      border-left: 4px solid #ef4444;
      animation: slideDown 0.3s ease;
    }

    body.dark-mode .error-container {
      background: #3a1a1a;
    }

    .error-list {
      padding: 1rem 1.5rem;
      margin: 0;
      list-style: none;
    }

    .error-list li {
      color: #b91c1c;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    body.dark-mode .error-list li {
      color: #fca5a5;
    }

    .error-list li:last-child {
      margin-bottom: 0;
    }

    .error-list li i {
      color: #ef4444;
    }

    /* ====================================
       RECENT SUBMISSIONS
    ==================================== */
    .recent-submissions {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(110, 110, 110, 0.1);
    }

    body.dark-mode .recent-submissions {
      background: #3a3a3a;
    }

    .recent-submissions h3 {
      color: #732529;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.8rem;
      font-size: 1.3rem;
    }

    body.dark-mode .recent-submissions h3 {
      color: #FE4853;
    }

    .recent-submissions h3 i {
      color: #FE4853;
    }

    .submissions-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .submission-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      background: #f8fafc;
      border-radius: 8px;
      transition: transform 0.2s ease;
    }

    body.dark-mode .submission-item {
      background: #4a4a4a;
    }

    .submission-item:hover {
      transform: translateX(5px);
    }

    .submission-info {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .submission-info h4 {
      margin: 0;
      font-size: 1rem;
      color: #732529;
    }

    body.dark-mode .submission-info h4 {
      color: #FE4853;
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }

    .status-approved {
      background: #d1fae5;
      color: #065f46;
    }

    .status-rejected {
      background: #fee2e2;
      color: #b91c1c;
    }

    .status-active {
      background: #dbeafe;
      color: #1e40af;
    }

    .status-archived {
      background: #e2e8f0;
      color: #475569;
    }

    body.dark-mode .status-pending {
      background: #92400e;
      color: #fef3c7;
    }

    body.dark-mode .status-approved {
      background: #065f46;
      color: #d1fae5;
    }

    body.dark-mode .status-rejected {
      background: #b91c1c;
      color: #fee2e2;
    }

    body.dark-mode .status-active {
      background: #1e3a5f;
      color: #93c5fd;
    }

    body.dark-mode .status-archived {
      background: #4a4a4a;
      color: #e0e0e0;
    }

    .submission-item small {
      color: #6E6E6E;
      font-size: 0.85rem;
    }

    body.dark-mode .submission-item small {
      color: #94a3b8;
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

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .fa-spinner {
      animation: spin 1s linear infinite;
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

      .submission-container {
        padding: 0 1rem;
        margin: 1rem auto;
      }
      
      .submission-card {
        padding: 1.5rem;
      }
      
      .submission-card h2 {
        font-size: 1.5rem;
      }
      
      .form-footer {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
      
      .submission-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
      }
      
      .submission-info {
        width: 100%;
      }

      .avatar {
        width: 38px;
        height: 38px;
        font-size: 1rem;
      }
      
      .dropdown-content {
        right: -5px;
        min-width: 150px;
      }
    }

    @media (max-width: 480px) {
      .submission-card {
        padding: 1rem;
      }
      
      .file-upload-info {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .form-group input[type="text"],
      .form-group input[type="file"],
      .form-group textarea {
        padding: 0.6rem 0.8rem;
        font-size: 0.95rem;
      }
      
      .btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
      }
      
      .submission-info h4 {
        font-size: 0.95rem;
      }
      
      .status-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
      }
      
      .topbar h1 {
        font-size: 1.3rem;
      }
      
      .notification-bell {
        font-size: 1.1rem;
      }
      
      .avatar {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
      }
      
      .dropdown-content {
        min-width: 140px;
      }
      
      .dropdown-content a {
        padding: 10px 14px;
        font-size: 0.9rem;
      }
    }
 
    @media print {
      .submission-card {
        box-shadow: none;
        border: 1px solid #ddd;
      }
      
      .btn,
      .file-upload-wrapper,
      .notification-bell,
      .avatar-dropdown,
      .sidebar,
      .mobile-menu-btn,
      .theme-toggle,
      .logout-btn {
        display: none !important;
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
    <p>Student Portal</p>
  </div>

  <nav class="sidebar-nav">
    <a href="student_dashboard.php" class="nav-link">
      <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="projects.php" class="nav-link">
      <i class="fas fa-folder-open"></i> My Projects
    </a>
    <a href="submission.php" class="nav-link active">
      <i class="fas fa-upload"></i> Submit Thesis
    </a>
    <a href="archived.php" class="nav-link">
      <i class="fas fa-archive"></i> Archived Theses
    </a>
    <a href="profile.php" class="nav-link">
      <i class="fas fa-user-circle"></i> Profile
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
         <div class="hamburger-menu" id="hamburgerBtn">
          <i class="fas fa-bars"></i>
        </div>
        <h1>Thesis Submission</h1>
      </div>

      <div class="user-info">
         <div class="notification-container">
          <a href="notification.php" class="notification-bell" id="notificationBell">
            <i class="fas fa-bell"></i>
            <?php if ($notificationCount > 0): ?>
              <span class="notification-badge"><?= $notificationCount ?></span>
            <?php endif; ?>
          </a>
        </div>
        
         <div class="avatar-container">
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
      </div>
    </header>

    <div class="submission-container">

      <?php if ($successMessage): ?>
        <div class="success-message">
          <i class="fas fa-check-circle"></i>
          <?= htmlspecialchars($successMessage) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($formErrors)): ?>
        <div class="error-container">
          <ul class="error-list">
            <?php foreach ($formErrors as $err): ?>
              <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="submission-card">
        <h2><i class="fas fa-upload"></i> New Thesis Submission</h2>
        <p class="form-note">Fields marked with <span class="required">*</span> are required</p>

        <form method="POST" enctype="multipart/form-data" id="submissionForm">

          <div class="form-group">
            <label for="title">
              <i class="fas fa-heading"></i> Thesis Title <span class="required">*</span>
            </label>
            <input type="text" id="title" name="title" required
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                   placeholder="Enter the full title of your thesis"
                   minlength="5" maxlength="255">
            <small class="form-text">Minimum 5 characters, maximum 255 characters</small>
          </div>

          <div class="form-group">
            <label for="abstract">
              <i class="fas fa-align-left"></i> Abstract <span class="required">*</span>
            </label>
            <textarea id="abstract" name="abstract" required
                      placeholder="Provide a comprehensive summary of your thesis (minimum 50 characters)"
                      minlength="50" maxlength="5000"><?= htmlspecialchars($_POST['abstract'] ?? '') ?></textarea>
            <small class="form-text">Minimum 50 characters, maximum 5000 characters</small>
          </div>

          <div class="form-group">
            <label for="keywords">
              <i class="fas fa-tags"></i> Keywords
            </label>
            <input type="text" id="keywords" name="keywords"
                   value="<?= htmlspecialchars($_POST['keywords'] ?? '') ?>"
                   placeholder="e.g., Machine Learning, Education, Data Analysis (separate with commas)">
            <small class="form-text">Optional: Add keywords to make your thesis more discoverable</small>
          </div>

          <div class="form-group">
            <label for="adviser">
              <i class="fas fa-user-tie"></i> Thesis Adviser <span class="required">*</span>
            </label>
            <input type="text" id="adviser" name="adviser" required
                   value="<?= htmlspecialchars($_POST['adviser'] ?? '') ?>"
                   placeholder="Full name of your thesis adviser">
          </div>

          <div class="form-group">
            <label for="manuscript">
              <i class="fas fa-file-pdf"></i> Upload Manuscript <span class="required">*</span>
            </label>
            <div class="file-upload-wrapper">
              <input type="file" id="manuscript" name="manuscript" accept=".pdf" required>
              <div class="file-upload-info">
                <i class="fas fa-info-circle"></i>
                <span>Accepted format: PDF only | Maximum size: 10MB</span>
              </div>
            </div>
          </div>

          <div class="form-footer">
            <button type="submit" class="btn primary" id="submitBtn">
              <i class="fas fa-paper-plane"></i> Submit for Review
            </button>
            <button type="reset" class="btn secondary" onclick="return confirm('Are you sure you want to clear the form?')">
              <i class="fas fa-undo"></i> Clear Form
            </button>
          </div>

        </form>
      </div>

      <?php if (!empty($recentSubmissions)): ?>
      <div class="recent-submissions">
        <h3><i class="fas fa-history"></i> Your Recent Submissions</h3>
        <div class="submissions-list">
          <?php foreach ($recentSubmissions as $sub): ?>
            <div class="submission-item">
              <div class="submission-info">
                <h4><?= htmlspecialchars($sub['title']) ?></h4>
                <span class="status-badge status-<?= strtolower($sub['status']) ?>">
                  <?= ucfirst(htmlspecialchars($sub['status'])) ?>
                </span>
              </div>
              <small><?= date('M d, Y', strtotime($sub['date_submitted'])) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
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

  // Avatar dropdown
  const avatarBtn = document.getElementById('avatarBtn');
  const dropdownMenu = document.getElementById('dropdownMenu');
  
  if (avatarBtn) {
    avatarBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle('show');
    });
  }
  
  window.addEventListener('click', function() {
    dropdownMenu.classList.remove('show');
  });
  
  dropdownMenu.addEventListener('click', function(e) {
    e.stopPropagation();
  });

  // Sidebar toggle
  const hamburgerBtn = document.getElementById('hamburgerBtn');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');

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

  if (overlay) {
    overlay.addEventListener('click', function() {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
      const icon = hamburgerBtn.querySelector('i');
      icon.classList.remove('fa-times');
      icon.classList.add('fa-bars');
    });
  }

  // Close sidebar when clicking nav links on mobile
  const navLinks = document.querySelectorAll('.nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        const icon = hamburgerBtn.querySelector('i');
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    });
  });

  // Form submission loading state
  const form = document.getElementById('submissionForm');
  const submitBtn = document.getElementById('submitBtn');
  
  if (form) {
    form.addEventListener('submit', function() {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    });
  }

  // File input validation
  const fileInput = document.getElementById('manuscript');
  if (fileInput) {
    fileInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const fileName = file.name;
        const fileExt = fileName.split('.').pop().toLowerCase();
        
        if (fileExt !== 'pdf') {
          alert('Please select a PDF file.');
          this.value = '';
        }
        
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
          alert('File size must not exceed 10MB.');
          this.value = '';
        }
      }
    });
  }
</script>

</body>
</html>